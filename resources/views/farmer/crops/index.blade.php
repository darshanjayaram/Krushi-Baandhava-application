@extends('layouts.farmer')

@php
    $activeLocale = app()->getLocale();
@endphp

@section('title', $activeLocale === 'en' 
    ? 'Karnataka Crops Directory & Mandi Rates — Krushi Baandhava' 
    : 'ಕರ್ನಾಟಕದ ಪ್ರಮುಖ ಕೃಷಿ ಬೆಳೆಗಳು ಮತ್ತು ಎಪಿಎಂಸಿ ದರಗಳು — ಕೃಷಿ ಬಾಂಧವ'
)

@section('content')
<div x-data="{
        searchQuery: '{{ addslashes($search) }}',
        activeCategory: '{{ $categorySlug ?? 'all' }}',
        matches(name, nameKn, catSlug, scientific) {
            const q = this.searchQuery.toLowerCase().trim();
            const matchesQuery = !q 
                || name.toLowerCase().includes(q) 
                || (nameKn && nameKn.toLowerCase().includes(q))
                || (scientific && scientific.toLowerCase().includes(q));
            const matchesCat = this.activeCategory === 'all' || catSlug === this.activeCategory;
            return matchesQuery && matchesCat;
        },
        hasAnyMatches() {
            // Evaluated reactively in Alpine
            const items = document.querySelectorAll('.crop-grid-card');
            let count = 0;
            items.forEach(el => {
                if (el.style.display !== 'none') count++;
            });
            return count > 0;
        },
        clearSearch() {
            this.searchQuery = '';
        }
    }" 
    class="space-y-4 sm:space-y-6">

    <!-- 1. Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-[#EAF4EC] text-[#1C5A2C] text-xs font-bold mb-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>🌾</span>
                <span>{{ $activeLocale === 'en' ? 'Crop Commodity Directory' : 'ಕೃಷಿ ಉತ್ಪನ್ನಗಳ ಡೈರೆಕ್ಟರಿ' }}</span>
            </div>
            <h1 class="text-xl sm:text-3xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                @if($activeLocale === 'en')
                    Karnataka Crops Directory <span class="text-xs sm:text-base font-normal text-stone-500 font-kannada">(ಕರ್ನಾಟಕದ ಬೆಳೆಗಳು)</span>
                @else
                    ಕರ್ನಾಟಕದ ಬೆಳೆಗಳು <span class="text-xs sm:text-base font-normal text-stone-500 font-sans">(Crops Directory)</span>
                @endif
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} mt-0.5">
                {{ $activeLocale === 'en' 
                    ? 'Statewide commercial, cereal, spice, and plantation crops with live mandi price intelligence.' 
                    : 'ರಾಜ್ಯದ ಪ್ರಮುಖ ವಾಣಿಜ್ಯ, ಧಾನ್ಯ, ಮಸಾಲೆ ಮತ್ತು ತೋಟಗಾರಿಕಾ ಬೆಳೆಗಳ ವಿವರ ಹಾಗೂ ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು.' }}
            </p>
        </div>

        <a href="{{ route('home') }}" 
           class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-[#D9CEB8] text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs tap-feedback active:scale-95 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>&larr;</span>
            <span>{{ $activeLocale === 'en' ? 'Back to Home' : 'ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>
    </div>

    <!-- 2. Interactive Search & Category Filter Dock -->
    <div class="bg-white p-3 sm:p-4 rounded-2xl sm:rounded-3xl border-2 border-[#D9CEB8] shadow-sm space-y-3">
        
        <!-- Live Instant Search Bar with Debounce -->
        <div class="relative flex items-center">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                <i class="fa-solid fa-magnifying-glass text-xs sm:text-sm text-[#1C5A2C]"></i>
            </div>
            
            <input type="text" 
                   x-model.debounce.150ms="searchQuery"
                   placeholder="{{ $activeLocale === 'en' ? 'Search crop name (e.g. Arecanut, Banana, Coffee, Paddy)...' : 'ಬೆಳೆ ಹೆಸರು ಹುಡುಕಿ (ಉದಾ: ಅಡಿಕೆ, ಬಾಳೆ, ಕಾಫಿ, ಭತ್ತ)...' }}"
                   class="w-full text-xs sm:text-sm pl-9 pr-24 sm:pr-28 py-2.5 sm:py-3 bg-[#FAF8F5] border border-[#DDD2BE] rounded-xl sm:rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#1C5A2C] focus:bg-white transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            
            <!-- Clear Button -->
            <button type="button" 
                    x-show="searchQuery.trim().length > 0" 
                    @click="clearSearch()"
                    x-cloak
                    class="absolute right-12 sm:right-14 text-stone-400 hover:text-stone-700 p-1 text-xs cursor-pointer">
                <i class="fa-solid fa-circle-xmark"></i>
            </button>

            <!-- Search Indicator / Badge -->
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2">
                <span class="px-2.5 py-1 bg-[#1C5A2C] text-white text-[10px] font-black rounded-lg uppercase tracking-wider">
                    LIVE
                </span>
            </div>
        </div>

        <!-- Category Horizontal Pill Bar (Instant Zero-Reload Filtering) -->
        <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
            <!-- All Crops Pill -->
            <button type="button"
                    @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'bg-[#FAF8F5] text-stone-700 hover:bg-stone-100 border border-[#DDD2BE] font-bold'"
                    class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition cursor-pointer shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>{{ $activeLocale === 'en' ? 'All Crops' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳು' }}</span>
                <span class="text-[10px] ml-1 px-1.5 py-0.2 rounded-full"
                      :class="activeCategory === 'all' ? 'bg-white/20 text-white' : 'bg-stone-200 text-stone-600'">
                    {{ $crops->total() }}
                </span>
            </button>

            <!-- Specific Categories -->
            @foreach($categories as $cat)
                <button type="button"
                        @click="activeCategory = '{{ $cat->slug }}'"
                        :class="activeCategory === '{{ $cat->slug }}' ? 'bg-[#1C5A2C] text-white shadow-xs font-black' : 'bg-[#FAF8F5] text-stone-700 hover:bg-stone-100 border border-[#DDD2BE] font-bold'"
                        class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition cursor-pointer shrink-0 flex items-center gap-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>{{ $activeLocale === 'en' ? $cat->name : ($cat->name_kn ?: $cat->name) }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                          :class="activeCategory === '{{ $cat->slug }}' ? 'bg-white/20 text-white' : 'bg-stone-200 text-stone-600'">
                        {{ $cat->crops_count }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- 3. Crops Directory Grid: 2 Columns on Mobile, 2 on Tablet, 3 on Desktop -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3.5 lg:gap-4">
        @forelse($crops as $crop)
            @php
                $priceInfo = $latestPricesByCrop[$crop->id] ?? null;
                $cropDisplayName = $activeLocale === 'en' ? $crop->name : ($crop->name_kn ?: $crop->name);
                $cropSecondaryName = $activeLocale === 'en' ? ($crop->name_kn ?: null) : ($crop->name !== $cropDisplayName ? $crop->name : null);
                $unitDisplay = $activeLocale === 'kn' 
                    ? ($crop->standard_unit === 'Quintal' ? 'ಕ್ವಿಂಟಾಲ್' : ($crop->standard_unit ?? 'ಕ್ವಿಂಟಾಲ್')) 
                    : ($crop->standard_unit ?? 'Quintal');
            @endphp
            
            <a href="{{ route('farmer.crops.show', $crop->slug) }}"
               x-show="matches('{{ addslashes($crop->name) }}', '{{ addslashes($crop->name_kn ?? '') }}', '{{ $crop->category->slug ?? '' }}', '{{ addslashes($crop->scientific_name ?? '') }}')"
               x-transition:enter="transition ease-out duration-200 transform"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               class="crop-grid-card bg-white rounded-2xl border-2 border-[#E2DAC8] hover:border-[#1C5A2C] overflow-hidden shadow-xs hover:shadow-md flex flex-col justify-between group tap-feedback active:scale-[0.98] transition-all cursor-pointer block text-left">
                
                <!-- Card Top: Full-Bleed Crop Photo with Category Badge & Name Overlay -->
                <div class="relative h-24 sm:h-32 overflow-hidden bg-stone-100">
                    <img src="{{ $crop->photo_url }}" 
                         alt="{{ $crop->name }}" 
                         class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                    
                    <!-- Dark Gradient for Text Legibility -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent"></div>

                    <!-- Top Right Category Badge -->
                    <div class="absolute top-1.5 right-1.5">
                        <span class="bg-black/60 backdrop-blur-xs text-white text-[8px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full border border-white/20">
                            {{ $crop->category ? ($activeLocale === 'en' ? $crop->category->name : ($crop->category->name_kn ?: $crop->category->name)) : ($activeLocale === 'en' ? 'Crop' : 'ಬೆಳೆ') }}
                        </span>
                    </div>

                    <!-- Crop Name on Bottom Photo -->
                    <div class="absolute bottom-1.5 left-2 right-2 text-white">
                        <h2 class="text-xs sm:text-base font-black {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} tracking-wide leading-tight drop-shadow-sm truncate">
                            {{ $cropDisplayName }}
                        </h2>
                        @if($cropSecondaryName)
                            <p class="text-[9px] sm:text-[10px] text-stone-300 font-medium truncate">
                                ({{ $cropSecondaryName }})
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Card Body (Price Range, Varieties, Unit & Action) -->
                <div class="p-2 sm:p-3 flex flex-col justify-between flex-1 gap-1.5">
                    
                    <!-- State Price Range & Mandi Count -->
                    <div>
                        <div class="text-[8px] sm:text-[10px] uppercase font-bold text-stone-400 flex items-center justify-between {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span>{{ $activeLocale === 'en' ? 'Price Range' : 'ಮಾದರಿ ದರ ಶ್ರೇಣಿ' }}</span>
                            @if($priceInfo && $priceInfo->mandi_count > 0)
                                <span class="text-[#1C5A2C] font-extrabold lowercase">
                                    {{ $priceInfo->mandi_count }} {{ $activeLocale === 'en' ? 'mandis' : 'ಮಂಡಿ' }}
                                </span>
                            @endif
                        </div>

                        <div class="text-xs sm:text-base font-black text-[#1C5A2C] leading-tight font-sans mt-0.5">
                            @if($priceInfo && $priceInfo->min_modal > 0)
                                ₹{{ number_format($priceInfo->min_modal, 0) }} - ₹{{ number_format($priceInfo->max_modal, 0) }}
                            @else
                                <span class="text-stone-400 text-[10px] sm:text-xs font-semibold">
                                    {{ $activeLocale === 'en' ? 'Live rates available' : 'ದರಗಳು ಲಭ್ಯವಿದೆ' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Varieties Preview (Truncated cleanly for 2-column mobile layout) -->
                    <div class="text-[9px] sm:text-[11px] text-stone-500 leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} truncate">
                        <span class="font-bold text-stone-700">{{ $activeLocale === 'en' ? 'Var:' : 'ತಳಿ:' }}</span>
                        @if($crop->varieties->isNotEmpty())
                            <span>{{ $crop->varieties->map(fn($v) => $v->displayName($activeLocale))->take(2)->implode(', ') }}</span>
                            @if($crop->varieties->count() > 2)
                                <span class="text-[#1C5A2C] font-bold">+{{ $crop->varieties->count() - 2 }}</span>
                            @endif
                        @else
                            <span class="text-stone-400">{{ $activeLocale === 'en' ? 'Standard' : 'ಸಾಮಾನ್ಯ' }}</span>
                        @endif
                    </div>

                    <!-- Card Footer: Localized Unit & Action Link -->
                    <div class="pt-1.5 border-t border-stone-100 flex items-center justify-between text-[9px] sm:text-[11px] gap-1">
                        <span class="text-stone-500 font-medium truncate">
                            {{ $activeLocale === 'en' ? 'Unit:' : 'ಮಾನಕ:' }} <strong>{{ $unitDisplay }}</strong>
                        </span>
                        
                        <span class="text-[#1C5A2C] font-extrabold shrink-0 group-hover:translate-x-0.5 transition-transform flex items-center gap-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span>{{ $activeLocale === 'en' ? 'View' : 'ದರ ನೋಡಿ' }}</span>
                            <span>&rarr;</span>
                        </span>
                    </div>

                </div>
            </a>
        @empty
            <div class="col-span-full bg-white rounded-3xl p-8 text-center border-2 border-dashed border-[#D9CEB8] space-y-2">
                <div class="text-3xl">🌾</div>
                <div class="font-extrabold text-stone-800 text-base {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'No crops found' : 'ಯಾವುದೇ ಬೆಳೆಗಳು ಕಂಡುಬಂದಿಲ್ಲ' }}
                </div>
                <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Please modify your search term or clear the filter.' : 'ದಯವಿಟ್ಟು ಹುಡುಕಾಟ ಪದವನ್ನು ಬದಲಾಯಿಸಿ ಅಥವಾ ಫಿಲ್ಟರ್ ತೆರವುಗೊಳಿಸಿ.' }}
                </p>
                <div class="pt-2">
                    <a href="{{ route('farmer.crops.index') }}" 
                       class="px-4 py-2 text-xs font-bold text-[#1C5A2C] bg-[#EAF4EC] rounded-xl hover:bg-[#d8edd9] transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Show All Crops' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳನ್ನು ತೋರಿಸಿ' }}
                    </a>
                </div>
            </div>
        @endforelse

        <!-- Fallback Client-side Search No-Results Box -->
        <div x-show="searchQuery.trim().length > 0 && !hasAnyMatches()" 
             x-cloak 
             class="col-span-full bg-white rounded-3xl p-8 text-center border-2 border-dashed border-[#D9CEB8] space-y-2">
            <div class="text-3xl">🔍</div>
            <div class="font-extrabold text-stone-800 text-base {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'No crops match your search' : 'ಹುಡುಕಾಟಕ್ಕೆ ತಕ್ಕ ಬೆಳೆ ಸಿಗಲಿಲ್ಲ' }}
            </div>
            <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' 
                    ? 'Try searching by a different name (e.g. Arecanut, Banana, Coffee, Paddy).' 
                    : 'ದಯವಿಟ್ಟು ಬೇರೆ ಬೆಳೆ ಹೆಸರನ್ನು ಟೈಪ್ ಮಾಡಿ (ಉದಾ: ಅಡಿಕೆ, ಬಾಳೆ, ಕಾಫಿ, ಭತ್ತ).' }}
            </p>
            <div class="pt-2">
                <button type="button" 
                        @click="clearSearch(); activeCategory = 'all'" 
                        class="px-4 py-2 text-xs font-bold text-[#1C5A2C] bg-[#EAF4EC] rounded-xl hover:bg-[#d8edd9] transition cursor-pointer {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Reset Search & Filters' : 'ಹುಡುಕಾಟ ತೆರವುಗೊಳಿಸಿ' }}
                </button>
            </div>
        </div>
    </div>

    <!-- 4. Pagination Links (if paginated) -->
    @if($crops->hasPages())
        <div class="mt-4 pt-3 border-t border-[#E5DECE]">
            {{ $crops->links() }}
        </div>
    @endif

</div>
@endsection
