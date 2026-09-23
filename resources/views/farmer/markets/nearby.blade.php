@extends('layouts.farmer')

@section('title', 'ಹತ್ತಿರದ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳು (Nearby APMC Mandis) — ಕೃಷಿ ಬಾಂಧವ')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-stone-500 font-medium">
        <a href="{{ route('home') }}" class="hover:text-emerald-700">ಮುಖಪುಟ</a>
        <span>&rsaquo;</span>
        <a href="{{ route('farmer.markets.index') }}" class="hover:text-emerald-700">ಮಾರುಕಟ್ಟೆಗಳು</a>
        <span>&rsaquo;</span>
        <span class="text-stone-900 font-bold">ಹತ್ತಿರದ ಮಂಡಿಗಳು</span>
    </nav>

    <!-- Geolocation Hero Card -->
    <div class="bg-gradient-to-br from-emerald-900 via-emerald-850 to-stone-900 text-white rounded-3xl p-5 sm:p-7 shadow-lg relative overflow-hidden">
        <div class="relative z-10 max-w-2xl space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-800/80 border border-emerald-600/40 text-emerald-200 text-xs font-bold">
                <span>📍 ಜಿಪಿಎಸ್ ಆಧಾರಿತ ಮಂಡಿ ಶೋಧಕ</span>
                <span>•</span>
                <span>Nearby Mandi Radar</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black tracking-tight leading-tight">
                ನಿಮ್ಮ ಸಮೀಪದ ಕರ್ನಾಟಕ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳು
            </h1>
            <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
                ನಿಮ್ಮ ಪ್ರಸ್ತುತ ಜಿಪಿಎಸ್ ಸ್ಥಳವನ್ನು ಪತ್ತೆಹಚ್ಚಿ, ಕಡಿಮೆ ದೂರದಲ್ಲಿರುವ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳನ್ನು ಮತ್ತು ಇಂದಿನ ದರಗಳನ್ನು ಕ್ಷಣಾರ್ಧದಲ್ಲಿ ಹೋಲಿಕೆ ಮಾಡಿ.
            </p>

            <!-- GPS Auto-Detect Button -->
            <div class="pt-2 flex flex-wrap items-center gap-3">
                <button type="button" 
                        id="gpsDetectBtn" 
                        onclick="detectFarmerLocation()" 
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-amber-400 hover:bg-amber-300 text-emerald-950 font-black text-sm shadow-md transition active:scale-95 cursor-pointer">
                    <span id="gpsBtnIcon">📍</span>
                    <span id="gpsBtnText">ಪ್ರಸ್ತುತ ಸ್ಥಳದಿಂದ ಹುಡುಕಿ (Locate Me)</span>
                </button>

                @if($isGpsLocation)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-800/90 border border-emerald-500/50 text-xs font-bold text-emerald-100">
                        <span>✓</span>
                        <span>ಜಿಪಿಎಸ್ ಸ್ಥಳ ಸಕ್ರಿಯವಾಗಿದೆ</span>
                    </span>
                @endif
            </div>

            <!-- GPS Status / Error Message Container -->
            <div id="gpsStatusMessage" class="hidden text-xs font-semibold px-3 py-2 rounded-xl bg-rose-950/80 border border-rose-700/60 text-rose-200"></div>

            <!-- Current Location Badge -->
            <div class="pt-2 flex items-center gap-2 text-xs text-emerald-200/90">
                <span class="text-stone-300">ಪ್ರಸ್ತುತ ಕೇಂದ್ರ:</span>
                <strong class="text-white bg-black/30 px-3 py-1 rounded-lg border border-white/10 font-bold truncate max-w-xs">
                    {{ $locationName }}
                </strong>
            </div>
        </div>

        <!-- Decorative Pattern -->
        <div class="absolute -right-12 -bottom-12 w-64 h-64 rounded-full bg-emerald-600/10 pointer-events-none blur-2xl"></div>
    </div>

    <!-- Filter Control Panel: Radius, Manual District Fallback, Commodity -->
    <div class="bg-white border border-stone-200/90 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
        <form method="GET" action="{{ route('farmer.markets.nearby') }}" id="nearbyFilterForm" class="space-y-4">
            <!-- Hidden coordinates if active -->
            @if($userLat && $userLon && $isGpsLocation)
                <input type="hidden" name="lat" value="{{ $userLat }}">
                <input type="hidden" name="lon" value="{{ $userLon }}">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <!-- 1. Manual District Fallback -->
                <div>
                    <label for="districtSelect" class="block text-xs font-bold text-stone-700 mb-1">
                        🏛️ ಜಿಲ್ಲೆ ಆಯ್ಕೆ (Manual District)
                    </label>
                    <select id="districtSelect" 
                            name="district" 
                            onchange="clearGpsAndSubmit()" 
                            class="w-full text-xs font-semibold text-stone-800 bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none">
                        <option value="">-- ಜಿಲ್ಲೆ ಆಯ್ಕೆಮಾಡಿ (Select District) --</option>
                        @foreach($allDistricts as $d)
                            <option value="{{ $d->id }}" {{ $districtId == $d->id ? 'selected' : '' }}>
                                {{ $d->name }} {{ $d->name_kn ? "({$d->name_kn})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Commodity Filter -->
                <div>
                    <label for="cropSelect" class="block text-xs font-bold text-stone-700 mb-1">
                        🌾 ಬೆಳೆ ಫಿಲ್ಟರ್ (Filter by Crop)
                    </label>
                    <select id="cropSelect" 
                            name="crop" 
                            onchange="this.form.submit()" 
                            class="w-full text-xs font-semibold text-stone-800 bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none">
                        <option value="">ಎಲ್ಲಾ ಬೆಳೆಗಳು (All Commodities)</option>
                        @foreach($allCrops as $c)
                            <option value="{{ $c->slug }}" {{ $cropSlug === $c->slug ? 'selected' : '' }}>
                                {{ $c->name }} {{ $c->name_kn ? "({$c->name_kn})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Search Radius Selector -->
                <div>
                    <label class="block text-xs font-bold text-stone-700 mb-1">
                        📏 ಹುಡುಕಾಟ ವ್ಯಾಪ್ತಿ (Search Radius)
                    </label>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach([25, 50, 100, 150] as $r)
                            <button type="submit" 
                                    name="radius" 
                                    value="{{ $r }}" 
                                    class="py-2 text-xs font-bold rounded-xl text-center transition {{ (int)$radiusKm === $r ? 'bg-emerald-800 text-white shadow-xs' : 'bg-stone-100 hover:bg-stone-200 text-stone-700' }}">
                                {{ $r }} km
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Active Filter Badges -->
            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-stone-100 text-xs">
                <span class="text-stone-400 font-medium">ಸಕ್ರಿಯ ಫಿಲ್ಟರ್‌ಗಳು:</span>
                <span class="px-2.5 py-1 rounded-lg bg-stone-100 text-stone-700 font-semibold">
                    ವ್ಯಾಪ್ತಿ: <strong>{{ (int) $radiusKm }} ಕಿ.ಮೀ</strong>
                </span>

                @if($selectedCrop)
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-900 border border-emerald-200 font-semibold flex items-center gap-1">
                        <span>🌾 {{ $selectedCrop->name }} ({{ $selectedCrop->name_kn ?? '' }})</span>
                        <a href="{{ route('farmer.markets.nearby', array_filter(['lat' => $userLat, 'lon' => $userLon, 'district' => $districtId, 'radius' => $radiusKm])) }}" 
                           class="text-emerald-700 hover:text-emerald-950 font-black ml-1">✕</a>
                    </span>
                @endif

                @if($isGpsLocation)
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-900 border border-blue-200 font-semibold">
                        📍 ಲೈವ್ GPS ಕೋಆರ್ಡಿನೇಟ್‌ಗಳು
                    </span>
                @endif
            </div>
        </form>
    </div>

    <!-- Results Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-black text-stone-900 tracking-tight flex items-center gap-2">
                <span>🏛️ ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆಗಳು (Nearby Mandis)</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                    {{ $nearbyMarkets->count() }} ಲಭ್ಯವಿದೆ
                </span>
            </h2>
            <p class="text-xs text-stone-500 mt-0.5">
                ಕಡಿಮೆ ದೂರದಿಂದ ಹೆಚ್ಚಿನ ದೂರದ ಅನುಕ್ರಮದಲ್ಲಿ ಜೋಡಿಸಲಾಗಿದೆ (Sorted by Closest Distance)
            </p>
        </div>
    </div>

    <!-- Markets List Grid -->
    @if($nearbyMarkets->isEmpty())
        <div class="bg-white rounded-3xl p-8 text-center border border-stone-200/90 shadow-xs space-y-3">
            <div class="text-4xl">📍</div>
            <h3 class="font-extrabold text-stone-800 text-base">ಈ ವ್ಯಾಪ್ತಿಯಲ್ಲಿ ಯಾವುದೇ ಕರ್ನಾಟಕ ಮಂಡಿಗಳು ಕಂಡುಬಂದಿಲ್ಲ</h3>
            <p class="text-xs text-stone-500 max-w-md mx-auto">
                {{ (int) $radiusKm }} ಕಿ.ಮೀ ವ್ಯಾಪ್ತಿಯಲ್ಲಿ ಯಾವುದೇ ಮಾರುಕಟ್ಟೆ ಕಂಡುಬಂದಿಲ್ಲ. ದಯವಿಟ್ಟು ವ್ಯಾಪ್ತಿಯನ್ನು 100 ಅಥವಾ 150 ಕಿ.ಮೀ ಗೆ ಹೆಚ್ಚಿಸಿ ಅಥವಾ ಮತ್ತೊಂದು ಜಿಲ್ಲೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ.
            </p>
            <div class="pt-2 flex justify-center gap-2">
                <a href="{{ route('farmer.markets.nearby', array_filter(['lat' => $userLat, 'lon' => $userLon, 'district' => $districtId, 'radius' => 100, 'crop' => $cropSlug])) }}" 
                   class="px-4 py-2 text-xs font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition shadow-xs">
                    100 ಕಿ.ಮೀ ವ್ಯಾಪ್ತಿಗೆ ಹುಡುಕಿ
                </a>
                <a href="{{ route('farmer.markets.index') }}" 
                   class="px-4 py-2 text-xs font-bold text-stone-700 bg-stone-100 rounded-xl hover:bg-stone-200 transition">
                    ಎಲ್ಲಾ ಮಂಡಿಗಳ ಪಟ್ಟಿ ನೋಡಿ
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($nearbyMarkets as $index => $market)
                <div class="bg-white border {{ $index === 0 ? 'border-emerald-500/80 ring-2 ring-emerald-500/20' : 'border-stone-200/90' }} rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between space-y-4">
                    <div>
                        <!-- Top Row: Distance Pill & Direction -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-xl {{ $index === 0 ? 'bg-emerald-700 text-white' : 'bg-stone-100 text-stone-800' }} text-xs font-black tracking-tight flex items-center gap-1 shadow-2xs">
                                        <span>📍</span>
                                        <span>{{ $market->distance_km }} ಕಿ.ಮೀ</span>
                                        <span class="text-[10px] font-normal opacity-90">({{ $market->distance_km }} km)</span>
                                    </span>
                                    <span class="text-[11px] font-semibold text-stone-500 bg-stone-50 px-2 py-0.5 rounded-lg border border-stone-100">
                                        🧭 {{ $market->direction_kn }} ({{ $market->direction_en }})
                                    </span>
                                </div>

                                <h3 class="font-black text-stone-900 text-lg mt-2 leading-snug">
                                    <a href="{{ route('farmer.markets.show', $market->code) }}" class="hover:text-emerald-700 transition">
                                        {{ $market->name }}
                                    </a>
                                </h3>

                                <div class="text-xs text-stone-500 mt-0.5 flex items-center gap-2">
                                    <span>🏛️ {{ $market->district ? $market->district->name : 'Karnataka' }}</span>
                                    @if($market->taluk)
                                        <span>•</span>
                                        <span>{{ $market->taluk->name }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($index === 0)
                                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 shrink-0">
                                    ಅತ್ಯಂತ ಹತ್ತಿರ (Closest)
                                </span>
                            @endif
                        </div>

                        <!-- Commodity Highlight / Rates Display -->
                        @if($selectedCrop && isset($market->filtered_crop_price) && $market->filtered_crop_price)
                            <div class="mt-3.5 p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-emerald-950">🌾 {{ $selectedCrop->name }} ಇಂದಿನ ದರ:</span>
                                    <span class="text-[10px] font-semibold text-emerald-700">ಮಾದರಿ ದರ</span>
                                </div>
                                <div class="text-2xl font-black text-emerald-900 mt-0.5 flex items-baseline gap-1">
                                    <span>₹{{ number_format($market->filtered_crop_price->modal_price, 0) }}</span>
                                    <span class="text-xs font-normal text-emerald-700 font-sans">/ {{ $market->filtered_crop_price->unit }}</span>
                                </div>
                                <div class="text-[11px] text-emerald-800 mt-1 flex items-center gap-3">
                                    <span>ಕನಿಷ್ಠ: ₹{{ number_format($market->filtered_crop_price->min_price, 0) }}</span>
                                    <span>•</span>
                                    <span>ಗರಿಷ್ಠ: ₹{{ number_format($market->filtered_crop_price->max_price, 0) }}</span>
                                </div>
                            </div>
                        @elseif($market->top_prices->isNotEmpty())
                            <div class="mt-3.5 p-3 rounded-xl bg-stone-50 border border-stone-100 space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] font-bold text-stone-500">
                                    <span>ಇಂದಿನ ಪ್ರಮುಖ ದರಗಳು</span>
                                    <span>{{ $market->commodities_count }} ಬೆಳೆಗಳ ವಹಿವಾಟು</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($market->top_prices as $price)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-stone-700 font-medium truncate max-w-[150px]">
                                                {{ $price->crop->name }}
                                                @if($price->variety)
                                                    <span class="text-[10px] text-stone-400">({{ $price->variety->name }})</span>
                                                @endif
                                            </span>
                                            <span class="font-extrabold text-stone-900">
                                                ₹{{ number_format($price->modal_price, 0) }}
                                                <span class="text-[10px] font-normal text-stone-400">/ {{ $price->unit }}</span>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mt-3.5 p-2.5 rounded-xl bg-stone-50 border border-stone-100 text-xs text-stone-500 text-center">
                                ಈ ಮಂಡಿಗೆ ಇಂದಿನ ದರಗಳ ಮಾಹಿತಿ ಇನ್ನೂ ಬಂದಿಲ್ಲ
                            </div>
                        @endif
                    </div>

                    <!-- Action Bar: Google Maps Direction + Details + WhatsApp Share -->
                    <div class="pt-3 border-t border-stone-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2">
                            <!-- Direct Google Maps Navigation -->
                            <a href="{{ $market->google_maps_url }}" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition active:scale-95">
                                <span>🗺️</span>
                                <span>Google Maps ನಲ್ಲಿ ದಾರಿ ನೋಡಿ</span>
                            </a>

                            <!-- View Mandi Profile -->
                            <a href="{{ route('farmer.markets.show', $market->code) }}" 
                               class="px-2.5 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold transition">
                                ಪೂರ್ಣ ವಿವರ
                            </a>
                        </div>

                        <!-- WhatsApp Share -->
                        @php
                            $shareText = "🌾 *ಕೃಷಿ ಬಾಂಧವ — ಹತ್ತಿರದ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ*\n"
                                . "🏛️ *" . $market->name . " APMC*\n"
                                . "📍 ದೂರ: " . $market->distance_km . " ಕಿ.ಮೀ (" . $market->direction_kn . ")\n"
                                . "🗺️ ದಾರಿ: " . $market->google_maps_url . "\n"
                                . "👉 ಮಂಡಿ ದರಗಳನ್ನು ವೀಕ್ಷಿಸಲು: " . route('farmer.markets.show', $market->code);
                        @endphp
                        <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" 
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

<!-- JavaScript for Browser Geolocation -->
<script>
function detectFarmerLocation() {
    const btn = document.getElementById('gpsDetectBtn');
    const icon = document.getElementById('gpsBtnIcon');
    const text = document.getElementById('gpsBtnText');
    const statusMsg = document.getElementById('gpsStatusMessage');

    if (!navigator.geolocation) {
        showGpsError('ನಿಮ್ಮ ಬ್ರೌಸರ್ ಜಿಪಿಎಸ್ ಸ್ಥಳ ಗುರುತಿಸುವಿಕೆಯನ್ನು ಬೆಂಬಲಿಸುವುದಿಲ್ಲ. ದಯವಿಟ್ಟು ಜಿಲ್ಲೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ.');
        return;
    }

    // Set loading state
    icon.innerHTML = '⏳';
    text.innerText = 'ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲಾಗುತ್ತಿದೆ...';
    btn.disabled = true;
    btn.classList.add('opacity-75');
    statusMsg.classList.add('hidden');

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            // Preserve current radius and crop filters if present
            const url = new URL(window.location.href);
            url.searchParams.set('lat', lat.toFixed(6));
            url.searchParams.set('lon', lon.toFixed(6));
            url.searchParams.delete('district'); // Clear manual district when GPS is used

            window.location.href = url.toString();
        },
        function (error) {
            btn.disabled = false;
            btn.classList.remove('opacity-75');
            icon.innerHTML = '📍';
            text.innerText = 'ಪ್ರಸ್ತುತ ಸ್ಥಳದಿಂದ ಹುಡುಕಿ (Locate Me)';

            let msg = 'ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲು ಸಾಧ್ಯವಾಗಲಿಲ್ಲ. ದಯವಿಟ್ಟು ಜಿಲ್ಲಾ ಪಟ್ಟಿಯಿಂದ ಆಯ್ಕೆಮಾಡಿ.';
            if (error.code === error.PERMISSION_DENIED) {
                msg = '⚠️ ಸ್ಥಳ ಪ್ರವೇಶ ನಿರಾಕರಿಸಲಾಗಿದೆ (Location Permission Denied). ದಯವಿಟ್ಟು ಬ್ರೌಸರ್ ಸೆಟ್ಟಿಂಗ್‌ನಲ್ಲಿ ಅನುಮತಿ ನೀಡಿ ಅಥವಾ ಕೆಳಗಿನ ಜಿಲ್ಲಾ ಪಟ್ಟಿಯಿಂದ ಆಯ್ಕೆಮಾಡಿ.';
            } else if (error.code === error.TIMEOUT) {
                msg = '⚠️ ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚುವಿಕೆ ಸಮಯ ಮೀರಿದೆ. ದಯವಿಟ್ಟು ಪುನಃ ಪ್ರಯತ್ನಿಸಿ.';
            }
            showGpsError(msg);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

function showGpsError(message) {
    const statusMsg = document.getElementById('gpsStatusMessage');
    statusMsg.innerText = message;
    statusMsg.classList.remove('hidden');
}

function clearGpsAndSubmit() {
    const form = document.getElementById('nearbyFilterForm');
    // Remove lat and lon hidden inputs when manual district is changed
    const latInput = form.querySelector('input[name="lat"]');
    const lonInput = form.querySelector('input[name="lon"]');
    if (latInput) latInput.remove();
    if (lonInput) lonInput.remove();
    form.submit();
}
</script>
@endsection
