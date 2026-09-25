@extends('layouts.admin')

@php
    $isEdit = isset($datasource);
    $title = $isEdit ? "Edit '{$datasource->name}'" : 'Register New Data Source';
@endphp

@section('title', $title)

@section('content')
<div class="w-full space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.datasources.index') }}" class="text-xs font-bold text-emerald-400 hover:text-emerald-300 transition flex items-center gap-1 mb-1">
                <span>←</span> Back to Data Sources
            </a>
            <h1 class="text-2xl font-black text-white">{{ $title }}</h1>
            <p class="text-sm text-slate-400 font-medium">Configure provider endpoint, sync intervals, and encrypted credentials.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-950/60 border border-rose-800/80 rounded-2xl text-rose-300 text-sm font-semibold">
            <div class="font-bold mb-1">Please fix the following validation errors:</div>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $isEdit ? route('admin.datasources.update', $datasource) : route('admin.datasources.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <!-- General Configuration Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-black text-white uppercase tracking-wider pb-3 border-b border-slate-800 flex items-center gap-2">
                <span>⚙️</span> Provider Details
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-400 mb-1">Source Name <span class="text-rose-400">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $datasource->name ?? '') }}" required placeholder="e.g. data.gov.in Mandi Prices" class="w-full px-3.5 py-2.5 text-sm bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label for="code" class="block text-xs font-bold text-slate-400 mb-1">Unique Code Identifier <span class="text-rose-400">*</span></label>
                    <input type="text" id="code" name="code" value="{{ old('code', $datasource->code ?? '') }}" required placeholder="e.g. data_gov_mandi" class="w-full px-3.5 py-2.5 text-sm font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="provider_class" class="block text-xs font-bold text-slate-400 mb-1">Adapter Implementation Class <span class="text-rose-400">*</span></label>
                    <select id="provider_class" name="provider_class" required class="w-full px-3.5 py-2.5 text-xs bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @foreach($providers as $class => $label)
                            <option value="{{ $class }}" {{ old('provider_class', $datasource->provider_class ?? '') === $class ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="type" class="block text-xs font-bold text-slate-400 mb-1">Data Category <span class="text-rose-400">*</span></label>
                    <select id="type" name="type" required class="w-full px-3.5 py-2.5 text-sm bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="market_prices" {{ old('type', $datasource->type ?? '') === 'market_prices' ? 'selected' : '' }}>Market Prices & Mandi Arrivals</option>
                        <option value="weather" {{ old('type', $datasource->type ?? '') === 'weather' ? 'selected' : '' }}>Weather & Forecasts</option>
                        <option value="news" {{ old('type', $datasource->type ?? '') === 'news' ? 'selected' : '' }}>Agriculture News & Advisories</option>
                        <option value="videos" {{ old('type', $datasource->type ?? '') === 'videos' ? 'selected' : '' }}>YouTube Agriculture Videos</option>
                        <option value="schemes" {{ old('type', $datasource->type ?? '') === 'schemes' ? 'selected' : '' }}>Government Schemes</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="base_url" class="block text-xs font-bold text-slate-400 mb-1">Base URL <span class="text-rose-400">*</span></label>
                    <input type="url" id="base_url" name="base_url" value="{{ old('base_url', $datasource->base_url ?? '') }}" required placeholder="https://api.data.gov.in/resource" class="w-full px-3.5 py-2.5 text-sm font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label for="endpoint" class="block text-xs font-bold text-slate-400 mb-1">Endpoint Path</label>
                    <input type="text" id="endpoint" name="endpoint" value="{{ old('endpoint', $datasource->endpoint ?? '') }}" placeholder="resource-id-or-path" class="w-full px-3.5 py-2.5 text-sm font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <!-- Sync Scheduling & Timing Controls -->
            <div class="pt-3 border-t border-slate-800">
                <h3 class="text-xs font-black uppercase text-white tracking-wider mb-3 flex items-center gap-1.5">
                    <span>⏱️</span> Auto Price Sync Scheduling (Industry Standards)
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="sync_frequency" class="block text-xs font-bold text-slate-400 mb-1">Sync Frequency</label>
                        <select id="sync_frequency" name="sync_frequency" class="w-full px-3 py-2 text-xs bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:ring-1 focus:ring-emerald-500 outline-none">
                            <option value="twice_daily" {{ old('sync_frequency', $datasource->sync_frequency ?? 'twice_daily') === 'twice_daily' ? 'selected' : '' }}>⭐ Twice Daily (Morning & Evening)</option>
                            <option value="daily" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'daily' ? 'selected' : '' }}>Once Daily (Pick Time)</option>
                            <option value="every_2_hours" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'every_2_hours' ? 'selected' : '' }}>Every 2 Hours</option>
                            <option value="every_6_hours" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'every_6_hours' ? 'selected' : '' }}>Every 6 Hours</option>
                            <option value="every_12_hours" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'every_12_hours' ? 'selected' : '' }}>Every 12 Hours</option>
                            <option value="hourly" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'hourly' ? 'selected' : '' }}>Hourly</option>
                            <option value="weekly" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        </select>
                        <span class="text-[10px] text-slate-500 mt-1 block">Mandis report opening & closing rates.</span>
                    </div>

                    <div>
                        <label for="sync_time" class="block text-xs font-bold text-slate-400 mb-1">Sync Time(s) (IST)</label>
                        <input type="text" id="sync_time" name="sync_time" value="{{ old('sync_time', $datasource->sync_time ?? '06:00,18:00') }}" placeholder="06:00,18:00" class="w-full px-3 py-2 text-xs font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:ring-1 focus:ring-emerald-500 outline-none">
                        <span class="text-[10px] text-slate-500 mt-1 block">Comma-separated 24hr times (e.g. 06:00,18:00).</span>
                    </div>

                    <div>
                        <label for="sync_days" class="block text-xs font-bold text-slate-400 mb-1">Operating Days</label>
                        <select id="sync_days" name="sync_days" class="w-full px-3 py-2 text-xs bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:ring-1 focus:ring-emerald-500 outline-none">
                            <option value="mon_sat" {{ old('sync_days', $datasource->sync_days ?? 'mon_sat') === 'mon_sat' ? 'selected' : '' }}>Mon – Sat (Skip Sunday Mandi Holiday)</option>
                            <option value="all" {{ old('sync_days', $datasource->sync_days ?? '') === 'all' ? 'selected' : '' }}>All 7 Days (Every Day)</option>
                        </select>
                        <span class="text-[10px] text-slate-500 mt-1 block">Karnataka APMCs are closed on Sundays.</span>
                    </div>

                    <div>
                        <label for="cron_expression" class="block text-xs font-bold text-slate-400 mb-1">Custom Cron (Optional)</label>
                        <input type="text" id="cron_expression" name="cron_expression" value="{{ old('cron_expression', $datasource->cron_expression ?? '') }}" placeholder="0 6,18 * * 1-6" class="w-full px-3 py-2 text-xs font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:ring-1 focus:ring-emerald-500 outline-none">
                        <span class="text-[10px] text-slate-500 mt-1 block">Leave empty to use frequency settings above.</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-3 border-t border-slate-800">
                <div>
                    <label for="auth_type" class="block text-xs font-bold text-slate-400 mb-1">Authentication</label>
                    <select id="auth_type" name="auth_type" class="w-full px-3 py-2 text-xs bg-slate-950/80 border border-slate-800 text-white rounded-xl">
                        <option value="api_key" {{ old('auth_type', $datasource->auth_type ?? '') === 'api_key' ? 'selected' : '' }}>API Key (Param/Header)</option>
                        <option value="bearer_token" {{ old('auth_type', $datasource->auth_type ?? '') === 'bearer_token' ? 'selected' : '' }}>Bearer Token</option>
                        <option value="none" {{ old('auth_type', $datasource->auth_type ?? '') === 'none' ? 'selected' : '' }}>Public / None</option>
                    </select>
                </div>

                <div>
                    <label for="timeout_seconds" class="block text-xs font-bold text-slate-400 mb-1">Timeout (Sec)</label>
                    <input type="number" id="timeout_seconds" name="timeout_seconds" value="{{ old('timeout_seconds', $datasource->timeout_seconds ?? 30) }}" min="5" max="120" class="w-full px-3 py-2 text-xs bg-slate-950/80 border border-slate-800 text-white rounded-xl">
                </div>

                <div>
                    <label for="rate_limit_per_minute" class="block text-xs font-bold text-slate-400 mb-1">Rate Limit / Min</label>
                    <input type="number" id="rate_limit_per_minute" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute', $datasource->rate_limit_per_minute ?? 60) }}" placeholder="60" class="w-full px-3 py-2 text-xs bg-slate-950/80 border border-slate-800 text-white rounded-xl">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $datasource->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 bg-slate-950 border-slate-800 rounded-md">
                    <span class="text-xs font-bold text-slate-300">Enable automatic background scheduled ingestion for this provider</span>
                </label>
            </div>
        </div>

        <!-- Encrypted Credentials Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h2 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                    <span>🔒</span> Encrypted Credentials at Rest
                </h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-950/80 text-amber-300 border border-amber-800/60">
                    AES-256-CBC Encrypted
                </span>
            </div>

            <p class="text-xs text-slate-400 font-medium leading-relaxed">
                All credentials are encrypted server-side using Laravel's application key. Cleartext keys are never echoed back in full.
                @if($isEdit && $datasource->credential?->api_key)
                    <span class="text-emerald-400 font-bold">Encrypted key is currently stored. Leave field blank to keep unchanged.</span>
                @endif
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="api_key" class="block text-xs font-bold text-slate-400 mb-1">API Key / Token</label>
                    <input type="password" id="api_key" name="api_key" value="{{ $isEdit && $datasource->credential?->masked_api_key ? $datasource->credential->masked_api_key : '' }}" placeholder="Paste API Key" class="w-full px-3.5 py-2 text-xs font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    @if($isEdit && $datasource->credential?->masked_api_key)
                        <span class="text-[10px] text-slate-500 font-mono mt-1 block">Masked: {{ $datasource->credential->masked_api_key }}</span>
                    @endif
                </div>

                <div>
                    <label for="client_id" class="block text-xs font-bold text-slate-400 mb-1">Client ID (Optional)</label>
                    <input type="text" id="client_id" name="client_id" value="{{ old('client_id', $datasource->credential->client_id ?? '') }}" placeholder="Client ID" class="w-full px-3.5 py-2 text-xs font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label for="client_secret" class="block text-xs font-bold text-slate-400 mb-1">Client Secret (Optional)</label>
                    <input type="password" id="client_secret" name="client_secret" placeholder="Client Secret" class="w-full px-3.5 py-2 text-xs font-mono bg-slate-950/80 border border-slate-800 text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.datasources.index') }}" class="px-4 py-2 text-sm font-bold text-slate-400 hover:text-white transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm rounded-xl transition shadow-sm cursor-pointer">
                {{ $isEdit ? 'Update Configuration' : 'Register Source' }}
            </button>
        </div>
    </form>
</div>
@endsection
