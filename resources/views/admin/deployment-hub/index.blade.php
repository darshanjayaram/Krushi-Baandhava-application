@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span class="text-emerald-400">🚀</span>
                <span>Deployment & APMC Discovery Hub</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Zero-Manual-Entry engine: Auto-populate Karnataka APMC mandis, crop commodities, and source aliases directly from live government feeds.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.datasources.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold border border-slate-700 transition">
                Data Sources & APIs
            </a>
            <a href="{{ route('admin.markets.index') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-600/30 transition">
                View Mandis
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Districts</div>
            <div class="text-2xl font-black text-emerald-400">{{ $districtsCount }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Karnataka coverage: {{ number_format(($districtsCount / 31) * 100, 0) }}%</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">APMC Mandis</div>
            <div class="text-2xl font-black text-cyan-400">{{ $marketsCount }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Active markets registered</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Canonical Crops</div>
            <div class="text-2xl font-black text-amber-400">{{ $cropsCount }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Commodities monitored</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Crop Varieties</div>
            <div class="text-2xl font-black text-teal-400">{{ $varietiesCount }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Rashi, Bette, Gorabal, etc.</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Price Records</div>
            <div class="text-2xl font-black text-purple-400">{{ number_format($pricesCount) }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Daily rates logged</div>
        </div>
    </div>

    <!-- Automated Discovery Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Mandi Discovery Card -->
        <div class="bg-slate-900/90 border border-cyan-500/30 rounded-3xl p-6 relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div>
                <div class="w-12 h-12 rounded-2xl bg-cyan-950 border border-cyan-500/40 flex items-center justify-center text-2xl mb-4">
                    🏢
                </div>
                <h2 class="text-base font-bold text-white mb-1.5">Auto-Discover Mandis</h2>
                <p class="text-xs text-slate-400 leading-relaxed mb-6">
                    Queries the live government feed to find newly reporting APMC mandis and auto-creates district and mandi records without duplicates.
                </p>
            </div>
            <form action="{{ route('admin.deployment-hub.discover-mandis') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs shadow-lg shadow-cyan-600/30 transition flex items-center justify-center gap-1.5">
                    <span>🔍</span>
                    <span>Scan for Mandis</span>
                </button>
            </form>
        </div>

        <!-- Crop Discovery Card -->
        <div class="bg-slate-900/90 border border-amber-500/30 rounded-3xl p-6 relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div>
                <div class="w-12 h-12 rounded-2xl bg-amber-950 border border-amber-500/40 flex items-center justify-center text-2xl mb-4">
                    🌱
                </div>
                <h2 class="text-base font-bold text-white mb-1.5">Auto-Discover Crops</h2>
                <p class="text-xs text-slate-400 leading-relaxed mb-6">
                    Detects any new crops or varieties present in incoming data feeds and registers them with automated name cleanup, slugging, and alias mappings.
                </p>
            </div>
            <form action="{{ route('admin.deployment-hub.discover-crops') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg shadow-amber-600/30 transition flex items-center justify-center gap-1.5">
                    <span>🌱</span>
                    <span>Scan for Crops</span>
                </button>
            </form>
        </div>

        <!-- Standard Karnataka Directory Re-Sync -->
        <div class="bg-slate-900/90 border border-emerald-500/30 rounded-3xl p-6 relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-950 border border-emerald-500/40 flex items-center justify-center text-2xl mb-4">
                    ⚡
                </div>
                <h2 class="text-base font-bold text-white mb-1.5">Sync Directory</h2>
                <p class="text-xs text-slate-400 leading-relaxed mb-6">
                    Ensures all 31 Karnataka districts and 68 official APMC mandis are present. Guaranteed zero duplicate records.
                </p>
            </div>
            <form action="{{ route('admin.deployment-hub.import-standard-master') }}" method="POST" onsubmit="return confirm('Synchronize Karnataka master directories?');">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-1.5">
                    <span>⚡</span>
                    <span>Sync Directory</span>
                </button>
            </form>
        </div>

        <!-- Live Price Ingestion Trigger Card -->
        <div class="bg-slate-900/90 border border-purple-500/30 rounded-3xl p-6 relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div>
                <div class="w-12 h-12 rounded-2xl bg-purple-950 border border-purple-500/40 flex items-center justify-center text-2xl mb-4">
                    📡
                </div>
                <h2 class="text-base font-bold text-white mb-1.5">Sync Live Rates</h2>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    Triggers immediate price synchronization from selected API provider to update all Karnataka mandi rates.
                </p>
            </div>
            <form action="{{ route('admin.deployment-hub.sync-prices') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <select name="source" class="w-full px-2.5 py-1.5 bg-slate-800 border border-slate-700 rounded-lg text-xs text-white outline-none focus:ring-1 focus:ring-purple-500">
                        <option value="data_gov_mandi">data.gov.in (OGD India)</option>
                        <option value="ceda_agmarknet">CEDA Agmarknet (Ashoka Univ)</option>
                    </select>
                </div>
                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 transition flex items-center justify-center gap-1.5">
                    <span>🚀</span>
                    <span>Sync Rates Now</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Active Records Preview Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Mandis Preview -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>🏢</span>
                    <span>Recent Active APMC Mandis</span>
                </h3>
                <a href="{{ route('admin.markets.index') }}" class="text-xs text-emerald-400 hover:underline">View All ({{ $marketsCount }}) →</a>
            </div>
            <div class="divide-y divide-slate-800 text-xs">
                @forelse($recentMandis as $mandi)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-white">{{ $mandi->name }}</span>
                            <span class="text-slate-400 text-[11px] block">{{ $mandi->district?->name ?? 'Karnataka' }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono text-[10px]">
                            {{ $mandi->code }}
                        </span>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-500">No mandis found. Run discovery above.</div>
                @endforelse
            </div>
        </div>

        <!-- Crops Preview -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>🌱</span>
                    <span>Monitored Crops & Varieties</span>
                </h3>
                <a href="{{ route('admin.crops.index') }}" class="text-xs text-amber-400 hover:underline">View All ({{ $cropsCount }}) →</a>
            </div>
            <div class="divide-y divide-slate-800 text-xs">
                @forelse($recentCrops as $crop)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-white">{{ $crop->name }}</span>
                            <span class="text-slate-400 text-[11px] block">{{ $crop->varieties->count() }} Varieties ({{ $crop->varieties->take(3)->pluck('name')->implode(', ') }})</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-emerald-400 font-mono text-[10px]">
                            {{ $crop->code }}
                        </span>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-500">No crops found. Run discovery above.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
