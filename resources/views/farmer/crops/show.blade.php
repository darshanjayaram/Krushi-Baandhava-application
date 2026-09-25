@extends('layouts.farmer')

@php
    $activeLocale = app()->getLocale();
@endphp

@section('title', ($activeLocale === 'en' 
    ? $crop->name . " — Today's Market Prices & Forecast" 
    : (($crop->name_kn ? $crop->name_kn . ' (' . $crop->name . ')' : $crop->name) . ' — ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು & ಮುನ್ಸೂಚನೆ')
))

@section('content')
@php
    $selectedMarketPrices = $selectedMarketPrices ?? collect();
    if ($selectedMarketPrices->isNotEmpty()) {
        $activePriceItem = $varietyId 
            ? ($selectedMarketPrices->firstWhere('variety_id', $varietyId) ?? $selectedMarketPrices->first())
            : $selectedMarketPrices->first();
    } else {
        $activePriceItem = $mandiPrices->first();
    }

    $displayModal = $activePriceItem ? (float) $activePriceItem->modal_price : ($stats['avg_modal'] > 0 ? (float) $stats['avg_modal'] : 0);
    $rawMktName = $selectedMarket ? $selectedMarket->name : ($activePriceItem ? $activePriceItem->market->name : null);
    $rawMktKn = $selectedMarket ? $selectedMarket->name_kn : ($activePriceItem ? $activePriceItem->market->name_kn : null);
    $displayMarketName = $rawMktName 
        ? ($activeLocale === 'en' ? $rawMktName : ($rawMktKn ?? $rawMktName)) 
        : ($activeLocale === 'en' ? 'State Average (Karnataka)' : 'ಕರ್ನಾಟಕ ಸರಾಸರಿ');
    $displayMarketDistrict = $selectedMarket?->district?->name ?? ($activePriceItem?->market?->district?->name ?? 'Karnataka');
    $isStandardQuintal = ($crop->standard_unit === 'Quintal' || !$crop->standard_unit);
    $perKgPrice = ($isStandardQuintal && $displayModal > 0) ? round($displayModal / 100, 1) : null;

    // Determine Market Advisory Sentiment from forecast
    $firstHorizon = !empty($forecast['horizons']) ? ($forecast['horizons'][1] ?? $forecast['horizons'][0]) : null;
    $forecastDir = $firstHorizon['direction'] ?? 'neutral';
@endphp

