# Phase 8 Implementation Plan: Historical Analytics & Seasonal Analysis

## 1. Overview & Business Objectives
Karnataka farmers need clear, visual price history and seasonal insights to make informed decisions about when to harvest, store, or sell their commodities. Phase 8 builds the **Historical Analytics & Seasonal Analysis Engine** for Krushi Baandhava:
1. **Precomputed Aggregate Tables**: `price_daily_statistics` and `price_monthly_statistics` ensuring sub-20ms query latency on multi-year datasets without slow `GROUP BY` recalculations on every page load.
2. **Interactive Charting (Mobile-First via Chart.js bundled with Vite)**: Farmers can toggle between 7-Day, 30-Day, 90-Day, and 1-Year price and arrival trends with touch-optimized tooltips in Kannada and English.
3. **5-Year Seasonal Index & "Best Months to Sell" (ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ತಿಂಗಳುಗಳು)**: Calculates the normalized 12-month seasonal price index $S_m = \frac{\bar{P}_m}{\bar{P}_{annual}}$ across 3 to 5 years, highlighting high-price selling windows vs. harvest flush lean months.
4. **State-Level vs. Local Mandi Comparison**: Enables farmers to compare their local APMC mandi price trend against the Karnataka state-wide benchmark.
5. **Nightly Aggregation Engine**: Console command `krushi:compute-statistics` running on a non-blocking cron schedule.
6. **Public Analytics REST API**: Endpoints for trends, seasonality, and statistical summaries.

---

## 2. Proposed Changes

### Database Layer
- **[NEW] `database/migrations/2026_09_23_000002_create_price_statistics_tables.php`**:
  - `price_daily_statistics`: `id`, `crop_id`, `variety_id` (nullable), `state_id`, `record_date`, `avg_modal_price`, `min_modal_price`, `max_modal_price`, `total_arrival_quantity`, `active_markets_count`, unique index `(crop_id, variety_id, state_id, record_date)`.
  - `price_monthly_statistics`: `id`, `crop_id`, `variety_id` (nullable), `market_id` (nullable for state-wide), `year`, `month`, `avg_modal_price`, `min_price`, `max_price`, `seasonal_index`, `observations_count`, unique index `(crop_id, variety_id, market_id, year, month)`.

### Models Layer
- **[NEW] `app/Models/PriceDailyStatistic.php`**:
  - Casts for dates and decimals, scopes `forCrop`, `forRange`, relationships to `Crop`, `CropVariety`, `State`.
- **[NEW] `app/Models/PriceMonthlyStatistic.php`**:
  - Casts, scopes `forCrop`, `forMarket`, relationships to `Crop`, `CropVariety`, `Market`. Accessor for Kannada month name (`month_name_kn`).

### Analytics Service Layer
- **[NEW] `app/Services/Analytics/HistoricalAnalyticsService.php`**:
  - `computeDailyStatistics(?string $date = null)`: Aggregates daily canonical prices for Karnataka.
  - `computeMonthlyStatistics(?int $year = null, ?int $month = null)`: Aggregates monthly prices & updates seasonal indices.
  - `getDailyTrends(int $cropId, ?int $marketId = null, int $days = 30)`: Daily time series (dates, modal prices, min, max, arrivals).
  - `getSeasonalAnalysis(int $cropId, ?int $marketId = null)`: 12-month seasonal index profile with classifications (Peak Price Window / ಸಾಧಾರಣ / ಕುಸಿತದ ಅವಧಿ) and "Best Months to Sell" recommendations.
  - `getStatisticalSummary(int $cropId, ?int $marketId = null, int $days = 30)`: Min, max, average, standard deviation/volatility, arrival sums, observation count.

### Console Command & Cron Schedule
- **[NEW] `app/Console/Commands/ComputePriceStatisticsCommand.php`**:
  - `krushi:compute-statistics {--date=} {--backfill-days=} {--months}`: Command to calculate daily and monthly stats with backfill support.
- **[MODIFY] `routes/console.php`**:
  - Schedule `krushi:compute-statistics` nightly at `01:00` following price ingestion.

### Frontend & UI Layer
- **[MODIFY] `package.json`**:
  - Install `chart.js` for standalone, dependency-free offline-capable chart rendering bundled into `public/build`.
- **[MODIFY] `resources/js/app.js`**:
  - Import Chart.js and register chart components or make accessible to Blade templates via clean event-driven scripts.
- **[MODIFY] `app/Http/Controllers/Farmer/CropController.php`**:
  - In `show()` method, load historical trend data and seasonal analysis so the crop profile features an interactive price chart and "Best Months to Sell" card.
- **[MODIFY] `resources/views/farmer/crops/show.blade.php`**:
  - Add interactive Chart.js price/arrival trend chart with timeframe buttons (7D, 30D, 90D, 1Y).
  - Add "Best Months to Sell (ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ತಿಂಗಳುಗಳು)" seasonal indicator card with visual seasonal index meter.
  - Add statistical summary pills (Highest 30D, Lowest 30D, 30D Average, Volatility rating).

### API Layer
- **[NEW] `app/Http/Controllers/Api/V1/AnalyticsApiController.php`**:
  - `GET /api/v1/analytics/trends`: Returns daily/monthly price series for charts.
  - `GET /api/v1/analytics/seasonality`: Returns 12-month seasonal index and selling recommendations.
  - `GET /api/v1/analytics/summary`: Returns statistical summary for crop/market.
- **[MODIFY] `routes/api.php`**:
  - Register `/api/v1/analytics/*` routes.

---

## 3. Verification Plan

### Automated Tests
- `tests/Unit/HistoricalAnalyticsServiceTest.php`:
  - Verify daily aggregation calculation accuracy.
  - Verify monthly aggregation & 5-year seasonal index calculation formula ($S_m = \bar{P}_m / \bar{P}_{annual}$).
  - Verify data-deficiency handling (empty datasets do not error out).
  - Verify statistical summary (min, max, average, std dev).
- `tests/Feature/ComputePriceStatisticsCommandTest.php`:
  - Run `krushi:compute-statistics` and assert records exist in `price_daily_statistics` and `price_monthly_statistics`.
- `tests/Feature/FarmerHistoricalAnalyticsTest.php`:
  - Verify crop show page loads with chart data and seasonal index.
  - Verify public analytics REST API endpoints return 200 OK with valid JSON structure.
- Total test suite execution: `artisan test` to ensure 0 regressions across all existing 83 tests.
- Build verification: `npm run build` to ensure Chart.js builds cleanly with Vite.
