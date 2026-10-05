<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Supported application locales.
     */
    public const SUPPORTED_LOCALES = ['kn', 'en'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve locale from URL query param, route param, session, cookie, or fallback to 'kn'
        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        $defaultLang = \App\Models\SystemSetting::get('default_language', 'kn');
        if (!in_array($defaultLang, self::SUPPORTED_LOCALES, true)) {
            $defaultLang = 'kn';
        }

        $locale = $request->query('lang')
            ?? $routeLocale
            ?? $sessionLocale
            ?? $request->cookie('locale')
            ?? $defaultLang;

        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = $defaultLang;
        }

        App::setLocale($locale);

        if ($request->hasSession() && $request->session()->get('locale') !== $locale) {
            $request->session()->put('locale', $locale);
        }

        $response = $next($request);

        // Determine final effective locale (in case route handler updated session)
        $finalLocale = ($request->hasSession() && $request->session()->has('locale'))
            ? $request->session()->get('locale')
            : $locale;

        if (!in_array($finalLocale, self::SUPPORTED_LOCALES, true)) {
            $finalLocale = $defaultLang;
        }

        App::setLocale($finalLocale);

        // Ensure persistent cookie matches final effective locale
        if ($request->cookie('locale') !== $finalLocale && method_exists($response, 'withCookie')) {
            $response->withCookie(cookie()->forever('locale', $finalLocale));
        }

        return $response;
    }
}
