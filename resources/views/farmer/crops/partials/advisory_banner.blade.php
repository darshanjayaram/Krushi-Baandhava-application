@php
    $h7Horizon = collect($forecast['horizons'] ?? [])->firstWhere('horizon_days', 7);
    $advisoryPct = $h7Horizon ? abs($h7Horizon['percentage_change']) : null;
    $advisoryConf = $h7Horizon ? (int)($h7Horizon['confidence_score'] ?? 0) : null;
    $advisoryDir = $h7Horizon['direction'] ?? $forecastDir;
@endphp
<div class="px-3.5 py-2.5 sm:px-4 sm:py-3 {{ $forecastDir === 'down' ? 'bg-amber-50/90' : ($forecastDir === 'up' ? 'bg-emerald-50/90' : 'bg-stone-50') }}">
    <div class="flex items-start gap-2.5">
        <!-- Left accent stripe -->
        <div class="w-1 self-stretch rounded-full shrink-0 {{ $forecastDir === 'down' ? 'bg-amber-400' : ($forecastDir === 'up' ? 'bg-emerald-500' : 'bg-stone-400') }}"></div>

        <div class="flex-1 space-y-1">
            <!-- Title row -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-base sm:text-lg leading-none">{{ $forecastDir === 'down' ? '⏰' : ($forecastDir === 'up' ? '📈' : '💡') }}</span>
                <div class="font-extrabold text-xs sm:text-sm {{ $forecastDir === 'down' ? 'text-amber-950' : ($forecastDir === 'up' ? 'text-emerald-950' : 'text-stone-800') }} {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    @if($forecastDir === 'down')
                        {{ $activeLocale === 'en' ? 'Optimal Time to Sell (Sell Now)' : 'ಮಾರಾಟಕ್ಕೆ ಸೂಕ್ತ ಸಮಯ' }}
                    @elseif($forecastDir === 'up')
                        {{ $activeLocale === 'en' ? 'Price Rise Expected (Hold / Watch)' : 'ಧಾರಣೆ ಏರಿಕೆಯ ಮುನ್ಸೂಚನೆ' }}
                    @else
                        {{ $activeLocale === 'en' ? 'Market Stable (Monitor)' : 'ಮಾರುಕಟ್ಟೆ ಸ್ಥಿರ — ಗಮನಿಸಿ' }}
                    @endif
                </div>
            </div>

            <!-- Body text -->
            <p class="text-[11px] sm:text-xs leading-snug {{ $forecastDir === 'down' ? 'text-amber-800' : ($forecastDir === 'up' ? 'text-emerald-800' : 'text-stone-600') }} {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                @if($forecastDir === 'down')
                    {{ $activeLocale === 'en' ? 'Arrivals are expected to increase over the coming weeks, which may cause prices to soften. Selling at current favorable rates is advisable.' : 'ಮುಂದಿನ ವಾರಗಳಲ್ಲಿ ಮಾರುಕಟ್ಟೆಗೆ ಆವಕ ಹೆಚ್ಚಾಗುವ ಮುನ್ಸೂಚನೆ ಇದ್ದು, ದರಗಳು ಕೊಂಚ ಇಳಿಕೆಯಾಗುವ ಸಾಧ್ಯತೆಯಿದೆ. ಸದ್ಯದ ಉತ್ತಮ ಬೆಲೆಯಲ್ಲಿ ಮಾರಾಟ ಮಾಡುವುದು ಸೂಕ್ತ.' }}
                @elseif($forecastDir === 'up')
                    {{ $activeLocale === 'en' ? 'Signs of rising demand are observed in regional mandis. Prices may improve further in the coming days.' : 'ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಬೇಡಿಕೆ ಹೆಚ್ಚಾಗುವ ಲಕ್ಷಣಗಳು ಕಂಡುಬರುತ್ತಿದ್ದು, ಮುಂದಿನ ದಿನಗಳಲ್ಲಿ ದರ ಇನ್ನಷ್ಟು ಸುಧಾರಿಸುವ ಸಂಭವವಿದೆ.' }}
                @else
                    {{ $activeLocale === 'en' ? 'Market rates are steady. Consider transportation costs and arrival volumes of nearby mandis before selling.' : 'ಮಾರುಕಟ್ಟೆ ದರಗಳು ಸ್ಥಿರವಾಗಿದ್ದು, ಹತ್ತಿರದ ಮಂಡಿಗಳ ಸಾರಿಗೆ ವೆಚ್ಚ ಮತ್ತು ಆವಕ ಗಮನಿಸಿ ಮಾರಾಟ ನಿರ್ಧಾರ ಕೈಗೊಳ್ಳಿ.' }}
                @endif
            </p>

            <!-- Live forecast stat pills with clear high-contrast numbers -->
            @if($advisoryPct !== null && !empty($forecast['is_sufficient']))
                <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                    <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full border shadow-2xs leading-none {{ $forecastDir === 'up' ? 'bg-emerald-100/90 border-emerald-300' : ($forecastDir === 'down' ? 'bg-amber-100/90 border-amber-300' : 'bg-stone-100 border-stone-300') }}">
                        <span class="inline-flex items-center text-[10px] sm:text-[11px] font-bold text-stone-600 leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? '7-day forecast:' : '7 ದಿನ:' }}
                        </span>
                        <span class="inline-flex items-center text-xs sm:text-[13px] font-black font-sans tracking-tight leading-none {{ $forecastDir === 'up' ? 'text-emerald-700' : ($forecastDir === 'down' ? 'text-red-700' : 'text-stone-800') }}">
                            {{ $forecastDir === 'up' ? '↑ +' : ($forecastDir === 'down' ? '↓ -' : '→ ±') }}{{ $advisoryPct }}%
                        </span>
                    </span>
                    @if($advisoryConf > 0)
                        <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full border border-slate-300 bg-slate-100/90 text-slate-900 shadow-2xs leading-none">
                            <span class="inline-flex items-center text-[10px] sm:text-[11px] font-bold text-slate-500 leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $activeLocale === 'en' ? 'Confidence:' : 'ವಿಶ್ವಾಸ:' }}
                            </span>
                            <span class="inline-flex items-center text-xs sm:text-[13px] font-black font-sans text-slate-900 tracking-tight leading-none">
                                {{ $advisoryConf }}%
                            </span>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
