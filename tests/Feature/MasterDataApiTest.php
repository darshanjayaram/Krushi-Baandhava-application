<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\District;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    /**
     * Test districts public API.
     */
    public function test_api_districts_endpoint_returns_json(): void
    {
        $response = $this->getJson('/api/v1/districts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'name_kn', 'latitude', 'longitude'],
            ],
        ]);
        $response->assertJsonFragment(['name' => 'Shivamogga']);
    }

    /**
     * Test taluks for district API.
     */
    public function test_api_taluks_endpoint_returns_taluks(): void
    {
        $district = District::where('name', 'Shivamogga')->first();

        $response = $this->getJson("/api/v1/districts/{$district->id}/taluks");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'district' => ['id', 'name'],
            'data' => [
                '*' => ['id', 'name', 'name_kn'],
            ],
        ]);
        $response->assertJsonFragment(['name' => 'Thirthahalli']);
    }

    /**
     * Test markets public API with distance proximity calculation.
     */
    public function test_api_markets_with_proximity_distance(): void
    {
        // Query near Shivamogga APMC coordinates (13.9299, 75.5681)
        $response = $this->getJson('/api/v1/markets?latitude=13.9299&longitude=75.5681&radius=50');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'distance'],
            ],
            'pagination',
        ]);
    }

    /**
     * Test crops public API.
     */
    public function test_api_crops_endpoint_returns_crops(): void
    {
        $response = $this->getJson('/api/v1/crops?is_major=true');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'slug', 'category', 'varieties'],
            ],
        ]);
        $response->assertJsonFragment(['name' => 'Arecanut']);
    }

    /**
     * Test crop varieties public API.
     */
    public function test_api_crop_varieties_endpoint(): void
    {
        $arecanut = Crop::where('slug', 'arecanut')->first();

        $response = $this->getJson("/api/v1/crops/{$arecanut->id}/varieties");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'crop' => ['id', 'name'],
            'data' => [
                '*' => ['id', 'name', 'slug'],
            ],
        ]);
        $response->assertJsonFragment(['slug' => 'rashi']);
    }
}
