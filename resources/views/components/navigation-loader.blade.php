<style>
    /* Global Navigation Transition Loader Styles */
    #globalNavigationLoader {
        position: fixed;
        inset: 0;
        z-index: 99998;
        display: flex;
        align-items: center;
        justify-content: center;
        /* Smooth, elegant translucent frosted veil instead of opaque white */
        background: rgba(15, 28, 20, 0.42);
        backdrop-filter: blur(12px) saturate(160%);
        -webkit-backdrop-filter: blur(12px) saturate(160%);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 220ms cubic-bezier(0.16, 1, 0.3, 1), 
                    visibility 220ms cubic-bezier(0.16, 1, 0.3, 1);
    }

    #globalNavigationLoader.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .kb-loader-card {
        background-color: #FAF8F5;
        border: 2px solid #D9CEB8;
        box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.35), 0 12px 24px -6px rgba(28, 90, 44, 0.2);
        transform: scale(0.95);
        transition: transform 240ms cubic-bezier(0.16, 1, 0.3, 1);
    }

    #globalNavigationLoader.is-visible .kb-loader-card {
        transform: scale(1);
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
</style>

@php
    $appLogo = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
    $logoVersion = file_exists(public_path(ltrim($appLogo, '/'))) ? filemtime(public_path(ltrim($appLogo, '/'))) : '1';
    $appLogoUrl = asset($appLogo) . '?v=' . $logoVersion;
    $appName = \App\Models\SystemSetting::get('application_name', 'Krushi Baandhava');
@endphp

<!-- Full-Screen Glassmorphic Navigation Loading Window -->
<div id="globalNavigationLoader" aria-hidden="true" role="status">
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

        <!-- Bilingual Loading Typography -->
        <div class="space-y-0.5">
            <h4 class="text-sm font-black text-[#1C5A2C] font-kannada tracking-wide">
                ಪುಟ ಲೋಡ್ ಆಗುತ್ತಿದೆ...
            </h4>
            <p class="text-[11px] text-stone-500 font-semibold tracking-tight">
                Loading latest market data...
            </p>
        </div>

        <!-- Sleek Indeterminate Progress Bar in Forest Green -->
        <div class="w-full h-1.5 bg-[#E2DAC8] rounded-full overflow-hidden mt-3.5 relative kb-progress-indeterminate">
        </div>

    </div>
</div>

<script>
(function() {
    let loaderEl = null;
    let delayTimer = null;
    let safetyWatchdog = null;
    const ANTI_FLICKER_DELAY = 160; // ms threshold so instant/cached navigations don't flicker

    function getLoader() {
        if (!loaderEl) loaderEl = document.getElementById('globalNavigationLoader');
        return loaderEl;
    }

    function showLoader() {
        const el = getLoader();
        if (!el) return;

        // Cancel any pending timer
        clearTimeout(delayTimer);
        clearTimeout(safetyWatchdog);

        // Anti-flicker delay: only reveal if navigation takes > 160ms
        delayTimer = setTimeout(function() {
            el.classList.add('is-visible');
            el.setAttribute('aria-hidden', 'false');

            // Safety Watchdog: automatically dismiss after 7 seconds if navigation is aborted or cancelled
            safetyWatchdog = setTimeout(hideLoader, 7000);
        }, ANTI_FLICKER_DELAY);
    }

    function hideLoader() {
        clearTimeout(delayTimer);
        clearTimeout(safetyWatchdog);
        const el = getLoader();
        if (el) {
            el.classList.remove('is-visible');
            el.setAttribute('aria-hidden', 'true');
        }
    }

    // Expose global methods for custom AJAX or modal actions
    window.showPageLoader = showLoader;
    window.hidePageLoader = hideLoader;

    // 1. Intercept internal link taps
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href) return;

        // Ignore anchors, JS links, protocols, new tabs, downloads, or modifier keys
        if (href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('tel:') ||
            href.startsWith('mailto:') ||
            href.startsWith('whatsapp:') ||
            link.getAttribute('target') === '_blank' ||
            link.hasAttribute('download') ||
            e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
            return;
        }

        try {
            const targetUrl = new URL(link.href, window.location.origin);
            if (targetUrl.origin === window.location.origin) {
                // Ignore if link points to current exact page and query
                if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search) {
                    return;
                }
                showLoader();
            }
        } catch(err) {}
    }, { passive: true });

    // 2. Intercept standard form submissions (e.g. search, filters)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form) return;
        // Ignore if form is marked no-loader or targets a new tab
        if (form.hasAttribute('data-no-loader') || form.getAttribute('target') === '_blank') {
            return;
        }
        showLoader();
    }, { passive: true });

    // 3. Dismiss on back/forward cache restore, DOM complete, or popstate
    window.addEventListener('pageshow', hideLoader);
    window.addEventListener('popstate', hideLoader);
    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
    }
})();
</script>
