@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">

    <!-- Header & Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.crops.index') }}" class="hover:text-emerald-400">Crops & Commodities</a>
                <span>/</span>
                <span class="text-white">{{ $isEdit ? 'Edit ' . $crop->name : 'New Crop' }}</span>
            </div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">{{ $isEdit ? 'Edit Crop & Varieties' : 'Register New Crop' }}</h2>
        </div>

        <a href="{{ route('admin.crops.index') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
            Back to Crops
        </a>
    </div>

    <!-- Crop Details Form Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ $isEdit ? route('admin.crops.update', $crop) : route('admin.crops.store') }}" class="space-y-6">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Category Selector -->
                <div>
                    <label for="category_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Category</label>
                    <select id="category_id" name="category_id" required class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
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
                    <label for="standard_unit" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Standard Trading Unit</label>
                    <select id="standard_unit" name="standard_unit" required class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
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
                    <label for="name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Crop Name (English)</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $crop->name) }}" required placeholder="e.g. Arecanut" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Crop Name (Kannada) -->
                <div>
                    <label for="name_kn" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Crop Name (Kannada)</label>
                    <input type="text" id="name_kn" name="name_kn" value="{{ old('name_kn', $crop->name_kn) }}" placeholder="e.g. ಅಡಿಕೆ" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-kannada focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name_kn')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">URL Slug (Auto-generated if empty)</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $crop->slug) }}" placeholder="e.g. arecanut" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('slug')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Scientific Name -->
                <div>
                    <label for="scientific_name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Scientific / Botanical Name</label>
                    <input type="text" id="scientific_name" name="scientific_name" value="{{ old('scientific_name', $crop->scientific_name) }}" placeholder="e.g. Areca catechu" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs italic focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('scientific_name')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Crop Description / Agronomy Notes</label>
                <textarea id="description" name="description" rows="2" placeholder="Cultivation notes, major producing regions..." class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">{{ old('description', $crop->description) }}</textarea>
            </div>

            <!-- Checkboxes -->
            <div class="flex items-center gap-6 pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_major" value="1" {{ old('is_major', $crop->is_major) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs font-semibold text-slate-300 select-none">★ Highlight as Major Karnataka Crop</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $crop->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs font-semibold text-slate-300 select-none">Active in market prices</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('admin.crops.index') }}" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition cursor-pointer">
                    {{ $isEdit ? 'Save Changes' : 'Register Crop' }}
                </button>
            </div>
        </form>
    </div>

    <!-- If editing: Varieties Section -->
    @if($isEdit)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">Commercial Varieties of {{ $crop->name }}</h3>
                    <p class="text-xs text-slate-400">Trade grades and cultivars traded in APMC mandis</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 text-xs font-semibold">
                    {{ $crop->varieties->count() }} Varieties
                </span>
            </div>

            <!-- Add Variety Form -->
            <form method="POST" action="{{ route('admin.varieties.store') }}" class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <input type="hidden" name="crop_id" value="{{ $crop->id }}">

                <div>
                    <input type="text" name="name" required placeholder="Variety Name (English)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <input type="text" name="name_kn" placeholder="Variety Name (Kannada)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-kannada focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <input type="text" name="slug" placeholder="Slug (optional)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-emerald-600 text-white font-bold text-xs rounded-lg transition cursor-pointer">
                        + Add Variety
                    </button>
                </div>
            </form>

            <!-- Varieties List -->
            @if($crop->varieties->isEmpty())
                <div class="text-center py-6 text-xs text-slate-400">No varieties registered for this crop.</div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($crop->varieties as $variety)
                        <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800/80 flex items-center justify-between gap-2">
                            <div>
                                <div class="font-bold text-xs text-white">{{ $variety->name }}</div>
                                @if($variety->name_kn)
                                    <div class="text-[11px] text-emerald-400 font-kannada">{{ $variety->name_kn }}</div>
                                @endif
                                <div class="text-[10px] text-slate-500 font-mono mt-0.5">slug: {{ $variety->slug }}</div>
                            </div>

                            <form method="POST" action="{{ route('admin.varieties.destroy', $variety) }}" onsubmit="return confirm('Remove variety {{ $variety->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 rounded text-slate-500 hover:text-rose-400 hover:bg-slate-800 transition" title="Remove Variety">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
