@extends('layouts.admin')

@section('title', 'Ingestion Sync & Health Logs')

@section('content')
<div class="space-y-6" x-data="{ currentTab: '{{ $tab }}', detailModal: false, selectedDetail: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.datasources.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 transition flex items-center gap-1 mb-1">
                <span>←</span> Back to Data Sources
            </a>
            <h1 class="text-2xl font-black text-emerald-950 flex items-center gap-2">
                <span>📜</span> Ingestion Sync & Health Audit Trail
            </h1>
            <p class="text-sm text-stone-500 font-medium">Detailed execution trace for automated cron syncs and test connections.</p>
        </div>

        <!-- Tabs -->
        <div class="flex items-center gap-1 bg-stone-100 p-1 rounded-xl text-xs font-bold">
            <button type="button" @click="currentTab = 'sync'" :class="currentTab === 'sync' ? 'bg-white text-emerald-800 shadow-xs' : 'text-stone-500 hover:text-stone-900'" class="px-3.5 py-1.5 rounded-lg transition">
                Ingestion Sync Logs ({{ $syncLogs->total() }})
            </button>
            <button type="button" @click="currentTab = 'health'" :class="currentTab === 'health' ? 'bg-white text-emerald-800 shadow-xs' : 'text-stone-500 hover:text-stone-900'" class="px-3.5 py-1.5 rounded-lg transition">
                API Health Checks ({{ $healthLogs->total() }})
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-stone-200/80 rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.sync-logs.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" :value="currentTab">
            <div>
                <select name="source_id" onchange="this.form.submit()" class="px-3 py-1.5 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                    <option value="">All Data Sources</option>
                    @foreach($dataSources as $ds)
                        <option value="{{ $ds->id }}" {{ $sourceId == $ds->id ? 'selected' : '' }}>{{ $ds->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="success" {{ $status === 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="running" {{ $status === 'running' ? 'selected' : '' }}>Running</option>
                </select>
            </div>
            @if($sourceId || $status)
                <a href="{{ route('admin.sync-logs.index') }}" class="text-xs font-bold text-stone-500 hover:text-stone-800">Clear Filters</a>
            @endif
        </form>
    </div>

    <!-- TAB 1: SYNC EXECUTION LOGS -->
    <div x-show="currentTab === 'sync'" class="bg-white border border-stone-200/80 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
                <thead class="bg-stone-50/80 text-stone-500 font-bold uppercase text-[11px] border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3">Timestamp</th>
                        <th class="px-4 py-3">Data Source</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Received</th>
                        <th class="px-4 py-3">Inserted</th>
                        <th class="px-4 py-3">Rejected</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 font-medium">
                    @forelse($syncLogs as $log)
                        <tr class="hover:bg-stone-50/60 transition">
                            <td class="px-5 py-3 font-mono">
                                <div>{{ $log->started_at->format('d M Y, H:i:s') }}</div>
                                <div class="text-[10px] text-stone-400">{{ $log->started_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3 font-bold text-stone-900">{{ $log->dataSource?->name ?? 'Deleted Source' }}</td>
                            <td class="px-4 py-3 font-mono text-stone-700">{{ $log->duration_ms !== null ? $log->duration_ms . ' ms' : 'In Progress' }}</td>
                            <td class="px-4 py-3 font-bold text-stone-900">{{ number_format($log->records_received) }}</td>
                            <td class="px-4 py-3 font-bold text-emerald-700">{{ number_format($log->records_inserted) }}</td>
                            <td class="px-4 py-3 font-bold {{ $log->records_rejected > 0 ? 'text-rose-600' : 'text-stone-400' }}">{{ number_format($log->records_rejected) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-800' : ($log->status === 'running' ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($log->details || $log->error_message)
                                    <button type="button" @click="selectedDetail = {{ json_encode(['error' => $log->error_message, 'details' => $log->details]) }}; detailModal = true;" class="text-emerald-700 hover:text-emerald-900 font-bold text-xs">
                                        View Trace
                                    </button>
                                @else
                                    <span class="text-stone-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-stone-400">No sync execution logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($syncLogs->hasPages())
            <div class="px-5 py-3 border-t border-stone-100">
                {{ $syncLogs->appends(['tab' => 'sync', 'source_id' => $sourceId, 'status' => $status])->links() }}
            </div>
        @endif
    </div>

    <!-- TAB 2: API HEALTH CHECK LOGS -->
    <div x-show="currentTab === 'health'" style="display: none;" class="bg-white border border-stone-200/80 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
                <thead class="bg-stone-50/80 text-stone-500 font-bold uppercase text-[11px] border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3">Timestamp</th>
                        <th class="px-4 py-3">Data Source</th>
                        <th class="px-4 py-3">HTTP Status</th>
                        <th class="px-4 py-3">Latency</th>
                        <th class="px-4 py-3">Auth Result</th>
                        <th class="px-4 py-3">Records Found</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 font-medium">
                    @forelse($healthLogs as $hLog)
                        <tr class="hover:bg-stone-50/60 transition">
                            <td class="px-5 py-3 font-mono">
                                <div>{{ $hLog->created_at->format('d M Y, H:i:s') }}</div>
                                <div class="text-[10px] text-stone-400">{{ $hLog->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3 font-bold text-stone-900">{{ $hLog->dataSource?->name ?? 'Deleted Source' }}</td>
                            <td class="px-4 py-3 font-mono {{ $hLog->http_status === 200 ? 'text-emerald-700 font-bold' : 'text-rose-600' }}">{{ $hLog->http_status ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-mono text-stone-700">{{ $hLog->response_time_ms }} ms</td>
                            <td class="px-4 py-3 capitalize text-stone-700 font-semibold">{{ $hLog->auth_result }}</td>
                            <td class="px-4 py-3 font-bold text-stone-900">{{ $hLog->records_found }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $hLog->status === 'healthy' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $hLog->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($hLog->sample_payload || $hLog->error_message)
                                    <button type="button" @click="selectedDetail = {{ json_encode(['error' => $hLog->error_message, 'payload' => $hLog->sample_payload, 'fields' => $hLog->detected_fields]) }}; detailModal = true;" class="text-emerald-700 hover:text-emerald-900 font-bold text-xs">
                                        View Payload
                                    </button>
                                @else
                                    <span class="text-stone-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-stone-400">No connection test health logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($healthLogs->hasPages())
            <div class="px-5 py-3 border-t border-stone-100">
                {{ $healthLogs->appends(['tab' => 'health', 'source_id' => $sourceId, 'status' => $status])->links() }}
            </div>
        @endif
    </div>

    <!-- Trace / Payload Detail Modal -->
    <div x-show="detailModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="detailModal" x-transition.opacity class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs transition-opacity" @click="detailModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="detailModal" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-stone-100 p-6">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <h3 class="text-sm font-black text-stone-900 uppercase tracking-wider">Log Detail & Diagnostics</h3>
                    <button type="button" @click="detailModal = false" class="text-stone-400 hover:text-stone-600 font-bold">✕</button>
                </div>

                <div class="mt-4 space-y-4">
                    <div x-show="selectedDetail?.error" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-semibold" x-text="selectedDetail?.error"></div>

                    <div x-show="selectedDetail?.fields?.length > 0">
                        <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Detected Fields</label>
                        <div class="flex flex-wrap gap-1 bg-stone-50 p-2.5 rounded-xl border border-stone-200/60">
                            <template x-for="f in selectedDetail?.fields" :key="f">
                                <span class="px-2 py-0.5 bg-white border border-stone-200 text-stone-700 font-mono text-[11px] rounded" x-text="f"></span>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Raw Payload / Execution Details</label>
                        <pre class="bg-stone-950 text-emerald-400 p-3.5 rounded-xl font-mono text-xs overflow-x-auto max-h-64" x-text="JSON.stringify(selectedDetail?.details || selectedDetail?.payload || selectedDetail, null, 2)"></pre>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" @click="detailModal = false" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold text-xs rounded-xl transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
