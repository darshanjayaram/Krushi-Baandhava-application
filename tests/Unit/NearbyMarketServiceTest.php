<?php

namespace Tests\Unit;

use App\Models\Market;
use App\Services\Location\NearbyMarketService;
use Tests\TestCase;

class NearbyMarketServiceTest extends TestCase
{
    public function test_haversine_distance_calculation_accuracy(): void
    {
        $service = new NearbyMarketService();

        // Distance between Bengaluru (12.9716, 77.5946) and Mysuru (12.2958, 76.6394) is ~128-135 km
        $distance = $service->calculateHaversineDistance(12.9716, 77.5946, 12.2958, 76.6394);

        $this->assertGreaterThan(125, $distance);
        $this->assertLessThan(140, $distance);
    }

    public function test_bearing_and_cardinal_direction_calculation(): void
    {
        $service = new NearbyMarketService();

        // Point directly North
        $bearingNorth = $service->calculateBearing(13.0, 75.0, 14.0, 75.0);
        $this->assertEquals(0, round($bearingNorth));
        $this->assertEquals('North', $service->getCardinalDirection($bearingNorth, 'en'));
        $this->assertEquals('ಉತ್ತರ', $service->getCardinalDirection($bearingNorth, 'kn'));

        // Point East
        $bearingEast = $service->calculateBearing(13.0, 75.0, 13.0, 76.0);
        $this->assertEquals(90, round($bearingEast));
        $this->assertEquals('East', $service->getCardinalDirection($bearingEast, 'en'));
        $this->assertEquals('ಪೂರ್ವ', $service->getCardinalDirection($bearingEast, 'kn'));
    }

    public function test_find_nearby_markets_within_radius(): void
    {
        $service = new NearbyMarketService();

        // Coordinates around Shivamogga APMC (13.9350, 75.5650)
        $nearby = $service->findNearby(13.9300, 75.5600, 25.0);

        $this->assertNotEmpty($nearby);
        $closest = $nearby->first();
        $this->assertInstanceOf(Market::class, $closest);
        $this->assertLessThanOrEqual(25.0, $closest->distance_km);
        $this->assertNotNull($closest->google_maps_url);
    }
}
