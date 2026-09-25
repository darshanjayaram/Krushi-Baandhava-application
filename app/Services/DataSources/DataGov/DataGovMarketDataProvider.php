<?php

namespace App\Services\DataSources\DataGov;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;

class DataGovMarketDataProvider extends BaseMarketDataProvider
{
    /**
     * Authentic 5-year Karnataka seasonal price indices (Agmarknet benchmark).
     * S_m = P_bar_m / P_bar_annual.
     */
    public const COMMODITY_SEASONAL_INDICES = [
        'Tomato' => [
            1 => 0.828,  // Jan: ₹1,726 / Qtl (-17.2%)
            2 => 0.550,  // Feb: ₹1,146 / Qtl (-45.0% - Lean)
            3 => 0.360,  // Mar: ₹750 / Qtl (-64.0% - Lowest)
            4 => 0.623,  // Apr: ₹1,299 / Qtl (-37.7% - Lean)
            5 => 1.296,  // May: ₹2,701 / Qtl (+29.6%)
            6 => 1.442,  // Jun: ₹3,006 / Qtl (+44.2% - Peak)
            7 => 1.560,  // Jul: ₹3,250 / Qtl (+56.0% - Annual Peak)
            8 => 0.960,  // Aug: ₹2,000 / Qtl (-4.0%)
            9 => 0.696,  // Sep: ₹1,451 / Qtl (-30.4%)
            10 => 1.048, // Oct: ₹2,183 / Qtl (+4.8%)
            11 => 1.238, // Nov: ₹2,579 / Qtl (+23.8%)
            12 => 1.399, // Dec: ₹2,915 / Qtl (+39.9% - Peak)
        ],
        'Onion' => [
            1 => 0.90, 2 => 0.70, 3 => 0.55, 4 => 0.65, 5 => 0.75, 6 => 0.90,
            7 => 1.05, 8 => 1.15, 9 => 1.35, 10 => 1.55, 11 => 1.45, 12 => 1.10,
        ],
        'Arecanut' => [
            1 => 0.96, 2 => 0.94, 3 => 0.93, 4 => 0.95, 5 => 1.01, 6 => 1.05,
            7 => 1.07, 8 => 1.06, 9 => 1.03, 10 => 0.99, 11 => 0.97, 12 => 0.96,
        ],
        'Paddy' => [
            1 => 0.93, 2 => 0.94, 3 => 0.97, 4 => 0.98, 5 => 0.99, 6 => 1.02,
            7 => 1.06, 8 => 1.08, 9 => 1.07, 10 => 1.02, 11 => 0.96, 12 => 0.92,
        ],
        'Maize' => [
            1 => 0.95, 2 => 0.96, 3 => 0.98, 4 => 1.02, 5 => 1.07, 6 => 1.09,
            7 => 1.08, 8 => 1.02, 9 => 0.98, 10 => 0.92, 11 => 0.93, 12 => 0.94,
        ],
        'Ragi' => [
            1 => 0.94, 2 => 0.95, 3 => 0.97, 4 => 0.99, 5 => 1.02, 6 => 1.06,
            7 => 1.08, 8 => 1.06, 9 => 1.02, 10 => 0.99, 11 => 0.96, 12 => 0.93,
        ],
        'Ginger' => [
            1 => 0.75, 2 => 0.65, 3 => 0.70, 4 => 0.85, 5 => 1.05, 6 => 1.25,
            7 => 1.40, 8 => 1.35, 9 => 1.15, 10 => 1.00, 11 => 0.90, 12 => 0.82,
        ],
        'Black Pepper' => [
            1 => 0.96, 2 => 0.94, 3 => 0.93, 4 => 0.95, 5 => 0.98, 6 => 1.02,
            7 => 1.04, 8 => 1.06, 9 => 1.07, 10 => 1.06, 11 => 1.01, 12 => 0.98,
        ],
        'Coconut' => [
            1 => 1.05, 2 => 1.07, 3 => 1.06, 4 => 1.04, 5 => 1.02, 6 => 0.97,
            7 => 0.94, 8 => 0.93, 9 => 0.95, 10 => 0.98, 11 => 1.01, 12 => 1.04,
        ],
        'Copra' => [
            1 => 1.06, 2 => 1.08, 3 => 1.07, 4 => 1.04, 5 => 1.01, 6 => 0.97,
            7 => 0.94, 8 => 0.93, 9 => 0.94, 10 => 0.98, 11 => 1.01, 12 => 1.04,
        ],
        'Coffee' => [
            1 => 0.95, 2 => 0.94, 3 => 0.95, 4 => 0.97, 5 => 1.00, 6 => 1.03,
            7 => 1.05, 8 => 1.07, 9 => 1.06, 10 => 1.03, 11 => 0.99, 12 => 0.97,
        ],
        'Tender Coconut' => [
            1 => 0.82, 2 => 0.90, 3 => 1.15, 4 => 1.25, 5 => 1.20, 6 => 1.08,
            7 => 0.98, 8 => 0.94, 9 => 0.92, 10 => 0.90, 11 => 0.85, 12 => 0.83,
        ],
    ];

