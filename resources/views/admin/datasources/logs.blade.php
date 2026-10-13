@extends('layouts.admin')

@section('title', 'Ingestion Sync & Health Logs')

@section('content')
<div class="space-y-6" x-data="{ currentTab: '{{ $tab }}', detailModal: false, selectedDetail: null }">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.datasources.index') }}" class="text-xs font-bold text-emerald-400 hover:text-emerald-300 transition inline-flex items-center gap-1 mb-1.5">
                <span>←</span> Back to Data Sources
            </a>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>📜</span> Ingestion Sync & Health Audit Trail
            </h1>
            <p class="text-sm text-slate-400 font-medium">Detailed execution trace for automated cron syncs, API payloads, and health checks.</p>
        </div>

        <!-- Mode Switcher Tabs -->
        <div class="flex items-center gap-1 bg-slate-950 border border-slate-800/80 p-1 rounded-xl text-xs font-bold self-start sm:self-auto">
            <button type="button" 
                    @click="currentTab = 'sync'" 
                    :class="currentTab === 'sync' ? 'bg-emerald-600 text-white shadow-sm font-black' : 'text-slate-400 hover:text-white'" 
                    class="px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                Ingestion Sync Logs ({{ $syncLogs->total() }})
            </button>
            <button type="button" 
                    @click="currentTab = 'health'" 
                    :class="currentTab === 'health' ? 'bg-emerald-600 text-white shadow-sm font-black' : 'text-slate-400 hover:text-white'" 
                    class="px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                API Health Checks ({{ $healthLogs->total() }})
            </button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.sync-logs.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" :value="currentTab">
            <div class="w-full sm:w-auto">
                <select name="source_id" onchange="this.form.submit()" class="w-full sm:w-64 max-w-full truncate px-3 py-2 sm:py-1.5 text-xs font-medium bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    <option value="">All Data Sources</option>
                    @foreach($dataSources as $ds)
                        <option value="{{ $ds->id }}" {{ $sourceId == $ds->id ? 'selected' : '' }}>{{ $ds->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto px-3 py-2 sm:py-1.5 text-xs font-medium bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="success" {{ $status === 'success' ? 'selected' : '' }}>Success / Healthy</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed / Error</option>
                    <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="running" {{ $status === 'running' ? 'selected' : '' }}>Running</option>
                </select>
            </div>
            @if($sourceId || $status)
                <a href="{{ route('admin.sync-logs.index', ['tab' => $tab]) }}" class="px-3 py-1.5 text-xs font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition">
                    ✕ Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- TAB 1: SYNC EXECUTION LOGS -->
    <div x-show="currentTab === 'sync'" id="admin-sync-logs-table-card" data-async-table="true" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Data Source</th>
                        <th class="py-3 px-4 text-center">Duration</th>
                        <th class="py-3 px-4 text-center">Received</th>
                        <th class="py-3 px-4 text-center">Inserted</th>
                        <th class="py-3 px-4 text-center">Rejected</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($syncLogs as $log)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono whitespace-nowrap">
                                <div class="text-white font-bold">{{ $log->started_at->format('d M Y, H:i:s') }}</div>
                                <div class="text-[10px] text-slate-400 font-sans">{{ $log->started_at->diffForHumans() }}</div>
                            </td>
                            <td class="py-3 px-4 font-bold text-white whitespace-nowrap">
                                <span>{{ $log->dataSource?->name ?? 'Deleted Source' }}</span>
                                @if($log->dataSource?->code)
                                    <span class="text-[10px] text-slate-400 font-mono block">{{ $log->dataSource->code }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-cyan-400 whitespace-nowrap">
                                {{ $log->duration_ms !== null ? number_format($log->duration_ms) . ' ms' : 'In Progress' }}
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-white">
                                {{ number_format($log->records_received) }}
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-emerald-400">
                                {{ number_format($log->records_inserted) }}
                            </td>
                            <td class="py-3 px-4 text-center font-bold {{ $log->records_rejected > 0 ? 'text-rose-400' : 'text-slate-500' }}">
                                {{ number_format($log->records_rejected) }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $log->status === 'success' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/60' : ($log->status === 'running' ? 'bg-cyan-950 text-cyan-300 border border-cyan-800/60 animate-pulse' : ($log->status === 'partial' ? 'bg-amber-950 text-amber-300 border border-amber-800/60' : 'bg-rose-950 text-rose-300 border border-rose-800/60')) }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($log->details || $log->error_message)
                                    <button type="button" 
                                            @click="selectedDetail = {{ json_encode(['title' => 'Sync Execution Trace', 'source' => $log->dataSource?->name, 'time' => $log->started_at->format('d M Y, H:i:s'), 'error' => $log->error_message, 'details' => $log->details]) }}; detailModal = true;" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-bold border border-slate-700 transition cursor-pointer">
                                        <span>🔍 View Trace</span>
                                    </button>
                                @else
                                    <span class="text-slate-600 font-mono">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500 text-xs">
                                No ingestion sync execution logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($syncLogs->hasPages())
            <div class="px-4 py-3 border-t border-slate-800 bg-slate-950/40">
                {{ $syncLogs->appends(['tab' => 'sync', 'source_id' => $sourceId, 'status' => $status])->links() }}
            </div>
        @endif
    </div>

    <!-- TAB 2: API HEALTH CHECK LOGS -->
    <div x-show="currentTab === 'health'" id="admin-health-logs-table-card" data-async-table="true" style="display: none;" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Data Source</th>
                        <th class="py-3 px-4 text-center">HTTP Status</th>
                        <th class="py-3 px-4 text-center">Latency</th>
                        <th class="py-3 px-4 text-center">Auth Result</th>
                        <th class="py-3 px-4 text-center">Records Found</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($healthLogs as $hLog)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono whitespace-nowrap">
                                <div class="text-white font-bold">{{ $hLog->created_at->format('d M Y, H:i:s') }}</div>
                                <div class="text-[10px] text-slate-400 font-sans">{{ $hLog->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="py-3 px-4 font-bold text-white whitespace-nowrap">
                                <span>{{ $hLog->dataSource?->name ?? 'Deleted Source' }}</span>
                                @if($hLog->dataSource?->code)
                                    <span class="text-[10px] text-slate-400 font-mono block">{{ $hLog->dataSource->code }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-mono whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $hLog->http_status === 200 ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/60' : 'bg-rose-950 text-rose-400 border border-rose-800/60' }}">
                                    {{ $hLog->http_status ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-cyan-400 whitespace-nowrap">
                                {{ number_format($hLog->response_time_ms) }} ms
                            </td>
                            <td class="py-3 px-4 text-center capitalize text-slate-300 font-semibold whitespace-nowrap">
                                {{ $hLog->auth_result }}
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-white">
                                {{ number_format($hLog->records_found) }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $hLog->status === 'healthy' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/60' : 'bg-rose-950 text-rose-300 border border-rose-800/60' }}">
                                    {{ $hLog->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($hLog->sample_payload || $hLog->error_message)
                                    <button type="button" 
                                            @click="selectedDetail = {{ json_encode(['title' => 'API Connection Payload & Diagnostic', 'source' => $hLog->dataSource?->name, 'time' => $hLog->created_at->format('d M Y, H:i:s'), 'error' => $hLog->error_message, 'payload' => $hLog->sample_payload, 'fields' => $hLog->detected_fields]) }}; detailModal = true;" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-bold border border-slate-700 transition cursor-pointer">
                                        <span>🔍 View Payload</span>
                                    </button>
                                @else
                                    <span class="text-slate-600 font-mono">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500 text-xs">
                                No connection test health logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($healthLogs->hasPages())
            <div class="px-4 py-3 border-t border-slate-800 bg-slate-950/40">
                {{ $healthLogs->appends(['tab' => 'health', 'source_id' => $sourceId, 'status' => $status])->links() }}
            </div>
        @endif
    </div>

    <!-- Trace / Payload Detail Modal -->
    <div x-show="detailModal" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-4" 
         role="dialog" 
         aria-modal="true"
         @keydown.escape.window="detailModal = false">
        
        <!-- Dark Dimming Backdrop -->
        <div x-show="detailModal" 
             x-transition.opacity 
             class="fixed inset-0 bg-black/80" 
             @click="detailModal = false"></div>

        <!-- Modal Box -->
        <div x-show="detailModal" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 bg-slate-900 rounded-2xl max-w-2xl w-full p-4 sm:p-6 shadow-2xl border border-slate-700 text-white my-auto max-h-[90vh] overflow-y-auto scrollbar-thin">
            
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-white flex items-center gap-2">
                        <span>🔍</span>
                        <span x-text="selectedDetail?.title || 'Log Detail & Diagnostics'"></span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="(selectedDetail?.source || '') + (selectedDetail?.time ? ' — ' + selectedDetail?.time : '')"></p>
                </div>
                <button type="button" @click="detailModal = false" class="text-slate-400 hover:text-white text-lg font-bold cursor-pointer p-1">✕</button>
            </div>

            <div class="mt-4 space-y-4">
                <!-- Error Alert Box if present -->
                <div x-show="selectedDetail?.error" class="p-3 bg-rose-950/80 border border-rose-800 rounded-xl text-xs text-rose-300 font-semibold flex items-start gap-2">
                    <span class="text-base shrink-0">⚠️</span>
                    <span x-text="selectedDetail?.error"></span>
                </div>

                <!-- Detected Fields if present -->
                <div x-show="selectedDetail?.fields && selectedDetail?.fields?.length > 0">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Detected Schema Fields</label>
                    <div class="flex flex-wrap gap-1.5 bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                        <template x-for="f in selectedDetail?.fields" :key="f">
                            <span class="px-2 py-0.5 bg-slate-900 border border-slate-800 text-cyan-300 font-mono text-[11px] rounded-md font-medium" x-text="f"></span>
                        </template>
                    </div>
                </div>

                <!-- Raw Payload / JSON Details -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Raw Payload / Execution Details</label>
                    <div class="relative">
                        <pre class="bg-slate-950 text-emerald-400 p-4 rounded-xl font-mono text-xs overflow-x-auto max-h-72 border border-slate-800 select-all leading-relaxed" 
                             x-text="JSON.stringify(selectedDetail?.details || selectedDetail?.payload || selectedDetail, null, 2)"></pre>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" @click="detailModal = false" class="w-full sm:w-auto px-5 py-2.5 sm:py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition cursor-pointer text-center">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
