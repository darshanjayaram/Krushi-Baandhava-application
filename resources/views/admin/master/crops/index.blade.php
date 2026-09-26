@extends('layouts.admin')

@section('title', 'Agricultural Commodities & Crops')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🌾</span> Agricultural Commodities & Crops
                <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-950/80 text-emerald-300 border border-emerald-800/60 rounded-full font-kannada">ಬೆಳೆಗಳ ಪಟ್ಟಿ</span>
            </h1>
            <p class="text-sm text-slate-400 font-medium">Manage canonical Karnataka commodities, trading units, discovery radius, and cultivar mappings.</p>
        </div>

        <a href="{{ route('admin.crops.create', ['category_id' => $categoryId]) }}" 
           class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Register New Crop</span>
        </a>
    </div>

    <!-- Metrics Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Commodities</span>
            <div class="text-3xl font-extrabold text-white mt-1">{{ $crops->total() }}</div>
            <div class="text-xs font-medium text-emerald-400 mt-1">Cataloged in database</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Major KA Crops</span>
            <div class="text-3xl font-extrabold text-amber-400 mt-1">
                {{ \App\Models\Crop::where('is_major', true)->count() }}
            </div>
            <div class="text-xs font-medium text-slate-400 mt-1">Priority state focus</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Trading</span>
            <div class="text-3xl font-extrabold text-emerald-400 mt-1">
                {{ \App\Models\Crop::where('is_active', true)->count() }}
            </div>
            <div class="text-xs font-medium text-slate-400 mt-1">Active price feeds</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Feed Aliases Mapped</span>
            <div class="text-3xl font-extrabold text-cyan-400 mt-1">
                {{ \App\Models\CropSourceMapping::count() }}
            </div>
            <div class="text-xs font-medium text-slate-400 mt-1">Auto-mapped variety strings</div>
        </div>
    </div>

    <!-- Filters & Search Bar Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.crops.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Search Text -->
            <div class="relative sm:col-span-2">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search by crop name, Kannada name, or scientific name..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white placeholder-slate-500 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
            </div>

            <!-- Category Filter -->
            <div class="flex items-center gap-2">
                <select name="category_id" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition shadow-sm cursor-pointer">
                    Filter
                </button>
                @if($search || $categoryId)
                    <a href="{{ route('admin.crops.index') }}" class="px-3 py-2.5 text-xs font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition" title="Reset Filters">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Crops Table Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-white flex items-center gap-2">
                <span>🌾</span> Registered Commodities ({{ $crops->total() }})
            </h2>
            <div class="text-xs font-semibold text-slate-400">Total Cataloged: {{ $crops->total() }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Crop Name</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Trading Unit</th>
                        <th class="py-3.5 px-4">API Feed Sources & Mapping Status</th>
                        <th class="py-3.5 px-4">Varieties & Grades</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($crops as $crop)
                        <tr class="hover:bg-slate-800/35 transition">
                            <!-- Crop Name -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-10 h-10 shrink-0">
                                        <img src="{{ $crop->photo_url }}" alt="{{ $crop->name }}" class="w-10 h-10 rounded-xl object-cover border border-slate-700 bg-slate-800 shadow-sm" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden')">
                                        <div class="hidden w-10 h-10 rounded-xl bg-emerald-950/80 text-emerald-400 border border-emerald-800/60 font-black text-xs flex items-center justify-center shadow-sm">
                                            {{ substr($crop->name, 0, 2) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-bold text-white text-sm flex items-center gap-1.5">
                                            <span>{{ $crop->name }}</span>
                                            @if($crop->is_major)
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase tracking-wider bg-amber-950/80 text-amber-300 border border-amber-800/60" title="Major Karnataka Commodity">
                                                    ★ Major
                                                </span>
                                            @endif
                                        </div>
                                        @if($crop->name_kn)
                                            <div class="text-[11px] text-emerald-400 font-kannada font-bold mt-0.5">{{ $crop->name_kn }}</div>
                                        @endif
                                        @if($crop->scientific_name)
                                            <div class="text-[10px] text-slate-500 italic mt-0.5">{{ $crop->scientific_name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 border border-slate-700/60 font-bold text-xs">
                                    {{ $crop->category->name }}
                                </span>
                            </td>

                            <!-- Trading Unit -->
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-300 text-xs">
                                {{ $crop->standard_unit }}
                            </td>

                            <!-- API Feed Sources & Mapping Status -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if($crop->isCoffeeBoard())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-950/70 text-amber-300 border border-amber-800/60">
                                                ☕ Coffee Board of India
                                            </span>
                                        @elseif($crop->isCoconutBoard())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-cyan-950/70 text-cyan-300 border border-cyan-800/60">
                                                🥥 Coconut Dev Board
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700/80">
                                                🏛️ data.gov.in / APMC
                                            </span>
                                        @endif

                                        @if($crop->prices_count > 0)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                                                {{ $crop->prices_count }} Active Prices
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md text-[10px] text-slate-500 bg-slate-800/60">
                                                No Prices Yet
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 text-[10px]">
                                        <span class="text-slate-400 font-semibold">{{ $crop->sourceMappings->count() }} feed aliases mapped</span>
                                        <span class="text-slate-600">•</span>
                                        <span class="inline-flex items-center gap-1 font-mono font-bold text-emerald-300 bg-emerald-950/50 px-1.5 py-0.5 rounded border border-emerald-800/50">
                                            <span>📍 Discovery: {{ $crop->market_radius_km == 0 || $crop->market_radius_km >= 500 ? 'All KA' : $crop->market_radius_km . 'km' }}</span>
                                            <span>• {{ $crop->default_market_sort === 'highest_price_first' ? '💰 Top Rate' : '📍 Nearest' }}</span>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Varieties & Grades -->
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.crops.edit', $crop) }}?tab=varieties" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700/60 hover:border-slate-600 text-slate-200 font-bold text-xs transition group">
                                    <span class="text-emerald-400 font-black">{{ $crop->varieties_count }}</span>
                                    <span>Grades / Cultivars &rarr;</span>
                                </a>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <form method="POST" action="{{ route('admin.crops.toggle', $crop) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition cursor-pointer {{ $crop->is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/60 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $crop->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                        {{ $crop->is_active ? 'Active' : 'Paused' }}
                                    </button>
                                </form>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.crops.edit', $crop) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/60 transition"
                                       title="Edit Crop & Map Varieties">
                                        <span>✏️ Edit & Varieties</span>
                                    </a>
                                    <a href="{{ route('farmer.crop.detail', $crop->id) }}" 
                                       target="_blank"
                                       class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition" 
                                       title="View on Website">
                                       <span>👁️</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-5 text-center text-slate-500">
                                <div class="text-3xl mb-2">🌾</div>
                                <div class="text-base font-bold text-white">No agricultural commodities found</div>
                                <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or register a new crop.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($crops->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $crops->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
