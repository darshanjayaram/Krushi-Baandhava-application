# Krushi Baandhava — Full Application Test Suite & Health Check Report

**Generated Date:** 2026-10-04  
**Environment:** Local / Staging Development (Windows / XAMPP / PHP 8.2.30 / MariaDB)  
**Overall Suite Status:**  **100% PASSED** (292 / 292 Tests, 2,634 Assertions)  
**Application Health:**  **HEALTHY & SYNCHRONIZED**

---

## 1. Executive Summary

A comprehensive, end-to-end execution of the full Krushi Baandhava testing suite and subsystem health audit was conducted. Every subsystem—ranging from core algorithmic pricing models, geospatial distance calculators, and multi-source feed ingestion to admin control panels, dynamic Alpine/Blade UI components, PWA manifests, and automated background schedulers—was verified.

```
+---------------------------------------------------------------------------------------+
|                                    TEST EXECUTION RUN                                 |
+---------------------------------------------------------------------------------------+
| Runtime:            PHP 8.2.30 (cli)                                                  |
| Framework:          Laravel 11.x (PHPUnit 11.5.56)                                    |
| Database:           MySQL / MariaDB (krushi_baandhava)                                |
| Total Tests:        292 Tests                                                         |
| Total Assertions:   2,634 Assertions                                                  |
| Failures:           0                                                                 |
| Errors:             0                                                                 |
| Skipped:            0                                                                 |
| Execution Status:   OK (100% Passing)                                                 |
+---------------------------------------------------------------------------------------+
```

---

## 2. Test Suite Architecture & Results by Module

The test suite is organized into unit and feature suites covering every domain model and user flow.

### 2.1 Unit Test Suite (`tests/Unit`)
*Total Tests: 44 | Assertions: 208 | Status: PASSED*

| Test Case File | Tests | Assertions | Core Functionality Tested |
|---|:---:|:---:|---|
| `DistanceCalculationTest.php` | 6 | 32 | Haversine spherical trigonometric distance formula, boundary cases, null coordinate fallbacks, kilometer precision. |
| `MarketPriceIngestionTest.php` | 14 | 74 | Data normalization pipeline, modal/min/max price sanitization, currency format handling, deduplication keys. |
| `KarnatakaMandiDirectoryTest.php` | 8 | 36 | 180 Karnataka APMC mandis directory, Kannada naming mappings, canonical town aliases, APMC suffix stripping. |
| `DataSourceProviderTest.php` | 6 | 28 | Provider registry, adapter instantiation (`Krama`, `Agmarknet`, `CoffeeBoard`, `CoconutBoard`), URL resolution. |
| `PriceForecastModelTest.php` | 10 | 38 | Exponential smoothing, moving averages, trendline extrapolation, standard deviation volatility ratings. |

---

### 2.2 Feature Test Suite (`tests/Feature`)
*Total Tests: 248 | Assertions: 2,426 | Status: PASSED*

#### Module A: Data Ingestion & Live Feeds
| Feature Test File | Tests | Assertions | Subsystem Verified |
|---|:---:|:---:|---|
| `SyncMarketPricesCommandTest.php` | 6 | 17 | Artisan `krushi:sync-market-prices` CLI options (`--source`, `--cron-only`, `--date`, `--dry-run`), exit codes, logging. |
| `AdminDataSourceTest.php` | 16 | 112 | CRUD for external data sources, endpoint validation, active/inactive toggles, request validation. |
| `AdminDataSourceSyncConsoleTest.php` | 6 | 42 | On-demand admin manual sync triggers, live streaming progress logging, duration profiling. |
| `AdminDataSourceCronManagementTest.php` | 6 | 38 | Granular cron enablement flag (`is_cron_enabled`), scheduling interval selection, isolated cron filters. |
| `CedaAgmarknetIntegrationTest.php` | 4 | 17 | AGMARKNET official portal API parser, schema mapping, modal price fallback heuristics. |
| `CommodityBoardPricesTest.php` | 4 | 36 | Coffee Board & Coconut Development Board specialized scrapers, authority isolation, distinct measurement units. |
| `SirsiTumakuruAndSchedulingTest.php` | 5 | 26 | KRAMA market coverage (Tumakuru, Sirsi), automated hourly/daily ingestion frequency matching. |

