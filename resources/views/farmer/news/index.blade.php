@extends('layouts.farmer')

@section('title', app()->getLocale() === 'en' ? 'Agri News & Alerts — Krushi Baandhava' : 'ಕೃಷಿ ಮಾರುಕಟ್ಟೆ ಸುದ್ದಿ & ಅಪ್ಡೇಟ್ಸ್ — Krushi News & Alerts')

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold mb-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>📰 {{ $activeLocale === 'en' ? 'Fresh Updates & Government Orders' : 'ತಾಜಾ ಮಾಹಿತಿ & ಸರ್ಕಾರಿ ಆದೇಶಗಳು' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Agri News & Market Alerts' : 'ಕೃಷಿ ಸುದ್ದಿ & ಸಮಾಚಾರ (Agri News & Alerts)' }}
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Price fluctuations, weather alerts, pest notices, and official agriculture announcements.' : 'ಮಂಡಿ ದರ ಏರಿಳಿತ, ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ, ರೋಗ ಬಾಧೆ ಎಚ್ಚರಿಕೆ ಮತ್ತು ಸರ್ಕಾರದ ಪ್ರಕಟಣೆಗಳು.' }}
            </p>
        </div>

        <a href="{{ route('home') }}" class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>&larr; {{ $activeLocale === 'en' ? 'Back to Home' : 'ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>
    </div>

    <!-- Breaking News Ticker / Highlight if present -->
    @if($breakingNews->isNotEmpty())
        <div class="bg-gradient-to-r from-red-600 via-red-500 to-amber-600 rounded-2xl p-4 text-white shadow-sm">
            <div class="flex items-center gap-2 mb-2 font-extrabold text-xs uppercase tracking-wider text-amber-200 font-sans">
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-200 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
                </span>
                <span>🚨 {{ $activeLocale === 'en' ? 'Breaking Agri Updates' : 'ಬ್ರೇಕಿಂಗ್ ಕೃಷಿ ಸುದ್ದಿ (Breaking Updates)' }}</span>
            </div>
            <div class="space-y-2">
                @foreach($breakingNews as $item)
                    <div class="flex items-start sm:items-center justify-between gap-2 border-b border-red-400/40 last:border-0 pb-1.5 last:pb-0">
                        <a href="{{ route('farmer.news.show', $item->slug) }}" class="text-xs sm:text-sm font-bold text-white hover:text-amber-100 line-clamp-1 transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            • {{ $activeLocale === 'en' ? ($item->title ?: $item->title_kn) : ($item->title_kn ?: $item->title) }}
                        </a>
                        <span class="text-[10px] text-red-100 shrink-0 font-medium font-sans">
                            {{ $item->published_at ? $item->published_at->diffForHumans() : ($activeLocale === 'en' ? 'Recently' : 'ಇತ್ತೀಚೆಗೆ') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Priority Filter Bar -->
    <div class="bg-white p-3 rounded-2xl border border-stone-200/90 shadow-xs flex items-center gap-2 overflow-x-auto text-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
        <a href="{{ route('farmer.news.index') }}" 
           class="px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ empty($priority) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            {{ $activeLocale === 'en' ? 'All News' : 'ಎಲ್ಲಾ ಸುದ್ದಿಗಳು (All News)' }}
        </a>
        <a href="{{ route('farmer.news.index', ['priority' => 'breaking']) }}" 
           class="px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition flex items-center gap-1 {{ $priority === 'breaking' ? 'bg-red-600 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            <span>🚨</span>
            <span>{{ $activeLocale === 'en' ? 'Breaking' : 'ತುರ್ತು / ಬ್ರೇಕಿಂಗ್' }}</span>
        </a>
        <a href="{{ route('farmer.news.index', ['priority' => 'high']) }}" 
           class="px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition flex items-center gap-1 {{ $priority === 'high' ? 'bg-amber-600 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            <span>⚡</span>
            <span>{{ $activeLocale === 'en' ? 'High Priority' : 'ಪ್ರಮುಖ ಸುದ್ದಿ' }}</span>
        </a>
    </div>

    <!-- News Grid -->
    @if($news->isEmpty())
        <div class="text-center py-16 bg-white rounded-2xl border border-stone-200 shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="text-4xl">📰</span>
            <h3 class="text-base font-bold text-stone-800 mt-2">{{ $activeLocale === 'en' ? 'No news articles found' : 'ಯಾವುದೇ ಸುದ್ದಿ ಕಂಡುಬಂದಿಲ್ಲ' }}</h3>
            <p class="text-xs text-stone-500 mt-1">{{ $activeLocale === 'en' ? 'Please check back later for updates.' : 'ಇನ್ನಷ್ಟು ಮಾಹಿತಿಗಾಗಿ ನಂತರ ಪರಿಶೀಲಿಸಿ.' }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($news as $article)
                <div class="bg-white rounded-2xl border border-stone-200/90 hover:border-emerald-500 hover:shadow-md transition flex flex-col p-5 group">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase {{ $article->priority_badge_class }}">
                            {{ $article->priority_label }}
                        </span>
                        <span class="text-[11px] font-semibold text-stone-400 font-sans">
                            {{ $article->published_at ? $article->published_at->format('d M, Y') : '' }}
                        </span>
                    </div>

                    <h3 class="text-base font-extrabold text-stone-900 group-hover:text-emerald-700 transition line-clamp-2 leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? ($article->title ?: $article->title_kn) : ($article->title_kn ?: $article->title) }}
                    </h3>
                    @if($activeLocale === 'kn' && $article->title_kn && $article->title)
                        <div class="text-xs text-stone-400 font-medium mt-0.5 line-clamp-1 font-sans">
                            {{ $article->title }}
                        </div>
                    @elseif($activeLocale === 'en' && $article->title_kn)
                        <div class="text-xs text-stone-400 font-medium mt-0.5 line-clamp-1 font-kannada">
                            {{ $article->title_kn }}
                        </div>
                    @endif

                    <p class="text-xs text-stone-600 mt-3 line-clamp-3 leading-relaxed flex-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? ($article->summary ?: $article->summary_kn) : ($article->summary_kn ?: $article->summary) }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <div class="text-[11px] text-stone-400 font-medium">
                            @if($article->source_name)
                                <span>{{ $activeLocale === 'en' ? 'Source: ' . $article->source_name : 'ಮೂಲ: ' . $article->source_name }}</span>
                            @endif
                        </div>
                        <a href="{{ route('farmer.news.show', $article->slug) }}" class="font-bold text-emerald-700 hover:text-emerald-800 transition">
                            {{ $activeLocale === 'en' ? 'Read full article →' : 'ಪೂರ್ಣ ವಿವರ ಓದಿ →' }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $news->links() }}
        </div>
    @endif

</div>
@endsection
