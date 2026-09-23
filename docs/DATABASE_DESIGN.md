# Krushi Baandhava — Database Design & Data Dictionary

## 1. Overview & Principles

The database schema is optimized for:
1. **High-speed querying on shared hosting**: Heavy reads by mobile farmers require composite indexes on `(crop_id, market_id, price_date)`.
2. **Auditability & Traceability**: The `market_price_raw` table stores original untouched external payloads before normalization.
3. **Data Integrity**: Enforced foreign keys, explicit column nullability, and database-level checks for positive prices and valid date ranges.
4. **Distance Calculations in MySQL**: Spatial coordinates (`latitude`, `longitude` as decimal/floats) with bounding-box index support and Haversine formula calculation.

---

## 2. Table Schemas by Domain

### 2.1 Users, Roles & Security

#### `users`
- `id` (BIGINT, PK, Auto Increment)
- `name` (VARCHAR 255)
- `email` (VARCHAR 255, Unique)
- `phone` (VARCHAR 20, Nullable, Unique)
- `password` (VARCHAR 255)
- `role` (VARCHAR 50, Default: 'farmer') — `super_admin`, `data_admin`, `forecast_admin`, `content_admin`, `support_admin`, `farmer`
- `preferred_language` (VARCHAR 10, Default: 'kn') — 'kn', 'en'
- `district_id` (BIGINT, Nullable, FK -> districts.id)
- `remember_token` (VARCHAR 100, Nullable)
- `created_at`, `updated_at` (TIMESTAMP)

#### `roles` & `permissions`
Standard role-permission pivot mapping for granular admin authorization.

#### `audit_logs`
- `id` (BIGINT, PK)
- `user_id` (BIGINT, Nullable, FK -> users.id)
- `action` (VARCHAR 100) — e.g., 'data_source.sync', 'settings.update'
- `entity_type` (VARCHAR 100, Nullable)
- `entity_id` (BIGINT, Nullable)
- `ip_address` (VARCHAR 45, Nullable)
- `user_agent` (TEXT, Nullable)
- `old_values` (JSON, Nullable)
- `new_values` (JSON, Nullable)
- `created_at` (TIMESTAMP)

---

### 2.2 Geographic & Market Infrastructure

#### `states`
- `id` (BIGINT, PK)
- `name` (VARCHAR 100) — e.g., 'Karnataka'
- `code` (VARCHAR 10, Unique) — 'KA'
- `is_active` (BOOLEAN, Default: true)

#### `districts`
- `id` (BIGINT, PK)
- `state_id` (BIGINT, FK -> states.id)
- `name` (VARCHAR 100) — e.g., 'Shivamogga', 'Chikkamagaluru'
- `name_kn` (VARCHAR 150, Nullable) — 'ಶಿವಮೊಗ್ಗ'
- `code` (VARCHAR 20, Nullable)
- `latitude` (DECIMAL(10, 7), Nullable)
- `longitude` (DECIMAL(10, 7), Nullable)
- `is_active` (BOOLEAN, Default: true)

#### `taluks`
- `id` (BIGINT, PK)
- `district_id` (BIGINT, FK -> districts.id)
- `name` (VARCHAR 100) — e.g., 'Thirthahalli', 'Sagara'
- `name_kn` (VARCHAR 150, Nullable)
- `latitude` (DECIMAL(10, 7), Nullable)
- `longitude` (DECIMAL(10, 7), Nullable)

#### `localities`
- `id` (BIGINT, PK)
- `taluk_id` (BIGINT, FK -> taluks.id)
- `name` (VARCHAR 150)
- `name_kn` (VARCHAR 200, Nullable)
- `pincode` (VARCHAR 10, Nullable)

#### `markets` (APMC Mandis)
- `id` (BIGINT, PK)
- `district_id` (BIGINT, FK -> districts.id)
- `taluk_id` (BIGINT, Nullable, FK -> taluks.id)
- `name` (VARCHAR 150) — e.g., 'Shivamogga APMC'
- `name_kn` (VARCHAR 200, Nullable)
- `code` (VARCHAR 50, Nullable)
- `market_type` (VARCHAR 50, Default: 'APMC') — 'APMC', 'Sub-market', 'Private'
- `latitude` (DECIMAL(10, 7))
- `longitude` (DECIMAL(10, 7))
- `address` (TEXT, Nullable)
- `is_active` (BOOLEAN, Default: true)
- `created_at`, `updated_at` (TIMESTAMP)
- *Indexes*: `(district_id)`, `(latitude, longitude)`

---

### 2.3 Crops & Varieties Master Data

