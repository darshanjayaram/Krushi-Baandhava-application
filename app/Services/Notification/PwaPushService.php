<?php

namespace App\Services\Notification;

use App\Models\NotificationBroadcast;
use App\Models\PushSubscription;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PwaPushService
{
    protected ?WebPush $webPush = null;

    /**
     * Get or create WebPush instance with current VAPID credentials.
     */
    public function getWebPush(): WebPush
    {
        if ($this->webPush !== null) {
            return $this->webPush;
        }

        $subject = SystemSetting::get('vapid_subject', config('webpush.vapid.subject', 'mailto:contact@krushibaandhava.in'));
        $publicKey = SystemSetting::get('vapid_public_key', config('webpush.vapid.public_key'));
        $privateKey = SystemSetting::get('vapid_private_key', config('webpush.vapid.private_key'));

        $auth = [
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];

        $defaultOptions = [
            'TTL' => (int) config('webpush.defaults.ttl', 86400),
            'urgency' => config('webpush.defaults.urgency', 'normal'),
        ];

        $this->webPush = new WebPush($auth, $defaultOptions);
        $this->webPush->setReuseVAPIDHeaders(true);

        return $this->webPush;
    }

    /**
     * Get public VAPID key for frontend registration.
     */
    public function getPublicKey(): string
    {
        return SystemSetting::get('vapid_public_key', config('webpush.vapid.public_key', ''));
    }

    /**
     * Check if master PWA push notifications switch is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) SystemSetting::get('pwa_push_enabled', true);
    }

    /**
     * Send a push notification to a single PushSubscription.
     */
    public function sendToSubscription(PushSubscription $subscription, array $payload): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'reason' => 'PWA push notifications are disabled in system settings.',
            ];
        }

        try {
            $webPush = $this->getWebPush();

            $subObject = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
            ]);

            $defaultLogo = SystemSetting::get('app_logo', '/icons/icon-192.svg');
            $jsonPayload = json_encode([
                'title' => $payload['title'] ?? 'Krushi Baandhava - ಕೃಷಿ ಬಾಂಧವ',
                'body' => $payload['body'] ?? 'ಹೊಸ ಮಂಡಿ ದರಗಳು ಲಭ್ಯವಿದೆ.',
                'url' => $payload['url'] ?? '/',
                'icon' => $payload['icon'] ?? $defaultLogo,
                'badge' => $payload['badge'] ?? $defaultLogo,
                'data' => [
                    'url' => $payload['url'] ?? '/',
                ],
            ], JSON_UNESCAPED_UNICODE);

            $report = $webPush->sendOneNotification($subObject, $jsonPayload);

            if ($report->isSuccess()) {
                $subscription->update(['last_active_at' => now()]);
                return [
                    'success' => true,
                    'endpoint' => $subscription->endpoint,
                ];
            }

            // If subscription has expired or is unsubscribed (404 Not Found, 410 Gone)
            if ($report->isSubscriptionExpired()) {
                $subscription->update(['is_active' => false]);
                Log::info("Push subscription marked inactive (expired/gone): {$subscription->id}");
            }

            return [
                'success' => false,
                'reason' => $report->getReason(),
                'expired' => $report->isSubscriptionExpired(),
            ];
        } catch (\Throwable $e) {
            Log::error("Failed to send PWA push to subscription {$subscription->id}: " . $e->getMessage());
            return [
                'success' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a direct push notification to an arbitrary device (used for Admin Test Push).
     */
    public function sendDirect(string $endpoint, string $publicKey, string $authToken, array $payload, string $contentEncoding = 'aes128gcm'): array
    {
        try {
            $webPush = $this->getWebPush();

            $subObject = Subscription::create([
                'endpoint' => $endpoint,
                'publicKey' => $publicKey,
                'authToken' => $authToken,
                'contentEncoding' => $contentEncoding ?: 'aes128gcm',
            ]);

            $defaultLogo = SystemSetting::get('app_logo', '/icons/icon-192.svg');
            $jsonPayload = json_encode([
                'title' => $payload['title'] ?? 'Krushi Baandhava - ಟೆಸ್ಟ್ ಅಧಿಸೂಚನೆ',
                'body' => $payload['body'] ?? 'ಇದು ಪರೀಕ್ಷಾರ್ಥ ಅಧಿಸೂಚನೆ (Test Notification).',
                'url' => $payload['url'] ?? '/',
                'icon' => $payload['icon'] ?? $defaultLogo,
                'badge' => $payload['badge'] ?? $defaultLogo,
                'data' => [
                    'url' => $payload['url'] ?? '/',
                ],
            ], JSON_UNESCAPED_UNICODE);

            $report = $webPush->sendOneNotification($subObject, $jsonPayload);

            if ($report->isSuccess()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'reason' => $report->getReason(),
            ];
        } catch (\Throwable $e) {
            Log::error("Failed to send direct test push: " . $e->getMessage());
            return [
                'success' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }


    /**
     * Broadcast a notification to all active subscribers.
     */
    public function broadcast(NotificationBroadcast $broadcast): array
    {
        if (!$this->isEnabled()) {
            $broadcast->update([
                'status' => 'failed',
                'error_details' => 'PWA push notifications are disabled in system settings.',
            ]);
            return [
                'success' => false,
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
            ];
        }

        $broadcast->update(['status' => 'sending']);

        $subscriptions = PushSubscription::active()->get();
        $total = $subscriptions->count();
        $successCount = 0;
        $failureCount = 0;
        $webPush = $this->getWebPush();

        foreach ($subscriptions as $subscription) {
            $isEn = ($subscription->preferred_language === 'en') && !empty($broadcast->title_en);
            
            $title = $isEn ? $broadcast->title_en : $broadcast->title_kn;
            $body = $isEn ? ($broadcast->body_en ?: $broadcast->body_kn) : $broadcast->body_kn;

            $appLogo = SystemSetting::get('app_logo', '/icons/icon-192.svg');
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $broadcast->target_url ?: '/',
                'icon' => $appLogo,
                'badge' => $appLogo,
                'data' => [
                    'url' => $broadcast->target_url ?: '/',
                ],
            ], JSON_UNESCAPED_UNICODE);

            try {
                $subObject = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                ]);

                $webPush->queueNotification($subObject, $payload);
            } catch (\Throwable $e) {
                $failureCount++;
                Log::warning("Failed to queue push notification: " . $e->getMessage());
            }
        }

        // Flush all queued notifications in concurrent HTTP requests
        $expiredSubscriptions = [];
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                $successCount++;
            } else {
                $failureCount++;
                if ($report->isSubscriptionExpired()) {
                    $expiredSubscriptions[] = $endpoint;
                }
            }
        }

        // Deactivate expired endpoints in bulk
        if (!empty($expiredSubscriptions)) {
            PushSubscription::whereIn('endpoint', $expiredSubscriptions)->update(['is_active' => false]);
            Log::info("Bulk deactivated " . count($expiredSubscriptions) . " expired push subscriptions.");
        }

        $broadcast->update([
            'status' => 'sent',
            'total_recipients' => $total,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'sent_at' => now(),
        ]);

        return [
            'success' => true,
            'total' => $total,
            'sent' => $successCount,
            'failed' => $failureCount,
        ];
    }
}
