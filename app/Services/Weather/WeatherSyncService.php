<?php

namespace App\Services\Weather;

use App\Models\District;
use App\Models\WeatherForecast;
use App\Services\Weather\Contracts\WeatherProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherSyncService
{
    public function __construct(
        protected WeatherProviderInterface $provider
    ) {}

    /**
     * Synchronize 7-day weather forecast for a specific district.
     *
     * @param District $district
     * @param bool $force Force sync even if already updated within the last 6 hours
     * @return int Number of daily forecast records updated
     */
    public function syncDistrict(District $district, bool $force = false): int
    {
        if (!$district->latitude || !$district->longitude) {
            Log::warning("Skipping weather sync for district {$district->name}: Missing coordinates.");
            return 0;
        }

        // Avoid repeated fetches if recently updated within 6 hours (unless forced)
        if (!$force) {
            $recentlySynced = WeatherForecast::where('district_id', $district->id)
                ->where('fetched_at', '>=', Carbon::now()->subHours(6))
                ->exists();

            if ($recentlySynced) {
                return 0;
            }
        }

        $forecast = $this->provider->fetchForecast(
            (float) $district->latitude,
            (float) $district->longitude
        );

        $todayStr = Carbon::today()->toDateString();
        $current = $forecast['current'] ?? [];
        $syncedCount = 0;

        foreach ($forecast['daily'] as $daily) {
            $isToday = ($daily['date'] === $todayStr);

            WeatherForecast::updateOrCreate(
                [
                    'district_id' => $district->id,
                    'forecast_date' => $daily['date'],
                ],
                [
                    'latitude' => $district->latitude,
                    'longitude' => $district->longitude,
                    'current_temperature' => $isToday ? ($current['temperature'] ?? null) : null,
                    'current_humidity' => $isToday ? ($current['humidity'] ?? null) : null,
                    'current_wind_speed' => $isToday ? ($current['wind_speed'] ?? null) : null,
                    'current_weather_code' => $isToday ? ($current['weather_code'] ?? null) : null,
                    'temp_min' => $daily['temp_min'],
                    'temp_max' => $daily['temp_max'],
                    'precipitation_probability' => $daily['precipitation_probability'],
                    'weather_code' => $daily['weather_code'],
                    'weather_condition_en' => $daily['condition_en'],
                    'weather_condition_kn' => $daily['condition_kn'],
                    'weather_icon' => $daily['icon'],
                    'farming_advisory_en' => $daily['advisory_en'],
                    'farming_advisory_kn' => $daily['advisory_kn'],
                    'raw_payload' => $forecast['raw'] ?? null,
                    'fetched_at' => Carbon::now(),
                ]
            );

            $syncedCount++;
        }

        return $syncedCount;
    }

    /**
     * Synchronize weather forecasts for all active Karnataka districts.
     */
    public function syncAllDistricts(bool $force = false): array
    {
        $districts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $districts->count(),
            'synced' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($districts as $district) {
            try {
                $count = $this->syncDistrict($district, $force);
                if ($count > 0) {
                    $stats['synced']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (Throwable $e) {
                $stats['failed']++;
                Log::error("Failed to sync weather for district {$district->name}: {$e->getMessage()}");
            }
        }

        return $stats;
    }
}
