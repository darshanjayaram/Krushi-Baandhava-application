# Admin APMC Mandi Management, Code Auto-Generation & Feed Alias Mapping

## 1. Overview & Architecture

In agricultural price discovery systems like Krushi Baandhava and Negilu Krushi, canonical markets (APMC Mandis, Sub-market yards, and Commodity Board offices) form the master data backbone.

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│ DAILY ARRIVAL PRICE FEEDS                                                               │
│ (data.gov.in, Agmarknet, Coffee Board, Coconut Development Board, KRAMA)                │
│ Raw payload: { "market": "Shimoga", "district": "Shimoga", "modal_price": 52400, ... }  │
│ ⚠️ Note: APIs DO NOT provide GPS coordinates or official codes!                         │
└────────────────────────────────────────┬────────────────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│ KRUSHI BAANDHAVA RESOLUTION ENGINE                                                      │
│ 1. Matches via `market_source_mappings` (e.g., "Shimoga" -> Shivamogga APMC)           │
│ 2. Falls back to cross-source verified aliases & Karnataka synonym search               │
└────────────────────────────────────────┬────────────────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│ CANONICAL MARKET (MASTER DATA in `markets` table)                                       │
│ • Name: Shivamogga APMC (ಶಿವಮೊಗ್ಗ ಎಪಿಎಂಸಿ)                                              │
│ • District: Shivamogga                                                                  │
│ • Code: KA_APMC_SHI (Auto-generated or official)                                        │
│ • GPS Coordinates: 13.9310, 75.5700 (Stored in Master DB)                               │
└────────────────────────────────────────┬────────────────────────────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│ FARMER FRONTEND & DISTANCE CALCULATION                                                  │
│ Farmer GPS / Selected District <──────── Haversine Distance ────────> Market GPS        │
│ Displays: "📍 nearest market • 78 km" / Surrounding Markets Ranked by Proximity         │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Why Government APIs Don't Provide GPS Coordinates

Government APIs (**data.gov.in**, **Agmarknet**, **Coffee Board**, **Coconut Development Board**) only transmit commercial pricing data:
- `State`, `District`, `Market` (text strings)
- `Commodity`, `Variety` (text strings)
- `Arrival_Date`, `Min_Price`, `Max_Price`, `Modal_Price`, `Arrival_Quantity`

**They do not have latitude or longitude coordinates.**

### How Distance Calculations Work:
1. **Master DB Coordinates**: Krushi Baandhava stores the latitude and longitude inside the `markets` table.
2. **Real-time Proximity**: When a farmer turns on GPS or selects their district, the system runs the mathematical **Haversine formula** to measure distances from the farmer's location to every active market.
3. **Surrounding Market Badging**: Nearby markets are sorted by distance with badges like `📍 nearest market • 18 km`, `34 km`, etc.

---

## 3. Official APMC Code: Optional & Auto-Generated

### The Problem
Previously, the Mandi registration form had a required text input labeled **"OFFICIAL APMC CODE"**. Administrators and data operators rarely know technical codes (like `CDB_ASK` or `KA_APMC_SHI`), which caused friction and forced users to guess.

### The Solution
1. **Label Updated**: Marked as **`Official APMC Code (Optional - Auto-generated if left blank)`**.
2. **⚡ Auto-Generate Button**: Clicking the button in the UI generates a clean, standardized code from the English name (e.g., `Shivamogga APMC` $\rightarrow$ `KA_APMC_SHIVAMOGGA`).
3. **Backend Fallback**: In `MarketController::store()` and `MarketController::update()`, if the code field is left blank, the server automatically assigns a unique code (`KA_APMC_{SLUG}`):

```php
protected function generateMarketCode(string $name, ?int $ignoreId = null): string
{
    $clean = preg_replace('/\s*\(.*?\)/', '', $name);
    $clean = preg_replace('/\s+APMC/i', '', $clean);
    $base = 'KA_APMC_' . Str::upper(Str::slug(trim($clean), '_'));
    if (strlen($base) > 35) {
        $base = substr($base, 0, 35);
    }
    $code = $base;
    $counter = 1;
    while (Market::where('code', $code)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
        $code = "{$base}_{$counter}";
        $counter++;
    }
    return $code;
}
```

