<?php

namespace App\Services\DataSources\Agmarknet;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;

class AgmarknetMarketDataProvider extends BaseMarketDataProvider
{
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords();
        }

        $url = rtrim($this->dataSource->base_url, '/') . '/' . ltrim($this->dataSource->endpoint ?? '', '/');
        $res = $this->makeGetRequest($url, [
            'state' => 'Karnataka',
            'date' => $filters['date'] ?? Carbon::today()->format('Y-m-d'),
        ]);

        if (!$res['success'] || !isset($res['body']['data']) || !is_array($res['body']['data'])) {
            return [];
        }

        return $res['body']['data'];
    }

    public function normalize(array $record): ?array
    {
        $cropName = $this->applyTransformation($record['Commodity'] ?? $record['commodity'] ?? null, 'trim');
        $marketName = $this->applyTransformation($record['Market'] ?? $record['market'] ?? null, 'trim');
        $stateName = $this->applyTransformation($record['State'] ?? $record['state'] ?? null, 'trim');

        // Strictly reject any records that do not belong to Karnataka
        if (!empty($stateName) && strcasecmp($stateName, 'Karnataka') !== 0) {
            return null;
        }

        if (empty($cropName) || empty($marketName)) {
            return null;
        }

        $priceDate = $this->applyTransformation($record['PriceDate'] ?? $record['date'] ?? null, 'date_format:Y-m-d');
        if (empty($priceDate)) {
            $priceDate = Carbon::today()->format('Y-m-d');
        }

        $min = (float) $this->applyTransformation($record['MinPrice'] ?? $record['min_price'] ?? 0, 'to_number');
        $max = (float) $this->applyTransformation($record['MaxPrice'] ?? $record['max_price'] ?? 0, 'to_number');
        $modal = (float) $this->applyTransformation($record['ModalPrice'] ?? $record['modal_price'] ?? 0, 'to_number');

        return [
            'source_crop' => $cropName,
            'source_variety' => $this->applyTransformation($record['Variety'] ?? $record['variety'] ?? 'General', 'trim'),
            'source_market' => $marketName,
            'source_district' => $this->applyTransformation($record['District'] ?? $record['district'] ?? 'Karnataka', 'trim'),
            'price_date' => $priceDate,
            'min_price' => round($min, 2),
            'max_price' => round($max, 2),
            'modal_price' => round($modal, 2),
            'arrival_quantity' => 0.0,
            'unit' => 'Quintal',
            'raw_payload' => $record,
        ];
    }

    public function healthCheck(): array
    {
        if ($this->isMockMode()) {
            return [
                'http_status' => 200,
                'response_time_ms' => 48,
                'auth_result' => 'success (mock mode)',
                'records_found' => 3,
                'detected_fields' => ['Commodity', 'Variety', 'Market', 'District', 'MinPrice', 'MaxPrice', 'ModalPrice'],
                'status' => 'healthy',
                'error_message' => null,
                'sample_payload' => $this->getMockRecords()[0] ?? [],
            ];
        }

        $url = rtrim($this->dataSource->base_url, '/');
        $res = $this->makeGetRequest($url);

        return [
            'http_status' => $res['http_status'],
            'response_time_ms' => $res['response_time_ms'],
            'auth_result' => $res['success'] ? 'success' : 'failed',
            'records_found' => 0,
            'detected_fields' => [],
            'status' => $res['success'] ? 'healthy' : 'unhealthy',
            'error_message' => $res['error'],
            'sample_payload' => null,
        ];
    }

    protected function getMockRecords(): array
    {
        return [
            [
                'Commodity' => 'Arecanut',
                'Variety' => 'Chali',
                'Market' => 'Mangalore',
                'District' => 'Dakshina Kannada',
                'MinPrice' => '42000',
                'MaxPrice' => '47500',
                'ModalPrice' => '45000',
                'PriceDate' => Carbon::today()->format('Y-m-d'),
            ],
            [
                'Commodity' => 'Coffee',
                'Variety' => 'Robusta Cherry',
                'Market' => 'Chikkamagaluru',
                'District' => 'Chikkamagaluru',
                'MinPrice' => '13000',
                'MaxPrice' => '15500',
                'ModalPrice' => '14200',
                'PriceDate' => Carbon::today()->format('Y-m-d'),
            ],
            [
                'Commodity' => 'Pepper',
                'Variety' => 'Black',
                'Market' => 'Shivamogga',
                'District' => 'Shivamogga',
                'MinPrice' => '59000',
                'MaxPrice' => '62500',
                'ModalPrice' => '61000',
                'PriceDate' => Carbon::today()->format('Y-m-d'),
            ],
        ];
    }
}
