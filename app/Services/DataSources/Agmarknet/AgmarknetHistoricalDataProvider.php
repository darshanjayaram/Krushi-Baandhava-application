<?php

namespace App\Services\DataSources\Agmarknet;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgmarknetHistoricalDataProvider extends BaseMarketDataProvider
{
    /**
     * Generate a new visual 6-letter CAPTCHA image and verification key.
     *
     * @return array{
     *     success: bool,
     *     captcha_key: string|null,
     *     captcha_image: string|null,
     *     error: string|null
     * }
     */
    public function generateCaptcha(): array
    {
        $baseUrl = rtrim($this->dataSource->base_url ?: 'https://api.agmarknet.gov.in/v1', '/');
        $verifySsl = config('services.http.verify_ssl', false);

        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => $verifySsl])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                ])
                ->post("{$baseUrl}/captcha/generator", []);

            if ($response->successful()) {
                $data = $response->json();
                $imageRaw = $data['captcha_image'] ?? '';
                $imageSrc = str_starts_with($imageRaw, 'data:image')
                    ? $imageRaw
                    : "data:image/png;base64,{$imageRaw}";

                return [
                    'success' => true,
                    'captcha_key' => $data['captcha_key'] ?? null,
                    'captcha_image' => $imageSrc,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'captcha_key' => null,
                'captcha_image' => null,
                'error' => "HTTP {$response->status()}: " . substr($response->body(), 0, 100),
            ];
        } catch (\Throwable $e) {
            Log::warning("AgmarknetHistoricalDataProvider: Captcha generation failed - " . $e->getMessage());
            return [
                'success' => false,
                'captcha_key' => null,
                'captcha_image' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify the user-entered CAPTCHA string.
     */
    public function verifyCaptcha(string $captchaKey, string $captchaCode): array
    {
        $baseUrl = rtrim($this->dataSource->base_url ?: 'https://api.agmarknet.gov.in/v1', '/');
        $verifySsl = config('services.http.verify_ssl', false);

        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => $verifySsl])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/captcha/verify", [
                    'captcha' => trim($captchaCode),
                    'captcha_key' => trim($captchaKey),
                ]);

            $body = $response->json();
            $isSuccess = $response->successful() && ($body['status'] ?? '') === 'success';

            return [
                'success' => $isSuccess,
                'message' => $body['message'] ?? ($isSuccess ? 'Verified' : 'Invalid CAPTCHA'),
                'token' => $body['token'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'token' => null,
            ];
        }
    }

    /**
     * Fetch historical market records with optional CAPTCHA verification or official token.
     * When captcha is not provided or official API is unreachable, provides intelligent fallback
     * to official data.gov.in mandi dataset.
     */
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords($filters);
        }

        $baseUrl = rtrim($this->dataSource->base_url ?: 'https://api.agmarknet.gov.in/v1', '/');
        $verifySsl = config('services.http.verify_ssl', false);

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->apiKey)) {
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        $captchaKey = $filters['captcha_key'] ?? null;
        $captchaVal = $filters['captcha_value'] ?? ($filters['captcha_code'] ?? null);

        // Resolve Agmarknet commodity_id if local crop_id or commodity was supplied
        $commodityId = $filters['commodity_id'] ?? null;
        if (!$commodityId) {
            $cropName = null;
            if (!empty($filters['crop_id'])) {
                $mapping = \App\Models\CropSourceMapping::where('data_source_id', $this->dataSource->id)
                    ->where('crop_id', $filters['crop_id'])
                    ->whereRaw('source_crop_name REGEXP "^[0-9]+$"')
                    ->first();
                if ($mapping && is_numeric($mapping->source_crop_name)) {
                    $commodityId = (int) $mapping->source_crop_name;
                } else {
                    $crop = \App\Models\Crop::find($filters['crop_id']);
                    $cropName = $crop?->name;
                }
            } elseif (!empty($filters['commodity'])) {
                $cropName = (string) $filters['commodity'];
            } elseif (!empty($filters['commodity_name'])) {
                $cropName = (string) $filters['commodity_name'];
            }

            if (!$commodityId && $cropName) {
                $officialMap = [
                    'Arecanut' => 118,
                    'Coconut' => 116,
                    'Copra' => 111,
                    'Tender Coconut' => 161,
                    'Coffee' => 41,
                    'Black Pepper' => 34,
                    'Ginger' => 87,
                    'Paddy' => 2,
                    'Ragi' => 30,
                    'Maize' => 4,
                    'Onion' => 23,
                    'Tomato' => 65,
                    'Jowar' => 5,
                    'Green Chilli' => 73,
                    'Banana' => 19,
                    'Sunflower' => 14,
                    'Cotton' => 15,
                    'Rice' => 3,
                    'Garlic' => 25,
                    'Dry Chillies' => 113,
                    'Cashewnut' => 33,
                    'Groundnut' => 10,
                ];
                $commodityId = $officialMap[$cropName] ?? null;
            }
        }

        // Clean and normalize from_date and to_date
        $rawFrom = $filters['from_date'] ?? ($filters['date'] ?? null);
        $rawTo = $filters['to_date'] ?? ($filters['date'] ?? null);

        $fromDate = $this->parseFilterDate($rawFrom) ?: Carbon::now()->subYears(3)->format('Y-m-d');
        $toDate = $this->parseFilterDate($rawTo) ?: Carbon::now()->format('Y-m-d');

        // 1. PRIMARY STRATEGY: If commodity is resolved, use official Date-Wise Specific Commodity endpoint
        // This endpoint returns ALL Karnataka APMC mandis and dates across the requested months without requiring CAPTCHA!
        $karnatakaStateId = 16; // Agmarknet official ID for Karnataka State

        if ($commodityId) {
            $dateWiseRecords = $this->fetchDateWiseSpecificCommodity(
                (int) $commodityId,
                $fromDate,
                $toDate,
                $karnatakaStateId
            );

            if (!empty($dateWiseRecords)) {
                Log::info("AgmarknetHistoricalDataProvider: Fetched " . count($dateWiseRecords) . " records for Commodity {$commodityId} ({$fromDate} to {$toDate}) via official date-wise API.");
                return $dateWiseRecords;
            }
        }

        // 1.1 If single date and all commodities (no specific crop selected), use official Daily Report State endpoint
        if (!$commodityId && $fromDate === $toDate) {
            $dailyRecords = $this->fetchDailyReportAllCommodities($fromDate, $karnatakaStateId);
            if (!empty($dailyRecords)) {
                Log::info("AgmarknetHistoricalDataProvider: Fetched " . count($dailyRecords) . " records for date {$fromDate} across all commodities via official daily report API.");
                return $dailyRecords;
            }
        }

        $payload = array_filter([
            'state_id' => $karnatakaStateId, // Strictly Karnataka
            'commodity_id' => $commodityId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'captcha_key' => $captchaKey,
            'captcha' => $captchaVal,
        ], fn ($v) => $v !== null && $v !== '');

        // 2. SECONDARY STRATEGY: Official Agmarknet report API if a captcha or api key is present
        if (!empty($captchaKey) && !empty($captchaVal)) {
            try {
                $response = Http::timeout($this->dataSource->timeout_seconds ?? 45)
                    ->withOptions(['verify' => $verifySsl])
                    ->withHeaders($headers)
                    ->post("{$baseUrl}/daily-price-arrival/report", $payload);

                $data = $response->json();

                if ($response->successful()) {
                    $records = $this->extractRecordsFromResponse($data);
                    if (!empty($records)) {
                        return $records;
                    }
                    if (($data['message'] ?? '') === 'No data available') {
                        Log::info("AgmarknetHistoricalDataProvider: Official Agmarknet reports no data available for {$fromDate} to {$toDate}.");
                        return [];
                    }
                }

                if ($response->status() === 400 && !empty($data['detail'])) {
                    Log::warning("AgmarknetHistoricalDataProvider: Official API validation error: " . $data['detail']);
                    throw new \RuntimeException($data['detail']);
                }

                Log::warning("AgmarknetHistoricalDataProvider: Report query response: " . substr($response->body(), 0, 150));
            } catch (\Throwable $e) {
                Log::error("AgmarknetHistoricalDataProvider: Fetch error - " . $e->getMessage());
                if ($e instanceof \RuntimeException) {
                    throw $e;
                }
            }
        } else {
            Log::info("AgmarknetHistoricalDataProvider: Captcha not supplied in headless mode. Using fallback feed.");
        }

        // Intelligent Fallback: Pull from official data.gov.in Mandi Prices provider (API-key authenticated)
        return $this->fallbackFetch($filters);
    }

    /**
     * Fetch date-wise prices for a specific commodity from official Agmarknet API (no CAPTCHA required).
     */
    protected function fetchDateWiseSpecificCommodity(int $commodityId, string $fromDate, string $toDate, int $stateId = 16): array
    {
        $startDate = Carbon::parse($fromDate)->startOfDay();
        $endDate = Carbon::parse($toDate)->endOfDay();
        if ($startDate->gt($endDate)) {
            $temp = $startDate;
            $startDate = $endDate->copy()->startOfDay();
            $endDate = $temp->copy()->endOfDay();
        }

        // Map commodityId to commodity name
        $idToNameMap = [
            118 => 'Arecanut(Betelnut/Supari)',
            116 => 'Coconut',
            111 => 'Copra',
            161 => 'Tender Coconut',
            41  => 'Coffee',
            34  => 'Black Pepper',
            87  => 'Ginger(Green)',
            2   => 'Paddy(Dhan)(Common)',
            30  => 'Ragi (Finger Millet)',
            4   => 'Maize',
            23  => 'Onion',
            65  => 'Tomato',
            5   => 'Jowar(Sorghum)',
            73  => 'Green Chilli',
            19  => 'Banana',
            14  => 'Sunflower',
            15  => 'Cotton',
            3   => 'Rice',
            25  => 'Garlic',
            113 => 'Dry Chillies',
            33  => 'Cashewnuts',
            10  => 'Groundnut',
        ];
        $commodityName = $idToNameMap[$commodityId] ?? 'Arecanut(Betelnut/Supari)';

        $cursor = $startDate->copy()->startOfMonth();
        $endMonth = $endDate->copy()->startOfMonth();

        $allRecords = [];

        while ($cursor->lte($endMonth)) {
            $year = $cursor->year;
            $month = $cursor->format('m');

            try {
                $response = Http::timeout(35)
                    ->withOptions(['verify' => false])
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/json',
                    ])
                    ->get('https://api.agmarknet.gov.in/v1/prices-and-arrivals/date-wise/specific-commodity', [
                        'year' => $year,
                        'month' => $month,
                        'stateId' => $stateId,
                        'commodityId' => $commodityId,
                        'includeExcel' => false,
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $markets = $json['markets'] ?? [];
                    foreach ($markets as $mkt) {
                        $marketName = $mkt['marketName'] ?? '';
                        foreach ($mkt['dates'] ?? [] as $dateEntry) {
                            $arrivalDateStr = $dateEntry['arrivalDate'] ?? '';
                            if (empty($arrivalDateStr)) {
                                continue;
                            }

                            $cleanDateStr = preg_replace('/\/+/', '/', trim($arrivalDateStr));
                            $parsedDate = null;
                            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $cleanDateStr, $m)) {
                                $parsedDate = Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
                            } else {
                                try {
                                    $parsedDate = Carbon::parse($cleanDateStr)->startOfDay();
                                } catch (\Throwable) {
                                    $parsedDate = null;
                                }
                            }

                            if ($parsedDate && ($parsedDate->lt($startDate) || $parsedDate->gt($endDate))) {
                                continue;
                            }

                            foreach ($dateEntry['data'] ?? [] as $row) {
                                $allRecords[] = [
                                    'Commodity' => $commodityName,
                                    'Market' => $marketName,
                                    'Variety' => $row['variety'] ?? 'Local',
                                    'Grade' => $row['grade'] ?? ($row['Grade'] ?? 'Local'),
                                    'Min_Price' => $row['minimumPrice'] ?? 0,
                                    'Max_Price' => $row['maximumPrice'] ?? 0,
                                    'Modal_Price' => $row['modalPrice'] ?? 0,
                                    'Arrival_Date' => $arrivalDateStr,
                                    'Arrival_Quantity' => $row['arrivals'] ?? 0,
                                ];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("AgmarknetHistoricalDataProvider: Month {$year}-{$month} fetch error: " . $e->getMessage());
            }

            $cursor->addMonth();
        }

        return $allRecords;
    }

    /**
     * Fetch daily report across all commodities for a single date (no CAPTCHA required).
     */
    protected function fetchDailyReportAllCommodities(string $targetDate, int $stateId = 16): array
    {
        try {
            $formattedDate = Carbon::parse($targetDate)->format('Y-m-d');
            $response = Http::timeout(35)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])
                ->get('https://api.agmarknet.gov.in/v1/prices-and-arrivals/commodity-market/daily-report-state', [
                    'date' => $formattedDate,
                    'state' => $stateId,
                    'includeExcel' => false,
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $groups = $json['commodityGroups'] ?? [];
                $records = [];
                $displayDate = Carbon::parse($targetDate)->format('d/m/Y');

                foreach ($groups as $group) {
                    $commodities = $group['commodities'] ?? [$group];
                    foreach ($commodities as $commodity) {
                        $commodityName = $commodity['commodityName'] ?? '';
                        foreach ($commodity['markets'] ?? [] as $mkt) {
                            $marketName = $mkt['marketCenter'] ?? '';
                            foreach ($mkt['data'] ?? [] as $row) {
                                $records[] = [
                                    'Commodity' => $commodityName,
                                    'Market' => $marketName,
                                    'Variety' => $row['variety'] ?? 'Local',
                                    'Grade' => $row['grade'] ?? ($row['Grade'] ?? 'Local'),
                                    'Min_Price' => $row['minimumPrice'] ?? 0,
                                    'Max_Price' => $row['maximumPrice'] ?? 0,
                                    'Modal_Price' => $row['modalPrice'] ?? 0,
                                    'Arrival_Date' => $displayDate,
                                    'Arrival_Quantity' => $row['arrivals'] ?? 0,
                                ];
                            }
                        }
                    }
                }

                return $records;
            }
        } catch (\Throwable $e) {
            Log::warning("AgmarknetHistoricalDataProvider: Daily report fetch error for {$targetDate}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Robust parser for DD/MM/YYYY, DD-MM-YYYY, or YYYY-MM-DD date filter strings.
     */
    protected function parseFilterDate(?string $dateStr): ?string
    {
        if (!$dateStr) {
            return null;
        }
        $clean = trim($dateStr);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $clean)) {
            return $clean;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $clean, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }
        try {
            return Carbon::parse($clean)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Fallback fetch to CEDA Agmarknet, KRAMA, or data.gov.in official Mandi dataset.
     */
    protected function fallbackFetch(array $filters): array
    {
        // 1. Try CEDA Agmarknet if available (official Agmarknet historical dataset)
        try {
            $cedaSource = \App\Models\DataSource::where('code', 'ceda_agmarknet')->where('is_active', true)->first();
            if ($cedaSource) {
                Log::info("AgmarknetHistoricalDataProvider: Cascading fallback to CEDA Agmarknet.");
                $cedaProvider = \App\Services\DataSources\DataSourceRegistry::make($cedaSource);
                $records = iterator_to_array($cedaProvider->fetch($filters));
                if (!empty($records)) {
                    return $records;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("AgmarknetHistoricalDataProvider: CEDA fallback exception: " . $e->getMessage());
        }

        // 2. Try KRAMA (Karnataka APMC official auction reports with multi-day coverage)
        try {
            $kramaSource = \App\Models\DataSource::where('code', 'krama_karnataka')->where('is_active', true)->first();
            if ($kramaSource) {
                Log::info("AgmarknetHistoricalDataProvider: Cascading fallback to KRAMA Karnataka.");
                $kramaProvider = \App\Services\DataSources\DataSourceRegistry::make($kramaSource);
                $records = iterator_to_array($kramaProvider->fetch($filters));
                if (!empty($records)) {
                    return $records;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("AgmarknetHistoricalDataProvider: KRAMA fallback exception: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Standardize Agmarknet record for Krushi Baandhava historical dataset.
     */
    public function normalize(array $record): ?array
    {
        $crop = $this->applyTransformation($record['Commodity'] ?? ($record['cmdt_name'] ?? ($record['commodity_name'] ?? ($record['crop'] ?? ($record['commodity'] ?? null)))), 'trim');
        $market = $this->applyTransformation($record['Market'] ?? ($record['market_name'] ?? ($record['market'] ?? null)), 'trim');

        if (empty($crop) || empty($market)) {
            return null;
        }

        // Strict state boundary: Discard any records from other states
        $state = $this->applyTransformation($record['State'] ?? ($record['state_name'] ?? ($record['state'] ?? null)), 'trim');
        if (!empty($state) && stripos($state, 'Karnataka') === false && stripos($state, 'KA') === false) {
            return null;
        }

        $minPrice = (float) $this->applyTransformation($record['Min_Price'] ?? ($record['min_price'] ?? ($record['min'] ?? 0)), 'to_number');
        $maxPrice = (float) $this->applyTransformation($record['Max_Price'] ?? ($record['max_price'] ?? ($record['max'] ?? 0)), 'to_number');
        $modalPrice = (float) $this->applyTransformation($record['Modal_Price'] ?? ($record['modal_price'] ?? ($record['modal'] ?? 0)), 'to_number');

        if ($modalPrice <= 0 && $minPrice <= 0 && $maxPrice <= 0) {
            return null;
        }

        if ($modalPrice <= 0) {
            $modalPrice = ($minPrice + $maxPrice) / 2;
        }

        $rawDate = $record['Arrival_Date'] ?? ($record['arrival_date'] ?? ($record['price_date'] ?? ($record['date'] ?? null)));
        $date = null;
        if (!empty($rawDate)) {
            $date = $this->applyTransformation($rawDate, 'date_format:d/m/Y');
            if (empty($date)) {
                try {
                    $date = Carbon::parse($rawDate)->format('Y-m-d');
                } catch (\Throwable) {
                    $date = null;
                }
            }
        }
        if (empty($date)) {
            $date = Carbon::today()->format('Y-m-d');
        }

        return [
            'source_crop' => $crop,
            'source_variety' => $this->applyTransformation($record['Variety'] ?? ($record['variety_name'] ?? ($record['variety'] ?? 'Local')), 'trim') ?: 'Local',
            'source_grade' => $this->applyTransformation($record['Grade'] ?? ($record['grade_name'] ?? ($record['grade'] ?? 'Local')), 'trim') ?: 'Local',
            'source_market' => $market,
            'source_district' => $this->applyTransformation($record['District'] ?? ($record['district_name'] ?? ($record['district'] ?? null)), 'trim'),
            'price_date' => $date,
            'min_price' => round($minPrice, 2),
            'max_price' => round($maxPrice, 2),
            'modal_price' => round($modalPrice, 2),
            'arrival_quantity' => (float) $this->applyTransformation($record['Arrival_Quantity'] ?? ($record['arrival_quantity'] ?? ($record['arrivals'] ?? 0)), 'to_number'),
            'unit' => 'Quintal',
            'source' => 'agmarknet_historical',
            'raw_payload' => $record,
        ];
    }

    /**
     * Health check verifying connectivity to Agmarknet 2.0 API.
     */
    public function healthCheck(): array
    {
        if ($this->isMockMode()) {
            return [
                'http_status' => 200,
                'response_time_ms' => 50,
                'auth_result' => 'success (mock mode)',
                'records_found' => 3,
                'detected_fields' => ['Commodity', 'Market', 'Variety', 'Modal_Price', 'Arrival_Date'],
                'status' => 'healthy',
                'error_message' => null,
                'sample_payload' => $this->getMockRecords()[0] ?? null,
            ];
        }

        $baseUrl = rtrim($this->dataSource->base_url ?: 'https://api.agmarknet.gov.in/v1', '/');
        $startTime = microtime(true);
        $verifySsl = config('services.http.verify_ssl', false);

        try {
            // Check filters endpoint (publicly accessible health check)
            $response = Http::timeout(10)
                ->withOptions(['verify' => $verifySsl])
                ->get("{$baseUrl}/daily-price-arrival/filters");

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $isHealthy = $response->successful();

            return [
                'http_status' => $response->status(),
                'response_time_ms' => $responseTimeMs,
                'auth_result' => $isHealthy ? 'success (public gateway)' : 'failed',
                'records_found' => $isHealthy ? 1 : 0,
                'detected_fields' => ['cmdt_data', 'state_data', 'market_data', 'variety_data'],
                'status' => $isHealthy ? 'healthy' : 'unhealthy',
                'error_message' => $isHealthy ? null : 'Failed to reach Agmarknet 2.0 filters API',
                'sample_payload' => null,
            ];
        } catch (\Throwable $e) {
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            return [
                'http_status' => null,
                'response_time_ms' => $responseTimeMs,
                'auth_result' => 'connection_error',
                'records_found' => 0,
                'detected_fields' => [],
                'status' => 'unhealthy',
                'error_message' => $e->getMessage(),
                'sample_payload' => null,
            ];
        }
    }

    /**
     * Mock records for testing.
     */
    protected function getMockRecords(array $filters = []): array
    {
        $today = Carbon::today()->format('d/m/Y');
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? ($filters['date'] ?? null);

        $templates = [
            [
                'Commodity' => 'Tomato',
                'Market' => 'Kolar',
                'Variety' => 'Hybrid',
                'Grade' => 'FAQ',
                'Min_Price' => 1200,
                'Max_Price' => 2200,
                'Modal_Price' => 1700,
                'Arrival_Quantity' => 1800,
            ],
            [
                'Commodity' => 'Arecanut',
                'Market' => 'Shivamogga',
                'Variety' => 'Rashi',
                'Grade' => 'FAQ',
                'Min_Price' => 42000,
                'Max_Price' => 49000,
                'Modal_Price' => 46000,
                'Arrival_Quantity' => 450,
            ],
            [
                'Commodity' => 'Paddy',
                'Market' => 'Raichur',
                'Variety' => 'Sona Masuri',
                'Grade' => 'FAQ',
                'Min_Price' => 2900,
                'Max_Price' => 3800,
                'Modal_Price' => 3200,
                'Arrival_Quantity' => 8500,
            ],
        ];

        if ($fromDate && $toDate && $fromDate !== $toDate) {
            $startDate = Carbon::parse($fromDate);
            $endDate = Carbon::parse($toDate);
            if ($startDate->gt($endDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
            if ($startDate->diffInDays($endDate) > 90) {
                $startDate = $endDate->copy()->subDays(90);
            }

            $records = [];
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                if (!$cursor->isSunday()) {
                    $dStr = $cursor->format('d/m/Y');
                    foreach ($templates as $tmpl) {
                        $row = $tmpl;
                        $row['Arrival_Date'] = $dStr;
                        $row['arrival_date'] = $dStr;
                        $records[] = $row;
                    }
                }
                $cursor->addDay();
            }
            return $records;
        }

        $records = [];
        $dStr = $toDate ? Carbon::parse($toDate)->format('d/m/Y') : $today;
        foreach ($templates as $tmpl) {
            $row = $tmpl;
            $row['Arrival_Date'] = $dStr;
            $row['arrival_date'] = $dStr;
            $records[] = $row;
        }
        return $records;
    }

    /**
     * Unpack records from Agmarknet API response envelopes.
     * Supports nested envelopes like { "data": [ ... ], "pagination": [ ... ] }
     * and { "status": true, "data": { "data": [ ... ], "pagination": [ ... ] } }.
     */
    protected function extractRecordsFromResponse(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        // 1. Direct nested subkey $data['data']['data']
        if (isset($data['data']['data']) && is_array($data['data']['data'])) {
            return array_values($data['data']['data']);
        }

        // 2. Direct nested subkey $data['data']['records']
        if (isset($data['data']['records']) && is_array($data['data']['records'])) {
            return array_values($data['data']['records']);
        }

        // 3. Check $data['data']
        if (isset($data['data']) && is_array($data['data'])) {
            if (array_is_list($data['data'])) {
                // If the first element is another wrapper
                if (isset($data['data'][0]['data']) && is_array($data['data'][0]['data'])) {
                    return array_values($data['data'][0]['data']);
                }
                return $data['data'];
            }
            if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                return array_values($data['data']['data']);
            }
        }

        // 4. Check $data['records']
        if (isset($data['records']) && is_array($data['records'])) {
            return array_values($data['records']);
        }

        // 5. If $data itself is a list
        if (array_is_list($data)) {
            if (isset($data[0]['data']) && is_array($data[0]['data'])) {
                return array_values($data[0]['data']);
            }
            return $data;
        }

        return [];
    }
}
