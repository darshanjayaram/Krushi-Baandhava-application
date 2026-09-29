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
        Schema::table('market_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('market_prices', 'grade')) {
                $table->string('grade', 50)->nullable()->default('Average')->after('unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            if (Schema::hasColumn('market_prices', 'grade')) {
                $table->dropColumn('grade');
            }
        });
    }
};
