<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Authenticate an admin user with brute-force protection.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        // Check if user has exceeded rate limit (5 attempts per minute)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLog::log('admin.login_throttled', 'User', null, null, [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'lockout_seconds' => $seconds,
            ]);

            return back()->withErrors([
                'email' => "Too many failed login attempts. For security, your access is locked. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->isAdmin()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                AuditLog::log('admin.unauthorized_access', 'User', $user->id, null, [
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return back()->withErrors([
                    'email' => 'This account does not have administrative access privileges.',
                ])->onlyInput('email');
            }

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                AuditLog::log('admin.deactivated_attempt', 'User', $user->id, null, [
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return back()->withErrors([
                    'email' => 'Your staff account has been deactivated. Please contact a Super Administrator.',
                ])->onlyInput('email');
            }

            // Successful authentication -> clear rate limit
            RateLimiter::clear($throttleKey);

            $user->update(['last_login_at' => now()]);

            $request->session()->regenerate();

            AuditLog::log('admin.login', 'User', $user->id, null, [
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return redirect()->intended(route('admin.dashboard'))->with('success', "Welcome back, {$user->name}!");
        }

        // Failed attempt -> record hit and audit log
        RateLimiter::hit($throttleKey, 60);

        AuditLog::log('admin.login_failed', 'User', null, null, [
            'email' => $credentials['email'],
            'ip' => $request->ip(),
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the admin user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            AuditLog::log('admin.logout', 'User', Auth::id());
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been safely signed out.');
    }
}
