<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Services\Market\WhereToSellService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhereToSellApiController extends Controller
{
    public function __construct(
        protected WhereToSellService $decisionService
    ) {}

    /**
     * Run Where to Sell comparison simulation via API.
     */
    public function compare(Request $request): JsonResponse
    {
        $cropParam = $request->input('crop_id') ?? $request->input('crop');

        if (!$cropParam) {
            return response()->json([
                'success' => false,
                'message' => 'Crop ID or slug is required.',
            ], 422);
        }

        $crop = is_numeric($cropParam)
            ? Crop::find($cropParam)
            : Crop::where('slug', $cropParam)->first();

        if (!$crop) {
            return response()->json([
                'success' => false,
                'message' => 'Crop not found.',
            ], 404);
        }

        $quantity = max(0.5, (float) $request->input('quantity', 10.0));
        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;
        $districtId = $request->filled('district_id') ? (int) $request->input('district_id') : null;
        $talukId = $request->filled('taluk_id') ? (int) $request->input('taluk_id') : null;
        $vehicle = $request->input('vehicle', 'pickup');
        $rateType = $request->input('rate_type', 'per_km');
        $customRate = $request->filled('custom_rate') ? (float) $request->input('custom_rate') : null;
        $varietyId = $request->filled('variety_id') ? (int) $request->input('variety_id') : null;
        $baselineMarketId = $request->filled('market_id') 
            ? (int) $request->input('market_id') 
            : ($request->filled('baseline_market_id') ? (int) $request->input('baseline_market_id') : null);
        $sort = $request->input('sort', 'net_realization');
        $maxDistance = $request->filled('max_distance') ? (float) $request->input('max_distance') : null;
        $roundTrip = $request->has('round_trip') ? $request->boolean('round_trip') : true;

        $result = $this->decisionService->compare(
            $crop->id,
            $lat,
            $lng,
            $quantity,
            [
                'vehicle' => $vehicle,
                'rate_type' => $rateType,
                'custom_rate' => $customRate,
                'variety_id' => $varietyId,
                'baseline_market_id' => $baselineMarketId,
                'market_id' => $baselineMarketId,
                'district_id' => $districtId,
                'taluk_id' => $talukId,
                'sort' => $sort,
                'max_distance' => $maxDistance,
                'round_trip' => $roundTrip,
            ]
        );

        return response()->json($result);
    }
}
