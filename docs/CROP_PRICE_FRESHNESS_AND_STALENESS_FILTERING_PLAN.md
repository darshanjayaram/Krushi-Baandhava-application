# 100% Crop Price Freshness & Dynamic Category Staleness Implementation Plan

**Location:** `docs/CROP_PRICE_FRESHNESS_AND_STALENESS_FILTERING_PLAN.md`  
**Status:** Approved & Ready to Execute  
**Date:** October 2026  
**Scope:** Admin-Configurable Price Freshness Thresholds, Per-Category Dynamic Overrides, and Strict Outdated Data Suppression on the Farmer Crop Page.

---

## 1. Problem Definition & Architectural Goals

### 1.1 The Issue
Currently, on the farmer crop page (`/crops/{crop}`), when fetching market prices and available varieties, the query relies on:
```php
$latestDate = MarketPrice::where('crop_id', $crop->id)->max('price_date');
```
If a seasonal crop variety or a specific mandi has not reported any auctions for **30, 60, or 90 days**, the system still loads that ancient date and displays it as current rates. This misleads farmers into thinking outdated rates are actively being traded in mandis today.

### 1.2 The Solution
Implement an admin-governed **Data Freshness Cutoff Engine**:
1. **Global Default Staleness Limit:** A master setting in `system_settings` (`crop_price_staleness_days`, default: `14` days).
2. **Per-Category Dynamic Staleness:** Fine-grained overrides stored in `system_settings` (`category_price_staleness_days` JSON) tailored to commodity shelf-life:
   - **Vegetables (ತರಕಾರಿಗಳು):** `7` Days (perishable, daily volatility)
   - **Fruits (ಹಣ್ಣುಗಳು):** `7` Days (perishable, rapid price shifts)
   - **Cereals & Millets (ಧಾನ್ಯಗಳು & ಸಿರಿಧಾನ್ಯಗಳು):** `21` Days (storable grains, bi-weekly auction cycles)
   - **Pulses (ಬೇಳೆಕಾಳುಗಳು):** `21` Days (storable pulses)
   - **Commercial & Plantation (ವಾಣಿಜ್ಯ & ತೋಟಗಾರಿಕೆ):** `30` Days (Arecanut, Copra, Coconut, Cotton, Coffee)
   - **Spices (ಮಸಾಲೆ ಬೆಳೆಗಳು):** `30` Days (Black Pepper, Cardamom, Dry Chillies, Ginger)
   - **Oilseeds (ಎಣ್ಣೆಕಾಳುಗಳು):** `30` Days (Groundnut, Sunflower, Soyabean)
3. **Strict Exclusion:** Any crop variety or APMC mandi whose latest trade date is older than the computed cutoff date (`today - thresholdDays`) will be **100% hidden** from the crop page.
4. **Clean Empty State:** If no auctions exist within the freshness window for an entire crop, the page displays:  
   *"No active Karnataka APMC auctions recorded within the last X days."*

---

## 2. Technical Design & Flow

```mermaid
flowchart TD
    A[Admin Configures Freshness Settings] -->|Global & Per-Category Days| B[SystemSetting in MySQL]
    B -->|Cached 300s| C[Crop Model: getFreshnessCutoffDate]
    D[Farmer loads /crops/{crop}] --> C
    C -->|Calculates cutoff: today - thresholdDays| E[CropController::show]
    E --> F[Filter Available Varieties]
    E --> G[Filter Available APMC Mandis]
    F -->|max price_date >= cutoff| H[Display in Variety Selector]
    F -->|max price_date < cutoff| I[Strictly Excluded / Hidden]
    G -->|price_date >= cutoff| J[Display in Mandi Ranking Cards]
    G -->|price_date < cutoff| K[Strictly Excluded / Hidden]
```

---

## 3. Implementation Steps

### Phase 1: Database & System Settings
1. Add initial settings in `SystemSetting`:
   - `crop_price_staleness_days`: integer `14`
   - `category_price_staleness_days`: JSON
     ```json
     {
       "vegetables": 7,
       "fruits": 7,
       "cereals-millets": 21,
       "pulses": 21,
       "commercial-plantation": 30,
       "spices": 30,
       "oilseeds": 30,
       "commercial-crops": 30
     }
     ```
2. Add helper methods in `app/Models/Crop.php`:
   - `getStalenessThresholdDays(): int` — evaluates category override or falls back to global default.
   - `getFreshnessCutoffDate(): string` — computes `Carbon::today()->subDays($days)->toDateString()`.

### Phase 2: Admin Panel Settings UI
1. In `resources/views/admin/settings/index.blade.php`:
   - Under the **Market & Price Ingestion** section, build a clean management card:
     - Global Default Staleness Window (Input: days).
     - Category-wise Staleness Table (Inputs: days for each active category).
2. In `app/Http/Controllers/Admin/SettingController.php`:
   - Ensure saving supports the category JSON mapping and automatically clears cache.

