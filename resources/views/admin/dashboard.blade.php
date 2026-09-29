@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{ showMandiDrawer: false, mandiTab: 'all', mandiSearch: '', showCronDrawer: false, copiedCron: false, copiedCronAlt: false }">

    <!-- ========================================== -->
    <!-- cPanel Cron Job Status & Scheduler Monitor -->
    <!-- ========================================== -->
    <div class="rounded-2xl border {{ $cronStatus['is_active'] ? 'border-emerald-600/50 bg-slate-900/95 shadow-lg shadow-emerald-950/20' : 'border-amber-600/60 bg-slate-900/95 shadow-lg shadow-amber-950/20' }} p-4 sm:p-5 transition-all">
        <!-- Main Bar: Status Indicator, Last Heartbeat & Quick Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5">
                <!-- Status Icon with Pulse Ring -->
                <div class="relative flex items-center justify-center w-11 h-11 rounded-2xl shrink-0 {{ $cronStatus['is_active'] ? 'bg-emerald-950/80 border border-emerald-700/60 text-emerald-400' : 'bg-amber-950/80 border border-amber-700/60 text-amber-400' }}">
                    @if($cronStatus['is_active'])
                        <span class="absolute inline-flex h-full w-full rounded-2xl bg-emerald-400 opacity-20 animate-ping"></span>
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <span class="absolute inline-flex h-full w-full rounded-2xl bg-amber-400 opacity-20 animate-ping"></span>
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>

                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="text-base font-black text-white tracking-wide">
                            cPanel Cron Job & Automated Scheduler
                        </h2>
                        @if($cronStatus['is_active'])
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-950 text-emerald-300 border border-emerald-700 shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>CRON RUNNING & ACTIVE</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-950 text-amber-300 border border-amber-700 shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span>CRON NOT RUNNING / SETUP REQUIRED</span>
                            </span>
                        @endif
                    </div>

                    <div class="mt-1 flex items-center gap-2 text-xs text-slate-400 flex-wrap">
                        <span class="flex items-center gap-1">
                            <span class="font-semibold {{ $cronStatus['is_active'] ? 'text-emerald-400' : 'text-amber-400' }}">
                                Last Heartbeat:
                            </span>
                            <span class="text-white font-medium">{{ $cronStatus['last_heartbeat_human'] }}</span>
                            @if($cronStatus['last_heartbeat'])
                                <span class="text-slate-500">({{ $cronStatus['last_heartbeat_formatted'] }})</span>
                            @endif
                        </span>
                        <span class="text-slate-600 hidden sm:inline">•</span>
                        <span class="text-slate-300 hidden sm:inline">
                            Runs every minute in cPanel & triggers Market Ingestion, Weather, AI Forecasts & Retention
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 flex-wrap shrink-0">
                <!-- Toggle Setup & Details Drawer -->
                <button type="button" 
                        @click="showCronDrawer = !showCronDrawer" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition border cursor-pointer {{ $cronStatus['is_active'] ? 'bg-slate-800 hover:bg-slate-700 text-slate-200 border-slate-700' : 'bg-amber-600 hover:bg-amber-500 text-white border-amber-500 shadow-sm' }}">
                    <span>⚙️</span>
                    <span x-text="showCronDrawer ? 'Hide Cron Details ▲' : 'View cPanel Setup & Tasks ▼'"></span>
                </button>

                <!-- One-click Copy cPanel Command -->
                <button type="button" 
                        @click="navigator.clipboard.writeText('{{ addslashes($cronStatus['cpanel_command']) }}'); copiedCron = true; setTimeout(() => copiedCron = false, 2500)" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition border cursor-pointer"
                        :class="copiedCron ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 hover:bg-slate-700 text-white border-slate-700'">
                    <span x-show="!copiedCron">📋 Copy cPanel Command</span>
                    <span x-show="copiedCron" x-cloak>✓ Command Copied!</span>
                </button>

                <!-- Manual Scheduler Test Tick -->
                <form action="{{ route('admin.scheduler.test') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            title="Manually trigger a scheduler tick to test and verify cron heartbeat immediately" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800/90 hover:bg-slate-700 text-cyan-300 hover:text-cyan-200 border border-slate-700 transition cursor-pointer">
                        <span>⚡</span>
                        <span>Test Scheduler Tick</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Collapsible Expandable cPanel Setup & Scheduled Tasks Drawer -->
        <div x-show="showCronDrawer" x-cloak x-transition class="mt-4 pt-4 border-t border-slate-800 space-y-4">
            
            <!-- Quick Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Heartbeat Status</span>
                    <div class="mt-1 flex items-center gap-2">
                        @if($cronStatus['is_active'])
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-bold text-emerald-400">Active (Running)</span>
                        @else
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span class="font-bold text-rose-400">Not Running</span>
                        @endif
                    </div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">{{ $cronStatus['last_heartbeat_human'] }}</span>
                </div>

                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Configured Price Sync</span>
                    <div class="mt-1 font-mono font-bold text-white flex items-center gap-1.5 flex-wrap">
                        <span>🌅 {{ $cronStatus['morning_time'] ?: '06:00' }}</span>
                        @if(!empty($cronStatus['afternoon_time']))
                            <span>•</span>
                            <span>☀️ {{ $cronStatus['afternoon_time'] }}</span>
                        @endif
                        <span>•</span>
                        <span>🌇 {{ $cronStatus['evening_time'] ?: '19:30' }}</span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">
                        {{ $cronStatus['enable_hourly'] ? 'Hourly sync active during trading' : 'Hourly sync disabled' }}
                    </span>
                </div>

                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Operating Days</span>
                    <span class="mt-1 font-bold text-white block">
                        {{ $cronStatus['operating_days'] === 'mon_sat' ? '🗓️ Mon – Sat (APMC Trading Days)' : '🗓️ All 7 Days' }}
                    </span>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Skips Sundays when APMCs are shut</span>
                </div>

                <div class="bg-slate-950/80 rounded-xl p-3 border border-slate-800">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Server Base Path</span>
                    <span class="mt-1 font-mono text-[11px] text-emerald-400 truncate block" title="{{ $cronStatus['base_path'] }}">
                        {{ $cronStatus['base_path'] }}
                    </span>
                    <span class="text-[10px] text-slate-500 mt-0.5 block">PHP: {{ basename($cronStatus['php_binary']) }}</span>
                </div>
            </div>

            <!-- Ready-to-use cPanel Cron Commands -->
            <div class="space-y-3 bg-slate-950/90 border border-slate-800 rounded-xl p-3 sm:p-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-bold text-white flex items-center gap-1.5">
                            <span class="text-emerald-400">📌</span> Primary cPanel Cron Command (Recommended)
                        </label>
                        <span class="text-[11px] text-slate-400 font-mono">Runs every minute (* * * * *)</span>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-lg p-2">
                        <code class="flex-1 font-mono text-xs text-amber-300 select-all overflow-x-auto whitespace-nowrap px-1">
                            {{ $cronStatus['cpanel_command'] }}
                        </code>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ addslashes($cronStatus['cpanel_command']) }}'); copiedCron = true; setTimeout(() => copiedCron = false, 2500)"
                                class="shrink-0 px-2.5 py-1 text-xs font-bold rounded-lg transition border cursor-pointer"
                                :class="copiedCron ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border-slate-700'">
                            <span x-show="!copiedCron">📋 Copy</span>
                            <span x-show="copiedCron" x-cloak>✓ Copied</span>
                        </button>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[11px] font-bold text-slate-400 flex items-center gap-1.5">
                            <span>Alternative:</span> Direct PHP Binary Command (If `php` is not in standard PATH)
                        </label>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-900/60 border border-slate-800 rounded-lg p-2">
                        <code class="flex-1 font-mono text-xs text-slate-300 select-all overflow-x-auto whitespace-nowrap px-1">
                            {{ $cronStatus['cpanel_binary_command'] }}
                        </code>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ addslashes($cronStatus['cpanel_binary_command']) }}'); copiedCronAlt = true; setTimeout(() => copiedCronAlt = false, 2500)"
                                class="shrink-0 px-2.5 py-1 text-xs font-bold rounded-lg transition border cursor-pointer"
                                :class="copiedCronAlt ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 hover:bg-slate-700 text-slate-300 border-slate-700'">
                            <span x-show="!copiedCronAlt">📋 Copy</span>
                            <span x-show="copiedCronAlt" x-cloak>✓ Copied</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4-Step cPanel Configuration Walkthrough -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3 sm:p-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <span>📖</span> How to Add this in cPanel (Takes only 30 seconds):
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs text-slate-300">
                    <div class="bg-slate-900/80 border border-slate-800 rounded-lg p-2.5">
                        <div class="font-bold text-emerald-400 mb-0.5">Step 1: Open Cron Jobs</div>
                        <p class="text-slate-400 text-[11px]">Log in to your cPanel hosting. In the search bar, type <strong class="text-white">"Cron Jobs"</strong> (under Advanced section).</p>
                    </div>
                    <div class="bg-slate-900/80 border border-slate-800 rounded-lg p-2.5">
                        <div class="font-bold text-emerald-400 mb-0.5">Step 2: Select Frequency</div>
                        <p class="text-slate-400 text-[11px]">Under <strong class="text-white">Common Settings</strong>, select dropdown option: <strong class="text-amber-300">"Once Per Minute (* * * * *)"</strong>.</p>
                    </div>
                    <div class="bg-slate-900/80 border border-slate-800 rounded-lg p-2.5">
                        <div class="font-bold text-emerald-400 mb-0.5">Step 3: Paste Command</div>
                        <p class="text-slate-400 text-[11px]">Paste the copyable command from above into the <strong class="text-white">Command</strong> text input box.</p>
                    </div>
                    <div class="bg-slate-900/80 border border-slate-800 rounded-lg p-2.5">
                        <div class="font-bold text-emerald-400 mb-0.5">Step 4: Save & Verify</div>
                        <p class="text-slate-400 text-[11px]">Click <strong class="text-emerald-400">Add New Cron Job</strong>. Return here and refresh: the badge will turn green!</p>
                    </div>
                </div>
            </div>

            <!-- Scheduled Background Tasks Breakdown Table -->
            <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-3 sm:p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                        <span>🤖</span> Tasks Handled Automatically by this Cron:
                    </h3>
                    <a href="{{ route('admin.datasources.index') }}" class="text-xs font-bold text-emerald-400 hover:underline">
                        Change Ingestion Schedule &rarr;
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-semibold">
                                <th class="py-1.5 px-2">Scheduled Task</th>
                                <th class="py-1.5 px-2">Frequency / Timing</th>
                                <th class="py-1.5 px-2">Purpose</th>
                                <th class="py-1.5 px-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr>
                                <td class="py-2 px-2 font-medium text-white flex items-center gap-1.5">
                                    <span>🌾</span> <span>Mandi Market Prices Ingestion</span>
                                </td>
                                <td class="py-2 px-2 font-mono text-amber-300">
                                    {{ $cronStatus['morning_time'] ?: '06:00' }}@if(!empty($cronStatus['afternoon_time'])), {{ $cronStatus['afternoon_time'] }}@endif & {{ $cronStatus['evening_time'] ?: '19:30' }} IST
                                </td>
                                <td class="py-2 px-2 text-slate-400">Syncs KRAMA Karnataka Mandis, Official Agmarknet, Coffee Board & Coconut Board</td>
                                <td class="py-2 px-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Auto</span></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-2 font-medium text-white flex items-center gap-1.5">
                                    <span>🌦️</span> <span>Hyperlocal Weather Advisories</span>
                                </td>
                                <td class="py-2 px-2 font-mono text-cyan-300">05:30 & 14:30 IST Daily</td>
                                <td class="py-2 px-2 text-slate-400">Updates 7-day agricultural forecasts via Open-Meteo</td>
                                <td class="py-2 px-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Auto</span></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-2 font-medium text-white flex items-center gap-1.5">
                                    <span>📊</span> <span>Historical Analytics & Seasonality</span>
                                </td>
                                <td class="py-2 px-2 font-mono text-purple-300">01:00 IST Nightly</td>
                                <td class="py-2 px-2 text-slate-400">Computes 12-month seasonal indices & modal averages</td>
                                <td class="py-2 px-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Auto</span></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-2 font-medium text-white flex items-center gap-1.5">
                                    <span>🔮</span> <span>Price Forecasting Engine</span>
                                </td>
                                <td class="py-2 px-2 font-mono text-indigo-300">02:00 IST Nightly</td>
                                <td class="py-2 px-2 text-slate-400">Generates 1D, 7D, 15D, 30D Holt's Linear projections</td>
                                <td class="py-2 px-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Auto</span></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-2 font-medium text-white flex items-center gap-1.5">
                                    <span>🧹</span> <span>1-Year Rolling Retention Pruner</span>
                                </td>
                                <td class="py-2 px-2 font-mono text-slate-400">23:00 IST Nightly</td>
                                <td class="py-2 px-2 text-slate-400">Prunes records >365 days; keeps database fast (~35MB)</td>
                                <td class="py-2 px-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">Auto</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Mandi Reporting Coverage (Dual-Horizon: 7-Day Trading vs Today's Live) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">APMC Mandi Coverage</span>
                    <span class="p-2 rounded-xl bg-cyan-950/70 border border-cyan-800/40 text-cyan-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                </div>
                
                <!-- Primary Dual Horizon Stats -->
                <div class="mt-3 flex items-baseline justify-between gap-2">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-white">{{ $stats['weekly_coverage_percent'] }}%</span>
                        <span class="text-xs text-slate-400 font-medium">({{ $stats['weekly_reporting_markets_count'] }}/{{ $stats['markets_count'] }})</span>
                    </div>
                    @if($stats['weekly_coverage_percent'] >= 80)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">7D Active</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">7D Active</span>
                    @endif
                </div>

                <div class="mt-2 text-xs flex items-center justify-between text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        <span class="font-medium text-cyan-300">Today: {{ $stats['market_coverage_percent'] }}%</span>
                    </span>
                    <span class="text-[11px] text-slate-400">({{ $stats['reporting_markets_count'] }} Live Mandis)</span>
                </div>

                <!-- Dual Progress Bars -->
                <div class="mt-3 space-y-1.5">
                    <div>
                        <div class="flex justify-between text-[10px] text-slate-400 mb-0.5">
                            <span>7-Day Active Network</span>
                            <span class="text-emerald-400 font-semibold">{{ $stats['weekly_coverage_percent'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-1.5 rounded-full" style="width: {{ min(100, $stats['weekly_coverage_percent']) }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[10px] text-slate-400 mb-0.5">
                            <span>Today's Live Ingestion</span>
                            <span class="text-cyan-400 font-semibold">{{ $stats['market_coverage_percent'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-1.5 rounded-full" style="width: {{ min(100, $stats['market_coverage_percent']) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inspect Drawer Trigger -->
            <button @click="showMandiDrawer = true" type="button" class="mt-4 w-full py-1.5 px-3 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 text-xs font-semibold text-cyan-300 hover:text-cyan-200 border border-cyan-500/20 hover:border-cyan-500/40 flex items-center justify-center gap-1.5 transition group">
                <svg class="w-3.5 h-3.5 text-cyan-400 group-hover:scale-110 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>Inspect Mandi Network</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded bg-cyan-950/80 text-cyan-400 border border-cyan-800/40">{{ $stats['markets_count'] }}</span>
            </button>
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

    <!-- Slide-over Drawer: Karnataka APMC Mandi Network Surveillance -->
    <div x-show="showMandiDrawer" 
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden" 
         aria-labelledby="slide-over-title" 
         role="dialog" 
         aria-modal="true"
         style="display: none;">
        
        <!-- Backdrop Blur -->
        <div x-show="showMandiDrawer"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showMandiDrawer = false"
             class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="showMandiDrawer"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-xl bg-slate-900 border-l border-slate-800 shadow-2xl flex flex-col">
                
                <!-- Drawer Header -->
                <div class="p-6 border-b border-slate-800 bg-slate-950/60 flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-cyan-950 text-cyan-400 border border-cyan-800/40">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                            <h2 class="text-base font-bold text-white tracking-tight" id="slide-over-title">Karnataka APMC Mandi Network</h2>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Real-time arrival surveillance & 7-day rolling trading telemetry</p>
                    </div>
                    <button @click="showMandiDrawer = false" type="button" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Live Metrics Summary Bar -->
                <div class="grid grid-cols-3 gap-2 p-4 bg-slate-950/30 border-b border-slate-800/60 text-center">
                    <div class="p-2.5 rounded-xl bg-slate-800/50 border border-slate-700/50">
                        <div class="text-[11px] text-slate-400 uppercase font-semibold">Total Mandis</div>
                        <div class="text-xl font-bold text-white mt-0.5">{{ $mandiNetworkStats['total'] }}</div>
                        <div class="text-[10px] text-slate-500">Karnataka APMCs</div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-950/40 border border-emerald-800/40">
                        <div class="text-[11px] text-emerald-400 uppercase font-semibold">7D Active</div>
                        <div class="text-xl font-bold text-emerald-300 mt-0.5">{{ $mandiNetworkStats['week_count'] }} <span class="text-xs font-normal text-emerald-400">({{ $mandiNetworkStats['week_percent'] }}%)</span></div>
                        <div class="text-[10px] text-emerald-500/80">Trading network</div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-cyan-950/40 border border-cyan-800/40">
                        <div class="text-[11px] text-cyan-400 uppercase font-semibold">Today's Live</div>
                        <div class="text-xl font-bold text-cyan-300 mt-0.5">{{ $mandiNetworkStats['today_count'] }} <span class="text-xs font-normal text-cyan-400">({{ $mandiNetworkStats['today_percent'] }}%)</span></div>
                        <div class="text-[10px] text-cyan-500/80">Ingested today</div>
                    </div>
                </div>

                <!-- Filters & Search Toolbar -->
                <div class="p-4 border-b border-slate-800/80 space-y-3 bg-slate-900">
                    <!-- Search Input -->
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input x-model="mandiSearch" 
                               type="text" 
                               placeholder="Search mandi name, Kannada name, district..." 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 transition">
                    </div>

                    <!-- Filter Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                        <button @click="mandiTab = 'all'" 
                                :class="mandiTab === 'all' ? 'bg-cyan-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                            All ({{ $mandiNetworkStats['total'] }})
                        </button>
                        <button @click="mandiTab = 'active_today'" 
                                :class="mandiTab === 'active_today' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Active Today ({{ $mandiNetworkStats['today_count'] }})
                        </button>
                        <button @click="mandiTab = 'active_week'" 
                                :class="mandiTab === 'active_week' ? 'bg-blue-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                            Traded This Week ({{ max(0, $mandiNetworkStats['week_count'] - $mandiNetworkStats['today_count']) }})
                        </button>
                        @if($mandiNetworkStats['dormant_count'] > 0)
                        <button @click="mandiTab = 'dormant'" 
                                :class="mandiTab === 'dormant' ? 'bg-rose-500 text-white font-bold' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            Dormant ({{ $mandiNetworkStats['dormant_count'] }})
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Mandi List Scroll Area -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2.5">
                    @foreach($mandiNetworkStats['markets'] as $m)
                        <div x-show="(mandiTab === 'all' || '{{ $m['status'] }}' === mandiTab) && ('{{ strtolower(addslashes($m['name'])) }}'.includes(mandiSearch.toLowerCase()) || '{{ strtolower(addslashes($m['name_kn'] ?? '')) }}'.includes(mandiSearch.toLowerCase()) || '{{ strtolower(addslashes($m['district'])) }}'.includes(mandiSearch.toLowerCase()))"
                             class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition flex flex-col gap-2">
                            
                            <!-- Market Title & Badges -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-bold text-white text-sm truncate flex items-center gap-1.5">
                                        <span>{{ $m['name'] }}</span>
                                        @if($m['name_kn'])
                                            <span class="text-xs font-normal text-slate-400 font-serif">({{ $m['name_kn'] }})</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 text-[10px] font-medium">{{ $m['district'] }}</span>
                                        <span class="text-slate-600">•</span>
                                        <span class="text-[10px] text-slate-500 font-mono">{{ $m['code'] }}</span>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div>
                                    @if($m['status'] === 'active_today')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-950 text-emerald-300 border border-emerald-700/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            Live Today
                                        </span>
                                    @elseif($m['status'] === 'active_week')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-950 text-blue-300 border border-blue-700/60">
                                            <span>Traded {{ $m['days_ago'] == 1 ? 'Yesterday' : ($m['days_ago'] . 'd ago') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-950/60 text-rose-300 border border-rose-800/40">
                                            Dormant
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Footer / Stats & Quick Link -->
                            <div class="pt-2 border-t border-slate-900/80 flex items-center justify-between text-[11px] text-slate-400">
                                <div>
                                    <span class="text-slate-300 font-medium">{{ $m['crops_count'] }}</span> commodities quoted
                                    <span class="text-slate-600 mx-1">•</span>
                                    <span class="text-slate-500">{{ $m['quotes_count'] }} total records</span>
                                </div>
                                <a href="{{ route('admin.unresolved-mappings.index') }}" class="text-[11px] text-cyan-400 hover:text-cyan-300 font-medium flex items-center gap-1">
                                    Aliases &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between text-xs text-slate-400">
                    <span>Showing Karnataka APMC mandi network</span>
                    <button @click="showMandiDrawer = false" type="button" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-medium transition">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
