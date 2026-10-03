@extends('layouts.admin')

@section('title', 'Staff & Role Management — Krushi Baandhava Admin')

@section('content')
<div class="space-y-6" x-data="{ roleMatrixOpen: false }">

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="text-2xl">👥</span>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Staff & Role Access Control</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">Manage platform administrators, assign granular roles, and enforce least-privilege security.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <button type="button" 
                    @click="roleMatrixOpen = true"
                    class="flex-1 sm:flex-none justify-center px-3.5 py-2.5 sm:py-2 text-xs font-bold text-slate-300 bg-slate-800/80 hover:bg-slate-700 border border-slate-700 rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                <span>🛡️</span>
                <span>Permissions Matrix</span>
            </button>
            <a href="{{ route('admin.users.create') }}" 
               class="flex-1 sm:flex-none justify-center px-4 py-2.5 sm:py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl transition shadow-md shadow-emerald-950/40 flex items-center gap-1.5 cursor-pointer">
                <span>+ Add Staff</span>
            </a>
        </div>
    </div>

    <!-- 4-Card Metric Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Staff</div>
            <div class="text-2xl font-black text-white mt-1">{{ $metrics['total_staff'] }}</div>
            <div class="text-[11px] text-emerald-400 mt-0.5">{{ $metrics['active_staff'] }} Active Accounts</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-rose-400">Super Admins</div>
            <div class="text-2xl font-black text-rose-300 mt-1">{{ $metrics['super_admins'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Full System Access</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Data Managers</div>
            <div class="text-2xl font-black text-emerald-300 mt-1">{{ $metrics['data_admins'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Mandis & Daily Prices</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400">Content & Support</div>
            <div class="text-2xl font-black text-amber-300 mt-1">{{ $metrics['content_admins'] + $metrics['support_admins'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Agri CMS & Helpdesk</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            
            <!-- Search Keyword -->
            <div class="sm:col-span-5">
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search staff by name, email, or phone..." 
                       class="w-full text-xs font-medium px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
            </div>

            <!-- Role Filter -->
            <div class="sm:col-span-3">
                <select name="role" class="w-full text-xs font-bold px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                    <option value="">All Roles</option>
                    @foreach($availableRoles as $roleKey => $roleDesc)
                        <option value="{{ $roleKey }}" {{ $roleFilter === $roleKey ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $roleKey)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="sm:col-span-2">
                <select name="status" class="w-full text-xs font-bold px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>Suspended Only</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full px-3 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl transition cursor-pointer">
                    Filter
                </button>
                @if($search || $roleFilter || $statusFilter !== null)
                    <a href="{{ route('admin.users.index') }}" class="px-2.5 py-2 text-xs font-bold text-slate-400 hover:text-white bg-slate-950 border border-slate-800 rounded-xl transition" title="Clear Filters">
                        ✕
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Staff Directory Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-md">
        <!-- Mobile Table Swipe Cue -->
        <div class="sm:hidden px-4 py-2 bg-slate-950/80 border-b border-slate-800 text-[11px] text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-1.5 font-medium">
                <span>👉</span> Scroll horizontally to see role & actions
            </span>
            <span class="text-[10px] text-slate-500 font-mono">Swipe ↔</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/60 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="px-4 py-3.5">Staff Member</th>
                        <th class="px-4 py-3.5">Role & Access Level</th>
                        <th class="px-4 py-3.5">Assigned District</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Last Active</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-800/40 transition">
                            
                            <!-- Staff Member Info -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-700 border border-slate-600/50 flex items-center justify-center font-bold text-xs text-white uppercase shrink-0">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white flex items-center gap-1.5">
                                            <span>{{ $user->name }}</span>
                                            @if($user->id === auth()->id())
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 bg-emerald-950 text-emerald-300 border border-emerald-700/60 rounded">You</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $user->email }}</div>
                                        @if($user->phone)
                                            <div class="text-[10px] text-slate-500 font-mono">📱 {{ $user->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Role Badge -->
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border {{ $user->getRoleBadgeClass() }}">
                                    {{ $user->getRoleTitle() }}
                                </span>
                            </td>

                            <!-- District -->
                            <td class="px-4 py-3.5 text-slate-300">
                                @if($user->district)
                                    <span class="inline-flex items-center gap-1 font-medium">
                                        <span>📍</span>
                                        <span>{{ $user->district->name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-500 italic">Statewide HQ</span>
                                @endif
                            </td>

                            <!-- Active Status Toggle -->
                            <td class="px-4 py-3.5">
                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                    @csrf
                                    @if($user->is_active)
                                        <button type="submit" 
                                                title="Click to suspend staff account"
                                                onclick="return confirm('Suspend staff account for {{ addslashes($user->name) }}?')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-700/60 hover:border-emerald-500 transition cursor-pointer">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            <span>Active</span>
                                        </button>
                                    @else
                                        <button type="submit" 
                                                title="Click to reactivate staff account"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-950/80 text-rose-300 border border-rose-700/60 hover:border-rose-500 transition cursor-pointer">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                            <span>Suspended</span>
                                        </button>
                                    @endif
                                </form>
                            </td>

                            <!-- Last Active -->
                            <td class="px-4 py-3.5 text-slate-400 text-[11px] font-mono">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never logged in' }}
                            </td>

                            <!-- Action Buttons -->
                            <td class="px-4 py-3.5 text-right space-x-1.5">
                                <a href="{{ route('admin.users.edit', $user) }}" 
                                   class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-bold transition border border-slate-700">
                                    Edit
                                </a>

                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Permanently remove {{ addslashes($user->name) }} from admin staff?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 text-[11px] font-bold transition border border-rose-800/60 cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic">
                                No staff members found matching the specified filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Permissions Matrix Reference Modal -->
    <div x-show="roleMatrixOpen" 
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         @keydown.escape.window="roleMatrixOpen = false">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="roleMatrixOpen = false"></div>

        <!-- Modal Dialog -->
        <div class="relative z-10 bg-slate-900 rounded-2xl max-w-2xl w-full p-4 sm:p-6 shadow-2xl border border-slate-700 text-white my-auto max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🛡️</span> Role Permissions Reference Matrix
                    </h3>
                    <p class="text-xs text-slate-400">Overview of administrative access boundaries across the platform.</p>
                </div>
                <button @click="roleMatrixOpen = false" class="text-slate-400 hover:text-white text-lg font-bold cursor-pointer">✕</button>
            </div>

            <div class="mt-4 space-y-3 text-xs">
                
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-rose-300">Super Administrator</span>
                        <span class="text-[10px] font-mono uppercase bg-rose-950 text-rose-300 border border-rose-700/60 px-2 py-0.5 rounded">super_admin</span>
                    </div>
                    <p class="text-slate-300 text-[11px]">Full, unrestricted control over the entire system. Can create/delete staff, modify system branding, purge databases, and toggle feature flags.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-emerald-300">Data & Mandi Administrator</span>
                        <span class="text-[10px] font-mono uppercase bg-emerald-950 text-emerald-300 border border-emerald-700/60 px-2 py-0.5 rounded">data_admin</span>
                    </div>
                    <p class="text-slate-300 text-[11px]">Manages APMC mandis, commodities, live ingestion triggers, historical backfills, unresolved entity mappings, and data quality logs.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-amber-300">Agricultural Content Editor</span>
                        <span class="text-[10px] font-mono uppercase bg-amber-950 text-amber-300 border border-amber-700/60 px-2 py-0.5 rounded">content_admin</span>
                    </div>
                    <p class="text-slate-300 text-[11px]">Curates farmer-facing content: Government Schemes, Agri News, YouTube Education Videos, Agronomy Articles, and Navigation menus.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-cyan-300">Farmer Support Officer</span>
                        <span class="text-[10px] font-mono uppercase bg-cyan-950 text-cyan-300 border border-cyan-700/60 px-2 py-0.5 rounded">support_admin</span>
                    </div>
                    <p class="text-slate-300 text-[11px]">Responds to farmer helpdesk complaints, resolves grievance inquiries, reviews feedback submissions, and manages admin guidelines.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-purple-300">Price & Market Analyst</span>
                        <span class="text-[10px] font-mono uppercase bg-purple-950 text-purple-300 border border-purple-700/60 px-2 py-0.5 rounded">forecast_admin</span>
                    </div>
                    <p class="text-slate-300 text-[11px]">Monitors price freshness & staleness rules, tracks Prophet projection models, reviews audit trails, and inspects weather cache storage.</p>
                </div>

            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" @click="roleMatrixOpen = false" class="px-4 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
