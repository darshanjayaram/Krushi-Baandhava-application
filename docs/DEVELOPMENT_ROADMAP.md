# Krushi Baandhava — Development Roadmap (Phases 0–14)

This roadmap outlines the sequenced development progression. Each phase delivers verified functionality, complete with migrations, models, services, UI components, tests, and updated status tracking.

---

## Roadmap Overview

```
Phase 0: Environment Audit & Prerequisites
   ↓
Phase 1: Foundation & Scaffolding [CURRENT SPRINT]
   ↓
Phase 2: Master Data Management (Districts, Mandis, Crops)
   ↓
Phase 3: Data Source Integration Framework & Mappings
   ↓
Phase 4: data.gov.in Mandi Price Ingestion Pipeline
   ↓
Phase 5: Farmer Market UI & Price Browsing
   ↓
Phase 6: Geolocation & Nearby Mandi Discovery
   ↓
Phase 7: Weather Integration (Open-Meteo)
   ↓
Phase 8: Historical Analytics & Seasonal Analysis
   ↓
Phase 9: Price Forecasting Engine & Backtesting
   ↓
Phase 10: "Where to Sell" Decision Engine
   ↓
Phase 11: Agricultural CMS, Schemes, News & Videos
   ↓
Phase 12: Complete Admin Control & Data Quality Center
   ↓
Phase 13: PWA Offline Shell, Manifest & SEO Optimization
   ↓
Phase 14: Production Hardening & cPanel Deployment Verification
```

---

## Detailed Phase Breakdown

### Phase 0: Environment Audit & Prerequisites
- Audit PHP (8.2+), extensions, Composer, Node.js, npm, MySQL.
- Establish repository documentation and architectural guidelines.
- **Milestone**: Completed environment verification and approved architecture docs.

### Phase 1: Foundation & Scaffolding (Current Task)
- Scaffold Laravel 11 with modern Tailwind CSS, Alpine.js, and Livewire.
- Implement base layouts: Farmer Mobile PWA Layout (thumb-zone navigation) & Admin Control Layout.
- PWA foundation: `manifest.json`, `sw.js`, and meta tags.
- Core foundation migrations: `users`, `roles`, `feature_flags`, `system_settings`, `audit_logs`.
- Master data migrations: `states`, `districts`, `taluks`, `localities`, `markets`, `crop_categories`, `crops`, `crop_varieties`.
- Seeders for Karnataka districts, major crops (Arecanut, Coffee, Coconut, Paddy, etc.), sample APMC markets, and default admin user.
- Authentication for Admin Panel.
- **Acceptance Criteria**: Migrations and seeders pass cleanly; admin can log in; farmer shell renders on mobile viewports; tests pass.

### Phase 2: Master Data Management
- Admin management CRUD for Districts, Taluks, and APMC Markets (coordinates, active status).
- Admin management CRUD for Crops, Categories, and Varieties (standard units, icons).
- Public APIs for master data lookups.
- **Acceptance Criteria**: Data Admin can create, modify, and deactivate geographic and commodity master records with form validation.

### Phase 3: Data Source Integration Framework
- Implement `MarketDataProviderInterface`.
- Schema for `data_sources`, `data_source_credentials` (encrypted), `data_source_mappings`.
- Provider registry, "Test Connection" tool, and sync log recording.
- **Acceptance Criteria**: Data sources can be configured with encrypted API keys and tested via the Admin UI.

### Phase 4: data.gov.in Ingestion Pipeline
- Implementation of `DataGovMarketDataProvider`.
- `market_price_raw` table and checksum deduplication.
- Normalization, alias matching, sanity checks (`price >= 0`, `min <= modal <= max`), and insertion to `market_prices`.
- Artisan sync command and scheduled job.
- **Acceptance Criteria**: Ingestion imports daily Karnataka mandi prices without duplicates; bad records are logged and rejected with reasons.

