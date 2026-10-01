@extends('layouts.farmer')

@section('title', (app()->getLocale() === 'en' ? ($scheme->title ?: $scheme->title_kn) : ($scheme->title_kn ?: $scheme->title)) . ' — ' . (app()->getLocale() === 'en' ? 'Government Scheme Details' : 'ಸರ್ಕಾರಿ ಯೋಜನೆ ವಿವರಗಳು'))

@section('content')
@php
    $activeLocale = app()->getLocale();
    $mainTitle = $activeLocale === 'en' ? ($scheme->title ?: $scheme->title_kn) : ($scheme->title_kn ?: $scheme->title);
    $subTitle = $activeLocale === 'en' ? $scheme->title_kn : $scheme->title;
    $benefitText = $activeLocale === 'en' ? ($scheme->benefit_amount ?: $scheme->benefit_amount_kn) : ($scheme->benefit_amount_kn ?: $scheme->benefit_amount);
    $eligibilityText = $activeLocale === 'en' ? ($scheme->eligibility_criteria ?: $scheme->eligibility_criteria_kn) : ($scheme->eligibility_criteria_kn ?: $scheme->eligibility_criteria);
    $docsText = $scheme->documents_required ?: ($scheme->documents_required_kn ?? '');
    $emoji = $scheme->icon_emoji ?: match($scheme->category) {
        'subsidy' => '💰',
        'machinery' => '🚜',
        'irrigation' => '💧',
        'insurance' => '🛡️',
        'organic' => '🌱',
        default => '🏛️',
    };

    // Punchy stat helper
    $punchyStat = match($scheme->slug ?? '') {
        'pm-kisan-samman-nidhi' => $activeLocale === 'en' ? '₹6,000 / yr' : '₹6,000 / ವರ್ಷ',
        'ganga-kalyana-scheme' => $activeLocale === 'en' ? '100% Free (₹4.75L)' : '100% ಉಚಿತ (₹4.75 ಲಕ್ಷ)',
        'pm-kusum-solar-pumpset' => $activeLocale === 'en' ? '80% - 90% Subsidy' : 'ಶೇ. 80 - 90 ಸಬ್ಸಿಡಿ',
        'farm-mechanization-subsidy' => $activeLocale === 'en' ? 'Up to 90% (₹2L)' : 'ಶೇ. 90 (ಗರಿಷ್ಠ ₹2 ಲಕ್ಷ)',
        'pmksy-micro-irrigation' => $activeLocale === 'en' ? 'Up to 90% Subsidy' : 'ಶೇ. 75 - 90 ಸಬ್ಸಿಡಿ',
        'krishi-bhagya-scheme' => $activeLocale === 'en' ? '80% - 90% Grant' : 'ಶೇ. 80 - 90 ಅನುದಾನ',
        'pashu-bhagya-scheme' => $activeLocale === 'en' ? 'Up to 50% Subsidy' : 'ಶೇ. 33 - 50 ಸಬ್ಸಿಡಿ',
        'krishi-yantra-dhare' => $activeLocale === 'en' ? '50% Rent Discount' : 'ಶೇ. 50 ರಿಯಾಯಿತಿ ಬಾಡಿಗೆ',
        'pm-fasal-bima-yojana' => $activeLocale === 'en' ? '1.5% - 2% Premium' : 'ಕೇವಲ 1.5% - 2% ಪ್ರೀಮಿಯಂ',
        'soil-health-card-scheme' => $activeLocale === 'en' ? '100% Free Testing' : '100% ಉಚಿತ ಮಣ್ಣು ಪರೀಕ್ಷೆ',
        'fruits-portal-registration-farmer-id' => $activeLocale === 'en' ? 'Free FID Registration' : 'ಉಚಿತ FID ನೋಂದಣಿ',
        default => $benefitText,
    };
@endphp

