# Phase 10 Implementation Plan: "Where to Sell" Decision Engine

## 1. Overview & Objective
When Karnataka farmers harvest their crops (e.g. 10 Quintals of Tomato, Onion, or Arecanut), they face a critical financial decision:
**"Should I sell at my local APMC mandi at a lower price, or hire a vehicle and transport the harvest to a distant mandi offering a higher price?"**

A distant mandi might quote ₹300/Quintal higher, but hiring a pickup truck for 60 km might cost ₹2,800, turning a seeming advantage into a net financial loss.

**Phase 10: "Where to Sell" Decision Engine (ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?)** provides farmers with an interactive, transparent, and mathematically grounded decision-making tool. It calculates:
1. **Gross Revenue**: $\text{Quantity (Q)} \times \text{Mandi Modal Price (₹/Q)}$
2. **Estimated Haulage / Transport Cost**: Distance $\times$ Rural road factor ($1.2\times$) $\times$ Vehicle rate per km
3. **Mandi Handling & Cess**: 1.5% APMC market fee + ₹10/Q loading-unloading handling
4. **Net Realization (ನಿವ್ವಳ ಆದಾಯ)**: $\text{Gross Revenue} - \text{Transport Cost} - \text{Mandi Deductions}$
5. **Comparative Advantage vs. Nearest Mandi**: Transparently informs whether travelling to a distant mandi yields a net extra profit or an unintended loss.

---

## 2. User Review Required

> [!IMPORTANT]
> **No Black-Box Scoring**: In strict adherence to project principles, every calculation (distance, transport rate, APMC cess, net profit) is completely open, auditable, and adjustable by the farmer. No arbitrary "AI score" is shown without underlying financial numbers.
>
> **Karnataka-Only Mandi Guarantee**: Only Karnataka APMC markets are considered in comparisons; out-of-state mandis remain strictly filtered out.

---

## 3. Proposed Architecture & Changes

### A. Core Decision Service: `app/Services/Market/WhereToSellService.php`
- `compare(int $cropId, float $latitude, float $longitude, float $quantityQuintals = 10.0, array $options = []): Collection`
- Geolocation resolution: accepts GPS coordinates, or automatically resolves representative centroids of selected Taluk or District.
- Haversine spherical distance with $1.2\times$ rural route winding multiplier.
- Vehicle transport cost profiles:
  - **Auto / 3-Wheeler** (up to 8 Q): ₹15/km (min ₹200)
  - **Pickup / Bolero 1.5T** (up to 20 Q): ₹22/km (min ₹400) — *Default*
  - **Mini Truck / Canter 4T** (up to 50 Q): ₹32/km (min ₹800)
  - Customizable rate-per-km override for farmer-specific negotiated rates.
- APMC Cess & Hamali (handling) estimation.
- Computes baseline distance to nearest market and calculates $\Delta \text{Net Profit}$ for every competing market.
- Sort options: `net_realization` (default), `price_desc`, `distance_asc`.

### B. Controllers & Routing
1. **Web Controller**: `app/Http/Controllers/Farmer/WhereToSellController.php`
   - Route: `GET /where-to-sell` (`farmer.decision.where-to-sell`)
   - Pre-fills with selected crop, default quantity (10 Q), active district, or GPS coordinates.
2. **API Controller**: `app/Http/Controllers/Api/V1/WhereToSellApiController.php`
   - Route: `GET /api/v1/decision/where-to-sell`
   - Returns structured JSON comparison matrix for mobile apps and third-party integrations.

### C. Farmer UI: `resources/views/farmer/decision/where_to_sell.blade.php`
- **Interactive Simulator Form**:
  - Crop selection chips/dropdown with current market activity badges.
  - Quantity input (stepper for Quintals).
  - Origin switcher: "Use GPS Location" button + District/Taluk dropdown fallback.
  - Vehicle type selector chips (Auto, Pickup, Mini Truck) with rate customization drawer.
  - Real-time sort toggles: Net Realization (ಅತ್ಯಧಿಕ ನಿವ್ವಳ ಲಾಭ), Mandi Price, Distance.
- **Decision Recommendation Card**:
  - Highlights the **#1 Recommended APMC Mandi**.
  - Shows net in-pocket earnings and $\Delta$ extra profit vs. selling locally.
- **Detailed Mandi Comparison Matrix**:
  - Mandi card list & responsive comparison table.
  - Visual breakdown: Gross price $\rightarrow$ Haulage $\rightarrow$ APMC fee $\rightarrow$ Net Realization.
  - Google Maps direct navigation button ("ಹೋಗುವ ಮಾರ್ಗ").

### D. Navigation & Cross-Linking Integration
- **Desktop Navigation Bar** in `resources/views/layouts/farmer.blade.php`: Add "Where to Sell (ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?)" link.
- **Mobile Thumb-zone Navigation**: Add/update quick access to Decision Engine.
- **Crop Detail Screen** (`resources/views/farmer/crops/show.blade.php`): Add a prominent call-to-action button: "Compare Mandis & Haulage / ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?" linking directly to `/where-to-sell?crop={slug}`.

---

## 4. Verification Plan

### Automated Tests:
1. `tests/Unit/WhereToSellServiceTest.php`:
   - Unit test mathematical calculation of Gross Revenue, Transport Cost, Mandi Cess, and Net Realization.
   - Unit test vehicle type rates and distance scaling.
   - Unit test comparison vs nearest mandi ($\Delta$ Net Profit).
   - Unit test sorting by net profit, price, and distance.
   - Unit test strict Karnataka market scoping.
2. `tests/Feature/FarmerWhereToSellTest.php`:
   - Feature test web page `/where-to-sell` renders and responds to crop/location/quantity query params.
   - Feature test API endpoint `GET /api/v1/decision/where-to-sell` returns structured JSON with 200 OK.
   - Feature test fallback to district/taluk centroid when GPS coordinates are omitted.
3. Full regression suite: `php artisan test` (must remain 100% green, $\ge 110$ tests).
4. Asset compilation: `npm run build` with 0 errors.
