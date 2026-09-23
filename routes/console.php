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
|
| Morning sync: 06:00 IST (captures early morning arrivals & opening rates)
| Evening sync: 18:00 IST (captures final closing modal prices & day aggregates)
|
*/
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


