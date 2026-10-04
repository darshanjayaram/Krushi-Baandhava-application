<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\DataSource;
use App\Services\DataSources\Agmarknet\AgmarknetHistoricalDataProvider;
use App\Services\DataSources\DataSourceRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CedaAgmarknetIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_agmarknet_historical_provider_is_registered_in_registry(): void
    {
        $providers = DataSourceRegistry::getAvailableProviders();
        $this->assertArrayHasKey(AgmarknetHistoricalDataProvider::class, $providers);
        $this->assertStringContainsString('Official AGMARKNET Provider', $providers[AgmarknetHistoricalDataProvider::class]);
    }

    public function test_agmarknet_historical_provider_normalizes_records_correctly(): void
    {
        $dataSource = DataSource::where('code', 'agmarknet_official')->first() ?? new DataSource([
            'code' => 'agmarknet_official',
            'name' => 'Agmarknet Official',
            'provider_class' => AgmarknetHistoricalDataProvider::class,
        ]);
        $provider = new AgmarknetHistoricalDataProvider($dataSource);

        $sampleRecord = [
            'Commodity' => 'Paddy',
            'District' => 'Tumkur',
            'Market' => 'Tumkur',
            'Arrival_Date' => '26/11/2024',
            'Min_Price' => 2300,
            'Max_Price' => 2850,
            'Modal_Price' => 2500,
            'Arrival_Quantity' => 120.5,
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
