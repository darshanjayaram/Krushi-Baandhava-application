<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\User;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminCropVarietyMappingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Crop $paddy;
    protected CropVariety $jyothi;
    protected DataSource $dataSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->paddy = Crop::where('slug', 'paddy')->firstOrFail();
        $this->jyothi = $this->paddy->varieties()->where('name', 'like', '%Jyothi%')->first()
            ?? $this->paddy->varieties()->first();

        $this->dataSource = DataSource::where('code', 'data_gov_mandi')->firstOrFail();
    }

    /**
     * Test admin crops index displays API feed sources and variety mapping status.
     */
    public function test_admin_crops_index_displays_api_feed_sources_and_mapping_status(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/crops');

        $response->assertStatus(200);
        $response->assertSee('API Feed Sources & Mapping Status', false);
        $response->assertSee('data.gov.in / APMC', false);
        $response->assertSee('Coffee Board of India', false);
        $response->assertSee('Coconut Dev Board', false);
    }

    /**
     * Test admin can view crop edit screen with variety mapping card.
     */
    public function test_admin_can_view_crop_edit_with_variety_mapping_card(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/crops/{$this->paddy->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Commercial Varieties of Paddy', false);
        $response->assertSee('Map Raw API Variety / Grade Strings', false);
        $response->assertSee('Target Canonical Grade', false);
    }

    /**
     * Test admin can map a raw API variety string to a specific canonical crop grade.
     */
    public function test_admin_can_add_variety_alias_mapping(): void
    {
        $rawVarietyString = 'Common Grade Test ' . rand(100, 999);

        $response = $this->actingAs($this->admin)->post("/admin/crops/{$this->paddy->id}/variety-aliases", [
            'crop_variety_id' => $this->jyothi->id,
            'source_variety_name' => $rawVarietyString,
            'data_source_id' => $this->dataSource->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crop_source_mappings', [
            'crop_id' => $this->paddy->id,
            'crop_variety_id' => $this->jyothi->id,
            'source_variety_name' => $rawVarietyString,
            'is_verified' => true,
        ]);

        // Test ingestion service resolves this raw variety string to the exact mapped variety
        $service = app(MarketPriceIngestionService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('resolveVariety');
        $method->setAccessible(true);

        $resolved = $method->invoke($service, $this->paddy->id, $rawVarietyString, $this->dataSource->id);
        $this->assertNotNull($resolved);
        $this->assertEquals($this->jyothi->id, $resolved->id);
        $this->assertEquals($this->jyothi->name, $resolved->name);
    }

    /**
     * Test admin can delete a variety alias mapping.
     */
    public function test_admin_can_remove_variety_alias_mapping(): void
    {
        $mapping = CropSourceMapping::create([
            'data_source_id' => $this->dataSource->id,
            'source_crop_name' => $this->paddy->name,
            'source_variety_name' => 'Temp Variety ' . rand(100, 999),
            'crop_id' => $this->paddy->id,
            'crop_variety_id' => $this->jyothi->id,
            'confidence_score' => 1.00,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/admin/crops/{$this->paddy->id}/variety-aliases/{$mapping->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('crop_source_mappings', ['id' => $mapping->id]);
    }

    /**
     * Test admin can fetch live varieties endpoint for a crop.
     */
    public function test_admin_can_fetch_live_varieties_endpoint(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/admin/crops/{$this->paddy->id}/live-varieties?data_source_id={$this->dataSource->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'crop' => ['id', 'name'],
            'source' => ['id', 'name', 'code'],
            'varieties',
        ]);
        $this->assertTrue($response->json('ok'));
    }

    /**
     * Test admin can inspect raw API feed payload for a crop.
     */
    public function test_admin_can_inspect_raw_api_feed_endpoint(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/admin/crops/{$this->paddy->id}/inspect-feed?data_source_id={$this->dataSource->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'source' => ['id', 'name', 'code'],
            'http_status',
            'response_time_ms',
            'status',
            'sample_records',
        ]);
        $this->assertTrue($response->json('ok'));
    }

    /**
     * Test admin can add variety alias via AJAX with JSON response for 1-click mapping.
     */
    public function test_admin_can_add_variety_alias_via_ajax(): void
    {
        $rawVarietyString = 'Live Ajax Grade ' . rand(1000, 9999);

        $response = $this->actingAs($this->admin)->postJson("/admin/crops/{$this->paddy->id}/variety-aliases", [
            'crop_variety_id' => $this->jyothi->id,
            'source_variety_name' => $rawVarietyString,
            'data_source_id' => $this->dataSource->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'mapping' => [
                'raw_name' => $rawVarietyString,
                'variety_id' => $this->jyothi->id,
                'variety_name' => $this->jyothi->name,
            ],
        ]);

        $this->assertDatabaseHas('crop_source_mappings', [
            'crop_id' => $this->paddy->id,
            'crop_variety_id' => $this->jyothi->id,
            'source_variety_name' => $rawVarietyString,
        ]);
    }

    /**
     * Test unresolved entity mappings page renders canonical crop dropdown with clean category name.
     */
    public function test_unresolved_mappings_dropdown_renders_clean_category_name(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/unresolved-mappings');

        $response->assertStatus(200);
        $response->assertSee('Unresolved Entity & Alias Resolvers', false);
        $response->assertSee('Associate with Canonical Crop:', false);

        // Ensure category name is rendered cleanly and not converted to raw JSON
        $cropWithCategory = Crop::whereNotNull('category_id')->with('category')->first();
        if ($cropWithCategory && $cropWithCategory->category) {
            $response->assertSee(e($cropWithCategory->category->name), false);
            $response->assertDontSee('"display_order":', false);
        }
    }
}
