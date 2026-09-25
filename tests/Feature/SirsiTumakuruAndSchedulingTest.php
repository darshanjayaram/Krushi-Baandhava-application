<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SirsiTumakuruAndSchedulingTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_sched_test@krushibaandhava.org'],
            [
                'name' => 'Scheduler Admin',
                'password' => bcrypt('password123'),
                'role' => 'super_admin',
                'preferred_language' => 'kn',
            ]
        );

        // Seed clean Arecanut test prices for all 4 key Karnataka markets
        $today = Carbon::today()->toDateString();
        $defaultDistrictId = District::value('id') ?? 1;
        $uttaraKannadaId = District::where('name', 'like', '%Uttara Kannada%')->value('id') ?? $defaultDistrictId;
        $tumakuruId = District::where('name', 'like', '%Tumakuru%')->value('id') ?? $defaultDistrictId;
        $shivamoggaId = District::where('name', 'like', '%Shivamogga%')->value('id') ?? $defaultDistrictId;
        $davanagereId = District::where('name', 'like', '%Davanagere%')->value('id') ?? $defaultDistrictId;

        $sirsi = Market::firstOrCreate(
            ['code' => 'SIRSI'],
            ['name' => 'Sirsi APMC (TSS)', 'name_kn' => 'ಶಿರಸಿ ಎಪಿಎಂಸಿ (TSS)', 'district_id' => $uttaraKannadaId, 'latitude' => 14.6195, 'longitude' => 74.8354]
        );
        $tumakuru = Market::where('name', 'like', '%Tumakuru%')->where('name', 'like', '%APMC%')->first()
            ?? Market::firstOrCreate(
                ['code' => 'TUMAKURU_APMC'],
                ['name' => 'Tumakuru APMC', 'name_kn' => 'ತುಮಕೂರು ಎಪಿಎಂಸಿ', 'district_id' => $tumakuruId, 'latitude' => 13.3379, 'longitude' => 77.1010]
            );
        $sagar = Market::where('name', 'like', '%Sagar%')->first()
            ?? Market::firstOrCreate(
                ['name' => 'Sagar APMC'],
                ['name_kn' => 'ಸಾಗರ ಎಪಿಎಂಸಿ', 'code' => 'SAGAR', 'district_id' => $shivamoggaId, 'latitude' => 14.1670, 'longitude' => 75.0336]
            );
        $channagiri = Market::where('name', 'like', '%Channagiri%')->first()
            ?? Market::firstOrCreate(
                ['name' => 'Channagiri APMC'],
                ['name_kn' => 'ಚನ್ನಗಿರಿ ಎಪಿಎಂಸಿ', 'code' => 'CHANNAGIRI', 'district_id' => $davanagereId, 'latitude' => 14.0256, 'longitude' => 75.9272]
            );

        $rashi = CropVariety::firstOrCreate(['crop_id' => 1, 'name' => 'Rashi'], ['name_kn' => 'ರಾಶಿ', 'is_active' => true]);
        $chali = CropVariety::firstOrCreate(['crop_id' => 1, 'name' => 'Chali'], ['name_kn' => 'ಚಾಲಿ', 'is_active' => true]);
        $bette = CropVariety::firstOrCreate(['crop_id' => 1, 'name' => 'Bette'], ['name_kn' => 'ಬೆಟ್ಟೆ', 'is_active' => true]);

        MarketPrice::updateOrCreate(
            ['crop_id' => 1, 'market_id' => $sirsi->id, 'variety_id' => $rashi->id, 'price_date' => $today],
            ['district_id' => $sirsi->district_id, 'min_price' => 45000, 'max_price' => 47000, 'modal_price' => 46024, 'data_source_id' => 1, 'unit' => 'Quintal']
        );
        MarketPrice::updateOrCreate(
            ['crop_id' => 1, 'market_id' => $sirsi->id, 'variety_id' => $chali->id, 'price_date' => $today],
            ['district_id' => $sirsi->district_id, 'min_price' => 43000, 'max_price' => 45000, 'modal_price' => 44599, 'data_source_id' => 1, 'unit' => 'Quintal']
        );
        MarketPrice::updateOrCreate(
            ['crop_id' => 1, 'market_id' => $sirsi->id, 'variety_id' => $bette->id, 'price_date' => $today],
            ['district_id' => $sirsi->district_id, 'min_price' => 36000, 'max_price' => 38000, 'modal_price' => 37691, 'data_source_id' => 1, 'unit' => 'Quintal']
        );
        MarketPrice::updateOrCreate(
            ['crop_id' => 1, 'market_id' => $tumakuru->id, 'variety_id' => $rashi->id, 'price_date' => $today],
            ['district_id' => $tumakuru->district_id, 'min_price' => 46000, 'max_price' => 48000, 'modal_price' => 47500, 'data_source_id' => 1, 'unit' => 'Quintal']
        );
        MarketPrice::updateOrCreate(
            ['crop_id' => 1, 'market_id' => $sagar->id, 'variety_id' => $rashi->id, 'price_date' => $today],
            ['district_id' => $sagar->district_id, 'min_price' => 46500, 'max_price' => 48500, 'modal_price' => 47669, 'data_source_id' => 1, 'unit' => 'Quintal']
        );
        MarketPrice::updateOrCreate(
            ['crop_id' => 1, 'market_id' => $channagiri->id, 'variety_id' => $rashi->id, 'price_date' => $today],
            ['district_id' => $channagiri->district_id, 'min_price' => 44500, 'max_price' => 46000, 'modal_price' => 45585, 'data_source_id' => 1, 'unit' => 'Quintal']
        );
    }

    public function test_arecanut_crop_detail_displays_four_markets_including_sirsi_and_tumakuru(): void
    {
        $areca = Crop::where('name', 'like', '%Arecanut%')->orWhere('id', 1)->first();
        $this->assertNotNull($areca);

        $sirsi = Market::where('name', 'like', '%Sirsi%')->first();
        $tumakuru = Market::where('name', 'like', '%Tumakuru%')->where('name', 'like', '%APMC%')->first();
        $sagar = Market::where('name', 'like', '%Sagar%')->first();
        $channagiri = Market::where('name', 'like', '%Channagiri%')->first();

        $this->assertNotNull($sirsi, 'Sirsi market should exist');
        $this->assertNotNull($tumakuru, 'Tumakuru market should exist');

        $response = $this->get("/crop/{$areca->id}");
        $response->assertStatus(200);

        // Check all 4 markets are present in the page
        $content = $response->getContent();
        $this->assertTrue(stripos($content, 'Sirsi') !== false || stripos($content, 'ಶಿರಸಿ') !== false);
        $this->assertTrue(stripos($content, 'Tumakuru') !== false || stripos($content, 'ತುಮಕೂರು') !== false);
        $this->assertTrue(stripos($content, 'Sagar') !== false || stripos($content, 'ಸಾಗರ') !== false);
        $this->assertTrue(stripos($content, 'Channagiri') !== false || stripos($content, 'ಚನ್ನಗಿರಿ') !== false);
    }

    public function test_selecting_sirsi_market_displays_tss_grades(): void
    {
        $areca = Crop::where('name', 'like', '%Arecanut%')->orWhere('id', 1)->first();
        $this->assertNotNull($areca);

        $response = $this->get("/crop/{$areca->id}?market=SIRSI");
        $response->assertStatus(200);

        $content = $response->getContent();
        // Should contain TSS varieties (Rashi, Chali, Bette)
        $this->assertTrue(stripos($content, 'Chali') !== false || stripos($content, 'ಚಾಲಿ') !== false);
        $this->assertTrue(stripos($content, 'Bette') !== false || stripos($content, 'ಬೆಟ್ಟೆ') !== false);
        $this->assertTrue(stripos($content, '46,024') !== false || stripos($content, '46024') !== false);
    }

    public function test_admin_datasources_page_displays_cpanel_cron_assistant(): void
    {
        Cache::forever('scheduler_last_heartbeat', now());

        $response = $this->actingAs($this->admin)->get('/admin/datasources');
        $response->assertStatus(200);

        $response->assertSee('Automated Scheduling & cPanel Cron Assistant', false);
        $response->assertSee('schedule:run');
        $response->assertSee('Copy Command');
        $response->assertSee('Cron Active');
    }

    public function test_datasource_is_due_method_evaluates_schedule(): void
    {
        $ds = DataSource::first();
        $this->assertNotNull($ds);

        // Test weekday vs Sunday
        $ds->sync_days = 'mon_sat';
        $sunday = Carbon::parse('2026-09-27 12:00:00'); // Sunday
        $this->assertFalse($ds->isDue($sunday));

        // Test hourly frequency
        $ds->sync_frequency = 'hourly';
        $ds->last_sync_at = Carbon::now()->subMinutes(65);
        $this->assertTrue($ds->isDue(Carbon::now()));

        $ds->last_sync_at = Carbon::now()->subMinutes(20);
        $this->assertFalse($ds->isDue(Carbon::now()));
    }

    public function test_tss_sirsi_datasource_is_registered_and_syncs_arecanut_prices(): void
    {
        $tssSource = DataSource::where('code', 'tss_sirsi')->first();
        $this->assertNotNull($tssSource, 'tss_sirsi data source must be registered');

        $response = $this->actingAs($this->admin)->get('/admin/datasources');
        $response->assertStatus(200);
        $response->assertSee('TSS Sirsi', false);

        // Test running sync command for tss_sirsi
        $exitCode = \Illuminate\Support\Facades\Artisan::call('krushi:sync-market-prices', [
            'source' => 'tss_sirsi',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        // Verify prices updated with tss_sirsi data source
        $sirsiMarket = Market::where('code', 'KA_APMC_SRS')->orWhere('code', 'SIRSI')->first();
        $this->assertNotNull($sirsiMarket);

        $prices = MarketPrice::where('market_id', $sirsiMarket->id)
            ->where('data_source_id', $tssSource->id)
            ->get();

        $this->assertGreaterThanOrEqual(5, $prices->count(), 'All 5 TSS Sirsi auction varieties should be synced');
    }
}
