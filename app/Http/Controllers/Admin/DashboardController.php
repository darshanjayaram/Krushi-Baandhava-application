<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\DataSource;
use App\Models\District;
use App\Models\FeatureFlag;
use App\Models\ForecastMetric;
use App\Models\ForecastModel;
use App\Models\ForecastRun;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Models\PriceForecast;
use App\Models\SyncLog;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the operational admin dashboard with live ingestion & health analytics.
     */
    public function index(): View
    {
        $today = Carbon::today()->toDateString();
        $latestPriceDate = MarketPrice::max('price_date') ?? $today;

        // Scheduler / cPanel Cron Health Detection (Dual Check: Memory Cache + Persistent System Setting DB + DataSource)
        $rawHeartbeat = Cache::get('scheduler_last_heartbeat') 
            ?? SystemSetting::get('scheduler_last_heartbeat')
            ?? DataSource::max('last_heartbeat_at');
        $lastHeartbeat = $rawHeartbeat ? Carbon::parse($rawHeartbeat) : null;
        $isCronActive = $lastHeartbeat && $lastHeartbeat->diffInMinutes(now()) <= 15;

        $cronStatus = [
            'is_active' => $isCronActive,
            'last_heartbeat' => $lastHeartbeat,
            'last_heartbeat_human' => $lastHeartbeat ? $lastHeartbeat->diffForHumans() : 'Never',
            'last_heartbeat_formatted' => $lastHeartbeat ? $lastHeartbeat->format('d M Y, h:i A') : 'No heartbeat recorded yet',
            'minutes_ago' => $lastHeartbeat ? (int) $lastHeartbeat->diffInMinutes(now()) : null,
            'cpanel_command' => "* * * * * cd " . base_path() . " && php artisan schedule:run >> " . storage_path('logs/cron.log') . " 2>&1",
            'cpanel_binary_command' => "* * * * * /usr/local/bin/php " . base_path('artisan') . " schedule:run >> " . storage_path('logs/cron.log') . " 2>&1",
            'base_path' => base_path(),
            'php_binary' => PHP_BINARY,
            'morning_time' => SystemSetting::get('cron_market_morning_time', '06:00'),
            'evening_time' => SystemSetting::get('cron_market_evening_time', '19:30'),
            'afternoon_time' => SystemSetting::get('cron_market_afternoon_time', '12:30'),
            'enable_hourly' => (bool) SystemSetting::get('cron_market_enable_hourly', true),
            'operating_days' => SystemSetting::get('cron_market_operating_days', 'mon_sat'),
        ];

        $cronStatus['tasks'] = [
            'mandi_prices' => [
                'name' => 'Mandi Market Prices Ingestion',
                'title' => 'Mandi Market Prices Ingestion',
                'icon' => '🌾',
                'setting_key' => 'cron_task_mandi_prices',
                'frequency' => ($cronStatus['morning_time'] ?: '06:00') . (!empty($cronStatus['afternoon_time']) ? ', ' . $cronStatus['afternoon_time'] : '') . ' & ' . ($cronStatus['evening_time'] ?: '19:30') . ' IST',
                'timing' => ($cronStatus['morning_time'] ?: '06:00') . (!empty($cronStatus['afternoon_time']) ? ', ' . $cronStatus['afternoon_time'] : '') . ' & ' . ($cronStatus['evening_time'] ?: '19:30') . ' IST',
                'purpose' => 'Syncs KRAMA Karnataka Mandis, Official Agmarknet, Coffee Board & Coconut Board',
                'is_active' => (bool) SystemSetting::get('cron_task_mandi_prices', true),
                'enabled' => (bool) SystemSetting::get('cron_task_mandi_prices', true),
            ],
            'weather_sync' => [
                'name' => 'Hyperlocal Weather Advisories',
                'title' => 'Hyperlocal Weather Advisories',
                'icon' => '🌦️',
                'setting_key' => 'cron_task_weather_sync',
                'frequency' => '05:30 & 14:30 IST Daily',
                'timing' => '05:30 & 14:30 IST Daily',
                'purpose' => 'Updates 7-day agricultural forecasts via Open-Meteo & prunes cache',
                'is_active' => (bool) SystemSetting::get('cron_task_weather_sync', true),
                'enabled' => (bool) SystemSetting::get('cron_task_weather_sync', true),
            ],
            'analytics_stats' => [
                'name' => 'Historical Analytics & Seasonality',
                'title' => 'Historical Analytics & Seasonality',
                'icon' => '📊',
                'setting_key' => 'cron_task_analytics_stats',
                'frequency' => '01:00 IST Nightly',
                'timing' => '01:00 IST Nightly',
                'purpose' => 'Computes 12-month seasonal indices & modal averages',
                'is_active' => (bool) SystemSetting::get('cron_task_analytics_stats', true),
                'enabled' => (bool) SystemSetting::get('cron_task_analytics_stats', true),
            ],
            'forecasting' => [
                'name' => 'Price Forecasting Engine',
                'title' => 'Price Forecasting Engine',
                'icon' => '🔮',
                'setting_key' => 'cron_task_forecasting',
                'frequency' => '02:00 IST Nightly',
                'timing' => '02:00 IST Nightly',
                'purpose' => 'Generates 1D, 7D, 15D, 30D Holt\'s Linear projections',
                'is_active' => (bool) SystemSetting::get('cron_task_forecasting', true),
                'enabled' => (bool) SystemSetting::get('cron_task_forecasting', true),
            ],
            'retention_pruning' => [
                'name' => '1-Year Rolling Retention Pruner',
                'title' => '1-Year Rolling Retention Pruner',
                'icon' => '🧹',
                'setting_key' => 'cron_task_retention_pruning',
                'frequency' => '23:00 IST Nightly',
                'timing' => '23:00 IST Nightly',
                'purpose' => 'Prunes records >365 days; keeps database fast (~35MB)',
                'is_active' => (bool) SystemSetting::get('cron_task_retention_pruning', true),
                'enabled' => (bool) SystemSetting::get('cron_task_retention_pruning', true),
            ],
            'data_integrity' => [
                'name' => 'Data Integrity Auditor',
                'title' => 'Data Integrity Auditor',
                'icon' => '🛡️',
                'setting_key' => 'cron_task_data_integrity',
                'frequency' => '03:00 IST Nightly',
                'timing' => '03:00 IST Nightly',
                'purpose' => 'Flags anomalously stale or isolated price points',
                'is_active' => (bool) SystemSetting::get('cron_task_data_integrity', true),
                'enabled' => (bool) SystemSetting::get('cron_task_data_integrity', true),
            ],
        ];

        $totalMarkets = Market::karnataka()->count();
        $reportingMarketsCount = MarketPrice::where('price_date', $latestPriceDate)
            ->distinct('market_id')
            ->count('market_id');
        $marketCoveragePercent = $totalMarkets > 0
            ? round(($reportingMarketsCount / $totalMarkets) * 100, 1)
            : 0;

        // Rolling 7-day active trading horizon
        $rollingWindowDays = 7;
        $rollingWindowStartDate = Carbon::parse($latestPriceDate)->subDays($rollingWindowDays)->toDateString();
        $weeklyReportingMarketsCount = MarketPrice::whereBetween('price_date', [$rollingWindowStartDate, $latestPriceDate])
            ->distinct('market_id')
            ->count('market_id');
        $weeklyCoveragePercent = $totalMarkets > 0
            ? round(($weeklyReportingMarketsCount / $totalMarkets) * 100, 1)
            : 0;

        // Mandi Network Breakdown for Inspection Drawer
        $todayMarketIds = MarketPrice::where('price_date', $latestPriceDate)
            ->distinct('market_id')
            ->pluck('market_id')
            ->flip()
            ->toArray();

        $weekMarketIds = MarketPrice::whereBetween('price_date', [$rollingWindowStartDate, $latestPriceDate])
            ->distinct('market_id')
            ->pluck('market_id')
            ->flip()
            ->toArray();

        $marketPriceAggs = MarketPrice::selectRaw('market_id, MAX(price_date) as last_date, COUNT(DISTINCT crop_id) as crops_count, COUNT(*) as quotes_count')
            ->groupBy('market_id')
            ->get()
            ->keyBy('market_id');

        $karnatakaMarkets = Market::karnataka()
            ->with(['district'])
            ->orderBy('name')
            ->get()
            ->map(function ($market) use ($todayMarketIds, $weekMarketIds, $marketPriceAggs) {
                $stat = $marketPriceAggs->get($market->id);
                $isToday = isset($todayMarketIds[$market->id]);
                $isWeek = isset($weekMarketIds[$market->id]);

                $status = $isToday ? 'active_today' : ($isWeek ? 'active_week' : 'dormant');
                $lastTraded = $stat?->last_date;
                $daysAgo = $lastTraded ? Carbon::parse($lastTraded)->diffInDays(Carbon::today()) : null;

                return [
                    'id' => $market->id,
                    'code' => $market->code,
                    'name' => $market->name,
                    'name_kn' => $market->name_kn,
                    'district' => $market->district?->name ?? 'Karnataka',
                    'market_type' => $market->market_type ?? 'APMC Market Yard',
                    'status' => $status,
                    'last_traded' => $lastTraded,
                    'days_ago' => $daysAgo,
                    'crops_count' => (int) ($stat?->crops_count ?? 0),
                    'quotes_count' => (int) ($stat?->quotes_count ?? 0),
                ];
            });

        $mandiNetworkStats = [
            'total' => $totalMarkets,
            'today_count' => $reportingMarketsCount,
            'today_percent' => $marketCoveragePercent,
            'week_count' => $weeklyReportingMarketsCount,
            'week_percent' => $weeklyCoveragePercent,
            'dormant_count' => max(0, $totalMarkets - $weeklyReportingMarketsCount),
            'dormant_percent' => $totalMarkets > 0 ? round((max(0, $totalMarkets - $weeklyReportingMarketsCount) / $totalMarkets) * 100, 1) : 0,
            'markets' => $karnatakaMarkets,
        ];

        // Daily / Ingestion volume
        $rawTodayCount = MarketPriceRaw::whereDate('received_at', $today)->count();
        $rawTotalCount = MarketPriceRaw::count();
        $rawProcessedCount = MarketPriceRaw::where('processing_status', 'processed')->count();
        $rawRejectedCount = MarketPriceRaw::where('processing_status', 'rejected')->count();
        $rawDuplicateCount = MarketPriceRaw::where('processing_status', 'duplicate')->count();

        // Canonical prices count
        $totalPricesCount = MarketPrice::count();
        $todayPricesCount = MarketPrice::where('price_date', $latestPriceDate)->count();

        // Data Sources with latest sync
        $dataSources = DataSource::with(['syncLogs' => fn ($q) => $q->latest('started_at')->limit(1)])
            ->orderBy('name')
            ->get();

        // Forecast Health
        $forecastStats = [
            'total_models' => ForecastModel::count(),
            'active_models' => ForecastModel::where('is_active', true)->count(),
            'total_projections' => PriceForecast::count(),
            'recent_runs_count' => ForecastRun::where('status', 'completed')->count(),
            'avg_mape' => round((float) (ForecastMetric::avg('mape') ?? 0), 2),
        ];

        $stats = [
            'districts_count' => District::count(),
            'markets_count' => $totalMarkets,
            'reporting_markets_count' => $reportingMarketsCount,
            'market_coverage_percent' => $marketCoveragePercent,
            'weekly_reporting_markets_count' => $weeklyReportingMarketsCount,
            'weekly_coverage_percent' => $weeklyCoveragePercent,
            'rolling_window_days' => $rollingWindowDays,
            'latest_price_date' => $latestPriceDate,
            'crops_count' => Crop::count(),
            'active_crops_count' => Crop::where('is_active', true)->count(),
            'total_prices_count' => $totalPricesCount,
            'today_prices_count' => $todayPricesCount,
            'raw_today_count' => $rawTodayCount,
            'raw_total_count' => $rawTotalCount,
            'raw_processed_count' => $rawProcessedCount,
            'raw_rejected_count' => $rawRejectedCount,
            'raw_duplicate_count' => $rawDuplicateCount,
            'enabled_flags_count' => FeatureFlag::where('is_enabled', true)->count(),
            'total_flags_count' => FeatureFlag::count(),
        ];

        // Recent Audit Logs
        $recentAuditLogs = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        // Recent rejected records (alerts)
        $recentRejected = MarketPriceRaw::where('processing_status', 'rejected')
            ->with('dataSource')
            ->latest('received_at')
            ->limit(5)
            ->get();

        $featureFlags = FeatureFlag::orderBy('name')->get();

        return view('admin.dashboard', compact(
            'stats',
            'dataSources',
            'forecastStats',
            'recentAuditLogs',
            'recentRejected',
            'featureFlags',
            'mandiNetworkStats',
            'cronStatus'
        ));
    }

    /**
     * Test or manually tick the Laravel scheduler and update the heartbeat.
     */
    public function runSchedulerTest(): RedirectResponse
    {
        try {
            Artisan::call('schedule:run');
            $now = now();
            Cache::forever('scheduler_last_heartbeat', $now);
            try {
                SystemSetting::set('scheduler_last_heartbeat', $now->toDateTimeString(), 'string', 'system', 'Timestamp of last scheduler execution');
            } catch (\Throwable $e) {}

            return redirect()->back()->with('success', 'Scheduler tick completed successfully! Heartbeat recorded at ' . now()->format('h:i:s A') . '.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Scheduler test encountered an issue: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active/paused state of a specific background cron task.
     */
    public function toggleScheduledTask(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'task' => ['required_without:task_key', 'string', 'in:mandi_prices,weather_sync,analytics_stats,forecasting,retention_pruning,data_integrity'],
            'task_key' => ['required_without:task', 'string', 'in:mandi_prices,weather_sync,analytics_stats,forecasting,retention_pruning,data_integrity'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $taskKey = $validated['task'] ?? $validated['task_key'];
        $settingKey = 'cron_task_' . $taskKey;
        $currentValue = (bool) SystemSetting::get($settingKey, true);
        
        $newValue = $request->has('enabled')
            ? $request->boolean('enabled')
            : !$currentValue;

        SystemSetting::set($settingKey, $newValue ? 'true' : 'false', 'boolean', 'cron', "Master enable toggle for scheduled task {$taskKey}");
        Cache::forget('cron_task_statuses');

        AuditLog::log(
            'toggle_scheduled_cron_task',
            'SystemSetting',
            null,
            [$settingKey => $currentValue],
            [$settingKey => $newValue]
        );

        $taskName = ucwords(str_replace('_', ' ', $taskKey));
        $msg = $newValue
            ? "Scheduled task '{$taskName}' resumed in cPanel cron."
            : "Scheduled task '{$taskName}' paused from cPanel cron.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'task' => $taskKey,
                'task_key' => $taskKey,
                'enabled' => $newValue,
                'is_active' => $newValue,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }
}
