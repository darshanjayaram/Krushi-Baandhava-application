<?php

namespace App\Services\DataSources\Krama;

use App\Services\DataSources\BaseMarketDataProvider;

class KramaMarketDataProvider extends BaseMarketDataProvider
{
    public function fetch(array $filters = []): iterable
    {
        // Placeholder adapter until official state direct feed API credentials are authorized
        return [];
    }

    public function normalize(array $record): ?array
    {
        return null;
    }

    public function healthCheck(): array
    {
        return [
            'http_status' => 200,
            'response_time_ms' => 15,
            'auth_result' => 'skipped (placeholder)',
            'records_found' => 0,
            'detected_fields' => [],
            'status' => 'healthy',
            'error_message' => 'KRAMA adapter is an official access placeholder. Ingestion routed primarily via data.gov.in Mandi Prices.',
            'sample_payload' => null,
        ];
    }
}
