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

            if ($prices->isNotEmpty()) {
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

            // Fall back to state-level daily trends if market has no records in this window
            $stateTrend = $this->getDailyTrends($cropId, null, $days);
            $stateTrend['scope'] = 'state_fallback';
            return $stateTrend;
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
            1 => 'ಜನವರಿ',
            2 => 'ಫೆಬ್ರವರಿ',
            3 => 'ಮಾರ್ಚ್',
            4 => 'ಏಪ್ರಿಲ್',
            5 => 'ಮೇ',
            6 => 'ಜೂನ್',
            7 => 'ಜುಲೈ',
            8 => 'ಆಗಸ್ಟ್',
            9 => 'ಸೆಪ್ಟೆಂಬರ್',
            10 => 'ಅಕ್ಟೋಬರ್',
            11 => 'ನವೆಂಬರ್',
            12 => 'ಡಿಸೆಂಬರ್',
        ];

        $englishMonths = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $shortKannadaMonths = [
            1 => 'ಜನ',
            2 => 'ಫೆಬ್ರ',
            3 => 'ಮಾರ್ಚ್',
            4 => 'ಏಪ್ರಿ',
            5 => 'ಮೇ',
            6 => 'ಜೂನ್',
            7 => 'ಜುಲೈ',
            8 => 'ಆಗ',
            9 => 'ಸೆಪ್',
            10 => 'ಅಕ್ಟೋ',
            11 => 'ನವೆಂ',
            12 => 'ಡಿಸೆಂ',
        ];

        $shortEnglishMonths = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
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

        // If market-specific has fewer than 2 months of data, fall back to state-level
        if ($marketId !== null && $results->count() < 2) {
            return $this->getSeasonalAnalysis($cropId, null);
        }

        $distinctMonthsCount = $results->count();

        // If even state-level has fewer than 2 distinct months, data is insufficient for reliable seasonal peaks
        if ($distinctMonthsCount < 2) {
            $monthlyProfile = [];
            for ($m = 1; $m <= 12; $m++) {
                $row = $results->get($m);
                $monthlyProfile[] = [
                    'month' => $m,
                    'name_kn' => $kannadaMonths[$m],
                    'name_en' => $englishMonths[$m],
                    'short_name_kn' => $shortKannadaMonths[$m],
                    'short_name_en' => $shortEnglishMonths[$m],
                    'avg_price' => $row ? round((float) $row->avg_price, 2) : 0.0,
                    'min_price' => $row ? round((float) $row->min_price, 2) : 0.0,
                    'max_price' => $row ? round((float) $row->max_price, 2) : 0.0,
                    'seasonal_index' => 0.0,
                    'index_percentage' => 0.0,
                    'bar_height_percent' => 0,
                    'tier' => 'insufficient',
                    'is_peak' => false,
                    'observations' => $row ? (int) $row->total_obs : 0,
                    'arrivals' => $row ? round((float) $row->total_arr, 2) : 0.0,
                    'category' => 'insufficient',
                    'badge_kn' => 'ಮಾಹಿತಿ ಲಭ್ಯವಿಲ್ಲ (No Data)',
                    'badge_color' => 'stone',
                    'icon' => '⚪',
                ];
            }

            return [
                'annual_baseline' => round((float) ($results->avg('avg_price') ?: 0), 2),
                'monthly_profile' => $monthlyProfile,
                'best_months' => [],
                'peak_months_kn' => [],
                'peak_months_en' => [],
                'lead_summary_kn' => "ವಿಶ್ವಾಸಾರ್ಹ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆಗೆ ಕನಿಷ್ಠ 2 ಪ್ರತ್ಯೇಕ ತಿಂಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ.",
                'lead_summary_en' => "At least 2 distinct months of market price records are required for seasonal analysis.",
                'is_sufficient' => false,
                'has_seasonal_data' => false,
                'distinct_months' => $distinctMonthsCount,
                'message_kn' => "ವಿಶ್ವಾಸಾರ್ಹ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆಗೆ ಕನಿಷ್ಠ 2 ಪ್ರತ್ಯೇಕ ತಿಂಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ (ಪ್ರಸ್ತುತ {$distinctMonthsCount} ತಿಂಗಳ ದರ ಲಭ್ಯವಿದೆ).",
                'message_en' => "At least 2 distinct months of market price records are required for seasonal analysis (currently {$distinctMonthsCount} month(s) available).",
            ];
        }

        // Overall annual baseline average across recorded months
        $overallAvg = (float) $results->avg('avg_price') ?: 0;

        $monthlyProfile = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $results->get($m);
            if ($row) {
                $avgPrice = round((float) $row->avg_price, 2);
                $minPrice = round((float) $row->min_price, 2);
                $maxPrice = round((float) $row->max_price, 2);
                $obsCount = (int) $row->total_obs;
                $arrivals = round((float) $row->total_arr, 2);
                $seasonalIndex = ($overallAvg > 0 && $avgPrice > 0)
                    ? round($avgPrice / $overallAvg, 4)
                    : 1.0000;
            } else {
                // Month has no observations in database
                $avgPrice = 0.0;
                $minPrice = 0.0;
                $maxPrice = 0.0;
                $obsCount = 0;
                $arrivals = 0.0;
                $seasonalIndex = 0.0;
            }

            $monthlyProfile[] = [
                'month' => $m,
                'name_kn' => $kannadaMonths[$m],
                'name_en' => $englishMonths[$m],
                'short_name_kn' => $shortKannadaMonths[$m],
                'short_name_en' => $shortEnglishMonths[$m],
                'avg_price' => $avgPrice,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'seasonal_index' => $seasonalIndex,
                'index_percentage' => $seasonalIndex > 0 ? round(($seasonalIndex - 1.0) * 100, 1) : 0.0,
                'observations' => $obsCount,
                'arrivals' => $arrivals,
                'category' => 'insufficient',
                'tier' => 'insufficient',
                'is_peak' => false,
                'badge_kn' => 'ಮಾಹಿತಿ ಲಭ್ಯವಿಲ್ಲ (No Data)',
                'badge_color' => 'stone',
                'icon' => '⚪',
            ];
        }

        // Relative tier classification based on recorded price rankings
        $recordedProfile = collect($monthlyProfile)->where('observations', '>', 0);
        $totalRecorded = $recordedProfile->count();
        $sortedRecorded = $recordedProfile->sortByDesc('avg_price')->values();

        $peakCount = max(1, (int) round($totalRecorded * 0.25));
        $leanCount = max(1, (int) round($totalRecorded * 0.25));

        $peakMonthNums = $sortedRecorded->take(min(3, $peakCount))->pluck('month')->all();
        $leanMonthNums = $sortedRecorded->reverse()->take(min(3, $leanCount))->pluck('month')->all();
        $leanMonthNums = array_values(array_diff($leanMonthNums, $peakMonthNums));

        // Incorporate absolute bounds (index >= 1.05 peak, index <= 0.95 lean)
        foreach ($monthlyProfile as &$item) {
            if ($item['observations'] > 0 && $item['avg_price'] > 0) {
                if ($item['seasonal_index'] >= 1.05 && !in_array($item['month'], $peakMonthNums) && count($peakMonthNums) < 3) {
                    $peakMonthNums[] = $item['month'];
                }
                if ($item['seasonal_index'] <= 0.95 && !in_array($item['month'], $leanMonthNums) && !in_array($item['month'], $peakMonthNums) && count($leanMonthNums) < 3) {
                    $leanMonthNums[] = $item['month'];
                }
            }
        }
        unset($item);

        // Assign tiers and presentation attributes
        foreach ($monthlyProfile as &$item) {
            if ($item['observations'] === 0 || $item['avg_price'] <= 0) {
                $item['category'] = 'insufficient';
                $item['tier'] = 'insufficient';
                $item['badge_kn'] = 'ಮಾಹಿತಿ ಲಭ್ಯವಿಲ್ಲ';
                $item['badge_en'] = 'No Data';
                $item['badge_color'] = 'stone';
                $item['icon'] = '⚪';
                $item['is_peak'] = false;
            } elseif (in_array($item['month'], $peakMonthNums)) {
                $item['category'] = 'peak';
                $item['tier'] = 'pk';
                $item['badge_kn'] = 'ಅತ್ಯುತ್ತಮ ಧಾರಣೆ';
                $item['badge_en'] = 'Peak Price';
                $item['badge_color'] = 'emerald';
                $item['icon'] = '⭐';
                $item['is_peak'] = true;
            } elseif (in_array($item['month'], $leanMonthNums)) {
                $item['category'] = 'lean';
                $item['tier'] = 'lo';
                $item['badge_kn'] = 'ಆವಕ ಹೆಚ್ಚಳ / ಕಡಿಮೆ ಬೆಲೆ';
                $item['badge_en'] = 'High Arrivals / Low Price';
                $item['badge_color'] = 'rose';
                $item['icon'] = '📉';
                $item['is_peak'] = false;
            } else {
                $item['category'] = 'average';
                $item['tier'] = 'mid';
                $item['badge_kn'] = 'ಸಾಧಾರಣ ಧಾರಣೆ';
                $item['badge_en'] = 'Normal Price';
                $item['badge_color'] = 'amber';
                $item['icon'] = '⚖️';
                $item['is_peak'] = false;
            }
        }
        unset($item);

        // Proportional Bar Heights (Normalized between 18% floor and 100% ceiling for clear visual contrast)
        $recordedForHeights = collect($monthlyProfile)->where('observations', '>', 0)->where('avg_price', '>', 0);
        $minRecordedAvg = (float) ($recordedForHeights->min('avg_price') ?: 0.0);
        $maxRecordedAvg = (float) ($recordedForHeights->max('avg_price') ?: 0.0);
        $priceSpread = $maxRecordedAvg - $minRecordedAvg;

        foreach ($monthlyProfile as &$item) {
            if ($item['observations'] > 0 && $item['avg_price'] > 0) {
                if ($priceSpread > 0) {
                    $ratio = ($item['avg_price'] - $minRecordedAvg) / $priceSpread;
                    $item['bar_height_percent'] = (int) round(18 + (82 * $ratio));
                } else {
                    $item['bar_height_percent'] = 100;
                }
            } else {
                $item['bar_height_percent'] = 0;
            }
        }
        unset($item);

        // Identify Top 3 Best Months to Sell (from highest average price recorded months that are peak or above-average)
        $bestMonths = collect($monthlyProfile)
            ->filter(fn ($item) => $item['observations'] > 0 && $item['avg_price'] > 0 && $item['tier'] !== 'lo' && $item['tier'] !== 'insufficient')
            ->sortByDesc('avg_price')
            ->take(3)
            ->values()
            ->map(function ($item, $rank) {
                return [
                    'rank' => $rank + 1,
                    'month_name_kn' => $item['name_kn'],
                    'month_name_en' => $item['name_en'],
                    'short_name_kn' => $item['short_name_kn'],
                    'short_name_en' => $item['short_name_en'],
                    'avg_price' => $item['avg_price'],
                    'seasonal_index' => $item['seasonal_index'],
                    'premium_percent' => round(($item['seasonal_index'] - 1.0) * 100, 1),
                ];
            })->toArray();

        // If all recorded months happened to be lean (fallback), use the highest price recorded month
        if (empty($bestMonths) && $recordedProfile->isNotEmpty()) {
            $topItem = $recordedProfile->sortByDesc('avg_price')->first();
            $bestMonths = [[
                'rank' => 1,
                'month_name_kn' => $topItem['name_kn'],
                'month_name_en' => $topItem['name_en'],
                'short_name_kn' => $topItem['short_name_kn'],
                'short_name_en' => $topItem['short_name_en'],
                'avg_price' => $topItem['avg_price'],
                'seasonal_index' => $topItem['seasonal_index'],
                'premium_percent' => round(($topItem['seasonal_index'] - 1.0) * 100, 1),
            ]];
        }

        // Extract peak month short names for natural language guidance
        $peakMonthsKn = collect($bestMonths)->pluck('short_name_kn')->take(3)->filter()->values();
        $peakMonthsEn = collect($bestMonths)->pluck('short_name_en')->take(3)->filter()->values();

        $leadSummaryKn = $peakMonthsKn->isNotEmpty()
            ? "ಸಾಮಾನ್ಯವಾಗಿ " . $peakMonthsKn->implode(', ') . " ತಿಂಗಳಲ್ಲಿ ಬೆಲೆ ಹೆಚ್ಚು — ಆ ಸಮಯಕ್ಕೆ ಬೆಳೆ ಮಾರಲು ಸಿದ್ಧವಾಗಿರುವಂತೆ ಯೋಜಿಸಿ."
            : "ಕರ್ನಾಟಕ ಮಾರುಕಟ್ಟೆಗಳಲ್ಲಿನ ಐತಿಹಾಸಿಕ ಆವಕ ಮತ್ತು ಬೇಡಿಕೆಯ ಆಧಾರದ ಮೇಲೆ ಬೆಲೆ ವ್ಯತ್ಯಾಸವಾಗುತ್ತದೆ.";

        $leadSummaryEn = $peakMonthsEn->isNotEmpty()
            ? "Prices are usually highest around " . $peakMonthsEn->implode(', ') . " — plan your crop to be ready to sell then."
            : "Prices vary seasonally based on market arrivals and demand.";

        return [
            'annual_baseline' => round((float) $overallAvg, 2),
            'monthly_profile' => $monthlyProfile,
            'best_months' => $bestMonths,
            'peak_months_kn' => $peakMonthsKn->toArray(),
            'peak_months_en' => $peakMonthsEn->toArray(),
            'lead_summary_kn' => $leadSummaryKn,
            'lead_summary_en' => $leadSummaryEn,
            'is_sufficient' => count($bestMonths) > 0,
            'has_seasonal_data' => count($bestMonths) > 0,
            'distinct_months' => $distinctMonthsCount,
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
            if ($marketId !== null) {
                return $this->getStatisticalSummary($cropId, null, $days);
            }

            return [
                'min_price' => 0,
                'max_price' => 0,
                'avg_price' => 0,
                'std_dev' => 0,
                'volatility_rating' => 'ಕಡಿಮೆ / ಸ್ಥಿರ',
                'volatility_rating_kn' => 'ಕಡಿಮೆ / ಸ್ಥಿರ',
                'volatility_rating_en' => 'Stable / Low',
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
            $volatilityRatingKn = 'ಅಧಿಕ ಏರಿಳಿತ';
            $volatilityRatingEn = 'High Volatility';
            $volatilityColor = 'rose';
        } elseif ($volatilityPercent > 8) {
            $volatilityRatingKn = 'ಮಧ್ಯಮ ಏರಿಳಿತ';
            $volatilityRatingEn = 'Moderate Volatility';
            $volatilityColor = 'amber';
        } else {
            $volatilityRatingKn = 'ಕಡಿಮೆ ಏರಿಳಿತ / ಸ್ಥಿರ';
            $volatilityRatingEn = 'Stable / Low Volatility';
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
            'volatility_rating' => $volatilityRatingKn,
            'volatility_rating_kn' => $volatilityRatingKn,
            'volatility_rating_en' => $volatilityRatingEn,
            'volatility_color' => $volatilityColor,
            'observations_count' => $prices->count(),
            'first_price' => $firstPrice,
            'last_price' => $lastPrice,
            'price_change_percent' => $changePercent,
            'trend_direction' => $trendDirection,
        ];
    }
}
