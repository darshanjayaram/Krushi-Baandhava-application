@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>🏛️</span>
                <span>Government Welfare & Agricultural Schemes</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">
                Manage subsidies, farm machinery grants, multi-banner slider, and official portal links for farmers
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <a href="{{ route('farmer.schemes.index') }}" target="_blank"
               class="flex-1 sm:flex-none justify-center inline-flex items-center gap-1.5 px-3.5 py-2.5 sm:py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition border border-slate-700">
                <span>👁️ View Page</span>
                <span>↗</span>
            </a>

            <a href="{{ route('admin.schemes.create') }}" 
               class="flex-1 sm:flex-none justify-center inline-flex items-center gap-2 px-4 py-2.5 sm:py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Scheme</span>
            </a>
        </div>
    </div>

    <!-- Status Alert -->
    @if(session('status'))
        <div class="p-3.5 rounded-xl bg-emerald-950/80 border border-emerald-800/80 text-emerald-200 text-xs font-bold flex items-center gap-2">
            <span>✓</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 rounded-xl bg-rose-950/80 border border-rose-800/80 text-rose-200 text-xs font-bold flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 1. QUICK SYSTEM SETTINGS BAR (Per Page & Slider Auto-play) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
            <div class="flex items-center gap-2 text-xs font-black text-white">
                <span class="text-amber-400">⚙️</span>
                <span>Farmer UI Display & Multi-Banner Slider Settings</span>
            </div>
            <span class="text-[11px] font-bold text-slate-400">Controls `/schemes` public view</span>
        </div>

        <form method="POST" action="{{ route('admin.schemes.settings') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            @csrf

            <!-- Max Schemes Per Page -->
            <div>
                <label class="block text-[11px] font-extrabold text-slate-300 uppercase tracking-wider mb-1">
                    Max Schemes Per Page
                </label>
                <select name="per_page" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500">
                    @foreach([6, 9, 12, 15, 18, 24] as $n)
                        <option value="{{ $n }}" {{ $perPageSetting === $n ? 'selected' : '' }}>
                            {{ $n }} schemes per page {{ $n === 9 ? '(Default)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Banner Slider Auto-play -->
            <div>
                <label class="block text-[11px] font-extrabold text-slate-300 uppercase tracking-wider mb-1">
                    Banner Slider Auto-Rotate
                </label>
                <select name="slider_autoplay" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500">
                    <option value="0" {{ $sliderAutoplay === 0 ? 'selected' : '' }}>Manual (No auto-play)</option>
                    <option value="3" {{ $sliderAutoplay === 3 ? 'selected' : '' }}>Every 3 seconds</option>
                    <option value="5" {{ $sliderAutoplay === 5 ? 'selected' : '' }}>Every 5 seconds (Recommended)</option>
                    <option value="7" {{ $sliderAutoplay === 7 ? 'selected' : '' }}>Every 7 seconds</option>
                    <option value="10" {{ $sliderAutoplay === 10 ? 'selected' : '' }}>Every 10 seconds</option>
                </select>
            </div>

            <!-- Show / Hide Banner Slider Toggle -->
            <div>
                <label class="block text-[11px] font-extrabold text-slate-300 uppercase tracking-wider mb-1">
                    Top Banner Slider
                </label>
                <label class="flex items-center gap-2 px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl cursor-pointer">
                    <input type="checkbox" name="show_banner" value="1" {{ $showBanner ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-900">
                    <span class="text-xs font-bold text-white">Enable Top Slider ({{ $featuredCount }} Active)</span>
                </label>
            </div>

            <!-- Save Settings Button -->
            <div>
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs shadow-sm transition">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- 2. STATS KPI TILES -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 flex items-center justify-between">
            <div>
                <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Total Schemes</div>
                <div class="text-xl font-black text-white mt-0.5">{{ $totalCount }}</div>
            </div>
            <span class="text-2xl">📋</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 flex items-center justify-between">
            <div>
                <div class="text-[10px] font-extrabold text-emerald-400 uppercase tracking-wider">Active for Farmers</div>
                <div class="text-xl font-black text-emerald-300 mt-0.5">{{ $activeCount }}</div>
            </div>
            <span class="text-2xl">✅</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 flex items-center justify-between">
            <div>
                <div class="text-[10px] font-extrabold text-amber-400 uppercase tracking-wider">Featured in Top Slider</div>
                <div class="text-xl font-black text-amber-300 mt-0.5">{{ $featuredCount }}</div>
            </div>
            <span class="text-2xl">⭐</span>
        </div>
    </div>

    <!-- 3. SEARCH & FILTERS -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.schemes.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div class="sm:col-span-2">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search scheme name, Kannada title, benefit, or department..." 
                       class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <div>
                <select name="category" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $catKey => $catData)
                        <option value="{{ $catKey }}" {{ $category === $catKey ? 'selected' : '' }}>
                            {{ $catData['icon'] }} {{ $catData['name_kn'] }} ({{ $catData['name_en'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="featured" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Banners</option>
                    <option value="yes" {{ $featured === 'yes' ? 'selected' : '' }}>⭐ Featured in Slider</option>
                    <option value="no" {{ $featured === 'no' ? 'selected' : '' }}>Standard Cards</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition">
                    Filter
                </button>
                @if($search || $category || $featured || $status)
                    <a href="{{ route('admin.schemes.index') }}" class="px-2 text-xs text-slate-400 hover:text-white transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- 4. SCHEMES TABLE WITH BULK ACTIONS -->
    <form method="POST" action="{{ route('admin.schemes.bulk') }}" id="bulkForm" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        @csrf

        <!-- Bulk Toolbar -->
        <div class="p-3.5 bg-slate-950/80 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 text-slate-400">
                <input type="checkbox" id="selectAllCheckbox" class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-900">
                <label for="selectAllCheckbox" class="font-bold cursor-pointer">Select All on Page</label>
            </div>

            <div class="flex items-center gap-2">
                <select name="action" required class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs font-bold">
                    <option value="">— Bulk Action —</option>
                    <option value="feature">⭐ Add to Banner Slider</option>
                    <option value="unfeature">Remove from Banner Slider</option>
                    <option value="activate">✓ Activate for Farmers</option>
                    <option value="deactivate">✕ Deactivate</option>
                    <option value="delete">🗑️ Delete Selected</option>
                </select>
                <button type="submit" onclick="return confirm('Apply bulk action to selected schemes?')" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-lg transition">
                    Apply
                </button>
            </div>
        </div>

        <!-- Mobile Table Swipe Cue -->
        <div class="sm:hidden px-4 py-2 bg-slate-950/80 border-b border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-1.5 font-medium">
                <span>👉</span> Scroll horizontally for scheme actions & status
            </span>
            <span class="text-[10px] text-slate-500 font-mono">Swipe ↔</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-3 w-8 text-center">#</th>
                        <th class="py-3 px-4">Scheme Details</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Highlight Benefit Metric</th>
                        <th class="py-3 px-4">Direct Govt Link</th>
                        <th class="py-3 px-3 text-center">Banner Slider</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($schemes as $s)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-3 text-center">
                                <input type="checkbox" name="selected_ids[]" value="{{ $s->id }}" class="scheme-checkbox rounded border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-900">
                            </td>

                            <td class="py-3 px-4">
                                <div class="flex items-start gap-2.5">
                                    <span class="text-xl shrink-0">{{ $s->icon_emoji ?? '🌾' }}</span>
                                    <div>
                                        <div class="font-bold text-white text-sm leading-snug">{{ $s->title_kn ?? $s->title }}</div>
                                        <div class="text-[11px] text-slate-400 font-sans mt-0.5">{{ $s->title }}</div>
                                        @if($s->sponsoring_agency)
                                            <div class="text-[10px] text-slate-500 mt-1">🏛️ {{ $s->sponsoring_agency }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-800 text-emerald-300 border border-slate-700">
                                    <span>{{ $categories[$s->category]['icon'] ?? '📋' }}</span>
                                    <span>{{ $s->category }}</span>
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <div class="text-amber-300 font-bold truncate max-w-[220px]">
                                    {{ $s->banner_tag ?: ($s->benefit_amount_kn ?: $s->benefit_amount) }}
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                @php
                                    $linkUrl = $s->apply_url ?: $s->official_url;
                                    $linkHost = $linkUrl ? parse_url($linkUrl, PHP_URL_HOST) : null;
                                @endphp
                                @if($linkUrl)
                                    <a href="{{ $linkUrl }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 hover:underline">
                                        <span class="font-mono">{{ $linkHost }}</span>
                                        <span>↗</span>
                                    </a>
                                @else
                                    <span class="text-slate-600 text-[11px]">No link</span>
                                @endif
                            </td>

                            <!-- 1-Click Banner Slider Toggle -->
                            <td class="py-3 px-3 text-center">
                                <button type="button" 
                                        onclick="document.getElementById('toggle-featured-{{ $s->id }}').submit();"
                                        title="{{ $s->is_featured ? 'Click to remove from banner slider' : 'Click to feature in top slider' }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition cursor-pointer {{ $s->is_featured ? 'bg-amber-950 text-amber-300 border border-amber-700 shadow-xs' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:text-white' }}">
                                    <span>{{ $s->is_featured ? '⭐ Slider' : '☆ Card Only' }}</span>
                                </button>
                            </td>

                            <!-- 1-Click Active Toggle -->
                            <td class="py-3 px-3 text-center">
                                <button type="button" 
                                        onclick="document.getElementById('toggle-status-{{ $s->id }}').submit();"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold transition cursor-pointer {{ $s->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' }}">
                                    {{ $s->is_active ? '✓ Active' : '✕ Inactive' }}
                                </button>
                            </td>

                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.schemes.edit', $s) }}" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition">
                                        Edit
                                    </a>
                                    <button type="button" 
                                            onclick="if(confirm('Are you sure you want to delete this scheme?')) document.getElementById('delete-scheme-{{ $s->id }}').submit();"
                                            class="px-2 py-1 rounded-lg bg-rose-950/60 hover:bg-rose-900 text-rose-300 text-xs font-bold transition">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-500">No schemes found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schemes->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $schemes->links() }}
            </div>
        @endif
    </form>

    <!-- Hidden Individual Action Forms -->
    @foreach($schemes as $s)
        <form id="toggle-featured-{{ $s->id }}" method="POST" action="{{ route('admin.schemes.toggle-featured', $s) }}" class="hidden">
            @csrf
        </form>
        <form id="toggle-status-{{ $s->id }}" method="POST" action="{{ route('admin.schemes.toggle', $s) }}" class="hidden">
            @csrf
        </form>
        <form id="delete-scheme-{{ $s->id }}" method="POST" action="{{ route('admin.schemes.destroy', $s) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('.scheme-checkbox');

        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                checkboxes.forEach(cb => cb.checked = e.target.checked);
            });
        }
    });
</script>
@endsection
