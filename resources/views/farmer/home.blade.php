@extends('layouts.farmer')

@section('title', 'Krushi Baandhava — ಕರ್ನಾಟಕ ರೈತರ ಮಾರುಕಟ್ಟೆ ದರಗಳು')

@section('content')
<div class="space-y-6" x-data="farmerHome()">

    <!-- 1. Hero District Context Banner -->
    <div class="bg-gradient-to-br from-emerald-850 via-emerald-800 to-teal-900 rounded-3xl p-5 sm:p-6 text-white shadow-xl relative overflow-hidden border border-emerald-700/40">
        <!-- Background Ambient Accents -->
        <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-amber-400/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute top-0 right-1/4 w-32 h-32 bg-emerald-400/10 rounded-full blur-xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs text-xs font-semibold text-emerald-200 border border-white/10">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>ದೈನಂದಿನ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ದರಗಳು • Verified Mandi Feeds</span>
                </div>

                <div class="text-2xl sm:text-3xl font-black tracking-tight mt-2 flex items-baseline gap-2">
                    <span>{{ $activeDistrict->name }}</span>
                    @if($activeDistrict->name_kn)
                        <span class="text-lg sm:text-xl font-medium text-emerald-200 font-kannada">({{ $activeDistrict->name_kn }})</span>
                    @endif
                </div>

                <div class="text-xs sm:text-sm text-emerald-100/90 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span>📍 {{ $activeDistrict->markets->count() }} APMC ಮಂಡಿಗಳು ನೋಂದಾಯಿತವಾಗಿವೆ</span>
                    <span>•</span>
                    <span>ದಿನಾಂಕ: <strong>{{ $stats['latest_date_formatted'] }}</strong></span>
                </div>
            </div>

            <!-- District Switcher & State Scope Toggle -->
            <div class="flex items-center gap-2 self-start sm:self-center">
                @if($viewScope === 'district')
                    <a href="{{ route('home', ['district' => $activeDistrict->id, 'scope' => 'all', 'category' => $selectedCategory]) }}" 
                       class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-bold text-white transition active:scale-95 flex items-center gap-1.5">
                        <span>🌐 ಎಲ್ಲಾ ರಾಜ್ಯ</span>
                    </a>
                @else
                    <a href="{{ route('home', ['district' => $activeDistrict->id, 'scope' => 'district', 'category' => $selectedCategory]) }}" 
                       class="px-3.5 py-2 rounded-xl bg-amber-400 text-emerald-950 font-bold text-xs transition active:scale-95 flex items-center gap-1.5 shadow-sm">
                        <span>📍 ಜಿಲ್ಲೆ ಮಾತ್ರ</span>
                    </a>
                @endif

                <a href="{{ route('farmer.markets.nearby') }}" 
                   class="px-3.5 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-emerald-950 font-black text-xs transition active:scale-95 flex items-center gap-1.5 shadow-sm">
                    <span>📍 ಹತ್ತಿರದ ಮಂಡಿಗಳು</span>
                </a>

                <button @click="districtModal = true" 
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white text-emerald-900 hover:bg-emerald-50 text-xs font-bold shadow-md transition active:scale-95 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>ಜಿಲ್ಲೆ ಬದಲಾಯಿಸಿ</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Weather & Farm Advisory Quick Bar -->
    @if(isset($todayWeather) && $todayWeather)
        <div class="bg-emerald-50/90 border border-emerald-200/90 rounded-2xl p-3.5 sm:p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-3xl shrink-0">{{ $todayWeather->weather_icon }}</span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-black text-emerald-950">{{ round($todayWeather->current_temperature ?? $todayWeather->temp_max) }}°C</span>
                        <span class="text-xs font-bold text-emerald-800">{{ $todayWeather->weather_condition_kn }}</span>
                        <span class="text-[11px] text-stone-500 font-sans">• 🌧️ {{ round($todayWeather->precipitation_probability) }}% ಮಳೆ ಸಾಧ್ಯತೆ</span>
                    </div>
                    @if($todayWeather->farming_advisory_kn)
                        <div class="text-xs font-medium text-emerald-900 line-clamp-1 mt-0.5">
                            🌾 <strong>ಕೃಷಿ ಸಲಹೆ:</strong> {{ $todayWeather->farming_advisory_kn }}
                        </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('farmer.weather.index', ['district' => $activeDistrict->id]) }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white hover:bg-emerald-100/80 border border-emerald-300 text-emerald-900 font-bold text-xs transition shadow-2xs self-start sm:self-center shrink-0">
                <span>7 ದಿನಗಳ ಮುನ್ಸೂಚನೆ &rsaquo;</span>
            </a>
        </div>
    @endif

    <!-- 2. Dual Search & Voice Input Bar -->
    <div class="bg-white p-2.5 sm:p-3 rounded-2xl border border-stone-200/90 shadow-sm">
        <form method="GET" action="{{ route('home') }}" class="flex items-center gap-2">
            <input type="hidden" name="district" value="{{ $activeDistrict->id }}">
            <input type="hidden" name="category" value="{{ $selectedCategory }}">
            <input type="hidden" name="scope" value="{{ $viewScope }}">

            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       x-model="searchQuery"
                       value="{{ $search }}" 
                       placeholder="ಬೆಳೆ ಅಥವಾ ಮಾರುಕಟ್ಟೆ ಹುಡುಕಿ (Search Arecanut, Shimoga...)"
                       class="w-full text-xs sm:text-sm pl-9 pr-10 py-2.5 bg-stone-50 hover:bg-stone-100/80 focus:bg-white border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 transition">
                
                @if($search)
                    <a href="{{ route('home', ['district' => $activeDistrict->id, 'category' => $selectedCategory]) }}" 
                       class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-600 text-sm font-bold">
                        ✕
                    </a>
                @endif
            </div>

            <!-- Voice Search Button (Web Speech API) -->
            <button type="button" 
                    @click="startVoiceSearch()"
                    :class="isListening ? 'bg-rose-500 text-white animate-pulse' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'"
                    class="p-2.5 rounded-xl transition font-bold text-xs flex items-center gap-1.5 shrink-0 cursor-pointer" 
                    title="ಮಾತನಾಡಿ ಹುಡುಕಿ / Voice Search in Kannada or English">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
                <span class="hidden sm:inline" x-text="isListening ? 'ಆಲಿಸಲಾಗುತ್ತಿದೆ...' : 'ಧ್ವನಿ ಹುಡುಕಾಟ'">ಧ್ವನಿ ಹುಡುಕಾಟ</span>
            </button>

            <!-- Search Submit -->
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition shrink-0 cursor-pointer">
                ಹುಡುಕಿ
            </button>
        </form>
    </div>

    <!-- 3. Category Filter Chips (Horizontal Pill Bar) -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
        <a href="{{ route('home', ['district' => $activeDistrict->id, 'category' => 'all', 'search' => $search, 'scope' => $viewScope]) }}"
           class="px-4 py-2 rounded-full font-bold whitespace-nowrap transition shadow-xs {{ $selectedCategory === 'all' || !$selectedCategory ? 'bg-emerald-800 text-white ring-2 ring-emerald-600/30' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200/90' }}">
            🌾 ಎಲ್ಲಾ ಬೆಳೆಗಳು (All)
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('home', ['district' => $activeDistrict->id, 'category' => $cat->slug, 'search' => $search, 'scope' => $viewScope]) }}"
               class="px-4 py-2 rounded-full font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5 {{ $selectedCategory === $cat->slug ? 'bg-emerald-800 text-white ring-2 ring-emerald-600/30' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200/90' }}">
                <span>{{ $cat->name }}</span>
                @if($cat->name_kn)
                    <span class="font-kannada font-normal opacity-90">({{ $cat->name_kn }})</span>
                @endif
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $selectedCategory === $cat->slug ? 'bg-white/20 text-white' : 'bg-stone-100 text-stone-500' }}">
                    {{ $cat->crops_count }}
                </span>
            </a>
        @endforeach
    </div>

    <!-- 4. Key Commodity Highlights / Price Movers Carousel -->
    @if($topMovers->isNotEmpty() && !$search)
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base font-black text-stone-900">⭐ ಪ್ರಮುಖ ಬೆಳೆಗಳ ದರ ಸೂಚ್ಯಂಕ</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900">Top Movers</span>
                </div>
                <a href="{{ route('farmer.crops.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">
                    ಎಲ್ಲಾ ಬೆಳೆಗಳು &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach($topMovers as $mover)
                    <a href="{{ route('farmer.crops.show', $mover->crop->slug) }}" 
                       class="bg-white p-3 rounded-2xl border border-stone-200/90 shadow-xs hover:border-emerald-600 hover:shadow-md transition flex flex-col justify-between group">
                        <div>
                            <div class="flex items-start justify-between gap-1">
                                <span class="font-extrabold text-stone-900 text-xs truncate group-hover:text-emerald-800 transition">{{ $mover->crop->name }}</span>
                                <span class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-1 rounded">₹</span>
                            </div>
                            <div class="text-[11px] text-stone-500 font-kannada truncate mt-0.5">{{ $mover->crop->name_kn }}</div>
                        </div>

                        <div class="mt-3 pt-2 border-t border-stone-100">
                            <div class="text-[10px] uppercase font-bold text-stone-400">ಮಾದರಿ ದರ</div>
                            <div class="text-base font-black text-emerald-800 tracking-tight">
                                ₹{{ number_format($mover->modal_price, 0) }}
                            </div>
                            <div class="text-[10px] text-stone-400 truncate mt-0.5">
                                {{ $mover->market->name }} APMC
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 5. Live Mandi Market Prices Grid -->
    <div class="space-y-3.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight flex items-center gap-2">
                    <span>ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು</span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                        {{ $latestPrices->total() }} ದರಗಳು ಲಭ್ಯ
                    </span>
                </h2>
                <p class="text-xs text-stone-500">
                    @if($viewScope === 'district')
                        {{ $activeDistrict->name }} ಜಿಲ್ಲೆಯ ಅಧಿಕೃತ APMC ಮಾರುಕಟ್ಟೆ ದರಗಳು
                    @else
                        ಕರ್ನಾಟಕ ರಾಜ್ಯಾದ್ಯಂತ ಇತ್ತೀಚಿನ APMC ಮಾರುಕಟ್ಟೆ ದರಗಳು
                    @endif
                </p>
            </div>

            <div class="text-xs text-stone-400 flex items-center gap-2">
                <span>ಎಲ್ಲಾ ದರಗಳು ಕ್ವಿಂಟಾಲ್‌ಗೆ (INR/Qtl)</span>
            </div>
        </div>

        @if($latestPrices->isEmpty())
            <div class="bg-white rounded-3xl p-8 text-center border border-stone-200/80 shadow-xs space-y-3">
                <div class="text-4xl">🌾</div>
                <div class="font-extrabold text-stone-800 text-base">ಈ ಆಯ್ಕೆಗೆ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ</div>
                <p class="text-xs text-stone-500 max-w-md mx-auto">
                    ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ ಈ ವರ್ಗದಲ್ಲಿ ದರಗಳು ದಾಖಲಾಗಿಲ್ಲ. ದಯವಿಟ್ಟು ರಾಜ್ಯ ಮಟ್ಟದ ದರಗಳನ್ನು ವೀಕ್ಷಿಸಿ ಅಥವಾ ಬೇರೆ ವರ್ಗವನ್ನು ಆಯ್ಕೆಮಾಡಿ.
                </p>
                <div class="pt-2 flex justify-center gap-2">
                    <a href="{{ route('home', ['district' => $activeDistrict->id, 'scope' => 'all']) }}" 
                       class="px-4 py-2 text-xs font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition shadow-xs">
                        ಎಲ್ಲಾ ರಾಜ್ಯದ ದರಗಳು ನೋಡಿ
                    </a>
                    <a href="{{ route('home') }}" class="px-4 py-2 text-xs font-bold text-stone-600 bg-stone-100 rounded-xl hover:bg-stone-200 transition">
                        ಫಿಲ್ಟರ್ ತೆರವುಗೊಳಿಸಿ
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($latestPrices as $price)
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between relative group">
                        <div>
                            <!-- Header: Crop + Variety + Category Pill -->
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="font-black text-stone-900 text-base leading-snug">
                                            <a href="{{ route('farmer.crops.show', $price->crop->slug) }}" class="hover:text-emerald-700 transition">
                                                {{ $price->crop->name }}
                                            </a>
                                        </h3>
                                        @if($price->crop->name_kn)
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-kannada">
                                                {{ $price->crop->name_kn }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs font-medium text-stone-500 mt-0.5">
                                        {{ $price->variety ? $price->variety->name : 'All Varieties (ಎಲ್ಲಾ ತಳಿಗಳು)' }}
                                    </div>
                                </div>

                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">
                                    {{ $price->crop->category ? $price->crop->category->name : 'Commodity' }}
                                </span>
                            </div>

                            <!-- APMC Mandi & District -->
                            <div class="text-xs text-stone-600 mt-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <a href="{{ route('farmer.markets.show', $price->market->code) }}" class="font-bold text-stone-800 hover:text-emerald-700 hover:underline transition">
                                    {{ $price->market->name }} APMC
                                </a>
                                <span class="text-stone-400">•</span>
                                <span class="text-stone-500">{{ $price->market->district ? $price->market->district->name : 'Karnataka' }}</span>
                            </div>

                            <!-- Hero Price Box -->
                            <div class="mt-3.5 p-3 rounded-xl bg-stone-50 border border-stone-100/90">
                                <div class="flex items-center justify-between text-[11px] font-bold text-stone-500 uppercase tracking-wider">
                                    <span>ಮಾದರಿ ದರ / Modal Rate</span>
                                    @if($price->price_spread > 0)
                                        <span class="text-amber-700 font-semibold text-[10px] bg-amber-50 px-1.5 py-0.5 rounded">
                                            ವ್ಯತ್ಯಾಸ: ₹{{ number_format($price->price_spread, 0) }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-2xl font-black text-emerald-950 mt-1 tracking-tight flex items-baseline gap-1.5">
                                    <span>₹{{ number_format($price->modal_price, 0) }}</span>
                                    <span class="text-xs font-semibold text-stone-400 font-sans">/ {{ $price->unit }}</span>
                                </div>

                                <!-- Min-Max Visual Bar -->
                                <div class="mt-2 pt-2 border-t border-stone-200/70 flex items-center justify-between text-xs text-stone-600">
                                    <div>
                                        <span class="text-stone-400 text-[10px] block font-medium">ಕನಿಷ್ಠ / Min</span>
                                        <span class="font-bold text-stone-800">
                                            {{ $price->min_price ? '₹' . number_format($price->min_price, 0) : '—' }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-stone-400 text-[10px] block font-medium">ಗರಿಷ್ಠ / Max</span>
                                        <span class="font-bold text-stone-800">
                                            {{ $price->max_price ? '₹' . number_format($price->max_price, 0) : '—' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer: Arrivals, Provenance & WhatsApp Share -->
                        <div class="mt-3.5 pt-2.5 border-t border-stone-100 flex items-center justify-between gap-2">
                            <!-- Arrivals or Date -->
                            <div class="text-[11px] text-stone-500">
                                @if($price->arrival_quantity)
                                    <span>ಆವಕ: <strong>{{ number_format($price->arrival_quantity, 1) }}</strong> {{ $price->arrival_unit ?? 'Qtl' }}</span>
                                @else
                                    <span>ದಿನಾಂಕ: {{ $price->price_date->format('d M') }}</span>
                                @endif
                            </div>

                            <!-- WhatsApp 1-Tap Share Button -->
                            @php
                                $shareText = "🌾 *ಕೃಷಿ ಬಾಂಧವ (Krushi Baandhava)*\n"
                                    . "ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರ: *" . $price->crop->name . ($price->crop->name_kn ? ' (' . $price->crop->name_kn . ')' : '') . "*\n"
                                    . "📍 ಮಾರುಕಟ್ಟೆ: " . $price->market->name . " APMC (" . ($price->market->district ? $price->market->district->name : 'Karnataka') . ")\n"
                                    . "💰 ಮಾದರಿ ದರ: ₹" . number_format($price->modal_price, 0) . " / " . $price->unit . "\n"
                                    . ($price->min_price && $price->max_price ? "📉 ಕನಿಷ್ಠ: ₹" . number_format($price->min_price, 0) . " | ಗರಿಷ್ಠ: ₹" . number_format($price->max_price, 0) . "\n" : "")
                                    . "📅 ದಿನಾಂಕ: " . $price->price_date->format('d M Y') . "\n"
                                    . "👉 ಅಧಿಕೃತ ದರಗಳು & ವಿಶ್ಲೇಷಣೆಗೆ ಭೇಟಿ ನೀಡಿ: " . url('/');
                                $whatsappUrl = "https://wa.me/?text=" . rawurlencode($shareText);
                            @endphp

                            <a href="{{ $whatsappUrl }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition active:scale-95 cursor-pointer">
                                <span>💬</span>
                                <span>ಶೇರ್ ಮಾಡಿ</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($latestPrices->hasPages())
                <div class="mt-4">
                    {{ $latestPrices->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- 6. Major Karnataka Crops Catalog Grid -->
    <div id="crops-catalog" class="space-y-3 pt-2">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base sm:text-lg font-black text-stone-900 tracking-tight">ಕರ್ನಾಟಕದ ಪ್ರಮುಖ ಬೆಳೆಗಳು (Commodities)</h2>
                <p class="text-xs text-stone-500">ಪ್ರತಿಯೊಂದು ಬೆಳೆಯ ಮಂಡಿವಾರು ದರ ಹೋಲಿಕೆ ವೀಕ್ಷಿಸಲು ಆಯ್ಕೆಮಾಡಿ</p>
            </div>
            <a href="{{ route('farmer.crops.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">
                ಎಲ್ಲಾ ನೋಡಿ &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($majorCrops as $crop)
                <a href="{{ route('farmer.crops.show', $crop->slug) }}" 
                   class="bg-white border border-stone-200/90 rounded-2xl p-3.5 text-center shadow-xs hover:border-emerald-600 hover:shadow-md transition group">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center font-black text-sm mb-2 shadow-inner group-hover:scale-105 transition">
                        {{ substr($crop->name, 0, 2) }}
                    </div>
                    <div class="font-extrabold text-stone-900 text-xs sm:text-sm group-hover:text-emerald-800 transition">{{ $crop->name }}</div>
                    @if($crop->name_kn)
                        <div class="text-[11px] text-stone-500 font-kannada font-medium mt-0.5">{{ $crop->name_kn }}</div>
                    @endif
                    <div class="text-[10px] text-emerald-700 font-semibold mt-1">
                        {{ $crop->varieties->count() }} ತಳಿಗಳು
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- 7. District Switcher Bottom-Sheet Modal -->
    <div x-show="districtModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4 border border-stone-100" @click.away="districtModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <div>
                    <h3 class="font-extrabold text-stone-900 text-lg">ಜಿಲ್ಲೆ ಆಯ್ಕೆಮಾಡಿ</h3>
                    <p class="text-xs text-stone-500">Select your Karnataka District</p>
                </div>
                <button @click="districtModal = false" class="text-stone-400 hover:text-stone-600 p-1 text-lg font-bold">✕</button>
            </div>

            <div class="grid grid-cols-2 gap-2 max-h-80 overflow-y-auto pr-1">
                @foreach($allDistricts as $dist)
                    <a href="{{ route('home', ['district' => $dist->id, 'category' => $selectedCategory]) }}" 
                       class="p-3 rounded-xl border text-left text-xs font-bold transition flex items-center justify-between {{ $dist->id === $activeDistrict->id ? 'bg-emerald-50 border-emerald-600 text-emerald-900 shadow-xs' : 'border-stone-200 hover:bg-stone-50 text-stone-700' }}">
                        <div>
                            <span>{{ $dist->name }}</span>
                            @if($dist->name_kn)
                                <span class="block text-[11px] font-normal text-stone-500 font-kannada">{{ $dist->name_kn }}</span>
                            @endif
                        </div>
                        @if($dist->id === $activeDistrict->id)
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

</div>

<script>
function farmerHome() {
    return {
        districtModal: false,
        searchQuery: '{{ addslashes($search) }}',
        isListening: false,
        startVoiceSearch() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert('ಧ್ವನಿ ಹುಡುಕಾಟವು ನಿಮ್ಮ ಬ್ರೌಸರ್‌ನಲ್ಲಿ ಬೆಂಬಲಿತವಾಗಿಲ್ಲ. (Voice search is not supported in this browser).');
                return;
            }
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.lang = 'kn-IN'; // Kannada primary, fallback handles English
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            this.isListening = true;

            recognition.onresult = (event) => {
                const spoken = event.results[0][0].transcript;
                this.searchQuery = spoken;
                this.isListening = false;
                // Auto submit form
                this.$el.querySelector('form').submit();
            };

            recognition.onerror = () => {
                this.isListening = false;
            };

            recognition.onend = () => {
                this.isListening = false;
            };

            recognition.start();
        }
    };
}
</script>
@endsection
