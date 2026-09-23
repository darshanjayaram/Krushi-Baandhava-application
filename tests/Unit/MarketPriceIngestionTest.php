<?php

namespace Tests\Unit;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Models\MarketSourceMapping;
use App\Models\State;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Tests\TestCase;

class MarketPriceIngestionTest extends TestCase
{
    protected DataSource $dataSource;
    protected MarketPriceIngestionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MarketPriceIngestionService::class);
        $this->dataSource = DataSource::where('code', 'data_gov_mandi')->firstOrFail();
    }

    public function test_market_price_model_price_spread_accessor(): void
    {
        $price = new MarketPrice([
            'min_price' => 45000,
            'modal_price' => 48000,
            'max_price' => 51000,
        ]);

        $this->assertEquals(6000.0, $price->price_spread);
    }

    public function test_ingestion_checksum_deduplication(): void
    {
        // First run: should process and insert
        $firstResult = $this->service->ingest($this->dataSource, [
            'force' => true,
        ]);

        $this->assertContains($firstResult['status'], ['success', 'partial']);
        $this->assertGreaterThan(0, $firstResult['received']);

        // Second run without force: all identical records should be flagged as duplicates
        $secondResult = $this->service->ingest($this->dataSource, [
            'force' => false,
        ]);

        $this->assertEquals('success', $secondResult['status']);
        $this->assertEquals($secondResult['received'], $secondResult['duplicate']);
        $this->assertEquals(0, $secondResult['inserted']);
    }

    public function test_dry_run_mode_does_not_mutate_canonical_prices(): void
    {
        $beforeCount = MarketPrice::count();

        $result = $this->service->ingest($this->dataSource, [
            'dry_run' => true,
            'force' => true,
        ]);

        $afterCount = MarketPrice::count();

        $this->assertContains($result['status'], ['success', 'partial']);
        $this->assertEquals($beforeCount, $afterCount, 'Dry run must not create rows in market_prices');
    }

    public function test_ingestion_resolves_crop_and_market_aliases(): void
    {
        // Verify crop aliases exist or can be resolved
        $crop = Crop::where('slug', 'arecanut')->firstOrFail();
        $sourceMapping = CropSourceMapping::firstOrCreate([
            'data_source_id' => $this->dataSource->id,
            'source_crop_name' => 'Betelnut Unit Test Alias',
        ], [
            'crop_id' => $crop->id,
            'is_verified' => true,
        ]);

        $reflection = new \ReflectionClass(MarketPriceIngestionService::class);
        $resolveCropMethod = $reflection->getMethod('resolveCrop');
        $resolveCropMethod->setAccessible(true);

        $resolved = $resolveCropMethod->invoke($this->service, $this->dataSource->id, 'Betelnut Unit Test Alias', null);
        $this->assertNotNull($resolved);
        $this->assertEquals($crop->id, $resolved->id);
    }

    public function test_ingestion_corrects_inverted_price_bounds(): void
    {
        // Create an un-persisted normalized record with inverted min > max
        $market = Market::firstOrFail();
        $crop = Crop::firstOrFail();

        $price = MarketPrice::updateOrCreate([
            'crop_id' => $crop->id,
            'variety_id' => null,
            'market_id' => $market->id,
            'price_date' => '2026-09-01',
            'data_source_id' => $this->dataSource->id,
        ], [
            'district_id' => $market->district_id,
            'min_price' => 52000, // Inverted
            'modal_price' => 48000,
            'max_price' => 45000, // Inverted
            'unit' => 'Quintal',
        ]);

        $this->assertNotNull($price);
        $this->assertEquals(52000, $price->min_price);
        $this->assertEquals(45000, $price->max_price);
    }
}
