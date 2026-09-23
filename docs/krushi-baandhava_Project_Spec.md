# Krushi Baandhava PWA — Project Specification & Antigravity Build Prompt

## 1. Objective

Build a production-ready, mobile-first Progressive Web App (PWA) for
farmers in Karnataka, India.

Core capabilities:

- Current agricultural market prices
- Commodity and variety prices
- Nearby market discovery
- Historical price trends
- Price forecasting
- Best months to sell analysis
- Where-to-sell market comparison
- Weather
- Agricultural information
- Government schemes
- Agriculture news and videos
- Admin-controlled data sources, APIs, mappings and schedules
- Data-quality monitoring
- Forecast-model monitoring
- English/Kannada-ready architecture
- PWA installation and low-bandwidth-friendly UX

The product may use Negilu Krushi as a functional reference, but must be
independently implemented. Do not copy its code, branding, assets,
proprietary implementation, or undisclosed forecasting algorithm.

------------------------------------------------------------------------

## 2. Hosting Constraint: cPanel First

The first production deployment must work on normal Linux cPanel shared
hosting.

### Required V1 stack

- Laravel
- PHP
- MySQL/MariaDB
- Blade
- Livewire
- Alpine.js
- Tailwind CSS
- Vite
- Laravel Scheduler
- Laravel Database Queue
- cPanel Cron
- File/database cache
- PWA service worker

### Not mandatory in V1

- Redis
- Horizon
- Supervisor
- PostgreSQL/PostGIS
- Docker
- Kubernetes
- Elasticsearch
- Python microservices
- Kafka
- Persistent Node.js server

The architecture must allow these to be added later.

Laravel 13 requires PHP 8.3+. If the cPanel server does not support PHP
8.3, stop and report the environment before selecting the Laravel
version.

------------------------------------------------------------------------

## 3. Architecture

Use a modular monolith.

``` text
Farmer PWA
    |
    v
Laravel Web/API Layer
    |
    +-- Market Intelligence
    +-- Forecast Engine
    +-- Location
    +-- Weather
    +-- Agriculture CMS
    +-- Schemes
    +-- News
    +-- Videos
    +-- Admin
    |
    v
MySQL/MariaDB
    |
    +-- Raw Source Data
    +-- Normalized Market Data
    +-- Historical Statistics
    +-- Forecasts
    +-- Weather
    +-- CMS
    |
    v
Laravel Scheduler + Database Queue
    |
    +-- data.gov.in
    +-- Agmarknet
    +-- KRAMA adapter
    +-- Coffee Board adapter
    +-- Coconut Board adapter
    +-- Open-Meteo
    +-- Future IMD adapter
```

### Critical rule

Normal user page requests must NOT depend on live external API calls.

Use:

``` text
External Source
 -> Scheduled Import
 -> Raw Data
 -> Normalization
 -> Canonical Database
 -> Statistics/Forecast
 -> Cache
 -> PWA
```

------------------------------------------------------------------------

# 4. Main Modules

Create:

1.  Farmer PWA
2.  Location
3.  Market Prices
4.  Historical Analytics
5.  Forecasting
6.  Best Months to Sell
7.  Where to Sell
8.  Weather
9.  Agriculture Information
10. Government Schemes
11. News
12. Videos
13. Search
14. Admin Panel
15. Data Source Management
16. API Field Mapping
17. Scheduler/Sync Management
18. Feature Flags
19. Roles/Permissions
20. Audit Logs

------------------------------------------------------------------------

# 5. Farmer PWA

Pages:

- Home
- Location selection
- Crops
- Crop detail
- Market detail
- Nearby markets
- Price history
- Forecast
- Best months to sell
- Where to sell
- Weather
- Agriculture information
- Schemes
- News
- Videos
- Search
- About
- Privacy
- Terms
- Data Sources
- Feedback

Use a clean, simple, farmer-friendly mobile-first UI.

Target 360px+ screens.

------------------------------------------------------------------------

# 6. Home Page

