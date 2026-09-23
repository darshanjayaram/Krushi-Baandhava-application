# Phase 6 Implementation Plan: Geolocation & Nearby Mandi Discovery

## Goal Description
Implement **Phase 6: Geolocation & Nearby Mandi Discovery** for Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ). This empowers farmers to discover their nearest Karnataka APMC mandis within a 25km, 50km, or 100km radius using browser GPS (HTML5 Geolocation) or manual district/taluk selection, with cached OpenStreetMap Nominatim reverse geocoding, Haversine spherical distance calculations, commodity availability filtering, Google Maps navigation links, and 1-tap WhatsApp sharing.

---

## User Review Required

> [!IMPORTANT]
> **Strict Karnataka Scoping**: In line with the user's explicit directive, all nearby market calculations and discovery queries strictly filter to Karnataka APMC mandis (`Market::karnataka()`), guaranteeing zero out-of-state mandis are returned even if a farmer is near state borders.

> [!NOTE]
> **Nominatim Fair-Use Compliance**: Reverse geocoding results are cached in Redis/database for 30 days (`Cache::remember`), preventing repeated third-party queries. Nominatim requests will include the official application `User-Agent: KrushiBaandhava/1.0 (contact@krushibaandhava.org)` as mandated by OSM operations policy.

---

## Proposed Changes

### 1. Location Services Layer

#### [NEW] `app/Services/Location/Contracts/GeocoderInterface.php`
- Interface defining `reverseGeocode(float $latitude, float $longitude): ?array`.

#### [NEW] `app/Services/Location/NominatimGeocoder.php`
- Implements `GeocoderInterface` using Laravel `Http` client.
- 30-day cache on rounded coordinates (`round($lat, 3)`, `round($lon, 3)`).
- Custom `User-Agent` header for OSM Nominatim policy.
- Normalized address output: `district`, `taluk`, `locality`, `state`, `display_name`, `postcode`.
- Graceful error handling (timeout, network down fallback).

#### [NEW] `app/Services/Location/NearbyMarketService.php`
- Methods:
  - `findNearby(float $latitude, float $longitude, float $radiusKm = 50, ?string $cropSlug = null, int $limit = 20): Collection`
  - Calculates precise spherical Haversine distance in kilometers and compass bearing/direction (North, Northeast, etc.).
  - Enforces `Market::karnataka()`.
  - Optionally filters by crops traded today on latest canonical date.
  - Enriches results with today's traded crop count, top commodity prices, and Google Maps direction link.

---

### 2. Controllers & Routing

#### [NEW] `app/Http/Controllers/Api/V1/NearbyMarketApiController.php`
- Route: `GET /api/v1/markets/nearby`
- Query parameters: `latitude`, `longitude`, `radius` (default: 50km), `crop` (optional).
- Structured JSON response with user location metadata, distance in km, bearings, and nearby market items.

#### [NEW] `app/Http/Controllers/Farmer/NearbyMarketController.php`
- Route: `GET /nearby-markets` (named `farmer.markets.nearby`)
- Accepts `lat`, `lon`, `radius`, `crop`, and manual `district_id` fallback.
- Injects `NearbyMarketService` and `GeocoderInterface`.

#### [MODIFY] `routes/web.php`
- Register `Route::get('/nearby-markets', [NearbyMarketController::class, 'index'])->name('farmer.markets.nearby');`

#### [MODIFY] `routes/api.php`
- Register `Route::get('/markets/nearby', [NearbyMarketApiController::class, 'index'])->name('api.v1.markets.nearby');`

#### [MODIFY] `app/Http/Controllers/Api/V1/MasterDataApiController.php`
- Enforce `Market::karnataka()` in existing `markets()` query.

---

### 3. Farmer UI & Navigation

#### [NEW] `resources/views/farmer/markets/nearby.blade.php`
- Mobile-first, responsive Tailwind CSS interface (clean, fast, independent design):
  - **GPS Locate Me Button**: One-tap browser `navigator.geolocation.getCurrentPosition` with loading spinner and error fallback.
  - **Manual Fallback Selector**: If GPS is denied or unavailable, farmer can pick any Karnataka district/taluk.
  - **Radius Filter Chips**: 25 km, 50 km, 100 km, 150 km.
  - **Commodity Filter**: Filter nearby mandis specifically trading crops of interest (e.g. Tomato, Arecanut, Onion, Maize).
  - **Nearby Mandi Cards**:
    - Mandi name (EN + KN) & APMC badge
    - Distance pill (e.g. `📍 14.8 ಕಿ.ಮೀ (14.8 km)`)
    - Direction compass indicator (e.g., `ಈಶಾನ್ಯ (North-East)`)
    - Today's traded commodities count & price highlights
    - **1-Tap Direct Navigation**: "Google Maps ನಲ್ಲಿ ದಾರಿ ನೋಡಿ (Navigate via Google Maps)" button (`https://www.google.com/maps/dir/?api=1&destination={lat},{lon}`)
    - **1-Tap WhatsApp Share**: Share list of nearby mandis with rates.

#### [MODIFY] `resources/views/layouts/farmer.blade.php`
- Add "ಹತ್ತಿರದ ಮಂಡಿಗಳು (Nearby Mandis)" link in:
  - Top header navigation bar
  - Mobile bottom thumb-zone navigation bar (quick tap)

#### [MODIFY] `resources/views/farmer/home.blade.php`
- Add a prominent quick action pill or button in the hero district banner:
  - "📍 ಹತ್ತಿರದ ಮಂಡಿಗಳನ್ನು ಹುಡುಕಿ (Find Nearest Mandis)" linking to `/nearby-markets`.

---

### 4. Automated Testing & Verification

#### [NEW] `tests/Unit/NominatimGeocoderTest.php`
- Tests Nominatim reverse geocoding with `Http::fake()`.
- Verifies caching behavior and custom `User-Agent`.
- Verifies graceful fallback on HTTP error.

#### [NEW] `tests/Unit/NearbyMarketServiceTest.php`
- Verifies Haversine distance calculations and ordering.
- Verifies radius constraints (25km, 50km, 100km).
- Verifies commodity filter filtering out mandis not trading that crop today.

#### [NEW] `tests/Feature/NearbyMarketTest.php`
- Verifies `GET /nearby-markets` renders successfully.
- Verifies `GET /nearby-markets?lat=13.9299&lon=75.5681` resolves Shivamogga and nearby APMC mandis.
- Verifies `GET /api/v1/markets/nearby` returns structured JSON.
- Verifies strict Karnataka isolation (out-of-state mandis like Pune/Goa are never returned).

---

## Verification Plan

### Automated Tests
- Run `& "C:\Program Files\php-8.2.30\php.exe" artisan test` (must pass 100% of tests, including new unit and feature tests).
- Run `npm run build` to verify frontend asset bundle.

### Manual Verification
- Test GPS geolocation flow in browser / emulator.
- Test radius toggle (25km, 50km, 100km).
- Test manual district selector fallback.
- Verify Google Maps navigation link opens proper coordinates.
