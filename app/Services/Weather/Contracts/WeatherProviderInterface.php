<?php

namespace App\Services\Weather\Contracts;

interface WeatherProviderInterface
{
    /**
     * Fetch 7-day weather forecast from provider for given coordinates.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array{
     *     current: array{
     *         temperature: float,
     *         humidity: float,
     *         wind_speed: float,
     *         weather_code: int,
     *         condition_en: string,
     *         condition_kn: string,
     *         icon: string
     *     },
     *     daily: array<int, array{
     *         date: string,
     *         temp_min: float,
     *         temp_max: float,
     *         precipitation_probability: float,
     *         weather_code: int,
     *         condition_en: string,
     *         condition_kn: string,
     *         icon: string,
     *         advisory_en: string,
     *         advisory_kn: string
     *     }>,
     *     raw: array
     * }
     */
    public function fetchForecast(float $latitude, float $longitude): array;
}