Show:

- Current location/district
- Today’s important market prices
- Nearby markets
- Weather
- Forecast highlights
- Best-months insight
- Agricultural information
- Schemes/news/video highlights

Example price card:

``` text
Arecanut
Shivamogga Market

Modal Price
₹XX,XXX / Quintal

Min ₹XX,XXX
Max ₹XX,XXX

Updated: Today
Source: Government feed
```

Every market value must expose freshness/date information. Never make
stale data look current.

------------------------------------------------------------------------

# 7. Location

Use browser Geolocation API.

Flow:

``` text
Browser GPS
 -> latitude/longitude
 -> Laravel
 -> reverse geocoder
 -> locality/taluk/district/state
 -> nearby markets
```

If GPS is denied:

``` text
State
 -> District
 -> Taluk
```

## Nominatim

Use a `GeocoderInterface` so the provider can be replaced later.

Nominatim reverse API:

`https://nominatim.org/release-docs/develop/api/Reverse/`

Public Nominatim usage policy must be respected:

`https://operations.osmfoundation.org/policies/nominatim/`

Do not call public Nominatim on every page request. Cache
reverse-geocoding results and identify the application with an
appropriate User-Agent.

Do not permanently store precise GPS unless there is a documented
requirement and appropriate privacy handling.

------------------------------------------------------------------------

# 8. Market Data

Canonical fields:

``` text
crop_id
variety_id
market_id
price_date
min_price
max_price
modal_price
arrival_quantity
unit
source_id
source_record_id
raw_record_id
created_at
updated_at
```

Support:

- commodity
- variety
- market
- district
- state
- min price
- max price
- modal price
- arrival quantity where available
- arrival/price date
- unit
- source provenance

------------------------------------------------------------------------

# 9. Initial Data Sources

## 9.1 data.gov.in

Use the official Government Open Data platform as the primary MVP
market-price source where the required Karnataka data is available.

Dataset:

`https://data.gov.in/resource/current-daily-price-various-commodities-various-markets-mandi`

Expected fields include commodity, variety, market, district, state,
minimum price, maximum price, modal price and date/arrival fields.

Requirements:

- credentials must remain server-side
- never expose API keys to JavaScript
- never commit credentials
- use `.env` or encrypted admin credentials
- rotate any credential previously exposed in chat before production

Do not put any actual API key in this project file.

------------------------------------------------------------------------

## 9.2 Agmarknet

Create an `AgmarknetSource` adapter.

Priority:

1.  Official documented API/feed
2.  Permitted structured endpoint
3.  Official downloadable data
4.  Other permitted structured source

Do not depend on undocumented endpoints without verification. Avoid
blind HTML scraping.

Keep all Agmarknet-specific logic inside the adapter.

------------------------------------------------------------------------

## 9.3 KRAMA

Create a `KramaSource` adapter placeholder.

Do not assume KRAMA has a direct public developer API.

Before production:

- verify official access method
- verify terms
- verify update frequency
- verify fields
- determine whether equivalent official data is already available
  through data.gov.in

If data.gov.in already provides the required official dataset, avoid
unnecessary duplicate ingestion.

------------------------------------------------------------------------

## 9.4 Coffee Board

Create `CoffeeBoardSource`.

Support:

- Arabica Cherry
- Arabica Parchment
- Robusta Cherry
- Robusta Parchment

Official site:

`https://coffeeboard.gov.in/`

Do not assume a PDF/website page is an API. Keep ingestion behind an
adapter so the source mechanism can change.

------------------------------------------------------------------------

## 9.5 Coconut Development Board

Create `CoconutBoardSource`.

Support:

- Coconut
- Copra
- Coconut oil/reference price where applicable

Official site:

`https://coconutboard.gov.in/`

Verify the current official structured/report access mechanism before
enabling automated production ingestion.

------------------------------------------------------------------------

# 10. Source Adapter Architecture

Create:

