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

    public function test_forecast_updates_dynamically_based_on_variety_and_market_selection(): void
    {
        $thirthahalli = Market::where('name', 'like', '%Thirthahalli%')->first();
        if (!$thirthahalli) {
            $this->markTestSkipped('Thirthahalli market not found in database.');
        }

        $sarakuVariety = \App\Models\CropVariety::where('crop_id', $this->crop->id)->where('name', 'like', '%Saraku%')->first();
        $sippegotuVariety = \App\Models\CropVariety::where('crop_id', $this->crop->id)->where('name', 'like', '%Sippegotu%')->first();

        if (!$sarakuVariety || !$sippegotuVariety) {
            $this->markTestSkipped('Saraku or Sippegotu variety not found.');
        }

        // 1. Visit with Saraku variety
        $sarakuResponse = $this->get(route('farmer.crop.detail', [
            'crop' => $this->crop->id,
            'market' => $thirthahalli->name,
            'variety' => $sarakuVariety->id,
        ]));

        $sarakuResponse->assertStatus(200);
        $sarakuForecast = $sarakuResponse->viewData('forecast');
        $this->assertTrue($sarakuForecast['is_sufficient']);
        $this->assertNotEmpty($sarakuForecast['horizons']);
        // Saraku current modal price is around 70k, so 7d forecast must be well above 40k
        $this->assertGreaterThan(40000, $sarakuForecast['horizons'][1]['expected_price']);

        // 2. Visit with Sippegotu variety
        $sippeResponse = $this->get(route('farmer.crop.detail', [
            'crop' => $this->crop->id,
            'market' => $thirthahalli->name,
            'variety' => $sippegotuVariety->id,
        ]));

        $sippeResponse->assertStatus(200);
        $sippeForecast = $sippeResponse->viewData('forecast');
        $this->assertTrue($sippeForecast['is_sufficient']);
        $this->assertNotEmpty($sippeForecast['horizons']);
        // Sippegotu current modal price is around 14k, so 7d forecast must be below 25k
        $this->assertLessThan(25000, $sippeForecast['horizons'][1]['expected_price']);

        // 3. Projections for Saraku and Sippegotu must be completely distinct and calibrated to their respective prices
        $this->assertNotEquals(
            $sarakuForecast['horizons'][1]['expected_price'],
            $sippeForecast['horizons'][1]['expected_price'],
            "Forecast expected price should differ significantly between Saraku and Sippegotu"
        );
    }
}
