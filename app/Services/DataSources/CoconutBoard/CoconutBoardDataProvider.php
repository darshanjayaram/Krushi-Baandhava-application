<?php

namespace App\Services\DataSources\CoconutBoard;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;

class CoconutBoardDataProvider extends BaseMarketDataProvider
{
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords();
        }

        $url = rtrim($this->dataSource->base_url, '/') . '/' . ltrim($this->dataSource->endpoint ?? '', '/');
        $res = $this->makeGetRequest($url);

        if (!$res['success'] || !isset($res['body']['prices']) || !is_array($res['body']['prices'])) {
            return [];
        }

        return $res['body']['prices'];
    }

    public function normalize(array $record): ?array
    {
        $commodity = $this->applyTransformation($record['commodity'] ?? null, 'trim');
        if (empty($commodity)) {
            return null;
        }

        $min = (float) $this->applyTransformation($record['min_price'] ?? 0, 'to_number');
        $max = (float) $this->applyTransformation($record['max_price'] ?? 0, 'to_number');
        $modal = (float) $this->applyTransformation($record['modal_price'] ?? (($min + $max) / 2), 'to_number');

        return [
            'source_crop' => $commodity === 'Copra' ? 'Copra' : 'Coconut',
            'source_variety' => $this->applyTransformation($record['grade'] ?? 'FAQ', 'trim'),
            'source_market' => $this->applyTransformation($record['center'] ?? 'Arsikere', 'trim'),
            'source_district' => $this->applyTransformation($record['district'] ?? 'Hassan', 'trim'),
            'price_date' => Carbon::today()->format('Y-m-d'),
            'min_price' => round($min, 2),
            'max_price' => round($max, 2),
            'modal_price' => round($modal, 2),
            'arrival_quantity' => 0.0,
            'unit' => $record['unit'] ?? ($commodity === 'Copra' ? 'Quintal' : '1000 Nuts'),
            'raw_payload' => $record,
        ];
    }

    public function healthCheck(): array
    {
        if ($this->isMockMode()) {
            return [
                'http_status' => 200,
                'response_time_ms' => 45,
                'auth_result' => 'success (mock mode)',
                'records_found' => 3,
                'detected_fields' => ['commodity', 'grade', 'center', 'district', 'min_price', 'max_price', 'modal_price', 'unit'],
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
                'commodity' => 'Copra',
                'grade' => 'Milling',
                'center' => 'Arsikere',
                'district' => 'Hassan',
                'min_price' => '11800',
                'max_price' => '12600',
                'modal_price' => '12200',
                'unit' => 'Quintal',
            ],
            [
                'commodity' => 'Copra',
                'grade' => 'Ball',
                'center' => 'Tiptur',
                'district' => 'Tumakuru',
                'min_price' => '16500',
                'max_price' => '18200',
                'modal_price' => '17400',
                'unit' => 'Quintal',
            ],
            [
                'commodity' => 'Coconut',
                'grade' => 'Dehusked Large',
                'center' => 'Mangalore',
                'district' => 'Dakshina Kannada',
                'min_price' => '22000',
                'max_price' => '25000',
                'modal_price' => '23500',
                'unit' => '1000 Nuts',
            ],
        ];
    }
}
