<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\State;
use Carbon\Carbon;
use Tests\TestCase;

class FarmerPriceForecastTest extends TestCase
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
    }

    public function test_crop_show_page_renders_forecast_card(): void
    {
        // Default / Kannada mode
        $knResponse = $this->get(route('farmer.crops.show', ['slug' => $this->crop->slug, 'lang' => 'kn']));
        $knResponse->assertStatus(200);
        $knResponse->assertSee('ದರ ಮುನ್ಸೂಚನೆ & ನಿರೀಕ್ಷಿತ ಶ್ರೇಣಿ', false);
        $knResponse->assertSee('ಗಮನಿಸಿ:');

        // English mode
        $enResponse = $this->get(route('farmer.crops.show', ['slug' => $this->crop->slug, 'lang' => 'en']));
        $enResponse->assertStatus(200);
        $enResponse->assertSee('Price Forecast & Projections', false);
        $enResponse->assertSee('Disclaimer:');
    }

    public function test_forecast_api_returns_structured_json(): void
    {
        $response = $this->getJson("/api/v1/forecasts?crop={$this->crop->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'crop' => [
                    'id' => $this->crop->id,
                    'slug' => $this->crop->slug,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'crop' => ['id', 'name', 'name_kn', 'slug'],
                'market_id',
                'forecast' => [
                    'is_sufficient',
                    'observations_count',
                    'min_required',
                    'horizons',
                ],
            ]);
    }

    public function test_forecast_api_returns_horizons_when_sufficient_data_present(): void
    {
        // Seed 35 daily observations to cross data sufficiency
        MarketPrice::where('crop_id', $this->crop->id)->delete();

        for ($i = 34; $i >= 0; $i--) {
            MarketPrice::create([
                'crop_id' => $this->crop->id,
                'market_id' => $this->market->id,
                'price_date' => Carbon::today()->subDays($i)->toDateString(),
                'min_price' => 46000,
                'max_price' => 52000,
                'modal_price' => 50000,
                'arrival_quantity' => 40,
                'unit' => 'Quintal',
            ]);
        }

        $response = $this->getJson("/api/v1/forecasts?crop_id={$this->crop->id}&market_id={$this->market->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['forecast']['is_sufficient']);
        $this->assertCount(4, $data['forecast']['horizons']);
        $this->assertEquals(1, $data['forecast']['horizons'][0]['horizon_days']);
        $this->assertEquals(7, $data['forecast']['horizons'][1]['horizon_days']);
        $this->assertEquals(15, $data['forecast']['horizons'][2]['horizon_days']);
        $this->assertEquals(30, $data['forecast']['horizons'][3]['horizon_days']);
    }
}
