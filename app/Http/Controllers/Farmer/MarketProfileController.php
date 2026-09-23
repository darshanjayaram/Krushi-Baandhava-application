<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketProfileController extends Controller
{
    /**
     * Display a directory of all APMC markets filterable by district.
     */
    public function index(Request $request): View
    {
        $districtId = $request->query('district');
        $search = trim((string) $request->query('search', ''));

        $query = Market::karnataka()
            ->with(['district', 'taluk'])
            ->where('is_active', true)
            ->orderBy('name');

        if ($districtId) {
            $query->where('district_id', $districtId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_kn', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $markets = $query->paginate(16)->withQueryString();

        $districts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->where('is_active', true)
            ->withCount(['markets' => fn ($q) => $q->karnataka()->where('is_active', true)])
            ->orderBy('name')
            ->get();

        // Count commodities traded today per market (Strictly Karnataka)
        $latestDate = MarketPrice::karnataka()->max('price_date') ?? Carbon::today()->toDateString();
        $tradedCountByMarket = MarketPrice::karnataka()->where('price_date', $latestDate)
            ->selectRaw('market_id, COUNT(DISTINCT crop_id) as crop_count')
            ->groupBy('market_id')
            ->get()
            ->keyBy('market_id');

        return view('farmer.markets.index', compact('markets', 'districts', 'districtId', 'search', 'tradedCountByMarket', 'latestDate'));
    }

    /**
     * Display the detailed market profile and today's traded commodity rates.
     */
    public function show(string $code): View
    {
        $market = Market::karnataka()
            ->with(['district', 'taluk'])
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $latestDate = MarketPrice::karnataka()->where('market_id', $market->id)->max('price_date')
            ?? MarketPrice::karnataka()->max('price_date')
            ?? Carbon::today()->toDateString();

        $prices = MarketPrice::karnataka()
            ->with(['crop.category', 'variety', 'dataSource'])
            ->where('market_id', $market->id)
            ->where('price_date', $latestDate)
            ->orderBy('modal_price', 'desc')
            ->get();

        $stats = [
            'total_commodities' => $prices->count(),
            'total_arrivals' => $prices->sum('arrival_quantity') ?? 0,
            'highest_rate' => $prices->max('modal_price') ?? 0,
            'highest_crop' => $prices->firstWhere('modal_price', $prices->max('modal_price'))?->crop->name ?? '—',
            'date_formatted' => Carbon::parse($latestDate)->format('d M Y'),
        ];

        return view('farmer.markets.show', compact('market', 'prices', 'stats', 'latestDate'));
    }
}