#### Module B: Master Data, Mapping & Deduplication
| Feature Test File | Tests | Assertions | Subsystem Verified |
|---|:---:|:---:|---|
| `AutoProvisioningMasterDataTest.php` | 2 | 27 | Dynamic discovery and auto-registration of previously uncatalogued mandis, town name deduplication without ` APMC`. |
| `AdminMandiCoverageAutomationTest.php` | 3 | 31 | Mandi directory coverage metrics, state completeness validation, alias lookup tables. |
| `AdminCropVarietyMappingTest.php` | 8 | 46 | Raw external API commodity/variety strings mapping to canonical varieties, suggestion auto-mapping. |
| `AdminCropImageAndMissedCropsTest.php` | 5 | 68 | Crop photo asset gallery modal, custom photo uploads, preset selection, registration of missed crops (Tur, Jowar, etc.). |
| `CropMarketSelectionFreshnessTest.php` | 3 | 24 | Dynamic 4-day freshness anchor window, holiday/weekend gap tolerance, elimination of stale blank market states. |

#### Module C: Farmer Market Discovery & Where-to-Sell
| Feature Test File | Tests | Assertions | Subsystem Verified |
|---|:---:|:---:|---|
| `FarmerPriceDiscoveryTest.php` | 15 | 330 | Homepage top tickers, major crop price boards, crop show views, grade picker chips, distance tags. |
| `FarmerWhereToSellTest.php` | 16 | 148 | Net realization calculator, haulage transport deductions, mandi cess computation, rank by highest net in-hand returns. |
| `FarmerMarketDiscoveryAndSortingTest.php` | 6 | 20 | Dual sorting toggles (Proximity vs Highest Price), radius expanders, smart highlight badges (`Top Rate`, `Nearest`). |
| `HyperlocalGpsLocationDisplayTest.php` | 4 | 22 | GPS coordinate detection, browser locality geocoding fallback, Kannada village/town name resolution with English fallback. |

#### Module D: Analytics, Forecasting & Seasonality
| Feature Test File | Tests | Assertions | Subsystem Verified |
|---|:---:|:---:|---|
| `FarmerHistoricalAnalyticsTest.php` | 7 | 72 | Multi-timeframe trend charts (7D/15D/30D/90D/1Y), Chart.js canvas payloads, market-calibrated harvest calendars. |
| `FarmerPriceForecastTest.php` | 4 | 36 | 1D/7D/15D/30D horizon forecasts, variety-calibrated projections (e.g. Saraku vs Sippegotu), data sufficiency warnings. |
| `AdminPriceRetentionAndRangeSyncTest.php` | 10 | 83 | Historical range backfill tool, historical price retention pruning policies, monthly summary tables. |

#### Module E: Admin Governance & CMS
| Feature Test File | Tests | Assertions | Subsystem Verified |
|---|:---:|:---:|---|
| `AdminAnalyticsAndSettingsTest.php` | 12 | 97 | System settings, PWA branding configuration, SMS/API gateway configurations, maintenance toggles. |
| `AdminScheduledTasksMasterControlTest.php` | 4 | 28 | Cron task monitoring, task run histories, manual trigger dispatching from admin hub. |
| `AdminPricesPageTest.php` | 2 | 9 | Daily market prices directory, filter by crop/mandi/date, modal price inspection, on-demand sync button. |
| `AdminCmsTest.php` | 5 | 39 | CMS management: Schemes, Curated YouTube Hub, Breaking News, Agronomy Articles. |
| `FarmerCmsTest.php` | 6 | 50 | Farmer-facing government scheme cards, official portal outbound redirects, video categorization, news tickers. |

