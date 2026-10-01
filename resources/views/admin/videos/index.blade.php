@extends('layouts.admin')

@section('content')
<div x-data="{
    previewModalOpen: false,
    previewVideoId: null,
    previewTitle: '',
    settingsModalOpen: false,
    selectedIds: [],
    selectAll: false,

    openPreview(id, title) {
        this.previewVideoId = id;
        this.previewTitle = title;
        this.previewModalOpen = true;
    },

    closePreview() {
        this.previewModalOpen = false;
        this.previewVideoId = null;
    },

    toggleSelectAll(checked) {
        this.selectAll = checked;
        if (checked) {
            const checkboxes = document.querySelectorAll('.video-checkbox');
            this.selectedIds = Array.from(checkboxes).map(cb => cb.value);
        } else {
            this.selectedIds = [];
        }
    },

    async toggleActive(id, btnEl) {
        try {
            const res = await fetch(`{{ url('admin/videos') }}/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                btnEl.classList.toggle('bg-emerald-950', data.is_active);
                btnEl.classList.toggle('text-emerald-300', data.is_active);
                btnEl.classList.toggle('border-emerald-800', data.is_active);
                btnEl.classList.toggle('bg-rose-950', !data.is_active);
                btnEl.classList.toggle('text-rose-300', !data.is_active);
                btnEl.classList.toggle('border-rose-800', !data.is_active);
                btnEl.innerText = data.is_active ? 'Active' : 'Hidden';
            }
        } catch(e) {
            console.error('Toggle failed', e);
        }
    },

    async toggleFeatured(id, starEl) {
        try {
            const res = await fetch(`{{ url('admin/videos') }}/${id}/toggle-featured`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                starEl.classList.toggle('text-amber-400', data.is_featured);
                starEl.classList.toggle('text-slate-600', !data.is_featured);
                starEl.title = data.is_featured ? 'Featured (Click to unset)' : 'Click to Feature';
            }
        } catch(e) {
            console.error('Toggle featured failed', e);
        }
    }
}" class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>🎬 Educational Video Hub</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    {{ $stats['total'] }} Videos
                </span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Curate YouTube agricultural tutorials, growth-stage farming guides, and progressive farmer success stories.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" 
                    @click="settingsModalOpen = true" 
                    class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs shadow-sm transition active:scale-95 cursor-pointer"
                    title="Configure Max Videos per Page for Farmer Hub">
                <span>⚙️</span>
                <span>Per Page: <strong class="text-emerald-400">{{ $farmerPerPage ?? 12 }}</strong></span>
            </button>

            <a href="{{ route('admin.videos.taxonomies.index') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs shadow-sm transition active:scale-95 cursor-pointer">
                <span>🏷️</span>
                <span>Categories & Stages</span>
            </a>

            <a href="{{ route('admin.videos.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs shadow-lg transition active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add YouTube Video</span>
            </a>
        </div>
    </div>

    <!-- Quick Metrics KPI Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Curated</span>
            <div class="text-xl sm:text-2xl font-black text-white mt-0.5">{{ $stats['total'] }}</div>
            <span class="text-[10px] text-slate-500 font-medium">In library</span>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-sm">
            <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Active Online</span>
            <div class="text-xl sm:text-2xl font-black text-emerald-400 mt-0.5">{{ $stats['active'] }}</div>
            <span class="text-[10px] text-slate-500 font-medium">Visible to farmers</span>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-sm">
            <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block">Featured Spotlight</span>
            <div class="text-xl sm:text-2xl font-black text-amber-300 mt-0.5">{{ $stats['featured'] }}</div>
            <span class="text-[10px] text-slate-500 font-medium">Pinned at top</span>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-sm">
            <span class="text-[10px] font-bold text-teal-400 uppercase tracking-wider block">Crops Covered</span>
            <div class="text-xl sm:text-2xl font-black text-teal-300 mt-0.5">{{ $stats['crops_covered'] }}</div>
            <span class="text-[10px] text-slate-500 font-medium">With linked guides</span>
        </div>
    </div>

    <!-- Filters, Search & Bulk Actions Toolbar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 sm:p-5 shadow-sm space-y-3">
        <form method="GET" action="{{ route('admin.videos.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
            <!-- Search input -->
            <div class="sm:col-span-4">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search title, channel name, topic..." 
                       class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <!-- Crop filter -->
            <div class="sm:col-span-2">
                <select name="crop_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Crops</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ (string)$cropId === (string)$c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->name_kn ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Category filter -->
            <div class="sm:col-span-2">
                <select name="category" class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $catKey => $catData)
                        <option value="{{ $catKey }}" {{ $category === $catKey ? 'selected' : '' }}>
                            {{ $catData['icon'] }} {{ $catData['name_kn'] ?? ($catData['name_en'] ?? ucfirst($catKey)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Growth Stage filter -->
            <div class="sm:col-span-2">
                <select name="growth_stage" class="w-full px-3 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Growth Stages</option>
                    @foreach($growthStages as $stageKey => $stageData)
                        <option value="{{ $stageKey }}" {{ $growthStage === $stageKey ? 'selected' : '' }}>
                            {{ $stageData['icon'] }} {{ $stageData['name_kn'] ?? ($stageData['name_en'] ?? ucfirst($stageKey)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status filter -->
            <div class="sm:col-span-2 flex gap-2">
                <select name="status" class="w-full px-2.5 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Status</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="hidden" {{ $status === 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition cursor-pointer">
                    Filter
                </button>
            </div>
        </form>

        <!-- Bulk Action Form -->
        <form method="POST" action="{{ route('admin.videos.bulk') }}" class="flex items-center justify-between pt-3 border-t border-slate-800 text-xs">
            @csrf
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer text-slate-300 font-semibold select-none">
                    <input type="checkbox" 
                           @change="toggleSelectAll($event.target.checked)"
                           class="rounded border-slate-700 bg-slate-950 text-emerald-600 focus:ring-emerald-500">
                    <span>Select All on Page</span>
                </label>

                <template x-if="selectedIds.length > 0">
                    <span class="text-emerald-400 font-bold" x-text="`(${selectedIds.length} selected)`"></span>
                </template>
            </div>

            <div class="flex items-center gap-2" x-show="selectedIds.length > 0" style="display: none;">
                <!-- Hidden inputs for selected IDs -->
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="selected_ids[]" :value="id">
                </template>

                <select name="bulk_action" required class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs">
                    <option value="">Choose Bulk Action...</option>
                    <option value="activate">Activate Selected</option>
                    <option value="deactivate">Deactivate Selected</option>
                    <option value="feature">Set as Featured ⭐</option>
                    <option value="unfeature">Remove from Featured</option>
                    <option value="delete">Delete Selected</option>
                </select>

                <button type="submit" 
                        onclick="return confirm('Execute bulk action on selected videos?')"
                        class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl transition cursor-pointer">
                    Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Videos Grid (3 Columns on Large Screens) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($videos as $v)
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-md flex flex-col justify-between hover:border-slate-700 transition group relative">
                
                <div>
                    <!-- Thumbnail with Interactive Play Button -->
                    <div class="relative aspect-video bg-slate-950 overflow-hidden cursor-pointer"
                         @click="openPreview('{{ $v->youtube_video_id }}', '{{ addslashes($v->title_kn ?? $v->title) }}')">
                        <img src="{{ $v->thumbnail_url }}" alt="{{ $v->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent"></div>

                        <!-- Centered Play Icon -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-90 group-hover:opacity-100 transition">
                            <div class="w-11 h-11 rounded-full bg-red-600/90 hover:bg-red-600 text-white flex items-center justify-center shadow-lg transform group-hover:scale-110 transition">
                                <span class="ml-0.5 text-xs font-black">▶</span>
                            </div>
                        </div>

                        <!-- Top Left: Checkbox & Category Badge -->
                        <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 z-10" @click.stop>
                            <input type="checkbox" 
                                   value="{{ $v->id }}" 
                                   x-model="selectedIds"
                                   class="video-checkbox rounded border-slate-600 bg-black/60 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                            
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-950/90 text-emerald-300 border border-emerald-700/60 shadow-xs backdrop-blur-xs">
                                {{ $v->getCategoryName('kn') }}
                            </span>
                        </div>

                        <!-- Top Right: Featured Star (Clickable AJAX) -->
                        <div class="absolute top-2.5 right-2.5 z-10" @click.stop>
                            <button type="button" 
                                    @click="toggleFeatured({{ $v->id }}, $el)"
                                    class="w-7 h-7 rounded-full bg-black/70 hover:bg-black text-center flex items-center justify-center transition cursor-pointer shadow-md {{ $v->is_featured ? 'text-amber-400' : 'text-slate-600 hover:text-amber-300' }}"
                                    title="{{ $v->is_featured ? 'Featured (Click to unset)' : 'Click to Feature' }}">
                                ★
                            </button>
                        </div>

                        <!-- Bottom Meta Badges (Duration & Growth Stage) -->
                        <div class="absolute bottom-2.5 inset-x-2.5 flex items-center justify-between text-[10px] font-bold text-white z-10">
                            @if($v->growth_stage && $v->growth_stage !== 'general')
                                <span class="px-2 py-0.5 rounded-md bg-stone-900/90 border border-stone-700 text-stone-200 backdrop-blur-xs">
                                    {{ $v->getGrowthStageName('kn') }}
                                </span>
                            @else
                                <span></span>
                            @endif

                            @if($v->duration_text)
                                <span class="px-2 py-0.5 rounded-md bg-black/90 text-white font-mono shadow-xs">
                                    {{ $v->duration_text }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 space-y-2">
                        <!-- Priority Kannada Title -->
                        <h4 class="font-bold text-white text-sm line-clamp-2 leading-snug font-kannada">
                            {{ $v->title_kn ?: $v->title }}
                        </h4>

                        @if($v->title_kn && $v->title && $v->title_kn !== $v->title)
                            <p class="text-xs text-slate-400 line-clamp-1 font-sans">
                                {{ $v->title }}
                            </p>
                        @endif

                        <!-- Channel & Crop Info -->
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                            <span class="truncate max-w-[160px] font-medium">📺 {{ $v->channel_name ?: 'Agri Channel' }}</span>
                            @if($v->crop)
                                <span class="text-emerald-400 font-bold bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-900/50">
                                    🌾 {{ $v->crop->name }}
                                </span>
                            @else
                                <span class="text-slate-500 font-medium">General</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card Footer Controls -->
                <div class="p-4 pt-0 border-t border-slate-800/80 flex items-center justify-between text-xs mt-auto">
                    <!-- Instant AJAX Active Toggle -->
                    <button type="button" 
                            @click="toggleActive({{ $v->id }}, $el)"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wider transition cursor-pointer border {{ $v->is_active ? 'bg-emerald-950 text-emerald-300 border-emerald-800' : 'bg-rose-950 text-rose-300 border-rose-800' }}">
                        {{ $v->is_active ? 'Active' : 'Hidden' }}
                    </button>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 font-semibold">
                        <button type="button" 
                                @click="openPreview('{{ $v->youtube_video_id }}', '{{ addslashes($v->title_kn ?? $v->title) }}')"
                                class="text-slate-400 hover:text-white transition flex items-center gap-1 cursor-pointer">
                            <span>▶</span>
                            <span>Watch</span>
                        </button>

                        <a href="{{ route('admin.videos.edit', $v) }}" 
                           class="text-emerald-400 hover:text-emerald-300 transition">
                            Edit
                        </a>

                        <form method="POST" action="{{ route('admin.videos.destroy', $v) }}" onsubmit="return confirm('Delete this video?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-400 hover:text-rose-300 transition cursor-pointer">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full bg-slate-900 border border-slate-800 rounded-3xl p-12 text-center space-y-3">
                <span class="text-4xl block">🎬</span>
                <h3 class="text-base font-extrabold text-white">No Curated Videos Found</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    No videos match your active filter criteria. Click below to add a new educational YouTube guide.
                </p>
                <div class="pt-2">
                    <a href="{{ route('admin.videos.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition">
                        <span>+ Add First Video</span>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($videos->hasPages())
        <div class="pt-2">
            {{ $videos->links() }}
        </div>
    @endif

    <!-- In-Page Interactive Video Playback Modal (Alpine.js) -->
    <div x-show="previewModalOpen" 
         x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         style="display: none;"
         @keydown.escape.window="closePreview()">
        
        <div class="relative w-full max-w-3xl bg-slate-950 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-3 p-4 sm:p-5"
             @click.away="closePreview()">
            
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white font-kannada truncate pr-4" x-text="previewTitle"></h3>
                <button type="button" 
                        @click="closePreview()"
                        class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-xs font-black transition cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- YouTube Iframe Embed -->
            <div class="aspect-video w-full rounded-2xl overflow-hidden bg-black shadow-inner">
                <template x-if="previewModalOpen && previewVideoId">
                    <iframe :src="`https://www.youtube.com/embed/${previewVideoId}?autoplay=1&rel=0`" 
                            class="w-full h-full" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </template>
            </div>
        </div>
    </div>

    <!-- Farmer Hub Settings Modal -->
    <div x-show="settingsModalOpen" 
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @keydown.escape.window="settingsModalOpen = false">
        <div class="bg-slate-900 border border-slate-700/80 rounded-3xl overflow-hidden shadow-2xl max-w-md w-full"
             @click.away="settingsModalOpen = false">
            
            <div class="flex items-center justify-between p-4 bg-slate-950 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚙️</span>
                    <h3 class="text-sm font-bold text-white">Farmer Video Hub Settings</h3>
                </div>
                <button type="button" @click="settingsModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 text-base font-bold">
                    ✕
                </button>
            </div>

            <form action="{{ route('admin.videos.settings') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-200 mb-1">
                        Max Videos Displayed Per Page (ಪ್ರತಿ ಪುಟದ ಗರಿಷ್ಠ ವಿಡಿಯೋಗಳು)
                    </label>
                    <p class="text-[11px] text-slate-400 mb-3">
                        Choose how many curated video cards will be rendered per page on the public Farmer Video Hub (<code class="text-emerald-400 font-mono">/farmer/videos</code>) before pagination controls appear.
                    </p>

                    <div class="grid grid-cols-4 gap-2">
                        @foreach([6, 8, 9, 12, 15, 18, 24, 30] as $option)
                            <label class="relative flex items-center justify-center p-2.5 rounded-xl border cursor-pointer text-xs font-bold transition text-center
                                          {{ ($farmerPerPage ?? 12) == $option ? 'bg-emerald-950 border-emerald-500 text-emerald-300 ring-2 ring-emerald-500/30' : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700 hover:bg-slate-800' }}">
                                <input type="radio" name="per_page" value="{{ $option }}" {{ ($farmerPerPage ?? 12) == $option ? 'checked' : '' }} class="sr-only">
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="settingsModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md transition">
                        Save Setting
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
