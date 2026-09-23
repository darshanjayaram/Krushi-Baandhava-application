@extends('layouts.admin')

@section('content')
<div class="space-y-8">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Mandi Reporting Coverage -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">APMC Mandi Coverage</span>
                <span class="p-2 rounded-xl bg-cyan-950/70 border border-cyan-800/40 text-cyan-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white">{{ $stats['market_coverage_percent'] }}%</span>
                <span class="text-xs text-slate-400">({{ $stats['reporting_markets_count'] }}/{{ $stats['markets_count'] }})</span>
            </div>
            <div class="mt-2 text-xs text-cyan-400/90 font-medium">Reporting on {{ \Carbon\Carbon::parse($stats['latest_price_date'])->format('d M Y') }}</div>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-3 overflow-hidden">
                <div class="bg-cyan-500 h-1.5 rounded-full" style="width: {{ min(100, $stats['market_coverage_percent']) }}%"></div>
            </div>
        </div>

        <!-- Ingestion Volume & Quality Rate -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Feed Ingestion</span>
                <span class="p-2 rounded-xl bg-purple-950/70 border border-purple-800/40 text-purple-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white">{{ number_format($stats['raw_total_count']) }}</span>
                <span class="text-xs text-slate-400">Raw records</span>
            </div>
            <div class="mt-2 flex items-center gap-2 text-xs">
                <span class="text-emerald-400 font-medium">{{ $stats['raw_processed_count'] }} OK</span>
                <span class="text-slate-500">•</span>
                <span class="text-rose-400 font-medium">{{ $stats['raw_rejected_count'] }} Rejected</span>
                <span class="text-slate-500">•</span>
                <span class="text-slate-400">{{ $stats['raw_duplicate_count'] }} Dup</span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-3 overflow-hidden">
                @php
                    $processedPct = $stats['raw_total_count'] > 0 ? round(($stats['raw_processed_count'] / $stats['raw_total_count']) * 100) : 100;
                @endphp
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $processedPct }}%"></div>
            </div>
        </div>

        <!-- Master Crops & Daily Quotes -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Agricultural Crops</span>
                <span class="p-2 rounded-xl bg-amber-950/70 border border-amber-800/40 text-amber-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white">{{ $stats['active_crops_count'] }}</span>
                <span class="text-xs text-slate-400">/ {{ $stats['crops_count'] }} Active</span>
            </div>
            <div class="mt-2 text-xs text-amber-400/90 font-medium">{{ number_format($stats['today_prices_count']) }} price quotes on latest date</div>
            <div class="mt-3 text-[11px] text-slate-400">Total stored prices: {{ number_format($stats['total_prices_count']) }}</div>
        </div>

        <!-- Forecasting Engine Health -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Forecasting Health</span>
                <span class="p-2 rounded-xl bg-emerald-950/70 border border-emerald-800/40 text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white">{{ $forecastStats['total_projections'] }}</span>
                <span class="text-xs text-emerald-400 font-bold">Projections</span>
            </div>
            <div class="mt-2 text-xs text-slate-400">
                Avg MAPE: <span class="text-emerald-400 font-semibold">{{ $forecastStats['avg_mape'] }}%</span> 
                across {{ $forecastStats['active_models'] }} models
            </div>
            <div class="mt-3 text-[11px] text-slate-400 font-mono">{{ $forecastStats['recent_runs_count'] }} successful runs recorded</div>
        </div>

    </div>

    <!-- Quick Operations Action Bar -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Quick Actions:</span>
            <form action="{{ route('admin.prices.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync Prices Now
                </button>
            </form>
            <a href="{{ route('admin.data-quality.index') }}" class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/60 flex items-center gap-1.5 transition">
                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Inspect Rejected Records ({{ $stats['raw_rejected_count'] }})
            </a>
            <a href="{{ route('admin.unresolved-mappings.index') }}" class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/60 flex items-center gap-1.5 transition">
                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Alias Resolvers
            </a>
        </div>
        <div>
            <a href="{{ route('admin.feature-flags.index') }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 flex items-center gap-1 transition">
                Manage Feature Flags &rarr;
            </a>
        </div>
    </div>

    <!-- Data Feeds Health Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-white">Upstream Ingestion Feeds & API Status</h2>
                <p class="text-xs text-slate-400">Status of automated sync jobs and upstream data providers</p>
            </div>
            <a href="{{ route('admin.datasources.index') }}" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold">
                Manage Adapters &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                        <th class="py-3 px-3">Data Source</th>
                        <th class="py-3 px-3">Provider Code</th>
                        <th class="py-3 px-3">Feed Type</th>
                        <th class="py-3 px-3">Last Sync Time</th>
                        <th class="py-3 px-3">Sync Status</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($dataSources as $ds)
                        @php
                            $latestLog = $ds->syncLogs->first();
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-3">
                                <div class="font-bold text-white text-sm">{{ $ds->name }}</div>
                                <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $ds->base_url }}</div>
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-300">{{ $ds->code }}</td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-300 uppercase">
                                    {{ str_replace('_', ' ', $ds->type) }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-300">
                                @if($ds->last_sync_at)
                                    <div>{{ \Carbon\Carbon::parse($ds->last_sync_at)->diffForHumans() }}</div>
                                    <div class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($ds->last_sync_at)->format('d M H:i') }}</div>
                                @else
                                    <span class="text-slate-400">Never executed</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if($ds->last_sync_status === 'success')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800/50">
                                        ● Success
                                    </span>
                                @elseif($ds->last_sync_status === 'partial')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800/50">
                                        ▲ Partial
                                    </span>
                                @elseif($ds->last_sync_status === 'failed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800/50">
                                        ✕ Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">
                                        Idle
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form action="{{ route('admin.datasources.trigger-sync', $ds) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-emerald-600/90 hover:bg-emerald-600 text-white transition" title="Trigger Sync">
                                            Run Now
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.datasources.mappings.index', $ds) }}" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition">
                                        Mappings
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No data sources configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Data Quality Alerts & Recent Audit Logs Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Data Quality Alerts -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-rose-400 text-base">⚠️</span>
                    <h2 class="text-base font-bold text-white">Recent Data Quality Alerts</h2>
                </div>
                <a href="{{ route('admin.data-quality.index') }}" class="text-xs font-semibold text-rose-400 hover:text-rose-300">
                    View All ({{ $stats['raw_rejected_count'] }}) &rarr;
                </a>
            </div>

            @if($recentRejected->isEmpty())
                <div class="py-8 text-center text-slate-400">
                    <div class="text-2xl mb-1">🎉</div>
                    <div class="text-sm font-semibold text-emerald-400">Zero Rejected Records!</div>
                    <div class="text-xs mt-1">All incoming market feeds are normalizing cleanly.</div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentRejected as $rej)
                        <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800/80">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-semibold text-slate-300">{{ $rej->dataSource?->name ?? 'Source' }}</span>
                                <span class="text-slate-400 text-[11px]">{{ $rej->received_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-xs text-rose-400 font-mono line-clamp-2">{{ $rej->error_message }}</div>
                            <div class="mt-2 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400 font-mono">Record #{{ $rej->id }}</span>
                                <form action="{{ route('admin.data-quality.reprocess', $rej) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-emerald-400 hover:text-emerald-300 font-semibold">
                                        Reprocess &rarr;
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Administrative Audit Logs Preview -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-cyan-400 text-base">🛡️</span>
                    <h2 class="text-base font-bold text-white">Administrative Audit Trail</h2>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">
                    View Full Trail &rarr;
                </a>
            </div>

            @if($recentAuditLogs->isEmpty())
                <div class="py-8 text-center text-xs text-slate-400">No administrative events recorded yet.</div>
            @else
                <div class="space-y-2.5">
                    @foreach($recentAuditLogs as $log)
                        <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-300 font-bold flex items-center justify-center text-[10px] shrink-0 uppercase">
                                    {{ substr($log->user->name ?? 'Sys', 0, 2) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="font-mono text-emerald-400 truncate">{{ $log->action }}</div>
                                    <div class="text-[10px] text-slate-400 truncate">by {{ $log->user->name ?? 'System' }} • {{ $log->ip_address }}</div>
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400 shrink-0 font-medium ml-2">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <!-- Active System Feature Flags -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-white">Platform Feature Flags</h2>
                <p class="text-xs text-slate-400">Operational switches for farmer-facing PWA modules</p>
            </div>
            <a href="{{ route('admin.feature-flags.index') }}" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition">
                Open Flag Center &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @foreach($featureFlags as $flag)
                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/90 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-sm text-white truncate">{{ $flag->name }}</div>
                        <div class="text-[11px] font-mono text-slate-400 truncate">{{ $flag->key }}</div>
                    </div>
                    <form action="{{ route('admin.feature-flags.toggle', $flag) }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $flag->is_enabled ? 'bg-emerald-950 text-emerald-300 border border-emerald-700/50 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' }} transition">
                            {{ $flag->is_enabled ? 'Enabled' : 'Disabled' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
