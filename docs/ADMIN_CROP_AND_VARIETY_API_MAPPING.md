# Admin Crop & Variety API Mapping Architecture

This document describes how Krushi Baandhava maps incoming agricultural crop commodities and their granular varieties/grades from multiple external API data feeds into canonical local crops and varieties.

---

## 1. Problem Overview & Architecture

### The Feed Variety Inconsistency Problem
External API data feeds (such as `data.gov.in` Mandi API, Agmarknet, Coffee Board of India, and Coconut Development Board) use inconsistent or generic naming conventions for varieties:
* **Paddy / Rice**: APIs often publish under varieties like `"Common"`, `"Grade A"`, `"Fine"`, `"FAQ"`, or `"Dhan (Basmati)"` instead of `"Jyothi"`, `"Sona Masuri"`, or `"IR 64"`.
* **Arecanut (Supari)**: APIs publish raw strings like `"Rashi (Average)"`, `"Bette (Grade 1)"`, `"Chali (White)"`, `"Supari Chali"`.
* **Coconut**: APIs publish `"Tender Coconut (Elaneer)"`, `"Dry Coconut (Kopra)"`, `"Raw Coconut"`.
* **Coffee**: Board feeds publish grades like `"Plantation A"`, `"Robusta Cherry AB"`, `"Parchment"`.

### The Fallback Risk
Previously, if an exact string match was not found in `crop_varieties.name`, the ingestion service fell back to `CropVariety::where('crop_id', $cropId)->first()`. This caused all distinct grades (e.g. `Common` and `Grade A`) to collapse under the first variety in the database (e.g., `Jyothi`), leading to inaccurate grade comparisons for farmers.

### The Solution: Two-Tier Feed Mapping Engine
Krushi Baandhava employs a two-tier mapping architecture using `crop_source_mappings`:

```
                       [ Incoming External API Feed ]
                       e.g. data.gov.in / Coffee Board
                                     │
                                     ▼
                   Tier 1: Commodity / Crop Level Mapping
             Does crop_source_mappings have source_crop_name?
                     (e.g., "Paddy(Dhan)(Common)" -> Crop: Paddy)
                                     │
                                     ▼
                    Tier 2: Variety / Grade Level Mapping
          Does crop_source_mappings have source_crop_variety_name?
             (e.g., "Grade A" -> CropVariety: Jyothi, id=1)
             (e.g., "Fine"    -> CropVariety: Sona Masuri, id=2)
                                     │
                     ┌───────────────┴───────────────┐
                     ▼                               ▼
            [ Mapped to Explicit ]          [ Exact Match or Default ]
            Variety & Clean Price           Fallback if unmapped
```

---

## 2. Database Schema

The table `crop_source_mappings` handles both commodity-level and variety-level mappings:

| Column | Type | Description |
|---|---|---|
| `id` | `BIGINT UNSIGNED` | Primary Key |
| `data_source_id` | `BIGINT UNSIGNED` | Foreign key referencing `data_sources.id` (e.g., 1 for `data_gov_mandi`) |
| `crop_id` | `BIGINT UNSIGNED` | Canonical crop (`crops.id`) |
| `crop_variety_id` | `BIGINT UNSIGNED NULL` | **Canonical variety (`crop_varieties.id`)**. If `NULL`, applies as commodity-level alias. |
| `source_crop_name` | `VARCHAR(100)` | Raw commodity string from external API |
| `source_crop_variety_name` | `VARCHAR(100) NULL` | **Raw variety/grade string from external API** |
| `is_active` | `TINYINT(1)` | Whether active (default `1`) |
| `created_at` / `updated_at` | `TIMESTAMP` | Timestamps |

---

## 3. Ingestion Resolution Logic (`MarketPriceIngestionService`)

When ingesting incoming mandi records, `MarketPriceIngestionService::resolveVariety()` follows a strict 4-step hierarchy:

```php
public function resolveVariety(int $cropId, ?string $varietyName, ?int $dataSourceId = null): ?CropVariety
{
    // Step 1: Check crop_source_mappings for explicit API variety string mapping
    if ($dataSourceId && $clean !== '') {
        $mappedVariety = CropVariety::where('crop_id', $cropId)
            ->whereHas('sourceMappings', function ($q) use ($dataSourceId, $clean) {
                $q->where('data_source_id', $dataSourceId)
                  ->where(function ($sub) use ($clean) {
                      $sub->whereRaw('LOWER(TRIM(source_crop_variety_name)) = ?', [$clean])
                          ->orWhereRaw('LOWER(TRIM(source_crop_name)) = ?', [$clean]);
                  })
                  ->where('is_active', true);
            })->first();

        if ($mappedVariety) return $mappedVariety;
    }

    // Step 2: Global mapping across all active data sources
    if ($clean !== '') {
        $mappedVariety = CropVariety::where('crop_id', $cropId)
            ->whereHas('sourceMappings', function ($q) use ($clean) {
                $q->where(function ($sub) use ($clean) {
                    $sub->whereRaw('LOWER(TRIM(source_crop_variety_name)) = ?', [$clean])
                        ->orWhereRaw('LOWER(TRIM(source_crop_name)) = ?', [$clean]);
                })->where('is_active', true);
            })->first();

        if ($mappedVariety) return $mappedVariety;
    }

    // Step 3: Exact or Case-Insensitive Name Match in crop_varieties table
    $variety = CropVariety::where('crop_id', $cropId)
        ->whereRaw('LOWER(name) = ?', [$clean])
        ->first();
    if ($variety) return $variety;

    // Step 4: Fallback to default or first variety for the crop
    return CropVariety::where('crop_id', $cropId)->where('is_default', true)->first()
        ?? CropVariety::where('crop_id', $cropId)->first();
}
```

