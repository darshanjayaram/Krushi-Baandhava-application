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
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
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
            $defaultDistrictName = \App\Models\SystemSetting::get('default_district', 'Shivamogga');
            $activeDistrict = $allDistricts->firstWhere('name', $defaultDistrictName)
                ?? $allDistricts->firstWhere('name', 'Shivamogga')
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
        // Priority 1: Direct report from active district strictly within freshness window (Reliable)
        // Priority 2: Closest market in Karnataka or state benchmark within freshness window (Benchmark)
        // Priority 3: Most recent historical price in DB (Fallback)
        $curatedPrices = collect();
        foreach ($allCrops as $crop) {
            $price = null;
            $isLocal = false;
            $cutoffDate = $crop->getFreshnessCutoffDate($latestPriceDate);

            if ($viewScope !== 'all' && $activeDistrict) {
                $price = MarketPrice::karnataka()
                    ->with(['crop.category', 'variety', 'market.district', 'dataSource'])
                    ->where('crop_id', $crop->id)
                    ->whereHas('market', fn ($m) => $m->where('district_id', $activeDistrict->id))
                    ->where('price_date', '>=', $cutoffDate)
                    ->orderBy('price_date', 'desc')
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
                    ->where('price_date', '>=', $cutoffDate)
                    ->orderBy('price_date', 'desc')
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
        $perPage = max(6, (int) \App\Models\SystemSetting::get('pagination_limit', 20));
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
        // Resolves the latest verified auction for each major commodity within its freshness window (7-30 days),
        // preventing provider sync downtimes (e.g. KRAMA outages) from collapsing spotlight cards.
        $spotlightCrops = Crop::where('is_active', true)
            ->where('is_major', true)
            ->get();

        $freshSpotlightPrices = collect();
        foreach ($spotlightCrops as $sCrop) {
            $cutoffDate = $sCrop->getFreshnessCutoffDate($latestPriceDate);

            // Priority 1: Market in farmer's active district within freshness window
            $sPrice = null;
            if ($activeDistrict) {
                $sPrice = MarketPrice::karnataka()
                    ->with(['crop', 'variety', 'market.district'])
                    ->where('crop_id', $sCrop->id)
                    ->whereHas('market', fn ($m) => $m->where('district_id', $activeDistrict->id))
                    ->where('price_date', '>=', $cutoffDate)
                    ->orderBy('price_date', 'desc')
                    ->orderBy('modal_price', 'desc')
                    ->first();
            }

            // Priority 2: State benchmark / closest Karnataka APMC market within freshness window
            if (!$sPrice) {
                $sPrice = MarketPrice::karnataka()
                    ->with(['crop', 'variety', 'market.district'])
                    ->where('crop_id', $sCrop->id)
                    ->where('price_date', '>=', $cutoffDate)
                    ->orderBy('price_date', 'desc')
                    ->orderBy('modal_price', 'desc')
                    ->first();
            }

            if ($sPrice) {
                $freshSpotlightPrices->push($sPrice);
            }
        }

        // Rank by highest modal price, ensuring distinct major commodities across the 4 cards
        $topMovers = $freshSpotlightPrices
            ->sortByDesc('modal_price')
            ->unique('crop_id')
            ->take(4)
            ->values();

        // 5a. Resolve authentic day-over-day price trend (Rise / Drop / Stable) against previous trading sessions
        // Each price compares against the immediate preceding session for its own crop and market.
        $attachDailyTrend = function ($item) {
            $prevPrice = MarketPrice::karnataka()
                ->where('crop_id', $item->crop_id)
                ->where('market_id', $item->market_id)
                ->where('price_date', '<', $item->price_date)
                ->orderBy('price_date', 'desc')
                ->first();

            // Fallback to any market prior price for this crop if this specific market has no prior history
            if (!$prevPrice) {
                $prevPrice = MarketPrice::karnataka()
                    ->where('crop_id', $item->crop_id)
                    ->where('price_date', '<', $item->price_date)
                    ->orderBy('price_date', 'desc')
                    ->first();
            }

            $prevModal = $prevPrice ? (float) $prevPrice->modal_price : null;
            if ($prevModal !== null && $prevModal > 0) {
                $item->daily_price_change = (float) ($item->modal_price - $prevModal);
                $item->daily_change_percent = round((($item->modal_price - $prevModal) / $prevModal) * 100, 1);
            } else {
                $item->daily_price_change = 0.0;
                $item->daily_change_percent = 0.0;
            }
            $item->daily_trend = ($item->daily_price_change > 0) ? 'rise' : (($item->daily_price_change < 0) ? 'drop' : 'stable');
        };

        $curatedPrices->each($attachDailyTrend);
        $topMovers->each($attachDailyTrend);

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

        // 8. Today's Weather & Advisory for Active District (On-Demand 15-Minute Dynamic TTL)
        $todayWeather = null;
        if ($activeDistrict) {
            $weatherService = app(\App\Services\Weather\WeatherSyncService::class);
            $ttlMinutes = $weatherService->getCacheTtlMinutes();

            $todayWeather = \App\Models\WeatherForecast::forDistrict($activeDistrict->id)
                ->where('forecast_date', '>=', Carbon::today()->toDateString())
                ->orderBy('forecast_date', 'asc')
                ->first();

            $isExpiredOrMissing = !$todayWeather 
                || !$todayWeather->fetched_at 
                || Carbon::parse($todayWeather->fetched_at)->lt(Carbon::now()->subMinutes($ttlMinutes));

            if ($isExpiredOrMissing && $activeDistrict->latitude && $activeDistrict->longitude) {
                try {
                    $weatherService->syncDistrict($activeDistrict, true);
                    $todayWeather = \App\Models\WeatherForecast::forDistrict($activeDistrict->id)
                        ->where('forecast_date', '>=', Carbon::today()->toDateString())
                        ->orderBy('forecast_date', 'asc')
                        ->first();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Weather on-demand sync failed for district {$activeDistrict->name}: {$e->getMessage()}");
                }
            }
        }

        $activeLocalArea = $request->cookie('selected_local_area') ?? session('selected_local_area');
        $activeLocalAreaKn = $request->cookie('selected_local_area_kn') ?? session('selected_local_area_kn');
        if ($activeLocalArea && $activeLocalAreaKn && preg_match('/bengaluru|bangalore|ಬೆಂಗಳೂರು/iu', $activeLocalAreaKn) && !preg_match('/bengaluru|bangalore/i', $activeLocalArea)) {
            $activeLocalAreaKn = $activeLocalArea;
        }

        if ($request->ajax() || $request->query('async') === '1' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $activeLocale = app()->getLocale();
            return response()->json([
                'ok' => true,
                'html' => view('farmer.partials.today_rates_content', compact(
                    'latestPrices',
                    'distinctCropPrices',
                    'activeLocale'
                ))->render(),
                'page' => $latestPrices->currentPage(),
                'last_page' => $latestPrices->lastPage(),
                'total' => $latestPrices->total(),
                'has_more' => $latestPrices->hasMorePages(),
            ]);
        }

        return view('farmer.home', compact(
            'activeDistrict',
            'activeLocalArea',
            'activeLocalAreaKn',
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
            'distinctCropPrices',
            'sortedPrices'
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
     * Reverse geocode GPS coordinates to identify hyperlocal town/suburb/village name
     * using OpenStreetMap Nominatim with bilingual Kannada/English support, concurrent pooling, and 24-hr caching.
     */
    public function reverseGeocode(Request $request): \Illuminate\Http\JsonResponse
    {
        $lat = $request->query('lat');
        $lon = $request->query('lon');

        if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
            return response()->json(['success' => false, 'message' => 'Invalid coordinates'], 400);
        }

        // Round to 3 decimal places (~1km radius) for high cache hit rate across local farmers
        $latRound = round((float) $lat, 3);
        $lonRound = round((float) $lon, 3);
        $cacheKey = "krushi_rev_geo_{$latRound}_{$lonRound}";

        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($latRound, $lonRound) {
            try {
                // Fetch English and Kannada details concurrently in parallel using Http::pool (zoom 14 for town/city level)
                $responses = \Illuminate\Support\Facades\Http::pool(fn ($pool) => [
                    $pool->withoutVerifying()
                        ->withHeaders(['User-Agent' => 'KrushiBaandhava/1.0 (https://krushibaandhava.in; contact@krushibaandhava.in)'])
                        ->timeout(3.0)
                        ->get('https://nominatim.openstreetmap.org/reverse', [
                            'format' => 'jsonv2',
                            'lat' => $latRound,
                            'lon' => $lonRound,
                            'zoom' => 14,
                            'accept-language' => 'en',
                        ]),
                    $pool->withoutVerifying()
                        ->withHeaders(['User-Agent' => 'KrushiBaandhava/1.0 (https://krushibaandhava.in; contact@krushibaandhava.in)'])
                        ->timeout(3.0)
                        ->get('https://nominatim.openstreetmap.org/reverse', [
                            'format' => 'jsonv2',
                            'lat' => $latRound,
                            'lon' => $lonRound,
                            'zoom' => 14,
                            'accept-language' => 'kn,en',
                        ]),
                ]);

                $resEn = $responses[0] instanceof \Illuminate\Http\Client\Response && $responses[0]->successful() ? $responses[0]->json() : null;
                $resKn = $responses[1] instanceof \Illuminate\Http\Client\Response && $responses[1]->successful() ? $responses[1]->json() : null;

                $addrEn = $resEn['address'] ?? [];
                $addrKn = $resKn['address'] ?? [];

                // Smart hierarchical extraction: city/town first (except metro Bengaluru where suburb/locality is preferred)
                $extractPlace = function (array $data, ?string $enPlace = null) {
                    $addr = $data['address'] ?? [];
                    $city = $addr['city'] ?? null;
                    $town = $addr['town'] ?? null;
                    $suburb = $addr['suburb'] ?? null;
                    $quarter = $addr['quarter'] ?? null;
                    $village = $addr['village'] ?? null;
                    $neighbourhood = $addr['neighbourhood'] ?? null;
                    $hamlet = $addr['hamlet'] ?? null;

                    // 1. Bengaluru / Bangalore Metro: locality/suburb/quarter is the primary identity (e.g. Kothanur, Yelahanka)
                    $isBengaluru = $city && preg_match('/bengaluru|bangalore|ಬೆಂಗಳೂರು/iu', $city);
                    if ($isBengaluru) {
                        $local = $quarter ?: ($suburb ?: ($neighbourhood ?: null));
                        if ($local) {
                            return $local;
                        }
                        // If specific English locality exists, retain that English locality instead of collapsing to broad "Bengaluru" / "ಬೆಂಗಳೂರು"
                        if ($enPlace && !preg_match('/bengaluru|bangalore|ಬೆಂಗಳೂರು/iu', $enPlace)) {
                            return $enPlace;
                        }
                        return $city;
                    }

                    // 2. Recognized market town (e.g. Maddur, Tiptur, Arsikere, Sirsi, Gokak, Bailhongal)
                    if ($town) {
                        return $town;
                    }

                    // 3. Suburb / Village / Quarter for rural or semi-urban areas
                    if ($suburb) return $suburb;
                    if ($village) return $village;
                    if ($quarter) return $quarter;

                    // 4. County/Taluk check (e.g. "Madduru taluk" -> "Maddur")
                    if (!empty($addr['county'])) {
                        $cleanCounty = trim(preg_replace('/\b(taluk|taluka|hobli)\b/iu', '', $addr['county']));
                        if (!empty($cleanCounty)) {
                            return $cleanCounty;
                        }
                    }

                    // 5. Recognized City across Karnataka (e.g. Shivamogga, Hubballi, Mysuru, Belagavi, Mangaluru, Davanagere)
                    if ($city) {
                        // If English has a specific locality/suburb/village, do not collapse to broad district/city
                        if ($enPlace && !preg_match('/' . preg_quote($city, '/') . '/iu', $enPlace) && !preg_match('/bengaluru|bangalore|ಬೆಂಗಳೂರು/iu', $enPlace)) {
                            return $enPlace;
                        }
                        return $city;
                    }

                    // 6. Hamlet / Neighbourhood / Root Name fallback
                    if ($hamlet) return $hamlet;
                    if ($neighbourhood) return $neighbourhood;

                    return !empty($data['name']) ? trim($data['name']) : ($enPlace ?: null);
                };

                $placeEn = $resEn ? $extractPlace($resEn) : null;
                $placeKn = $resKn ? $extractPlace($resKn, $placeEn) : null;

                if (!$placeEn && !$placeKn) {
                    return null;
                }

                // If placeKn is just the broad city/district while placeEn is a specific locality, keep placeEn
                if ($placeEn && $placeKn && preg_match('/bengaluru|bangalore|ಬೆಂಗಳೂರು/iu', $placeKn) && !preg_match('/bengaluru|bangalore/i', $placeEn)) {
                    $placeKn = $placeEn;
                }

                return [
                    'local_area' => $placeEn ?: $placeKn,
                    'local_area_kn' => $placeKn ?: $placeEn,
                    'postcode' => $addrEn['postcode'] ?? $addrKn['postcode'] ?? null,
                    'city' => $addrEn['city'] ?? $addrEn['town'] ?? null,
                    'state_district' => $addrEn['state_district'] ?? null,
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Reverse geocode failed for {$latRound}, {$lonRound}: " . $e->getMessage());
                return null;
            }
        });

        if ($result) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Location place name not found'
        ], 404);
    }

    /**
     * Persist user's selected or GPS detected district in session and cookie.
     */
    public function setLocation(Request $request): \Illuminate\Http\JsonResponse
    {
        $districtId = $request->input('district_id');
        $lat = $request->input('latitude');
        $lon = $request->input('longitude');
        $localArea = $request->input('local_area');
        $localAreaKn = $request->input('local_area_kn');

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

            // Persist or clear hyper-local place name
            if ($localArea) {
                $safeKn = $localAreaKn;
                if ($safeKn && preg_match('/bengaluru|bangalore|ಬೆಂಗಳೂರು/iu', $safeKn) && !preg_match('/bengaluru|bangalore/i', $localArea)) {
                    $safeKn = $localArea;
                }

                session([
                    'selected_local_area' => $localArea,
                    'selected_local_area_kn' => $safeKn ?: $localArea,
                ]);
                cookie()->queue('selected_local_area', $localArea, 525600);
                cookie()->queue('selected_local_area_kn', $safeKn ?: $localArea, 525600);
            } else {
                // User explicitly selected district from dropdown: clear previous GPS local area
                session()->forget(['selected_local_area', 'selected_local_area_kn']);
                cookie()->queue(cookie()->forget('selected_local_area'));
                cookie()->queue(cookie()->forget('selected_local_area_kn'));
            }

            $weatherService = app(\App\Services\Weather\WeatherSyncService::class);

            // If farmer gave GPS coordinates, sync farm-level weather on demand
            if ($lat && $lon) {
                try {
                    $weatherService->syncCoordinates((float) $lat, (float) $lon, $district, false);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('GPS weather sync failed in setLocation: ' . $e->getMessage());
                }
            } else {
                // If farmer selected a district, ensure fresh weather exists within TTL
                try {
                    $ttl = $weatherService->getCacheTtlMinutes();
                    $freshWeather = \App\Models\WeatherForecast::forDistrict($district->id)->today()->first();
                    if (!$freshWeather || \Carbon\Carbon::parse($freshWeather->fetched_at)->lt(\Carbon\Carbon::now()->subMinutes($ttl))) {
                        $weatherService->syncDistrict($district, true);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('District weather sync failed in setLocation: ' . $e->getMessage());
                }
            }

            $todayWeather = \App\Models\WeatherForecast::forDistrict($district->id)->today()->first();

            $safeKn = $localAreaKn;
            if ($localArea && $safeKn && preg_match('/bengaluru|bangalore|ಬೆಂಗಳೂರು/iu', $safeKn) && !preg_match('/bengaluru|bangalore/i', $localArea)) {
                $safeKn = $localArea;
            }

            return response()->json([
                'success' => true,
                'district_id' => $district->id,
                'district_name' => $district->name,
                'district_name_kn' => $district->name_kn,
                'local_area' => $localArea,
                'local_area_kn' => $safeKn ?: $localArea,
                'weather' => $todayWeather ? [
                    'temperature' => round($todayWeather->current_temperature ?? $todayWeather->temp_max ?? 28),
                    'temp_max' => round($todayWeather->temp_max ?? 30),
                    'temp_min' => round($todayWeather->temp_min ?? 22),
                    'condition_en' => $todayWeather->weather_condition_en ?? 'Partly Cloudy',
                    'condition_kn' => $todayWeather->weather_condition_kn ?? 'ಭಾಗಶಃ ಮೋಡ',
                    'rain_prob' => (int) round($todayWeather->precipitation_probability ?? 0),
                    'advisory_en' => $todayWeather->farming_advisory_en,
                    'advisory_kn' => $todayWeather->farming_advisory_kn,
                ] : null,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'District not found'], 404);
    }
}

