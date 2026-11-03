<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>೪೨೯ — ಅತಿಯಾದ ವಿನಂತಿಗಳು | 429 Too Many Requests</title>
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

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-700 text-xs font-bold">
                Error 429
            </div>
        </div>

        <!-- 429 Visual -->
        <div class="space-y-2">
            <div class="text-6xl sm:text-7xl font-black tracking-tight text-stone-900 flex items-center justify-center gap-2">
                <span>4</span>
                <span class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-purple-500/10 border-2 border-purple-500/20 text-3xl sm:text-4xl text-purple-600">
                    ⏱️
                </span>
                <span>9</span>
            </div>
            <div class="text-xs font-black tracking-widest text-purple-700 uppercase">
                ಅತಿಯಾದ ವಿನಂತಿಗಳು • Too Many Requests
            </div>
        </div>

        <!-- Explanatory Messages -->
        <div class="space-y-2">
            <h1 class="text-lg sm:text-xl font-black text-stone-900 font-kannada leading-snug">
                ಸ್ವಲ್ಪ ಸಮಯ ಕಾಯಿರಿ (ವಿನಂತಿ ಮಿತಿ ಮೀರಿದೆ)
            </h1>
            <p class="text-xs sm:text-sm text-stone-600 leading-relaxed">
                ನೀವು ಅಲ್ಪಾವಧಿಯಲ್ಲಿ ಹಲವಾರು ಬಾರಿ ವಿನಂತಿ ಕಳುಹಿಸಿದ್ದೀರಿ. ದಯವಿಟ್ಟು 1 ನಿಮಿಷ ನಿರೀಕ್ಷಿಸಿ ಪುನಃ ಪ್ರಯತ್ನಿಸಿ.
                <span class="block mt-1 text-stone-400 text-xs">
                    You have sent too many requests in a short period. Please wait a moment before trying again.
                </span>
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <button onclick="window.location.reload()" 
                    class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-[#1C5A2C] hover:bg-[#154622] text-white font-bold text-xs transition shadow-md active:scale-98 cursor-pointer">
                <span>🔄</span>
                <span>ಪುನಃ ಪ್ರಯತ್ನಿಸಿ (Retry)</span>
            </button>

            <a href="{{ url('/') }}" 
               class="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-white hover:bg-stone-50 border-2 border-[#E2DAC8] text-stone-700 font-bold text-xs transition shadow-xs active:scale-98">
                <span>🏠</span>
                <span>ಮುಖಪುಟಕ್ಕೆ ಹೋಗಿ (Home)</span>
            </a>
        </div>
    </div>
</body>
</html>
