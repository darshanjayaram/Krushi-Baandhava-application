<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\WeatherForecast;
use App\Services\Weather\WeatherSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeatherController extends Controller
{
    public function __construct(
        protected WeatherSyncService $weatherService
    ) {}

    /**
     * Display the 7-day hyperlocal weather forecast and farming advisories.
     */
    public function index(Request $request): View
    {
        $districtId = $request->query('district') ?? $request->cookie('selected_district_id') ?? session('selected_district_id');

        // All active Karnataka districts with coordinates
        $allDistricts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get();

        $activeDistrict = null;
        if ($districtId) {
            $activeDistrict = $allDistricts->firstWhere('id', $districtId);
        }

        if (!$activeDistrict) {
            $activeDistrict = $allDistricts->firstWhere('name', 'Shivamogga') ?? $allDistricts->first();
        }

        if ($activeDistrict) {
            cookie()->queue('selected_district_id', $activeDistrict->id, 525600);
            session(['selected_district_id' => $activeDistrict->id]);
        }

        $forecasts = collect();
        $todayForecast = null;

        if ($activeDistrict) {
            // Check if forecasts exist for active district
            $forecasts = WeatherForecast::forDistrict($activeDistrict->id)
                ->upcoming()
                ->take(7)
                ->get();

            // If empty, auto-sync once to populate database seamlessly
            if ($forecasts->isEmpty()) {
                $this->weatherService->syncDistrict($activeDistrict);
                $forecasts = WeatherForecast::forDistrict($activeDistrict->id)
                    ->upcoming()
                    ->take(7)
                    ->get();
            }

            $todayForecast = $forecasts->firstWhere('forecast_date', Carbon::today())
                ?? $forecasts->first();
        }

        return view('farmer.weather.index', compact(
            'activeDistrict',
            'allDistricts',
            'forecasts',
            'todayForecast'
        ));
    }
}
