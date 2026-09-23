# Implementation Plan: Phase 12 — Complete Admin Control & Data Quality Center

Phase 12 delivers the command and governance core of the Krushi Baandhava platform. It equips platform administrators with complete oversight and controls: live operational analytics, instant feature flag switches, configuration management, automated data quality inspection with 1-click reprocessing of rejected market feeds, raw alias resolvers, and an immutable audit trail.

## User Review Required

> [!IMPORTANT]
> - All administrative actions (toggling feature flags, updating system settings, resolving mappings, reprocessing/dismissing raw price records) are recorded in `audit_logs` with admin identity, client IP, and diffs.
> - Feature flags and system settings use Laravel Cache with 5-minute TTL; updating or toggling instantly invalidates the corresponding cache key (`feature_flag_{key}` and `system_setting_{key}`).
> - When an unresolved commodity or mandi alias is resolved, the system can automatically trigger reprocessing of all pending or rejected raw records that match the newly mapped alias.

---

## Proposed Changes

### 1. Ingestion Monitoring & Operational Analytics Dashboard

#### [MODIFY] [`app/Http/Controllers/Admin/DashboardController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Admin/DashboardController.php)
- Expand dashboard metrics:
  - **Mandi Reporting Coverage**: Mandis reporting prices today vs total mandis and % coverage.
  - **Daily Ingestion Stats**: Raw records received today, canonical records created/updated, duplicates suppressed, rejected records.
  - **Data Feeds Status**: Last sync timestamps and status badges for active data sources (`data_gov_mandi`, Open-Meteo, etc.).
  - **Forecasting Engine Health**: Active models, total generated projections, and average accuracy metric (MAPE).
  - **Recent Data Quality Alerts**: Top unmapped aliases or validation errors needing attention.

