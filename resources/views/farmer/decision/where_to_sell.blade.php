@extends('layouts.farmer')

@section('title', 'Where to Sell? (ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?) — Krushi Baandhava')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-12">
    <!-- Breadcrumb & Header -->
    <div>
        <nav class="flex items-center text-xs font-medium text-slate-500 mb-2 space-x-1">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">ಹೋಮ್ (Home)</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Where to Sell (ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?)</span>
        </nav>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="text-3xl">⚖️</span>
                    <span>Where to Sell? (ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು?)</span>
                </h1>
                <p class="text-sm text-slate-600 mt-1">
                    ನಿವ್ವಳ ಆದಾಯ ಹೋಲಿಕೆ · ಸಾರಿಗೆ ವೆಚ್ಚ ಮತ್ತು ಮಂಡಿ ಶುಲ್ಕ ಕಳೆದ ನಂತರ ನಿಮ್ಮ ಕೈಗೆ ಸಿಗುವ ನೈಜ ಲಾಭದ ಲೆಕ್ಕಾಚಾರ
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    ✓ 100% ಮುಕ್ತ ಹಾಗೂ ಉಚಿತ (Free & Transparent)
                </span>
            </div>
        </div>
    </div>

    <!-- Simulator Form Card -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('farmer.decision.where-to-sell') }}" id="decisionForm" class="space-y-5">
            <!-- 1. Crop Selection -->
            <div>
                <label for="cropSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    ೧. ಬೆಳೆ ಆಯ್ಕೆ ಮಾಡಿ (Select Crop)
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2 mb-3">
                    @foreach($crops->take(6) as $c)
                        <button type="button" 
                                onclick="selectCrop('{{ $c->slug }}')"
                                class="flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-bold transition text-left {{ ($params['crop'] === $c->slug) ? 'bg-emerald-50 border-emerald-600 text-emerald-900 ring-2 ring-emerald-500/20 shadow-sm' : 'border-slate-200 text-slate-700 hover:border-emerald-300 hover:bg-slate-50' }}">
                            <span class="text-base">{{ $c->icon_emoji ?? '🌱' }}</span>
                            <span class="truncate">{{ $c->name_kn ?? $c->name }}</span>
                        </button>
                    @endforeach
                </div>
                <select name="crop" id="cropSelect" class="w-full text-sm font-semibold rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-sm">
                    @foreach($crops as $c)
                        <option value="{{ $c->slug }}" {{ ($params['crop'] === $c->slug) ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->name_kn ?? $c->name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Quantity & Location Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Quantity Input -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        ೨. ಕಟಾವು ಪ್ರಮಾಣ (Harvest Quantity)
                    </label>
                    <div class="flex items-center gap-2 mt-2">
                        <input type="number" 
                               name="quantity" 
                               id="quantityInput"
                               step="0.5" 
                               min="0.5" 
                               max="1000"
                               value="{{ $params['quantity'] }}" 
                               class="w-full text-base font-bold rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-sm">
                        <span class="text-sm font-bold text-slate-600 whitespace-nowrap">ಕ್ವಿಂಟಾಲ್ (Qtl)</span>
                    </div>
                    <!-- Quick Pill buttons -->
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <button type="button" onclick="setQuantity(5)" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 bg-white hover:bg-emerald-50 hover:text-emerald-800 transition">5 Q</button>
                        <button type="button" onclick="setQuantity(10)" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 bg-white hover:bg-emerald-50 hover:text-emerald-800 transition">10 Q</button>
                        <button type="button" onclick="setQuantity(20)" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 bg-white hover:bg-emerald-50 hover:text-emerald-800 transition">20 Q</button>
                        <button type="button" onclick="setQuantity(50)" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 bg-white hover:bg-emerald-50 hover:text-emerald-800 transition">50 Q</button>
                    </div>
                </div>

                <!-- Origin Location Picker -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        ೩. ನಿಮ್ಮ ಸ್ಥಳ (Your Origin Location)
                    </label>
                    
                    <input type="hidden" name="lat" id="latInput" value="{{ $params['lat'] }}">
                    <input type="hidden" name="lng" id="lngInput" value="{{ $params['lng'] }}">

                    <div class="mt-2 space-y-2">
                        <!-- GPS Button -->
                        <button type="button" 
                                id="gpsBtn"
                                onclick="acquireGpsLocation()"
                                class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold transition {{ ($params['lat'] && $params['lng']) ? 'bg-emerald-700 text-white shadow-sm' : 'bg-white border border-slate-300 text-slate-700 hover:bg-emerald-50 hover:border-emerald-300' }}">
                            <span id="gpsIcon">📍</span>
                            <span id="gpsLabel">
                                {{ ($params['lat'] && $params['lng']) ? 'ಜಿಪಿಎಸ್ ಸ್ಥಳ ಸಕ್ರಿಯವಾಗಿದೆ (GPS Active)' : 'ನನ್ನ ಜಿಪಿಎಸ್ ಸ್ಥಳ ಬಳಸಿ (Use GPS)' }}
                            </span>
                        </button>

                        <!-- District Dropdown Fallback -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <select name="district_id" id="districtSelect" onchange="onDistrictChange()" class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">— ಜಿಲ್ಲೆ (District) —</option>
                                    @foreach($districts as $d)
                                        <option value="{{ $d->id }}" {{ ($params['district_id'] == $d->id) ? 'selected' : '' }}>
                                            {{ $d->name }} ({{ $d->name_kn ?? '' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select name="taluk_id" id="talukSelect" class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">— ತಾಲೂಕು (Taluk) —</option>
                                    @if($params['district_id'])
                                        @php
                                            $currDistrict = $districts->firstWhere('id', $params['district_id']);
                                        @endphp
                                        @if($currDistrict)
                                            @foreach($currDistrict->taluks as $t)
                                                <option value="{{ $t->id }}" {{ ($params['taluk_id'] == $t->id) ? 'selected' : '' }}>
                                                    {{ $t->name }} ({{ $t->name_kn ?? '' }})
                                                </option>
                                            @endforeach
                                        @endif
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Transport Vehicle Selection -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        ೪. ಸಾರಿಗೆ ವಾಹನ (Transport Vehicle)
                    </label>
                    <button type="button" onclick="toggleCustomRate()" class="text-xs font-semibold text-emerald-700 hover:underline">
                        ದರ ಬದಲಿಸಿ (Custom Rate)
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($vehicles as $k => $v)
                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition {{ ($params['vehicle'] === $k) ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-500/20 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                            <input type="radio" name="vehicle" value="{{ $k }}" {{ ($params['vehicle'] === $k) ? 'checked' : '' }} onchange="this.form.submit()" class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>{{ $v['icon'] }}</span>
                                    <span>{{ $v['name_kn'] }}</span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    ₹{{ $v['rate_per_km'] }}/km · ಗರಿಷ್ಠ {{ $v['max_capacity_qtl'] }} Qtl
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                <!-- Custom Rate Input Drawer -->
                <div id="customRateDrawer" class="{{ $params['custom_rate'] ? '' : 'hidden' }} mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200 flex items-center gap-3">
                    <span class="text-amber-800 text-xs font-bold">ನಿಮ್ಮ ಸ್ವಂತ ಸಾರಿಗೆ ದರ (Custom ₹/km):</span>
                    <input type="number" name="custom_rate" step="1" min="5" max="200" value="{{ $params['custom_rate'] }}" placeholder="ಉದಾ: 25" class="w-28 text-xs font-bold rounded-lg border-amber-300 focus:ring-amber-500">
                    <span class="text-xs text-amber-700">₹ ಪ್ರತಿ ಕಿ.ಮೀ</span>
                </div>
            </div>

            <!-- 4. Sorting & Calculate Action -->
            <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-500">ವಿಂಗಡಣೆ (Sort by):</span>
                    <div class="inline-flex rounded-lg shadow-sm border border-slate-200 p-0.5 bg-slate-50">
                        <button type="submit" name="sort" value="net_realization" class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ ($params['sort'] === 'net_realization') ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-emerald-700' }}">
                            ನಿವ್ವಳ ಲಾಭ (Net Profit)
                        </button>
                        <button type="submit" name="sort" value="price_desc" class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ ($params['sort'] === 'price_desc') ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-emerald-700' }}">
                            ದರ (Price)
                        </button>
                        <button type="submit" name="sort" value="distance_asc" class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ ($params['sort'] === 'distance_asc') ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-emerald-700' }}">
                            ದೂರ (Distance)
                        </button>
                    </div>
                </div>

                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm shadow-md transition">
                    <span>ಲೆಕ್ಕಾಚಾರ ಮಾಡಿ (Calculate Realization)</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>
    </div>

    @if(isset($comparison['markets']) && $comparison['markets']->isNotEmpty())
        <!-- #1 Best Decision Recommendation Banner -->
        @php
            $rec = $comparison['recommended_market'];
            $nearest = $comparison['nearest_market'];
        @endphp
        @if($rec)
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-800 via-emerald-900 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-emerald-600/30">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400 text-slate-950 font-black text-xs tracking-wider uppercase shadow-sm">
                            <span>★</span>
                            <span>#1 ಶಿಫಾರಸು ಮಾಡಲಾದ ಮಂಡಿ (Best Realization)</span>
                        </div>
                        <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight">
                            {{ $rec['market_name_kn'] }} ({{ $rec['market_name'] }})
                        </h2>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-emerald-200 text-xs sm:text-sm font-medium">
                            <span>📍 {{ $rec['district_name_kn'] }} ({{ $rec['district_name'] }})</span>
                            <span>•</span>
                            <span>🛣️ {{ $rec['distance_km'] }} ಕಿ.ಮೀ (ರಸ್ತೆ ದೂರ)</span>
                            <span>•</span>
                            <span>⏱️ ಅಂದಾಜು {{ $rec['transit_hours'] }} ಗಂಟೆ ಪ್ರಯಾಣ</span>
                        </div>
                        <div class="pt-2 text-xs text-emerald-300">
                            {{ $rec['verdict_kn'] }}
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row md:flex-col items-start md:items-end justify-center gap-3 shrink-0">
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 text-left md:text-right min-w-[200px]">
                            <div class="text-xs uppercase tracking-wider text-emerald-200 font-bold">ಕೈಗೆ ಸಿಗುವ ನಿವ್ವಳ ಲಾಭ (Net In-Pocket)</div>
                            <div class="text-3xl sm:text-4xl font-black text-white mt-1">
                                ₹{{ number_format($rec['net_realization'], 0) }}
                            </div>
                            <div class="text-xs text-amber-300 font-bold mt-1">
                                ₹{{ number_format($rec['net_rate_per_qtl'], 0) }} / ಕ್ವಿಂಟಾಲ್
                            </div>
                        </div>

                        <a href="{{ $rec['google_maps_url'] }}" target="_blank" rel="noopener" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs tracking-wide uppercase shadow transition">
                            <span>ನಕ್ಷೆಯಲ್ಲಿ ಮಾರ್ಗ (Navigate)</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Comparison Table / Cards Header -->
        <div class="flex items-center justify-between pt-2">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <span>📊</span>
                    <span>ಮಂಡಿಗಳ ಸಮಗ್ರ ಹೋಲಿಕೆ ಪಟ್ಟಿ (Mandi Comparison Matrix)</span>
                </h3>
                <p class="text-xs text-slate-500">
                    {{ $comparison['crop']->name_kn }} · ಒಟ್ಟು {{ $comparison['markets_count'] }} ಮಾರುಕಟ್ಟೆಗಳ ಲೆಕ್ಕಾಚಾರ
                </p>
            </div>
            <div class="text-xs text-slate-500">
                ಪ್ರಮಾಣ: <b>{{ $params['quantity'] }} Qtl</b> · ವಾಹನ: <b>{{ $comparison['vehicle']['name_kn'] }}</b>
            </div>
        </div>

        <!-- Mandi List Cards -->
        <div class="space-y-4">
            @foreach($comparison['markets'] as $index => $m)
                <div class="bg-white rounded-2xl border {{ $m['is_nearest'] ? 'border-blue-300 bg-blue-50/20' : ($index === 0 ? 'border-emerald-400 ring-1 ring-emerald-400 shadow-md' : 'border-slate-200 shadow-sm') }} p-5 transition hover:border-emerald-300">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Mandi Details -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-slate-900 text-white text-xs font-black flex items-center justify-center">
                                    #{{ $index + 1 }}
                                </span>
                                <h4 class="text-base font-extrabold text-slate-900">
                                    {{ $m['market_name_kn'] }} <span class="text-slate-500 font-normal">({{ $m['market_name'] }})</span>
                                </h4>
                                
                                @if($index === 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        ★ ಅತ್ಯಧಿಕ ನಿವ್ವಳ ಲಾಭ (Best Profit)
                                    </span>
                                @endif

                                @if($m['is_nearest'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                        📍 ಅತ್ಯಂತ ಹತ್ತಿರದ ಮಂಡಿ (Nearest Base)
                                    </span>
                                @endif

                                @if(!$m['is_nearest'] && $m['net_diff_vs_nearest'] >= 300)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        +₹{{ number_format($m['net_diff_vs_nearest'], 0) }} ಹೆಚ್ಚುವರಿ ಲಾಭ
                                    </span>
                                @elseif(!$m['is_nearest'] && $m['net_diff_vs_nearest'] <= -200)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        -₹{{ number_format(abs($m['net_diff_vs_nearest']), 0) }} ಕಡಿಮೆ (ಸಾರಿಗೆ ನಷ್ಟ)
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                                <span>ಜಿಲ್ಲೆ: <b>{{ $m['district_name_kn'] }} ({{ $m['district_name'] }})</b></span>
                                <span>•</span>
                                <span>ಮಾರುಕಟ್ಟೆ ದರ: <b class="text-slate-900">₹{{ number_format($m['modal_price'], 0) }}/Q</b></span>
                                <span>•</span>
                                <span>ದೂರ: <b>{{ $m['distance_km'] }} ಕಿ.ಮೀ</b> (~{{ $m['transit_hours'] }} ಗಂಟೆ)</span>
                            </div>

                            <div class="text-xs font-semibold {{ $m['badge_type'] === 'profit' ? 'text-emerald-700' : ($m['badge_type'] === 'loss' ? 'text-rose-700' : 'text-slate-600') }}">
                                {{ $m['verdict_kn'] }}
                            </div>
                        </div>

                        <!-- Financial Breakdown Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                            <div>
                                <div class="text-[10px] uppercase font-bold text-slate-500">ಒಟ್ಟು ಆದಾಯ (Gross)</div>
                                <div class="text-xs font-bold text-slate-800 mt-0.5">₹{{ number_format($m['gross_revenue'], 0) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] uppercase font-bold text-rose-600">ಸಾರಿಗೆ ವೆಚ್ಚ (Haulage)</div>
                                <div class="text-xs font-bold text-rose-700 mt-0.5">-₹{{ number_format($m['transport_cost'], 0) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] uppercase font-bold text-slate-500">ಮಂಡಿ ಶುಲ್ಕ (Cess+Hamali)</div>
                                <div class="text-xs font-bold text-slate-700 mt-0.5">-₹{{ number_format($m['apmc_cess'] + $m['hamali'], 0) }}</div>
                            </div>
                            <div class="bg-emerald-100/60 rounded-lg p-1 border border-emerald-300">
                                <div class="text-[10px] uppercase font-black text-emerald-900">ನಿವ್ವಳ ಆದಾಯ (Net)</div>
                                <div class="text-sm font-black text-emerald-800 mt-0.5">₹{{ number_format($m['net_realization'], 0) }}</div>
                            </div>
                        </div>

                        <!-- Navigation Button -->
                        <div class="shrink-0 flex items-center justify-end">
                            <a href="{{ $m['google_maps_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-white border border-slate-300 text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 hover:border-emerald-300 transition">
                                <span>ನಕ್ಷೆ (Map)</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- No active markets notice -->
        <div class="bg-amber-50 rounded-2xl p-6 border border-amber-200 text-center space-y-3">
            <span class="text-3xl">⚠️</span>
            <h3 class="text-base font-bold text-amber-900">
                ಈ ಬೆಳೆಗೆ ಇತ್ತೀಚಿನ ಸಕ್ರಿಯ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ
            </h3>
            <p class="text-xs text-amber-800 max-w-lg mx-auto">
                ಆಯ್ಕೆಮಾಡಿದ ಬೆಳೆಗೆ ಕರ್ನಾಟಕದ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳಲ್ಲಿ ಇತ್ತೀಚೆಗೆ ವ್ಯಾಪಾರ ದಾಖಲಾಗಿಲ್ಲ. ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ ಆಯ್ಕೆಮಾಡಿ ಅಥವಾ ಜಿಲ್ಲಾ ಫಿಲ್ಟರ್ ಪರಿಶೀಲಿಸಿ.
            </p>
        </div>
    @endif

    <!-- Transparent Formula Card -->
    <div class="bg-slate-100 rounded-2xl p-5 border border-slate-200 text-xs text-slate-600 space-y-2">
        <div class="font-bold text-slate-800 flex items-center gap-1.5">
            <span>ℹ️</span>
            <span>ಪಾರದರ್ಶಕ ಲೆಕ್ಕಾಚಾರ ವಿಧಾನ (Transparent Net Realization Formula):</span>
        </div>
        <p>
            <b>ನಿವ್ವಳ ಆದಾಯ (Net Realization)</b> = ಒಟ್ಟು ಆದಾಯ (ಪ್ರಮಾಣ × ಮಂಡಿ ದರ) - ಸಾರಿಗೆ ವೆಚ್ಚ (ರಸ್ತೆ ದೂರ × ವಾಹನ ದರ) - ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ಸೆಸ್ (1.5%) - ಹಮಾಲಿ ವೆಚ್ಚ (₹10/ಕ್ವಿಂಟಾಲ್).
        </p>
        <p class="text-slate-500">
            ಗ್ರಾಮೀಣ ರಸ್ತೆಗಳ ತಿರುವುಗಳನ್ನು ಪರಿಗಣಿಸಿ ನೇರ ರೇಖೆಯ ದೂರಕ್ಕೆ <b>1.2x ರಸ್ತೆ ಗುಣಕ</b> ಅನ್ವಯಿಸಲಾಗಿದೆ. ಯಾವುದೇ ರಹಸ್ಯ ರೇಟಿಂಗ್ ಅಥವಾ ಕೃತಕ ಸ್ಕೋರ್ ಇಲ್ಲ.
        </p>
    </div>
</div>

<script>
function selectCrop(slug) {
    document.getElementById('cropSelect').value = slug;
    document.getElementById('decisionForm').submit();
}

function setQuantity(q) {
    document.getElementById('quantityInput').value = q;
}

function toggleCustomRate() {
    const el = document.getElementById('customRateDrawer');
    el.classList.toggle('hidden');
}

function onDistrictChange() {
    // When district changes, clear GPS coordinates so district centroid is used
    document.getElementById('latInput').value = '';
    document.getElementById('lngInput').value = '';
    document.getElementById('decisionForm').submit();
}

function acquireGpsLocation() {
    const gpsBtn = document.getElementById('gpsBtn');
    const gpsLabel = document.getElementById('gpsLabel');
    const gpsIcon = document.getElementById('gpsIcon');

    if (!navigator.geolocation) {
        alert('ನಿಮ್ಮ ಬ್ರೌಸರ್‌ನಲ್ಲಿ GPS ಸೌಲಭ್ಯ ಲಭ್ಯವಿಲ್ಲ (GPS not supported).');
        return;
    }

    gpsLabel.textContent = 'ಸ್ಥಳ ಪಡೆಯಲಾಗುತ್ತಿದೆ... (Detecting GPS)';
    gpsIcon.textContent = '⏳';

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            document.getElementById('latInput').value = pos.coords.latitude;
            document.getElementById('lngInput').value = pos.coords.longitude;
            // Clear manual district and submit
            document.getElementById('districtSelect').value = '';
            document.getElementById('talukSelect').value = '';
            document.getElementById('decisionForm').submit();
        },
        function (err) {
            gpsLabel.textContent = 'GPS ವಿಫಲವಾಗಿದೆ - ಜಿಲ್ಲೆ ಆಯ್ಕೆಮಾಡಿ';
            gpsIcon.textContent = '⚠️';
            alert('ಸ್ಥಳ ಪಡೆಯಲು ಸಾಧ್ಯವಾಗಲಿಲ್ಲ. ದಯವಿಟ್ಟು ಕೆಳಗಿನ ಜಿಲ್ಲೆ ಮತ್ತು ತಾಲೂಕು ಆಯ್ಕೆಮಾಡಿ.');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}
</script>
@endsection
