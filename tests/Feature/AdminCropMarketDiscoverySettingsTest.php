<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminCropMarketDiscoverySettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected CropCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@krushibaandhava.org'],
            [
                'name' => 'Data Admin',
                'password' => bcrypt('password123'),
                'role' => 'super_admin',
                'preferred_language' => 'kn',
            ]
        );

        $this->category = CropCategory::firstOrCreate(
            ['slug' => 'test-cat-discovery'],
            ['name' => 'Test Discovery Category', 'display_order' => 1]
        );
    }

    public function test_admin_can_view_market_discovery_configuration_in_crop_form(): void
    {
        $crop = Crop::first();
        $this->assertNotNull($crop);

        $response = $this->actingAs($this->admin)->get("/admin/crops/{$crop->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('VIEW DIFFERENT MARKET', false);
        $response->assertSee('market_radius_km');
        $response->assertSee('default_market_sort');
        $response->assertSee('allow_user_sort_toggle');
        $response->assertSee('enable_smart_badges');
    }

    public function test_admin_can_update_crop_with_custom_discovery_and_distance_settings(): void
    {
        $crop = Crop::where('slug', 'arecanut')->first() ?? Crop::first();
        $this->assertNotNull($crop);

        $updatePayload = [
            'category_id' => $crop->category_id,
            'name' => $crop->name,
            'name_kn' => $crop->name_kn,
            'slug' => $crop->slug,
            'standard_unit' => $crop->standard_unit,
            'market_radius_km' => 425,
            'default_market_sort' => 'highest_price_first',
            'allow_user_sort_toggle' => '1',
            'enable_smart_badges' => '1',
            'is_major' => '1',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->put("/admin/crops/{$crop->id}", $updatePayload);
        $response->assertRedirect('/admin/crops');
        $response->assertSessionHas('success');

        $fresh = $crop->fresh();
        $this->assertEquals(425, $fresh->market_radius_km);
        $this->assertEquals('highest_price_first', $fresh->default_market_sort);
        $this->assertTrue($fresh->allow_user_sort_toggle);
        $this->assertTrue($fresh->enable_smart_badges);
    }

    public function test_admin_can_toggle_off_sort_switch_and_smart_badges(): void
    {
        $crop = Crop::first();
        $this->assertNotNull($crop);

        $updatePayload = [
            'category_id' => $crop->category_id,
            'name' => $crop->name,
            'name_kn' => $crop->name_kn,
            'slug' => $crop->slug,
            'standard_unit' => $crop->standard_unit,
            'market_radius_km' => 150,
            'default_market_sort' => 'nearest_first',
            // Checkboxes unchecked (omitted from payload)
            'is_major' => '0',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->put("/admin/crops/{$crop->id}", $updatePayload);
        $response->assertRedirect('/admin/crops');

        $fresh = $crop->fresh();
        $this->assertEquals(150, $fresh->market_radius_km);
        $this->assertEquals('nearest_first', $fresh->default_market_sort);
        $this->assertFalse($fresh->allow_user_sort_toggle);
        $this->assertFalse($fresh->enable_smart_badges);
    }

    public function test_validation_rejects_invalid_sort_type_and_negative_radius(): void
    {
        $crop = Crop::first();
        $this->assertNotNull($crop);

        $invalidPayload = [
            'category_id' => $crop->category_id,
            'name' => $crop->name,
            'standard_unit' => $crop->standard_unit,
            'market_radius_km' => -50, // invalid negative
            'default_market_sort' => 'invalid_sort_type', // invalid sort
        ];

        $response = $this->actingAs($this->admin)->put("/admin/crops/{$crop->id}", $invalidPayload);
        $response->assertSessionHasErrors(['market_radius_km', 'default_market_sort']);
    }
}
