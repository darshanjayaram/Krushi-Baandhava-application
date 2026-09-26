<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminCropImageAndMissedCropsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    /**
     * Test that admin crops index displays image thumbnails and newly added Karnataka crops.
     */
    public function test_admin_crops_index_renders_crop_images_and_missed_karnataka_crops(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/crops');

        $response->assertStatus(200);

        // Verify missed crops on page 1
        $response->assertSee('Jowar', false);
        $response->assertSee('Green Chilli', false);
        $response->assertSee('Banana', false);
        $response->assertSee('Groundnut', false);
        $response->assertSee('Cotton', false);

        // Verify Kannada names on page 1
        $response->assertSee('ಜೋಳ', false);
        $response->assertSee('ಹಸಿಮೆಣಸಿನಕಾಯಿ', false);
        $response->assertSee('ಬಾಳೆಹಣ್ಣು', false);
        $response->assertSee('ಕಡಲೆಕಾಯಿ', false);
        $response->assertSee('ಹತ್ತಿ', false);

        // Verify page 2 crops via search
        $turResponse = $this->actingAs($this->admin)->get('/admin/crops?search=Tur');
        $turResponse->assertStatus(200);
        $turResponse->assertSee('Tur', false);
        $turResponse->assertSee('ತೊಗರಿ', false);

        $sunflowerResponse = $this->actingAs($this->admin)->get('/admin/crops?search=Sunflower');
        $sunflowerResponse->assertStatus(200);
        $sunflowerResponse->assertSee('Sunflower', false);
        $sunflowerResponse->assertSee('ಸೂರ್ಯಕಾಂತಿ', false);

        // Verify thumbnail img tags are present
        $response->assertSee('rounded-xl object-cover border', false);
    }

    /**
     * Test admin can view crop edit form with crop image management section.
     */
    public function test_admin_crop_form_has_image_management_section(): void
    {
        $crop = Crop::where('slug', 'banana')->firstOrFail();

        $response = $this->actingAs($this->admin)->get(route('admin.crops.edit', $crop));

        $response->assertStatus(200);
        $response->assertSee('Crop Image & Thumbnail', false);
        $response->assertSee('Upload New Photo', false);
        $response->assertSee('Pick From Bundled Presets', false);
        $response->assertSee('enctype="multipart/form-data"', false);
    }

    /**
     * Test admin can upload a custom crop image.
     */
    public function test_admin_can_upload_custom_crop_image(): void
    {
        $crop = Crop::where('slug', 'tomato')->firstOrFail();

        $fakeImage = UploadedFile::fake()->image('custom_tomato.jpg', 300, 300);

        $response = $this->actingAs($this->admin)->put(route('admin.crops.update', $crop), [
            'name' => $crop->name,
            'name_kn' => $crop->name_kn,
            'category_id' => $crop->category_id,
            'standard_unit' => $crop->standard_unit,
            'image' => $fakeImage,
        ]);

        $response->assertRedirect(route('admin.crops.index'));

        $crop->refresh();
        $this->assertNotNull($crop->icon);
        $this->assertStringStartsWith('uploads/crops/', $crop->icon);
        $this->assertStringContainsString('uploads/crops/', $crop->photo_url);

        // Clean up created fake file
        if (file_exists(public_path($crop->icon))) {
            unlink(public_path($crop->icon));
        }
    }

    /**
     * Test admin can set a preset crop image.
     */
    public function test_admin_can_update_crop_with_preset_image(): void
    {
        $crop = Crop::where('slug', 'banana')->firstOrFail();

        $response = $this->actingAs($this->admin)->put(route('admin.crops.update', $crop), [
            'name' => $crop->name,
            'name_kn' => $crop->name_kn,
            'category_id' => $crop->category_id,
            'standard_unit' => $crop->standard_unit,
            'preset_image' => 'banana.jpg',
        ]);

        $response->assertRedirect(route('admin.crops.index'));

        $crop->refresh();
        $this->assertEquals('images/crops/banana.jpg', $crop->icon);
        $this->assertStringContainsString('images/crops/banana.jpg', $crop->photo_url);
    }

    /**
     * Test missed Karnataka crops have valid photo URLs and varieties.
     */
    public function test_missed_crops_have_valid_photo_urls_and_varieties(): void
    {
        $slugs = ['jowar', 'tur', 'green-chilli', 'banana', 'groundnut', 'sunflower', 'cotton'];

        foreach ($slugs as $slug) {
            $crop = Crop::with('varieties')->where('slug', $slug)->first();
            $this->assertNotNull($crop, "Crop with slug '{$slug}' should exist.");
            $this->assertTrue($crop->is_active);
            $this->assertNotEmpty($crop->photo_url);
            $this->assertGreaterThanOrEqual(2, $crop->varieties->count(), "Crop '{$slug}' should have at least 2 varieties.");
        }
    }
}
