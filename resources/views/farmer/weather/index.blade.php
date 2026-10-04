@extends('layouts.farmer')

@section('title', app()->getLocale() === 'en' ? 'Hyperlocal Weather Forecast & Farm Advisories — Krushi Baandhava' : 'ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ & ಕೃಷಿ ಸಲಹೆಗಳು (Weather & Farm Advisories) — ಕೃಷಿ ಬಾಂಧವ')

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-stone-500 font-medium">
        <a href="{{ route('home') }}" class="hover:text-emerald-700">{{ $activeLocale === 'en' ? 'Home' : 'ಮುಖಪುಟ' }}</a>
        <span>&rsaquo;</span>
        <span class="text-stone-900 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            {!! $activeLocale === 'en' ? 'Weather Forecast & Advisories' : 'ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ & ಕೃಷಿ ಸಲಹೆ' !!}
        </span>
    </nav>

    <!-- District Selector Tabs (Horizontal Scroll) -->
    <div class="space-y-1.5">
        <div class="flex items-center justify-between text-xs text-stone-500 font-bold px-1">
            <span class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                🏛️ {{ $activeLocale === 'en' ? 'Select Karnataka District:' : 'ಕರ್ನಾಟಕ ಜಿಲ್ಲೆ ಆಯ್ಕೆ (Select District):' }}
            </span>
            <span class="text-[11px] text-stone-400">
                {{ $activeLocale === 'en' ? '31 Districts Forecast' : '31 ಜಿಲ್ಲೆಗಳ ಮುನ್ಸೂಚನೆ' }}
            </span>
        </div>
        <div id="districtTabsContainer"
             x-data="{
                 scrollToActive() {
                     const active = this.$refs.activeTab;
                     if (!active) return;
                     // Horizontal centering within the scroll container
                     const container = this.$el;
                     const left = active.offsetLeft - (container.clientWidth / 2) + (active.clientWidth / 2);
                     container.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
                 }
             }"
             x-init="$nextTick(() => { scrollToActive(); setTimeout(() => scrollToActive(), 150); })"
             class="flex items-center gap-2 overflow-x-auto pb-2 text-xs scroll-smooth">
            @foreach($allDistricts as $d)
                @php $isActive = $activeDistrict && $activeDistrict->id == $d->id; @endphp
                <a href="{{ route('farmer.weather.index', ['district' => $d->id]) }}"
                   @if($isActive) x-ref="activeTab" id="activeDistrictTab" data-active="true" @endif
                   class="px-4 py-2 rounded-2xl font-bold whitespace-nowrap transition shadow-xs shrink-0 {{ $isActive ? 'bg-[#1C5A2C] text-white shadow-sm ring-2 ring-[#1C5A2C]/20' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200/90' }}">
                    <span>{{ $activeLocale === 'en' ? $d->name : ($d->name_kn ?? $d->name) }}</span>
                    @if($activeLocale === 'kn' && $d->name_kn)
                        <span class="font-sans font-normal opacity-90 text-[10px]">({{ $d->name }})</span>
                    @endif
                </a>
            @endforeach
        </div>
        <script>
            // Instant pure-JS fallback for page load (fires before full Alpine hydration if needed)
            (function() {
                function centerActiveTab() {
                    var container = document.getElementById('districtTabsContainer');
                    var active = document.getElementById('activeDistrictTab');
                    if (!container || !active) return;
                    var left = active.offsetLeft - (container.clientWidth / 2) + (active.clientWidth / 2);
                    container.scrollLeft = Math.max(0, left);
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', centerActiveTab);
                } else {
                    centerActiveTab();
                }
                window.addEventListener('load', centerActiveTab);
            })();
        </script>
    </div>

    <!-- Today's Weather Hero Banner (Atmospheric Radial Gradient & Topographic Contours like Schemes Carousel) -->
    @if($todayForecast)
        <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 shadow-[0_16px_40px_-10px_rgba(11,43,23,0.50)] border border-emerald-500/30 text-white"
             style="contain: paint; background: radial-gradient(circle at 85% 15%, #257044 0%, #154D2B 45%, #0B2B17 100%);">

            <!-- Atmospheric Elevation Contours & Radial Glow (Consistent with Schemes Carousel & Home Weather Card) -->
            <svg class="absolute inset-0 w-full h-full pointer-events-none select-none z-0" preserveAspectRatio="none" viewBox="0 0 800 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M-20 180 Q 240 70, 500 170 T 820 90" stroke="currentColor" stroke-width="1.8" stroke-opacity="0.18" class="text-emerald-200" />
                <path d="M-20 215 Q 260 110, 520 205 T 820 135" stroke="currentColor" stroke-width="1.4" stroke-opacity="0.14" class="text-white" stroke-dasharray="6 4" />
                <path d="M-20 245 Q 280 150, 540 235 T 820 175" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.10" class="text-emerald-300" />
                <ellipse cx="680" cy="45" rx="160" ry="110" fill="url(#weatherAtmGlow)" opacity="0.35" />
                <defs>
                    <radialGradient id="weatherAtmGlow" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#4ade80" stop-opacity="0.55"/>
                        <stop offset="100%" stop-color="#154D2B" stop-opacity="0"/>
                    </radialGradient>
                </defs>
            </svg>

            <!-- Top-Right Concentric Weather Arcs Overlay -->
            <svg class="absolute -top-10 -right-10 w-64 h-64 sm:w-80 sm:h-80 pointer-events-none text-white select-none z-0" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="200" cy="0" r="170" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.18" />
                <circle cx="200" cy="0" r="135" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.14" />
                <circle cx="200" cy="0" r="100" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.10" />
                <circle cx="200" cy="0" r="65" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.07" />
            </svg>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <!-- Left: Temperature & Main Condition -->
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-800/80 border border-emerald-600/40 text-emerald-200 text-xs font-bold font-sans">
                        <span>📍 {{ $activeLocale === 'en' ? $activeDistrict->name : ($activeDistrict->name_kn ?? $activeDistrict->name) }}</span>
                        <span>•</span>
                        <span>{{ $activeLocale === 'en' ? "Today's Weather" : 'ಇಂದಿನ ಹವಾಮಾನ' }}</span>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="text-5xl sm:text-6xl">{{ $todayForecast->weather_icon }}</span>
                        <div>
                            <div class="text-4xl sm:text-5xl font-black tracking-tight leading-none font-sans">
                                {{ $todayForecast->displayTemperature() }}°C
                            </div>
                            <div class="text-sm sm:text-base font-bold text-emerald-200 mt-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $todayForecast->conditionLabel($activeLocale) }}
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-emerald-100/90 font-sans">
                        <span>{{ $activeLocale === 'en' ? 'Date:' : 'ದಿನಾಂಕ:' }} <strong>{{ $todayForecast->forecast_date->format('d M Y') }} ({{ $activeLocale === 'en' ? $todayForecast->day_name_en : $todayForecast->day_name_kn }})</strong></span>
                    </div>
                </div>

                <!-- Right: Weather Metrics Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 bg-black/20 p-4 rounded-2xl border border-white/10 shrink-0">
                    <!-- Rain Probability -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">🌧️</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Rain Chance' : 'ಮಳೆ ಸಾಧ್ಯತೆ' }}
                        </div>
                        <div class="text-base font-extrabold text-white font-sans">
                            {{ round($todayForecast->precipitation_probability) }}%
                        </div>
                    </div>

                    <!-- Humidity -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">💧</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Humidity' : 'ತೇವಾಂಶ' }}
                        </div>
                        <div class="text-base font-extrabold text-white font-sans">
                            {{ round($todayForecast->current_humidity ?? 65) }}%
                        </div>
                    </div>

                    <!-- Wind Speed -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">💨</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Wind Speed' : 'ಗಾಳಿಯ ವೇಗ' }}
                        </div>
                        <div class="text-base font-extrabold text-white font-sans">
                            {{ round($todayForecast->current_wind_speed ?? 12) }} <span class="text-[10px] font-normal">km/h</span>
                        </div>
                    </div>

                    <!-- Temperature Range -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">🌡️</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'Min - Max' : 'ಕನಿಷ್ಠ - ಗರಿಷ್ಠ' }}
                        </div>
                        <div class="text-sm font-extrabold text-white font-sans">
                            {{ round($todayForecast->temp_min) }}° - {{ round($todayForecast->temp_max) }}°
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Share Button -->
            @php
                $distDisplayName = $activeLocale === 'en' ? $activeDistrict->name : ($activeDistrict->name_kn ?? $activeDistrict->name);
                $shareWeather = ($activeLocale === 'en'
                    ? "🌤️ *Krushi Baandhava — {$distDisplayName} Today's Weather & Advisory*\n"
                        . "🌡️ Temperature: " . $todayForecast->displayTemperature() . "°C (" . $todayForecast->conditionLabel('en') . ")\n"
                        . "🌧️ Rain Probability: " . round($todayForecast->precipitation_probability) . "%\n"
                        . "🌾 *Advisory:* " . $todayForecast->advisoryLabel('en') . "\n"
                        . "👉 View full 7-day forecast: " . url()->current()
                    : "🌤️ *ಕೃಷಿ ಬಾಂಧವ — {$distDisplayName} ಇಂದಿನ ಹವಾಮಾನ & ಕೃಷಿ ಸಲಹೆ*\n"
                        . "🌡️ ಉಷ್ಣಾಂಶ: " . $todayForecast->displayTemperature() . "°C (" . $todayForecast->conditionLabel('kn') . ")\n"
                        . "🌧️ ಮಳೆ ಸಾಧ್ಯತೆ: " . round($todayForecast->precipitation_probability) . "%\n"
                        . "🌾 *ಕೃಷಿ ಸಲಹೆ:* " . $todayForecast->advisoryLabel('kn') . "\n"
                        . "👉 7 ದಿನಗಳ ಸಂಪೂರ್ಣ ಮುನ್ಸೂಚನೆಗೆ: " . url()->current());
            @endphp
            <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
                <span class="text-xs text-emerald-200/80 font-sans">
                    {{ $activeLocale === 'en' ? 'Hyperlocal Forecast • Powered by Open-Meteo' : 'ಹೈಪರ್‌ಲೋಕಲ್ ಮುನ್ಸೂಚನೆ • Open-Meteo ನವೀಕರಣ' }}
                </span>
                <a href="https://wa.me/?text={{ rawurlencode($shareWeather) }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-stone-900 font-black text-xs shadow-xs transition active:scale-95 cursor-pointer">
                    <span>💬</span>
                    <span>{{ $activeLocale === 'en' ? 'Share Weather' : 'ಹವಾಮಾನ ಸಲಹೆ ಶೇರ್ ಮಾಡಿ' }}</span>
                </a>
            </div>
        </div>

        <!-- Prominent Agricultural Advisory Card -->
        <div class="bg-amber-50 border-2 border-amber-200/90 rounded-3xl p-5 sm:p-6 shadow-xs relative overflow-hidden">
            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-400 text-stone-900 flex items-center justify-center font-black text-2xl shadow-inner shrink-0">
                    🌾
                </div>
                <div class="space-y-1 flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-black text-amber-950 tracking-tight flex items-center gap-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span>{!! $activeLocale === 'en' ? "Today's Farm & Crop Protection Advisory" : 'ಇಂದಿನ ಕೃಷಿ & ಬೆಳೆ ಸಂರಕ್ಷಣಾ ಸಲಹೆ' !!}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-200 text-amber-900 uppercase font-sans">Farm Advisory</span>
                        </h3>
                    </div>
                    <p class="text-sm font-bold text-amber-950 leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $todayForecast->advisoryLabel($activeLocale) }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- 7-Day Hyperlocal Forecast Section (Modern Mobile-First 2-Column Responsive Grid) -->
    <div class="space-y-3.5 sm:space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base sm:text-lg font-black text-[#1C5A2C] tracking-tight flex items-center gap-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>📅</span>
                    <span>{{ $activeLocale === 'en' ? 'Upcoming 7-Day Weather Forecast' : 'ಮುಂದಿನ 7 ದಿನಗಳ ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ' }}</span>
                </h2>
                <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Daily rain probability, temperature range, and farming alerts' : 'ದಿನವಾರು ಮಳೆ ಸಾಧ್ಯತೆ, ತಾಪಮಾನ ಶ್ರೇಣಿ ಮತ್ತು ಕೃಷಿ ಮುನ್ನೆಚ್ಚರಿಕೆಗಳು' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-4">
            @foreach($forecasts as $index => $item)
                @php
                    $isHighRain = $item->precipitation_probability >= 55;
                    $isModerateRain = $item->precipitation_probability >= 25 && $item->precipitation_probability < 55;
                    $minTemp = round($item->temp_min);
                    $maxTemp = round($item->temp_max);
                @endphp
                <div class="bg-white border-2 {{ $item->is_today ? 'border-[#1C5A2C] bg-[#F7FAF7] shadow-md ring-2 ring-[#1C5A2C]/15' : 'border-[#E2DAC8] hover:border-[#1C5A2C] shadow-2xs hover:shadow-sm' }} rounded-2xl p-3 sm:p-4 transition-all duration-200 flex flex-col justify-between gap-3 group">
                    <div class="space-y-2.5">
                        <!-- Card Top Bar: Day & Date + Today Badge -->
                        <div class="flex items-start justify-between gap-1 pb-2 border-b {{ $item->is_today ? 'border-[#B8DEC0]' : 'border-stone-100' }}">
                            <div class="min-w-0">
                                <span class="text-xs sm:text-sm font-black block truncate {{ $item->is_today ? 'text-[#1C5A2C]' : 'text-stone-900' }} {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $activeLocale === 'en' ? $item->day_name_en : $item->day_name_kn }}
                                </span>
                                <span class="text-[10px] sm:text-[11px] text-stone-400 font-semibold block font-sans">
                                    {{ $item->forecast_date->format('d M Y') }}
                                </span>
                            </div>

                            @if($item->is_today)
                                <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-black px-2 py-0.5 rounded-full bg-[#1C5A2C] text-white shadow-xs shrink-0 font-sans">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                                    <span>{{ $activeLocale === 'en' ? 'Today' : 'ಇಂದು' }}</span>
                                </span>
                            @else
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-stone-100 text-stone-600 font-sans shrink-0">
                                    {{ $item->forecast_date->format('D') }}
                                </span>
                            @endif
                        </div>

                        <!-- Weather Condition & Icon Row -->
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl sm:text-3xl shrink-0 transform group-hover:scale-110 transition-transform">{{ $item->weather_icon }}</span>
                            <div class="min-w-0">
                                <div class="font-extrabold text-stone-900 text-xs sm:text-sm leading-snug truncate {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $item->conditionLabel($activeLocale) }}
                                </div>
                            </div>
                        </div>

                        <!-- Visual Temperature Range Bar (Min to Max with Color Gradient) -->
                        <div class="bg-stone-50 rounded-xl p-2 border border-stone-200/80 space-y-1">
                            <div class="flex items-baseline justify-between text-xs font-sans">
                                <span class="text-stone-500 font-semibold text-[11px]">
                                    <span class="text-[9px] text-stone-400 block sm:inline">{{ $activeLocale === 'en' ? 'Min' : 'ಕನಿಷ್ಠ' }}:</span>
                                    <strong class="text-stone-800">{{ $minTemp }}°</strong>
                                </span>
                                <span class="text-stone-900 font-black text-xs sm:text-sm">
                                    <span class="text-[9px] text-stone-400 font-semibold block sm:inline">{{ $activeLocale === 'en' ? 'Max' : 'ಗರಿಷ್ಠ' }}:</span>
                                    <strong class="text-[#1C5A2C]">{{ $maxTemp }}°C</strong>
                                </span>
                            </div>
                            <!-- Gradient Visual Range Pill -->
                            <div class="w-full h-1.5 rounded-full bg-stone-200 overflow-hidden">
                                <div class="w-full h-full rounded-full bg-gradient-to-r from-sky-400 via-amber-400 to-emerald-600"></div>
                            </div>
                        </div>

                        <!-- Rain Probability Visual Badge & Meter -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-stone-500 font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    🌧️ {{ $activeLocale === 'en' ? 'Rain:' : 'ಮಳೆ:' }}
                                </span>
                                @if($isHighRain)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 border border-blue-200 font-sans">
                                        {{ round($item->precipitation_probability) }}%
                                    </span>
                                @elseif($isModerateRain)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-200 font-sans">
                                        {{ round($item->precipitation_probability) }}%
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-800 font-sans">
                                        {{ round($item->precipitation_probability) }}%
                                    </span>
                                @endif
                            </div>
                            <div class="w-full h-1.5 bg-stone-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $isHighRain ? 'bg-blue-600' : ($isModerateRain ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                     style="width: {{ max(6, $item->precipitation_probability) }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Day Farm Advisory Tinted Micro-Card -->
                    <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-2 text-[10px] sm:text-[11px] font-semibold text-amber-950 leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} shadow-2xs">
                        <span class="shrink-0 font-bold text-amber-900">💡</span>
                        <span class="line-clamp-2" title="{{ $item->advisoryLabel($activeLocale) }}">
                            {{ $item->advisoryLabel($activeLocale) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