### Phase 3: Enforce Strict Filtering in `CropController.php`
1. Update `app/Http/Controllers/Farmer/CropController.php`:
   - Retrieve `$cutoffDate = $crop->getFreshnessCutoffDate();`.
   - Constrain `$pricedVarietyIds` query with `price_date >= $cutoffDate`.
   - Constrain `$recentMarketPrices` with `price_date >= $cutoffDate`.
   - Constrain `$mandiPricesQuery` with `price_date >= $cutoffDate`.
   - Remove the old fallback in line 443 that pulled ancient data from months ago when `$recentMarketPricesRaw` was empty.
   - If `$mandiGroups` is empty, render the clean notice with the exact threshold days.

### Phase 4: Verification & Smoke Testing
1. Test with fresh crops (e.g. Tomato, Paddy) $\rightarrow$ confirm active varieties and live mandis display correctly.
2. Test setting threshold to a very low number (e.g. 1 day) $\rightarrow$ confirm older varieties immediately disappear.
3. Test setting threshold back to default (7 days / 14 days) $\rightarrow$ confirm expected active items appear.
4. Run `php artisan test` or verify routes with 0 errors.

---

## 4. Deliverables Checklist

- [x] Register `crop_price_staleness_days` and `category_price_staleness_days` in `SystemSetting`.
- [x] Implement `getStalenessThresholdDays()` and `getFreshnessCutoffDate()` on `Crop` model.
- [x] Add Staleness Configuration UI in Admin Settings (`admin/settings`).
- [x] Update `CropController.php` to strictly enforce `$cutoffDate` and suppress stale varieties/mandis.
- [x] Verify functionality across different crop categories (Vegetables, Cereals, Plantation).
- [x] Update documentation with verified results.

---

## 5. Verification & Test Results

1. **Active Commodities (Fresh Data within Cutoff):**
   - **Tomato (Vegetables, 7-day cutoff `2026-09-25`):** Displays active varieties (Hybrid) and 33 Karnataka mandis reporting recent auctions up to `2026-10-02`.
   - **Paddy (Cereals & Millets, 21-day cutoff `2026-09-11`):** Displays active varieties (IR-64, etc.) and 64 Karnataka mandis reporting auctions up to `2026-10-01`.
   - **Arecanut (Commercial & Plantation, 30-day cutoff `2026-09-02`):** Displays active varieties (Api, etc.) and 56 Karnataka mandis reporting auctions up to `2026-10-02`.
   - **Coffee (Commercial & Plantation, 30-day cutoff `2026-09-02`):** Displays official Coffee Board centres (Chikmagalur, Hassan, Mudigere) with active rates up to `2026-10-01`.

2. **Off-Season / Stale Commodities (Prices Exceeding Cutoff):**
   - **Millets (Last traded `2026-08-26`, Cutoff `2026-09-02`):** `latestDate`, `availableVarieties`, `availableMarkets`, `mandiGroups`, and `selectedMarketPrices` are all strictly suppressed to 0 / null. Displays clean, informative empty state: *"No Karnataka APMC auctions recorded within the last 30 days."* / *"ಕಳೆದ 30 ದಿನಗಳಲ್ಲಿ ಯಾವುದೇ ಕರ್ನಾಟಕ ಮಂಡಿಗಳಲ್ಲಿ ದರ ದಾಖಲಾಗಿಲ್ಲ."*
   - **Jack Fruit (Last traded `2026-08-05`, Cutoff `2026-09-02`):** All stale records strictly hidden; renders empty state notice cleanly.
   - **Seethaphal (Last traded `2026-08-19`, Cutoff `2026-09-02`):** All stale records strictly hidden; renders empty state notice cleanly.

---

## 6. Dedicated Admin Module & Asynchronous Operations

A standalone module has been added to the Admin Panel:

- **Sidebar Menu:** Added `Price Freshness Rules` (⏳) under **Data & Mandi Management**.
- **Route:** `GET /admin/price-freshness` (`admin.price-freshness.index`).
- **Controller:** [`app/Http/Controllers/Admin/PriceFreshnessController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Admin/PriceFreshnessController.php).
- **View:** [`resources/views/admin/price-freshness/index.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/admin/price-freshness/index.blade.php).

### Features & Asynchronous Capabilities:
1. **100% Async Operations (No Page Reloads):**
   - **Async Save:** `POST /admin/price-freshness` saves global and per-category days via `fetch()` and displays an instant toast notification.
   - **Live Simulation:** `POST /admin/price-freshness/simulate` recalculates which commodities become active vs suppressed immediately on screen without saving to DB.
   - **Async Reset Defaults:** `POST /admin/price-freshness/reset` restores standard defaults (Vegetables: 7d, Cereals: 21d, Plantation: 30d) instantly.
2. **Interactive Commodity Explorer:**
   - Real-time search by crop name (English and Kannada).
   - Filter tabs: `All`, `🟢 Active`, `🔴 Suppressed`.
   - Displays applied threshold, calculated cutoff date, last recorded trade date, and active APMC mandis for every crop in Karnataka.


