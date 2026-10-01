@extends('layouts.farmer')

@section('title', 'ಕೃಷಿ ಬಾಂಧವ — ಕರ್ನಾಟಕ ರೈತರ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಮತ್ತು ಮುನ್ಸೂಚನೆ')

@section('content')
@php
    $activeLocale = app()->getLocale();
    $searchableCropsJson = json_encode(($sortedPrices ?? $distinctCropPrices)->map(function ($p) use ($activeLocale) {
        $marketName = $activeLocale === 'kn' ? ($p->market->name_kn ?? $p->market->name ?? '') : ($p->market->name ?? '');
        $districtName = $activeLocale === 'kn' ? ($p->market->district->name_kn ?? $p->market->district->name ?? '') : ($p->market->district->name ?? '');
        return [
            'id' => $p->crop_id,
            'name' => $p->crop->name ?? '',
            'name_kn' => $p->crop->name_kn ?? $p->crop->name ?? '',
            'market' => $marketName,
            'district' => $districtName,
            'market_raw' => $p->market->name ?? '',
            'market_kn' => $p->market->name_kn ?? '',
            'district_raw' => $p->market->district->name ?? '',
            'district_kn' => $p->market->district->name_kn ?? '',
            'modal_price' => number_format($p->modal_price ?? 0),
            'unit' => $p->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್'),
            'photo' => $p->crop->photo_url ?? '',
            'url' => route('farmer.crop.detail', $p->crop_id) . '?market=' . urlencode($p->market->name ?? ''),
            'trend' => ($p->daily_price_change ?? 0) > 0 ? '↑' : (($p->daily_price_change ?? 0) < 0 ? '↓' : '→'),
            'trend_class' => ($p->daily_price_change ?? 0) > 0 ? 'text-emerald-700 bg-emerald-50' : (($p->daily_price_change ?? 0) < 0 ? 'text-red-700 bg-red-50' : 'text-stone-600 bg-stone-100'),
        ];
    })->values());
@endphp

<script>
    window.kbSearchableCrops = {!! $searchableCropsJson !!};
</script>

