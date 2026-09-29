<?php

namespace App\Console\Commands;

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
    protected $description = 'Backfill historical mandi prices and trends (Deprecated alias: delegates to Official AGMARKNET krushi:sync-agmarknet-historical)';

    /**
     * Execute the console command.
     */
    public function handle(
        MarketPriceIngestionService $ingestionService,
        ForecastingEngineService $forecastingService
    ): int {
        $this->warn('Notice: CEDA Agmarknet has been upgraded to Official AGMARKNET (Govt of India - DMI).');
        $this->info('Forwarding to `krushi:sync-agmarknet-historical`...');

        return $this->call('krushi:sync-agmarknet-historical', [
            '--crop' => $this->option('crop'),
            '--days' => $this->option('days'),
            '--dry-run' => $this->option('dry-run'),
            '--forecast' => $this->option('forecast'),
        ]);
    }
}
