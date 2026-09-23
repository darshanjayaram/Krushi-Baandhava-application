<?php

namespace App\Services\Location;

use App\Models\Crop;
use App\Models\Market;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NearbyMarketService
{
    /**
     * Find Karnataka APMC markets within a given radius (km) from coordinates.
     *
     * @param float $latitude User latitude
     * @param float $longitude User longitude
     * @param float $radiusKm Maximum search radius in kilometers (default 50)
     * @param string|null $cropSlug Optional crop slug to filter by commodity traded today
     * @param int $limit Maximum number of results
     * @return Collection
     */
    public function findNearby(
        float $latitude,
        float $longitude,
        float $radiusKm = 50.0,
        ?string $cropSlug = null,
        int $limit = 20
    ): Collection {
        // 1. Query active Karnataka markets that have coordinates
        $markets = Market::karnataka()
            ->with(['district', 'taluk'])
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($markets->isEmpty()) {
            return collect();
        }

        // 2. Resolve crop filter if specified
        $filteredCrop = null;
        if ($cropSlug) {
            $filteredCrop = Crop::where('slug', $cropSlug)->where('is_active', true)->first();
        }

        // 3. Resolve latest price date in Karnataka
        $latestPriceDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();

        // 4. Calculate distances and filter by radius
        $nearby = $markets->map(function (Market $market) use ($latitude, $longitude) {
            $distance = $this->calculateHaversineDistance(
                $latitude,
                $longitude,
                (float) $market->latitude,
                (float) $market->longitude
            );

            $bearing = $this->calculateBearing(
                $latitude,
                $longitude,
                (float) $market->latitude,
                (float) $market->longitude
            );

            $market->distance_km = round($distance, 1);
            $market->bearing_degrees = round($bearing, 0);
            $market->direction_en = $this->getCardinalDirection($bearing, 'en');
            $market->direction_kn = $this->getCardinalDirection($bearing, 'kn');
            $market->google_maps_url = "https://www.google.com/maps/dir/?api=1&destination={$market->latitude},{$market->longitude}";

            return $market;
        })
        ->filter(fn (Market $m) => $m->distance_km <= $radiusKm)
        ->sortBy('distance_km')
        ->values();

        if ($nearby->isEmpty()) {
            return collect();
        }

        $marketIds = $nearby->pluck('id')->all();

        // 5. Fetch today's prices for these nearby markets to enrich them
        $pricesQuery = MarketPrice::karnataka()
            ->with(['crop', 'variety'])
            ->whereIn('market_id', $marketIds)
            ->where('price_date', $latestPriceDate);

        $allTodayPrices = $pricesQuery->get()->groupBy('market_id');

        // 6. Enrich markets with traded crops, prices, and crop filter matching
        $enriched = $nearby->map(function (Market $market) use ($allTodayPrices, $filteredCrop) {
            $marketPrices = $allTodayPrices->get($market->id, collect());

            $market->commodities_count = $marketPrices->pluck('crop_id')->unique()->count();
            $market->top_prices = $marketPrices->sortByDesc('modal_price')->take(3)->values();

            if ($filteredCrop) {
                $cropPrice = $marketPrices->firstWhere('crop_id', $filteredCrop->id);
                $market->filtered_crop_price = $cropPrice;
                $market->trades_filtered_crop = $cropPrice !== null;
            } else {
                $market->trades_filtered_crop = true;
            }

            return $market;
        });

        // If crop filter was applied, only keep markets actively trading that commodity today
        if ($filteredCrop) {
            $enriched = $enriched->filter(fn (Market $m) => $m->trades_filtered_crop);
        }

        return $enriched->take($limit)->values();
    }

    /**
     * Calculate spherical distance between two points using the Haversine formula (km).
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * Calculate initial compass bearing from point 1 to point 2 (degrees 0-360).
     */
    public function calculateBearing(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLonRad = deg2rad($lon2 - $lon1);

        $y = sin($dLonRad) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($dLonRad);

        $bearingRad = atan2($y, $x);
        $bearingDeg = fmod(rad2deg($bearingRad) + 360.0, 360.0);

        return $bearingDeg;
    }

    /**
     * Convert compass bearing degrees into 8-point cardinal direction.
     */
    public function getCardinalDirection(float $degrees, string $lang = 'en'): string
    {
        $directionsEn = ['North', 'North-East', 'East', 'South-East', 'South', 'South-West', 'West', 'North-West'];
        $directionsKn = ['ಉತ್ತರ', 'ಈಶಾನ್ಯ', 'ಪೂರ್ವ', 'ಆಗ್ನೇಯ', 'ದಕ್ಷಿಣ', 'ನೈಋತ್ಯ', 'ಪಶ್ಚಿಮ', 'ವಾಯುವ್ಯ'];

        $index = (int) round($degrees / 45.0) % 8;

        return $lang === 'kn' ? $directionsKn[$index] : $directionsEn[$index];
    }
}
