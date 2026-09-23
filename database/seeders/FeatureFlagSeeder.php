<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flags = [
            ['key' => 'market_prices', 'name' => 'Market Mandi Prices', 'description' => 'Display current APMC market prices and arrival data.', 'is_enabled' => true],
            ['key' => 'price_forecast', 'name' => 'Price Forecasting', 'description' => 'Show 1/7/15/30 day price projections and confidence bounds.', 'is_enabled' => true],
            ['key' => 'best_months', 'name' => 'Best Months to Sell', 'description' => '5-year seasonal index and historical monthly price analysis.', 'is_enabled' => true],
            ['key' => 'where_to_sell', 'name' => 'Where to Sell Mandi Comparison', 'description' => 'Multi-market price and distance comparison matrix.', 'is_enabled' => true],
            ['key' => 'weather', 'name' => 'Weather Forecasts', 'description' => '7-day localized weather forecasts and farming advisories.', 'is_enabled' => true],
            ['key' => 'agriculture_information', 'name' => 'Agriculture Advisory CMS', 'description' => 'Agronomy articles on cultivation, pest control, and fertilizers.', 'is_enabled' => true],
            ['key' => 'schemes', 'name' => 'Government Schemes', 'description' => 'Central and Karnataka state agricultural welfare schemes.', 'is_enabled' => true],
            ['key' => 'news', 'name' => 'Agricultural News', 'description' => 'Latest farming news, policy announcements, and weather alerts.', 'is_enabled' => true],
            ['key' => 'videos', 'name' => 'Farmer Educational Videos', 'description' => 'Curated YouTube farming guides and technical videos.', 'is_enabled' => true],
            ['key' => 'multi_language', 'name' => 'Multilingual Support (Kannada/English)', 'description' => 'Enable dynamic language switching between Kannada and English.', 'is_enabled' => true],
            ['key' => 'notifications', 'name' => 'Push Notifications', 'description' => 'Web push notifications for significant mandi price changes.', 'is_enabled' => false],
            ['key' => 'whatsapp', 'name' => 'WhatsApp Price Alerts', 'description' => 'WhatsApp broadcast integration for daily price alerts.', 'is_enabled' => false],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::firstOrCreate(
                ['key' => $flag['key']],
                $flag
            );
        }
    }
}