<div class="space-y-6 max-w-5xl mx-auto pb-14">

    <!-- ============================================================== -->
    <!-- 1. BREADCRUMB & BACK NAVIGATION                                -->
    <!-- ============================================================== -->
    <div class="flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
        <a href="{{ route('farmer.schemes.index') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-stone-200 text-stone-700 font-black hover:bg-stone-50 hover:border-[#1C5A2C] transition shadow-2xs group">
            <span class="group-hover:-translate-x-0.5 transition-transform">&larr;</span>
            <span>{{ $activeLocale === 'en' ? 'Back to Schemes' : 'ಯೋಜನೆಗಳ ಪಟ್ಟಿಗೆ ಹಿಂತಿರುಗಿ' }}</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#FAF6EC] border border-[#E8E1D0] text-stone-800">
                <span>{{ $emoji }}</span>
                <span>{{ $activeLocale === 'en' ? ($scheme->category_label_en ?? ucfirst($scheme->category)) : $scheme->category_label_kn }}</span>
            </span>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                <span>{{ $activeLocale === 'en' ? 'Active Scheme' : 'ಪ್ರಸ್ತುತ ಸಕ್ರಿಯ' }}</span>
            </span>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. HIGH-IMPACT HERO BANNER CARD (Atmospheric Topographic Theme)-->
    <!-- ============================================================== -->
    <div class="relative overflow-hidden rounded-3xl text-white p-6 sm:p-8 shadow-[0_16px_40px_-10px_rgba(11,43,23,0.50)] border border-emerald-500/30"
         style="contain: paint; background: radial-gradient(circle at 85% 15%, #257044 0%, #154D2B 45%, #0B2B17 100%);">
        <!-- Unique Krushi Baandhava Agricultural Field Elevation Contours -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none select-none z-0" preserveAspectRatio="none" viewBox="0 0 800 240" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M-20 180 Q 240 70, 500 170 T 820 90" stroke="currentColor" stroke-width="1.8" stroke-opacity="0.18" class="text-emerald-200" />
            <path d="M-20 215 Q 260 110, 520 205 T 820 135" stroke="currentColor" stroke-width="1.4" stroke-opacity="0.14" class="text-white" stroke-dasharray="6 4" />
            <path d="M-20 245 Q 280 150, 540 235 T 820 175" stroke="currentColor" stroke-width="1.2" stroke-opacity="0.10" class="text-emerald-300" />
            <ellipse cx="680" cy="45" rx="150" ry="100" fill="#4ade80" opacity="0.15" />
        </svg>

        <div class="relative z-10 space-y-5">
            <!-- Sponsoring Agency & Portal Badge -->
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 pb-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs border border-white/15 text-xs font-bold text-emerald-200 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <span>🏛️</span>
                    <span>{{ $scheme->sponsoring_agency ?: ($activeLocale === 'en' ? 'Government of Karnataka' : 'ಕರ್ನಾಟಕ ಸರ್ಕಾರ') }}</span>
                </div>
                <div class="text-[11px] font-extrabold text-amber-300 tracking-wide flex items-center gap-1.5">
                    <span class="text-xs">✨</span>
                    <span>{{ $activeLocale === 'en' ? 'Karnataka Farmer Welfare Portal' : 'ಅಧಿಕೃತ ಕೃಷಿ ಕಲ್ಯಾಣ ಯೋಜನೆ' }}</span>
                </div>
            </div>

            <!-- Title & Metric Row -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/15 backdrop-blur-xs border border-white/20 flex items-center justify-center text-2xl sm:text-3xl shrink-0 shadow-inner">
                            {{ $emoji }}
                        </div>
                        <div class="space-y-1">
                            <h1 class="text-xl sm:text-3xl font-black text-white leading-tight tracking-tight {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $mainTitle }}
                            </h1>
                            @if($subTitle && $subTitle !== $mainTitle)
                                <p class="text-xs sm:text-sm font-semibold text-emerald-200/90 leading-snug {{ $activeLocale === 'kn' ? 'font-sans' : 'font-kannada' }}">
                                    {{ $subTitle }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Big Highlight Stat -->
                <div class="lg:text-right shrink-0 pt-2 lg:pt-0">
                    <div class="text-2xl sm:text-4xl font-black text-amber-300 font-sans tracking-tight">
                        {{ $punchyStat }}
                    </div>
                    <div class="text-[10px] sm:text-[11px] font-extrabold text-emerald-200 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'KEY BENEFIT / SUBSIDY' : 'ಮುಖ್ಯ ಸೌಲಭ್ಯ / ಲಾಭ' }}
                    </div>
                </div>
            </div>

            <!-- Action CTAs Bar -->
            <div class="pt-2 flex flex-wrap items-center gap-3">
                @if($scheme->apply_url)
                    <a href="{{ $scheme->apply_url }}" target="_blank" rel="noopener noreferrer" 
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-amber-400 hover:bg-amber-300 text-stone-950 font-black text-xs sm:text-sm shadow-md transition transform hover:-translate-y-0.5 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span>📝</span>
                        <span>{{ $activeLocale === 'en' ? 'Apply Now (Official Portal)' : 'ಆನ್‌ಲೈನ್‌ನಲ್ಲಿ ಅರ್ಜಿ ಸಲ್ಲಿಸಿ (Apply Online)' }}</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a>
                @endif

                @if($scheme->official_url && $scheme->official_url !== $scheme->apply_url)
                    <a href="{{ $scheme->official_url }}" target="_blank" rel="noopener noreferrer" 
                       class="inline-flex items-center gap-2 px-4 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white font-bold text-xs sm:text-sm backdrop-blur-xs border border-white/20 transition {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span>🌐</span>
                        <span>{{ $activeLocale === 'en' ? 'Department Website' : 'ಇಲಾಖೆಯ ಅಧಿಕೃತ ವೆಬ್‌ಸೈಟ್' }}</span>
                        <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a>
                @endif

                <button onclick="window.print()" type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white/90 font-bold text-xs backdrop-blur-xs border border-white/15 transition ml-auto {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    <span>{{ $activeLocale === 'en' ? 'Print Guide' : 'ಪ್ರಿಂಟ್ / PDF' }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 3. HIGHLIGHT BENEFIT TILE                                       -->
    <!-- ============================================================== -->
    @if($benefitText)
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#1C5A2C] via-[#164C25] to-[#123E1E] border border-[#2E7D32]/50 p-6 sm:p-7 shadow-xs text-white">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-400 text-stone-950 flex items-center justify-center text-2xl shrink-0 shadow-md">
                    💰
                </div>
                <div class="space-y-1.5 flex-1 min-w-0">
                    <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full bg-white/10 border border-white/15 text-[10px] font-black text-amber-300 uppercase tracking-wider {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        <span>{{ $activeLocale === 'en' ? 'YOU GET · FINANCIAL BENEFIT' : '⭐ ನೀವು ಪಡೆಯುವ ಲಾಭ · ಸಬ್ಸಿಡಿ ಮೊತ್ತ' }}</span>
                    </div>
                    <h2 class="text-lg sm:text-2xl font-black text-white leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $benefitText }}
                    </h2>
                    <p class="text-xs sm:text-sm text-emerald-100/90 font-medium leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' 
                            ? 'Financial grant or subsidy is directly credited to your Aadhaar-linked bank account (DBT) or adjusted at the authorized dealership upon department approval.' 
                            : 'ಸರ್ಕಾರದಿಂದ ನಿಗದಿಪಡಿಸಲಾದ ಸಬ್ಸಿಡಿ ಮೊತ್ತವು ಅರ್ಹ ರೈತರ ಆಧಾರ್ ಜೋಡಿತ ಬ್ಯಾಂಕ್ ಖಾತೆಗೆ ನೇರವಾಗಿ ಜಮೆಯಾಗುತ್ತದೆ (DBT) ಅಥವಾ ಅನುಮೋದಿತ ವಿತರಕರಲ್ಲಿ ರಿಯಾಯಿತಿ ದೊರೆಯುತ್ತದೆ.' }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- 4. CORE DETAILS: ELIGIBILITY & DOCUMENTS (Neat Clean Cards)    -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        
        <!-- Eligibility Card -->
        <div class="bg-white rounded-[26px] border border-[#E8E1D0] p-6 shadow-2xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center gap-3 border-b border-stone-100 pb-3">
                    <span class="w-9 h-9 rounded-xl bg-[#E6F1E4] text-[#1C5A2C] flex items-center justify-center text-lg">✅</span>
                    <h3 class="text-base font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Eligibility Criteria' : 'ಅರ್ಹತೆಯ ಮಾನದಂಡಗಳು (Eligibility)' }}
                    </h3>
                </div>

                <div class="text-xs sm:text-sm text-stone-800 leading-relaxed font-medium {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    @if($eligibilityText)
                        <div class="p-3.5 rounded-2xl bg-[#FAF6EC] border border-[#E8E1D0] text-stone-900 font-bold mb-3">
                            {{ $eligibilityText }}
                        </div>
                    @endif

                    <ul class="space-y-2 text-stone-600 text-xs">
                        <li class="flex items-start gap-2">
                            <span class="text-[#1C5A2C] font-bold shrink-0">✓</span>
                            <span>{{ $activeLocale === 'en' ? 'Must be a permanent resident farmer of Karnataka.' : 'ಕರ್ನಾಟಕ ರಾಜ್ಯದ ಖಾಯಂ ನಿವಾಸಿ ರೈತರಾಗಿರಬೇಕು.' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#1C5A2C] font-bold shrink-0">✓</span>
                            <span>{{ $activeLocale === 'en' ? 'Valid agricultural land records (RTC / Pahani) registered on FRUITS portal.' : 'ರೈತರ ಹೆಸರಿನಲ್ಲಿ ಜಮೀನಿನ ಪಹಣಿ (RTC) ಇರಬೇಕು ಹಾಗೂ FRUITS ಪೋರ್ಟಲ್‌ನಲ್ಲಿ ನೋಂದಣಿಯಾಗಿರಬೇಕು.' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#1C5A2C] font-bold shrink-0">✓</span>
                            <span>{{ $activeLocale === 'en' ? 'Bank account must be seeded with Aadhaar for DBT transfer.' : 'ಬ್ಯಾಂಕ್ ಖಾತೆಗೆ ಆಧಾರ್ ಲಿಂಕ್ ಆಗಿರಬೇಕು (DBT ಸೌಲಭ್ಯಕ್ಕೆ).' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-[11px] font-bold text-stone-500">
                <span>{{ $activeLocale === 'en' ? 'Priority to Small & Marginal Farmers' : 'ಸಣ್ಣ ಮತ್ತು ಅತಿ ಸಣ್ಣ ರೈತರಿಗೆ ಮೊದಲ ಆದ್ಯತೆ' }}</span>
                <span class="text-[#1C5A2C]">✓ 100% ಅಧಿಕೃತ</span>
            </div>
        </div>

        <!-- Required Documents Checklist Card -->
        <div class="bg-white rounded-[26px] border border-[#E8E1D0] p-6 shadow-2xs flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center gap-3 border-b border-stone-100 pb-3">
                    <span class="w-9 h-9 rounded-xl bg-[#FAEFD6] text-[#E0A23A] flex items-center justify-center text-lg">📄</span>
                    <h3 class="text-base font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                        {{ $activeLocale === 'en' ? 'Required Documents' : 'ಅಗತ್ಯ ದಾಖಲೆಗಳು (Checklist)' }}
                    </h3>
                </div>

                <div class="text-xs sm:text-sm text-stone-800 leading-relaxed space-y-2 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    @if($docsText)
                        <div class="p-3.5 rounded-2xl bg-[#FAF6EC] border border-[#E8E1D0] text-stone-900 font-bold">
                            {{ $docsText }}
                        </div>
                    @endif

                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center gap-2.5 p-2 rounded-xl bg-stone-50 border border-stone-100 text-xs font-semibold text-stone-700">
                            <span class="text-[#1C5A2C] text-sm">📋</span>
                            <span>{{ $activeLocale === 'en' ? 'FRUITS Farmer ID (FID)' : 'FRUITS ರೈತ ಗುರುತಿನ ಸಂಖ್ಯೆ (FID ಸಂಖ್ಯೆ)' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 p-2 rounded-xl bg-stone-50 border border-stone-100 text-xs font-semibold text-stone-700">
                            <span class="text-[#1C5A2C] text-sm">🪪</span>
                            <span>{{ $activeLocale === 'en' ? 'Aadhaar Card copy' : 'ಆಧಾರ್ ಕಾರ್ಡ್ ಪ್ರತಿ' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 p-2 rounded-xl bg-stone-50 border border-stone-100 text-xs font-semibold text-stone-700">
                            <span class="text-[#1C5A2C] text-sm">🌾</span>
                            <span>{{ $activeLocale === 'en' ? 'Recent Land RTC / Pahani record' : 'ಇತ್ತೀಚಿನ ಜಮೀನಿನ ಆರ್.ಟಿ.ಸಿ / ಪಹಣಿ' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 p-2 rounded-xl bg-stone-50 border border-stone-100 text-xs font-semibold text-stone-700">
                            <span class="text-[#1C5A2C] text-sm">🏦</span>
                            <span>{{ $activeLocale === 'en' ? 'Bank Passbook front page (Aadhaar linked)' : 'ಬ್ಯಾಂಕ್ ಪಾಸ್‌ಬುಕ್ ಪ್ರತಿ (ಆಧಾರ್ ಲಿಂಕ್ ಇರುವ ಖಾತೆ)' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 p-2 rounded-xl bg-stone-50 border border-stone-100 text-xs font-semibold text-stone-700">
                            <span class="text-[#1C5A2C] text-sm">📑</span>
                            <span>{{ $activeLocale === 'en' ? 'Caste & Income Certificate (if applicable for subsidy)' : 'ಜಾತಿ ಮತ್ತು ಆದಾಯ ಪ್ರಮಾಣಪತ್ರ (ಅನ್ವಯಿಸಿದಲ್ಲಿ)' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-stone-100 text-[11px] font-bold text-amber-800 flex items-center gap-1.5">
                <span>💡</span>
                <span>{{ $activeLocale === 'en' ? 'Tip: Keep photocopies and scanned PDF copies ready' : 'ಸೂಚನೆ: ಎಲ್ಲಾ ದಾಖಲೆಗಳ ಜೆರಾಕ್ಸ್ ಪ್ರತಿಯನ್ನು ಸಿದ್ಧವಾಗಿಟ್ಟುಕೊಳ್ಳಿ' }}</span>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 5. STEP-BY-STEP APPLICATION ROADMAP                            -->
    <!-- ============================================================== -->
    <div class="bg-white rounded-[26px] border border-[#E8E1D0] p-6 sm:p-7 shadow-2xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-stone-100 pb-3">
            <div>
                <h3 class="text-base sm:text-lg font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Application Roadmap (4 Simple Steps)' : 'ಅರ್ಜಿ ಸಲ್ಲಿಸುವ ಸರಳ 4 ಹಂತಗಳು (Application Roadmap)' }}
                </h3>
                <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Follow these steps to submit your application and track approval status.' : 'ಈ ಕೆಳಗಿನ ಹಂತಗಳನ್ನು ಅನುಸರಿಸಿ ಅಧಿಕೃತವಾಗಿ ಅರ್ಜಿ ಸಲ್ಲಿಸಿ ಸಬ್ಸಿಡಿ ಪಡೆಯಿರಿ.' }}
                </p>
            </div>
            @if($scheme->apply_url)
                <a href="{{ $scheme->apply_url }}" target="_blank" rel="noopener noreferrer" 
                   class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full bg-[#1C5A2C] hover:bg-[#144223] text-white font-black text-xs shadow-xs hover:shadow-sm transition shrink-0">
                    <span>{{ $activeLocale === 'en' ? 'Start Application →' : 'ಅರ್ಜಿ ಪ್ರಾರಂಭಿಸಿ →' }}</span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Step 1 -->
            <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-7 h-7 rounded-full bg-[#1C5A2C] text-white font-black text-xs flex items-center justify-center">1</span>
                    <span class="text-xl">🪪</span>
                </div>
                <h4 class="text-xs font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? '1. FRUITS / FID Registration' : '1. FID ಸಂಖ್ಯೆ ಪಡೆಯಿರಿ' }}
                </h4>
                <p class="text-[11px] text-stone-600 leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Ensure your farmer profile and land RTC are active on Karnataka FRUITS portal.' : 'ಕರ್ನಾಟಕ ಸರ್ಕಾರದ FRUITS ಪೋರ್ಟಲ್‌ನಲ್ಲಿ ನಿಮ್ಮ ರೈತ ಗುರುತಿನ ಸಂಖ್ಯೆ (FID) ಸಕ್ರಿಯವಾಗಿರಬೇಕು.' }}
                </p>
            </div>

            <!-- Step 2 -->
            <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-7 h-7 rounded-full bg-[#1C5A2C] text-white font-black text-xs flex items-center justify-center">2</span>
                    <span class="text-xl">💻</span>
                </div>
                <h4 class="text-xs font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? '2. Online Application' : '2. ಆನ್‌ಲೈನ್ ಅರ್ಜಿ ಸಲ್ಲಿಕೆ' }}
                </h4>
                <p class="text-[11px] text-stone-600 leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Submit application on official portal or visit your nearest Grama One / Seva Sindhu center.' : 'ಅಧಿಕೃತ ಪೋರ್ಟಲ್ ಮೂಲಕ ಅಥವಾ ಸಮೀಪದ ಗ್ರಾಮ ಒನ್ / ಸೇವಾ ಸಿಂಧು ಕೇಂದ್ರದಲ್ಲಿ ಅರ್ಜಿ ಸಲ್ಲಿಸಿ.' }}
                </p>
            </div>

            <!-- Step 3 -->
            <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-7 h-7 rounded-full bg-[#1C5A2C] text-white font-black text-xs flex items-center justify-center">3</span>
                    <span class="text-xl">🏛️</span>
                </div>
                <h4 class="text-xs font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? '3. RSK Verification' : '3. ಸ್ಥಳ ಪರಿಶೀಲನೆ & ಅನುಮೋದನೆ' }}
                </h4>
                <p class="text-[11px] text-stone-600 leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Your local Raitha Samparka Kendra (RSK) agriculture officer verifies records.' : 'ನಿಮ್ಮ ಸ್ಥಳೀಯ ರೈತ ಸಂಪರ್ಕ ಕೇಂದ್ರದ (RSK) ಕೃಷಿ ಅಧಿಕಾರಿಗಳು ದಾಖಲೆಗಳನ್ನು ಪರಿಶೀಲಿಸುತ್ತಾರೆ.' }}
                </p>
            </div>

            <!-- Step 4 -->
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="w-7 h-7 rounded-full bg-[#1C5A2C] text-white font-black text-xs flex items-center justify-center">4</span>
                    <span class="text-xl">💰</span>
                </div>
                <h4 class="text-xs font-black text-emerald-950 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? '4. DBT Subsidy Release' : '4. ನೇರ ಸಬ್ಸಿಡಿ ಜಮೆ (DBT)' }}
                </h4>
                <p class="text-[11px] text-emerald-900 leading-relaxed {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Subsidy or machinery grant is released directly to your account or verified dealer.' : 'ಅನುಮೋದನೆಗೊಂಡ ಸಬ್ಸಿಡಿ ಮೊತ್ತವು ನಿಮ್ಮ ಬ್ಯಾಂಕ್ ಖಾತೆಗೆ ನೇರವಾಗಿ ಜಮೆಯಾಗುತ್ತದೆ.' }}
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 6. KISAN CALL CENTRE & RSK HELPLINE BANNER                      -->
    <!-- ============================================================== -->
    <div class="rounded-3xl border border-[#E8E1D0] bg-white p-5 sm:p-6 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#E6F1E4] text-[#1C5A2C] flex items-center justify-center text-2xl shrink-0">
                📞
            </div>
            <div>
                <h4 class="text-sm font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Need Help with this Scheme?' : 'ಈ ಯೋಜನೆ ಬಗ್ಗೆ ಹೆಚ್ಚಿನ ಮಾಹಿತಿ ಅಥವಾ ನೆರವು ಬೇಕೆ?' }}
                </h4>
                <p class="text-xs text-stone-500 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' 
                        ? 'Call Kisan Call Centre Toll-Free (1800-180-1551) or contact your Hobli Raitha Samparka Kendra (RSK).' 
                        : 'ಕಿಸಾನ್ ಕಾಲ್ ಸೆಂಟರ್ ಉಚಿತ ಸಹಾಯವಾಣಿ 1800-180-1551 ಗೆ ಕರೆ ಮಾಡಿ ಅಥವಾ ನಿಮ್ಮ ಹೋಬಳಿ ರೈತ ಸಂಪರ್ಕ ಕೇಂದ್ರಕ್ಕೆ ಭೇಟಿ ನೀಡಿ.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="tel:18001801551" 
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#1C5A2C] hover:bg-[#144223] text-white font-black text-xs shadow-xs hover:shadow-sm transition">
                <span>📞</span>
                <span>1800-180-1551 (Toll-Free)</span>
            </a>
            <a href="https://fruits.karnataka.gov.in/" target="_blank" rel="noopener noreferrer" 
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-800 font-bold text-xs transition border border-stone-200">
                <span>FRUITS Portal ↗</span>
            </a>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 7. RELATED SCHEMES SECTION (with Dark Boxed Highlight)         -->
    <!-- ============================================================== -->
    @if($relatedSchemes->isNotEmpty())
        <div class="pt-6 border-t border-[#E8E1D0] space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-black text-stone-900 {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                    {{ $activeLocale === 'en' ? 'Related Agriculture Schemes' : 'ಇತರೆ ಸಂಬಂಧಿತ ಕೃಷಿ ಯೋಜನೆಗಳು (Related Schemes)' }}
                </h3>
                <a href="{{ route('farmer.schemes.index', ['category' => $scheme->category]) }}" class="text-xs font-bold text-[#1C5A2C] hover:underline">
                    {{ $activeLocale === 'en' ? 'View All in Category →' : 'ಈ ವರ್ಗದ ಎಲ್ಲಾ ಯೋಜನೆಗಳು →' }}
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($relatedSchemes as $rel)
                    @php
                        $relTitle = $activeLocale === 'en' ? ($rel->title ?: $rel->title_kn) : ($rel->title_kn ?: $rel->title);
                        $relBenefit = $activeLocale === 'en' ? ($rel->benefit_amount ?: $rel->benefit_amount_kn) : ($rel->benefit_amount_kn ?: $rel->benefit_amount);
                        $relEmoji = $rel->icon_emoji ?: '🌾';
                    @endphp
                    <a href="{{ route('farmer.schemes.show', $rel->slug) }}" 
                       class="group p-5 bg-white rounded-[24px] border border-[#E8E1D0] hover:border-[#1C5A2C] hover:shadow-lg transition-all flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-2xl">{{ $relEmoji }}</span>
                                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-[#FAF6EC] text-stone-600 border border-[#E8E1D0]">
                                    {{ $activeLocale === 'en' ? ($rel->category_label_en ?? ucfirst($rel->category)) : $rel->category_label_kn }}
                                </span>
                            </div>
                            <h4 class="text-xs font-black text-stone-900 group-hover:text-[#1C5A2C] transition line-clamp-2 leading-snug {{ $activeLocale === 'kn' ? 'font-kannada' : 'font-sans' }}">
                                {{ $relTitle }}
                            </h4>
                            @if($relBenefit)
                                <div class="p-2.5 rounded-xl bg-[#F2F8F3] text-[#144223] text-[11px] font-black line-clamp-2 border border-[#C2DFCA]">
                                    <span>💰</span> {{ $relBenefit }}
                                </div>
                            @endif
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-stone-100 flex items-center justify-between text-[11px] font-black text-[#1C5A2C]">
                            <span>{{ $activeLocale === 'en' ? 'View Details' : 'ವಿವರ ನೋಡಿ' }}</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
