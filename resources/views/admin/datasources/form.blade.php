@extends('layouts.admin')

@php
    $isEdit = isset($datasource);
    $title = $isEdit ? "Edit '{$datasource->name}'" : 'Register New Data Source';
@endphp

@section('title', $title)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.datasources.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 transition flex items-center gap-1 mb-1">
                <span>←</span> Back to Data Sources
            </a>
            <h1 class="text-2xl font-black text-emerald-950">{{ $title }}</h1>
            <p class="text-sm text-stone-500 font-medium">Configure provider endpoint, sync intervals, and encrypted credentials.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-sm font-semibold">
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
        <div class="bg-white border border-stone-200/80 rounded-2xl p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-black text-stone-900 uppercase tracking-wider pb-3 border-b border-stone-100 flex items-center gap-2">
                <span>⚙️</span> Provider Details
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-bold text-stone-700 mb-1">Source Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $datasource->name ?? '') }}" required placeholder="e.g. data.gov.in Mandi Prices" class="w-full px-3.5 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                </div>

                <div>
                    <label for="code" class="block text-xs font-bold text-stone-700 mb-1">Unique Code Identifier <span class="text-rose-500">*</span></label>
                    <input type="text" id="code" name="code" value="{{ old('code', $datasource->code ?? '') }}" required placeholder="e.g. data_gov_mandi" class="w-full px-3.5 py-2 text-sm font-mono border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="provider_class" class="block text-xs font-bold text-stone-700 mb-1">Adapter Implementation Class <span class="text-rose-500">*</span></label>
                    <select id="provider_class" name="provider_class" required class="w-full px-3.5 py-2 text-xs border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                        @foreach($providers as $class => $label)
                            <option value="{{ $class }}" {{ old('provider_class', $datasource->provider_class ?? '') === $class ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="type" class="block text-xs font-bold text-stone-700 mb-1">Data Category <span class="text-rose-500">*</span></label>
                    <select id="type" name="type" required class="w-full px-3.5 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
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
                    <label for="base_url" class="block text-xs font-bold text-stone-700 mb-1">Base URL <span class="text-rose-500">*</span></label>
                    <input type="url" id="base_url" name="base_url" value="{{ old('base_url', $datasource->base_url ?? '') }}" required placeholder="https://api.data.gov.in/resource" class="w-full px-3.5 py-2 text-sm font-mono border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                </div>

                <div>
                    <label for="endpoint" class="block text-xs font-bold text-stone-700 mb-1">Endpoint Path</label>
                    <input type="text" id="endpoint" name="endpoint" value="{{ old('endpoint', $datasource->endpoint ?? '') }}" placeholder="resource-id-or-path" class="w-full px-3.5 py-2 text-sm font-mono border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                <div>
                    <label for="auth_type" class="block text-xs font-bold text-stone-700 mb-1">Authentication</label>
                    <select id="auth_type" name="auth_type" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl">
                        <option value="api_key" {{ old('auth_type', $datasource->auth_type ?? '') === 'api_key' ? 'selected' : '' }}>API Key (Param/Header)</option>
                        <option value="bearer_token" {{ old('auth_type', $datasource->auth_type ?? '') === 'bearer_token' ? 'selected' : '' }}>Bearer Token</option>
                        <option value="none" {{ old('auth_type', $datasource->auth_type ?? '') === 'none' ? 'selected' : '' }}>Public / None</option>
                    </select>
                </div>

                <div>
                    <label for="sync_frequency" class="block text-xs font-bold text-stone-700 mb-1">Sync Frequency</label>
                    <select id="sync_frequency" name="sync_frequency" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl">
                        <option value="hourly" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'hourly' ? 'selected' : '' }}>Hourly</option>
                        <option value="twice_daily" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'twice_daily' ? 'selected' : '' }}>Twice Daily</option>
                        <option value="daily" {{ old('sync_frequency', $datasource->sync_frequency ?? 'daily') === 'daily' ? 'selected' : '' }}>Daily (Recommended)</option>
                        <option value="weekly" {{ old('sync_frequency', $datasource->sync_frequency ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    </select>
                </div>

                <div>
                    <label for="timeout_seconds" class="block text-xs font-bold text-stone-700 mb-1">Timeout (Sec)</label>
                    <input type="number" id="timeout_seconds" name="timeout_seconds" value="{{ old('timeout_seconds', $datasource->timeout_seconds ?? 30) }}" min="5" max="120" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl">
                </div>

                <div>
                    <label for="rate_limit_per_minute" class="block text-xs font-bold text-stone-700 mb-1">Rate Limit / Min</label>
                    <input type="number" id="rate_limit_per_minute" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute', $datasource->rate_limit_per_minute ?? 60) }}" placeholder="60" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl">
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $datasource->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 rounded-md border-stone-300">
                    <span class="text-xs font-bold text-stone-700">Enable automatic background scheduled ingestion</span>
                </label>
            </div>
        </div>

        <!-- Encrypted Credentials Card -->
        <div class="bg-white border border-stone-200/80 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                <h2 class="text-sm font-black text-stone-900 uppercase tracking-wider flex items-center gap-2">
                    <span>🔒</span> Encrypted Credentials at Rest
                </h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    AES-256-CBC Encrypted
                </span>
            </div>

            <p class="text-xs text-stone-500 font-medium leading-relaxed">
                All credentials are encrypted server-side using Laravel's application key. Cleartext keys are never echoed back in full.
                @if($isEdit && $datasource->credential?->api_key)
                    <span class="text-emerald-700 font-bold">Encrypted key is currently stored. Leave field blank to keep unchanged.</span>
                @endif
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="api_key" class="block text-xs font-bold text-stone-700 mb-1">API Key / Token</label>
                    <input type="password" id="api_key" name="api_key" value="{{ $isEdit && $datasource->credential?->masked_api_key ? $datasource->credential->masked_api_key : '' }}" placeholder="Paste API Key" class="w-full px-3.5 py-2 text-xs font-mono border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                    @if($isEdit && $datasource->credential?->masked_api_key)
                        <span class="text-[10px] text-stone-400 font-mono mt-1 block">Masked: {{ $datasource->credential->masked_api_key }}</span>
                    @endif
                </div>

                <div>
                    <label for="client_id" class="block text-xs font-bold text-stone-700 mb-1">Client ID (Optional)</label>
                    <input type="text" id="client_id" name="client_id" value="{{ old('client_id', $datasource->credential->client_id ?? '') }}" placeholder="Client ID" class="w-full px-3.5 py-2 text-xs font-mono border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                </div>

                <div>
                    <label for="client_secret" class="block text-xs font-bold text-stone-700 mb-1">Client Secret (Optional)</label>
                    <input type="password" id="client_secret" name="client_secret" placeholder="Client Secret" class="w-full px-3.5 py-2 text-xs font-mono border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.datasources.index') }}" class="px-4 py-2 text-sm font-bold text-stone-600 hover:text-stone-900 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm rounded-xl transition shadow-xs">
                {{ $isEdit ? 'Update Configuration' : 'Register Source' }}
            </button>
        </div>
    </form>
</div>
@endsection
