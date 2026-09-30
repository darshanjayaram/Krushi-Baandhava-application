<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class FooterController extends Controller
{
    /**
     * Default links for Column 2 (Platform Hubs) - Minimum 6 items per sub-column (12 items total).
     */
    protected function defaultCol2Links(): array
    {
        return [
            // Sub-column 1 (Left - Top to Bottom)
            ['icon' => '📊', 'label_en' => 'Daily Mandi Rates', 'label_kn' => 'ದೈನಂದಿನ ಮಂಡಿ ದರಗಳು', 'url' => '/crops', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '📈', 'label_en' => 'Price Forecasts', 'label_kn' => 'ದರ ಮುನ್ಸೂಚನೆ & ಟ್ರೆಂಡ್ಸ್', 'url' => '/crops', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🏛️', 'label_en' => 'Govt Schemes', 'label_kn' => 'ಸರ್ಕಾರಿ ಯೋಜನೆಗಳು', 'url' => '/schemes', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🌤️', 'label_en' => 'Weather Radar', 'label_kn' => 'ಹವಾಮಾನ & ಮಳೆ ವರದಿ', 'url' => '/weather', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '▶️', 'label_en' => 'Agri Videos', 'label_kn' => 'ಕೃಷಿ ವೀಡಿಯೊಗಳು', 'url' => '/videos', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '📰', 'label_en' => 'Agri News & Alerts', 'label_kn' => 'ಸುದ್ದಿ & ಮಾರುಕಟ್ಟೆ ಎಚ್ಚರಿಕೆ', 'url' => '/news', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],

            // Sub-column 2 (Right - Top to Bottom)
            ['icon' => '📖', 'label_en' => 'Farming Guides', 'label_kn' => 'ಕೃಷಿ ಕೈಪಿಡಿಗಳು (Guides)', 'url' => '/articles', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🏢', 'label_en' => 'Mandi Directory', 'label_kn' => 'ಮಂಡಿ ವಿವರ', 'url' => '/crops', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🌾', 'label_en' => 'Crop Advisory', 'label_kn' => 'ಬೆಳೆ ಸಲಹೆ & ರಕ್ಷಣೆ', 'url' => '/articles', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '💰', 'label_en' => 'MSP Support Rates', 'label_kn' => 'ಬೆಂಬಲ ಬೆಲೆ (MSP) ವಿವರ', 'url' => '/schemes', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🧪', 'label_en' => 'Soil & Nutrients', 'label_kn' => 'ಮಣ್ಣು & ಪೋಷಕಾಂಶ ನಿರ್ವಹಣೆ', 'url' => '/articles', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
            ['icon' => '🌱', 'label_en' => 'Organic Farming', 'label_kn' => 'ಸಾವಯವ ಕೃಷಿ ಪದ್ಧತಿ', 'url' => '/articles', 'style' => 'link', 'new_tab' => false, 'is_visible' => true],
        ];
    }

    /**
     * Default links for Column 3 (Community & Help).
     */
    protected function defaultCol3Links(): array
    {
        return [
            [
                'icon' => '💬',
                'label_en' => 'Join WhatsApp Community',
                'label_kn' => 'ವಾಟ್ಸಾಪ್ ಸಮುದಾಯಕ್ಕೆ ಸೇರಿ',
                'subtitle_en' => 'Karnataka Rytha Channel',
                'subtitle_kn' => 'ಕರ್ನಾಟಕ ರೈತರ ಸಮುದಾಯ',
                'url' => 'https://whatsapp.com/channel/krushi-baandhava',
                'style' => 'button',
                'new_tab' => true,
                'is_visible' => true
            ],
            [
                'icon' => '✉️',
                'label_en' => 'support@krushibaandhava.org',
                'label_kn' => 'support@krushibaandhava.org',
                'subtitle_en' => 'Email Support',
                'subtitle_kn' => 'ಇಮೇಲ್ ಬೆಂಬಲ',
                'url' => 'mailto:support@krushibaandhava.org',
                'style' => 'chip',
                'new_tab' => false,
                'is_visible' => true
            ],
            [
                'icon' => '⚠️',
                'label_en' => 'Report Mandi Rate Issue',
                'label_kn' => 'ದರ ವ್ಯತ್ಯಾಸ ವರದಿ ಮಾಡಿ',
                'subtitle_en' => 'Report Grievance / Voice',
                'subtitle_kn' => 'ಧ್ವನಿ ಅಥವಾ ಫೋಟೋ ಮೂಲಕ ತಿಳಿಸಿ',
                'url' => '/feedback?mode=issue&category=price_discrepancy',
                'style' => 'alert',
                'new_tab' => false,
                'is_visible' => true
            ]
        ];
    }

    /**
     * Display the Footer CMS management page.
     */
    public function index(): View
    {
        $rawCol2Links = SystemSetting::get('footer_col2_links');
        $col2Links = is_array($rawCol2Links) ? $rawCol2Links : (json_decode($rawCol2Links ?? '', true) ?: $this->defaultCol2Links());

        $rawCol3Links = SystemSetting::get('footer_col3_links');
        $col3Links = is_array($rawCol3Links) ? $rawCol3Links : (json_decode($rawCol3Links ?? '', true) ?: $this->defaultCol3Links());

        $settings = [
            'developer_name' => SystemSetting::get('footer_developer_name', 'Darshan Jayaram'),
            'developer_url' => SystemSetting::get('footer_developer_url', '#'),
            'copyright_text' => SystemSetting::get('footer_copyright_text', '© ' . date('Y') . ' Krushi Baandhava. All rights reserved.'),
            'tagline_en' => SystemSetting::get('footer_tagline_en', 'Karnataka Farmer Market Intelligence Network'),
            'tagline_kn' => SystemSetting::get('footer_tagline_kn', 'ಕರ್ನಾಟಕದ ರೈತ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ ವೇದಿಕೆ'),
            'description_en' => SystemSetting::get('footer_description_en', 'Karnataka agricultural intelligence network — real-time mandi trading prices, modal rates, and predictive crop guidance.'),
            'description_kn' => SystemSetting::get('footer_description_kn', 'ಕರ್ನಾಟಕದ ಸ್ವತಂತ್ರ ರೈತ ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿ ವೇದಿಕೆ — ನೈಜ ಸಮಯದ ಮಂಡಿ ದರಗಳು ಮತ್ತು ಬೆಳೆ ಮುನ್ಸೂಚನೆ.'),
            'telemetry_badge' => SystemSetting::get('footer_telemetry_badge', '31 Districts • 160+ Mandis'),
            'telemetry_source' => SystemSetting::get('footer_telemetry_source', 'Sourced from KRAMA & Agmarknet Feeds'),
            'disclaimer_en' => SystemSetting::get('footer_disclaimer_en', 'Prices are indicative, sourced from public mandi data — verify before trading. Krushi Baandhava is an independent farmer welfare platform and does not represent any government entity.'),
            'disclaimer_kn' => SystemSetting::get('footer_disclaimer_kn', 'ದರಗಳು ಸಾರ್ವಜನಿಕ ಮಂಡಿ ದತ್ತಾಂಶವನ್ನು ಆಧರಿಸಿವೆ — ವ್ಯಾಪಾರದ ಮೊದಲು ಮಂಡಿಯಲ್ಲಿ ಪರಿಶೀಲಿಸಿ. ಕೃಷಿ ಬಾಂಧವ ಸ್ವತಂತ್ರ ರೈತ ಕಲ್ಯಾಣ ವೇದಿಕೆಯಾಗಿದ್ದು, ಯಾವುದೇ ಸರ್ಕಾರಿ ಸಂಸ್ಥೆಯನ್ನು ಪ್ರತಿನಿಧಿಸುವುದಿಲ್ಲ.'),
            'show_telemetry' => (bool) SystemSetting::get('footer_show_telemetry', true),
            'show_disclaimer' => (bool) SystemSetting::get('footer_show_disclaimer', true),
            
            // Dynamic Columns 2 & 3
            'col2_title_en' => SystemSetting::get('footer_col2_title_en', 'Platform Hubs'),
            'col2_title_kn' => SystemSetting::get('footer_col2_title_kn', 'ವೇದಿಕೆ ಕೇಂದ್ರಗಳು'),
            'col2_links' => $col2Links,

            'col3_title_en' => SystemSetting::get('footer_col3_title_en', 'Community & Help'),
            'col3_title_kn' => SystemSetting::get('footer_col3_title_kn', 'ಸಂಪರ್ಕ & ಸಹಾಯ'),
            'col3_links' => $col3Links,
        ];

        return view('admin.footer.index', compact('settings'));
    }

    /**
     * Update the Footer CMS settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'footer_developer_name' => 'required|string|max:100',
            'footer_developer_url' => 'nullable|string|max:255',
            'footer_copyright_text' => 'nullable|string|max:150',
            'footer_tagline_en' => 'nullable|string|max:150',
            'footer_tagline_kn' => 'nullable|string|max:150',
            'footer_description_en' => 'required|string|max:400',
            'footer_description_kn' => 'required|string|max:400',
            'footer_telemetry_badge' => 'nullable|string|max:100',
            'footer_telemetry_source' => 'nullable|string|max:150',
            'footer_disclaimer_en' => 'nullable|string|max:500',
            'footer_disclaimer_kn' => 'nullable|string|max:500',
            'footer_show_telemetry' => 'nullable|boolean',
            'footer_show_disclaimer' => 'nullable|boolean',

            // Columns 2 & 3 Dynamic Data
            'footer_col2_title_en' => 'required|string|max:100',
            'footer_col2_title_kn' => 'required|string|max:100',
            'footer_col2_links' => 'nullable|string',

            'footer_col3_title_en' => 'required|string|max:100',
            'footer_col3_title_kn' => 'required|string|max:100',
            'footer_col3_links' => 'nullable|string',
        ]);

        // String & URL keys
        $stringKeys = [
            'footer_developer_name',
            'footer_developer_url',
            'footer_copyright_text',
            'footer_tagline_en',
            'footer_tagline_kn',
            'footer_description_en',
            'footer_description_kn',
            'footer_telemetry_badge',
            'footer_telemetry_source',
            'footer_disclaimer_en',
            'footer_disclaimer_kn',
            'footer_col2_title_en',
            'footer_col2_title_kn',
            'footer_col3_title_en',
            'footer_col3_title_kn',
        ];

        foreach ($stringKeys as $key) {
            if ($request->has($key)) {
                SystemSetting::set($key, $request->input($key, ''), 'string', 'footer');
            }
        }

        // Process Column 2 JSON Links
        if ($request->has('footer_col2_links')) {
            $rawCol2 = $request->input('footer_col2_links');
            $decodedCol2 = is_array($rawCol2) ? $rawCol2 : json_decode($rawCol2, true);
            if (is_array($decodedCol2)) {
                // Sanitize items
                $cleanCol2 = array_map(function ($item) {
                    return [
                        'icon' => (string) ($item['icon'] ?? '🔗'),
                        'label_en' => (string) ($item['label_en'] ?? 'Link'),
                        'label_kn' => (string) ($item['label_kn'] ?? ''),
                        'url' => (string) ($item['url'] ?? '/'),
                        'style' => (string) ($item['style'] ?? 'link'),
                        'new_tab' => (bool) ($item['new_tab'] ?? false),
                        'is_visible' => (bool) ($item['is_visible'] ?? true),
                    ];
                }, $decodedCol2);
                SystemSetting::set('footer_col2_links', json_encode(array_values($cleanCol2)), 'json', 'footer');
            }
        }

        // Process Column 3 JSON Links / Buttons
        if ($request->has('footer_col3_links')) {
            $rawCol3 = $request->input('footer_col3_links');
            $decodedCol3 = is_array($rawCol3) ? $rawCol3 : json_decode($rawCol3, true);
            if (is_array($decodedCol3)) {
                // Sanitize items
                $cleanCol3 = array_map(function ($item) {
                    return [
                        'icon' => (string) ($item['icon'] ?? '💬'),
                        'label_en' => (string) ($item['label_en'] ?? 'Action'),
                        'label_kn' => (string) ($item['label_kn'] ?? ''),
                        'subtitle_en' => (string) ($item['subtitle_en'] ?? ''),
                        'subtitle_kn' => (string) ($item['subtitle_kn'] ?? ''),
                        'url' => (string) ($item['url'] ?? '#'),
                        'style' => (string) ($item['style'] ?? 'link'), // 'button', 'link', 'alert', 'chip'
                        'new_tab' => (bool) ($item['new_tab'] ?? false),
                        'is_visible' => (bool) ($item['is_visible'] ?? true),
                    ];
                }, $decodedCol3);
                SystemSetting::set('footer_col3_links', json_encode(array_values($cleanCol3)), 'json', 'footer');
            }
        }

        // Boolean toggles
        $booleanKeys = [
            'footer_show_telemetry',
            'footer_show_disclaimer',
        ];

        foreach ($booleanKeys as $key) {
            $val = $request->boolean($key) ? '1' : '0';
            SystemSetting::set($key, $val, 'boolean', 'footer');
        }

        // Clear view and route caches to reflect immediately
        Artisan::call('view:clear');

        return redirect()->route('admin.footer.index')
            ->with('success', 'Footer CMS layout, dynamic columns, and buttons published successfully.');
    }
}
