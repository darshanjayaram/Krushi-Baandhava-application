# Implementation Plan - Farmer-Centric Terminology: Removing "APMC" in Favor of "Mandi" & "Market"

Following the convention of top farmer-centric apps like **Negilu**, this plan replaces bureaucratic government acronyms ("APMC" / "ಎಪಿಎಂಸಿ") across user-facing screens, PWA metadata, headers, cards, and navigation in favor of natural farmer terms like **"Mandi" (ಮಂಡಿ)**, **"Market" (ಮಾರುಕಟ್ಟೆ)**, or clean town/market names.

---

## 1. Scope & Strategy

1. **User/Farmer-Facing Layer (Complete Clean-Up)**:
   - **PWA Manifest & Install Prompt**: App Title, Description, Shortcuts, and Screenshot labels.
   - **Header & Drawer Navigation**: Subtitles, badges, links, and search hints.
   - **Homepage (Live Ticker & Commodity Cards)**: `LIVE APMC` badge $\rightarrow$ `LIVE MANDI`, ticker copy, town suffix clean-up.
   - **Market & Crop Detail Pages**: Stop appending redundant `APMC` to market names (e.g. `Shivamogga APMC` $\rightarrow$ `Shivamogga` or `Shivamogga Mandi`). Clean up WhatsApp share text and source attributions.
   - **Footer CMS**: Telemetry badge (`160+ APMCs` $\rightarrow$ `160+ Mandis`), links (`APMC Directory` $\rightarrow$ `Mandi Directory`), and footer mission statement.

2. **Internal/Backend Layer (Preserved for Stability)**:
   - Database schema columns (`market_type`, `apmc_cess`), third-party scrapers/sync drivers (`AgmarknetOfficialFeedService`, `KramaKarnatakaFeedService`), and master data codes (`KA_APMC_*`) remain untouched internally to ensure data ingestion pipelines and calculations function without regression.

---

## 2. File-by-File Changes

