@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">Government Welfare & Agricultural Schemes</h2>
            <p class="text-xs text-slate-400 mt-0.5">Manage state & central agricultural subsidies, eligibility rules, and application links</p>
        </div>

        <a href="{{ route('admin.schemes.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add New Scheme</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.schemes.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative sm:col-span-2">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search scheme name, Kannada title, or department..." 
                       class="w-full pl-4 pr-4 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <div class="flex items-center gap-2">
                <select name="category" class="w-full px-3 py-2 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Categories</option>
                    <option value="subsidy" {{ $category === 'subsidy' ? 'selected' : '' }}>ಸಬ್ಸಿಡಿ (Subsidy)</option>
                    <option value="machinery" {{ $category === 'machinery' ? 'selected' : '' }}>ಯಂತ್ರೋಪಕರಣ (Machinery)</option>
                    <option value="irrigation" {{ $category === 'irrigation' ? 'selected' : '' }}>ನೀರಾವರಿ (Irrigation)</option>
                    <option value="insurance" {{ $category === 'insurance' ? 'selected' : '' }}>ವಿಮೆ (Insurance)</option>
                    <option value="organic" {{ $category === 'organic' ? 'selected' : '' }}>ಸಾವಯವ (Organic)</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition">
                    Filter
                </button>
                @if($search || $category)
                    <a href="{{ route('admin.schemes.index') }}" class="px-2 text-xs text-slate-400 hover:text-white transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Schemes Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Scheme</th>
                        <th class="py-3 px-4">Category & Department</th>
                        <th class="py-3 px-4">Benefit Details</th>
                        <th class="py-3 px-4">Links</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($schemes as $s)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl shrink-0">{{ $s->icon_emoji ?? '🌾' }}</span>
                                    <div>
                                        <div class="font-bold text-white text-sm">{{ $s->title_kn ?? $s->title }}</div>
                                        <div class="text-[11px] text-slate-400 font-sans">{{ $s->title }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-emerald-300 border border-slate-700">
                                    {{ $s->category }}
                                </span>
                                <div class="text-[11px] text-slate-400 mt-1 truncate max-w-[200px]">{{ $s->sponsoring_agency }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-emerald-400 font-semibold truncate max-w-[240px]">
                                    {{ $s->benefit_amount_kn ?? $s->benefit_amount }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    @if($s->apply_url)
                                        <a href="{{ $s->apply_url }}" target="_blank" class="text-emerald-400 hover:underline">Apply</a>
                                    @endif
                                    @if($s->official_url)
                                        <a href="{{ $s->official_url }}" target="_blank" class="text-slate-400 hover:text-white">Portal</a>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('admin.schemes.toggle', $s) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold transition {{ $s->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' }}">
                                        {{ $s->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.schemes.edit', $s) }}" class="p-1 text-slate-400 hover:text-emerald-400">Edit</a>
                                    <form method="POST" action="{{ route('admin.schemes.destroy', $s) }}" onsubmit="return confirm('Delete scheme?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-rose-400 hover:text-rose-300">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No schemes found.</td>
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
    </div>
</div>
@endsection
