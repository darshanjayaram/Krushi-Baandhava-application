@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ inspectModal: false, activePayload: {} }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Data Quality & Ingestion Inspector</h1>
            <p class="text-sm text-slate-400 mt-0.5">Diagnose parsing failures, unrecognized entities, and reprocess raw market price feeds</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.unresolved-mappings.index') }}" class="px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-semibold rounded-xl flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Alias Resolvers
            </a>
            <form action="{{ route('admin.data-quality.reprocess-all') }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('Attempt batch reprocessing of rejected records?')"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-sm flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Batch Reprocess Rejected
                </button>
            </form>
        </div>
    </div>

    <!-- Status Filters & Search Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-4">
        
        <!-- Status Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto w-full lg:w-auto">
            <a href="{{ route('admin.data-quality.index', ['status' => 'rejected', 'data_source_id' => $dataSourceId]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $status === 'rejected' ? 'bg-rose-950 text-rose-300 border border-rose-800/60' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800' }}">
                <span>Rejected</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-900/60">{{ $stats['rejected'] }}</span>
            </a>
            <a href="{{ route('admin.data-quality.index', ['status' => 'pending', 'data_source_id' => $dataSourceId]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $status === 'pending' ? 'bg-amber-950 text-amber-300 border border-amber-800/60' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800' }}">
                <span>Pending</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-900/60">{{ $stats['pending'] }}</span>
            </a>
            <a href="{{ route('admin.data-quality.index', ['status' => 'duplicate', 'data_source_id' => $dataSourceId]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $status === 'duplicate' ? 'bg-purple-950 text-purple-300 border border-purple-800/60' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800' }}">
                <span>Duplicates</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-900/60">{{ $stats['duplicate'] }}</span>
            </a>
            <a href="{{ route('admin.data-quality.index', ['status' => 'processed', 'data_source_id' => $dataSourceId]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $status === 'processed' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/60' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800' }}">
                <span>Processed</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-900/60">{{ $stats['processed'] }}</span>
            </a>
            <a href="{{ route('admin.data-quality.index', ['status' => 'all', 'data_source_id' => $dataSourceId]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $status === 'all' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white' }}">
                All Records
            </a>
        </div>

        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('admin.data-quality.index') }}" class="w-full lg:w-auto flex items-center gap-2.5">
            <input type="hidden" name="status" value="{{ $status }}">
            
            <select name="data_source_id" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
                <option value="">All Data Sources</option>
                @foreach($dataSources as $ds)
                    <option value="{{ $ds->id }}" {{ $dataSourceId == $ds->id ? 'selected' : '' }}>{{ $ds->name }}</option>
                @endforeach
            </select>

            <input type="text" name="search" value="{{ $search }}" placeholder="Search error keyword..."
                   class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 w-44">

            <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition">
                Filter
            </button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold bg-slate-950/40">
                        <th class="py-3 px-4">Record ID / Source</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Diagnostic / Error Reason</th>
                        <th class="py-3 px-4">Raw Payload Sample</th>
                        <th class="py-3 px-4">Received Time</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($records as $rec)
                        <tr class="hover:bg-slate-800/25 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-white">#{{ $rec->id }}</div>
                                <div class="text-[11px] text-slate-400">{{ $rec->dataSource?->name ?? 'Unknown Source' }}</div>
                            </td>

                            <td class="py-3 px-4">
                                @if($rec->processing_status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800/50 uppercase">
                                        Rejected
                                    </span>
                                @elseif($rec->processing_status === 'processed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800/50 uppercase">
                                        Processed
                                    </span>
                                @elseif($rec->processing_status === 'duplicate')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-bold bg-purple-950 text-purple-300 border border-purple-800/50 uppercase">
                                        Duplicate
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800/50 uppercase">
                                        Pending
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 max-w-xs">
                                @if($rec->error_message)
                                    <div class="text-rose-400 font-mono text-[11px] leading-tight line-clamp-2" title="{{ $rec->error_message }}">
                                        {{ $rec->error_message }}
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px]">Normalized successfully</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @php
                                    $p = $rec->payload;
                                    $cropName = $p['Commodity'] ?? $p['commodity'] ?? $p['source_crop'] ?? 'N/A';
                                    $marketName = $p['Market'] ?? $p['market'] ?? $p['source_market'] ?? 'N/A';
                                    $district = $p['District'] ?? $p['district'] ?? 'N/A';
                                    $modal = $p['Modal_Price'] ?? $p['modal_price'] ?? '0';
                                @endphp
                                <div class="text-xs text-white font-medium">{{ $cropName }} @ {{ $marketName }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $district }} • ₹{{ $modal }}/Qtl</div>
                            </td>

                            <td class="py-3 px-4 text-slate-300">
                                <div>{{ $rec->received_at->diffForHumans() }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $rec->received_at->format('Y-m-d H:i') }}</div>
                            </td>

                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="activePayload = {{ json_encode($rec->payload) }}; inspectModal = true"
                                            class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-medium rounded-lg border border-slate-700 transition"
                                            title="View Raw JSON">
                                        Inspect
                                    </button>

                                    @if($rec->processing_status === 'rejected' || $rec->processing_status === 'pending')
                                        <form action="{{ route('admin.data-quality.reprocess', $rec) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600/90 hover:bg-emerald-600 text-white text-[11px] font-semibold rounded-lg transition"
                                                    title="Attempt Reprocessing">
                                                Reprocess
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.data-quality.destroy', $rec) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Dismiss and delete raw record #{{ $rec->id }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-400 transition" title="Dismiss Record">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No records found for the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $records->links() }}
            </div>
        @endif
    </div>

    <!-- Payload Inspector Modal -->
    <div x-show="inspectModal" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         @keydown.escape.window="inspectModal = false">
        <!-- Backdrop -->
        <div x-show="inspectModal" x-transition.opacity class="fixed inset-0 bg-black/80" @click="inspectModal = false"></div>
        <div @click.away="inspectModal = false" class="relative z-10 bg-slate-900 border border-slate-700 rounded-2xl max-w-xl w-full p-6 shadow-2xl my-8">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Raw Upstream Record Payload</h3>
                <button @click="inspectModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
            </div>

            <div class="mt-4">
                <pre class="bg-slate-950 p-4 rounded-xl text-emerald-400 text-xs font-mono overflow-x-auto max-h-96 border border-slate-800/80 leading-relaxed" x-text="JSON.stringify(activePayload, null, 2)"></pre>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-800 flex justify-end">
                <button type="button" @click="inspectModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
