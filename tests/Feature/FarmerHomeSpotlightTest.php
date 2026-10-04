<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FarmerHomeSpotlightTest extends TestCase
{
    use DatabaseTransactions;

    public function test_spotlight_cards_remain_filled_across_freshness_window_during_partial_provider_sync(): void
    {
        // 1. Ensure State and District exist
        $state = State::firstOrCreate(['code' => 'KA'], ['name' => 'Karnataka']);
        $district = District::firstOrCreate(
            ['name' => 'Shivamogga', 'state_id' => $state->id],
            ['code' => 'SHI', 'is_active' => true]
        );
        $market = Market::firstOrCreate(
            ['name' => 'Shivamogga', 'district_id' => $district->id],
            ['code' => 'SHI_MKT', 'state_id' => $state->id, 'market_type' => 'APMC', 'is_active' => true]
        );

        $category = CropCategory::firstOrCreate(
            ['slug' => 'commercial-plantation'],
            ['name' => 'Commercial', 'name_kn' => 'ವಾಣಿಜ್ಯ', 'is_active' => true]
        );

        $sourceA = DataSource::first() ?? DataSource::create([
            'code' => 'TEST_SOURCE_A',
            'name' => 'Test Source A',
            'type' => 'scraper',
            'provider_class' => 'App\\Services\\Ingestion\\Providers\\CoffeeBoardScraper',
            'base_url' => 'https://example.com',
            'is_active' => true,
        ]);
        $sourceB = $sourceA;

        // 2. Create 4 major crops
        $cropNames = ['Arecanut', 'Coffee', 'Black Pepper', 'Coconut'];
        $crops = [];
        foreach ($cropNames as $name) {
            $crops[] = Crop::firstOrCreate(
                ['name' => $name],
                [
                    'name_kn' => $name,
                    'category_id' => $category->id,
                    'is_major' => true,
                    'is_active' => true,
                    'primary_unit' => 'Quintal',
                ]
            );
        }

        // 3. Simulate scenario: Coffee synced on '2026-10-03', but KRAMA (other 3 crops) only has '2026-10-01'
        $today = Carbon::parse('2026-10-03');
        $twoDaysAgo = Carbon::parse('2026-10-01');

        MarketPrice::create([
            'crop_id' => $crops[1]->id, // Coffee
            'market_id' => $market->id,
            'data_source_id' => $sourceA->id,
            'price_date' => $today->toDateString(),
            'min_price' => 45000,
            'max_price' => 50000,
            'modal_price' => 48000,
        ]);

        foreach ([$crops[0], $crops[2], $crops[3]] as $idx => $otherCrop) {
            MarketPrice::create([
                'crop_id' => $otherCrop->id,
                'market_id' => $market->id,
                'data_source_id' => $sourceB->id,
                'price_date' => $twoDaysAgo->toDateString(),
                'min_price' => 20000 + ($idx * 5000),
                'max_price' => 25000 + ($idx * 5000),
                'modal_price' => 22000 + ($idx * 5000),
            ]);
        }

        // 4. Request Homepage
        $response = $this->get(route('home', ['district' => $district->id]));

        $response->assertStatus(200);

        // 5. Assert all 4 distinct crops are loaded in view data
        $response->assertViewHas('topMovers', function ($topMovers) {
            // Must have 4 distinct items
            $this->assertGreaterThanOrEqual(4, $topMovers->count());
            $uniqueCrops = $topMovers->pluck('crop_id')->unique();
            $this->assertGreaterThanOrEqual(4, $uniqueCrops->count());
            return true;
        });

        // 6. Assert date pills render on cards where auction date != latestPriceDate
        $response->assertSee('📅');
    }
}
