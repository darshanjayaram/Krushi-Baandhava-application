<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\District;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the mobile-first farmer home screen with live APMC rates,
     * category filtering, district context, and WhatsApp sharing.
     */
    public function index(Request $request): View
    {
        $districtId = $request->query('district');
        $selectedCategory = $request->query('category', 'all');
        $search = trim((string) $request->query('search', ''));
        $viewScope = $request->query('scope', 'district'); // 'district' or 'all'

        // 1. Resolve Active District (Strictly Karnataka)
        $allDistricts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->where('is_active', true)
            ->with(['markets' => fn ($mq) => $mq->karnataka()])
            ->orderBy('name')
            ->get();

        $activeDistrict = null;
        if ($districtId) {
            $activeDistrict = $allDistricts->firstWhere('id', $districtId);
        }

        if (!$activeDistrict) {
            $activeDistrict = $allDistricts->firstWhere('name', 'Shivamogga')
                ?? $allDistricts->first();
        }

        // 2. Resolve Latest Date in DB (Strictly Karnataka)
        $latestPriceDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();

        // 3. Query Live Market Prices (Strictly Karnataka)
        $pricesQuery = MarketPrice::karnataka()
            ->with(['crop.category', 'variety', 'market.district', 'dataSource'])
            ->where('price_date', $latestPriceDate);

        // Apply District Scope if requested and available
        if ($viewScope === 'district' && $activeDistrict) {
            $districtPricesCount = (clone $pricesQuery)
                ->whereHas('market', fn ($m) => $m->where('district_id', $activeDistrict->id))
                ->count();

            if ($districtPricesCount > 0) {
                $pricesQuery->whereHas('market', fn ($m) => $m->where('district_id', $activeDistrict->id));
            } else {
                // District has no direct reports today; fall back to state view with notice
                $viewScope = 'state_fallback';
            }
        }

        // Category Filter
        if ($selectedCategory && $selectedCategory !== 'all') {
            $pricesQuery->whereHas('crop.category', fn ($c) => $c->where('slug', $selectedCategory));
        }

        // Search Filter
        if ($search !== '') {
            $pricesQuery->where(function ($q) use ($search) {
                $q->whereHas('crop', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('name_kn', 'like', "%{$search}%");
                })->orWhereHas('market', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                       ->orWhere('name_kn', 'like', "%{$search}%");
                });
            });
        }

        $latestPrices = $pricesQuery
            ->orderBy('modal_price', 'desc')
            ->paginate(12)
            ->withQueryString();

        // 4. Categories with Active Crop Counts
        $categories = CropCategory::where('is_active', true)
            ->withCount(['crops' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('display_order')
            ->get();

        // 5. Top Market Highlights / Price Movers (Featured Karnataka Commodities)
        $topMovers = MarketPrice::karnataka()
            ->with(['crop', 'variety', 'market.district'])
            ->where('price_date', $latestPriceDate)
            ->whereHas('crop', fn ($c) => $c->where('is_major', true))
            ->orderBy('modal_price', 'desc')
            ->take(6)
            ->get()
            ->unique('crop_id');

        // 6. Major Crops Catalog Grid
        $majorCrops = Crop::with(['category', 'varieties' => fn ($q) => $q->where('is_active', true)])
            ->where('is_major', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // 7. Overall Summary Stats (Strictly Karnataka)
        $stats = [
            'total_mandis_reporting' => MarketPrice::karnataka()->where('price_date', $latestPriceDate)->distinct('market_id')->count('market_id'),
            'total_commodities' => MarketPrice::karnataka()->where('price_date', $latestPriceDate)->distinct('crop_id')->count('crop_id'),
            'total_arrivals' => MarketPrice::karnataka()->where('price_date', $latestPriceDate)->sum('arrival_quantity') ?? 0,
            'latest_date_formatted' => Carbon::parse($latestPriceDate)->format('d M Y'),
        ];

        // 8. Today's Weather & Advisory for Active District
        $todayWeather = null;
        if ($activeDistrict) {
            $todayWeather = \App\Models\WeatherForecast::forDistrict($activeDistrict->id)
                ->where('forecast_date', '>=', Carbon::today()->toDateString())
                ->orderBy('forecast_date', 'asc')
                ->first();
        }

        return view('farmer.home', compact(
            'activeDistrict',
            'allDistricts',
            'latestPrices',
            'categories',
            'selectedCategory',
            'search',
            'viewScope',
            'topMovers',
            'majorCrops',
            'stats',
            'latestPriceDate',
            'todayWeather'
        ));
    }

    /**
     * Show the dedicated PWA offline fallback screen when network connection is unavailable.
     */
    public function offline(): View
    {
        return view('farmer.offline');
    }
}

