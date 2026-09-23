<?php

namespace App\Console\Commands;

use App\Models\District;
use App\Services\Weather\WeatherSyncService;
use Illuminate\Console\Command;

class SyncWeatherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'krushi:sync-weather
                            {district? : District ID, name or code to sync specifically}
                            {--force : Force sync even if synced within the last 6 hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize 7-day hyperlocal weather forecasts and agricultural advisories from Open-Meteo';

    public function __construct(
        protected WeatherSyncService $weatherService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $districtParam = $this->argument('district');
        $force = (bool) $this->option('force');

        $this->info("============================================================");
        $this->info("   KRUSHI BAANDHAVA (ಕೃಷಿ ಬಾಂಧವ) - Weather Synchronization  ");
        $this->info("============================================================");
        $this->line("Mode: " . ($force ? '<fg=yellow;options=bold>FORCED SYNC</>' : '<fg=green;options=bold>INCREMENTAL SYNC</>'));

        if ($districtParam) {
            $district = District::where('id', $districtParam)
                ->orWhere('name', $districtParam)
                ->orWhere('code', $districtParam)
                ->first();

            if (!$district) {
                $this->error("District '{$districtParam}' not found in database.");
                return self::FAILURE;
            }

            $this->line("Syncing weather for: <comment>{$district->name}</comment> ({$district->latitude}, {$district->longitude})");
            $count = $this->weatherService->syncDistrict($district, $force);

            if ($count > 0) {
                $this->info("✓ Successfully updated {$count}-day forecast for {$district->name}.");
            } else {
                $this->comment("— Skipped {$district->name}: Already up-to-date (use --force to override).");
            }

            return self::SUCCESS;
        }

        $this->line("Starting state-wide sync for all active Karnataka districts...");
        $stats = $this->weatherService->syncAllDistricts($force);

        $this->newLine();
        $this->info("Summary:");
        $this->table(
            ['Total Districts', 'Updated', 'Skipped (Current)', 'Failed'],
            [[$stats['total'], $stats['synced'], $stats['skipped'], $stats['failed']]]
        );

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
