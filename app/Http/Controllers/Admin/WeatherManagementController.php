<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\SystemSetting;
use App\Models\WeatherForecast;
use App\Services\Weather\WeatherSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WeatherManagementController extends Controller
{
    public function __construct(
        protected WeatherSyncService $weatherService
    ) {}

    /**
     * Display weather storage overview, TTL settings, and district cache statuses.
     */
    public function index(Request $request): View
    {
        $stats = $this->weatherService->getStorageStats();
        $ttlMinutes = (int) ($stats['cache_ttl_minutes'] ?? 15);

        // Approximate DB storage size
        $approxSizeBytes = $stats['total_records'] * 768; // ~768 bytes per forecast row including JSON payload
        $formattedSize = $approxSizeBytes > 1048576 
            ? round($approxSizeBytes / 1048576, 2) . ' MB' 
            : round($approxSizeBytes / 1024, 1) . ' KB';

        // Load all active Karnataka districts with latest weather fetch timestamp & forecast count
        $districts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->where('is_active', true)
            ->withCount('weatherForecasts')
            ->orderBy('name')
            ->get()
            ->map(function ($district) use ($ttlMinutes) {
                $latestForecast = WeatherForecast::where('district_id', $district->id)
                    ->orderByDesc('fetched_at')
                    ->first(['fetched_at', 'current_temperature', 'weather_condition_en']);

                $fetchedAt = $latestForecast?->fetched_at;
                $isFresh = false;
                $statusText = 'Not Synced';
                $statusColor = 'slate';

                if ($fetchedAt) {
                    $ageMinutes = Carbon::parse($fetchedAt)->diffInMinutes(Carbon::now());
                    if ($ageMinutes <= $ttlMinutes) {
                        $isFresh = true;
                        $statusText = 'Fresh (' . $ageMinutes . 'm ago)';
                        $statusColor = 'emerald';
                    } elseif ($ageMinutes <= ($ttlMinutes * 2)) {
                        $statusText = 'Expiring (' . $ageMinutes . 'm ago)';
                        $statusColor = 'amber';
                    } else {
                        $statusText = 'Stale (' . Carbon::parse($fetchedAt)->diffForHumans() . ')';
                        $statusColor = 'rose';
                    }
                }

                $district->latest_fetch = $fetchedAt;
                $district->is_fresh = $isFresh;
                $district->status_text = $statusText;
                $district->status_color = $statusColor;
                $district->current_temp = $latestForecast?->current_temperature;
                $district->current_condition = $latestForecast?->weather_condition_en;

                return $district;
            });

        // Recent 10 weather fetch logs / records
        $recentForecasts = WeatherForecast::with('district')
            ->orderByDesc('fetched_at')
            ->take(10)
            ->get();

        return view('admin.weather.index', compact(
            'stats',
            'ttlMinutes',
            'formattedSize',
            'districts',
            'recentForecasts'
        ));
    }

    /**
     * Update the weather cache TTL duration setting.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cache_ttl_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $oldTtl = $this->weatherService->getCacheTtlMinutes();
        $newTtl = (int) $validated['cache_ttl_minutes'];

        SystemSetting::set('weather_cache_ttl_minutes', $newTtl, 'integer', 'weather');

        AuditLog::log(
            'weather.settings_update',
            'SystemSetting',
            null,
            ['cache_ttl_minutes' => $oldTtl],
            ['cache_ttl_minutes' => $newTtl]
        );

        return redirect()->route('admin.weather.index')
            ->with('success', "Weather Cache TTL updated to {$newTtl} minutes.");
    }

    /**
     * Bulk prune or purge stored weather forecasts.
     */
    public function prune(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prune_mode' => ['required', 'in:7_days,30_days,all'],
        ]);

        $mode = $validated['prune_mode'];
        $deletedCount = 0;

        try {
            if ($mode === '7_days') {
                $deletedCount = $this->weatherService->pruneForecasts(7);
                $message = "Cleaned up {$deletedCount} forecast records older than 7 days.";
            } elseif ($mode === '30_days') {
                $deletedCount = $this->weatherService->pruneForecasts(30);
                $message = "Cleaned up {$deletedCount} forecast records older than 30 days.";
            } elseif ($mode === 'all') {
                $deletedCount = $this->weatherService->purgeAll();
                $message = "Purged all {$deletedCount} weather records from the database.";
            }

            AuditLog::log(
                'weather.prune',
                'WeatherForecast',
                null,
                [],
                ['mode' => $mode, 'deleted_count' => $deletedCount]
            );

            return redirect()->route('admin.weather.index')
                ->with('success', $message);
        } catch (Throwable $e) {
            return redirect()->route('admin.weather.index')
                ->with('error', 'Failed to prune weather data: ' . $e->getMessage());
        }
    }

    /**
     * Manually trigger on-demand sync for a specific district.
     */
    public function syncDistrict(Request $request, District $district): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $count = $this->weatherService->syncDistrict($district, true);

            AuditLog::log(
                'weather.manual_district_sync',
                'District',
                $district->id,
                [],
                ['district' => $district->name, 'forecasts_synced' => $count]
            );

            if ($request->expectsJson() || $request->ajax()) {
                $latest = WeatherForecast::where('district_id', $district->id)->latest('fetched_at')->first();
                return response()->json([
                    'success' => true,
                    'district_id' => $district->id,
                    'district_name' => $district->name,
                    'district_name_kn' => $district->name_kn,
                    'forecasts_count' => $count,
                    'temp' => $latest?->current_temperature,
                    'condition' => $latest?->weather_condition_en,
                    'fetched_at' => $latest?->fetched_at ? Carbon::parse($latest->fetched_at)->format('d M H:i') : null,
                    'message' => "Synced {$count} days for {$district->name}",
                ]);
            }

            return redirect()->route('admin.weather.index')
                ->with('success', "Instantly synced {$count} forecast days for {$district->name} ({$district->name_kn}).");
        } catch (Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'district_id' => $district->id,
                    'district_name' => $district->name,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.weather.index')
                ->with('error', "Weather sync failed for {$district->name}: " . $e->getMessage());
        }
    }

    /**
     * Synchronize all active Karnataka districts (Async Queue or Extended Synchronous).
     */
    public function syncAll(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        // If async/queue mode is requested
        if ($request->boolean('async') || $request->input('mode') === 'async' || $request->input('mode') === 'queue') {
            \App\Jobs\SyncWeatherJob::dispatch(true);

            AuditLog::log(
                'weather.async_sync_all_dispatched',
                'WeatherForecast',
                null,
                [],
                ['status' => 'queued']
            );

            $msg = 'Weather synchronization has been dispatched asynchronously to the background queue.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg, 'async' => true]);
            }

            return redirect()->route('admin.weather.index')->with('success', $msg);
        }

        try {
            $stats = $this->weatherService->syncAllDistricts(true);

            AuditLog::log(
                'weather.manual_sync_all',
                'WeatherForecast',
                null,
                [],
                $stats
            );

            $msg = "Batch sync complete: {$stats['synced']} districts updated, {$stats['skipped']} skipped, {$stats['failed']} failed.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg, 'stats' => $stats]);
            }

            return redirect()->route('admin.weather.index')
                ->with('success', $msg);
        } catch (Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->route('admin.weather.index')
                ->with('error', 'Batch weather sync failed: ' . $e->getMessage());
        }
    }
}
