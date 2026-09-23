@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">APMC Mandis & Markets</h2>
            <p class="text-xs text-slate-400 mt-0.5">Manage wholesale trading yards, sub-markets, and geographical locations</p>
        </div>

        <a href="{{ route('admin.markets.create', ['district_id' => $districtId]) }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md shadow-emerald-950/40 transition active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Register New Mandi</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.markets.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Search -->
            <div class="relative sm:col-span-2">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search by mandi name, Kannada name, or APMC code..." 
                       class="w-full pl-10 pr-4 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <!-- District Filter -->
            <div class="flex items-center gap-2">
                <select name="district_id" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Districts</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}" {{ $districtId == $d->id ? 'selected' : '' }}>
                            {{ $d->name }} ({{ $d->markets()->count() }})
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition cursor-pointer">
                    Apply
                </button>
                @if($search || $districtId)
                    <a href="{{ route('admin.markets.index') }}" class="px-2 text-xs text-slate-400 hover:text-white transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Markets Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/40 text-slate-400 uppercase tracking-wider font-semibold">
                        <th class="py-3 px-4">Mandi / Yard Name</th>
                        <th class="py-3 px-4">District & Taluk</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">APMC Code</th>
                        <th class="py-3 px-4">GPS Pin</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($markets as $market)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-white text-sm">{{ $market->name }}</div>
                                @if($market->name_kn)
                                    <div class="text-[11px] text-emerald-400 font-kannada font-medium">{{ $market->name_kn }}</div>
                                @endif
                                @if($market->address)
                                    <div class="text-[10px] text-slate-500 mt-0.5 line-clamp-1">{{ $market->address }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-200">{{ $market->district->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $market->taluk->name ?? 'Headquarters' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $market->market_type === 'APMC' ? 'bg-cyan-950 text-cyan-300 border border-cyan-800/50' : 'bg-slate-800 text-slate-300' }}">
                                    {{ $market->market_type }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-300">{{ $market->code ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if($market->latitude && $market->longitude)
                                    <a href="https://maps.google.com/?q={{ $market->latitude }},{{ $market->longitude }}" target="_blank" class="inline-flex items-center gap-1 text-slate-300 hover:text-emerald-400 font-mono transition" title="Open in Maps">
                                        <svg class="w-3.5 h-3.5 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ number_format($market->latitude, 3) }}, {{ number_format($market->longitude, 3) }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-600 font-mono">No GPS</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('admin.markets.toggle', $market) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider transition cursor-pointer {{ $market->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-700/50 hover:bg-emerald-900' : 'bg-rose-950 text-rose-300 border border-rose-700/50 hover:bg-rose-900' }}">
                                        {{ $market->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('admin.markets.edit', $market) }}" 
                                   class="inline-flex items-center gap-1 text-slate-300 hover:text-emerald-400 font-semibold transition">
                                    <span>Edit</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                No mandis found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($markets->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/20">
                {{ $markets->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
