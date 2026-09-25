<?php

namespace Tests\Feature;

use App\Models\District;
use Tests\TestCase;

class FarmerPwaTest extends TestCase
{
    /**
     * Test farmer home screen loads successfully with modern UI.
     */
    public function test_farmer_home_screen_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Krushi Baandhava');
        $response->assertSee('ಇಂದಿನ ಮಾರುಕಟ್ಟೆ ದರಗಳು');
        $response->assertSee('ಮಂಡಿಗಳು');
    }

    /**
     * Test PWA manifest is accessible and valid.
     */
    public function test_pwa_manifest_is_accessible(): void
    {
        $response = $this->get('/manifest.json');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'short_name' => 'Krushi Baandhava',
            'theme_color' => '#047857',
            'display' => 'standalone',
        ]);
    }

    /**
     * Test PWA service worker file is served.
     */
    public function test_pwa_service_worker_is_accessible(): void
    {
        $response = $this->get('/sw.js');

        $response->assertStatus(200);
        $this->assertStringContainsString('krushi-baandhava', $response->getContent());
    }

    /**
     * Test switching active district updates home view.
     */
    public function test_switching_district_updates_view(): void
    {
        $ckm = District::where('name', 'Chikkamagaluru')->first();

        if ($ckm) {
            $response = $this->get('/?district=' . $ckm->id);
            $response->assertStatus(200);
            $response->assertSee('Chikkamagaluru');
        }
    }
}
