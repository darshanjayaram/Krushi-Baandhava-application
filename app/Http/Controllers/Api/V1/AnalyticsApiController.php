<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Market;
use App\Services\Analytics\HistoricalAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsApiController extends Controller
{
    public function __construct(
        protected HistoricalAnalyticsService $analyticsService
    ) {}

    /**
     * Get price and arrival time series trends.
     * GET /api/v1/analytics/trends?crop_id=1&market_id=2&range=30d
     */
    public function trends(Request $request): JsonResponse
    {
        $crop = $this->resolveCrop($request);
        if (! $crop) {
            return response()->json([
                'success' => false,
                'message' => 'Crop not found or missing crop_id parameter.',
            ], 404);
        }

        $marketId = $this->resolveMarketId($request);
        $rangeParam = $request->query('range', '30d');
        $days = match ($rangeParam) {
            '7d' => 7,
            '15d' => 15,
            '90d' => 90,
            '365d', '1y' => 365,
            default => 30,
        };

        $trends = $this->analyticsService->getDailyTrends($crop->id, $marketId, $days);

        return response()->json([
            'success' => true,
            'crop' => [
                'id' => $crop->id,
                'name' => $crop->name,
                'name_kn' => $crop->name_kn,
                'slug' => $crop->slug,
            ],
            'market_id' => $marketId,
            'range_days' => $days,
            'data' => $trends,
        ]);
    }

    /**
     * Get 12-month seasonal index and Best Months to Sell recommendations.
     * GET /api/v1/analytics/seasonality?crop_id=1
     */
    public function seasonality(Request $request): JsonResponse
    {
        $crop = $this->resolveCrop($request);
        if (! $crop) {
            return response()->json([
                'success' => false,
                'message' => 'Crop not found or missing crop_id parameter.',
            ], 404);
        }

        $marketId = $this->resolveMarketId($request);
        $seasonalData = $this->analyticsService->getSeasonalAnalysis($crop->id, $marketId);

        return response()->json([
            'success' => true,
            'crop' => [
                'id' => $crop->id,
                'name' => $crop->name,
                'name_kn' => $crop->name_kn,
                'slug' => $crop->slug,
            ],
            'market_id' => $marketId,
            'data' => $seasonalData,
        ]);
    }

    /**
     * Get statistical volatility and price summary.
     * GET /api/v1/analytics/summary?crop_id=1&market_id=2&days=30
     */
    public function summary(Request $request): JsonResponse
    {
        $crop = $this->resolveCrop($request);
        if (! $crop) {
            return response()->json([
                'success' => false,
                'message' => 'Crop not found or missing crop_id parameter.',
            ], 404);
        }

        $marketId = $this->resolveMarketId($request);
        $days = (int) $request->query('days', 30);
        $summary = $this->analyticsService->getStatisticalSummary($crop->id, $marketId, $days);

        return response()->json([
            'success' => true,
            'crop' => [
                'id' => $crop->id,
                'name' => $crop->name,
                'name_kn' => $crop->name_kn,
                'slug' => $crop->slug,
            ],
            'market_id' => $marketId,
            'days' => $days,
            'data' => $summary,
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
