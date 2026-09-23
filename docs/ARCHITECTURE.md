# Krushi Baandhava — System Architecture

## 1. Architectural Style: Modular Monolith

Krushi Baandhava is built as a **modular monolith** in Laravel. A monolith ensures simple, reliable single-server deployment on shared Linux cPanel hosting without the operational overhead of microservices, while clean domain separation guarantees maintainability and straightforward extraction into microservices if needed later.

```
+-----------------------------------------------------------------------------------+
|                                  FARMER PWA (Client)                             |
|  - Mobile-First Responsive UI (Tailwind CSS, Alpine.js, Blade)                    |
|  - Service Worker (App Shell & Dynamic Data Caching)                             |
|  - Geolocation Integration & Bilingual Selector (EN/KN)                           |
+-----------------------------------------+-----------------------------------------+
                                          |
                                          | HTTP / HTTPS Requests
                                          v
+-----------------------------------------------------------------------------------+
|                              LARAVEL WEB & API LAYER                              |
|  +---------------------------+  +---------------------------+  +---------------+  |
|  |     Farmer Controllers    |  |     Admin Controllers     |  |   API (v1)    |  |
|  |  (Home, Crops, Markets,   |  | (Sources, Mappings, Sync, |  |  (Resources,  |  |
|  |   Forecast, Weather, CMS) |  |   Quality, Logs, Settings)|  | Rate Limiting)|  |
|  +-------------+-------------+  +-------------+-------------+  +-------+-------+  |
+----------------|------------------------------|------------------------|----------+
                 |                              |                        |
                 +------------------------------+------------------------+
                                                |
                                                v
+-----------------------------------------------------------------------------------+
|                                 DOMAIN SERVICES                                   |
|  +-----------------------+  +-----------------------+  +-----------------------+  |
|  | Location & Geocoding  |  | Market Intelligence   |  | Forecast Engine       |  |
|  | (GeocoderInterface)   |  | (Normalization, Dedup)|  | (Statistical Models)  |  |
|  +-----------------------+  +-----------------------+  +-----------------------+  |
|  +-----------------------+  +-----------------------+  +-----------------------+  |
|  | Weather Service       |  | Agriculture CMS &     |  | Analytics Aggregator  |  |
|  | (WeatherProviderIntf) |  | Government Schemes    |  | (Daily/Weekly/Monthly)|  |
|  +-----------------------+  +-----------------------+  +-----------------------+  |
+-----------------------------------------------+-----------------------------------+
                                                |
                                                v
+-----------------------------------------------------------------------------------+
|                        DATABASE & REPOSITORIES (MySQL / MariaDB)                  |
|  +--------------------+  +--------------------+  +--------------------+           |
|  | Master Records     |  | Raw External Logs  |  | Canonical Prices   |           |
|  | (States, Crops,    |  | (market_price_raw, |  | (market_prices,    |           |
|  |  Markets, Users)   |  |  sync_logs)        |  |  arrivals)         |           |
|  +--------------------+  +--------------------+  +--------------------+           |
|  +--------------------+  +--------------------+  +--------------------+           |
|  | Aggregated Stats   |  | Price Forecasts    |  | Weather & Content  |           |
|  | (daily, monthly)   |  | (forecast_runs)    |  | (articles, schemes)|           |
|  +--------------------+  +--------------------+  +--------------------+           |
+-----------------------------------------------+-----------------------------------+
                                                ^
                                                | Background Writing / Processing
+-----------------------------------------------+-----------------------------------+
|                       SCHEDULED WORKERS & INGESTION QUEUES                        |
|  Laravel Scheduler (Cron) --> Ingestion Queue (Database Driver)                   |
|  +----------------------+   +-----------------------+   +----------------------+  |
|  | data.gov.in Adapter  |   | Agmarknet Adapter     |   | Boards (Coffee/Coco) |  |
|  +----------------------+   +-----------------------+   +----------------------+  |
|  +----------------------+   +-----------------------+   +----------------------+  |
|  | Open-Meteo Weather   |   | Statistics Calculator |   | Forecast Generator   |  |
|  +----------------------+   +-----------------------+   +----------------------+  |
+-----------------------------------------------------------------------------------+
```

---

## 2. Directory Layout & Organization

The project adheres to modern Laravel domain structuring:

