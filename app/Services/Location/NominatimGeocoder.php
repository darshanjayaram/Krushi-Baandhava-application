<?php

namespace App\Services\Location;

use App\Services\Location\Contracts\GeocoderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NominatimGeocoder implements GeocoderInterface
{
    protected const USER_AGENT = 'KrushiBaandhava/1.0 (contact@krushibaandhava.org)';
    protected const BASE_URL = 'https://nominatim.openstreetmap.org/reverse';
    protected const CACHE_TTL_DAYS = 30;

    /**
     * Reverse geocode geographic coordinates with 30-day caching.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        // Round to 3 decimal places (~110m resolution) to group nearby GPS pings and save cache/network calls
        $cacheLat = round($latitude, 3);
        $cacheLon = round($longitude, 3);
        $cacheKey = "nominatim_rev_{$cacheLat}_{$cacheLon}";

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($latitude, $longitude) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ])
                ->timeout(5)
                ->get(self::BASE_URL, [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'accept-language' => 'kn,en',
                    'addressdetails' => 1,
                ]);

                if (!$response->successful()) {
                    Log::warning("Nominatim reverse geocode HTTP failure: {$response->status()}", [
                        'lat' => $latitude,
                        'lon' => $longitude,
                    ]);
                    return null;
                }

                $data = $response->json();
                if (empty($data) || !isset($data['address'])) {
                    return null;
                }

                $addr = $data['address'];

                // Resolve District
                $district = $addr['state_district']
                    ?? $addr['county']
                    ?? $addr['district']
                    ?? null;

                // Strip standard suffixes if present e.g. "Shivamogga District" -> "Shivamogga"
                if ($district) {
                    $district = preg_replace('/\s+(District|ಜಿಲ್ಲೆ)$/i', '', trim($district));
                }

                // Resolve Taluk / Sub-district
                $taluk = $addr['subdistrict']
                    ?? $addr['town']
                    ?? $addr['taluk']
                    ?? $addr['municipality']
                    ?? null;

                if ($taluk) {
                    $taluk = preg_replace('/\s+(Taluk|ತಾಲೂಕು)$/i', '', trim($taluk));
                }

                // Resolve Locality / Village
                $locality = $addr['village']
                    ?? $addr['suburb']
                    ?? $addr['hamlet']
                    ?? $addr['neighbourhood']
                    ?? $addr['city']
                    ?? $taluk;

                return [
                    'district' => $district,
                    'taluk' => $taluk,
                    'locality' => $locality,
                    'state' => $addr['state'] ?? 'Karnataka',
                    'country' => $addr['country'] ?? 'India',
                    'postcode' => $addr['postcode'] ?? null,
                    'display_name' => $data['display_name'] ?? "{$latitude}, {$longitude}",
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ];
            } catch (Throwable $e) {
                Log::error("Nominatim reverse geocoding exception: {$e->getMessage()}", [
                    'lat' => $latitude,
                    'lon' => $longitude,
                ]);
                return null;
            }
        });
    }
}
