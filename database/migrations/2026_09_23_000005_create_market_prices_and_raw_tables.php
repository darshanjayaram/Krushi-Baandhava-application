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
        // 1. Raw Market Price Feed Table (Two-tier storage)
        Schema::create('market_price_raw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->string('external_record_id', 150)->nullable();
            $table->json('payload');
            $table->char('checksum', 64)->index(); // SHA-256 of normalized record JSON
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_status', 50)->default('pending'); // pending, processed, duplicate, rejected, failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['data_source_id', 'processing_status']);
        });

        // 2. Canonical Market Prices Table (Clean, normalized, indexed)
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->date('price_date');
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->decimal('modal_price', 10, 2);
            $table->decimal('arrival_quantity', 12, 2)->default(0.00);
            $table->string('unit', 50)->default('Quintal');
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->foreignId('raw_record_id')->nullable()->constrained('market_price_raw')->nullOnDelete();
            $table->timestamps();

            // Unique constraint to enforce zero-duplicates in canonical dataset
            $table->unique(
                ['crop_id', 'variety_id', 'market_id', 'price_date', 'data_source_id'],
                'uniq_crop_var_mkt_date_src'
            );

            // Composite performance indexes
            $table->index(['crop_id', 'market_id', 'price_date'], 'idx_crop_market_date');
            $table->index(['market_id', 'price_date'], 'idx_market_date');
            $table->index(['crop_id', 'price_date', 'modal_price'], 'idx_crop_date_modal');
            $table->index(['district_id', 'price_date'], 'idx_district_date');
        });

        // 3. Companion Market Arrivals Table
        Schema::create('market_arrivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->date('arrival_date');
            $table->decimal('quantity', 12, 2)->default(0.00);
            $table->string('unit', 50)->default('Quintal');
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['market_id', 'crop_id', 'variety_id', 'arrival_date', 'data_source_id'],
                'uniq_mkt_arrival_record'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_arrivals');
        Schema::dropIfExists('market_prices');
        Schema::dropIfExists('market_price_raw');
    }
};
