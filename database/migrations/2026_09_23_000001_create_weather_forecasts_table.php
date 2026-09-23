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
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->date('forecast_date')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Current metrics (populated for today's forecast)
            $table->float('current_temperature')->nullable();
            $table->float('current_humidity')->nullable();
            $table->float('current_wind_speed')->nullable();
            $table->integer('current_weather_code')->nullable();

            // Daily forecast metrics
            $table->float('temp_min');
            $table->float('temp_max');
            $table->float('precipitation_probability')->default(0); // 0-100%
            $table->integer('weather_code'); // WMO code
            $table->string('weather_condition_en');
            $table->string('weather_condition_kn');
            $table->string('weather_icon', 20)->default('☀️');

            // Dynamic agricultural advisory
            $table->text('farming_advisory_en')->nullable();
            $table->text('farming_advisory_kn')->nullable();

            $table->json('raw_payload')->nullable();
            $table->timestamp('fetched_at')->useCurrent();
            $table->timestamps();

            // Unique constraint: one forecast per district per day
            $table->unique(['district_id', 'forecast_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};
