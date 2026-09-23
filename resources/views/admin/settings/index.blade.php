@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ currentTab: '{{ $activeTab }}' }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">System Configuration Settings</h1>
            <p class="text-sm text-slate-400 mt-0.5">Manage platform operational defaults, thresholds, localization, and forecasting parameters</p>
        </div>
        <div>
            <span class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-emerald-400 font-mono">
                Cached with Auto-Invalidation (300s TTL)
            </span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800">
        @php
            $tabMeta = [
                'general' => ['name' => 'General & Platform', 'icon' => '⚙️'],
                'localization' => ['name' => 'State & Localization', 'icon' => '📍'],
                'forecasting' => ['name' => 'Price Forecasting', 'icon' => '📈'],
                'analytics' => ['name' => 'Historical Analytics', 'icon' => '📊'],
                'data_sources' => ['name' => 'Data Sync Feeds', 'icon' => '🔄'],
                'weather' => ['name' => 'Weather Services', 'icon' => '🌤️'],
                'performance' => ['name' => 'Performance & Cache', 'icon' => '⚡'],
            ];
        @endphp

        @foreach($groupedSettings as $groupName => $settings)
            @php
                $meta = $tabMeta[$groupName] ?? ['name' => ucfirst($groupName), 'icon' => '📁'];
            @endphp
            <button type="button" @click="currentTab = '{{ $groupName }}'"
                    :class="currentTab === '{{ $groupName }}' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 font-medium'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 shrink-0 border border-slate-800 transition">
                <span>{{ $meta['icon'] }}</span>
                <span>{{ $meta['name'] }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-950/60 font-mono">{{ $settings->count() }}</span>
            </button>
        @endforeach
    </div>

    <!-- Settings Forms per Group -->
    @foreach($groupedSettings as $groupName => $settings)
        <div x-show="currentTab === '{{ $groupName }}'" style="display: none;" class="space-y-6">
            <form action="{{ route('admin.settings.update') }}" method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                @csrf
                <input type="hidden" name="tab" value="{{ $groupName }}">

                <div class="border-b border-slate-800 pb-4">
                    <h2 class="text-base font-bold text-white capitalize">{{ $tabMeta[$groupName]['name'] ?? $groupName }} Settings</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Configuration keys under the <code class="font-mono text-emerald-400">{{ $groupName }}</code> namespace</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($settings as $setting)
                        <div class="space-y-1.5 p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider">
                                    {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                                </label>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-900 text-slate-400 border border-slate-800">
                                    {{ $setting->type }}
                                </span>
                            </div>

                            <p class="text-[11px] text-slate-400 leading-relaxed">{{ $setting->description }}</p>

                            <div class="pt-2">
                                @if($setting->type === 'boolean')
                                    <div class="flex items-center gap-3">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" 
                                                   {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:width-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                        </label>
                                        <span class="text-xs text-slate-300 font-medium">
                                            {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'Active / Enabled' : 'Disabled' }}
                                        </span>
                                    </div>

                                @elseif($setting->key === 'default_district')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                                        @foreach($districts as $d)
                                            <option value="{{ $d->name }}" {{ $setting->value === $d->name ? 'selected' : '' }}>
                                                {{ $d->name }} ({{ $d->name_kn }})
                                            </option>
                                        @endforeach
                                    </select>

                                @elseif($setting->key === 'default_language')
                                    <select name="settings[{{ $setting->key }}]" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                                        <option value="kn" {{ $setting->value === 'kn' ? 'selected' : '' }}>ಕನ್ನಡ (Kannada - Default)</option>
                                        <option value="en" {{ $setting->value === 'en' ? 'selected' : '' }}>English</option>
                                    </select>

                                @elseif($setting->type === 'integer')
                                    <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                           class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-emerald-500">

                                @elseif($setting->type === 'json')
                                    <textarea name="settings[{{ $setting->key }}]" rows="2"
                                              class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-emerald-400 font-mono focus:outline-none focus:border-emerald-500">{{ $setting->value }}</textarea>

                                @else
                                    <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}"
                                           class="w-full px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save {{ $tabMeta[$groupName]['name'] ?? $groupName }} Settings
                    </button>
                </div>
            </form>
        </div>
    @endforeach

</div>
@endsection
