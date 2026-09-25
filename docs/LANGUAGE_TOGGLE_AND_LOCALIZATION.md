# Language Toggle & Bilingual Localization Architecture (Kannada <-> English)

## 1. Problem Statement & Root Cause
Prior to this fix, the language switch indicator in the header layout (`resources/views/layouts/farmer.blade.php`) was a static decorative HTML element:
```html
<div class="flex items-center gap-1 bg-white rounded-lg px-2 py-1 text-xs font-bold text-stone-700 border border-stone-200/80 shadow-2xs">
    <span>EN</span>
    <span class="text-stone-300">/</span>
    <span class="font-kannada">ಕ</span>
</div>
```
- Clicking it had no effect because it lacked interactive anchor links, route bindings, and backend session or cookie handlers.
- When farmers wished to view rates or menus in English or Kannada, there was no mechanism to switch application locale and persist the choice across page reloads or PWA sessions.

---

## 2. Technical Architecture & Implementation

### A. Dedicated Locale Switch Routes (`routes/web.php` & `routes/api.php`)
1. **Web Route**: `GET /locale/{lang}`
   - Validates `$lang` against supported locales `['kn', 'en']` (safely defaults to `'kn'`).
   - Updates `session(['locale' => $lang])`.
   - Attaches a persistent cookie `locale` (valid for 1 year, SameSite Lax).
   - Redirects back to the user's referer page (preserving current crop, filter, or market context) or `/`.
2. **API Route**: `POST /api/v1/set-locale`
   - Accepts `{ "locale": "en" }` or `{ "locale": "kn" }`.
   - Safely updates session if available, sets response cookie, and returns structured JSON `{ "success": true, "status": "success", "locale": $locale }`.

### B. `SetLocaleMiddleware` (`app/Http/Middleware/SetLocaleMiddleware.php`)
- **Execution Order**: Registered in Laravel 11's `web` middleware pipeline via `bootstrap/app.php` (`$middleware->web(append: [SetLocaleMiddleware::class])`).
- **Hierarchy of Resolution**:
  1. URL query parameter: `?lang=kn` or `?lang=en`
  2. Route parameter: `/locale/{lang}`
  3. Session value: `$request->session()->get('locale')`
  4. Persistent Cookie: `$request->cookie('locale')`
  5. Default: `'kn'` (Kannada is the primary native farmer language for Karnataka).
- **Post-Request Cookie Syncing**: Inspects `$finalLocale` after route handler execution to ensure persistent cookies are automatically updated if the user switched language mid-request.

### C. Interactive Header UI (`resources/views/layouts/farmer.blade.php`)
Replaced the static element with a segmented pill toggle:
```blade
<div class="flex items-center bg-white rounded-xl p-1 text-xs font-bold border border-stone-200/90 shadow-2xs">
    <a href="{{ route('locale.switch', 'en') }}" 
       class="px-2 py-1 rounded-lg transition {{ $activeLocale === 'en' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-100' }}"
       title="Switch to English">
        EN
    </a>
    <span class="text-stone-300 mx-0.5">/</span>
    <a href="{{ route('locale.switch', 'kn') }}" 
       class="px-2 py-1 rounded-lg transition font-kannada {{ $activeLocale === 'kn' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-100' }}"
       title="ಕನ್ನಡಕ್ಕೆ ಬದಲಾಯಿಸಿ">
        ಕನ್ನಡ
    </a>
</div>
```
- Visible on both desktop and mobile header navigation bars.
- Clear visual active state highlighting: deep emerald green `#1C5A2C` with white bold text.

### D. Comprehensive In-Page Localization
1. **Desktop Navigation**:
   - `Rates` / `ದರಗಳು`
   - `Schemes` / `ಯೋಜನೆಗಳು`
   - `Videos` / `ವಿಡಿಯೋಗಳು`
   - `News` / `ಸುದ್ದಿಗಳು`
2. **Mobile Bottom 4-Tab Navigation**:
   - `Home` / `ಮುಖಪುಟ`
   - `Rates` / `ದರಗಳು`
   - `Schemes` / `ಯೋಜನೆಗಳು`
   - `Weather` / `ಹವಾಮಾನ`
3. **Hero Banner & Action Cards (`home.blade.php`)**:
   - "Live Market Data" / "ನೇರ ಮಾರುಕಟ್ಟೆ ದತ್ತಾಂಶ"
   - Headline: "Growing crops is not enough, selling at the right time matters" / "ಬೆಳೆ ಬೆಳೆದಷ್ಟೇ ಸಾಕಾಗದು, ಸರಿಯಾದ ಸಮಯಕ್ಕೆ ಮಾರಾಟವೂ ಮುಖ್ಯ"
   - Action Card 1: "Check nearby mandi rates" / "ಹತ್ತಿರದ ಮಂಡಿ ದರಗಳನ್ನು ನೋಡಿ"
   - Action Card 2: "Join on WhatsApp" / "ವಾಟ್ಸ್‌ಆ್ಯಪ್‌ನಲ್ಲಿ ಸೇರಿ"
   - Action Card 3: "How to Use" / "ಹೇಗೆ ಬಳಸುವುದು"
