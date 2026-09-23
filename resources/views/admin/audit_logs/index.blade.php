@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ diffModal: false, activeLog: {} }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Administrative Audit Trail</h1>
            <p class="text-sm text-slate-400 mt-0.5">Immutable forensic record of all administrative modifications, feature flag toggles, and data resolutions</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-cyan-400 font-mono">
                {{ $logs->total() }} Logged Events
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm flex flex-wrap items-center gap-3">
        
        <!-- Action Filter -->
        <select name="action" class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
            <option value="">All Actions</option>
            @foreach($distinctActions as $act)
                <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
            @endforeach
        </select>

        <!-- Admin User Filter -->
        <select name="user_id" class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
            <option value="">All Administrators</option>
            @foreach($admins as $adminUser)
                <option value="{{ $adminUser->id }}" {{ request('user_id') == $adminUser->id ? 'selected' : '' }}>
                    {{ $adminUser->name }} ({{ $adminUser->email }})
                </option>
            @endforeach
        </select>

        <!-- Date Range -->
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500" placeholder="From Date">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500" placeholder="To Date">

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition">
            Filter Logs
        </button>

        @if(request()->hasAny(['action', 'user_id', 'date_from', 'date_to']))
            <a href="{{ route('admin.audit-logs.index') }}" class="text-xs text-slate-400 hover:text-white transition">Reset</a>
        @endif
    </form>

    <!-- Table of Audit Logs -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold bg-slate-950/40">
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Admin User</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Entity Target</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4 text-right">Details / Diffs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/25 transition">
                            <td class="py-3 px-4">
                                <div class="text-slate-200 font-medium">{{ $log->created_at->diffForHumans() }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-white">{{ $log->user->name ?? 'System' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $log->user->email ?? 'Automated Daemon' }}</div>
                            </td>

                            <td class="py-3 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-mono font-semibold bg-slate-950 text-emerald-400 border border-slate-800">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                @if($log->entity_type)
                                    <span class="text-slate-300 font-medium">{{ $log->entity_type }}</span>
                                    @if($log->entity_id)
                                        <span class="text-slate-500 font-mono">#{{ $log->entity_id }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-500 italic">None</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 font-mono text-slate-400 text-[11px]">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>

                            <td class="py-3 px-4 text-right">
                                @if($log->old_values || $log->new_values)
                                    <button type="button" 
                                            @click="activeLog = {
                                                id: {{ $log->id }},
                                                action: '{{ $log->action }}',
                                                user: '{{ addslashes($log->user->name ?? 'System') }}',
                                                ip: '{{ $log->ip_address }}',
                                                entity: '{{ $log->entity_type }} #{{ $log->entity_id }}',
                                                time: '{{ $log->created_at->format('Y-m-d H:i:s') }}',
                                                old_values: {{ json_encode($log->old_values) }},
                                                new_values: {{ json_encode($log->new_values) }}
                                            }; diffModal = true"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold border border-slate-700 transition">
                                        View Changes
                                    </button>
                                @else
                                    <span class="text-slate-500 text-[11px] italic">No diff payload</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No audit logs found for the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Diffs Modal -->
    <div x-show="diffModal" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="diffModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>Event Audit Details</span>
                        <code class="text-xs font-mono text-emerald-400" x-text="activeLog.action"></code>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="'Performed by ' + activeLog.user + ' on ' + activeLog.time + ' (' + activeLog.ip + ')'"></p>
                </div>
                <button @click="diffModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Previous State (Old)</h4>
                    <pre class="bg-slate-950 p-3 rounded-xl text-rose-400 text-xs font-mono overflow-x-auto max-h-80 border border-slate-800/80 leading-relaxed" 
                         x-text="activeLog.old_values ? JSON.stringify(activeLog.old_values, null, 2) : 'None'"></pre>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Updated State (New)</h4>
                    <pre class="bg-slate-950 p-3 rounded-xl text-emerald-400 text-xs font-mono overflow-x-auto max-h-80 border border-slate-800/80 leading-relaxed" 
                         x-text="activeLog.new_values ? JSON.stringify(activeLog.new_values, null, 2) : 'None'"></pre>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-800 flex justify-end">
                <button type="button" @click="diffModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
