<?php

namespace App\Services\Market;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Taluk;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WhereToSellService
{
    /**
     * Vehicle specifications and cost rates (₹ / km).
     */
    public const VEHICLES = [
        'auto' => [
            'key' => 'auto',
            'name_en' => 'Auto / 3-Wheeler',
            'name_kn' => 'ಆಟೋ / 3-ಚಕ್ರ ವಾಹನ',
            'rate_per_km' => 15.0,
            'min_fare' => 200.0,
            'max_capacity_qtl' => 8.0,
            'icon' => '🛺',
        ],
        'pickup' => [
            'key' => 'pickup',
            'name_en' => 'Pickup / Bolero',
            'name_kn' => 'ಪಿಕಪ್ / ಬೊಲೆರೊ',
            'rate_per_km' => 22.0,
            'min_fare' => 400.0,
            'max_capacity_qtl' => 20.0,
            'icon' => '🛻',
        ],
        'truck' => [
            'key' => 'truck',
            'name_en' => 'Mini Truck / Canter',
            'name_kn' => 'ಮಿನಿ ಲಾರಿ / ಕ್ಯಾಂಟರ್',
            'rate_per_km' => 32.0,
            'min_fare' => 800.0,
            'max_capacity_qtl' => 50.0,
            'icon' => '🚚',
        ],
    ];

    /**
     * Rural road winding multiplier (straight-line distance to actual driving road distance).
     */
    public const ROAD_FACTOR = 1.20;

    /**
     * APMC statutory user fee / market cess (1.5%).
     */
    public const APMC_CESS_RATE = 0.015;

    /**
     * Hamali / handling charge per quintal (₹10/Q).
     */
    public const HAMALI_PER_QUINTAL = 10.0;

