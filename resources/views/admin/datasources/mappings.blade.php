@extends('layouts.admin')

@section('title', "Mappings for {$datasource->name}")

@section('content')
<div class="space-y-6" x-data="{ tab: 'fields' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.datasources.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 transition flex items-center gap-1 mb-1">
                <span>←</span> Back to Data Sources
            </a>
            <h1 class="text-2xl font-black text-emerald-950 flex items-center gap-2">
                <span>🔀</span> Data Mappings & Aliases
            </h1>
            <p class="text-sm text-stone-500 font-medium">Configure declarative field transforms and alias resolvers for <b class="text-stone-900">{{ $datasource->name }}</b>.</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-1 bg-stone-100 p-1 rounded-xl text-xs font-bold">
            <button type="button" @click="tab = 'fields'" :class="tab === 'fields' ? 'bg-white text-emerald-800 shadow-xs' : 'text-stone-500 hover:text-stone-900'" class="px-3.5 py-1.5 rounded-lg transition">
                Field Mappings ({{ $datasource->mappings->count() }})
            </button>
            <button type="button" @click="tab = 'crops'" :class="tab === 'crops' ? 'bg-white text-emerald-800 shadow-xs' : 'text-stone-500 hover:text-stone-900'" class="px-3.5 py-1.5 rounded-lg transition">
                Crop Aliases ({{ $datasource->cropMappings->count() }})
            </button>
            <button type="button" @click="tab = 'markets'" :class="tab === 'markets' ? 'bg-white text-emerald-800 shadow-xs' : 'text-stone-500 hover:text-stone-900'" class="px-3.5 py-1.5 rounded-lg transition">
                Market Aliases ({{ $datasource->marketMappings->count() }})
            </button>
        </div>
    </div>

    <!-- TAB 1: DECLARATIVE FIELD MAPPINGS -->
    <div x-show="tab === 'fields'" class="space-y-4">
        <!-- New Field Mapping Form Card -->
        <div class="bg-white border border-stone-200/80 rounded-2xl p-5 shadow-xs">
            <h2 class="text-xs font-bold text-stone-900 uppercase tracking-wider mb-3">Add Field Transformation Rule</h2>
            <form action="{{ route('admin.datasources.mappings.fields.store', $datasource) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Source Field</label>
                    <input type="text" name="source_field" required placeholder="e.g. Modal_Price" class="w-full px-3 py-2 text-xs font-mono border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Target Field</label>
                    <input type="text" name="target_field" required placeholder="e.g. modal_price" class="w-full px-3 py-2 text-xs font-mono border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Transformation</label>
                    <select name="transformation_rule" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        <option value="">None (Raw)</option>
                        <option value="trim">Trim Whitespace</option>
                        <option value="to_number">Convert to Decimal Number</option>
                        <option value="to_integer">Convert to Integer</option>
                        <option value="uppercase">Uppercase</option>
                        <option value="lowercase">Lowercase</option>
                        <option value="date_format:d/m/Y">Parse Date (DD/MM/YYYY)</option>
                        <option value="date_format:Y-m-d">Parse Date (YYYY-MM-DD)</option>
                        <option value="per_quintal_from_50kg">50kg Bag to 1 Quintal (* 2)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Default Fallback</label>
                    <input type="text" name="default_value" placeholder="Optional fallback" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl transition shadow-xs">
                        + Add Rule
                    </button>
                </div>
            </form>
        </div>

        <!-- Field Mappings List Table -->
        <div class="bg-white border border-stone-200/80 rounded-2xl overflow-hidden shadow-xs">
            <table class="w-full text-left text-xs text-stone-600">
                <thead class="bg-stone-50/80 text-stone-500 font-bold uppercase text-[11px] border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3">Source Payload Key</th>
                        <th class="px-4 py-3">Standard Target Field</th>
                        <th class="px-4 py-3">Transformation Rule</th>
                        <th class="px-4 py-3">Default Value</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 font-medium">
                    @forelse($datasource->mappings as $mapping)
                        <tr class="hover:bg-stone-50/60 transition">
                            <td class="px-5 py-3 font-mono font-bold text-stone-900">{{ $mapping->source_field }}</td>
                            <td class="px-4 py-3 font-mono text-emerald-800">{{ $mapping->target_field }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded bg-stone-100 text-stone-700 font-mono text-[11px]">
                                    {{ $mapping->transformation_rule ?: 'raw' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-stone-400 font-mono">{{ $mapping->default_value ?: '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <form action="{{ route('admin.datasources.mappings.fields.destroy', $mapping) }}" method="POST" onsubmit="return confirm('Remove field rule?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold text-xs">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-stone-400">No field transformation rules defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: CROP ALIAS RESOLVER -->
    <div x-show="tab === 'crops'" style="display: none;" class="space-y-4">
        <!-- New Crop Alias Form -->
        <div class="bg-white border border-stone-200/80 rounded-2xl p-5 shadow-xs">
            <h2 class="text-xs font-bold text-stone-900 uppercase tracking-wider mb-3">Map External Commodity Name to Canonical Crop</h2>
            <form action="{{ route('admin.datasources.mappings.crops.store', $datasource) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">External Commodity String</label>
                    <input type="text" name="source_crop_name" required placeholder="e.g. Betelnut or Arecanut" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">External Variety (Optional)</label>
                    <input type="text" name="source_variety_name" placeholder="e.g. Rashi" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Canonical Platform Crop</label>
                    <select name="crop_id" required class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        <option value="">— Select Crop —</option>
                        @foreach($allCrops as $crop)
                            <option value="{{ $crop->id }}">{{ $crop->name }} ({{ $crop->name_kn }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl transition shadow-xs">
                        + Save Crop Alias
                    </button>
                </div>
            </form>
        </div>

        <!-- Crop Aliases List -->
        <div class="bg-white border border-stone-200/80 rounded-2xl overflow-hidden shadow-xs">
            <table class="w-full text-left text-xs text-stone-600">
                <thead class="bg-stone-50/80 text-stone-500 font-bold uppercase text-[11px] border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3">External Commodity String</th>
                        <th class="px-4 py-3">External Variety</th>
                        <th class="px-4 py-3">Mapped Platform Crop</th>
                        <th class="px-4 py-3 text-center">Confidence</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 font-medium">
                    @forelse($datasource->cropMappings as $alias)
                        <tr class="hover:bg-stone-50/60 transition">
                            <td class="px-5 py-3 font-bold text-stone-900">{{ $alias->source_crop_name }}</td>
                            <td class="px-4 py-3 text-stone-500">{{ $alias->source_variety_name ?: 'Any' }}</td>
                            <td class="px-4 py-3 font-bold text-emerald-800">{{ $alias->crop?->name }} ({{ $alias->crop?->name_kn }})</td>
                            <td class="px-4 py-3 text-center font-mono">{{ number_format($alias->confidence_score * 100) }}%</td>
                            <td class="px-5 py-3 text-right">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Verified</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-stone-400">No crop alias mappings recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: MARKET ALIAS RESOLVER -->
    <div x-show="tab === 'markets'" style="display: none;" class="space-y-4">
        <!-- New Market Alias Form -->
        <div class="bg-white border border-stone-200/80 rounded-2xl p-5 shadow-xs">
            <h2 class="text-xs font-bold text-stone-900 uppercase tracking-wider mb-3">Map External Mandi Name to Canonical Market</h2>
            <form action="{{ route('admin.datasources.mappings.markets.store', $datasource) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">External Mandi String</label>
                    <input type="text" name="source_market_name" required placeholder="e.g. Shimoga or Sagar" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">External District (Optional)</label>
                    <input type="text" name="source_district_name" placeholder="e.g. Shimoga" class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase mb-1">Canonical Platform Market</label>
                    <select name="market_id" required class="w-full px-3 py-2 text-xs border border-stone-200 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        <option value="">— Select APMC Mandi —</option>
                        @foreach($allMarkets as $market)
                            <option value="{{ $market->id }}">{{ $market->name }} ({{ $market->district?->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl transition shadow-xs">
                        + Save Market Alias
                    </button>
                </div>
            </form>
        </div>

        <!-- Market Aliases List -->
        <div class="bg-white border border-stone-200/80 rounded-2xl overflow-hidden shadow-xs">
            <table class="w-full text-left text-xs text-stone-600">
                <thead class="bg-stone-50/80 text-stone-500 font-bold uppercase text-[11px] border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3">External Mandi String</th>
                        <th class="px-4 py-3">External District</th>
                        <th class="px-4 py-3">Mapped Platform Market</th>
                        <th class="px-4 py-3 text-center">Confidence</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 font-medium">
                    @forelse($datasource->marketMappings as $alias)
                        <tr class="hover:bg-stone-50/60 transition">
                            <td class="px-5 py-3 font-bold text-stone-900">{{ $alias->source_market_name }}</td>
                            <td class="px-4 py-3 text-stone-500">{{ $alias->source_district_name ?: 'Any' }}</td>
                            <td class="px-4 py-3 font-bold text-emerald-800">{{ $alias->market?->name }} ({{ $alias->market?->district?->name }})</td>
                            <td class="px-4 py-3 text-center font-mono">{{ number_format($alias->confidence_score * 100) }}%</td>
                            <td class="px-5 py-3 text-right">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Verified</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-stone-400">No market alias mappings recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
