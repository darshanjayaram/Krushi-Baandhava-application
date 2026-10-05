@extends('layouts.admin')

@section('title', 'Daily Market Prices')

@section('content')
<div class="space-y-6" x-data="pricesManager()">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>💰</span> Daily Market Prices
                <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-950/80 text-emerald-300 border border-emerald-800/60 rounded-full font-kannada">ದೈನಂದಿನ ಬೆಲೆಗಳು</span>
            </h1>
            <p class="text-sm text-slate-400 font-medium">Canonicalized, deduplicated APMC mandi price feeds from Karnataka State APMC (KRAMA), Official AGMARKNET, data.gov.in, TSS Sirsi, and commodity boards.</p>
        </div>
        <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
            <button @click="archiveDrawerOpen = !archiveDrawerOpen" type="button" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-200 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 transition shadow-sm cursor-pointer">
                <span>📅 Archive</span>
                <span class="text-[10px] px-1.5 py-0.5 bg-slate-800 text-cyan-400 rounded-md font-mono" x-text="archiveDrawerOpen ? '▲' : '▼'"></span>
            </button>
            <button @click="openPruneModal()" type="button" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-rose-300 bg-slate-900 border border-rose-900/60 rounded-xl hover:bg-rose-950/60 transition shadow-sm cursor-pointer">
                <span>🗑️ Prune</span>
            </button>
            <a href="{{ route('admin.sync-logs.index') }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-200 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 transition shadow-sm">
                <span>📜 Logs</span>
            </a>
            <button @click="resetSyncModal(); syncModalOpen = true" type="button" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer">
                <span>⚡ Run Sync</span>
            </button>
        </div>
    </div>

    <!-- Metrics Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Canonical Records</span>
            <div class="text-3xl font-extrabold text-white mt-1">{{ number_format($totalPrices) }}</div>
            <div class="text-xs font-medium text-emerald-400 mt-1">Canonical Prices &bull; Deduplicated at rest (SHA-256)</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Latest Date Records</span>
            <div class="text-3xl font-extrabold text-emerald-400 mt-1">{{ number_format($latestCount) }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Date: {{ \Carbon\Carbon::parse($latestDate)->format('d M Y') }}</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Mandis</span>
            <div class="text-3xl font-extrabold text-cyan-400 mt-1">{{ $activeMarketsCount }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Reporting on latest date</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Avg Price Spread</span>
            <div class="text-3xl font-extrabold text-amber-400 mt-1">₹{{ number_format($avgSpread, 0) }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Max - Min spread across mandis</div>
        </div>
    </div>

    <!-- Archive & Volume Breakdown Drawer (Collapsible) -->
    <div x-show="archiveDrawerOpen" x-transition.opacity class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div>
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <span>📅</span> Historical Data Archive & Storage Footprint
                </h3>
                <p class="text-xs text-slate-400">Monthly breakdown of recorded trades, active markets, and estimated database disk usage.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-emerald-400 bg-emerald-950/80 px-2.5 py-1 rounded-lg border border-emerald-800/60">
                    Est. Total Size: ~{{ round(($totalPrices * 300) / (1024 * 1024), 1) }} MB
                </span>
                <button type="button" @click="archiveDrawerOpen = false" class="text-slate-400 hover:text-white text-base cursor-pointer">✕</button>
            </div>
        </div>

        @if($archiveBreakdown->isEmpty())
            <div class="py-8 text-center text-slate-500 text-xs">No historical monthly records compiled yet.</div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/80 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Period</th>
                            <th class="py-3 px-4 text-center">Daily Records</th>
                            <th class="py-3 px-4 text-center">Active Mandis</th>
                            <th class="py-3 px-4 text-center">Crops</th>
                            <th class="py-3 px-4 text-right">Avg Modal Price</th>
                            <th class="py-3 px-4 text-right">Est. Disk Usage</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @foreach($archiveBreakdown as $row)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-white flex items-center gap-1.5">
                                        <span>📅</span> {{ $row['month_name_en'] }} {{ $row['year'] }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-kannada">{{ $row['month_name_kn'] }} {{ $row['year'] }}</div>
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-white">
                                    {{ number_format($row['records_count']) }}
                                </td>
                                <td class="py-3 px-4 text-center text-cyan-400">
                                    {{ $row['mandis_count'] }}
                                </td>
                                <td class="py-3 px-4 text-center text-amber-400">
                                    {{ $row['crops_count'] }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-400">
                                    ₹{{ number_format($row['avg_modal'], 2) }}
                                </td>
                                <td class="py-3 px-4 text-right text-slate-400 font-mono">
                                    ~{{ $row['est_size_mb'] }} MB
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap space-x-2">
                                    <a href="{{ route('admin.prices.index', ['year' => $row['year'], 'month' => $row['month']]) }}" 
                                       class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-bold border border-slate-700 transition"
                                       title="Filter Table for This Month">
                                        <span>🔍 Filter</span>
                                    </a>
                                    <button type="button" 
                                            @click="openPruneForPeriod({{ $row['year'] }}, {{ $row['month'] }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-950 hover:bg-rose-900 text-rose-300 rounded-lg text-[11px] font-bold border border-rose-800/60 transition cursor-pointer"
                                            title="Prune this month's daily records">
                                        <span>🗑️ Prune</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.prices.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Search Text -->
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search Crop / Mandi</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}" placeholder="e.g. Arecanut, Shimoga, Sagar..."
                            class="w-full text-xs font-medium pl-8 pr-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <span class="absolute left-2.5 top-2.5 text-slate-500 text-xs">🔍</span>
                    </div>
                </div>

                <!-- Crop Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Crop</label>
                    <select name="crop_id" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <option value="">All Crops</option>
                        @foreach($crops as $c)
                            <option value="{{ $c->id }}" {{ $cropId == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->name_kn }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- District Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">District</label>
                    <select name="district_id" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <option value="">All Districts</option>
                        @foreach($districts as $d)
                            <option value="{{ $d->id }}" {{ $districtId == $d->id ? 'selected' : '' }}>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Year Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Year</label>
                    <select name="year" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <option value="">All Years</option>
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Month Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Month</label>
                    <select name="month" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <option value="">All Months</option>
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $mNameEn = date('F', mktime(0, 0, 0, $m, 1));
                            @endphp
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ str_pad($m, 2, '0', STR_PAD_LEFT) }} - {{ $mNameEn }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Second Row: Feed Source, Single Date & Filter Action Buttons -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pt-3 border-t border-slate-800/80">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Data Source Filter -->
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 shrink-0">Feed Source:</span>
                        <select name="data_source_id" class="text-xs font-medium px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                            <option value="">All Sources</option>
                            @foreach($dataSources as $ds)
                                <option value="{{ $ds->id }}" {{ $dataSourceId == $ds->id ? 'selected' : '' }}>
                                    {{ $ds->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Exact Date Filter -->
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 shrink-0">Exact Date:</span>
                        <input type="date" name="date" value="{{ $date }}"
                            class="text-xs font-medium px-3 py-1.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        @if($date)
                            <button type="button" onclick="this.form.date.value=''; this.form.submit();" class="text-slate-400 hover:text-white text-xs font-bold transition" title="Clear Date">
                                ✕
                            </button>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($search || $cropId || $marketId || $districtId || $dataSourceId || $date || $year || $month)
                        <a href="{{ route('admin.prices.index') }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition">
                            ✕ Reset Filters
                        </a>
                    @endif
                    <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer">
                        Filter Records
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filter Indicators -->
    @if($year || $month || $date)
        <div class="flex items-center gap-2 px-1 text-xs text-slate-400">
            <span class="font-bold text-white">Active Period Filter:</span>
            @if($year)
                <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-cyan-300 border border-slate-700 font-mono text-[11px]">Year: {{ $year }}</span>
            @endif
            @if($month)
                <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-cyan-300 border border-slate-700 font-mono text-[11px]">Month: {{ date('F', mktime(0, 0, 0, $month, 1)) }}</span>
            @endif
            @if($date)
                <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-emerald-300 border border-slate-700 font-mono text-[11px]">Date: {{ $date }}</span>
            @endif
        </div>
    @endif

    <!-- Data Table Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <!-- Mobile Table Swipe Hint -->
        <div class="sm:hidden flex items-center justify-between px-3.5 py-2 bg-slate-950/70 border-b border-slate-800 text-[11px] text-slate-400">
            <span class="flex items-center gap-1.5 font-medium"><span>👈</span> <span>Swipe table horizontally</span> <span>👉</span></span>
            <span class="text-emerald-400 font-bold">Rates & Actions on right</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Crop & Commodity</th>
                        <th class="py-3 px-4">Mandi & District</th>
                        <th class="py-3 px-4">Variety / Grade</th>
                        <th class="py-3 px-4 text-right">Modal Price</th>
                        <th class="py-3 px-4 text-center">Feed Source</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($prices as $price)
                        <tr class="hover:bg-slate-800/50 transition">
                            <!-- Date -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-mono text-slate-200">{{ \Carbon\Carbon::parse($price->price_date)->format('d M Y') }}</span>
                                <div class="text-[10px] text-slate-500 font-mono">{{ \Carbon\Carbon::parse($price->price_date)->format('D') }}</div>
                            </td>

                            <!-- Crop -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white text-sm">{{ $price->crop->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-400 font-kannada">{{ $price->crop->name_kn ?? '' }}</div>
                            </td>

                            <!-- Mandi & District -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-200">{{ $price->market->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $price->market->district->name ?? 'Unknown District' }}</div>
                            </td>

                            <!-- Variety -->
                            <td class="py-3.5 px-4">
                                @if($price->variety)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-800 text-slate-200 border border-slate-700">
                                        {{ $price->variety->name }}
                                    </span>
                                @else
                                    <span class="text-slate-500 italic text-[11px]">General / Unspecified</span>
                                @endif
                            </td>

                            <!-- Modal Price -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="font-extrabold text-base text-emerald-400">
                                    ₹{{ number_format($price->modal_price, 2) }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    ₹{{ number_format($price->min_price, 0) }} - ₹{{ number_format($price->max_price, 0) }}
                                    / {{ $price->unit ?? 'Qtl' }}
                                </div>
                            </td>

                            <!-- Source -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                                    {{ $price->dataSource->name ?? 'Feed API' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.crops.edit', $price->crop_id) }}?tab=varieties" 
                                   class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/60 transition"
                                   title="Manage Varieties & Feed Mapping">
                                    <span>🏷️ Varieties</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">🌾</div>
                                <div class="text-base font-bold text-white">No market price records found</div>
                                <p class="text-xs text-slate-400 mt-1">Try changing filters or run an on-demand sync from external feeds.</p>
                                <button @click="resetSyncModal(); syncModalOpen = true" type="button" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer">
                                    <span>⚡</span> Run Ingestion Sync
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($prices->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $prices->links() }}
            </div>
        @endif
    </div>

    <!-- Upgraded Ingestion Sync & Historical Backfill Modal -->
    <div x-show="syncModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         @keydown.escape.window="syncModalOpen = false">
        
        <!-- Dark Dimming Backdrop -->
        <div x-show="syncModalOpen"
             x-transition.opacity
             class="fixed inset-0 bg-black/80"
             @click="syncModalOpen = false"></div>

        <!-- Modal Box -->
        <div x-show="syncModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 bg-slate-900 rounded-2xl max-w-xl w-full p-4 sm:p-6 shadow-2xl border border-slate-700 text-white my-auto max-h-[90vh] overflow-y-auto"
             @click.away="syncConsoleState === 'syncing' ? null : syncModalOpen = false">
            
            <!-- Dynamic Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span x-show="syncConsoleState === 'config'">⚡ Ingestion Sync & Historical Backfill</span>
                        <span x-show="syncConsoleState === 'syncing'" class="inline-flex items-center gap-2 text-emerald-400">
                            <svg class="animate-spin h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Ingesting Market Feeds...
                        </span>
                        <span x-show="syncConsoleState === 'completed'" class="text-emerald-400">✅ Ingestion Run Completed</span>
                        <span x-show="syncConsoleState === 'error'" class="text-rose-400">❌ Ingestion Run Failed</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <span x-show="syncConsoleState === 'config'">Sync live daily prices or backfill historical Karnataka APMC records.</span>
                        <span x-show="syncConsoleState === 'syncing'">Connecting to upstream feeds, normalizing varieties, deduplicating with SHA-256...</span>
                        <span x-show="syncConsoleState === 'completed'">Telemetry summary, canonical write metrics, and entity resolution status.</span>
                        <span x-show="syncConsoleState === 'error'">Encountered an issue executing the ingestion pipeline.</span>
                    </p>
                </div>
                <button :disabled="syncConsoleState === 'syncing'" 
                        @click="syncModalOpen = false" 
                        class="text-slate-400 hover:text-white text-lg font-bold cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">✕</button>
            </div>

            <!-- STATE 1: CONFIG -->
            <div x-show="syncConsoleState === 'config'">
                <!-- Mode Switcher Tabs -->
                <div class="flex rounded-xl bg-slate-950 p-1 border border-slate-800 mt-4">
                    <button type="button" 
                            @click="syncMode = 'single'"
                            class="flex-1 py-1.5 text-xs font-bold rounded-lg transition"
                            :class="syncMode === 'single' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'">
                        Single Trading Date
                    </button>
                    <button type="button" 
                            @click="syncMode = 'range'"
                            class="flex-1 py-1.5 text-xs font-bold rounded-lg transition"
                            :class="syncMode === 'range' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'">
                        Date Range (Historical Backfill)
                    </button>
                </div>

                <!-- Single Date Form -->
                <form x-show="syncMode === 'single'" @submit.prevent="runSync('single')" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Target Feed</label>
                        <select name="data_source_id" x-model="selectedSource" @change="onSourceChange()" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="">All Active Sources (Batch)</option>
                            @foreach($dataSources as $ds)
                                <option value="{{ $ds->id }}">{{ $ds->name }} ({{ $ds->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Target Crop (Optional)</label>
                            <span class="text-[10px] text-emerald-400 font-medium" x-text="availableCrops.length + ' configured sync crops'"></span>
                        </div>
                        <select name="crop_id" x-model="selectedCrop" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="" x-text="availableCrops.length > 0 ? 'All Configured Crops (' + availableCrops.length + ')' : 'All Configured Crops'"></option>
                            <template x-for="c in availableCrops" :key="c.id">
                                <option :value="c.id" x-text="c.name + (c.name_kn ? ' (' + c.name_kn + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Target Date</label>
                        <input type="date" name="target_date" x-model="targetDate" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <!-- Single Sync Force Option -->
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="force" id="force_sync_single" value="1" x-model="forceSync" class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500">
                        <label for="force_sync_single" class="text-xs text-amber-300 font-medium">
                            ⚡ <strong>Force Re-sync & Overwrite:</strong> Re-process and update records even if already ingested
                        </label>
                    </div>

                    <div class="bg-emerald-950/40 p-3 rounded-xl text-xs text-emerald-300 font-medium leading-relaxed border border-emerald-800/60">
                        💡 Pulls auction records for the selected date. Automatically skips duplicates via SHA-256 checksums unless Force Re-sync is checked.
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="syncModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 rounded-xl hover:bg-slate-700 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer">
                            Start Single Sync
                        </button>
                    </div>
                </form>

                <!-- Date Range Backfill Form -->
                <form x-show="syncMode === 'range'" @submit.prevent="runSync('range')" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Target Feed</label>
                        <select name="data_source_id" x-model="selectedSource" @change="onSourceChange()" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="">All Active Sources (Batch)</option>
                            @foreach($dataSources as $ds)
                                <option value="{{ $ds->id }}">{{ $ds->name }} ({{ $ds->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Official AGMARKNET CAPTCHA Box (Visible when Official Agmarknet is chosen) -->
                    <div x-show="isAgmarknetSourceSelected()" class="bg-slate-950 border border-purple-500/40 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-purple-300 flex items-center gap-1.5">
                                <span>🛡️</span> Security Verification (Official AGMARKNET API)
                            </span>
                            <button type="button" @click="refreshAgmarknetCaptcha()" class="text-xs text-purple-400 hover:text-purple-300 font-bold flex items-center gap-1 cursor-pointer">
                                <span :class="agmarknetCaptchaLoading ? 'animate-spin inline-block' : ''">🔄</span>
                                <span>Refresh Image</span>
                            </button>
                        </div>

                        <!-- Image container -->
                        <div class="h-16 bg-white/95 rounded-xl border border-slate-700/60 flex items-center justify-center p-2 overflow-hidden shadow-inner">
                            <template x-if="agmarknetCaptchaLoading">
                                <span class="text-slate-500 text-xs font-medium animate-pulse">Generating security CAPTCHA...</span>
                            </template>
                            <template x-if="!agmarknetCaptchaLoading && agmarknetCaptchaImage">
                                <img :src="agmarknetCaptchaImage" alt="AGMARKNET Security CAPTCHA" class="h-12 object-contain select-none">
                            </template>
                        </div>

                        <!-- Captcha Input (Case-sensitive) -->
                        <div>
                            <input type="text" 
                                   x-model="agmarknetCaptchaCode" 
                                   maxlength="8" 
                                   placeholder="Type the 6 characters exactly (case-sensitive)..." 
                                   autocomplete="off"
                                   autocorrect="off"
                                   autocapitalize="off"
                                   spellcheck="false"
                                   class="w-full bg-slate-900 border border-slate-700 text-center tracking-widest text-sm font-mono font-black text-amber-300 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-purple-500 placeholder:normal-case placeholder:text-slate-500 placeholder:text-xs placeholder:tracking-normal">
                            <p class="text-[10px] text-slate-400 text-center mt-1">
                                Aa Case-Sensitive: Match uppercase & lowercase letters exactly as shown in the image above.
                            </p>
                        </div>

                        <p class="text-[11px] text-purple-300/80 leading-relaxed">
                            💡 Directorate of Marketing & Inspection (DMI) requires this quick 6-character verification to unlock multi-year auction archives.
                        </p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Target Crop (Optional)</label>
                            <span class="text-[10px] text-emerald-400 font-medium" x-text="availableCrops.length + ' configured sync crops'"></span>
                        </div>
                        <select name="crop_id" x-model="selectedCrop" class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="" x-text="availableCrops.length > 0 ? 'All Configured Crops (' + availableCrops.length + ')' : 'All Configured Crops'"></option>
                            <template x-for="c in availableCrops" :key="c.id">
                                <option :value="c.id" x-text="c.name + (c.name_kn ? ' (' + c.name_kn + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Date Range Calendar Pickers -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">From Date</label>
                            <input type="date" name="from_date" x-model="fromDate" required class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">To Date</label>
                            <input type="date" name="to_date" x-model="toDate" required class="w-full text-xs font-medium px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- 1-Click Quick Presets -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Quick Range Presets (Up to 6 Years)</label>
                            <span class="text-[10px] text-emerald-400 font-medium">Full Multi-Year Support</span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button type="button" @click="setPreset(30)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition cursor-pointer">
                                30 Days
                            </button>
                            <button type="button" @click="setPreset(90)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition cursor-pointer">
                                90 Days
                            </button>
                            <button type="button" @click="setPreset(365)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition cursor-pointer">
                                1 Year (365d)
                            </button>
                            <button type="button" @click="setPreset(1095)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition cursor-pointer">
                                3 Years
                            </button>
                            <button type="button" @click="setPreset(1825)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition cursor-pointer">
                                5 Years
                            </button>
                            <button type="button" @click="setPreset(2190)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-950/90 hover:bg-emerald-900 text-emerald-300 border border-emerald-700 shadow-sm transition cursor-pointer">
                                ★ 6 Years (2,190d)
                            </button>
                        </div>
                    </div>

                    <!-- Analytics Pre-Calculation Option -->
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="update_analytics" id="update_analytics" value="1" x-model="updateAnalytics" class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                        <label for="update_analytics" class="text-xs text-slate-300 font-medium">
                            Automatically update Monthly Statistics & 4-Horizon Price Forecasts
                        </label>
                    </div>

                    <!-- Force Re-sync Option -->
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="force" id="force_sync_range" value="1" x-model="forceSync" class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500">
                        <label for="force_sync_range" class="text-xs text-amber-300 font-medium">
                            ⚡ <strong>Force Re-sync & Overwrite:</strong> Bypass SHA-256 deduplication and re-process existing records (updates prices & recalculates analytics)
                        </label>
                    </div>

                    <div class="bg-cyan-950/40 p-3 rounded-xl text-xs text-cyan-300 font-medium leading-relaxed border border-cyan-800/60">
                        🚀 <strong>Multi-Year Historical Backfill (Up to 6 Years):</strong> Pulls multi-year auction trade archives directly from Official AGMARKNET / KRAMA feeds. Automatically computes 5-year rolling seasonal baselines, accurately maps peak selling months, and powers 4-horizon price forecasts.
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="syncModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 rounded-xl hover:bg-slate-700 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer">
                            🚀 Start Backfill Sync
                        </button>
                    </div>
                </form>
            </div>

            <!-- STATE 2: SYNCING (LIVE REAL-TIME STREAMING CONSOLE) -->
            <div x-show="syncConsoleState === 'syncing'" class="py-5 space-y-4">
                <!-- Elapsed Time Badge & Mode -->
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-950/80 border border-emerald-800/80 rounded-full text-xs font-bold text-emerald-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span x-text="syncMode === 'range' ? 'Historical Range Backfill' : 'Single Date Feed Ingestion'"></span>
                        </span>
                        <template x-if="syncMode === 'range' && totalSlices > 1">
                            <span class="px-2.5 py-0.5 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-800 text-[11px] font-mono font-bold"
                                  x-text="'Batch ' + (currentSliceIndex + 1) + ' of ' + totalSlices">
                            </span>
                        </template>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-400 bg-slate-950 px-2.5 py-1 rounded-lg border border-slate-800">
                        ⏱️ <span x-text="syncElapsedSeconds">0.0</span>s elapsed
                    </span>
                </div>

                <!-- Animated Glowing Progress Bar -->
                <div class="space-y-1.5 bg-slate-950/60 p-3.5 rounded-2xl border border-slate-800/80">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-200 truncate max-w-sm flex items-center gap-1.5">
                            <span class="animate-spin text-emerald-400" x-show="syncProgressPercent < 100">⟳</span>
                            <span x-text="syncProgressStage">Connecting to Mandi Feeds...</span>
                        </span>
                        <span class="text-emerald-400 font-mono text-sm font-black" x-text="syncProgressPercent + '%'">0%</span>
                    </div>
                    <div class="w-full bg-slate-900 border border-slate-800 rounded-full h-3 p-0.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 h-full rounded-full transition-all duration-300 shadow-[0_0_12px_rgba(16,185,129,0.5)]"
                             :style="'width: ' + syncProgressPercent + '%'"></div>
                    </div>
                </div>

                <!-- Live Cumulative Counters (Ticks up in real-time) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div class="bg-slate-950/90 border border-slate-800/80 rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Feeds Ingested</div>
                        <div class="text-lg font-black text-blue-400 mt-0.5" x-text="(syncSummary.received || 0).toLocaleString()">0</div>
                    </div>
                    <div class="bg-slate-950/90 border border-slate-800/80 rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">New Inserted</div>
                        <div class="text-lg font-black text-emerald-400 mt-0.5" x-text="(syncSummary.inserted || 0).toLocaleString()">0</div>
                    </div>
                    <div class="bg-slate-950/90 border border-slate-800/80 rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-cyan-400">Updated</div>
                        <div class="text-lg font-black text-cyan-400 mt-0.5" x-text="(syncSummary.updated || 0).toLocaleString()">0</div>
                    </div>
                    <div class="bg-slate-950/90 border border-slate-800/80 rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Deduplicated</div>
                        <div class="text-lg font-black text-slate-300 mt-0.5" x-text="(syncSummary.duplicate || 0).toLocaleString()">0</div>
                    </div>
                </div>

                <!-- Real-Time Activity Feed Terminal (Live Logs Streaming) -->
                <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-inner">
                    <div class="px-3.5 py-2 bg-slate-900/90 border-b border-slate-800/80 flex items-center justify-between text-xs font-bold">
                        <div class="flex items-center gap-2 text-slate-300">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>Live Ingestion Activity Stream</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-mono" x-text="syncLiveLogs.length + ' events logged'"></span>
                    </div>
                    <div class="p-3 max-h-44 overflow-y-auto space-y-1.5 font-mono text-[11px] scrollbar-thin" id="syncLiveLogContainer">
                        <template x-for="(log, idx) in syncLiveLogs" :key="idx">
                            <div class="flex items-start gap-2 leading-relaxed">
                                <span class="text-slate-500 text-[10px] shrink-0 font-mono" x-text="'[' + log.time + ']'"></span>
                                <span :class="log.type === 'success' ? 'text-emerald-300' : (log.type === 'error' ? 'text-rose-400' : (log.type === 'info' ? 'text-cyan-300' : 'text-slate-300'))" 
                                      x-text="log.text">
                                </span>
                            </div>
                        </template>
                        <template x-if="syncLiveLogs.length === 0">
                            <div class="text-slate-500 italic text-[11px]">Connecting to upstream APMC Mandi feeds & initializing time slices...</div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                    <span class="italic">Slices are injected chunk-by-chunk to prevent timeouts and preserve memory.</span>
                    <template x-if="syncMode === 'range' && !abortSyncRequested">
                        <button type="button" 
                                @click="abortSyncRequested = true; addLiveLog('⚠️ Stop requested. Sync will finish gracefully after current batch.', 'info')" 
                                class="text-slate-400 hover:text-rose-400 transition underline cursor-pointer font-medium">
                            Stop after current batch
                        </button>
                    </template>
                </div>
            </div>

            <!-- STATE 3: COMPLETED (TELEMETRY DASHBOARD) -->
            <div x-show="syncConsoleState === 'completed'" class="py-4 space-y-4">
                <!-- Header Status Badge -->
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-950 border border-emerald-800 rounded-full text-xs font-bold text-emerald-400">
                        <span>🚀 Pipeline Executed Successfully</span>
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">
                        ⏱️ Completed in <span class="text-white" x-text="syncElapsedSeconds"></span>s
                    </span>
                </div>

                <!-- Telemetry 4-Card Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Received</div>
                        <div class="text-xl font-extrabold text-blue-400 mt-1" x-text="(syncSummary.received || 0).toLocaleString()">0</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Raw Records</div>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Inserted</div>
                        <div class="text-xl font-extrabold text-emerald-400 mt-1" x-text="(syncSummary.inserted || 0).toLocaleString()">0</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">New Canonical</div>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-cyan-400">Updated</div>
                        <div class="text-xl font-extrabold text-cyan-400 mt-1" x-text="(syncSummary.updated || 0).toLocaleString()">0</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Refreshed</div>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Duplicates</div>
                        <div class="text-xl font-extrabold text-slate-300 mt-1" x-text="(syncSummary.duplicate || 0).toLocaleString()">0</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">SHA-256 Skipped</div>
                    </div>
                </div>

                <!-- All Duplicates Notice (Already Synced & Protected) -->
                <div x-show="(syncSummary.duplicate || 0) > 0 && (syncSummary.inserted || 0) === 0" class="bg-blue-950/40 border border-blue-800/80 rounded-xl p-3.5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-300 flex items-center gap-1.5">
                            <span>ℹ️</span>
                            <span>All records for this period already exist in the database (<span x-text="syncSummary.duplicate"></span> duplicates skipped)</span>
                        </span>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-blue-900/60 text-blue-200 rounded border border-blue-700/60 font-mono">
                            Up to Date
                        </span>
                    </div>
                    <p class="text-xs text-blue-200/90 leading-relaxed">
                        The system checked all <span class="font-bold text-white font-mono" x-text="syncSummary.duplicate"></span> records against existing database rows using SHA-256 cryptographic checksums. Because these exact auction records are already saved in your database, deduplication protected your database from duplicate rows.
                    </p>
                    <div class="pt-1 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Want to overwrite and recalculate analytics anyway?</span>
                        <button type="button"
                                @click="forceSync = true; runSync(syncMode)"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-200 hover:text-white bg-amber-900/80 hover:bg-amber-800 px-3 py-1.5 rounded-lg border border-amber-700 transition cursor-pointer">
                            <span>⚡ Force Re-sync & Overwrite</span>
                        </button>
                    </div>
                </div>

                <!-- Quarantined / Rejection Notice (if rejected > 0) -->
                <div x-show="(syncSummary.rejected || 0) > 0" class="bg-amber-950/40 border border-amber-800/80 rounded-xl p-3.5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-300 flex items-center gap-1.5">
                            <span>⚠️</span>
                            <span><span x-text="syncSummary.rejected"></span> records quarantined for entity resolution</span>
                        </span>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-amber-900/60 text-amber-200 rounded border border-amber-700/60">
                            Isolated Safely
                        </span>
                    </div>
                    <p class="text-xs text-amber-200/90 leading-relaxed">
                        These items could not be automatically mapped to Krushi Baandhava's core catalogue (e.g. non-tracked crops or new APMC spellings). They were saved to quarantine without polluting live prices.
                    </p>
                    <!-- Distinct Unresolved Commodities List -->
                    <div x-show="uniqueRejections.length > 0" class="pt-1">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400/80 mb-1">Unresolved Commodities Encountered:</div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="(rej, idx) in uniqueRejections" :key="idx">
                                <span class="text-[11px] font-mono font-medium px-2 py-0.5 bg-amber-900/40 border border-amber-700/50 text-amber-200 rounded-md" x-text="rej"></span>
                            </template>
                        </div>
                    </div>
                    <div class="pt-1 flex items-center justify-end">
                        <a href="{{ route('admin.unresolved-mappings.index') }}" 
                           target="_blank"
                           class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-200 hover:text-white bg-amber-900/80 hover:bg-amber-800 px-3 py-1.5 rounded-lg border border-amber-700 transition">
                            <span>Review & Map in Unresolved Mappings</span>
                            <span>↗</span>
                        </a>
                    </div>
                </div>

                <!-- Data Sources Involved -->
                <div x-show="syncSources && syncSources.length > 0" class="text-xs text-slate-400 flex items-center gap-2 pt-1">
                    <span class="font-bold">Sources processed:</span>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="(src, idx) in syncSources" :key="idx">
                            <span class="px-2 py-0.5 bg-slate-800 text-slate-300 rounded text-[11px] font-mono" x-text="typeof src === 'object' ? (src.name || src.code) : src"></span>
                        </template>
                    </div>
                </div>

                <!-- Modal Action Buttons -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" 
                            @click="resetSyncModal()" 
                            class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 rounded-xl hover:bg-slate-700 transition cursor-pointer">
                        Run Another Sync
                    </button>
                    <button type="button" 
                            @click="syncModalOpen = false; window.location.reload()" 
                            class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm cursor-pointer flex items-center gap-1.5">
                        <span>Done & Refresh Table</span>
                        <span>↻</span>
                    </button>
                </div>
            </div>

            <!-- STATE 4: ERROR -->
            <div x-show="syncConsoleState === 'error'" class="py-4 space-y-4">
                <div class="bg-rose-950/50 border border-rose-800 rounded-xl p-4 space-y-2">
                    <div class="flex items-center gap-2 text-rose-300 text-sm font-bold">
                        <span>⚠️ Ingestion Execution Failed</span>
                    </div>
                    <p class="text-xs text-rose-200 leading-relaxed" x-text="syncErrorMessage"></p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" 
                            @click="syncModalOpen = false" 
                            class="px-4 py-2 text-xs font-bold text-slate-400 bg-slate-800 rounded-xl hover:bg-slate-700 transition cursor-pointer">
                        Close
                    </button>
                    <button type="button" 
                            @click="resetSyncModal()" 
                            class="px-5 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-500 transition shadow-sm cursor-pointer">
                        Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Retention & Safe Pruning Modal -->
    <div x-show="pruneModalOpen"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         @keydown.escape.window="pruneModalOpen = false">
        
        <!-- Dark Dimming Backdrop -->
        <div x-show="pruneModalOpen"
             x-transition.opacity
             class="fixed inset-0 bg-black/80"
             @click="pruneModalOpen = false"></div>

        <!-- Modal Box -->
        <div x-show="pruneModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 bg-slate-900 rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-700 text-white my-auto max-h-[90vh] overflow-y-auto"
             @click.away="pruneModalOpen = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-black text-rose-300 flex items-center gap-2">
                        <span>🗑️</span> Data Retention & Safe Pruning Manager
                    </h3>
                    <p class="text-xs text-slate-400">Reclaim MySQL disk space by safely pruning old daily records.</p>
                </div>
                <button @click="pruneModalOpen = false" class="text-slate-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="executeAsyncPrune()" class="mt-4 space-y-4">
                @csrf

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Pruning Strategy</label>
                        <button type="button" @click="fetchPrunePreview()" :disabled="prunePreviewLoading" class="text-[11px] font-bold text-cyan-400 hover:text-cyan-300 transition flex items-center gap-1 cursor-pointer">
                            <span :class="prunePreviewLoading ? 'animate-spin' : ''">🔄</span>
                            <span x-text="prunePreviewLoading ? 'Refreshing...' : 'Refresh Metrics'"></span>
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        <!-- Strategy 1 (NEW & RECOMMENDED): Inactive / Disabled Crops Targeted Cleanup -->
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer hover:border-slate-700 transition" :class="pruneStrategy === 'inactive_crops' ? 'border-amber-500 bg-amber-950/25 shadow-sm' : ''">
                            <input type="radio" name="strategy" value="inactive_crops" x-model="pruneStrategy" class="mt-0.5 text-amber-500 focus:ring-amber-500">
                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="text-xs font-black text-amber-300 flex items-center gap-1.5">
                                        <span>🎯</span>
                                        <span>Prune Records for Inactive Crops (Targeted Cleanup)</span>
                                    </div>
                                    <template x-if="prunePreviewData">
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-amber-950 text-amber-300 border border-amber-800/80 font-bold"
                                              x-text="(prunePreviewData.inactive_records_count || 0).toLocaleString() + ' records across ' + (prunePreviewData.inactive_crops_count || 0) + ' crops'">
                                        </span>
                                    </template>
                                </div>
                                <p class="text-[11px] text-slate-300 mt-1 leading-relaxed">
                                    Safely removes historical daily trade records <strong class="text-white">only for deactivated commodities</strong> (<code class="text-amber-300 font-mono text-[10px]">is_active = false</code>). Preserves all historical rates and forecasts for your active crops.
                                </p>
                                
                                <div x-show="pruneStrategy === 'inactive_crops'" class="mt-3 space-y-2.5">
                                    <!-- Comparison Stats -->
                                    <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
                                        <div>
                                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Protected Active Crops:</span>
                                            <span class="text-emerald-400 font-black text-xs" x-text="(prunePreviewData?.active_crops_count ?? '...') + ' Active (' + (prunePreviewData?.active_records_count?.toLocaleString() ?? '...') + ' rows)'"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Targeted For Pruning:</span>
                                            <span class="text-rose-400 font-black text-xs" x-text="(prunePreviewData?.inactive_crops_count ?? '...') + ' Inactive (' + (prunePreviewData?.inactive_records_count?.toLocaleString() ?? '...') + ' rows)'"></span>
                                        </div>
                                    </div>

                                    <!-- Inactive Crops Drawer -->
                                    <div x-data="{ showList: false }" class="pt-0.5">
                                        <button type="button" @click="showList = !showList" class="text-[11px] font-bold text-amber-400 hover:text-amber-300 transition flex items-center gap-1 cursor-pointer">
                                            <span x-text="showList ? '▲ Hide Crop List' : '▼ Inspect All Inactive Crops (' + (prunePreviewData?.inactive_crops_count ?? 0) + ')'"></span>
                                        </button>
                                        <div x-show="showList" class="mt-2 max-h-36 overflow-y-auto p-2 bg-slate-950 rounded-xl border border-slate-800 flex flex-wrap gap-1.5 scrollbar-thin">
                                            <template x-for="c in prunePreviewData?.inactive_crops || []" :key="c.id">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] bg-slate-900 border border-slate-800 text-slate-300">
                                                    <span class="text-white font-medium" x-text="c.name"></span>
                                                    <span class="text-slate-500 font-mono" x-text="'(' + c.records_count.toLocaleString() + ')'"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Strategy 2: Age Based -->
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer hover:border-slate-700 transition" :class="pruneStrategy === 'age' ? 'border-emerald-600 bg-emerald-950/20' : ''">
                            <input type="radio" name="strategy" value="age" x-model="pruneStrategy" class="mt-0.5 text-emerald-500 focus:ring-emerald-500">
                            <div>
                                <div class="text-xs font-bold text-white">Prune Daily Records by Age (Rolling Window)</div>
                                <p class="text-[11px] text-slate-400 mt-0.5">Keeps recent daily records and automatically cleans up older rows.</p>
                                <div x-show="pruneStrategy === 'age'" class="mt-2 flex items-center gap-2">
                                    <span class="text-xs text-slate-400 font-medium">Keep last:</span>
                                    <select name="older_than_days" x-model="pruneDays" class="text-xs font-bold px-3 py-1 bg-slate-900 border border-slate-700 rounded-lg text-white">
                                        <option value="365">365 Days (1 Full Year - Recommended for cPanel)</option>
                                        <option value="180">180 Days (6 Months)</option>
                                        <option value="90">90 Days (3 Months)</option>
                                    </select>
                                </div>
                            </div>
                        </label>

                        <!-- Strategy 3: Specific Period -->
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-800 bg-slate-950/60 cursor-pointer hover:border-slate-700 transition" :class="pruneStrategy === 'period' ? 'border-emerald-600 bg-emerald-950/20' : ''">
                            <input type="radio" name="strategy" value="period" x-model="pruneStrategy" class="mt-0.5 text-emerald-500 focus:ring-emerald-500">
                            <div class="flex-1">
                                <div class="text-xs font-bold text-white">Delete Specific Year / Month</div>
                                <p class="text-[11px] text-slate-400 mt-0.5">Prunes daily trade records for an exact historical period.</p>
                                <div x-show="pruneStrategy === 'period'" class="mt-2 grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-0.5">Year</label>
                                        <select name="year" x-model="pruneYear" class="w-full text-xs font-bold px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white">
                                            @foreach($availableYears as $yr)
                                                <option value="{{ $yr }}">{{ $yr }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-0.5">Month (Optional)</label>
                                        <select name="month" x-model="pruneMonth" class="w-full text-xs font-bold px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white">
                                            <option value="">Entire Year (All Months)</option>
                                            @for($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Async Status Box / Alerts -->
                <div x-show="pruneAsyncError" class="p-3 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-xs flex items-center gap-2" style="display: none;">
                    <span>⚠️</span>
                    <span x-text="pruneAsyncError"></span>
                </div>

                <!-- Safety Guard Guarantee -->
                <div class="bg-emerald-950/40 p-3.5 rounded-xl border border-emerald-800/60 flex items-start gap-2.5 text-xs text-emerald-200">
                    <span class="text-base shrink-0">🛡️</span>
                    <div>
                        <div class="font-black text-white text-[11px] uppercase tracking-wide">Pre-Aggregation Safety Guard Active:</div>
                        <p class="text-[11px] text-emerald-300/90 mt-0.5 leading-relaxed">
                            Before daily rows are removed, the system compiles and saves permanent monthly summaries in <code class="bg-emerald-950 px-1 py-0.5 rounded text-emerald-300 font-mono">price_monthly_statistics</code>. 
                            <strong>"Best Months to Sell" and long-term 5-year trends will never break.</strong>
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="pruneModalOpen = false" :disabled="pruneExecuting" class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 rounded-xl hover:bg-slate-700 transition cursor-pointer disabled:opacity-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="pruneExecuting || (pruneStrategy === 'inactive_crops' && (prunePreviewData?.inactive_records_count ?? 0) === 0)" 
                            class="px-5 py-2 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-500 transition shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <template x-if="pruneExecuting">
                            <span class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        </template>
                        <span x-text="pruneExecuting ? 'Pruning in Chunks...' : '⚠️ Execute Safe Prune'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed bottom-6 right-6 z-50 max-w-md p-4 rounded-2xl shadow-2xl border backdrop-blur-md flex items-center gap-3"
         :class="toast.type === 'success' ? 'bg-emerald-950/95 border-emerald-500/50 text-emerald-200 shadow-emerald-950/60' : (toast.type === 'warning' ? 'bg-amber-950/95 border-amber-500/50 text-amber-200 shadow-amber-950/60' : 'bg-rose-950/95 border-rose-500/50 text-rose-200 shadow-rose-950/60')"
         style="display: none;">
        <span class="text-xl" x-text="toast.type === 'success' ? '✅' : (toast.type === 'warning' ? '⚠️' : '❌')"></span>
        <div class="text-xs font-semibold leading-relaxed flex-1" x-text="toast.message"></div>
        <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white text-xs p-1">✕</button>
    </div>

</div>

<script>
function pricesManager() {
    return {
        syncModalOpen: false,
        syncMode: 'single',
        syncConsoleState: 'config',
        syncProgressPercent: 0,
        syncProgressStage: '',
        syncElapsedSeconds: 0,
        syncTimerInterval: null,
        syncProgressInterval: null,
        syncSummary: {
            received: 0,
            inserted: 0,
            updated: 0,
            duplicate: 0,
            rejected: 0
        },
        syncSources: [],
        syncRejections: [],
        syncErrorMessage: '',
        syncLiveLogs: [],
        totalSlices: 0,
        currentSliceIndex: 0,
        abortSyncRequested: false,
        forceSync: false,
        pruneModalOpen: false,
        archiveDrawerOpen: false,
        selectedSource: '',
        selectedCrop: '',
        targetDate: '{{ date('Y-m-d') }}',
        fromDate: '{{ date('Y-m-d', strtotime('-90 days')) }}',
        toDate: '{{ date('Y-m-d') }}',
        updateAnalytics: true,
        pruneStrategy: 'inactive_crops',
        pruneDays: 365,
        pruneYear: '{{ count($availableYears) > 1 ? $availableYears[1] : (date('Y') - 1) }}',
        pruneMonth: '',
        pruneExecuting: false,
        prunePreviewLoading: false,
        prunePreviewData: null,
        pruneAsyncError: '',
        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },
        agmarknetSourceId: '{{ $dataSources->firstWhere('code', 'agmarknet_official')?->id ?? 4 }}',
        agmarknetCaptchaKey: '',
        agmarknetCaptchaImage: '',
        agmarknetCaptchaCode: '',
        savedSourceCrops: @json($savedSourceCrops ?? []),
        savedAllActiveCrops: @json($savedAllActiveCrops ?? []),

        get availableCrops() {
            if (this.selectedSource && this.savedSourceCrops && this.savedSourceCrops[this.selectedSource]) {
                return this.savedSourceCrops[this.selectedSource];
            }
            return this.savedAllActiveCrops || [];
        },

        isAgmarknetSourceSelected() {
            return String(this.selectedSource) === String(this.agmarknetSourceId);
        },

        async onSourceChange() {
            if (this.selectedCrop) {
                const cropExists = this.availableCrops.some(c => String(c.id) === String(this.selectedCrop));
                if (!cropExists) {
                    this.selectedCrop = '';
                }
            }
            if (this.isAgmarknetSourceSelected() && !this.agmarknetCaptchaImage) {
                await this.refreshAgmarknetCaptcha();
            }
        },

        async refreshAgmarknetCaptcha() {
            this.agmarknetCaptchaLoading = true;
            this.agmarknetCaptchaImage = '';
            try {
                const res = await fetch('{{ route('admin.datasources.agmarknet.captcha') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.ok) {
                    this.agmarknetCaptchaKey = data.captcha_key;
                    this.agmarknetCaptchaImage = data.captcha_image;
                }
            } catch (e) {
                console.warn('Captcha fetch error:', e);
            } finally {
                this.agmarknetCaptchaLoading = false;
            }
        },

        get uniqueRejections() {
            if (!this.syncRejections || !this.syncRejections.length) return [];
            const list = [];
            for (let i = 0; i < this.syncRejections.length; i++) {
                const r = this.syncRejections[i];
                const name = r.raw_crop || r.commodity || r.reason || r.error;
                if (name && list.indexOf(name) === -1) {
                    list.push(name);
                }
            }
            return list.slice(0, 8);
        },

        setPreset(days) {
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - days);
            this.toDate = end.toISOString().split('T')[0];
            this.fromDate = start.toISOString().split('T')[0];
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 6000);
        },

        async openPruneModal() {
            this.pruneStrategy = 'inactive_crops';
            this.pruneAsyncError = '';
            this.pruneModalOpen = true;
            await this.fetchPrunePreview();
        },

        async fetchPrunePreview() {
            this.prunePreviewLoading = true;
            try {
                const res = await fetch('{{ route('admin.prices.prune-preview') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.ok) {
                    this.prunePreviewData = data;
                }
            } catch (err) {
                console.warn('Failed to load prune preview metrics:', err);
            } finally {
                this.prunePreviewLoading = false;
            }
        },

        async executeAsyncPrune() {
            if (!confirm('Execute this safe pruning operation? Monthly trends and active crop forecasts are permanently preserved.')) {
                return;
            }

            this.pruneExecuting = true;
            this.pruneAsyncError = '';

            try {
                const res = await fetch('{{ route('admin.prices.prune') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        strategy: this.pruneStrategy,
                        older_than_days: this.pruneDays,
                        year: this.pruneYear,
                        month: this.pruneMonth
                    })
                });

                const data = await res.json();

                if (data.ok) {
                    this.pruneModalOpen = false;
                    this.showToast(data.message, 'success');
                    await this.fetchPrunePreview();
                    setTimeout(() => {
                        window.location.reload();
                    }, 2200);
                } else {
                    this.pruneAsyncError = data.message || 'Pruning operation completed with no records removed.';
                    this.showToast(this.pruneAsyncError, 'warning');
                }
            } catch (err) {
                this.pruneAsyncError = 'Request failed: ' + (err.message || 'Server error during pruning');
                this.showToast(this.pruneAsyncError, 'error');
            } finally {
                this.pruneExecuting = false;
            }
        },

        openPruneForPeriod(yr, mo) {
            this.pruneStrategy = 'period';
            this.pruneYear = yr;
            this.pruneMonth = mo;
            this.pruneModalOpen = true;
            this.fetchPrunePreview();
        },

        resetSyncModal() {
            this.syncConsoleState = 'config';
            this.syncProgressPercent = 0;
            this.syncProgressStage = '';
            this.syncElapsedSeconds = 0;
            this.syncLiveLogs = [];
            this.totalSlices = 0;
            this.currentSliceIndex = 0;
            this.abortSyncRequested = false;
            this.forceSync = false;
            this.agmarknetCaptchaCode = '';
            this.syncSummary = {
                received: 0,
                inserted: 0,
                updated: 0,
                duplicate: 0,
                rejected: 0
            };
            this.syncSources = [];
            this.syncRejections = [];
            this.syncErrorMessage = '';
            if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
            if (this.syncProgressInterval) clearInterval(this.syncProgressInterval);
            if (this.isAgmarknetSourceSelected()) {
                this.refreshAgmarknetCaptcha();
            }
        },

        splitRangeIntoSlices(fromStr, toStr, sliceDays = 25) {
            const slices = [];
            let cur = new Date(fromStr + 'T00:00:00');
            const end = new Date(toStr + 'T00:00:00');

            if (isNaN(cur.getTime()) || isNaN(end.getTime()) || cur > end) {
                return [{ from: fromStr, to: toStr }];
            }

            while (cur <= end) {
                const sliceEnd = new Date(cur);
                sliceEnd.setDate(sliceEnd.getDate() + (sliceDays - 1));
                const effectiveEnd = sliceEnd > end ? end : sliceEnd;

                slices.push({
                    from: cur.toISOString().split('T')[0],
                    to: effectiveEnd.toISOString().split('T')[0]
                });

                cur = new Date(effectiveEnd);
                cur.setDate(cur.getDate() + 1);
            }
            return slices.length > 0 ? slices : [{ from: fromStr, to: toStr }];
        },

        addLiveLog(text, type = 'default') {
            const now = new Date();
            const time = now.toTimeString().split(' ')[0];
            this.syncLiveLogs.push({ time, text, type });
            if (this.syncLiveLogs.length > 300) {
                this.syncLiveLogs.shift();
            }
            this.$nextTick(() => {
                const container = document.getElementById('syncLiveLogContainer');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        startProgressAnimation(mode) {
            this.syncConsoleState = 'syncing';
            this.syncProgressPercent = 8;
            this.syncElapsedSeconds = 0;
            this.syncProgressStage = mode === 'range' 
                ? 'Connecting to Official AGMARKNET / KRAMA historical archive...'
                : 'Connecting to upstream APMC mandi feeds...';

            if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
            this.syncTimerInterval = setInterval(() => {
                this.syncElapsedSeconds = +(this.syncElapsedSeconds + 0.5).toFixed(1);
            }, 500);

            if (mode === 'single') {
                const stages = [
                    { at: 35, text: 'Retrieving official auction trade payloads...' },
                    { at: 65, text: 'Normalizing varieties & checking SHA-256 duplicate checksums...' },
                    { at: 85, text: 'Writing canonical records into database...' }
                ];
                let stageIdx = 0;
                if (this.syncProgressInterval) clearInterval(this.syncProgressInterval);
                this.syncProgressInterval = setInterval(() => {
                    if (this.syncProgressPercent < 90) {
                        this.syncProgressPercent += Math.floor(Math.random() * 8) + 4;
                        if (this.syncProgressPercent > 90) this.syncProgressPercent = 90;

                        if (stageIdx < stages.length && this.syncProgressPercent >= stages[stageIdx].at) {
                            this.syncProgressStage = stages[stageIdx].text;
                            stageIdx++;
                        }
                    }
                }, 500);
            }
        },

        finishProgress(data) {
            if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
            if (this.syncProgressInterval) clearInterval(this.syncProgressInterval);
            this.syncProgressPercent = 100;
            this.syncProgressStage = 'Ingestion complete!';
            if (data && data.summary) {
                this.syncSummary = data.summary;
            }
            if (data && data.sources) {
                this.syncSources = data.sources;
            }
            if (data && data.rejections) {
                this.syncRejections = data.rejections;
            }
            setTimeout(() => {
                this.syncConsoleState = 'completed';
            }, 450);
        },

        handleSyncError(msg) {
            if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
            if (this.syncProgressInterval) clearInterval(this.syncProgressInterval);
            this.syncErrorMessage = msg || 'An unexpected error occurred while communicating with the server.';
            this.syncConsoleState = 'error';
        },

        async runSync(mode) {
            this.syncConsoleState = 'syncing';
            this.syncProgressPercent = 4;
            this.syncLiveLogs = [];
            this.abortSyncRequested = false;
            this.syncSummary = {
                received: 0,
                inserted: 0,
                updated: 0,
                duplicate: 0,
                rejected: 0
            };
            this.syncSources = [];
            this.syncRejections = [];
            this.syncErrorMessage = '';

            if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
            this.syncElapsedSeconds = 0;
            this.syncTimerInterval = setInterval(() => {
                this.syncElapsedSeconds = +(this.syncElapsedSeconds + 0.5).toFixed(1);
            }, 500);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const selectedCropObj = this.availableCrops.find(c => String(c.id) === String(this.selectedCrop));
            const cropLabel = selectedCropObj ? (selectedCropObj.name || selectedCropObj.kannada_name) : 'All Active Crops';

            if (mode === 'single') {
                this.startProgressAnimation('single');
                this.syncProgressStage = `Connecting to Mandi Feeds for ${this.targetDate}...`;
                this.addLiveLog(`🚀 Starting single-day ingestion for ${cropLabel} on ${this.targetDate}`, 'info');

                try {
                    const resp = await fetch('{{ route('admin.prices.sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            data_source_id: this.selectedSource || null,
                            crop_id: this.selectedCrop || null,
                            target_date: this.targetDate,
                            force: this.forceSync ? 1 : 0,
                            captcha_key: this.agmarknetCaptchaKey || null,
                            captcha_code: this.agmarknetCaptchaCode || null,
                        })
                    });

                    const data = await resp.json();
                    if (!resp.ok || !data.ok) {
                        const errMsg = data.message || 'Server returned status ' + resp.status;
                        this.addLiveLog(`❌ Sync failed: ${errMsg}`, 'error');
                        this.handleSyncError(errMsg);
                        return;
                    }

                    const mNames = data.synced_markets && data.synced_markets.length 
                        ? ` across ${data.synced_markets.slice(0, 4).join(', ')}` 
                        : '';
                    const insCount = data.summary?.inserted || 0;
                    const dupCount = data.summary?.duplicate || 0;
                    this.addLiveLog(`✅ Complete: ${data.summary?.received || 0} feeds (${insCount} inserted, ${dupCount} duplicates)${mNames}`, insCount > 0 ? 'success' : 'default');
                    this.finishProgress(data);
                } catch (err) {
                    this.addLiveLog(`❌ Network error: ${err.message}`, 'error');
                    this.handleSyncError(err.message || 'Network request failed');
                }
                return;
            }

            // MODE === 'range': Ingestion
            // If syncing with Official AGMARKNET (or if visual CAPTCHA was entered), send entire range in 1 batch
            // because official government CAPTCHA tokens are single-use and expire after 1 request.
            const isAgmarknet = (this.selectedSource == 4 || this.selectedSource == 'agmarknet_official' || (this.agmarknetCaptchaCode && this.agmarknetCaptchaCode.length > 0));
            const slices = isAgmarknet
                ? [{ from: this.fromDate, to: this.toDate }]
                : this.splitRangeIntoSlices(this.fromDate, this.toDate, 25);
            this.totalSlices = slices.length;
            this.addLiveLog(`🗓️ Initializing backfill for ${cropLabel} from ${this.fromDate} to ${this.toDate} (${slices.length} batch ${slices.length > 1 ? 'slices' : 'request'})`, 'info');

            for (let i = 0; i < slices.length; i++) {
                if (this.abortSyncRequested) {
                    this.addLiveLog(`⏹️ Sync gracefully halted by user at batch ${i + 1}/${slices.length}.`, 'info');
                    break;
                }

                this.currentSliceIndex = i + 1;
                const slice = slices[i];
                const isLastSlice = (i === slices.length - 1) || this.abortSyncRequested;
                
                // Starting percentage for this slice
                const sliceBasePercent = Math.round((i / slices.length) * 100);
                this.syncProgressPercent = Math.max(sliceBasePercent, 4);
                this.syncProgressStage = `Batch ${i + 1}/${slices.length}: Ingesting ${slice.from} to ${slice.to}...`;
                
                this.addLiveLog(`⚡ [Batch ${i + 1}/${slices.length}] Querying ${slice.from} → ${slice.to}...`, 'info');

                try {
                    const resp = await fetch('{{ route('admin.prices.sync-range') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            data_source_id: this.selectedSource || null,
                            crop_id: this.selectedCrop || null,
                            from_date: slice.from,
                            to_date: slice.to,
                            update_analytics: this.updateAnalytics ? 1 : 0,
                            skip_forecasts: isLastSlice ? 0 : 1, // Only final slice computes heavy Prophet/time-series models
                            force: this.forceSync ? 1 : 0,
                            captcha_key: this.agmarknetCaptchaKey || null,
                            captcha_code: this.agmarknetCaptchaCode || null,
                        })
                    });

                    const data = await resp.json();

                    if (!resp.ok || !data.ok) {
                        const errMsg = data.message || `HTTP ${resp.status} on batch ${slice.from} to ${slice.to}`;
                        this.addLiveLog(`❌ Error on batch ${i + 1}: ${errMsg}`, 'error');
                        this.handleSyncError(errMsg);
                        return;
                    }

                    // Accumulate metrics
                    if (data.summary) {
                        this.syncSummary.received += data.summary.received || 0;
                        this.syncSummary.inserted += data.summary.inserted || 0;
                        this.syncSummary.updated += data.summary.updated || 0;
                        this.syncSummary.duplicate += data.summary.duplicate || 0;
                        this.syncSummary.rejected += data.summary.rejected || 0;
                    }

                    // Merge sources
                    if (Array.isArray(data.sources)) {
                        for (const s of data.sources) {
                            if (!this.syncSources.some(existing => (existing.code || existing) === (s.code || s))) {
                                this.syncSources.push(s);
                            }
                        }
                    }

                    // Merge rejections
                    if (Array.isArray(data.rejections)) {
                        for (const r of data.rejections) {
                            if (!this.syncRejections.some(existing => existing.id === r.id)) {
                                this.syncRejections.push(r);
                            }
                        }
                    }

                    const mNames = data.synced_markets && data.synced_markets.length 
                        ? ` (Mandi: ${data.synced_markets.slice(0, 3).join(', ')})` 
                        : '';
                    const insertedCount = data.summary?.inserted || 0;
                    const dupCount = data.summary?.duplicate || 0;
                    const rejCount = data.summary?.rejected || 0;
                    
                    let batchMsg = `✓ Batch ${i + 1}/${slices.length} finished: +${insertedCount} new, ${dupCount} duplicate`;
                    if (rejCount > 0) {
                        batchMsg += `, ${rejCount} quarantined for aliasing`;
                    }
                    batchMsg += mNames;
                    const logType = insertedCount > 0 ? 'success' : (rejCount > 0 ? 'warning' : 'default');
                    this.addLiveLog(batchMsg, logType);

                    // Update completed percentage
                    this.syncProgressPercent = Math.round(((i + 1) / slices.length) * 100);

                } catch (err) {
                    this.addLiveLog(`❌ Network error on batch ${i + 1} (${slice.from} → ${slice.to}): ${err.message}`, 'error');
                    this.handleSyncError(err.message || 'Network request failed during batch sync');
                    return;
                }
            }

            this.syncProgressPercent = 100;
            this.syncProgressStage = 'All batches completed!';
            this.addLiveLog(`🎉 Finished! Total ${this.syncSummary.inserted.toLocaleString()} records inserted, ${this.syncSummary.duplicate.toLocaleString()} duplicates deduplicated.`, 'success');

            this.finishProgress({
                summary: this.syncSummary,
                sources: this.syncSources,
                rejections: this.syncRejections
            });
        }
    };
}
</script>
@endsection
