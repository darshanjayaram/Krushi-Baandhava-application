<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Services\DataSources\Ceda\CedaAgmarknetDataProvider;
use Illuminate\Database\Seeder;

class CedaDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataSource = DataSource::updateOrCreate(
            ['code' => 'ceda_agmarknet'],
            [
                'name' => 'CEDA Agmarknet (Ashoka University)',
                'provider_class' => CedaAgmarknetDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://api.ceda.ashoka.edu.in/v1',
                'endpoint' => 'agmarknet/prices',
                'auth_type' => 'bearer',
                'sync_frequency' => 'daily',
                'is_active' => true,
                'timeout_seconds' => 35,
                'rate_limit_per_minute' => 1,
            ]
        );

        // Core Karnataka Commodity ID Mappings for CEDA
        $cropMap = [
            'Arecanut' => [140, 41],
            'Coffee' => [45],
            'Coconut' => [138],
            'Copra' => [138],
            'Black Pepper' => [38],
            'Green Ginger' => [27, 103],
            'Paddy' => [2, 414],
            'Ragi (Finger Millet)' => [30],
            'Maize' => [4],
            'Onion' => [23],
            'Tomato' => [78],
            'Tender Coconut' => [200],
            'Jowar (Sorghum)' => [5],
            'Tur (Red Gram)' => [7],
            'Green Chilli' => [87],
            'Banana' => [19],
            'Groundnut' => [10],
            'Sunflower' => [14],
        ];

        foreach ($cropMap as $cropName => $cedaIds) {
            $crop = Crop::where('name', $cropName)->first();
            if (!$crop) {
                continue;
            }

            foreach ($cedaIds as $cedaId) {
                $sourceName = CedaAgmarknetDataProvider::CEDA_COMMODITIES[$cedaId] ?? $cropName;

                // 1. Map by numeric CEDA ID as source_crop_name
                CropSourceMapping::updateOrCreate(
                    [
                        'data_source_id' => $dataSource->id,
                        'source_crop_name' => (string) $cedaId,
                        'source_variety_name' => $cropName,
                    ],
                    [
                        'crop_id' => $crop->id,
                        'confidence_score' => 1.0,
                        'is_verified' => true,
                    ]
                );

                // 2. Map by standard source name
                CropSourceMapping::updateOrCreate(
                    [
                        'data_source_id' => $dataSource->id,
                        'source_crop_name' => $sourceName,
                        'source_variety_name' => $cropName !== $sourceName ? $cropName : null,
                    ],
                    [
                        'crop_id' => $crop->id,
                        'confidence_score' => 1.0,
                        'is_verified' => true,
                    ]
                );
            }
        }
    }
}
