<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\State;
use Carbon\Carbon;
use Tests\TestCase;

class ComputePriceStatisticsCommandTest extends TestCase
{
    public function test_compute_statistics_artisan_command_runs_successfully(): void
    {
        $karnataka = State::where('code', 'KA')->firstOrFail();
        $market = Market::whereHas('district', fn ($q) => $q->where('state_id', $karnataka->id))->firstOrFail();
        $crop = Crop::firstOrFail();

        $today = Carbon::today()->toDateString();
        MarketPrice::firstOrCreate(
            [
                'crop_id' => $crop->id,
                'market_id' => $market->id,
                'price_date' => $today,
            ],
            [
                'min_price' => 2100,
                'max_price' => 2400,
                'modal_price' => 2300,
                'arrival_quantity' => 450,
                'unit' => 'Quintal',
            ]
        );

        $this->artisan('krushi:compute-statistics --months')
            ->expectsOutputToContain('Starting Historical Price Statistics Calculation...')
            ->expectsOutputToContain('Daily statistics computed:')
            ->expectsOutputToContain('Monthly statistics computed:')
            ->assertSuccessful();

        $this->assertDatabaseHas('price_daily_statistics', [
            'crop_id' => $crop->id,
            'record_date' => $today,
        ]);

        $this->assertDatabaseHas('price_monthly_statistics', [
            'crop_id' => $crop->id,
        ]);
    }
}
