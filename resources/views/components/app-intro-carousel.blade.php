@php
    $appLogo = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
    $appName = \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava');
    $logoVersion = file_exists(public_path(ltrim($appLogo, '/'))) ? filemtime(public_path(ltrim($appLogo, '/'))) : '1';
    $appLogoUrl = asset($appLogo) . '?v=' . $logoVersion;
@endphp

{{-- ==========================================================================
     Krushi Baandhava — Android-Grade Full-Screen Welcome & Onboarding Screen
     ==========================================================================
     • 100% SOLID OPAQUE FULL-SCREEN: No transparent bleed-through to the homepage.
     • Native Android Studio Onboarding Pattern (ViewPager + Dots + Next/Skip + Get Started).
     • Official Krushi Baandhava App Icon prominently integrated in every slide.
     • Splash screen strictly yields pointer events and disappears completely.
     • Preserves the separate "How to Use" homepage tour (window.startKrushiTour).
     ========================================================================== --}}

<style>
/* Fullscreen Opaque Overlay (Android Activity Style) */
#krushiAppIntro {
    position: fixed !important;
    inset: 0 !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100dvh !important;
    max-height: 100vh !important;
    background-color: #FAF8F5 !important;
    background: radial-gradient(circle at 50% 12%, #FFFFFF 0%, #FAF5EC 55%, #F4ECE0 100%) !important;
    z-index: 999999 !important;
    display: none;
    flex-direction: column !important;
    justify-content: space-between !important;
    overflow: hidden !important;
    user-select: none !important;
    -webkit-user-select: none !important;
    opacity: 0;
    transition: opacity 0.35s ease !important;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif !important;
    color: #1f2937 !important;
    box-sizing: border-box !important;
    pointer-events: auto !important;
}

#krushiAppIntro * {
    box-sizing: border-box !important;
}

#krushiAppIntro.kb-intro-visible {
    opacity: 1 !important;
}

/* Strictly hide on desktop viewport (>= 1024px) unless explicitly forced via ?intro=1 */
@media (min-width: 1024px) {
    #krushiAppIntro:not([data-kb-forced="true"]) {
        display: none !important;
    }
}

/* 1. WELCOME SPLASH SCREEN LAYER */
#kbIntroSplash {
    position: absolute !important;
    inset: 0 !important;
    background: #FAF8F5 !important;
    background: radial-gradient(circle at 50% 35%, #FFFFFF 0%, #FAF5EC 50%, #F4ECE0 100%) !important;
    z-index: 50 !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    text-align: center;
    transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s !important;
    pointer-events: auto;
}

#kbIntroSplash.kb-splash-fading {
    opacity: 0 !important;
    pointer-events: none !important;
}

#kbIntroSplash.kb-splash-hidden {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

.kb-splash-icon-wrapper {
    position: relative;
    margin-bottom: 24px;
}

.kb-splash-pulse-ring {
    position: absolute;
    inset: -12px;
    border-radius: 38px;
    background: rgba(28, 90, 44, 0.12);
    animation: kbPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 1;
}

@keyframes kbPulse {
    0%, 100% { transform: scale(1); opacity: 0.6; }
    50% { transform: scale(1.1); opacity: 0.2; }
}

