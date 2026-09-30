<?php

namespace App\Services\Pwa;

use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;

class PwaManifestService
{
    /**
     * Generate the complete dynamic Web App Manifest payload.
     */
    public function generateManifest(): array
    {
        $appName = SystemSetting::get('application_name', 'Krushi Baandhava');
        $appNameKn = SystemSetting::get('application_name_kn', 'ಕೃಷಿ ಬಾಂಧವ');
        $pwaName = SystemSetting::get('pwa_name', "{$appName} - {$appNameKn}");
        $pwaShortName = SystemSetting::get('pwa_short_name', $appName);
        $pwaDesc = SystemSetting::get('pwa_description', 'Karnataka Farmers Market Prices, Transparent Price Forecasts, Nearby Mandis, and Weather Advisories.');
        $themeColor = SystemSetting::get('pwa_theme_color', '#F5EFE6');
        $bgColor = SystemSetting::get('pwa_background_color', '#F5EFE6');
        $displayMode = SystemSetting::get('pwa_display_mode', 'standalone');

        // Dynamic base icons
        $icons = [];

        // 1. Check for custom uploaded branding logo or PWA icon
        $customLogo = SystemSetting::get('pwa_icon') ?: SystemSetting::get('app_logo');
        if ($customLogo && $customLogo !== '/icons/icon-192.svg' && $customLogo !== '/icons/icon-512.svg') {
            $cleanPath = ltrim($customLogo, '/');
            if (file_exists(public_path($cleanPath))) {
                $ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    default => 'image/svg+xml',
                };
                $icons[] = [
                    'src' => $cleanPath,
                    'sizes' => '512x512',
                    'type' => $mime,
                    'purpose' => 'any',
                ];
                $icons[] = [
                    'src' => $cleanPath,
                    'sizes' => '512x512',
                    'type' => $mime,
                    'purpose' => 'maskable',
                ];
            }
        }

        // 2. Standard 192x192 and 512x512 PNG icons (any + maskable)
        if (file_exists(public_path('icons/icon-192.png'))) {
            $icons[] = [
                'src' => 'icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ];
            $icons[] = [
                'src' => 'icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ];
        }

        if (file_exists(public_path('icons/icon-512.png'))) {
            $icons[] = [
                'src' => 'icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ];
            $icons[] = [
                'src' => 'icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ];
        }

        // 3. Vector SVG fallbacks
        if (file_exists(public_path('icons/icon-192.svg'))) {
            $icons[] = [
                'src' => 'icons/icon-192.svg',
                'sizes' => '192x192',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ];
        }
        if (file_exists(public_path('icons/icon-512.svg'))) {
            $icons[] = [
                'src' => 'icons/icon-512.svg',
                'sizes' => '512x512',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ];
        }

        // Shortcuts for quick actions from home screen icon long-press
        $iconShortcut = file_exists(public_path('icons/icon-192.png'))
            ? 'icons/icon-192.png'
            : 'icons/icon-192.svg';

        $shortcuts = [
            [
                'name' => 'Market Prices - ಮಾರುಕಟ್ಟೆ ದರಗಳು',
                'short_name' => 'Prices',
                'description' => "Today's mandi market rates across Karnataka",
                'url' => './?source=shortcut',
                'icons' => [
                    [
                        'src' => $iconShortcut,
                        'sizes' => '192x192',
                        'type' => str_ends_with($iconShortcut, '.png') ? 'image/png' : 'image/svg+xml',
                    ],
                ],
            ],
            [
                'name' => 'Crops & Forecast - ಬೆಳೆಗಳು & ಮುನ್ಸೂಚನೆ',
                'short_name' => 'Crops',
                'description' => 'Crop price trends and agricultural forecasts',
                'url' => './crops?source=shortcut',
                'icons' => [
                    [
                        'src' => $iconShortcut,
                        'sizes' => '192x192',
                        'type' => str_ends_with($iconShortcut, '.png') ? 'image/png' : 'image/svg+xml',
                    ],
                ],
            ],
            [
                'name' => 'Where to Sell - ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು',
                'short_name' => 'Where to Sell',
                'description' => 'Compare nearby mandi net returns with transport haulage',
                'url' => './where-to-sell?source=shortcut',
                'icons' => [
                    [
                        'src' => $iconShortcut,
                        'sizes' => '192x192',
                        'type' => str_ends_with($iconShortcut, '.png') ? 'image/png' : 'image/svg+xml',
                    ],
                ],
            ],
            [
                'name' => 'Weather Forecast - ಹವಾಮಾನ ಮಾಹಿತಿ',
                'short_name' => 'Weather',
                'description' => 'Hyperlocal 7-day weather and farming advisory',
                'url' => './weather?source=shortcut',
                'icons' => [
                    [
                        'src' => $iconShortcut,
                        'sizes' => '192x192',
                        'type' => str_ends_with($iconShortcut, '.png') ? 'image/png' : 'image/svg+xml',
                    ],
                ],
            ],
            [
                'name' => 'Govt Schemes - ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು',
                'short_name' => 'Schemes',
                'description' => 'Agricultural subsidies and farmer welfare programs',
                'url' => './schemes?source=shortcut',
                'icons' => [
                    [
                        'src' => $iconShortcut,
                        'sizes' => '192x192',
                        'type' => str_ends_with($iconShortcut, '.png') ? 'image/png' : 'image/svg+xml',
                    ],
                ],
            ],
        ];

        // Screenshots for Chromium Rich Install UI standard
        $screenshots = [];
        if (file_exists(public_path('icons/icon-512.png'))) {
            $screenshots[] = [
                'src' => 'icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'form_factor' => 'wide',
                'label' => "{$pwaShortName} - Karnataka Mandi Market Rates",
            ];
            $screenshots[] = [
                'src' => 'icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'form_factor' => 'narrow',
                'label' => "{$pwaShortName} - Daily Prices and Forecasts",
            ];
        }

        return [
            'id' => './?source=pwa',
            'name' => $pwaName,
            'short_name' => $pwaShortName,
            'description' => $pwaDesc,
            'start_url' => './?source=pwa',
            'scope' => './',
            'display' => $displayMode,
            'background_color' => $bgColor,
            'theme_color' => $themeColor,
            'orientation' => 'portrait-primary',
            'lang' => 'kn-IN',
            'dir' => 'ltr',
            'categories' => ['agriculture', 'business', 'productivity', 'utilities'],
            'prefer_related_applications' => false,
            'icons' => $icons,
            'shortcuts' => $shortcuts,
            'screenshots' => $screenshots,
        ];
    }

    /**
     * Synchronize the manifest JSON directly to public/manifest.json on disk.
     */
    public function syncDiskManifest(): bool
    {
        try {
            $manifest = $this->generateManifest();
            $path = public_path('manifest.json');
            return (bool) file_put_contents(
                $path,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Return JSON response with proper PWA manifest headers.
     */
    public function response(): JsonResponse
    {
        $manifest = $this->generateManifest();

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
