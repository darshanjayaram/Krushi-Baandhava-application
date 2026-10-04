<?php

namespace Tests\Unit;

use App\Models\DataSource;
use App\Models\DataSourceCredential;
use App\Services\DataSources\CoconutBoard\CoconutBoardDataProvider;
use App\Services\DataSources\CoffeeBoard\CoffeeBoardDataProvider;
use App\Services\DataSources\Krama\KramaMarketDataProvider;
use App\Services\DataSources\DataSourceRegistry;
use Tests\TestCase;

class DataSourceProviderTest extends TestCase
{
    public function test_registry_resolves_krama_provider(): void
    {
        $ds = new DataSource([
            'name' => 'KRAMA Karnataka',
            'code' => 'krama_karnataka',
            'provider_class' => KramaMarketDataProvider::class,
            'base_url' => 'https://krama.karnataka.gov.in',
        ]);

        $provider = DataSourceRegistry::make($ds);

        $this->assertInstanceOf(KramaMarketDataProvider::class, $provider);
    }

    public function test_krama_provider_mock_fetch_and_normalize(): void
    {
        $ds = new DataSource([
            'name' => 'KRAMA Karnataka',
            'code' => 'krama_karnataka',
            'provider_class' => KramaMarketDataProvider::class,
            'base_url' => 'https://krama.karnataka.gov.in',
        ]);

        $provider = new KramaMarketDataProvider($ds);

        $records = iterator_to_array($provider->fetch());
        $this->assertNotEmpty($records);

        $sample = $records[0];
        $this->assertArrayHasKey('crop', $sample);
        $this->assertArrayHasKey('market', $sample);

        $normalized = $provider->normalize($sample);
        $this->assertNotNull($normalized);
        $this->assertEquals('Arecanut', $normalized['source_crop']);
        $this->assertEquals('CHANNAGIRI', $normalized['source_market']);
        $this->assertGreaterThan(0, $normalized['modal_price']);
        $this->assertEquals('Quintal', $normalized['unit']);
    }

    public function test_krama_provider_health_check_in_mock_mode(): void
    {
        $ds = new DataSource([
            'name' => 'KRAMA Karnataka',
            'code' => 'krama_karnataka',
            'provider_class' => KramaMarketDataProvider::class,
            'base_url' => 'https://krama.karnataka.gov.in',
        ]);

        $provider = new KramaMarketDataProvider($ds);
        $health = $provider->healthCheck();

        $this->assertEquals(200, $health['http_status']);
        $this->assertEquals('healthy', $health['status']);
        $this->assertGreaterThan(0, $health['records_found']);
    }

    public function test_coffee_board_normalizes_50kg_bag_to_quintal(): void
    {
        $ds = new DataSource([
            'name' => 'Coffee Board of India',
            'code' => 'coffee_board',
            'provider_class' => CoffeeBoardDataProvider::class,
            'base_url' => 'https://coffeeboard.gov.in',
        ]);

        $provider = new CoffeeBoardDataProvider($ds);
        $record = [
            'variety' => 'Arabica Cherry',
            'min_price_50kg' => '10000',
            'max_price_50kg' => '12000',
            'location' => 'Chikkamagaluru',
        ];

        $normalized = $provider->normalize($record);

        $this->assertNotNull($normalized);
        $this->assertEquals('Coffee', $normalized['source_crop']);
        $this->assertEquals('Arabica Cherry', $normalized['source_variety']);
        $this->assertEquals(20000, $normalized['min_price']); // 10000 * 2
        $this->assertEquals(24000, $normalized['max_price']); // 12000 * 2
        $this->assertEquals(22000, $normalized['modal_price']);
    }

    public function test_coconut_board_normalizes_copra_and_coconut(): void
    {
        $ds = new DataSource([
            'name' => 'Coconut Development Board',
            'code' => 'coconut_board',
            'provider_class' => CoconutBoardDataProvider::class,
            'base_url' => 'https://coconutboard.gov.in',
        ]);

        $provider = new CoconutBoardDataProvider($ds);
        $record = [
            'commodity' => 'Copra',
            'grade' => 'Milling',
            'center' => 'Arsikere',
            'district' => 'Hassan',
            'min_price' => '12000',
            'max_price' => '13000',
            'unit' => 'Quintal',
        ];

        $normalized = $provider->normalize($record);

        $this->assertNotNull($normalized);
        $this->assertEquals('Copra', $normalized['source_crop']);
        $this->assertEquals('Quintal', $normalized['unit']);
        $this->assertEquals(12500, $normalized['modal_price']);
    }
}
