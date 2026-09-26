@extends('layouts.farmer')

@section('title', (app()->getLocale() === 'en' ? ($scheme->title ?: $scheme->title_kn) : ($scheme->title_kn ?: $scheme->title)) . ' — ' . (app()->getLocale() === 'en' ? 'Government Scheme Details' : 'ಸರ್ಕಾರಿ ಯೋಜನೆ ವಿವರಗಳು'))

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Back & Breadcrumb -->
    <div class="flex items-center justify-between {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
        <a href="{{ route('farmer.schemes.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs">
            <span>&larr; {{ $activeLocale === 'en' ? 'Back to Schemes' : 'ಯೋಜನೆಗಳ ಪಟ್ಟಿಗೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
            {{ $activeLocale === 'en' ? ($scheme->category_label_en ?? ucfirst($scheme->category)) : $scheme->category_label_kn }}
        </span>
    </div>

    <!-- Scheme Header Card -->
    <div class="bg-white rounded-3xl border border-stone-200 p-6 sm:p-8 shadow-xs">
        <div class="space-y-2">
            @if($scheme->sponsoring_agency)
                <div class="text-xs font-extrabold text-emerald-700 uppercase tracking-wider flex items-center gap-1 font-sans">
                    <span>🏛️</span>
                    <span>{{ $scheme->sponsoring_agency }}</span>
                </div>
            @endif

            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? ($scheme->title ?: $scheme->title_kn) : ($scheme->title_kn ?: $scheme->title) }}
            </h1>

            @if($activeLocale === 'kn' && $scheme->title_kn && $scheme->title)
                <p class="text-sm font-semibold text-stone-400 font-sans">
                    {{ $scheme->title }}
                </p>
            @elseif($activeLocale === 'en' && $scheme->title_kn)
                <p class="text-sm font-semibold text-stone-400 font-kannada">
                    {{ $scheme->title_kn }}
                </p>
            @endif
        </div>

        @if($scheme->summary_kn || $scheme->summary)
            <div class="mt-4 pt-4 border-t border-stone-100 text-sm sm:text-base text-stone-700 leading-relaxed font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? ($scheme->summary ?: $scheme->summary_kn) : ($scheme->summary_kn ?: $scheme->summary) }}
            </div>
        @endif

        @if($scheme->official_url)
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ $scheme->official_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs sm:text-sm shadow-sm transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>{{ $activeLocale === 'en' ? 'Apply on Official Portal' : 'ಅಧಿಕೃತ ಪೋರ್ಟಲ್‌ನಲ್ಲಿ ಅರ್ಜಿ ಸಲ್ಲಿಸಿ (Official Apply Portal)' }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                </a>
            </div>
        @endif
    </div>

    <!-- Key Details Section (Grid of 2 columns) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        
        <!-- Benefits Card -->
        @if($scheme->benefits || $scheme->benefits_kn)
            <div class="bg-gradient-to-br from-amber-50 to-orange-50/50 rounded-2xl border border-amber-200/80 p-5 shadow-xs">
                <div class="flex items-center gap-2 text-amber-900 font-extrabold text-sm mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="text-lg">💰</span>
                    <span>{{ $activeLocale === 'en' ? 'Scheme Benefits & Subsidies' : 'ಯೋಜನೆಯ ಸೌಲಭ್ಯಗಳು & ಸಬ್ಸಿಡಿ (Benefits)' }}</span>
                </div>
                <div class="text-xs sm:text-sm text-stone-800 whitespace-pre-line leading-relaxed font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {!! nl2br(e($activeLocale === 'en' ? ($scheme->benefits ?: $scheme->benefits_kn) : ($scheme->benefits_kn ?: $scheme->benefits))) !!}
                </div>
            </div>
        @endif

        <!-- Eligibility Card -->
        @if($scheme->eligibility || $scheme->eligibility_kn)
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50/50 rounded-2xl border border-emerald-200/80 p-5 shadow-xs">
                <div class="flex items-center gap-2 text-emerald-900 font-extrabold text-sm mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="text-lg">✅</span>
                    <span>{{ $activeLocale === 'en' ? 'Eligibility Criteria' : 'ಅರ್ಹತೆಯ ಮಾನದಂಡಗಳು (Eligibility Criteria)' }}</span>
                </div>
                <div class="text-xs sm:text-sm text-stone-800 whitespace-pre-line leading-relaxed font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {!! nl2br(e($activeLocale === 'en' ? ($scheme->eligibility ?: $scheme->eligibility_kn) : ($scheme->eligibility_kn ?: $scheme->eligibility))) !!}
                </div>
            </div>
        @endif

        <!-- Documents Required Card -->
        @if($scheme->documents_required || $scheme->documents_required_kn)
            <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-xs">
                <div class="flex items-center gap-2 text-stone-900 font-extrabold text-sm mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="text-lg">📄</span>
                    <span>{{ $activeLocale === 'en' ? 'Required Documents' : 'ಅಗತ್ಯವಿರುವ ದಾಖಲೆಗಳು (Required Documents)' }}</span>
                </div>
                <div class="text-xs sm:text-sm text-stone-700 whitespace-pre-line leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {!! nl2br(e($activeLocale === 'en' ? ($scheme->documents_required ?: $scheme->documents_required_kn) : ($scheme->documents_required_kn ?: $scheme->documents_required))) !!}
                </div>
            </div>
        @endif

        <!-- How to Apply Card -->
        @if($scheme->how_to_apply || $scheme->how_to_apply_kn)
            <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-xs">
                <div class="flex items-center gap-2 text-stone-900 font-extrabold text-sm mb-3 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="text-lg">📝</span>
                    <span>{{ $activeLocale === 'en' ? 'How to Apply' : 'ಅರ್ಜಿ ಸಲ್ಲಿಸುವ ವಿಧಾನ (How to Apply)' }}</span>
                </div>
                <div class="text-xs sm:text-sm text-stone-700 whitespace-pre-line leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {!! nl2br(e($activeLocale === 'en' ? ($scheme->how_to_apply ?: $scheme->how_to_apply_kn) : ($scheme->how_to_apply_kn ?: $scheme->how_to_apply))) !!}
                </div>
            </div>
        @endif

    </div>

    <!-- Related Schemes -->
    @if($relatedSchemes->isNotEmpty())
        <div class="pt-6 border-t border-stone-200 space-y-4">
            <h3 class="text-base font-extrabold text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Related Schemes' : 'ಇತರೆ ಸಂಬಂಧಿತ ಯೋಜನೆಗಳು (Related Schemes)' }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($relatedSchemes as $rel)
                    <a href="{{ route('farmer.schemes.show', $rel->slug) }}" class="p-4 bg-white rounded-xl border border-stone-200 hover:border-emerald-500 hover:shadow-xs transition block">
                        <div class="text-xs font-bold text-stone-900 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? ($rel->title ?: $rel->title_kn) : ($rel->title_kn ?: $rel->title) }}
                        </div>
                        <div class="text-[11px] text-stone-500 mt-1 line-clamp-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? ($rel->summary ?: $rel->summary_kn) : ($rel->summary_kn ?: $rel->summary) }}
                        </div>
                        <div class="text-[11px] font-bold text-emerald-700 mt-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'View Details →' : 'ವಿವರ ನೋಡಿ →' }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
