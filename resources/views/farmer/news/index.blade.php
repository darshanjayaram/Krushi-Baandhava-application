@extends('layouts.farmer')

@section('title', 'ಕೃಷಿ ಮಾರುಕಟ್ಟೆ ಸುದ್ದಿ & ಅಪ್ಡೇಟ್ಸ್ — Krushi News & Alerts')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold mb-1">
                <span>📰 ತಾಜಾ ಮಾಹಿತಿ & ಸರ್ಕಾರಿ ಆದೇಶಗಳು</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight">
                ಕೃಷಿ ಸುದ್ದಿ & ಸಮಾಚಾರ (Agri News & Alerts)
            </h1>
            <p class="text-xs sm:text-sm text-stone-500">
                ಮಂಡಿ ದರ ಏರಿಳಿತ, ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ, ರೋಗ ಬಾಧೆ ಎಚ್ಚರಿಕೆ ಮತ್ತು ಸರ್ಕಾರದ ಪ್ರಕಟಣೆಗಳು.
            </p>
        </div>

        <a href="{{ route('home') }}" class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs">
            <span>&larr; ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ</span>
        </a>
    </div>

    <!-- Breaking News Ticker / Highlight if present -->
    @if($breakingNews->isNotEmpty())
        <div class="bg-gradient-to-r from-red-600 via-red-500 to-amber-600 rounded-2xl p-4 text-white shadow-sm">
            <div class="flex items-center gap-2 mb-2 font-extrabold text-xs uppercase tracking-wider text-amber-200">
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-200 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
                </span>
                <span>🚨 ಬ್ರೇಕಿಂಗ್ ಕೃಷಿ ಸುದ್ದಿ (Breaking Updates)</span>
            </div>
            <div class="space-y-2">
                @foreach($breakingNews as $item)
                    <div class="flex items-start sm:items-center justify-between gap-2 border-b border-red-400/40 last:border-0 pb-1.5 last:pb-0">
                        <a href="{{ route('farmer.news.show', $item->slug) }}" class="text-xs sm:text-sm font-bold text-white hover:text-amber-100 line-clamp-1 transition">
                            • {{ $item->title_kn ?: $item->title }}
                        </a>
                        <span class="text-[10px] text-red-100 shrink-0 font-medium">
                            {{ $item->published_at ? $item->published_at->diffForHumans() : 'ಇತ್ತೀಚೆಗೆ' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Priority Filter Bar -->
    <div class="bg-white p-3 rounded-2xl border border-stone-200/90 shadow-xs flex items-center gap-2 overflow-x-auto text-xs">
        <a href="{{ route('farmer.news.index') }}" 
           class="px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ empty($priority) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            ಎಲ್ಲಾ ಸುದ್ದಿಗಳು (All News)
        </a>
        <a href="{{ route('farmer.news.index', ['priority' => 'breaking']) }}" 
           class="px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition flex items-center gap-1 {{ $priority === 'breaking' ? 'bg-red-600 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            <span>🚨</span>
            <span>ತುರ್ತು / ಬ್ರೇಕಿಂಗ್ (Breaking)</span>
        </a>
        <a href="{{ route('farmer.news.index', ['priority' => 'high']) }}" 
           class="px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition flex items-center gap-1 {{ $priority === 'high' ? 'bg-amber-600 text-white shadow-xs' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            <span>⚡</span>
            <span>ಪ್ರಮುಖ (High Priority)</span>
        </a>
    </div>

    <!-- News Grid -->
    @if($news->isEmpty())
        <div class="text-center py-16 bg-white rounded-2xl border border-stone-200 shadow-xs">
            <span class="text-4xl">📰</span>
            <h3 class="text-base font-bold text-stone-800 mt-2">ಯಾವುದೇ ಸುದ್ದಿ ಕಂಡುಬಂದಿಲ್ಲ</h3>
            <p class="text-xs text-stone-500 mt-1">ಇನ್ನಷ್ಟು ಮಾಹಿತಿಗಾಗಿ ನಂತರ ಪರಿಶೀಲಿಸಿ.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($news as $article)
                <div class="bg-white rounded-2xl border border-stone-200/90 hover:border-emerald-500 hover:shadow-md transition flex flex-col p-5 group">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase {{ $article->priority_badge_class }}">
                            {{ $article->priority_label }}
                        </span>
                        <span class="text-[11px] font-semibold text-stone-400">
                            {{ $article->published_at ? $article->published_at->format('d M, Y') : '' }}
                        </span>
                    </div>

                    <h3 class="text-base font-extrabold text-stone-900 group-hover:text-emerald-700 transition line-clamp-2 leading-snug">
                        {{ $article->title_kn ?: $article->title }}
                    </h3>
                    @if($article->title_kn && $article->title)
                        <div class="text-xs text-stone-400 font-medium mt-0.5 line-clamp-1">
                            {{ $article->title }}
                        </div>
                    @endif

                    <p class="text-xs text-stone-600 mt-3 line-clamp-3 leading-relaxed flex-1">
                        {{ $article->summary_kn ?: $article->summary }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs">
                        <div class="text-[11px] text-stone-400 font-medium">
                            @if($article->source_name)
                                <span>ಮೂಲ: {{ $article->source_name }}</span>
                            @endif
                        </div>
                        <a href="{{ route('farmer.news.show', $article->slug) }}" class="font-bold text-emerald-700 hover:text-emerald-800 transition">
                            ಪೂರ್ಣ ವಿವರ ಓದಿ &rarr;
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