#### [MODIFY] [`resources/views/admin/dashboard.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/admin/dashboard.blade.php)
- Replace static cards with a responsive, modern dark-slate dashboard layout:
  - KPI metric grid (Total Mandis & Today's reporting coverage %, Ingestion Volume & Quality Rate, Active Crops, Active Feature Flags).
  - Data Source feed health status table (Endpoint, Last Run, Duration, Status, Quick Run button).
  - Quick operational actions bar ("Sync Market Prices", "Recalculate Stats", "Run Forecast Models").
  - Latest Unresolved Mappings & Rejected Records alert box.
  - Live Audit Log preview.

---

### 2. Feature Flags Management Center

#### [NEW] [`app/Http/Controllers/Admin/FeatureFlagController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Admin/FeatureFlagController.php)
- `index()`: Display list of all feature flags with status badges, category filters, and quick toggle buttons.
- `toggle(FeatureFlag $flag)`: Toggle flag status, invalidate cache, write audit log, and return back or JSON for Alpine.js.
- `update(Request $request, FeatureFlag $flag)`: Update name, description, or status, invalidate cache, and write audit log.

#### [NEW] [`resources/views/admin/feature_flags/index.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/admin/feature_flags/index.blade.php)
- Full admin UI with search, status filters (Enabled / Disabled / All), switch toggles, and edit modal/inline form.

---

### 3. Application System Settings Manager

#### [NEW] [`app/Http/Controllers/Admin/SystemSettingController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Admin/SystemSettingController.php)
- `index()`: Display settings grouped into clean tabs:
  - **General**: Application name, pagination limit, maintenance mode.
  - **Localization**: Default state, default district, default language.
  - **Forecasting**: Minimum observations required, active horizons (1/7/15/30 days).
  - **Analytics**: Seasonality evaluation years, historical lookback window.
  - **Sync & Feeds**: Price sync frequency, weather sync frequency.
  - **Performance**: Cache lifetime duration.
- `update(Request $request)`: Validates input according to setting type (`string`, `integer`, `boolean`, `json`), updates records, clears cache, writes audit log, and returns with a success message.

#### [NEW] [`resources/views/admin/settings/index.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/admin/settings/index.blade.php)
- Alpine.js tabbed interface for setting groups, with type-specific inputs (toggles for booleans, number inputs, selects, tags/JSON editors).

---

### 4. Data Quality & Rejected Record Inspector

#### [MODIFY] [`app/Services/Ingestion/MarketPriceIngestionService.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Services/Ingestion/MarketPriceIngestionService.php)
- Add `reprocessRawRecord(MarketPriceRaw $rawRecord): array`:
  - Re-executes entity resolution (crop, variety, market) and sanity validation.
  - If valid, updates or creates canonical `MarketPrice`, updates raw record to `processed`, and clears `error_message`.
  - If still invalid, keeps `rejected` with updated error message.
- Add `reprocessBatch(?int $dataSourceId = null, ?string $reasonKeyword = null): array`:
  - Batch reprocessing method returning summary counts (`processed`, `still_rejected`).

#### [NEW] [`app/Http/Controllers/Admin/DataQualityController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Admin/DataQualityController.php)
- `index(Request $request)`: List records from `market_price_raw` with filters (status: rejected, duplicate, pending; data source; search error message).
- `reprocess(MarketPriceRaw $rawRecord)`: Reprocess single record via service, log audit, and flash result.
- `reprocessAll(Request $request)`: Reprocess all rejected records (or filtered subset), log audit, and flash result.
- `destroy(MarketPriceRaw $rawRecord)`: Delete corrupted raw record, log audit.

#### [NEW] [`resources/views/admin/data_quality/index.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/admin/data_quality/index.blade.php)
- Tabular inspector with payload inspection modal, error badges, quick reprocess buttons, and batch reprocess controls.

---

### 5. Unresolved Mapping Resolver

#### [NEW] [`app/Http/Controllers/Admin/UnresolvedMappingController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Admin/UnresolvedMappingController.php)
- `index()`: Scans `market_price_raw` for distinct unmapped raw crop and mandi aliases from rejected payloads.
- `resolveCrop(Request $request)`: Creates a `CropSourceMapping`, automatically reprocesses all matching rejected records, logs audit.
- `resolveMarket(Request $request)`: Creates a `MarketSourceMapping`, automatically reprocesses all matching rejected records, logs audit.

#### [NEW] [`resources/views/admin/data_quality/unresolved.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/admin/data_quality/unresolved.blade.php)
- Unresolved alias resolution queue: displays unrecognized raw strings, frequency count, and instant dropdown resolver with 1-click "Map & Reprocess" button.

---

### 6. Administrative Audit Logs Viewer

#### [NEW] [`app/Http/Controllers/Admin/AuditLogController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Admin/AuditLogController.php)
- `index(Request $request)`: Filterable listing by action, admin user, entity type, and date range.
- `show(AuditLog $auditLog)`: JSON modal or detail drawer showing old vs new values diff.

#### [NEW] [`resources/views/admin/audit_logs/index.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/admin/audit_logs/index.blade.php)
- Dedicated audit log screen with search, action category badges, user attribution, and expandable JSON payload diffs.

---

### 7. Layout & Navigation Wiring

#### [MODIFY] [`resources/views/layouts/admin.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/layouts/admin.blade.php)
- Connect sidebar navigation links:
  - Feature Flags (`admin.feature-flags.index`)
  - System Settings (`admin.settings.index`)
  - Data Quality & Rejected Records (`admin.data-quality.index`)
  - Unresolved Aliases (`admin.unresolved-mappings.index`)
  - Audit Trail (`admin.audit-logs.index`)

#### [MODIFY] [`routes/web.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/routes/web.php)
- Register all routes under `Route::middleware(['auth', 'admin'])->prefix('admin')`.

---

## Verification Plan

### Automated Tests
Run PHPUnit feature and unit tests to ensure 100% test passing:
- `& "C:\Program Files\php-8.2.30\php.exe" artisan test --filter=AdminAnalyticsAndSettingsTest`
- Test full test suite: `& "C:\Program Files\php-8.2.30\php.exe" artisan test` (must achieve >135 green tests).
- Front-end build verification: `npm run build`.

### Manual / Browser Verification
- Login to Admin panel at `/admin/dashboard`.
- Verify KPI cards, live reporting coverage %, and feed health indicators.
- Toggle a feature flag at `/admin/feature-flags` and verify state changes and cache invalidation.
- Update system settings at `/admin/settings` and verify persistence across groups.
- Inspect rejected records at `/admin/data-quality`, test individual and batch reprocessing.
- Resolve an unmapped commodity alias at `/admin/unresolved-mappings` and observe automatic record reprocessing.
- View administrative events at `/admin/audit-logs` and inspect diff details.
