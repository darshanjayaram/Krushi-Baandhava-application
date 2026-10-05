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
        $maxDistance = !empty($options['max_distance']) ? (float) $options['max_distance'] : null;
        $isRoundTrip = isset($options['round_trip']) ? filter_var($options['round_trip'], FILTER_VALIDATE_BOOLEAN) : true;

        // 3. Resolve variety selection and comparison baseline market if specified
        $varietyId = !empty($options['variety_id']) ? (int) $options['variety_id'] : null;
        $baselineMarketId = !empty($options['baseline_market_id']) 
            ? (int) $options['baseline_market_id'] 
            : (!empty($options['market_id']) ? (int) $options['market_id'] : null);

        // 4. Find active Karnataka markets trading this crop/variety strictly within freshness rules
        $marketPrices = $this->getCandidateMarketPrices($crop, $varietyId, $baselineMarketId);

        if ($marketPrices->isEmpty()) {
            return [
                'success' => true,
                'crop' => $crop,
                'selected_variety_id' => $varietyId,
                'selected_variety' => $varietyId ? $crop->varieties->firstWhere('id', $varietyId) : null,
                'origin' => $origin,
                'quantity_quintals' => $quantityQuintals,
                'vehicle' => $vehicle,
                'max_distance' => $maxDistance,
                'round_trip' => $isRoundTrip,
                'is_round_trip' => $isRoundTrip,
                'markets_count' => 0,
                'recommended_market' => null,
                'baseline_market' => null,
                'is_custom_baseline' => false,
                'nearest_market' => null,
                'markets' => collect(),
            ];
        }

        // 5. Compute distance, costs, and realization for every candidate market
        $cutoffDate = $crop->getFreshnessCutoffDate();
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $candidates = $marketPrices->map(function ($price) use ($origin, $quantityQuintals, $vehicle, $cutoffDate, $today, $yesterday, $isRoundTrip) {
            $market = $price->market;
            $modalPrice = (float) $price->modal_price;

            // Straight-line and estimated road distance (one-way)
            $straightKm = $this->calculateHaversineDistance(
                $origin['latitude'],
                $origin['longitude'],
                (float) $market->latitude,
                (float) $market->longitude
            );
            $roadKm = round($straightKm * self::ROAD_FACTOR, 1);

            // Travel time estimate (average 40 km/h rural road transit)
            $transitHours = round($roadKm / 40.0, 1);

            // Two-Way Billed Mileage when Round-Trip is active (Farm -> Mandi -> Farm)
            $billedKm = $isRoundTrip ? round($roadKm * 2, 1) : $roadKm;

            // Financial Gross Revenue
            $grossRevenue = round($quantityQuintals * $modalPrice, 2);

            // Transport Cost Calculation according to flexible rate mode
            $rateType = $vehicle['rate_type'] ?? 'per_km';
            $customVal = $vehicle['custom_rate_value'] ?? $vehicle['rate_per_km'];

            if ($rateType === 'per_quintal') {
                $transportCost = round($quantityQuintals * $customVal, 2);
            } elseif ($rateType === 'fixed_fare') {
                $transportCost = round($customVal, 2);
            } elseif ($rateType === 'fuel_only') {
                $transportCost = round($billedKm * $customVal, 2);
            } else {
                // Default: per_km (billed on round-trip mileage if active)
                $transportCost = max($vehicle['min_fare'] ?? 0, round($billedKm * $vehicle['rate_per_km'], 2));
            }

            $apmcCess = round($grossRevenue * self::APMC_CESS_RATE, 2);
            $hamali = round($quantityQuintals * self::HAMALI_PER_QUINTAL, 2);
            $totalDeductions = round($transportCost + $apmcCess + $hamali, 2);
            $netRealization = round($grossRevenue - $totalDeductions, 2);
            $netRatePerQtl = round($netRealization / $quantityQuintals, 2);

            // Date & Freshness formatting (Explicit As of Date)
            $priceDate = $price->price_date ? Carbon::parse($price->price_date)->toDateString() : null;
            $isToday = ($priceDate === $today);
            $isYesterday = ($priceDate === $yesterday);
            $isStale = ($priceDate && $priceDate < $cutoffDate);

            if ($priceDate) {
                $carbonDate = Carbon::parse($priceDate);
                $formattedDate = $carbonDate->format('d M Y');
                $dayMonth = $carbonDate->format('d M');

                if ($isToday) {
                    $asOfLabelKn = 'ಇಂದು (' . $dayMonth . ')';
                    $asOfLabelEn = 'Today (' . $dayMonth . ')';
                    $freshnessBadgeKn = '📅 ಇಂದು (' . $dayMonth . ')';
                    $freshnessBadgeEn = '📅 Today (' . $dayMonth . ')';
                } elseif ($isYesterday) {
                    $asOfLabelKn = 'ನಿನ್ನೆ (' . $dayMonth . ')';
                    $asOfLabelEn = 'Yesterday (' . $dayMonth . ')';
                    $freshnessBadgeKn = '📅 ನಿನ್ನೆ (' . $dayMonth . ')';
                    $freshnessBadgeEn = '📅 Yesterday (' . $dayMonth . ')';
                } else {
                    $asOfLabelKn = $formattedDate;
                    $asOfLabelEn = $formattedDate;
                    $freshnessBadgeKn = '📅 ' . $formattedDate;
                    $freshnessBadgeEn = '📅 ' . $formattedDate;
                }

                if ($isStale) {
                    $freshnessBadgeKn = '⚠️ ಹಳೆಯ ದರ: ' . $formattedDate;
                    $freshnessBadgeEn = '⚠️ Stale: ' . $formattedDate;
                }
            } else {
                $formattedDate = '—';
                $asOfLabelKn = '—';
                $asOfLabelEn = '—';
                $freshnessBadgeKn = '—';
                $freshnessBadgeEn = '—';
            }

            return [
                'market_id' => $market->id,
                'market_name' => $market->name,
                'market_name_kn' => $market->name_kn ?? $market->name,
                'district_name' => $market->district->name ?? '',
                'district_name_kn' => $market->district->name_kn ?? '',
                'latitude' => (float) $market->latitude,
                'longitude' => (float) $market->longitude,
                'price_date' => $priceDate,
                'as_of_date_formatted' => $formattedDate,
                'as_of_label_kn' => $asOfLabelKn,
                'as_of_label_en' => $asOfLabelEn,
                'freshness_badge_kn' => $freshnessBadgeKn,
                'freshness_badge_en' => $freshnessBadgeEn,
                'is_today' => $isToday,
                'is_yesterday' => $isYesterday,
                'is_stale' => $isStale,
                'variety_id' => $price->variety_id,
                'variety_name' => $price->variety->name ?? 'Standard',
                'variety_name_kn' => $price->variety->name_kn ?? $price->variety->name ?? 'ಸಾಮಾನ್ಯ',
                'modal_price' => $modalPrice,
                'min_price' => (float) $price->min_price,
                'max_price' => (float) $price->max_price,
                'distance_km' => $roadKm,
                'billed_km' => $billedKm,
                'is_round_trip' => $isRoundTrip,
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

        // 5b. Filter candidates strictly by KM Range / Search Radius if specified
        if ($maxDistance && $maxDistance > 0) {
            $candidates = $candidates->filter(fn($c) => $c['distance_km'] <= $maxDistance)->values();
        }

        if ($candidates->isEmpty()) {
            return [
                'success' => true,
                'crop' => $crop,
                'selected_variety_id' => $varietyId,
                'selected_variety' => $varietyId ? $crop->varieties->firstWhere('id', $varietyId) : null,
                'origin' => $origin,
                'quantity_quintals' => $quantityQuintals,
                'vehicle' => $vehicle,
                'max_distance' => $maxDistance,
                'round_trip' => $isRoundTrip,
                'is_round_trip' => $isRoundTrip,
                'markets_count' => 0,
                'recommended_market' => null,
                'baseline_market' => null,
                'is_custom_baseline' => false,
                'nearest_market' => null,
                'markets' => collect(),
            ];
        }

        // 6. Establish Baseline: Identify Baseline Mandi (User-Selected or Nearest GPS)
        $baselineCandidate = null;
        $isCustomBaseline = false;

        if ($baselineMarketId) {
            $baselineCandidate = $candidates->firstWhere('market_id', $baselineMarketId);
            if ($baselineCandidate) {
                $isCustomBaseline = true;
            }
        }

        // Fallback to closest mandi by distance if no baseline or baseline not in candidates
        if (!$baselineCandidate) {
            $baselineCandidate = $candidates->sortBy('distance_km')->first();
            $isCustomBaseline = false;
        }

        $nearestCandidate = $candidates->sortBy('distance_km')->first();

        // 7. Compute Comparative Delta vs. Baseline Mandi
        $enriched = $candidates->map(function ($item) use ($baselineCandidate, $nearestCandidate, $isCustomBaseline) {
            $isBaseline = ($item['market_id'] === $baselineCandidate['market_id']);
            $isNearest = ($item['market_id'] === $nearestCandidate['market_id']);

            $netDiff = round($item['net_realization'] - $baselineCandidate['net_realization'], 2);
            $grossDiff = round($item['gross_revenue'] - $baselineCandidate['gross_revenue'], 2);
            $transportDiff = round($item['transport_cost'] - $baselineCandidate['transport_cost'], 2);

            $baseNameKn = $baselineCandidate['market_name_kn'] ?? $baselineCandidate['market_name'];
            $baseNameEn = $baselineCandidate['market_name'];

            if ($isBaseline) {
                $verdictKn = $isCustomBaseline 
                    ? 'ಆಯ್ಕೆಯ ಆಧಾರ ಮಂಡಿ' 
                    : 'ಸ್ಥಳೀಯ ಮಾರುಕಟ್ಟೆ (ಆಧಾರ ಮಂಡಿ)';
                $verdictEn = $isCustomBaseline 
                    ? 'Selected Baseline Market' 
                    : 'Local Market (Baseline)';
                $badgeType = 'base';
            } elseif ($netDiff >= 300) {
                $verdictKn = $isCustomBaseline
                    ? "{$baseNameKn} ಗಿಂತ +₹" . number_format($netDiff, 0) . ' ಹೆಚ್ಚುವರಿ ಲಾಭ'
                    : 'ಹೆಚ್ಚುವರಿ ನಿವ್ವಳ ಲಾಭ: +₹' . number_format($netDiff, 0) . ' (ಸಾರಿಗೆ ನಂತರ)';
                $verdictEn = $isCustomBaseline
                    ? "+₹" . number_format($netDiff, 0) . " extra profit vs {$baseNameEn}"
                    : 'Extra profit: +₹' . number_format($netDiff, 0) . ' after transport';
                $badgeType = 'profit';
            } elseif ($netDiff <= -200) {
                $verdictKn = $isCustomBaseline
                    ? "ನಿಮ್ಮ ಆಯ್ಕೆಯ {$baseNameKn} ಮಂಡಿಯೇ ಉತ್ತಮ (₹" . number_format(abs($netDiff), 0) . ' ನಷ್ಟ ಸಂಭವ)'
                    : 'ಸ್ಥಳೀಯ ಮಂಡಿಯೇ ಉತ್ತಮ (₹' . number_format(abs($netDiff), 0) . ' ನಷ್ಟ ಸಂಭವ)';
                $verdictEn = $isCustomBaseline
                    ? "Stay with {$baseNameEn} (₹" . number_format(abs($netDiff), 0) . ' net loss elsewhere)'
                    : 'Stay local (₹' . number_format(abs($netDiff), 0) . ' net loss)';
                $badgeType = 'loss';
            } else {
                $verdictKn = 'ಸಮಾನ ಲಾಭ (ವ್ಯತ್ಯಾಸ ನಗಣ್ಯ)';
                $verdictEn = 'Comparable net return';
                $badgeType = 'neutral';
            }

            $item['is_baseline'] = $isBaseline;
            $item['is_nearest'] = $isNearest;
            $item['net_diff_vs_baseline'] = $netDiff;
            $item['net_diff_vs_nearest'] = $netDiff; // Backward compatibility
            $item['gross_diff_vs_baseline'] = $grossDiff;
            $item['transport_diff_vs_baseline'] = $transportDiff;
            $item['verdict_kn'] = $verdictKn;
            $item['verdict_en'] = $verdictEn;
            $item['badge_type'] = $badgeType;

            return $item;
        });

        // 8. Sort markets according to user preference
        $sortOption = $options['sort'] ?? 'net_realization';
        $sorted = match ($sortOption) {
            'price_desc' => $enriched->sortByDesc('modal_price')->values(),
            'distance_asc' => $enriched->sortBy('distance_km')->values(),
            default => $enriched->sortByDesc('net_realization')->values(),
        };

        // 9. Identify #1 Recommended Mandi (highest net realization)
        $recommended = $enriched->sortByDesc('net_realization')->first();

        return [
            'success' => true,
            'crop' => $crop,
            'selected_variety_id' => $varietyId,
            'selected_variety' => $varietyId ? $crop->varieties->firstWhere('id', $varietyId) : null,
            'origin' => $origin,
            'quantity_quintals' => $quantityQuintals,
            'vehicle' => $vehicle,
            'sort' => $sortOption,
            'max_distance' => $maxDistance,
            'round_trip' => $isRoundTrip,
            'is_round_trip' => $isRoundTrip,
            'markets_count' => $sorted->count(),
            'recommended_market' => $recommended,
            'baseline_market' => $baselineCandidate,
            'is_custom_baseline' => $isCustomBaseline,
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

        // 4. Fallback: Default to configured default district or first active Karnataka district
        $defaultDistrictName = \App\Models\SystemSetting::get('default_district', 'Shivamogga');
        $defaultDistrict = District::where('is_active', true)->where('name', $defaultDistrictName)->first()
            ?? District::where('is_active', true)->whereNotNull('latitude')->first();
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
        $rateType = $options['rate_type'] ?? 'per_km';

        $profile['rate_type'] = $rateType;

        if (isset($options['custom_rate']) && is_numeric($options['custom_rate']) && (float) $options['custom_rate'] > 0) {
            $customVal = (float) $options['custom_rate'];
            $profile['custom_rate_value'] = $customVal;
            $profile['is_custom_rate'] = true;
            if ($rateType === 'per_km' || $rateType === 'fuel_only') {
                $profile['rate_per_km'] = $customVal;
            }
        } else {
            $profile['is_custom_rate'] = false;
            $profile['custom_rate_value'] = $profile['rate_per_km'];
        }

        return $profile;
    }

    /**
     * Fetch active Karnataka market prices for the specified crop (and optionally specific variety).
     * Strictly enforces the crop's staleness threshold so outdated prices do not distort recommendations.
     */
    protected function getCandidateMarketPrices(Crop|int $crop, ?int $varietyId = null, ?int $baselineMarketId = null): Collection
    {
        if (is_numeric($crop)) {
            $crop = Crop::with('varieties')->findOrFail($crop);
        }

        $cropId = $crop->id;
        $cutoffDate = $crop->getFreshnessCutoffDate();

        // 1. First, search strictly within the crop's freshness window (>= cutoffDate)
        $query = MarketPrice::karnataka()
            ->with(['market.district', 'market.taluk', 'variety'])
            ->where('crop_id', $cropId)
            ->where('price_date', '>=', $cutoffDate)
            ->where('modal_price', '>', 0)
            ->whereHas('market', function ($m) {
                $m->where('is_active', true)
                  ->whereNotNull('latitude')
                  ->whereNotNull('longitude');
            })
            ->orderBy('price_date', 'desc')
            ->orderBy('modal_price', 'desc');

        if ($varietyId) {
            $query->where('variety_id', $varietyId);
        }

        $allPrices = $query->get();

        // 2. If filtering by variety yielded no results, fallback to any variety of this crop within the freshness window
        if ($varietyId && $allPrices->isEmpty()) {
            $allPrices = MarketPrice::karnataka()
                ->with(['market.district', 'market.taluk', 'variety'])
                ->where('crop_id', $cropId)
                ->where('price_date', '>=', $cutoffDate)
                ->where('modal_price', '>', 0)
                ->whereHas('market', function ($m) {
                    $m->where('is_active', true)
                      ->whereNotNull('latitude')
                      ->whereNotNull('longitude');
                })
                ->orderBy('price_date', 'desc')
                ->orderBy('modal_price', 'desc')
                ->get();
        }

        // 3. If fewer than 2 markets across Karnataka, expand search to recent historical prices
        if ($allPrices->count() < 2) {
            $historicalPrices = MarketPrice::karnataka()
                ->with(['market.district', 'market.taluk', 'variety'])
                ->where('crop_id', $cropId)
                ->where('modal_price', '>', 0)
                ->whereHas('market', function ($m) {
                    $m->where('is_active', true)
                      ->whereNotNull('latitude')
                      ->whereNotNull('longitude');
                })
                ->orderBy('price_date', 'desc')
                ->orderBy('modal_price', 'desc')
                ->take(30)
                ->get();

            $allPrices = $allPrices->concat($historicalPrices);
        }

        // 4. Group by market_id and select the latest trading record per market
        $candidatePrices = $allPrices
            ->groupBy('market_id')
            ->map(fn($records) => $records->first())
            ->values();

        // 5. If a baseline market was specifically requested, ensure its latest trading record is present
        if ($baselineMarketId && !$candidatePrices->contains('market_id', $baselineMarketId)) {
            $baselineRecord = MarketPrice::karnataka()
                ->with(['market.district', 'market.taluk', 'variety'])
                ->where('crop_id', $cropId)
                ->where('market_id', $baselineMarketId)
                ->where('modal_price', '>', 0)
                ->when($varietyId, fn($q) => $q->where('variety_id', $varietyId))
                ->orderBy('price_date', 'desc')
                ->first();

            if (!$baselineRecord && $varietyId) {
                $baselineRecord = MarketPrice::karnataka()
                    ->with(['market.district', 'market.taluk', 'variety'])
                    ->where('crop_id', $cropId)
                    ->where('market_id', $baselineMarketId)
                    ->where('modal_price', '>', 0)
                    ->orderBy('price_date', 'desc')
                    ->first();
            }

            if ($baselineRecord) {
                $candidatePrices->push($baselineRecord);
            }
        }

        return $candidatePrices;
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
