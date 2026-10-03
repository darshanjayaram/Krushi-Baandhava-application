@extends('layouts.farmer')

@section('title', app()->getLocale() === 'en' ? 'Where to Sell? (Net Realization Simulator) — Krushi Baandhava' : 'ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು? (ನಿವ್ವಳ ಲಾಭ ಕ್ಯಾಲ್ಕುಲೇಟರ್) — ಕೃಷಿ ಬಾಂಧವ')

@section('content')
@php
    $activeLocale = app()->getLocale();
    $isEn = ($activeLocale === 'en');
    $initialRec = $comparison['recommended_market'] ?? null;
    $initialNearest = $comparison['nearest_market'] ?? null;
    $initialHasResults = isset($comparison['markets']) && $comparison['markets']->isNotEmpty();
    $mapApiKey = \App\Models\SystemSetting::get('map_api_key', '');
    $mapTileProvider = \App\Models\SystemSetting::get('map_tile_provider', 'carto_voyager');
    $mapCustomTileUrl = \App\Models\SystemSetting::get('map_custom_tile_url', '');
@endphp

<!-- Leaflet CSS for Interactive Karnataka Route Map (Local Vendor Asset) -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}"/>

<!-- Typography & Optical Baseline Rules: Ensures Noto Sans Kannada applies properly for Kannada text -->
<style>
    [x-cloak] { display: none !important; }

    .font-kannada,
    .kannada-text {
        font-family: 'Noto Sans Kannada', 'Manrope', sans-serif !important;
    }

    @if(!$isEn)
    html[lang="kn"],
    html[lang="kn"] body {
        font-family: 'Noto Sans Kannada', 'Manrope', sans-serif !important;
    }
    @endif

    /* Custom Leaflet pulse & ping animations */
    @keyframes ping {
        75%, 100% {
            transform: scale(2);
            opacity: 0;
        }
    }
    @keyframes pulse {
        50% {
            opacity: .5;
        }
    }

    /* Soft Shimmer Skeleton Loading Animation */
    @keyframes kbShimmerSweep {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .kb-shimmer-box {
        position: relative;
        overflow: hidden;
    }
    .kb-shimmer-box::after {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        transform: translateX(-100%);
        background: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0.12) 35%,
            rgba(255, 255, 255, 0.28) 50%,
            rgba(255, 255, 255, 0.12) 65%,
            rgba(255, 255, 255, 0) 100%
        );
        animation: kbShimmerSweep 1.6s infinite ease-in-out;
        content: '';
    }
</style>

