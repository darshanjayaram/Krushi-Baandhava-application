<?php

namespace App\Services\DataSources\DataGov;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;

class DataGovMarketDataProvider extends BaseMarketDataProvider
{
    /**
     * Fetch records from data.gov.in or local realistic mock dataset.
     */
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords();
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
     * @return array<int, array<string, string>>
     */
    protected function getMockRecords(): array
    {
        $today = Carbon::today()->format('d/m/Y');

        return [
            // Arecanut in Shivamogga, Sagar, Channagiri
            [
                'state' => 'Karnataka',
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Arecanut',
                'variety' => 'Rashi',
                'arrival_date' => $today,
                'min_price' => '48500',
                'max_price' => '54000',
                'modal_price' => '52400',
                'arrival_quantity' => '142',
            ],
            [
                'state' => 'Karnataka',
                'district' => 'Shimoga',
                'market' => 'Sagar',
                'commodity' => 'Arecanut',
                'variety' => 'Rashi',
                'arrival_date' => $today,
                'min_price' => '47000',
                'max_price' => '53800',
                'modal_price' => '51900',
                'arrival_quantity' => '85',
            ],
            [
                'state' => 'Karnataka',
                'district' => 'Davanagere',
                'market' => 'Channagiri',
                'commodity' => 'Arecanut',
                'variety' => 'Rashi',
                'arrival_date' => $today,
                'min_price' => '49000',
                'max_price' => '54500',
                'modal_price' => '52800',
                'arrival_quantity' => '110',
            ],
            // Paddy in Mandya & Shivamogga
            [
                'state' => 'Karnataka',
                'district' => 'Mandya',
                'market' => 'Mandya',
                'commodity' => 'Paddy',
                'variety' => 'Sona Masuri',
                'arrival_date' => $today,
                'min_price' => '2400',
                'max_price' => '2850',
                'modal_price' => '2680',
                'arrival_quantity' => '520',
            ],
            [
                'state' => 'Karnataka',
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Paddy',
                'variety' => 'Jyothi',
                'arrival_date' => $today,
                'min_price' => '2300',
                'max_price' => '2750',
                'modal_price' => '2550',
                'arrival_quantity' => '340',
            ],
            // Ragi in Mysuru
            [
                'state' => 'Karnataka',
                'district' => 'Mysuru',
                'market' => 'Mysuru (Bandipalya)',
                'commodity' => 'Ragi',
                'variety' => 'Local Ragi',
                'arrival_date' => $today,
                'min_price' => '3200',
                'max_price' => '3800',
                'modal_price' => '3550',
                'arrival_quantity' => '180',
            ],
            // Maize in Davanagere & Shivamogga
            [
                'state' => 'Karnataka',
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Maize',
                'variety' => 'Yellow Hybrid Maize',
                'arrival_date' => $today,
                'min_price' => '2100',
                'max_price' => '2450',
                'modal_price' => '2320',
                'arrival_quantity' => '840',
            ],
            [
                'state' => 'Karnataka',
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Maize',
                'variety' => 'Yellow Hybrid Maize',
                'arrival_date' => $today,
                'min_price' => '2050',
                'max_price' => '2400',
                'modal_price' => '2280',
                'arrival_quantity' => '290',
            ],
            // Jowar in Davanagere
            [
                'state' => 'Karnataka',
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Jowar',
                'variety' => 'White Jowar',
                'arrival_date' => $today,
                'min_price' => '3600',
                'max_price' => '4400',
                'modal_price' => '4100',
                'arrival_quantity' => '320',
            ],
            // Tur in Davanagere
            [
                'state' => 'Karnataka',
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Tur',
                'variety' => 'Red Tur',
                'arrival_date' => $today,
                'min_price' => '9200',
                'max_price' => '10800',
                'modal_price' => '10200',
                'arrival_quantity' => '260',
            ],
            // Onion in Davanagere
            [
                'state' => 'Karnataka',
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Onion',
                'variety' => 'Red Medium Onion',
                'arrival_date' => $today,
                'min_price' => '1800',
                'max_price' => '2600',
                'modal_price' => '2250',
                'arrival_quantity' => '650',
            ],
            // Tomato in Kolar & Shivamogga
            [
                'state' => 'Karnataka',
                'district' => 'Kolar',
                'market' => 'Kolar',
                'commodity' => 'Tomato',
                'variety' => 'Hybrid Tomato',
                'arrival_date' => $today,
                'min_price' => '1400',
                'max_price' => '2200',
                'modal_price' => '1850',
                'arrival_quantity' => '920',
            ],
            [
                'state' => 'Karnataka',
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Tomato',
                'variety' => 'Local Tomato',
                'arrival_date' => $today,
                'min_price' => '1200',
                'max_price' => '1800',
                'modal_price' => '1550',
                'arrival_quantity' => '180',
            ],
            // Green Chilli in Belagavi & Shivamogga
            [
                'state' => 'Karnataka',
                'district' => 'Belagavi',
                'market' => 'Belagavi',
                'commodity' => 'Green Chilli',
                'variety' => 'Green Chilli Hybrid',
                'arrival_date' => $today,
                'min_price' => '3200',
                'max_price' => '4500',
                'modal_price' => '3900',
                'arrival_quantity' => '240',
            ],
            // Banana in Mysuru
            [
                'state' => 'Karnataka',
                'district' => 'Mysuru',
                'market' => 'Mysuru (Bandipalya)',
                'commodity' => 'Banana',
                'variety' => 'Yelakki Banana',
                'arrival_date' => $today,
                'min_price' => '2200',
                'max_price' => '3400',
                'modal_price' => '2900',
                'arrival_quantity' => '310',
            ],
            // Raw Banana in Shivamogga
            [
                'state' => 'Karnataka',
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Raw Banana',
                'variety' => 'Green Cooking Banana',
                'arrival_date' => $today,
                'min_price' => '1400',
                'max_price' => '2000',
                'modal_price' => '1750',
                'arrival_quantity' => '120',
            ],
            // Groundnut in Davanagere
            [
                'state' => 'Karnataka',
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Groundnut',
                'variety' => 'Pod Groundnut',
                'arrival_date' => $today,
                'min_price' => '6200',
                'max_price' => '7400',
                'modal_price' => '6850',
                'arrival_quantity' => '380',
            ],
            // Sunflower in Davanagere
            [
                'state' => 'Karnataka',
                'district' => 'Davanagere',
                'market' => 'Davanagere',
                'commodity' => 'Sunflower',
                'variety' => 'Sunflower Seed',
                'arrival_date' => $today,
                'min_price' => '4800',
                'max_price' => '5600',
                'modal_price' => '5250',
                'arrival_quantity' => '210',
            ],
            // Black Pepper in Mangaluru & Mudigere
            [
                'state' => 'Karnataka',
                'district' => 'Dakshina Kannada',
                'market' => 'Mangaluru APMC (Baikampady)',
                'commodity' => 'Black Pepper',
                'variety' => 'Garbled Black Pepper',
                'arrival_date' => $today,
                'min_price' => '59000',
                'max_price' => '64000',
                'modal_price' => '61500',
                'arrival_quantity' => '45',
            ],
            // Green Ginger in Shivamogga
            [
                'state' => 'Karnataka',
                'district' => 'Shimoga',
                'market' => 'Shimoga',
                'commodity' => 'Green Ginger',
                'variety' => 'Green Ginger',
                'arrival_date' => $today,
                'min_price' => '4500',
                'max_price' => '6200',
                'modal_price' => '5400',
                'arrival_quantity' => '160',
            ],
        ];
    }
}
