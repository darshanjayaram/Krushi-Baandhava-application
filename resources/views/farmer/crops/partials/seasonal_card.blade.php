    <!-- 5. Best Months to Sell — Krushi Harvest Calendar -->
    @if(!empty($seasonalAnalysis['is_sufficient']) && !empty($seasonalAnalysis['best_months']))
    <style>
        /* ── Krushi Harvest Calendar ── */
        .khc-card{background:linear-gradient(148deg,#081A0F 0%,#0F2A1A 38%,#1A4428 65%,#0D2318 100%);border-radius:24px;padding:20px 20px 18px;position:relative;overflow:hidden;box-shadow:0 16px 56px rgba(0,0,0,0.32),inset 0 1px 0 rgba(255,255,255,0.07);}
        .khc-card::after{content:'';position:absolute;inset:0;border-radius:24px;border:1px solid rgba(255,255,255,0.09);pointer-events:none;}
        /* Ambient glows */
        .khc-glow{position:absolute;border-radius:50%;filter:blur(48px);pointer-events:none;}
        /* Header */
        .khc-eyebrow{color:#86EFAC;font-size:9.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px;display:flex;align-items:center;gap:5px;}
        .khc-title{color:#FFFFFF;font-size:20px;font-weight:900;line-height:1.1;}
        .khc-badge-5y{background:rgba(255,255,255,0.09);border:1px solid rgba(255,255,255,0.14);border-radius:20px;padding:4px 12px;color:rgba(255,255,255,0.6);font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0;}
        /* Lead text */
        .khc-lead{color:rgba(255,255,255,0.72);font-size:13px;font-weight:600;line-height:1.65;margin:10px 0 16px;}
        .khc-lead strong{color:#FDE68A;font-weight:900;}
        /* Peak month chip badges */
        .khc-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:35px;}
        .khc-chip{background:linear-gradient(135deg,#7C2D12,#C2410C,#F59E0B);border-radius:14px;padding:5px 13px 5px 8px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 3px 14px rgba(245,158,11,0.28);animation:chipPulse 3.5s ease-in-out infinite;}
        @keyframes chipPulse{0%,100%{transform:scale(1);box-shadow:0 3px 14px rgba(245,158,11,0.28);}50%{transform:scale(1.03);box-shadow:0 5px 22px rgba(245,158,11,0.48);}}
        /* Bar chart */
        .khc-bars{display:flex;align-items:flex-end;gap:4px;height:130px;position:relative;}
        /* Column */
        .khc-col{flex:1;display:flex;flex-direction:column;align-items:center;height:100%;position:relative;cursor:pointer;}
        .khc-col:focus{outline:none;}
        /* Track */
        .khc-track{flex:1;width:100%;background:rgba(255,255,255,0.06);border-radius:6px 6px 0 0;overflow:hidden;display:flex;align-items:flex-end;position:relative;transition:background .2s;}
        .khc-col:hover .khc-track{background:rgba(255,255,255,0.11);}
        /* Fill */
        .khc-fill{width:100%;border-radius:6px 6px 0 0;height:0%;transition:height .85s cubic-bezier(.34,1.4,.64,1);box-sizing:border-box;}
        .khc-fill.pk{background:linear-gradient(180deg,#FDE68A 0%,#F59E0B 40%,#B45309 100%);}
        .khc-fill.mid{background:linear-gradient(180deg,rgba(110,231,183,.75) 0%,rgba(16,185,129,.5) 100%);}
        .khc-fill.lo{
            background:linear-gradient(180deg,rgba(239,68,68,0.35) 0%,rgba(185,28,28,0.18) 100%);
            border-top:2.5px solid #EF4444;
            border-left:1.5px solid rgba(239,68,68,0.65);
            border-right:1.5px solid rgba(239,68,68,0.65);
            box-shadow:0 -2px 10px rgba(239,68,68,0.35);
        }
        .khc-fill.pk.khc-loaded{box-shadow:0 -8px 24px rgba(245,158,11,.55);animation:pkShine 2.8s ease-in-out 0s infinite;}
        .khc-fill.lo.khc-loaded{animation:loPulse 3s ease-in-out infinite;}
        @keyframes pkShine{0%,100%{box-shadow:0 -8px 24px rgba(245,158,11,.55);}50%{box-shadow:0 -14px 36px rgba(245,158,11,.85);}}
        @keyframes loPulse{0%,100%{border-top-color:#EF4444;box-shadow:0 -2px 8px rgba(239,68,68,0.3);}50%{border-top-color:#F87171;box-shadow:0 -5px 16px rgba(239,68,68,0.65);}}
        /* Inline price on peak bar */
        .khc-price-inline{position:absolute;width:100%;bottom:4px;text-align:center;color:rgba(255,255,255,.9);font-size:7px;font-weight:900;line-height:1;opacity:0;transition:opacity .4s .95s;pointer-events:none;}
        .khc-price-inline.khc-loaded{opacity:1;}
        /* Month label */
        .khc-lbl{font-size:9px;margin-top:5px;font-weight:700;color:rgba(255,255,255,.35);text-align:center;transition:color .2s;white-space:nowrap;}
        .khc-lbl.pk{color:#FDE68A;font-weight:900;}
        .khc-lbl.lo{color:#FCA5A5;font-weight:800;}
        .khc-col:hover .khc-lbl:not(.pk):not(.lo){color:rgba(255,255,255,.72);}
        /* Crown above peak */
        .khc-crown{position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:13px;line-height:1;opacity:0;transition:opacity .5s 1.1s;}
        .khc-crown.khc-loaded{opacity:1;}
        /* Tooltip */
        .khc-tip{position:absolute;bottom:calc(100% + 10px);left:50%;transform:translateX(-50%);opacity:0;pointer-events:none;z-index:50;transition:opacity .15s,transform .15s;transform-origin:bottom center;white-space:nowrap;background:rgba(4,4,4,.94);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);color:#fff;border-radius:10px;padding:7px 12px;font-size:11px;display:flex;flex-direction:column;align-items:center;gap:2px;box-shadow:0 6px 24px rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.1);}
        .khc-col:hover .khc-tip,.khc-col:focus .khc-tip{opacity:1;transform:translateX(-50%) translateY(-2px);}
        .khc-tip-arrow{width:7px;height:7px;background:rgba(4,4,4,.94);transform:rotate(45deg);margin-top:3px;align-self:center;flex-shrink:0;}
        /* Bottom divider rule */
        .khc-rule{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent);margin:14px 0 0;}
        /* Footer */
        .khc-foot{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:12px;}
        .khc-avg{color:rgba(255,255,255,.48);font-size:10px;font-weight:700;}
        .khc-avg strong{color:#86EFAC;font-size:11px;}
        .khc-legend{display:flex;align-items:center;gap:10px;}
        .khc-dot{width:8px;height:8px;border-radius:2px;flex-shrink:0;}
        .khc-leg-txt{font-size:9.5px;font-weight:700;}
    </style>

    <div class="khc-card">
        {{-- Ambient radial orbs --}}
        <div class="khc-glow" style="top:-90px;right:-70px;width:220px;height:220px;background:radial-gradient(circle,rgba(46,139,78,.2) 0%,transparent 70%);"></div>
        <div class="khc-glow" style="bottom:-70px;left:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(245,158,11,.09) 0%,transparent 70%);"></div>

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
            <div>
                <div class="khc-eyebrow {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:5px;">
                        <span style="display:inline-block;width:6px;height:6px;background:#86EFAC;border-radius:50%;flex-shrink:0;"></span>
                        {{ $activeLocale === 'en' ? 'HISTORICAL SEASONAL ANALYSIS' : 'ಐತಿಹಾಸಿಕ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆ' }}
                    </span>
                    @if(!empty($seasonalAnalysis['market_name']))
                        <span style="background:rgba(255,255,255,0.12);padding:2px 8px;border-radius:12px;font-size:10.5px;color:#FDE68A;border:1px solid rgba(253,230,138,0.25);">
                            📍 {{ $activeLocale === 'kn' && !empty($seasonalAnalysis['market_name_kn']) ? $seasonalAnalysis['market_name_kn'] : $seasonalAnalysis['market_name'] }}
                        </span>
                    @endif
                    @if(($seasonalAnalysis['scope'] ?? '') === 'market_calibrated')
                        <span style="background:rgba(99,102,241,0.18);padding:2px 8px;border-radius:12px;font-size:9.5px;color:rgba(196,198,255,0.85);border:1px solid rgba(99,102,241,0.3);" title="{{ $activeLocale === 'en' ? 'State seasonal pattern scaled to this mandi\'s actual price level' : 'ರಾಜ್ಯ ಋತುಮಾನ ಮಾದರಿ — ಈ ಮಂಡಿ ದರ ಮಟ್ಟಕ್ಕೆ ಹೊಂದಿಸಲಾಗಿದೆ' }}">
                            {{ $activeLocale === 'en' ? '🔄 State pattern · local price' : '🔄 ರಾಜ್ಯ ಮಾದರಿ · ಸ್ಥಳೀಯ ಬೆಲೆ' }}
                        </span>
                    @endif
                </div>
                <div class="khc-title mb-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Best Months to Sell' : 'ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು' }}
                </div>
                @php
                    $yearsCount = (int) ($seasonalAnalysis['seasonality_years'] ?? \App\Models\SystemSetting::get('seasonality_years', 5));
                @endphp
                <div class="{{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="font-size:12px;font-weight:600;color:rgba(255,255,255,0.72);margin-top:2px;">
                    {{ $activeLocale === 'en' ? "{$yearsCount}-year historical price seasonality & peak harvest window" : "{$yearsCount} ವರ್ಷಗಳ ಮಂಡಿ ಇತಿಹಾಸದ ಆಧಾರದ ಮೇಲೆ ಗರಿಷ್ಠ ಧಾರಣೆ ಸಿಗುವ ತಿಂಗಳುಗಳು" }}
                </div>
            </div>
            <div class="khc-badge-5y {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="margin-top:2px;">
                @if(($seasonalAnalysis['distinct_months'] ?? 0) >= 12)
                    {{ $activeLocale === 'en' ? "Last {$yearsCount} Years" : "ಕಳೆದ {$yearsCount} ವರ್ಷ" }}
                @else
                    {{ $seasonalAnalysis['distinct_months'] ?? 2 }} {{ $activeLocale === 'en' ? 'Months Recorded' : 'ತಿಂಗಳ ಮಂಡಿ ದಾಖಲೆ' }}
                @endif
            </div>
        </div>

        {{-- Lead text --}}
        <div class="khc-lead {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
            @if($activeLocale === 'en')
                @if(!empty($seasonalAnalysis['peak_months_en']))
                    Prices in <strong>{{ $seasonalAnalysis['market_name'] ?? 'Karnataka' }}</strong> are usually highest around <strong>{{ implode(', ', $seasonalAnalysis['peak_months_en']) }}</strong> — plan your harvest and sale for those months.
                    @if(($seasonalAnalysis['scope'] ?? '') === 'market_calibrated')
                        <span style="font-size:11px;font-weight:600;color:rgba(196,198,255,0.7);"> (Seasonal shape from statewide data, prices calibrated to this mandi's level.)</span>
                    @endif
                @else
                    {{ $seasonalAnalysis['lead_summary_en'] ?? 'Seasonal price variations based on historical mandi arrivals.' }}
                @endif
            @else
                @if(!empty($seasonalAnalysis['peak_months_kn']))
                    <strong>{{ !empty($seasonalAnalysis['market_name_kn']) ? $seasonalAnalysis['market_name_kn'] : ($seasonalAnalysis['market_name'] ?? 'ಕರ್ನಾಟಕ') }}</strong> ಮಾರುಕಟ್ಟೆಯಲ್ಲಿ ಸಾಮಾನ್ಯವಾಗಿ <strong>{{ implode(', ', $seasonalAnalysis['peak_months_kn']) }}</strong> ತಿಂಗಳಲ್ಲಿ ಬೆಲೆ ಹೆಚ್ಚು — ಆ ಸಮಯಕ್ಕೆ ಬೆಳೆ ಮಾರಲು ಸಿದ್ಧರಾಗಿ.
                    @if(($seasonalAnalysis['scope'] ?? '') === 'market_calibrated')
                        <span style="font-size:11px;font-weight:600;color:rgba(196,198,255,0.7);"> (ರಾಜ್ಯ ಋತುಮಾನ ಮಾದರಿ — ಈ ಮಂಡಿ ಬೆಲೆಗೆ ಹೊಂದಿಸಲಾಗಿದೆ.)</span>
                    @endif
                @else
                    {{ $seasonalAnalysis['lead_summary_kn'] ?? 'ಮಾರುಕಟ್ಟೆ ಇತಿಹಾಸ ಆಧಾರದ ಮೇಲೆ ಬೆಲೆ ವ್ಯತ್ಯಾಸ ತೋರಿಸಲಾಗಿದೆ.' }}
                @endif
            @endif
        </div>

        {{-- Peak month chips --}}
        @php
            $peakMonths = collect($seasonalAnalysis['monthly_profile'])
                ->filter(fn($m) => !empty($m['is_peak']) || ($m['tier'] ?? '') === 'pk')
                ->values();
        @endphp
        @if($peakMonths->isNotEmpty())
            <div class="khc-chips">
                @foreach($peakMonths as $pm)
                    <div class="khc-chip {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span style="font-size:15px;line-height:1;">⭐</span>
                        <div style="line-height:1.25;">
                            <div style="color:#fff;font-size:12px;font-weight:900;">
                                {{ $activeLocale === 'en' ? ($pm['name_en'] ?? $pm['short_name_en']) : ($pm['short_name_kn'] ?? $pm['name_kn']) }}
                            </div>
                            @if(($pm['avg_price'] ?? 0) > 0)
                                <div style="color:rgba(255,255,255,.82);font-size:9.5px;font-weight:700;">₹{{ number_format($pm['avg_price'],0) }}/{{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Bar chart --}}
        <div class="khc-bars season-bars-container" role="img" aria-label="{{ $activeLocale === 'en' ? 'Monthly Seasonal Price Trend' : 'ತಿಂಗಳವಾರ ಬೆಲೆ ಋತುಮಾನ ಗ್ರಾಫ್' }}">
            @foreach($seasonalAnalysis['monthly_profile'] as $m)
                @php
                    $tier  = $m['tier'] ?? 'mid';
                    $isPeak = $tier === 'pk' || !empty($m['is_peak']);
                    $hasData = ($m['observations'] ?? 0) > 0 && ($m['avg_price'] ?? 0) > 0;
                    $hPct  = $hasData ? max(7, (int)($m['bar_height_percent'] ?? 0)) : 0;
                    $idxPct = $m['index_percentage'] ?? 0;
                    $mName = $activeLocale === 'en' ? $m['name_en'] : $m['name_kn'];
                    $mShort = $activeLocale === 'en' ? $m['short_name_en'] : ($m['short_name_kn'] ?? $m['name_kn']);
                @endphp
                <div class="khc-col {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}"
                     tabindex="0"
                     role="button"
                     aria-label="{{ $mName }}{{ $hasData ? ': ₹'.number_format($m['avg_price'],0) : '' }}">

                    {{-- Tooltip --}}
                    <div class="khc-tip">
                        <span style="font-weight:900;color:#FDE68A;font-size:12px;">{{ $mName }}</span>
                        @if($hasData)
                            <span style="font-weight:700;font-size:11.5px;">₹{{ number_format($m['avg_price'],0) }}<span style="font-size:9px;font-weight:600;opacity:.7;">/{{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</span></span>
                            <span style="font-size:9px;font-weight:700;color:{{ $idxPct >= 0 ? '#6EE7B7' : '#FCA5A5' }};">{{ $idxPct >= 0 ? '+' : '' }}{{ $idxPct }}% avg</span>
                            @if($tier === 'lo')
                                <span style="font-size:9px;font-weight:700;color:#FCA5A5;background:rgba(239,68,68,0.22);border:1px solid rgba(239,68,68,0.5);padding:1px 6px;border-radius:6px;margin-top:2px;">
                                    📉 {{ $activeLocale === 'en' ? 'Low Price Period' : 'ಕಡಿಮೆ ಬೆಲೆ ಅವಧಿ' }}
                                </span>
                            @elseif($isPeak)
                                <span style="font-size:9px;font-weight:700;color:#FDE68A;background:rgba(245,158,11,0.25);border:1px solid rgba(245,158,11,0.5);padding:1px 6px;border-radius:6px;margin-top:2px;">
                                    ⭐ {{ $activeLocale === 'en' ? 'Peak Selling Window' : 'ಅತ್ಯುತ್ತಮ ಧಾರಣೆ ಕಾಲ' }}
                                </span>
                            @endif
                        @else
                            <span style="font-size:10px;color:rgba(255,255,255,.4);">{{ $activeLocale === 'en' ? 'No Data' : 'ಮಾಹಿತಿ ಇಲ್ಲ' }}</span>
                        @endif
                        <div class="khc-tip-arrow"></div>
                    </div>

                    {{-- Crown (peak only) --}}
                    @if($isPeak && $hasData)
                        <div class="khc-crown" aria-hidden="true">🏆</div>
                    @endif

                    {{-- Track + Fill --}}
                    <div class="khc-track">
                        @if($hasData)
                            <div class="khc-fill {{ $tier }} season-bar-fill"
                                 data-target-height="{{ $hPct }}%"
                                 style="height:{{ $hPct }}%;"></div>
                            @if($isPeak)
                                <div class="khc-price-inline bms-price-label">
                                    ₹{{ number_format($m['avg_price'],0) }}
                                </div>
                            @endif
                        @else
                            <div style="width:100%;height:2px;background:rgba(255,255,255,.07);align-self:flex-end;"></div>
                        @endif
                    </div>

                    {{-- Month label --}}
                    <div class="khc-lbl {{ $isPeak ? 'pk' : ($tier === 'lo' ? 'lo' : '') }}">
                        {{ $mShort }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Baseline divider --}}
        <div class="khc-rule"></div>

        {{-- Footer --}}
        <div class="khc-foot">
            @if(!empty($seasonalAnalysis['annual_baseline']) && $seasonalAnalysis['annual_baseline'] > 0)
                <div class="khc-avg {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Annual Baseline' : 'ವಾರ್ಷಿಕ ಸರಾಸರಿ' }}{{ !empty($seasonalAnalysis['market_name']) ? ' ('.($activeLocale === 'kn' && !empty($seasonalAnalysis['market_name_kn']) ? $seasonalAnalysis['market_name_kn'] : $seasonalAnalysis['market_name']).')' : '' }}: <strong>₹{{ number_format($seasonalAnalysis['annual_baseline'],0) }}/{{ $activeLocale === 'en' ? 'Qtl' : 'ಕ್ವಿಂ' }}</strong>
                </div>
            @endif
            <div class="khc-legend">
                <span style="display:inline-flex;align-items:center;gap:4px;">
                    <span class="khc-dot" style="background:linear-gradient(135deg,#F59E0B,#B45309);"></span>
                    <span class="khc-leg-txt {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color:#FDE68A;">{{ $activeLocale === 'en' ? 'Peak Window' : 'ಉತ್ತಮ ಕಾಲ' }}</span>
                </span>
                <span style="display:inline-flex;align-items:center;gap:4px;">
                    <span class="khc-dot" style="background:rgba(110,231,183,.65);"></span>
                    <span class="khc-leg-txt {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color:rgba(255,255,255,.42);">{{ $activeLocale === 'en' ? 'Normal' : 'ಸಾಮಾನ್ಯ' }}</span>
                </span>
                <span style="display:inline-flex;align-items:center;gap:5px;">
                    <span class="khc-dot" style="background:rgba(239,68,68,0.25);border:1.5px solid #EF4444;box-shadow:0 0 6px rgba(239,68,68,0.45);"></span>
                    <span class="khc-leg-txt {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}" style="color:#FCA5A5;">{{ $activeLocale === 'en' ? 'Low Price Period' : 'ಕಡಿಮೆ ಬೆಲೆ' }}</span>
                </span>
            </div>
        </div>

        <div id="seasonalityCanvas" class="hidden" aria-hidden="true"></div>
    </div>

    @else
    {{-- Insufficient data: light header + amber notice --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-start gap-2.5">
                <span class="w-1.5 h-8 sm:h-9 rounded-full bg-[#1C5A2C] shrink-0 mt-0.5"></span>
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-stone-900 tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Best Months to Sell' : 'ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು' }}
                    </h2>
                    <p class="text-xs text-stone-500 font-medium mt-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? '5-year historical price seasonality & peak harvest window' : '5 ವರ್ಷಗಳ ಮಂಡಿ ಇತಿಹಾಸದ ಆಧಾರದ ಮೇಲೆ ಗರಿಷ್ಠ ಧಾರಣೆ ಸಿಗುವ ತಿಂಗಳುಗಳು' }}
                    </p>
                </div>
            </div>
            <div class="inline-flex items-center justify-center leading-none text-xs font-bold text-stone-600 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }} bg-stone-100 px-3 py-1.5 rounded-full border border-stone-200/80 shadow-2xs shrink-0">
                <span class="inline-flex items-center leading-none">{{ $activeLocale === 'en' ? 'Last 5 Years' : 'ಕಳೆದ 5 ವರ್ಷ' }}</span>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-950 flex items-start gap-3">
            <span class="text-xl shrink-0">ℹ️</span>
            <div class="space-y-1 text-xs {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                <div class="font-bold text-sm text-amber-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Seasonal Data Insufficiency Notice' : 'ಋತುಮಾನ ಮಾಹಿತಿ ಕೊರತೆ ಸೂಚನೆ' }}
                </div>
                <p class="leading-relaxed">{{ $activeLocale === 'en' ? ($seasonalAnalysis['message_en'] ?? 'At least 2 distinct months of market price records are required for seasonal analysis.') : ($seasonalAnalysis['message_kn'] ?? 'ವಿಶ್ವಾಸಾರ್ಹ ಋತುಮಾನ ವಿಶ್ಲೇಷಣೆಗೆ ಕನಿಷ್ಠ 2 ಪ್ರತ್ಯೇಕ ತಿಂಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ.') }}</p>
                <p class="text-amber-800/80">{{ $activeLocale === 'en' ? 'Krushi Baandhava does not generate synthetic prices. Seasonal Selling Indices will activate once at least 2 distinct months of continuous mandi records are logged.' : 'ಕೃಷಿ ಬಾಂಧವ ಕೃತಕ ಅಂದಾಜುಗಳನ್ನು ಪ್ರದರ್ಶಿಸುವುದಿಲ್ಲ. ಮಂಡಿಗಳಿಂದ ಕನಿಷ್ಠ 2 ಪ್ರತ್ಯೇಕ ತಿಂಗಳುಗಳ ನಿರಂತರ ದರಗಳು ದಾಖಲಾದ ನಂತರ ನಿಖರ ಋತುಮಾನ ಸೂಚ್ಯಂಕ ಸ್ವಯಂಚಾಲಿತವಾಗಿ ಸಕ್ರಿಯಗೊಳ್ಳುತ್ತದೆ.' }}</p>
            </div>
        </div>
        <div id="seasonalityCanvas" class="hidden" aria-hidden="true"></div>
    </div>
    @endif
