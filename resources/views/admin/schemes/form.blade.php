@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">
                {{ $isEdit ? 'Edit Government Scheme' : 'Add Government Scheme' }}
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Configure welfare benefits, eligibility rules, and official application links</p>
        </div>
        <a href="{{ route('admin.schemes.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white">
            ← Back to Schemes
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.schemes.update', $scheme) : route('admin.schemes.store') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Scheme Title (English) *</label>
                <input type="text" name="title" value="{{ old('title', $scheme->title) }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                @error('title') <span class="text-rose-400 text-[11px]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Scheme Title (ಕನ್ನಡ / Kannada)</label>
                <input type="text" name="title_kn" value="{{ old('title_kn', $scheme->title_kn) }}" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $scheme->slug) }}" placeholder="auto-generated-if-empty" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Category *</label>
                <select name="category" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="subsidy" {{ old('category', $scheme->category) === 'subsidy' ? 'selected' : '' }}>ಸಬ್ಸಿಡಿ (Subsidy)</option>
                    <option value="machinery" {{ old('category', $scheme->category) === 'machinery' ? 'selected' : '' }}>ಯಂತ್ರೋಪಕರಣ (Machinery)</option>
                    <option value="irrigation" {{ old('category', $scheme->category) === 'irrigation' ? 'selected' : '' }}>ನೀರಾವರಿ (Irrigation)</option>
                    <option value="insurance" {{ old('category', $scheme->category) === 'insurance' ? 'selected' : '' }}>ವಿಮೆ (Insurance)</option>
                    <option value="organic" {{ old('category', $scheme->category) === 'organic' ? 'selected' : '' }}>ಸಾವಯವ (Organic)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Emoji Icon</label>
                <input type="text" name="icon_emoji" value="{{ old('icon_emoji', $scheme->icon_emoji ?? '🌾') }}" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Sponsoring Agency *</label>
                <input type="text" name="sponsoring_agency" value="{{ old('sponsoring_agency', $scheme->sponsoring_agency ?? 'ಕರ್ನಾಟಕ ಕೃಷಿ ಇಲಾಖೆ') }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Benefit Details (ಕನ್ನಡ)</label>
                <input type="text" name="benefit_amount_kn" value="{{ old('benefit_amount_kn', $scheme->benefit_amount_kn) }}" placeholder="ಉದಾ: ಶೇ. 90 ಸಬ್ಸಿಡಿ" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Eligibility Criteria (ಕನ್ನಡ)</label>
                <textarea name="eligibility_criteria_kn" rows="3" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('eligibility_criteria_kn', $scheme->eligibility_criteria_kn) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Required Documents (ದಾಖಲೆಗಳು)</label>
                <textarea name="documents_required" rows="3" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('documents_required', $scheme->documents_required) }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Official Portal URL</label>
                <input type="url" name="official_url" value="{{ old('official_url', $scheme->official_url) }}" placeholder="https://..." class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Direct Apply Link</label>
                <input type="url" name="apply_url" value="{{ old('apply_url', $scheme->apply_url) }}" placeholder="https://..." class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $scheme->is_active) ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-950">
                <span class="text-xs font-bold text-white">Active (ಸಕ್ರಿಯ)</span>
            </label>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition">
                {{ $isEdit ? 'Update Scheme' : 'Save Scheme' }}
            </button>
        </div>
    </form>
</div>
@endsection
