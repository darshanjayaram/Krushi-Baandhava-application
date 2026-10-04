# Implementation Plan: Market Switch Grade Selection Synchronization Fix

## Overview
When a farmer selects a specific crop variety/grade in one APMC market (e.g., **Api · Average** in **Thirthahalli APMC**) and subsequently switches to another market that trades a disjoint set of varieties (e.g., **Honnali APMC**, which only trades **EDI** and **Sippegotu**), no grade pill in the new market receives the active green selection highlight. Both grade pills remain unselected with default gray/white borders.

This plan details the root cause, target architectural changes, file modifications, and verification steps to guarantee that switching markets always cleanly activates and highlights the new market's default/active grade.

---

## Root Cause Analysis
1. **Client-Side State Persistence:**
   - In [`resources/views/farmer/crops/show.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/farmer/crops/show.blade.php), selecting a grade via `switchGradeAsync()` updates:
     ```javascript
     this.selectedGradeVarietyId = Number(gradeObj.variety_id); // e.g. 5 (Api)
     this.selectedGradeName = gradeObj.grade || null;           // e.g. "Average"
     ```
2. **Missing State Synchronization in `switchMarketAsync()` & `resetMarketAsync()`:**
   - When the farmer clicks a new market pill (e.g. Honnali), `switchMarketAsync()` executes an asynchronous fetch and receives `data.grades` (containing Honnali's varieties: EDI [314] and Sippegotu [313], with EDI marked `is_selected: true`).
   - However, `switchMarketAsync()` never updates or resets `this.selectedGradeVarietyId` or `this.selectedGradeName`.
3. **Pessimistic Evaluation in `isGradeSelected(g)`:**
   - `isGradeSelected(g)` checks:
     ```javascript
     if (this.selectedGradeVarietyId !== null && this.selectedGradeVarietyId !== undefined) {
         const sameVar = Number(this.selectedGradeVarietyId) === Number(g.variety_id);
         const sameGrade = (this.selectedGradeName || '') === (g.grade || '');
         return sameVar && sameGrade;
     }
     return !!g.is_selected;
     ```
   - Because `this.selectedGradeVarietyId` is still `5` (from Thirthahalli):
     - `Number(5) === Number(314)` is `false` (EDI).
     - `Number(5) === Number(313)` is `false` (Sippegotu).
   - Because `this.selectedGradeVarietyId !== null` is true, the function never falls through to `return !!g.is_selected`.
   - Result: Every grade in the new market evaluates to `false`, leaving all grade pills unselected.

---

## User Review Required
> [!NOTE]
> The fix consists of two complementary safety layers:
> 1. **Active State Synchronization:** When new grades arrive via AJAX during market switch or reset, automatically update `selectedGradeVarietyId` and `selectedGradeName` to the server's selected variety.
> 2. **Self-Healing Fallback:** In `isGradeSelected(g)`, if `selectedGradeVarietyId` does not match *any* grade in the currently displayed `gradesList`, automatically fall back to `!!g.is_selected`.

---

## Proposed Changes

### 1. Client-Side Alpine Component
#### File: [`resources/views/farmer/crops/show.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/farmer/crops/show.blade.php)

- **Update `isGradeSelected(g)`:**
  Verify if `selectedGradeVarietyId` actually exists in `this.gradesList`. If it does not exist in the current market, fall back to `!!g.is_selected`:
  ```javascript
  isGradeSelected(g) {
      if (!g) return false;
      if (this.selectedGradeVarietyId !== null && this.selectedGradeVarietyId !== undefined) {
          const existsInCurrentMarket = Array.isArray(this.gradesList) && this.gradesList.some(item => Number(item.variety_id) === Number(this.selectedGradeVarietyId));
          if (existsInCurrentMarket) {
              const sameVar = Number(this.selectedGradeVarietyId) === Number(g.variety_id);
              const sameGrade = (this.selectedGradeName || '') === (g.grade || '');
              return sameVar && sameGrade;
          }
      }
      return !!g.is_selected;
  }
  ```

- **Update `switchMarketAsync()`:**
  Synchronize `selectedGradeVarietyId` and `selectedGradeName` when `data.grades` arrives:
  ```javascript
  if (data.grades && Array.isArray(data.grades)) {
      this.gradesList = data.grades;
      const activeGrade = data.grades.find(item => item.is_selected) || data.grades[0];
      if (activeGrade) {
          this.selectedGradeVarietyId = Number(activeGrade.variety_id);
          this.selectedGradeName = activeGrade.grade || null;
      } else {
          this.selectedGradeVarietyId = null;
          this.selectedGradeName = null;
      }
  }
  ```

- **Update `resetMarketAsync()`:**
  Apply identical synchronization when resetting to state average or nearest market.

- **Update `popstate` Event Listener:**
  Ensure browser history navigation (back/forward) synchronizes variety and grade if present in `e.state`.

---

### 2. Backend Controller JSON Response
#### File: [`app/Http/Controllers/Farmer/CropController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Farmer/CropController.php)

- Include explicit `active_variety_id` and `active_grade` in the JSON response of `show()`:
  ```php
  'active_variety_id' => $activeVarietyId,
  'active_grade' => $activePriceItem?->grade,
  ```

---

## Verification Plan

### Automated Tests
1. **PHPUnit Feature Test:**
   - Run `php artisan test --filter=FarmerPriceDiscoveryTest` to ensure existing price discovery and mandi switching features pass without regressions.
2. **Dedicated Test Case:**
   - Create or extend a test in `tests/Feature/FarmerPriceDiscoveryTest.php` sending AJAX `X-Market-Switch: 1` requests to verify:
     - Selecting Thirthahalli with `variety=5` (Api) returns HTTP 200 with `Api` marked `is_selected: true`.
     - Subsequently querying Honnali (`market=Honnali`) returns HTTP 200 with Honnali's default variety (`EDI`) marked `is_selected: true` in `grades`.

### Manual Browser Verification
1. Open `http://localhost/Krushi-Baandhava-application/public/crop/1?market=Thirthahalli`.
2. Click on **Api · Average** in the "PICK YOUR GRADE" section. Confirm green active styling on `Api · Average`.
3. Under "VIEW DIFFERENT MARKET", click on **HONNALI**.
4. Confirm:
   - "PICK YOUR GRADE" renders **EDI** and **Sippegotu · Medium**.
   - **EDI** is prominently highlighted with green background (`bg-[#1C5A2C] text-white border-[#1C5A2C]`).
   - The price displays **₹25,000** for Honnali.
5. Click on **Sippegotu · Medium**. Confirm it switches price to **₹10,500** and moves the green highlight to Sippegotu.
