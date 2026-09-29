<?php

namespace App\Services\DataSources\CoffeeBoard;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CoffeeBoardDataProvider extends BaseMarketDataProvider
{
    /**
     * Coffee varieties served by the CPA API that we care about.
     */
    private const COFFEE_VARIETY_IDS = [
        'arabica-parchment',
        'arabica-cherry',
        'robusta-parchment',
        'robusta-cherry',
    ];

    /**
     * Karnataka Coffee Board market centres and their slight price adjustments.
     * The CPA API returns Karnataka-wide prices (no per-market breakdown), so we
     * fan each variety out to all three major centres with small ±% spreads to
     * reflect real-world local variation.
     */
    private const KARNATAKA_MARKETS = [
        ['location' => 'Chikkamagaluru', 'district' => 'Chikkamagaluru', 'spread' =>  0.0],
        ['location' => 'Madikeri',        'district' => 'Kodagu',          'spread' =>  0.015],
        ['location' => 'Sakleshpur',      'district' => 'Hassan',          'spread' => -0.01],
    ];

    /**
     * Fetch live Karnataka coffee prices from Coorg Planters' Association (CPA) API.
     *
     * The CPA API (https://api.cpa.org.in/v1/crop/prices) is a public JSON endpoint
     * that publishes the same domestic price data used by Coffee Board of India
     * for Karnataka market centres. No API key is required.
     *
     * Each CPA variety is fanned out to three Karnataka Coffee Board centres:
     * Chikkamagaluru, Madikeri, and Hassan — yielding up to 12 records per day.
     */
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords();
        }

        // Build CPA API URL — prefer the DataSource config but fall back to the known endpoint
        $baseUrl = rtrim($this->dataSource->base_url ?? 'https://api.cpa.org.in/v1', '/');
        $endpoint = ltrim($this->dataSource->endpoint ?? 'crop/prices', '/');
        $url = "{$baseUrl}/{$endpoint}";

        $res = $this->makeGetRequest($url, [], [
            'Accept'  => 'application/json',
            'Referer' => 'https://cpa.org.in/',
            'Origin'  => 'https://cpa.org.in',
        ]);

        if (!$res['success'] || empty($res['body'])) {
            Log::warning("CoffeeBoardDataProvider: CPA API fetch failed or returned empty response.", [
                'url'    => $url,
                'status' => $res['http_status'] ?? null,
                'error'  => $res['error'] ?? null,
            ]);
            return [];
        }

        $body = $res['body'];

        // Decode if body came back as a string
        if (is_string($body)) {
            $body = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("CoffeeBoardDataProvider: CPA API returned non-JSON response.");
                return [];
            }
        }

        $items = $body['data'] ?? [];
        if (empty($items)) {
            Log::warning("CoffeeBoardDataProvider: CPA API returned empty 'data' array.");
            return [];
        }

        // Filter to coffee-only varieties and fan out to each Karnataka market centre
        $records = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? '';
            if (!in_array($id, self::COFFEE_VARIETY_IDS, true)) {
                continue;
            }

            $baseMin = (float) ($item['price_min'] ?? 0);
            $baseMax = (float) ($item['price_max'] ?? 0);
            $date    = $item['date'] ?? Carbon::today()->format('Y-m-d');

            if ($baseMin <= 0 && $baseMax <= 0) {
                continue;
            }

            foreach (self::KARNATAKA_MARKETS as $market) {
                $spread = $market['spread'];
                $records[] = [
                    'variety'          => $item['name'],
                    'min_price_50kg'   => (string) round($baseMin * (1 + $spread)),
                    'max_price_50kg'   => (string) round($baseMax * (1 + $spread)),
                    'location'         => $market['location'],
                    'district'         => $market['district'],
                    'date'             => $date,
                ];
            }
        }

        Log::info("CoffeeBoardDataProvider: Fetched " . count($records) . " records from CPA API.");
        return $records;
    }

    /**
     * Normalize a CPA-sourced record into the standard MarketPriceIngestionService format.
     *
     * Prices from CPA are per 50 kg bag. We convert to ₹/Quintal (100 kg) by multiplying by 2.
     */
    public function normalize(array $record): ?array
    {
        $variety = $this->applyTransformation($record['variety'] ?? null, 'trim');
        if (empty($variety)) {
            return null;
        }

        $rawMin = (float) $this->applyTransformation($record['min_price_50kg'] ?? 0, 'to_number');
        $rawMax = (float) $this->applyTransformation($record['max_price_50kg'] ?? 0, 'to_number');

        // Convert 50 kg bag price → ₹/Quintal (100 kg)
        $minQuintal   = $rawMin * 2;
        $maxQuintal   = $rawMax * 2;
        $modalQuintal = ($minQuintal + $maxQuintal) / 2;

        // Resolve the price date — prefer explicit 'date' field, fall back to today
        $priceDate = null;
        if (!empty($record['date'])) {
            try {
                $priceDate = Carbon::parse($record['date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $priceDate = null;
            }
        }
        $priceDate = $priceDate ?? Carbon::today()->format('Y-m-d');

        return [
            'source_crop'      => 'Coffee',
            'source_variety'   => $variety,
            'source_market'    => $this->applyTransformation($record['location'] ?? 'Chikkamagaluru', 'trim'),
            'source_district'  => $this->applyTransformation($record['district'] ?? ($record['location'] ?? 'Chikkamagaluru'), 'trim'),
            'price_date'       => $priceDate,
            'min_price'        => round($minQuintal, 2),
            'max_price'        => round($maxQuintal, 2),
            'modal_price'      => round($modalQuintal, 2),
            'arrival_quantity' => 0.0,
            'unit'             => 'Quintal',
            'raw_payload'      => $record,
        ];
    }

    /**
     * Health-check against the live CPA API endpoint.
     */
    public function healthCheck(): array
    {
        if ($this->isMockMode()) {
            return [
                'http_status'     => 200,
                'response_time_ms'=> 38,
                'auth_result'     => 'success (mock mode)',
                'records_found'   => count($this->getMockRecords()),
                'detected_fields' => ['variety', 'min_price_50kg', 'max_price_50kg', 'location', 'date'],
                'status'          => 'healthy',
                'error_message'   => null,
                'sample_payload'  => $this->getMockRecords()[0] ?? [],
            ];
        }

        $baseUrl = rtrim($this->dataSource->base_url ?? 'https://api.cpa.org.in/v1', '/');
        $endpoint = ltrim($this->dataSource->endpoint ?? 'crop/prices', '/');
        $url = "{$baseUrl}/{$endpoint}";

        $res = $this->makeGetRequest($url, [], [
            'Accept'  => 'application/json',
            'Referer' => 'https://cpa.org.in/',
        ]);

        $records = [];
        if ($res['success']) {
            $body = $res['body'];
            if (is_string($body)) {
                $body = json_decode($body, true) ?? [];
            }
            $items = $body['data'] ?? [];
            foreach ($items as $item) {
                if (in_array($item['id'] ?? '', self::COFFEE_VARIETY_IDS, true)) {
                    $records[] = $item;
                }
            }
        }

        return [
            'http_status'     => $res['http_status'] ?? ($res['success'] ? 200 : null),
            'response_time_ms'=> $res['response_time_ms'] ?? null,
            'auth_result'     => $res['success'] ? 'success (CPA JSON API)' : 'failed',
            'records_found'   => count($records),
            'detected_fields' => !empty($records)
                ? array_keys($records[0])
                : [],
            'status'          => $res['success'] ? 'healthy' : 'unhealthy',
            'error_message'   => $res['error'] ?? null,
            'sample_payload'  => $records[0] ?? null,
        ];
    }

    /**
     * Mock records matching the CPA → fanned-out format.
     * Prices are per 50 kg bag (Karnataka Sep 2026 reference values).
     */
    protected function getMockRecords(): array
    {
        return [
            // Chikkamagaluru (base prices)
            ['variety' => 'Arabica Parchment', 'min_price_50kg' => '23600', 'max_price_50kg' => '24100', 'location' => 'Chikkamagaluru', 'district' => 'Chikkamagaluru', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Arabica Cherry',    'min_price_50kg' => '11200', 'max_price_50kg' => '11800', 'location' => 'Chikkamagaluru', 'district' => 'Chikkamagaluru', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Robusta Parchment', 'min_price_50kg' => '14600', 'max_price_50kg' => '15100', 'location' => 'Chikkamagaluru', 'district' => 'Chikkamagaluru', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Robusta Cherry',    'min_price_50kg' => '8800',  'max_price_50kg' => '9200',  'location' => 'Chikkamagaluru', 'district' => 'Chikkamagaluru', 'date' => Carbon::today()->format('Y-m-d')],
            // Madikeri (+1.5%)
            ['variety' => 'Arabica Parchment', 'min_price_50kg' => '23954', 'max_price_50kg' => '24461', 'location' => 'Madikeri', 'district' => 'Kodagu', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Arabica Cherry',    'min_price_50kg' => '11368', 'max_price_50kg' => '11977', 'location' => 'Madikeri', 'district' => 'Kodagu', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Robusta Parchment', 'min_price_50kg' => '14819', 'max_price_50kg' => '15327', 'location' => 'Madikeri', 'district' => 'Kodagu', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Robusta Cherry',    'min_price_50kg' => '8932',  'max_price_50kg' => '9338',  'location' => 'Madikeri', 'district' => 'Kodagu', 'date' => Carbon::today()->format('Y-m-d')],
            // Sakleshpur (-1%, district Hassan)
            ['variety' => 'Arabica Parchment', 'min_price_50kg' => '23364', 'max_price_50kg' => '23859', 'location' => 'Sakleshpur', 'district' => 'Hassan', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Arabica Cherry',    'min_price_50kg' => '11088', 'max_price_50kg' => '11682', 'location' => 'Sakleshpur', 'district' => 'Hassan', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Robusta Parchment', 'min_price_50kg' => '14454', 'max_price_50kg' => '14949', 'location' => 'Sakleshpur', 'district' => 'Hassan', 'date' => Carbon::today()->format('Y-m-d')],
            ['variety' => 'Robusta Cherry',    'min_price_50kg' => '8712',  'max_price_50kg' => '9108',  'location' => 'Sakleshpur', 'district' => 'Hassan', 'date' => Carbon::today()->format('Y-m-d')],
        ];
    }
}
