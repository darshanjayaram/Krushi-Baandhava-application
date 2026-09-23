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
        // 1. Government Welfare & Agricultural Schemes
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_kn')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 50)->default('subsidy'); // subsidy, machinery, irrigation, insurance, organic, general
            $table->string('sponsoring_agency')->default('Karnataka Dept of Agriculture');
            $table->string('benefit_amount')->nullable();
            $table->string('benefit_amount_kn')->nullable();
            $table->text('eligibility_criteria')->nullable();
            $table->text('eligibility_criteria_kn')->nullable();
            $table->text('documents_required')->nullable();
            $table->string('official_url', 500)->nullable();
            $table->string('apply_url', 500)->nullable();
            $table->string('icon_emoji', 20)->default('🌾');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category']);
            $table->index('display_order');
        });

        // 2. Agricultural News & Breaking Market Circulars
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->string('headline');
            $table->string('headline_kn')->nullable();
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->text('summary_kn')->nullable();
            $table->longText('content')->nullable();
            $table->longText('content_kn')->nullable();
            $table->string('source_name')->default('Department of Agriculture');
            $table->string('source_url', 500)->nullable();
            $table->string('priority', 30)->default('standard'); // breaking, important, standard
            $table->string('image_url', 500)->nullable();
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'published_at']);
            $table->index(['priority', 'published_at']);
        });

        // 3. Curated YouTube Educational Videos
        Schema::create('curated_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_kn')->nullable();
            $table->string('youtube_video_id', 50);
            $table->string('youtube_url', 500);
            $table->foreignId('crop_id')->nullable()->constrained('crops')->nullOnDelete();
            $table->string('category', 50)->default('cultivation'); // cultivation, pest_control, organic, machinery, success_story
            $table->string('channel_name')->nullable();
            $table->string('duration_text', 30)->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'crop_id']);
            $table->index('display_order');
        });

        // 4. Agronomy Articles & Cultivation Guides
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->nullable()->constrained('crops')->nullOnDelete();
            $table->string('title');
            $table->string('title_kn')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 50)->default('cultivation'); // cultivation, pest_control, soil_fertilizer, harvest_storage, general
            $table->text('summary')->nullable();
            $table->text('summary_kn')->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_kn')->nullable();
            $table->string('featured_image', 500)->nullable();
            $table->string('author_name')->default('ಕೃಷಿ ತಜ್ಞರು (Agri Expert)');
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'category']);
            $table->index(['crop_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::dropIfExists('curated_videos');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('schemes');
    }
};