``` text
app/Services/DataSources/
    Contracts/
        MarketDataProviderInterface.php
    DataGov/
        DataGovMarketDataProvider.php
    Agmarknet/
        AgmarknetMarketDataProvider.php
    Krama/
        KramaMarketDataProvider.php
    CoffeeBoard/
        CoffeeBoardDataProvider.php
    CoconutBoard/
        CoconutBoardDataProvider.php
```

Interface:

``` php
interface MarketDataProviderInterface
{
    public function fetch(array $filters = []): iterable;

    public function normalize(array $record): ?array;

    public function healthCheck(): array;
}
```

The rest of the application must not depend on source-specific field
names.

------------------------------------------------------------------------

# 11. Normalization Pipeline

``` text
FETCH
  -> RAW RECORD
  -> VALIDATE
  -> MAP
  -> NORMALIZE
  -> RESOLVE CROP
  -> RESOLVE VARIETY
  -> RESOLVE MARKET
  -> DEDUPLICATE
  -> SAVE CANONICAL RECORD
  -> UPDATE STATISTICS
```

Rejected records must be logged with the reason.

------------------------------------------------------------------------

# 12. Data Mapping

Admin must be able to map external fields to internal fields.

Example:

``` text
commodity       -> crop
variety         -> variety
market          -> market
district        -> district
state           -> state
min_price       -> min_price
max_price       -> max_price
modal_price     -> modal_price
arrival_date    -> price_date
arrival_quantity -> arrival_quantity
```

Use declarative/safe mappings. Do not allow arbitrary code execution
from the Admin Panel.

------------------------------------------------------------------------

# 13. Crop and Market Mapping

Create source mappings for:

``` text
crop_source_mappings
market_source_mappings
```

Support aliases and spelling differences.

Do not silently create incorrect crops or markets from source names.

Admin should be able to review unresolved mappings.

------------------------------------------------------------------------

# 14. Data Quality

Validate:

``` text
price >= 0
min_price <= modal_price
modal_price <= max_price
valid date
valid crop
valid market
```

Bad records:

``` text
reject
log
show in Admin
```

Never silently insert invalid data.

------------------------------------------------------------------------

# 15. Deduplication

Use a canonical unique key based on source + crop + variety + market +
date + source record identifier where appropriate.

The same source record imported twice must not create duplicate market
prices.

Keep raw source data for traceability and reprocessing.

------------------------------------------------------------------------

# 16. Raw Data

Create:

``` text
market_price_raw
```

Fields:

``` text
id
source_id
external_record_id
payload JSON
checksum
received_at
processed_at
processing_status
error_message
```

Reasons:

- auditability
- debugging
- reprocessing
- source-schema changes
- traceability

------------------------------------------------------------------------

# 17. Historical Analytics

Support:

- daily
- weekly
- monthly
- yearly

Show:

- modal price
- min/max
- average
- arrival quantity where available

Filters:

``` text
Crop
Variety
Market
Date Range
```

Use indexed queries and precomputed aggregates where practical.

------------------------------------------------------------------------

# 18. Price Forecasting

Important: the exact forecasting algorithm used by another site is not
publicly documented. Do not claim to reproduce it.

Build an independent, transparent forecasting engine.

Horizons:

``` text
Tomorrow
Next 7 Days
Next 15 Days
Next 30 Days
```

Forecast response:

``` text
expected_price
lower_bound
upper_bound
change_percent
reliability
generated_at
model_version
data_points_used
```

If data is insufficient, return:

``` text
Insufficient historical data for a reliable estimate.
```

Do not manufacture predictions.

------------------------------------------------------------------------

# 19. Forecast Architecture

Interface:

``` php
interface ForecastModelInterface
{
    public function train(array $dataset): ForecastModelResult;

    public function predict(
        array $historicalData,
        ForecastContext $context
    ): ForecastResult;
}
```

V1:

- historical baseline
- moving averages
- exponential smoothing
- seasonal index
- recent trend
- historical volatility/error for ranges

V2:

- SARIMA where appropriate

V3:

