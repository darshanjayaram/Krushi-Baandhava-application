<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'application_name', 'value' => 'Krushi Baandhava', 'type' => 'string', 'group' => 'general', 'description' => 'Platform application branding name in English.'],
            ['key' => 'application_name_kn', 'value' => 'ಕೃಷಿ ಬಾಂಧವ', 'type' => 'string', 'group' => 'general', 'description' => 'Platform application branding name in Kannada (ಕನ್ನಡ ಹೆಸರು).'],
            ['key' => 'navbar_subtitle_en', 'value' => 'Direct APMC Market Rates & Forecast', 'type' => 'string', 'group' => 'general', 'description' => 'Navbar Subtitle in English.'],
            ['key' => 'navbar_subtitle_kn', 'value' => 'ನೇರ ಮಾರುಕಟ್ಟೆ ದರ ಮತ್ತು ರೈತ ಮುನ್ಸೂಚನೆ', 'type' => 'string', 'group' => 'general', 'description' => 'Navbar Subtitle in Kannada (ನ್ಯಾವ್‌ಬಾರ್ ಉಪ-ಶೀರ್ಷಿಕೆ).'],
            ['key' => 'hero_headline_kn', 'value' => 'ಬೆವರ ಹನಿಗೆ ಸಿಗಲಿ ತಕ್ಕ ಪ್ರತಿಫಲ, ರೈತನ ಕೈಲಿರಲಿ ಮಾರುಕಟ್ಟೆಯ ಬಲ', 'type' => 'string', 'group' => 'general', 'description' => 'Homepage Hero Banner Headline in Kannada (ರೈತರ ಮುಖಪುಟದ ಶೀರ್ಷಿಕೆ).'],
            ['key' => 'hero_headline_en', 'value' => "Let every drop of sweat earn its true reward; let market strength be in the farmer's hands", 'type' => 'string', 'group' => 'general', 'description' => 'Homepage Hero Banner Headline in English.'],
            ['key' => 'hero_subtitle_kn', 'value' => 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ಎಪಿಎಂಸಿ ಮಂಡಿಗಳ ಇಂದಿನ ನೇರ ದರ ಮತ್ತು ದರ ಮುನ್ಸೂಚನೆ.', 'type' => 'string', 'group' => 'general', 'description' => 'Homepage Hero Banner Subtitle in Kannada (ರೈತರ ಮುಖಪುಟದ ಉಪ-ಶೀರ್ಷಿಕೆ).'],
            ['key' => 'hero_subtitle_en', 'value' => 'Live prices and future trends from all Karnataka APMC mandis.', 'type' => 'string', 'group' => 'general', 'description' => 'Homepage Hero Banner Subtitle in English.'],
            ['key' => 'default_state', 'value' => 'Karnataka', 'type' => 'string', 'group' => 'localization', 'description' => 'Default state for market filtering.'],
            ['key' => 'default_district', 'value' => 'Shivamogga', 'type' => 'string', 'group' => 'localization', 'description' => 'Default fallback district when location is unavailable.'],
            ['key' => 'default_language', 'value' => 'kn', 'type' => 'string', 'group' => 'localization', 'description' => 'Default platform language (kn = Kannada, en = English).'],
            ['key' => 'forecast_minimum_observations', 'value' => '30', 'type' => 'integer', 'group' => 'forecasting', 'description' => 'Minimum historical price observations required before producing a forecast.'],
            ['key' => 'forecast_horizons', 'value' => json_encode([1, 7, 15, 30]), 'type' => 'json', 'group' => 'forecasting', 'description' => 'Active forecasting projection horizons in days.'],
            ['key' => 'forecast_confidence_threshold', 'value' => '70', 'type' => 'integer', 'group' => 'forecasting', 'description' => 'Minimum confidence percentage required to display forecast on farmer mobile screen.'],
            ['key' => 'forecasting_engine', 'value' => 'holts_linear_trend', 'type' => 'string', 'group' => 'forecasting', 'description' => 'Active mathematical projection algorithm (holts_linear_trend, seasonal_decomposition, moving_average_weighted).'],
            ['key' => 'seasonality_years', 'value' => '5', 'type' => 'integer', 'group' => 'forecasting', 'description' => 'Number of historical years evaluated for seasonal index computation.'],
            ['key' => 'weather_sync_interval', 'value' => 'twice_daily', 'type' => 'string', 'group' => 'weather', 'description' => 'Frequency of background weather sync jobs (hourly, twice_daily, daily, manual).'],
            ['key' => 'weather_provider', 'value' => 'open_meteo', 'type' => 'string', 'group' => 'weather', 'description' => 'Active meteorological data provider driver (open_meteo, imd_mausam, custom_rest).'],
            ['key' => 'weather_api_endpoint', 'value' => 'https://api.open-meteo.com/v1/forecast', 'type' => 'string', 'group' => 'weather', 'description' => 'Base API endpoint URL for fetching weather forecasts and meteorological observations.'],
            ['key' => 'weather_timezone', 'value' => 'Asia/Kolkata', 'type' => 'string', 'group' => 'weather', 'description' => 'Standard timezone string applied to forecast date boundaries and daily aggregations.'],
            ['key' => 'weather_advisory_mode', 'value' => 'standard_agronomic', 'type' => 'string', 'group' => 'weather', 'description' => 'Agricultural advisory sensitivity mode (standard_agronomic, strict_monsoon_alert, conservative).'],
            ['key' => 'weather_fallback_strategy', 'value' => 'use_cached_last_known', 'type' => 'string', 'group' => 'weather', 'description' => 'Behavior when weather API is unreachable or rate-limited (use_cached_last_known, fallback_district_centroid, suppress_forecast).'],
            ['key' => 'weather_units_system', 'value' => 'metric_celsius', 'type' => 'string', 'group' => 'weather', 'description' => 'Measurement units system for temperature, wind, and precipitation (metric_celsius, imperial_standard).'],
            ['key' => 'primary_feed_provider', 'value' => 'krama_karnataka', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Primary automated APMC price feed driver (krama_karnataka, agmarknet_official).'],
            ['key' => 'cron_market_morning_time', 'value' => '06:00', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Morning automated APMC mandi arrivals sync time in IST.'],
            ['key' => 'cron_market_afternoon_time', 'value' => '12:30', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Mid-day perishable APMC vegetable and fruit auctions sync time in IST.'],
            ['key' => 'cron_market_evening_time', 'value' => '19:30', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Evening final APMC closing auction rates sync time in IST.'],
            ['key' => 'cron_market_enable_hourly', 'value' => '1', 'type' => 'boolean', 'group' => 'data_sources', 'description' => 'Enable hourly price refreshes during active market trading hours.'],
            ['key' => 'cron_market_operating_days', 'value' => 'mon_sat', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Operating days for APMC price ingestion (mon_sat skips Sunday Mandi holiday).'],
            ['key' => 'market_sync_interval', 'value' => 'daily', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Default synchronization frequency for mandi prices.'],
            ['key' => 'auto_provision_master_data', 'value' => 'true', 'type' => 'boolean', 'group' => 'data_sources', 'description' => 'Automatically provision newly discovered crops, varieties, and APMC markets from incoming data.gov.in API records.'],
            ['key' => 'crop_price_staleness_days', 'value' => '14', 'type' => 'integer', 'group' => 'price_freshness', 'description' => 'Global default maximum age in days before APMC market prices and varieties are considered stale and hidden on the crop page.'],
            ['key' => 'category_price_staleness_days', 'value' => json_encode([
                'vegetables' => 7,
                'fruits' => 7,
                'cereals-millets' => 21,
                'pulses' => 21,
                'commercial-plantation' => 30,
                'spices' => 30,
                'oilseeds' => 30,
                'commercial-crops' => 30,
            ]), 'type' => 'json', 'group' => 'price_freshness', 'description' => 'Dynamic staleness window thresholds in days by agricultural crop category.'],
            ['key' => 'cache_duration', 'value' => '3600', 'type' => 'integer', 'group' => 'performance', 'description' => 'Default cache lifetime in seconds for public aggregate data.'],
            ['key' => 'pagination_limit', 'value' => '20', 'type' => 'integer', 'group' => 'general', 'description' => 'Default items per page for listings.'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'maintenance', 'description' => 'Toggle administrative maintenance mode.'],
            // Progressive Web App (PWA) & Mobile Installation
            ['key' => 'pwa_name', 'value' => 'Krushi Baandhava - Farmer APMC Market Intelligence', 'type' => 'string', 'group' => 'pwa', 'description' => 'PWA full application name displayed on mobile banners and app stores.'],
            ['key' => 'pwa_short_name', 'value' => 'KrushiBaandhava', 'type' => 'string', 'group' => 'pwa', 'description' => 'Short label shown directly under mobile home screen icon.'],
            ['key' => 'pwa_description', 'value' => 'Real-time Karnataka APMC mandi prices, live arrival rates, price projections, weather advisories, and nearest market discovery.', 'type' => 'string', 'group' => 'pwa', 'description' => 'Description included in manifest and mobile installer prompts.'],
            ['key' => 'pwa_theme_color', 'value' => '#047857', 'type' => 'string', 'group' => 'pwa', 'description' => 'Mobile browser status bar theme color.'],
            ['key' => 'pwa_background_color', 'value' => '#064e3b', 'type' => 'string', 'group' => 'pwa', 'description' => 'Mobile app splash launch screen background color.'],
            ['key' => 'pwa_start_url', 'value' => '/?source=pwa', 'type' => 'string', 'group' => 'pwa', 'description' => 'Initial route loaded when PWA icon is opened.'],
            ['key' => 'pwa_display_mode', 'value' => 'standalone', 'type' => 'string', 'group' => 'pwa', 'description' => 'PWA display mode (standalone, fullscreen, minimal-ui, browser).'],
            ['key' => 'pwa_banner_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'pwa', 'description' => 'Show in-app mobile installation prompt banner to visiting farmers.'],
            ['key' => 'pwa_icon', 'value' => '/icons/icon-512.svg', 'type' => 'string', 'group' => 'pwa', 'description' => 'Application 512x512 mobile installation home icon.'],
            // PWA Push Notifications & Schedulers
            ['key' => 'pwa_push_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'pwa', 'description' => 'Master switch for PWA web push notifications.'],
            ['key' => 'vapid_public_key', 'value' => 'BHQ7JNFx2IRv46UUSOBWIpDOv4t8fYtYXBz6kofw8okad1NH18T8TSKf5DoPJrEcNqmdvDrsEG7Zzo-fgr-XfGw', 'type' => 'string', 'group' => 'pwa', 'description' => 'VAPID Application Server Public Key (P-256 base64url).'],
            ['key' => 'vapid_private_key', 'value' => 'gdvsE-WHW7_hR0gQdRNbWbFnChNxhGgcJmZijahw3MM', 'type' => 'string', 'group' => 'pwa', 'description' => 'VAPID Application Server Private Key (secret).'],
            ['key' => 'vapid_subject', 'value' => 'mailto:contact@krushibaandhava.in', 'type' => 'string', 'group' => 'pwa', 'description' => 'VAPID Subject mailto or URL for push services.'],
            ['key' => 'pwa_auto_rates_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'pwa', 'description' => 'Enable automated daily evening APMC market rates notification.'],
            ['key' => 'pwa_auto_rates_time', 'value' => '18:30', 'type' => 'string', 'group' => 'pwa', 'description' => 'Scheduled time (IST) to broadcast daily APMC market rates summary.'],
            ['key' => 'pwa_auto_weather_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'pwa', 'description' => 'Enable automated morning weather advisory notification.'],
            ['key' => 'pwa_auto_weather_time', 'value' => '07:00', 'type' => 'string', 'group' => 'pwa', 'description' => 'Scheduled time (IST) to broadcast morning weather alert.'],
            ['key' => 'pwa_auto_forecast_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'pwa', 'description' => 'Enable automated weekly Monday market trend outlook notification.'],
            ['key' => 'pwa_auto_forecast_time', 'value' => '08:00', 'type' => 'string', 'group' => 'pwa', 'description' => 'Scheduled time (IST) on Monday to broadcast weekly outlook.'],
            ['key' => 'pwa_auto_scheme_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'pwa', 'description' => 'Automatically notify subscribers when a new government scheme is published.'],
            // Interactive Route Maps & Geolocation
            ['key' => 'map_api_key', 'value' => '', 'type' => 'string', 'group' => 'maps', 'description' => 'CARTO Basemaps API key for authenticating route and mandi map tile requests.'],
            ['key' => 'map_tile_provider', 'value' => 'carto_voyager', 'type' => 'string', 'group' => 'maps', 'description' => 'Active interactive route map tile provider (carto_voyager, carto_positron, osm_standard, custom).'],
            ['key' => 'map_custom_tile_url', 'value' => '', 'type' => 'string', 'group' => 'maps', 'description' => 'Optional custom map tile server URL pattern (e.g. https://{s}.tile.example.com/{z}/{x}/{y}.png).'],
        ];

        foreach ($settings as $setting) {
            $existing = SystemSetting::where('key', $setting['key'])->first();
            if ($existing) {
                $existing->update([
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                ]);
            } else {
                SystemSetting::create($setting);
            }
        }
    }
}
