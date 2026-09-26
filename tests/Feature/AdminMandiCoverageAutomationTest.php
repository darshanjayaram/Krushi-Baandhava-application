<?php

namespace Tests\Feature;

use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketSourceMapping;
use App\Models\User;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Database\Seeders\KarnatakaMandiAliasSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminMandiCoverageAutomationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected DataSource $dataSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_coverage_test@krushibaandhava.org'],
            [
                'name' => 'Coverage Admin',
                'password' => bcrypt('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        $this->dataSource = DataSource::where('code', 'data_gov_mandi')->firstOrFail();
    }

    public function test_karnataka_mandi_alias_seeder_populates_mappings_for_all_karnataka_mandis(): void
    {
        $karnatakaMarketsCount = Market::karnataka()->count();
        $this->assertGreaterThan(40, $karnatakaMarketsCount);

        // Verify key mandis have source mappings
        $byadgi = Market::where('code', 'KA_APMC_BYD')->first();
        $this->assertNotNull($byadgi);
        $this->assertTrue(
            MarketSourceMapping::where('market_id', $byadgi->id)->exists(),
            'Byadgi APMC should have source mappings'
        );

        $kalaburagi = Market::where('code', 'KA_APMC_KLB')->first();
        $this->assertNotNull($kalaburagi);
        $this->assertTrue(
            MarketSourceMapping::where('market_id', $kalaburagi->id)->exists(),
            'Kalaburagi APMC should have source mappings'
        );

        $vijayapura = Market::where('code', 'KA_APMC_VJP')->first();
        $this->assertNotNull($vijayapura);
        $this->assertTrue(
            MarketSourceMapping::where('market_id', $vijayapura->id)->exists(),
            'Vijayapura APMC should have source mappings'
        );
    }

    public function test_market_price_ingestion_service_resolves_market_with_caching_and_intelligent_matching(): void
    {
        $service = app(MarketPriceIngestionService::class);

        // 1. Resolve via direct alias mapping
        $resolvedByadgi = $service->resolveMarket($this->dataSource->id, 'Byadagi', 'Haveri');
        $this->assertNotNull($resolvedByadgi);
        $this->assertEquals('KA_APMC_BYD', $resolvedByadgi->code);

        // 2. Resolve via second call - should hit in-memory cache
        $cachedByadgi = $service->resolveMarket($this->dataSource->id, 'Byadagi', 'Haveri');
        $this->assertSame($resolvedByadgi->id, $cachedByadgi->id);

        // 3. Resolve historical colonial spelling
        $resolvedGulbarga = $service->resolveMarket($this->dataSource->id, 'Gulbarga', 'Kalaburagi');
        $this->assertNotNull($resolvedGulbarga);
        $this->assertEquals('KA_APMC_KLB', $resolvedGulbarga->code);

        // 4. Resolve Hospet spelling
        $resolvedHospet = $service->resolveMarket($this->dataSource->id, 'Hospet', 'Vijayanagara');
        $this->assertNotNull($resolvedHospet);
        $this->assertEquals('KA_APMC_HSP', $resolvedHospet->code);
    }

    public function test_admin_dashboard_displays_dual_horizon_coverage_and_slide_over_drawer(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats');
        $response->assertViewHas('mandiNetworkStats');

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('weekly_coverage_percent', $stats);
        $this->assertArrayHasKey('weekly_reporting_markets_count', $stats);
        $this->assertArrayHasKey('market_coverage_percent', $stats);
        $this->assertArrayHasKey('reporting_markets_count', $stats);

        // Verify weekly coverage is high (should be >= 80% with comprehensive seeder)
        $this->assertGreaterThanOrEqual(80, $stats['weekly_coverage_percent']);

        $mandiStats = $response->viewData('mandiNetworkStats');
        $this->assertEquals(Market::karnataka()->count(), $mandiStats['total']);
        $this->assertNotEmpty($mandiStats['markets']);

        // Check view elements
        $response->assertSee('APMC Mandi Coverage');
        $response->assertSee('7-Day Active Network');
        $response->assertSee("Today's Live Ingestion", false);
        $response->assertSee('Inspect Mandi Network');
        $response->assertSee('Karnataka APMC Mandi Network');
        $response->assertSee('Real-time arrival surveillance');
    }
}
