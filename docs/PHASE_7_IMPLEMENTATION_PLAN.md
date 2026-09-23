# Phase 7 Implementation Plan: Weather Integration (Open-Meteo Hyperlocal Forecast & Agricultural Advisories)

## Goal Description
Implement **Phase 7: Weather Integration** for Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ). This integrates the Open-Meteo weather API to deliver 7-day hyperlocal weather forecasts (temperature, precipitation probability, humidity, wind speed) and automated agricultural advisories in Kannada & English for all Karnataka districts, backed by non-blocking scheduled database synchronization and instant sub-30ms farmer page rendering.

---

## User Review Required

> [!IMPORTANT]
> **Non-Blocking Architecture**: In accordance with the system specification, weather forecasts will never be fetched synchronously during farmer page loads. Forecasts are stored in the database (`weather_forecasts` table) via scheduled background sync (`krushi:sync-weather`) and cached, ensuring sub-50ms page load times on low-bandwidth rural networks.

> [!NOTE]
> **Kannada Agricultural Advisories**: WMO weather codes and rainfall probabilities are dynamically mapped into practical Kannada farming advisories (e.g., advising on pesticide spraying windows, irrigation requirements, drainage readiness, and harvest timings).

---

## Proposed Changes

### 1. Database Schema & Models

#### [NEW] `database/migrations/2026_09_23_000001_create_weather_forecasts_table.php`
- Columns:
  - `id`
  - `district_id` (foreign key to `districts`, cascade delete, indexed)
  - `forecast_date` (date, indexed)
  - `current_temperature` (float, nullable)
  - `current_humidity` (float, nullable)
  - `current_wind_speed` (float, nullable)
  - `temp_min` (float)
  - `temp_max` (float)
  - `precipitation_probability` (float)
  - `weather_code` (integer)
  - `weather_condition_en` (string)
  - `weather_condition_kn` (string)
  - `weather_icon` (string)
  - `farming_advisory_en` (text)
  - `farming_advisory_kn` (text)
  - `raw_payload` (json, nullable)
  - `fetched_at` (timestamp)
  - `timestamps`
  - Unique index on `['district_id', 'forecast_date']`

#### [NEW] `app/Models/WeatherForecast.php`
- Relationship: `belongsTo(District::class)`.
- Scopes: `forDistrict()`, `upcoming()`, `today()`.
- Accessors for formatted dates and Kannada day of the week.

---

### 2. Weather Services Layer

#### [NEW] `app/Services/Weather/Contracts/WeatherProviderInterface.php`
- Interface defining:
  - `fetchForecast(float $latitude, float $longitude): array`

#### [NEW] `app/Services/Weather/OpenMeteoWeatherProvider.php`
- Implements `WeatherProviderInterface`.
- Fetches 7-day forecast from `https://api.open-meteo.com/v1/forecast`.
- Converts WMO weather codes (0–99) to Kannada/English conditions and icons.
- Generates dynamic, practical agricultural advisories based on rainfall probability, temperature, and wind.

#### [NEW] `app/Services/Weather/WeatherSyncService.php`
- Syncs 7-day forecast for a single district or all Karnataka districts.
- Deduplicates and upserts into `weather_forecasts` table.

#### [NEW] `app/Console/Commands/SyncWeatherCommand.php`
- Signature: `krushi:sync-weather {district? : Specific district ID or code} {--force : Force sync even if synced recently}`.
- Registered in `routes/console.php` with twice-daily schedule (6 AM and 3 PM).

---

### 3. Controllers & Routes

#### [NEW] `app/Http/Controllers/Farmer/WeatherController.php`
- Route: `GET /weather` (named `farmer.weather.index`).
- Displays today's current weather card, farming advisory banner, 7-day forecast cards, and district switcher.

#### [NEW] `app/Http/Controllers/Api/V1/WeatherApiController.php`
- Route: `GET /api/v1/weather/forecast`
- Accepts `district_id` or `latitude`/`longitude`.
- Returns structured JSON forecast with agricultural advisory.

#### [MODIFY] `routes/web.php`
- Register `Route::get('/weather', [WeatherController::class, 'index'])->name('farmer.weather.index');`

#### [MODIFY] `routes/api.php`
- Register `Route::get('/weather/forecast', [WeatherApiController::class, 'forecast'])->name('api.v1.weather.forecast');`

---

### 4. Farmer UI Integration

#### [NEW] `resources/views/farmer/weather/index.blade.php`
- Mobile-first, responsive Tailwind CSS interface:
  - **Current Weather Hero**: Large temp display, weather icon, humidity, wind speed, precipitation probability.
  - **Prominent Kannada Farming Advisory Box**: High-visibility agricultural recommendation (spraying, harvesting, irrigation).
  - **7-Day Forecast Grid**: Daily cards with date, Kannada day name (ಸೋಮವಾರ, ಮಂಗಳವಾರ, etc.), temperature range, rain probability meter, and condition.
  - **District Switcher Tabs**: Easy horizontal scrolling selector for Karnataka districts.
  - **1-Tap WhatsApp Share**: Share daily weather and farming advisory.

#### [MODIFY] `resources/views/farmer/home.blade.php`
- Embed a sleek **Weather & Advisory Bar** right under the hero district card showing today's temperature, rain forecast, and agricultural advisory with link to `/weather`.

#### [MODIFY] `resources/views/layouts/farmer.blade.php`
- Wire the mobile thumb-zone bottom navigation `Weather` tab to `route('farmer.weather.index')`.

---

### 5. Automated Testing

#### [NEW] `tests/Unit/OpenMeteoWeatherProviderTest.php`
- Tests WMO code translation to Kannada/English.
- Tests dynamic agricultural advisory generation for different weather conditions.
- Tests response parsing using `Http::fake()`.

#### [NEW] `tests/Unit/WeatherSyncServiceTest.php`
- Tests database upserts in `weather_forecasts`.

#### [NEW] `tests/Feature/FarmerWeatherTest.php`
- Tests `/weather` page rendering and district switching.
- Tests `/api/v1/weather/forecast` JSON response.
- Tests `krushi:sync-weather` console command.

---

## Verification Plan

### Automated Tests
- Run `& "C:\Program Files\php-8.2.30\php.exe" artisan test` (must achieve 100% green pass across all tests).
- Run `npm run build` to verify frontend assets.

### Manual Verification
- Verify `/weather` renders correctly with 7-day forecast cards and Kannada advisories.
- Verify home page weather summary widget.
- Verify WhatsApp sharing formats weather details cleanly.