<div class="space-y-5 max-w-6xl mx-auto pb-24 sm:pb-16" 
     x-data="whereToSellApp({
        locale: '{{ $activeLocale }}',
        initialCrop: @js($selectedCrop ? ['id' => $selectedCrop->id, 'slug' => $selectedCrop->slug, 'name' => $selectedCrop->name, 'name_kn' => $selectedCrop->name_kn ?? $selectedCrop->name, 'photo_url' => $selectedCrop->photo_url, 'varieties' => $selectedCrop->varieties] : null),
        initialVarietyId: {{ $params['variety_id'] ? (int)$params['variety_id'] : 'null' }},
        initialBaselineMarketId: {{ !empty($params['market_id']) ? (int)$params['market_id'] : 'null' }},
        initialQuantity: {{ (float)($params['quantity'] ?? 10) }},
        initialVehicle: '{{ $params['vehicle'] ?? 'pickup' }}',
        initialRateType: '{{ $params['rate_type'] ?? 'per_km' }}',
        initialCustomRate: {{ $params['custom_rate'] ? (float)$params['custom_rate'] : 'null' }},
        initialLat: {{ $params['lat'] ? (float)$params['lat'] : 'null' }},
        initialLng: {{ $params['lng'] ? (float)$params['lng'] : 'null' }},
        initialDistrictId: {{ $params['district_id'] ? (int)$params['district_id'] : 'null' }},
        initialTalukId: {{ $params['taluk_id'] ? (int)$params['taluk_id'] : 'null' }},
        initialMaxDistance: {{ !empty($params['max_distance']) ? (float)$params['max_distance'] : 'null' }},
        initialRoundTrip: {{ isset($params['round_trip']) ? ($params['round_trip'] ? 'true' : 'false') : 'true' }},
        isContextual: {{ !empty($isContextual) ? 'true' : 'false' }},
        initialSort: '{{ $params['sort'] ?? 'net_realization' }}',
        initialComparison: @js($comparison),
        apiUrl: '{{ route('api.v1.decision.where-to-sell') }}',
        mapApiKey: @js($mapApiKey),
        mapTileProvider: @js($mapTileProvider),
        mapCustomTileUrl: @js($mapCustomTileUrl)
     })"
     x-init="initApp()">

    <!-- ========================================================================= -->
    <!-- 1. BREADCRUMBS & COMPACT HERO                                             -->
    <!-- ========================================================================= -->
    <nav class="flex items-center text-xs font-semibold text-stone-500 space-x-1.5 pt-1">
        <a href="{{ route('home') }}" class="hover:text-emerald-700 transition flex items-center gap-1">
            <span>🏠</span>
            <span>{{ $isEn ? 'Home' : 'ಹೋಮ್' }}</span>
        </a>
        <span class="text-stone-400">/</span>
        <span class="text-emerald-900 font-bold">
            {{ $isEn ? 'Where to Sell? (Net Realization Simulator)' : 'ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು? (Where to Sell?)' }}
        </span>
    </nav>

    <!-- Classic Negilu Modern Hero Card -->
    <div class="rounded-3xl relative shadow-xl border-2 border-[#D9CEB8] overflow-hidden text-white"
         style="background: linear-gradient(135deg, rgba(16, 54, 28, 0.97) 0%, rgba(12, 42, 22, 0.92) 55%, rgba(6, 22, 11, 0.98) 100%), url('{{ asset('images/hero_farmer.jpg') }}') center right / cover no-repeat;">
        
        <div class="absolute -right-16 -top-16 w-60 h-60 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-60 h-60 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 p-4 sm:p-7 space-y-3.5">
            <!-- Top Eyebrow Badges -->
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-black/40 backdrop-blur-md text-[11px] font-bold text-emerald-200 border border-emerald-500/40 shadow-xs">
                    <span>⚖️</span>
                    <span>{{ $isEn ? 'Net Realization & Transport Cost Simulator' : 'ನಿವ್ವಳ ಲಾಭ & ಸಾರಿಗೆ ವೆಚ್ಚ ಕ್ಯಾಲ್ಕುಲೇಟರ್' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-950/70 backdrop-blur-md text-[10px] font-bold text-emerald-300 border border-emerald-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>{{ $isEn ? 'Real-Time Async Simulation' : 'ನೈಜ ಸಮಯದಲ್ಲಿ ಲೈವ್ ಲೆಕ್ಕಾಚಾರ (Live Async)' }}</span>
                    </span>
                </div>
            </div>

            <!-- Main Headline -->
            <div class="max-w-3xl space-y-1 sm:space-y-1.5">
                <h1 class="text-xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight leading-tight">
                    {{ $isEn ? 'Where will you get the highest take-home cash for your harvest?' : 'ಬೆಳೆಯನ್ನು ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಿದರೆ ಹೆಚ್ಚು ಹಣ ಕೈಗೆ ಸಿಗುತ್ತದೆ?' }}
                </h1>
                <p class="text-xs sm:text-sm text-emerald-100 font-medium leading-relaxed">
                    {{ $isEn 
                        ? 'Do not be misled by high mandi prices alone! Calculate your true net in-pocket earnings after deducting actual road transport fuel, hamali loading, and APMC market cess.' 
                        : 'ಕೇವಲ ಮಂಡಿ ದರ ನೋಡಿ ಮೋಸಹೋಗಬೇಡಿ! ವಾಹನದ ಸಾರಿಗೆ ವೆಚ್ಚ, ಹಮಾಲಿ ಮತ್ತು ಮಾರುಕಟ್ಟೆ ಸೆಸ್ ಕಳೆದ ನಂತರ, ನಿಮ್ಮ ಕೈಗೆ ಅಸಲಿ ನಿವ್ವಳ ಲಾಭ ಎಷ್ಟು ಉಳಿಯುತ್ತದೆ ಎಂಬುದನ್ನು ನಿಖರವಾಗಿ ತಿಳಿಯಿರಿ.' }}
                </p>
            </div>

            <!-- Active Simulation Snapshot Bar -->
            <div class="pt-1 flex flex-wrap items-center gap-2 text-xs text-emerald-100">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span>🌾</span>
                    <strong class="text-white" x-text="getCropDisplayName()"></strong>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/15" x-show="varietyId">
                    <span>🏷️</span>
                    <strong class="text-amber-200" x-text="getVarietyDisplayName()"></strong>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span>⚖️</span>
                    <strong class="text-white" x-text="quantity + ' {{ $isEn ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್' }}'"></strong>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/15 truncate max-w-[200px] sm:max-w-none">
                    <span>📍</span>
                    <strong class="text-white truncate" x-text="originLabel"></strong>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span>🚚</span>
                    <strong class="text-white" x-text="getVehicleSummaryText()"></strong>
                </div>
                <!-- KM Range Radius Snapshot Pill -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span>📏</span>
                    <strong class="text-white" x-text="maxDistance ? (maxDistance + ' km {{ $isEn ? 'Radius' : 'ವ್ಯಾಪ್ತಿ' }}') : '{{ $isEn ? 'All Karnataka' : 'ಇಡೀ ಕರ್ನಾಟಕ' }}'"></strong>
                </div>
                <!-- Round-Trip Snapshot Pill -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/15" x-show="rateType === 'per_km' || rateType === 'fuel_only'">
                    <span>🔄</span>
                    <strong :class="roundTrip ? 'text-amber-200' : 'text-stone-300'" x-text="roundTrip ? '{{ $isEn ? 'Round-Trip (2x)' : 'ರೌಂಡ್ ಟ್ರಿಪ್ (2x)' }}' : '{{ $isEn ? 'One-Way' : 'ಒಂದು ಬದಿ' }}'"></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. SIMULATOR COMMAND CONTROLS (PWA Mobile First & Parchment Heritage)     -->
    <!-- ========================================================================= -->
    <div class="bg-[#FCFAF6] rounded-3xl border-2 border-[#D9CEB8] shadow-md p-4 sm:p-6 space-y-5 relative">
        
        <!-- ACTIVE BASELINE CONTEXT CAPSULE BANNER (From Crop Details) -->
        <template x-if="baselineMarket && isCustomBaseline">
            <div class="rounded-2xl p-3.5 sm:p-4 bg-gradient-to-r from-[#092614] via-[#0d381c] to-[#124b27] text-white border-2 border-emerald-400/40 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-emerald-800/80 border border-emerald-400 flex items-center justify-center text-xl shrink-0 shadow-inner">🎯</span>
                    <div class="space-y-0.5">
                        <div class="text-[10px] sm:text-xs uppercase tracking-wider font-extrabold text-emerald-300 flex items-center gap-1.5">
                            <span>{{ $isEn ? 'Comparison Baseline Mandi (From Crop Page):' : 'ಆಯ್ಕೆಯ ಆಧಾರ ಮಂಡಿ (ಬೆಳೆ ಪುಟದಿಂದ ಸ್ವೀಕರಿಸಲಾಗಿದೆ):' }}</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        </div>
                        <div class="text-sm sm:text-base font-black text-white flex flex-wrap items-center gap-2">
                            <span x-text="locale === 'en' ? baselineMarket.market_name : (baselineMarket.market_name_kn || baselineMarket.market_name)"></span>
                            <span class="text-amber-300 font-mono" x-text="'(₹' + formatNumber(baselineMarket.modal_price) + '/Q)'"></span>
                            <span class="px-2.5 py-0.5 rounded-full bg-white/10 text-emerald-200 text-xs font-sans font-semibold border border-white/15" x-text="locale === 'en' ? baselineMarket.freshness_badge_en : baselineMarket.freshness_badge_kn"></span>
                            <template x-if="getVarietyDisplayName()">
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-200 border border-amber-400/30 text-xs font-medium" x-text="'🏷️ ' + getVarietyDisplayName()"></span>
                            </template>
                        </div>
                    </div>
                </div>
                <button type="button" 
                        @click="clearBaselineMarket()" 
                        class="self-start sm:self-center px-3.5 py-2 rounded-xl bg-white/15 hover:bg-white/25 active:scale-95 border border-white/20 text-xs font-bold text-white transition cursor-pointer flex items-center gap-1.5 shrink-0 shadow-xs">
                    <span>🔄</span>
                    <span>{{ $isEn ? 'Switch to Nearest GPS' : 'ಹತ್ತಿರದ ಮಂಡಿಗೆ ಮರುಹೊಂದಿಸಿ (GPS)' }}</span>
                </button>
            </div>
        </template>

        <!-- CONTEXTUAL COMMODITY SUMMARY CARD (Shown when user navigates from specific Crop page) -->
        <div x-show="isContextual && !isCropSelectionExpanded" 
             x-cloak
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="rounded-2xl p-3.5 sm:p-4 bg-gradient-to-r from-[#092614] via-[#0d381c] to-[#124b27] text-white border-2 border-emerald-400/50 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-2xl overflow-hidden bg-white/10 border-2 border-emerald-400/40 shrink-0 p-0.5 flex items-center justify-center">
                    <template x-if="selectedCrop && selectedCrop.photo_url">
                        <img :src="selectedCrop.photo_url" :alt="getCropDisplayName()" class="w-full h-full object-cover rounded-xl">
                    </template>
                    <template x-if="!selectedCrop || !selectedCrop.photo_url">
                        <span class="text-2xl">🌱</span>
                    </template>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            {{ $isEn ? 'Selected Commodity' : 'ಆಯ್ಕೆಯಾದ ಬೆಳೆ & ತಳಿ' }}
                        </span>
                        <span class="text-[11px] text-emerald-200/80 font-sans">({{ $isEn ? 'From Crop Page' : 'ಬೆಳೆ ಪುಟದಿಂದ' }})</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-base sm:text-lg font-black text-white" x-text="getCropDisplayName()"></h3>
                        <template x-if="getVarietyDisplayName()">
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-bold font-sans" x-text="'🏷️ ' + getVarietyDisplayName()"></span>
                        </template>
                    </div>
                </div>
            </div>

            <button type="button" 
                    @click="isCropSelectionExpanded = true; $nextTick(() => { scrollToSelectedCrop(); scrollToSelectedVariety(); })"
                    class="self-start sm:self-center px-3.5 py-2 rounded-xl bg-white/15 hover:bg-white/25 active:scale-95 border border-white/20 text-xs font-bold text-white transition cursor-pointer flex items-center gap-1.5 shrink-0 shadow-xs">
                <span>🔄</span>
                <span>{{ $isEn ? 'Change Crop / Variety ▾' : 'ಬೆಳೆ ಅಥವಾ ತಳಿ ಬದಲಾಯಿಸಿ ▾' }}</span>
            </button>
        </div>

        <!-- FULL CROP & VARIETY SELECTOR CONTAINER -->
        <div x-show="!isContextual || isCropSelectionExpanded" x-cloak class="space-y-3">
            <div x-show="isContextual && isCropSelectionExpanded" class="flex items-center justify-between pb-1 border-b border-stone-200">
                <span class="text-xs text-amber-900 font-bold bg-amber-100 px-2.5 py-0.5 rounded-lg border border-amber-200">
                    {{ $isEn ? 'Showing all crops & varieties' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳು & ತಳಿಗಳನ್ನು ತೋರಿಸಲಾಗುತ್ತಿದೆ' }}
                </span>
                <button type="button" 
                        @click="isCropSelectionExpanded = false" 
                        class="text-xs font-bold text-stone-700 hover:text-stone-900 bg-stone-100 hover:bg-stone-200 px-2.5 py-1 rounded-lg border border-stone-300 flex items-center gap-1 cursor-pointer">
                    <span>▲</span>
                    <span>{{ $isEn ? 'Collapse Selection' : 'ಆಯ್ಕೆ ಮುಚ್ಚಿ (Collapse)' }}</span>
                </button>
            </div>

            <!-- STEP 1: HORIZONTAL SNAP-SWIPE CROP CAROUSEL -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-emerald-700 text-white flex items-center justify-center text-[10px] font-mono">1</span>
                        <span>{{ $isEn ? 'Select Traded Crop:' : 'ಬೆಳೆ ಆಯ್ಕೆ ಮಾಡಿ (Select Crop):' }}</span>
                    </label>
                    
                    <button type="button" 
                            @click="cropSearchModal = true"
                            class="text-xs font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 cursor-pointer bg-emerald-100/60 px-2.5 py-1 rounded-lg border border-emerald-200">
                        <span>🔍 {{ $isEn ? 'All Crops (' . $crops->count() . ')' : 'ಎಲ್ಲಾ ' . $crops->count() . ' ಬೆಳೆಗಳು' }}</span>
                    </button>
                </div>

                <!-- Single Row Horizontal Touch Carousel -->
                <div id="cropCarouselContainer" class="flex items-center gap-2.5 overflow-x-auto snap-x snap-mandatory py-1 px-0.5 scroll-smooth">
                    @foreach($crops as $c)
                        <button type="button" 
                                id="crop-card-{{ $c->slug }}"
                                @click="selectCrop(@js(['id' => $c->id, 'slug' => $c->slug, 'name' => $c->name, 'name_kn' => $c->name_kn ?? $c->name, 'photo_url' => $c->photo_url, 'varieties' => $c->varieties]))"
                                :class="selectedCrop && selectedCrop.slug === '{{ $c->slug }}' 
                                    ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-500/30 shadow-md scale-[1.02]' 
                                    : 'bg-white border-stone-200 hover:border-emerald-300 hover:bg-stone-50'"
                                class="snap-start shrink-0 flex flex-col items-center justify-between p-2 rounded-2xl border transition text-center group cursor-pointer relative w-24 sm:w-28 h-28">
                            
                            <div x-show="selectedCrop && selectedCrop.slug === '{{ $c->slug }}'" 
                                 class="absolute top-1 right-1 w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[8px] font-bold shadow-xs">
                                ✓
                            </div>

                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-stone-100 border border-stone-200/80 mb-1 shrink-0">
                                @if(!empty($c->photo_url))
                                    <img src="{{ $c->photo_url }}" alt="{{ $c->name }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xl">🌱</div>
                                @endif
                            </div>

                            <span class="text-xs font-bold text-stone-900 group-hover:text-emerald-800 line-clamp-1">
                                {{ $isEn ? $c->name : ($c->name_kn ?? $c->name) }}
                            </span>
                            <span class="text-[9px] text-stone-500 truncate w-full font-sans">
                                {{ $isEn ? ($c->name_kn ?? '') : $c->name }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- STEP 1B: DYNAMIC VARIETY (ತಳಿ / ಪ್ರಭೇದ) SELECTOR RIBBON -->
            <div class="space-y-2 pt-1 border-t border-stone-200/60" x-show="selectedCrop && selectedCrop.varieties && selectedCrop.varieties.length > 0">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-amber-600 text-white flex items-center justify-center text-[10px] font-mono">1B</span>
                        <span>{{ $isEn ? 'Commercial Variety / Grade Selection:' : 'ತಳಿ / ಪ್ರಭೇದ ಆಯ್ಕೆ (Variety Selection):' }}</span>
                    </label>
                    <span class="text-[10px] text-stone-500 font-medium">
                        {{ $isEn ? 'Accurate like-for-like mandi price comparison' : 'ನಿಖರವಾದ ಸಮಾನ ತಳಿ ದರ ಹೋಲಿಕೆ' }}
                    </span>
                </div>

                <!-- Variety Chips Container -->
                <div id="varietyCarouselContainer" class="flex items-center gap-2 overflow-x-auto py-1.5 px-0.5 scroll-smooth snap-x snap-mandatory">
                    <!-- All Varieties Pill (Default) -->
                    <button type="button" 
                            id="variety-chip-all"
                            @click="selectVariety(null)"
                            :class="!varietyId ? 'bg-amber-500 text-slate-950 font-black shadow-xs border-amber-600 ring-2 ring-amber-400/40 scale-[1.02]' : 'bg-white text-stone-700 border-stone-200 hover:border-amber-400 hover:bg-stone-50'"
                            class="snap-start shrink-0 px-3.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                        <span x-show="!varietyId" class="w-3.5 h-3.5 rounded-full bg-slate-950 text-white flex items-center justify-center text-[8px] font-bold">✓</span>
                        <span x-show="varietyId">🌾</span>
                        <span>{{ $isEn ? 'All Varieties (Standard)' : 'ಎಲ್ಲಾ ತಳಿಗಳು (ಸಾಮಾನ್ಯ / ಸರಾಸರಿ)' }}</span>
                    </button>

                    <!-- Specific Varieties -->
                    <template x-for="v in (selectedCrop ? (selectedCrop.varieties || []) : [])" :key="v.id">
                        <button type="button" 
                                :id="'variety-chip-' + v.id"
                                @click="selectVariety(v.id)"
                                :class="varietyId == v.id ? 'bg-amber-500 text-slate-950 font-black shadow-xs border-amber-600 ring-2 ring-amber-400/40 scale-[1.02]' : 'bg-white text-stone-700 border-stone-200 hover:border-amber-400 hover:bg-stone-50'"
                                class="snap-start shrink-0 px-3.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                            <span x-show="varietyId == v.id" class="w-3.5 h-3.5 rounded-full bg-slate-950 text-white flex items-center justify-center text-[8px] font-bold">✓</span>
                            <span x-text="locale === 'en' ? v.name : (v.name_kn || v.name)"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- STEP 2 & 3: QUANTITY STEPPER & LOCATION CARD (2-Col Responsive Grid) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-1 border-t border-stone-200/80">
            
            <!-- 2. QUANTITY STEPPER -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-stone-200/90 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-emerald-700 text-white flex items-center justify-center text-[10px] font-mono">2</span>
                        <span>{{ $isEn ? 'Harvest Quantity:' : 'ಕಟಾವು ಪ್ರಮಾಣ (Harvest Quantity):' }}</span>
                    </label>
                    <span class="text-[10px] text-stone-500 font-bold uppercase">{{ $isEn ? 'Quintals (100 kg)' : 'ಕ್ವಿಂಟಾಲ್ (100 ಕೆಜಿ)' }}</span>
                </div>

                <!-- Stepper Widget -->
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="adjustQuantity(-5)"
                            class="w-10 h-10 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 text-lg font-black transition flex items-center justify-center border border-stone-300 cursor-pointer active:scale-95 shrink-0"
                            title="Subtract 5 Quintals">
                        −
                    </button>
                    
                    <div class="relative flex-1">
                        <input type="number" 
                               x-model.number="quantity"
                               @input="hasUncalculatedChanges = true"
                               step="0.5" 
                               min="0.5" 
                               max="2000"
                               class="w-full text-center text-xl sm:text-2xl font-black rounded-xl border-stone-300 focus:border-emerald-500 focus:ring-emerald-500 py-1.5 text-stone-900 bg-stone-50 font-mono">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-stone-500 uppercase pointer-events-none">
                            Qtl
                        </span>
                    </div>

                    <button type="button" 
                            @click="adjustQuantity(5)"
                            class="w-10 h-10 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 text-lg font-black transition flex items-center justify-center border border-stone-300 cursor-pointer active:scale-95 shrink-0"
                            title="Add 5 Quintals">
                        +
                    </button>
                </div>

                <!-- Quick Preset Weight Chips -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button type="button" @click="setQuantity(5)" :class="quantity === 5 ? 'bg-emerald-800 text-white border-emerald-800' : 'bg-stone-50 text-stone-700 border-stone-200'" class="px-2.5 py-1 text-xs font-bold rounded-lg border hover:border-emerald-400 transition cursor-pointer">5 Q</button>
                    <button type="button" @click="setQuantity(10)" :class="quantity === 10 ? 'bg-emerald-800 text-white border-emerald-800' : 'bg-stone-50 text-stone-700 border-stone-200'" class="px-2.5 py-1 text-xs font-bold rounded-lg border hover:border-emerald-400 transition cursor-pointer">10 Q (1 T)</button>
                    <button type="button" @click="setQuantity(20)" :class="quantity === 20 ? 'bg-emerald-800 text-white border-emerald-800' : 'bg-stone-50 text-stone-700 border-stone-200'" class="px-2.5 py-1 text-xs font-bold rounded-lg border hover:border-emerald-400 transition cursor-pointer">20 Q (2 T)</button>
                    <button type="button" @click="setQuantity(50)" :class="quantity === 50 ? 'bg-emerald-800 text-white border-emerald-800' : 'bg-stone-50 text-stone-700 border-stone-200'" class="px-2.5 py-1 text-xs font-bold rounded-lg border hover:border-emerald-400 transition cursor-pointer">50 Q (5 T)</button>
                    <button type="button" @click="setQuantity(100)" :class="quantity === 100 ? 'bg-emerald-800 text-white border-emerald-800' : 'bg-stone-50 text-stone-700 border-stone-200'" class="px-2.5 py-1 text-xs font-bold rounded-lg border hover:border-emerald-400 transition cursor-pointer">100 Q (10 T)</button>
                </div>

                <div class="text-[11px] text-stone-500 font-medium flex items-center justify-between pt-0.5">
                    <span>💡 {{ $isEn ? 'Est:' : 'ಅಂದಾಜು:' }} <strong class="text-stone-800" x-text="Math.round(quantity * 100) + ' {{ $isEn ? 'kg' : 'ಕೆಜಿ' }}'"></strong> (<span x-text="Math.round(quantity * 2) + ' {{ $isEn ? 'bags' : 'ಚೀಲಗಳು' }}'"></span>)</span>
                </div>
            </div>

            <!-- 3. ORIGIN LOCATION & MAP PIN CARD -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-stone-200/90 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-emerald-700 text-white flex items-center justify-center text-[10px] font-mono">3</span>
                        <span>{{ $isEn ? 'Your Farm / Origin Location:' : 'ನಿಮ್ಮ ತೋಟ / ಗ್ರಾಮದ ಸ್ಥಳ (Your Origin):' }}</span>
                    </label>
                    <span class="text-[10px] text-stone-500">{{ $isEn ? 'Road Distance Engine' : 'ರಸ್ತೆ ದೂರದ ಲೆಕ್ಕಾಚಾರ' }}</span>
                </div>

                <!-- GPS Location Button with Status -->
                <button type="button" 
                        @click="acquireGpsLocation()"
                        :disabled="gpsLoading"
                        :class="gpsActive ? 'bg-emerald-900 text-emerald-100 border-emerald-700 shadow-sm' : 'bg-stone-50 hover:bg-emerald-50 text-stone-800 border-stone-300 hover:border-emerald-300'"
                        class="w-full inline-flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition border cursor-pointer active:scale-98">
                    <div class="flex items-center gap-2">
                        <span :class="gpsLoading ? 'animate-spin' : (gpsActive ? 'animate-pulse' : '')" class="text-sm">
                            <template x-if="gpsLoading">⏳</template>
                            <template x-if="!gpsLoading && gpsActive">📍</template>
                            <template x-if="!gpsLoading && !gpsActive">🎯</template>
                        </span>
                        <span class="text-left" x-text="gpsLabelText"></span>
                    </div>
                    
                    <span class="px-2 py-0.5 rounded-md text-[10px] bg-emerald-700 text-white font-mono" x-show="lat && lng" x-text="roundCoord(lat) + ', ' + roundCoord(lng)"></span>
                    <span class="text-[10px] text-stone-400 font-mono" x-show="!lat || !lng">Tap ➔</span>
                </button>

                <!-- District & Taluk Fallback Selectors -->
                <div class="grid grid-cols-2 gap-2 pt-0.5">
                    <div>
                        <select x-model="districtId" 
                                @change="onDistrictChange()" 
                                class="w-full text-xs font-bold rounded-xl border-stone-300 bg-white py-1.5 px-2 focus:border-emerald-500 focus:ring-emerald-500 cursor-pointer">
                            <option value="">{{ $isEn ? '— Select District —' : '— ಜಿಲ್ಲೆ (District) —' }}</option>
                            @foreach($districts as $d)
                                <option value="{{ $d->id }}">
                                    {{ $isEn ? $d->name : ($d->name_kn ?? $d->name) }} ({{ $d->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select x-model="talukId" 
                                @change="onTalukChange()"
                                class="w-full text-xs font-bold rounded-xl border-stone-300 bg-white py-1.5 px-2 focus:border-emerald-500 focus:ring-emerald-500 cursor-pointer">
                            <option value="">{{ $isEn ? '— Select Taluk —' : '— ತಾಲೂಕು (Taluk) —' }}</option>
                            <template x-for="t in currentTaluks" :key="t.id">
                                <option :value="t.id" x-text="locale === 'en' ? t.name : (t.name_kn || t.name)"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="text-[11px] text-stone-500 flex items-center justify-between pt-0.5">
                    <span>{{ $isEn ? 'Active Anchor:' : 'ಪ್ರಸ್ತುತ ಲೆಕ್ಕ ಕೇಂದ್ರ:' }}</span>
                    <strong class="text-emerald-900 font-bold truncate max-w-[190px]" x-text="originLabel"></strong>
                </div>

                <!-- KM Range / Radius Filter Chips -->
                <div class="pt-2 border-t border-stone-200/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-[11px] font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1">
                            <span>📏</span>
                            <span>{{ $isEn ? 'Search Radius / KM Range:' : 'ಹುಡುಕಾಟದ ವ್ಯಾಪ್ತಿ (Search Radius):' }}</span>
                        </label>
                        <span class="text-[10px] text-stone-500 font-medium" x-text="maxDistance ? (maxDistance + ' km {{ $isEn ? 'radius' : 'ದೂರದೊಳಗೆ' }}') : '{{ $isEn ? 'All Karnataka' : 'ಇಡೀ ಕರ್ನಾಟಕ' }}'"></span>
                    </div>

                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" 
                                @click="setMaxDistance(50)" 
                                :class="maxDistance === 50 ? 'bg-emerald-800 text-white font-black shadow-xs border-emerald-800 ring-2 ring-emerald-500/20' : 'bg-stone-50 text-stone-700 border-stone-200 hover:border-emerald-400 hover:bg-stone-100'" 
                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer flex items-center gap-1">
                            <span x-show="maxDistance === 50" class="text-[9px]">✓</span>
                            <span>50 km</span>
                        </button>
                        <button type="button" 
                                @click="setMaxDistance(100)" 
                                :class="maxDistance === 100 ? 'bg-emerald-800 text-white font-black shadow-xs border-emerald-800 ring-2 ring-emerald-500/20' : 'bg-stone-50 text-stone-700 border-stone-200 hover:border-emerald-400 hover:bg-stone-100'" 
                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer flex items-center gap-1">
                            <span x-show="maxDistance === 100" class="text-[9px]">✓</span>
                            <span>100 km</span>
                        </button>
                        <button type="button" 
                                @click="setMaxDistance(150)" 
                                :class="maxDistance === 150 ? 'bg-emerald-800 text-white font-black shadow-xs border-emerald-800 ring-2 ring-emerald-500/20' : 'bg-stone-50 text-stone-700 border-stone-200 hover:border-emerald-400 hover:bg-stone-100'" 
                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer flex items-center gap-1">
                            <span x-show="maxDistance === 150" class="text-[9px]">✓</span>
                            <span>150 km</span>
                        </button>
                        <button type="button" 
                                @click="setMaxDistance(200)" 
                                :class="maxDistance === 200 ? 'bg-emerald-800 text-white font-black shadow-xs border-emerald-800 ring-2 ring-emerald-500/20' : 'bg-stone-50 text-stone-700 border-stone-200 hover:border-emerald-400 hover:bg-stone-100'" 
                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer flex items-center gap-1">
                            <span x-show="maxDistance === 200" class="text-[9px]">✓</span>
                            <span>200 km</span>
                        </button>
                        <button type="button" 
                                @click="setMaxDistance(null)" 
                                :class="maxDistance === null ? 'bg-emerald-800 text-white font-black shadow-xs border-emerald-800 ring-2 ring-emerald-500/20' : 'bg-stone-50 text-stone-700 border-stone-200 hover:border-emerald-400 hover:bg-stone-100'" 
                                class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer flex items-center gap-1">
                            <span x-show="maxDistance === null" class="text-[9px]">✓</span>
                            <span>{{ $isEn ? 'All Karnataka' : 'ಇಡೀ ಕರ್ನಾಟಕ' }}</span>
                        </button>
                    </div>

                    <p class="text-[10px] text-stone-500 font-sans">
                        {{ $isEn 
                            ? 'Evaluates only mandis within this distance from your farm origin.' 
                            : 'ನಿಮ್ಮ ಸ್ಥಳದಿಂದ ಈ ರಸ್ತೆ ದೂರದೊಳಗಿನ ಮಂಡಿಗಳನ್ನು ಮಾತ್ರ ಹೋಲಿಸಲಾಗುತ್ತದೆ.' }}
                    </p>
                </div>
            </div>

        </div>

        <!-- STEP 4: ENHANCED TRANSPORT VEHICLE & 4-MODE FLEXIBLE RATE SELECTOR -->
        <div class="pt-2 border-t border-stone-200/80 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                <label class="block text-xs font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-700 text-white flex items-center justify-center text-[10px] font-mono">4</span>
                    <span>{{ $isEn ? 'Transport Vehicle & Freight Rates:' : 'ಸಾರಿಗೆ ವಾಹನ ಮತ್ತು ಸಾಗಣೆ ದರ (Transport & Rates):' }}</span>
                </label>
                
                <span class="text-[11px] text-stone-500 font-medium">
                    {{ $isEn ? 'Easily adjust rates to match your local market freight' : 'ನಿಮ್ಮ ಊರಿನ ಸಾರಿಗೆ ದರಕ್ಕೆ ಅನುಗುಣವಾಗಿ ತಕ್ಷಣ ಬದಲಾಯಿಸಿ' }}
                </span>
            </div>

            <!-- 4-Mode Rate Type Pill Switcher -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 text-xs">
                <button type="button" 
                        @click="setRateType('per_km')"
                        :class="rateType === 'per_km' ? 'bg-emerald-900 text-white shadow-xs' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'"
                        class="px-2.5 py-1 rounded-lg font-bold transition shrink-0 cursor-pointer">
                    {{ $isEn ? '🛣️ Per Km Rate (₹ / km)' : '🛣️ ಪ್ರತಿ ಕಿ.ಮೀ ದರ (₹ / km)' }}
                </button>
                <button type="button" 
                        @click="setRateType('per_quintal')"
                        :class="rateType === 'per_quintal' ? 'bg-emerald-900 text-white shadow-xs' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'"
                        class="px-2.5 py-1 rounded-lg font-bold transition shrink-0 cursor-pointer">
                    {{ $isEn ? '📦 Per Bag / Qtl (₹ / Qtl)' : '📦 ಚೀಲ / ಕ್ವಿಂಟಾಲ್‌ಗೆ (₹ / Qtl)' }}
                </button>
                <button type="button" 
                        @click="setRateType('fixed_fare')"
                        :class="rateType === 'fixed_fare' ? 'bg-emerald-900 text-white shadow-xs' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'"
                        class="px-2.5 py-1 rounded-lg font-bold transition shrink-0 cursor-pointer">
                    {{ $isEn ? '🤝 Fixed Flat Trip (Lumpsum ₹)' : '🤝 ಒಟ್ಟು ಸ್ಥಿರ ಬಾಡಿಗೆ (Lumpsum ₹)' }}
                </button>
                <button type="button" 
                        @click="setRateType('fuel_only')"
                        :class="rateType === 'fuel_only' ? 'bg-emerald-900 text-white shadow-xs' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'"
                        class="px-2.5 py-1 rounded-lg font-bold transition shrink-0 cursor-pointer">
                    {{ $isEn ? '🚜 Own Vehicle / Diesel Only' : '🚜 ಸ್ವಂತ ವಾಹನ / ಇಂಧನ ಮಾತ್ರ' }}
                </button>
            </div>

            <!-- Vehicle Selection Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                @foreach($vehicles as $k => $v)
                    <div @click="selectVehicle('{{ $k }}')"
                         :class="vehicle === '{{ $k }}' 
                            ? 'bg-emerald-50/90 border-emerald-600 ring-2 ring-emerald-500/20 shadow-md' 
                            : 'bg-white border-stone-200 hover:border-emerald-300 hover:bg-stone-50'"
                         class="relative flex flex-col justify-between p-3 rounded-2xl border-2 cursor-pointer transition">
                        
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">{{ $v['icon'] }}</span>
                                <div>
                                    <div class="text-xs font-black text-stone-900">
                                        {{ $isEn ? $v['name_en'] : $v['name_kn'] }}
                                    </div>
                                    <div class="text-[10px] text-stone-500 font-sans">
                                        {{ $isEn ? $v['name_kn'] : $v['name_en'] }}
                                    </div>
                                </div>
                            </div>
                            <input type="radio" 
                                   name="vehicleRadio" 
                                   value="{{ $k }}" 
                                   :checked="vehicle === '{{ $k }}'" 
                                   class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                        </div>

                        <div class="mt-2 pt-2 border-t border-stone-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-stone-500 block text-[9px]">{{ $isEn ? 'Base Rate:' : 'ಸಾಮಾನ್ಯ ದರ:' }}</span>
                                <strong class="text-emerald-900 font-black">₹{{ $v['rate_per_km'] }}/km</strong>
                            </div>
                            <div class="text-right">
                                <span class="text-stone-500 block text-[9px]">{{ $isEn ? 'Payload Limit:' : 'ಸಾಮರ್ಥ್ಯ:' }}</span>
                                <strong class="text-stone-800 font-bold">~{{ $v['max_capacity_qtl'] }} Qtl</strong>
                            </div>
                        </div>

                        <!-- Capacity Warning Pill -->
                        <div x-show="quantity > {{ $v['max_capacity_qtl'] }}" 
                             class="mt-1.5 text-[9px] font-bold text-amber-800 bg-amber-100/90 px-1.5 py-0.5 rounded flex items-center gap-1">
                            <span>⚠️</span>
                            <span>{{ $isEn ? 'Payload Overload Warning' : 'ಸಾಮರ್ಥ್ಯ ಮೀರಿದೆ (Overload)' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Round-Trip Freight (2x Mileage) Toggle Card -->
            <div class="rounded-2xl p-3 bg-white border border-stone-200 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-base">🔄</span>
                        <span class="font-black text-stone-900">
                            {{ $isEn ? 'Round-Trip Vehicle Freight (2-Way Mileage)' : 'ಹೋಗಿ ಬರುವ ಸಾರಿಗೆ ವೆಚ್ಚ (Round-Trip 2x)' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                              :class="roundTrip ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-stone-200 text-stone-600'">
                            <span x-text="roundTrip ? '{{ $isEn ? '2x Mileage Active' : '2x ದೂರ ಅನ್ವಯವಾಗಿದೆ' }}' : '{{ $isEn ? '1-Way Mileage' : 'ಒಂದು ಬದಿ ದೂರ' }}'"></span>
                        </span>
                    </div>
                    <p class="text-[11px] text-stone-600 leading-tight">
                        {{ $isEn 
                            ? 'Hired vehicles (Ape/Bolero/407) charge for returning to your village empty. Turn OFF if paying one-way freight only.' 
                            : 'ಗ್ರಾಮೀಣ ಬಾಡಿಗೆ ವಾಹನಗಳು ಮಂಡಿಯಿಂದ ಗ್ರಾಮಕ್ಕೆ ಖಾಲಿ ವಾಪಸ್ ಬರುವ ಪ್ರಯಾಣಕ್ಕೂ ಬಾಡಿಗೆ ಪಡೆಯುತ್ತವೆ. ಕೇವಲ ಒಂದು ಬದಿಯ ಬಾಡಿಗೆಯಾದರೆ ಇದನ್ನು ಆಫ್ (OFF) ಮಾಡಿ.' }}
                        <span x-show="rateType === 'per_quintal' || rateType === 'fixed_fare'" class="text-amber-800 font-bold block pt-0.5">
                            ({{ $isEn ? 'Note: Per-quintal and fixed fare modes are billed per quantity/trip and are not doubled.' : 'ಸೂಚನೆ: ಕ್ವಿಂಟಾಲ್ ದರ ಅಥವಾ ಸ್ಥಿರ ಒಟ್ಟು ಬಾಡಿಗೆಯಲ್ಲಿ ಇದು ದುಪ್ಪಟ್ಟಾಗುವುದಿಲ್ಲ.' }})
                        </span>
                    </p>
                </div>

                <!-- Modern Accessible Toggle Switch -->
                <div class="flex items-center gap-2.5 shrink-0 self-start sm:self-center">
                    <button type="button" 
                            @click="toggleRoundTrip()"
                            :aria-pressed="roundTrip"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                            :class="roundTrip ? 'bg-emerald-600' : 'bg-stone-300'">
                        <span class="sr-only">Toggle Round Trip</span>
                        <span aria-hidden="true" 
                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
                              :class="roundTrip ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                    <span class="font-extrabold text-xs" :class="roundTrip ? 'text-emerald-900' : 'text-stone-500'" x-text="roundTrip ? '{{ $isEn ? 'ON (2-Way)' : 'ಆನ್ (2-ಬದಿ)' }}' : '{{ $isEn ? 'OFF (1-Way)' : 'ಆಫ್ (1-ಬದಿ)' }}'"></span>
                </div>
            </div>

            <!-- In-Place Editable Rate Drawer / Custom Rate Stepper -->
            <div class="bg-amber-50/80 rounded-2xl p-3 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="space-y-0.5">
                    <div class="font-bold text-amber-950 flex items-center gap-1.5">
                        <span>✏️</span>
                        <span x-text="getRateTypeDescriptionTitle()"></span>
                    </div>
                    <p class="text-[11px] text-amber-900 leading-tight" x-text="getRateTypeDescriptionHelp()"></p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" 
                            @click="adjustCustomRate(-1)" 
                            class="w-8 h-8 rounded-lg bg-amber-200 hover:bg-amber-300 text-amber-950 font-black flex items-center justify-center text-sm cursor-pointer active:scale-95">
                        −
                    </button>
                    
                    <div class="relative w-28">
                        <input type="number" 
                               x-model.number="customRate"
                               @input="hasUncalculatedChanges = true"
                               step="0.5" 
                               min="1" 
                               max="10000" 
                               class="w-full text-center text-sm font-black rounded-lg border-amber-300 bg-white focus:ring-amber-500 focus:border-amber-500 py-1 font-mono">
                    </div>

                    <button type="button" 
                            @click="adjustCustomRate(1)" 
                            class="w-8 h-8 rounded-lg bg-amber-200 hover:bg-amber-300 text-amber-950 font-black flex items-center justify-center text-sm cursor-pointer active:scale-95">
                        +
                    </button>

                    <span class="font-black text-amber-950 text-xs" x-text="getRateUnitLabel()"></span>
                </div>
            </div>
        </div>

        <!-- STEP 5: SORTING TABS & PRIMARY CALCULATE CTA -->
        <div class="pt-3 border-t border-stone-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-bold text-stone-500 uppercase tracking-wider">{{ $isEn ? 'Sort by:' : 'ವಿಂಗಡಣೆ:' }}</span>
                <div class="inline-flex rounded-xl shadow-xs border border-stone-200 p-0.5 bg-white">
                    <button type="button" 
                            @click="setSort('net_realization')" 
                            :class="sort === 'net_realization' ? 'bg-emerald-800 text-white shadow-xs' : 'text-stone-600 hover:text-emerald-800'"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer flex items-center gap-1">
                        <span>🏆</span>
                        <span>{{ $isEn ? 'Net Profit' : 'ನಿವ್ವಳ ಲಾಭ' }}</span>
                    </button>
                    <button type="button" 
                            @click="setSort('distance_asc')" 
                            :class="sort === 'distance_asc' ? 'bg-emerald-800 text-white shadow-xs' : 'text-stone-600 hover:text-emerald-800'"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer flex items-center gap-1">
                        <span>📍</span>
                        <span>{{ $isEn ? 'Shortest Distance' : 'ಕಡಿಮೆ ದೂರ' }}</span>
                    </button>
                    <button type="button" 
                            @click="setSort('price_desc')" 
                            :class="sort === 'price_desc' ? 'bg-emerald-800 text-white shadow-xs' : 'text-stone-600 hover:text-emerald-800'"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer flex items-center gap-1">
                        <span>📈</span>
                        <span>{{ $isEn ? 'Mandi Price' : 'ಮಂಡಿ ದರ' }}</span>
                    </button>
                </div>
            </div>

            <!-- Primary Async Action CTA -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <span x-show="hasUncalculatedChanges && !isLoading" 
                      x-cloak
                      class="text-[11px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-3 py-1.5 rounded-xl shadow-xs animate-pulse flex items-center gap-1.5 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span>{{ $isEn ? 'Settings updated — Click to Calculate' : 'ಬದಲಾವಣೆಗಳಿವೆ — ಲೆಕ್ಕಾಚಾರಕ್ಕೆ ಕ್ಲಿಕ್ ಮಾಡಿ' }}</span>
                </span>

                <button type="button" 
                        @click="fetchComparison(true)"
                        :disabled="isLoading"
                        :class="hasUncalculatedChanges 
                            ? 'from-emerald-600 via-emerald-500 to-teal-600 hover:from-emerald-500 hover:to-teal-500 ring-4 ring-emerald-400/40 shadow-xl scale-[1.02]' 
                            : 'from-emerald-800 via-emerald-700 to-teal-800 hover:from-emerald-700 hover:to-teal-700 shadow-md'"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-2xl bg-gradient-to-r text-white font-black text-xs sm:text-sm transition-all duration-200 active:scale-95 cursor-pointer">
                    <span x-show="!isLoading" class="flex items-center gap-1.5">
                        <span x-show="hasUncalculatedChanges" class="text-amber-300">●</span>
                        <span>⚡ {{ $isEn ? 'Calculate Realization' : 'ಲೆಕ್ಕಾಚಾರ ಮಾಡಿ (Calculate Realization)' }}</span>
                    </span>
                    <span x-show="isLoading" class="flex items-center gap-1.5">
                        <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ $isEn ? 'Simulating Realization...' : 'ಲೆಕ್ಕಾಚಾರ ಮಾಡಲಾಗುತ್ತಿದೆ...' }}</span>
                    </span>
                    <span x-show="!isLoading" class="text-sm">➔</span>
                </button>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- SOFT SHIMMER SKELETON LOADING STATE (Async Calculation in Flight)        -->
    <!-- ========================================================================= -->
    <div x-show="isLoading" 
         x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-out duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-[0.99]"
         class="space-y-4 select-none">
        
        <!-- Shimmer Champion Card Skeleton -->
        <div class="rounded-3xl p-5 sm:p-7 bg-gradient-to-br from-[#092614] via-[#0d381c] to-[#06180c] border-2 border-amber-400/30 text-white shadow-xl space-y-4">
            <div class="flex items-center gap-2">
                <div class="h-6 w-52 rounded-full bg-amber-400/30 kb-shimmer-box"></div>
            </div>
            <div class="space-y-2">
                <div class="h-8 w-64 sm:w-80 rounded-xl bg-white/20 kb-shimmer-box"></div>
                <div class="h-4 w-48 rounded-lg bg-emerald-400/20 kb-shimmer-box"></div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                <div class="h-20 rounded-2xl bg-white/10 border border-white/10 p-3 flex flex-col justify-between kb-shimmer-box">
                    <div class="h-3 w-16 bg-white/20 rounded"></div>
                    <div class="h-6 w-24 bg-white/30 rounded"></div>
                </div>
                <div class="h-20 rounded-2xl bg-white/10 border border-white/10 p-3 flex flex-col justify-between kb-shimmer-box">
                    <div class="h-3 w-16 bg-white/20 rounded"></div>
                    <div class="h-6 w-24 bg-white/30 rounded"></div>
                </div>
                <div class="h-20 rounded-2xl bg-white/10 border border-white/10 p-3 flex flex-col justify-between kb-shimmer-box">
                    <div class="h-3 w-16 bg-white/20 rounded"></div>
                    <div class="h-6 w-24 bg-white/30 rounded"></div>
                </div>
                <div class="h-20 rounded-2xl bg-white/10 border border-white/10 p-3 flex flex-col justify-between kb-shimmer-box">
                    <div class="h-3 w-16 bg-white/20 rounded"></div>
                    <div class="h-6 w-24 bg-white/30 rounded"></div>
                </div>
            </div>
        </div>

        <!-- Shimmer Map Container Skeleton -->
        <div class="h-72 sm:h-80 rounded-3xl bg-stone-200/90 border-2 border-stone-300/80 flex flex-col items-center justify-center text-stone-500 gap-2.5 kb-shimmer-box shadow-sm">
            <span class="text-3xl animate-bounce">🗺️</span>
            <span class="text-xs font-bold text-stone-600 font-kannada">
                {{ $isEn ? 'Preparing Interactive Karnataka Route Map...' : 'ಕರ್ನಾಟಕದ ಸಂವಾದಾತ್ಮಕ ನಕ್ಷೆ ಸಿದ್ಧವಾಗುತ್ತಿದೆ...' }}
            </span>
        </div>

        <!-- Shimmer Mandi Comparison Cards Skeleton -->
        <div class="space-y-3">
            <div class="rounded-2xl bg-white border border-stone-200 p-4 space-y-3 kb-shimmer-box shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="h-5 w-40 bg-stone-200 rounded-lg"></div>
                    <div class="h-6 w-24 bg-emerald-100 rounded-lg"></div>
                </div>
                <div class="h-4 w-56 bg-stone-100 rounded"></div>
                <div class="h-10 w-full bg-stone-50 rounded-xl border border-stone-100"></div>
            </div>
            <div class="rounded-2xl bg-white border border-stone-200 p-4 space-y-3 kb-shimmer-box shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="h-5 w-36 bg-stone-200 rounded-lg"></div>
                    <div class="h-6 w-24 bg-emerald-100 rounded-lg"></div>
                </div>
                <div class="h-4 w-52 bg-stone-100 rounded"></div>
                <div class="h-10 w-full bg-stone-50 rounded-xl border border-stone-100"></div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- WELCOME / PROMPT TO CALCULATE BANNER (Shown BEFORE Calculate clicked)      -->
    <!-- ========================================================================= -->
    <div x-show="!isLoading && !hasCalculated" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="relative overflow-hidden rounded-2xl sm:rounded-3xl shadow-[0_12px_32px_-8px_rgba(11,43,23,0.50)] border-2 border-emerald-500/40 text-white p-3.5 sm:p-5"
         style="contain: paint; background: radial-gradient(circle at 85% 15%, #257044 0%, #154D2B 45%, #0B2B17 100%);">
        
        <!-- Agricultural Field Elevation Contours (Topographic Landscape Geometry - Preserved) -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none select-none z-0" preserveAspectRatio="none" viewBox="0 0 800 240" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M-20 180 Q 240 70, 500 170 T 820 90" stroke="currentColor" stroke-width="1.8" stroke-opacity="0.18" class="text-emerald-200" />
            <path d="M-20 215 Q 260 110, 520 205 T 820 135" stroke="currentColor" stroke-width="1.4" stroke-opacity="0.14" class="text-white" stroke-dasharray="6 4" />
            <path d="M-20 245 Q 280 150, 540 235 T 820 175" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.10" class="text-emerald-300" />
            <ellipse cx="680" cy="45" rx="160" ry="110" fill="url(#atmGlowWts)" opacity="0.35" />
            <defs>
                <radialGradient id="atmGlowWts" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#4ade80" stop-opacity="0.5"/>
                    <stop offset="100%" stop-color="#154D2B" stop-opacity="0"/>
                </radialGradient>
            </defs>
        </svg>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 sm:gap-4">
            <!-- Left Info Block: Compact & Readable -->
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-xl sm:text-2xl shrink-0 shadow-sm ring-2 ring-white/10">
                    ⚖️
                </div>
                <div class="space-y-0.5">
                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-white/15 backdrop-blur-md text-[9px] sm:text-[10px] font-bold text-emerald-200 border border-white/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>{{ $isEn ? 'Net In-Pocket Profit Simulator' : 'ನಿವ್ವಳ ಲಾಭ ಕ್ಯಾಲ್ಕುಲೇಟರ್' }}</span>
                    </div>
                    <h3 class="text-sm sm:text-base md:text-lg font-black text-white tracking-tight leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $isEn ? 'Ready to Simulate Your Net Realization?' : 'ನಿಮ್ಮ ಬೆಳೆಯ ನಿವ್ವಳ ಲಾಭ ಲೆಕ್ಕಹಾಕಲು ಸಿದ್ಧರಿದ್ದೀರಾ?' }}
                    </h3>
                    <p class="text-[11px] sm:text-xs text-emerald-100/85 leading-relaxed font-medium max-w-xl {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $isEn 
                            ? 'Calculates actual take-home earnings across Karnataka mandis after road fuel, hamali, and cess.' 
                            : 'ರಸ್ತೆ ಸಾರಿಗೆ ವೆಚ್ಚ, ಹಮಾಲಿ ಮತ್ತು ಮಂಡಿ ಸೆಸ್ ಕಳೆದ ನಂತರ ಕೈಗೆ ಸಿಗುವ ನಿವ್ವಳ ಲಾಭವನ್ನು ತಿಳಿಯಿರಿ.' }}
                    </p>
                </div>
            </div>

            <!-- Right CTA Button (Sleek Mobile PWA Friendly Tap Target) -->
            <div class="shrink-0 w-full sm:w-auto">
                <button type="button" 
                        @click="fetchComparison(true)" 
                        :disabled="isLoading"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-2xl bg-white hover:bg-emerald-50 active:scale-95 font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition-all cursor-pointer border-2 border-emerald-400/40 group shrink-0 disabled:opacity-75 disabled:cursor-not-allowed"
                        style="color: #052e16 !important; background-color: #ffffff !important;">
                    <span x-show="!isLoading" class="inline-flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-black shadow-xs group-hover:scale-110 transition shrink-0">⚡</span>
                        <span class="font-black text-xs sm:text-sm whitespace-nowrap {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color: #052e16 !important;">
                            {{ $isEn ? 'Calculate Realization' : 'ನಿವ್ವಳ ಲಾಭ ಲೆಕ್ಕಹಾಕಿ' }}
                        </span>
                        <span class="text-xs font-black transition group-hover:translate-x-1 shrink-0" style="color: #047857 !important;">&rarr;</span>
                    </span>
                    <span x-show="isLoading" x-cloak class="inline-flex items-center gap-2 py-0.5">
                        <svg class="animate-spin h-4 w-4 text-emerald-800" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span class="font-bold text-xs text-emerald-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color: #052e16 !important;">
                            {{ $isEn ? 'Calculating...' : 'ಲೆಕ್ಕಹಾಕಲಾಗುತ್ತಿದೆ...' }}
                        </span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. REAL-TIME ASYNC RESULTS PRESENTATION (Only shown AFTER Calculate clicked) -->
    <!-- ========================================================================= -->
    <div x-show="!isLoading && hasCalculated" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         id="resultsSection" 
         class="space-y-5">
        
        <template x-if="hasResults && recommendedMarket">
            <div class="space-y-5">
                
                <!-- 3A. #1 BEST DECISION CHAMPION TROPHY BANNER -->
                <div class="relative overflow-hidden rounded-3xl p-5 sm:p-7 text-white shadow-2xl border-2 border-amber-400/40"
                     style="background: linear-gradient(135deg, #092614 0%, #0d381c 45%, #05160b 100%);">
                    
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-amber-400/20 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                        
                        <div class="space-y-3 max-w-2xl">
                            <!-- Champion Badge -->
                            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-amber-400 text-slate-950 font-black text-xs tracking-wider uppercase shadow-md">
                                <span>🏆</span>
                                <span>{{ $isEn ? '#1 Top Recommended Market (Best Realization)' : '#1 ಅತ್ಯುತ್ತಮ ಶಿಫಾರಸು ಮಂಡಿ (Best Realization)' }}</span>
                            </div>

                            <!-- Mandi Title -->
                            <div>
                                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                                    <span x-text="locale === 'en' ? recommendedMarket.market_name : (recommendedMarket.market_name_kn || recommendedMarket.market_name)"></span>
                                    <span class="text-emerald-300 font-sans font-bold text-base sm:text-xl" x-text="locale === 'en' ? ('(' + (recommendedMarket.market_name_kn || '') + ')') : ('(' + recommendedMarket.market_name + ')')"></span>
                                </h2>
                                
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-emerald-200 text-xs sm:text-sm font-semibold mt-1">
                                    <span>📍 <span x-text="locale === 'en' ? recommendedMarket.district_name : (recommendedMarket.district_name_kn || recommendedMarket.district_name)"></span></span>
                                    <span>•</span>
                                    <span>🛣️ <span x-text="recommendedMarket.distance_km"></span> {{ $isEn ? 'km road' : 'ಕಿ.ಮೀ ರಸ್ತೆ' }}
                                        <template x-if="recommendedMarket.is_round_trip && (rateType === 'per_km' || rateType === 'fuel_only')">
                                            <span class="text-amber-300 text-xs font-mono font-normal" x-text="'(' + recommendedMarket.billed_km + ' km {{ $isEn ? 'RT' : 'ರೌಂಡ್ ಟ್ರಿಪ್' }})'"></span>
                                        </template>
                                    </span>
                                    <span>•</span>
                                    <span>⏱️ ~<span x-text="recommendedMarket.transit_hours"></span> {{ $isEn ? 'hours' : 'ಗಂಟೆ' }}</span>
                                    <span>•</span>
                                    <span>📊 {{ $isEn ? 'Mandi Price:' : 'ದರ:' }} <strong class="text-white font-mono" x-text="'₹' + formatNumber(recommendedMarket.modal_price) + '/Q'"></strong></span>
                                    <span>•</span>
                                    <!-- Explicit As of Date Badge -->
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold"
                                          :class="recommendedMarket.is_today ? 'bg-emerald-400 text-slate-950 font-black' : (recommendedMarket.is_yesterday ? 'bg-amber-400 text-slate-950 font-black' : (recommendedMarket.is_stale ? 'bg-rose-400 text-slate-950' : 'bg-white/20 text-white'))">
                                        <span x-text="locale === 'en' ? recommendedMarket.freshness_badge_en : recommendedMarket.freshness_badge_kn"></span>
                                    </span>
                                </div>

                                <div class="pt-1" x-show="recommendedMarket.variety_name">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-white/10 text-[11px] text-amber-200">
                                        <span>🏷️ {{ $isEn ? 'Traded Variety:' : 'ವ್ಯಾಪಾರವಾದ ತಳಿ:' }}</span>
                                        <strong x-text="locale === 'en' ? recommendedMarket.variety_name : (recommendedMarket.variety_name_kn || recommendedMarket.variety_name)"></strong>
                                    </span>
                                </div>
                            </div>

                            <!-- Comparison Callout Insight -->
                            <div class="p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-xs text-emerald-100 flex items-center gap-2">
                                <span class="text-lg">💡</span>
                                <div>
                                    <template x-if="recommendedMarket.is_baseline">
                                        <span>
                                            <template x-if="isCustomBaseline">
                                                <span>
                                                    {{ $isEn ? 'Your selected ' : 'ನಿಮ್ಮ ಆಯ್ಕೆಯ ' }}<strong class="text-amber-300" x-text="locale === 'en' ? recommendedMarket.market_name : (recommendedMarket.market_name_kn || recommendedMarket.market_name)"></strong>{{ $isEn ? ' market is already the most profitable choice! No need to transport further.' : ' ಮಂಡಿಯೇ ಅತ್ಯಂತ ಲಾಭದಾಯಕವಾಗಿದೆ! ಬೇರೆ ಮಂಡಿಗೆ ಸಾಗಿಸಿ ಸಾರಿಗೆ ವೆಚ್ಚ ವ್ಯರ್ಥ ಮಾಡುವ ಅಗತ್ಯವಿಲ್ಲ.' }}
                                                </span>
                                            </template>
                                            <template x-if="!isCustomBaseline">
                                                <span>
                                                    {{ $isEn ? 'Your nearest local market offers the highest net return! No need to spend extra transport fuel.' : 'ನಿಮ್ಮ ಹತ್ತಿರದ ಮಂಡಿಯೇ ಅತ್ಯಂತ ಲಾಭದಾಯಕವಾಗಿದೆ! ದೂರದ ಮಂಡಿಗೆ ಹೋಗಿ ಸಾರಿಗೆ ವೆಚ್ಚ ವ್ಯರ್ಥ ಮಾಡುವ ಅಗತ್ಯವಿಲ್ಲ.' }}
                                                </span>
                                            </template>
                                        </span>
                                    </template>
                                    <template x-if="!recommendedMarket.is_baseline && (recommendedMarket.net_diff_vs_baseline > 0 || recommendedMarket.net_diff_vs_nearest > 0)">
                                        <span>
                                            <template x-if="isCustomBaseline">
                                                <span>
                                                    {{ $isEn ? 'Earns ' : 'ನಿಮ್ಮ ಆಯ್ಕೆಯ ಮಂಡಿಗಿಂತ ' }}<strong class="text-amber-300" x-text="'+₹' + formatNumber(recommendedMarket.net_diff_vs_baseline || recommendedMarket.net_diff_vs_nearest)"></strong>{{ $isEn ? ' extra net profit over your selected mandi after all transport fuel and cess!' : ' ಹೆಚ್ಚುವರಿ ನಿವ್ವಳ ಲಾಭ ಸಿಗುತ್ತದೆ! ಸಾರಿಗೆ ಖರ್ಚು ಕಳೆದ ನಂತರವೂ ಇದು ನಿಮಗೆ ಅತ್ಯಂತ ಲಾಭದಾಯಕ.' }}
                                                </span>
                                            </template>
                                            <template x-if="!isCustomBaseline">
                                                <span>
                                                    {{ $isEn ? 'Earns ' : 'ಸ್ಥಳೀಯ ಮಂಡಿಗಿಂತ ' }}<strong class="text-amber-300" x-text="'+₹' + formatNumber(recommendedMarket.net_diff_vs_nearest)"></strong>{{ $isEn ? ' extra net profit over nearest market after all transport fuel and cess!' : ' ಹೆಚ್ಚುವರಿ ಲಾಭ ಸಿಗುತ್ತದೆ! ಸಾರಿಗೆ ಖರ್ಚು ಕಳೆದ ನಂತರವೂ ಇದು ನಿಮಗೆ ಅತ್ಯಂತ ಲಾಭದಾಯಕ.' }}
                                                </span>
                                            </template>
                                        </span>
                                    </template>
                                    <template x-if="!recommendedMarket.is_baseline && (recommendedMarket.net_diff_vs_baseline <= 0 && recommendedMarket.net_diff_vs_nearest <= 0)">
                                        <span x-text="locale === 'en' ? recommendedMarket.verdict_en : recommendedMarket.verdict_kn"></span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Hero Financial Take-Home Box -->
                        <div class="flex flex-col items-start lg:items-end gap-2.5 shrink-0">
                            <div class="w-full sm:w-auto bg-black/40 backdrop-blur-md rounded-2xl p-4 sm:p-5 border border-emerald-400/30 text-left lg:text-right min-w-[220px] shadow-inner">
                                <div class="text-[10px] font-black uppercase tracking-wider text-emerald-300">
                                    {{ $isEn ? 'Net In-Pocket Cash Realization:' : 'ಕೈಗೆ ಸಿಗುವ ನಿವ್ವಳ ಲಾಭ (Net In-Pocket):' }}
                                </div>
                                <div class="text-3xl sm:text-4xl font-black text-amber-300 font-mono mt-0.5" x-text="'₹' + formatNumber(recommendedMarket.net_realization)">
                                </div>
                                <div class="text-xs text-emerald-200 font-bold mt-0.5" x-text="'₹' + formatNumber(recommendedMarket.net_rate_per_qtl) + ' {{ $isEn ? '/ Quintal Net Realized Rate' : '/ ಕ್ವಿಂಟಾಲ್ ನೈಜ ದರ' }}'">
                                </div>
                            </div>

                            <a :href="recommendedMarket.google_maps_url" 
                               target="_blank" 
                               rel="noopener" 
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs tracking-wider uppercase shadow-md transition active:scale-95 cursor-pointer">
                                <span>🗺️ {{ $isEn ? 'Navigate on Google Maps' : 'ಗೂಗಲ್ ಮ್ಯಾಪ್‌ನಲ್ಲಿ ಮಾರ್ಗ (Navigate)' }}</span>
                                <span class="text-sm">↗</span>
                            </a>
                        </div>

                    </div>
                </div>

                <!-- ================================================================= -->
                <!-- 3B. INTERACTIVE LEAFLET KARNATAKA ROUTE MAP CONTAINER             -->
                <!-- ================================================================= -->
                <div class="bg-white rounded-3xl border-2 border-[#D9CEB8] shadow-md p-4 sm:p-5 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm sm:text-base font-black text-emerald-950 flex items-center gap-1.5">
                                <span>🗺️</span>
                                <span>{{ $isEn ? 'Market Locations & Interactive Route Map' : 'ಮಂಡಿಗಳ ನಕ್ಷೆ & ಮಾರ್ಗ (Interactive Route Map)' }}</span>
                            </h3>
                            <p class="text-[11px] text-stone-500 font-medium">
                                {{ $isEn 
                                    ? 'Click anywhere on map to reposition your farm pin, or tap mandi pins for details' 
                                    : 'ನಕ್ಷೆಯಲ್ಲಿ ನಿಮ್ಮ ತೋಟದ ಸ್ಥಳವನ್ನು ಕ್ಲಿಕ್ ಮಾಡಿ ಅಥವಾ ಮಂಡಿ ಪಿನ್‌ಗಳನ್ನು ಟ್ಯಾಪ್ ಮಾಡಿ ವಿವರ ನೋಡಿ' }}
                            </p>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-[10px] font-bold text-emerald-900 border border-emerald-200">
                            <span>📌</span>
                            <span>{{ $isEn ? 'Click on map to set your farm location' : 'ನಕ್ಷೆ ಮೇಲೆ ಕ್ಲಿಕ್ ಮಾಡಿ ಹೊಸ ಸ್ಥಳ ಆಯ್ಕೆ ಮಾಡಬಹುದು' }}</span>
                        </div>
                    </div>

                    <!-- Map Canvas -->
                    <div id="whereToSellMap" 
                         class="w-full h-72 sm:h-96 rounded-2xl border border-stone-200 shadow-inner z-0 overflow-hidden bg-stone-100">
                    </div>

                    <!-- Map Legend -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[11px] text-stone-600">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-blue-600 border border-white shadow-xs"></span>
                                <span>{{ $isEn ? 'Your Origin' : 'ನಿಮ್ಮ ಸ್ಥಳ (Origin)' }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-amber-400 border border-white shadow-xs"></span>
                                <span>{{ $isEn ? '#1 Best Market' : '#1 ಅತ್ಯುತ್ತಮ ಮಂಡಿ' }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-sky-600 border border-white shadow-xs"></span>
                                <span>{{ $isEn ? 'Baseline Mandi' : 'ಆಧಾರ ಮಂಡಿ' }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-emerald-600 border border-white shadow-xs"></span>
                                <span>{{ $isEn ? 'Other Markets' : 'ಇತರ ಮಂಡಿಗಳು' }}</span>
                            </span>
                        </div>

                        <button type="button" 
                                @click="resetMapBounds()" 
                                class="text-xs text-emerald-800 font-bold hover:underline cursor-pointer flex items-center gap-1">
                            <span>🔄</span>
                            <span>{{ $isEn ? 'Fit Map Bounds' : 'ನಕ್ಷೆ ಮರುಹೊಂದಿಸಿ (Fit Bounds)' }}</span>
                        </button>
                    </div>
                </div>

                <!-- ================================================================= -->
                <!-- 3C. ALL MANDIS COMPARISON CARDS / MATRIX VIEW SWITCHER            -->
                <!-- ================================================================= -->
                <div class="flex items-center justify-between gap-3 pt-1">
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-stone-900 flex items-center gap-1.5">
                            <span>📊</span>
                            <span>{{ $isEn ? 'Karnataka Mandi Take-Home Comparison' : 'ಎಲ್ಲಾ ಮಂಡಿಗಳ ನೈಜ ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ' }}</span>
                        </h3>
                        <p class="text-xs text-stone-500 font-medium">
                            {{ $isEn ? 'Comparative realization across ' : 'ಒಟ್ಟು ' }}<span class="font-bold text-emerald-900" x-text="markets.length"></span>{{ $isEn ? ' traded APMC markets' : ' ಮಾರುಕಟ್ಟೆಗಳಲ್ಲಿ ಇಂದಿನ ವ್ಯಾಪಾರದ ಲೆಕ್ಕಾಚಾರ' }}
                        </p>
                    </div>

                    <div class="inline-flex rounded-xl border border-stone-300 bg-white p-0.5 shadow-xs text-xs">
                        <button type="button" 
                                @click="viewMode = 'cards'"
                                :class="viewMode === 'cards' ? 'bg-emerald-800 text-white font-bold' : 'text-stone-600'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer">
                            {{ $isEn ? 'Cards' : 'ಕಾರ್ಡ್‌ಗಳು' }}
                        </button>
                        <button type="button" 
                                @click="viewMode = 'table'"
                                :class="viewMode === 'table' ? 'bg-emerald-800 text-white font-bold' : 'text-stone-600'"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer">
                            {{ $isEn ? 'Table' : 'ಕೋಷ್ಟಕ' }}
                        </button>
                    </div>
                </div>

                <!-- 3D. CARDS VIEW -->
                <div x-show="viewMode === 'cards'" class="space-y-3">
                    <template x-for="(m, idx) in markets" :key="m.market_id">
                        <div :class="idx === 0 ? 'border-2 border-emerald-600 bg-emerald-50/20' : 'border border-stone-200 bg-white'"
                             class="rounded-2xl p-3.5 sm:p-4 shadow-xs transition hover:shadow-md space-y-3">
                            
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black shrink-0"
                                              :class="idx === 0 ? 'bg-amber-400 text-slate-950' : 'bg-stone-100 text-stone-700'"
                                              x-text="'#' + (idx + 1)"></span>
                                        <h4 class="text-sm sm:text-base font-black text-stone-900">
                                            <span x-text="locale === 'en' ? m.market_name : (m.market_name_kn || m.market_name)"></span>
                                            <span class="text-stone-500 font-sans text-xs font-semibold" x-text="locale === 'en' ? ('(' + (m.market_name_kn || '') + ')') : ('(' + m.market_name + ')')"></span>
                                        </h4>
                                        <template x-if="m.is_baseline">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-sky-100 text-sky-900 border border-sky-300">
                                                🎯 {{ $isEn ? 'Baseline Market' : 'ಆಧಾರ ಮಂಡಿ' }}
                                            </span>
                                        </template>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-stone-500">
                                        <span>📍 <span x-text="locale === 'en' ? m.district_name : (m.district_name_kn || m.district_name)"></span></span>
                                        <span>•</span>
                                        <span>🛣️ <span x-text="m.distance_km"></span> km
                                            <template x-if="m.is_round_trip && (rateType === 'per_km' || rateType === 'fuel_only')">
                                                <span class="text-[10px] text-amber-800 bg-amber-100/90 font-mono font-bold px-1.5 py-0.5 rounded" x-text="'(' + m.billed_km + ' km RT)'"></span>
                                            </template>
                                        </span>
                                        <span>•</span>
                                        <span>⏱️ ~<span x-text="m.transit_hours"></span>h</span>
                                        <span x-show="m.variety_name">• 🏷️ <span x-text="locale === 'en' ? m.variety_name : (m.variety_name_kn || m.variety_name)"></span></span>
                                        <span>•</span>
                                        <!-- Explicit As of Date Badge -->
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                                              :class="m.is_today ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : (m.is_yesterday ? 'bg-amber-50 text-amber-900 border border-amber-200' : (m.is_stale ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-stone-100 text-stone-700 border border-stone-200'))">
                                            <span x-text="locale === 'en' ? m.freshness_badge_en : m.freshness_badge_kn"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Realization Cash Pill & Google Maps -->
                                <div class="flex items-center justify-between sm:justify-end gap-3 pt-1 sm:pt-0">
                                    <div class="text-left sm:text-right">
                                        <div class="text-[10px] text-stone-500 font-bold uppercase">{{ $isEn ? 'Net Take-Home:' : 'ನಿವ್ವಳ ಕೈಗೆ:' }}</div>
                                        <div class="text-lg sm:text-xl font-black text-emerald-900 font-mono" x-text="'₹' + formatNumber(m.net_realization)"></div>
                                    </div>
                                    <a :href="m.google_maps_url" 
                                       target="_blank" 
                                       rel="noopener" 
                                       class="px-3 py-1.5 rounded-xl bg-stone-100 hover:bg-emerald-100 text-stone-800 text-xs font-bold transition flex items-center gap-1">
                                        <span>🗺️</span>
                                        <span class="hidden sm:inline">{{ $isEn ? 'Map' : 'ನಕ್ಷೆ' }}</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Financial Mini-Waterfall Bar -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-stone-100 text-xs">
                                <div class="p-2 rounded-xl bg-stone-50">
                                    <span class="text-[10px] text-stone-500 block">{{ $isEn ? 'Modal Price:' : 'ಮಂಡಿ ದರ:' }}</span>
                                    <strong class="text-stone-900 font-mono font-bold" x-text="'₹' + formatNumber(m.modal_price) + '/Q'"></strong>
                                </div>
                                <div class="p-2 rounded-xl bg-stone-50">
                                    <span class="text-[10px] text-stone-500 block">{{ $isEn ? 'Gross Revenue:' : 'ಒಟ್ಟು ಮೌಲ್ಯ:' }}</span>
                                    <strong class="text-stone-900 font-mono font-bold" x-text="'₹' + formatNumber(m.gross_revenue)"></strong>
                                </div>
                                <div class="p-2 rounded-xl bg-stone-50">
                                    <span class="text-[10px] text-rose-600 block">{{ $isEn ? 'Transport Cost:' : 'ಸಾರಿಗೆ ವೆಚ್ಚ:' }}</span>
                                    <strong class="text-rose-700 font-mono font-bold" x-text="'-₹' + formatNumber(m.transport_cost)"></strong>
                                </div>
                                <div class="p-2 rounded-xl bg-stone-50">
                                    <span class="text-[10px] text-stone-500 block">{{ $isEn ? 'Cess & Hamali:' : 'ಸೆಸ್ & ಹಮಾಲಿ:' }}</span>
                                    <strong class="text-stone-700 font-mono font-bold" x-text="'-₹' + formatNumber(m.apmc_cess + m.hamali)"></strong>
                                </div>
                            </div>

                            <!-- Verdict Tag -->
                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="font-bold text-xs" 
                                      :class="m.is_baseline ? 'text-sky-800' : (m.net_diff_vs_baseline >= 300 ? 'text-emerald-800' : (m.net_diff_vs_baseline <= -200 ? 'text-rose-700' : 'text-stone-600'))"
                                      x-text="locale === 'en' ? m.verdict_en : m.verdict_kn"></span>
                                <span class="text-[10px] font-mono font-bold text-stone-500" x-text="'₹' + formatNumber(m.net_rate_per_qtl) + '/Qtl'"></span>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- 3E. DETAILED TABLE VIEW -->
                <div x-show="viewMode === 'table'" class="overflow-x-auto bg-white rounded-2xl border border-stone-200 shadow-xs">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-stone-50 text-stone-600 uppercase font-mono border-b border-stone-200">
                            <tr>
                                <th class="py-2.5 px-3">{{ $isEn ? 'Market' : 'ಮಂಡಿ' }}</th>
                                <th class="py-2.5 px-3">{{ $isEn ? 'Variety' : 'ತಳಿ' }}</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">{{ $isEn ? 'As of Date' : 'ದಿನಾಂಕ' }}</th>
                                <th class="py-2.5 px-3">{{ $isEn ? 'Road Km' : 'ರಸ್ತೆ ದೂರ' }}</th>
                                <th class="py-2.5 px-3 text-right">{{ $isEn ? 'Mandi Price' : 'ಮಂಡಿ ದರ' }}</th>
                                <th class="py-2.5 px-3 text-right">{{ $isEn ? 'Gross Value' : 'ಒಟ್ಟು ಮೌಲ್ಯ' }}</th>
                                <th class="py-2.5 px-3 text-right text-rose-600">{{ $isEn ? 'Freight' : 'ಸಾರಿಗೆ' }}</th>
                                <th class="py-2.5 px-3 text-right">{{ $isEn ? 'Cess+Hamali' : 'ಸೆಸ್+ಹಮಾಲಿ' }}</th>
                                <th class="py-2.5 px-3 text-right text-emerald-900 font-bold">{{ $isEn ? 'Net Take-Home' : 'ನಿವ್ವಳ ಹಣ' }}</th>
                                <th class="py-2.5 px-3 text-center">{{ $isEn ? 'Map' : 'ನಕ್ಷೆ' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <template x-for="m in markets" :key="m.market_id">
                                <tr class="hover:bg-stone-50" :class="m.is_baseline ? 'bg-sky-50/30' : ''">
                                    <td class="py-2.5 px-3 font-bold text-stone-900">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span x-text="locale === 'en' ? m.market_name : (m.market_name_kn || m.market_name)"></span>
                                            <template x-if="m.is_baseline">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-sky-100 text-sky-900 border border-sky-200">
                                                    🎯 {{ $isEn ? 'Baseline' : 'ಆಧಾರ' }}
                                                </span>
                                            </template>
                                        </div>
                                        <span class="block text-[10px] text-stone-400 font-sans" x-text="locale === 'en' ? m.district_name : (m.district_name_kn || m.district_name)"></span>
                                    </td>
                                    <td class="py-2.5 px-3 text-stone-600 font-medium" x-text="locale === 'en' ? (m.variety_name || 'Standard') : (m.variety_name_kn || m.variety_name || 'ಸಾಮಾನ್ಯ')"></td>
                                    <td class="py-2.5 px-3 whitespace-nowrap text-stone-600">
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold"
                                              :class="m.is_today ? 'bg-emerald-50 text-emerald-800' : (m.is_yesterday ? 'bg-amber-50 text-amber-900' : (m.is_stale ? 'bg-rose-50 text-rose-800' : 'bg-stone-100 text-stone-700'))"
                                              x-text="locale === 'en' ? m.freshness_badge_en : m.freshness_badge_kn"></span>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono">
                                        <span x-text="m.distance_km + ' km'"></span>
                                        <template x-if="m.is_round_trip && (rateType === 'per_km' || rateType === 'fuel_only')">
                                            <span class="block text-[10px] text-amber-700 font-semibold" x-text="'(' + m.billed_km + ' km RT)'"></span>
                                        </template>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold" x-text="'₹' + formatNumber(m.modal_price)"></td>
                                    <td class="py-2.5 px-3 text-right font-mono" x-text="'₹' + formatNumber(m.gross_revenue)"></td>
                                    <td class="py-2.5 px-3 text-right font-mono text-rose-600" x-text="'-₹' + formatNumber(m.transport_cost)"></td>
                                    <td class="py-2.5 px-3 text-right font-mono" x-text="'-₹' + formatNumber(m.apmc_cess + m.hamali)"></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-black bg-emerald-50/50 text-emerald-900" x-text="'₹' + formatNumber(m.net_realization)"></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <a :href="m.google_maps_url" target="_blank" rel="noopener" class="text-emerald-700 font-bold underline">Map ↗</a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

            </div>
        </template>

        <!-- EMPTY STATE (No traded markets recently or radius too tight) -->
        <template x-if="!hasResults">
            <div class="bg-amber-50 rounded-3xl p-6 sm:p-8 border-2 border-amber-200 text-center space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 border border-amber-300 flex items-center justify-center text-2xl mx-auto">
                    ⚠️
                </div>
                <div class="space-y-1">
                    <h3 class="text-base sm:text-lg font-black text-amber-950">
                        <template x-if="maxDistance">
                            <span>{{ $isEn ? 'No APMC mandis found within ' : 'ನಿಮ್ಮಿಂದ ' }}<span x-text="maxDistance + ' km'"></span>{{ $isEn ? ' road radius' : ' ವ್ಯಾಪ್ತಿಯಲ್ಲಿ ಸಕ್ರಿಯ ಮಂಡಿಗಳು ಲಭ್ಯವಿಲ್ಲ' }}</span>
                        </template>
                        <template x-if="!maxDistance">
                            <span>{{ $isEn ? 'No recent mandi prices recorded for this selection' : 'ಈ ಬೆಳೆಗೆ ಇತ್ತೀಚಿನ ಸಕ್ರಿಯ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ' }}</span>
                        </template>
                    </h3>
                    <p class="text-xs sm:text-sm text-amber-900 max-w-md mx-auto leading-relaxed">
                        <template x-if="maxDistance">
                            <span>{{ $isEn ? 'Try expanding your search radius to 100 km, 150 km, or All Karnataka to discover trading markets.' : 'ವ್ಯಾಪಾರವಾಗುತ್ತಿರುವ ಇತರ ಮಂಡಿಗಳನ್ನು ನೋಡಲು ಹುಡುಕಾಟದ ವ್ಯಾಪ್ತಿಯನ್ನು 100 ಕಿ.ಮೀ, 150 ಕಿ.ಮೀ ಗೆ ಅಥವಾ ಇಡೀ ಕರ್ನಾಟಕಕ್ಕೆ ವಿಸ್ತರಿಸಿ.' }}</span>
                        </template>
                        <template x-if="!maxDistance">
                            <span>{{ $isEn ? 'No active mandi trades recorded across Karnataka for the selected crop/variety. Try selecting another variety or change your origin district.' : 'ಆಯ್ಕೆಮಾಡಿದ ಬೆಳೆಗೆ ಕರ್ನಾಟಕದ ಮಂಡಿಗಳಲ್ಲಿ ಇತ್ತೀಚೆಗೆ ವ್ಯಾಪಾರ ದಾಖಲಾಗಿಲ್ಲ. ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ/ತಳಿ ಆಯ್ಕೆಮಾಡಿ ಅಥವಾ ನಿಮ್ಮ ಜಿಲ್ಲೆಯನ್ನು ಬದಲಾಯಿಸಿ.' }}</span>
                        </template>
                    </p>
                </div>

                <template x-if="maxDistance">
                    <div class="flex items-center justify-center gap-2 pt-1 flex-wrap">
                        <button type="button" 
                                @click="setMaxDistance(100); fetchComparison(true)" 
                                class="px-4 py-2 rounded-xl bg-amber-200 hover:bg-amber-300 text-amber-950 font-bold text-xs shadow-xs transition cursor-pointer">
                            <span>🔍 {{ $isEn ? 'Expand to 100 km' : '100 ಕಿ.ಮೀ ಗೆ ವಿಸ್ತರಿಸಿ' }}</span>
                        </button>
                        <button type="button" 
                                @click="setMaxDistance(null); fetchComparison(true)" 
                                class="px-4 py-2 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                            <span>🌏 {{ $isEn ? 'Search All Karnataka' : 'ಇಡೀ ಕರ್ನಾಟಕದ ಮಂಡಿಗಳನ್ನು ನೋಡಿ' }}</span>
                        </button>
                    </div>
                </template>
            </div>
        </template>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. TRANSPARENT METHODOLOGY ACCORDION                                      -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-3xl p-5 border-2 border-[#D9CEB8] text-xs text-stone-600 space-y-3 shadow-xs">
        <div class="flex items-center gap-2 text-stone-900 border-b border-stone-200 pb-2">
            <span class="text-base">📐</span>
            <h4 class="text-xs sm:text-sm font-black text-emerald-950">
                {{ $isEn ? 'Transparent Calculation Methodology & Formula:' : 'ಪಾರದರ್ಶಕ ನಿವ್ವಳ ಲಾಭ ಲೆಕ್ಕಾಚಾರ ಪದ್ಧತಿ (Transparent Formula):' }}
            </h4>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs leading-relaxed">
            <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200 space-y-1">
                <div class="font-bold text-emerald-900 flex items-center gap-1">
                    <span>1️⃣</span>
                    <span>{{ $isEn ? 'The Realization Formula:' : 'ನಿವ್ವಳ ಹಣದ ಸೂತ್ರ (Formula):' }}</span>
                </div>
                <p>
                    <strong>{{ $isEn ? 'Net Realization' : 'ನಿವ್ವಳ ಲಾಭ' }}</strong> = 
                    <code>{{ $isEn ? 'Gross Revenue (Qty × Price)' : 'ಒಟ್ಟು ಮೌಲ್ಯ (ಪ್ರಮಾಣ × ದರ)' }}</code> - 
                    <code>{{ $isEn ? 'Transport Freight' : 'ಸಾರಿಗೆ ವೆಚ್ಚ' }}</code> - 
                    <code>{{ $isEn ? 'APMC Cess (1.5%)' : 'ಮಾರುಕಟ್ಟೆ ಸೆಸ್ (1.5%)' }}</code> - 
                    <code>{{ $isEn ? 'Hamali (₹10/Qtl)' : 'ಹಮಾಲಿ ಶುಲ್ಕ (₹10/ಕ್ವಿಂಟಾಲ್)' }}</code>.
                </p>
            </div>

            <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200 space-y-1">
                <div class="font-bold text-emerald-900 flex items-center gap-1">
                    <span>2️⃣</span>
                    <span>{{ $isEn ? '1.2x Rural Road Multiplier:' : '1.2x ಗ್ರಾಮೀಣ ರಸ್ತೆ ಗುಣಕ (Rural Road Factor):' }}</span>
                </div>
                <p>
                    {{ $isEn 
                        ? 'Accounting for actual rural road bends and highway connections, a 1.2x multiplier is applied over straight-line coordinates.' 
                        : 'ಗ್ರಾಮೀಣ ಭಾಗದ ತಿರುವು ರಸ್ತೆಗಳು ಮತ್ತು ಹೆದ್ದಾರಿ ಸಂಪರ್ಕವನ್ನು ಪರಿಗಣಿಸಿ, ನೇರ ರೇಖೆಯ ನಕ್ಷೆ ದೂರಕ್ಕೆ 1.2x ರಸ್ತೆ ದೂರ ಅನ್ವಯಿಸಲಾಗಿದೆ.' }}
                </p>
            </div>
        </div>

        <div class="text-[10px] text-stone-500 pt-0.5 flex items-center gap-1.5">
            <span>🛡️</span>
            <span>
                {{ $isEn 
                    ? 'Krushi Baandhava does not endorse any specific commission agent or private trader. All figures are computed using official APMC government price bulletins.' 
                    : 'ಕೃಷಿ ಬಾಂಧವ ಪೋರ್ಟಲ್‌ನಲ್ಲಿ ಯಾವುದೇ ಮಂಡಿ ಪರವಾಗಿ ಕೃತಕ ರೇಟಿಂಗ್ ಇರುವುದಿಲ್ಲ. ಇದು ಸಂಪೂರ್ಣವಾಗಿ ಅಧಿಕೃತ ಸರ್ಕಾರಿ ದರಗಳ ನೈಜ ಗಣಿತೀಯ ಲೆಕ್ಕಾಚಾರವಾಗಿದೆ.' }}
            </span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. ALL CROPS SEARCH MODAL DIALOG                                          -->
    <!-- ========================================================================= -->
    <div x-show="cropSearchModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         @keydown.escape.window="cropSearchModal = false">
        
        <div class="bg-white rounded-3xl max-w-lg w-full p-5 shadow-2xl border-2 border-stone-200 space-y-4 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <h3 class="text-sm font-black text-stone-900 flex items-center gap-2">
                    <span>🌾</span>
                    <span>{{ $isEn ? 'All Traded Karnataka Crops' : 'ಕರ್ನಾಟಕದ ಸಕ್ರಿಯ ಬೆಳೆಗಳು (All Traded Crops)' }}</span>
                </h3>
                <button type="button" @click="cropSearchModal = false" class="w-8 h-8 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-700 flex items-center justify-center font-bold">
                    ✕
                </button>
            </div>

            <!-- Filter input -->
            <input type="text" 
                   x-model="cropModalFilter" 
                   placeholder="{{ $isEn ? 'Search crop name (e.g. Paddy, Arecanut, Onion)...' : 'ಬೆಳೆಯ ಹೆಸರು ಹುಡುಕಿ... (e.g. ಭತ್ತ, ಅಡಿಕೆ, ಈರುಳ್ಳಿ)' }}" 
                   class="w-full text-xs font-bold rounded-xl border-stone-300 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">

            <!-- Scrollable List -->
            <div class="overflow-y-auto space-y-1.5 flex-1 pr-1">
                @foreach($crops as $c)
                    <button type="button" 
                            x-show="!cropModalFilter || '{{ strtolower($c->name . ' ' . ($c->name_kn ?? '')) }}'.includes(cropModalFilter.toLowerCase())"
                            @click="selectCrop(@js(['id' => $c->id, 'slug' => $c->slug, 'name' => $c->name, 'name_kn' => $c->name_kn ?? $c->name, 'varieties' => $c->varieties])); cropSearchModal = false;"
                            class="w-full text-left p-2.5 rounded-xl hover:bg-emerald-50 border border-stone-100 hover:border-emerald-200 transition flex items-center justify-between group cursor-pointer">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg overflow-hidden bg-stone-100 border border-stone-200 shrink-0">
                                @if(!empty($c->photo_url))
                                    <img src="{{ $c->photo_url }}" alt="{{ $c->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="w-full h-full flex items-center justify-center text-sm">🌱</span>
                                @endif
                            </div>
                            <div>
                                <div class="text-xs font-bold text-stone-900 group-hover:text-emerald-900">
                                    {{ $isEn ? $c->name : ($c->name_kn ?? $c->name) }}
                                </div>
                                <div class="text-[10px] text-stone-500 font-sans">
                                    {{ $isEn ? ($c->name_kn ?? '') : $c->name }}
                                </div>
                            </div>
                        </div>
                        <span class="text-xs text-stone-400 group-hover:text-emerald-700">{{ $isEn ? 'Select ➔' : 'ಆಯ್ಕೆ ➔' }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 6. MOBILE STICKY FLOATING ACTION CAPSULE (PWA Style)                     -->
    <!-- ========================================================================= -->
    <div class="sm:hidden fixed bottom-3 inset-x-3 z-40">
        <div class="bg-slate-950/95 backdrop-blur-md rounded-2xl p-2.5 shadow-2xl border border-emerald-500/40 text-white flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0 pl-1">
                <span class="text-lg">🌾</span>
                <div class="truncate">
                    <div class="text-xs font-bold text-white truncate" x-text="getCropDisplayName()"></div>
                    <div class="text-[10px] text-emerald-300 font-mono" x-text="quantity + ' Qtl • ' + getRateUnitLabel()"></div>
                </div>
            </div>

            <button type="button" 
                    @click="fetchComparison(true); scrollToResults();"
                    :disabled="isLoading"
                    :class="hasUncalculatedChanges 
                        ? 'from-emerald-400 to-teal-300 text-slate-950 ring-2 ring-emerald-300 shadow-xl scale-[1.03] animate-pulse' 
                        : 'from-emerald-500 to-teal-500 text-slate-950'"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r font-black text-xs uppercase tracking-wider shadow-md shrink-0 flex items-center gap-1 active:scale-95 cursor-pointer transition">
                <span x-show="!isLoading" class="flex items-center gap-1">
                    <span x-show="hasUncalculatedChanges" class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-ping"></span>
                    <span>⚡ {{ $isEn ? 'Calculate' : 'ಲೆಕ್ಕ ಮಾಡಿ' }}</span>
                    <span>➔</span>
                </span>
                <span x-show="isLoading" class="flex items-center gap-1">
                    <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>...</span>
                </span>
            </button>
        </div>
    </div>

</div>

<!-- Leaflet JS for Interactive Karnataka Route Map (Local Vendor Asset) -->
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

<script>
function whereToSellApp(config) {
    return {
        // State
        locale: config.locale || 'kn',
        selectedCrop: config.initialCrop,
        varietyId: config.initialVarietyId,
        quantity: config.initialQuantity || 10,
        vehicle: config.initialVehicle || 'pickup',
        rateType: config.initialRateType || 'per_km',
        customRate: config.initialCustomRate || 22,
        lat: config.initialLat,
        lng: config.initialLng,
        districtId: config.initialDistrictId,
        talukId: config.initialTalukId,
        sort: config.initialSort || 'net_realization',
        viewMode: 'cards',
        
        // Contextual Commodity State (from crop page)
        isContextual: Boolean(config.isContextual),
        isCropSelectionExpanded: !Boolean(config.isContextual),

        // KM Range & Round-Trip
        maxDistance: (config.initialMaxDistance !== undefined && config.initialMaxDistance !== null) ? Number(config.initialMaxDistance) : null,
        roundTrip: config.initialRoundTrip !== undefined ? Boolean(config.initialRoundTrip) : true,
        
        // Data & Loading
        comparison: config.initialComparison || {},
        markets: config.initialComparison?.markets || [],
        recommendedMarket: config.initialComparison?.recommended_market || null,
        baselineMarketId: config.initialBaselineMarketId || null,
        baselineMarket: config.initialComparison?.baseline_market || null,
        isCustomBaseline: Boolean(config.initialComparison?.is_custom_baseline),
        originLabel: (config.locale === 'en' 
            ? (config.initialComparison?.origin?.name || config.initialComparison?.origin?.name_kn) 
            : (config.initialComparison?.origin?.name_kn || config.initialComparison?.origin?.name)) || 'Karnataka',
        hasResults: Boolean(config.initialComparison?.markets && config.initialComparison.markets.length > 0),
        hasCalculated: false,
        hasUncalculatedChanges: false,
        isLoading: false,
        
        // GPS State
        gpsLoading: false,
        gpsActive: Boolean(config.initialLat && config.initialLng),
        gpsLabelText: '',

        // Modals & UI
        cropSearchModal: false,
        cropModalFilter: '',
        districtsData: @js($districts),
        currentTaluks: [],
        mapInstance: null,
        mapMarkersLayer: null,
        debounceTimer: null,
        mapApiKey: config.mapApiKey || '',
        mapTileProvider: config.mapTileProvider || 'carto_voyager',
        mapCustomTileUrl: config.mapCustomTileUrl || '',

        initApp() {
            this.setGpsLabel();

            // Restore saved rate from localStorage if present
            const savedRate = localStorage.getItem('krushi_where_to_sell_rate');
            const savedRateType = localStorage.getItem('krushi_where_to_sell_rate_type');
            if (savedRate && !config.initialCustomRate) {
                this.customRate = parseFloat(savedRate);
            }
            if (savedRateType && !config.initialRateType) {
                this.rateType = savedRateType;
            }

            // Sync current taluks for initial district
            this.syncTaluks();

            // Instant auto-alignment on mount without animated sideways jump
            this.$nextTick(() => {
                this.scrollToSelectedCrop(true);
                this.scrollToSelectedVariety(true);

                if (this.hasCalculated) {
                    this.$nextTick(() => {
                        this.initLeafletMap();
                    });
                }

                if (typeof window.hidePageLoader === 'function') {
                    window.hidePageLoader();
                }
            });
        },

        scrollToSelectedCrop(instant = false) {
            this.$nextTick(() => {
                if (!this.selectedCrop) return;
                const card = document.getElementById(`crop-card-${this.selectedCrop.slug}`);
                if (card) {
                    card.scrollIntoView({
                        behavior: instant ? 'auto' : 'smooth',
                        block: 'nearest',
                        inline: 'center'
                    });
                }
            });
        },

        scrollToSelectedVariety(instant = false) {
            this.$nextTick(() => {
                const tryScroll = (attempts = 0) => {
                    const chipId = this.varietyId ? `variety-chip-${this.varietyId}` : 'variety-chip-all';
                    const chip = document.getElementById(chipId);
                    if (chip) {
                        chip.scrollIntoView({
                            behavior: instant ? 'auto' : 'smooth',
                            block: 'nearest',
                            inline: 'center'
                        });
                    } else if (attempts < 4) {
                        setTimeout(() => tryScroll(attempts + 1), 60);
                    }
                };
                setTimeout(() => tryScroll(0), instant ? 0 : 40);
            });
        },

        setGpsLabel() {
            if (this.lat && this.lng) {
                this.gpsLabelText = (this.locale === 'en') ? 'Live GPS Location Active' : 'ಜಿಪಿಎಸ್ ಸ್ಥಳ ಸಕ್ರಿಯವಾಗಿದೆ (GPS Active)';
            } else {
                this.gpsLabelText = (this.locale === 'en') ? 'Detect Live GPS Location' : 'ನನ್ನ ನೇರ ಜಿಪಿಎಸ್ ಸ್ಥಳ ಬಳಸಿ (Use Live GPS)';
            }
        },

        getCropDisplayName() {
            if (!this.selectedCrop) return (this.locale === 'en' ? 'Select Crop' : 'ಬೆಳೆ ಆಯ್ಕೆಮಾಡಿ');
            return (this.locale === 'en') ? this.selectedCrop.name : (this.selectedCrop.name_kn || this.selectedCrop.name);
        },

        getVarietyDisplayName() {
            if (!this.varietyId || !this.selectedCrop || !this.selectedCrop.varieties) return '';
            const v = this.selectedCrop.varieties.find(item => item.id == this.varietyId);
            if (!v) return '';
            return (this.locale === 'en') ? v.name : (v.name_kn || v.name);
        },

        syncTaluks() {
            if (!this.districtId) {
                this.currentTaluks = [];
                return;
            }
            const d = this.districtsData.find(item => item.id == this.districtId);
            this.currentTaluks = d ? (d.taluks || []) : [];
        },

        selectCrop(cropObj) {
            this.selectedCrop = cropObj;
            // Reset variety and baseline selection when crop changes
            this.varietyId = (cropObj.varieties && cropObj.varieties.length > 0) ? cropObj.varieties[0].id : null;
            this.baselineMarketId = null;
            this.baselineMarket = null;
            this.isCustomBaseline = false;
            this.hasUncalculatedChanges = true;
            this.scrollToSelectedCrop();
            this.scrollToSelectedVariety();
        },

        clearBaselineMarket() {
            this.baselineMarketId = null;
            this.baselineMarket = null;
            this.isCustomBaseline = false;
            this.hasUncalculatedChanges = true;
        },

        selectVariety(vId) {
            this.varietyId = vId;
            this.hasUncalculatedChanges = true;
            this.scrollToSelectedVariety();
        },

        setQuantity(val) {
            this.quantity = Math.max(0.5, parseFloat(val));
            this.hasUncalculatedChanges = true;
        },

        adjustQuantity(delta) {
            this.quantity = Math.max(0.5, parseFloat((this.quantity + delta).toFixed(1)));
            this.hasUncalculatedChanges = true;
        },

        selectVehicle(vKey) {
            this.vehicle = vKey;
            if (this.rateType === 'per_km') {
                const defaults = { 'auto': 15, 'pickup': 22, 'truck': 32 };
                this.customRate = defaults[vKey] || 22;
            }
            this.hasUncalculatedChanges = true;
        },

        setRateType(type) {
            this.rateType = type;
            localStorage.setItem('krushi_where_to_sell_rate_type', type);

            if (type === 'per_km') {
                this.customRate = (this.vehicle === 'auto') ? 15 : (this.vehicle === 'truck' ? 32 : 22);
            } else if (type === 'per_quintal') {
                this.customRate = 40; // ₹40/Qtl average freight
            } else if (type === 'fixed_fare') {
                this.customRate = 1800; // Flat ₹1,800
            } else if (type === 'fuel_only') {
                this.customRate = 12; // ₹12/km diesel
            }

            localStorage.setItem('krushi_where_to_sell_rate', this.customRate);
            this.hasUncalculatedChanges = true;
        },

        adjustCustomRate(delta) {
            this.customRate = Math.max(1, parseFloat((this.customRate + delta).toFixed(1)));
            localStorage.setItem('krushi_where_to_sell_rate', this.customRate);
            this.hasUncalculatedChanges = true;
        },

        setSort(s) {
            this.sort = s;
            this.hasUncalculatedChanges = true;
        },

        setMaxDistance(dist) {
            this.maxDistance = dist ? Number(dist) : null;
            this.hasUncalculatedChanges = true;
        },

        toggleRoundTrip() {
            this.roundTrip = !this.roundTrip;
            this.hasUncalculatedChanges = true;
        },

        onDistrictChange() {
            this.lat = null;
            this.lng = null;
            this.gpsActive = false;
            this.setGpsLabel();
            this.talukId = null;
            this.syncTaluks();
            this.hasUncalculatedChanges = true;
        },

        onTalukChange() {
            this.lat = null;
            this.lng = null;
            this.gpsActive = false;
            this.hasUncalculatedChanges = true;
        },

        acquireGpsLocation() {
            if (!navigator.geolocation) {
                alert(this.locale === 'en' ? 'GPS geolocation is not supported by your browser.' : 'ನಿಮ್ಮ ಬ್ರೌಸರ್‌ನಲ್ಲಿ GPS ಸೌಲಭ್ಯ ಲಭ್ಯವಿಲ್ಲ.');
                return;
            }

            this.gpsLoading = true;
            this.gpsLabelText = (this.locale === 'en') ? 'Detecting Location...' : 'ಸ್ಥಳ ಪಡೆಯಲಾಗುತ್ತಿದೆ... (Detecting GPS)';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.districtId = null;
                    this.talukId = null;
                    this.currentTaluks = [];
                    this.gpsActive = true;
                    this.gpsLoading = false;
                    this.setGpsLabel();
                    this.hasUncalculatedChanges = true;
                    this.updateMapMarkers();
                },
                (err) => {
                    console.warn('GPS location failed, trying IP fallback:', err);
                    fetch('https://ipwho.is/')
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.success !== false && data.latitude && data.longitude) {
                                this.lat = data.latitude;
                                this.lng = data.longitude;
                                this.districtId = null;
                                this.talukId = null;
                                this.currentTaluks = [];
                                this.gpsActive = true;
                                this.gpsLabelText = (this.locale === 'en') ? 'Network Location Active' : 'ನೆಟ್‌ವರ್ಕ್ ಸ್ಥಳ ಸಕ್ರಿಯವಾಗಿದೆ';
                                this.hasUncalculatedChanges = true;
                                this.updateMapMarkers();
                            } else {
                                throw new Error('IP unavailable');
                            }
                        })
                        .catch(() => {
                            this.gpsActive = false;
                            this.gpsLabelText = (this.locale === 'en') ? 'GPS Failed - Select District' : 'ಸ್ಥಳ ಪತ್ತೆ ವಿಫಲ - ಜಿಲ್ಲೆ ಆಯ್ಕೆಮಾಡಿ';
                        })
                        .finally(() => {
                            this.gpsLoading = false;
                        });
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
            );
        },

        triggerCalculationDebounced() {
            this.hasUncalculatedChanges = true;
        },

        async fetchComparison(scrollToResult = true) {
            if (!this.selectedCrop) return;

            this.isLoading = true;

            const params = new URLSearchParams({
                crop_id: this.selectedCrop.id,
                quantity: this.quantity,
                vehicle: this.vehicle,
                rate_type: this.rateType,
                sort: this.sort
            });

            if (this.varietyId) params.append('variety_id', this.varietyId);
            if (this.baselineMarketId) params.append('market_id', this.baselineMarketId);
            if (this.customRate) params.append('custom_rate', this.customRate);
            if (this.maxDistance) params.append('max_distance', this.maxDistance);
            params.append('round_trip', this.roundTrip ? '1' : '0');

            if (this.lat && this.lng) {
                params.append('lat', this.lat);
                params.append('lng', this.lng);
            } else if (this.talukId) {
                params.append('taluk_id', this.talukId);
            } else if (this.districtId) {
                params.append('district_id', this.districtId);
            }

            try {
                const res = await fetch(`${config.apiUrl}?${params.toString()}`);
                const data = await res.json();

                if (data && data.success) {
                    this.comparison = data;
                    this.markets = data.markets || [];
                    this.recommendedMarket = data.recommended_market || null;
                    this.baselineMarket = data.baseline_market || null;
                    this.isCustomBaseline = Boolean(data.is_custom_baseline);
                    
                    const orig = data.origin;
                    this.originLabel = (this.locale === 'en' 
                        ? (orig?.name || orig?.name_kn) 
                        : (orig?.name_kn || orig?.name)) || 'Karnataka';

                    this.hasResults = Boolean(this.markets && this.markets.length > 0);
                    this.hasCalculated = true;

                    // Update Leaflet Map and scroll to results once DOM renders
                    this.$nextTick(() => {
                        this.initLeafletMap();
                        this.updateMapMarkers();
                        if (scrollToResult) {
                            this.scrollToResults();
                        }
                    });

                    // All pending changes have been successfully calculated!
                    this.hasUncalculatedChanges = false;
                }
            } catch (e) {
                console.error('Failed to fetch where-to-sell comparison:', e);
            } finally {
                this.isLoading = false;
            }
        },

        scrollToResults() {
            this.$nextTick(() => {
                const el = document.getElementById('resultsSection');
                if (el) {
                    const topOffset = el.getBoundingClientRect().top + window.pageYOffset - 90;
                    window.scrollTo({ top: Math.max(0, topOffset), behavior: 'smooth' });
                }
            });
        },

        // =========================================================================
        // LEAFLET INTERACTIVE MAP IMPLEMENTATION
        // =========================================================================
        initLeafletMap() {
            const mapContainer = document.getElementById('whereToSellMap');
            if (!mapContainer || typeof L === 'undefined') return;

            if (this.mapInstance) {
                this.updateMapMarkers();
                setTimeout(() => this.mapInstance?.invalidateSize(), 100);
                return;
            }

            const centerLat = this.lat || this.comparison?.origin?.latitude || 13.9299;
            const centerLng = this.lng || this.comparison?.origin?.longitude || 75.5681;

            this.mapInstance = L.map('whereToSellMap', {
                center: [centerLat, centerLng],
                zoom: 8,
                zoomControl: true,
                scrollWheelZoom: false
            });

            let tileUrl = '';
            let attribution = '&copy; OpenStreetMap &copy; CARTO';
            let subdomains = 'abcd';

            const apiKey = (this.mapApiKey || '').trim();
            const provider = this.mapTileProvider || 'carto_voyager';
            const customUrl = (this.mapCustomTileUrl || '').trim();

            if (provider === 'custom' && customUrl !== '') {
                tileUrl = customUrl;
                if (apiKey && tileUrl.includes('{api_key}')) {
                    tileUrl = tileUrl.replace('{api_key}', encodeURIComponent(apiKey));
                } else if (apiKey && !tileUrl.includes('api_key=')) {
                    tileUrl += (tileUrl.includes('?') ? '&' : '?') + 'api_key=' + encodeURIComponent(apiKey);
                }
            } else if (provider === 'osm_standard') {
                tileUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors';
                subdomains = 'abc';
            } else if (provider === 'carto_positron') {
                if (apiKey) {
                    tileUrl = `https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png?api_key=${encodeURIComponent(apiKey)}`;
                    attribution = '&copy; OpenStreetMap &copy; CARTO';
                } else {
                    tileUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
                    attribution = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors';
                    subdomains = 'abc';
                }
            } else {
                // Default: carto_voyager
                if (apiKey) {
                    tileUrl = `https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png?api_key=${encodeURIComponent(apiKey)}`;
                    attribution = '&copy; OpenStreetMap &copy; CARTO';
                } else {
                    tileUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
                    attribution = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors';
                    subdomains = 'abc';
                }
            }

            L.tileLayer(tileUrl, {
                attribution: attribution,
                maxZoom: 18,
                subdomains: subdomains
            }).addTo(this.mapInstance);

            this.mapMarkersLayer = L.layerGroup().addTo(this.mapInstance);

            this.mapInstance.on('click', (e) => {
                const clickedLat = e.latlng.lat;
                const clickedLng = e.latlng.lng;

                if (!this.lat || !this.lng) {
                    const promptText = (this.locale === 'en')
                        ? 'Would you like to automatically detect your current GPS location?\n\nClick "OK" to use GPS, or "Cancel" to place your farm pin at the location you just clicked on the map.'
                        : 'ನಿಮ್ಮ ನಿಖರವಾದ ಪ್ರಸ್ತುತ ಸ್ಥಳವನ್ನು ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಪಡೆಯಬೇಕೆ? (Use Current GPS?)\n\n"OK" ಕ್ಲಿಕ್ ಮಾಡಿದರೆ ಜಿಪಿಎಸ್ ಆನ್ ಆಗುತ್ತದೆ, "Cancel" ಕ್ಲಿಕ್ ಮಾಡಿದರೆ ನೀವು ನಕ್ಷೆಯಲ್ಲಿ ಕ್ಲಿಕ್ ಮಾಡಿದ ಸ್ಥಳವನ್ನೇ ಬಳಸಲಾಗುವುದು.';
                    
                    const wantGps = confirm(promptText);
                    if (wantGps) {
                        this.acquireGpsLocation();
                        return;
                    }
                }

                this.lat = clickedLat;
                this.lng = clickedLng;
                this.districtId = null;
                this.talukId = null;
                this.gpsActive = true;
                this.gpsLabelText = (this.locale === 'en') ? 'Map Pin Active' : 'ನಕ್ಷೆ ಸ್ಥಳ ಸಕ್ರಿಯವಾಗಿದೆ (Map Pin Active)';
                this.hasUncalculatedChanges = true;
                this.updateMapMarkers();
            });

            this.updateMapMarkers();
        },

        updateMapMarkers() {
            if (!this.mapInstance || !this.mapMarkersLayer || typeof L === 'undefined') return;

            this.mapMarkersLayer.clearLayers();
            const bounds = [];

            // 1. Plot Farmer Origin Pin
            const originLat = this.lat || this.comparison?.origin?.latitude;
            const originLng = this.lng || this.comparison?.origin?.longitude;

            if (originLat && originLng) {
                const originIcon = L.divIcon({
                    className: 'custom-origin-marker',
                    html: `
                        <div style="position:relative; width:34px; height:34px; display:flex; align-items:center; justify-content:center;">
                            <div style="position:absolute; width:100%; height:100%; border-radius:50%; background:rgba(37,99,235,0.3); animation:ping 2s cubic-bezier(0,0,0.2,1) infinite;"></div>
                            <div style="width:28px; height:28px; border-radius:50%; background:#1d4ed8; border:3px solid #ffffff; box-shadow:0 4px 6px -1px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px;">
                                📍
                            </div>
                        </div>
                    `,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17]
                });

                const originMarker = L.marker([originLat, originLng], { icon: originIcon });
                const originTitle = (this.locale === 'en') ? '📍 Your Farm Origin' : '📍 ನಿಮ್ಮ ಸ್ಥಳ (Your Origin)';
                originMarker.bindPopup(`
                    <div style="font-family:sans-serif; font-size:12px; line-height:1.4;">
                        <strong style="color:#1d4ed8; font-size:13px;">${originTitle}</strong><br>
                        <span>${this.originLabel}</span><br>
                        <small style="color:#64748b;">(${originLat.toFixed(3)}, ${originLng.toFixed(3)})</small>
                    </div>
                `);
                this.mapMarkersLayer.addLayer(originMarker);
                bounds.push([originLat, originLng]);
            }

            // 2. Plot Mandis
            if (this.markets && this.markets.length > 0) {
                this.markets.forEach((m, idx) => {
                    if (!m.latitude || !m.longitude) return;

                    const isChampion = (idx === 0);
                    const isBaseline = Boolean(m.is_baseline);
                    let markerColor = '#059669';
                    let markerEmoji = '🌾';
                    let size = 28;

                    if (isChampion) {
                        markerColor = '#f59e0b';
                        markerEmoji = '🏆';
                        size = 38;
                    } else if (isBaseline) {
                        markerColor = '#0284c7';
                        markerEmoji = '🎯';
                        size = 34;
                    }

                    const mandiIcon = L.divIcon({
                        className: 'custom-mandi-marker',
                        html: `
                            <div style="position:relative; width:${size}px; height:${size}px; display:flex; align-items:center; justify-content:center;">
                                ${isChampion ? '<div style="position:absolute; width:100%; height:100%; border-radius:50%; background:rgba(245,158,11,0.4); animation:pulse 1.5s infinite;"></div>' : ''}
                                ${isBaseline && !isChampion ? '<div style="position:absolute; width:100%; height:100%; border-radius:50%; background:rgba(2,132,199,0.3); animation:pulse 1.8s infinite;"></div>' : ''}
                                <div style="width:${size - 4}px; height:${size - 4}px; border-radius:50%; background:${markerColor}; border:2.5px solid #ffffff; box-shadow:0 4px 6px -1px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:${isChampion ? '15px' : (isBaseline ? '14px' : '11px')}; font-weight:bold;">
                                    ${markerEmoji}
                                </div>
                            </div>
                        `,
                        iconSize: [size, size],
                        iconAnchor: [size / 2, size / 2]
                    });

                    const marker = L.marker([m.latitude, m.longitude], { icon: mandiIcon });
                    
                    const mName = (this.locale === 'en') ? m.market_name : (m.market_name_kn || m.market_name);
                    const championBadge = (this.locale === 'en') ? '🏆 #1 Top Market' : '🏆 #1 ಶಿಫಾರಸು ಮಂಡಿ';
                    const baselineBadge = (this.locale === 'en') ? '🎯 Comparison Baseline' : '🎯 ಆಧಾರ ಮಂಡಿ';
                    const netTitle = (this.locale === 'en') ? 'Net Take-Home:' : 'ಕೈಗೆ ಸಿಗುವ ಹಣ:';
                    const navBtn = (this.locale === 'en') ? 'Navigate on Google Maps ➔' : 'Google Maps ನಲ್ಲಿ ದಾರಿ ನೋಡಿ ➔';
                    const isRt = Boolean(m.is_round_trip && (this.rateType === 'per_km' || this.rateType === 'fuel_only'));
                    const distLabel = (this.locale === 'en') 
                        ? `${m.distance_km} km${isRt ? ` (${m.billed_km} km RT)` : ''} • ~${m.transit_hours}h` 
                        : `${m.distance_km} ಕಿ.ಮೀ${isRt ? ` (${m.billed_km} km RT)` : ''} • ~${m.transit_hours}h`;
                    const dateBadge = (this.locale === 'en') ? (m.freshness_badge_en || m.as_of_label_en) : (m.freshness_badge_kn || m.as_of_label_kn);

                    const popupHtml = `
                        <div style="font-family:sans-serif; font-size:12px; line-height:1.4; min-width:180px;">
                            ${isChampion ? `<span style="background:#fef3c7; color:#92400e; font-weight:bold; font-size:10px; padding:2px 6px; border-radius:4px; display:inline-block; margin-bottom:4px;">${championBadge}</span> ` : ''}
                            ${isBaseline ? `<span style="background:#e0f2fe; color:#0369a1; font-weight:bold; font-size:10px; padding:2px 6px; border-radius:4px; display:inline-block; margin-bottom:4px;">${baselineBadge}</span>` : ''}
                            <strong style="color:#0f172a; font-size:13px; display:block;">${mName}</strong>
                            <span style="color:#64748b; font-size:11px;">🛣️ ${distLabel}</span><br>
                            <span style="color:#047857; font-weight:bold; font-size:11px;">${dateBadge}</span>
                            <div style="margin-top:6px; padding:6px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0;">
                                <span style="color:#166534; font-size:11px;">${netTitle}</span><br>
                                <strong style="color:#14532d; font-size:15px; font-weight:900;">₹${this.formatNumber(m.net_realization)}</strong>
                                <span style="color:#15803d; font-size:10px;"> (₹${this.formatNumber(m.modal_price)}/Q)</span>
                            </div>
                            <div style="margin-top:8px;">
                                <a href="${m.google_maps_url}" target="_blank" rel="noopener" style="display:inline-block; width:100%; text-align:center; background:#10b981; color:#ffffff; font-weight:bold; padding:4px 8px; border-radius:6px; text-decoration:none; font-size:11px;">
                                    ${navBtn}
                                </a>
                            </div>
                        </div>
                    `;

                    marker.bindPopup(popupHtml);
                    this.mapMarkersLayer.addLayer(marker);
                    bounds.push([m.latitude, m.longitude]);
                });
            }

            if (bounds.length > 0) {
                this.mapInstance.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
            }
        },

        resetMapBounds() {
            this.updateMapMarkers();
        },

        // Helpers
        getVehicleSummaryText() {
            const vNames = (this.locale === 'en') ? {
                'auto': 'Auto Rickshaw',
                'pickup': 'Bolero / Pickup',
                'truck': 'Canter / Mini Truck'
            } : {
                'auto': 'ಆಟೋ / 3-ಚಕ್ರ',
                'pickup': 'ಪಿಕಪ್ / ಬೊಲೆರೊ',
                'truck': 'ಮಿನಿ ಲಾರಿ / ಕ್ಯಾಂಟರ್'
            };
            return (vNames[this.vehicle] || 'Vehicle') + ' (' + this.getRateUnitLabel() + ')';
        },

        getRateUnitLabel() {
            if (this.rateType === 'per_quintal') return '₹' + this.customRate + '/Qtl';
            if (this.rateType === 'fixed_fare') return '₹' + this.customRate + (this.locale === 'en' ? ' Flat' : ' ಒಟ್ಟು');
            if (this.rateType === 'fuel_only') return '₹' + this.customRate + (this.locale === 'en' ? '/km (Fuel)' : '/km (ಇಂಧನ)');
            return '₹' + this.customRate + '/km';
        },

        getRateTypeDescriptionTitle() {
            if (this.locale === 'en') {
                if (this.rateType === 'per_quintal') return 'Per Quintal / Bag Haulage Rate:';
                if (this.rateType === 'fixed_fare') return 'Agreed Fixed Trip Hire (Lumpsum):';
                if (this.rateType === 'fuel_only') return 'Own Vehicle Diesel Fuel Cost (Per Km):';
                return 'Custom Negotiated Rate (Per Km):';
            } else {
                if (this.rateType === 'per_quintal') return 'ಪ್ರತಿ ಕ್ವಿಂಟಾಲ್ / ಚೀಲಕ್ಕೆ ಸಾಗಣೆ ದರ:';
                if (this.rateType === 'fixed_fare') return 'ಸ್ಥಿರ ಒಟ್ಟು ಗುತ್ತಿಗೆ ಬಾಡಿಗೆ:';
                if (this.rateType === 'fuel_only') return 'ಸ್ವಂತ ವಾಹನದ ಡೀಸೆಲ್ / ಇಂಧನ ವೆಚ್ಚ:';
                return 'ನಿಮ್ಮ ಸ್ವಂತ ಅಥವಾ ಒಪ್ಪಂದದ ಪ್ರತಿ ಕಿ.ಮೀ ದರ:';
            }
        },

        getRateTypeDescriptionHelp() {
            if (this.locale === 'en') {
                if (this.rateType === 'per_quintal') return 'When transport charges per bag/quintal regardless of distance (typically ₹30–₹60/Q).';
                if (this.rateType === 'fixed_fare') return 'Enter the total negotiated round-trip cost for the entire vehicle hire.';
                if (this.rateType === 'fuel_only') return 'If using your own tractor or jeep, count pure diesel fuel cost (~₹10–₹14/km).';
                return 'Standard: Auto ₹15/km, Bolero ₹20–₹25/km, Canter ₹30–₹35/km.';
            } else {
                if (this.rateType === 'per_quintal') return 'ಚಾಲಕರು ಕಿ.ಮೀ ಬದಲಿಗೆ ಪ್ರತಿ ಚೀಲಕ್ಕೆ/ಕ್ವಿಂಟಾಲ್‌ಗೆ ದರ ವಿಧಿಸಿದರೆ (ಸಾಮಾನ್ಯವಾಗಿ ₹30–₹60/Q) ಇಲ್ಲಿ ನಮೂದಿಸಿ.';
                if (this.rateType === 'fixed_fare') return 'ಇಡೀ ಟ್ರಿಪ್‌ಗೆ ಒಪ್ಪಂದ ಮಾಡಿಕೊಂಡ ಒಟ್ಟು ಗುತ್ತಿಗೆ ಮೊತ್ತವನ್ನು ನಮೂದಿಸಿ.';
                if (this.rateType === 'fuel_only') return 'ಸ್ವಂತ ಟ್ರ್ಯಾಕ್ಟರ್/ಜೀಪ್ ಇದ್ದರೆ ಕೇವಲ ಡೀಸೆಲ್ ವೆಚ್ಚವನ್ನು (ಸಾಮಾನ್ಯವಾಗಿ ₹10–₹14/km) ಲೆಕ್ಕಹಾಕಿ.';
                return 'ಸಾಮಾನ್ಯ ಆಟೋ ₹15, ಬೊಲೆರೊ ₹20-25, ಕ್ಯಾಂಟರ್ ₹30-35. ನಿಮ್ಮ ಊರಿನ ದರಕ್ಕೆ ಹೊಂದಿಸಿ.';
            }
        },

        formatNumber(val) {
            if (val === null || val === undefined || isNaN(val)) return '0';
            return Math.round(val).toLocaleString('en-IN');
        },

        roundCoord(c) {
            return c ? c.toFixed(3) : '';
        }
    };
}
</script>
@endsection