### Phase 5: Farmer Market UI & Price Browsing
- Mobile-first Home screen with today's price highlights and freshness badges.
- Crop catalog, crop detail screen with current mandi rates, and APMC market detail page.
- Search and filter by crop, variety, and district.
- **Acceptance Criteria**: Farmers can view current prices with clear date and source provenance; zero live external API calls on page render.

### Phase 6: Geolocation & Nearby Mandi Discovery
- Geolocation integration (HTML5 Geolocation API with manual fallback).
- Cached Nominatim reverse geocoder (`GeocoderInterface`).
- Bounding-box and Haversine distance calculations in MySQL.
- Nearby markets list sorted by distance in kilometers.
- **Acceptance Criteria**: Farmer can discover nearby mandis within 25km, 50km, and 100km with accurate distance calculations.

### Phase 7: Weather Integration
- Implementation of `OpenMeteoWeatherProvider` adhering to `WeatherProviderInterface`.
- Scheduled weather synchronization for district headquarters.
- Weather forecast cards in Farmer PWA showing temperature, precipitation probability, and farming advisory.
- **Acceptance Criteria**: 7-day weather forecast available without real-time API latency on user load.

### Phase 8: Historical Analytics & Seasonal Analysis
- Precomputed `price_daily_statistics` and `price_monthly_statistics`.
- Interactive price trend charts (Daily, Weekly, Monthly, 1-Year).
- "Best Months to Sell" 5-year seasonal index analysis.
- **Acceptance Criteria**: Historical charts display clean trends with statistical summary (min, max, average, observation count).

### Phase 9: Price Forecasting Engine & Backtesting
- Mathematical forecasting models (Holt's Linear Exponential Smoothing, Seasonal Index).
- Multi-horizon projections: 1-Day, 7-Day, 15-Day, 30-Day.
- Confidence intervals and sufficiency check (minimum 30 observations).
- Rolling walk-forward backtesting tracking MAE, RMSE, and MAPE.
- **Acceptance Criteria**: Forecasts display expected price, bounds, and confidence score; data-deficient markets show proper insufficiency notice.

### Phase 10: "Where to Sell" Decision Engine
- Interactive comparison tool evaluating nearby active markets for selected crop.
- Sorting by highest current price, shortest distance, and latest update.
- Transparent net realization indicators (transparent calculations, no black-box scores).
- **Acceptance Criteria**: Farmers can easily compare prices across 3-5 nearby markets to decide where to transport harvest.

### Phase 11: Agricultural CMS, Schemes, News & Videos
- Multilingual CMS articles (Cultivation, Pest Control, Fertilizer, Irrigation).
- Government schemes directory with eligibility criteria and application guides.
- Curated agricultural news and YouTube video embeds.
- Bilingual switcher (Kannada / English).
- **Acceptance Criteria**: Content Admin can publish bilingual articles and schemes with category filters.

### Phase 12: Complete Admin Control & Data Quality Center
- Ingestion monitoring dashboard (sync logs, failure rates, queue backlog).
- Unresolved mapping resolver for new external crop and mandi names.
- Rejected record inspector with error diagnosis and re-run capability.
- Feature flags toggle center and application settings manager.
- Audit trail for administrative actions.
- **Acceptance Criteria**: Full admin control over data sources, mappings, feature flags, and logs.

### Phase 13: PWA Offline Shell, Manifest & SEO
- Full PWA Service Worker caching (app shell, static assets, cached prices).
- Web App Manifest with icons and standalone display mode.
- Open Graph tags, canonical URLs, dynamic sitemap (`sitemap.xml`), and `robots.txt`.
- **Acceptance Criteria**: App passes Lighthouse PWA criteria; install prompt functions on mobile browsers.

### Phase 14: Production Hardening & Deployment Verification
- Comprehensive security audit (CSRF, XSS, rate limiting, SQL injection defense).
- Performance benchmarking (`LCP < 1.2s`, asset optimization, database query audit).
- Verification of cPanel cron jobs and queue workers.
- **Acceptance Criteria**: Production-ready deployment package with verified cPanel installation documentation.
