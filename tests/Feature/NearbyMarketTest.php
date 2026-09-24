<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Market;
use App\Models\State;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NearbyMarketTest extends TestCase
{
    use DatabaseTransactions;

    public function test_nearby_markets_web_page_renders_successfully(): void
    {
        $response = $this->get('/nearby-markets');

        $response->assertStatus(200);
        $response->assertSee('ನಿಮ್ಮ ಸಮೀಪದ ಕರ್ನಾಟಕ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳು', false);
        $response->assertSee('ಪ್ರಸ್ತುತ ಸ್ಥಳದಿಂದ ಹುಡುಕಿ', false);
        $response->assertSee('ಹುಡುಕಾಟ ವ್ಯಾಪ್ತಿ', false);
    }

    public function test_nearby_markets_handles_gps_coordinates_and_finds_nearest_mandis(): void
    {
        // Shivamogga coordinates
        $response = $this->get('/nearby-markets?lat=13.9299&lon=75.5681&radius=50');

        $response->assertStatus(200);
        $response->assertSee('Shivamogga APMC');
        $response->assertSee('ಕಿ.ಮೀ', false);
        $response->assertSee('Google Maps ನಲ್ಲಿ ದಾರಿ ನೋಡಿ', false);
    }

    public function test_nearby_markets_handles_manual_district_fallback(): void
    {
        $district = District::where('is_active', true)->where('name', 'Bengaluru Urban')->first()
            ?? District::where('is_active', true)->firstOrFail();

        $response = $this->get('/nearby-markets?district=' . $district->id . '&radius=50');

        $response->assertStatus(200);
        $response->assertSee($district->name);
    }

    public function test_nearby_markets_api_returns_structured_json(): void
    {
        $response = $this->getJson('/api/v1/markets/nearby?latitude=13.9299&longitude=75.5681&radius=50');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'user_location' => ['latitude', 'longitude', 'district', 'taluk', 'locality', 'display_name'],
            'radius_km',
            'crop_filter',
            'count',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'name_kn',
                    'code',
                    'market_type',
                    'latitude',
                    'longitude',
                    'district',
                    'distance_km',
                    'bearing_degrees',
                    'direction' => ['en', 'kn'],
                    'commodities_count',
                    'top_prices',
                    'google_maps_url',
                ],
            ],
        ]);
    }

    public function test_nearby_markets_strictly_excludes_out_of_state_mandis(): void
    {
        // Create an out-of-state state and mandi very close to coordinates
        $mhState = State::firstOrCreate(
            ['code' => 'MH'],
            ['name' => 'Maharashtra', 'name_kn' => 'ಮಹಾರಾಷ್ಟ್ರ', 'is_active' => true]
        );
        $puneDistrict = District::firstOrCreate(
            ['code' => 'MH_PUN'],
            ['state_id' => $mhState->id, 'name' => 'Pune', 'name_kn' => 'ಪುಣೆ', 'is_active' => true]
        );
        $puneMarket = Market::firstOrCreate(
            ['code' => 'MH_APMC_PUN2'],
            [
                'district_id' => $puneDistrict->id,
                'name' => 'Pune OutOfState APMC',
                'name_kn' => 'ಪುಣೆ ಹೊರರಾಜ್ಯ ಮಾರುಕಟ್ಟೆ',
                'type' => 'principal',
                'latitude' => 13.9300, // artificial coordinates right next to test point
                'longitude' => 75.5600,
                'is_active' => true,
            ]
        );

        // Web check
        $webResponse = $this->get('/nearby-markets?lat=13.9299&lon=75.5681&radius=50');
        $webResponse->assertStatus(200);
        $webResponse->assertDontSee('Pune OutOfState APMC');

        // API check
        $apiResponse = $this->getJson('/api/v1/markets/nearby?latitude=13.9299&longitude=75.5681&radius=50');
        $apiResponse->assertStatus(200);
        $marketNames = collect($apiResponse->json('data'))->pluck('name');
        $this->assertFalse($marketNames->contains('Pune OutOfState APMC'));
    }
}
