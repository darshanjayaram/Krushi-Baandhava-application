# Commercial Crop Variety Async Management - Implementation Plan
**Feature:** Asynchronous Editing & Management of Commercial Crop Varieties  
**Module:** Admin Panel Master Data (`Admin -> Crops -> Commercial Varieties`)  
**Status:** Completed & Verified  
**Date:** October 2026  

---

## 1. Executive Summary & Problem Context
Crops traded in Karnataka APMC mandis are sold by specific commercial trade varieties and grades (e.g. *Rashi*, *Bile Kalu*, *Neelam*, *Bangalore Blue*, *Tender Coconut*). 

Previously:
- The Admin Crop editor (`admin/master/crops/{crop}/edit`) allowed adding a variety grade and deleting a grade, but had **no option to edit** an existing variety's English name, Kannada name, slug, or active status.
- Admin users wondered whether renaming a variety would require renaming historical database rows in `market_prices`.
- **Relational Integrity:** Because `market_prices` links to varieties via Foreign Key `variety_id` (not hardcoded text strings), updating a variety's name in `crop_varieties` instantly updates all historical and live price displays without requiring any database data migration.
- **Async Requirement:** The user explicitly requested that editing and saving variety changes must happen **asynchronously (via AJAX / JSON fetch)** without disturbing the user's scroll position or reloading the page.

---

## 2. Technical Architecture & Data Flow

```
+-----------------------------------------------------------------------------------+
|                        ADMIN CROP VARIETIES TAB (AlpineJS)                        |
|                                                                                   |
|  [ Variety Card ]                                                                 |
|  - English: "Rashi"                                                               |
|  - Kannada: "ರಾಶಿ"                                                                |
|  - Action Buttons: [ ✏️ Edit ] [ 🗑️ Delete ]                                       |
|             |                                                                     |
|             v (Click ✏️ Edit)                                                      |
|  Switches Card to Inline Alpine.js Editor (or Modal):                              |
|  - Input: name ("Rashi Gold")                                                     |
|  - Input: name_kn ("ರಾಶಿ ಗೋಲ್ಡ್")                                                 |
|  - Input: slug ("rashi-gold")                                                     |
|  - Toggle: is_active (true/false)                                                 |
|  - Buttons: [ Save Changes ] [ Cancel ]                                           |
|             |                                                                     |
|             v (Click [ Save Changes ])                                            |
|  Async PUT /admin/varieties/{id} (headers: Accept: application/json)               |
+-------------|---------------------------------------------------------------------+
              |
              v (JSON Request)
+-----------------------------------------------------------------------------------+
|                           LARAVEL BACKEND CONTROLLER                              |
|                   App\Http\Controllers\Admin\CropVarietyController                |
|                                                                                   |
|  1. Validate input via CropVarietyRequest:                                         |
|     - 'name' => required, string                                                  |
|     - 'name_kn' => nullable, string                                               |
|     - 'slug' => unique per crop, ignore current variety ID                        |
|  2. Update crop_varieties table                                                   |
|  3. Log AuditLog ('crop_variety.update')                                          |
|  4. If $request->wantsJson() || $request->ajax():                                 |
|     Return JSON response:                                                         |
|     {                                                                             |
|       "success": true,                                                            |
|       "message": "Variety 'Rashi Gold' updated successfully.",                    |
|       "variety": { "id": 42, "name": "...", "name_kn": "...", "slug": "..." }     |
|     }                                                                             |
+-------------|---------------------------------------------------------------------+
              |
              v (JSON 200 OK)
+-----------------------------------------------------------------------------------+
|                           CLIENT UI STATE UPDATE                                  |
|                                                                                   |
|  1. Update Alpine.js reactive card state (name, name_kn, slug)                    |
|  2. Show green "✓ Saved!" notification indicator                                  |
|  3. Return card to clean display view                                             |
|  4. Zero page reload, zero scroll jump!                                           |
+-----------------------------------------------------------------------------------+
```

---

## 3. Implementation Steps

