# Implementation Plan: Phase 13 — PWA Offline Shell, Manifest, Service Worker & SEO Optimization

Phase 13 elevates the farmer experience into a resilient, installable Progressive Web Application (PWA) with complete offline fallback capabilities and search engine discoverability. Even when connectivity drops in rural Karnataka fields, farmers will have access to cached prices and a friendly offline guidance screen.

## User Review Required

> [!IMPORTANT]
> - The Service Worker will automatically cache the dedicated `/offline` page during the installation phase. When a farmer navigates without an internet connection and the requested page is not in cache, the Service Worker safely renders the `/offline` view.
> - The XML Sitemap (`/sitemap.xml`) is dynamically generated from real database entities (all active crops, Karnataka mandis, welfare schemes, news articles, and guides) with dynamic `<lastmod>` timestamps and `<priority>` weighting.
> - An in-app "Install App / ಮುಖಪುಟಕ್ಕೆ ಸೇರಿಸಿ" banner will appear non-intrusively on mobile browsers when the PWA install criteria are met, allowing 1-tap installation to the home screen.

---

## Proposed Changes

### 1. Web App Manifest Enhancement

#### [MODIFY] [`public/manifest.json`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/public/manifest.json)
- Add quick action shortcuts for home screen long-press:
  - **Today's Market Prices** (`/`)
  - **Where to Sell** (`/where-to-sell`)
  - **Weather Advisory** (`/weather`)
  - **Government Schemes** (`/schemes`)
- Add `categories`, `id`, `dir`, and `prefer_related_applications: false`.

---

### 2. Dedicated Offline Fallback & Controller

#### [NEW] [`resources/views/farmer/offline.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/farmer/offline.blade.php)
- Clean, bilingual offline screen:
  - Visual signal: *"You are currently offline / ನೀವು ಪ್ರಸ್ತುತ ಆಫ್‌ಲೈನ್‌ನಲ್ಲಿದ್ದೀರಿ"*.
  - Guidance explaining that cached prices are still viewable and live connectivity will auto-resume.
  - Quick action buttons: "Try Reconnecting", "Browse Home", "View Crops Catalog".

#### [MODIFY] [`app/Http/Controllers/Farmer/HomeController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Farmer/HomeController.php)
- Add `offline()` action returning `view('farmer.offline')`.

#### [MODIFY] [`routes/web.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/routes/web.php)
- Register `Route::get('/offline', [HomeController::class, 'offline'])->name('offline');`.

---

### 3. Service Worker Resilience & Web Push Hooks

#### [MODIFY] [`public/sw.js`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/public/sw.js)
- Upgrade cache version to `krushi-baandhava-v2`.
- Pre-cache core shell: `/`, `/offline`, `/manifest.json`, `/icons/icon-192.svg`, `/icons/icon-512.svg`.
- Implement navigation fallback: if network request fails and resource is not in cache, fallback to `/offline`.
- Add push notification event listener stubs (`self.addEventListener('push')` and `self.addEventListener('notificationclick')`) ready for push alerts.

---

### 4. Dynamic XML Sitemap & robots.txt

#### [NEW] [`app/Http/Controllers/Farmer/SitemapController.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/app/Http/Controllers/Farmer/SitemapController.php)
- Generate a dynamic, valid XML sitemap (`/sitemap.xml`) including:
  - Core platform URLs (`/`, `/crops`, `/markets`, `/nearby-markets`, `/where-to-sell`, `/weather`, `/schemes`, `/news`, `/videos`, `/articles`).
  - All active Crops (`/crops/{slug}`).
  - All active APMC Mandis (`/markets/{code}`).
  - All active Government Schemes (`/schemes/{slug}`).
  - All active News Articles (`/news/{slug}`).
  - All published Farming Guides (`/articles/{slug}`).
- Sets `Content-Type: application/xml` and caches for 1 hour.

#### [MODIFY] [`routes/web.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/routes/web.php)
- Register `Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');`.

#### [MODIFY] [`public/robots.txt`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/public/robots.txt)
- Disallow `/admin/` and `/api/`, allow all public routes, and declare the sitemap URL.

---

### 5. SEO, Social Graph Tags & PWA Install UI

#### [MODIFY] [`resources/views/layouts/farmer.blade.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/resources/views/layouts/farmer.blade.php)
- Insert canonical link: `<link rel="canonical" href="{{ url()->current() }}">`.
- Add Open Graph meta tags (`og:title`, `og:description`, `og:image`, `og:url`, `og:locale`, `og:site_name`).
- Add Twitter Card meta tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`).
- Add non-intrusive Alpine.js PWA install banner listening to `beforeinstallprompt`.
- Add real-time online/offline network detector banner listening to `window.addEventListener('online')` and `window.addEventListener('offline')`.

---

### 6. Automated Testing

#### [NEW] [`tests/Feature/PwaAndSeoTest.php`](file:///d:/xampp/htdocs/Krushi-Baandhava-application/tests/Feature/PwaAndSeoTest.php)
- Verify `manifest.json` contains valid JSON, name, icons, start_url, and shortcuts.
- Verify `sw.js` is accessible and contains offline fallback logic.
- Verify `/offline` route renders correctly.
- Verify `/sitemap.xml` returns 200 with `application/xml` and includes core routes, crops, and mandis.
- Verify `robots.txt` contains sitemap directive and restricts `/admin/`.
- Verify Open Graph and canonical tags are rendered in HTML.

---

## Verification Plan

### Automated Tests
Run PHPUnit test suite:
- `& "C:\Program Files\php-8.2.30\php.exe" artisan test --filter=PwaAndSeoTest`
- Full test suite: `& "C:\Program Files\php-8.2.30\php.exe" artisan test` (must achieve >143 green tests).
- Asset build check: `npm run build`.

### Manual / Browser Verification
- Visit `/offline` in browser and verify bilingual offline messaging.
- Visit `/sitemap.xml` and verify valid XML structure with crops, mandis, and schemes.
- Check view-source on `/` to verify Open Graph tags and canonical URL.
- Inspect `manifest.json` in Chrome DevTools Application tab.
