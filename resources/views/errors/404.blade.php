<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>೪೦೪ — ಪುಟ ಕಂಡುಬಂದಿಲ್ಲ | 404 Page Not Found</title>
    @vite(['resources/css/app.css'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tiro+Kannada:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .font-kannada { font-family: 'Tiro Kannada', serif; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FAF8F5; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 antialiased text-stone-800">
    @php
        $appLogo = '/icons/icon-192.svg';
        $appName = 'Krushi Baandhava';
        $appNameKn = 'ಕೃಷಿ ಬಾಂಧವ';
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $appLogo = \App\Models\SystemSetting::get('app_logo', $appLogo);
                $appName = \App\Models\SystemSetting::get('application_name', $appName);
                $appNameKn = \App\Models\SystemSetting::get('application_name_kn', $appNameKn);
            }
        } catch (\Throwable $ignored) {}

        $isAdmin = request()->is('admin*');
    @endphp

    <div class="max-w-xl w-full bg-white rounded-3xl border-2 border-[#E2DAC8] p-6 sm:p-10 shadow-xl text-center space-y-6 sm:space-y-8">
        
        <!-- Header Brand & Badge -->
        <div class="flex items-center justify-between border-b border-[#EAE3D2] pb-5">
            <div class="flex items-center gap-3 text-left">
                <div class="w-12 h-12 rounded-2xl bg-[#FAF8F5] border border-[#E2DAC8] flex items-center justify-center p-2 shadow-xs shrink-0">
                    <img src="{{ asset($appLogo) }}" alt="{{ $appName }}" class="max-w-full max-h-full object-contain">
                </div>
                <div>
                    <div class="font-extrabold text-stone-900 text-sm tracking-tight">{{ $appName }}</div>
                    <div class="text-[11px] font-bold text-[#1C5A2C] font-kannada">{{ $appNameKn }}</div>
                </div>
            </div>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Error 404
            </div>
        </div>

        <!-- 404 Visual Icon & Numbers -->
        <div class="space-y-2">
            <div class="text-6xl sm:text-7xl font-black tracking-tight text-stone-900 flex items-center justify-center gap-2">
                <span>4</span>
                <span class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-[#1C5A2C]/10 border-2 border-[#1C5A2C]/20 text-3xl sm:text-4xl text-[#1C5A2C]">
                    🔍
                </span>
                <span>4</span>
            </div>
            <div class="text-xs font-black tracking-widest text-[#1C5A2C] uppercase">
                ಪುಟ ಕಂಡುಬಂದಿಲ್ಲ • Page Not Found
            </div>
        </div>

        <!-- Explanatory Messages -->
        <div class="space-y-2">
            <h1 class="text-lg sm:text-xl font-black text-stone-900 font-kannada leading-snug">
                ಕ್ಷಮಿಸಿ! ನೀವು ಹುಡುಕುತ್ತಿರುವ ವೆಬ್ ಪುಟ ಲಭ್ಯವಿಲ್ಲ
            </h1>
            <p class="text-xs sm:text-sm text-stone-600 leading-relaxed">
                ನೀವು ನಮೂದಿಸಿದ ಲಿಂಕ್ ತಪ್ಪಾಗಿರಬಹುದು ಅಥವಾ ಈ ಪುಟವನ್ನು ತೆಗೆದುಹಾಕಲಾಗಿರಬಹುದು.
                <span class="block mt-1 text-stone-400 text-xs">
                    The requested URL could not be found on this server. It may have been moved or deleted.
                </span>
            </p>
        </div>

        @if($isAdmin)
            <!-- Admin Panel Specific Context -->
            <div class="bg-amber-50 rounded-2xl border border-amber-200 p-4 text-xs text-amber-900 text-left space-y-2">
                <div class="flex items-center gap-2 font-bold text-amber-800">
                    <span>🛡️</span>
                    <span>ನಿರ್ವಾಹಕ ಪೋರ್ಟಲ್ ಸೂಚನೆ (Admin Portal Notice)</span>
                </div>
                <p class="text-[11px] text-amber-700 leading-relaxed">
                    You attempted to access an admin endpoint (<code class="bg-amber-100 px-1 py-0.5 rounded text-amber-900 font-mono text-[10px]">{{ request()->path() }}</code>) that does not exist or has been restructured.
                </p>
                <div class="pt-2 flex flex-wrap gap-2">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#1C5A2C] text-white text-xs font-bold hover:bg-[#154622] transition shadow-xs">
                        📊 Dashboard / ಡ್ಯಾಶ್‌ಬೋರ್ಡ್
                    </a>
                    <a href="{{ route('admin.prices.index') }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-stone-300 text-stone-700 text-xs font-bold hover:bg-stone-50 transition">
                        🌾 Mandi Prices / ಮಂಡಿ ದರಗಳು
                    </a>
                </div>
            </div>
        @else
            <!-- Search Suggestion Bar for Farmers -->
            <form action="{{ url('/crops') }}" method="GET" class="relative max-w-md mx-auto">
                <div class="flex items-center rounded-2xl border-2 border-[#E2DAC8] bg-[#FAF8F5] focus-within:border-[#1C5A2C] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#1C5A2C]/20 transition p-1">
                    <div class="pl-3 pr-2 text-stone-400 text-base">
                        🌾
                    </div>
                    <input type="text" 
                           name="search" 
                           placeholder="ಬೆಳೆ ಅಥವಾ ಮಂಡಿ ಹುಡುಕಿ (Search Crop / Mandi)..." 
                           class="w-full bg-transparent border-0 text-xs sm:text-sm text-stone-800 focus:outline-none placeholder-stone-400 py-2">
                    <button type="submit" 
                            class="px-4 py-2 rounded-xl bg-[#1C5A2C] text-white text-xs font-bold hover:bg-[#154622] transition shrink-0">
                        ಹುಡುಕಿ
                    </button>
                </div>
            </form>
        @endif

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <a href="{{ url('/') }}" 
               class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-[#1C5A2C] hover:bg-[#154622] text-white font-bold text-xs transition shadow-md active:scale-98">
                <span>🏠</span>
                <span>ಮುಖಪುಟಕ್ಕೆ ಹೋಗಿ (Home)</span>
            </a>

            <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/') }}'" 
                    class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-white hover:bg-stone-50 border-2 border-[#E2DAC8] text-stone-700 font-bold text-xs transition shadow-xs active:scale-98 cursor-pointer">
                <span>🔙</span>
                <span>ಹಿಂದಿನ ಪುಟ (Go Back)</span>
            </button>
        </div>

        <!-- Quick Farmer Direct Links -->
        <div class="pt-4 border-t border-[#EAE3D2] flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs">
            <a href="{{ url('/crops') }}" class="font-bold text-[#1C5A2C] hover:underline flex items-center gap-1">
                <span>🌾</span> ಮಂಡಿ ದರಗಳು
            </a>
            <span class="text-stone-300">•</span>
            <a href="{{ url('/schemes') }}" class="font-bold text-[#1C5A2C] hover:underline flex items-center gap-1">
                <span>📋</span> ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು
            </a>
            <span class="text-stone-300">•</span>
            <a href="{{ url('/feedback') }}" class="font-bold text-[#1C5A2C] hover:underline flex items-center gap-1">
                <span>💬</span> ರೈತ ಸಹಾಯವಾಣಿ
            </a>
        </div>
    </div>
</body>
</html>
