@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">
                {{ $isEdit ? 'Edit Educational Video' : 'Add Educational YouTube Video' }}
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Embed agricultural guides, pest management tutorials, and modern farming techniques</p>
        </div>
        <a href="{{ route('admin.videos.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white">
            ← Back to Videos
        </a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.videos.update', $video) : route('admin.videos.store') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-sm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">YouTube URL / Share Link *</label>
            <input type="text" name="youtube_url" value="{{ old('youtube_url', $video->youtube_url) }}" placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..." required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            <span class="text-[11px] text-slate-400">The video ID and thumbnail will be automatically detected.</span>
            @error('youtube_url') <span class="text-rose-400 text-[11px] block mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Video Title (English) *</label>
                <input type="text" name="title" value="{{ old('title', $video->title) }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Video Title (ಕನ್ನಡ / Kannada) *</label>
                <input type="text" name="title_kn" value="{{ old('title_kn', $video->title_kn) }}" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Associated Crop (Optional)</label>
                <select name="crop_id" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">None (General)</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ old('crop_id', $video->crop_id) == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->name_kn ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Category *</label>
                <select name="category" required class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="cultivation" {{ old('category', $video->category) === 'cultivation' ? 'selected' : '' }}>ಬೇಸಾಯ (Cultivation)</option>
                    <option value="pest_control" {{ old('category', $video->category) === 'pest_control' ? 'selected' : '' }}>ಕೀಟ ನಿಯಂತ್ರಣ (Pest Control)</option>
                    <option value="organic" {{ old('category', $video->category) === 'organic' ? 'selected' : '' }}>ಸಾವಯವ (Organic)</option>
                    <option value="machinery" {{ old('category', $video->category) === 'machinery' ? 'selected' : '' }}>ಯಂತ್ರೋಪಕರಣ (Machinery)</option>
                    <option value="success_story" {{ old('category', $video->category) === 'success_story' ? 'selected' : '' }}>ರೈತರ ಯಶೋಗಾಥೆ (Success Story)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Display Order</label>
                <input type="number" name="display_order" value="{{ old('display_order', $video->display_order ?? 0) }}" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Channel Name</label>
                <input type="text" name="channel_name" value="{{ old('channel_name', $video->channel_name) }}" placeholder="e.g. Krishi Darshana" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Duration (e.g. 14:30)</label>
                <input type="text" name="duration_text" value="{{ old('duration_text', $video->duration_text) }}" placeholder="12:45" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $video->is_active) ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-950">
                <span class="text-xs font-bold text-white">Active (ಪ್ರದರ್ಶಿಸಿ)</span>
            </label>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition">
                {{ $isEdit ? 'Update Video' : 'Save Video' }}
            </button>
        </div>
    </form>
</div>
@endsection
