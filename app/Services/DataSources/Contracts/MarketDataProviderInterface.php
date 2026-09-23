<?php

namespace App\Services\DataSources\Contracts;

interface MarketDataProviderInterface
{
    /**
     * Fetch raw market records from the external source provider.
     *
     * @param array<string, mixed> $filters
     * @return iterable<int, array<string, mixed>>
     */
    public function fetch(array $filters = []): iterable;

    /**
     * Normalize a single raw record into the standardized Krushi Baandhava format.
     * Returns null if the record cannot be parsed or fails basic sanity rules.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>|null
     */
    public function normalize(array $record): ?array;

    /**
     * Perform a non-destructive ping/health check against the provider endpoint.
     *
     * @return array{
     *     http_status: int|null,
     *     response_time_ms: int|null,
     *     auth_result: string,
     *     records_found: int,
     *     detected_fields: array<int, string>,
     *     status: string,
     *     error_message: string|null,
     *     sample_payload: array<string, mixed>|null
     * }
     */
    public function healthCheck(): array;
}
