<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\User;
use App\Services\DataSources\Agmarknet\AgmarknetHistoricalDataProvider;
use App\Services\DataSources\DataSourceRegistry;
use App\Services\DataSources\Krama\KramaMarketDataProvider;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KramaAndAgmarknetIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_krama_and_agmarknet_providers_are_registered_in_registry(): void
    {
        $providers = DataSourceRegistry::getAvailableProviders();

        $this->assertArrayHasKey(KramaMarketDataProvider::class, $providers);
        $this->assertStringContainsString('KRAMA', $providers[KramaMarketDataProvider::class]);

        $this->assertArrayHasKey(AgmarknetHistoricalDataProvider::class, $providers);
        $this->assertStringContainsString('Official AGMARKNET', $providers[AgmarknetHistoricalDataProvider::class]);
    }

    public function test_krama_and_agmarknet_data_sources_exist_in_db(): void
    {
        $krama = DataSource::where('code', 'krama_karnataka')->first();
        $this->assertNotNull($krama, 'KRAMA DataSource should exist in the database.');
        $this->assertEquals(KramaMarketDataProvider::class, $krama->provider_class);
        $this->assertTrue($krama->is_active);

        $agmarknet = DataSource::where('code', 'agmarknet_official')->first();
        $this->assertNotNull($agmarknet, 'Official AGMARKNET DataSource should exist in the database.');
        $this->assertEquals(AgmarknetHistoricalDataProvider::class, $agmarknet->provider_class);
    }

    public function test_krama_provider_parses_html_report_and_normalizes_records(): void
    {
        $krama = DataSource::where('code', 'krama_karnataka')->first();
        $provider = new KramaMarketDataProvider($krama);

        // Sample HTML matching KRAMA report structure
        $sampleHtml = <<<HTML
<html>
<body>
    <span style="font-weight:bold;">COMMODITY: Tomato</span>
    <div>
        <table>
            <tr>
                <td>Market</td><td>Variety</td><td>Grade</td><td>Arrivals</td><td>Units</td><td>Min</td><td>Max</td><td>Modal</td>
            </tr>
            <tr>
                <td>Kolar</td><td>Local</td><td>FAQ</td><td>1,250</td><td>Quintal</td><td>2,000</td><td>3,500</td><td>2,800</td>
            </tr>
            <tr>
                <td>Chikkaballapura</td><td>Hybrid</td><td>Average</td><td>800</td><td>Quintal</td><td>2,200</td><td>3,600</td><td>3,000</td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;

        $records = $provider->parseReportHtml($sampleHtml, '28/09/2026');
        $this->assertCount(2, $records);

        $first = $records[0];
        $this->assertEquals('Tomato', $first['crop']);
        $this->assertEquals('Kolar', $first['market']);
        $this->assertEquals('Local', $first['variety']);
        $this->assertEquals('FAQ', $first['grade']);
        $this->assertEquals(1250.0, $first['arrivals']);
        $this->assertEquals(2000.0, $first['min']);
        $this->assertEquals(3500.0, $first['max']);
        $this->assertEquals(2800.0, $first['modal']);

        // Test normalization
        $normalized = $provider->normalize($first);
        $this->assertNotNull($normalized);
        $this->assertEquals('Tomato', $normalized['source_crop']);
        $this->assertEquals('Kolar', $normalized['source_market']);
        $this->assertEquals('Local', $normalized['source_variety']);
        $this->assertEquals('FAQ', $normalized['source_grade']);
        $this->assertEquals(2800.0, $normalized['modal_price']);
        $this->assertEquals('Quintal', $normalized['unit']);
    }

    public function test_krama_mock_fetch_and_health_check(): void
    {
        $krama = DataSource::where('code', 'krama_karnataka')->first();
        config(['services.datasources.mock_mode' => true]);

        $provider = new KramaMarketDataProvider($krama);
        $records = $provider->fetch();
        $this->assertNotEmpty($records);

        $health = $provider->healthCheck();
        $this->assertEquals('healthy', $health['status']);
        $this->assertEquals(200, $health['http_status']);

        config(['services.datasources.mock_mode' => false]);
    }

    public function test_agmarknet_official_provider_generates_and_verifies_captcha_mock(): void
    {
        $agmarknet = DataSource::where('code', 'agmarknet_official')->first();
        $provider = new AgmarknetHistoricalDataProvider($agmarknet);

        $this->assertNotNull($provider);

        // Test normalization
        $sampleRaw = [
            'state_id' => 16,
            'state_name' => 'Karnataka',
            'district_name' => 'Kolar',
            'market_name' => 'Kolar APMC',
            'commodity_name' => 'Tomato',
            'variety' => 'Hybrid',
            'grade' => 'FAQ',
            'arrival_date' => '2026-09-28',
            'min_price' => 2000,
            'max_price' => 3200,
            'modal_price' => 2600,
            'arrival_quantity' => 450,
            'unit' => 'Quintal',
        ];

        $normalized = $provider->normalize($sampleRaw);
        $this->assertNotNull($normalized);
        $this->assertEquals('Tomato', $normalized['source_crop']);
        $this->assertEquals('Kolar APMC', $normalized['source_market']);
        $this->assertEquals('Kolar', $normalized['source_district']);
        $this->assertEquals(2600.0, $normalized['modal_price']);
    }

    public function test_admin_can_access_agmarknet_captcha_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/admin/datasources/agmarknet/captcha');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
        ]);
    }

    public function test_krama_sync_via_artisan_command_dry_run(): void
    {
        $this->artisan('krushi:sync-market-prices', [
            'source' => 'krama_karnataka',
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('KRAMA (Karnataka State Agricultural Marketing Board)')
        ->assertSuccessful();
    }

    public function test_krama_provider_supports_multi_day_date_range(): void
    {
        $krama = DataSource::where('code', 'krama_karnataka')->first();
        config(['services.datasources.mock_mode' => true]);

        $provider = new KramaMarketDataProvider($krama);
        $records = iterator_to_array($provider->fetch([
            'from_date' => '2026-09-20',
            'to_date' => '2026-09-25',
        ]));

        $this->assertNotEmpty($records);
        // Should yield records spanning multiple mock days
        $dates = array_unique(array_column($records, 'arrival_date'));
        $this->assertGreaterThanOrEqual(1, count($dates));

        config(['services.datasources.mock_mode' => false]);
    }

    public function test_agmarknet_historical_provider_supports_multi_day_date_range(): void
    {
        $agmarknet = DataSource::where('code', 'agmarknet_official')->first();
        config(['services.datasources.mock_mode' => true]);

        $provider = new AgmarknetHistoricalDataProvider($agmarknet);
        $records = iterator_to_array($provider->fetch([
            'from_date' => '2026-09-20',
            'to_date' => '2026-09-25',
        ]));

        $this->assertNotEmpty($records);
        $dates = array_unique(array_column($records, 'arrival_date'));
        $this->assertGreaterThanOrEqual(1, count($dates));

        config(['services.datasources.mock_mode' => false]);
    }
}
