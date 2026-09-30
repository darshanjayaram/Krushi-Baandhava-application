<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class NavbarController extends Controller
{
    /**
     * Default links for Desktop Header Navbar.
     * Dedicated desktop-only navigation items.
     */
    protected function defaultDesktopLinks(): array
    {
        return [
            [
                'label_en' => 'Rates',
                'label_kn' => 'ದರಗಳು',
                'url' => '/crops',
                'route_match' => 'home,farmer.crops.*',
                'badge' => '',
                'badge_color' => 'emerald',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'label_en' => 'Schemes',
                'label_kn' => 'ಯೋಜನೆಗಳು',
                'url' => '/schemes',
                'route_match' => 'farmer.schemes.*',
                'badge' => 'GOVT',
                'badge_color' => 'amber',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'label_en' => 'Videos',
                'label_kn' => 'ವಿಡಿಯೋಗಳು',
                'url' => '/videos',
                'route_match' => 'farmer.videos.*',
                'badge' => '',
                'badge_color' => 'emerald',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'label_en' => 'News',
                'label_kn' => 'ಸುದ್ದಿಗಳು',
                'url' => '/news',
                'route_match' => 'farmer.news.*',
                'badge' => 'LIVE',
                'badge_color' => 'rose',
                'new_tab' => false,
                'is_visible' => true,
            ],
        ];
    }

    /**
     * Default links for Mobile Bottom Navbar (Astro-Style Floating Island Dock).
     * Dedicated mobile quick-access tabs with icon support.
     */
    protected function defaultMobileDockLinks(): array
    {
        return [
            [
                'icon' => 'home', // 'home', 'rates', 'schemes', 'weather', 'videos', 'news', 'help', or custom emoji
                'label_en' => 'Home',
                'label_kn' => 'ಮುಖಪುಟ',
                'url' => '/',
                'route_match' => 'home',
                'has_dot' => false,
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => 'rates',
                'label_en' => 'Rates',
                'label_kn' => 'ದರಗಳು',
                'url' => '/crops',
                'route_match' => 'farmer.crops.*',
                'has_dot' => true,
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => 'schemes',
                'label_en' => 'Schemes',
                'label_kn' => 'ಯೋಜನೆಗಳು',
                'url' => '/schemes',
                'route_match' => 'farmer.schemes.*',
                'has_dot' => false,
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => 'weather',
                'label_en' => 'Weather',
                'label_kn' => 'ಹವಾಮಾನ',
                'url' => '/weather',
                'route_match' => 'farmer.weather.*',
                'has_dot' => false,
                'new_tab' => false,
                'is_visible' => true,
            ],
        ];
    }

    /**
     * Default links for Mobile Slide-Over Hamburger Drawer Menu.
     * Full mobile directory navigation list.
     */
    protected function defaultDrawerLinks(): array
    {
        return [
            [
                'icon' => '🌾',
                'label_en' => 'Home & Live Mandi Rates',
                'label_kn' => 'ಮುಖಪುಟ & ಲೈವ್ ಮಂಡಿ ದರ',
                'subtitle_en' => 'Karnataka APMC live prices',
                'subtitle_kn' => 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ದರಗಳು',
                'url' => '/',
                'badge' => '',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => '📊',
                'label_en' => 'All Mandi Commodities',
                'label_kn' => 'ಎಲ್ಲಾ ಕೃಷಿ ಸರಕುಗಳು',
                'subtitle_en' => 'Vegetables, Grains, Arecanut',
                'subtitle_kn' => 'ತರಕಾರಿ, ಧಾನ್ಯ, ಅಡಿಕೆ, ವಾಣಿಜ್ಯ ಬೆಳೆ',
                'url' => '/crops',
                'badge' => 'LIVE',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => '🏛️',
                'label_en' => 'Government Schemes (ಯೋಜನೆಗಳು)',
                'label_kn' => 'ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು',
                'subtitle_en' => 'Subsidies, grants & loans',
                'subtitle_kn' => 'ಸಬ್ಸಿಡಿ, ಸಹಾಯಧನ ಮತ್ತು ಸಾಲ ಸೌಲಭ್ಯ',
                'url' => '/schemes',
                'badge' => 'NEW',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => '▶️',
                'label_en' => 'Educational Agri Videos',
                'label_kn' => 'ಕೃಷಿ ಮಾಹಿತಿ ವಿಡಿಯೋಗಳು',
                'subtitle_en' => 'Modern farming guides & tips',
                'subtitle_kn' => 'ಆಧುನಿಕ ಕೃಷಿ ತಂತ್ರಜ್ಞಾನ & ಸಲಹೆಗಳು',
                'url' => '/videos',
                'badge' => '',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => '📰',
                'label_en' => 'Agricultural News & Alerts',
                'label_kn' => 'ಕೃಷಿ ಸುದ್ದಿಗಳು & ಮುನ್ಸೂಚನೆ',
                'subtitle_en' => 'Weather advisories & policy news',
                'subtitle_kn' => 'ಹವಾಮಾನ ಎಚ್ಚರಿಕೆ & ಕೃಷಿ ವರದಿಗಳು',
                'url' => '/news',
                'badge' => '',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => '📖',
                'label_en' => 'Farming Guides & Advisory',
                'label_kn' => 'ಕೃಷಿ ಕೈಪಿಡಿ & ಮಾರ್ಗದರ್ಶಿ',
                'subtitle_en' => 'Pest control & soil advisory',
                'subtitle_kn' => 'ಕೀಟ ನಿಯಂತ್ರಣ ಮತ್ತು ಮಣ್ಣು ಪರೀಕ್ಷೆ',
                'url' => '/articles',
                'badge' => '',
                'new_tab' => false,
                'is_visible' => true,
            ],
            [
                'icon' => '💬',
                'label_en' => 'Report Price Issue / Feedback',
                'label_kn' => 'ದರ ವ್ಯತ್ಯಾಸ ವರದಿ / ಸಲಹೆ ನೀಡಿ',
                'subtitle_en' => 'Voice note & grievance portal',
                'subtitle_kn' => 'ಧ್ವನಿ ಅಥವಾ ಫೋಟೋ ಮೂಲಕ ತಿಳಿಸಿ',
                'url' => '/feedback',
                'badge' => 'HELP',
                'new_tab' => false,
                'is_visible' => true,
            ],
        ];
    }

    /**
     * Display the Navbar & Menus CMS management dashboard.
     */
    public function index(): View
    {
        // 1. Desktop Navbar Links
        $rawDesktop = SystemSetting::get('navbar_desktop_links');
        $desktopLinks = is_array($rawDesktop) ? $rawDesktop : (json_decode($rawDesktop ?? '', true) ?: $this->defaultDesktopLinks());

        // 2. Mobile Bottom Dock Links
        $rawDock = SystemSetting::get('navbar_mobile_dock_links');
        $mobileDockLinks = is_array($rawDock) ? $rawDock : (json_decode($rawDock ?? '', true) ?: $this->defaultMobileDockLinks());

        // 3. Mobile Hamburger Drawer Links
        $rawDrawer = SystemSetting::get('navbar_drawer_links');
        $drawerLinks = is_array($rawDrawer) ? $rawDrawer : (json_decode($rawDrawer ?? '', true) ?: $this->defaultDrawerLinks());

        // Settings bundle
        $settings = [
            // Section 1: Desktop Navbar
            'desktop_links' => $desktopLinks,
            'show_location_pill' => (bool) SystemSetting::get('navbar_show_location_pill', true),
            'show_language_toggle' => (bool) SystemSetting::get('navbar_show_language_toggle', true),
            'show_hamburger_button' => (bool) SystemSetting::get('navbar_show_hamburger_button', true),

            // Section 2: Mobile Bottom Dock
            'mobile_dock_links' => $mobileDockLinks,
            'mobile_dock_style' => SystemSetting::get('navbar_mobile_dock_style', 'floating'), // 'floating' or 'fixed'
            'mobile_dock_show_labels' => (bool) SystemSetting::get('navbar_mobile_dock_show_labels', true),

            // Section 3: Hamburger Drawer
            'drawer_links' => $drawerLinks,
            'drawer_show_district' => (bool) SystemSetting::get('navbar_drawer_show_district', true),
            'drawer_show_whatsapp' => (bool) SystemSetting::get('navbar_drawer_show_whatsapp', true),
            'drawer_whatsapp_url' => SystemSetting::get('navbar_drawer_whatsapp_url', 'https://chat.whatsapp.com/sample-farmer-group'),
            'drawer_whatsapp_label_en' => SystemSetting::get('navbar_drawer_whatsapp_label_en', 'Join WhatsApp Farmer Helpdesk'),
            'drawer_whatsapp_label_kn' => SystemSetting::get('navbar_drawer_whatsapp_label_kn', 'ವಾಟ್ಸಾಪ್ ರೈತರ ಸಹಾಯವಾಣಿಗೆ ಸೇರಿ'),
            'drawer_show_pwa' => (bool) SystemSetting::get('navbar_drawer_show_pwa', true),

            // Section 4: Floating Feedback Action Button (FAB)
            'show_feedback_fab' => (bool) SystemSetting::get('navbar_show_feedback_fab', true),
            'feedback_fab_pulse' => (bool) SystemSetting::get('navbar_feedback_fab_pulse', true),
            'feedback_fab_url' => SystemSetting::get('navbar_feedback_fab_url', '/feedback'),
        ];

        return view('admin.navbar.index', compact('settings'));
    }

    /**
     * Update the Navbar & Menus CMS settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'navbar_desktop_links' => 'nullable|string',
            'navbar_mobile_dock_links' => 'nullable|string',
            'navbar_drawer_links' => 'nullable|string',
            'navbar_mobile_dock_style' => 'nullable|string|in:floating,fixed',
            'navbar_drawer_whatsapp_url' => 'nullable|string|max:255',
            'navbar_drawer_whatsapp_label_en' => 'nullable|string|max:100',
            'navbar_drawer_whatsapp_label_kn' => 'nullable|string|max:100',
            'navbar_feedback_fab_url' => 'nullable|string|max:255',
        ]);

        // 1. Process Desktop Links
        if ($request->has('navbar_desktop_links')) {
            $raw = $request->input('navbar_desktop_links');
            $decoded = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($decoded)) {
                $clean = array_map(function ($item) {
                    return [
                        'label_en' => (string) ($item['label_en'] ?? 'Link'),
                        'label_kn' => (string) ($item['label_kn'] ?? ''),
                        'url' => (string) ($item['url'] ?? '/'),
                        'route_match' => (string) ($item['route_match'] ?? ''),
                        'badge' => (string) ($item['badge'] ?? ''),
                        'badge_color' => in_array($item['badge_color'] ?? '', ['emerald', 'rose', 'amber', 'cyan']) ? $item['badge_color'] : 'emerald',
                        'new_tab' => (bool) ($item['new_tab'] ?? false),
                        'is_visible' => (bool) ($item['is_visible'] ?? true),
                    ];
                }, $decoded);
                SystemSetting::set('navbar_desktop_links', json_encode(array_values($clean)), 'json', 'navbar');
            }
        }

        // 2. Process Mobile Bottom Dock Links
        if ($request->has('navbar_mobile_dock_links')) {
            $raw = $request->input('navbar_mobile_dock_links');
            $decoded = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($decoded)) {
                $clean = array_map(function ($item) {
                    return [
                        'icon' => (string) ($item['icon'] ?? 'home'),
                        'label_en' => (string) ($item['label_en'] ?? 'Home'),
                        'label_kn' => (string) ($item['label_kn'] ?? ''),
                        'url' => (string) ($item['url'] ?? '/'),
                        'route_match' => (string) ($item['route_match'] ?? ''),
                        'has_dot' => (bool) ($item['has_dot'] ?? false),
                        'new_tab' => (bool) ($item['new_tab'] ?? false),
                        'is_visible' => (bool) ($item['is_visible'] ?? true),
                    ];
                }, $decoded);
                SystemSetting::set('navbar_mobile_dock_links', json_encode(array_values($clean)), 'json', 'navbar');
            }
        }

        // 3. Process Hamburger Drawer Links
        if ($request->has('navbar_drawer_links')) {
            $raw = $request->input('navbar_drawer_links');
            $decoded = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($decoded)) {
                $clean = array_map(function ($item) {
                    return [
                        'icon' => (string) ($item['icon'] ?? '🌾'),
                        'label_en' => (string) ($item['label_en'] ?? 'Item'),
                        'label_kn' => (string) ($item['label_kn'] ?? ''),
                        'subtitle_en' => (string) ($item['subtitle_en'] ?? ''),
                        'subtitle_kn' => (string) ($item['subtitle_kn'] ?? ''),
                        'url' => (string) ($item['url'] ?? '/'),
                        'badge' => (string) ($item['badge'] ?? ''),
                        'new_tab' => (bool) ($item['new_tab'] ?? false),
                        'is_visible' => (bool) ($item['is_visible'] ?? true),
                    ];
                }, $decoded);
                SystemSetting::set('navbar_drawer_links', json_encode(array_values($clean)), 'json', 'navbar');
            }
        }

        // 4. Process Boolean Component Toggles
        $booleanKeys = [
            'navbar_show_location_pill',
            'navbar_show_language_toggle',
            'navbar_show_hamburger_button',
            'navbar_mobile_dock_show_labels',
            'navbar_drawer_show_district',
            'navbar_drawer_show_whatsapp',
            'navbar_drawer_show_pwa',
            'navbar_show_feedback_fab',
            'navbar_feedback_fab_pulse',
        ];

        foreach ($booleanKeys as $key) {
            $val = $request->boolean($key) ? '1' : '0';
            SystemSetting::set($key, $val, 'boolean', 'navbar');
        }

        // 5. Process String Settings
        $stringKeys = [
            'navbar_mobile_dock_style',
            'navbar_drawer_whatsapp_url',
            'navbar_drawer_whatsapp_label_en',
            'navbar_drawer_whatsapp_label_kn',
            'navbar_feedback_fab_url',
        ];

        foreach ($stringKeys as $key) {
            if ($request->has($key)) {
                SystemSetting::set($key, $request->input($key, ''), 'string', 'navbar');
            }
        }

        // Clear compiled views to reflect instantly
        Artisan::call('view:clear');

        return redirect()->route('admin.navbar.index')
            ->with('success', 'Navbar, Mobile Bottom Dock, and Hamburger Drawer navigation menus published successfully!');
    }

    /**
     * Reset either all sections or a specific section back to factory defaults.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $section = $request->input('section', 'all');

        if ($section === 'all' || $section === 'desktop') {
            SystemSetting::set('navbar_desktop_links', json_encode($this->defaultDesktopLinks()), 'json', 'navbar');
            SystemSetting::set('navbar_show_location_pill', '1', 'boolean', 'navbar');
            SystemSetting::set('navbar_show_language_toggle', '1', 'boolean', 'navbar');
            SystemSetting::set('navbar_show_hamburger_button', '1', 'boolean', 'navbar');
        }

        if ($section === 'all' || $section === 'mobile_dock') {
            SystemSetting::set('navbar_mobile_dock_links', json_encode($this->defaultMobileDockLinks()), 'json', 'navbar');
            SystemSetting::set('navbar_mobile_dock_style', 'floating', 'string', 'navbar');
            SystemSetting::set('navbar_mobile_dock_show_labels', '1', 'boolean', 'navbar');
        }

        if ($section === 'all' || $section === 'drawer') {
            SystemSetting::set('navbar_drawer_links', json_encode($this->defaultDrawerLinks()), 'json', 'navbar');
            SystemSetting::set('navbar_drawer_show_district', '1', 'boolean', 'navbar');
            SystemSetting::set('navbar_drawer_show_whatsapp', '1', 'boolean', 'navbar');
            SystemSetting::set('navbar_drawer_show_pwa', '1', 'boolean', 'navbar');
            SystemSetting::set('navbar_drawer_whatsapp_url', 'https://chat.whatsapp.com/sample-farmer-group', 'string', 'navbar');
            SystemSetting::set('navbar_drawer_whatsapp_label_en', 'Join WhatsApp Farmer Helpdesk', 'string', 'navbar');
            SystemSetting::set('navbar_drawer_whatsapp_label_kn', 'ವಾಟ್ಸಾಪ್ ರೈತರ ಸಹಾಯವಾಣಿಗೆ ಸೇರಿ', 'string', 'navbar');
        }

        if ($section === 'all' || $section === 'feedback') {
            SystemSetting::set('navbar_show_feedback_fab', '1', 'boolean', 'navbar');
            SystemSetting::set('navbar_feedback_fab_pulse', '1', 'boolean', 'navbar');
            SystemSetting::set('navbar_feedback_fab_url', '/feedback', 'string', 'navbar');
        }

        Artisan::call('view:clear');

        return redirect()->route('admin.navbar.index')
            ->with('success', 'Navigation menus successfully reset to standard factory presets.');
    }
}
