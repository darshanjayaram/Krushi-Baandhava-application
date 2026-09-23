# Phase 9: Price Forecasting Engine & Backtesting — Walkthrough

## 1. Overview & Objectives
Phase 9 implemented the predictive analytics and price forecasting system for **Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)**, strictly adhering to the technical specifications defined in `docs/FORECASTING.md`.

Key goals achieved:
- Built native mathematical forecasting algorithms directly in PHP 8.2 (zero Python/R/external microservice dependencies), ensuring 100% compatibility with shared Linux cPanel hosting.
- Enforced a rigorous **Data Sufficiency Rule**: minimum of 30 daily observations in the preceding 90 days. If unsatisfied, no synthetic numbers are shown; instead, a clear Kannada notice is displayed.
- Multi-horizon projections: **1-Day (ನಾಳೆ)**, **7-Day (1 ವಾರ)**, **15-Day (15 ದಿನ)**, and **30-Day (1 ತಿಂಗಳು)**.
- Confidence intervals: lower and upper bounds calculated using residual variance scaled by horizon distance:
  $$\hat{Y}_{t+h} \pm 1.96 \cdot \sigma_e \cdot \sqrt{\frac{h}{7} + 0.5}$$
- Walk-forward backtesting computing MAE, RMSE, MAPE, and Directional Accuracy.
- Multi-horizon UI cards integrated into `/crops/{slug}` and an open JSON API at `/api/v1/forecasts`.
- Automated nightly batch command: `php artisan krushi:generate-forecasts` scheduled at `02:00 IST`.

---

## 2. Database Schema
Migration file: `database/migrations/2026_09_23_000003_create_price_forecasting_tables.php`

1. **`forecast_models`**:
   - `name`, `code` (e.g., `holts_linear_trend`, `seasonal_decomposition`), `description`, `parameters` (JSON), `is_active`.
2. **`forecast_runs`**:
   - `model_id`, `crop_id`, `market_id`, `run_date`, `status` (`running`, `completed`, `failed`), `data_points_used`, `metrics` (JSON), `error_message`.
3. **`price_forecasts`**:
   - `crop_id`, `market_id`, `model_id`, `forecast_run_id`, `forecast_date`, `horizon_days` (1, 7, 15, 30), `predicted_modal_price`, `lower_bound`, `upper_bound`, `confidence_score` (0-100%).
   - Unique composite index: `[crop_id, market_id, horizon_days, forecast_date]`.
4. **`forecast_metrics`**:
   - `model_id`, `crop_id`, `market_id`, `horizon_days`, `evaluation_date`, `mae`, `rmse`, `mape`, `directional_accuracy`.

---

## 3. Mathematical Models & Service Architecture

### 3.1 Contract Interface
`app/Services/Forecast/Contracts/ForecastModelInterface.php` defines:
- `forecast(Collection $historicalSeries, int $horizonDays, array $params = []): array`
- `backtest(Collection $historicalSeries, int $horizonDays, int $testPeriods = 7): array`

### 3.2 Holt's Linear Exponential Smoothing
`app/Services/Forecast/Models/HoltsLinearTrendModel.php`:
- Updates level ($L_t$) and trend ($T_t$) using smoothing constants $\alpha = 0.3$ and $\beta = 0.1$:
  $$L_t = \alpha Y_t + (1 - \alpha)(L_{t-1} + T_{t-1})$$
  $$T_t = \beta (L_t - L_{t-1}) + (1 - \beta) T_{t-1}$$
- $h$-step ahead projection:
  $$\hat{Y}_{t+h} = L_t + h \cdot T_t$$
- Standard deviation of residuals $\sigma_e$ is computed over the historical fit, producing widening confidence bounds for longer horizons.

### 3.3 Seasonal Decomposition Model
`app/Services/Forecast/Models/SeasonalDecompositionModel.php`:
- Calculates monthly seasonal indices $S_m$ across years.
- De-seasonalizes prices, calculates moving average baseline, and re-applies seasonal multipliers.

### 3.4 Orchestrator Engine
`app/Services/Forecast/ForecastingEngineService.php`:
- Verifies $\ge 30$ observations in last 90 days.
- Generates 1D, 7D, 15D, and 30D forecasts.
- Calculates trend direction (`bullish`, `bearish`, `stable`) and percentage movement.
- Executes rolling walk-forward backtesting and saves metrics in `forecast_metrics`.

---

## 4. Console Command & Nightly Scheduling

Command: `app/Console/Commands/GeneratePriceForecastsCommand.php`
```bash
# Run for all active crops and markets with backtesting evaluation:
php artisan krushi:generate-forecasts --backtest

# Run for a specific crop/market:
php artisan krushi:generate-forecasts --crop=1 --market=1
```

Scheduled in `routes/console.php`:
```php
Schedule::command('krushi:generate-forecasts --backtest')
    ->dailyAt('02:00')
    ->name('generate-price-forecasts')
    ->withoutOverlapping(60)
    ->appendOutputTo(storage_path('logs/forecasting.log'));
```

---

## 5. UI & Public API

### 5.1 Farmer UI (`resources/views/farmer/crops/show.blade.php`)
- **Multi-horizon Grid**: 4 cards showing 1-Day, 7-Day, 15-Day, and 30-Day projections.
- **Visual Badges**: Bullish (green $\uparrow$), Bearish (red $\downarrow$), Stable (slate $\leftrightarrow$).
- **Range Display**: Lower and Upper bounds with confidence score bar (e.g., 85%).
- **Data Insufficiency Notice**: Clean alert box explaining in Kannada when $< 30$ days of data exist.
- **Ethical Disclaimer**: Clear notice that agricultural markets depend on weather, arrivals, and policy; projections are indicative guides.

### 5.2 Public REST API (`app/Http/Controllers/Api/V1/ForecastApiController.php`)
`GET /api/v1/forecasts?crop=1&market=1`
Response:
```json
{
  "success": true,
  "crop": { "id": 1, "name": "Tomato", "name_kn": "ಟೊಮೆಟೊ" },
  "market": { "id": 1, "name": "Kolar APMC" },
  "forecasts": [
    {
      "horizon_days": 1,
      "horizon_label": "ನಾಳೆ",
      "forecast_date": "2026-09-24",
      "predicted_price": 2840.00,
      "lower_bound": 2690.00,
      "upper_bound": 2990.00,
      "confidence_score": 88.0
    }
  ]
}
```

---

## 6. Testing & Quality Assurance
Run all Phase 9 and regression tests:
```bash
php artisan test
```
Result: **105 tests passed (1,021 assertions)**.

Key Test Suites:
- `tests/Unit/HoltsLinearTrendModelTest.php`: Mathematical correctness of level, trend, horizon projection, and widening bounds.
- `tests/Unit/ForecastingEngineServiceTest.php`: Sufficiency threshold enforcement, multi-horizon array structures, backtesting calculations.
- `tests/Feature/GeneratePriceForecastsCommandTest.php`: Console CLI execution, option handling, database persistence.
- `tests/Feature/FarmerPriceForecastTest.php`: Public API output validation and Blade view rendering checks.
