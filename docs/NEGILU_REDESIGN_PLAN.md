# Negilu Krishi Redesign Plan: UI/UX Overhaul, Crop Images, Navbar Decluttering, Auto-Location Picker & Web Scrapers

This plan details the full transformation of Krushi Baandhava to match the pleasant, professional, and farmer-friendly aesthetic of [Negilu Krishi](https://negilukrishi.in/), add real crop images, streamline the navbar, fix the auto-location picker with a dedicated modal, and build direct website scrapers for the Coffee Board of India and Coconut Development Board.

## Proposed Changes

### 1. Aesthetic & Color Scheme Overhaul (Matching Negilu Krishi)
- File: `resources/views/layouts/farmer.blade.php`
- Replace harsh dark themes with Negilu Krishi's palette:
  - Header: Rich forest green `#1C5A2C` with gold badge `#F0C24A` and crisp typography (Manrope & Noto Sans Kannada).
  - Background: Soft, airy, warm off-white `#F6F8F6` with subtle ambient glow.
  - Card style: Clean white `#FFFFFF` cards, soft rounded corners (`rounded-2xl` / 18px), subtle green-tinted borders (`border border-emerald-900/10`), and delicate hover elevation.
- Clean up top navigation:
  - Reduce navbar from 10+ items to **ONLY 4 clean links**:
    1. **ದರಗಳು (Prices)** -> `/`
    2. **ಎಲ್ಲಿ ಮಾರಾಟ? (Where to Sell)** -> `/where-to-sell`
    3. **ಹವಾಮಾನ (Weather)** -> `/weather`
    4. **ಯೋಜನೆಗಳು & ಮಾಹಿತಿ (Schemes & News)** -> dropdown/link to `/schemes`
  - Right tools:
    - 📍 **Location Pill**: e.g., `📍 ಶಿವಮೊಗ್ಗ (Shivamogga)` — clicking it triggers the interactive Location Modal.
    - 🌐 **Language Toggle**: Clean `EN / ಕ`.
    - 📱 **App Button**: `📲 ಆ್ಯಪ್` quick install trigger.
- Clean up mobile bottom bar to 4 items: Prices, Where to Sell, Weather, Schemes.

### 2. High-Quality Crop Photos & Visual Crop Cards
- File: `app/Models/Crop.php`
- Add `photo_url` accessor mapping every commodity to its real photo in `public/images/crops/` (`arecanut.jpg`, `coffee.jpg`, `copra.jpg`, `tender_coconut.jpg`, `black_pepper.jpg`, `banana.jpg`, `ginger.jpg`, `tomato.jpg`, `onion.jpg`, etc.).
- File: `resources/views/farmer/home.blade.php`
- Redesign the crop price card to match Negilu Krishi's card architecture:
  - Top photo container (height ~120px) with object-cover image and bottom gradient fade.
  - Kannada name overlay + price trend arrow (`↑` green, `↓` red, `→` neutral).
  - Prominent modal rate: large bold numbers (e.g. `₹45,999` with smaller `/ Quintal`).
  - APMC Mandi & District pin: `📍 ಸಾಗರ · Shivamogga`.
  - Reliability badge: `ವಿಶ್ವಸನೀಯ (Reliable)`.
  - Action link: `ಯಾವಾಗ ಮಾರಾಟ ಮಾಡಬೇಕು ›` (links to Where to Sell / Crop details).
  - Green WhatsApp share button with icon.

### 3. Interactive Auto-Location Picker Modal
- File: `resources/views/components/location-modal.blade.php`
- Embed interactive Alpine.js modal matching Negilu Krishi's location UX:
  - Header: *"ನೀವು ಯಾವ ಪ್ರದೇಶದಲ್ಲಿ ಕೃಷಿ ಮಾಡುತ್ತೀರಿ? (Where do you farm?)"*
  - Subtitle: *"ನಿಮ್ಮ ಪ್ರದೇಶದ ದರ, ಮಾರುಕಟ್ಟೆ ಮತ್ತು ಹವಾಮಾನ ತೋರಿಸುತ್ತೇವೆ."*
  - **Action 1 (GPS)**: 🛰️ *"ನನ್ನ ಸ್ಥಳ ಬಳಸಿ (Use my location - GPS)"*
    - Uses `navigator.geolocation.getCurrentPosition()`.
    - Automatically resolves coordinates to the closest Karnataka district via Haversine calculation, updates active district in session, and refreshes view.
    - Handles permission denied / non-HTTPS gracefully with a friendly message and automatic fallback to manual list.
  - **Action 2 (Manual District)**: 📝 *"ನನ್ನ ಜಿಲ್ಲೆ ಆಯ್ಕೆ ಮಾಡಿ (Choose district from list)"*
    - Instant dropdown containing all Karnataka districts (Shivamogga, Chikkamagaluru, Hassan, Kolar, Mandya, Davanagere, etc.).
    - 1-click selection immediately sets district and reloads.
- File: `app/Http/Controllers/Farmer/HomeController.php`
- Store selected district in cookie/session so it persists across visits.

### 4. Direct Website Scrapers for Coffee Board & Coconut Board
- File: `app/Services/DataSources/CoffeeBoard/CoffeeBoardDataProvider.php`
- Implement direct HTML table scraper for Coffee Board of India (`https://www.indiacoffee.org/` / `https://coffeeboard.gov.in/`):
  - Fetches the daily market rates HTML page using cURL with realistic browser user-agent.
  - Parses table rows via `DOMDocument` / `DOMXPath` extracting Arabica Parchment, Arabica Cherry, Robusta Parchment, and Robusta Cherry prices.
  - Normalizes 50 kg bag rates into standard Quintal benchmarks.
- File: `app/Services/DataSources/CoconutBoard/CoconutBoardDataProvider.php`
- Implement direct HTML table scraper for Coconut Development Board (`https://www.coconutboard.gov.in/`):
  - Fetches daily market price bulletin HTML page.
  - Parses copra (Milling, Ball) and tender coconut market prices across Arsikere, Tiptur, Mangalore, and Bangalore.
- Files: `resources/views/admin/datasources/index.blade.php` & `show.blade.php`
- Label both sources clearly as **"Direct Website Web Scraper (HTML Parser)"** with "Scrape Live Website" trigger button and health check diagnostics.

---

## Verification Plan

### Automated Tests
- Test Crop image accessors: `tests/Unit/CropPhotoTest.php`
- Test Location Modal and persistent district switcher: `tests/Feature/FarmerPriceDiscoveryTest.php`
- Test Scraper Data Providers: `tests/Unit/ScraperDataProviderTest.php`
- Run full test suite: `& "C:\Program Files\php-8.2.30\php.exe" artisan test`

### Manual Verification in Browser (XAMPP)
- Open `http://localhost/Krushi-Baandhava-application/`
- Verify the clean, airy, pleasant Negilu-style header and color scheme.
- Verify crop cards show real images (Arecanut, Coconut, Coffee, Pepper, Tomato, Onion, etc.).
- Click the location pill in the navbar; test both GPS auto-detection and district selection.
- Check Admin Panel -> Data Sources to see Coffee Board & Coconut Board web scrapers.
