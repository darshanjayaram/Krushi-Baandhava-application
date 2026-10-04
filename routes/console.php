<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Console Scheduling (Krushi Baandhava APMC / data.gov.in Ingestion)
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| Scheduler Live Heartbeat (for cPanel Health Monitor in Admin Panel)
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::forever('scheduler_last_heartbeat', now());
})->everyMinute();

// Admin-configured dynamic cron schedule (Admin > Data Sources > Configure Cron Timings)
$morningTime = \App\Models\SystemSetting::get('cron_market_morning_time', '06:00');
$eveningTime = \App\Models\SystemSetting::get('cron_market_evening_time', '19:30');
$afternoonTime = \App\Models\SystemSetting::get('cron_market_afternoon_time', '12:30');
$enableHourly = \App\Models\SystemSetting::get('cron_market_enable_hourly', true);

if (!empty($morningTime)) {
    Schedule::command('krushi:sync-market-prices --cron-only')
        ->dailyAt($morningTime)
        ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_mandi_prices', true))
        ->withoutOverlapping(60)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/sync_morning.log'));
}

if (!empty($eveningTime)) {
    Schedule::command('krushi:sync-market-prices --cron-only')
        ->dailyAt($eveningTime)
        ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_mandi_prices', true))
        ->withoutOverlapping(60)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/sync_evening.log'));
}

if (!empty($afternoonTime)) {
    Schedule::command('krushi:sync-market-prices --cron-only')
        ->dailyAt($afternoonTime)
        ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_mandi_prices', true))
        ->withoutOverlapping(60)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/sync_afternoon.log'));
}

if ($enableHourly) {
    Schedule::command('krushi:sync-market-prices --cron-only')
        ->hourly()
        ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_mandi_prices', true))
        ->withoutOverlapping(60)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/sync_periodic.log'));
}

// Every 15 minutes, check any custom data source schedules using isDue()
Schedule::command('krushi:sync-market-prices --cron-only')
    ->everyFifteenMinutes()
    ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_mandi_prices', true))
    ->withoutOverlapping(15)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sync_check.log'));

/*
|--------------------------------------------------------------------------
| Weather Scheduling (Open-Meteo On-Demand Sync)
|--------------------------------------------------------------------------
| Statewide batch sync across all 31 districts has been retired in favor of
| on-demand 15-minute TTL caching based on active farmer location coordinates.
| Scheduled commands can still be run manually if needed: `php artisan krushi:sync-weather`
*/
// Schedule::command('krushi:sync-weather')->dailyAt('05:30');
// Schedule::command('krushi:sync-weather')->dailyAt('14:30');

// Nightly automatic cleanup of expired weather forecasts (keeps records from piling up)
Schedule::call(function () {
    \App\Services\Weather\WeatherSyncService::pruneForecasts(7);
})->dailyAt('03:30')
  ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_weather_sync', true))
  ->name('prune-weather-forecasts');

/*
|--------------------------------------------------------------------------
| Historical Price Statistics & Seasonal Index Calculation
|--------------------------------------------------------------------------
|
| Nightly aggregation: 01:00 IST (aggregates past day prices & updates monthly seasonality)
|
*/
Schedule::command('krushi:compute-statistics --months')
    ->dailyAt('01:00')
    ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_analytics_stats', true))
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/analytics_nightly.log'));

/*
|--------------------------------------------------------------------------
| Price Forecasting Engine (Holt's Linear & Seasonal Projections)
|--------------------------------------------------------------------------
|
| Nightly forecast generation: 02:00 IST (computes 1D, 7D, 15D, 30D projections and bounds)
|
*/
Schedule::command('krushi:generate-forecasts')
    ->dailyAt('02:00')
    ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_forecasting', true))
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/forecast_nightly.log'));

/*
|--------------------------------------------------------------------------
| Automated 1-Year Rolling Retention Pruning (Keeps Database at ~35 MB)
|--------------------------------------------------------------------------
|
| Nightly cleanup: 23:00 IST (pre-aggregates monthly stats, prunes daily records > 365d)
|
*/
Schedule::command('krushi:prune-prices --days=365')
    ->dailyAt('23:00')
    ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_retention_pruning', true))
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/prune_nightly.log'));

/*
|--------------------------------------------------------------------------
| Automated Daily Data Integrity Auditor (KRAMA 1:1 Fidelity & Zero Ghost Mandis)
|--------------------------------------------------------------------------
|
| Nightly audit & auto-reconciliation: 20:30 IST (after evening KRAMA sync)
|
*/
Schedule::command('data:audit-integrity --days=3 --fix')
    ->dailyAt('20:30')
    ->when(fn () => (bool) \App\Models\SystemSetting::get('cron_task_data_integrity', true))
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/audit_integrity.log'));
