<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Services\Analytics\HistoricalAnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneHistoricalMarketPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'krushi:prune-prices
                            {--days=365 : Number of rolling days to retain in the daily table}
                            {--prune-raw : Also prune matching raw API records older than retention period}
                            {--dry-run : Simulate pruning without deleting records}
                            {--force : Bypass confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Safely roll up and prune market prices older than specified retention days to keep database lean';

    public function handle(HistoricalAnalyticsService $analyticsService): int
    {
        $days = (int) $this->option('days');
        if ($days < 30) {
            $this->error('Retention days must be at least 30 to protect active forecasting horizons.');
            return Command::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $pruneRaw = (bool) $this->option('prune-raw');
        $cutoffDate = Carbon::today()->subDays($days)->toDateString();

        $this->info("=== Krushi Baandhava Rolling Retention Manager ===");
        $this->info("Retention Window : Last {$days} days");
        $this->info("Cutoff Date      : Records before {$cutoffDate} will be pruned");
        $this->info("Mode             : " . ($dryRun ? 'DRY-RUN (Simulation)' : 'LIVE EXECUTION'));

        // 1. Identify records eligible for pruning
        $staleQuery = MarketPrice::where('price_date', '<', $cutoffDate);
        $totalStale = $staleQuery->count();

        if ($totalStale === 0) {
            $this->info("No market price records older than {$cutoffDate} found. Database is already optimal.");
            return Command::SUCCESS;
        }

        $this->info("Found {$totalStale} daily price records older than {$cutoffDate}.");

        // 2. Pre-Aggregation Safety Guard: Compile monthly statistics for affected months
        $stalePeriods = MarketPrice::where('price_date', '<', $cutoffDate)
            ->selectRaw('DISTINCT YEAR(price_date) as yr, MONTH(price_date) as mo')
            ->orderBy('yr')
            ->orderBy('mo')
            ->get();

        $this->info("Running Pre-Aggregation Safety Guard across {$stalePeriods->count()} historical month(s)...");

        if (!$dryRun) {
            foreach ($stalePeriods as $period) {
                $yr = (int) $period->yr;
                $mo = (int) $period->mo;
                $this->line(" - Pre-aggregating monthly statistics for {$yr}-" . str_pad($mo, 2, '0', STR_PAD_LEFT) . "...");
                $analyticsService->computeMonthlyStatistics($yr, $mo);
                $analyticsService->updateSeasonalIndices();
            }
            $this->info("✓ Pre-aggregation complete. All historical monthly statistics & 'Best Months to Sell' preserved.");
        }

        if ($dryRun) {
            $this->comment("[Dry-Run] Would delete {$totalStale} daily price records.");
            return Command::SUCCESS;
        }

        // 3. Chunked Deletion (Prevents MySQL locks on cPanel / shared hosting)
        $this->info("Pruning {$totalStale} daily records in chunked batches...");
        $deletedCount = 0;
        $chunkSize = 2000;

        do {
            $deletedInChunk = DB::transaction(function () use ($cutoffDate, $chunkSize) {
                $ids = MarketPrice::where('price_date', '<', $cutoffDate)
                    ->limit($chunkSize)
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    return 0;
                }

                return MarketPrice::whereIn('id', $ids)->delete();
            });

            $deletedCount += $deletedInChunk;
            $this->line(" - Deleted {$deletedCount} / {$totalStale} records...");
        } while ($deletedInChunk > 0);

        // 4. Optional: Prune matching stale raw records
        $rawDeletedCount = 0;
        if ($pruneRaw) {
            $this->info("Pruning associated raw API logs older than {$cutoffDate}...");
            do {
                $rawInChunk = DB::transaction(function () use ($cutoffDate, $chunkSize) {
                    $rawIds = MarketPriceRaw::where('received_at', '<', $cutoffDate)
                        ->where('processing_status', 'processed')
                        ->limit($chunkSize)
                        ->pluck('id');

                    if ($rawIds->isEmpty()) {
                        return 0;
                    }

                    return MarketPriceRaw::whereIn('id', $rawIds)->delete();
                });

                $rawDeletedCount += $rawInChunk;
            } while ($rawInChunk > 0);
            $this->info("✓ Pruned {$rawDeletedCount} raw API records.");
        }

        // 5. Audit Logging
        AuditLog::create([
            'user_id' => null, // System automated cron
            'action' => 'prune_market_prices',
            'auditable_type' => MarketPrice::class,
            'auditable_id' => null,
            'old_values' => [
                'retention_days' => $days,
                'cutoff_date' => $cutoffDate,
            ],
            'new_values' => [
                'records_deleted' => $deletedCount,
                'raw_records_deleted' => $rawDeletedCount,
                'status' => 'completed',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'CLI: krushi:prune-prices',
        ]);

        Log::info("Krushi Baandhava Retention Pruning: Deleted {$deletedCount} daily records older than {$cutoffDate}.");
        $this->info("✓ Pruning complete. Successfully removed {$deletedCount} old records while preserving all monthly summaries.");

        return Command::SUCCESS;
    }
}
