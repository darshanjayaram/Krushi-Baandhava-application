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
        $response->assertSee('ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು', false);
        $response->assertSee('priceTrendCanvas');
        $response->assertSee('seasonalityCanvas');

        // Verify English mode renders English titles
        $enResponse = $this->withSession(['locale' => 'en'])->get(route('farmer.crops.show', $this->crop->slug));
        $enResponse->assertStatus(200);
        $enResponse->assertSee('Historical Price Trend');
        $enResponse->assertSee('Best Months to Sell');
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
        $response->assertSee('7 ದಿನ');
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

    public function test_crop_show_page_renders_data_insufficiency_notice_when_seasonal_history_is_sparse(): void
    {
        $sparseCrop = Crop::create([
            'name' => 'Sparse Show Test Crop',
            'name_kn' => 'ಸ್ಪಾರ್ಸ್ ಬೆಳೆ',
            'slug' => 'sparse-show-test-' . uniqid(),
            'category_id' => $this->crop->category_id,
            'is_active' => true,
        ]);

        MarketPrice::create([
            'crop_id' => $sparseCrop->id,
            'market_id' => $this->market->id,
            'price_date' => Carbon::today()->toDateString(),
            'min_price' => 2000,
            'max_price' => 2200,
            'modal_price' => 2100,
            'unit' => 'Quintal',
        ]);

        $response = $this->get(route('farmer.crops.show', $sparseCrop->slug));

        $response->assertStatus(200);
        $response->assertSee('ಋತುಮಾನ ಮಾಹಿತಿ ಕೊರತೆ ಸೂಚನೆ');
        $response->assertSee('ವಿಶ್ವಾಸಾರ್ಹ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆಗೆ ಕನಿಷ್ಠ 2 ಪ್ರತ್ಯೇಕ ತಿಂಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ');
        $response->assertDontSee('Top Selling Windows');

        // Test English mode
        $enResponse = $this->withSession(['locale' => 'en'])->get(route('farmer.crops.show', $sparseCrop->slug));
        $enResponse->assertStatus(200);
        $enResponse->assertSee('Seasonal Data Insufficiency Notice');

        // Clean up
        MarketPrice::where('crop_id', $sparseCrop->id)->delete();
        $sparseCrop->delete();
    }

    public function test_best_months_to_sell_recalculates_and_refreshes_based_on_selected_market(): void
    {
        $ragi = Crop::where('slug', 'ragi')->first();
        if (! $ragi) {
            $this->markTestSkipped('Ragi crop not seeded.');
        }

        $mandyaMarket = Market::where('name', 'like', '%Mandya%')->firstOrFail();
        $yeshwanthpurMarket = Market::where('name', 'like', '%Yeshwanthpur%')->firstOrFail();

        // 1. Test Mandya Market
        $mandyaResponse = $this->withSession(['locale' => 'en'])->get(route('farmer.crops.show', [
            'slug' => $ragi->slug,
            'market' => $mandyaMarket->name,
        ]));

        $mandyaResponse->assertStatus(200);
        $mandyaResponse->assertSee('Best Months to Sell');
        $mandyaResponse->assertSee($mandyaMarket->name);

        // 2. Test Yeshwanthpur Market
        $yprResponse = $this->withSession(['locale' => 'en'])->get(route('farmer.crops.show', [
            'slug' => $ragi->slug,
            'market' => $yeshwanthpurMarket->name,
        ]));

        $yprResponse->assertStatus(200);
        $yprResponse->assertSee('Best Months to Sell');
        $yprResponse->assertSee($yeshwanthpurMarket->name);

        // 3. Test API returns market-specific baselines
        $mandyaApi = $this->getJson("/api/v1/analytics/seasonality?crop_id={$ragi->id}&market_id={$mandyaMarket->id}");
        $mandyaApi->assertStatus(200);
        $mandyaBaseline = $mandyaApi->json('data.annual_baseline');
        $this->assertEquals($mandyaMarket->id, $mandyaApi->json('data.market_id'));

        $yprApi = $this->getJson("/api/v1/analytics/seasonality?crop_id={$ragi->id}&market_id={$yeshwanthpurMarket->id}");
        $yprApi->assertStatus(200);
        $yprBaseline = $yprApi->json('data.annual_baseline');
        $this->assertEquals($yeshwanthpurMarket->id, $yprApi->json('data.market_id'));

        // Assert that the two markets produce distinct baselines reflecting their individual price levels
        $this->assertNotEquals($mandyaBaseline, $yprBaseline);
    }
}
