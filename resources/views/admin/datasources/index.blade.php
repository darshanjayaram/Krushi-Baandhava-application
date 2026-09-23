@extends('layouts.admin')

@section('title', 'Data Sources & Adapters')

@section('content')
<div class="space-y-6" x-data="dataSourceManager()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-emerald-950 flex items-center gap-2">
                <span>📡</span> Data Sources & Provider Adapters
            </h1>
            <p class="text-sm text-stone-500 font-medium">Manage external APMC feeds, commodity boards, credential encryption & live connection health.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.sync-logs.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-bold text-stone-700 bg-white border border-stone-200 rounded-xl hover:bg-stone-50 transition shadow-xs">
                <span>📜</span> Ingestion Logs
            </a>
            <a href="{{ route('admin.datasources.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 transition shadow-xs">
                <span>+</span> Register Data Source
            </a>
        </div>
    </div>

    <!-- Metrics Summary Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Registered Sources</span>
            <div class="text-2xl font-black text-stone-900 mt-1">{{ $stats['total'] }}</div>
            <div class="text-xs font-semibold text-emerald-700 mt-1">Multi-source architecture</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Active Adapters</span>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ $stats['active'] }}</div>
            <div class="text-xs font-semibold text-stone-500 mt-1">Scheduled for daily sync</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Today's Ingestions</span>
            <div class="text-2xl font-black text-blue-700 mt-1">{{ $stats['synced_today'] }}</div>
            <div class="text-xs font-semibold text-stone-500 mt-1">Successful sync executions</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-xs">
            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Recent Ingestion Errors</span>
            <div class="text-2xl font-black {{ $stats['failed_recent'] > 0 ? 'text-rose-600' : 'text-stone-900' }} mt-1">{{ $stats['failed_recent'] }}</div>
            <div class="text-xs font-semibold text-stone-500 mt-1">Past 7 days</div>
        </div>
    </div>

    <!-- Data Sources Table Card -->
    <div class="bg-white border border-stone-200/80 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="font-bold text-stone-900 flex items-center gap-2">
                <span>🔌</span> Configured Providers ({{ $dataSources->total() }})
            </h2>
            <div class="text-xs font-semibold text-stone-400">cPanel Async Batch Compatible</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-stone-600">
                <thead class="bg-stone-50/80 text-stone-500 font-bold uppercase text-[11px] tracking-wider border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3">Source Name & Code</th>
                        <th class="px-4 py-3">Provider Adapter</th>
                        <th class="px-4 py-3">Sync Frequency</th>
                        <th class="px-4 py-3">Last Sync</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 font-medium">
                    @forelse($dataSources as $source)
                        <tr class="hover:bg-stone-50/60 transition">
                            <td class="px-5 py-4">
                                <div class="font-bold text-stone-900">{{ $source->name }}</div>
                                <div class="text-xs text-stone-400 font-mono mt-0.5">{{ $source->code }} · {{ $source->auth_type }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/50">
                                    {{ class_basename($source->provider_class) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-xs font-bold text-stone-700 uppercase">{{ $source->sync_frequency }}</span>
                                <div class="text-[11px] text-stone-400">Timeout: {{ $source->timeout_seconds }}s</div>
                            </td>
                            <td class="px-4 py-4">
                                @if($source->last_sync_at)
                                    <div class="text-xs font-bold text-stone-800">{{ $source->last_sync_at->diffForHumans() }}</div>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold {{ $source->last_sync_status === 'success' ? 'text-emerald-700' : 'text-rose-600' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $source->last_sync_status === 'success' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ ucfirst($source->last_sync_status) }}
                                    </span>
                                @else
                                    <span class="text-xs text-stone-400 italic">Never synced</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                <form action="{{ route('admin.datasources.toggle-status', $source) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition {{ $source->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $source->is_active ? 'bg-emerald-600' : 'bg-stone-400' }}"></span>
                                        {{ $source->is_active ? 'Active' : 'Paused' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Test Connection Button -->
                                    <button type="button" @click="testConnection({{ $source->id }}, '{{ addslashes($source->name) }}')" class="p-1.5 text-stone-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition" title="Test Connection">
                                        <span class="text-base">⚡</span>
                                    </button>

                                    <!-- Sync Now Trigger -->
                                    <form action="{{ route('admin.datasources.trigger-sync', $source) }}" method="POST" onsubmit="return confirm('Run ingestion sync now for {{ $source->name }}?');">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-stone-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition" title="Run Sync Now">
                                            <span class="text-base">🔄</span>
                                        </button>
                                    </form>

                                    <!-- Field & Alias Mappings -->
                                    <a href="{{ route('admin.datasources.mappings.index', $source) }}" class="p-1.5 text-stone-500 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition" title="Field & Alias Mappings">
                                        <span class="text-base">🔀</span>
                                    </a>

                                    <!-- Edit Config -->
                                    <a href="{{ route('admin.datasources.edit', $source) }}" class="p-1.5 text-stone-500 hover:text-stone-900 hover:bg-stone-100 rounded-lg transition" title="Edit Configuration">
                                        <span class="text-base">✏️</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-stone-400">
                                No data sources registered yet. Click "+ Register Data Source" above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dataSources->hasPages())
            <div class="px-5 py-3 border-t border-stone-100">
                {{ $dataSources->links() }}
            </div>
        @endif
    </div>

    <!-- Live Connection Diagnostics Modal -->
    <div x-show="modalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs transition-opacity" @click="modalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="modalOpen" x-transition class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-stone-100">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div>
                            <h3 class="text-lg font-black text-stone-900 flex items-center gap-2">
                                <span>⚡</span> Connection Test Diagnostic
                            </h3>
                            <p class="text-xs text-stone-400 font-medium" x-text="'Target Provider: ' + currentSourceName"></p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="text-stone-400 hover:text-stone-600 text-xl font-bold">✕</button>
                    </div>

                    <!-- Loading State -->
                    <div x-show="isLoading" class="py-12 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-600 border-t-transparent"></div>
                        <p class="text-sm font-bold text-stone-600 mt-3">Pinging provider endpoint & verifying schema...</p>
                        <p class="text-xs text-stone-400 mt-1">Checking SSL, headers & payload attributes</p>
                    </div>

                    <!-- Result State -->
                    <div x-show="!isLoading && testResult" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-stone-50 p-3 rounded-xl border border-stone-200/60">
                                <div class="text-[11px] font-bold text-stone-400 uppercase">HTTP Status</div>
                                <div class="text-lg font-black mt-0.5" :class="testResult?.health?.http_status === 200 ? 'text-emerald-700' : 'text-rose-600'" x-text="testResult?.health?.http_status ?? 'N/A'"></div>
                            </div>
                            <div class="bg-stone-50 p-3 rounded-xl border border-stone-200/60">
                                <div class="text-[11px] font-bold text-stone-400 uppercase">Latency</div>
                                <div class="text-lg font-black text-stone-900 mt-0.5" x-text="(testResult?.health?.response_time_ms ?? 0) + ' ms'"></div>
                            </div>
                            <div class="bg-stone-50 p-3 rounded-xl border border-stone-200/60">
                                <div class="text-[11px] font-bold text-stone-400 uppercase">Auth Result</div>
                                <div class="text-xs font-black text-emerald-800 mt-1 capitalize" x-text="testResult?.health?.auth_result ?? 'N/A'"></div>
                            </div>
                            <div class="bg-stone-50 p-3 rounded-xl border border-stone-200/60">
                                <div class="text-[11px] font-bold text-stone-400 uppercase">Records Found</div>
                                <div class="text-lg font-black text-stone-900 mt-0.5" x-text="testResult?.health?.records_found ?? 0"></div>
                            </div>
                        </div>

                        <!-- Detected Schema Fields -->
                        <div x-show="testResult?.health?.detected_fields?.length > 0">
                            <label class="block text-xs font-bold text-stone-500 uppercase tracking-wider mb-1.5">Detected Schema Fields</label>
                            <div class="flex flex-wrap gap-1.5 bg-stone-50 p-3 rounded-xl border border-stone-200/60">
                                <template x-for="field in testResult?.health?.detected_fields" :key="field">
                                    <span class="px-2 py-0.5 bg-white border border-stone-200 text-stone-700 font-mono text-xs rounded-md" x-text="field"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Error Message Alert if any -->
                        <div x-show="testResult?.health?.error_message" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-semibold" x-text="testResult?.health?.error_message"></div>

                        <!-- Sample Raw Payload -->
                        <div x-show="testResult?.health?.sample_payload">
                            <label class="block text-xs font-bold text-stone-500 uppercase tracking-wider mb-1.5">Sample Normalized Record</label>
                            <pre class="bg-stone-950 text-emerald-400 p-3 rounded-xl font-mono text-xs overflow-x-auto max-h-48" x-text="JSON.stringify(testResult?.health?.sample_payload, null, 2)"></pre>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold text-xs rounded-xl transition">
                            Close Diagnostic
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function dataSourceManager() {
    return {
        modalOpen: false,
        isLoading: false,
        currentSourceName: '',
        testResult: null,

        testConnection(sourceId, name) {
            this.currentSourceName = name;
            this.testResult = null;
            this.isLoading = true;
            this.modalOpen = true;

            fetch(`/admin/datasources/${sourceId}/test-connection`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.isLoading = false;
                this.testResult = data;
            })
            .catch(err => {
                this.isLoading = false;
                this.testResult = {
                    health: {
                        http_status: null,
                        response_time_ms: 0,
                        auth_result: 'failed',
                        status: 'unhealthy',
                        error_message: err.message || 'Network request failed'
                    }
                };
            });
        }
    }
}
</script>
@endsection
