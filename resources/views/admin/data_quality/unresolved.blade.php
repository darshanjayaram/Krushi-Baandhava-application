@extends('layouts.admin')

@section('content')
<div class="space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Unresolved Entity & Alias Resolvers</h1>
            <p class="text-sm text-slate-400 mt-0.5">Map unrecognized commodity and mandi names discovered in rejected feeds directly to verified canonical entities</p>
        </div>
        <div>
            <a href="{{ route('admin.data-quality.index') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-xs font-semibold rounded-xl flex items-center gap-2 transition">
                &larr; Back to Data Quality Inspector
            </a>
        </div>
    </div>

    <!-- Overview Banner -->
    <div class="bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-800/40 rounded-2xl p-5 shadow-sm flex items-start gap-4">
        <span class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 text-xl shrink-0">💡</span>
        <div>
            <h2 class="text-sm font-bold text-white">How Entity Resolution & Auto-Reprocess Works</h2>
            <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                When upstream APIs introduce new spelling variations (e.g. <span class="font-mono text-amber-300">"Paddy(Dhan)(Common)"</span> or <span class="font-mono text-amber-300">"Binny Mill (F&V)"</span>), records are held in <span class="text-rose-400 font-semibold">rejected</span> status. Selecting the matching canonical entity below will store a verified mapping rule and immediately reprocess all affected records into active market prices.
            </p>
        </div>
    </div>

    <!-- Resolvers Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Unresolved Commodity Aliases -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base">🌾</span>
                    <h2 class="text-base font-bold text-white">Unmapped Commodity Aliases</h2>
                </div>
                <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-950 text-amber-300 border border-amber-800/40">
                    {{ count($unresolvedCrops) }} Unresolved
                </span>
            </div>

            @if(empty($unresolvedCrops))
                <div class="p-8 text-center bg-slate-900 border border-slate-800 rounded-2xl">
                    <div class="text-3xl mb-2">🎉</div>
                    <div class="text-sm font-bold text-emerald-400">All Commodity Aliases Mapped!</div>
                    <div class="text-xs text-slate-400 mt-1">Every commodity in incoming price feeds maps cleanly to canonical crops.</div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($unresolvedCrops as $cropItem)
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:border-slate-700 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs text-slate-400">Source: <span class="text-slate-300 font-semibold">{{ $cropItem['data_source_name'] }}</span></div>
                                    <div class="text-base font-bold text-white font-mono mt-1 text-amber-300">
                                        "{{ $cropItem['raw_crop_name'] }}"
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800/50">
                                    {{ $cropItem['count'] }} Rejected Record{{ $cropItem['count'] > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <form action="{{ route('admin.unresolved-mappings.resolve-crop') }}" method="POST" class="pt-3 border-t border-slate-800/80 space-y-3">
                                @csrf
                                <input type="hidden" name="data_source_id" value="{{ $cropItem['data_source_id'] }}">
                                <input type="hidden" name="source_crop_name" value="{{ $cropItem['raw_crop_name'] }}">

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                        Associate with Canonical Crop:
                                    </label>
                                    <select name="crop_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                                        <option value="">-- Choose Canonical Crop --</option>
                                        @foreach($canonicalCrops as $c)
                                            <option value="{{ $c->id }}">
                                                {{ $c->name }} ({{ $c->name_kn }}) — {{ $c->category }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save Mapping & Reprocess {{ $cropItem['count'] }} Record{{ $cropItem['count'] > 1 ? 's' : '' }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Unresolved APMC Mandi Aliases -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base">🏛️</span>
                    <h2 class="text-base font-bold text-white">Unmapped APMC Mandi Aliases</h2>
                </div>
                <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-cyan-950 text-cyan-300 border border-cyan-800/40">
                    {{ count($unresolvedMarkets) }} Unresolved
                </span>
            </div>

            @if(empty($unresolvedMarkets))
                <div class="p-8 text-center bg-slate-900 border border-slate-800 rounded-2xl">
                    <div class="text-3xl mb-2">🎉</div>
                    <div class="text-sm font-bold text-emerald-400">All Mandi Aliases Mapped!</div>
                    <div class="text-xs text-slate-400 mt-1">Every market in incoming price feeds maps cleanly to verified Karnataka APMC mandis.</div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($unresolvedMarkets as $mktItem)
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:border-slate-700 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs text-slate-400">
                                        Source: <span class="text-slate-300 font-semibold">{{ $mktItem['data_source_name'] }}</span>
                                        @if($mktItem['raw_district_name'])
                                            • District: <span class="text-slate-300 font-mono">{{ $mktItem['raw_district_name'] }}</span>
                                        @endif
                                    </div>
                                    <div class="text-base font-bold text-white font-mono mt-1 text-cyan-300">
                                        "{{ $mktItem['raw_market_name'] }}"
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800/50">
                                    {{ $mktItem['count'] }} Rejected Record{{ $mktItem['count'] > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <form action="{{ route('admin.unresolved-mappings.resolve-market') }}" method="POST" class="pt-3 border-t border-slate-800/80 space-y-3">
                                @csrf
                                <input type="hidden" name="data_source_id" value="{{ $mktItem['data_source_id'] }}">
                                <input type="hidden" name="source_market_name" value="{{ $mktItem['raw_market_name'] }}">
                                <input type="hidden" name="source_district_name" value="{{ $mktItem['raw_district_name'] ?? '' }}">

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                        Associate with Verified Karnataka Mandi:
                                    </label>
                                    <select name="market_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                                        <option value="">-- Choose Canonical Mandi --</option>
                                        @foreach($canonicalMarkets as $m)
                                            <option value="{{ $m->id }}">
                                                {{ $m->name }} ({{ $m->name_kn }}) — {{ $m->district?->name ?? 'Karnataka' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="submit" class="w-full py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save Mapping & Reprocess {{ $mktItem['count'] }} Record{{ $mktItem['count'] > 1 ? 's' : '' }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
