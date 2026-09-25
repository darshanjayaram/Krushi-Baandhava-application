@extends('layouts.farmer')

@section('title', 'ಕೃಷಿ ಮಾರ್ಗದರ್ಶಿ ಮತ್ತು ಬೇಸಾಯ ಕ್ರಮಗಳು — Agronomy Guides')

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>📚 {{ $activeLocale === 'en' ? 'Agronomy Knowledge Base' : 'ತಜ್ಞರ ಬೇಸಾಯ ಕೈಪಿಡಿ' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Agri Guides & Agronomy Articles' : 'ಕೃಷಿ ಮಾರ್ಗದರ್ಶಿ & ಲೇಖನಗಳು' }}
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Scientific practices, nutrient management, pest prevention and yield-boosting techniques.' : 'ವೈಜ್ಞಾನಿಕ ಕೃಷಿ ವಿಧಾನಗಳು, ಮಣ್ಣಿನ ಪೋಷಕಾಂಶ, ರೋಗ ನಿಯಂತ್ರಣ ಹಾಗೂ ಇಳುವರಿ ಹೆಚ್ಚಿಸುವ ತಂತ್ರಗಳು.' }}
            </p>
        </div>

        <a href="{{ route('home') }}" class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>&larr; {{ $activeLocale === 'en' ? 'Back to Home' : 'ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-2xl border border-stone-200/90 shadow-xs space-y-3">
        <form method="GET" action="{{ route('farmer.articles.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
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
                       placeholder="{{ $activeLocale === 'en' ? 'Search article topic...' : 'ಲೇಖನದ ವಿಷಯ ಹುಡುಕಿ (ಉದಾ: ರೋಗ ನಿಯಂತ್ರಣ, ಗೊಬ್ಬರ)...' }}"
                       class="w-full text-xs sm:text-sm pl-9 pr-8 py-2.5 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            </div>

            <!-- Crop filter -->
            <div class="sm:col-span-3">
                <select name="crop_id" 
                        onchange="this.form.submit()"
                        class="w-full text-xs sm:text-sm py-2.5 px-3 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <option value="">{{ $activeLocale === 'en' ? 'All Crops' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳು' }}</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ (string)$cropId === (string)$c->id ? 'selected' : '' }}>
                            {{ $activeLocale === 'en' ? $c->name : ($c->name_kn ?: $c->name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition">
                    ಹುಡುಕಿ
                </button>
            </div>
        </form>

        <!-- Category Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
            <a href="{{ route('farmer.articles.index', array_filter(['crop_id' => $cropId, 'search' => $search])) }}" 
               class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ empty($category) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                ಎಲ್ಲಾ ಲೇಖನಗಳು
            </a>
            @foreach($categories as $catKey => $catData)
                <a href="{{ route('farmer.articles.index', array_filter(['category' => $catKey, 'crop_id' => $cropId, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition flex items-center gap-1.5 {{ $category === $catKey ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                    <span>{{ $catData['icon'] }}</span>
                    <span>{{ $catData['name_kn'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Articles Grid -->
    @if($articles->isEmpty())
        <div class="text-center py-16 bg-white rounded-2xl border border-stone-200 shadow-xs">
            <span class="text-4xl">📖</span>
            <h3 class="text-base font-bold text-stone-800 mt-2">ಯಾವುದೇ ಲೇಖನಗಳು ಕಂಡುಬಂದಿಲ್ಲ</h3>
            <p class="text-xs text-stone-500 mt-1">ಬೇರೆ ಬೆಳೆ ಅಥವಾ ವರ್ಗವನ್ನು ಆಯ್ಕೆ ಮಾಡಿ ನೋಡಿ.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($articles as $art)
                <div class="bg-white rounded-2xl border border-stone-200/90 hover:border-emerald-500 hover:shadow-md transition flex flex-col p-5 group">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            {{ $art->category_label_kn }}
                        </span>
                        @if($art->crop)
                            <span class="text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-200 px-2 py-0.5 rounded-md">
                                🌾 {{ $art->crop->kannada_name ?: $art->crop->name }}
                            </span>
                        @endif
                    </div>

                    <h3 class="text-base font-extrabold text-stone-900 group-hover:text-emerald-700 transition line-clamp-2 leading-snug">
                        {{ $art->title_kn ?: $art->title }}
                    </h3>
                    @if($art->title_kn && $art->title)
                        <div class="text-xs text-stone-400 font-medium mt-0.5 line-clamp-1">
                            {{ $art->title }}
                        </div>
                    @endif

                    <p class="text-xs text-stone-600 mt-3 line-clamp-3 leading-relaxed flex-1">
                        {{ $art->summary_kn ?: $art->summary }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs">
                        <div class="text-[11px] text-stone-400">
                            {{ $art->author_name ? 'ಲೇಖಕರು: ' . $art->author_name : '' }}
                        </div>
                        <a href="{{ route('farmer.articles.show', $art->slug) }}" class="font-bold text-emerald-700 hover:text-emerald-800 transition">
                            ಓದಿ &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $articles->links() }}
        </div>
    @endif

</div>
@endsection