.kb-splash-icon-box {
    position: relative;
    z-index: 2;
    width: 108px;
    height: 108px;
    border-radius: 30px;
    background: linear-gradient(135deg, #1C5A2C 0%, #164622 100%);
    padding: 10px;
    border: 3px solid #E2D7C3;
    box-shadow: 0 20px 45px -10px rgba(28, 90, 44, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
}

.kb-splash-title {
    font-size: 27px;
    font-weight: 900;
    color: #143e1e;
    margin: 0 0 6px 0;
    font-family: 'Noto Sans Kannada', sans-serif !important;
    letter-spacing: -0.01em;
}

.kb-splash-sub {
    font-size: 14px;
    font-weight: 700;
    color: #1C5A2C;
    margin: 0 0 4px 0;
    font-family: 'Noto Sans Kannada', sans-serif !important;
}

.kb-splash-en {
    font-size: 11px;
    font-weight: 600;
    color: #78716c;
    margin: 0 0 26px 0;
}

.kb-splash-loader {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #EAF4EC;
    border: 1px solid #B8DEC0;
    color: #1C5A2C;
    padding: 8px 18px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 800;
    font-family: 'Noto Sans Kannada', sans-serif !important;
}

/* 2. TOP BAR (ALWAYS ON TOP, FULLY CLICKABLE) */
.kb-intro-top-bar {
    position: relative !important;
    z-index: 100 !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    padding: max(16px, env(safe-area-inset-top)) 24px 8px 24px;
    flex-shrink: 0;
    pointer-events: auto !important;
}

.kb-top-brand {
    display: flex;
    align-items: center;
    gap: 10px;
}

.kb-top-logo-badge {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #FFFFFF;
    border: 1.5px solid #E2D7C3;
    padding: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.kb-top-logo {
    width: 100%;
    height: 100%;
    border-radius: 7px;
    object-fit: contain;
}

.kb-top-name {
    font-size: 15px;
    font-weight: 900;
    color: #1C5A2C;
    font-family: 'Noto Sans Kannada', sans-serif !important;
}

.kb-skip-link {
    background: #FFFFFF !important;
    border: 1.5px solid #E5DBCB !important;
    color: #57534E !important;
    font-size: 12px !important;
    font-weight: 800 !important;
    cursor: pointer !important;
    padding: 6px 14px !important;
    border-radius: 9999px !important;
    transition: all 0.15s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04) !important;
    pointer-events: auto !important;
    touch-action: manipulation !important;
}

.kb-skip-link:hover, .kb-skip-link:active {
    color: #1C5A2C !important;
    background: #EAF4EC !important;
    border-color: #B8DEC0 !important;
    transform: translateY(-1px);
}

/* 3. MAIN PAGER CONTAINER */
.kb-intro-pager {
    position: relative !important;
    z-index: 10 !important;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    overflow: hidden;
    padding: 0 16px;
    touch-action: pan-y;
}

.kb-pager-track {
    display: flex;
    width: 100%;
    transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    will-change: transform;
}

.kb-pager-slide {
    width: 100%;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 6px 14px;
}

/* Central Illustration Artworks with Official App Icon */
.kb-art-container {
    width: min(290px, 80vw);
    min-height: 240px;
    margin: 0 auto 20px auto;
    border-radius: 28px;
    background: linear-gradient(150deg, #FFFFFF 0%, #FAF4E8 55%, #F3EBDD 100%);
    border: 2px solid #E7DDD0;
    box-shadow: 0 18px 36px -10px rgba(71, 55, 34, 0.14), 0 4px 12px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    position: relative;
    overflow: hidden;
}

.kb-art-top-row {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    font-weight: 800;
}

/* Central App Icon Hero Frame on Slide Cards */
.kb-slide-app-hero {
    position: relative;
    margin: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 0;
}

.kb-slide-icon-ring {
    width: 82px;
    height: 82px;
    border-radius: 26px;
    background: #FFFFFF;
    border: 3px solid #1C5A2C;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 28px -6px rgba(28, 90, 44, 0.35);
    position: relative;
}

.kb-slide-icon-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 18px;
}

.kb-slide-feature-pill {
    margin-top: 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #FFFFFF;
    border: 1px solid #E2D7C5;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 800;
    color: #1C5A2C;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}

.kb-art-bottom-pill {
    width: 100%;
    background: #FFFFFF;
    border: 1.5px solid #E5DBCB;
    border-radius: 14px;
    padding: 9px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 800;
    box-shadow: 0 3px 8px rgba(0,0,0,0.04);
}

/* Slide Text Elements */
.kb-slide-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #EAF4EC;
    color: #1C5A2C;
    border: 1px solid #B8DEC0;
    font-size: 11px;
    font-weight: 900;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.kb-slide-heading {
    font-size: 22px;
    font-weight: 900;
    line-height: 1.3;
    color: #111827;
    margin: 0 0 4px 0;
    font-family: 'Noto Sans Kannada', sans-serif !important;
}

.kb-slide-sub-en {
    font-size: 12px;
    font-weight: 700;
    color: #4b5563;
    margin: 0 0 8px 0;
}

.kb-slide-body {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.55;
    color: #4b5563;
    margin: 0 0 14px 0;
    max-width: 350px;
    font-family: 'Noto Sans Kannada', sans-serif !important;
}

/* Feature Highlights */
.kb-highlights-list {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}

.kb-highlight-badge {
    background: #FFFFFF;
    border: 1px solid #E2D7C5;
    color: #1C5A2C;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 11px;
    border-radius: 9999px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}

/* 4. BOTTOM ACTION BAR (Native Android Style) */
.kb-intro-bottom-bar {
    position: relative !important;
    z-index: 100 !important;
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    padding: 12px 24px max(24px, env(safe-area-inset-bottom)) 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    pointer-events: auto !important;
}

/* Page Indicator Dots */
.kb-pager-dots {
    display: flex;
    align-items: center;
    gap: 8px;
    pointer-events: auto !important;
}

.kb-pager-dot {
    height: 9px;
    width: 9px;
    border-radius: 9999px;
    background: #D5CBBB;
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0;
    outline: none;
    touch-action: manipulation !important;
}

.kb-pager-dot.active {
    width: 28px;
    background: #1C5A2C;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(28, 90, 44, 0.35);
}

/* Next / Get Started Buttons */
.kb-action-right {
    display: flex;
    align-items: center;
    gap: 10px;
    pointer-events: auto !important;
}

/* Round Circular Next FAB Button */
.kb-fab-next {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1C5A2C 0%, #144621 100%);
    color: #FFFFFF;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 8px 22px rgba(28, 90, 44, 0.38);
    cursor: pointer !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    pointer-events: auto !important;
    touch-action: manipulation !important;
}

.kb-fab-next:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 26px rgba(28, 90, 44, 0.45);
}

.kb-fab-next:active {
    transform: scale(0.92);
}

/* Final Get Started Button */
.kb-get-started-btn {
    display: none;
    background: linear-gradient(135deg, #1C5A2C 0%, #123d1c 100%);
    color: #FFFFFF;
    border: none;
    padding: 14px 26px;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 900;
    cursor: pointer !important;
    font-family: 'Noto Sans Kannada', sans-serif !important;
    box-shadow: 0 10px 24px rgba(28, 90, 44, 0.38);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    pointer-events: auto !important;
    touch-action: manipulation !important;
    align-items: center;
    gap: 8px;
}

.kb-get-started-btn:hover {
    transform: scale(1.03);
    box-shadow: 0 12px 28px rgba(28, 90, 44, 0.45);
}

.kb-get-started-btn:active {
    transform: scale(0.95);
}
</style>

<div id="krushiAppIntro" role="dialog" aria-modal="true" aria-label="Welcome to Krushi Baandhava">

    {{-- ============================================================== --}}
    {{-- A. WELCOME SPLASH LAYER (Shows on launch for 1.2 seconds)       --}}
    {{-- ============================================================== --}}
    <div id="kbIntroSplash">
        <div class="kb-splash-icon-wrapper">
            <div class="kb-splash-pulse-ring"></div>
            <div class="kb-splash-icon-box">
                <img src="{{ $appLogoUrl }}" alt="{{ $appName }}" style="width: 100%; height: 100%; object-fit: contain; border-radius: 20px;" onerror="this.onerror=null; this.src='{{ asset('icons/icon-192.svg') }}';">
            </div>
        </div>

        <h1 class="kb-splash-title">
            {{ $appName }}
        </h1>
        <p class="kb-splash-sub">
            ಕೃಷಿ ಬಾಂಧವ • ರೈತರ ನಂಬಿಕಸ್ತ ಒಡನಾಡಿ
        </p>
        <p class="kb-splash-en">
            Karnataka Farmers' Trusted Agricultural Market Hub
        </p>

        <div class="kb-splash-loader">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #1C5A2C; display: inline-block;"></span>
            <span>ಸ್ವಾಗತ... ಪ್ರಾರಂಭಿಸಲಾಗುತ್ತಿದೆ</span>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- B. TOP BAR: Brand Logo & Clickable Skip Link                    --}}
    {{-- ============================================================== --}}
    <header class="kb-intro-top-bar">
        <div class="kb-top-brand">
            <div class="kb-top-logo-badge">
                <img src="{{ $appLogoUrl }}" alt="Logo" class="kb-top-logo" onerror="this.onerror=null; this.src='{{ asset('icons/icon-192.svg') }}';">
            </div>
            <span class="kb-top-name">ಕೃಷಿ ಬಾಂಧವ</span>
        </div>

        <button type="button" class="kb-skip-link" id="kbIntroSkipBtn">
            <span>Skip (ಬಿಟ್ಟುಬಿಡಿ)</span>
            <span style="font-size: 13px;">✕</span>
        </button>
    </header>

    {{-- ============================================================== --}}
    {{-- C. MAIN PAGER CONTAINER (3 HIGH-QUALITY NATIVE SLIDES)         --}}
    {{-- ============================================================== --}}
    <main class="kb-intro-pager" id="kbIntroSwipeArea">
        <div class="kb-pager-track" id="kbIntroTrack">

            {{-- SLIDE 1: DAILY MANDI PRICES --}}
            <div class="kb-pager-slide">
                <div class="kb-art-container">
                    <div class="kb-art-top-row" style="color: #1C5A2C;">
                        <span>🌾 ಕರ್ನಾಟಕದ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳು</span>
                        <span style="background: #EAF4EC; border: 1px solid #B8DEC0; padding: 2px 8px; border-radius: 6px;">150+ APMC</span>
                    </div>

                    <!-- Slide 1 App Icon Integration -->
                    <div class="kb-slide-app-hero">
                        <div class="kb-slide-icon-ring" style="border-color: #1C5A2C;">
                            <img src="{{ $appLogoUrl }}" alt="Krushi Baandhava" class="kb-slide-icon-img" onerror="this.onerror=null; this.src='{{ asset('icons/icon-192.svg') }}';">
                        </div>
                        <div class="kb-slide-feature-pill">
                            <span>⚡ ಅಧಿಕೃತ ಮಂಡಿ ನೇರ ಧಾರಣೆ</span>
                        </div>
                    </div>

                    <div class="kb-art-bottom-pill">
                        <div style="text-align: left;">
                            <div style="color: #111827; font-weight: 800;">ಅಡಿಕೆ • Arecanut</div>
                            <div style="color: #6b7280; font-size: 10px;">ಶಿವಮೊಗ್ಗ / ಸಾಗರ ಮಂಡಿ</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: #1C5A2C; font-weight: 900; font-size: 13px;">₹56,400</div>
                            <div style="color: #6b7280; font-size: 10px;">ಕ್ವಿಂಟಾಲ್‌ಗೆ</div>
                        </div>
                    </div>
                </div>

                <div class="kb-slide-tag">
                    <span>⚡</span> ನೈಜ ಮಾರುಕಟ್ಟೆ ದರಗಳು
                </div>
                <h2 class="kb-slide-heading">
                    ದೈನಂದಿನ ಅಧಿಕೃತ ಮಂಡಿ ದರಗಳು
                </h2>
                <div class="kb-slide-sub-en">
                    Daily Official APMC Mandi Rates
                </div>
                <p class="kb-slide-body">
                    ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳ ಇಂದಿನ ನಿಖರ, ಕನಿಷ್ಠ ಹಾಗೂ ಗರಿಷ್ಠ ದರಗಳು ನಿಮ್ಮ ಮೊಬೈಲ್‌ನಲ್ಲೇ ಕ್ಷಣಾರ್ಧದಲ್ಲಿ ಲಭ್ಯ.
                </p>

                <div class="kb-highlights-list">
                    <span class="kb-highlight-badge">📍 ಸಮೀಪದ ಮಂಡಿ</span>
                    <span class="kb-highlight-badge">⚡ ನೇರ ದರಗಳು</span>
                    <span class="kb-highlight-badge">⚖️ ಅಧಿಕೃತ ಧಾರಣೆ</span>
                </div>
            </div>

            {{-- SLIDE 2: SMART PRICE TRENDS & ADVISORY --}}
            <div class="kb-pager-slide">
                <div class="kb-art-container">
                    <div class="kb-art-top-row" style="color: #0284C7;">
                        <span>📊 6 ವರ್ಷಗಳ ಇತಿಹಾಸ</span>
                        <span style="background: #E0F2FE; border: 1px solid #BAE6FD; padding: 2px 8px; border-radius: 6px;">AI ವಿಶ್ಲೇಷಣೆ</span>
                    </div>

                    <!-- Slide 2 App Icon Integration + Growth Trend -->
                    <div class="kb-slide-app-hero">
                        <div class="kb-slide-icon-ring" style="border-color: #0284C7;">
                            <img src="{{ $appLogoUrl }}" alt="Krushi Baandhava" class="kb-slide-icon-img" onerror="this.onerror=null; this.src='{{ asset('icons/icon-192.svg') }}';">
                        </div>
                        <div class="kb-slide-feature-pill" style="color: #0284C7; border-color: #BAE6FD; background: #F0F9FF;">
                            <span>📈 ದರ ಏರಿಕೆಯ ಮುನ್ಸೂಚನೆ & ಸಲಹೆ</span>
                        </div>
                    </div>

                    <div class="kb-art-bottom-pill">
                        <div style="text-align: left;">
                            <div style="color: #111827; font-weight: 800;"><span style="color: #16A34A;">▲ +14.6%</span> ಏರಿಕೆ ಮುನ್ಸೂಚನೆ</div>
                            <div style="color: #6b7280; font-size: 10px;">ಮುಂದಿನ 15 ದಿನಗಳ ಅಂದಾಜು</div>
                        </div>
                        <div style="background: #EAF4EC; border: 1px solid #B8DEC0; color: #1C5A2C; padding: 3px 8px; border-radius: 6px; font-weight: 900; font-size: 10px;">
                            ಮಾರಾಟಕ್ಕೆ ಸೂಕ್ತ
                        </div>
                    </div>
                </div>

                <div class="kb-slide-tag" style="background: #E0F2FE; color: #0369A1; border-color: #BAE6FD;">
                    <span>📈</span> ಬೆಲೆ ಮುನ್ಸೂಚನೆ & ಸಲಹೆ
                </div>
                <h2 class="kb-slide-heading">
                    ದರ ಏರಿಕೆಯ ಮುನ್ಸೂಚನೆ & ಸಲಹೆ
                </h2>
                <div class="kb-slide-sub-en">
                    Smart Price Forecast & Selling Advisory
                </div>
                <p class="kb-slide-body">
                    ಬೆಲೆ ಯಾವಾಗ ಏರುತ್ತದೆ, ಯಾವಾಗ ಇಳಿಯುತ್ತದೆ? 6 ವರ್ಷಗಳ ಮಾರುಕಟ್ಟೆ ಇತಿಹಾಸ ಪರಿಶೀಲಿಸಿ ನಿಮ್ಮ ಬೆಳೆಗೆ ಗರಿಷ್ಠ ಲಾಭ ಗಳಿಸಿ.
                </p>

                <div class="kb-highlights-list">
                    <span class="kb-highlight-badge" style="color: #0369A1;">🔮 AI ಮುನ್ಸೂಚನೆ</span>
                    <span class="kb-highlight-badge" style="color: #0369A1;">🎯 ಗರಿಷ್ಠ ಲಾಭ</span>
                    <span class="kb-highlight-badge" style="color: #0369A1;">📅 ಯಾವಾಗ ಮಾರಬೇಕು?</span>
                </div>
            </div>

            {{-- SLIDE 3: GOVT SCHEMES & WEATHER --}}
            <div class="kb-pager-slide">
                <div class="kb-art-container">
                    <div class="kb-art-top-row" style="color: #D97706;">
                        <span>🏛️ ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು</span>
                        <span style="background: #FEF3C7; border: 1px solid #FDE68A; padding: 2px 8px; border-radius: 6px;">ನೇರ ಸಬ್ಸಿಡಿ</span>
                    </div>

                    <!-- Slide 3 App Icon Integration + Schemes/Weather -->
                    <div class="kb-slide-app-hero">
                        <div class="kb-slide-icon-ring" style="border-color: #D97706;">
                            <img src="{{ $appLogoUrl }}" alt="Krushi Baandhava" class="kb-slide-icon-img" onerror="this.onerror=null; this.src='{{ asset('icons/icon-192.svg') }}';">
                        </div>
                        <div class="kb-slide-feature-pill" style="color: #B45309; border-color: #FDE68A; background: #FFFBEB;">
                            <span>🏛️ ರೈತರ ಸಬ್ಸಿಡಿ & ಹವಾಮಾನ</span>
                        </div>
                    </div>

                    <div class="kb-art-bottom-pill">
                        <div style="text-align: left;">
                            <div style="color: #111827; font-weight: 800;">ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ ಸಬ್ಸಿಡಿ</div>
                            <div style="color: #D97706; font-size: 10px;">50% - 90% ಸರ್ಕಾರದ ಅನುದಾನ</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: #0284C7; font-weight: 900; font-size: 12px;">🌦️ 28°C</div>
                            <div style="color: #6b7280; font-size: 10px;">ಮಳೆ ಮುನ್ಸೂಚನೆ</div>
                        </div>
                    </div>
                </div>

                <div class="kb-slide-tag" style="background: #FEF3C7; color: #B45309; border-color: #FDE68A;">
                    <span>🏛️</span> ರೈತ ಯೋಜನೆಗಳು & ಹವಾಮಾನ
                </div>
                <h2 class="kb-slide-heading">
                    ಸರ್ಕಾರಿ ಸಬ್ಸಿಡಿ & ಹವಾಮಾನ ಮಾಹಿತಿ
                </h2>
                <div class="kb-slide-sub-en">
                    Govt Welfare Schemes & Local Weather
                </div>
                <p class="kb-slide-body">
                    ರೈತರ ಕೃಷಿ ಯೋಜನೆಗಳು, ನೇರ ಸಬ್ಸಿಡಿ ಅರ್ಜಿ ಲಿಂಕ್‌ಗಳು ಹಾಗೂ ನಿಮ್ಮ ಗ್ರಾಮದ ನಿಖರ ಮಳೆ-ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ ಒಂದೇ ಸೂರಿನಡಿ.
                </p>

                <div class="kb-highlights-list">
                    <span class="kb-highlight-badge" style="color: #B45309;">💰 ನೇರ ಸಬ್ಸಿಡಿ</span>
                    <span class="kb-highlight-badge" style="color: #B45309;">⛅ ಮಳೆ ಮುನ್ಸೂಚನೆ</span>
                    <span class="kb-highlight-badge" style="color: #B45309;">📲 ಸುಲಭ ಮಾಹಿತಿ</span>
                </div>
            </div>

        </div>
    </main>

    {{-- ============================================================== --}}
    {{-- D. BOTTOM ACTION BAR: Dots & Round Next FAB Button             --}}
    {{-- ============================================================== --}}
    <footer class="kb-intro-bottom-bar">
        <!-- Dots Pager Indicator -->
        <div class="kb-pager-dots">
            <button type="button" class="kb-pager-dot active" data-slide="0" aria-label="Slide 1"></button>
            <button type="button" class="kb-pager-dot" data-slide="1" aria-label="Slide 2"></button>
            <button type="button" class="kb-pager-dot" data-slide="2" aria-label="Slide 3"></button>
        </div>

        <!-- Right Side: Circular FAB Next Button OR Get Started Button -->
        <div class="kb-action-right">
            <!-- Circular Arrow Next Button (Slides 1 & 2) -->
            <button type="button" class="kb-fab-next" id="kbIntroNextFab" aria-label="Next Slide">
                <span>→</span>
            </button>

            <!-- Prominent Get Started Button (Slide 3) -->
            <button type="button" class="kb-get-started-btn" id="kbIntroGetStartedBtn">
                <span>ಪ್ರಾರಂಭಿಸಿ 🚀 (Get Started)</span>
            </button>
        </div>
    </footer>

</div>

<script>
(function () {
    'use strict';

    const STORAGE_KEY = 'kb_onboarding_shown';
    const modal = document.getElementById('krushiAppIntro');
    if (!modal) return;

    const splash = document.getElementById('kbIntroSplash');
    const track = document.getElementById('kbIntroTrack');
    const swipeArea = document.getElementById('kbIntroSwipeArea');
    const dots = document.querySelectorAll('.kb-pager-dot');
    const nextFab = document.getElementById('kbIntroNextFab');
    const getStartedBtn = document.getElementById('kbIntroGetStartedBtn');
    const skipBtn = document.getElementById('kbIntroSkipBtn');

    let currentSlide = 0;
    const totalSlides = 3;

    function updateSlide(index) {
        currentSlide = Math.max(0, Math.min(index, totalSlides - 1));

        // Slide movement
        if (track) {
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
        }

        // Update Dots
        dots.forEach((dot, idx) => {
            if (idx === currentSlide) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });

        // Switch between Circular FAB Next and Get Started button on last slide
        if (currentSlide === totalSlides - 1) {
            if (nextFab) nextFab.style.display = 'none';
            if (getStartedBtn) getStartedBtn.style.display = 'inline-flex';
        } else {
            if (nextFab) nextFab.style.display = 'flex';
            if (getStartedBtn) getStartedBtn.style.display = 'none';
        }
    }

    function openIntro(force = false) {
        const urlParams = new URLSearchParams(window.location.search);
        if (force || urlParams.has('intro') || urlParams.has('welcome') || urlParams.has('onboarding')) {
            modal.setAttribute('data-kb-forced', 'true');
        }

        modal.style.display = 'flex';
        modal.setAttribute('data-kb-modal-open', 'true');
        // Force reflow
        void modal.offsetWidth;
        modal.classList.add('kb-intro-visible');
        document.body.style.overflow = 'hidden';
        updateSlide(0);

        // Show Welcome Splash on first launch, then transition to slides
        if (splash) {
            splash.classList.remove('kb-splash-fading', 'kb-splash-hidden');
            splash.style.display = 'flex';
            splash.style.opacity = '1';
            splash.style.pointerEvents = 'auto';

            setTimeout(() => {
                // Immediately disable pointer events when fading out so clicks are never blocked!
                splash.style.pointerEvents = 'none';
                splash.classList.add('kb-splash-fading');
                setTimeout(() => {
                    splash.classList.add('kb-splash-hidden');
                    splash.style.display = 'none';
                }, 450);
            }, 1200);
        }
    }

    function closeIntro() {
        // Immediately dismiss splash if still visible
        if (splash) {
            splash.classList.add('kb-splash-hidden');
            splash.style.display = 'none';
            splash.style.pointerEvents = 'none';
        }

        modal.removeAttribute('data-kb-modal-open');
        modal.removeAttribute('data-kb-forced');
        modal.classList.remove('kb-intro-visible');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);

        try {
            localStorage.setItem(STORAGE_KEY, 'true');
        } catch (e) {}
    }

    let isTransitioning = false;

    // Next / Prev slide handlers (debounced to advance exactly one slide at a time)
    function goNext() {
        if (isTransitioning) return;
        isTransitioning = true;
        setTimeout(() => { isTransitioning = false; }, 350);

        if (currentSlide < totalSlides - 1) {
            updateSlide(currentSlide + 1);
        } else {
            closeIntro();
        }
    }

    function goPrev() {
        if (isTransitioning) return;
        isTransitioning = true;
        setTimeout(() => { isTransitioning = false; }, 350);

        if (currentSlide > 0) {
            updateSlide(currentSlide - 1);
        }
    }

    // Attach to window for inline onclick hooks & external control
    window.krushiIntroNext = goNext;
    window.krushiIntroPrev = goPrev;
    window.krushiIntroGoTo = function (idx) {
        updateSlide(idx);
    };
    window.krushiIntroClose = closeIntro;
    window.openKrushiIntro = function () {
        openIntro(true);
    };
    window.resetKrushiIntro = function () {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {}
        openIntro(true);
    };

    // Button Listeners (in addition to inline onclick)
    if (nextFab) {
        nextFab.addEventListener('click', function (e) {
            e.stopPropagation();
            goNext();
        });
    }

    if (getStartedBtn) {
        getStartedBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeIntro();
        });
    }

    if (skipBtn) {
        skipBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeIntro();
        });
    }

    dots.forEach(dot => {
        dot.addEventListener('click', function (e) {
            e.stopPropagation();
            const target = parseInt(this.getAttribute('data-slide'), 10);
            if (!isNaN(target)) updateSlide(target);
        });
    });

    // Touch Swipe Gesture Support (Natural Mobile Swiping)
    if (swipeArea) {
        let touchStartX = 0;
        let touchStartY = 0;

        swipeArea.addEventListener('touchstart', function (e) {
            if (e.changedTouches && e.changedTouches[0]) {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }
        }, { passive: true });

        swipeArea.addEventListener('touchend', function (e) {
            if (!e.changedTouches || !e.changedTouches[0]) return;
            const touchEndX = e.changedTouches[0].screenX;
            const touchEndY = e.changedTouches[0].screenY;

            const diffX = touchEndX - touchStartX;
            const diffY = touchEndY - touchStartY;

            // Ensure horizontal swipe is dominant
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 40) {
                if (diffX < 0) {
                    // Swiped Left -> Next
                    goNext();
                } else {
                    // Swiped Right -> Prev
                    goPrev();
                }
            }
        }, { passive: true });
    }

    // Keyboard support (Arrow keys and Escape)
    document.addEventListener('keydown', function (e) {
        if (modal.style.display !== 'flex') return;
        if (e.key === 'ArrowRight') {
            goNext();
        } else if (e.key === 'ArrowLeft') {
            goPrev();
        } else if (e.key === 'Escape') {
            closeIntro();
        }
    });

    // Check if running in PWA environment (standalone app on mobile)
    function isPwaEnvironment() {
        const urlParams = new URLSearchParams(window.location.search);

        // 1. Manual tester / developer override via URL query param
        if (urlParams.has('intro') || urlParams.has('welcome') || urlParams.has('onboarding')) {
            return true;
        }

        // 2. Strict desktop check: Never show on regular desktop browser viewports
        const isDesktopViewport = window.innerWidth >= 1024;
        if (isDesktopViewport) {
            return false;
        }

        // 3. PWA Standalone display mode check
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                             window.matchMedia('(display-mode: fullscreen)').matches ||
                             window.matchMedia('(display-mode: minimal-ui)').matches ||
                             window.navigator.standalone === true;

        // 4. PWA launch parameter from Web App Manifest start_url (?source=pwa) or TWA
        const isPwaSource = urlParams.get('source') === 'pwa' ||
                            urlParams.get('pwa') === '1' ||
                            urlParams.get('app') === 'pwa' ||
                            (document.referrer && document.referrer.startsWith('android-app://'));

        return isStandalone || isPwaSource;
    }

    // Auto-check on first-time visit (STRICTLY PWA ONLY - NEVER REGULAR DESKTOP)
    function checkFirstTime() {
        if (!isPwaEnvironment()) {
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const forceIntro = urlParams.has('intro') || urlParams.has('welcome') || urlParams.has('onboarding');

        let alreadyShown = false;
        try {
            alreadyShown = localStorage.getItem(STORAGE_KEY) === 'true';
        } catch (e) {}

        if (forceIntro || !alreadyShown) {
            setTimeout(openIntro, 150);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkFirstTime);
    } else {
        checkFirstTime();
    }
})();
</script>
