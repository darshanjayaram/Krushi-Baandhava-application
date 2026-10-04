<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationBroadcast;
use App\Models\PushSubscription;
use App\Models\SystemSetting;
use App\Services\Notification\PwaPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PwaNotificationController extends Controller
{
    protected PwaPushService $pushService;

    public function __construct(PwaPushService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Display PWA push notifications hub.
     */
    public function index(): View
    {
        // Auto-run migrations if tables were not yet created
        if (!\Illuminate\Support\Facades\Schema::hasTable('push_subscriptions') || !\Illuminate\Support\Facades\Schema::hasTable('notification_broadcasts')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        // Subscriber statistics
        $totalSubscribers = \Illuminate\Support\Facades\Schema::hasTable('push_subscriptions') ? PushSubscription::active()->count() : 0;
        $androidCount = \Illuminate\Support\Facades\Schema::hasTable('push_subscriptions') ? PushSubscription::active()->where('device_type', 'android')->count() : 0;
        $iosCount = \Illuminate\Support\Facades\Schema::hasTable('push_subscriptions') ? PushSubscription::active()->where('device_type', 'ios')->count() : 0;
        $desktopCount = \Illuminate\Support\Facades\Schema::hasTable('push_subscriptions') ? PushSubscription::active()->where('device_type', 'desktop')->count() : 0;

        // Recent broadcast history
        $broadcasts = NotificationBroadcast::with('creator')
            ->latest('id')
            ->paginate(15);

        // Automation settings
        $settings = [
            'pwa_push_enabled' => (bool) SystemSetting::get('pwa_push_enabled', true),
            'pwa_auto_rates_enabled' => (bool) SystemSetting::get('pwa_auto_rates_enabled', true),
            'pwa_auto_rates_time' => SystemSetting::get('pwa_auto_rates_time', '18:30'),
            'pwa_auto_weather_enabled' => (bool) SystemSetting::get('pwa_auto_weather_enabled', true),
            'pwa_auto_weather_time' => SystemSetting::get('pwa_auto_weather_time', '07:00'),
            'pwa_auto_forecast_enabled' => (bool) SystemSetting::get('pwa_auto_forecast_enabled', true),
            'pwa_auto_forecast_time' => SystemSetting::get('pwa_auto_forecast_time', '08:00'),
            'pwa_auto_scheme_enabled' => (bool) SystemSetting::get('pwa_auto_scheme_enabled', true),
            'vapid_public_key' => $this->pushService->getPublicKey(),
        ];

        return view('admin.notifications.index', compact(
            'totalSubscribers',
            'androidCount',
            'iosCount',
            'desktopCount',
            'broadcasts',
            'settings'
        ));
    }

    /**
     * Send an instant broadcast notification to all subscribed farmers.
     */
    public function sendBroadcast(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title_kn' => 'required|string|max:120',
            'body_kn' => 'required|string|max:300',
            'title_en' => 'nullable|string|max:120',
            'body_en' => 'nullable|string|max:300',
            'target_url' => 'required|string|max:255',
            'type' => 'required|string|in:custom_broadcast,daily_rates,weather,weekly_forecast,scheme',
        ]);

        $broadcast = NotificationBroadcast::create([
            'created_by' => auth()->id(),
            'type' => $validated['type'],
            'title_kn' => $validated['title_kn'],
            'title_en' => $validated['title_en'] ?? null,
            'body_kn' => $validated['body_kn'],
            'body_en' => $validated['body_en'] ?? null,
            'target_url' => $validated['target_url'],
            'status' => 'pending',
            'total_recipients' => 0,
        ]);

        $result = $this->pushService->broadcast($broadcast);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? "Notification successfully broadcast to {$result['sent']} subscribers!"
                    : 'Failed to broadcast notification. Please check system settings.',
                'broadcast' => [
                    'id' => $broadcast->id,
                    'type' => $broadcast->type,
                    'title_kn' => $broadcast->title_kn,
                    'body_kn' => $broadcast->body_kn,
                    'target_url' => $broadcast->target_url,
                    'status' => $broadcast->status,
                    'success_count' => $broadcast->success_count,
                    'failure_count' => $broadcast->failure_count,
                    'created_at_formatted' => $broadcast->created_at->format('d M Y, h:i A'),
                ],
                'stats' => $result,
            ], $result['success'] ? 200 : 400);
        }

        if ($result['success']) {
            return redirect()->route('admin.notifications.index')
                ->with('success', "Notification successfully broadcast to {$result['sent']} subscribers!");
        }

        return redirect()->route('admin.notifications.index')
            ->with('error', 'Failed to broadcast notification. Please check system settings.');
    }

    /**
     * Send a test push notification to admin's current device without page reload.
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:300',
            'url' => 'nullable|string|max:255',
        ]);

        $result = $this->pushService->sendDirect(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            [
                'title' => $validated['title'],
                'body' => $validated['body'],
                'url' => $validated['url'] ?? '/',
            ]
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Test notification successfully delivered to your device!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to deliver test notification: ' . ($result['reason'] ?? 'Unknown error'),
        ], 500);
    }

    /**
     * Update automated notification scheduler settings (supports async toggle & full form).
     */
    public function updateSettings(Request $request): JsonResponse|RedirectResponse
    {
        // Single setting key-value update via Async Toggle
        if ($request->has('setting_key')) {
            $key = $request->input('setting_key');
            $val = $request->input('setting_value');

            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($val) ? ($val ? 'true' : 'false') : (string) $val, 'group' => 'pwa']
            );

            \Illuminate\Support\Facades\Cache::flush();

            return response()->json([
                'success' => true,
                'key' => $key,
                'value' => $val,
                'message' => 'Setting updated successfully.',
            ]);
        }

        // Bulk form update
        $settingsToUpdate = [
            'pwa_push_enabled' => $request->has('pwa_push_enabled') ? 'true' : 'false',
            'pwa_auto_rates_enabled' => $request->has('pwa_auto_rates_enabled') ? 'true' : 'false',
            'pwa_auto_rates_time' => $request->input('pwa_auto_rates_time', '18:30'),
            'pwa_auto_weather_enabled' => $request->has('pwa_auto_weather_enabled') ? 'true' : 'false',
            'pwa_auto_weather_time' => $request->input('pwa_auto_weather_time', '07:00'),
            'pwa_auto_forecast_enabled' => $request->has('pwa_auto_forecast_enabled') ? 'true' : 'false',
            'pwa_auto_forecast_time' => $request->input('pwa_auto_forecast_time', '08:00'),
            'pwa_auto_scheme_enabled' => $request->has('pwa_auto_scheme_enabled') ? 'true' : 'false',
        ];

        foreach ($settingsToUpdate as $key => $val) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val, 'group' => 'pwa']
            );
        }

        \Illuminate\Support\Facades\Cache::flush();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification schedule rules updated successfully.',
            ]);
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notification schedule rules updated successfully.');
    }

    /**
     * Delete a broadcast log record (supports async delete).
     */
    public function destroy(NotificationBroadcast $broadcast, Request $request): JsonResponse|RedirectResponse
    {
        $id = $broadcast->id;
        $broadcast->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $id,
                'message' => 'Broadcast record deleted successfully.',
            ]);
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Broadcast record deleted successfully.');
    }
}
