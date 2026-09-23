@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">Farming Guides & Agricultural CMS</h2>
            <p class="text-xs text-slate-400 mt-0.5">Author agronomy guides, pest prevention advisories, and crop cultivation best practices</p>
        </div>

        <a href="{{ route('admin.articles.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Write New Guide</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.articles.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="relative sm:col-span-2">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search title, author, or keyword..." 
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
                    <option value="pest_control" {{ $category === 'pest_control' ? 'selected' : '' }}>ಕೀಟ & ರೋಗ (Pest)</option>
                    <option value="soil_fertilizer" {{ $category === 'soil_fertilizer' ? 'selected' : '' }}>ಮಣ್ಣು & ಗೊಬ್ಬರ (Fertilizer)</option>
                    <option value="harvest_storage" {{ $category === 'harvest_storage' ? 'selected' : '' }}>ಕೊಯ್ಲು & ಸಂಗ್ರಹ (Harvest)</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Articles Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Title</th>
                        <th class="py-3 px-4">Crop & Category</th>
                        <th class="py-3 px-4">Author</th>
                        <th class="py-3 px-4">Published Date</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($articles as $a)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-white text-sm">{{ $a->title_kn ?? $a->title }}</div>
                                <div class="text-[11px] text-slate-400 font-sans mt-0.5 truncate max-w-[360px]">{{ $a->title }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-emerald-300 border border-slate-700">
                                    {{ $a->category }}
                                </span>
                                @if($a->crop)
                                    <div class="text-[11px] text-amber-400 mt-1 font-semibold">{{ $a->crop->name }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-400">
                                {{ $a->author_name }}
                            </td>
                            <td class="py-3 px-4 text-slate-400">
                                {{ $a->published_at ? $a->published_at->format('d M Y') : '—' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('admin.articles.toggle', $a) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold transition {{ $a->is_published ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                                        {{ $a->is_published ? 'Published' : 'Draft' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.articles.edit', $a) }}" class="p-1 text-slate-400 hover:text-emerald-400">Edit</a>
                                    <form method="POST" action="{{ route('admin.articles.destroy', $a) }}" onsubmit="return confirm('Delete article?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-rose-400 hover:text-rose-300">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No agronomy guides found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articles->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
