<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Move seasonality_years to best_months_to_sell group
        $seasonalitySetting = SystemSetting::where('key', 'seasonality_years')->first();
        if ($seasonalitySetting) {
            $seasonalitySetting->update([
                'group' => 'best_months_to_sell',
                'type' => 'integer',
                'description' => 'Number of historical years evaluated to compute monthly seasonal price indices and peak harvest selling months.'
            ]);
        } else {
            SystemSetting::create([
                'key' => 'seasonality_years',
                'value' => '5',
                'type' => 'integer',
                'group' => 'best_months_to_sell',
                'description' => 'Number of historical years evaluated to compute monthly seasonal price indices and peak harvest selling months.'
            ]);
        }

        // 2. Ensure forecast_minimum_observations belongs to forecasting group
        $minObs = SystemSetting::where('key', 'forecast_minimum_observations')->first();
        if ($minObs) {
            $minObs->update([
                'group' => 'forecasting',
                'type' => 'integer',
                'description' => 'Minimum historical price observations required before producing a forecast.'
            ]);
        } else {
            SystemSetting::create([
                'key' => 'forecast_minimum_observations',
                'value' => '30',
                'type' => 'integer',
                'group' => 'forecasting',
                'description' => 'Minimum historical price observations required before producing a forecast.'
            ]);
        }

        // 3. Ensure forecast_confidence_threshold belongs to forecasting group
        $conf = SystemSetting::where('key', 'forecast_confidence_threshold')->first();
        if ($conf) {
            $conf->update([
                'group' => 'forecasting',
                'type' => 'integer',
                'description' => 'Minimum confidence percentage required to display forecast on farmer mobile screen.'
            ]);
        } else {
            SystemSetting::create([
                'key' => 'forecast_confidence_threshold',
                'value' => '70',
                'type' => 'integer',
                'group' => 'forecasting',
                'description' => 'Minimum confidence percentage required to display forecast on farmer mobile screen.'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'seasonality_years')->update(['group' => 'general']);
        SystemSetting::where('key', 'forecast_minimum_observations')->update(['group' => 'general']);
        SystemSetting::where('key', 'forecast_confidence_threshold')->update(['group' => 'general']);
    }
};
