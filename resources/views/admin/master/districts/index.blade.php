@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">Karnataka Districts</h2>
            <p class="text-xs text-slate-400 mt-0.5">Manage administrative districts, taluk mappings, and geographic centroids</p>
        </div>

        <a href="{{ route('admin.districts.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md shadow-emerald-950/40 transition active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add District</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.districts.index') }}" class="flex items-center gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search by district name, Kannada name, or code..." 
                       class="w-full pl-10 pr-4 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition cursor-pointer">
                Filter
            </button>
            @if($search)
                <a href="{{ route('admin.districts.index') }}" class="px-3 py-2 text-xs text-slate-400 hover:text-white transition">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Districts Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/40 text-slate-400 uppercase tracking-wider font-semibold">
                        <th class="py-3 px-4">District</th>
                        <th class="py-3 px-4">Code</th>
                        <th class="py-3 px-4">Centroid (Lat, Lon)</th>
                        <th class="py-3 px-4">Taluks</th>
                        <th class="py-3 px-4">Mandis</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($districts as $district)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-white text-sm">{{ $district->name }}</div>
                                @if($district->name_kn)
                                    <div class="text-[11px] text-emerald-400 font-kannada font-medium">{{ $district->name_kn }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-300">{{ $district->code ?? '—' }}</td>
                            <td class="py-3 px-4 text-slate-400 font-mono">
                                @if($district->latitude && $district->longitude)
                                    <span>{{ number_format($district->latitude, 4) }}, {{ number_format($district->longitude, 4) }}</span>
                                @else
                                    <span class="text-slate-600">Not set</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-lg bg-slate-800 text-slate-300 font-semibold">{{ $district->taluks_count }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-lg bg-emerald-950 border border-emerald-800/40 text-emerald-400 font-semibold">{{ $district->markets_count }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('admin.districts.toggle', $district) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider transition cursor-pointer {{ $district->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-700/50 hover:bg-emerald-900' : 'bg-rose-950 text-rose-300 border border-rose-700/50 hover:bg-rose-900' }}">
                                        {{ $district->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('admin.districts.edit', $district) }}" 
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
                                No districts matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($districts->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/20">
                {{ $districts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
