@extends('layouts.admin')

@section('title', 'Data Sources & Adapters')

@section('content')
<style>
    /* Modern 6px Thin Scrollbars for Modals and Data Grids */
    .modal-thin-scrollbar::-webkit-scrollbar,
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px !important;
        height: 6px !important;
    }
    .modal-thin-scrollbar::-webkit-scrollbar-track,
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #020617 !important;
        border-radius: 9999px !important;
    }
    .modal-thin-scrollbar::-webkit-scrollbar-thumb,
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #059669 !important;
        border-radius: 9999px !important;
        border: 1px solid #064e3b !important;
    }
    .modal-thin-scrollbar::-webkit-scrollbar-thumb:hover,
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #10b981 !important;
    }
    .modal-thin-scrollbar::-webkit-scrollbar-button,
    .custom-scrollbar::-webkit-scrollbar-button {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }
    @supports not selector(::-webkit-scrollbar) {
        .modal-thin-scrollbar,
        .custom-scrollbar {
            scrollbar-width: thin !important;
            scrollbar-color: #059669 #020617 !important;
        }
    }
</style>
<div class="space-y-6" x-data="dataSourceManager()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>📡</span> Data Sources & Provider Adapters
            </h1>
            <p class="text-sm text-slate-400 font-medium">Manage external APMC feeds, commodity boards, credential encryption & live connection health.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <button type="button" 
                    @click="runSyncAll('{{ route('admin.datasources.sync-all') }}')" 
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-1.5 px-3.5 py-2.5 sm:py-2 text-xs font-bold text-cyan-300 bg-cyan-950/80 border border-cyan-800 rounded-xl hover:bg-cyan-900 transition shadow-sm cursor-pointer"
                    :class="syncLoading && activeSyncSourceId === 'all' ? 'ring-2 ring-cyan-500' : ''"
                    title="Run batch ingestion sync for all active data sources for today">
                <span class="text-sm" :class="syncLoading && activeSyncSourceId === 'all' ? 'animate-spin inline-block' : ''">🔄</span>
                <span>Sync All Sources (Today: {{ now()->format('d M') }})</span>
            </button>
            <a href="{{ route('admin.sync-logs.index') }}" class="flex-1 sm:flex-none justify-center inline-flex items-center gap-1.5 px-3.5 py-2.5 sm:py-2 text-xs font-bold text-slate-200 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 transition shadow-sm">
                <span>📜 Ingestion Logs</span>
            </a>
            <a href="{{ route('admin.datasources.create') }}" class="flex-1 sm:flex-none justify-center inline-flex items-center gap-1.5 px-4 py-2.5 sm:py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-sm">
                <span>+ Register Source</span>
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
    <div class="bg-slate-900 rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-800 space-y-4" x-data="{ copiedCpanel: false, copiedStandard: false, showInstructions: false, showScheduleEditor: false }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <span class="text-2xl">⏱️</span>
                <div>
                    <h2 class="text-base font-black text-white tracking-wide flex items-center gap-2">
                        <span>Automated Scheduling & cPanel Cron Assistant</span>
                    </h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Configure once in cPanel. Laravel dynamically triggers all data sources based on your schedule.</p>
                </div>
            </div>

            <!-- Action button & Live Heartbeat Badge -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" 
                        @click="showScheduleEditor = !showScheduleEditor" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer border"
                        :class="showScheduleEditor ? 'bg-amber-600 text-white border-amber-500' : 'text-amber-300 bg-amber-950/80 border-amber-800 hover:bg-amber-900'">
                    <span>⚙️</span>
                    <span x-text="showScheduleEditor ? 'Close Timings Editor ▲' : 'Set Cron Timings ⚙️'"></span>
                </button>

                @if($cronInfo['is_active'])
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-950 text-emerald-300 border border-emerald-800/80 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Cron Active (Tick {{ $cronInfo['last_heartbeat'] ? $cronInfo['last_heartbeat']->diffForHumans() : 'Just now' }})</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-rose-950 text-rose-300 border border-rose-800/80 shadow-sm" title="Add cron command in cPanel to activate">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        <span>{{ $cronInfo['last_heartbeat'] ? 'Cron Idle (' . $cronInfo['last_heartbeat']->diffForHumans() . ')' : 'Cron Not Running / Not Set' }}</span>
                    </span>
                @endif

                <form action="{{ route('admin.scheduler.test') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-cyan-300 hover:text-cyan-200 border border-slate-700 transition cursor-pointer" title="Manually tick the scheduler to test heartbeat">
                        <span>⚡</span>
                        <span>Test Scheduler Tick</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Configured Auto Sync Timings Badge Bar -->
        <div class="flex items-center justify-between gap-3 bg-slate-950/80 border border-slate-800 rounded-xl p-3 flex-wrap text-xs">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-slate-400 font-bold flex items-center gap-1">
                    <span>⏰</span> Configured Timings:
                </span>
                <span class="px-2.5 py-0.5 rounded-lg bg-emerald-950 text-emerald-300 border border-emerald-800 font-mono font-bold" title="Morning mandi arrivals sync">
                    🌅 Morning: {{ $cronInfo['morning_time'] ?: '06:00' }}
                </span>
                <span class="px-2.5 py-0.5 rounded-lg bg-cyan-950 text-cyan-300 border border-cyan-800 font-mono font-bold" title="Evening closing auction rates sync">
                    🌇 Evening: {{ $cronInfo['evening_time'] ?: '19:30' }}
                </span>
                @if($cronInfo['afternoon_time'])
                    <span class="px-2.5 py-0.5 rounded-lg bg-amber-950 text-amber-300 border border-amber-800 font-mono font-bold" title="Mid-day rate update">
                        ☀️ Mid-Day: {{ $cronInfo['afternoon_time'] }}
                    </span>
                @endif
                <span class="px-2.5 py-0.5 rounded-lg bg-slate-800 text-slate-300 font-bold" title="Operating days">
                    {{ $cronInfo['operating_days'] === 'mon_sat' ? '🗓️ Mon–Sat (Excl. Sun)' : '🗓️ All 7 Days' }}
                </span>
                <span class="px-2.5 py-0.5 rounded-lg {{ $cronInfo['enable_hourly'] ? 'bg-slate-800 text-slate-300' : 'bg-slate-900 text-slate-500' }}" title="Trading hours hourly refreshes">
                    {{ $cronInfo['enable_hourly'] ? '⚡ Trading Hours Hourly Sync (Active)' : 'Hourly Sync Disabled' }}
                </span>
                <span class="px-2.5 py-0.5 rounded-lg bg-indigo-950 text-indigo-300 border border-indigo-800/80 font-bold flex items-center gap-1.5 shadow-xs" title="Number of active feeds enrolled in automated background cron">
                    <span>⚡</span>
                    <span>Cron Scope:</span>
                    <span class="font-mono text-indigo-200" id="cron-scope-pill-count">{{ $cronInfo['cron_enabled_count'] }} of {{ $stats['active'] }} Active Feeds</span>
                </span>
            </div>

            <button type="button" 
                    @click="showScheduleEditor = !showScheduleEditor" 
                    class="text-xs font-bold text-amber-400 hover:text-amber-300 transition cursor-pointer underline">
                <span x-text="showScheduleEditor ? 'Hide Editor ▲' : 'Change Timings ✎'"></span>
            </button>
        </div>

        <!-- Interactive Schedule Timings Form (Drawer) -->
        <div x-show="showScheduleEditor" x-cloak x-transition class="bg-slate-950 border border-amber-600/70 rounded-2xl p-4 sm:p-5 space-y-4 shadow-xl">
            <form action="{{ route('admin.datasources.update-schedule-timings') }}" method="POST">
                @csrf
                <input type="hidden" name="enrolled_sources_submitted" value="1">
                <input type="hidden" name="scheduled_tasks_submitted" value="1">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-sm font-black text-white flex items-center gap-2">
                            <span>⚙️</span> Edit Automated Background Sync Schedule & Cron Feeds
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Update morning/evening sync times and toggle which data sources and background jobs participate in the cPanel cron job.
                        </p>
                    </div>
                    <button type="button" @click="showScheduleEditor = false" class="text-slate-400 hover:text-white text-base font-bold cursor-pointer">✕</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                    <!-- Morning Sync Time -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center gap-1">
                            <span>🌅</span> Morning Sync Time (IST) <span class="text-rose-400">*</span>
                        </label>
                        <input type="time" name="morning_time" value="{{ old('morning_time', $cronInfo['morning_time']) }}" required class="w-full px-3 py-2 text-xs font-mono bg-slate-900 border border-slate-700 text-white rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                        <span class="text-[10px] text-slate-500 mt-1 block">Opening arrivals & auction bids (Default: 06:00).</span>
                    </div>

                    <!-- Evening Sync Time -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center gap-1">
                            <span>🌇</span> Evening Sync Time (IST) <span class="text-rose-400">*</span>
                        </label>
                        <input type="time" name="evening_time" value="{{ old('evening_time', $cronInfo['evening_time']) }}" required class="w-full px-3 py-2 text-xs font-mono bg-slate-900 border border-slate-700 text-white rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                        <span class="text-[10px] text-slate-500 mt-1 block">Final daily mandi closing rates (Default: 18:00).</span>
                    </div>

                    <!-- Optional Mid-day Sync Time -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center gap-1">
                            <span>☀️</span> Mid-Day Sync Time (Optional)
                        </label>
                        <input type="time" name="afternoon_time" value="{{ old('afternoon_time', $cronInfo['afternoon_time']) }}" class="w-full px-3 py-2 text-xs font-mono bg-slate-900 border border-slate-700 text-white rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                        <span class="text-[10px] text-slate-500 mt-1 block">Afternoon trade update (Leave empty to skip).</span>
                    </div>

                    <!-- Operating Days -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center gap-1">
                            <span>🗓️</span> Operating Days <span class="text-rose-400">*</span>
                        </label>
                        <select name="operating_days" class="w-full px-3 py-2 text-xs bg-slate-900 border border-slate-700 text-white rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                            <option value="mon_sat" {{ $cronInfo['operating_days'] === 'mon_sat' ? 'selected' : '' }}>Mon – Sat (Skip Sunday Mandi Holiday)</option>
                            <option value="all" {{ $cronInfo['operating_days'] === 'all' ? 'selected' : '' }}>All 7 Days (Every Day)</option>
                        </select>
                        <span class="text-[10px] text-slate-500 mt-1 block">Karnataka APMCs are closed on Sundays.</span>
                    </div>
                </div>

                <!-- Enrolled Ingestion Feeds in Cron Checklist -->
                <div class="pt-4 border-t border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                        <div>
                            <label class="block text-xs font-bold text-white flex items-center gap-1.5">
                                <span>⚡</span> Services Enrolled in Automated cPanel Cron
                            </label>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Check the upstream feeds that should execute automatically on this cron schedule. Unchecked feeds are excluded from background cron (you can still run them manually on-demand).
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="$el.closest('form').querySelectorAll('.cron-source-cb').forEach(cb => cb.checked = true)" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 underline cursor-pointer">Select All</button>
                            <span class="text-slate-600">|</span>
                            <button type="button" @click="$el.closest('form').querySelectorAll('.cron-source-cb').forEach(cb => cb.checked = false)" class="text-[11px] font-bold text-rose-400 hover:text-rose-300 underline cursor-pointer">Deselect All</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 pt-1">
                        @foreach($cronInfo['all_sources'] as $src)
                            <label class="flex items-start gap-2.5 p-2.5 rounded-xl border transition cursor-pointer select-none {{ $src->is_cron_enabled ? 'bg-slate-900/90 border-slate-700 hover:border-emerald-500/70' : 'bg-slate-950/60 border-slate-800/80 hover:border-slate-700 opacity-80' }}">
                                <input type="checkbox" 
                                       name="enrolled_sources[]" 
                                       value="{{ $src->id }}" 
                                       {{ $src->is_cron_enabled ? 'checked' : '' }} 
                                       class="cron-source-cb mt-0.5 rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-950">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-bold text-white truncate flex items-center gap-1.5">
                                        <span>{{ $src->name }}</span>
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-400 truncate mt-0.5">{{ $src->code }}</div>
                                    <div class="mt-1 flex items-center gap-1 flex-wrap">
                                        @if($src->is_active)
                                             <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-950/80 text-emerald-300 border border-emerald-800/60">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-800 text-slate-400">Paused</span>
                                        @endif
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-mono text-cyan-300 bg-cyan-950/60 border border-cyan-800/50 truncate max-w-[120px]">
                                            {{ class_basename($src->provider_class) }}
                                        </span>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Scheduled Background Tasks Master Switches -->
                <div class="pt-4 border-t border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                        <div>
                            <label class="block text-xs font-bold text-white flex items-center gap-1.5">
                                <span>🤖</span> Scheduled Background Tasks Master Switches
                            </label>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Enable or pause individual scheduled background jobs executed by Laravel's cron worker. Unchecked tasks will be skipped during automated cron runs.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="$el.closest('form').querySelectorAll('.cron-task-cb').forEach(cb => cb.checked = true)" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 underline cursor-pointer">Select All</button>
                            <span class="text-slate-600">|</span>
                            <button type="button" @click="$el.closest('form').querySelectorAll('.cron-task-cb').forEach(cb => cb.checked = false)" class="text-[11px] font-bold text-rose-400 hover:text-rose-300 underline cursor-pointer">Deselect All</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 pt-1">
                        @foreach($cronInfo['scheduled_tasks'] ?? [] as $tKey => $task)
                            @php
                                $taskEnabled = (bool) ($task['enabled'] ?? $task['is_active'] ?? true);
                                $taskKey = $task['key'] ?? $tKey;
                                $taskName = $task['name'] ?? $task['title'] ?? $taskKey;
                                $taskDesc = $task['desc'] ?? $task['purpose'] ?? '';
                            @endphp
                            <label class="flex items-start gap-2.5 p-2.5 rounded-xl border transition cursor-pointer select-none {{ $taskEnabled ? 'bg-slate-900/90 border-slate-700 hover:border-emerald-500/70' : 'bg-slate-950/60 border-slate-800/80 hover:border-slate-700 opacity-70' }}">
                                <input type="checkbox" 
                                       name="scheduled_tasks[]" 
                                       value="{{ $taskKey }}" 
                                       {{ $taskEnabled ? 'checked' : '' }} 
                                       class="cron-task-cb mt-0.5 rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-950">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-bold text-white flex items-center justify-between gap-1">
                                        <span class="truncate">{{ $taskName }}</span>
                                        <span class="text-[10px] font-mono px-1.5 py-0.2 rounded shrink-0 {{ $taskEnabled ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/60' : 'bg-slate-800 text-slate-400' }}">
                                            {{ $taskEnabled ? 'Active' : 'Paused' }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-1 leading-relaxed">{{ $taskDesc }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Checkboxes and Save Button -->
                <div class="pt-3 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1.5">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-300 select-none">
                            <input type="checkbox" name="enable_hourly" value="1" {{ $cronInfo['enable_hourly'] ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                            <span>Enable hourly sync during active market trading hours (10:00 AM – 05:00 PM)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-emerald-400 font-semibold block select-none">
                            <input type="checkbox" name="apply_to_sources" value="1" checked class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                            <span>Apply these timings to all active data sources automatically</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" @click="showScheduleEditor = false" class="px-3.5 py-2 text-xs font-bold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl transition shadow-sm cursor-pointer flex items-center gap-1.5">
                            <span>💾</span>
                            <span>Save Cron Timings & Feeds</span>
                        </button>
                    </div>
                </div>
            </form>
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
        <div class="px-4 sm:px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-white flex items-center gap-2">
                <span>🔌</span> Configured Providers ({{ $dataSources->total() }})
            </h2>
            <div class="text-xs font-semibold text-slate-400">cPanel Async Batch Compatible</div>
        </div>

        <!-- Mobile Table Swipe Cue -->
        <div class="sm:hidden px-4 py-2 bg-slate-950/80 border-b border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-1.5 font-medium">
                <span>👉</span> Scroll horizontally for adapters & actions
            </span>
            <span class="text-[10px] text-slate-500 font-mono">Swipe ↔</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Source Name & Code</th>
                        <th class="py-3.5 px-4">Provider Adapter</th>
                        <th class="py-3.5 px-4">Configured Crops</th>
                        <th class="py-3.5 px-4">Sync Frequency</th>
                        <th class="py-3.5 px-4">Last Sync</th>
                        <th class="py-3.5 px-4 text-center">Cron Schedule</th>
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
                                @if($source->code === 'krama_karnataka')
                                    <div class="flex items-center gap-2 flex-wrap mt-1">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-black bg-emerald-950 text-emerald-300 border border-emerald-700/80 shadow-xs">
                                            <span>🥇</span>
                                            <span>Primary Live Karnataka Source (Daily APMC Feed)</span>
                                        </span>
                                    </div>

                                    <!-- Automatic Failover Toggle Switch to AGMARKNET -->
                                    <div class="mt-2.5 p-2 rounded-xl bg-slate-950/80 border border-slate-800 flex items-center justify-between gap-3 max-w-sm"
                                         x-data="{ 
                                             failoverEnabled: {{ \App\Models\SystemSetting::get('krama_agmarknet_failover_enabled', true) ? 'true' : 'false' }}, 
                                             loading: false,
                                             async toggleFailover() {
                                                 this.loading = true;
                                                 try {
                                                     const res = await fetch('{{ route('admin.datasources.toggle-krama-failover') }}', {
                                                         method: 'POST',
                                                         headers: {
                                                             'Content-Type': 'application/json',
                                                             'Accept': 'application/json',
                                                             'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                         },
                                                         body: JSON.stringify({ enabled: !this.failoverEnabled })
                                                     });
                                                     const data = await res.json();
                                                     if (data.ok) {
                                                         this.failoverEnabled = data.enabled;
                                                         if (typeof showToast === 'function') {
                                                             showToast(data.message, 'success');
                                                         }
                                                     }
                                                 } catch (e) {
                                                     alert('Failed to toggle AGMARKNET failover.');
                                                 } finally {
                                                     this.loading = false;
                                                 }
                                             }
                                         }">
                                        <div class="min-w-0">
                                            <div class="text-[11px] font-bold flex items-center gap-1.5" :class="failoverEnabled ? 'text-emerald-300' : 'text-slate-400'">
                                                <span>🔄</span>
                                                <span>Auto AGMARKNET Failover:</span>
                                                <span class="font-black uppercase tracking-wider text-[10px] px-1.5 py-0.2 rounded"
                                                      :class="failoverEnabled ? 'bg-emerald-900/80 text-emerald-200 border border-emerald-700' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                                      x-text="failoverEnabled ? 'ON' : 'OFF'"></span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-0.5 leading-tight">
                                                Auto-switches to AGMARKNET if KRAMA returns 0 records or is down
                                            </p>
                                        </div>

                                        <button type="button" 
                                                @click="toggleFailover()"
                                                :disabled="loading"
                                                class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50"
                                                :class="failoverEnabled ? 'bg-emerald-500' : 'bg-slate-700'"
                                                title="Turn ON/OFF automatic switch from KRAMA to AGMARKNET">
                                            <span class="sr-only">Toggle AGMARKNET Failover</span>
                                            <span aria-hidden="true" 
                                                  class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                  :class="failoverEnabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                        </button>
                                    </div>
                                @elseif($source->code === 'agmarknet_official')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-black bg-purple-950 text-purple-300 border border-purple-700/80 mt-1 shadow-xs">
                                        <span>📜</span>
                                        <span>Official AGMARKNET (Multi-Year Historical & Predictions)</span>
                                    </span>
                                @elseif(in_array($source->code, ['coffee_board', 'coconut_board']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-950/70 text-amber-300 border border-amber-800/60 mt-1">
                                        <span>🕷️</span>
                                        <span>Direct Web Scraper (HTML Parser)</span>
                                    </span>
                                @elseif($source->code === 'data_gov_mandi')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-cyan-950/70 text-cyan-300 border border-cyan-800/60 mt-1">
                                        <span>🔌</span>
                                        <span>data.gov.in (National Fallback Feed)</span>
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
                                @if(isset($source->total_configured_crops) && $source->total_configured_crops > 0)
                                    <button type="button" 
                                            @click="openCropSyncModal({{ $source->id }}, '{{ addslashes($source->name) }}')"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold transition border cursor-pointer {{ $source->active_configured_crops > 0 ? 'bg-emerald-950/80 text-emerald-300 border-emerald-800/80 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border-slate-700' }}"
                                            title="Configure which crops get synced for {{ $source->name }}">
                                        <span>🌾</span>
                                        <span id="crop-sync-count-{{ $source->id }}">{{ $source->active_configured_crops }} / {{ $source->total_configured_crops }} Active</span>
                                    </button>
                                @else
                                    <button type="button" 
                                            @click="openCropSyncModal({{ $source->id }}, '{{ addslashes($source->name) }}')"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold text-amber-300 bg-amber-950/80 border border-amber-800/80 hover:bg-amber-900 transition cursor-pointer"
                                            title="Configure which crops get synced for {{ $source->name }}">
                                        <span>🌾</span>
                                        <span>Configure Crops</span>
                                    </button>
                                @endif
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
                            <td class="py-3.5 px-4" id="sync-status-col-{{ $source->id }}">
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
                            <td class="py-3.5 px-4 text-center" id="cron-status-col-{{ $source->id }}">
                                <form action="{{ route('admin.datasources.toggle-cron', $source) }}" method="POST" class="inline" onsubmit="event.preventDefault(); window.toggleCronAjax({{ $source->id }}, this);">
                                    @csrf
                                    <button type="submit" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition cursor-pointer {{ $source->is_cron_enabled ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/80 hover:bg-emerald-900 shadow-xs' : 'bg-amber-950/70 text-amber-300 border border-amber-800/70 hover:bg-amber-900 shadow-xs' }}"
                                            title="{{ $source->is_cron_enabled ? 'Auto-runs on cron schedule. Click to exclude.' : 'Excluded from cron schedule. Click to enroll.' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $source->is_cron_enabled ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                                        <span>{{ $source->is_cron_enabled ? '⚡ Auto Cron' : '⏸️ Excluded' }}</span>
                                    </button>
                                </form>
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
                                    @if($source->code === 'agmarknet_official')
                                        <!-- Official Agmarknet Captcha Sync Modal Trigger -->
                                        <button type="button" 
                                                @click="openAgmarknetCaptchaModal()" 
                                                class="px-2.5 py-1 text-xs font-black text-purple-300 bg-purple-950/90 border border-purple-700/80 hover:bg-purple-900 rounded-xl transition cursor-pointer flex items-center gap-1 shadow-xs" 
                                                title="Official Agmarknet Captcha Historical Sync">
                                            <span>🔑</span>
                                            <span>Captcha Sync</span>
                                        </button>
                                    @endif

                                    <!-- Configure Crops Modal Trigger -->
                                    <button type="button" 
                                            @click="openCropSyncModal({{ $source->id }}, '{{ addslashes($source->name) }}')" 
                                            class="p-1.5 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" 
                                            title="Configure Sync Crops (Whitelist & Presets)">
                                        <span class="text-base">🌾</span>
                                    </button>

                                    <!-- Test Connection Button -->
                                    <button type="button" @click="testConnection('{{ route('admin.datasources.test-connection', $source) }}', '{{ addslashes($source->name) }}')" class="p-1.5 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Test Connection">
                                        <span class="text-base">⚡</span>
                                    </button>

                                    <!-- Interactive Ingestion Sync Trigger -->
                                    <button type="button" 
                                            @click="runSync('{{ route('admin.datasources.trigger-sync', $source) }}', '{{ addslashes($source->name) }}', {{ $source->id }})" 
                                            class="p-1.5 text-slate-400 hover:text-cyan-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" 
                                            :class="syncLoading && activeSyncSourceId === {{ $source->id }} ? 'text-cyan-400 bg-slate-800 ring-1 ring-cyan-500/50' : ''"
                                            title="Run Ingestion Sync (Selected Crops Only)">
                                        <span class="text-base inline-block" :class="syncLoading && activeSyncSourceId === {{ $source->id }} ? 'animate-spin' : ''">🔄</span>
                                    </button>

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
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
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

    <!-- Live Ingestion Sync Console Modal -->
    <div x-show="syncModalOpen" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="sync-modal-title" 
         role="dialog" 
         aria-modal="true"
         @keydown.escape.window="closeSyncModal()">
        
        <!-- Dark Dimming Backdrop -->
        <div x-show="syncModalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80 backdrop-blur-sm" 
             @click="closeSyncModal()"></div>

        <!-- Centering & Safe Padding Wrapper (Never cuts off top header) -->
        <div class="flex min-h-full items-start sm:items-center justify-center p-3 sm:p-5 text-center">
            <!-- Crisp Modal Dialog Box -->
            <div x-show="syncModalOpen" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative z-10 bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl max-w-4xl w-full border border-slate-700 text-white my-auto flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[85vh]">
                
                <!-- Modal Header (Always Visible & Pinned) -->
                <div class="p-4 sm:p-6 pb-4 border-b border-slate-800 flex items-center justify-between shrink-0 bg-slate-900/95 sticky top-0 z-20">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h3 class="text-lg font-black text-white flex items-center gap-2" id="sync-modal-title">
                            <span class="text-cyan-400">🔄</span> Run Ingestion Sync
                        </h3>

                        <!-- Live Status Badges -->
                        <template x-if="syncLoading">
                            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-cyan-950 text-cyan-300 border border-cyan-800 animate-pulse">
                                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                                <span x-text="'Syncing (' + syncElapsedSeconds.toFixed(1) + 's)...'"></span>
                            </span>
                        </template>

                        <template x-if="!syncLoading && syncResult">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black border uppercase tracking-wider"
                                  :class="{
                                      'bg-emerald-950 text-emerald-300 border-emerald-800/80': syncResult?.status === 'success',
                                      'bg-amber-950 text-amber-300 border-amber-800/80': syncResult?.status === 'partial',
                                      'bg-rose-950 text-rose-300 border-rose-800/80': syncResult?.status === 'failed' || !syncResult?.ok
                                  }"
                                  x-text="syncResult?.status === 'success' ? '✓ Ingestion Successful' : (syncResult?.status === 'partial' ? '⚠️ Completed with Warnings' : '✕ Ingestion Failed')">
                            </span>
                        </template>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-1 flex items-center gap-1.5 flex-wrap">
                        <span>Feed Provider: <strong class="text-slate-200" x-text="syncSourceName"></strong></span>
                        <span class="text-slate-600">•</span>
                        <span>Target Date: <strong class="text-cyan-400 font-mono">{{ now()->format('Y-m-d') }} (Today)</strong></span>
                    </p>
                </div>
                <button type="button" @click="closeSyncModal()" class="text-slate-400 hover:text-white text-2xl font-bold cursor-pointer p-1 rounded-lg hover:bg-slate-800 transition">✕</button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="overflow-y-auto modal-thin-scrollbar p-5 sm:p-6 space-y-5 flex-1">
                <!-- 1. Sync In Progress State -->
                <div x-show="syncLoading" class="py-10 text-center space-y-6">
                    <div class="relative inline-flex items-center justify-center">
                        <div class="w-16 h-16 rounded-full border-4 border-slate-800 border-t-cyan-400 border-r-emerald-400 animate-spin"></div>
                        <span class="absolute text-xl">🌾</span>
                    </div>

                    <div>
                        <h4 class="text-base font-bold text-white">Ingesting Daily APMC Mandi Records...</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                            Connecting to remote government provider endpoint. Records are being streamed, validated, and normalized into canonical format.
                        </p>
                    </div>

                    <!-- Pipeline Steps Visualizer -->
                    <div class="max-w-md mx-auto bg-slate-950/80 border border-slate-800 rounded-xl p-4 text-left space-y-2.5 text-xs">
                        <div class="flex items-center gap-2 text-slate-300 font-medium">
                            <span class="text-emerald-400">✓</span>
                            <span>Provider Handshake & Auth check</span>
                        </div>
                        <div class="flex items-center gap-2 text-cyan-300 font-bold animate-pulse">
                            <span class="inline-block animate-spin text-[10px]">🔄</span>
                            <span>Streaming and parsing payload records...</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                            <span>⏳</span>
                            <span>Checksum deduplication & canonical matching</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                            <span>⏳</span>
                            <span>Atomic database upsert & historical price indexing</span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500 italic">
                        External government APIs typically take 3 to 15 seconds to respond. Please do not navigate away.
                    </p>
                </div>

                <!-- 2. Sync Completed State -->
                <div x-show="!syncLoading && syncResult" class="space-y-5">
                    <!-- KPI Metrics Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm text-center sm:text-left">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fetched</div>
                            <div class="text-xl font-black text-white mt-0.5" x-text="syncResult?.received ?? 0"></div>
                            <div class="text-[10px] text-slate-500">records streamed</div>
                        </div>
                        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm text-center sm:text-left">
                            <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Inserted</div>
                            <div class="text-xl font-black text-emerald-400 mt-0.5" x-text="syncResult?.inserted ?? 0"></div>
                            <div class="text-[10px] text-slate-500">new rates saved</div>
                        </div>
                        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm text-center sm:text-left">
                            <div class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider">Updated</div>
                            <div class="text-xl font-black text-cyan-400 mt-0.5" x-text="syncResult?.updated ?? 0"></div>
                            <div class="text-[10px] text-slate-500">prices refreshed</div>
                        </div>
                        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm text-center sm:text-left">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Duplicates</div>
                            <div class="text-xl font-black text-slate-400 mt-0.5" x-text="syncResult?.duplicate ?? 0"></div>
                            <div class="text-[10px] text-slate-500">unchanged checksum</div>
                        </div>
                        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm text-center sm:text-left"
                             :class="(syncResult?.rejected ?? 0) > 0 ? 'border-rose-800/80 bg-rose-950/20' : ''">
                            <div class="text-[10px] font-bold uppercase tracking-wider" :class="(syncResult?.rejected ?? 0) > 0 ? 'text-rose-400' : 'text-slate-400'">Unmapped</div>
                            <div class="text-xl font-black mt-0.5" :class="(syncResult?.rejected ?? 0) > 0 ? 'text-rose-400' : 'text-slate-400'" x-text="syncResult?.rejected ?? 0"></div>
                            <div class="text-[10px] text-slate-500">needs alias map</div>
                        </div>
                        <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 shadow-sm text-center sm:text-left">
                            <div class="text-[10px] font-bold text-amber-300 uppercase tracking-wider">Duration</div>
                            <div class="text-xl font-black text-amber-300 mt-0.5" x-text="((syncResult?.duration_ms ?? 0) / 1000).toFixed(2) + 's'"></div>
                            <div class="text-[10px] text-slate-500">execution time</div>
                        </div>
                    </div>

                    <!-- Error Alert if any -->
                    <div x-show="syncResult?.error || !syncResult?.ok" 
                         class="p-3.5 bg-rose-950/70 border border-rose-800/90 rounded-xl text-xs text-rose-300 font-semibold leading-relaxed flex items-start gap-2 shadow-sm">
                        <span class="text-base shrink-0">⚠️</span>
                        <div>
                            <div class="font-black text-rose-200 uppercase tracking-wide text-[11px]">Sync Execution Error:</div>
                            <div class="mt-0.5 text-rose-300" x-text="syncResult?.error || syncResult?.message"></div>
                        </div>
                    </div>

                    <!-- Warning about unmapped / held records -->
                    <div x-show="(syncResult?.rejected ?? 0) > 0" 
                         class="p-3.5 bg-amber-950/40 border border-amber-800/70 rounded-xl text-xs text-amber-200 flex items-start gap-2.5">
                        <span class="text-base shrink-0">⚠️</span>
                        <div>
                            <span class="font-bold text-amber-300">Action Required:</span>
                            <span x-text="(syncResult?.rejected ?? 0) + ' record(s) could not be matched automatically. Use the \'Quick Map & Retry\' button on unmapped crops below to assign the canonical crop and reprocess held records instantly.'"></span>
                        </div>
                    </div>

                    <!-- Crops Breakdown Section -->
                    <div class="bg-slate-950 rounded-2xl border border-slate-800 p-4 space-y-3.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-white flex items-center gap-1.5">
                                    <span>🌾</span> Ingested Crops Breakdown
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-800 text-slate-300" x-text="getTotalCropsCount() + ' commodities'"></span>
                            </div>

                            <!-- Tabs & Search Filter -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="inline-flex rounded-xl bg-slate-900 p-1 border border-slate-800 text-xs">
                                    <button type="button" 
                                            @click="cropFilterTab = 'all'" 
                                            class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                            :class="cropFilterTab === 'all' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400 hover:text-white'">
                                        All (<span x-text="getTotalCropsCount()"></span>)
                                    </button>
                                    <button type="button" 
                                            @click="cropFilterTab = 'synced'" 
                                            class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                            :class="cropFilterTab === 'synced' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/60 shadow-sm' : 'text-slate-400 hover:text-white'">
                                        Synced (<span x-text="getSyncedCropsCount()"></span>)
                                    </button>
                                    <button type="button" 
                                            @click="cropFilterTab = 'failed'" 
                                            class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                            :class="cropFilterTab === 'failed' ? 'bg-rose-950 text-rose-400 border border-rose-800/60 shadow-sm' : 'text-slate-400 hover:text-white'">
                                        Unmapped (<span x-text="getFailedCropsCount()"></span>)
                                    </button>
                                </div>

                                <div class="relative">
                                    <input type="text" 
                                           x-model="cropSearchQuery" 
                                           placeholder="Filter crop..." 
                                           class="bg-slate-900 border border-slate-800 text-white rounded-xl px-2.5 py-1 text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none w-36 sm:w-44">
                                    <button type="button" 
                                            x-show="cropSearchQuery" 
                                            @click="cropSearchQuery = ''" 
                                            class="absolute right-2 top-1.5 text-slate-500 hover:text-slate-300 text-xs">✕</button>
                                </div>
                            </div>
                        </div>

                        <!-- Crops Table -->
                        <div class="overflow-x-auto overflow-y-auto max-h-80 rounded-xl border border-slate-800/80">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800 tracking-wider sticky top-0 z-10">
                                    <tr>
                                        <th class="py-2.5 px-3.5 w-5/12">Commodity / Crop</th>
                                        <th class="py-2.5 px-3.5 w-2/12">Status</th>
                                        <th class="py-2.5 px-3.5 w-3/12">Ingestion Stats</th>
                                        <th class="py-2.5 px-3.5 w-2/12 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <template x-for="crop in getFilteredCrops()" :key="crop.raw_name || crop.crop_name">
                                    <tbody class="divide-y divide-slate-800/60 border-t border-slate-800/40">
                                        <!-- Main Row -->
                                        <tr class="hover:bg-slate-900/50 transition">
                                            <td class="py-3 px-3.5">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="rounded-lg bg-emerald-950/80 border border-emerald-800/60 flex items-center justify-center text-xs font-bold text-emerald-400 shrink-0" style="width: 28px; height: 28px; min-width: 28px; max-width: 28px;">
                                                        🌾
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="font-bold text-white text-xs tracking-wide" x-text="crop.crop_name || crop.raw_name"></div>
                                                        <template x-if="crop.raw_name && crop.raw_name !== crop.crop_name">
                                                            <div class="text-[10px] text-slate-400 font-mono" x-text="'Raw: ' + crop.raw_name"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3.5">
                                                <template x-if="crop.status === 'synced'">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/90 text-emerald-300 border border-emerald-800/80">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Synced
                                                    </span>
                                                </template>
                                                <template x-if="crop.status === 'partial'">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-950/90 text-amber-300 border border-amber-800/80">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Partial
                                                    </span>
                                                </template>
                                                <template x-if="crop.status === 'failed'">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-950/90 text-rose-300 border border-rose-800/80">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Needs Mapping
                                                    </span>
                                                </template>
                                                <template x-if="crop.status === 'skipped'">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Excluded
                                                    </span>
                                                </template>
                                            </td>
                                            <td class="py-3 px-3.5">
                                                <div class="flex items-center gap-2 text-xs font-mono flex-wrap">
                                                    <span class="text-slate-400 font-bold" title="Total received" x-text="crop.received + ' rec'"></span>
                                                    <template x-if="crop.inserted > 0">
                                                        <span class="text-emerald-400 font-bold" title="New records" x-text="'+' + crop.inserted + ' new'"></span>
                                                    </template>
                                                    <template x-if="crop.updated > 0">
                                                        <span class="text-cyan-400 font-bold" title="Updated prices" x-text="'~' + crop.updated + ' upd'"></span>
                                                    </template>
                                                    <template x-if="crop.duplicate > 0">
                                                        <span class="text-slate-500" title="Duplicates" x-text="'⊘' + crop.duplicate + ' dup'"></span>
                                                    </template>
                                                    <template x-if="crop.rejected > 0">
                                                        <span class="text-rose-400 font-bold" title="Unmapped / held" x-text="'!' + crop.rejected + ' unmapped'"></span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3.5 text-right">
                                                <template x-if="crop.rejected > 0 || !crop.crop_id">
                                                    <button type="button" 
                                                            @click="toggleCropDrawer(crop)" 
                                                            class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer inline-flex items-center gap-1.5 shadow-sm"
                                                            :class="crop.drawerOpen ? 'bg-amber-600 text-white hover:bg-amber-500' : 'bg-amber-950/80 text-amber-300 border border-amber-800/80 hover:bg-amber-900'">
                                                        <span>🔀</span>
                                                        <span x-text="crop.drawerOpen ? 'Close Drawer' : 'Quick Map & Retry'"></span>
                                                    </button>
                                                </template>
                                                <template x-if="crop.rejected === 0 && crop.crop_id">
                                                    <span class="text-xs text-slate-500 inline-flex items-center gap-1">
                                                        <span class="text-emerald-400">✓</span> Ingested
                                                    </span>
                                                </template>
                                            </td>
                                        </tr>

                                        <!-- Inline Quick-Mapping Drawer Row -->
                                        <tr x-show="crop.drawerOpen" class="bg-slate-950/90 border-b border-slate-800">
                                            <td colspan="4" class="px-4 py-3.5">
                                                <div class="bg-slate-900 border border-amber-700/60 rounded-xl p-3.5 space-y-3">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="text-amber-400 text-sm">🔀</span>
                                                                <span class="text-xs font-bold text-white">
                                                                    Quick-Map Raw Commodity: <code class="text-amber-300 font-mono text-xs px-1.5 py-0.5 bg-black/50 rounded" x-text="crop.raw_name"></code>
                                                                </span>
                                                            </div>
                                                            <p class="text-[11px] text-slate-400 mt-1">
                                                                Select the matching canonical crop to save a permanent mapping rule and reprocess held records immediately.
                                                            </p>
                                                        </div>
                                                        <button type="button" @click="crop.drawerOpen = false" class="text-slate-400 hover:text-white text-xs font-bold cursor-pointer">✕</button>
                                                    </div>

                                                    <!-- Rejection reason pills -->
                                                    <template x-if="crop.rejection_reasons && crop.rejection_reasons.length > 0">
                                                        <div class="bg-black/50 p-2 rounded-lg border border-slate-800 text-[11px] text-rose-300 space-y-1">
                                                            <div class="font-bold text-[10px] uppercase tracking-wider text-rose-400">Ingestion Warning:</div>
                                                            <template x-for="reason in crop.rejection_reasons" :key="reason">
                                                                <div class="flex items-start gap-1">
                                                                    <span class="text-rose-400">•</span>
                                                                    <span x-text="reason"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    <!-- Mapping Selection and Retry Action -->
                                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-0.5">
                                                        <div class="flex-1">
                                                            <select x-model="crop.selectedCropId" class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                                                <option value="">-- Select Canonical Crop --</option>
                                                                <template x-for="c in canonicalCrops" :key="c.id">
                                                                    <option :value="c.id" x-text="c.name + (c.name_kn ? ' (' + c.name_kn + ')' : '')"></option>
                                                                </template>
                                                            </select>
                                                        </div>
                                                        <button type="button" 
                                                                @click="retryCropSync(crop)" 
                                                                :disabled="!crop.selectedCropId || retryingCrops[crop.raw_name || crop.crop_name]"
                                                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5 shadow-sm shrink-0">
                                                            <template x-if="retryingCrops[crop.raw_name || crop.crop_name]">
                                                                <span class="inline-block animate-spin">🔄</span>
                                                            </template>
                                                            <template x-if="!retryingCrops[crop.raw_name || crop.crop_name]">
                                                                <span>⚡</span>
                                                            </template>
                                                            <span x-text="retryingCrops[crop.raw_name || crop.crop_name] ? 'Reprocessing...' : 'Save Mapping & Retry Now'"></span>
                                                        </button>
                                                    </div>

                                                    <!-- Success Message Alert -->
                                                    <template x-if="crop.retrySuccessMessage">
                                                        <div class="p-2.5 bg-emerald-950/80 border border-emerald-800 rounded-lg text-xs text-emerald-300 font-semibold flex items-center gap-1.5">
                                                            <span>✓</span>
                                                            <span x-text="crop.retrySuccessMessage"></span>
                                                        </div>
                                                    </template>

                                                    <!-- Error Message Alert -->
                                                    <template x-if="crop.retryErrorMessage">
                                                        <div class="p-2.5 bg-rose-950/80 border border-rose-800 rounded-lg text-xs text-rose-300 font-semibold flex items-center gap-1.5">
                                                            <span>⚠️</span>
                                                            <span x-text="crop.retryErrorMessage"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </template>

                                <!-- Empty Filter State -->
                                <tbody x-show="getFilteredCrops().length === 0">
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-xs">
                                            No commodities found matching the active filter.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 sm:p-5 bg-slate-950 border-t border-slate-800 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <template x-if="hasRejectedCrops() && !syncLoading">
                        <button type="button" 
                                @click="retryAllFailed()" 
                                :disabled="retryingAll"
                                class="px-3.5 py-2 bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-sm">
                            <span :class="retryingAll ? 'animate-spin inline-block' : ''">⚡</span>
                            <span x-text="retryingAll ? 'Retrying All...' : 'Retry All Unmapped Records'"></span>
                        </button>
                    </template>
                    <template x-if="!hasRejectedCrops() && !syncLoading && syncResult">
                        <span class="text-xs text-emerald-400 font-medium flex items-center gap-1">
                            <span>✓</span> All parsed commodities mapped successfully
                        </span>
                    </template>
                </div>

                <button type="button" 
                        @click="closeSyncModal()" 
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition cursor-pointer">
                    Close Console
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- ========================================================================= -->
    <!-- AGMARKNET OFFICIAL HISTORICAL SYNC & CAPTCHA MODAL                        -->
    <!-- ========================================================================= -->
    <div x-show="agmarknetCaptchaModalOpen" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6" 
         role="dialog" 
         aria-modal="true"
         @keydown.escape.window="agmarknetCaptchaModalOpen = false">
        
        <!-- Backdrop -->
        <div x-show="agmarknetCaptchaModalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80" 
             @click="agmarknetCaptchaModalOpen = false"></div>

        <!-- Dialog Box -->
        <div x-show="agmarknetCaptchaModalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative z-10 bg-slate-900 border border-purple-500/40 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl space-y-0 text-left">
            
            <!-- Modal Header -->
            <div class="p-5 sm:p-6 border-b border-slate-800 bg-gradient-to-r from-purple-950/80 to-slate-900 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-900/60 border border-purple-600/40 flex items-center justify-center text-xl shadow-inner">
                        📜
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white">Official AGMARKNET Historical Sync</h3>
                        <p class="text-xs text-purple-300/80 font-medium">Directorate of Marketing & Inspection (DMI / MoA&FW)</p>
                    </div>
                </div>
                <button type="button" @click="agmarknetCaptchaModalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 sm:p-6 space-y-4 text-xs">
                <!-- Info Notice -->
                <div class="bg-purple-950/40 border border-purple-800/60 rounded-xl p-3 text-purple-200 text-xs leading-relaxed">
                    💡 <strong>Multi-Year Training & Predictions:</strong> AGMARKNET provides verified multi-year historical trade data. To access the official government archive, complete the quick visual security verification below.
                </div>

                <!-- Date Range Filters -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">From Date</label>
                        <input type="date" x-model="agmarknetFromDate" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">To Date</label>
                        <input type="date" x-model="agmarknetToDate" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-purple-500">
                    </div>
                </div>

                <!-- Optional Crop Filter -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">Commodity / Crop (Optional)</label>
                    <select x-model="agmarknetCropId" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs outline-none focus:border-purple-500">
                        <option value="">All Karnataka Crops</option>
                        <template x-for="crop in canonicalCrops" :key="crop.id">
                            <option :value="crop.id" x-text="crop.name + ' (' + (crop.name_kn || '') + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- CAPTCHA Box -->
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                            <span>🛡️</span> Security Verification (Official API)
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
                               class="w-full bg-slate-900 border border-slate-700 text-center tracking-widest text-base font-mono font-black text-amber-300 rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-purple-500 placeholder:normal-case placeholder:text-slate-500 placeholder:text-xs placeholder:tracking-normal">
                        <p class="text-[10px] text-slate-400 text-center mt-1">
                            Aa Case-Sensitive: Match uppercase & lowercase letters exactly as shown in the image above.
                        </p>
                    </div>
                </div>

                <!-- Error & Success Messages -->
                <template x-if="agmarknetSyncError">
                    <div class="bg-rose-950/80 border border-rose-800 text-rose-300 rounded-xl p-3 text-xs flex items-center gap-2">
                        <span>⚠️</span>
                        <span x-text="agmarknetSyncError"></span>
                    </div>
                </template>

                <template x-if="agmarknetSyncStatus">
                    <div class="bg-emerald-950/80 border border-emerald-800 text-emerald-300 rounded-xl p-3 text-xs flex items-center gap-2">
                        <span>✓</span>
                        <span x-text="agmarknetSyncStatus"></span>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 sm:p-5 bg-slate-950 border-t border-slate-800 flex items-center justify-between">
                <button type="button" @click="agmarknetCaptchaModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitAgmarknetHistoricalSync()" 
                        :disabled="agmarknetCaptchaSyncing || agmarknetCaptchaLoading"
                        class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-purple-600/30 transition flex items-center gap-2 cursor-pointer">
                    <span x-show="agmarknetCaptchaSyncing" class="animate-spin inline-block">🔄</span>
                    <span x-text="agmarknetCaptchaSyncing ? 'Ingesting Archives...' : 'Verify & Ingest Archives 🚀'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Crop Synchronization Configuration Modal (1-Click Presets & Individual Selection) -->
    <div x-show="cropSyncModalOpen" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true"
         @keydown.escape.window="if (!cropSyncSaving) cropSyncModalOpen = false">
        
        <!-- Dark Dimming Backdrop -->
        <div x-show="cropSyncModalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80 backdrop-blur-sm" 
             @click="if (!cropSyncSaving) cropSyncModalOpen = false"></div>

        <!-- Centering & Safe Padding Wrapper (Never cuts off top header) -->
        <div class="flex min-h-full items-start sm:items-center justify-center p-2 sm:p-4 text-center">
            
            <!-- Crisp Modal Dialog Box -->
            <div x-show="cropSyncModalOpen" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative z-10 bg-slate-900 border-2 border-emerald-600/80 rounded-2xl text-left overflow-hidden shadow-2xl max-w-5xl w-full text-white my-auto flex flex-col"
                 style="max-height: 85vh; height: 85vh;">
                
            <!-- 1. FIXED HEADER (Always pinned at top, shrink: 0) -->
            <div style="flex-shrink: 0 !important; background: #020617; border-bottom: 1px solid #1e293b; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="text-2xl p-2 rounded-2xl bg-emerald-950 text-emerald-400 border border-emerald-800/80 shrink-0">🌾</span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-sm sm:text-base font-black text-white truncate" x-text="'Configure Sync Crops — ' + cropSyncSourceName"></h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-950 text-emerald-300 border border-emerald-700 font-mono" 
                                  x-text="selectedCropIds.size + ' / ' + (cropSyncData?.crops?.length || 0) + ' Crops Active'">
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-medium mt-0.5 hidden sm:block">
                            Select which commodities will be fetched & ingested from this provider. Unselected items (livestock, wood, dals) are skipped to save server memory and ensure clean data.
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="cropSyncModalOpen = false" 
                        :disabled="cropSyncSaving"
                        style="padding: 6px 12px; color: #94a3b8; border-radius: 8px; background: transparent; border: none; cursor: pointer; font-size: 1.25rem; line-height: 1; flex-shrink: 0; margin-left: 8px;"
                        class="hover:text-white hover:bg-slate-800 transition">✕</button>
            </div>

            <!-- 2. MODAL LOADING STATE (Centered, flex-1) -->
            <div x-show="cropSyncLoading" 
                 style="flex: 1 1 0% !important; min-height: 0 !important; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px; text-align: center;">
                <span class="text-3xl animate-spin">🔄</span>
                <div class="text-sm font-bold text-white mt-3">Loading crops catalog & sync settings...</div>
                <div class="text-xs text-slate-400 mt-1">Inspecting database and source configurations...</div>
            </div>

            <!-- 3. SCROLLABLE BODY (Only this scrolls! flex: 1, min-height: 0, overflow-y: auto) -->
            <div x-show="!cropSyncLoading && cropSyncData" 
                 class="space-y-4 modal-thin-scrollbar"
                 style="flex: 1 1 0% !important; min-height: 0 !important; overflow-y: auto !important; padding: 16px 20px; -webkit-overflow-scrolling: touch; box-sizing: border-box;">
                    
                    <!-- Specialized Single/Dedicated Commodity Board Notice -->
                    <div x-show="cropSyncData?.datasource?.is_specialized" 
                         class="bg-gradient-to-r from-emerald-950/80 via-slate-900 to-slate-950 border border-emerald-500/50 rounded-2xl p-4 flex items-start gap-3.5 shadow-md">
                        <span class="text-2xl p-2 rounded-xl bg-emerald-900/60 text-emerald-300 border border-emerald-700/60 shrink-0">🏛️</span>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-black uppercase tracking-wider text-emerald-300" x-text="cropSyncData?.datasource?.specialization_title"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-900/90 text-emerald-200 border border-emerald-600/60 tracking-wider">Statutory Board</span>
                            </div>
                            <p class="text-xs text-slate-300 mt-1.5 leading-relaxed" x-text="cropSyncData?.datasource?.specialization_note"></p>
                        </div>
                    </div>

                    <!-- Quick Action Presets (1-Click) Section (Only for Multi-Commodity Portals like KRAMA & Agmarknet) -->
                    <div x-show="!cropSyncData?.datasource?.is_specialized" class="bg-slate-950/90 border border-slate-800 rounded-2xl p-3 sm:p-4 space-y-2.5">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <span class="text-xs font-black text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                                <span>⚡</span> Quick Action Presets (1-Click Auto-Select)
                            </span>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" @click="selectAllVisible()" class="text-slate-400 hover:text-cyan-300 transition font-bold cursor-pointer underline">
                                    Select All Filtered
                                </button>
                                <span class="text-slate-600">·</span>
                                <button type="button" @click="deselectAllVisible()" class="text-slate-400 hover:text-rose-400 transition font-bold cursor-pointer underline">
                                    Deselect All Filtered
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap pt-1">
                            <!-- Preset 1: Recommended Karnataka Core -->
                            <button type="button" 
                                    @click="applyPresetKarnatakaCore()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition cursor-pointer border border-emerald-500"
                                    title="Select the ~60 recommended essential Karnataka crops and uncheck all non-crops">
                                <span>🌟</span>
                                <span>Recommended Karnataka Core (~60 Clean Crops)</span>
                            </button>

                            <!-- Preset 2: Deselect Non-Crops -->
                            <button type="button" 
                                    @click="applyPresetDeselectNonCrops()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-950/80 hover:bg-rose-900 text-rose-300 border border-rose-800/80 transition cursor-pointer"
                                    title="Uncheck livestock (sheep/bull), mill-processed dals, wood, and cut flowers">
                                <span>❌</span>
                                <span>Deselect Non-Crops & Byproducts</span>
                            </button>

                            <!-- Preset 3: Plantation & Cash -->
                            <button type="button" 
                                    @click="applyPresetPlantation()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-950/80 hover:bg-amber-900 text-amber-300 border border-amber-800/80 transition cursor-pointer"
                                    title="Toggle Arecanut, Coconut, Copra, Cotton, Jaggery, Cashewnut, Coffee">
                                <span>🌴</span>
                                <span>Plantation & Cash Crops</span>
                            </button>

                            <!-- Preset 4: Vegetables Only -->
                            <button type="button" 
                                    @click="applyPresetVegetables()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-teal-950/80 hover:bg-teal-900 text-teal-300 border border-teal-800/80 transition cursor-pointer"
                                    title="Toggle Tomato, Onion, Potato, Beans, Chilli, Brinjal, Carrot, etc.">
                                <span>🥗</span>
                                <span>Vegetables</span>
                            </button>

                            <!-- Preset 5: Cereals & Pulses -->
                            <button type="button" 
                                    @click="applyPresetCerealsPulses()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-950/80 hover:bg-purple-900 text-purple-300 border border-purple-800/80 transition cursor-pointer"
                                    title="Toggle Maize, Paddy, Rice, Ragi, Jowar, Wheat, Bengalgram, Tur, etc.">
                                <span>🌾</span>
                                <span>Cereals & Pulses</span>
                            </button>
                        </div>
                    </div>

                    <!-- Sticky Search & Category Filter Pills -->
                    <div class="sticky top-0 z-10 bg-slate-900/95 backdrop-blur-xs py-1.5 -my-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <!-- Category Filter Pills -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none flex-wrap">
                            <template x-for="grp in cropSyncData?.filter_groups || []" :key="grp.slug">
                                <button type="button" 
                                        @click="cropSyncSelectedGroup = grp.slug" 
                                        class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                                        :class="cropSyncSelectedGroup === grp.slug ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-950/80 text-slate-400 border-slate-800 hover:text-white hover:bg-slate-800'"
                                        x-text="grp.name">
                                </button>
                            </template>
                        </div>

                        <!-- Search Input -->
                        <div class="relative w-full sm:w-64 shrink-0">
                            <input type="text" 
                                   x-model="cropSyncSearchQuery" 
                                   placeholder="Search crop or ಕನ್ನಡ..." 
                                   class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-950 border border-slate-700 text-white rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                            <span class="absolute left-2.5 top-2 text-xs text-slate-400">🔍</span>
                            <button x-show="cropSyncSearchQuery" 
                                    @click="cropSyncSearchQuery = ''" 
                                    class="absolute right-2.5 top-1.5 text-xs text-slate-400 hover:text-white">✕</button>
                        </div>
                    </div>

                    <!-- Individual Crops Selection Grid (Scrolls smoothly in body) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <template x-for="crop in getVisibleCropSyncList()" :key="crop.id">
                            <div class="relative flex items-center justify-between p-3 rounded-2xl border transition-all cursor-pointer select-none"
                                 :class="selectedCropIds.has(crop.id) 
                                    ? 'bg-emerald-950/40 border-emerald-600/80 shadow-xs' 
                                    : 'bg-slate-950/50 border-slate-800/80 hover:border-slate-700 opacity-70 hover:opacity-100'"
                                 @click="toggleCropSync(crop.id)">
                                
                                <div class="flex items-center gap-3 min-w-0 pr-2">
                                    <!-- Checkbox -->
                                    <input type="checkbox" 
                                           :checked="selectedCropIds.has(crop.id)" 
                                           @click.stop="toggleCropSync(crop.id)"
                                           class="w-4 h-4 rounded text-emerald-600 bg-slate-900 border-slate-700 focus:ring-emerald-500 focus:ring-offset-slate-950 shrink-0 cursor-pointer">

                                    <!-- Crop Details -->
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-bold text-xs text-white truncate" x-text="crop.name"></span>
                                            <span x-show="crop.name_kn" class="text-[11px] text-slate-400 font-medium" x-text="'(' + crop.name_kn + ')'"></span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                            <span class="px-1.5 py-0.2 rounded font-semibold"
                                                  :class="{
                                                      'bg-emerald-950 text-emerald-300': crop.category_slug === 'plantation',
                                                      'bg-amber-950 text-amber-300': crop.category_slug === 'vegetables',
                                                      'bg-purple-950 text-purple-300': crop.category_slug === 'cereals-pulses',
                                                      'bg-rose-950 text-rose-300': crop.category_slug === 'non-crops',
                                                      'bg-cyan-950 text-cyan-300': crop.category_slug === 'spices',
                                                      'bg-yellow-950 text-yellow-300': crop.category_slug === 'oilseeds',
                                                      'bg-blue-950 text-blue-300': crop.category_slug === 'fruits',
                                                  }"
                                                  x-text="crop.category_name">
                                            </span>
                                            <span x-show="crop.records_count > 0" x-text="crop.records_count.toLocaleString() + ' records (' + crop.mandis_count + ' mandis)'"></span>
                                            <span x-show="crop.records_count === 0" class="italic text-slate-500">0 records</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Single Crop Instant Sync Button [⚡ Sync Now] -->
                                <div class="shrink-0 flex items-center" @click.stop>
                                    <button type="button" 
                                            @click="syncSingleCropNow(crop)" 
                                            :disabled="syncingSingleCropId !== null"
                                            class="px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-900 hover:bg-slate-800 text-amber-300 border border-slate-700 hover:border-amber-500 transition shadow-xs flex items-center gap-1 cursor-pointer"
                                            :title="'Instantly fetch & sync only ' + crop.name + ' right now'">
                                        <span :class="syncingSingleCropId === crop.id ? 'animate-spin' : ''">⚡</span>
                                        <span x-text="syncingSingleCropId === crop.id ? 'Syncing...' : 'Sync Now'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            <!-- 4. FIXED FOOTER (Always pinned at bottom, shrink: 0, easily accessible) -->
            <div style="flex-shrink: 0 !important; background: #020617; border-top: 1px solid #1e293b; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-sizing: border-box;">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-slate-300">
                        <span>Configured:</span> 
                        <b class="text-emerald-400" x-text="selectedCropIds.size"></b> 
                        <span class="text-slate-500">/ <span x-text="cropSyncData?.crops?.length || 0"></span> crops</span>
                    </span>
                    <span x-show="cropSyncSaveMessage" x-transition class="text-xs font-bold text-emerald-400 flex items-center gap-1">
                        <span>✓</span>
                        <span x-text="cropSyncSaveMessage"></span>
                    </span>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap justify-end">
                    <!-- Cancel Button -->
                    <button type="button" 
                            @click="cropSyncModalOpen = false" 
                            :disabled="cropSyncSaving"
                            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-700 hover:border-slate-600 transition shadow-sm cursor-pointer disabled:opacity-50">
                        Cancel
                    </button>
                    
                    <!-- Save Configuration Button -->
                    <button type="button" 
                            @click="saveCropSyncConfig(false)" 
                            :disabled="cropSyncSaving"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 active:scale-98 border border-emerald-500 hover:border-emerald-400 shadow-md shadow-emerald-950/50 transition inline-flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <span x-show="cropSyncSaving" class="animate-spin text-sm">🔄</span>
                        <span x-show="!cropSyncSaving" class="text-sm">💾</span>
                        <span>Save Configuration</span>
                    </button>

                    <!-- Save & Run Ingestion Sync Button -->
                    <button type="button" 
                            @click="saveCropSyncConfig(true)" 
                            :disabled="cropSyncSaving"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-black text-white bg-gradient-to-r from-cyan-600 via-sky-600 to-blue-600 hover:from-cyan-500 hover:via-sky-500 hover:to-blue-500 active:scale-98 border border-cyan-400/50 shadow-lg shadow-cyan-950/60 hover:shadow-cyan-900/80 transition inline-flex items-center gap-2 cursor-pointer disabled:opacity-50"
                            title="Save the selected crop whitelist and immediately run live price ingestion for today">
                        <span class="text-sm">⚡</span>
                        <span>Save & Run Ingestion Sync</span>
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
        // Crop Sync Configuration Manager state
        cropSyncModalOpen: false,
        cropSyncLoading: false,
        cropSyncSaving: false,
        cropSyncSourceId: null,
        cropSyncSourceName: '',
        cropSyncSourceCode: '',
        cropSyncData: null,
        cropSyncSearchQuery: '',
        cropSyncSelectedGroup: 'all',
        selectedCropIds: new Set(),
        syncingSingleCropId: null,
        cropSyncSaveMessage: '',

        async openCropSyncModal(sourceId, sourceName) {
            this.cropSyncSourceId = sourceId;
            this.cropSyncSourceName = sourceName;
            this.cropSyncModalOpen = true;
            this.cropSyncLoading = true;
            this.cropSyncData = null;
            this.cropSyncSearchQuery = '';
            this.cropSyncSelectedGroup = 'all';
            this.cropSyncSaveMessage = '';

            try {
                const res = await fetch(`{{ url('admin/datasources') }}/${sourceId}/crop-sync`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.cropSyncLoading = false;
                if (data.ok) {
                    this.cropSyncData = data;
                    this.cropSyncSourceCode = data.datasource.code;
                    this.selectedCropIds = new Set(
                        data.crops.filter(c => c.is_enabled).map(c => c.id)
                    );
                } else {
                    alert('Failed to load crop sync configuration: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                this.cropSyncLoading = false;
                alert('Error loading crop sync configuration: ' + err.message);
            }
        },

        applyPresetKarnatakaCore() {
            if (!this.cropSyncData?.presets?.core_crop_ids) return;
            this.selectedCropIds = new Set(this.cropSyncData.presets.core_crop_ids);
        },

        applyPresetDeselectNonCrops() {
            if (!this.cropSyncData?.presets?.non_crop_ids) return;
            const nonCropSet = new Set(this.cropSyncData.presets.non_crop_ids);
            const next = new Set();
            this.selectedCropIds.forEach(id => {
                if (!nonCropSet.has(id)) {
                    next.add(id);
                }
            });
            this.selectedCropIds = next;
        },

        applyPresetPlantation() {
            if (!this.cropSyncData?.presets?.plantation_crop_ids) return;
            const targetIds = this.cropSyncData.presets.plantation_crop_ids;
            const allSelected = targetIds.every(id => this.selectedCropIds.has(id));
            if (allSelected) {
                targetIds.forEach(id => this.selectedCropIds.delete(id));
            } else {
                targetIds.forEach(id => this.selectedCropIds.add(id));
            }
            this.selectedCropIds = new Set(this.selectedCropIds);
        },

        applyPresetVegetables() {
            if (!this.cropSyncData?.presets?.vegetable_crop_ids) return;
            const targetIds = this.cropSyncData.presets.vegetable_crop_ids;
            const allSelected = targetIds.every(id => this.selectedCropIds.has(id));
            if (allSelected) {
                targetIds.forEach(id => this.selectedCropIds.delete(id));
            } else {
                targetIds.forEach(id => this.selectedCropIds.add(id));
            }
            this.selectedCropIds = new Set(this.selectedCropIds);
        },

        applyPresetCerealsPulses() {
            if (!this.cropSyncData?.presets?.cereal_pulse_crop_ids) return;
            const targetIds = this.cropSyncData.presets.cereal_pulse_crop_ids;
            const allSelected = targetIds.every(id => this.selectedCropIds.has(id));
            if (allSelected) {
                targetIds.forEach(id => this.selectedCropIds.delete(id));
            } else {
                targetIds.forEach(id => this.selectedCropIds.add(id));
            }
            this.selectedCropIds = new Set(this.selectedCropIds);
        },

        selectAllVisible() {
            this.getVisibleCropSyncList().forEach(c => this.selectedCropIds.add(c.id));
            this.selectedCropIds = new Set(this.selectedCropIds);
        },

        deselectAllVisible() {
            this.getVisibleCropSyncList().forEach(c => this.selectedCropIds.delete(c.id));
            this.selectedCropIds = new Set(this.selectedCropIds);
        },

        toggleCropSync(cropId) {
            if (this.selectedCropIds.has(cropId)) {
                this.selectedCropIds.delete(cropId);
            } else {
                this.selectedCropIds.add(cropId);
            }
            this.selectedCropIds = new Set(this.selectedCropIds);
        },

        getVisibleCropSyncList() {
            if (!this.cropSyncData?.crops) return [];
            let list = this.cropSyncData.crops;

            if (this.cropSyncSelectedGroup !== 'all') {
                list = list.filter(c => c.category_slug === this.cropSyncSelectedGroup);
            }

            if (this.cropSyncSearchQuery.trim()) {
                const q = this.cropSyncSearchQuery.toLowerCase().trim();
                list = list.filter(c => 
                    c.name.toLowerCase().includes(q) || 
                    (c.name_kn && c.name_kn.toLowerCase().includes(q))
                );
            }

            return list;
        },

        async saveCropSyncConfig(andRunSync = false) {
            this.cropSyncSaving = true;
            this.cropSyncSaveMessage = '';

            try {
                const res = await fetch(`{{ url('admin/datasources') }}/${this.cropSyncSourceId}/crop-sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        crop_ids: Array.from(this.selectedCropIds)
                    })
                });

                const data = await res.json();
                this.cropSyncSaving = false;

                if (data.ok) {
                    this.cropSyncSaveMessage = data.message;
                    const countBadge = document.getElementById(`crop-sync-count-${this.cropSyncSourceId}`);
                    if (countBadge) {
                        countBadge.innerText = `${data.active_crops_count} / ${data.total_crops_count} Active`;
                    }

                    if (andRunSync) {
                        this.cropSyncModalOpen = false;
                        const syncUrl = `{{ url('admin/datasources') }}/${this.cropSyncSourceId}/trigger-sync`;
                        this.runSync(syncUrl, this.cropSyncSourceName, this.cropSyncSourceId);
                    } else {
                        setTimeout(() => this.cropSyncSaveMessage = '', 3500);
                    }
                } else {
                    alert('Error saving configuration: ' + (data.message || data.error || 'Validation error'));
                }
            } catch (err) {
                this.cropSyncSaving = false;
                alert('Save failed: ' + err.message);
            }
        },

        async syncSingleCropNow(crop) {
            this.syncingSingleCropId = crop.id;
            try {
                const res = await fetch(`{{ url('admin/datasources') }}/${this.cropSyncSourceId}/sync-crop/${crop.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await res.json();
                this.syncingSingleCropId = null;

                if (data.ok) {
                    alert(`✅ ${data.message}`);
                    if (data.result && data.result.inserted !== undefined) {
                        crop.records_count = (crop.records_count || 0) + (data.result.inserted || 0) + (data.result.updated || 0);
                    }
                } else {
                    alert(`❌ Sync failed for ${crop.name}: ` + (data.error || 'Unknown error'));
                }
            } catch (err) {
                this.syncingSingleCropId = null;
                alert(`Error syncing ${crop.name}: ` + err.message);
            }
        },

        // Agmarknet Captcha Modal State
        agmarknetCaptchaModalOpen: false,
        agmarknetCaptchaLoading: false,
        agmarknetCaptchaSyncing: false,
        agmarknetCaptchaKey: '',
        agmarknetCaptchaImage: '',
        agmarknetCaptchaCode: '',
        agmarknetFromDate: '{{ now()->subYears(3)->format("Y-m-d") }}',
        agmarknetToDate: '{{ now()->format("Y-m-d") }}',
        agmarknetCropId: '',
        agmarknetSyncStatus: null,
        agmarknetSyncError: null,

        async openAgmarknetCaptchaModal() {
            this.agmarknetCaptchaModalOpen = true;
            this.agmarknetCaptchaCode = '';
            this.agmarknetSyncStatus = null;
            this.agmarknetSyncError = null;
            await this.refreshAgmarknetCaptcha();
        },

        async refreshAgmarknetCaptcha() {
            this.agmarknetCaptchaLoading = true;
            this.agmarknetCaptchaImage = '';
            this.agmarknetSyncError = null;
            try {
                const res = await fetch('{{ route("admin.datasources.agmarknet.captcha") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.ok) {
                    this.agmarknetCaptchaKey = data.captcha_key;
                    this.agmarknetCaptchaImage = data.captcha_image;
                } else {
                    this.agmarknetSyncError = data.error || 'Failed to generate CAPTCHA image.';
                }
            } catch (e) {
                this.agmarknetSyncError = e.message;
            } finally {
                this.agmarknetCaptchaLoading = false;
            }
        },

        async submitAgmarknetHistoricalSync() {
            if (!this.agmarknetCaptchaCode || this.agmarknetCaptchaCode.length < 4) {
                this.agmarknetSyncError = 'Please enter the 6-letter CAPTCHA shown in the image.';
                return;
            }

            this.agmarknetCaptchaSyncing = true;
            this.agmarknetSyncError = null;
            this.agmarknetSyncStatus = null;

            try {
                const res = await fetch('{{ route("admin.datasources.agmarknet.sync-historical") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        captcha_key: this.agmarknetCaptchaKey,
                        captcha_code: this.agmarknetCaptchaCode,
                        from_date: this.agmarknetFromDate,
                        to_date: this.agmarknetToDate,
                        crop_id: this.agmarknetCropId ? parseInt(this.agmarknetCropId) : null,
                    })
                });

                const data = await res.json();
                if (data.ok) {
                    this.agmarknetSyncStatus = data.message || 'Historical sync completed successfully!';
                } else {
                    this.agmarknetSyncError = data.error || 'Verification or sync failed. Please try again.';
                    await this.refreshAgmarknetCaptcha();
                }
            } catch (e) {
                this.agmarknetSyncError = e.message;
                await this.refreshAgmarknetCaptcha();
            } finally {
                this.agmarknetCaptchaSyncing = false;
            }
        },

        // Diagnostic test state
        modalOpen: false,
        isLoading: false,
        currentSourceName: '',
        testResult: null,

        // Sync console state
        syncModalOpen: false,
        syncLoading: false,
        activeSyncSourceId: null,
        syncSourceName: '',
        syncResult: null,
        syncElapsedSeconds: 0,
        syncTimerInterval: null,
        cropFilterTab: 'all', // 'all' | 'synced' | 'failed'
        cropSearchQuery: '',
        retryingCrops: {},
        retryingAll: false,
        canonicalCrops: {{ Js::from($canonicalCrops) }},

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
        },

        async runSync(syncUrl, name, sourceId) {
            this.syncSourceName = name;
            this.activeSyncSourceId = sourceId;
            this.syncResult = null;
            this.syncLoading = true;
            this.syncModalOpen = true;
            this.syncElapsedSeconds = 0;
            this.cropFilterTab = 'all';
            this.cropSearchQuery = '';

            const startTime = performance.now();
            if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
            this.syncTimerInterval = setInterval(() => {
                this.syncElapsedSeconds = (performance.now() - startTime) / 1000;
            }, 100);

            try {
                let res = await fetch(syncUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (res.status === 419) {
                    throw new Error('CSRF session expired. Please refresh the page.');
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

                if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
                this.syncLoading = false;

                // Prepare crops array with UI reactivity properties
                if (data.crops && Array.isArray(data.crops)) {
                    data.crops = data.crops.map(c => ({
                        ...c,
                        drawerOpen: false,
                        selectedCropId: c.crop_id || '',
                        retrySuccessMessage: null,
                        retryErrorMessage: null,
                    }));
                }

                this.syncResult = data;

                // Update table row Last Sync column dynamically if found
                const statusCol = document.getElementById('sync-status-col-' + sourceId);
                if (statusCol && data.datasource) {
                    const statusBadgeClass = data.status === 'success' 
                        ? 'text-emerald-400' 
                        : (data.status === 'partial' ? 'text-amber-400' : 'text-rose-400');
                    const dotClass = data.status === 'success' 
                        ? 'bg-emerald-400' 
                        : (data.status === 'partial' ? 'bg-amber-400' : 'bg-rose-400');
                    const statusLabel = data.status.charAt(0).toUpperCase() + data.status.slice(1);

                    statusCol.innerHTML = `
                        <div class="text-xs font-bold text-white">${data.datasource.last_sync_at}</div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold ${statusBadgeClass}">
                            <span class="w-1.5 h-1.5 rounded-full ${dotClass}"></span>
                            ${statusLabel}
                        </span>
                    `;
                }
            } catch (err) {
                if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
                this.syncLoading = false;
                this.syncResult = {
                    ok: false,
                    status: 'failed',
                    error: err.message,
                    message: 'Sync failed: ' + err.message,
                    received: 0,
                    inserted: 0,
                    updated: 0,
                    duplicate: 0,
                    rejected: 0,
                    duration_ms: Math.round(this.syncElapsedSeconds * 1000),
                    crops: [],
                };
            }
        },

        runSyncAll(syncAllUrl) {
            this.runSync(syncAllUrl, 'All Active Feeds (Today: {{ now()->format('d M Y') }})', 'all');
        },

        closeSyncModal() {
            if (this.syncLoading) {
                if (!confirm('Ingestion sync is currently in progress. Closing this console will not stop background ingestion. Are you sure?')) {
                    return;
                }
            }
            if (this.syncTimerInterval) clearInterval(this.syncTimerInterval);
            this.syncModalOpen = false;
        },

        toggleCropDrawer(crop) {
            crop.drawerOpen = !crop.drawerOpen;
            crop.retrySuccessMessage = null;
            crop.retryErrorMessage = null;
        },

        async retryCropSync(crop) {
            if (!this.activeSyncSourceId) return;
            const cropKey = crop.raw_name || crop.crop_name;
            this.retryingCrops[cropKey] = true;
            crop.retrySuccessMessage = null;
            crop.retryErrorMessage = null;

            try {
                const retryUrl = `{{ url('admin/datasources') }}/${this.activeSyncSourceId}/retry-crop`;
                const res = await fetch(retryUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        commodity_name: crop.raw_name || crop.crop_name,
                        crop_id: crop.selectedCropId || null
                    })
                });

                const data = await res.json();
                this.retryingCrops[cropKey] = false;

                if (data.ok) {
                    const newlyProcessed = data.processed || 0;
                    crop.rejected = data.still_rejected;
                    crop.inserted = (crop.inserted || 0) + newlyProcessed;
                    crop.status = data.status;
                    crop.crop_id = data.crop_id;
                    crop.crop_name = data.crop_name || crop.crop_name;
                    crop.photo_url = data.photo_url || crop.photo_url;
                    crop.retrySuccessMessage = data.message;

                    // Update summary counts
                    if (this.syncResult) {
                        this.syncResult.rejected = Math.max(0, (this.syncResult.rejected || 0) - newlyProcessed);
                        this.syncResult.inserted = (this.syncResult.inserted || 0) + newlyProcessed;
                        if (this.syncResult.rejected === 0) {
                            this.syncResult.status = 'success';
                        }
                    }
                } else {
                    crop.retryErrorMessage = data.message || 'Reprocessing failed.';
                }
            } catch (err) {
                this.retryingCrops[cropKey] = false;
                crop.retryErrorMessage = 'Network error: ' + err.message;
            }
        },

        async retryAllFailed() {
            if (!this.activeSyncSourceId || this.retryingAll) return;
            this.retryingAll = true;

            try {
                const retryUrl = `{{ url('admin/datasources') }}/${this.activeSyncSourceId}/retry-all`;
                const res = await fetch(retryUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await res.json();
                this.retryingAll = false;

                if (data.ok) {
                    const processed = data.processed || 0;
                    if (this.syncResult) {
                        this.syncResult.rejected = data.still_rejected;
                        this.syncResult.inserted = (this.syncResult.inserted || 0) + processed;
                        if (data.still_rejected === 0) {
                            this.syncResult.status = 'success';
                        }
                    }
                    alert(data.message);
                } else {
                    alert('Batch retry failed: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                this.retryingAll = false;
                alert('Batch retry error: ' + err.message);
            }
        },

        getFilteredCrops() {
            if (!this.syncResult || !this.syncResult.crops) return [];
            let list = this.syncResult.crops;

            // Tab filter
            if (this.cropFilterTab === 'synced') {
                list = list.filter(c => c.status === 'synced');
            } else if (this.cropFilterTab === 'failed') {
                list = list.filter(c => c.status === 'failed' || c.status === 'partial' || c.rejected > 0);
            }

            // Text search filter
            if (this.cropSearchQuery.trim()) {
                const q = this.cropSearchQuery.toLowerCase().trim();
                list = list.filter(c => 
                    (c.crop_name && c.crop_name.toLowerCase().includes(q)) ||
                    (c.raw_name && c.raw_name.toLowerCase().includes(q))
                );
            }

            return list;
        },

        getTotalCropsCount() {
            return this.syncResult?.crops?.length || 0;
        },

        getSyncedCropsCount() {
            if (!this.syncResult?.crops) return 0;
            return this.syncResult.crops.filter(c => c.status === 'synced').length;
        },

        getFailedCropsCount() {
            if (!this.syncResult?.crops) return 0;
            return this.syncResult.crops.filter(c => c.status === 'failed' || c.status === 'partial' || c.rejected > 0).length;
        },

        hasRejectedCrops() {
            return (this.syncResult?.rejected ?? 0) > 0 || this.getFailedCropsCount() > 0;
        }
    };
}

window.toggleCronAjax = async function(sourceId, formElement) {
    const btn = formElement.querySelector('button');
    if (!btn) return;
    btn.disabled = true;
    btn.classList.add('opacity-50');

    try {
        const token = formElement.querySelector('input[name="_token"]')?.value;
        const res = await fetch(formElement.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await res.json();
        if (data.ok) {
            const isCron = data.is_cron_enabled;
            if (isCron) {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition cursor-pointer bg-emerald-950 text-emerald-300 border border-emerald-800/80 hover:bg-emerald-900 shadow-xs';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span><span>⚡ Auto Cron</span>';
                btn.title = 'Auto-runs on cron schedule. Click to exclude.';
            } else {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition cursor-pointer bg-amber-950/70 text-amber-300 border border-amber-800/70 hover:bg-amber-900 shadow-xs';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span><span>⏸️ Excluded</span>';
                btn.title = 'Excluded from cron schedule. Click to enroll.';
            }

            // Update scope pill count if present on page
            const pillCount = document.getElementById('cron-scope-pill-count');
            if (pillCount && data.cron_enabled_count !== undefined) {
                pillCount.innerText = `${data.cron_enabled_count} of {{ $stats['active'] }} Active Feeds`;
            }

            // Sync checkbox in drawer if present
            const drawerCb = document.querySelector(`.cron-source-cb[value="${sourceId}"]`);
            if (drawerCb) {
                drawerCb.checked = isCron;
            }
        } else {
            alert('Could not update cron enrollment: ' + (data.message || 'Server error'));
        }
    } catch (err) {
        alert('Network error while toggling cron: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.classList.remove('opacity-50');
    }
};
</script>
@endsection
