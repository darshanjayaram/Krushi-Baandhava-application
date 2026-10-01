@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">
                {{ $isEdit ? 'Edit Government Scheme' : 'Add Government Scheme' }}
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Configure welfare benefits, banner slider placement, and official application links
            </p>
        </div>
        <a href="{{ route('admin.schemes.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition">
            ← Back to Schemes
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.schemes.update', $scheme) : route('admin.schemes.store') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <!-- TOP BANNER SLIDER HIGHLIGHT SETTING (Featured Card) -->
        <div class="p-4 rounded-xl bg-slate-950 border border-amber-500/30 space-y-3">
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $scheme->is_featured) ? 'checked' : '' }} class="rounded border-slate-700 text-amber-500 focus:ring-amber-400 bg-slate-900">
                    <span class="text-xs font-black text-amber-300">⭐ Feature in Top Banner Slider (ಮುಖಪುಟ ಬ್ಯಾನರ್ ಸ್ಲೈಡರ್)</span>
                </label>
                <span class="text-[11px] text-slate-400">Will rotate in top carousel on `/schemes`</span>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-300 mb-1">
                    Banner Highlight Badge (ಉದಾ: ₹6,000 / ವರ್ಷ, ಶೇ. 90 ಸಬ್ಸಿಡಿ, 100% ಉಚಿತ)
                </label>
                <input type="text" name="banner_tag" value="{{ old('banner_tag', $scheme->banner_tag) }}" placeholder="e.g. ₹6,000 / ವರ್ಷ or Up to 90% Subsidy" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs font-bold focus:ring-2 focus:ring-amber-500">
            </div>
        </div>

        <!-- Titles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Scheme Title (ಕನ್ನಡ / Kannada) *</label>
                <input type="text" name="title_kn" value="{{ old('title_kn', $scheme->title_kn) }}" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                @error('title_kn') <span class="text-rose-400 text-[11px]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Scheme Title (English) *</label>
                <input type="text" name="title" value="{{ old('title', $scheme->title) }}" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                @error('title') <span class="text-rose-400 text-[11px]">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Category, Emoji, Slug -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Category *</label>
                <select name="category" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="subsidy" {{ old('category', $scheme->category) === 'subsidy' ? 'selected' : '' }}>💰 ಸಬ್ಸಿಡಿ (Subsidies & Grants)</option>
                    <option value="machinery" {{ old('category', $scheme->category) === 'machinery' ? 'selected' : '' }}>🚜 ಯಂತ್ರೋಪಕರಣ (Farm Machinery)</option>
                    <option value="irrigation" {{ old('category', $scheme->category) === 'irrigation' ? 'selected' : '' }}>💧 ನೀರಾವರಿ (Micro Irrigation)</option>
                    <option value="insurance" {{ old('category', $scheme->category) === 'insurance' ? 'selected' : '' }}>🛡️ ವಿಮೆ (Crop Insurance)</option>
                    <option value="organic" {{ old('category', $scheme->category) === 'organic' ? 'selected' : '' }}>🌱 ಸಾವಯವ (Organic & Soil)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Emoji Icon</label>
                <input type="text" name="icon_emoji" value="{{ old('icon_emoji', $scheme->icon_emoji ?? '🌾') }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Display Order</label>
                <input type="number" name="display_order" value="{{ old('display_order', $scheme->display_order ?? 0) }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <!-- Authority & Benefit Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Sponsoring Agency / Department *</label>
                <input type="text" name="sponsoring_agency" value="{{ old('sponsoring_agency', $scheme->sponsoring_agency ?? 'ಕರ್ನಾಟಕ ಕೃಷಿ ಇಲಾಖೆ') }}" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Benefit Summary (ಕನ್ನಡ / Kannada)</label>
                <input type="text" name="benefit_amount_kn" value="{{ old('benefit_amount_kn', $scheme->benefit_amount_kn) }}" placeholder="e.g. ಪ.ಜಾ/ಪ.ಪಂ ರೈತರಿಗೆ ಶೇ. 90 ಮತ್ತು ಸಾಮಾನ್ಯರಿಗೆ ಶೇ. 50 ಸಬ್ಸಿಡಿ" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <!-- Eligibility Criteria -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Eligibility Criteria (ಕನ್ನಡ)</label>
                <textarea name="eligibility_criteria_kn" rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('eligibility_criteria_kn', $scheme->eligibility_criteria_kn) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Required Documents (ದಾಖಲೆಗಳು)</label>
                <textarea name="documents_required" rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('documents_required', $scheme->documents_required) }}</textarea>
            </div>
        </div>

        <!-- URLs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Direct Apply Link (Online Portal) *</label>
                <input type="url" name="apply_url" value="{{ old('apply_url', $scheme->apply_url) }}" placeholder="https://k-kisan.karnataka.gov.in/ or https://fruits.karnataka.gov.in/" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                <span class="text-[10px] text-slate-500">Directly opens in new tab when farmer clicks "Apply now"</span>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Official Department Portal</label>
                <input type="url" name="official_url" value="{{ old('official_url', $scheme->official_url) }}" placeholder="https://raitamitra.karnataka.gov.in/" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $scheme->is_active) ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-950">
                <span class="text-xs font-bold text-white">Active for Farmers (ಸಕ್ರಿಯ)</span>
            </label>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition">
                {{ $isEdit ? 'Update Scheme' : 'Save Scheme' }}
            </button>
        </div>
    </form>
</div>
@endsection
