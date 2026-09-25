@extends('layouts.farmer')

@section('title', $market->name . ' APMC (' . ($market->name_kn ?? '') . ') — ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು & ಆವಕ')

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-stone-500 font-medium">
        <a href="{{ route('home') }}" class="hover:text-emerald-700">ಮುಖಪುಟ</a>
        <span>&rsaquo;</span>
        <a href="{{ route('farmer.markets.index') }}" class="hover:text-emerald-700">ಮಂಡಿಗಳು</a>
        <span>&rsaquo;</span>
        <span class="text-stone-900 font-bold">{{ $market->name }} APMC</span>
    </nav>

    <!-- Mandi Header Card -->
    <div class="bg-white border border-stone-200/90 rounded-3xl p-5 sm:p-6 shadow-sm relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-cyan-50 text-cyan-800 flex items-center justify-center font-black text-2xl shadow-inner shrink-0">
                    🏛️
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight">
                            {{ $market->name }} APMC
                        </h1>
                        @if($market->name_kn)
                            <span class="text-base sm:text-lg font-bold px-2.5 py-0.5 rounded-lg bg-stone-100 text-stone-700 font-kannada">
                                {{ $market->name_kn }}
                            </span>
                        @endif
                    </div>
                    <div class="text-xs sm:text-sm text-stone-500 mt-1 flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-stone-100 font-mono font-semibold">{{ $market->code }}</span>
                        <span>•</span>
                        <span>ಜಿಲ್ಲೆ: <strong>{{ $market->district ? $market->district->name : 'Karnataka' }}</strong></span>
                        @if($market->taluk)
                            <span>•</span>
                            <span>ತಾಲೂಕು: <strong>{{ $market->taluk->name }}</strong></span>
                        @endif
                    </div>
                    @if($market->address)
                        <div class="text-xs text-stone-400 mt-1 flex items-center gap-1">
                            <span>📍 {{ $market->address }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- WhatsApp Share Mandi Rates -->
            @php
                $shareMandiText = "🏛️ *ಕೃಷಿ ಬಾಂಧವ — APMC ಮಾರುಕಟ್ಟೆ ದರಗಳು*\n"
                    . "*" . $market->name . " APMC (" . ($market->district ? $market->district->name : 'Karnataka') . ")*\n"
                    . "🌾 ಇಂದಿನ ವಹಿವಾಟು: " . $stats['total_commodities'] . " ಬೆಳೆಗಳು\n"
                    . "📦 ಒಟ್ಟು ಆವಕ: " . number_format($stats['total_arrivals'], 1) . " ಕ್ವಿಂಟಾಲ್\n"
                    . "📅 ದಿನಾಂಕ: " . $stats['date_formatted'] . "\n"
                    . "👉 ಎಲ್ಲಾ ಬೆಳೆಗಳ ದರ ವೀಕ್ಷಿಸಲು: " . url()->current();
                $whatsappUrl = "https://wa.me/?text=" . rawurlencode($shareMandiText);
            @endphp

            <a href="{{ $whatsappUrl }}" 
               target="_blank" 
               rel="noopener noreferrer"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition active:scale-95 self-start sm:self-center">
                <span>💬</span>
                <span>ಮಂಡಿ ದರಗಳನ್ನು ಶೇರ್ ಮಾಡಿ</span>
            </a>
        </div>

        <!-- 3-Stat Overview Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 pt-5 border-t border-stone-100">
            <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500">ವಹಿವಾಟಾದ ಬೆಳೆಗಳು (Commodities)</span>
                <div class="text-2xl font-black text-stone-900 mt-1">
                    {{ $stats['total_commodities'] }}
                </div>
                <div class="text-xs font-semibold text-emerald-700 mt-0.5">
                    ಇಂದಿನ ವರದಿ ಪ್ರಕಾರ
                </div>
            </div>

            <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500">ಒಟ್ಟು ಆವಕ (Total Arrivals)</span>
                <div class="text-2xl font-black text-stone-900 mt-1">
                    {{ number_format($stats['total_arrivals'], 1) }}
                </div>
                <div class="text-xs font-semibold text-stone-500 mt-0.5">
                    ಕ್ವಿಂಟಾಲ್‌ಗಳು
                </div>
            </div>

            <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100">
                <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500">ಅತಿ ಹೆಚ್ಚು ದರ (Peak Rate)</span>
                <div class="text-2xl font-black text-emerald-950 mt-1">
                    {{ $stats['highest_rate'] > 0 ? '₹' . number_format($stats['highest_rate'], 0) : '—' }}
                </div>
                <div class="text-xs font-semibold text-stone-500 truncate mt-0.5">
                    {{ $stats['highest_crop'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Traded Commodities Table / Cards -->
    <div class="space-y-3.5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-stone-900 tracking-tight flex items-center gap-2">
                    <span>🌾 ಈ ಮಂಡಿಯಲ್ಲಿ ಇಂದಿನ ವಹಿವಾಟು ದರಗಳು</span>
                </h2>
                <p class="text-xs text-stone-500">ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ದೈನಂದಿನ ಬೆಲೆಗಳು ಮತ್ತು ಆವಕ ಪ್ರಮಾಣ</p>
            </div>
            <span class="text-xs text-stone-400">ದಿನಾಂಕ: {{ $stats['date_formatted'] }}</span>
        </div>

        @if($prices->isEmpty())
            <div class="bg-white rounded-3xl p-8 text-center border border-stone-200/80 shadow-xs space-y-2">
                <div class="text-3xl">🏛️</div>
                <div class="font-extrabold text-stone-800 text-base">ಈ ಮಂಡಿಯಿಂದ ಇಂದಿನ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ</div>
                <p class="text-xs text-stone-500">ಇಂದಿನ ವಹಿವಾಟು ವರದಿ ಇನ್ನೂ ದಾಖಲಾಗಿಲ್ಲ. ದಯವಿಟ್ಟು ನಂತರ ಪ್ರಯತ್ನಿಸಿ ಅಥವಾ ಇತರ ಮಂಡಿಗಳನ್ನು ನೋಡಿ.</p>
                <div class="pt-2">
                    <a href="{{ route('farmer.markets.index') }}" class="px-4 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition">
                        ಇತರ ಮಂಡಿಗಳನ್ನು ನೋಡಿ
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($prices as $price)
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <!-- Crop + Variety -->
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-black text-stone-900 text-base leading-snug">
                                        <a href="{{ route('farmer.crops.show', $price->crop->slug) }}" class="hover:text-emerald-700 transition">
                                            {{ $price->crop->name }}
                                        </a>
                                    </h3>
                                    @if($activeLocale === 'kn' && $price->crop->name_kn)
                                        <div class="text-xs font-semibold text-emerald-800 font-kannada mt-0.5">
                                            {{ $price->crop->name_kn }}
                                        </div>
                                    @endif
                                    <div class="text-xs text-stone-500 mt-1">
                                        {{ $price->variety ? $price->variety->displayName($activeLocale) : ($activeLocale === 'en' ? 'All Varieties' : 'ಎಲ್ಲಾ ತಳಿಗಳು') }}
                                    </div>
                                </div>

                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">
                                    {{ $price->crop->category ? $price->crop->category->name : 'Crop' }}
                                </span>
                            </div>

                            <!-- Hero Price Box -->
                            <div class="mt-3.5 p-3 rounded-xl bg-stone-50 border border-stone-100">
                                <div class="text-[10px] uppercase font-bold text-stone-400">ಮಾದರಿ ದರ / Modal Rate</div>
                                <div class="text-2xl font-black text-emerald-950 mt-0.5 tracking-tight flex items-baseline gap-1.5">
                                    <span>₹{{ number_format($price->modal_price, 0) }}</span>
                                    <span class="text-xs font-semibold text-stone-400 font-sans">/ {{ $price->unit }}</span>
                                </div>

                                <div class="mt-2 pt-2 border-t border-stone-200/70 flex items-center justify-between text-xs text-stone-600">
                                    <div>
                                        <span class="text-stone-400 text-[10px] block">ಕನಿಷ್ಠ</span>
                                        <span class="font-bold">{{ $price->min_price ? '₹' . number_format($price->min_price, 0) : '—' }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-stone-400 text-[10px] block">ಗರಿಷ್ಠ</span>
                                        <span class="font-bold">{{ $price->max_price ? '₹' . number_format($price->max_price, 0) : '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-3.5 pt-2.5 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
                            <div>
                                @if($price->arrival_quantity)
                                    <span>ಆವಕ: <strong>{{ number_format($price->arrival_quantity, 1) }}</strong> {{ $price->arrival_unit ?? 'Qtl' }}</span>
                                @else
                                    <span>ದಿನಾಂಕ: {{ $price->price_date->format('d M') }}</span>
                                @endif
                            </div>

                            @php
                                $itemShare = "🌾 *ಕೃಷಿ ಬಾಂಧವ*\n"
                                    . "ಇಂದಿನ *" . $price->crop->name . "* ದರ @" . $market->name . " APMC:\n"
                                    . "💰 ಮಾದರಿ ದರ: ₹" . number_format($price->modal_price, 0) . " / " . $price->unit . "\n"
                                    . ($price->min_price && $price->max_price ? "📉 ಕನಿಷ್ಠ: ₹" . number_format($price->min_price, 0) . " | ಗರಿಷ್ಠ: ₹" . number_format($price->max_price, 0) . "\n" : "")
                                    . "👉 ವಿವರಗಳಿಗೆ: " . url()->current();
                            @endphp
                            <a href="https://wa.me/?text={{ rawurlencode($itemShare) }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                                <span>💬</span>
                                <span>ಶೇರ್</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
