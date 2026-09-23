<?php

namespace App\Services\Analytics;

use App\Models\Crop;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\PriceDailyStatistic;
use App\Models\PriceMonthlyStatistic;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HistoricalAnalyticsService
{
    /**
     * Compute and persist daily price statistics for Karnataka state.
     */
    public function computeDailyStatistics(?string $date = null, ?int $cropId = null): int
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();
        $karnataka = State::where('code', 'KA')->first();

        if (! $karnataka) {
            return 0;
        }

        $query = MarketPrice::karnataka()
            ->where('price_date', $targetDate)
            ->selectRaw('
                crop_id,
                variety_id,
                AVG(modal_price) as avg_modal,
                MIN(min_price) as min_modal,
                MAX(max_price) as max_modal,
                SUM(COALESCE(arrival_quantity, 0)) as total_arrivals,
                COUNT(DISTINCT market_id) as active_markets
            ')
            ->groupBy('crop_id', 'variety_id');

        if ($cropId !== null) {
            $query->where('crop_id', $cropId);
        }

        $records = $query->get();
        $savedCount = 0;

        foreach ($records as $record) {
            PriceDailyStatistic::updateOrCreate(
                [
                    'crop_id' => $record->crop_id,
                    'variety_id' => $record->variety_id,
                    'state_id' => $karnataka->id,
                    'record_date' => $targetDate,
                ],
                [
                    'avg_modal_price' => round((float) $record->avg_modal, 2),
                    'min_modal_price' => round((float) $record->min_modal, 2),
                    'max_modal_price' => round((float) $record->max_modal, 2),
                    'total_arrival_quantity' => round((float) $record->total_arrivals, 2),
                    'active_markets_count' => (int) $record->active_markets,
                ]
            );
            $savedCount++;
        }

        return $savedCount;
    }

    /**
     * Compute and persist monthly statistics and seasonal index.
     */
    public function computeMonthlyStatistics(?int $year = null, ?int $month = null, ?int $cropId = null): int
    {
        $year = $year ?? (int) Carbon::now()->year;
        $month = $month ?? (int) Carbon::now()->month;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        // 1. Compute state-level monthly aggregate (market_id = null)
        $stateQuery = MarketPrice::karnataka()
            ->whereBetween('price_date', [$startDate, $endDate])
            ->selectRaw('
                crop_id,
                variety_id,
                AVG(modal_price) as avg_modal,
                MIN(min_price) as min_val,
                MAX(max_price) as max_val,
                COUNT(id) as obs_count,
                SUM(COALESCE(arrival_quantity, 0)) as total_arrivals
            ')
            ->groupBy('crop_id', 'variety_id');

        if ($cropId !== null) {
            $stateQuery->where('crop_id', $cropId);
        }

        $stateRecords = $stateQuery->get();
        $savedCount = 0;

        foreach ($stateRecords as $rec) {
            PriceMonthlyStatistic::updateOrCreate(
                [
                    'crop_id' => $rec->crop_id,
                    'variety_id' => $rec->variety_id,
                    'market_id' => null,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'avg_modal_price' => round((float) $rec->avg_modal, 2),
                    'min_price' => round((float) $rec->min_val, 2),
                    'max_price' => round((float) $rec->max_val, 2),
                    'observations_count' => (int) $rec->obs_count,
                    'total_arrivals' => round((float) $rec->total_arrivals, 2),
                ]
            );
            $savedCount++;
        }

        // 2. Compute individual market-level monthly aggregates
        $marketQuery = MarketPrice::karnataka()
            ->whereBetween('price_date', [$startDate, $endDate])
            ->selectRaw('
                crop_id,
                variety_id,
                market_id,
                AVG(modal_price) as avg_modal,
                MIN(min_price) as min_val,
                MAX(max_price) as max_val,
                COUNT(id) as obs_count,
                SUM(COALESCE(arrival_quantity, 0)) as total_arrivals
            ')
            ->groupBy('crop_id', 'variety_id', 'market_id');

        if ($cropId !== null) {
            $marketQuery->where('crop_id', $cropId);
        }

        $marketRecords = $marketQuery->get();

        foreach ($marketRecords as $rec) {
            PriceMonthlyStatistic::updateOrCreate(
                [
                    'crop_id' => $rec->crop_id,
                    'variety_id' => $rec->variety_id,
                    'market_id' => $rec->market_id,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'avg_modal_price' => round((float) $rec->avg_modal, 2),
                    'min_price' => round((float) $rec->min_val, 2),
                    'max_price' => round((float) $rec->max_val, 2),
                    'observations_count' => (int) $rec->obs_count,
                    'total_arrivals' => round((float) $rec->total_arrivals, 2),
                ]
            );
            $savedCount++;
        }

        // 3. Recalculate seasonal indices for updated crops
        $this->updateSeasonalIndices($cropId);

        return $savedCount;
    }

    /**
     * Update 5-year seasonal index (S_m = P_bar_m / P_bar_annual) for crops.
     */
    public function updateSeasonalIndices(?int $cropId = null): void
    {
        $cropIds = $cropId ? [$cropId] : Crop::where('is_active', true)->pluck('id')->toArray();

        foreach ($cropIds as $cId) {
            // Compute annual baseline average across all recorded months for this crop
            $annualAvg = (float) PriceMonthlyStatistic::where('crop_id', $cId)
                ->whereNull('market_id')
                ->avg('avg_modal_price');

            if ($annualAvg <= 0) {
                continue;
            }

            // Update each monthly record's seasonal index
            $records = PriceMonthlyStatistic::where('crop_id', $cId)
                ->whereNull('market_id')
                ->get();

            foreach ($records as $row) {
                $seasonalIndex = round(((float) $row->avg_modal_price) / $annualAvg, 4);
                $row->update(['seasonal_index' => $seasonalIndex]);
            }
        }
    }

    /**
     * Get daily historical price and arrival timeseries for charting.
     */
    public function getDailyTrends(int $cropId, ?int $marketId = null, int $days = 30): array
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($days - 1);

        if ($marketId !== null) {
            // Market-specific daily trend
            $prices = MarketPrice::karnataka()
                ->where('crop_id', $cropId)
                ->where('market_id', $marketId)
                ->whereBetween('price_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('price_date', 'asc')
                ->get();

            $labels = [];
            $modalPrices = [];
            $minPrices = [];
            $maxPrices = [];
            $arrivals = [];

            foreach ($prices as $p) {
                $labels[] = Carbon::parse($p->price_date)->format('d M');
                $modalPrices[] = (float) $p->modal_price;
                $minPrices[] = (float) ($p->min_price ?? $p->modal_price);
                $maxPrices[] = (float) ($p->max_price ?? $p->modal_price);
                $arrivals[] = (float) ($p->arrival_quantity ?? 0);
            }

            return [
                'labels' => $labels,
                'modal_prices' => $modalPrices,
                'min_prices' => $minPrices,
                'max_prices' => $maxPrices,
                'arrivals' => $arrivals,
                'has_data' => count($labels) > 0,
                'scope' => 'market',
                'market_id' => $marketId,
            ];
        }

        // State-level trend: Try precomputed daily statistics first
        $stats = PriceDailyStatistic::where('crop_id', $cropId)
            ->whereBetween('record_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('record_date', 'asc')
            ->get();

        if ($stats->isNotEmpty()) {
            $grouped = $stats->groupBy(fn ($item) => Carbon::parse($item->record_date)->format('Y-m-d'));
            $labels = [];
            $modalPrices = [];
            $minPrices = [];
            $maxPrices = [];
            $arrivals = [];

            foreach ($grouped as $date => $items) {
                $labels[] = Carbon::parse($date)->format('d M');
                $modalPrices[] = round((float) $items->avg('avg_modal_price'), 2);
                $minPrices[] = round((float) $items->min('min_modal_price'), 2);
                $maxPrices[] = round((float) $items->max('max_modal_price'), 2);
                $arrivals[] = round((float) $items->sum('total_arrival_quantity'), 2);
            }

            return [
                'labels' => $labels,
                'modal_prices' => $modalPrices,
                'min_prices' => $minPrices,
                'max_prices' => $maxPrices,
                'arrivals' => $arrivals,
                'has_data' => count($labels) > 0,
                'scope' => 'state',
                'market_id' => null,
            ];
        }

        // Dynamic fallback directly from MarketPrice if stats table isn't yet backfilled
        $rawPrices = MarketPrice::karnataka()
            ->where('crop_id', $cropId)
            ->whereBetween('price_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('price_date, AVG(modal_price) as avg_modal, MIN(min_price) as min_val, MAX(max_price) as max_val, SUM(COALESCE(arrival_quantity, 0)) as total_arr')
            ->groupBy('price_date')
            ->orderBy('price_date', 'asc')
            ->get();

        $labels = [];
        $modalPrices = [];
        $minPrices = [];
        $maxPrices = [];
        $arrivals = [];

        foreach ($rawPrices as $p) {
            $labels[] = Carbon::parse($p->price_date)->format('d M');
            $modalPrices[] = round((float) $p->avg_modal, 2);
            $minPrices[] = round((float) $p->min_val, 2);
            $maxPrices[] = round((float) $p->max_val, 2);
            $arrivals[] = round((float) $p->total_arr, 2);
        }

        return [
            'labels' => $labels,
            'modal_prices' => $modalPrices,
            'min_prices' => $minPrices,
            'max_prices' => $maxPrices,
            'arrivals' => $arrivals,
            'has_data' => count($labels) > 0,
            'scope' => 'state',
            'market_id' => null,
        ];
    }

    /**
     * Compute 12-month seasonal analysis & Best Months to Sell.
     */
    public function getSeasonalAnalysis(int $cropId, ?int $marketId = null): array
    {
        $kannadaMonths = [
            1 => 'ಜನವರಿ (Jan)',
            2 => 'ಫೆಬ್ರವರಿ (Feb)',
            3 => 'ಮಾರ್ಚ್ (Mar)',
            4 => 'ಏಪ್ರಿಲ್ (Apr)',
            5 => 'ಮೇ (May)',
            6 => 'ಜೂನ್ (Jun)',
            7 => 'ಜುಲೈ (Jul)',
            8 => 'ಆಗಸ್ಟ್ (Aug)',
            9 => 'ಸೆಪ್ಟೆಂಬರ್ (Sep)',
            10 => 'ಅಕ್ಟೋಬರ್ (Oct)',
            11 => 'ನವೆಂಬರ್ (Nov)',
            12 => 'ಡಿಸೆಂಬರ್ (Dec)',
        ];

        $englishMonths = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        // Fetch historical monthly averages over multi-year records
        $query = DB::table('market_prices')
            ->join('markets', 'market_prices.market_id', '=', 'markets.id')
            ->join('districts', 'markets.district_id', '=', 'districts.id')
            ->join('states', 'districts.state_id', '=', 'states.id')
            ->where('states.code', 'KA')
            ->where('market_prices.crop_id', $cropId)
            ->selectRaw('
                MONTH(price_date) as month_num,
                AVG(modal_price) as avg_price,
                MIN(min_price) as min_price,
                MAX(max_price) as max_price,
                COUNT(market_prices.id) as total_obs,
                SUM(COALESCE(arrival_quantity, 0)) as total_arr
            ')
            ->groupBy(DB::raw('MONTH(price_date)'));

        if ($marketId !== null) {
            $query->where('market_prices.market_id', $marketId);
        }

        $results = $query->get()->keyBy('month_num');

        // Overall annual baseline average
        $overallAvg = $results->avg('avg_price') ?: 0;

        $monthlyProfile = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $results->get($m);
            $avgPrice = $row ? round((float) $row->avg_price, 2) : 0;
            $minPrice = $row ? round((float) $row->min_price, 2) : 0;
            $maxPrice = $row ? round((float) $row->max_price, 2) : 0;
            $obsCount = $row ? (int) $row->total_obs : 0;
            $arrivals = $row ? round((float) $row->total_arr, 2) : 0;

            $seasonalIndex = ($overallAvg > 0 && $avgPrice > 0)
                ? round($avgPrice / $overallAvg, 4)
                : 1.0000;

            // Classification
            if ($seasonalIndex >= 1.06) {
                $category = 'peak';
                $badgeKn = 'ಅತ್ಯುತ್ತಮ ಧಾರಣೆ (Peak Price)';
                $badgeColor = 'emerald';
                $icon = '⭐';
            } elseif ($seasonalIndex >= 1.01) {
                $category = 'above_average';
                $badgeKn = 'ಉತ್ತಮ ಬೆಲೆ (Above Average)';
                $badgeColor = 'blue';
                $icon = '📈';
            } elseif ($seasonalIndex >= 0.95) {
                $category = 'average';
                $badgeKn = 'ಸಾಧಾರಣ ಧಾರಣೆ (Normal)';
                $badgeColor = 'amber';
                $icon = '⚖️';
            } else {
                $category = 'lean';
                $badgeKn = 'ಆವಕ ಹೆಚ್ಚಳ / ಕಡಿಮೆ ಬೆಲೆ (Lean)';
                $badgeColor = 'rose';
                $icon = '📉';
            }

            $monthlyProfile[] = [
                'month' => $m,
                'name_kn' => $kannadaMonths[$m],
                'name_en' => $englishMonths[$m],
                'avg_price' => $avgPrice,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'seasonal_index' => $seasonalIndex,
                'index_percentage' => round(($seasonalIndex - 1.0) * 100, 1),
                'observations' => $obsCount,
                'arrivals' => $arrivals,
                'category' => $category,
                'badge_kn' => $badgeKn,
                'badge_color' => $badgeColor,
                'icon' => $icon,
            ];
        }

        // Identify Top 3 Best Months to Sell
        $sortedByPrice = collect($monthlyProfile)
            ->filter(fn ($item) => $item['avg_price'] > 0)
            ->sortByDesc('seasonal_index')
            ->values();

        $bestMonths = $sortedByPrice->take(3)->map(function ($item, $rank) {
            return [
                'rank' => $rank + 1,
                'month_name_kn' => $item['name_kn'],
                'month_name_en' => $item['name_en'],
                'avg_price' => $item['avg_price'],
                'seasonal_index' => $item['seasonal_index'],
                'premium_percent' => round(($item['seasonal_index'] - 1.0) * 100, 1),
            ];
        })->toArray();

        return [
            'annual_baseline' => round((float) $overallAvg, 2),
            'monthly_profile' => $monthlyProfile,
            'best_months' => $bestMonths,
            'has_seasonal_data' => $results->count() > 0,
        ];
    }

    /**
     * Compute statistical summary (min, max, avg, volatility / std dev, total arrivals).
     */
    public function getStatisticalSummary(int $cropId, ?int $marketId = null, int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days - 1)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $query = MarketPrice::karnataka()
            ->where('crop_id', $cropId)
            ->whereBetween('price_date', [$startDate, $endDate]);

        if ($marketId !== null) {
            $query->where('market_id', $marketId);
        }

        $prices = $query->pluck('modal_price')->map(fn ($p) => (float) $p);

        if ($prices->isEmpty()) {
            return [
                'min_price' => 0,
                'max_price' => 0,
                'avg_price' => 0,
                'std_dev' => 0,
                'volatility_rating' => 'ಕಡಿಮೆ (Low)',
                'observations_count' => 0,
                'price_change_percent' => 0,
                'trend_direction' => 'stable',
            ];
        }

        $minPrice = $prices->min();
        $maxPrice = $prices->max();
        $avgPrice = round($prices->avg(), 2);

        // Standard Deviation
        $variance = 0.0;
        foreach ($prices as $p) {
            $variance += pow($p - $avgPrice, 2);
        }
        $stdDev = $prices->count() > 1 ? round(sqrt($variance / ($prices->count() - 1)), 2) : 0;
        $volatilityPercent = $avgPrice > 0 ? round(($stdDev / $avgPrice) * 100, 1) : 0;

        // Volatility categorization
        if ($volatilityPercent > 15) {
            $volatilityRating = 'ಅಧಿಕ ಏರಿಳಿತ (High Volatility)';
            $volatilityColor = 'rose';
        } elseif ($volatilityPercent > 8) {
            $volatilityRating = 'ಮಧ್ಯಮ ಏರಿಳಿತ (Moderate Volatility)';
            $volatilityColor = 'amber';
        } else {
            $volatilityRating = 'ಕಡಿಮೆ ಏರಿಳಿತ / ಸ್ಥಿರ (Stable / Low)';
            $volatilityColor = 'emerald';
        }

        // Price change percentage from first to last available in period
        $firstPrice = (float) ($query->clone()->orderBy('price_date', 'asc')->first()?->modal_price ?? $avgPrice);
        $lastPrice = (float) ($query->clone()->orderBy('price_date', 'desc')->first()?->modal_price ?? $avgPrice);
        $changePercent = $firstPrice > 0 ? round((($lastPrice - $firstPrice) / $firstPrice) * 100, 1) : 0;

        $trendDirection = 'stable';
        if ($changePercent > 1.5) {
            $trendDirection = 'up';
        } elseif ($changePercent < -1.5) {
            $trendDirection = 'down';
        }

        return [
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'avg_price' => $avgPrice,
            'std_dev' => $stdDev,
            'volatility_percent' => $volatilityPercent,
            'volatility_rating' => $volatilityRating,
            'volatility_color' => $volatilityColor,
            'observations_count' => $prices->count(),
            'first_price' => $firstPrice,
            'last_price' => $lastPrice,
            'price_change_percent' => $changePercent,
            'trend_direction' => $trendDirection,
        ];
    }
}
