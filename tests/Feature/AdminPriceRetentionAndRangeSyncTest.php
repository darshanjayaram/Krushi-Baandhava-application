<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Models\PriceMonthlyStatistic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminPriceRetentionAndRangeSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $farmer;
    protected Crop $crop;
    protected Market $market;
    protected DataSource $dataSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_retention_test@krushibaandhava.org'],
            [
                'name' => 'Retention Admin',
                'password' => bcrypt('secret123'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        $this->farmer = User::firstOrCreate(
            ['email' => 'farmer_retention_test@krushibaandhava.org'],
            [
                'name' => 'Test Farmer',
                'password' => bcrypt('secret123'),
                'role' => User::ROLE_FARMER,
                'is_active' => true,
            ]
        );

        $district = District::first() ?? District::where('is_active', true)->firstOrFail();
        $this->market = Market::first() ?? Market::where('is_active', true)->firstOrFail();
        $this->crop = Crop::first() ?? Crop::where('is_active', true)->firstOrFail();

        $this->dataSource = DataSource::where('code', 'ceda_agmarknet')->first()
            ?? DataSource::create([
                'name' => 'CEDA Agmarknet',
                'code' => 'ceda_agmarknet',
                'driver_class' => \App\Services\DataSources\Ceda\CedaAgmarknetDataProvider::class,
                'base_url' => 'https://api.ceda.ashoka.edu.in/v1',
                'auth_type' => 'bearer',
                'is_active' => true,
            ]);
    }

    public function test_admin_can_view_prices_page_with_archive_breakdown_and_year_month_filters(): void
    {
        // Seed 2 records across different years/months
        $p1 = MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'district_id' => $this->market->district_id,
            'price_date' => '2026-08-15',
            'min_price' => 45000,
            'max_price' => 50000,
            'modal_price' => 48000,
            'data_source_id' => $this->dataSource->id,
        ]);

        $p2 = MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'district_id' => $this->market->district_id,
            'price_date' => '2025-05-10',
            'min_price' => 42000,
            'max_price' => 46000,
            'modal_price' => 44000,
            'data_source_id' => $this->dataSource->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.prices.index'));

        $response->assertStatus(200);
        $response->assertViewHas('archiveBreakdown');
        $response->assertViewHas('availableYears');

        // Filter by Year 2025 & Month 5 for this test crop and market
        $filterResponse = $this->actingAs($this->admin)->get(route('admin.prices.index', ['year' => 2025, 'month' => 5, 'crop_id' => $this->crop->id, 'market_id' => $this->market->id]));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('10 May 2025');

        // Filter by Month 8 (August) for this test crop and market
        $monthResponse = $this->actingAs($this->admin)->get(route('admin.prices.index', ['month' => 8, 'crop_id' => $this->crop->id, 'market_id' => $this->market->id]));
        $monthResponse->assertStatus(200);
        $monthResponse->assertSee('15 Aug 2026');
    }

    public function test_admin_can_trigger_historical_range_sync(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.prices.sync-range'), [
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-10',
            'data_source_id' => $this->dataSource->id,
            'crop_id' => $this->crop->id,
            'update_analytics' => true,
        ]);

        $response->assertRedirect(route('admin.prices.index'));
        $response->assertSessionHas('success');
    }

    public function test_range_sync_validates_dates_and_max_span(): void
    {
        // Rejects to_date before from_date
        $invalidResponse = $this->actingAs($this->admin)->post(route('admin.prices.sync-range'), [
            'from_date' => '2026-09-10',
            'to_date' => '2026-08-10',
        ]);
        $invalidResponse->assertSessionHasErrors(['to_date']);

        // Rejects span exceeding 6 years (2,192 days)
        $exceedResponse = $this->actingAs($this->admin)->post(route('admin.prices.sync-range'), [
            'from_date' => '2018-01-01',
            'to_date' => '2026-01-01',
        ]);
        $exceedResponse->assertSessionHas('error');
    }

    public function test_admin_can_safely_prune_records_by_age_preserving_monthly_statistics(): void
    {
        // 1. Seed an older record (400 days ago) and a fresh record (10 days ago)
        $staleDate = Carbon::today()->subDays(400)->toDateString();
        $freshDate = Carbon::today()->subDays(10)->toDateString();

        $stalePrice = MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'district_id' => $this->market->district_id,
            'price_date' => $staleDate,
            'min_price' => 40000,
            'max_price' => 45000,
            'modal_price' => 43000,
            'data_source_id' => $this->dataSource->id,
        ]);

        $freshPrice = MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'district_id' => $this->market->district_id,
            'price_date' => $freshDate,
            'min_price' => 48000,
            'max_price' => 52000,
            'modal_price' => 50000,
            'data_source_id' => $this->dataSource->id,
        ]);

        // 2. Prune records older than 365 days
        $response = $this->actingAs($this->admin)->post(route('admin.prices.prune'), [
            'strategy' => 'age',
            'older_than_days' => 365,
        ]);

        $response->assertRedirect(route('admin.prices.index'));
        $response->assertSessionHas('success');

        // Stale record should be deleted, fresh record kept
        $this->assertDatabaseMissing('market_prices', ['id' => $stalePrice->id]);
        $this->assertDatabaseHas('market_prices', ['id' => $freshPrice->id]);

        // Safety Guard check: monthly statistics must be preserved for the stale month
        $staleYr = (int) Carbon::parse($staleDate)->year;
        $staleMo = (int) Carbon::parse($staleDate)->month;

        $this->assertDatabaseHas('price_monthly_statistics', [
            'crop_id' => $this->crop->id,
            'year' => $staleYr,
            'month' => $staleMo,
        ]);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'prune_market_prices',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_prune_records_by_specific_period(): void
    {
        $oldPrice = MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'district_id' => $this->market->district_id,
            'price_date' => '2023-03-15',
            'min_price' => 39000,
            'max_price' => 42000,
            'modal_price' => 41000,
            'data_source_id' => $this->dataSource->id,
        ]);

        $recentPrice = MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'district_id' => $this->market->district_id,
            'price_date' => '2026-03-15',
            'min_price' => 48000,
            'max_price' => 52000,
            'modal_price' => 50000,
            'data_source_id' => $this->dataSource->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.prices.prune'), [
            'strategy' => 'period',
            'year' => 2023,
            'month' => 3,
        ]);

        $response->assertRedirect(route('admin.prices.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('market_prices', ['id' => $oldPrice->id]);
        $this->assertDatabaseHas('market_prices', ['id' => $recentPrice->id]);

        // Monthly stats preserved for 2023-03
        $this->assertDatabaseHas('price_monthly_statistics', [
            'crop_id' => $this->crop->id,
            'year' => 2023,
            'month' => 3,
        ]);
    }

    public function test_artisan_prune_command_supports_dry_run_and_execution(): void
    {
        $oldDate = Carbon::today()->subDays(400)->toDateString();

        $oldPrice = MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'district_id' => $this->market->district_id,
            'price_date' => $oldDate,
            'min_price' => 35000,
            'max_price' => 38000,
            'modal_price' => 37000,
            'data_source_id' => $this->dataSource->id,
        ]);

        // Dry-run should not delete
        $this->artisan('krushi:prune-prices --days=365 --dry-run')
            ->expectsOutputToContain('DRY-RUN (Simulation)')
            ->assertSuccessful();

        $this->assertDatabaseHas('market_prices', ['id' => $oldPrice->id]);

        // Live execution should delete and preserve stats
        $this->artisan('krushi:prune-prices --days=365 --force')
            ->expectsOutputToContain('Pruning complete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('market_prices', ['id' => $oldPrice->id]);
        $this->assertDatabaseHas('price_monthly_statistics', [
            'crop_id' => $this->crop->id,
            'year' => (int) Carbon::parse($oldDate)->year,
            'month' => (int) Carbon::parse($oldDate)->month,
        ]);
    }

    public function test_non_admin_cannot_access_or_prune_prices(): void
    {
        // Unauthenticated
        $this->post(route('admin.prices.sync-range'), [])->assertRedirect(route('admin.login'));
        $this->post(route('admin.prices.prune'), [])->assertRedirect(route('admin.login'));

        // Farmer role (web request redirects to admin.login, JSON returns 403)
        $this->actingAs($this->farmer)->post(route('admin.prices.sync-range'), [])->assertRedirect(route('admin.login'));
        $this->actingAs($this->farmer)->post(route('admin.prices.prune'), [])->assertRedirect(route('admin.login'));
        $this->actingAs($this->farmer)->postJson(route('admin.prices.sync-range'), [])->assertForbidden();
        $this->actingAs($this->farmer)->postJson(route('admin.prices.prune'), [])->assertForbidden();
    }

    public function test_admin_single_sync_returns_json_telemetry(): void
    {
        // Seed a rejected record in raw table to verify rejection telemetry
        MarketPriceRaw::create([
            'data_source_id' => $this->dataSource->id,
            'payload' => ['commodity' => 'Sunflower', 'market' => 'Shimoga'],
            'checksum' => hash('sha256', 'test_sunflower_raw'),
            'processing_status' => 'rejected',
            'error_message' => 'Unresolved canonical crop',
            'received_at' => Carbon::now(),
        ]);

        $mockService = $this->createMock(\App\Services\Ingestion\MarketPriceIngestionService::class);
        $mockService->method('ingest')->willReturn([
            'status' => 'success',
            'source_code' => 'ceda_agmarknet',
            'duration_ms' => 150,
            'received' => 15,
            'inserted' => 12,
            'updated' => 0,
            'duplicate' => 0,
            'rejected' => 3,
            'errors' => [],
        ]);
        $this->app->instance(\App\Services\Ingestion\MarketPriceIngestionService::class, $mockService);

        $response = $this->actingAs($this->admin)->postJson(route('admin.prices.sync'), [
            'data_source_id' => $this->dataSource->id,
            'target_date' => Carbon::today()->toDateString(),
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'mode' => 'single',
            'summary' => [
                'received' => 15,
                'inserted' => 12,
                'updated' => 0,
                'duplicate' => 0,
                'rejected' => 3,
            ],
        ]);
        $response->assertJsonStructure([
            'ok',
            'mode',
            'duration_ms',
            'summary' => ['received', 'inserted', 'updated', 'duplicate', 'rejected'],
            'sources',
            'rejections',
            'message',
        ]);
        $this->assertNotEmpty($response->json('rejections'));
    }

    public function test_admin_range_sync_returns_json_telemetry(): void
    {
        MarketPriceRaw::create([
            'data_source_id' => $this->dataSource->id,
            'payload' => ['commodity' => 'Jowar', 'market' => 'Raichur'],
            'checksum' => hash('sha256', 'test_jowar_raw'),
            'processing_status' => 'rejected',
            'error_message' => 'Unresolved canonical crop',
            'received_at' => Carbon::now(),
        ]);

        $mockService = $this->createMock(\App\Services\Ingestion\MarketPriceIngestionService::class);
        $mockService->method('ingest')->willReturn([
            'status' => 'success',
            'source_code' => 'ceda_agmarknet',
            'duration_ms' => 200,
            'received' => 45,
            'inserted' => 38,
            'updated' => 2,
            'duplicate' => 3,
            'rejected' => 2,
            'errors' => [],
        ]);
        $this->app->instance(\App\Services\Ingestion\MarketPriceIngestionService::class, $mockService);

        $response = $this->actingAs($this->admin)->postJson(route('admin.prices.sync-range'), [
            'data_source_id' => $this->dataSource->id,
            'crop_id' => $this->crop->id,
            'from_date' => Carbon::today()->subDays(7)->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
            'update_analytics' => 0,
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'mode' => 'range',
        ]);
        $response->assertJsonStructure([
            'ok',
            'mode',
            'duration_ms',
            'summary' => ['received', 'inserted', 'updated', 'duplicate', 'rejected'],
            'sources',
            'rejections',
            'message',
        ]);
        $this->assertNotEmpty($response->json('rejections'));
    }

    public function test_historical_range_sync_is_strictly_idempotent_with_zero_duplicates(): void
    {
        // 1. Ingest initial records
        $testDate = '2026-08-25';
        MarketPrice::where('crop_id', $this->crop->id)
            ->where('market_id', $this->market->id)
            ->where('price_date', $testDate)
            ->delete();
        AdminPriceRetentionDedupTestProvider::$recordToYield = [
            'state' => 'Karnataka',
            'district' => $this->market->district->name ?? 'Shimoga',
            'market' => $this->market->name,
            'commodity' => $this->crop->name,
            'variety' => 'General',
            'grade' => 'FAQ',
            'arrival_date' => $testDate,
            'min_price' => 45000,
            'max_price' => 50000,
            'modal_price' => 48000,
            'reported_unit' => 'Quintal',
            'arrivals' => 100,
        ];

        AdminPriceRetentionDedupTestProvider::$normalizedRecord = [
            'source_crop' => $this->crop->name,
            'source_variety' => 'General',
            'source_market' => $this->market->name,
            'source_district' => $this->market->district->name ?? 'Shimoga',
            'source_state' => 'Karnataka',
            'price_date' => $testDate,
            'min_price' => 45000.0,
            'max_price' => 50000.0,
            'modal_price' => 48000.0,
            'arrival_quantity' => 100.0,
            'unit' => 'Quintal',
        ];

        $testSource = DataSource::create([
            'name' => 'Dedup Test Source',
            'code' => 'dedup_test_' . uniqid(),
            'provider_class' => AdminPriceRetentionDedupTestProvider::class,
            'base_url' => 'https://api.test-dedup.org',
            'is_active' => true,
        ]);

        $ingestionService = app(\App\Services\Ingestion\MarketPriceIngestionService::class);

        // Run 1: First sync
        $result1 = $ingestionService->ingest($testSource, ['filters' => []]);
        $this->assertEquals(1, $result1['inserted']);
        $this->assertEquals(0, $result1['duplicate']);

        $countAfterFirst = MarketPrice::where('crop_id', $this->crop->id)
            ->where('market_id', $this->market->id)
            ->where('price_date', $testDate)
            ->count();
        $this->assertEquals(1, $countAfterFirst);

        // Run 2: Exact same sync triggered a second time (e.g. admin syncs same month twice)
        $result2 = $ingestionService->ingest($testSource, ['filters' => []]);
        $this->assertEquals(0, $result2['inserted']);
        $this->assertEquals(1, $result2['duplicate']); // Checksum deduplication caught it!

        $countAfterSecond = MarketPrice::where('crop_id', $this->crop->id)
            ->where('market_id', $this->market->id)
            ->where('price_date', $testDate)
            ->count();
        // Row count must NOT increase - strict idempotency
        $this->assertEquals(1, $countAfterSecond);

        // Run 3: Exact same sync triggered with force => true (Force Re-sync & Overwrite)
        $result3 = $ingestionService->ingest($testSource, ['force' => true, 'filters' => []]);
        $this->assertEquals(0, $result3['inserted']);
        $this->assertEquals(1, $result3['updated']); // Overwritten & refreshed!
        $this->assertEquals(0, $result3['duplicate']); // Deduplication bypassed!

        $countAfterThird = MarketPrice::where('crop_id', $this->crop->id)
            ->where('market_id', $this->market->id)
            ->where('price_date', $testDate)
            ->count();
        $this->assertEquals(1, $countAfterThird);

        // Clean up
        MarketPrice::where('crop_id', $this->crop->id)->where('price_date', $testDate)->delete();
        MarketPriceRaw::where('data_source_id', $testSource->id)->delete();
        $testSource->delete();
    }
}

class AdminPriceRetentionDedupTestProvider implements \App\Services\DataSources\Contracts\MarketDataProviderInterface
{
    public static ?array $recordToYield = null;
    public static ?array $normalizedRecord = null;

    public function __construct(DataSource $dataSource)
    {
    }

    public function fetch(array $filters = []): \Generator
    {
        if (self::$recordToYield) {
            yield self::$recordToYield;
        }
    }

    public function normalize(array $rawRecord): ?array
    {
        return self::$normalizedRecord;
    }

    public function healthCheck(): array
    {
        return [
            'http_status' => 200,
            'response_time_ms' => 10,
            'auth_result' => 'ok',
            'records_found' => 1,
            'detected_fields' => [],
            'status' => 'healthy',
            'error_message' => null,
            'sample_payload' => null,
        ];
    }
}

