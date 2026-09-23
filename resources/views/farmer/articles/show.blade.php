@extends('layouts.farmer')

@section('title', ($article->title_kn ?: $article->title) . ' — ಕೃಷಿ ಮಾರ್ಗದರ್ಶಿ')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Back Button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('farmer.articles.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs">
            <span>&larr; ಎಲ್ಲಾ ಲೇಖನಗಳಿಗೆ ಹಿಂತಿರುಗಿ</span>
        </a>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800">
                {{ $article->category_label_kn }}
            </span>
            @if($article->crop)
                <a href="{{ route('farmer.crops.show', $article->crop->slug) }}" class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-900 hover:bg-amber-200 transition">
                    🌾 {{ $article->crop->kannada_name ?: $article->crop->name }}
                </a>
            @endif
        </div>
    </div>

    <!-- Article Content -->
    <article class="bg-white rounded-3xl border border-stone-200 p-6 sm:p-10 shadow-xs space-y-6">
        
        <header class="space-y-3 border-b border-stone-100 pb-5">
            <div class="flex flex-wrap items-center gap-3 text-xs text-stone-500 font-semibold">
                @if($article->published_at)
                    <span>📅 {{ $article->published_at->format('d F Y') }}</span>
                @endif
                @if($article->author_name)
                    <span>• ಲೇಖಕರು: {{ $article->author_name }}</span>
                @endif
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 leading-tight">
                {{ $article->title_kn ?: $article->title }}
            </h1>

            @if($article->title_kn && $article->title)
                <p class="text-sm font-semibold text-stone-400">
                    {{ $article->title }}
                </p>
            @endif
        </header>

        <!-- Summary Callout -->
        @if($article->summary_kn || $article->summary)
            <div class="p-4 rounded-2xl bg-emerald-50/70 border-l-4 border-emerald-600 text-emerald-950 text-sm sm:text-base font-semibold leading-relaxed">
                {{ $article->summary_kn ?: $article->summary }}
            </div>
        @endif

        <!-- Main Body Content -->
        <div class="prose max-w-none text-stone-800 text-sm sm:text-base leading-relaxed space-y-4 font-normal">
            @if($article->body_kn)
                <div class="space-y-4">
                    {!! nl2br(e($article->body_kn)) !!}
                </div>
            @endif

            @if($article->body && (!$article->body_kn || $article->body !== $article->body_kn))
                <div class="mt-8 pt-6 border-t border-stone-100 text-stone-700 text-sm space-y-3">
                    <div class="text-xs font-bold text-stone-400 uppercase tracking-wider">English Guide / Content</div>
                    {!! nl2br(e($article->body)) !!}
                </div>
            @endif
        </div>

    </article>

    <!-- Related Articles -->
    @if($relatedArticles->isNotEmpty())
        <div class="pt-4 space-y-3">
            <h3 class="text-base font-extrabold text-stone-900">ಸಂಬಂಧಿತ ಲೇಖನಗಳು & ಕೈಪಿಡಿ (Related Guides)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($relatedArticles as $rel)
                    <a href="{{ route('farmer.articles.show', $rel->slug) }}" class="p-4 bg-white rounded-xl border border-stone-200 hover:border-emerald-500 hover:shadow-xs transition block">
                        <div class="text-xs font-bold text-stone-900 line-clamp-2">
                            {{ $rel->title_kn ?: $rel->title }}
                        </div>
                        <div class="text-[11px] text-stone-500 mt-1 line-clamp-2">
                            {{ $rel->summary_kn ?: $rel->summary }}
                        </div>
                        <div class="text-[11px] font-bold text-emerald-700 mt-2">
                            ಓದಿ &rarr;
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
