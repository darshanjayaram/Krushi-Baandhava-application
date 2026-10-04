{{-- ============================================================
     Krushi Baandhava — Onboarding "How to Use" Tour Component
     ------------------------------------------------------------
     • First-visit auto start: only on pages that contain the hero
       anchor (#tourHeroLocationCard → home page) and only when the
       `krushi_tour_done` cookie is absent (365-day cookie).
     • ?tour=1 in the URL forces the tour even if the cookie exists.
     • Manual start: window.startKrushiTour() (💡 "How to Use" pill).
     • Bilingual: reads <html lang>. Kannada mode shows KN + EN subtext,
       English mode shows EN only.
     • Audio: tries pre-recorded MP3 first
         public/audio/tour/{kn|en}/step{N}.mp3
       and falls back to the Web Speech API (speechSynthesis). If the
       device has no Kannada voice, the English line is spoken instead.
     • Spotlight: the "hole" element carries a huge box-shadow which
       dims everything except the target (no external libraries).
     • Hidden targets (display:none, e.g. desktop-only pill on mobile,
       or mobile bottom nav on desktop) are skipped automatically.
     • z-index: click-catcher 3999 | hole 4000 | bubble 4001
     ============================================================ --}}

<style>
.kb-tour-catcher {
    position: fixed; inset: 0; z-index: 3999;
    background: transparent;
}
.kb-tour-catcher.kb-tour-hidden { display: none; }

.kb-tour-hole {
    position: fixed; z-index: 4000;
    border-radius: 14px;
    pointer-events: none;
    box-shadow:
        0 0 0 9999px rgba(10, 22, 14, 0.70),
        0 0 0 3px rgba(234, 179, 8, 0.85) inset,
        0 0 22px 6px rgba(234, 179, 8, 0.35);
    transition: top .35s cubic-bezier(.4,0,.2,1), left .35s cubic-bezier(.4,0,.2,1),
                width .35s cubic-bezier(.4,0,.2,1), height .35s cubic-bezier(.4,0,.2,1),
                opacity .25s ease;
}
.kb-tour-hole.kb-tour-hidden { opacity: 0; }
/* Used when a step has no visible target: full-screen dim, no cutout */
.kb-tour-hole.kb-tour-nohole { top: 50% !important; left: 50% !important; width: 0 !important; height: 0 !important; box-shadow: 0 0 0 9999px rgba(10, 22, 14, 0.70); }

.kb-tour-bubble {
    position: fixed; z-index: 4001;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,.22), 0 2px 8px rgba(0,0,0,.12), 0 0 0 1.5px rgba(28,90,44,.12);
    padding: 16px 16px 13px;
    width: min(340px, calc(100vw - 28px));
    transition: top .35s cubic-bezier(.4,0,.2,1), left .35s cubic-bezier(.4,0,.2,1), opacity .22s ease, transform .22s ease;
}
.kb-tour-bubble.kb-tour-hidden { opacity: 0; transform: scale(.95) translateY(8px); pointer-events: none; }

