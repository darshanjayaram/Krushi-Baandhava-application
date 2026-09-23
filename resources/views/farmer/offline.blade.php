@extends('layouts.farmer')

@section('content')
<div class="max-w-xl mx-auto px-4 py-12 sm:py-16 text-center">

    <!-- Offline Visual Signal -->
    <div class="w-24 h-24 mx-auto rounded-3xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 mb-6 shadow-sm">
        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-5.657-2.615m0 0l2.829-2.829m-2.829 2.829L3 21m5.657-8.485a5 5 0 01-1.414-3.536m0 0l2.829 2.829m1.414-5.657L3 3" />
        </svg>
    </div>

    <!-- Bilingual Headline -->
    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
        You're Currently Offline
    </h1>
    <p class="text-lg font-bold text-emerald-800 mt-1 font-kannada">
        ನೀವು ಪ್ರಸ್ತುತ ಆಫ್‌ಲೈನ್‌ನಲ್ಲಿದ್ದೀರಿ
    </p>

    <!-- Information Card -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm mt-6 text-left space-y-4">
        <div class="flex items-start gap-3">
            <span class="text-xl">🌾</span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Cached Rates Remain Available</h2>
                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                    Any APMC mandi prices, weather forecasts, or guides you have recently viewed on Krushi Baandhava are saved in your phone's offline storage.
                </p>
                <p class="text-xs text-emerald-700 font-medium mt-1 font-kannada leading-relaxed">
                    ನೀವು ಇತ್ತೀಚೆಗೆ ವೀಕ್ಷಿಸಿದ ಎಪಿಎಂಸಿ ದರಗಳು ಮತ್ತು ಮಾಹಿತಿಯನ್ನು ಆಫ್‌ಲೈನ್‌ನಲ್ಲೂ ನೋಡಬಹುದು.
                </p>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-start gap-3">
            <span class="text-xl">🔄</span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Automatic Live Reconnection</h2>
                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                    As soon as your cellular signal (4G/5G) or Wi-Fi connection returns, the app will automatically refresh with live market prices.
                </p>
                <p class="text-xs text-emerald-700 font-medium mt-1 font-kannada leading-relaxed">
                    ನೆಟ್‌ವರ್ಕ್ ಸಿಕ್ಕ ತಕ್ಷಣ ಲೈವ್ ಮಾರುಕಟ್ಟೆ ದರಗಳು ತಾನಾಗಿಯೇ ಅಪ್‌ಡೇಟ್ ಆಗುತ್ತವೆ.
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Action Controls -->
    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <button onclick="window.location.reload()" 
                class="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Try Reconnecting / ಮರುಪ್ರಯತ್ನಿಸಿ</span>
        </button>

        <a href="{{ route('home') }}" 
           class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-sm border border-slate-200 transition">
            Go to Home / ಮುಖಪುಟ
        </a>

        <a href="{{ route('farmer.crops.index') }}" 
           class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-sm border border-slate-200 transition">
            Crops / ಬೆಳೆಗಳು
        </a>
    </div>

    <!-- Tip Card -->
    <div class="mt-8 text-xs text-slate-500 font-medium max-w-md mx-auto">
        💡 <span class="font-semibold text-slate-700">ಸಲಹೆ:</span> ಕೃಷಿ ಬಾಂಧವ ಆ್ಯಪ್ ಅನ್ನು ನಿಮ್ಮ ಫೋನ್ ಮುಖಪುಟಕ್ಕೆ (Add to Home Screen) ಸೇರಿಸಿಕೊಳ್ಳಿ, ಇದರಿಂದ ತೋಟದಲ್ಲಿ ನೆಟ್‌ವರ್ಕ್ ಇಲ್ಲದಿದ್ದರೂ ಹಿಂದಿನ ದರಗಳನ್ನು ವೀಕ್ಷಿಸಬಹುದು.
    </div>

</div>
@endsection
