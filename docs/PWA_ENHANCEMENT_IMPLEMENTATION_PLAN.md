# PWA Enhancement & Modernization Implementation Plan

## 1. Executive Summary
This document outlines the end-to-end upgrade of the Progressive Web App (PWA) layer in the **Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ)** application. The upgrade ensures compliance with Google Chromium Rich Install UI standards, iOS Safari web app requirements, dynamic branding synchronization from Admin Settings, offline resiliency across custom subpaths (XAMPP/cPanel), and standalone-mode UX refinement.

---

## 2. Identified Gaps & Target Solutions

| # | Component | Current Gap | Target Solution |
|---|---|---|---|
| 1 | **Manifest Synchronization** | `public/manifest.json` is static; web server serves disk file, bypassing dynamic DB settings | Wire `farmer.blade.php` to dynamic route `route('pwa.manifest')` and sync `public/manifest.json` on disk whenever Admin settings are updated |
| 2 | **Theme Color Alignment** | HTML header uses `#F5EFE6` (parchment) while manifest had `#1C5A2C` (forest green) | Harmonize both to dynamic `SystemSetting::get('pwa_theme_color', '#F5EFE6')` |
| 3 | **Standalone App Awareness** | Desktop header `📲 App` pill and drawer button remain visible and prompt install even inside installed PWA | Use `display-mode: standalone` check to hide header pill and show "App Installed ✓" badge in drawer |
| 4 | **Offline Subpath Cache Match** | `sw.js` uses `caches.match('/offline')` which breaks on subfolder installs (e.g. `/Krushi-Baandhava-application/public/`) | Resolve offline URL relative to `self.registration.scope` |
| 5 | **Rich Install UI** | Manifest lacks `screenshots` property required by Chrome/Edge for modern rich install dialog | Add wide and narrow screenshot declarations with localized descriptive labels |
| 6 | **iOS Safari Meta** | Missing `apple-mobile-web-app-title` and `application-name` | Add both meta tags to `<head>` in `farmer.blade.php` |
| 7 | **Service Worker Lifecycle** | No notification to reload when new SW version activates | Add lightweight toast alerting user of available updates with instant refresh |

---

## 3. Implementation Steps

### Step 1: Dynamic Manifest & Disk Sync
1. Refactor `Route::get('/manifest.json')` in `routes/web.php` to include:
   - Dynamic branding name, short name, and description.
   - Dynamic `theme_color` and `background_color`.
   - Dynamic icons from `pwa_icon` or `app_logo` with maskable & any purposes.
   - `screenshots` array for Chromium Rich Install UI.
   - Standard shortcuts (Rates, Where to Sell, Weather, Schemes).
2. Update `SystemSettingController.php`:
   - Add helper `syncManifestJson()` to rewrite `public/manifest.json` on disk whenever branding or PWA settings are saved.
3. Update `resources/views/layouts/farmer.blade.php`:
   - Change manifest link to `{{ route('pwa.manifest') }}` with version timestamp query for cache-busting.

### Step 2: Standalone Mode Reactive Experience
1. In `resources/views/layouts/farmer.blade.php`:
   - Add global Alpine check `isStandalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true`.
   - Wrap Desktop Header `📲 App` button with `x-show="!isStandalone"`.
   - In Mobile Hamburger Drawer, replace the install button with an installed status pill when `isStandalone` is true.

### Step 3: Service Worker Resilience & Update Toast
1. In `public/sw.js`:
   - Ensure cache matching uses relative URL resolution `new URL('./offline', self.registration.scope).href`.
   - Support `SKIP_WAITING` message for seamless reload.
2. In `farmer.blade.php`:
   - Add SW update listener to prompt user when a new cache version is ready.

### Step 4: iOS Safari Optimization
1. In `farmer.blade.php`:
   - Add `<meta name="apple-mobile-web-app-title" content="{{ $appName }}">`.
   - Add `<meta name="application-name" content="{{ $appName }}">`.

### Step 5: Verification & Testing
1. Create `tests/Feature/PwaManifestTest.php` to assert:
   - `/manifest.json` returns 200 with `application/manifest+json`.
   - Contains proper IDs, scopes, theme colors, shortcuts, and screenshots.
   - Offline route `/offline` returns 200.
2. Run `npm run build` and PHPUnit test suite.
