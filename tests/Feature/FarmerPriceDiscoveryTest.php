<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FarmerPriceDiscoveryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure canonical market prices are present
        if (MarketPrice::count() === 0) {
            Artisan::call('krushi:sync-market-prices', ['source' => 'data_gov_mandi', '--force' => true]);
        }
    }

    public function test_farmer_home_screen_renders_with_real_database_prices(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Krushi Baandhava', false);
        $response->assertSee('ದೈನಂದಿನ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ದರಗಳು', false);
        $response->assertSee('ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು', false);
        $response->assertSee('ಶೇರ್ ಮಾಡಿ', false);
    }

    public function test_farmer_home_screen_handles_district_filtering(): void
    {
        $district = District::where('is_active', true)->firstOrFail();

        $response = $this->get('/?district=' . $district->id);

        $response->assertStatus(200);
        $response->assertSee($district->name);
    }

    public function test_farmer_home_screen_handles_category_filtering(): void
    {
        $category = CropCategory::where('is_active', true)->firstOrFail();

        $response = $this->get('/?category=' . $category->slug);

        $response->assertStatus(200);
        $response->assertSee($category->name);
    }

    public function test_farmer_home_screen_handles_search_query(): void
    {
        $crop = Crop::where('slug', 'arecanut')->firstOrFail();

        $response = $this->get('/?search=' . urlencode($crop->name));

        $response->assertStatus(200);
        $response->assertSee($crop->name);
    }

    public function test_farmer_crops_catalog_renders_and_lists_crops(): void
    {
        $response = $this->get('/crops');

        $response->assertStatus(200);
        $response->assertSee('ಕರ್ನಾಟಕದ ಬೆಳೆಗಳು', false);
        $response->assertSee('Crops Directory', false);
        $response->assertSee('Arecanut');
    }

    public function test_farmer_crop_detail_renders_mandi_comparison(): void
    {
        $crop = Crop::where('slug', 'arecanut')->firstOrFail();

        $response = $this->get('/crops/' . $crop->slug);

        $response->assertStatus(200);
        $response->assertSee($crop->name);
        $response->assertSee('ರಾಜ್ಯದ ಗರಿಷ್ಠ ದರ', false);
        $response->assertSee('ಮಂಡಿವಾರು ದರ ಹೋಲಿಕೆ', false);
    }

    public function test_farmer_markets_directory_renders_and_filters_by_district(): void
    {
        $district = District::where('is_active', true)->firstOrFail();

        $response = $this->get('/markets?district=' . $district->id);

        $response->assertStatus(200);
        $response->assertSee('ಕರ್ನಾಟಕ APMC ಮಂಡಿಗಳು', false);
        $response->assertSee($district->name);
    }

    public function test_farmer_market_detail_renders_profile_and_traded_crops(): void
    {
        $market = Market::where('is_active', true)->firstOrFail();

        $response = $this->get('/markets/' . $market->code);

        $response->assertStatus(200);
        $response->assertSee($market->name . ' APMC');
        $response->assertSee('ವಹಿವಾಟಾದ ಬೆಳೆಗಳು', false);
    }

    public function test_public_price_api_returns_structured_json(): void
    {
        $response = $this->getJson('/api/v1/prices/today');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'price_date',
            'count',
            'data' => [
                '*' => [
                    'id',
                    'price_date',
                    'crop' => ['id', 'name', 'name_kn', 'slug'],
                    'variety',
                    'market' => ['id', 'name', 'name_kn', 'code', 'district'],
                    'modal_price',
                    'min_price',
                    'max_price',
                    'price_spread',
                    'arrival_quantity',
                    'unit',
                    'source',
                ],
            ],
        ]);
    }

    public function test_strictly_filters_out_non_karnataka_mandis_across_application(): void
    {
        // 1. Create a non-Karnataka state, district, and mandi
        $mhState = \App\Models\State::firstOrCreate(
            ['code' => 'MH'],
            ['name' => 'Maharashtra', 'name_kn' => 'ಮಹಾರಾಷ್ಟ್ರ', 'is_active' => true]
        );
        $puneDistrict = District::firstOrCreate(
            ['code' => 'MH_PUN'],
            ['state_id' => $mhState->id, 'name' => 'Pune', 'name_kn' => 'ಪುಣೆ', 'is_active' => true]
        );
        $puneMarket = Market::firstOrCreate(
            ['code' => 'MH_APMC_PUN'],
            [
                'district_id' => $puneDistrict->id,
                'name' => 'Pune Azadpur APMC',
                'name_kn' => 'ಪುಣೆ ಮಾರುಕಟ್ಟೆ',
                'type' => 'principal',
                'is_active' => true,
            ]
        );

        $crop = Crop::firstOrFail();
        $date = MarketPrice::max('price_date') ?? now()->toDateString();

        $dataSource = \App\Models\DataSource::firstOrFail();

        // Attach an active price to Pune APMC
        MarketPrice::firstOrCreate(
            [
                'market_id' => $puneMarket->id,
                'crop_id' => $crop->id,
                'price_date' => $date,
            ],
            [
                'district_id' => $puneDistrict->id,
                'data_source_id' => $dataSource->id,
                'min_price' => 5000,
                'max_price' => 7000,
                'modal_price' => 6000,
                'unit' => 'Quintal',
                'is_verified' => true,
            ]
        );

        // 2. Assert home screen does not display Pune Azadpur APMC
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('Pune Azadpur APMC');

        // 3. Assert markets index does not list Pune Azadpur APMC
        $marketsResponse = $this->get('/markets');
        $marketsResponse->assertStatus(200);
        $marketsResponse->assertDontSee('Pune Azadpur APMC');

        // 4. Assert crop detail does not show Pune Azadpur APMC
        $cropResponse = $this->get('/crops/' . $crop->slug);
        $cropResponse->assertStatus(200);
        $cropResponse->assertDontSee('Pune Azadpur APMC');

        // 5. Assert public API does not return Pune Azadpur APMC
        $apiResponse = $this->getJson('/api/v1/prices/today');
        $apiResponse->assertStatus(200);
        $marketNames = collect($apiResponse->json('data'))->pluck('market.name');
        $this->assertFalse($marketNames->contains('Pune Azadpur APMC'));
    }

    public function test_crop_detail_supports_karnataka_mandi_filtering_via_query_param(): void
    {
        $binnyMarket = Market::where('code', 'KA_APMC_BNM')->firstOrFail();
        $crop = Crop::where('slug', 'tomato')->first() ?? Crop::firstOrFail();
        $date = MarketPrice::karnataka()->max('price_date') ?? now()->toDateString();
        $dataSource = \App\Models\DataSource::firstOrFail();

        MarketPrice::firstOrCreate(
            [
                'market_id' => $binnyMarket->id,
                'crop_id' => $crop->id,
                'price_date' => $date,
            ],
            [
                'district_id' => $binnyMarket->district_id,
                'data_source_id' => $dataSource->id,
                'min_price' => 2000,
                'max_price' => 3000,
                'modal_price' => 2500,
                'unit' => 'Quintal',
                'arrival_quantity' => 100,
                'arrival_unit' => 'Quintal',
                'is_verified' => true,
            ]
        );

        // Query crop page with ?market=BINNY%20MILL%20%28F%26V%29
        $response = $this->get('/crops/' . $crop->slug . '?market=' . urlencode('Binny Mill (F&V)'));

        $response->assertStatus(200);
        $response->assertSee('Binny Mill (F&V)');
        $response->assertSee('ಕರ್ನಾಟಕ ಮಂಡಿ ಆಯ್ಕೆ', false);
    }

    public function test_ranked_by_best_price_groups_multiple_varieties_into_single_apmc_card(): void
    {
        $crop = Crop::where('slug', 'pepper')->orWhere('slug', 'arecanut')->firstOrFail();
        $market = Market::karnataka()->firstOrFail();
        $date = MarketPrice::where('crop_id', $crop->id)->max('price_date') ?? now()->toDateString();
        $dataSource = \App\Models\DataSource::firstOrFail();

        $v1 = \App\Models\CropVariety::firstOrCreate(
            ['crop_id' => $crop->id, 'name' => 'Grade Alpha Premium'],
            ['slug' => 'grade-alpha-premium', 'name_kn' => 'ಗ್ರೇಡ್ ಆಲ್ಫಾ ಪ್ರೀಮಿಯಂ', 'is_active' => true]
        );
        $v2 = \App\Models\CropVariety::firstOrCreate(
            ['crop_id' => $crop->id, 'name' => 'Grade Beta Regular'],
            ['slug' => 'grade-beta-regular', 'name_kn' => 'ಗ್ರೇಡ್ ಬೀಟಾ ರೆಗ್ಯುಲರ್', 'is_active' => true]
        );

        MarketPrice::updateOrCreate(
            [
                'market_id' => $market->id,
                'crop_id' => $crop->id,
                'variety_id' => $v1->id,
                'price_date' => $date,
            ],
            [
                'district_id' => $market->district_id,
                'data_source_id' => $dataSource->id,
                'min_price' => 60000,
                'max_price' => 65000,
                'modal_price' => 63000,
                'unit' => 'Quintal',
                'arrival_quantity' => 100,
                'arrival_unit' => 'Quintal',
                'is_verified' => true,
            ]
        );

        MarketPrice::updateOrCreate(
            [
                'market_id' => $market->id,
                'crop_id' => $crop->id,
                'variety_id' => $v2->id,
                'price_date' => $date,
            ],
            [
                'district_id' => $market->district_id,
                'data_source_id' => $dataSource->id,
                'min_price' => 55000,
                'max_price' => 59000,
                'modal_price' => 57000,
                'unit' => 'Quintal',
                'arrival_quantity' => 50,
                'arrival_unit' => 'Quintal',
                'is_verified' => true,
            ]
        );

        $response = $this->get('/crops/' . $crop->slug);

        $response->assertStatus(200);
        $mandiGroups = $response->viewData('mandiGroups');
        $this->assertNotNull($mandiGroups);

        // Verify that this market only appears ONCE in mandiGroups
        $marketOccurrences = $mandiGroups->filter(fn($g) => $g->market->id === $market->id);
        $this->assertEquals(1, $marketOccurrences->count(), 'The market must only appear once in mandiGroups');

        $marketGroup = $marketOccurrences->first();
        $this->assertGreaterThanOrEqual(2, $marketGroup->variety_count);
        $this->assertEquals(63000, $marketGroup->best_modal);

        // Verify the HTML renders both varieties inside the page (Kannada by default, English when toggled)
        $response->assertSee('ಗ್ರೇಡ್ ಆಲ್ಫಾ ಪ್ರೀಮಿಯಂ', false);
        $response->assertSee('ಗ್ರೇಡ್ ಬೀಟಾ ರೆಗ್ಯುಲರ್', false);

        $enResponse = $this->withSession(['locale' => 'en'])->get('/crops/' . $crop->slug);
        $enResponse->assertSee('Grade Alpha Premium');
        $enResponse->assertSee('Grade Beta Regular');
    }

    public function test_ranked_by_best_price_shows_two_nearest_apmcs_to_user_location(): void
    {
        $crop = Crop::where('slug', 'arecanut')->orWhere('slug', 'pepper')->firstOrFail();
        $userDistrict = District::where('name', 'Shivamogga')->first() ?? District::firstOrFail();

        $response = $this->withSession(['selected_district_id' => $userDistrict->id])
            ->get('/crops/' . $crop->slug);

        $response->assertStatus(200);

        $nearestTwoGroups = $response->viewData('nearestTwoGroups');
        $this->assertNotNull($nearestTwoGroups);
        $this->assertLessThanOrEqual(2, $nearestTwoGroups->count());

        if ($nearestTwoGroups->count() === 2) {
            // Assert that the 2 cards are ranked by modal price between them (#1 >= #2)
            $this->assertGreaterThanOrEqual(
                $nearestTwoGroups[1]->best_modal,
                $nearestTwoGroups[0]->best_modal
            );

            // Assert that the view renders the nearest APMC
            $response->assertSee($nearestTwoGroups[0]->market->name);
            $response->assertSee($nearestTwoGroups[1]->market->name);
        }
    }
}
