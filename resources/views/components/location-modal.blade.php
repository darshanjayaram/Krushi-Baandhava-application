@props([
    'allDistricts' => null,
    'activeDistrict' => null,
])

@php
    $activeLocale = app()->getLocale();
    $isEn = ($activeLocale === 'en');

    $districtsList = (isset($allDistricts) && $allDistricts && $allDistricts->isNotEmpty())
        ? $allDistricts
        : \Illuminate\Support\Facades\Cache::remember('karnataka_districts_list', 3600, function () {
            return \App\Models\District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'name_kn', 'latitude', 'longitude', 'code']);
        });

    if (!$districtsList instanceof \Illuminate\Support\Collection) {
        $districtsList = collect($districtsList);
    }

    $districtsPayload = $districtsList->map(function ($d) {
        return [
            'id' => is_array($d) ? $d['id'] : $d->id,
            'name' => is_array($d) ? $d['name'] : $d->name,
            'name_kn' => is_array($d) ? ($d['name_kn'] ?? $d['name']) : ($d->name_kn ?? $d->name),
            'lat' => (float) (is_array($d) ? ($d['latitude'] ?? 0) : ($d->latitude ?? 0)),
            'lon' => (float) (is_array($d) ? ($d['longitude'] ?? 0) : ($d->longitude ?? 0)),
        ];
    })->values();

    $currentActiveId = null;
    $currentActiveName = 'Shivamogga';
    $currentActiveNameKn = 'ಶಿವಮೊಗ್ಗ';

    if (isset($activeDistrict) && $activeDistrict) {
        $currentActiveId = is_array($activeDistrict) ? $activeDistrict['id'] : $activeDistrict->id;
        $currentActiveName = is_array($activeDistrict) ? $activeDistrict['name'] : $activeDistrict->name;
        $currentActiveNameKn = is_array($activeDistrict) ? ($activeDistrict['name_kn'] ?? $currentActiveName) : ($activeDistrict->name_kn ?? $currentActiveName);
    } else {
        $cookieOrSession = request()->query('district') ?? request()->cookie('selected_district_id') ?? session('selected_district_id');
        $matched = $districtsPayload->firstWhere('id', (int) $cookieOrSession) ?? $districtsPayload->first();
        if ($matched) {
            $currentActiveId = $matched['id'];
            $currentActiveName = $matched['name'];
            $currentActiveNameKn = $matched['name_kn'] ?? $matched['name'];
        }
    }

    // Popular Karnataka Trading Centers for Quick-Tap
    $popularDistricts = [
        ['name' => 'Shivamogga', 'name_kn' => 'ಶಿವಮೊಗ್ಗ', 'icon' => '🌾'],
        ['name' => 'Hassan', 'name_kn' => 'ಹಾಸನ', 'icon' => '☕'],
        ['name' => 'Kolar', 'name_kn' => 'ಕೋಲಾರ', 'icon' => '🍅'],
        ['name' => 'Davanagere', 'name_kn' => 'ದಾವಣಗೆರೆ', 'icon' => '🌽'],
        ['name' => 'Mysuru', 'name_kn' => 'ಮೈಸೂರು', 'icon' => '🥥'],
        ['name' => 'Tumakuru', 'name_kn' => 'ತುಮಕೂರು', 'icon' => '🌴'],
        ['name' => 'Belagavi', 'name_kn' => 'ಬೆಳಗಾವಿ', 'icon' => '🌾'],
    ];
@endphp

