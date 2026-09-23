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
        Schema::create('crop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_kn', 150)->nullable();
            $table->string('slug', 100)->unique();
            $table->string('icon', 100)->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('crop_categories')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('name_kn', 200)->nullable();
            $table->string('slug', 150)->unique();
            $table->string('scientific_name', 150)->nullable();
            $table->string('standard_unit', 50)->default('Quintal');
            $table->string('icon', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_major')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
        });

        Schema::create('crop_varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('name_kn', 200)->nullable();
            $table->string('slug', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['crop_id', 'slug']);
            $table->index(['crop_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_varieties');
        Schema::dropIfExists('crops');
        Schema::dropIfExists('crop_categories');
    }
};
