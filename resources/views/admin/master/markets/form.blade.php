@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">

    <!-- Header & Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.markets.index') }}" class="hover:text-emerald-400">APMC Mandis</a>
                <span>/</span>
                <span class="text-white">{{ $isEdit ? 'Edit ' . $market->name : 'New Mandi' }}</span>
            </div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">{{ $isEdit ? 'Edit Mandi' : 'Register New APMC Mandi' }}</h2>
        </div>

        <a href="{{ route('admin.markets.index') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
            Back to Mandis
        </a>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-300 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Quick-Fill Preset Card -->
    @if(!empty($presets))
    <div class="bg-gradient-to-r from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-900/40 rounded-2xl p-5 shadow-sm space-y-3">
        <div class="flex items-center gap-2">
            <span class="text-emerald-400 text-base">⚡</span>
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Quick-Fill from Standard Karnataka Mandi Directory</h3>
            <span class="px-2 py-0.5 rounded-full bg-emerald-900/50 text-[10px] font-semibold text-emerald-300 border border-emerald-700/50">45+ Presets Available</span>
        </div>
        <p class="text-[11px] text-slate-400">Select a known Karnataka APMC yard or commodity board centre to automatically pre-fill names, district, coordinates, and code.</p>
        <div>
            <select id="preset_selector" class="w-full sm:max-w-lg px-3.5 py-2.5 bg-slate-950 border border-emerald-700/60 rounded-xl text-emerald-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option value="">-- Choose Standard APMC / Board to Auto-Fill Form --</option>
                @foreach($presets as $idx => $preset)
                    <option value="{{ $idx }}" data-json="{{ json_encode($preset) }}">
                        {{ $preset['name'] }} ({{ $preset['district'] }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    <!-- Market Details Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ $isEdit ? route('admin.markets.update', $market) : route('admin.markets.store') }}" class="space-y-6">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- District Selector -->
                <div>
                    <label for="district_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">District</label>
                    <select id="district_id" name="district_id" required class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">Select District</option>
                        @foreach($districts as $district)
                            <option value="{{ $district->id }}" data-name="{{ $district->name }}" {{ old('district_id', $market->district_id) == $district->id ? 'selected' : '' }}>
                                {{ $district->name }} ({{ $district->name_kn }})
                            </option>
                        @endforeach
                    </select>
                    @error('district_id')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Taluk Selector -->
                <div>
                    <label for="taluk_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Taluk (Optional)</label>
                    <select id="taluk_id" name="taluk_id" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">District Headquarters / Direct</option>
                        @foreach($taluks as $taluk)
                            <option value="{{ $taluk->id }}" {{ old('taluk_id', $market->taluk_id) == $taluk->id ? 'selected' : '' }}>
                                {{ $taluk->name }} ({{ $taluk->name_kn }})
                            </option>
                        @endforeach
                    </select>
                    @error('taluk_id')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Market Name in English -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Mandi / Yard Name (English)</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $market->name) }}" required placeholder="e.g. Shivamogga APMC" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Market Name in Kannada -->
                <div>
                    <label for="name_kn" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Mandi / Yard Name (Kannada)</label>
                    <input type="text" id="name_kn" name="name_kn" value="{{ old('name_kn', $market->name_kn) }}" placeholder="e.g. ಶಿವಮೊಗ್ಗ ಎಪಿಎಂಸಿ" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-kannada focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name_kn')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- APMC Code (Optional) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="code" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                            Official APMC Code <span class="text-slate-500 font-normal lowercase">(optional)</span>
                        </label>
                        <button type="button" id="btn_auto_code" class="text-[11px] font-semibold text-emerald-400 hover:text-emerald-300 transition">
                            ⚡ Auto-Generate
                        </button>
                    </div>
                    <input type="text" id="code" name="code" value="{{ old('code', $market->code) }}" placeholder="Leave blank to auto-generate (e.g. KA_APMC_SHI)" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">If you don't know the code, leave it blank. Krushi Baandhava will generate a unique system code automatically.</p>
                    @error('code')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Market Type -->
                <div>
                    <label for="market_type" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Market Type</label>
                    <select id="market_type" name="market_type" required class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="APMC" {{ old('market_type', $market->market_type) === 'APMC' ? 'selected' : '' }}>APMC Main Yard</option>
                        <option value="Sub-market" {{ old('market_type', $market->market_type) === 'Sub-market' ? 'selected' : '' }}>Sub-market Yard</option>
                        <option value="Private" {{ old('market_type', $market->market_type) === 'Private' ? 'selected' : '' }}>Private Mandi / Commodity Board / Cooperative</option>
                    </select>
                    @error('market_type')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Latitude -->
                <div>
                    <label for="latitude" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">GPS Latitude (For Distance Calculation)</label>
                    <input type="number" step="any" id="latitude" name="latitude" value="{{ old('latitude', $market->latitude) }}" placeholder="e.g. 13.9310" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('latitude')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Longitude -->
                <div>
                    <label for="longitude" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">GPS Longitude (For Distance Calculation)</label>
                    <input type="number" step="any" id="longitude" name="longitude" value="{{ old('longitude', $market->longitude) }}" placeholder="e.g. 75.5700" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('longitude')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Address -->
            <div>
                <label for="address" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Yard Address / Location Details</label>
                <textarea id="address" name="address" rows="2" placeholder="e.g. APMC Market Yard, B.H. Road, Shivamogga" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">{{ old('address', $market->address) }}</textarea>
                @error('address')
                    <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Checkbox -->
            <div class="pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $market->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs font-semibold text-slate-300 select-none">Active in market listings & nearby comparisons</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('admin.markets.index') }}" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition cursor-pointer">
                    {{ $isEdit ? 'Save Changes' : 'Register Mandi' }}
                </button>
            </div>
        </form>
    </div>

    <!-- Mapped Feed Aliases Section (Only on Edit Screen) -->
    @if($isEdit)
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
        <div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                <h3 class="text-sm font-bold text-white">Raw Feed Aliases Mapped to this Mandi</h3>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Whenever daily arrival price feeds (data.gov.in, Agmarknet, etc.) arrive with any of these raw market names, they will automatically be recognized and routed to <strong class="text-emerald-300">{{ $market->name }}</strong>.
            </p>
        </div>

        <!-- Current Mapped Aliases Pills -->
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5">Active Mapped Aliases ({{ $market->sourceMappings->count() }})</div>
            @if($market->sourceMappings->isEmpty())
                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 text-slate-500 text-xs">
                    No feed aliases currently mapped. The system will rely on exact canonical name matching ("{{ $market->name }}").
                </div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($market->sourceMappings as $mapping)
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700/80 text-xs text-slate-200 shadow-sm">
                            <span class="font-mono text-emerald-400 font-semibold">{{ $mapping->source_market_name }}</span>
                            @if($mapping->source_district_name)
                                <span class="text-[10px] text-slate-400">({{ $mapping->source_district_name }})</span>
                            @endif
                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-800 text-slate-400">
                                {{ $mapping->dataSource?->name ?? 'Default Source' }}
                            </span>
                            <form method="POST" action="{{ route('admin.markets.aliases.remove', [$market, $mapping]) }}" class="inline" onsubmit="return confirm('Remove alias {{ $mapping->source_market_name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-rose-400 transition ml-1" title="Delete alias">
                                    &times;
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Add New Alias Form -->
        <div class="pt-4 border-t border-slate-800">
            <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3">Map New Feed Alias</h4>
            <form method="POST" action="{{ route('admin.markets.aliases.add', $market) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <div class="sm:col-span-2">
                    <input type="text" name="source_market_name" required placeholder="Feed Mandi Name (e.g. {{ str_replace(' APMC', '', $market->name) }})" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <select name="data_source_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        @foreach($dataSources ?? [] as $ds)
                            <option value="{{ $ds->id }}">{{ $ds->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition cursor-pointer">
                        + Map Alias
                    </button>
                </div>
            </form>
            <p class="text-[11px] text-slate-500 mt-2">
                Tip: Adding an alias here will immediately reprocess any pending or rejected records from that data feed.
            </p>
        </div>
    </div>
    @endif

</div>

<!-- JavaScript for Preset Auto-Fill and Code Generation -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const presetSelector = document.getElementById('preset_selector');
    const nameInput = document.getElementById('name');
    const nameKnInput = document.getElementById('name_kn');
    const districtSelect = document.getElementById('district_id');
    const codeInput = document.getElementById('code');
    const typeSelect = document.getElementById('market_type');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const addressInput = document.getElementById('address');
    const btnAutoCode = document.getElementById('btn_auto_code');

    // Preset Quick-Fill Handler
    if (presetSelector) {
        presetSelector.addEventListener('change', function () {
            const selectedOpt = presetSelector.options[presetSelector.selectedIndex];
            if (!selectedOpt || !selectedOpt.dataset.json) return;

            try {
                const data = JSON.parse(selectedOpt.dataset.json);
                if (data.name) nameInput.value = data.name;
                if (data.name_kn) nameKnInput.value = data.name_kn;
                if (data.code) codeInput.value = data.code;
                if (data.market_type && typeSelect) typeSelect.value = data.market_type;
                if (data.lat !== undefined) latInput.value = data.lat;
                if (data.lon !== undefined) lonInput.value = data.lon;
                if (data.address) addressInput.value = data.address;

                // Match District by name
                if (data.district && districtSelect) {
                    for (let i = 0; i < districtSelect.options.length; i++) {
                        const opt = districtSelect.options[i];
                        if (opt.dataset.name && opt.dataset.name.toLowerCase() === data.district.toLowerCase()) {
                            districtSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            } catch (e) {
                console.error('Error applying preset', e);
            }
        });
    }

    // Auto-Generate Code Button Handler
    if (btnAutoCode && nameInput && codeInput) {
        btnAutoCode.addEventListener('click', function () {
            const val = nameInput.value.trim();
            if (!val) {
                alert('Please enter a Mandi Name first.');
                return;
            }
            let clean = val.replace(/\s*\(.*?\)/g, '').replace(/\s+APMC/gi, '').trim();
            let slug = clean.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').toUpperCase();
            codeInput.value = 'KA_APMC_' + slug;
        });
    }
});
</script>
@endsection