<style>
    .kb-location-card {
        background-color: #FAF8F5;
        overflow: hidden;
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
    }
    .kb-dropdown-collapsible {
        display: grid;
        grid-template-rows: 0fr;
        opacity: 0;
        visibility: hidden;
        transition: grid-template-rows 220ms cubic-bezier(0.16, 1, 0.3, 1), 
                    opacity 180ms ease,
                    visibility 220ms ease;
    }
    .kb-dropdown-collapsible.open {
        grid-template-rows: 1fr;
        opacity: 1;
        visibility: visible;
    }
    .kb-dropdown-content {
        min-height: 0;
        overflow: hidden;
    }
    @media (max-width: 639px) {
        .kb-location-card {
            width: 100% !important;
            max-width: 100% !important;
            margin-top: auto !important;
            margin-bottom: 0 !important;
            border-top-left-radius: 28px !important;
            border-top-right-radius: 28px !important;
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            min-height: 65vh !important;
            max-height: 88dvh !important;
            display: flex !important;
            flex-direction: column !important;
        }
        .kb-district-scroll-list {
            max-height: 250px !important;
        }
    }
    @media (min-width: 640px) {
        .kb-location-card {
            width: 100% !important;
            max-width: 580px !important;
            margin: auto !important;
            border-radius: 28px !important;
        }
        .kb-district-scroll-list {
            max-height: 240px !important;
        }
    }
    /* Explicit bulletproof padding & vertical alignment classes */
    .kb-modal-header {
        background-color: #1C5A2C;
        color: #ffffff;
        flex-shrink: 0;
    }
    .kb-modal-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px !important;
    }
    .kb-modal-body {
        padding: 20px 24px 16px 24px !important;
        background-color: #FAF8F5;
        flex: 1 1 0%;
        overflow-y: auto;
    }
    .kb-modal-footer {
        background-color: #FAF8F5;
        padding: 16px 24px !important;
        border-top: 1px solid #EFEAE0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        margin-top: auto;
    }
    @keyframes kb-indeterminate-anim {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(20%); }
        100% { transform: translateX(100%); }
    }
    .kb-indeterminate-bar {
        animation: kb-indeterminate-anim 1.1s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        transform-origin: 0% 50%;
    }
    @media (max-width: 639px) {
        .kb-modal-header-inner {
            padding: 6px 20px 14px 20px !important;
        }
        .kb-modal-body {
            padding: 16px 20px 14px 20px !important;
        }
        .kb-modal-footer {
            padding: 14px 20px !important;
        }
    }
</style>

