<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach production security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. Prevent MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. XSS Filter (Legacy protection for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 4. Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Restrict Unused Device Hardware APIs
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), payment=(), usb=(), display-capture=(), geolocation=(self)'
        );

        // 6. Content Security Policy (allows self, Livewire/Alpine inline, Chart.js CDN, YouTube embeds)
        if (!$response->headers->has('Content-Security-Policy')) {
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com data:",
                "img-src 'self' data: https: blob:",
                "media-src 'self'",
                "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com",
                "connect-src 'self' https://nominatim.openstreetmap.org https://api.open-meteo.com",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ];

            $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        }

        return $response;
    }
}
