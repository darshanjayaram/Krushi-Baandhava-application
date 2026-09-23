<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\District;
use App\Models\MarketPrice;
use App\Services\Market\WhereToSellService;
use Illuminate\Http\Request;
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
        // 1. Get all active Karnataka crops that have market prices
        $tradedCropIds = MarketPrice::karnataka()
            ->select('crop_id')
            ->distinct()
            ->pluck('crop_id');

        $crops = Crop::where('is_active', true)
            ->whereIn('id', $tradedCropIds)
            ->orderBy('name')
            ->get();

        if ($crops->isEmpty()) {
            $crops = Crop::where('is_active', true)->orderBy('name')->take(20)->get();
        }

        // 2. Resolve selected crop (from slug, id, or default to first crop)
        $selectedCrop = null;
        if ($request->filled('crop')) {
            $cropParam = $request->input('crop');
            $selectedCrop = is_numeric($cropParam)
                ? Crop::find($cropParam)
                : Crop::where('slug', $cropParam)->first();
        }

        if (!$selectedCrop && $crops->isNotEmpty()) {
            $selectedCrop = $crops->first();
        }

        // 3. Resolve districts with taluks for the location picker fallback
        $districts = District::where('is_active', true)
            ->whereHas('state', fn($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->with(['taluks' => fn($t) => $t->where('is_active', true)])
            ->orderBy('name')
            ->get();

        // 4. Gather simulation parameters
        $quantity = max(0.5, (float) $request->input('quantity', 10.0));
        $vehicle = $request->input('vehicle', 'pickup');
        $customRate = $request->filled('custom_rate') ? (float) $request->input('custom_rate') : null;
        $sort = $request->input('sort', 'net_realization');

        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;
        $districtId = $request->filled('district_id') ? (int) $request->input('district_id') : null;
        $talukId = $request->filled('taluk_id') ? (int) $request->input('taluk_id') : null;

        // 5. Run Decision Engine Comparison
        $comparison = [];
        if ($selectedCrop) {
            $comparison = $this->decisionService->compare(
                $selectedCrop->id,
                $lat,
                $lng,
                $quantity,
                [
                    'vehicle' => $vehicle,
                    'custom_rate' => $customRate,
                    'district_id' => $districtId,
                    'taluk_id' => $talukId,
                    'sort' => $sort,
                ]
            );
        }

        return view('farmer.decision.where_to_sell', [
            'crops' => $crops,
            'selectedCrop' => $selectedCrop,
            'districts' => $districts,
            'comparison' => $comparison,
            'vehicles' => WhereToSellService::VEHICLES,
            'params' => [
                'crop' => $selectedCrop?->slug,
                'quantity' => $quantity,
                'vehicle' => $vehicle,
                'custom_rate' => $customRate,
                'lat' => $lat,
                'lng' => $lng,
                'district_id' => $districtId,
                'taluk_id' => $talukId,
                'sort' => $sort,
            ],
        ]);
    }
}
