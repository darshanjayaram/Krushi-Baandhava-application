# Phase-Wise Implementation Plan: Historical Date-Range Sync, Data Explorer & Safe Pruning

## 1. Executive Summary & Architectural Goals
This plan specifies the implementation of:
1. **Historical Date-Range Ingestion Tool**: Calendar pickers (`from_date` to `to_date`), quick presets (30d, 90d, 1yr), and optional single-crop targeting to backfill historical APMC auction data directly from the Admin Panel.
2. **Year & Month Data Explorer**: Granular year-wise and month-wise filters, paired with a visual Archive Volume & Storage Breakdown drawer (tracking records count, active mandis, and estimated disk footprint).
3. **Safe Pruning & Retention Manager**: Admin-controlled data pruning (by age or specific year/month) equipped with a **Pre-Aggregation Safety Guard** that guarantees `price_monthly_statistics` are compiled and preserved before raw daily rows are removed—ensuring "Best Months to Sell" and long-term trend charts never break.
4. **Deterministic & cPanel Optimized**: Zero regression on existing single-day sync or public farmer endpoints, sub-second query performance via composite indexes, and strict chunking to avoid cPanel execution timeouts.

---

## 2. Technical Architecture & Data Flow

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           Admin Panel (/admin/prices)                           │
├───────────────────────────────┬────────────────────────────────┬────────────────┤
│ 1. Range Sync Modal (From/To) │ 2. Year/Month Data Explorer    │ 3. Prune Tool  │
└───────────────┬───────────────┴────────────────┬───────────────┴────────┬───────┘
                │                                │                        │
                ▼                                ▼                        ▼
┌───────────────────────────────┐ ┌──────────────────────────────┐ ┌──────────────┐
│ MarketPriceController@sync    │ │ MarketPriceController@index  │ │ @prune       │
│ - Accepts from_date & to_date │ │ - Year & Month filters       │ │ - Guard: pre-│
│ - Chunked range ingestion     │ │ - GROUP BY Year, Month stats │ │   aggregate  │
│ - Deduplicated via SHA-256    │ │ - Instant pagination         │ │ - Audit log  │
└───────────────┬───────────────┘ └──────────────────────────────┘ └──────┬───────┘
                │                                                         │
                ▼                                                         ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           Database Tables (MySQL InnoDB)                        │
