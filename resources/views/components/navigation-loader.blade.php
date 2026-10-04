@php
    $activeLocale = app()->getLocale();
    $isEn = ($activeLocale === 'en');
    $appLogo = \App\Models\SystemSetting::get('app_logo', '/uploads/branding/app_logo_1790493250.png');
    $logoVersion = file_exists(public_path(ltrim($appLogo, '/'))) ? filemtime(public_path(ltrim($appLogo, '/'))) : '1';
    $appLogoUrl = asset($appLogo) . '?v=' . $logoVersion;
    $appName = \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava');
    $defaultTitle = $isEn ? 'Loading Page...' : 'ಪುಟ ಲೋಡ್ ಆಗುತ್ತಿದೆ...';
    $defaultSubtitle = $isEn ? 'Loading latest market data...' : 'ಮಾರುಕಟ್ಟೆಯ ಇತ್ತೀಚಿನ ಮಾಹಿತಿ ಪಡೆಯಲಾಗುತ್ತಿದೆ...';
@endphp

<style>
    /* ==========================================================================
       ORIGINAL KRUSHI BAANDHAVA LOADING WINDOW UI
       With Negilu-inspired smooth navigation lifecycle behavior:
       - Starts visible on target page until DOM + Alpine + assets fully settle
       - Slowly fades out smoothly over 0.4s once window.load completes
       - Appears instantly upon internal link navigation
       - Built-in 7s CSS + JS safety watchdogs and bfcache protection
       ========================================================================== */
    #globalNavigationLoader {
        position: fixed;
        inset: 0;
        z-index: 99998;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 28, 20, 0.42);
        backdrop-filter: blur(12px) saturate(160%);
        -webkit-backdrop-filter: blur(12px) saturate(160%);
        transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                    visibility 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        animation: kbLoaderSafety 0s linear 7s forwards;
        will-change: opacity, visibility;
    }

    #globalNavigationLoader.hide {
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    .kb-loader-card {
        background-color: #FAF8F5;
        border: 2px solid #D9CEB8;
        box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.35), 0 12px 24px -6px rgba(28, 90, 44, 0.2);
        transform: scale(1);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #globalNavigationLoader.hide .kb-loader-card {
        transform: scale(0.95);
    }

    /* Enforce strict font-family rules inside global navigation loader */
    #globalNavigationLoader,
    #globalNavigationLoader .font-sans,
    #globalNavigationLoader .is-en {
        font-family: 'Plus Jakarta Sans', 'Manrope', system-ui, -apple-system, sans-serif !important;
    }

    #globalNavigationLoader .font-kannada,
    #globalNavigationLoader .is-kn,
    #globalNavigationLoader [lang="kn"] {
        font-family: 'Noto Sans Kannada', 'Manrope', sans-serif !important;
    }

    @keyframes kbBarIndeterminate {
        0% {
            left: -35%;
            right: 100%;
        }
        60% {
            left: 100%;
            right: -90%;
        }
        100% {
            left: 100%;
            right: -90%;
        }
    }

    @keyframes kbBarIndeterminateShort {
        0% {
            left: -200%;
            right: 100%;
        }
        60% {
            left: 107%;
            right: -8%;
        }
        100% {
            left: 107%;
            right: -8%;
        }
    }

    .kb-progress-indeterminate::before {
        content: '';
        position: absolute;
        background-color: #1C5A2C;
        top: 0; left: 0; bottom: 0;
        will-change: left, right;
        animation: kbBarIndeterminate 1.8s cubic-bezier(0.65, 0.815, 0.735, 0.395) infinite;
        border-radius: 9999px;
    }

    .kb-progress-indeterminate::after {
        content: '';
        position: absolute;
        background-color: #2D8A46;
        top: 0; left: 0; bottom: 0;
        will-change: left, right;
        animation: kbBarIndeterminateShort 1.8s cubic-bezier(0.165, 0.84, 0.44, 1) infinite;
        animation-delay: 0.95s;
        border-radius: 9999px;
    }

    @keyframes kbLoaderSafety {
        to { opacity: 0; visibility: hidden; pointer-events: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .kb-loader-card {
            transition: none !important;
        }
    }
</style>

<!-- Full-Screen Glassmorphic Navigation Loading Window (Original UI preserved) -->
<div id="globalNavigationLoader" aria-hidden="false" role="status">
    <div class="kb-loader-card w-[260px] sm:w-[280px] rounded-3xl p-5 flex flex-col items-center text-center select-none">
        
        <!-- Application Logo Badge with Pulse and Micro-Spinner -->
        <div class="relative mb-3.5">
            <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-2xl bg-white border-2 border-[#D9CEB8] shadow-sm flex items-center justify-center p-2.5 overflow-hidden">
                <img src="{{ $appLogoUrl }}" 
                     alt="{{ $appName }}" 
                     class="w-full h-full object-contain animate-pulse">
            </div>
            <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-[#1C5A2C] text-white flex items-center justify-center text-xs font-black shadow-md ring-2 ring-white">
                <svg class="w-3 h-3 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Localized Loading Typography -->
        <div class="space-y-0.5">
            <h4 id="globalLoaderTitle" 
                class="kb-loader-title text-sm font-black text-[#1C5A2C] tracking-wide {{ $isEn ? 'font-sans is-en' : 'font-kannada is-kn' }}"
                style="{{ $isEn ? 'font-family: \'Plus Jakarta Sans\', \'Manrope\', system-ui, sans-serif !important;' : 'font-family: \'Noto Sans Kannada\', \'Manrope\', sans-serif !important;' }}">
                {{ $defaultTitle }}
            </h4>
            <p id="globalLoaderSubtitle" 
               class="kb-loader-subtitle text-[11px] text-stone-500 font-semibold tracking-tight {{ $isEn ? 'font-sans is-en' : 'font-kannada is-kn' }}"
               style="{{ $isEn ? 'font-family: \'Plus Jakarta Sans\', \'Manrope\', system-ui, sans-serif !important;' : 'font-family: \'Noto Sans Kannada\', \'Manrope\', sans-serif !important;' }}">
                {{ $defaultSubtitle }}
            </p>
        </div>

        <!-- Sleek Indeterminate Progress Bar in Forest Green -->
        <div class="w-full h-1.5 bg-[#E2DAC8] rounded-full overflow-hidden mt-3.5 relative kb-progress-indeterminate">
        </div>

    </div>
</div>

<script>
/* Page loader control:
   Preserves original Krushi Baandhava modal card UI, while adopting Negilu's smooth
   lifecycle behavior:
   1. Stays visible until the destination page, DOM, Alpine.js, and assets fully settle.
   2. Slowly fades out smoothly over 0.4s (via CSS transition).
   3. Instantly displays upon internal navigation clicks.
   4. Dual safety nets (CSS 7s animation + JS 7s watchdog) and bfcache protection. */
(function() {
    var L = document.getElementById('globalNavigationLoader');
    if (!L) return;

    var defaultTitle = @js($defaultTitle);
    var defaultSubtitle = @js($defaultSubtitle);
    var isDefaultKn = @js(!$isEn);

    function setLoaderContent(title, subtitle, isKn) {
        var titleEl = document.getElementById('globalLoaderTitle');
        var subEl = document.getElementById('globalLoaderSubtitle');
        var finalTitle = title || defaultTitle;
        var finalSub = subtitle || defaultSubtitle;
        var kn = (isKn !== undefined && isKn !== null) ? isKn : isDefaultKn;

        if (titleEl) {
            titleEl.textContent = finalTitle;
            if (kn) {
                titleEl.className = 'kb-loader-title text-sm font-black text-[#1C5A2C] tracking-wide font-kannada is-kn';
                titleEl.style.fontFamily = "'Noto Sans Kannada', 'Manrope', sans-serif";
            } else {
                titleEl.className = 'kb-loader-title text-sm font-black text-[#1C5A2C] tracking-wide font-sans is-en';
                titleEl.style.fontFamily = "'Plus Jakarta Sans', 'Manrope', system-ui, sans-serif";
            }
        }

        if (subEl) {
            subEl.textContent = finalSub;
            if (kn) {
                subEl.className = 'kb-loader-subtitle text-[11px] text-stone-500 font-semibold tracking-tight font-kannada is-kn';
                subEl.style.fontFamily = "'Noto Sans Kannada', 'Manrope', sans-serif";
            } else {
                subEl.className = 'kb-loader-subtitle text-[11px] text-stone-500 font-semibold tracking-tight font-sans is-en';
                subEl.style.fontFamily = "'Plus Jakarta Sans', 'Manrope', system-ui, sans-serif";
            }
        }
    }

    function hide() {
        L.style.animation = 'none';
        L.classList.add('hide');
        L.setAttribute('aria-hidden', 'true');
    }

    function show(title, subtitle, isKn) {
        setLoaderContent(title, subtitle, isKn);
        L.style.animation = 'none';
        L.classList.remove('hide');
        L.setAttribute('aria-hidden', 'false');
    }

    // Expose helpers globally
    window.showPageLoader = show;
    window.hidePageLoader = hide;
    window.setPageLoaderContent = setLoaderContent;

    // Slowly fade out once destination page DOM, Alpine, and primary assets are completely ready
    if (document.readyState === 'complete') {
        setTimeout(hide, 140);
    } else {
        window.addEventListener('load', function() {
            setTimeout(hide, 140);
        });
    }

    // Safety watchdog: auto-hide after 7 seconds if anything stalls
    setTimeout(hide, 7000);

    // Back/Forward cache (bfcache): dismiss immediately on restore
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) hide();
    });

    // Bubble phase click listener on internal links
    document.addEventListener('click', function(e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        // Skip buttons/links that do in-place actions or downloads
        if (a.target === '_blank' || 
            a.hasAttribute('download') || 
            a.hasAttribute('data-no-loader') || 
            a.closest('[data-no-loader]') || 
            a.hasAttribute('data-video-id') || 
            a.classList.contains('wa') || 
            a.classList.contains('tour-btn') || 
            a.hasAttribute('data-tour')) {
            return;
        }

        var href = a.getAttribute('href') || '';
        if (!href || href[0] === '#' || /^(mailto:|tel:|javascript:|whatsapp:)/i.test(href)) return;

        var u;
        try {
            u = new URL(a.href, location.href);
        } catch (_) {
            return;
        }

        // Only same-origin navigations
        if (u.origin !== location.origin) return;

        // In-page hash anchors on the exact same page
        if (u.pathname === location.pathname && (u.hash || u.search === location.search)) return;

        // Custom friendly status message for language switching
        if (u.pathname.includes('/locale/en') || u.searchParams.get('lang') === 'en') {
            show('Switching to English...', 'Loading application in English...', false);
            return;
        }
        if (u.pathname.includes('/locale/kn') || u.searchParams.get('lang') === 'kn') {
            show('ಕನ್ನಡಕ್ಕೆ ಬದಲಾಯಿಸಲಾಗುತ್ತಿದೆ...', 'ಕನ್ನಡದಲ್ಲಿ ಮಾಹಿತಿ ಸಿದ್ಧವಾಗುತ್ತಿದೆ...', true);
            return;
        }

        // Show immediately with standard loading card text
        show();
    }, false);

    // Form submission support (e.g. search / filters without AJAX)
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || e.defaultPrevented || form.hasAttribute('data-no-loader') || form.getAttribute('target') === '_blank') return;
        show();
    }, false);
})();
</script>
