# Krushi Baandhava — Price Forecasting Engine Specification

## 1. Principles & Ethical Disclosures

1. **Independent Implementation**: Krushi Baandhava uses an open, transparent statistical forecasting framework. It does not mimic or reverse-engineer proprietary algorithms from other applications.
2. **Confidence Bounds & Transparency**: All forecasts report an `expected_price` alongside `lower_bound` and `upper_bound` calculated from historical residual volatility.
3. **Strict Data Sufficiency Threshold**:
   - Minimum observations required: **30 daily prices** within the preceding 90 days.
   - If historical records are fewer than 30, the system returns:
     > *"Insufficient historical data for a reliable estimate."*
   - Fabricated or synthetic price predictions are strictly prohibited.
4. **No Financial Guarantees**: UI text clearly states that market forecasts are statistical projections based on past patterns and are subject to market conditions, weather events, and policy shifts.

---

## 2. Forecast Horizons

The engine computes projections for 4 discrete operational windows:
1. **Tomorrow (+1 Day)**: Short-term mandi price movement.
2. **Next 7 Days (+7 Days)**: Weekly harvesting and transport planning.
3. **Next 15 Days (+15 Days)**: Mid-term drying and storage decisions.
4. **Next 30 Days (+30 Days)**: Monthly selling strategy.

---

## 3. Mathematical Models (V1 PHP Implementation)

All V1 algorithms are implemented natively in PHP without external Python dependencies to maintain 100% compatibility with Linux cPanel shared hosting.

### 3.1 Model 1: Exponential Smoothing with Trend (Holt's Linear)
Captures recent momentum while weighting recent days higher than distant history:
$$L_t = \alpha Y_t + (1 - \alpha)(L_{t-1} + T_{t-1})$$
$$T_t = \beta (L_t - L_{t-1}) + (1 - \beta) T_{t-1}$$
$$\hat{Y}_{t+h} = L_t + h \cdot T_t$$
*Default smoothing factors: $\alpha = 0.35$, $\beta = 0.15$.*

### 3.2 Model 2: Seasonal Decomposition & Historical Index
For agricultural commodities strongly tied to annual harvesting cycles (e.g., Arecanut, Coffee, Ragi):
1. Compute the 12-month seasonal index $S_m$ over the previous 3 to 5 years:
   $$S_m = \frac{\bar{P}_m}{\bar{P}_{annual}}$$
2. Combine base trend with seasonal multiplier:
   $$\hat{P}_{forecast} = \text{TrendBaseline} \times S_m$$

### 3.3 Uncertainty & Confidence Intervals
Prediction intervals are calculated using the Root Mean Square Error ($RMSE$) of the validation residuals:
$$\text{Lower Bound} = \hat{Y}_{t+h} - z \cdot \sigma_h$$
$$\text{Upper Bound} = \hat{Y}_{t+h} + z \cdot \sigma_h$$
*(Where $z = 1.96$ for a 95% confidence interval, scaled by $\sqrt{h}$ for horizon expansion).*

---

## 4. Rolling Walk-Forward Backtesting

Model evaluation uses rolling cross-validation:

```
[---------- Training Window ----------] [ Horizon h ] (Test)
      [---------- Training Window ----------] [ Horizon h ] (Test)
            [---------- Training Window ----------] [ Horizon h ] (Test)
```

### Metrics Tracked in `forecast_metrics`:
- **MAE (Mean Absolute Error)**: $\frac{1}{n}\sum |y_i - \hat{y}_i|$
- **RMSE (Root Mean Square Error)**: $\sqrt{\frac{1}{n}\sum (y_i - \hat{y}_i)^2}$
- **MAPE (Mean Absolute Percentage Error)**: $\frac{100\%}{n}\sum \left|\frac{y_i - \hat{y}_i}{y_i}\right|$
- **Directional Accuracy**: Percentage of periods where the forecasted sign of price change matches the actual market direction.

The model demonstrating the lowest MAPE for a given commodity on backtested data is automatically designated as the active production model for that commodity.
