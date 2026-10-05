@php
    $activeLocale = $activeLocale ?? app()->getLocale();
@endphp

<!-- ==================== VIEW 1: CLEAN FULL-BLEED CARDS GRID (Negilu Krushi Clean Master) ==================== -->
<div id="cropsGrid" x-show="currentView === 'grid'" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3.5 lg:gap-4">
    @forelse($distinctCropPrices as $price)
        <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($price->market->name) }}"
           data-cat="{{ $price->crop->category->slug ?? 'other' }}" 
           data-name="{{ strtolower($price->crop->name . ' ' . ($price->crop->name_kn ?? '') . ' ' . $price->market->name . ' ' . ($price->market->name_kn ?? '') . ' ' . ($price->market->district->name ?? '') . ' ' . ($price->market->district->name_kn ?? '') . ' ' . ($price->variety->name ?? '') . ' ' . ($price->variety->name_kn ?? '')) }}"
           @if($loop->first) id="tourCropCard" @endif
           class="crop-article bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] overflow-hidden shadow-xs hover:shadow-md flex flex-col justify-between group cursor-pointer block tap-feedback active:scale-[0.98]"
           style="transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.2, 0, 0, 1), border-color 0.2s, box-shadow 0.2s;">
            
            <!-- Clean Photo (No clutter badges, crop name on bottom gradient) -->
            <div class="relative h-24 sm:h-32 overflow-hidden bg-stone-100">
                <img src="{{ $price->crop->photo_url }}" 
                     alt="{{ $price->crop->name }}" 
                     class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                
                @if($price->reliability_badge === 'Reliable')
                    <div class="absolute top-1.5 right-1.5">
                        <span class="bg-emerald-600/95 text-white text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse shrink-0"></span>
                            <span class="leading-none">{{ $activeLocale === 'en' ? 'Reliable' : 'ವಿಶ್ವಾಸಾರ್ಹ' }}</span>
                        </span>
                    </div>
                @else
                    <div class="absolute top-1.5 right-1.5">
                        <span class="bg-amber-500/90 text-stone-950 text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm inline-flex items-center gap-1" title="{{ $activeLocale === 'en' ? 'State benchmark rate' : 'ರಾಜ್ಯ ಸರಾಸರಿ / ಸಮೀಪದ ಮಂಡಿ ದರ' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-800 shrink-0"></span>
                            <span class="leading-none">{{ $activeLocale === 'en' ? 'Benchmark' : 'ಮೌಲ್ಯಾಂಕನ' }}</span>
                        </span>
                    </div>
                @endif

                <!-- Crop Name on Photo Bottom -->
                <div class="absolute bottom-1.5 left-2 right-2 text-white">
                    <h3 class="text-xs sm:text-base font-black {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} tracking-wide leading-tight drop-shadow-md break-words">
                        {{ $activeLocale === 'en' ? $price->crop->name : ($price->crop->name_kn ?? $price->crop->name) }}
                    </h3>
                </div>
            </div>

            <!-- Clean Body (Price, Variety, Market & Subtle arrow - zero congestion) -->
            <div class="p-2 sm:p-3 flex flex-col justify-between flex-1 gap-1">
                <div>
                    <!-- Price & Trend -->
                    <div class="flex items-baseline justify-between gap-1">
                        <div class="text-sm sm:text-lg font-black text-[#1C5A2C] leading-none">
                            ₹{{ number_format($price->modal_price) }}
                            <span class="text-[9px] sm:text-xs font-semibold text-stone-500">/ {{ $price->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್') }}</span>
                        </div>
                        @if(($price->daily_price_change ?? 0) > 0)
                            <span class="text-[9px] sm:text-[10px] font-extrabold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded shrink-0 flex items-center gap-0.5">
                                <span class="shrink-0 text-[9px]">↑</span>
                                <span>{{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}</span>
                            </span>
                        @elseif(($price->daily_price_change ?? 0) < 0)
                            <span class="text-[9px] sm:text-[10px] font-extrabold text-red-700 bg-red-50 px-1.5 py-0.5 rounded shrink-0 flex items-center gap-0.5">
                                <span class="shrink-0 text-[9px]">↓</span>
                                <span>{{ $activeLocale === 'en' ? 'Drop' : 'ಇಳಿಕೆ' }}</span>
                            </span>
                        @else
                            <span class="text-[9px] sm:text-[10px] font-extrabold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded shrink-0 flex items-center gap-0.5">
                                <span class="shrink-0 text-[9px]">→</span>
                                <span>{{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}</span>
                            </span>
                        @endif
                    </div>

                    <!-- Variety -->
                    <div class="text-[10px] sm:text-[11px] font-bold text-stone-600 mt-1 leading-tight break-words">
                        {{ $activeLocale === 'kn' ? ($price->variety->name_kn ?? $price->variety->name ?? 'ಸಾಮಾನ್ಯ') : ($price->variety->name ?? 'Common') }}
                    </div>
                </div>

                <!-- Market Line & Tap Arrow -->
                <div class="flex items-center justify-between text-[10px] sm:text-[11px] text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} pt-1.5 border-t border-stone-100 gap-1 mt-1">
                    <span class="flex items-center gap-1 text-stone-700 font-semibold truncate">
                        <span class="text-[9px] text-stone-400 shrink-0">📍</span>
                        <span class="truncate">
                            {{ $activeLocale === 'kn' ? ($price->market->name_kn ?? $price->market->name) : $price->market->name }}
                            @if(!empty($price->market->district))
                                · {{ $activeLocale === 'kn' ? ($price->market->district->name_kn ?? $price->market->district->name) : $price->market->district->name }}
                            @endif
                        </span>
                    </span>
                    <span class="text-[10px] sm:text-xs text-[#1C5A2C] font-extrabold shrink-0 group-hover:translate-x-0.5 transition-transform">
                        ›
                    </span>
                </div>
            </div>
        </a>
    @empty
        <div class="col-span-full text-center py-12 bg-white rounded-2xl border-2 border-dashed border-[#D9CEB8]">
            <span class="text-3xl">🔍</span>
            <h4 class="text-sm font-black text-stone-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-2">
                {{ $activeLocale === 'en' ? 'No crops found matching this criteria' : 'ಹುಡುಕಾಟಕ್ಕೆ ತಕ್ಕ ಬೆಳೆ ಸಿಗಲಿಲ್ಲ' }}
            </h4>
            <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
                {{ $activeLocale === 'en' ? 'Try searching for a different commodity or reset filters' : 'ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ ಅಥವಾ ವರ್ಗವನ್ನು ಆಯ್ಕೆಮಾಡಿ.' }}
            </p>
        </div>
    @endforelse
</div>

<!-- ==================== VIEW 2: STREAMLINED RESPONSIVE LIST VIEW ==================== -->
<div id="cropsList" x-show="currentView === 'list'" class="space-y-2.5" style="display: none;">
    @forelse($distinctCropPrices as $price)
        <div data-cat="{{ $price->crop->category->slug ?? 'other' }}"
             data-name="{{ strtolower($price->crop->name . ' ' . ($price->crop->name_kn ?? '') . ' ' . $price->market->name . ' ' . ($price->market->name_kn ?? '') . ' ' . ($price->market->district->name ?? '') . ' ' . ($price->market->district->name_kn ?? '') . ' ' . ($price->variety->name ?? '') . ' ' . ($price->variety->name_kn ?? '')) }}"
             class="crop-list-item bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] p-3 sm:p-4 flex items-center justify-between gap-3 cursor-pointer shadow-sm hover:bg-emerald-50/30 tap-feedback active:scale-[0.985]"
             style="transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.2, 0, 0, 1), border-color 0.2s, background-color 0.2s;">
            
            <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($price->market->name) }}" 
               class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl overflow-hidden bg-stone-100 shrink-0 border border-[#D9CEB8]">
                    <img src="{{ $price->crop->photo_url }}" 
                         alt="{{ $price->crop->name }}" 
                         class="w-full h-full object-cover">
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="font-extrabold text-sm sm:text-base text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} truncate">
                            {{ $activeLocale === 'en' ? $price->crop->name : ($price->crop->name_kn ?? $price->crop->name) }}
                        </h4>
                        <span class="hidden sm:inline-block bg-[#EAF4EC] text-[#1C5A2C] text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                            {{ $activeLocale === 'en' ? ($price->crop->category->name ?? 'Crop') : ($price->crop->category->name_kn ?? $price->crop->category->name ?? 'ಬೆಳೆ') }}
                        </span>
                    </div>
                    <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} truncate">
                        📍 {{ $activeLocale === 'kn' ? ($price->market->name_kn ?? $price->market->name) : $price->market->name }}
                        · {{ $activeLocale === 'kn' ? ($price->variety->name_kn ?? $price->variety->name ?? 'ಸಾಮಾನ್ಯ') : ($price->variety->name ?? 'Common') }}
                        @if(!empty($price->market->district))
                            · {{ $activeLocale === 'kn' ? ($price->market->district->name_kn ?? $price->market->district->name) : $price->market->district->name }}
                        @endif
                    </p>
                </div>
            </a>

            <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                <div class="text-right">
                    <div class="text-base sm:text-lg font-black text-[#1C5A2C]">
                        ₹{{ number_format($price->modal_price) }} 
                        <span class="text-xs font-normal text-stone-500">/ {{ $price->crop->primary_unit ?? ($activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂಟಾಲ್') }}</span>
                    </div>
                    @if(($price->daily_price_change ?? 0) > 0)
                        <span class="text-[11px] font-extrabold text-emerald-700">↑ {{ $activeLocale === 'en' ? 'Rise' : 'ಏರಿಕೆ' }}</span>
                    @elseif(($price->daily_price_change ?? 0) < 0)
                        <span class="text-[11px] font-extrabold text-red-600">↓ {{ $activeLocale === 'en' ? 'Drop' : 'ಇಳಿಕೆ' }}</span>
                    @else
                        <span class="text-[11px] font-extrabold text-blue-600">→ {{ $activeLocale === 'en' ? 'Stable' : 'ಸ್ಥಿರ' }}</span>
                    @endif
                </div>
                <a href="{{ route('farmer.crop.detail', $price->crop_id) }}?market={{ urlencode($price->market->name) }}"
                   class="w-8 h-8 rounded-full bg-[#FAF8F5] border border-[#D9CEB8] flex items-center justify-center text-stone-400 hover:text-[#1C5A2C] hover:border-[#1C5A2C] transition-all">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </a>
            </div>
        </div>
    @empty
        <!-- Empty State -->
    @endforelse
</div>

<!-- Empty Search Fallback (Client Side) with Smooth Fade In -->
<div id="clientNoResults" class="hidden text-center py-10 bg-white rounded-2xl border-2 border-dashed border-[#D9CEB8] transition-all duration-200">
    <span class="text-3xl">🔍</span>
    <h4 class="text-sm font-black text-stone-800 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-2">
        {{ $activeLocale === 'en' ? 'No crops found matching your search' : 'ಹುಡುಕಾಟಕ್ಕೆ ತಕ್ಕ ಬೆಳೆ ಸಿಗಲಿಲ್ಲ' }}
    </h4>
    <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
        {{ $activeLocale === 'en' ? 'Try searching by a different name' : 'ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ ಅಥವಾ ಮಾರುಕಟ್ಟೆ ಹೆಸರನ್ನು ಟೈಪ್ ಮಾಡಿ.' }}
    </p>
</div>

<!-- Custom Agricultural Theme Pagination -->
@include('farmer.partials.pagination', ['paginator' => $latestPrices, 'activeLocale' => $activeLocale])
