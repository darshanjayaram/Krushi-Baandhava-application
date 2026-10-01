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
        Schema::table('curated_videos', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->string('growth_stage', 50)->default('general')->after('category');
            $table->string('language', 20)->default('kn')->after('growth_stage');
            $table->unsignedInteger('views_count')->default(0)->after('display_order');

            $table->index(['is_active', 'is_featured']);
            $table->index(['crop_id', 'growth_stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curated_videos', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_featured']);
            $table->dropIndex(['crop_id', 'growth_stage']);

            $table->dropColumn(['is_featured', 'growth_stage', 'language', 'views_count']);
        });
    }
};