```text
app/
├── Actions/                  # Single-purpose invocable domain actions
├── Console/Commands/         # Scheduled CLI commands (Sync, Aggregate, Forecast)
├── Enums/                    # Typed enums (SyncStatus, RoleType, ForecastHorizon)
├── Events/                   # Domain events (MarketPriceIngested, ForecastCompleted)
├── Exceptions/               # Custom application exceptions
├── Http/
│   ├── Controllers/
│   │   ├── Admin/            # Admin dashboard and resource controllers
│   │   ├── Api/V1/           # Versioned REST API controllers
│   │   └── Farmer/           # Farmer-facing web controllers
│   ├── Middleware/           # Role verification, PWA headers, localization
│   ├── Requests/             # Form validation requests
│   └── Resources/            # JSON API transformation resources
├── Jobs/                     # Queueable jobs (Imports, Normalization, WeatherSync)
├── Models/                   # Eloquent models with indexes and relationships
├── Policies/                 # Authorization policies (Role-based access)
└── Services/
    ├── DataSources/          # External provider adapters & ingestion pipeline
    │   ├── Contracts/        # MarketDataProviderInterface
    │   ├── DataGov/          # data.gov.in API client & provider
    │   ├── Agmarknet/        # Agmarknet structured adapter
    │   ├── CoffeeBoard/      # Coffee Board provider
    │   └── CoconutBoard/     # Coconut Board provider
    ├── Forecast/             # Forecasting algorithms & backtesting engines
    │   ├── Contracts/        # ForecastModelInterface
    │   ├── Models/           # MovingAverageModel, ExponentialSmoothing, SeasonalModel
    │   └── Backtesting/      # Rolling backtester & accuracy metric calculators
    ├── Location/             # Geocoding & distance calculation
    │   ├── Contracts/        # GeocoderInterface
    │   └── Nominatim/        # Cached Nominatim reverse geocoder
    ├── Weather/              # Weather provider integration
    │   ├── Contracts/        # WeatherProviderInterface
    │   └── OpenMeteo/        # Open-Meteo provider
    └── Analytics/            # Aggregation & seasonal statistics calculations
```

---

## 3. Ingestion Pipeline & Normalization Lifecycle

The core market data pipeline is completely decoupled from web request handling:

```
[ External API / Source Feed ]
              |
              v (Scheduled artisan command / Ingestion Job)
[ Fetch Raw Payload ]
              |
              v
[ Insert to `market_price_raw` ] (Checksum, received_at, status='pending')
              |
              v
[ Validate Raw Record Schema ]
   ├─ Fail ──> Mark `failed`, record error_message, log rejection reason
   └─ Pass ──> Continue
              |
              v
[ Apply Data Source Field Mapping ] (Declarative mappings: commodity -> crop, etc.)
              |
              v
[ Resolve Entities ]
   ├─ Resolve Crop & Variety (via `crop_source_mappings` & aliases)
   └─ Resolve Market & District (via `market_source_mappings` & aliases)
   └─ Unresolved? ──> Log to `unresolved_mappings`, mark for admin review
              |
              v
[ Sanity & Data Quality Verification ]
   ├─ Checks: min_price <= modal_price <= max_price, price > 0, valid date
   └─ Fail ──> Reject record, log issue to `data_quality_issues`
              |
              v
[ Deduplication & Canonical Storage ]
   ├─ Check canonical unique key: (crop_id, variety_id, market_id, price_date, source_id)
   └─ Upsert into `market_prices` table
              |
              v
[ Post-Processing Pipeline ]
   ├─ Update `price_daily_statistics`
   └─ Invalidate relevant cached market aggregates
```

---

## 4. Provider Abstraction Contracts

All external integrations utilize strict interface boundaries:

### 4.1 Market Data Provider Contract
```php
namespace App\Services\DataSources\Contracts;

interface MarketDataProviderInterface
{
    public function fetch(array $filters = []): iterable;
    public function normalize(array $record): ?array;
    public function healthCheck(): array;
}
```

### 4.2 Geocoder Contract
```php
namespace App\Services\Location\Contracts;

interface GeocoderInterface
{
    public function reverseGeocode(float $latitude, float $longitude): ?array;
}
```

### 4.3 Weather Provider Contract
```php
namespace App\Services\Weather\Contracts;

interface WeatherProviderInterface
{
    public function getCurrentWeather(float $latitude, float $longitude): array;
    public function getForecast(float $latitude, float $longitude, int $days = 7): array;
}
```

### 4.4 Forecast Model Contract
```php
namespace App\Services\Forecast\Contracts;

interface ForecastModelInterface
{
    public function train(array $historicalData): array;
    public function predict(array $historicalData, array $context): array;
}
```

---

## 5. Linux cPanel Shared Hosting Concurrency & Queue Model

1. **Queue Execution**: Configured with `QUEUE_CONNECTION=database`. A cPanel Cron job triggers queue execution every 5-10 minutes with `--stop-when-empty` to prevent long-running worker processes from consuming CPU limits:
   ```bash
   php /home/USERNAME/agrimarket/artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
   ```
2. **Scheduler Execution**: Single cPanel Cron job triggers Laravel's internal scheduler every minute:
   ```bash
   php /home/USERNAME/agrimarket/artisan schedule:run >> /dev/null 2>&1
   ```
3. **Database Caching**: In shared hosting without Redis, `CACHE_STORE=file` or `CACHE_STORE=database` is leveraged with indexed tags or structured keys for instant retrieval of today's market rates.
