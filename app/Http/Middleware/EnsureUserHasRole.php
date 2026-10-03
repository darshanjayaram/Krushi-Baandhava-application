<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized admin access.'], 403);
            }
            return redirect()->route('admin.login')->with('error', 'Administrator privileges are required.');
        }

        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')->with('error', 'Your staff account has been deactivated.');
        }

        // Super Admin has unrestricted bypass across all modules
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // If specific roles were provided, check if user has one of them
        if (!empty($roles) && !$user->hasRole($roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden: Your role does not have permission for this module.'], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', 'Access restricted: Your assigned role (' . $user->getRoleTitle() . ') does not have permission to access that module.');
        }

        return $next($request);
    }
}
