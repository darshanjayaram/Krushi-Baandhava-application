@extends('layouts.farmer')

@section('title', $crop->name . ' (' . ($crop->name_kn ?? '') . ') — ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು & ಹೋಲಿಕೆ')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-stone-500 font-medium">
        <a href="{{ route('home') }}" class="hover:text-emerald-700">ಮುಖಪುಟ</a>
        <span>&rsaquo;</span>
        <a href="{{ route('farmer.crops.index') }}" class="hover:text-emerald-700">ಬೆಳೆಗಳು</a>
        <span>&rsaquo;</span>
        <span class="text-stone-900 font-bold">{{ $crop->name }}</span>
    </nav>

    <!-- Crop Header Card -->
    <div class="bg-white border border-stone-200/90 rounded-3xl p-5 sm:p-6 shadow-sm relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center font-black text-2xl shadow-inner shrink-0">
                    {{ substr($crop->name, 0, 2) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight">
                            {{ $crop->name }}
                        </h1>
                        @if($crop->name_kn)
                            <span class="text-base sm:text-lg font-bold px-2.5 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 font-kannada">
                                {{ $crop->name_kn }}
                            </span>
                        @endif
                    </div>
                    <div class="text-xs sm:text-sm text-stone-500 mt-1 flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-stone-100 font-semibold">{{ $crop->category ? $crop->category->name : 'Commodity' }}</span>
                        <span>•</span>
                        <span>ಮಾನಕ ತೂಕ: <strong>{{ $crop->standard_unit ?? 'Quintal' }}</strong></span>
                        @if($crop->scientific_name)
                            <span>•</span>
                            <span class="italic text-stone-400 font-sans">({{ $crop->scientific_name }})</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- WhatsApp Share Commodity Rates -->
            @php
                $shareAllText = "🌾 *ಕೃಷಿ ಬಾಂಧವ — ಕರ್ನಾಟಕ ಮಾರುಕಟ್ಟೆ ದರಗಳು*\n"
                    . "ಇಂದಿನ *" . $crop->name . ($crop->name_kn ? ' (' . $crop->name_kn . ')' : '') . "* ಮಾರುಕಟ್ಟೆ ದರಗಳ ವಿವರ:\n"
                    . "🏆 ಗರಿಷ್ಠ ದರ: ₹" . number_format($stats['highest_modal'], 0) . " (" . $stats['highest_market'] . " APMC)\n"
                    . "📉 ಕನಿಷ್ಠ ದರ: ₹" . number_format($stats['lowest_modal'], 0) . " (" . $stats['lowest_market'] . " APMC)\n"
                    . "📊 ರಾಜ್ಯದ ಸರಾಸರಿ: ₹" . number_format($stats['avg_modal'], 0) . " / " . ($crop->standard_unit ?? 'Quintal') . "\n"
                    . "📅 ದಿನಾಂಕ: " . $stats['date_formatted'] . "\n"
                    . "👉 ಸಂಪೂರ್ಣ ಮಂಡಿವಾರು ಹೋಲಿಕೆಗೆ ನೋಡಿ: " . url()->current();
                $whatsappUrl = "https://wa.me/?text=" . rawurlencode($shareAllText);
            @endphp

            <a href="{{ $whatsappUrl }}" 
               target="_blank" 
               rel="noopener noreferrer"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition active:scale-95 self-start sm:self-center">
                <span>💬</span>
                <span>ದರಗಳನ್ನು ವಾಟ್ಸಾಪ್‌ನಲ್ಲಿ ಹಂಚಿಕೊಳ್ಳಿ</span>
            </a>
        </div>

        <!-- 4-Stat Overview Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6 pt-5 border-t border-stone-100">
            <!-- Highest Rate -->
            <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">ರಾಜ್ಯದ ಗರಿಷ್ಠ ದರ (Highest)</span>
                <div class="text-xl sm:text-2xl font-black text-emerald-950 mt-1">
                    {{ $stats['highest_modal'] > 0 ? '₹' . number_format($stats['highest_modal'], 0) : '—' }}
                </div>
                <div class="text-xs font-semibold text-emerald-700 truncate mt-0.5">
                    {{ $stats['highest_market'] }} APMC
                </div>
            </div>

            <!-- Lowest Rate -->
            <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500">ಕನಿಷ್ಠ ದರ (Lowest)</span>
                <div class="text-xl sm:text-2xl font-black text-stone-800 mt-1">
                    {{ $stats['lowest_modal'] > 0 ? '₹' . number_format($stats['lowest_modal'], 0) : '—' }}
                </div>
                <div class="text-xs font-semibold text-stone-500 truncate mt-0.5">
                    {{ $stats['lowest_market'] }} APMC
                </div>
            </div>

            <!-- State Average -->
            <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500">ರಾಜ್ಯ ಸರಾಸರಿ (Average)</span>
                <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1">
                    {{ $stats['avg_modal'] > 0 ? '₹' . number_format($stats['avg_modal'], 0) : '—' }}
                </div>
                <div class="text-xs font-semibold text-stone-500 mt-0.5">
                    {{ $stats['total_mandis'] }} ಮಂಡಿಗಳಿಂದ
                </div>
            </div>

            <!-- Arrivals -->
            <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500">ಒಟ್ಟು ಆವಕ (Arrivals)</span>
                <div class="text-xl sm:text-2xl font-black text-stone-900 mt-1">
                    {{ number_format($stats['total_arrivals'], 1) }}
                </div>
                <div class="text-xs font-semibold text-stone-500 mt-0.5">
                    ಕ್ವಿಂಟಾಲ್ (Quintals)
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls: Variety & Karnataka APMC Mandi Selection -->
    <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs space-y-4">
        <!-- Variety Filter Tabs -->
        @if($crop->varieties->isNotEmpty())
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
                <span class="font-bold text-stone-500 whitespace-nowrap">ತಳಿ ಆಯ್ಕೆ:</span>
                <a href="{{ route('farmer.crops.show', array_filter(['slug' => $crop->slug, 'market' => $marketParam])) }}"
                   class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition shadow-xs {{ !$varietyId ? 'bg-emerald-800 text-white' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200' }}">
                    ಎಲ್ಲಾ ತಳಿಗಳು (All)
                </a>
                @foreach($crop->varieties as $v)
                    <a href="{{ route('farmer.crops.show', array_filter(['slug' => $crop->slug, 'variety' => $v->id, 'market' => $marketParam])) }}"
                       class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition shadow-xs {{ $varietyId == $v->id ? 'bg-emerald-800 text-white' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200' }}">
                        <span>{{ $v->name }}</span>
                        @if($v->name_kn)
                            <span class="font-kannada font-normal opacity-90">({{ $v->name_kn }})</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Karnataka Mandi Filter Dropdown -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 {{ $crop->varieties->isNotEmpty() ? 'border-t border-stone-100' : '' }}">
            <div class="flex items-center gap-2">
                <span class="text-base">🏛️</span>
                <div>
                    <label for="marketSelect" class="text-xs font-bold text-stone-800 block">ಕರ್ನಾಟಕ ಮಂಡಿ ಆಯ್ಕೆ (Karnataka APMC Mandi)</label>
                    <span class="text-[11px] text-stone-400">ಕೇವಲ ಕರ್ನಾಟಕದ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳು ಮಾತ್ರ (Strictly Karnataka)</span>
                </div>
            </div>

            <form method="GET" action="{{ route('farmer.crops.show', $crop->slug) }}" class="flex items-center gap-2">
                @if($varietyId)
                    <input type="hidden" name="variety" value="{{ $varietyId }}">
                @endif
                <div class="relative min-w-[240px] sm:min-w-[280px]">
                    <select id="marketSelect" 
                            name="market" 
                            onchange="this.form.submit()" 
                            class="w-full text-xs font-bold text-stone-800 bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 pr-8 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none transition appearance-none cursor-pointer">
                        <option value="">ಎಲ್ಲಾ ಕರ್ನಾಟಕ ಮಂಡಿಗಳು (All Karnataka Mandis)</option>
                        @foreach($availableMarkets as $m)
                            <option value="{{ $m->name }}" {{ (strtolower($marketParam) === strtolower($m->name) || strtolower($marketParam) === strtolower($m->code)) ? 'selected' : '' }}>
                                {{ $m->name }} APMC ({{ $m->district?->name ?? 'Karnataka' }})
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-stone-400 text-xs">▼</div>
                </div>

                @if($marketParam)
                    <a href="{{ route('farmer.crops.show', array_filter(['slug' => $crop->slug, 'variety' => $varietyId])) }}" 
                       class="px-2.5 py-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-bold transition flex items-center gap-1 shrink-0"
                       title="ಫಿಲ್ಟರ್ ತೆರವುಗೊಳಿಸಿ">
                        <span>✕</span>
                        <span class="hidden sm:inline">ತೆರವುಗೊಳಿಸಿ</span>
                    </a>
                @endif
            </form>
        </div>

        @if($marketParam)
            <div class="flex items-center gap-2 pt-1 text-xs">
                <span class="text-stone-500">ಆಯ್ಕೆಯಾದ ಮಂಡಿ:</span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-900 border border-emerald-200 font-bold">
                    <span>🏛️</span>
                    <span>{{ $marketParam }}</span>
                </span>
                <span class="text-stone-400 text-[11px]">({{ $mandiPrices->count() }} ದಾಖಲೆಗಳು)</span>
            </div>
        @endif

        @if($availableMarkets->isNotEmpty())
            <!-- Quick Mandi Switcher Chips (Similar to Negilu Krushi) -->
            <div class="pt-3 border-t border-stone-100">
                <div class="text-[11px] font-bold text-stone-500 mb-2 flex items-center gap-1.5">
                    <span>🏛️</span>
                    <span>ಬೇರೆ ಮಾರುಕಟ್ಟೆ ನೋಡಿ · ಮಾರುಕಟ್ಟೆ ಬದಲಿಸಿ (Quick Market Switcher):</span>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
                    <a href="{{ route('farmer.crops.show', array_filter(['slug' => $crop->slug, 'variety' => $varietyId])) }}"
                       class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition border {{ empty($marketParam) ? 'bg-emerald-800 text-white border-emerald-800 shadow-sm' : 'bg-stone-50 text-stone-700 hover:bg-stone-100 border-stone-200' }}">
                        ಎಲ್ಲಾ ಮಂಡಿಗಳು (All)
                    </a>
                    @foreach($availableMarkets as $am)
                        @php
                            $isSelected = (strtolower($marketParam) === strtolower($am->name) || strtolower($marketParam) === strtolower($am->code));
                        @endphp
                        <a href="{{ route('farmer.crops.show', array_filter(['slug' => $crop->slug, 'variety' => $varietyId, 'market' => $am->name])) }}"
                           class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition border {{ $isSelected ? 'bg-emerald-800 text-white border-emerald-800 shadow-sm' : 'bg-stone-50 text-stone-700 hover:bg-emerald-50 hover:border-emerald-300 border-stone-200' }}">
                            @if($isSelected)
                                <span>★</span>
                            @endif
                            <span>{{ $am->name_kn ?? $am->name }}</span>
                            @if(isset($am->today_modal_price) && $am->today_modal_price > 0)
                                <span class="{{ $isSelected ? 'text-amber-300' : 'text-emerald-700' }} font-black">₹{{ number_format($am->today_modal_price, 0) }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Phase 10: Where to Sell Decision Engine Callout -->
    <div class="bg-gradient-to-r from-emerald-900 via-emerald-800 to-slate-900 rounded-3xl p-5 sm:p-6 text-white shadow-md border border-emerald-700/40">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-950 font-black text-[11px] uppercase tracking-wider">
                    <span>⚖️</span>
                    <span>Where to Sell? · ಇಂದು ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?</span>
                </div>
                <h3 class="text-lg sm:text-xl font-black tracking-tight">
                    ಸಾರಿಗೆ ವೆಚ್ಚ ಮತ್ತು ಮಂಡಿ ಕಮಿಷನ್ ಕಳೆದ ನಂತರ ನಿಮ್ಮ ಕೈಗೆ ಎಷ್ಟು ಉಳಿಯುತ್ತದೆ?
                </h3>
                <p class="text-xs sm:text-sm text-emerald-200">
                    ಹತ್ತಿರದ ಮತ್ತು ದೂರದ ಮಂಡಿಗಳಲ್ಲಿ ರಸ್ತೆ ಸಾರಿಗೆ ದರ ಹಾಗೂ ಎಪಿಎಂಸಿ ಶುಲ್ಕಗಳನ್ನು ಹೋಲಿಸಿ, ನಿಖರವಾದ ನಿವ್ವಳ ಲಾಭ ಪಡೆಯಿರಿ.
                </p>
            </div>
            <a href="{{ route('farmer.decision.where-to-sell', ['crop' => $crop->slug]) }}" 
               class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wide shadow-lg transition active:scale-95 shrink-0">
                <span>ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ (Simulator)</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Phase 8: Historical Analytics & Interactive Price Trends -->
    <div class="bg-white border border-stone-200/90 rounded-3xl p-5 sm:p-6 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">📈</span>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight">
                        ಬೆಲೆ ಇತಿಹಾಸ & ಪ್ರವೃತ್ತಿ (Historical Price Trend)
                    </h2>
                </div>
                <p class="text-xs text-stone-500 mt-0.5">
                    @if($selectedMarket)
                        <span><strong>{{ $selectedMarket->name }} APMC</strong> ಯ {{ $rangeDays }} ದಿನಗಳ ದರ ಮತ್ತು ಆವಕ ಮಾಹಿತಿ</span>
                    @else
                        <span><strong>ಕರ್ನಾಟಕ ರಾಜ್ಯ ಸರಾಸರಿ</strong>ಯ {{ $rangeDays }} ದಿನಗಳ ದರ ಮತ್ತು ಆವಕ ಮಾಹಿತಿ (State Benchmark)</span>
                    @endif
                </p>
            </div>

            <!-- Timeframe Filter Chips -->
            <div class="flex items-center gap-1.5 bg-stone-100 p-1 rounded-xl text-xs font-bold self-start sm:self-auto">
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
                    <a href="{{ route('farmer.crops.show', array_filter(['slug' => $crop->slug, 'variety' => $varietyId, 'market' => $marketParam, 'range' => $rKey])) }}"
                       class="px-2.5 py-1 rounded-lg transition {{ $rangeParam === $rKey ? 'bg-white text-emerald-800 shadow-xs' : 'text-stone-600 hover:text-stone-900' }}">
                        {{ $rLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Trend Statistical Summary Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3 rounded-2xl bg-stone-50 border border-stone-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">ಅವಧಿಯ ಗರಿಷ್ಠ (Period High)</span>
                <div class="text-lg font-black text-emerald-900 mt-0.5">
                    {{ $statisticalSummary['max_price'] > 0 ? '₹' . number_format($statisticalSummary['max_price'], 0) : '—' }}
                </div>
            </div>
            <div class="p-3 rounded-2xl bg-stone-50 border border-stone-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">ಅವಧಿಯ ಕನಿಷ್ಠ (Period Low)</span>
                <div class="text-lg font-black text-stone-800 mt-0.5">
                    {{ $statisticalSummary['min_price'] > 0 ? '₹' . number_format($statisticalSummary['min_price'], 0) : '—' }}
                </div>
            </div>
            <div class="p-3 rounded-2xl bg-stone-50 border border-stone-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">ಅವಧಿಯ ಸರಾಸರಿ (Period Avg)</span>
                <div class="text-lg font-black text-stone-900 mt-0.5">
                    {{ $statisticalSummary['avg_price'] > 0 ? '₹' . number_format($statisticalSummary['avg_price'], 0) : '—' }}
                </div>
            </div>
            <div class="p-3 rounded-2xl bg-stone-50 border border-stone-100">
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
                    <span class="font-bold text-stone-600 text-sm">ಈ ಅವಧಿಗೆ ಸಾಕಷ್ಟು ದರ ಇತಿಹಾಸ ದಾಖಲಾಗಿಲ್ಲ</span>
                    <span class="text-xs text-stone-400 mt-1">ಹೆಚ್ಚಿನ ದಿನಗಳ ದರಗಳು ದಾಖಲಾದಂತೆ ಪ್ರವೃತ್ತಿ ಗ್ರಾಫ್ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.</span>
                </div>
            @endif
        </div>
        <div class="flex items-center justify-between text-[11px] text-stone-400 px-1">
            <span>🟢 ಹಸಿರು ಗೆರೆ: ಮಾದರಿ ಬೆಲೆ (₹/ಕ್ವಿಂಟಾಲ್)</span>
            <span>🩶 ಬೂದು ಬಾರ್: ದೈನಂದಿನ ಆವಕ ಪ್ರಮಾಣ (ಕ್ವಿಂಟಾಲ್)</span>
        </div>
    </div>

    <!-- Phase 8: 5-Year Seasonal Selling Index & "Best Months to Sell" -->
    <div class="bg-gradient-to-br from-amber-500/10 via-emerald-500/5 to-white border border-amber-200/60 rounded-3xl p-5 sm:p-6 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🗓️</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight">
                            ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ತಿಂಗಳುಗಳು (Best Months to Sell)
                        </h2>
                        <span class="text-xs font-semibold text-emerald-800">5 ವರ್ಷಗಳ ಋತುಮಾನ ಸೂಚ್ಯಂಕ ವಿಶ್ಲೇಷಣೆ (Seasonal Price Index)</span>
                    </div>
                </div>
                <p class="text-xs text-stone-600 mt-2 leading-relaxed max-w-2xl">
                    ಕರ್ನಾಟಕ ಮಾರುಕಟ್ಟೆಗಳಲ್ಲಿನ ಐತಿಹಾಸಿಕ ಆವಕ ಮತ್ತು ಬೇಡಿಕೆಯ ಆಧಾರದ ಮೇಲೆ, ಈ ಬೆಳೆಗೆ ಗರಿಷ್ಠ ಬೆಲೆ ಸಿಗುವ ತಿಂಗಳುಗಳನ್ನು ಇಲ್ಲಿ ಗುರುತಿಸಲಾಗಿದೆ. ಋತುಮಾನ ಸೂಚ್ಯಂಕ 1.0 ಕ್ಕಿಂತ ಹೆಚ್ಚಿದ್ದರೆ ಆ ತಿಂಗಳಲ್ಲಿ ಸರಾಸರಿಗಿಂತ ಹೆಚ್ಚಿನ ಧಾರಣೆ ಇರುತ್ತದೆ.
                </p>
            </div>

            <!-- Annual Baseline Chip -->
            @if($seasonalAnalysis['annual_baseline'] > 0)
                <div class="px-3.5 py-2 rounded-2xl bg-white border border-amber-200 shadow-xs text-right shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400 block">ವಾರ್ಷಿಕ ಸರಾಸರಿ ಮಾನದಂಡ</span>
                    <span class="text-base font-black text-amber-900">₹{{ number_format($seasonalAnalysis['annual_baseline'], 0) }}</span>
                    <span class="text-[10px] text-stone-500 block">/ ಕ್ವಿಂಟಾಲ್</span>
                </div>
            @endif
        </div>

        <!-- Top 3 Best Months Cards -->
        @if(!empty($seasonalAnalysis['best_months']))
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-900 block mb-2.5">
                    ⭐ ಗರಿಷ್ಠ ಲಾಭದ ತಿಂಗಳುಗಳು (Top Selling Windows):
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($seasonalAnalysis['best_months'] as $bm)
                        <div class="bg-white/90 backdrop-blur-xs p-3.5 rounded-2xl border border-emerald-200/80 shadow-xs flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-amber-400 text-stone-900 flex items-center justify-center font-black text-[10px]">
                                        #{{ $bm['rank'] }}
                                    </span>
                                    <span class="font-black text-stone-900 text-sm">
                                        {{ $bm['month_name_kn'] }}
                                    </span>
                                </div>
                                <div class="text-xs text-stone-500 font-semibold mt-1">
                                    ಸರಾಸರಿ: <strong class="text-emerald-800">₹{{ number_format($bm['avg_price'], 0) }}</strong>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-900 text-xs font-black">
                                    +{{ $bm['premium_percent'] }}%
                                </span>
                                <span class="text-[10px] text-stone-400 block mt-0.5">ಹೆಚ್ಚುವರಿ ಲಾಭ</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- 12-Month Seasonality Bar Chart -->
        <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-stone-700">12 ತಿಂಗಳುಗಳ ಋತುಮಾನ ದರ ಸೂಚ್ಯಂಕ (1.0 = ಸರಾಸರಿ ಮಾನದಂಡ):</span>
                <div class="flex items-center gap-3 text-[11px]">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> ಗರಿಷ್ಠ ಬೆಲೆ</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> ಉತ್ತಮ ಬೆಲೆ</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> ಸಾಧಾರಣ</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> ಆವಕ ಹೆಚ್ಚಳ</span>
                </div>
            </div>

            <div class="relative w-full h-56">
                <canvas id="seasonalityCanvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Phase 9: Price Forecasting & Mathematical Projections -->
    <div class="bg-white border border-stone-200/90 rounded-3xl p-5 sm:p-6 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🎯</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight">
                            ದರ ಮುನ್ಸೂಚನೆ & ನಿರೀಕ್ಷಿತ ಶ್ರೇಣಿ (Price Forecast & Projections)
                        </h2>
                        <span class="text-xs font-semibold text-emerald-800">
                            ಗಣಿತೀಯ ಪ್ರವೃತ್ತಿ ಅಂದಾಜು (Holt's Linear Trend / Seasonal Projection)
                        </span>
                    </div>
                </div>
                <p class="text-xs text-stone-500 mt-1">
                    ಮುಂಬರುವ 1, 7, 15 ಮತ್ತು 30 ದಿನಗಳಲ್ಲಿ ನಿರೀಕ್ಷಿತ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ಸಂಭಾವ್ಯ ಗರಿಷ್ಠ-ಕನಿಷ್ಠ ಶ್ರೇಣಿ.
                </p>
            </div>

            @if(!empty($forecast['is_sufficient']))
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-900 border border-emerald-200 text-xs font-bold self-start sm:self-auto">
                    <span>✓</span>
                    <span>{{ $forecast['observations_count'] }} ದಿನಗಳ ದರ ದತ್ತಾಂಶ ಲಭ್ಯ</span>
                </div>
            @endif
        </div>

        @if(!empty($forecast['is_sufficient']))
            <!-- Multi-Horizon Projections Grid (1D, 7D, 15D, 30D) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                @foreach($forecast['horizons'] as $h)
                    <div class="p-4 rounded-2xl bg-stone-50/70 border border-stone-200/80 hover:border-emerald-500/50 hover:bg-emerald-50/20 transition flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-stone-800">{{ $h['label_kn'] }}</span>
                                <span class="text-[11px] font-bold text-stone-400 font-sans">{{ $h['target_date_formatted'] }}</span>
                            </div>

                            <!-- Expected Price -->
                            <div class="mt-2.5">
                                <span class="text-[10px] uppercase font-bold text-stone-400 block">ನಿರೀಕ್ಷಿತ ದರ (Expected)</span>
                                <div class="text-2xl font-black text-emerald-950 tracking-tight flex items-baseline gap-1.5 mt-0.5">
                                    <span>₹{{ number_format($h['expected_price'], 0) }}</span>
                                    <span class="text-xs font-semibold text-stone-400 font-sans">/ ಕ್ವಿಂ</span>
                                </div>
                            </div>

                            <!-- Percentage Movement Indicator -->
                            <div class="mt-1 flex items-center gap-1.5 text-xs font-bold">
                                @if($h['direction'] === 'up')
                                    <span class="text-emerald-700 flex items-center">▲ +{{ $h['percentage_change'] }}% ಏರಿಕೆ ಸಾಧ್ಯತೆ</span>
                                @elseif($h['direction'] === 'down')
                                    <span class="text-rose-600 flex items-center">▼ {{ $h['percentage_change'] }}% ಇಳಿಕೆ ಸಾಧ್ಯತೆ</span>
                                @else
                                    <span class="text-stone-500">▬ ಸ್ಥಿರ ಧಾರಣೆ</span>
                                @endif
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-stone-200/60 space-y-1.5">
                            <!-- Lower and Upper Confidence Range -->
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-stone-400 text-[11px]">ಸಂಭಾವ್ಯ ಶ್ರೇಣಿ:</span>
                                <span class="font-bold text-stone-800">
                                    ₹{{ number_format($h['lower_bound'], 0) }} – ₹{{ number_format($h['upper_bound'], 0) }}
                                </span>
                            </div>

                            <!-- Confidence Score -->
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-stone-400">ವಿಶ್ವಾಸಾರ್ಹತೆ:</span>
                                <span class="font-extrabold {{ $h['confidence_score'] >= 80 ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $h['confidence_score'] }}%
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Data Insufficiency Graceful Notice -->
            <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-amber-950 flex items-start gap-3">
                <span class="text-xl shrink-0">ℹ️</span>
                <div class="space-y-1 text-xs">
                    <div class="font-bold text-sm text-amber-900">ದತ್ತಾಂಶ ಅಸಮರ್ಪಕತೆ (Data Insufficiency Notice)</div>
                    <p class="leading-relaxed">
                        {{ $forecast['message_kn'] ?? 'ವಿಶ್ವಾಸಾರ್ಹ ಮುನ್ಸೂಚನೆಗೆ ಕನಿಷ್ಠ 30 ದಿನಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ.' }}
                    </p>
                    <p class="text-amber-800/80">
                        ಕೃಷಿ ಬಾಂಧವ ಕೃತಕ ಅಥವಾ ಸುಳ್ಳು ಅಂದಾಜುಗಳನ್ನು ಪ್ರದರ್ಶಿಸುವುದಿಲ್ಲ. ಮಂಡಿಗಳಿಂದ ನಿರಂತರ 30 ದಿನಗಳ ದರಗಳು ದಾಖಲಾದ ನಂತರ ನಿಖರ ಗಣಿತೀಯ ಮುನ್ಸೂಚನೆ ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.
                    </p>
                </div>
            </div>
        @endif

        <!-- Ethical Disclaimer -->
        <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/70 text-[11px] text-stone-500 leading-relaxed flex items-center gap-2">
            <span class="text-stone-400 shrink-0">⚖️</span>
            <span>
                <strong>ಗಮನಿಸಿ (Disclaimer):</strong> ಇದು ಕೇವಲ ಹಿಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳ ಪ್ರವೃತ್ತಿ ಆಧಾರಿತ ಗಣಿತೀಯ ಅಂದಾಜು. ನೈಜ ದರಗಳು ಹವಾಮಾನ ಪರಿಸ್ಥಿತಿ, ಮಾರುಕಟ್ಟೆಯ ಆವಕ ಪ್ರಮಾಣ, ರಫ್ತು ನಿಯಮಗಳು ಮತ್ತು ಸರ್ಕಾರದ ನೀತಿಗಳಿಂದ ವ್ಯತ್ಯಾಸವಾಗಬಹುದು.
            </span>
        </div>
    </div>

    <!-- Mandi Rates Comparison List -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-stone-900 tracking-tight flex items-center gap-2">
                    <span>🏆 ಮಂಡಿವಾರು ದರ ಹೋಲಿಕೆ (Ranked by Best Price)</span>
                </h2>
                <p class="text-xs text-stone-500">
                    @if($marketParam)
                        <span>ಆಯ್ಕೆಯಾದ ಮಂಡಿಯ ದರ ವಿವರಗಳು ({{ $marketParam }})</span>
                    @else
                        <span>ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಮಂಡಿಗಳನ್ನು ಅತ್ಯಧಿಕ ದರದಿಂದ ಇಳಿಕೆ ಕ್ರಮದಲ್ಲಿ ಪ್ರದರ್ಶಿಸಲಾಗಿದೆ (Highest to Lowest)</span>
                    @endif
                </p>
            </div>
            <span class="text-xs text-stone-400">ದಿನಾಂಕ: {{ $stats['date_formatted'] }}</span>
        </div>

        @if($mandiPrices->isEmpty())
            <div class="bg-white rounded-3xl p-8 text-center border border-stone-200/80 shadow-xs space-y-2">
                <div class="text-3xl">🌾</div>
                <div class="font-extrabold text-stone-800 text-base">ಈ ಬೆಳೆಗೆ ಇಂದಿನ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ</div>
                <p class="text-xs text-stone-500">ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ ಯಾವುದೇ APMC ಮಾರುಕಟ್ಟೆಯಿಂದ ದರ ಮಾಹಿತಿ ಬಂದಿಲ್ಲ.</p>
                <div class="pt-2">
                    <a href="{{ route('farmer.crops.index') }}" class="px-4 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition">
                        ಇತರ ಬೆಳೆಗಳನ್ನು ವೀಕ್ಷಿಸಿ
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($mandiPrices as $index => $item)
                    <div class="bg-white border {{ $index === 0 ? 'border-emerald-500/80 ring-2 ring-emerald-500/20' : 'border-stone-200/90' }} rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between relative">
                        <!-- Top Bar: Rank Badge + Mandi Name -->
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-400 text-emerald-950 font-black' : 'bg-stone-100 text-stone-600 font-bold' }} flex items-center justify-center text-xs shrink-0">
                                            #{{ $index + 1 }}
                                        </span>
                                        <h3 class="font-black text-stone-900 text-base">
                                            <a href="{{ route('farmer.markets.show', $item->market->code) }}" class="hover:text-emerald-700 transition">
                                                {{ $item->market->name }} APMC
                                            </a>
                                        </h3>
                                    </div>
                                    <div class="text-xs text-stone-500 mt-1 pl-8">
                                        {{ $item->market->district ? $item->market->district->name : 'Karnataka' }}
                                        @if($item->variety)
                                            • <span class="font-semibold text-emerald-800">{{ $item->variety->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if($index === 0)
                                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        ಉತ್ತಮ ದರ (Best Price)
                                    </span>
                                @endif
                            </div>

                            <!-- Price Box -->
                            <div class="mt-3.5 p-3 rounded-xl bg-stone-50 border border-stone-100">
                                <div class="text-[10px] uppercase font-bold text-stone-400">ಮಾದರಿ ದರ (Modal Price)</div>
                                <div class="text-2xl font-black text-emerald-950 mt-0.5 tracking-tight flex items-baseline gap-1.5">
                                    <span>₹{{ number_format($item->modal_price, 0) }}</span>
                                    <span class="text-xs font-semibold text-stone-400 font-sans">/ {{ $item->unit }}</span>
                                </div>

                                <div class="mt-2 pt-2 border-t border-stone-200/70 flex items-center justify-between text-xs text-stone-600">
                                    <div>
                                        <span class="text-stone-400 text-[10px] block">ಕನಿಷ್ಠ</span>
                                        <span class="font-bold">{{ $item->min_price ? '₹' . number_format($item->min_price, 0) : '—' }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-stone-400 text-[10px] block">ಗರಿಷ್ಠ</span>
                                        <span class="font-bold">{{ $item->max_price ? '₹' . number_format($item->max_price, 0) : '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-3.5 pt-2.5 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
                            <div>
                                @if($item->arrival_quantity)
                                    <span>ಆವಕ: <strong>{{ number_format($item->arrival_quantity, 1) }}</strong> {{ $item->arrival_unit ?? 'Qtl' }}</span>
                                @else
                                    <span>ಮಂಡಿ ಫೀಡ್: {{ $item->dataSource ? $item->dataSource->name : 'APMC' }}</span>
                                @endif
                            </div>

                            <!-- WhatsApp Share for this Mandi -->
                            @php
                                $mandiShare = "🌾 *ಕೃಷಿ ಬಾಂಧವ (Krushi Baandhava)*\n"
                                    . "ಇಂದಿನ *" . $crop->name . "* ದರ @" . $item->market->name . " APMC:\n"
                                    . "💰 ಮಾದರಿ ದರ: ₹" . number_format($item->modal_price, 0) . " / " . $item->unit . "\n"
                                    . ($item->min_price && $item->max_price ? "📉 ಕನಿಷ್ಠ: ₹" . number_format($item->min_price, 0) . " | ಗರಿಷ್ಠ: ₹" . number_format($item->max_price, 0) . "\n" : "")
                                    . "📅 ದಿನಾಂಕ: " . $item->price_date->format('d M Y') . "\n"
                                    . "👉 ಸಂಪೂರ್ಣ ವಿವರಗಳಿಗೆ: " . url()->current();
                            @endphp
                            <a href="https://wa.me/?text={{ rawurlencode($mandiShare) }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                                <span>💬</span>
                                <span>ಶೇರ್ ಮಾಡಿ</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Phase 11: Agricultural CMS (Videos, Guides & Government Schemes) -->
    @if($cropVideos->isNotEmpty() || $cropArticles->isNotEmpty())
        <div class="bg-white rounded-3xl border border-stone-200 p-5 sm:p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-stone-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🎬</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight">
                            {{ $crop->kannada_name ?: $crop->name }} — ತಜ್ಞರ ವಿಡಿಯೋ & ಬೇಸಾಯ ಮಾರ್ಗದರ್ಶಿ
                        </h2>
                        <span class="text-xs text-stone-500">ವೈಜ್ಞಾನಿಕ ಕೃಷಿ ಪದ್ಧತಿಗಳು ಮತ್ತು ಪ್ರಾಯೋಗಿಕ ಮಾಹಿತಿ</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">
                        ಎಲ್ಲಾ ವಿಡಿಯೋಗಳು &rarr;
                    </a>
                </div>
            </div>

            <!-- Videos Row -->
            @if($cropVideos->isNotEmpty())
                <div>
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3">ತರಬೇತಿ ವಿಡಿಯೋಗಳು (Training Videos)</h3>
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
                                        <span class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded bg-black/80 text-white text-[9px] font-bold">
                                            {{ $vid->duration_text }}
                                        </span>
                                    @endif
                                </a>
                                <div class="p-3">
                                    <h4 class="text-xs font-bold text-stone-900 line-clamp-2 group-hover:text-emerald-700 transition">
                                        {{ $vid->title_kn ?: $vid->title }}
                                    </h4>
                                    <div class="text-[10px] text-stone-500 mt-1 flex items-center justify-between">
                                        <span>{{ $vid->channel_name ?: 'ಕೃಷಿ ಮಾಹಿತಿ' }}</span>
                                        <a href="{{ route('farmer.videos.index', ['crop_id' => $crop->id]) }}" class="font-bold text-emerald-700">ವೀಕ್ಷಿಸಿ ▶</a>
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
                    <h3 class="text-xs font-extrabold text-stone-400 uppercase tracking-wider mb-3">ಬೇಸಾಯ ಲೇಖನಗಳು & ಕೈಪಿಡಿ (Agri Guides)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($cropArticles as $art)
                            <a href="{{ route('farmer.articles.show', $art->slug) }}" class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200 hover:border-emerald-500 hover:shadow-xs transition block">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 mb-1.5">
                                    {{ $art->category_label_kn }}
                                </span>
                                <h4 class="text-xs font-bold text-stone-900 line-clamp-2">
                                    {{ $art->title_kn ?: $art->title }}
                                </h4>
                                <p class="text-[11px] text-stone-500 mt-1 line-clamp-2">
                                    {{ $art->summary_kn ?: $art->summary }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Applicable Government Schemes for Farmers -->
    @if($cropSchemes->isNotEmpty())
        <div class="bg-gradient-to-br from-emerald-800 to-teal-900 rounded-3xl p-5 sm:p-6 text-white shadow-md space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-emerald-700/60 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🏛️</span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-white tracking-tight">
                            ಕೃಷಿ ಸಬ್ಸಿಡಿ & ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು (Government Schemes)
                        </h2>
                        <span class="text-xs text-emerald-200">ರೈತರಿಗೆ ಲಭ್ಯವಿರುವ ಆರ್ಥಿಕ ನೆರವು & ಯಂತ್ರೋಪಕರಣ ಸಬ್ಸಿಡಿ</span>
                    </div>
                </div>
                <a href="{{ route('farmer.schemes.index') }}" class="text-xs font-bold text-amber-300 hover:text-amber-200">
                    ಎಲ್ಲಾ ಯೋಜನೆಗಳು &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($cropSchemes as $sch)
                    <div class="bg-emerald-950/50 backdrop-blur-xs border border-emerald-600/50 rounded-2xl p-4 flex flex-col justify-between hover:border-emerald-400 transition">
                        <div>
                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-700/70 text-emerald-100 mb-2">
                                {{ $sch->category_label_kn }}
                            </span>
                            <h4 class="text-xs font-bold text-white line-clamp-2">
                                {{ $sch->title_kn ?: $sch->title }}
                            </h4>
                            <p class="text-[11px] text-emerald-200/90 mt-1.5 line-clamp-2">
                                {{ $sch->summary_kn ?: $sch->summary }}
                            </p>
                        </div>
                        <div class="mt-4 pt-2 border-t border-emerald-800/80 flex items-center justify-between text-xs">
                            <a href="{{ route('farmer.schemes.show', $sch->slug) }}" class="font-bold text-amber-300 hover:text-amber-200">
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

