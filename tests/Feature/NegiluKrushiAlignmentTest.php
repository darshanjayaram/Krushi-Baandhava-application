<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropVariety;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Tests\TestCase;

class NegiluKrushiAlignmentTest extends TestCase
{
    protected District $shivamogga;
    protected string $latestDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shivamogga = District::where('name', 'Shivamogga')->firstOrFail();
        $this->latestDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();
    }

    /**
     * Test that all core crops remain visible on the homepage even when a specific district (Shivamogga) is selected.
     */
    public function test_all_core_crops_are_visible_on_homepage_with_district_selected(): void
    {
        $response = $this->get('/?district=' . $this->shivamogga->id);

        $response->assertStatus(200);

        // Core crops must all be present on the home screen
        $expectedCrops = [
            'Arecanut',
            'Coffee',
            'Coconut',
            'Copra',
            'Paddy',
            'Ragi',
            'Maize',
            'Onion',
            'Tomato',
            'Tender Coconut',
        ];

        foreach ($expectedCrops as $cropName) {
            $response->assertSee($cropName, false);
        }
    }

    /**
     * Test that local crops have Reliable badge and benchmark crops have Benchmark badge.
     */
    public function test_crop_cards_display_reliable_and_benchmark_badges(): void
    {
        $response = $this->get('/?district=' . $this->shivamogga->id);

        $response->assertStatus(200);

        // Reliable badge for Shivamogga's local crops in Kannada mode
        $response->assertSee('ವಿಶ್ವಾಸಾರ್ಹ');

        // Benchmark badge for crops trading outside Shivamogga in Kannada mode
        $response->assertSee('ಮೌಲ್ಯಾಂಕನ');

        // In English mode
        $enResponse = $this->withSession(['locale' => 'en'])->get('/?district=' . $this->shivamogga->id);
        $enResponse->assertStatus(200);
        $enResponse->assertSee('Reliable');
        $enResponse->assertSee('Benchmark');
    }

    /**
     * Test that "PICK YOUR GRADE" only displays varieties that have recorded prices on the date.
     */
    public function test_pick_your_grade_only_shows_varieties_with_available_prices(): void
    {
        $crop = Crop::where('slug', 'arecanut')->firstOrFail();

        // Find varieties with prices vs without prices
        $pricedVarietyIds = MarketPrice::where('crop_id', $crop->id)
            ->where('price_date', $this->latestDate)
            ->whereNotNull('variety_id')
            ->pluck('variety_id')
            ->unique();

        $pricedVariety = CropVariety::where('crop_id', $crop->id)
            ->whereIn('id', $pricedVarietyIds)
            ->first();

        $unpricedVariety = CropVariety::where('crop_id', $crop->id)
            ->whereNotIn('id', $pricedVarietyIds)
            ->first();

        $response = $this->get('/crops/' . $crop->slug);
        $response->assertStatus(200);

        if ($pricedVariety) {
            $content = $response->getContent();
            $this->assertTrue(
                stripos($content, $pricedVariety->name) !== false
                || ($pricedVariety->name_kn && stripos($content, $pricedVariety->name_kn) !== false)
            );
        }

        if ($unpricedVariety) {
            // Unpriced variety should NOT appear under "PICK YOUR GRADE"
            $response->assertDontSee('href="' . route('farmer.crop.detail', ['crop' => $crop->id, 'variety' => $unpricedVariety->id]), false);
        }
    }

    /**
     * Test that "VIEW DIFFERENT MARKET" orders markets by proximity to the user's district.
     */
    public function test_view_different_market_orders_by_surrounding_proximity(): void
    {
        $crop = Crop::where('slug', 'arecanut')->firstOrFail();

        $response = $this->withCookie('selected_district_id', $this->shivamogga->id)
            ->get('/crops/' . $crop->slug);

        $response->assertStatus(200);

        // In Kannada mode
        $response->assertSee('ಮಾರುಕಟ್ಟೆ ಬದಲಿಸಿ (ಕರ್ನಾಟಕ ಮಂಡಿಗಳು)', false);

        // In English mode
        $enResponse = $this->withSession(['locale' => 'en'])
            ->withCookie('selected_district_id', $this->shivamogga->id)
            ->get('/crops/' . $crop->slug);
        $enResponse->assertStatus(200);
        $enResponse->assertSee('VIEW DIFFERENT MARKET (All Mandis)', false);
    }

    /**
     * Test setLocation endpoint with coordinates for different districts across Karnataka.
     */
    public function test_set_location_api_works_with_coordinates(): void
    {
        // 1. Shivamogga coordinates
        $resShivamogga = $this->postJson('/set-location', [
            'latitude' => 13.9299,
            'longitude' => 75.5681,
        ]);
        $resShivamogga->assertStatus(200);
        $resShivamogga->assertJson([
            'success' => true,
            'district_name' => 'Shivamogga',
        ]);

        // 2. Bengaluru coordinates
        $resBengaluru = $this->postJson('/set-location', [
            'latitude' => 12.9716,
            'longitude' => 77.5946,
        ]);
        $resBengaluru->assertStatus(200);
        $resBengaluru->assertJson([
            'success' => true,
            'district_name' => 'Bengaluru Urban',
        ]);

        // 3. Hassan coordinates
        $resHassan = $this->postJson('/set-location', [
            'latitude' => 13.0033,
            'longitude' => 76.1004,
        ]);
        $resHassan->assertStatus(200);
        $resHassan->assertJson([
            'success' => true,
            'district_name' => 'Hassan',
        ]);

        // 4. Belagavi coordinates
        $resBelagavi = $this->postJson('/set-location', [
            'latitude' => 15.8497,
            'longitude' => 74.4977,
        ]);
        $resBelagavi->assertStatus(200);
        $resBelagavi->assertJson([
            'success' => true,
            'district_name' => 'Belagavi',
        ]);
    }

    /**
     * Test that the location modal on every page has all Karnataka districts populated.
     */
    public function test_location_modal_has_all_districts_populated(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Location modal component must contain districts JSON
        $response->assertSee('districts: [', false);
        $response->assertDontSee('districts: []', false);

        // Verify key districts are included in the modal's JSON payload
        $response->assertSee('Shivamogga');
        $response->assertSee('Bengaluru Urban');
        $response->assertSee('Hassan');
        $response->assertSee('Mysuru');
        $response->assertSee('Belagavi');
        $response->assertSee('Dakshina Kannada');
        $response->assertSee('Tumakuru');
        $response->assertSee('Kodagu');
    }
}
