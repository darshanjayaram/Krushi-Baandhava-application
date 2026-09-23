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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('name_kn', 150)->nullable();
            $table->string('code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state_id', 'name']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('taluks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('name_kn', 150)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['district_id', 'name']);
        });

        Schema::create('localities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taluk_id')->constrained('taluks')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('name_kn', 200)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->foreignId('taluk_id')->nullable()->constrained('taluks')->nullOnDelete();
            $table->string('name', 150);
            $table->string('name_kn', 200)->nullable();
            $table->string('code', 50)->nullable()->index();
            $table->string('market_type', 50)->default('APMC');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['district_id', 'is_active']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markets');
        Schema::dropIfExists('localities');
        Schema::dropIfExists('taluks');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('states');
    }
};
