<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\Notification\PwaPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PwaPushSubscriptionController extends Controller
{
    protected PwaPushService $pushService;

    public function __construct(PwaPushService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Get VAPID public key for frontend subscription.
     */
    public function vapidKey(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'enabled' => $this->pushService->isEnabled(),
            'publicKey' => $this->pushService->getPublicKey(),
        ]);
    }

    /**
     * Store or refresh a PWA push subscription.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'content_encoding' => 'nullable|string|max:32',
            'device_type' => 'nullable|string|in:android,ios,desktop,unknown',
            'language' => 'nullable|string|in:kn,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $endpoint = trim($request->input('endpoint'));
        $endpointHash = PushSubscription::hashEndpoint($endpoint);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => auth()->id() ?? null,
                'endpoint' => $endpoint,
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'content_encoding' => $request->input('content_encoding', 'aes128gcm') ?: 'aes128gcm',
                'device_type' => $request->input('device_type', 'unknown'),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'preferred_language' => $request->input('language', session('locale', 'kn')),
                'is_active' => true,
                'last_active_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Push notifications subscription saved successfully.',
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Deactivate a PWA push subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $endpoint = trim((string) $request->input('endpoint'));
        if (!$endpoint) {
            return response()->json(['success' => false, 'message' => 'Endpoint required.'], 400);
        }

        $endpointHash = PushSubscription::hashEndpoint($endpoint);
        PushSubscription::where('endpoint_hash', $endpointHash)->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Push subscription deactivated successfully.',
        ]);
    }
}
