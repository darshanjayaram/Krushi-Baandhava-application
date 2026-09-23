# Krushi Baandhava — Admin Panel Specification

## 1. Role-Based Access Control (RBAC)

The administration panel uses strict Laravel Policies and Middleware to partition administrative duties:

| Role | Permissions & Accessible Areas |
| :--- | :--- |
| **Super Admin** | Full access to users, roles, audit logs, system settings, feature flags, all sub-modules |
| **Data Admin** | Data sources, API credentials, field mappings, alias resolvers, sync jobs, crops, markets |
| **Forecast Admin** | Forecast model configurations, horizon parameters, backtesting reports, accuracy metrics |
| **Content Admin** | Agriculture CMS articles, government schemes, agricultural news, educational videos |
| **Support Admin** | Farmer feedback review, bug submissions, market reporting issues |

---

## 2. Dashboard KPIs & Monitoring Widgets

The Admin Overview displays key health indicators:
1. **Sync Health**: Status of active feeds (data.gov.in, Agmarknet, etc.) with timestamps of last successful and failed syncs.
2. **Daily Ingestion Volume**: Total raw records fetched today, successfully normalized records, duplicates suppressed, and records rejected.
3. **Queue & Job Health**: Active database queue jobs, failed jobs count, and retry triggers.
4. **Data Quality Alerts**: Records flagged for price inconsistencies (`min > modal` or `modal > max`), negative values, or unrecognized commodity aliases.
5. **Forecast Run Health**: Last model generation timestamp, number of markets with active forecasts, and average MAPE.

---

## 3. Core Functional Screens

### 3.1 Data Source & Credentials Management
- **List & Configuration**: View all registered upstream providers, active status, endpoint URLs, and configured sync intervals.
- **Secure Credentials Form**: Input API keys, client secrets, and custom headers. Values are encrypted at rest using Laravel's `Crypt` facade and masked in the UI (`sk_live_...****`).
- **Connection Test Tool**: Real-time diagnostic modal that tests connection latency, auth status, schema discovery, and parses a sample record.

### 3.2 Declarative Field & Entity Mapping
- **Field Mapping**: Safe dropdown-based field mapping from raw provider keys (e.g. `Modal_Price` -> `modal_price`). No executable code or eval expressions.
- **Alias Resolution Queue**: Displays unmapped raw strings (e.g. *"Arecanut(Mangalore-New)"*) with an admin interface to associate them with canonical internal crops/varieties or create new verified entities.

### 3.3 Sync Jobs & Historical Logs
- **Manual Trigger**: "Run Sync Now" button with immediate dispatch to the database queue.
- **Sync History**: Tabular log showing source name, start/end timestamps, duration, counts (`received`, `inserted`, `updated`, `duplicate`, `rejected`), status badge, and detailed error tracebacks.

### 3.4 Data Quality Review Screen
- Dedicated table of rejected records from `market_price_raw` where `processing_status = 'rejected'`.
- Displays validation failure reasons (e.g., *"Modal price 45000 exceeds max price 42000"*) with options to dismiss or reprocess after mapping correction.

### 3.5 Feature Flags & System Settings
- **Feature Flags**: Instant toggle switches for modules: `market_prices`, `price_forecast`, `best_months`, `where_to_sell`, `weather`, `schemes`, `news`, `videos`, `multi_language`.
- **System Settings**: Form for application defaults: `default_state`, `default_district`, `forecast_minimum_observations`, `cache_duration`, `maintenance_mode`.
