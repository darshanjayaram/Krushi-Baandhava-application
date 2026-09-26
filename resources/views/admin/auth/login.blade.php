<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Sign In — Krushi Baandhava</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex items-center justify-center p-4 sm:p-6 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black text-slate-100">

    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-xl border border-emerald-400/30">
                <img src="{{ \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg') }}" alt="Krushi Baandhava" class="w-9 h-9 object-contain">
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Krushi Baandhava Admin</h1>
            <p class="text-sm text-slate-400 mt-1">Authorized Management & Data Operations Portal</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl">
            @if (session('error'))
                <div class="mb-5 p-3.5 rounded-xl bg-rose-950/80 border border-rose-600/40 text-rose-300 text-xs font-medium flex items-center gap-2.5">
                    <svg class="w-4 h-4 shrink-0 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-950/80 border border-emerald-600/40 text-emerald-300 text-xs font-medium flex items-center gap-2.5">
                    <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email', 'admin@krushibaandhava.org') }}" 
                           required 
                           autofocus
                           class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
                    @error('email')
                        <p class="text-rose-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Password</label>
                    </div>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           value="password123" 
                           required
                           class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
                    @error('password')
                        <p class="text-rose-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs text-slate-400 select-none">Remember this browser</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full py-2.5 px-4 mt-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-lg shadow-emerald-950/60 focus:outline-none focus:ring-2 focus:ring-emerald-400 transition cursor-pointer">
                    Sign In to Portal
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-emerald-400 transition inline-flex items-center gap-1">
                    <span>← Return to Farmer PWA</span>
                </a>
            </div>
        </div>

        <div class="mt-6 text-center text-[11px] text-slate-500">
            Protected system • All administrative logins and actions are audit logged.
        </div>
    </div>

</body>
</html>
