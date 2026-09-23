<?php

namespace App\Services\Weather;

use App\Services\Weather\Contracts\WeatherProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenMeteoWeatherProvider implements WeatherProviderInterface
{
    protected const ENDPOINT = 'https://api.open-meteo.com/v1/forecast';
    protected const TIMEOUT_SECONDS = 8;

    /**
     * Fetch 7-day weather forecast from Open-Meteo.
     */
    public function fetchForecast(float $latitude, float $longitude): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get(self::ENDPOINT, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                    'timezone' => 'Asia/Kolkata',
                    'forecast_days' => 7,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException("Open-Meteo HTTP request failed with status: {$response->status()}");
            }

            $data = $response->json();

            // 1. Parse Current Weather
            $currentData = $data['current'] ?? [];
            $currentCode = (int) ($currentData['weather_code'] ?? 0);
            $currentMeta = $this->translateWmoCode($currentCode);

            $current = [
                'temperature' => (float) ($currentData['temperature_2m'] ?? 25.0),
                'humidity' => (float) ($currentData['relative_humidity_2m'] ?? 60.0),
                'wind_speed' => (float) ($currentData['wind_speed_10m'] ?? 10.0),
                'weather_code' => $currentCode,
                'condition_en' => $currentMeta['en'],
                'condition_kn' => $currentMeta['kn'],
                'icon' => $currentMeta['icon'],
            ];

            // 2. Parse 7-Day Daily Forecasts
            $daily = [];
            $dates = $data['daily']['time'] ?? [];
            $maxTemps = $data['daily']['temperature_2m_max'] ?? [];
            $minTemps = $data['daily']['temperature_2m_min'] ?? [];
            $rainProbs = $data['daily']['precipitation_probability_max'] ?? [];
            $weatherCodes = $data['daily']['weather_code'] ?? [];

            foreach ($dates as $index => $date) {
                $code = (int) ($weatherCodes[$index] ?? 0);
                $meta = $this->translateWmoCode($code);
                $maxT = (float) ($maxTemps[$index] ?? 30.0);
                $minT = (float) ($minTemps[$index] ?? 20.0);
                $rainP = (float) ($rainProbs[$index] ?? 0.0);

                $advisories = $this->generateAgriculturalAdvisories($rainP, $maxT, $code);

                $daily[] = [
                    'date' => $date,
                    'temp_min' => $minT,
                    'temp_max' => $maxT,
                    'precipitation_probability' => $rainP,
                    'weather_code' => $code,
                    'condition_en' => $meta['en'],
                    'condition_kn' => $meta['kn'],
                    'icon' => $meta['icon'],
                    'advisory_en' => $advisories['en'],
                    'advisory_kn' => $advisories['kn'],
                ];
            }

            return [
                'current' => $current,
                'daily' => $daily,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error("OpenMeteoWeatherProvider exception: {$e->getMessage()}", [
                'lat' => $latitude,
                'lon' => $longitude,
            ]);
            throw $e;
        }
    }

    /**
     * Map WMO Weather Interpretation Codes (0-99) into human-readable strings & icons.
     * Reference: https://open-meteo.com/en/docs
     */
    public function translateWmoCode(int $code): array
    {
        return match ($code) {
            0 => ['en' => 'Clear sky', 'kn' => 'ಶುಭ್ರ ಆಕಾಶ', 'icon' => '☀️'],
            1 => ['en' => 'Mainly clear', 'kn' => 'ಹೆಚ್ಚಾಗಿ ಶುಭ್ರ', 'icon' => '🌤️'],
            2 => ['en' => 'Partly cloudy', 'kn' => 'ಭಾಗಶಃ ಮೋಡ', 'icon' => '⛅'],
            3 => ['en' => 'Overcast', 'kn' => 'ದಟ್ಟ ಮೋಡ', 'icon' => '☁️'],
            45, 48 => ['en' => 'Foggy', 'kn' => 'ಮಂಜು ಕವಿದ', 'icon' => '🌫️'],
            51, 53, 55 => ['en' => 'Drizzle', 'kn' => 'ತುಂತುರು ಮಳೆ', 'icon' => '🌦️'],
            61 => ['en' => 'Slight rain', 'kn' => 'ಹಗುರ ಮಳೆ', 'icon' => '🌧️'],
            63 => ['en' => 'Moderate rain', 'kn' => 'ಸಾಧಾರಣ ಮಳೆ', 'icon' => '🌧️'],
            65 => ['en' => 'Heavy rain', 'kn' => 'ಭಾರೀ ಮಳೆ', 'icon' => '🌧️'],
            80, 81 => ['en' => 'Rain showers', 'kn' => 'ಮಳೆ ಹನಿಗಳು', 'icon' => '🌦️'],
            82 => ['en' => 'Violent rain showers', 'kn' => 'ಧಾರಾಕಾರ ಮಳೆ', 'icon' => '⛈️'],
            95 => ['en' => 'Thunderstorm', 'kn' => 'ಗುಡುಗು ಸಹಿತ ಮಳೆ', 'icon' => '⛈️'],
            96, 99 => ['en' => 'Thunderstorm with hail', 'kn' => 'ಆಲಿಕಲ್ಲು ಸಹಿತ ಮಳೆ', 'icon' => '⛈️'],
            default => ['en' => 'Cloudy', 'kn' => 'ಮೋಡ ಕವಿದ ವಾತಾವರಣ', 'icon' => '⛅'],
        };
    }

    /**
     * Generate dynamic, actionable agricultural advisories in Kannada and English.
     */
    public function generateAgriculturalAdvisories(float $rainProbability, float $tempMax, int $weatherCode): array
    {
        // Thunderstorm or severe rain
        if (in_array($weatherCode, [82, 95, 96, 99]) || $rainProbability >= 70) {
            return [
                'kn' => 'ಭಾರೀ ಮಳೆ ಮತ್ತು ಗುಡುಗಿನ ಮುನ್ಸೂಚನೆ ಇದೆ. ಕೀಟನಾಶಕ ಸಿಂಪಡಣೆ, ಗೊಬ್ಬರ ವಿತರಣೆ ಮತ್ತು ಬೆಳೆ ಕೊಯ್ಲು ಮುಂದೂಡಿ. ತೋಟದಲ್ಲಿ ನೀರು ನಿಲ್ಲದಂತೆ ಕಾಲುವೆ ತೆರವುಗೊಳಿಸಿ.',
                'en' => 'Heavy rainfall / thunderstorm forecasted. Postpone spraying, fertilization, and harvesting. Ensure clear field drainage.',
            ];
        }

        // Moderate rain expected
        if ($rainProbability >= 40) {
            return [
                'kn' => 'ಸಾಧಾರಣ ಮಳೆಯ ಸಾಧ್ಯತೆ. ನೀರಾವರಿ ನೀಡುವುದನ್ನು ತಾತ್ಕಾಲಿಕವಾಗಿ ನಿಲ್ಲಿಸಿ. ಕೊಯ್ಲು ಮಾಡಿದ ಕೃಷಿ ಉತ್ಪನ್ನಗಳನ್ನು ಸುರಕ್ಷಿತ ಗೋದಾಮಿನಲ್ಲಿ ಸಂಗ್ರಹಿಸಿ.',
                'en' => 'Moderate rainfall likely. Suspend irrigation operations and protect harvested commodities from moisture.',
            ];
        }

        // High heat advisory
        if ($tempMax >= 35.0) {
            return [
                'kn' => 'ಹೆಚ್ಚಿನ ತಾಪಮಾನ ದಾಖಲಾಗುವ ಸಾಧ್ಯತೆ. ತೇವಾಂಶ ಕಾಪಾಡಲು ಅಡಿಕೆ ಮತ್ತು ಕಾಫಿ ಗಿಡಗಳಿಗೆ ಸಂಜೆ ವೇಳೆ ನಿಯಮಿತ ನೀರಾವರಿ ಒದಗಿಸಿ.',
                'en' => 'High temperature conditions. Provide adequate evening irrigation to plantation and vegetable crops.',
            ];
        }

        // Favorable farming conditions
        return [
            'kn' => 'ಅನುಕೂಲಕರ ಹವಾಮಾನ. ಕೀಟನಾಶಕ ಸಿಂಪಡಣೆ, ಕಳೆ ಕೀಳುವಿಕೆ, ಗೊಬ್ಬರ ವಿತರಣೆ ಮತ್ತು ಬೆಳೆ ಕೊಯ್ಲು ಕಾರ್ಯಗಳಿಗೆ ಸೂಕ್ತ ಸಮಯ.',
            'en' => 'Favorable dry weather. Suitable window for weeding, chemical spraying, fertilizer top-dressing, and harvesting.',
        ];
    }
}
