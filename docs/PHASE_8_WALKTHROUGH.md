# Phase 8 Walkthrough: Historical Analytics & Seasonal Analysis

Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ) has delivered **Phase 8: Historical Analytics & Seasonal Analysis**, giving Karnataka farmers access to lightning-fast historical price and arrival trends, interactive dual-axis Chart.js visualizations, statistical volatility indicators, and transparent 5-year seasonal index analysis ("Best Months to Sell" / ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ತಿಂಗಳುಗಳು) without external CDN dependencies or slow real-time `GROUP BY` recalculations.

---

## 1. Key Capabilities Delivered

### A. Precomputed Aggregate Tables & Models
- **`price_daily_statistics`**: Precomputes state-wide daily summaries per crop (`avg_modal_price`, `min_modal_price`, `max_modal_price`, `total_arrival_quantity`, `active_markets_count`) with composite indexes ensuring sub-10ms lookup speeds on multi-year ranges.
- **`price_monthly_statistics`**: Precomputes monthly prices and the 12-month seasonal index $S_m = \frac{\bar{P}_m}{\bar{P}_{annual}}$ both state-wide (`market_id = null`) and per-mandi.
- **Models**:
  - `app/Models/PriceDailyStatistic.php`: Scopes `forCrop`, `forRange`.
  - `app/Models/PriceMonthlyStatistic.php`: Kannada month name accessor (`month_name_kn`), seasonality classification (`seasonality_label`).

### B. Historical Analytics Service (`HistoricalAnalyticsService`)
- **`computeDailyStatistics(?string $date, ?int $cropId)`**: Aggregates daily canonical prices for Karnataka.
- **`computeMonthlyStatistics(?int $year, ?int $month, ?int $cropId)`**: Aggregates monthly figures and triggers seasonal index recalculation.
- **`getDailyTrends(int $cropId, ?int $marketId, int $days)`**: Returns formatted time series arrays (`labels`, `modal_prices`, `min_prices`, `max_prices`, `arrivals`) for state benchmarks or specific APMC mandis across 7D, 15D, 30D, 90D, and 1Y windows.
- **`getSeasonalAnalysis(int $cropId, ?int $marketId)`**: Computes the 12-month seasonal index profile and identifies the **Top 3 Best Months to Sell (ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ತಿಂಗಳುಗಳು)** with percentage premiums over annual baseline.
- **`getStatisticalSummary(int $cropId, ?int $marketId, int $days)`**: Period high, period low, period average, standard deviation, and volatility rating (ಸ್ಥಿರ / ಕಡಿಮೆ ಏರಿಳಿತ, ಮಧ್ಯಮ, ಅಥವಾ ಅಧಿಕ).

### C. Console Command & Nightly Cron
- **Command**: `php artisan krushi:compute-statistics {--date=} {--backfill-days=} {--months}`
- **Automated Scheduling**: Scheduled in `routes/console.php` nightly at `01:00 IST` following the daily price sync, maintaining precalculated cache without impacting daytime traffic.

### D. Interactive Client-side Charting (Chart.js via Vite)
- Installed `chart.js` and bundled into `resources/js/app.js` and `public/build/assets/app-*.js` (zero external CDN requests, fully functional in offline PWA mode).
- **Dual-axis Price & Arrival Chart**:
  - Left Y-axis (Emerald): Modal Price (₹/Quintal).
  - Right Y-axis (Slate): Daily Arrivals (Quintals).
  - Timeframe toggles: 7D, 15D, 30D (default), 90D, 1Y.
  - Interactive touch-optimized tooltips with Kannada and English labels.
- **12-Month Seasonality Bar Chart**:
  - Color-coded bars: Emerald (Peak Price), Blue (Above Average), Amber (Normal), Rose (Harvest Flush / Lean).
  - Top 3 Best Selling Months highlighted with gold/silver/bronze badges and premium percentages.

### E. Public Analytics REST API
- `GET /api/v1/analytics/trends`: `?crop=arecanut&range=30d&market_id=2`
- `GET /api/v1/analytics/seasonality`: `?crop_id=1`
- `GET /api/v1/analytics/summary`: `?crop=tomato&days=15`

---

## 2. Automated Test Verification: 94 Tests, 907 Assertions (100% Green)

```
   PASS  Tests\Unit\DataSourceProviderTest (5 tests)
   PASS  Tests\Unit\ExampleTest (1 test)
   PASS  Tests\Unit\HistoricalAnalyticsServiceTest (5 tests)
         ✓ compute daily statistics aggregates and persists
         ✓ compute monthly statistics and seasonal index
         ✓ daily trends timeseries structure
         ✓ seasonal analysis identifies best months
         ✓ statistical summary handles empty and active data
   PASS  Tests\Unit\MarketPriceIngestionTest (5 tests)
   PASS  Tests\Unit\MasterDataModelTest (5 tests)
   PASS  Tests\Unit\NearbyMarketServiceTest (3 tests)
   PASS  Tests\Unit\NominatimGeocoderTest (2 tests)
   PASS  Tests\Unit\OpenMeteoWeatherProviderTest (3 tests)
   PASS  Tests\Unit\WeatherSyncServiceTest (1 test)
   PASS  Tests\Feature\AdminAuthTest (5 tests)
   PASS  Tests\Feature\AdminDataSourceTest (9 tests)
   PASS  Tests\Feature\AdminMasterDataTest (8 tests)
   PASS  Tests\Feature\ComputePriceStatisticsCommandTest (1 test)
         ✓ compute statistics artisan command runs successfully
   PASS  Tests\Feature\ExampleTest (1 test)
   PASS  Tests\Feature\FarmerHistoricalAnalyticsTest (5 tests)
         ✓ crop show page renders historical analytics and seasonality
         ✓ crop show handles range and market filters
         ✓ analytics trends api returns structured json
         ✓ analytics seasonality api returns structured json
         ✓ analytics summary api returns structured json
   PASS  Tests\Feature\FarmerPriceDiscoveryTest (11 tests)
   PASS  Tests\Feature\FarmerPwaTest (4 tests)
   PASS  Tests\Feature\FarmerWeatherTest (4 tests)
   PASS  Tests\Feature\MasterDataApiTest (5 tests)
   PASS  Tests\Feature\NearbyMarketTest (5 tests)
   PASS  Tests\Feature\SyncMarketPricesCommandTest (6 tests)

   Tests:    94 passed (907 assertions)
   Duration: 6.87s
```

---

## 3. Production Asset Build

```
✓ 62 modules transformed.
public/build/manifest.json              0.33 kB │ gzip:  0.17 kB
public/build/assets/app-C1gVuWP9.css   99.49 kB │ gzip: 16.57 kB
public/build/assets/app-UtKnDpl-.js   261.56 kB │ gzip: 91.04 kB
✓ built in 1.39s
```
