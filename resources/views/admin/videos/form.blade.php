@extends('layouts.admin')

@section('content')
<div x-data="{
    youtubeUrl: '{{ old('youtube_url', $video->youtube_url) }}',
    youtubeId: '{{ old('youtube_video_id', $video->youtube_video_id) }}',
    title: '{{ old('title', $video->title) }}',
    titleKn: '{{ old('title_kn', $video->title_kn) }}',
    channelName: '{{ old('channel_name', $video->channel_name) }}',
    durationText: '{{ old('duration_text', $video->duration_text) }}',
    category: '{{ old('category', $video->category ?? 'cultivation') }}',
    growthStage: '{{ old('growth_stage', $video->growth_stage ?? 'general') }}',
    cropId: '{{ old('crop_id', $video->crop_id) }}',
    isActive: {{ old('is_active', $video->is_active ?? true) ? 'true' : 'false' }},
    isFeatured: {{ old('is_featured', $video->is_featured ?? false) ? 'true' : 'false' }},
    thumbnailUrl: '{{ $video->thumbnail_url ?? '' }}',
    isLoadingMetadata: false,
    fetchError: '',
    fetchSuccess: '',

    extractId(url) {
        if (!url) return '';
        const reg = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(reg);
        return (match && match[2].length === 11) ? match[2] : (url.length === 11 ? url : '');
    },

    async fetchMetadata() {
        if (!this.youtubeUrl.trim()) {
            this.fetchError = 'Please paste a YouTube URL first';
            return;
        }

        const id = this.extractId(this.youtubeUrl.trim());
        if (id) {
            this.youtubeId = id;
            this.thumbnailUrl = `https://img.youtube.com/vi/${id}/hqdefault.jpg`;
        }

        this.isLoadingMetadata = true;
        this.fetchError = '';
        this.fetchSuccess = '';

        try {
            const res = await fetch('{{ route('admin.videos.fetch-metadata') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ url: this.youtubeUrl.trim() })
            });

            const data = await res.json();
            if (data.success) {
                if (data.youtube_video_id) this.youtubeId = data.youtube_video_id;
                if (data.title && !this.title) this.title = data.title;
                if (data.channel_name && !this.channelName) this.channelName = data.channel_name;
                if (data.thumbnail_url) this.thumbnailUrl = data.thumbnail_url;
                this.fetchSuccess = 'Video details fetched successfully!';
            } else {
                this.fetchError = data.message || 'Could not fetch video details';
            }
        } catch (e) {
            this.fetchError = 'Network error fetching metadata';
        } finally {
            this.isLoadingMetadata = false;
        }
    }
}" class="space-y-6">

    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.videos.index') }}" class="hover:text-emerald-400 transition">Educational Videos</a>
                <span>/</span>
                <span class="text-slate-200">{{ $isEdit ? 'Edit Video' : 'Add New Video' }}</span>
            </div>
            <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>{{ $isEdit ? '✏️ Edit Curated Video' : '🎬 Add Educational YouTube Video' }}</span>
                <template x-if="isFeatured">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-400/20 text-amber-300 border border-amber-400/30">
                        ⭐ Featured
                    </span>
                </template>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Paste any YouTube tutorial or farmer interview link. Details will be automatically extracted.
            </p>
        </div>

        <a href="{{ route('admin.videos.index') }}" 
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition">
            <span>&larr; Back to Videos</span>
        </a>
    </div>

    <!-- Main Layout: 2 Columns on Desktop -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Form Column (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            <form method="POST" action="{{ $isEdit ? route('admin.videos.update', $video) : route('admin.videos.store') }}" 
                  class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-7 space-y-5 shadow-xl">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <!-- YouTube URL with Auto-Fetch Action -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-300">
                        YouTube URL or Share Link <span class="text-rose-400">*</span>
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1 min-w-0">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-rose-500 font-bold">
                                ▶
                            </span>
                            <input type="text" 
                                   name="youtube_url" 
                                   x-model="youtubeUrl"
                                   @paste="setTimeout(() => fetchMetadata(), 150)"
                                   placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..." 
                                   required 
                                   class="w-full pl-9 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                        </div>
                        <button type="button" 
                                @click="fetchMetadata()"
                                :disabled="isLoadingMetadata"
                                class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition flex items-center gap-1.5 shrink-0 disabled:opacity-50 cursor-pointer">
                            <svg x-show="isLoadingMetadata" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isLoadingMetadata ? 'Fetching...' : '⚡ Auto-Fetch'"></span>
                        </button>
                    </div>

                    <div x-show="fetchSuccess" x-text="fetchSuccess" class="text-xs font-bold text-emerald-400 pt-1" style="display: none;"></div>
                    <div x-show="fetchError" x-text="fetchError" class="text-xs font-bold text-rose-400 pt-1" style="display: none;"></div>
                    @error('youtube_url') <span class="text-rose-400 text-xs font-bold block pt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Video Titles (Dual Language) -->
                <div class="space-y-4 pt-2 border-t border-slate-800">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Video Title (English) <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               x-model="title" 
                               required 
                               placeholder="e.g. Complete Scientific Method of Arecanut Cultivation"
                               class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 transition">
                        @error('title') <span class="text-rose-400 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-300">
                                Video Title (ಕನ್ನಡ / Kannada) <span class="text-rose-400">*</span>
                            </label>
                            <span class="text-[10px] text-amber-300 font-bold">ರೈತರಿಗೆ ಸುಲಭವಾಗಿ ಅರ್ಥವಾಗುವಂತೆ</span>
                        </div>
                        <input type="text" 
                               name="title_kn" 
                               x-model="titleKn" 
                               required 
                               placeholder="ಉದಾಹರಣೆಗೆ: ಅಡಿಕೆ ಕೃಷಿಯ ಸಮಗ್ರ ವೈಜ್ಞಾನಿಕ ಬೇಸಾಯ ಪದ್ಧತಿ"
                               class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs sm:text-sm font-kannada focus:ring-2 focus:ring-emerald-500 transition">
                        @error('title_kn') <span class="text-rose-400 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Categorization & Growth Stage -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-800">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Associated Crop (ಬೆಳೆ)
                        </label>
                        <select name="crop_id" 
                                x-model="cropId"
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                            <option value="">🌾 General (Universal / All Crops)</option>
                            @foreach($crops as $c)
                                <option value="{{ $c->id }}" {{ old('crop_id', $video->crop_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->name_kn ?? $c->name }})
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[10px] text-slate-500 mt-1 block">Linking to a crop shows this video directly on that crop's price page!</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-300">
                                Category (ವರ್ಗ) <span class="text-rose-400">*</span>
                            </label>
                            <a href="{{ route('admin.videos.taxonomies.index', ['type' => 'category']) }}" 
                               target="_blank" 
                               class="text-[10px] text-emerald-400 hover:underline font-bold">
                                + Manage ↗
                            </a>
                        </div>
                        <select name="category" 
                                x-model="category"
                                required 
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                            @foreach($categories as $catKey => $catData)
                                <option value="{{ $catKey }}" {{ old('category', $video->category) === $catKey ? 'selected' : '' }}>
                                    {{ $catData['icon'] }} {{ $catData['name_kn'] ?? ($catData['name_en'] ?? ucfirst($catKey)) }} ({{ $catData['name_en'] ?? ucfirst($catKey) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-300">
                                Growth Stage (ಬೆಳೆ ಹಂತ)
                            </label>
                            <a href="{{ route('admin.videos.taxonomies.index', ['type' => 'growth_stage']) }}" 
                               target="_blank" 
                               class="text-[10px] text-amber-400 hover:underline font-bold">
                                + Manage ↗
                            </a>
                        </div>
                        <select name="growth_stage" 
                                x-model="growthStage"
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                            @foreach($growthStages as $stageKey => $stageData)
                                <option value="{{ $stageKey }}" {{ old('growth_stage', $video->growth_stage) === $stageKey ? 'selected' : '' }}>
                                    {{ $stageData['icon'] }} {{ $stageData['name_kn'] ?? ($stageData['name_en'] ?? ucfirst($stageKey)) }} ({{ $stageData['name_en'] ?? ucfirst($stageKey) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">
                            Audio Language (ಭಾಷೆ)
                        </label>
                        <select name="language" 
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                            <option value="kn" {{ old('language', $video->language ?? 'kn') === 'kn' ? 'selected' : '' }}>ಕನ್ನಡ (Kannada Audio)</option>
                            <option value="en" {{ old('language', $video->language) === 'en' ? 'selected' : '' }}>English (ಇಂಗ್ಲಿಷ್)</option>
                            <option value="hi" {{ old('language', $video->language) === 'hi' ? 'selected' : '' }}>Hindi (ಹಿಂದಿ)</option>
                        </select>
                    </div>
                </div>

                <!-- Video Metadata: Channel, Duration, Display Order -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-800">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Channel Name</label>
                        <input type="text" 
                               name="channel_name" 
                               x-model="channelName" 
                               placeholder="e.g. UAS Dharwad, Krishi Darshana" 
                               class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Duration (e.g. 14:30)</label>
                        <input type="text" 
                               name="duration_text" 
                               x-model="durationText" 
                               placeholder="12:45" 
                               class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Display Priority (Order)</label>
                        <input type="number" 
                               name="display_order" 
                               value="{{ old('display_order', $video->display_order ?? 0) }}" 
                               class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Status & Placement Toggles -->
                <div class="pt-4 border-t border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-3 rounded-2xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1" 
                               x-model="isActive"
                               class="mt-0.5 rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-900 w-4 h-4">
                        <div>
                            <span class="text-xs font-bold text-white block">Active (ಸಕ್ರಿಯ)</span>
                            <span class="text-[11px] text-slate-400 block">Make this video visible to farmers</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 cursor-pointer hover:border-amber-400 transition">
                        <input type="checkbox" 
                               name="is_featured" 
                               value="1" 
                               x-model="isFeatured"
                               class="mt-0.5 rounded border-amber-600 text-amber-500 focus:ring-amber-400 bg-slate-900 w-4 h-4">
                        <div>
                            <span class="text-xs font-bold text-amber-300 block flex items-center gap-1">
                                <span>⭐ Featured Spotlight</span>
                            </span>
                            <span class="text-[11px] text-amber-200/70 block">Pin to top of Video Hub & Spotlight banners</span>
                        </div>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                    <a href="{{ route('admin.videos.index') }}" class="text-xs font-bold text-slate-400 hover:text-white transition">
                        Cancel
                    </a>

                    <button type="submit" 
                            class="px-7 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs sm:text-sm shadow-lg hover:shadow-xl transition active:scale-95 cursor-pointer">
                        {{ $isEdit ? '💾 Update Video' : '🚀 Publish Video to Farmers' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Live Preview Column (5 Cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="sticky top-6 space-y-4">
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                            <span>📱 Live Farmer Card Preview</span>
                        </h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                            Real-time
                        </span>
                    </div>

                    <!-- Live Render of the Video Card -->
                    <div class="bg-white rounded-2xl border-2 border-stone-200 overflow-hidden shadow-md flex flex-col justify-between transition text-stone-900">
                        <!-- Thumbnail Area -->
                        <div class="relative aspect-video bg-stone-950 overflow-hidden group">
                            <template x-if="thumbnailUrl">
                                <img :src="thumbnailUrl" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!thumbnailUrl">
                                <div class="w-full h-full flex flex-col items-center justify-center text-stone-500 p-4 text-center">
                                    <span class="text-3xl mb-1">🎬</span>
                                    <span class="text-xs font-bold">Thumbnail will render here</span>
                                </div>
                            </template>

                            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent"></div>

                            <!-- Play Icon Overlay -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-red-600 text-white flex items-center justify-center shadow-lg transform group-hover:scale-110 transition">
                                    <span class="ml-1 font-black text-sm">▶</span>
                                </div>
                            </div>

                            <!-- Duration Badge -->
                            <span x-show="durationText" x-text="durationText" class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-black/80 text-white text-[10px] font-bold"></span>

                            <!-- Category Badge -->
                            <span class="absolute top-2 left-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-800 text-white shadow-xs">
                                <span x-text="category.toUpperCase()"></span>
                            </span>

                            <!-- Featured Ribbon -->
                            <span x-show="isFeatured" class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-400 text-stone-950 shadow-xs flex items-center gap-1">
                                <span>⭐</span>
                                <span>Featured</span>
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="p-3.5 space-y-2">
                            <!-- Kannada Title Priority -->
                            <h4 class="font-extrabold text-sm text-stone-900 font-kannada line-clamp-2 leading-snug"
                                x-text="titleKn || 'ಕನ್ನಡ ವಿಡಿಯೋ ಶೀರ್ಷಿಕೆ ಇಲ್ಲಿ ಕಾಣಿಸುತ್ತದೆ...'"></h4>

                            <!-- English Title Subtext -->
                            <p class="text-xs text-stone-500 font-medium line-clamp-1"
                               x-text="title || 'English title will appear here...'"></p>

                            <!-- Channel & Growth Stage Tags -->
                            <div class="pt-2 border-t border-stone-100 flex items-center justify-between text-[11px] text-stone-600 flex-wrap gap-1">
                                <span class="font-bold flex items-center gap-1 truncate max-w-[150px]">
                                    <span>📺</span>
                                    <span x-text="channelName || 'YouTube Channel'"></span>
                                </span>

                                <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-700 font-bold text-[10px]">
                                    <span x-text="growthStage"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Embedded Player (If YouTube ID is available) -->
                    <template x-if="youtubeId">
                        <div class="pt-2 border-t border-slate-800 space-y-2">
                            <span class="text-[11px] font-bold text-slate-400 block">🎥 Test Playback (In-Page):</span>
                            <div class="aspect-video rounded-xl overflow-hidden border border-slate-800 bg-black">
                                <iframe :src="`https://www.youtube.com/embed/${youtubeId}?rel=0`"
                                        class="w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
