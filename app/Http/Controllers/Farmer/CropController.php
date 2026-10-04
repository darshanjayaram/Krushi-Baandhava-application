<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CuratedVideo;
use App\Models\Article;
use App\Models\Market;
use App\Models\Scheme;
use App\Models\MarketPrice;
use App\Services\Analytics\HistoricalAnalyticsService;
use App\Services\Forecast\ForecastingEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CropController extends Controller
{
    public function __construct(
        protected HistoricalAnalyticsService $analyticsService,
        protected ForecastingEngineService $forecastingService
    ) {}

    /**
     * Display a listing of all agricultural crops/commodities.
     */
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $search = trim((string) $request->query('search', ''));

        $query = Crop::with(['category', 'varieties' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('is_major', 'desc')
            ->orderBy('name');

        if ($categorySlug) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $categorySlug));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_kn', 'like', "%{$search}%")
                  ->orWhere('scientific_name', 'like', "%{$search}%");
            });
        }

        $crops = $query->paginate(36)->withQueryString();

        $categories = CropCategory::where('is_active', true)
            ->withCount(['crops' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('display_order')
            ->get();

        // Get latest price dates for each crop (Strictly Karnataka)
        $latestPricesByCrop = MarketPrice::karnataka()
            ->selectRaw('crop_id, MAX(modal_price) as max_modal, MIN(modal_price) as min_modal, AVG(modal_price) as avg_modal, COUNT(DISTINCT market_id) as mandi_count')
            ->groupBy('crop_id')
            ->get()
            ->keyBy('crop_id');

        return view('farmer.crops.index', compact('crops', 'categories', 'categorySlug', 'search', 'latestPricesByCrop'));
    }

    /**
     * Display the detailed price profile and APMC mandi comparison for a specific crop.
     * Supports filtering by Karnataka APMC mandi (?market=BINNY%20MILL%20%28F%26V%29).
     */
    public function show(string $cropIdentifier, Request $request): View|\Illuminate\Http\JsonResponse
    {
        $cropQuery = Crop::with(['category', 'varieties' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true);

        if (is_numeric($cropIdentifier)) {
            $crop = (clone $cropQuery)->where('id', (int) $cropIdentifier)->first()
                ?? $cropQuery->where('slug', $cropIdentifier)->firstOrFail();
        } else {
            $crop = $cropQuery->where('slug', $cropIdentifier)->firstOrFail();
        }

        $varietyId = $request->query('variety');
        $gradeParam = trim((string) $request->query('grade', ''));
        $marketParam = trim((string) $request->query('market', ''));
        $activeLocale = $request->query('lang') ?: (session('locale') ?: ($request->cookie('locale') ?: app()->getLocale()));

        // Determine if this crop is governed by an official commodity board (Coffee Board or Coconut Board)
        $boardMeta = null;
        if ($crop->isCoffeeBoard()) {
            $boardMeta = [
                'type' => 'coffee_board',
                'badge_en' => 'Coffee Board of India',
                'badge_kn' => 'ಕಾಫಿ ಮಂಡಳಿ ಅಧಿಕೃತ ದರಗಳು',
                'icon' => '☕',
                'authority' => 'Coffee Board of India (Ministry of Commerce & Industry, Govt. of India)',
                'centre_label_kn' => 'ಕಾಫಿ ಮಂಡಳಿ ಕೇಂದ್ರ ಆಯ್ಕೆ',
                'centre_label_en' => 'Select Coffee Board Centre',
                'rates_heading_kn' => 'ಕಾಫಿ ಮಂಡಳಿ ಕೇಂದ್ರವಾರು ದರ ಹೋಲಿಕೆ',
                'rates_heading_en' => 'Official Coffee Board Rates by Centre',
                'theme' => 'coffee',
            ];
        } elseif ($crop->isCoconutBoard()) {
            $boardMeta = [
                'type' => 'coconut_board',
                'badge_en' => 'Coconut Development Board',
                'badge_kn' => 'ತೆಂಗು ಅಭಿವೃದ್ಧಿ ಮಂಡಳಿ ದರಗಳು',
                'icon' => '🥥',
                'authority' => 'Coconut Development Board (Ministry of Agriculture, Govt. of India)',
                'centre_label_kn' => 'ತೆಂಗು ಮಂಡಳಿ ಖರೀದಿ ಕೇಂದ್ರ ಆಯ್ಕೆ',
                'centre_label_en' => 'Select CDB Purchase Centre',
                'rates_heading_kn' => 'ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರವಾರು ದರ ಹೋಲಿಕೆ',
                'rates_heading_en' => 'Official CDB Rates by Centre',
                'theme' => 'coconut',
            ];
        }

        // Base price query builder (Coffee Board authority isolation for coffee; APMC & multi-source for all other crops)
        $basePricesQuery = MarketPrice::karnataka()->where('crop_id', $crop->id);
        if ($crop->isCoffeeBoard()) {
            $basePricesQuery->whereHas('dataSource', function ($dq) {
                $dq->where('code', 'coffee_board');
            });
        }

        // Resolve platform anchor date (max of today and latest platform data date to handle holidays/weekends safely)
        $stalenessThresholdDays = $crop->getStalenessThresholdDays();
        $platformMaxDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();
        $anchorDate = Carbon::today()->gt(Carbon::parse($platformMaxDate)) ? Carbon::today() : Carbon::parse($platformMaxDate);
        $cutoffDate = $crop->getFreshnessCutoffDate($anchorDate->toDateString());

        // Resolve latest date specifically for this crop in Karnataka strictly within the freshness window
        $latestDate = (clone $basePricesQuery)
            ->where('price_date', '>=', $cutoffDate)
            ->max('price_date');

        // Filter available varieties to only those that actually have recorded prices for this crop within freshness window
        $pricedVarietyIds = [];
        if ($latestDate) {
            $pricedVarietyIds = (clone $basePricesQuery)
                ->where('price_date', '>=', $cutoffDate)
                ->where('price_date', '<=', $latestDate)
                ->whereNotNull('variety_id')
                ->pluck('variety_id')
                ->unique()
                ->toArray();
        }

        $availableVarieties = $crop->varieties
            ->whereIn('id', $pricedVarietyIds)
            ->values();

        // If a specific variety was requested but has no prices within the freshness window, clear it so we don't query stale data
        if ($varietyId && !in_array((int)$varietyId, $pricedVarietyIds) && !empty($pricedVarietyIds)) {
            $varietyId = null;
        }

        // Resolve reference coordinates to find nearby markets
        $refLat = null;
        $refLon = null;
        if (session('user_lat') && session('user_lng')) {
            $refLat = (float) session('user_lat');
            $refLon = (float) session('user_lng');
        } elseif ($request->cookie('user_lat') && $request->cookie('user_lng')) {
            $refLat = (float) $request->cookie('user_lat');
            $refLon = (float) $request->cookie('user_lng');
        }

        $selectedDistrictId = $request->cookie('selected_district_id') ?? session('selected_district_id');
        $userDistrict = null;
        if ($selectedDistrictId) {
            $userDistrict = \App\Models\District::find($selectedDistrictId);
            if ($userDistrict && $userDistrict->latitude && $userDistrict->longitude && ($refLat === null || $refLon === null)) {
                $refLat = (float) $userDistrict->latitude;
                $refLon = (float) $userDistrict->longitude;
            }
        }

        if ($refLat === null || $refLon === null) {
            $refLat = 13.9299;
            $refLon = 75.5681;
        }

        // 1. Fetch all distinct Karnataka mandis/centres reporting this crop within the freshness window, sorted by nearby proximity
        $recentMarketPrices = collect();
        if ($latestDate) {
            $recentMarketPrices = (clone $basePricesQuery)
                ->with(['market.district'])
                ->where('price_date', '>=', $cutoffDate)
                ->where('price_date', '<=', $latestDate)
                ->orderBy('price_date', 'desc')
                ->orderBy('modal_price', 'desc')
                ->get();
        }

        $availableMarkets = $recentMarketPrices
            ->groupBy('market_id')
            ->map(function ($records) use ($refLat, $refLon, $userDistrict) {
                $mp = $records->first();
                $m = $mp->market;
                if ($m) {
                    $m->today_modal_price = (float) $mp->modal_price;
                    $m->price_date = $mp->price_date;
                    $m->is_same_district = ($userDistrict && $m->district_id === $userDistrict->id);

                    if ($m->latitude && $m->longitude) {
                        $latFrom = deg2rad($refLat);
                        $lonFrom = deg2rad($refLon);
                        $latTo = deg2rad($m->latitude);
                        $lonTo = deg2rad($m->longitude);

                        $latDelta = $latTo - $latFrom;
                        $lonDelta = $lonTo - $lonFrom;

                        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
                        $m->distance_km = round($angle * 6371, 1);
                    } else {
                        $m->distance_km = 9999;
                    }
                }
                return $m;
            })
            ->filter()
            ->values();

        // Market Discovery & Distance Configuration from Crop Settings
        $marketRadiusKm = (int) ($crop->market_radius_km ?? 300);
        $defaultMarketSort = $crop->default_market_sort ?? 'nearest_first';
        $allowUserSortToggle = (bool) ($crop->allow_user_sort_toggle ?? true);
        $enableSmartBadges = (bool) ($crop->enable_smart_badges ?? true);

        // Identify true nearest market and top paying market across available mandis
        $actualNearestMarket = $availableMarkets->filter(fn($m) => isset($m->distance_km) && $m->distance_km < 9000)
            ->sortBy('distance_km')
            ->first();

        $topRateMarket = $availableMarkets->filter(fn($m) => isset($m->today_modal_price) && $m->today_modal_price > 0)
            ->sortByDesc('today_modal_price')
            ->first();

        // Calculate distance rank (for Proximity sorting)
        $distSorted = $availableMarkets->sort(function ($a, $b) {
            if ($a->is_same_district !== $b->is_same_district) {
                return $b->is_same_district <=> $a->is_same_district;
            }
            if ($a->distance_km !== $b->distance_km) {
                return $a->distance_km <=> $b->distance_km;
            }
            return strcmp($a->name, $b->name);
        })->values();

        $distanceRankMap = [];
        foreach ($distSorted as $idx => $m) {
            $distanceRankMap[$m->id] = $idx + 1;
        }

        // Calculate price rank (for Highest Price sorting)
        $priceSorted = $availableMarkets->sort(function ($a, $b) {
            if ($a->today_modal_price !== $b->today_modal_price) {
                return $b->today_modal_price <=> $a->today_modal_price;
            }
            return $a->distance_km <=> $b->distance_km;
        })->values();

        $priceRankMap = [];
        foreach ($priceSorted as $idx => $m) {
            $priceRankMap[$m->id] = $idx + 1;
        }

        // Tag each market with discovery flags & CSS flex order ranks
        $availableMarkets = $availableMarkets->map(function ($m) use ($actualNearestMarket, $topRateMarket, $marketRadiusKm, $distanceRankMap, $priceRankMap) {
            $m->is_nearest = ($actualNearestMarket && $m->id === $actualNearestMarket->id);
            $m->is_top_rate = ($topRateMarket && $m->id === $topRateMarket->id);
            $m->is_within_radius = ($marketRadiusKm <= 0 || $marketRadiusKm >= 500 || $m->distance_km <= $marketRadiusKm);
            $m->distance_rank = $distanceRankMap[$m->id] ?? 99;
            $m->price_rank = $priceRankMap[$m->id] ?? 99;
            return $m;
        });

        // Ensure at least the closest market is visible even if outside the configured radius
        if ($actualNearestMarket && $availableMarkets->where('is_within_radius', true)->isEmpty()) {
            $first = $availableMarkets->firstWhere('id', $actualNearestMarket->id);
            if ($first) {
                $first->is_within_radius = true;
            }
        }

        // Initial collection sort according to default_market_sort
        if ($defaultMarketSort === 'highest_price_first') {
            $availableMarkets = $availableMarkets->sortBy('price_rank')->values();
        } else {
            $availableMarkets = $availableMarkets->sortBy('distance_rank')->values();
        }

        // 2. Base query for Karnataka mandi/board prices
        $targetMandiDate = $latestDate;
        if ($varietyId && $latestDate) {
            $targetMandiDate = (clone $basePricesQuery)
                ->where('variety_id', $varietyId)
                ->where('price_date', '>=', $cutoffDate)
                ->max('price_date');
        }

        $mandiPrices = collect();
        if ($targetMandiDate) {
            $mandiPricesQuery = (clone $basePricesQuery)
                ->with(['variety', 'market.district', 'dataSource'])
                ->where('price_date', $targetMandiDate);

            if ($varietyId) {
                $mandiPricesQuery->where('variety_id', $varietyId);
            }

            $mandiPrices = $mandiPricesQuery
                ->orderBy('modal_price', 'desc')
                ->get();
        }

        $marketProximityMap = $availableMarkets->keyBy('id');

        // Group prices by APMC / Market so each mandi appears exactly once in the ranking cards
        // with all its varieties presented neatly within that single card
        $mandiGroups = $mandiPrices
            ->groupBy('market_id')
            ->map(function ($prices) use ($marketProximityMap, $userDistrict) {
                $bestRecord = $prices->sortByDesc('modal_price')->first();
                $market = $bestRecord->market;
                $prox = $marketProximityMap->get($market->id);

                if ($prox) {
                    $market->distance_km = $prox->distance_km;
                    $market->is_same_district = $prox->is_same_district;
                } else {
                    $market->is_same_district = ($userDistrict && $market->district_id === $userDistrict->id);
                    $market->distance_km = 9999;
                }

                return (object) [
                    'market' => $market,
                    'best_item' => $bestRecord,
                    'best_modal' => (float) $bestRecord->modal_price,
                    'total_arrivals' => (float) $prices->sum('arrival_quantity'),
                    'arrival_unit' => $bestRecord->arrival_unit ?? 'Qtl',
                    'unit' => $bestRecord->unit ?? 'Quintal',
                    'price_date' => $bestRecord->price_date,
                    'dataSource' => $bestRecord->dataSource,
                    'varieties' => $prices->sortByDesc('modal_price')->values(),
                    'variety_count' => $prices->count(),
                    'distance_km' => $market->distance_km,
                    'is_same_district' => $market->is_same_district,
                ];
            })
            ->sortByDesc('best_modal')
            ->values();

        // Identify the 2 APMCs nearest to current user location
        $nearestSorted = (clone $mandiGroups)->sort(function ($a, $b) use ($marketParam) {
            if ($marketParam !== '') {
                $aMatch = ($a->market->name === $marketParam || $a->market->code === $marketParam);
                $bMatch = ($b->market->name === $marketParam || $b->market->code === $marketParam);
                if ($aMatch !== $bMatch) {
                    return $bMatch <=> $aMatch;
                }
            }

            if ($a->is_same_district !== $b->is_same_district) {
                return $b->is_same_district <=> $a->is_same_district;
            }
            if ($a->distance_km !== $b->distance_km) {
                return $a->distance_km <=> $b->distance_km;
            }
            return $b->best_modal <=> $a->best_modal;
        })->values();

        // 2 APMCs nearest to user location, ranked by best price between them
        $nearestTwoGroups = $nearestSorted->take(2)->sortByDesc('best_modal')->values();

        // Remaining Karnataka mandis for expandable list
        $nearestMarketIds = $nearestTwoGroups->pluck('market.id')->all();
        $allOtherMandiGroups = $mandiGroups->reject(function ($g) use ($nearestMarketIds) {
            return in_array($g->market->id, $nearestMarketIds);
        })->sortByDesc('best_modal')->values();

        // State-level analytics summary for this commodity across all Karnataka mandis/centres
        $allKarnatakaPrices = $latestDate
            ? (clone $basePricesQuery)->with(['market'])->where('price_date', $latestDate)->get()
            : collect();

        $stats = [
            'highest_modal' => $allKarnatakaPrices->max('modal_price') ?? 0,
            'highest_market' => $allKarnatakaPrices->firstWhere('modal_price', $allKarnatakaPrices->max('modal_price'))?->market->name ?? '—',
            'lowest_modal' => $allKarnatakaPrices->min('modal_price') ?? 0,
            'lowest_market' => $allKarnatakaPrices->firstWhere('modal_price', $allKarnatakaPrices->min('modal_price'))?->market->name ?? '—',
            'avg_modal' => $allKarnatakaPrices->avg('modal_price') ?? 0,
            'total_mandis' => $allKarnatakaPrices->pluck('market_id')->unique()->count(),
            'total_arrivals' => $allKarnatakaPrices->sum('arrival_quantity') ?? 0,
            'date_formatted' => $latestDate ? Carbon::parse($latestDate)->format('d M Y') : '—',
        ];

        // Matched selected market if filtered or auto-resolve nearest
        $selectedMarket = null;
        $isMarketParamMatched = false;
        if ($marketParam !== '') {
            $selectedMarket = $availableMarkets->first(function ($m) use ($marketParam) {
                return strcasecmp($m->name, $marketParam) === 0
                    || strcasecmp($m->code ?? '', $marketParam) === 0
                    || $m->name_kn === $marketParam
                    || stripos($m->name, $marketParam) !== false;
            });

            if ($selectedMarket) {
                $isMarketParamMatched = true;
            }
        }

        if (!$selectedMarket && $availableMarkets->isNotEmpty()) {
            // Priority 1: Market in user's home district
            // Priority 2: Nearest market by proximity
            $selectedMarket = $availableMarkets->firstWhere('is_same_district', true) ?? $availableMarkets->first();
        }

        // Check if selected market is outside user's home district and extract distance
        $isNearestFallback = false;
        $isSelectedActualNearest = false;
        $nearestDistanceKm = null;
        if ($selectedMarket) {
            $isNearestFallback = (!$userDistrict || $selectedMarket->district_id !== $userDistrict->id);
            $nearestDistanceKm = (isset($selectedMarket->distance_km) && $selectedMarket->distance_km < 1000)
                ? $selectedMarket->distance_km
                : null;
            $isSelectedActualNearest = ($actualNearestMarket && $selectedMarket->id === $actualNearestMarket->id);
        }

        // Fetch latest traded prices for this selected market across its active trading window (strictly >= cutoffDate)
        // Negilu Krishi alignment: Each variety/grade resolves independently to its own latest trading session record.
        $selectedMarketPrices = collect();
        if ($selectedMarket && $latestDate) {
            $recentMarketPricesRaw = (clone $basePricesQuery)
                ->with(['variety', 'market.district', 'dataSource'])
                ->where('market_id', $selectedMarket->id)
                ->where('price_date', '>=', $cutoffDate)
                ->where('modal_price', '>', 0)
                ->orderBy('price_date', 'desc')
                ->orderBy('modal_price', 'desc')
                ->get();

            // Group by variety (and grade) and take the latest record per variety
            $selectedMarketPrices = $recentMarketPricesRaw
                ->groupBy(function ($item) {
                    return ($item->variety_id ?? 'default') . '_' . ($item->grade ?? '');
                })
                ->map(fn($recs) => $recs->first())
                ->sortByDesc('modal_price')
                ->values();

            // If the selected market has no positive trades within freshness window, fall back to best active market
            if ($selectedMarketPrices->isEmpty() && $availableMarkets->isNotEmpty()) {
                $fallback = $availableMarkets->firstWhere('is_same_district', true) ?? $availableMarkets->first();
                if ($fallback && (!$selectedMarket || $fallback->id !== $selectedMarket->id)) {
                    $selectedMarket = $fallback;
                    $isMarketParamMatched = false;
                    $recentMarketPricesRaw = (clone $basePricesQuery)
                        ->with(['variety', 'market.district', 'dataSource'])
                        ->where('market_id', $selectedMarket->id)
                        ->where('price_date', '>=', $cutoffDate)
                        ->where('modal_price', '>', 0)
                        ->orderBy('price_date', 'desc')
                        ->orderBy('modal_price', 'desc')
                        ->get();

                    $selectedMarketPrices = $recentMarketPricesRaw
                        ->groupBy(function ($item) {
                            return ($item->variety_id ?? 'default') . '_' . ($item->grade ?? '');
                        })
                        ->map(fn($recs) => $recs->first())
                        ->sortByDesc('modal_price')
                        ->values();
                }
            }
        }

        $activePriceItem = null;
        if ($selectedMarketPrices->isNotEmpty()) {
            if ($varietyId) {
                $activePriceItem = $selectedMarketPrices->first(function ($item) use ($varietyId, $gradeParam) {
                    $matched = false;
                    if (is_numeric($varietyId)) {
                        $matched = ($item->variety_id == $varietyId);
                    } else {
                        $vName = $item->variety?->name ?? '';
                        $cleanVarietyQuery = trim(preg_replace('/\[.*?\]/', '', (string) $varietyId));
                        $matched = ($item->variety_id == $varietyId)
                            || (!empty($vName) && strcasecmp($vName, $cleanVarietyQuery) === 0)
                            || (!empty($vName) && stripos($varietyId, $vName) !== false);
                    }
                    if (!$matched) {
                        return false;
                    }
                    if ($gradeParam !== '' && $item->grade) {
                        return strcasecmp($item->grade, $gradeParam) === 0;
                    }
                    return true;
                });
            }
            if (!$activePriceItem) {
                $activePriceItem = $selectedMarketPrices->first();
            }
        } else {
            $activePriceItem = $mandiPrices->first();
        }

        // Calculate authentic Day-over-Day price change against previous trading session for this specific variety
        $dailyPriceChange = null;
        $dailyPriceChangePercent = 0.0;
        $dailyPriceChangeTrend = null;

        if ($activePriceItem) {
            $previousPriceRecord = (clone $basePricesQuery)
                ->where('market_id', $activePriceItem->market_id)
                ->when($activePriceItem->variety_id, fn($q) => $q->where('variety_id', $activePriceItem->variety_id))
                ->when($activePriceItem->grade, fn($q) => $q->where('grade', $activePriceItem->grade))
                ->where('price_date', '<', $activePriceItem->price_date)
                ->orderBy('price_date', 'desc')
                ->first();

            if (!$previousPriceRecord && $activePriceItem->variety_id) {
                $previousPriceRecord = (clone $basePricesQuery)
                    ->where('market_id', $activePriceItem->market_id)
                    ->where('variety_id', $activePriceItem->variety_id)
                    ->where('price_date', '<', $activePriceItem->price_date)
                    ->orderBy('price_date', 'desc')
                    ->first();
            }

            if (!$previousPriceRecord) {
                $previousPriceRecord = (clone $basePricesQuery)
                    ->where('market_id', $activePriceItem->market_id)
                    ->where('price_date', '<', $activePriceItem->price_date)
                    ->orderBy('price_date', 'desc')
                    ->first();
            }

            if ($previousPriceRecord && $previousPriceRecord->modal_price > 0) {
                $dailyPriceChange = (float) ($activePriceItem->modal_price - $previousPriceRecord->modal_price);
                $dailyPriceChangePercent = round(($dailyPriceChange / (float) $previousPriceRecord->modal_price) * 100, 1);
                $dailyPriceChangeTrend = ($dailyPriceChange > 0) ? 'rise' : (($dailyPriceChange < 0) ? 'drop' : 'stable');
            }
        }

        // Calculate weekly market trade summary range across active varieties
        $weeklyMinTradedPrice = $selectedMarketPrices->isNotEmpty()
            ? (float) ($selectedMarketPrices->min('min_price') ?: $selectedMarketPrices->min('modal_price'))
            : 0;
        $weeklyMaxTradedPrice = $selectedMarketPrices->isNotEmpty()
            ? (float) ($selectedMarketPrices->max('max_price') ?: $selectedMarketPrices->max('modal_price'))
            : 0;

        // Fetch Historical Analytics (Trends, Seasonality, Volatility)
        $rangeParam = $request->query('range', '30d');
        $rangeDays = match ($rangeParam) {
            '7d' => 7,
            '15d' => 15,
            '90d' => 90,
            '365d', '1y' => 365,
            default => 30,
        };

        $activeVarietyId = $activePriceItem?->variety_id;
        $activeModalPrice = (float) ($activePriceItem?->modal_price ?? ($stats['avg_modal'] ?? 0));

        $dailyTrends = $this->analyticsService->getDailyTrends($crop->id, $selectedMarket?->id, $rangeDays, $activeVarietyId);
        $seasonalAnalysis = $this->analyticsService->getSeasonalAnalysis($crop->id, $selectedMarket?->id, $activeVarietyId);
        $statisticalSummary = $this->analyticsService->getStatisticalSummary($crop->id, $selectedMarket?->id, $rangeDays, $activeVarietyId);
        $forecast = $this->forecastingService->getForecastsForCrop($crop->id, $selectedMarket?->id, $activeVarietyId, $activeModalPrice);

        // Agricultural CMS integrations for this crop
        $cropVideos = CuratedVideo::active()
            ->where(function ($q) use ($crop) {
                $q->where('crop_id', $crop->id)
                  ->orWhereNull('crop_id');
            })
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->take(3)
            ->get();

        $cropArticles = Article::published()
            ->where(function ($q) use ($crop) {
                $q->where('crop_id', $crop->id)
                  ->orWhereNull('crop_id');
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        $cropSchemes = Scheme::active()
            ->orderBy('display_order')
            ->take(3)
            ->get();

        $displayModal = $activePriceItem ? (float) $activePriceItem->modal_price : ($stats['avg_modal'] > 0 ? (float) $stats['avg_modal'] : 0);
        $rawMktName = $selectedMarket ? $selectedMarket->name : ($activePriceItem ? $activePriceItem->market->name : null);
        $rawMktKn = $selectedMarket ? $selectedMarket->name_kn : ($activePriceItem ? $activePriceItem->market->name_kn : null);
        $displayMarketName = $rawMktName 
            ? ($activeLocale === 'en' ? $rawMktName : ($rawMktKn ?? $rawMktName)) 
            : ($activeLocale === 'en' ? 'State Average (Karnataka)' : 'ಕರ್ನಾಟಕ ಸರಾಸರಿ');
        $displayMarketDistrict = $selectedMarket?->district?->name ?? ($activePriceItem?->market?->district?->name ?? 'Karnataka');
        $isStandardQuintal = ($crop->standard_unit === 'Quintal' || !$crop->standard_unit);
        $perKgPrice = ($isStandardQuintal && $displayModal > 0) ? round($displayModal / 100, 1) : null;

        $gradesList = $selectedMarketPrices->map(function ($smp) use ($activeLocale, $activePriceItem, $crop) {
            $isVarSelected = ($activePriceItem && $activePriceItem->variety_id == $smp->variety_id && (!$smp->grade || $activePriceItem->grade == $smp->grade));
            return [
                'variety_id' => $smp->variety_id,
                'grade' => $smp->grade,
                'label' => $smp->getDisplayVarietyGrade($activeLocale),
                'modal_price' => (float) $smp->modal_price,
                'modal_formatted' => '₹' . number_format($smp->modal_price, 0),
                'is_selected' => $isVarSelected,
                'url' => route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $smp->variety_id, 'grade' => $smp->grade, 'market' => $smp->market?->name])),
            ];
        })->values();

        $whereToSellUrl = route('farmer.decision.where-to-sell', array_filter([
            'crop' => $crop->slug,
            'variety_id' => $activeVarietyId ?? ($activePriceItem?->variety_id ?? null),
            'market_id' => $selectedMarket?->id,
            'district_id' => $selectedMarket?->district_id ?? ($userDistrict?->id ?? null),
            'from_crop' => 1,
        ]));

        $resetUrl = route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $varietyId]));

        $priceData = [
            'modal_price' => $displayModal,
            'modal_formatted' => $displayModal > 0 ? '₹' . number_format($displayModal, 0) : '—',
            'per_kg_formatted' => $perKgPrice ? ('≈ ₹' . $perKgPrice . '/kg') : null,
            'unit_label' => $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : ($crop->standard_unit ?? 'Quintal'),
            'price_date' => $activePriceItem?->price_date ?? $latestDate,
            'date_formatted' => ($activePriceItem?->price_date || $latestDate) 
                ? Carbon::parse($activePriceItem?->price_date ?? $latestDate)->format('d M Y') 
                : '—',
            'as_of_text' => ($activePriceItem?->price_date || $latestDate)
                ? (Carbon::parse($activePriceItem?->price_date ?? $latestDate)->isToday()
                    ? ($activeLocale === 'en' ? 'as of now' : 'ಇಂದಿನವರೆಗೆ')
                    : ($activeLocale === 'en' ? 'as of ' . Carbon::parse($activePriceItem?->price_date ?? $latestDate)->format('d M') : 'ದಿನಾಂಕ: ' . Carbon::parse($activePriceItem?->price_date ?? $latestDate)->format('d M')))
                : ($activeLocale === 'en' ? 'as of now' : 'ಇಂದಿನವರೆಗೆ'),
            'min_price' => (float) ($activePriceItem?->min_price ?? 0),
            'max_price' => (float) ($activePriceItem?->max_price ?? 0),
            'spread_formatted' => ($activePriceItem && $activePriceItem->min_price > 0 && $activePriceItem->max_price > 0 && $activePriceItem->price_spread > 0)
                ? ('₹' . number_format($activePriceItem->min_price, 0) . ' – ₹' . number_format($activePriceItem->max_price, 0))
                : null,
            'daily_change' => $dailyPriceChange,
            'daily_change_percent' => $dailyPriceChangePercent,
            'daily_trend' => $dailyPriceChangeTrend,
            'display_market_name' => $displayMarketName,
            'district_name' => $displayMarketDistrict,
            'distance_km' => $nearestDistanceKm,
            'is_nearest' => (bool) $isSelectedActualNearest,
        ];

        $firstHorizon = !empty($forecast['horizons']) ? ($forecast['horizons'][1] ?? $forecast['horizons'][0]) : null;
        $forecastDir = $firstHorizon['direction'] ?? 'neutral';

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Market-Switch')) {
            return response()->json([
                'success' => true,
                'market' => [
                    'id' => $selectedMarket?->id,
                    'name' => $selectedMarket?->name,
                    'name_kn' => $selectedMarket?->name_kn,
                    'display_name' => $displayMarketName,
                    'distance_km' => $nearestDistanceKm,
                    'district_name' => $displayMarketDistrict,
                    'is_nearest' => (bool) $isSelectedActualNearest,
                ],
                'price_item' => $priceData,
                'grades' => $gradesList,
                'active_variety_id' => $activeVarietyId ?? ($activePriceItem?->variety_id ?? null),
                'active_grade' => $activePriceItem?->grade ?? null,
                'where_to_sell_url' => $whereToSellUrl,
                'reset_url' => $resetUrl,
                'advisory_html' => view('farmer.crops.partials.advisory_banner', compact('forecast', 'forecastDir', 'activeLocale'))->render(),
                'forecast_html' => view('farmer.crops.partials.forecast_card', compact('forecast', 'crop', 'activeLocale', 'activePriceItem', 'boardMeta'))->render(),
                'seasonal_html' => view('farmer.crops.partials.seasonal_card', compact('seasonalAnalysis', 'crop', 'activeLocale'))->render(),
            ]);
        }

        return view('farmer.crops.show', compact(
            'crop',
            'mandiPrices',
            'mandiGroups',
            'nearestTwoGroups',
            'allOtherMandiGroups',
            'userDistrict',
            'stats',
            'varietyId',
            'latestDate',
            'availableMarkets',
            'actualNearestMarket',
            'isSelectedActualNearest',
            'marketRadiusKm',
            'defaultMarketSort',
            'allowUserSortToggle',
            'enableSmartBadges',
            'marketParam',
            'selectedMarket',
            'isNearestFallback',
            'nearestDistanceKm',
            'selectedMarketPrices',
            'activePriceItem',
            'dailyPriceChange',
            'dailyPriceChangePercent',
            'dailyPriceChangeTrend',
            'rangeParam',
            'rangeDays',
            'dailyTrends',
            'seasonalAnalysis',
            'statisticalSummary',
            'forecast',
            'cropVideos',
            'cropArticles',
            'cropSchemes',
            'boardMeta',
            'availableVarieties',
            'gradeParam',
            'weeklyMinTradedPrice',
            'weeklyMaxTradedPrice',
            'activeModalPrice',
            'activeVarietyId',
            'activeLocale',
            'stalenessThresholdDays',
            'cutoffDate',
            'displayModal',
            'displayMarketName',
            'displayMarketDistrict',
            'perKgPrice',
            'priceData',
            'gradesList',
            'whereToSellUrl',
            'resetUrl',
            'isMarketParamMatched'
        ));
    }

    /**
     * AJAX endpoint to return historical price trend data, metrics, and insight without page reload.
     */
    public function trendAjax(string $cropIdentifier, Request $request): \Illuminate\Http\JsonResponse
    {
        $crop = Crop::where('slug', $cropIdentifier)
            ->orWhere('id', is_numeric($cropIdentifier) ? (int)$cropIdentifier : 0)
            ->firstOrFail();

        $rangeParam = $request->query('range', '30d');
        $rangeDays = match ($rangeParam) {
            '7d' => 7,
            '15d' => 15,
            '90d' => 90,
            '365d', '1y' => 365,
            default => 30,
        };

        $marketId = $request->filled('market_id') ? (int) $request->query('market_id') : null;
        if (!$marketId && $request->filled('market')) {
            $mktName = trim((string) $request->query('market'));
            $market = Market::karnataka()->where(function ($mq) use ($mktName) {
                $mq->where('name', $mktName)
                   ->orWhere('name_kn', $mktName)
                   ->orWhere('code', $mktName);
            })->first();
            $marketId = $market?->id;
        }

        $activeLocale = $request->query('lang') ?: (session('locale') ?: ($request->cookie('locale') ?: app()->getLocale()));
        $varietyId = $request->filled('variety') ? (int) $request->query('variety') : null;

        $dailyTrends = $this->analyticsService->getDailyTrends($crop->id, $marketId, $rangeDays, $varietyId);
        $statisticalSummary = $this->analyticsService->getStatisticalSummary($crop->id, $marketId, $rangeDays, $varietyId);

        $firstPrice = (float) ($statisticalSummary['first_price'] ?? 0);
        $lastPrice = (float) ($statisticalSummary['last_price'] ?? 0);
        $avgPrice = (float) ($statisticalSummary['avg_price'] ?? 0);
        $minPrice = (float) ($statisticalSummary['min_price'] ?? 0);
        $maxPrice = (float) ($statisticalSummary['max_price'] ?? 0);
        $priceSpread = max(0, $maxPrice - $minPrice);
        $changePct = (float) ($statisticalSummary['price_change_percent'] ?? 0);
        $trendDir = $statisticalSummary['trend_direction'] ?? 'stable';
        $volPercent = $statisticalSummary['volatility_percent'] ?? 0;
        $volColor = $statisticalSummary['volatility_color'] ?? 'emerald';
        $volRating = $statisticalSummary['volatility_rating'] ?? 'ಕಡಿಮೆ (Low)';
        $displayVolRating = $activeLocale === 'en'
            ? ($statisticalSummary['volatility_rating_en'] ?? 'Stable / Low Volatility')
            : ($statisticalSummary['volatility_rating_kn'] ?? $volRating);

        $sumArrivals = !empty($dailyTrends['arrivals']) ? array_sum(array_filter($dailyTrends['arrivals'], fn($v) => is_numeric($v) && $v > 0)) : 0;
        $unit = strtolower($crop->standard_unit ?? 'quintal');
        $unitKn = 'ಕ್ವಿಂಟಾಲ್';

        // Precompute localized insight text
        $insightText = '';
        if ($activeLocale === 'en') {
            if ($trendDir === 'up') {
                $insightText = "Over the last {$rangeDays} days, modal rates rose from <strong>₹" . number_format($firstPrice) . "</strong> to <strong>₹" . number_format($lastPrice) . "</strong> <strong>(+{$changePct}%)</strong>. Market demand remains strong with favorable selling momentum.";
            } elseif ($trendDir === 'down') {
                $insightText = "Over the last {$rangeDays} days, modal rates softened from <strong>₹" . number_format($firstPrice) . "</strong> to <strong>₹" . number_format($lastPrice) . "</strong> <strong>(-" . abs($changePct) . "%)</strong>. Local arrivals may be elevated; check the price forecast before committing volume.";
            } else {
                $insightText = "Over the last {$rangeDays} days, prices held steady with an average of <strong>₹" . number_format($avgPrice) . "/{$unit}</strong>. Trading spread between high and low is <strong>₹" . number_format($priceSpread) . "</strong>.";
            }
        } else {
            if ($trendDir === 'up') {
                $insightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹" . number_format($firstPrice) . "</strong> ರಿಂದ <strong>₹" . number_format($lastPrice) . "</strong> ಕ್ಕೆ <strong>(+{$changePct}%) ಏರಿಕೆಯಾಗಿದೆ</strong>. ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಉತ್ತಮವಾಗಿದ್ದು ಮಾರಾಟಕ್ಕೆ ಅನುಕೂಲಕರ ಪ್ರವೃತ್ತಿಯಿದೆ.";
            } elseif ($trendDir === 'down') {
                $insightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹" . number_format($firstPrice) . "</strong> ರಿಂದ <strong>₹" . number_format($lastPrice) . "</strong> ಕ್ಕೆ <strong>(-" . abs($changePct) . "%) ಇಳಿಕೆಯಾಗಿದೆ</strong>. ಸ್ಥಳೀಯ ಆವಕ ಹೆಚ್ಚಾಗಿರಬಹುದು, ಬೆಲೆ ಮುನ್ಸೂಚನೆ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧರಿಸಿ.";
            } else {
                $insightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ದರವು ಸರಾಸರಿ <strong>₹" . number_format($avgPrice) . "/{$unitKn}</strong> ನೊಂದಿಗೆ ಸ್ಥಿರವಾಗಿದೆ. ಗರಿಷ್ಠ ಮತ್ತು ಕನಿಷ್ಠ ದರದ ಅಂತರ <strong>₹" . number_format($priceSpread) . "</strong> ಆಗಿದೆ.";
            }
        }

        return response()->json([
            'success' => true,
            'range' => $rangeParam,
            'range_days' => $rangeDays,
            'chart_data' => [
                'labels' => $dailyTrends['labels'] ?? [],
                'modalPrices' => $dailyTrends['modal_prices'] ?? [],
                'minPrices' => $dailyTrends['min_prices'] ?? [],
                'maxPrices' => $dailyTrends['max_prices'] ?? [],
                'arrivals' => $dailyTrends['arrivals'] ?? [],
                'has_data' => !empty($dailyTrends['has_data']),
                'locale' => $activeLocale,
            ],
            'metrics' => [
                'max_price' => $maxPrice,
                'min_price' => $minPrice,
                'avg_price' => $avgPrice,
                'price_spread' => $priceSpread,
                'diff_high_avg' => max(0, $maxPrice - $avgPrice),
                'diff_avg_low' => max(0, $avgPrice - $minPrice),
                'observations_count' => $statisticalSummary['observations_count'] ?? count($dailyTrends['labels'] ?? []),
                'trend_dir' => $trendDir,
                'change_pct' => $changePct,
                'abs_change_pct' => abs($changePct),
                'vol_rating' => $displayVolRating,
                'vol_percent' => $volPercent,
                'vol_color' => $volColor,
                'sum_arrivals' => $sumArrivals,
                'insight_text' => $insightText,
            ],
        ]);
    }
}
