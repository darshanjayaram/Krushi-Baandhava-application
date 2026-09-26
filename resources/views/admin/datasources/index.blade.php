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
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" 
                    @click="runSyncAll('{{ route('admin.datasources.sync-all') }}')" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-cyan-300 bg-cyan-950/80 border border-cyan-800 rounded-xl hover:bg-cyan-900 transition shadow-sm cursor-pointer"
                    :class="syncLoading && activeSyncSourceId === 'all' ? 'ring-2 ring-cyan-500' : ''"
                    title="Run batch ingestion sync for all active data sources for today">
                <span class="text-sm" :class="syncLoading && activeSyncSourceId === 'all' ? 'animate-spin inline-block' : ''">🔄</span>
                <span>Sync All Sources (Today: {{ now()->format('d M') }})</span>
            </button>
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
                    🌇 Evening: {{ $cronInfo['evening_time'] ?: '18:00' }}
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
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-sm font-black text-white flex items-center gap-2">
                            <span>⚙️</span> Edit Automated Background Sync Schedule
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Update morning/evening sync times. Laravel's scheduler will trigger external feeds automatically according to these hours.
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
                            <span>Save Cron Timings</span>
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

                                    <!-- Interactive Ingestion Sync Trigger -->
                                    <button type="button" 
                                            @click="runSync('{{ route('admin.datasources.trigger-sync', $source) }}', '{{ addslashes($source->name) }}', {{ $source->id }})" 
                                            class="p-1.5 text-slate-400 hover:text-cyan-400 hover:bg-slate-800 rounded-xl transition cursor-pointer" 
                                            :class="syncLoading && activeSyncSourceId === {{ $source->id }} ? 'text-cyan-400 bg-slate-800 ring-1 ring-cyan-500/50' : ''"
                                            title="Run Ingestion Sync">
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
            <div class="overflow-y-auto p-5 sm:p-6 space-y-5 flex-1">
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
</div>

<script>
function dataSourceManager() {
    return {
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
</script>
@endsection