- optional Python service using models such as XGBoost/LightGBM if
  backtesting justifies it

Do not introduce Python in V1 unless required.

------------------------------------------------------------------------

# 20. Forecast Features

Potential features:

``` text
lag_1
lag_3
lag_7
lag_14
lag_30
rolling_mean_7
rolling_mean_14
rolling_mean_30
rolling_std_7
rolling_std_30
month
week_of_year
seasonal_index
market
crop
variety
arrival_quantity
```

Do not automatically use weather as a model input. Validate its
predictive value first.

------------------------------------------------------------------------

# 21. Forecast Validation

Implement rolling historical backtesting.

Metrics:

``` text
MAE
RMSE
MAPE
Directional Accuracy
Prediction Interval Coverage
```

Store:

``` text
model_version
crop_id
market_id
variety_id
horizon
training_period
test_period
metric_name
metric_value
```

Model selection must be based on validation results, not model
complexity.

------------------------------------------------------------------------

# 22. Best Months to Sell

Default analysis window:

``` text
Previous 5 years
```

Process:

``` text
Historical prices
 -> monthly grouping
 -> seasonal/trend analysis
 -> monthly index
 -> chart
```

Display historical evidence such as:

``` text
Historical average
Historical range
Seasonal index
Number of observations
```

Avoid language that makes historical patterns sound guaranteed.

------------------------------------------------------------------------

# 23. Where to Sell

Given user location:

``` text
location
 -> nearby active markets
 -> current crop/variety prices
 -> distance
 -> freshness
```

Display:

``` text
Market
Current Price
Distance
Updated
Variety
```

Filters:

- Highest current price
- Nearest
- Recently updated
- Variety

Do not create an opaque “best market” score.

Later optionally add:

- estimated transport cost
- market fees
- estimated net realization

If these are added, display the inputs used in the calculation.

------------------------------------------------------------------------

# 24. Weather

## V1: Open-Meteo

`https://open-meteo.com/`

Create:

``` php
interface WeatherProviderInterface
{
    public function getCurrentWeather(float $lat, float $lon): array;

    public function getForecast(float $lat, float $lon, int $days): array;
}
```

Cache/schedule weather data. Do not call weather APIs on every user page
load.

## V2: IMD

Keep an IMD adapter possible without changing the weather module.

------------------------------------------------------------------------

# 25. Nearby Market Distance

Use MySQL in V1.

Approach:

1.  bounding-box filter
2.  Haversine calculation
3.  distance sorting

No PostGIS dependency.

------------------------------------------------------------------------

# 26. Agriculture CMS

Fields:

``` text
title
slug
summary
content
featured_image
category
crop
language
status
published_at
seo_title
seo_description
```

Categories:

- cultivation
- pests
- diseases
- fertilizer
- irrigation
- harvesting
- storage
- post-harvest
- market information

------------------------------------------------------------------------

# 27. Government Schemes

Fields:

``` text
scheme_name
description
eligibility
benefits
documents_required
how_to_apply
official_url
state
district_applicability
start_date
end_date
status
```

Use verified official information.

------------------------------------------------------------------------

# 28. News

Fields:

``` text
title
slug
summary
content
image
category
source
source_url
published_at
language
status
```

------------------------------------------------------------------------

# 29. Videos

Support YouTube links.

Fields:

``` text
title
thumbnail
description
youtube_url
crop/category
language
status
published_at
```

Do not re-host copyrighted videos unless permitted.

------------------------------------------------------------------------

# 30. Admin Panel

Admin dashboard must show:

``` text
Data sources
Last successful sync
Failed syncs
Today's market records
Markets
Crops
Forecast runs
Forecast failures
Weather sync
Queue
Failed jobs
API errors
Data quality issues
```

------------------------------------------------------------------------

# 31. Admin Data Source Management

Fields:

``` text
name
code
provider
type
base_url
endpoint
authentication_type
api_key
client_id
client_secret
headers
timeout
rate_limit
sync_frequency
active
last_sync
next_sync
```

