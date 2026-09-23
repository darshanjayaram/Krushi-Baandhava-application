<?php

namespace Tests\Feature;

use App\Models\Crop;
use Tests\TestCase;

class GeneratePriceForecastsCommandTest extends TestCase
{
    public function test_generate_price_forecasts_command_runs_for_crop(): void
    {
        $crop = Crop::firstOrFail();

        $this->artisan('krushi:generate-forecasts', [
            '--crop' => $crop->slug,
            '--backtest' => true,
        ])
        ->expectsOutputToContain('Starting Krushi Baandhava Price Forecasting Engine...')
        ->assertSuccessful();
    }

    public function test_generate_price_forecasts_batch_command_runs_successfully(): void
    {
        $this->artisan('krushi:generate-forecasts')
            ->expectsOutputToContain('Starting Krushi Baandhava Price Forecasting Engine...')
            ->expectsOutputToContain('Generating batch forecasts for all active Karnataka commodities...')
            ->assertSuccessful();
    }
}
