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
                            <option value="{{ $district->id }}" {{ old('district_id', $market->district_id) == $district->id ? 'selected' : '' }}>
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

                <!-- APMC Code -->
                <div>
                    <label for="code" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Official APMC Code</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $market->code) }}" placeholder="e.g. KA_APMC_SHI" class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:outline-none">
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
                        <option value="Private" {{ old('market_type', $market->market_type) === 'Private' ? 'selected' : '' }}>Private Mandi / Cooperative</option>
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

</div>
@endsection
