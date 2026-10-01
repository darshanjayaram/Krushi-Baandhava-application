@extends('layouts.farmer')

@section('title', app()->getLocale() === 'en' ? 'Government Schemes & Subsidies — Krushi Baandhava' : 'ಕರ್ನಾಟಕ ಸರ್ಕಾರಿ ಕೃಷಿ ಯೋಜನೆಗಳು & ಸಬ್ಸಿಡಿ — ಕೃಷಿ ಬಾಂಧವ')

@section('content')
@php
    $activeLocale = app()->getLocale();
    $hasActiveFilters = !empty($search) || !empty($category);

    // Helper closure for punchy badge metrics in dark boxed highlights
    $getPunchyStat = function ($scheme, $locale) {
        if (!empty($scheme->banner_tag)) {
            return $scheme->banner_tag;
        }
        $slug = $scheme->slug ?? '';
        return match($slug) {
            'pm-kisan-samman-nidhi' => $locale === 'en' ? '₹6,000 / yr' : '₹6,000 / ವರ್ಷ',
            'ganga-kalyana-scheme' => $locale === 'en' ? '100% Free (₹4.75L)' : '100% ಉಚಿತ (₹4.75 ಲಕ್ಷ)',
            'pm-kusum-solar-pumpset' => $locale === 'en' ? '80% - 90% Subsidy' : 'ಶೇ. 80 - 90 ಸಬ್ಸಿಡಿ',
            'farm-mechanization-subsidy' => $locale === 'en' ? 'Up to 90% (₹2L)' : 'ಶೇ. 90 (ಗರಿಷ್ಠ ₹2 ಲಕ್ಷ)',
            'pmksy-micro-irrigation' => $locale === 'en' ? 'Up to 90% Subsidy' : 'ಶೇ. 75 - 90 ಸಬ್ಸಿಡಿ',
            'krishi-bhagya-scheme' => $locale === 'en' ? '80% - 90% Grant' : 'ಶೇ. 80 - 90 ಅನುದಾನ',
            'pashu-bhagya-scheme' => $locale === 'en' ? 'Up to 50% Subsidy' : 'ಶೇ. 33 - 50 ಸಬ್ಸಿಡಿ',
            'krishi-yantra-dhare' => $locale === 'en' ? '50% Rent Discount' : 'ಶೇ. 50 ರಿಯಾಯಿತಿ ಬಾಡಿಗೆ',
            'pm-fasal-bima-yojana' => $locale === 'en' ? '1.5% - 2% Premium' : 'ಕೇವಲ 1.5% - 2% ಪ್ರೀಮಿಯಂ',
            'soil-health-card-scheme' => $locale === 'en' ? '100% Free Testing' : '100% ಉಚಿತ ಮಣ್ಣು ಪರೀಕ್ಷೆ',
            'fruits-portal-registration-farmer-id' => $locale === 'en' ? 'Free FID Registration' : 'ಉಚಿತ FID ನೋಂದಣಿ',
            default => ($locale === 'en' ? ($scheme->benefit_amount ?: 'Government Grant') : ($scheme->benefit_amount_kn ?: 'ಸರ್ಕಾರಿ ಸಹಾಯಧನ')),
        };
    };
@endphp

