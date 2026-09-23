<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\WeatherForecast;
use App\Services\Weather\WeatherSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherApiController extends Controller
{
    public function __construct(
        protected WeatherSyncService $weatherService
    ) {}

    /**
     * Retrieve 7-day hyperlocal weather forecast for a district or coordinates.
     */
    public function forecast(Request $request): JsonResponse
    {
        $districtId = $request->query('district_id');
        $lat = $request->query('latitude');
        $lon = $request->query('longitude');

        $district = null;

        if ($districtId) {
            $district = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->find($districtId);
        } elseif ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            // Find closest Karnataka district by coordinates
            $districts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();

            $userLat = (float) $lat;
            $userLon = (float) $lon;

            $district = $districts->sortBy(function ($d) use ($userLat, $userLon) {
                return pow($d->latitude - $userLat, 2) + pow($d->longitude - $userLon, 2);
            })->first();
        }

        if (!$district) {
            $district = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->where('name', 'Shivamogga')
                ->first()
                ?? District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))->first();
        }

        if (!$district) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active Karnataka district found.',
            ], 404);
        }

        $forecasts = WeatherForecast::forDistrict($district->id)
            ->upcoming()
            ->take(7)
            ->get();

        if ($forecasts->isEmpty()) {
            $this->weatherService->syncDistrict($district);
            $forecasts = WeatherForecast::forDistrict($district->id)
                ->upcoming()
                ->take(7)
                ->get();
        }

        $today = $forecasts->firstWhere('forecast_date', Carbon::today()) ?? $forecasts->first();

        $dailyPayload = $forecasts->map(function (WeatherForecast $f) {
            return [
                'date' => $f->forecast_date->toDateString(),
                'day_name' => [
                    'en' => $f->day_name_en,
                    'kn' => $f->day_name_kn,
                ],
                'temp_min' => (float) $f->temp_min,
                'temp_max' => (float) $f->temp_max,
                'precipitation_probability' => (float) $f->precipitation_probability,
                'weather_code' => $f->weather_code,
                'condition' => [
                    'en' => $f->weather_condition_en,
                    'kn' => $f->weather_condition_kn,
                ],
                'icon' => $f->weather_icon,
                'farming_advisory' => [
                    'en' => $f->farming_advisory_en,
                    'kn' => $f->farming_advisory_kn,
                ],
            ];
        });

        return response()->json([
            'status' => 'success',
            'district' => [
                'id' => $district->id,
                'name' => $district->name,
                'name_kn' => $district->name_kn,
                'latitude' => (float) $district->latitude,
                'longitude' => (float) $district->longitude,
            ],
            'current' => $today ? [
                'temperature' => $today->current_temperature !== null ? (float) $today->current_temperature : (float) $today->temp_max,
                'humidity' => $today->current_humidity !== null ? (float) $today->current_humidity : null,
                'wind_speed' => $today->current_wind_speed !== null ? (float) $today->current_wind_speed : null,
                'weather_code' => $today->current_weather_code ?? $today->weather_code,
                'condition' => [
                    'en' => $today->weather_condition_en,
                    'kn' => $today->weather_condition_kn,
                ],
                'icon' => $today->weather_icon,
                'advisory' => [
                    'en' => $today->farming_advisory_en,
                    'kn' => $today->farming_advisory_kn,
                ],
            ] : null,
            'forecast_days' => $dailyPayload->count(),
            'daily' => $dailyPayload,
            'source' => 'Open-Meteo',
        ]);
    }
}