<div class="space-y-6" x-data="farmerHome()">

    <!-- ==================== 1. HERO BANNER (Negilu Krushi Clean Master Standard) ==================== -->
    <section class="rounded-3xl relative shadow-lg border-2 border-[#D9CEB8] min-h-[300px] sm:min-h-[340px] flex flex-col justify-between z-30 w-full max-w-full min-w-0"
             style="background: linear-gradient(147deg, rgba(16, 54, 28, 0.94) 0%, rgb(12 42 22 / 65%) 50%, rgba(6, 22, 11, 0.88) 100%), url('{{ asset('images/hero_farmer.jpg') }}') center right / cover no-repeat;">
        
        <!-- Hero Content -->
        <div class="p-4 sm:p-8 z-10 w-full space-y-2.5 sm:space-y-3">
            
            <!-- Top Eyebrow Row: Live Status on Left & How to Use on Right -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <!-- Live Eyebrow -->
                <div class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 rounded-full bg-black/40 backdrop-blur-md text-[11px] sm:text-xs font-bold text-emerald-200 border border-emerald-500/40 shadow-sm max-w-full">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                    <span class="truncate min-w-0">{{ $activeLocale === 'en' ? 'Live Market Data • Live Mandi Rates' : 'ದೈನಂದಿನ ಅಧಿಕೃತ ಮಂಡಿ ದರಗಳು (ನೇರ ಮಾರುಕಟ್ಟೆ ದತ್ತಾಂಶ)' }} • {{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Karnataka') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಕರ್ನಾಟಕ') }}</span>
                </div>

                <!-- How to Use Guide Pill (Top Right Corner) -->
                <a href="{{ route('farmer.articles.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-400/20 hover:bg-amber-400/30 text-amber-300 text-xs font-black border border-amber-400/40 backdrop-blur-md shadow-sm transition hover:scale-105 active:scale-95 shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="inline-flex items-center leading-none text-xs shrink-0">💡</span>
                    <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'How to Use? ›' : 'ಹೇಗೆ ಬಳಸುವುದು? ›' }}</span>
                </a>
            </div>

            <div class="max-w-3xl space-y-2.5 sm:space-y-3">
                <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' 
                        ? \App\Models\SystemSetting::get('hero_headline_en', "Let every drop of sweat earn its true reward; let market strength be in the farmer's hands")
                        : \App\Models\SystemSetting::get('hero_headline_kn', 'ಬೆವರ ಹನಿಗೆ ಸಿಗಲಿ ತಕ್ಕ ಪ್ರತಿಫಲ, ರೈತನ ಕೈಲಿರಲಿ ಮಾರುಕಟ್ಟೆಯ ಬಲ') }}
                </h1>

                <p class="text-xs sm:text-sm text-emerald-100 font-medium max-w-xl {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} leading-relaxed">
                    {{ $activeLocale === 'en' 
                        ? \App\Models\SystemSetting::get('hero_subtitle_en', 'Live prices and future trends from Karnataka mandis and agricultural markets.')
                        : \App\Models\SystemSetting::get('hero_subtitle_kn', 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಮಂಡಿಗಳ ಇಂದಿನ ನೇರ ದರ ಮತ್ತು ದರ ಮುನ್ಸೂಚನೆ.') }}
                </p>
            </div>
        </div>

        <!-- Mobile-First Classic Integrated Command Dock (With Live Debounced Amazon Search) -->
        <div class="p-3 sm:p-6 relative z-30">
            <div class="backdrop-blur-xs rounded-2xl sm:rounded-3xl border-2 border-[#D9CEB8] shadow-2xl p-2.5 sm:p-3 md:py-2.5 md:px-4 relative flex flex-col md:flex-row md:items-center gap-2.5 md:gap-4"
                 x-data="{
                     searchQuery: '',
                     isSearchOpen: false,
                     results: [],
                     allCrops: window.kbSearchableCrops || [],
                     
                     performSearch(isFromFocus = false) {
                         const q = this.searchQuery.toLowerCase().trim();
                         if (!q) {
                             if (isFromFocus) {
                                 this.results = this.allCrops.slice(0, 6);
                                 this.isSearchOpen = true;
                             } else {
                                 this.results = [];
                                 this.closeSearch(false);
                             }
                             return;
                         }
                         this.results = this.allCrops.filter(c => 
                             (c.name && c.name.toLowerCase().includes(q)) || 
                             (c.name_kn && c.name_kn.toLowerCase().includes(q)) ||
                             (c.market && c.market.toLowerCase().includes(q)) ||
                             (c.district && c.district.toLowerCase().includes(q)) ||
                             (c.market_raw && c.market_raw.toLowerCase().includes(q)) ||
                             (c.market_kn && c.market_kn.toLowerCase().includes(q)) ||
                             (c.district_raw && c.district_raw.toLowerCase().includes(q)) ||
                             (c.district_kn && c.district_kn.toLowerCase().includes(q))
                         ).slice(0, 8);
                         this.isSearchOpen = true;
                     },
                     closeSearch(shouldBlur = true) {
                         this.isSearchOpen = false;
                         if (shouldBlur && this.$refs.searchInput) {
                             this.$refs.searchInput.blur();
                         }
                     },
                     clearSearch() {
                         this.searchQuery = '';
                         this.results = [];
                         this.closeSearch(true);
                     },
                     toggleSearch() {
                         if (this.isSearchOpen) {
                             this.closeSearch(true);
                         } else {
                             if (this.$refs.searchInput) {
                                 this.$refs.searchInput.focus();
                             }
                             this.performSearch(true);
                         }
                     }
                 }"
                 @keydown.escape.window="closeSearch(true)"
                 @scroll.window="closeSearch(true)">
                
                <!-- Mandi Hub (Row 1 on Mobile, Left Column on Desktop) -->
                <div class="flex items-center justify-between md:justify-start gap-2 sm:gap-3 md:shrink-0" @click="closeSearch(true)">
                    <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center text-sm sm:text-base font-black shrink-0 border border-emerald-200">
                            📍
                        </div>
                        <div class="min-w-0 flex flex-col justify-center">
                            <span class="text-[9px] sm:text-[10px] text-amber-300 font-bold uppercase tracking-wider block {{ $activeLocale === 'kn' ? 'font-kannada leading-tight' : 'leading-none' }}">
                                {{ $activeLocale === 'en' ? 'Your Mandi Center' : 'ನಿಮ್ಮ ಮಂಡಿ ಕೇಂದ್ರ' }}
                            </span>
                            <span class="font-black text-xs sm:text-sm text-white block truncate {{ $activeLocale === 'kn' ? 'font-kannada leading-normal pt-1 pb-0.5' : 'font-sans leading-tight mt-0.5' }}">
                                {{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Karnataka') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಕರ್ನಾಟಕ') }}
                            </span>
                        </div>
                    </div>

                    <button type="button"
                            @click="$dispatch('open-location-modal'); closeSearch(true);"
                            class="btn-mandi-change bg-[#EAF4EC] hover:bg-[#1C5A2C] text-[#1C5A2C] hover:text-white px-2.5 py-1.5 md:px-3 md:py-1.5 rounded-xl text-[11px] font-black border border-[#B8DEC0] transition cursor-pointer shrink-0 md:ml-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Change ▾' : 'ಬದಲಿಸಿ ▾' }}
                    </button>
                </div>

                <!-- Divider: Horizontal on mobile, Vertical on desktop -->
                <div class="border-t border-[#E5DECE] md:hidden"></div>
                <div class="hidden md:block w-px h-8 bg-[#D9CEB8] shrink-0"></div>

                <!-- Search Bar (Row 2 on Mobile, Expanded Right Column on Desktop) -->
                <div class="relative flex-1 min-w-0" @click.away="closeSearch(true)">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-[#1C5A2C] text-xs transition-colors duration-200 cursor-pointer"
                       @click="toggleSearch()"
                       title="{{ $activeLocale === 'en' ? 'Toggle search' : 'ಹುಡುಕಾಟ' }}"></i>
                    
                    <input type="text"
                           x-ref="searchInput"
                           x-model="searchQuery"
                           @input.debounce.150ms="performSearch(false)"
                           @focus="performSearch(true)"
                           @keydown.escape.stop="clearSearch()"
                           placeholder="{{ $activeLocale === 'en' ? 'Search any crop or mandi (e.g. Arecanut, Pepper, Tomato)...' : 'ಯಾವುದೇ ಬೆಳೆ ಅಥವಾ ಮಂಡಿ ಹುಡುಕಿ... (ಅಡಿಕೆ, ಕಾಳುಮೆಣಸು, ಟೊಮೆಟೊ)' }}"
                           class="home-search-input w-full pl-9 pr-9 py-2 sm:py-2.5 rounded-xl bg-white border border-[#D9CEB8] text-xs sm:text-sm font-semibold text-amber-300 placeholder-stone-400 focus:outline-none focus:border-[#1C5A2C] focus:ring-2 focus:ring-[#1C5A2C]/20 focus:bg-emerald-50/10 shadow-inner transition-all duration-200 {{ $activeLocale === 'kn' ? 'font-kannada search-input-kn' : 'font-sans' }}">

                    <!-- Clear / Close ✕ Button with smooth scale/fade transition -->
                    <button type="button"
                            x-show="searchQuery.length > 0 || isSearchOpen"
                            x-transition:enter="transition ease-out duration-150 transform"
                            x-transition:enter-start="opacity-0 scale-75"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100 transform"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-75"
                            @click.stop="clearSearch()"
                            title="{{ $activeLocale === 'en' ? 'Close search' : 'ಹುಡುಕಾಟ ಮುಚ್ಚಿ' }}"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 text-xs cursor-pointer p-1" style="display: none;">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>

                    <!-- AMAZON-STYLE LIVE FLOATING DROPDOWN WITH SILKY SMOOTH SLIDE & FADE -->
                    <div x-show="isSearchOpen"
                         x-transition:enter="transition ease-out duration-250 transform"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150 transform"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                         style="display: none;"
                         class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-[0_20px_45px_-15px_rgba(28,90,44,0.15),0_10px_20px_-5px_rgba(0,0,0,0.08)] border-2 border-[#1C5A2C] overflow-hidden z-50 divide-y divide-stone-100 max-h-52 md:max-h-59 overflow-y-auto scroll-smooth">
                        
                        <!-- Header counter & explicit Close ✕ button -->
                        <div class="px-3.5 py-1.5 bg-[#FAF8F5] text-[10px] font-bold text-stone-500 flex items-center justify-between border-b border-stone-100">
                            <span x-text="searchQuery.trim().length === 0 ? '{{ $activeLocale === 'en' ? 'Popular Karnataka Crops' : 'ಪ್ರಮುಖ ಬೆಳೆಗಳು' }}' : (results.length > 0 ? (results.length + ' {{ $activeLocale === 'en' ? 'crops found' : 'ಬೆಳೆಗಳು ಲಭ್ಯ' }}') : '{{ $activeLocale === 'en' ? 'Search Results' : 'ಫಲಿತಾಂಶ' }}')"></span>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] text-[#1C5A2C] font-black uppercase">LIVE MANDI</span>
                                <button type="button"
                                        @click.stop="closeSearch(true)"
                                        class="text-stone-400 hover:text-stone-800 text-[11px] font-black px-1.5 py-0.5 rounded hover:bg-stone-200 transition cursor-pointer flex items-center gap-0.5"
                                        title="{{ $activeLocale === 'en' ? 'Close' : 'ಮುಚ್ಚಿ' }}">
                                    <span>✕</span>
                                    <span class="text-[9px] font-semibold">{{ $activeLocale === 'en' ? 'Close' : 'ಮುಚ್ಚಿ' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Results List -->
                        <template x-for="item in results" :key="item.id">
                            <a :href="item.url" 
                               class="flex items-center justify-between p-2.5 sm:p-3 hover:bg-[#EAF4EC] hover:pl-3.5 sm:hover:pl-4 transition-all duration-200 group cursor-pointer text-left tap-feedback active:scale-[0.985]">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img :src="item.photo" :alt="item.name" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-cover border border-stone-200 shrink-0 group-hover:scale-105 transition-transform duration-200 shadow-xs">
                                    <div class="min-w-0">
                                        <div class="font-black text-xs sm:text-sm text-stone-900 group-hover:text-[#1C5A2C] transition-colors duration-150 truncate {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                            @if($activeLocale === 'kn')
                                                <span x-text="item.name_kn || item.name"></span>
                                                <span class="text-[10px] text-stone-400 font-normal ml-1" x-show="item.name_kn && item.name && item.name_kn !== item.name" x-text="'(' + item.name + ')'"></span>
                                            @else
                                                <span x-text="item.name"></span>
                                                <span class="text-[10px] text-stone-400 font-normal ml-1" x-show="item.name_kn && item.name_kn !== item.name" x-text="'(' + item.name_kn + ')'"></span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-stone-500 font-medium truncate flex items-center gap-1 mt-0.5">
                                            <span class="text-[9px] text-[#1C5A2C]">📍</span>
                                            <span x-text="item.market ? (item.market + (item.district ? ' · ' + item.district : '')) : '{{ $activeLocale === 'en' ? 'Karnataka Mandi' : 'ಕರ್ನಾಟಕ ಮಂಡಿ' }}'"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 pl-2">
                                    <div class="text-xs sm:text-sm font-black text-[#1C5A2C] leading-none group-hover:scale-105 transition-transform duration-150" x-text="'₹' + item.modal_price"></div>
                                    <div class="text-[9px] text-stone-500 mt-0.5" x-text="'/ ' + item.unit"></div>
                                </div>
                            </a>
                        </template>

                        <!-- No results message -->
                        <div x-show="results.length === 0 && searchQuery.trim().length > 0" class="p-5 text-center text-xs text-stone-500 bg-white space-y-1">
                            <span class="text-2xl block">🔍</span>
                            <p class="font-bold text-stone-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? 'No matching crops found' : 'ಯಾವುದೇ ಬೆಳೆ ಕಂಡುಬಂದಿಲ್ಲ' }}
                            </p>
                            <p class="text-[11px] text-stone-400 mt-0.5">
                                {{ $activeLocale === 'en' ? 'Try searching Arecanut, Pepper, Tomato, Onion, etc.' : 'ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ ಅಥವಾ ಮಂಡಿ ಹೆಸರನ್ನು ಟೈಪ್ ಮಾಡಿ' }}
                            </p>
                        </div>

                        <!-- Footer: View All in Directory -->
                        <a href="#allCropsSection" 
                           @click="isSearchOpen = false" 
                           class="block p-2 bg-[#FAF8F5] hover:bg-[#F2ECE1] text-center text-xs font-black text-[#1C5A2C] transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Explore all 40+ crops in directory ↓' : 'ಎಲ್ಲಾ 40+ ಬೆಳೆಗಳನ್ನು ಕೆಳಗೆ ಪಟ್ಟಿಯಲ್ಲಿ ನೋಡಿ ↓' }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==================== 2. TODAY SNAPSHOT (TOP 4 RATES + DEEP GREEN WEATHER CARD) ==================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-start">
        
        <!-- Left Column: Top 4 Spotlight Rates (8 Cols) -->
        <div class="lg:col-span-8 space-y-3.5">
            
            <div class="flex items-center justify-between pb-1 border-b border-[#E5DECE]">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-base font-black text-[#1C5A2C] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1.5">
                        <span class="shrink-0">🌟</span>
                        <span>{{ $activeLocale === 'en' ? "Today's Key Market Rates" : 'ಇಂದಿನ ಪ್ರಮುಖ ದರಗಳು' }}</span>
                        <span class="text-xs text-stone-500 font-semibold hidden sm:inline">({{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Karnataka') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಕರ್ನಾಟಕ') }})</span>
                    </h2>
                </div>
                <a href="#allCropsSection" class="text-xs font-bold text-[#1C5A2C] hover:underline {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                    <span>{{ $activeLocale === 'en' ? 'View All Crops' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳು ನೋಡಿ' }}</span>
                    <span class="shrink-0">›</span>
                </a>
            </div>

            <!-- Top 4 Spotlight Cards Grid (2 Columns on Mobile, 2 Columns on Tablet/Desktop) -->
            <div class="grid grid-cols-2 gap-2.5 sm:gap-3.5">
                @forelse($topMovers->take(4) as $mover)
                    <a href="{{ route('farmer.crop.detail', $mover->crop_id) }}?market={{ urlencode($mover->market->name) }}"
                       class="bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 group flex flex-col justify-between tap-feedback active:scale-[0.98]">
                        
                        <!-- Full Top Photo (Centered, no awkward cropping, proportionate height) -->
                        <div class="relative h-24 sm:h-32 overflow-hidden bg-stone-100">
                            <img src="{{ $mover->crop->photo_url }}" 
                                 alt="{{ $mover->crop->name }}" 
                                 class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent"></div>
                            
                            <div class="absolute bottom-1.5 left-2 right-2 sm:bottom-2 sm:left-3 sm:right-3 flex items-end justify-between text-white gap-1">
                                <span class="font-extrabold text-xs sm:text-base {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} tracking-wide drop-shadow-sm leading-tight break-words">
                                    {{ $activeLocale === 'en' ? $mover->crop->name : ($mover->crop->name_kn ?? $mover->crop->name) }}
                                </span>
                                
                                @if(($mover->daily_price_change ?? 0) > 0)
                                    <span class="bg-emerald-600/90 text-white text-[9px] sm:text-[11px] font-black px-2 py-1 rounded-full shrink-0 inline-flex items-center justify-center gap-1 leading-none shadow-sm">
                                         <span class="inline-flex items-center leading-none shrink-0 text-[10px]">↑</span>
                                         <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}</span>
                                     </span>
                                 @elseif(($mover->daily_price_change ?? 0) < 0)
                                     <span class="bg-red-500/90 text-white text-[9px] sm:text-[11px] font-black px-2 py-1 rounded-full shrink-0 inline-flex items-center justify-center gap-1 leading-none shadow-sm">
                                         <span class="inline-flex items-center leading-none shrink-0 text-[10px]">↓</span>
                                         <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Drop' : 'ಇಳಿಕೆ' }}</span>
                                     </span>
                                 @else
                                     <span class="bg-blue-600/90 text-white text-[9px] sm:text-[11px] font-black px-2 py-1 rounded-full shrink-0 inline-flex items-center justify-center gap-1 leading-none shadow-sm">
                                         <span class="inline-flex items-center leading-none shrink-0 text-[10px]">→</span>
                                         <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}</span>
                                     </span>
                                 @endif
                            </div>
                        </div>

                        <!-- Card Body (Zero truncation, natural wrap, clear readable hierarchy) -->
                        <div class="p-2.5 sm:p-3.5 flex flex-col justify-between flex-1 gap-1.5">
                            <div>
                                <div class="text-base sm:text-xl font-black text-[#1C5A2C] leading-none">
                                    ₹{{ number_format($mover->modal_price) }} 
                                    <span class="text-[10px] sm:text-xs font-semibold text-stone-600 block sm:inline mt-0.5 sm:mt-0">/ {{ $mover->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್') }}</span>
                                </div>
                                <div class="text-[10px] sm:text-[11px] font-bold text-stone-500 mt-1 leading-tight break-words">
                                    {{ $activeLocale === 'kn' ? ($mover->variety->name_kn ?? $mover->variety->name ?? 'ಸಾಮಾನ್ಯ') : ($mover->variety->name ?? 'Common') }}
                                </div>
                            </div>
                            
                            <div class="flex items-start justify-between text-[10px] sm:text-xs text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} pt-1.5 border-t border-stone-100 gap-1">
                                <span class="flex items-start gap-1 text-stone-700 font-semibold leading-tight break-words">
                                    <span class="shrink-0 text-[10px] mt-0.5">📍</span>
                                    <span>
                                        {{ $activeLocale === 'kn' ? ($mover->market->name_kn ?? $mover->market->name) : $mover->market->name }}
                                        @if(!empty($mover->market->district))
                                            · {{ $activeLocale === 'kn' ? ($mover->market->district->name_kn ?? $mover->market->district->name) : $mover->market->district->name }}
                                        @endif
                                    </span>
                                </span>
                                <span class="text-[10px] sm:text-[11px] text-[#1C5A2C] font-extrabold shrink-0 mt-0.5 flex items-center gap-0.5">
                                    <span>{{ $activeLocale === 'en' ? 'Details' : 'ವಿವರ' }}</span>
                                    <span class="shrink-0">›</span>
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-2 p-6 text-center text-xs text-stone-500 bg-white rounded-2xl border-2 border-dashed border-[#D9CEB8]">
                        {{ $activeLocale === 'en' ? 'No spotlight rate updates for today yet.' : 'ಇಂದಿನ ಮುಖ್ಯಾಂಶ ದರಗಳು ಶೀಘ್ರದಲ್ಲೇ ಅಪ್ಡೇಟ್ ಆಗಲಿವೆ.' }}
                    </div>
                @endforelse
            </div>

        </div>

        <!-- Right Column: Classic Atmospheric Topographic Weather Card (4 Cols Desktop with Shimmer Loading) -->
        <style>
            @keyframes kbWeatherShimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
            .kb-weather-shimmer {
                position: relative;
                overflow: hidden;
            }
            .kb-weather-shimmer::after {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                transform: translateX(-100%);
                background: linear-gradient(
                    90deg,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(255, 255, 255, 0.12) 35%,
                    rgba(255, 255, 255, 0.26) 50%,
                    rgba(255, 255, 255, 0.12) 65%,
                    rgba(255, 255, 255, 0) 100%
                );
                animation: kbWeatherShimmer 1.5s infinite ease-in-out;
                content: '';
            }
        </style>

        <aside class="lg:col-span-4 relative overflow-hidden rounded-3xl p-4 sm:p-5 border border-emerald-500/30 shadow-xl flex flex-col gap-3.5 text-white"
               style="contain: paint; background: radial-gradient(circle at 85% 15%, #257044 0%, #154D2B 45%, #0B2B17 100%);"
               x-data="{
                   isLoading: true,
                   init() {
                       // Initial elegant shimmer load animation (reveals after 450ms)
                       setTimeout(() => { this.isLoading = false; }, 450);

                       // Listen for location changes or weather updates
                       window.addEventListener('weather-updating', () => { this.isLoading = true; });
                       window.addEventListener('weather-updated', () => { this.isLoading = false; });
                   }
               }">
            
            <!-- Classic Topographic Concentric Contour Lines (Top-Right Atmospheric Arcs) -->
            <svg class="absolute -top-6 -right-6 w-52 h-52 sm:w-60 sm:h-60 pointer-events-none text-white select-none z-0" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="200" cy="0" r="170" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.20" />
                <circle cx="200" cy="0" r="135" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.15" />
                <circle cx="200" cy="0" r="100" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.11" />
                <circle cx="200" cy="0" r="65" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.08" />
            </svg>

            <!-- ==================== 1. SHIMMER SKELETON LOADER STATE ==================== -->
            <div x-show="isLoading" 
                 x-transition:leave="transition ease-out duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="space-y-3.5 z-10 relative flex flex-col gap-3.5 w-full select-none">
                
                <!-- Skeleton Header -->
                <div class="flex items-center justify-between border-b border-white/15 pb-2.5">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-white/15 kb-weather-shimmer shrink-0"></div>
                        <div class="w-28 h-5 rounded-md bg-white/20 kb-weather-shimmer"></div>
                    </div>
                    <div class="w-12 h-4 rounded-full bg-white/15 kb-weather-shimmer"></div>
                </div>

                <!-- Skeleton Main Metric (Temperature & Condition) -->
                <div class="flex items-baseline justify-between my-0.5">
                    <div class="w-24 h-12 rounded-2xl bg-white/20 kb-weather-shimmer"></div>
                    <div class="flex flex-col items-end gap-1.5">
                        <div class="w-20 h-4 rounded-md bg-white/15 kb-weather-shimmer"></div>
                        <div class="w-28 h-3 rounded-md bg-white/10 kb-weather-shimmer"></div>
                    </div>
                </div>

                <!-- Skeleton Glassmorphic Rain Probability Card -->
                <div class="bg-white/[0.08] backdrop-blur-md border border-white/15 rounded-2xl p-3 flex items-center gap-3 shadow-inner">
                    <div class="w-8 h-8 rounded-xl bg-white/20 kb-weather-shimmer shrink-0"></div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <div class="w-32 h-3 rounded-md bg-white/15 kb-weather-shimmer"></div>
                        <div class="w-24 h-5 rounded-md bg-white/25 kb-weather-shimmer"></div>
                    </div>
                </div>

                <!-- Skeleton Farm Advisory -->
                <div class="pt-2 border-t border-white/15 flex flex-col gap-1.5">
                    <div class="w-24 h-3.5 rounded-md bg-white/20 kb-weather-shimmer"></div>
                    <div class="w-full h-3 rounded-md bg-white/15 kb-weather-shimmer"></div>
                    <div class="w-4/5 h-3 rounded-md bg-white/10 kb-weather-shimmer"></div>
                </div>

                <!-- Skeleton 3-Day Micro Outlook -->
                <div class="grid grid-cols-3 gap-1.5 pt-1 border-t border-white/15">
                    <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-2 rounded-xl flex flex-col items-center gap-1.5">
                        <div class="w-8 h-2.5 rounded bg-white/15 kb-weather-shimmer"></div>
                        <div class="w-5 h-5 rounded-full bg-white/20 kb-weather-shimmer my-0.5"></div>
                        <div class="w-10 h-3 rounded bg-white/15 kb-weather-shimmer"></div>
                    </div>
                    <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-2 rounded-xl flex flex-col items-center gap-1.5">
                        <div class="w-8 h-2.5 rounded bg-white/15 kb-weather-shimmer"></div>
                        <div class="w-5 h-5 rounded-full bg-white/20 kb-weather-shimmer my-0.5"></div>
                        <div class="w-10 h-3 rounded bg-white/15 kb-weather-shimmer"></div>
                    </div>
                    <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-2 rounded-xl flex flex-col items-center gap-1.5">
                        <div class="w-8 h-2.5 rounded bg-white/15 kb-weather-shimmer"></div>
                        <div class="w-5 h-5 rounded-full bg-white/20 kb-weather-shimmer my-0.5"></div>
                        <div class="w-10 h-3 rounded bg-white/15 kb-weather-shimmer"></div>
                    </div>
                </div>

                <!-- Skeleton View Full Forecast Link -->
                <div class="flex justify-center pt-1">
                    <div class="w-44 h-3 rounded-md bg-white/15 kb-weather-shimmer"></div>
                </div>
            </div>

            <!-- ==================== 2. LIVE LOADED WEATHER CONTENT ==================== -->
            <div x-show="!isLoading" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-3.5 z-10 relative flex flex-col gap-3.5">

                <!-- Header: Location & Live Status -->
                <div class="flex items-center justify-between border-b border-white/15 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="text-xl sm:text-2xl filter drop-shadow">🌤️</span>
                        <div>
                            <h3 class="font-extrabold text-sm sm:text-base text-white tracking-wide {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Karnataka') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಕರ್ನಾಟಕ') }}
                            </h3>
                        </div>
                    </div>
                    <span class="bg-black/30 backdrop-blur-sm text-emerald-200 text-[10px] font-black px-2 py-0.5 rounded-full border border-white/10">
                        LIVE
                    </span>
                </div>

                <!-- Main Metric: Large Temperature & Condition -->
                <div class="flex items-baseline justify-between my-0.5">
                    <div class="flex items-start">
                        <span class="text-4xl sm:text-5xl font-black tracking-tight text-white leading-none">
                            {{ round($todayWeather->current_temperature ?? $todayWeather->temp_max ?? 28) }}
                        </span>
                        <span class="text-xl sm:text-2xl font-black text-white/90 ml-0.5">°C</span>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-xs sm:text-sm text-emerald-100 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $todayWeather 
                                ? ($activeLocale === 'en' 
                                    ? ($todayWeather->weather_condition_en ?? 'Partly Cloudy') 
                                    : ($todayWeather->weather_condition_kn ?? 'ಭಾಗಶಃ ಮೋಡ')) 
                                : ($activeLocale === 'en' ? 'Partly Cloudy' : 'ಭಾಗಶಃ ಮೋಡ') }}
                        </div>
                        <div class="text-[10px] sm:text-[11px] text-emerald-200/90 mt-0.5 font-medium">
                            {{ $activeLocale === 'en' ? 'Max' : 'ಗರಿಷ್ಠ' }} {{ round($todayWeather->temp_max ?? 31) }}°C · {{ $activeLocale === 'en' ? 'Min' : 'ಕನಿಷ್ಠ' }} {{ round($todayWeather->temp_min ?? 22) }}°C
                        </div>
                    </div>
                </div>

                <!-- Glassmorphic Rain Probability Card (Exact match to screenshot) -->
                <div class="bg-white/[0.08] backdrop-blur-md border border-white/15 rounded-2xl p-3 flex items-center gap-3 shadow-inner">
                    <div class="text-2xl sm:text-3xl shrink-0 filter drop-shadow">
                        🌧️
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] text-emerald-100/90 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Chance of rain today' : 'ಇಂದು ಮಳೆ ಸಾಧ್ಯತೆ' }}
                        </div>
                        <div class="text-lg sm:text-2xl font-black text-white leading-tight flex items-baseline gap-1.5">
                            @php
                                $precipProb = (int) round($todayWeather->precipitation_probability ?? 0);
                            @endphp
                            <span>{{ $precipProb }}%</span>
                            <span class="text-[10px] sm:text-xs text-emerald-200 font-medium">
                                ({{ $precipProb > 50 ? ($activeLocale === 'en' ? 'Rain Likely' : 'ಮಳೆ ಸಂಭವ') : ($activeLocale === 'en' ? 'Dry / Fair' : 'ಒಣ ಹವೆ') }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Agricultural Spray & Field Advisory -->
                <div class="pt-2 border-t border-white/15 flex flex-col gap-1">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-amber-300 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="shrink-0">🌾</span>
                        <span>{{ $activeLocale === 'en' ? 'Farm Advisory' : 'ಕೃಷಿ ಸಲಹೆ' }}</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-white/95 font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} leading-relaxed">
                        {{ $todayWeather 
                            ? ($activeLocale === 'en' 
                                ? ($todayWeather->farming_advisory_en ?? 'Good day to dry and move produce; suitable for field spraying.') 
                                : ($todayWeather->farming_advisory_kn ?? 'ಒಣ ಹವೆ: ಕೀಟನಾಶಕ ಸಿಂಪಡಣೆ, ಅಡಿಕೆ ಕೊಯ್ಲು ಹಾಗೂ ಅಂಗಳದಲ್ಲಿ ಕಾಳುಮೆಣಸು ಒಣಗಿಸಲು ಸೂಕ್ತ.'))
                            : ($activeLocale === 'en' 
                                ? 'Good day to dry and move produce; suitable for field spraying.' 
                                : 'ಒಣ ಹವೆ: ಕೀಟನಾಶಕ ಸಿಂಪಡಣೆ, ಅಡಿಕೆ ಕೊಯ್ಲು ಹಾಗೂ ಅಂಗಳದಲ್ಲಿ ಕಾಳುಮೆಣಸು ಒಣಗಿಸಲು ಸೂಕ್ತ.') }}
                    </p>
                </div>

                <!-- 3-Day Micro Outlook -->
                <div class="grid grid-cols-3 gap-1.5 pt-1 text-center text-xs border-t border-white/15">
                    <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-1.5 rounded-xl">
                        <span class="block text-[10px] text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Today' : 'ಇಂದು' }}</span>
                        <span class="block text-sm my-0.5">🌤️</span>
                        <span class="font-bold text-[11px] text-white">{{ round($todayWeather->temp_max ?? 28) }}°C</span>
                    </div>
                    <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-1.5 rounded-xl">
                        <span class="block text-[10px] text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Tomorrow' : 'ನಾಳೆ' }}</span>
                        <span class="block text-sm my-0.5">⛅</span>
                        <span class="font-bold text-[11px] text-white">{{ round(($todayWeather->temp_max ?? 28) - 1) }}°C</span>
                    </div>
                    <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-1.5 rounded-xl">
                        <span class="block text-[10px] text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Day 3' : '3ನೇ ದಿನ' }}</span>
                        <span class="block text-sm my-0.5">🌧️</span>
                        <span class="font-bold text-[11px] text-white">{{ round(($todayWeather->temp_max ?? 28) - 2) }}°C</span>
                    </div>
                </div>

                <!-- View Full Forecast Link -->
                <a href="{{ route('farmer.weather.index') }}" 
                   class="text-center text-xs font-bold text-emerald-200 hover:text-white pt-1 z-10 relative {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'View 7-Day District Forecast ›' : '7 ದಿನಗಳ ಸಂಪೂರ್ಣ ಹವಾಮಾನ ವರದಿ ನೋಡಿ ›' }}
                </a>

            </div>

        </aside>

    </div>

    {{-- ==================== 3. KARNATAKA IMD WEATHER RADAR SUMMARY STRIP (HIDDEN UNTIL OFFICIAL IMD API IS CONFIGURED) ====================
    <section class="bg-white rounded-2xl p-3.5 sm:p-4 border-2 border-[#E2DAC8] shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#EAF4EC] border border-[#B8DEC0] flex items-center justify-center text-lg text-[#1C5A2C] shrink-0">
                🗺️
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xs sm:text-sm font-black text-[#1C5A2C] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Karnataka Regional Weather Alert (IMD Summary)' : 'ಕರ್ನಾಟಕ ಹವಾಮಾನ ಮತ್ತು ಮಳೆ ಎಚ್ಚರಿಕೆ (IMD Alert Summary)' }}
                    </h3>
                    <span class="bg-[#FAF8F5] text-stone-600 border border-[#D9CEB8] text-[10px] font-bold px-2 py-0.5 rounded">
                        {{ $stats['latest_date_formatted'] ?? 'Today' }}
                    </span>
                </div>
                <p class="text-xs text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
                    {{ $activeLocale === 'en' ? 'Coastal & Malnad: Light to moderate rain • South Interior: Dry harvest conditions' : 'ಕರಾವಳಿ & ಮಲೆನಾಡು: ಹಗುರ ಮಳೆ • ದಕ್ಷಿಣ ಒಳನಾಡು: ಒಣ ಹವೆ, ಸುಗ್ಗಿ ಕೆಲಸಗಳಿಗೆ ಅನುಕೂಲಕರ' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap text-[11px] font-bold">
            <span class="inline-flex items-center justify-center gap-1.5 bg-yellow-50 text-yellow-800 border border-yellow-200 px-2.5 py-1.5 rounded-lg {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="inline-flex items-center text-xs leading-none shrink-0">🟡</span>
                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Malnad: Moderate Rain' : 'ಮಲೆನಾಡು: ಸಾಧಾರಣ ಮಳೆ' }}</span>
            </span>
            <span class="inline-flex items-center justify-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-1.5 rounded-lg {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="inline-flex items-center text-xs leading-none shrink-0">🟢</span>
                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'South Interior: Fair Weather' : 'ದಕ್ಷಿಣ ಒಳನಾಡು: ಅನುಕೂಲಕರ' }}</span>
            </span>
        </div>
    </section>
    --}}

    <!-- ==================== 4. ALL CROPS DIRECTORY (ALL MANDIS) ==================== -->
    <section id="allCropsSection" class="p-2 sm:p-6 bg-[#FAF8F5] rounded-2xl sm:rounded-3xl border-2 border-[#E5DECE] shadow-sm space-y-3 sm:space-y-4 w-full max-w-full min-w-0 overflow-hidden">
        
        <!-- Header with Dual View Toggle (Cards vs List) & Search Input -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg sm:text-xl font-black text-[#1C5A2C] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-2">
                        <span>🌾</span>
                        <span>{{ $activeLocale === 'en' ? "Today's Market Rates" : 'ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು' }}</span>
                    </h2>
                    <span class="bg-[#EAF4EC] text-[#1C5A2C] border border-[#B8DEC0] text-[11px] font-extrabold px-2.5 py-0.5 rounded-full">
                        {{ $distinctCropPrices->count() }} {{ $activeLocale === 'en' ? 'Crops Tracked' : 'ಬೆಳೆಗಳು ಲಭ್ಯ (ಮಂಡಿಗಳು)' }}
                    </span>
                </div>
                <p class="text-xs text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
                    {{ $activeLocale === 'en' ? 'Official Karnataka mandi modal and average prices by commodity' : 'ಕರ್ನಾಟಕದ ಪ್ರಮುಖ ಮಂಡಿ ಮಾರುಕಟ್ಟೆಗಳ ಇಂದಿನ ಸರಾಸರಿ ಮತ್ತು ಮಾದರಿ ಧಾರಣೆ' }}
                </p>
            </div>

            <!-- Controls: View Switcher (Grid vs List) + Search Bar -->
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                
                <!-- View Toggle Buttons (Cards vs List) -->
                <div class="bg-white border-2 border-[#D9CEB8] rounded-xl p-1 flex items-center shadow-sm">
                    <button type="button" 
                            @click="currentView = 'grid'" 
                            :class="currentView === 'grid' ? 'bg-[#1C5A2C] text-white shadow-sm' : 'text-stone-600 hover:text-stone-900'"
                            class="px-3 py-1 rounded-lg text-xs font-black transition-all cursor-pointer flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor">
                            <circle cx="3" cy="4.5" r="1.5"/>
                            <circle cx="8" cy="4.5" r="1.5"/>
                            <circle cx="13" cy="4.5" r="1.5"/>
                            <circle cx="3" cy="11.5" r="1.5"/>
                            <circle cx="8" cy="11.5" r="1.5"/>
                            <circle cx="13" cy="11.5" r="1.5"/>
                        </svg>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Cards' : 'ಕಾರ್ಡ್' }}</span>
                    </button>
                    <button type="button" 
                            @click="currentView = 'list'" 
                            :class="currentView === 'list' ? 'bg-[#1C5A2C] text-white shadow-sm' : 'text-stone-600 hover:text-stone-900'"
                            class="px-3 py-1 rounded-lg text-xs font-black transition-all cursor-pointer flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor">
                            <circle cx="2.5" cy="3.5" r="1.2"/>
                            <rect x="5.5" y="2.5" width="9" height="2" rx="1"/>
                            <circle cx="2.5" cy="8" r="1.2"/>
                            <rect x="5.5" y="7" width="9" height="2" rx="1"/>
                            <circle cx="2.5" cy="12.5" r="1.2"/>
                            <rect x="5.5" y="11.5" width="9" height="2" rx="1"/>
                        </svg>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'List' : 'ಪಟ್ಟಿ' }}</span>
                    </button>
                </div>

                <!-- Live Client-side & Voice-Ready Search Input with Smooth Focus & Transitions -->
                <div class="relative flex-1 sm:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 text-xs transition-colors duration-200"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           @input.debounce.150ms="filterCrops()"
                           placeholder="{{ $activeLocale === 'en' ? 'Search crop or mandi...' : 'ಬೆಳೆ ಅಥವಾ ಮಾರುಕಟ್ಟೆ ಹುಡುಕಿ...' }}" 
                           class="w-full pl-9 pr-8 py-2 bg-white border-2 border-[#D9CEB8] focus:border-[#1C5A2C] focus:ring-2 focus:ring-[#1C5A2C]/20 focus:bg-emerald-50/10 rounded-xl text-xs font-semibold text-stone-800 outline-none transition-all duration-200 {{ $activeLocale === 'kn' ? 'font-kannada search-input-kn' : 'font-sans' }} shadow-sm">
                    <button type="button" 
                            x-show="searchQuery.length > 0" 
                            x-transition:enter="transition ease-out duration-150 transform"
                            x-transition:enter-start="opacity-0 scale-75"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100 transform"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-75"
                            @click="searchQuery = ''; filterCrops()" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 text-xs cursor-pointer p-0.5" style="display: none;">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- Category Filter Pills (Matches Negilu Krushi Clean Categories) -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2.5 text-xs font-bold text-stone-700 w-full min-w-0 max-w-full" style="contain: paint;">
            <button type="button" 
                    @click="setCategory('all')" 
                    :class="selectedCat === 'all' ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 border-[#D9CEB8] hover:border-[#1C5A2C]'"
                    class="category-filter-btn flex-none px-4 py-2 rounded-xl border-2 transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="shrink-0">🌾</span>
                <span>{{ $activeLocale === 'en' ? 'All' : 'ಎಲ್ಲಾ' }}</span>
            </button>
            
            @foreach($categories as $cat)
                <button type="button" 
                        @click="setCategory('{{ $cat->slug }}')" 
                        :class="selectedCat === '{{ $cat->slug }}' ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 border-[#D9CEB8] hover:border-[#1C5A2C]'"
                        class="category-filter-btn flex-none px-4 py-2 rounded-xl border-2 transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    @php
                        $catEmoji = match(strtolower($cat->slug)) {
                            'plantation', 'commercial' => '🌴',
                            'spice', 'spices' => '🌶️',
                            'vegetable', 'vegetables' => '🥕',
                            'grain', 'grains', 'pulses' => '🌾',
                            'oilseed', 'oilseeds' => '🌻',
                            'fruit', 'fruits' => '🍌',
                            default => '🌿'
                        };
                    @endphp
                    <span class="shrink-0">{{ $catEmoji }}</span>
                    <span>{{ $activeLocale === 'en' ? $cat->name : ($cat->name_kn ?? $cat->name) }}</span>
                    <span class="sr-only">{{ $cat->name }}</span>
                </button>
            @endforeach
        </div>

        <!-- Quick Hint Nudge -->
        <div class="flex items-start sm:items-center gap-2 bg-[#EAF4EC] border border-[#B8DEC0] px-3 py-1.5 rounded-xl sm:rounded-full text-[11px] sm:text-xs text-[#1C5A2C] font-semibold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} w-full max-w-full">
            <span class="shrink-0 mt-0.5 sm:mt-0">👆</span> 
            <span class="leading-snug">{{ $activeLocale === 'en' ? 'Tap any crop to view prices across different markets and seasonal trends' : 'ಯಾವುದೇ ಬೆಳೆಯ ಮೇಲೆ ಕ್ಲಿಕ್ ಮಾಡಿ — ವಿವಿಧ ಮಾರುಕಟ್ಟೆಗಳ ದರ ಮತ್ತು ಸೀಸನಲ್ ಮುನ್ಸೂಚನೆ ನೋಡಿ' }}</span>
        </div>

        <!-- ==================== VIEW 1: CLEAN FULL-BLEED CARDS GRID (Negilu Krushi Clean Master) ==================== -->
        <div id="cropsGrid" x-show="currentView === 'grid'" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3.5 lg:gap-4">
            @forelse($distinctCropPrices as $price)
                <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($price->market->name) }}"
                   data-cat="{{ $price->crop->category->slug ?? 'other' }}" 
                   data-name="{{ strtolower($price->crop->name . ' ' . ($price->crop->name_kn ?? '') . ' ' . $price->market->name . ' ' . ($price->market->name_kn ?? '') . ' ' . ($price->market->district->name ?? '') . ' ' . ($price->market->district->name_kn ?? '') . ' ' . ($price->variety->name ?? '') . ' ' . ($price->variety->name_kn ?? '')) }}"
                   class="crop-article bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] overflow-hidden shadow-xs hover:shadow-md flex flex-col justify-between group cursor-pointer block tap-feedback active:scale-[0.98]"
                   style="transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.2, 0, 0, 1), border-color 0.2s, box-shadow 0.2s;">
                    
                    <!-- Clean Photo (No clutter badges, crop name on bottom gradient) -->
                    <div class="relative h-24 sm:h-32 overflow-hidden bg-stone-100">
                        <img src="{{ $price->crop->photo_url }}" 
                             alt="{{ $price->crop->name }}" 
                             class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                        
                        @if($price->reliability_badge === 'Reliable')
                            <div class="absolute top-1.5 right-1.5">
                                <span class="bg-emerald-600/95 text-white text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse shrink-0"></span>
                                    <span class="leading-none">{{ $activeLocale === 'en' ? 'Reliable' : 'ವಿಶ್ವಾಸಾರ್ಹ' }}</span>
                                </span>
                            </div>
                        @else
                            <div class="absolute top-1.5 right-1.5">
                                <span class="bg-amber-500/90 text-stone-950 text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm inline-flex items-center gap-1" title="{{ $activeLocale === 'en' ? 'State benchmark rate' : 'ರಾಜ್ಯ ಸರಾಸರಿ / ಸಮೀಪದ ಮಂಡಿ ದರ' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-800 shrink-0"></span>
                                    <span class="leading-none">{{ $activeLocale === 'en' ? 'Benchmark' : 'ಮೌಲ್ಯಾಂಕನ' }}</span>
                                </span>
                            </div>
                        @endif

                        <!-- Crop Name on Photo Bottom -->
                        <div class="absolute bottom-1.5 left-2 right-2 text-white">
                            <h3 class="text-xs sm:text-base font-black {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} tracking-wide leading-tight drop-shadow-md break-words">
                                {{ $activeLocale === 'en' ? $price->crop->name : ($price->crop->name_kn ?? $price->crop->name) }}
                            </h3>
                        </div>
                    </div>

                    <!-- Clean Body (Price, Variety, Market & Subtle arrow - zero congestion) -->
                    <div class="p-2 sm:p-3 flex flex-col justify-between flex-1 gap-1">
                        <div>
                            <!-- Price & Trend -->
                            <div class="flex items-baseline justify-between gap-1">
                                <div class="text-sm sm:text-lg font-black text-[#1C5A2C] leading-none">
                                    ₹{{ number_format($price->modal_price) }}
                                    <span class="text-[9px] sm:text-xs font-semibold text-stone-500">/ {{ $price->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್') }}</span>
                                </div>
                                @if(($price->daily_price_change ?? 0) > 0)
                                    <span class="text-[9px] sm:text-[10px] font-extrabold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded shrink-0 flex items-center gap-0.5">
                                        <span class="shrink-0 text-[9px]">↑</span>
                                        <span>{{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}</span>
                                    </span>
                                @elseif(($price->daily_price_change ?? 0) < 0)
                                    <span class="text-[9px] sm:text-[10px] font-extrabold text-red-700 bg-red-50 px-1.5 py-0.5 rounded shrink-0 flex items-center gap-0.5">
                                        <span class="shrink-0 text-[9px]">↓</span>
                                        <span>{{ $activeLocale === 'en' ? 'Drop' : 'ಇಳಿಕೆ' }}</span>
                                    </span>
                                @else
                                    <span class="text-[9px] sm:text-[10px] font-extrabold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded shrink-0 flex items-center gap-0.5">
                                        <span class="shrink-0 text-[9px]">→</span>
                                        <span>{{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}</span>
                                    </span>
                                @endif
                            </div>

                            <!-- Variety -->
                            <div class="text-[10px] sm:text-[11px] font-bold text-stone-600 mt-1 leading-tight break-words">
                                {{ $activeLocale === 'kn' ? ($price->variety->name_kn ?? $price->variety->name ?? 'ಸಾಮಾನ್ಯ') : ($price->variety->name ?? 'Common') }}
                            </div>
                        </div>

                        <!-- Market Line & Tap Arrow -->
                        <div class="flex items-center justify-between text-[10px] sm:text-[11px] text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} pt-1.5 border-t border-stone-100 gap-1 mt-1">
                            <span class="flex items-center gap-1 text-stone-700 font-semibold truncate">
                                <span class="text-[9px] text-stone-400 shrink-0">📍</span>
                                <span class="truncate">
                                    {{ $activeLocale === 'kn' ? ($price->market->name_kn ?? $price->market->name) : $price->market->name }}
                                    @if(!empty($price->market->district))
                                        · {{ $activeLocale === 'kn' ? ($price->market->district->name_kn ?? $price->market->district->name) : $price->market->district->name }}
                                    @endif
                                </span>
                            </span>
                            <span class="text-[10px] sm:text-xs text-[#1C5A2C] font-extrabold shrink-0 group-hover:translate-x-0.5 transition-transform">
                                ›
                            </span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12 bg-white rounded-2xl border-2 border-dashed border-[#D9CEB8]">
                    <span class="text-3xl">🔍</span>
                    <h4 class="text-sm font-black text-stone-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-2">
                        {{ $activeLocale === 'en' ? 'No crops found matching this criteria' : 'ಹುಡುಕಾಟಕ್ಕೆ ತಕ್ಕ ಬೆಳೆ ಸಿಗಲಿಲ್ಲ' }}
                    </h4>
                    <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
                        {{ $activeLocale === 'en' ? 'Try searching for a different commodity or reset filters' : 'ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ ಅಥವಾ ವರ್ಗವನ್ನು ಆಯ್ಕೆಮಾಡಿ.' }}
                    </p>
                </div>
            @endforelse
        </div>

        <!-- ==================== VIEW 2: STREAMLINED RESPONSIVE LIST VIEW ==================== -->
        <div id="cropsList" x-show="currentView === 'list'" class="space-y-2.5" style="display: none;">
            @forelse($distinctCropPrices as $price)
                <div data-cat="{{ $price->crop->category->slug ?? 'other' }}"
                     data-name="{{ strtolower($price->crop->name . ' ' . ($price->crop->name_kn ?? '') . ' ' . $price->market->name . ' ' . ($price->market->name_kn ?? '') . ' ' . ($price->market->district->name ?? '') . ' ' . ($price->market->district->name_kn ?? '') . ' ' . ($price->variety->name ?? '') . ' ' . ($price->variety->name_kn ?? '')) }}"
                     class="crop-list-item bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] p-3 sm:p-4 flex items-center justify-between gap-3 cursor-pointer shadow-sm hover:bg-emerald-50/30 tap-feedback active:scale-[0.985]"
                     style="transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.2, 0, 0, 1), border-color 0.2s, background-color 0.2s;">
                    
                    <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($mover->market->name ?? $price->market->name) }}" 
                       class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl overflow-hidden bg-stone-100 shrink-0 border border-[#D9CEB8]">
                            <img src="{{ $price->crop->photo_url }}" 
                                 alt="{{ $price->crop->name }}" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="font-extrabold text-sm sm:text-base text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} truncate">
                                    {{ $activeLocale === 'en' ? $price->crop->name : ($price->crop->name_kn ?? $price->crop->name) }}
                                </h4>
                                <span class="hidden sm:inline-block bg-[#EAF4EC] text-[#1C5A2C] text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                    {{ $activeLocale === 'en' ? ($price->crop->category->name ?? 'Crop') : ($price->crop->category->name_kn ?? $price->crop->category->name ?? 'ಬೆಳೆ') }}
                                </span>
                            </div>
                            <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} truncate">
                                📍 {{ $activeLocale === 'kn' ? ($price->market->name_kn ?? $price->market->name) : $price->market->name }}
                                · {{ $activeLocale === 'kn' ? ($price->variety->name_kn ?? $price->variety->name ?? 'ಸಾಮಾನ್ಯ') : ($price->variety->name ?? 'Common') }}
                                @if(!empty($price->market->district))
                                    · {{ $activeLocale === 'kn' ? ($price->market->district->name_kn ?? $price->market->district->name) : $price->market->district->name }}
                                @endif
                            </p>
                        </div>
                    </a>

                    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                        <div class="text-right">
                            <div class="text-base sm:text-lg font-black text-[#1C5A2C]">
                                ₹{{ number_format($price->modal_price) }} 
                                <span class="text-xs font-normal text-stone-500">/ {{ $price->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್') }}</span>
                            </div>
                            @if(($price->daily_price_change ?? 0) > 0)
                                <span class="text-[11px] font-extrabold text-emerald-700">↑ {{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}</span>
                            @elseif(($price->daily_price_change ?? 0) < 0)
                                <span class="text-[11px] font-extrabold text-red-600">↓ {{ $activeLocale === 'en' ? 'Drop' : 'ಇಳಿಕೆ' }}</span>
                            @else
                                <span class="text-[11px] font-extrabold text-blue-600">→ {{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}</span>
                            @endif
                        </div>
                        <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($price->market->name) }}"
                           class="w-8 h-8 rounded-full bg-[#FAF8F5] border border-[#D9CEB8] flex items-center justify-center text-stone-400 hover:text-[#1C5A2C] hover:border-[#1C5A2C] transition-all">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
            @endforelse
        </div>

        <!-- Empty Search Fallback (Client Side) with Smooth Fade In -->
        <div id="clientNoResults" class="hidden text-center py-10 bg-white rounded-2xl border-2 border-dashed border-[#D9CEB8] transition-all duration-200">
            <span class="text-3xl">🔍</span>
            <h4 class="text-sm font-black text-stone-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-2">
                {{ $activeLocale === 'en' ? 'No crops found matching your search' : 'ಹುಡುಕಾಟಕ್ಕೆ ತಕ್ಕ ಬೆಳೆ ಸಿಗಲಿಲ್ಲ' }}
            </h4>
            <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
                {{ $activeLocale === 'en' ? 'Try searching by a different name' : 'ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ ಅಥವಾ ಮಾರುಕಟ್ಟೆ ಹೆಸರನ್ನು ಟೈಪ್ ಮಾಡಿ.' }}
            </p>
        </div>

        <!-- Pagination Links if available -->
        @if(method_exists($latestPrices, 'hasPages') && $latestPrices->hasPages())
            <div class="pt-4 border-t border-[#E5DECE]">
                {{ $latestPrices->links() }}
            </div>
        @endif

    </section>

</div>

<script>
function farmerHome() {
    return {
        currentView: 'grid',
        selectedCat: '{{ $selectedCategory ?? 'all' }}',
        searchQuery: '{{ addslashes($search ?? '') }}',

        setCategory(slug) {
            this.selectedCat = slug;
            this.filterCrops();
        },

        filterCrops() {
            const query = this.searchQuery.toLowerCase().trim();
            const cat = this.selectedCat;
            let visibleCount = 0;

            const transitionItem = (el, show) => {
                if (el._hideTimer) {
                    clearTimeout(el._hideTimer);
                    el._hideTimer = null;
                }

                if (show) {
                    const isHidden = el.style.display === 'none' || getComputedStyle(el).display === 'none';
                    if (isHidden) {
                        el.style.opacity = '0';
                        el.style.transform = 'scale(0.96) translateY(6px)';
                        el.style.display = 'flex';
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                el.style.opacity = '1';
                                el.style.transform = 'scale(1) translateY(0)';
                            });
                        });
                    } else {
                        el.style.opacity = '1';
                        el.style.transform = 'scale(1) translateY(0)';
                    }
                } else {
                    const isVisible = el.style.display !== 'none' && getComputedStyle(el).display !== 'none';
                    if (isVisible) {
                        el.style.opacity = '0';
                        el.style.transform = 'scale(0.96) translateY(6px)';
                        el._hideTimer = setTimeout(() => {
                            el.style.display = 'none';
                            el._hideTimer = null;
                        }, 220);
                    }
                }
            };

            // Filter Grid Cards
            const gridCards = document.querySelectorAll('#cropsGrid .crop-article');
            gridCards.forEach(card => {
                const cardCat = card.getAttribute('data-cat');
                const cardName = card.getAttribute('data-name');
                const matchCat = (cat === 'all' || cardCat === cat);
                const matchSearch = (!query || cardName.includes(query));
                const shouldShow = matchCat && matchSearch;

                transitionItem(card, shouldShow);
                if (shouldShow) visibleCount++;
            });

            // Filter List Items
            const listItems = document.querySelectorAll('#cropsList .crop-list-item');
            listItems.forEach(item => {
                const itemCat = item.getAttribute('data-cat');
                const itemName = item.getAttribute('data-name');
                const matchCat = (cat === 'all' || itemCat === cat);
                const matchSearch = (!query || itemName.includes(query));
                const shouldShow = matchCat && matchSearch;

                transitionItem(item, shouldShow);
                if (shouldShow) visibleCount++;
            });

            const noRes = document.getElementById('clientNoResults');
            if (noRes) {
                if (visibleCount === 0 && (gridCards.length > 0 || listItems.length > 0)) {
                    noRes.classList.remove('hidden');
                    noRes.style.opacity = '0';
                    noRes.style.transform = 'translateY(6px)';
                    requestAnimationFrame(() => {
                        noRes.style.transition = 'opacity 0.24s cubic-bezier(0.4, 0, 0.2, 1), transform 0.24s cubic-bezier(0.4, 0, 0.2, 1)';
                        noRes.style.opacity = '1';
                        noRes.style.transform = 'translateY(0)';
                    });
                } else {
                    noRes.style.opacity = '0';
                    setTimeout(() => {
                        if (visibleCount > 0) {
                            noRes.classList.add('hidden');
                        }
                    }, 200);
                }
            }
        }
    };
}
</script>
@endsection
