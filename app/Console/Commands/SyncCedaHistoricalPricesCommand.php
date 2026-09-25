<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\DataSource;
use App\Services\DataSources\Ceda\CedaAgmarknetDataProvider;
use App\Services\Forecast\ForecastingEngineService;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Console\Command;

class SyncCedaHistoricalPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'krushi:sync-ceda-historical
                            {--crop= : Specific crop slug or ID}
                            {--days=90 : Number of historical days to fetch (7-2190, up to 6 years)}
                            {--dry-run : Preview ingestion without writing to database}
                            {--forecast : Run forecasting engine for processed crops}';

    /**
     * The console command description.
     */
    protected $description = 'Backfill historical mandi prices and trends from CEDA Agmarknet API for rate predictions and charts';

    /**
     * Execute the console command.
     */
    public function handle(
        MarketPriceIngestionService $ingestionService,
        ForecastingEngineService $forecastingService
    ): int {
        $this->info('Starting CEDA Agmarknet Historical Sync Engine...');

        $dataSource = DataSource::where('code', 'ceda_agmarknet')->first();
        if (!$dataSource) {
            $this->error("Data source 'ceda_agmarknet' not found. Run 'php artisan db:seed --class=CedaDataSourceSeeder' first.");
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

        $summary = [];
        $totalReceived = 0;
        $totalInserted = 0;
        $totalUpdated = 0;

        foreach ($crops as $crop) {
            // Find mapped CEDA commodity IDs from crop_source_mappings
            $mappedCedaIds = $crop->sourceMappings()
                ->where('data_source_id', $dataSource->id)
                ->pluck('source_crop_name')
                ->filter(fn ($val) => is_numeric($val))
                ->map(fn ($val) => (int) $val)
                ->unique()
                ->values()
                ->all();

            // Fallback: search CedaAgmarknetDataProvider commodity map by crop name
            if (empty($mappedCedaIds)) {
                $mappedCedaIds = array_keys(array_filter(
                    CedaAgmarknetDataProvider::CEDA_COMMODITIES,
                    fn ($name) => strcasecmp($name, $crop->name) === 0 || stripos($crop->name, $name) !== false
                ));
            }

            if (empty($mappedCedaIds)) {
                $this->line("  ⤑ <comment>{$crop->name}</comment>: No CEDA commodity mapping found. Skipping.");
                continue;
            }

            foreach ($mappedCedaIds as $cedaId) {
                $commodityName = CedaAgmarknetDataProvider::CEDA_COMMODITIES[$cedaId] ?? "Commodity #{$cedaId}";
                $this->line("  ⤑ Ingesting history for <info>{$crop->name}</info> (CEDA: {$commodityName} [ID {$cedaId}])...");

                $result = $ingestionService->ingest($dataSource, [
                    'dry_run' => $dryRun,
                    'filters' => [
                        'commodity_id' => $cedaId,
                        'days' => $days,
                        'state_id' => CedaAgmarknetDataProvider::KARNATAKA_STATE_ID,
                    ],
                ]);

                $totalReceived += $result['received'];
                $totalInserted += $result['inserted'];
                $totalUpdated += $result['updated'];

                $forecastStatus = 'Skipped';
                if ($runForecast && !$dryRun) {
                    $fcResult = $forecastingService->getForecastsForCrop($crop->id);
                    $forecastStatus = $fcResult['is_sufficient']
                        ? "Generated ({$fcResult['observations_count']} obs)"
                        : "Insufficient ({$fcResult['observations_count']} obs)";
                }

                $summary[] = [
                    'crop' => $crop->name,
                    'ceda_commodity' => "{$commodityName} (#{$cedaId})",
                    'received' => $result['received'],
                    'inserted' => $result['inserted'],
                    'updated' => $result['updated'],
                    'duration' => $result['duration_ms'] . 'ms',
                    'forecast' => $forecastStatus,
                ];
            }
        }

        $this->newLine();
        $this->table(
            ['Crop', 'CEDA Commodity', 'Received', 'Inserted', 'Updated', 'Latency', 'Forecasting'],
            $summary
        );

        $this->info("Historical sync finished: {$totalReceived} received, {$totalInserted} inserted, {$totalUpdated} updated.");

        return Command::SUCCESS;
    }
}
