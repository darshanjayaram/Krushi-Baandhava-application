<?php

namespace Tests\Unit;

use App\Models\Crop;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\State;
use App\Services\Forecast\ForecastingEngineService;
use App\Services\Forecast\Models\HoltsLinearTrendModel;
use Carbon\Carbon;
use Tests\TestCase;

class ForecastingEngineServiceTest extends TestCase
{
    protected Crop $crop;
    protected Market $market;
    protected ForecastingEngineService $forecastingService;

    protected function setUp(): void
    {
        parent::setUp();

        $karnataka = State::where('code', 'KA')->firstOrFail();
        $this->market = Market::whereHas('district', fn ($q) => $q->where('state_id', $karnataka->id))->firstOrFail();
        $this->crop = Crop::where('slug', 'arecanut')->first() ?? Crop::firstOrFail();
        $this->forecastingService = new ForecastingEngineService();
    }

    public function test_data_sufficiency_rejection_when_under_30_observations(): void
    {
        // Clean recent records for this crop
        MarketPrice::where('crop_id', $this->crop->id)->delete();

        // Seed only 10 daily observations
        for ($i = 9; $i >= 0; $i--) {
            MarketPrice::create([
                'crop_id' => $this->crop->id,
                'market_id' => $this->market->id,
                'price_date' => Carbon::today()->subDays($i)->toDateString(),
                'min_price' => 45000,
                'max_price' => 50000,
                'modal_price' => 48000,
                'arrival_quantity' => 20,
                'unit' => 'Quintal',
            ]);
        }

        $result = $this->forecastingService->getForecastsForCrop($this->crop->id, $this->market->id);

        $this->assertFalse($result['is_sufficient']);
        $this->assertEquals(10, $result['observations_count']);
        $this->assertEmpty($result['horizons']);
        $this->assertStringContainsString('ವಿಶ್ವಾಸಾರ್ಹ ಮುನ್ಸೂಚನೆಗೆ ಕನಿಷ್ಠ 30 ದಿನಗಳ', $result['message_kn']);
    }

    public function test_generates_multi_horizon_projections_when_sufficient(): void
    {
        // Clean recent records
        MarketPrice::where('crop_id', $this->crop->id)->delete();

        // Seed 35 daily observations
        for ($i = 34; $i >= 0; $i--) {
            MarketPrice::create([
                'crop_id' => $this->crop->id,
                'market_id' => $this->market->id,
                'price_date' => Carbon::today()->subDays($i)->toDateString(),
                'min_price' => 45000 + ($i * 20),
                'max_price' => 50000 + ($i * 20),
                'modal_price' => 48000 + ($i * 20),
                'arrival_quantity' => 30,
                'unit' => 'Quintal',
            ]);
        }

        $result = $this->forecastingService->getForecastsForCrop($this->crop->id, $this->market->id);

        $this->assertTrue($result['is_sufficient']);
        $this->assertCount(4, $result['horizons']); // 1, 7, 15, 30 days
        $this->assertEquals(1, $result['horizons'][0]['horizon_days']);
        $this->assertEquals(7, $result['horizons'][1]['horizon_days']);
        $this->assertEquals(15, $result['horizons'][2]['horizon_days']);
        $this->assertEquals(30, $result['horizons'][3]['horizon_days']);

        foreach ($result['horizons'] as $h) {
            $this->assertGreaterThan(0, $h['expected_price']);
            $this->assertTrue($h['expected_price'] <= $h['upper_bound']);
            $this->assertTrue($h['lower_bound'] <= $h['expected_price']);
            $this->assertTrue($h['lower_bound'] >= 0);
            $this->assertGreaterThanOrEqual(20.0, $h['confidence_score']);
        }
    }

    public function test_rolling_walk_forward_backtest(): void
    {
        $model = new HoltsLinearTrendModel();
        $metrics = $this->forecastingService->backtest($model, $this->crop->id, $this->market->id, 7);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('mae', $metrics);
        $this->assertArrayHasKey('rmse', $metrics);
        $this->assertArrayHasKey('mape', $metrics);
        $this->assertArrayHasKey('directional_accuracy', $metrics);
    }
}
