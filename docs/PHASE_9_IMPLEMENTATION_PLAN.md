# Phase 9 Implementation Plan: Price Forecasting Engine & Backtesting

## 1. Overview & Business Objectives
Karnataka farmers need reliable, transparent price projections to plan harvesting, drying, storage, and transport windows. In accordance with [`docs/FORECASTING.md`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/docs/FORECASTING.md), Phase 9 delivers an **open, independent, statistical forecasting engine**:
1. **Mathematical Models (Native PHP 8.2 for cPanel shared hosting compatibility)**:
   - **Holt's Linear Exponential Smoothing**: Double exponential smoothing capturing local level $L_t$ and trend $T_t$ ($\alpha = 0.35, \beta = 0.15$).
   - **Seasonal Decomposition & Historical Index**: Combines baseline momentum with 12-month seasonal multiplier $S_m$.
2. **Multi-Horizon Projections**:
   - Tomorrow (+1 Day): Immediate mandi transport.
   - Next 7 Days (+7 Days): Weekly harvesting schedule.
   - Next 15 Days (+15 Days): Drying & short-term holding.
   - Next 30 Days (+30 Days): Monthly selling strategy.
3. **Transparent Uncertainty Bounds & Confidence Scores**:
   - Lower Bound and Upper Bound calculated using residual volatility ($RMSE$) scaled by $\sqrt{h}$.
   - Confidence score percentage (0–100%) grounded in residual variance.
4. **Strict Data Sufficiency Threshold**:
   - Minimum 30 daily observations required within preceding 90 days.
   - If historical records are fewer than 30, the system cleanly flags:
     *⚠️ "ವಿಶ್ವಾಸಾರ್ಹ ಮುನ್ಸೂಚನೆಗೆ ಕನಿಷ್ಠ 30 ದಿನಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ (Insufficient historical data)."*
   - Zero synthetic or fabricated predictions.
5. **Rolling Walk-Forward Backtesting**:
   - Tracks MAE, RMSE, MAPE, and directional accuracy in `forecast_metrics`.
6. **Mobile-First UI & Public REST API**:
   - Forecast projection card with horizon selector tabs, confidence bounds meter, and ethical disclaimer on `/crops/{slug}`.
   - REST API endpoint `GET /api/v1/forecasts`.

---

## 2. Proposed Changes

### Database Layer
- **[NEW] `database/migrations/2026_09_23_000003_create_price_forecasting_tables.php`**:
  - `forecast_models`: `id`, `name`, `code` (unique), `version`, `parameters` (json), `is_active`.
  - `forecast_runs`: `id`, `model_id`, `started_at`, `completed_at`, `status`, `total_predictions`, `error_message`.
  - `price_forecasts`: `id`, `run_id`, `crop_id`, `variety_id` (nullable), `market_id` (nullable for state benchmark), `forecast_date`, `horizon_days`, `expected_price`, `lower_bound`, `upper_bound`, `confidence_score`, `data_points_used`, unique index on `[crop_id, variety_id, market_id, forecast_date, horizon_days]`.
  - `forecast_metrics`: `id`, `model_id`, `crop_id`, `variety_id` (nullable), `market_id` (nullable), `horizon_days`, `mae`, `rmse`, `mape`, `directional_accuracy`.

### Models Layer
- **[NEW] `app/Models/ForecastModel.php`**
- **[NEW] `app/Models/ForecastRun.php`**
- **[NEW] `app/Models/PriceForecast.php`**
- **[NEW] `app/Models/ForecastMetric.php`**

### Forecasting Services Layer
- **[NEW] `app/Services/Forecast/Contracts/ForecastModelInterface.php`**: Interface definition.
- **[NEW] `app/Services/Forecast/Models/HoltsLinearTrendModel.php`**: Holt's linear exponential smoothing and residual error calculation.
- **[NEW] `app/Services/Forecast/Models/SeasonalDecompositionModel.php`**: Seasonal multiplier projection.
- **[NEW] `app/Services/Forecast/ForecastingEngineService.php`**: Orchestration, data sufficiency enforcement, horizon calculations, batch generation, and rolling walk-forward backtesting.

### Console Command & Scheduling
- **[NEW] `app/Console/Commands/GeneratePriceForecastsCommand.php`**:
  - `krushi:generate-forecasts {--crop=} {--market=} {--backtest}`
- **[MODIFY] `routes/console.php`**:
  - Schedule `krushi:generate-forecasts` nightly at `02:00 IST`.

### Frontend & UI Layer
- **[MODIFY] `app/Http/Controllers/Farmer/CropController.php`**:
  - Fetch forecasts for horizons (1d, 7d, 15d, 30d) and pass to view.
- **[MODIFY] `resources/views/farmer/crops/show.blade.php`**:
  - Add **Price Forecasting Card (ದರ ಮುನ್ಸೂಚನೆ & ನಿರೀಕ್ಷಿತ ಶ್ರೇಣಿ)** with horizon tabs, upper/lower bounds visualization, confidence pill, sufficiency indicator, and ethical disclaimer.

### API Layer
- **[NEW] `app/Http/Controllers/Api/V1/ForecastApiController.php`**:
  - `GET /api/v1/forecasts`: Horizon projections, bounds, and confidence score.
- **[MODIFY] `routes/api.php`**:
  - Register `/api/v1/forecasts` endpoint.

---

## 3. Verification Plan

### Automated Tests
- `tests/Unit/HoltsLinearTrendModelTest.php`:
  - Holt's linear equation output on known price trajectory.
  - Correct lower and upper bounds calculation ($\text{Lower} \le \text{Expected} \le \text{Upper}$).
  - Residual RMSE and confidence score bounds.
- `tests/Unit/ForecastingEngineServiceTest.php`:
  - Enforce data sufficiency threshold ($< 30$ records fails gracefully with explanatory message).
  - Horizon calculations for 1, 7, 15, and 30 days.
  - Backtesting metrics calculation (MAE, RMSE, MAPE).
- `tests/Feature/GeneratePriceForecastsCommandTest.php`:
  - Run `php artisan krushi:generate-forecasts` and assert records created in `price_forecasts` and `forecast_runs`.
- `tests/Feature/FarmerPriceForecastTest.php`:
  - Crop show page displays forecast card and projections.
  - Public REST API `GET /api/v1/forecasts` returns structured JSON.
- Run complete test suite (`artisan test`) to ensure all 94+ tests pass.
- Verify Vite production build (`npm run build`).
