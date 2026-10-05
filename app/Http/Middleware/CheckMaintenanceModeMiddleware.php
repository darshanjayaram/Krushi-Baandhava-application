<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maintenanceMode = filter_var(SystemSetting::get('maintenance_mode', false), FILTER_VALIDATE_BOOLEAN);

        if (!$maintenanceMode) {
            return $next($request);
        }

        // Allow Admin Portal routes and authentication endpoints
        if (
            $request->is('admin*') ||
            $request->is('login*') ||
            $request->is('logout*') ||
            $request->is('up')
        ) {
            return $next($request);
        }

        // Allow authenticated Admins
        if ($request->user() && method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin()) {
            return $next($request);
        }

        // Return 503 Maintenance Mode response
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => 'System is currently under maintenance. Please try again shortly.',
                'message_kn' => 'ಕೃಷಿ ಬಾಂಧವ ಸಿಸ್ಟಮ್ ಪ್ರಸ್ತುತ ನಿರ್ವಹಣೆಯಲ್ಲಿದೆ. ದಯವಿಟ್ಟು ಸ್ವಲ್ಪ ಸಮಯದ ನಂತರ ಪ್ರಯತ್ನಿಸಿ.',
            ], 503);
        }

        return response()->view('errors.503', [], 503);
    }
}
