<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    /**
     * Test admin login screen is viewable.
     */
    public function test_admin_login_screen_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Krushi Baandhava Admin');
        $response->assertSee('Sign In to Portal');
    }

    /**
     * Test guests cannot access protected admin routes.
     */
    public function test_guests_are_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Test farmer role cannot access admin dashboard.
     */
    public function test_farmer_role_cannot_access_admin_dashboard(): void
    {
        $farmer = User::where('role', User::ROLE_FARMER)->first();

        if ($farmer) {
            $response = $this->actingAs($farmer)->get('/admin/dashboard');
            $response->assertRedirect('/admin/login');
        }
    }

    /**
     * Test Super Admin authentication and dashboard rendering.
     */
    public function test_super_admin_can_authenticate_and_access_dashboard(): void
    {
        $admin = User::where('email', 'admin@krushibaandhava.org')->first();

        if ($admin) {
            $response = $this->post('/admin/login', [
                'email' => 'admin@krushibaandhava.org',
                'password' => 'password123',
            ]);

            $response->assertRedirect('/admin/dashboard');

            $dashboardResponse = $this->actingAs($admin)->get('/admin/dashboard');
            $dashboardResponse->assertStatus(200);
            $dashboardResponse->assertSee('APMC Mandis');
            $dashboardResponse->assertSee('Feature Flags');
        }
    }

    /**
     * Test admin logout.
     */
    public function test_admin_can_logout(): void
    {
        $admin = User::where('email', 'admin@krushibaandhava.org')->first();

        if ($admin) {
            $response = $this->actingAs($admin)->post('/admin/logout');
            $response->assertRedirect('/admin/login');
            $this->assertGuest();
        }
    }
}
