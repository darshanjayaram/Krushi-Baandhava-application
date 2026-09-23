<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Krushi Baandhava' }} — ಕರ್ನಾಟಕ ರೈತರ ಕೃಷಿ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ</title>
    <meta name="description" content="{{ $description ?? 'ಕರ್ನಾಟಕದ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ದರಗಳು, ನಿಖರ ಬೆಲೆ ಮುನ್ಸೂಚನೆ, ಹತ್ತಿರದ ಮಂಡಿಗಳು ಮತ್ತು ಹವಾಮಾನ ಮಾಹಿತಿ.' }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / WhatsApp Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Krushi Baandhava - ಕೃಷಿ ಬಾಂಧವ">
    <meta property="og:title" content="{{ $title ?? 'Krushi Baandhava' }} — ಕರ್ನಾಟಕ ರೈತರ ಕೃಷಿ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ">
    <meta property="og:description" content="{{ $description ?? 'ಕರ್ನಾಟಕದ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ದರಗಳು, ನಿಖರ ಬೆಲೆ ಮುನ್ಸೂಚನೆ, ಹತ್ತಿರದ ಮಂಡಿಗಳು ಮತ್ತು ಹವಾಮಾನ ಮಾಹಿತಿ.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('/icons/icon-512.svg') }}">
    <meta property="og:locale" content="kn_IN">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Krushi Baandhava' }} — ಕರ್ನಾಟಕ ರೈತರ ಕೃಷಿ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ">
    <meta name="twitter:description" content="{{ $description ?? 'ಕರ್ನಾಟಕದ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ದರಗಳು, ನಿಖರ ಬೆಲೆ ಮುನ್ಸೂಚನೆ, ಹತ್ತಿರದ ಮಂಡಿಗಳು ಮತ್ತು ಹವಾಮಾನ ಮಾಹಿತಿ.' }}">
    <meta name="twitter:image" content="{{ asset('/icons/icon-512.svg') }}">

    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#047857">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Krushi Baandhava">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <link rel="icon" type="image/svg+xml" href="/icons/icon-192.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Tiro+Kannada:ital@0;1&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .font-kannada {
            font-family: 'Tiro Kannada', serif;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
