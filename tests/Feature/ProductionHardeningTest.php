<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    public function test_security_headers_are_present_on_farmer_routes(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_security_headers_are_present_on_admin_routes(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_laravel_health_probe_up_endpoint_returns_200(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_public_api_rate_limiter_headers_are_present(): void
    {
        $response = $this->getJson('/api/v1/districts');

        $response->assertStatus(200);
        $this->assertTrue(
            $response->headers->has('X-RateLimit-Limit') || $response->headers->has('RateLimit-Limit')
        );
    }

    public function test_where_to_sell_decision_rate_limiter_is_active(): void
    {
        $response = $this->getJson('/api/v1/decision/where-to-sell');

        // Without parameters it returns 422, but rate limit header is attached
        $this->assertTrue(
            $response->headers->has('X-RateLimit-Limit') || $response->headers->has('RateLimit-Limit')
        );
    }

    public function test_production_health_check_command_runs_successfully(): void
    {
        $this->artisan('app:health-check')
            ->assertExitCode(0);
    }

    public function test_production_health_check_command_supports_json_output(): void
    {
        $this->artisan('app:health-check', ['--json' => true])
            ->assertExitCode(0);
    }

    public function test_htaccess_file_contains_security_caching_and_compression_directives(): void
    {
        $htaccessPath = public_path('.htaccess');
        $this->assertFileExists($htaccessPath);

        $content = file_get_contents($htaccessPath);
        $this->assertStringContainsString('Options -Indexes', $content);
        $this->assertStringContainsString('FilesMatch', $content);
        $this->assertStringContainsString('<Files .env>', $content);
        $this->assertStringContainsString('mod_deflate.c', $content);
        $this->assertStringContainsString('mod_expires.c', $content);
        $this->assertStringContainsString('ExpiresByType text/css "access plus 1 year"', $content);
        $this->assertStringContainsString('RewriteEngine On', $content);
    }
}
