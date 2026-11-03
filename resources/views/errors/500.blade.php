<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>೫೦೦ — ಸರ್ವರ್ ದೋಷ | 500 Server Error</title>
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

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-red-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                Error 500
            </div>
        </div>

        <!-- 500 Visual Icon & Numbers -->
        <div class="space-y-2">
            <div class="text-6xl sm:text-7xl font-black tracking-tight text-stone-900 flex items-center justify-center gap-2">
                <span>5</span>
                <span class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-amber-500/10 border-2 border-amber-500/30 text-3xl sm:text-4xl text-amber-600">
                    ⚙️
                </span>
                <span>0</span>
            </div>
            <div class="text-xs font-black tracking-widest text-amber-700 uppercase">
                ಸರ್ವರ್ ದೋಷ • Internal Server Error
            </div>
        </div>

        <!-- Explanatory Messages -->
        <div class="space-y-2">
            <h1 class="text-lg sm:text-xl font-black text-stone-900 font-kannada leading-snug">
                ಕ್ಷಮಿಸಿ, ಸರ್ವರ್‌ನಲ್ಲಿ ಅನಿರೀಕ್ಷಿತ ತಾಂತ್ರಿಕ ತೊಂದರೆ ಎದುರಾಗಿದೆ
            </h1>
            <p class="text-xs sm:text-sm text-stone-600 leading-relaxed">
                ನಮ್ಮ ತಂತ್ರಜ್ಞರ ತಂಡಕ್ಕೆ ಈಗಾಗಲೇ ಈ ದೋಷದ ಮಾಹಿತಿ ತಲುಪಿದೆ. ದಯವಿಟ್ಟು ಕೆಲವು ಕ್ಷಣಗಳ ನಂತರ ಪುಟವನ್ನು ರಿಫ್ರೆಶ್ ಮಾಡಿ ಅಥವಾ ಮುಖಪುಟಕ್ಕೆ ಹಿಂತಿರುಗಿ.
                <span class="block mt-1 text-stone-400 text-xs">
                    An unexpected error occurred while processing your request. The issue has been logged by our system.
                </span>
            </p>
        </div>

        <!-- Info Notice Box -->
        <div class="bg-[#FAF8F5] rounded-2xl border border-[#E2DAC8] p-4 text-xs text-stone-600 text-left space-y-2.5">
            <div class="flex items-start gap-2.5">
                <span class="text-emerald-700 font-bold shrink-0 text-sm">💡</span>
                <span class="leading-relaxed">
                    <strong>ಏನು ಮಾಡಬಹುದು? (What you can do):</strong><br>
                    • ಪುಟವನ್ನು ರಿಫ್ರೆಶ್ ಮಾಡಿ (Refresh page)<br>
                    • ಇಂಟರ್ನೆಟ್ ಸಂಪರ್ಕವನ್ನು ಪರಿಶೀಲಿಸಿ (Check network connection)<br>
                    • ಸಮಸ್ಯೆ ಮುಂದುವರಿದರೆ ನಮ್ಮ ಸಹಾಯವಾಣಿಗೆ ಸಂದೇಶ ಕಳುಹಿಸಿ (Contact helpdesk)
                </span>
            </div>
        </div>

        @if(config('app.debug') && !app()->isProduction() && isset($exception))
            <!-- Safe Debug Accordion for Developers (Only when APP_DEBUG=true & not in production) -->
            <details class="text-left bg-stone-900 text-stone-200 rounded-2xl p-4 text-xs font-mono border border-stone-700">
                <summary class="cursor-pointer font-bold text-amber-400 hover:text-amber-300">
                    🛠️ Developer Diagnostics (Debug Mode Active)
                </summary>
                <div class="mt-3 space-y-1 overflow-x-auto text-[11px]">
                    <div class="text-red-400 font-bold">{{ get_class($exception) }}</div>
                    <div class="text-stone-300">{{ $exception->getMessage() }}</div>
                    <div class="text-stone-500 pt-1 text-[10px]">{{ $exception->getFile() }}:{{ $exception->getLine() }}</div>
                </div>
            </details>
        @endif

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <button onclick="window.location.reload()" 
                    class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-[#1C5A2C] hover:bg-[#154622] text-white font-bold text-xs transition shadow-md active:scale-98 cursor-pointer">
                <span>🔄</span>
                <span>ಪುಟ ರಿಫ್ರೆಶ್ ಮಾಡಿ (Try Again)</span>
            </button>

            <a href="{{ $isAdmin ? route('admin.dashboard') : url('/') }}" 
               class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-white hover:bg-stone-50 border-2 border-[#E2DAC8] text-stone-700 font-bold text-xs transition shadow-xs active:scale-98">
                <span>🏠</span>
                <span>{{ $isAdmin ? 'ಅಡ್ಮಿನ್ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್ (Dashboard)' : 'ಮುಖಪುಟಕ್ಕೆ ಹೋಗಿ (Home)' }}</span>
            </a>
        </div>

        <!-- Secondary Support Link -->
        <div class="pt-4 border-t border-[#EAE3D2] flex items-center justify-center gap-4 text-xs text-stone-500">
            <a href="{{ url('/feedback') }}" class="font-bold text-[#1C5A2C] hover:underline flex items-center gap-1">
                <span>💬</span> ಸಮಸ್ಯೆ ವರದಿ ಮಾಡಿ (Report Issue)
            </a>
            <span>•</span>
            <a href="{{ route('admin.login') }}" class="hover:text-stone-700 transition">
                ಅಡ್ಮಿನ್ ಲಾಗಿನ್ (Admin Portal)
            </a>
        </div>
    </div>
</body>
</html>
