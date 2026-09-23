<?php

namespace Tests\Unit;

use App\Services\Location\NominatimGeocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NominatimGeocoderTest extends TestCase
{
    public function test_reverse_geocode_fetches_and_normalizes_address_with_cache(): void
    {
        Cache::flush();

        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Shivamogga, Shivamogga District, Karnataka, 577201, India',
                'address' => [
                    'city' => 'Shivamogga',
                    'subdistrict' => 'Shivamogga Taluk',
                    'state_district' => 'Shivamogga District',
                    'state' => 'Karnataka',
                    'postcode' => '577201',
                    'country' => 'India',
                ],
            ], 200),
        ]);

        $geocoder = new NominatimGeocoder();
        $result = $geocoder->reverseGeocode(13.9299, 75.5681);

        $this->assertNotNull($result);
        $this->assertEquals('Shivamogga', $result['district']);
        $this->assertEquals('Shivamogga', $result['taluk']);
        $this->assertEquals('Karnataka', $result['state']);
        $this->assertEquals('577201', $result['postcode']);

        // Verify second call is served from Cache (no new HTTP requests)
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([], 500),
        ]);

        $cachedResult = $geocoder->reverseGeocode(13.9299, 75.5681);
        $this->assertNotNull($cachedResult);
        $this->assertEquals('Shivamogga', $cachedResult['district']);
    }

    public function test_reverse_geocode_handles_http_errors_gracefully(): void
    {
        Cache::flush();

        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response('Server Error', 500),
        ]);

        $geocoder = new NominatimGeocoder();
        $result = $geocoder->reverseGeocode(12.9716, 77.5946);

        $this->assertNull($result);
    }
}
