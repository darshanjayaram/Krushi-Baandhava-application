    <!-- 4. "What's next" Forecast Horizons (Compact Classic 2-Column Mobile & 4-Column Desktop) -->
    <div class="bg-white rounded-2xl sm:rounded-3xl p-3 sm:p-6 border-2 border-[#D9CEB8] shadow-sm space-y-3.5 sm:space-y-5 overflow-hidden">
        
        <!-- Section Header with Classic Editorial Layout -->
        <div class="flex items-center justify-between gap-3 pb-3 border-b-2 border-[#F0EAE1]">
            <div class="flex items-start gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full bg-[#1C5A2C] shrink-0 mt-0.5"></span>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? 'When to Sell? — Price Forecast' : 'ಯಾವಾಗ ಮಾರಬೇಕು? — ಬೆಲೆ ಮುನ್ಸೂಚನೆ' }}
                        </h2>
                        @if($activePriceItem && $activePriceItem->variety)
                            <span class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-full text-[10px] sm:text-[11px] font-black bg-[#FAF6EE] text-[#1C5A2C] border border-[#D9CEB8] shadow-2xs leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                <span class="inline-flex items-center leading-none">{{ $activePriceItem->getDisplayVarietyGrade($activeLocale) }}</span>
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? '1 to 15-day projected price movement & market direction' : 'ಮುಂದಿನ 15 ದಿನಗಳ ನಿರೀಕ್ಷಿತ ದರ ಶ್ರೇಣಿ ಮತ್ತು ಮಾರುಕಟ್ಟೆ ಪ್ರವೃತ್ತಿ' }}
                    </p>
                </div>
            </div>
            <div class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-300 text-emerald-900 text-[10px] sm:text-[11px] font-black shadow-2xs shrink-0 leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#1C5A2C] animate-pulse shrink-0"></span>
                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Updated daily' : 'ದೈನಂದಿನ ಅಪ್ಡೇಟ್' }}</span>
            </div>
        </div>

        @if(!empty($forecast['is_sufficient']) && !empty($forecast['horizons']))
            <!-- Farmer Verdict Banner (Instant Actionable Recommendation) -->
            @if(!empty($forecast['verdict_kn']))
                <div class="px-3.5 py-2.5 rounded-xl bg-[#FAF8F5] border border-[#E5DECE] flex items-center justify-between gap-2.5 flex-wrap">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-[#1C5A2C] text-xs font-black shrink-0">
                            💡
                        </span>
                        <div class="text-xs sm:text-sm font-black text-[#1C5A2C] {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? ($forecast['verdict_en'] ?? $forecast['verdict_kn']) : $forecast['verdict_kn'] }}
                        </div>
                    </div>
                    @if(!empty($forecast['why_summary_kn']))
                        <div class="text-[11px] text-stone-600 font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                            {{ $activeLocale === 'en' ? ($forecast['why_summary_en'] ?? '') : $forecast['why_summary_kn'] }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- 4-Card Forecast Grid (2 Columns on Mobile, 4 Columns on Desktop) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3.5">
                @foreach($forecast['horizons'] as $idx => $h)
                    @php
                        $hDays = $h['horizon_days'] ?? ($h['horizon'] ?? 1);
                        $horizonTitles = [
                            1 => ['en' => 'TOMORROW', 'kn' => 'ನಾಳೆ'],
                            7 => ['en' => 'NEXT WEEK', 'kn' => 'ಮುಂದಿನ ವಾರ'],
                            15 => ['en' => 'FORTNIGHT', 'kn' => '15 ದಿನ (ಪಕ್ಷ)'],
                            30 => ['en' => 'NEXT MONTH', 'kn' => 'ಮುಂದಿನ ತಿಂಗಳು'],
                        ];
                        $horizonMeta = $horizonTitles[$hDays] ?? ['en' => "+{$hDays} DAYS", 'kn' => $h['label_kn'] ?? 'ಮುನ್ಸೂಚನೆ'];
                        $shortDate = !empty($h['target_date']) ? \Carbon\Carbon::parse($h['target_date'])->format('d M') : str_replace(' ' . date('Y'), '', $h['target_date_formatted'] ?? '');

                        $absPct = abs($h['percentage_change']);
                        $dir = $h['direction'] ?? 'steady';
                        if ($dir === 'up') {
                            $heroChange = "↑ +{$absPct}%";
                            $arrow = "↑";
                            $dirColorClass = "text-[#16803C]";
                            $accentColor = "bg-[#16803C]";
                        } elseif ($dir === 'down') {
                            $heroChange = "↓ -{$absPct}%";
                            $arrow = "↓";
                            $dirColorClass = "text-[#C0392B]";
                            $accentColor = "bg-[#C0392B]";
                        } else {
                            $heroChange = "→ ≈ 0%";
                            $arrow = "→";
                            $dirColorClass = "text-[#B45309]";
                            $accentColor = "bg-amber-600";
                        }

                        $confScore = (float)($h['confidence_score'] ?? 50);
                        if ($confScore >= 70) {
                            $qualLabelEn = 'LIKELY';
                            $qualLabelKn = 'ಹೆಚ್ಚು ಸಾಧ್ಯತೆ';
                            $confColor = '#16803C';
                            $confTextClass = 'text-[#16803C]';
                        } elseif ($confScore >= 50) {
                            $qualLabelEn = 'POSSIBLE';
                            $qualLabelKn = 'ಸಾಧ್ಯತೆ ಇದೆ';
                            $confColor = '#D97706';
                            $confTextClass = 'text-[#B45309]';
                        } else {
                            $qualLabelEn = 'LESS LIKELY';
                            $qualLabelKn = 'ಸಾಧ್ಯತೆ ಕಡಿಮೆ';
                            $confColor = '#9CA3AF';
                            $confTextClass = 'text-stone-600';
                        }
                    @endphp
                    <div class="bg-[#FAF8F5] rounded-xl sm:rounded-2xl p-2 sm:p-4 border-2 border-[#E5DECE] hover:border-[#1C5A2C] shadow-2xs transition-all duration-200 flex flex-col justify-between space-y-2 sm:space-y-3 relative overflow-hidden group">
                        
                        <!-- Top Accent Line -->
                        <div class="absolute top-0 left-0 right-0 h-1 {{ $accentColor }} transition-colors"></div>

                        <!-- Card Header: Brand Green Horizon Pill & Short Target Date -->
                        <div class="flex items-center justify-between gap-1 pt-0.5">
                            <span class="inline-flex items-center justify-center px-2 py-1 rounded bg-[#1C5A2C] text-white text-[9px] sm:text-[11px] font-black tracking-wider uppercase font-sans shadow-2xs border border-[#1C5A2C] leading-none {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? $horizonMeta['en'] : $horizonMeta['kn'] }}</span>
                            </span>
                            
                            <span class="text-[9px] sm:text-xs font-bold text-stone-500 font-sans whitespace-nowrap leading-none inline-flex items-center">
                                {{ $shortDate }}
                            </span>
                        </div>

                        <!-- Hero Metric: Expected Movement Percentage -->
                        <div class="space-y-0.5 sm:space-y-1">
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl sm:text-2xl lg:text-[32px] font-black tracking-tight font-sans leading-none {{ $dirColorClass }}">
                                    {{ $heroChange }}
                                </span>
                            </div>

                            <!-- Sub-line: Predicted Target Price with Direction Arrow -->
                            <div class="flex items-baseline gap-1 text-stone-900 font-sans flex-wrap leading-tight">
                                <span class="text-xs sm:text-sm lg:text-base font-black tracking-tight">
                                    ₹{{ number_format($h['expected_price'], 0) }}
                                </span>
                                <span class="text-xs font-black {{ $dirColorClass }}">
                                    {{ $arrow }}
                                </span>
                                <span class="text-[9px] sm:text-[11px] font-semibold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                    /{{ $activeLocale === 'en' ? strtolower($crop->standard_unit ?? 'quintal') : 'ಕ್ವಿಂ' }}
                                </span>
                            </div>
                        </div>

                        <!-- Bottom Section: Auction Range & Confidence -->
                        <div class="pt-1.5 sm:pt-2.5 border-t border-[#EAE3D2] space-y-1.5 sm:space-y-2 text-xs">
                            <!-- Expected Trading Range -->
                            <div class="text-[9.5px] sm:text-xs text-stone-600 font-sans leading-tight">
                                <span class="font-bold text-stone-400 {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                    {{ $activeLocale === 'en' ? 'range: ' : 'ಶ್ರೇಣಿ: ' }}
                                </span>
                                <span class="font-black text-stone-800 whitespace-nowrap">
                                    ₹{{ number_format($h['lower_bound'], 0) }} – ₹{{ number_format($h['upper_bound'], 0) }}
                                </span>
                            </div>

                            <!-- Confidence Score & Progress Bar (with Qualitative Status) -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-1 text-[8.5px] sm:text-[10px] font-bold tracking-wider uppercase">
                                    <span class="text-stone-400 font-extrabold whitespace-nowrap {{ $activeLocale === 'kn' ? 'font-kannada' : '' }}">
                                        {{ $activeLocale === 'en' ? 'CONFIDENCE' : 'ವಿಶ್ವಾಸ' }}
                                    </span>
                                    <span class="font-black font-sans whitespace-nowrap text-right {{ $confTextClass }} {{ $activeLocale === 'kn' ? 'font-kannada text-[8px] sm:text-[9.5px]' : '' }}">
                                        {{ $activeLocale === 'en' ? $qualLabelEn : $qualLabelKn }}
                                    </span>
                                </div>
                                <div class="w-full bg-[#E5DECE] h-1.5 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" 
                                         style="width: {{ min(100, max(10, $confScore)) }}%; background-color: {{ $confColor }};">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <!-- Model Accuracy Caveat for Volatile Crops -->
            @if(!empty($forecast['is_high_volatility']))
                <div class="p-3 rounded-xl bg-amber-50/90 border border-amber-300/80 text-[11.5px] font-medium text-amber-900 flex items-start gap-2.5 leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span class="text-amber-700 shrink-0 text-sm">⚠️</span>
                    <span>
                        {{ $activeLocale === 'en' ? ($forecast['caveat_en'] ?? 'The model is subject to market arrival volatility for this crop — treat these projections as an informative indicator, not an absolute guarantee.') : ($forecast['caveat_kn'] ?? 'ಈ ಬೆಳೆಗೆ ಮಾರುಕಟ್ಟೆ ಆವಕದ ಏರಿಳಿತ ಹೆಚ್ಚಿರುತ್ತದೆ — ಈ ಮುನ್ಸೂಚನೆಯನ್ನು ಮಾಹಿತಿ ಮಾರ್ಗದರ್ಶಿಯಾಗಿ ಪರಿಗಣಿಸಿ, ಖಚಿತ ಗ್ಯಾರಂಟಿ ಅಲ್ಲ.') }}
                    </span>
                </div>
            @endif

        @else
            <!-- Data Insufficiency Notice -->
            <div class="p-4 rounded-2xl bg-amber-50/80 border-2 border-amber-200 text-amber-950 flex items-start gap-3">
                <span class="text-xl shrink-0">ℹ️</span>
                <div class="space-y-1 text-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <div class="font-bold text-sm text-amber-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">{{ $activeLocale === 'en' ? 'Data Insufficiency Notice' : 'ದರ ಮಾಹಿತಿ ಕೊರತೆ ಸೂಚನೆ' }}</div>
                    <p class="leading-relaxed">
                        {{ $activeLocale === 'en' ? ($forecast['message_en'] ?? 'Minimum 30 days of market prices required for a reliable forecast.') : ($forecast['message_kn'] ?? 'ವಿಶ್ವಾಸಾರ್ಹ ಮುನ್ಸೂಚನೆಗೆ ಕನಿಷ್ಠ 30 ದಿನಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ.') }}
                    </p>
                    <p class="text-amber-800/80">
                        {{ $activeLocale === 'en' 
                            ? 'Krushi Baandhava does not generate synthetic prices. Projections will automatically activate once 30 continuous days of mandi records are logged.' 
                            : 'ಕೃಷಿ ಬಾಂಧವ ಕೃತಕ ಅಂದಾಜುಗಳನ್ನು ಪ್ರದರ್ಶಿಸುವುದಿಲ್ಲ. ಮಂಡಿಗಳಿಂದ 30 ದಿನಗಳ ನಿರಂತರ ದರಗಳು ದಾಖಲಾದ ನಂತರ ನಿಖರ ಗಣಿತೀಯ ಮುನ್ಸೂಚನೆ ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.' }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Disclaimer -->
        <div class="rounded-xl p-2.5 sm:p-3 bg-stone-50 border border-stone-200 text-stone-500 flex items-start gap-2 text-[11px] leading-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            <span class="text-sm shrink-0">📊</span>
            <p>
                <strong class="font-bold text-stone-700">{{ $activeLocale === 'en' ? 'Disclaimer: ' : 'ಹಕ್ಕುತ್ಯಾಗ: ' }}</strong>
                <span>
                    {{ $activeLocale === 'en' 
                        ? ($forecast['disclaimer_en'] ?? 'Mathematical estimation based on past price patterns. Actual realized rates may vary based on weather, daily market arrival volumes, and government trade policies.') 
                        : ($forecast['disclaimer_kn'] ?? 'ಇದು ಕೇವಲ ಹಿಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳ ಪ್ರವೃತ್ತಿ ಆಧಾರಿತ ಗಣಿತೀಯ ಅಂದಾಜು. ನೈಜ ದರಗಳು ಹವಾಮಾನ ಪರಿಸ್ಥಿತಿ, ಮಾರುಕಟ್ಟೆಯ ಆವಕ ಪ್ರಮಾಣ ಮತ್ತು ಸರ್ಕಾರದ ನೀತಿಗಳಿಂದ ವ್ಯತ್ಯಾಸವಾಗಬಹುದು.') }}
                </span>
            </p>
        </div>
    </div>
