@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{
    currentTab: '{{ $activeTab }}',
    searchQuery: '',
    runningAction: null,
    actionToast: null,
    async runSystemAction(url, actionKey, confirmMsg = null) {
        if (confirmMsg && !confirm(confirmMsg)) return;
        if (this.runningAction) return;
        this.runningAction = actionKey;
        this.actionToast = null;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tab: this.currentTab })
            });
            const data = await res.json();
            if (data.ok) {
                this.actionToast = { type: 'success', message: data.message };
            } else {
                this.actionToast = { type: 'error', message: data.message || 'Operation failed.' };
            }
        } catch (err) {
            this.actionToast = { type: 'error', message: 'Execution error: ' + err.message };
        } finally {
            this.runningAction = null;
            setTimeout(() => { if (this.actionToast) this.actionToast = null; }, 6000);
        }
    }
}">

    <!-- Header & Quick Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span class="text-emerald-400">⚙️</span>
                <span>System Configuration Settings — Admin Control Center</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Configure platform identity, Progressive Web App (PWA), price forecasting, weather services, and low-level system operations.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
                <input type="text" x-model="searchQuery" placeholder="Filter settings..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
            </div>
            <span class="hidden sm:inline-block px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-[11px] font-semibold text-emerald-400 font-mono shrink-0">
                • Realtime Sync
            </span>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- IMMEDIATE SYSTEM OPERATIONS (Pixel-Perfect from Screenshot)     -->
    <!-- ============================================================== -->
    <div class="p-6 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl space-y-4">
        <!-- Header -->
        <div class="flex items-center gap-3.5 border-b border-slate-800/80 pb-4">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-rose-950/60 to-purple-950/40 border border-rose-500/30 flex items-center justify-center text-rose-400 font-mono text-sm font-black shadow-inner shrink-0">
                &gt;_
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-bold text-white tracking-tight">Immediate System Operations</h3>
                <p class="text-xs text-slate-400 mt-0.5">Execute low-level system actions, framework cache clearing, and database updates.</p>
            </div>
        </div>

        <!-- Async Action Notification Toast -->
        <div x-show="actionToast" 
             x-cloak 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="p-4 rounded-2xl flex items-center justify-between gap-3 text-xs font-bold border shadow-lg"
             :class="actionToast?.type === 'success' ? 'bg-emerald-950/90 border-emerald-500/40 text-emerald-200' : 'bg-rose-950/90 border-rose-500/40 text-rose-200'">
            <div class="flex items-center gap-2.5">
                <span x-text="actionToast?.type === 'success' ? '✅' : '⚠️'" class="text-base"></span>
                <span x-text="actionToast?.message"></span>
            </div>
            <button type="button" @click="actionToast = null" class="text-slate-400 hover:text-white font-mono text-sm cursor-pointer">&times;</button>
        </div>

        <!-- 3-Column Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
            <!-- Card 1: Application Caches -->
            <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-col justify-between space-y-4 shadow-sm hover:border-slate-700/80 transition">
                <div>
                    <div class="flex items-center gap-2 text-white font-bold text-sm">
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Clear Caches</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Clears view cache, compiled routes, config cache, and application memory stores.
                    </p>
                </div>
                <div>
                    <button type="button" 
                            @click="runSystemAction('{{ route('admin.settings.clear-cache') }}', 'clear_cache')"
                            :disabled="runningAction !== null"
                            class="w-full py-2.5 px-4 bg-slate-800/90 hover:bg-slate-700/90 disabled:opacity-50 text-white font-bold text-xs rounded-xl border border-slate-700/80 transition flex items-center justify-center gap-2 cursor-pointer shadow-xs active:scale-[0.99]">
                        <template x-if="runningAction === 'clear_cache'">
                            <span class="w-3.5 h-3.5 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span>
                        </template>
                        <template x-if="runningAction !== 'clear_cache'">
                            <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </template>
                        <span x-text="runningAction === 'clear_cache' ? 'Clearing Caches...' : 'Clear All Caches'"></span>
                    </button>
                </div>
            </div>

            <!-- Card 2: Production Speed Optimization -->
            <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-col justify-between space-y-4 shadow-sm hover:border-slate-700/80 transition">
                <div>
                    <div class="flex items-center gap-2 text-white font-bold text-sm">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Production Speed</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Pre-compiles routes, configuration, and views into cached files for fastest page loads.
                    </p>
                </div>
                <div>
                    <button type="button" 
                            @click="runSystemAction('{{ route('admin.settings.optimize') }}', 'optimize')"
                            :disabled="runningAction !== null"
                            class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-black text-xs rounded-xl shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]">
                        <template x-if="runningAction === 'optimize'">
                            <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        </template>
                        <template x-if="runningAction !== 'optimize'">
                            <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </template>
                        <span x-text="runningAction === 'optimize' ? 'Compiling for Production...' : 'Optimize for Production'"></span>
                    </button>
                </div>
            </div>

            <!-- Card 3: Database Schema & Migrations -->
            <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-col justify-between space-y-4 shadow-sm hover:border-slate-700/80 transition">
                <div>
                    <div class="flex items-center gap-2 text-white font-bold text-sm">
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="8" ry="3"></ellipse>
                            <path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5"></path>
                            <path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"></path>
                        </svg>
                        <span>Database Schema</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Executes pending database migrations and seeds newly introduced tables/plans safely.
                    </p>
                </div>
                <div>
                    <button type="button" 
                            @click="runSystemAction('{{ route('admin.settings.update-database') }}', 'update_database', 'Execute pending database migrations and update master seeds?')"
                            :disabled="runningAction !== null"
                            class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-slate-950 font-black text-xs rounded-xl shadow-md shadow-amber-500/20 transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]">
                        <template x-if="runningAction === 'update_database'">
                            <span class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                        </template>
                        <template x-if="runningAction !== 'update_database'">
                            <svg class="w-4 h-4 text-slate-950 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <ellipse cx="12" cy="5" rx="8" ry="3"></ellipse>
                                <path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5"></path>
                                <path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"></path>
                            </svg>
                        </template>
                        <span x-text="runningAction === 'update_database' ? 'Migrating Database...' : 'Update Database'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Modern Enterprise Row) -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800 no-scrollbar">
        @php
            $tabMeta = [
                'general' => ['name' => 'General & Platform Branding', 'icon' => '🏛️'],
                'pwa' => ['name' => 'Mobile App & PWA', 'icon' => '📱'],
                'weather' => ['name' => 'Weather Services', 'icon' => '🌤️'],
                'maps' => ['name' => 'Interactive Maps & Route Tiles', 'icon' => '🗺️'],
                'data_sources' => ['name' => 'Data Sync Feeds', 'icon' => '🔄'],
                'forecasting' => ['name' => 'Price Forecasting', 'icon' => '📈'],
                'best_months_to_sell' => ['name' => 'Best Months to Sell', 'icon' => '🗓️'],
                'performance' => ['name' => 'Performance & Cache', 'icon' => '⚡'],
                'maintenance' => ['name' => 'Maintenance & Tools', 'icon' => '🛠️'],
                'localization' => ['name' => 'Regional & Language', 'icon' => '📍'],
            ];
        @endphp

        @foreach($groupedSettings as $groupName => $settings)
            @php
                $meta = $tabMeta[$groupName] ?? ['name' => ucfirst($groupName), 'icon' => '📁'];
            @endphp
            <button type="button" @click="currentTab = '{{ $groupName }}'"
                    :class="currentTab === '{{ $groupName }}' ? 'bg-amber-500 text-slate-950 font-black shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 font-medium'"
                    class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 shrink-0 border border-slate-800 transition">
                <span>{{ $meta['icon'] }}</span>
                <span>{{ $meta['name'] }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-mono font-bold"
                      :class="currentTab === '{{ $groupName }}' ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-950 text-slate-400'">
                    {{ $settings->count() }}
                </span>
            </button>
        @endforeach
    </div>

    <!-- Hidden standalone form for Batch Forecasting Trigger -->
    <form id="trigger-forecast-form" action="{{ route('admin.settings.trigger-forecasting') }}" method="POST" class="hidden">
        @csrf
    </form>

    <!-- Settings Forms per Group -->
    @foreach($groupedSettings as $groupName => $settings)
        <div x-show="currentTab === '{{ $groupName }}'" style="display: none;" class="space-y-6">

            <!-- ============================================================== -->
            <!-- TAB-SPECIFIC FEATURE CARDS                                     -->
            <!-- ============================================================== -->

            @if($groupName === 'general')
                <!-- APPLICATION BRANDING & LOGO SECTION -->
                @php
                    $currentLogo = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
                    $isCustomLogo = $currentLogo !== '/icons/icon-192.svg';
                @endphp
                <div class="p-6 rounded-3xl bg-gradient-to-br from-slate-950/90 to-slate-900 border border-emerald-500/30 shadow-lg relative overflow-hidden"
                     x-data="{ logoPreview: '{{ $currentLogo }}' }">
                    <div class="absolute -top-16 -right-16 w-44 h-44 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-2xl bg-slate-900 border-2 border-emerald-500/40 flex items-center justify-center p-2.5 shadow-xl relative overflow-hidden shrink-0">
                                <img :src="logoPreview" alt="App Logo" class="max-w-full max-h-full object-contain">
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Application Identity & Logo</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Propagates to Farmer PWA header, Admin Portal sidebar, Login Screen, and Favicons.</p>
                                <div class="text-[11px] text-emerald-400 font-mono mt-1">Recommended: 192×192 or 512×512 PNG, SVG, or WebP (&le; 2MB)</div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <label class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md cursor-pointer transition flex items-center gap-2">
                                <span>📤</span>
                                <span>Upload New Logo</span>
                                <input type="file" form="settings-form-{{ $groupName }}" name="app_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden"
                                       @change="const file = $event.target.files[0]; if(file) { const reader = new FileReader(); reader.onload = (e) => logoPreview = e.target.result; reader.readAsDataURL(file); }">
                            </label>

                            @if($isCustomLogo)
                                <button type="submit" form="settings-form-{{ $groupName }}" name="remove_logo" value="1" 
                                        class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs rounded-xl border border-slate-700 transition"
                                        onclick="return confirm('Reset application logo back to default?');">
                                    Reset to Default
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- APPLICATION NAME & NAVBAR SUBTITLE MANAGER                     -->
                <!-- ============================================================== -->
                @php
                    $appNameEn = \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava');
                    $appNameKn = \App\Models\SystemSetting::get('application_name_kn', 'ಕೃಷಿ ಬಾಂಧವ');
                    $navSubtitleEn = \App\Models\SystemSetting::get('navbar_subtitle_en', 'Direct APMC Market Rates & Forecast');
                    $navSubtitleKn = \App\Models\SystemSetting::get('navbar_subtitle_kn', 'ನೇರ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ರೈತ ಮುನ್ಸೂಚನೆ');
                @endphp
                <div class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-6"
                     x-data="{
                         navPreviewLang: 'kn',
                         appNameEn: @js($appNameEn),
                         appNameKn: @js($appNameKn),
                         navSubtitleEn: @js($navSubtitleEn),
                         navSubtitleKn: @js($navSubtitleKn)
                     }">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg">
                                🏷️
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Application Name & Navbar Subtitle (ಅಪ್ಲಿಕೇಶನ್ ಹೆಸರು & ನ್ಯಾವ್‌ಬಾರ್ ಉಪ-ಶೀರ್ಷಿಕೆ)</h3>
                                <p class="text-xs text-slate-400">Configure the platform brand name and supporting subtitle displayed in the top navigation bar.</p>
                            </div>
                        </div>

                        <!-- Language Preview Switcher -->
                        <div class="flex items-center gap-1.5 p-1 bg-slate-900 border border-slate-800 rounded-xl self-start sm:self-auto">
                            <button type="button" @click="navPreviewLang = 'kn'"
                                    :class="navPreviewLang === 'kn' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                                ಕನ್ನಡ (Kn)
                            </button>
                            <button type="button" @click="navPreviewLang = 'en'"
                                    :class="navPreviewLang === 'en' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                                English (En)
                            </button>
                        </div>
                    </div>

                    <!-- Live Dynamic Navbar Preview -->
                    <div class="rounded-2xl p-4 border-2 border-[#D9CEB8] bg-[#F5EFE6] shadow-md flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-[#1C5A2C] text-white flex items-center justify-center font-black text-xl shadow-inner border border-[#134423] shrink-0">
                                🌾
                            </div>
                            <div>
                                <span class="font-extrabold text-[#1C5A2C] text-base tracking-tight block leading-tight"
                                      x-text="navPreviewLang === 'kn' ? (appNameKn || 'ಕೃಷಿ ಬಾಂಧವ') : (appNameEn || 'Krushi Baandhava')">
                                </span>
                                <p class="text-[11px] text-stone-500 font-medium font-kannada leading-none mt-1"
                                   x-text="navPreviewLang === 'kn' ? (navSubtitleKn || 'ನೇರ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ರೈತ ಮುನ್ಸೂಚನೆ') : (navSubtitleEn || 'Direct APMC Market Rates & Forecast')">
                                </p>
                            </div>
                        </div>
                        <div class="text-[10px] text-emerald-800 bg-[#EAF4EC] border border-[#B8DEC0] px-2.5 py-1 rounded-full font-mono font-bold">
                            ⚡ Navbar Preview
                        </div>
                    </div>

                    <!-- Editable Fields Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Application Name (English) -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-amber-400">
                                <span>Application Name (English)</span>
                                <span class="text-slate-400 font-normal ml-1">(ಆಂಗ್ಲ ಹೆಸರು)</span>
                            </label>
                            <input type="text" form="settings-form-{{ $groupName }}" name="settings[application_name]" x-model="appNameEn"
                                   placeholder="e.g. Krushi Baandhava"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                            <p class="text-[10px] text-slate-500">Primary brand name when user selects English.</p>
                        </div>

                        <!-- Application Name (Kannada) -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-amber-400">
                                <span>ಕನ್ನಡ ಅಪ್ಲಿಕೇಶನ್ ಹೆಸರು</span>
                                <span class="text-slate-400 font-normal ml-1">(Kannada App Name)</span>
                            </label>
                            <input type="text" form="settings-form-{{ $groupName }}" name="settings[application_name_kn]" x-model="appNameKn"
                                   placeholder="ಉದಾ: ಕೃಷಿ ಬಾಂಧವ"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                            <p class="text-[10px] text-slate-500">Primary brand name when user selects Kannada.</p>
                        </div>

                        <!-- Navbar Subtitle (English) -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-amber-400">
                                <span>Navbar Subtitle (English)</span>
                                <span class="text-slate-400 font-normal ml-1">(ಆಂಗ್ಲ ಉಪ-ಶೀರ್ಷಿಕೆ)</span>
                            </label>
                            <input type="text" form="settings-form-{{ $groupName }}" name="settings[navbar_subtitle_en]" x-model="navSubtitleEn"
                                   placeholder="e.g. Direct APMC Market Rates & Forecast"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                            <p class="text-[10px] text-slate-500">Sub-headline shown directly below brand name in English.</p>
                        </div>

                        <!-- Navbar Subtitle (Kannada) -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-amber-400">
                                <span>ಕನ್ನಡ ನ್ಯಾವ್‌ಬಾರ್ ಉಪ-ಶೀರ್ಷಿಕೆ</span>
                                <span class="text-slate-400 font-normal ml-1">(Kannada Navbar Subtitle)</span>
                            </label>
                            <input type="text" form="settings-form-{{ $groupName }}" name="settings[navbar_subtitle_kn]" x-model="navSubtitleKn"
                                   placeholder="ಉದಾ: ನೇರ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ರೈತ ಮುನ್ಸೂಚನೆ"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                            <p class="text-[10px] text-slate-500">Sub-headline shown directly below brand name in Kannada.</p>
                        </div>
                    </div>
                </div>

                <!-- HOMEPAGE HERO BANNER SLOGAN & SUBTITLE MANAGER -->
                @php
                    $heroHeadlineKn = \App\Models\SystemSetting::get('hero_headline_kn', 'ಬೆವರ ಹನಿಗೆ ಸಿಗಲಿ ತಕ್ಕ ಪ್ರತಿಫಲ, ರೈತನ ಕೈಲಿರಲಿ ಮಾರುಕಟ್ಟೆಯ ಬಲ');
                    $heroHeadlineEn = \App\Models\SystemSetting::get('hero_headline_en', "Let every drop of sweat earn its true reward; let market strength be in the farmer's hands");
                    $heroSubtitleKn = \App\Models\SystemSetting::get('hero_subtitle_kn', 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳ ಇಂದಿನ ನೇರ ದರ ಮತ್ತು ದರ ಮುನ್ಸೂಚನೆ.');
                    $heroSubtitleEn = \App\Models\SystemSetting::get('hero_subtitle_en', 'Live prices and future trends from all Karnataka APMC mandis.');
                @endphp
                <div class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-6"
                     x-data="{
                         previewLang: 'kn',
                         headlineKn: @js($heroHeadlineKn),
                         headlineEn: @js($heroHeadlineEn),
                         subtitleKn: @js($heroSubtitleKn),
                         subtitleEn: @js($heroSubtitleEn)
                     }">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg">
                                🌾
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Homepage Hero Slogan & Subtitle (ಮುಖಪುಟದ ಶೀರ್ಷಿಕೆ)</h3>
                                <p class="text-xs text-slate-400">Configure the top banner inspiring tagline and description displayed on the farmer home screen.</p>
                            </div>
                        </div>

                        <!-- Language Preview Switcher -->
                        <div class="flex items-center gap-1.5 p-1 bg-slate-900 border border-slate-800 rounded-xl self-start sm:self-auto">
                            <button type="button" @click="previewLang = 'kn'"
                                    :class="previewLang === 'kn' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                                ಕನ್ನಡ (Kn)
                            </button>
                            <button type="button" @click="previewLang = 'en'"
                                    :class="previewLang === 'en' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                                English (En)
                            </button>
                        </div>
                    </div>

                    <!-- Live Dynamic Preview Container (Mimics the Farmer App Hero Banner) -->
                    <div class="rounded-2xl p-5 sm:p-6 border-2 border-emerald-900/60 shadow-lg relative overflow-hidden"
                         style="background: linear-gradient(135deg, rgba(16, 54, 28, 0.96) 0%, rgba(12, 42, 22, 0.94) 50%, rgba(6, 22, 11, 0.92) 100%);">
                        <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-black/40 text-[10px] font-bold text-emerald-200 border border-emerald-500/40 mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span x-text="previewLang === 'kn' ? 'ನೇರ ಮಾರುಕಟ್ಟೆ ದತ್ತಾಂಶ • ಶಿವಮೊಗ್ಗ' : 'Live APMC Market Rates • Shivamogga'"></span>
                        </div>
                        <h4 class="text-base sm:text-xl font-black text-white leading-tight drop-shadow"
                            x-text="previewLang === 'kn' ? (headlineKn || 'ಬೆವರ ಹನಿಗೆ ಸಿಗಲಿ ತಕ್ಕ ಪ್ರತಿಫಲ, ರೈತನ ಕೈಲಿರಲಿ ಮಾರುಕಟ್ಟೆಯ ಬಲ') : (headlineEn || 'Let every drop of sweat earn its true reward; let market strength be in the farmer\'s hands')">
                        </h4>
                        <p class="text-xs text-emerald-100/90 mt-1 max-w-xl leading-relaxed"
                           x-text="previewLang === 'kn' ? (subtitleKn || 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳ ಇಂದಿನ ನೇರ ದರ ಮತ್ತು ದರ ಮುನ್ಸೂಚನೆ.') : (subtitleEn || 'Live prices and future trends from all Karnataka APMC mandis.')">
                        </p>
                        <div class="mt-2 text-[10px] text-emerald-400 font-mono">
                            ⚡ Realtime Preview of Farmer Homepage Banner
                        </div>
                    </div>

                    <!-- Editable Fields Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Kannada Headline -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-emerald-400">
                                <span>ಕನ್ನಡ ಶೀರ್ಷಿಕೆ</span>
                                <span class="text-slate-400 font-normal ml-1">(Kannada Headline)</span>
                            </label>
                            <input type="text" form="settings-form-{{ $groupName }}" name="settings[hero_headline_kn]" x-model="headlineKn"
                                   placeholder="ಉದಾ: ಬೆವರ ಹನಿಗೆ ಸಿಗಲಿ ತಕ್ಕ ಪ್ರತಿಫಲ, ರೈತನ ಕೈಲಿರಲಿ ಮಾರುಕಟ್ಟೆಯ ಬಲ"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                            <p class="text-[10px] text-slate-500">Main headline displayed on the farmer homepage in Kannada mode.</p>
                        </div>

                        <!-- English Headline -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-emerald-400">
                                <span>English Headline</span>
                                <span class="text-slate-400 font-normal ml-1">(ಆಂಗ್ಲ ಶೀರ್ಷಿಕೆ)</span>
                            </label>
                            <input type="text" form="settings-form-{{ $groupName }}" name="settings[hero_headline_en]" x-model="headlineEn"
                                   placeholder="e.g. Let every drop of sweat earn its true reward; let market strength be in the farmer's hands"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans">
                            <p class="text-[10px] text-slate-500">Main headline displayed on the farmer homepage in English mode.</p>
                        </div>

                        <!-- Kannada Subtitle -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-emerald-400">
                                <span>ಕನ್ನಡ ಉಪ-ಶೀರ್ಷಿಕೆ</span>
                                <span class="text-slate-400 font-normal ml-1">(Kannada Subtitle)</span>
                            </label>
                            <textarea form="settings-form-{{ $groupName }}" name="settings[hero_subtitle_kn]" x-model="subtitleKn" rows="2"
                                      placeholder="ಉದಾ: ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳ ಇಂದಿನ ನೇರ ದರ ಮತ್ತು ದರ ಮುನ್ಸೂಚನೆ."
                                      class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans leading-relaxed"></textarea>
                            <p class="text-[10px] text-slate-500">Supporting subtitle displayed under the Kannada headline.</p>
                        </div>

                        <!-- English Subtitle -->
                        <div class="space-y-1.5 p-4 rounded-2xl bg-slate-900/90 border border-slate-800">
                            <label class="block text-xs font-bold text-emerald-400">
                                <span>English Subtitle</span>
                                <span class="text-slate-400 font-normal ml-1">(ಆಂಗ್ಲ ಉಪ-ಶೀರ್ಷಿಕೆ)</span>
                            </label>
                            <textarea form="settings-form-{{ $groupName }}" name="settings[hero_subtitle_en]" x-model="subtitleEn" rows="2"
                                      placeholder="e.g. Live prices and future trends from all Karnataka APMC mandis."
                                      class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-sans leading-relaxed"></textarea>
                            <p class="text-[10px] text-slate-500">Supporting subtitle displayed under the English headline.</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- DEDICATED FOOTER CMS CALLOUT                                   -->
                <!-- ============================================================== -->
                <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-950/60 to-slate-900 border border-emerald-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Footer Layout & Content CMS</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Developer attribution, bilingual statements, APMC feeds badge, and farmer community links are managed in the dedicated CMS page.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.footer.index') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shrink-0 transition flex items-center gap-1.5 self-start sm:self-auto shadow-sm">
                        <span>Open Footer CMS</span>
                        <span>→</span>
                    </a>
                </div>
            @endif

            @if($groupName === 'pwa')
                <!-- ============================================================== -->
                <!-- PWA MOBILE INSTALLATION HEADER (Astro Tatva Style)             -->
                <!-- ============================================================== -->
                @php
                    $currentPwaIcon = \App\Models\SystemSetting::get('pwa_icon', '/icons/icon-512.svg');
                @endphp
                <div class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg">
                                📱
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">Progressive Web App (PWA) & Mobile Installation</h3>
                                <p class="text-xs text-slate-400">Configure Android & iOS installable app branding, icons, splash screen, and in-app install banner.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 shrink-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>PWA Active</span>
                        </span>
                    </div>

                    <!-- Application Mobile Icon Card -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6"
                         x-data="{ pwaIconPreview: '{{ $currentPwaIcon }}' }">
                        <div class="flex items-center gap-4">
                            <div class="relative w-20 h-20 rounded-2xl bg-slate-950 border-2 border-amber-500/30 flex items-center justify-center p-2.5 shadow-xl shrink-0 overflow-hidden">
                                <img :src="pwaIconPreview" alt="PWA Icon" class="max-w-full max-h-full object-contain">
                                <span class="absolute bottom-1 right-1 text-[9px] font-mono px-1 py-0.2 rounded bg-amber-500/20 text-amber-300 font-bold border border-amber-500/40">512PX</span>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-white">Application Mobile Icon</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">Displayed on user's Android & iOS home screens and during app launch.</p>
                                <p class="text-[10px] text-amber-400 font-mono mt-1">Upload PNG, JPG, or SVG. PNG icons at 192×192 & 512×512 are generated automatically.</p>
                            </div>
                        </div>
                        <label class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-700 cursor-pointer transition flex items-center gap-2 shrink-0">
                            <span>📤</span>
                            <span>Upload New App Icon</span>
                            <input type="file" form="settings-form-{{ $groupName }}" name="pwa_icon" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden"
                                   @change="const file = $event.target.files[0]; if(file) { const reader = new FileReader(); reader.onload = (e) => pwaIconPreview = e.target.result; reader.readAsDataURL(file); }">
                        </label>
                    </div>
                </div>
            @endif

            @if($groupName === 'forecasting')
                <!-- ============================================================== -->
                <!-- FORECASTING CONTROL DECK & BATCH RUNNER                        -->
                <!-- ============================================================== -->
                <div class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>📈</span>
                                <span>Price Forecasting Engine & Execution Deck</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Statistical projection horizons, minimum observations, and batch forecast generator.</p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <button type="submit" form="trigger-forecast-form" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-2 shrink-0">
                                <span>⚡</span>
                                <span>Trigger Batch Forecast Now</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Metrics Deck -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Total Projections</span>
                            <div class="text-lg font-black text-white mt-1">{{ number_format($totalForecasts) }}</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Tracked Commodities</span>
                            <div class="text-lg font-black text-emerald-400 mt-1">{{ $totalCrops }} Crops</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Primary Algorithm</span>
                            <div class="text-xs font-mono font-bold text-white mt-2 truncate">Holt's Linear Trend</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Last Batch Run</span>
                            <div class="text-xs font-medium text-slate-300 mt-2 truncate">{{ $lastForecastRun ? $lastForecastRun->created_at->diffForHumans() : 'Ready' }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @if($groupName === 'best_months_to_sell')
                <!-- ============================================================== -->
                <!-- BEST MONTHS TO SELL (KRUSHI HARVEST CALENDAR) CONTROL DECK    -->
                <!-- ============================================================== -->
                <div class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🗓️</span>
                                <span>Best Months to Sell — Krushi Harvest Calendar Control Deck</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Multi-year seasonal price indices, peak cyclical selling windows, and annual harvest calendar analytics.</p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Harvest Calendar Active</span>
                            </span>
                        </div>
                    </div>

                    <!-- Quick Metrics Deck -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Calendar Horizon</span>
                            <div class="text-lg font-black text-white mt-1">12 Months (Jan–Dec)</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Tracked Commodities</span>
                            <div class="text-lg font-black text-emerald-400 mt-1">{{ $totalCrops }} Crops</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Statistical Method</span>
                            <div class="text-xs font-mono font-bold text-white mt-2 truncate">Ratio-to-Mean (Multiplicative)</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Outlier Smoothing</span>
                            <div class="text-xs font-mono font-bold text-emerald-400 mt-2 truncate">Medial Winsorization</div>
                        </div>
                    </div>
                </div>
            @endif

            @if($groupName === 'maintenance')
                <!-- DATA & LOG RETENTION MAINTENANCE -->
                <div class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-800/80 pb-4">
                        <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 font-mono text-sm font-bold">
                            🗑️
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Data Retention & Storage Pruning</h3>
                            <p class="text-xs text-slate-400">Manage database storage by purging expired sync logs, temporary weather caches, and stale raw payloads.</p>
                        </div>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 text-rose-400 font-bold text-sm">
                                <span>🗑️</span>
                                <span>Manual Feed & Log Pruning</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Immediately cleans up expired sync logs, temporary weather caches, and rejected payload archives older than 30 days.
                            </p>
                        </div>
                        <form action="{{ route('admin.settings.prune-data') }}" method="POST" onsubmit="return confirm('Prune expired historical sync logs and payload archives older than 30 days?');" class="shrink-0">
                            @csrf
                            <input type="hidden" name="tab" value="maintenance">
                            <button type="submit" class="py-2.5 px-4 bg-rose-950/60 hover:bg-rose-900/70 text-rose-300 hover:text-white font-bold text-xs rounded-xl border border-rose-800/60 transition flex items-center gap-2 cursor-pointer shadow-sm">
                                <span>🗑️</span>
                                <span>Prune Stale Logs</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if($groupName === 'maps')
                @php
                    $currentApiKey = \App\Models\SystemSetting::get('map_api_key', '');
                    $currentProvider = \App\Models\SystemSetting::get('map_tile_provider', 'carto_voyager');
                    $currentCustomUrl = \App\Models\SystemSetting::get('map_custom_tile_url', '');
                @endphp

                <!-- Leaflet Local Assets for Live Admin Map Verification -->
                <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}"/>
                <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

                <script>
                    function adminMapSettings(initialApiKey, initialProvider, initialCustomUrl) {
                        return {
                            apiKey: initialApiKey || '',
                            provider: initialProvider || 'carto_voyager',
                            customUrl: initialCustomUrl || '',
                            showKey: false,
                            previewMap: null,
                            tileLayer: null,
                            initMap() {
                                this.$nextTick(() => {
                                    const container = document.getElementById('adminMapPreview');
                                    if (!container || typeof L === 'undefined') return;
                                    if (this.previewMap) {
                                        this.updateTiles();
                                        return;
                                    }
                                    this.previewMap = L.map('adminMapPreview', {
                                        center: [13.9299, 75.5681],
                                        zoom: 7,
                                        zoomControl: true,
                                        scrollWheelZoom: false
                                    });

                                    // Sample Mandi Pin (Shivamogga)
                                    const testPin = L.divIcon({
                                        className: 'admin-preview-pin',
                                        html: '<div style="width:32px;height:32px;border-radius:50%;background:#059669;border:3px solid #fff;display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;box-shadow:0 4px 6px rgba(0,0,0,0.4);">🌾</div>',
                                        iconSize: [32, 32],
                                        iconAnchor: [16, 16]
                                    });
                                    L.marker([13.9299, 75.5681], { icon: testPin })
                                        .bindPopup('<div style="font-family:sans-serif;font-size:12px;"><strong style="color:#059669;">Shivamogga Mandi</strong><br><small style="color:#64748b;">Karnataka Geospatial Anchor</small></div>')
                                        .addTo(this.previewMap);

                                    this.updateTiles();
                                });
                            },
                            updateTiles() {
                                if (!this.previewMap || typeof L === 'undefined') return;
                                if (this.tileLayer) {
                                    this.previewMap.removeLayer(this.tileLayer);
                                }

                                let url = '';
                                let attribution = '&copy; OpenStreetMap &copy; CARTO';
                                let subdomains = 'abcd';
                                const key = (this.apiKey || '').trim();

                                if (this.provider === 'custom' && this.customUrl.trim() !== '') {
                                    url = this.customUrl.trim();
                                    if (key && url.includes('{api_key}')) {
                                        url = url.replace('{api_key}', encodeURIComponent(key));
                                    } else if (key && !url.includes('api_key=')) {
                                        url += (url.includes('?') ? '&' : '?') + 'api_key=' + encodeURIComponent(key);
                                    }
                                } else if (this.provider === 'osm_standard') {
                                    url = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
                                    attribution = '&copy; OpenStreetMap contributors';
                                    subdomains = 'abc';
                                } else if (this.provider === 'carto_positron') {
                                    url = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png' + (key ? ('?api_key=' + encodeURIComponent(key)) : '');
                                } else {
                                    // Default carto_voyager
                                    url = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png' + (key ? ('?api_key=' + encodeURIComponent(key)) : '');
                                }

                                this.tileLayer = L.tileLayer(url, {
                                    attribution: attribution,
                                    maxZoom: 18,
                                    subdomains: subdomains
                                }).addTo(this.previewMap);
                            }
                        };
                    }
                </script>

                <!-- Dedicated Interactive Maps & Cartography Card -->
                <div class="space-y-6"
                     x-data="adminMapSettings(@js($currentApiKey), @js($currentProvider), @js($currentCustomUrl))"
                     x-init="
                        initMap();
                        $watch('$parent.currentTab', val => {
                            if (val === 'maps') {
                                setTimeout(() => {
                                    if (previewMap) {
                                        previewMap.invalidateSize();
                                        updateTiles();
                                    } else {
                                        initMap();
                                    }
                                }, 150);
                            }
                        });
                     ">

                    <!-- Informational Callout Box -->
                    <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-500/20 text-xs text-emerald-200/90 flex items-start gap-3 leading-relaxed">
                        <span class="text-xl shrink-0">💡</span>
                        <div class="space-y-1">
                            <strong class="text-white font-semibold text-sm">CARTO Basemaps API Key & Watermark Information</strong>
                            <p class="text-emerald-100/80">
                                CARTO recently updated its basemaps service policy requiring an API key for tile rendering (<code class="text-amber-300 font-mono">carto.com/basemaps/apikey</code>).
                                Enter your API key below to remove the watermark from the interactive route map on the farmer <span class="font-bold text-white">Where-to-Sell</span> simulator. If left empty, Krushi Baandhava automatically falls back to OpenStreetMap so farmers never experience watermark clutter.
                            </p>
                        </div>
                    </div>

                    <!-- Map Configuration Inputs Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        
                        <!-- Left 7 Cols: Inputs -->
                        <div class="lg:col-span-7 space-y-4">
                            <!-- 1. API Key Input -->
                            <div class="space-y-2 p-5 rounded-2xl bg-slate-950/80 border border-slate-800">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-white flex items-center gap-1.5">
                                        <span>🔑 CARTO Basemaps API Key</span>
                                        <span class="text-rose-400">*</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <template x-if="apiKey && apiKey.trim().length > 0">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px] font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                <span>Key Present</span>
                                            </span>
                                        </template>
                                        <template x-if="!apiKey || apiKey.trim().length === 0">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-950 text-amber-300 border border-amber-800 text-[10px] font-bold">
                                                <span>OSM Fallback Active</span>
                                            </span>
                                        </template>
                                        <button type="button" @click="showKey = !showKey" class="text-[11px] text-slate-400 hover:text-white flex items-center gap-1 ml-1 cursor-pointer">
                                            <span x-text="showKey ? '🙈 Hide' : '👁️ Show'"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="relative">
                                    <input :type="showKey ? 'text' : 'password'" 
                                           form="settings-form-{{ $groupName }}"
                                           name="settings[map_api_key]" 
                                           x-model="apiKey"
                                           @input="updateTiles()"
                                           placeholder="Paste your CARTO API Key here (e.g. carto_default_public_...)"
                                           class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white placeholder-slate-500 font-mono focus:outline-none focus:border-emerald-500 pr-10">
                                    <div class="absolute right-3 top-2.5 text-slate-500 pointer-events-none text-xs">
                                        🔒
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-0.5">
                                    <span>Used in <code class="font-mono text-emerald-400">/where-to-sell</code> Leaflet route simulator</span>
                                    <a href="https://carto.com/basemaps/apikey" target="_blank" rel="noopener noreferrer" class="text-emerald-400 hover:text-emerald-300 font-medium flex items-center gap-0.5">
                                        <span>Get CARTO key</span>
                                        <span>↗</span>
                                    </a>
                                </div>
                            </div>

                            <!-- 2. Tile Provider Selector -->
                            <div class="space-y-2 p-5 rounded-2xl bg-slate-950/80 border border-slate-800">
                                <label class="block text-xs font-bold text-white flex items-center gap-1.5">
                                    <span>🗺️ Map Cartography Style (Tile Provider)</span>
                                </label>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                    <!-- Option 1: CARTO Voyager -->
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition select-none"
                                           :class="provider === 'carto_voyager' ? 'bg-emerald-950/40 border-emerald-500 text-white shadow-sm' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'">
                                        <input type="radio" form="settings-form-{{ $groupName }}" name="settings[map_tile_provider]" value="carto_voyager" 
                                               x-model="provider" @change="updateTiles()" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <div class="text-xs font-bold text-white flex items-center gap-1">
                                                <span>CARTO Voyager</span>
                                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 font-normal">Best</span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">High-contrast roads, town names, and topography (Recommended).</div>
                                        </div>
                                    </label>

                                    <!-- Option 2: CARTO Positron -->
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition select-none"
                                           :class="provider === 'carto_positron' ? 'bg-emerald-950/40 border-emerald-500 text-white shadow-sm' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'">
                                        <input type="radio" form="settings-form-{{ $groupName }}" name="settings[map_tile_provider]" value="carto_positron" 
                                               x-model="provider" @change="updateTiles()" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <div class="text-xs font-bold text-white">CARTO Positron</div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">Minimal light gray cartography with subtle road outlines.</div>
                                        </div>
                                    </label>

                                    <!-- Option 3: OpenStreetMap Standard -->
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition select-none"
                                           :class="provider === 'osm_standard' ? 'bg-emerald-950/40 border-emerald-500 text-white shadow-sm' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'">
                                        <input type="radio" form="settings-form-{{ $groupName }}" name="settings[map_tile_provider]" value="osm_standard" 
                                               x-model="provider" @change="updateTiles()" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <div class="text-xs font-bold text-white flex items-center gap-1">
                                                <span>OpenStreetMap</span>
                                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-300 font-normal">Free</span>
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">Community public tiles (100% free, no API key required).</div>
                                        </div>
                                    </label>

                                    <!-- Option 4: Custom URL -->
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition select-none"
                                           :class="provider === 'custom' ? 'bg-emerald-950/40 border-emerald-500 text-white shadow-sm' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'">
                                        <input type="radio" form="settings-form-{{ $groupName }}" name="settings[map_tile_provider]" value="custom" 
                                               x-model="provider" @change="updateTiles()" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <div class="text-xs font-bold text-white">Custom Server</div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">Self-hosted or custom raster tile URL endpoint.</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- 3. Custom Tile URL Input (Shown when custom is selected) -->
                            <div class="space-y-1.5 p-4 rounded-2xl bg-slate-950/80 border border-slate-800" x-show="provider === 'custom'">
                                <label class="block text-xs font-bold text-white">
                                    <span>🌐 Custom Tile Server URL Pattern</span>
                                </label>
                                <input type="text" form="settings-form-{{ $groupName }}" name="settings[map_custom_tile_url]" 
                                       x-model="customUrl" @input="updateTiles()"
                                       placeholder="https://{s}.tile.example.com/{z}/{x}/{y}.png"
                                       class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                                <p class="text-[10px] text-slate-400">Must include <code class="text-amber-300">{z}</code>, <code class="text-amber-300">{x}</code>, and <code class="text-amber-300">{y}</code> variables.</p>
                            </div>
                        </div>

                        <!-- Right 5 Cols: Live Map Preview -->
                        <div class="lg:col-span-5 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-white flex items-center gap-1.5">
                                    <span>🛰️ Live Tile Verification Preview</span>
                                </label>
                                <button type="button" @click="updateTiles()" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 cursor-pointer flex items-center gap-1">
                                    <span>🔄 Refresh Preview</span>
                                </button>
                            </div>

                            <!-- Map container -->
                            <div class="rounded-2xl border-2 border-slate-800 overflow-hidden bg-slate-950 relative shadow-inner">
                                <div id="adminMapPreview" style="height: 290px; width: 100%; z-index: 1;"></div>
                                <div class="absolute bottom-2 left-2 z-10 px-2.5 py-1 rounded-lg bg-slate-950/85 backdrop-blur-md text-[10px] font-mono text-slate-300 border border-slate-800 pointer-events-none">
                                    <span x-text="provider.toUpperCase()"></span> • Live Render
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 text-center">
                                Verify that the "API KEY REQUIRED" watermark does not appear above when your key is entered.
                            </p>
                        </div>

                    </div>
                </div>
            @endif

            <!-- Main Group Form -->
            <form id="settings-form-{{ $groupName }}" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                @csrf
                <input type="hidden" name="tab" value="{{ $groupName }}">

                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-white capitalize">{{ $tabMeta[$groupName]['name'] ?? $groupName }} Configuration</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Parameters under the <code class="font-mono text-emerald-400">{{ $groupName }}</code> domain</p>
                    </div>
                </div>

                <!-- Settings Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($settings as $setting)
                        @if(in_array($setting->key, ['hero_headline_kn', 'hero_headline_en', 'hero_subtitle_kn', 'hero_subtitle_en', 'application_name', 'application_name_kn', 'navbar_subtitle_en', 'navbar_subtitle_kn', 'map_api_key', 'map_tile_provider', 'map_custom_tile_url']))
                            @continue
                        @endif
                        <div class="space-y-2 p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 hover:border-slate-700 transition flex flex-col justify-between"
                             x-show="searchQuery === '' || '{{ strtolower($setting->key ?? '') }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($setting->description ?? '') }}'.includes(searchQuery.toLowerCase())">
                            
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider">
                                        {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                                    </label>
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-900 text-slate-400 border border-slate-800">
                                        {{ $setting->type }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-400 leading-relaxed">{{ $setting->description }}</p>
                            </div>

                            <div class="pt-2">
                                <!-- ============================================ -->
                                <!-- 1. FIXED & ENHANCED BOOLEAN TOGGLE (Point 1) -->
                                <!-- ============================================ -->
                                @if($setting->type === 'boolean')
                                    <div class="flex items-center justify-between pt-1" x-data="{ enabled: {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false' }} }">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block w-2.5 h-2.5 rounded-full transition-colors duration-200" 
                                                  :class="enabled ? 'bg-emerald-400 shadow-sm shadow-emerald-400' : 'bg-slate-600'"></span>
                                            <span class="text-xs font-bold tracking-wide transition-colors" 
                                                  :class="enabled ? 'text-emerald-400' : 'text-slate-400'" 
                                                  x-text="enabled ? 'Active / Enabled' : 'Disabled'"></span>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer select-none">
                                            <!-- Hidden field guarantees 0 is submitted when unchecked -->
                                            <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                            <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" 
                                                   x-model="enabled"
                                                   {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600 transition-colors duration-200"></div>
                                        </label>
                                    </div>

                                <!-- ============================================ -->
                                <!-- 2. PWA COLOR PICKERS (Status Bar / Splash)   -->
                                <!-- ============================================ -->
                                @elseif($setting->key === 'pwa_theme_color' || $setting->key === 'pwa_background_color')
                                    <div class="flex items-center gap-3" x-data="{ colorHex: '{{ $setting->value }}' }">
                                        <div class="relative">
                                            <input type="color" x-model="colorHex" 
                                                   class="w-10 h-10 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer p-0.5">
                                        </div>
                                        <input type="text" name="settings[{{ $setting->key }}]" x-model="colorHex"
                                               class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono uppercase focus:outline-none focus:border-emerald-500">
                                    </div>

                                <!-- PWA Display Mode -->
                                @elseif($setting->key === 'pwa_display_mode')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="standalone" {{ $setting->value === 'standalone' ? 'selected' : '' }}>Standalone (App-like — Hides browser bar)</option>
                                        <option value="fullscreen" {{ $setting->value === 'fullscreen' ? 'selected' : '' }}>Fullscreen (Immersive)</option>
                                        <option value="minimal-ui" {{ $setting->value === 'minimal-ui' ? 'selected' : '' }}>Minimal UI</option>
                                        <option value="browser" {{ $setting->value === 'browser' ? 'selected' : '' }}>Browser Window</option>
                                    </select>

                                <!-- PWA Description -->
                                @elseif($setting->key === 'pwa_description')
                                    <textarea name="settings[{{ $setting->key }}]" rows="2"
                                              class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 leading-relaxed">{{ $setting->value }}</textarea>

                                <!-- ============================================ -->
                                <!-- 3. INTERACTIVE FORECAST HORIZONS SELECTOR    -->
                                <!-- ============================================ -->
                                @elseif($setting->key === 'forecast_horizons')
                                    @php
                                        $selectedHorizons = json_decode($setting->value, true) ?: [1, 7, 15, 30];
                                        $allHorizons = [
                                            1 => '1 Day (Tomorrow)',
                                            7 => '7 Days (1 Week)',
                                            15 => '15 Days (Fortnight)',
                                            30 => '30 Days (1 Month)',
                                            60 => '60 Days (Seasonal Window)',
                                        ];
                                    @endphp
                                    <div class="space-y-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($allHorizons as $hDays => $hLabel)
                                                <label class="flex items-center gap-2 p-2 rounded-xl bg-slate-900 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                                                    <input type="checkbox" name="settings[{{ $setting->key }}][]" value="{{ $hDays }}"
                                                           {{ in_array($hDays, $selectedHorizons) ? 'checked' : '' }}
                                                           class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500">
                                                    <span class="text-[11px] font-semibold text-slate-300">{{ $hLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <p class="text-[10px] text-slate-500">Checked projection horizons are computed during daily automated batch forecasting runs.</p>
                                    </div>

                                @elseif($setting->key === 'forecasting_engine')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="holts_linear_trend" {{ $setting->value === 'holts_linear_trend' ? 'selected' : '' }}>Holt's Double Exponential Smoothing (Recommended - Trend Sensitive)</option>
                                        <option value="seasonal_decomposition" {{ $setting->value === 'seasonal_decomposition' ? 'selected' : '' }}>Seasonal Decomposition & Moving Average</option>
                                        <option value="moving_average_weighted" {{ $setting->value === 'moving_average_weighted' ? 'selected' : '' }}>Weighted Rolling Volatility Model</option>
                                    </select>

                                @elseif($setting->key === 'forecast_minimum_observations')
                                    <div class="space-y-1.5">
                                        <div class="relative">
                                            <input type="number" min="5" max="365" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-emerald-500">
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] text-emerald-400 font-mono font-bold pointer-events-none">
                                                days of data
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 leading-relaxed">Default: 30 days. Historical daily mandi records needed to generate projections. If records are below this threshold, a data-insufficiency banner is displayed.</p>
                                    </div>

                                @elseif($setting->key === 'forecast_confidence_threshold')
                                    <div class="space-y-1.5">
                                        <div class="relative">
                                            <input type="number" min="10" max="100" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-emerald-500">
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] text-emerald-400 font-mono font-bold pointer-events-none">
                                                % min score
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 leading-relaxed">Default: 70%. Confidence threshold required to label projections as 'Likely / ಹೆಚ್ಚು ಸಾಧ್ಯತೆ' on farmer cards. Projections below this score show cautionary advice.</p>
                                    </div>

                                @elseif($setting->key === 'seasonality_years')
                                    <div class="space-y-1.5">
                                        <div class="relative">
                                            <input type="number" min="1" max="15" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-emerald-500">
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] text-emerald-400 font-mono font-bold pointer-events-none">
                                                historical years
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 leading-relaxed">Default: 5 years. Used by 'Best Months to Sell' (Krushi Harvest Calendar) to evaluate annual cyclical peak price months across historical years.</p>
                                    </div>


                                <!-- ============================================ -->
                                <!-- 4. DATA SYNC FEEDS CONTROLS (Point 3)        -->
                                <!-- ============================================ -->
                                @elseif($setting->key === 'primary_feed_provider')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="krama_karnataka" {{ $setting->value === 'krama_karnataka' ? 'selected' : '' }}>KRAMA (Karnataka APMC Board — Primary Live Feed)</option>
                                        <option value="agmarknet_official" {{ $setting->value === 'agmarknet_official' ? 'selected' : '' }}>Official AGMARKNET (Govt of India — Multi-Year Historical & Predictions)</option>
                                        <option value="datagov_direct" {{ $setting->value === 'datagov_direct' ? 'selected' : '' }}>data.gov.in Direct OGDS Mandi API (National Fallback)</option>
                                    </select>

                                @elseif($setting->key === 'market_sync_interval')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="hourly" {{ $setting->value === 'hourly' ? 'selected' : '' }}>Hourly Ingestion</option>
                                        <option value="twice_daily" {{ $setting->value === 'twice_daily' ? 'selected' : '' }}>Twice Daily (11:30 AM & 5:30 PM Mandi Auctions)</option>
                                        <option value="daily" {{ $setting->value === 'daily' ? 'selected' : '' }}>Daily Morning (06:00 IST)</option>
                                        <option value="manual" {{ $setting->value === 'manual' ? 'selected' : '' }}>Manual On-demand Ingestion Only</option>
                                    </select>

                                <!-- District Dropdown -->
                                @elseif($setting->key === 'default_district')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        @foreach($districts as $d)
                                            <option value="{{ $d->name }}" {{ $setting->value === $d->name ? 'selected' : '' }}>
                                                {{ $d->name }} ({{ $d->name_kn }})
                                            </option>
                                        @endforeach
                                    </select>

                                <!-- Language Dropdown -->
                                @elseif($setting->key === 'default_language')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="kn" {{ $setting->value === 'kn' ? 'selected' : '' }}>ಕನ್ನಡ (Kannada - Default)</option>
                                        <option value="en" {{ $setting->value === 'en' ? 'selected' : '' }}>English</option>
                                    </select>

                                <!-- Weather Settings -->
                                @elseif($setting->key === 'weather_provider')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="open_meteo" {{ $setting->value === 'open_meteo' ? 'selected' : '' }}>Open-Meteo API (ECMWF/GFS High-Res Global)</option>
                                        <option value="imd_mausam" {{ $setting->value === 'imd_mausam' ? 'selected' : '' }}>IMD Mausam / Agromet (Govt of India)</option>
                                        <option value="custom_rest" {{ $setting->value === 'custom_rest' ? 'selected' : '' }}>Custom Agro-Meteorological REST API</option>
                                    </select>

                                @elseif($setting->key === 'weather_sync_interval')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="hourly" {{ $setting->value === 'hourly' ? 'selected' : '' }}>Hourly (Every 60 Minutes)</option>
                                        <option value="twice_daily" {{ $setting->value === 'twice_daily' ? 'selected' : '' }}>Twice Daily (Recommended - 06:00 & 18:00 IST)</option>
                                        <option value="daily" {{ $setting->value === 'daily' ? 'selected' : '' }}>Once Daily (06:00 IST Morning)</option>
                                        <option value="manual" {{ $setting->value === 'manual' ? 'selected' : '' }}>Manual Only (On-demand via Admin / CLI)</option>
                                    </select>

                                @elseif($setting->key === 'weather_advisory_mode')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="standard_agronomic" {{ $setting->value === 'standard_agronomic' ? 'selected' : '' }}>Standard Agronomic (70% rain alert threshold)</option>
                                        <option value="strict_monsoon_alert" {{ $setting->value === 'strict_monsoon_alert' ? 'selected' : '' }}>Strict Monsoon Alert (50% early warning threshold)</option>
                                        <option value="conservative" {{ $setting->value === 'conservative' ? 'selected' : '' }}>Conservative (High certainty alerts only)</option>
                                    </select>

                                @elseif($setting->key === 'weather_fallback_strategy')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="use_cached_last_known" {{ $setting->value === 'use_cached_last_known' ? 'selected' : '' }}>Serve Last Known Cached Forecast (Recommended)</option>
                                        <option value="fallback_district_centroid" {{ $setting->value === 'fallback_district_centroid' ? 'selected' : '' }}>Approximate from District Centroid Coordinates</option>
                                        <option value="suppress_forecast" {{ $setting->value === 'suppress_forecast' ? 'selected' : '' }}>Suppress Forecast with System Notice</option>
                                    </select>

                                @elseif($setting->key === 'weather_units_system')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="metric_celsius" {{ $setting->value === 'metric_celsius' ? 'selected' : '' }}>Metric (°C, km/h, mm)</option>
                                        <option value="imperial_standard" {{ $setting->value === 'imperial_standard' ? 'selected' : '' }}>Imperial (°F, mph, in)</option>
                                    </select>

                                <!-- Numeric Input with Unit Badge -->
                                @elseif($setting->type === 'integer')
                                    <div class="relative">
                                        <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                               class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-emerald-500">
                                        @php
                                            $unit = '';
                                            if (str_contains($setting->key, 'duration') || str_contains($setting->key, 'cache')) $unit = 'seconds';
                                            elseif (str_contains($setting->key, 'years')) $unit = 'years';
                                            elseif (str_contains($setting->key, 'observations')) $unit = 'records';
                                            elseif (str_contains($setting->key, 'threshold') || str_contains($setting->key, 'percentage')) $unit = '%';
                                            elseif (str_contains($setting->key, 'limit')) $unit = 'items';
                                        @endphp
                                        @if($unit)
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] text-slate-500 font-mono pointer-events-none">
                                                {{ $unit }}
                                            </span>
                                        @endif
                                    </div>

                                @elseif($setting->type === 'json')
                                    <textarea name="settings[{{ $setting->key }}]" rows="2"
                                              class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-emerald-400 font-mono focus:outline-none focus:border-emerald-500">{{ $setting->value }}</textarea>

                                @else
                                    <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                           class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Save {{ $tabMeta[$groupName]['name'] ?? $groupName }} Settings</span>
                    </button>
                </div>
            </form>
        </div>
    @endforeach

</div>
@endsection
