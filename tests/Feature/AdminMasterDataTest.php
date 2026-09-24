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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    use DatabaseTransactions;

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
     * Test admin can create a market without APMC code and it auto-generates a clean unique code.
     */
    public function test_admin_can_create_market_without_code_and_auto_generates(): void
    {
        $district = District::first();
        $name = 'Tarikere Sub Yard ' . rand(100, 999);

        $response = $this->actingAs($this->admin)->post('/admin/markets', [
            'district_id' => $district->id,
            'name' => $name,
            'name_kn' => 'ತರೀಕೆರೆ ಉಪ ಮಾರುಕಟ್ಟೆ',
            'code' => '', // Left blank intentionally
            'market_type' => 'Sub-market',
            'latitude' => 13.71,
            'longitude' => 75.81,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/markets');
        $market = Market::where('name', $name)->first();
        $this->assertNotNull($market);
        $this->assertNotEmpty($market->code);
        $this->assertStringStartsWith('KA_APMC_', $market->code);
    }

    /**
     * Test admin can view market edit form with quick-fill presets and mapped aliases.
     */
    public function test_admin_can_view_market_edit_form_with_presets_and_aliases(): void
    {
        $market = Market::first();

        $response = $this->actingAs($this->admin)->get("/admin/markets/{$market->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Quick-Fill from Standard Karnataka Mandi Directory', false);
        $response->assertSee('Official APMC Code', false);
        $response->assertSee('Raw Feed Aliases Mapped to this Mandi', false);
        $response->assertSee('Map New Feed Alias', false);
    }

    /**
     * Test admin can add and remove a feed alias directly from the market edit screen.
     */
    public function test_admin_can_add_and_remove_feed_alias_from_market(): void
    {
        $market = Market::first();
        $aliasName = 'Raw Alias ' . rand(1000, 9999);

        // Add alias
        $addResponse = $this->actingAs($this->admin)->post("/admin/markets/{$market->id}/aliases", [
            'source_market_name' => $aliasName,
            'source_district_name' => 'Shivamogga',
        ]);

        $addResponse->assertRedirect();
        $this->assertDatabaseHas('market_source_mappings', [
            'market_id' => $market->id,
            'source_market_name' => $aliasName,
            'is_verified' => true,
        ]);

        $mapping = \App\Models\MarketSourceMapping::where('source_market_name', $aliasName)->first();

        // Delete alias
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/markets/{$market->id}/aliases/{$mapping->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('market_source_mappings', ['id' => $mapping->id]);
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