    /**
     * Compare active Karnataka APMC markets for a selected crop and compute net realization.
     *
     * @param int $cropId
     * @param float|null $latitude
     * @param float|null $longitude
     * @param float $quantityQuintals
     * @param array $options
     * @return array
     */
    public function compare(
        int $cropId,
        ?float $latitude = null,
        ?float $longitude = null,
        float $quantityQuintals = 10.0,
        array $options = []
    ): array {
        $crop = Crop::with('varieties')->findOrFail($cropId);
        $quantityQuintals = max(0.5, $quantityQuintals);

        // 1. Resolve origin coordinates and location label
        $origin = $this->resolveOriginLocation($latitude, $longitude, $options);

        // 2. Resolve vehicle profile & transport rate
        $vehicle = $this->resolveVehicleProfile($options);

        // 3. Find active Karnataka markets trading this crop recently
        $marketPrices = $this->getCandidateMarketPrices($cropId);

        if ($marketPrices->isEmpty()) {
            return [
                'success' => true,
                'crop' => $crop,
                'origin' => $origin,
                'quantity_quintals' => $quantityQuintals,
                'vehicle' => $vehicle,
                'markets_count' => 0,
                'recommended_market' => null,
                'nearest_market' => null,
                'markets' => collect(),
            ];
        }

        // 4. Compute distance, costs, and realization for every candidate market
        $candidates = $marketPrices->map(function ($price) use ($origin, $quantityQuintals, $vehicle) {
            $market = $price->market;
            $modalPrice = (float) $price->modal_price;

            // Straight-line and estimated road distance
            $straightKm = $this->calculateHaversineDistance(
                $origin['latitude'],
                $origin['longitude'],
                (float) $market->latitude,
                (float) $market->longitude
            );
            $roadKm = round($straightKm * self::ROAD_FACTOR, 1);

            // Travel time estimate (average 40 km/h rural road transit)
            $transitHours = round($roadKm / 40.0, 1);

            // Financial Breakdown
            $grossRevenue = round($quantityQuintals * $modalPrice, 2);
            $transportCost = max($vehicle['min_fare'], round($roadKm * $vehicle['rate_per_km'], 2));
            $apmcCess = round($grossRevenue * self::APMC_CESS_RATE, 2);
            $hamali = round($quantityQuintals * self::HAMALI_PER_QUINTAL, 2);
            $totalDeductions = round($transportCost + $apmcCess + $hamali, 2);
            $netRealization = round($grossRevenue - $totalDeductions, 2);
            $netRatePerQtl = round($netRealization / $quantityQuintals, 2);

            return [
                'market_id' => $market->id,
                'market_name' => $market->name,
                'market_name_kn' => $market->name_kn ?? $market->name,
                'district_name' => $market->district->name ?? '',
                'district_name_kn' => $market->district->name_kn ?? '',
                'latitude' => (float) $market->latitude,
                'longitude' => (float) $market->longitude,
                'price_date' => $price->price_date,
                'variety_name' => $price->variety->name ?? 'Standard',
                'modal_price' => $modalPrice,
                'min_price' => (float) $price->min_price,
                'max_price' => (float) $price->max_price,
                'distance_km' => $roadKm,
                'straight_distance_km' => round($straightKm, 1),
                'transit_hours' => $transitHours,
                'gross_revenue' => $grossRevenue,
                'transport_cost' => $transportCost,
                'apmc_cess' => $apmcCess,
                'hamali' => $hamali,
                'total_deductions' => $totalDeductions,
                'net_realization' => $netRealization,
                'net_rate_per_qtl' => $netRatePerQtl,
                'google_maps_url' => "https://www.google.com/maps/dir/?api=1&origin={$origin['latitude']},{$origin['longitude']}&destination={$market->latitude},{$market->longitude}",
            ];
        });

        // 5. Establish Baseline: Identify Nearest Local Mandi
        $nearestCandidate = $candidates->sortBy('distance_km')->first();

        // 6. Compute Comparative Delta vs. Nearest Mandi
        $enriched = $candidates->map(function ($item) use ($nearestCandidate) {
            $isNearest = ($item['market_id'] === $nearestCandidate['market_id']);
            $netDiff = round($item['net_realization'] - $nearestCandidate['net_realization'], 2);
            $grossDiff = round($item['gross_revenue'] - $nearestCandidate['gross_revenue'], 2);
            $transportDiff = round($item['transport_cost'] - $nearestCandidate['transport_cost'], 2);

            if ($isNearest) {
                $verdictKn = 'ಸ್ಥಳೀಯ ಮಾರುಕಟ್ಟೆ (ಆಧಾರ ಮಂಡಿ)';
                $verdictEn = 'Local Market (Baseline)';
                $badgeType = 'base';
            } elseif ($netDiff >= 300) {
                $verdictKn = 'ಹೆಚ್ಚುವರಿ ನಿವ್ವಳ ಲಾಭ: ₹' . number_format($netDiff, 0) . ' (ಸಾರಿಗೆ ಕಳೆದ ನಂತರ ಲಾಭದಾಯಕ)';
                $verdictEn = 'Extra profit: ₹' . number_format($netDiff, 0) . ' after transport';
                $badgeType = 'profit';
            } elseif ($netDiff <= -200) {
                $verdictKn = 'ಸ್ಥಳೀಯ ಮಂಡಿಯೇ ಉತ್ತಮ (₹' . number_format(abs($netDiff), 0) . ' ನಷ್ಟ ಸಂಭವ)';
                $verdictEn = 'Stay local (₹' . number_format(abs($netDiff), 0) . ' net loss)';
                $badgeType = 'loss';
            } else {
                $verdictKn = 'ಸಮಾನ ಲಾಭ (ವ್ಯತ್ಯಾಸ ನಗಣ್ಯ)';
                $verdictEn = 'Comparable net return';
                $badgeType = 'neutral';
            }

            $item['is_nearest'] = $isNearest;
            $item['net_diff_vs_nearest'] = $netDiff;
            $item['gross_diff_vs_nearest'] = $grossDiff;
            $item['transport_diff_vs_nearest'] = $transportDiff;
            $item['verdict_kn'] = $verdictKn;
            $item['verdict_en'] = $verdictEn;
            $item['badge_type'] = $badgeType;

            return $item;
        });

        // 7. Sort markets according to user preference
        $sortOption = $options['sort'] ?? 'net_realization';
        $sorted = match ($sortOption) {
            'price_desc' => $enriched->sortByDesc('modal_price')->values(),
            'distance_asc' => $enriched->sortBy('distance_km')->values(),
            default => $enriched->sortByDesc('net_realization')->values(),
        };

        // 8. Identify #1 Recommended Mandi (highest net realization)
        $recommended = $enriched->sortByDesc('net_realization')->first();

        return [
            'success' => true,
            'crop' => $crop,
            'origin' => $origin,
            'quantity_quintals' => $quantityQuintals,
            'vehicle' => $vehicle,
            'sort' => $sortOption,
            'markets_count' => $sorted->count(),
            'recommended_market' => $recommended,
            'nearest_market' => $nearestCandidate,
            'markets' => $sorted,
        ];
    }

