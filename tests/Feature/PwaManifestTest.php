<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Pwa\PwaManifestService;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    /**
     * Test dynamic PWA manifest route returns correct JSON structure and headers.
     */
    public function test_pwa_manifest_returns_valid_json_and_headers(): void
    {
        $response = $this->get('/manifest.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8');

        $data = $response->json();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('short_name', $data);
        $this->assertArrayHasKey('theme_color', $data);
        $this->assertArrayHasKey('background_color', $data);
        $this->assertArrayHasKey('display', $data);
        $this->assertArrayHasKey('icons', $data);
        $this->assertArrayHasKey('shortcuts', $data);
        $this->assertArrayHasKey('screenshots', $data);

        $this->assertEquals('standalone', $data['display']);
        $this->assertEquals('portrait-primary', $data['orientation']);
        $this->assertEquals('kn-IN', $data['lang']);

        // Assert at least 192x192 and 512x512 icons exist
        $sizes = array_column($data['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        // Assert maskable icon exists
        $purposes = array_column($data['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);

        // Assert screenshots exist for Chromium Rich Install UI
        $this->assertNotEmpty($data['screenshots']);
        $formFactors = array_column($data['screenshots'], 'form_factor');
        $this->assertContains('wide', $formFactors);
        $this->assertContains('narrow', $formFactors);
    }

    /**
     * Test disk synchronization writes valid JSON to public/manifest.json.
     */
    public function test_pwa_manifest_sync_writes_to_disk(): void
    {
        $service = app(PwaManifestService::class);
        $synced = $service->syncDiskManifest();

        $this->assertTrue($synced);

        $path = public_path('manifest.json');
        $this->assertFileExists($path);

        $content = json_decode(file_get_contents($path), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('name', $content);
        $this->assertArrayHasKey('icons', $content);
    }

    /**
     * Test offline fallback route is accessible.
     */
    public function test_offline_page_is_accessible(): void
    {
        $response = $this->get('/offline');

        $response->assertStatus(200);
        $response->assertSee('You\'re Currently Offline', false);
        $response->assertSee('ನೀವು ಪ್ರಸ್ತುತ ಆಫ್‌ಲೈನ್‌ನಲ್ಲಿದ್ದೀರಿ');
    }

    /**
     * Test farmer layout includes PWA meta tags and dynamic manifest.
     */
    public function test_farmer_layout_includes_pwa_meta_tags(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('apple-mobile-web-app-capable', false);
        $response->assertSee('apple-mobile-web-app-title', false);
        $response->assertSee('pwaUpdateToast', false);
    }
}
