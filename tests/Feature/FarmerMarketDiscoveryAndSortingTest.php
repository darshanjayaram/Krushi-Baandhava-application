<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FarmerMarketDiscoveryAndSortingTest extends TestCase
{
    use DatabaseTransactions;

    protected Crop $crop;
    protected Market $nearMarket;
    protected Market $farMarket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crop = Crop::where('slug', 'arecanut')->first() ?? Crop::first();

        $tumakuruDist = District::where('name', 'Tumakuru')->first() ?? District::first();

        $mangaluruDist = District::where('name', 'Dakshina Kannada')->first() 
            ?? District::where('id', '!=', $tumakuruDist->id)->first() 
            ?? $tumakuruDist;

        $this->nearMarket = Market::firstOrCreate(
            ['code' => 'TEST_TUM'],
            [
                'name' => 'Tumakuru Test APMC',
                'name_kn' => 'ತುಮಕೂರು ಪರೀಕ್ಷಾ APMC',
                'district_id' => $tumakuruDist->id,
                'latitude' => 13.3400,
                'longitude' => 77.1000,
                'is_active' => true,
            ]
        );

        $this->farMarket = Market::firstOrCreate(
            ['code' => 'TEST_MNG'],
            [
                'name' => 'Mangaluru Test APMC',
                'name_kn' => 'ಮಂಗಳೂರು ಪರೀಕ್ಷಾ APMC',
                'district_id' => $mangaluruDist->id,
                'latitude' => 12.8700,
                'longitude' => 74.8800,
                'is_active' => true,
            ]
        );

        // Seed price records for both markets for this crop
        MarketPrice::updateOrCreate(
            [
                'crop_id' => $this->crop->id,
                'market_id' => $this->nearMarket->id,
                'price_date' => Carbon::today()->toDateString(),
            ],
            [
                'modal_price' => 45000,
                'min_price' => 42000,
                'max_price' => 47000,
                'arrival_quantity' => 150,
            ]
        );

        MarketPrice::updateOrCreate(
            [
                'crop_id' => $this->crop->id,
                'market_id' => $this->farMarket->id,
                'price_date' => Carbon::today()->toDateString(),
            ],
            [
                'modal_price' => 52000, // Higher price
                'min_price' => 49000,
                'max_price' => 54000,
                'arrival_quantity' => 200,
            ]
        );
    }

    public function test_dual_sorting_toggle_renders_when_allowed_by_admin(): void
    {
        $this->crop->update([
            'allow_user_sort_toggle' => true,
            'default_market_sort' => 'nearest_first',
        ]);

        $response = $this->withSession(['locale' => 'en', 'user_lat' => 12.9716, 'user_lng' => 77.5946])
            ->withCookie('locale', 'en')
            ->get("/crops/{$this->crop->slug}");

        $response->assertStatus(200);
        // Should contain Alpine activeSort and sorting toggle buttons in English
        $response->assertSee("activeSort: 'nearest_first'", false);
        $response->assertSee('Nearest', false);
        $response->assertSee('Top Rate', false);
    }

    public function test_dual_sorting_toggle_hidden_when_disabled_by_admin(): void
    {
        $this->crop->update([
            'allow_user_sort_toggle' => false,
            'default_market_sort' => 'highest_price_first',
        ]);

        $response = $this->withSession(['locale' => 'en', 'user_lat' => 12.9716, 'user_lng' => 77.5946])
            ->withCookie('locale', 'en')
            ->get("/crops/{$this->crop->slug}");

        $response->assertStatus(200);
        $response->assertSee("activeSort: 'highest_price_first'", false);
        // The toggle button group should not be rendered
        $response->assertDontSee("activeSort = 'nearest_first'", false);
    }

    public function test_smart_badges_displayed_on_market_pills_when_enabled(): void
    {
        $this->crop->update([
            'enable_smart_badges' => true,
        ]);

        $response = $this->withSession(['locale' => 'en', 'user_lat' => 12.9716, 'user_lng' => 77.5946])
            ->withCookie('locale', 'en')
            ->get("/crops/{$this->crop->slug}?market=" . urlencode($this->nearMarket->name));

        $response->assertStatus(200);
        // When nearMarket is selected, farMarket (which has higher price) should have Top Rate badge
        $response->assertSee('Top Rate', false);
    }

    public function test_top_distance_badge_does_not_mislabel_distant_market_as_nearest(): void
    {
        // When distant market (Mangaluru) is explicitly selected
        $response = $this->withSession(['locale' => 'en', 'user_lat' => 12.9716, 'user_lng' => 77.5946]) // Bengaluru
            ->withCookie('locale', 'en')
            ->get("/crops/{$this->crop->slug}?market=" . urlencode($this->farMarket->name));

        $response->assertStatus(200);
        // It must NOT say "nearest market • 3" for Mangaluru!
        // Instead, it should say "away (Nearest: "
        $response->assertSee('away', false);
        $response->assertSee('Nearest:', false);
        $response->assertDontSee('nearest market • 3', false);
    }

    public function test_market_radius_filter_limits_pills_with_expand_toggle(): void
    {
        // Set a small radius (e.g. 100km from Bengaluru) so Tumakuru (~70km) is within and Mangaluru (~300km) is outside
        $this->crop->update([
            'market_radius_km' => 100,
        ]);

        $response = $this->withSession(['locale' => 'en', 'user_lat' => 12.9716, 'user_lng' => 77.5946])
            ->withCookie('locale', 'en')
            ->get("/crops/{$this->crop->slug}");

        $response->assertStatus(200);
        // Radius label is shown in header
        $response->assertSee('(100 km)', false);
        // Expand button for mandis beyond 100km is present
        $response->assertSee('more mandis beyond', false);
        $response->assertSee('100 km', false);
    }

    public function test_kannada_localization_renders_proper_kannada_labels(): void
    {
        $this->crop->update([
            'allow_user_sort_toggle' => true,
            'enable_smart_badges' => true,
            'market_radius_km' => 100,
        ]);

        $response = $this->withSession(['locale' => 'kn', 'user_lat' => 12.9716, 'user_lng' => 77.5946])
            ->withCookie('locale', 'kn')
            ->get("/crops/{$this->crop->slug}");

        $response->assertStatus(200);
        $response->assertSee('ಹತ್ತಿರ', false);
        $response->assertSee('ಹೆಚ್ಚಿನ ಬೆಲೆ', false);
        $response->assertSee('ಹೆಚ್ಚಿನ ಮಂಡಿಗಳು', false);
    }
}