Secrets must be encrypted.

After saving, never show full credentials.

Never log credentials.

------------------------------------------------------------------------

# 32. Admin API Test

Button:

``` text
Test Connection
```

Display:

``` text
HTTP Status
Response Time
Authentication Result
Records Found
Detected Fields
Mapping Validation
Sample Record
```

Never expose credentials.

------------------------------------------------------------------------

# 33. Sync Management

Actions:

``` text
Run Now
Pause
Resume
Retry Failed
View Logs
View Imported Records
View Rejected Records
```

Sync log:

``` text
source
started_at
completed_at
duration
records_received
records_inserted
records_updated
records_duplicate
records_rejected
error_count
status
error_message
```

------------------------------------------------------------------------

# 34. Scheduler

Use Laravel Scheduler.

Schedule jobs such as:

``` text
market import
data normalization
daily statistics
forecast generation
weather sync
seasonality analysis
cleanup
health checks
```

cPanel should run:

``` bash
php artisan schedule:run
```

through Cron.

The exact PHP binary/path must be verified on the hosting account.

Do not assume `/usr/bin/php`.

------------------------------------------------------------------------

# 35. Queue

V1:

``` env
QUEUE_CONNECTION=database
```

Jobs:

``` text
ImportMarketDataJob
NormalizeMarketDataJob
CalculateDailyStatisticsJob
GenerateForecastJob
GenerateSeasonalAnalysisJob
SyncWeatherJob
SendNotificationJob
```

Use short-lived queue workers compatible with shared hosting.

Do not require Supervisor/Horizon in V1.

------------------------------------------------------------------------

# 36. Database Schema

Create migrations for:

``` text
users

states
districts
taluks
localities
markets

crop_categories
crops
crop_varieties

data_sources
data_source_credentials
data_source_mappings

market_price_raw
market_prices
market_arrivals

price_daily_statistics
price_weekly_statistics
price_monthly_statistics

forecast_models
forecast_runs
price_forecasts
forecast_metrics

weather_locations
weather_forecasts
weather_alerts

agriculture_categories
agriculture_articles

schemes
scheme_documents

news_categories
news_articles

videos

feature_flags
system_settings

sync_jobs
sync_logs
api_health_logs

roles
permissions
audit_logs

feedback
notifications
```

Add foreign keys and indexes.

Important indexes:

``` text
crop_id
variety_id
market_id
district_id
price_date
source_id
external_id
```

Use composite indexes for high-volume price queries.

------------------------------------------------------------------------

# 37. API

Version public APIs:

``` text
GET /api/v1/crops
GET /api/v1/crops/{crop}
GET /api/v1/crops/{crop}/prices
GET /api/v1/markets
GET /api/v1/markets/{market}
GET /api/v1/markets/nearby
GET /api/v1/prices
GET /api/v1/prices/history
GET /api/v1/forecasts
GET /api/v1/seasonality
GET /api/v1/weather
GET /api/v1/articles
GET /api/v1/schemes
GET /api/v1/news
GET /api/v1/videos
```

Use Laravel API Resources.

Public endpoints must be rate-limited and validated.

------------------------------------------------------------------------

# 38. Security

Implement:

- HTTPS
- CSRF
- secure cookies
- authentication
- authorization
- policies/gates
- validation
- SQL injection prevention through Eloquent/query builder
- rate limiting
- secure password hashing
- encrypted credentials
- secure uploads
- MIME validation
- upload size limits
- audit logging
- production debug disabled

Never expose:

``` text
.env
API keys
passwords
tokens
client secrets
storage/logs
database files
admin-only endpoints
```

------------------------------------------------------------------------

# 39. Admin Roles

Create:

### Super Admin

Everything.

### Data Admin

Data sources, mappings, imports, crops, markets.

### Forecast Admin

Forecast configuration, runs, metrics.

### Content Admin

Articles, schemes, news, videos.

### Support Admin

Feedback/support.

Use Laravel authorization policies.

