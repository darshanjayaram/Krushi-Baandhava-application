<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CuratedVideo;
use App\Models\Article;
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

        $crops = $query->paginate(18)->withQueryString();

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
    public function show(string $cropIdentifier, Request $request): View
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
        $marketParam = trim((string) $request->query('market', ''));

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

        // Base price query builder with strict board / APMC authority isolation
        $basePricesQuery = MarketPrice::karnataka()->where('crop_id', $crop->id);
        if ($boardMeta) {
            $boardSourceCode = $boardMeta['type'];
            $basePricesQuery->whereHas('dataSource', function ($dq) use ($boardSourceCode) {
                $dq->where('code', $boardSourceCode);
            });
        }

        // Resolve latest date specifically for this crop in Karnataka
        $latestDate = (clone $basePricesQuery)->max('price_date') ?? Carbon::today()->toDateString();

        // Filter available varieties to only those that actually have recorded prices for this crop on $latestDate
        $pricedVarietyIds = (clone $basePricesQuery)
            ->where('price_date', $latestDate)
            ->whereNotNull('variety_id')
            ->pluck('variety_id')
            ->unique()
            ->toArray();

        $availableVarieties = $crop->varieties
            ->whereIn('id', $pricedVarietyIds)
            ->values();

        // Resolve reference coordinates to find nearby markets
        $refLat = null;
        $refLon = null;
        $selectedDistrictId = $request->cookie('selected_district_id') ?? session('selected_district_id');
        $userDistrict = null;
        if ($selectedDistrictId) {
            $userDistrict = \App\Models\District::find($selectedDistrictId);
            if ($userDistrict && $userDistrict->latitude && $userDistrict->longitude) {
                $refLat = (float) $userDistrict->latitude;
                $refLon = (float) $userDistrict->longitude;
            }
        }

        if ($refLat === null || $refLon === null) {
            $refLat = 13.9299;
            $refLon = 75.5681;
        }

        // 1. Fetch all distinct Karnataka mandis/centres reporting this crop on the date, sorted by nearby proximity
        $availableMarkets = (clone $basePricesQuery)
            ->with(['market.district'])
            ->where('price_date', $latestDate)
            ->get()
            ->map(function ($mp) use ($refLat, $refLon, $userDistrict) {
                $m = $mp->market;
                if ($m) {
                    $m->today_modal_price = (float) $mp->modal_price;
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
            ->unique('id')
            ->sort(function ($a, $b) {
                if ($a->is_same_district !== $b->is_same_district) {
                    return $b->is_same_district <=> $a->is_same_district;
                }
                if ($a->distance_km !== $b->distance_km) {
                    return $a->distance_km <=> $b->distance_km;
                }
                return strcmp($a->name, $b->name);
            })
            ->values();

        // 2. Base query for Karnataka mandi/board prices on this date
        $mandiPricesQuery = (clone $basePricesQuery)
            ->with(['variety', 'market.district', 'dataSource'])
            ->where('price_date', $latestDate);

        if ($varietyId) {
            $mandiPricesQuery->where('variety_id', $varietyId);
        }

        // Filter by specific Karnataka mandi/centre if requested
        if ($marketParam !== '') {
            $mandiPricesQuery->whereHas('market', function ($mq) use ($marketParam) {
                $mq->karnataka()->where(function ($sub) use ($marketParam) {
                    $sub->where('name', $marketParam)
                        ->orWhere('name_kn', $marketParam)
                        ->orWhere('code', $marketParam)
                        ->orWhere('name', 'like', "%{$marketParam}%");
                });
            });
        }

        $mandiPrices = $mandiPricesQuery
            ->orderBy('modal_price', 'desc')
            ->get();

        // State-level analytics summary for this commodity across all Karnataka mandis/centres
        $allKarnatakaPrices = (clone $basePricesQuery)
            ->with(['market'])
            ->where('price_date', $latestDate)
            ->get();

        $stats = [
            'highest_modal' => $allKarnatakaPrices->max('modal_price') ?? 0,
            'highest_market' => $allKarnatakaPrices->firstWhere('modal_price', $allKarnatakaPrices->max('modal_price'))?->market->name ?? '—',
            'lowest_modal' => $allKarnatakaPrices->min('modal_price') ?? 0,
            'lowest_market' => $allKarnatakaPrices->firstWhere('modal_price', $allKarnatakaPrices->min('modal_price'))?->market->name ?? '—',
            'avg_modal' => $allKarnatakaPrices->avg('modal_price') ?? 0,
            'total_mandis' => $allKarnatakaPrices->pluck('market_id')->unique()->count(),
            'total_arrivals' => $allKarnatakaPrices->sum('arrival_quantity') ?? 0,
            'date_formatted' => Carbon::parse($latestDate)->format('d M Y'),
        ];

        // Matched selected market if filtered or auto-resolve nearest
        $selectedMarket = null;
        if ($marketParam !== '') {
            $selectedMarket = $availableMarkets->first(function ($m) use ($marketParam) {
                return $m->name === $marketParam || $m->name_kn === $marketParam || $m->code === $marketParam;
            });
        }

        if (!$selectedMarket && $availableMarkets->isNotEmpty()) {
            // Priority 1: Market in user's home district
            // Priority 2: Nearest market by proximity
            $selectedMarket = $availableMarkets->firstWhere('is_same_district', true) ?? $availableMarkets->first();
        }

        // Check if selected market is outside user's home district and extract distance
        $isNearestFallback = false;
        $nearestDistanceKm = null;
        if ($selectedMarket) {
            $isNearestFallback = (!$userDistrict || $selectedMarket->district_id !== $userDistrict->id);
            $nearestDistanceKm = (isset($selectedMarket->distance_km) && $selectedMarket->distance_km < 1000)
                ? $selectedMarket->distance_km
                : null;
        }

        // Fetch varieties specifically trading at this selected market
        $selectedMarketPrices = collect();
        if ($selectedMarket) {
            $selectedMarketPrices = (clone $basePricesQuery)
                ->with(['variety'])
                ->where('price_date', $latestDate)
                ->where('market_id', $selectedMarket->id)
                ->orderBy('modal_price', 'desc')
                ->get();
        }

        // Fetch Historical Analytics (Trends, Seasonality, Volatility)
        $rangeParam = $request->query('range', '30d');
        $rangeDays = match ($rangeParam) {
            '7d' => 7,
            '15d' => 15,
            '90d' => 90,
            '365d', '1y' => 365,
            default => 30,
        };

        $dailyTrends = $this->analyticsService->getDailyTrends($crop->id, $selectedMarket?->id, $rangeDays);
        $seasonalAnalysis = $this->analyticsService->getSeasonalAnalysis($crop->id, $selectedMarket?->id);
        $statisticalSummary = $this->analyticsService->getStatisticalSummary($crop->id, $selectedMarket?->id, $rangeDays);
        $forecast = $this->forecastingService->getForecastsForCrop($crop->id, $selectedMarket?->id);

        // Agricultural CMS integrations for this crop
        $cropVideos = CuratedVideo::active()
            ->where(function ($q) use ($crop) {
                $q->where('crop_id', $crop->id)
                  ->orWhereNull('crop_id');
            })
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

        return view('farmer.crops.show', compact(
            'crop',
            'mandiPrices',
            'stats',
            'varietyId',
            'latestDate',
            'availableMarkets',
            'marketParam',
            'selectedMarket',
            'isNearestFallback',
            'nearestDistanceKm',
            'selectedMarketPrices',
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
            'availableVarieties'
        ));
    }
}