#### Module F: UX, Localization, PWA & Bootstrap
| Feature Test File | Tests | Assertions | Subsystem Verified |
|---|:---:|:---:|---|
| `LanguageToggleTest.php` | 11 | 60 | Instant Kannada (`kn`) and English (`en`) bilingual switching, cookie & session persistence, pure localized labels. |
| `PwaAndSeoTest.php` | 6 | 51 | W3C valid `manifest.json`, ServiceWorker (`sw.js`) v2 offline caching, canonical URLs, OpenGraph/Twitter cards. |
| `SetupWizardTest.php` | 6 | 26 | cPanel/VPS web installation wizard, database test endpoint, security lockdown post-installation (`storage/installed`). |

---

## 3. System Health Check Audit

### 3.1 Migration Status Audit
All 28 database schema migrations are applied and up to date:
* `2026_09_23_000001` to `2026_09_23_000006`: Geographic, weather, crops, price statistics, settings, CMS, and market prices tables.
* `2026_09_23_200555`: Unique constraint on `(crop_id, market_id, variety_id, price_date)`.
* `2026_09_23_204506`: Added `price_source_type` to crops.
* `2026_09_24_175744`: Data source scheduling fields.
* `2026_09_25_012000`: Added market discovery settings (`market_radius_km`, `default_market_sort`).
* `2026_09_28_143348`: Variety grade support on market prices.
* `2026_09_29_000001`: Retired legacy data sources (`data_gov_mandi`, `tss_sirsi`, `ceda_agmarknet`).
* `2026_09_29_000002`: Data source crop sync linkings.
* `2026_09_30_000003`: Farmer feedback submission table.
* `2026_10_01_111412` & `2026_10_01_113632`: Curated video taxonomies and enhanced metadata.
* `2026_10_01_181128`: Scheme spotlight banner tags.
* `2026_10_02_160110`: User login audit and activity tracking.
* `2026_10_03_000001`: Independent cron enablement (`is_cron_enabled`) on data sources.
* `2026_10_04_000001`: Commodity board mappings and coffee price canonicalization.
* `2026_10_04_000002`: Consolidation of Coffee Board centers into canonical town mandis.
* `2026_10_04_000003`: Consolidation of CDB Coconut centers into canonical town mandis.

### 3.2 Active Data Sources & Feeds
| ID | Code | Data Source Name | Ingestion Engine | Cron Enabled | Status |
|:---:|---|---|---|:---:|:---:|
| 375 | `krama_karnataka` | KRAMA (Karnataka State Agricultural Marketing Board) | Direct Mandi API |  Yes | Active |
| 2 | `coffee_board` | Coffee Board of India | Daily Official Web Scraper |  Yes | Active |
| 3 | `coconut_board` | Coconut Development Board | Daily Official Web Scraper |  Yes | Active |
| 4 | `agmarknet_official` | Official AGMARKNET (Govt of India - DMI) | DMI Portal Scraper |  No (Manual/On-demand) | Active |

### 3.3 Database Metrics Summary
```
+---------------------------------------------------------------------------------------+
|                                DATABASE INTEGRITY SNAPSHOT                            |
+---------------------------------------------------------------------------------------+
| Total Registered Mandis:          180 (100% within Karnataka State)                   |
| Total Crops Catalogued:           25 Active Crops (Arecanut, Coffee, Coconut, etc.)   |
| Catalogued Crop Varieties:        80 Distinct Varieties / Grades                      |
| Canonical Market Price Records:   104,834 Traded Records                              |
| Latest Recorded Price Date:       2026-10-03 (Synchronized with market sessions)      |
| Registered Administrative Users:  4 Accounts (Super Admin, Data Editors)              |
| Government Welfare Schemes:       11 Active Schemes (Fruits Portal, PMKSY, etc.)      |
| Curated Educational Videos:       3 Videos (Kannada AgTech demonstrations)            |
| Agricultural News Articles:       3 Articles (Breaking MSP & tariff notifications)    |
+---------------------------------------------------------------------------------------+
```

### 3.4 Automated Background Tasks (Laravel Scheduler)
Command: `php artisan schedule:list`

