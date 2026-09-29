<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-[#F5EFE6] antialiased notranslate" translate="no">
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
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ \App\Models\SystemSetting::get('pwa_theme_color', '#F5EFE6') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ $pwaIconUrl }}">
    <link rel="icon" type="{{ $logoMime }}" href="{{ $appLogoUrl }}">
    <link rel="shortcut icon" href="{{ $appLogoUrl }}">

    <!-- Google Fonts: Inter / Plus Jakarta Sans & Noto Sans Kannada (Negilu Krushi Alignment) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Kannada:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
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
        .font-kannada {
            font-family: 'Noto Sans Kannada', system-ui, -apple-system, sans-serif;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
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
            z-index: 99999;
            pointer-events: none;
            opacity: 0;
            transition: width 0.35s cubic-bezier(0.1, 0.8, 0.2, 1), opacity 0.2s ease;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex flex-col min-h-screen antialiased pt-16 sm:pt-18 pb-[calc(4.5rem+env(safe-area-inset-bottom,0px))] md:pb-6 bg-[#F5EFE6] notranslate">

    <!-- Top Navigation Progress Indicator (Silky Smooth Instant Page Feedback) -->
    <div id="globalPageProgressBar" aria-hidden="true"></div>

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
                    <div>
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <span class="font-extrabold text-[#1C5A2C] text-base sm:text-lg tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                {{ $activeLocale === 'kn' 
                                    ? \App\Models\SystemSetting::get('application_name_kn', \App\Models\SystemSetting::get('application_name', 'ಕೃಷಿ ಬಾಂಧವ')) 
                                    : \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-stone-500 font-medium font-kannada leading-none mt-0.5">
                            {{ $activeLocale === 'en' 
                                ? \App\Models\SystemSetting::get('navbar_subtitle_en', 'Direct APMC Market Rates & Forecast') 
                                : \App\Models\SystemSetting::get('navbar_subtitle_kn', 'ನೇರ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ರೈತ ಮುನ್ಸೂಚನೆ') }}
                        </p>
                    </div>
                </a>



                <!-- Decluttered Clean Desktop Navigation: Exactly 4 Main Links -->
                <nav class="hidden md:flex items-center gap-1.5 text-xs sm:text-sm font-bold">
                    <a href="{{ route('home') }}" 
                       class="px-4 py-2 rounded-xl transition {{ request()->routeIs('home') || request()->routeIs('farmer.crops.*') ? 'bg-[#E5DDC9] text-stone-900 font-black shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-black/5' }}">
                        {{ $activeLocale === 'en' ? 'Rates' : 'ದರಗಳು' }}
                    </a>
                    <a href="{{ route('farmer.schemes.index') }}" 
                       class="px-4 py-2 rounded-xl transition {{ request()->routeIs('farmer.schemes.*') ? 'bg-[#E5DDC9] text-stone-900 font-black shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-black/5' }}">
                        {{ $activeLocale === 'en' ? 'Schemes' : 'ಯೋಜನೆಗಳು' }}
                    </a>
                    <a href="{{ route('farmer.videos.index') }}" 
                       class="px-4 py-2 rounded-xl transition {{ request()->routeIs('farmer.videos.*') ? 'bg-[#E5DDC9] text-stone-900 font-black shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-black/5' }}">
                        {{ $activeLocale === 'en' ? 'Videos' : 'ವಿಡಿಯೋಗಳು' }}
                    </a>
                    <a href="{{ route('farmer.news.index') }}" 
                       class="px-4 py-2 rounded-xl transition {{ request()->routeIs('farmer.news.*') ? 'bg-[#E5DDC9] text-stone-900 font-black shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-black/5' }}">
                        {{ $activeLocale === 'en' ? 'News' : 'ಸುದ್ದಿಗಳು' }}
                    </a>
                </nav>

                <!-- Right Utility Bar: Location Pill + Language Toggle -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Location Pill for Mobile/Quick access -->
                    <button type="button" 
                            x-data
                            @click="$dispatch('open-location-modal')" 
                            class="hidden sm:flex items-center gap-1.5 bg-[#FAF8F5] border border-[#DDD2BE] rounded-full px-3 py-1.5 text-xs font-bold text-stone-800 transition hover:bg-stone-100 active:scale-95 cursor-pointer shadow-2xs"
                            title="{{ $activeLocale === 'en' ? 'Click to change location' : 'ಸ್ಥಳ ಬದಲಾಯಿಸಲು ಕ್ಲಿಕ್ ಮಾಡಿ' }}">
                        <span class="text-rose-500 text-xs">📍</span>
                        <span class="max-w-[100px] truncate {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                            {{ $activeLocale === 'en' ? ($activeDistrict->name ?? $activeDistrict->name_kn ?? 'Shivamogga') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಶಿವಮೊಗ್ಗ') }}
                        </span>
                    </button>

                    <!-- Interactive Kannada / English Toggle -->
                    <div class="flex items-center bg-white rounded-xl p-1 text-xs font-bold border border-stone-200/90 shadow-2xs">
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
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Flow (Compact mobile horizontal padding to give cards maximum width) -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-3.5 sm:py-5 min-w-0 page-main-container">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Mobile-First 4-Tab Bottom Navigation Bar (Rock-solid Fixed for Mobile Screens & PWA) -->
    <nav id="mobileBottomNav" class="md:hidden fixed bottom-0 left-0 right-0 inset-x-0 w-full z-50 bg-white/95 backdrop-blur-md border-t border-stone-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] pb-[env(safe-area-inset-bottom,0px)]">
        <div class="grid grid-cols-4 h-16 w-full max-w-lg mx-auto px-2">
            <!-- Home -->
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('home') ? 'text-[#1C5A2C] font-extrabold' : 'text-stone-500 hover:text-[#1C5A2C]' }} transition active:scale-95">
                <span class="text-lg">🌾</span>
                <span class="text-[10px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} font-bold leading-none">{{ $activeLocale === 'en' ? 'Home' : 'ಮುಖಪುಟ' }}</span>
            </a>

            <!-- Crops / Rates -->
            <a href="{{ route('farmer.crops.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.crops.*') ? 'text-[#1C5A2C] font-extrabold' : 'text-stone-500 hover:text-[#1C5A2C]' }} transition active:scale-95">
                <span class="text-lg">📊</span>
                <span class="text-[10px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} font-bold leading-none">{{ $activeLocale === 'en' ? 'Rates' : 'ದರಗಳು' }}</span>
            </a>

            <!-- Schemes -->
            <a href="{{ route('farmer.schemes.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.schemes.*') ? 'text-[#1C5A2C] font-extrabold' : 'text-stone-500 hover:text-[#1C5A2C]' }} transition active:scale-95">
                <span class="text-lg">🏛️</span>
                <span class="text-[10px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} font-bold leading-none">{{ $activeLocale === 'en' ? 'Schemes' : 'ಯೋಜನೆಗಳು' }}</span>
            </a>

            <!-- Weather -->
            <a href="{{ route('farmer.weather.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.weather.*') ? 'text-[#1C5A2C] font-extrabold' : 'text-stone-500 hover:text-[#1C5A2C]' }} transition active:scale-95">
                <span class="text-lg">🌤️</span>
                <span class="text-[10px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} font-bold leading-none">{{ $activeLocale === 'en' ? 'Weather' : 'ಹವಾಮಾನ' }}</span>
            </a>
        </div>
    </nav>

    <!-- Desktop Footer -->
    <footer class="hidden md:block mt-auto bg-transparent border-t border-[#E8DFC8] py-8 text-center text-xs text-stone-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex flex-col items-start gap-1 text-left">
                <div class="flex items-center gap-2">
                    @if($hasCustomLogo)
                        <img src="{{ $appLogoUrl }}" alt="Logo" class="w-6 h-6 object-contain rounded-lg border border-[#D9CEB8] bg-white p-0.5 shrink-0">
                    @endif
                    <span class="font-bold text-stone-800">{{ $activeLocale === 'en' ? 'Krushi Baandhava' : 'ಕೃಷಿ ಬಾಂಧವ' }}</span>
                    <span>•</span>
                    <span>{{ $activeLocale === 'en' ? 'Official APMC market rates & agricultural advisory for Karnataka farmers' : 'ಕರ್ನಾಟಕದ ರೈತರಿಗಾಗಿ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ದರಗಳು & ಕೃಷಿ ಮಾಹಿತಿ' }}</span>
                </div>
                <p class="text-[11px] text-stone-400">
                    {{ $activeLocale === 'en' ? 'Mandi rates sourced from Karnataka State APMC (KRAMA), Agmarknet & Cooperative Societies (TSS Sirsi)' : 'ದರಗಳು ಕರ್ನಾಟಕ ರಾಜ್ಯ ಎಪಿಎಂಸಿ (KRAMA), ಅಗ್ಮಾರ್ಕ್‌ನೆಟ್ ಮತ್ತು ಸಹಕಾರಿ ಸಂಘಗಳಿಂದ (TSS Sirsi)' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-semibold text-stone-600">
                <a href="{{ route('farmer.schemes.index') }}" class="hover:text-stone-900">{{ $activeLocale === 'en' ? 'Govt Schemes' : 'ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು' }}</a>
                <a href="{{ route('farmer.news.index') }}" class="hover:text-stone-900">{{ $activeLocale === 'en' ? 'Agri News' : 'ಕೃಷಿ ಸುದ್ದಿ' }}</a>
                <a href="{{ route('farmer.videos.index') }}" class="hover:text-stone-900">{{ $activeLocale === 'en' ? 'Videos' : 'ವಿಡಿಯೋಗಳು' }}</a>
                <a href="{{ route('farmer.articles.index') }}" class="hover:text-stone-900">{{ $activeLocale === 'en' ? 'Guides' : 'ಕೈಪಿಡಿಗಳು' }}</a>
                <a href="{{ route('admin.login') }}" class="text-stone-400 hover:text-stone-900">Admin Portal</a>
            </div>
        </div>
    </footer>

    <!-- Floating Feedback Pill Button (Bottom Right) -->
    <a href="https://whatsapp.com/channel/krushi-baandhava" 
       target="_blank" 
       rel="noopener noreferrer"
       class="fixed bottom-[calc(4.75rem+env(safe-area-inset-bottom,0px))] md:bottom-6 right-4 sm:right-7 z-40 inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#1C5A2C] hover:bg-[#154622] text-white font-bold text-xs shadow-lg transition active:scale-95 cursor-pointer">
        <span class="text-sm">💬</span>
        <span>{{ $activeLocale === 'en' ? 'Feedback' : 'ಪ್ರತಿಕ್ರಿಯೆ' }}</span>
    </a>

    <!-- Location Picker Modal Component -->
    <x-location-modal :all-districts="$allDistricts ?? null" :active-district="$activeDistrict ?? null" />

    <!-- PWA Install Banner Component -->
    <div x-data="{
            showInstallPrompt: false,
            deferredPrompt: null,
            init() {
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    if (!localStorage.getItem('pwa_prompt_dismissed')) {
                        this.showInstallPrompt = true;
                    }
                });
                window.addEventListener('appinstalled', () => {
                    this.showInstallPrompt = false;
                    this.deferredPrompt = null;
                });
                window.addEventListener('open-install-prompt', () => {
                    this.installApp();
                });
            },
            async installApp() {
                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    const { outcome } = await this.deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        this.showInstallPrompt = false;
                    }
                    this.deferredPrompt = null;
                } else {
                    alert('ನಿಮ್ಮ ಬ್ರೌಸರ್ ಮೆನುವಿನಲ್ಲಿ (Three dots / Share) Add to Home screen ಅಥವಾ Install ಆಯ್ಕೆಮಾಡಿ.');
                }
            },
            dismiss() {
                this.showInstallPrompt = false;
                localStorage.setItem('pwa_prompt_dismissed', '1');
            }
        }"
        x-show="showInstallPrompt"
        x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
        class="fixed bottom-[calc(4.75rem+env(safe-area-inset-bottom,0px))] md:bottom-6 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 bg-[#1C5A2C] text-white rounded-2xl p-4 shadow-2xl border border-emerald-700/60 flex items-center justify-between gap-3"
    >
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center p-1 shrink-0 shadow-inner border border-emerald-400/50 overflow-hidden">
                <img src="{{ $appLogoUrl }}" alt="App Icon" class="w-full h-full object-contain">
            </div>
            <div>
                <h4 class="font-bold text-sm text-white flex items-center gap-1.5">
                    <span>Install Krushi Baandhava (ಕೃಷಿ ಬಾಂಧವ ಆ್ಯಪ್)</span>
                    <span class="text-[10px] bg-[#F0C24A] text-[#1C5A2C] font-bold px-1.5 py-0.5 rounded">PWA</span>
                </h4>
                <p class="text-xs text-emerald-100 mt-0.5 leading-snug">
                    ಆ್ಯಪ್ ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಿ - ನೆಟ್‌ವರ್ಕ್ ಇಲ್ಲದಿದ್ದರೂ ದರ ಪರಿಶೀಲಿಸಿ!
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button @click="installApp()" class="px-3.5 py-2 rounded-xl bg-[#F0C24A] hover:bg-amber-300 text-[#1C5A2C] font-black text-xs shadow-md transition transform active:scale-95 cursor-pointer">
                ಇನ್‌ಸ್ಟಾಲ್
            </button>
            <button @click="dismiss()" class="text-emerald-200 hover:text-white p-1 text-sm rounded-lg cursor-pointer" title="Dismiss">
                ✕
            </button>
        </div>
    </div>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch((err) => {
                    console.warn('Service Worker registration skipped:', err);
                });
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
    @livewireScripts
</body>
</html>
