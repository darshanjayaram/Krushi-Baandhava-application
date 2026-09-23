<?php

namespace App\Services\DataSources;

use App\Models\DataSource;
use App\Services\DataSources\Contracts\MarketDataProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseMarketDataProvider implements MarketDataProviderInterface
{
    protected DataSource $dataSource;
    protected ?string $apiKey = null;
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected ?array $additionalHeaders = null;

    public function __construct(DataSource $dataSource)
    {
        $this->dataSource = $dataSource;

        if ($dataSource->relationLoaded('credential') || $dataSource->credential) {
            $this->apiKey = $dataSource->credential?->api_key;
            $this->clientId = $dataSource->credential?->client_id;
            $this->clientSecret = $dataSource->credential?->client_secret;
            $rawHeaders = $dataSource->credential?->additional_headers;
            $this->additionalHeaders = is_array($rawHeaders) ? $rawHeaders : (is_string($rawHeaders) ? json_decode($rawHeaders, true) : null);
        }
    }

    /**
     * Check if mock mode is globally active or explicitly set.
     */
    protected function isMockMode(): bool
    {
        return (bool) config('services.data_sources.mock_mode', env('DATA_SOURCE_MOCK_MODE', true));
    }

    /**
     * Execute an HTTP GET request with measured response time and standardized headers.
     *
     * @param string $url
     * @param array<string, mixed> $queryParams
     * @param array<string, string> $extraHeaders
     * @return array{
     *     success: bool,
     *     http_status: int|null,
     *     response_time_ms: int,
     *     body: mixed,
     *     error: string|null
     * }
     */
    protected function makeGetRequest(string $url, array $queryParams = [], array $extraHeaders = []): array
    {
        $headers = array_merge([
            'User-Agent' => 'KrushiBaandhava/1.0 (contact@krushibaandhava.org; Karnataka)',
            'Accept' => 'application/json',
        ], $this->additionalHeaders ?? [], $extraHeaders);

        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->dataSource->timeout_seconds ?? 30)
                ->withHeaders($headers)
                ->get($url, $queryParams);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            return [
                'success' => $response->successful(),
                'http_status' => $response->status(),
                'response_time_ms' => $responseTimeMs,
                'body' => $response->json() ?? $response->body(),
                'error' => $response->successful() ? null : 'HTTP ' . $response->status() . ': ' . substr($response->body(), 0, 200),
            ];
        } catch (\Throwable $e) {
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::warning("DataSource [{$this->dataSource->code}] HTTP request failed: " . $e->getMessage());

            return [
                'success' => false,
                'http_status' => null,
                'response_time_ms' => $responseTimeMs,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Apply declarative transformation rule to a raw field value.
     */
    protected function applyTransformation(mixed $value, ?string $rule): mixed
    {
        if ($value === null || $rule === null || trim($rule) === '') {
            return is_string($value) ? trim($value) : $value;
        }

        $ruleParts = explode(':', $rule, 2);
        $action = strtolower(trim($ruleParts[0]));
        $param = $ruleParts[1] ?? null;

        return match ($action) {
            'trim' => is_string($value) ? trim($value) : $value,
            'uppercase' => is_string($value) ? mb_strtoupper(trim($value)) : $value,
            'lowercase' => is_string($value) ? mb_strtolower(trim($value)) : $value,
            'to_number' => (function () use ($value) {
                if (is_numeric($value)) {
                    return (float) $value;
                }
                $clean = preg_replace('/[^0-9.]/', '', (string) $value);
                return is_numeric($clean) ? (float) $clean : 0.0;
            })(),
            'to_integer' => (function () use ($value) {
                $clean = preg_replace('/[^0-9]/', '', (string) $value);
                return is_numeric($clean) ? (int) $clean : 0;
            })(),
            'date_format' => (function () use ($value, $param) {
                try {
                    $format = $param ?: 'd/m/Y';
                    return Carbon::createFromFormat($format, trim((string) $value))->format('Y-m-d');
                } catch (\Throwable) {
                    try {
                        return Carbon::parse((string) $value)->format('Y-m-d');
                    } catch (\Throwable) {
                        return null;
                    }
                }
            })(),
            'per_quintal_from_50kg' => (function () use ($value) {
                $num = (float) preg_replace('/[^0-9.]/', '', (string) $value);
                return round($num * 2, 2);
            })(),
            default => $value,
        };
    }
}
