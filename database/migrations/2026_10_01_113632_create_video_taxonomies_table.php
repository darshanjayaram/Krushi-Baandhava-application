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
        Schema::create('video_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index(); // 'category' or 'growth_stage'
            $table->string('slug', 64)->index();
            $table->string('name', 120);
            $table->string('name_kn', 191)->nullable();
            $table->string('icon', 32)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['type', 'slug']);
        });

        $now = now();

        $defaultCategories = [
            ['slug' => 'cultivation', 'name' => 'Cultivation Techniques', 'name_kn' => 'ಬೇಸಾಯ ಪದ್ಧತಿ', 'icon' => '🌾', 'display_order' => 1],
            ['slug' => 'pest_control', 'name' => 'Pest & Disease Control', 'name_kn' => 'ಕೀಟ ಹಾಗೂ ರೋಗ ನಿರ್ವಹಣೆ', 'icon' => '🐛', 'display_order' => 2],
            ['slug' => 'organic', 'name' => 'Organic & Natural Farming', 'name_kn' => 'ಸಾವಯವ & ನೈಸರ್ಗಿಕ ಕೃಷಿ', 'icon' => '🍃', 'display_order' => 3],
            ['slug' => 'machinery', 'name' => 'Farm Machinery & Drones', 'name_kn' => 'ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ', 'icon' => '🚜', 'display_order' => 4],
            ['slug' => 'success_story', 'name' => 'Farmer Success Story', 'name_kn' => 'ರೈತರ ಯಶೋಗಾಥೆ', 'icon' => '🏆', 'display_order' => 5],
            ['slug' => 'irrigation', 'name' => 'Irrigation Management', 'name_kn' => 'ನೀರಾವರಿ ಪದ್ಧತಿಗಳು', 'icon' => '💧', 'display_order' => 6],
            ['slug' => 'farming_tips', 'name' => 'Farming Tips & Techniques', 'name_kn' => 'ಕೃಷಿ ತಂತ್ರಜ್ಞಾನ & ಸಲಹೆಗಳು', 'icon' => '🌾', 'display_order' => 7],
        ];

        foreach ($defaultCategories as $cat) {
            DB::table('video_taxonomies')->insert([
                'type' => 'category',
                'slug' => $cat['slug'],
                'name' => $cat['name'],
                'name_kn' => $cat['name_kn'],
                'icon' => $cat['icon'],
                'display_order' => $cat['display_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $defaultStages = [
            ['slug' => 'general', 'name' => 'General Guide', 'name_kn' => 'ಸಾಮಾನ್ಯ ಮಾರ್ಗದರ್ಶಿ', 'icon' => '📌', 'display_order' => 1],
            ['slug' => 'nursery', 'name' => 'Nursery & Sowing', 'name_kn' => 'ಬಿತ್ತನೆ & ಸಸಿ ಮಡಿ', 'icon' => '🌱', 'display_order' => 2],
            ['slug' => 'vegetative', 'name' => 'Growth & Nutrition', 'name_kn' => 'ಬೆಳವಣಿಗೆ & ಪೋಷಕಾಂಶ', 'icon' => '🌿', 'display_order' => 3],
            ['slug' => 'pest_control', 'name' => 'Pest & Disease Control', 'name_kn' => 'ಕೀಟ & ರೋಗ ನಿರ್ವಹಣೆ', 'icon' => '🐛', 'display_order' => 4],
            ['slug' => 'irrigation', 'name' => 'Irrigation & Water Mgmt', 'name_kn' => 'ನೀರಾವರಿ ನಿರ್ವಹಣೆ', 'icon' => '💧', 'display_order' => 5],
            ['slug' => 'harvest', 'name' => 'Harvesting & Picking', 'name_kn' => 'ಕೊಯ್ಲು & ಕಟಾವು', 'icon' => '✂️', 'display_order' => 6],
            ['slug' => 'post_harvest', 'name' => 'Storage & Value Addition', 'name_kn' => 'ಸಂಸ್ಕರಣೆ & ಮೌಲ್ಯವರ್ಧನೆ', 'icon' => '📦', 'display_order' => 7],
        ];

        foreach ($defaultStages as $stage) {
            DB::table('video_taxonomies')->insert([
                'type' => 'growth_stage',
                'slug' => $stage['slug'],
                'name' => $stage['name'],
                'name_kn' => $stage['name_kn'],
                'icon' => $stage['icon'],
                'display_order' => $stage['display_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_taxonomies');
    }
};
