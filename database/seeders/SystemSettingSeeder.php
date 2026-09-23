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
            ['key' => 'default_state', 'value' => 'Karnataka', 'type' => 'string', 'group' => 'localization', 'description' => 'Default state for market filtering.'],
            ['key' => 'default_district', 'value' => 'Shivamogga', 'type' => 'string', 'group' => 'localization', 'description' => 'Default fallback district when location is unavailable.'],
            ['key' => 'default_language', 'value' => 'kn', 'type' => 'string', 'group' => 'localization', 'description' => 'Default platform language (kn = Kannada, en = English).'],
            ['key' => 'forecast_minimum_observations', 'value' => '30', 'type' => 'integer', 'group' => 'forecasting', 'description' => 'Minimum historical price observations required before producing a forecast.'],
            ['key' => 'forecast_horizons', 'value' => json_encode([1, 7, 15, 30]), 'type' => 'json', 'group' => 'forecasting', 'description' => 'Active forecasting projection horizons in days.'],
            ['key' => 'seasonality_years', 'value' => '5', 'type' => 'integer', 'group' => 'analytics', 'description' => 'Number of historical years evaluated for seasonal index computation.'],
            ['key' => 'weather_sync_interval', 'value' => 'twice_daily', 'type' => 'string', 'group' => 'weather', 'description' => 'Frequency of background weather sync jobs.'],
            ['key' => 'market_sync_interval', 'value' => 'daily', 'type' => 'string', 'group' => 'data_sources', 'description' => 'Default synchronization frequency for mandi prices.'],
            ['key' => 'cache_duration', 'value' => '3600', 'type' => 'integer', 'group' => 'performance', 'description' => 'Default cache lifetime in seconds for public aggregate data.'],
            ['key' => 'pagination_limit', 'value' => '20', 'type' => 'integer', 'group' => 'general', 'description' => 'Default items per page for listings.'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'general', 'description' => 'Toggle administrative maintenance mode.'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