<body class="flex flex-col min-h-full text-slate-800 selection:bg-emerald-500 selection:text-white pb-20 md:pb-6">

    <!-- Real-time Connectivity Status Indicator -->
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
            <span>Offline Mode: Showing cached rates. / ಆಫ್‌ಲೈನ್ ಮೋಡ್: ಉಳಿಸಲಾದ ದರಗಳು ಲಭ್ಯವಿವೆ.</span>
        </div>
        <div x-show="showReconnected" style="display: none;"
             class="bg-emerald-600 text-white px-4 py-1.5 text-center text-xs font-bold shadow-sm flex items-center justify-center gap-2">
            <span>✓</span>
            <span>Back Online! Live connectivity restored. / ಆನ್‌ಲೈನ್‌ಗೆ ಮರಳಿದೆ!</span>
        </div>
    </div>

    <!-- Top Farmer Header -->
    <header class="sticky top-0 z-40 bg-emerald-800 text-white shadow-md border-b border-emerald-900/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Branding -->
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-300 rounded-lg p-1">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-inner border border-emerald-400/30">
                        <img src="/icons/icon-192.svg" alt="Krushi Baandhava" class="w-7 h-7">
                    </div>
                    <div>
                        <div class="text-lg font-extrabold tracking-tight leading-none text-white flex items-center gap-1.5">
                            <span>Krushi Baandhava</span>
                            <span class="text-[10px] font-semibold tracking-wider uppercase px-1.5 py-0.5 rounded bg-amber-400 text-emerald-950 font-sans">Karnataka</span>
                        </div>
                        <div class="text-xs text-emerald-200/90 font-medium font-kannada leading-tight">ಕೃಷಿ ಬಾಂಧವ • ರೈತರ ಮಿತ್ರ</div>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-1 font-medium text-xs">
                    <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('home') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition">
                        Home (ಮುಖಪುಟ)
                    </a>
                    <a href="{{ route('farmer.crops.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.crops.*') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition">
                        Crops (ಬೆಳೆಗಳು)
                    </a>
                    <a href="{{ route('farmer.markets.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.markets.index') || request()->routeIs('farmer.markets.show') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition">
                        Mandis (ಮಂಡಿಗಳು)
                    </a>
                    <a href="{{ route('farmer.markets.nearby') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.markets.nearby') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition flex items-center gap-1">
                        <span>📍</span>
                        <span>Nearby (ಹತ್ತಿರದ ಮಂಡಿಗಳು)</span>
                    </a>
                    <a href="{{ route('farmer.decision.where-to-sell') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.decision.where-to-sell') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition flex items-center gap-1">
                        <span>⚖️</span>
                        <span>Where to Sell (ಎಲ್ಲಿ ಮಾರಾಟ?)</span>
                    </a>
                    <a href="{{ route('farmer.weather.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.weather.*') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition flex items-center gap-1">
                        <span>🌤️</span>
                        <span>Weather (ಹವಾಮಾನ)</span>
                    </a>
                    <a href="{{ route('farmer.schemes.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.schemes.*') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition flex items-center gap-1">
                        <span>🏛️</span>
                        <span>Schemes (ಯೋಜನೆಗಳು)</span>
                    </a>
                    <a href="{{ route('farmer.news.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.news.*') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition flex items-center gap-1">
                        <span>📰</span>
                        <span>News (ಸುದ್ದಿ)</span>
                    </a>
                    <a href="{{ route('farmer.videos.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.videos.*') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition flex items-center gap-1">
                        <span>🎬</span>
                        <span>Videos (ವಿಡಿಯೋ)</span>
                    </a>
                    <a href="{{ route('farmer.articles.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('farmer.articles.*') ? 'bg-white/20 text-white font-bold' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }} transition flex items-center gap-1">
                        <span>📚</span>
                        <span>Guides (ಕೈಪಿಡಿ)</span>
                    </a>
                </nav>

                <!-- Location & Language Quick Switcher -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Current District Indicator -->
                    <div class="flex items-center gap-1.5 bg-emerald-900/60 border border-emerald-600/40 rounded-full px-3 py-1.5 text-xs font-semibold text-emerald-100">
                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        <span class="truncate max-w-[90px] sm:max-w-none">{{ $activeDistrict->name ?? 'Shivamogga' }}</span>
                    </div>

                    <!-- Admin Link if logged in or quick access -->
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold bg-white/10 hover:bg-white/20 text-white rounded-lg px-2.5 py-1.5 border border-white/15 transition">
                                <span>Admin</span>
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Flow -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Mobile-First Thumb Zone Bottom Navigation Bar (Fixed for Mobile Screens) -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
        <div class="grid grid-cols-6 h-16 max-w-lg mx-auto px-1">
            <!-- Home -->
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('home') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-emerald-600' }} transition">
                <svg class="w-5 h-5 {{ request()->routeIs('home') ? 'stroke-[2.5]' : 'stroke-2' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px] font-medium leading-none">Home</span>
            </a>

            <!-- Crops -->
            <a href="{{ route('farmer.crops.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.crops.*') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-emerald-600' }} transition">
                <svg class="w-5 h-5 {{ request()->routeIs('farmer.crops.*') ? 'stroke-[2.5]' : 'stroke-2' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span class="text-[10px] font-medium leading-none">Crops</span>
            </a>

            <!-- Mandis -->
            <a href="{{ route('farmer.markets.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.markets.index') || request()->routeIs('farmer.markets.show') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-emerald-600' }} transition">
                <svg class="w-5 h-5 {{ request()->routeIs('farmer.markets.index') ? 'stroke-[2.5]' : 'stroke-2' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="text-[10px] font-medium leading-none">Mandis</span>
            </a>

            <!-- Nearby Mandis -->
            <a href="{{ route('farmer.markets.nearby') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.markets.nearby') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-emerald-600' }} transition">
                <svg class="w-5 h-5 {{ request()->routeIs('farmer.markets.nearby') ? 'stroke-[2.5]' : 'stroke-2' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-[10px] font-medium leading-none">Nearby</span>
            </a>

            <!-- Weather -->
            <a href="{{ route('farmer.weather.index') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.weather.*') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-emerald-600' }} transition">
                <svg class="w-5 h-5 {{ request()->routeIs('farmer.weather.*') ? 'stroke-[2.5]' : 'stroke-2' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                </svg>
                <span class="text-[10px] font-medium leading-none">Weather</span>
            </a>

            <!-- Where to Sell -->
            <a href="{{ route('farmer.decision.where-to-sell') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('farmer.decision.where-to-sell') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-emerald-600' }} transition">
                <span class="text-lg leading-none">⚖️</span>
                <span class="text-[10px] font-medium leading-none">Sell</span>
            </a>
        </div>
    </nav>

    <!-- Desktop Footer -->
    <footer class="hidden md:block mt-auto bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-700">Krushi Baandhava</span>
                <span>•</span>
                <span>Government Mandi Rates & Agricultural Intelligence</span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('farmer.schemes.index') }}" class="hover:text-emerald-700">Govt Schemes</a>
                <a href="{{ route('farmer.news.index') }}" class="hover:text-emerald-700">Agri News</a>
                <a href="{{ route('farmer.videos.index') }}" class="hover:text-emerald-700">Videos</a>
                <a href="{{ route('farmer.articles.index') }}" class="hover:text-emerald-700">Guides</a>
                <a href="{{ route('admin.login') }}" class="hover:text-emerald-700 font-medium">Admin Portal</a>
            </div>
        </div>
    </footer>

    <!-- PWA Install Banner -->
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
            },
            async installApp() {
                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    const { outcome } = await this.deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        this.showInstallPrompt = false;
                    }
                    this.deferredPrompt = null;
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
        class="fixed bottom-16 md:bottom-6 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 bg-emerald-900 text-white rounded-2xl p-4 shadow-2xl border border-emerald-700/60 flex items-center justify-between gap-3"
    >
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center p-2 shrink-0 shadow-inner">
                <img src="/icons/icon-192.svg" alt="App Icon" class="w-8 h-8">
            </div>
            <div>
                <h4 class="font-bold text-sm text-white flex items-center gap-1.5">
                    <span>Install Krushi Baandhava</span>
                    <span class="text-[10px] bg-amber-400 text-emerald-950 font-bold px-1.5 py-0.5 rounded">PWA</span>
                </h4>
                <p class="text-xs text-emerald-200 mt-0.5 leading-snug">
                    ಆ್ಯಪ್ ಇನ್‌ಸ್ಟಾಲ್ ಮಾಡಿ - ಇಂಟರ್ನೆಟ್ ಇಲ್ಲದಿದ್ದರೂ ದರಗಳನ್ನು ಪರಿಶೀಲಿಸಿ!
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button @click="installApp()" class="px-3.5 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-emerald-950 font-bold text-xs shadow-md transition transform active:scale-95">
                Install
            </button>
            <button @click="dismiss()" class="text-emerald-300 hover:text-white p-1 text-sm rounded-lg" title="Dismiss">
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
    @livewireScripts
</body>
</html>
