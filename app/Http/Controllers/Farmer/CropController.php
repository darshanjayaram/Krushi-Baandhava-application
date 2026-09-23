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
    public function show(string $slug, Request $request): View
    {
        $crop = Crop::with(['category', 'varieties' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $varietyId = $request->query('variety');
        $marketParam = trim((string) $request->query('market', ''));

        // Resolve latest date specifically for this crop in Karnataka
        $latestDate = MarketPrice::karnataka()
            ->where('crop_id', $crop->id)
            ->max('price_date') ?? Carbon::today()->toDateString();

        // 1. Fetch all distinct Karnataka mandis reporting this crop on the date (for dropdown filter & quick chips)
        $availableMarkets = MarketPrice::karnataka()
            ->with(['market.district'])
            ->where('crop_id', $crop->id)
            ->where('price_date', $latestDate)
            ->get()
            ->map(function ($mp) {
                $m = $mp->market;
                if ($m) {
                    $m->today_modal_price = (float) $mp->modal_price;
                }
                return $m;
            })
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        // 2. Base query for Karnataka mandi prices on this date
        $mandiPricesQuery = MarketPrice::karnataka()
            ->with(['variety', 'market.district', 'dataSource'])
            ->where('crop_id', $crop->id)
            ->where('price_date', $latestDate);

        if ($varietyId) {
            $mandiPricesQuery->where('variety_id', $varietyId);
        }

        // Filter by specific Karnataka mandi if requested (e.g. ?market=BINNY%20MILL%20%28F%26V%29 or code)
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

        // State-level analytics summary for this commodity across all Karnataka mandis
        $allKarnatakaPrices = MarketPrice::karnataka()
            ->with(['market'])
            ->where('crop_id', $crop->id)
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

        // Matched selected market if filtered
        $selectedMarket = null;
        if ($marketParam !== '') {
            $selectedMarket = $availableMarkets->first(function ($m) use ($marketParam) {
                return $m->name === $marketParam || $m->name_kn === $marketParam || $m->code === $marketParam;
            });
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
            'rangeParam',
            'rangeDays',
            'dailyTrends',
            'seasonalAnalysis',
            'statisticalSummary',
            'forecast',
            'cropVideos',
            'cropArticles',
            'cropSchemes'
        ));
    }
}
