<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Services\Market\WhereToSellService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class WhereToSellController extends Controller
{
    public function __construct(
        protected WhereToSellService $decisionService
    ) {}

    /**
     * Display the Where to Sell decision simulator page.
     */
    public function index(Request $request): View
    {
        // 1. Get all active Karnataka crops that have market prices (cached for instant performance)
        $crops = Cache::remember('where_to_sell_active_crops', 1800, function () {
            $karnatakaMarketIds = Market::karnataka()->pluck('id');

            $tradedCropIds = MarketPrice::whereIn('market_id', $karnatakaMarketIds)
                ->distinct()
                ->pluck('crop_id');

            $items = Crop::where('is_active', true)
                ->whereIn('id', $tradedCropIds)
                ->with(['varieties' => fn($q) => $q->where('is_active', true)->orderBy('name')])
                ->orderBy('name')
                ->get();

            if ($items->isEmpty()) {
                $items = Crop::where('is_active', true)
                    ->with(['varieties' => fn($q) => $q->where('is_active', true)->orderBy('name')])
                    ->orderBy('name')
                    ->take(20)
                    ->get();
            }

            return $items;
        });

        // 2. Resolve selected crop (from slug, id, or default to first crop)
        $selectedCrop = null;
        if ($request->filled('crop')) {
            $cropParam = $request->input('crop');
            $selectedCrop = is_numeric($cropParam)
                ? $crops->firstWhere('id', (int) $cropParam)
                : $crops->firstWhere('slug', $cropParam);

            if (!$selectedCrop) {
                $selectedCrop = is_numeric($cropParam)
                    ? Crop::with(['varieties' => fn($q) => $q->where('is_active', true)->orderBy('name')])->find($cropParam)
                    : Crop::with(['varieties' => fn($q) => $q->where('is_active', true)->orderBy('name')])->where('slug', $cropParam)->first();
            }
        }

        if (!$selectedCrop && $crops->isNotEmpty()) {
            $selectedCrop = $crops->first();
        }

        // 3. Resolve districts with taluks for the location picker fallback (cached)
        $districts = Cache::remember('where_to_sell_districts_with_taluks', 86400, function () {
            return District::where('is_active', true)
                ->whereHas('state', fn($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->with(['taluks' => fn($t) => $t->where('is_active', true)])
                ->orderBy('name')
                ->get();
        });

        // 4. Gather simulation parameters
        $quantity = max(0.5, (float) $request->input('quantity', 10.0));
        $vehicle = $request->input('vehicle', 'pickup');
        $rateType = $request->input('rate_type', 'per_km');
        $customRate = $request->filled('custom_rate') ? (float) $request->input('custom_rate') : null;
        $varietyId = $request->filled('variety_id') ? (int) $request->input('variety_id') : null;
        $baselineMarketId = $request->filled('market_id') 
            ? (int) $request->input('market_id') 
            : ($request->filled('baseline_market_id') ? (int) $request->input('baseline_market_id') : null);
        $sort = $request->input('sort', 'net_realization');

        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;
        $districtId = $request->filled('district_id') 
            ? (int) $request->input('district_id') 
            : ($lat ? null : (int) ($request->cookie('selected_district_id') ?? session('selected_district_id')));
        $talukId = $request->filled('taluk_id') ? (int) $request->input('taluk_id') : null;

        $maxDistance = $request->filled('max_distance') ? (float) $request->input('max_distance') : null;
        $roundTrip = $request->has('round_trip') ? $request->boolean('round_trip') : true;
        $isContextual = $request->boolean('from_crop', false) || ($request->filled('crop') && $request->filled('variety_id'));

        // 5. Initial state: comparison is performed on-demand asynchronously when user clicks "Calculate Realization"
        $comparison = [];

        return view('farmer.decision.where_to_sell', [
            'crops' => $crops,
            'selectedCrop' => $selectedCrop,
            'districts' => $districts,
            'comparison' => $comparison,
            'isContextual' => $isContextual,
            'vehicles' => WhereToSellService::VEHICLES,
            'params' => [
                'crop' => $selectedCrop?->slug,
                'variety_id' => $varietyId,
                'market_id' => $baselineMarketId,
                'baseline_market_id' => $baselineMarketId,
                'quantity' => $quantity,
                'vehicle' => $vehicle,
                'rate_type' => $rateType,
                'custom_rate' => $customRate,
                'lat' => $lat,
                'lng' => $lng,
                'district_id' => $districtId,
                'taluk_id' => $talukId,
                'max_distance' => $maxDistance,
                'round_trip' => $roundTrip,
                'from_crop' => $isContextual ? 1 : 0,
                'sort' => $sort,
            ],
        ]);
    }
}