| Component / File | Current Bureaucratic Text | New Farmer-First Text |
| :--- | :--- | :--- |
| **`app/Services/Pwa/PwaManifestService.php`** | `pwa_description`: *...Nearby APMC Mandis...*<br>Shortcuts: *Today's APMC mandi market rates...*<br>Screenshots: *...Karnataka APMC Market Rates* | *...Nearby Mandis...*<br>*Today's mandi market rates across Karnataka*<br>*...Karnataka Mandi Market Rates* |
| **Database `system_settings`** | `pwa_name`: `Krushi Baandhava - Farmer APMC Market Intelligence`<br>`pwa_description`: `Real-time Karnataka APMC mandi prices...`<br>`hero_subtitle_en`: `...from all Karnataka APMC mandis.`<br>`footer_description_en`: `...real-time APMC trading prices...`<br>`footer_telemetry_badge`: `31 Districts • 160+ APMCs`<br>`footer_col2_links`: `APMC Directory` / `ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ವಿವರ`<br>`navbar_drawer_links`: `Karnataka APMC live prices` / `ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ದರಗಳು` | `Krushi Baandhava - Farmer Market Intelligence`<br>`Real-time Karnataka mandi prices, live arrival rates, price projections, weather advisories, and nearest market discovery.`<br>`Live prices and future trends from Karnataka mandis and agricultural markets.`<br>`Karnataka agricultural intelligence network — real-time mandi trading prices, modal rates, and predictive crop guidance.`<br>`31 Districts • 160+ Mandis`<br>`Mandi Directory` / `ಮಂಡಿ ವಿವರ`<br>`Karnataka mandi live prices` / `ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಮಾರುಕಟ್ಟೆ ದರಗಳು` |
| **`resources/views/layouts/farmer.blade.php`** | Fallback subtitle: `Direct APMC Market Rates & Forecast`<br>Drawer subtitle: `APMC Mandi & Farmer Hub`<br>Drawer link item: `Karnataka APMC live prices` | `Direct Mandi Rates & Farmer Forecast`<br>`Farmer Market Hub` / `ಕರ್ನಾಟಕ ರೈತ ಮಾರುಕಟ್ಟೆ`<br>`Karnataka mandi live prices` |
| **`app/Http/Controllers/Admin/NavbarController.php`** | Fallback defaults using `Direct APMC Market Rates & Forecast` and `Karnataka APMC live prices` | `Direct Mandi Rates & Farmer Forecast` and `Karnataka mandi live prices` |
| **`app/Http/Controllers/Admin/FooterController.php`** | Default link `APMC Directory` / `ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ವಿವರ`<br>Telemetry: `31 Districts • 160+ APMCs`<br>Description: `...real-time APMC trading prices...` | `Mandi Directory` / `ಮಂಡಿ ವಿವರ`<br>`31 Districts • 160+ Mandis`<br>`...real-time mandi trading prices...` |
| **`resources/views/farmer/home.blade.php`** | Header ticker: `Live APMC Market Rates` / `ದೈನಂದಿನ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ದರಗಳು`<br>Badge: `LIVE APMC`<br>Location label: `(APMC)` / `(ಎಪಿಎಂಸಿ)`<br>Search fallback: `Karnataka APMC` / `ಕರ್ನಾಟಕ ಎಪಿಎಂಸಿ` | `Live Mandi Rates` / `ದೈನಂದಿನ ಅಧಿಕೃತ ಮಂಡಿ ದರಗಳು`<br>`LIVE MANDI`<br>`(ಮಂಡಿ)` / `(Mandi)`<br>`Karnataka Mandi` / `ಕರ್ನಾಟಕ ಮಂಡಿ` |
| **`resources/views/farmer/crops/partials/mandi-card.blade.php` & `show.blade.php`** | Suffix appending: `{{ $market->name . ' APMC' }}`<br>Share text: `🏛️ ಕೃಷಿ ಬಾಂಧವ — APMC ಮಾರುಕಟ್ಟೆ ದರಗಳು`<br>Attribution: `Source: APMC / Agmarknet Karnataka` | Strip trailing "APMC": `preg_replace('/\s+APMC$/i', '', $market->name)`<br>`🏛️ ಕೃಷಿ ಬಾಂಧವ — ಮಾರುಕಟ್ಟೆ ದರಗಳು`<br>`Source: Mandi / Agmarknet Karnataka` |
| **`resources/views/farmer/markets/` (`index`, `nearby`, `show`)** | `Karnataka APMC Mandis Directory`<br>`Nearby APMC Mandis`<br>`Official APMC daily modal rates...` | `Karnataka Mandis Directory`<br>`Nearby Mandis & Markets`<br>`Official mandi daily modal rates...` |
| **`resources/views/farmer/feedback/index.blade.php` & `offline.blade.php`** | `District / APMC Mandi`<br>Placeholder: `Kolar APMC, Yeshwantpur`<br>Offline text: `Any APMC mandi prices...` | `District / Mandi`<br>`Kolar, Shivamogga, Yeshwantpur`<br>`Any mandi prices...` |

---

## 3. Execution Steps

1. **Step 1: PWA Manifest & System Settings Update**:
   - Update `app/Services/Pwa/PwaManifestService.php`.
   - Update database `system_settings` records via Tinker / Eloquent.
   - Run disk synchronization to write clean JSON into `public/manifest.json`.

2. **Step 2: Navbar, Header, and Drawer Clean-Up**:
   - Update `app/Http/Controllers/Admin/NavbarController.php`.
   - Update `resources/views/layouts/farmer.blade.php`.

3. **Step 3: Footer CMS Clean-Up**:
   - Update `app/Http/Controllers/Admin/FooterController.php`.

4. **Step 4: Homepage & Market Views Clean-Up**:
   - Update `resources/views/farmer/home.blade.php`.
   - Update `resources/views/farmer/crops/partials/mandi-card.blade.php` and `resources/views/farmer/crops/show.blade.php`.
   - Update `resources/views/farmer/markets/index.blade.php`, `nearby.blade.php`, `show.blade.php`.
   - Update `resources/views/farmer/feedback/index.blade.php` and `offline.blade.php`.

5. **Step 5: Testing & Verification**:
   - Re-run test suite (`PwaManifestTest`, `NavbarCmsTest`, etc.).
   - Verify `public/manifest.json` via curl.
   - Compile frontend assets with `npm run build`.
