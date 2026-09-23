<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Crop;
use App\Models\CuratedVideo;
use App\Models\NewsArticle;
use App\Models\Scheme;
use Tests\TestCase;

class FarmerCmsTest extends TestCase
{
    protected Scheme $scheme;
    protected NewsArticle $news;
    protected CuratedVideo $video;
    protected Article $article;
    protected Crop $crop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crop = Crop::where('is_active', true)->firstOrFail();

        $this->scheme = Scheme::updateOrCreate(
            ['slug' => 'pmksy-drip-subsidy-test'],
            [
                'title' => 'Krishi Sinchayee Yojana Drip Subsidy',
                'title_kn' => 'ಕೃಷಿ ಸಿಂಚಾಯಿ ಯೋಜನೆ ಹನಿ ನೀರಾವರಿ',
                'category' => 'irrigation',
                'sponsoring_agency' => 'Govt of Karnataka',
                'benefit_amount' => 'Drip and sprinkler equipment subsidy up to 5 hectares',
                'eligibility_criteria' => 'Must have valid RTC / Pahani with source of water',
                'documents_required' => 'Aadhaar, Pahani, Bank Passbook, Soil Test Certificate',
                'official_url' => 'https://fruits.karnataka.gov.in',
                'apply_url' => 'https://fruits.karnataka.gov.in',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        $this->news = NewsArticle::updateOrCreate(
            ['slug' => 'arecanut-mip-revised-test'],
            [
                'headline' => 'Arecanut minimum import price revised',
                'title' => 'Arecanut minimum import price revised',
                'headline_kn' => 'ಅಡಿಕೆ ಕನಿಷ್ಠ ಆಮದು ದರ ಪರಿಷ್ಕರಣೆ',
                'title_kn' => 'ಅಡಿಕೆ ಕನಿಷ್ಠ ಆಮದು ದರ ಪರಿಷ್ಕರಣೆ',
                'priority' => 'breaking',
                'summary' => 'Central government revises minimum import price to protect domestic growers',
                'content' => 'Detailed notification issued by DGFT today...',
                'body' => 'Detailed notification issued by DGFT today...',
                'source_name' => 'Ministry of Commerce',
                'is_active' => true,
                'published_at' => now(),
            ]
        );

        $this->video = CuratedVideo::updateOrCreate(
            ['youtube_video_id' => 'abc12345678'],
            [
                'title' => 'Scientific Drip Irrigation in Arecanut',
                'title_kn' => 'ಅಡಿಕೆಯಲ್ಲಿ ವೈಜ್ಞಾನಿಕ ಹನಿ ನೀರಾವರಿ ಪದ್ಧತಿ',
                'youtube_url' => 'https://www.youtube.com/watch?v=abc12345678',
                'crop_id' => $this->crop->id,
                'category' => 'irrigation',
                'channel_name' => 'Krushi Channel',
                'duration_text' => '10:30',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        $this->article = Article::updateOrCreate(
            ['slug' => 'arecanut-soil-nutrition-test'],
            [
                'crop_id' => $this->crop->id,
                'title' => 'Soil Nutrition Management in Arecanut Orchards',
                'title_kn' => 'ಅಡಿಕೆ ತೋಟಗಳಲ್ಲಿ ಮಣ್ಣಿನ ಪೋಷಕಾಂಶ ನಿರ್ವಹಣೆ',
                'category' => 'soil_fertilizer',
                'summary' => 'Complete schedule of organic and inorganic fertilizers for high yield',
                'body' => 'Apply 100g N, 40g P, 140g K per palm in two split doses...',
                'author_name' => 'Dr. R. Hegde',
                'is_published' => true,
                'published_at' => now(),
            ]
        );
    }

    public function test_farmer_can_view_schemes_list_and_detail(): void
    {
        // 1. Schemes Index
        $response = $this->get('/schemes');
        $response->assertStatus(200);
        $response->assertSee('ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು');
        $response->assertSee('ಕೃಷಿ ಸಿಂಚಾಯಿ ಯೋಜನೆ');

        // 2. Category filtering
        $filterResponse = $this->get('/schemes?category=irrigation');
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('ಕೃಷಿ ಸಿಂಚಾಯಿ ಯೋಜನೆ');

        // 3. Scheme Show
        $showResponse = $this->get("/schemes/{$this->scheme->slug}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('ಅರ್ಹತೆಯ ಮಾನದಂಡಗಳು');
        $showResponse->assertSee('ಅಗತ್ಯವಿರುವ ದಾಖಲೆಗಳು');
        $showResponse->assertSee('https://fruits.karnataka.gov.in');
    }

    public function test_farmer_can_view_news_list_and_detail(): void
    {
        // 1. News Index
        $response = $this->get('/news');
        $response->assertStatus(200);
        $response->assertSee('ಕೃಷಿ ಸುದ್ದಿ');
        $response->assertSee('ಅಡಿಕೆ ಕನಿಷ್ಠ ಆಮದು ದರ ಪರಿಷ್ಕರಣೆ');

        // 2. Breaking Priority filter
        $breakingResponse = $this->get('/news?priority=breaking');
        $breakingResponse->assertStatus(200);
        $breakingResponse->assertSee('ಬ್ರೇಕಿಂಗ್');

        // 3. News Show
        $showResponse = $this->get("/news/{$this->news->slug}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Ministry of Commerce');
        $showResponse->assertSee('Detailed notification issued by DGFT');
    }

    public function test_farmer_can_view_videos_hub(): void
    {
        $response = $this->get('/videos');
        $response->assertStatus(200);
        $response->assertSee('ಕೃಷಿ ವಿಡಿಯೋಗಳು');
        $response->assertSee('ಅಡಿಕೆಯಲ್ಲಿ ವೈಜ್ಞಾನಿಕ ಹನಿ ನೀರಾವರಿ ಪದ್ಧತಿ');
        $response->assertSee('abc12345678');

        // Filter by crop
        $cropFilterResponse = $this->get("/videos?crop_id={$this->crop->id}");
        $cropFilterResponse->assertStatus(200);
        $cropFilterResponse->assertSee('Scientific Drip Irrigation');
    }

    public function test_farmer_can_view_articles_guide(): void
    {
        // 1. Articles Index
        $response = $this->get('/articles');
        $response->assertStatus(200);
        $response->assertSee('ಕೃಷಿ ಮಾರ್ಗದರ್ಶಿ');
        $response->assertSee('ಅಡಿಕೆ ತೋಟಗಳಲ್ಲಿ ಮಣ್ಣಿನ ಪೋಷಕಾಂಶ ನಿರ್ವಹಣೆ');

        // 2. Article Show
        $showResponse = $this->get("/articles/{$this->article->slug}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Dr. R. Hegde');
        $showResponse->assertSee('two split doses');
    }

    public function test_crop_show_page_displays_cms_integrations(): void
    {
        $response = $this->get("/crops/{$this->crop->slug}");
        $response->assertStatus(200);
        $response->assertSee('ತಜ್ಞರ ವಿಡಿಯೋ');
        $response->assertSee('ಕೃಷಿ ಸಬ್ಸಿಡಿ');
    }

    public function test_public_rest_api_v1_cms_endpoints(): void
    {
        // Schemes API
        $schemesRes = $this->getJson('/api/v1/schemes');
        $schemesRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'data', 'pagination']);

        // News API
        $newsRes = $this->getJson('/api/v1/news');
        $newsRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'data', 'pagination']);

        // Videos API
        $videosRes = $this->getJson('/api/v1/videos');
        $videosRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'data', 'pagination']);

        // Articles API
        $articlesRes = $this->getJson('/api/v1/articles');
        $articlesRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'data', 'pagination']);
    }
}
