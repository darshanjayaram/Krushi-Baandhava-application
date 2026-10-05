<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-[#F5EFE6] antialiased notranslate" style="background-color: #F5EFE6;" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="google" content="notranslate">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'ಕೃಷಿ ಬಾಂಧವ (Krushi Baandhava)' }} — ಕರ್ನಾಟಕ ರೈತರ ಕೃಷಿ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ</title>
    <meta name="description" content="{{ $description ?? 'ಕರ್ನಾಟಕದ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ದರಗಳು, ನಿಖರ ಬೆಲೆ ಮುನ್ಸೂಚನೆ, ಹತ್ತಿರದ ಮಂಡಿಗಳು ಮತ್ತು ಹವಾಮಾನ ಮಾಹಿತಿ.' }}">
    <link rel="canonical" href="{{ url()->current() }}">

    @php
        $activeLocale = app()->getLocale();
        $appLogo = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
        $logoExt = strtolower(pathinfo($appLogo, PATHINFO_EXTENSION));
        $logoMime = match($logoExt) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/svg+xml'
        };
        $logoVersion = file_exists(public_path(ltrim($appLogo, '/'))) ? filemtime(public_path(ltrim($appLogo, '/'))) : '1';
        $appLogoUrl = asset($appLogo) . '?v=' . $logoVersion;
        $pwaIcon = \App\Models\SystemSetting::get('pwa_icon');
        if (!$pwaIcon || $pwaIcon === '/icons/icon-512.svg') {
            $pwaIcon = $appLogo;
        }
        $pwaIconUrl = asset($pwaIcon) . '?v=' . $logoVersion;
        $appName = \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava');
    @endphp

    <!-- Open Graph / WhatsApp Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }} - ಕೃಷಿ ಬಾಂಧವ">
    <meta property="og:title" content="{{ $title ?? 'ಕೃಷಿ ಬಾಂಧವ (Krushi Baandhava)' }} — ಕರ್ನಾಟಕ ರೈತರ ಕೃಷಿ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ">
    <meta property="og:description" content="{{ $description ?? 'ಕರ್ನಾಟಕದ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ದರಗಳು, ನಿಖರ ಬೆಲೆ ಮುನ್ಸೂಚನೆ, ಹತ್ತಿರದ ಮಂಡಿಗಳು ಮತ್ತು ಹವಾಮಾನ ಮಾಹಿತಿ.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $appLogoUrl }}">
    <meta property="og:locale" content="kn_IN">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? $appName }}">
    <meta name="twitter:description" content="{{ $description ?? 'ಕರ್ನಾಟಕದ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ದರಗಳು' }}">
    <meta name="twitter:image" content="{{ $appLogoUrl }}">

    <!-- PWA Settings & Dynamic Favicons -->
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <script>
        // Global PWA prompt listener (early capture before Alpine initializes)
        window.deferredPwaPrompt = null;
        window.isPwaStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.deferredPwaPrompt = e;
            window.dispatchEvent(new CustomEvent('pwa-prompt-ready'));
        });
        window.addEventListener('appinstalled', () => {
            window.deferredPwaPrompt = null;
            window.isPwaStandalone = true;
            window.dispatchEvent(new CustomEvent('pwa-installed'));
        });
    </script>
    <meta name="theme-color" content="{{ \App\Models\SystemSetting::get('pwa_theme_color', '#F5EFE6') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
    <meta name="application-name" content="{{ $appName }}">
    <link rel="apple-touch-icon" href="{{ $pwaIconUrl }}">
    <link rel="icon" type="{{ $logoMime }}" href="{{ $appLogoUrl }}">
    <link rel="shortcut icon" href="{{ $appLogoUrl }}">

    <!-- Google Fonts: Noto Sans Kannada, Manrope & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Kannada:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Anek+Kannada:wght@100..800&family=Tiro+Kannada:ital@0;1&family=Baloo+Tamma+2:wght@400..800&family=Tiro+Kannada:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
        html {
            scroll-behavior: smooth;
            background-color: #F5EFE6;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
            background-color: #F5EFE6;
            color: #1F2937;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        /* ============================================================== */
        /* KANNADA OPTICAL TYPOGRAPHY & VERTICAL BASELINE ALIGNMENT SYSTEM */
        /* ============================================================== */
        /* 0. Noto Sans Kannada Optical Metric Rebalance: overrides oversized descender to prevent text uplifting */
        @font-face {
            font-family: 'Noto Sans Kannada';
            src: local('Noto Sans Kannada'),
                 url('https://fonts.gstatic.com/s/notosanskannada/v32/8vIh7xs32H97qzQKnzfeXycxXZyUmySvZWItmf1fe6TVmgoD4F-Yo3w.woff2') format('woff2');
            unicode-range: U+0951-0952, U+0964-0965, U+0C80-0CF3, U+1CD0, U+1CD2-1CD3, U+1CDA, U+1CF2, U+1CF4, U+200C-200D, U+20B9, U+25CC, U+A830-A835;
            ascent-override: 90%;
            descent-override: 22%;
            line-gap-override: 0%;
        }

        html[lang="kn"],
        html[lang="kn"] body,
        html[lang="kn"] *,
        html[lang="kn"] input,
        html[lang="kn"] button,
        html[lang="kn"] select,
        html[lang="kn"] textarea,
        .font-kannada {
            font-family: 'Noto Sans Kannada', 'Manrope', sans-serif !important;
        }

        /* 1. Base line-height relaxation for Kannada script vowels/matras */
        html[lang="kn"] .leading-none {
            line-height: 1.25 !important;
        }
        html[lang="kn"] .leading-tight {
            line-height: 1.35 !important;
        }

        html[lang="kn"] #siteHeader nav:not(.hidden) a,
        html[lang="kn"] #siteHeader a.font-kannada,
        html[lang="kn"] .category-filter-btn,
        html[lang="kn"] .btn-mandi-change,
        html[lang="kn"] button.btn-mandi-change {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Strict Responsive Visibility Guarantee: never display hidden elements on mobile/tablet only */
        @media (max-width: 1023px) {
            html[lang="kn"] #siteHeader .hidden,
            html[lang="kn"] #siteHeader .lg\:flex,
            html[lang="kn"] #siteHeader .xl\:inline-flex {
                display: none !important;
            }
        }

        /* 3. Section Titles with Emoji or badges */
        html[lang="kn"] h1,
        html[lang="kn"] h2,
        html[lang="kn"] h3 {
            line-height: 1.35 !important;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Modern Thin Frontend Scrollbar */
        ::-webkit-scrollbar {
            width: 5px !important;
            height: 5px !important;
        }

        ::-webkit-scrollbar-track {
            background: transparent !important;
        }

        ::-webkit-scrollbar-thumb {
            background: #1c5a2c !important;
            border-radius: 9999px !important;
            transition: background-color 0.2s ease !important;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #144220 !important;
        }

        ::-webkit-scrollbar-button,
        ::-webkit-scrollbar-button:single-button,
        ::-webkit-scrollbar-button:vertical:decrement,
        ::-webkit-scrollbar-button:vertical:increment,
        ::-webkit-scrollbar-button:horizontal:decrement,
        ::-webkit-scrollbar-button:horizontal:increment {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            background: transparent !important;
        }

        ::-webkit-scrollbar-corner {
            background: transparent !important;
        }

        /* Firefox Only - Chromium uses ::-webkit-scrollbar without triggering Windows system arrow buttons */
        @supports not selector(::-webkit-scrollbar) {
            * {
                scrollbar-width: thin !important;
                scrollbar-color: #1c5a2c transparent !important;
            }
        }

        /* Explicit Viewport Pinning for Mobile Navbar & Bottom Menu Dock */
        #siteHeader {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            width: 100vw !important;
            z-index: 40 !important;
            box-sizing: border-box;
        }

        #mobileBottomNav {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            width: 100vw !important;
            max-width: 100vw !important;
            z-index: 50 !important;
            box-sizing: border-box;
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* ==================== 1. UNIVERSAL SMOOTH PAGE ENTRANCE ==================== */
        @keyframes kbPageEnter {
            0% {
                opacity: 0.2;
            }
            100% {
                opacity: 1;
            }
        }

        .page-main-container {
            animation: kbPageEnter 0.18s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        /* ==================== 3. TACTILE TOUCH CONFIRMATION MICRO-INTERACTION ==================== */
        .interactive-card,
        .crop-article,
        .crop-list-item,
        .tap-feedback {
            transition: transform 0.15s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.18s ease, border-color 0.18s ease !important;
            -webkit-touch-callout: none;
        }
        .interactive-card:active,
        .crop-article:active,
        .crop-list-item:active,
        .tap-feedback:active {
            transform: scale(0.982) !important;
        }

        /* ==================== 4. YOUTUBE / GITHUB STYLE TOP NAVIGATION PROGRESS BAR ==================== */
        #globalPageProgressBar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #1C5A2C 0%, #2D8A46 65%, #F0C24A 100%);
            box-shadow: 0 0 10px rgba(28, 90, 44, 0.7);
            opacity: 0;
            transition: width 0.35s cubic-bezier(0.1, 0.8, 0.2, 1), opacity 0.2s ease;
        }

        /* ==================== 5. NATIVE CROSS-DOCUMENT VIEW TRANSITIONS (ZERO BLANK FLASH) ==================== */
        @view-transition {
            navigation: auto;
        }
    </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body x-data="{ isMobileMenuOpen: false }" @keydown.escape.window="isMobileMenuOpen = false" class="flex flex-col min-h-screen antialiased pt-16 sm:pt-18 pb-0 md:pb-6 bg-[#F5EFE6] notranslate" style="background-color: #F5EFE6;">

    <!-- Top Navigation Progress Indicator (Silky Smooth Instant Page Feedback) -->
    <div id="globalPageProgressBar" aria-hidden="true"></div>

    <!-- Full-Screen Glassmorphic Navigation Loading Window -->
    <x-navigation-loader />

    <!-- Connectivity Status Indicator -->
    <div x-data="{
        isOnline: navigator.onLine,
        showReconnected: false,
        init() {
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.showReconnected = true;
                setTimeout(() => { this.showReconnected = false; }, 3500);
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });
        }
    }">
        <div x-show="!isOnline" style="display: none;"
             class="bg-amber-500 text-slate-950 px-4 py-1.5 text-center text-xs font-bold shadow-sm flex items-center justify-center gap-2">
            <span class="animate-pulse">⚠️</span>
            <span>{{ app()->getLocale() === 'en' ? 'Offline Mode: Showing cached rates' : 'ಆಫ್‌ಲೈನ್ ಮೋಡ್: ಉಳಿಸಲಾದ ದರಗಳನ್ನು ತೋರಿಸಲಾಗುತ್ತಿದೆ' }}</span>
        </div>
        <div x-show="showReconnected" style="display: none;"
             class="bg-[#1C5A2C] text-white px-4 py-1.5 text-center text-xs font-bold shadow-sm flex items-center justify-center gap-2">
            <span>✓</span>
            <span>{{ app()->getLocale() === 'en' ? 'Live connectivity restored!' : 'ಆನ್‌ಲೈನ್‌ಗೆ ಮರಳಿದೆ!' }}</span>
        </div>
    </div>

    @php
        // --- Navbar & Menus CMS Dynamic Configuration ---
        // 1. Desktop Navbar
        $rawDesktopNav = \App\Models\SystemSetting::get('navbar_desktop_links');
        $desktopNavLinks = is_array($rawDesktopNav) ? $rawDesktopNav : (json_decode($rawDesktopNav ?? '', true) ?: [
            ['label_en' => 'Rates', 'label_kn' => 'ದರಗಳು', 'url' => '/crops', 'route_match' => 'home,farmer.crops.*', 'badge' => '', 'badge_color' => 'emerald', 'new_tab' => false, 'is_visible' => true],
            ['label_en' => 'Schemes', 'label_kn' => 'ಯೋಜನೆಗಳು', 'url' => '/schemes', 'route_match' => 'farmer.schemes.*', 'badge' => 'GOVT', 'badge_color' => 'amber', 'new_tab' => false, 'is_visible' => true],
            ['label_en' => 'Videos', 'label_kn' => 'ವಿಡಿಯೋಗಳು', 'url' => '/videos', 'route_match' => 'farmer.videos.*', 'badge' => '', 'badge_color' => 'emerald', 'new_tab' => false, 'is_visible' => true],
            ['label_en' => 'News', 'label_kn' => 'ಸುದ್ದಿಗಳು', 'url' => '/news', 'route_match' => 'farmer.news.*', 'badge' => 'LIVE', 'badge_color' => 'rose', 'new_tab' => false, 'is_visible' => true],
        ]);
        $showLocationPill = (bool) \App\Models\SystemSetting::get('navbar_show_location_pill', true);
        $showLanguageToggle = (bool) \App\Models\SystemSetting::get('navbar_show_language_toggle', true);
        $showHamburgerButton = (bool) \App\Models\SystemSetting::get('navbar_show_hamburger_button', true);
        $showDesktopAppButton = (bool) \App\Models\SystemSetting::get('navbar_show_desktop_app_button', true);
        $showSubtitle = (bool) \App\Models\SystemSetting::get('navbar_show_subtitle', true);

        // 2. Mobile Bottom Dock
        $rawDockNav = \App\Models\SystemSetting::get('navbar_mobile_dock_links');
        $mobileDockNavLinks = is_array($rawDockNav) ? $rawDockNav : (json_decode($rawDockNav ?? '', true) ?: [
            ['icon' => 'home', 'label_en' => 'Home', 'label_kn' => 'ಮುಖಪುಟ', 'url' => '/', 'route_match' => 'home', 'has_dot' => false, 'new_tab' => false, 'is_visible' => true],
            ['icon' => 'rates', 'label_en' => 'Rates', 'label_kn' => 'ದರಗಳು', 'url' => '/crops', 'route_match' => 'farmer.crops.*', 'has_dot' => true, 'new_tab' => false, 'is_visible' => true],
            ['icon' => 'schemes', 'label_en' => 'Schemes', 'label_kn' => 'ಯೋಜನೆಗಳು', 'url' => '/schemes', 'route_match' => 'farmer.schemes.*', 'has_dot' => false, 'new_tab' => false, 'is_visible' => true],
            ['icon' => 'weather', 'label_en' => 'Weather', 'label_kn' => 'ಹವಾಮಾನ', 'url' => '/weather', 'route_match' => 'farmer.weather.*', 'has_dot' => false, 'new_tab' => false, 'is_visible' => true],
        ]);
        $mobileDockNavLinks = array_values(array_filter($mobileDockNavLinks, fn($i) => !empty($i['is_visible'])));
        $mobileDockStyle = \App\Models\SystemSetting::get('navbar_mobile_dock_style', 'floating');
        $mobileDockShowLabels = (bool) \App\Models\SystemSetting::get('navbar_mobile_dock_show_labels', true);

        // 3. Mobile Hamburger Drawer
        $rawDrawerNav = \App\Models\SystemSetting::get('navbar_drawer_links');
        $drawerNavLinks = is_array($rawDrawerNav) ? $rawDrawerNav : (json_decode($rawDrawerNav ?? '', true) ?: [
            ['icon' => '🌾', 'label_en' => 'Daily Mandi Rates', 'label_kn' => 'ದೈನಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು', 'subtitle_en' => 'Karnataka mandi live prices', 'subtitle_kn' => 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಮಾರುಕಟ್ಟೆ ದರಗಳು', 'url' => '/', 'badge' => '', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '📊', 'label_en' => 'Crops & Rate Forecast', 'label_kn' => 'ಬೆಳೆಗಳು & ದರ ಮುನ್ಸೂಚನೆ', 'subtitle_en' => 'Vegetables, Grains, Arecanut', 'subtitle_kn' => 'ತರಕಾರಿ, ಧಾನ್ಯ, ಅಡಿಕೆ', 'url' => '/crops', 'badge' => 'LIVE', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🏛️', 'label_en' => 'Government Schemes', 'label_kn' => 'ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು', 'subtitle_en' => 'Subsidies & welfare', 'subtitle_kn' => 'ಸಬ್ಸಿಡಿ & ಸಹಾಯಧನ', 'url' => '/schemes', 'badge' => 'NEW', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🎬', 'label_en' => 'Farming Videos', 'label_kn' => 'ಕೃಷಿ ವಿಡಿಯೋಗಳು', 'subtitle_en' => 'Agri video guides', 'subtitle_kn' => 'ಕೃಷಿ ಮಾಹಿತಿ ವಿಡಿಯೋಗಳು', 'url' => '/videos', 'badge' => '', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '📰', 'label_en' => 'Agricultural News', 'label_kn' => 'ಕೃಷಿ ಸುದ್ದಿಗಳು', 'subtitle_en' => 'Alerts & advisories', 'subtitle_kn' => 'ಹವಾಮಾನ ಎಚ್ಚರಿಕೆ & ವರದಿಗಳು', 'url' => '/news', 'badge' => '', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '📖', 'label_en' => 'Farming Guides & Advisory', 'label_kn' => 'ಕೃಷಿ ಕೈಪಿಡಿ & ಮಾರ್ಗದರ್ಶಿ', 'subtitle_en' => 'Pest control & soil advisory', 'subtitle_kn' => 'ಕೀಟ ನಿಯಂತ್ರಣ ಮತ್ತು ಮಣ್ಣು ಪರೀಕ್ಷೆ', 'url' => '/articles', 'badge' => '', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '💬', 'label_en' => 'Report Issue / Feedback', 'label_kn' => 'ದರ ವ್ಯತ್ಯಾಸ ವರದಿ / ಸಲಹೆ', 'subtitle_en' => 'Voice & photo helpdesk', 'subtitle_kn' => 'ಧ್ವನಿ ಅಥವಾ ಫೋಟೋ ಮೂಲಕ ತಿಳಿಸಿ', 'url' => '/feedback', 'badge' => 'HELP', 'new_tab' => false, 'is_visible' => true],
        ]);
        $drawerNavLinks = array_values(array_filter($drawerNavLinks, fn($i) => !empty($i['is_visible'])));
        $drawerShowDistrict = (bool) \App\Models\SystemSetting::get('navbar_drawer_show_district', true);
        $drawerShowWhatsapp = (bool) \App\Models\SystemSetting::get('navbar_drawer_show_whatsapp', true);
        $drawerWhatsappUrl = \App\Models\SystemSetting::get('navbar_drawer_whatsapp_url', 'https://chat.whatsapp.com/sample-farmer-group');
        $drawerWhatsappLabelEn = \App\Models\SystemSetting::get('navbar_drawer_whatsapp_label_en', 'Join WhatsApp Farmer Helpdesk');
        $drawerWhatsappLabelKn = \App\Models\SystemSetting::get('navbar_drawer_whatsapp_label_kn', 'ವಾಟ್ಸಾಪ್ ರೈತರ ಸಹಾಯವಾಣಿಗೆ ಸೇರಿ');
        $drawerShowPwa = (bool) \App\Models\SystemSetting::get('navbar_drawer_show_pwa', true);
        $drawerPwaLabelEn = \App\Models\SystemSetting::get('navbar_drawer_pwa_label_en', 'Install App on Phone');
        $drawerPwaLabelKn = \App\Models\SystemSetting::get('navbar_drawer_pwa_label_kn', 'ಆ್ಯಪ್ ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಿ');

        // 4. Floating Feedback Action Button (FAB)
        $showFeedbackFab = (bool) \App\Models\SystemSetting::get('navbar_show_feedback_fab', true);
        $feedbackFabPulse = (bool) \App\Models\SystemSetting::get('navbar_feedback_fab_pulse', true);
        $feedbackFabUrl = \App\Models\SystemSetting::get('navbar_feedback_fab_url', '/feedback');
    @endphp

    <!-- Elevated Classic Floating Header with Matching Background & Classic Shadow -->
    <header id="siteHeader" class="fixed top-0 left-0 right-0 w-full z-40 bg-[#F5EFE6]/98 backdrop-blur-md border-b-2 border-[#D9CEB8] shadow-[0_4px_20px_-2px_rgba(0,0,0,0.08),0_2px_8px_-1px_rgba(0,0,0,0.05)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-18">
                <!-- Brand Identity (Dynamic App Logo & Site Name) -->
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 sm:gap-3 group focus:outline-none">
                    @php
                        $customLogoPath = public_path(ltrim($appLogo, '/'));
                        $hasCustomLogo = $appLogo && $appLogo !== '/icons/icon-192.svg' && file_exists($customLogoPath);
                    @endphp
                    @if($hasCustomLogo)
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-white flex items-center justify-center overflow-hidden shadow-xs border-2 border-[#D9CEB8] shrink-0 p-0.5 group-hover:scale-105 transition-transform duration-200">
                            <img src="{{ $appLogoUrl }}" 
                                 alt="{{ \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava') }}" 
                                 class="w-full h-full object-contain rounded-xl">
                        </div>
                    @else
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#1C5A2C] text-white flex items-center justify-center font-black text-xl shadow-inner border border-[#134423] shrink-0">
                            🌾
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <span class="font-extrabold text-[#1C5A2C] text-sm sm:text-base lg:text-lg tracking-tight whitespace-nowrap {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                {{ $activeLocale === 'kn' 
                                    ? \App\Models\SystemSetting::get('application_name_kn', \App\Models\SystemSetting::get('application_name', 'ಕೃಷಿ ಬಾಂಧವ')) 
                                    : \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava') }}
                            </span>
                        </div>
                        @if($showSubtitle)
                        <p class="text-[10px] sm:text-[11px] text-stone-500 font-medium font-kannada truncate max-w-[130px] sm:max-w-[200px] lg:max-w-none {{ $activeLocale === 'kn' ? 'leading-snug pt-0.5' : 'leading-none mt-0.5' }}">
                            {{ $activeLocale === 'en' 
                                ? \App\Models\SystemSetting::get('navbar_subtitle_en', 'Direct Mandi Rates & Forecast') 
                                : \App\Models\SystemSetting::get('navbar_subtitle_kn', 'ನೇರ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ರೈತ ಮುನ್ಸೂಚನೆ') }}
                        </p>
                        @endif
                    </div>
                </a>

                <!-- Dynamic Desktop Navigation (Managed via Navbar CMS - Desktop Only) -->
                <nav class="hidden lg:flex items-center gap-1.5 text-xs sm:text-sm font-bold">
                    @foreach($desktopNavLinks as $dLink)
                        @if(!empty($dLink['is_visible']))
                            @php
                                $isLinkActive = false;
                                if (!empty($dLink['route_match'])) {
                                    foreach(explode(',', $dLink['route_match']) as $rPattern) {
                                        $trimmed = trim($rPattern);
                                        if (!empty($trimmed) && request()->routeIs($trimmed)) {
                                            $isLinkActive = true;
                                            break;
                                        }
                                    }
                                }
                                if (!$isLinkActive && !empty($dLink['url'])) {
                                    $targetPath = ltrim(parse_url($dLink['url'], PHP_URL_PATH) ?? '', '/');
                                    if (!empty($targetPath) && request()->is($targetPath . '*')) {
                                        $isLinkActive = true;
                                    } elseif ($targetPath === '' && request()->is('/')) {
                                        $isLinkActive = true;
                                    }
                                }
                                $linkTitle = $activeLocale === 'kn' ? ($dLink['label_kn'] ?? $dLink['label_en']) : ($dLink['label_en'] ?? '');
                            @endphp
                            <a href="{{ url($dLink['url'] ?? '/') }}" 
                               @if(!empty($dLink['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                               class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ $isLinkActive ? 'bg-[#E5DDC9] text-stone-900 font-black shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-black/5' }}">
                                <span>{{ $linkTitle }}</span>
                                @if(!empty($dLink['badge']))
                                    @php
                                        $badgeBg = match($dLink['badge_color'] ?? 'emerald') {
                                            'rose' => 'bg-rose-600 text-white',
                                            'amber' => 'bg-amber-500 text-stone-950',
                                            'cyan' => 'bg-cyan-600 text-white',
                                            default => 'bg-[#1C5A2C] text-white',
                                        };
                                    @endphp
                                    <span class="text-[9px] px-1.5 py-0.2 rounded-full font-black tracking-wider uppercase {{ $badgeBg }}">
                                        {{ $dLink['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </nav>

                <!-- Right Utility Bar: Language Toggle + Hamburger Button (Desktop extras: Location & App) -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    @if($showLocationPill)
                    <!-- Location Pill - Desktop Only (Hidden on Mobile & Tablet to prevent header crowding) -->
                    <button type="button" 
                            id="tourHeaderLocationPill"
                            x-data
                            @click="$dispatch('open-location-modal')" 
                            class="header-location-pill hidden lg:flex items-center gap-1.5 bg-[#FAF8F5] border border-[#DDD2BE] rounded-full px-3 py-1.5 text-xs font-bold text-stone-800 transition hover:bg-stone-100 active:scale-95 cursor-pointer shadow-2xs shrink-0"
                            title="{{ $activeLocale === 'en' ? 'Click to change location' : 'ಸ್ಥಳ ಬದಲಾಯಿಸಲು ಕ್ಲಿಕ್ ಮಾಡಿ' }}">
                        <span class="text-rose-500 text-xs">📍</span>
                        <span class="max-w-[130px] truncate {{ $activeLocale === 'kn' ? 'font-kannada pt-0.5 leading-normal' : '' }}">
                            @if(!empty($activeLocalArea))
                                @php
                                    $displayAreaPill = ($activeLocale === 'kn')
                                        ? ((!empty($activeLocalAreaKn) && !preg_match('/^(ಬೆಂಗಳೂರು|Bengaluru|Bangalore)$/iu', trim($activeLocalAreaKn))) ? $activeLocalAreaKn : $activeLocalArea)
                                        : $activeLocalArea;
                                @endphp
                                {{ $displayAreaPill }}
                            @else
                                {{ $activeLocale === 'en' ? ($activeDistrict->name ?? $activeDistrict->name_kn ?? 'Shivamogga') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಶಿವಮೊಗ್ಗ') }}
                            @endif
                        </span>
                    </button>
                    @endif

                    @if($showLanguageToggle)
                    <!-- Interactive Kannada / English Toggle -->
                    <div id="tourLangToggle" class="flex items-center bg-white rounded-xl p-1 text-xs font-bold border border-stone-200/90 shadow-2xs shrink-0">
                        <a href="{{ route('locale.switch', 'en') }}" 
                            class="px-2 py-1 rounded-lg transition {{ $activeLocale === 'en' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-100' }}"
                            title="Switch to English">
                            EN
                        </a>
                        <a href="{{ route('locale.switch', 'kn') }}" 
                            class="px-2 py-1 rounded-lg transition font-kannada {{ $activeLocale === 'kn' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'text-stone-500 hover:text-stone-900 hover:bg-stone-100' }}"
                            title="ಕನ್ನಡಕ್ಕೆ ಬದಲಾಯಿಸಿ">
                            ಕನ್ನಡ
                        </a>
                    </div>
                    @endif

                    @if($showDesktopAppButton)
                    <!-- Desktop PWA App Button - Wide Desktop Only (Hidden on Mobile, Tablet & Standalone App) -->
                    <button type="button" 
                            x-data="{ isStandalone: window.isPwaStandalone }"
                            x-show="!isStandalone"
                            @click="$dispatch('open-install-prompt')"
                            class="hidden xl:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-900 border border-amber-300 font-bold text-xs transition active:scale-95 cursor-pointer shadow-2xs shrink-0"
                            title="{{ $activeLocale === 'en' ? 'Get the App' : 'ಆ್ಯಪ್ ಪಡೆಯಿರಿ' }}">
                        <span>📲</span>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada pt-0.5' : '' }}">{{ $activeLocale === 'en' ? 'App' : 'ಆ್ಯಪ್' }}</span>
                    </button>
                    @endif

                    @if($showHamburgerButton)
                    <!-- Top-Right Hamburger Menu Button -->
                    <button type="button" 
                            @click="isMobileMenuOpen = true"
                            class="flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white border border-[#DDD2BE] text-stone-700 hover:text-[#1C5A2C] hover:border-[#1C5A2C] hover:bg-stone-50 transition active:scale-95 shadow-2xs cursor-pointer group shrink-0"
                            aria-label="{{ $activeLocale === 'en' ? 'Open navigation menu' : 'ಮೆನು ತೆರೆಯಿರಿ' }}"
                            title="{{ $activeLocale === 'en' ? 'Menu' : 'ಮೆನು' }}">
                        <svg class="w-5 h-5 text-stone-700 group-hover:text-[#1C5A2C] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Flow (Compact mobile horizontal padding to give cards maximum width) -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-3.5 sm:py-5 min-w-0 page-main-container">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Astro-Style Floating Island Bottom Navigation Dock (PWA & Mobile-First, Managed via Navbar CMS) -->
    @if(count($mobileDockNavLinks) > 0)
    <nav id="mobileBottomNav" 
         class="md:hidden fixed {{ $mobileDockStyle === 'floating' ? 'bottom-3 inset-x-3 max-w-md mx-auto rounded-2xl border border-stone-200/90 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.12),0_4px_10px_-2px_rgba(0,0,0,0.06)]' : 'bottom-0 inset-x-0 w-full border-t border-stone-200/90 shadow-lg' }} z-40 bg-white/95 backdrop-blur-xl pb-[env(safe-area-inset-bottom,0px)] transition-all">
        <div class="grid h-15 w-full items-center px-1.5 py-1" style="grid-template-columns: repeat({{ count($mobileDockNavLinks) }}, minmax(0, 1fr));">
            @foreach($mobileDockNavLinks as $tab)
                @php
                    $isTabActive = false;
                    if (!empty($tab['route_match'])) {
                        foreach(explode(',', $tab['route_match']) as $rPattern) {
                            $trimmed = trim($rPattern);
                            if (!empty($trimmed) && request()->routeIs($trimmed)) {
                                $isTabActive = true;
                                break;
                            }
                        }
                    }
                    if (!$isTabActive && !empty($tab['url'])) {
                        $targetPath = ltrim(parse_url($tab['url'], PHP_URL_PATH) ?? '', '/');
                        if (!empty($targetPath) && request()->is($targetPath . '*')) {
                            $isTabActive = true;
                        } elseif ($targetPath === '' && request()->is('/')) {
                            $isTabActive = true;
                        }
                    }
                    $tabTitle = $activeLocale === 'kn' ? ($tab['label_kn'] ?? $tab['label_en']) : ($tab['label_en'] ?? '');
                    $tabIcon = $tab['icon'] ?? 'home';
                @endphp
                <a href="{{ url($tab['url'] ?? '/') }}" 
                   @if(!empty($tab['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                   class="group relative flex flex-col items-center justify-center py-1 rounded-xl transition-all duration-200 active:scale-90 {{ $isTabActive ? 'text-[#1C5A2C] bg-emerald-50/90 font-black' : 'text-stone-500 hover:text-stone-800 font-semibold' }}">
                    <div class="relative">
                        @if($tabIcon === 'home')
                            @if($isTabActive)
                                <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                                    <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                            @endif
                        @elseif($tabIcon === 'rates')
                            <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="{{ $isTabActive ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="{{ $isTabActive ? '1.5' : '2' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        @elseif($tabIcon === 'schemes')
                            @if($isTabActive)
                                <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 1 1-.832 1.248L12 3.901 3.416 9.624a.75.75 0 0 1-.832-1.248l9-6Z" />
                                    <path fill-rule="evenodd" d="M20.25 10.332v9.918H21a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5h.75v-9.918a.75.75 0 0 1 .634-.74A49.109 49.109 0 0 1 12 9c2.59 0 5.134.202 7.616.592a.75.75 0 0 1 .634.74Zm-7.5 2.418a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Zm3-.75a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-1.5 0v-6.75a.75.75 0 0 1 .75-.75ZM9 12.75a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Z" clip-rule="evenodd" />
                                    <path d="M12 7.875a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5m-15 10.5V10.5M3 21h18M12 6.75h.008v.008H12V6.75z" />
                                </svg>
                            @endif
                        @elseif($tabIcon === 'weather')
                            <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="{{ $isTabActive ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="{{ $isTabActive ? '1.5' : '2' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
                            </svg>
                        @elseif($tabIcon === 'videos')
                            @if($isTabActive)
                                <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm14.024-.983a1.125 1.125 0 0 1 0 1.966l-5.603 3.113A1.125 1.125 0 0 1 9 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113Z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" />
                                </svg>
                            @endif
                        @elseif($tabIcon === 'news')
                            <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="{{ $isTabActive ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="{{ $isTabActive ? '1.5' : '2' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" />
                            </svg>
                        @elseif($tabIcon === 'help')
                            <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="{{ $isTabActive ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="{{ $isTabActive ? '1.5' : '2' }}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-1.074-.85 9.99 9.99 0 001.078-2.612C3.896 16.036 3 14.12 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                            </svg>
                        @else
                            <span class="text-base leading-none">{{ $tabIcon }}</span>
                        @endif

                        @if(!empty($tab['has_dot']))
                            <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-amber-400 shadow-xs"></span>
                        @endif
                    </div>
                    @if($mobileDockShowLabels)
                    <span class="text-[10px] {{ $activeLocale === 'kn' ? 'font-kannada pt-0.5 leading-tight' : 'font-sans leading-none mt-0.5' }}">
                        {{ $tabTitle }}
                    </span>
                    @endif
                    @if($isTabActive)
                        <span class="w-1.5 h-1 rounded-full bg-[#1C5A2C] mt-0.5"></span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
    @endif

    <!-- Slide-Over Drawer (Off-Canvas Agricultural Navigation Menu, Managed via Navbar CMS) -->
    <div x-cloak x-show="isMobileMenuOpen" class="relative z-50">
        <!-- Backdrop -->
        <div x-show="isMobileMenuOpen" 
             x-transition:enter="transition-opacity ease-linear duration-250"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="isMobileMenuOpen = false"
             class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs"></div>

        <!-- Drawer Panel (Slides smoothly from Right) -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
                <div x-show="isMobileMenuOpen"
                     x-transition:enter="transform transition ease-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in duration-250"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen max-w-xs sm:max-w-sm bg-[#FAF8F5] border-l border-[#DDD2BE] shadow-2xl flex flex-col pointer-events-auto overflow-y-auto">
                     
                    <!-- Drawer Header -->
                    <div class="p-4 sm:p-5 border-b border-[#E8DFCE] bg-[#F5EFE6] flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-[#1C5A2C] text-white flex items-center justify-center font-black text-lg shadow-sm">
                                🌾
                            </div>
                            <div>
                                <h3 class="font-extrabold text-[#1C5A2C] text-sm sm:text-base leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                    {{ $activeLocale === 'kn' ? 'ಕೃಷಿ ಬಾಂಧವ' : 'Krushi Baandhava' }}
                                </h3>
                                <p class="text-[10px] text-stone-500 font-medium">
                                    {{ $activeLocale === 'en' ? 'Mandi & Farmer Hub' : 'ಕರ್ನಾಟಕ ರೈತ ಮಾರುಕಟ್ಟೆ' }}
                                </p>
                            </div>
                        </div>
                        <button type="button" 
                                @click="isMobileMenuOpen = false"
                                class="w-8 h-8 rounded-lg bg-white border border-[#DDD2BE] flex items-center justify-center text-stone-500 hover:text-stone-900 hover:bg-stone-100 transition cursor-pointer">
                            <span class="text-xl leading-none">&times;</span>
                        </button>
                    </div>

                    @if($drawerShowDistrict)
                    <!-- Active District Card inside Drawer -->
                    <div class="p-3.5 mx-4 mt-4 rounded-xl bg-white border border-[#E8DFCE] flex items-center justify-between shadow-2xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-rose-500 text-sm">📍</span>
                            <div class="truncate">
                                <span class="text-[10px] text-stone-400 font-bold uppercase tracking-wider block">{{ $activeLocale === 'en' ? 'Active District' : 'ಆಯ್ಕೆಯಾದ ಜಿಲ್ಲೆ' }}</span>
                                <span class="text-xs font-black text-stone-800 {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                    @if(!empty($activeLocalArea))
                                        @php
                                            $displayAreaDrawer = ($activeLocale === 'kn')
                                                ? ((!empty($activeLocalAreaKn) && !preg_match('/^(ಬೆಂಗಳೂರು|Bengaluru|Bangalore)$/iu', trim($activeLocalAreaKn))) ? $activeLocalAreaKn : $activeLocalArea)
                                                : $activeLocalArea;
                                        @endphp
                                        {{ $displayAreaDrawer }}
                                        <span class="text-stone-500 font-medium text-[11px]">({{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Shivamogga') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಶಿವಮೊಗ್ಗ') }})</span>
                                    @else
                                        {{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Shivamogga') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಶಿವಮೊಗ್ಗ') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        <button type="button" 
                                @click="isMobileMenuOpen = false; $dispatch('open-location-modal')"
                                class="text-xs text-[#1C5A2C] hover:text-[#154622] font-black underline shrink-0 pl-2 cursor-pointer">
                            {{ $activeLocale === 'en' ? 'Change' : 'ಬದಲಿಸಿ' }}
                        </button>
                    </div>
                    @endif

                    <!-- Navigation Links List (Dynamically populated via Drawer CMS) -->
                    <div class="flex-1 py-4 px-3 space-y-1">
                        <div class="px-3.5 pt-1 pb-1 text-[10px] font-black uppercase tracking-wider text-stone-400">
                            {{ $activeLocale === 'en' ? 'Menu Directory' : 'ಮೆನು ವಿಭಾಗಗಳು' }}
                        </div>

                        @foreach($drawerNavLinks as $dItem)
                            @php
                                $isItemActive = false;
                                if (!empty($dItem['url'])) {
                                    $targetPath = ltrim(parse_url($dItem['url'], PHP_URL_PATH) ?? '', '/');
                                    if (!empty($targetPath) && request()->is($targetPath . '*')) {
                                        $isItemActive = true;
                                    } elseif ($targetPath === '' && request()->is('/')) {
                                        $isItemActive = true;
                                    }
                                }
                                $itemTitle = $activeLocale === 'kn' ? ($dItem['label_kn'] ?? $dItem['label_en']) : ($dItem['label_en'] ?? '');
                                $itemSub = $activeLocale === 'kn' ? ($dItem['subtitle_kn'] ?? $dItem['subtitle_en'] ?? '') : ($dItem['subtitle_en'] ?? '');
                            @endphp
                            <a href="{{ url($dItem['url'] ?? '/') }}" 
                               @if(!empty($dItem['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition {{ $isItemActive ? 'bg-[#1C5A2C] text-white shadow-xs' : 'text-stone-700 hover:bg-white hover:text-stone-900' }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-base shrink-0">{{ $dItem['icon'] ?? '🌾' }}</span>
                                    <div class="truncate">
                                        <div class="{{ $activeLocale === 'kn' ? 'font-kannada pt-0.5' : '' }} leading-tight truncate">{{ $itemTitle }}</div>
                                        @if(!empty($itemSub))
                                            <div class="text-[10px] {{ $isItemActive ? 'text-white/80' : 'text-stone-400' }} font-normal truncate mt-0.5">{{ $itemSub }}</div>
                                        @endif
                                    </div>
                                </div>
                                @if(!empty($dItem['badge']))
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full font-black uppercase tracking-wider {{ $isItemActive ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $dItem['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <!-- Drawer Footer (WhatsApp Helpdesk & PWA Install) -->
                    @if($drawerShowWhatsapp || $drawerShowPwa)
                    <div class="p-4 border-t border-[#E8DFCE] bg-[#F5EFE6] space-y-2.5">
                        @if($drawerShowWhatsapp)
                        <!-- WhatsApp Helpdesk -->
                        <a href="{{ $drawerWhatsappUrl }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition">
                            <span class="text-sm">💬</span>
                            <span>{{ $activeLocale === 'en' ? $drawerWhatsappLabelEn : $drawerWhatsappLabelKn }}</span>
                        </a>
                        @endif

                        @if($drawerShowPwa)
                        <!-- PWA Install / Status -->
                        <div x-data="{ isStandalone: window.isPwaStandalone }">
                            <template x-if="!isStandalone">
                                <button type="button" 
                                        @click="isMobileMenuOpen = false; $dispatch('open-install-prompt')"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-white border border-[#DDD2BE] text-stone-700 hover:bg-stone-50 font-bold text-xs transition cursor-pointer">
                                    <span>📲</span>
                                    <span>{{ $activeLocale === 'en' ? $drawerPwaLabelEn : $drawerPwaLabelKn }}</span>
                                </button>
                            </template>
                            <template x-if="isStandalone">
                                <div class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs">
                                    <span>✅</span>
                                    <span>{{ $activeLocale === 'kn' ? 'ಆ್ಯಪ್ ಇನ್‌ಸ್ಟಾಲ್ ಆಗಿದೆ' : 'App Installed' }}</span>
                                </div>
                            </template>
                        </div>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- Elevated Classic Agricultural Footer with Mobile Accordions -->
    <x-farmer-footer />

    <!-- Adaptive Feedback Action Button (Prominently Highlighted Forest Green FAB, Managed via Navbar CMS) -->
    @if($showFeedbackFab && !request()->routeIs('farmer.feedback.*'))
    <a href="{{ url($feedbackFabUrl) }}" 
       aria-label="{{ $activeLocale === 'en' ? 'Farmer Feedback and Issue Helpdesk' : 'ರೈತರ ಪ್ರತಿಕ್ರಿಯೆ ಮತ್ತು ಸಹಾಯವಾಣಿ' }}"
       title="{{ $activeLocale === 'en' ? 'Report Issue or Give Feedback' : 'ದರ ವ್ಯತ್ಯಾಸ ವರದಿ / ಸಲಹೆ ನೀಡಿ' }}"
       style="background: linear-gradient(135deg, #134423 0%, #1C5A2C 50%, #257238 100%); box-shadow: 0 10px 25px -4px rgba(28, 90, 44, 0.55), 0 4px 8px -2px rgba(28, 90, 44, 0.35); border: 2.5px solid #FFFFFF;"
       class="fixed bottom-[calc(5.25rem+env(safe-area-inset-bottom,0px))] md:bottom-7 right-4 sm:right-6 md:right-8 z-40 group flex items-center justify-center w-[52px] h-[52px] md:w-auto md:h-13 md:px-5 rounded-full text-white font-extrabold text-xs sm:text-[13.5px] hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer">
        
        @if($feedbackFabPulse)
        <!-- Live Attention Indicator Dot -->
        <span class="absolute -top-1 -right-1 flex h-4 w-4 pointer-events-none">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-90"></span>
            <span class="relative inline-flex rounded-full h-4 w-4 bg-amber-500 border-2 border-white shadow-sm"></span>
        </span>
        @endif

        <!-- Bold Solid Feedback / Message Bubble SVG Symbol -->
        <div class="flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-white transition-transform duration-300 group-hover:scale-110 drop-shadow-xs" 
                 viewBox="0 0 24 24" 
                 fill="currentColor">
                <path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 006 21.75a6.721 6.721 0 003.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9s-4.428-9-9.75-9-9.75 3.97-9.75 9c0 2.409 1.025 4.587 2.674 6.192.232.226.277.428.254.543a3.73 3.73 0 01-.814 1.686.75.75 0 00.444 1.223zM8.25 10.5a.75.75 0 01.75-.75h6a.75.75 0 010 1.5h-6a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z" clip-rule="evenodd" />
            </svg>
        </div>

        <!-- Desktop Expanded Label (Hidden on mobile to preserve 100% of mandi rate screen real estate) -->
        <span class="hidden md:inline-flex items-center gap-1.5 ml-2.5 whitespace-nowrap text-white font-extrabold {{ $activeLocale === 'kn' ? 'font-kannada pt-0.5' : 'font-sans' }}">
            <span>{{ $activeLocale === 'en' ? 'Feedback / Report' : 'ಪ್ರತಿಕ್ರಿಯೆ & ಸಲಹೆ' }}</span>
        </span>
    </a>
    @endif

    <!-- Location Picker Modal Component -->
    <x-location-modal :all-districts="$allDistricts ?? null" :active-district="$activeDistrict ?? null" />

    <!-- Centered Negilu-Style PWA Install Modal & Prompt Controller -->
    <div 
        x-data="{
            showInstallModal: false,
            deferredPrompt: window.deferredPwaPrompt || null,
            showManualGuide: false,
            platform: 'android',
            isInstalled: false,

            init() {
                // Detect OS / browser platform
                const ua = navigator.userAgent || navigator.vendor || window.opera || '';
                if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
                    this.platform = 'ios';
                } else if (/android/i.test(ua)) {
                    this.platform = 'android';
                } else {
                    this.platform = 'desktop';
                }

                // Check if running in standalone PWA window
                if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                    this.isInstalled = true;
                }

                // Listen for early or late prompt events
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    window.deferredPwaPrompt = e;
                    this.deferredPrompt = e;
                });

                window.addEventListener('pwa-prompt-ready', () => {
                    this.deferredPrompt = window.deferredPwaPrompt;
                });

                window.addEventListener('appinstalled', () => {
                    this.showInstallModal = false;
                    this.deferredPrompt = null;
                    window.deferredPwaPrompt = null;
                    this.isInstalled = true;
                });

                window.addEventListener('open-install-prompt', () => {
                    this.deferredPrompt = window.deferredPwaPrompt || this.deferredPrompt;
                    this.showManualGuide = false;
                    this.showInstallModal = true;
                });
            },

            async handleInstallClick() {
                this.deferredPrompt = window.deferredPwaPrompt || this.deferredPrompt;
                if (this.deferredPrompt) {
                    try {
                        this.deferredPrompt.prompt();
                        const { outcome } = await this.deferredPrompt.userChoice;
                        if (outcome === 'accepted') {
                            this.showInstallModal = false;
                            this.isInstalled = true;
                        }
                        this.deferredPrompt = null;
                        window.deferredPwaPrompt = null;
                    } catch (err) {
                        console.warn('Install prompt error:', err);
                        this.showManualGuide = true;
                    }
                } else {
                    // Browser has not fired prompt or does not support automatic prompt (e.g. iOS Safari)
                    // Show clear in-modal guide instead of browser alert
                    this.showManualGuide = true;
                }
            }
        }"
    >
        <!-- Modal Backdrop & Dialog -->
        <div 
            x-show="showInstallModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            aria-modal="true"
            role="dialog"
        >
            <!-- Backdrop -->
            <div 
                x-show="showInstallModal"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="showInstallModal = false"
                class="fixed inset-0 bg-black/65 backdrop-blur-xs transition-opacity"
            ></div>

            <!-- Centered Modal Card (Direct Negilu Krushi Alignment) -->
            <div 
                x-show="showInstallModal"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative w-full max-w-sm sm:max-w-md bg-[#FAF8F5] rounded-3xl p-6 sm:p-7 shadow-2xl border border-[#DDD2BE] text-center z-10 overflow-hidden"
            >
                <!-- Close Button (Top-Right) -->
                <button 
                    type="button"
                    @click="showInstallModal = false"
                    class="absolute top-4 right-4 text-stone-400 hover:text-stone-700 w-8 h-8 rounded-full flex items-center justify-center hover:bg-stone-200/60 transition cursor-pointer"
                    aria-label="Close"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Phone / App Icon Graphic -->
                <div class="mx-auto mb-4 flex items-center justify-center">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white p-2.5 shadow-md border border-[#E5DDC9] flex items-center justify-center relative">
                        <img src="{{ $appLogoUrl }}" alt="App Icon" class="w-full h-full object-contain">
                        <span class="absolute -bottom-1.5 -right-1.5 bg-[#1C5A2C] text-white text-[11px] p-1 rounded-full shadow-xs">📲</span>
                    </div>
                </div>

                <!-- Title & Subtitle Matching Negilu -->
                <h3 class="text-xl sm:text-2xl font-black text-stone-900 tracking-tight leading-snug">
                    {{ $activeLocale === 'kn' ? 'ಕೃಷಿ ಬಾಂಧವ ಆ್ಯಪ್ ಪಡೆಯಿರಿ' : 'Get the Krushi Baandhava app' }}
                </h3>
                <p class="text-xs sm:text-[13px] text-stone-500 font-medium mt-1.5 px-2 leading-relaxed">
                    {{ $activeLocale === 'kn' 
                        ? 'ಪ್ಲೇ ಸ್ಟೋರ್ ಬೇಡ · ಉಚಿತ · ಪ್ರತಿದಿನದ ಮಾರುಕಟ್ಟೆ ದರಗಳು & ಮುನ್ಸೂಚನೆ ನಿಮ್ಮ ಮೊಬೈಲ್‌ನಲ್ಲಿ' 
                        : 'No Play Store · Free · Daily prices & forecasts on your phone' }}
                </p>

                <!-- Action Button or Step-by-Step Guide -->
                <div class="mt-6">
                    <!-- If already installed -->
                    <template x-if="isInstalled">
                        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs sm:text-sm font-bold flex items-center justify-center gap-2">
                            <span>✅</span>
                            <span>{{ $activeLocale === 'kn' ? 'ಆ್ಯಪ್ ಈಗಾಗಲೇ ನಿಮ್ಮ ಫೋನ್‌ನಲ್ಲಿ ಇನ್‌ಸ್ಟಾಲ್ ಆಗಿದೆ!' : 'The app is already installed on your device!' }}</span>
                        </div>
                    </template>

                    <!-- Normal State: Big Dark Green Download Button -->
                    <template x-if="!isInstalled && !showManualGuide">
                        <div>
                            <button 
                                type="button"
                                @click="handleInstallClick()"
                                class="w-full py-3.5 px-6 rounded-2xl bg-[#1C5A2C] hover:bg-[#154622] text-white font-extrabold text-sm sm:text-base shadow-lg hover:shadow-xl transition transform active:scale-98 flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <span class="text-lg">📲</span>
                                <span>{{ $activeLocale === 'kn' ? 'ಡೌನ್‌ಲೋಡ್ / ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಿ' : 'Download now' }}</span>
                            </button>
                            <p class="text-[11px] text-stone-400 font-semibold mt-2.5 tracking-wide">
                                {{ $activeLocale === 'kn' ? 'ಒಂದೇ ಕ್ಲಿಕ್‌ನಲ್ಲಿ ಇನ್‌ಸ್ಟಾಲ್ (One-tap install)' : 'One-tap install' }}
                            </p>
                        </div>
                    </template>

                    <!-- Inline Step-by-Step Guide (Fallback if automatic prompt unavailable) -->
                    <template x-if="!isInstalled && showManualGuide">
                        <div class="text-left bg-white border border-[#DDD2BE] rounded-2xl p-4 shadow-inner space-y-3">
                            <div class="flex items-center gap-2 text-stone-900 font-bold text-xs sm:text-sm pb-2 border-b border-stone-100">
                                <span class="text-base">💡</span>
                                <span>{{ $activeLocale === 'kn' ? 'ನಿಮ್ಮ ಮೊಬೈಲ್‌ನಲ್ಲಿ ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಲು ಹಂತಗಳು:' : 'How to install on your device:' }}</span>
                            </div>

                            <!-- iOS Guide -->
                            <template x-if="platform === 'ios'">
                                <div class="space-y-2 text-xs text-stone-600">
                                    <div class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-[10px]">1</span>
                                        <span>{{ $activeLocale === 'kn' ? 'Safari ಬ್ರೌಸರ್ ಕೆಳಭಾಗದಲ್ಲಿರುವ Share (⎋) ಬಟನ್ ಒತ್ತಿ.' : 'Tap the Share button (⎋) in Safari bottom bar.' }}</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-[10px]">2</span>
                                        <span>{{ $activeLocale === 'kn' ? 'ಕೆಳಗೆ ಸ್ಕ್ರಾಲ್ ಮಾಡಿ "Add to Home Screen" (➕) ಆಯ್ಕೆಮಾಡಿ.' : 'Scroll down and tap "Add to Home Screen" (➕).' }}</span>
                                    </div>
                                </div>
                            </template>

                            <!-- Android / Chrome / Edge Guide -->
                            <template x-if="platform !== 'ios'">
                                <div class="space-y-2 text-xs text-stone-600">
                                    <div class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-[10px]">1</span>
                                        <span>{{ $activeLocale === 'kn' ? 'ಬ್ರೌಸರ್ ಮೆನುವಿನಲ್ಲಿರುವ (⋮ Three dots) ಕ್ಲಿಕ್ ಮಾಡಿ.' : 'Click the browser menu (⋮ Three dots) in top-right.' }}</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-[10px]">2</span>
                                        <span>{{ $activeLocale === 'kn' ? '"Install app" ಅಥವಾ "Add to Home screen" (📲) ಆಯ್ಕೆಮಾಡಿ.' : 'Select "Install app" or "Add to Home screen" (📲).' }}</span>
                                    </div>
                                </div>
                            </template>

                            <button 
                                type="button"
                                @click="showInstallModal = false"
                                class="w-full mt-2 py-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 text-xs font-bold transition cursor-pointer text-center"
                            >
                                {{ $activeLocale === 'kn' ? 'ಸರಿ, ಅರ್ಥವಾಯಿತು' : 'Got it' }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- PWA Service Worker Update Available Notification Toast -->
    <div id="pwaUpdateToast" style="display: none;" class="fixed bottom-20 md:bottom-6 left-4 z-50 max-w-sm bg-slate-900 border border-slate-700 text-white rounded-2xl p-3.5 shadow-2xl items-center justify-between gap-3 animate-bounce">
        <div class="flex items-center gap-2.5">
            <span class="text-xl">✨</span>
            <div class="text-xs">
                <div class="font-bold">{{ $activeLocale === 'kn' ? 'ಹೊಸ ಆವೃತ್ತಿ ಲಭ್ಯವಿದೆ!' : 'New Update Available!' }}</div>
                <div class="text-[10px] text-slate-300">{{ $activeLocale === 'kn' ? 'ತಾಜಾ ದರಗಳನ್ನು ಪಡೆಯಲು ಮರುಲೋಡ್ ಮಾಡಿ' : 'Reload to get latest rates & features' }}</div>
            </div>
        </div>
        <button id="pwaReloadBtn" type="button" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shrink-0 cursor-pointer shadow-sm">
            {{ $activeLocale === 'kn' ? 'ಮರುಲೋಡ್' : 'Reload' }}
        </button>
    </div>

    <!-- Service Worker Registration & Live Update Flow -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '{{ asset('/') }}' }).then((registration) => {
                    function promptUpdate() {
                        const toast = document.getElementById('pwaUpdateToast');
                        const reloadBtn = document.getElementById('pwaReloadBtn');
                        if (toast && reloadBtn) {
                            toast.style.display = 'flex';
                            reloadBtn.onclick = () => {
                                if (registration.waiting) {
                                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                                }
                                window.location.reload();
                            };
                        }
                    }

                    if (registration.waiting) {
                        promptUpdate();
                    }

                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        if (newWorker) {
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    promptUpdate();
                                }
                            });
                        }
                    });
                }).catch((err) => {
                    console.warn('Service Worker registration skipped:', err);
                });
            });

            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
        }
    </script>

    <!-- Global Smooth Navigation Feedback & Progress Controller -->
    <script>
        (function() {
            let bar = null;
            let timer = null;

            function getBar() {
                if (!bar) bar = document.getElementById('globalPageProgressBar');
                return bar;
            }

            function startProgress() {
                const b = getBar();
                if (!b) return;
                clearTimeout(timer);
                b.style.transition = 'none';
                b.style.width = '0%';
                b.style.opacity = '1';
                requestAnimationFrame(() => {
                    b.style.transition = 'width 0.35s cubic-bezier(0.1, 0.85, 0.25, 1)';
                    b.style.width = '75%';
                });
            }

            function completeProgress() {
                const b = getBar();
                if (!b) return;
                b.style.transition = 'width 0.15s ease, opacity 0.2s ease 0.1s';
                b.style.width = '100%';
                b.style.opacity = '0';
                timer = setTimeout(() => {
                    b.style.width = '0%';
                }, 350);
            }

            // Intercept internal link taps for instant tactile visual feedback
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href) return;

                // Ignore anchors, external, protocols, new tabs, or keyboard modifiers
                if (href.startsWith('#') ||
                    href.startsWith('javascript:') ||
                    href.startsWith('tel:') ||
                    href.startsWith('mailto:') ||
                    href.startsWith('whatsapp:') ||
                    link.getAttribute('target') === '_blank' ||
                    link.hasAttribute('download') ||
                    e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
                    return;
                }

                try {
                    const targetUrl = new URL(link.href, window.location.origin);
                    if (targetUrl.origin === window.location.origin) {
                        // Skip if already on the exact same page
                        if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search) {
                            return;
                        }
                        // Same origin internal page navigation
                        startProgress();
                    }
                } catch(err) {}
            }, { passive: true });

            // Back/forward cache and unload events
            window.addEventListener('pageshow', completeProgress);
            window.addEventListener('beforeunload', startProgress);

            // Finish any lingering progress upon fresh page load
            if (document.readyState === 'complete') {
                completeProgress();
            } else {
                window.addEventListener('load', completeProgress);
            }
        })();
    </script>
    {{-- Onboarding "How to Use" Tour --}}
    <x-onboarding-tour />

    {{-- Progressive Web App Push Notifications --}}
    <script>
        window.KRUSHI_PWA_CONFIG = {
            vapidKeyUrl: "{{ \Illuminate\Support\Facades\Route::has('api.v1.pwa.vapid-key') ? route('api.v1.pwa.vapid-key') : url('/api/v1/pwa/vapid-key') }}",
            subscribeUrl: "{{ \Illuminate\Support\Facades\Route::has('api.v1.pwa.subscribe') ? route('api.v1.pwa.subscribe') : url('/api/v1/pwa/subscribe') }}",
            unsubscribeUrl: "{{ \Illuminate\Support\Facades\Route::has('api.v1.pwa.unsubscribe') ? route('api.v1.pwa.unsubscribe') : url('/api/v1/pwa/unsubscribe') }}"
        };
    </script>
    <script src="{{ asset('js/pwa-push.js') }}?v={{ file_exists(public_path('js/pwa-push.js')) ? filemtime(public_path('js/pwa-push.js')) : '2' }}" defer></script>

    @livewireScripts
</body>
</html>
