@extends('layouts.admin')

@section('title', 'Price Freshness & Staleness Rules')

@section('content')
<div x-data="priceFreshnessManager()" x-init="init()" class="space-y-6">

    <!-- Top Notifications Toast (Async Feedback) -->
    <div x-show="toast.visible" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         :class="toast.type === 'success' ? 'bg-emerald-950/90 border-emerald-500/50 text-emerald-200' : 'bg-rose-950/90 border-rose-500/50 text-rose-200'"
         class="fixed top-20 right-6 z-50 px-5 py-3.5 rounded-2xl border shadow-2xl backdrop-blur-md flex items-center gap-3 max-w-md"
         style="display: none;">
        <span class="text-xl" x-text="toast.type === 'success' ? '✅' : '⚠️'"></span>
        <div class="flex-1 text-xs font-semibold leading-relaxed" x-text="toast.message"></div>
        <button type="button" @click="toast.visible = false" class="text-slate-400 hover:text-white p-1">✕</button>
    </div>

    <!-- Header & Platform Anchor Status -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="text-2xl">⏳</span>
                <h1 class="text-2xl font-black text-white tracking-tight">Price Freshness & Staleness Rules</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Live Engine
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                Control the maximum age of APMC mandi prices before varieties and markets are strictly hidden from the farmer mobile interface. Old data older than your threshold is suppressed automatically.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <button type="button" 
                    @click="simulateImpact()" 
                    :disabled="loading"
                    class="flex-1 sm:flex-none justify-center px-4 py-2.5 sm:py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg x-show="!simulating" class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg x-show="simulating" class="w-4 h-4 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="simulating ? 'Simulating...' : 'Simulate'"></span>
            </button>

            <button type="button" 
                    @click="saveRules()" 
                    :disabled="loading"
                    class="flex-1 sm:flex-none justify-center px-4 py-2.5 sm:py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-600/30 transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg x-show="!saving" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="saving ? 'Saving...' : 'Save Rules'"></span>
            </button>
        </div>
    </div>

    <!-- Live Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Crops</div>
            <div class="text-2xl font-black text-emerald-400 mt-1 font-mono flex items-baseline gap-2">
                <span x-text="stats.active_count"></span>
                <span class="text-xs font-semibold text-slate-400" x-text="'(' + stats.active_percentage + '%)'"></span>
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Trading within active window</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Suppressed (Stale)</div>
            <div class="text-2xl font-black text-rose-400 mt-1 font-mono" x-text="stats.stale_count"></div>
            <div class="text-[10px] text-slate-400 mt-1">Hidden from crop detail page</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Monitored</div>
            <div class="text-2xl font-black text-white mt-1 font-mono" x-text="stats.total_crops"></div>
            <div class="text-[10px] text-slate-400 mt-1">Active crop master records</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Platform Anchor Date</div>
            <div class="text-lg font-black text-cyan-400 mt-1 font-mono truncate" x-text="stats.anchor_date"></div>
            <div class="text-[10px] text-slate-400 mt-1">Max Karnataka market date</div>
        </div>
    </div>

    <!-- Configuration Panel -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-lg space-y-6">
        
        <!-- Global Default + Quick Presets Row -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-800">
            <div class="space-y-1 max-w-md">
                <label class="block text-sm font-black text-white">Global Fallback Freshness Threshold</label>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Used as the default cutoff window when a crop category does not specify its own custom days.
                </p>
                <div class="flex items-center gap-3 pt-2">
                    <div class="relative w-36">
                        <input type="number" 
                               x-model.number="globalDays" 
                               min="1" 
                               max="90" 
                               class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white font-mono font-bold text-sm focus:border-emerald-500 focus:outline-none">
                        <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">Days</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <template x-for="chip in [7, 14, 21, 30]">
                            <button type="button" 
                                    @click="globalDays = chip" 
                                    :class="globalDays === chip ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-800 hover:bg-slate-700 text-slate-300'"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-mono transition cursor-pointer"
                                    x-text="chip + 'd'">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Quick Preset Configurations -->
            <div class="space-y-2 lg:text-right">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Quick Category Presets</span>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" 
                            @click="applyPreset('strict')" 
                            class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                        <span>⚡</span>
                        <span>Strict (Veg 5d, Cer 14d, Pl 21d)</span>
                    </button>
                    <button type="button" 
                            @click="applyPreset('balanced')" 
                            class="px-3 py-2 rounded-xl bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 text-xs font-bold border border-emerald-800/60 transition flex items-center gap-1.5 cursor-pointer">
                        <span>⚖️</span>
                        <span>Recommended Standard</span>
                    </button>
                    <button type="button" 
                            @click="applyPreset('relaxed')" 
                            class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                        <span>🌿</span>
                        <span>Relaxed (Veg 14d, Cer 30d, Pl 45d)</span>
                    </button>
                    <button type="button" 
                            @click="resetToDefaults()" 
                            class="px-3 py-2 rounded-xl bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 text-xs font-bold border border-rose-800/40 transition flex items-center gap-1.5 cursor-pointer">
                        <span>🔄</span>
                        <span>Reset System Defaults</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Dynamic Category Cards Grid -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-black text-white">Category-Specific Freshness Overrides</h3>
                    <p class="text-xs text-slate-400">Different agricultural commodities trade at different cycle speeds. Fast-perishing vegetables require a tighter threshold than durable plantation nuts.</p>
                </div>
                <span class="text-xs text-slate-400 font-mono font-bold" x-text="categories.length + ' Categories'"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                <template x-for="cat in categories" :key="cat.slug">
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 hover:border-slate-700 transition flex flex-col justify-between space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-xs font-black text-white" x-text="cat.name"></div>
                                <div class="text-[11px] text-emerald-400 font-medium font-kannada" x-text="cat.name_kn || ''"></div>
                                <div class="text-[10px] text-slate-400 mt-0.5" x-text="(cat.crops_count || 0) + ' Crops'"></div>
                            </div>
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-slate-300" 
                                  x-text="(categoryDays[cat.slug] || globalDays) + 'd'">
                            </span>
                        </div>

                        <!-- Stepper / Input Control -->
                        <div class="flex items-center gap-2 pt-1 border-t border-slate-900">
                            <button type="button" 
                                    @click="decrementCat(cat.slug)"
                                    class="w-8 h-8 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 font-bold flex items-center justify-center transition cursor-pointer">
                                -
                            </button>
                            <div class="relative flex-1">
                                <input type="number" 
                                       x-model.number="categoryDays[cat.slug]" 
                                       min="1" 
                                       max="90" 
                                       class="w-full text-center py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-white font-mono font-bold text-xs focus:border-emerald-500 focus:outline-none">
                            </div>
                            <button type="button" 
                                    @click="incrementCat(cat.slug)"
                                    class="w-8 h-8 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 font-bold flex items-center justify-center transition cursor-pointer">
                                +
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Sticky Bottom Action Footer Inside Card -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Changes save asynchronously without reloading the page.</span>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="simulateImpact()" 
                        :disabled="loading"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition cursor-pointer disabled:opacity-50">
                    Run Simulation
                </button>
                <button type="button" 
                        @click="saveRules()" 
                        :disabled="loading"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                    <span x-text="saving ? 'Saving Rules...' : 'Save Rules Now'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Live Crop Impact Explorer & Filter Table -->
    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-lg space-y-4">
        
        <!-- Table Controls: Search & Status Filter Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div>
                <h3 class="text-sm font-black text-white">Live Commodity Status Explorer</h3>
                <p class="text-xs text-slate-400">Review how your threshold rules impact each crop's visibility right now.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Status Filter Tabs -->
                <div class="inline-flex rounded-xl bg-slate-950 p-1 border border-slate-800 text-xs">
                    <button type="button" 
                            @click="filterStatus = 'all'" 
                            :class="filterStatus === 'all' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1 rounded-lg transition"
                            x-text="'All (' + crops.length + ')'">
                    </button>
                    <button type="button" 
                            @click="filterStatus = 'active'" 
                            :class="filterStatus === 'active' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-emerald-400'"
                            class="px-3 py-1 rounded-lg transition"
                            x-text="'🟢 Active (' + stats.active_count + ')'">
                    </button>
                    <button type="button" 
                            @click="filterStatus = 'stale'" 
                            :class="filterStatus === 'stale' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-rose-400'"
                            class="px-3 py-1 rounded-lg transition"
                            x-text="'🔴 Suppressed (' + stats.stale_count + ')'">
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-48 sm:w-56">
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Search crop name..." 
                           class="w-full px-3 py-1.5 pl-8 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
                    <span class="absolute left-2.5 top-2 text-slate-500 text-xs">🔍</span>
                </div>
            </div>
        </div>

        <!-- Mobile Table Swipe Cue -->
        <div class="sm:hidden px-4 py-2 bg-slate-950 border-b border-slate-800 text-[11px] text-slate-400 flex items-center justify-between rounded-xl">
            <span class="flex items-center gap-1.5 font-medium">
                <span>👉</span> Scroll horizontally for cutoff date & visibility
            </span>
            <span class="text-[10px] text-slate-500 font-mono">Swipe ↔</span>
        </div>

        <!-- Responsive Crops Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 font-bold border-b border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Crop / Commodity</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-center">Applied Threshold</th>
                        <th class="py-3 px-4 text-center">Cutoff Date</th>
                        <th class="py-3 px-4 text-center">Last Traded Date</th>
                        <th class="py-3 px-4 text-center">Reporting Mandis</th>
                        <th class="py-3 px-4 text-right">Visibility Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 bg-slate-900/40">
                    <template x-for="crop in filteredCrops" :key="crop.id">
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg overflow-hidden bg-slate-950 shrink-0 border border-slate-800">
                                        <img :src="crop.photo_url" :alt="crop.name" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <a :href="'/crops/' + crop.slug" target="_blank" class="font-bold text-white hover:text-emerald-400 transition" x-text="crop.name"></a>
                                        <div class="text-[11px] text-slate-400 font-kannada" x-text="crop.name_kn || ''"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-300 font-medium" x-text="crop.category_name"></td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-cyan-400" x-text="crop.threshold_days + ' Days'"></td>
                            <td class="py-3 px-4 text-center font-mono text-slate-400" x-text="crop.cutoff_date"></td>
                            <td class="py-3 px-4 text-center font-mono">
                                <span x-show="crop.last_traded_date" class="text-white font-bold" x-text="crop.last_traded_date"></span>
                                <span x-show="!crop.last_traded_date" class="text-slate-500">—</span>
                                <div x-show="crop.days_since_trade !== null" class="text-[10px] text-slate-400" x-text="crop.days_since_trade + ' days ago'"></div>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-slate-300" x-text="crop.total_mandis"></td>
                            <td class="py-3 px-4 text-right">
                                <span x-show="crop.status === 'active'" 
                                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    <span>Active</span>
                                </span>
                                <span x-show="crop.status === 'stale'" 
                                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    <span>Suppressed</span>
                                </span>
                                <span x-show="crop.status === 'no_data'" 
                                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                    <span>No Records</span>
                                </span>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredCrops.length === 0">
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            No commodities matching current filter or search query.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function priceFreshnessManager() {
    return {
        globalDays: {{ $globalDays }},
        categoryDays: @json($categoryDays),
        defaultCategoryDays: @json($defaultCategoryDays),
        categories: @json($categories),
        stats: @json($stats),
        crops: @json($crops),
        
        filterStatus: 'all',
        searchQuery: '',
        loading: false,
        saving: false,
        simulating: false,

        toast: {
            visible: false,
            message: '',
            type: 'success',
            timer: null,
        },

        init() {
            // Ensure all active category slugs exist in categoryDays object
            this.categories.forEach(cat => {
                if (!this.categoryDays[cat.slug]) {
                    this.categoryDays[cat.slug] = this.defaultCategoryDays[cat.slug] || this.globalDays;
                }
            });
        },

        showToast(message, type = 'success') {
            if (this.toast.timer) clearTimeout(this.toast.timer);
            this.toast.message = message;
            this.toast.type = type;
            this.toast.visible = true;
            this.toast.timer = setTimeout(() => {
                this.toast.visible = false;
            }, 4000);
        },

        incrementCat(slug) {
            let current = parseInt(this.categoryDays[slug] || this.globalDays);
            if (current < 90) {
                this.categoryDays[slug] = current + 1;
            }
        },

        decrementCat(slug) {
            let current = parseInt(this.categoryDays[slug] || this.globalDays);
            if (current > 1) {
                this.categoryDays[slug] = current - 1;
            }
        },

        applyPreset(mode) {
            if (mode === 'strict') {
                this.globalDays = 10;
                this.categoryDays['vegetables'] = 5;
                this.categoryDays['fruits'] = 7;
                this.categoryDays['cereals-millets'] = 14;
                this.categoryDays['pulses'] = 14;
                this.categoryDays['commercial-plantation'] = 21;
                this.categoryDays['spices'] = 21;
                this.categoryDays['oilseeds'] = 21;
                this.categoryDays['commercial-crops'] = 21;
                this.showToast('Applied Strict preset (5d veg, 14d cereals, 21d commercial). Click Save to apply.');
            } else if (mode === 'balanced') {
                this.globalDays = 14;
                this.categoryDays['vegetables'] = 7;
                this.categoryDays['fruits'] = 7;
                this.categoryDays['cereals-millets'] = 21;
                this.categoryDays['pulses'] = 21;
                this.categoryDays['commercial-plantation'] = 30;
                this.categoryDays['spices'] = 30;
                this.categoryDays['oilseeds'] = 30;
                this.categoryDays['commercial-crops'] = 30;
                this.showToast('Applied Recommended Balanced preset. Click Save to apply.');
            } else if (mode === 'relaxed') {
                this.globalDays = 21;
                this.categoryDays['vegetables'] = 14;
                this.categoryDays['fruits'] = 14;
                this.categoryDays['cereals-millets'] = 30;
                this.categoryDays['pulses'] = 30;
                this.categoryDays['commercial-plantation'] = 45;
                this.categoryDays['spices'] = 45;
                this.categoryDays['oilseeds'] = 45;
                this.categoryDays['commercial-crops'] = 45;
                this.showToast('Applied Relaxed preset (14d veg, 30d cereals, 45d commercial). Click Save to apply.');
            }
        },

        async saveRules() {
            this.loading = true;
            this.saving = true;

            try {
                const response = await fetch("{{ route('admin.price-freshness.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        global_days: this.globalDays,
                        category_days: this.categoryDays
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                    this.crops = data.crops;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Failed to save rules', 'error');
                }
            } catch (err) {
                this.showToast('Network error while saving settings: ' + err.message, 'error');
            } finally {
                this.loading = false;
                this.saving = false;
            }
        },

        async simulateImpact() {
            this.loading = true;
            this.simulating = true;

            try {
                const response = await fetch("{{ route('admin.price-freshness.simulate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        global_days: this.globalDays,
                        category_days: this.categoryDays
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                    this.crops = data.crops;
                    this.showToast('Simulation updated live without saving!', 'success');
                }
            } catch (err) {
                this.showToast('Simulation failed: ' + err.message, 'error');
            } finally {
                this.loading = false;
                this.simulating = false;
            }
        },

        async resetToDefaults() {
            if (!confirm('Are you sure you want to restore freshness rules to standard system defaults?')) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch("{{ route('admin.price-freshness.reset') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.globalDays = data.global_days;
                    this.categoryDays = data.category_days;
                    this.stats = data.stats;
                    this.crops = data.crops;
                    this.showToast(data.message, 'success');
                }
            } catch (err) {
                this.showToast('Reset failed: ' + err.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        get filteredCrops() {
            let list = this.crops;

            if (this.filterStatus === 'active') {
                list = list.filter(c => c.status === 'active');
            } else if (this.filterStatus === 'stale') {
                list = list.filter(c => c.status === 'stale');
            }

            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(c => 
                    (c.name && c.name.toLowerCase().includes(q)) ||
                    (c.name_kn && c.name_kn.toLowerCase().includes(q)) ||
                    (c.category_name && c.category_name.toLowerCase().includes(q))
                );
            }

            return list;
        }
    };
}
</script>
@endsection