------------------------------------------------------------------------

# 40. Feature Flags

Create:

``` text
market_prices
price_forecast
best_months
where_to_sell
weather
agriculture_information
schemes
news
videos
notifications
whatsapp
multi_language
```

Each should be enabled/disabled from Admin.

------------------------------------------------------------------------

# 41. System Settings

Admin-managed:

``` text
application_name
default_state
default_district
default_language
forecast_minimum_observations
forecast_horizons
seasonality_years
weather_sync_interval
market_sync_interval
cache_duration
pagination_limit
maintenance_mode
```

Do not store secrets in generic settings.

------------------------------------------------------------------------

# 42. PWA

Implement:

``` text
manifest.json
service worker
icons
installability
offline shell
responsive layout
```

Cache safe public assets/data.

Never cache sensitive Admin data publicly.

Show last-updated timestamps for cached market information.

------------------------------------------------------------------------

# 43. Internationalization

Prepare from the beginning for:

``` text
English
Kannada
```

Use Laravel language files:

``` text
lang/en/
lang/kn/
```

Do not hard-code all UI text.

CMS records should have language support.

------------------------------------------------------------------------

# 44. SEO

Implement:

``` text
SEO title
SEO description
canonical URL
Open Graph
sitemap.xml
robots.txt
structured data where appropriate
```

Example URLs:

``` text
/crops/arecanut
/crops/arecanut/shivamogga
/markets/shivamogga
/articles/{slug}
/schemes/{slug}
```

------------------------------------------------------------------------

# 45. Performance

Design for 10,000+ registered users, but do not promise 10,000
concurrent users on shared cPanel.

Requirements:

- no external API calls during normal page rendering
- no N+1 queries
- indexed database
- eager loading
- pagination
- caching
- precomputed analytics
- queued long-running tasks
- compressed images
- minimal JavaScript
- low-bandwidth-friendly pages

The code must be migration-ready to a VPS.

------------------------------------------------------------------------

# 46. cPanel Deployment

Preferred conceptual layout:

``` text
/home/USERNAME/agrimarket/
    app/
    bootstrap/
    config/
    database/
    resources/
    routes/
    storage/
    vendor/

public_html/
    index.php
    .htaccess
    build/
    manifest.json
    icons/
```

Only Laravel’s public directory should be web-accessible.

Never expose the project root.

------------------------------------------------------------------------

# 47. Environment

Example:

``` env
APP_NAME="Krushi Baandhava"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=file
```

External credentials belong in secure server-side configuration or
encrypted admin credentials.

Create `.env.example` with blank secrets.

Never commit `.env`.

------------------------------------------------------------------------

# 48. Testing

Create:

## Unit tests

- normalization
- mapping
- deduplication
- distance calculation
- seasonality
- forecast calculations

## Feature tests

- API
- authentication
- permissions
- admin CRUD
- sync
- PWA routes

## Integration tests

- data.gov fixture
- weather fixture
- geocoder fixture

Never call production external APIs during automated tests.

------------------------------------------------------------------------

# 49. Seeders

Seed:

``` text
Karnataka
districts
sample taluks
sample markets
sample crops
sample varieties
roles
permissions
feature flags
demo data sources
```

Do not seed API credentials.

Create enough demo records for Admin UI testing.

------------------------------------------------------------------------

# 50. Mock Mode

Support development mock mode:

``` env
DATA_SOURCE_MOCK_MODE=true
```

Mocks should simulate:

- success
- empty result
- malformed data
- timeout
- rate limit
- server error

------------------------------------------------------------------------

# 51. Project Directory

``` text
app/
├── Actions/
├── Console/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Models/
├── Notifications/
├── Policies/
├── Services/
│   ├── DataSources/
│   ├── Forecast/
│   ├── Location/
│   ├── Weather/
│   ├── Market/
│   └── Analytics/
└── Support/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
└── views/
    ├── layouts/
    ├── components/
    ├── farmer/
    └── admin/

routes/
├── web.php
├── api.php
└── console.php

tests/
├── Feature/
└── Unit/
```

