<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaNotificationTest extends TestCase
{
    /**
     * Test VAPID public key API endpoint.
     */
    public function test_vapid_key_endpoint_returns_valid_public_key(): void
    {
        $response = $this->getJson(route('api.v1.pwa.vapid-key'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'enabled' => true,
            ]);

        $this->assertNotEmpty($response->json('publicKey'));
    }

    /**
     * Test saving a new PWA push subscription.
     */
    public function test_farmer_can_subscribe_to_pwa_push_notifications(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-farmer-token-' . uniqid();
        $payload = [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4Ywf',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'device_type' => 'android',
            'language' => 'kn',
        ];

        $response = $this->postJson(route('api.v1.pwa.subscribe'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $endpoint,
            'device_type' => 'android',
            'preferred_language' => 'kn',
            'is_active' => true,
        ]);
    }

    /**
     * Test idempotency on repeated subscription.
     */
    public function test_repeated_subscription_updates_existing_record_smoothly(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-repeat-token';
        $payload = [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4Ywf',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'device_type' => 'android',
            'language' => 'kn',
        ];

        $this->postJson(route('api.v1.pwa.subscribe'), $payload)->assertStatus(200);

        // Update language to English
        $payload['language'] = 'en';
        $payload['device_type'] = 'ios';
        $this->postJson(route('api.v1.pwa.subscribe'), $payload)->assertStatus(200);

        $this->assertEquals(1, PushSubscription::where('endpoint', $endpoint)->count());
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $endpoint,
            'device_type' => 'ios',
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
    }

    /**
     * Test unsubscription endpoint.
     */
    public function test_farmer_can_unsubscribe_from_pwa_push_notifications(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-unsub-token';
        PushSubscription::create([
            'endpoint' => $endpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
            'public_key' => 'test-key',
            'auth_token' => 'test-auth',
            'device_type' => 'android',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('api.v1.pwa.unsubscribe'), ['endpoint' => $endpoint]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $endpoint,
            'is_active' => false,
        ]);
    }

    /**
     * Test Admin Notifications Hub page loads for authenticated admin.
     */
    public function test_admin_can_view_pwa_notifications_hub(): void
    {
        $admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('admin.notifications.index'));

        $response->assertStatus(200)
            ->assertSee('PWA Push Notifications Hub')
            ->assertSee('Instant Broadcast Composer')
            ->assertSee(SystemSetting::get('app_logo', '/icons/icon-192.svg'));
    }

    /**
     * Test Admin can update automation settings.
     */
    public function test_admin_can_update_automation_settings(): void
    {
        $admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.notifications.settings'), [
            'pwa_push_enabled' => 'true',
            'pwa_auto_rates_enabled' => 'true',
            'pwa_auto_rates_time' => '19:00',
            'pwa_auto_weather_enabled' => 'true',
            'pwa_auto_weather_time' => '06:30',
        ]);

        $response->assertRedirect(route('admin.notifications.index'));

        $this->assertEquals('19:00', SystemSetting::get('pwa_auto_rates_time'));
        $this->assertEquals('06:30', SystemSetting::get('pwa_auto_weather_time'));
    }

    /**
     * Test Admin can toggle setting asynchronously via JSON without page reload.
     */
    public function test_admin_can_toggle_setting_asynchronously_via_json(): void
    {
        $admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)->postJson(route('admin.notifications.settings'), [
            'setting_key' => 'pwa_auto_rates_enabled',
            'setting_value' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'key' => 'pwa_auto_rates_enabled',
            ]);

        $this->assertFalse((bool) filter_var(SystemSetting::get('pwa_auto_rates_enabled'), FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * Test Admin can broadcast notification asynchronously via JSON without page reload.
     */
    public function test_admin_can_broadcast_asynchronously_via_json(): void
    {
        $admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)->postJson(route('admin.notifications.broadcast'), [
            'type' => 'custom_broadcast',
            'title_kn' => '⚠️ ನಾಳೆ ಮಂಡಿ ರಜೆ',
            'body_kn' => 'ಸಾರ್ವಜನಿಕ ರಜೆ ಪ್ರಯುಕ್ತ ನಾಳೆ ಎಲ್ಲಾ ಮಂಡಿಗಳಿಗೆ ರಜೆ ಇರಲಿದೆ.',
            'target_url' => '/crops',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'broadcast' => [
                    'type' => 'custom_broadcast',
                    'title_kn' => '⚠️ ನಾಳೆ ಮಂಡಿ ರಜೆ',
                ],
            ]);

        $this->assertDatabaseHas('notification_broadcasts', [
            'title_kn' => '⚠️ ನಾಳೆ ಮಂಡಿ ರಜೆ',
            'target_url' => '/crops',
        ]);
    }

    /**
     * Test artisan daily rates command handles empty subscriber gracefully.
     */
    public function test_artisan_daily_rates_command_executes_successfully(): void
    {
        $this->artisan('pwa:send-daily-rates --force')
            ->assertSuccessful();
    }

    /**
     * Test artisan weather alert command executes successfully.
     */
    public function test_artisan_weather_command_executes_successfully(): void
    {
        $this->artisan('pwa:send-weather-alert --force')
            ->assertSuccessful();
    }
}
