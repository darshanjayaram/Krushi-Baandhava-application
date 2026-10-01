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
     * Get the configured cache TTL in minutes (default 15 minutes).
     */
    public function getCacheTtlMinutes(): int
    {
        return max(1, (int) \App\Models\SystemSetting::get('weather_cache_ttl_minutes', 15));
    }

    /**
     * Synchronize 7-day weather forecast for a specific district.
     *
     * @param District $district
     * @param bool $force Force sync even if already updated within the cache TTL
     * @return int Number of daily forecast records updated
     */
    public function syncDistrict(District $district, bool $force = false): int
    {
        if (!$district->latitude || !$district->longitude) {
            Log::warning("Skipping weather sync for district {$district->name}: Missing coordinates.");
            return 0;
        }

        // Avoid repeated fetches if recently updated within the configured TTL minutes (default: 15 min)
        if (!$force) {
            $ttlMinutes = $this->getCacheTtlMinutes();
            $recentlySynced = WeatherForecast::where('district_id', $district->id)
                ->where('fetched_at', '>=', Carbon::now()->subMinutes($ttlMinutes))
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
     * Synchronize weather directly using real-time GPS coordinates (Farm-Level).
     */
    public function syncCoordinates(float $latitude, float $longitude, ?District $district = null, bool $force = false): array
    {
        if (!$district) {
            $district = $this->findClosestDistrict($latitude, $longitude);
        }

        $ttlMinutes = $this->getCacheTtlMinutes();

        if (!$force && $district) {
            $todayCached = WeatherForecast::where('district_id', $district->id)
                ->where('forecast_date', Carbon::today()->toDateString())
                ->where('fetched_at', '>=', Carbon::now()->subMinutes($ttlMinutes))
                ->first();

            if ($todayCached) {
                return [
                    'synced' => false,
                    'cached' => true,
                    'district' => $district,
                    'weather' => $todayCached,
                ];
            }
        }

        $forecast = $this->provider->fetchForecast($latitude, $longitude);
        $todayStr = Carbon::today()->toDateString();
        $current = $forecast['current'] ?? [];
        $todayRecord = null;

        foreach ($forecast['daily'] as $daily) {
            $isToday = ($daily['date'] === $todayStr);

            $rec = WeatherForecast::updateOrCreate(
                [
                    'district_id' => $district?->id,
                    'forecast_date' => $daily['date'],
                ],
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
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

            if ($isToday) {
                $todayRecord = $rec;
            }
        }

        return [
            'synced' => true,
            'cached' => false,
            'district' => $district,
            'weather' => $todayRecord,
        ];
    }

    /**
     * Find closest Karnataka district given a latitude & longitude.
     */
    public function findClosestDistrict(float $lat, float $lon): ?District
    {
        $districts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $closest = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($districts as $d) {
            $latFrom = deg2rad($lat);
            $lonFrom = deg2rad($lon);
            $latTo = deg2rad((float) $d->latitude);
            $lonTo = deg2rad((float) $d->longitude);
            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;
            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            $dist = 6371 * $angle;

            if ($dist < $minDist) {
                $minDist = $dist;
                $closest = $d;
            }
        }

        return $closest;
    }

    /**
     * Storage overview for Admin Panel management.
     */
    public function getStorageStats(): array
    {
        $totalRecords = WeatherForecast::count();
        $oldestDate = WeatherForecast::min('forecast_date');
        $newestDate = WeatherForecast::max('forecast_date');
        $latestFetch = WeatherForecast::max('fetched_at');
        $districtsCovered = WeatherForecast::distinct('district_id')->count('district_id');

        return [
            'total_records' => $totalRecords,
            'oldest_date' => $oldestDate,
            'newest_date' => $newestDate,
            'latest_fetch' => $latestFetch,
            'districts_covered' => $districtsCovered,
            'cache_ttl_minutes' => $this->getCacheTtlMinutes(),
        ];
    }

    /**
     * Prune weather forecasts older than a given number of days.
     */
    public function pruneForecasts(int $daysToKeep = 7): int
    {
        $cutoffDate = Carbon::today()->subDays($daysToKeep)->toDateString();
        return WeatherForecast::where('forecast_date', '<', $cutoffDate)->delete();
    }

    /**
     * Purge all saved weather forecasts.
     */
    public function purgeAll(): int
    {
        $count = WeatherForecast::count();
        WeatherForecast::query()->delete();
        return $count;
    }

    /**
     * Synchronize weather forecasts for all active Karnataka districts.
     */
    public function syncAllDistricts(bool $force = false): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

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
