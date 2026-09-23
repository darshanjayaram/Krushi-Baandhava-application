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
        Schema::create('price_daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->date('record_date');
            $table->decimal('avg_modal_price', 10, 2);
            $table->decimal('min_modal_price', 10, 2);
            $table->decimal('max_modal_price', 10, 2);
            $table->decimal('total_arrival_quantity', 14, 2)->default(0);
            $table->unsignedInteger('active_markets_count')->default(1);
            $table->timestamps();

            $table->unique(['crop_id', 'variety_id', 'state_id', 'record_date'], 'uq_pds_crop_var_state_date');
            $table->index(['crop_id', 'record_date'], 'idx_pds_crop_date');
            $table->index(['state_id', 'record_date'], 'idx_pds_state_date');
        });

        Schema::create('price_monthly_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained('markets')->nullOnDelete(); // Nullable = State-level aggregate
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1 - 12
            $table->decimal('avg_modal_price', 10, 2);
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->decimal('seasonal_index', 6, 4)->nullable(); // e.g. 1.1520 = 15.2% above annual baseline
            $table->unsignedInteger('observations_count')->default(1);
            $table->decimal('total_arrivals', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['crop_id', 'variety_id', 'market_id', 'year', 'month'], 'uq_pms_crop_var_mkt_yr_mo');
            $table->index(['crop_id', 'year', 'month'], 'idx_pms_crop_period');
            $table->index(['market_id', 'year', 'month'], 'idx_pms_market_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_monthly_statistics');
        Schema::dropIfExists('price_daily_statistics');
    }
};
