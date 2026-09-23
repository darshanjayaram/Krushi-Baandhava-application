# Implementation Plan: Phase 5 — Farmer Market Price Discovery UI (Negilu Krushi Benchmark)

Build a mobile-first, high-performance farmer price discovery interface for **Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)**. The UI will deliver the speed, clarity, and farmer-friendliness that makes Negilu Krushi popular, but with our own **distinctive, modern design system** (warm emerald/amber palette, crisp typography, clean card architecture, rich Kannada bilingual support, and one-tap WhatsApp sharing).

---

## 1. User Constraints & Design Principles

> [!IMPORTANT]
> **User Mandate**: The UI must be **different from Negilu Krushi**, but equally or more **user-friendly**. Do NOT clone or copy the visual layout of Negilu Krushi.

### Design Differentiation Strategy:
| Feature Dimension | Negilu Krushi Pattern | Krushi Baandhava Modern UI |
| :--- | :--- | :--- |
| **Color & Theme** | Generic green/white, high border contrast | Warm Forest Emerald (`#047857`), soft warm paper backdrop (`#faf9f5`), Golden Harvest (`#f59e0b`) accents, multi-layer soft shadows |
| **Header & Top Bar** | Dense banner with small district toggle | Clean sticky mobile header with instant District bottom-sheet modal + active mandi count pill |
| **Top Highlights** | Static 2x2 grid of 4 hardcoded crops | Dynamic **Price Movers & Trends Carousel** with actual daily market prices, modal rates, and day-over-day changes |
| **Commodity Filtering** | Flat text buttons | Rich pill category chips (All, Commercial [ವಾಣಿಜ್ಯ], Cereals [ಧಾನ್ಯಗಳು], Spices [ಮಸಾಲೆ], Vegetables [ತರಕಾರಿಗಳು]) with commodity counts |
| **Mandi Price Card** | Basic table borders | Modern elevated card: Crop (EN + KN badge), Variety, APMC Mandi & District, large bold Modal Price (`₹XX,XXX / Quintal`), visual Min-Max spread bar, Arrival Quantity, Freshness provenance badge, and **1-tap WhatsApp Share** |
| **Search & Discovery** | Small search input | Prominent dual search bar supporting text & Web Speech voice input in Kannada (`kn-IN`) and English |
| **Mandi Comparison** | Basic list | Side-by-side or stacked Mandi comparison ranking all APMC mandis trading that commodity by highest price |

---

## 2. Architecture & Pages to Implement

```
                                Farmer PWA Navigation
                                         │
     ┌───────────────────┬───────────────┴───────────────┬───────────────────┐
     ▼                   ▼                               ▼                   ▼
  Home Screen       Crops Directory                 APMC Mandis        Price Search API
  (Real Live Rates, (All Karnataka Commodities,     (All APMC Centers, (/api/v1/prices/daily,
   Pill Filters,     Categories & Varieties)         Reporting Crops)   JSON endpoint)
   WhatsApp Share)       │                               │
                         ▼                               ▼
                  Crop Detail Page               Market Detail Page
                  (/crops/{slug})                (/markets/{code})
                  (Mandi Comparison,             (Today's Arrivals &
                   High/Low, Varieties)           Traded Commodities)
```

### Proposed Files & Components:

#### Controllers & Services:
1. **[MODIFY] `app/Http/Controllers/Farmer/HomeController.php`**:
   - Replace sample hardcoded prices with real database queries from `MarketPrice::with(['crop', 'variety', 'market.district', 'dataSource'])`.
   - Implement category-based price filtering and district scoping.
   - Calculate high/low/average state prices and top price movers.
2. **[NEW] `app/Http/Controllers/Farmer/CropController.php`**:
   - `index()`: Catalog of all active crops grouped by category with reporting mandi count.
   - `show($slug)`: Detail page for a specific crop showing today's rates across all APMC mandis, variety breakdowns, state min/max/average, and price spread ranking.
3. **[NEW] `app/Http/Controllers/Farmer/MarketProfileController.php`**:
   - `index()`: Directory of Karnataka APMC mandis filterable by district.
   - `show($code)`: Mandi profile displaying all crops traded today, modal prices, arrivals, address, and district information.
4. **[NEW] `app/Http/Controllers/Api/V1/PriceApiController.php`**:
   - `/api/v1/prices/today`: Fast JSON feed of today's prices filterable by crop, district, and market for instant client-side search and caching.

#### Blade Views:
5. **[MODIFY] `resources/views/layouts/farmer.blade.php`**:
   - Update navigation links in bottom navigation bar (`Home`, `Crops`, `Mandis`, `Search`).
   - Enhance mobile header with active district pill and smooth district selector modal.
6. **[MODIFY] `resources/views/farmer/home.blade.php`**:
   - Complete redesign using the new modern UI:
     - District & Live APMC Status Hero Card
     - Quick Search Bar with Instant Filter
     - Category Filter Chips (All, Commercial, Cereals, Spices, Vegetables)
     - Today's Mandi Price Cards with large readable prices, spread bars, freshness provenance, and WhatsApp share buttons
     - Major Karnataka Crops Quick Navigation Grid
     - District Switcher Bottom Sheet / Modal
7. **[NEW] `resources/views/farmer/crops/index.blade.php`**:
   - Searchable crop catalog with category tabs, icons, variety counts, and direct links to live prices.
8. **[NEW] `resources/views/farmer/crops/show.blade.php`**:
   - Crop detail screen: State price summary (High, Low, Average), variety filter tabs, all reporting APMC mandis sorted by price, and WhatsApp share button for the crop.
9. **[NEW] `resources/views/farmer/markets/index.blade.php`**:
   - APMC mandi directory with district filter tabs and reporting crop count badges.
10. **[NEW] `resources/views/farmer/markets/show.blade.php`**:
    - Mandi profile page showing all commodities traded today with modal, min, max, and arrivals.

---

## 3. Verification Plan

### Automated Tests:
- `tests/Feature/FarmerPriceDiscoveryTest.php`:
  - Test home screen loads with real database prices and handles empty price states gracefully.
  - Test filtering home screen by district displays only that district's mandi prices.
  - Test crops directory displays all categories and active crops.
  - Test crop detail page (`/crops/{slug}`) displays mandis and prices.
  - Test markets directory and mandi detail page (`/markets/{code}`) display traded crops.
  - Test public API `/api/v1/prices/today` returns valid JSON with proper structure.

### Manual & Performance Verification:
- Verify responsive layout on mobile viewport (360px, 390px, 412px) and desktop.
- Verify zero external API calls on page render (all queries run against local MySQL).
- Verify `npm run build` succeeds without CSS/JS errors.
- Verify full test suite passing (`php artisan test`).