------------------------------------------------------------------------

# 52. Implementation Phases

## Phase 0 — Environment Audit

Check:

``` text
PHP
Composer
Node/npm
MySQL/MariaDB
PHP extensions
Cron
SSH/Terminal
SSL
storage permissions
```

If PHP \< 8.3, report before Laravel 13 setup.

## Phase 1 — Foundation

- Laravel
- database
- authentication
- Admin auth
- layouts
- Tailwind
- Livewire
- Alpine
- PWA foundation
- logging

## Phase 2 — Master Data

- states
- districts
- taluks
- markets
- crops
- varieties
- Admin CRUD

## Phase 3 — Data Source Framework

- source model
- credentials
- mappings
- adapters
- health checks
- sync logs
- manual sync
- scheduler

## Phase 4 — data.gov.in

- fixture
- importer
- validation
- normalization
- mapping
- deduplication
- persistence
- logs

## Phase 5 — Market UI

- home
- crops
- crop detail
- markets
- market detail
- price history

## Phase 6 — Location

- GPS
- manual location
- geocoder abstraction
- Nominatim adapter
- cache
- nearby markets

## Phase 7 — Weather

- provider interface
- Open-Meteo adapter
- sync
- cache
- UI

## Phase 8 — Analytics

- daily/weekly/monthly aggregates
- trends
- five-year seasonality

## Phase 9 — Forecasting

- model interface
- baseline
- seasonal model
- exponential smoothing
- uncertainty
- backtesting
- metrics

## Phase 10 — Where to Sell

- nearby markets
- current price
- distance
- freshness
- variety
- filters

## Phase 11 — CMS

- agriculture information
- schemes
- news
- videos
- English/Kannada

## Phase 12 — Admin Completion

- source health
- imports
- failures
- data quality
- forecasts
- feature flags
- settings
- audit logs

## Phase 13 — PWA/SEO

- manifest
- service worker
- installability
- offline shell
- SEO

## Phase 14 — Production Hardening

- security
- performance
- cron
- queues
- caching
- backups
- logs
- permissions
- mobile testing

------------------------------------------------------------------------

# 53. VPS Migration Plan

Current:

``` text
cPanel
Laravel
MySQL
Database Queue
File Cache
Cron
```

Future:

``` text
VPS
Laravel
MySQL
Redis
Horizon
Nginx
PHP-FPM
Dedicated workers
```

Business logic must remain portable.

------------------------------------------------------------------------

# 54. Engineering Rules

1.  Do not put business logic in huge controllers.
2.  Use Services/Actions.
3.  Use Form Requests.
4.  Use Policies.
5.  Use API Resources.
6.  Avoid N+1 queries.
7.  Add indexes.
8.  Use transactions where required.
9.  Use queues for long tasks.
10. Use Scheduler for recurring work.
11. Keep provider-specific code inside adapters.
12. Never hard-code credentials.
13. Never expose credentials to frontend.
14. Do not assume undocumented endpoints are permanent.
15. Keep raw source data.
16. Track source provenance.
17. Track freshness.
18. Do not claim forecasts are guarantees.
19. Do not copy undisclosed algorithms.
20. Prefer cPanel-compatible V1 dependencies.
21. Keep extension points for Redis/Python/IMD.
22. Test every important data transformation.
23. Never silently discard failed imports.
24. Do not silently replace bad data.
25. Keep Farmer and Admin interfaces separated.
26. Minimize retained location data.
27. Never make external APIs a normal page-render dependency.
28. Do not use production credentials locally.

------------------------------------------------------------------------

# 55. Antigravity Instructions

You are the primary development agent.

Before writing large amounts of code:

1.  Inspect the workspace.
2.  Check whether Laravel already exists.
3.  Check PHP/Composer/Node versions.
4.  Check database configuration.
5.  Do not delete existing user code.
6.  Create/update the project plan.
7.  Create migrations first.
8.  Create models and relationships.
9.  Create services/interfaces.
10. Create seeders.
11. Create tests.
12. Implement incrementally.
13. Run tests after each major module.
14. Fix errors before proceeding.
15. Keep documentation current.

