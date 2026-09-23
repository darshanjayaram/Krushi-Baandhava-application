<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Crop;
use App\Models\CuratedVideo;
use App\Models\NewsArticle;
use App\Models\Scheme;
use Tests\TestCase;

class CmsModelTest extends TestCase
{
    public function test_scheme_model_scopes_and_attributes(): void
    {
        $activeScheme = Scheme::firstOrCreate(
            ['slug' => 'pm-kisan-unit-test'],
            [
                'title' => 'PM Kisan Test',
                'title_kn' => 'ಪಿಎಂ ಕಿಸಾನ್ ಟೆಸ್ಟ್',
                'category' => 'subsidy',
                'sponsoring_agency' => 'Central Govt',
                'summary' => 'Income support for farmers',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        $inactiveScheme = Scheme::firstOrCreate(
            ['slug' => 'old-scheme-unit-test'],
            [
                'title' => 'Old Scheme Test',
                'category' => 'subsidy',
                'summary' => 'Discontinued',
                'is_active' => false,
                'display_order' => 99,
            ]
        );

        $activeSchemes = Scheme::active()->get();
        $this->assertTrue($activeSchemes->contains($activeScheme));
        $this->assertFalse($activeSchemes->contains($inactiveScheme));

        $this->assertEquals('ಸಬ್ಸಿಡಿ & ಅನುದಾನ (Subsidies & Grants)', $activeScheme->category_label_kn);
    }

    public function test_news_article_scopes_and_priority_badges(): void
    {
        $breakingNews = NewsArticle::firstOrCreate(
            ['slug' => 'cyclone-alert-unit-test'],
            [
                'title' => 'Cyclone Alert Test',
                'title_kn' => 'ಚಂಡಮಾರುತ ಎಚ್ಚರಿಕೆ ಟೆಸ್ಟ್',
                'priority' => 'breaking',
                'summary' => 'Heavy rainfall expected in coastal districts',
                'is_active' => true,
                'published_at' => now(),
            ]
        );

        $normalNews = NewsArticle::firstOrCreate(
            ['slug' => 'market-report-unit-test'],
            [
                'title' => 'Market report Test',
                'priority' => 'normal',
                'summary' => 'Stable arrivals today',
                'is_active' => true,
                'published_at' => now()->subDay(),
            ]
        );

        $this->assertTrue(NewsArticle::active()->breaking()->get()->contains($breakingNews));
        $this->assertFalse(NewsArticle::active()->breaking()->get()->contains($normalNews));

        $this->assertEquals('🚨 ಬ್ರೇಕಿಂಗ್ (Breaking)', $breakingNews->priority_label);
        $this->assertStringContainsString('bg-red-100', $breakingNews->priority_badge_class);
    }

    public function test_curated_video_youtube_id_extraction_and_urls(): void
    {
        // Standard watch URL
        $id1 = CuratedVideo::extractYoutubeId('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertEquals('dQw4w9WgXcQ', $id1);

        // Short URL (youtu.be)
        $id2 = CuratedVideo::extractYoutubeId('https://youtu.be/dQw4w9WgXcQ?si=test1234');
        $this->assertEquals('dQw4w9WgXcQ', $id2);

        // Direct 11-char ID
        $id3 = CuratedVideo::extractYoutubeId('dQw4w9WgXcQ');
        $this->assertEquals('dQw4w9WgXcQ', $id3);

        $video = new CuratedVideo([
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);

        $this->assertEquals('https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $video->thumbnail_url);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0', $video->embed_url);
    }

    public function test_article_slug_boot_and_category_labels(): void
    {
        $article = Article::firstOrCreate(
            ['slug' => 'soil-health-best-practices-unit'],
            [
                'title' => 'Soil Health Best Practices Unit',
                'title_kn' => 'ಮಣ್ಣಿನ ಆರೋಗ್ಯ ರಕ್ಷಣೆ',
                'category' => 'soil_fertilizer',
                'summary' => 'How to test soil nitrogen levels',
                'body' => 'Comprehensive soil testing guide...',
                'is_published' => true,
            ]
        );

        $this->assertNotNull($article->published_at);
        $this->assertEquals('ಮಣ್ಣು & ರಸಗೊಬ್ಬರ (Soil & Fertilizer)', $article->category_label_kn);

        $published = Article::published()->get();
        $this->assertTrue($published->contains($article));
    }
}