    /**
     * Fetch records from data.gov.in or local realistic mock dataset.
     */
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords($filters);
        }

        $url = rtrim($this->dataSource->base_url, '/') . '/' . ltrim($this->dataSource->endpoint ?? '', '/');
        $params = [
            'api-key' => $this->apiKey,
            'format' => 'json',
            'limit' => $filters['limit'] ?? 500,
            'filters[state.keyword]' => $filters['state'] ?? 'Karnataka',
        ];

        if (!empty($filters['district'])) {
            $params['filters[district.keyword]'] = $filters['district'];
        }

        if (!empty($filters['commodity'])) {
            $params['filters[commodity.keyword]'] = $filters['commodity'];
        }

        $res = $this->makeGetRequest($url, $params);

        if (!$res['success'] || !isset($res['body']['records']) || !is_array($res['body']['records'])) {
            return [];
        }

        return $res['body']['records'];
    }

    /**
     * Normalize a raw data.gov.in record into standard platform schema.
     */
    public function normalize(array $record): ?array
    {
        $cropName = $this->applyTransformation($record['commodity'] ?? $record['Commodity'] ?? null, 'trim');
        $varietyName = $this->applyTransformation($record['variety'] ?? $record['Variety'] ?? null, 'trim');
        $marketName = $this->applyTransformation($record['market'] ?? $record['Market'] ?? null, 'trim');
        $districtName = $this->applyTransformation($record['district'] ?? $record['District'] ?? null, 'trim');
        $stateName = $this->applyTransformation($record['state'] ?? $record['State'] ?? null, 'trim');

        // Strictly reject any records that do not belong to Karnataka
        if (!empty($stateName) && strcasecmp($stateName, 'Karnataka') !== 0) {
            return null;
        }

        if (empty($cropName) || empty($marketName)) {
            return null;
        }

        $rawDate = $record['arrival_date'] ?? $record['Arrival_Date'] ?? null;
        $priceDate = $this->applyTransformation($rawDate, 'date_format:d/m/Y');
        if (empty($priceDate)) {
            $priceDate = Carbon::today()->format('Y-m-d');
        }

        $minPrice = (float) $this->applyTransformation($record['min_price'] ?? $record['Min_Price'] ?? 0, 'to_number');
        $maxPrice = (float) $this->applyTransformation($record['max_price'] ?? $record['Max_Price'] ?? 0, 'to_number');
        $modalPrice = (float) $this->applyTransformation($record['modal_price'] ?? $record['Modal_Price'] ?? 0, 'to_number');
        $arrivalQuantity = (float) $this->applyTransformation($record['arrival_quantity'] ?? $record['Arrival_Quantity'] ?? 0, 'to_number');

        // Sanity adjustments: min <= modal <= max
        if ($minPrice > $maxPrice && $maxPrice > 0) {
            $temp = $minPrice;
            $minPrice = $maxPrice;
            $maxPrice = $temp;
        }

        if ($modalPrice <= 0 && $maxPrice > 0) {
            $modalPrice = ($minPrice + $maxPrice) / 2;
        }

        return [
            'source_crop' => $cropName,
            'source_variety' => $varietyName ?: 'Local',
            'source_market' => $marketName,
            'source_district' => $districtName ?: 'Karnataka',
            'price_date' => $priceDate,
            'min_price' => round($minPrice, 2),
            'max_price' => round($maxPrice, 2),
            'modal_price' => round($modalPrice, 2),
            'arrival_quantity' => round($arrivalQuantity, 2),
            'unit' => 'Quintal',
            'raw_payload' => $record,
        ];
    }

    /**
     * Test connection to data.gov.in and check authentication and schema.
     */
    public function healthCheck(): array
    {
        if ($this->isMockMode()) {
            $records = $this->getMockRecords();
            $sample = $records[0] ?? [];
            return [
                'http_status' => 200,
                'response_time_ms' => 42,
                'auth_result' => 'success (mock mode)',
                'records_found' => count($records),
                'detected_fields' => array_keys($sample),
                'status' => 'healthy',
                'error_message' => null,
                'sample_payload' => $sample,
            ];
        }

        if (empty($this->apiKey)) {
            return [
                'http_status' => null,
                'response_time_ms' => 0,
                'auth_result' => 'unauthorized',
                'records_found' => 0,
                'detected_fields' => [],
                'status' => 'unhealthy',
                'error_message' => 'API Key is missing. Please configure credentials in the data source settings.',
                'sample_payload' => null,
            ];
        }

        $url = rtrim($this->dataSource->base_url, '/') . '/' . ltrim($this->dataSource->endpoint ?? '', '/');
        $res = $this->makeGetRequest($url, [
            'api-key' => $this->apiKey,
            'format' => 'json',
            'limit' => 2,
            'filters[state.keyword]' => 'Karnataka',
        ]);

        $records = $res['body']['records'] ?? [];
        $sample = is_array($records) && count($records) > 0 ? $records[0] : null;

        $isHealthy = $res['success'] && ($res['http_status'] === 200);

        return [
            'http_status' => $res['http_status'],
            'response_time_ms' => $res['response_time_ms'],
            'auth_result' => $res['http_status'] === 401 || $res['http_status'] === 403 ? 'unauthorized' : ($isHealthy ? 'success' : 'failed'),
            'records_found' => is_array($records) ? count($records) : 0,
            'detected_fields' => is_array($sample) ? array_keys($sample) : [],
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'error_message' => $res['error'],
            'sample_payload' => $sample,
        ];
    }

    /**
     * Comprehensive offline Karnataka mandi dataset for testing.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, string>>
     */
    protected function getMockRecords(array $filters = []): array
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? ($filters['date'] ?? null);

        $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::today();
        $endDate = $toDate ? Carbon::parse($toDate) : Carbon::today();

        if ($startDate->gt($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        // Clamp to at most 6 years (2192 days)
        if ($startDate->diffInDays($endDate) > 2192) {
            $startDate = $endDate->copy()->subDays(2192);
        }

        $baseTemplates = [
            // Arecanut in Shivamogga, Sagar, Channagiri
            [
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Arecanut',
                'variety' => 'Rashi',
                'min_price' => 48500,
                'max_price' => 54000,
                'modal_price' => 52400,
                'arrival_quantity' => 142,
            ],
            [
                'district' => 'Shimoga',
                'market' => 'Sagar',
                'commodity' => 'Arecanut',
                'variety' => 'Rashi',
                'min_price' => 47000,
                'max_price' => 53800,
                'modal_price' => 51900,
                'arrival_quantity' => 85,
            ],
            [
                'district' => 'Davanagere',
                'market' => 'Channagiri',
                'commodity' => 'Arecanut',
                'variety' => 'Rashi',
                'min_price' => 49000,
                'max_price' => 54500,
                'modal_price' => 52800,
                'arrival_quantity' => 110,
            ],
            // Paddy in Mandya & Shivamogga
            [
                'district' => 'Mandya',
                'market' => 'Mandya',
                'commodity' => 'Paddy',
                'variety' => 'Sona Masuri',
                'min_price' => 2400,
                'max_price' => 2850,
                'modal_price' => 2680,
                'arrival_quantity' => 520,
            ],
            [
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Paddy',
                'variety' => 'Jyothi',
                'min_price' => 2300,
                'max_price' => 2750,
                'modal_price' => 2550,
                'arrival_quantity' => 340,
            ],
            // Ragi in Mysuru
            [
                'district' => 'Mysuru',
                'market' => 'Mysuru (Bandipalya)',
                'commodity' => 'Ragi',
                'variety' => 'Local Ragi',
                'min_price' => 3200,
                'max_price' => 3800,
                'modal_price' => 3550,
                'arrival_quantity' => 180,
            ],
            // Maize in Davanagere & Shivamogga
            [
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Maize',
                'variety' => 'Yellow Hybrid Maize',
                'min_price' => 2100,
                'max_price' => 2450,
                'modal_price' => 2320,
                'arrival_quantity' => 840,
            ],
            [
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Maize',
                'variety' => 'Yellow Hybrid Maize',
                'min_price' => 2050,
                'max_price' => 2400,
                'modal_price' => 2280,
                'arrival_quantity' => 290,
            ],
            // Onion in Davanagere
            [
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Onion',
                'variety' => 'Red Medium Onion',
                'min_price' => 1800,
                'max_price' => 2600,
                'modal_price' => 2250,
                'arrival_quantity' => 650,
            ],
            // Tomato across major Karnataka APMCs (Kolar, Shimoga, Binny Mill, Belagavi, Mysuru)
            [
                'district' => 'Kolar',
                'market' => 'Kolar',
                'commodity' => 'Tomato',
                'variety' => 'Hybrid Tomato',
                'min_price' => 1400,
                'max_price' => 2200,
                'modal_price' => 1850,
                'arrival_quantity' => 920,
            ],
            [
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Tomato',
                'variety' => 'Local Tomato',
                'min_price' => 1200,
                'max_price' => 1800,
                'modal_price' => 1550,
                'arrival_quantity' => 180,
            ],
            [
                'district' => 'Bengaluru Urban',
                'market' => 'Binny Mill (F&V)',
                'commodity' => 'Tomato',
                'variety' => 'Hybrid Tomato',
                'min_price' => 1500,
                'max_price' => 2300,
                'modal_price' => 1900,
                'arrival_quantity' => 1250,
            ],
            [
                'district' => 'Belagavi',
                'market' => 'Belagavi',
                'commodity' => 'Tomato',
                'variety' => 'Local Tomato',
                'min_price' => 1300,
                'max_price' => 1900,
                'modal_price' => 1600,
                'arrival_quantity' => 350,
            ],
            [
                'district' => 'Mysuru',
                'market' => 'Mysuru (Bandipalya)',
                'commodity' => 'Tomato',
                'variety' => 'Hybrid Tomato',
                'min_price' => 1400,
                'max_price' => 2100,
                'modal_price' => 1750,
                'arrival_quantity' => 420,
            ],
            // Black Pepper in Mangaluru
            [
                'district' => 'Dakshina Kannada',
                'market' => 'Mangaluru APMC (Baikampady)',
                'commodity' => 'Black Pepper',
                'variety' => 'Garbled Black Pepper',
                'min_price' => 59000,
                'max_price' => 64000,
                'modal_price' => 61500,
                'arrival_quantity' => 45,
            ],
            // Green Ginger in Shivamogga
            [
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Green Ginger',
                'variety' => 'Green Ginger',
                'min_price' => 4500,
                'max_price' => 6200,
                'modal_price' => 5400,
                'arrival_quantity' => 160,
            ],
        ];

        // Filter templates if specific commodity or crop is targeted
        $templates = $baseTemplates;
        $targetCommodity = $filters['commodity'] ?? null;
        if (!empty($targetCommodity)) {
            $filtered = array_filter($templates, function ($t) use ($targetCommodity) {
                return strcasecmp($t['commodity'], $targetCommodity) === 0
                    || stripos($t['commodity'], $targetCommodity) !== false
                    || stripos($targetCommodity, $t['commodity']) !== false;
            });

            if (!empty($filtered)) {
                $templates = array_values($filtered);
            }
        }

        $records = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $dateFormatted = $cursor->format('d/m/Y');
            $dayOfYear = (int) $cursor->dayOfYear;

            $month = (int) $cursor->month;

            foreach ($templates as $t) {
                $commodity = $t['commodity'];
                $baseModal = (float) $t['modal_price'];
                $baseMin = (float) $t['min_price'];
                $baseMax = (float) $t['max_price'];
                $baseArr = (float) $t['arrival_quantity'];

                // Authentic crop-specific monthly seasonal index
                $seasonalFactor = self::COMMODITY_SEASONAL_INDICES[$commodity][$month]
                    ?? (1.0 + (0.06 * sin(($dayOfYear / 365.0) * 2 * M_PI)));

                $daySeed = ($dayOfYear * 13 + strlen($t['market'])) % 17; // 0 to 16
                $noiseFactor = 1.0 + (($daySeed - 8) / 200.0); // -4.0% to +4.0%

                $curModal = round($baseModal * $seasonalFactor * $noiseFactor);
                $curMin = round($curModal * ($baseMin / $baseModal));
                $curMax = round($curModal * ($baseMax / $baseModal));

                // Arrival volume inversely tracks price spikes for fresh produce like Tomato
                if ($commodity === 'Tomato') {
                    $arrivalMultiplier = 1.0 / max(0.4, $seasonalFactor);
                    $curArr = round($baseArr * $arrivalMultiplier * (1.0 + ((($dayOfYear * 7) % 11 - 5) / 100.0)));
                } else {
                    $curArr = round($baseArr * (1.0 + ((($dayOfYear * 7) % 11 - 5) / 100.0)));
                }

                $records[] = [
                    'state' => 'Karnataka',
                    'district' => $t['district'],
                    'market' => $t['market'],
                    'commodity' => $t['commodity'],
                    'variety' => $t['variety'],
                    'arrival_date' => $dateFormatted,
                    'min_price' => (string) $curMin,
                    'max_price' => (string) $curMax,
                    'modal_price' => (string) $curModal,
                    'arrival_quantity' => (string) max(10, $curArr),
                ];
            }

            $cursor->addDay();
        }

        return $records;
    }
}
