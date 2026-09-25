# VIEW DIFFERENT MARKET — Proximity Filtering, Sorting & Smart Badges

This document details the architecture and implementation of the **Farmer-Facing Market Discovery System** on Krushi Baandhava (`/crop/{slug}`).

---

## 1. Overview & Objectives

In agricultural price discovery, farmers evaluate trade-offs between **transport distance** and **crop price**:
1. **Distance-Constrained Relevance:** Instead of overwhelming a farmer with every APMC across Karnataka (some 500+ km away), each crop has an admin-controlled radius (`market_radius_km`).
2. **Proximity vs. Profit Sort Order:** Farmers can toggle between:
   * **`📍 Nearest First`**: Arranges mandis by shortest driving distance from the farmer's GPS/district.
   * **`💰 Highest Price First`**: Arranges mandis by maximum modal price today to find top-paying markets.
3. **Smart Visual Badging:**
   * **`📍 Nearest`**: Automatically attached to the closest active mandi.
   * **`🔥 Top Rate`**: Automatically attached to the market offering the highest price today in Karnataka.
   * **`★ Selected`**: Highlights the active market currently displayed on the card.
4. **Accurate Distance Attribution:**
   * The top distance chip only displays **`📍 Nearest Market • {X} km`** if the farmer is currently inspecting the actual closest market.
   * If inspecting a distant market (e.g., Mangaluru 301 km away while Tumakuru is 67 km), it displays **`📍 301 km away`** to avoid misleading the user.

---

## 2. Technical Architecture

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        KRUSHI BAANDHAVA MARKET DISCOVERY ENGINE                        │
├────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                        │
│   [ Admin Configuration (crops table) ]                                                │
│    • market_radius_km: 350 km (e.g. Arecanut)                                          │
│    • default_market_sort: 'nearest_first'                                              │
│    • allow_user_sort_toggle: true                                                      │
│    • enable_smart_badges: true                                                         │
│                                                                                        │
│   [ Farmer Location Resolution ]                                                       │
│    • User GPS coordinates or district centroid ($refLat, $refLon)                     │
│    • Haversine distance computation for each active mandi ($m->distance_km)            │
│                                                                                        │
│   [ Classification & Tagging ]                                                         │
│    • $nearestMarketId: Market with min($distance_km)                                   │
│    • $topRateMarketId: Market with max($today_modal_price)                             │
│    • Within Radius vs. Beyond Radius partitioning                                      │
│                                                                                        │
│   [ Reactive Frontend (show.blade.php + Alpine.js) ]                                   │
│    • 1-Tap Toggle: [📍 Nearest First] / [💰 Highest Price] (zero page reload)          │
│    • Smart Badges: 📍 Nearest, 🔥 Top Rate, ★ Active                                   │
│    • Expandable link: "+ Show N more mandis beyond {X} km"                             │
│                                                                                        │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Database Schema Reference

The `crops` table stores these configuration parameters:

| Column | Type | Default | Purpose |
|---|---|---|---|
| `market_radius_km` | `INT UNSIGNED` | `300` | Search radius in KM (0 or >=500 = Unlimited / All KA) |
| `default_market_sort` | `VARCHAR(30)` | `'nearest_first'` | Default order (`nearest_first` or `highest_price_first`) |
| `allow_user_sort_toggle` | `BOOLEAN` | `true` | Show/hide the interactive sort switcher on website |
| `enable_smart_badges` | `BOOLEAN` | `true` | Show/hide the `📍 Nearest` and `🔥 Top Rate` pill tags |

---

## 4. User Experience Walkthrough

### Default Proximity View (e.g., User in Bengaluru Urban)
For Arecanut (`market_radius_km` = 350 km):
1. **Tumakuru APMC (67 km):**
   * Tagged with: `📍 NEAREST`
   * Rate: `₹47,500`
2. **Channagiri APMC (215 km):**
   * Rate: `₹45,585`
3. **Shivamogga APMC (244 km):**
   * Tagged with: `🔥 TOP RATE`
   * Rate: `₹50,000`
4. **Puttur APMC (260 km):**
   * Rate: `₹46,800`
5. **Mangaluru APMC (301 km):**
   * Rate: `₹48,200`
6. **Sagara APMC (307 km):**
   * Rate: `₹45,999`
7. **Sirsi APMC (TSS) (350 km):**
   * Rate: `₹46,024`

### When Farmer Toggles to "Highest Price First":
The list instantly rearranges without page reload:
1. **Shivamogga APMC (244 km)** — `₹50,000` (`🔥 TOP RATE`)
2. **Mangaluru APMC (301 km)** — `₹48,200`
3. **Tumakuru APMC (67 km)** — `₹47,500` (`📍 NEAREST`)
4. **Puttur APMC (260 km)** — `₹46,800`
5. **Sirsi APMC (TSS) (350 km)** — `₹46,024`
6. **Sagara APMC (307 km)** — `₹45,999`
7. **Channagiri APMC (215 km)** — `₹45,585`

---

## 5. UI Cleanliness & Streamlined Layout
- **No Bulky Dropdown**: The redundant `<select>` dropdown has been eliminated. All mandis are directly accessible via fast, clickable pill chips with live prices, distance indicators, and badges.
- **Reset Button**: If a farmer clicks a specific mandi, a discreet `✕ Reset` button appears in the header allowing them to return to their local nearest mandi with one tap.

---

## 6. Verification & Testing

Feature tests in `tests/Feature/FarmerMarketDiscoveryAndSortingTest.php` verify:
1. Distance calculation from farmer location to Karnataka mandis.
2. Accurate tagging of the nearest market ID and highest rate market ID.
3. Sorting by proximity (nearest first) vs sorting by price (highest rate first).
4. Respecting admin toggles for `allow_user_sort_toggle` and `enable_smart_badges`.
5. Non-nearest markets display accurate `{X} km away` label rather than mislabeling as `nearest market`.
6. Radius threshold filtering and expand toggle.
