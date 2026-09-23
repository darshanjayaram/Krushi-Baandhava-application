@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">
                {{ $isEdit ? 'Edit Agricultural News Bulletin' : 'Publish News Bulletin' }}
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Publish market updates, policy changes, and breaking farm alerts</p>
        </div>
        <a href="{{ route('admin.news.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white">
            ← Back to News
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.news.update', $news) : route('admin.news.store') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Headline (English) *</label>
                <input type="text" name="headline" value="{{ old('headline', $news->headline) }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                @error('headline') <span class="text-rose-400 text-[11px]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Headline (ಕನ್ನಡ / Kannada) *</label>
                <input type="text" name="headline_kn" value="{{ old('headline_kn', $news->headline_kn) }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $news->slug) }}" placeholder="auto-generated-if-empty" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Priority *</label>
                <select name="priority" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="breaking" {{ old('priority', $news->priority) === 'breaking' ? 'selected' : '' }}>ತಾಜಾ ಸುದ್ದಿ (Breaking)</option>
                    <option value="important" {{ old('priority', $news->priority) === 'important' ? 'selected' : '' }}>ಮುಖ್ಯ ಸುದ್ದಿ (Important)</option>
                    <option value="standard" {{ old('priority', $news->priority) === 'standard' ? 'selected' : '' }}>ಸಾಮಾನ್ಯ (Standard)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Publish Timestamp</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $news->published_at ? $news->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Source Name *</label>
                <input type="text" name="source_name" value="{{ old('source_name', $news->source_name ?? 'ಕೃಷಿ ಇಲಾಖೆ') }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Source Link / Circular URL</label>
                <input type="url" name="source_url" value="{{ old('source_url', $news->source_url) }}" placeholder="https://..." class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Summary (English)</label>
                <textarea name="summary" rows="2" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('summary', $news->summary) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Summary (ಕನ್ನಡ / Kannada)</label>
                <textarea name="summary_kn" rows="2" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('summary_kn', $news->summary_kn) }}</textarea>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Full News Body (ಕನ್ನಡ)</label>
            <textarea name="content_kn" rows="4" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('content_kn', $news->content_kn) }}</textarea>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $news->is_active) ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-950">
                <span class="text-xs font-bold text-white">Active (ಪ್ರಕಟಿತ)</span>
            </label>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition">
                {{ $isEdit ? 'Update News' : 'Publish News' }}
            </button>
        </div>
    </form>
</div>
@endsection