---

## 4. Admin Panel Features

### 1. Crop Catalog Status (`/admin/crops`)
The master Crop list (`resources/views/admin/master/crops/index.blade.php`) displays:
* Active price counts for Karnataka mandis.
* Mapped data source badges (`🟢 data.gov.in / APMC`, `☕ Coffee Board`, `🥥 Coconut Dev Board`, `agmarknet`, etc.).
* Variety alias count badge (`x variety aliases mapped`).
* Direct warning if any crop has 0 varieties or 0 data source mappings.

### 2. Variety Mapping Management (`/admin/crops/{crop}/edit`)
When editing any crop, administrators can:
* **Inspect Mapped API Strings**: Every variety card shows badge tags of external API strings that resolve to it.
* **Map New API Variety / Grade Strings**: Form with:
  * Variety selector (e.g. `Jyothi`, `Sona Masuri`, `Rashi`, `Bette`).
  * Data Source dropdown (e.g. `data.gov.in / APMC Mandi Feed`).
  * Raw API Variety / Grade string (e.g. `Grade A`, `FAQ`, `Common`).
* **Auto-Reprocessing**: Adding a variety alias immediately reprocesses any raw market price records matching that raw variety, instantly updating active farmer price displays.
* **One-Click Removal**: Deleting an alias frees the string without breaking core data.

---

## 5. Seeded Standard Karnataka Variety Mappings

| Crop | Canonical Variety | Raw API Strings Mapped | Data Source |
|---|---|---|---|
| **Paddy (Dhan)** | `Jyothi` | `Common`, `Grade A`, `Jyothi (Coarse)`, `Paddy Common` | `data_gov_mandi` |
| **Paddy (Dhan)** | `Sona Masuri` | `Fine`, `Sona Masoori`, `Super Fine`, `BPT` | `data_gov_mandi` |
| **Arecanut** | `Rashi` | `Rashi (Average)`, `Rashi Best`, `Average Rashi`, `Rashi Gota` | `data_gov_mandi` |
| **Arecanut** | `Bette` | `Bette (Grade 1)`, `Bette Best`, `Bette Medium` | `data_gov_mandi` |
| **Arecanut** | `Chali` | `Chali (White)`, `Supari Chali`, `Chali Grade A` | `data_gov_mandi` |
| **Coconut** | `Tender Coconut` | `Tender Coconut (Elaneer)`, `Elaneer`, `Green Tender` | `coconut_board`, `data_gov_mandi` |
| **Coconut** | `Kopra` | `Dry Coconut (Kopra)`, `Milling Copra`, `Ball Copra` | `coconut_board`, `data_gov_mandi` |
| **Coffee** | `Arabica Parchment` | `Plantation A`, `Arabica Plantation`, `Plantation PB` | `coffee_board` |
| **Coffee** | `Robusta Cherry` | `Robusta Cherry AB`, `Robusta Cherry A`, `Robusta Parchment` | `coffee_board` |

---

## 7. Market Discovery & Distance Configuration (VIEW DIFFERENT MARKET)

Each crop in Krushi Baandhava can be individually tuned from the Admin Panel to control how its surrounding markets are displayed on the farmer-facing portal:

### Configurable Attributes on `crops` Table:
1. **`market_radius_km` (`unsigned integer`)**:
   - Interactive slider from 25 km to 500 km (or 0 / >=500 for *All Karnataka / Unlimited*).
   - Defaults tailored by crop perishability and trading style (e.g., Arecanut = 350 km, Coffee = 250 km, Coconut = 200 km, Pepper = 300 km, Tomato = 100 km).
2. **`default_market_sort` (`string`)**:
   - `nearest_first`: Orders surrounding mandis by closest driving proximity from user's location.
   - `highest_price_first`: Orders mandis starting from the highest modal price today.
3. **`allow_user_sort_toggle` (`boolean`)**:
   - When enabled, renders the interactive **"Nearest First"** vs **"Highest Price First"** switch buttons for farmers on the website.
4. **`enable_smart_badges` (`boolean`)**:
   - When enabled, automatically tags the closest mandi with `📍 Nearest` and the highest-paying mandi with `🔥 Top Rate`.

---

## 8. Verification & Automated Testing

The complete test suite verifies:
1. `AdminCropVarietyMappingTest` — Variety alias mapping and feed resolution.
2. `AdminCropMarketDiscoverySettingsTest` — Admin crop form slider, sort selection, toggle switches, and persistence.
3. All **195 feature & unit tests** pass with **3,854 assertions**.

