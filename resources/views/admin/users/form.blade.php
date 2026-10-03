@extends('layouts.admin')

@section('title', ($user->exists ? 'Edit Staff Member' : 'Add New Staff Member') . ' — Krushi Baandhava Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    selectedRole: '{{ old('role', $user->role ?? 'data_admin') }}'
}">

    <!-- Page Header & Back Button -->
    <div class="flex items-center justify-between pb-2">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-slate-400 hover:text-white transition flex items-center gap-1.5 mb-1.5">
                <span>←</span>
                <span>Back to Staff Directory</span>
            </a>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                {{ $user->exists ? 'Edit Staff: ' . $user->name : 'Create New Staff Member' }}
            </h1>
        </div>
        <span class="text-xs font-mono px-3 py-1 bg-slate-900 border border-slate-700 text-slate-300 rounded-xl">
            {{ $user->exists ? 'Staff ID #' . $user->id : 'New Registration' }}
        </span>
    </div>

    <!-- Main Form Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-8 shadow-xl">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-6">
            @csrf
            @if($user->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Full Name <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $user->name) }}" 
                           placeholder="e.g. Ramesh Patil"
                           required 
                           class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white placeholder-slate-600 focus:outline-none transition @error('name') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                    @error('name')
                        <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Official Email Address <span class="text-rose-400">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email', $user->email) }}" 
                           placeholder="staff@krushibaandhava.in"
                           required 
                           class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white placeholder-slate-600 focus:outline-none transition @error('email') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                    @error('email')
                        <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <!-- Phone Number -->
                <div>
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Phone Number (Optional)
                    </label>
                    <input type="text" 
                           id="phone" 
                           name="phone" 
                           value="{{ old('phone', $user->phone) }}" 
                           placeholder="+91 9876543210"
                           class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white placeholder-slate-600 focus:outline-none transition @error('phone') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                    @error('phone')
                        <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Preferred Language -->
                <div>
                    <label for="preferred_language" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Default Interface Language <span class="text-rose-400">*</span>
                    </label>
                    <select id="preferred_language" 
                            name="preferred_language" 
                            class="w-full text-xs font-bold px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                        <option value="kn" {{ old('preferred_language', $user->preferred_language) === 'kn' ? 'selected' : '' }}>
                            ಕನ್ನಡ (Kannada - Default)
                        </option>
                        <option value="en" {{ old('preferred_language', $user->preferred_language) === 'en' ? 'selected' : '' }}>
                            English
                        </option>
                    </select>
                </div>

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <!-- Role Selection -->
                <div>
                    <label for="role" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Assigned Role & Access Level <span class="text-rose-400">*</span>
                    </label>
                    <select id="role" 
                            name="role" 
                            x-model="selectedRole"
                            class="w-full text-xs font-bold px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                        @foreach($roles as $roleKey => $roleDesc)
                            <option value="{{ $roleKey }}" {{ old('role', $user->role) === $roleKey ? 'selected' : '' }}>
                                {{ $roleDesc }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Operational District -->
                <div>
                    <label for="district_id" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Operational District / HQ
                    </label>
                    <select id="district_id" 
                            name="district_id" 
                            class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                        <option value="">Statewide HQ (All Karnataka Mandis)</option>
                        @foreach($districts as $d)
                            <option value="{{ $d->id }}" {{ (string)old('district_id', $user->district_id) === (string)$d->id ? 'selected' : '' }}>
                                {{ $d->name }} ({{ $d->name_kn }})
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    {{ $user->exists ? 'Update Password (leave blank to keep current)' : 'Initial Password' }}
                    @if(!$user->exists) <span class="text-rose-400">*</span> @endif
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       {{ $user->exists ? '' : 'required' }}
                       placeholder="{{ $user->exists ? '••••••••' : 'Minimum 8 characters' }}"
                       class="w-full text-xs font-medium px-3.5 py-2.5 bg-slate-950 border rounded-xl text-white placeholder-slate-600 focus:outline-none transition @error('password') border-rose-500 @else border-slate-700 focus:border-emerald-500 @enderror">
                @error('password')
                    <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Account Status Toggle (On Edit) -->
            @if($user->exists)
                <div class="pt-2 border-t border-slate-800">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1" 
                               {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 bg-slate-950 border-slate-700">
                        <div>
                            <span class="text-xs font-bold text-white block">Account Active & Authorized to Sign In</span>
                            <span class="text-[11px] text-slate-400">Uncheck to temporarily suspend this staff member's administrative access.</span>
                        </div>
                    </label>
                </div>
            @endif

            <!-- Dynamic Role Privilege Summary Card -->
            <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Privilege Scope for Selected Role:</div>
                <div class="text-xs text-slate-300 leading-relaxed">
                    <template x-if="selectedRole === 'super_admin'">
                        <span class="text-rose-300 font-medium">⚠️ <strong>Super Administrator:</strong> Full unrestricted permissions across all settings, staff accounts, database purges, and API connectors.</span>
                    </template>
                    <template x-if="selectedRole === 'data_admin'">
                        <span class="text-emerald-300 font-medium">🌾 <strong>Data Administrator:</strong> Authorized to manage APMC mandis, crop mappings, live price sync, and data quality logs. Restricted from modifying system settings or users.</span>
                    </template>
                    <template x-if="selectedRole === 'content_admin'">
                        <span class="text-amber-300 font-medium">📝 <strong>Content Editor:</strong> Authorized to publish Government Schemes, Agri News, YouTube Education Videos, and navigation menus. Restricted from modifying prices or system configs.</span>
                    </template>
                    <template x-if="selectedRole === 'support_admin'">
                        <span class="text-cyan-300 font-medium">💬 <strong>Support Officer:</strong> Authorized to resolve farmer helpdesk tickets, respond to grievance inquiries, and maintain admin documentation.</span>
                    </template>
                    <template x-if="selectedRole === 'forecast_admin'">
                        <span class="text-purple-300 font-medium">📊 <strong>Price Analyst:</strong> Authorized to inspect price trends, freshness rules, weather cache, and system audit logs.</span>
                    </template>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="pt-4 border-t border-slate-800 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="text-center px-4 py-3 sm:py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition">
                    Cancel
                </a>
                <button type="submit" class="justify-center px-6 py-3 sm:py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl transition shadow-md shadow-emerald-950/40 cursor-pointer flex items-center gap-2">
                    <span>{{ $user->exists ? 'Save Staff Changes' : 'Create Staff Member' }}</span>
                    <span>✓</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
