<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataSourceRequest;
use App\Models\ApiHealthLog;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Models\DataSourceCredential;
use App\Models\SyncLog;
use App\Models\SystemSetting;
use App\Services\DataSources\DataSourceRegistry;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataSourceController extends Controller
{
    public function index(): View
    {
        $dataSources = DataSource::with(['credential', 'mappings'])
            ->withCount(['syncLogs', 'healthLogs'])
            ->orderBy('id', 'asc')
            ->paginate(15);

        $stats = [
            'total' => DataSource::count(),
            'active' => DataSource::where('is_active', true)->count(),
            'synced_today' => SyncLog::whereDate('started_at', Carbon::today())->where('status', 'success')->count(),
            'failed_recent' => SyncLog::where('status', 'failed')->where('started_at', '>=', now()->subDays(7))->count(),
        ];

        $rawHeartbeat = \Illuminate\Support\Facades\Cache::get('scheduler_last_heartbeat') 
            ?? DataSource::max('last_heartbeat_at');
        $lastHeartbeat = $rawHeartbeat ? Carbon::parse($rawHeartbeat) : null;
        $isCronActive = $lastHeartbeat && $lastHeartbeat->diffInMinutes(now()) <= 15;

        $cronInfo = [
            'php_binary' => PHP_BINARY,
            'base_path' => base_path(),
            'artisan_path' => base_path('artisan'),
            'cpanel_command' => "* * * * * /usr/local/bin/php " . base_path('artisan') . " schedule:run >/dev/null 2>&1",
            'standard_command' => "* * * * * cd " . base_path() . " && php artisan schedule:run >> /dev/null 2>&1",
            'last_heartbeat' => $lastHeartbeat,
            'is_active' => $isCronActive,
            'morning_time' => SystemSetting::get('cron_market_morning_time', '06:00'),
            'evening_time' => SystemSetting::get('cron_market_evening_time', '18:00'),
            'afternoon_time' => SystemSetting::get('cron_market_afternoon_time', ''),
            'enable_hourly' => (bool) SystemSetting::get('cron_market_enable_hourly', true),
            'operating_days' => SystemSetting::get('cron_market_operating_days', 'mon_sat'),
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

    public function toggleStatus(DataSource $datasource): RedirectResponse
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
            ]
        );

        $msg = "Automated background cron timings updated successfully: Morning (" . ($morning ?: 'Off') . "), Evening (" . ($evening ?: 'Off') . "), Operating Days: " . ($operatingDays === 'mon_sat' ? 'Mon-Sat' : 'All 7 Days') . ".";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'timings' => [
                    'morning_time' => $morning,
                    'evening_time' => $evening,
                    'afternoon_time' => $afternoon,
                    'enable_hourly' => $enableHourly,
                    'operating_days' => $operatingDays,
                    'sync_time_string' => $syncTimeString,
                ],
            ]);
        }

        return back()->with('success', $msg);
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

