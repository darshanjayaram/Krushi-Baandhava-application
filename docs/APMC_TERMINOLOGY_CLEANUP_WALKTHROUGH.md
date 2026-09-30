# Walkthrough - Farmer-Centric Terminology Clean-Up: Removing "APMC"

We have removed the bureaucratic government acronym **"APMC" / "ಎಪಿಎಂಸಿ"** across all farmer-facing screens, PWA metadata, headers, cards, and navigation in favor of natural, farmer-first terms like **"Mandi" (ಮಂಡಿ)**, **"Market" (ಮಾರುಕಟ್ಟೆ)**, and clean market names—matching the UX conventions of benchmark applications like **Negilu**.

---

## 1. Summary of Changes

### 1. PWA Install Prompt & Manifest (`public/manifest.json`)
- **PWA Name:** Changed from `"Krushi Baandhava - Farmer APMC Market Intelligence"` to `"Krushi Baandhava - Farmer Market Intelligence"`.
- **PWA Description:** Changed from `"Real-time Karnataka APMC mandi prices..."` to `"Real-time Karnataka mandi prices, live arrival rates, price projections, weather advisories, and nearest market discovery."`.
- **Shortcuts & Screenshots:** Changed `"Today's APMC mandi market rates..."` to `"Today's mandi market rates across Karnataka"` and screenshot labels to `"Krushi Baandhava - Karnataka Mandi Market Rates"`.
- **Disk Manifest:** Re-synchronized disk file `public/manifest.json` with zero occurrences of "APMC".

### 2. Header & Navigation Drawer
- **Header Subtitle:** Replaced `"Direct APMC Market Rates & Forecast"` with `"Direct Mandi Rates & Farmer Forecast"` / `"ನೇರ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ರೈತ ಮುನ್ಸೂಚನೆ"`.
- **Drawer Header:** Replaced `"APMC Mandi & Farmer Hub"` with `"Mandi & Farmer Hub"` / `"ಕರ್ನಾಟಕ ರೈತ ಮಾರುಕಟ್ಟೆ"`.
- **Drawer Link:** Replaced `"Karnataka APMC live prices"` / `"ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ದರಗಳು"` with `"Karnataka mandi live prices"` / `"ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಮಾರುಕಟ್ಟೆ ದರಗಳು"`.

### 3. Homepage (Live Ticker & Command Dock)
- **Top Ticker:** Replaced `"Live Market Data • Live APMC Market Rates"` with `"Live Market Data • Live Mandi Rates"`.
- **Kannada Ticker:** Replaced `"ದೈನಂದಿನ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ದರಗಳು"` with `"ದೈನಂದಿನ ಅಧಿಕೃತ ಮಂಡಿ ದರಗಳು"`.
- **Search Modal Badge:** Replaced `"LIVE APMC"` with `"LIVE MANDI"`.
- **Location Selector:** Removed bureaucratic `(APMC)` / `(ಎಪಿಎಂಸಿ)` suffix from selected district pill (`ಬೆಂಗಳೂರು ನಗರ` instead of `ಬೆಂಗಳೂರು ನಗರ (ಎಪಿಎಂಸಿ)`).
- **Search Fallback:** Replaced `"Karnataka APMC"` / `"ಕರ್ನಾಟಕ ಎಪಿಎಂಸಿ"` with `"Karnataka Mandi"` / `"ಕರ್ನಾಟಕ ಮಂಡಿ"`.
- **Commodity Section:** Replaced `"Official Karnataka APMC modal and average prices..."` with `"Official Karnataka mandi modal and average prices..."`.

### 4. Market Cards & Crop Detail Pages
- **Clean Market Names:** Stripped trailing `" APMC"` suffix (e.g. `Kolar APMC` $\rightarrow$ `Kolar`, `Shivamogga APMC` $\rightarrow$ `Shivamogga`).
- **Kannada Market Names:** Stripped trailing `" ಎಪಿಎಂಸಿ"` (e.g. `ಕೋಲಾರ ಎಪಿಎಂಸಿ` $\rightarrow$ `ಕೋಲಾರ`).
- **Attribution & Sources:** Replaced `"Source: APMC / Agmarknet Karnataka"` with `"Source: Mandi / Agmarknet Karnataka"` (`ಮೂಲ: ಮಂಡಿ / Agmarknet Karnataka`).
- **WhatsApp Share Copy:** Cleaned up share messages so farmers share `"ಮಾರುಕಟ್ಟೆ ದರಗಳು"` / `"ಮಂಡಿ ದರಗಳು"` without APMC acronyms.

### 5. Markets Directory & Nearby Mandis
- **Directory Title:** Replaced `"Karnataka APMC Mandis Directory"` with `"Karnataka Mandis Directory"` / `"ಕರ್ನಾಟಕ ಮಂಡಿ ಮಾರುಕಟ್ಟೆಗಳು"`.
- **Nearby Radar:** Replaced `"APMC Mandis Nearest to You"` with `"Mandis Nearest to You in Karnataka"` / `"ನಿಮ್ಮ ಸಮೀಪದ ಕರ್ನಾಟಕ ಮಂಡಿ ಮಾರುಕಟ್ಟೆಗಳು"`.

### 6. Footer CMS
- **Telemetry Badge:** Changed from `"31 Districts • 160+ APMCs"` to `"31 Districts • 160+ Mandis"`.
- **Footer Link:** Changed from `"APMC Directory"` (`ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ವಿವರ`) to `"Mandi Directory"` (`ಮಂಡಿ ವಿವರ`).
- **Footer Description:** Changed from `"...real-time APMC trading prices..."` to `"...real-time mandi trading prices..."`.

---

## 2. Test Verification Results

All automated test suites pass 100%:
- `FarmerPriceDiscoveryTest`: **15 passed** (1,488 assertions)
- `NearbyMarketTest`: **5 passed** (236 assertions)
- `PwaManifestTest`: **4 passed** (12 assertions)
- `NavbarCmsTest`: **3 passed** (8 assertions)
- `FarmerFeedbackAndHelpdeskTest`: **5 passed** (83 assertions)
- `npm run build`: Assets compiled cleanly in **4.16s**
- `manifest.json`: Verified via HTTP curl with 0 occurrences of "APMC"
