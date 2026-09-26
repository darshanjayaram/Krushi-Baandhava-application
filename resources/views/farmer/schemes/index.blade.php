@extends('layouts.farmer')

@section('title', app()->getLocale() === 'en' ? 'Government Schemes & Subsidies — Krushi Baandhava' : 'ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು ಮತ್ತು ಸಬ್ಸಿಡಿ — Karnataka Farmer Schemes')

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>🏛️ {{ $activeLocale === 'en' ? 'Department of Agriculture & State Support' : 'ಕೃಷಿ ಇಲಾಖೆ & ಸರ್ಕಾರದ ನೆರವು' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Government Schemes & Subsidies' : 'ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು & ಸಬ್ಸಿಡಿ (Government Schemes)' }}
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Karnataka and Central agricultural subsidies, farm machinery grants, and crop insurance schemes.' : 'ಕರ್ನಾಟಕ ಮತ್ತು ಕೇಂದ್ರ ಸರ್ಕಾರದ ಕೃಷಿ ಸಬ್ಸಿಡಿಗಳು, ಯಂತ್ರೋಪಕರಣ ನೆರವು ಹಾಗೂ ಬೆಳೆ ವಿಮೆ ವಿವರಗಳು.' }}
            </p>
        </div>

        <a href="{{ route('home') }}" class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>&larr; {{ $activeLocale === 'en' ? 'Back to Home' : 'ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>
    </div>

    <!-- Search & Category Filters -->
    <div class="bg-white p-4 rounded-2xl border border-stone-200/90 shadow-xs space-y-3">
        <!-- Search bar -->
        <form method="GET" action="{{ route('farmer.schemes.index') }}" class="flex items-center gap-2">
            @if($category)
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="{{ $activeLocale === 'en' ? 'Search schemes (e.g. Tractor, PM-KISAN, Drip irrigation)...' : 'ಯೋಜನೆ ಹೆಸರು ಅಥವಾ ಇಲಾಖೆ ಹುಡುಕಿ (Search schemes, e.g. Tractor, PM-KISAN, ಹನಿ ನೀರಾವರಿ)...' }}"
                       class="w-full text-xs sm:text-sm pl-9 pr-10 py-2.5 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                @if($search)
                    <a href="{{ route('farmer.schemes.index', ['category' => $category]) }}" class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-600 text-sm font-bold">
                        ✕
                    </a>
                @endif
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Search' : 'ಹುಡುಕಿ' }}
            </button>
        </form>

        <!-- Category Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
            <a href="{{ route('farmer.schemes.index', array_filter(['search' => $search])) }}" 
               class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ empty($category) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                {{ $activeLocale === 'en' ? 'All Schemes' : 'ಎಲ್ಲಾ ಯೋಜನೆಗಳು (All)' }}
            </a>
            @foreach($categories as $catKey => $catData)
                <a href="{{ route('farmer.schemes.index', array_filter(['category' => $catKey, 'search' => $search])) }}" 
                   class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition flex items-center gap-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ $category === $catKey ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                    <span>{{ $catData['icon'] }}</span>
                    <span>{{ $activeLocale === 'en' ? ($catData['name_en'] ?? ucfirst($catKey)) : $catData['name_kn'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Schemes Grid -->
    @if($schemes->isEmpty())
        <div class="text-center py-16 bg-white rounded-2xl border border-stone-200 shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="text-4xl">🔍</span>
            <h3 class="text-base font-bold text-stone-800 mt-2">{{ $activeLocale === 'en' ? 'No schemes found' : 'ಯಾವುದೇ ಯೋಜನೆಗಳು ಕಂಡುಬಂದಿಲ್ಲ' }}</h3>
            <p class="text-xs text-stone-500 mt-1">{{ $activeLocale === 'en' ? 'Try searching with different keywords or clearing filters.' : 'ಬೇರೆ ಪದಗಳಿಂದ ಹುಡುಕಿ ಅಥವಾ ಫಿಲ್ಟರ್ ಬದಲಾಯಿಸಿ ನೋಡಿ.' }}</p>
            <a href="{{ route('farmer.schemes.index') }}" class="inline-block mt-4 text-xs font-bold text-emerald-700 hover:underline">
                {{ $activeLocale === 'en' ? 'View all schemes' : 'ಎಲ್ಲಾ ಯೋಜನೆಗಳನ್ನು ವೀಕ್ಷಿಸಿ' }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($schemes as $scheme)
                <div class="bg-white rounded-2xl border border-stone-200/90 hover:border-emerald-500 hover:shadow-md transition flex flex-col p-5 group">
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? ($scheme->category_label_en ?? ucfirst($scheme->category)) : $scheme->category_label_kn }}
                        </span>
                        @if($scheme->sponsoring_agency)
                            <span class="text-[10px] font-semibold text-stone-500 bg-stone-100 px-2 py-0.5 rounded-md">
                                {{ $scheme->sponsoring_agency }}
                            </span>
                        @endif
                    </div>

                    <h3 class="text-base font-extrabold text-stone-900 group-hover:text-emerald-700 transition line-clamp-2 leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? ($scheme->title ?: $scheme->title_kn) : ($scheme->title_kn ?: $scheme->title) }}
                    </h3>
                    @if($activeLocale === 'kn' && $scheme->title_kn && $scheme->title)
                        <div class="text-xs text-stone-400 font-medium mt-0.5 line-clamp-1 font-sans">
                            {{ $scheme->title }}
                        </div>
                    @elseif($activeLocale === 'en' && $scheme->title_kn)
                        <div class="text-xs text-stone-400 font-medium mt-0.5 line-clamp-1 font-kannada">
                            {{ $scheme->title_kn }}
                        </div>
                    @endif

                    <p class="text-xs text-stone-600 mt-3 line-clamp-3 leading-relaxed flex-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? ($scheme->summary ?: $scheme->summary_kn) : ($scheme->summary_kn ?: $scheme->summary) }}
                    </p>

                    @if($scheme->benefits || $scheme->benefits_kn)
                        <div class="mt-4 p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/60 text-xs text-amber-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            <div class="font-bold text-[11px] text-amber-950 mb-0.5 flex items-center gap-1">
                                <span>💡 {{ $activeLocale === 'en' ? 'Key Benefit:' : 'ಮುಖ್ಯ ಸೌಲಭ್ಯ:' }}</span>
                            </div>
                            <div class="line-clamp-2">
                                {{ Str::limit(strip_tags($activeLocale === 'en' ? ($scheme->benefits ?: $scheme->benefits_kn) : ($scheme->benefits_kn ?: $scheme->benefits)), 100) }}
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 pt-3 border-t border-stone-100 flex items-center justify-between {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <a href="{{ route('farmer.schemes.show', $scheme->slug) }}" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-800 transition">
                            <span>{{ $activeLocale === 'en' ? 'Details & Guidelines' : 'ಮಾಹಿತಿ & ಅರ್ಜಿ ವಿವರಗಳು' }} &rarr;</span>
                        </a>
                        @if($scheme->official_url)
                            <a href="{{ $scheme->official_url }}" target="_blank" rel="noopener" class="text-[11px] font-semibold text-stone-400 hover:text-stone-700 flex items-center gap-0.5">
                                <span>{{ $activeLocale === 'en' ? 'Portal' : 'ಪೋರ್ಟಲ್' }}</span>
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $schemes->links() }}
        </div>
    @endif

</div>
@endsection
