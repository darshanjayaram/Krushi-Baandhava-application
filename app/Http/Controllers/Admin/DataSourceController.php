<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataSourceRequest;
use App\Models\ApiHealthLog;
use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\DataSourceCredential;
use App\Models\SyncLog;
use App\Services\DataSources\DataSourceRegistry;
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
        ];

        return view('admin.datasources.index', compact('dataSources', 'stats', 'cronInfo'));
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
    /**
     * Trigger manual sync for the data source.
     */
    public function triggerSync(DataSource $datasource, \App\Services\Ingestion\MarketPriceIngestionService $ingestionService): RedirectResponse
    {
        try {
            $result = $ingestionService->ingest($datasource);

            $statusText = match ($result['status']) {
                'success' => 'successfully',
                'partial' => 'with some non-fatal warnings',
                default => 'with errors',
            };

            return back()->with('success', "Sync for '{$datasource->name}' finished {$statusText}: {$result['received']} fetched, {$result['inserted']} new, {$result['updated']} updated, {$result['duplicate']} duplicates skipped in {$result['duration_ms']}ms.");
        } catch (\Throwable $e) {
            return back()->with('error', "Sync failed for '{$datasource->name}': " . $e->getMessage());
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

