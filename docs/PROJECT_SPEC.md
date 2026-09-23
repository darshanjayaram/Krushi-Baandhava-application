# Krushi Baandhava — Project Specification (Master Reference)

## 1. Executive Summary & Objective

**Krushi Baandhava** (ಕೃಷಿ ಬಾಂಧವ — "Farmer's Companion") is a mobile-first Progressive Web App (PWA) built specifically for farmers in Karnataka, India. Its primary purpose is to provide transparent, verified agricultural market intelligence, price trends, reliable price forecasting, nearby APMC market comparison, weather updates, agricultural advisory, government schemes, and localized agricultural news and videos.

### Key Value Propositions:
- **Mobile-First & PWA-Enabled**: Designed for low-bandwidth rural connectivity, screen widths down to 360px, installable with an offline app shell.
- **Strict Data Provenance**: Every price exposes arrival date, source feed, and freshness. Stale data is never disguised as current.
- **Transparent Forecasting**: Independent statistical and time-series models with confidence intervals and backtesting metrics, with no proprietary or "black-box" claims.
- **cPanel-First Architecture**: Operates efficiently on standard Linux cPanel shared hosting (PHP 8.2+, MySQL/MariaDB, database queues, cPanel Cron) while retaining architectural boundaries for VPS scaling (Redis, Horizon, Python microservices).
- **Zero External API Render Blocking**: User-facing pages query local canonical databases and cached aggregates; live external API calls are strictly handled asynchronously in scheduled/queued background processes.

---

## 2. Core Capabilities Matrix

| Capability | Scope & Implementation | Data Source / Mechanism |
| :--- | :--- | :--- |
| **Market Prices** | Daily mandi prices by Crop, Variety, and APMC Market in Karnataka | data.gov.in, Agmarknet, KRAMA, Boards |
| **Nearby Markets** | Geolocation-based discovery sorted by Haversine distance | Browser Geolocation + Nominatim Cache + MySQL Bounding Box |
| **Price History** | Historical trends (Daily, Weekly, Monthly, Yearly) | Precomputed daily/monthly statistics table |
| **Price Forecasting** | 1-day, 7-day, 15-day, and 30-day price projections with intervals | Baseline, Moving Averages, Exponential Smoothing, Seasonal Index |
| **Best Months to Sell**| 5-year seasonal index and historical monthly price distributions | 5-Year historical price data aggregation |
| **Where to Sell** | Multi-market price and distance comparison for selected crops | Indexed queries on today's/latest canonical records |
| **Weather** | 7-day temperature, rainfall probability, humidity, and advisories | Open-Meteo API (scheduled background sync) |
| **Agriculture CMS** | Verified advisories on cultivation, pests, irrigation, harvesting | Admin CMS (multilingual: Kannada & English) |
| **Government Schemes**| Central and Karnataka state agricultural schemes & how-to-apply | Curated official scheme repository |
| **News & Videos** | YouTube-curated farmer educational videos and agricultural news | Curated links and news items |
| **Admin Controls** | Data source configs, encrypted credentials, field mappings, sync logs | Secure Admin Panel with RBAC & Audit Trails |

---

## 3. Hosting Constraints & Environment

- **Target Deployment Platform**: Linux cPanel Shared Hosting (Apache/LiteSpeed, MySQL 8.0/MariaDB 10.x, PHP 8.2+).
- **PHP Version**: Current environment PHP 8.2.30. Core framework: **Laravel 11.x**.
- **Queue System**: Database queue driver (`QUEUE_CONNECTION=database`) with short-lived workers scheduled via cPanel Cron (`php artisan queue:work --stop-when-empty`).
- **Scheduler**: Single cPanel Cron running `php artisan schedule:run >> /dev/null 2>&1` every minute.
- **Cache**: File/Database cache (`CACHE_STORE=file` or `database`).
- **Frontend Stack**: Tailwind CSS (purged with Vite), Alpine.js for lightweight reactivity, Livewire for interactive admin & search components.
- **Security Boundaries**: Web root points exclusively to `public/` (or symlinked `public_html`). Application root (`app`, `config`, `storage`, `.env`, `vendor`) resides outside `public_html`.

---

## 4. Engineering Standards & Rules of Engagement

1. **Non-blocking Ingestion**: External APIs (`data.gov.in`, `open-meteo`, etc.) must never be called during standard user HTTP requests.
2. **Raw Data Preservation**: All external API payloads are stored verbatim in `market_price_raw` before normalization for debugging and reprocessing.
3. **Canonical Normalization**: Standardized crop, variety, and market IDs are resolved via mapping tables with rejection logs for unmatched or invalid entities.
4. **Data Integrity & Validation**: Sanity checks enforce `price >= 0`, `min_price <= modal_price <= max_price`, and valid dates.
5. **No Blind Scraping**: Ingestion utilizes structured APIs, open data portals, or official published reports.
6. **No Fake Forecasts**: If historical records for a commodity-market pair are below the minimum threshold (e.g., 30 observations), the forecast displays: *"Insufficient historical data for a reliable estimate."*
7. **Bilingual Preparedness**: All UI strings are managed through Laravel translation files (`lang/en/` and `lang/kn/`).
