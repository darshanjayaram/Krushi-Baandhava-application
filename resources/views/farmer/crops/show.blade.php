@extends('layouts.farmer')

@section('title', ($crop->name_kn ? $crop->name_kn . ' (' . $crop->name . ')' : $crop->name) . ' — ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು & ಮುನ್ಸೂಚನೆ')

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
    $displayMarketName = $selectedMarket ? $selectedMarket->name : ($activePriceItem ? $activePriceItem->market->name : 'ಕರ್ನಾಟಕ ಸರಾಸರಿ (State Avg)');
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
            <span class="font-kannada">ಹಿಂದಕ್ಕೆ</span>
            <span class="text-[11px] font-sans text-stone-400 font-normal">Back</span>
        </a>

        <div class="flex items-center gap-2 text-xs font-semibold text-stone-500">
            <a href="{{ route('farmer.crops.index') }}" class="px-2.5 py-0.5 rounded-full bg-white border border-[#E8DFC8] text-stone-700 hover:text-emerald-800 transition">
                {{ $crop->category ? ($crop->category->name_kn ?? $crop->category->name) : 'ಬೆಳೆಗಳು' }}
            </a>
            <span>&bull;</span>
            <span class="text-stone-900 font-bold">{{ $crop->name_kn ?? $crop->name }}</span>
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
                        <span class="text-[10px] opacity-90 font-kannada font-normal">• {{ $boardMeta['badge_kn'] }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/95 backdrop-blur-md text-xs font-extrabold text-stone-800 shadow-sm border border-stone-200/60 font-sans">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Reliable</span>
                        <span class="text-[10px] text-stone-500 font-kannada font-normal">• ಅಧಿಕೃತ</span>
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
                    {{ strtoupper($crop->category ? $crop->category->name : 'COMMODITY') }}
                </div>
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white font-sans drop-shadow-sm">
                    {{ $crop->name }}
                </h1>
                @if($crop->name_kn)
                    <div class="text-xl sm:text-2xl font-black text-amber-200 font-kannada">
                        {{ $crop->name_kn }}
                    </div>
                @endif
                <div class="pt-1 flex items-center gap-2 text-xs text-white/80 font-sans">
                    <span>Standard Unit: <strong>{{ $crop->standard_unit ?? 'Quintal' }}</strong></span>
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
                    <span class="font-extrabold tracking-wider text-stone-400 uppercase font-sans">
                        CURRENT PRICE
                    </span>
                    <span class="text-[11px] font-semibold text-stone-500 font-sans">
                        Updated: {{ $stats['date_formatted'] }}
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
                        <div class="text-sm font-semibold text-stone-400 font-sans">
                            / {{ strtolower($crop->standard_unit ?? 'quintal') }}
                        </div>
                    @endif

                    @if($activePriceItem && $activePriceItem->price_spread > 0)
                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-xs font-bold font-sans">
                            ↑ +₹{{ number_format($activePriceItem->price_spread, 0) }}
                        </span>
                    @endif
                </div>

                <!-- Active Mandi / Centre Info Badge (Negilu Krushi Alignment) -->
                <div class="flex flex-wrap items-center gap-2 text-xs text-stone-600 pt-0.5 font-sans">
                    <span class="font-bold text-stone-800">
                        {{ $crop->standard_unit ?? 'Quintal' }} • @ {{ strtoupper($displayMarketName) }}
                    </span>

                    @if(!empty($isNearestFallback) && !empty($nearestDistanceKm))
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#fff4e5] text-[#9a5b00] border border-[#ffe0b2] text-[11px] font-extrabold whitespace-nowrap shadow-2xs"
                              title="No market for this crop in your district — nearest one shown">
                            📍 nearest market • {{ round($nearestDistanceKm) }} km
                        </span>
                    @elseif(!empty($nearestDistanceKm))
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold whitespace-nowrap">
                            📍 {{ round($nearestDistanceKm) }} km away
                        </span>
                    @endif

                    <span class="text-stone-300">•</span>
                    <span class="text-stone-500 font-medium">as of {{ \Carbon\Carbon::parse($latestDate)->format('d M') }}</span>

                    @if($boardMeta)
                        <span class="text-amber-900 font-bold font-kannada text-[11px]">({{ $boardMeta['badge_kn'] }})</span>
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
                            <div class="text-[11px] font-extrabold tracking-wide uppercase text-stone-300">
                                {{ $displayMarketPrices->first()->variety?->name ?? 'Standard Grade' }}
                                @if($displayMarketPrices->first()->variety?->name_kn)
                                    <span class="font-kannada font-normal text-stone-400">({{ $displayMarketPrices->first()->variety->name_kn }})</span>
                                @endif
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
                    <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider font-sans">
                        PICK YOUR GRADE • ಗ್ರೇಡ್ ಆಯ್ಕೆಮಾಡಿ
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        @foreach($displayMarketPrices as $smp)
                            @php
                                $isVarSelected = ($varietyId == $smp->variety_id) || (!$varietyId && $loop->first);
                            @endphp
                            <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $smp->variety_id, 'market' => $displayMarketName])) }}"
                               class="px-3.5 py-2 rounded-2xl font-bold transition border flex flex-col items-start gap-0.5 cursor-pointer {{ $isVarSelected ? 'bg-stone-900 text-white border-stone-900 shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
                                <span class="text-[11px] {{ $isVarSelected ? 'text-stone-300' : 'text-stone-600' }}">
                                    {{ $smp->variety?->name ?? 'Standard' }}
                                    @if($smp->variety?->name_kn)
                                        <span class="font-kannada font-normal opacity-80">({{ $smp->variety->name_kn }})</span>
                                    @endif
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
                    <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider font-sans">
                        PICK YOUR GRADE
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'market' => $marketParam])) }}"
                           class="px-3.5 py-2 rounded-xl font-bold transition border {{ empty($varietyId) ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-2xs' : 'bg-stone-50 text-stone-700 hover:bg-stone-100 border-stone-200' }}">
                            <span>ಎಲ್ಲಾ ತಳಿ / FAQ (All Grades)</span>
                        </a>

                        @foreach($availableVarieties as $v)
                            @php
                                $isVarSelected = ($varietyId == $v->id);
                            @endphp
                            <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $v->id, 'market' => $marketParam])) }}"
                               class="px-3.5 py-2 rounded-xl font-bold transition border flex items-center gap-1.5 {{ $isVarSelected ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-2xs' : 'bg-stone-50 text-stone-700 hover:bg-stone-100 border-stone-200' }}">
                                <span>{{ $v->name }}</span>
                                @if($v->name_kn)
                                    <span class="font-kannada font-normal opacity-90">({{ $v->name_kn }})</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- C. "VIEW DIFFERENT MARKET / CENTRE" (Wrapped Grid Like Negilu Krushi) -->
            <div class="space-y-2 pt-2 border-t border-stone-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="text-[11px] font-extrabold text-stone-400 uppercase tracking-wider font-sans">
                        @if($boardMeta)
                            VIEW DIFFERENT CENTRE • {{ $boardMeta['centre_label_kn'] }}
                        @else
                            VIEW DIFFERENT MARKET • ಮಾರುಕಟ್ಟೆ ಬದಲಿಸಿ (ಕರ್ನಾಟಕ ಮಂಡಿ ಆಯ್ಕೆ)
                        @endif
                    </div>
                    
                    <!-- Mandi / Centre Dropdown Selector for 30+ Mandis -->
                    <form method="GET" action="{{ route('farmer.crop.detail', $crop->id) }}" class="flex items-center gap-2">
                        @if($varietyId)
                            <input type="hidden" name="variety" value="{{ $varietyId }}">
                        @endif
                        <select name="market" 
                                onchange="this.form.submit()" 
                                class="text-xs font-bold text-stone-800 bg-stone-50 border border-stone-200 rounded-lg px-2.5 py-1 focus:ring-1 focus:ring-emerald-600 outline-none cursor-pointer">
                            <option value="">
                                @if($boardMeta)
                                    {{ $boardMeta['centre_label_kn'] }} (All Centres)
                                @else
                                    ಕರ್ನಾಟಕ APMC ಮಂಡಿ ಆಯ್ಕೆ (All Mandis)
                                @endif
                            </option>
                            @foreach($availableMarkets as $m)
                                <option value="{{ $m->name }}" {{ ($selectedMarket && $selectedMarket->id === $m->id) ? 'selected' : '' }}>
                                    {{ $m->name }}{{ $boardMeta ? '' : (str_ends_with(strtolower($m->name), 'apmc') ? '' : ' APMC') }} ({{ $m->district?->name ?? 'KA' }}){{ isset($m->distance_km) && $m->distance_km < 1000 ? ' • ' . round($m->distance_km) . ' km' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($marketParam)
                            <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $varietyId])) }}" 
                               class="text-xs text-stone-400 hover:text-stone-700 font-bold" title="Clear filter">✕</a>
                        @endif
                    </form>
                </div>

                <!-- Quick Mandi / Centre Pills in Wrap Row (Zero Horizontal Scroll!) -->
                <div class="flex flex-wrap gap-2 text-xs max-h-48 overflow-y-auto pr-1">
                    @foreach($availableMarkets as $am)
                        @php
                            $isMktSelected = ($selectedMarket && $selectedMarket->id === $am->id);
                        @endphp
                        <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'market' => $am->name])) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full font-bold transition border cursor-pointer {{ $isMktSelected ? 'bg-[#1C5A2C] text-white border-[#1C5A2C] shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-50 border-stone-200' }}">
                            @if($isMktSelected)
                                <span class="text-amber-300">★</span>
                            @endif
                            <span class="font-sans uppercase text-[12px] font-extrabold tracking-wide">{{ $am->name }}</span>
                            @if(isset($am->distance_km) && $am->distance_km < 1000)
                                <span class="text-[10px] {{ $isMktSelected ? 'text-emerald-200' : 'text-stone-400' }} font-medium">({{ round($am->distance_km) }}km)</span>
                            @endif
                            @if(isset($am->today_modal_price) && $am->today_modal_price > 0)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-black font-sans {{ $isMktSelected ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-800' }}">
                                    ₹{{ number_format($am->today_modal_price, 0) }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- D. Market Advisory / Sentiment Banner -->
            <div class="rounded-2xl p-4 border transition {{ $forecastDir === 'down' ? 'bg-amber-50/80 border-amber-200/90 text-amber-950' : ($forecastDir === 'up' ? 'bg-emerald-50/80 border-emerald-200/90 text-emerald-950' : 'bg-stone-50 border-stone-200 text-stone-800') }}">
                <div class="flex items-start gap-3">
                    <span class="text-2xl shrink-0">
                        {{ $forecastDir === 'down' ? '⏰' : ($forecastDir === 'up' ? '📈' : '💡') }}
                    </span>
                    <div class="space-y-0.5 text-xs">
                        <div class="font-extrabold text-sm flex items-center gap-2">
                            @if($forecastDir === 'down')
                                <span>ಮಾರಾಟಕ್ಕೆ ಸೂಕ್ತ ಸಮಯ (Sell now)</span>
                            @elseif($forecastDir === 'up')
                                <span>ಧಾರಣೆ ಏರಿಕೆಯ ಮುನ್ಸೂಚನೆ (Hold / Watch)</span>
                            @else
                                <span>ಮಾರುಕಟ್ಟೆ ಸಲಹೆ (Market Advisory)</span>
                            @endif
                        </div>
                        <p class="leading-relaxed font-kannada text-stone-600">
                            @if($forecastDir === 'down')
                                ಮುಂದಿನ ವಾರಗಳಲ್ಲಿ ಮಾರುಕಟ್ಟೆಗೆ ಆವಕ ಹೆಚ್ಚಾಗುವ ಮುನ್ಸೂಚನೆ ಇದ್ದು, ದರಗಳು ಕೊಂಚ ಇಳಿಕೆಯಾಗುವ ಸಾಧ್ಯತೆಯಿದೆ. ಸದ್ಯದ ಉತ್ತಮ ಬೆಲೆಯಲ್ಲಿ ಮಾರಾಟ ಮಾಡುವುದು ಸೂಕ್ತ.
                            @elseif($forecastDir === 'up')
                                ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಹೆಚ್ಚಾಗುವ ಲಕ್ಷಣಗಳು ಕಂಡುಬರುತ್ತಿದ್ದು, ಮುಂದಿನ ದಿನಗಳಲ್ಲಿ ದರ ಇನ್ನಷ್ಟು ಸುಧಾರಿಸುವ ಸಂಭವವಿದೆ.
                            @else
                                ಮಾರುಕಟ್ಟೆ ದರಗಳು ಸ್ಥಿರವಾಗಿದ್ದು, ಹತ್ತಿರದ ಮಂಡಿಗಳ ಸಾರಿಗೆ ವೆಚ್ಚ ಮತ್ತು ಆವಕ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧಾರ ಕೈಗೊಳ್ಳಿ.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- E. Action Buttons: WhatsApp Share & Where to Sell Simulator -->
            @php
                $sharePriceText = "🌾 *ಕೃಷಿ ಬಾಂಧವ — " . $crop->name . ($crop->name_kn ? ' (' . $crop->name_kn . ')' : '') . "*\n"
                    . "📍 " . ($boardMeta ? 'ಕೇಂದ್ರ: ' : 'ಮಾರುಕಟ್ಟೆ: ') . $displayMarketName . ($boardMeta ? '' : (str_ends_with(strtolower($displayMarketName), 'apmc') ? '' : ' APMC')) . "\n"
                    . "💰 ಇಂದಿನ ಮಾದರಿ ದರ: ₹" . number_format($displayModal, 0) . " / " . ($crop->standard_unit ?? 'Quintal') . "\n"
                    . ($perKgPrice ? "⚖️ ಪ್ರತಿ ಕೆ.ಜಿ ಗೆ: ≈ ₹" . $perKgPrice . "/kg\n" : "")
                    . "📅 ದಿನಾಂಕ: " . $stats['date_formatted'] . "\n"
                    . "👉 ಸಂಪೂರ್ಣ ದರ & ಮುನ್ಸೂಚನೆ ವೀಕ್ಷಿಸಿ: " . url()->current();
                $whatsappDetailUrl = "https://wa.me/?text=" . rawurlencode($sharePriceText);
            @endphp

            <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <!-- Bright WhatsApp Button (Negilu Krishi Bright Green) -->
                <a href="{{ $whatsappDetailUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="w-full sm:flex-1 py-3.5 px-6 rounded-2xl bg-[#25D366] hover:bg-[#20BD5A] text-white font-black text-sm tracking-wide shadow-sm hover:shadow transition transform active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
                    <span class="text-lg">💬</span>
                    <span>Share price</span>
                    <span class="font-kannada font-bold text-xs opacity-90">(ದರ ಶೇರ್ ಮಾಡಿ)</span>
                </a>

                <!-- Net Profit Simulator Button (Where to Sell) -->
                <a href="{{ route('farmer.decision.where-to-sell', ['crop' => $crop->slug]) }}"
                   class="w-full sm:w-auto py-3.5 px-5 rounded-2xl bg-stone-900 hover:bg-stone-800 text-white font-bold text-xs shadow-sm transition transform active:scale-95 flex items-center justify-center gap-2 shrink-0">
                    <span>⚖️ Where to Sell?</span>
                    <span class="font-kannada font-bold opacity-90">(ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ)</span>
                    <span class="text-xs">&rarr;</span>
                </a>
            </div>

        </div>

    </div>

    <!-- 3. 4-Metric State Overview Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Highest Rate -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 font-kannada block">
                {{ $boardMeta ? 'ಅತ್ಯಧಿಕ ದರ (Highest)' : 'ರಾಜ್ಯದ ಗರಿಷ್ಠ ದರ (Highest)' }}
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
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 font-kannada block">
                ಕನಿಷ್ಠ ದರ (Lowest)
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
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 font-kannada block">
                {{ $boardMeta ? 'ಮಂಡಳಿ ಸರಾಸರಿ (Average)' : 'ರಾಜ್ಯ ಸರಾಸರಿ (Average)' }}
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1 font-sans">
                {{ $stats['avg_modal'] > 0 ? '₹' . number_format($stats['avg_modal'], 0) : '—' }}
            </div>
            <div class="text-xs font-semibold text-stone-500 mt-0.5 font-kannada">
                {{ $stats['total_mandis'] }} {{ $boardMeta ? 'ಕೇಂದ್ರಗಳಿಂದ' : 'ಮಂಡಿಗಳಿಂದ' }}
            </div>
        </div>

        <!-- Arrivals -->
        <div class="bg-white p-4 rounded-2xl border border-[#E8DFC8] shadow-2xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 font-kannada block">
                ಒಟ್ಟು ಆವಕ (Arrivals)
            </span>
            <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1 font-sans">
                {{ number_format($stats['total_arrivals'], 1) }}
            </div>
            <div class="text-xs font-semibold text-stone-500 mt-0.5 font-kannada">
                ಕ್ವಿಂಟಾಲ್ (Quintals)
            </div>
        </div>
    </div>

    <!-- 4. "What's next" Forecast Horizons (Negilu Krishi 4-Card Projections) -->
    <div class="bg-white rounded-3xl p-5 sm:p-7 border border-[#E8DFC8] shadow-sm space-y-5">
        
        <!-- Section Header with Green Bar -->
        <div class="flex items-center justify-between pb-3 border-b border-stone-100">
            <div class="flex items-center gap-2.5">
                <span class="w-1.5 h-6 rounded-full bg-[#1C5A2C]"></span>
                <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight font-sans">
                    What's next
                </h2>
                <span class="text-xs font-bold text-stone-500 font-kannada">
                    • ದರ ಮುನ್ಸೂಚನೆ & ನಿರೀಕ್ಷಿತ ಶ್ರೇಣಿ (Price Forecast & Projections)
                </span>
            </div>
            <div class="text-xs font-bold text-emerald-800 font-sans bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                Updated daily • ದೈನಂದಿನ ಅಪ್ಡೇಟ್
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
                            <span class="text-[11px] font-black tracking-wider text-stone-700 uppercase font-sans">
                                {{ $horizonMeta['en'] }}
                            </span>
                            <span class="text-[11px] font-bold text-stone-400 font-sans">
                                {{ $h['target_date_formatted'] }}
                            </span>
                        </div>

                        <!-- Expected Price -->
                        <div class="space-y-1">
                            <div class="text-[10px] font-bold text-stone-400 uppercase font-sans">
                                EXPECTED PRICE • {{ $horizonMeta['kn'] }}
                            </div>
                            <div class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight font-sans flex items-baseline gap-1">
                                <span>₹{{ number_format($h['expected_price'], 0) }}</span>
                                <span class="text-xs font-semibold text-stone-400 font-sans">/ ಕ್ವಿಂ</span>
                            </div>

                            <!-- Percentage movement indicator -->
                            <div class="text-xs font-bold font-sans flex items-center gap-1.5">
                                @if($h['direction'] === 'up')
                                    <span class="text-emerald-700">▲ +{{ $h['percentage_change'] }}% ಏರಿಕೆ ಸಾಧ್ಯತೆ</span>
                                @elseif($h['direction'] === 'down')
                                    <span class="text-rose-600">▼ {{ $h['percentage_change'] }}% ಇಳಿಕೆ ಸಾಧ್ಯತೆ</span>
                                @else
                                    <span class="text-stone-500">▬ ಸ್ಥಿರ ಧಾರಣೆ</span>
                                @endif
                            </div>
                        </div>

                        <!-- Confidence & Range Footer -->
                        <div class="pt-2.5 border-t border-stone-200/70 space-y-1 text-xs">
                            <div class="flex items-center justify-between text-stone-500 font-sans">
                                <span class="text-[11px]">Range:</span>
                                <span class="font-bold text-stone-800">
                                    ₹{{ number_format($h['lower_bound'], 0) }} – ₹{{ number_format($h['upper_bound'], 0) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] font-sans">
                                <span class="text-stone-400">Confidence:</span>
                                <span class="font-extrabold {{ $h['confidence_score'] >= 80 ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $h['confidence_score'] }}%
                                </span>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <!-- Data Insufficiency Notice -->
            <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-950 flex items-start gap-3">
                <span class="text-xl shrink-0">ℹ️</span>
                <div class="space-y-1 text-xs font-kannada">
                    <div class="font-bold text-sm text-amber-900 font-sans">Data Insufficiency Notice</div>
                    <p class="leading-relaxed">
                        {{ $forecast['message_kn'] ?? 'ವಿಶ್ವಾಸಾರ್ಹ ಮುನ್ಸೂಚನೆಗೆ ಕನಿಷ್ಠ 30 ದಿನಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ.' }}
                    </p>
                    <p class="text-amber-800/80">
                        ಕೃಷಿ ಬಾಂಧವ ಕೃತಕ ಅಂದಾಜುಗಳನ್ನು ಪ್ರದರ್ಶಿಸುವುದಿಲ್ಲ. ಮಂಡಿಗಳಿಂದ 30 ದಿನಗಳ ನಿರಂತರ ದರಗಳು ದಾಖಲಾದ ನಂತರ ನಿಖರ ಗಣಿತೀಯ ಮುನ್ಸೂಚನೆ ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.
                    </p>
                </div>
            </div>
        @endif

        <!-- Disclaimer -->
        <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/80 text-[11px] text-stone-500 leading-relaxed flex items-center gap-2 font-kannada">
            <span class="text-stone-400 shrink-0">⚖️</span>
            <span>
                <strong>ಗಮನಿಸಿ (Disclaimer):</strong> ಇದು ಕೇವಲ ಹಿಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳ ಪ್ರವೃತ್ತಿ ಆಧಾರಿತ ಗಣಿತೀಯ ಅಂದಾಜು. ನೈಜ ದರಗಳು ಹವಾಮಾನ ಪರಿಸ್ಥಿತಿ, ಮಾರುಕಟ್ಟೆಯ ಆವಕ ಪ್ರಮಾಣ ಮತ್ತು ಸರ್ಕಾರದ ನೀತಿಗಳಿಂದ ವ್ಯತ್ಯಾಸವಾಗಬಹುದು.
            </span>
        </div>

    </div>

    <!-- 5. Mandi / Board Rates Comparison List (Ranked Highest to Lowest) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-6 rounded-full {{ $boardMeta ? ($boardMeta['theme'] === 'coffee' ? 'bg-amber-800' : 'bg-emerald-700') : 'bg-[#1C5A2C]' }}"></span>
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight font-kannada">
                        @if($boardMeta)
                            {{ $boardMeta['rates_heading_kn'] }} ({{ $boardMeta['rates_heading_en'] }})
                        @else
                            ಮಂಡಿವಾರು ದರ ಹೋಲಿಕೆ (Ranked by Best Price)
                        @endif
                    </h2>
                    <p class="text-xs text-stone-500 font-kannada">
                        @if($boardMeta)
                            @if($marketParam)
                                ಆಯ್ಕೆಯಾದ ಕೇಂದ್ರದ ದರ ವಿವರಗಳು ({{ $marketParam }})
                            @else
                                {{ $boardMeta['authority'] }} ಅಧಿಕೃತ ಖರೀದಿ ಮತ್ತು ಕ್ಯೂರಿಂಗ್ ಕೇಂದ್ರಗಳು
                            @endif
                        @else
                            @if($marketParam)
                                ಆಯ್ಕೆಯಾದ ಮಂಡಿಯ ದರ ವಿವರಗಳು ({{ $marketParam }})
                            @else
                                ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಮಂಡಿಗಳನ್ನು ಅತ್ಯಧಿಕ ದರದಿಂದ ಇಳಿಕೆ ಕ್ರಮದಲ್ಲಿ ಪ್ರದರ್ಶಿಸಲಾಗಿದೆ
                            @endif
                        @endif
                    </p>
                </div>
            </div>
            <span class="text-xs text-stone-500 font-sans">ದಿನಾಂಕ: {{ $stats['date_formatted'] }}</span>
        </div>

        @if($mandiPrices->isEmpty())
            <div class="bg-white rounded-3xl p-8 text-center border border-[#E8DFC8] shadow-2xs space-y-2">
                <div class="text-3xl">{{ $boardMeta ? $boardMeta['icon'] : '🌾' }}</div>
                <div class="font-extrabold text-stone-800 text-base font-kannada">ಈ ಬೆಳೆಗೆ ಇಂದಿನ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ</div>
                <p class="text-xs text-stone-500 font-kannada">
                    @if($boardMeta)
                        ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ {{ $boardMeta['badge_kn'] }} ಅಧಿಕೃತ ಕೇಂದ್ರಗಳಿಂದ ದರ ಮಾಹಿತಿ ಪ್ರಕಟವಾಗಿಲ್ಲ.
                    @else
                        ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ ಯಾವುದೇ APMC ಮಾರುಕಟ್ಟೆಯಿಂದ ದರ ಮಾಹಿತಿ ಬಂದಿಲ್ಲ.
                    @endif
                </p>
                <div class="pt-2">
                    <a href="{{ route('farmer.crops.index') }}" class="px-4 py-2 text-xs font-bold text-white bg-[#1C5A2C] rounded-xl hover:bg-[#154622] transition font-kannada">
                        ಇತರ ಬೆಳೆಗಳನ್ನು ವೀಕ್ಷಿಸಿ
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($mandiPrices as $index => $item)
                    <div class="bg-white border {{ $index === 0 ? 'border-emerald-600 ring-2 ring-emerald-500/20' : 'border-[#E8DFC8]' }} rounded-2xl p-4 shadow-2xs hover:shadow-md transition flex flex-col justify-between relative group">
                        <!-- Top Bar: Rank Badge + Mandi / Centre Name -->
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-400 text-stone-950 font-black' : 'bg-stone-100 text-stone-600 font-bold' }} flex items-center justify-center text-xs shrink-0 font-sans">
                                            #{{ $index + 1 }}
                                        </span>
                                        <h3 class="font-black text-stone-900 text-base font-sans">
                                            @if($boardMeta)
                                                <span>{{ $item->market->name }}</span>
                                            @else
                                                <a href="{{ route('farmer.markets.show', $item->market->code) }}" class="hover:text-emerald-700 transition">
                                                    {{ str_ends_with(strtolower($item->market->name), 'apmc') ? $item->market->name : $item->market->name . ' APMC' }}
                                                </a>
                                            @endif
                                        </h3>
                                    </div>
                                    <div class="text-xs text-stone-500 mt-1 pl-8 font-sans">
                                        {{ $item->market->district ? $item->market->district->name : 'Karnataka' }}
                                        @if($item->variety)
                                            • <span class="font-semibold text-emerald-800">{{ $item->variety->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if($boardMeta)
                                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full {{ $boardMeta['theme'] === 'coffee' ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-emerald-100 text-emerald-900 border-emerald-300' }} border font-sans">
                                        {{ $boardMeta['icon'] }} {{ $boardMeta['badge_en'] }}
                                    </span>
                                @elseif($index === 0)
                                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 font-sans">
                                        Best Price
                                    </span>
                                @endif
                            </div>

                            <!-- Price Box -->
                            <div class="mt-3.5 p-3 rounded-xl bg-stone-50 border border-stone-100">
                                <div class="text-[10px] uppercase font-bold text-stone-400 font-sans">ಮಾದರಿ ದರ (Modal Price)</div>
                                <div class="text-2xl font-black text-emerald-950 mt-0.5 tracking-tight flex flex-wrap items-baseline gap-1.5 font-sans">
                                    <span>₹{{ number_format($item->modal_price, 0) }}</span>
                                    <span class="text-xs font-semibold text-stone-400 font-sans">/ {{ $item->unit }}</span>
                                    @if($crop->isCoffeeBoard())
                                        <span class="text-xs font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded-md font-sans">
                                            ≈ ₹{{ number_format($item->modal_price / 2, 0) }}/50kg Bag
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-2 pt-2 border-t border-stone-200/70 flex items-center justify-between text-xs text-stone-600 font-sans">
                                    <div>
                                        <span class="text-stone-400 text-[10px] block">ಕನಿಷ್ಠ (Min)</span>
                                        <span class="font-bold">{{ $item->min_price ? '₹' . number_format($item->min_price, 0) : '—' }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-stone-400 text-[10px] block">ಗರಿಷ್ಠ (Max)</span>
                                        <span class="font-bold">{{ $item->max_price ? '₹' . number_format($item->max_price, 0) : '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-3.5 pt-2.5 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500 font-sans">
                            <div>
                                @if($item->arrival_quantity)
                                    <span>ಆವಕ: <strong>{{ number_format($item->arrival_quantity, 1) }}</strong> {{ $item->arrival_unit ?? 'Qtl' }}</span>
                                @else
                                    <span>{{ $boardMeta ? 'ದರ ಮೂಲ: ' . ($item->dataSource ? $item->dataSource->name : $boardMeta['badge_en']) : 'ಮಂಡಿ ಫೀಡ್: ' . ($item->dataSource ? $item->dataSource->name : 'APMC') }}</span>
                                @endif
                            </div>

                            <!-- WhatsApp Share for this Mandi / Centre -->
                            @php
                                $mandiShare = "🌾 *ಕೃಷಿ ಬಾಂಧವ (Krushi Baandhava)*\n"
                                    . "ಇಂದಿನ *" . $crop->name . "* ದರ @" . $item->market->name . ($boardMeta ? '' : (str_ends_with(strtolower($item->market->name), 'apmc') ? '' : ' APMC')) . ":\n"
                                    . "💰 ಮಾದರಿ ದರ: ₹" . number_format($item->modal_price, 0) . " / " . $item->unit . "\n"
                                    . ($item->min_price && $item->max_price ? "📉 ಕನಿಷ್ಠ: ₹" . number_format($item->min_price, 0) . " | ಗರಿಷ್ಠ: ₹" . number_format($item->max_price, 0) . "\n" : "")
                                    . "📅 ದಿನಾಂಕ: " . $item->price_date->format('d M Y') . "\n"
                                    . "👉 ಸಂಪೂರ್ಣ ವಿವರಗಳಿಗೆ: " . url()->current();
                            @endphp
                            <a href="https://wa.me/?text={{ rawurlencode($mandiShare) }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="text-xs font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 font-sans">
                                <span>💬</span>
                                <span>ಶೇರ್ ಮಾಡಿ</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- 6. Historical Analytics & Interactive Price Trends -->
    <div class="bg-white border border-[#E8DFC8] rounded-3xl p-5 sm:p-7 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-6 rounded-full bg-[#1C5A2C]"></span>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight font-kannada">
                        ಬೆಲೆ ಇತಿಹಾಸ & ಪ್ರವೃತ್ತಿ (Historical Price Trend)
                    </h2>
                </div>
                <p class="text-xs text-stone-500 mt-1 font-kannada">
                    @if($selectedMarket)
                        <strong>{{ $selectedMarket->name }}{{ $boardMeta ? '' : (str_ends_with(strtolower($selectedMarket->name), 'apmc') ? '' : ' APMC') }}</strong> ಯ {{ $rangeDays }} ದಿನಗಳ ದರ ಮತ್ತು ಆವಕ ಮಾಹಿತಿ
                    @else
                        <strong>ಕರ್ನಾಟಕ {{ $boardMeta ? 'ಮಂಡಳಿ' : 'ರಾಜ್ಯ' }} ಸರಾಸರಿ</strong>ಯ {{ $rangeDays }} ದಿನಗಳ ದರ ಮತ್ತು ಆವಕ ಮಾಹಿತಿ ({{ $boardMeta ? 'Board Benchmark' : 'State Benchmark' }})
                    @endif
                </p>
            </div>

            <!-- Timeframe Filter Chips -->
            <div class="flex items-center gap-1.5 bg-stone-100 p-1 rounded-xl text-xs font-bold self-start sm:self-auto font-sans">
                @php
                    $ranges = [
                        '7d' => '7 ದಿನ (7D)',
                        '15d' => '15 ದಿನ (15D)',
                        '30d' => '30 ದಿನ (30D)',
                        '90d' => '3 ತಿಂಗಳು (90D)',
                        '365d' => '1 ವರ್ಷ (1Y)',
                    ];
                @endphp
                @foreach($ranges as $rKey => $rLabel)
                    <a href="{{ route('farmer.crop.detail', array_filter(['crop' => $crop->id, 'variety' => $varietyId, 'market' => $marketParam, 'range' => $rKey])) }}"
                       class="px-2.5 py-1 rounded-lg transition {{ $rangeParam === $rKey ? 'bg-white text-emerald-800 shadow-2xs font-extrabold' : 'text-stone-600 hover:text-stone-900' }}">
                        {{ $rLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Trend Statistical Summary Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 font-sans">
            <div class="p-3.5 rounded-2xl bg-stone-50 border border-stone-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">ಅವಧಿಯ ಗರಿಷ್ಠ (Period High)</span>
                <div class="text-lg font-black text-emerald-900 mt-0.5">
                    {{ $statisticalSummary['max_price'] > 0 ? '₹' . number_format($statisticalSummary['max_price'], 0) : '—' }}
                </div>
            </div>
            <div class="p-3.5 rounded-2xl bg-stone-50 border border-stone-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">ಅವಧಿಯ ಕನಿಷ್ಠ (Period Low)</span>
                <div class="text-lg font-black text-stone-800 mt-0.5">
                    {{ $statisticalSummary['min_price'] > 0 ? '₹' . number_format($statisticalSummary['min_price'], 0) : '—' }}
                </div>
            </div>
            <div class="p-3.5 rounded-2xl bg-stone-50 border border-stone-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">ಅವಧಿಯ ಸರಾಸರಿ (Period Avg)</span>
                <div class="text-lg font-black text-stone-900 mt-0.5">
                    {{ $statisticalSummary['avg_price'] > 0 ? '₹' . number_format($statisticalSummary['avg_price'], 0) : '—' }}
                </div>
            </div>
            <div class="p-3.5 rounded-2xl bg-stone-50 border border-stone-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">ಬೆಲೆ ಏರಿಳಿತ (Volatility)</span>
                <div class="text-sm font-black text-{{ $statisticalSummary['volatility_color'] ?? 'emerald' }}-700 mt-1">
                    {{ $statisticalSummary['volatility_rating'] }}
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="relative w-full h-72 sm:h-80 bg-stone-50/50 rounded-2xl p-3 border border-stone-100">
            @if(!empty($dailyTrends['has_data']))
                <canvas id="priceTrendCanvas"></canvas>
            @else
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-stone-400">
                    <span class="text-3xl mb-2">📊</span>
                    <span class="font-bold text-stone-600 text-sm font-kannada">ಈ ಅವಧಿಗೆ ಸಾಕಷ್ಟು ದರ ಇತಿಹಾಸ ದಾಖಲಾಗಿಲ್ಲ</span>
                    <span class="text-xs text-stone-400 mt-1 font-kannada">ಹೆಚ್ಚಿನ ದಿನಗಳ ದರಗಳು ದಾಖಲಾದಂತೆ ಪ್ರವೃತ್ತಿ ಗ್ರಾಫ್ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.</span>
                </div>
            @endif
        </div>
        <div class="flex items-center justify-between text-[11px] text-stone-400 px-1 font-kannada">
            <span>🟢 ಹಸಿರು ಗೆರೆ: ಮಾದರಿ ಬೆಲೆ (₹/ಕ್ವಿಂಟಾಲ್)</span>
            <span>🩶 ಬೂದು ಬಾರ್: ದೈನಂದಿನ ಆವಕ ಪ್ರಮಾಣ (ಕ್ವಿಂಟಾಲ್)</span>
        </div>
    </div>

    <!-- 7. 5-Year Seasonal Selling Index & "Best Months to Sell" -->
    <div class="bg-gradient-to-br from-amber-500/10 via-emerald-500/5 to-white border border-amber-200/60 rounded-3xl p-5 sm:p-7 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🗓️</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight font-kannada">
                            ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ತಿಂಗಳುಗಳು (Best Months to Sell)
                        </h2>
                        <span class="text-xs font-semibold text-emerald-800 font-kannada">5 ವರ್ಷಗಳ ಋತುಮಾನ ಸೂಚ್ಯಂಕ ವಿಶ್ಲೇಷಣೆ (Seasonal Price Index)</span>
                    </div>
                </div>
                <p class="text-xs text-stone-600 mt-2 leading-relaxed max-w-2xl font-kannada">
                    ಕರ್ನಾಟಕ ಮಾರುಕಟ್ಟೆಗಳಲ್ಲಿನ ಐತಿಹಾಸಿಕ ಆವಕ ಮತ್ತು ಬೇಡಿಕೆಯ ಆಧಾರದ ಮೇಲೆ, ಈ ಬೆಳೆಗೆ ಗರಿಷ್ಠ ಬೆಲೆ ಸಿಗುವ ತಿಂಗಳುಗಳನ್ನು ಇಲ್ಲಿ ಗುರುತಿಸಲಾಗಿದೆ. ಋತುಮಾನ ಸೂಚ್ಯಂಕ 1.0 ಕ್ಕಿಂತ ಹೆಚ್ಚಿದ್ದರೆ ಆ ತಿಂಗಳಲ್ಲಿ ಸರಾಸರಿಗಿಂತ ಹೆಚ್ಚಿನ ಧಾರಣೆ ಇರುತ್ತದೆ.
                </p>
            </div>

            <!-- Annual Baseline Chip -->
            @if($seasonalAnalysis['annual_baseline'] > 0)
                <div class="px-3.5 py-2 rounded-2xl bg-white border border-amber-200 shadow-2xs text-right shrink-0 font-sans">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400 block font-kannada">ವಾರ್ಷಿಕ ಸರಾಸರಿ ಮಾನದಂಡ</span>
                    <span class="text-base font-black text-amber-900">₹{{ number_format($seasonalAnalysis['annual_baseline'], 0) }}</span>
                    <span class="text-[10px] text-stone-500 block">/ ಕ್ವಿಂಟಾಲ್</span>
                </div>
            @endif
        </div>

        <!-- Top 3 Best Months Cards -->
        @if(!empty($seasonalAnalysis['best_months']))
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-900 block mb-2.5 font-kannada">
                    ⭐ ಗರಿಷ್ಠ ಲಾಭದ ತಿಂಗಳುಗಳು (Top Selling Windows):
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($seasonalAnalysis['best_months'] as $bm)
                        <div class="bg-white/90 backdrop-blur-xs p-3.5 rounded-2xl border border-emerald-200/80 shadow-2xs flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-amber-400 text-stone-900 flex items-center justify-center font-black text-[10px] font-sans">
                                        #{{ $bm['rank'] }}
                                    </span>
                                    <span class="font-black text-stone-900 text-sm font-kannada">
                                        {{ $bm['month_name_kn'] }}
                                    </span>
                                </div>
                                <div class="text-xs text-stone-500 font-semibold mt-1 font-sans">
                                    ಸರಾಸರಿ: <strong class="text-emerald-800">₹{{ number_format($bm['avg_price'], 0) }}</strong>
                                </div>
                            </div>
                            <div class="text-right font-sans">
                                <span class="inline-block px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-900 text-xs font-black">
                                    +{{ $bm['premium_percent'] }}%
                                </span>
                                <span class="text-[10px] text-stone-400 block mt-0.5 font-kannada">ಹೆಚ್ಚುವರಿ ಲಾಭ</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- 12-Month Seasonality Bar Chart -->
        <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-2xs space-y-3">
            <div class="flex items-center justify-between text-xs font-kannada">
                <span class="font-bold text-stone-700">12 ತಿಂಗಳುಗಳ ಋತುಮಾನ ದರ ಸೂಚ್ಯಂಕ (1.0 = ಸರಾಸರಿ ಮಾನದಂಡ):</span>
                <div class="flex items-center gap-3 text-[11px]">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> ಗರಿಷ್ಠ ಬೆಲೆ</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> ಉತ್ತಮ ಬೆಲೆ</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> ಸಾಧಾರಣ</span>
                </div>
            </div>

            <div class="relative w-full h-56">
                <canvas id="seasonalityCanvas"></canvas>
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
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight font-kannada">
                            {{ $crop->name_kn ?: $crop->name }} — ತಜ್ಞರ ವಿಡಿಯೋ & ಬೇಸಾಯ ಮಾರ್ಗದರ್ಶಿ
                        </h2>
                        <span class="text-xs text-stone-500 font-kannada">ವೈಜ್ಞಾನಿಕ ಕೃಷಿ ಪದ್ಧತಿಗಳು ಮತ್ತು ಪ್ರಾಯೋಗಿಕ ಮಾಹಿತಿ</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="text-xs font-bold text-emerald-800 hover:text-emerald-950 font-kannada">
                        ಎಲ್ಲಾ ವಿಡಿಯೋಗಳು &rarr;
                    </a>
                </div>
            </div>

            <!-- Videos Row -->
            @if($cropVideos->isNotEmpty())
                <div>
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3 font-kannada">ತರಬೇತಿ ವಿಡಿಯೋಗಳು (Training Videos)</h3>
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
                                    <h4 class="text-xs font-bold text-stone-900 line-clamp-2 group-hover:text-emerald-700 transition font-kannada">
                                        {{ $vid->title_kn ?: $vid->title }}
                                    </h4>
                                    <div class="text-[10px] text-stone-500 mt-1 flex items-center justify-between font-kannada">
                                        <span>{{ $vid->channel_name ?: 'ಕೃಷಿ ಮಾಹಿತಿ' }}</span>
                                        <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="font-bold text-emerald-800 font-sans">ವೀಕ್ಷಿಸಿ ▶</a>
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
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3 font-kannada">ಬೇಸಾಯ ಲೇಖನಗಳು & ಕೈಪಿಡಿ (Agri Guides)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($cropArticles as $art)
                            <a href="{{ route('farmer.articles.show', $art->slug) }}" class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200 hover:border-emerald-500 hover:shadow-xs transition block">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 mb-1.5 font-kannada">
                                    {{ $art->category_label_kn }}
                                </span>
                                <h4 class="text-xs font-bold text-stone-900 line-clamp-2 font-kannada">
                                    {{ $art->title_kn ?: $art->title }}
                                </h4>
                                <p class="text-[11px] text-stone-500 mt-1 line-clamp-2 font-kannada">
                                    {{ $art->summary_kn ?: $art->summary }}
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
                        <h2 class="text-lg sm:text-xl font-black text-white tracking-tight font-kannada">
                            ಕೃಷಿ ಸಬ್ಸಿಡಿ & ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು (Government Schemes)
                        </h2>
                        <span class="text-xs text-emerald-200 font-kannada">ರೈತರಿಗೆ ಲಭ್ಯವಿರುವ ಆರ್ಥಿಕ ನೆರವು & ಯಂತ್ರೋಪಕರಣ ಸಬ್ಸಿಡಿ</span>
                    </div>
                </div>
                <a href="{{ route('farmer.schemes.index') }}" class="text-xs font-bold text-amber-300 hover:text-amber-200 font-kannada">
                    ಎಲ್ಲಾ ಯೋಜನೆಗಳು &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($cropSchemes as $sch)
                    <div class="bg-emerald-950/50 backdrop-blur-xs border border-emerald-600/50 rounded-2xl p-4 flex flex-col justify-between hover:border-emerald-400 transition">
                        <div>
                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-700/70 text-emerald-100 mb-2 font-kannada">
                                {{ $sch->category_label_kn }}
                            </span>
                            <h4 class="text-xs font-bold text-white line-clamp-2 font-kannada">
                                {{ $sch->title_kn ?: $sch->title }}
                            </h4>
                            <p class="text-[11px] text-emerald-200/90 mt-1.5 line-clamp-2 font-kannada">
                                {{ $sch->summary_kn ?: $sch->summary }}
                            </p>
                        </div>
                        <div class="mt-4 pt-2 border-t border-emerald-800/80 flex items-center justify-between text-xs">
                            <a href="{{ route('farmer.schemes.show', $sch->slug) }}" class="font-bold text-amber-300 hover:text-amber-200 font-kannada">
                                ಅರ್ಜಿ ವಿವರ &rarr;
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
            if (typeof window.initPriceTrendChart === 'function') {
                window.initPriceTrendChart('priceTrendCanvas', {
                    labels: @json($dailyTrends['labels']),
                    modalPrices: @json($dailyTrends['modal_prices']),
                    minPrices: @json($dailyTrends['min_prices']),
                    maxPrices: @json($dailyTrends['max_prices']),
                    arrivals: @json($dailyTrends['arrivals']),
                });
            }
        @endif

        @if(!empty($seasonalAnalysis['has_seasonal_data']))
            if (typeof window.initSeasonalityChart === 'function') {
                window.initSeasonalityChart('seasonalityCanvas', @json($seasonalAnalysis['monthly_profile']));
            }
        @endif
    });
</script>
@endsection
