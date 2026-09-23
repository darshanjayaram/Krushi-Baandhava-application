<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\NewsArticle;
use App\Models\Scheme;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PwaAndSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('public_sitemap_xml');
    }

    public function test_manifest_json_is_valid_and_has_required_pwa_fields(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $jsonContent = file_get_contents($manifestPath);
        $manifest = json_decode($jsonContent, true);

        $this->assertIsArray($manifest);
        $this->assertEquals('Krushi Baandhava - ಕೃಷಿ ಬಾಂಧವ', $manifest['name']);
        $this->assertEquals('Krushi Baandhava', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('#047857', $manifest['theme_color']);
        $this->assertNotEmpty($manifest['icons']);
        $this->assertNotEmpty($manifest['shortcuts']);
        $this->assertContains('productivity', $manifest['categories']);

        // Check specific shortcuts
        $shortcutUrls = array_column($manifest['shortcuts'], 'url');
        $this->assertContains('/?source=shortcut', $shortcutUrls);
        $this->assertContains('/where-to-sell?source=shortcut', $shortcutUrls);
        $this->assertContains('/weather?source=shortcut', $shortcutUrls);
        $this->assertContains('/schemes?source=shortcut', $shortcutUrls);
    }

    public function test_service_worker_has_v2_cache_offline_precache_and_push_handlers(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $swContent = file_get_contents($swPath);
        $this->assertStringContainsString('krushi-baandhava-v2', $swContent);
        $this->assertStringContainsString("'/offline'", $swContent);
        $this->assertStringContainsString('self.addEventListener(\'install\'', $swContent);
        $this->assertStringContainsString('self.addEventListener(\'fetch\'', $swContent);
        $this->assertStringContainsString('self.addEventListener(\'push\'', $swContent);
        $this->assertStringContainsString('self.addEventListener(\'notificationclick\'', $swContent);
    }

    public function test_offline_page_returns_successful_bilingual_view(): void
    {
        $response = $this->get('/offline');

        $response->assertStatus(200);
        $response->assertSee("You're Currently Offline", false);
        $response->assertSee('ನೀವು ಪ್ರಸ್ತುತ ಆಫ್‌ಲೈನ್‌ನಲ್ಲಿದ್ದೀರಿ');
        $response->assertSee('Cached Rates Remain Available');
        $response->assertSee('Try Reconnecting / ಮರುಪ್ರಯತ್ನಿಸಿ');
    }

    public function test_dynamic_sitemap_xml_renders_correctly_with_entities(): void
    {
        // Ensure test entities exist
        $crop = Crop::where('is_active', true)->first();
        $market = Market::karnataka()->where('is_active', true)->first();

        $scheme = Scheme::firstOrCreate(
            ['slug' => 'test-sitemap-scheme'],
            [
                'title' => 'Test Sitemap Scheme',
                'title_kn' => 'ಟೆಸ್ಟ್ ಸೈಟ್‌ಮ್ಯಾಪ್ ಯೋಜನೆ',
                'category' => 'general',
                'sponsoring_agency' => 'Govt of Karnataka',
                'is_active' => true,
                'display_order' => 99,
            ]
        );

        $news = NewsArticle::firstOrCreate(
            ['slug' => 'test-sitemap-news'],
            [
                'title' => 'Test Sitemap News',
                'title_kn' => 'ಟೆಸ್ಟ್ ಸೈಟ್‌ಮ್ಯಾಪ್ ಸುದ್ದಿ',
                'category' => 'general',
                'content' => 'Test news content',
                'is_active' => true,
                'is_breaking' => false,
                'display_order' => 99,
                'published_at' => now(),
            ]
        );

        $article = Article::firstOrCreate(
            ['slug' => 'test-sitemap-guide'],
            [
                'title' => 'Test Sitemap Guide',
                'title_kn' => 'ಟೆಸ್ಟ್ ಸೈಟ್‌ಮ್ಯಾಪ್ ಮಾರ್ಗದರ್ಶಿ',
                'category' => 'general',
                'summary' => 'Guide summary',
                'content' => 'Guide full content',
                'is_active' => true,
                'display_order' => 99,
                'published_at' => now(),
            ]
        );

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('xml', strtolower((string) $response->headers->get('Content-Type')));

        $content = $response->getContent();
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString(url('/'), $content);
        $this->assertStringContainsString(url('/crops'), $content);
        $this->assertStringContainsString(url('/markets'), $content);
        $this->assertStringContainsString(url('/where-to-sell'), $content);
        $this->assertStringContainsString(url('/weather'), $content);
        $this->assertStringContainsString(url('/schemes/' . $scheme->slug), $content);
        $this->assertStringContainsString(url('/news/' . $news->slug), $content);
        $this->assertStringContainsString(url('/articles/' . $article->slug), $content);

        if ($crop) {
            $this->assertStringContainsString(url('/crops/' . $crop->slug), $content);
        }
        if ($market) {
            $this->assertStringContainsString(url('/markets/' . $market->code), $content);
        }
    }

    public function test_robots_txt_disallows_admin_and_declares_sitemap(): void
    {
        $robotsPath = public_path('robots.txt');
        $this->assertFileExists($robotsPath);

        $content = file_get_contents($robotsPath);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /api/', $content);
        $this->assertStringContainsString('Sitemap: https://krushibaandhava.org/sitemap.xml', $content);
    }

    public function test_homepage_includes_seo_meta_tags_network_indicator_and_pwa_banner(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta property="og:type" content="website"', false);
        $response->assertSee('<meta name="twitter:card"', false);
        $response->assertSee('Install Krushi Baandhava');
        $response->assertSee('Offline Mode: Showing cached rates');
        $response->assertSee('ಆಫ್‌ಲೈನ್ ಮೋಡ್: ಉಳಿಸಲಾದ ದರಗಳು ಲಭ್ಯವಿವೆ');
    }
}
