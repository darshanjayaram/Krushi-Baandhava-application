# Key Market Rates Freshness & Provider Downtime Resilience Plan

## Overview
This document specifies the resilience architecture for **"Today's Key Market Rates" (ಇಂದಿನ ಪ್ರಮುಖ ದರಗಳು - Top 4 Spotlight Cards)** on the Krushi Baandhava homepage (`/`).

When an external provider (such as KRAMA or Agmarknet) experiences downtime, Sunday closures, or partial sync failure, the platform must seamlessly display the most recent verified auction rates for major commodities within their configured **Price Freshness Rules** (7–30 days) instead of collapsing into only displaying the isolated commodities synced that day (e.g. Coffee Board).

---

## 1. Problem Statement
* Previously, `HomeController.php` queried spotlight crops strictly using:
  ```php
  $topMovers = MarketPrice::karnataka()
      ->where('price_date', $latestPriceDate)
      ...
  ```
* If on a given date only 1 provider succeeded (e.g. Coffee Board on 2026-10-03) while KRAMA was down, `$latestPriceDate` became `2026-10-03`.
* This strictly eliminated all other fresh APMC commodities (Arecanut, Pepper, Copra, Coconut, Tomato, Paddy) whose verified auctions occurred 1 or 2 days earlier.
* **Farmer UX Result:** Only 1 card was shown, leaving 3 blank or broken-looking gaps.

---

## 2. Architectural Solution

### Rule 1: Freshness-Window Spotlight Resolution
* The top spotlight items evaluate major crops (`Crop::where('is_major', true)`).
* For each major crop, resolve its latest valid price within its dynamic category cutoff date (`$crop->getFreshnessCutoffDate($latestPriceDate)`).
* Priority ordering for location relevance:
  1. Active District local market if available.
  2. State-level benchmark market.
* Take the top 4 distinct commodities ranked by market prominence & modal value.

### Rule 2: Day-over-Day Trend Calculation Against Immediate Preceding Session
* The price change trend (`daily_price_change`, `daily_trend` = rise/drop/stable) compares against the immediate preceding trading session for that specific crop & market (`price_date < $item->price_date`).
* This ensures mathematical accuracy of trends even when different commodities trade on different frequency schedules (e.g., daily APMC vegetables vs. bi-weekly spice auctions vs. Coffee Board).

### Rule 3: Transparent Farmer UI & Date Freshness Badges
On each spotlight card:
* If the auction date matches today/latest calendar sync: show status as live (`🟢 ಇಂದು / Live`).
* If the auction date is from a previous trading session within the freshness window: show a clear, transparent date pill (e.g., `📅 01 Oct` or `ಇತ್ತೀಚಿನ ದರ · 01 Oct`).
* Farmers get immediate confidence in the date of the auction while enjoying a full, informative dashboard.

---

## 3. Implementation Steps
1. **Controller Query Update (`app/Http/Controllers/Farmer/HomeController.php`):**
   - Refactor `$topMovers` query to resolve the latest auction within the freshness window per major commodity.
   - Update trend calculation to compare each item against its own prior trading session.
2. **Blade Template Enhancement (`resources/views/farmer/home.blade.php`):**
   - Add date freshness badges to the spotlight cards (displaying auction date when not current day).
3. **Automated Feature Verification (`tests/Feature/FarmerHomeSpotlightTest.php`):**
   - Assert that when partial sync occurs, all 4 spotlight cards remain filled with fresh commodities.
   - Assert that trends and date indicators display correctly.

---

## Status: Completed & Verified (2026-10-04)
* **Controller Refactoring**: `app/Http/Controllers/Farmer/HomeController.php` resolves each major commodity within its dynamic freshness cutoff window (->getFreshnessCutoffDate()).
* **Independent Preceding Session Trends**: Calculates day-over-day price trend against each crop & market's immediate prior trading session.
* **Auction Date Badging**: `resources/views/farmer/home.blade.php` displays a transparent date badge (`📅 01 Oct`) whenever an item's auction date is from a previous trading session within the freshness window.
* **Automated Feature Verification**:
  - `FarmerHomeSpotlightTest.php`: Passed (1 test, 7 assertions).
  - `FarmerPriceDiscoveryTest.php`: Passed (15 tests, 330 assertions).
