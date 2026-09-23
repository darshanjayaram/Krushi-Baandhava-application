<?php

namespace App\Services\Ingestion;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Models\MarketSourceMapping;
use App\Models\SyncLog;
use App\Services\DataSources\DataSourceRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketPriceIngestionService
{
    /**
     * Ingest and normalize records for a given data source.
     *
     * @param DataSource|string $dataSourceOrCode
     * @param array{
     *     force?: bool,
     *     dry_run?: bool,
     *     filters?: array<string, mixed>
     * } $options
     * @return array{
     *     status: string,
     *     source_code: string,
     *     duration_ms: int,
     *     received: int,
     *     inserted: int,
     *     updated: int,
     *     duplicate: int,
     *     rejected: int,
     *     errors: array<int, string>
     * }
     */
    public function ingest(DataSource|string $dataSourceOrCode, array $options = []): array
    {
        $startTime = microtime(true);
        $startedAt = Carbon::now();

        $dataSource = is_string($dataSourceOrCode)
            ? DataSource::where('code', $dataSourceOrCode)->firstOrFail()
            : $dataSourceOrCode;

        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $filters = $options['filters'] ?? [];

        $counts = [
            'received' => 0,
            'inserted' => 0,
            'updated' => 0,
            'duplicate' => 0,
            'rejected' => 0,
        ];
        $errors = [];

        $syncLog = null;
        if (!$dryRun) {
            $syncLog = SyncLog::create([
                'data_source_id' => $dataSource->id,
                'started_at' => $startedAt,
                'status' => 'running',
            ]);
        }

        try {
            $provider = DataSourceRegistry::make($dataSource);
            $rawRecords = iterator_to_array($provider->fetch($filters));
            $counts['received'] = count($rawRecords);

            foreach ($rawRecords as $record) {
                // 1. Checksum deduplication
                $checksum = hash('sha256', json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                $existingRaw = MarketPriceRaw::where('data_source_id', $dataSource->id)
                    ->where('checksum', $checksum)
                    ->first();

                if ($existingRaw && !$force) {
                    $counts['duplicate']++;
                    continue;
                }

                if ($dryRun) {
                    // In dry-run mode, simulate normalization and entity resolution
                    $normalized = $provider->normalize($record);
                    if ($normalized === null) {
                        $counts['rejected']++;
                    } else {
                        $crop = $this->resolveCrop($dataSource->id, $normalized['source_crop'], $normalized['source_variety']);
                        $market = $this->resolveMarket($dataSource->id, $normalized['source_market'], $normalized['source_district']);
                        if ($crop && $market && $normalized['modal_price'] > 0) {
                            $counts['inserted']++;
                        } else {
                            $counts['rejected']++;
                        }
                    }
                    continue;
                }

                // Persist raw record in market_price_raw
                $rawModel = $existingRaw ?? new MarketPriceRaw([
                    'data_source_id' => $dataSource->id,
                    'checksum' => $checksum,
                    'received_at' => Carbon::now(),
                ]);
                $rawModel->payload = $record;
                $rawModel->processing_status = 'pending';
                $rawModel->error_message = null;
                $rawModel->save();

                // 2. Normalization
                $normalized = $provider->normalize($record);
                if ($normalized === null) {
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => 'Failed to parse record attributes (missing commodity or market).',
                    ]);
                    $counts['rejected']++;
                    continue;
                }

                // 3. Resolve Canonical Crop
                $crop = $this->resolveCrop($dataSource->id, $normalized['source_crop'], $normalized['source_variety'] ?? null);
                if (!$crop) {
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => "Unmapped commodity alias: '{$normalized['source_crop']}'. Add mapping in admin.",
                    ]);
                    $counts['rejected']++;
                    continue;
                }

                // 4. Resolve Canonical Variety
                $variety = $this->resolveVariety($crop->id, $normalized['source_variety'] ?? null);

                // 5. Resolve Canonical Market
                $market = $this->resolveMarket($dataSource->id, $normalized['source_market'], $normalized['source_district'] ?? null);
                if (!$market) {
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => "Unmapped market alias: '{$normalized['source_market']}'. Add mapping in admin.",
                    ]);
                    $counts['rejected']++;
                    continue;
                }

                // 6. Sanity Validations
                $modalPrice = (float) $normalized['modal_price'];
                $minPrice = (float) ($normalized['min_price'] ?? $modalPrice);
                $maxPrice = (float) ($normalized['max_price'] ?? $modalPrice);
                $arrivalQty = (float) ($normalized['arrival_quantity'] ?? 0);

                if ($modalPrice <= 0) {
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => "Invalid price value: modal price ({$modalPrice}) must be greater than zero.",
                    ]);
                    $counts['rejected']++;
                    continue;
                }

                // Self-correct or bound prices
                if ($minPrice > $modalPrice) {
                    $minPrice = $modalPrice;
                }
                if ($maxPrice < $modalPrice) {
                    $maxPrice = $modalPrice;
                }

                $priceDate = Carbon::parse($normalized['price_date']);
                if ($priceDate->isFuture()) {
                    $priceDate = Carbon::today();
                }

                // 7. Persist Canonical Record to market_prices
                $canonical = MarketPrice::updateOrCreate(
                    [
                        'crop_id' => $crop->id,
                        'variety_id' => $variety?->id,
                        'market_id' => $market->id,
                        'price_date' => $priceDate->format('Y-m-d'),
                        'data_source_id' => $dataSource->id,
                    ],
                    [
                        'district_id' => $market->district_id,
                        'min_price' => $minPrice,
                        'max_price' => $maxPrice,
                        'modal_price' => $modalPrice,
                        'arrival_quantity' => $arrivalQty,
                        'unit' => $normalized['unit'] ?? 'Quintal',
                        'raw_record_id' => $rawModel->id,
                    ]
                );

                if ($canonical->wasRecentlyCreated) {
                    $counts['inserted']++;
                } else {
                    $counts['updated']++;
                }

                $rawModel->update([
                    'processing_status' => 'processed',
                    'processed_at' => Carbon::now(),
                ]);
            }

            $durationMs = max(0, (int) round((microtime(true) - $startTime) * 1000));
            $finalStatus = ($counts['rejected'] > 0 && $counts['inserted'] === 0 && $counts['updated'] === 0)
                ? 'failed'
                : (($counts['rejected'] > 0) ? 'partial' : 'success');

            if ($syncLog) {
                $syncLog->update([
                    'completed_at' => Carbon::now(),
                    'duration_ms' => $durationMs,
                    'records_received' => $counts['received'],
                    'records_inserted' => $counts['inserted'],
                    'records_updated' => $counts['updated'],
                    'records_duplicate' => $counts['duplicate'],
                    'records_rejected' => $counts['rejected'],
                    'status' => $finalStatus,
                    'details' => [
                        'sample_record' => $rawRecords[0] ?? null,
                    ],
                ]);
            }

            if (!$dryRun) {
                $dataSource->update([
                    'last_sync_at' => Carbon::now(),
                    'last_sync_status' => $finalStatus,
                ]);
            }

            return array_merge($counts, [
                'status' => $finalStatus,
                'source_code' => $dataSource->code,
                'duration_ms' => $durationMs,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            $durationMs = max(0, (int) round((microtime(true) - $startTime) * 1000));
            Log::error("Ingestion failed for [{$dataSource->code}]: " . $e->getMessage());

            if ($syncLog) {
                $syncLog->update([
                    'completed_at' => Carbon::now(),
                    'duration_ms' => $durationMs,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            if (!$dryRun) {
                $dataSource->update([
                    'last_sync_at' => Carbon::now(),
                    'last_sync_status' => 'failed',
                ]);
            }

            return array_merge($counts, [
                'status' => 'failed',
                'source_code' => $dataSource->code,
                'duration_ms' => $durationMs,
                'errors' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Resolve raw commodity name to canonical Crop model.
     */
    protected function resolveCrop(int $dataSourceId, string $sourceCropName, ?string $varietyName = null): ?Crop
    {
        $mappingQuery = CropSourceMapping::where('data_source_id', $dataSourceId)
            ->where('source_crop_name', $sourceCropName);

        if ($varietyName) {
            $exactWithVariety = (clone $mappingQuery)->where('source_variety_name', $varietyName)->first();
            if ($exactWithVariety && $exactWithVariety->crop) {
                return $exactWithVariety->crop;
            }
        }

        $aliasMapping = $mappingQuery->whereNull('source_variety_name')->first();
        if ($aliasMapping && $aliasMapping->crop) {
            return $aliasMapping->crop;
        }

        // Fallback: direct name / slug lookup
        $clean = trim($sourceCropName);
        return Crop::where('name', $clean)
            ->orWhere('slug', Str::slug($clean))
            ->orWhere('name_kn', $clean)
            ->first();
    }

    /**
     * Resolve raw variety string to CropVariety model.
     */
    protected function resolveVariety(int $cropId, ?string $varietyName): ?CropVariety
    {
        if (empty($varietyName)) {
            return CropVariety::where('crop_id', $cropId)->first();
        }

        $clean = trim($varietyName);

        $variety = CropVariety::where('crop_id', $cropId)
            ->where(function ($q) use ($clean) {
                $q->where('name', $clean)
                    ->orWhere('slug', Str::slug($clean))
                    ->orWhere('name_kn', $clean);
            })
            ->first();

        return $variety ?: CropVariety::where('crop_id', $cropId)->first();
    }

    /**
     * Resolve raw market name to canonical Market model.
     */
    protected function resolveMarket(int $dataSourceId, string $sourceMarketName, ?string $districtName = null): ?Market
    {
        $mappingQuery = MarketSourceMapping::where('data_source_id', $dataSourceId)
            ->where('source_market_name', $sourceMarketName);

        if ($districtName) {
            $exactWithDistrict = (clone $mappingQuery)->where('source_district_name', $districtName)->first();
            if ($exactWithDistrict && $exactWithDistrict->market) {
                $m = $exactWithDistrict->market;
                if ($m->district?->state?->code === 'KA' || $m->district?->state?->name === 'Karnataka') {
                    return $m;
                }
            }
        }

        $aliasMapping = $mappingQuery->whereNull('source_district_name')->first();
        if ($aliasMapping && $aliasMapping->market) {
            $m = $aliasMapping->market;
            if ($m->district?->state?->code === 'KA' || $m->district?->state?->name === 'Karnataka') {
                return $m;
            }
        }

        // Fallback: direct search in markets table strictly restricted to Karnataka
        $clean = trim($sourceMarketName);
        return Market::karnataka()
            ->where(function ($q) use ($clean) {
                $q->where('name', $clean)
                    ->orWhere('name', 'like', "{$clean} APMC%")
                    ->orWhere('name_kn', $clean);
            })
            ->first();
    }

    /**
     * Reprocess a single raw record from market_price_raw.
     *
     * @return array{success: bool, error?: string, price_id?: int, crop?: string, market?: string}
     */
    public function reprocessRawRecord(MarketPriceRaw $rawModel): array
    {
        $dataSource = $rawModel->dataSource;
        if (!$dataSource) {
            return ['success' => false, 'error' => 'Data source not found for this raw record.'];
        }

        $provider = DataSourceRegistry::make($dataSource);
        $record = $rawModel->payload;

        $normalized = $provider->normalize($record);
        if ($normalized === null) {
            $rawModel->update([
                'processing_status' => 'rejected',
                'processed_at' => Carbon::now(),
                'error_message' => 'Failed to parse record attributes (missing commodity or market).',
            ]);
            return ['success' => false, 'error' => $rawModel->error_message];
        }

        $crop = $this->resolveCrop($dataSource->id, $normalized['source_crop'], $normalized['source_variety'] ?? null);
        if (!$crop) {
            $rawModel->update([
                'processing_status' => 'rejected',
                'processed_at' => Carbon::now(),
                'error_message' => "Unmapped commodity alias: '{$normalized['source_crop']}'. Add mapping in admin.",
            ]);
            return ['success' => false, 'error' => $rawModel->error_message];
        }

        $variety = $this->resolveVariety($crop->id, $normalized['source_variety'] ?? null);

        $market = $this->resolveMarket($dataSource->id, $normalized['source_market'], $normalized['source_district'] ?? null);
        if (!$market) {
            $rawModel->update([
                'processing_status' => 'rejected',
                'processed_at' => Carbon::now(),
                'error_message' => "Unmapped market alias: '{$normalized['source_market']}'. Add mapping in admin.",
            ]);
            return ['success' => false, 'error' => $rawModel->error_message];
        }

        $modalPrice = (float) $normalized['modal_price'];
        $minPrice = (float) ($normalized['min_price'] ?? $modalPrice);
        $maxPrice = (float) ($normalized['max_price'] ?? $modalPrice);
        $arrivalQty = (float) ($normalized['arrival_quantity'] ?? 0);

        if ($modalPrice <= 0) {
            $rawModel->update([
                'processing_status' => 'rejected',
                'processed_at' => Carbon::now(),
                'error_message' => "Invalid price value: modal price ({$modalPrice}) must be greater than zero.",
            ]);
            return ['success' => false, 'error' => $rawModel->error_message];
        }

        if ($minPrice > $modalPrice) {
            $minPrice = $modalPrice;
        }
        if ($maxPrice < $modalPrice) {
            $maxPrice = $modalPrice;
        }

        $priceDate = Carbon::parse($normalized['price_date']);
        if ($priceDate->isFuture()) {
            $priceDate = Carbon::today();
        }

        $canonical = MarketPrice::updateOrCreate(
            [
                'crop_id' => $crop->id,
                'variety_id' => $variety?->id,
                'market_id' => $market->id,
                'price_date' => $priceDate->format('Y-m-d'),
                'data_source_id' => $dataSource->id,
            ],
            [
                'district_id' => $market->district_id,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'modal_price' => $modalPrice,
                'arrival_quantity' => $arrivalQty,
                'unit' => $normalized['unit'] ?? 'Quintal',
                'raw_record_id' => $rawModel->id,
            ]
        );

        $rawModel->update([
            'processing_status' => 'processed',
            'processed_at' => Carbon::now(),
            'error_message' => null,
        ]);

        return [
            'success' => true,
            'price_id' => $canonical->id,
            'crop' => $crop->name,
            'market' => $market->name,
        ];
    }

    /**
     * Batch reprocess rejected raw records.
     *
     * @return array{total: int, processed: int, still_rejected: int}
     */
    public function reprocessBatch(?int $dataSourceId = null, ?string $reasonKeyword = null): array
    {
        $query = MarketPriceRaw::where('processing_status', 'rejected');

        if ($dataSourceId) {
            $query->where('data_source_id', $dataSourceId);
        }

        if ($reasonKeyword) {
            $query->where('error_message', 'like', "%{$reasonKeyword}%");
        }

        $records = $query->limit(200)->get();
        $processed = 0;
        $stillRejected = 0;

        foreach ($records as $record) {
            $result = $this->reprocessRawRecord($record);
            if ($result['success']) {
                $processed++;
            } else {
                $stillRejected++;
            }
        }

        return [
            'total' => $records->count(),
            'processed' => $processed,
            'still_rejected' => $stillRejected,
        ];
    }
}
