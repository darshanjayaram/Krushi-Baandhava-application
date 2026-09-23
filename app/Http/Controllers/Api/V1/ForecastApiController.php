<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Market;
use App\Services\Forecast\ForecastingEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForecastApiController extends Controller
{
    public function __construct(
        protected ForecastingEngineService $forecastingService
    ) {}

    /**
     * Get multi-horizon price projections and confidence bounds for a crop.
     * GET /api/v1/forecasts?crop=arecanut&market_id=2
     */
    public function index(Request $request): JsonResponse
    {
        $crop = $this->resolveCrop($request);
        if (! $crop) {
            return response()->json([
                'success' => false,
                'message' => 'Crop not found or missing crop_id / crop parameter.',
            ], 404);
        }

        $marketId = $this->resolveMarketId($request);
        $forecastData = $this->forecastingService->getForecastsForCrop($crop->id, $marketId);

        return response()->json([
            'success' => true,
            'crop' => [
                'id' => $crop->id,
                'name' => $crop->name,
                'name_kn' => $crop->name_kn,
                'slug' => $crop->slug,
            ],
            'market_id' => $marketId,
            'forecast' => $forecastData,
        ]);
    }

    protected function resolveCrop(Request $request): ?Crop
    {
        if ($request->filled('crop_id')) {
            return Crop::find($request->query('crop_id'));
        }

        if ($request->filled('crop')) {
            return Crop::where('slug', $request->query('crop'))
                ->orWhere('name', $request->query('crop'))
                ->first();
        }

        return null;
    }

    protected function resolveMarketId(Request $request): ?int
    {
        if ($request->filled('market_id')) {
            return (int) $request->query('market_id');
        }

        if ($request->filled('market')) {
            $mkt = Market::karnataka()
                ->where('slug', $request->query('market'))
                ->orWhere('code', $request->query('market'))
                ->first();

            return $mkt?->id;
        }

        return null;
    }
}
