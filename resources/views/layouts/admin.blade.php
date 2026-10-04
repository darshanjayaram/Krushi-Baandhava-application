<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Dashboard' }} — Krushi Baandhava Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Kannada:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @php
        $adminLogo = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
        $adminLogoExt = strtolower(pathinfo($adminLogo, PATHINFO_EXTENSION));
        $adminLogoMime = match($adminLogoExt) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/svg+xml'
        };
        $adminLogoVersion = file_exists(public_path(ltrim($adminLogo, '/'))) ? filemtime(public_path(ltrim($adminLogo, '/'))) : '1';
        $adminLogoUrl = asset($adminLogo) . '?v=' . $adminLogoVersion;
    @endphp
    <link rel="icon" type="{{ $adminLogoMime }}" href="{{ $adminLogoUrl }}">
    <link rel="shortcut icon" href="{{ $adminLogoUrl }}">

    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        html[lang="kn"],
        html[lang="kn"] body,
        html[lang="kn"] *,
        .font-kannada,
        [lang="kn"],
        [lang="kn"] * {
            font-family: 'Noto Sans Kannada', 'Manrope', sans-serif !important;
        }

        /* Prevent auto-zooming on focus in iOS mobile safari */
        @media screen and (max-width: 768px) {
            input:not([type="checkbox"]):not([type="radio"]), select, textarea {
                font-size: 16px !important;
            }
        }

        /* Momentum touch scrolling for mobile tables and lists */
        .overflow-x-auto, .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
        }

        /* Safe area inset support for mobile screens with gesture bars */
        .safe-area-bottom {
            padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
        }

        /* ============================================================== */
        /* MODERN THIN SCROLLBAR SYSTEM (Industry Standard UX)             */
        /* ============================================================== */
        /* Chromium, Chrome, Edge, Safari, Opera */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #020617;
            border-radius: 9999px;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
            border: 1px solid rgba(15, 23, 42, 0.5);
            transition: background 0.2s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #10b981; /* Emerald-500 hover accent */
        }

        ::-webkit-scrollbar-corner {
            background: transparent;
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

        /* Firefox Only */
        @supports not selector(::-webkit-scrollbar) {
            * {
                scrollbar-width: thin;
                scrollbar-color: #334155 #020617;
            }
        }

        /* Dedicated Modal & Content Thin Scrollbars */
        .modal-thin-scrollbar::-webkit-scrollbar,
        .custom-scrollbar::-webkit-scrollbar,
        .thin-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .modal-thin-scrollbar::-webkit-scrollbar-track,
        .custom-scrollbar::-webkit-scrollbar-track,
        .thin-scrollbar::-webkit-scrollbar-track {
            background: #020617;
            border-radius: 9999px;
        }

        .modal-thin-scrollbar::-webkit-scrollbar-thumb,
        .custom-scrollbar::-webkit-scrollbar-thumb,
        .thin-scrollbar::-webkit-scrollbar-thumb {
            background: #059669; /* Emerald-600 */
            border-radius: 9999px;
            border: 1px solid #064e3b;
        }

        .modal-thin-scrollbar::-webkit-scrollbar-thumb:hover,
        .custom-scrollbar::-webkit-scrollbar-thumb:hover,
        .thin-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #10b981; /* Emerald-500 */
        }

        .modal-thin-scrollbar,
        .custom-scrollbar,
        .thin-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #059669 #020617;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full bg-slate-950 text-slate-100 selection:bg-emerald-500 selection:text-white" 
      x-data="{ sidebarOpen: false }"
      x-effect="if (sidebarOpen) { document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/75 backdrop-blur-sm lg:hidden"
         @click="sidebarOpen = false"
         style="display: none;"></div>

    <!-- Admin Sidebar (Responsive Drawer on Mobile, Fixed Sidebar on Large Screens) -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-300 ease-in-out transform -translate-x-full lg:translate-x-0 shadow-2xl lg:shadow-none"
           :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
        
        <!-- Sidebar Header / Brand -->
        <div class="h-16 px-4 sm:px-6 flex items-center justify-between border-b border-slate-800 shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center shadow-lg border border-emerald-400/30 p-1 shrink-0 overflow-hidden">
                    <img src="{{ $adminLogoUrl }}" alt="Krushi Baandhava" class="w-full h-full object-contain">
                </div>
                <div>
                    <span class="font-extrabold text-white text-base tracking-tight leading-none block">Krushi Admin</span>
                    <span class="text-[10px] text-emerald-400 font-semibold uppercase tracking-wider block">Karnataka Core</span>
                </div>
            </a>
            <button @click="sidebarOpen = false" 
                    class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer active:scale-95"
                    aria-label="Close sidebar">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto overscroll-contain px-3 sm:px-4 py-4 space-y-6 text-sm">
            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Operations</div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.dashboard') }}" 
                       @click="sidebarOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold min-h-[42px] {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition active:scale-[0.98]">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessModule('data') || auth()->user()->canAccessModule('prices'))
            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Data & Mandi Management</div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.prices.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.prices.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Daily Market Prices</span>
                    </a>
                    <a href="{{ route('admin.price-freshness.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.price-freshness.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <span class="text-base leading-none">⏳</span>
                        <span>Price Freshness Rules</span>
                    </a>
                    <a href="{{ route('admin.deployment-hub.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.deployment-hub.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Deployment & APMC Hub</span>
                    </a>
                    <a href="{{ route('admin.datasources.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.datasources.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h4" />
                        </svg>
                        <span>Data Sources & APIs</span>
                    </a>
                    <a href="{{ route('admin.crops.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.crops.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Crops & Commodities</span>
                    </a>
                    <a href="{{ route('admin.markets.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.markets.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>APMC Mandis</span>
                    </a>
                    <a href="{{ route('admin.districts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.districts.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Districts & Taluks</span>
                    </a>
                    <a href="{{ route('admin.data-quality.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.data-quality.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Data Quality & Feeds</span>
                    </a>
                    <a href="{{ route('admin.unresolved-mappings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.unresolved-mappings.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span>Alias Resolvers</span>
                    </a>
                    <a href="{{ route('admin.sync-logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.sync-logs.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Sync Logs History</span>
                    </a>
                    <a href="{{ route('admin.weather.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.weather.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <span class="text-base leading-none">🌤️</span>
                        <span>Weather Engine</span>
                    </a>
                </div>
            </div>
            @endif

            @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessModule('support'))
            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Helpdesk & Community</div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.feedback.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.feedback.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <div class="flex items-center gap-3">
                            <span class="text-base leading-none">🎙️</span>
                            <span>Feedback & Issues</span>
                        </div>
                        @php
                            $unreadFeedbackCount = \App\Models\FarmerFeedback::where('status', 'new')->count();
                        @endphp
                        @if($unreadFeedbackCount > 0)
                            <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-rose-600 text-white shadow-sm">
                                {{ $unreadFeedbackCount }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('admin.notifications.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.notifications.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <div class="flex items-center gap-3">
                            <span class="text-base leading-none">📲</span>
                            <span>PWA Push Alerts</span>
                        </div>
                        @php
                            $pwaSubCount = 0;
                            try {
                                if (\Illuminate\Support\Facades\Schema::hasTable('push_subscriptions')) {
                                    $pwaSubCount = \App\Models\PushSubscription::where('is_active', true)->count();
                                }
                            } catch (\Throwable $e) {
                                $pwaSubCount = 0;
                            }
                        @endphp
                        @if($pwaSubCount > 0)
                            <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-600 text-white shadow-sm">
                                {{ $pwaSubCount }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>
            @endif

            @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccessModule('content'))
            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Content Management (CMS)</div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.schemes.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.schemes.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <span class="text-base leading-none">🏛️</span>
                        <span>Govt Schemes</span>
                    </a>
                    <a href="{{ route('admin.news.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.news.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <span class="text-base leading-none">📰</span>
                        <span>Agri News & Alerts</span>
                    </a>
                    <a href="{{ route('admin.videos.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.videos.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <span class="text-base leading-none">▶️</span>
                        <span>Educational Videos</span>
                    </a>
                    <a href="{{ route('admin.articles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.articles.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <span class="text-base leading-none">📖</span>
                        <span>Farming Guides</span>
                    </a>
                    <a href="{{ route('admin.navbar.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.navbar.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                        <span>Navbar & Menus CMS</span>
                    </a>
                    <a href="{{ route('admin.footer.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.footer.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Footer CMS</span>
                    </a>
                </div>
            </div>
            @endif

            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">System & Governance</div>
                <div class="mt-2 space-y-1">
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.users.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            <svg class="w-5 h-5 shrink-0 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Staff & Roles</span>
                        </a>
                        <a href="{{ route('admin.feature-flags.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.feature-flags.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                            <span>Feature Flags</span>
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.settings.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            <svg class="w-5 h-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>System Settings</span>
                        </a>
                    @endif
                    <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.audit-logs.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Audit Trail</span>
                    </a>
                    <a href="{{ route('admin.notes.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.notes.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Notes & Guidelines</span>
                    </a>
                    <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.profile.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>My Profile & Settings</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Sidebar Footer / Farmer App Link -->
        <div class="p-4 border-t border-slate-800">
            <a href="{{ route('home') }}" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-800/80 hover:bg-slate-800 text-xs font-semibold text-slate-300 hover:text-white transition">
                <span>View Farmer PWA</span>
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </aside>

    <!-- Main Container -->
    <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
        
        <!-- Top Navbar -->
        <header class="h-16 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 sticky top-0 z-30 flex items-center justify-between px-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <button @click="sidebarOpen = true" 
                        class="lg:hidden p-2 rounded-xl text-slate-300 hover:text-white bg-slate-800/70 hover:bg-slate-800 border border-slate-700/60 transition cursor-pointer shrink-0 active:scale-95"
                        aria-label="Open sidebar navigation">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-sm sm:text-lg font-bold text-white tracking-tight truncate max-w-[180px] xs:max-w-[260px] sm:max-w-none">{{ $header ?? 'Dashboard' }}</h1>
            </div>

            <!-- User Profile Dropdown Menu -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
                @auth
                    <div class="relative">
                        <!-- User Pill Button -->
                        <button type="button" 
                                @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-2 sm:gap-2.5 p-1 sm:p-1.5 rounded-xl hover:bg-slate-800 transition cursor-pointer text-left focus:outline-none border border-transparent hover:border-slate-700">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 border border-emerald-400/40 flex items-center justify-center font-bold text-xs text-white uppercase shadow-sm shrink-0">
                                {{ substr(auth()->user()->name, 0, 2) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-xs font-semibold text-white leading-none flex items-center gap-1.5">
                                    <span>{{ auth()->user()->name }}</span>
                                    <svg class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': userMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <div class="text-[10px] text-emerald-400 font-mono mt-0.5 uppercase">{{ auth()->user()->getRoleTitle() }}</div>
                            </div>
                        </button>

                        <!-- Dropdown Panel -->
                        <div x-show="userMenuOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-64 max-w-[calc(100vw-24px)] rounded-2xl bg-slate-900 border border-slate-700 shadow-2xl z-50 overflow-hidden text-white py-1.5"
                             style="display: none;">
                            
                            <!-- Header Info -->
                            <div class="px-4 py-3 border-b border-slate-800 bg-slate-950/40">
                                <div class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</div>
                                <div class="mt-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ auth()->user()->getRoleBadgeClass() }}">
                                        {{ auth()->user()->getRoleTitle() }}
                                    </span>
                                </div>
                            </div>

                            <!-- Menu Options -->
                            <div class="py-1 text-xs">
                                <a href="{{ route('admin.profile.edit') }}" 
                                   class="flex items-center gap-2.5 px-4 py-2.5 text-slate-300 hover:text-white hover:bg-slate-800/80 transition min-h-[40px]">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>My Profile & Preferences</span>
                                </a>

                                <a href="{{ route('admin.profile.edit') }}#password" 
                                   class="flex items-center gap-2.5 px-4 py-2.5 text-slate-300 hover:text-white hover:bg-slate-800/80 transition min-h-[40px]">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>Change Password</span>
                                </a>

                                @if(auth()->user()->isSuperAdmin())
                                    <a href="{{ route('admin.users.index') }}" 
                                       class="flex items-center gap-2.5 px-4 py-2.5 text-slate-300 hover:text-white hover:bg-slate-800/80 transition min-h-[40px]">
                                        <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span>Staff & Access Control</span>
                                    </a>
                                @endif
                            </div>

                            <!-- Logout Divider & Action -->
                            <div class="border-t border-slate-800 pt-1">
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-rose-300 hover:text-rose-200 hover:bg-rose-950/40 transition cursor-pointer text-left min-h-[40px]">
                                        <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>
        </header>

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="w-full px-3.5 sm:px-6 lg:px-8 mt-3 sm:mt-4">
                <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-950/80 border border-emerald-600/40 text-emerald-200 text-xs sm:text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="w-full px-3.5 sm:px-6 lg:px-8 mt-3 sm:mt-4">
                <div class="p-3.5 sm:p-4 rounded-xl bg-rose-950/80 border border-rose-600/40 text-rose-200 text-xs sm:text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Page Content (Responsive padding with extra bottom space on mobile for dock) -->
        <main class="flex-1 p-3 sm:p-5 lg:p-8 w-full pb-24 lg:pb-8">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <!-- Mobile Bottom Quick Navigation Bar (Sticky Dock for Fast 1-Thumb Switching) -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-slate-900/95 backdrop-blur-xl border-t border-slate-800/90 px-2 py-1.5 flex items-center justify-around shadow-2xl safe-area-bottom">
        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}" 
           class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition text-center min-w-[56px] {{ request()->routeIs('admin.dashboard') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span class="text-[10px] mt-1 leading-none font-medium">Home</span>
        </a>

        <!-- Prices -->
        <a href="{{ route('admin.prices.index') }}" 
           class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition text-center min-w-[56px] {{ request()->routeIs('admin.prices.*') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-[10px] mt-1 leading-none font-medium">Prices</span>
        </a>

        <!-- APMC Mandis -->
        <a href="{{ route('admin.markets.index') }}" 
           class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition text-center min-w-[56px] {{ request()->routeIs('admin.markets.*') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="text-[10px] mt-1 leading-none font-medium">Mandis</span>
        </a>

        <!-- Crops -->
        <a href="{{ route('admin.crops.index') }}" 
           class="flex flex-col items-center justify-center py-1 px-2 rounded-xl transition text-center min-w-[56px] {{ request()->routeIs('admin.crops.*') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span class="text-[10px] mt-1 leading-none font-medium">Crops</span>
        </a>

        <!-- More Menu (Opens Drawer) -->
        <button type="button" 
                @click="sidebarOpen = true" 
                class="flex flex-col items-center justify-center py-1 px-2 rounded-xl text-slate-400 hover:text-white transition text-center min-w-[56px] cursor-pointer">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span class="text-[10px] mt-1 leading-none font-medium">More</span>
        </button>
    </nav>

    @livewireScripts
</body>
</html>
