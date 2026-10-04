/**
 * Krushi Baandhava — PWA Push Notifications Client Engine
 * Handles service worker push registration, VAPID key conversion, and opt-in prompts.
 */
(function() {
    'use strict';

    function detectDeviceType() {
        const ua = navigator.userAgent || '';
        if (/android/i.test(ua)) return 'android';
        if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) return 'ios';
        return 'desktop';
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function getApiUrl(key) {
        if (window.KRUSHI_PWA_CONFIG && window.KRUSHI_PWA_CONFIG[key]) {
            return window.KRUSHI_PWA_CONFIG[key];
        }

        // Automatic base path resolution (supports localhost /Krushi-Baandhava-application/public/ or production)
        let basePath = '';
        const pathname = window.location.pathname;
        const publicMatch = pathname.match(/^(.*?\/public)/i);
        if (publicMatch) {
            basePath = publicMatch[1];
        }

        const map = {
            vapidKeyUrl: `${basePath}/api/v1/pwa/vapid-key`,
            subscribeUrl: `${basePath}/api/v1/pwa/subscribe`,
            unsubscribeUrl: `${basePath}/api/v1/pwa/unsubscribe`
        };

        return map[key] || `${basePath}/api/v1/pwa/${key}`;
    }

    const KrushiPwaPush = {
        isSupported: function() {
            return ('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window);
        },

        getPermissionState: function() {
            if (!this.isSupported()) return 'unsupported';
            return Notification.permission; // 'default', 'granted', 'denied'
        },

        subscribe: async function() {
            if (!this.isSupported()) {
                console.warn('[PWA Push] Push notifications not supported on this browser.');
                this.hidePrompt();
                return { success: false, reason: 'unsupported' };
            }

            const isKn = (document.documentElement.lang || 'kn') === 'kn';
            const allowBtn = document.getElementById('krushi-push-allow-btn');
            const laterBtn = document.getElementById('krushi-push-later-btn');

            if (allowBtn) {
                allowBtn.disabled = true;
                allowBtn.innerText = isKn ? 'ಸಕ್ರಿಯಗೊಳಿಸಲಾಗುತ್ತಿದೆ...' : 'Enabling...';
            }
            if (laterBtn) {
                laterBtn.disabled = true;
            }

            try {
                // 1. Request user permission from browser
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    console.log('[PWA Push] Notification permission was not granted:', permission);
                    this.dismissPrompt(7);
                    return { success: false, reason: 'denied' };
                }

                // Permission granted: immediately update UI and dismiss the banner so user is never blocked
                if (allowBtn) {
                    allowBtn.innerText = isKn ? '✓ ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ' : '✓ Enabled';
                    allowBtn.classList.remove('bg-emerald-500');
                    allowBtn.classList.add('bg-emerald-400');
                }
                localStorage.setItem('krushi_pwa_push_enabled', 'true');
                localStorage.setItem('krushi_push_prompt_dismissed_until', (Date.now() + (365 * 24 * 60 * 60 * 1000)).toString());
                
                // Animate and hide the prompt smoothly
                setTimeout(() => this.hidePrompt(), 400);

                // 2. Fetch VAPID public key with dynamic path
                const vapidUrl = getApiUrl('vapidKeyUrl');
                const keyRes = await fetch(vapidUrl);
                const keyData = await keyRes.json();
                if (!keyData.success || !keyData.publicKey) {
                    throw new Error('VAPID public key unavailable from server');
                }

                // 3. Register push manager
                const registration = await navigator.serviceWorker.ready;
                const convertedVapidKey = urlBase64ToUint8Array(keyData.publicKey);

                let subscription = await registration.pushManager.getSubscription();
                if (!subscription) {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: convertedVapidKey
                    });
                }

                // 4. Send subscription to Laravel backend with dynamic path
                const subJson = subscription.toJSON();
                const saveUrl = getApiUrl('subscribeUrl');
                const saveRes = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        endpoint: subJson.endpoint,
                        keys: {
                            p256dh: subJson.keys?.p256dh,
                            auth: subJson.keys?.auth
                        },
                        content_encoding: 'aes128gcm',
                        device_type: detectDeviceType(),
                        language: document.documentElement.lang || 'kn'
                    })
                });

                const saveJson = await saveRes.json();
                if (saveJson.success) {
                    console.log('[PWA Push] Successfully subscribed to Push Notifications.');
                    return { success: true };
                } else {
                    throw new Error(saveJson.message || 'Server rejected subscription');
                }
            } catch (err) {
                console.error('[PWA Push] Subscription error:', err);
                // Ensure banner is closed regardless of background network errors
                this.hidePrompt();
                return { success: false, reason: err.message };
            }
        },

        syncSubscriptionSilently: async function() {
            if (!this.isSupported() || Notification.permission !== 'granted') return;
            try {
                const vapidUrl = getApiUrl('vapidKeyUrl');
                const keyRes = await fetch(vapidUrl);
                const keyData = await keyRes.json();
                if (!keyData.success || !keyData.publicKey) return;

                const registration = await navigator.serviceWorker.ready;
                const convertedVapidKey = urlBase64ToUint8Array(keyData.publicKey);

                let subscription = await registration.pushManager.getSubscription();
                if (!subscription) {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: convertedVapidKey
                    });
                }

                const subJson = subscription.toJSON();
                const saveUrl = getApiUrl('subscribeUrl');
                await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        endpoint: subJson.endpoint,
                        keys: {
                            p256dh: subJson.keys?.p256dh,
                            auth: subJson.keys?.auth
                        },
                        content_encoding: 'aes128gcm',
                        device_type: detectDeviceType(),
                        language: document.documentElement.lang || 'kn'
                    })
                });
                localStorage.setItem('krushi_pwa_push_enabled', 'true');
            } catch (e) {
                console.warn('[PWA Push] Silent sync notice:', e.message);
            }
        },

        unsubscribe: async function() {
            if (!this.isSupported()) return;
            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription();
                if (subscription) {
                    await subscription.unsubscribe();
                    const unsubUrl = getApiUrl('unsubscribeUrl');
                    await fetch(unsubUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ endpoint: subscription.endpoint })
                    });
                }
                localStorage.removeItem('krushi_pwa_push_enabled');
                console.log('[PWA Push] Unsubscribed.');
            } catch (err) {
                console.error('[PWA Push] Unsubscribe error:', err);
            }
        },

        hidePrompt: function() {
            const banner = document.getElementById('krushi-push-optin-banner');
            if (banner) {
                banner.classList.add('translate-y-full', 'opacity-0');
                setTimeout(() => {
                    if (banner && banner.parentNode) {
                        banner.remove();
                    }
                }, 350);
            }
        },

        dismissPrompt: function(days = 7) {
            this.hidePrompt();
            const expiry = Date.now() + (days * 24 * 60 * 60 * 1000);
            localStorage.setItem('krushi_push_prompt_dismissed_until', expiry.toString());
        },

        showOptInBanner: function() {
            if (!this.isSupported()) return;

            // If permission is already granted, silently sync and NEVER show the prompt
            if (Notification.permission === 'granted') {
                this.syncSubscriptionSilently();
                return;
            }

            // If permission is already denied by user in browser, do not bother them
            if (Notification.permission === 'denied') {
                return;
            }

            // Check dismissal expiry
            const dismissedUntil = localStorage.getItem('krushi_push_prompt_dismissed_until');
            if (dismissedUntil && Date.now() < parseInt(dismissedUntil, 10)) {
                return;
            }

            if (document.getElementById('krushi-push-optin-banner')) return;

            const isKn = (document.documentElement.lang || 'kn') === 'kn';

            const bannerHtml = `
            <div id="krushi-push-optin-banner" 
                 class="fixed bottom-20 md:bottom-6 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 transform transition-all duration-300 ease-out translate-y-0 opacity-100">
                <div class="bg-gradient-to-r from-emerald-950/95 to-slate-900/95 backdrop-blur-md border border-emerald-500/40 p-4 rounded-2xl shadow-2xl shadow-emerald-950/60 text-white flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center shrink-0 text-xl shadow-inner">
                        🔔
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-emerald-300 tracking-tight">
                            ${isKn ? 'ದೈನಂದಿನ ಮಂಡಿ ದರಗಳು & ಮುನ್ಸೂಚನೆ' : 'Daily APMC Rates & Weather Alerts'}
                        </h4>
                        <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5 leading-snug">
                            ${isKn ? 'ಸಂಜೆ ಮಂಡಿ ದರಗಳು ಮತ್ತು ಹವಾಮಾನ ಎಚ್ಚರಿಕೆಗಳನ್ನು ನಿಮ್ಮ ಮೊಬೈಲ್‌ನಲ್ಲಿ ತಕ್ಷಣ ಪಡೆಯಿರಿ.' : 'Receive daily closing mandi rates & weather alerts right on your phone.'}
                        </p>
                        <div class="flex items-center gap-2 mt-3">
                            <button type="button" id="krushi-push-allow-btn" 
                                    class="px-3.5 py-1.5 bg-emerald-500 hover:bg-emerald-400 active:scale-95 text-slate-950 font-black text-xs rounded-xl shadow-md transition cursor-pointer">
                                ${isKn ? 'ಅನುಮತಿಸಿ (Allow)' : 'Allow Alerts'}
                            </button>
                            <button type="button" id="krushi-push-later-btn" 
                                    class="px-3 py-1.5 bg-slate-800/80 hover:bg-slate-700 active:scale-95 text-slate-300 text-xs rounded-xl transition cursor-pointer">
                                ${isKn ? 'ನಂತರ (Later)' : 'Later'}
                            </button>
                        </div>
                    </div>
                    <button type="button" id="krushi-push-close-btn" class="text-slate-400 hover:text-white p-1 text-sm cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>`;

            document.body.insertAdjacentHTML('beforeend', bannerHtml);

            document.getElementById('krushi-push-allow-btn')?.addEventListener('click', (e) => {
                e.preventDefault();
                KrushiPwaPush.subscribe();
            });
            document.getElementById('krushi-push-later-btn')?.addEventListener('click', (e) => {
                e.preventDefault();
                KrushiPwaPush.dismissPrompt(5);
            });
            document.getElementById('krushi-push-close-btn')?.addEventListener('click', (e) => {
                e.preventDefault();
                KrushiPwaPush.dismissPrompt(5);
            });
        }
    };

    window.KrushiPwaPush = KrushiPwaPush;

    // Automatically check after page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => KrushiPwaPush.showOptInBanner(), 2000);
        });
    } else {
        setTimeout(() => KrushiPwaPush.showOptInBanner(), 2000);
    }
})();
