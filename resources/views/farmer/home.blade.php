@extends('layouts.farmer')

@section('title', 'ಕೃಷಿ ಬಾಂಧವ — ಕರ್ನಾಟಕ ರೈತರ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಮತ್ತು ಮುನ್ಸೂಚನೆ')

@section('content')
@php
    $activeLocale = app()->getLocale();
    $searchableCropsJson = json_encode(($sortedPrices ?? $distinctCropPrices)->map(function ($p) use ($activeLocale) {
        return [
            'id' => $p->crop_id,
            'name' => $p->crop->name ?? '',
            'name_kn' => $p->crop->name_kn ?? $p->crop->name ?? '',
            'market' => $p->market->name ?? '',
            'district' => $p->market->district->name ?? '',
            'modal_price' => number_format($p->modal_price ?? 0),
            'unit' => $p->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್'),
            'photo' => $p->crop->photo_url ?? '',
            'url' => route('farmer.crop.detail', $p->crop_id) . '?market=' . urlencode($p->market->name ?? ''),
            'trend' => ($p->price_spread ?? 0) > 0 ? '↑' : (($p->price_spread ?? 0) < 0 ? '↓' : '→'),
            'trend_class' => ($p->price_spread ?? 0) > 0 ? 'text-emerald-700 bg-emerald-50' : (($p->price_spread ?? 0) < 0 ? 'text-red-700 bg-red-50' : 'text-stone-600 bg-stone-100'),
        ];
    })->values());
@endphp

