<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    protected function tearDown(): void
    {
        // Cleanup lock file if created in test
        if (File::exists(storage_path('installed'))) {
            File::delete(storage_path('installed'));
        }
        parent::tearDown();
    }

    public function test_setup_wizard_page_is_accessible_when_unlocked(): void
    {
        if (File::exists(storage_path('installed'))) {
            File::delete(storage_path('installed'));
        }

        $response = $this->get(route('setup.index'));
        $response->assertStatus(200);
        $response->assertSee('Krushi Baandhava');
        $response->assertSee('Step 1: Database');
        $response->assertSee('Step 2: Create Super Administrator Account');
        $response->assertSee('Step 3: Master Data');
        $response->assertSee('Karnataka State APMC Directory');
    }

    public function test_setup_wizard_locks_permanently_after_installation(): void
    {
        // Create lock file
        File::put(storage_path('installed'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]));

        // Visiting /setup when installed should show the locked view
        $response = $this->get(route('setup.index'));
        $response->assertStatus(200);
        $response->assertSee('Setup Wizard is Locked');
        $response->assertSee('permanently disabled');
        $response->assertSee('Go to Admin Login');

        // Attempting to post to /setup when locked must be blocked with 403 Forbidden
        $postResponse = $this->post(route('setup.run'), [
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'krushi_baandhava',
            'db_username' => 'root',
            'admin_name' => 'Hacker User',
            'admin_email' => 'hacker@example.com',
            'admin_password' => 'secret1234',
            'admin_password_confirmation' => 'secret1234',
        ]);

        $postResponse->assertStatus(403);
    }

    public function test_setup_test_db_ajax_endpoint_returns_json(): void
    {
        $response = $this->postJson(route('setup.test-db'), [
            'host' => config('database.connections.mysql.host', '127.0.0.1'),
            'port' => config('database.connections.mysql.port', '3306'),
            'database' => config('database.connections.mysql.database', 'krushi_baandhava'),
            'username' => config('database.connections.mysql.username', 'root'),
            'password' => config('database.connections.mysql.password', ''),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message']);
        $this->assertTrue($response->json('success'));
    }

    public function test_admin_deployment_hub_accessible_by_super_admin(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'superadmin_test@krushibaandhava.org'],
            [
                'name' => 'Super Admin Test',
                'phone' => '9800099999',
                'role' => User::ROLE_SUPER_ADMIN,
                'preferred_language' => 'en',
                'password' => bcrypt('password123'),
            ]
        );

        $response = $this->actingAs($admin)->get(route('admin.deployment-hub.index'));
        $response->assertStatus(200);
        $response->assertSee('Deployment');
        $response->assertSee('APMC Discovery Hub');
        $response->assertSee('Auto-Discover Mandis');
        $response->assertSee('Auto-Discover Crops');
        $response->assertSee('Sync Directory');
        $response->assertSee('Sync Live Rates');
    }

    public function test_admin_can_trigger_live_prices_sync_from_hub(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'superadmin_test@krushibaandhava.org'],
            [
                'name' => 'Super Admin Test',
                'phone' => '9800099999',
                'role' => User::ROLE_SUPER_ADMIN,
                'preferred_language' => 'en',
                'password' => bcrypt('password123'),
            ]
        );

        $response = $this->actingAs($admin)->post(route('admin.deployment-hub.sync-prices'), [
            'source' => 'data_gov_mandi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_setup_bootstrap_artisan_command_succeeds(): void
    {
        $this->artisan('app:bootstrap', ['--force' => true])
            ->assertSuccessful();

        $this->assertFileExists(storage_path('installed'));
    }
}
