<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Models\FeatureFlag;
use App\Models\Market;
use App\Models\MarketPriceRaw;
use App\Models\MarketSourceMapping;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminAnalyticsAndSettingsTest extends TestCase
{
    protected User $admin;
    protected DataSource $dataSource;

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

        $this->dataSource = DataSource::firstOrCreate(
            ['code' => 'data_gov_mandi'],
            [
                'name' => 'data.gov.in Mandi Prices',
                'provider_class' => \App\Services\DataSources\DataGov\DataGovMarketDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://api.data.gov.in/resource/9ef84268-d588-465a-a308-a864a43d0070',
                'sync_interval_minutes' => 720,
                'is_active' => true,
            ]
        );
    }

    public function test_guests_cannot_access_phase12_admin_screens(): void
    {
        $routes = [
            '/admin/dashboard',
            '/admin/feature-flags',
            '/admin/settings',
            '/admin/data-quality',
            '/admin/unresolved-mappings',
            '/admin/audit-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/admin/login');
        }
    }

    public function test_admin_can_view_operational_dashboard_analytics(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('APMC Mandi Coverage');
        $response->assertSee('Feed Ingestion');
        $response->assertSee('Forecasting Health');
        $response->assertSee('Upstream Ingestion Feeds');
        $response->assertSee('Sync Prices Now');
    }

    public function test_admin_can_view_and_toggle_feature_flag(): void
    {
        $flag = FeatureFlag::updateOrCreate(
            ['key' => 'test_feature_flag_toggle'],
            [
                'name' => 'Test Toggle Feature',
                'description' => 'Temporary test flag for unit verification.',
                'is_enabled' => false,
            ]
        );
        Cache::forget("feature_flag_{$flag->key}");

        // View index
        $indexResponse = $this->actingAs($this->admin)->get('/admin/feature-flags');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Feature Flags Center');
        $indexResponse->assertSee('test_feature_flag_toggle');

        // Toggle to true
        $toggleResponse = $this->actingAs($this->admin)
            ->post("/admin/feature-flags/{$flag->id}/toggle");

        $toggleResponse->assertRedirect();
        $flag->refresh();
        $this->assertTrue((bool) $flag->is_enabled);
        $this->assertTrue(FeatureFlag::isEnabled('test_feature_flag_toggle'));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'feature_flag.toggle',
            'entity_type' => 'FeatureFlag',
            'entity_id' => $flag->id,
        ]);
    }

    public function test_admin_can_update_feature_flag_details(): void
    {
        $flag = FeatureFlag::updateOrCreate(
            ['key' => 'test_feature_flag_edit'],
            [
                'name' => 'Original Flag Name',
                'description' => 'Original description.',
                'is_enabled' => true,
            ]
        );
        Cache::forget("feature_flag_{$flag->key}");

        $response = $this->actingAs($this->admin)->put("/admin/feature-flags/{$flag->id}", [
            'name' => 'Updated Flag Name',
            'description' => 'Updated description text.',
            'is_enabled' => '0',
        ]);

        $response->assertRedirect('/admin/feature-flags');
        $flag->refresh();
        $this->assertEquals('Updated Flag Name', $flag->name);
        $this->assertEquals('Updated description text.', $flag->description);
        $this->assertFalse((bool) $flag->is_enabled);
    }

    public function test_admin_can_view_and_update_system_settings(): void
    {
        $viewResponse = $this->actingAs($this->admin)->get('/admin/settings');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('System Configuration Settings');
        $viewResponse->assertSee('Platform');

        // Update application name
        $randApp = 'Krushi Baandhava Test ' . rand(100, 999);
        $updateResponse = $this->actingAs($this->admin)->post('/admin/settings', [
            'tab' => 'general',
            'settings' => [
                'application_name' => $randApp,
                'forecast_minimum_observations' => 45,
            ],
        ]);

        $updateResponse->assertRedirect('/admin/settings?tab=general');
        $this->assertEquals($randApp, SystemSetting::get('application_name'));
        $this->assertEquals(45, SystemSetting::get('forecast_minimum_observations'));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.bulk_update',
            'entity_type' => 'SystemSetting',
        ]);

        // Restore canonical setting for other test isolation
        SystemSetting::set('application_name', 'Krushi Baandhava', 'string', 'general');
        SystemSetting::set('forecast_minimum_observations', 30, 'integer', 'forecasting');
    }

    public function test_admin_can_inspect_reprocess_and_delete_data_quality_records(): void
    {
        // Create sample rejected raw record
        $raw = MarketPriceRaw::create([
            'data_source_id' => $this->dataSource->id,
            'external_record_id' => 'test_raw_dq_' . rand(1000, 9999),
            'payload' => [
                'State' => 'Karnataka',
                'District' => 'Shivamogga',
                'Market' => 'Shimoga APMC',
                'Commodity' => 'Arecanut',
                'Variety' => 'Bette',
                'Modal_Price' => '45000',
                'Min_Price' => '42000',
                'Max_Price' => '48000',
                'Price_Date' => '23/09/2026',
            ],
            'checksum' => hash('sha256', 'test_checksum_' . rand()),
            'received_at' => now(),
            'processing_status' => 'rejected',
            'error_message' => "Unmapped commodity alias: 'Test-Special-Crop-Reject'. Add mapping in admin.",
        ]);

        // View data quality screen
        $viewResponse = $this->actingAs($this->admin)->get('/admin/data-quality');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Data Quality', false);
        $viewResponse->assertSee('Ingestion Inspector', false);
        $viewResponse->assertSee((string) $raw->id);

        // Reprocess single record
        $reprocessResponse = $this->actingAs($this->admin)->post("/admin/data-quality/reprocess/{$raw->id}");
        $reprocessResponse->assertRedirect();

        // Reprocess batch
        $batchResponse = $this->actingAs($this->admin)->post('/admin/data-quality/reprocess-all');
        $batchResponse->assertRedirect();

        // Delete raw record
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/data-quality/{$raw->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('market_price_raw', ['id' => $raw->id]);
    }

    public function test_admin_can_resolve_unmapped_entities_and_auto_reprocess(): void
    {
        $crop = Crop::first() ?? Crop::create([
            'name' => 'Arecanut',
            'name_kn' => 'ಅಡಿಕೆ',
            'slug' => 'arecanut-test',
            'category' => 'Plantation',
        ]);

        $market = Market::first() ?? Market::create([
            'name' => 'Shimoga APMC',
            'name_kn' => 'ಶಿವಮೊಗ್ಗ ಎಪಿಎಂಸಿ',
            'code' => 'SHM-TEST',
            'district_id' => 1,
            'is_active' => true,
        ]);

        $rawCropName = 'Areca Raw Test ' . rand(100, 999);
        $rawMarketName = 'Mandi Raw Test ' . rand(100, 999);

        // Create rejected records simulating unmapped entities
        $raw1 = MarketPriceRaw::create([
            'data_source_id' => $this->dataSource->id,
            'payload' => ['Commodity' => $rawCropName, 'Market' => 'Shimoga'],
            'checksum' => hash('sha256', 'crop_unmap_' . rand()),
            'received_at' => now(),
            'processing_status' => 'rejected',
            'error_message' => "Unmapped commodity alias: '{$rawCropName}'. Add mapping in admin.",
        ]);

        $raw2 = MarketPriceRaw::create([
            'data_source_id' => $this->dataSource->id,
            'payload' => ['Commodity' => 'Arecanut', 'Market' => $rawMarketName],
            'checksum' => hash('sha256', 'mkt_unmap_' . rand()),
            'received_at' => now(),
            'processing_status' => 'rejected',
            'error_message' => "Unmapped market alias: '{$rawMarketName}'. Add mapping in admin.",
        ]);

        // View unresolved mappings queue
        $viewResponse = $this->actingAs($this->admin)->get('/admin/unresolved-mappings');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Unresolved Entity', false);
        $viewResponse->assertSee('Alias Resolvers', false);
        $viewResponse->assertSee($rawCropName);
        $viewResponse->assertSee($rawMarketName);

        // Resolve commodity alias
        $resolveCropResponse = $this->actingAs($this->admin)->post('/admin/unresolved-mappings/resolve-crop', [
            'data_source_id' => $this->dataSource->id,
            'source_crop_name' => $rawCropName,
            'crop_id' => $crop->id,
        ]);
        $resolveCropResponse->assertRedirect('/admin/unresolved-mappings');
        $this->assertDatabaseHas('crop_source_mappings', [
            'data_source_id' => $this->dataSource->id,
            'source_crop_name' => $rawCropName,
            'crop_id' => $crop->id,
        ]);

        // Resolve mandi alias
        $resolveMarketResponse = $this->actingAs($this->admin)->post('/admin/unresolved-mappings/resolve-market', [
            'data_source_id' => $this->dataSource->id,
            'source_market_name' => $rawMarketName,
            'market_id' => $market->id,
        ]);
        $resolveMarketResponse->assertRedirect('/admin/unresolved-mappings');
        $this->assertDatabaseHas('market_source_mappings', [
            'data_source_id' => $this->dataSource->id,
            'source_market_name' => $rawMarketName,
            'market_id' => $market->id,
        ]);
    }

    public function test_admin_can_view_audit_trail_and_inspect_diffs(): void
    {
        $log = AuditLog::log(
            'test.inspection_event',
            'FeatureFlag',
            1,
            ['is_enabled' => false],
            ['is_enabled' => true]
        );

        $response = $this->actingAs($this->admin)->get('/admin/audit-logs');
        $response->assertStatus(200);
        $response->assertSee('Administrative Audit Trail');
        $response->assertSee('test.inspection_event');

        // Show endpoint returns JSON
        $detailResponse = $this->actingAs($this->admin)->get("/admin/audit-logs/{$log->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonFragment([
            'action' => 'test.inspection_event',
        ]);
    }
}
