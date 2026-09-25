@extends('layouts.admin')

@section('title', 'Data Sources & Adapters')

@section('content')
<div class="space-y-6" x-data="dataSourceManager()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>📡</span> Data Sources & Provider Adapters
            </h1>
            <p class="text-sm text-slate-400 font-medium">Manage external APMC feeds, commodity boards, credential encryption & live connection health.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.sync-logs.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-slate-200 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 transition shadow-sm">
                <span>📜 Ingestion Logs</span>
            </a>
            <a href="{{ route('admin.datasources.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm">
                <span>+</span> Register Data Source
            </a>
        </div>
    </div>

    <!-- Metrics Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Registered Sources</span>
            <div class="text-3xl font-extrabold text-white mt-1">{{ $stats['total'] }}</div>
            <div class="text-xs font-medium text-emerald-400 mt-1">Multi-source architecture</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Adapters</span>
            <div class="text-3xl font-extrabold text-emerald-400 mt-1">{{ $stats['active'] }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Scheduled for daily sync</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Today's Ingestions</span>
            <div class="text-3xl font-extrabold text-cyan-400 mt-1">{{ $stats['synced_today'] }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Successful sync executions</div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Recent Ingestion Errors</span>
            <div class="text-3xl font-extrabold {{ $stats['failed_recent'] > 0 ? 'text-rose-400' : 'text-slate-400' }} mt-1">{{ $stats['failed_recent'] }}</div>
            <div class="text-xs font-medium text-slate-400 mt-1">Past 7 days</div>
        </div>
    </div>

    <!-- cPanel Cron Job Setup & Live Health Monitor Card -->
    <div class="bg-slate-900 rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-800 space-y-4" x-data="{ copiedCpanel: false, copiedStandard: false, showInstructions: false }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <span class="text-2xl">⏱️</span>
                <div>
                    <h2 class="text-base font-black text-white tracking-wide flex items-center gap-2">
                        <span>Automated Scheduling & cPanel Cron Assistant</span>
                    </h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Configure once in cPanel. Laravel dynamically triggers all data sources based on their admin schedule.</p>
                </div>
            </div>

            <!-- Live Heartbeat Badge -->
            <div class="shrink-0">
                @if($cronInfo['is_active'])
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-950 text-emerald-300 border border-emerald-800/80">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Cron Active (Tick {{ $cronInfo['last_heartbeat'] ? $cronInfo['last_heartbeat']->diffForHumans() : 'Just now' }})</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-amber-950 text-amber-300 border border-amber-800/80" title="Add cron command in cPanel to activate">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>{{ $cronInfo['last_heartbeat'] ? 'Cron Idle (' . $cronInfo['last_heartbeat']->diffForHumans() . ')' : 'Cron Not Running / Not Set' }}</span>
                    </span>
                @endif
            </div>
        </div>

        <!-- Server Paths & Ready-to-copy Command -->
        <div class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Detected PHP Binary</span>
                    <span class="font-mono text-emerald-400 mt-1 block truncate" title="{{ $cronInfo['php_binary'] }}">{{ $cronInfo['php_binary'] }}</span>
                </div>
                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Project Base Path</span>
                    <span class="font-mono text-emerald-400 mt-1 block truncate" title="{{ $cronInfo['base_path'] }}">{{ $cronInfo['base_path'] }}</span>
                </div>
            </div>

            <!-- Copyable cPanel Command Box -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-300 flex items-center gap-1">
                        <span>📋</span> Recommended cPanel Cron Command (Runs Every Minute)
                    </label>
                    <button type="button" @click="showInstructions = !showInstructions" class="text-xs text-emerald-400 hover:text-emerald-300 font-bold underline cursor-pointer">
                        <span x-text="showInstructions ? 'Hide cPanel Steps ▲' : 'View cPanel Steps ▼'"></span>
                    </button>
                </div>
                <div class="flex items-center gap-2 bg-slate-950/90 border border-slate-800 rounded-xl p-2.5">
                    <code class="flex-1 font-mono text-xs text-amber-300 select-all overflow-x-auto whitespace-nowrap px-1">
                        {{ $cronInfo['cpanel_command'] }}
                    </code>
                    <button type="button" 
                            @click="navigator.clipboard.writeText('{{ addslashes($cronInfo['cpanel_command']) }}'); copiedCpanel = true; setTimeout(() => copiedCpanel = false, 2500)"
                            class="shrink-0 px-3 py-1.5 text-xs font-bold rounded-xl transition border cursor-pointer"
                            :class="copiedCpanel ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 hover:bg-slate-700 text-white border-slate-700'">
                        <span x-show="!copiedCpanel">📋 Copy Command</span>
                        <span x-show="copiedCpanel">✓ Copied!</span>
                    </button>
                </div>
            </div>

            <!-- Expandable Step-by-Step cPanel Guide -->
            <div x-show="showInstructions" x-cloak class="bg-slate-950/60 rounded-xl p-4 border border-slate-800 text-xs space-y-2 text-slate-300">
                <div class="font-bold text-emerald-400 uppercase tracking-wider text-[11px]">How to add this in cPanel (3 Simple Steps):</div>
                <ol class="list-decimal list-inside space-y-1.5 text-slate-300 leading-relaxed font-medium">
                    <li>Log into your <b>cPanel</b> account ➔ Go to the <b>Advanced</b> section ➔ Click <b>Cron Jobs</b>.</li>
                    <li>Under <b>Add New Cron Job</b>, select <b>Common Settings: Once Per Minute (* * * * *)</b>.</li>
                    <li>In the <b>Command</b> field, paste the copied command above and click <b>Add New Cron Job</b>.</li>
                </ol>
                <div class="text-[11px] text-slate-400 pt-1 border-t border-slate-800">
                    💡 <b>Note:</b> You only need to set this once in cPanel. Whenever you adjust sync times or days in the admin panel below, Laravel will automatically execute according to your new schedule!
                </div>
            </div>
        </div>
    </div>

    <!-- Data Sources Table Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-white flex items-center gap-2">
                <span>🔌</span> Configured Providers ({{ $dataSources->total() }})
            </h2>
            <div class="text-xs font-semibold text-slate-400">cPanel Async Batch Compatible</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Source Name & Code</th>
                        <th class="py-3.5 px-4">Provider Adapter</th>
                        <th class="py-3.5 px-4">Sync Frequency</th>
                        <th class="py-3.5 px-4">Last Sync</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($dataSources as $source)
                        <tr class="hover:bg-slate-800/35 transition">
                            <td class="py-3.5 px-5">
                                <div class="font-bold text-white text-sm">{{ $source->name }}</div>
                                <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $source->code }} · {{ $source->auth_type }}</div>
                                @if(in_array($source->code, ['coffee_board', 'coconut_board']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-950/70 text-amber-300 border border-amber-800/60 mt-1">
                                        <span>🕷️</span>
                                        <span>Direct Web Scraper (HTML Parser)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-cyan-950/70 text-cyan-300 border border-cyan-800/60 mt-1">
                                        <span>🔌</span>
                                        <span>Government REST API</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-950 text-emerald-300 border border-emerald-800/60">
                                    {{ class_basename($source->provider_class) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-xs font-bold text-slate-200 uppercase block">
                                    {{ str_replace('_', ' ', $source->sync_frequency) }}
                                </span>
                                @if($source->sync_time)
                                    <div class="text-[11px] font-mono text-emerald-400 font-semibold mt-0.5">
                                        🕒 {{ $source->sync_time }}
                                    </div>
                                @endif
                                <div class="text-[10px] text-slate-500 mt-0.5">
                                    {{ $source->sync_days === 'mon_sat' ? '🗓️ Mon–Sat (Excl. Sun)' : '🗓️ All 7 Days' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($source->last_sync_at)
                                    <div class="text-xs font-bold text-white">{{ $source->last_sync_at->diffForHumans() }}</div>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold {{ $source->last_sync_status === 'success' ? 'text-emerald-400' : 'text-rose-400' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $source->last_sync_status === 'success' ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                        {{ ucfirst($source->last_sync_status) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500 italic">Never synced</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <form action="{{ route('admin.datasources.toggle-status', $source) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition cursor-pointer {{ $source->is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/60 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $source->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                        {{ $source->is_active ? 'Active' : 'Paused' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Test Connection Button -->
                                    <button type="button" @click="testConnection('{{ route('admin.datasources.test-connection', $source) }}', '{{ addslashes($source->name) }}')" class="p-1.5 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Test Connection">
                                        <span class="text-base">⚡</span>
                                    </button>

                                    <!-- Sync Now Trigger -->
                                    <form action="{{ route('admin.datasources.trigger-sync', $source) }}" method="POST" onsubmit="return confirm('Run ingestion sync now for {{ $source->name }}?');">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-cyan-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Run Sync Now">
                                            <span class="text-base">🔄</span>
                                        </button>
                                    </form>

                                    <!-- Field & Alias Mappings -->
                                    <a href="{{ route('admin.datasources.mappings.index', $source) }}" class="p-1.5 text-slate-400 hover:text-amber-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Field & Alias Mappings">
                                        <span class="text-base">🔀</span>
                                    </a>

                                    <!-- Edit Config -->
                                    <a href="{{ route('admin.datasources.edit', $source) }}" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Edit Configuration">
                                        <span class="text-base">✏️</span>
                                    </a>

                                    <!-- Delete Data Source -->
                                    <form action="{{ route('admin.datasources.destroy', $source) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete data source \'{{ addslashes($source->name) }}\'? All associated mappings and raw logs will be removed.');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Delete Data Source">
                                            <span class="text-base">🗑️</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">🔌</div>
                                <div class="text-base font-bold text-white">No data sources configured</div>
                                <p class="text-xs text-slate-400 mt-1">Register an external mandi API or scraper adapter to start ingesting prices.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dataSources->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $dataSources->links() }}
            </div>
        @endif
    </div>

    <!-- Live Connection Diagnostics Modal -->
    <div x-show="modalOpen" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
         @keydown.escape.window="modalOpen = false">
        
        <!-- Dark Dimming Backdrop (No blur filter so modal dialog remains 100% crisp and readable) -->
        <div x-show="modalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80" 
             @click="modalOpen = false"></div>

        <!-- Crisp Modal Dialog Box (relative z-10 ensures it sits cleanly on top of backdrop) -->
        <div x-show="modalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl max-w-2xl w-full border border-slate-700 text-white my-8">
            <div class="p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-black text-white flex items-center gap-2">
                                    <span>⚡</span> Connection Test Diagnostic
                                </h3>
                                <template x-if="!isLoading && testResult">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black border uppercase tracking-wider"
                                          :class="{
                                              'bg-emerald-950 text-emerald-300 border-emerald-800/80': testResult?.health?.status === 'healthy',
                                              'bg-amber-950 text-amber-300 border-amber-800/80': testResult?.health?.status === 'degraded',
                                              'bg-rose-950 text-rose-300 border-rose-800/80': testResult?.health?.status === 'unhealthy' || !testResult?.ok
                                          }"
                                          x-text="testResult?.health?.status === 'healthy' ? '✓ Online & Operational' : (testResult?.health?.status === 'degraded' ? '⚠️ Degraded' : '✕ Connection Failed')">
                                    </span>
                                </template>
                            </div>
                            <p class="text-xs text-slate-400 font-medium mt-0.5" x-text="'Target Provider: ' + currentSourceName"></p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white text-xl font-bold cursor-pointer">✕</button>
                    </div>

                    <!-- Loading State -->
                    <div x-show="isLoading" class="py-12 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-500 border-t-transparent"></div>
                        <p class="text-sm font-bold text-white mt-3">Testing provider connectivity & schema integrity...</p>
                        <p class="text-xs text-slate-400 mt-1">Measuring endpoint latency, verifying auth & parsing sample records</p>
                    </div>

                    <!-- Result State -->
                    <div x-show="!isLoading && testResult" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">HTTP Status</div>
                                <div class="text-lg font-black mt-0.5" 
                                     :class="testResult?.health?.http_status === 200 ? 'text-emerald-400' : 'text-rose-400'" 
                                     x-text="testResult?.health?.http_status ? (testResult.health.http_status + (testResult?.health?.http_status === 200 ? ' OK' : '')) : 'Error'"></div>
                            </div>
                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Latency</div>
                                <div class="text-lg font-black text-cyan-400 mt-0.5" x-text="(testResult?.health?.response_time_ms ?? 0) + ' ms'"></div>
                            </div>
                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Auth Status</div>
                                <div class="text-xs font-black mt-1 uppercase tracking-wide" 
                                     :class="testResult?.health?.auth_result === 'valid' || testResult?.health?.auth_result === 'passed' || String(testResult?.health?.auth_result).includes('success') ? 'text-emerald-400' : 'text-rose-400'"
                                     x-text="testResult?.health?.auth_result ?? 'N/A'"></div>
                            </div>
                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Records Found</div>
                                <div class="text-lg font-black text-white mt-0.5" x-text="testResult?.health?.records_found ?? 0"></div>
                            </div>
                        </div>

                        <!-- Detected Schema Fields -->
                        <div x-show="testResult?.health?.detected_fields && testResult?.health?.detected_fields.length > 0">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Detected Schema Attributes</label>
                            <div class="flex flex-wrap gap-1.5 bg-slate-950 p-3 rounded-xl border border-slate-800">
                                <template x-for="field in testResult?.health?.detected_fields" :key="field">
                                    <span class="px-2 py-0.5 bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs font-bold rounded-md" x-text="field"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Error Message Alert if any -->
                        <div x-show="testResult?.error || testResult?.health?.error_message" 
                             class="p-3.5 bg-rose-950/60 border border-rose-800/80 rounded-xl text-xs text-rose-300 font-semibold leading-relaxed flex items-start gap-2 shadow-sm">
                            <span class="text-base shrink-0">⚠️</span>
                            <div>
                                <div class="font-black text-rose-200 uppercase tracking-wide text-[11px]">Diagnostics Alert:</div>
                                <div class="mt-0.5 text-rose-300" x-text="testResult?.health?.error_message || testResult?.error"></div>
                            </div>
                        </div>

                        <!-- Sample Raw Payload -->
                        <div x-show="testResult?.health?.sample_payload">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Normalized Sample Payload</label>
                            <pre class="bg-black/90 text-emerald-400 p-3 rounded-xl font-mono text-xs overflow-x-auto max-h-48 border border-slate-800 shadow-inner" x-text="JSON.stringify(testResult?.health?.sample_payload, null, 2)"></pre>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl transition cursor-pointer">
                            Close Diagnostic
                        </button>
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

        async testConnection(testUrl, name) {
            this.currentSourceName = name;
            this.testResult = null;
            this.isLoading = true;
            this.modalOpen = true;

            try {
                let res = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                // Fallback to GET if CSRF token expired or returned 419
                if (res.status === 419) {
                    res = await fetch(testUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                }

                let data;
                const contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await res.json();
                } else {
                    const rawHtml = await res.text();
                    const cleanText = rawHtml.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim().substring(0, 160);
                    throw new Error(`Server returned HTTP ${res.status}: ${cleanText || 'Non-JSON response'}`);
                }

                this.isLoading = false;
                this.testResult = data;

                if (!res.ok && !data.health) {
                    this.testResult = {
                        ok: false,
                        health: {
                            http_status: res.status,
                            response_time_ms: 0,
                            auth_result: 'failed',
                            status: 'unhealthy',
                            records_found: 0,
                            detected_fields: [],
                            error_message: data.error || data.message || ('Server returned HTTP ' + res.status)
                        }
                    };
                }
            } catch (err) {
                this.isLoading = false;
                this.testResult = {
                    ok: false,
                    error: err.message,
                    health: {
                        http_status: 500,
                        response_time_ms: 0,
                        auth_result: 'error',
                        status: 'unhealthy',
                        records_found: 0,
                        detected_fields: [],
                        error_message: err.message
                    }
                };
            }
        }
    };
}
</script>
@endsection
