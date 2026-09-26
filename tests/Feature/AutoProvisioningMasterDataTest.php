<?php

namespace Tests\Feature;

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
use Illuminate\Support\Str;
use Tests\TestCase;

class AutoProvisioningMasterDataTest extends TestCase
{
    protected DataSource $dataSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataSource = DataSource::firstOrCreate(
            ['code' => 'test_data_gov'],
            [
                'name' => 'Test Ingestion Provider',
                'provider_class' => \App\Services\DataSources\DataGov\DataGovMarketDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://api.data.gov.in/resource',
                'is_active' => true,
            ]
        );
    }

    public function test_ingestion_service_auto_provisions_unmapped_crop_and_market_from_api_payload(): void
    {
        $uniqueId = rand(1000, 9999);
        $rawCommodity = "UniqueTestCrop{$uniqueId}(Grain)";
        $cleanExpectedCrop = "UniqueTestCrop{$uniqueId}";
        $rawMarket = "UniqueMandi{$uniqueId}";
        $rawDistrict = "UniqueDistrict{$uniqueId}";
        $rawVariety = "GradeA{$uniqueId}";

        $rawRecord = [
            'state' => 'Karnataka',
            'district' => $rawDistrict,
            'market' => $rawMarket,
            'commodity' => $rawCommodity,
            'variety' => $rawVariety,
            'arrival_date' => Carbon::today()->format('d/m/Y'),
            'min_price' => '2200',
            'max_price' => '2600',
            'modal_price' => '2450',
            'arrival_quantity' => '150',
        ];

        // Create an anonymous mock provider returning our single raw record
        $mockProvider = new class($this->dataSource, $rawRecord) extends \App\Services\DataSources\DataGov\DataGovMarketDataProvider {
            protected array $testRecord;

            public function __construct(DataSource $ds, array $record)
            {
                parent::__construct($ds);
                $this->testRecord = $record;
            }

            public function fetch(array $filters = []): iterable
            {
                return [$this->testRecord];
            }
        };

        \App\Services\DataSources\DataSourceRegistry::register('test_data_gov', fn () => $mockProvider);

        /** @var MarketPriceIngestionService $service */
        $service = app(MarketPriceIngestionService::class);
        $result = $service->ingest('test_data_gov', ['force' => true]);

        // 1. Verify sync succeeded with 1 insert and 0 rejections
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(0, $result['rejected']);

        // 2. Verify Crop was automatically provisioned
        $crop = Crop::where('name', $cleanExpectedCrop)->first();
        $this->assertNotNull($crop, "Auto-provisioned crop '{$cleanExpectedCrop}' should exist in crops table.");
        $this->assertEquals(Str::slug($cleanExpectedCrop), $crop->slug);

        // 3. Verify Variety was automatically provisioned
        $variety = CropVariety::where('crop_id', $crop->id)->where('name', $rawVariety)->first();
        $this->assertNotNull($variety, "Auto-provisioned variety '{$rawVariety}' should exist.");

        // 4. Verify Market and District were automatically provisioned
        $market = Market::where('name', "{$rawMarket} APMC")->first();
        $this->assertNotNull($market, "Auto-provisioned market '{$rawMarket} APMC' should exist.");
        $this->assertEquals('APMC Mandi', $market->market_type);

        $district = District::where('name', $rawDistrict)->first();
        $this->assertNotNull($district, "Auto-provisioned district '{$rawDistrict}' should exist.");
        $this->assertEquals($district->id, $market->district_id);

        // 5. Verify source mappings were created
        $cropMapping = CropSourceMapping::where('source_crop_name', $rawCommodity)->first();
        $this->assertNotNull($cropMapping);
        $this->assertEquals($crop->id, $cropMapping->crop_id);

        $marketMapping = MarketSourceMapping::where('source_market_name', $rawMarket)->first();
        $this->assertNotNull($marketMapping);
        $this->assertEquals($market->id, $marketMapping->market_id);

        // 6. Verify MarketPrice record was ingested
        $price = MarketPrice::where('crop_id', $crop->id)->where('market_id', $market->id)->first();
        $this->assertNotNull($price);
        $this->assertEquals(2450.0, (float) $price->modal_price);

        // Clean up
        MarketPrice::where('crop_id', $crop->id)->delete();
        MarketPriceRaw::where('payload', 'like', "%{$rawCommodity}%")->delete();
        CropSourceMapping::where('source_crop_name', $rawCommodity)->delete();
        MarketSourceMapping::where('source_market_name', $rawMarket)->delete();
        CropVariety::where('crop_id', $crop->id)->delete();
        $crop->delete();
        $market->delete();
        $district->delete();
    }

    public function test_repeated_sync_does_not_duplicate_crops_markets_or_prices(): void
    {
        $uniqueId = rand(10000, 99999);
        $rawCommodity = "NoDupCrop{$uniqueId}(Special)";
        $cleanExpectedCrop = "NoDupCrop{$uniqueId}";
        $rawMarket = "NoDupMandi{$uniqueId}";
        $rawDistrict = "NoDupDist{$uniqueId}";

        $today = Carbon::today()->format('d/m/Y');

        $record1 = [
            'state' => 'Karnataka',
            'district' => $rawDistrict,
            'market' => $rawMarket,
            'commodity' => $rawCommodity,
            'variety' => 'Standard',
            'arrival_date' => $today,
            'min_price' => '3000',
            'max_price' => '3400',
            'modal_price' => '3200',
            'arrival_quantity' => '100',
        ];

        $mockProvider = new class($this->dataSource, $record1) extends \App\Services\DataSources\DataGov\DataGovMarketDataProvider {
            public array $currentRecord;

            public function __construct(DataSource $ds, array $record)
            {
                parent::__construct($ds);
                $this->currentRecord = $record;
            }

            public function fetch(array $filters = []): iterable
            {
                return [$this->currentRecord];
            }
        };

        \App\Services\DataSources\DataSourceRegistry::register('test_data_gov', fn () => $mockProvider);

        /** @var MarketPriceIngestionService $service */
        $service = app(MarketPriceIngestionService::class);

        // Run 1: First sync -> should insert 1
        $res1 = $service->ingest('test_data_gov', ['force' => true]);
        $this->assertEquals(1, $res1['inserted']);
        $this->assertEquals(0, $res1['updated']);

        $crop = Crop::where('name', $cleanExpectedCrop)->firstOrFail();
        $market = Market::where('name', "{$rawMarket} APMC")->firstOrFail();

        $cropsCount = Crop::where('name', $cleanExpectedCrop)->count();
        $marketsCount = Market::where('name', "{$rawMarket} APMC")->count();
        $pricesCount = MarketPrice::where('crop_id', $crop->id)->where('market_id', $market->id)->count();

        $this->assertEquals(1, $cropsCount, "There should be exactly 1 crop record.");
        $this->assertEquals(1, $marketsCount, "There should be exactly 1 market record.");
        $this->assertEquals(1, $pricesCount, "There should be exactly 1 price record.");

        // Run 2: Same commodity and mandi, but updated price on the same day -> should update, NOT duplicate
        $record2 = $record1;
        $record2['modal_price'] = '3350';
        $mockProvider->currentRecord = $record2;

        $res2 = $service->ingest('test_data_gov', ['force' => true]);
        $this->assertEquals(0, $res2['inserted'], "Second sync should NOT insert a new price row.");
        $this->assertEquals(1, $res2['updated'], "Second sync should update the existing price row.");

        // Verify still strictly 1 crop, 1 market, and 1 price in the database
        $this->assertEquals(1, Crop::where('name', $cleanExpectedCrop)->count());
        $this->assertEquals(1, Market::where('name', "{$rawMarket} APMC")->count());
        $this->assertEquals(1, MarketPrice::where('crop_id', $crop->id)->where('market_id', $market->id)->count());

        $updatedPrice = MarketPrice::where('crop_id', $crop->id)->where('market_id', $market->id)->firstOrFail();
        $this->assertEquals(3350.0, (float) $updatedPrice->modal_price, "Price should be updated to 3350, not duplicated.");

        // Clean up
        MarketPrice::where('crop_id', $crop->id)->delete();
        MarketPriceRaw::where('payload', 'like', "%{$rawCommodity}%")->delete();
        CropSourceMapping::where('source_crop_name', $rawCommodity)->delete();
        MarketSourceMapping::where('source_market_name', $rawMarket)->delete();
        CropVariety::where('crop_id', $crop->id)->delete();
        $crop->delete();
        $market->delete();
        District::where('name', $rawDistrict)->delete();
    }
}
