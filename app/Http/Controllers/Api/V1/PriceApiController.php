<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceApiController extends Controller
{
    /**
     * Return today's verified canonical market prices.
     */
    public function today(Request $request): JsonResponse
    {
        $cropSlug = $request->query('crop');
        $marketCode = $request->query('market');
        $districtId = $request->query('district');
        $categorySlug = $request->query('category');
        $date = $request->query('date');

        $targetDate = $date ?? MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();

        $query = MarketPrice::karnataka()
            ->with(['crop', 'variety', 'market.district', 'dataSource'])
            ->where('price_date', $targetDate);

        if ($cropSlug) {
            $query->whereHas('crop', fn ($c) => $c->where('slug', $cropSlug));
        }

        if ($marketCode) {
            $query->whereHas('market', fn ($m) => $m->where('code', $marketCode));
        }

        if ($districtId) {
            $query->whereHas('market', fn ($m) => $m->where('district_id', $districtId));
        }

        if ($categorySlug) {
            $query->whereHas('crop.category', fn ($c) => $c->where('slug', $categorySlug));
        }

        $prices = $query->orderBy('modal_price', 'desc')->get();

        $payload = $prices->map(function (MarketPrice $price) {
            return [
                'id' => $price->id,
                'price_date' => $price->price_date->toDateString(),
                'crop' => [
                    'id' => $price->crop->id,
                    'name' => $price->crop->name,
                    'name_kn' => $price->crop->name_kn,
                    'slug' => $price->crop->slug,
                ],
                'variety' => $price->variety ? [
                    'id' => $price->variety->id,
                    'name' => $price->variety->name,
                    'name_kn' => $price->variety->name_kn,
                ] : null,
                'market' => [
                    'id' => $price->market->id,
                    'name' => $price->market->name,
                    'name_kn' => $price->market->name_kn,
                    'code' => $price->market->code,
                    'district' => $price->market->district ? [
                        'id' => $price->market->district->id,
                        'name' => $price->market->district->name,
                        'name_kn' => $price->market->district->name_kn,
                    ] : null,
                ],
                'modal_price' => (float) $price->modal_price,
                'min_price' => $price->min_price ? (float) $price->min_price : null,
                'max_price' => $price->max_price ? (float) $price->max_price : null,
                'price_spread' => $price->price_spread,
                'arrival_quantity' => $price->arrival_quantity ? (float) $price->arrival_quantity : null,
                'unit' => $price->unit,
                'source' => $price->dataSource ? $price->dataSource->name : 'APMC Mandi Feed',
            ];
        });

        return response()->json([
            'success' => true,
            'price_date' => $targetDate,
            'count' => $payload->count(),
            'data' => $payload,
        ]);
    }
}
