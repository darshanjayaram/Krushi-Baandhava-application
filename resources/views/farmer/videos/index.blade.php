@extends('layouts.farmer')

@section('title', app()->getLocale() === 'en' ? 'Farming Videos & Training — Krushi Baandhava' : 'ಕೃಷಿ ವಿಡಿಯೋಗಳು & ತರಬೇತಿ — Curated Farming Videos')

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div x-data="{
    activeModal: false,
    activeVideoId: null,
    activeTitle: '',
    openVideo(id, title) {
        this.activeVideoId = id;
        this.activeTitle = title;
        this.activeModal = true;
    },
    closeVideo() {
        this.activeModal = false;
        this.activeVideoId = null;
    }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold mb-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>🎬 {{ $activeLocale === 'en' ? 'Curated Video Guides & Farmer Training' : 'ಕೃಷಿ ದೃಶ್ಯಾವಳಿ & ವಿಡಿಯೋ ತರಬೇತಿ' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Farming Videos' : 'ಕೃಷಿ ವಿಡಿಯೋಗಳು (Farming Videos)' }}
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Expert agricultural advice, progressive farmer experiences, and practical pest management.' : 'ಪರಿಣಿತರ ಬೇಸಾಯ ಸಲಹೆಗಳು, ಯಶಸ್ವಿ ರೈತರ ಅನುಭವ ಮತ್ತು ಕೀಟ ನಿಯಂತ್ರಣದ ಪ್ರಾಯೋಗಿಕ ಮಾಹಿತಿ.' }}
            </p>
        </div>

        <a href="{{ route('home') }}" class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>&larr; {{ $activeLocale === 'en' ? 'Back to Home' : 'ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white p-4 rounded-2xl border border-stone-200/90 shadow-xs space-y-3">
        <form method="GET" action="{{ route('farmer.videos.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
            @if($category)
                <input type="hidden" name="category" value="{{ $category }}">
            @endif

            <!-- Search input -->
            <div class="relative sm:col-span-7">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="{{ $activeLocale === 'en' ? 'Search video topic or YouTube channel...' : 'ವಿಡಿಯೋ ವಿಷಯ ಅಥವಾ ಯೂಟ್ಯೂಬ್ ಚಾನೆಲ್ ಹುಡುಕಿ...' }}"
                       class="w-full text-xs sm:text-sm pl-9 pr-8 py-2.5 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            </div>

            <!-- Crop filter -->
            <div class="sm:col-span-3">
                <select name="crop_id" 
                        onchange="this.form.submit()"
                        class="w-full text-xs sm:text-sm py-2.5 px-3 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <option value="">{{ $activeLocale === 'en' ? 'All Crops' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳು (All Crops)' }}</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ (string)$cropId === (string)$c->id ? 'selected' : '' }}>
                            {{ $activeLocale === 'en' ? $c->name : ($c->name_kn ?: $c->name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Filter' : 'ಫಿಲ್ಟರ್ ಮಾಡಿ' }}
                </button>
            </div>
        </form>

        <!-- Category Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
            <a href="{{ route('farmer.videos.index', array_filter(['crop_id' => $cropId, 'search' => $search])) }}" 
               class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ empty($category) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                {{ $activeLocale === 'en' ? 'All Videos' : 'ಎಲ್ಲಾ ವಿಡಿಯೋಗಳು' }}
            </a>
            @foreach($categories as $catKey => $catData)
                <a href="{{ route('farmer.videos.index', array_filter(['category' => $catKey, 'crop_id' => $cropId, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition flex items-center gap-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ $category === $catKey ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                    <span>{{ $catData['icon'] }}</span>
                    <span>{{ $activeLocale === 'en' ? ($catData['name_en'] ?? ucfirst($catKey)) : $catData['name_kn'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Video Grid -->
    @if($videos->isEmpty())
        <div class="text-center py-16 bg-white rounded-2xl border border-stone-200 shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="text-4xl">🎬</span>
            <h3 class="text-base font-bold text-stone-800 mt-2">{{ $activeLocale === 'en' ? 'No videos found' : 'ಯಾವುದೇ ವಿಡಿಯೋಗಳು ಕಂಡುಬಂದಿಲ್ಲ' }}</h3>
            <p class="text-xs text-stone-500 mt-1">{{ $activeLocale === 'en' ? 'Try selecting a different crop or topic.' : 'ಬೇರೆ ಬೆಳೆ ಅಥವಾ ವಿಷಯವನ್ನು ಆಯ್ಕೆ ಮಾಡಿ ನೋಡಿ.' }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($videos as $video)
                <div class="bg-white rounded-2xl border border-stone-200/90 overflow-hidden hover:border-emerald-500 hover:shadow-md transition flex flex-col group">
                    <!-- Thumbnail Container with Play Overlay -->
                    <div class="relative aspect-video bg-stone-900 cursor-pointer overflow-hidden" 
                         @click="openVideo('{{ $video->youtube_video_id }}', '{{ addslashes($video->title_kn ?: $video->title) }}')">
                        <img src="{{ $video->thumbnail_url }}" 
                             alt="{{ $video->title }}" 
                             loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        
                        <!-- Play Button Overlay -->
                        <div class="absolute inset-0 bg-stone-900/25 group-hover:bg-stone-900/10 transition flex items-center justify-center">
                            <div class="w-12 h-12 rounded-full bg-red-600 group-hover:bg-red-700 text-white flex items-center justify-center shadow-lg transform group-hover:scale-110 transition">
                                <svg class="w-6 h-6 ml-0.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Duration Pill -->
                        @if($video->duration_text)
                            <span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-black/80 text-white text-[10px] font-bold tracking-wider font-sans">
                                {{ $video->duration_text }}
                            </span>
                        @endif

                        <!-- Crop Badge -->
                        @if($video->crop)
                            <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-emerald-800/90 backdrop-blur-xs text-white text-[10px] font-bold {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? $video->crop->name : ($video->crop->name_kn ?: $video->crop->name) }}
                            </span>
                        @endif
                    </div>

                    <!-- Meta Details -->
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-stone-900 group-hover:text-emerald-700 transition line-clamp-2 leading-snug cursor-pointer {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                                @click="openVideo('{{ $video->youtube_video_id }}', '{{ addslashes($video->title_kn ?: $video->title) }}')">
                                {{ $activeLocale === 'en' ? ($video->title ?: $video->title_kn) : ($video->title_kn ?: $video->title) }}
                            </h3>
                            @if($activeLocale === 'kn' && $video->title_kn && $video->title)
                                <div class="text-[11px] text-stone-400 mt-0.5 line-clamp-1 font-sans">
                                    {{ $video->title }}
                                </div>
                            @elseif($activeLocale === 'en' && $video->title_kn)
                                <div class="text-[11px] text-stone-400 mt-0.5 line-clamp-1 font-kannada">
                                    {{ $video->title_kn }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 pt-3 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <span class="font-medium truncate max-w-[150px]">
                                {{ $video->channel_name ?: ($activeLocale === 'en' ? 'Agri Info' : 'ಕೃಷಿ ಮಾಹಿತಿ') }}
                            </span>
                            <button type="button" 
                                    @click="openVideo('{{ $video->youtube_video_id }}', '{{ addslashes($video->title_kn ?: $video->title) }}')"
                                    class="font-bold text-emerald-700 hover:text-emerald-800 transition">
                                {{ $activeLocale === 'en' ? 'Watch ▶' : 'ವೀಕ್ಷಿಸಿ ▶' }}
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $videos->links() }}
        </div>
    @endif

    <!-- Video Player Modal -->
    <div x-show="activeModal" 
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @keydown.escape.window="closeVideo()">
        <div class="bg-stone-900 rounded-3xl overflow-hidden shadow-2xl max-w-4xl w-full border border-stone-700/80" 
             @click.away="closeVideo()">
            
            <div class="flex items-center justify-between p-4 bg-stone-950 text-white">
                <h4 class="text-xs sm:text-sm font-bold truncate pr-4" x-text="activeTitle"></h4>
                <button type="button" @click="closeVideo()" class="p-1 rounded-lg text-stone-400 hover:text-white hover:bg-stone-800 text-lg font-bold">
                    ✕
                </button>
            </div>

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
