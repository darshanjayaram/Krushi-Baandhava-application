<?php

namespace App\Services\DataSources\Krama;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KramaMarketDataProvider extends BaseMarketDataProvider
{
    /**
     * Fetch daily APMC auction records directly from KRAMA (Karnataka State Agricultural Marketing Board).
     * Source: https://krama.karnataka.gov.in/reports/
     */
    /**
     * Fetch daily APMC auction records directly from KRAMA (Karnataka State Agricultural Marketing Board).
     * Supports both single date and multi-day historical backfill date ranges.
     * Source: https://krama.karnataka.gov.in/reports/
     */
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords($filters);
        }

        @set_time_limit(900);
        @ini_set('max_execution_time', '900');

        $baseUrl = rtrim($this->dataSource->base_url ?: 'https://krama.karnataka.gov.in', '/');
        $mainRepUrl = "{$baseUrl}/reports/Main_Rep";
        $commadityUrl = "{$baseUrl}/reports/Commadity";
        $timeout = max(45, (int) ($this->dataSource->timeout_seconds ?? 45));

        // Check if a multi-day date range is requested
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        if ($fromDate && $toDate && $fromDate !== $toDate) {
            $startDate = Carbon::parse($fromDate);
            $endDate = Carbon::parse($toDate);

            if ($startDate->gt($endDate)) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            // Max clamp 2192 days (~6 years)
            if ($startDate->diffInDays($endDate) > 2192) {
                $startDate = $endDate->copy()->subDays(2192);
            }

            $force = (bool) ($filters['force'] ?? false);

            // Query DB to see which dates in range already have full data (> 50 records)
            $existingDates = [];
            if (!$force) {
                try {
                    $existingDates = \App\Models\MarketPriceRaw::where('data_source_id', $this->dataSource->id)
                        ->whereBetween('price_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->selectRaw('DATE(price_date) as pdate, count(*) as cnt')
                        ->groupBy('pdate')
                        ->having('cnt', '>=', 50)
                        ->pluck('pdate')
                        ->map(fn ($d) => Carbon::parse($d)->toDateString())
                        ->flip()
                        ->all();
                } catch (\Throwable $e) {
                    $existingDates = [];
                }
            }

            $allRecords = [];
            $cursor = $endDate->copy();

            while ($cursor->gte($startDate)) {
                $cursorDateStr = $cursor->toDateString();

                // Karnataka APMC auctions typically do not operate on Sundays
                if ($cursor->isSunday()) {
                    $cursor->subDay();
                    continue;
                }

                // If already synced and not forcing re-sync, skip making HTTP request
                if (!$force && isset($existingDates[$cursorDateStr])) {
                    $cursor->subDay();
                    continue;
                }

                $kramaDate = $cursor->format('d/m/Y');
                try {
                    $dayRecords = $this->fetchSingleDate($kramaDate, $mainRepUrl, $commadityUrl, $timeout, $filters);
                    foreach ($dayRecords as $rec) {
                        $allRecords[] = $rec;
                    }
                } catch (\Throwable $e) {
                    Log::warning("KramaMarketDataProvider: Skip day {$kramaDate} due to error: " . $e->getMessage());
                }

                $cursor->subDay();
            }

            if (empty($allRecords)) {
                Log::info("KramaMarketDataProvider: KRAMA returned 0 records across range. Triggering AGMARKNET failover.");
                return $this->fallbackToAgmarknet($filters, $fromDate);
            }

            return $allRecords;
        }

        // Single date query
        $dateStr = isset($filters['date'])
            ? Carbon::parse($filters['date'])->format('d/m/Y')
            : (isset($filters['to_date']) ? Carbon::parse($filters['to_date'])->format('d/m/Y') : Carbon::today()->format('d/m/Y'));

        $records = $this->fetchSingleDate($dateStr, $mainRepUrl, $commadityUrl, $timeout, $filters);

        // Failover Strategy: If KRAMA returned 0 records or had an issue, fallback to Official AGMARKNET
        if (empty($records)) {
            Log::info("KramaMarketDataProvider: KRAMA returned 0 records for {$dateStr}. Falling back to Official AGMARKNET.");
            $records = $this->fallbackToAgmarknet($filters, $dateStr);
        }

        return $records;
    }

    /**
     * Fetch APMC records for a single specific auction date from KRAMA.
     */
    public function fetchSingleDate(string $dateStr, string $mainRepUrl, string $commadityUrl, int $timeout = 45, array $filters = []): array
    {
        $cookieJar = new CookieJar();
        $verifySsl = config('services.http.verify_ssl', false);

        $client = Http::timeout($timeout)
            ->withOptions([
                'verify' => $verifySsl,
                'cookies' => $cookieJar,
            ])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]);

        try {
            // Step 1: Initial GET to acquire ASP.NET ViewState & Session Cookies
            $getRes = $client->get($mainRepUrl);
            if (!$getRes->successful()) {
                Log::warning("KramaMarketDataProvider: Step 1 GET failed for {$dateStr} with status {$getRes->status()}");
                return [];
            }

            $dom1 = new DOMDocument();
            @$dom1->loadHTML($getRes->body());
            $xp1 = new DOMXPath($dom1);

            $vs1 = $xp1->query('//input[@id="__VIEWSTATE"]')->item(0)?->getAttribute('value') ?? '';
            $vsg1 = $xp1->query('//input[@id="__VIEWSTATEGENERATOR"]')->item(0)?->getAttribute('value') ?? '';
            $ev1 = $xp1->query('//input[@id="__EVENTVALIDATION"]')->item(0)?->getAttribute('value') ?? '';

            if (empty($vs1)) {
                Log::warning("KramaMarketDataProvider: Failed to extract __VIEWSTATE from {$mainRepUrl} for {$dateStr}");
                return [];
            }

            // Step 2: Post Commoditywise Daily Report selection ('C')
            $step1Data = [
                '__EVENTTARGET' => '',
                '__EVENTARGUMENT' => '',
                '__LASTFOCUS' => '',
                '__VIEWSTATE' => $vs1,
                '__VIEWSTATEGENERATOR' => $vsg1,
                '__VIEWSTATEENCRYPTED' => '',
                '__EVENTVALIDATION' => $ev1,
                '_ctl0:txtSiteSearch' => '',
                '_ctl0:MainContent:TxtDate' => $dateStr,
                '_ctl0:MainContent:RadBtnSel' => 'C',
                '_ctl0:MainContent:BtnRep' => 'View Report',
            ];

            $res1 = $client->asForm()->post($mainRepUrl, $step1Data);
            if (!$res1->successful()) {
                Log::warning("KramaMarketDataProvider: Step 2 POST failed for {$dateStr} with status {$res1->status()}");
                return [];
            }

            $dom2 = new DOMDocument();
            @$dom2->loadHTML($res1->body());
            $xp2 = new DOMXPath($dom2);

            $vs2 = $xp2->query('//input[@id="__VIEWSTATE"]')->item(0)?->getAttribute('value') ?? '';
            $vsg2 = $xp2->query('//input[@id="__VIEWSTATEGENERATOR"]')->item(0)?->getAttribute('value') ?? '';
            $ev2 = $xp2->query('//input[@id="__EVENTVALIDATION"]')->item(0)?->getAttribute('value') ?? '';

            // Step 3: Select commodity checkboxes (respect enabled crops filter if provided)
            $checkboxes = $xp2->query('//input[@type="checkbox"]');
            if ($checkboxes->length === 0) {
                Log::warning("KramaMarketDataProvider: No commodity checkboxes found on {$commadityUrl} for {$dateStr}");
                return [];
            }

            $allowedCommodities = null;
            if (!empty($filters['enabled_crop_names']) && is_array($filters['enabled_crop_names'])) {
                $allowedCommodities = [];
                foreach ($filters['enabled_crop_names'] as $name) {
                    $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $name)));
                    if (!empty($clean)) {
                        $allowedCommodities[$clean] = true;
                    }
                }
            }

            $step2Data = [
                '__EVENTTARGET' => '',
                '__EVENTARGUMENT' => '',
                '__LASTFOCUS' => '',
                '__VIEWSTATE' => $vs2,
                '__VIEWSTATEGENERATOR' => $vsg2,
                '__VIEWSTATEENCRYPTED' => '',
                '__EVENTVALIDATION' => $ev2,
                '_ctl0:txtSiteSearch' => '',
                '_ctl0:MainContent:BtnRep' => 'View Report',
            ];

            $matchedCount = 0;
            foreach ($checkboxes as $cb) {
                $name = $cb->getAttribute('name');
                if (!$name) {
                    continue;
                }

                if ($allowedCommodities !== null) {
                    $id = $cb->getAttribute('id');
                    $label = '';
                    if ($id) {
                        $lblNodes = $xp2->query("//label[@for='{$id}']");
                        if ($lblNodes->length > 0) {
                            $label = trim($lblNodes->item(0)->textContent);
                        }
                    }
                    if (empty($label)) {
                        $label = trim($cb->parentNode?->textContent ?? '');
                    }

                    $cleanLabel = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $label)));
                    if (!empty($cleanLabel) && !isset($allowedCommodities[$cleanLabel])) {
                        $isMatch = false;
                        foreach (array_keys($allowedCommodities) as $allowedKey) {
                            if (str_contains($cleanLabel, $allowedKey) || str_contains($allowedKey, $cleanLabel)) {
                                $isMatch = true;
                                break;
                            }
                        }
                        if (!$isMatch) {
                            continue; // Skip unconfigured commodity
                        }
                    }
                }

                $step2Data[$name] = 'on';
                $matchedCount++;
            }

            // Fallback: If no checkbox matched (e.g. dynamic layout changes), select all as safe fallback
            if ($matchedCount === 0) {
                foreach ($checkboxes as $cb) {
                    $name = $cb->getAttribute('name');
                    if ($name) {
                        $step2Data[$name] = 'on';
                    }
                }
            }

            // Step 4: Submit to Commadity to generate the report
            $res2 = $client->asForm()->post($commadityUrl, $step2Data);
            if (!$res2->successful() || empty($res2->body())) {
                Log::warning("KramaMarketDataProvider: Step 4 Report POST failed for {$dateStr} with status {$res2->status()}");
                return [];
            }

            return $this->parseReportHtml($res2->body(), $dateStr, $allowedCommodities);
        } catch (\Throwable $e) {
            Log::error("KramaMarketDataProvider: Fetch exception for {$dateStr} - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse KRAMA HTML report containing commodity headings and tables.
     */
    public function parseReportHtml(string $html, string $dateStr, ?array $allowedCommodities = null): array
    {
        $records = [];
        $carbonDate = Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $spans = $xpath->query('//span[contains(text(), "COMMODITY:")]');

        foreach ($spans as $span) {
            $rawCrop = trim(str_ireplace('COMMODITY:', '', $span->textContent));
            if (empty($rawCrop)) {
                continue;
            }

            if ($allowedCommodities !== null) {
                $cleanCrop = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $rawCrop)));
                if (!isset($allowedCommodities[$cleanCrop])) {
                    $isMatch = false;
                    foreach (array_keys($allowedCommodities) as $allowedKey) {
                        if (str_contains($cleanCrop, $allowedKey) || str_contains($allowedKey, $cleanCrop)) {
                            $isMatch = true;
                            break;
                        }
                    }
                    if (!$isMatch) {
                        continue; // Skip unconfigured commodity section
                    }
                }
            }

            // Find the sibling or nested table
            $next = $span->nextSibling;
            $table = null;
            while ($next) {
                if ($next->nodeName === 'table') {
                    $table = $next;
                    break;
                }
                if ($next->nodeName === 'div') {
                    $innerTables = $next->getElementsByTagName('table');
                    if ($innerTables->length > 0) {
                        $table = $innerTables->item(0);
                        break;
                    }
                }
                $next = $next->nextSibling;
            }

            if (!$table) {
                continue;
            }

            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $row) {
                $cells = [];
                foreach ($row->getElementsByTagName('td') as $td) {
                    $cells[] = trim(preg_replace('/\s+/', ' ', $td->textContent));
                }

                // Check minimum expected columns: Market, Variety, Grade, Arrivals, Units, Min, Max, Modal
                if (count($cells) < 8 || stripos($cells[0], 'No Data Found') !== false) {
                    continue;
                }

                $modalPrice = (float) str_replace(',', '', $cells[7]);
                $minPrice = (float) str_replace(',', '', $cells[5]);
                $maxPrice = (float) str_replace(',', '', $cells[6]);

                if ($modalPrice <= 0 && $minPrice <= 0 && $maxPrice <= 0) {
                    continue;
                }

                $records[] = [
                    'crop' => $rawCrop,
                    'market' => $cells[0],
                    'variety' => $cells[1],
                    'grade' => $cells[2],
                    'arrivals' => (float) str_replace(',', '', $cells[3]),
                    'units' => $cells[4],
                    'min' => $minPrice,
                    'max' => $maxPrice,
                    'modal' => $modalPrice > 0 ? $modalPrice : ($minPrice + $maxPrice) / 2,
                    'date' => $carbonDate,
                ];
            }
        }

        return $records;
    }

    /**
     * Failover: Fetch live APMC auction records from Official AGMARKNET when KRAMA is down or returns empty.
     */
    protected function fallbackToAgmarknet(array $filters, ?string $targetDate = null): array
    {
        if (!\App\Models\SystemSetting::get('krama_agmarknet_failover_enabled', true)) {
            \Illuminate\Support\Facades\Log::info("KramaMarketDataProvider: Automatic failover to AGMARKNET is turned OFF in Admin settings. Skipping failover.");
            return [];
        }

        try {
            $agmarknetSource = \App\Models\DataSource::where('code', 'agmarknet_official')
                ->where('is_active', true)
                ->first();

            if (!$agmarknetSource) {
                return [];
            }

            Log::info("KramaMarketDataProvider: Triggering automatic backup sync to Official AGMARKNET for {$targetDate}.");
            $provider = \App\Services\DataSources\DataSourceRegistry::make($agmarknetSource);
            $fallbackFilters = $filters;

            if ($targetDate && empty($fallbackFilters['date']) && empty($fallbackFilters['from_date'])) {
                $cleanedDate = str_replace('/', '-', $targetDate);
                $fallbackFilters['date'] = Carbon::parse($cleanedDate)->format('Y-m-d');
                $fallbackFilters['from_date'] = $fallbackFilters['date'];
                $fallbackFilters['to_date'] = $fallbackFilters['date'];
            }

            $records = iterator_to_array($provider->fetch($fallbackFilters));
            if (!empty($records)) {
                Log::info("KramaMarketDataProvider: Failover successful: " . count($records) . " records retrieved from Official AGMARKNET backup.");
                return $records;
            }
        } catch (\Throwable $e) {
            Log::warning("KramaMarketDataProvider: AGMARKNET failover failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Normalize raw KRAMA (or failover AGMARKNET) record to canonical format.
     */
    public function normalize(array $record): ?array
    {
        $rawCrop = $this->applyTransformation($record['crop'] ?? ($record['Commodity'] ?? ($record['cmdt_name'] ?? null)), 'trim');
        $rawMarket = $this->applyTransformation($record['market'] ?? ($record['Market'] ?? ($record['market_name'] ?? null)), 'trim');

        if (empty($rawCrop) || empty($rawMarket)) {
            return null;
        }

        $min = (float) ($record['min'] ?? ($record['Min_Price'] ?? ($record['min_price'] ?? 0)));
        $max = (float) ($record['max'] ?? ($record['Max_Price'] ?? ($record['max_price'] ?? 0)));
        $modal = (float) ($record['modal'] ?? ($record['Modal_Price'] ?? ($record['modal_price'] ?? 0)));

        if ($modal <= 0 && $min <= 0 && $max <= 0) {
            return null;
        }

        if ($modal <= 0) {
            $modal = ($min + $max) / 2;
        }

        $variety = $this->applyTransformation($record['variety'] ?? ($record['Variety'] ?? 'Local'), 'trim');
        $grade = $this->applyTransformation($record['grade'] ?? ($record['Grade'] ?? 'Local'), 'trim');

        $rawDate = $record['date'] ?? ($record['Arrival_Date'] ?? ($record['price_date'] ?? Carbon::today()->format('Y-m-d')));
        try {
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', trim($rawDate), $m)) {
                $priceDate = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
            } else {
                $priceDate = Carbon::parse($rawDate)->format('Y-m-d');
            }
        } catch (\Throwable) {
            $priceDate = Carbon::today()->format('Y-m-d');
        }

        $arrivals = (float) ($record['arrivals'] ?? ($record['Arrival_Quantity'] ?? 0.0));

        return [
            'source_crop' => $rawCrop,
            'source_variety' => $variety ?: 'Local',
            'source_grade' => $grade ?: 'Local',
            'source_market' => $rawMarket,
            'source_district' => $record['District'] ?? null,
            'price_date' => $priceDate,
            'min_price' => round($min, 2),
            'max_price' => round($max, 2),
            'modal_price' => round($modal, 2),
            'arrival_quantity' => $arrivals,
            'unit' => 'Quintal',
            'raw_payload' => $record,
        ];
    }

    /**
     * Health check verifying KRAMA portal accessibility.
     */
    public function healthCheck(): array
    {
        if ($this->isMockMode()) {
            return [
                'http_status' => 200,
                'response_time_ms' => 45,
                'auth_result' => 'success (mock mode)',
                'records_found' => count($this->getMockRecords()),
                'detected_fields' => ['crop', 'market', 'variety', 'grade', 'arrivals', 'min', 'max', 'modal'],
                'status' => 'healthy',
                'error_message' => null,
                'sample_payload' => $this->getMockRecords()[0] ?? null,
            ];
        }

        $baseUrl = rtrim($this->dataSource->base_url ?: 'https://krama.karnataka.gov.in', '/');
        $mainRepUrl = "{$baseUrl}/reports/Main_Rep";
        $startTime = microtime(true);
        $verifySsl = config('services.http.verify_ssl', false);

        try {
            $res = Http::timeout(15)
                ->withOptions(['verify' => $verifySsl])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($mainRepUrl);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $isHealthy = $res->successful() && str_contains($res->body(), '__VIEWSTATE');

            return [
                'http_status' => $res->status(),
                'response_time_ms' => $responseTimeMs,
                'auth_result' => $isHealthy ? 'success (public web portal)' : 'failed',
                'records_found' => $isHealthy ? 1 : 0,
                'detected_fields' => ['crop', 'market', 'variety', 'grade', 'arrivals', 'min', 'max', 'modal'],
                'status' => $isHealthy ? 'healthy' : 'unhealthy',
                'error_message' => $isHealthy ? null : 'Failed to retrieve KRAMA report form',
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
     * High-fidelity sample records for test coverage and mock mode.
     */
    protected function getMockRecords(array $filters = []): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? ($filters['date'] ?? null);

        $templates = [
            [
                'crop' => 'Arecanut',
                'market' => 'CHANNAGIRI',
                'variety' => 'Rashi',
                'grade' => 'Non FAQ',
                'arrivals' => 751.0,
                'units' => 'Quintal',
                'min' => 40000.0,
                'max' => 48099.0,
                'modal' => 45782.0,
            ],
            [
                'crop' => 'Arecanut',
                'market' => 'SHIKARIPUR',
                'variety' => 'Rashi',
                'grade' => 'FAQ',
                'arrivals' => 554.0,
                'units' => 'Quintal',
                'min' => 42300.0,
                'max' => 45500.0,
                'modal' => 43367.0,
            ],
            [
                'crop' => 'Arecanut',
                'market' => 'TIRTHAHALLI',
                'variety' => 'Gorabalu',
                'grade' => 'Average',
                'arrivals' => 294.0,
                'units' => 'Quintal',
                'min' => 26000.0,
                'max' => 29800.0,
                'modal' => 29800.0,
            ],
            [
                'crop' => 'Tomato',
                'market' => 'KOLAR',
                'variety' => 'Tomato',
                'grade' => 'Average',
                'arrivals' => 2500.0,
                'units' => 'Quintal',
                'min' => 400.0,
                'max' => 3000.0,
                'modal' => 1530.0,
            ],
            [
                'crop' => 'Tomato',
                'market' => 'SHIVAMOGGA',
                'variety' => 'Tomato',
                'grade' => 'Average',
                'arrivals' => 425.0,
                'units' => 'Quintal',
                'min' => 800.0,
                'max' => 1600.0,
                'modal' => 1200.0,
            ],
            [
                'crop' => 'Paddy',
                'market' => 'SINDHANUR',
                'variety' => 'Paddy RNR Old',
                'grade' => 'Average',
                'arrivals' => 28743.0,
                'units' => 'Quintal',
                'min' => 2800.0,
                'max' => 4490.0,
                'modal' => 4100.0,
            ],
            [
                'crop' => 'Paddy',
                'market' => 'RAICHUR',
                'variety' => 'Sona Mahsuri',
                'grade' => 'FAQ',
                'arrivals' => 225.0,
                'units' => 'Quintal',
                'min' => 2889.0,
                'max' => 3759.0,
                'modal' => 3096.0,
            ],
            [
                'crop' => 'Maize',
                'market' => 'HANGAL',
                'variety' => 'Local',
                'grade' => 'Medium',
                'arrivals' => 10942.0,
                'units' => 'Quintal',
                'min' => 2100.0,
                'max' => 2750.0,
                'modal' => 2400.0,
            ],
            [
                'crop' => 'Onion',
                'market' => 'SHIVAMOGGA',
                'variety' => 'Onion',
                'grade' => 'Average',
                'arrivals' => 100.0,
                'units' => 'Quintal',
                'min' => 4000.0,
                'max' => 6000.0,
                'modal' => 5000.0,
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
                    $dStr = $cursor->format('Y-m-d');
                    foreach ($templates as $tmpl) {
                        $row = $tmpl;
                        $row['date'] = $dStr;
                        $row['arrival_date'] = $dStr;
                        $records[] = $row;
                    }
                }
                $cursor->addDay();
            }
            return $records;
        }

        $records = [];
        $dStr = $toDate ? Carbon::parse($toDate)->format('Y-m-d') : $today;
        foreach ($templates as $tmpl) {
            $row = $tmpl;
            $row['date'] = $dStr;
            $row['arrival_date'] = $dStr;
            $records[] = $row;
        }
        return $records;
    }
}
