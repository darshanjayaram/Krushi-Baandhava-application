<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \App\Services\Location\Contracts\GeocoderInterface::class,
            \App\Services\Location\NominatimGeocoder::class
        );

        $this->app->singleton(
            \App\Services\Weather\Contracts\WeatherProviderInterface::class,
            \App\Services\Weather\OpenMeteoWeatherProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('decision', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Share active district & all Karnataka districts with all farmer layout views & location modal
        \Illuminate\Support\Facades\View::composer(['layouts.farmer', 'components.location-modal'], function ($view) {
            $request = request();
            $districtId = $request->query('district') ?? $request->cookie('selected_district_id') ?? session('selected_district_id');

            $allDistricts = \Illuminate\Support\Facades\Cache::remember('karnataka_districts_list', 3600, function () {
                return \App\Models\District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'name_kn', 'latitude', 'longitude', 'code']);
            });

            $activeDistrict = null;
            if ($districtId) {
                $activeDistrict = $allDistricts->firstWhere('id', $districtId);
            }
            if (!$activeDistrict) {
                $activeDistrict = $allDistricts->firstWhere('name', 'Shivamogga') ?? $allDistricts->first();
            }

            $view->with('allDistricts', $allDistricts)
                 ->with('activeDistrict', $activeDistrict);
        });
    }
}
