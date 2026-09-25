@extends('layouts.admin')

@section('title', $isEdit ? 'Edit ' . $crop->name : 'Register New Crop')

@section('content')
<div class="w-full space-y-6" x-data="{ 
    tab: '{{ request()->get('tab', 'profile') }}',
    // Proximity state
    radius: {{ old('market_radius_km', $crop->market_radius_km ?? 300) }},
    sort: '{{ old('default_market_sort', $crop->default_market_sort ?? 'nearest_first') }}',
    
    // Live Variety Suggestions State
    liveLoading: false,
    liveSourceId: '{{ $dataSources->first()?->id ?? 1 }}',
    liveVarietiesList: [],
    liveError: null,

    // API Inspector Modal State
    inspectorOpen: false,
    inspectorLoading: false,
    inspectorData: null,
    inspectorError: null,

    initLiveVarieties() {
        @if($isEdit)
        this.fetchLiveVarieties();
        @endif
    },

    fetchLiveVarieties() {
        this.liveLoading = true;
        this.liveError = null;
        fetch('{{ $isEdit ? route('admin.crops.live-varieties', $crop) : '#' }}?data_source_id=' + this.liveSourceId, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            this.liveLoading = false;
            if (data.ok) {
                this.liveVarietiesList = (data.varieties || [])
                    .filter(v => v.raw_name && String(v.raw_name).trim().length > 0)
                    .map(v => ({
                        ...v,
                        selected_grade_id: v.suggested_variety_id ? String(v.suggested_variety_id) : '',
                        mapping_in_progress: false
                    }));
            } else {
                this.liveError = data.error || 'Failed to fetch live varieties.';
            }
        })
        .catch(err => {
            this.liveLoading = false;
            this.liveError = err.message || 'Network error.';
        });
    },

    quickMapVariety(item) {
        if (!item) return;
        const targetGradeId = item.selected_grade_id;
        const varietyRawName = item.raw_name;

        if (!targetGradeId) {
            alert('Please select a target canonical grade to map.');
            return;
        }

        item.mapping_in_progress = true;

        fetch('{{ $isEdit ? route('admin.crops.variety-aliases.add', $crop) : '#' }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                crop_variety_id: targetGradeId,
                source_variety_name: varietyRawName,
                data_source_id: this.liveSourceId ? parseInt(this.liveSourceId) : null
            })
        })
        .then(res => res.json())
        .then(data => {
            item.mapping_in_progress = false;
            if (data.ok) {
                // Update item locally
                item.is_mapped = true;
                item.mapped_variety_name = data.mapping.variety_name;
                item.mapped_variety_id = data.mapping.variety_id;
                item.mapping_id = data.mapping.id;
            } else {
                alert(data.message || data.error || 'Failed to map variety.');
            }
        })
        .catch(err => {
            item.mapping_in_progress = false;
            alert(err.message || 'Failed to map variety.');
        });
    },

    quickUnmapVariety(item) {
        if (!item || !item.mapping_id) return;
        if (!confirm(`Remove variety mapping for '${item.raw_name}'?`)) return;

        item.mapping_in_progress = true;
        const deleteUrl = '{{ $isEdit ? url('/admin/crops/' . $crop->id . '/variety-aliases') : '#' }}/' + item.mapping_id;

        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            item.mapping_in_progress = false;
            if (data.ok) {
                item.is_mapped = false;
                item.mapped_variety_name = null;
                item.mapped_variety_id = null;
                item.mapping_id = null;
                item.selected_grade_id = item.suggested_variety_id ? String(item.suggested_variety_id) : '';
            } else {
                alert(data.message || data.error || 'Failed to remove mapping.');
            }
        })
        .catch(err => {
            item.mapping_in_progress = false;
            alert(err.message || 'Failed to remove mapping.');
        });
    },

    openInspector() {
        this.inspectorOpen = true;
        this.inspectorLoading = true;
        this.inspectorError = null;
        this.inspectorData = null;

        fetch('{{ $isEdit ? route('admin.crops.inspect-feed', $crop) : '#' }}?data_source_id=' + this.liveSourceId, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            this.inspectorLoading = false;
            if (data.ok) {
                this.inspectorData = data;
            } else {
                this.inspectorError = data.error || 'Failed to retrieve raw feed.';
            }
        })
        .catch(err => {
            this.inspectorLoading = false;
            this.inspectorError = err.message || 'Network error.';
        });
    }
}" x-init="initLiveVarieties()">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                <a href="{{ route('admin.crops.index') }}" class="hover:text-emerald-400 transition">Commodities & Crops</a>
                <span>/</span>
                <span class="text-white font-bold">{{ $isEdit ? $crop->name : 'New Crop' }}</span>
            </div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🌾</span> {{ $isEdit ? 'Edit ' . $crop->name : 'Register New Crop' }}
                @if($isEdit && $crop->name_kn)
                    <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-950/80 text-emerald-300 border border-emerald-800/60 rounded-full font-kannada">{{ $crop->name_kn }}</span>
                @endif
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.crops.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition shadow-sm">
                <span>← Back to Directory</span>
            </a>
            @if($isEdit)
                <a href="{{ route('farmer.crop.detail', $crop->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-emerald-300 bg-emerald-950/70 border border-emerald-800/60 rounded-xl hover:bg-emerald-900 transition shadow-sm">
                    <span>👁️ Live View</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-300 text-xs font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Modern Tab Bar -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-px">
        <button type="button" @click="tab = 'profile'" 
                :class="tab === 'profile' ? 'border-emerald-500 text-emerald-400 bg-slate-900' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
                class="px-4 py-2.5 text-xs font-bold border-b-2 rounded-t-xl transition cursor-pointer flex items-center gap-2">
            <span>📝</span> Basic Profile & Agronomy
        </button>

        <button type="button" @click="tab = 'discovery'" 
                :class="tab === 'discovery' ? 'border-emerald-500 text-emerald-400 bg-slate-900' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
                class="px-4 py-2.5 text-xs font-bold border-b-2 rounded-t-xl transition cursor-pointer flex items-center gap-2">
            <span>📍</span> Discovery & Proximity
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono" x-text="radius >= 500 ? 'All KA' : radius + ' km'"></span>
        </button>

        @if($isEdit)
            <button type="button" @click="tab = 'varieties'" 
                    :class="tab === 'varieties' ? 'border-emerald-500 text-emerald-400 bg-slate-900' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 rounded-t-xl transition cursor-pointer flex items-center gap-2">
                <span>🏷️</span> Varieties & Live API Strings
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-950 text-emerald-300 border border-emerald-800/80 font-bold">
                    {{ $crop->varieties->count() }} Grades
                </span>
            </button>
        @endif
    </div>

    <!-- Main Crop Form (Wraps Profile & Discovery) -->
    <form method="POST" action="{{ $isEdit ? route('admin.crops.update', $crop) : route('admin.crops.store') }}" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <!-- TAB 1: Basic Profile & Agronomy -->
        <div x-show="tab === 'profile'" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div>
                    <h2 class="text-base font-extrabold text-white">Commodity Specification</h2>
                    <p class="text-xs text-slate-400">Official names, categories, taxonomy, and standard APMC trading measurement units.</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $crop->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/80' : 'bg-slate-800 text-slate-400' }}">
                    {{ $crop->is_active ? '● Active in Feeds' : 'Paused' }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Category Selector -->
                <div>
                    <label for="category_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Category</label>
                    <select id="category_id" name="category_id" required class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $crop->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Standard Unit -->
                <div>
                    <label for="standard_unit" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Standard Trading Unit</label>
                    <select id="standard_unit" name="standard_unit" required class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                        @foreach(['Quintal', '50 Kg Bag', '1000 Nuts', 'Kg', '15 Kg Box', 'Ton'] as $unit)
                            <option value="{{ $unit }}" {{ old('standard_unit', $crop->standard_unit) == $unit ? 'selected' : '' }}>
                                {{ $unit }}
                            </option>
                        @endforeach
                    </select>
                    @error('standard_unit')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Crop Name (English) -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Crop Name (English)</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $crop->name) }}" required placeholder="e.g. Arecanut" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    @error('name')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Crop Name (Kannada) -->
                <div>
                    <label for="name_kn" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Crop Name (Kannada)</label>
                    <input type="text" id="name_kn" name="name_kn" value="{{ old('name_kn', $crop->name_kn) }}" placeholder="e.g. ಅಡಿಕೆ" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-kannada font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    @error('name_kn')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">URL Slug (Auto-generated if empty)</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $crop->slug) }}" placeholder="e.g. arecanut" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    @error('slug')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Scientific Name -->
                <div>
                    <label for="scientific_name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Scientific / Botanical Name</label>
                    <input type="text" id="scientific_name" name="scientific_name" value="{{ old('scientific_name', $crop->scientific_name) }}" placeholder="e.g. Areca catechu" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs italic focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    @error('scientific_name')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Crop Description / Agronomy Notes</label>
                <textarea id="description" name="description" rows="3" placeholder="Cultivation notes, harvesting seasons, major Karnataka producing taluks and mandis..." class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">{{ old('description', $crop->description) }}</textarea>
            </div>

            <!-- Checkboxes -->
            <div class="flex flex-wrap items-center gap-6 pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_major" value="1" {{ old('is_major', $crop->is_major) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs font-bold text-slate-300 select-none">★ Highlight as Major Karnataka Crop</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $crop->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs font-bold text-slate-300 select-none">Active in market price feeds</span>
                </label>
            </div>

            <!-- Action buttons -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('admin.crops.index') }}" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition cursor-pointer">
                    {{ $isEdit ? 'Save Changes' : 'Register Crop' }}
                </button>
            </div>
        </div>

        <!-- TAB 2: Market Discovery & Proximity (Preserves VIEW DIFFERENT MARKET and inputs) -->
        <div x-show="tab === 'discovery'" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                <div>
                    <h2 class="text-base font-extrabold text-white flex items-center gap-2">
                        <span>📍</span> VIEW DIFFERENT MARKET — Distance & Sorting Configuration
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Control how surrounding APMC mandis are filtered, ordered, and badged for this crop on the website.</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-emerald-950 text-emerald-300 border border-emerald-800/80 shrink-0 self-start sm:self-auto"
                      x-text="radius == 0 || radius >= 500 ? 'All Karnataka (No Radius Limit)' : 'Max Radius: ' + radius + ' km'">
                </span>
            </div>

            <div class="space-y-6">
                <!-- KM Radius Slider -->
                <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="market_radius_km" class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>📏</span> Mandi Search Radius (Distance Slider)
                        </label>
                        <span class="text-xs font-mono font-extrabold text-emerald-400 bg-slate-900 px-2 py-0.5 rounded-md border border-slate-800" x-text="radius == 0 || radius >= 500 ? 'Unlimited (All Karnataka)' : radius + ' km radius'"></span>
                    </div>

                    <!-- Slider Input -->
                    <div class="flex items-center gap-4 pt-1">
                        <span class="text-xs text-slate-500 font-bold shrink-0">25 km</span>
                        <input type="range" 
                               id="market_radius_km" 
                               name="market_radius_km" 
                               min="25" 
                               max="500" 
                               step="25"
                               x-model="radius"
                               class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500">
                        <span class="text-xs text-slate-500 font-bold shrink-0">500 km</span>
                    </div>

                    <!-- Quick Presets -->
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <span class="text-xs font-bold text-slate-400">Quick Presets:</span>
                        <button type="button" @click="radius = 50" class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer" :class="radius == 50 ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-700'">50 km</button>
                        <button type="button" @click="radius = 100" class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer" :class="radius == 100 ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-700'">100 km</button>
                        <button type="button" @click="radius = 250" class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer" :class="radius == 250 ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-700'">250 km</button>
                        <button type="button" @click="radius = 350" class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer" :class="radius == 350 ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-700'">350 km</button>
                        <button type="button" @click="radius = 500" class="px-2.5 py-1 text-xs font-bold rounded-lg border transition cursor-pointer" :class="radius >= 500 ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm' : 'bg-slate-900 hover:bg-slate-800 text-slate-300 border-slate-700'">No Limit (All KA)</button>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Surrounding mandis within this distance from the farmer's district/GPS will be displayed in VIEW DIFFERENT MARKET.</p>
                </div>

                <!-- Default Sort Option -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                        <span>🔀</span> Default Mandi Sort Order in Website
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-4 rounded-xl border transition cursor-pointer" 
                               :class="sort === 'nearest_first' ? 'bg-emerald-950/40 border-emerald-500 text-white shadow-sm' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                            <input type="radio" name="default_market_sort" value="nearest_first" x-model="sort" class="w-4 h-4 text-emerald-600 bg-slate-950 border-slate-700 focus:ring-emerald-500">
                            <div>
                                <div class="text-xs font-bold text-slate-200">📍 Nearest First (Proximity)</div>
                                <div class="text-[11px] text-slate-400">Order mandis starting from closest driving distance</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-4 rounded-xl border transition cursor-pointer" 
                               :class="sort === 'highest_price_first' ? 'bg-emerald-950/40 border-emerald-500 text-white shadow-sm' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'">
                            <input type="radio" name="default_market_sort" value="highest_price_first" x-model="sort" class="w-4 h-4 text-emerald-600 bg-slate-950 border-slate-700 focus:ring-emerald-500">
                            <div>
                                <div class="text-xs font-bold text-slate-200">💰 Highest Price First (Profit)</div>
                                <div class="text-[11px] text-slate-400">Order mandis starting from highest modal rate today</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Toggles: User Sort Switch & Smart Badges -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-4 rounded-xl bg-slate-950/70 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                        <input type="checkbox" name="allow_user_sort_toggle" value="1" {{ old('allow_user_sort_toggle', $crop->allow_user_sort_toggle ?? true) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-200 block">Show Sort Switcher to Farmers</span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Renders "Nearest First" vs "Highest Price First" toggle pills on the website.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 rounded-xl bg-slate-950/70 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                        <input type="checkbox" name="enable_smart_badges" value="1" {{ old('enable_smart_badges', $crop->enable_smart_badges ?? true) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-200 block">Enable Automatic Smart Badges</span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Tags closest mandi with 📍 Nearest and best rate with 🔥 Top Rate.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('admin.crops.index') }}" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition cursor-pointer">
                    {{ $isEdit ? 'Save Changes' : 'Register Crop' }}
                </button>
            </div>
        </div>
    </form>

    <!-- TAB 3: Varieties & Live API Variety Strings (When Editing) -->
    @if($isEdit)
        <div x-show="tab === 'varieties'" class="space-y-6">

            <!-- Card 1: Canonical Varieties & Add Grade -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                    <div>
                        <h2 class="text-base font-extrabold text-white">Commercial Varieties of {{ $crop->name }}</h2>
                        <p class="text-xs text-slate-400">Official commercial trade grades and cultivars traded in Karnataka APMC mandis.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-200 border border-slate-700 text-xs font-bold">
                        {{ $crop->varieties->count() }} Registered Grades
                    </span>
                </div>

                <!-- Add Variety Form -->
                <form method="POST" action="{{ route('admin.varieties.store') }}" class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @csrf
                    <input type="hidden" name="crop_id" value="{{ $crop->id }}">

                    <div>
                        <input type="text" name="name" required placeholder="Grade Name (English)*" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <input type="text" name="name_kn" placeholder="Grade Name (Kannada)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-kannada font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <input type="text" name="slug" placeholder="Slug (optional)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg transition shadow-sm cursor-pointer">
                            + Add Grade
                        </button>
                    </div>
                </form>

                <!-- Varieties List -->
                @if($crop->varieties->isEmpty())
                    <div class="text-center py-8 text-xs font-medium text-slate-500 bg-slate-950/50 rounded-xl border border-dashed border-slate-800">
                        No canonical grades registered yet. Add common grades above or map them directly from live API suggestions below.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        @foreach($crop->varieties as $variety)
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2.5 hover:border-slate-700 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-extrabold text-xs text-white">{{ $variety->name }}</div>
                                        @if($variety->name_kn)
                                            <div class="text-[11px] text-emerald-400 font-kannada font-semibold">{{ $variety->name_kn }}</div>
                                        @endif
                                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">slug: {{ $variety->slug }}</div>
                                    </div>

                                    <form method="POST" action="{{ route('admin.varieties.destroy', $variety) }}" onsubmit="return confirm('Remove variety grade {{ $variety->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-slate-500 hover:text-rose-400 hover:bg-slate-800 transition cursor-pointer" title="Remove Variety">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>

                                <!-- Mapped Variety Strings Badge List -->
                                <div class="pt-2 border-t border-slate-850">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Mapped API Strings</span>
                                        <span class="text-[9px] font-mono text-slate-500">({{ $variety->sourceMappings->count() }})</span>
                                    </div>
                                    @php
                                        $varietyMappings = $variety->sourceMappings ?? collect();
                                    @endphp
                                    @if($varietyMappings->isEmpty())
                                        <span class="text-[10px] text-slate-500 italic">Exact name match only ("{{ $variety->name }}")</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($varietyMappings as $vMap)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-slate-900 border border-slate-700 text-emerald-300 font-mono font-medium shadow-sm">
                                                    <span>{{ $vMap->source_variety_name }}</span>
                                                    <form method="POST" action="{{ route('admin.crops.variety-aliases.remove', [$crop, $vMap]) }}" class="inline" onsubmit="return confirm('Unmap variety string {{ $vMap->source_variety_name }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-slate-500 hover:text-rose-400 font-bold transition cursor-pointer">&times;</button>
                                                    </form>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Card 2: Live API Variety String Detector & 1-Click Mapper -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                    <div>
                        <h2 class="text-base font-extrabold text-white flex items-center gap-2">
                            <span>⚡</span> Live API Variety Suggestions & Auto-Detection
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Real variety strings extracted from recent market records. Map them with 1-click to prevent unclassified rates.</p>
                    </div>

                    <!-- Source Selector & Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                        <select x-model="liveSourceId" @change="fetchLiveVarieties()" class="px-3 py-1.5 text-xs font-semibold bg-slate-950/80 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @foreach($dataSources as $ds)
                                <option value="{{ $ds->id }}">{{ $ds->name }}</option>
                            @endforeach
                        </select>

                        <button type="button" @click="fetchLiveVarieties()" :disabled="liveLoading" class="px-3 py-1.5 text-xs font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700/60 rounded-xl transition flex items-center gap-1 shadow-sm cursor-pointer">
                            <span :class="{'animate-spin': liveLoading}">🔄</span>
                            <span x-text="liveLoading ? 'Loading...' : 'Refresh'"></span>
                        </button>

                        <button type="button" @click="openInspector()" class="px-3 py-1.5 text-xs font-bold text-cyan-300 bg-cyan-950/70 border border-cyan-800/60 rounded-xl hover:bg-cyan-900 transition flex items-center gap-1 shadow-sm cursor-pointer">
                            <span>🔍</span> Inspect Raw Feed
                        </button>
                    </div>
                </div>

                <!-- Live Variety Suggestion Chips Grid -->
                <div>
                    <!-- Error State -->
                    <div x-show="liveError" class="p-4 rounded-xl bg-amber-950/60 border border-amber-800/60 text-amber-300 text-xs font-medium mb-3">
                        <span class="font-bold">Notice:</span> <span x-text="liveError"></span>
                    </div>

                    <!-- Loading Skeleton -->
                    <div x-show="liveLoading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 animate-pulse">
                        <div class="h-20 bg-slate-950/70 border border-slate-800 rounded-xl"></div>
                        <div class="h-20 bg-slate-950/70 border border-slate-800 rounded-xl"></div>
                        <div class="h-20 bg-slate-950/70 border border-slate-800 rounded-xl"></div>
                    </div>

                    <!-- Loaded Items -->
                    <div x-show="!liveLoading && liveVarietiesList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <template x-for="item in liveVarietiesList" :key="item.raw_name">
                            <div class="p-3.5 rounded-xl border transition space-y-2"
                                 :class="item.is_mapped ? 'bg-slate-950/70 border-emerald-800/50' : 'bg-slate-950/70 border-amber-800/50'">
                                <div class="flex items-start justify-between gap-1.5">
                                    <div class="truncate">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-mono font-bold text-white truncate" x-text="item.raw_name"></span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-medium">
                                            <span x-text="item.market"></span> • <span class="font-bold text-emerald-400" x-text="'₹' + item.price.toLocaleString()"></span>
                                        </div>
                                    </div>

                                    <!-- Status Pill -->
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black shrink-0"
                                          :class="item.is_mapped ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/80' : 'bg-amber-950 text-amber-300 border border-amber-800/80'">
                                        <span x-text="item.is_mapped ? '✓ Mapped' : '⚠️ Unmapped'"></span>
                                    </span>
                                </div>

                                <!-- Mapped Destination or 1-Click Mapper -->
                                <div class="pt-2 border-t border-slate-800">
                                    <template x-if="item.is_mapped">
                                        <div class="text-[11px] font-medium text-emerald-400 flex items-center justify-between gap-1">
                                            <span class="truncate">Mapped to: <strong class="font-bold text-white" x-text="item.mapped_variety_name"></strong></span>
                                            <button type="button" 
                                                    @click="quickUnmapVariety(item)"
                                                    :disabled="item.mapping_in_progress"
                                                    title="Remove mapping"
                                                    class="text-xs text-rose-400 hover:text-rose-300 p-1 hover:bg-rose-950/50 rounded transition cursor-pointer shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="!item.is_mapped">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <select x-model="item.selected_grade_id" class="w-full px-2 py-1 text-[11px] bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                                    <option value="">-- Choose Grade --</option>
                                                    @foreach($crop->varieties as $v)
                                                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" 
                                                        @click="quickMapVariety(item)"
                                                        :disabled="item.mapping_in_progress"
                                                        class="px-2.5 py-1 text-[11px] font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shrink-0 transition shadow-sm cursor-pointer disabled:opacity-50">
                                                    <span x-text="item.mapping_in_progress ? '...' : '+ Map'"></span>
                                                </button>
                                            </div>
                                            <template x-if="item.suggested_variety_name">
                                                <div class="text-[10px] text-emerald-400 font-medium flex items-center gap-1">
                                                    <span>💡 Auto-detected:</span>
                                                    <span class="font-bold underline" x-text="item.suggested_variety_name"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty state -->
                    <div x-show="!liveLoading && liveVarietiesList.length === 0 && !liveError" class="text-center py-8 text-xs font-medium text-slate-500 bg-slate-950/50 rounded-xl border border-dashed border-slate-800">
                        No distinct raw variety strings detected in recent feeds for this crop yet. Click "Inspect Raw Feed" above to inspect incoming payloads.
                    </div>
                </div>
            </div>

            <!-- Card 3: Manual Mapping Form (Preserves exact strings for test compatibility) -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
                <div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <h4 class="text-xs font-extrabold text-white uppercase tracking-wider">Map Raw API Variety / Grade Strings</h4>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        When daily feeds send non-standard variety names (e.g. data.gov.in sends <code class="text-emerald-400 bg-slate-950 px-1 py-0.5 rounded font-mono">"Common"</code>, <code class="text-emerald-400 bg-slate-950 px-1 py-0.5 rounded font-mono">"Fine"</code>, <code class="text-emerald-400 bg-slate-950 px-1 py-0.5 rounded font-mono">"Grade A"</code>, or <code class="text-emerald-400 bg-slate-950 px-1 py-0.5 rounded font-mono">"Dhan (Basmati)"</code>), map them here so they are classified under the right grade automatically.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.crops.variety-aliases.add', $crop) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-slate-950/70 p-4 rounded-xl border border-slate-800">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Target Canonical Grade</label>
                        <select name="crop_variety_id" required class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="">-- Choose Grade --</option>
                            @foreach($crop->varieties as $v)
                                <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->name_kn }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Raw API Variety String</label>
                        <input type="text" name="source_variety_name" required placeholder="e.g. Common / Grade A / FAQ" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Data Source Provider</label>
                        <select name="data_source_id" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            @foreach($dataSources ?? [] as $ds)
                                <option value="{{ $ds->id }}">{{ $ds->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full py-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg shadow-sm transition cursor-pointer">
                            + Map Variety Alias
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Raw API Response Inspector Drawer / Modal -->
        <div x-show="inspectorOpen" 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
             @keydown.escape.window="inspectorOpen = false">
            <!-- Modal Backdrop -->
            <div x-show="inspectorOpen"
                 x-transition.opacity
                 class="fixed inset-0 bg-black/80"
                 @click="inspectorOpen = false"></div>

            <div class="relative z-10 bg-slate-900 rounded-2xl border border-slate-700 shadow-2xl max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden text-white my-8"
                 @click.away="inspectorOpen = false">
                
                <!-- Drawer Header -->
                <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-950/60">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-cyan-950 text-cyan-400 border border-cyan-800/60 flex items-center justify-center font-bold text-base shadow-sm">
                            🔍
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white">Raw API Response Inspector</h3>
                            <p class="text-xs text-slate-400">Live feed diagnostic & payload explorer for <span class="font-bold text-emerald-400">{{ $crop->name }}</span></p>
                        </div>
                    </div>
                    <button type="button" @click="inspectorOpen = false" class="text-slate-400 hover:text-white text-xl font-bold p-1 cursor-pointer">&times;</button>
                </div>

                <!-- Drawer Content -->
                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <div x-show="inspectorLoading" class="text-center py-12 space-y-3">
                        <div class="inline-block animate-spin text-2xl text-emerald-400">⏳</div>
                        <div class="text-xs font-bold text-slate-300">Connecting to API provider and retrieving payload...</div>
                    </div>

                    <div x-show="inspectorError" class="p-4 rounded-xl bg-rose-950/60 border border-rose-800/80 text-rose-300 text-xs">
                        <strong class="font-bold">Error:</strong> <span x-text="inspectorError"></span>
                    </div>

                    <template x-if="inspectorData">
                        <div class="space-y-4">
                            <!-- Telemetry Pills -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase">Provider</div>
                                    <div class="text-xs font-bold text-white truncate" x-text="inspectorData.source.name"></div>
                                </div>
                                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase">HTTP Status</div>
                                    <div class="text-xs font-black text-emerald-400" x-text="inspectorData.http_status + ' OK'"></div>
                                </div>
                                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase">Latency</div>
                                    <div class="text-xs font-mono font-bold text-cyan-400" x-text="inspectorData.response_time_ms + ' ms'"></div>
                                </div>
                                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase">Status</div>
                                    <div class="text-xs font-bold capitalize text-emerald-400" x-text="inspectorData.status"></div>
                                </div>
                            </div>

                            <!-- Schema Detected Fields -->
                            <div x-show="inspectorData.detected_fields && inspectorData.detected_fields.length > 0">
                                <span class="text-xs font-bold text-slate-400 block mb-1.5 uppercase tracking-wider">Detected Field Schema:</span>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="f in inspectorData.detected_fields" :key="f">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 border border-slate-700" x-text="f"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Formatted JSON viewer -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Raw Payload / Sample Records:</span>
                                    <button type="button" 
                                            @click="navigator.clipboard.writeText(JSON.stringify(inspectorData.sample_records, null, 2)); alert('Copied JSON payload to clipboard!');"
                                            class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 cursor-pointer">
                                        📋 Copy JSON
                                    </button>
                                </div>
                                <pre class="p-4 rounded-xl bg-black/90 border border-slate-800 text-emerald-400 font-mono text-[11px] overflow-x-auto max-h-72 leading-relaxed" 
                                     x-text="JSON.stringify(inspectorData.sample_records, null, 2)"></pre>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Inspect the exact variety strings returned above and map them in the Varieties tab.</span>
                    <button type="button" @click="inspectorOpen = false" class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 border border-slate-700/60 rounded-xl hover:bg-slate-700 transition cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
