# KRAMA & AGMARKNET DATA ARCHITECTURE & IMPLEMENTATION SPECIFICATION

**Project:** Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)  
**Document Version:** 1.0  
**Date:** September 2026  
**Status:** In Progress / Active Implementation  

---

## 1. Executive Summary & Strategic Rationale

Krushi Baandhava's mission is to provide the **most authentic, accurate, and timely crop prices** to Karnataka farmers.

Following rigorous live benchmarking against official government sources (`https://krama.karnataka.gov.in`, `https://agmarknet.gov.in`, and comparative analysis of leading platforms like Negilu Krushi), we have established a **hybrid, dual-engine data architecture**:

1. **Primary Live Karnataka APMC Prices**: **KRAMA** (Karnataka State Agricultural Marketing Board / e-Krishi Marukatte / Krishimaratavahini).
2. **Historical Time-Series & Prediction Baseline**: **AGMARKNET** (Directorate of Marketing & Inspection, Ministry of Agriculture & Farmers Welfare, GoI).
3. **Specialized Non-APMC Commercial Crops**:
   - **Coffee Board of India** (Arabica & Robusta daily benchmark rates).
   - **Coconut Development Board** (CDB reference copra, coconut, and oil rates).
   - **TSS Sirsi Cooperative Society** (Sirsi tender auction rates for Rashi, Chali, Bette, Gorabalu).
4. **Secondary / National Benchmark**: **data.gov.in Mandi API** (OGD Platform India).

---

## 2. Comparison & Architectural Justification

| Architectural Dimension | KRAMA (Karnataka State Board) | Agmarknet (Central Govt DMI) |
| :--- | :--- | :--- |
| **Data Authority** | Department of Agricultural Marketing, Govt of Karnataka | Directorate of Marketing & Inspection (DMI), MoA&FW, GoI |
| **Role in Krushi Baandhava** | **Primary Daily Live Feed** (Karnataka Core) | **Historical Archive & AI Model Training** |
| **Origin Point** | **Point of Sale**: Auction lots in Karnataka APMCs directly report into ReMS / KRAMA. | **Downstream Aggregator**: Batched periodically from state marketing boards. |
| **Update Latency** | **Real-Time / Same-Day**: Records appear as soon as daily lot bidding closes (afternoon). | **12–24+ hours lag**: Batch ingested at end of day or following morning. |
| **Karnataka APMC Coverage** | **100% Coverage**: Every regulated APMC and sub-yard across all 31 districts. | **Partial Coverage**: Selected major mandis synced via NIC nodal officers. |
| **Commercial Varieties** | **Exact Market Grades**: `Rashi`, `Gorabalu`, `EDI`, `Hale Chali`, `Hosa Chali`, `Sona Masuri Old`, `Paddy RNR Old`, etc. | **Generic Labels**: `Arecanut(Betelnut/Supari)`, `Paddy(Dhan)(Common)`. |
| **Automation Constraint** | **Zero Captcha**: Automated cron jobs can fetch daily data without human intervention. | **Requires 6-letter visual captcha** on `/v1/daily-price-arrival/report` or DMI token. |
| **Historical Archive Depth** | Current daily transactions and recent weeks. | **18+ years of archives (2007 to present)** across all Indian mandis. |

---

## 3. Data Pipeline & Workflow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          KRUSHI BAANDHAVA SYSTEM                            │
├──────────────────────────────────────┬──────────────────────────────────────┤
│ 1. DAILY LIVE APMC PRICES            │ 2. HISTORICAL DATA & PREDICTIONS     │
│    (Karnataka Core Mandis)           │                                      │
│    SOURCE: KRAMA (e-Marukatte)       │    SOURCE: AGMARKNET (GoI / DMI)     │
│    • Real-time same-day auctions     │    • 3 to 5 years of past records    │
│    • Exact Karnataka varieties       │    • Powers 12-month seasonality     │
│      (Rashi, Chali, Sona Masuri)     │    • Powers AI price forecasts       │
│    • 100% automated (No captcha)     │    • Periodic / on-demand admin sync │
├──────────────────────────────────────┴──────────────────────────────────────┤
│ 3. SPECIALIZED NON-APMC BOARDS                                              │
│    • Coffee Board of India (Arabica & Robusta daily board rates)            │
│    • Coconut Development Board (CDB reference copra & coconut rates)        │
│    • TSS Sirsi Cooperative (Sirsi tender auctions)                          │
├─────────────────────────────────────────────────────────────────────────────┤
│ 4. SECONDARY / NATIONAL BENCHMARK                                           │
│    • data.gov.in Mandi API (Headless server API key)                        │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Implementation Steps

### Step 1: Cleanup of Obsolete CEDA Integration
- Replace `ceda_agmarknet` in `data_sources` table and seeders with `agmarknet_official` (Official AGMARKNET - DMI).
- Deprecate/remove obsolete `App\Services\DataSources\Ceda\` provider.
- Clean up any orphaned CEDA mappings in `crop_source_mappings` and `market_source_mappings`.

### Step 2: Implementation of `KramaMarketDataProvider`
- Location: `app/Services/DataSources/Krama/KramaMarketDataProvider.php`
- Class implements `MarketDataProviderInterface` extending `BaseMarketDataProvider`.
- Interacts with KRAMA's daily report system (`https://krama.karnataka.gov.in/reports/Main_Rep` and `https://krama.karnataka.gov.in/reports/CommadityRep`).
- Robust HTML parsing for tables containing:
  - Market / APMC Name
  - Commodity
  - Commercial Variety & Grade
  - Min Price, Max Price, Modal Price (₹/Quintal)
  - Arrivals Quantity
  - Arrival Date
- Normalizes commercial varieties to canonical varieties in `crop_varieties` and APMC mandis in `markets`.
- Registered in `app/Services/DataSources/DataSourceRegistry.php`.

### Step 3: Implementation of `AgmarknetHistoricalDataProvider`
- Location: `app/Services/DataSources/Agmarknet/AgmarknetHistoricalDataProvider.php`
- Interacts with `https://api.agmarknet.gov.in/v1/` or Agmarknet web report endpoints.
- Captcha Flow:
  - Endpoint `/v1/captcha/generator` yields base64 image and token.
  - Admin modal displays captcha and accepts 6-letter verification string.
  - Ingests multi-year historical prices (e.g. 2021–2025).
  - Feeds historical baseline records into `PriceAnalyticsService` for 12-month seasonality calculations and `PriceForecastService` for AI projection models.

### Step 4: Database Seeder & Registry Updates
- Update `database/seeders/DataSourceSeeder.php` to register `krama_karnataka` as active daily primary.
- Update `DatabaseSeeder.php` and create migration / seeder scripts.
- Ensure all 31 Karnataka districts and canonical mandis are mapped.

### Step 5: Test Coverage & Regression Safety
- Create comprehensive PHPUnit tests:
  - `Tests\Unit\KramaMarketDataProviderTest`: HTML parsing, pricing extraction, variety resolution.
  - `Tests\Unit\AgmarknetHistoricalDataProviderTest`: Captcha request flow and payload structures.
  - `Tests\Feature\KramaIngestionTest`: End-to-end sync, database persistence, and duplicate handling.
- Verify all existing 250 test assertions pass without regression.
