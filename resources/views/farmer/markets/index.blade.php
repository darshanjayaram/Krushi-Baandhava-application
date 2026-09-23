@extends('layouts.farmer')

@section('title', 'ಕರ್ನಾಟಕ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳು — APMC Mandis Directory')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-1">
                <span>🏛️ ಕೃಷಿ ಉತ್ಪನ್ನ ಮಾರುಕಟ್ಟೆ ಸಮಿತಿಗಳು</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight">
                ಕರ್ನಾಟಕ APMC ಮಂಡಿಗಳು (Mandis Directory)
            </h1>
            <p class="text-xs sm:text-sm text-stone-500">
                ರಾಜ್ಯದ ನೋಂದಾಯಿತ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆ ಕೇಂದ್ರಗಳು ಹಾಗೂ ಇಂದಿನ ಆವಕ ಮತ್ತು ವಹಿವಾಟು ದರಗಳು.
            </p>
        </div>

        <a href="{{ route('home') }}" class="self-start sm:self-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-stone-200 text-xs font-bold text-stone-700 hover:bg-stone-50 transition shadow-xs">
            <span>&larr; ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ</span>
        </a>
    </div>

    <!-- Search & District Filter -->
    <div class="bg-white p-3 rounded-2xl border border-stone-200/90 shadow-xs space-y-3">
        <!-- Search bar -->
        <form method="GET" action="{{ route('farmer.markets.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="district" value="{{ $districtId }}">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="ಮಾರುಕಟ್ಟೆ ಹೆಸರು ಅಥವಾ ಕೋಡ್ ಹುಡುಕಿ (Search Mandi name, e.g. Shimoga, ಸಾಗರ, MKT-SHI)..."
                       class="w-full text-xs sm:text-sm pl-9 pr-10 py-2.5 bg-stone-50 border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition">
                @if($search)
                    <a href="{{ route('farmer.markets.index', ['district' => $districtId]) }}" class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-600 text-sm font-bold">
                        ✕
                    </a>
                @endif
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition shrink-0">
                ಹುಡುಕಿ
            </button>
        </form>

        <!-- District Filter Chips -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
            <a href="{{ route('farmer.markets.index', ['search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition shadow-xs {{ !$districtId ? 'bg-emerald-800 text-white ring-2 ring-emerald-600/30' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                ಎಲ್ಲಾ ಜಿಲ್ಲೆಗಳು (All)
            </a>
            @foreach($districts as $d)
                <a href="{{ route('farmer.markets.index', ['district' => $d->id, 'search' => $search]) }}"
                   class="px-3.5 py-1.5 rounded-full font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5 {{ $districtId == $d->id ? 'bg-emerald-800 text-white ring-2 ring-emerald-600/30' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    <span>{{ $d->name }}</span>
                    @if($d->name_kn)
                        <span class="font-kannada font-normal opacity-90">({{ $d->name_kn }})</span>
                    @endif
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $districtId == $d->id ? 'bg-white/20 text-white' : 'bg-white text-stone-500' }}">
                        {{ $d->markets_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Mandis Directory Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($markets as $market)
            @php
                $traded = $tradedCountByMarket[$market->id] ?? null;
            @endphp
            <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
                <div>
                    <!-- Top header -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-800 flex items-center justify-center font-black text-lg shadow-inner group-hover:scale-105 transition shrink-0">
                                🏛️
                            </div>
                            <div>
                                <h2 class="font-black text-stone-900 text-base leading-snug">
                                    <a href="{{ route('farmer.markets.show', $market->code) }}" class="hover:text-emerald-700 transition">
                                        {{ $market->name }} APMC
                                    </a>
                                </h2>
                                @if($market->name_kn)
                                    <span class="text-xs font-semibold text-stone-600 font-kannada">
                                        {{ $market->name_kn }} ಎಪಿಎಂಸಿ
                                    </span>
                                @endif
                            </div>
                        </div>

                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md bg-stone-100 text-stone-600">
                            {{ $market->code }}
                        </span>
                    </div>

                    <!-- Location info -->
                    <div class="mt-3.5 space-y-1 text-xs text-stone-500">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-stone-700">ಜಿಲ್ಲೆ:</span>
                            <span>{{ $market->district ? $market->district->name : 'Karnataka' }}</span>
                            @if($market->taluk)
                                <span class="text-stone-300">•</span>
                                <span>ತಾಲೂಕು: {{ $market->taluk->name }}</span>
                            @endif
                        </div>
                        @if($market->address)
                            <div class="text-[11px] text-stone-400 truncate">
                                {{ $market->address }}
                            </div>
                        @endif
                    </div>

                    <!-- Traded Commodities Today Badge -->
                    <div class="mt-3 pt-3 border-t border-stone-100 flex items-center justify-between text-xs">
                        <span class="text-stone-400">ಇಂದಿನ ವಹಿವಾಟು:</span>
                        @if($traded && $traded->crop_count > 0)
                            <span class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full">
                                {{ $traded->crop_count }} ಬೆಳೆಗಳ ದರ ಲಭ್ಯ
                            </span>
                        @else
                            <span class="text-stone-400">ದರ ಮಾಹಿತಿ ನಿರೀಕ್ಷೆಯಲ್ಲಿದೆ</span>
                        @endif
                    </div>
                </div>

                <!-- Footer Action Button -->
                <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between">
                    <span class="text-[11px] text-stone-400 font-medium capitalize">{{ str_replace('_', ' ', $market->market_type ?? 'Principal') }}</span>
                    <a href="{{ route('farmer.markets.show', $market->code) }}" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition shadow-xs">
                        <span>ಇಂದಿನ ದರಗಳು ನೋಡಿ</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl p-8 text-center border border-stone-200/80 shadow-xs space-y-2">
                <div class="text-3xl">🏛️</div>
                <div class="font-extrabold text-stone-800 text-base">ಯಾವುದೇ ಮಾರುಕಟ್ಟೆಗಳು ಕಂಡುಬಂದಿಲ್ಲ</div>
                <p class="text-xs text-stone-500">ದಯವಿಟ್ಟು ಹುಡುಕಾಟ ಪದವನ್ನು ಬದಲಾಯಿಸಿ ಅಥವಾ ಬೇರೆ ಜಿಲ್ಲೆ ಆಯ್ಕೆಮಾಡಿ.</p>
                <div class="pt-2">
                    <a href="{{ route('farmer.markets.index') }}" class="px-4 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition">
                        ಎಲ್ಲಾ ಮಂಡಿಗಳನ್ನು ತೋರಿಸಿ
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    @if($markets->hasPages())
        <div class="mt-4">
            {{ $markets->links() }}
        </div>
    @endif

</div>
@endsection
