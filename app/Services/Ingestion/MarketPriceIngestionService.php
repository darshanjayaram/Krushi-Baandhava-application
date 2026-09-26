<?php

namespace App\Services\Ingestion;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropSourceMapping;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Models\MarketSourceMapping;
use App\Models\State;
use App\Models\SyncLog;
use App\Models\SystemSetting;
use App\Services\DataSources\DataSourceRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketPriceIngestionService
{
    /**
     * In-memory cache for market source mappings.
     *
     * @var array<string, Market|null>
     */
    protected array $resolvedMarketsCache = [];

    /**
     * In-memory cache of Karnataka markets.
     *
     * @var \Illuminate\Database\Eloquent\Collection<int, Market>|null
     */
    protected ?\Illuminate\Database\Eloquent\Collection $karnatakaMarketsCache = null;

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

        $cropStats = [];

        try {
            $provider = DataSourceRegistry::make($dataSource);
            $rawRecords = iterator_to_array($provider->fetch($filters));
            $counts['received'] = count($rawRecords);

            foreach ($rawRecords as $record) {
                // Determine raw commodity name for crop tracking
                $rawCropName = trim((string)($record['commodity'] ?? ($record['Commodity'] ?? ($record['source_crop'] ?? 'Unknown'))));
                if (empty($rawCropName)) {
                    $rawCropName = 'Unknown';
                }

                // 1. Checksum deduplication
                $checksum = hash('sha256', json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                $existingRaw = MarketPriceRaw::where('data_source_id', $dataSource->id)
                    ->where('checksum', $checksum)
                    ->first();

                if ($existingRaw && !$force) {
                    $counts['duplicate']++;
                    if (!isset($cropStats[$rawCropName])) {
                        $cropStats[$rawCropName] = [
                            'crop_name' => $rawCropName,
                            'raw_name' => $rawCropName,
                            'crop_id' => null,
                            'photo_url' => asset('images/crops/arecanut.jpg'),
                            'received' => 0,
                            'inserted' => 0,
                            'updated' => 0,
                            'duplicate' => 0,
                            'rejected' => 0,
                            'skipped' => 0,
                            'status' => 'synced',
                            'rejection_reasons' => [],
                        ];
                    }
                    $cropStats[$rawCropName]['received']++;
                    $cropStats[$rawCropName]['duplicate']++;
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
                        if (($crop || $this->shouldAutoProvisionMasterData()) && ($market || $this->shouldAutoProvisionMasterData()) && $normalized['modal_price'] > 0) {
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
                    $err = 'Failed to parse record attributes (missing commodity or market).';
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => $err,
                    ]);
                    $counts['rejected']++;

                    if (!isset($cropStats[$rawCropName])) {
                        $cropStats[$rawCropName] = [
                            'crop_name' => $rawCropName,
                            'raw_name' => $rawCropName,
                            'crop_id' => null,
                            'photo_url' => asset('images/crops/arecanut.jpg'),
                            'received' => 0,
                            'inserted' => 0,
                            'updated' => 0,
                            'duplicate' => 0,
                            'rejected' => 0,
                            'skipped' => 0,
                            'status' => 'failed',
                            'rejection_reasons' => [],
                        ];
                    }
                    $cropStats[$rawCropName]['received']++;
                    $cropStats[$rawCropName]['rejected']++;
                    $cropStats[$rawCropName]['rejection_reasons'][] = $err;
                    continue;
                }

                $rawCropName = trim((string)($normalized['source_crop'] ?? $rawCropName));

                // 3. Resolve Canonical Crop
                $crop = $this->resolveCrop($dataSource->id, $normalized['source_crop'], $normalized['source_variety'] ?? null);
                if (!$crop && $this->shouldAutoProvisionMasterData()) {
                    $crop = $this->autoProvisionCrop($dataSource->id, $normalized['source_crop'], $normalized['source_variety'] ?? null);
                }
                $cropEntryKey = $crop ? $crop->name : $rawCropName;

                if (!isset($cropStats[$cropEntryKey])) {
                    $cropStats[$cropEntryKey] = [
                        'crop_name' => $cropEntryKey,
                        'raw_name' => $rawCropName,
                        'crop_id' => $crop?->id,
                        'photo_url' => $crop ? $crop->photo_url : asset('images/crops/arecanut.jpg'),
                        'received' => 0,
                        'inserted' => 0,
                        'updated' => 0,
                        'duplicate' => 0,
                        'rejected' => 0,
                        'skipped' => 0,
                        'status' => 'synced',
                        'rejection_reasons' => [],
                    ];
                }
                $cropStats[$cropEntryKey]['received']++;

                if (!$crop) {
                    $err = "Unmapped commodity alias: '{$normalized['source_crop']}'. Add mapping in admin.";
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => $err,
                    ]);
                    $counts['rejected']++;
                    $cropStats[$cropEntryKey]['rejected']++;
                    $cropStats[$cropEntryKey]['rejection_reasons'][] = $err;
                    continue;
                }

                // 3.1 Enforce Authority Source Isolation:
                // Coffee is strictly governed by Coffee Board of India (exclude APMC feeds)
                if ($crop->isCoffeeBoard() && $dataSource->code !== 'coffee_board') {
                    $rawModel->update([
                        'processing_status' => 'skipped',
                        'processed_at' => Carbon::now(),
                        'error_message' => "Skipped: Coffee commodity rates are strictly managed via Coffee Board of India data source.",
                    ]);
                    $counts['skipped'] = ($counts['skipped'] ?? 0) + 1;
                    $cropStats[$cropEntryKey]['skipped']++;
                    continue;
                }

                // Coconut & Copra are strictly governed by Coconut Development Board (exclude generic APMC feeds)
                if ($crop->isCoconutBoard() && $dataSource->code !== 'coconut_board') {
                    $rawModel->update([
                        'processing_status' => 'skipped',
                        'processed_at' => Carbon::now(),
                        'error_message' => "Skipped: Coconut/Copra commodity rates are strictly managed via Coconut Development Board data source.",
                    ]);
                    $counts['skipped'] = ($counts['skipped'] ?? 0) + 1;
                    $cropStats[$cropEntryKey]['skipped']++;
                    continue;
                }

                // 4. Resolve Canonical Variety
                $variety = $this->resolveVariety($crop->id, $normalized['source_variety'] ?? null, $dataSource->id);
                if (!$variety && $this->shouldAutoProvisionMasterData()) {
                    $variety = $this->autoProvisionVariety($crop->id, $normalized['source_variety'] ?? null, $dataSource->id, $normalized['source_crop']);
                }

                // 5. Resolve Canonical Market
                $market = $this->resolveMarket($dataSource->id, $normalized['source_market'], $normalized['source_district'] ?? null);
                if (!$market && $this->shouldAutoProvisionMasterData()) {
                    $market = $this->autoProvisionMarket($dataSource->id, $normalized['source_market'], $normalized['source_district'] ?? null);
                }
                if (!$market) {
                    $err = "Unmapped market alias: '{$normalized['source_market']}'. Add mapping in admin.";
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => $err,
                    ]);
                    $counts['rejected']++;
                    $cropStats[$cropEntryKey]['rejected']++;
                    $cropStats[$cropEntryKey]['rejection_reasons'][] = $err;
                    continue;
                }

                // 6. Sanity Validations
                $modalPrice = (float) $normalized['modal_price'];
                $minPrice = (float) ($normalized['min_price'] ?? $modalPrice);
                $maxPrice = (float) ($normalized['max_price'] ?? $modalPrice);
                $arrivalQty = (float) ($normalized['arrival_quantity'] ?? 0);

                if ($modalPrice <= 0) {
                    $err = "Invalid price value: modal price ({$modalPrice}) must be greater than zero.";
                    $rawModel->update([
                        'processing_status' => 'rejected',
                        'processed_at' => Carbon::now(),
                        'error_message' => $err,
                    ]);
                    $counts['rejected']++;
                    $cropStats[$cropEntryKey]['rejected']++;
                    $cropStats[$cropEntryKey]['rejection_reasons'][] = $err;
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
                $varietyKey = $variety?->id ?? 0;
                $canonical = MarketPrice::updateOrCreate(
                    [
                        'crop_id'         => $crop->id,
                        'variety_id_key'  => $varietyKey,
                        'market_id'       => $market->id,
                        'price_date'      => $priceDate->format('Y-m-d'),
                    ],
                    [
                        'variety_id'       => $variety?->id,
                        'district_id'      => $market->district_id,
                        'data_source_id'   => $dataSource->id,
                        'min_price'        => $minPrice,
                        'max_price'        => $maxPrice,
                        'modal_price'      => $modalPrice,
                        'arrival_quantity' => $arrivalQty,
                        'unit'             => $normalized['unit'] ?? 'Quintal',
                        'raw_record_id'    => $rawModel->id,
                    ]
                );

                if ($canonical->wasRecentlyCreated) {
                    $counts['inserted']++;
                    $cropStats[$cropEntryKey]['inserted']++;
                } else {
                    $counts['updated']++;
                    $cropStats[$cropEntryKey]['updated']++;
                }

                $rawModel->update([
                    'processing_status' => 'processed',
                    'processed_at' => Carbon::now(),
                ]);
            }

            // Finalize per-crop status
            foreach ($cropStats as &$cs) {
                $cs['rejection_reasons'] = array_values(array_unique($cs['rejection_reasons']));
                if ($cs['rejected'] > 0 && ($cs['inserted'] + $cs['updated']) === 0) {
                    $cs['status'] = 'failed';
                } elseif ($cs['rejected'] > 0) {
                    $cs['status'] = 'partial';
                } elseif ($cs['skipped'] > 0 && ($cs['inserted'] + $cs['updated']) === 0) {
                    $cs['status'] = 'skipped';
                } else {
                    $cs['status'] = 'synced';
                }
            }
            unset($cs);

            $durationMs = max(0, (int) round((microtime(true) - $startTime) * 1000));
            $finalStatus = ($counts['rejected'] > 0 && $counts['inserted'] === 0 && $counts['updated'] === 0)
                ? 'failed'
                : (($counts['rejected'] > 0) ? 'partial' : 'success');

            $cropsBreakdown = array_values($cropStats);

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
                        'crops_breakdown' => $cropsBreakdown,
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
                'crops_breakdown' => $cropsBreakdown,
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

        // Global verified mapping fallback across any data source
        $globalCropMapping = CropSourceMapping::where('source_crop_name', $sourceCropName)
            ->where('is_verified', true)
            ->first();
        if ($globalCropMapping && $globalCropMapping->crop) {
            return $globalCropMapping->crop;
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
    protected function resolveVariety(int $cropId, ?string $varietyName, ?int $dataSourceId = null): ?CropVariety
    {
        if (empty($varietyName)) {
            return CropVariety::where('crop_id', $cropId)->first();
        }

        $clean = trim($varietyName);

        // 1. Check explicit variety mapping in crop_source_mappings for this specific data source
        if ($dataSourceId) {
            $mapping = CropSourceMapping::where('data_source_id', $dataSourceId)
                ->where('crop_id', $cropId)
                ->where('source_variety_name', $clean)
                ->whereNotNull('crop_variety_id')
                ->first();

            if ($mapping && $mapping->variety) {
                return $mapping->variety;
            }
        }

        // 2. Check global verified variety mapping across any data source
        $globalMapping = CropSourceMapping::where('crop_id', $cropId)
            ->where('source_variety_name', $clean)
            ->whereNotNull('crop_variety_id')
            ->where('is_verified', true)
            ->first();

        if ($globalMapping && $globalMapping->variety) {
            return $globalMapping->variety;
        }

        // 3. Exact name / slug / Kannada name direct lookup
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
     * Resolve raw market name to canonical Market model with in-memory caching and intelligent matching.
     */
    public function resolveMarket(int $dataSourceId, string $sourceMarketName, ?string $districtName = null): ?Market
    {
        $cleanMarket = trim($sourceMarketName);
        $cleanDistrict = $districtName ? trim($districtName) : null;
        $cacheKey = "{$dataSourceId}:{$cleanMarket}:" . ($cleanDistrict ?? '');

        if (array_key_exists($cacheKey, $this->resolvedMarketsCache)) {
            return $this->resolvedMarketsCache[$cacheKey];
        }

        // Preload Karnataka markets into memory if not loaded yet
        if ($this->karnatakaMarketsCache === null) {
            $this->karnatakaMarketsCache = Market::karnataka()
                ->with(['district.state'])
                ->get();
        }

        // 1. Direct DataSource mapping with District match
        if ($cleanDistrict) {
            $exactWithDistrict = MarketSourceMapping::where('data_source_id', $dataSourceId)
                ->where('source_market_name', $cleanMarket)
                ->where('source_district_name', $cleanDistrict)
                ->with(['market.district.state'])
                ->first();

            if ($exactWithDistrict && $exactWithDistrict->market) {
                $m = $exactWithDistrict->market;
                if ($m->district?->state?->code === 'KA' || $m->district?->state?->name === 'Karnataka') {
                    return $this->resolvedMarketsCache[$cacheKey] = $m;
                }
            }
        }

        // 2. Direct DataSource mapping without District match
        $aliasMapping = MarketSourceMapping::where('data_source_id', $dataSourceId)
            ->where('source_market_name', $cleanMarket)
            ->whereNull('source_district_name')
            ->with(['market.district.state'])
            ->first();

        if ($aliasMapping && $aliasMapping->market) {
            $m = $aliasMapping->market;
            if ($m->district?->state?->code === 'KA' || $m->district?->state?->name === 'Karnataka') {
                return $this->resolvedMarketsCache[$cacheKey] = $m;
            }
        }

        // 3. Global verified mapping fallback across any data source
        $globalKey = "global:{$cleanMarket}";
        if (array_key_exists($globalKey, $this->resolvedMarketsCache)) {
            $m = $this->resolvedMarketsCache[$globalKey];
            if ($m !== null) {
                return $this->resolvedMarketsCache[$cacheKey] = $m;
            }
        } else {
            $globalMarketMapping = MarketSourceMapping::where('source_market_name', $cleanMarket)
                ->where('is_verified', true)
                ->with(['market.district.state'])
                ->first();

            if ($globalMarketMapping && $globalMarketMapping->market) {
                $m = $globalMarketMapping->market;
                if ($m->district?->state?->code === 'KA' || $m->district?->state?->name === 'Karnataka') {
                    $this->resolvedMarketsCache[$globalKey] = $m;
                    return $this->resolvedMarketsCache[$cacheKey] = $m;
                }
            }
            $this->resolvedMarketsCache[$globalKey] = null;
        }

        // 4. In-memory intelligent matching against Karnataka markets
        $normalizedSearch = strtolower(trim(preg_replace('/\b(apmc|mandi|market)\b/i', '', $cleanMarket)));

        foreach ($this->karnatakaMarketsCache as $mandi) {
            $mName = strtolower(trim(preg_replace('/\b(apmc|mandi|market)\b/i', '', $mandi->name)));
            if (
                $mName === $normalizedSearch ||
                strtolower($mandi->slug) === Str::slug($cleanMarket) ||
                $mandi->name_kn === $cleanMarket ||
                str_starts_with($mName, $normalizedSearch) ||
                str_starts_with($normalizedSearch, $mName)
            ) {
                // If district name is provided, verify it matches the market's district if possible
                if ($cleanDistrict && $mandi->district) {
                    $distClean = strtolower(trim(preg_replace('/\b(district|dist)\b/i', '', $cleanDistrict)));
                    $mDistClean = strtolower(trim(preg_replace('/\b(district|dist)\b/i', '', $mandi->district->name)));
                    if ($distClean === $mDistClean || str_contains($mDistClean, $distClean) || str_contains($distClean, $mDistClean)) {
                        return $this->resolvedMarketsCache[$cacheKey] = $mandi;
                    }
                } else {
                    return $this->resolvedMarketsCache[$cacheKey] = $mandi;
                }
            }
        }

        // 5. Fallback: direct search in markets table strictly restricted to Karnataka
        $clean = trim($sourceMarketName);
        $market = Market::karnataka()
            ->where(function ($q) use ($clean) {
                $q->where('name', $clean)
                    ->orWhere('name', 'like', "{$clean} APMC%")
                    ->orWhere('name_kn', $clean);
            })
            ->first();

        return $this->resolvedMarketsCache[$cacheKey] = $market;
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
        if (!$crop && $this->shouldAutoProvisionMasterData()) {
            $crop = $this->autoProvisionCrop($dataSource->id, $normalized['source_crop'], $normalized['source_variety'] ?? null);
        }
        if (!$crop) {
            $rawModel->update([
                'processing_status' => 'rejected',
                'processed_at' => Carbon::now(),
                'error_message' => "Unmapped commodity alias: '{$normalized['source_crop']}'. Add mapping in admin.",
            ]);
            return ['success' => false, 'error' => $rawModel->error_message];
        }

        $variety = $this->resolveVariety($crop->id, $normalized['source_variety'] ?? null, $dataSource->id);
        if (!$variety && $this->shouldAutoProvisionMasterData()) {
            $variety = $this->autoProvisionVariety($crop->id, $normalized['source_variety'] ?? null, $dataSource->id, $normalized['source_crop']);
        }

        $market = $this->resolveMarket($dataSource->id, $normalized['source_market'], $normalized['source_district'] ?? null);
        if (!$market && $this->shouldAutoProvisionMasterData()) {
            $market = $this->autoProvisionMarket($dataSource->id, $normalized['source_market'], $normalized['source_district'] ?? null);
        }
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

        $varietyKey = $variety?->id ?? 0;
        $canonical = MarketPrice::updateOrCreate(
            [
                'crop_id' => $crop->id,
                'variety_id_key' => $varietyKey,
                'market_id' => $market->id,
                'price_date' => $priceDate->format('Y-m-d'),
            ],
            [
                'variety_id' => $variety?->id,
                'data_source_id' => $dataSource->id,
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

    /**
     * Reprocess rejected raw records specifically for a commodity name.
     *
     * @return array{total: int, processed: int, still_rejected: int, errors: array<string>}
     */
    public function reprocessCrop(int $dataSourceId, string $commodityName): array
    {
        $records = MarketPriceRaw::where('data_source_id', $dataSourceId)
            ->where('processing_status', 'rejected')
            ->where(function ($q) use ($commodityName) {
                $q->where('error_message', 'like', "%'{$commodityName}'%")
                  ->orWhere('payload', 'like', "%\"{$commodityName}\"%")
                  ->orWhere('payload', 'like', "%'{$commodityName}'%");
            })
            ->limit(200)
            ->get();

        $processed = 0;
        $stillRejected = 0;
        $errors = [];

        foreach ($records as $record) {
            $result = $this->reprocessRawRecord($record);
            if ($result['success']) {
                $processed++;
            } else {
                $stillRejected++;
                if (!empty($result['error'])) {
                    $errors[] = $result['error'];
                }
            }
        }

        return [
            'total' => $records->count(),
            'processed' => $processed,
            'still_rejected' => $stillRejected,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * Determine if master data auto-provisioning is enabled.
     */
    protected function shouldAutoProvisionMasterData(): bool
    {
        return (bool) SystemSetting::get('auto_provision_master_data', true);
    }

    /**
     * Auto-provision a missing canonical crop from an incoming raw commodity name.
     */
    protected function autoProvisionCrop(int $dataSourceId, string $sourceCropName, ?string $sourceVarietyName = null): ?Crop
    {
        $raw = trim($sourceCropName);
        if ($raw === '') {
            return null;
        }

        // Clean redundant bracketed terms: "Paddy(Dhan)(Common)" -> "Paddy", "Arecanut(Betelnut/Supari)" -> "Arecanut"
        $clean = preg_replace('/\([^)]+\)/', '', $raw);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if ($clean === '') {
            $clean = $raw;
        }

        // Check if matching crop already exists under clean name or slug
        $crop = Crop::where('name', $clean)
            ->orWhere('slug', Str::slug($clean))
            ->orWhere('name', 'like', "%{$clean}%")
            ->first();

        if (!$crop) {
            $defaultCategory = CropCategory::firstOrCreate(
                ['slug' => 'commercial-crops'],
                ['name' => 'Commercial & Field Crops', 'name_kn' => 'ವಾಣಿಜ್ಯ ಮತ್ತು ಕ್ಷೇತ್ರ ಬೆಳೆಗಳು', 'is_active' => true]
            );

            $crop = Crop::create([
                'name' => $clean,
                'name_kn' => $clean,
                'slug' => Str::slug($clean) ?: 'crop-' . uniqid(),
                'category_id' => $defaultCategory->id,
                'standard_unit' => 'Quintal',
                'is_active' => true,
            ]);
        }

        // Automatically create source mapping so future records resolve instantly
        CropSourceMapping::firstOrCreate(
            [
                'data_source_id' => $dataSourceId,
                'source_crop_name' => $raw,
            ],
            [
                'crop_id' => $crop->id,
                'is_verified' => true,
            ]
        );

        return $crop;
    }

    /**
     * Auto-provision a missing canonical variety for a crop.
     */
    protected function autoProvisionVariety(int $cropId, ?string $sourceVarietyName, int $dataSourceId, ?string $sourceCropName = null): ?CropVariety
    {
        $vName = $sourceVarietyName ? trim($sourceVarietyName) : 'General / Local';
        $vName = preg_replace('/\([^)]+\)/', '', $vName);
        $vName = trim(preg_replace('/\s+/', ' ', $vName)) ?: 'General';

        $variety = CropVariety::firstOrCreate(
            [
                'crop_id' => $cropId,
                'name' => $vName,
            ],
            [
                'name_kn' => $vName,
                'slug' => Str::slug($vName) . '-' . $cropId,
                'is_active' => true,
            ]
        );

        if ($sourceVarietyName && $sourceCropName) {
            CropSourceMapping::firstOrCreate(
                [
                    'data_source_id' => $dataSourceId,
                    'crop_id' => $cropId,
                    'source_crop_name' => trim($sourceCropName),
                    'source_variety_name' => trim($sourceVarietyName),
                ],
                [
                    'crop_variety_id' => $variety->id,
                    'is_verified' => true,
                ]
            );
        }

        return $variety;
    }

    /**
     * Auto-provision a missing Karnataka APMC market and district.
     */
    protected function autoProvisionMarket(int $dataSourceId, string $sourceMarketName, ?string $sourceDistrictName = null): ?Market
    {
        $cleanMandi = trim($sourceMarketName);
        if ($cleanMandi === '') {
            return null;
        }

        $karnataka = State::where('code', 'KA')->first()
            ?? State::firstOrCreate(['code' => 'KA'], ['name' => 'Karnataka', 'name_kn' => 'ಕರ್ನಾಟಕ']);

        // Resolve or auto-create district
        $distRaw = $sourceDistrictName ? trim($sourceDistrictName) : 'Karnataka';
        $distClean = trim(preg_replace('/\b(district|dist)\b/i', '', $distRaw));

        $district = District::where('state_id', $karnataka->id)
            ->where(function ($q) use ($distClean) {
                $q->where('name', $distClean)
                    ->orWhere('name', 'like', "%{$distClean}%");
            })
            ->first();

        if (!$district) {
            $district = District::create([
                'state_id' => $karnataka->id,
                'name' => $distClean ?: 'Karnataka',
                'name_kn' => $distClean ?: 'ಕರ್ನಾಟಕ',
                'code' => substr('KA_' . strtoupper(Str::slug($distClean ?: 'dist', '_')), 0, 20),
                'latitude' => 13.9299,
                'longitude' => 75.5681,
            ]);
        }

        // Clean market name (e.g. "Tumkur" -> "Tumkur APMC")
        $baseName = trim(preg_replace('/\b(apmc|mandi|market)\b/i', '', $cleanMandi));
        $marketName = $baseName . ' APMC';

        $market = Market::where('district_id', $district->id)
            ->where(function ($q) use ($baseName, $marketName) {
                $q->where('name', $marketName)
                    ->orWhere('name', 'like', "%{$baseName}%");
            })
            ->first();

        if (!$market) {
            $market = Market::create([
                'district_id' => $district->id,
                'name' => $marketName,
                'name_kn' => $marketName,
                'code' => substr('KA_APMC_' . strtoupper(Str::slug($baseName, '_')), 0, 50),
                'market_type' => 'APMC Mandi',
                'latitude' => $district->latitude ?? 13.9299,
                'longitude' => $district->longitude ?? 75.5681,
                'is_active' => true,
            ]);
        }

        // Auto-create source mapping
        MarketSourceMapping::firstOrCreate(
            [
                'data_source_id' => $dataSourceId,
                'source_market_name' => $cleanMandi,
            ],
            [
                'market_id' => $market->id,
                'source_district_name' => $sourceDistrictName ? trim($sourceDistrictName) : null,
                'is_verified' => true,
            ]
        );

        // Update in-memory caches
        $cacheKey = "{$dataSourceId}:{$cleanMandi}:" . ($sourceDistrictName ? trim($sourceDistrictName) : '');
        $this->resolvedMarketsCache[$cacheKey] = $market;
        if ($this->karnatakaMarketsCache !== null) {
            $this->karnatakaMarketsCache->push($market);
        }

        return $market;
    }
}
