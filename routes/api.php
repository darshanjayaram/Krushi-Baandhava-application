<?php

use App\Http\Controllers\Api\V1\MasterDataApiController;
use App\Http\Controllers\Api\V1\NearbyMarketApiController;
use App\Http\Controllers\Api\V1\PriceApiController;
use App\Http\Controllers\Api\V1\WeatherApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Geographic lookups
    Route::get('/districts', [MasterDataApiController::class, 'districts'])->name('api.v1.districts');
    Route::get('/districts/{district}/taluks', [MasterDataApiController::class, 'taluks'])->name('api.v1.districts.taluks');
    Route::get('/markets', [MasterDataApiController::class, 'markets'])->name('api.v1.markets');
    Route::get('/markets/nearby', [NearbyMarketApiController::class, 'index'])->name('api.v1.markets.nearby');

    // Commodity & variety lookups
    Route::get('/crops', [MasterDataApiController::class, 'crops'])->name('api.v1.crops');
    Route::get('/crops/{crop}/varieties', [MasterDataApiController::class, 'varieties'])->name('api.v1.crops.varieties');

    // Verified market prices discovery
    Route::get('/prices/today', [PriceApiController::class, 'today'])->name('api.v1.prices.today');

    // Hyperlocal weather forecasts & agricultural advisories
    Route::get('/weather/forecast', [WeatherApiController::class, 'forecast'])->name('api.v1.weather.forecast');

    // Historical analytics, price trends & seasonality
    Route::get('/analytics/trends', [\App\Http\Controllers\Api\V1\AnalyticsApiController::class, 'trends'])->name('api.v1.analytics.trends');
    Route::get('/analytics/seasonality', [\App\Http\Controllers\Api\V1\AnalyticsApiController::class, 'seasonality'])->name('api.v1.analytics.seasonality');
    Route::get('/analytics/summary', [\App\Http\Controllers\Api\V1\AnalyticsApiController::class, 'summary'])->name('api.v1.analytics.summary');

    // Statistical price projections & confidence bounds
    Route::get('/forecasts', [\App\Http\Controllers\Api\V1\ForecastApiController::class, 'index'])->name('api.v1.forecasts');

    // Where to Sell multi-mandi net realization decision engine
    Route::get('/decision/where-to-sell', [\App\Http\Controllers\Api\V1\WhereToSellApiController::class, 'compare'])
        ->middleware('throttle:decision')
        ->name('api.v1.decision.where-to-sell');

    // Agricultural CMS (Schemes, News, Videos, Agronomy Guides)
    Route::get('/schemes', [\App\Http\Controllers\Api\V1\CmsApiController::class, 'schemes'])->name('api.v1.schemes');
    Route::get('/news', [\App\Http\Controllers\Api\V1\CmsApiController::class, 'news'])->name('api.v1.news');
    Route::get('/videos', [\App\Http\Controllers\Api\V1\CmsApiController::class, 'videos'])->name('api.v1.videos');
    Route::get('/articles', [\App\Http\Controllers\Api\V1\CmsApiController::class, 'articles'])->name('api.v1.articles');
});
