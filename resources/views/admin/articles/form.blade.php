@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">
                {{ $isEdit ? 'Edit Farming Guide' : 'Write New Farming Guide' }}
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Author expert cultivation methods, disease prevention, and agronomy advice</p>
        </div>
        <a href="{{ route('admin.articles.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white">
            ← Back to Guides
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.articles.update', $article) : route('admin.articles.store') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Title (English) *</label>
                <input type="text" name="title" value="{{ old('title', $article->title) }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                @error('title') <span class="text-rose-400 text-[11px]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Title (ಕನ್ನಡ / Kannada) *</label>
                <input type="text" name="title_kn" value="{{ old('title_kn', $article->title_kn) }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $article->slug) }}" placeholder="auto-generated-if-empty" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Associated Crop</label>
                <select name="crop_id" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">General (No crop)</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ old('crop_id', $article->crop_id) == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->name_kn ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Category *</label>
                <select name="category" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="cultivation" {{ old('category', $article->category) === 'cultivation' ? 'selected' : '' }}>ಬೇಸಾಯ ಕ್ರಮಗಳು (Cultivation)</option>
                    <option value="pest_control" {{ old('category', $article->category) === 'pest_control' ? 'selected' : '' }}>ಕೀಟ & ರೋಗ ಬಾಧೆ (Pest & Disease)</option>
                    <option value="soil_fertilizer" {{ old('category', $article->category) === 'soil_fertilizer' ? 'selected' : '' }}>ಮಣ್ಣು & ರಸಗೊಬ್ಬರ (Soil & Fertilizer)</option>
                    <option value="harvest_storage" {{ old('category', $article->category) === 'harvest_storage' ? 'selected' : '' }}>ಕೊಯ್ಲು & ಸಂಗ್ರಹಣೆ (Harvest & Storage)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Author Name</label>
                <input type="text" name="author_name" value="{{ old('author_name', $article->author_name ?? 'ಕೃಷಿ ತಜ್ಞರು') }}" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Publish Date</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Summary (English)</label>
                <textarea name="summary" rows="2" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('summary', $article->summary) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Summary (ಕನ್ನಡ / Kannada)</label>
                <textarea name="summary_kn" rows="2" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('summary_kn', $article->summary_kn) }}</textarea>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Detailed Guide Content (ಕನ್ನಡ) *</label>
            <textarea name="body_kn" rows="6" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('body_kn', $article->body_kn) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Detailed Guide Content (English)</label>
            <textarea name="body" rows="4" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">{{ old('body', $article->body) }}</textarea>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $article->is_published) ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-950">
                <span class="text-xs font-bold text-white">Publish Immediately (ಪ್ರಕಟಿಸಿ)</span>
            </label>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition">
                {{ $isEdit ? 'Update Guide' : 'Publish Guide' }}
            </button>
        </div>
    </form>
</div>
@endsection
