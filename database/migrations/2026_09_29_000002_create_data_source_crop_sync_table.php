<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_source_crop_sync', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->string('sync_priority', 20)->default('standard'); // high, standard, low
            $table->timestamps();

            $table->unique(['data_source_id', 'crop_id'], 'source_crop_sync_unique');
            $table->index(['data_source_id', 'is_enabled'], 'source_crop_enabled_idx');
        });

        // Seed initial default configurations for existing active providers
        $this->seedInitialConfigurations();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_source_crop_sync');
    }

    /**
     * Pre-populate default enabled/disabled crops for active providers.
     */
    protected function seedInitialConfigurations(): void
    {
        $crops = DB::table('crops')->get(['id', 'name']);
        if ($crops->isEmpty()) {
            return;
        }

        $nonCrops = [
            'Sheep', 'Goat', 'She Baffalo', 'He Baffalo', 'Bull', 'Calf', 'Ox', 'She Goat',
            'Tur Dal', 'Bengal Gramdal', 'Black Gramdal', 'Green Gramdal', 'Avaredal', 'Chennangidal',
            'Wood', 'Coco Brooms', 'Honge Seed', 'Neem Seed', 'Soapnut', 'Antawala', 'Hippe Seed',
            'Rose', 'Crysanthamum', 'Marygold', 'All Flowers',
            'Linseed', 'Niger Seed', 'T. V. Cumbu', 'Maragenasu', 'Bullar', 'Duster Beans', 'Gurellu', 'Moath', 'Barley', 'Cumminseed'
        ];
        $nonCropsLookup = array_fill_keys(array_map('strtolower', $nonCrops), true);

        $now = now();

        // 1. KRAMA Karnataka & Official AGMARKNET
        $generalSources = DB::table('data_sources')
            ->whereIn('code', ['krama_karnataka', 'agmarknet_official'])
            ->get(['id', 'code']);

        foreach ($generalSources as $source) {
            $rows = [];
            foreach ($crops as $crop) {
                $isNonCrop = isset($nonCropsLookup[strtolower(trim($crop->name))]);
                $rows[] = [
                    'data_source_id' => $source->id,
                    'crop_id' => $crop->id,
                    'is_enabled' => !$isNonCrop,
                    'sync_priority' => 'standard',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($rows)) {
                DB::table('data_source_crop_sync')->insertOrIgnore($rows);
            }
        }

        // 2. Coffee Board of India
        $coffeeSource = DB::table('data_sources')->where('code', 'coffee_board')->first(['id']);
        if ($coffeeSource) {
            $coffeeCrop = DB::table('crops')->where('name', 'like', '%Coffee%')->first(['id']);
            if ($coffeeCrop) {
                DB::table('data_source_crop_sync')->insertOrIgnore([
                    'data_source_id' => $coffeeSource->id,
                    'crop_id' => $coffeeCrop->id,
                    'is_enabled' => true,
                    'sync_priority' => 'standard',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 3. Coconut Development Board
        $coconutSource = DB::table('data_sources')->where('code', 'coconut_board')->first(['id']);
        if ($coconutSource) {
            $coconutCrops = DB::table('crops')
                ->where(function ($q) {
                    $q->where('name', 'like', '%Coconut%')
                      ->orWhere('name', 'like', '%Copra%');
                })
                ->get(['id']);

            $rows = [];
            foreach ($coconutCrops as $cc) {
                $rows[] = [
                    'data_source_id' => $coconutSource->id,
                    'crop_id' => $cc->id,
                    'is_enabled' => true,
                    'sync_priority' => 'standard',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($rows)) {
                DB::table('data_source_crop_sync')->insertOrIgnore($rows);
            }
        }
    }
};