.kb-tour-counter {
    display: inline-flex; align-items: center;
    background: #EAF4EC; color: #1C5A2C; border: 1px solid #B8DEC0;
    border-radius: 999px; font-size: 10px; font-weight: 800; padding: 2px 9px;
}
.kb-tour-dots { display: flex; gap: 5px; align-items: center; }
.kb-tour-dot { width: 6px; height: 6px; border-radius: 50%; background: #D9CEB8; transition: all .2s; }
.kb-tour-dot.active { background: #1C5A2C; width: 16px; border-radius: 5px; }

.kb-tour-speaker {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 50%;
    background: #EAF4EC; border: 1.5px solid #B8DEC0; color: #1C5A2C;
    cursor: pointer; font-size: 14px; transition: background .18s, transform .12s;
}
.kb-tour-speaker:hover { background: #D3EDDB; transform: scale(1.06); }
.kb-tour-speaker.speaking { background: #1C5A2C; color: #fff; animation: kbTourPulse 1.2s infinite; }
@keyframes kbTourPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(28,90,44,.45); } 50% { box-shadow: 0 0 0 6px rgba(28,90,44,0); } }

.kb-tour-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    border-radius: 10px; font-size: 12px; font-weight: 800; padding: 9px 14px;
    cursor: pointer; border: none; line-height: 1; transition: background .15s, transform .1s;
}
.kb-tour-btn:active { transform: scale(.95); }
.kb-tour-btn-skip { background: transparent; color: #78716c; border: 1.5px solid #D6D0C6; }
.kb-tour-btn-skip:hover { background: #F5EFE6; }
.kb-tour-btn-back { background: #F5EFE6; color: #44403c; border: 1.5px solid #D9CEB8; }
.kb-tour-btn-back:hover { background: #EDE5D4; }
.kb-tour-btn-next { background: #1C5A2C; color: #fff; flex: 1; }
.kb-tour-btn-next:hover { background: #164722; }

.kb-tour-bubble .kn { font-family: 'Noto Sans Kannada', sans-serif; }
</style>

<div id="kbTourCatcher" class="kb-tour-catcher kb-tour-hidden" aria-hidden="true"></div>
<div id="kbTourHole" class="kb-tour-hole kb-tour-hidden" aria-hidden="true"></div>

<div id="kbTourBubble" class="kb-tour-bubble kb-tour-hidden" role="dialog" aria-modal="true" aria-labelledby="kbTourTextMain">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="kb-tour-counter" id="kbTourCounter">1 / 4</span>
            <div class="kb-tour-dots" id="kbTourDots"></div>
        </div>
        <button type="button" class="kb-tour-speaker" id="kbTourSpeaker" aria-label="Listen / ಕೇಳಿ" title="Listen / ಕೇಳಿ">🔊</button>
    </div>

    <p id="kbTourTextMain" style="font-size:14px;font-weight:700;color:#1a1a1a;line-height:1.55;margin:0 0 4px;"></p>
    <p id="kbTourTextSub" style="font-size:11px;font-weight:500;color:#57534e;line-height:1.5;margin:0 0 14px;"></p>

    <div style="display:flex;align-items:center;gap:7px;">
        <button type="button" class="kb-tour-btn kb-tour-btn-skip" id="kbTourSkip">Skip</button>
        <button type="button" class="kb-tour-btn kb-tour-btn-back" id="kbTourBack" style="display:none;">‹ Back</button>
        <button type="button" class="kb-tour-btn kb-tour-btn-next" id="kbTourNext">Next ›</button>
    </div>
</div>

<script>
(function () {
    'use strict';

    var COOKIE = 'krushi_tour_done';
    var AUDIO_BASE = @json(rtrim(asset('audio/tour'), '/'));

    // [selectors (first VISIBLE match wins), Kannada text, English text]
    var STEPS = [
        ['#tourHeroLocationCard, #tourHeaderLocationPill',
         '📍 ಮೊದಲು ಇಲ್ಲಿ ಒತ್ತಿ ನಿಮ್ಮ ಊರು ಅಥವಾ ಜಿಲ್ಲೆ ಆಯ್ಕೆ ಮಾಡಿ — ಹತ್ತಿರದ ಮಾರುಕಟ್ಟೆ ದರ ಸಿಗುತ್ತದೆ.',
         '📍 First, tap here to set your village or district — you will see prices from mandis near you.'],
        ['#tourLangToggle',
         '🌐 ಕನ್ನಡ ಅಥವಾ English — ಇಲ್ಲಿ ಸುಲಭವಾಗಿ ಭಾಷೆ ಬದಲಿಸಿ.',
         '🌐 Switch between Kannada and English anytime using this toggle.'],
        ['#tourPricesSection',
         '📊 ಇಂದಿನ ಅಧಿಕೃತ ಮಂಡಿ ದರಗಳು ಇಲ್ಲಿವೆ. ಬೆಳೆ ಅಥವಾ ಮಾರುಕಟ್ಟೆ ಹೆಸರು ಟೈಪ್ ಮಾಡಿ ಹುಡುಕಬಹುದು.',
         '📊 Today\'s official mandi rates are here. Search by crop or market name.'],
        ['#tourCropCard',
         '🌾 ಯಾವುದೇ ಬೆಳೆ ಒತ್ತಿ — ದರ ಏರುತ್ತದೋ ಇಳಿಯುತ್ತದೋ, ಎಲ್ಲಿ ಮತ್ತು ಯಾವಾಗ ಮಾರಬೇಕು ಎಂದು ತಿಳಿಯಿರಿ.',
         '🌾 Tap any crop to see price trends, and where and when to sell for the best returns.'],
        ['#mobileBottomNav',
         '📱 ದರಗಳು, ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು, ಹವಾಮಾನ ಮತ್ತು ವಿಡಿಯೋಗಳು — ಕೆಳಗಿನ ಮೆನುವಿನಲ್ಲಿ ಸಿಗುತ್ತವೆ.',
         '📱 Mandi rates, government schemes, weather and videos — all in this bottom menu.']
    ];

    var $ = function (id) { return document.getElementById(id); };
    var catcher = $('kbTourCatcher'), hole = $('kbTourHole'), bubble = $('kbTourBubble');
    var counter = $('kbTourCounter'), dots = $('kbTourDots');
    var textMain = $('kbTourTextMain'), textSub = $('kbTourTextSub');
    var speaker = $('kbTourSpeaker'), btnSkip = $('kbTourSkip'), btnBack = $('kbTourBack'), btnNext = $('kbTourNext');

    var active = false, idx = 0, plan = [], currentEl = null, rafId = null, audioEl = null, speaking = false, userInteracted = false;

    /* ---------- helpers ---------- */
    function isKn() { return (document.documentElement.lang || '').toLowerCase().indexOf('kn') === 0; }
    function setCookie(v) {
        var d = new Date(); d.setTime(d.getTime() + 365 * 864e5);
        document.cookie = COOKIE + '=' + v + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
    }
    function getCookie() { var m = document.cookie.match(new RegExp('(?:^|; )' + COOKIE + '=([^;]*)')); return m ? m[1] : null; }

    function isVisible(el) {
        if (!el || !el.getClientRects().length) return false;
        var r = el.getBoundingClientRect();
        if (r.width < 2 || r.height < 2) return false;
        var cs = getComputedStyle(el);
        return cs.visibility !== 'hidden' && cs.display !== 'none';
    }
    function findTarget(stepIdx) {
        var sels = STEPS[stepIdx][0].split(',');
        for (var i = 0; i < sels.length; i++) {
            var el = document.querySelector(sels[i].trim());
            if (isVisible(el)) return el;
        }
        return null;
    }
    function buildPlan() {
        plan = [];
        for (var i = 0; i < STEPS.length; i++) if (findTarget(i)) plan.push(i);
    }

    /* ---------- spotlight + bubble positioning (viewport coords: everything is position:fixed) ---------- */
    function place(r) {
        var PAD = 8;
        hole.classList.remove('kb-tour-nohole');
        hole.style.top = (r.top - PAD) + 'px';
        hole.style.left = (r.left - PAD) + 'px';
        hole.style.width = (r.width + PAD * 2) + 'px';
        hole.style.height = (r.height + PAD * 2) + 'px';
        hole.classList.remove('kb-tour-hidden');

        var vw = window.innerWidth, vh = window.innerHeight;
        var bw = bubble.offsetWidth || 320, bh = bubble.offsetHeight || 170, gap = 14;
        var below = r.bottom + PAD + gap, above = r.top - PAD - gap - bh;
        var top;
        if (below + bh <= vh - 8) top = below;
        else if (above >= 8) top = above;
        else top = Math.max(8, vh - bh - 12);   // tall target: dock bubble at bottom
        var left = r.left + r.width / 2 - bw / 2;
        left = Math.max(14, Math.min(left, vw - bw - 14));
        bubble.style.transform = '';
        bubble.style.top = top + 'px';
        bubble.style.left = left + 'px';
        bubble.classList.remove('kb-tour-hidden');
    }
    function placeCentered() {
        hole.classList.add('kb-tour-nohole');
        hole.classList.remove('kb-tour-hidden');
        var bw = bubble.offsetWidth || 320, bh = bubble.offsetHeight || 170;
        bubble.style.top = Math.max(8, (window.innerHeight - bh) / 2) + 'px';
        bubble.style.left = Math.max(14, (window.innerWidth - bw) / 2) + 'px';
        bubble.classList.remove('kb-tour-hidden');
    }

    // Wait until the target stops moving (smooth scroll / layout shifts), then place.
    function settleAndPlace(el) {
        if (rafId) cancelAnimationFrame(rafId);
        var ticks = 0, stable = 0, last = null;
        (function tick() {
            ticks++;
            var r = el.getBoundingClientRect();
            if (last && Math.abs(r.top - last.top) < 0.5 && Math.abs(r.left - last.left) < 0.5 &&
                Math.abs(r.width - last.width) < 0.5 && Math.abs(r.height - last.height) < 0.5) stable++;
            else stable = 0;
            last = r;
            if (stable >= 3 || ticks >= 90) { rafId = null; place(r); }
            else rafId = requestAnimationFrame(tick);
        })();
    }

    function reposition() {
        if (!active) return;
        if (currentEl && isVisible(currentEl)) place(currentEl.getBoundingClientRect());
    }

    /* ---------- audio: MP3 first, speechSynthesis fallback ---------- */
    function stopAudio() {
        if (audioEl) { try { audioEl.pause(); } catch (e) {} audioEl.onended = audioEl.onerror = null; audioEl = null; }
        if (window.speechSynthesis) window.speechSynthesis.cancel();
        speaking = false; speaker.classList.remove('speaking');
    }
    function setSpeaking(on) { speaking = on; speaker.classList.toggle('speaking', on); }
    function stripEmoji(s) { return s.replace(/[\u2190-\u21FF\u2600-\u27BF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|\uD83E[\uDC00-\uDFFF]|\uFE0F/g, '').trim(); }

    function speakTTS(stepIdx) {
        if (!window.speechSynthesis) return;
        var kn = isKn();
        var voices = window.speechSynthesis.getVoices() || [];
        var voice = kn
            ? voices.filter(function (v) { return /^kn/i.test(v.lang); })[0]
            : (voices.filter(function (v) { return /^en[-_]IN/i.test(v.lang); })[0] ||
               voices.filter(function (v) { return /^en/i.test(v.lang); })[0]);
        // Kannada mode always speaks the Kannada line (never switches to English).
        var u = new SpeechSynthesisUtterance(stripEmoji(STEPS[stepIdx][kn ? 1 : 2]));
        u.lang = kn ? 'kn-IN' : 'en-IN';
        if (voice) u.voice = voice;
        u.rate = 0.92;
        u.onstart = function () { setSpeaking(true); };
        u.onend = u.onerror = function () { setSpeaking(false); };
        window.speechSynthesis.speak(u);
    }

    function play(stepIdx) {
        stopAudio();
        var lang = isKn() ? 'kn' : 'en';
        var a = new Audio(AUDIO_BASE + '/' + lang + '/step' + (stepIdx + 1) + '.mp3');
        audioEl = a;
        var fellBack = false;
        var fallback = function () {
            if (fellBack || audioEl !== a) return;
            fellBack = true; audioEl = null; setSpeaking(false);
            speakTTS(stepIdx);
        };
        a.onended = function () { setSpeaking(false); };
        a.onerror = fallback;
        var p = a.play();
        setSpeaking(true);
        if (p && p.catch) p.catch(function (err) {
            // NotAllowedError = autoplay blocked (iOS before a tap) → stay silent until user taps 🔊
            if (err && err.name === 'NotAllowedError') { if (audioEl === a) { audioEl = null; setSpeaking(false); } }
            else fallback();
        });
    }

    /* ---------- render ---------- */
    function render() {
        var kn = isKn(), stepIdx = plan[idx], total = plan.length, last = idx === total - 1;
        counter.textContent = (idx + 1) + ' / ' + total;
        dots.innerHTML = '';
        for (var i = 0; i < total; i++) {
            var d = document.createElement('span');
            d.className = 'kb-tour-dot' + (i === idx ? ' active' : '');
            dots.appendChild(d);
        }
        if (kn) {
            textMain.className = 'kn'; textMain.textContent = STEPS[stepIdx][1];
            textSub.textContent = stripEmoji(STEPS[stepIdx][2]); textSub.style.display = '';
        } else {
            textMain.className = ''; textMain.textContent = STEPS[stepIdx][2];
            textSub.textContent = ''; textSub.style.display = 'none';
        }
        btnSkip.textContent = kn ? 'ಬಿಡಿ' : 'Skip';
        btnBack.textContent = kn ? '‹ ಹಿಂದೆ' : '‹ Back';
        btnNext.textContent = last ? (kn ? '✓ ಮುಗಿಸಿ' : '✓ Done') : (kn ? 'ಮುಂದೆ ›' : 'Next ›');
        btnBack.style.display = idx > 0 ? '' : 'none';
        btnSkip.style.display = last ? 'none' : '';

        currentEl = findTarget(stepIdx);
        if (currentEl) {
            var r = currentEl.getBoundingClientRect();
            var fixedLike = /fixed|sticky/.test(getComputedStyle(currentEl).position) || currentEl.closest('header, #mobileBottomNav');
            if (!fixedLike && (r.top < 70 || r.bottom > window.innerHeight - 80)) {
                currentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            settleAndPlace(currentEl);
        } else {
            placeCentered();
        }
        if (userInteracted) play(stepIdx);
        btnNext.focus({ preventScroll: true });
    }

    /* ---------- navigation ---------- */
    function next() { userInteracted = true; if (idx >= plan.length - 1) finish(); else { idx++; render(); } }
    function prev() { userInteracted = true; if (idx > 0) { idx--; render(); } }
    function finish() {
        stopAudio();
        if (rafId) cancelAnimationFrame(rafId);
        active = false; currentEl = null;
        catcher.classList.add('kb-tour-hidden');
        hole.classList.add('kb-tour-hidden');
        bubble.classList.add('kb-tour-hidden');
        setCookie('1');
        if (window.history && location.search.indexOf('tour=1') !== -1) {
            var url = location.href.replace(/([?&])tour=1(&|$)/, function (m, a, b) { return b ? a : ''; }).replace(/\?$/, '');
            history.replaceState(null, '', url);
        }
    }

    btnNext.addEventListener('click', next);
    btnBack.addEventListener('click', prev);
    btnSkip.addEventListener('click', finish);
    catcher.addEventListener('click', next);
    speaker.addEventListener('click', function () {
        userInteracted = true;
        if (speaking) stopAudio(); else play(plan[idx]);
    });
    document.addEventListener('keydown', function (e) {
        if (!active) return;
        if (e.key === 'Escape') finish();
        else if (e.key === 'ArrowRight') next();
        else if (e.key === 'ArrowLeft') prev();
    });
    window.addEventListener('resize', reposition);
    window.addEventListener('scroll', reposition, { passive: true });

    if (window.speechSynthesis && window.speechSynthesis.getVoices) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = function () { window.speechSynthesis.getVoices(); };
    }

    /* ---------- public API ---------- */
    window.startKrushiTour = function (opts) {
        buildPlan();
        if (!plan.length) return false;
        // Manual start (button click) counts as a user gesture → audio allowed immediately
        userInteracted = !(opts && opts.auto);
        idx = 0; active = true;
        catcher.classList.remove('kb-tour-hidden');
        render();
        return true;
    };
    window.resetKrushiTour = function () { document.cookie = COOKIE + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/'; };

    /* ---------- first-visit auto start ---------- */
    function shouldAuto() {
        if (!document.getElementById('tourHeroLocationCard')) return false;   // home page only
        if (/[?&]tour=1(&|$)/.test(location.search)) return true;
        return !getCookie();
    }
    function autoStart() {
        // Don't fight with other modals (e.g. the first-visit location modal) — wait for them to close
        var tries = 0;
        (function attempt() {
            var blocking = document.querySelector('[data-kb-modal-open="true"]');
            if (blocking && tries++ < 20) return setTimeout(attempt, 1000);
            window.startKrushiTour({ auto: true });
        })();
    }
    if (shouldAuto()) {
        if (document.readyState === 'complete') setTimeout(autoStart, 1400);
        else window.addEventListener('load', function () { setTimeout(autoStart, 1400); });
    }
})();
</script>
