@extends('layouts.admin')

@section('title', 'Agricultural Commodities & Crops')

@section('content')
<div x-data="cropsManagement()" class="space-y-6">

    <!-- Toast Notification Overlay -->
    <div x-show="toast.show" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-5 right-5 z-50 max-w-sm p-4 rounded-2xl shadow-2xl border backdrop-blur-md flex items-center gap-3 text-xs"
         :class="toast.type === 'success' ? 'bg-slate-900/95 border-emerald-500/80 text-emerald-200 shadow-emerald-950/40' : 'bg-slate-900/95 border-rose-500/80 text-rose-200 shadow-rose-950/40'"
         style="display: none;">
        <span class="text-base" x-text="toast.type === 'success' ? '✅' : '⚠️'"></span>
        <span class="font-bold flex-1 leading-relaxed" x-text="toast.message"></span>
        <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white font-bold text-sm cursor-pointer">✕</button>
    </div>

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🌾</span> Agricultural Commodities & Crops
                <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-950/80 text-emerald-300 border border-emerald-800/60 rounded-full font-kannada">ಬೆಳೆಗಳ ಪಟ್ಟಿ</span>
            </h1>
            <p class="text-sm text-slate-400 font-medium">Manage canonical Karnataka commodities, trading units, discovery radius, and cultivar mappings.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <template x-if="selectedIds.length > 0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <!-- Quick Activate -->
                    <button type="button" 
                            @click="executeBatchAction('activate')"
                            :disabled="isUpdatingBatch"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-50"
                            title="Activate all selected commodities">
                        <template x-if="isUpdatingBatch && batchActionTarget === 'activate'">
                            <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        </template>
                        <template x-if="!(isUpdatingBatch && batchActionTarget === 'activate')">
                            <span>▶️</span>
                        </template>
                        <span>Activate (<span x-text="inactiveSelectedCount"></span>)</span>
                    </button>

                    <!-- Quick Pause -->
                    <button type="button" 
                            @click="executeBatchAction('pause')"
                            :disabled="isUpdatingBatch"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-50"
                            title="Pause all selected commodities">
                        <template x-if="isUpdatingBatch && batchActionTarget === 'pause'">
                            <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        </template>
                        <template x-if="!(isUpdatingBatch && batchActionTarget === 'pause')">
                            <span>⏸️</span>
                        </template>
                        <span>Pause (<span x-text="activeSelectedCount"></span>)</span>
                    </button>

                    <!-- Delete Selected -->
                    <button type="button" 
                            @click="openBatchModal()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl transition shadow-sm cursor-pointer">
                        <span>🗑️</span>
                        <span>Delete (<span x-text="selectedIds.length"></span>)</span>
                    </button>
                </div>
            </template>

            <a href="{{ route('admin.crops.create', ['category_id' => $categoryId]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Register New Crop</span>
            </a>
        </div>
    </div>

    <!-- Metrics Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.crops.index') }}" 
           class="bg-slate-900 border {{ empty($status) ? 'border-emerald-500/80 ring-1 ring-emerald-500/30' : 'border-slate-800' }} hover:border-slate-700 rounded-2xl p-5 shadow-sm transition block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Commodities</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-bold">All</span>
            </div>
            <div class="text-3xl font-extrabold text-white mt-1">{{ $totalCropsCount }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Cataloged in database</div>
        </a>

        <a href="{{ route('admin.crops.index', ['status' => 'active', 'category_id' => $categoryId]) }}" 
           class="bg-slate-900 border {{ $status === 'active' ? 'border-emerald-500/80 ring-1 ring-emerald-500/30' : 'border-slate-800' }} hover:border-slate-700 rounded-2xl p-5 shadow-sm transition block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Trading</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800/80 font-bold">Enabled</span>
            </div>
            <div class="text-3xl font-extrabold text-emerald-400 mt-1">{{ $activeCropsCount }}</div>
            <div class="text-xs font-medium text-emerald-400/90 mt-1">Active sync & market feeds</div>
        </a>

        <a href="{{ route('admin.crops.index', ['status' => 'inactive', 'category_id' => $categoryId]) }}" 
           class="bg-slate-900 border {{ $status === 'inactive' ? 'border-amber-500/80 ring-1 ring-amber-500/30' : 'border-slate-800' }} hover:border-slate-700 rounded-2xl p-5 shadow-sm transition block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Inactive / Disabled</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-950 text-amber-400 border border-amber-800/80 font-bold">Paused</span>
            </div>
            <div class="text-3xl font-extrabold text-amber-400 mt-1">{{ $inactiveCropsCount }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Ready for cleanup / deletion</div>
        </a>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Major Priority Crops</span>
            <div class="text-3xl font-extrabold text-cyan-400 mt-1">
                {{ \App\Models\Crop::where('is_major', true)->count() }}
            </div>
            <div class="text-xs font-medium text-slate-400 mt-1">Priority state focus</div>
        </div>
    </div>

    <!-- Filters & Search Bar Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm space-y-3">
        <form method="GET" action="{{ route('admin.crops.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <!-- Search Text -->
            <div class="relative sm:col-span-5">
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
            <div class="sm:col-span-3">
                <select name="category_id" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="sm:col-span-2">
                <select name="status" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Statuses ({{ $totalCropsCount }})</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only ({{ $activeCropsCount }})</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive Only ({{ $inactiveCropsCount }})</option>
                </select>
            </div>

            <!-- Per Page & Submit -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <select name="per_page" class="w-24 px-2 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="Items per page">
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200 (All)</option>
                </select>

                <button type="submit" class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition shadow-sm cursor-pointer text-center">
                    Filter
                </button>

                @if($search || $categoryId || $status || $perPage != 50)
                    <a href="{{ route('admin.crops.index') }}" class="px-3 py-2.5 text-xs font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition" title="Reset Filters">
                        ✕
                    </a>
                @endif
            </div>
        </form>

        <!-- Quick Selection Controls -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 flex-wrap gap-2 text-xs">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-slate-400 font-semibold text-[11px] mr-1">Quick Select:</span>
                <button type="button" 
                        @click="selectAllOnPage()" 
                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/60 text-[11px] font-bold cursor-pointer transition">
                    All Visible ({{ $crops->count() }})
                </button>
                <button type="button" 
                        @click="selectOnlyActive()" 
                        class="px-2.5 py-1 rounded-lg bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border border-emerald-800/80 text-[11px] font-bold cursor-pointer transition">
                    ▶️ Active Only
                </button>
                <button type="button" 
                        @click="selectOnlyInactive()" 
                        class="px-2.5 py-1 rounded-lg bg-amber-950/80 hover:bg-amber-900 text-amber-300 border border-amber-800/80 text-[11px] font-bold cursor-pointer transition">
                    ⏸️ Paused Only
                </button>
                <button type="button" 
                        @click="selectOnlyMajor()" 
                        class="px-2.5 py-1 rounded-lg bg-purple-950/80 hover:bg-purple-900 text-purple-300 border border-purple-800/80 text-[11px] font-bold cursor-pointer transition">
                    ★ Major Only
                </button>
                <template x-if="selectedIds.length > 0">
                    <button type="button" 
                            @click="deselectAll()" 
                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white border border-slate-700/60 text-[11px] font-bold cursor-pointer transition">
                        ✕ Clear Selection
                    </button>
                </template>
            </div>

            <div class="text-[11px] text-slate-400">
                <span class="text-white font-bold" x-text="selectedIds.length"></span> commodities selected on this page
            </div>
        </div>
    </div>

    <!-- Crops Table Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2">
                <h2 class="font-bold text-white flex items-center gap-2">
                    <span>🌾</span> Registered Commodities ({{ $crops->total() }})
                </h2>
                <template x-if="selectedIds.length > 0">
                    <span class="px-2.5 py-0.5 rounded-full bg-rose-950 text-rose-300 border border-rose-800 text-[11px] font-bold"
                          x-text="selectedIds.length + ' selected'">
                    </span>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <template x-if="selectedIds.length > 0">
                    <button type="button" 
                            @click="openBatchModal()"
                            class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition shadow-sm cursor-pointer flex items-center gap-1.5">
                        <span>🗑️</span>
                        <span>Delete Selected (<span x-text="selectedIds.length"></span>)</span>
                    </button>
                </template>
                <div class="text-xs font-semibold text-slate-400">Page {{ $crops->currentPage() }} of {{ $crops->lastPage() }}</div>
            </div>
        </div>

        <!-- Mobile Table Swipe Hint -->
        <div class="sm:hidden flex items-center justify-between px-3.5 py-2 bg-slate-950/70 border-b border-slate-800 text-[11px] text-slate-400">
            <span class="flex items-center gap-1.5 font-medium"><span>👈</span> <span>Swipe table horizontally</span> <span>👉</span></span>
            <span class="text-emerald-400 font-bold">Varieties & Actions on right</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 pl-5 pr-2 w-10 text-center">
                            <input type="checkbox" 
                                   @change="toggleSelectAll()" 
                                   :checked="selectedIds.length > 0 && selectedIds.length === pageCropIds.length"
                                   :indeterminate="selectedIds.length > 0 && selectedIds.length < pageCropIds.length"
                                   class="rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-rose-500 cursor-pointer"
                                   title="Select / Deselect all on this page">
                        </th>
                        <th class="py-3.5 px-4">Crop Name</th>
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
                        <tr class="transition hover:bg-slate-800/35"
                            :class="isSelected({{ $crop->id }}) ? 'bg-emerald-950/20 hover:bg-emerald-950/30' : ''">
                            
                            <!-- Checkbox -->
                            <td class="py-3.5 pl-5 pr-2 text-center">
                                <input type="checkbox" 
                                       :value="{{ $crop->id }}" 
                                       x-model="selectedIds"
                                       class="rounded bg-slate-950 border-slate-700 text-emerald-500 focus:ring-emerald-500 cursor-pointer">
                            </td>

                            <!-- Crop Name -->
                            <td class="py-3.5 px-4">
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
                                            <template x-if="cropsMajor[{{ $crop->id }}]">
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase tracking-wider bg-amber-950/80 text-amber-300 border border-amber-800/60" title="Major Karnataka Commodity">
                                                    ★ Major
                                                </span>
                                            </template>
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
                                                No Prices
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 text-[10px]">
                                        <span class="text-slate-400 font-semibold">{{ $crop->sourceMappings->count() }} feed aliases mapped</span>
                                        <span class="text-slate-600">•</span>
                                        <span class="inline-flex items-center gap-1 font-mono font-bold text-emerald-300 bg-emerald-950/50 px-1.5 py-0.5 rounded border border-emerald-800/50">
                                            <span>📍 Radius: {{ $crop->market_radius_km == 0 || $crop->market_radius_km >= 500 ? 'All KA' : $crop->market_radius_km . 'km' }}</span>
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

                            <!-- Status (Async Instant Toggle) -->
                            <td class="py-3.5 px-4 text-center">
                                <button type="button" 
                                        @click="toggleSingleCrop({{ $crop->id }})" 
                                        :disabled="togglingCrops.includes({{ $crop->id }})"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition cursor-pointer disabled:opacity-50"
                                        :class="cropsStatus[{{ $crop->id }}] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/60 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700'"
                                        :title="cropsStatus[{{ $crop->id }}] ? 'Click to Pause this commodity' : 'Click to Activate this commodity'">
                                    <template x-if="togglingCrops.includes({{ $crop->id }})">
                                        <span class="w-2.5 h-2.5 border border-current border-t-transparent rounded-full animate-spin"></span>
                                    </template>
                                    <template x-if="!togglingCrops.includes({{ $crop->id }})">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="cropsStatus[{{ $crop->id }}] ? 'bg-emerald-400' : 'bg-slate-500'"></span>
                                    </template>
                                    <span x-text="cropsStatus[{{ $crop->id }}] ? 'Active' : 'Paused'"></span>
                                </button>
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
                                    <button type="button" 
                                            @click="openSingleModal({ id: {{ $crop->id }}, name: '{{ addslashes($crop->name) }}', is_active: {{ $crop->is_active ? 'true' : 'false' }}, prices_count: {{ (int) $crop->prices_count }} })" 
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-950/40 border border-transparent hover:border-rose-800/60 transition cursor-pointer"
                                            title="Delete Commodity">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 px-5 text-center text-slate-500">
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

    <!-- Floating Bulk Action Toolbar (Appears when >= 1 crops selected) -->
    <div x-show="selectedIds.length > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-6"
         class="fixed bottom-20 lg:bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900/95 border border-slate-700/80 shadow-2xl backdrop-blur-md rounded-2xl px-4 sm:px-5 py-3 sm:py-3.5 flex items-center gap-3.5 text-xs flex-wrap justify-between max-w-4xl w-[94%]"
         style="display: none;">
        
        <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <div>
                <span class="font-bold text-white"><span x-text="selectedIds.length"></span> commodities selected</span>
                <div class="text-[11px] text-slate-400 mt-0.5">
                    <span class="text-emerald-400 font-bold" x-text="activeSelectedCount + ' Active'"></span> • 
                    <span class="text-amber-400 font-bold" x-text="inactiveSelectedCount + ' Paused'"></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- 1. Bulk Activate Button (Async) -->
            <button type="button" 
                    @click="executeBatchAction('activate')" 
                    :disabled="isUpdatingBatch"
                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-sm cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                    title="Activate selected commodities">
                <template x-if="isUpdatingBatch && batchActionTarget === 'activate'">
                    <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                </template>
                <template x-if="!(isUpdatingBatch && batchActionTarget === 'activate')">
                    <span>▶️</span>
                </template>
                <span>Activate (<span x-text="inactiveSelectedCount"></span>)</span>
            </button>

            <!-- 2. Bulk Pause Button (Async) -->
            <button type="button" 
                    @click="executeBatchAction('pause')" 
                    :disabled="isUpdatingBatch"
                    class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs transition shadow-sm cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                    title="Pause selected commodities">
                <template x-if="isUpdatingBatch && batchActionTarget === 'pause'">
                    <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                </template>
                <template x-if="!(isUpdatingBatch && batchActionTarget === 'pause')">
                    <span>⏸️</span>
                </template>
                <span>Pause (<span x-text="activeSelectedCount"></span>)</span>
            </button>

            <!-- 3. Bulk Major Highlight -->
            <div x-data="{ openMajorMenu: false }" class="relative">
                <button type="button" 
                        @click="openMajorMenu = !openMajorMenu" 
                        :disabled="isUpdatingBatch"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 font-bold text-xs transition cursor-pointer flex items-center gap-1">
                    <span>★ Major</span>
                    <span class="text-[10px]">▾</span>
                </button>
                <div x-show="openMajorMenu" 
                     @click.away="openMajorMenu = false" 
                     x-cloak
                     class="absolute bottom-full mb-2 right-0 w-48 bg-slate-900 border border-slate-800 rounded-xl p-1 shadow-2xl z-50 text-xs">
                    <button type="button" 
                            @click="openMajorMenu = false; executeBatchAction('mark_major')"
                            class="w-full text-left px-3 py-1.5 rounded-lg hover:bg-slate-800 text-amber-300 font-bold transition flex items-center gap-2 cursor-pointer">
                        <span>★ Mark as Major</span>
                    </button>
                    <button type="button" 
                            @click="openMajorMenu = false; executeBatchAction('unmark_major')"
                            class="w-full text-left px-3 py-1.5 rounded-lg hover:bg-slate-800 text-slate-300 hover:text-white font-medium transition flex items-center gap-2 cursor-pointer">
                        <span>☆ Remove Major</span>
                    </button>
                </div>
            </div>

            <!-- 4. Bulk Category Reassignment -->
            <div x-data="{ openCatMenu: false }" class="relative">
                <button type="button" 
                        @click="openCatMenu = !openCatMenu" 
                        :disabled="isUpdatingBatch"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition cursor-pointer flex items-center gap-1">
                    <span>📁 Move</span>
                    <span class="text-[10px]">▾</span>
                </button>
                <div x-show="openCatMenu" 
                     @click.away="openCatMenu = false" 
                     x-cloak
                     class="absolute bottom-full mb-2 right-0 w-52 bg-slate-900 border border-slate-800 rounded-xl p-2 shadow-2xl z-50 text-xs space-y-1 max-h-56 overflow-y-auto">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block px-1 pb-1 border-b border-slate-800">Move to Category:</span>
                    @foreach($categories as $cat)
                        <button type="button" 
                                @click="openCatMenu = false; executeBatchAction('change_category', {{ $cat->id }})"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-800 text-slate-200 hover:text-white font-medium transition truncate flex items-center justify-between cursor-pointer">
                            <span class="truncate">{{ $cat->name }}</span>
                            <span class="text-[10px] text-slate-500 font-mono">→</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- 5. Delete Button -->
            <button type="button" 
                    @click="openBatchModal()" 
                    :disabled="isUpdatingBatch"
                    class="px-3 py-1.5 rounded-xl bg-rose-600/90 hover:bg-rose-500 text-white font-bold text-xs transition shadow-sm cursor-pointer flex items-center gap-1">
                <span>🗑️</span>
                <span>Delete</span>
            </button>

            <!-- 6. Clear Selection -->
            <button type="button" 
                    @click="deselectAll()" 
                    class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white font-bold text-xs transition cursor-pointer"
                    title="Clear selection">
                ✕
            </button>
        </div>
    </div>

    <!-- Modal 1: Batch Delete Confirmation Modal -->
    <div x-show="batchModalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none;">
        
        <div @click.away="if (!isDeleting) batchModalOpen = false" 
             class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-rose-950/80 text-rose-400 border border-rose-800/80 flex items-center justify-center text-lg">
                        🗑️
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white">Delete Selected Commodities</h3>
                        <p class="text-xs text-slate-400">Permanent catalog removal</p>
                    </div>
                </div>
                <button type="button" @click="batchModalOpen = false" :disabled="isDeleting" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
            </div>

            <!-- Content Details -->
            <div class="space-y-3 text-xs leading-relaxed">
                <p class="text-slate-300">
                    You have selected <strong class="text-white font-mono text-sm" x-text="selectedIds.length"></strong> agricultural commodities for deletion.
                </p>

                <!-- Warning if any active crops selected -->
                <template x-if="activeSelectedCount > 0">
                    <div class="p-3.5 rounded-xl bg-amber-950/70 border border-amber-800/80 text-amber-200 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-amber-300">
                            <span>⚠️</span>
                            <span>Warning: <span x-text="activeSelectedCount"></span> Active Crops Selected!</span>
                        </div>
                        <p class="text-[11px] text-amber-200/90 leading-relaxed">
                            Deleting an active crop will permanently remove all associated daily prices, historical analytics, and price forecasts for that crop.
                        </p>
                    </div>
                </template>

                <!-- Reassuring message if only inactive crops selected -->
                <template x-if="activeSelectedCount === 0">
                    <div class="p-3 rounded-xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-200 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-emerald-300">
                            <span>🛡️</span>
                            <span>Safe Inactive Crops Deletion</span>
                        </div>
                        <p class="text-[11px] text-emerald-200/90 leading-relaxed">
                            All <span class="font-bold" x-text="selectedIds.length"></span> selected commodities are inactive/disabled. Deleting them will cleanly remove cultivar grades and feed mappings while preserving your active commodities intact.
                        </p>
                    </div>
                </template>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400">
                    ⚡ <strong>Cascading Cleanup:</strong> Cultivar varieties, sync configurations, and mapping aliases for these crops will be safely cleaned up automatically.
                </div>
            </div>

            <form method="POST" action="{{ route('admin.crops.batch-delete') }}" @submit="isDeleting = true">
                @csrf
                <!-- Dynamic hidden inputs for all selected crop IDs -->
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="crop_ids[]" :value="id">
                </template>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" 
                            @click="batchModalOpen = false" 
                            :disabled="isDeleting" 
                            class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 rounded-xl hover:bg-slate-700 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="isDeleting" 
                            class="px-5 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-500 transition shadow-sm cursor-pointer flex items-center gap-2 disabled:opacity-50">
                        <template x-if="isDeleting">
                            <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        </template>
                        <span x-text="isDeleting ? 'Deleting...' : '🗑️ Confirm Delete (' + selectedIds.length + ')'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Single Crop Delete Confirmation Modal -->
    <div x-show="singleModalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none;">
        
        <div @click.away="if (!isDeleting) singleModalOpen = false" 
             class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-rose-950/80 text-rose-400 border border-rose-800/80 flex items-center justify-center text-lg">
                        🗑️
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white">Delete Commodity</h3>
                        <p class="text-xs text-slate-400" x-text="targetCrop?.name"></p>
                    </div>
                </div>
                <button type="button" @click="singleModalOpen = false" :disabled="isDeleting" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
            </div>

            <div class="space-y-3 text-xs leading-relaxed">
                <p class="text-slate-300">
                    Are you sure you want to delete <strong class="text-white font-bold" x-text="targetCrop?.name"></strong> from the system catalog?
                </p>

                <template x-if="targetCrop && targetCrop.prices_count > 0">
                    <div class="p-3 rounded-xl bg-amber-950/70 border border-amber-800/80 text-amber-200">
                        ⚠️ <strong>Notice:</strong> This commodity still has <span class="font-bold" x-text="targetCrop.prices_count"></span> price records which will also be deleted.
                    </div>
                </template>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400">
                    All associated cultivar varieties, aliases, and sync rules will be removed permanently.
                </div>
            </div>

            <form :action="'{{ url('admin/crops') }}/' + (targetCrop?.id || '')" method="POST" @submit="isDeleting = true">
                @csrf
                @method('DELETE')

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" 
                            @click="singleModalOpen = false" 
                            :disabled="isDeleting" 
                            class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 rounded-xl hover:bg-slate-700 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="isDeleting" 
                            class="px-5 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-500 transition shadow-sm cursor-pointer flex items-center gap-2 disabled:opacity-50">
                        <template x-if="isDeleting">
                            <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        </template>
                        <span x-text="isDeleting ? 'Deleting...' : '🗑️ Delete Commodity'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function cropsManagement() {
    return {
        selectedIds: [],
        pageCropIds: @json($crops->pluck('id')->values()),
        cropsStatus: {
            @foreach($crops as $c)
                {{ $c->id }}: {{ $c->is_active ? 'true' : 'false' }},
            @endforeach
        },
        cropsMajor: {
            @foreach($crops as $c)
                {{ $c->id }}: {{ $c->is_major ? 'true' : 'false' }},
            @endforeach
        },
        togglingCrops: [],
        isUpdatingBatch: false,
        batchActionTarget: '',
        batchModalOpen: false,
        singleModalOpen: false,
        targetCrop: null,
        isDeleting: false,
        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null,
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        toggleSelectAll() {
            if (this.selectedIds.length === this.pageCropIds.length) {
                this.selectedIds = [];
            } else {
                this.selectedIds = [...this.pageCropIds];
            }
        },

        selectOnlyInactive() {
            this.selectedIds = this.pageCropIds.filter(id => !this.cropsStatus[id]);
        },

        selectOnlyActive() {
            this.selectedIds = this.pageCropIds.filter(id => this.cropsStatus[id]);
        },

        selectOnlyMajor() {
            this.selectedIds = this.pageCropIds.filter(id => this.cropsMajor[id]);
        },

        selectAllOnPage() {
            this.selectedIds = [...this.pageCropIds];
        },

        deselectAll() {
            this.selectedIds = [];
        },

        isSelected(id) {
            return this.selectedIds.includes(id);
        },

        get inactiveSelectedCount() {
            return this.selectedIds.filter(id => !this.cropsStatus[id]).length;
        },

        get activeSelectedCount() {
            return this.selectedIds.filter(id => this.cropsStatus[id]).length;
        },

        // Async single crop status toggle
        async toggleSingleCrop(cropId) {
            if (this.togglingCrops.includes(cropId)) return;
            this.togglingCrops.push(cropId);

            try {
                const response = await fetch(`{{ url('admin/crops') }}/${cropId}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();
                if (data.ok) {
                    this.cropsStatus[cropId] = data.is_active;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Failed to update status', 'error');
                }
            } catch (err) {
                this.showToast('Network error updating crop status', 'error');
            } finally {
                this.togglingCrops = this.togglingCrops.filter(id => id !== cropId);
            }
        },

        // Async batch update actions (activate, pause, mark_major, unmark_major, change_category)
        async executeBatchAction(action, categoryId = null) {
            if (this.selectedIds.length === 0 || this.isUpdatingBatch) return;

            this.isUpdatingBatch = true;
            this.batchActionTarget = action;

            try {
                const payload = {
                    crop_ids: this.selectedIds,
                    action: action,
                };
                if (categoryId) {
                    payload.category_id = categoryId;
                }

                const response = await fetch(`{{ route('admin.crops.batch-update') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                if (data.ok) {
                    if (action === 'activate') {
                        this.selectedIds.forEach(id => { this.cropsStatus[id] = true; });
                    } else if (action === 'pause') {
                        this.selectedIds.forEach(id => { this.cropsStatus[id] = false; });
                    } else if (action === 'mark_major') {
                        this.selectedIds.forEach(id => { this.cropsMajor[id] = true; });
                    } else if (action === 'unmark_major') {
                        this.selectedIds.forEach(id => { this.cropsMajor[id] = false; });
                    } else if (action === 'change_category') {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Batch update failed', 'error');
                }
            } catch (err) {
                this.showToast('Network error during batch update', 'error');
            } finally {
                this.isUpdatingBatch = false;
                this.batchActionTarget = '';
            }
        },

        openBatchModal() {
            if (this.selectedIds.length === 0) return;
            this.batchModalOpen = true;
        },

        openSingleModal(crop) {
            this.targetCrop = crop;
            this.singleModalOpen = true;
        }
    };
}
</script>
@endsection