├────────────────────────────────────────┬────────────────────────────────────────┤
│ market_prices (Active 365-day rolling) │ price_monthly_statistics (Permanent)   │
│ • Daily prices per crop, variety, mandi│ • 1 row/month/crop/variety/market      │
│ • Pruned safely when older than 1 yr   │ • Powers "Best Months" & 5-year trends │
└────────────────────────────────────────┴────────────────────────────────────────┘
```

---

## 3. Phase-by-Phase Implementation

### Phase 1: Backend Routing, Ingestion Range & Automated 1-Year Rolling Retention Engine
* **Goal**: Support date-range backfills, safe data pruning, and the automated 1-year rolling retention cycle in controllers and console commands.
* **Tasks**:
  1. **Automated 1-Year Rolling Retention Command (`app/Console/Commands/PruneHistoricalMarketPricesCommand.php`)**:
     * Signature: `krushi:prune-prices {--days=365 : Number of rolling days to retain in daily table}`
     * **Safety Rollup**: Runs automatically before deletion. Compiles any daily records older than 365 days into `price_monthly_statistics` (1 row per month/crop/variety/market).
     * **Daily Table Cleanup**: Deletes records older than 365 days from `market_prices`.
     * **cPanel Scheduling (`routes/console.php`)**: Scheduled to run nightly at 23:00 (11 PM) after the daily 18:00 sync, ensuring the database is **permanently capped at ~35 MB** automatically with zero manual effort.
  2. **Route Definition (`routes/web.php`)**:
     * Add `POST /admin/prices/sync-range` $\rightarrow$ `MarketPriceController@syncRange`
     * Add `POST /admin/prices/prune` $\rightarrow$ `MarketPriceController@prune`
  3. **Controller Enhancements (`app/Http/Controllers/Admin/MarketPriceController.php`)**:
     * In `index()`:
       * Add query filters for `$year` and `$month`.
       * Compute Month-wise & Year-wise breakdown summary:
         ```sql
         SELECT YEAR(price_date) as yr, MONTH(price_date) as mo, 
                COUNT(*) as records_count, COUNT(DISTINCT market_id) as mandis_count,
                AVG(modal_price) as avg_price, MIN(price_date) as min_date, MAX(price_date) as max_date
         FROM market_prices
         GROUP BY YEAR(price_date), MONTH(price_date)
         ORDER BY yr DESC, mo DESC
         ```
     * In `syncRange(Request $request)`:
       * Validate `from_date` and `to_date` (`to_date >= from_date`, max span 365 days per batch).
       * Delegate to `MarketPriceIngestionService` with date boundaries.
       * For CEDA Agmarknet, pass `from_date` and `to_date` directly to the API endpoint.
       * If checkbox `update_analytics` is true, trigger `HistoricalAnalyticsService::computeMonthlyStatistics()` and `ForecastingEngineService`.
     * In `prune(Request $request)`:
       * Default preset: **"Prune data older than 1 Year (Keep 365-Day Rolling Window)"**.
       * Other options: `older_than_days` (180, 90) OR specific `year` / `month`.
       * **Safety Guard**: Compile/ensure `price_monthly_statistics` exists for the targeted period before running deletion.
       * Delete from `market_prices` (and optional cascade to orphaned `market_price_raw`).
       * Log action in `AuditLog` (records deleted, date range, user ID).
       * Return clear flash message.

---

### Phase 2: User Interface Upgrades in Admin Panel (`admin/prices/index.blade.php`)
* **Goal**: Deliver a polished, dark-theme-consistent interface matching the existing dashboard style (`bg-slate-900 border-slate-800 text-white`).
* **Tasks**:
  1. **Upgrade Ingestion Sync Modal**:
     * Add mode toggle: `Single Date` vs `Date Range (Historical Backfill)`.
     * Add HTML5 `<input type="date">` for `From Date` and `To Date`.
     * Add 1-click Quick Presets:
       * `Last 30 Days` (sets from = today - 30d, to = today)
       * `Last 90 Days` (sets from = today - 90d, to = today)
       * `Past 1 Year (365 Days)` (sets from = today - 365d, to = today)
     * Add checkbox: `☑ Pre-calculate Monthly Statistics & Forecasts`.
  2. **Add Year & Month Filter Controls**:
     * Add `Year` select dropdown (`All Years`, dynamic list from existing data).
     * Add `Month` select dropdown (`All Months`, Jan..Dec in English & Kannada).
  3. **Add "Archive & Volume Breakdown" Collapsible Drawer**:
     * Shows a structured table: Year/Month, Record Count, Active Mandis, Avg Price, Estimated Size.
     * Actions per row: `🔍 Filter This Month` and `🗑️ Prune Month`.
  4. **Add Dedicated "Data Retention & Prune" Modal**:
     * Options:
       * `(●) Keep last 365 days (Prune data older than 1 year - Recommended)`
       * `( ) Keep last 180 days (Prune data older than 6 months)`
       * `( ) Delete specific Year / Month`
     * Visual Safety Guarantee indicator: confirms monthly averages are preserved.
     * Dangerous action confirmation button (`Delete & Free Up Space`).

---

### Phase 3: Integration, Regression Prevention & Verification
* **Goal**: Guarantee zero regressions and total stability.
* **Tasks**:
  1. Verify single-date sync continues to work without disruption.
  2. Verify all cPanel constraints: query execution times under 50ms, memory overhead under 16MB.
  3. Create automated tests in `tests/Feature/AdminPriceRetentionAndRangeSyncTest.php`:
     * Test admin can perform date-range sync.
     * Test admin can filter by year and month.
     * Test safe prune removes raw daily rows while verifying monthly summary records remain intact.
     * Test unauthenticated or unauthorized users are forbidden.
  4. Run full test suite (`php vendor/bin/phpunit`) to confirm all tests pass.

---

## 4. Risks & Mitigations

| Risk | Cause | Mitigation |
| :--- | :--- | :--- |
| **API Timeout during 1-year sync** | External API rate limiting or large payload | Chunk date range into 30-day or 90-day intervals if needed, or set HTTP client timeout to 60s. |
| **Accidental loss of trend data** | Admin deletes past year daily prices | Pre-aggregation safety check automatically runs first, guaranteeing `price_monthly_statistics` exists before rows are deleted. |
| **cPanel Lockup on massive delete** | Deleting 100k+ rows at once in single query | Chunk deletions in batches of 5,000 with `DB::transaction()` to prevent table locks. |
