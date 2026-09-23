@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ editModal: false, activeFlag: {} }">

    <!-- Header & Summary Cards -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Feature Flags Center</h1>
            <p class="text-sm text-slate-400 mt-0.5">Toggle and configure public platform capabilities in real time without redeployment</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300">
                Total: <span class="text-white font-bold">{{ $totalCount }}</span>
            </span>
            <span class="px-3 py-1 rounded-xl bg-emerald-950 border border-emerald-800/50 text-xs font-semibold text-emerald-300">
                Active: <span class="font-bold">{{ $enabledCount }}</span>
            </span>
            <span class="px-3 py-1 rounded-xl bg-rose-950 border border-rose-800/50 text-xs font-semibold text-rose-300">
                Disabled: <span class="font-bold">{{ $disabledCount }}</span>
            </span>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.feature-flags.index') }}" class="flex-1 w-full flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, key, or description..."
                       class="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
                <option value="">All Statuses</option>
                <option value="enabled" {{ request('status') === 'enabled' ? 'selected' : '' }}>Only Enabled</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Only Disabled</option>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.feature-flags.index') }}" class="text-xs text-slate-400 hover:text-white transition">Reset</a>
            @endif
        </form>
    </div>

    <!-- Flags List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($flags as $flag)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:border-slate-700 transition">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-white tracking-tight truncate">{{ $flag->name }}</h3>
                            <code class="text-[11px] font-mono text-emerald-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800/80 inline-block mt-1">
                                {{ $flag->key }}
                            </code>
                        </div>

                        <!-- Instant Toggle Form -->
                        <form action="{{ route('admin.feature-flags.toggle', $flag) }}" method="POST">
                            @csrf
                            <button type="submit" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $flag->is_enabled ? 'bg-emerald-600' : 'bg-slate-700' }}"
                                    title="Click to toggle status">
                                <span class="sr-only">Toggle flag</span>
                                <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $flag->is_enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </form>
                    </div>

                    <p class="text-xs text-slate-400 mt-3 line-clamp-3 leading-relaxed">
                        {{ $flag->description ?? 'No description provided.' }}
                    </p>
                </div>

                <div class="mt-5 pt-3 border-t border-slate-800/70 flex items-center justify-between text-xs">
                    <span class="inline-flex items-center gap-1.5 font-semibold {{ $flag->is_enabled ? 'text-emerald-400' : 'text-slate-400' }}">
                        <span class="w-2 h-2 rounded-full {{ $flag->is_enabled ? 'bg-emerald-500' : 'bg-slate-600' }}"></span>
                        {{ $flag->is_enabled ? 'Active on PWA' : 'Module Inactive' }}
                    </span>

                    <button @click="activeFlag = { id: {{ $flag->id }}, name: '{{ addslashes($flag->name) }}', key: '{{ $flag->key }}', description: '{{ addslashes($flag->description ?? '') }}', is_enabled: {{ $flag->is_enabled ? 'true' : 'false' }} }; editModal = true"
                            class="text-slate-400 hover:text-emerald-400 font-medium transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Edit
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 bg-slate-900 border border-slate-800 rounded-2xl">
                No feature flags found matching the criteria.
            </div>
        @endforelse
    </div>

    <!-- Edit Modal -->
    <div x-show="editModal" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-lg font-bold text-white">Edit Feature Flag</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form :action="'{{ url('admin/feature-flags') }}/' + activeFlag.id" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Key (Identifier)</label>
                    <input type="text" :value="activeFlag.key" disabled class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs font-mono text-slate-400 cursor-not-allowed">
                    <p class="text-[10px] text-slate-500 mt-1">Unique code used in codebase checks</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Display Name</label>
                    <input type="text" name="name" x-model="activeFlag.name" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Description</label>
                    <textarea name="description" x-model="activeFlag.description" rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_enabled" id="modal_is_enabled" value="1" x-model="activeFlag.is_enabled" class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                    <label for="modal_is_enabled" class="text-xs text-slate-300 font-medium">Flag is active and enabled on public portal</label>
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <button type="button" @click="editModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