---

## 4. Quick-Fill from Standard Karnataka Mandi Directory (165+ Presets)

A comprehensive directory of **165+ Karnataka APMC main yards, sub-markets, and Commodity Board purchase centres** is embedded directly in the form header, powered by `App\Support\KarnatakaMandiDirectory`.

### Key Capabilities:
- **All 31 Karnataka Districts Covered**: Grouped by district using `<optgroup>` tags (e.g. `📍 Shivamogga (7)`, `📍 Belagavi (11)`, `📍 Tumakuru (10)`, `📍 Mandya (7)`, etc.) with live district counts.
- **Bi-Lingual Names**: Displays English and official Kannada names (e.g. `Shivamogga APMC — ಶಿವಮೊಗ್ಗ ಎಪಿಎಂಸಿ`, `Thirthahalli APMC — ತೀರ್ಥಹಳ್ಳಿ ಎಪಿಎಂಸಿ`).
- **Comprehensive KRAMA Alignment**: Fully aligned with KRAMA (Karnataka State Agricultural Marketing Board) market definitions and official APMC jurisdiction.
- **Pre-Configured Raw Aliases**: Each directory entry includes historical spelling aliases from KRAMA and Agmarknet feeds (e.g. `TIRTHAHALLI`, `SHIKARIPUR`, `CHANNAGIRI`).
- **Commodity Boards Included**: Specialized Coffee Board auction centres (Madikeri, Sakleshpur, Chikkamagaluru, Balehonnur) and Coconut Development Board (CDB) market offices (Arsikere, Tiptur, Udupi, Mandya).

### Behavior:
Selecting any preset instantly auto-populates:
- **English Name** & **Kannada Name**
- **District** (matches and selects the district dropdown automatically)
- **Official Code** & **Market Type**
- **GPS Latitude & Longitude**
- **Yard Address**

---

## 5. Direct Feed Alias Management on Edit Mandi

In addition to the global **Data Quality $\rightarrow$ Unresolved Mappings** dashboard, administrators can now view and manage feed aliases **directly inside the Edit Mandi screen**.

### Features:
1. **Active Mapped Aliases Pills**:
   - Shows all raw names mapped to this market (e.g. `Shimoga`, `Shimoga APMC`, `Arasikere`).
   - Displays the provider source tag (`data.gov.in`, `Agmarknet`).
   - Includes a one-click **&times;** button to unmap an alias.
2. **Map New Feed Alias**:
   - Input: Raw feed name (e.g. `Arasikere`).
   - Provider: Choose which data feed uses this alias (or default to data.gov.in).
   - Button: `+ Map Alias`.
   - **Instant Auto-Reprocess**: Upon adding an alias, the system automatically triggers `MarketPriceIngestionService::reprocessBatch()`, converting any rejected or pending arrival records into live market prices!

---

## 6. Endpoints & Routes Reference

| Route Name | Method | Path | Controller Action | Description |
| :--- | :--- | :--- | :--- | :--- |
| `admin.markets.index` | `GET` | `/admin/markets` | `MarketController@index` | List all APMC mandis |
| `admin.markets.create` | `GET` | `/admin/markets/create` | `MarketController@create` | New mandi registration form with presets |
| `admin.markets.store` | `POST` | `/admin/markets` | `MarketController@store` | Store mandi with code auto-generation |
| `admin.markets.edit` | `GET` | `/admin/markets/{market}/edit` | `MarketController@edit` | Edit mandi with aliases card |
| `admin.markets.update` | `PUT` | `/admin/markets/{market}` | `MarketController@update` | Update mandi details |
| `admin.markets.aliases.add` | `POST` | `/admin/markets/{market}/aliases` | `MarketController@addAlias` | Directly add raw feed alias & auto-reprocess |
| `admin.markets.aliases.remove` | `DELETE` | `/admin/markets/{market}/aliases/{mapping}` | `MarketController@removeAlias` | Remove raw feed alias |
