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
use Carbon\Carbon;
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

        $totalMarkets = Market::karnataka()->count();
        $reportingMarketsCount = MarketPrice::where('price_date', $latestPriceDate)
            ->distinct('market_id')
            ->count('market_id');
        $marketCoveragePercent = $totalMarkets > 0
            ? round(($reportingMarketsCount / $totalMarkets) * 100, 1)
            : 0;

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
            'featureFlags'
        ));
    }
}