<div class="space-y-6">

    <!-- 1. Top Breadcrumb & Back Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('home') }}" 
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white border border-[#E8DFC8] text-stone-700 hover:text-stone-950 font-bold text-xs shadow-2xs hover:bg-stone-50 transition active:scale-95">
            <span class="text-sm leading-none">&lsaquo;</span>
            <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Back' : 'ಹಿಂದಕ್ಕೆ' }}</span>
            @if($activeLocale === 'kn')
                <span class="text-[11px] font-sans text-stone-400 font-normal">Back</span>
            @endif
        </a>

        <div class="flex items-center gap-2 text-xs font-semibold text-stone-500">
            <a href="{{ route('farmer.crops.index') }}" class="px-2.5 py-0.5 rounded-full bg-white border border-[#E8DFC8] text-stone-700 hover:text-emerald-800 transition">
                {{ $crop->category ? ($activeLocale === 'en' ? $crop->category->name : ($crop->category->name_kn ?? $crop->category->name)) : ($activeLocale === 'en' ? 'Crops' : 'ಬೆಳೆಗಳು') }}
            </a>
            <span>&bull;</span>
            <span class="text-stone-900 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? $crop->name : ($crop->name_kn ?? $crop->name) }}
            </span>
        </div>
    </div>

    <!-- 2. Hero 2-Column Showcase (Exact Negilu Krishi Architecture) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">

        <!-- Left Column: Large Crop Photo Card (5 Cols) -->
        <div class="lg:col-span-5 bg-white rounded-3xl overflow-hidden border border-[#E8DFC8] shadow-sm relative flex flex-col min-h-[380px] sm:min-h-[440px]">
            <!-- Full Height Image -->
            <img src="{{ $crop->photo_url }}" 
                 alt="{{ $crop->name }}" 
                 class="w-full h-full absolute inset-0 object-cover">
            
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-black/20"></div>

            <!-- Top Left Floating "● Reliable" Badge -->
            <div class="relative z-10 p-5 flex items-center justify-between">
                @if($boardMeta)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $boardMeta['theme'] === 'coffee' ? 'bg-amber-950/90 text-amber-200 border-amber-800' : 'bg-emerald-950/90 text-emerald-200 border-emerald-800' }} backdrop-blur-md text-xs font-black shadow-sm border font-sans">
                        <span>{{ $boardMeta['icon'] }}</span>
                        <span>{{ $boardMeta['badge_en'] }}</span>
                        @if($activeLocale === 'kn')
                            <span class="text-[10px] opacity-90 font-kannada font-normal">• {{ $boardMeta['badge_kn'] }}</span>
                        @endif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/95 backdrop-blur-md text-xs font-extrabold text-stone-800 shadow-sm border border-stone-200/60 font-sans">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Reliable</span>
                        @if($activeLocale === 'kn')
                            <span class="text-[10px] text-stone-500 font-kannada font-normal">• ಅಧಿಕೃತ</span>
                        @endif
                    </span>
                @endif

                @if($crop->is_major)
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-400/95 backdrop-blur-xs text-stone-950 font-black text-[10px] uppercase tracking-wider font-sans">
                        Major Crop
                    </span>
                @endif
            </div>

            <!-- Bottom Left Crop Name & Category Overlay -->
            <div class="relative z-10 mt-auto p-5 sm:p-6 space-y-1 text-white">
                <div class="text-[11px] font-black uppercase tracking-widest text-emerald-300 font-sans">
                    {{ strtoupper($crop->category ? ($activeLocale === 'en' ? $crop->category->name : ($crop->category->name_kn ?? $crop->category->name)) : 'COMMODITY') }}
                </div>
                @if($activeLocale === 'kn' && $crop->name_kn)
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white font-kannada drop-shadow-sm">
                        {{ $crop->name_kn }}
                    </h1>
                    <div class="text-base font-semibold text-emerald-200/90 font-sans">
                        {{ $crop->name }}
                    </div>
                @else
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white font-sans drop-shadow-sm">
                        {{ $crop->name }}
                    </h1>
                    @if($crop->name_kn)
                        <div class="text-base font-semibold text-amber-200/70 font-kannada">
                            {{ $crop->name_kn }}
                        </div>
                    @endif
                @endif
                <div class="pt-1 flex items-center gap-2 text-xs text-white/80 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>{{ $activeLocale === 'en' ? 'Standard Unit:' : 'ಪ್ರಮಾಣಿತ ಘಟಕ:' }} <strong>{{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : ($crop->standard_unit ?? 'Quintal') }}</strong></span>
                    @if($crop->scientific_name)
                        <span>•</span>
                        <span class="italic text-white/70">{{ $crop->scientific_name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Live Price, Grade Picker, Mandi Switcher & WhatsApp Share (7 Cols) -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-5 sm:p-7 border border-[#E8DFC8] shadow-sm flex flex-col justify-between space-y-5">
            
            <!-- A. Current Price Section -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-extrabold tracking-wider text-stone-400 uppercase {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'CURRENT PRICE' : 'ಇಂದಿನ ದರ' }}
                    </span>
                    <span class="text-[11px] font-semibold text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Updated:' : 'ನವೀಕರಿಸಲಾಗಿದೆ:' }} {{ $stats['date_formatted'] }}
                    </span>
                </div>

                <div class="flex flex-wrap items-baseline gap-2.5">
                    <div class="text-3xl sm:text-5xl font-black text-stone-900 tracking-tight font-sans">
                        {{ $displayModal > 0 ? '₹' . number_format($displayModal, 0) : '—' }}
                    </div>

                    @if($perKgPrice)
                        <div class="text-base sm:text-lg font-bold text-stone-500 font-sans">
                            ≈ ₹{{ $perKgPrice }}/kg
                        </div>
                    @else
                        <div class="text-sm font-semibold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            / {{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : strtolower($crop->standard_unit ?? 'quintal') }}
                        </div>
                    @endif

                    @if($activePriceItem && $activePriceItem->price_spread > 0)
                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-xs font-bold font-sans">
                            ↑ +₹{{ number_format($activePriceItem->price_spread, 0) }}
                        </span>
                    @endif
                </div>

                <!-- Active Mandi / Centre Info Badge (Negilu Krishi Alignment) -->
                <div class="flex flex-wrap items-center gap-2 text-xs text-stone-600 pt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="font-bold text-stone-800">
                        {{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : ($crop->standard_unit ?? 'Quintal') }} • @ {{ strtoupper($displayMarketName) }}
                    </span>

                    @if(!empty($isSelectedActualNearest) && !empty($nearestDistanceKm))
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#fff4e5] text-[#9a5b00] border border-[#ffe0b2] text-[11px] font-extrabold whitespace-nowrap shadow-2xs"
                              title="{{ $activeLocale === 'en' ? 'Closest mandi to your location' : 'ನಿಮ್ಮ ಸ್ಥಳಕ್ಕೆ ಅತ್ಯಂತ ಸಮೀಪದ ಮಾರುಕಟ್ಟೆ' }}">
                            📍 {{ $activeLocale === 'en' ? 'nearest market' : 'ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆ' }} • {{ round($nearestDistanceKm) }} km
                        </span>
                    @elseif(!empty($nearestDistanceKm))
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold whitespace-nowrap">
                            📍 {{ round($nearestDistanceKm) }} km {{ $activeLocale === 'en' ? 'away' : 'ದೂರ' }}
                            @if(!empty($actualNearestMarket) && $actualNearestMarket->id !== $selectedMarket?->id)
                                <span class="text-[10px] text-emerald-600 font-medium">({{ $activeLocale === 'en' ? 'Nearest: ' : 'ಸಮೀಪ: ' }}{{ $activeLocale === 'en' ? $actualNearestMarket->name : ($actualNearestMarket->name_kn ?? $actualNearestMarket->name) }} {{ round($actualNearestMarket->distance_km) }}km)</span>
                            @endif
                        </span>
                    @endif

                    <span class="text-stone-300">•</span>
                    <span class="text-stone-500 font-medium">{{ $activeLocale === 'en' ? 'as of' : 'ದಿನಾಂಕ' }} {{ \Carbon\Carbon::parse($latestDate)->format('d M') }}</span>

                    @if($boardMeta)
                        <span class="text-amber-900 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} text-[11px]">({{ $activeLocale === 'en' ? $boardMeta['badge_en'] : $boardMeta['badge_kn'] }})</span>
                    @endif
                </div>
            </div>

            <!-- B. "PICK YOUR GRADE" Section (Smart Single vs Multi Grade Layout) -->
            @php
                $displayMarketPrices = isset($selectedMarketPrices) && $selectedMarketPrices->isNotEmpty() ? $selectedMarketPrices : collect();
            @endphp

            @if($displayMarketPrices->count() === 1)
                <!-- Single Grade: Clean Compact Box Without Scroll -->
                <div class="pt-2 border-t border-stone-100">
                    <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-stone-900 text-white shadow-xs">
                        <div>
                            <div class="text-[11px] font-extrabold tracking-wide uppercase text-stone-300 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $displayMarketPrices->first()->variety ? $displayMarketPrices->first()->variety->displayName($activeLocale) : ($activeLocale === 'en' ? 'Standard Grade' : 'ಸಾಮಾನ್ಯ ಗ್ರೇಡ್') }}
                            </div>
                            <div class="text-base font-black text-emerald-400 font-sans tracking-tight">
                                ₹{{ number_format($displayMarketPrices->first()->modal_price, 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($displayMarketPrices->count() > 1)
                <!-- Multiple Grades: Wrap Row Without Horizontal Scroll -->
                <div class="space-y-2 pt-2 border-t border-stone-100">
                    <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'PICK YOUR GRADE' : 'ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ' }}
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        @foreach($displayMarketPrices as $smp)
                            @php
                                $isVarSelected = ($varietyId == $smp->variety_id) || (!$varietyId && $loop->first);
                                $vLabel = $smp->variety ? $smp->variety->displayName($activeLocale) : ($activeLocale === 'en' ? 'Standard' : 'ಸಾಮಾನ್ಯ');
                            @endphp
                            <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $smp->variety_id, 'market' => $displayMarketName])) }}"
                               class="px-3.5 py-2 rounded-2xl font-bold transition border flex flex-col items-start gap-0.5 cursor-pointer {{ $isVarSelected ? 'bg-stone-900 text-white border-stone-900 shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
                                <span class="text-[11px] {{ $isVarSelected ? 'text-stone-300' : 'text-stone-600' }} {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                    {{ $vLabel }}
                                </span>
                                <span class="text-sm font-black font-sans {{ $isVarSelected ? 'text-emerald-400' : 'text-emerald-800' }}">
                                    ₹{{ number_format($smp->modal_price, 0) }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @elseif(isset($availableVarieties) && $availableVarieties->isNotEmpty())
                <!-- Fallback General Varieties (Wrap Row) -->
                <div class="space-y-2 pt-2 border-t border-stone-100">
                    <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'PICK YOUR GRADE' : 'ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ' }}
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'market' => $marketParam])) }}"
                           class="px-3.5 py-2 rounded-xl font-bold transition border {{ empty($varietyId) ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-2xs' : 'bg-stone-50 text-stone-700 hover:bg-stone-100 border-stone-200' }}">
                            <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">{{ $activeLocale === 'en' ? 'All Grades (FAQ)' : 'ಎಲ್ಲಾ ತಳಿಗಳು / FAQ' }}</span>
                        </a>

                        @foreach($availableVarieties as $v)
                            @php
                                $isVarSelected = ($varietyId == $v->id);
                                $vTitle = $v->displayName($activeLocale);
                            @endphp
                            <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $v->id, 'market' => $marketParam])) }}"
                               class="px-3.5 py-2 rounded-xl font-bold transition border flex items-center gap-1.5 {{ $isVarSelected ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-2xs' : 'bg-stone-50 text-stone-700 hover:bg-stone-100 border-stone-200' }}">
                                <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">{{ $vTitle }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- C. "VIEW DIFFERENT MARKET / CENTRE" (Wrapped Grid Like Negilu Krishi) -->
            <div class="space-y-2.5 pt-2 border-t border-stone-100" x-data="{ activeSort: '{{ $defaultMarketSort ?? 'nearest_first' }}', showAllRadius: false }">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            @if($boardMeta)
                                {{ $activeLocale === 'en' ? 'VIEW DIFFERENT CENTRE' : $boardMeta['centre_label_kn'] }}
                            @else
                                {{ $activeLocale === 'en' ? 'VIEW DIFFERENT MARKET (All Mandis)' : 'ಮಾರುಕಟ್ಟೆ ಬದಲಿಸಿ (ಕರ್ನಾಟಕ ಮಂಡಿಗಳು)' }}
                            @endif
                            @if(!empty($marketRadiusKm))
                                <span class="text-[10px] font-semibold text-stone-400 normal-case">({{ $marketRadiusKm }} km)</span>
                            @endif
                        </div>

                        @if(!empty($allowUserSortToggle))
                            <div class="inline-flex items-center bg-stone-100 p-0.5 rounded-lg border border-stone-200 text-[10px] font-bold">
                                <button type="button" 
                                        @click="activeSort = 'nearest_first'"
                                        :class="activeSort === 'nearest_first' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-stone-500 hover:text-stone-800'"
                                        class="px-2 py-0.5 rounded transition flex items-center gap-1 cursor-pointer">
                                    <span>📍</span>
                                    <span>{{ $activeLocale === 'en' ? 'Nearest' : 'ಹತ್ತಿರ' }}</span>
                                </button>
                                <button type="button" 
                                        @click="activeSort = 'highest_price_first'"
                                        :class="activeSort === 'highest_price_first' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-stone-500 hover:text-stone-800'"
                                        class="px-2 py-0.5 rounded transition flex items-center gap-1 cursor-pointer">
                                    <span>🔥</span>
                                    <span>{{ $activeLocale === 'en' ? 'Top Rate' : 'ಹೆಚ್ಚಿನ ಬೆಲೆ' }}</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($marketParam)
                        <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $varietyId])) }}" 
                           class="text-xs text-stone-500 hover:text-stone-800 font-bold flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-stone-100 hover:bg-stone-200 border border-stone-200 transition" 
                           title="{{ $activeLocale === 'en' ? 'Reset to nearest market' : 'ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆಗೆ ಮರುಹೊಂದಿಸಿ' }}">
                            <span>✕</span>
                            <span>{{ $activeLocale === 'en' ? 'Reset' : 'ಮರುಹೊಂದಿಸಿ' }}</span>
                        </a>
                    @endif
                </div>

                <!-- Quick Mandi / Centre Pills in Wrap Row (Zero Horizontal Scroll!) -->
                @php
                    $beyondRadiusCount = 0;
                @endphp
                <div class="flex flex-wrap gap-2 text-xs max-h-56 overflow-y-auto pr-1">
                    @foreach($availableMarkets as $am)
                        @php
                            $isMktSelected = ($selectedMarket && $selectedMarket->id === $am->id);
                            $amTitle = $activeLocale === 'en' ? $am->name : ($am->name_kn ?? $am->name);
                            $isWithin = !empty($am->is_within_radius);
                            if (!$isWithin && !$isMktSelected) {
                                $beyondRadiusCount++;
                            }
                        @endphp
                        <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'market' => $am->name])) }}"
                           x-show="showAllRadius || {{ ($isWithin || $isMktSelected) ? 'true' : 'false' }}"
                           :style="activeSort === 'highest_price_first' ? 'order: {{ $am->price_rank ?? 999 }}' : 'order: {{ $am->distance_rank ?? 999 }}'"
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full font-bold transition border cursor-pointer {{ $isMktSelected ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
                            @if($isMktSelected)
                                <span class="text-amber-300">★</span>
                            @endif
                            <span class="font-sans uppercase text-[12px] font-extrabold tracking-wide">{{ $amTitle }}</span>
                            @if(isset($am->distance_km) && $am->distance_km < 1000)
                                <span class="text-[10px] {{ $isMktSelected ? 'text-emerald-200' : 'text-stone-400' }} font-medium">({{ round($am->distance_km) }}km)</span>
                            @endif

                            @if(!empty($enableSmartBadges))
                                @if(!empty($am->is_nearest) && !$isMktSelected)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-sky-100 text-sky-800 border border-sky-200">
                                        📍 {{ $activeLocale === 'en' ? 'Nearest' : 'ಹತ್ತಿರ' }}
                                    </span>
                                @endif
                                @if(!empty($am->is_top_rate) && !$isMktSelected)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200">
                                        🔥 {{ $activeLocale === 'en' ? 'Top Rate' : 'ಅತ್ಯಧಿಕ' }}
                                    </span>
                                @endif
                            @endif

                            @if(isset($am->today_modal_price) && $am->today_modal_price > 0)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-black font-sans {{ $isMktSelected ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-800' }}">
                                    ₹{{ number_format($am->today_modal_price, 0) }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if($beyondRadiusCount > 0)
                    <div class="pt-0.5">
                        <button type="button" 
                                @click="showAllRadius = !showAllRadius" 
                                class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 transition inline-flex items-center gap-1 cursor-pointer">
                            <span x-text="showAllRadius ? '▲ {{ $activeLocale === 'en' ? 'Hide distant mandis beyond' : 'ದೂರದ ಮಂಡಿಗಳನ್ನು ಮರೆಮಾಡಿ' }} {{ $marketRadiusKm }} km' : '+ {{ $activeLocale === 'en' ? 'Show' : 'ತೋರಿಸಿ' }} {{ $beyondRadiusCount }} {{ $activeLocale === 'en' ? 'more mandis beyond' : 'ಹೆಚ್ಚಿನ ಮಂಡಿಗಳು' }} {{ $marketRadiusKm }} km ▾'"></span>
                        </button>
                    </div>
                @endif
            </div>

            <!-- D. Market Advisory / Sentiment Banner -->
            <div class="rounded-2xl p-4 border transition {{ $forecastDir === 'down' ? 'bg-amber-50/80 border-amber-200/90 text-amber-950' : ($forecastDir === 'up' ? 'bg-emerald-50/80 border-emerald-200/90 text-emerald-950' : 'bg-stone-50 border-stone-200 text-stone-800') }}">
                <div class="flex items-start gap-3">
                    <span class="text-2xl shrink-0">
                        {{ $forecastDir === 'down' ? '⏰' : ($forecastDir === 'up' ? '📈' : '💡') }}
                    </span>
                    <div class="space-y-0.5 text-xs">
                        <div class="font-extrabold text-sm flex items-center gap-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            @if($forecastDir === 'down')
                                <span>{{ $activeLocale === 'en' ? 'Optimal Time to Sell (Sell now)' : 'ಮಾರಾಟಕ್ಕೆ ಸೂಕ್ತ ಸಮಯ' }}</span>
                            @elseif($forecastDir === 'up')
                                <span>{{ $activeLocale === 'en' ? 'Price Rise Expected (Hold / Watch)' : 'ಧಾರಣೆ ಏರಿಕೆಯ ಮುನ್ಸೂಚನೆ' }}</span>
                            @else
                                <span>{{ $activeLocale === 'en' ? 'Market Advisory (Stable)' : 'ಮಾರುಕಟ್ಟೆ ಸಲಹೆ' }}</span>
                            @endif
                        </div>
                        <p class="leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} text-stone-600">
                            @if($forecastDir === 'down')
                                {{ $activeLocale === 'en' ? 'Arrivals are expected to increase over the coming weeks, which may cause prices to soften. Selling at current favorable rates is advisable.' : 'ಮುಂದಿನ ವಾರಗಳಲ್ಲಿ ಮಾರುಕಟ್ಟೆಗೆ ಆವಕ ಹೆಚ್ಚಾಗುವ ಮುನ್ಸೂಚನೆ ಇದ್ದು, ದರಗಳು ಕೊಂಚ ಇಳಿಕೆಯಾಗುವ ಸಾಧ್ಯತೆಯಿದೆ. ಸದ್ಯದ ಉತ್ತಮ ಬೆಲೆಯಲ್ಲಿ ಮಾರಾಟ ಮಾಡುವುದು ಸೂಕ್ತ.' }}
                            @elseif($forecastDir === 'up')
                                {{ $activeLocale === 'en' ? 'Signs of rising demand are observed in regional mandis. Prices may improve further in the coming days.' : 'ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಹೆಚ್ಚಾಗುವ ಲಕ್ಷಣಗಳು ಕಂಡುಬರುತ್ತಿದ್ದು, ಮುಂದಿನ ದಿನಗಳಲ್ಲಿ ದರ ಇನ್ನಷ್ಟು ಸುಧಾರಿಸುವ ಸಂಭವವಿದೆ.' }}
                            @else
                                {{ $activeLocale === 'en' ? 'Market rates are steady. Consider transportation costs and arrival volumes of nearby mandis before selling.' : 'ಮಾರುಕಟ್ಟೆ ದರಗಳು ಸ್ಥಿರವಾಗಿದ್ದು, ಹತ್ತಿರದ ಮಂಡಿಗಳ ಸಾರಿಗೆ ವೆಚ್ಚ ಮತ್ತು ಆವಕ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧಾರ ಕೈಗೊಳ್ಳಿ.' }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- E. Action Buttons: WhatsApp Share & Where to Sell Simulator -->
            @php
                $sharePriceText = "🌾 *" . ($activeLocale === 'en' ? 'Krushi Baandhava — ' : 'ಕೃಷಿ ಬಾಂಧವ — ') . ($activeLocale === 'en' ? $crop->name : ($crop->name_kn ?: $crop->name)) . "*\n"
                    . "📍 " . ($boardMeta ? ($activeLocale === 'en' ? 'Centre: ' : 'ಕೇಂದ್ರ: ') : ($activeLocale === 'en' ? 'Market: ' : 'ಮಾರುಕಟ್ಟೆ: ')) . $displayMarketName . ($boardMeta ? '' : (str_ends_with(strtolower($displayMarketName), 'apmc') ? '' : ' APMC')) . "\n"
                    . "💰 " . ($activeLocale === 'en' ? "Today's Modal Rate: ₹" : 'ಇಂದಿನ ಮಾದರಿ ದರ: ₹') . number_format($displayModal, 0) . " / " . ($activeLocale === 'en' ? ($crop->standard_unit ?? 'Quintal') : ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್'))) . "\n"
                    . ($perKgPrice ? ($activeLocale === 'en' ? "⚖️ Approx per kg: ≈ ₹" : "⚖️ ಪ್ರತಿ ಕೆ.ಜಿ ಗೆ: ≈ ₹") . $perKgPrice . "/kg\n" : "")
                    . "📅 " . ($activeLocale === 'en' ? 'Date: ' : 'ದಿನಾಂಕ: ') . $stats['date_formatted'] . "\n"
                    . "👉 " . ($activeLocale === 'en' ? 'View Full Rate & Forecast: ' : 'ಸಂಪೂರ್ಣ ದರ & ಮುನ್ಸೂಚನೆ ವೀಕ್ಷಿಸಿ: ') . url()->current();
                $whatsappDetailUrl = "https://wa.me/?text=" . rawurlencode($sharePriceText);
            @endphp

            <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <!-- Bright WhatsApp Button (Negilu Krishi Bright Green) -->
                <a href="{{ $whatsappDetailUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="w-full sm:flex-1 py-3.5 px-6 rounded-2xl bg-[#25D366] hover:bg-[#20BD5A] text-white font-black text-sm tracking-wide shadow-sm hover:shadow transition transform active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
                    <span class="text-lg">💬</span>
                    <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Share Price' : 'ದರ ಶೇರ್ ಮಾಡಿ' }}</span>
                </a>

                <!-- Net Profit Simulator Button (Where to Sell) -->
                <a href="{{ route('farmer.decision.where-to-sell', ['crop' => $crop->slug]) }}"
                   class="w-full sm:w-auto py-3.5 px-5 rounded-2xl bg-stone-900 hover:bg-stone-800 text-white font-bold text-xs shadow-sm transition transform active:scale-95 flex items-center justify-center gap-2 shrink-0">
                    @if($activeLocale === 'en')
                        <span class="font-sans">⚖️ Where to Sell?</span>
                        <span class="font-sans font-bold opacity-90 text-[11px]">(Net Profit Comparison)</span>
                    @else
                        <span class="font-kannada">⚖️ ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?</span>
                        <span class="font-kannada font-bold opacity-90 text-[11px]">(ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ)</span>
                    @endif
                    <span class="text-xs">&rarr;</span>
                </a>
            </div>

        </div>

    </div>

    <!-- 3. 4-Metric State Overview Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Highest Rate -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? ($boardMeta ? 'Highest Rate' : 'State Highest Rate') : ($boardMeta ? 'ಅತ್ಯಧಿಕ ದರ' : 'ರಾಜ್ಯದ ಗರಿಷ್ಠ ದರ') }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-emerald-950 mt-1 font-sans">
                {{ $stats['highest_modal'] > 0 ? '₹' . number_format($stats['highest_modal'], 0) : '—' }}
            </div>
            <div class="text-xs font-semibold text-emerald-700 truncate mt-0.5 font-sans">
                {{ $stats['highest_market'] }}{{ $boardMeta ? '' : (str_ends_with(strtolower($stats['highest_market']), 'apmc') ? '' : ' APMC') }}
            </div>
        </div>

        <!-- Lowest Rate -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? 'Lowest Rate' : 'ಕನಿಷ್ಠ ದರ' }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-800 mt-1 font-sans">
                {{ $stats['lowest_modal'] > 0 ? '₹' . number_format($stats['lowest_modal'], 0) : '—' }}
            </div>
            <div class="text-xs font-semibold text-stone-500 truncate mt-0.5 font-sans">
                {{ $stats['lowest_market'] }}{{ $boardMeta ? '' : (str_ends_with(strtolower($stats['lowest_market']), 'apmc') ? '' : ' APMC') }}
            </div>
        </div>

        <!-- State/Board Average -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? ($boardMeta ? 'Board Average' : 'State Average') : ($boardMeta ? 'ಮಂಡಳಿ ಸರಾಸರಿ' : 'ರಾಜ್ಯ ಸರಾಸರಿ') }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1 font-sans">
                {{ $stats['avg_modal'] > 0 ? '₹' . number_format($stats['avg_modal'], 0) : '—' }}
            </div>
            <div class="text-xs font-semibold text-stone-500 mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $stats['total_mandis'] }} {{ $activeLocale === 'en' ? ($boardMeta ? 'centres' : 'mandis') : ($boardMeta ? 'ಕೇಂದ್ರಗಳಿಂದ' : 'ಮಂಡಿಗಳಿಂದ') }}
            </div>
        </div>

        <!-- Arrivals -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} block">
                {{ $activeLocale === 'en' ? 'Total Arrivals' : 'ಒಟ್ಟು ಆವಕ' }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1 font-sans">
                {{ number_format($stats['total_arrivals'], 1) }}
            </div>
            <div class="text-xs font-semibold text-stone-500 mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Quintals' : 'ಕ್ವಿಂಟಾಲ್' }}
            </div>
        </div>
    </div>

    <!-- 4. "What's next" Forecast Horizons (Negilu Krishi 4-Card Projections) -->
    <div class="bg-white rounded-3xl p-5 sm:p-7 border border-[#E8DFC8] shadow-sm space-y-5">
        
        <!-- Section Header with Green Bar -->
        <div class="flex items-center justify-between pb-3 border-b border-stone-100">
            <div class="flex items-center gap-2.5">
                <span class="w-1.5 h-6 rounded-full bg-[#1C5A2C]"></span>
                <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? "What's next" : 'ಮುಂದೇನು?' }}
                </h2>
                @if($activeLocale === 'en')
                    <span class="text-xs font-bold text-stone-500 font-sans">
                        • Price Forecast & Projections
                    </span>
                @else
                    <span class="text-xs font-bold text-stone-500 font-kannada">
                        • ದರ ಮುನ್ಸೂಚನೆ & ನಿರೀಕ್ಷಿತ ಶ್ರೇಣಿ
                    </span>
                @endif
            </div>
            <div class="text-xs font-bold text-emerald-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                {{ $activeLocale === 'en' ? 'Updated daily' : 'ದೈನಂದಿನ ಅಪ್ಡೇಟ್' }}
            </div>
        </div>

        @if(!empty($forecast['is_sufficient']) && !empty($forecast['horizons']))
            <!-- 4-Card Forecast Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($forecast['horizons'] as $idx => $h)
                    @php
                        $hDays = $h['horizon_days'] ?? ($h['horizon'] ?? 1);
                        $horizonTitles = [
                            1 => ['en' => 'TOMORROW', 'kn' => 'ನಾಳೆ'],
                            7 => ['en' => 'NEXT WEEK', 'kn' => 'ಮುಂದಿನ ವಾರ'],
                            15 => ['en' => 'FORTNIGHT', 'kn' => '15 ದಿನ (ಪಕ್ಷ)'],
                            30 => ['en' => 'NEXT MONTH', 'kn' => 'ಮುಂದಿನ ತಿಂಗಳು'],
                        ];
                        $horizonMeta = $horizonTitles[$hDays] ?? ['en' => "+{$hDays} DAYS", 'kn' => $h['label_kn'] ?? 'ಮುನ್ಸೂಚನೆ'];
                    @endphp
                    <div class="p-4 rounded-2xl bg-stone-50/80 border border-stone-200/80 hover:border-emerald-500/60 hover:bg-emerald-50/20 transition flex flex-col justify-between space-y-3 group">
                        
                        <!-- Top Label & Date -->
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black tracking-wider text-stone-700 uppercase {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? $horizonMeta['en'] : $horizonMeta['kn'] }}
                            </span>
                            <span class="text-[11px] font-bold text-stone-400 font-sans">
                                {{ $h['target_date_formatted'] }}
                            </span>
                        </div>

                        <!-- Expected Price -->
                        <div class="space-y-1">
                            <div class="text-[10px] font-bold text-stone-400 uppercase {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? "EXPECTED PRICE • {$horizonMeta['en']}" : "ನಿರೀಕ್ಷಿತ ದರ • {$horizonMeta['kn']}" }}
                            </div>
                            <div class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight font-sans flex items-baseline gap-1">
                                <span>₹{{ number_format($h['expected_price'], 0) }}</span>
                                <span class="text-xs font-semibold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/ {{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}</span>
                            </div>

                            <!-- Percentage movement indicator -->
                            <div class="text-xs font-bold font-sans flex items-center gap-1.5">
                                @if($h['direction'] === 'up')
                                    <span class="text-emerald-700">▲ +{{ $h['percentage_change'] }}% {{ $activeLocale === 'en' ? 'Expected Rise' : 'ಏರಿಕೆ ಸಾಧ್ಯತೆ' }}</span>
                                @elseif($h['direction'] === 'down')
                                    <span class="text-rose-600">▼ {{ $h['percentage_change'] }}% {{ $activeLocale === 'en' ? 'Expected Drop' : 'ಇಳಿಕೆ ಸಾಧ್ಯತೆ' }}</span>
                                @else
                                    <span class="text-stone-500">▬ {{ $activeLocale === 'en' ? 'Steady / Flat' : 'ಸ್ಥಿರ ಧಾರಣೆ' }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Confidence & Range Footer -->
                        <div class="pt-2.5 border-t border-stone-200/70 space-y-1 text-xs">
                            <div class="flex items-center justify-between text-stone-500 font-sans">
                                <span class="text-[11px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Range:' : 'ಶ್ರೇಣಿ:' }}</span>
                                <span class="font-bold text-stone-800">
                                    ₹{{ number_format($h['lower_bound'], 0) }} – ₹{{ number_format($h['upper_bound'], 0) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] font-sans">
                                <span class="text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Confidence:' : 'ವಿಶ್ವಾಸಾರ್ಹತೆ:' }}</span>
                                <span class="font-extrabold {{ $h['confidence_score'] >= 80 ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $h['confidence_score'] }}%
                                </span>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <!-- Model Accuracy Caveat for Volatile Crops -->
            @if(!empty($forecast['is_high_volatility']))
                <div class="p-3 rounded-xl bg-amber-50/90 border border-amber-300/80 text-[11.5px] font-medium text-amber-900 flex items-start gap-2.5 leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="text-amber-700 shrink-0 text-sm">⚠️</span>
                    <span>
                        {{ $activeLocale === 'en' ? ($forecast['caveat_en'] ?? 'The model is subject to market arrival volatility for this crop — treat these projections as an informative indicator, not an absolute guarantee.') : ($forecast['caveat_kn'] ?? 'ಈ ಬೆಳೆಗೆ ಮಾರುಕಟ್ಟೆ ಆವಕದ ಏರಿಳಿತ ಹೆಚ್ಚಿರುತ್ತದೆ — ಈ ಮುನ್ಸೂಚನೆಯನ್ನು ಮಾಹಿತಿ ಮಾರ್ಗದರ್ಶಿಯಾಗಿ ಪರಿಗಣಿಸಿ, ಖಚಿತ ಗ್ಯಾರಂಟಿ ಅಲ್ಲ.') }}
                    </span>
                </div>
            @endif

            <!-- Actionable "Why" Explanatory Card (Negilu Krushi Standard) -->
            @if(!empty($forecast['why_summary_en']) || !empty($forecast['why_summary_kn']))
                <div class="p-3.5 sm:p-4 rounded-2xl bg-stone-50 border border-stone-200/90 text-stone-800 flex items-start gap-3 text-xs leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="text-lg shrink-0">💡</span>
                    <div>
                        <strong class="font-black text-stone-900">{{ $activeLocale === 'en' ? 'Why:' : 'ವಿಶ್ಲೇಷಣೆ / ಕಾರಣ:' }}</strong>
                        <span class="text-stone-700">{{ $activeLocale === 'en' ? $forecast['why_summary_en'] : $forecast['why_summary_kn'] }}</span>
                    </div>
                </div>
            @endif
        @else
            <!-- Data Insufficiency Notice -->
            <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-950 flex items-start gap-3">
                <span class="text-xl shrink-0">ℹ️</span>
                <div class="space-y-1 text-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <div class="font-bold text-sm text-amber-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Data Insufficiency Notice' : 'ದರ ಮಾಹಿತಿ ಕೊರತೆ ಸೂಚನೆ' }}</div>
                    <p class="leading-relaxed">
                        {{ $activeLocale === 'en' ? ($forecast['message_en'] ?? 'Minimum 30 days of market prices required for a reliable forecast.') : ($forecast['message_kn'] ?? 'ವಿಶ್ವಾಸಾರ್ಹ ಮುನ್ಸೂಚನೆಗೆ ಕನಿಷ್ಠ 30 ದಿನಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ.') }}
                    </p>
                    <p class="text-amber-800/80">
                        {{ $activeLocale === 'en' 
                            ? 'Krushi Baandhava does not generate synthetic prices. Projections will automatically activate once 30 continuous days of mandi records are logged.' 
                            : 'ಕೃಷಿ ಬಾಂಧವ ಕೃತಕ ಅಂದಾಜುಗಳನ್ನು ಪ್ರದರ್ಶಿಸುವುದಿಲ್ಲ. ಮಂಡಿಗಳಿಂದ 30 ದಿನಗಳ ನಿರಂತರ ದರಗಳು ದಾಖಲಾದ ನಂತರ ನಿಖರ ಗಣಿತೀಯ ಮುನ್ಸೂಚನೆ ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.' }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Disclaimer -->
        <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/80 text-[11px] text-stone-500 leading-relaxed flex items-center gap-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="text-stone-400 shrink-0">⚖️</span>
            <span>
                <strong>{{ $activeLocale === 'en' ? 'Disclaimer:' : 'ಗಮನಿಸಿ:' }}</strong>
                {{ $activeLocale === 'en' 
                    ? ($forecast['disclaimer_en'] ?? 'Mathematical estimation based on past price patterns. Actual realized rates may vary based on weather, daily market arrival volumes, and government trade policies.') 
                    : ($forecast['disclaimer_kn'] ?? 'ಇದು ಕೇವಲ ಹಿಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳ ಪ್ರವೃತ್ತಿ ಆಧಾರಿತ ಗಣಿತೀಯ ಅಂದಾಜು. ನೈಜ ದರಗಳು ಹವಾಮಾನ ಪರಿಸ್ಥಿತಿ, ಮಾರುಕಟ್ಟೆಯ ಆವಕ ಪ್ರಮಾಣ ಮತ್ತು ಸರ್ಕಾರದ ನೀತಿಗಳಿಂದ ವ್ಯತ್ಯಾಸವಾಗಬಹುದು.') }}
            </span>
        </div>

    </div>

    <!-- 5. Mandi / Board Rates Comparison List (Ranked Highest to Lowest) -->
    <div class="space-y-4" x-data="{ showAllMandis: false }">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-6 rounded-full {{ $boardMeta ? ($boardMeta['theme'] === 'coffee' ? 'bg-amber-800' : 'bg-emerald-700') : 'bg-[#1C5A2C]' }}"></span>
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        @if($boardMeta)
                            {{ $activeLocale === 'en' ? $boardMeta['rates_heading_en'] : $boardMeta['rates_heading_kn'] }}
                        @else
                            {{ $activeLocale === 'en' ? 'Mandi Price Comparison (Ranked by Best Price)' : 'ಮಂಡಿವಾರು ದರ ಹೋಲಿಕೆ (ಅತ್ಯುತ್ತಮ ದರ)' }}
                        @endif
                    </h2>
                    <p class="text-xs text-stone-500 font-sans">
                        @if($boardMeta)
                            {{ $boardMeta['authority'] }} {{ $activeLocale === 'en' ? 'Official Centres Near You' : 'ನಿಮ್ಮ ಹತ್ತಿರದ ಅಧಿಕೃತ ಕೇಂದ್ರಗಳ ದರ ಹೋಲಿಕೆ' }}
                        @else
                            @if(isset($userDistrict) && $userDistrict)
                                📍 {{ $activeLocale === 'en' ? 'Showing 2 nearest mandis to ' . $userDistrict->name . ' ranked by best price' : 'ನಿಮ್ಮ ಸ್ಥಳಕ್ಕೆ (' . ($userDistrict->name_kn ?? $userDistrict->name) . ') ಹತ್ತಿರವಿರುವ 2 ಮಂಡಿಗಳ ದರ ಹೋಲಿಕೆ' }}
                            @else
                                📍 {{ $activeLocale === 'en' ? 'Showing 2 nearest mandis to your location ranked by best price' : 'ನಿಮ್ಮ ಸ್ಥಳಕ್ಕೆ ಹತ್ತಿರವಿರುವ 2 ಮಂಡಿಗಳ ದರ ಹೋಲಿಕೆ' }}
                            @endif
                        @endif
                    </p>
                </div>
            </div>
            <span class="text-xs text-stone-500 font-sans">{{ $activeLocale === 'en' ? 'Date: ' : 'ದಿನಾಂಕ: ' }}{{ $stats['date_formatted'] }}</span>
        </div>

        @php
            $mandiGroups = $mandiGroups ?? ($mandiPrices->isNotEmpty() ? $mandiPrices->groupBy('market_id')->map(function ($prices) {
                $bestRecord = $prices->sortByDesc('modal_price')->first();
                return (object) [
                    'market' => $bestRecord->market,
                    'best_item' => $bestRecord,
                    'best_modal' => (float) $bestRecord->modal_price,
                    'total_arrivals' => (float) $prices->sum('arrival_quantity'),
                    'arrival_unit' => $bestRecord->arrival_unit ?? 'Qtl',
                    'unit' => $bestRecord->unit ?? 'Quintal',
                    'price_date' => $bestRecord->price_date,
                    'dataSource' => $bestRecord->dataSource,
                    'varieties' => $prices->sortByDesc('modal_price')->values(),
                    'variety_count' => $prices->count(),
                    'distance_km' => $bestRecord->market->distance_km ?? 9999,
                    'is_same_district' => $bestRecord->market->is_same_district ?? false,
                ];
            })->sortByDesc('best_modal')->values() : collect());

            $nearestTwoGroups = $nearestTwoGroups ?? $mandiGroups->take(2);
            $allOtherMandiGroups = $allOtherMandiGroups ?? $mandiGroups->slice(2);
        @endphp

        @if($mandiGroups->isEmpty())
            <div class="bg-white rounded-3xl p-8 text-center border border-[#E8DFC8] shadow-2xs space-y-2">
                <div class="text-3xl">{{ $boardMeta ? $boardMeta['icon'] : '🌾' }}</div>
                <div class="font-extrabold text-stone-800 text-base font-kannada">
                    {{ $activeLocale === 'en' ? 'No mandi prices available for today' : 'ಈ ಬೆಳೆಗೆ ಇಂದಿನ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ' }}
                </div>
                <p class="text-xs text-stone-500 font-kannada">
                    @if($boardMeta)
                        {{ $activeLocale === 'en' ? 'Rates not yet published by official centres.' : 'ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ ' . $boardMeta['badge_kn'] . ' ಅಧಿಕೃತ ಕೇಂದ್ರಗಳಿಂದ ದರ ಮಾಹಿತಿ ಪ್ರಕಟವಾಗಿಲ್ಲ.' }}
                    @else
                        {{ $activeLocale === 'en' ? 'No APMC market has reported prices for this date.' : 'ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ ಯಾವುದೇ APMC ಮಾರುಕಟ್ಟೆಯಿಂದ ದರ ಮಾಹಿತಿ ಬಂದಿಲ್ಲ.' }}
                    @endif
                </p>
                <div class="pt-2">
                    <a href="{{ route('farmer.crops.index') }}" class="px-4 py-2 text-xs font-bold text-white bg-[#1C5A2C] rounded-xl hover:bg-[#154622] transition font-sans">
                        {{ $activeLocale === 'en' ? 'View Other Crops' : 'ಇತರ ಬೆಳೆಗಳನ್ನು ವೀಕ್ಷಿಸಿ' }}
                    </a>
                </div>
            </div>
        @else
            <!-- 2 APMCs Near to Current User Location (Ranked by Best Price) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($nearestTwoGroups as $index => $group)
                    @include('farmer.crops.partials.mandi-card', [
                        'group' => $group,
                        'rank' => $index + 1,
                        'isTopNearest' => ($index === 0),
                        'isNearestCard' => true,
                        'crop' => $crop,
                        'boardMeta' => $boardMeta,
                        'activeLocale' => $activeLocale,
                    ])
                @endforeach
            </div>

            <!-- Optional View All Other Mandis in Karnataka -->
            @if($allOtherMandiGroups->isNotEmpty())
                <div class="pt-2 text-center">
                    <button type="button" 
                            @click="showAllMandis = !showAllMandis"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white border border-[#E8DFC8] text-stone-800 hover:text-emerald-900 font-bold text-xs shadow-2xs hover:bg-stone-50 transition active:scale-95 cursor-pointer font-sans">
                        <span x-text="showAllMandis ? '▲' : '▼'"></span>
                        <span x-text="showAllMandis 
                            ? '{{ $activeLocale === 'en' ? 'Show 2 Nearest Mandis Only' : 'ಕೇವಲ ಹತ್ತಿರದ 2 ಮಂಡಿಗಳನ್ನು ತೋರಿಸಿ' }}' 
                            : '{{ $activeLocale === 'en' ? 'View All Other ' . $allOtherMandiGroups->count() . ' Mandis in Karnataka' : 'ಕರ್ನಾಟಕದ ಉಳಿದ ' . $allOtherMandiGroups->count() . ' ಮಂಡಿಗಳನ್ನು ವೀಕ್ಷಿಸಿ' }}'">
                        </span>
                    </button>

                    <div x-show="showAllMandis" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="space-y-3 pt-4 mt-4 border-t border-dashed border-stone-200 text-left">
                        <div class="flex items-center justify-between text-xs font-bold text-stone-500 font-sans px-1">
                            <span>🏛️ {{ $activeLocale === 'en' ? 'All Other Karnataka Mandis (Ranked by Best Price):' : 'ಕರ್ನಾಟಕದ ಇತರ ಎಲ್ಲಾ ಮಂಡಿಗಳು (ಅತ್ಯಧಿಕ ದರದಿಂದ ಇಳಿಕೆ ಕ್ರಮದಲ್ಲಿ):' }}</span>
                            <span class="text-stone-400 text-[11px]">{{ $allOtherMandiGroups->count() }} {{ $activeLocale === 'en' ? 'Mandis' : 'ಮಂಡಿಗಳು' }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($allOtherMandiGroups as $otherIndex => $group)
                                @include('farmer.crops.partials.mandi-card', [
                                    'group' => $group,
                                    'rank' => $otherIndex + 3,
                                    'isTopNearest' => false,
                                    'isNearestCard' => false,
                                    'crop' => $crop,
                                    'boardMeta' => $boardMeta,
                                    'activeLocale' => $activeLocale,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- 6. Historical Analytics & Interactive Price Trends -->
    @php
        $sumArrivals = !empty($dailyTrends['arrivals']) ? array_sum(array_filter($dailyTrends['arrivals'], fn($v) => is_numeric($v) && $v > 0)) : 0;
        $trendDir = $statisticalSummary['trend_direction'] ?? 'stable';
        $changePct = (float) ($statisticalSummary['price_change_percent'] ?? 0);
        $firstPrice = (float) ($statisticalSummary['first_price'] ?? 0);
        $lastPrice = (float) ($statisticalSummary['last_price'] ?? 0);
        $avgPrice = (float) ($statisticalSummary['avg_price'] ?? 0);
        $minPrice = (float) ($statisticalSummary['min_price'] ?? 0);
        $maxPrice = (float) ($statisticalSummary['max_price'] ?? 0);
        $priceSpread = max(0, $maxPrice - $minPrice);
        $volRating = $statisticalSummary['volatility_rating'] ?? 'ಕಡಿಮೆ (Low)';
        $volColor = $statisticalSummary['volatility_color'] ?? 'emerald';
        $volPercent = $statisticalSummary['volatility_percent'] ?? 0;
    @endphp

    <div class="bg-white border border-[#E8DFC8] rounded-3xl p-5 sm:p-7 shadow-sm space-y-5 relative overflow-hidden">
        <!-- Subtle Ambient Glow -->
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-emerald-100/30 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Section Header Row -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 relative z-10">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 flex items-center justify-center text-base shadow-2xs">
                        📈
                    </div>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        @if($activeLocale === 'en')
                            Historical Price Trend
                        @else
                            ಬೆಲೆ ಇತಿಹಾಸ & ಪ್ರವೃತ್ತಿ
                        @endif
                    </h2>

                    <!-- Dynamic Trend Momentum Pill -->
                    @if($trendDir === 'up')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/90 shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>▲ +{{ abs($changePct) }}% {{ $activeLocale === 'en' ? 'Rising Trend' : 'ಏರಿಕೆಯ ಪ್ರವೃತ್ತಿ' }}</span>
                        </span>
                    @elseif($trendDir === 'down')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200/90 shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                            <span>▼ -{{ abs($changePct) }}% {{ $activeLocale === 'en' ? 'Falling Trend' : 'ಇಳಿಕೆಯ ಪ್ರವೃತ್ತಿ' }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-stone-100 text-stone-700 border border-stone-200/80 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span class="w-2 h-2 rounded-full bg-stone-400"></span>
                            <span>⟷ {{ $activeLocale === 'en' ? 'Stable Trend' : 'ಸ್ಥಿರ ಪ್ರವೃತ್ತಿ' }}</span>
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2 mt-1.5 text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex-wrap">
                    @if($selectedMarket)
                        <span class="inline-flex items-center gap-1 font-semibold text-stone-800 bg-stone-100/80 px-2 py-0.5 rounded-md border border-stone-200/60 font-sans">
                            🏛️ {{ $displayMarketName }}{{ $boardMeta ? '' : (str_ends_with(strtolower($displayMarketName), 'apmc') ? '' : ' APMC') }}
                        </span>
                        <span>{{ $activeLocale === 'en' ? "— {$rangeDays} days price and market arrival report" : "ಯ {$rangeDays} ದಿನಗಳ ದರ ಮತ್ತು ಮಾರುಕಟ್ಟೆ ಆವಕ ವರದಿ" }}</span>
                    @else
                        <span class="inline-flex items-center gap-1 font-semibold text-stone-800 bg-stone-100/80 px-2 py-0.5 rounded-md border border-stone-200/60 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            🌐 {{ $activeLocale === 'en' ? ($boardMeta ? 'Karnataka Board Average' : 'Karnataka State Benchmark') : ('ಕರ್ನಾಟಕ ' . ($boardMeta ? 'ಮಂಡಳಿ' : 'ರಾಜ್ಯ') . ' ಸರಾಸರಿ') }}
                        </span>
                        <span>{{ $activeLocale === 'en' ? "— {$rangeDays} days price and arrival report" : "ಯ {$rangeDays} ದಿನಗಳ ದರ ಮತ್ತು ಆವಕ ವರದಿ" }}</span>
                    @endif
                </div>
            </div>

            <!-- Timeframe Filter Chips with Negilu Krushi styling -->
            <div class="flex items-center gap-1 bg-stone-100/90 p-1.5 rounded-2xl border border-stone-200/70 self-start lg:self-auto font-sans shadow-2xs overflow-x-auto no-scrollbar">
                @php
                    $ranges = [
                        '7d' => ['kn' => '7 ದಿನ', 'en' => '7 Days (7D)'],
                        '15d' => ['kn' => '15 ದಿನ', 'en' => '15 Days (15D)'],
                        '30d' => ['kn' => '30 ದಿನ', 'en' => '30 Days (30D)'],
                        '90d' => ['kn' => '3 ತಿಂಗಳು', 'en' => '3 Months (90D)'],
                        '365d' => ['kn' => '1 ವರ್ಷ', 'en' => '1 Year (1Y)'],
                    ];
                @endphp
                @foreach($ranges as $rKey => $rMeta)
                    @php $isActiveRange = ($rangeParam === $rKey); @endphp
                    <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $varietyId, 'market' => $marketParam, 'range' => $rKey])) }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1 text-xs whitespace-nowrap {{ $isActiveRange ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'text-stone-600 hover:text-stone-900 hover:bg-white/80 font-bold' }}">
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} font-bold">{{ $activeLocale === 'en' ? $rMeta['en'] : $rMeta['kn'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- 4 Key Statistical Metric Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
            <!-- 1. Period High -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-50/70 via-white to-emerald-50/20 border border-emerald-200/80 shadow-2xs relative overflow-hidden group hover:border-emerald-300 transition">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-[10.5px] font-black uppercase tracking-wider text-emerald-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                        <span>▲</span>
                        <span>{{ $activeLocale === 'en' ? 'Period High' : 'ಅವಧಿಯ ಗರಿಷ್ಠ' }}</span>
                    </span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500/60"></span>
                </div>
                <div class="flex items-baseline gap-1 mt-1 font-sans">
                    <span class="text-xl sm:text-2xl font-black text-emerald-950 font-sans tracking-tight">
                        {{ $maxPrice > 0 ? '₹' . number_format($maxPrice, 0) : '—' }}
                    </span>
                    @if($maxPrice > 0)
                        <span class="text-[11px] font-bold text-emerald-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂಟಾಲ್' }}</span>
                    @endif
                </div>
                @if($maxPrice > 0 && $avgPrice > 0)
                    <div class="text-[11px] font-bold text-emerald-700 mt-1.5 flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span>+₹{{ number_format($maxPrice - $avgPrice, 0) }}</span>
                        <span class="text-stone-400 font-normal">{{ $activeLocale === 'en' ? 'above average' : 'ಸರಾಸರಿಗಿಂತ ಹೆಚ್ಚು' }}</span>
                    </div>
                @else
                    <div class="text-[11px] text-stone-400 mt-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Highest recorded rate' : 'ಅತ್ಯಧಿಕ ದಾಖಲಾದ ದರ' }}</div>
                @endif
            </div>

            <!-- 2. Period Low -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-50/60 via-white to-orange-50/20 border border-amber-200/70 shadow-2xs relative overflow-hidden group hover:border-amber-300 transition">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-[10.5px] font-black uppercase tracking-wider text-amber-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                        <span>▼</span>
                        <span>{{ $activeLocale === 'en' ? 'Period Low' : 'ಅವಧಿಯ ಕನಿಷ್ಠ' }}</span>
                    </span>
                    <span class="w-2 h-2 rounded-full bg-amber-500/60"></span>
                </div>
                <div class="flex items-baseline gap-1 mt-1 font-sans">
                    <span class="text-xl sm:text-2xl font-black text-stone-900 font-sans tracking-tight">
                        {{ $minPrice > 0 ? '₹' . number_format($minPrice, 0) : '—' }}
                    </span>
                    @if($minPrice > 0)
                        <span class="text-[11px] font-bold text-amber-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂಟಾಲ್' }}</span>
                    @endif
                </div>
                @if($minPrice > 0 && $avgPrice > 0)
                    <div class="text-[11px] font-bold text-amber-800 mt-1.5 flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span>-₹{{ number_format($avgPrice - $minPrice, 0) }}</span>
                        <span class="text-stone-400 font-normal">{{ $activeLocale === 'en' ? 'below average' : 'ಸರಾಸರಿಗಿಂತ ಕಡಿಮೆ' }}</span>
                    </div>
                @else
                    <div class="text-[11px] text-stone-400 mt-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Lowest recorded rate' : 'ಕನಿಷ್ಠ ದಾಖಲಾದ ದರ' }}</div>
                @endif
            </div>

            <!-- 3. Period Average -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50/50 via-white to-slate-50 border border-blue-200/70 shadow-2xs relative overflow-hidden group hover:border-blue-300 transition">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-[10.5px] font-black uppercase tracking-wider text-blue-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                        <span>⚖️</span>
                        <span>{{ $activeLocale === 'en' ? 'Period Average' : 'ಅವಧಿಯ ಸರಾಸರಿ' }}</span>
                    </span>
                    <span class="w-2 h-2 rounded-full bg-blue-500/60"></span>
                </div>
                <div class="flex items-baseline gap-1 mt-1 font-sans">
                    <span class="text-xl sm:text-2xl font-black text-stone-900 font-sans tracking-tight">
                        {{ $avgPrice > 0 ? '₹' . number_format($avgPrice, 0) : '—' }}
                    </span>
                    @if($avgPrice > 0)
                        <span class="text-[11px] font-bold text-blue-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂಟಾಲ್' }}</span>
                    @endif
                </div>
                <div class="text-[11px] font-bold text-blue-900 mt-1.5 flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>{{ $statisticalSummary['observations_count'] ?? count($dailyTrends['labels'] ?? []) }} {{ $activeLocale === 'en' ? 'days recorded' : 'ದಿನಗಳ ದಾಖಲೆ' }}</span>
                    <span class="text-stone-400 font-normal">{{ $activeLocale === 'en' ? 'benchmark' : 'ಮೌಲ್ಯಾಂಕನ' }}</span>
                </div>
            </div>

            <!-- 4. Volatility & Spread -->
            @php
                $volBgClass = match($volColor) {
                    'rose' => 'from-rose-50/60 via-white to-red-50/20 border-rose-200/70 text-rose-800',
                    'amber' => 'from-amber-50/60 via-white to-yellow-50/20 border-amber-200/70 text-amber-800',
                    default => 'from-emerald-50/50 via-white to-teal-50/20 border-emerald-200/70 text-emerald-800',
                };
                $dotColorClass = match($volColor) {
                    'rose' => 'bg-rose-500',
                    'amber' => 'bg-amber-500',
                    default => 'bg-emerald-500',
                };
                $displayVolRating = $activeLocale === 'en' 
                    ? ($statisticalSummary['volatility_rating_en'] ?? 'Stable / Low Volatility') 
                    : ($statisticalSummary['volatility_rating_kn'] ?? $volRating);
            @endphp
            <div class="p-4 rounded-2xl bg-gradient-to-br {{ $volBgClass }} border shadow-2xs relative overflow-hidden group transition">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-[10.5px] font-black uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full {{ $dotColorClass }}"></span>
                        <span>{{ $activeLocale === 'en' ? 'Price Volatility' : 'ಬೆಲೆ ಏರಿಳಿತ' }}</span>
                    </span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-base sm:text-lg font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} tracking-tight line-clamp-1">
                        {{ $displayVolRating }}
                    </span>
                </div>
                <div class="text-[11px] font-bold text-stone-600 mt-1.5 flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>{{ $activeLocale === 'en' ? 'Spread: ' : 'ವ್ಯತ್ಯಾಸ: ' }}<strong>₹{{ number_format($priceSpread, 0) }}</strong></span>
                    <span class="text-stone-400 font-sans">({{ $volPercent }}%)</span>
                </div>
            </div>
        </div>

        <!-- Actionable Farmer Market Intelligence Callout Strip -->
        @if(!empty($dailyTrends['has_data']))
            <div class="bg-gradient-to-r from-[#FBF8EF] to-emerald-50/40 border border-[#E8DFC8] rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <div class="flex items-start gap-2.5">
                    <span class="text-xl shrink-0 mt-0.5">💡</span>
                    <div class="text-xs text-stone-700 leading-relaxed">
                        <span class="font-black text-stone-900">{{ $activeLocale === 'en' ? 'Farmer Insight:' : 'ದರ ಪ್ರವೃತ್ತಿ ಒಳನೋಟ:' }}</span>
                        @if($activeLocale === 'en')
                            @if($trendDir === 'up')
                                Over the last {{ $rangeDays }} days, modal rates rose from <strong>₹{{ number_format($firstPrice) }}</strong> to <strong>₹{{ number_format($lastPrice) }}</strong> <strong>(+{{ abs($changePct) }}%)</strong>. Market demand remains strong with favorable selling momentum.
                            @elseif($trendDir === 'down')
                                Over the last {{ $rangeDays }} days, modal rates softened from <strong>₹{{ number_format($firstPrice) }}</strong> to <strong>₹{{ number_format($lastPrice) }}</strong> <strong>(-{{ abs($changePct) }}%)</strong>. Local arrivals may be elevated; check the price forecast before committing volume.
                            @else
                                Over the last {{ $rangeDays }} days, prices held steady with an average of <strong>₹{{ number_format($avgPrice) }}/{{ strtolower($crop->standard_unit ?? 'quintal') }}</strong>. The trading spread between high and low is <strong>₹{{ number_format($priceSpread) }}</strong>.
                            @endif
                        @else
                            @if($trendDir === 'up')
                                ಕಳೆದ {{ $rangeDays }} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹{{ number_format($firstPrice) }}</strong> ರಿಂದ <strong>₹{{ number_format($lastPrice) }}</strong> ಕ್ಕೆ <strong>(+{{ abs($changePct) }}%) ಏರಿಕೆಯಾಗಿದೆ</strong>. ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಉತ್ತಮವಾಗಿದ್ದು ಮಾರಾಟಕ್ಕೆ ಅನುಕೂಲಕರ ಪ್ರವೃತ್ತಿಯಿದೆ.
                            @elseif($trendDir === 'down')
                                ಕಳೆದ {{ $rangeDays }} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹{{ number_format($firstPrice) }}</strong> ರಿಂದ <strong>₹{{ number_format($lastPrice) }}</strong> ಕ್ಕೆ <strong>(-{{ abs($changePct) }}%) ಇಳಿಕೆಯಾಗಿದೆ</strong>. ಸ್ಥಳೀಯ ಆವಕ ಹೆಚ್ಚಾಗಿರಬಹುದು, ಬೆಲೆ ಮುನ್ಸೂಚನೆ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧರಿಸಿ.
                            @else
                                ಕಳೆದ {{ $rangeDays }} ದಿನಗಳಲ್ಲಿ ದರವು ಸರಾಸರಿ <strong>₹{{ number_format($avgPrice) }}/ಕ್ವಿಂಟಾಲ್</strong> ನೊಂದಿಗೆ ಸ್ಥಿರವಾಗಿದೆ. ಗರಿಷ್ಠ ಮತ್ತು ಕನಿಷ್ಠ ದರದ ಅಂತರ <strong>₹{{ number_format($priceSpread) }}</strong> ಆಗಿದೆ.
                            @endif
                        @endif
                    </div>
                </div>
                @if($sumArrivals > 0)
                    <div class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#E8DFC8] text-[11px] font-bold text-stone-700 self-start md:self-auto shadow-2xs font-sans">
                        <span class="text-emerald-700">📦</span>
                        <span>{{ $activeLocale === 'en' ? 'Total Arrivals: ' : 'ಒಟ್ಟು ಆವಕ: ' }}<strong>{{ number_format($sumArrivals) }} {{ $activeLocale === 'en' ? 'Quintals' : 'ಕ್ವಿಂಟಾಲ್' }}</strong></span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Chart Canvas Container with Modern Toolbar & Framing -->
        <div class="rounded-2xl bg-stone-50/70 border border-stone-200/70 p-3 sm:p-4 space-y-3">
            <!-- Toolbar above Chart -->
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs px-1">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-1.5 text-stone-800 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="w-3.5 h-1.5 rounded-full bg-emerald-600 inline-block"></span>
                        @if($activeLocale === 'en')
                            <span>Modal Rate (₹)</span>
                        @else
                            <span>ಮಾದರಿ ದರ (₹)</span>
                        @endif
                    </div>
                    @if($sumArrivals > 0)
                        <div class="flex items-center gap-1.5 text-stone-600 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span class="w-3 h-3 rounded-xs bg-slate-300 inline-block"></span>
                            @if($activeLocale === 'en')
                                <span>Daily Arrivals (Qtl)</span>
                            @else
                                <span>ದೈನಂದಿನ ಆವಕ (ಕ್ವಿಂಟಾಲ್)</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="text-[11px] text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                    <span>👆 {{ $activeLocale === 'en' ? 'Touch or hover on graph for details' : 'ಗ್ರಾಫ್ ಮೇಲೆ ಸ್ಪರ್ಶಿಸಿ ವಿವರ ನೋಡಿ' }}</span>
                </div>
            </div>

            <!-- Canvas Wrapper -->
            <div class="relative w-full h-72 sm:h-84 md:h-96 bg-white rounded-xl p-2 sm:p-3 border border-stone-100 shadow-2xs">
                @if(!empty($dailyTrends['has_data']))
                    <canvas id="priceTrendCanvas"></canvas>
                @else
                    <div class="h-full flex flex-col items-center justify-center text-center p-6 text-stone-400">
                        <span class="text-4xl mb-2">📊</span>
                        <span class="font-bold text-stone-700 text-sm {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Insufficient price history recorded for this period' : 'ಈ ಅವಧಿಗೆ ಸಾಕಷ್ಟು ದರ ಇತಿಹಾಸ ದಾಖಲಾಗಿಲ್ಲ' }}
                        </span>
                        <span class="text-xs text-stone-400 mt-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} max-w-sm">
                            {{ $activeLocale === 'en' ? 'As more trading days are recorded by mandis, trend and arrival charts will activate automatically.' : 'ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಹೆಚ್ಚಿನ ದಿನಗಳ ವಹಿವಾಟು ದಾಖಲಾದಂತೆ ಪ್ರವೃತ್ತಿ ಮತ್ತು ಆವಕ ನಕ್ಷೆ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.' }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Chart Footer Meta -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-stone-400 px-1 pt-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} border-t border-stone-200/50">
                <div class="flex items-center gap-1.5">
                    <span>{{ $activeLocale === 'en' ? '🟢 Green line: Modal rate (₹/Qtl)' : '🟢 ಹಸಿರು ಗೆರೆ: ದರ (₹/ಕ್ವಿಂಟಾಲ್)' }}</span>
                    @if($sumArrivals > 0)
                        <span>&bull;</span>
                        <span>{{ $activeLocale === 'en' ? '🩶 Grey bar: Daily arrival volume' : '🩶 ಬೂದು ಬಾರ್: ಮಾರುಕಟ್ಟೆ ಆವಕ ಪ್ರಮಾಣ' }}</span>
                    @endif
                </div>
                <div class="text-stone-400 font-sans text-[10.5px]">
                    {{ $activeLocale === 'en' ? 'Source: APMC / Agmarknet Karnataka' : 'ಮೂಲ: ಎಪಿಎಂಸಿ / Agmarknet Karnataka' }}
                </div>
            </div>
        </div>
    </div>

    <!-- 7. Best Months to Sell — Krushi Harvest Calendar (Original Design) -->
    @if(!empty($seasonalAnalysis['is_sufficient']) && !empty($seasonalAnalysis['best_months']))
    <style>
        /* ── Krushi Harvest Calendar ── */
        .khc-card{background:linear-gradient(148deg,#081A0F 0%,#0F2A1A 38%,#1A4428 65%,#0D2318 100%);border-radius:24px;padding:20px 20px 18px;position:relative;overflow:hidden;box-shadow:0 16px 56px rgba(0,0,0,0.32),inset 0 1px 0 rgba(255,255,255,0.07);}
        .khc-card::after{content:'';position:absolute;inset:0;border-radius:24px;border:1px solid rgba(255,255,255,0.09);pointer-events:none;}
        /* Ambient glows */
        .khc-glow{position:absolute;border-radius:50%;filter:blur(48px);pointer-events:none;}
        /* Header */
        .khc-eyebrow{color:#86EFAC;font-size:9.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:3px;display:flex;align-items:center;gap:5px;}
        .khc-title{color:#FFFFFF;font-size:20px;font-weight:900;line-height:1.1;}
        .khc-badge-5y{background:rgba(255,255,255,0.09);border:1px solid rgba(255,255,255,0.14);border-radius:20px;padding:4px 12px;color:rgba(255,255,255,0.6);font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0;}
        /* Lead text */
        .khc-lead{color:rgba(255,255,255,0.72);font-size:13px;font-weight:600;line-height:1.65;margin:10px 0 16px;}
        .khc-lead strong{color:#FDE68A;font-weight:900;}
        /* Peak month chip badges */
        .khc-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;}
        .khc-chip{background:linear-gradient(135deg,#7C2D12,#C2410C,#F59E0B);border-radius:14px;padding:5px 13px 5px 8px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 3px 14px rgba(245,158,11,0.28);animation:chipPulse 3.5s ease-in-out infinite;}
        @@keyframes chipPulse{0%,100%{transform:scale(1);box-shadow:0 3px 14px rgba(245,158,11,0.28);}50%{transform:scale(1.03);box-shadow:0 5px 22px rgba(245,158,11,0.48);}}
        /* Bar chart */
        .khc-bars{display:flex;align-items:flex-end;gap:4px;height:130px;position:relative;}
        /* Column */
        .khc-col{flex:1;display:flex;flex-direction:column;align-items:center;height:100%;position:relative;cursor:pointer;}
        .khc-col:focus{outline:none;}
        /* Track */
        .khc-track{flex:1;width:100%;background:rgba(255,255,255,0.06);border-radius:6px 6px 0 0;overflow:hidden;display:flex;align-items:flex-end;position:relative;transition:background .2s;}
        .khc-col:hover .khc-track{background:rgba(255,255,255,0.11);}
        /* Fill */
        .khc-fill{width:100%;border-radius:6px 6px 0 0;height:0%;transition:height .85s cubic-bezier(.34,1.4,.64,1);box-sizing:border-box;}
        .khc-fill.pk{background:linear-gradient(180deg,#FDE68A 0%,#F59E0B 40%,#B45309 100%);}
        .khc-fill.mid{background:linear-gradient(180deg,rgba(110,231,183,.75) 0%,rgba(16,185,129,.5) 100%);}
        .khc-fill.lo{
            background:linear-gradient(180deg,rgba(239,68,68,0.35) 0%,rgba(185,28,28,0.18) 100%);
            border-top:2.5px solid #EF4444;
            border-left:1.5px solid rgba(239,68,68,0.65);
            border-right:1.5px solid rgba(239,68,68,0.65);
            box-shadow:0 -2px 10px rgba(239,68,68,0.35);
        }
        .khc-fill.pk.khc-loaded{box-shadow:0 -8px 24px rgba(245,158,11,.55);animation:pkShine 2.8s ease-in-out 0s infinite;}
        .khc-fill.lo.khc-loaded{animation:loPulse 3s ease-in-out infinite;}
        @@keyframes pkShine{0%,100%{box-shadow:0 -8px 24px rgba(245,158,11,.55);}50%{box-shadow:0 -14px 36px rgba(245,158,11,.85);}}
        @@keyframes loPulse{0%,100%{border-top-color:#EF4444;box-shadow:0 -2px 8px rgba(239,68,68,0.3);}50%{border-top-color:#F87171;box-shadow:0 -5px 16px rgba(239,68,68,0.65);}}
        /* Inline price on peak bar */
        .khc-price-inline{position:absolute;width:100%;bottom:4px;text-align:center;color:rgba(255,255,255,.9);font-size:7px;font-weight:900;line-height:1;opacity:0;transition:opacity .4s .95s;pointer-events:none;}
        .khc-price-inline.khc-loaded{opacity:1;}
        /* Month label */
        .khc-lbl{font-size:9px;margin-top:5px;font-weight:700;color:rgba(255,255,255,.35);text-align:center;transition:color .2s;white-space:nowrap;}
        .khc-lbl.pk{color:#FDE68A;font-weight:900;}
        .khc-lbl.lo{color:#FCA5A5;font-weight:800;}
        .khc-col:hover .khc-lbl:not(.pk):not(.lo){color:rgba(255,255,255,.72);}
        /* Crown above peak */
        .khc-crown{position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:13px;line-height:1;opacity:0;transition:opacity .5s 1.1s;}
        .khc-crown.khc-loaded{opacity:1;}
        /* Tooltip */
        .khc-tip{position:absolute;bottom:calc(100% + 10px);left:50%;transform:translateX(-50%);opacity:0;pointer-events:none;z-index:50;transition:opacity .15s,transform .15s;transform-origin:bottom center;white-space:nowrap;background:rgba(4,4,4,.94);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);color:#fff;border-radius:10px;padding:7px 12px;font-size:11px;display:flex;flex-direction:column;align-items:center;gap:2px;box-shadow:0 6px 24px rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.1);}
        .khc-col:hover .khc-tip,.khc-col:focus .khc-tip{opacity:1;transform:translateX(-50%) translateY(-2px);}
        .khc-tip-arrow{width:7px;height:7px;background:rgba(4,4,4,.94);transform:rotate(45deg);margin-top:3px;align-self:center;flex-shrink:0;}
        /* Bottom divider rule */
        .khc-rule{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent);margin:14px 0 0;}
        /* Footer */
        .khc-foot{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:12px;}
        .khc-avg{color:rgba(255,255,255,.48);font-size:10px;font-weight:700;}
        .khc-avg strong{color:#86EFAC;font-size:11px;}
        .khc-legend{display:flex;align-items:center;gap:10px;}
        .khc-dot{width:8px;height:8px;border-radius:2px;flex-shrink:0;}
        .khc-leg-txt{font-size:9.5px;font-weight:700;}
    </style>

    <div class="khc-card">
        {{-- Ambient radial orbs --}}
        <div class="khc-glow" style="top:-90px;right:-70px;width:220px;height:220px;background:radial-gradient(circle,rgba(46,139,78,.2) 0%,transparent 70%);"></div>
        <div class="khc-glow" style="bottom:-70px;left:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(245,158,11,.09) 0%,transparent 70%);"></div>

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
            <div>
                <div class="khc-eyebrow {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span style="display:inline-block;width:6px;height:6px;background:#86EFAC;border-radius:50%;flex-shrink:0;"></span>
                    {{ $activeLocale === 'en' ? 'HISTORICAL SEASONAL ANALYSIS' : 'ಐತಿಹಾಸಿಕ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆ' }}
                </div>
                <div class="khc-title {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Best Months to Sell' : 'ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು' }}
                </div>
            </div>
            <div class="khc-badge-5y {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="margin-top:2px;">
                @if(($seasonalAnalysis['distinct_months'] ?? 0) >= 12)
                    {{ $activeLocale === 'en' ? 'Last 5 Years' : 'ಕಳೆದ 5 ವರ್ಷ' }}
                @else
                    {{ $seasonalAnalysis['distinct_months'] ?? 2 }} {{ $activeLocale === 'en' ? 'Months Recorded' : 'ತಿಂಗಳ ಮಂಡಿ ದಾಖಲೆ' }}
                @endif
            </div>
        </div>

        {{-- Lead text --}}
        <div class="khc-lead {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            @if($activeLocale === 'en')
                @if(!empty($seasonalAnalysis['peak_months_en']))
                    Prices are usually highest around <strong>{{ implode(', ', $seasonalAnalysis['peak_months_en']) }}</strong> — plan your harvest and sale for those months.
                @else
                    {{ $seasonalAnalysis['lead_summary_en'] ?? 'Seasonal price variations based on historical mandi arrivals.' }}
                @endif
            @else
                @if(!empty($seasonalAnalysis['peak_months_kn']))
                    ಸಾಮಾನ್ಯವಾಗಿ <strong>{{ implode(', ', $seasonalAnalysis['peak_months_kn']) }}</strong> ತಿಂಗಳಲ್ಲಿ ಬೆಲೆ ಹೆಚ್ಚು — ಆ ಸಮಯಕ್ಕೆ ಬೆಳೆ ಮಾರಲು ಸಿದ್ಧರಾಗಿ.
                @else
                    {{ $seasonalAnalysis['lead_summary_kn'] ?? 'ಮಾರುಕಟ್ಟೆ ಇತಿಹಾಸ ಆಧಾರದ ಮೇಲೆ ಬೆಲೆ ವ್ಯತ್ಯಾಸ ತೋರಿಸಲಾಗಿದೆ.' }}
                @endif
            @endif
        </div>

        {{-- Peak month chips --}}
        @php
            $peakMonths = collect($seasonalAnalysis['monthly_profile'])
                ->filter(fn($m) => !empty($m['is_peak']) || ($m['tier'] ?? '') === 'pk')
                ->values();
        @endphp
        @if($peakMonths->isNotEmpty())
            <div class="khc-chips">
                @foreach($peakMonths as $pm)
                    <div class="khc-chip {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span style="font-size:15px;line-height:1;">⭐</span>
                        <div style="line-height:1.25;">
                            <div style="color:#fff;font-size:12px;font-weight:900;">
                                {{ $activeLocale === 'en' ? ($pm['name_en'] ?? $pm['short_name_en']) : ($pm['short_name_kn'] ?? $pm['name_kn']) }}
                            </div>
                            @if(($pm['avg_price'] ?? 0) > 0)
                                <div style="color:rgba(255,255,255,.82);font-size:9.5px;font-weight:700;">₹{{ number_format($pm['avg_price'],0) }}/{{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Bar chart --}}
        <div class="khc-bars season-bars-container" role="img" aria-label="{{ $activeLocale === 'en' ? 'Monthly Seasonal Price Trend' : 'ತಿಂಗಳವಾರ ಬೆಲೆ ಋತುಮಾನ ಗ್ರಾಫ್' }}">
            @foreach($seasonalAnalysis['monthly_profile'] as $m)
                @php
                    $tier  = $m['tier'] ?? 'mid';
                    $isPeak = $tier === 'pk' || !empty($m['is_peak']);
                    $hasData = ($m['observations'] ?? 0) > 0 && ($m['avg_price'] ?? 0) > 0;
                    $hPct  = $hasData ? max(7, (int)($m['bar_height_percent'] ?? 0)) : 0;
                    $idxPct = $m['index_percentage'] ?? 0;
                    $mName = $activeLocale === 'en' ? $m['name_en'] : $m['name_kn'];
                    $mShort = $activeLocale === 'en' ? $m['short_name_en'] : ($m['short_name_kn'] ?? $m['name_kn']);
                @endphp
                <div class="khc-col {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                     tabindex="0"
                     role="button"
                     aria-label="{{ $mName }}{{ $hasData ? ': ₹'.number_format($m['avg_price'],0) : '' }}">

                    {{-- Tooltip --}}
                    <div class="khc-tip">
                        <span style="font-weight:900;color:#FDE68A;font-size:12px;">{{ $mName }}</span>
                        @if($hasData)
                            <span style="font-weight:700;font-size:11.5px;">₹{{ number_format($m['avg_price'],0) }}<span style="font-size:9px;font-weight:600;opacity:.7;">/{{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</span></span>
                            <span style="font-size:9px;font-weight:700;color:{{ $idxPct >= 0 ? '#6EE7B7' : '#FCA5A5' }};">{{ $idxPct >= 0 ? '+' : '' }}{{ $idxPct }}% avg</span>
                            @if($tier === 'lo')
                                <span style="font-size:9px;font-weight:700;color:#FCA5A5;background:rgba(239,68,68,0.22);border:1px solid rgba(239,68,68,0.5);padding:1px 6px;border-radius:6px;margin-top:2px;">
                                    📉 {{ $activeLocale === 'en' ? 'Low Price Period' : 'ಕಡಿಮೆ ಬೆಲೆ ಅವಧಿ' }}
                                </span>
                            @elseif($isPeak)
                                <span style="font-size:9px;font-weight:700;color:#FDE68A;background:rgba(245,158,11,0.25);border:1px solid rgba(245,158,11,0.5);padding:1px 6px;border-radius:6px;margin-top:2px;">
                                    ⭐ {{ $activeLocale === 'en' ? 'Peak Selling Window' : 'ಅತ್ಯುತ್ತಮ ಧಾರಣೆ ಕಾಲ' }}
                                </span>
                            @endif
                        @else
                            <span style="font-size:10px;color:rgba(255,255,255,.4);">{{ $activeLocale === 'en' ? 'No Data' : 'ಮಾಹಿತಿ ಇಲ್ಲ' }}</span>
                        @endif
                        <div class="khc-tip-arrow"></div>
                    </div>

                    {{-- Crown (peak only) --}}
                    @if($isPeak && $hasData)
                        <div class="khc-crown" aria-hidden="true">🏆</div>
                    @endif

                    {{-- Track + Fill --}}
                    <div class="khc-track">
                        @if($hasData)
                            <div class="khc-fill {{ $tier }} season-bar-fill"
                                 data-target-height="{{ $hPct }}%"
                                 style="height:{{ $hPct }}%;"></div>
                            @if($isPeak)
                                <div class="khc-price-inline bms-price-label">
                                    ₹{{ number_format($m['avg_price'],0) }}
                                </div>
                            @endif
                        @else
                            <div style="width:100%;height:2px;background:rgba(255,255,255,.07);align-self:flex-end;"></div>
                        @endif
                    </div>

                    {{-- Month label --}}
                    <div class="khc-lbl {{ $isPeak ? 'pk' : ($tier === 'lo' ? 'lo' : '') }}">
                        {{ $mShort }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Baseline divider --}}
        <div class="khc-rule"></div>

        {{-- Footer --}}
        <div class="khc-foot">
            @if(!empty($seasonalAnalysis['annual_baseline']) && $seasonalAnalysis['annual_baseline'] > 0)
                <div class="khc-avg {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Annual Average: ' : 'ವಾರ್ಷಿಕ ಸರಾಸರಿ: ' }}<strong>₹{{ number_format($seasonalAnalysis['annual_baseline'],0) }}/{{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</strong>
                </div>
            @endif
            <div class="khc-legend">
                <span style="display:inline-flex;align-items:center;gap:4px;">
                    <span class="khc-dot" style="background:linear-gradient(135deg,#F59E0B,#B45309);"></span>
                    <span class="khc-leg-txt {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color:#FDE68A;">{{ $activeLocale === 'en' ? 'Peak Window' : 'ಉತ್ತಮ ಕಾಲ' }}</span>
                </span>
                <span style="display:inline-flex;align-items:center;gap:4px;">
                    <span class="khc-dot" style="background:rgba(110,231,183,.65);"></span>
                    <span class="khc-leg-txt {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color:rgba(255,255,255,.42);">{{ $activeLocale === 'en' ? 'Normal' : 'ಸಾಮಾನ್ಯ' }}</span>
                </span>
                <span style="display:inline-flex;align-items:center;gap:5px;">
                    <span class="khc-dot" style="background:rgba(239,68,68,0.25);border:1.5px solid #EF4444;box-shadow:0 0 6px rgba(239,68,68,0.45);"></span>
                    <span class="khc-leg-txt {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color:#FCA5A5;">{{ $activeLocale === 'en' ? 'Low Price Period' : 'ಕಡಿಮೆ ಬೆಲೆ' }}</span>
                </span>
            </div>
        </div>

        <div id="seasonalityCanvas" class="hidden" aria-hidden="true"></div>
    </div>

    @else
    {{-- Insufficient data: light header + amber notice --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-6 rounded-full bg-[#1C5A2C]"></span>
                <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Best Months to Sell' : 'ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು' }}
                </h2>
            </div>
            <div class="text-xs font-bold text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} bg-stone-100 px-3 py-1 rounded-full border border-stone-200/60">
                {{ $activeLocale === 'en' ? 'Last 5 Years' : 'ಕಳೆದ 5 ವರ್ಷ' }}
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-950 flex items-start gap-3">
            <span class="text-xl shrink-0">ℹ️</span>
            <div class="space-y-1 text-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <div class="font-bold text-sm text-amber-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Seasonal Data Insufficiency Notice' : 'ಋತುಮಾನ ಮಾಹಿತಿ ಕೊರತೆ ಸೂಚನೆ' }}
                </div>
                <p class="leading-relaxed">{{ $activeLocale === 'en' ? ($seasonalAnalysis['message_en'] ?? 'At least 2 distinct months of market price records are required for seasonal analysis.') : ($seasonalAnalysis['message_kn'] ?? 'ವಿಶ್ವಾಸಾರ್ಹ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆಗೆ ಕನಿಷ್ಠ 2 ಪ್ರತ್ಯೇಕ ತಿಂಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ.') }}</p>
                <p class="text-amber-800/80">{{ $activeLocale === 'en' ? 'Krushi Baandhava does not generate synthetic prices. Seasonal Selling Indices will activate once at least 2 distinct months of continuous mandi records are logged.' : 'ಕೃಷಿ ಬಾಂಧವ ಕೃತಕ ಅಂದಾಜುಗಳನ್ನು ಪ್ರದರ್ಶಿಸುವುದಿಲ್ಲ. ಮಂಡಿಗಳಿಂದ ಕನಿಷ್ಠ 2 ಪ್ರತ್ಯೇಕ ತಿಂಗಳುಗಳ ನಿರಂತರ ದರಗಳು ದಾಖಲಾದ ನಂತರ ನಿಖರ ಋತುಮಾನ ಸೂಚ್ಯಂಕ ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.' }}</p>
            </div>
        </div>
        <div id="seasonalityCanvas" class="hidden" aria-hidden="true"></div>
    </div>
    @endif



    <!-- 8. Agri Videos, Articles & Government Schemes -->
    @if($cropVideos->isNotEmpty() || $cropArticles->isNotEmpty())
        <div class="bg-white rounded-3xl border border-[#E8DFC8] p-5 sm:p-7 shadow-2xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-stone-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🎬</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? $crop->name . ' — Expert Videos & Agronomy Guides' : (($crop->name_kn ?: $crop->name) . ' — ತಜ್ಞರ ವಿಡಿಯೋ & ಬೇಸಾಯ ಮಾರ್ಗದರ್ಶಿ') }}
                        </h2>
                        <span class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Scientific farming practices and practical guidance' : 'ವೈಜ್ಞಾನಿಕ ಕೃಷಿ ಪದ್ಧತಿಗಳು ಮತ್ತು ಪ್ರಾಯೋಗಿಕ ಮಾಹಿತಿ' }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="text-xs font-bold text-emerald-800 hover:text-emerald-950 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'All Videos &rarr;' : 'ಎಲ್ಲಾ ವಿಡಿಯೋಗಳು &rarr;' }}
                    </a>
                </div>
            </div>

            <!-- Videos Row -->
            @if($cropVideos->isNotEmpty())
                <div>
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Training Videos' : 'ತರಬೇತಿ ವಿಡಿಯೋಗಳು' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach($cropVideos as $vid)
                            <div class="bg-stone-50 rounded-2xl border border-stone-200 overflow-hidden hover:border-emerald-500 hover:shadow-xs transition group">
                                <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="block relative aspect-video bg-stone-900">
                                    <img src="{{ $vid->thumbnail_url }}" alt="{{ $vid->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    <div class="absolute inset-0 bg-stone-950/20 flex items-center justify-center">
                                        <div class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow">
                                            <svg class="w-5 h-5 ml-0.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                    @if($vid->duration_text)
                                        <span class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded bg-black/80 text-white text-[9px] font-bold font-sans">
                                            {{ $vid->duration_text }}
                                        </span>
                                    @endif
                                </a>
                                <div class="p-3">
                                    <h4 class="text-xs font-bold text-stone-900 line-clamp-2 group-hover:text-emerald-700 transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                        {{ $activeLocale === 'en' ? $vid->title : ($vid->title_kn ?: $vid->title) }}
                                    </h4>
                                    <div class="text-[10px] text-stone-500 mt-1 flex items-center justify-between {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                        <span>{{ $vid->channel_name ?: ($activeLocale === 'en' ? 'Agri Info' : 'ಕೃಷಿ ಮಾಹಿತಿ') }}</span>
                                        <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="font-bold text-emerald-800 font-sans">{{ $activeLocale === 'en' ? 'Watch ▶' : 'ವೀಕ್ಷಿಸಿ ▶' }}</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Articles Row -->
            @if($cropArticles->isNotEmpty())
                <div class="pt-3 border-t border-stone-100">
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Agri Guides & Manuals' : 'ಬೇಸಾಯ ಲೇಖನಗಳು & ಕೈಪಿಡಿ' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($cropArticles as $art)
                            <a href="{{ route('farmer.articles.show', $art->slug) }}" class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200 hover:border-emerald-500 hover:shadow-xs transition block">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 mb-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $activeLocale === 'en' ? ($art->category_label_en ?? $art->category_label_kn) : $art->category_label_kn }}
                                </span>
                                <h4 class="text-xs font-bold text-stone-900 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $activeLocale === 'en' ? $art->title : ($art->title_kn ?: $art->title) }}
                                </h4>
                                <p class="text-[11px] text-stone-500 mt-1 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $activeLocale === 'en' ? $art->summary : ($art->summary_kn ?: $art->summary) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- 9. Government Schemes -->
    @if($cropSchemes->isNotEmpty())
        <div class="bg-gradient-to-br from-[#1C5A2C] to-teal-900 rounded-3xl p-5 sm:p-7 text-white shadow-md space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-emerald-700/60 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🏛️</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-white tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Agricultural Subsidies & Government Schemes' : 'ಕೃಷಿ ಸಬ್ಸಿಡಿ & ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು' }}
                        </h2>
                        <span class="text-xs text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Financial assistance and farm machinery subsidies available for farmers' : 'ರೈತರಿಗೆ ಲಭ್ಯವಿರುವ ಆರ್ಥಿಕ ನೆರವು & ಯಂತ್ರೋಪಕರಣ ಸಬ್ಸಿಡಿ' }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('farmer.schemes.index') }}" class="text-xs font-bold text-amber-300 hover:text-amber-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'All Schemes &rarr;' : 'ಎಲ್ಲಾ ಯೋಜನೆಗಳು &rarr;' }}
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($cropSchemes as $sch)
                    <div class="bg-emerald-950/50 backdrop-blur-xs border border-emerald-600/50 rounded-2xl p-4 flex flex-col justify-between hover:border-emerald-400 transition">
                        <div>
                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-700/70 text-emerald-100 mb-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? ($sch->category_label_en ?? $sch->category_label_kn) : $sch->category_label_kn }}
                            </span>
                            <h4 class="text-xs font-bold text-white line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? $sch->title : ($sch->title_kn ?: $sch->title) }}
                            </h4>
                            <p class="text-[11px] text-emerald-200/90 mt-1.5 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $sch->summary_kn ?: $sch->summary }}
                            </p>
                        </div>
                        <div class="mt-4 pt-2 border-t border-emerald-800/80 flex items-center justify-between text-xs">
                            <a href="{{ route('farmer.schemes.show', $sch->slug) }}" class="font-bold text-amber-300 hover:text-amber-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? 'Scheme Details &rarr;' : 'ಅರ್ಜಿ ವಿವರ &rarr;' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(!empty($dailyTrends['has_data']))
            const trendData = {
                labels: @json($dailyTrends['labels']),
                modalPrices: @json($dailyTrends['modal_prices']),
                minPrices: @json($dailyTrends['min_prices']),
                maxPrices: @json($dailyTrends['max_prices']),
                arrivals: @json($dailyTrends['arrivals']),
                locale: '{{ $activeLocale }}',
            };
            let attempts = 0;
            const initTrend = () => {
                if (typeof window.initPriceTrendChart === 'function') {
                    window.initPriceTrendChart('priceTrendCanvas', trendData);
                } else if (attempts < 10) {
                    attempts++;
                    setTimeout(initTrend, 100);
                }
            };
            initTrend();
        @endif

        @if(!empty($seasonalAnalysis['is_sufficient']) && !empty($seasonalAnalysis['best_months']))
            const barFills = document.querySelectorAll('.season-bar-fill');
            if (barFills.length > 0) {
                const animateBars = () => {
                    // Double rAF forces browser to paint height:0 before transitioning
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            barFills.forEach((bar, idx) => {
                                const targetHeight = bar.getAttribute('data-target-height') || '0%';
                                setTimeout(() => {
                                    bar.style.height = targetHeight;
                                    // After spring-bounce settles (~900ms), activate peak glow + crown + price
                                    if (bar.classList.contains('pk')) {
                                        setTimeout(() => {
                                            bar.classList.add('khc-loaded');
                                            const col = bar.closest('.khc-col');
                                            if (col) {
                                                const crown = col.querySelector('.khc-crown');
                                                if (crown) crown.classList.add('khc-loaded');
                                                const priceLabel = col.querySelector('.khc-price-inline');
                                                if (priceLabel) priceLabel.classList.add('khc-loaded');
                                            }
                                        }, 900);
                                    } else if (bar.classList.contains('lo')) {
                                        setTimeout(() => {
                                            bar.classList.add('khc-loaded');
                                        }, 900);
                                    }
                                }, 60 * idx);
                            });
                        });
                    });
                };

                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                animateBars();
                                observer.disconnect();
                            }
                        });
                    }, { threshold: 0.08 });

                    const container = document.querySelector('.season-bars-container');
                    if (container) {
                        observer.observe(container);
                    } else {
                        setTimeout(animateBars, 200);
                    }
                } else {
                    setTimeout(animateBars, 200);
                }
            }
        @endif
    });
</script>
@endsection
