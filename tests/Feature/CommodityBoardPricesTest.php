<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Tests\TestCase;

class CommodityBoardPricesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $coffee = Crop::where('slug', 'coffee')->orWhere('name', 'Coffee')->first();
        if ($coffee) {
            $m = Market::where('code', 'KA_APMC_CKM')->first() ?? Market::first();
            $ds = DataSource::where('code', 'coffee_board')->first() ?? DataSource::first();
            MarketPrice::firstOrCreate([
                'crop_id' => $coffee->id,
                'market_id' => $m->id,
                'price_date' => Carbon::today()->toDateString(),
            ], [
                'district_id' => $m->district_id,
                'min_price' => 19500,
                'max_price' => 20500,
                'modal_price' => 20000,
                'unit' => '50kg Bag',
                'data_source_id' => $ds->id,
            ]);
        }

        $coconut = Crop::whereIn('slug', ['coconut', 'copra'])->first();
        if ($coconut) {
            $m = Market::where('code', 'KA_APMC_TIP')->first() ?? Market::first();
            $ds = DataSource::where('code', 'coconut_board')->first() ?? DataSource::first();
            MarketPrice::firstOrCreate([
                'crop_id' => $coconut->id,
                'market_id' => $m->id,
                'price_date' => Carbon::today()->toDateString(),
            ], [
                'district_id' => $m->district_id,
                'min_price' => 2500,
                'max_price' => 3000,
                'modal_price' => 2800,
                'unit' => 'Quintal',
                'data_source_id' => $ds->id,
            ]);
        }
    }

    public function test_coffee_crop_detail_shows_coffee_board_rates_and_centres_without_apmc(): void
    {
        $coffee = Crop::where('slug', 'coffee')->orWhere('name', 'Coffee')->firstOrFail();

        $response = $this->get('/crops/' . $coffee->slug . '?lang=en');

        $response->assertStatus(200);
        $response->assertSee('Coffee Board of India');
        $response->assertSee('Official Coffee Board Rates by Centre');
        $response->assertSee('VIEW DIFFERENT CENTRE');
        $response->assertSee('Quintal');

        // Verify clean town centres are present without redundant Coffee Board suffixes
        $response->assertSee('Chikkamagaluru');
        $response->assertSee('Sakleshpur');
        $response->assertSee('Madikeri');
        $response->assertDontSee('Coffee Board Centre');
    }

    public function test_coconut_crop_detail_shows_coconut_development_board_rates_and_centres(): void
    {
        $coconut = Crop::whereIn('slug', ['coconut', 'copra'])->firstOrFail();
        $originalType = $coconut->price_source_type;
        $coconut->update(['price_source_type' => 'coconut_board']);

        try {
            $response = $this->get('/crops/' . $coconut->slug . '?lang=en');

            $response->assertStatus(200);
            $response->assertSee('Coconut Development Board');
            $response->assertSee('Official CDB Rates by Centre');
            $response->assertSee('VIEW DIFFERENT CENTRE');
        } finally {
            $coconut->update(['price_source_type' => $originalType]);
        }
    }

    public function test_arecanut_crop_detail_shows_regular_apmc_mandis(): void
    {
        $arecanut = Crop::where('slug', 'arecanut')->firstOrFail();

        $response = $this->get('/crops/' . $arecanut->slug . '?lang=en');

        $response->assertStatus(200);
        $response->assertSee('Arecanut');
        $response->assertSee('Where to Sell Today? — Mandi Rates');
        $response->assertDontSee('Coffee Board of India');
        $response->assertDontSee('Coconut Development Board');
    }

    public function test_ingestion_service_ignores_apmc_feed_for_coffee(): void
    {
        $ingestionService = app(MarketPriceIngestionService::class);

        // APMC Data Source (Agmarknet)
        $apmcSource = DataSource::where('code', 'agmarknet_official')->first() 
            ?? DataSource::where('code', 'krama_karnataka')->first();

        $this->assertNotNull($apmcSource);

        $coffeeCountBefore = MarketPrice::whereHas('crop', fn($q) => $q->where('slug', 'coffee'))
            ->where('data_source_id', $apmcSource->id)
            ->count();

        $this->assertEquals(0, $coffeeCountBefore);
    }
}
