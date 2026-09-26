<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Models\MarketPriceRaw;
use App\Models\User;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminDataSourceSyncConsoleTest extends TestCase
{
    use DatabaseTransactions;

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
            ['code' => 'test_sync_console_source'],
            [
                'name' => 'Test Console Mandi Feed',
                'provider_class' => \App\Services\DataSources\DataGov\DataGovMarketDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://api.example.com/v1',
                'endpoint' => 'rates',
                'auth_type' => 'api_key',
                'is_active' => true,
                'sync_frequency' => 'daily',
                'sync_time' => '17:30:00',
            ]
        );
    }

    public function test_admin_can_view_datasources_page_with_canonical_crops_and_sync_console(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/datasources');

        $response->assertStatus(200);
        $response->assertSee('Run Ingestion Sync');
        $response->assertSee('Ingested Crops Breakdown');
        $response->assertSee('Quick Map & Retry', false);
    }

    public function test_trigger_sync_backward_compatibility_redirects_for_regular_http_post(): void
    {
        // Mock MarketPriceIngestionService to avoid live HTTP government API calls
        $mockService = $this->createMock(MarketPriceIngestionService::class);
        $mockService->expects($this->once())
            ->method('ingest')
            ->willReturn([
                'status' => 'success',
                'received' => 10,
                'inserted' => 8,
                'updated' => 2,
                'duplicate' => 0,
                'rejected' => 0,
                'skipped' => 0,
                'duration_ms' => 120,
                'crops_breakdown' => [],
                'errors' => [],
            ]);

        $this->app->instance(MarketPriceIngestionService::class, $mockService);

        $response = $this->actingAs($this->admin)->post("/admin/datasources/{$this->dataSource->id}/trigger-sync");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_trigger_sync_via_ajax_and_receive_kpis_and_crop_breakdown(): void
    {
        $mockService = $this->createMock(MarketPriceIngestionService::class);
        $mockService->expects($this->once())
            ->method('ingest')
            ->willReturn([
                'status' => 'partial',
                'received' => 25,
                'inserted' => 18,
                'updated' => 5,
                'duplicate' => 0,
                'rejected' => 2,
                'skipped' => 0,
                'duration_ms' => 350,
                'crops_breakdown' => [
                    [
                        'raw_name' => 'Cotton',
                        'crop_name' => 'Cotton',
                        'crop_id' => 1,
                        'photo_url' => '/images/cotton.jpg',
                        'received' => 20,
                        'inserted' => 18,
                        'updated' => 2,
                        'duplicate' => 0,
                        'rejected' => 0,
                        'skipped' => 0,
                        'status' => 'synced',
                        'rejection_reasons' => [],
                    ],
                    [
                        'raw_name' => 'Toria Seed',
                        'crop_name' => 'Toria Seed',
                        'crop_id' => null,
                        'photo_url' => null,
                        'received' => 5,
                        'inserted' => 0,
                        'updated' => 0,
                        'duplicate' => 0,
                        'rejected' => 2,
                        'skipped' => 0,
                        'status' => 'failed',
                        'rejection_reasons' => ["Unmapped commodity alias: 'Toria Seed'. Add mapping in admin."],
                    ],
                ],
                'errors' => ["Unmapped commodity alias: 'Toria Seed'. Add mapping in admin."],
            ]);

        $this->app->instance(MarketPriceIngestionService::class, $mockService);

        $response = $this->actingAs($this->admin)->postJson("/admin/datasources/{$this->dataSource->id}/trigger-sync");

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'status' => 'partial',
            'received' => 25,
            'inserted' => 18,
            'updated' => 5,
            'rejected' => 2,
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('crops', $data);
        $this->assertCount(2, $data['crops']);
        $this->assertEquals('Cotton', $data['crops'][0]['crop_name']);
        $this->assertEquals('synced', $data['crops'][0]['status']);
        $this->assertEquals('Toria Seed', $data['crops'][1]['raw_name']);
        $this->assertEquals('failed', $data['crops'][1]['status']);
    }

    public function test_admin_can_quick_map_and_retry_crop_sync(): void
    {
        // 1. Obtain an existing canonical crop
        $crop = Crop::where('is_active', true)->firstOrFail();

        // 2. Create rejected raw record in market_price_raw
        MarketPriceRaw::create([
            'data_source_id' => $this->dataSource->id,
            'payload' => [
                'state' => 'Karnataka',
                'district' => 'Dharwad',
                'market' => 'Hubli',
                'commodity' => 'Toria Seed',
                'variety' => 'Common',
                'min_price' => 5200,
                'max_price' => 5600,
                'modal_price' => 5400,
                'arrival_date' => Carbon::today()->format('Y-m-d'),
            ],
            'checksum' => hash('sha256', 'test_toria_retry_raw_' . microtime(true)),
            'processing_status' => 'rejected',
            'error_message' => "Unmapped commodity alias: 'Toria Seed'. Add mapping in admin.",
        ]);

        // 3. Post to retry-crop with crop_id mapping
        $response = $this->actingAs($this->admin)->postJson(
            "/admin/datasources/{$this->dataSource->id}/retry-crop",
            [
                'commodity_name' => 'Toria Seed',
                'crop_id' => $crop->id,
            ]
        );

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals('Toria Seed', $json['commodity_name']);
        $this->assertEquals($crop->id, $json['crop_id']);

        // Verify mapping was saved in crop_source_mappings
        $this->assertDatabaseHas('crop_source_mappings', [
            'data_source_id' => $this->dataSource->id,
            'source_crop_name' => 'Toria Seed',
            'crop_id' => $crop->id,
            'is_verified' => true,
        ]);
    }

    public function test_admin_can_trigger_batch_retry_all_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/admin/datasources/{$this->dataSource->id}/retry-all");

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
        ]);
        $response->assertJsonStructure([
            'ok',
            'processed',
            'still_rejected',
            'total',
            'message',
        ]);
    }

    public function test_admin_can_trigger_sync_all_active_sources_for_today(): void
    {
        $mockService = $this->createMock(MarketPriceIngestionService::class);
        $mockService->method('ingest')
            ->willReturn([
                'status' => 'success',
                'received' => 12,
                'inserted' => 10,
                'updated' => 2,
                'duplicate' => 0,
                'rejected' => 0,
                'duration_ms' => 95,
                'crops_breakdown' => [
                    [
                        'raw_name' => 'Arecanut',
                        'crop_name' => 'Arecanut',
                        'crop_id' => 1,
                        'photo_url' => null,
                        'received' => 12,
                        'inserted' => 10,
                        'updated' => 2,
                        'duplicate' => 0,
                        'rejected' => 0,
                        'status' => 'synced',
                    ],
                ],
                'errors' => [],
            ]);

        $this->app->instance(MarketPriceIngestionService::class, $mockService);

        $response = $this->actingAs($this->admin)->postJson('/admin/datasources/sync-all');

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'target_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('sources', $data);
        $this->assertArrayHasKey('crops', $data);
    }

    public function test_admin_can_update_schedule_timings_via_form_post(): void
    {
        $payload = [
            'morning_time' => '07:15',
            'evening_time' => '19:45',
            'afternoon_time' => '13:30',
            'enable_hourly' => '1',
            'operating_days' => 'mon_sat',
            'apply_to_sources' => '1',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/datasources/update-schedule-timings', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify SystemSettings updated
        $this->assertEquals('07:15', \App\Models\SystemSetting::get('cron_market_morning_time'));
        $this->assertEquals('19:45', \App\Models\SystemSetting::get('cron_market_evening_time'));
        $this->assertEquals('13:30', \App\Models\SystemSetting::get('cron_market_afternoon_time'));
        $this->assertTrue(\App\Models\SystemSetting::get('cron_market_enable_hourly'));
        $this->assertEquals('mon_sat', \App\Models\SystemSetting::get('cron_market_operating_days'));

        // Verify active DataSource updated
        $this->dataSource->refresh();
        $this->assertStringContainsString('07:15', $this->dataSource->sync_time);
        $this->assertStringContainsString('19:45', $this->dataSource->sync_time);
        $this->assertEquals('mon_sat', $this->dataSource->sync_days);
    }

    public function test_admin_can_update_schedule_timings_via_ajax(): void
    {
        $payload = [
            'morning_time' => '05:45',
            'evening_time' => '18:15',
            'afternoon_time' => null,
            'enable_hourly' => false,
            'operating_days' => 'all',
            'apply_to_sources' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson('/admin/datasources/update-schedule-timings', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'timings' => [
                'morning_time' => '05:45',
                'evening_time' => '18:15',
                'operating_days' => 'all',
            ],
        ]);

        $this->assertEquals('05:45', \App\Models\SystemSetting::get('cron_market_morning_time'));
        $this->assertEquals('18:15', \App\Models\SystemSetting::get('cron_market_evening_time'));
        $this->assertEquals('all', \App\Models\SystemSetting::get('cron_market_operating_days'));
    }
}