Do not ask the user to paste secrets into source code.

------------------------------------------------------------------------

# 56. First Task

Do NOT build the entire application immediately.

First perform an environment audit and create:

``` text
docs/PROJECT_SPEC.md
docs/ARCHITECTURE.md
docs/DATABASE_DESIGN.md
docs/API_INTEGRATION.md
docs/FORECASTING.md
docs/ADMIN_PANEL.md
docs/DEPLOYMENT_CPANEL.md
docs/DEVELOPMENT_ROADMAP.md
docs/IMPLEMENTATION_STATUS.md
```

`IMPLEMENTATION_STATUS.md` must track:

``` text
Module
Status
Files
Tests
Known Issues
Next Task
```

------------------------------------------------------------------------

# 57. First Development Sprint

Implement only:

``` text
Laravel foundation
Database connection
Admin authentication
Base layout
Master-data migrations
Seeders
Feature flags
System settings
Admin navigation
Farmer navigation
PWA manifest
```

Do not begin forecasting until normalized market data is working.

------------------------------------------------------------------------

# 58. Definition of Done

A module is complete only when:

- migration exists
- model exists
- validation exists
- authorization exists where needed
- service/action exists
- UI exists
- error handling exists
- tests exist
- logging exists where needed
- documentation is updated

------------------------------------------------------------------------

# 59. Final Product Flow

``` text
Farmer opens PWA
      |
      v
Location selected
      |
      v
District / nearby markets
      |
      v
Current crop prices
      |
      +--> Price History
      |
      +--> Forecast
      |
      +--> Best Months
      |
      +--> Where to Sell
      |
      +--> Weather
      |
      +--> Agriculture Information
      |
      +--> Schemes
      |
      +--> News
      |
      +--> Videos
```

Admin:

``` text
Admin
 |
 +-- Dashboard
 +-- Data Sources
 +-- API Credentials
 +-- Field Mapping
 +-- Sync Jobs
 +-- Market Data
 +-- Crops
 +-- Markets
 +-- Forecasts
 +-- Backtesting
 +-- Weather
 +-- Articles
 +-- Schemes
 +-- News
 +-- Videos
 +-- Feature Flags
 +-- Settings
 +-- Roles/Users
 +-- Audit Logs
```

------------------------------------------------------------------------

# 60. References

Laravel 13: https://laravel.com/docs/13.x

Laravel Deployment: https://laravel.com/docs/13.x/deployment

Laravel Database: https://laravel.com/docs/13.x/database

Laravel Queues: https://laravel.com/docs/13.x/queues

Laravel Scheduling: https://laravel.com/docs/13.x/scheduling

cPanel Cron: https://docs.cpanel.net/cpanel/advanced/cron-jobs/

Nominatim Reverse API:
https://nominatim.org/release-docs/develop/api/Reverse/

Nominatim Usage Policy:
https://operations.osmfoundation.org/policies/nominatim/

Open-Meteo: https://open-meteo.com/

data.gov.in: https://data.gov.in/

Coffee Board: https://coffeeboard.gov.in/

Coconut Development Board: https://coconutboard.gov.in/

------------------------------------------------------------------------

# 61. Final Build Instruction

Build this as a real production application, not static demo pages.

Priorities:

``` text
Correct data
Traceable sources
Reliable ingestion
Fast farmer experience
Transparent forecasting
Admin control
Security
cPanel compatibility
Future scalability
```

Implementation order:

``` text
Environment
→ Laravel foundation
→ Database
→ Master data
→ Data-source framework
→ Market ingestion
→ Market UI
→ Location
→ Weather
→ Historical analytics
→ Forecasting
→ Where to Sell
→ CMS
→ Admin completion
→ PWA
→ Security
→ Performance
→ Production deployment
```
