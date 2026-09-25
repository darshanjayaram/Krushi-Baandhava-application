@extends('layouts.farmer')

@section('title', 'ಕರ್ನಾಟಕದ ಪ್ರಮುಖ ಕೃಷಿ ಬೆಳೆಗಳು — Commodity Directory')

@section('content')
@php
    $activeLocale = app()->getLocale();
@endphp
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-1 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span>🌾 {{ $activeLocale === 'en' ? 'Crop Commodity Information' : 'ಕೃಷಿ ಉತ್ಪನ್ನಗಳ ಮಾಹಿತಿ' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Karnataka Crops Directory' : 'ಕರ್ನಾಟಕದ ಬೆಳೆಗಳು' }}
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Statewide commercial, cereal, spice, and plantation crops with live mandi price intelligence.' : 'ರಾಜ್ಯದ ಪ್ರಮುಖ ವಾಣಿಜ್ಯ, ಧಾನ್ಯ, ಮಸಾಲೆ ಮತ್ತು ತೋಟಗಾರಿಕಾ ಬೆಳೆಗಳ ವಿವರ ಹಾಗೂ ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು.' }}
            </p>
        </div>

        <a href="{{ route('home') }}" class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span>&larr; {{ $activeLocale === 'en' ? 'Back to Home' : 'ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>
    </div>

    <!-- Search & Category Filters -->
    <div class="bg-white p-3 rounded-2xl border border-stone-200/90 shadow-xs space-y-3">
        <!-- Search bar -->
        <form method="GET" action="{{ route('farmer.crops.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="category" value="{{ $categorySlug }}">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="{{ $activeLocale === 'en' ? 'Search crop name (e.g. Arecanut, Coffee, Paddy)...' : 'ಬೆಳೆ ಹೆಸರು ಹುಡುಕಿ (ಉದಾ: ಅಡಿಕೆ, ಕಾಫಿ, ಭತ್ತ)...' }}"
                       class="w-full text-xs sm:text-sm pl-9 pr-10 py-2.5 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                @if($search)
                    <a href="{{ route('farmer.crops.index', ['category' => $categorySlug]) }}" class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-600 text-sm font-bold">
                        ✕
                    </a>
                @endif
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition shrink-0 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                {{ $activeLocale === 'en' ? 'Search' : 'ಹುಡುಕಿ' }}
            </button>
        </form>

        <!-- Category Pill Chips -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
            <a href="{{ route('farmer.crops.index', ['search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition shadow-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ !$categorySlug ? 'bg-emerald-800 text-white ring-2 ring-emerald-600/30' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                {{ $activeLocale === 'en' ? 'All Crops' : 'ಎಲ್ಲಾ ಬೆಳೆಗಳು' }}
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('farmer.crops.index', ['category' => $cat->slug, 'search' => $search]) }}"
                   class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} {{ $categorySlug === $cat->slug ? 'bg-emerald-800 text-white ring-2 ring-emerald-600/30' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    <span>{{ $activeLocale === 'en' ? $cat->name : ($cat->name_kn ?: $cat->name) }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $categorySlug === $cat->slug ? 'bg-white/20 text-white' : 'bg-white text-stone-500' }}">
                        {{ $cat->crops_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Crops Directory Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($crops as $crop)
            @php
                $priceInfo = $latestPricesByCrop[$crop->id] ?? null;
            @endphp
            <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
                <div>
                    <!-- Top header: Crop Name & Category -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $crop->photo_url }}" 
                                 alt="{{ $crop->name }}" 
                                 class="w-14 h-14 rounded-2xl object-cover shrink-0 shadow-sm border border-stone-200 group-hover:scale-105 transition duration-300">
                            <div>
                                <h2 class="font-black text-stone-900 text-base leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    <a href="{{ route('farmer.crops.show', $crop->slug) }}" class="hover:text-[#1C5A2C] transition">
                                        {{ $activeLocale === 'en' ? $crop->name : ($crop->name_kn ?: $crop->name) }}
                                    </a>
                                </h2>
                            </div>
                        </div>

                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-stone-100 text-stone-600 font-sans">
                            {{ $crop->category ? ($activeLocale === 'en' ? $crop->category->name : ($crop->category->name_kn ?: $crop->category->name)) : 'Crop' }}
                        </span>
                    </div>

                    <!-- Varieties List Preview -->
                    <div class="mt-3 text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span class="font-bold text-stone-700">{{ $activeLocale === 'en' ? 'Varieties:' : 'ತಳಿಗಳು:' }}</span>
                        @if($crop->varieties->isNotEmpty())
                            <span>{{ $crop->varieties->map(fn($v) => $v->displayName($activeLocale))->take(3)->implode(', ') }}</span>
                            @if($crop->varieties->count() > 3)
                                <span class="text-[#1C5A2C] font-semibold">+{{ $crop->varieties->count() - 3 }} {{ $activeLocale === 'en' ? 'more' : 'ಇನ್ನಷ್ಟು' }}</span>
                            @endif
                        @else
                            <span class="text-stone-400">{{ $activeLocale === 'en' ? 'Standard / All' : 'ಸಾಮಾನ್ಯ / ಎಲ್ಲಾ' }}</span>
                        @endif
                    </div>

                    <!-- Price Snapshot if available -->
                    @if($priceInfo)
                        <div class="mt-3.5 p-3 rounded-xl bg-stone-50 border border-stone-100">
                            <div class="text-[10px] uppercase font-bold text-stone-400 flex items-center justify-between {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                <span>{{ $activeLocale === 'en' ? 'State Price Range' : 'ಮಾದರಿ ದರ ಶ್ರೇಣಿ' }}</span>
                                <span class="text-[#1C5A2C] font-bold lowercase">{{ $priceInfo->mandi_count }} {{ $activeLocale === 'en' ? 'mandis' : 'ಮಂಡಿಗಳು' }}</span>
                            </div>
                            <div class="text-lg font-black text-[#1C5A2C] mt-0.5 tracking-tight font-sans">
                                ₹{{ number_format($priceInfo->min_modal, 0) }} - ₹{{ number_format($priceInfo->max_modal, 0) }}
                                <span class="text-xs font-normal text-stone-400">/ {{ $crop->standard_unit ?? 'Qtl' }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer Action Button -->
                <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between">
                    <span class="text-[11px] text-stone-400 font-medium">ಮಾನಕ: {{ $crop->standard_unit ?? 'Quintal' }}</span>
                    <a href="{{ route('farmer.crops.show', $crop->slug) }}" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white text-xs font-bold transition shadow-xs">
                        <span>ದರ ಹೋಲಿಕೆ ವೀಕ್ಷಿಸಿ</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl p-8 text-center border border-stone-200/80 shadow-xs space-y-2">
                <div class="text-3xl">🌾</div>
                <div class="font-extrabold text-stone-800 text-base">ಯಾವುದೇ ಬೆಳೆಗಳು ಕಂಡುಬಂದಿಲ್ಲ</div>
                <p class="text-xs text-stone-500">ದಯವಿಟ್ಟು ಹುಡುಕಾಟ ಪದವನ್ನು ಬದಲಾಯಿಸಿ ಅಥವಾ ಫಿಲ್ಟರ್ ತೆರವುಗೊಳಿಸಿ.</p>
                <div class="pt-2">
                    <a href="{{ route('farmer.crops.index') }}" class="px-4 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition">
                        ಎಲ್ಲಾ ಬೆಳೆಗಳನ್ನು ತೋರಿಸಿ
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    @if($crops->hasPages())
        <div class="mt-4">
            {{ $crops->links() }}
        </div>
    @endif

</div>
@endsection
