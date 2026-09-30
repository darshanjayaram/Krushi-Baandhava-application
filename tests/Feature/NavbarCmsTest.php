<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarCmsTest extends TestCase
{
    /**
     * Test admin can access navbar CMS page.
     */
    public function test_admin_can_view_navbar_cms(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.navbar.index'));

        $response->assertStatus(200);
        $response->assertSee('Navbar & Menus CMS', false);
        $response->assertSee('Desktop Header Navbar');
        $response->assertSee('Mobile Bottom Dock');
        $response->assertSee('Mobile Hamburger Drawer');
    }

    /**
     * Test admin can update navbar configuration.
     */
    public function test_admin_can_update_navbar_links_and_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $customDesktopLinks = [
            [
                'label_en' => 'Special Mandi Rates',
                'label_kn' => 'ವಿಶೇಷ ಮಾರುಕಟ್ಟೆ ದರ',
                'url' => '/crops',
                'route_match' => 'home',
                'badge' => 'HOT',
                'badge_color' => 'amber',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'label_en' => 'Hidden Link',
                'label_kn' => 'ಅಡಗಿಸಲಾದ ಲಿಂಕ್',
                'url' => '/hidden',
                'route_match' => '',
                'badge' => '',
                'badge_color' => 'emerald',
                'new_tab' => false,
                'is_visible' => false, // Hidden
            ],
        ];

        $customDockLinks = [
            [
                'icon' => 'home',
                'label_en' => 'Home',
                'label_kn' => 'ಮುಖಪುಟ',
                'url' => '/',
                'route_match' => 'home',
                'has_dot' => true,
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => 'rates',
                'label_en' => 'Rates',
                'label_kn' => 'ದರಗಳು',
                'url' => '/crops',
                'route_match' => 'farmer.crops.*',
                'has_dot' => false,
                'new_tab' => false,
                'is_visible' => true,
            ],
        ];

        $response = $this->actingAs($admin)->post(route('admin.navbar.update'), [
            'navbar_desktop_links' => json_encode($customDesktopLinks),
            'navbar_mobile_dock_links' => json_encode($customDockLinks),
            'navbar_show_location_pill' => '1',
            'navbar_show_language_toggle' => '1',
            'navbar_show_hamburger_button' => '1',
            'navbar_mobile_dock_style' => 'floating',
            'navbar_mobile_dock_show_labels' => '1',
            'navbar_drawer_show_district' => '1',
            'navbar_drawer_show_whatsapp' => '1',
            'navbar_drawer_whatsapp_url' => 'https://wa.me/919999999999',
            'navbar_drawer_show_pwa' => '1',
            'navbar_show_feedback_fab' => '1',
            'navbar_feedback_fab_pulse' => '1',
            'navbar_feedback_fab_url' => '/feedback',
        ]);

        $response->assertRedirect(route('admin.navbar.index'));
        $response->assertSessionHas('success');

        // Check public farmer homepage renders the custom visible link in English
        $publicEnResponse = $this->withSession(['locale' => 'en'])->get(route('home'));
        $publicEnResponse->assertStatus(200);
        $publicEnResponse->assertSee('Special Mandi Rates');
        $publicEnResponse->assertDontSee('Hidden Link');

        // Check public farmer homepage renders the custom visible link in Kannada
        $publicKnResponse = $this->withSession(['locale' => 'kn'])->get(route('home'));
        $publicKnResponse->assertStatus(200);
        $publicKnResponse->assertSee('ವಿಶೇಷ ಮಾರುಕಟ್ಟೆ ದರ');
    }

    /**
     * Test admin can reset navigation to defaults.
     */
    public function test_admin_can_reset_navbar_defaults(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.navbar.reset'), [
            'section' => 'all',
        ]);

        $response->assertRedirect(route('admin.navbar.index'));
        $response->assertSessionHas('success');

        $this->assertNotEmpty(SystemSetting::get('navbar_desktop_links'));
        $this->assertNotEmpty(SystemSetting::get('navbar_mobile_dock_links'));
    }
}