<!-- Unified Outer Backdrop & Positioning Container -->
<div x-data="locationModalHandler()"
     x-show="isOpen"
     x-cloak
     @open-location-modal.window="openModal()"
     @keydown.escape.window="closeModal()"
     @click.self="closeModal()"
     class="fixed inset-0 z-50 overflow-y-auto bg-stone-950/70 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4"
     aria-labelledby="modal-title" role="dialog" aria-modal="true"
     style="display: none;">

    <!-- Modal Card (Unified container, responsive bottom-sheet on mobile & 580px dialog on desktop) -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-200 transform"
         x-transition:enter-start="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
         x-transition:enter-end="translate-y-0 sm:translate-y-0 sm:scale-100 opacity-100"
         x-transition:leave="ease-in duration-150 transform"
         x-transition:leave-start="translate-y-0 sm:scale-100 opacity-100"
         x-transition:leave-end="translate-y-full sm:translate-y-4 sm:scale-95 opacity-0"
         @click.stop
         class="kb-location-card w-full text-left shadow-2xl flex flex-col relative border-0">

        <!-- Unified Header Section (Solid Rich Forest Green #1C5A2C - Drag bar + title unified) -->
        <div class="kb-modal-header">
            <!-- Mobile Drag Indicator Bar (Flush inside header, no duplicate radius) -->
            <div class="sm:hidden pt-3 pb-1 flex justify-center cursor-grab" 
                 @click.stop="closeModal()">
                <div class="w-12 h-1 bg-white/40 rounded-full"></div>
            </div>

            <!-- Title & Close Bar (Symmetrical top & bottom 18px padding, matching 24px side grid) -->
            <div class="kb-modal-header-inner">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0 shadow-inner">
                        <svg class="w-5 h-5 sm:w-5.5 sm:h-5.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-black text-sm sm:text-base text-white leading-tight tracking-tight {{ $isEn ? 'font-sans' : 'font-kannada' }}" id="modal-title">
                            {{ $isEn ? 'Select Location' : 'ನಿಮ್ಮ ಸ್ಥಳ ಆಯ್ಕೆಮಾಡಿ' }}
                        </h3>
                        <p class="text-[10.5px] sm:text-xs text-emerald-100 font-semibold mt-0.5 {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                            {{ $isEn ? 'Live APMC market rates & weather forecast' : 'ಕರ್ನಾಟಕ ಎಪಿಎಂಸಿ ದರಗಳು & ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ' }}
                        </p>
                    </div>
                </div>

                <button type="button" 
                        @click.stop="closeModal()" 
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition active:scale-90 cursor-pointer"
                        title="{{ $isEn ? 'Close' : 'ಮುಚ್ಚಿ' }}">
                    <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body Area: GPS Hero + Dropdown Selector Box + Quick Chips (Explicit 20px top padding, 24px side grid) -->
        <div class="kb-modal-body space-y-4">

            <!-- 1. GPS Auto-Detect Button Card (Clean White, Soft Light Border) -->
            <button type="button" 
                    @click.stop="detectGPSLocation()" 
                    :disabled="isDetecting || isUpdating"
                    class="w-full p-3.5 sm:p-4 rounded-2xl bg-white border border-[#EFEAE0] hover:border-[#1C5A2C] transition active:scale-[0.99] flex items-center justify-between group cursor-pointer text-left shadow-2xs">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-50 text-[#1C5A2C] border border-emerald-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                        <svg class="w-5 h-5 sm:w-5.5 sm:h-5.5 text-[#1C5A2C]" :class="isDetecting ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 2v3m0 14v3m10-10h-3M5 12H2m15.364-7.364l-2.121 2.121M8.757 15.243l-2.121 2.121m12.728 0l-2.121-2.121M8.757 8.757L6.636 6.636"/>
                            <circle cx="12" cy="12" r="4" stroke-width="2.2"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-black text-stone-900 text-xs sm:text-[13.5px] flex items-center gap-2 {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                            <span>{{ $isEn ? 'Use Current Location (GPS)' : 'ಪ್ರಸ್ತುತ ಸ್ಥಳ ಬಳಸಿ (GPS)' }}</span>
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#1C5A2C] text-white font-sans inline-flex items-center leading-none">
                                {{ $isEn ? 'Accurate' : 'ನಿಖರ' }}
                            </span>
                        </div>
                        <div class="text-[10.5px] sm:text-xs text-stone-600 font-medium mt-0.5 {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                            <span x-show="!isDetecting">{{ $isEn ? 'Automatically locates closest Karnataka APMC mandi' : 'ಜಿಪಿಎಸ್ ಮೂಲಕ ಹತ್ತಿರದ ಮಂಡಿ ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಹೊಂದಿಸುತ್ತದೆ' }}</span>
                            <span x-show="isDetecting" class="text-[#1C5A2C] font-bold" x-text="detectingMessage"></span>
                        </div>
                    </div>
                </div>
                <div class="w-7 h-7 rounded-full bg-stone-50 group-hover:bg-emerald-50 flex items-center justify-center text-stone-400 group-hover:text-[#1C5A2C] transition shrink-0 ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </button>

            <!-- GPS Error Banner if any -->
            <div x-show="gpsError" x-cloak style="display: none;" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-semibold flex items-center gap-1.5">
                <span>⚠️</span>
                <span x-text="gpsError"></span>
            </div>

            <!-- 2. Modern Dropdown Component (Contained Inline Expansion, Symmetrical p-3.5 sm:p-4) -->
            <div>
                <label class="block text-[10.5px] sm:text-[11.5px] font-black text-stone-600 uppercase tracking-wider mb-1.5 {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                    {{ $isEn ? 'Select Karnataka District' : 'ಕರ್ನಾಟಕದ ಜಿಲ್ಲೆ ಆಯ್ಕೆಮಾಡಿ' }}
                </label>

                <!-- Dropdown Trigger Button -->
                <button type="button"
                        @click.stop="toggleDropdown()"
                        :disabled="isUpdating"
                        class="w-full p-3.5 sm:p-4 rounded-2xl bg-white border transition flex items-center justify-between text-left shadow-2xs group cursor-pointer"
                        :class="isDropdownOpen ? 'border-[#1C5A2C] ring-2 ring-[#1C5A2C]/15' : 'border-[#EFEAE0] hover:border-[#1C5A2C]'">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-50 text-[#1C5A2C] border border-emerald-200/80 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-[#1C5A2C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="truncate text-xs sm:text-[13.5px] font-bold text-stone-900 flex items-center">
                            @if($isEn)
                                <span class="font-sans font-bold" x-text="activeDistrictName"></span>
                                <span class="text-[11px] sm:text-xs text-stone-500 font-kannada ml-1.5" x-text="'(' + activeDistrictNameKn + ')'"></span>
                            @else
                                <span class="font-kannada font-bold" x-text="activeDistrictNameKn"></span>
                                <span class="text-[11px] sm:text-xs text-stone-500 font-sans ml-1.5" x-text="'(' + activeDistrictName + ')'"></span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-2">
                        <span class="text-[9.5px] sm:text-[10.5px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-[#1C5A2C] border border-emerald-200 font-mono inline-flex items-center leading-none">
                            31
                        </span>
                        <div class="w-5 h-5 flex items-center justify-center">
                            <svg class="w-4 h-4 text-stone-400 group-hover:text-[#1C5A2C] transition-transform duration-200"
                                 :class="isDropdownOpen ? 'rotate-180 text-[#1C5A2C]' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </button>

                <!-- INLINE Contained Dropdown Panel with Smooth CSS Grid Accordion Transition -->
                <div class="kb-dropdown-collapsible" :class="isDropdownOpen ? 'open' : ''">
                    <div class="kb-dropdown-content">
                        <div class="pt-2">
                            <div class="bg-white rounded-2xl border border-[#EFEAE0] shadow-sm overflow-hidden flex flex-col">
                                <!-- Search Input with Perfectly Aligned SVG Magnifier Icon (Inside input container) -->
                                <div class="p-2.5 sm:p-3 border-b border-[#EFEAE0] bg-[#FAF8F5]">
                                    <div class="relative flex items-center w-full">
                                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-3.5 flex items-center pointer-events-none text-stone-400">
                                            <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>
                                        <input type="text"
                                               x-ref="dropdownSearch"
                                               x-model="searchFilter"
                                               placeholder="{{ $isEn ? 'Search district (e.g. Hassan, Kolar)...' : 'ಜಿಲ್ಲೆ ಹೆಸರು ಹುಡುಕಿ (ಉದಾ: ಹಾಸನ, ಕೋಲಾರ)...' }}"
                                               class="w-full text-xs sm:text-sm pl-9.5 sm:pl-10.5 pr-8 py-2 sm:py-2.5 bg-white border border-[#EFEAE0] rounded-xl focus:outline-none focus:border-[#1C5A2C] font-semibold text-stone-900 shadow-2xs">
                                        <button x-show="searchFilter" @click.stop="searchFilter = ''" type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-700 text-xs font-bold cursor-pointer">
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <!-- Smooth Scrollable District List (Responsive max-height: 180px mobile / 240px desktop) -->
                                <div class="kb-district-scroll-list p-1 divide-y divide-stone-100" 
                                     style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    <template x-for="dist in filteredDistricts" :key="dist.id">
                                        <button type="button"
                                                @click.stop="selectDistrict(dist)"
                                                :disabled="isUpdating"
                                                class="w-full px-3 py-2.5 text-left rounded-xl transition flex items-center justify-between cursor-pointer group text-xs sm:text-sm hover:bg-stone-50 my-0.5"
                                                :class="activeDistrictId == dist.id ? 'bg-emerald-50 text-[#1C5A2C] font-black border-l-4 border-[#1C5A2C]' : 'text-stone-800'">
                                            <div class="flex items-center gap-1.5 truncate">
                                                @if($isEn)
                                                    <span class="font-sans font-bold text-stone-900 group-hover:text-[#1C5A2C]" 
                                                          :class="activeDistrictId == dist.id ? 'text-[#1C5A2C]' : ''"
                                                          x-text="dist.name"></span>
                                                    <span class="text-stone-500 text-[11px] sm:text-xs font-kannada" 
                                                          :class="activeDistrictId == dist.id ? 'text-emerald-800 font-semibold' : ''"
                                                          x-text="'(' + (dist.name_kn || dist.name) + ')'"></span>
                                                @else
                                                    <span class="font-kannada font-bold text-stone-900 group-hover:text-[#1C5A2C]" 
                                                          :class="activeDistrictId == dist.id ? 'text-[#1C5A2C]' : ''"
                                                          x-text="dist.name_kn || dist.name"></span>
                                                    <span class="text-stone-500 text-[11px] sm:text-xs font-sans" 
                                                          :class="activeDistrictId == dist.id ? 'text-emerald-800 font-bold' : ''"
                                                          x-text="'(' + dist.name + ')'"></span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                <!-- Row Spinner when this item is clicked -->
                                                <template x-if="isUpdating && updatingDistrictId == dist.id">
                                                    <svg class="w-4 h-4 text-[#1C5A2C] animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </template>
                                                <template x-if="!(isUpdating && updatingDistrictId == dist.id) && activeDistrictId == dist.id">
                                                    <span class="w-4 h-4 sm:w-4.5 sm:h-4.5 rounded-full bg-[#1C5A2C] text-white flex items-center justify-center text-[9px] sm:text-[10px] font-black shrink-0 shadow-xs">✓</span>
                                                </template>
                                            </div>
                                        </button>
                                    </template>

                                    <div x-show="filteredDistricts.length === 0" style="display: none;" class="py-4 text-center text-xs text-stone-500 font-semibold {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                                        {{ $isEn ? 'No districts found matching your search.' : 'ಯಾವುದೇ ಜಿಲ್ಲೆ ಕಂಡುಬಂದಿಲ್ಲ.' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Popular Trading Centers (1-Tap Quick Shortcut Pills, Always Smooth & Visible) -->
            <div class="space-y-2 pt-0.5">
                <div class="flex items-center justify-between text-[11px] sm:text-xs font-black text-stone-600 uppercase tracking-wider {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                    <span>{{ $isEn ? 'Popular Mandi Hubs' : 'ಪ್ರಮುಖ ಮಾರುಕಟ್ಟೆ ಕೇಂದ್ರಗಳು' }}</span>
                    <span class="text-[9.5px] sm:text-[10.5px] text-[#1C5A2C] font-black font-sans">{{ $isEn ? '1-Tap Select' : '1-ಟ್ಯಾಪ್ ಆಯ್ಕೆ' }}</span>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
                    @foreach($popularDistricts as $pop)
                        <button type="button"
                                @click.stop="selectDistrictByName('{{ $pop['name'] }}')"
                                :disabled="isUpdating"
                                class="px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-full text-xs sm:text-[13px] font-bold shrink-0 transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95 shadow-2xs"
                                :class="activeDistrictName == '{{ $pop['name'] }}' ? 'bg-[#1C5A2C] text-white border border-[#1C5A2C] shadow-xs' : 'bg-white hover:bg-stone-50 text-stone-800 border border-[#EFEAE0] hover:border-[#1C5A2C]'">
                            <span class="inline-flex items-center justify-center leading-none text-xs sm:text-sm">{{ $pop['icon'] }}</span>
                            <span class="leading-none {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                                {{ $isEn ? $pop['name'] : $pop['name_kn'] }}
                            </span>
                            <template x-if="isUpdating && updatingDistrictName === '{{ $pop['name'] }}'">
                                <svg class="w-3.5 h-3.5 text-current animate-spin ml-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                        </button>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Footer: Clean, Soft Reduced Border Divider -->
        <div class="kb-modal-footer text-xs sm:text-[13px] text-stone-600">
            <span class="{{ $isEn ? 'font-sans' : 'font-kannada' }} font-medium text-[11px] sm:text-xs">
                {{ $isEn ? 'Tap any district to switch rates instantly' : 'ಯಾವುದೇ ಜಿಲ್ಲೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ ತಕ್ಷಣ ದರಗಳನ್ನು ನೋಡಿ' }}
            </span>
            <button type="button" @click.stop="closeModal()" class="font-black text-[#1C5A2C] hover:underline cursor-pointer text-xs sm:text-sm {{ $isEn ? 'font-sans' : 'font-kannada' }}">
                {{ $isEn ? 'Done' : 'ಮುಗಿಯಿತು' }}
            </button>
        </div>

        <!-- Smooth Loading & Transition Overlay (Appears instantly on selection with sleek animation) -->
        <div x-show="isUpdating" 
             x-cloak
             x-transition:enter="ease-out duration-250 transition"
             x-transition:enter-start="opacity-0 scale-[0.98]"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute inset-0 bg-[#FAF8F5]/96 backdrop-blur-xs z-50 flex flex-col items-center justify-center p-6 text-center select-none"
             style="display: none;">
            
            <!-- Animated Spinner Icon with Forest Green Badge -->
            <div class="relative mb-3.5">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-3xl bg-emerald-50 border-2 border-emerald-200/80 flex items-center justify-center text-[#1C5A2C] shadow-md">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 text-[#1C5A2C] animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="absolute -top-1 -right-1 w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-[#1C5A2C] text-white flex items-center justify-center text-[10px] sm:text-xs font-black shadow-sm ring-2 ring-white">
                    ✓
                </div>
            </div>

            <!-- Dynamic District Update Text -->
            <div class="space-y-1.5 max-w-[320px]">
                <div class="text-[10.5px] sm:text-[11px] uppercase font-black tracking-widest text-[#1C5A2C]">
                    {{ $isEn ? 'Switching Market Location' : 'ಸ್ಥಳ ಬದಲಾಯಿಸಲಾಗುತ್ತಿದೆ' }}
                </div>
                <h4 class="text-base sm:text-lg font-black text-stone-900 leading-tight">
                    <span x-text="updatingDistrictName"></span>
                    <span class="text-stone-500 font-semibold text-xs sm:text-sm font-kannada ml-1" 
                          x-show="updatingDistrictNameKn && updatingDistrictNameKn !== updatingDistrictName" 
                          x-text="'(' + updatingDistrictNameKn + ')'"></span>
                </h4>
                <p class="text-xs text-stone-600 font-medium">
                    {{ $isEn ? 'Loading latest APMC market rates & weather...' : 'ತಾಜಾ ಎಪಿಎಂಸಿ ದರಗಳು & ಹವಾಮಾನವನ್ನು ನವೀಕರಿಸಲಾಗುತ್ತಿದೆ...' }}
                </p>
            </div>

            <!-- Smooth Indeterminate Progress Bar -->
            <div class="w-40 sm:w-48 h-1.5 bg-emerald-100 rounded-full overflow-hidden mt-4 relative">
                <div class="kb-indeterminate-bar h-full bg-[#1C5A2C] rounded-full w-full"></div>
            </div>
        </div>
    </div>
</div>

<script>
function locationModalHandler() {
    return {
        isOpen: false,
        isDropdownOpen: false,
        isDetecting: false,
        isUpdating: false,
        updatingDistrictId: null,
        updatingDistrictName: '',
        updatingDistrictNameKn: '',
        locale: '{{ $activeLocale }}',
        detectingMessage: '{{ $isEn ? "Detecting location..." : "ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲಾಗುತ್ತಿದೆ..." }}',
        gpsError: null,
        searchFilter: '',
        activeDistrictId: {{ $currentActiveId ?? 1 }},
        activeDistrictName: '{{ $currentActiveName }}',
        activeDistrictNameKn: '{{ $currentActiveNameKn }}',
        districts: {!! json_encode($districtsPayload) !!},

        get filteredDistricts() {
            if (!this.searchFilter.trim()) {
                return this.districts;
            }
            const q = this.searchFilter.toLowerCase().trim();
            return this.districts.filter(d => 
                (d.name && d.name.toLowerCase().includes(q)) || 
                (d.name_kn && d.name_kn.toLowerCase().includes(q))
            );
        },

        openModal() {
            this.isOpen = true;
            this.isDropdownOpen = true;
            this.isUpdating = false;
            this.updatingDistrictId = null;
            this.searchFilter = '';
            this.gpsError = null;
            // Preserves website scrollbar - no overflow-hidden on body
        },

        closeModal() {
            if (this.isUpdating) return;
            this.isOpen = false;
            this.isDropdownOpen = false;
        },

        toggleDropdown() {
            if (this.isUpdating) return;
            this.isDropdownOpen = !this.isDropdownOpen;
            if (this.isDropdownOpen) {
                this.searchFilter = '';
                this.$nextTick(() => {
                    setTimeout(() => {
                        if (this.$refs.dropdownSearch) {
                            this.$refs.dropdownSearch.focus();
                        }
                    }, 100);
                });
            }
        },

        selectDistrictByName(name) {
            if (this.isUpdating) return;
            const target = this.districts.find(d => d.name.toLowerCase() === name.toLowerCase());
            if (target) {
                this.selectDistrict(target);
            }
        },

        selectDistrict(dist) {
            if (this.isUpdating) return;
            this.isUpdating = true;
            this.updatingDistrictId = dist.id;
            this.updatingDistrictName = dist.name;
            this.updatingDistrictNameKn = dist.name_kn || dist.name;
            this.activeDistrictId = dist.id;
            this.activeDistrictName = dist.name;
            this.activeDistrictNameKn = dist.name_kn || dist.name;
            this.saveAndRedirect(dist.id, dist.name, dist.lat, dist.lon);
        },

        detectGPSLocation() {
            if (this.isUpdating) return;
            if (!navigator.geolocation) {
                this.gpsError = this.locale === 'en'
                    ? 'Your browser does not support GPS. Please select your district from the list below.'
                    : 'ನಿಮ್ಮ ಬ್ರೌಸರ್ ಜಿಪಿಎಸ್ ಬೆಂಬಲಿಸುವುದಿಲ್ಲ. ದಯವಿಟ್ಟು ಜಿಲ್ಲಾ ಪಟ್ಟಿಯಿಂದ ಆಯ್ಕೆಮಾಡಿ.';
                return;
            }

            this.isDetecting = true;
            this.detectingMessage = this.locale === 'en' ? 'Detecting location...' : 'ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲಾಗುತ್ತಿದೆ...';
            this.gpsError = null;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    this.detectingMessage = this.locale === 'en' ? 'Matching nearest Karnataka mandi...' : 'ಸಮೀಪದ ಜಿಲ್ಲೆ ಹೊಂದಿಸಲಾಗುತ್ತಿದೆ...';
                    this.findNearestDistrictAndSelect(lat, lon);
                },
                (error) => {
                    console.warn('GPS failed or timed out, trying IP geolocation fallback...', error);
                    this.fallbackToIP();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 7000,
                    maximumAge: 60000
                }
            );
        },

        fallbackToIP() {
            fetch('https://ipapi.co/json/')
                .then(res => res.json())
                .then(data => {
                    if (data && data.latitude && data.longitude) {
                        this.findNearestDistrictAndSelect(data.latitude, data.longitude);
                    } else {
                        throw new Error('IP coordinates unavailable');
                    }
                })
                .catch(() => {
                    this.isDetecting = false;
                    this.gpsError = this.locale === 'en'
                        ? 'Could not detect location. Please select your district from the list.'
                        : 'ಸ್ಥಳ ಪತ್ತೆಹಚ್ಚಲು ಸಾಧ್ಯವಾಗಲಿಲ್ಲ. ದಯವಿಟ್ಟು ಪಟ್ಟಿಯಿಂದ ನಿಮ್ಮ ಜಿಲ್ಲೆಯನ್ನು ಆರಿಸಿ.';
                });
        },

        findNearestDistrictAndSelect(userLat, userLon) {
            let nearest = null;
            let minDistanceKm = Infinity;

            for (const d of this.districts) {
                if (d.lat && d.lon) {
                    const distKm = this.getHaversineKm(userLat, userLon, d.lat, d.lon);
                    if (distKm < minDistanceKm) {
                        minDistanceKm = distKm;
                        nearest = d;
                    }
                }
            }

            if (nearest) {
                this.isUpdating = true;
                this.updatingDistrictId = nearest.id;
                this.updatingDistrictName = nearest.name;
                this.updatingDistrictNameKn = nearest.name_kn || nearest.name;
                this.saveAndRedirect(nearest.id, nearest.name, userLat, userLon);
            } else {
                const fallback = this.districts[0] || { id: 1, name: 'Shivamogga' };
                this.isUpdating = true;
                this.updatingDistrictId = fallback.id;
                this.updatingDistrictName = fallback.name;
                this.updatingDistrictNameKn = fallback.name_kn || fallback.name;
                this.saveAndRedirect(fallback.id, fallback.name);
            }
        },

        getHaversineKm(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon / 2) * Math.sin(dLon / 2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },

        saveAndRedirect(districtId, districtName, lat = null, lon = null) {
            // Persist in cookie (1 year)
            document.cookie = "selected_district_id=" + districtId + "; path=/; max-age=31536000; SameSite=Lax";
            if (window.localStorage) {
                localStorage.setItem('krushi_district_id', districtId);
                localStorage.setItem('krushi_district_name', districtName);
            }

            const payload = { district_id: districtId };
            if (lat && lon) {
                payload.latitude = lat;
                payload.longitude = lon;
            }

            // Post to backend to persist in session
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const apiPromise = fetch('{{ route('set-location') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : ''
                },
                body: JSON.stringify(payload)
            }).catch(() => {
                // Ignore failure, cookie is already saved
            });

            // Minimum transition delay (500ms) to provide a smooth, delightful visual confirmation
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));

            Promise.all([apiPromise, minDelay]).finally(() => {
                // Navigate or reload
                const url = new URL(window.location.href);
                url.searchParams.set('district', districtId);
                window.location.href = url.toString();
            });
        }
    };
}
</script>
