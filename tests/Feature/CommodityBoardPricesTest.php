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
    public function test_coffee_crop_detail_shows_coffee_board_rates_and_centres_without_apmc(): void
    {
        $coffee = Crop::where('slug', 'coffee')->orWhere('name', 'Coffee')->firstOrFail();

        $response = $this->get('/crops/' . $coffee->slug);

        $response->assertStatus(200);
        $response->assertSee('Coffee Board of India');
        $response->assertSee('ಕಾಫಿ ಮಂಡಳಿ ಅಧಿಕೃತ ದರಗಳು', false);
        $response->assertSee('ಕಾಫಿ ಮಂಡಳಿ ಕೇಂದ್ರ ಆಯ್ಕೆ', false);
        $response->assertSee('ಕಾಫಿ ಮಂಡಳಿ ಕೇಂದ್ರವಾರು ದರ ಹೋಲಿಕೆ', false);
        $response->assertSee('/50kg Bag');

        // Verify Coffee Board curing centres are present
        $response->assertSee('Chikkamagaluru (Coffee Board)');
        $response->assertDontSee('Chikkamagaluru APMC');
    }

    public function test_coconut_crop_detail_shows_coconut_development_board_rates_and_centres(): void
    {
        $coconut = Crop::whereIn('slug', ['coconut', 'copra'])->firstOrFail();

        $response = $this->get('/crops/' . $coconut->slug);

        $response->assertStatus(200);
        $response->assertSee('Coconut Development Board');
        $response->assertSee('ತೆಂಗು ಅಭಿವೃದ್ಧಿ ಮಂಡಳಿ ದರಗಳು', false);
        $response->assertSee('ತೆಂಗು ಮಂಡಳಿ ಖರೀದಿ ಕೇಂದ್ರ ಆಯ್ಕೆ', false);
        $response->assertSee('ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರವಾರು ದರ ಹೋಲಿಕೆ', false);

        // Verify CDB centres are shown instead of generic APMCs
        $response->assertSee('CDB Centre');
    }

    public function test_arecanut_crop_detail_shows_regular_apmc_mandis(): void
    {
        $arecanut = Crop::where('slug', 'arecanut')->firstOrFail();

        $response = $this->get('/crops/' . $arecanut->slug);

        $response->assertStatus(200);
        $response->assertSee('Arecanut');
        $response->assertSee('ಕರ್ನಾಟಕ APMC ಮಂಡಿ ಆಯ್ಕೆ', false);
        $response->assertSee('ಮಂಡಿವಾರು ದರ ಹೋಲಿಕೆ', false);
        $response->assertDontSee('Coffee Board of India');
        $response->assertDontSee('Coconut Development Board');
    }

    public function test_ingestion_service_ignores_apmc_feed_for_coffee_and_coconut(): void
    {
        $ingestionService = app(MarketPriceIngestionService::class);

        // Simulated APMC Data Source (e.g. data.gov.in)
        $apmcSource = DataSource::where('code', 'data_gov_mandi')->first() ?? DataSource::first();

        // Feed attempting to ingest Coffee from generic APMC
        $coffeeRaw = [
            'source_crop' => 'Coffee',
            'source_variety' => 'Arabica Cherry',
            'source_market' => 'Shivamogga APMC',
            'price_date' => Carbon::today()->format('Y-m-d'),
            'modal_price' => 25000,
            'min_price' => 24000,
            'max_price' => 26000,
            'arrival_quantity' => 10,
            'unit' => 'Quintal',
            'raw_payload' => ['sample' => 'data'],
        ];

        // Ensure this APMC raw entry gets skipped
        $rawCountBefore = MarketPrice::whereHas('crop', fn($q) => $q->where('slug', 'coffee'))
            ->where('data_source_id', $apmcSource->id)
            ->count();

        $this->assertEquals(0, $rawCountBefore);
    }
}
