<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataApiController extends Controller
{
    /**
     * Get all active districts in Karnataka.
     */
    public function districts(): JsonResponse
    {
        $districts = District::where('is_active', true)
            ->select('id', 'name', 'name_kn', 'code', 'latitude', 'longitude')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $districts,
        ]);
    }

    /**
     * Get taluks for a specified district.
     */
    public function taluks(District $district): JsonResponse
    {
        $taluks = $district->taluks()
            ->where('is_active', true)
            ->select('id', 'district_id', 'name', 'name_kn', 'latitude', 'longitude')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'district' => [
                'id' => $district->id,
                'name' => $district->name,
                'name_kn' => $district->name_kn,
            ],
            'data' => $taluks,
        ]);
    }

    /**
     * Get APMC markets, optionally filtered by district or sorted by proximity.
     */
    public function markets(Request $request): JsonResponse
    {
        $districtId = $request->query('district_id');
        $lat = $request->query('latitude');
        $lon = $request->query('longitude');
        $radiusKm = (float) $request->query('radius', 100);

        $query = Market::karnataka()
            ->with('district:id,name,name_kn')
            ->where('is_active', true);

        if ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            $query->nearby((float) $lat, (float) $lon, $radiusKm);
        } else {
            if ($districtId) {
                $query->where('district_id', $districtId);
            }
            $query->orderBy('name');
        }

        $markets = $query->paginate(25);

        return response()->json([
            'status' => 'success',
            'data' => $markets->items(),
            'pagination' => [
                'current_page' => $markets->currentPage(),
                'last_page' => $markets->lastPage(),
                'per_page' => $markets->perPage(),
                'total' => $markets->total(),
            ],
        ]);
    }

    /**
     * Get active crops, with optional category or major filter.
     */
    public function crops(Request $request): JsonResponse
    {
        $isMajor = $request->query('is_major');
        $categoryId = $request->query('category_id');

        $crops = Crop::with(['category:id,name,slug', 'varieties:id,crop_id,name,name_kn,slug'])
            ->where('is_active', true)
            ->when($isMajor !== null, function ($q) use ($isMajor) {
                $q->where('is_major', filter_var($isMajor, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($categoryId, function ($q, $categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $crops,
        ]);
    }

    /**
     * Get varieties for a specific crop.
     */
    public function varieties(Crop $crop): JsonResponse
    {
        $varieties = $crop->varieties()
            ->where('is_active', true)
            ->select('id', 'crop_id', 'name', 'name_kn', 'slug')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'crop' => [
                'id' => $crop->id,
                'name' => $crop->name,
                'name_kn' => $crop->name_kn,
                'standard_unit' => $crop->standard_unit,
            ],
            'data' => $varieties,
        ]);
    }
}
