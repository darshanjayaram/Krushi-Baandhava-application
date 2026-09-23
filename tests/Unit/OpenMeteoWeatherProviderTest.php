<?php

namespace Tests\Unit;

use App\Services\Weather\OpenMeteoWeatherProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenMeteoWeatherProviderTest extends TestCase
{
    public function test_translate_wmo_code_maps_to_kannada_and_english(): void
    {
        $provider = new OpenMeteoWeatherProvider();

        $clear = $provider->translateWmoCode(0);
        $this->assertEquals('Clear sky', $clear['en']);
        $this->assertEquals('ಶುಭ್ರ ಆಕಾಶ', $clear['kn']);
        $this->assertEquals('☀️', $clear['icon']);

        $thunder = $provider->translateWmoCode(95);
        $this->assertEquals('Thunderstorm', $thunder['en']);
        $this->assertEquals('ಗುಡುಗು ಸಹಿತ ಮಳೆ', $thunder['kn']);
        $this->assertEquals('⛈️', $thunder['icon']);
    }

    public function test_generate_agricultural_advisories(): void
    {
        $provider = new OpenMeteoWeatherProvider();

        // High rain (80%)
        $highRain = $provider->generateAgriculturalAdvisories(80.0, 26.0, 65);
        $this->assertStringContainsString('ಭಾರೀ ಮಳೆ', $highRain['kn']);
        $this->assertStringContainsString('Postpone spraying', $highRain['en']);

        // Extreme heat (37°C)
        $heatWave = $provider->generateAgriculturalAdvisories(10.0, 37.0, 0);
        $this->assertStringContainsString('ಹೆಚ್ಚಿನ ತಾಪಮಾನ', $heatWave['kn']);
        $this->assertStringContainsString('evening irrigation', $heatWave['en']);

        // Favorable conditions
        $favorable = $provider->generateAgriculturalAdvisories(5.0, 28.0, 0);
        $this->assertStringContainsString('ಅನುಕೂಲಕರ ಹವಾಮಾನ', $favorable['kn']);
        $this->assertStringContainsString('Suitable window', $favorable['en']);
    }

    public function test_fetch_forecast_parses_open_meteo_response(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 27.5,
                    'relative_humidity_2m' => 65.0,
                    'wind_speed_10m' => 12.0,
                    'weather_code' => 2,
                ],
                'daily' => [
                    'time' => ['2026-09-23', '2026-09-24', '2026-09-25'],
                    'temperature_2m_max' => [30.5, 31.0, 29.0],
                    'temperature_2m_min' => [21.0, 20.5, 22.0],
                    'precipitation_probability_max' => [15.0, 75.0, 30.0],
                    'weather_code' => [2, 65, 3],
                ],
            ], 200),
        ]);

        $provider = new OpenMeteoWeatherProvider();
        $result = $provider->fetchForecast(13.9299, 75.5681);

        $this->assertIsArray($result);
        $this->assertEquals(27.5, $result['current']['temperature']);
        $this->assertEquals('ಭಾಗಶಃ ಮೋಡ', $result['current']['condition_kn']);
        $this->assertCount(3, $result['daily']);
        $this->assertEquals('2026-09-23', $result['daily'][0]['date']);
        $this->assertNotEmpty($result['daily'][0]['advisory_kn']);
    }
}
