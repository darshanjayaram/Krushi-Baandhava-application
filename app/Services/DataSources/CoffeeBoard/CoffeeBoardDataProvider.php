<?php

namespace App\Services\DataSources\CoffeeBoard;

use App\Services\DataSources\BaseMarketDataProvider;
use Carbon\Carbon;

class CoffeeBoardDataProvider extends BaseMarketDataProvider
{
    public function fetch(array $filters = []): iterable
    {
        if ($this->isMockMode()) {
            return $this->getMockRecords();
        }

        $url = rtrim($this->dataSource->base_url, '/') . '/' . ltrim($this->dataSource->endpoint ?? '', '/');
        $res = $this->makeGetRequest($url);

        if (!$res['success'] || !isset($res['body']['rates']) || !is_array($res['body']['rates'])) {
            return [];
        }

        return $res['body']['rates'];
    }

    public function normalize(array $record): ?array
    {
        $variety = $this->applyTransformation($record['variety'] ?? null, 'trim');
        if (empty($variety)) {
            return null;
        }

        // Standard unit: 50 kg bag price normalized to Quintal (100 kg)
        $rawMin = (float) $this->applyTransformation($record['min_price_50kg'] ?? 0, 'to_number');
        $rawMax = (float) $this->applyTransformation($record['max_price_50kg'] ?? 0, 'to_number');

        $minQuintal = $rawMin * 2;
        $maxQuintal = $rawMax * 2;
        $modalQuintal = ($minQuintal + $maxQuintal) / 2;

        return [
            'source_crop' => 'Coffee',
            'source_variety' => $variety,
            'source_market' => $this->applyTransformation($record['location'] ?? 'Chikkamagaluru', 'trim'),
            'source_district' => 'Chikkamagaluru',
            'price_date' => Carbon::today()->format('Y-m-d'),
            'min_price' => round($minQuintal, 2),
            'max_price' => round($maxQuintal, 2),
            'modal_price' => round($modalQuintal, 2),
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
                'response_time_ms' => 38,
                'auth_result' => 'success (mock mode)',
                'records_found' => 4,
                'detected_fields' => ['variety', 'min_price_50kg', 'max_price_50kg', 'location'],
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
                'variety' => 'Arabica Parchment',
                'min_price_50kg' => '17500',
                'max_price_50kg' => '18200',
                'location' => 'Chikkamagaluru',
            ],
            [
                'variety' => 'Arabica Cherry',
                'min_price_50kg' => '9800',
                'max_price_50kg' => '10600',
                'location' => 'Chikkamagaluru',
            ],
            [
                'variety' => 'Robusta Parchment',
                'min_price_50kg' => '11500',
                'max_price_50kg' => '12200',
                'location' => 'Chikkamagaluru',
            ],
            [
                'variety' => 'Robusta Cherry',
                'min_price_50kg' => '6800',
                'max_price_50kg' => '7400',
                'location' => 'Chikkamagaluru',
            ],
        ];
    }
}
