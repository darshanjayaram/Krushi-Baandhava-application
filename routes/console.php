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

Schedule::command('krushi:sync-market-prices')
    ->dailyAt('06:00')
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sync_morning.log'));

Schedule::command('krushi:sync-market-prices')
    ->dailyAt('18:00')
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sync_evening.log'));

Schedule::command('krushi:sync-market-prices')
    ->hourly()
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sync_periodic.log'));

/*
|--------------------------------------------------------------------------
| Weather Scheduling (Open-Meteo 7-Day Hyperlocal & Agricultural Advisory)
|--------------------------------------------------------------------------
*/
Schedule::command('krushi:sync-weather')
    ->dailyAt('05:30')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/weather_morning.log'));

Schedule::command('krushi:sync-weather')
    ->dailyAt('14:30')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/weather_afternoon.log'));

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
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/prune_nightly.log'));



