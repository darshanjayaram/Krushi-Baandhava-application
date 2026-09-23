<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\WeatherForecast;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FarmerWeatherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 28.5,
                    'relative_humidity_2m' => 62.0,
                    'wind_speed_10m' => 14.0,
                    'weather_code' => 2,
                ],
                'daily' => [
                    'time' => [
                        Carbon::today()->toDateString(),
                        Carbon::tomorrow()->toDateString(),
                        Carbon::today()->addDays(2)->toDateString(),
                    ],
                    'temperature_2m_max' => [32.0, 31.0, 29.5],
                    'temperature_2m_min' => [21.0, 20.0, 21.5],
                    'precipitation_probability_max' => [20.0, 65.0, 10.0],
                    'weather_code' => [2, 63, 1],
                ],
            ], 200),
        ]);
    }

    public function test_farmer_weather_screen_renders_successfully(): void
    {
        $response = $this->get('/weather');

        $response->assertStatus(200);
        $response->assertSee('ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ & ಕೃಷಿ ಸಲಹೆ', false);
        $response->assertSee('ಮುಂದಿನ 7 ದಿನಗಳ ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ', false);
        $response->assertSee('ಇಂದಿನ ಕೃಷಿ & ಬೆಳೆ ಸಂರಕ್ಷಣಾ ಸಲಹೆ', false);
    }

    public function test_farmer_weather_screen_handles_district_switching(): void
    {
        $district = District::where('is_active', true)->where('name', 'Shivamogga')->first()
            ?? District::where('is_active', true)->firstOrFail();

        $response = $this->get('/weather?district=' . $district->id);

        $response->assertStatus(200);
        $response->assertSee($district->name);
    }

    public function test_weather_api_returns_structured_json_forecast(): void
    {
        $district = District::where('is_active', true)->firstOrFail();

        $response = $this->getJson('/api/v1/weather/forecast?district_id=' . $district->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'district' => ['id', 'name', 'name_kn', 'latitude', 'longitude'],
            'current' => ['temperature', 'humidity', 'wind_speed', 'weather_code', 'condition', 'icon', 'advisory'],
            'forecast_days',
            'daily' => [
                '*' => [
                    'date',
                    'day_name' => ['en', 'kn'],
                    'temp_min',
                    'temp_max',
                    'precipitation_probability',
                    'weather_code',
                    'condition' => ['en', 'kn'],
                    'icon',
                    'farming_advisory' => ['en', 'kn'],
                ],
            ],
            'source',
        ]);
    }

    public function test_sync_weather_artisan_command_runs_successfully(): void
    {
        $district = District::where('is_active', true)->firstOrFail();

        $exitCode = Artisan::call('krushi:sync-weather', [
            'district' => $district->id,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        $forecastCount = WeatherForecast::where('district_id', $district->id)->count();
        $this->assertGreaterThan(0, $forecastCount);
    }
}