4. **Spotlight & Section Headings**:
   - "Today's Top Rates" / "ಇಂದಿನ ಪ್ರಮುಖ ದರಗಳು"
   - "Today's Market Rates" / "ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು"
   - "View All ›" / "ಎಲ್ಲಾ ನೋಡಿ ›"
   - Category filter chip "All" / "ಎಲ್ಲಾ"
   - Search placeholder: "Search crops..." / "ಬೆಳೆ ಹುಡುಕಿ..."
5. **Crop Show View (`show.blade.php`)**:
   - "CURRENT PRICE" / "ಇಂದಿನ ದರ"
   - "Updated:" / "ನವೀಕರಿಸಲಾಗಿದೆ:"
   - "nearest market" / "ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆ"
   - "PICK YOUR GRADE" / "ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ"
   - "All Grades (FAQ)" / "ಎಲ್ಲಾ ತಳಿ / FAQ"
   - "VIEW DIFFERENT MARKET" / "VIEW DIFFERENT MARKET • ಮಾರುಕಟ್ಟೆ ಬದಲಿಸಿ (ಕರ್ನಾಟಕ ಮಂಡಿ ಆಯ್ಕೆ)"
   - Market Advisory Headlines and Sentiment Explanations adapting to `$activeLocale`.
   - "Share Price" / "ದರ ಶೇರ್ ಮಾಡಿ"
   - "Where to Sell? (ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ)"

---

## 3. Verification & Automated Test Suite

A dedicated feature test suite `tests/Feature/LanguageToggleTest.php` was added:
1. `test_default_locale_is_kannada`: Verifies Kannada (`kn`) is the initial default without cookies or session.
2. `test_switching_to_english_via_route`: Verifies `/locale/en` sets session `locale=en` and cookie `locale=en`.
3. `test_english_locale_renders_english_ui_elements`: Confirms Home page displays English headers and navigation.
4. `test_switching_back_to_kannada`: Verifies `/locale/kn` restores Kannada session and cookie.
5. `test_invalid_locale_defaults_to_kannada`: Confirms unsupported locales fallback securely to `'kn'`.
6. `test_locale_switch_preserves_referer_url`: Verifies the user is returned to the exact page they were browsing.
7. `test_api_set_locale_endpoint`: Confirms API endpoint sets locale and responds with JSON.

### Full Test Suite Results:
- **Total Tests**: 182 passed
- **Total Assertions**: 3,397 passed
- **Regressions**: 0

---

## 4. Browser Translation Suppression & Single-Script Purity

### A. Suppressing Native Browser "Translate this page" Popup
Like Negilu Krushi (`negilukrishi.in`), Krushi Baandhava provides its own native high-fidelity bilingual translation. When Google Chrome automatically detects Kannada text, it attempts to trigger its machine translation pop-up (`Translate this page? [Kannada] [English]`).

To prevent this conflicting browser behavior:
1. Added `<meta name="google" content="notranslate">` in `<head>` (`resources/views/layouts/farmer.blade.php`).
2. Added `class="notranslate" translate="no"` to `<html>` and `<body>` tags.
3. Chrome now honors the native in-app language switch without intrusive popup prompts.

### B. Eliminating Mixed Kannada/English Strings in English Mode
As observed in user screenshots, certain cards were rendering mixed text when English translation was selected (e.g., `Garbled (ಗಾರ್ಬಲ್ಡ್)`, `Rashi (ರಾಶಿ)`, `ರಾಗಿ • Indaf (ಇಂಡಾಫ್)`, `ಈರುಳ್ಳಿ • Medium (ಮಧ್ಯಮ)`).

**Fixes Applied:**
1. **Model Helpers on `CropVariety` (`app/Models/CropVariety.php`)**:
   - `getCleanNameAttribute()`: Strips out any Kannada characters in parentheses (e.g. `"Garbled (ಗಾರ್ಬಲ್ಡ್)"` -> `"Garbled"`, `"Medium (ಮಧ್ಯಮ)"` -> `"Medium"`).
   - `getCleanNameKnAttribute()`: Extracts clean Kannada without English text (e.g. `"Medium (ಮಧ್ಯಮ)"` -> `"ಮಧ್ಯಮ"`).
   - `displayName(?string $locale)`: Returns the appropriate clean single-language string based on the active locale.
2. **Crop Name & Variety Overlay (`resources/views/farmer/home.blade.php`)**:
   - Replaced redundant `$price->crop->name_kn` prefix on the card overlay with `$price->variety->displayName($activeLocale)`.
   - In English mode: displays `Ragi (Finger Millet)` with subtitle `Indaf` (or `Onion` with subtitle `Medium`).
   - In Kannada mode: displays `ರಾಗಿ` with subtitle `ಇಂಡಾಫ್` (or `ಈರುಳ್ಳಿ` with subtitle `ಮಧ್ಯಮ`).
3. **District Action Card 1**:
   - Removed secondary Kannada parentheses `({{ $activeDistrict->name_kn }})` in English mode, displaying pure `Bengaluru Urban`.
4. **Database & Seeder Sanitization**:
   - Updated existing database rows in `crop_varieties` to have separated, clean `name` (English) and `name_kn` (Kannada) values.
   - Updated `CropMasterSeeder.php` and `ComprehensiveKarnatakaMarketPricesSeeder.php` to seed pure English `name` and pure Kannada `name_kn`.

