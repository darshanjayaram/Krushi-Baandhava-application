@extends('layouts.admin')

@section('title', 'My Profile & Account Security — Krushi Baandhava Admin')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Page Header / Identity Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-emerald-500/5 rounded-full blur-2xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 border border-emerald-400/30 flex items-center justify-center font-black text-2xl text-white shadow-lg shrink-0 uppercase tracking-wider">
                    {{ substr($user->name, 0, 2) }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">{{ $user->name }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider border {{ $user->getRoleBadgeClass() }}">
                            {{ $user->getRoleTitle() }}
                        </span>
                        @if($user->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/90 text-emerald-300 border border-emerald-700/60 font-mono">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active Staff
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400 mt-1 flex items-center gap-3 flex-wrap">
                        <span>📧 {{ $user->email }}</span>
                        @if($user->phone)
                            <span>📱 {{ $user->phone }}</span>
                        @endif
                        @if($user->district)
                            <span>📍 {{ $user->district->name }} District</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="text-xs text-slate-400 border-t sm:border-t-0 sm:border-l border-slate-800 pt-3 sm:pt-0 sm:pl-6 space-y-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Account Telemetry</div>
                <div>Member since: <strong class="text-white">{{ $user->created_at->format('M d, Y') }}</strong></div>
                <div>Last active: <strong class="text-emerald-300">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Just now' }}</strong></div>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Columns: Edit Profile & Change Password Forms -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Card 1: Personal & Operational Information -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 shadow-md">
                <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>👤</span> Personal & Operational Information
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Manage your display credentials, contact phone, and administrative preferences.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                                Full Name <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" 
                                   required 
                                   class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white focus:outline-none transition @error('name') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                            @error('name')
                                <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email (Read Only) -->
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                Official Login Email (Locked)
                            </label>
                            <input type="email" 
                                   id="email" 
                                   value="{{ $user->email }}" 
                                   disabled 
                                   class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950/50 border border-slate-800 rounded-xl text-slate-400 cursor-not-allowed">
                            <p class="text-[10px] text-slate-500 mt-1">Contact a Super Administrator if your official login email must be altered.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Phone Number -->
                        <div>
                            <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                                Phone Number (For System Alerts)
                            </label>
                            <input type="text" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone) }}" 
                                   placeholder="e.g. +91 9876543210"
                                   class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white focus:outline-none transition @error('phone') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                            @error('phone')
                                <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Preferred Language -->
                        <div>
                            <label for="preferred_language" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                                Preferred Admin Language <span class="text-rose-400">*</span>
                            </label>
                            <select id="preferred_language" 
                                    name="preferred_language" 
                                    class="w-full text-xs font-bold px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                                <option value="kn" {{ old('preferred_language', $user->preferred_language) === 'kn' ? 'selected' : '' }}>
                                    ಕನ್ನಡ (Kannada - Default)
                                </option>
                                <option value="en" {{ old('preferred_language', $user->preferred_language) === 'en' ? 'selected' : '' }}>
                                    English (Default)
                                </option>
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1">Changes your personal interface language across the control panel.</p>
                        </div>
                    </div>

                    <!-- Operational District -->
                    <div>
                        <label for="district_id" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Assigned Operational District / Headquarters
                        </label>
                        <select id="district_id" 
                                name="district_id" 
                                class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                            <option value="">All Karnataka / State Headquarters (Unassigned)</option>
                            @foreach($districts as $d)
                                <option value="{{ $d->id }}" {{ (string)old('district_id', $user->district_id) === (string)$d->id ? 'selected' : '' }}>
                                    {{ $d->name }} ({{ $d->name_kn }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Sets your primary geographical focus for local Mandi monitoring and notifications.</p>
                    </div>

                    <!-- Submit Profile Changes -->
                    <div class="pt-3 flex items-center justify-end">
                        <button type="submit" class="w-full sm:w-auto justify-center px-5 py-3 sm:py-2.5 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-500 transition shadow-md shadow-emerald-950/40 cursor-pointer flex items-center gap-2">
                            <span>Save Profile Details</span>
                            <span>✓</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card 2: Security & Password Management -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 shadow-md">
                <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🔒</span> Change Security Password
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Ensure your account is protected with a secure password containing letters and numbers.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Current Password <span class="text-rose-400">*</span>
                        </label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               required 
                               autocomplete="current-password"
                               class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white focus:outline-none transition @error('current_password') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                        @error('current_password')
                            <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                                New Password <span class="text-rose-400">*</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   autocomplete="new-password"
                                   placeholder="Minimum 8 characters"
                                   class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white focus:outline-none transition @error('password') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                            @error('password')
                                <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm New Password -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">
                                Confirm New Password <span class="text-rose-400">*</span>
                            </label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   autocomplete="new-password"
                                   class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Password Policy Banner -->
                    <div class="bg-slate-950/70 p-3 rounded-xl border border-slate-800 text-[11px] text-slate-400 flex items-start gap-2">
                        <span class="text-emerald-400 font-bold">ℹ️</span>
                        <span>For strong security, passwords must contain at least <strong>8 characters</strong>, include letters and numbers, and cannot match your current password.</span>
                    </div>

                    <!-- Submit Password Change -->
                    <div class="pt-2 flex items-center justify-end">
                        <button type="submit" class="w-full sm:w-auto justify-center px-5 py-3 sm:py-2.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl transition cursor-pointer flex items-center gap-2">
                            <span>Update Password</span>
                            <span>🔑</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Right 1 Column: Role Access Matrix & Activity Log -->
        <div class="space-y-6">

            <!-- Card 3: Role & Permission Scope -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 shadow-md space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>🛡️</span> Your Access Scope
                    </h3>
                    <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded border {{ $user->getRoleBadgeClass() }}">
                        {{ $user->role }}
                    </span>
                </div>

                <div class="text-xs text-slate-300 leading-relaxed">
                    You are logged in with the <strong class="text-white">{{ $user->getRoleTitle() }}</strong> role.
                </div>

                <div class="space-y-2 pt-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Permitted Administration Modules:</div>
                    
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950/80 border border-slate-800/80">
                            <span class="text-slate-300">Daily Market Prices & Mandis</span>
                            <span class="font-mono text-[10px] {{ $user->canAccessModule('prices') || $user->isSuperAdmin() ? 'text-emerald-400 font-bold' : 'text-slate-600' }}">
                                {{ $user->canAccessModule('prices') || $user->isSuperAdmin() ? '✓ Allowed' : '✗ Restricted' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950/80 border border-slate-800/80">
                            <span class="text-slate-300">Live Ingestion & Data Sources</span>
                            <span class="font-mono text-[10px] {{ $user->canAccessModule('data') || $user->isSuperAdmin() ? 'text-emerald-400 font-bold' : 'text-slate-600' }}">
                                {{ $user->canAccessModule('data') || $user->isSuperAdmin() ? '✓ Allowed' : '✗ Restricted' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950/80 border border-slate-800/80">
                            <span class="text-slate-300">Agricultural CMS (Schemes, News, Videos)</span>
                            <span class="font-mono text-[10px] {{ $user->canAccessModule('content') || $user->isSuperAdmin() ? 'text-emerald-400 font-bold' : 'text-slate-600' }}">
                                {{ $user->canAccessModule('content') || $user->isSuperAdmin() ? '✓ Allowed' : '✗ Restricted' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950/80 border border-slate-800/80">
                            <span class="text-slate-300">Farmer Helpdesk CRM & Grievances</span>
                            <span class="font-mono text-[10px] {{ $user->canAccessModule('support') || $user->isSuperAdmin() ? 'text-emerald-400 font-bold' : 'text-slate-600' }}">
                                {{ $user->canAccessModule('support') || $user->isSuperAdmin() ? '✓ Allowed' : '✗ Restricted' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950/80 border border-slate-800/80">
                            <span class="text-slate-300">System Settings & Data Pruning</span>
                            <span class="font-mono text-[10px] {{ $user->isSuperAdmin() ? 'text-emerald-400 font-bold' : 'text-slate-600' }}">
                                {{ $user->isSuperAdmin() ? '✓ Allowed' : '✗ Restricted' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950/80 border border-slate-800/80">
                            <span class="text-slate-300">Staff & Role Management</span>
                            <span class="font-mono text-[10px] {{ $user->isSuperAdmin() ? 'text-emerald-400 font-bold' : 'text-slate-600' }}">
                                {{ $user->isSuperAdmin() ? '✓ Allowed' : '✗ Restricted' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Recent Personal Activity Logs -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 shadow-md space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>📜</span> Recent Actions Log
                    </h3>
                    <a href="{{ route('admin.audit-logs.index') }}" class="text-[11px] text-emerald-400 hover:text-emerald-300 font-bold">
                        Full Audit Trail ↗
                    </a>
                </div>

                <div class="space-y-2.5">
                    @forelse($recentLogs as $log)
                        <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-800/70 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-white font-mono text-[11px]">{{ $log->action }}</span>
                                <span class="text-[10px] text-slate-500 font-mono">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            @if($log->auditable_type)
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    Target: <span class="text-emerald-400">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-xs text-slate-500 italic text-center py-4">No recent activity logged yet.</div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
