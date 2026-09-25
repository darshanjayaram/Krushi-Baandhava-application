# CEDA Agmarknet API Integration Architecture & Production Blueprint

This document details the live testing results of the **CEDA Agmarknet API** (Centre for Economic Data and Analysis, Ashoka University), compares it directly with `data.gov.in`, and establishes the architecture to achieve and surpass **Negilu Krushi** (https://negilukrishi.in/).

---

## 1. Live Verification & API Key Testing Summary

Using an official personal API key, we performed live diagnostic tests across all endpoints against `https://api.ceda.ashoka.edu.in/v1`:

### 1.1 Authentication & Rate Limiting
* **Authentication Header:** `Authorization: Bearer <API_KEY>` (**Confirmed Working: HTTP 200 OK**).
* Other headers (`api-key`, `x-api-key`) return `HTTP 401 Unauthorized`.
* **Rate Limit Policy:** Exactly **40 requests per hour** (`ratelimit-limit: 40; ratelimit-policy: 40;w=3600`).
* **Environment Configuration:**
  ```env
  CEDA_API_BASE_URL=https://api.ceda.ashoka.edu.in/v1
  CEDA_API_KEY=fc5f...4ecc  # Stored securely in .env
  ```

---

## 2. Live Discovery: Karnataka Taxonomy & Mappings

### 2.1 State & Districts (`GET /agmarknet/geographies`)
* **Karnataka State ID:** `state_id` = **`29`** (Official Census 2011 State Code).
* **30 Standardized Districts:**
  | District | `district_id` | District | `district_id` |
  |---|---|---|---|
  | **Bagalkot** | `556` | **Haveri** | `564` |
  | **Bangalore** | `572` | **Kodagu** | `576` |
  | **Bangalore Rural** | `583` | **Kolar** | `581` |
  | **Belgaum** | `555` | **Koppal** | `560` |
  | **Bellary** | `565` | **Mandya** | `573` |
  | **Bidar** | `558` | **Mysore** | `577` |
  | **Bijapur** | `557` | **Raichur** | `559` |
  | **Chamarajanagar** | `578` | **Ramanagara** | `584` |
  | **Chikkaballapura** | `582` | **Shimoga** | `568` |
  | **Chikmagalur** | `570` | **Tumkur** | `571` |
  | **Chitradurga** | `566` | **Udupi** | `569` |
  | **Dakshina Kannada** | `575` | **Uttara Kannada** | `563` |
  | **Davanagere** | `567` | **Yadgir** | `580` |
  | **Dharwad** | `562` | **Gulbarga** | `579` |
  | **Gadag** | `561` | **Hassan** | `574` |

### 2.2 Core Karnataka Commodities (`GET /agmarknet/commodities`)
Out of 453 commodities, the exact IDs for Krushi Baandhava crops are:

| Crop | CEDA `commodity_id` | Official Name in CEDA |
|---|---|---|
| **Arecanut (Supari)** | `140` | `Arecanut(Betelnut/Supari)` |
| **Betelnuts (Raw)** | `41` | `Betelnuts` |
| **Paddy (Common)** | `2` | `Paddy(Dhan)(Common)` |
| **Paddy (Basmati)** | `414` | `Paddy(Dhan)(Basmati)` |
| **Rice** | `3` | `Rice` |
| **Maize** | `4` | `Maize` |
| **Cotton** | `15` | `Cotton` |
| **Onion** | `23` | `Onion` |
| **Coconut** | `138` | `Coconut` |
| **Tender Coconut** | `200` | `Tender Coconut` |
| **Coffee** | `45` | `Coffee` |
| **Tomato** | `78` | `Tomato` |
| **Ragi** | `30` | `Ragi (Finger Millet)` |
| **Groundnut** | `10` | `Groundnut` |
| **Black Pepper** | `38` | `Black pepper` |
| **Turmeric** | `39` | `Turmeric` |
| **Ginger (Dry)** | `27` | `Ginger(Dry)` |
| **Jowar** | `5` | `Jowar(Sorghum)` |
| **Bengal Gram** | `6` | `Bengal Gram(Gram)(Whole)` |
| **Red Gram (Tur)** | `7` | `Red Gram` |
| **Dry Chillies** | `132` | `Dry Chillies` |

### 2.3 Live Prices Output (`POST /agmarknet/prices`)
Tested query for Tumkur (`district_id: [571]`), Market Tumkur (`market_id: [784]`), Commodity Paddy (`2`):
```json
{
  "output": {
    "type": "success",
    "message": "Data exists",
    "data": [
      {
        "date": "2024-11-26T00:00:00.000Z",
        "commodity_id": 2,
        "census_state_id": 29,
        "census_district_id": 571,
        "market_id": 784,
        "min_price": 2300,
        "max_price": 2850,
        "modal_price": 2500
      }
    ]
  }
}
```

---

## 3. Why We Should NOT Drop data.gov.in (The Reality of Negilu Krushi)

If we drop `data.gov.in` and rely 100% on CEDA alone, we face three critical bottlenecks:

### 1. The Rate Limit Bottleneck (40 requests/hour)
* Krushi Baandhava tracks 30+ Karnataka districts and 25+ crops.
* Fetching prices on CEDA requires separate POST requests per commodity and district.
* Querying 20 crops across 30 districts would take **600 API calls** — with a 40 req/hour limit, syncing a single day's prices would take **15 hours**!
* In contrast, `data.gov.in` allows fetching **all 300+ Karnataka mandis and all crops in a single bulk API call** (`filters[state.keyword]=Karnataka&limit=5000`).

### 2. The Variety / Grade Omission
* **Negilu Krushi's flagship feature** is allowing farmers to pick specific grades:
  * Under Arecanut in Tumakuru: **Rashi - Average** (₹47,500), **Bette** (₹42,000), **Chali** (₹38,000).
  * Under Paddy: **Jyothi**, **Sona Masuri**, **IR 64**.
* **CEDA returns NO varieties or grades.** It aggregates prices strictly at the commodity level. If we dropped `data.gov.in`, we would lose all variety breakdown cards!

### 3. Latency & 504 Timeouts
* During live tests, high-volume commodity queries on CEDA (such as Arecanut in Shimoga) took **>30 seconds** and frequently failed with `504 Gateway Time-out` on Ashoka University's Nginx server.

---

## 4. The Production Hybrid Architecture (Best of Both Worlds)

To match and surpass Negilu Krushi without breaking or getting blocked:

```
                       ┌────────────────────────────────┐
                       │   DAILY ARTISAN SCHEDULER      │
                       └───────────────┬────────────────┘
                                       │
                ┌──────────────────────┴──────────────────────┐
                ▼                                             ▼
    [ Primary Live Variety Feed ]                 [ CEDA Agmarknet Engine ]
       data.gov.in Mandi Feed                       POST /agmarknet/prices
   (Single bulk call for all Karnataka)             (Targeted within 40 req/hr)
   - Fetches Rashi, Bette, Chali, Sona              - 30/60/90-Day Historical Trends
   - Daily Live Prices for Farmer Cards             - Mandi Arrival Volumes (quantities)
   - 0 Rate Limit Bottlenecks                       - Powers AI Rate Predictions
                │                                             │
                └──────────────────────┬──────────────────────┘
                                       ▼
                       ┌────────────────────────────────┐
                       │   KRUSHI BAANDHAVA DATABASE    │
                       │   - market_prices (live rates) │
                       │   - price_forecasts (trends)   │
                       │   - mandi_arrival_volumes      │
                       └───────────────┬────────────────┘
                                       ▼
                       ┌────────────────────────────────┐
                       │  FARMER MOBILE / WEB PWA UI    │
                       │  - Live Grade Pills (Rashi...) │
                       │  - Nearest Mandis (78 km away) │
                       │  - 30-Day Trendline Chart      │
                       │  - Arrival Volume Gauge        │
                       └────────────────────────────────┘
```

### Key Operational Rules:
1. **Live Daily Variety Prices**: Exclusively powered by `data.gov.in` + Commodity Boards (Coffee Board & Coconut Board) for real-time daily mandi variety cards.
2. **Historical Trendlines & Charts**: Powered by `CedaAgmarknetDataProvider` to pull 30-day and 90-day time series for core crops once daily (consuming only ~15 of our 40 hourly quota).
3. **Rate Predictions & Forecasting**: Supplies the 30+ daily historical observations required by `ForecastingEngineService` to compute 7-day, 15-day, and 30-day price projections.
4. **Mandi Arrival Volumes**: Powered by CEDA's dedicated `/agmarknet/quantities` endpoint to display APMC arrival volume gauges.

---

## 5. Completed Implementation & Verification

The integration has been built, seeded, and tested:

### 5.1 Components Implemented
1. **`CedaAgmarknetDataProvider`** ([`app/Services/DataSources/Ceda/CedaAgmarknetDataProvider.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Services/DataSources/Ceda/CedaAgmarknetDataProvider.php)):
   * Connects to `https://api.ceda.ashoka.edu.in/v1` via Bearer token (`CEDA_API_KEY`).
   * Fetches historical time series (`POST /agmarknet/prices`) and arrival volumes (`POST /agmarknet/quantities`).
   * Pre-mapped with all 30 Karnataka districts (Census state ID `29`) and core commodities.
   * Robust mock mode for automated testing without consuming API quota.
2. **`DataSourceRegistry`** ([`app/Services/DataSources/DataSourceRegistry.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Services/DataSources/DataSourceRegistry.php)):
   * Registered `CedaAgmarknetDataProvider`.
3. **`CedaDataSourceSeeder`** ([`database/seeders/CedaDataSourceSeeder.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/database/seeders/CedaDataSourceSeeder.php)):
   * Created and verified `ceda_agmarknet` entry in `data_sources`.
   * Seeded standard commodity mappings in `crop_source_mappings` for Arecanut, Paddy, Maize, Cotton, Onion, Tomato, Ragi, Groundnut, Coconut, Coffee, Turmeric, Ginger, etc.
4. **Artisan Command `krushi:sync-ceda-historical`** ([`app/Console/Commands/SyncCedaHistoricalPricesCommand.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Console/Commands/SyncCedaHistoricalPricesCommand.php)):
   * Usage:
     ```bash
     # Preview 90 days historical sync
     php artisan krushi:sync-ceda-historical --crop=Paddy --days=90 --dry-run

     # Ingest history and immediately trigger rate predictions
     php artisan krushi:sync-ceda-historical --crop=Paddy --days=90 --forecast
     ```
   * Renders CLI summary table with latency, records received, inserted, updated, and forecast status.

### 5.2 Test Coverage
* Created [`tests/Feature/CedaAgmarknetIntegrationTest.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/tests/Feature/CedaAgmarknetIntegrationTest.php):
  * `test_ceda_provider_is_registered_in_registry`: **PASS**
  * `test_ceda_data_source_and_mappings_exist_in_db`: **PASS**
  * `test_ceda_provider_normalizes_records_correctly`: **PASS**
  * `test_ceda_sync_artisan_command_dry_run`: **PASS**
  * `test_ceda_sync_ingests_and_enables_forecasting`: **PASS**
* Complete project test suite: **173 passed (3,360 assertions)** with zero failures.
