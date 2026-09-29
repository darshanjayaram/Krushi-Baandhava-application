<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\DataSource;
use App\Services\DataSources\Ceda\CedaAgmarknetDataProvider;
use App\Services\DataSources\DataSourceRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CedaAgmarknetIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ceda_provider_is_registered_in_registry(): void
    {
        $providers = DataSourceRegistry::getAvailableProviders();
        $this->assertArrayHasKey(CedaAgmarknetDataProvider::class, $providers);
        $this->assertStringContainsString('CEDA Agmarknet Provider', $providers[CedaAgmarknetDataProvider::class]);
    }

    public function test_ceda_provider_normalizes_records_correctly(): void
    {
        $dataSource = new DataSource([
            'code' => 'ceda_agmarknet',
            'name' => 'CEDA Agmarknet',
            'provider_class' => CedaAgmarknetDataProvider::class,
        ]);
        $provider = new CedaAgmarknetDataProvider($dataSource);

        $sampleRecord = [
            'date' => '2024-11-26T00:00:00.000Z',
            'commodity_id' => 2,
            'census_state_id' => 29,
            'census_district_id' => 571,
            'market_id' => 784,
            'min_price' => 2300,
            'max_price' => 2850,
            'modal_price' => 2500,
            'quantity' => 120.5,
        ];

        $normalized = $provider->normalize($sampleRecord);

        $this->assertNotNull($normalized);
        $this->assertEquals('Paddy', $normalized['source_crop']);
        $this->assertEquals('Tumkur', $normalized['source_district']);
        $this->assertEquals('2024-11-26', $normalized['price_date']);
        $this->assertEquals(2300.0, $normalized['min_price']);
        $this->assertEquals(2850.0, $normalized['max_price']);
        $this->assertEquals(2500.0, $normalized['modal_price']);
        $this->assertEquals(120.5, $normalized['arrival_quantity']);
        $this->assertEquals('Quintal', $normalized['unit']);
    }

    public function test_legacy_ceda_sync_artisan_command_delegates_to_agmarknet_official(): void
    {
        $this->artisan('krushi:sync-ceda-historical', [
            '--crop' => 'Paddy',
            '--days' => 14,
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('Notice: CEDA Agmarknet has been upgraded to Official AGMARKNET')
        ->expectsOutputToContain('Starting Official AGMARKNET Historical Sync Engine')
        ->assertSuccessful();
    }

    public function test_official_agmarknet_sync_artisan_command_dry_run(): void
    {
        $this->artisan('krushi:sync-agmarknet-historical', [
            '--crop' => 'Paddy',
            '--days' => 30,
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('Starting Official AGMARKNET Historical Sync Engine')
        ->expectsOutputToContain('Running in DRY-RUN mode')
        ->assertSuccessful();
    }
}
