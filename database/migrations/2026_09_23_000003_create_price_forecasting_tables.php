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
        Schema::create('forecast_models', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('version', 20)->default('1.0');
            $table->json('parameters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('forecast_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('forecast_models')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 50)->default('running'); // running, completed, failed
            $table->unsignedInteger('total_predictions')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('price_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('forecast_runs')->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained('markets')->nullOnDelete(); // Null = Karnataka State Benchmark
            $table->date('forecast_date'); // Target prediction date
            $table->unsignedSmallInteger('horizon_days'); // 1, 7, 15, 30
            $table->decimal('expected_price', 10, 2);
            $table->decimal('lower_bound', 10, 2);
            $table->decimal('upper_bound', 10, 2);
            $table->decimal('confidence_score', 5, 2)->default(0.00); // 0.00 - 100.00%
            $table->unsignedInteger('data_points_used')->default(0);
            $table->timestamps();

            $table->unique(
                ['crop_id', 'variety_id', 'market_id', 'forecast_date', 'horizon_days'],
                'uq_pf_crop_var_mkt_date_hrz'
            );
            $table->index(['crop_id', 'market_id', 'forecast_date'], 'idx_pf_crop_mkt_date');
            $table->index(['crop_id', 'horizon_days'], 'idx_pf_crop_horizon');
        });

        Schema::create('forecast_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('forecast_models')->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained('markets')->nullOnDelete();
            $table->unsignedSmallInteger('horizon_days');
            $table->decimal('mae', 10, 2)->nullable();
            $table->decimal('rmse', 10, 2)->nullable();
            $table->decimal('mape', 5, 2)->nullable(); // Percentage error
            $table->decimal('directional_accuracy', 5, 2)->nullable(); // Percentage
            $table->timestamps();

            $table->index(['crop_id', 'horizon_days'], 'idx_fm_crop_horizon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_metrics');
        Schema::dropIfExists('price_forecasts');
        Schema::dropIfExists('forecast_runs');
        Schema::dropIfExists('forecast_models');
    }
};
