<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Services\Analytics\HistoricalAnalyticsService;
use App\Services\DataSources\Ceda\CedaAgmarknetDataProvider;
use App\Services\Forecast\ForecastingEngineService;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MarketPriceController extends Controller
{
    public function __construct(
        protected MarketPriceIngestionService $ingestionService,
        protected HistoricalAnalyticsService $analyticsService,
        protected ForecastingEngineService $forecastingService
    ) {
    }

    /**
     * Display a listing of canonical market prices with filtering and analytics.
     */
    public function index(Request $request): View
    {
        $cropId = $request->query('crop_id');
        $marketId = $request->query('market_id');
        $districtId = $request->query('district_id');
        $dataSourceId = $request->query('data_source_id');
        $date = $request->query('date');
        $year = $request->query('year');
        $month = $request->query('month');
        $search = $request->query('search');

        $query = MarketPrice::with(['crop', 'variety', 'market.district', 'dataSource'])
            ->orderBy('price_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($cropId) {
            $query->where('crop_id', $cropId);
        }

        if ($marketId) {
            $query->where('market_id', $marketId);
        }

        if ($districtId) {
            $query->whereHas('market', fn ($m) => $m->where('district_id', $districtId));
        }

        if ($dataSourceId) {
            $query->where('data_source_id', $dataSourceId);
        }

        if ($date) {
            $query->where('price_date', $date);
        }

        if ($year) {
            $query->whereYear('price_date', (int) $year);
        }

        if ($month) {
            $query->whereMonth('price_date', (int) $month);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('crop', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('name_kn', 'like', "%{$search}%");
                })->orWhereHas('market', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                       ->orWhere('name_kn', 'like', "%{$search}%");
                });
            });
        }

        $prices = $query->paginate(25)->withQueryString();

        // High-level statistics
        $totalPrices = MarketPrice::count();
        $latestDate = MarketPrice::max('price_date') ?? Carbon::today()->toDateString();
        $latestCount = MarketPrice::where('price_date', $latestDate)->count();
        $activeMarketsCount = MarketPrice::where('price_date', $latestDate)->distinct('market_id')->count('market_id');
        $avgSpread = MarketPrice::where('price_date', $latestDate)
            ->whereNotNull('max_price')
            ->whereNotNull('min_price')
            ->selectRaw('AVG(max_price - min_price) as avg_spread')
            ->value('avg_spread') ?? 0;

        $crops = Crop::where('is_active', true)->orderBy('name')->get();
        $markets = Market::where('is_active', true)->with('district')->orderBy('name')->get();
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $dataSources = DataSource::orderBy('name')->get();

        // Available years for dropdown
        $availableYears = MarketPrice::selectRaw('DISTINCT YEAR(price_date) as yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->filter()
            ->values()
            ->all();

        // Month-by-month and year-by-year volume archive summaries
        $archiveBreakdown = MarketPrice::selectRaw('
                YEAR(price_date) as yr,
                MONTH(price_date) as mo,
                COUNT(id) as records_count,
                COUNT(DISTINCT market_id) as mandis_count,
                COUNT(DISTINCT crop_id) as crops_count,
                AVG(modal_price) as avg_modal,
                MIN(price_date) as min_date,
                MAX(price_date) as max_date
            ')
            ->groupByRaw('YEAR(price_date), MONTH(price_date)')
            ->orderByDesc('yr')
            ->orderByDesc('mo')
            ->get()
            ->map(function ($row) {
                $dt = Carbon::createFromDate($row->yr, $row->mo, 1);
                $records = (int) $row->records_count;
                return [
                    'year' => (int) $row->yr,
                    'month' => (int) $row->mo,
                    'month_name_en' => $dt->format('F'),
                    'month_name_kn' => match ((int) $row->mo) {
                        1 => 'ಜನವರಿ', 2 => 'ಫೆಬ್ರವರಿ', 3 => 'ಮಾರ್ಚ್', 4 => 'ಏಪ್ರಿಲ್',
                        5 => 'ಮೇ', 6 => 'ಜೂನ್', 7 => 'ಜುಲೈ', 8 => 'ಆಗಸ್ಟ್',
                        9 => 'ಸೆಪ್ಟೆಂಬರ್', 10 => 'ಅಕ್ಟೋಬರ್', 11 => 'ನವೆಂಬರ್', 12 => 'ಡಿಸೆಂಬರ್',
                        default => $dt->format('F'),
                    },
                    'records_count' => $records,
                    'mandis_count' => (int) $row->mandis_count,
                    'crops_count' => (int) $row->crops_count,
                    'avg_modal' => round((float) $row->avg_modal, 2),
                    'est_size_mb' => round(($records * 300) / (1024 * 1024), 2),
                    'min_date' => $row->min_date,
                    'max_date' => $row->max_date,
                ];
            });

        return view('admin.prices.index', compact(
            'prices',
            'totalPrices',
            'latestDate',
            'latestCount',
            'activeMarketsCount',
            'avgSpread',
            'crops',
            'markets',
            'districts',
            'dataSources',
            'availableYears',
            'archiveBreakdown',
            'cropId',
            'marketId',
            'districtId',
            'dataSourceId',
            'date',
            'year',
            'month',
            'search'
        ));
    }

    /**
     * Trigger on-demand sync from the admin prices view (single target date).
     */
    public function sync(Request $request): RedirectResponse|JsonResponse
    {
        $sourceId = $request->input('data_source_id');
        $date = $request->input('target_date', Carbon::today()->toDateString());
        $force = $request->boolean('force', false);
        $startTime = microtime(true);
        $syncStartTimestamp = Carbon::now()->subSeconds(2);

        $sources = $sourceId
            ? DataSource::where('id', $sourceId)->get()
            : DataSource::where('is_active', true)->get();

        $sourceBreakdown = [];
        $totalReceived = 0;
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalDuplicates = 0;
        $totalRejected = 0;

        foreach ($sources as $source) {
            $res = $this->ingestionService->ingest($source, [
                'force' => $force,
                'filters' => ['date' => $date],
            ]);

            $rec = (int) ($res['received'] ?? 0);
            $ins = (int) ($res['inserted'] ?? 0);
            $upd = (int) ($res['updated'] ?? 0);
            $dup = (int) ($res['duplicate'] ?? 0);
            $rej = (int) ($res['rejected'] ?? 0);

            $totalReceived += $rec;
            $totalInserted += $ins;
            $totalUpdated += $upd;
            $totalDuplicates += $dup;
            $totalRejected += $rej;

            $sourceBreakdown[] = [
                'id' => $source->id,
                'name' => $source->name,
                'code' => $source->code,
                'status' => $res['status'] ?? 'success',
                'received' => $rec,
                'inserted' => $ins,
                'updated' => $upd,
                'duplicate' => $dup,
                'rejected' => $rej,
                'duration_ms' => $res['duration_ms'] ?? 0,
            ];
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        // Fetch any quarantined rejected records during this sync
        $sourceIds = $sources->pluck('id')->toArray();
        $recentRejected = MarketPriceRaw::whereIn('data_source_id', $sourceIds)
            ->where('processing_status', 'rejected')
            ->where('received_at', '>=', $syncStartTimestamp)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'source_id' => $r->data_source_id,
                    'raw_crop' => $r->payload['commodity'] ?? $r->payload['Commodity'] ?? 'Unknown',
                    'raw_market' => $r->payload['market'] ?? $r->payload['Market'] ?? 'Unknown',
                    'raw_variety' => $r->payload['variety'] ?? $r->payload['Variety'] ?? null,
                    'error' => $r->error_message,
                ];
            })
            ->values();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'mode' => 'single',
                'target_date' => $date,
                'duration_ms' => $durationMs,
                'summary' => [
                    'received' => $totalReceived,
                    'inserted' => $totalInserted,
                    'updated' => $totalUpdated,
                    'duplicate' => $totalDuplicates,
                    'rejected' => $totalRejected,
                ],
                'sources' => $sourceBreakdown,
                'rejections' => $recentRejected,
                'message' => count($sources) === 1
                    ? "Synced {$sources->first()->name}: {$totalInserted} new canonical records, {$totalUpdated} updated, {$totalDuplicates} duplicates skipped."
                    : "Batch sync complete for {$date}: {$totalInserted} new records, {$totalUpdated} updated, {$totalDuplicates} duplicates skipped.",
            ]);
        }

        $msg = count($sources) === 1
            ? "Synced {$sources->first()->name}: {$totalInserted} new canonical records, {$totalUpdated} updated, {$totalDuplicates} duplicates skipped."
            : "Batch sync complete for {$date}: {$totalInserted} new records, {$totalUpdated} updated, {$totalDuplicates} duplicates skipped.";

        return redirect()->route('admin.prices.index')->with('success', $msg);
    }

    /**
     * Trigger historical date-range backfill and ingestion sync.
     */
    public function syncRange(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'data_source_id' => ['nullable', 'exists:data_sources,id'],
            'crop_id' => ['nullable', 'exists:crops,id'],
            'update_analytics' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
        ]);

        $fromDate = Carbon::parse($validated['from_date'])->toDateString();
        $toDate = Carbon::parse($validated['to_date'])->toDateString();
        $updateAnalytics = $request->boolean('update_analytics', true);
        $force = $request->boolean('force', false);
        $sourceId = $validated['data_source_id'] ?? null;
        $cropId = $validated['crop_id'] ?? null;
        $startTime = microtime(true);
        $syncStartTimestamp = Carbon::now()->subSeconds(2);

        // Configure extended execution limits for multi-year batch backfill (up to 6 years)
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        // Maximum span guard: Allow up to 6 years (2,192 days) of multi-year auction archives
        $diffDays = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate));
        if ($diffDays > 2192) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Date range cannot exceed 6 years (2,190 days) per backfill request.',
                ], 422);
            }
            return redirect()->route('admin.prices.index')
                ->with('error', 'Date range cannot exceed 6 years (2,190 days) per backfill request.');
        }

        $sources = $sourceId
            ? DataSource::where('id', $sourceId)->get()
            : DataSource::where('is_active', true)->get();

        $crops = $cropId
            ? Crop::where('id', $cropId)->get()
            : Crop::where('is_active', true)->get();

        $sourceBreakdown = [];
        $totalReceived = 0;
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalDuplicates = 0;
        $totalRejected = 0;

        foreach ($sources as $source) {
            $srcReceived = 0;
            $srcInserted = 0;
            $srcUpdated = 0;
            $srcDuplicates = 0;
            $srcRejected = 0;

            // For CEDA Agmarknet, map crops to commodity IDs
            if ($source->code === 'ceda_agmarknet') {
                foreach ($crops as $crop) {
                    $mappedCedaIds = $crop->sourceMappings()
                        ->where('data_source_id', $source->id)
                        ->pluck('source_crop_name')
                        ->filter(fn ($val) => is_numeric($val))
                        ->map(fn ($val) => (int) $val)
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($mappedCedaIds)) {
                        $mappedCedaIds = array_keys(array_filter(
                            CedaAgmarknetDataProvider::CEDA_COMMODITIES,
                            fn ($name) => strcasecmp($name, $crop->name) === 0 || stripos($crop->name, $name) !== false
                        ));
                    }

                    if (empty($mappedCedaIds)) {
                        $mappedCedaIds = [2]; // Default commodity
                    }

                    foreach ($mappedCedaIds as $cedaId) {
                        $res = $this->ingestionService->ingest($source, [
                            'force' => $force,
                            'filters' => [
                                'commodity_id' => $cedaId,
                                'from_date' => $fromDate,
                                'to_date' => $toDate,
                            ],
                        ]);

                        $srcReceived += (int) ($res['received'] ?? 0);
                        $srcInserted += (int) ($res['inserted'] ?? 0);
                        $srcUpdated += (int) ($res['updated'] ?? 0);
                        $srcDuplicates += (int) ($res['duplicate'] ?? 0);
                        $srcRejected += (int) ($res['rejected'] ?? 0);
                    }
                }
            } else {
                $targetCommodity = $cropId ? $crops->first()?->name : null;
                $res = $this->ingestionService->ingest($source, [
                    'force' => $force,
                    'filters' => array_filter([
                        'from_date' => $fromDate,
                        'to_date' => $toDate,
                        'date' => $toDate,
                        'crop_id' => $cropId,
                        'commodity' => $targetCommodity,
                    ]),
                ]);

                $srcReceived += (int) ($res['received'] ?? 0);
                $srcInserted += (int) ($res['inserted'] ?? 0);
                $srcUpdated += (int) ($res['updated'] ?? 0);
                $srcDuplicates += (int) ($res['duplicate'] ?? 0);
                $srcRejected += (int) ($res['rejected'] ?? 0);
            }

            $totalReceived += $srcReceived;
            $totalInserted += $srcInserted;
            $totalUpdated += $srcUpdated;
            $totalDuplicates += $srcDuplicates;
            $totalRejected += $srcRejected;

            $sourceBreakdown[] = [
                'id' => $source->id,
                'name' => $source->name,
                'code' => $source->code,
                'status' => $srcRejected > 0 ? 'partial' : 'success',
                'received' => $srcReceived,
                'inserted' => $srcInserted,
                'updated' => $srcUpdated,
                'duplicate' => $srcDuplicates,
                'rejected' => $srcRejected,
            ];
        }

        // Pre-aggregate daily & monthly statistics and forecasting if requested
        if ($updateAnalytics) {
            $start = Carbon::parse($fromDate);
            $end = Carbon::parse($toDate);

            // Compute daily statistics for each date in range
            $dayCursor = $start->copy();
            while ($dayCursor->lte($end)) {
                $this->analyticsService->computeDailyStatistics($dayCursor->toDateString(), $cropId);
                $dayCursor->addDay();
            }

            // Compute monthly statistics
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $yr = (int) $cursor->year;
                $mo = (int) $cursor->month;
                $this->analyticsService->computeMonthlyStatistics($yr, $mo, $cropId);
                $cursor->addMonth();
            }

            $this->analyticsService->updateSeasonalIndices();

            // Refresh & persist forecasts for processed crops
            try {
                $this->forecastingService->runAllForecasts();
            } catch (\Throwable $e) {
                Log::warning("Forecasting run failed: " . $e->getMessage());
            }
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        // Fetch any quarantined rejected records during this range sync
        $sourceIds = $sources->pluck('id')->toArray();
        $recentRejected = MarketPriceRaw::whereIn('data_source_id', $sourceIds)
            ->where('processing_status', 'rejected')
            ->where('received_at', '>=', $syncStartTimestamp)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'source_id' => $r->data_source_id,
                    'raw_crop' => $r->payload['commodity'] ?? $r->payload['Commodity'] ?? 'Unknown',
                    'raw_market' => $r->payload['market'] ?? $r->payload['Market'] ?? 'Unknown',
                    'raw_variety' => $r->payload['variety'] ?? $r->payload['Variety'] ?? null,
                    'error' => $r->error_message,
                ];
            })
            ->values();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'mode' => 'range',
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'duration_ms' => $durationMs,
                'summary' => [
                    'received' => $totalReceived,
                    'inserted' => $totalInserted,
                    'updated' => $totalUpdated,
                    'duplicate' => $totalDuplicates,
                    'rejected' => $totalRejected,
                ],
                'sources' => $sourceBreakdown,
                'rejections' => $recentRejected,
                'analytics_updated' => $updateAnalytics,
                'message' => "Historical backfill complete ({$fromDate} to {$toDate}): {$totalInserted} new records, {$totalUpdated} updated, {$totalDuplicates} duplicates, {$totalRejected} quarantined.",
            ]);
        }

        $msg = "Historical backfill complete ({$fromDate} to {$toDate}): {$totalInserted} new records inserted, {$totalUpdated} updated, {$totalDuplicates} duplicates skipped.";
        if ($updateAnalytics) {
            $msg .= " Monthly statistics and price projections have been refreshed.";
        }

        return redirect()->route('admin.prices.index')->with('success', $msg);
    }

    /**
     * Safely prune and delete historical market prices.
     */
    public function prune(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'strategy' => ['required', 'in:age,period'],
            'older_than_days' => ['required_if:strategy,age', 'nullable', 'integer', 'min:30'],
            'year' => ['required_if:strategy,period', 'nullable', 'integer'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $strategy = $validated['strategy'];
        $days = (int) ($validated['older_than_days'] ?? 365);
        $targetYear = $validated['year'] ?? null;
        $targetMonth = $validated['month'] ?? null;

        // 1. Build query for records to prune
        $pruneQuery = MarketPrice::query();

        if ($strategy === 'age') {
            $cutoffDate = Carbon::today()->subDays($days)->toDateString();
            $pruneQuery->where('price_date', '<', $cutoffDate);
            $scopeDescription = "older than {$days} days (before {$cutoffDate})";
        } else {
            $pruneQuery->whereYear('price_date', (int) $targetYear);
            if ($targetMonth) {
                $pruneQuery->whereMonth('price_date', (int) $targetMonth);
                $scopeDescription = "for period {$targetYear}-" . str_pad($targetMonth, 2, '0', STR_PAD_LEFT);
            } else {
                $scopeDescription = "for entire year {$targetYear}";
            }
        }

        $totalRecords = (clone $pruneQuery)->count();

        if ($totalRecords === 0) {
            return redirect()->route('admin.prices.index')
                ->with('info', "No daily market price records found matching criteria ({$scopeDescription}). No deletion performed.");
        }

        // 2. Pre-Aggregation Safety Guard: Ensure monthly statistics are compiled before deletion
        $affectedPeriods = (clone $pruneQuery)
            ->selectRaw('DISTINCT YEAR(price_date) as yr, MONTH(price_date) as mo')
            ->get();

        foreach ($affectedPeriods as $period) {
            $yr = (int) $period->yr;
            $mo = (int) $period->mo;
            $this->analyticsService->computeMonthlyStatistics($yr, $mo);
        }
        $this->analyticsService->updateSeasonalIndices();

        // 3. Chunked deletion to prevent table locks in cPanel
        $deletedCount = 0;
        $chunkSize = 2000;

        do {
            $chunkIds = (clone $pruneQuery)->limit($chunkSize)->pluck('id');
            if ($chunkIds->isEmpty()) {
                break;
            }

            $deletedInChunk = DB::transaction(function () use ($chunkIds) {
                return MarketPrice::whereIn('id', $chunkIds)->delete();
            });

            $deletedCount += $deletedInChunk;
        } while ($deletedInChunk > 0);

        // 4. Audit Log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'prune_market_prices',
            'auditable_type' => MarketPrice::class,
            'auditable_id' => null,
            'old_values' => [
                'strategy' => $strategy,
                'criteria' => $scopeDescription,
                'records_targeted' => $totalRecords,
            ],
            'new_values' => [
                'records_deleted' => $deletedCount,
                'monthly_statistics_preserved' => true,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.prices.index')
            ->with('success', "Safely pruned {$deletedCount} daily records ({$scopeDescription}). All monthly statistics, 'Best Months to Sell', and 5-year trends were preserved.");
    }
}
