# Phase 10: "Where to Sell" Decision Engine — Walkthrough

## 1. Overview & Objectives
Phase 10 delivered the **"Where to Sell" Decision Engine (ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?)** for **Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)**, providing Karnataka farmers with a transparent, mathematically rigorous tool to evaluate whether transporting their harvest to distant APMC markets yields genuine extra profit or an unintended loss after vehicle transport and mandi fees.

### Key Goals Achieved:
- **100% Free & Open Access**: No mandatory Google Sign-In or phone authentication barriers (unlike Negilu Krushi's `#signin-lock` paywall/login gate).
- **Multi-Mandi Realization Comparison**: Evaluates all active Karnataka APMC markets trading a selected crop.
- **Realistic Rural Haulage Modeling**:
  - Distance computed using Haversine formula + $1.2\times$ rural road factor.
  - Three vehicle profiles: Auto 3-Wheeler (₹15/km, max 8 Q), Bolero/Pickup (₹22/km, max 20 Q), and Canter Mini Truck (₹32/km, max 50 Q).
  - Customizable transport rate per km override for farmer-negotiated contracts.
- **Transparent Mandi Deductions**: APMC statutory market cess (1.5%) and hamali handling charges (₹10/Q).
- **Comparative Advantage vs. Nearest Local Mandi**:
  - Highlights the **#1 Best Mandi to Sell** with net in-pocket earnings.
  - Clearly reports $\Delta \text{Net Profit}$ (e.g. "Going to Kolar yields ₹4,200 extra profit after transport" OR "Stay local: transport eats ₹1,800!").
- **Direct Turn-by-Turn Navigation**: 1-tap Google Maps directions link for every candidate market.
- **Crop Page Quick Chips**: Added horizontal market switcher chips (`?market=...`) and Where to Sell callout banner directly on `/crops/{slug}`.
- **Public REST API**: `GET /api/v1/decision/where-to-sell`.

---

## 2. Mathematical Realization Architecture

The financial engine is implemented in `app/Services/Market/WhereToSellService.php`:

$$\text{Gross Revenue} = \text{Quantity (Q)} \times \text{Modal Price (₹/Q)}$$

$$\text{Road Distance (km)} = \text{Haversine Straight Distance} \times 1.20$$

$$\text{Transport Cost} = \max(\text{Min Fare}, \text{Road Distance} \times \text{Rate/km})$$

$$\text{Mandi Fees} = (\text{Gross Revenue} \times 0.015) + (\text{Quantity} \times 10)$$

$$\text{Net Realization} = \text{Gross Revenue} - \text{Transport Cost} - \text{Mandi Fees}$$

$$\text{Net Rate (₹/Q)} = \frac{\text{Net Realization}}{\text{Quantity}}$$

### Delta Advantage vs. Nearest Mandi:
$$\Delta \text{Net} = \text{Net Realization}_{\text{target}} - \text{Net Realization}_{\text{nearest}}$$
- If $\Delta \text{Net} \ge +300$: Marked with green badge (`+₹X ಹೆಚ್ಚುವರಿ ಲಾಭ`).
- If $\Delta \text{Net} \le -200$: Marked with warning badge (`-₹X ಕಡಿಮೆ (ಸಾರಿಗೆ ನಷ್ಟ)`).
- If $\Delta \text{Net} \approx 0$: Marked as comparable.

---

## 3. Implemented Components

### 3.1 Service Layer
- **`app/Services/Market/WhereToSellService.php`**:
  - Location resolution via GPS coordinates, Taluk centroid, District centroid, or default state fallback.
  - Vehicle profiles and customizable rate overrides.
  - Candidate APMC market discovery with Karnataka-only boundary constraint.
  - Sorting by `net_realization` (default), `price_desc`, and `distance_asc`.

### 3.2 Controllers & Routes
- **Web Controller**: `app/Http/Controllers/Farmer/WhereToSellController.php`
  - Route: `GET /where-to-sell` -> `farmer.decision.where-to-sell`.
- **API Controller**: `app/Http/Controllers/Api/V1/WhereToSellApiController.php`
  - Route: `GET /api/v1/decision/where-to-sell` -> `api.v1.decision.where-to-sell`.
- **Crop Detail Enhancement**: `app/Http/Controllers/Farmer/CropController.php`
  - Attached `today_modal_price` to `$availableMarkets` for instant quick-chip price display.

### 3.3 User Interfaces
- **Dedicated Simulator**: `resources/views/farmer/decision/where_to_sell.blade.php`:
  - 1-click crop selector chips + searchable select.
  - Harvest quantity input with fast stepper pills (5Q, 10Q, 20Q, 50Q).
  - Origin switcher: 1-click GPS auto-detect button with loader, plus District/Taluk fallback dropdowns.
  - Vehicle selector chips (Auto, Pickup, Canter) with custom rate drawer.
  - #1 Recommended Mandi showcase card with big in-pocket figures.
  - Ranked comparison cards with gross, haulage, fee, and net realization breakdowns.
- **Crop Detail Page Integration** (`resources/views/farmer/crops/show.blade.php`):
  - Horizontal quick market chips (similar to Negilu Krushi) with live today's modal prices.
  - Direct callout banner linking to `/where-to-sell?crop={slug}`.
- **Navigation Layouts** (`resources/views/layouts/farmer.blade.php`):
  - Desktop nav link: "Where to Sell (ಎಲ್ಲಿ ಮಾರಾಟ?)".
  - Mobile bottom navigation: Dedicated "Sell" tab with ⚖️ icon.

---

## 4. Public REST API

Endpoint: `GET /api/v1/decision/where-to-sell`

### Query Parameters:
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `crop` / `crop_id` | string / int | Yes | Crop slug (e.g. `tomato`) or ID |
| `quantity` | float | No | Quantity in quintals (default: 10.0) |
| `vehicle` | string | No | `auto`, `pickup` (default), `truck` |
| `custom_rate` | float | No | Custom ₹/km transport rate override |
| `lat`, `lng` | float | No | Farmer GPS coordinates |
| `district_id` | int | No | Fallback district ID |
| `taluk_id` | int | No | Fallback taluk ID |
| `sort` | string | No | `net_realization` (default), `price_desc`, `distance_asc` |

### Sample Response:
```json
{
  "success": true,
  "crop": { "id": 1, "name": "Tomato", "slug": "tomato" },
  "origin": { "latitude": 13.1367, "longitude": 78.1291, "name": "Current GPS Location" },
  "quantity_quintals": 10.0,
  "vehicle": { "key": "pickup", "name_en": "Pickup / Bolero", "rate_per_km": 22.0 },
  "markets_count": 5,
  "recommended_market": {
    "market_name": "Kolar APMC",
    "market_name_kn": "ಕೋಲಾರ",
    "modal_price": 2800.0,
    "distance_km": 24.5,
    "gross_revenue": 28000.0,
    "transport_cost": 539.0,
    "apmc_cess": 420.0,
    "hamali": 100.0,
    "net_realization": 26941.0,
    "net_rate_per_qtl": 2694.1,
    "verdict_kn": "ಹೆಚ್ಚುವರಿ ನಿವ್ವಳ ಲಾಭ: ₹4,200 (ಸಾರಿಗೆ ಕಳೆದ ನಂತರ ಲಾಭದಾಯಕ)",
    "google_maps_url": "https://www.google.com/maps/dir/?..."
  }
}
```

---

## 5. Automated Testing & Verification

Run tests:
```bash
php artisan test --filter=WhereToSell
```
Result: **9 passed (58 assertions)**.

Run full application regression suite:
```bash
php artisan test
```
Result: **114 passed (1,170 assertions), 100% green**.

Front-end build:
```bash
npm run build
```
Result: Succeeded with 0 errors (`built in 1.31s`).