#### `crop_categories`
- `id` (BIGINT, PK)
- `name` (VARCHAR 100) — e.g., 'Commercial Crops', 'Spices', 'Cereals', 'Vegetables'
- `name_kn` (VARCHAR 150, Nullable)
- `slug` (VARCHAR 100, Unique)
- `icon` (VARCHAR 100, Nullable)
- `display_order` (INT, Default: 0)

#### `crops`
- `id` (BIGINT, PK)
- `category_id` (BIGINT, FK -> crop_categories.id)
- `name` (VARCHAR 150) — e.g., 'Arecanut', 'Coffee', 'Coconut', 'Paddy'
- `name_kn` (VARCHAR 200, Nullable) — 'ಅಡಿಕೆ'
- `slug` (VARCHAR 150, Unique)
- `scientific_name` (VARCHAR 150, Nullable)
- `standard_unit` (VARCHAR 50, Default: 'Quintal') — 'Quintal', 'Kg', 'Bag', '1000 Nuts'
- `icon` (VARCHAR 255, Nullable)
- `is_major` (BOOLEAN, Default: false)
- `is_active` (BOOLEAN, Default: true)
- `created_at`, `updated_at` (TIMESTAMP)

#### `crop_varieties`
- `id` (BIGINT, PK)
- `crop_id` (BIGINT, FK -> crops.id)
- `name` (VARCHAR 150) — e.g., 'Rashi', 'Bette', 'Chali', 'Arabica Parchment', 'Robusta Cherry'
- `name_kn` (VARCHAR 200, Nullable)
- `slug` (VARCHAR 150)
- `is_active` (BOOLEAN, Default: true)
- *Unique Constraint*: `(crop_id, slug)`

---

### 2.4 Data Sources, Credentials & Mappings

#### `data_sources`
- `id` (BIGINT, PK)
- `name` (VARCHAR 150) — 'data.gov.in Mandi Prices', 'Agmarknet Daily Feed'
- `code` (VARCHAR 50, Unique) — 'data_gov_mandi', 'agmarknet', 'coffee_board'
- `provider_class` (VARCHAR 255) — Fully-qualified class name of adapter
- `base_url` (VARCHAR 255)
- `endpoint` (VARCHAR 255, Nullable)
- `auth_type` (VARCHAR 50, Default: 'api_key') — 'api_key', 'bearer_token', 'none'
- `sync_frequency` (VARCHAR 50, Default: 'daily') — 'hourly', 'daily', 'twice_daily'
- `is_active` (BOOLEAN, Default: true)
- `last_sync_at` (TIMESTAMP, Nullable)
- `last_sync_status` (VARCHAR 50, Nullable) — 'success', 'failed', 'partial'
- `created_at`, `updated_at` (TIMESTAMP)

#### `data_source_credentials`
- `id` (BIGINT, PK)
- `data_source_id` (BIGINT, FK -> data_sources.id, Unique)
- `api_key` (TEXT, Nullable) — Encrypted via Laravel Crypt
- `client_id` (TEXT, Nullable) — Encrypted
- `client_secret` (TEXT, Nullable) — Encrypted
- `additional_headers` (JSON, Nullable) — Encrypted

#### `data_source_mappings` (Field Transformations)
- `id` (BIGINT, PK)
- `data_source_id` (BIGINT, FK -> data_sources.id)
- `source_field` (VARCHAR 100) — e.g., 'Modal_Price', 'Commodity'
- `target_field` (VARCHAR 100) — e.g., 'modal_price', 'crop_name'
- `transformation_rule` (VARCHAR 100, Nullable) — 'to_number', 'trim', 'date_format:d/m/Y'

#### `crop_source_mappings` & `market_source_mappings` (Alias Resolvers)
- Alias resolver tables to safely map raw external strings (e.g. "Arecanut(Betelnut)") to canonical internal crop/market IDs.

---

### 2.5 Market Ingestion & Canonical Prices

#### `market_price_raw`
- `id` (BIGINT, PK)
- `source_id` (BIGINT, FK -> data_sources.id)
- `external_record_id` (VARCHAR 150, Nullable)
- `payload` (JSON)
- `checksum` (CHAR 64) — SHA-256 of normalized raw record payload
- `received_at` (TIMESTAMP)
- `processed_at` (TIMESTAMP, Nullable)
- `processing_status` (VARCHAR 50, Default: 'pending') — 'pending', 'processed', 'rejected', 'failed'
- `error_message` (TEXT, Nullable)
- *Indexes*: `(source_id, processing_status)`, `(checksum)`

