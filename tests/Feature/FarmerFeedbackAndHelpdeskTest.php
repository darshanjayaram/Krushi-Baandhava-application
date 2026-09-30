<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\FarmerFeedback;
use App\Models\Market;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FarmerFeedbackAndHelpdeskTest extends TestCase
{
    public function test_farmer_feedback_page_loads_successfully(): void
    {
        $response = $this->get(route('farmer.feedback.create'));

        $response->assertStatus(200);
        $response->assertSee('ಸಮಸ್ಯೆ ವರದಿ');
        $response->assertSee('ಸಲಹೆ');
    }

    public function test_farmer_can_submit_issue_with_photo_voice_and_email(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('mandi_slip.jpg', 600, 600);
        $voice = UploadedFile::fake()->create('voice.webm', 256, 'audio/webm');

        $payload = [
            'type' => 'issue',
            'category' => 'price_discrepancy',
            'crop_name' => 'Tomato',
            'market_name' => 'Kolar APMC',
            'message' => 'Mandi reported price is Rs. 200 higher than actual auction rate.',
            'farmer_name' => 'Ramesh Gowda',
            'farmer_phone' => '9876543210',
            'farmer_email' => 'ramesh.farmer@example.com',
            'photo' => $photo,
            'voice' => $voice,
            'voice_duration' => 45,
        ];

        $response = $this->post(route('farmer.feedback.store'), $payload);

        $response->assertRedirect(route('farmer.feedback.create'));
        $this->assertDatabaseHas('farmer_feedbacks', [
            'type' => 'issue',
            'category' => 'price_discrepancy',
            'farmer_phone' => '9876543210',
            'farmer_email' => 'ramesh.farmer@example.com',
            'crop_name' => 'Tomato',
            'status' => 'new',
        ]);

        $feedback = FarmerFeedback::where('farmer_phone', '9876543210')->first();
        $this->assertNotNull($feedback);
        $this->assertStringStartsWith('KB-', $feedback->ticket_no);
        $this->assertEquals('ramesh.farmer@example.com', $feedback->farmer_email);
        $this->assertNotNull($feedback->photo_path);
        $this->assertNotNull($feedback->voice_path);
        Storage::disk('public')->assertExists($feedback->photo_path);
        Storage::disk('public')->assertExists($feedback->voice_path);
    }

    public function test_farmer_can_submit_ajax_feedback_with_email(): void
    {
        $payload = [
            'type' => 'feedback',
            'category' => 'feature_request',
            'rating' => 5,
            'message' => 'Please add WhatsApp alert service for daily prices.',
            'farmer_name' => 'Suresh Kumar',
            'farmer_phone' => '9123456789',
            'farmer_email' => 'suresh@krushi.org',
        ];

        $response = $this->postJson(route('farmer.feedback.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'type' => 'feedback',
        ]);
        $this->assertNotEmpty($response->json('ticket_no'));

        $this->assertDatabaseHas('farmer_feedbacks', [
            'farmer_phone' => '9123456789',
            'farmer_email' => 'suresh@krushi.org',
        ]);
    }

    public function test_admin_can_view_and_manage_feedbacks(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $feedback = FarmerFeedback::create([
            'type' => 'issue',
            'category' => 'weighing_issue',
            'message' => 'Weighing scale fraud reported in market.',
            'voice_path' => 'uploads/feedback/voice/sample_test.webm',
            'voice_duration' => 15,
            'farmer_name' => 'Basavaraj',
            'farmer_phone' => '9988776655',
            'status' => 'new',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.feedback.index'));

        $response->assertStatus(200);
        $response->assertSee($feedback->ticket_no);
        $response->assertSee('Basavaraj');
        $response->assertSee('9988776655');

        // Test show endpoint returns valid voice_url
        $showResponse = $this->actingAs($admin)->get(route('admin.feedback.show', $feedback));
        $showResponse->assertStatus(200);
        $showResponse->assertJson([
            'success' => true,
            'data' => [
                'id' => $feedback->id,
                'ticket_no' => $feedback->ticket_no,
                'voice_duration' => 15,
            ],
        ]);
        $this->assertNotNull($showResponse->json('data.voice_url'));
        $this->assertStringContainsString('uploads/feedback/voice/sample_test.webm', $showResponse->json('data.voice_url'));
        $response->assertSee('9988776655');

        // Test updating status and notes
        $updateResponse = $this->actingAs($admin)->put(route('admin.feedback.update', $feedback), [
            'status' => 'resolved',
            'admin_notes' => 'Contacted APMC secretary and verified weighing scales.',
        ]);

        $updateResponse->assertRedirect();
        $feedback->refresh();
        $this->assertEquals('resolved', $feedback->status);
        $this->assertEquals($admin->id, $feedback->resolved_by);
        $this->assertNotNull($feedback->resolved_at);
        $this->assertEquals('Contacted APMC secretary and verified weighing scales.', $feedback->admin_notes);
    }

    public function test_admin_can_manage_form_settings_and_categories(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $settingsPayload = [
            'enable_voice' => '1',
            'max_voice_seconds' => 120,
            'enable_photos' => '1',
            'enable_email' => '1',
            'support_whatsapp' => '919999888877',
            'notification_email' => 'alerts@krushibaandhava.org',
            'issue_categories' => [
                [
                    'id' => 'price_discrepancy',
                    'icon' => '💰',
                    'label_en' => 'Price Discrepancy',
                    'label_kn' => 'ದರ ವ್ಯತ್ಯಾಸ',
                    'is_active' => '1',
                ],
                [
                    'id' => 'custom_mandi_issue',
                    'icon' => '🏢',
                    'label_en' => 'Custom Mandi Gate Issue',
                    'label_kn' => 'ಮಾರುಕಟ್ಟೆ ಗೇಟ್ ಸಮಸ್ಯೆ',
                    'is_active' => '1',
                ]
            ],
            'feedback_categories' => [
                [
                    'id' => 'feature_request',
                    'icon' => '✨',
                    'label_en' => 'Feature Request',
                    'label_kn' => 'ಹೊಸ ವೈಶಿಷ್ಟ್ಯ',
                    'is_active' => '1',
                ]
            ]
        ];

        $response = $this->actingAs($admin)->post(route('admin.feedback.settings.update'), $settingsPayload);

        $response->assertRedirect(route('admin.feedback.index', ['tab' => 'settings']));
        $this->assertEquals('919999888877', SystemSetting::get('feedback_support_whatsapp'));
        $this->assertEquals(120, SystemSetting::get('feedback_max_voice_seconds'));

        // Test reset to defaults
        $resetResponse = $this->actingAs($admin)->post(route('admin.feedback.settings.reset'));
        $resetResponse->assertRedirect(route('admin.feedback.index', ['tab' => 'settings']));
        $this->assertEquals(180, SystemSetting::get('feedback_max_voice_seconds'));
    }
}
