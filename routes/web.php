<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CropController;
use App\Http\Controllers\Admin\CropVarietyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataSourceController;
use App\Http\Controllers\Admin\DataSourceMappingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DataQualityController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\MarketController;
use App\Http\Controllers\Admin\MarketPriceController;
use App\Http\Controllers\Admin\SyncLogController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\TalukController;
use App\Http\Controllers\Admin\UnresolvedMappingController;
use App\Http\Controllers\Farmer\CropController as FarmerCropController;
use App\Http\Controllers\Farmer\HomeController;
use App\Http\Controllers\Farmer\MarketProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Farmer\NearbyMarketController;
use App\Http\Controllers\Farmer\WeatherController;

/*
|--------------------------------------------------------------------------
| Farmer PWA Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/set-location', [HomeController::class, 'setLocation'])->name('set-location');

// Language Switcher Route (Kannada <-> English)
Route::get('/locale/{lang}', function (string $lang, \Illuminate\Http\Request $request) {
    if (!in_array($lang, ['kn', 'en'], true)) {
        $lang = 'kn';
    }
    session(['locale' => $lang]);

    $redirectUrl = $request->header('referer') ?: url('/');
    return redirect($redirectUrl)->withCookie(cookie()->forever('locale', $lang));
})->name('locale.switch');
Route::get('/crops', [FarmerCropController::class, 'index'])->name('farmer.crops.index');
Route::get('/crops/{slug}', [FarmerCropController::class, 'show'])->name('farmer.crops.show');
Route::get('/crop/{crop}', [FarmerCropController::class, 'show'])->name('farmer.crop.detail');
Route::get('/markets', [MarketProfileController::class, 'index'])->name('farmer.markets.index');
Route::get('/nearby-markets', [NearbyMarketController::class, 'index'])->name('farmer.markets.nearby');
Route::get('/where-to-sell', [\App\Http\Controllers\Farmer\WhereToSellController::class, 'index'])->name('farmer.decision.where-to-sell');
Route::get('/markets/{code}', [MarketProfileController::class, 'show'])->name('farmer.markets.show');
Route::get('/weather', [WeatherController::class, 'index'])->name('farmer.weather.index');

// Agricultural CMS (Schemes, News, Videos, Agronomy Guides)
Route::get('/schemes', [\App\Http\Controllers\Farmer\SchemeController::class, 'index'])->name('farmer.schemes.index');
Route::get('/schemes/{slug}', [\App\Http\Controllers\Farmer\SchemeController::class, 'show'])->name('farmer.schemes.show');
Route::get('/news', [\App\Http\Controllers\Farmer\NewsController::class, 'index'])->name('farmer.news.index');
Route::get('/news/{slug}', [\App\Http\Controllers\Farmer\NewsController::class, 'show'])->name('farmer.news.show');
Route::get('/videos', [\App\Http\Controllers\Farmer\VideoController::class, 'index'])->name('farmer.videos.index');
Route::get('/articles', [\App\Http\Controllers\Farmer\ArticleController::class, 'index'])->name('farmer.articles.index');
Route::get('/articles/{slug}', [\App\Http\Controllers\Farmer\ArticleController::class, 'show'])->name('farmer.articles.show');

// PWA Offline Fallback & Dynamic XML Sitemap
Route::get('/offline', [HomeController::class, 'offline'])->name('offline');
Route::get('/sitemap.xml', [\App\Http\Controllers\Farmer\SitemapController::class, 'index'])->name('sitemap');

Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::get('/manifest.json', function () {
    return response(file_get_contents(public_path('manifest.json')), 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
})->name('pwa.manifest');

Route::get('/sw.js', function () {
    return response(file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript',
    ]);
})->name('pwa.sw');

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:admin-login')
        ->name('admin.login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    /*
    |--------------------------------------------------------------------------
    | Protected Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // Master Data: Districts & Taluks
        Route::patch('/districts/{district}/toggle', [DistrictController::class, 'toggleStatus'])->name('admin.districts.toggle');
        Route::resource('districts', DistrictController::class)->names('admin.districts');
        Route::resource('taluks', TalukController::class)->only(['store', 'update', 'destroy'])->names('admin.taluks');

        // Master Data: APMC Mandis
        Route::patch('/markets/{market}/toggle', [MarketController::class, 'toggleStatus'])->name('admin.markets.toggle');
        Route::post('/markets/{market}/aliases', [MarketController::class, 'addAlias'])->name('admin.markets.aliases.add');
        Route::delete('/markets/{market}/aliases/{mapping}', [MarketController::class, 'removeAlias'])->name('admin.markets.aliases.remove');
        Route::resource('markets', MarketController::class)->names('admin.markets');

        // Master Data: Crops & Varieties
        Route::patch('/crops/{crop}/toggle', [CropController::class, 'toggleStatus'])->name('admin.crops.toggle');
        Route::get('/crops/{crop}/live-varieties', [CropController::class, 'liveVarieties'])->name('admin.crops.live-varieties');
        Route::get('/crops/{crop}/inspect-feed', [CropController::class, 'inspectFeed'])->name('admin.crops.inspect-feed');
        Route::post('/crops/{crop}/variety-aliases', [CropController::class, 'addVarietyAlias'])->name('admin.crops.variety-aliases.add');
        Route::delete('/crops/{crop}/variety-aliases/{mapping}', [CropController::class, 'removeVarietyAlias'])->name('admin.crops.variety-aliases.remove');
        Route::resource('crops', CropController::class)->names('admin.crops');
        Route::resource('varieties', CropVarietyController::class)->only(['store', 'update', 'destroy'])->names('admin.varieties');

        // Data Sources & Providers
        Route::post('/datasources/{datasource}/toggle-status', [DataSourceController::class, 'toggleStatus'])->name('admin.datasources.toggle-status');
        Route::match(['GET', 'POST'], '/datasources/{datasource}/test-connection', [DataSourceController::class, 'testConnection'])->name('admin.datasources.test-connection');
        Route::post('/datasources/{datasource}/trigger-sync', [DataSourceController::class, 'triggerSync'])->name('admin.datasources.trigger-sync');

        Route::get('/datasources/{datasource}/mappings', [DataSourceMappingController::class, 'index'])->name('admin.datasources.mappings.index');
        Route::post('/datasources/{datasource}/mappings/fields', [DataSourceMappingController::class, 'storeFieldMapping'])->name('admin.datasources.mappings.fields.store');
        Route::delete('/datasources/mappings/fields/{mapping}', [DataSourceMappingController::class, 'destroyFieldMapping'])->name('admin.datasources.mappings.fields.destroy');
        Route::post('/datasources/{datasource}/mappings/crops', [DataSourceMappingController::class, 'storeCropAlias'])->name('admin.datasources.mappings.crops.store');
        Route::post('/datasources/{datasource}/mappings/markets', [DataSourceMappingController::class, 'storeMarketAlias'])->name('admin.datasources.mappings.markets.store');

        Route::resource('datasources', DataSourceController::class)->names('admin.datasources');

        // Market Prices
        Route::get('/prices', [MarketPriceController::class, 'index'])->name('admin.prices.index');
        Route::post('/prices/sync', [MarketPriceController::class, 'sync'])->name('admin.prices.sync');
        Route::post('/prices/sync-range', [MarketPriceController::class, 'syncRange'])->name('admin.prices.sync-range');
        Route::post('/prices/prune', [MarketPriceController::class, 'prune'])->name('admin.prices.prune');

        // Sync & API Health Logs
        Route::get('/sync-logs', [SyncLogController::class, 'index'])->name('admin.sync-logs.index');

        // Agricultural CMS & Knowledge Base
        Route::post('/schemes/{scheme}/toggle', [\App\Http\Controllers\Admin\SchemeController::class, 'toggle'])->name('admin.schemes.toggle');
        Route::resource('schemes', \App\Http\Controllers\Admin\SchemeController::class)->names('admin.schemes');

        Route::post('/news/{news}/toggle', [\App\Http\Controllers\Admin\NewsController::class, 'toggle'])->name('admin.news.toggle');
        Route::resource('news', \App\Http\Controllers\Admin\NewsController::class)->names('admin.news');

        Route::post('/videos/{video}/toggle', [\App\Http\Controllers\Admin\VideoController::class, 'toggle'])->name('admin.videos.toggle');
        Route::resource('videos', \App\Http\Controllers\Admin\VideoController::class)->names('admin.videos');

        Route::post('/articles/{article}/toggle', [\App\Http\Controllers\Admin\ArticleController::class, 'toggle'])->name('admin.articles.toggle');
        Route::resource('articles', \App\Http\Controllers\Admin\ArticleController::class)->names('admin.articles');

        // Feature Flags
        Route::get('/feature-flags', [FeatureFlagController::class, 'index'])->name('admin.feature-flags.index');
        Route::post('/feature-flags/{featureFlag}/toggle', [FeatureFlagController::class, 'toggle'])->name('admin.feature-flags.toggle');
        Route::put('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'update'])->name('admin.feature-flags.update');

        // System Settings
        Route::get('/settings', [SystemSettingController::class, 'index'])->name('admin.settings.index');
        Route::post('/settings', [SystemSettingController::class, 'update'])->name('admin.settings.update');

        // Data Quality & Rejected Records
        Route::get('/data-quality', [DataQualityController::class, 'index'])->name('admin.data-quality.index');
        Route::post('/data-quality/reprocess/{rawRecord}', [DataQualityController::class, 'reprocess'])->name('admin.data-quality.reprocess');
        Route::post('/data-quality/reprocess-all', [DataQualityController::class, 'reprocessAll'])->name('admin.data-quality.reprocess-all');
        Route::delete('/data-quality/{rawRecord}', [DataQualityController::class, 'destroy'])->name('admin.data-quality.destroy');

        // Unresolved Mappings
        Route::get('/unresolved-mappings', [UnresolvedMappingController::class, 'index'])->name('admin.unresolved-mappings.index');
        Route::post('/unresolved-mappings/resolve-crop', [UnresolvedMappingController::class, 'resolveCrop'])->name('admin.unresolved-mappings.resolve-crop');
        Route::post('/unresolved-mappings/resolve-market', [UnresolvedMappingController::class, 'resolveMarket'])->name('admin.unresolved-mappings.resolve-market');

        // Audit Trail Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');
    });
});