<div class="space-y-6 pb-14 max-w-7xl mx-auto">

    <!-- ============================================================== -->
    <!-- 1. PAGE HEADER BAND (Negilu Inspired Neat & Clean Typography)  -->
    <!-- ============================================================== -->
    <div class="pt-2 sm:pt-4 pb-1 space-y-2.5">
        <!-- Live Counter Eyebrow Capsule -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-[#DDD2BE] text-xs font-black text-stone-700 shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
            <span>
                {{ $activeLocale === 'en' 
                    ? $schemes->total() . ' schemes available' 
                    : $schemes->total() . ' ಸಕ್ರಿಯ ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು ಲಭ್ಯ' }}
            </span>
        </div>

        <!-- Display Heading -->
        <div class="space-y-1">
            <h1 class="text-2xl sm:text-4xl font-black text-stone-900 tracking-tight leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Government schemes' : 'ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು' }}
                <span class="text-base sm:text-xl font-bold text-stone-500 font-sans ml-1 sm:ml-2">
                    {{ $activeLocale === 'en' ? '· Karnataka & Central' : '· ಸಬ್ಸಿಡಿ & ಸಹಾಯಧನ' }}
                </span>
            </h1>
            <p class="text-xs sm:text-base text-stone-600 font-medium max-w-2xl leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' 
                    ? 'Subsidies, insurance and farm machinery assistance in plain language, with the benefit amount up front and a direct way to apply.' 
                    : 'ಸಹಾಯಧನ, ಬೆಳೆ ವಿಮೆ ಮತ್ತು ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ ಸೌಲಭ್ಯ — ಸರಳ ಕನ್ನಡದಲ್ಲಿ, ನಿಖರ ಸಬ್ಸಿಡಿ ಮೊತ್ತ ಹಾಗೂ ಅಧಿಕೃತ ಅರ್ಜಿ ಲಿಂಕ್‌ಗಳೊಂದಿಗೆ.' }}
            </p>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. STREAMLINED PILL TABS & SEARCH (Negilu Style Capsules)      -->
    <!-- ============================================================== -->
    <div class="space-y-3">
        <!-- Horizontal Scrollable Pill Chips Bar -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1.5 pt-0.5 no-scrollbar -mx-1 px-1">
            <!-- All / For you Chip -->
            <a href="{{ route('farmer.schemes.index', array_filter(['search' => $search])) }}" 
               class="px-4 py-2 rounded-full font-extrabold whitespace-nowrap transition inline-flex items-center gap-1.5 text-xs sm:text-sm shrink-0 shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ empty($category) ? 'bg-[#1C5A2C] text-white shadow-xs' : 'bg-white text-stone-700 hover:bg-[#FAF8F5] border border-[#DDD2BE]' }}">
                <span>✨</span>
                <span>{{ $activeLocale === 'en' ? 'All Schemes' : 'ನಿಮಗಾಗಿ (ಎಲ್ಲಾ)' }}</span>
            </a>

            @foreach($categories as $catKey => $catData)
                @php
                    $isCatActive = $category === $catKey;
                    $catLabel = $activeLocale === 'en' ? ($catData['name_en'] ?? ucfirst($catKey)) : $catData['name_kn'];
                @endphp
                <a href="{{ route('farmer.schemes.index', array_filter(['category' => $catKey, 'search' => $search])) }}" 
                   class="px-4 py-2 rounded-full font-extrabold whitespace-nowrap transition inline-flex items-center gap-1.5 text-xs sm:text-sm shrink-0 shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ $isCatActive ? 'bg-[#1C5A2C] text-white shadow-xs' : 'bg-white text-stone-700 hover:bg-[#FAF8F5] border border-[#DDD2BE]' }}">
                    <span class="text-sm leading-none">{{ $catData['icon'] }}</span>
                    <span>{{ $catLabel }}</span>
                </a>
            @endforeach
        </div>

        <!-- Integrated Search Bar -->
        <form method="GET" action="{{ route('farmer.schemes.index') }}" class="flex items-center gap-2">
            @if($category)
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="{{ $activeLocale === 'en' ? 'Search scheme by name (e.g. Tractor, Drip, Solar, Borewell, PM-KISAN)...' : 'ಯೋಜನೆ ಹುಡುಕಿ (ಉದಾ: ಟ್ರಾಕ್ಟರ್, ಹನಿ ನೀರಾವರಿ, ಬೋರ್‌ವೆಲ್, ಸೋಲಾರ್, ಕಿಸಾನ್)...' }}"
                       class="w-full text-xs sm:text-sm pl-9 pr-10 py-2.5 bg-white border border-[#DDD2BE] rounded-full focus:outline-none focus:ring-2 focus:ring-[#1C5A2C] focus:border-[#1C5A2C] transition shadow-2xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                @if($search)
                    <a href="{{ route('farmer.schemes.index', array_filter(['category' => $category])) }}" 
                       class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-stone-400 hover:text-stone-700 text-sm font-bold"
                       title="Clear search">✕</a>
                @endif
            </div>
            <button type="submit" 
                    class="px-5 py-2.5 rounded-full bg-[#1C5A2C] hover:bg-[#144223] text-white font-black text-xs sm:text-sm shadow-xs hover:shadow-sm transition shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Search' : 'ಹುಡುಕಿ' }}
            </button>
        </form>

        <!-- Active Filter Indicator Bar -->
        @if($hasActiveFilters)
            <div class="flex items-center justify-between text-xs flex-wrap gap-2 px-1">
                <div class="flex items-center gap-1.5 text-stone-500 text-[11px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="font-bold">{{ $activeLocale === 'en' ? 'Filtered By:' : 'ಫಿಲ್ಟರ್:' }}</span>
                    @if($search)
                        <span class="px-2 py-0.5 rounded-full bg-stone-100 text-stone-800 font-semibold border border-stone-200">"{{ $search }}"</span>
                    @endif
                    @if($category && isset($categories[$category]))
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold border border-emerald-200">
                            {{ $categories[$category]['icon'] }} {{ $activeLocale === 'en' ? $categories[$category]['name_en'] : $categories[$category]['name_kn'] }}
                        </span>
                    @endif
                </div>

                <a href="{{ route('farmer.schemes.index') }}" 
                   class="font-black text-rose-600 hover:text-rose-700 text-[11px] transition inline-flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>✕ {{ $activeLocale === 'en' ? 'Clear all' : 'ಎಲ್ಲಾ ತೆರವುಗೊಳಿಸಿ' }}</span>
                </a>
            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- 3. MULTI-BANNER SLIDER CAROUSEL (Continuous Track, 0 Flickering)-->
    <!-- ============================================================== -->
    @if(isset($bannerSchemes) && $bannerSchemes->isNotEmpty())
        <div x-data="{
                currentSlide: 0,
                total: {{ $bannerSchemes->count() }},
                autoplayDuration: {{ ($sliderAutoplay ?? 5) * 1000 }},
                timer: null,
                isPaused: false,
                touchStartX: 0,
                touchEndX: 0,
                next() {
                    this.currentSlide = (this.currentSlide + 1) % this.total;
                },
                prev() {
                    this.currentSlide = (this.currentSlide - 1 + this.total) % this.total;
                },
                goTo(index) {
                    this.currentSlide = index;
                    this.resetAutoplay();
                },
                startAutoplay() {
                    if (this.autoplayDuration > 0 && this.total > 1) {
                        this.timer = setInterval(() => {
                            if (!this.isPaused) this.next();
                        }, this.autoplayDuration);
                    }
                },
                stopAutoplay() {
                    if (this.timer) clearInterval(this.timer);
                },
                resetAutoplay() {
                    this.stopAutoplay();
                    this.startAutoplay();
                },
                handleTouchStart(e) {
                    this.touchStartX = e.touches[0].clientX;
                },
                handleTouchEnd(e) {
                    this.touchEndX = e.changedTouches[0].clientX;
                    const diff = this.touchStartX - this.touchEndX;
                    if (Math.abs(diff) > 40) {
                        if (diff > 0) {
                            this.next();
                        } else {
                            this.prev();
                        }
                        this.resetAutoplay();
                    }
                }
            }"
            x-init="startAutoplay()"
            @mouseenter="isPaused = true"
            @mouseleave="isPaused = false"
            @touchstart.passive="handleTouchStart($event)"
            @touchend.passive="handleTouchEnd($event)"
            class="relative select-none group/slider">

            <!-- Carousel Viewport Window (Atmospheric Radial Gradient - Weather Card Theme) -->
            <div class="relative overflow-hidden rounded-3xl shadow-[0_16px_40px_-10px_rgba(11,43,23,0.50)] border border-emerald-500/30"
                 style="contain: paint; background: radial-gradient(circle at 85% 15%, #257044 0%, #154D2B 45%, #0B2B17 100%);">
                <!-- Continuous Flex Track with GPU Hardware Acceleration -->
                <div class="flex w-full transition-transform duration-500 ease-[cubic-bezier(0.2,0.8,0.2,1)] will-change-transform"
                     :style="'transform: translate3d(-' + (currentSlide * 100) + '%, 0px, 0px);'">

                    @foreach($bannerSchemes as $idx => $bScheme)
                        @php
                            $bTitle = $activeLocale === 'en' ? ($bScheme->title ?: $bScheme->title_kn) : ($bScheme->title_kn ?: $bScheme->title);
                            $bSub = $activeLocale === 'en' ? $bScheme->title_kn : $bScheme->title;
                            $bPunchy = $getPunchyStat($bScheme, $activeLocale);
                            $bDesc = $activeLocale === 'en' 
                                ? ($bScheme->benefit_amount ?: $bScheme->eligibility_criteria) 
                                : ($bScheme->benefit_amount_kn ?: $bScheme->eligibility_criteria_kn);
                            $bDirectUrl = $bScheme->apply_url ?: $bScheme->official_url ?: '#';
                        @endphp
                        <div class="w-full shrink-0 flex-none relative overflow-hidden text-white p-5 sm:p-8 flex flex-col justify-between min-h-[220px]"
                             style="background: radial-gradient(circle at 85% 20%, #257044 0%, #154D2B 45%, #0B2B17 100%);">

                            <!-- Unique Krushi Baandhava Agricultural Field Elevation Contours (Distinctive Topographic Geometry) -->
                            <svg class="absolute inset-0 w-full h-full pointer-events-none select-none z-0" preserveAspectRatio="none" viewBox="0 0 800 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M-20 180 Q 240 70, 500 170 T 820 90" stroke="currentColor" stroke-width="1.8" stroke-opacity="0.18" class="text-emerald-200" />
                                <path d="M-20 215 Q 260 110, 520 205 T 820 135" stroke="currentColor" stroke-width="1.4" stroke-opacity="0.14" class="text-white" stroke-dasharray="6 4" />
                                <path d="M-20 245 Q 280 150, 540 235 T 820 175" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.10" class="text-emerald-300" />
                                <ellipse cx="680" cy="45" rx="150" ry="100" fill="url(#atmGlow-{{ $idx }})" opacity="0.30" />
                                <defs>
                                    <radialGradient id="atmGlow-{{ $idx }}" cx="50%" cy="50%" r="50%">
                                        <stop offset="0%" stop-color="#4ade80" stop-opacity="0.5"/>
                                        <stop offset="100%" stop-color="#154D2B" stop-opacity="0"/>
                                    </radialGradient>
                                </defs>
                            </svg>

                            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                                <!-- Left Content -->
                                <div class="space-y-3 max-w-2xl">
                                    <!-- Badges Row -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#1C5A2C] text-xs font-black shadow-xs">
                                             <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                             <span>{{ $activeLocale === 'en' ? 'Open now' : 'ಈಗ ತೆರೆಯಲಾಗಿದೆ' }}</span>
                                        </span>
                                        <span class="px-3 py-1 rounded-full bg-white/15 text-emerald-200 text-xs font-bold backdrop-blur-xs border border-white/10">
                                            {{ $activeLocale === 'en' ? ($bScheme->category_label_en ?? ucfirst($bScheme->category)) : $bScheme->category_label_kn }}
                                        </span>
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-stone-200 text-xs font-medium backdrop-blur-xs">
                                            {{ $activeLocale === 'en' ? 'Karnataka Farmers' : 'ಕರ್ನಾಟಕ ರೈತರು' }}
                                        </span>
                                        @if($bannerSchemes->count() > 1)
                                            <span class="text-[11px] text-amber-300 font-bold ml-1 font-mono">
                                                [{{ $idx + 1 }}/{{ $bannerSchemes->count() }}]
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Title -->
                                    <div class="space-y-1">
                                        <h2 class="text-xl sm:text-3xl font-black text-white leading-tight tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                            {{ $bTitle }}
                                        </h2>
                                        @if($bSub && $bSub !== $bTitle)
                                            <p class="text-xs sm:text-sm font-semibold text-emerald-200/90 leading-snug {{ $activeLocale === 'kn' ? 'font-sans' : 'font-kannada' }}">
                                                {{ $bSub }}
                                            </p>
                                        @endif
                                    </div>

                                    <!-- Description -->
                                    <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed max-w-xl font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                        {{ $bDesc }}
                                    </p>
                                </div>

                                <!-- Right Big Stat & Action Button -->
                                <div class="lg:text-right shrink-0 flex flex-col lg:items-end justify-center pt-2 lg:pt-0 border-t lg:border-t-0 border-white/15">
                                    <div class="space-y-0.5">
                                        <div class="text-3xl sm:text-5xl font-black text-amber-300 font-sans tracking-tight">
                                            {{ $bPunchy }}
                                        </div>
                                        <div class="text-[11px] font-extrabold text-emerald-200 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                            {{ $activeLocale === 'en' ? 'BENEFIT / GRANT' : 'ಮುಖ್ಯ ಸೌಲಭ್ಯ / ಲಾಭ' }}
                                        </div>
                                    </div>

                                    <a href="{{ $bDirectUrl }}" target="_blank" rel="noopener noreferrer"
                                       class="mt-4 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-white text-[#1C5A2C] hover:bg-amber-300 hover:text-stone-950 font-black text-xs sm:text-sm shadow-md transition transform hover:-translate-y-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                        <span>{{ $activeLocale === 'en' ? 'Apply on Official Portal' : 'ಅಧಿಕೃತ ಪೋರ್ಟಲ್‌ನಲ್ಲಿ ಅರ್ಜಿ ಸಲ್ಲಿಸಿ' }}</span>
                                        <svg class="w-4 h-4 ml-0.5 text-stone-950" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Carousel Navigation Arrows & Dots (Shown when > 1 slide) -->
            @if($bannerSchemes->count() > 1)
                <!-- Previous Button -->
                <button @click="prev(); resetAutoplay()" 
                        type="button"
                        class="absolute left-2.5 sm:left-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/20 hover:bg-white/35 text-white backdrop-blur-md flex items-center justify-center transition border border-white/30 shadow-md z-20 cursor-pointer text-lg font-bold active:scale-95"
                        aria-label="Previous Slide">
                    ‹
                </button>
                <!-- Next Button -->
                <button @click="next(); resetAutoplay()" 
                        type="button"
                        class="absolute right-2.5 sm:right-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/20 hover:bg-white/35 text-white backdrop-blur-md flex items-center justify-center transition border border-white/30 shadow-md z-20 cursor-pointer text-lg font-bold active:scale-95"
                        aria-label="Next Slide">
                    ›
                </button>

                <!-- Indicator Dots -->
                <div class="flex items-center justify-center gap-2 mt-3.5">
                    @foreach($bannerSchemes as $idx => $bScheme)
                        <button @click="goTo({{ $idx }})" 
                                type="button"
                                class="h-2 rounded-full transition-all duration-300 cursor-pointer"
                                :class="currentSlide === {{ $idx }} ? 'w-8 bg-[#1C5A2C]' : 'w-2 bg-stone-300 hover:bg-stone-400'"
                                aria-label="Go to banner {{ $idx + 1 }}">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- 4. SCHEMES 3-COLUMN CARDS GRID (with Dark Boxed Accents)       -->
    <!-- ============================================================== -->
    @if($schemes->isEmpty())
        <div class="text-center py-16 bg-white rounded-3xl border border-stone-200 p-6 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center text-3xl mx-auto shadow-2xs">
                🔍
            </div>
            <h3 class="text-lg font-black text-stone-900 mt-4">
                {{ $activeLocale === 'en' ? 'No government schemes found' : 'ಯಾವುದೇ ಯೋಜನೆಗಳು ಕಂಡುಬಂದಿಲ್ಲ' }}
            </h3>
            <p class="text-xs sm:text-sm text-stone-500 mt-1 max-w-md mx-auto">
                {{ $activeLocale === 'en' ? 'Try searching with different terms like "Tractor", "Drip", "Borewell", or clear your filters.' : 'ದಯವಿಟ್ಟು ಬೇರೆ ಪದಗಳಿಂದ ಹುಡುಕಿ (ಉದಾ: ಟ್ರಾಕ್ಟರ್, ಸೋಲಾರ್, ಬೋರ್‌ವೆಲ್) ಅಥವಾ ಫಿಲ್ಟರ್ ತೆರವುಗೊಳಿಸಿ.' }}
            </p>
            <a href="{{ route('farmer.schemes.index') }}" 
               class="inline-flex items-center gap-1.5 mt-5 px-5 py-2.5 rounded-full bg-[#1C5A2C] hover:bg-[#144223] text-white font-black text-xs shadow-xs hover:shadow-sm transition">
                <span>{{ $activeLocale === 'en' ? 'View all available schemes' : 'ಎಲ್ಲಾ ಯೋಜನೆಗಳನ್ನು ನೋಡಿ' }}</span>
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($schemes as $scheme)
                @php
                    $sTitle = $activeLocale === 'en' ? ($scheme->title ?: $scheme->title_kn) : ($scheme->title_kn ?: $scheme->title);
                    $sSub = $activeLocale === 'en' ? $scheme->title_kn : $scheme->title;
                    $punchyBenefit = $getPunchyStat($scheme, $activeLocale);
                    $eligSnippet = $activeLocale === 'en' ? ($scheme->eligibility_criteria ?: $scheme->eligibility_criteria_kn) : ($scheme->eligibility_criteria_kn ?: $scheme->eligibility_criteria);
                    $sumSnippet = $activeLocale === 'en' ? ($scheme->benefit_amount ?: $scheme->eligibility_criteria) : ($scheme->benefit_amount_kn ?: $scheme->eligibility_criteria_kn);
                    $sEmoji = $scheme->icon_emoji ?: match($scheme->category) {
                        'subsidy' => '💰',
                        'machinery' => '🚜',
                        'irrigation' => '💧',
                        'insurance' => '🛡️',
                        'organic' => '🌱',
                        default => '📋',
                    };
                    $sTintClass = match($scheme->category) {
                        'insurance' => 'bg-[#FAEFD6] text-[#E0A23A]',
                        'subsidy' => 'bg-[#E6F1E4] text-[#1C5A2C]',
                        'machinery' => 'bg-[#E8F3EE] text-[#144223]',
                        'irrigation' => 'bg-[#E3F2FD] text-[#0288D1]',
                        default => 'bg-[#FAF6EC] text-[#52604F]',
                    };
                    $targetDirectUrl = $scheme->apply_url ?: $scheme->official_url ?: '#';
                    $hostName = parse_url($targetDirectUrl, PHP_URL_HOST) ?? '';
                @endphp
                <article class="bg-white rounded-[26px] border border-[#E8E1D0] hover:border-[#1C5A2C] hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col p-5 group relative">
                    
                    <!-- Top Row: Squircle Icon & Title -->
                    <div class="flex items-start gap-3.5 mb-3">
                        <div class="w-12 h-12 rounded-2xl {{ $sTintClass }} flex items-center justify-center text-2xl shrink-0 shadow-2xs">
                            {{ $sEmoji }}
                        </div>
                        <div class="space-y-0.5 flex-1 min-w-0">
                            <h3 class="text-base font-black text-stone-900 group-hover:text-[#1C5A2C] transition line-clamp-2 leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                <a href="{{ $targetDirectUrl }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                    {{ $sTitle }}
                                </a>
                            </h3>
                            @if($scheme->sponsoring_agency)
                                <div class="text-[10px] font-bold text-stone-500 truncate" title="{{ $scheme->sponsoring_agency }}">
                                    🏛️ {{ $scheme->sponsoring_agency }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Tags Row (Negilu Style Pills) -->
                    <div class="flex flex-wrap items-center gap-1.5 mb-3 text-[11px] font-extrabold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            <span>{{ $activeLocale === 'en' ? 'Open' : 'ಸಕ್ರಿಯ' }}</span>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#FAF6EC] text-stone-700 border border-[#E8E1D0]">
                            {{ $activeLocale === 'en' ? ($scheme->category_label_en ?? ucfirst($scheme->category)) : $scheme->category_label_kn }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#FAF6EC] text-stone-600 border border-[#E8E1D0]">
                            {{ $activeLocale === 'en' ? 'All farmers' : 'ಕರ್ನಾಟಕ ರೈತರು' }}
                        </span>
                    </div>

                    <!-- Brief Description -->
                    <p class="text-xs text-stone-600 leading-relaxed line-clamp-2 mb-3 font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $sumSnippet }}
                    </p>

                    <!-- Dashed Divider -->
                    <div class="border-t border-dashed border-[#E8E1D0] my-2"></div>

                    <!-- Eligibility Section -->
                    <div class="mb-3.5 space-y-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <div class="text-[10px] font-black tracking-widest text-stone-400 uppercase font-sans">
                            {{ $activeLocale === 'en' ? 'ELIGIBILITY' : 'ಅರ್ಹತೆ · ELIGIBILITY' }}
                        </div>
                        <div class="text-xs text-stone-800 leading-snug line-clamp-2 font-medium">
                            {{ $eligSnippet }}
                        </div>
                    </div>

                    <!-- ⭐ CLASSIC BENEFIT ENTITLEMENT TILE (Harmonious Brand Emerald Box) -->
                    <div class="mt-auto mb-3.5 p-3 rounded-2xl bg-[#F2F8F3] border border-[#C2DFCA] shadow-2xs">
                        <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider text-[#1C5A2C] mb-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span class="inline-flex items-center gap-1">
                                <span>⭐</span>
                                <span>{{ $activeLocale === 'en' ? 'YOU GET' : 'ನೀವು ಪಡೆಯುವ ಲಾಭ' }}</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full bg-[#E1EFE2] text-[#1C5A2C] font-mono text-[9px] font-black border border-[#BFDDBF]">
                                DBT / SUBSIDY
                            </span>
                        </div>
                        <div class="text-base sm:text-lg font-black text-[#144223] tracking-tight leading-tight flex items-baseline gap-1 font-sans">
                            <span>{{ $punchyBenefit }}</span>
                        </div>
                    </div>

                    <!-- Full-Width Direct Apply Pill Button (Brand Primary Green) -->
                    <div class="space-y-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <a href="{{ $targetDirectUrl }}" target="_blank" rel="noopener noreferrer" 
                           class="w-full py-2.5 px-4 rounded-full bg-[#1C5A2C] hover:bg-[#144223] text-white font-extrabold text-xs sm:text-sm text-center transition flex items-center justify-center gap-1.5 shadow-xs hover:shadow-md group/btn">
                            <span>{{ $activeLocale === 'en' ? 'Apply on Official Portal' : 'ಈಗಲೇ ಅರ್ಜಿ ಸಲ್ಲಿಸಿ' }}</span>
                            <svg class="w-3.5 h-3.5 text-amber-300 shrink-0 group-hover/btn:translate-x-0.5 group-hover/btn:-translate-y-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>

                        @if($hostName)
                            <div class="flex items-center justify-center gap-1 text-[10px] sm:text-[11px] font-bold text-stone-500 pt-0.5">
                                <span>🏛️</span>
                                <span class="font-mono text-stone-600 truncate max-w-[170px]">{{ $hostName }}</span>
                                <span class="text-emerald-700 font-extrabold ml-0.5">✓ {{ $activeLocale === 'en' ? 'Verified' : 'ಅಧಿಕೃತ' }}</span>
                            </div>
                        @endif
                    </div>

                </article>
            @endforeach
        </div>

        <!-- Pagination Controls with Counter Summary -->
        @if($schemes->hasPages())
            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-stone-200">
                <div class="text-xs font-bold text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' 
                        ? 'Showing ' . $schemes->firstItem() . ' to ' . $schemes->lastItem() . ' of ' . $schemes->total() . ' schemes'
                        : 'ಒಟ್ಟು ' . $schemes->total() . ' ಯೋಜನೆಗಳಲ್ಲಿ ' . $schemes->firstItem() . ' ರಿಂದ ' . $schemes->lastItem() . ' ರವರೆಗೆ ತೋರಿಸಲಾಗುತ್ತಿದೆ' }}
                </div>
                <div>
                    {{ $schemes->links() }}
                </div>
            </div>
        @endif
    @endif



</div>
@endsection
