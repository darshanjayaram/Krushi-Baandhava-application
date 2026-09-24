@extends('layouts.farmer')

@section('title', 'ಕೃಷಿ ಬಾಂಧವ — ಕರ್ನಾಟಕ ರೈತರ ಮಾರುಕಟ್ಟೆ ದರಗಳು')

@section('content')
<div class="space-y-6" x-data="farmerHome()">

    <!-- 1. Hero Photographic Banner (Exact Negilu Krishi Style) -->
    <div class="rounded-3xl relative overflow-hidden shadow-lg border border-[#E8DFC8] min-h-[320px] sm:min-h-[360px] flex flex-col justify-between"
         style="background: linear-gradient(to right, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.6) 45%, rgba(0,0,0,0.2) 100%), url('/images/hero_farmer.jpg') center right / cover no-repeat;">
        
        <!-- Top Context in Hero -->
        <div class="p-6 sm:p-8 z-10 max-w-2xl space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold text-white border border-white/20">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>ನೇರ ಮಾರುಕಟ್ಟೆ ದತ್ತಾಂಶ • {{ $activeDistrict->name }}</span>
            </div>

            <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-snug font-kannada">
                ಬೆಳೆ ಬೆಳೆದಷ್ಟೇ ಸಾಕಾಗದು, ಸರಿಯಾದ ಸಮಯಕ್ಕೆ ಮಾರಾಟವೂ ಮುಖ್ಯ
            </h1>

            <!-- Subtitle and context -->
            <div class="text-xs sm:text-sm text-stone-200 font-medium font-kannada space-y-1">
                <p>ಇಂದಿನ ದರ, ಮಾರುಕಟ್ಟೆ ಮತ್ತು ಅಂದಾಜು — ಒಂದೇ ಕಡೆ.</p>
                <p class="text-[11px] text-stone-300">ದೈನಂದಿನ ಅಧಿಕೃತ ಎಪಿಎಂಸಿ ದರಗಳು • Karnataka APMC Mandis</p>
            </div>
        </div>

        <!-- 3 Floating Action Cards pinned to bottom of Hero Banner -->
        <div class="p-4 sm:p-6 z-10 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Action Card 1: District Picker -->
            <button type="button"
                    @click="$dispatch('open-location-modal')"
                    class="bg-white/95 hover:bg-white backdrop-blur-md rounded-2xl p-3.5 text-left border border-white/60 shadow-sm transition transform active:scale-95 flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shrink-0">
                        📍
                    </div>
                    <div>
                        <div class="font-extrabold text-stone-900 text-sm font-sans flex items-center gap-1">
                            <span>{{ $activeDistrict->name }}</span>
                            @if($activeDistrict->name_kn)
                                <span class="text-xs font-normal text-stone-500 font-kannada">({{ $activeDistrict->name_kn }})</span>
                            @endif
                        </div>
                        <div class="text-[11px] text-stone-500 font-kannada mt-0.5">ಹತ್ತಿರದ ಮಂಡಿ ದರಗಳನ್ನು ನೋಡಿ</div>
                    </div>
                </div>
                <span class="text-[11px] text-emerald-800 font-bold shrink-0 font-kannada group-hover:translate-x-0.5 transition">
                    ಬದಲಾಯಿಸಲು ಸ್ಪರ್ಶಿಸಿ &rsaquo;
                </span>
            </button>

            <!-- Action Card 2: WhatsApp Channel -->
            <a href="https://whatsapp.com/channel/krushi-baandhava" 
               target="_blank" 
               rel="noopener noreferrer"
               class="bg-white/95 hover:bg-white backdrop-blur-md rounded-2xl p-3.5 text-left border border-white/60 shadow-sm transition transform active:scale-95 flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                        💬
                    </div>
                    <div>
                        <div class="font-extrabold text-stone-900 text-sm font-kannada">ವಾಟ್ಸ್‌ಆ್ಯಪ್‌ನಲ್ಲಿ ಸೇರಿ</div>
                        <div class="text-[11px] text-stone-500 font-kannada mt-0.5">ದೈನಂದಿನ ದರಗಳು ಮತ್ತು ಬೆಲೆ ಎಚ್ಚರಿಕೆಗಳು</div>
                    </div>
                </div>
                <span class="text-[11px] text-emerald-800 font-bold shrink-0 font-kannada group-hover:translate-x-0.5 transition">
                    ಸೇರಿ &rsaquo;
                </span>
            </a>

            <!-- Action Card 3: Guide / How to use -->
            <a href="{{ route('farmer.articles.index') }}"
               class="bg-white/95 hover:bg-white backdrop-blur-md rounded-2xl p-3.5 text-left border border-white/60 shadow-sm transition transform active:scale-95 flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                        💡
                    </div>
                    <div>
                        <div class="font-extrabold text-stone-900 text-sm font-kannada">ಹೇಗೆ ಬಳಸುವುದು</div>
                        <div class="text-[11px] text-stone-500 font-kannada mt-0.5">ಮಾರ್ಗದರ್ಶನ - ಹಂತ ಹಂತವಾಗಿ ತೋರಿಸುತ್ತದೆ</div>
                    </div>
                </div>
                <span class="text-[11px] text-emerald-800 font-bold shrink-0 font-kannada group-hover:translate-x-0.5 transition">
                    ನೋಡಿ &rsaquo;
                </span>
            </a>
        </div>
    </div>

    <!-- 2. Main 2-Column Section (Exact Negilu Krishi Grid) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Crops & Prices Directory (8 cols) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- A. Today's Top Spotlight Rates (4 Cards) -->
            @if($topMovers->isNotEmpty() && !$search)
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-extrabold text-stone-900 tracking-tight font-kannada">
                                ಇಂದಿನ ಪ್ರಮುಖ ದರಗಳು ({{ $activeDistrict->name }})
                            </h2>
                        </div>
                        <a href="{{ route('farmer.crops.index') }}" class="text-xs font-bold text-stone-600 hover:text-stone-900 font-kannada">
                            ಎಲ್ಲಾ ನೋಡಿ &rsaquo;
                        </a>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($topMovers as $mover)
                            <a href="{{ route('farmer.crop.detail', $mover->crop_id) }}?market={{ urlencode($mover->market->name) }}" 
                               class="bg-white rounded-2xl border border-stone-200/80 overflow-hidden shadow-2xs hover:shadow-md hover:border-emerald-600 transition flex flex-col justify-between group">
                                
                                <!-- Photo with Kannada Crop Name & Direction Arrow Overlay -->
                                <div class="h-24 sm:h-28 w-full overflow-hidden relative bg-stone-100">
                                    <img src="{{ $mover->crop->photo_url }}" 
                                         alt="{{ $mover->crop->name }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>
                                    
                                    <!-- Kannada Name on bottom-left -->
                                    <span class="absolute bottom-2 left-2 text-white font-kannada font-black text-sm drop-shadow-xs">
                                        {{ $mover->crop->name_kn ?? $mover->crop->name }}
                                    </span>

                                    <!-- Trend Arrow Pill on bottom-right -->
                                    <div class="absolute bottom-2 right-2 w-6 h-6 rounded-full bg-white/30 backdrop-blur-xs flex items-center justify-center text-white text-xs font-black">
                                        @if($mover->price_spread > 0)
                                            <span class="text-emerald-300">↑</span>
                                        @elseif($mover->price_spread < 0)
                                            <span class="text-rose-300">↓</span>
                                        @else
                                            <span>&rarr;</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Card Price Details -->
                                <div class="p-3 space-y-1">
                                    <div class="text-base sm:text-lg font-black text-stone-900 tracking-tight">
                                        <span class="text-emerald-800">₹ {{ number_format($mover->modal_price, 0) }}</span>
                                        <span class="text-[10px] font-normal text-stone-400 font-sans">/ {{ strtolower($mover->unit ?? 'quintal') }}</span>
                                    </div>
                                    <div class="text-[11px] text-stone-500 truncate font-sans">
                                        {{ $mover->variety ? $mover->variety->name : 'FAQ Grade' }}
                                    </div>
                                    <div class="text-[10px] text-stone-400 truncate flex items-center gap-1 font-sans">
                                        <span class="text-rose-400">📍</span>
                                        <span>{{ $mover->market->name }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- B. Section Divider with Green Bar: "ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು" -->
            <div class="pt-2 flex items-center justify-between border-t border-stone-200/60">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-6 rounded-full bg-[#1C5A2C]"></span>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight font-kannada">
                        ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು
                    </h2>
                </div>
                <div class="text-xs text-stone-500 font-medium font-kannada">
                    ದಿನಾಂಕದ ಪ್ರಕಾರ {{ $stats['latest_date_formatted'] }}
                </div>
            </div>

            <!-- C. Category Filter Chips (Negilu Krishi Icons) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
                <a href="{{ route('home', ['district' => $activeDistrict->id, 'category' => 'all', 'search' => $search, 'scope' => $viewScope]) }}"
                   class="px-4 py-2 rounded-full font-bold whitespace-nowrap transition shadow-2xs flex items-center gap-1.5 {{ $selectedCategory === 'all' || !$selectedCategory ? 'bg-[#1C5A2C] text-white' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200/80' }}">
                    <span>🌾 ಎಲ್ಲಾ</span>
                </a>
                @foreach($categories as $cat)
                    @php
                        $icon = match(strtolower($cat->slug)) {
                            'plantation', 'commercial' => '🌴',
                            'vegetables' => '🥕',
                            'cereals-millets', 'cereals' => '🌾',
                            'oilseeds' => '🌻',
                            'fruits' => '🍌',
                            'spices' => '🌶️',
                            default => '🌿',
                        };
                    @endphp
                    <a href="{{ route('home', ['district' => $activeDistrict->id, 'category' => $cat->slug, 'search' => $search, 'scope' => $viewScope]) }}"
                       class="px-3.5 py-2 rounded-full font-bold whitespace-nowrap transition shadow-2xs flex items-center gap-1.5 {{ $selectedCategory === $cat->slug ? 'bg-[#1C5A2C] text-white' : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200/80' }}">
                        <span>{{ $icon }}</span>
                        <span>{{ $cat->name_kn ?? $cat->name }}</span>
                    </a>
                @endforeach
            </div>

            <!-- D. Search Input with Voice Icon -->
            <div class="bg-white p-2 rounded-2xl border border-stone-200/80 shadow-2xs">
                <form method="GET" action="{{ route('home') }}" class="flex items-center gap-2">
                    <input type="hidden" name="district" value="{{ $activeDistrict->id }}">
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                    <input type="hidden" name="scope" value="{{ $viewScope }}">

                    <div class="relative flex-1">
                        <input type="text" 
                               name="search" 
                               x-model="searchQuery"
                               value="{{ $search }}" 
                               placeholder="ಬೆಳೆ ಹುಡುಕಿ..."
                               class="w-full text-xs sm:text-sm pl-4 pr-10 py-2.5 bg-stone-50 hover:bg-stone-100/70 focus:bg-white border-0 rounded-xl focus:outline-none focus:ring-1 focus:ring-emerald-700 transition">
                        
                        @if($search)
                            <a href="{{ route('home', ['district' => $activeDistrict->id, 'category' => $selectedCategory]) }}" 
                               class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-600 text-sm font-bold">
                                ✕
                            </a>
                        @endif
                    </div>

                    <!-- Voice Search Icon -->
                    <button type="button" 
                            @click="startVoiceSearch()"
                            :class="isListening ? 'text-rose-500 animate-pulse' : 'text-stone-400 hover:text-stone-600'"
                            class="p-2 rounded-xl transition cursor-pointer" 
                            title="ಧ್ವನಿ ಹುಡುಕಾಟ (Kannada Voice Search)">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- E. Curated Commodities Grid (Distinct Crops without Repetitive Rows) -->
            @php
                $displayCrops = $distinctCropPrices->isNotEmpty() ? $distinctCropPrices : $latestPrices;
            @endphp

            @if($displayCrops->isEmpty())
                <div class="bg-white rounded-3xl p-8 text-center border border-stone-200/80 shadow-2xs space-y-3">
                    <div class="text-4xl">🌾</div>
                    <div class="font-extrabold text-stone-800 text-base font-kannada">ಈ ಆಯ್ಕೆಗೆ ದರಗಳು ಲಭ್ಯವಿಲ್ಲ</div>
                    <p class="text-xs text-stone-500 max-w-md mx-auto font-kannada">
                        ಪ್ರಸ್ತುತ ದಿನಾಂಕಕ್ಕೆ ಈ ವರ್ಗದಲ್ಲಿ ದರಗಳು ದಾಖಲಾಗಿಲ್ಲ. ದಯವಿಟ್ಟು ಎಲ್ಲಾ ರಾಜ್ಯದ ದರಗಳನ್ನು ವೀಕ್ಷಿಸಿ ಅಥವಾ ಬೇರೆ ವರ್ಗವನ್ನು ಆಯ್ಕೆಮಾಡಿ.
                    </p>
                    <div class="pt-2 flex justify-center gap-2">
                        <a href="{{ route('home', ['district' => $activeDistrict->id, 'scope' => 'all']) }}" 
                           class="px-4 py-2 text-xs font-bold text-white bg-[#1C5A2C] rounded-xl hover:bg-[#154622] transition shadow-xs font-kannada">
                            ಎಲ್ಲಾ ರಾಜ್ಯದ ದರಗಳು ನೋಡಿ
                        </a>
                        <a href="{{ route('home') }}" class="px-4 py-2 text-xs font-bold text-stone-600 bg-stone-100 rounded-xl hover:bg-stone-200 transition font-kannada">
                            ಫಿಲ್ಟರ್ ತೆರವುಗೊಳಿಸಿ
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($displayCrops as $price)
                        <div class="bg-white rounded-2xl border border-stone-200/80 overflow-hidden shadow-2xs hover:shadow-md hover:border-emerald-600 transition flex flex-col justify-between group">
                            
                            <!-- Card Content (Click navigates to Crop Detail) -->
                            <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($price->market->name) }}" class="block">
                                <div class="h-32 w-full overflow-hidden relative bg-stone-100">
                                    <img src="{{ $price->crop->photo_url }}" 
                                         alt="{{ $price->crop->name }}" 
                                         loading="lazy"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                                    <!-- Bottom-left: Kannada + English Crop Name -->
                                    <div class="absolute bottom-2.5 left-3">
                                        <div class="text-white text-base sm:text-lg font-black tracking-tight leading-tight font-kannada">
                                            {{ $price->crop->name_kn ?? $price->crop->name }}
                                        </div>
                                        <div class="text-[11px] text-white/80 font-medium font-sans">
                                            {{ $price->crop->name }} • {{ $price->variety ? $price->variety->name : 'Standard' }}
                                        </div>
                                    </div>

                                    <!-- Category Pill on top-right -->
                                    <span class="absolute top-2.5 right-2.5 text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/40 backdrop-blur-xs text-white border border-white/20">
                                        {{ $price->crop->category ? $price->crop->category->name : 'Commodity' }}
                                    </span>
                                </div>

                                <div class="p-3.5 space-y-2">
                                    <!-- Modal Rupee Price Box & Reliability Badge (Negilu Krishi Style) -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-xl sm:text-2xl font-black text-emerald-900 tracking-tight font-sans">
                                                ₹ {{ number_format($price->modal_price, 0) }}
                                            </span>
                                            <span class="text-xs text-stone-400 font-sans">/ {{ $price->unit ?? 'Qtl' }}</span>
                                        </div>

                                        @if(isset($price->is_local) && $price->is_local)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span>Reliable</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="ರಾಜ್ಯ ಸರಾಸರಿ / ಸಮೀಪದ ಮಂಡಿ ದರ">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                <span>Benchmark</span>
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Mandi Location Info -->
                                    <div class="text-xs text-stone-500 flex items-center gap-1.5 truncate">
                                        <span class="text-rose-500 text-xs shrink-0">📍</span>
                                        <span class="font-bold text-stone-800 truncate font-sans">
                                            @if($price->crop->isBoardPriced())
                                                {{ $price->market->name }}
                                            @else
                                                {{ str_ends_with(strtolower($price->market->name), 'apmc') ? $price->market->name : $price->market->name . ' APMC' }}
                                            @endif
                                        </span>
                                        <span class="text-stone-300">•</span>
                                        <span class="text-stone-500 truncate font-sans">{{ $price->market->district ? $price->market->district->name : 'Karnataka' }}</span>
                                    </div>
                                </div>
                            </a>

                            <!-- Card Footer: WhatsApp 1-Tap Share Button -->
                            <div class="px-3.5 py-2.5 bg-stone-50 border-t border-stone-100 flex items-center justify-between gap-2">
                                <span class="text-[11px] text-stone-400 font-sans">
                                    ದಿನಾಂಕ: {{ $price->price_date->format('d M') }}
                                </span>

                                @php
                                    $shareText = "🌾 *ಕೃಷಿ ಬಾಂಧವ (Krushi Baandhava)*\n"
                                        . "ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರ: *" . $price->crop->name . ($price->crop->name_kn ? ' (' . $price->crop->name_kn . ')' : '') . "*\n"
                                        . "📍 ಮಾರುಕಟ್ಟೆ: " . $price->market->name . " APMC\n"
                                        . "💰 ಮಾದರಿ ದರ: ₹" . number_format($price->modal_price, 0) . " / " . $price->unit . "\n"
                                        . "📅 ದಿನಾಂಕ: " . $price->price_date->format('d M Y') . "\n"
                                        . "👉 ವಿವರಗಳಿಗೆ ಭೇಟಿ ನೀಡಿ: " . url('/crop/' . $price->crop_id);
                                    $whatsappUrl = "https://wa.me/?text=" . rawurlencode($shareText);
                                @endphp

                                <a href="{{ $whatsappUrl }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 hover:text-emerald-950 transition active:scale-95 cursor-pointer">
                                    <span>💬</span>
                                    <span>ಶೇರ್ ಮಾಡಿ</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($latestPrices->hasPages())
                    <div class="mt-4 pt-2">
                        {{ $latestPrices->links() }}
                    </div>
                @endif
            @endif

        </div>

        <!-- Right Column: Weather & Karnataka IMD Weather Alert Map (4 cols) -->
        <div class="lg:col-span-4 space-y-5">

            <!-- 1. Deep Green Weather Card (Negilu Style) -->
            <div class="rounded-3xl p-5 sm:p-6 text-white shadow-md relative overflow-hidden"
                 style="background: linear-gradient(135deg, #1E4E38 0%, #286043 100%);">
                
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⛅</span>
                        <span class="font-extrabold text-sm tracking-tight font-kannada">
                            {{ $activeDistrict->name_kn ?? $activeDistrict->name }}
                        </span>
                    </div>
                    <a href="{{ route('farmer.weather.index', ['district' => $activeDistrict->id]) }}" 
                       class="text-[11px] font-bold text-emerald-200 hover:text-white font-kannada">
                        7 ದಿನಗಳು &rsaquo;
                    </a>
                </div>

                <div class="py-4 flex items-baseline justify-between">
                    <div>
                        <div class="text-4xl sm:text-5xl font-black tracking-tight">
                            {{ round($todayWeather->current_temperature ?? 22) }}°<span class="text-2xl font-light">C</span>
                        </div>
                        <div class="text-xs text-emerald-200/90 font-medium font-kannada mt-1">
                            {{ $todayWeather->weather_condition_kn ?? 'ಭಾಗಶಃ ಮೋಡ' }}
                        </div>
                    </div>

                    <div class="text-right space-y-1">
                        <div class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-white/15 backdrop-blur-xs text-white">
                            <span>🌧️</span>
                            <span>{{ round($todayWeather->precipitation_probability ?? 12) }}% ಮಳೆ</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-white/10 text-xs text-emerald-100 font-medium font-kannada leading-relaxed">
                    🌾 {{ $todayWeather->farming_advisory_kn ?? 'ಒಣಗಿಸಲು ಒಳ್ಳೆಯ ದಿನ — ಸಾಗಾಣಿಕೆಗೆ ಸೂಕ್ತ' }}
                </div>
            </div>

            <!-- 2. Karnataka IMD Weather Alert Card -->
            <div class="bg-white rounded-3xl p-5 border border-stone-200/80 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🗺️</span>
                        <h3 class="font-black text-stone-900 text-sm font-kannada">ಕರ್ನಾಟಕ ಹವಾಮಾನ ಎಚ್ಚರಿಕೆ</h3>
                    </div>
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-blue-100 text-blue-900 font-sans">
                        IMD
                    </span>
                </div>

                <div class="text-[11px] text-stone-400 font-kannada">
                    ದಿನಾಂಕ: {{ $stats['latest_date_formatted'] }}
                </div>

                <!-- Karnataka Regional Weather Alert Indicators -->
                <div class="space-y-2.5">
                    <div class="p-3 rounded-2xl bg-amber-50/80 border border-amber-200/70 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                            <div>
                                <div class="font-bold text-stone-900 text-xs font-kannada">ಕರಾವಳಿ ಮತ್ತು ಮಲೆನಾಡು</div>
                                <div class="text-[10px] text-stone-500 font-kannada">ಸಾಧಾರಣದಿಂದ ಭಾರಿ ಮಳೆ ಸಾಧ್ಯತೆ</div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-kannada">ಹಳದಿ ಎಚ್ಚರಿಕೆ</span>
                    </div>

                    <div class="p-3 rounded-2xl bg-emerald-50/80 border border-emerald-200/70 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                            <div>
                                <div class="font-bold text-stone-900 text-xs font-kannada">ದಕ್ಷಿಣ ಒಳನಾಡು</div>
                                <div class="text-[10px] text-stone-500 font-kannada">ಭಾಗಶಃ ಮೋಡ, ಒಣ ಹವೆ</div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 font-kannada">ಸಾಮಾನ್ಯ</span>
                    </div>

                    <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200/70 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-stone-400 shrink-0"></span>
                            <div>
                                <div class="font-bold text-stone-900 text-xs font-kannada">ಉತ್ತರ ಒಳನಾಡು</div>
                                <div class="text-[10px] text-stone-500 font-kannada">ಬಿಸಿಲು ಮತ್ತು ಶುಷ್ಕ ಹವಾಮಾನ</div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-stone-200 text-stone-800 font-kannada">ಸಾಮಾನ್ಯ</span>
                    </div>
                </div>

                <a href="{{ route('farmer.weather.index') }}" 
                   class="block w-full py-2.5 text-center text-xs font-bold text-stone-700 hover:text-stone-900 bg-stone-100 hover:bg-stone-200/80 rounded-xl transition font-kannada">
                    ಸಂಪೂರ್ಣ ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ ನೋಡಿ &rarr;
                </a>
            </div>

        </div>

    </div>

</div>

<script>
function farmerHome() {
    return {
        searchQuery: '{{ addslashes($search) }}',
        isListening: false,
        startVoiceSearch() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert('ಧ್ವನಿ ಹುಡುಕಾಟವು ನಿಮ್ಮ ಬ್ರೌಸರ್‌ನಲ್ಲಿ ಬೆಂಬಲಿತವಾಗಿಲ್ಲ (Voice search is not supported in this browser).');
                return;
            }
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.lang = 'kn-IN'; // Kannada primary
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            this.isListening = true;

            recognition.onresult = (event) => {
                const spoken = event.results[0][0].transcript;
                this.searchQuery = spoken;
                this.isListening = false;
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
