<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\DataSource;
use App\Services\Forecast\ForecastingEngineService;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAgmarknetHistoricalPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'krushi:sync-agmarknet-historical
                            {--crop= : Specific crop slug or ID}
                            {--days=90 : Number of historical days to fetch (7-2190, up to 6 years)}
                            {--dry-run : Preview ingestion without writing to database}
                            {--forecast : Run forecasting engine for processed crops}';

    /**
     * The console command description.
     */
    protected $description = 'Backfill historical mandi prices and multi-year archives from Official AGMARKNET API (api.agmarknet.gov.in) for predictions and seasonal analysis';

    /**
     * Execute the console command.
     */
    public function handle(
        MarketPriceIngestionService $ingestionService,
        ForecastingEngineService $forecastingService
    ): int {
        $this->info('Starting Official AGMARKNET Historical Sync Engine...');

        $dataSource = DataSource::where('code', 'agmarknet_official')->first();
        if (!$dataSource) {
            $this->error("Data source 'agmarknet_official' not found. Run 'php artisan db:seed --class=KramaAndAgmarknetSeeder' first.");
            return Command::FAILURE;
        }

        $days = (int) $this->option('days');
        if ($days < 7 || $days > 2192) {
            $this->warn("Days option should be between 7 and 2190 (up to 6 years). Defaulting to 90 days.");
            $days = 90;
        }

        $dryRun = (bool) $this->option('dry-run');
        $runForecast = (bool) $this->option('forecast');

        if ($dryRun) {
            $this->comment('Running in DRY-RUN mode. No database records will be created or modified.');
        }

        $cropOption = $this->option('crop');
        $cropsQuery = Crop::where('is_active', true);

        if ($cropOption) {
            $cropsQuery->where(function ($q) use ($cropOption) {
                if (is_numeric($cropOption)) {
                    $q->where('id', (int) $cropOption);
                } else {
                    $q->where('slug', $cropOption)->orWhere('name', $cropOption);
                }
            });
        }

        $crops = $cropsQuery->orderBy('id', 'asc')->get();

        if ($crops->isEmpty()) {
            $this->error("No active crops matching criteria found.");
            return Command::FAILURE;
        }

        $this->info("Processing historical backfill for {$crops->count()} crop(s) over {$days} days...");

        $fromDate = Carbon::now()->subDays($days)->format('Y-m-d');
        $toDate = Carbon::now()->format('Y-m-d');

        $summary = [];
        $totalReceived = 0;
        $totalInserted = 0;
        $totalUpdated = 0;

        foreach ($crops as $crop) {
            $this->line("Fetching historical records for: <info>{$crop->name}</info> ({$fromDate} to {$toDate})");

            $result = $ingestionService->ingest($dataSource, [
                'dry_run' => $dryRun,
                'force' => true,
                'filters' => [
                    'crop' => $crop->name,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
            ]);

            $totalReceived += $result['received'] ?? 0;
            $totalInserted += $result['inserted'] ?? 0;
            $totalUpdated += $result['updated'] ?? 0;

            $summary[] = [
                'Crop' => $crop->name,
                'Status' => $result['status'] ?? 'unknown',
                'Received' => $result['received'] ?? 0,
                'Inserted' => $result['inserted'] ?? 0,
                'Updated' => $result['updated'] ?? 0,
            ];

            if ($runForecast && !$dryRun) {
                $this->line("  Running price forecasting engine for: {$crop->name}...");
                try {
                    $forecastingService->generateForecastsForCrop($crop);
                    $this->info("  Forecast updated successfully for {$crop->name}");
                } catch (\Throwable $e) {
                    $this->warn("  Forecast generation skipped: " . $e->getMessage());
                }
            }
        }

        $this->table(['Crop', 'Status', 'Received', 'Inserted', 'Updated'], $summary);
        $this->info("Historical backfill completed. Total Received: {$totalReceived}, Inserted: {$totalInserted}, Updated: {$totalUpdated}");

        return Command::SUCCESS;
    }
}
