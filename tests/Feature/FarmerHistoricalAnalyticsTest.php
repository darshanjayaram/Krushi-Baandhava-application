<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\State;
use Carbon\Carbon;
use Tests\TestCase;

class FarmerHistoricalAnalyticsTest extends TestCase
{
    protected State $karnataka;
    protected Market $market;
    protected Crop $crop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->karnataka = State::where('code', 'KA')->firstOrFail();
        $this->market = Market::whereHas('district', fn ($q) => $q->where('state_id', $this->karnataka->id))->firstOrFail();
        $this->crop = Crop::where('slug', 'arecanut')->first() ?? Crop::firstOrFail();

        $today = Carbon::today()->toDateString();
        MarketPrice::firstOrCreate(
            [
                'crop_id' => $this->crop->id,
                'market_id' => $this->market->id,
                'price_date' => $today,
            ],
            [
                'min_price' => 45000,
                'max_price' => 52000,
                'modal_price' => 49000,
                'arrival_quantity' => 1250,
                'unit' => 'Quintal',
            ]
        );
    }

    public function test_crop_show_page_renders_historical_analytics_and_seasonality(): void
    {
        $response = $this->get(route('farmer.crops.show', $this->crop->slug));

        $response->assertStatus(200);
        $response->assertSee('ಬೆಲೆ ಇತಿಹಾಸ & ಪ್ರವೃತ್ತಿ', false);
        $response->assertSee('Historical Price Trend');
        $response->assertSee('ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ತಿಂಗಳುಗಳು');
        $response->assertSee('Best Months to Sell');
        $response->assertSee('priceTrendCanvas');
        $response->assertSee('seasonalityCanvas');
    }

    public function test_crop_show_handles_range_and_market_filters(): void
    {
        $response = $this->get(route('farmer.crops.show', [
            'slug' => $this->crop->slug,
            'range' => '7d',
            'market' => $this->market->name,
        ]));

        $response->assertStatus(200);
        $response->assertSee($this->market->name);
        $response->assertSee('7 ದಿನ (7D)');
    }

    public function test_analytics_trends_api_returns_structured_json(): void
    {
        $response = $this->getJson("/api/v1/analytics/trends?crop={$this->crop->slug}&range=30d");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'crop' => [
                    'id' => $this->crop->id,
                    'slug' => $this->crop->slug,
                ],
                'range_days' => 30,
            ])
            ->assertJsonStructure([
                'success',
                'crop' => ['id', 'name', 'name_kn', 'slug'],
                'market_id',
                'range_days',
                'data' => [
                    'labels',
                    'modal_prices',
                    'min_prices',
                    'max_prices',
                    'arrivals',
                    'has_data',
                    'scope',
                ],
            ]);
    }

    public function test_analytics_seasonality_api_returns_structured_json(): void
    {
        $response = $this->getJson("/api/v1/analytics/seasonality?crop_id={$this->crop->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'crop' => [
                    'id' => $this->crop->id,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'crop',
                'market_id',
                'data' => [
                    'annual_baseline',
                    'monthly_profile',
                    'best_months',
                    'has_seasonal_data',
                ],
            ]);
    }

    public function test_analytics_summary_api_returns_structured_json(): void
    {
        $response = $this->getJson("/api/v1/analytics/summary?crop={$this->crop->slug}&days=15");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'days' => 15,
            ])
            ->assertJsonStructure([
                'success',
                'crop',
                'market_id',
                'days',
                'data' => [
                    'min_price',
                    'max_price',
                    'avg_price',
                    'std_dev',
                    'volatility_percent',
                    'volatility_rating',
                    'observations_count',
                    'price_change_percent',
                    'trend_direction',
                ],
            ]);
    }
}
