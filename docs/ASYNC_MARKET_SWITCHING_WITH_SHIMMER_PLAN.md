# Async Market Switching with Shimmer Effect Implementation Plan

## 1. Context & Motivation
On the crop detail page (`/crops/{slug}`), selecting an alternate APMC mandi inside the **"VIEW DIFFERENT MARKET (All Mandis)"** section currently triggers a full page GET reload (`<a href="?market=...">`).
This causes:
1. Full browser re-render, screen flicker, and network overhead.
2. The page scroll resets to the top `(0, 0)`, forcing farmers on mobile and desktop to scroll down repeatedly.
3. Laggy user experience, especially on mobile 3G/4G connections.

## 2. Objective
Implement a smooth, client-side **asynchronous (AJAX / Alpine.js)** market switcher with:
- **Instant touch feedback** on the tapped mandi pill.
- **Zero page reload and zero scroll reset** — user remains exactly where they were looking.
- **Graceful shimmer/skeleton effect** during the brief data fetch (~60–120ms).
- **Smooth auto-scroll** of the selected mandi pill inside the container.
- **Dynamic synchronization** of:
  - Box 1 Price Metrics (Modal price, min/max day range, unit, updated date, arrivals).
  - Variety/Grade Chips ("PICK YOUR GRADE").
  - "Where to Sell" button query parameters (`market_id`, `district_id`).
  - Historical Price Trend Chart (15d/30d) auto-reloads for the new mandi.
- **URL Synchronization (`history.pushState` / `popstate`)**: Keeps the URL updated with `?market=...` for sharing/bookmarking and browser Back/Forward navigation.
- **Graceful Fallback**: Native `href` remains functional if JavaScript is disabled.

---

## 3. Phase-Wise Implementation Breakdown

### Phase 1: Backend Controller Support (`CropController.php`)
- In `CropController@show`, detect if the request is an AJAX request:
  - `$request->ajax()`, `$request->wantsJson()`, or header `X-Market-Switch`.
- When an AJAX request is received:
  - Resolve the requested market via existing robust matching logic (by name/id).
  - Retrieve the active prices, grade list, day range, spread, per-kg price, date, and "Where to Sell" parameters.
  - Return a structured JSON response containing:
    - `market`: `id`, `name`, `name_kn`, `display_name`, `distance_km`, `district_name`.
    - `price_item`: `modal_price`, `modal_formatted`, `per_kg`, `price_date`, `date_formatted`, `min_price`, `max_price`, `spread_formatted`, `price_change`, `price_trend`, `unit`.
    - `grades`: Array of available grades/varieties with display labels, modal prices, and selection status.
    - `where_to_sell_url`: Dynamically calculated URL with `market_id`, `district_id`, and `variety_id`.
    - `reset_url`: URL resetting to default nearest market.

### Phase 2: Shimmer / Skeleton Markup in `show.blade.php`
- Inside Box 1 (Element 2), introduce a skeleton layout wrapper with smooth Alpine transitions:
  - `x-show="isMarketLoading"` displays the glowing animated pulse skeleton.
  - `x-show="!isMarketLoading"` displays the live price data, day range, and grade chips.
- Design matching the warm agrarian palette (`#FAF8F5`, `#E8DFC8`, `#1C5A2C`):
  - Pulse bars for Date badge, Big ₹ Modal Price, /kg price, and Day Range.
  - Shimmer pill cards for the Grade / Variety selector.
  - Maintain identical height/structure to eliminate Cumulative Layout Shift (CLS).

### Phase 3: Alpine.js Async Market Switching Engine
- Enhance Box 1's `x-data`:
  - Track `isMarketLoading: false`.
  - Track `selectedMarketId`, `selectedMarketName`, `displayPriceData`, `gradesList`, `whereToSellHref`.
  - Implement `switchMarketAsync(marketName, marketId, amUrl)`:
    1. Immediately update `selectedMarketId` and `selectedMarketName` (instant visual feedback on the pill).
    2. Set `isMarketLoading = true`.
    3. Update browser address bar using `window.history.pushState({ market: marketName, marketId }, '', amUrl)`.
    4. Fetch market data from `route('farmer.crop.detail', { crop: $crop->id, market: marketName })` with `X-Market-Switch: 1`.
    5. Update price metrics, day range, and grades from JSON response.
    6. Dispatch global event `window.dispatchEvent(new CustomEvent('market-changed', { detail: { marketId, marketName } }))`.
    7. Reset `isMarketLoading = false`.
    8. Trigger `scrollToSelectedMandi()` with smooth behavior.
- Add `window.addEventListener('popstate')` to handle browser Back/Forward navigation without reloading.

### Phase 4: Historical Trend Chart Integration
- In the `historicalPriceTrend` Alpine component:
  - Add an event listener for `market-changed`.
  - When fired, update `this.marketId = detail.marketId` and call `this.selectRange(this.activeRange)`.
  - Update the chart's subtitle (`📍 Mandi Name — 30-day modal auctions`).

### Phase 5: Verification & Quality Assurance
- Test on desktop and mobile viewports.
- Confirm zero scroll jump to the top.
- Confirm smooth transition and shimmer loading animation.
- Confirm Grade Chips change dynamically per market.
- Confirm "Where to Sell" button updates its target link.
- Confirm browser URL updates and Back/Forward buttons navigate between selected mandis.
- Clear view and route caches.
