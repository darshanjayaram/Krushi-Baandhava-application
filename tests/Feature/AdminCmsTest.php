<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Crop;
use App\Models\CuratedVideo;
use App\Models\NewsArticle;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminCmsTest extends TestCase
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

    public function test_guest_and_non_admin_cannot_access_cms(): void
    {
        $this->get('/admin/schemes')->assertRedirect('/admin/login');
        $this->actingAs($this->farmer)->get('/admin/schemes')->assertRedirect('/admin/login');
    }

    public function test_admin_can_crud_and_toggle_schemes(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get('/admin/schemes');
        $response->assertStatus(200);
        $response->assertSee('Government Welfare');

        // 2. Create
        $response = $this->actingAs($this->admin)->post('/admin/schemes', [
            'title' => 'Test Tractor Subsidy',
            'title_kn' => 'ಟ್ರಾಕ್ಟರ್ ಸಬ್ಸಿಡಿ ಪರೀಕ್ಷೆ',
            'slug' => 'test-tractor-subsidy',
            'category' => 'machinery',
            'sponsoring_agency' => 'Karnataka Dept of Agriculture',
            'summary' => '50% subsidy on mini tractors',
            'benefits' => 'Up to Rs 1,50,000 subsidy',
            'is_active' => '1',
            'display_order' => 10,
        ]);
        $response->assertRedirect('/admin/schemes');

        $scheme = Scheme::where('slug', 'test-tractor-subsidy')->first();
        $this->assertNotNull($scheme);
        $this->assertTrue($scheme->is_active);

        // 3. Toggle
        $this->actingAs($this->admin)->post("/admin/schemes/{$scheme->id}/toggle");
        $scheme->refresh();
        $this->assertFalse($scheme->is_active);

        // 4. Update
        $this->actingAs($this->admin)->put("/admin/schemes/{$scheme->id}", [
            'title' => 'Updated Tractor Subsidy',
            'category' => 'machinery',
            'summary' => 'Updated summary',
            'is_active' => '1',
            'display_order' => 5,
        ]);
        $scheme->refresh();
        $this->assertEquals('Updated Tractor Subsidy', $scheme->title);
        $this->assertTrue($scheme->is_active);

        // 5. Delete
        $this->actingAs($this->admin)->delete("/admin/schemes/{$scheme->id}");
        $this->assertNull(Scheme::find($scheme->id));
    }

    public function test_admin_can_crud_and_toggle_news(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get('/admin/news');
        $response->assertStatus(200);
        $response->assertSee('Agri News');

        // 2. Create
        $response = $this->actingAs($this->admin)->post('/admin/news', [
            'title' => 'Monsoon Forecast 2026',
            'title_kn' => 'ಮಾನ್ಸೂನ್ ಮುನ್ಸೂಚನೆ 2026',
            'slug' => 'monsoon-forecast-2026',
            'priority' => 'breaking',
            'summary' => 'Early rains predicted across Karnataka malnad',
            'body' => 'Full weather bulletin for farmers...',
            'source_name' => 'IMD Bengaluru',
            'is_active' => '1',
        ]);
        $response->assertRedirect('/admin/news');

        $news = NewsArticle::where('slug', 'monsoon-forecast-2026')->first();
        $this->assertNotNull($news);
        $this->assertEquals('breaking', $news->priority);

        // 3. Toggle
        $this->actingAs($this->admin)->post("/admin/news/{$news->id}/toggle");
        $news->refresh();
        $this->assertFalse($news->is_active);

        // 4. Delete
        $this->actingAs($this->admin)->delete("/admin/news/{$news->id}");
        $this->assertNull(NewsArticle::find($news->id));
    }

    public function test_admin_can_crud_and_toggle_videos(): void
    {
        $crop = Crop::where('is_active', true)->first();

        // 1. Index
        $response = $this->actingAs($this->admin)->get('/admin/videos');
        $response->assertStatus(200);
        $response->assertSee('Curated Educational Video Hub');

        // 2. Create with full YouTube link
        $response = $this->actingAs($this->admin)->post('/admin/videos', [
            'title' => 'How to double Arecanut yield',
            'title_kn' => 'ಅಡಿಕೆ ಇಳುವರಿ ದ್ವಿಗುಣಗೊಳಿಸುವ ವಿಧಾನ',
            'youtube_url' => 'https://www.youtube.com/watch?v=kYv_3jV3pUQ',
            'crop_id' => $crop->id,
            'category' => 'farming_tips',
            'channel_name' => 'Krushi Jagattu',
            'duration_text' => '14:20',
            'is_active' => '1',
            'display_order' => 1,
        ]);
        $response->assertRedirect('/admin/videos');

        $video = CuratedVideo::where('title', 'How to double Arecanut yield')->first();
        $this->assertNotNull($video);
        $this->assertEquals('kYv_3jV3pUQ', $video->youtube_video_id);

        // 3. Toggle
        $this->actingAs($this->admin)->post("/admin/videos/{$video->id}/toggle");
        $video->refresh();
        $this->assertFalse($video->is_active);

        // 4. Delete
        $this->actingAs($this->admin)->delete("/admin/videos/{$video->id}");
        $this->assertNull(CuratedVideo::find($video->id));
    }

    public function test_admin_can_crud_and_toggle_articles(): void
    {
        $crop = Crop::where('is_active', true)->first();

        // 1. Index
        $response = $this->actingAs($this->admin)->get('/admin/articles');
        $response->assertStatus(200);
        $response->assertSee('Farming Guides');

        // 2. Create
        $response = $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'Management of Yellow Leaf Disease',
            'title_kn' => 'ಹಳದಿ ಎಲೆ ರೋಗ ನಿರ್ವಹಣೆ',
            'crop_id' => $crop->id,
            'category' => 'pest_control',
            'summary' => 'Symptoms and organic treatment protocols',
            'body' => 'Step 1: Check root health. Step 2: Apply bio-fungicide...',
            'author_name' => 'Dr. K. S. Rao, UAS Dharwad',
            'is_published' => '1',
        ]);
        $response->assertRedirect('/admin/articles');

        $article = Article::where('title', 'Management of Yellow Leaf Disease')->first();
        $this->assertNotNull($article);
        $this->assertEquals('management-of-yellow-leaf-disease', $article->slug);
        $this->assertTrue($article->is_published);

        // 3. Toggle
        $this->actingAs($this->admin)->post("/admin/articles/{$article->id}/toggle");
        $article->refresh();
        $this->assertFalse($article->is_published);

        // 4. Delete
        $this->actingAs($this->admin)->delete("/admin/articles/{$article->id}");
        $this->assertNull(Article::find($article->id));
    }
}
