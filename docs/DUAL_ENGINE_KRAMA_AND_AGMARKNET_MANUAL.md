# DUAL-ENGINE DATA ARCHITECTURE & OPERATOR MANUAL
## KRAMA (Primary Live Karnataka Feed) & Official AGMARKNET (Multi-Year Historical & Predictions)

**Project:** Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)  
**System:** Agricultural Market Intelligence & APMC Daily Auction Platform  
**Document:** Operator & Technical Reference Manual  
**Status:** Implemented & Verified in Production  

---

## 1. Overview & Architectural Motivation

In Karnataka, agricultural market prices originate from two distinct administrative layers:
1. **State Level (ReMS / KRAMA - Karnataka State Agricultural Marketing Board)**:
   - Every electronic auction lot conducted across Karnataka APMCs is registered first directly in the state e-tender / ReMS system.
   - Web Portal: `https://krama.karnataka.gov.in`
   - Provides **100% authentic, real-time, same-day auction results** with commercial market varieties (`Rashi`, `Bette`, `Gorabalu`, `Chali`, `Sona Masuri`, `Jyothi`, `Local`, etc.).
   - Does **not require CAPTCHA or API keys** for public daily commodity auction reports, making it fully automated for background cron jobs.

2. **National Level (AGMARKNET - Directorate of Marketing & Inspection, MoA&FW, GoI)**:
   - Aggregates mandi prices from all Indian states.
   - Official API: `https://api.agmarknet.gov.in/v1/`
   - Holds **18+ years of historical multi-year records** (2007–present), making it the gold standard for long-term seasonal indexing and AI price forecasting.
   - Imposes a **6-letter visual CAPTCHA** or DMI token on bulk multi-year report queries, making it ideal for scheduled on-demand admin-supervised ingestion.

---

## 2. Source Hierarchy & Roles in Krushi Baandhava

| Data Source | Badge / Label in Admin Panel | Role | Update Frequency | Automation Type |
| :--- | :--- | :--- | :--- | :--- |
| **KRAMA (Karnataka APMC Board)** | `🥇 Primary Live Karnataka Source (Daily APMC Feed)` | Live daily auction prices for all Karnataka crops | Daily (afternoon auctions) | Fully automated background cron |
| **Official AGMARKNET (DMI / GoI)** | `📜 Official AGMARKNET (Multi-Year Historical & Predictions)` | Multi-year historical archives & AI prediction baseline | On-demand / Monthly | Interactive Admin CAPTCHA Sync |
| **Coffee Board of India** | `Official Board` | Arabica & Robusta daily market rates | Daily | Automated web scraper |
| **Coconut Development Board** | `Official Board` | Coconut, Copra (Milling/Ball) & Coconut Oil rates | Daily | Automated web scraper |
| **TSS Sirsi Cooperative** | `Cooperative Society` | Tender auction rates for Sirsi arecanut grades | Daily | Automated web scraper |
| **data.gov.in Mandi API** | `🔌 data.gov.in (National Fallback Feed)` | Secondary national APMC fallback | As configured | API key automated |

---

## 3. How to Use the Admin Panel Data Sources Screen

Navigate to: `http://localhost/Krushi-Baandhava-application/public/admin/datasources`

### A. KRAMA Karnataka (Primary Live Source)
- **Status Indicator**: Shows green `Active` badge and schedule (`Daily @ 16:30`).
- **Immediate Live Sync**: Click the green **"Sync"** button to fetch today's auction lots across all Karnataka APMC yards.
- **Diagnostics**: Click **"Connection Test"** to test live HTTP connectivity to `krama.karnataka.gov.in`.

### B. Official AGMARKNET (Historical & Predictions)
- **Status Indicator**: Shows blue `Active (Manual / Captcha)` badge.
- **Visual CAPTCHA Ingestion**:
  1. Click the blue **"🔑 Captcha Sync"** button.
  2. A popup modal opens and automatically requests a fresh visual CAPTCHA image directly from `https://api.agmarknet.gov.in/v1/captcha/generator`.
  3. Enter the 6 letters shown in the CAPTCHA image. (If difficult to read, click 🔄 to reload).
  4. Select the target crop (e.g., *Arecanut*, *Tomato*, *Paddy*, or *All Active Crops*).
  5. Select the historical window (e.g. *90 Days*, *1 Year*, *3 Years*).
  6. Check **"Run Price Forecasting Engine"** to immediately update 7-day, 14-day, and 30-day projection curves.
  7. Click **"Verify & Run Historical Sync"**.

---

## 4. Console & Artisan Commands

### 1. Ingest Daily Live Prices from KRAMA
```bash
# Ingest today's APMC auction rates from KRAMA
php artisan krushi:sync-market-prices krama_karnataka

# Dry-run mode (validates HTML parsing without writing to database)
php artisan krushi:sync-market-prices krama_karnataka --dry-run

# Force sync specific date
php artisan krushi:sync-market-prices krama_karnataka --date=2026-09-28 --force
```

