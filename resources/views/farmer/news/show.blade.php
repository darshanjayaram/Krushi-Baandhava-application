@extends('layouts.farmer')

@section('title', ($news->title_kn ?: $news->title) . ' — ಕೃಷಿ ಸುದ್ದಿ')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Back Button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('farmer.news.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs">
            <span>&larr; ಎಲ್ಲಾ ಸುದ್ದಿಗಳಿಗೆ ಹಿಂತಿರುಗಿ</span>
        </a>
        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase {{ $news->priority_badge_class }}">
            {{ $news->priority_label }}
        </span>
    </div>

    <!-- Article Content -->
    <article class="bg-white rounded-3xl border border-stone-200 p-6 sm:p-10 shadow-xs space-y-6">
        
        <header class="space-y-3 border-b border-stone-100 pb-5">
            <div class="flex flex-wrap items-center gap-3 text-xs text-stone-500 font-semibold">
                @if($news->published_at)
                    <span>📅 {{ $news->published_at->format('d F Y, h:i A') }}</span>
                @endif
                @if($news->source_name)
                    <span>• ಮೂಲ: {{ $news->source_name }}</span>
                @endif
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 leading-tight">
                {{ $news->title_kn ?: $news->title }}
            </h1>

            @if($news->title_kn && $news->title)
                <p class="text-sm font-semibold text-stone-400">
                    {{ $news->title }}
                </p>
            @endif
        </header>

        <!-- Summary Callout -->
        @if($news->summary_kn || $news->summary)
            <div class="p-4 rounded-2xl bg-stone-50 border-l-4 border-emerald-600 text-stone-700 text-sm sm:text-base font-semibold leading-relaxed">
                {{ $news->summary_kn ?: $news->summary }}
            </div>
        @endif

        <!-- Main Body -->
        <div class="prose max-w-none text-stone-800 text-sm sm:text-base leading-relaxed space-y-4 font-normal">
            @if($news->body_kn)
                <div class="space-y-3">
                    {!! nl2br(e($news->body_kn)) !!}
                </div>
            @endif

            @if($news->body && (!$news->body_kn || $news->body !== $news->body_kn))
                <div class="mt-6 pt-6 border-t border-stone-100 text-stone-600 text-sm space-y-3">
                    <div class="text-xs font-bold text-stone-400 uppercase tracking-wider">English Summary / Report</div>
                    {!! nl2br(e($news->body)) !!}
                </div>
            @endif
        </div>

        @if($news->source_url)
            <div class="pt-4 border-t border-stone-100 flex items-center justify-between">
                <span class="text-xs text-stone-500">ಮೂಲ ವರದಿ:</span>
                <a href="{{ $news->source_url }}" target="_blank" rel="noopener" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                    <span>ಮೂಲ ವೆಬ್‌ಸೈಟ್‌ಗೆ ಭೇಟಿ ನೀಡಿ</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                </a>
            </div>
        @endif

    </article>

    <!-- Recent News -->
    @if($recentNews->isNotEmpty())
        <div class="pt-4 space-y-3">
            <h3 class="text-base font-extrabold text-stone-900">ಇತ್ತೀಚಿನ ಇತರೆ ಸುದ್ದಿಗಳು (Recent Updates)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($recentNews as $rel)
                    <a href="{{ route('farmer.news.show', $rel->slug) }}" class="p-4 bg-white rounded-xl border border-stone-200 hover:border-emerald-500 hover:shadow-xs transition block">
                        <div class="text-xs font-bold text-stone-900 line-clamp-2">
                            {{ $rel->title_kn ?: $rel->title }}
                        </div>
                        <div class="text-[11px] text-stone-400 mt-1">
                            {{ $rel->published_at ? $rel->published_at->diffForHumans() : '' }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
