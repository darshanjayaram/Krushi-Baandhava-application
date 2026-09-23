@extends('layouts.farmer')

@section('title', 'ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ & ಕೃಷಿ ಸಲಹೆಗಳು (Weather & Farm Advisories) — ಕೃಷಿ ಬಾಂಧವ')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-xs text-stone-500 font-medium">
        <a href="{{ route('home') }}" class="hover:text-emerald-700">ಮುಖಪುಟ</a>
        <span>&rsaquo;</span>
        <span class="text-stone-900 font-bold">ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ & ಕೃಷಿ ಸಲಹೆ</span>
    </nav>

    <!-- District Selector Tabs (Horizontal Scroll) -->
    <div class="space-y-1.5">
        <div class="flex items-center justify-between text-xs text-stone-500 font-bold px-1">
            <span>🏛️ ಕರ್ನಾಟಕ ಜಿಲ್ಲೆ ಆಯ್ಕೆ (Select District):</span>
            <span class="text-[11px] text-stone-400">31 ಜಿಲ್ಲೆಗಳ ಮುನ್ಸೂಚನೆ</span>
        </div>
        <div class="flex items-center gap-2 overflow-x-auto pb-2 text-xs no-scrollbar">
            @foreach($allDistricts as $d)
                <a href="{{ route('farmer.weather.index', ['district' => $d->id]) }}"
                   class="px-4 py-2 rounded-2xl font-bold whitespace-nowrap transition shadow-xs {{ $activeDistrict && $activeDistrict->id == $d->id ? 'bg-emerald-800 text-white shadow-sm' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200/90' }}">
                    <span>{{ $d->name }}</span>
                    @if($d->name_kn)
                        <span class="font-kannada font-normal opacity-90">({{ $d->name_kn }})</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <!-- Today's Weather Hero Banner -->
    @if($todayForecast)
        <div class="bg-gradient-to-br from-emerald-900 via-emerald-850 to-stone-900 text-white rounded-3xl p-6 sm:p-8 shadow-lg relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <!-- Left: Temperature & Main Condition -->
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-800/80 border border-emerald-600/40 text-emerald-200 text-xs font-bold">
                        <span>📍 {{ $activeDistrict->name }} {{ $activeDistrict->name_kn ? "({$activeDistrict->name_kn})" : '' }}</span>
                        <span>•</span>
                        <span>ಇಂದಿನ ಹವಾಮಾನ</span>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="text-5xl sm:text-6xl">{{ $todayForecast->weather_icon }}</span>
                        <div>
                            <div class="text-4xl sm:text-5xl font-black tracking-tight leading-none">
                                {{ round($todayForecast->current_temperature ?? $todayForecast->temp_max) }}°C
                            </div>
                            <div class="text-sm sm:text-base font-bold text-emerald-200 mt-1">
                                {{ $todayForecast->weather_condition_kn }} 
                                <span class="text-xs font-normal opacity-80 font-sans">({{ $todayForecast->weather_condition_en }})</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-emerald-100/90">
                        <span>ದಿನಾಂಕ: <strong>{{ $todayForecast->forecast_date->format('d M Y') }} ({{ $todayForecast->day_name_kn }})</strong></span>
                    </div>
                </div>

                <!-- Right: Weather Metrics Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 bg-black/20 p-4 rounded-2xl border border-white/10 shrink-0">
                    <!-- Rain Probability -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">🌧️</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5">ಮಳೆ ಸಾಧ್ಯತೆ</div>
                        <div class="text-base font-extrabold text-white">
                            {{ round($todayForecast->precipitation_probability) }}%
                        </div>
                    </div>

                    <!-- Humidity -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">💧</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5">ತೇವಾಂಶ</div>
                        <div class="text-base font-extrabold text-white">
                            {{ round($todayForecast->current_humidity ?? 65) }}%
                        </div>
                    </div>

                    <!-- Wind Speed -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">💨</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5">ಗಾಳಿಯ ವೇಗ</div>
                        <div class="text-base font-extrabold text-white">
                            {{ round($todayForecast->current_wind_speed ?? 12) }} <span class="text-[10px] font-normal">km/h</span>
                        </div>
                    </div>

                    <!-- Temperature Range -->
                    <div class="text-center p-2 rounded-xl bg-white/5">
                        <div class="text-lg">🌡️</div>
                        <div class="text-xs text-emerald-200 font-semibold mt-0.5">ಕನಿಷ್ಠ - ಗರಿಷ್ಠ</div>
                        <div class="text-sm font-extrabold text-white">
                            {{ round($todayForecast->temp_min) }}° - {{ round($todayForecast->temp_max) }}°
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Share Button -->
            @php
                $shareWeather = "🌤️ *ಕೃಷಿ ಬಾಂಧವ — " . $activeDistrict->name . " ಇಂದಿನ ಹವಾಮಾನ & ಕೃಷಿ ಸಲಹೆ*\n"
                    . "🌡️ ಉಷ್ಣಾಂಶ: " . round($todayForecast->current_temperature ?? $todayForecast->temp_max) . "°C (" . $todayForecast->weather_condition_kn . ")\n"
                    . "🌧️ ಮಳೆ ಸಾಧ್ಯತೆ: " . round($todayForecast->precipitation_probability) . "%\n"
                    . "🌾 *ಕೃಷಿ ಸಲಹೆ:* " . $todayForecast->farming_advisory_kn . "\n"
                    . "👉 7 ದಿನಗಳ ಸಂಪೂರ್ಣ ಮುನ್ಸೂಚನೆಗೆ: " . url()->current();
            @endphp
            <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
                <span class="text-xs text-emerald-200/80">ಹೈಪರ್‌ಲೋಕಲ್ ಮುನ್ಸೂಚನೆ • Open-Meteo ನವೀಕರಣ</span>
                <a href="https://wa.me/?text={{ rawurlencode($shareWeather) }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-emerald-950 font-black text-xs shadow-xs transition active:scale-95">
                    <span>💬</span>
                    <span>ಹವಾಮಾನ ಸಲಹೆ ಶೇರ್ ಮಾಡಿ</span>
                </a>
            </div>
        </div>

        <!-- Prominent Kannada Agricultural Advisory Card -->
        <div class="bg-amber-50 border-2 border-amber-200/90 rounded-3xl p-5 sm:p-6 shadow-xs relative overflow-hidden">
            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-400 text-emerald-950 flex items-center justify-center font-black text-2xl shadow-inner shrink-0">
                    🌾
                </div>
                <div class="space-y-1 flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-black text-amber-950 tracking-tight flex items-center gap-2">
                            <span>ಇಂದಿನ ಕೃಷಿ & ಬೆಳೆ ಸಂರಕ್ಷಣಾ ಸಲಹೆ</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-200 text-amber-900 uppercase">Farm Advisory</span>
                        </h3>
                    </div>
                    <p class="text-sm font-bold text-amber-950 leading-relaxed font-kannada">
                        {{ $todayForecast->farming_advisory_kn }}
                    </p>
                    <p class="text-xs text-amber-800/80 font-sans italic pt-0.5">
                        {{ $todayForecast->farming_advisory_en }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- 7-Day Hyperlocal Forecast Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-stone-900 tracking-tight flex items-center gap-2">
                    <span>📅 ಮುಂದಿನ 7 ದಿನಗಳ ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ (7-Day Forecast)</span>
                </h2>
                <p class="text-xs text-stone-500">ದಿನವಾರು ಮಳೆ ಸಾಧ್ಯತೆ, ತಾಪಮಾನ ಮತ್ತು ಕೃಷಿ ಮುನ್ನೆಚ್ಚರಿಕೆಗಳು</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($forecasts as $index => $item)
                <div class="bg-white border {{ $item->is_today ? 'border-emerald-500/80 ring-2 ring-emerald-500/20' : 'border-stone-200/90' }} rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between space-y-3">
                    <div>
                        <!-- Card Top Bar: Day & Date -->
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold {{ $item->is_today ? 'text-emerald-700' : 'text-stone-800' }}">
                                    {{ $item->day_name_kn }}
                                </span>
                                <span class="text-[11px] text-stone-400 block font-sans">
                                    {{ $item->forecast_date->format('d M Y') }}
                                </span>
                            </div>

                            @if($item->is_today)
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    ಇಂದು (Today)
                                </span>
                            @else
                                <span class="text-[10px] font-semibold text-stone-400 font-sans">
                                    {{ $item->day_name_en }}
                                </span>
                            @endif
                        </div>

                        <!-- Weather Condition & Icon -->
                        <div class="mt-3 flex items-center gap-3">
                            <span class="text-3xl">{{ $item->weather_icon }}</span>
                            <div>
                                <div class="font-bold text-stone-900 text-sm">
                                    {{ $item->weather_condition_kn }}
                                </div>
                                <div class="text-[11px] text-stone-400 font-sans">
                                    {{ $item->weather_condition_en }}
                                </div>
                            </div>
                        </div>

                        <!-- Temperature Range Bar -->
                        <div class="mt-3 p-2.5 rounded-xl bg-stone-50 border border-stone-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[10px] text-stone-400 block">ಕನಿಷ್ಠ</span>
                                <span class="font-bold text-stone-700">{{ round($item->temp_min) }}°C</span>
                            </div>
                            <div class="h-6 w-px bg-stone-200"></div>
                            <div class="text-right">
                                <span class="text-[10px] text-stone-400 block">ಗರಿಷ್ಠ</span>
                                <span class="font-black text-stone-900">{{ round($item->temp_max) }}°C</span>
                            </div>
                        </div>

                        <!-- Rain Probability Meter -->
                        <div class="mt-2.5 space-y-1">
                            <div class="flex items-center justify-between text-[11px] font-semibold">
                                <span class="text-stone-500">🌧️ ಮಳೆ ಸಾಧ್ಯತೆ:</span>
                                <span class="{{ $item->precipitation_probability >= 50 ? 'text-blue-700 font-bold' : 'text-stone-700' }}">
                                    {{ round($item->precipitation_probability) }}%
                                </span>
                            </div>
                            <div class="w-full h-1.5 bg-stone-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $item->precipitation_probability >= 60 ? 'bg-blue-600' : ($item->precipitation_probability >= 30 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                     style="width: {{ max(5, $item->precipitation_probability) }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Day Advisory Footer -->
                    <div class="pt-2.5 border-t border-stone-100">
                        <div class="text-[11px] font-medium text-stone-600 line-clamp-2" title="{{ $item->farming_advisory_kn }}">
                            💡 {{ $item->farming_advisory_kn }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
