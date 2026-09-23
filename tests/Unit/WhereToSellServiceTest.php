<?php

namespace Tests\Unit;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\State;
use App\Models\Taluk;
use App\Services\Market\WhereToSellService;
use Carbon\Carbon;
use Tests\TestCase;

class WhereToSellServiceTest extends TestCase
{
    protected WhereToSellService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WhereToSellService();
    }

    public function test_mathematical_realization_calculation_is_accurate(): void
    {
        // 1. Setup mock data
        $state = State::firstOrCreate(['code' => 'KA'], ['name' => 'Karnataka']);
        $district = District::firstOrCreate(['name' => 'Kolar', 'state_id' => $state->id], [
            'code' => 'KLR',
            'latitude' => 13.1367,
            'longitude' => 78.1291,
            'is_active' => true,
        ]);
        $market = Market::firstOrCreate(['code' => 'TEST_KOLAR'], [
            'name' => 'Test Kolar APMC',
            'name_kn' => 'ಟೆಸ್ಟ್ ಕೋಲಾರ',
            'district_id' => $district->id,
            'latitude' => 13.1367,
            'longitude' => 78.1291,
            'is_active' => true,
        ]);
        $crop = Crop::where('is_active', true)->first() ?? Crop::create([
            'slug' => 'test-tomato-decision',
            'name' => 'Test Tomato Decision',
            'name_kn' => 'ಟೆಸ್ಟ್ ಟೊಮೆಟೊ',
            'category_id' => 1,
            'is_active' => true,
        ]);

        $today = Carbon::today()->toDateString();
        MarketPrice::updateOrCreate(
            ['market_id' => $market->id, 'crop_id' => $crop->id, 'price_date' => $today],
            [
                'modal_price' => 2000.0,
                'min_price' => 1800.0,
                'max_price' => 2200.0,
                'arrival_quantity' => 500.0,
            ]
        );

        // Origin 50 km away
        $result = $this->service->compare(
            $crop->id,
            13.5000,
            78.1291,
            10.0,
            ['vehicle' => 'pickup']
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(10.0, $result['quantity_quintals']);
        $this->assertGreaterThan(0, $result['markets_count']);

        $m = $result['markets']->firstWhere('market_id', $market->id);
        $this->assertNotNull($m);

        // Expected gross: 10 * 2000 = 20,000
        $this->assertEquals(20000.0, $m['gross_revenue']);

        // Expected APMC cess: 1.5% of 20,000 = 300
        $this->assertEquals(300.0, $m['apmc_cess']);

        // Expected hamali: 10 Q * 10 = 100
        $this->assertEquals(100.0, $m['hamali']);

        // Expected net = gross - (transport + cess + hamali)
        $expectedNet = round($m['gross_revenue'] - ($m['transport_cost'] + $m['apmc_cess'] + $m['hamali']), 2);
        $this->assertEquals($expectedNet, $m['net_realization']);
        $this->assertEquals(round($expectedNet / 10.0, 2), $m['net_rate_per_qtl']);
    }

    public function test_vehicle_transport_rates_and_road_factor(): void
    {
        $pickup = $this->service->resolveVehicleProfile(['vehicle' => 'pickup']);
        $this->assertEquals(22.0, $pickup['rate_per_km']);
        $this->assertEquals(400.0, $pickup['min_fare']);

        $auto = $this->service->resolveVehicleProfile(['vehicle' => 'auto']);
        $this->assertEquals(15.0, $auto['rate_per_km']);
        $this->assertEquals(200.0, $auto['min_fare']);

        $truck = $this->service->resolveVehicleProfile(['vehicle' => 'truck']);
        $this->assertEquals(32.0, $truck['rate_per_km']);
        $this->assertEquals(800.0, $truck['min_fare']);

        $custom = $this->service->resolveVehicleProfile(['vehicle' => 'pickup', 'custom_rate' => 28.5]);
        $this->assertEquals(28.5, $custom['rate_per_km']);
        $this->assertTrue($custom['is_custom_rate']);
    }

    public function test_location_resolution_fallbacks(): void
    {
        // 1. GPS coordinates
        $gpsOrigin = $this->service->resolveOriginLocation(12.9716, 77.5946);
        $this->assertEquals('gps', $gpsOrigin['source']);
        $this->assertEquals(12.9716, $gpsOrigin['latitude']);

        // 2. Default fallback when no coordinates or options provided
        $defaultOrigin = $this->service->resolveOriginLocation(null, null);
        $this->assertNotEmpty($defaultOrigin['source']);
        $this->assertGreaterThan(0, $defaultOrigin['latitude']);
        $this->assertGreaterThan(0, $defaultOrigin['longitude']);
    }

    public function test_sorting_by_net_realization_and_price(): void
    {
        $crop = Crop::where('is_active', true)->firstOrFail();

        // Run compare with default net realization sort
        $resultNet = $this->service->compare($crop->id, 13.0, 77.5, 15.0, ['sort' => 'net_realization']);
        $this->assertTrue($resultNet['success']);

        if ($resultNet['markets']->count() >= 2) {
            $first = $resultNet['markets'][0];
            $second = $resultNet['markets'][1];
            $this->assertGreaterThanOrEqual($second['net_realization'], $first['net_realization']);
        }

        // Run compare with price sort
        $resultPrice = $this->service->compare($crop->id, 13.0, 77.5, 15.0, ['sort' => 'price_desc']);
        if ($resultPrice['markets']->count() >= 2) {
            $first = $resultPrice['markets'][0];
            $second = $resultPrice['markets'][1];
            $this->assertGreaterThanOrEqual($second['modal_price'], $first['modal_price']);
        }
    }
}
