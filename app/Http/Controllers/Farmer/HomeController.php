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
        $districtId = $request->query('district') ?? $request->cookie('selected_district_id') ?? session('selected_district_id');
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

        if ($activeDistrict) {
            cookie()->queue('selected_district_id', $activeDistrict->id, 525600);
            session(['selected_district_id' => $activeDistrict->id]);
        }

        // 2. Resolve Latest Date in DB (Strictly Karnataka)
        $latestPriceDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();

        // 3. Resolve Crops for the Homepage Directory (Negilu Krishi Architecture)
        $cropsQuery = Crop::with(['category', 'varieties' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->where('is_major', true);

        // Category Filter
        if ($selectedCategory && $selectedCategory !== 'all') {
            $cropsQuery->whereHas('category', fn ($c) => $c->where('slug', $selectedCategory));
        }

        // Search Filter
        if ($search !== '') {
            $cropsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_kn', 'like', "%{$search}%")
                  ->orWhere('scientific_name', 'like', "%{$search}%");
            });
        }

        $allCrops = $cropsQuery->orderBy('name')->get();

        // For each crop, resolve the best price for the farmer's location on latestDate:
        // Priority 1: Direct report from active district on latestPriceDate (Reliable)
        // Priority 2: Closest market in Karnataka or state average on latestPriceDate (Benchmark)
        // Priority 3: Most recent historical price in DB
        $curatedPrices = collect();
        foreach ($allCrops as $crop) {
            $price = null;
            $isLocal = false;

            if ($viewScope !== 'all' && $activeDistrict) {
                $price = MarketPrice::karnataka()
                    ->with(['crop.category', 'variety', 'market.district', 'dataSource'])
                    ->where('crop_id', $crop->id)
                    ->where('price_date', $latestPriceDate)
                    ->whereHas('market', fn ($m) => $m->where('district_id', $activeDistrict->id))
                    ->orderBy('modal_price', 'desc')
                    ->first();

                if ($price) {
                    $isLocal = true;
                }
            }

            if (!$price) {
                $price = MarketPrice::karnataka()
                    ->with(['crop.category', 'variety', 'market.district', 'dataSource'])
                    ->where('crop_id', $crop->id)
                    ->where('price_date', $latestPriceDate)
                    ->orderBy('modal_price', 'desc')
                    ->first();
                $isLocal = false;
            }

            if (!$price) {
                $price = MarketPrice::karnataka()
                    ->with(['crop.category', 'variety', 'market.district', 'dataSource'])
                    ->where('crop_id', $crop->id)
                    ->orderBy('price_date', 'desc')
                    ->orderBy('modal_price', 'desc')
                    ->first();
                $isLocal = false;
            }

            if ($price) {
                $price->is_local = $isLocal;
                $price->reliability_badge = $isLocal ? 'Reliable' : 'Benchmark';
                $curatedPrices->push($price);
            }
        }

        // Sort: local prices first, then highest modal price
        $sortedPrices = $curatedPrices->sort(function ($a, $b) {
            if ($a->is_local !== $b->is_local) {
                return $b->is_local <=> $a->is_local;
            }
            return $b->modal_price <=> $a->modal_price;
        })->values();

        // Paginate results so mobile pagination and hasPages() work seamlessly
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page') ?: 1;
        $perPage = 20;
        $latestPrices = new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedPrices->forPage($page, $perPage)->values(),
            $sortedPrices->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );
        $distinctCropPrices = $sortedPrices->forPage($page, $perPage)->values();

        // 4. Categories with Active Crop Counts
        $categories = CropCategory::where('is_active', true)
            ->withCount(['crops' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('display_order')
            ->get();

        // 5. Top Market Highlights / Price Movers (Featured Karnataka Commodities - 4 Spotlight Cards)
        $topMovers = MarketPrice::karnataka()
            ->with(['crop', 'variety', 'market.district'])
            ->where('price_date', $latestPriceDate)
            ->whereHas('crop', fn ($c) => $c->where('is_major', true))
            ->orderBy('modal_price', 'desc')
            ->get()
            ->unique('crop_id')
            ->take(4)
            ->values();

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
            'todayWeather',
            'distinctCropPrices'
        ));
    }

    /**
     * Show the dedicated PWA offline fallback screen when network connection is unavailable.
     */
    public function offline(): View
    {
        return view('farmer.offline');
    }

    /**
     * Persist user's selected or GPS detected district in session and cookie.
     */
    public function setLocation(Request $request): \Illuminate\Http\JsonResponse
    {
        $districtId = $request->input('district_id');
        $lat = $request->input('latitude');
        $lon = $request->input('longitude');

        $district = null;
        if ($districtId) {
            $district = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->find($districtId);
        } elseif ($lat && $lon) {
            $districts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();

            $minDist = PHP_FLOAT_MAX;
            foreach ($districts as $d) {
                $latFrom = deg2rad((float) $lat);
                $lonFrom = deg2rad((float) $lon);
                $latTo = deg2rad((float) $d->latitude);
                $lonTo = deg2rad((float) $d->longitude);
                $latDelta = $latTo - $latFrom;
                $lonDelta = $lonTo - $lonFrom;
                $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
                $dist = 6371 * $angle;

                if ($dist < $minDist) {
                    $minDist = $dist;
                    $district = $d;
                }
            }
        }

        if ($district) {
            session(['selected_district_id' => $district->id]);
            cookie()->queue('selected_district_id', $district->id, 525600);

            return response()->json([
                'success' => true,
                'district_id' => $district->id,
                'district_name' => $district->name,
                'district_name_kn' => $district->name_kn,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'District not found'], 404);
    }
}

