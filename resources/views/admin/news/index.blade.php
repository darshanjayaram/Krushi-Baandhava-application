@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">Agricultural News & Market Bulletins</h2>
            <p class="text-xs text-slate-400 mt-0.5">Publish APMC market arrivals, policy updates, and breaking agricultural news</p>
        </div>

        <a href="{{ route('admin.news.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Publish News Bulletin</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.news.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative sm:col-span-2">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search headline, Kannada title, or source..." 
                       class="w-full pl-4 pr-4 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <div class="flex items-center gap-2">
                <select name="priority" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Priorities</option>
                    <option value="breaking" {{ $priority === 'breaking' ? 'selected' : '' }}>ತಾಜಾ ಸುದ್ದಿ (Breaking)</option>
                    <option value="important" {{ $priority === 'important' ? 'selected' : '' }}>ಮುಖ್ಯ ಸುದ್ದಿ (Important)</option>
                    <option value="standard" {{ $priority === 'standard' ? 'selected' : '' }}>ಸಾಮಾನ್ಯ (Standard)</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition">
                    Filter
                </button>
                @if($search || $priority)
                    <a href="{{ route('admin.news.index') }}" class="px-2 text-xs text-slate-400 hover:text-white transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- News Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Headline</th>
                        <th class="py-3 px-4">Priority & Source</th>
                        <th class="py-3 px-4">Published Date</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($news as $n)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-white text-sm">{{ $n->headline_kn ?? $n->headline }}</div>
                                <div class="text-[11px] text-slate-400 font-sans mt-0.5 truncate max-w-[400px]">{{ $n->headline }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $n->priority_badge_color }}">
                                    {{ strtoupper($n->priority) }}
                                </span>
                                <div class="text-[11px] text-slate-400 mt-1">{{ $n->source_name }}</div>
                            </td>
                            <td class="py-3 px-4 text-slate-400">
                                {{ $n->published_at ? $n->published_at->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('admin.news.toggle', $n) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold transition {{ $n->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' }}">
                                        {{ $n->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.news.edit', $n) }}" class="p-1 text-slate-400 hover:text-emerald-400">Edit</a>
                                    <form method="POST" action="{{ route('admin.news.destroy', $n) }}" onsubmit="return confirm('Delete news?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-rose-400 hover:text-rose-300">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">No news articles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($news->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $news->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
