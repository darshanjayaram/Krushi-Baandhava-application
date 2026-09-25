<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Services\DataSources\Ceda\CedaAgmarknetDataProvider;
use App\Services\DataSources\DataSourceRegistry;
use App\Services\Forecast\ForecastingEngineService;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
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

    public function test_ceda_data_source_and_mappings_exist_in_db(): void
    {
        $dataSource = DataSource::where('code', 'ceda_agmarknet')->first();
        $this->assertNotNull($dataSource, 'CEDA DataSource should be seeded in the database.');
        $this->assertEquals(CedaAgmarknetDataProvider::class, $dataSource->provider_class);

        $paddy = Crop::where('name', 'Paddy')->first();
        if ($paddy) {
            $hasMapping = $paddy->sourceMappings()
                ->where('data_source_id', $dataSource->id)
                ->exists();
            $this->assertTrue($hasMapping, 'Paddy should have CEDA mappings.');
        }
    }

    public function test_ceda_provider_normalizes_records_correctly(): void
    {
        $dataSource = DataSource::where('code', 'ceda_agmarknet')->first();
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

    public function test_ceda_sync_artisan_command_dry_run(): void
    {
        $this->artisan('krushi:sync-ceda-historical', [
            '--crop' => 'Paddy',
            '--days' => 30,
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('Starting CEDA Agmarknet Historical Sync Engine...')
        ->expectsOutputToContain('Running in DRY-RUN mode')
        ->assertSuccessful();
    }

    public function test_ceda_sync_ingests_and_enables_forecasting(): void
    {
        $paddy = Crop::where('name', 'Paddy')->first();
        $this->assertNotNull($paddy);

        // Run sync command for real (in transaction) for Paddy over 45 days
        $this->artisan('krushi:sync-ceda-historical', [
            '--crop' => 'Paddy',
            '--days' => 45,
            '--forecast' => true,
        ])->assertSuccessful();

        // Verify records were inserted into market_prices
        $pricesCount = MarketPrice::where('crop_id', $paddy->id)
            ->where('price_date', '>=', Carbon::today()->subDays(45)->toDateString())
            ->count();

        $this->assertGreaterThan(25, $pricesCount, 'At least 25 historical records should be ingested for Paddy.');

        // Verify forecasting engine now has sufficient data
        $forecastingService = app(ForecastingEngineService::class);
        $forecast = $forecastingService->getForecastsForCrop($paddy->id);

        $this->assertTrue($forecast['is_sufficient'], 'Forecasting should now be sufficient with historical data.');
        $this->assertNotEmpty($forecast['horizons'], 'Forecast horizons should be generated.');
    }
}
