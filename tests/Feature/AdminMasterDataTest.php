<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\District;
use App\Models\Market;
use App\Models\State;
use App\Models\Taluk;
use App\Models\User;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    protected User $admin;
    protected User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->farmer = User::where('role', User::ROLE_FARMER)->first()
            ?? User::factory()->create(['role' => User::ROLE_FARMER]);
    }

    /**
     * Test admin can view districts list.
     */
    public function test_admin_can_view_districts_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/districts?search=Shivamogga');

        $response->assertStatus(200);
        $response->assertSee('Karnataka Districts');
        $response->assertSee('Shivamogga');
    }

    /**
     * Test admin can create a new district with audit log.
     */
    public function test_admin_can_create_district(): void
    {
        $state = State::first();
        $name = 'Hassan ' . rand(100, 999);

        $response = $this->actingAs($this->admin)->post('/admin/districts', [
            'state_id' => $state->id,
            'name' => $name,
            'name_kn' => 'ಹಾಸನ',
            'code' => 'KA_HAS_' . rand(1000, 9999),
            'latitude' => 13.0033,
            'longitude' => 76.1004,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/districts');
        $this->assertDatabaseHas('districts', ['name' => $name]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'district.create',
            'entity_type' => 'District',
        ]);
    }

    /**
     * Test admin can toggle district status.
     */
    public function test_admin_can_toggle_district_status(): void
    {
        $district = District::create([
            'state_id' => State::first()->id,
            'name' => 'Temporary District ' . rand(1000, 9999),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch("/admin/districts/{$district->id}/toggle");

        $response->assertRedirect();
        $this->assertFalse((bool) $district->fresh()->is_active);

        $district->delete();
    }

    /**
     * Test admin can add a taluk to a district.
     */
    public function test_admin_can_add_taluk(): void
    {
        $district = District::first();

        $response = $this->actingAs($this->admin)->post('/admin/taluks', [
            'district_id' => $district->id,
            'name' => 'Test Taluk ' . rand(100, 999),
            'name_kn' => 'ಟೆಸ್ಟ್ ತಾಲೂಕು',
            'latitude' => 13.5,
            'longitude' => 75.5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('taluks', ['district_id' => $district->id, 'name_kn' => 'ಟೆಸ್ಟ್ ತಾಲೂಕು']);
    }

    /**
     * Test admin can view and register APMC mandis.
     */
    public function test_admin_can_create_market(): void
    {
        $district = District::first();
        $name = 'Test Mandi ' . rand(100, 999);

        $response = $this->actingAs($this->admin)->post('/admin/markets', [
            'district_id' => $district->id,
            'name' => $name,
            'name_kn' => 'ಟೆಸ್ಟ್ ಮಂಡಿ',
            'code' => 'KA_APMC_TEST_' . rand(1000, 9999),
            'market_type' => 'APMC',
            'latitude' => 13.935,
            'longitude' => 75.575,
            'address' => 'Test Yard Address',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/markets');
        $this->assertDatabaseHas('markets', ['name' => $name]);
    }

    /**
     * Test admin can register a crop with auto-generated slug.
     */
    public function test_admin_can_create_crop(): void
    {
        $category = CropCategory::first();
        $suffix = rand(100, 999);

        $response = $this->actingAs($this->admin)->post('/admin/crops', [
            'category_id' => $category->id,
            'name' => 'Cardamom ' . $suffix,
            'name_kn' => 'ಏಲಕ್ಕಿ',
            'standard_unit' => 'Kg',
            'scientific_name' => 'Elettaria cardamomum',
            'is_major' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/crops');
        $this->assertDatabaseHas('crops', [
            'name' => 'Cardamom ' . $suffix,
        ]);
    }

    /**
     * Test admin can add variety to crop.
     */
    public function test_admin_can_add_variety(): void
    {
        $crop = Crop::first();
        $varietyName = 'Special Variety ' . rand(1000, 9999);

        $response = $this->actingAs($this->admin)->post('/admin/varieties', [
            'crop_id' => $crop->id,
            'name' => $varietyName,
            'name_kn' => 'ಸ್ಪೆಷಲ್ ಗ್ರೇಡ್',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crop_varieties', [
            'crop_id' => $crop->id,
            'name' => $varietyName,
        ]);
    }

    /**
     * Test farmer role cannot access master data management.
     */
    public function test_farmer_cannot_access_master_data_crud(): void
    {
        $response = $this->actingAs($this->farmer)->get('/admin/districts');
        $response->assertRedirect('/admin/login');

        $responsePost = $this->actingAs($this->farmer)->post('/admin/crops', [
            'name' => 'Unauthorized Crop',
        ]);
        $responsePost->assertRedirect('/admin/login');
    }
}
