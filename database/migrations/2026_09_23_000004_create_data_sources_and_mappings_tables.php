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
        // 1. Data Sources master table
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->unique();
            $table->string('provider_class', 255);
            $table->string('type', 50)->default('market_prices');
            $table->string('base_url', 255);
            $table->string('endpoint', 255)->nullable();
            $table->string('auth_type', 50)->default('api_key'); // api_key, bearer_token, none
            $table->string('sync_frequency', 50)->default('daily'); // hourly, daily, twice_daily, weekly
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('timeout_seconds')->default(30);
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_sync_status', 50)->nullable(); // success, failed, partial
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // 2. Encrypted credentials at rest
        Schema::create('data_source_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->unique()->constrained('data_sources')->cascadeOnDelete();
            $table->text('api_key')->nullable();
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('additional_headers')->nullable();
            $table->timestamps();
        });

        // 3. Declarative field mappings
        Schema::create('data_source_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->string('source_field', 100);
            $table->string('target_field', 100);
            $table->string('transformation_rule', 100)->nullable(); // trim, to_number, date_format:d/m/Y, uppercase, etc.
            $table->boolean('is_required')->default(false);
            $table->string('default_value', 255)->nullable();
            $table->timestamps();

            $table->index(['data_source_id', 'source_field']);
        });

        // 4. Ingestion Sync Execution Logs
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_inserted')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_duplicate')->default(0);
            $table->unsignedInteger('records_rejected')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->string('status', 50)->default('running'); // running, success, failed, partial
            $table->text('error_message')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['data_source_id', 'status']);
            $table->index('started_at');
        });

        // 5. API Health & Connection Test Logs
        Schema::create('api_health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('auth_result', 50)->default('skipped'); // success, failed, unauthorized, skipped
            $table->unsignedInteger('records_found')->default(0);
            $table->json('detected_fields')->nullable();
            $table->string('status', 50)->default('healthy'); // healthy, degraded, unhealthy
            $table->text('error_message')->nullable();
            $table->json('sample_payload')->nullable();
            $table->timestamps();

            $table->index(['data_source_id', 'created_at']);
        });

        // 6. Crop Alias Mappings (Raw string -> Canonical crop_id)
        Schema::create('crop_source_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->string('source_crop_name', 150);
            $table->string('source_variety_name', 150)->nullable();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('crop_variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->decimal('confidence_score', 3, 2)->default(1.00);
            $table->boolean('is_verified')->default(true);
            $table->timestamps();

            $table->unique(['data_source_id', 'source_crop_name', 'source_variety_name'], 'crop_source_unique');
        });

        // 7. Market Alias Mappings (Raw string -> Canonical market_id)
        Schema::create('market_source_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->string('source_market_name', 150);
            $table->string('source_district_name', 150)->nullable();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->decimal('confidence_score', 3, 2)->default(1.00);
            $table->boolean('is_verified')->default(true);
            $table->timestamps();

            $table->unique(['data_source_id', 'source_market_name', 'source_district_name'], 'market_source_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_source_mappings');
        Schema::dropIfExists('crop_source_mappings');
        Schema::dropIfExists('api_health_logs');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('data_source_mappings');
        Schema::dropIfExists('data_source_credentials');
        Schema::dropIfExists('data_sources');
    }
};
