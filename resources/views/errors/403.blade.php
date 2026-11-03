<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>೪೦೩ — ಪ್ರವೇಶ ನಿರಾಕರಿಸಲಾಗಿದೆ | 403 Forbidden</title>
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

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-700 text-xs font-bold">
                Error 403
            </div>
        </div>

        <!-- 403 Visual -->
        <div class="space-y-2">
            <div class="text-6xl sm:text-7xl font-black tracking-tight text-stone-900 flex items-center justify-center gap-2">
                <span>4</span>
                <span class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-rose-500/10 border-2 border-rose-500/20 text-3xl sm:text-4xl text-rose-600">
                    🔒
                </span>
                <span>3</span>
            </div>
            <div class="text-xs font-black tracking-widest text-rose-700 uppercase">
                ಪ್ರವೇಶ ನಿರಾಕರಿಸಲಾಗಿದೆ • Access Forbidden
            </div>
        </div>

        <!-- Explanatory Messages -->
        <div class="space-y-2">
            <h1 class="text-lg sm:text-xl font-black text-stone-900 font-kannada leading-snug">
                ಈ ಪುಟವನ್ನು ವೀಕ್ಷಿಸಲು ಅಥವಾ ನಿರ್ವಹಿಸಲು ನಿಮಗೆ ಅನುಮತಿ ಇಲ್ಲ
            </h1>
            <p class="text-xs sm:text-sm text-stone-600 leading-relaxed">
                ಈ ವಿಭಾಗವು ಅಧಿಕೃತ ನಿರ್ವಾಹಕರಿಗೆ ಮಾತ್ರ ಸೀಮಿತವಾಗಿದೆ. ದಯವಿಟ್ಟು ಸೂಕ್ತ ಖಾತೆಯೊಂದಿಗೆ ಲಾಗಿನ್ ಮಾಡಿ.
                <span class="block mt-1 text-stone-400 text-xs">
                    You do not have the required permissions or roles to access this protected resource.
                </span>
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <a href="{{ route('admin.login') }}" 
               class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-[#1C5A2C] hover:bg-[#154622] text-white font-bold text-xs transition shadow-md active:scale-98">
                <span>🔐</span>
                <span>ಅಡ್ಮಿನ್ ಲಾಗಿನ್ (Admin Login)</span>
            </a>

            <a href="{{ url('/') }}" 
               class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-white hover:bg-stone-50 border-2 border-[#E2DAC8] text-stone-700 font-bold text-xs transition shadow-xs active:scale-98">
                <span>🏠</span>
                <span>ಮುಖಪುಟಕ್ಕೆ ಹೋಗಿ (Home)</span>
            </a>
        </div>
    </div>
</body>
</html>