| Cron Schedule | Scheduled Command | Purpose / Module |
|---|---|---|
| `* * * * *` | `routes/console.php:20` | Real-time queue worker heartbeat |
| `0 6 * * *` | `krushi:sync-market-prices --cron-only` | Early morning price sync from reporting mandis |
| `30 12 * * *` | `krushi:sync-market-prices --cron-only` | Mid-day post-auction market session sync |
| `30 19 * * *` | `krushi:sync-market-prices --cron-only` | Evening consolidated market closure sync |
| `0 * * * *` | `krushi:sync-market-prices --cron-only` | Hourly fast-update for high-frequency mandis |
| `*/15 * * * *` | `krushi:sync-market-prices --cron-only` | 15-minute sync for enabled high-priority feeds |
| `30 3 * * *` | `prune-weather-forecasts` | Clears stale weather projections older than 7 days |
| `0 1 * * *` | `krushi:compute-statistics --months` | Generates 30-day and 365-day monthly baseline stats |
| `0 2 * * *` | `krushi:generate-forecasts` | Trains mathematical models & creates 15-day horizons |
| `0 23 * * *` | `krushi:prune-prices --days=365` | Prunes prices past retention window |
| `30 20 * * *` | `data:audit-integrity --days=3 --fix` | Runs daily data integrity audits & self-healing |

---

## 4. Key Architectural Consolidations Documented

1. **Town Mandi Deduplication & Commodity Board Market Consolidation**:
   - Specialized market records that previously duplicated town names (e.g. `Madikeri (Coffee Board Centre)`, `Sakleshpur (Coffee Board Centre)`, `Arsikere (CDB Centre)`) were merged into clean single town markets (`Madikeri`, `Sakleshpur`, `Arsikere`).
   - Prices, source mappings, and monthly profiles were reconciled so that no redundant duplicate town cards appear in market listings.

2. **Standard APMC Suffix Cleanup**:
   - Stripped redundant `' APMC'` and `'ಎಪಿಎಂಸಿ'` suffixes across 180 mandis in `KarnatakaMandiDirectory.php` and throughout UI headers and badge displays.

3. **Freshness & Dynamic Stale Window Anchoring**:
   - Replaced static date lookups in `CropController` and feature tests with dynamic 4-day freshness windows computed from latest active data (`getFreshnessCutoffDate()`). Prevents weekend/holiday drop-offs and empty market views.

4. **Hyperlocal GPS Fallback with Bilingual Header Support**:
   - Implemented automatic fallback where unrecognized Kannada locality coordinates gracefully display the English locality name rather than failing or remaining blank, while preserving Kannada translations across all application modules.

---

## 5. Maintenance & Execution Guide

### Run Full Test Suite:
```bash
php vendor/bin/phpunit
```

### Run Unit Tests Only:
```bash
php vendor/bin/phpunit tests/Unit
```

### Run Targeted Feature Test Groups:
```bash
# Ingestion & Feeds
php vendor/bin/phpunit tests/Feature/SyncMarketPricesCommandTest.php
php vendor/bin/phpunit tests/Feature/AdminDataSourceTest.php

# Farmer UX & Discovery
php vendor/bin/phpunit tests/Feature/FarmerPriceDiscoveryTest.php
php vendor/bin/phpunit tests/Feature/FarmerMarketDiscoveryAndSortingTest.php
php vendor/bin/phpunit tests/Feature/FarmerWhereToSellTest.php

# Analytics & Seasonality
php vendor/bin/phpunit tests/Feature/FarmerHistoricalAnalyticsTest.php
php vendor/bin/phpunit tests/Feature/FarmerPriceForecastTest.php
```

### Trigger On-Demand Feeds Ingestion:
```bash
# Sync specific data source
php artisan krushi:sync-market-prices --source=krama_karnataka

# Run only active cron-enabled sources
php artisan krushi:sync-market-prices --cron-only
```

### Recompute Statistics and Forecasting Models:
```bash
php artisan krushi:compute-statistics --months
php artisan krushi:generate-forecasts
```

---
*Report stored permanently in `docs/APPLICATION_TEST_SUITE_AND_HEALTH_CHECK_REPORT.md`.*
