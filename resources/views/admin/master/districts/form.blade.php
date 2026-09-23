@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">

    <!-- Header & Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.districts.index') }}" class="hover:text-emerald-400">Districts</a>
                <span>/</span>
                <span class="text-white">{{ $isEdit ? 'Edit ' . $district->name : 'New District' }}</span>
            </div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">{{ $isEdit ? 'Edit District' : 'Register New District' }}</h2>
        </div>

        <a href="{{ route('admin.districts.index') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
            Back to List
        </a>
    </div>

    <!-- District Details Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ $isEdit ? route('admin.districts.update', $district) : route('admin.districts.store') }}" class="space-y-6">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- State Selector -->
                <div>
                    <label for="state_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">State</label>
                    <select id="state_id" name="state_id" required class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ old('state_id', $district->state_id) == $state->id ? 'selected' : '' }}>
                                {{ $state->name }} ({{ $state->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('state_id')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- District Code -->
                <div>
                    <label for="code" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">District Code</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $district->code) }}" placeholder="e.g. KA_SHI" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none uppercase font-mono">
                    @error('code')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name in English -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">District Name (English)</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $district->name) }}" required placeholder="e.g. Shivamogga" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name in Kannada -->
                <div>
                    <label for="name_kn" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">District Name (Kannada)</label>
                    <input type="text" id="name_kn" name="name_kn" value="{{ old('name_kn', $district->name_kn) }}" placeholder="e.g. ಶಿವಮೊಗ್ಗ" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-kannada focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name_kn')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Latitude -->
                <div>
                    <label for="latitude" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Latitude (Centroid)</label>
                    <input type="number" step="any" id="latitude" name="latitude" value="{{ old('latitude', $district->latitude) }}" placeholder="e.g. 13.9299" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('latitude')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Longitude -->
                <div>
                    <label for="longitude" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Longitude (Centroid)</label>
                    <input type="number" step="any" id="longitude" name="longitude" value="{{ old('longitude', $district->longitude) }}" placeholder="e.g. 75.5681" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('longitude')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Active Checkbox -->
            <div class="pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $district->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs font-semibold text-slate-300 select-none">Active in Karnataka farmer platform</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('admin.districts.index') }}" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition cursor-pointer">
                    {{ $isEdit ? 'Save Changes' : 'Create District' }}
                </button>
            </div>
        </form>
    </div>

    <!-- If editing: Taluks section -->
    @if($isEdit)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">Taluks in {{ $district->name }}</h3>
                    <p class="text-xs text-slate-400">Sub-district administrative subdivisions</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 text-xs font-semibold">
                    {{ $district->taluks->count() }} Taluks
                </span>
            </div>

            <!-- Add Taluk Form -->
            <form method="POST" action="{{ route('admin.taluks.store') }}" class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3">
                @csrf
                <input type="hidden" name="district_id" value="{{ $district->id }}">

                <div>
                    <input type="text" name="name" required placeholder="Taluk Name (English)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <input type="text" name="name_kn" placeholder="Taluk Name (Kannada)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-kannada focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <input type="number" step="any" name="latitude" placeholder="Latitude (optional)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-emerald-600 text-white font-bold text-xs rounded-lg transition cursor-pointer">
                        + Add Taluk
                    </button>
                </div>
            </form>

            <!-- Taluks List -->
            @if($district->taluks->isEmpty())
                <div class="text-center py-6 text-xs text-slate-400">No taluks added yet.</div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($district->taluks as $taluk)
                        <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800/80 flex items-center justify-between gap-2">
                            <div>
                                <div class="font-bold text-xs text-white">{{ $taluk->name }}</div>
                                @if($taluk->name_kn)
                                    <div class="text-[11px] text-emerald-400 font-kannada">{{ $taluk->name_kn }}</div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('admin.taluks.destroy', $taluk) }}" onsubmit="return confirm('Delete taluk {{ $taluk->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 rounded text-slate-500 hover:text-rose-400 hover:bg-slate-800 transition" title="Delete Taluk">
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
