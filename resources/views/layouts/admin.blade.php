<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Dashboard' }} — Krushi Baandhava Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full bg-slate-950 text-slate-100 selection:bg-emerald-500 selection:text-white" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"
         @click="sidebarOpen = false"
         style="display: none;"></div>

    <!-- Admin Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-300 transform -translate-x-full lg:translate-x-0"
           :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
        
        <!-- Sidebar Header / Brand -->
        <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-lg border border-emerald-400/30">
                    <img src="{{ \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg') }}" alt="Krushi Baandhava" class="w-6 h-6 object-contain">
                </div>
                <div>
                    <span class="font-extrabold text-white text-base tracking-tight leading-none block">Krushi Admin</span>
                    <span class="text-[10px] text-emerald-400 font-semibold uppercase tracking-wider block">Karnataka Core</span>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-6 text-sm">
            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Operations</div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Data & Mandi Management</div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('admin.prices.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.prices.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Daily Market Prices</span>
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
                </div>
            </div>

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
                </div>
            </div>

            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">System & Governance</div>
                <div class="mt-2 space-y-1">
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
                    <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ request()->routeIs('admin.audit-logs.*') ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                        <svg class="w-5 h-5 shrink-0 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Audit Trail</span>
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
        <header class="h-16 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-lg font-bold text-white tracking-tight">{{ $header ?? 'Dashboard' }}</h1>
            </div>

            <!-- User Menu -->
            <div class="flex items-center gap-3">
                @auth
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-emerald-700 border border-emerald-500/40 flex items-center justify-center font-bold text-xs text-white uppercase">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-semibold text-white leading-none">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-emerald-400 font-mono mt-0.5 uppercase">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}" class="ml-2">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition" title="Sign Out">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                @endauth
            </div>
        </header>

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-600/40 text-emerald-200 text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="p-4 rounded-xl bg-rose-950/80 border border-rose-600/40 text-rose-200 text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 w-full">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    @livewireScripts
</body>
</html>
