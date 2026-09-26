@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ currentTab: '{{ $activeTab }}', searchQuery: '' }">

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

    <!-- Navigation Tabs (Modern Enterprise Row) -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800 no-scrollbar">
        @php
            $tabMeta = [
                'general' => ['name' => 'General & Platform Branding', 'icon' => '🏛️'],
                'pwa' => ['name' => 'Mobile App & PWA', 'icon' => '📱'],
                'weather' => ['name' => 'Weather Services', 'icon' => '🌤️'],
                'data_sources' => ['name' => 'Data Sync Feeds', 'icon' => '🔄'],
                'forecasting' => ['name' => 'Price Forecasting', 'icon' => '📈'],
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

            @if($groupName === 'maintenance')
                <!-- ============================================================== -->
                <!-- IMMEDIATE SYSTEM OPERATIONS (Astro Tatva Screenshot 2 Style)   -->
                <!-- ============================================================== -->
                <div class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-800/80 pb-4">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 font-mono text-sm font-bold">
                            &gt;_
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Immediate System Operations</h3>
                            <p class="text-xs text-slate-400">Execute low-level system actions, framework cache clearing, and database updates.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <!-- Application Caches -->
                        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center gap-2 text-amber-400 font-bold text-xs">
                                    <span>🔄</span>
                                    <span>Application Caches</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                    Clears view cache, compiled routes, config cache, and application memory stores.
                                </p>
                            </div>
                            <form action="{{ route('admin.settings.clear-cache') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-amber-400 hover:text-amber-300 font-bold text-xs rounded-xl border border-slate-700/80 transition flex items-center justify-center gap-2">
                                    <span>🔄</span>
                                    <span>Clear All Caches</span>
                                </button>
                            </form>
                        </div>

                        <!-- Database Schema -->
                        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center gap-2 text-orange-400 font-bold text-xs">
                                    <span>🗄️</span>
                                    <span>Database Schema</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                    Executes pending database migrations and seeds newly introduced tables/plans safely.
                                </p>
                            </div>
                            <form action="{{ route('admin.settings.update-database') }}" method="POST" onsubmit="return confirm('Execute pending database migrations and update master seeds?');">
                                @csrf
                                <button type="submit" class="w-full py-2.5 px-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow-lg shadow-amber-500/20 transition flex items-center justify-center gap-2">
                                    <span>🗄️</span>
                                    <span>Update Database</span>
                                </button>
                            </form>
                        </div>

                        <!-- Manual Feed Pruning -->
                        <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center gap-2 text-rose-400 font-bold text-xs">
                                    <span>🗑️</span>
                                    <span>Manual PDF & Feed Pruning</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                    Immediately cleans up expired sync logs, temporary weather caches, and rejected payload archives.
                                </p>
                            </div>
                            <form action="{{ route('admin.settings.prune-data') }}" method="POST" onsubmit="return confirm('Prune expired historical sync logs and payload archives?');">
                                @csrf
                                <button type="submit" class="w-full py-2.5 px-3 bg-rose-950/40 hover:bg-rose-900/50 text-rose-300 hover:text-white font-bold text-xs rounded-xl border border-rose-900/50 transition flex items-center justify-center gap-2">
                                    <span>🗑️</span>
                                    <span>Prune Stale Data</span>
                                </button>
                            </form>
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
                        @if(in_array($setting->key, ['hero_headline_kn', 'hero_headline_en', 'hero_subtitle_kn', 'hero_subtitle_en']))
                            @continue
                        @endif
                        <div class="space-y-2 p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 hover:border-slate-700 transition flex flex-col justify-between"
                             x-show="searchQuery === '' || '{{ strtolower($setting->key) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($setting->description) }}'.includes(searchQuery.toLowerCase())">
                            
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

                                <!-- ============================================ -->
                                <!-- 4. DATA SYNC FEEDS CONTROLS (Point 3)        -->
                                <!-- ============================================ -->
                                @elseif($setting->key === 'primary_feed_provider')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-medium">
                                        <option value="ceda_agmarknet" {{ $setting->value === 'ceda_agmarknet' ? 'selected' : '' }}>CEDA Agmarknet (Ashoka University — High-Speed & Clean Mandis)</option>
                                        <option value="datagov_direct" {{ $setting->value === 'datagov_direct' ? 'selected' : '' }}>data.gov.in Direct OGDS Mandi API</option>
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
