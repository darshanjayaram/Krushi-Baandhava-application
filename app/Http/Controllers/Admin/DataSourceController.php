<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataSourceRequest;
use App\Models\ApiHealthLog;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\CropCategory;
use App\Models\DataSource;
use App\Models\DataSourceCredential;
use App\Models\DataSourceCropSync;
use App\Models\SyncLog;
use App\Models\SystemSetting;
use App\Services\DataSources\DataSourceRegistry;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DataSourceController extends Controller
{
    public function index(): View
    {
        $dataSources = DataSource::with(['credential', 'mappings'])
            ->withCount([
                'syncLogs',
                'healthLogs',
                'cropSyncs as total_configured_crops',
                'cropSyncs as active_configured_crops' => fn ($q) => $q->where('is_enabled', true),
            ])
            ->orderBy('id', 'asc')
            ->paginate(15);

        $stats = [
            'total' => DataSource::count(),
            'active' => DataSource::where('is_active', true)->count(),
            'cron_enabled' => DataSource::where('is_active', true)->where('is_cron_enabled', true)->count(),
            'synced_today' => SyncLog::whereDate('started_at', Carbon::today())->where('status', 'success')->count(),
            'failed_recent' => SyncLog::where('status', 'failed')->where('started_at', '>=', now()->subDays(7))->count(),
        ];

        $rawHeartbeat = \Illuminate\Support\Facades\Cache::get('scheduler_last_heartbeat') 
            ?? SystemSetting::get('scheduler_last_heartbeat')
            ?? DataSource::max('last_heartbeat_at');
        $lastHeartbeat = $rawHeartbeat ? Carbon::parse($rawHeartbeat) : null;
        $isCronActive = $lastHeartbeat && $lastHeartbeat->diffInMinutes(now()) <= 15;

        $cronInfo = [
            'php_binary' => PHP_BINARY,
            'base_path' => base_path(),
            'artisan_path' => base_path('artisan'),
            'cpanel_command' => "* * * * * /usr/local/bin/php " . base_path('artisan') . " schedule:run >> " . storage_path('logs/cron.log') . " 2>&1",
            'standard_command' => "* * * * * cd " . base_path() . " && php artisan schedule:run >> " . storage_path('logs/cron.log') . " 2>&1",
            'last_heartbeat' => $lastHeartbeat,
            'is_active' => $isCronActive,
            'morning_time' => SystemSetting::get('cron_market_morning_time', '06:00'),
            'evening_time' => SystemSetting::get('cron_market_evening_time', '19:30'),
            'afternoon_time' => SystemSetting::get('cron_market_afternoon_time', '12:30'),
            'enable_hourly' => (bool) SystemSetting::get('cron_market_enable_hourly', true),
            'operating_days' => SystemSetting::get('cron_market_operating_days', 'mon_sat'),
            'cron_enabled_count' => DataSource::where('is_active', true)->where('is_cron_enabled', true)->count(),
            'all_sources' => DataSource::orderBy('id', 'asc')->get(),
            'scheduled_tasks' => [
                'mandi_prices' => [
                    'key' => 'mandi_prices',
                    'name' => '🌾 Mandi Market Prices Ingestion',
                    'title' => 'Mandi Market Prices Ingestion',
                    'icon' => '🌾',
                    'setting_key' => 'cron_task_mandi_prices',
                    'frequency' => (SystemSetting::get('cron_market_morning_time', '06:00') ?: '06:00') . (!empty(SystemSetting::get('cron_market_afternoon_time', '12:30')) ? ', ' . SystemSetting::get('cron_market_afternoon_time', '12:30') : '') . ' & ' . (SystemSetting::get('cron_market_evening_time', '19:30') ?: '19:30') . ' IST',
                    'purpose' => 'Syncs active Mandi feeds (KRAMA, Agmarknet, Coffee Board & Coconut Board)',
                    'desc' => 'Syncs active Mandi feeds (KRAMA, Agmarknet, Coffee Board & Coconut Board)',
                    'is_active' => (bool) SystemSetting::get('cron_task_mandi_prices', true),
                    'enabled' => (bool) SystemSetting::get('cron_task_mandi_prices', true),
                ],
                'weather_sync' => [
                    'key' => 'weather_sync',
                    'name' => '🌦️ Hyperlocal Weather Advisories & Cache Pruning',
                    'title' => 'Hyperlocal Weather Advisories & Cache Pruning',
                    'icon' => '🌦️',
                    'setting_key' => 'cron_task_weather_sync',
                    'frequency' => '05:30 & 14:30 IST Daily',
                    'purpose' => 'Updates 7-day agricultural forecasts via Open-Meteo & prunes cache',
                    'desc' => 'Updates 7-day agricultural forecasts via Open-Meteo & prunes cache',
                    'is_active' => (bool) SystemSetting::get('cron_task_weather_sync', true),
                    'enabled' => (bool) SystemSetting::get('cron_task_weather_sync', true),
                ],
                'analytics_stats' => [
                    'key' => 'analytics_stats',
                    'name' => '📊 Historical Analytics & Seasonality',
                    'title' => 'Historical Analytics & Seasonality',
                    'icon' => '📊',
                    'setting_key' => 'cron_task_analytics_stats',
                    'frequency' => '01:00 IST Nightly',
                    'purpose' => 'Computes 12-month seasonal indices & modal averages',
                    'desc' => 'Computes 12-month seasonal indices & modal averages',
                    'is_active' => (bool) SystemSetting::get('cron_task_analytics_stats', true),
                    'enabled' => (bool) SystemSetting::get('cron_task_analytics_stats', true),
                ],
                'forecasting' => [
                    'key' => 'forecasting',
                    'name' => '🔮 Price Forecasting Engine',
                    'title' => 'Price Forecasting Engine',
                    'icon' => '🔮',
                    'setting_key' => 'cron_task_forecasting',
                    'frequency' => '02:00 IST Nightly',
                    'purpose' => 'Generates 1D, 7D, 15D, 30D Holt\'s Linear projections',
                    'desc' => 'Generates 1D, 7D, 15D, 30D Holt\'s Linear projections',
                    'is_active' => (bool) SystemSetting::get('cron_task_forecasting', true),
                    'enabled' => (bool) SystemSetting::get('cron_task_forecasting', true),
                ],
                'retention_pruning' => [
                    'key' => 'retention_pruning',
                    'name' => '🧹 1-Year Rolling Retention Pruner',
                    'title' => '1-Year Rolling Retention Pruner',
                    'icon' => '🧹',
                    'setting_key' => 'cron_task_retention_pruning',
                    'frequency' => '23:00 IST Nightly',
                    'purpose' => 'Prunes records >365 days; keeps database fast (~35MB)',
                    'desc' => 'Prunes records >365 days; keeps database fast (~35MB)',
                    'is_active' => (bool) SystemSetting::get('cron_task_retention_pruning', true),
                    'enabled' => (bool) SystemSetting::get('cron_task_retention_pruning', true),
                ],
                'data_integrity' => [
                    'key' => 'data_integrity',
                    'name' => '🛡️ Data Integrity Auditor',
                    'title' => 'Data Integrity Auditor',
                    'icon' => '🛡️',
                    'setting_key' => 'cron_task_data_integrity',
                    'frequency' => '03:00 IST Nightly',
                    'purpose' => 'Flags anomalously stale or isolated price points',
                    'desc' => 'Flags anomalously stale or isolated price points',
                    'is_active' => (bool) SystemSetting::get('cron_task_data_integrity', true),
                    'enabled' => (bool) SystemSetting::get('cron_task_data_integrity', true),
                ],
            ],
        ];

        $canonicalCrops = Crop::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_kn', 'slug']);

        return view('admin.datasources.index', compact('dataSources', 'stats', 'cronInfo', 'canonicalCrops'));
    }

    public function create(): View
    {
        $providers = DataSourceRegistry::getAvailableProviders();
        return view('admin.datasources.form', compact('providers'));
    }

    public function store(DataSourceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_cron_enabled'] = $request->boolean('is_cron_enabled', true);

        $dataSource = DataSource::create($validated);

        if (!empty($validated['api_key']) || !empty($validated['client_id']) || !empty($validated['client_secret'])) {
            DataSourceCredential::create([
                'data_source_id' => $dataSource->id,
                'api_key' => $validated['api_key'] ?? null,
                'client_id' => $validated['client_id'] ?? null,
                'client_secret' => $validated['client_secret'] ?? null,
            ]);
        }

        AuditLog::log(
            'create',
            'DataSource',
            $dataSource->id,
            null,
            $dataSource->toArray()
        );

        return redirect()->route('admin.datasources.index')
            ->with('success', "Data source '{$dataSource->name}' registered successfully.");
    }

    public function edit(DataSource $datasource): View
    {
        $datasource->load('credential');
        $providers = DataSourceRegistry::getAvailableProviders();

        return view('admin.datasources.form', compact('datasource', 'providers'));
    }

    public function update(DataSourceRequest $request, DataSource $datasource): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);
        if ($request->has('is_cron_enabled')) {
            $validated['is_cron_enabled'] = $request->boolean('is_cron_enabled');
        }
        $oldValues = $datasource->toArray();

        $datasource->update($validated);

        // Update credentials only if provided and not masked placeholder
        $apiKey = $request->input('api_key');
        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        $isMasked = fn(?string $val) => $val && str_contains($val, '****');

        if (($apiKey && !$isMasked($apiKey)) || ($clientSecret && !$isMasked($clientSecret)) || ($clientId && !$isMasked($clientId))) {
            $credential = $datasource->credential ?? new DataSourceCredential(['data_source_id' => $datasource->id]);

            if ($apiKey && !$isMasked($apiKey)) {
                $credential->api_key = $apiKey;
            }
            if ($clientId && !$isMasked($clientId)) {
                $credential->client_id = $clientId;
            }
            if ($clientSecret && !$isMasked($clientSecret)) {
                $credential->client_secret = $clientSecret;
            }

            $credential->save();
        }

        AuditLog::log(
            'update',
            'DataSource',
            $datasource->id,
            $oldValues,
            $datasource->fresh()->toArray()
        );

        return redirect()->route('admin.datasources.index')
            ->with('success', "Data source '{$datasource->name}' updated successfully.");
    }

    public function toggleStatus(Request $request, DataSource $datasource): JsonResponse|RedirectResponse
    {
        $oldStatus = $datasource->is_active;
        $datasource->update(['is_active' => !$oldStatus]);

        AuditLog::log(
            'toggle_status',
            'DataSource',
            $datasource->id,
            ['is_active' => $oldStatus],
            ['is_active' => !$oldStatus]
        );

        $msg = $datasource->is_active
            ? "Data source '{$datasource->name}' resumed."
            : "Data source '{$datasource->name}' paused.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'is_active' => (bool) $datasource->is_active,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function toggleCron(Request $request, DataSource $datasource): JsonResponse|RedirectResponse
    {
        $oldCron = (bool) $datasource->is_cron_enabled;
        $newCron = !$oldCron;
        $datasource->update(['is_cron_enabled' => $newCron]);

        AuditLog::log(
            'toggle_cron',
            'DataSource',
            $datasource->id,
            ['is_cron_enabled' => $oldCron],
            ['is_cron_enabled' => $newCron]
        );

        $msg = $newCron
            ? "Data source '{$datasource->name}' enrolled in automated cron schedule."
            : "Data source '{$datasource->name}' excluded from automated cron schedule.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'is_cron_enabled' => $newCron,
                'message' => $msg,
                'cron_enabled_count' => DataSource::where('is_active', true)->where('is_cron_enabled', true)->count(),
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Toggle automatic live price failover from KRAMA to AGMARKNET.
     */
    public function toggleKramaFailover(Request $request): JsonResponse|RedirectResponse
    {
        $current = (bool) SystemSetting::get('krama_agmarknet_failover_enabled', true);
        $newValue = $request->has('enabled') ? $request->boolean('enabled') : !$current;

        SystemSetting::set(
            'krama_agmarknet_failover_enabled',
            $newValue,
            'boolean',
            'data_sources',
            'Automatic Failover to Official AGMARKNET when KRAMA live price feed is unavailable or returns 0 records'
        );

        AuditLog::log(
            'toggle_setting',
            'SystemSetting',
            null,
            ['krama_agmarknet_failover_enabled' => $current],
            ['krama_agmarknet_failover_enabled' => $newValue]
        );

        $msg = $newValue
            ? "Automatic live price failover to AGMARKNET is enabled. If KRAMA returns 0 records, prices will automatically fetch from AGMARKNET."
            : "Automatic live price failover to AGMARKNET is disabled. KRAMA will not automatically fall back to AGMARKNET.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'enabled' => $newValue,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Test connection to provider endpoint and return health diagnostic.
     */
    public function testConnection(Request $request, DataSource $datasource): JsonResponse|RedirectResponse
    {
        try {
            $provider = DataSourceRegistry::make($datasource);
            $health = $provider->healthCheck();

            $log = ApiHealthLog::create([
                'data_source_id' => $datasource->id,
                'http_status' => $health['http_status'],
                'response_time_ms' => $health['response_time_ms'],
                'auth_result' => $health['auth_result'],
                'records_found' => $health['records_found'],
                'detected_fields' => $health['detected_fields'],
                'status' => $health['status'],
                'error_message' => $health['error_message'],
                'sample_payload' => $health['sample_payload'],
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true,
                    'health' => $health,
                    'log_id' => $log->id,
                ]);
            }

            $statusText = $health['status'] === 'healthy' ? 'Healthy (HTTP ' . ($health['http_status'] ?? 200) . ')' : 'Issue Detected';
            return back()->with('success', "Connection test for '{$datasource->name}' completed: {$statusText} in {$health['response_time_ms']}ms.");
        } catch (\Throwable $e) {
            ApiHealthLog::create([
                'data_source_id' => $datasource->id,
                'status' => 'unhealthy',
                'error_message' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'error' => $e->getMessage(),
                    'health' => [
                        'http_status' => 500,
                        'response_time_ms' => 0,
                        'auth_result' => 'failed',
                        'records_found' => 0,
                        'detected_fields' => [],
                        'status' => 'unhealthy',
                        'error_message' => $e->getMessage(),
                        'sample_payload' => null,
                    ],
                ], 200);
            }

            return back()->with('error', "Connection test failed: " . $e->getMessage());
        }
    }

    /**
     * Trigger manual sync for the data source.
     */
    public function triggerSync(Request $request, DataSource $datasource, MarketPriceIngestionService $ingestionService): JsonResponse|RedirectResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        try {
            $result = $ingestionService->ingest($datasource);

            $statusText = match ($result['status']) {
                'success' => 'successfully',
                'partial' => 'with some non-fatal warnings',
                default => 'with errors',
            };

            $message = "Sync for '{$datasource->name}' finished {$statusText}: {$result['received']} fetched, {$result['inserted']} new, {$result['updated']} updated, {$result['duplicate']} duplicates skipped in {$result['duration_ms']}ms.";

            if ($request->wantsJson() || $request->ajax()) {
                $freshDs = $datasource->fresh();
                return response()->json([
                    'ok' => true,
                    'status' => $result['status'],
                    'message' => $message,
                    'datasource' => [
                        'id' => $datasource->id,
                        'name' => $datasource->name,
                        'code' => $datasource->code,
                        'last_sync_at' => $freshDs->last_sync_at?->diffForHumans() ?? 'Just now',
                        'last_sync_status' => $freshDs->last_sync_status ?? $result['status'],
                    ],
                    'received' => $result['received'],
                    'inserted' => $result['inserted'],
                    'updated' => $result['updated'],
                    'duplicate' => $result['duplicate'],
                    'rejected' => $result['rejected'],
                    'skipped' => $result['skipped'] ?? 0,
                    'duration_ms' => $result['duration_ms'],
                    'crops' => $result['crops_breakdown'] ?? [],
                    'errors' => $result['errors'] ?? [],
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'message' => "Sync failed for '{$datasource->name}': " . $e->getMessage(),
                    'crops' => [],
                ], 200);
            }

            return back()->with('error', "Sync failed for '{$datasource->name}': " . $e->getMessage());
        }
    }

    /**
     * Trigger manual sync for all active data sources for today.
     */
    public function syncAll(Request $request, MarketPriceIngestionService $ingestionService): JsonResponse|RedirectResponse
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        $activeSources = DataSource::where('is_active', true)->get();
        $targetDate = Carbon::today()->format('Y-m-d');

        $totalReceived = 0;
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalDuplicate = 0;
        $totalRejected = 0;
        $allCrops = [];
        $sourceResults = [];
        $overallStatus = 'success';
        $startTime = microtime(true);

        foreach ($activeSources as $source) {
            try {
                $result = $ingestionService->ingest($source, ['to_date' => $targetDate]);
                $totalReceived += $result['received'] ?? 0;
                $totalInserted += $result['inserted'] ?? 0;
                $totalUpdated += $result['updated'] ?? 0;
                $totalDuplicate += $result['duplicate'] ?? 0;
                $totalRejected += $result['rejected'] ?? 0;

                if (($result['status'] ?? '') === 'failed') {
                    $overallStatus = 'partial';
                } elseif (($result['status'] ?? '') === 'partial' && $overallStatus !== 'failed') {
                    $overallStatus = 'partial';
                }

                if (!empty($result['crops_breakdown'])) {
                    foreach ($result['crops_breakdown'] as $cb) {
                        $key = $cb['raw_name'] ?? $cb['crop_name'];
                        if (!isset($allCrops[$key])) {
                            $allCrops[$key] = $cb;
                        } else {
                            $allCrops[$key]['received'] += $cb['received'];
                            $allCrops[$key]['inserted'] += $cb['inserted'];
                            $allCrops[$key]['updated'] += $cb['updated'];
                            $allCrops[$key]['duplicate'] += $cb['duplicate'];
                            $allCrops[$key]['rejected'] += $cb['rejected'];
                        }
                    }
                }

                $sourceResults[] = [
                    'id' => $source->id,
                    'name' => $source->name,
                    'status' => $result['status'] ?? 'success',
                    'received' => $result['received'] ?? 0,
                    'inserted' => $result['inserted'] ?? 0,
                    'updated' => $result['updated'] ?? 0,
                    'rejected' => $result['rejected'] ?? 0,
                ];
            } catch (\Throwable $e) {
                $overallStatus = 'partial';
                $sourceResults[] = [
                    'id' => $source->id,
                    'name' => $source->name,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $durationMs = max(0, (int) round((microtime(true) - $startTime) * 1000));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'status' => $overallStatus,
                'target_date' => $targetDate,
                'message' => "All {$activeSources->count()} data sources synced for {$targetDate}: {$totalReceived} fetched, {$totalInserted} new, {$totalUpdated} updated.",
                'datasource' => [
                    'id' => 0,
                    'name' => "All Active Feeds ({$activeSources->count()} sources for {$targetDate})",
                    'code' => 'all',
                    'last_sync_at' => 'Just now',
                    'last_sync_status' => $overallStatus,
                ],
                'received' => $totalReceived,
                'inserted' => $totalInserted,
                'updated' => $totalUpdated,
                'duplicate' => $totalDuplicate,
                'rejected' => $totalRejected,
                'duration_ms' => $durationMs,
                'crops' => array_values($allCrops),
                'sources' => $sourceResults,
            ]);
        }

        return back()->with('success', "Batch sync completed for today ({$targetDate}) across {$activeSources->count()} data sources.");
    }

    /**
     * Retry / reprocess held raw records for a specific crop, optionally mapping it first.
     */
    public function retryCrop(Request $request, DataSource $datasource, MarketPriceIngestionService $ingestionService): JsonResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $validated = $request->validate([
            'commodity_name' => ['required', 'string', 'max:150'],
            'crop_id' => ['nullable', 'exists:crops,id'],
        ]);

        $commodityName = trim($validated['commodity_name']);
        $cropId = $validated['crop_id'] ?? null;

        // If a canonical crop_id was supplied, save/verify mapping rule
        if ($cropId) {
            CropSourceMapping::updateOrCreate(
                [
                    'data_source_id' => $datasource->id,
                    'source_crop_name' => $commodityName,
                    'source_variety_name' => null,
                ],
                [
                    'crop_id' => $cropId,
                    'crop_variety_id' => null,
                    'confidence_score' => 1.00,
                    'is_verified' => true,
                ]
            );
        }

        // Reprocess held records for this commodity
        $result = $ingestionService->reprocessCrop($datasource->id, $commodityName);

        $status = $result['processed'] > 0 && $result['still_rejected'] === 0
            ? 'synced'
            : ($result['processed'] > 0 ? 'partial' : 'failed');

        $message = $result['processed'] > 0
            ? "Successfully reprocessed {$result['processed']} records for '{$commodityName}'."
            : "No records could be reprocessed. Please check entity mapping.";

        $canonicalCrop = $cropId ? Crop::find($cropId) : null;

        return response()->json([
            'ok' => $result['processed'] > 0,
            'status' => $status,
            'commodity_name' => $commodityName,
            'crop_id' => $cropId,
            'crop_name' => $canonicalCrop?->name,
            'photo_url' => $canonicalCrop?->photo_url,
            'processed' => $result['processed'],
            'still_rejected' => $result['still_rejected'],
            'message' => $message,
            'errors' => $result['errors'],
        ]);
    }

    /**
     * Retry / reprocess all rejected records for this data source.
     */
    public function retryAll(DataSource $datasource, MarketPriceIngestionService $ingestionService): JsonResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $result = $ingestionService->reprocessBatch($datasource->id);

        return response()->json([
            'ok' => true,
            'processed' => $result['processed'],
            'still_rejected' => $result['still_rejected'],
            'total' => $result['total'],
            'message' => "Batch reprocess completed: {$result['processed']} processed, {$result['still_rejected']} remaining rejected.",
        ]);
    }

    /**
     * Update automated background cron sync timings and schedules.
     */
    public function updateScheduleTimings(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'morning_time' => ['nullable', 'string', 'max:10'],
            'evening_time' => ['nullable', 'string', 'max:10'],
            'afternoon_time' => ['nullable', 'string', 'max:10'],
            'enable_hourly' => ['nullable', 'boolean'],
            'operating_days' => ['required', 'in:mon_sat,all'],
            'apply_to_sources' => ['nullable', 'boolean'],
            'enrolled_sources' => ['nullable', 'array'],
            'enrolled_sources.*' => ['integer', 'exists:data_sources,id'],
            'scheduled_tasks' => ['nullable', 'array'],
            'scheduled_tasks.*' => ['string', 'in:mandi_prices,weather_sync,analytics_stats,forecasting,retention_pruning,data_integrity'],
        ]);

        $morning = trim($validated['morning_time'] ?? '');
        $evening = trim($validated['evening_time'] ?? '');
        $afternoon = trim($validated['afternoon_time'] ?? '');
        $enableHourly = $request->boolean('enable_hourly');
        $operatingDays = $validated['operating_days'];
        $applyToSources = $request->boolean('apply_to_sources', true);

        SystemSetting::set('cron_market_morning_time', $morning, 'string', 'cron', 'Primary morning market rates sync time (HH:MM)');
        SystemSetting::set('cron_market_evening_time', $evening, 'string', 'cron', 'Primary evening market closing rates sync time (HH:MM)');
        SystemSetting::set('cron_market_afternoon_time', $afternoon, 'string', 'cron', 'Optional mid-day market rates sync time (HH:MM)');
        SystemSetting::set('cron_market_enable_hourly', $enableHourly ? 'true' : 'false', 'boolean', 'cron', 'Enable periodic hourly sync during trading hours');
        SystemSetting::set('cron_market_operating_days', $operatingDays, 'string', 'cron', 'Scheduled sync operating days (mon_sat vs all)');

        // Build comma-separated sync_time string for data sources (e.g. "06:00,18:00")
        $times = array_filter([$morning, $afternoon, $evening]);
        $syncTimeString = implode(',', $times);
        $frequency = count($times) >= 2 ? 'twice_daily' : 'daily';

        if ($applyToSources && !empty($syncTimeString)) {
            DataSource::where('is_active', true)->update([
                'sync_time' => $syncTimeString,
                'sync_frequency' => $frequency,
                'sync_days' => $operatingDays,
            ]);
        }

        // Synchronize enrolled cron sources whitelist if submitted
        if ($request->has('enrolled_sources_submitted')) {
            $enrolledIds = array_map('intval', (array) $request->input('enrolled_sources', []));
            DataSource::whereIn('id', $enrolledIds)->update(['is_cron_enabled' => true]);
            DataSource::whereNotIn('id', $enrolledIds)->update(['is_cron_enabled' => false]);
        }

        // Synchronize scheduled background tasks master switches if submitted
        $knownTasks = ['mandi_prices', 'weather_sync', 'analytics_stats', 'forecasting', 'retention_pruning', 'data_integrity'];
        $updatedTasks = [];
        if ($request->has('scheduled_tasks_submitted')) {
            $selectedTasks = (array) $request->input('scheduled_tasks', []);
            foreach ($knownTasks as $taskKey) {
                $isEnabled = in_array($taskKey, $selectedTasks, true);
                SystemSetting::set('cron_task_' . $taskKey, $isEnabled ? 'true' : 'false', 'boolean', 'cron', "Automated background cron task master switch: {$taskKey}");
                $updatedTasks[$taskKey] = $isEnabled;
            }
            Cache::forget('cron_task_statuses');
        } else {
            foreach ($knownTasks as $taskKey) {
                $updatedTasks[$taskKey] = (bool) SystemSetting::get('cron_task_' . $taskKey, true);
            }
        }

        $enrolledCount = DataSource::where('is_active', true)->where('is_cron_enabled', true)->count();
        $totalActive = DataSource::where('is_active', true)->count();

        AuditLog::log(
            'update_cron_schedule',
            'SystemSetting',
            null,
            [],
            [
                'morning' => $morning,
                'evening' => $evening,
                'afternoon' => $afternoon,
                'enable_hourly' => $enableHourly,
                'operating_days' => $operatingDays,
                'applied_to_sources' => $applyToSources,
                'enrolled_sources' => $request->input('enrolled_sources', []),
                'enrolled_count' => $enrolledCount,
                'scheduled_tasks' => $updatedTasks,
            ]
        );

        $msg = "Automated background cron timings updated successfully: Morning (" . ($morning ?: 'Off') . "), Evening (" . ($evening ?: 'Off') . "), Operating Days: " . ($operatingDays === 'mon_sat' ? 'Mon-Sat' : 'All 7 Days') . ", Cron Scope: {$enrolledCount} of {$totalActive} active feeds enrolled.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'cron_enabled_count' => $enrolledCount,
                'total_active' => $totalActive,
                'timings' => [
                    'morning_time' => $morning,
                    'evening_time' => $evening,
                    'afternoon_time' => $afternoon,
                    'enable_hourly' => $enableHourly,
                    'operating_days' => $operatingDays,
                    'sync_time_string' => $syncTimeString,
                ],
                'scheduled_tasks' => $updatedTasks,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Generate visual CAPTCHA for Agmarknet official sync.
     */
    public function agmarknetCaptcha(): JsonResponse
    {
        $datasource = DataSource::where('code', 'agmarknet_official')->first();
        if (!$datasource) {
            return response()->json(['ok' => false, 'error' => 'Official Agmarknet data source not found.'], 404);
        }

        try {
            /** @var \App\Services\DataSources\Agmarknet\AgmarknetHistoricalDataProvider $provider */
            $provider = DataSourceRegistry::make($datasource);
            $captchaData = $provider->generateCaptcha();

            return response()->json([
                'ok' => $captchaData['success'],
                'captcha_key' => $captchaData['captcha_key'],
                'captcha_image' => $captchaData['captcha_image'],
                'error' => $captchaData['error'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Trigger Agmarknet historical sync with user-provided CAPTCHA.
     */
    public function agmarknetHistoricalSync(Request $request, MarketPriceIngestionService $ingestionService): JsonResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $datasource = DataSource::where('code', 'agmarknet_official')->first();
        if (!$datasource) {
            return response()->json(['ok' => false, 'error' => 'Official Agmarknet data source not found.'], 404);
        }

        $validated = $request->validate([
            'captcha_key' => 'required|string',
            'captcha_code' => 'required|string|min:4|max:8',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'crop_id' => 'nullable|integer',
        ]);

        try {
            // Run ingestion with captcha parameters directly passed to the official report API
            $result = $ingestionService->ingest($datasource, [
                'captcha_key' => $validated['captcha_key'],
                'captcha_code' => $validated['captcha_code'],
                'captcha_value' => $validated['captcha_code'],
                'from_date' => $validated['from_date'] ?? null,
                'to_date' => $validated['to_date'] ?? null,
                'crop_id' => $validated['crop_id'] ?? null,
                'force' => true,
            ]);

            $inserted = (int) ($result['inserted'] ?? 0);
            $updated = (int) ($result['updated'] ?? 0);
            $received = (int) ($result['received'] ?? 0);

            $msg = ($inserted > 0 || $updated > 0)
                ? "Agmarknet historical sync completed: {$inserted} new records inserted, {$updated} updated ({$received} received)."
                : "Agmarknet sync completed: Verified official API, but no mandi records were reported by Agmarknet for this date range.";

            return response()->json([
                'ok' => true,
                'result' => $result,
                'message' => $msg,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get crop synchronization configuration for a data source.
     */
    public function getCropSyncConfig(DataSource $datasource): JsonResponse
    {
        $allCrops = Crop::with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $isCoffeeBoard = ($datasource->code === 'coffee_board');
        $isCoconutBoard = ($datasource->code === 'coconut_board');
        $isSpecialized = ($isCoffeeBoard || $isCoconutBoard);

        // Filter crops for specialized single-commodity statutory boards
        if ($isCoffeeBoard) {
            $allCrops = $allCrops->filter(function ($c) {
                return str_contains(strtolower($c->name), 'coffee');
            })->values();
        } elseif ($isCoconutBoard) {
            $allCrops = $allCrops->filter(function ($c) {
                $name = strtolower(trim($c->name));
                return in_array($name, ['coconut', 'copra', 'tender coconut'], true);
            })->values();
        }

        $syncMap = DataSourceCropSync::where('data_source_id', $datasource->id)
            ->pluck('is_enabled', 'crop_id')
            ->all();

        // Get market records count per crop for this data source
        $priceStats = DB::table('market_prices')
            ->where('data_source_id', $datasource->id)
            ->select('crop_id', DB::raw('count(*) as total_records'), DB::raw('count(distinct market_id) as total_mandis'))
            ->groupBy('crop_id')
            ->get()
            ->keyBy('crop_id');

        $nonCrops = [
            'sheep', 'goat', 'she baffalo', 'he baffalo', 'bull', 'calf', 'ox', 'she goat',
            'tur dal', 'bengal gramdal', 'black gramdal', 'green gramdal', 'avaredal', 'chennangidal',
            'wood', 'coco brooms', 'honge seed', 'neem seed', 'soapnut', 'antawala', 'hippe seed',
            'rose', 'crysanthamum', 'marygold', 'all flowers',
            'linseed', 'niger seed', 't. v. cumbu', 'maragenasu', 'bullar', 'duster beans', 'gurellu', 'moath', 'barley', 'cumminseed'
        ];
        $nonCropsLookup = array_fill_keys($nonCrops, true);

        $plantationLookup = array_fill_keys(['arecanut', 'coconut', 'copra', 'tender coconut', 'cotton', 'jaggery', 'cashewnut', 'coffee', 'betel leaves', 'betal leaves', 'lint', 'cotton seed'], true);
        $cerealsPulsesLookup = array_fill_keys(['maize', 'paddy', 'rice', 'ragi', 'ragi (finger millet)', 'jowar', 'wheat', 'bajra', 'navane', 'same/savi', 'bengalgram', 'tur', 'greengram', 'blackgram', 'horse gram', 'cowpea', 'alasande gram', 'chapparada avare', 'mataki', 'sajje', 'redgram', 'foxtail millet', 'millets'], true);
        $oilseedsLookup = array_fill_keys(['groundnut', 'sunflower', 'soyabeen', 'safflower', 'sesamum', 'mustard', 'gingelly', 'castor seed', 'groundnut seed'], true);
        $spicesLookup = array_fill_keys(['ginger', 'black pepper', 'garlic', 'dry chillies', 'tamarind fruit', 'coriander seed', 'turmeric', 'methi seeds', 'chilly red', 'tamarind seed'], true);
        $vegetablesLookup = array_fill_keys(['tomato', 'beans', 'green chilli', 'brinjal', 'onion', 'carrot', 'cucumbar', 'potato', 'ladies finger', 'ridgeguard', 'beetroot', 'cabbage', 'raddish', 'knool khol', 'cauliflower', 'bitter gourd', 'capsicum', 'thondekai', 'bottle gourd', 'drum stick', 'sweet pumpkin', 'seemebadanekai', 'sweet potato', 'snakeguard', 'green avare', 'suvarnagadde', 'bunch beans', 'ash gourd', 'white pumpkin', 'peas wet', 'alasandikai', 'thogarikai', 'leafy vegetables', 'chilly capsicum', 'green peas'], true);
        $fruitsLookup = array_fill_keys(['banana', 'pineapple', 'pine apple', 'apple', 'papaya', 'lime', 'water melon', 'pomagranate', 'mango', 'chikoos', 'karbuja', 'orange', 'mousambi', 'grapes', 'banana green', 'guava', 'dry grapes', 'jack fruit', 'seethaphal', 'other fruits', 'sweet lime', 'pear'], true);

        $coreCropIds = [];
        $nonCropIds = [];
        $plantationCropIds = [];
        $vegetableCropIds = [];
        $cerealPulseCropIds = [];
        $oilseedCropIds = [];
        $spiceCropIds = [];
        $fruitCropIds = [];

        $cropsList = [];
        $activeCount = 0;

        foreach ($allCrops as $c) {
            $cNameLower = strtolower(trim($c->name));
            $isNonCrop = isset($nonCropsLookup[$cNameLower]);

            // Determine accurate group name and slug
            $groupName = 'Other';
            $groupSlug = 'other';

            if ($isSpecialized) {
                $groupName = $isCoffeeBoard ? 'Coffee' : 'Coconut & Copra';
                $groupSlug = $isCoffeeBoard ? 'coffee' : 'coconut';
            } elseif (isset($plantationLookup[$cNameLower])) {
                $groupName = 'Plantation & Cash';
                $groupSlug = 'plantation';
                $plantationCropIds[] = $c->id;
            } elseif (isset($vegetablesLookup[$cNameLower])) {
                $groupName = 'Vegetables';
                $groupSlug = 'vegetables';
                $vegetableCropIds[] = $c->id;
            } elseif (isset($cerealsPulsesLookup[$cNameLower])) {
                $groupName = 'Cereals & Pulses';
                $groupSlug = 'cereals-pulses';
                $cerealPulseCropIds[] = $c->id;
            } elseif (isset($oilseedsLookup[$cNameLower])) {
                $groupName = 'Oilseeds';
                $groupSlug = 'oilseeds';
                $oilseedCropIds[] = $c->id;
            } elseif (isset($spicesLookup[$cNameLower])) {
                $groupName = 'Spices';
                $groupSlug = 'spices';
                $spiceCropIds[] = $c->id;
            } elseif (isset($fruitsLookup[$cNameLower])) {
                $groupName = 'Fruits';
                $groupSlug = 'fruits';
                $fruitCropIds[] = $c->id;
            } elseif ($isNonCrop) {
                $groupName = 'Non-Crop / Excluded';
                $groupSlug = 'non-crops';
            }

            // In syncMap, default to enabled for specialized crops or non-excluded crops
            $isEnabled = isset($syncMap[$c->id]) ? (bool) $syncMap[$c->id] : ($isSpecialized ? true : !$isNonCrop);
            if ($isEnabled) {
                $activeCount++;
            }

            if ($isNonCrop) {
                $nonCropIds[] = $c->id;
            } else {
                $coreCropIds[] = $c->id;
            }

            $stats = $priceStats->get($c->id);

            $cropsList[] = [
                'id' => $c->id,
                'name' => $c->name,
                'name_kn' => $c->name_kn,
                'category_name' => $groupName,
                'category_slug' => $groupSlug,
                'is_enabled' => $isEnabled,
                'is_karnataka_core' => !$isNonCrop,
                'is_non_crop' => $isNonCrop,
                'photo_url' => $c->photo_url,
                'records_count' => (int) ($stats->total_records ?? 0),
                'mandis_count' => (int) ($stats->total_mandis ?? 0),
            ];
        }

        if ($isSpecialized) {
            $filterGroups = [
                ['slug' => 'all', 'name' => ($isCoffeeBoard ? 'Coffee' : 'Coconut & Copra') . ' (' . count($cropsList) . ')'],
            ];
        } else {
            $filterGroups = [
                ['slug' => 'all', 'name' => 'All Items (' . count($cropsList) . ')'],
                ['slug' => 'plantation', 'name' => 'Plantation & Cash (' . count($plantationCropIds) . ')'],
                ['slug' => 'vegetables', 'name' => 'Vegetables (' . count($vegetableCropIds) . ')'],
                ['slug' => 'cereals-pulses', 'name' => 'Cereals & Pulses (' . count($cerealPulseCropIds) . ')'],
                ['slug' => 'spices', 'name' => 'Spices (' . count($spiceCropIds) . ')'],
                ['slug' => 'oilseeds', 'name' => 'Oilseeds (' . count($oilseedCropIds) . ')'],
                ['slug' => 'fruits', 'name' => 'Fruits (' . count($fruitCropIds) . ')'],
                ['slug' => 'non-crops', 'name' => 'Non-Crops / Excluded (' . count($nonCropIds) . ')'],
            ];
        }

        return response()->json([
            'ok' => true,
            'datasource' => [
                'id' => $datasource->id,
                'name' => $datasource->name,
                'code' => $datasource->code,
                'active_crops_count' => $activeCount,
                'total_crops_count' => count($cropsList),
                'is_specialized' => $isSpecialized,
                'specialization_title' => match ($datasource->code) {
                    'coffee_board' => 'Statutory Single-Commodity Board (Coffee)',
                    'coconut_board' => 'Specialized Commodity Board (Coconut & Copra)',
                    default => null,
                },
                'specialization_note' => match ($datasource->code) {
                    'coffee_board' => 'Coffee Board of India exclusively publishes domestic farm-gate prices for Coffee (Arabica & Robusta varieties) across key growing hubs: Chikkamagaluru, Madikeri, and Sakleshpur.',
                    'coconut_board' => 'Coconut Development Board exclusively tracks Coconut, Copra (Ball & Milling), and Tender Coconut across Karnataka producing hubs (Tiptur, Arsikere, Mangaluru).',
                    default => null,
                },
            ],
            'filter_groups' => $filterGroups,
            'crops' => array_values($cropsList),
            'presets' => [
                'core_crop_ids' => $coreCropIds,
                'non_crop_ids' => $nonCropIds,
                'plantation_crop_ids' => $plantationCropIds,
                'vegetable_crop_ids' => $vegetableCropIds,
                'cereal_pulse_crop_ids' => $cerealPulseCropIds,
                'oilseed_crop_ids' => $oilseedCropIds,
                'spice_crop_ids' => $spiceCropIds,
                'fruit_crop_ids' => $fruitCropIds,
            ],
        ]);
    }

    /**
     * Save updated crop synchronization settings for a data source.
     */
    public function updateCropSyncConfig(Request $request, DataSource $datasource): JsonResponse
    {
        $validated = $request->validate([
            'crop_ids' => 'present|array',
            'crop_ids.*' => 'integer|exists:crops,id',
        ]);

        $selectedCropIds = array_map('intval', $validated['crop_ids']);
        $selectedLookup = array_fill_keys($selectedCropIds, true);

        $isCoffeeBoard = ($datasource->code === 'coffee_board');
        $isCoconutBoard = ($datasource->code === 'coconut_board');
        $isSpecialized = ($isCoffeeBoard || $isCoconutBoard);

        if ($isCoffeeBoard) {
            $relevantCropIds = Crop::where('name', 'like', '%coffee%')->pluck('id')->all();
        } elseif ($isCoconutBoard) {
            $relevantCropIds = Crop::whereIn(DB::raw('LOWER(name)'), ['coconut', 'copra', 'tender coconut'])->pluck('id')->all();
        } else {
            $relevantCropIds = Crop::pluck('id')->all();
        }

        $allCropIds = Crop::pluck('id')->all();
        $now = now();

        $upsertData = [];
        foreach ($allCropIds as $cid) {
            // For specialized providers, non-relevant crops must be disabled
            $isEnabled = isset($selectedLookup[$cid]);
            if ($isSpecialized && !in_array($cid, $relevantCropIds, true)) {
                $isEnabled = false;
            }

            $upsertData[] = [
                'data_source_id' => $datasource->id,
                'crop_id' => $cid,
                'is_enabled' => $isEnabled,
                'sync_priority' => 'standard',
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        DB::table('data_source_crop_sync')->upsert(
            $upsertData,
            ['data_source_id', 'crop_id'],
            ['is_enabled', 'updated_at']
        );

        // Synchronize public visibility (is_active on crops table):
        // A crop is active in the public app if it is enabled in at least one active data source
        $activeCropIdsAcrossSources = DB::table('data_source_crop_sync')
            ->join('data_sources', 'data_source_crop_sync.data_source_id', '=', 'data_sources.id')
            ->where('data_sources.is_active', true)
            ->where('data_source_crop_sync.is_enabled', true)
            ->distinct()
            ->pluck('data_source_crop_sync.crop_id')
            ->all();

        if (!empty($activeCropIdsAcrossSources)) {
            Crop::whereIn('id', $activeCropIdsAcrossSources)->update(['is_active' => true]);
            Crop::whereNotIn('id', $activeCropIdsAcrossSources)->update(['is_active' => false]);
        }

        AuditLog::log(
            'update_crop_sync',
            'DataSource',
            $datasource->id,
            null,
            ['active_crops_count' => count($selectedCropIds)]
        );

        $totalCount = $isSpecialized ? count($relevantCropIds) : count($allCropIds);

        return response()->json([
            'ok' => true,
            'message' => "Crop sync settings saved for {$datasource->name}: " . count($selectedCropIds) . " of {$totalCount} crops configured to sync.",
            'active_crops_count' => count($selectedCropIds),
            'total_crops_count' => $totalCount,
        ]);
    }

    /**
     * Instantly synchronize a single crop for a data source.
     */
    public function syncSingleCrop(Request $request, DataSource $datasource, Crop $crop, MarketPriceIngestionService $ingestionService): JsonResponse
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        try {
            $result = $ingestionService->ingest($datasource, [
                'crop_id' => $crop->id,
                'force' => true,
            ]);

            return response()->json([
                'ok' => true,
                'crop' => [
                    'id' => $crop->id,
                    'name' => $crop->name,
                    'name_kn' => $crop->name_kn,
                ],
                'result' => $result,
                'message' => "Successfully synced {$crop->name} for {$datasource->name} in {$result['duration_ms']}ms: {$result['inserted']} new records, {$result['updated']} updated.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => "Sync failed for {$crop->name}: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a data source and its associated relations.
     */
    public function destroy(DataSource $datasource): RedirectResponse
    {
        $name = $datasource->name;
        $id = $datasource->id;

        $datasource->delete();

        AuditLog::log(
            'delete',
            'DataSource',
            $id,
            ['name' => $name],
            null
        );

        return redirect()->route('admin.datasources.index')
            ->with('success', "Data source '{$name}' was deleted successfully.");
    }
}