### 2. Ingest Multi-Year Historical Archives from Official AGMARKNET
```bash
# Backfill last 90 days for Paddy and generate AI forecasts
php artisan krushi:sync-agmarknet-historical --crop=Paddy --days=90 --forecast

# Dry-run preview
php artisan krushi:sync-agmarknet-historical --crop=Tomato --days=30 --dry-run

# Backfill up to 3 years of archives for all active crops
php artisan krushi:sync-agmarknet-historical --days=1095 --forecast
```

### 3. Legacy CEDA Forwarding Alias
Any cron script or tool still calling `krushi:sync-ceda-historical` will automatically display a notice and forward execution to `krushi:sync-agmarknet-historical`:
```bash
php artisan krushi:sync-ceda-historical --crop=Paddy --days=90
```

### 4. Safe Historical Data Pruning & Archival
```bash
# Prune daily records older than 365 days while preserving monthly statistical baselines
php artisan krushi:prune-historical-prices --age=365

# Prune specific date range
php artisan krushi:prune-historical-prices --from=2024-01-01 --to=2024-12-31 --force
```

---

## 5. cPanel Automated Cron Configuration

In your cPanel cron jobs dashboard, schedule the automated daily sync:

```cron
# 1. Daily APMC Live Auction Sync from KRAMA (Runs daily at 4:30 PM & 6:30 PM)
30 16,18 * * * /usr/local/bin/php /home/username/public_html/artisan krushi:sync-market-prices krama_karnataka >> /dev/null 2>&1

# 2. Daily Commodity Boards & Cooperative Societies Sync (Runs daily at 11:30 AM & 5:00 PM)
30 11,17 * * * /usr/local/bin/php /home/username/public_html/artisan krushi:sync-market-prices coffee_board >> /dev/null 2>&1
35 11,17 * * * /usr/local/bin/php /home/username/public_html/artisan krushi:sync-market-prices coconut_board >> /dev/null 2>&1
40 11,17 * * * /usr/local/bin/php /home/username/public_html/artisan krushi:sync-market-prices tss_sirsi >> /dev/null 2>&1

# 3. Daily Analytics & Statistics Aggregation (Runs daily at 7:30 PM)
30 19 * * * /usr/local/bin/php /home/username/public_html/artisan krushi:compute-statistics >> /dev/null 2>&1

# 4. Nightly Price Forecast Generation (Runs nightly at 8:00 PM)
00 20 * * * /usr/local/bin/php /home/username/public_html/artisan krushi:generate-forecasts >> /dev/null 2>&1
```

---

## 6. Implementation Code Map

| File Path | Description |
| :--- | :--- |
| `app/Services/DataSources/Krama/KramaMarketDataProvider.php` | KRAMA HTML report scraper, ASP.NET postback handler, and record normalizer |
| `app/Services/DataSources/Agmarknet/AgmarknetHistoricalDataProvider.php` | Official AGMARKNET API client with CAPTCHA generation, verification, and multi-year ingestion |
| `app/Services/DataSources/DataSourceRegistry.php` | Central provider registry binding provider classes to data source codes |
| `app/Http/Controllers/Admin/DataSourceController.php` | Controller handling CAPTCHA generation API (`agmarknetCaptcha`) and sync trigger (`agmarknetHistoricalSync`) |
| `resources/views/admin/datasources/index.blade.php` | Admin UI featuring source hierarchy badges, connection diagnostics, and Alpine.js CAPTCHA modal |
| `app/Console/Commands/SyncAgmarknetHistoricalPricesCommand.php` | Console command `krushi:sync-agmarknet-historical` |
| `app/Console/Commands/SyncCedaHistoricalPricesCommand.php` | Backward-compatibility alias forwarding to official AGMARKNET |
| `database/seeders/KramaAndAgmarknetSeeder.php` | Database seeder establishing `krama_karnataka` and `agmarknet_official` data sources and crop mappings |
| `tests/Feature/KramaAndAgmarknetIntegrationTest.php` | Automated test suite verifying KRAMA parsing, mock fetch, health check, CAPTCHA endpoint, and dry runs |
| `tests/Feature/CedaAgmarknetIntegrationTest.php` | Automated test suite verifying migration and command delegation |

---

## 7. Verification & Quality Assurance

All automated test suites pass with 100% green assertions:
- `KramaAndAgmarknetIntegrationTest`: **7/7 PASSED** (38 assertions)
- `CedaAgmarknetIntegrationTest`: **4/4 PASSED** (17 assertions)
- `AdminDataSourceTest`: **12/12 PASSED** (73 assertions)
- `AdminPriceRetentionAndRangeSyncTest`: **10/10 PASSED** (83 assertions)
- `CommodityBoardPricesTest`: **4/4 PASSED** (21 assertions)
- `SirsiTumakuruAndSchedulingTest`: **5/5 PASSED** (29 assertions)
- `FarmerMarketDiscoveryAndSortingTest`: **6/6 PASSED** (21 assertions)
- `PwaAndSeoTest`: **6/6 PASSED** (51 assertions)
- `AdminCropImageAndMissedCropsTest`: **5/5 PASSED** (68 assertions)
- `AdminCropVarietyMappingTest`: **8/8 PASSED** (46 assertions)
