<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocaleMiddleware::class,
            \App\Http\Middleware\CheckMaintenanceModeMiddleware::class,
        ]);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom rendering for 404 Not Found (and ModelNotFoundException)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'The requested endpoint or resource was not found.',
                ], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        // Custom rendering for 403 Forbidden
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Access denied or unauthorized.',
                ], 403);
            }
            return response()->view('errors.403', [], 403);
        });

        // Custom rendering for 419 Page / CSRF Token Expired
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Your session has expired. Please refresh and try again.',
                ], 419);
            }
            return response()->view('errors.419', [], 419);
        });

        // Custom rendering for 429 Too Many Requests
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Too many requests. Please slow down and try again shortly.',
                ], 429);
            }
            return response()->view('errors.429', [], 429);
        });

        // Production Server Errors (500) and Zero-Disk-I/O Emergency Fallback
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $msg = $e->getMessage();
            $isDiskQuotaError = str_contains($msg, 'Disk quota exceeded')
                || str_contains($msg, 'errno=122')
                || str_contains($msg, 'No space left on device');

            // 1. If Disk Quota or Disk Full is encountered, bypass Blade and return Zero-Disk static HTML
            if ($isDiskQuotaError) {
                if ($request->is('api/*') || $request->wantsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Server storage quota exceeded (errno 122). Please contact server administrator.',
                    ], 500);
                }

                return response(\App\Support\EmergencyErrorHandler::renderDiskQuotaPage($msg), 500, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ]);
            }

            // 2. Standard Production / Non-Debug 500 Handler
            if (!config('app.debug') || app()->isProduction()) {
                if ($request->is('api/*') || $request->wantsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'An internal server error occurred. Our team has been notified.',
                    ], 500);
                }

                try {
                    return response()->view('errors.500', ['exception' => $e], 500);
                } catch (\Throwable $nestedError) {
                    // If Blade compilation fails (e.g. storage write failure), fallback to Zero-Disk HTML
                    return response(\App\Support\EmergencyErrorHandler::renderDiskQuotaPage($nestedError->getMessage()), 500, [
                        'Content-Type' => 'text/html; charset=UTF-8',
                    ]);
                }
            }
        });
    })->create();
