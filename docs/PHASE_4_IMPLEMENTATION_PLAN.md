# Phase 4: data.gov.in Ingestion Pipeline & Deduplication Implementation Plan

Phase 4 implements the production data ingestion pipeline for **Krushi Baandhava**. It connects the data source framework established in Phase 3 to persistent raw and canonical market price tables, with SHA-256 checksum deduplication, alias resolution, sanity checks, Artisan CLI execution, and cPanel-friendly scheduled jobs.

---

## Architecture Principles

1. **Two-Tier Storage**: Incoming API records are first stored verbatim in `market_price_raw` with a SHA-256 checksum of the normalized payload. Only validated, normalized records with resolved foreign keys (`crop_id`, `market_id`, `district_id`) are inserted into the canonical `market_prices` table.
2. **Zero-Duplicate Ingestion**: Consecutive cron runs fetching the same day's mandi prices will recognize existing checksums and mark them as duplicates without modifying canonical prices or corrupting historical analytics.
3. **cPanel Shared Hosting Compatibility**: The ingestion process runs via `php artisan krushi:sync-market-prices` and queued database jobs (`php artisan queue:work --stop-when-empty`), ensuring zero dependency on daemon supervisors.

---

## Proposed Changes

### 1. Database Migrations
#### `database/migrations/2026_09_23_000005_create_market_prices_and_raw_tables.php`
- `market_price_raw`:
  - `id` (bigint PK)
  - `data_source_id` (FK -> `data_sources.id`, cascade)
  - `external_record_id` (varchar 150, nullable)
  - `payload` (json)
  - `checksum` (char 64, index) — SHA-256 of canonical payload string
  - `received_at` (timestamp)
  - `processed_at` (timestamp, nullable)
  - `processing_status` (string 50: 'pending', 'processed', 'duplicate', 'rejected', 'failed')
  - `error_message` (text, nullable)
  - Indexes: `(data_source_id, processing_status)`, `(checksum)`
- `market_prices` (Canonical Record):
  - `id` (bigint PK)
  - `crop_id` (FK -> `crops.id`, cascade)
  - `variety_id` (FK -> `crop_varieties.id`, nullable)
  - `market_id` (FK -> `markets.id`, cascade)
  - `district_id` (FK -> `districts.id`, cascade)
  - `price_date` (date)
  - `min_price` (decimal 10,2)
  - `max_price` (decimal 10,2)
  - `modal_price` (decimal 10,2)
  - `arrival_quantity` (decimal 12,2, default 0)
  - `unit` (varchar 50, default 'Quintal')
  - `data_source_id` (FK -> `data_sources.id`)
  - `raw_record_id` (FK -> `market_price_raw.id`, nullable)
  - timestamps
  - Unique constraint: `(crop_id, variety_id, market_id, price_date, data_source_id)`
  - Composite indexes:
    - `idx_crop_market_date`: `(crop_id, market_id, price_date)`
    - `idx_market_date`: `(market_id, price_date)`
    - `idx_crop_date_modal`: `(crop_id, price_date, modal_price)`
    - `idx_district_date`: `(district_id, price_date)`
- `market_arrivals` (Companion Arrivals):
  - `id` (bigint PK)
  - `market_id`, `crop_id`, `variety_id` (nullable), `arrival_date`, `quantity`, `unit`, `data_source_id`, timestamps.

---

### 2. Eloquent Models
#### `app/Models/MarketPriceRaw.php`
- Relations: `dataSource`, `canonicalPrice`.
- Scopes: `scopePending`, `scopeDuplicate`, `scopeProcessed`.
#### `app/Models/MarketPrice.php`
- Relations: `crop`, `variety`, `market`, `district`, `dataSource`, `rawRecord`.
- Scopes: `scopeToday`, `scopeForCrop`, `scopeForMarket`, `scopeForDistrict`, `scopeDateRange`.
- Accessor: `price_change_direction` (computes up `↑`, down `↓`, stable `→` relative to previous observation).
#### `app/Models/MarketArrival.php`
- Relations: `market`, `crop`, `variety`, `dataSource`.

---

### 3. Ingestion & Normalization Engine
#### `app/Services/Ingestion/MarketPriceIngestionService.php`
- Ingests from any active `DataSource` (defaults to `data_gov_mandi`).
- Computes SHA-256 checksum of raw record.
- Identifies duplicates without querying external APIs repeatedly.
- Normalizes raw field values using provider adapter.
- Resolves canonical entities:
  - Looks up `crop_source_mappings` -> fallback to exact name or slug in `crops`.
  - Looks up `market_source_mappings` -> fallback to exact APMC name or code in `markets`.
  - Resolves variety or defaults to crop's primary/first variety.
- Enforces strict sanity checks:
  - `modal_price > 0`
  - `min_price <= modal_price <= max_price` (swaps or derives if bounds inverted)
  - `price_date` not in the future
- Persists valid records to `market_prices` with `updateOrCreate` on unique constraint.
- Updates `SyncLog` execution record.

---

### 4. Artisan CLI Command & Scheduled Jobs
#### `app/Console/Commands/SyncMarketPricesCommand.php`
- Signature: `krushi:sync-market-prices {source? : Specific data source code} {--force} {--dry-run}`
- Interactive console with progress bar, detailed counts (received, inserted, updated, duplicate, rejected), and execution duration.
#### `app/Jobs/SyncMarketPricesJob.php`
- Asynchronous queue job dispatchable by admin UI "Sync Now" button or scheduler.
#### `routes/console.php`
- Schedules `krushi:sync-market-prices` daily at `06:00` and `18:00` IST via Laravel Scheduler.

---

### 5. Admin Live Ingestion Viewer
#### `app/Http/Controllers/Admin/MarketPriceController.php`
- `index()`: Paginated view of canonical imported prices with search/filters by crop, market, district, and date.
#### `resources/views/admin/prices/index.blade.php`
- Modern Tailwind view showing latest prices, freshness badges, arrival quantities, and source provenance chips.
- Add link in `resources/views/layouts/admin.blade.php` sidebar.

---

## Verification Plan

### Automated Tests
1. **`tests/Unit/MarketPriceIngestionTest.php`**:
   - Verify SHA-256 checksum generation and deduplication.
   - Verify alias resolution (e.g. "Betelnut" resolving to Arecanut).
   - Verify sanity checks reject invalid prices (`modal <= 0`) and record rejection reasons.
2. **`tests/Feature/SyncMarketPricesCommandTest.php`**:
   - Test `php artisan krushi:sync-market-prices` executes and populates `market_prices`.
   - Test second run detects duplicates correctly.
   - Test `--dry-run` flag does not mutate database.
   - Test admin prices view (`/admin/prices`) displays imported data.
3. **Execution**:
   ```powershell
   php artisan test
   npm run build
   ```