<div class="space-y-6" x-data="farmerHome()">

    <!-- ==================== 1. HERO BANNER (Negilu Krushi Clean Master Standard) ==================== -->
    <section class="rounded-3xl relative shadow-lg border-2 border-[#D9CEB8] min-h-[300px] sm:min-h-[340px] flex flex-col justify-between z-30"
             style="background: linear-gradient(147deg, rgba(16, 54, 28, 0.94) 0%, rgb(12 42 22 / 65%) 50%, rgba(6, 22, 11, 0.88) 100%), url('{{ asset('images/hero_farmer.jpg') }}') center right / cover no-repeat;">
        
        <!-- Hero Content -->
        <div class="p-4 sm:p-8 z-10 max-w-3xl space-y-2.5 sm:space-y-3">
            
            <!-- Top Eyebrow Row: Live Status + P1: Hero Top Guide Pill -->
            <div class="flex items-center justify-between flex-wrap gap-2">
                <!-- Live Eyebrow -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-black/40 backdrop-blur-md text-xs font-bold text-emerald-200 border border-emerald-500/40 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>{{ $activeLocale === 'en' ? 'Live APMC Market Rates' : 'ನೇರ ಮಾರುಕಟ್ಟೆ ದತ್ತಾಂಶ' }} • {{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Karnataka') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಕರ್ನಾಟಕ') }}</span>
                </div>

                <!-- P1: Hero Top Pill - How to Use (ಹೇಗೆ ಬಳಸುವುದು) -->
                <a href="{{ route('farmer.articles.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/20 hover:bg-amber-400/30 text-amber-300 text-xs font-black border border-amber-400/40 backdrop-blur-md shadow-sm transition hover:scale-105 active:scale-95 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>💡</span>
                    <span>{{ $activeLocale === 'en' ? 'How to Use? ›' : 'ಹೇಗೆ ಬಳಸುವುದು? ›' }}</span>
                </a>
            </div>

            <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' 
                    ? \App\Models\SystemSetting::get('hero_headline_en', "Let every drop of sweat earn its true reward; let market strength be in the farmer's hands")
                    : \App\Models\SystemSetting::get('hero_headline_kn', 'ಬೆವರ ಹನಿಗೆ ಸಿಗಲಿ ತಕ್ಕ ಪ್ರತಿಫಲ, ರೈತನ ಕೈಲಿರಲಿ ಮಾರುಕಟ್ಟೆಯ ಬಲ') }}
            </h1>

            <p class="text-xs sm:text-sm text-emerald-100 font-medium max-w-xl {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} leading-relaxed">
                {{ $activeLocale === 'en' 
                    ? \App\Models\SystemSetting::get('hero_subtitle_en', 'Live prices and future trends from all Karnataka APMC mandis.')
                    : \App\Models\SystemSetting::get('hero_subtitle_kn', 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳ ಇಂದಿನ ನೇರ ದರ ಮತ್ತು ದರ ಮುನ್ಸೂಚನೆ.') }}
            </p>
        </div>

        <!-- Mobile-First Classic Integrated Command Dock (With Live Debounced Amazon Search) -->
        <div class="p-3 sm:p-6 relative z-30">
            <div class="bg-[#FAF8F5] rounded-2xl sm:rounded-3xl border-2 border-[#D9CEB8] shadow-2xl p-2.5 sm:p-3 md:py-2.5 md:px-4 relative flex flex-col md:flex-row md:items-center gap-2.5 md:gap-4"
                 x-data="{
                     searchQuery: '',
                     isSearchOpen: false,
                     results: [],
                     allCrops: {{ $searchableCropsJson }},
                     
                     performSearch() {
                         const q = this.searchQuery.toLowerCase().trim();
                         if (!q) {
                             this.results = this.allCrops.slice(0, 6);
                             this.isSearchOpen = true;
                             return;
                         }
                         this.results = this.allCrops.filter(c => 
                             (c.name && c.name.toLowerCase().includes(q)) || 
                             (c.name_kn && c.name_kn.toLowerCase().includes(q)) ||
                             (c.market && c.market.toLowerCase().includes(q)) ||
                             (c.district && c.district.toLowerCase().includes(q))
                         ).slice(0, 8);
                         this.isSearchOpen = true;
                     },
                     clearSearch() {
                         this.searchQuery = '';
                         this.results = this.allCrops.slice(0, 6);
                         this.isSearchOpen = false;
                     }
                 }"
                 @click.away="isSearchOpen = false">
                
                <!-- Mandi Hub (Row 1 on Mobile, Left Column on Desktop) -->
                <div class="flex items-center justify-between md:justify-start gap-2 sm:gap-3 md:shrink-0">
                    <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center text-sm sm:text-base font-black shrink-0 border border-emerald-200">
                            📍
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] sm:text-[10px] text-stone-500 font-bold uppercase tracking-wider block leading-none">
                                {{ $activeLocale === 'en' ? 'Your Mandi Center' : 'ನಿಮ್ಮ ಮಂಡಿ ಕೇಂದ್ರ' }}
                            </span>
                            <span class="font-black text-xs sm:text-sm text-stone-900 block truncate leading-tight mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Karnataka') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಕರ್ನಾಟಕ') }} (APMC)
                            </span>
                        </div>
                    </div>

                    <button type="button"
                            @click="$dispatch('open-location-modal')"
                            class="bg-[#EAF4EC] hover:bg-[#1C5A2C] text-[#1C5A2C] hover:text-white px-2.5 py-1.5 md:px-3 md:py-1.5 rounded-xl text-[11px] font-black border border-[#B8DEC0] transition cursor-pointer shrink-0 md:ml-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Change ▾' : 'ಬದಲಿಸಿ ▾' }}
                    </button>
                </div>

                <!-- Divider: Horizontal on mobile, Vertical on desktop -->
                <div class="border-t border-[#E5DECE] md:hidden"></div>
                <div class="hidden md:block w-px h-8 bg-[#D9CEB8] shrink-0"></div>

                <!-- Search Bar (Row 2 on Mobile, Expanded Right Column on Desktop) -->
                <div class="relative flex-1 min-w-0">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 text-xs transition-colors duration-200"></i>
                    
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.150ms="performSearch()"
                           @focus="performSearch()"
                           placeholder="{{ $activeLocale === 'en' ? 'Search any crop or mandi (e.g. Arecanut, Pepper, Tomato)...' : 'ಯಾವುದೇ ಬೆಳೆ ಅಥವಾ ಮಂಡಿ ಹುಡುಕಿ... (ಅಡಿಕೆ, ಕಾಳುಮೆಣಸು, ಟೊಮೆಟೊ)' }}"
                           class="w-full pl-9 pr-9 py-2 sm:py-2.5 rounded-xl bg-white border border-[#D9CEB8] text-xs sm:text-sm font-semibold text-stone-900 placeholder-stone-400 focus:outline-none focus:border-[#1C5A2C] focus:ring-2 focus:ring-[#1C5A2C]/20 focus:bg-emerald-50/10 shadow-inner transition-all duration-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">

                    <!-- Clear ✕ Button with smooth scale/fade transition -->
                    <button type="button"
                            x-show="searchQuery.length > 0"
                            x-transition:enter="transition ease-out duration-150 transform"
                            x-transition:enter-start="opacity-0 scale-75"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100 transform"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-75"
                            @click="clearSearch()"
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
                         class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-[0_20px_45px_-15px_rgba(28,90,44,0.15),0_10px_20px_-5px_rgba(0,0,0,0.08)] border-2 border-[#1C5A2C] overflow-hidden z-50 divide-y divide-stone-100 max-h-80 overflow-y-auto scroll-smooth">
                        
                        <!-- Header counter -->
                        <div class="px-3.5 py-1.5 bg-[#FAF8F5] text-[10px] font-bold text-stone-500 flex items-center justify-between border-b border-stone-100">
                            <span x-text="searchQuery.trim().length === 0 ? '{{ $activeLocale === 'en' ? 'Popular Karnataka Crops' : 'ಪ್ರಮುಖ ಬೆಳೆಗಳು' }}' : (results.length > 0 ? (results.length + ' {{ $activeLocale === 'en' ? 'crops found' : 'ಬೆಳೆಗಳು ಲಭ್ಯ' }}') : '{{ $activeLocale === 'en' ? 'Search Results' : 'ಫಲಿತಾಂಶ' }}')"></span>
                            <span class="text-[9px] text-[#1C5A2C] font-black uppercase">LIVE APMC</span>
                        </div>

                        <!-- Results List -->
                        <template x-for="item in results" :key="item.id">
                            <a :href="item.url" 
                               class="flex items-center justify-between p-2.5 sm:p-3 hover:bg-[#EAF4EC] hover:pl-3.5 sm:hover:pl-4 transition-all duration-200 group cursor-pointer text-left">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <img :src="item.photo" :alt="item.name" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-cover border border-stone-200 shrink-0 group-hover:scale-105 transition-transform duration-200 shadow-xs">
                                    <div class="min-w-0">
                                        <div class="font-black text-xs sm:text-sm text-stone-900 group-hover:text-[#1C5A2C] transition-colors duration-150 truncate {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                            <span x-text="item.name_kn"></span>
                                            <span class="text-[10px] text-stone-400 font-normal ml-1" x-text="'(' + item.name + ')'"></span>
                                        </div>
                                        <div class="text-[10px] text-stone-500 font-medium truncate flex items-center gap-1 mt-0.5">
                                            <span class="text-[9px] text-[#1C5A2C]">📍</span>
                                            <span x-text="item.market ? (item.market + ' APMC') : 'Karnataka APMC'"></span>
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
                        <span>🌟</span>
                        <span>{{ $activeLocale === 'en' ? "Today's Key Market Rates" : 'ಇಂದಿನ ಪ್ರಮುಖ ದರಗಳು' }}</span>
                        <span class="text-xs text-stone-500 font-semibold hidden sm:inline">({{ $activeLocale === 'en' ? ($activeDistrict->name ?? 'Karnataka') : ($activeDistrict->name_kn ?? $activeDistrict->name ?? 'ಕರ್ನಾಟಕ') }})</span>
                    </h2>
                </div>
                <a href="#allCropsSection" class="text-xs font-bold text-[#1C5A2C] hover:underline {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                    {{ $activeLocale === 'en' ? 'View All Crops ›' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳು ನೋಡಿ ›' }}
                </a>
            </div>

            <!-- Top 4 Spotlight Cards Grid (2 Columns on Mobile, 2 Columns on Tablet/Desktop) -->
            <div class="grid grid-cols-2 gap-2.5 sm:gap-3.5">
                @forelse($topMovers->take(4) as $mover)
                    <a href="{{ route('farmer.crop.detail', $mover->crop_id) }}?market={{ urlencode($mover->market->name) }}"
                       class="bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 group flex flex-col justify-between">
                        
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
                                
                                @if(($mover->price_spread ?? 0) > 0)
                                    <span class="bg-emerald-600/90 text-white text-[9px] sm:text-[11px] font-black px-1.5 sm:px-2 py-0.5 rounded-full shrink-0 flex items-center gap-0.5 shadow-sm">
                                        ↑ {{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}
                                    </span>
                                @elseif(($mover->price_spread ?? 0) < 0)
                                    <span class="bg-red-500/90 text-white text-[9px] sm:text-[11px] font-black px-1.5 sm:px-2 py-0.5 rounded-full shrink-0 flex items-center gap-0.5 shadow-sm">
                                        ↓ {{ $activeLocale === 'en' ? 'Drop' : 'ಇಳಿಕೆ' }}
                                    </span>
                                @else
                                    <span class="bg-blue-600/90 text-white text-[9px] sm:text-[11px] font-black px-1.5 sm:px-2 py-0.5 rounded-full shrink-0 flex items-center gap-0.5 shadow-sm">
                                        → {{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}
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
                                    {{ $mover->variety->name ?? 'Common' }}
                                </div>
                            </div>
                            
                            <div class="flex items-start justify-between text-[10px] sm:text-xs text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} pt-1.5 border-t border-stone-100 gap-1">
                                <span class="flex items-start gap-1 text-stone-700 font-semibold leading-tight break-words">
                                    <span class="shrink-0 text-[10px] mt-0.5">📍</span>
                                    <span>{{ $mover->market->name }} · {{ $mover->market->district->name ?? '' }}</span>
                                </span>
                                <span class="text-[10px] sm:text-[11px] text-[#1C5A2C] font-extrabold shrink-0 mt-0.5">
                                    {{ $activeLocale === 'en' ? 'Details ›' : 'ವಿವರ ›' }}
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

        <!-- Right Column: Classic Atmospheric Topographic Weather Card (4 Cols Desktop) -->
        <aside class="lg:col-span-4 relative overflow-hidden rounded-3xl p-4 sm:p-5 border border-emerald-500/30 shadow-xl flex flex-col gap-3.5 text-white"
               style="background: radial-gradient(circle at 85% 15%, #257044 0%, #154D2B 45%, #0B2B17 100%);">
            
            <!-- Classic Topographic Concentric Contour Lines (Top-Right Atmospheric Arcs) -->
            <svg class="absolute -top-6 -right-6 w-52 h-52 sm:w-60 sm:h-60 pointer-events-none text-white select-none z-0" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="200" cy="0" r="170" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.20" />
                <circle cx="200" cy="0" r="135" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.15" />
                <circle cx="200" cy="0" r="100" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.11" />
                <circle cx="200" cy="0" r="65" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.08" />
            </svg>

            <!-- Header: Location & Live Status -->
            <div class="flex items-center justify-between z-10 relative border-b border-white/15 pb-2.5">
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
            <div class="flex items-baseline justify-between z-10 relative my-0.5">
                <div class="flex items-start">
                    <span class="text-4xl sm:text-5xl font-black tracking-tight text-white leading-none">
                        {{ $todayWeather->temperature_max ?? 28 }}
                    </span>
                    <span class="text-xl sm:text-2xl font-black text-white/90 ml-0.5">°C</span>
                </div>
                <div class="text-right">
                    <div class="font-bold text-xs sm:text-sm text-emerald-100 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $todayWeather->weather_condition_kn ?? ($activeLocale === 'en' ? 'Partly Cloudy' : 'ಭಾಗಶಃ ಮೋಡ') }}
                    </div>
                    <div class="text-[10px] sm:text-[11px] text-emerald-200/90 mt-0.5 font-medium">
                        {{ $activeLocale === 'en' ? 'Max' : 'ಗರಿಷ್ಠ' }} {{ $todayWeather->temperature_max ?? 31 }}°C · {{ $activeLocale === 'en' ? 'Min' : 'ಕನಿಷ್ಠ' }} {{ $todayWeather->temperature_min ?? 22 }}°C
                    </div>
                </div>
            </div>

            <!-- Glassmorphic Rain Probability Card (Exact match to screenshot) -->
            <div class="bg-white/[0.08] backdrop-blur-md border border-white/15 rounded-2xl p-3 flex items-center gap-3 z-10 relative shadow-inner">
                <div class="text-2xl sm:text-3xl shrink-0 filter drop-shadow">
                    🌧️
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] text-emerald-100/90 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Chance of rain today' : 'ಇಂದು ಮಳೆ ಸಾಧ್ಯತೆ' }}
                    </div>
                    <div class="text-lg sm:text-2xl font-black text-white leading-tight flex items-baseline gap-1.5">
                        <span>{{ $todayWeather->rain_chance_percent ?? 0 }}%</span>
                        <span class="text-[10px] sm:text-xs text-emerald-200 font-medium">
                            ({{ ($todayWeather->rain_chance_percent ?? 0) > 50 ? ($activeLocale === 'en' ? 'Rain Likely' : 'ಮಳೆ ಸಂಭವ') : ($activeLocale === 'en' ? 'Dry / Fair' : 'ಒಣ ಹವೆ') }})
                        </span>
                    </div>
                </div>
            </div>

            <!-- Agricultural Spray & Field Advisory -->
            <div class="pt-2 border-t border-white/15 z-10 relative flex flex-col gap-1">
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-amber-300 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>🌾</span>
                    <span>{{ $activeLocale === 'en' ? 'Farm Advisory' : 'ಕೃಷಿ ಸಲಹೆ' }}</span>
                </div>
                <p class="text-[11px] sm:text-xs text-white/95 font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} leading-relaxed">
                    {{ $todayWeather->spray_advisory_kn ?? ($activeLocale === 'en' ? 'Good day to dry and move produce; suitable for field spraying.' : 'ಒಣ ಹವೆ: ಕೀಟನಾಶಕ ಸಿಂಪಡಣೆ, ಅಡಿಕೆ ಕೊಯ್ಲು ಹಾಗೂ ಅಂಗಳದಲ್ಲಿ ಕಾಳುಮೆಣಸು ಒಣಗಿಸಲು ಸೂಕ್ತ.') }}
                </p>
            </div>

            <!-- 3-Day Micro Outlook -->
            <div class="grid grid-cols-3 gap-1.5 pt-1 text-center text-xs border-t border-white/15 z-10 relative">
                <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-1.5 rounded-xl">
                    <span class="block text-[10px] text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Today' : 'ಇಂದು' }}</span>
                    <span class="block text-sm my-0.5">🌤️</span>
                    <span class="font-bold text-[11px] text-white">{{ $todayWeather->temperature_max ?? 28 }}°C</span>
                </div>
                <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-1.5 rounded-xl">
                    <span class="block text-[10px] text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Tomorrow' : 'ನಾಳೆ' }}</span>
                    <span class="block text-sm my-0.5">⛅</span>
                    <span class="font-bold text-[11px] text-white">{{ ($todayWeather->temperature_max ?? 28) - 1 }}°C</span>
                </div>
                <div class="bg-black/25 backdrop-blur-sm border border-white/10 p-1.5 rounded-xl">
                    <span class="block text-[10px] text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Day 3' : '3ನೇ ದಿನ' }}</span>
                    <span class="block text-sm my-0.5">🌧️</span>
                    <span class="font-bold text-[11px] text-white">{{ ($todayWeather->temperature_max ?? 28) - 2 }}°C</span>
                </div>
            </div>

            <!-- View Full Forecast Link -->
            <a href="{{ route('farmer.weather.index') }}" 
               class="text-center text-xs font-bold text-emerald-200 hover:text-white pt-1 z-10 relative {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'View 7-Day District Forecast ›' : '7 ದಿನಗಳ ಸಂಪೂರ್ಣ ಹವಾಮಾನ ವರದಿ ನೋಡಿ ›' }}
            </a>

        </aside>

    </div>

    <!-- ==================== 3. KARNATAKA IMD WEATHER RADAR SUMMARY STRIP ==================== -->
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
            <span class="bg-yellow-50 text-yellow-800 border border-yellow-200 px-2.5 py-1 rounded-lg">
                🟡 {{ $activeLocale === 'en' ? 'Malnad: Moderate Rain' : 'ಮಲೆನಾಡು: ಸಾಧಾರಣ ಮಳೆ' }}
            </span>
            <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-1 rounded-lg">
                🟢 {{ $activeLocale === 'en' ? 'South Interior: Fair Weather' : 'ದಕ್ಷಿಣ ಒಳನಾಡು: ಅನುಕೂಲಕರ' }}
            </span>
        </div>
    </section>

    <!-- ==================== 4. ALL CROPS DIRECTORY (ALL MANDIS) ==================== -->
    <section id="allCropsSection" class="p-2 sm:p-6 bg-[#FAF8F5] rounded-2xl sm:rounded-3xl border-2 border-[#E5DECE] shadow-sm space-y-3 sm:space-y-4">
        
        <!-- Header with Dual View Toggle (Cards vs List) & Search Input -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg sm:text-xl font-black text-[#1C5A2C] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-2">
                        <span>🌾</span>
                        <span>{{ $activeLocale === 'en' ? 'All Market Prices' : 'ಇಂದಿನ ಎಲ್ಲಾ ಮಾರುಕಟ್ಟೆ ದರಗಳು' }}</span>
                    </h2>
                    <span class="bg-[#EAF4EC] text-[#1C5A2C] border border-[#B8DEC0] text-[11px] font-extrabold px-2.5 py-0.5 rounded-full">
                        {{ $distinctCropPrices->count() }} {{ $activeLocale === 'en' ? 'Crops Tracked' : 'ಬೆಳೆಗಳು ಲಭ್ಯ' }}
                    </span>
                </div>
                <p class="text-xs text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
                    {{ $activeLocale === 'en' ? 'Official Karnataka APMC modal and average prices by commodity' : 'ಕರ್ನಾಟಕದ ಪ್ರಮುಖ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳ ಇಂದಿನ ಸರಾಸರಿ ಮತ್ತು ಮಾದರಿ ಧಾರಣೆ' }}
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
                           class="w-full pl-9 pr-8 py-2 bg-white border-2 border-[#D9CEB8] focus:border-[#1C5A2C] focus:ring-2 focus:ring-[#1C5A2C]/20 focus:bg-emerald-50/10 rounded-xl text-xs font-semibold text-stone-800 outline-none transition-all duration-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} shadow-sm">
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
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-2.5 text-xs font-bold text-stone-700">
            <button type="button" 
                    @click="setCategory('all')" 
                    :class="selectedCat === 'all' ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 border-[#D9CEB8] hover:border-[#1C5A2C]'"
                    class="flex-none px-4 py-2 rounded-xl border-2 transition-all cursor-pointer {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                🌾 {{ $activeLocale === 'en' ? 'All' : 'ಎಲ್ಲಾ' }}
            </button>
            
            @foreach($categories as $cat)
                <button type="button" 
                        @click="setCategory('{{ $cat->slug }}')" 
                        :class="selectedCat === '{{ $cat->slug }}' ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 border-[#D9CEB8] hover:border-[#1C5A2C]'"
                        class="flex-none px-4 py-2 rounded-xl border-2 transition-all cursor-pointer {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
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
                    {{ $catEmoji }} {{ $activeLocale === 'en' ? $cat->name : ($cat->name_kn ?? $cat->name) }}
                </button>
            @endforeach
        </div>

        <!-- Quick Hint Nudge -->
        <div class="inline-flex items-center gap-2 bg-[#EAF4EC] border border-[#B8DEC0] px-3.5 py-1.5 rounded-full text-xs text-[#1C5A2C] font-semibold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>👆</span> 
            <span>{{ $activeLocale === 'en' ? 'Tap any crop to view prices across different markets and seasonal trends' : 'ಯಾವುದೇ ಬೆಳೆಯ ಮೇಲೆ ಕ್ಲಿಕ್ ಮಾಡಿ — ವಿವಿಧ ಮಾರುಕಟ್ಟೆಗಳ ದರ ಮತ್ತು ಸೀಸನಲ್ ಮುನ್ಸೂಚನೆ ನೋಡಿ' }}</span>
        </div>

        <!-- ==================== VIEW 1: CLEAN FULL-BLEED CARDS GRID (Negilu Krushi Clean Master) ==================== -->
        <div id="cropsGrid" x-show="currentView === 'grid'" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3.5 lg:gap-4">
            @forelse($distinctCropPrices as $price)
                <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($price->market->name) }}"
                   data-cat="{{ $price->crop->category->slug ?? 'other' }}" 
                   data-name="{{ strtolower($price->crop->name . ' ' . ($price->crop->name_kn ?? '') . ' ' . $price->market->name . ' ' . ($price->market->district->name ?? '')) }}"
                   class="crop-article bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] overflow-hidden shadow-xs hover:shadow-md flex flex-col justify-between group cursor-pointer block"
                   style="transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s, box-shadow 0.2s;">
                    
                    <!-- Clean Photo (No clutter badges, crop name on bottom gradient) -->
                    <div class="relative h-24 sm:h-32 overflow-hidden bg-stone-100">
                        <img src="{{ $price->crop->photo_url }}" 
                             alt="{{ $price->crop->name }}" 
                             class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                        
                        @if($price->reliability_badge === 'Reliable')
                            <div class="absolute top-1.5 right-1.5">
                                <span class="bg-emerald-600/95 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full shadow-sm flex items-center gap-0.5">
                                    <span class="w-1 h-1 rounded-full bg-white animate-pulse"></span>
                                    <span>{{ $activeLocale === 'en' ? 'Reliable' : 'ವಿಶ್ವಸನೀಯ' }}</span>
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
                                @if(($price->price_spread ?? 0) > 0)
                                    <span class="text-[9px] sm:text-[10px] font-extrabold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded shrink-0">
                                        ↑ {{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}
                                    </span>
                                @elseif(($price->price_spread ?? 0) < 0)
                                    <span class="text-[9px] sm:text-[10px] font-extrabold text-red-700 bg-red-50 px-1.5 py-0.2 rounded shrink-0">
                                        ↓ {{ $activeLocale === 'en' ? 'Drop' : 'ಇಳಿಕೆ' }}
                                    </span>
                                @else
                                    <span class="text-[9px] sm:text-[10px] font-extrabold text-blue-700 bg-blue-50 px-1.5 py-0.2 rounded shrink-0">
                                        → {{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}
                                    </span>
                                @endif
                            </div>

                            <!-- Variety -->
                            <div class="text-[10px] sm:text-[11px] font-bold text-stone-600 mt-1 leading-tight break-words">
                                {{ $price->variety->name ?? 'Common' }}
                            </div>
                        </div>

                        <!-- Market Line & Tap Arrow -->
                        <div class="flex items-center justify-between text-[10px] sm:text-[11px] text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} pt-1.5 border-t border-stone-100 gap-1 mt-1">
                            <span class="flex items-center gap-1 text-stone-700 font-semibold truncate">
                                <span class="text-[9px] text-stone-400 shrink-0">📍</span>
                                <span class="truncate">{{ $price->market->name }} · {{ $price->market->district->name ?? '' }}</span>
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
                     data-name="{{ strtolower($price->crop->name . ' ' . ($price->crop->name_kn ?? '') . ' ' . $price->market->name . ' ' . ($price->market->district->name ?? '')) }}"
                     class="crop-list-item bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] p-3 sm:p-4 flex items-center justify-between gap-3 cursor-pointer shadow-sm hover:bg-emerald-50/30"
                     style="transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s, background-color 0.2s;">
                    
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
                                📍 {{ $price->market->name }} · {{ $price->variety->name ?? 'Common' }} · {{ $price->market->district->name ?? '' }}
                            </p>
                        </div>
                    </a>

                    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                        <div class="text-right">
                            <div class="text-base sm:text-lg font-black text-[#1C5A2C]">
                                ₹{{ number_format($price->modal_price) }} 
                                <span class="text-xs font-normal text-stone-500">/ {{ $price->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್') }}</span>
                            </div>
                            @if(($price->price_spread ?? 0) > 0)
                                <span class="text-[11px] font-extrabold text-emerald-700">↑ {{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}</span>
                            @elseif(($price->price_spread ?? 0) < 0)
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
