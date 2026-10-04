# PWA General Push Notifications & Admin Management Hub — Implementation Plan

## 1. Overview & Objective
Enable **Progressive Web App (PWA) Push Notifications** for all farmers in Karnataka who install or visit Krushi Baandhava, managed completely from an intuitive **Admin Control Hub**.

This system delivers high-impact **General Notifications** (Daily APMC Mandi Rates, Weather Advisories, Weekly Forecasts, and Govt Scheme Alerts) to mobile lock screens even when the browser or app is closed.

---

## 2. Technical Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              FARMER PWA CLIENT                              │
│  [Browser / PWA] ──(Allow Prompt)──> sw.js registration.pushManager        │
│                                              │                              │
│                                   (POST /api/pwa/subscribe)                 │
└──────────────────────────────────────────────┼──────────────────────────────┘
                                               ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           LARAVEL BACKEND DATABASE                          │
│                                                                             │
│  • push_subscriptions       (endpoint, p256dh, auth, device_type, is_active) │
│  • notification_broadcasts  (title_kn, title_en, body_kn, body_en, status)   │
│  • system_settings          (vapid keys, auto_rates_time, auto_weather_time)│
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
                      ┌────────────────┴────────────────┐
                      ▼                                 ▼
      ┌──────────────────────────────┐  ┌──────────────────────────────────┐
      │   ADMIN MANAGEMENT HUB UI    │  │   LARAVEL CONSOLE SCHEDULER      │
      │   • Instant Broadcast        │  │   • Daily Mandi Rates (6:30 PM)  │
      │   • Live Notification Preview│  │   • Morning Weather (7:00 AM)    │
      │   • Scheduled Timers & Toggle│  │   • Weekly Forecast (Mon 8:00 AM)│
      │   • Subscriber Device Stats  │  │                                  │
      └───────────────┬──────────────┘  └─────────────────┬────────────────┘
                      │                                   │
                      └────────────────┬──────────────────┘
                                       ▼
                       [App\Services\Notification\PwaPushService]
                                       │ (minishlink/web-push)
                                       ▼
                         FCM / Mozilla / Apple WebPush
                                       │
                                       ▼
                    [Farmer's Mobile Screen Notification]
```

---

## 3. Phased Implementation Breakdown

### Phase 1: Database Architecture & Core WebPush Engine
* **Step 1.1:** Require `minishlink/web-push` via Composer (VAPID ECDSA NIST P-256 web push protocol).
* **Step 1.2:** Create database migration for:
  - `push_subscriptions`: stores endpoint, public keys (`p256dh`, `auth`), content encoding, device type (`android`, `ios`, `desktop`), language preference (`kn`, `en`), active state.
  - `notification_broadcasts`: logs title, body, target URL, type (`daily_rates`, `weather`, `scheme`, `custom_broadcast`), delivery count, success/fail metrics, sender admin ID.
* **Step 1.3:** Create Eloquent models:
  - `App\Models\PushSubscription`
  - `App\Models\NotificationBroadcast`
* **Step 1.4:** Generate & store VAPID public/private keypair in `SystemSetting` / `.env`.
* **Step 1.5:** Build `App\Services\Notification\PwaPushService`:
  - Methods: `sendToSubscription()`, `broadcast()`, `sendTest()`, `pruneExpired()`.
  - Automatically deactivates expired subscriptions (HTTP 404 / 410 Gone responses).

### Phase 2: Client-Side (PWA) Subscription & Opt-In UI
* **Step 2.1:** Create `public/js/pwa-push.js`:
  - Detects Service Worker and PushManager support.
  - Converts VAPID public key to `Uint8Array`.
  - Subscribes via `registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey })`.
  - Sends subscription details to `POST /api/pwa/subscribe`.
* **Step 2.2:** Build user-friendly farmer opt-in prompt (Bilingual Kannada/English):
  - Subtle floating card / notification bell: *"🔔 ಇಂದಿನ ಮಂಡಿ ದರಗಳು ಮತ್ತು ಹವಾಮಾನ ಎಚ್ಚರಿಕೆಗಳನ್ನು ಮೊಬೈಲ್‌ನಲ್ಲಿ ಪಡೆಯಿರಿ"* with *"ಅನುಮತಿಸಿ (Enable)"* and *"ನಂತರ (Later)"*.
* **Step 2.3:** Enhance `public/sw.js` notification click handling to open the specific `target_url` smoothly.

### Phase 3: Admin Management Hub (UI & Controllers)
* **Step 3.1:** Create `App\Http\Controllers\Admin\PwaNotificationController`:
  - `index()`: Loads subscriber analytics, schedule configs, and broadcast history.
  - `sendBroadcast()`: Handles manual/instant broadcast to all subscribers.
  - `sendTestNotification()`: Sends a single push to the admin's device to test payload and styling.
  - `updateSchedules()`: Updates automated triggers (time and ON/OFF toggles).
* **Step 3.2:** Register Admin Routes in `routes/web.php` under `admin/notifications`.
* **Step 3.3:** Add Admin Sidebar link with badge showing total subscribers in `resources/views/layouts/admin.blade.php`.
* **Step 3.4:** Create `resources/views/admin/notifications/index.blade.php`:
  - Metric Cards: Total Subscribers, Android %, iOS %, Delivery Success Rate.
  - Live Mobile Notification Preview card (interactive preview updating as admin types in Kannada/English).
  - Instant Compose Form (Title, Body, Target Destination URL selector).
  - Automated Schedulers Card (Toggles for 6:30 PM Rates, 7:00 AM Weather, 8:00 AM Forecast).
  - Broadcast History Table (Timestamp, Title, Recipients, Status, Delivered %).

### Phase 4: Automated Scheduled Tasks & Event Hooks
* **Step 4.1:** Artisan command `php artisan pwa:send-daily-rates`:
  - Summarizes today's top APMC price movements across Karnataka mandis.
  - Dispatches push notification to all subscribers.
* **Step 4.2:** Artisan command `php artisan pwa:send-weather-alert`:
  - Summarizes regional weather conditions and advisories.
* **Step 4.3:** Register schedules in `routes/console.php` reading times from admin settings:
  - Daily at 18:30 (Mandi rates).
  - Daily at 07:00 (Weather).
* **Step 4.4:** Optional scheme trigger: When new scheme published in `SchemeController`, prompt or auto-notify farmers.

### Phase 5: Verification & End-to-End Testing
* **Step 5.1:** Write unit/feature tests in `tests/Feature/PwaNotificationTest.php`:
  - Subscription registration API (`/api/pwa/subscribe`).
  - Unsubscribe API (`/api/pwa/unsubscribe`).
  - Admin broadcast permission checks and payload delivery.
  - 410 Gone dead subscription cleanup logic.
* **Step 5.2:** Verify live in browser:
  - Open PWA, click enable notifications.
  - Verify record stored in `push_subscriptions`.
  - Admin triggers broadcast, verify receipt and click redirection.

---

## 4. Execution Roadmap
1. **Phase 1:** Core dependencies, database tables, VAPID key generator, PwaPushService. *(Immediate Start)*
2. **Phase 2:** PWA client script, API endpoints, opt-in banner.
3. **Phase 3:** Admin controller, views, routing, and live mobile preview UI.
4. **Phase 4:** Scheduled console commands & auto-triggers.
5. **Phase 5:** Automated test suite & verification.
