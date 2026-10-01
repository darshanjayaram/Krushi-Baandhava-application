@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="weatherAdminManager()">

    <!-- Page Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span class="text-amber-400">🌤️</span>
                <span>Weather Engine & Storage Management</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Configure on-demand Open-Meteo cache duration, farm-level coordinates sync, and manage database storage footprint.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Interactive Async Batch Runner Button -->
            <button type="button" 
                    @click="startBatchSync()"
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black transition flex items-center gap-2 shadow-lg hover:shadow-emerald-900/30">
                <span class="animate-pulse">⚡</span>
                <span>Sync All 31 Districts (Async)</span>
            </button>

            <!-- Optional Background Queue Button -->
            <form action="{{ route('admin.weather.sync-all') }}" method="POST" 
                  onsubmit="return confirm('Dispatch background queue job for all districts?')">
                @csrf
                <input type="hidden" name="mode" value="async">
                <button type="submit" 
                        title="Dispatches to Laravel background worker queue"
                        class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-bold text-slate-400 hover:text-slate-200 transition flex items-center gap-1.5 shadow-sm">
                    <span>⚙️</span>
                    <span>Queue Job</span>
                </button>
            </form>

            <a href="{{ route('admin.settings.index') }}" 
               class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-bold text-slate-300 hover:text-white transition">
                ← Settings
            </a>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 text-xs sm:text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2.5">
                <span class="text-base">✓</span>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-white text-xs font-bold">✕</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-950/80 border border-rose-500/50 text-rose-200 text-xs sm:text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2.5">
                <span class="text-base">⚠️</span>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-white text-xs font-bold">✕</button>
        </div>
    @endif

    <!-- Architecture & Operational Mode Banner -->
    <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950/50 via-slate-900 to-cyan-950/40 border border-emerald-700/40 shadow-xl">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-[10px] font-black text-emerald-300 uppercase tracking-wider">
                        Active Mode: On-Demand Hyperlocal
                    </span>
                    <span class="text-xs text-slate-400">• Statewide Batch Cron Disabled</span>
                </div>
                <h3 class="text-sm sm:text-base font-black text-white">
                    Smart Demand-Driven Weather Engine
                </h3>
                <p class="text-xs text-slate-300 max-w-3xl leading-relaxed">
                    Instead of blindly syncing all 31 districts twice daily, weather forecasts are now fetched instantly whenever a farmer visits the homepage, opens <span class="text-emerald-300 font-mono">/weather</span>, or provides farm GPS coordinates. If cached data for their district is fresher than <strong class="text-white">{{ $ttlMinutes }} minutes</strong>, instant local cache is served without API overhead.
                </p>
            </div>
            <div class="shrink-0 flex items-center gap-2 px-3 py-2 rounded-2xl bg-black/40 border border-white/10 text-xs font-mono text-emerald-300">
                <span>Quota Safe: ~4 req/hr max</span>
            </div>
        </div>
    </div>

    <!-- 4 High-Level Metric Tiles -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Forecast Records -->
        <div class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold">
                <span>Stored Records</span>
                <span class="text-cyan-400">📊</span>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-white">
                {{ number_format($stats['total_records']) }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Daily 7-day forecast snapshots
            </div>
        </div>

        <!-- Metric 2: Estimated Table Footprint -->
        <div class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold">
                <span>Estimated Footprint</span>
                <span class="text-emerald-400">💾</span>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-emerald-400">
                {{ $formattedSize }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Includes raw hourly payloads
            </div>
        </div>

        <!-- Metric 3: Active Cache Duration -->
        <div class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold">
                <span>Cache Duration (TTL)</span>
                <span class="text-amber-400">⏱️</span>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-amber-300">
                {{ $ttlMinutes }} <span class="text-sm font-semibold text-slate-400">min</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Configurable via panel below
            </div>
        </div>

        <!-- Metric 4: Districts with Cached Data -->
        <div class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 text-xs font-semibold">
                <span>Districts Covered</span>
                <span class="text-teal-400">📍</span>
            </div>
            <div class="mt-2 text-2xl sm:text-3xl font-black text-teal-300">
                {{ $stats['districts_covered'] }} <span class="text-sm font-semibold text-slate-400">/ 31</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Karnataka district coverage
            </div>
        </div>
    </div>

    <!-- Management Controls: Cache TTL & Bulk Pruning Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Card 1: Cache Duration / TTL Setting -->
        <div class="p-6 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl flex flex-col justify-between space-y-5">
            <div>
                <div class="flex items-center gap-3 border-b border-slate-800/80 pb-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-950/40 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg shrink-0">
                        ⏱️
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-tight">Weather Cache Duration (TTL)</h3>
                        <p class="text-xs text-slate-400">Define how long fetched forecasts remain valid before an on-demand re-sync.</p>
                    </div>
                </div>

                <form action="{{ route('admin.weather.settings') }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="cache_ttl_minutes" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                            Freshness Expiry Window (in Minutes)
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="number" 
                                   id="cache_ttl_minutes" 
                                   name="cache_ttl_minutes" 
                                   value="{{ old('cache_ttl_minutes', $ttlMinutes) }}" 
                                   min="1" 
                                   max="1440" 
                                   required 
                                   class="w-36 px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-base font-bold focus:outline-none focus:border-amber-400">
                            <span class="text-xs text-slate-400">minutes (e.g. 15 = 15m)</span>
                        </div>
                    </div>

                    <!-- Preset Quick Pickers -->
                    <div class="space-y-1.5 pt-1">
                        <span class="text-[11px] font-semibold text-slate-400">Common Presets:</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach([10, 15, 30, 60, 180, 360] as $preset)
                                <button type="button" 
                                        onclick="document.getElementById('cache_ttl_minutes').value = {{ $preset }}"
                                        class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-amber-500/50 text-[11px] font-mono font-bold {{ $ttlMinutes == $preset ? 'text-amber-400 border-amber-500/80 bg-amber-950/20' : 'text-slate-300' }} transition">
                                    {{ $preset }}m {{ $preset == 15 ? '(Default)' : '' }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 text-[11px] text-slate-400 leading-relaxed">
                        💡 Setting this to <strong class="text-white">15 minutes</strong> ensures farmers always see fresh live weather conditions while keeping Open-Meteo API requests strictly below free tier limits (10,000 requests/day).
                    </div>

                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition shadow-md">
                            Save Cache TTL Duration
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Bulk Pruning & Storage Purge -->
        <div class="p-6 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl flex flex-col justify-between space-y-5">
            <div>
                <div class="flex items-center gap-3 border-b border-slate-800/80 pb-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-950/40 border border-rose-500/30 flex items-center justify-center text-rose-400 text-lg shrink-0">
                        🧹
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-tight">Weather Storage Cleanup & Bulk Pruning</h3>
                        <p class="text-xs text-slate-400">Prevent database bloat by pruning expired or historical forecast data.</p>
                    </div>
                </div>

                <div class="mt-4 space-y-2 text-xs text-slate-400">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/70 border border-slate-800">
                        <span>Oldest Forecast In Storage:</span>
                        <span class="font-mono text-white font-bold">{{ $stats['oldest_date'] ? \Carbon\Carbon::parse($stats['oldest_date'])->format('d M Y') : 'None' }}</span>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/70 border border-slate-800">
                        <span>Latest Forecast In Storage:</span>
                        <span class="font-mono text-white font-bold">{{ $stats['newest_date'] ? \Carbon\Carbon::parse($stats['newest_date'])->format('d M Y') : 'None' }}</span>
                    </div>
                </div>

                <form action="{{ route('admin.weather.prune') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                            Select Pruning Scope:
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
                                <input type="radio" name="prune_mode" value="7_days" x-model="selectedPruneMode" class="text-rose-500 focus:ring-0">
                                <div>
                                    <div class="text-xs font-bold text-white">Delete forecasts older than 7 days (Recommended)</div>
                                    <div class="text-[11px] text-slate-400">Cleans expired forecasts while preserving current upcoming week.</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
                                <input type="radio" name="prune_mode" value="30_days" x-model="selectedPruneMode" class="text-rose-500 focus:ring-0">
                                <div>
                                    <div class="text-xs font-bold text-white">Delete forecasts older than 30 days</div>
                                    <div class="text-[11px] text-slate-400">Retains last month of past daily weather records.</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 rounded-xl bg-rose-950/20 border border-rose-900/50 hover:border-rose-700 cursor-pointer transition">
                                <input type="radio" name="prune_mode" value="all" x-model="selectedPruneMode" class="text-rose-500 focus:ring-0">
                                <div>
                                    <div class="text-xs font-bold text-rose-300">Purge ALL Weather Records (Emergency Reset)</div>
                                    <div class="text-[11px] text-rose-400/80">Completely clears the weather forecasts table. New data will re-sync on demand.</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" 
                                :onclick="selectedPruneMode === 'all' ? 'return confirm(\'CAUTION: Are you sure you want to PURGE ALL stored weather forecasts? This cannot be undone!\')' : 'return confirm(\'Proceed with pruning historical weather forecasts?\')'"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs transition shadow-md">
                            Execute Bulk Cleanup
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Karnataka Districts Real-time Cache Status Grid -->
    <div class="p-6 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                    <span>📍</span>
                    <span>District Cache Status & Manual Micro-Sync</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Live status of all 31 Karnataka administrative districts with direct on-demand re-sync triggers.</p>
            </div>

            <!-- Quick Filter -->
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
                <input type="text" 
                       x-model="filterQuery" 
                       placeholder="Filter district..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-3 px-3">District</th>
                        <th class="py-3 px-3">Cached Rows</th>
                        <th class="py-3 px-3">Current Temp</th>
                        <th class="py-3 px-3">Last Fetched</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($districts as $d)
                        <tr id="district-row-{{ $d->id }}" 
                            class="hover:bg-slate-900/50 transition"
                            x-show="!filterQuery || '{{ strtolower($d->name) }}'.includes(filterQuery.toLowerCase()) || '{{ $d->name_kn }}'.includes(filterQuery)">
                            <td class="py-3 px-3 font-bold text-white flex items-center gap-2">
                                <span>{{ $d->name }}</span>
                                <span class="text-slate-400 font-normal font-kannada">({{ $d->name_kn }})</span>
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-300">
                                <span class="district-count-display">{{ $d->weather_forecasts_count }}</span> days
                            </td>
                            <td class="py-3 px-3 font-medium">
                                <span class="district-temp-display">
                                    @if($d->current_temp !== null)
                                        <span class="text-amber-300 font-bold font-mono">{{ round($d->current_temp) }}°C</span>
                                        <span class="text-slate-400 text-[11px]">· {{ $d->current_condition }}</span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-400 font-mono text-[11px]">
                                <span class="district-fetch-display">
                                    {{ $d->latest_fetch ? \Carbon\Carbon::parse($d->latest_fetch)->format('d M H:i') : 'Never' }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="district-status-badge px-2 py-0.5 rounded-full text-[10px] font-bold 
                                    {{ $d->status_color === 'emerald' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : '' }}
                                    {{ $d->status_color === 'amber' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : '' }}
                                    {{ $d->status_color === 'rose' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : '' }}
                                    {{ $d->status_color === 'slate' ? 'bg-slate-800 text-slate-400' : '' }}">
                                    ● {{ $d->status_text }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button type="button" 
                                        @click="syncSingleDistrict({{ $d->id }}, '{{ addslashes($d->name) }}', $event)"
                                        class="district-sync-btn px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-emerald-600 text-slate-300 hover:text-white text-[11px] font-bold transition shadow-xs cursor-pointer">
                                    Sync Now
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">
                                No Karnataka districts registered.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- ASYNC BATCH RUNNER PROGRESS MODAL                              -->
    <!-- ============================================================== -->
    <div x-show="isBatchSyncing" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm select-none"
         style="display: none;">
        
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5 text-white animate-in fade-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 text-lg">
                        ⚡
                    </div>
                    <div>
                        <h4 class="text-base font-black tracking-tight">Async Weather Synchronization</h4>
                        <p class="text-xs text-slate-400">Syncing Open-Meteo forecasts in real-time batches</p>
                    </div>
                </div>

                <button type="button" 
                        x-show="isFinished"
                        @click="isBatchSyncing = false; window.location.reload();"
                        class="text-slate-400 hover:text-white text-sm font-bold p-1 rounded-lg hover:bg-slate-800 transition">
                    ✕
                </button>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-xs font-mono">
                    <span class="text-slate-300 font-semibold" x-text="syncStatusMessage"></span>
                    <span class="text-emerald-400 font-bold" x-text="syncProgress + '%'"></span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-950 border border-slate-800 overflow-hidden relative">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-300 ease-out"
                         :style="'width: ' + syncProgress + '%'"></div>
                </div>
            </div>

            <!-- Stats Counter Tiles -->
            <div class="grid grid-cols-3 gap-2.5 text-center text-xs">
                <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                    <span class="text-slate-500 text-[10px] block uppercase font-bold">Processed</span>
                    <span class="text-base font-black text-white" x-text="syncCurrentIndex + ' / ' + syncTotal"></span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-950 border border-emerald-950">
                    <span class="text-emerald-500 text-[10px] block uppercase font-bold">Success</span>
                    <span class="text-base font-black text-emerald-400" x-text="syncSuccessCount"></span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-950 border border-rose-950">
                    <span class="text-rose-500 text-[10px] block uppercase font-bold">Failed</span>
                    <span class="text-base font-black text-rose-400" x-text="syncFailedCount"></span>
                </div>
            </div>

            <!-- Live Streaming Log Box -->
            <div class="space-y-1.5">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Live Activity Stream:</span>
                <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800/80 font-mono text-[11px] space-y-1 max-h-40 overflow-y-auto">
                    <template x-for="(log, idx) in syncLogs" :key="idx">
                        <div :class="log.status === 'success' ? 'text-emerald-400' : 'text-rose-400'" class="truncate">
                            <span x-text="log.text"></span>
                        </div>
                    </template>
                    <div x-show="syncLogs.length === 0" class="text-slate-600">
                        Starting async pipeline...
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-800">
                <button type="button" 
                        x-show="!isFinished"
                        @click="isBatchSyncing = false"
                        class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition">
                    Run in Background (Hide Modal)
                </button>
                <button type="button" 
                        x-show="isFinished"
                        @click="isBatchSyncing = false; window.location.reload();"
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-black text-white transition shadow-md">
                    Done & Refresh Data
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function weatherAdminManager() {
    return {
        selectedPruneMode: '7_days',
        filterQuery: '',
        isBatchSyncing: false,
        isFinished: false,
        syncProgress: 0,
        syncTotal: 0,
        syncCurrentIndex: 0,
        syncStatusMessage: 'Initializing...',
        syncSuccessCount: 0,
        syncFailedCount: 0,
        syncLogs: [],
        districtsPayload: @json($districts->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'name_kn' => $d->name_kn])),

        async startBatchSync() {
            if (!confirm('Start real-time asynchronous weather sync for all ' + this.districtsPayload.length + ' Karnataka districts? This executes safely without web request timeouts.')) {
                return;
            }

            this.isBatchSyncing = true;
            this.isFinished = false;
            this.syncProgress = 0;
            this.syncTotal = this.districtsPayload.length;
            this.syncCurrentIndex = 0;
            this.syncSuccessCount = 0;
            this.syncFailedCount = 0;
            this.syncLogs = [];
            this.syncStatusMessage = 'Connecting to Open-Meteo...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || '{{ csrf_token() }}';

            for (let i = 0; i < this.districtsPayload.length; i++) {
                if (!this.isBatchSyncing && !this.isFinished) {
                    // Allowed to run in background
                }
                const district = this.districtsPayload[i];
                this.syncCurrentIndex = i + 1;
                this.syncStatusMessage = `Syncing ${district.name} (${district.name_kn})...`;
                this.syncProgress = Math.round(((i + 1) / this.syncTotal) * 100);

                try {
                    const response = await fetch(`{{ url('admin/weather/sync-district') }}/${district.id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    const data = await response.json();
                    if (data && data.success) {
                        this.syncSuccessCount++;
                        this.syncLogs.unshift({
                            status: 'success',
                            text: `✓ ${district.name}: Synced ${data.forecasts_count || 7} days (${data.temp !== undefined ? Math.round(data.temp) + '°C' : 'Ready'})`
                        });
                        this.updateRow(district.id, data);
                    } else {
                        throw new Error(data?.message || 'Sync response indicated error');
                    }
                } catch (err) {
                    this.syncFailedCount++;
                    this.syncLogs.unshift({
                        status: 'error',
                        text: `✕ ${district.name}: ${err.message || 'Network error'}`
                    });
                }

                // Short 50ms pause between district calls to avoid micro-rate-limiting
                await new Promise(r => setTimeout(r, 60));
            }

            this.isFinished = true;
            this.syncStatusMessage = `Completed! ${this.syncSuccessCount} districts synced successfully.`;
        },

        async syncSingleDistrict(districtId, districtName, event) {
            const btn = event?.currentTarget;
            const originalText = btn ? btn.innerText : 'Sync Now';
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Syncing...';
                btn.classList.add('opacity-75');
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || '{{ csrf_token() }}';

            try {
                const response = await fetch(`{{ url('admin/weather/sync-district') }}/${districtId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();
                if (data && data.success) {
                    this.updateRow(districtId, data);
                    if (btn) {
                        btn.innerText = '✓ Done';
                        btn.classList.remove('bg-slate-800', 'opacity-75');
                        btn.classList.add('bg-emerald-600', 'text-white');
                        setTimeout(() => {
                            btn.innerText = originalText;
                            btn.disabled = false;
                            btn.classList.remove('bg-emerald-600', 'text-white');
                            btn.classList.add('bg-slate-800');
                        }, 2500);
                    }
                } else {
                    alert('Sync failed for ' + districtName + ': ' + (data?.message || 'Unknown error'));
                    if (btn) {
                        btn.disabled = false;
                        btn.innerText = originalText;
                        btn.classList.remove('opacity-75');
                    }
                }
            } catch (err) {
                alert('Sync error: ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    btn.classList.remove('opacity-75');
                }
            }
        },

        updateRow(districtId, data) {
            const row = document.getElementById('district-row-' + districtId);
            if (!row) return;

            const tempSpan = row.querySelector('.district-temp-display');
            if (tempSpan && data.temp !== undefined) {
                tempSpan.innerHTML = `<span class="text-amber-300 font-bold font-mono">${Math.round(data.temp)}°C</span> <span class="text-slate-400 text-[11px]">· ${data.condition || ''}</span>`;
            }

            const fetchSpan = row.querySelector('.district-fetch-display');
            if (fetchSpan && data.fetched_at) {
                fetchSpan.innerText = data.fetched_at;
            }

            const countSpan = row.querySelector('.district-count-display');
            if (countSpan && data.forecasts_count) {
                countSpan.innerText = data.forecasts_count;
            }

            const badgeSpan = row.querySelector('.district-status-badge');
            if (badgeSpan) {
                badgeSpan.className = 'district-status-badge px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold';
                badgeSpan.innerHTML = '● Fresh (Just now)';
            }
        }
    };
}
</script>
@endsection
