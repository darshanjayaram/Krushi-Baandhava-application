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
use App\Http\Controllers\Admin\PriceFreshnessController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SyncLogController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\TalukController;
use App\Http\Controllers\Admin\UnresolvedMappingController;
use App\Http\Controllers\Admin\UserController;
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
Route::get('/reverse-geocode', [HomeController::class, 'reverseGeocode'])->name('reverse-geocode');

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
Route::get('/crops/{slug}/trend-ajax', [FarmerCropController::class, 'trendAjax'])->name('farmer.crops.trend-ajax');
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

// Farmer Feedback & Grievance Helpdesk
Route::get('/feedback', [\App\Http\Controllers\Farmer\FeedbackController::class, 'create'])->name('farmer.feedback.create');
Route::post('/feedback', [\App\Http\Controllers\Farmer\FeedbackController::class, 'store'])->name('farmer.feedback.store');

Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::get('/manifest.json', function (\App\Services\Pwa\PwaManifestService $pwaService) {
    return $pwaService->response();
})->name('pwa.manifest');

Route::get('/sw.js', function () {
    return response(file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript',
    ]);
})->name('pwa.sw');

// Web Setup / 1-Click Installation Wizard for cPanel Fresh Deployments
Route::get('/setup', [\App\Http\Controllers\SetupController::class, 'index'])->name('setup.index');
Route::post('/setup', [\App\Http\Controllers\SetupController::class, 'run'])->name('setup.run');
Route::post('/setup/test-db', [\App\Http\Controllers\SetupController::class, 'testDb'])->name('setup.test-db');
Route::post('/setup/test-api', [\App\Http\Controllers\SetupController::class, 'testApiKey'])->name('setup.test-api');

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
        Route::post('/scheduler/test', [DashboardController::class, 'runSchedulerTest'])->name('admin.scheduler.test');
        Route::post('/scheduler/toggle-task', [DashboardController::class, 'toggleScheduledTask'])->name('admin.scheduler.toggle-task');

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
        Route::post('/crops-media/upload', [CropController::class, 'uploadMedia'])->name('admin.crops.media.upload');
        Route::post('/crops-media/batch-delete', [CropController::class, 'batchDeleteMedia'])->name('admin.crops.media.batch-delete');
        Route::post('/crops/batch-delete', [CropController::class, 'batchDestroy'])->name('admin.crops.batch-delete');
        Route::resource('crops', CropController::class)->names('admin.crops');
        Route::resource('varieties', CropVarietyController::class)->only(['store', 'update', 'destroy'])->names('admin.varieties');

        // Data Sources & Providers
        Route::post('/datasources/sync-all', [DataSourceController::class, 'syncAll'])->name('admin.datasources.sync-all');
        Route::post('/datasources/update-schedule-timings', [DataSourceController::class, 'updateScheduleTimings'])->name('admin.datasources.update-schedule-timings');
        Route::post('/datasources/toggle-krama-failover', [DataSourceController::class, 'toggleKramaFailover'])->name('admin.datasources.toggle-krama-failover');
        Route::post('/datasources/{datasource}/toggle-status', [DataSourceController::class, 'toggleStatus'])->name('admin.datasources.toggle-status');
        Route::post('/datasources/{datasource}/toggle-cron', [DataSourceController::class, 'toggleCron'])->name('admin.datasources.toggle-cron');
        Route::match(['GET', 'POST'], '/datasources/{datasource}/test-connection', [DataSourceController::class, 'testConnection'])->name('admin.datasources.test-connection');
        Route::post('/datasources/{datasource}/trigger-sync', [DataSourceController::class, 'triggerSync'])->name('admin.datasources.trigger-sync');
        Route::post('/datasources/{datasource}/retry-crop', [DataSourceController::class, 'retryCrop'])->name('admin.datasources.retry-crop');
        Route::post('/datasources/{datasource}/retry-all', [DataSourceController::class, 'retryAll'])->name('admin.datasources.retry-all');

        // Crop Synchronization Whitelist & Single-Crop Sync
        Route::get('/datasources/{datasource}/crop-sync', [DataSourceController::class, 'getCropSyncConfig'])->name('admin.datasources.crop-sync.get');
        Route::post('/datasources/{datasource}/crop-sync', [DataSourceController::class, 'updateCropSyncConfig'])->name('admin.datasources.crop-sync.update');
        Route::post('/datasources/{datasource}/sync-crop/{crop}', [DataSourceController::class, 'syncSingleCrop'])->name('admin.datasources.sync-crop');

        Route::get('/datasources/{datasource}/mappings', [DataSourceMappingController::class, 'index'])->name('admin.datasources.mappings.index');
        Route::post('/datasources/{datasource}/mappings/fields', [DataSourceMappingController::class, 'storeFieldMapping'])->name('admin.datasources.mappings.fields.store');
        Route::delete('/datasources/mappings/fields/{mapping}', [DataSourceMappingController::class, 'destroyFieldMapping'])->name('admin.datasources.mappings.fields.destroy');
        Route::post('/datasources/{datasource}/mappings/crops', [DataSourceMappingController::class, 'storeCropAlias'])->name('admin.datasources.mappings.crops.store');
        Route::post('/datasources/{datasource}/mappings/markets', [DataSourceMappingController::class, 'storeMarketAlias'])->name('admin.datasources.mappings.markets.store');
        Route::get('/datasources/agmarknet/captcha', [DataSourceController::class, 'agmarknetCaptcha'])->name('admin.datasources.agmarknet.captcha');
        Route::post('/datasources/agmarknet/sync-historical', [DataSourceController::class, 'agmarknetHistoricalSync'])->name('admin.datasources.agmarknet.sync-historical');

        Route::resource('datasources', DataSourceController::class)->names('admin.datasources');

        // Master Data Deployment & APMC Discovery Hub
        Route::get('/deployment-hub', [\App\Http\Controllers\Admin\DeploymentHubController::class, 'index'])->name('admin.deployment-hub.index');
        Route::post('/deployment-hub/discover-mandis', [\App\Http\Controllers\Admin\DeploymentHubController::class, 'discoverMandis'])->name('admin.deployment-hub.discover-mandis');
        Route::post('/deployment-hub/discover-crops', [\App\Http\Controllers\Admin\DeploymentHubController::class, 'discoverCrops'])->name('admin.deployment-hub.discover-crops');
        Route::post('/deployment-hub/import-standard-master', [\App\Http\Controllers\Admin\DeploymentHubController::class, 'importStandardMaster'])->name('admin.deployment-hub.import-standard-master');
        Route::post('/deployment-hub/sync-prices', [\App\Http\Controllers\Admin\DeploymentHubController::class, 'syncLivePrices'])->name('admin.deployment-hub.sync-prices');

        // Market Prices
        Route::get('/prices', [MarketPriceController::class, 'index'])->name('admin.prices.index');
        Route::get('/prices/prune-preview', [MarketPriceController::class, 'prunePreview'])->name('admin.prices.prune-preview');
        Route::post('/prices/sync', [MarketPriceController::class, 'sync'])->name('admin.prices.sync');
        Route::post('/prices/sync-range', [MarketPriceController::class, 'syncRange'])->name('admin.prices.sync-range');
        Route::post('/prices/prune', [MarketPriceController::class, 'prune'])->name('admin.prices.prune');

        // Sync & API Health Logs
        Route::get('/sync-logs', [SyncLogController::class, 'index'])->name('admin.sync-logs.index');

        // Agricultural CMS & Knowledge Base
        Route::post('/schemes/settings', [\App\Http\Controllers\Admin\SchemeController::class, 'updateSettings'])->name('admin.schemes.settings');
        Route::post('/schemes/bulk', [\App\Http\Controllers\Admin\SchemeController::class, 'bulkAction'])->name('admin.schemes.bulk');
        Route::post('/schemes/{scheme}/toggle', [\App\Http\Controllers\Admin\SchemeController::class, 'toggle'])->name('admin.schemes.toggle');
        Route::post('/schemes/{scheme}/toggle-featured', [\App\Http\Controllers\Admin\SchemeController::class, 'toggleFeatured'])->name('admin.schemes.toggle-featured');
        Route::resource('schemes', \App\Http\Controllers\Admin\SchemeController::class)->names('admin.schemes');

        Route::post('/news/{news}/toggle', [\App\Http\Controllers\Admin\NewsController::class, 'toggle'])->name('admin.news.toggle');
        Route::resource('news', \App\Http\Controllers\Admin\NewsController::class)->names('admin.news');

        Route::post('/videos/fetch-metadata', [\App\Http\Controllers\Admin\VideoController::class, 'fetchMetadata'])->name('admin.videos.fetch-metadata');
        Route::post('/videos/settings', [\App\Http\Controllers\Admin\VideoController::class, 'updateSettings'])->name('admin.videos.settings');
        Route::post('/videos/bulk', [\App\Http\Controllers\Admin\VideoController::class, 'bulkAction'])->name('admin.videos.bulk');
        Route::post('/videos/{video}/toggle', [\App\Http\Controllers\Admin\VideoController::class, 'toggle'])->name('admin.videos.toggle');
        Route::post('/videos/{video}/toggle-featured', [\App\Http\Controllers\Admin\VideoController::class, 'toggleFeatured'])->name('admin.videos.toggle-featured');
        Route::get('/videos/taxonomies', [\App\Http\Controllers\Admin\VideoTaxonomyController::class, 'index'])->name('admin.videos.taxonomies.index');
        Route::post('/videos/taxonomies', [\App\Http\Controllers\Admin\VideoTaxonomyController::class, 'store'])->name('admin.videos.taxonomies.store');
        Route::put('/videos/taxonomies/{taxonomy}', [\App\Http\Controllers\Admin\VideoTaxonomyController::class, 'update'])->name('admin.videos.taxonomies.update');
        Route::delete('/videos/taxonomies/{taxonomy}', [\App\Http\Controllers\Admin\VideoTaxonomyController::class, 'destroy'])->name('admin.videos.taxonomies.destroy');
        Route::post('/videos/taxonomies/{taxonomy}/toggle', [\App\Http\Controllers\Admin\VideoTaxonomyController::class, 'toggle'])->name('admin.videos.taxonomies.toggle');
        Route::resource('videos', \App\Http\Controllers\Admin\VideoController::class)->names('admin.videos');

        Route::post('/articles/{article}/toggle', [\App\Http\Controllers\Admin\ArticleController::class, 'toggle'])->name('admin.articles.toggle');
        Route::resource('articles', \App\Http\Controllers\Admin\ArticleController::class)->names('admin.articles');

        // Navigation Bars & Menus CMS (Desktop, Mobile Dock, Hamburger Drawer)
        Route::get('/navbar', [\App\Http\Controllers\Admin\NavbarController::class, 'index'])->name('admin.navbar.index');
        Route::post('/navbar', [\App\Http\Controllers\Admin\NavbarController::class, 'update'])->name('admin.navbar.update');
        Route::post('/navbar/reset', [\App\Http\Controllers\Admin\NavbarController::class, 'resetDefaults'])->name('admin.navbar.reset');

        // Footer Layout & Content CMS
        Route::get('/footer', [\App\Http\Controllers\Admin\FooterController::class, 'index'])->name('admin.footer.index');
        Route::post('/footer', [\App\Http\Controllers\Admin\FooterController::class, 'update'])->name('admin.footer.update');

        // Farmer Helpdesk & Grievance CRM
        Route::post('/feedback/settings', [\App\Http\Controllers\Admin\FeedbackController::class, 'updateSettings'])->name('admin.feedback.settings.update');
        Route::post('/feedback/settings/reset', [\App\Http\Controllers\Admin\FeedbackController::class, 'resetSettings'])->name('admin.feedback.settings.reset');
        Route::resource('feedback', \App\Http\Controllers\Admin\FeedbackController::class)
            ->only(['index', 'show', 'update', 'destroy'])
            ->names('admin.feedback');

        // Feature Flags & System Settings (Super Admin Exclusive)
        Route::middleware(['role:super_admin'])->group(function () {
            Route::get('/feature-flags', [FeatureFlagController::class, 'index'])->name('admin.feature-flags.index');
            Route::post('/feature-flags/{featureFlag}/toggle', [FeatureFlagController::class, 'toggle'])->name('admin.feature-flags.toggle');
            Route::put('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'update'])->name('admin.feature-flags.update');

            Route::get('/settings', [SystemSettingController::class, 'index'])->name('admin.settings.index');
            Route::post('/settings', [SystemSettingController::class, 'update'])->name('admin.settings.update');
            Route::post('/settings/clear-cache', [SystemSettingController::class, 'clearCache'])->name('admin.settings.clear-cache');
            Route::post('/settings/optimize', [SystemSettingController::class, 'optimizeApp'])->name('admin.settings.optimize');
            Route::post('/settings/update-database', [SystemSettingController::class, 'updateDatabase'])->name('admin.settings.update-database');
            Route::get('/settings/update-database', [SystemSettingController::class, 'updateDatabase']);
            Route::post('/settings/prune-data', [SystemSettingController::class, 'pruneData'])->name('admin.settings.prune-data');
            Route::post('/settings/trigger-forecasting', [SystemSettingController::class, 'triggerForecasting'])->name('admin.settings.trigger-forecasting');
        });

        // Price Freshness & Staleness Rules Module (Async)
        Route::get('/price-freshness', [PriceFreshnessController::class, 'index'])->name('admin.price-freshness.index');
        Route::post('/price-freshness', [PriceFreshnessController::class, 'update'])->name('admin.price-freshness.update');
        Route::post('/price-freshness/simulate', [PriceFreshnessController::class, 'simulate'])->name('admin.price-freshness.simulate');
        Route::post('/price-freshness/reset', [PriceFreshnessController::class, 'reset'])->name('admin.price-freshness.reset');

        // Weather Storage & On-Demand Cache Engine
        Route::get('/weather', [\App\Http\Controllers\Admin\WeatherManagementController::class, 'index'])->name('admin.weather.index');
        Route::post('/weather/settings', [\App\Http\Controllers\Admin\WeatherManagementController::class, 'updateSettings'])->name('admin.weather.settings');
        Route::post('/weather/prune', [\App\Http\Controllers\Admin\WeatherManagementController::class, 'prune'])->name('admin.weather.prune');
        Route::post('/weather/sync-district/{district}', [\App\Http\Controllers\Admin\WeatherManagementController::class, 'syncDistrict'])->name('admin.weather.sync-district');
        Route::post('/weather/sync-all', [\App\Http\Controllers\Admin\WeatherManagementController::class, 'syncAll'])->name('admin.weather.sync-all');

        // Data Quality & Rejected Records
        Route::get('/data-quality', [DataQualityController::class, 'index'])->name('admin.data-quality.index');
        Route::post('/data-quality/reprocess/{rawRecord}', [DataQualityController::class, 'reprocess'])->name('admin.data-quality.reprocess');
        Route::post('/data-quality/reprocess-all', [DataQualityController::class, 'reprocessAll'])->name('admin.data-quality.reprocess-all');
        Route::delete('/data-quality/{rawRecord}', [DataQualityController::class, 'destroy'])->name('admin.data-quality.destroy');

        // Unresolved Mappings
        Route::get('/unresolved-mappings', [UnresolvedMappingController::class, 'index'])->name('admin.unresolved-mappings.index');
        Route::post('/unresolved-mappings/resolve-crop', [UnresolvedMappingController::class, 'resolveCrop'])->name('admin.unresolved-mappings.resolve-crop');
        Route::post('/unresolved-mappings/resolve-market', [UnresolvedMappingController::class, 'resolveMarket'])->name('admin.unresolved-mappings.resolve-market');

        // Admin Notes & System Guidelines
        Route::get('/notes', [\App\Http\Controllers\Admin\NoteController::class, 'index'])->name('admin.notes.index');
        Route::post('/notes', [\App\Http\Controllers\Admin\NoteController::class, 'update'])->name('admin.notes.update');

        // PWA Push Notifications & Farmer Communications Hub
        Route::get('/notifications', [\App\Http\Controllers\Admin\PwaNotificationController::class, 'index'])->name('admin.notifications.index');
        Route::post('/notifications/broadcast', [\App\Http\Controllers\Admin\PwaNotificationController::class, 'sendBroadcast'])->name('admin.notifications.broadcast');
        Route::post('/notifications/test', [\App\Http\Controllers\Admin\PwaNotificationController::class, 'sendTestNotification'])->name('admin.notifications.test');
        Route::post('/notifications/settings', [\App\Http\Controllers\Admin\PwaNotificationController::class, 'updateSettings'])->name('admin.notifications.settings');
        Route::delete('/notifications/{broadcast}', [\App\Http\Controllers\Admin\PwaNotificationController::class, 'destroy'])->name('admin.notifications.destroy');

        // Audit Trail Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('admin.audit-logs.show');

        // Admin Profile & Security Settings
        Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');

        // Staff & Role Management (Super Admin Exclusive)
        Route::middleware(['role:super_admin'])->group(function () {
            Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('admin.users.toggle');
            Route::resource('users', UserController::class)->names('admin.users');
        });
    });
});
