<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\DataSourceCredential;
use App\Models\DataSourceMapping;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\DataSources\DataGov\DataGovMarketDataProvider;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDataSourceTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@krushibaandhava.org'],
            [
                'name' => 'Data Admin',
                'password' => bcrypt('password123'),
                'role' => 'super_admin',
                'preferred_language' => 'kn',
            ]
        );
    }

    public function test_guest_cannot_access_datasources_admin(): void
    {
        $response = $this->get('/admin/datasources');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_datasources_list(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/datasources');

        $response->assertStatus(200);
        $response->assertSee('Provider Adapters');
        $response->assertSee('data_gov_mandi');
    }

    public function test_admin_can_create_datasource_with_encrypted_credentials(): void
    {
        $rand = rand(1000, 9999);
        $code = "ds_test_{$rand}";
        $secretKey = "secret_api_key_val_{$rand}";

        $payload = [
            'name' => "Test Source {$rand}",
            'code' => $code,
            'provider_class' => DataGovMarketDataProvider::class,
            'type' => 'market_prices',
            'base_url' => 'https://api.example.com/v1',
            'endpoint' => 'mandi-rates',
            'auth_type' => 'api_key',
            'sync_frequency' => 'daily',
            'timeout_seconds' => 25,
            'rate_limit_per_minute' => 60,
            'is_active' => '1',
            'api_key' => $secretKey,
        ];

        $response = $this->actingAs($this->admin)->post('/admin/datasources', $payload);
        $response->assertRedirect('/admin/datasources');
        $response->assertSessionHas('success');

        $dataSource = DataSource::where('code', $code)->first();
        $this->assertNotNull($dataSource);

        // Verify credential table stores encrypted value, NOT raw cleartext
        $rawRow = DB::table('data_source_credentials')
            ->where('data_source_id', $dataSource->id)
            ->first();

        $this->assertNotNull($rawRow);
        $this->assertNotEquals($secretKey, $rawRow->api_key, 'Raw database column must NOT store cleartext API key');

        // Verify model decrypts it on access
        $credential = $dataSource->credential;
        $this->assertEquals($secretKey, $credential->api_key);
        $this->assertStringContainsString('****', $credential->masked_api_key);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'entity_type' => 'DataSource',
            'entity_id' => $dataSource->id,
        ]);
    }

    public function test_admin_can_update_datasource_without_overwriting_credentials_when_blank(): void
    {
        $rand = rand(1000, 9999);
        $ds = DataSource::create([
            'name' => "Update DS {$rand}",
            'code' => "update_ds_{$rand}",
            'provider_class' => DataGovMarketDataProvider::class,
            'type' => 'market_prices',
            'base_url' => 'https://api.example.com/old',
            'auth_type' => 'api_key',
            'sync_frequency' => 'daily',
            'timeout_seconds' => 30,
            'is_active' => true,
        ]);

        DataSourceCredential::create([
            'data_source_id' => $ds->id,
            'api_key' => 'existing_secret_12345',
        ]);

        $updatePayload = [
            'name' => "Updated DS {$rand} Name",
            'code' => $ds->code,
            'provider_class' => DataGovMarketDataProvider::class,
            'type' => 'market_prices',
            'base_url' => 'https://api.example.com/new',
            'auth_type' => 'api_key',
            'sync_frequency' => 'twice_daily',
            'timeout_seconds' => 45,
            'is_active' => '1',
            'api_key' => '', // blank = keep existing
        ];

        $response = $this->actingAs($this->admin)->put("/admin/datasources/{$ds->id}", $updatePayload);
        $response->assertRedirect('/admin/datasources');

        $this->assertEquals("Updated DS {$rand} Name", $ds->fresh()->name);
        $this->assertEquals('existing_secret_12345', $ds->fresh()->credential->api_key);
    }

    public function test_admin_can_toggle_datasource_status(): void
    {
        $ds = DataSource::where('code', 'data_gov_mandi')->first();
        $this->assertNotNull($ds);

        $initialStatus = $ds->is_active;

        $response = $this->actingAs($this->admin)->post("/admin/datasources/{$ds->id}/toggle-status");
        $response->assertRedirect();

        $this->assertEquals(!$initialStatus, $ds->fresh()->is_active);

        // Toggle back
        $this->actingAs($this->admin)->post("/admin/datasources/{$ds->id}/toggle-status");
        $this->assertEquals($initialStatus, $ds->fresh()->is_active);
    }

    public function test_test_connection_endpoint_returns_diagnostics(): void
    {
        $ds = DataSource::where('code', 'data_gov_mandi')->first();
        $this->assertNotNull($ds);

        $response = $this->actingAs($this->admin)->postJson("/admin/datasources/{$ds->id}/test-connection");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'health' => [
                'http_status',
                'response_time_ms',
                'auth_result',
                'records_found',
                'detected_fields',
                'status',
            ],
            'log_id',
        ]);

        $this->assertTrue($response->json('ok'));
        $this->assertEquals(200, $response->json('health.http_status'));
        $this->assertEquals('healthy', $response->json('health.status'));

        // Assert logged in api_health_logs
        $this->assertDatabaseHas('api_health_logs', [
            'data_source_id' => $ds->id,
            'status' => 'healthy',
        ]);
    }

    public function test_trigger_sync_creates_sync_log(): void
    {
        $ds = DataSource::where('code', 'data_gov_mandi')->first();
        $this->assertNotNull($ds);

        $response = $this->actingAs($this->admin)->post("/admin/datasources/{$ds->id}/trigger-sync");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sync_logs', [
            'data_source_id' => $ds->id,
            'status' => 'success',
        ]);

        $this->assertEquals('success', $ds->fresh()->last_sync_status);
        $this->assertNotNull($ds->fresh()->last_sync_at);
    }

    public function test_admin_can_manage_field_mappings(): void
    {
        $ds = DataSource::where('code', 'data_gov_mandi')->first();

        // 1. View mappings
        $viewRes = $this->actingAs($this->admin)->get("/admin/datasources/{$ds->id}/mappings");
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Data Mappings');
        $viewRes->assertSee('Crop Aliases');

        // 2. Add custom field mapping
        $rand = rand(100, 999);
        $response = $this->actingAs($this->admin)->post("/admin/datasources/{$ds->id}/mappings/fields", [
            'source_field' => "Custom_Field_{$rand}",
            'target_field' => "custom_target_{$rand}",
            'transformation_rule' => 'trim',
        ]);
        $response->assertRedirect();

        $mapping = DataSourceMapping::where('data_source_id', $ds->id)
            ->where('source_field', "Custom_Field_{$rand}")
            ->first();

        $this->assertNotNull($mapping);

        // 3. Remove field mapping
        $delRes = $this->actingAs($this->admin)->delete("/admin/datasources/mappings/fields/{$mapping->id}");
        $delRes->assertRedirect();

        $this->assertDatabaseMissing('data_source_mappings', ['id' => $mapping->id]);
    }

    public function test_admin_can_view_sync_logs_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/sync-logs');
        $response->assertStatus(200);
        $response->assertSee('Health Audit Trail');
    }
}
