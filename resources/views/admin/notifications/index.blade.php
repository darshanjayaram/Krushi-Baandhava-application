@extends('layouts.admin')

@section('content')
@php
    $appLogoPath = \App\Models\SystemSetting::get('app_logo', '/icons/icon-192.svg');
    $appLogoUrl = asset($appLogoPath);
@endphp

<!-- Define Alpine Component synchronously before HTML element renders -->
<script>
window.pwaAdminHub = function(initData) {
    return {
        totalSubscribers: initData.totalSubscribers || 0,
        settings: initData.settings || {},
        broadcastList: initData.broadcastsData || [],
        previewLang: 'kn',
        isBroadcasting: false,
        testingPush: false,
        savingSetting: false,
        toast: { show: false, message: '', type: 'success' },

        form: {
            type: 'custom_broadcast',
            title_kn: '🌾 ಇಂದಿನ ಮಂಡಿ ದರಗಳು ಅಪ್ಡೇಟ್ ಆಗಿವೆ',
            title_en: '🌾 Today\'s APMC Mandi Rates Updated',
            body_kn: 'ಕರ್ನಾಟಕದ ಪ್ರಮುಖ ಮಂಡಿಗಳಲ್ಲಿ ಅಡಿಕೆ, ತೆಂಗು ಮತ್ತು ಮೆಕ್ಕೆಜೋಳದ ಇಂದಿನ ಧಾರಣೆ ನೋಡಲು ಟ್ಯಾಪ್ ಮಾಡಿ.',
            body_en: 'Check today\'s closing auction rates and modal prices across Karnataka mandis.',
            target_url: '/crops',
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        formatNumber(num) {
            return new Intl.NumberFormat('en-IN').format(num || 0);
        },

        formatTimestamp(ts) {
            if (!ts) return '';
            try {
                const d = new Date(ts);
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' +
                       d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
            } catch(e) {
                return ts;
            }
        },

        getTypeLabel(type) {
            const map = {
                'daily_rates': '🌾 Mandi Rates',
                'weather': '🌤️ Weather',
                'weekly_forecast': '📈 Forecast',
                'scheme': '🏛️ Scheme',
                'custom_broadcast': '📢 Broadcast'
            };
            return map[type] || type;
        },

        loadTemplate(key) {
            const dateStr = new Date().toLocaleDateString('en-GB');
            if (key === 'rates') {
                this.form.type = 'daily_rates';
                this.form.title_kn = `🌾 ಇಂದಿನ ಮಂಡಿ ದರಗಳು ಅಪ್ಡೇಟ್ ಆಗಿವೆ (${dateStr})`;
                this.form.title_en = `🌾 Today's APMC Mandi Rates (${dateStr})`;
                this.form.body_kn = 'ಕರ್ನಾಟಕದ ಪ್ರಮುಖ APMC ಮಂಡಿಗಳಲ್ಲಿ ಅಡಿಕೆ, ತೆಂಗು, ಮೆಕ್ಕೆಜೋಳ ಇಂದಿನ ಹರಾಜು ಮುಕ್ತಾಯಗೊಂಡಿದೆ. ಇಂದಿನ ದರ ವೀಕ್ಷಿಸಿ.';
                this.form.body_en = 'Closing auction rates updated across Karnataka mandis. Tap to view today\'s modal prices.';
                this.form.target_url = '/crops';
            } else if (key === 'holiday') {
                this.form.type = 'custom_broadcast';
                this.form.title_kn = '⚠️ ನಾಳೆ ಕರ್ನಾಟಕದ ಎಲ್ಲಾ APMC ಮಂಡಿಗಳಿಗೆ ರಜೆ';
                this.form.title_en = '⚠️ Karnataka APMC Mandis Holiday Notice';
                this.form.body_kn = 'ಸಾರ್ವಜನಿಕ ರಜೆ ಪ್ರಯುಕ್ತ ನಾಳೆ ಎಲ್ಲಾ ಮಂಡಿಗಳಲ್ಲಿ ಕೃಷಿ ಉತ್ಪನ್ನಗಳ ವಹಿವಾಟು ಇರುವುದಿಲ್ಲ.';
                this.form.body_en = 'Mandi trading will remain closed tomorrow on account of a public holiday.';
                this.form.target_url = '/crops';
            } else if (key === 'rain') {
                this.form.type = 'weather';
                this.form.title_kn = '🌧️ ನಿಮ್ಮ ಭಾಗದಲ್ಲಿ ಭಾರೀ ಮಳೆ ಮುನ್ನೆಚ್ಚರಿಕೆ';
                this.form.title_en = '🌧️ Heavy Rainfall Advisory in Your Region';
                this.form.body_kn = 'ಮುಂದಿನ 24 ಗಂಟೆಗಳಲ್ಲಿ ಸಾಧಾರಣದಿಂದ ಭಾರೀ ಮಳೆಯಾಗುವ ಸಾಧ್ಯತೆ ಇದೆ. ಬೆಳೆ ರಕ್ಷಣೆ ಮತ್ತು ಕೃಷಿ ಸಲಹೆಗಳನ್ನು ವೀಕ್ಷಿಸಿ.';
                this.form.body_en = 'Moderate to heavy rainfall expected over the next 24 hours. Check crop protection advisories.';
                this.form.target_url = '/weather';
            } else if (key === 'scheme') {
                this.form.type = 'scheme';
                this.form.title_kn = '🏛️ ಹೊಸ ಕೃಷಿ ಸಬ್ಸಿಡಿ & ಸರ್ಕಾರಿ ಯೋಜನೆ ಪ್ರಕಟಣೆ';
                this.form.title_en = '🏛️ New Agriculture Subsidy & Govt Scheme';
                this.form.body_kn = 'ರೈತರಿಗೆ ಹೊಸ ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ ಮತ್ತು ಬಿತ್ತನೆ ಬೀಜ ಸಹಾಯಧನ ಅರ್ಜಿ ಆಹ್ವಾನಿಸಲಾಗಿದೆ. ವಿವರಗಳಿಗೆ ಟ್ಯಾಪ್ ಮಾಡಿ.';
                this.form.body_en = 'New farm equipment & seed subsidy applications opened for farmers. Tap to view eligibility.';
                this.form.target_url = '/schemes';
            } else if (key === 'forecast') {
                this.form.type = 'weekly_forecast';
                this.form.title_kn = '📈 ಈ ವಾರದ ಕೃಷಿ ಧಾರಣೆ ಮುನ್ನೋಟ (Forecast)';
                this.form.title_en = '📈 Weekly Agricultural Price Outlook';
                this.form.body_kn = 'ಮುಂದಿನ 7-15 ದಿನಗಳಲ್ಲಿ ಮಾರುಕಟ್ಟೆ ಏರಿಳಿತದ ಸಂಭವನೀಯತೆ ಮತ್ತು ಬೆಳೆ ಮಾರಾಟಕ್ಕೆ ಸೂಕ್ತ ಸಮಯದ ವಿಶ್ಲೇಷಣೆ.';
                this.form.body_en = 'Expected price trends and optimal selling window analysis for the next 7-15 days.';
                this.form.target_url = '/crops';
            }
            this.showToast('Template loaded successfully ✓', 'success');
        },

        async toggleSetting(key, val) {
            this.settings[key] = val;
            this.savingSetting = true;
            try {
                const res = await fetch("{{ route('admin.notifications.settings') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        setting_key: key,
                        setting_value: val
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(`Setting saved (${val ? 'ON' : 'OFF'}) ✓`);
                } else {
                    throw new Error('Failed to save');
                }
            } catch (err) {
                this.settings[key] = !val; // rollback
                this.showToast('Failed to save setting', 'error');
            } finally {
                this.savingSetting = false;
            }
        },

        async updateTimeSetting(key, val) {
            this.settings[key] = val;
            this.savingSetting = true;
            try {
                const res = await fetch("{{ route('admin.notifications.settings') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        setting_key: key,
                        setting_value: val
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(`Time schedule saved: ${val} ✓`);
                }
            } catch (err) {
                this.showToast('Failed to save time schedule', 'error');
            } finally {
                this.savingSetting = false;
            }
        },

        async submitBroadcast() {
            if (!confirm(`Are you sure you want to broadcast this notification to all ${this.formatNumber(this.totalSubscribers)} farmers?`)) {
                return;
            }

            this.isBroadcasting = true;
            try {
                const res = await fetch("{{ route('admin.notifications.broadcast') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    if (data.broadcast) {
                        this.broadcastList.unshift(data.broadcast);
                    }
                } else {
                    throw new Error(data.message || 'Broadcast failed');
                }
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.isBroadcasting = false;
            }
        },

        async testPushOnMyDevice() {
            if (!('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window)) {
                alert('Web Push notifications are not supported on this browser.');
                return;
            }

            this.testingPush = true;
            try {
                const perm = await Notification.requestPermission();
                if (perm !== 'granted') {
                    throw new Error('Notification permission was denied in this browser.');
                }

                // Subscribe if not yet subscribed
                if (window.KrushiPwaPush) {
                    await window.KrushiPwaPush.subscribe();
                }

                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (!sub) {
                    throw new Error('Could not retrieve push subscription for this device.');
                }

                const subJson = sub.toJSON();

                const res = await fetch("{{ route('admin.notifications.test') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        endpoint: subJson.endpoint,
                        keys: subJson.keys,
                        title: this.previewLang === 'kn' ? this.form.title_kn : (this.form.title_en || this.form.title_kn),
                        body: this.previewLang === 'kn' ? this.form.body_kn : (this.form.body_en || this.form.body_kn),
                        url: this.form.target_url
                    })
                });

                const data = await res.json();
                if (data.success) {
                    this.showToast('Test notification delivered to your screen! ✓', 'success');
                } else {
                    throw new Error(data.message);
                }
            } catch (err) {
                this.showToast(err.message, 'error');
            } finally {
                this.testingPush = false;
            }
        },

        async deleteBroadcast(id) {
            if (!confirm('Are you sure you want to delete this broadcast record?')) return;

            try {
                const res = await fetch(`/admin/notifications/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.broadcastList = this.broadcastList.filter(item => item.id !== id);
                    this.showToast('Record deleted successfully ✓');
                }
            } catch (err) {
                this.showToast('Failed to delete record', 'error');
            }
        }
    };
};

if (window.Alpine) {
    window.Alpine.data('pwaAdminHub', window.pwaAdminHub);
}
</script>

<div class="space-y-6" x-data="pwaAdminHub({
    totalSubscribers: {{ $totalSubscribers }},
    settings: @js($settings),
    broadcastsData: @js($broadcasts->items())
})">

    <!-- Global Floating Async Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4 sm:translate-y-0 sm:translate-x-4"
         x-transition:enter-end="opacity-100 transform translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-20 right-4 sm:right-6 z-50 max-w-md w-full pointer-events-none" style="display: none;">
        <div class="pointer-events-auto p-4 rounded-2xl shadow-2xl flex items-center gap-3 border"
             :class="toast.type === 'success' ? 'bg-emerald-950/95 border-emerald-500/40 text-emerald-200 shadow-emerald-950/50' : 'bg-rose-950/95 border-rose-500/40 text-rose-200 shadow-rose-950/50'">
            <span class="text-xl" x-text="toast.type === 'success' ? '✅' : '⚠️'"></span>
            <div class="flex-1 text-xs font-semibold" x-text="toast.message"></div>
            <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white p-1 text-sm">✕</button>
        </div>
    </div>

    <!-- Page Header & Telemetry with Real Application Logo -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-white/10 border border-emerald-400/30 p-1.5 flex items-center justify-center shadow-lg shrink-0 overflow-hidden">
                <img src="{{ $appLogoUrl }}" alt="Krushi Baandhava Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                    <span>PWA Push Notifications Hub</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">Farmer Communications & Alerts</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
                    Broadcast direct APMC mandi rates, weather advisories, weekly forecasts, and emergency announcements to subscribed farmers in Karnataka.
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <span class="px-3.5 py-1.5 rounded-xl bg-emerald-950/70 border border-emerald-500/30 text-xs font-semibold text-emerald-300 flex items-center gap-2 shadow-inner">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Async VAPID WebPush Active</span>
            </span>
        </div>
    </div>

    <!-- Subscriber Analytics Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Subscribers -->
        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm hover:border-slate-700 transition flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Subscribers</p>
                <h3 class="text-2xl font-black text-white mt-1 font-mono" x-text="formatNumber(totalSubscribers)">{{ number_format($totalSubscribers) }}</h3>
                <span class="text-[11px] text-emerald-400 font-medium">Active Devices</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-2xl text-emerald-400">
                👥
            </div>
        </div>

        <!-- Card 2: Android Users -->
        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm hover:border-slate-700 transition flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Android Devices</p>
                <h3 class="text-2xl font-black text-emerald-300 mt-1 font-mono">{{ number_format($androidCount) }}</h3>
                <span class="text-[11px] text-slate-400 font-medium">
                    {{ $totalSubscribers > 0 ? round(($androidCount / $totalSubscribers) * 100) : 0 }}% of total
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-2xl">
                🤖
            </div>
        </div>

        <!-- Card 3: iOS / Safari Users -->
        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm hover:border-slate-700 transition flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Apple iOS / Safari</p>
                <h3 class="text-2xl font-black text-cyan-300 mt-1 font-mono">{{ number_format($iosCount) }}</h3>
                <span class="text-[11px] text-slate-400 font-medium">
                    {{ $totalSubscribers > 0 ? round(($iosCount / $totalSubscribers) * 100) : 0 }}% of total
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-2xl">
                🍎
            </div>
        </div>

        <!-- Card 4: Desktop / PC Users -->
        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-sm hover:border-slate-700 transition flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Desktop / PC</p>
                <h3 class="text-2xl font-black text-purple-300 mt-1 font-mono">{{ number_format($desktopCount) }}</h3>
                <span class="text-[11px] text-slate-400 font-medium">
                    {{ $totalSubscribers > 0 ? round(($desktopCount / $totalSubscribers) * 100) : 0 }}% of total
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-2xl">
                💻
            </div>
        </div>
    </div>

    <!-- Main Content Area: 2 Columns Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Instant Broadcast Composer & Live Mobile Mockup (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="p-6 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-lg">
                            📢
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white tracking-tight">Instant Broadcast Composer</h3>
                            <p class="text-xs text-slate-400">Send instant push notifications directly to subscribed farmer devices (Zero page reload).</p>
                        </div>
                    </div>

                    <!-- Language Preview Switcher -->
                    <div class="flex items-center gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
                        <button type="button" @click="previewLang = 'kn'" 
                                :class="previewLang === 'kn' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                class="px-2.5 py-1 text-xs rounded-lg transition cursor-pointer">
                            Kannada Preview
                        </button>
                        <button type="button" @click="previewLang = 'en'" 
                                :class="previewLang === 'en' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                class="px-2.5 py-1 text-xs rounded-lg transition cursor-pointer">
                            English Preview
                        </button>
                    </div>
                </div>

                <!-- Quick Preset Templates Bar -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span>💡</span>
                        <span>Quick Templates (Click to Auto-Fill):</span>
                    </label>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" @click="loadTemplate('rates')" 
                                class="px-2.5 py-1 rounded-lg bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition active:scale-95 cursor-pointer">
                            🌾 Mandi Rates
                        </button>
                        <button type="button" @click="loadTemplate('holiday')" 
                                class="px-2.5 py-1 rounded-lg bg-amber-950/80 hover:bg-amber-900 text-amber-300 border border-amber-500/30 text-xs font-semibold transition active:scale-95 cursor-pointer">
                            ⚠️ Mandi Holiday Notice
                        </button>
                        <button type="button" @click="loadTemplate('rain')" 
                                class="px-2.5 py-1 rounded-lg bg-cyan-950/80 hover:bg-cyan-900 text-cyan-300 border border-cyan-500/30 text-xs font-semibold transition active:scale-95 cursor-pointer">
                            🌧️ Rain Advisory
                        </button>
                        <button type="button" @click="loadTemplate('scheme')" 
                                class="px-2.5 py-1 rounded-lg bg-purple-950/80 hover:bg-purple-900 text-purple-300 border border-purple-500/30 text-xs font-semibold transition active:scale-95 cursor-pointer">
                            🏛️ New Scheme / Subsidy
                        </button>
                        <button type="button" @click="loadTemplate('forecast')" 
                                class="px-2.5 py-1 rounded-lg bg-blue-950/80 hover:bg-blue-900 text-blue-300 border border-blue-500/30 text-xs font-semibold transition active:scale-95 cursor-pointer">
                            📈 Weekly Price Outlook
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitBroadcast" class="space-y-4">
                    <!-- Broadcast Category Type -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Notification Category (Type)</label>
                        <select x-model="form.type" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500 transition">
                            <option value="custom_broadcast">📢 General Announcement / Urgent Alert (Custom Broadcast)</option>
                            <option value="daily_rates">🌾 Daily Mandi Rates Summary</option>
                            <option value="weather">🌤️ Weather & Agronomic Advisory</option>
                            <option value="weekly_forecast">📈 Weekly Price Outlook & Forecast</option>
                            <option value="scheme">🏛️ Government Scheme Announcement</option>
                        </select>
                    </div>

                    <!-- Title Inputs -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Title (Kannada) <span class="text-rose-400">*</span></label>
                            <input type="text" x-model="form.title_kn" required placeholder="e.g. 🌾 ಇಂದಿನ ಮಂಡಿ ದರಗಳು ಅಪ್ಡೇಟ್ ಆಗಿವೆ"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Title (English - Optional)</label>
                            <input type="text" x-model="form.title_en" placeholder="e.g. 🌾 Today's APMC Mandi Rates Updated"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
                        </div>
                    </div>

                    <!-- Body Inputs -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Message Body (Kannada) <span class="text-rose-400">*</span></label>
                            <textarea x-model="form.body_kn" rows="2" required placeholder="ರೈತರಿಗೆ ತಲುಪಬೇಕಾದ ಸಂದೇಶದ ವಿವರ..."
                                      class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Message Body (English - Optional)</label>
                            <textarea x-model="form.body_en" rows="2" placeholder="English notification body text..."
                                      class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition"></textarea>
                        </div>
                    </div>

                    <!-- Target Landing Page & Quick Pills -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Destination Landing Page (URL) <span class="text-rose-400">*</span></label>
                        <div class="flex items-center gap-2 mb-2">
                            <input type="text" x-model="form.target_url" required placeholder="/crops"
                                   class="flex-1 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono focus:outline-none focus:border-emerald-500 transition">
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" @click="form.target_url = '/crops'" class="px-2 py-0.5 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 font-mono transition cursor-pointer">
                                /crops (Mandi Rates)
                            </button>
                            <button type="button" @click="form.target_url = '/'" class="px-2 py-0.5 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 font-mono transition cursor-pointer">
                                / (Home Page)
                            </button>
                            <button type="button" @click="form.target_url = '/weather'" class="px-2 py-0.5 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 font-mono transition cursor-pointer">
                                /weather (Weather Advisory)
                            </button>
                            <button type="button" @click="form.target_url = '/schemes'" class="px-2 py-0.5 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 font-mono transition cursor-pointer">
                                /schemes (Govt Schemes)
                            </button>
                            <button type="button" @click="form.target_url = '/news'" class="px-2 py-0.5 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[11px] text-slate-300 font-mono transition cursor-pointer">
                                /news (Agri News)
                            </button>
                        </div>
                    </div>

                    <!-- Live Mobile Preview Card with Real Application Logo -->
                    <div class="pt-2">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span>📱</span>
                            <span>Mobile Lock Screen Preview:</span>
                        </p>
                        
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-950 to-emerald-950/40 border border-emerald-500/30 shadow-lg flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-white/10 border border-emerald-400/40 p-1 flex items-center justify-center shrink-0 shadow-inner overflow-hidden">
                                <img src="{{ $appLogoUrl }}" alt="Application Logo" class="w-full h-full object-contain">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="font-bold text-emerald-400">Krushi Baandhava • ಕೃಷಿ ಬಾಂಧವ</span>
                                    <span>Just now</span>
                                </div>
                                <h4 class="text-xs sm:text-sm font-bold text-white mt-1 tracking-tight" 
                                    x-text="previewLang === 'kn' ? (form.title_kn || 'Notification Title') : (form.title_en || form.title_kn || 'Notification Title')"></h4>
                                <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5 leading-snug line-clamp-2" 
                                   x-text="previewLang === 'kn' ? (form.body_kn || 'Notification message text body...') : (form.body_en || form.body_kn || 'Notification message text body...')"></p>
                                <span class="inline-block mt-2 text-[10px] text-emerald-400/80 font-mono" x-text="'🔗 ' + form.target_url"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons: Test on Device & Broadcast -->
                    <div class="pt-3 flex flex-col sm:flex-row items-center gap-3">
                        <!-- Test on My Device Button -->
                        <button type="button" @click="testPushOnMyDevice" :disabled="testingPush || isBroadcasting"
                                class="w-full sm:w-auto py-3 px-4 bg-slate-800 hover:bg-slate-700 active:scale-[0.98] text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                            <span x-show="!testingPush">🧪 Test on My Device</span>
                            <span x-show="testingPush" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span>Testing delivery...</span>
                            </span>
                        </button>

                        <!-- Broadcast Button -->
                        <button type="submit" :disabled="isBroadcasting || testingPush"
                                class="w-full sm:flex-1 py-3 px-5 bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] text-white font-black text-xs sm:text-sm rounded-xl shadow-lg shadow-emerald-900/40 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                            <span x-show="!isBroadcasting">🚀 Broadcast to All <span x-text="formatNumber(totalSubscribers)"></span> Farmers</span>
                            <span x-show="isBroadcasting" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span>Broadcasting to subscribers...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Automated Daily Schedulers with Ultra-Crisp Toggle UI (5 Cols) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="p-6 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold text-lg">
                            ⏱️
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white tracking-tight">Automated Schedulers</h3>
                            <p class="text-xs text-slate-400">Configure daily background triggers (Auto-saves instantly on toggle).</p>
                        </div>
                    </div>
                    
                    <span x-show="savingSetting" class="text-[10px] font-mono text-emerald-400 animate-pulse bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-500/30">
                        Saving...
                    </span>
                </div>

                <div class="space-y-4">
                    <!-- Master Switch -->
                    <div class="p-4 rounded-2xl border transition-all duration-300"
                         :class="settings.pwa_push_enabled ? 'bg-emerald-950/20 border-emerald-500/40 shadow-sm' : 'bg-slate-900/60 border-slate-800 opacity-80'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base"
                                     :class="settings.pwa_push_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500 border border-slate-700'">
                                    ⚡
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-white block">PWA Push Notifications (Master Switch)</span>
                                    <span class="text-[10px] text-slate-400">Master switch to enable or disable all push notifications</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-colors"
                                      :class="settings.pwa_push_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                      x-text="settings.pwa_push_enabled ? 'ON' : 'OFF'"></span>
                                <!-- Modern Animated iOS Slider Button -->
                                <button type="button" 
                                        @click="toggleSetting('pwa_push_enabled', !settings.pwa_push_enabled)"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="settings.pwa_push_enabled ? 'bg-emerald-500 shadow-md shadow-emerald-500/30' : 'bg-slate-800 border-slate-700'">
                                    <span aria-hidden="true" 
                                          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                          :class="settings.pwa_push_enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sub Schedulers Container (Dims if Master Switch is OFF) -->
                    <div class="space-y-3.5 transition-opacity duration-300"
                         :class="settings.pwa_push_enabled ? 'opacity-100' : 'opacity-40 pointer-events-none'">

                        <!-- Schedule 1: Daily Mandi Rates -->
                        <div class="p-4 rounded-2xl border transition-all duration-300 space-y-3"
                             :class="settings.pwa_auto_rates_enabled ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-slate-900/60 border-slate-800'">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base"
                                         :class="settings.pwa_auto_rates_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500 border border-slate-700'">
                                        🌾
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-white block">Daily Mandi Rates Summary</span>
                                        <span class="text-[10px] text-slate-400">Evening closing rates summary across Karnataka APMC mandis</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-colors"
                                          :class="settings.pwa_auto_rates_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                          x-text="settings.pwa_auto_rates_enabled ? 'ACTIVE • ON' : 'INACTIVE • OFF'"></span>
                                    <button type="button" 
                                            @click="toggleSetting('pwa_auto_rates_enabled', !settings.pwa_auto_rates_enabled)"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="settings.pwa_auto_rates_enabled ? 'bg-emerald-500 shadow-md shadow-emerald-500/30' : 'bg-slate-800 border-slate-700'">
                                        <span aria-hidden="true" 
                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                              :class="settings.pwa_auto_rates_enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                Dispatched automatically each evening after APMC mandi auctions finish.
                            </p>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 transition-opacity"
                                 :class="settings.pwa_auto_rates_enabled ? 'opacity-100' : 'opacity-30 pointer-events-none'">
                                <span class="text-[11px] text-slate-400">Dispatch Time (IST):</span>
                                <input type="time" 
                                       x-model="settings.pwa_auto_rates_time"
                                       @change="updateTimeSetting('pwa_auto_rates_time', $event.target.value)"
                                       class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
                            </div>
                        </div>

                        <!-- Schedule 2: Morning Weather Advisory -->
                        <div class="p-4 rounded-2xl border transition-all duration-300 space-y-3"
                             :class="settings.pwa_auto_weather_enabled ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-slate-900/60 border-slate-800'">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base"
                                         :class="settings.pwa_auto_weather_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500 border border-slate-700'">
                                        🌤️
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-white block">Morning Weather Advisory</span>
                                        <span class="text-[10px] text-slate-400">Precipitation, humidity, and optimal crop spraying conditions</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-colors"
                                          :class="settings.pwa_auto_weather_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                          x-text="settings.pwa_auto_weather_enabled ? 'ACTIVE • ON' : 'INACTIVE • OFF'"></span>
                                    <button type="button" 
                                            @click="toggleSetting('pwa_auto_weather_enabled', !settings.pwa_auto_weather_enabled)"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="settings.pwa_auto_weather_enabled ? 'bg-emerald-500 shadow-md shadow-emerald-500/30' : 'bg-slate-800 border-slate-700'">
                                        <span aria-hidden="true" 
                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                              :class="settings.pwa_auto_weather_enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                Dispatched every morning with meteorological conditions and agronomic recommendations.
                            </p>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 transition-opacity"
                                 :class="settings.pwa_auto_weather_enabled ? 'opacity-100' : 'opacity-30 pointer-events-none'">
                                <span class="text-[11px] text-slate-400">Dispatch Time (IST):</span>
                                <input type="time" 
                                       x-model="settings.pwa_auto_weather_time"
                                       @change="updateTimeSetting('pwa_auto_weather_time', $event.target.value)"
                                       class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
                            </div>
                        </div>

                        <!-- Schedule 3: Weekly Monday Forecast -->
                        <div class="p-4 rounded-2xl border transition-all duration-300 space-y-3"
                             :class="settings.pwa_auto_forecast_enabled ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-slate-900/60 border-slate-800'">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base"
                                         :class="settings.pwa_auto_forecast_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500 border border-slate-700'">
                                        📈
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-white block">Weekly Monday Price Outlook</span>
                                        <span class="text-[10px] text-slate-400">7-15 day market trends and optimal selling window analysis</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-colors"
                                          :class="settings.pwa_auto_forecast_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                          x-text="settings.pwa_auto_forecast_enabled ? 'ACTIVE • ON' : 'INACTIVE • OFF'"></span>
                                    <button type="button" 
                                            @click="toggleSetting('pwa_auto_forecast_enabled', !settings.pwa_auto_forecast_enabled)"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="settings.pwa_auto_forecast_enabled ? 'bg-emerald-500 shadow-md shadow-emerald-500/30' : 'bg-slate-800 border-slate-700'">
                                        <span aria-hidden="true" 
                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                              :class="settings.pwa_auto_forecast_enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                Dispatched every Monday morning highlighting expected commodity price directions.
                            </p>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 transition-opacity"
                                 :class="settings.pwa_auto_forecast_enabled ? 'opacity-100' : 'opacity-30 pointer-events-none'">
                                <span class="text-[11px] text-slate-400">Monday Dispatch Time (IST):</span>
                                <input type="time" 
                                       x-model="settings.pwa_auto_forecast_time"
                                       @change="updateTimeSetting('pwa_auto_forecast_time', $event.target.value)"
                                       class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
                            </div>
                        </div>

                        <!-- Schedule 4: Govt Schemes Auto Alert -->
                        <div class="p-4 rounded-2xl border transition-all duration-300 space-y-2"
                             :class="settings.pwa_auto_scheme_enabled ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-slate-900/60 border-slate-800'">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base"
                                         :class="settings.pwa_auto_scheme_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500 border border-slate-700'">
                                        🏛️
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-white block">New Govt Schemes Auto-Alert</span>
                                        <span class="text-[10px] text-slate-400">Instant notification when a new subsidy or scheme is published</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-colors"
                                          :class="settings.pwa_auto_scheme_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                          x-text="settings.pwa_auto_scheme_enabled ? 'ACTIVE • ON' : 'INACTIVE • OFF'"></span>
                                    <button type="button" 
                                            @click="toggleSetting('pwa_auto_scheme_enabled', !settings.pwa_auto_scheme_enabled)"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="settings.pwa_auto_scheme_enabled ? 'bg-emerald-500 shadow-md shadow-emerald-500/30' : 'bg-slate-800 border-slate-700'">
                                        <span aria-hidden="true" 
                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                              :class="settings.pwa_auto_scheme_enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                Automatically alerts farmers as soon as a new government scheme is published in the CMS.
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Broadcast History & Delivery Logs -->
    <div class="p-6 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                    <span>📜</span>
                    <span>Broadcast Delivery History</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Complete record of previously broadcast notifications and live delivery statuses.</p>
            </div>
            <span class="text-xs text-slate-400 font-mono">
                Showing <span x-text="broadcastList.length"></span> records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800/80 text-slate-400 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-3">Date & Time</th>
                        <th class="py-3 px-3">Type</th>
                        <th class="py-3 px-3">Title & Message</th>
                        <th class="py-3 px-3">Target URL</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-3 text-right">Success / Failed</th>
                        <th class="py-3 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    <template x-for="item in broadcastList" :key="item.id">
                        <tr class="hover:bg-slate-900/50 transition">
                            <td class="py-3 px-3 text-slate-300 font-mono whitespace-nowrap" x-text="item.created_at_formatted || formatTimestamp(item.created_at)"></td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold"
                                      :class="{
                                          'bg-emerald-950 text-emerald-300 border border-emerald-800/40': item.type === 'daily_rates',
                                          'bg-cyan-950 text-cyan-300 border border-cyan-800/40': item.type === 'weather',
                                          'bg-purple-950 text-purple-300 border border-purple-800/40': item.type === 'weekly_forecast',
                                          'bg-amber-950 text-amber-300 border border-amber-800/40': item.type === 'scheme',
                                          'bg-slate-800 text-slate-300': item.type === 'custom_broadcast'
                                      }"
                                      x-text="getTypeLabel(item.type)"></span>
                            </td>
                            <td class="py-3 px-3 min-w-[220px]">
                                <div class="font-bold text-white leading-snug" x-text="item.title_kn"></div>
                                <div class="text-[11px] text-slate-400 line-clamp-1 mt-0.5" x-text="item.body_kn"></div>
                            </td>
                            <td class="py-3 px-3 text-slate-400 font-mono text-[11px] whitespace-nowrap" x-text="item.target_url"></td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold text-[10px]"
                                      :class="{
                                          'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': item.status === 'sent',
                                          'bg-amber-500/10 text-amber-400 border border-amber-500/20 animate-pulse': item.status === 'sending',
                                          'bg-rose-500/10 text-rose-400 border border-rose-500/20': item.status === 'failed' || item.status === 'pending'
                                      }"
                                      x-text="item.status === 'sent' ? '✓ Sent' : (item.status === 'sending' ? '• Sending...' : '✕ Failed')">
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-[11px] whitespace-nowrap">
                                <span class="text-emerald-400 font-bold" x-text="item.success_count || 0"></span>
                                <span class="text-slate-600">/</span>
                                <span class="text-rose-400" x-text="item.failure_count || 0"></span>
                            </td>
                            <td class="py-3 px-3 text-right whitespace-nowrap">
                                <button type="button" @click="deleteBroadcast(item.id)" class="text-slate-500 hover:text-rose-400 p-1 transition cursor-pointer" title="Delete broadcast log">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="broadcastList.length === 0">
                        <td colspan="7" class="py-8 text-center text-slate-500 text-xs">
                            No broadcast records found yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
