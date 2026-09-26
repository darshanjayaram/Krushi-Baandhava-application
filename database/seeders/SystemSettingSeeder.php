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
            ['key' => 'application_name', 'value' => 'Krushi Baandhava', 'type' => 'string', 'group' => 'general', 'description' => 'Platform application branding name.'],
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
            ['key' => 'primary_feed_provider', 'value' => 'ceda_agmarknet', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Primary automated APMC price feed driver (ceda_agmarknet, datagov_direct).'],
            ['key' => 'market_sync_interval', 'value' => 'daily', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Default synchronization frequency for mandi prices.'],
            ['key' => 'auto_provision_master_data', 'value' => 'true', 'type' => 'boolean', 'group' => 'data_sources', 'description' => 'Automatically provision newly discovered crops, varieties, and APMC markets from incoming data.gov.in API records.'],
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
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