#### `market_prices` (Canonical Record)
- `id` (BIGINT, PK)
- `crop_id` (BIGINT, FK -> crops.id)
- `variety_id` (BIGINT, Nullable, FK -> crop_varieties.id)
- `market_id` (BIGINT, FK -> markets.id)
- `district_id` (BIGINT, FK -> districts.id)
- `price_date` (DATE)
- `min_price` (DECIMAL(10, 2))
- `max_price` (DECIMAL(10, 2))
- `modal_price` (DECIMAL(10, 2))
- `arrival_quantity` (DECIMAL(12, 2), Nullable)
- `unit` (VARCHAR 50, Default: 'Quintal')
- `source_id` (BIGINT, FK -> data_sources.id)
- `raw_record_id` (BIGINT, Nullable, FK -> market_price_raw.id)
- `created_at`, `updated_at` (TIMESTAMP)
- *Unique Constraint*: `(crop_id, variety_id, market_id, price_date, source_id)`
- *Composite Performance Indexes*:
  - `idx_crop_market_date`: `(crop_id, market_id, price_date)`
  - `idx_market_date`: `(market_id, price_date)`
  - `idx_crop_date_modal`: `(crop_id, price_date, modal_price)`

---

### 2.6 Historical Aggregates & Statistics

#### `price_daily_statistics`
Precomputed daily summary across all markets in Karnataka per crop & variety.
- `id` (BIGINT, PK)
- `crop_id`, `variety_id`, `state_id`, `record_date` (DATE)
- `avg_modal_price`, `min_modal_price`, `max_modal_price` (DECIMAL(10, 2))
- `total_arrival_quantity` (DECIMAL(14, 2))
- `active_markets_count` (INT)
- *Unique Index*: `(crop_id, variety_id, state_id, record_date)`

#### `price_monthly_statistics` (For Seasonality & Best Months)
- `crop_id`, `variety_id`, `market_id`, `year`, `month`
- `avg_modal_price`, `min_price`, `max_price`, `seasonal_index`, `observations_count`

---

### 2.7 Forecasting Engine

#### `forecast_models`
- `id` (BIGINT, PK)
- `name` (VARCHAR 100) — 'Moving Average (SMA-14)', 'Holt-Winters Seasonal Smoothing'
- `code` (VARCHAR 50, Unique)
- `version` (VARCHAR 20)
- `parameters` (JSON, Nullable)
- `is_active` (BOOLEAN, Default: true)

#### `forecast_runs`
- `id` (BIGINT, PK)
- `model_id` (BIGINT, FK -> forecast_models.id)
- `started_at`, `completed_at` (TIMESTAMP)
- `status` (VARCHAR 50) — 'running', 'completed', 'failed'
- `total_predictions` (INT)
- `error_message` (TEXT, Nullable)

#### `price_forecasts`
- `id` (BIGINT, PK)
- `run_id` (BIGINT, FK -> forecast_runs.id)
- `crop_id` (BIGINT, FK -> crops.id)
- `variety_id` (BIGINT, Nullable, FK -> crop_varieties.id)
- `market_id` (BIGINT, FK -> markets.id)
- `forecast_date` (DATE) — Target future date
- `horizon_days` (INT) — 1, 7, 15, 30
- `expected_price` (DECIMAL(10, 2))
- `lower_bound` (DECIMAL(10, 2))
- `upper_bound` (DECIMAL(10, 2))
- `confidence_score` (DECIMAL(5, 2)) — 0.00 to 100.00%
- `data_points_used` (INT)
- *Indexes*: `(crop_id, market_id, forecast_date)`

#### `forecast_metrics` (Backtesting Results)
- `id` (BIGINT, PK)
- `model_id` (BIGINT, FK -> forecast_models.id)
- `crop_id`, `variety_id`, `market_id`
- `horizon_days` (INT)
- `mae` (DECIMAL(10, 2)), `rmse` (DECIMAL(10, 2)), `mape` (DECIMAL(5, 2))
- `directional_accuracy` (DECIMAL(5, 2))

---

### 2.8 Weather, CMS, Schemes, & Settings

- `weather_locations`: Latitude, longitude, name, district reference.
- `weather_forecasts`: Location, date, temp_min, temp_max, rain_prob, conditions, advisory.
- `agriculture_articles`: Title, slug, content, category, crop, featured_image, language ('kn'/'en').
- `schemes`: Scheme name, eligibility, benefits, application steps, official_url, district filter.
- `news_articles` & `videos`: Headline, link, YouTube video ID, summary, date.
- `feature_flags`: Key (e.g. 'price_forecast', 'weather'), is_enabled, description.
- `system_settings`: Key, value, type, description.
