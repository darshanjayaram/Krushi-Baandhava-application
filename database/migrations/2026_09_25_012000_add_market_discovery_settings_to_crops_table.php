<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->unsignedInteger('market_radius_km')->default(300)->after('price_source_type')
                ->comment('Maximum distance radius in KM to show in VIEW DIFFERENT MARKET (0 or >=500 = No Limit)');
            $table->string('default_market_sort', 30)->default('nearest_first')->after('market_radius_km')
                ->comment('Default ordering for VIEW DIFFERENT MARKET: nearest_first or highest_price_first');
            $table->boolean('allow_user_sort_toggle')->default(true)->after('default_market_sort')
                ->comment('Whether to display the Nearest First / Highest Price toggle switch on the website');
            $table->boolean('enable_smart_badges')->default(true)->after('allow_user_sort_toggle')
                ->comment('Whether to render automatic Nearest and Top Rate badges on market pills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn([
                'market_radius_km',
                'default_market_sort',
                'allow_user_sort_toggle',
                'enable_smart_badges',
            ]);
        });
    }
};
