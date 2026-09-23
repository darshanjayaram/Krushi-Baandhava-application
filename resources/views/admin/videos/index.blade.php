@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">Curated Educational Video Hub</h2>
            <p class="text-xs text-slate-400 mt-0.5">Manage YouTube agricultural tutorials, pest control guides, and farmer success stories</p>
        </div>

        <a href="{{ route('admin.videos.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add YouTube Video</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.videos.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="relative sm:col-span-2">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search video title, channel, or topic..." 
                       class="w-full pl-4 pr-4 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <div>
                <select name="crop_id" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Crops</option>
                    @foreach($crops as $c)
                        <option value="{{ $c->id }}" {{ $cropId == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->name_kn ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <select name="category" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Categories</option>
                    <option value="cultivation" {{ $category === 'cultivation' ? 'selected' : '' }}>ಬೇಸಾಯ (Cultivation)</option>
                    <option value="pest_control" {{ $category === 'pest_control' ? 'selected' : '' }}>ಕೀಟ ನಿಯಂತ್ರಣ (Pest Control)</option>
                    <option value="organic" {{ $category === 'organic' ? 'selected' : '' }}>ಸಾವಯವ (Organic)</option>
                    <option value="machinery" {{ $category === 'machinery' ? 'selected' : '' }}>ಯಂತ್ರೋಪಕರಣ (Machinery)</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Videos Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($videos as $v)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm flex flex-col justify-between">
                <div>
                    <!-- Thumbnail preview with duration badge -->
                    <div class="relative aspect-video bg-slate-950 overflow-hidden">
                        <img src="{{ $v->thumbnail_url }}" alt="{{ $v->title }}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
                        @if($v->duration_text)
                            <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md bg-black/80 text-white text-[10px] font-bold">
                                {{ $v->duration_text }}
                            </span>
                        @endif
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-900/90 text-emerald-200 border border-emerald-700/40">
                            {{ $v->category }}
                        </span>
                    </div>

                    <div class="p-4 space-y-2">
                        <h4 class="font-bold text-white text-sm line-clamp-2">{{ $v->title_kn ?? $v->title }}</h4>
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>📺 {{ $v->channel_name ?? 'Agri Channel' }}</span>
                            @if($v->crop)
                                <span class="text-emerald-400 font-semibold">{{ $v->crop->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-4 pt-0 border-t border-slate-800/80 flex items-center justify-between text-xs">
                    <form method="POST" action="{{ route('admin.videos.toggle', $v) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $v->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' }}">
                            {{ $v->is_active ? 'Active' : 'Hidden' }}
                        </button>
                    </form>

                    <div class="flex items-center gap-2">
                        <a href="{{ $v->youtube_url }}" target="_blank" class="text-slate-400 hover:text-white">Watch</a>
                        <a href="{{ route('admin.videos.edit', $v) }}" class="text-slate-400 hover:text-emerald-400">Edit</a>
                        <form method="POST" action="{{ route('admin.videos.destroy', $v) }}" onsubmit="return confirm('Delete video?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-400 hover:text-rose-300">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-500">
                No educational videos added yet.
            </div>
        @endforelse
    </div>

    @if($videos->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $videos->links() }}
        </div>
    @endif
</div>
@endsection
