# Variety-Wise Latest Price & Rolling Window Architecture (Negilu Krishi Alignment)

## 1. Overview & Business Rationale

In agricultural markets across Karnataka (such as **Koppa, Thirthahalli, Sagara, Sirsi, Tumakuru, Kolar, and Yeshwanthpur**), APMC auctions for different varieties and grades do not always occur on every single calendar day:
- **Example (Koppa Market Arecanut)**:
  - `Rashi · Average` traded on **22 Sep** (₹49,074)
  - `Sippegotu · Average` traded on **22 Sep** (₹13,000)
  - `Gorabalu` traded on **22 Sep** (₹26,000)
  - `Saraku · Average` was last traded on **19 Sep** (₹56,685)
  - `Bette · Average` was last traded on **19 Sep** (₹51,783)

### The Problem in Legacy Implementation:
Previously, the backend resolved market prices strictly by querying:
```php
$marketLatestDate = MarketPrice::where('market_id', $selectedMarket->id)->max('price_date');
$selectedMarketPrices = MarketPrice::where('market_id', $selectedMarket->id)
    ->where('price_date', $marketLatestDate) // ❌ Strict single date filter
    ->get();
```
Under this rigid filter, on 22 Sep, the query would only return `Rashi`, `Sippegotu`, and `Gorabalu`. `Saraku` and `Bette` were completely omitted from the "Pick your grade" pills, giving farmers the false impression that Saraku and Bette were not traded or unavailable.

### The Solution (Variety-Wise Independent Rolling Window):
Following the exact architecture of **Negilu Krishi**:
1. **Recent Trade Window (14-Day Cycle)**: Query all trade records for the selected APMC within the rolling 14-day cycle (`>= today - 14 days`).
2. **Variety-Wise Latest Record**: Group records by `variety_id` (and `grade`) and select each variety's **most recent trading session record** (`orderBy('price_date', 'desc')`).
3. **Clean Variety Pills (No Clutter)**: The variety strip displays `{Variety} · {Grade}` and `{Price}` (e.g. `Saraku · Average ₹56,685`, `Rashi · Average ₹49,074`), without dates inside the pills.
4. **Synchronized Hero Card**:
   - When a farmer clicks any variety (e.g. `Saraku`), the Hero section displays that specific variety's price (`₹56,685`) and its exact traded date (`as of 19 Sep`).
   - When clicking `Rashi`, the Hero section displays `₹49,074` and `as of 22 Sep`.
   - Day-over-Day (DoD) trend calculates against that specific variety's previous auction day.

---

## 2. Technical Architecture & Query Flow

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ USER VISITS CROP DETAIL PAGE (e.g., Arecanut @ KOPPA APMC)                             │
│ GET /crop/1?market=Koppa+APMC&variety=2                                                │
└────────────────────────────────────────┬───────────────────────────────────────────────┘
                                         │
                                         ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ 1. VARIETY-WISE ROLLING WINDOW RESOLUTION (`Farmer\CropController`)                     │
│ Query: `market_prices` for `market_id = 45` AND `price_date >= (max_date - 14 days)`  │
│ Order by `price_date DESC`, `modal_price DESC`                                         │
│ Group by `variety_id + '_' + grade`                                                    │
│ Extract first record per group -> array of latest variety records                      │
└────────────────────────────────────────┬───────────────────────────────────────────────┘
                                         │
                                         ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ 2. DYNAMIC VARIETY & HERO SYNCHRONIZATION                                              │
│ - Requested Variety: `variety_id = 2` (Saraku · Average)                               │
│ - Active Price Item: Traded Date = 2026-09-19, Price = ₹56,685                         │
│ - Previous Session: Traded Date < 2026-09-19 for Saraku -> Calculates DoD Trend        │
│ - Hero Card: Displays `₹56,685`, `as of 19 Sep`, `Updated: 19 Sep 2026`                │
└────────────────────────────────────────┬───────────────────────────────────────────────┘
                                         │
                                         ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ 3. VARIETY STRIP RENDERING (`show.blade.php`)                                          │
│ [Rashi · Average ₹49,074] [Saraku · Average ₹56,685 (Active)] [Bette · Average ₹51,783]│
│ [Gorabalu ₹26,000] [Sippegotu · Average ₹13,000]                                       │
│ Header text: "Trades this week: ₹13,000 – ₹56,685 (5 varieties)"                       │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Implementation Details

### A. Controller Logic (`app/Http/Controllers/Farmer/CropController.php`)
```php
// Fetch latest traded prices for this selected market across its active rolling trading window (14 days)
// Negilu Krishi alignment: Each variety resolves independently to its own latest trading session record.
$selectedMarketPrices = collect();
if ($selectedMarket) {
    $recentMarketPricesRaw = (clone $basePricesQuery)
        ->with(['variety', 'market.district', 'dataSource'])
        ->where('market_id', $selectedMarket->id)
        ->where('price_date', '>=', $recentCycleThreshold)
        ->where('modal_price', '>', 0)
        ->orderBy('price_date', 'desc')
        ->orderBy('modal_price', 'desc')
        ->get();

    // Fallback if no records in strict 14 days, get latest trading window for this market
    if ($recentMarketPricesRaw->isEmpty()) {
        $marketMaxDate = (clone $basePricesQuery)->where('market_id', $selectedMarket->id)->max('price_date');
        if ($marketMaxDate) {
            $fallbackThreshold = Carbon::parse($marketMaxDate)->subDays(14)->toDateString();
            $recentMarketPricesRaw = (clone $basePricesQuery)
                ->with(['variety', 'market.district', 'dataSource'])
                ->where('market_id', $selectedMarket->id)
                ->where('price_date', '>=', $fallbackThreshold)
                ->where('modal_price', '>', 0)
                ->orderBy('price_date', 'desc')
                ->orderBy('modal_price', 'desc')
                ->get();
        }
    }

    $selectedMarketPrices = $recentMarketPricesRaw
        ->groupBy(function ($item) {
            return ($item->variety_id ?? 'default') . '_' . ($item->grade ?? '');
        })
        ->map(fn($recs) => $recs->first())
        ->sortByDesc('modal_price')
        ->values();
}
```

### B. View Template Synchronization (`resources/views/farmer/crops/show.blade.php`)
- **Variety Selection Matching**: Checks both `variety_id` and optional `grade` parameter so distinct grades of the same variety can be highlighted accurately.
- **Weekly Trading Range Summary**: Displays `Trades this week: ₹{min} – ₹{max} ({count} varieties)` matching Negilu Krishi.
- **Hero Card Date**: Formats `activePriceItem->price_date` to show `as of {D M}` and `Updated: {D M Y}` reflecting the selected variety.

---

## 4. Universal Application Across All Crops

This logic is completely crop-agnostic and applies automatically to:
1. **Arecanut** (Thirthahalli, Sagara, Koppa, Sirsi, Tumakuru, Channagiri)
2. **Tomato** (Kolar, Binny Mill Bangalore, Ramanagara, Chikkaballapura)
3. **Paddy & Rice** (Raichur, Gangavathi, Davanagere, Shimoga)
4. **Onion, Maize, Cotton, Turmeric, Ginger, Coconut, Coffee**, etc.
