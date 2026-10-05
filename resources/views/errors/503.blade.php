<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ಸಿಸ್ಟಮ್ ನಿರ್ವಹಣೆ — System Maintenance | {{ \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava') }}</title>
    @vite(['resources/css/app.css'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tiro+Kannada:ital@0;1&family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .font-kannada { font-family: 'Tiro Kannada', serif; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FAF8F5; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased text-stone-800">
    <div class="max-w-lg w-full bg-white rounded-3xl border-2 border-[#E2DAC8] p-6 sm:p-8 shadow-xl text-center space-y-6">
        @php
            $appLogo = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
            $appName = \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava');
            $appNameKn = \App\Models\SystemSetting::get('application_name_kn', 'ಕೃಷಿ ಬಾಂಧವ');
        @endphp

        <!-- Branding Logo -->
        <div class="w-20 h-20 mx-auto rounded-2xl bg-[#FAF8F5] border-2 border-[#E2DAC8] flex items-center justify-center p-2 shadow-xs">
            <img src="{{ asset($appLogo) }}" alt="{{ $appName }}" class="max-w-full max-h-full object-contain">
        </div>

        <!-- Maintenance Icon -->
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-3xl">
            🛠️
        </div>

        <!-- Heading -->
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-black text-stone-900 font-kannada">
                ಕೃಷಿ ಬಾಂಧವ ತಾತ್ಕಾಲಿಕ ನಿರ್ವಹಣೆಯಲ್ಲಿದೆ
            </h1>
            <p class="text-sm font-bold text-[#1C5A2C]">
                {{ $appName }} is Currently Under Scheduled Maintenance
            </p>
        </div>

        <!-- Info Card -->
        <div class="bg-[#FAF8F5] rounded-2xl border border-[#E2DAC8] p-4 text-xs text-stone-600 text-left space-y-2">
            <div class="flex items-start gap-2">
                <span class="text-emerald-700 font-bold shrink-0">🌾</span>
                <span class="font-kannada text-stone-700 leading-relaxed">
                    ರೈತ ಬಾಂಧವರಿಗೆ ಉತ್ತಮ ಸೇವೆ ಮತ್ತು ನಿಖರ ಮಾರುಕಟ್ಟೆ ದರಗಳನ್ನು ಒದಗಿಸಲು ಸರ್ವರ್ ಸಿಸ್ಟಮ್ ನವೀಕರಣ ಕಾರ್ಯ ನಡೆಯುತ್ತಿದೆ.
                </span>
            </div>
            <div class="flex items-start gap-2 pt-2 border-t border-[#E8E0D0]">
                <span class="text-emerald-700 font-bold shrink-0">⏳</span>
                <span class="leading-relaxed">
                    We are performing routine server optimizations and price database upgrades. Normal access will be restored shortly.
                </span>
            </div>
        </div>

        <!-- Action -->
        <div class="pt-2">
            <button onclick="window.location.reload()" 
                    class="w-full py-3 px-6 rounded-xl bg-[#1C5A2C] hover:bg-[#154622] text-white font-black text-xs uppercase tracking-wider transition shadow-md cursor-pointer active:scale-98">
                🔄 Refresh Page / ಪುಟವನ್ನು ರಿಫ್ರೆಶ್ ಮಾಡಿ
            </button>
        </div>

        <!-- Admin Access Link -->
        <div class="pt-2">
            <a href="{{ route('admin.login') }}" class="text-[11px] text-stone-400 hover:text-stone-600 transition">
                Administrator Portal Login →
            </a>
        </div>
    </div>
</body>
</html>
