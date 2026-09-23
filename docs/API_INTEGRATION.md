# Krushi Baandhava — External API Integration Specification

## 1. Integration Architecture & Security Principles

1. **Strict Asynchronous Ingestion**: External APIs are invoked exclusively via scheduled CLI commands or queued jobs. User page loads never trigger outbound third-party HTTP requests.
2. **Zero Client-Side Exposure**: API keys, tokens, and basic credentials remain strictly server-side in `.env` or encrypted in `data_source_credentials`.
3. **Resilience & Rate Limiting**: All external calls implement timeout limits (10-30s), exponential backoff retries, and respect provider fair-use policies.
4. **Mock Mode for Development & Testing**: When `DATA_SOURCE_MOCK_MODE=true` in `.env`, providers return realistic local JSON fixtures to allow offline testing and prevent rate exhaustion.

---

## 2. Market Data Providers

### 2.1 data.gov.in (Primary Mandi Prices)

- **Dataset**: Current Daily Price of Various Commodities from Various Markets (Mandi)
- **Official Resource URL**: `https://data.gov.in/resource/current-daily-price-various-commodities-various-markets-mandi`
- **Request Format**:
  ```http
  GET https://api.data.gov.in/resource/{resource_id}?api-key={API_KEY}&format=json&limit=1000&filters[state.keyword]=Karnataka
  ```
- **Expected Payload Structure**:
  ```json
  {
    "status": "ok",
    "total": 450,
    "records": [
      {
        "state": "Karnataka",
        "district": "Shimoga",
        "market": "Shimoga",
        "commodity": "Arecanut",
        "variety": "Rashi",
        "arrival_date": "23/09/2026",
        "min_price": "48000",
        "max_price": "54500",
        "modal_price": "52000"
      }
    ]
  }
  ```
- **Field Normalization Strategy**:
  - `commodity` -> Trimmed, lowercase lookup against `crop_source_mappings` -> canonical `crop_id`.
  - `variety` -> Trimmed lookup against `crop_varieties` -> canonical `variety_id`.
  - `market` -> Trimmed lookup against `market_source_mappings` -> canonical `market_id`.
  - `arrival_date` -> Parsed via `Carbon::createFromFormat('d/m/Y', ...)` -> canonical `price_date`.
  - `min_price`, `max_price`, `modal_price` -> Cast to `float`, validated with `min <= modal <= max`.

---

### 2.2 Agmarknet Adapter

- **Role**: Secondary validation and historical reference source for national APMC mandis.
- **Access Strategy**: Structured data feeds or permitted JSON/XML endpoints.
- **Provider Class**: `App\Services\DataSources\Agmarknet\AgmarknetMarketDataProvider`
- **Fallback**: If official API access is temporarily unavailable, uses scheduled batch data imports.

---

### 2.3 KRAMA Adapter (Karnataka State Specifics)

- **Role**: State-level market prices and arrivals for Karnataka.
- **Access Strategy**: Configurable adapter placeholder that verifies official access channels before automated scheduling.

---

### 2.4 Coffee Board of India (`CoffeeBoardDataProvider`)

- **Target Commodities**:
  - Arabica Cherry
  - Arabica Parchment
  - Robusta Cherry
  - Robusta Parchment
- **Source**: `https://coffeeboard.gov.in/` daily price bulletins / structured releases.
- **Standard Unit**: 50 kg bag converted to standard Quintal (100 kg) for uniform platform pricing.

---

### 2.5 Coconut Development Board (`CoconutBoardDataProvider`)

- **Target Commodities**:
  - Coconut (Raw / Dehusked, unit: 1000 nuts)
  - Copra (Milling & Ball, unit: Quintal)
  - Coconut Oil (unit: Quintal / 15kg tin)
- **Source**: `https://coconutboard.gov.in/` market releases.

---

## 3. Weather Integration: Open-Meteo

- **Provider**: Open-Meteo (`https://open-meteo.com/`)
- **Advantage**: High accuracy, generous rate limits, no API key required for non-commercial/standard use.
- **Request Format**:
  ```http
  GET https://api.open-meteo.com/v1/forecast?latitude=13.9299&longitude=75.5681&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Asia%2FKolkata&forecast_days=7
  ```
- **Storage**: Results are cached in `weather_forecasts` table per district / taluk headquarters and updated twice daily.

---

## 4. Location & Reverse Geocoding: Nominatim (OpenStreetMap)

- **Endpoint**: `https://nominatim.openstreetmap.org/reverse`
- **Request Format**:
  ```http
  GET https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={LAT}&lon={LON}&accept-language=en,kn
  ```
- **Fair-Use Rules (Mandatory)**:
  1. Maximum 1 request per second.
  2. Send unique application User-Agent: `User-Agent: KrushiBaandhava/1.0 (contact@krushibaandhava.org)`.
  3. Strict caching: Geocoded results are hashed and cached for 30 days to avoid repeated lookups.
  4. Privacy: Precise coordinates are never saved to user profiles without consent.

---

## 5. Admin Connection Testing & Health Monitoring

The Admin Panel features a **"Test Connection"** utility for every registered data source.
- **Execution**: Sends a test ping with sample filters.
- **Metrics Collected**:
  - HTTP status code
  - Response latency (ms)
  - Authentication status
  - Record count returned
  - Detected schema attributes
  - Sample parsed record preview
- **Logging**: Stored in `api_health_logs` table for uptime tracking and alerting.