### Phase 1: Controller Enhancement for Async JSON Support
**File:** `app/Http/Controllers/Admin/CropVarietyController.php`
- In `update(CropVarietyRequest $request, CropVariety $variety)`:
  - Detect `$request->wantsJson()` or `$request->ajax()`.
  - Return JSON with updated attributes on success.
  - Maintain traditional redirect fallback for backward compatibility.
- Ensure validation errors return JSON 422 with clear validation messages.

### Phase 2: Frontend Interactive UI with Alpine.js
**File:** `resources/views/admin/master/crops/form.blade.php`
- Wrap each variety card in an Alpine.js component `x-data="varietyCard({ id, name, name_kn, slug, is_active, updateUrl })"`:
  - **Read-only View:**
    - Displays English Name, Kannada Name, Slug, Mapped API Aliases.
    - Action toolbar with **✏️ Edit** button and **🗑️ Delete** button.
  - **Inline Edit View:**
    - Inputs for `name`, `name_kn`, and `slug`.
    - Checkbox/switch for `is_active`.
    - Buttons: **"Save Changes"** (with async loading spinner `isSaving`) and **"Cancel"**.
    - Inline error container if validation fails.
  - **Async Save Handler (`saveVariety()`):**
    - Performs `fetch(updateUrl, { method: 'PUT', headers: { ... }, body: JSON.stringify(...) })`.
    - Dispatches a toast or card-level success badge upon completion.

### Phase 3: Verification & Integrity Testing
1. Test editing a variety name and verify response is 200 OK without page reload.
2. Verify that historical market price rows (`market_prices`) with matching `variety_id` display the updated name seamlessly.
3. Test validation error handling (e.g. blank name or duplicate slug).

---

## 4. Database Integrity Guarantee
- `market_prices` table links to varieties via **`variety_id` (Foreign Key)**.
- Historical price rows do NOT store variety text strings.
- Updating a variety in `crop_varieties` updates the name globally across all queries, charts, and mandi prices without touching existing price rows.

---

## 5. Grade vs. Variety Clarification & Mapping Architecture

### Why was "Grade" mentioned in the Admin Panel?
In Karnataka APMC mandis and local trading terminology, farmers and traders use the words **"Grade"** (ಗ್ರೇಡ್) and **"Variety"** (ತಳಿ) interchangeably:
- E.g., for Arecanut: *Rashi grade*, *Bette grade*, *Gorabal grade*, *Chali grade*.
- E.g., for Coconut: *Tender Coconut grade*, *Copra grade*.
- E.g., for Paddy / Wheat / Cotton: *FAQ grade*, *Grade A*, *Common*, *Fine*.

Because of this colloquial habit, the previous UI form labeled buttons as `+ Add Grade` and `Registered Grades`.

### How Mapping Actually Works in the System:
1. **Canonical Master Entity (`crop_varieties` table):**
   - Represents the canonical commercial trade variety/grade (e.g., *Rashi*, *Bette*, *Basmati*, *Grade A*).
   - Adding a variety in the admin panel stores it here.
2. **Raw External Feeds (Agmarknet / KSAMB):**
   - Government data feeds send inconsistent or abbreviated strings:
     - e.g. `RASHI`, `RASHI BETTE`, `BETTE NEW`, `FAQ`, `GRADE-A`, `COMMERCIAL`.
3. **The Mapping Table (`crop_source_mappings`):**
   - Links the raw incoming API string (`source_variety_name`) to the canonical record (`crop_variety_id`).
   - When an admin selects a canonical variety in the dropdown and clicks **`+ Map`**, it creates an entry:
     `source_variety_name: "RASHI BETTE" --> crop_variety_id: 1 (Rashi)`.
   - When daily ingestion runs, the ingestion service maps `RASHI BETTE` to variety `Rashi` automatically.
4. **Quality Grade Attribute (`market_prices.grade` column):**
   - In government feeds, there is also a generic quality rating like `FAQ` (Fair Average Quality) or `Average`. This is stored directly in `market_prices.grade` as an informational metadata string, not as a master table entity.

### UI Harmonization:
All UI labels across the admin panel tab have been harmonized to **"Commercial Variety"** and **"Variety"** to eliminate confusion between quality grades and commercial cultivars.
