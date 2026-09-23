<?php

namespace Tests\Unit;

use App\Models\District;
use App\Models\WeatherForecast;
use App\Services\Weather\Contracts\WeatherProviderInterface;
use App\Services\Weather\WeatherSyncService;
use Carbon\Carbon;
use Tests\TestCase;

class WeatherSyncServiceTest extends TestCase
{
    public function test_sync_district_persists_forecast_records(): void
    {
        $district = District::where('name', 'Shivamogga')->first()
            ?? District::where('is_active', true)->firstOrFail();

        $mockProvider = $this->createMock(WeatherProviderInterface::class);
        $mockProvider->expects($this->once())
            ->method('fetchForecast')
            ->willReturn([
                'current' => [
                    'temperature' => 28.0,
                    'humidity' => 70.0,
                    'wind_speed' => 11.0,
                    'weather_code' => 1,
                    'condition_en' => 'Mainly clear',
                    'condition_kn' => 'ಹೆಚ್ಚಾಗಿ ಶುಭ್ರ',
                    'icon' => '🌤️',
                ],
                'daily' => [
                    [
                        'date' => Carbon::today()->toDateString(),
                        'temp_min' => 21.0,
                        'temp_max' => 31.0,
                        'precipitation_probability' => 10.0,
                        'weather_code' => 1,
                        'condition_en' => 'Mainly clear',
                        'condition_kn' => 'ಹೆಚ್ಚಾಗಿ ಶುಭ್ರ',
                        'icon' => '🌤️',
                        'advisory_en' => 'Ideal day for field work.',
                        'advisory_kn' => 'ಕೃಷಿ ಕೆಲಸಗಳಿಗೆ ಉತ್ತಮ ದಿನ.',
                    ],
                    [
                        'date' => Carbon::tomorrow()->toDateString(),
                        'temp_min' => 22.0,
                        'temp_max' => 30.0,
                        'precipitation_probability' => 65.0,
                        'weather_code' => 63,
                        'condition_en' => 'Moderate rain',
                        'condition_kn' => 'ಸಾಧಾರಣ ಮಳೆ',
                        'icon' => '🌧️',
                        'advisory_en' => 'Rain expected.',
                        'advisory_kn' => 'ಮಳೆಯ ಸಾಧ್ಯತೆ ಇದೆ.',
                    ],
                ],
                'raw' => [],
            ]);

        $service = new WeatherSyncService($mockProvider);
        $count = $service->syncDistrict($district, true);

        $this->assertEquals(2, $count);

        $todayRecord = WeatherForecast::where('district_id', $district->id)
            ->where('forecast_date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($todayRecord);
        $this->assertEquals(28.0, $todayRecord->current_temperature);
        $this->assertEquals('ಹೆಚ್ಚಾಗಿ ಶುಭ್ರ', $todayRecord->weather_condition_kn);
        $this->assertEquals('ಕೃಷಿ ಕೆಲಸಗಳಿಗೆ ಉತ್ತಮ ದಿನ.', $todayRecord->farming_advisory_kn);
    }
}
