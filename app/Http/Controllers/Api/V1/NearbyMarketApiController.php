<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Location\Contracts\GeocoderInterface;
use App\Services\Location\NearbyMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NearbyMarketApiController extends Controller
{
    public function __construct(
        protected NearbyMarketService $nearbyService,
        protected GeocoderInterface $geocoder
    ) {}

    /**
     * Discover nearby Karnataka APMC markets from coordinates.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:300'],
            'crop' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radiusKm = isset($validated['radius']) ? (float) $validated['radius'] : 50.0;
        $cropSlug = $validated['crop'] ?? null;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 20;

        // Perform reverse geocoding to resolve locality
        $userLocation = $this->geocoder->reverseGeocode($latitude, $longitude);

        // Query nearby Karnataka markets
        $markets = $this->nearbyService->findNearby(
            $latitude,
            $longitude,
            $radiusKm,
            $cropSlug,
            $limit
        );

        $payload = $markets->map(function ($market) {
            return [
                'id' => $market->id,
                'name' => $market->name,
                'name_kn' => $market->name_kn,
                'code' => $market->code,
                'market_type' => $market->market_type,
                'latitude' => (float) $market->latitude,
                'longitude' => (float) $market->longitude,
                'address' => $market->address,
                'district' => $market->district ? [
                    'id' => $market->district->id,
                    'name' => $market->district->name,
                    'name_kn' => $market->district->name_kn,
                ] : null,
                'taluk' => $market->taluk ? [
                    'id' => $market->taluk->id,
                    'name' => $market->taluk->name,
                    'name_kn' => $market->taluk->name_kn,
                ] : null,
                'distance_km' => $market->distance_km,
                'bearing_degrees' => $market->bearing_degrees,
                'direction' => [
                    'en' => $market->direction_en,
                    'kn' => $market->direction_kn,
                ],
                'commodities_count' => $market->commodities_count,
                'top_prices' => $market->top_prices->map(function ($p) {
                    return [
                        'crop' => $p->crop->name,
                        'crop_kn' => $p->crop->name_kn,
                        'variety' => $p->variety?->name,
                        'modal_price' => (float) $p->modal_price,
                        'unit' => $p->unit,
                    ];
                }),
                'filtered_crop_price' => isset($market->filtered_crop_price) && $market->filtered_crop_price ? [
                    'modal_price' => (float) $market->filtered_crop_price->modal_price,
                    'min_price' => (float) $market->filtered_crop_price->min_price,
                    'max_price' => (float) $market->filtered_crop_price->max_price,
                    'unit' => $market->filtered_crop_price->unit,
                ] : null,
                'google_maps_url' => $market->google_maps_url,
            ];
        });

        return response()->json([
            'success' => true,
            'user_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'district' => $userLocation['district'] ?? null,
                'taluk' => $userLocation['taluk'] ?? null,
                'locality' => $userLocation['locality'] ?? null,
                'display_name' => $userLocation['display_name'] ?? null,
            ],
            'radius_km' => $radiusKm,
            'crop_filter' => $cropSlug,
            'count' => $payload->count(),
            'data' => $payload,
        ]);
    }
}
