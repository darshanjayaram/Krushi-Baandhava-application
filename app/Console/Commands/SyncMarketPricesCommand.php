<?php

namespace App\Console\Commands;

use App\Models\DataSource;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncMarketPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'krushi:sync-market-prices
                            {source? : Code of the specific data source (e.g. data_gov_mandi, agmarknet)}
                            {--date= : Specific target date to sync (YYYY-MM-DD), defaults to today}
                            {--force : Force sync even if raw payload checksum already exists}
                            {--dry-run : Ingest and validate raw payloads without upserting to canonical prices}
                            {--cron-only : Only process sources enrolled in automated cron schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest and canonicalize daily market prices from external data sources (data.gov.in, Agmarknet, Commodity Boards)';

    public function __construct(
        protected MarketPriceIngestionService $ingestionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceCode = $this->argument('source');
        $dateParam = $this->option('date');
        $isForced = (bool) $this->option('force');
        $isDryRun = (bool) $this->option('dry-run');
        $isCronOnly = (bool) $this->option('cron-only');

        // Determine target date(s): In morning cron (<11:00 AM), reconcile previous trading day + today
        $targetDates = [];
        if ($dateParam) {
            $targetDates = [Carbon::parse($dateParam)->toDateString()];
        } else {
            $now = Carbon::now();
            if ($now->hour < 11) {
                $prevTradingDay = Carbon::yesterday()->isSunday()
                    ? Carbon::today()->subDays(2)
                    : Carbon::yesterday();
                $targetDates = [$prevTradingDay->toDateString(), Carbon::today()->toDateString()];
            } else {
                $targetDates = [Carbon::today()->toDateString()];
            }
        }

        $this->info("============================================================");
        $this->info("   KRUSHI BAANDHAVA (ಕೃಷಿ ಬಾಂಧವ) - Market Price Ingestion   ");
        $this->info("============================================================");
        $this->line("Target Date(s): <comment>" . implode(', ', $targetDates) . "</comment>");
        $this->line("Mode:           " . ($isDryRun ? '<fg=yellow;options=bold>DRY RUN (No Canonical Writes)</>' : '<fg=green;options=bold>LIVE SYNC</>'));

        $query = DataSource::query();

        if ($sourceCode) {
            // Explicitly requested specific source runs regardless of cron status
            $query->where('code', $sourceCode);
        } else {
            $query->where('is_active', true);

            // In automated cron runs or regular schedule checks, filter by is_cron_enabled
            if ($isCronOnly || !$isForced) {
                $query->where('is_cron_enabled', true);
            }
        }

        \Illuminate\Support\Facades\Cache::forever('scheduler_last_heartbeat', now());

        $sources = $query->get();

        if (!$sourceCode && ($isCronOnly || !$isForced)) {
            $excludedCount = DataSource::where('is_active', true)->where('is_cron_enabled', false)->count();
            if ($excludedCount > 0) {
                $this->line("Cron Scope:     <fg=yellow>{$excludedCount}</> active source(s) excluded from automated cron schedule by admin.");
            }
        }

        if ($sources->isEmpty()) {
            $this->warn("No data sources found" . ($sourceCode ? " matching code '{$sourceCode}'." : " matching schedule criteria."));
            return self::SUCCESS;
        }

        $this->line("Found <info>{$sources->count()}</info> data source(s) to process.\n");

        $overallReceived = 0;
        $overallInserted = 0;
        $overallUpdated = 0;
        $overallDuplicates = 0;
        $overallRejected = 0;

        foreach ($sources as $source) {
            $source->updateQuietly(['last_heartbeat_at' => now()]);

            // If auto-running without specific source or force, check if due
            if (!$sourceCode && !$isForced && !$source->isDue()) {
                $this->line("↷ Skipping <fg=cyan>{$source->name}</> [{$source->code}]: Not due yet based on schedule ({$source->sync_frequency} @ {$source->sync_time}, {$source->sync_days}).");
                continue;
            }

            $this->info("▶ Ingesting from: <fg=cyan>{$source->name}</> [{$source->code}]");

            foreach ($targetDates as $targetDateStr) {
                if (count($targetDates) > 1) {
                    $this->line("  ↳ Date: <comment>{$targetDateStr}</comment>");
                }

                $result = $this->ingestionService->ingest($source, [
                    'force' => $isForced,
                    'dry_run' => $isDryRun,
                    'filters' => [
                        'date' => $targetDateStr,
                    ],
                ]);

                $statusColor = match ($result['status']) {
                    'success' => 'green',
                    'partial' => 'yellow',
                    default => 'red',
                };

                $this->line("    Status:        <fg={$statusColor};options=bold>" . strtoupper($result['status']) . "</>");
                $this->line("    Received:      {$result['received']}");
                $this->line("    New Inserted:  {$result['inserted']}");
                $this->line("    Updated:       {$result['updated']}");
                $this->line("    Duplicates:    {$result['duplicate']}");
                $this->line("    Rejected:      {$result['rejected']}");
                $this->line("    Duration:      {$result['duration_ms']} ms");

                $overallReceived += (int) ($result['received'] ?? 0);
                $overallInserted += (int) ($result['inserted'] ?? 0);
                $overallUpdated += (int) ($result['updated'] ?? 0);
                $overallDuplicates += (int) ($result['duplicate'] ?? 0);
                $overallRejected += (int) ($result['rejected'] ?? 0);

                if (! empty($result['errors'])) {
                    $this->warn("    Errors / Warnings:");
                    foreach (array_slice($result['errors'], 0, 5) as $err) {
                        $this->line("      • {$err}");
                    }
                }
            }

            $overallReceived += $result['received'];
            $overallInserted += $result['inserted'];
            $overallUpdated += $result['updated'];
            $overallDuplicates += $result['duplicate'];
            $overallRejected += $result['rejected'];

            $this->newLine();
        }

        $this->table(
            ['Metric', 'Total Across Sources'],
            [
                ['Total Records Received', $overallReceived],
                ['New Canonical Inserted', $overallInserted],
                ['Existing Canonical Updated', $overallUpdated],
                ['Duplicate Payloads Skipped', $overallDuplicates],
                ['Invalid/Rejected Records', $overallRejected],
            ]
        );

        $this->info("Ingestion completed successfully at " . Carbon::now()->toTimeString() . ".");

        return self::SUCCESS;
    }
}