    /**
     * Resolve origin latitude, longitude, and readable name.
     */
    public function resolveOriginLocation(?float $lat, ?float $lng, array $options = []): array
    {
        // 1. Explicit GPS coordinates
        if ($lat !== null && $lng !== null && $lat > 0 && $lng > 0) {
            return [
                'latitude' => $lat,
                'longitude' => $lng,
                'source' => 'gps',
                'name' => 'Current GPS Location',
                'name_kn' => 'ನನ್ನ ಜಿಪಿಎಸ್ ಸ್ಥಳ',
            ];
        }

        // 2. Taluk selection
        if (!empty($options['taluk_id'])) {
            $taluk = Taluk::with('district')->find($options['taluk_id']);
            if ($taluk && $taluk->latitude && $taluk->longitude) {
                return [
                    'latitude' => (float) $taluk->latitude,
                    'longitude' => (float) $taluk->longitude,
                    'source' => 'taluk',
                    'name' => "{$taluk->name}, {$taluk->district->name}",
                    'name_kn' => ($taluk->name_kn ?? $taluk->name) . ', ' . ($taluk->district->name_kn ?? $taluk->district->name),
                ];
            }
        }

        // 3. District selection
        if (!empty($options['district_id'])) {
            $district = District::find($options['district_id']);
            if ($district && $district->latitude && $district->longitude) {
                return [
                    'latitude' => (float) $district->latitude,
                    'longitude' => (float) $district->longitude,
                    'source' => 'district',
                    'name' => "{$district->name} District",
                    'name_kn' => ($district->name_kn ?? $district->name) . ' ಜಿಲ್ಲೆ',
                ];
            }
        }

        // 4. Fallback: Default to Shivamogga or first active Karnataka district
        $defaultDistrict = District::where('is_active', true)->whereNotNull('latitude')->first();
        $fallbackLat = (float) ($defaultDistrict?->latitude ?? 13.9299);
        $fallbackLng = (float) ($defaultDistrict?->longitude ?? 75.5681);
        $fallbackName = $defaultDistrict?->name ?? 'Shivamogga';
        $fallbackNameKn = $defaultDistrict?->name_kn ?? 'ಶಿವಮೊಗ್ಗ';

        return [
            'latitude' => $fallbackLat,
            'longitude' => $fallbackLng,
            'source' => 'default',
            'name' => "{$fallbackName} (Default)",
            'name_kn' => "{$fallbackNameKn} (ಪೂರ್ವನಿಯೋಜಿತ)",
        ];
    }

    /**
     * Resolve vehicle profile with custom rate overrides.
     */
    public function resolveVehicleProfile(array $options = []): array
    {
        $vehicleKey = $options['vehicle'] ?? 'pickup';
        $profile = self::VEHICLES[$vehicleKey] ?? self::VEHICLES['pickup'];

        if (isset($options['custom_rate']) && is_numeric($options['custom_rate']) && (float) $options['custom_rate'] > 0) {
            $profile['rate_per_km'] = (float) $options['custom_rate'];
            $profile['is_custom_rate'] = true;
        } else {
            $profile['is_custom_rate'] = false;
        }

        return $profile;
    }

    /**
     * Fetch active Karnataka market prices for the specified crop.
     */
    protected function getCandidateMarketPrices(int $cropId): Collection
    {
        $latestDate = MarketPrice::karnataka()
            ->where('crop_id', $cropId)
            ->max('price_date');

        if (!$latestDate) {
            return collect();
        }

        // Fetch prices on latest date for this crop in Karnataka
        $prices = MarketPrice::karnataka()
            ->with(['market.district', 'market.taluk', 'variety'])
            ->where('crop_id', $cropId)
            ->where('price_date', $latestDate)
            ->whereHas('market', function ($m) {
                $m->where('is_active', true)
                  ->whereNotNull('latitude')
                  ->whereNotNull('longitude');
            })
            ->get();

        // If today's data is only from 1 market, also search within preceding 3 days to provide comparative context
        if ($prices->count() < 2) {
            $threeDaysAgo = Carbon::parse($latestDate)->subDays(3)->toDateString();
            $recentPrices = MarketPrice::karnataka()
                ->with(['market.district', 'market.taluk', 'variety'])
                ->where('crop_id', $cropId)
                ->whereBetween('price_date', [$threeDaysAgo, $latestDate])
                ->whereHas('market', function ($m) {
                    $m->where('is_active', true)
                      ->whereNotNull('latitude')
                      ->whereNotNull('longitude');
                })
                ->orderBy('price_date', 'desc')
                ->get()
                ->unique('market_id');

            if ($recentPrices->count() > $prices->count()) {
                $prices = $recentPrices;
            }
        }

        return $prices->unique('market_id')->values();
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
}
