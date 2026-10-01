@extends('layouts.farmer')

@php
    $activeLocale = $activeLocale ?? (request()->query('lang') ?: (session('locale') ?: (request()->cookie('locale') ?: app()->getLocale())));
@endphp

@section('title', ($activeLocale === 'en' 
    ? $crop->name . " — Today's Market Prices & Forecast" 
    : (($crop->name_kn ? $crop->name_kn . ' (' . $crop->name . ')' : $crop->name) . ' — ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು & ಮುನ್ಸೂಚನೆ')
))

@section('content')
@php
    $selectedMarketPrices = $selectedMarketPrices ?? collect();
    $activePriceItem = $activePriceItem ?? (
        $selectedMarketPrices->isNotEmpty()
            ? ($varietyId 
                ? ($selectedMarketPrices->firstWhere('variety_id', $varietyId) ?? $selectedMarketPrices->first())
                : ($selectedMarketPrices->where('price_date', $latestDate)->sortByDesc('modal_price')->first() ?? $selectedMarketPrices->first()))
            : $mandiPrices->first()
    );

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

    <style>
        .custom-mandi-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-mandi-scroll::-webkit-scrollbar-track {
            background: #F5EFE6;
            border-radius: 4px;
        }
        .custom-mandi-scroll::-webkit-scrollbar-thumb {
            background: #1C5A2C;
            border-radius: 4px;
        }
    </style>

    <!-- 2. Hero Showcase: Classic Two-Box Aligned Architecture with Responsive Mobile Flow -->
    <div class="grid grid-cols-1 lg:grid-cols-12 lg:grid-rows-[1fr_auto] gap-5 items-stretch">

        <!-- ========================================================================= -->
        <!-- ELEMENT 1: CROP SPECIMEN PHOTO CARD                                      -->
        <!-- Mobile: Order 1 | Desktop: Left Column Row 1 (Cols 1-5)                   -->
        <!-- ========================================================================= -->
        <div class="order-1 lg:order-none lg:col-span-5 lg:col-start-1 lg:row-start-1 bg-stone-900 rounded-3xl overflow-hidden border-2 border-[#D9CEB8] shadow-sm relative flex flex-col justify-between p-5 text-white min-h-[280px] sm:min-h-[320px] h-full">
            <!-- Full Height Image -->
            <img src="{{ $crop->photo_url }}" 
                 alt="{{ $crop->name }}" 
                 class="w-full h-full absolute inset-0 object-cover opacity-90">
            
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-black/20"></div>

            <!-- Top Left Floating Badges -->
            <div class="relative z-10 flex items-center justify-between">
                @if($boardMeta)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $boardMeta['theme'] === 'coffee' ? 'bg-amber-950/90 text-amber-200 border-amber-800' : 'bg-emerald-950/90 text-emerald-200 border-emerald-800' }} backdrop-blur-md text-xs font-black shadow-sm border font-sans">
                        <span>{{ $boardMeta['icon'] }}</span>
                        <span>{{ $boardMeta['badge_en'] }}</span>
                        @if($activeLocale === 'kn')
                            <span class="text-[10px] opacity-90 font-kannada font-normal">• {{ $boardMeta['badge_kn'] }}</span>
                        @endif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-stone-900 font-extrabold text-xs shadow-sm border border-stone-200/60 font-sans">
                        <span class="w-2 h-2 rounded-full bg-[#1C5A2C] animate-pulse"></span>
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Reliable' : 'ಅಧಿಕೃತ' }}</span>
                        @if($activeLocale === 'kn')
                            <span class="text-[10px] text-stone-500 font-sans font-normal">• Reliable</span>
                        @endif
                    </span>
                @endif

                @if($crop->is_major)
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-stone-950 font-black text-[10px] uppercase tracking-wider shadow-2xs font-sans">
                        Major Crop
                    </span>
                @endif
            </div>

            <!-- Bottom Left Crop Name & Details -->
            <div class="relative z-10 mt-auto space-y-1 text-white">
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
                    <span>{{ $activeLocale === 'en' ? 'Standard Unit:' : 'ಪ್ರಮಾಣಿತ ಘಟಕ:' }} <strong class="text-white font-bold">{{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : ($crop->standard_unit ?? 'Quintal') }}</strong></span>
                    @if($crop->scientific_name)
                        <span>•</span>
                        <span class="italic text-white/70">{{ $crop->scientific_name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- ELEMENT 2: BOX 1 (CURRENT PRICE, PICK YOUR GRADE, VIEW DIFFERENT MARKET) -->
        <!-- Mobile: Order 2 | Desktop: Right Column Rows 1-2 (Cols 6-12)              -->
        <!-- ========================================================================= -->
        <div class="order-2 lg:order-none lg:col-span-7 lg:col-start-6 lg:row-start-1 lg:row-span-2 bg-white rounded-3xl p-5 sm:p-7 border-2 border-[#D9CEB8] shadow-sm flex flex-col justify-between space-y-4 h-full"
             x-data="{ activeSort: '{{ $defaultMarketSort ?? 'nearest_first' }}', showAllRadius: false, showAllGrades: true }">
            
            <!-- 1. Current Price Section -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-extrabold tracking-wider text-stone-400 uppercase text-[11px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'CURRENT PRICE' : 'ಇಂದಿನ ದರ' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-300 text-emerald-900 font-bold text-[11px] sm:text-xs shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                        <span class="text-emerald-700 font-semibold">{{ $activeLocale === 'en' ? 'Updated:' : 'ನವೀಕರಿಸಲಾಗಿದೆ:' }}</span>
                        <span class="font-black text-[#1C5A2C]">{{ \Carbon\Carbon::parse($activePriceItem->price_date ?? $latestDate)->format('d M Y') }}</span>
                    </span>
                </div>

                <div class="flex flex-wrap items-baseline gap-3">
                    <div class="text-4xl sm:text-5xl font-black text-stone-900 tracking-tight font-sans">
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

                    @if(isset($dailyPriceChangeTrend) && $dailyPriceChangeTrend === 'rise')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-xs font-bold font-sans border border-emerald-200">
                            <span>↑</span>
                            <span>+₹{{ number_format(abs($dailyPriceChange), 0) }}</span>
                            <span class="text-[11px] font-semibold text-emerald-600">(+{{ abs($dailyPriceChangePercent) }}%)</span>
                        </span>
                    @elseif(isset($dailyPriceChangeTrend) && $dailyPriceChangeTrend === 'drop')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-red-50 text-red-700 text-xs font-bold font-sans border border-red-200">
                            <span>↓</span>
                            <span>-₹{{ number_format(abs($dailyPriceChange), 0) }}</span>
                            <span class="text-[11px] font-semibold text-red-600">({{ $dailyPriceChangePercent }}%)</span>
                        </span>
                    @elseif(isset($dailyPriceChangeTrend) && $dailyPriceChangeTrend === 'stable' && $dailyPriceChange !== null)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-stone-100 text-stone-700 text-xs font-bold font-sans border border-stone-200">
                            <span>→</span>
                            <span>₹0</span>
                            <span class="text-[11px] font-semibold text-stone-500">({{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }})</span>
                        </span>
                    @endif
                </div>

                <!-- Active Mandi / Centre Details Bar -->
                <div class="flex flex-wrap items-center gap-2 text-xs text-stone-600 pt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="font-bold text-stone-800 uppercase">
                        {{ $activeLocale === 'kn' ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) : ($crop->standard_unit ?? 'Quintal') }} • @ {{ strtoupper($displayMarketName) }}
                    </span>

                    @if($activePriceItem && $activePriceItem->min_price > 0 && $activePriceItem->max_price > 0 && $activePriceItem->price_spread > 0)
                        <span class="text-stone-300">•</span>
                        <span class="text-stone-600 font-semibold" title="{{ $activeLocale === 'en' ? 'Day auction min-max range' : 'ದೈನಂದಿನ ಹರಾಜು ಕನಿಷ್ಠ-ಗರಿಷ್ಠ ವ್ಯಾಪ್ತಿ' }}">
                            {{ $activeLocale === 'en' ? 'Day Range:' : 'ಶ್ರೇಣಿ:' }} ₹{{ number_format($activePriceItem->min_price, 0) }} – ₹{{ number_format($activePriceItem->max_price, 0) }}
                        </span>
                    @endif

                    @if(!empty($isSelectedActualNearest) && !empty($nearestDistanceKm))
                        <span class="text-stone-300">•</span>
                        <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-[#fff4e5] text-[#9a5b00] border border-[#ffe0b2] text-[11px] font-extrabold whitespace-nowrap shadow-2xs leading-none"
                              title="{{ $activeLocale === 'en' ? 'Closest mandi to your location' : 'ನಿಮ್ಮ ಸ್ಥಳಕ್ಕೆ ಅತ್ಯಂತ ಸಮೀಪದ ಮಾರುಕಟ್ಟೆ' }}">
                            <span class="inline-flex items-center leading-none">📍</span>
                            <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">{{ $activeLocale === 'en' ? 'nearest market' : 'ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆ' }} • {{ round($nearestDistanceKm) }} km</span>
                        </span>
                    @elseif(!empty($nearestDistanceKm))
                        <span class="text-stone-300">•</span>
                        <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold whitespace-nowrap leading-none">
                            <span class="inline-flex items-center leading-none">📍</span>
                            <span class="inline-flex items-center leading-none">{{ round($nearestDistanceKm) }} km {{ $activeLocale === 'en' ? 'away' : 'ದೂರ' }}</span>
                            @if(!empty($actualNearestMarket) && $actualNearestMarket->id !== $selectedMarket?->id)
                                <span class="text-[10px] text-emerald-600 font-medium leading-none">({{ $activeLocale === 'en' ? 'Nearest: ' : 'ಸಮೀಪ: ' }}{{ $activeLocale === 'en' ? $actualNearestMarket->name : ($actualNearestMarket->name_kn ?? $actualNearestMarket->name) }} {{ round($actualNearestMarket->distance_km) }}km)</span>
                            @endif
                        </span>
                    @endif

                    <span class="text-stone-300">•</span>
                    <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-950 border border-amber-300 text-[11px] font-bold whitespace-nowrap shadow-2xs leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="inline-flex items-center leading-none text-xs">📅</span>
                        <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'as of' : 'ದಿನಾಂಕ:' }}</span>
                        <span class="font-black text-amber-900 underline decoration-amber-400 decoration-1 leading-none">{{ \Carbon\Carbon::parse($activePriceItem->price_date ?? $latestDate)->format('d M') }}</span>
                    </span>

                    @if($boardMeta)
                        <span class="text-amber-900 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} text-[11px]">({{ $activeLocale === 'en' ? $boardMeta['badge_en'] : $boardMeta['badge_kn'] }})</span>
                    @endif
                </div>
            </div>

            <!-- 2. "PICK YOUR GRADE" Section -->
            @php
                $displayMarketPrices = isset($selectedMarketPrices) && $selectedMarketPrices->isNotEmpty() ? $selectedMarketPrices : collect();
                $totalGradesCount = $displayMarketPrices->count();
            @endphp

            @if($totalGradesCount === 1)
                <!-- Single Grade: Clean Compact Box -->
                <div class="pt-2 border-t border-stone-100">
                    <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-[#1C5A2C] text-white shadow-xs border-2 border-[#1C5A2C]">
                        <div>
                            <div class="text-[11px] font-extrabold tracking-wide uppercase text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $displayMarketPrices->first()->getDisplayVarietyGrade($activeLocale) }}
                            </div>
                            <div class="text-base font-black text-white font-sans tracking-tight">
                                ₹{{ number_format($displayMarketPrices->first()->modal_price, 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($totalGradesCount > 1)
                <!-- Multiple Grades: Responsive Wrap Row with Toggle -->
                <div class="space-y-2 pt-2 border-t border-stone-100">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider flex items-center gap-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span>{{ $activeLocale === 'en' ? 'PICK YOUR GRADE' : 'ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ' }}</span>
                            <span class="text-[10px] text-stone-500 font-bold">({{ $totalGradesCount }} {{ $activeLocale === 'en' ? 'VARIETIES' : 'ತಳಿಗಳು' }})</span>
                        </div>
                        <span class="text-[11px] text-stone-500 font-medium hidden sm:inline">
                            @if(isset($weeklyMinTradedPrice) && $weeklyMinTradedPrice > 0 && isset($weeklyMaxTradedPrice) && $weeklyMaxTradedPrice > 0)
                                {{ $activeLocale === 'en' ? 'Trades this week: ₹' . number_format($weeklyMinTradedPrice, 0) . ' – ₹' . number_format($weeklyMaxTradedPrice, 0) : 'ಈ ವಾರದ ವಹಿವಾಟು: ₹' . number_format($weeklyMinTradedPrice, 0) . ' – ₹' . number_format($weeklyMaxTradedPrice, 0) }}
                            @else
                                {{ $activeLocale === 'en' ? 'Active Market Trades' : 'ಸಕ್ರಿಯ ಮಾರುಕಟ್ಟೆ ವಹಿವಾಟು' }}
                            @endif
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        @foreach($displayMarketPrices as $index => $smp)
                            @php
                                $isVarSelected = ($activePriceItem && $activePriceItem->variety_id == $smp->variety_id && (!$smp->grade || empty($gradeParam) || strcasecmp($activePriceItem->grade ?? '', $smp->grade) === 0));
                                $vGradeLabel = $smp->getDisplayVarietyGrade($activeLocale);
                                $isPrimaryGrade = ($index < 4 || $isVarSelected);
                            @endphp
                            <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $smp->variety_id, 'grade' => $smp->grade, 'market' => $displayMarketName])) }}"
                               x-show="showAllGrades || {{ $isPrimaryGrade ? 'true' : 'false' }}"
                               class="px-3.5 py-2 rounded-2xl font-bold transition border-2 flex flex-col items-start gap-0.5 cursor-pointer tap-feedback active:scale-95 {{ $isVarSelected ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
                                <span class="text-[11px] {{ $isVarSelected ? 'text-white' : 'text-stone-600' }} {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                    {{ $vGradeLabel }}
                                </span>
                                <span class="text-sm font-black font-sans {{ $isVarSelected ? 'text-emerald-200' : 'text-[#1C5A2C]' }}">
                                    ₹{{ number_format($smp->modal_price, 0) }}
                                </span>
                            </a>
                        @endforeach
                    </div>

                    @if($totalGradesCount > 4)
                        <div class="pt-0.5">
                            <button type="button" 
                                    @click="showAllGrades = !showAllGrades" 
                                    class="text-[11px] font-bold text-[#1C5A2C] hover:underline transition inline-flex items-center gap-1 cursor-pointer">
                                <span x-text="showAllGrades ? '▲ {{ $activeLocale === 'en' ? 'Show fewer grades' : 'ಕಡಿಮೆ ತಳಿಗಳನ್ನು ತೋರಿಸಿ' }}' : '+ {{ $activeLocale === 'en' ? 'Show' : 'ತೋರಿಸಿ' }} {{ $totalGradesCount - 4 }} {{ $activeLocale === 'en' ? 'more grades' : 'ಹೆಚ್ಚಿನ ತಳಿಗಳು' }} ▾'"></span>
                            </button>
                        </div>
                    @endif
                </div>
            @elseif(isset($availableVarieties) && $availableVarieties->isNotEmpty())
                <!-- Fallback General Varieties -->
                <div class="space-y-2 pt-2 border-t border-stone-100">
                    <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'PICK YOUR GRADE' : 'ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ' }}
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'market' => $marketParam])) }}"
                           class="px-3.5 py-2 rounded-xl font-bold transition border-2 {{ empty($varietyId) ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-2xs' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
                            <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">{{ $activeLocale === 'en' ? 'All Grades (FAQ)' : 'ಎಲ್ಲಾ ತಳಿಗಳು / FAQ' }}</span>
                        </a>

                        @foreach($availableVarieties as $v)
                            @php
                                $isVarSelected = ($varietyId == $v->id);
                                $vTitle = $v->displayName($activeLocale);
                            @endphp
                            <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $v->id, 'market' => $marketParam])) }}"
                               class="px-3.5 py-2 rounded-xl font-bold transition border-2 flex items-center gap-1.5 {{ $isVarSelected ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-2xs' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
                                <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">{{ $vTitle }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 3. "VIEW DIFFERENT MARKET / CENTRE" -->
            <div class="space-y-2.5 pt-2 border-t border-stone-100">
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

                <!-- Mandi Pills with Scrollbar -->
                @php
                    $beyondRadiusCount = 0;
                @endphp
                <div class="flex flex-wrap gap-2 text-xs max-h-56 overflow-y-auto pr-1 custom-mandi-scroll">
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
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full font-bold transition border-2 cursor-pointer tap-feedback active:scale-95 {{ $isMktSelected ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
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
                                class="text-[11px] font-bold text-[#1C5A2C] hover:underline transition inline-flex items-center gap-1 cursor-pointer">
                            <span x-text="showAllRadius ? '▲ {{ $activeLocale === 'en' ? 'Hide distant mandis beyond' : 'ದೂರದ ಮಂಡಿಗಳನ್ನು ಮರೆಮಾಡಿ' }} {{ $marketRadiusKm }} km' : '+ {{ $activeLocale === 'en' ? 'Show' : 'ತೋರಿಸಿ' }} {{ $beyondRadiusCount }} {{ $activeLocale === 'en' ? 'more mandis beyond' : 'ಹೆಚ್ಚಿನ ಮಂಡಿಗಳು' }} {{ $marketRadiusKm }} km ▾'"></span>
                        </button>
                    </div>
                @endif
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- ELEMENT 3: BOX 2 (ADVISORY & ACTION BUTTONS)                              -->
        <!-- Mobile: Order 3 (Directly Below Box 1) | Desktop: Left Column Row 2       -->
        <!-- ========================================================================= -->
        <div class="order-3 lg:order-none lg:col-span-5 lg:col-start-1 lg:row-start-2 bg-white rounded-2xl sm:rounded-3xl border-2 border-[#D9CEB8] shadow-sm overflow-hidden">

            <!-- Market Advisory / Sentiment Banner -->
            @php
                $h7Horizon = collect($forecast['horizons'] ?? [])->firstWhere('horizon_days', 7);
                $advisoryPct = $h7Horizon ? abs($h7Horizon['percentage_change']) : null;
                $advisoryConf = $h7Horizon ? (int)($h7Horizon['confidence_score'] ?? 0) : null;
                $advisoryDir = $h7Horizon['direction'] ?? $forecastDir;
            @endphp
            <div class="px-3.5 py-2.5 sm:px-4 sm:py-3 {{ $forecastDir === 'down' ? 'bg-amber-50/90' : ($forecastDir === 'up' ? 'bg-emerald-50/90' : 'bg-stone-50') }}">
                <div class="flex items-start gap-2.5">
                    <!-- Left accent stripe -->
                    <div class="w-1 self-stretch rounded-full shrink-0 {{ $forecastDir === 'down' ? 'bg-amber-400' : ($forecastDir === 'up' ? 'bg-emerald-500' : 'bg-stone-400') }}"></div>

                    <div class="flex-1 space-y-1">
                        <!-- Title row -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-base sm:text-lg leading-none">{{ $forecastDir === 'down' ? '⏰' : ($forecastDir === 'up' ? '📈' : '💡') }}</span>
                            <div class="font-extrabold text-xs sm:text-sm {{ $forecastDir === 'down' ? 'text-amber-950' : ($forecastDir === 'up' ? 'text-emerald-950' : 'text-stone-800') }} {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                @if($forecastDir === 'down')
                                    {{ $activeLocale === 'en' ? 'Optimal Time to Sell (Sell Now)' : 'ಮಾರಾಟಕ್ಕೆ ಸೂಕ್ತ ಸಮಯ' }}
                                @elseif($forecastDir === 'up')
                                    {{ $activeLocale === 'en' ? 'Price Rise Expected (Hold / Watch)' : 'ಧಾರಣೆ ಏರಿಕೆಯ ಮುನ್ಸೂಚನೆ' }}
                                @else
                                    {{ $activeLocale === 'en' ? 'Market Stable (Monitor)' : 'ಮಾರುಕಟ್ಟೆ ಸ್ಥಿರ — ಗಮನಿಸಿ' }}
                                @endif
                            </div>
                        </div>

                        <!-- Body text -->
                        <p class="text-[11px] sm:text-xs leading-snug {{ $forecastDir === 'down' ? 'text-amber-800' : ($forecastDir === 'up' ? 'text-emerald-800' : 'text-stone-600') }} {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            @if($forecastDir === 'down')
                                {{ $activeLocale === 'en' ? 'Arrivals are expected to increase over the coming weeks, which may cause prices to soften. Selling at current favorable rates is advisable.' : 'ಮುಂದಿನ ವಾರಗಳಲ್ಲಿ ಮಾರುಕಟ್ಟೆಗೆ ಆವಕ ಹೆಚ್ಚಾಗುವ ಮುನ್ಸೂಚನೆ ಇದ್ದು, ದರಗಳು ಕೊಂಚ ಇಳಿಕೆಯಾಗುವ ಸಾಧ್ಯತೆಯಿದೆ. ಸದ್ಯದ ಉತ್ತಮ ಬೆಲೆಯಲ್ಲಿ ಮಾರಾಟ ಮಾಡುವುದು ಸೂಕ್ತ.' }}
                            @elseif($forecastDir === 'up')
                                {{ $activeLocale === 'en' ? 'Signs of rising demand are observed in regional mandis. Prices may improve further in the coming days.' : 'ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಹೆಚ್ಚಾಗುವ ಲಕ್ಷಣಗಳು ಕಂಡುಬರುತ್ತಿದ್ದು, ಮುಂದಿನ ದಿನಗಳಲ್ಲಿ ದರ ಇನ್ನಷ್ಟು ಸುಧಾರಿಸುವ ಸಂಭವವಿದೆ.' }}
                            @else
                                {{ $activeLocale === 'en' ? 'Market rates are steady. Consider transportation costs and arrival volumes of nearby mandis before selling.' : 'ಮಾರುಕಟ್ಟೆ ದರಗಳು ಸ್ಥಿರವಾಗಿದ್ದು, ಹತ್ತಿರದ ಮಂಡಿಗಳ ಸಾರಿಗೆ ವೆಚ್ಚ ಮತ್ತು ಆವಕ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧಾರ ಕೈಗೊಳ್ಳಿ.' }}
                            @endif
                        </p>

                        <!-- Live forecast stat pills with clear high-contrast numbers -->
                        @if($advisoryPct !== null && !empty($forecast['is_sufficient']))
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full border shadow-2xs leading-none {{ $forecastDir === 'up' ? 'bg-emerald-100/90 border-emerald-300' : ($forecastDir === 'down' ? 'bg-amber-100/90 border-amber-300' : 'bg-stone-100 border-stone-300') }}">
                                    <span class="inline-flex items-center text-[10px] sm:text-[11px] font-bold text-stone-600 leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                        {{ $activeLocale === 'en' ? '7-day forecast:' : '7 ದಿನ:' }}
                                    </span>
                                    <span class="inline-flex items-center text-xs sm:text-[13px] font-black font-sans tracking-tight leading-none {{ $forecastDir === 'up' ? 'text-emerald-700' : ($forecastDir === 'down' ? 'text-red-700' : 'text-stone-800') }}">
                                        {{ $forecastDir === 'up' ? '↑ +' : ($forecastDir === 'down' ? '↓ -' : '→ ±') }}{{ $advisoryPct }}%
                                    </span>
                                </span>
                                @if($advisoryConf > 0)
                                    <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full border border-slate-300 bg-slate-100/90 text-slate-900 shadow-2xs leading-none">
                                        <span class="inline-flex items-center text-[10px] sm:text-[11px] font-bold text-slate-500 leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                            {{ $activeLocale === 'en' ? 'Confidence:' : 'ವಿಶ್ವಾಸ:' }}
                                        </span>
                                        <span class="inline-flex items-center text-xs sm:text-[13px] font-black font-sans text-slate-900 tracking-tight leading-none">
                                            {{ $advisoryConf }}%
                                        </span>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="h-px bg-[#EAE3D2]"></div>

            <!-- Action Buttons: WhatsApp Share & Where to Sell Simulator -->
            @php
                $cleanDisplayMarket = preg_replace('/\s+APMC$/i', '', $displayMarketName);
                $sharePriceText = "🌾 *" . ($activeLocale === 'en' ? 'Krushi Baandhava — ' : 'ಕೃಷಿ ಬಾಂಧವ — ') . ($activeLocale === 'en' ? $crop->name : ($crop->name_kn ?: $crop->name)) . "*\n"
                    . "📍 " . ($boardMeta ? ($activeLocale === 'en' ? 'Centre: ' : 'ಕೇಂದ್ರ: ') : ($activeLocale === 'en' ? 'Market: ' : 'ಮಾರುಕಟ್ಟೆ: ')) . $cleanDisplayMarket . "\n"
                    . "💰 " . ($activeLocale === 'en' ? "Today's Modal Rate: ₹" : 'ಇಂದಿನ ಮಾದರಿ ದರ: ₹') . number_format($displayModal, 0) . " / " . ($activeLocale === 'en' ? ($crop->standard_unit ?? 'Quintal') : ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್'))) . "\n"
                    . ($perKgPrice ? ($activeLocale === 'en' ? "⚖️ Approx per kg: ≈ ₹" : "⚖️ ಪ್ರತಿ ಕೆ.ಜಿ ಗೆ: ≈ ₹") . $perKgPrice . "/kg\n" : "")
                    . "📅 " . ($activeLocale === 'en' ? 'Date: ' : 'ದಿನಾಂಕ: ') . $stats['date_formatted'] . "\n"
                    . "👉 " . ($activeLocale === 'en' ? 'View Full Rate & Forecast: ' : 'ಸಂಪೂರ್ಣ ದರ & ಮುನ್ಸೂಚನೆ ವೀಕ್ಷಿಸಿ: ') . url()->current();
                $whatsappDetailUrl = "https://wa.me/?text=" . rawurlencode($sharePriceText);
            @endphp

            <div class="grid grid-cols-2 p-2 sm:p-2.5 gap-2">
                <!-- WhatsApp Share Button -->
                <a href="{{ $whatsappDetailUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="flex items-center justify-center gap-1.5 py-2 sm:py-2.5 px-2.5 rounded-xl sm:rounded-2xl bg-[#25D366] hover:bg-[#1db954] active:scale-95 text-white font-extrabold text-xs shadow-2xs transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.524 5.847L.057 23.882a.5.5 0 00.613.612l6.101-1.463A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.9a9.866 9.866 0 01-5.03-1.378l-.36-.214-3.733.896.927-3.63-.235-.374A9.867 9.867 0 012.1 12c0-5.464 4.436-9.9 9.9-9.9 5.464 0 9.9 4.436 9.9 9.9 0 5.464-4.436 9.9-9.9 9.9z"/>
                    </svg>
                    <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Share Price' : 'ದರ ಶೇರ್ ಮಾಡಿ' }}</span>
                </a>

                <!-- Where to Sell Button -->
                <a href="{{ route('farmer.decision.where-to-sell', ['crop' => $crop->slug]) }}"
                   class="flex items-center justify-center gap-1.5 py-2 sm:py-2.5 px-2.5 rounded-xl sm:rounded-2xl bg-[#1C5A2C] hover:bg-[#154622] active:scale-95 text-white font-extrabold text-xs shadow-2xs transition-all cursor-pointer">
                    <span class="text-xs sm:text-sm leading-none">⚖️</span>
                    <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Where to Sell?' : 'ಎಲ್ಲಿ ಮಾರಾಟ?' }}</span>
                    <span class="text-xs opacity-80">&rarr;</span>
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
                {{ preg_replace('/\s+APMC$/i', '', $stats['highest_market']) }}
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
                {{ preg_replace('/\s+APMC$/i', '', $stats['lowest_market']) }}
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

    <!-- 4. "What's next" Forecast Horizons (Compact Classic 2-Column Mobile & 4-Column Desktop) -->
    <div class="bg-white rounded-2xl sm:rounded-3xl p-3 sm:p-6 border-2 border-[#D9CEB8] shadow-sm space-y-3.5 sm:space-y-5 overflow-hidden">
        
        <!-- Section Header with Classic Editorial Layout -->
        <div class="flex items-center justify-between gap-3 pb-3 border-b-2 border-[#F0EAE1]">
            <div class="flex items-start gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full bg-[#1C5A2C] shrink-0 mt-0.5"></span>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'When to Sell? — Price Forecast' : 'ಯಾವಾಗ ಮಾರಬೇಕು? — ಬೆಲೆ ಮುನ್ಸೂಚನೆ' }}
                        </h2>
                        @if($activePriceItem && $activePriceItem->variety)
                            <span class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-full text-[10px] sm:text-[11px] font-black bg-[#FAF6EE] text-[#1C5A2C] border border-[#D9CEB8] shadow-2xs leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                <span class="inline-flex items-center leading-none">{{ $activePriceItem->getDisplayVarietyGrade($activeLocale) }}</span>
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? '1 to 15-day projected price movement & market direction' : 'ಮುಂದಿನ 15 ದಿನಗಳ ನಿರೀಕ್ಷಿತ ದರ ಶ್ರೇಣಿ ಮತ್ತು ಮಾರುಕಟ್ಟೆ ಪ್ರವೃತ್ತಿ' }}
                    </p>
                </div>
            </div>
            <div class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-300 text-emerald-900 text-[10px] sm:text-[11px] font-black shadow-2xs shrink-0 leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#1C5A2C] animate-pulse shrink-0"></span>
                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Updated daily' : 'ದೈನಂದಿನ ಅಪ್ಡೇಟ್' }}</span>
            </div>
        </div>

        @if(!empty($forecast['is_sufficient']) && !empty($forecast['horizons']))
            <!-- 4-Card Forecast Grid (2 Columns on Mobile, 4 Columns on Desktop) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3.5">
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
                        $shortDate = !empty($h['target_date']) ? \Carbon\Carbon::parse($h['target_date'])->format('d M') : str_replace(' ' . date('Y'), '', $h['target_date_formatted'] ?? '');

                        $absPct = abs($h['percentage_change']);
                        $dir = $h['direction'] ?? 'steady';
                        if ($dir === 'up') {
                            $heroChange = "↑ +{$absPct}%";
                            $arrow = "↑";
                            $dirColorClass = "text-[#16803C]";
                            $accentColor = "bg-[#16803C]";
                        } elseif ($dir === 'down') {
                            $heroChange = "↓ -{$absPct}%";
                            $arrow = "↓";
                            $dirColorClass = "text-[#C0392B]";
                            $accentColor = "bg-[#C0392B]";
                        } else {
                            $heroChange = "→ ≈ 0%";
                            $arrow = "→";
                            $dirColorClass = "text-[#B45309]";
                            $accentColor = "bg-amber-600";
                        }

                        $confScore = (float)($h['confidence_score'] ?? 50);
                        if ($confScore >= 70) {
                            $qualLabelEn = 'LIKELY';
                            $qualLabelKn = 'ಹೆಚ್ಚು ಸಾಧ್ಯತೆ';
                            $confColor = '#16803C';
                            $confTextClass = 'text-[#16803C]';
                        } elseif ($confScore >= 50) {
                            $qualLabelEn = 'POSSIBLE';
                            $qualLabelKn = 'ಸಾಧ್ಯತೆ ಇದೆ';
                            $confColor = '#D97706';
                            $confTextClass = 'text-[#B45309]';
                        } else {
                            $qualLabelEn = 'LESS LIKELY';
                            $qualLabelKn = 'ಸಾಧ್ಯತೆ ಕಡಿಮೆ';
                            $confColor = '#9CA3AF';
                            $confTextClass = 'text-stone-600';
                        }
                    @endphp
                    <div class="bg-[#FAF8F5] rounded-xl sm:rounded-2xl p-2 sm:p-4 border-2 border-[#E5DECE] hover:border-[#1C5A2C] shadow-2xs transition-all duration-200 flex flex-col justify-between space-y-2 sm:space-y-3 relative overflow-hidden group">
                        
                        <!-- Top Accent Line -->
                        <div class="absolute top-0 left-0 right-0 h-1 {{ $accentColor }} transition-colors"></div>

                        <!-- Card Header: Brand Green Horizon Pill & Short Target Date -->
                        <div class="flex items-center justify-between gap-1 pt-0.5">
                            <span class="inline-flex items-center justify-center px-2 py-1 rounded bg-[#1C5A2C] text-white text-[9px] sm:text-[11px] font-black tracking-wider uppercase font-sans shadow-2xs border border-[#1C5A2C] leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? $horizonMeta['en'] : $horizonMeta['kn'] }}</span>
                            </span>
                            
                            <span class="text-[9px] sm:text-xs font-bold text-stone-500 font-sans whitespace-nowrap leading-none inline-flex items-center">
                                {{ $shortDate }}
                            </span>
                        </div>

                        <!-- Hero Metric: Expected Movement Percentage (Negilu Style - Giant & Eye-Catching) -->
                        <div class="space-y-0.5 sm:space-y-1">
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl sm:text-2xl lg:text-[32px] font-black tracking-tight font-sans leading-none {{ $dirColorClass }}">
                                    {{ $heroChange }}
                                </span>
                            </div>

                            <!-- Sub-line: Predicted Target Price with Direction Arrow -->
                            <div class="flex items-baseline gap-1 text-stone-900 font-sans flex-wrap leading-tight">
                                <span class="text-xs sm:text-sm lg:text-base font-black tracking-tight">
                                    ₹{{ number_format($h['expected_price'], 0) }}
                                </span>
                                <span class="text-xs font-black {{ $dirColorClass }}">
                                    {{ $arrow }}
                                </span>
                                <span class="text-[9px] sm:text-[11px] font-semibold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    /{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}
                                </span>
                            </div>
                        </div>

                        <!-- Bottom Section: Auction Range & Confidence -->
                        <div class="pt-1.5 sm:pt-2.5 border-t border-[#EAE3D2] space-y-1.5 sm:space-y-2 text-xs">
                            <!-- Expected Trading Range -->
                            <div class="text-[9.5px] sm:text-xs text-stone-600 font-sans leading-tight">
                                <span class="font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                    {{ $activeLocale === 'en' ? 'range: ' : 'ಶ್ರೇಣಿ: ' }}
                                </span>
                                <span class="font-black text-stone-800 whitespace-nowrap">
                                    ₹{{ number_format($h['lower_bound'], 0) }} – ₹{{ number_format($h['upper_bound'], 0) }}
                                </span>
                            </div>

                            <!-- Confidence Score & Progress Bar (with Qualitative Status) -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-1 text-[8.5px] sm:text-[10px] font-bold tracking-wider uppercase">
                                    <span class="text-stone-400 font-extrabold whitespace-nowrap {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                        {{ $activeLocale === 'en' ? 'CONFIDENCE' : 'ವಿಶ್ವಾಸ' }}
                                    </span>
                                    <span class="font-black font-sans whitespace-nowrap text-right {{ $confTextClass }} {{ $activeLocale === 'kn' ? 'font-kannada text-[8px] sm:text-[9.5px]' : '' }}">
                                        {{ $activeLocale === 'en' ? $qualLabelEn : $qualLabelKn }}
                                    </span>
                                </div>
                                <div class="w-full bg-[#E5DECE] h-1.5 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" 
                                         style="width: {{ min(100, max(10, $confScore)) }}%; background-color: {{ $confColor }};">
                                    </div>
                                </div>
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


        @else
            <!-- Data Insufficiency Notice -->
            <div class="p-4 rounded-2xl bg-amber-50/80 border-2 border-amber-200 text-amber-950 flex items-start gap-3">
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
        <div class="rounded-xl p-2.5 sm:p-3 bg-stone-50 border border-stone-200 text-stone-500 flex items-start gap-2 text-[11px] leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="text-sm shrink-0">📊</span>
            <p>
                <strong class="font-bold text-stone-700">{{ $activeLocale === 'en' ? 'Disclaimer: ' : 'ಹಕ್ಕುತ್ಯಾಗ: ' }}</strong>
                <span>
                    {{ $activeLocale === 'en' 
                        ? ($forecast['disclaimer_en'] ?? 'Mathematical estimation based on past price patterns. Actual realized rates may vary based on weather, daily market arrival volumes, and government trade policies.') 
                        : ($forecast['disclaimer_kn'] ?? 'ಇದು ಕೇವಲ ಹಿಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳ ಪ್ರವೃತ್ತಿ ಆಧಾರಿತ ಗಣಿತೀಯ ಅಂದಾಜು. ನೈಜ ದರಗಳು ಹವಾಮಾನ ಪರಿಸ್ಥಿತಿ, ಮಾರುಕಟ್ಟೆಯ ಆವಕ ಪ್ರಮಾಣ ಮತ್ತು ಸರ್ಕಾರದ ನೀತಿಗಳಿಂದ ವ್ಯತ್ಯಾಸವಾಗಬಹುದು.') }}
                </span>
            </p>
        </div>
    </div>

    <!-- 5. Best Months to Sell — Krushi Harvest Calendar -->
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
        .khc-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:35px;}
        .khc-chip{background:linear-gradient(135deg,#7C2D12,#C2410C,#F59E0B);border-radius:14px;padding:5px 13px 5px 8px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 3px 14px rgba(245,158,11,0.28);animation:chipPulse 3.5s ease-in-out infinite;}
        @keyframes chipPulse{0%,100%{transform:scale(1);box-shadow:0 3px 14px rgba(245,158,11,0.28);}50%{transform:scale(1.03);box-shadow:0 5px 22px rgba(245,158,11,0.48);}}
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
        @keyframes pkShine{0%,100%{box-shadow:0 -8px 24px rgba(245,158,11,.55);}50%{box-shadow:0 -14px 36px rgba(245,158,11,.85);}}
        @keyframes loPulse{0%,100%{border-top-color:#EF4444;box-shadow:0 -2px 8px rgba(239,68,68,0.3);}50%{border-top-color:#F87171;box-shadow:0 -5px 16px rgba(239,68,68,0.65);}}
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
                <div class="khc-eyebrow {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:6px;height:6px;background:#86EFAC;border-radius:50%;flex-shrink:0;"></span>
                        {{ $activeLocale === 'en' ? 'HISTORICAL SEASONAL ANALYSIS' : 'ಐತಿಹಾಸಿಕ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆ' }}
                    </span>
                    @if(!empty($seasonalAnalysis['market_name']))
                        <span style="background:rgba(255,255,255,0.12);padding:2px 8px;border-radius:12px;font-size:10.5px;color:#FDE68A;border:1px solid rgba(253,230,138,0.25);">
                            📍 {{ $activeLocale === 'kn' && !empty($seasonalAnalysis['market_name_kn']) ? $seasonalAnalysis['market_name_kn'] : $seasonalAnalysis['market_name'] }}
                        </span>
                    @endif
                    @if(($seasonalAnalysis['scope'] ?? '') === 'market_calibrated')
                        <span style="background:rgba(99,102,241,0.18);padding:2px 8px;border-radius:12px;font-size:9.5px;color:rgba(196,198,255,0.85);border:1px solid rgba(99,102,241,0.3);" title="{{ $activeLocale === 'en' ? 'State seasonal pattern scaled to this mandi\'s actual price level' : 'ರಾಜ್ಯ ಋತುಮಾನ ಮಾದರಿ — ಈ ಮಂಡಿ ದರ ಮಟ್ಟಕ್ಕೆ ಹೊಂದಿಸಲಾಗಿದೆ' }}">
                            {{ $activeLocale === 'en' ? '🔄 State pattern · local price' : '🔄 ರಾಜ್ಯ ಮಾದರಿ · ಸ್ಥಳೀಯ ಬೆಲೆ' }}
                        </span>
                    @endif
                </div>
                <div class="khc-title {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Best Months to Sell' : 'ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು' }}
                </div>
                <div class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="font-size:12px;font-weight:600;color:rgba(255,255,255,0.72);margin-top:2px;">
                    {{ $activeLocale === 'en' ? '5-year historical price seasonality & peak harvest window' : '5 ವರ್ಷಗಳ ಮಂಡಿ ಇತಿಹಾಸದ ಆಧಾರದ ಮೇಲೆ ಗರಿಷ್ಠ ಧಾರಣೆ ಸಿಗುವ ತಿಂಗಳುಗಳು' }}
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
                    Prices in <strong>{{ $seasonalAnalysis['market_name'] ?? 'Karnataka' }}</strong> are usually highest around <strong>{{ implode(', ', $seasonalAnalysis['peak_months_en']) }}</strong> — plan your harvest and sale for those months.
                    @if(($seasonalAnalysis['scope'] ?? '') === 'market_calibrated')
                        <span style="font-size:11px;font-weight:600;color:rgba(196,198,255,0.7);"> (Seasonal shape from statewide data, prices calibrated to this mandi's level.)</span>
                    @endif
                @else
                    {{ $seasonalAnalysis['lead_summary_en'] ?? 'Seasonal price variations based on historical mandi arrivals.' }}
                @endif
            @else
                @if(!empty($seasonalAnalysis['peak_months_kn']))
                    <strong>{{ !empty($seasonalAnalysis['market_name_kn']) ? $seasonalAnalysis['market_name_kn'] : ($seasonalAnalysis['market_name'] ?? 'ಕರ್ನಾಟಕ') }}</strong> ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಸಾಮಾನ್ಯವಾಗಿ <strong>{{ implode(', ', $seasonalAnalysis['peak_months_kn']) }}</strong> ತಿಂಗಳಲ್ಲಿ ಬೆಲೆ ಹೆಚ್ಚು — ಆ ಸಮಯಕ್ಕೆ ಬೆಳೆ ಮಾರಲು ಸಿದ್ಧರಾಗಿ.
                    @if(($seasonalAnalysis['scope'] ?? '') === 'market_calibrated')
                        <span style="font-size:11px;font-weight:600;color:rgba(196,198,255,0.7);"> (ರಾಜ್ಯ ಋತುಮಾನ ಮಾದರಿ — ಈ ಮಂಡಿ ಬೆಲೆಗೆ ಹೊಂದಿಸಲಾಗಿದೆ.)</span>
                    @endif
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
                    {{ $activeLocale === 'en' ? 'Annual Baseline' : 'ವಾರ್ಷಿಕ ಸರಾಸರಿ' }}{{ !empty($seasonalAnalysis['market_name']) ? ' ('.($activeLocale === 'kn' && !empty($seasonalAnalysis['market_name_kn']) ? $seasonalAnalysis['market_name_kn'] : $seasonalAnalysis['market_name']).')' : '' }}: <strong>₹{{ number_format($seasonalAnalysis['annual_baseline'],0) }}/{{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</strong>
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
            <div class="flex items-start gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full bg-[#1C5A2C] shrink-0 mt-0.5"></span>
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Best Months to Sell' : 'ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು' }}
                    </h2>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? '5-year historical price seasonality & peak harvest window' : '5 ವರ್ಷಗಳ ಮಂಡಿ ಇತಿಹಾಸದ ಆಧಾರದ ಮೇಲೆ ಗರಿಷ್ಠ ಧಾರಣೆ ಸಿಗುವ ತಿಂಗಳುಗಳು' }}
                    </p>
                </div>
            </div>
            <div class="inline-flex items-center justify-center leading-none text-xs font-bold text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} bg-stone-100 px-3 py-1.5 rounded-full border border-stone-200/80 shadow-2xs shrink-0">
                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Last 5 Years' : 'ಕಳೆದ 5 ವರ್ಷ' }}</span>
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

    <!-- 6. Mandi / Board Rates Comparison List (Ranked Highest to Lowest) -->
    <div class="space-y-3 sm:space-y-4" x-data="{ showAllMandis: false }">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full {{ $boardMeta ? ($boardMeta['theme'] === 'coffee' ? 'bg-amber-800' : 'bg-emerald-700') : 'bg-[#1C5A2C]' }} shrink-0 mt-0.5"></span>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl sm:text-2xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            @if($boardMeta)
                                {{ $activeLocale === 'en' ? $boardMeta['rates_heading_en'] : $boardMeta['rates_heading_kn'] }}
                            @else
                                {{ $activeLocale === 'en' ? 'Where to Sell Today? — Mandi Rates' : 'ಇಂದು ಎಲ್ಲಿ ಮಾರಬೇಕು? — ಮಂಡಿ ದರಗಳು' }}
                            @endif
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-stone-500 font-medium mt-0.5 flex-wrap {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        @if($boardMeta)
                            <span>{{ $boardMeta['authority'] }} {{ $activeLocale === 'en' ? 'Official Centres Near You' : 'ನಿಮ್ಮ ಹತ್ತಿರದ ಅಧಿಕೃತ ಕೇಂದ್ರಗಳ ದರ ಹೋಲಿಕೆ' }}</span>
                        @else
                            <span>{{ $activeLocale === 'en' ? 'Ranked by highest modal price near your location' : 'ನಿಮ್ಮ ಸಮೀಪದ ಮಾರುಕಟ್ಟೆಗಳಲ್ಲಿ ಇಂದಿನ ಗರಿಷ್ಠ ದರಗಳ ಆಧಾರದಲ್ಲಿ' }}</span>
                            @if(isset($displayMarketName))
                                <span class="text-stone-300">•</span>
                                <span class="inline-flex items-center gap-1 font-bold text-stone-700 bg-[#FAF8F5] px-2 py-0.5 rounded-md border border-[#D9CEB8] text-[11px]">
                                    📍 {{ $activeLocale === 'en' ? 'Currently: ' : 'ಪ್ರಸ್ತುತ: ' }}{{ $displayMarketName }}
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <span class="inline-flex items-center justify-center leading-none text-xs font-bold text-stone-600 bg-stone-100 px-3 py-1.5 rounded-full border border-stone-200 shadow-2xs shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Date: ' : 'ದಿನಾಂಕ: ' }}{{ $stats['date_formatted'] }}</span>
            </span>
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
                        {{ $activeLocale === 'en' ? 'No mandi market has reported prices for this date.' : 'ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ ಯಾವುದೇ ಮಾರುಕಟ್ಟೆಯಿಂದ ದರ ಮಾಹಿತಿ ಬಂದಿಲ್ಲ.' }}
                    @endif
                </p>
                <div class="pt-2">
                    <a href="{{ route('farmer.crops.index') }}" class="px-4 py-2 text-xs font-bold text-white bg-[#1C5A2C] rounded-xl hover:bg-[#154622] transition font-sans">
                        {{ $activeLocale === 'en' ? 'View Other Crops' : 'ಇತರ ಬೆಳೆಗಳನ್ನು ವೀಕ್ಷಿಸಿ' }}
                    </a>
                </div>
            </div>
        @else
            <!-- 2 Mandis Near to Current User Location (Ranked by Best Price) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
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

    <!-- 7. Historical Analytics & Interactive Price Trends -->
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
        $displayVolRating = $activeLocale === 'en'
            ? ($statisticalSummary['volatility_rating_en'] ?? 'Stable / Low Volatility')
            : ($statisticalSummary['volatility_rating_kn'] ?? $volRating);

        $initialChartData = [
            'labels' => $dailyTrends['labels'] ?? [],
            'modalPrices' => $dailyTrends['modal_prices'] ?? [],
            'minPrices' => $dailyTrends['min_prices'] ?? [],
            'maxPrices' => $dailyTrends['max_prices'] ?? [],
            'arrivals' => $dailyTrends['arrivals'] ?? [],
            'has_data' => !empty($dailyTrends['has_data']),
            'locale' => $activeLocale,
        ];

        $initialInsightText = '';
        if ($activeLocale === 'en') {
            if ($trendDir === 'up') {
                $initialInsightText = "Over the last {$rangeDays} days, modal rates rose from <strong>₹" . number_format($firstPrice) . "</strong> to <strong>₹" . number_format($lastPrice) . "</strong> <strong>(+{$changePct}%)</strong>. Market demand remains strong with favorable selling momentum.";
            } elseif ($trendDir === 'down') {
                $initialInsightText = "Over the last {$rangeDays} days, modal rates softened from <strong>₹" . number_format($firstPrice) . "</strong> to <strong>₹" . number_format($lastPrice) . "</strong> <strong>(-" . abs($changePct) . "%)</strong>. Local arrivals may be elevated; check the price forecast before committing volume.";
            } else {
                $initialInsightText = "Over the last {$rangeDays} days, prices held steady with an average of <strong>₹" . number_format($avgPrice) . "/quintal</strong>. Trading spread between high and low is <strong>₹" . number_format($priceSpread) . "</strong>.";
            }
        } else {
            if ($trendDir === 'up') {
                $initialInsightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹" . number_format($firstPrice) . "</strong> ರಿಂದ <strong>₹" . number_format($lastPrice) . "</strong> ಕ್ಕೆ <strong>(+{$changePct}%) ಏರಿಕೆಯಾಗಿದೆ</strong>. ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಉತ್ತಮವಾಗಿದ್ದು ಮಾರಾಟಕ್ಕೆ ಅನುಕೂಲಕರ ಪ್ರವೃತ್ತಿಯಿದೆ.";
            } elseif ($trendDir === 'down') {
                $initialInsightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ಬೆಲೆಯು <strong>₹" . number_format($firstPrice) . "</strong> ರಿಂದ <strong>₹" . number_format($lastPrice) . "</strong> ಕ್ಕೆ <strong>(-" . abs($changePct) . "%) ಇಳಿಕೆಯಾಗಿದೆ</strong>. ಸ್ಥಳೀಯ ಆವಕ ಹೆಚ್ಚಾಗಿರಬಹುದು, ಬೆಲೆ ಮುನ್ಸೂಚನೆ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧರಿಸಿ.";
            } else {
                $initialInsightText = "ಕಳೆದ {$rangeDays} ದಿನಗಳಲ್ಲಿ ದರವು ಸರಾಸರಿ <strong>₹" . number_format($avgPrice) . "/ಕ್ವಿಂಟಾಲ್</strong> ನೊಂದಿಗೆ ಸ್ಥಿರವಾಗಿದೆ. ಗರಿಷ್ಠ ಮತ್ತು ಕನಿಷ್ಠ ದರದ ಅಂತರ <strong>₹" . number_format($priceSpread) . "</strong> ಆಗಿದೆ.";
            }
        }

        $initialMetrics = [
            'max_price' => $maxPrice,
            'min_price' => $minPrice,
            'avg_price' => $avgPrice,
            'price_spread' => $priceSpread,
            'diff_high_avg' => max(0, $maxPrice - $avgPrice),
            'diff_avg_low' => max(0, $avgPrice - $minPrice),
            'observations_count' => $statisticalSummary['observations_count'] ?? count($dailyTrends['labels'] ?? []),
            'trend_dir' => $trendDir,
            'change_pct' => $changePct,
            'abs_change_pct' => abs($changePct),
            'vol_rating' => $displayVolRating,
            'vol_percent' => $volPercent,
            'vol_color' => $volColor,
            'sum_arrivals' => $sumArrivals,
            'insight_text' => $initialInsightText,
        ];
    @endphp

    <script>
        /**
         * Alpine.js Reactive Component for Seamless AJAX Historical Price Trends & Shimmer Effects
         */
        function historicalPriceTrend(config) {
            const rangeMap = {
                '7d': 7,
                '15d': 15,
                '30d': 30,
                '90d': 90,
                '365d': 365,
                '1y': 365
            };

            return {
                cropSlug: config.cropSlug || '',
                marketId: config.marketId || '',
                varietyId: config.varietyId || '',
                activeRange: config.activeRange || '30d',
                activeLocale: config.activeLocale || 'kn',
                standardUnit: config.standardUnit || 'Quintal',
                rangeDays: rangeMap[config.activeRange] || 30,
                isLoading: false,
                metrics: config.initialMetrics || {},
                chartData: config.initialChartData || {},

                init() {
                    this.renderChart();
                },

                renderChart() {
                    let attempts = 0;
                    const tryRender = () => {
                        if (this.chartData && this.chartData.has_data && typeof window.initPriceTrendChart === 'function') {
                            window.initPriceTrendChart('priceTrendCanvas', this.chartData);
                        } else if (attempts < 25) {
                            attempts++;
                            setTimeout(tryRender, 80);
                        }
                    };
                    this.$nextTick(() => {
                        tryRender();
                    });
                },

                formatCurrency(val) {
                    if (val === null || val === undefined || isNaN(val) || val <= 0) return '—';
                    return '₹' + Math.round(Number(val)).toLocaleString('en-IN');
                },

                formatNumber(val) {
                    if (val === null || val === undefined || isNaN(val) || val <= 0) return '0';
                    return Math.round(Number(val)).toLocaleString('en-IN');
                },

                async selectRange(rangeKey) {
                    if (this.activeRange === rangeKey || this.isLoading) return;

                    this.activeRange = rangeKey;
                    this.rangeDays = rangeMap[rangeKey] || 30;
                    this.isLoading = true;

                    try {
                        const ajaxUrl = new URL('{{ route('farmer.crops.trend-ajax', ['slug' => $crop->slug]) }}', window.location.origin);
                        ajaxUrl.searchParams.set('range', rangeKey);
                        if (this.marketId) ajaxUrl.searchParams.set('market_id', this.marketId);
                        if (this.varietyId) ajaxUrl.searchParams.set('variety', this.varietyId);
                        ajaxUrl.searchParams.set('lang', this.activeLocale);

                        const res = await fetch(ajaxUrl.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();

                        if (data && data.success) {
                            this.metrics = data.metrics || {};
                            this.chartData = data.chart_data || {};
                            this.$nextTick(() => {
                                this.renderChart();
                            });
                        }
                    } catch (err) {
                        console.error('[HistoricalPriceTrend] Failed to load trend data:', err);
                    } finally {
                        setTimeout(() => {
                            this.isLoading = false;
                        }, 120);
                    }
                }
            };
        }
        window.priceTrendInitialConfig = {
            cropSlug: @json($crop->slug),
            marketId: @json($selectedMarket?->id ?? ''),
            varietyId: @json($activeVarietyId ?? ''),
            activeRange: @json($rangeParam),
            activeLocale: @json($activeLocale),
            standardUnit: @json($crop->standard_unit ?? 'Quintal'),
            initialChartData: @json($initialChartData),
            initialMetrics: @json($initialMetrics)
        };
        window.historicalPriceTrend = historicalPriceTrend;
        if (window.Alpine) {
            window.Alpine.data('historicalPriceTrend', historicalPriceTrend);
        } else {
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('historicalPriceTrend', historicalPriceTrend);
            });
        }
    </script>

    <div x-data="historicalPriceTrend(window.priceTrendInitialConfig)"
        x-init="init()"
        class="bg-white border-2 border-[#D9CEB8] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 shadow-sm space-y-3 sm:space-y-4 relative overflow-hidden transition-all duration-300">

        <!-- Subtle Ambient Background Accent -->
        <div class="absolute -top-24 -right-24 w-72 h-72 bg-[#1C5A2C]/5 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Section Header Row -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 sm:gap-3 relative z-10 pb-2.5 sm:pb-3 border-b border-[#F0EAE1]">
            <div class="flex items-start gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full bg-[#1C5A2C] shrink-0 mt-0.5"></span>
                <div>
                    <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap">
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Price Trend & Market History' : 'ದರ ಪ್ರವೃತ್ತಿ & ಮಾರುಕಟ್ಟೆ ಇತಿಹಾಸ' }}
                        </h2>

                        <!-- Dynamic Trend Momentum Pill (Reactive via Alpine) -->
                        <template x-if="isLoading">
                            <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full text-[10.5px] bg-stone-100 border border-stone-200 text-stone-400 animate-pulse font-sans leading-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-stone-300 shrink-0"></span>
                                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Updating...' : 'ನವೀಕರಿಸಲಾಗುತ್ತಿದೆ...' }}</span>
                            </span>
                        </template>
                        <template x-if="!isLoading">
                            <span>
                                <template x-if="metrics.trend_dir === 'up'">
                                    <span class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] sm:text-[11px] font-black bg-emerald-50 text-[#16803C] border border-emerald-300 shadow-2xs leading-none">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#16803C] animate-pulse shrink-0"></span>
                                        <span class="inline-flex items-center leading-none font-sans">▲ +<span x-text="metrics.abs_change_pct"></span>%</span>
                                        <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada text-[10px]' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Rising' : 'ಏರಿಕೆ' }}</span>
                                    </span>
                                </template>
                                <template x-if="metrics.trend_dir === 'down'">
                                    <span class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] sm:text-[11px] font-black bg-rose-50 text-[#C0392B] border border-rose-300 shadow-2xs leading-none">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#C0392B] animate-pulse shrink-0"></span>
                                        <span class="inline-flex items-center leading-none font-sans">▼ -<span x-text="metrics.abs_change_pct"></span>%</span>
                                        <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada text-[10px]' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Falling' : 'ಇಳಿಕೆ' }}</span>
                                    </span>
                                </template>
                                <template x-if="metrics.trend_dir === 'stable'">
                                    <span class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] sm:text-[11px] font-black bg-amber-50 text-[#B45309] border border-amber-300 shadow-2xs leading-none">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                        <span class="inline-flex items-center leading-none font-sans">⟷ 0%</span>
                                        <span class="inline-flex items-center leading-none {{ $activeLocale === 'kn' ? 'font-kannada text-[10px]' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}</span>
                                    </span>
                                </template>
                            </span>
                        </template>
                    </div>

                    <div class="flex items-center gap-1.5 mt-0.5 text-xs text-stone-500 font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex-wrap">
                        @if($selectedMarket)
                            <span class="font-bold text-stone-700">
                                📍 {{ preg_replace('/\s+APMC$/i', '', $displayMarketName) }}
                            </span>
                            <span>
                                — <span x-text="rangeDays"></span>{{ $activeLocale === 'en' ? '-day modal auctions & arrival volume' : ' ದಿನಗಳ ಹರಾಜು ದರಗಳು & ಆವಕ ದಾಖಲೆ' }}
                            </span>
                        @else
                            <span class="font-bold text-stone-700">
                                🌐 {{ $activeLocale === 'en' ? ($boardMeta ? 'Karnataka Board Average' : 'Karnataka State Average') : ('ಕರ್ನಾಟಕ ' . ($boardMeta ? 'ಮಂಡಳಿ' : 'ರಾಜ್ಯ') . ' ಸರಾಸರಿ') }}
                            </span>
                            <span>
                                — <span x-text="rangeDays"></span>{{ $activeLocale === 'en' ? '-day statewide trend' : ' ದಿನಗಳ ರಾಜ್ಯ ಸರಾಸರಿ ವರದಿ' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Timeframe Filter Chips with Smooth AJAX Toggle (No Full Page Reload) -->
            <div class="flex items-center gap-1 bg-[#F4EFE6] p-1 rounded-xl border border-[#D9CEB8] self-start lg:self-auto font-sans shadow-2xs overflow-x-auto no-scrollbar">
                @php
                    $ranges = [
                        '7d' => ['kn' => '7 ದಿನ', 'en' => '7D', 'full_en' => '7 Days'],
                        '15d' => ['kn' => '15 ದಿನ', 'en' => '15D', 'full_en' => '15 Days'],
                        '30d' => ['kn' => '30 ದಿನ', 'en' => '30D', 'full_en' => '30 Days'],
                        '90d' => ['kn' => '3 ತಿಂಗಳು', 'en' => '90D', 'full_en' => '3 Months'],
                        '365d' => ['kn' => '1 ವರ್ಷ', 'en' => '1Y', 'full_en' => '1 Year'],
                    ];
                @endphp
                @foreach($ranges as $rKey => $rMeta)
                    <button type="button"
                            @click="selectRange('{{ $rKey }}')"
                            :class="activeRange === '{{ $rKey }}' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'text-stone-700 hover:text-stone-950 hover:bg-white/90 font-bold'"
                            class="px-2.5 sm:px-3 py-1 rounded-lg transition-all flex items-center gap-1 text-[11px] sm:text-xs whitespace-nowrap cursor-pointer tap-feedback active:scale-95">
                        <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? $rMeta['en'] : $rMeta['kn'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- 4 Classic Metric Intelligence Cards in High-Contrast Compact Grid with Shimmer Skeletons -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
            <!-- 1. Period High -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-[#16803C] shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1 bg-[#16803C]"></div>
                <!-- Shimmer Overlay during AJAX fetch -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-[#16803C] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="text-[11px] leading-none">▲</span>
                            <span>{{ $activeLocale === 'en' ? 'Period High' : 'ಅವಧಿಯ ಗರಿಷ್ಠ' }}</span>
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#16803C]"></span>
                    </div>
                    <div class="flex items-baseline gap-1 font-sans">
                        <span class="text-lg sm:text-xl font-black text-stone-950 tracking-tight" x-text="formatCurrency(metrics.max_price)">
                            {{ $maxPrice > 0 ? '₹' . number_format($maxPrice, 0) : '—' }}
                        </span>
                        <span class="text-[10px] font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}</span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <template x-if="metrics.max_price > 0 && metrics.avg_price > 0">
                        <div class="text-[10px] sm:text-[10.5px] font-bold text-[#16803C] flex items-center gap-1 font-sans">
                            <span class="font-black">+₹<span x-text="formatNumber(metrics.diff_high_avg)"></span></span>
                            <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'above average' : 'ಸರಾಸರಿಗಿಂತ ಹೆಚ್ಚು' }}</span>
                        </div>
                    </template>
                    <template x-if="!(metrics.max_price > 0 && metrics.avg_price > 0)">
                        <div class="text-[10px] text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Peak recorded rate' : 'ಗರಿಷ್ಠ ದಾಖಲಾದ ದರ' }}</div>
                    </template>
                </div>
            </div>

            <!-- 2. Period Low -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-[#C0392B] shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1 bg-[#C0392B]"></div>
                <!-- Shimmer Overlay -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-[#C0392B] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="text-[11px] leading-none">▼</span>
                            <span>{{ $activeLocale === 'en' ? 'Period Low' : 'ಅವಧಿಯ ಕನಿಷ್ಠ' }}</span>
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C0392B]"></span>
                    </div>
                    <div class="flex items-baseline gap-1 font-sans">
                        <span class="text-lg sm:text-xl font-black text-stone-950 tracking-tight" x-text="formatCurrency(metrics.min_price)">
                            {{ $minPrice > 0 ? '₹' . number_format($minPrice, 0) : '—' }}
                        </span>
                        <span class="text-[10px] font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}</span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <template x-if="metrics.min_price > 0 && metrics.avg_price > 0">
                        <div class="text-[10px] sm:text-[10.5px] font-bold text-[#C0392B] flex items-center gap-1 font-sans">
                            <span class="font-black">-₹<span x-text="formatNumber(metrics.diff_avg_low)"></span></span>
                            <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'below average' : 'ಸರಾಸರಿಗಿಂತ ಕಡಿಮೆ' }}</span>
                        </div>
                    </template>
                    <template x-if="!(metrics.min_price > 0 && metrics.avg_price > 0)">
                        <div class="text-[10px] text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Lowest recorded rate' : 'ಕನಿಷ್ಠ ದಾಖಲಾದ ದರ' }}</div>
                    </template>
                </div>
            </div>

            <!-- 3. Period Average -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-[#1C5A2C] shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1 bg-[#1C5A2C]"></div>
                <!-- Shimmer Overlay -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-stone-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="text-[11px] leading-none">⚖️</span>
                            <span>{{ $activeLocale === 'en' ? 'Period Average' : 'ಅವಧಿಯ ಸರಾಸರಿ' }}</span>
                        </span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1C5A2C]"></span>
                    </div>
                    <div class="flex items-baseline gap-1 font-sans">
                        <span class="text-lg sm:text-xl font-black text-stone-950 tracking-tight" x-text="formatCurrency(metrics.avg_price)">
                            {{ $avgPrice > 0 ? '₹' . number_format($avgPrice, 0) : '—' }}
                        </span>
                        <span class="text-[10px] font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">/{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}</span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <div class="text-[10px] sm:text-[10.5px] font-bold text-stone-600 flex items-center gap-1 font-sans">
                        <span class="font-black text-stone-900" x-text="metrics.observations_count"></span>
                        <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'days logged' : 'ದಿನಗಳ ದಾಖಲೆ' }}</span>
                    </div>
                </div>
            </div>

            <!-- 4. Volatility & Spread -->
            <div class="bg-[#FAF8F5] rounded-xl p-2.5 sm:p-3 border-2 border-[#E5DECE] hover:border-stone-400 shadow-2xs transition-all relative overflow-hidden group flex flex-col justify-between">
                <div class="absolute top-0 left-0 right-0 h-1"
                     :class="metrics.vol_color === 'rose' ? 'bg-[#C0392B]' : (metrics.vol_color === 'amber' ? 'bg-amber-600' : 'bg-[#16803C]')"></div>
                <!-- Shimmer Overlay -->
                <div x-show="isLoading" class="absolute inset-0 bg-[#FAF8F5]/85 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity">
                    <div class="w-full h-full p-2.5 space-y-2 animate-pulse">
                        <div class="h-3 w-16 bg-stone-200 rounded"></div>
                        <div class="h-5 w-24 bg-stone-200 rounded"></div>
                        <div class="h-2.5 w-20 bg-stone-200 rounded"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider text-stone-700 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="metrics.vol_color === 'rose' ? 'bg-[#C0392B]' : (metrics.vol_color === 'amber' ? 'bg-amber-600' : 'bg-[#16803C]')"></span>
                            <span>{{ $activeLocale === 'en' ? 'Volatility' : 'ಏರಿಳಿತ' }}</span>
                        </span>
                        <span class="text-[9.5px] font-black font-sans px-1 py-0.2 rounded bg-[#E5DECE] text-stone-700">
                            <span x-text="metrics.vol_percent"></span>%
                        </span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xs sm:text-sm font-black text-stone-950 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} tracking-tight line-clamp-1 leading-snug"
                              x-text="metrics.vol_rating">
                            {{ $displayVolRating }}
                        </span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-[#EAE3D2] mt-1.5">
                    <div class="text-[10px] sm:text-[10.5px] font-bold text-stone-600 flex items-center gap-1 font-sans">
                        <span class="text-stone-400 font-semibold {{ $activeLocale === 'kn' ? 'font-kannada text-[9.5px]' : '' }}">{{ $activeLocale === 'en' ? 'Spread:' : 'ಅಂತರ:' }}</span>
                        <span class="font-black text-stone-900" x-text="formatCurrency(metrics.price_spread)"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actionable Farmer Market Intelligence Callout Strip -->
        <div x-show="chartData.has_data" class="bg-[#FAF6EE] border-2 border-[#D9CEB8] rounded-xl sm:rounded-2xl p-2.5 sm:p-3.5 flex flex-col md:flex-row md:items-center justify-between gap-2.5 shadow-2xs relative overflow-hidden {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <!-- Shimmer Bar -->
            <div x-show="isLoading" class="absolute inset-0 bg-[#FAF6EE]/90 backdrop-blur-[1px] flex items-center px-4 z-10 transition-opacity">
                <div class="w-full space-y-2 animate-pulse">
                    <div class="h-3 w-32 bg-amber-200/80 rounded"></div>
                    <div class="h-3 w-3/4 bg-amber-200/50 rounded"></div>
                </div>
            </div>
            <div class="flex items-start gap-2 sm:gap-2.5">
                <div class="w-6 h-6 rounded-lg bg-amber-100 border border-amber-300 flex items-center justify-center shrink-0 text-xs shadow-2xs">
                    💡
                </div>
                <div class="text-xs text-stone-800 leading-snug space-y-0.5">
                    <div class="font-black text-stone-950 flex items-center gap-1.5 text-[11.5px] sm:text-xs">
                        <span>{{ $activeLocale === 'en' ? 'Market Movement Analysis' : 'ದರ ಪ್ರವೃತ್ತಿ ವಿಶ್ಲೇಷಣೆ' }}</span>
                    </div>
                    <p class="text-[11px] sm:text-[11.5px] text-stone-700 font-medium" x-html="metrics.insight_text">
                        @if($activeLocale === 'en')
                            @if($trendDir === 'up')
                                Over the last {{ $rangeDays }} days, modal rates rose from <strong>₹{{ number_format($firstPrice) }}</strong> to <strong>₹{{ number_format($lastPrice) }}</strong> <strong>(+{{ abs($changePct) }}%)</strong>. Market demand remains strong with favorable selling momentum.
                            @elseif($trendDir === 'down')
                                Over the last {{ $rangeDays }} days, modal rates softened from <strong>₹{{ number_format($firstPrice) }}</strong> to <strong>₹{{ number_format($lastPrice) }}</strong> <strong>(-{{ abs($changePct) }}%)</strong>. Local arrivals may be elevated; check the price forecast before committing volume.
                            @else
                                Over the last {{ $rangeDays }} days, prices held steady with an average of <strong>₹{{ number_format($avgPrice) }}/{{ strtolower($crop->standard_unit ?? 'quintal') }}</strong>. Trading spread between high and low is <strong>₹{{ number_format($priceSpread) }}</strong>.
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
                    </p>
                </div>
            </div>
            <div x-show="metrics.sum_arrivals > 0" class="shrink-0 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border-2 border-[#D9CEB8] text-[10.5px] sm:text-[11px] font-extrabold text-stone-800 self-start md:self-auto shadow-2xs font-sans">
                <span class="text-[#1C5A2C]">📦</span>
                <span>{{ $activeLocale === 'en' ? 'Arrivals: ' : 'ಆವಕ: ' }}<strong><span x-text="formatNumber(metrics.sum_arrivals)"></span> {{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</strong></span>
            </div>
        </div>

        <!-- Chart Canvas Container with Modern Classic Framing -->
        <div class="rounded-xl sm:rounded-2xl bg-[#FAF8F5] border-2 border-[#E5DECE] p-2.5 sm:p-3.5 space-y-2.5 relative">
            <!-- Toolbar above Chart -->
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs px-1">
                <div class="flex items-center gap-3.5 flex-wrap">
                    <div class="flex items-center gap-1.5 text-stone-900 font-extrabold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="w-3 h-1.5 rounded-full bg-[#16803C] inline-block"></span>
                        @if($activeLocale === 'en')
                            <span>Modal Rate (₹)</span>
                        @else
                            <span>ಮಾದರಿ ದರ (₹)</span>
                        @endif
                    </div>
                    <div x-show="metrics.sum_arrivals > 0" class="flex items-center gap-1.5 text-stone-600 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="w-2.5 h-2.5 rounded-xs bg-slate-300 inline-block border border-slate-400/50"></span>
                        @if($activeLocale === 'en')
                            <span>Daily Arrivals (Qtl)</span>
                        @else
                            <span>ದೈನಂದಿನ ಆವಕ (ಕ್ವಿಂಟಾಲ್)</span>
                        @endif
                    </div>
                </div>
                <div class="text-[10.5px] font-semibold text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} flex items-center gap-1">
                    <span>👆 {{ $activeLocale === 'en' ? 'Hover or tap chart points for details' : 'ಗ್ರಾಫ್ ಮೇಲೆ ಸ್ಪರ್ಶಿಸಿ ವಿವರ ನೋಡಿ' }}</span>
                </div>
            </div>

            <!-- Canvas Wrapper (Compact Height: h-64 sm:h-72 md:h-80) with Subtle Shimmer Loader -->
            <div class="relative w-full h-64 sm:h-72 md:h-80 bg-white rounded-xl p-2 border border-[#EAE3D2] shadow-2xs overflow-hidden">
                <!-- Loading Shimmer Effect for Chart -->
                <div x-show="isLoading" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute inset-0 bg-white/80 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center p-6 space-y-3">
                    <div class="w-10 h-10 rounded-full border-3 border-[#1C5A2C]/20 border-t-[#1C5A2C] animate-spin"></div>
                    <div class="text-xs font-bold text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Updating price trends...' : 'ದರ ಇತಿಹಾಸ ನವೀಕರಿಸಲಾಗುತ್ತಿದೆ...' }}
                    </div>
                </div>

                <div x-show="chartData.has_data" class="w-full h-full">
                    <canvas id="priceTrendCanvas"></canvas>
                </div>

                <div x-show="!chartData.has_data && !isLoading" class="h-full flex flex-col items-center justify-center text-center p-6 text-stone-400">
                    <span class="text-4xl mb-2">📊</span>
                    <span class="font-bold text-stone-700 text-sm {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Insufficient price history recorded for this period' : 'ಈ ಅವಧಿಗೆ ಸಾಕಷ್ಟು ದರ ಇತಿಹಾಸ ದಾಖಲಾಗಿಲ್ಲ' }}
                    </span>
                    <span class="text-xs text-stone-400 mt-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} max-w-sm">
                        {{ $activeLocale === 'en' ? 'As more trading days are recorded by mandis, trend and arrival charts will activate automatically.' : 'ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಹೆಚ್ಚಿನ ದಿನಗಳ ವಹಿವಾಟು ದಾಖಲಾದಂತೆ ಪ್ರವೃತ್ತಿ ಮತ್ತು ಆವಕ ನಕ್ಷೆ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.' }}
                    </span>
                </div>
            </div>

            <!-- Chart Footer Meta -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-[10.5px] text-stone-500 px-1 pt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} border-t border-[#EAE3D2]">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-medium">{{ $activeLocale === 'en' ? '🟢 Green curve: Modal rate (₹/Qtl)' : '🟢 ಹಸಿರು ಗೆರೆ: ದರ (₹/ಕ್ವಿಂಟಾಲ್)' }}</span>
                    <template x-if="metrics.sum_arrivals > 0">
                        <span class="inline-flex items-center gap-1.5">
                            <span>&bull;</span>
                            <span class="font-medium">{{ $activeLocale === 'en' ? '🩶 Grey bars: Daily arrival volume' : '🩶 ಬೂದು ಬಾರ್: ಮಾರುಕಟ್ಟೆ ಆವಕ' }}</span>
                        </span>
                    </template>
                </div>
                <div class="text-stone-400 font-sans text-[10px] font-semibold">
                    {{ $activeLocale === 'en' ? 'Source: Mandi Daily Ingestion / Agmarknet Karnataka' : 'ಮೂಲ: ಮಂಡಿ ದೈನಂದಿನ ದರ / Agmarknet Karnataka' }}
                </div>
            </div>
        </div>
    </div>



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
