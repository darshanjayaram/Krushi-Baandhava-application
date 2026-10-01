@extends('layouts.farmer')

@section('title', app()->getLocale() === 'en' ? 'Farming Videos & Practical Guides — Krushi Baandhava' : 'ಕೃಷಿ ವಿಡಿಯೋಗಳು & ಪ್ರಾಯೋಗಿಕ ತರಬೇತಿ — ಕೃಷಿ ಬಾಂಧವ')

@section('content')
@php
    $activeLocale = app()->getLocale();
    $hasActiveFilters = !empty($search) || !empty($cropId) || !empty($category) || !empty($growthStage);
@endphp
<div x-data="{
    activeModal: false,
    activeVideoId: null,
    activeTitle: '',
    activeChannel: '',
    openVideo(id, title, channel = '') {
        this.activeVideoId = id;
        this.activeTitle = title;
        this.activeChannel = channel;
        this.activeModal = true;
    },
    closeVideo() {
        this.activeModal = false;
        this.activeVideoId = null;
    }
}" class="space-y-6 pb-12">

    <!-- ============================================================== -->
    <!-- 1. VIBRANT, MODERN & WELCOMING HERO BANNER (Ultra-Lean on Mobile) -->
    <!-- ============================================================== -->
    <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl border border-[#D9CEB8] bg-gradient-to-br from-[#10361C] via-[#0E2F19] to-[#0A2212] text-white p-3.5 sm:p-6 shadow-md">
        <!-- Ambient Warm Glows -->
        <div class="absolute -right-16 -top-16 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex items-center justify-between gap-3">
            <div class="space-y-0.5 sm:space-y-2">
                <!-- Eyebrow Badge -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-black/40 backdrop-blur-md border border-emerald-400/30 text-[10px] sm:text-[11px] font-black text-emerald-300 shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="hidden sm:inline">{{ $activeLocale === 'en' ? 'Curated Agricultural Video Guides' : 'ಕೃಷಿ ವಿಡಿಯೋ ಮಾರ್ಗದರ್ಶಿ • 100% ಪ್ರಾಯೋಗಿಕ ಮಾಹಿತಿ' }}</span>
                    <span class="sm:hidden">{{ $activeLocale === 'en' ? 'Video Guides' : 'ಕೃಷಿ ವಿಡಿಯೋಗಳು' }}</span>
                </div>

                <h1 class="text-base sm:text-2xl md:text-3xl font-black text-white tracking-tight leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Farming Videos & Farmer Training' : 'ಕೃಷಿ ವಿಡಿಯೋಗಳು & ತರಬೇತಿ' }}
                </h1>

                <!-- Subtitle: Desktop Only to save vertical screen space on mobile -->
                <p class="hidden sm:block text-xs sm:text-sm text-emerald-100/90 max-w-2xl leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' 
                        ? 'Handpicked scientific tutorials, crop protection breakthroughs, modern tools, and progressive Karnataka farmer experiences.' 
                        : 'ಕೃಷಿ ವಿವಿಗಳ ವೈಜ್ಞಾನಿಕ ಬೇಸಾಯ ಪದ್ಧತಿ, ಕೀಟ-ರೋಗ ನಿರ್ವಹಣೆ, ಆಧುನಿಕ ಯಂತ್ರೋಪಕರಣ ಹಾಗೂ ಪ್ರಗತಿಪರ ರೈತರ ನೈಜ ಯಶೋಗಾಥೆಗಳು.' }}
                </p>
            </div>

            <!-- Home Link -->
            <a href="{{ route('home') }}" 
               class="inline-flex items-center gap-1 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-[11px] sm:text-xs backdrop-blur-md border border-white/20 transition shadow-xs shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>&larr;</span>
                <span class="hidden sm:inline">{{ $activeLocale === 'en' ? 'Back to Home' : 'ಮುಖಪುಟ' }}</span>
                <span class="sm:hidden">{{ $activeLocale === 'en' ? 'Home' : 'ಮುಖಪುಟ' }}</span>
            </a>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. STREAMLINED UNIFIED SEARCH & FILTER BAR (Mobile-Optimized)   -->
    <!-- ============================================================== -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-stone-200/90 shadow-sm p-3 sm:p-4 space-y-2.5">
        
        <!-- Search & Dropdown Selectors Row: 2 cols on mobile, 12 cols on desktop -->
        <form method="GET" action="{{ route('farmer.videos.index') }}" class="grid grid-cols-2 sm:grid-cols-12 gap-2">
            @if($category)
                <input type="hidden" name="category" value="{{ $category }}">
            @endif

            <!-- Search input: full width on mobile -->
            <div class="relative col-span-2 sm:col-span-6">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="{{ $activeLocale === 'en' ? 'Search topic, pest, or channel...' : 'ಬೆಳೆ, ರೋಗ ಅಥವಾ ವಿಷಯ ಹುಡುಕಿ...' }}"
                       class="w-full text-xs pl-8 pr-7 py-2 bg-stone-50 border border-stone-200 rounded-xl sm:rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                @if($search)
                    <a href="{{ route('farmer.videos.index', array_filter(['crop_id' => $cropId, 'category' => $category, 'growth_stage' => $growthStage])) }}" 
                       class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-stone-400 hover:text-stone-700 text-xs font-bold"
                       title="Clear search">✕</a>
                @endif
            </div>

            <!-- Crop Selector: 1 col on mobile -->
            <div class="col-span-1 sm:col-span-3">
                <select name="crop_id" 
                        onchange="this.form.submit()"
                        class="w-full text-xs py-2 px-2.5 bg-stone-50 border border-stone-200 rounded-xl sm:rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-600 truncate {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <option value="">{{ $activeLocale === 'en' ? '🌾 All Crops' : '🌾 ಎಲ್ಲಾ ಬೆಳೆಗಳು' }}</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ (string)$cropId === (string)$c->id ? 'selected' : '' }}>
                            {{ $activeLocale === 'en' ? $c->name : ($c->name_kn ?: $c->name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Growth Stage Selector: 1 col on mobile (side-by-side with Crop!) -->
            <div class="col-span-1 sm:col-span-3">
                <select name="growth_stage" 
                        onchange="this.form.submit()"
                        class="w-full text-xs py-2 px-2.5 bg-amber-50/60 border border-amber-200 text-amber-950 font-bold rounded-xl sm:rounded-2xl focus:outline-none focus:ring-2 focus:ring-amber-500 truncate {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <option value="">{{ $activeLocale === 'en' ? '🌱 All Stages' : '🌱 ಎಲ್ಲಾ ಹಂತಗಳು' }}</option>
                    @foreach($growthStages as $stageKey => $stageData)
                        <option value="{{ $stageKey }}" {{ $growthStage === $stageKey ? 'selected' : '' }}>
                            {{ $stageData['icon'] }} {{ $activeLocale === 'en' ? ($stageData['name_en'] ?? ucfirst($stageKey)) : ($stageData['name_kn'] ?? ($stageData['name_en'] ?? ucfirst($stageKey))) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <!-- Category Horizontal Segmented Scroll Pills -->
        <div class="pt-1.5 border-t border-stone-100 flex items-center gap-1.5 overflow-x-auto pb-0.5 text-xs no-scrollbar -mx-1 px-1">
            <a href="{{ route('farmer.videos.index', array_filter(['crop_id' => $cropId, 'growth_stage' => $growthStage, 'search' => $search])) }}" 
               class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition text-xs leading-none inline-flex items-center gap-1 shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ empty($category) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200 hover:text-stone-900' }}">
                <span>🎬</span>
                <span>{{ $activeLocale === 'en' ? 'All' : 'ಎಲ್ಲಾ' }}</span>
            </a>

            @foreach($categories as $catKey => $catData)
                <a href="{{ route('farmer.videos.index', array_filter(['category' => $catKey, 'crop_id' => $cropId, 'growth_stage' => $growthStage, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition inline-flex items-center gap-1 text-xs leading-none shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ $category === $catKey ? 'bg-emerald-700 text-white shadow-xs font-black' : 'bg-stone-100 text-stone-600 hover:bg-stone-200 hover:text-stone-900' }}">
                    <span class="text-xs leading-none">{{ $catData['icon'] }}</span>
                    <span>{{ $activeLocale === 'en' ? ($catData['name_en'] ?? ucfirst($catKey)) : ($catData['name_kn'] ?? ($catData['name_en'] ?? ucfirst($catKey))) }}</span>
                </a>
            @endforeach
        </div>

        <!-- Active Filter Indicator & Reset -->
        @if($hasActiveFilters)
            <div class="pt-1.5 border-t border-stone-100 flex items-center justify-between text-xs flex-wrap gap-1.5">
                <div class="flex items-center gap-1 text-stone-500 text-[11px] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="font-bold">{{ $activeLocale === 'en' ? 'Filtered:' : 'ಫಿಲ್ಟರ್:' }}</span>
                    @if($search)
                        <span class="px-2 py-0.5 rounded bg-stone-100 text-stone-800 font-semibold">"{{ $search }}"</span>
                    @endif
                    @if($cropId)
                        @php $activeCrop = $crops->firstWhere('id', $cropId); @endphp
                        @if($activeCrop)
                            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">
                                🌾 {{ $activeLocale === 'en' ? $activeCrop->name : ($activeCrop->name_kn ?: $activeCrop->name) }}
                            </span>
                        @endif
                    @endif
                    @if($category && isset($categories[$category]))
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">
                            {{ $categories[$category]['icon'] }} {{ $activeLocale === 'en' ? $categories[$category]['name_en'] : $categories[$category]['name_kn'] }}
                        </span>
                    @endif
                    @if($growthStage && isset($growthStages[$growthStage]))
                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold">
                            {{ $growthStages[$growthStage]['icon'] }} {{ $activeLocale === 'en' ? $growthStages[$growthStage]['name_en'] : $growthStages[$growthStage]['name_kn'] }}
                        </span>
                    @endif
                </div>

                <a href="{{ route('farmer.videos.index') }}" 
                   class="font-black text-rose-600 hover:text-rose-700 text-[11px] transition inline-flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>✕ {{ $activeLocale === 'en' ? 'Clear' : 'ತೆರವುಗೊಳಿಸಿ' }}</span>
                </a>
            </div>
        @endif
    </div>

    <!-- ======================================================== -->
    <!-- 3. FEATURED VIDEO SHOWCASE (Tight & Responsive)          -->
    <!-- ======================================================== -->
    @if(isset($featuredVideo) && $featuredVideo)
        <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-white border-2 border-emerald-600/20 shadow-md hover:shadow-lg transition-all p-3.5 sm:p-5 group">
            <!-- Subtle decorative background warmth -->
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-gradient-to-br from-emerald-50 to-amber-50 rounded-full blur-2xl pointer-events-none opacity-60"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-3.5 sm:gap-6 items-center">
                
                <!-- Left Column: Video Thumbnail with Play Badge -->
                <div class="lg:col-span-5 relative aspect-video rounded-xl sm:rounded-2xl overflow-hidden cursor-pointer shadow-md bg-stone-900 border border-stone-200 group/thumb shrink-0"
                     @click="openVideo('{{ $featuredVideo->youtube_video_id }}', '{{ addslashes($featuredVideo->title_kn ?: $featuredVideo->title) }}', '{{ addslashes($featuredVideo->channel_name) }}')">
                    <img src="{{ $featuredVideo->thumbnail_url }}" 
                         alt="{{ $featuredVideo->title }}" 
                         class="w-full h-full object-cover group-hover/thumb:scale-105 transition duration-500">
                    
                    <!-- Vignette -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-black/20 pointer-events-none"></div>

                    <!-- Centered Play Button -->
                    <div class="absolute inset-0 bg-black/10 group-hover/thumb:bg-black/0 transition flex items-center justify-center">
                        <div class="w-12 sm:w-16 h-12 sm:h-16 rounded-full bg-red-600 text-white flex items-center justify-center shadow-2xl transform group-hover/thumb:scale-115 transition duration-300 ring-4 ring-white/50">
                            <svg class="w-6 sm:w-8 h-6 sm:h-8 ml-0.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>

                    <!-- Duration Pill -->
                    @if($featuredVideo->duration_text)
                        <span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded-md bg-black/85 text-white text-[10px] sm:text-[11px] font-bold font-mono tracking-wider shadow-sm">
                            {{ $featuredVideo->duration_text }}
                        </span>
                    @endif

                    <!-- Kannada Audio Pill -->
                    @if($featuredVideo->language === 'kn')
                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-md bg-emerald-800 text-white text-[9px] sm:text-[10px] font-bold shadow-sm border border-emerald-600">
                            ಕನ್ನಡ ಆಡಿಯೋ
                        </span>
                    @endif
                </div>

                <!-- Right Column: Info & Action -->
                <div class="lg:col-span-7 flex flex-col justify-between space-y-2.5 sm:space-y-3.5">
                    
                    <!-- Top Badges Row -->
                    <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-100 text-amber-900 border border-amber-300/80 text-[10px] sm:text-xs font-black tracking-wide leading-none uppercase shadow-xs">
                            ★ {{ $activeLocale === 'en' ? "Today's Spotlight" : 'ಇಂದಿನ ವಿಶೇಷ ಶಿಫಾರಸು' }}
                        </span>
                        @if($featuredVideo->crop)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200 text-[10px] sm:text-xs font-bold leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                🌾 {{ $activeLocale === 'en' ? $featuredVideo->crop->name : ($featuredVideo->crop->name_kn ?: $featuredVideo->crop->name) }}
                            </span>
                        @endif
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-stone-100 text-stone-700 border border-stone-200 text-[10px] sm:text-xs font-bold leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $featuredVideo->getGrowthStageName($activeLocale) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-stone-100 text-stone-600 text-[10px] sm:text-xs font-medium leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $featuredVideo->getCategoryName($activeLocale) }}
                        </span>
                    </div>

                    <!-- Title & Subtitle -->
                    <div>
                        <h2 class="text-base sm:text-xl font-black text-stone-900 hover:text-emerald-700 transition leading-snug cursor-pointer line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                            @click="openVideo('{{ $featuredVideo->youtube_video_id }}', '{{ addslashes($featuredVideo->title_kn ?: $featuredVideo->title) }}', '{{ addslashes($featuredVideo->channel_name) }}')">
                            {{ $activeLocale === 'en' ? ($featuredVideo->title ?: $featuredVideo->title_kn) : ($featuredVideo->title_kn ?: $featuredVideo->title) }}
                        </h2>

                        @if($activeLocale === 'kn' && $featuredVideo->title_kn && $featuredVideo->title)
                            <p class="text-xs text-stone-500 font-sans mt-1 line-clamp-1">
                                {{ $featuredVideo->title }}
                            </p>
                        @elseif($activeLocale === 'en' && $featuredVideo->title_kn)
                            <p class="text-xs text-stone-500 font-kannada mt-1 line-clamp-1">
                                {{ $featuredVideo->title_kn }}
                            </p>
                        @endif
                    </div>

                    <!-- Footer: Channel & Watch Button -->
                    <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-2 text-xs text-stone-600 truncate">
                            <span class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-[10px] shrink-0">
                                ▶
                            </span>
                            <div class="truncate">
                                <span class="text-stone-400 text-[10px] block leading-none">{{ $activeLocale === 'en' ? 'Channel / Creator' : 'ಮೂಲ ಚಾನೆಲ್' }}</span>
                                <span class="font-extrabold text-stone-800 text-xs mt-0.5 block truncate max-w-[150px] sm:max-w-xs">
                                    {{ $featuredVideo->channel_name ?: 'Krushi Guide' }}
                                </span>
                            </div>
                        </div>

                        <button type="button" 
                                @click="openVideo('{{ $featuredVideo->youtube_video_id }}', '{{ addslashes($featuredVideo->title_kn ?: $featuredVideo->title) }}', '{{ addslashes($featuredVideo->channel_name) }}')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-700 to-teal-700 hover:from-emerald-600 hover:to-teal-600 text-white text-xs sm:text-sm font-black shadow-md hover:shadow-lg transition active:scale-95 cursor-pointer shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <span>{{ $activeLocale === 'en' ? 'Watch Full Video' : 'ವಿಡಿಯೋ ವೀಕ್ಷಿಸಿ' }}</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 4. VIDEO CATALOG & COUNTER                 -->
    <!-- ========================================== -->
    <div class="flex items-center justify-between text-xs text-stone-500 px-1 pt-2">
        <div class="font-bold flex items-center gap-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
            <span>
                {{ $activeLocale === 'en' 
                    ? "Showing {$videos->firstItem()}–{$videos->lastItem()} of {$videos->total()} curated videos" 
                    : "ಒಟ್ಟು {$videos->total()} ವಿಡಿಯೋಗಳಲ್ಲಿ {$videos->firstItem()}–{$videos->lastItem()} ಪ್ರದರ್ಶಿಸಲಾಗುತ್ತಿದೆ" }}
            </span>
        </div>
        <div class="text-[11px] text-stone-400 font-bold font-sans bg-stone-100 px-2.5 py-1 rounded-lg">
            Page {{ $videos->currentPage() }} of {{ $videos->lastPage() }}
        </div>
    </div>

    <!-- Video Grid -->
    @if($videos->isEmpty())
        <div class="text-center py-16 bg-white rounded-3xl border border-stone-200 shadow-sm p-6 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="text-5xl block mb-2">🎬</span>
            <h3 class="text-base font-extrabold text-stone-800">
                {{ $activeLocale === 'en' ? 'No videos found for this filter' : 'ಯಾವುದೇ ವಿಡಿಯೋಗಳು ಕಂಡುಬಂದಿಲ್ಲ' }}
            </h3>
            <p class="text-xs text-stone-500 mt-1 max-w-sm mx-auto">
                {{ $activeLocale === 'en' 
                    ? 'Try selecting a different crop or topic, or reset all active filters.' 
                    : 'ಬೇರೆ ಬೆಳೆ ಅಥವಾ ವಿಷಯವನ್ನು ಆಯ್ಕೆ ಮಾಡಿ ನೋಡಿ ಅಥವಾ ಫಿಲ್ಟರ್‌ಗಳನ್ನು ತೆರವುಗೊಳಿಸಿ.' }}
            </p>
            @if($hasActiveFilters)
                <div class="mt-4">
                    <a href="{{ route('farmer.videos.index') }}" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition">
                        <span>✕ {{ $activeLocale === 'en' ? 'Clear Filters' : 'ಫಿಲ್ಟರ್ ತೆರವುಗೊಳಿಸಿ' }}</span>
                    </a>
                </div>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($videos as $video)
                <div class="bg-white rounded-3xl border border-stone-200/90 overflow-hidden hover:border-emerald-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                    
                    <!-- Thumbnail Container with Play Overlay -->
                    <div class="relative aspect-video bg-stone-900 cursor-pointer overflow-hidden" 
                         @click="openVideo('{{ $video->youtube_video_id }}', '{{ addslashes($video->title_kn ?: $video->title) }}', '{{ addslashes($video->channel_name) }}')">
                        <img src="{{ $video->thumbnail_url }}" 
                             alt="{{ $video->title }}" 
                             loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        
                        <!-- Gradient vignette -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20 pointer-events-none"></div>

                        <!-- Centered Play Button -->
                        <div class="absolute inset-0 bg-stone-950/20 group-hover:bg-stone-950/0 transition flex items-center justify-center">
                            <div class="w-12 h-12 rounded-full bg-red-600 group-hover:bg-red-700 text-white flex items-center justify-center shadow-xl transform group-hover:scale-115 transition duration-300 ring-2 ring-white/60">
                                <svg class="w-6 h-6 ml-0.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Top Floating Badges -->
                        <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between pointer-events-none">
                            @if($video->crop)
                                <span class="px-2.5 py-0.5 rounded-lg bg-emerald-900/90 backdrop-blur-md text-white text-[10px] font-bold leading-none inline-flex items-center shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    🌾 {{ $activeLocale === 'en' ? $video->crop->name : ($video->crop->name_kn ?: $video->crop->name) }}
                                </span>
                            @else
                                <span></span>
                            @endif

                            @if($video->is_featured)
                                <span class="px-2 py-0.5 rounded-lg bg-amber-400 text-amber-950 text-[10px] font-black leading-none uppercase shadow-sm">
                                    ★ {{ $activeLocale === 'en' ? 'Featured' : 'ವಿಶೇಷ' }}
                                </span>
                            @elseif($video->language === 'kn')
                                <span class="px-1.5 py-0.5 rounded-lg bg-black/70 backdrop-blur-md text-emerald-300 text-[10px] font-bold leading-none border border-emerald-400/30">
                                    ಕನ್ನಡ
                                </span>
                            @endif
                        </div>

                        <!-- Duration Pill (Bottom Right) -->
                        @if($video->duration_text)
                            <span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded-md bg-black/85 text-white text-[10px] font-bold font-mono tracking-wider shadow-xs">
                                {{ $video->duration_text }}
                            </span>
                        @endif
                    </div>

                    <!-- Meta Details -->
                    <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                        <div>
                            <!-- Tags Row: Growth Stage & Category -->
                            <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-900 border border-amber-200/80 text-[10px] font-extrabold leading-none inline-flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $video->getGrowthStageName($activeLocale) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-700 text-[10px] font-bold leading-none inline-flex items-center gap-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    {{ $video->getCategoryName($activeLocale) }}
                                </span>
                            </div>

                            <!-- Video Title -->
                            <h3 class="text-sm font-black text-stone-900 group-hover:text-emerald-700 transition line-clamp-2 leading-snug cursor-pointer {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                                @click="openVideo('{{ $video->youtube_video_id }}', '{{ addslashes($video->title_kn ?: $video->title) }}', '{{ addslashes($video->channel_name) }}')">
                                {{ $activeLocale === 'en' ? ($video->title ?: $video->title_kn) : ($video->title_kn ?: $video->title) }}
                            </h3>

                            @if($activeLocale === 'kn' && $video->title_kn && $video->title)
                                <div class="text-[11px] text-stone-400 mt-1 line-clamp-1 font-sans">
                                    {{ $video->title }}
                                </div>
                            @elseif($activeLocale === 'en' && $video->title_kn)
                                <div class="text-[11px] text-stone-400 mt-1 line-clamp-1 font-kannada">
                                    {{ $video->title_kn }}
                                </div>
                            @endif
                        </div>

                        <!-- Footer: Channel Info & Watch CTA -->
                        <div class="pt-3 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
                            <div class="flex items-center gap-1.5 truncate max-w-[150px]">
                                <span class="w-5 h-5 rounded-full bg-red-50 text-red-600 flex items-center justify-center font-bold text-[9px] shrink-0">
                                    ▶
                                </span>
                                <span class="font-bold text-stone-700 text-[11px] truncate">
                                    {{ $video->channel_name ?: ($activeLocale === 'en' ? 'Agri Info' : 'ಕೃಷಿ ಮಾಹಿತಿ') }}
                                </span>
                            </div>

                            <button type="button" 
                                    @click="openVideo('{{ $video->youtube_video_id }}', '{{ addslashes($video->title_kn ?: $video->title) }}', '{{ addslashes($video->channel_name) }}')"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-extrabold text-xs transition cursor-pointer {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                <span>{{ $activeLocale === 'en' ? 'Watch' : 'ವೀಕ್ಷಿಸಿ' }}</span>
                                <span class="text-[10px]">▶</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ========================================== -->
        <!-- 5. CLASSIC RESPONSIVE PAGINATION BAR       -->
        <!-- ========================================== -->
        @if($videos->hasPages())
            <div class="mt-8 pt-6 border-t border-stone-200">
                <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    
                    <!-- Mobile View: Clean Prev / Page Counter / Next -->
                    <div class="flex sm:hidden items-center justify-between w-full">
                        @if($videos->onFirstPage())
                            <span class="px-4 py-2 rounded-xl bg-stone-100 text-stone-400 text-xs font-bold border border-stone-200 cursor-not-allowed">
                                &larr; {{ $activeLocale === 'en' ? 'Previous' : 'ಹಿಂದಿನದು' }}
                            </span>
                        @else
                            <a href="{{ $videos->previousPageUrl() }}" 
                               class="px-4 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-300 shadow-xs transition">
                                &larr; {{ $activeLocale === 'en' ? 'Previous' : 'ಹಿಂದಿನದು' }}
                            </a>
                        @endif

                        <span class="text-xs font-black text-stone-700 bg-stone-100 px-3 py-1.5 rounded-xl">
                            {{ $videos->currentPage() }} / {{ $videos->lastPage() }}
                        </span>

                        @if($videos->hasMorePages())
                            <a href="{{ $videos->nextPageUrl() }}" 
                               class="px-4 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-300 shadow-xs transition">
                                {{ $activeLocale === 'en' ? 'Next' : 'ಮುಂದಿನದು' }} &rarr;
                            </a>
                        @else
                            <span class="px-4 py-2 rounded-xl bg-stone-100 text-stone-400 text-xs font-bold border border-stone-200 cursor-not-allowed">
                                {{ $activeLocale === 'en' ? 'Next' : 'ಮುಂದಿನದು' }} &rarr;
                            </span>
                        @endif
                    </div>

                    <!-- Desktop View: Left Summary + Right Numbered Buttons -->
                    <div class="hidden sm:block text-xs text-stone-500 font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' 
                            ? "Page {$videos->currentPage()} of {$videos->lastPage()} ({$videos->total()} total videos)" 
                            : "ಪುಟ {$videos->currentPage()} / {$videos->lastPage()} (ಒಟ್ಟು {$videos->total()} ವಿಡಿಯೋಗಳು)" }}
                    </div>

                    <div class="hidden sm:flex items-center gap-1.5">
                        <!-- Prev Button -->
                        @if($videos->onFirstPage())
                            <span class="px-3.5 py-2 rounded-xl bg-stone-100 text-stone-400 text-xs font-bold border border-stone-200 cursor-not-allowed">
                                &larr; {{ $activeLocale === 'en' ? 'Prev' : 'ಹಿಂದಿನದು' }}
                            </span>
                        @else
                            <a href="{{ $videos->previousPageUrl() }}" 
                               class="px-3.5 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-xs transition">
                                &larr; {{ $activeLocale === 'en' ? 'Prev' : 'ಹಿಂದಿನದು' }}
                            </a>
                        @endif

                        <!-- Page Number Buttons (With Intelligent Sliding Window) -->
                        @php
                            $current = $videos->currentPage();
                            $last = $videos->lastPage();
                            $start = max(1, $current - 2);
                            $end = min($last, $current + 2);
                        @endphp

                        @if($start > 1)
                            <a href="{{ $videos->url(1) }}" class="w-9 h-9 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-xs flex items-center justify-center transition">
                                1
                            </a>
                            @if($start > 2)
                                <span class="px-1 text-stone-400 font-bold">…</span>
                            @endif
                        @endif

                        @for($page = $start; $page <= $end; $page++)
                            @if($page == $current)
                                <span class="w-9 h-9 rounded-xl bg-emerald-700 text-white text-xs font-black shadow-sm flex items-center justify-center">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $videos->url($page) }}" class="w-9 h-9 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-xs flex items-center justify-center transition">
                                    {{ $page }}
                                </a>
                            @endif
                        @endfor

                        @if($end < $last)
                            @if($end < $last - 1)
                                <span class="px-1 text-stone-400 font-bold">…</span>
                            @endif
                            <a href="{{ $videos->url($last) }}" class="w-9 h-9 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-xs flex items-center justify-center transition">
                                {{ $last }}
                            </a>
                        @endif

                        <!-- Next Button -->
                        @if($videos->hasMorePages())
                            <a href="{{ $videos->nextPageUrl() }}" 
                               class="px-3.5 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-xs transition">
                                {{ $activeLocale === 'en' ? 'Next' : 'ಮುಂದಿನದು' }} &rarr;
                            </a>
                        @else
                            <span class="px-3.5 py-2 rounded-xl bg-stone-100 text-stone-400 text-xs font-bold border border-stone-200 cursor-not-allowed">
                                {{ $activeLocale === 'en' ? 'Next' : 'ಮುಂದಿನದು' }} &rarr;
                            </span>
                        @endif
                    </div>
                </nav>
            </div>
        @endif
    @endif

    <!-- ========================================== -->
    <!-- 6. IN-PAGE VIDEO CINEMA MODAL              -->
    <!-- ========================================== -->
    <div x-show="activeModal" 
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/85 backdrop-blur-md"
         @keydown.escape.window="closeVideo()">
        <div class="bg-stone-900 rounded-3xl overflow-hidden shadow-2xl max-w-4xl w-full border border-stone-700/80 flex flex-col" 
             @click.away="closeVideo()">
            
            <!-- Modal Header Bar -->
            <div class="flex items-center justify-between p-3.5 sm:p-4 bg-stone-950 text-white border-b border-stone-800">
                <div class="truncate pr-3">
                    <h4 class="text-xs sm:text-sm font-extrabold truncate font-kannada" x-text="activeTitle"></h4>
                    <span class="text-[10px] sm:text-[11px] text-stone-400 font-medium block truncate" x-text="activeChannel ? 'Channel: ' + activeChannel : 'Krushi Baandhava Video'"></span>
                </div>
                
                <div class="flex items-center gap-2 shrink-0">
                    <template x-if="activeVideoId">
                        <a :href="'https://www.youtube.com/watch?v=' + activeVideoId" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="px-2.5 py-1 rounded-lg bg-stone-800 hover:bg-stone-700 text-stone-300 text-[10px] font-bold transition inline-flex items-center gap-1">
                            <span>YouTube ↗</span>
                        </a>
                    </template>
                    <button type="button" 
                            @click="closeVideo()" 
                            class="w-8 h-8 rounded-full bg-stone-800 hover:bg-stone-700 text-stone-300 hover:text-white flex items-center justify-center text-xs font-bold transition">
                        ✕
                    </button>
                </div>
            </div>

            <!-- YouTube Video Embed -->
            <div class="aspect-video w-full bg-black">
                <template x-if="activeVideoId">
                    <iframe :src="'https://www.youtube.com/embed/' + activeVideoId + '?autoplay=1&rel=0'" 
                            title="YouTube video player" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen 
                            class="w-full h-full">
                    </iframe>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
