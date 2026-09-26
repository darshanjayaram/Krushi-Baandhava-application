<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropSourceMapping;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AddMissedKarnatakaCropsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure categories exist
        $categoriesMap = [
            'Cereals & Millets' => [
                'name_kn' => 'ಧಾನ್ಯಗಳು ಮತ್ತು ಸಿರಿಧಾನ್ಯಗಳು',
                'slug' => 'cereals-millets',
                'icon' => 'wheat',
                'display_order' => 3,
            ],
            'Pulses' => [
                'name_kn' => 'ಬೇಳೆಕಾಳುಗಳು',
                'slug' => 'pulses',
                'icon' => 'seedling',
                'display_order' => 5,
            ],
            'Vegetables' => [
                'name_kn' => 'ತರಕಾರಿಗಳು',
                'slug' => 'vegetables',
                'icon' => 'carrot',
                'display_order' => 4,
            ],
            'Fruits' => [
                'name_kn' => 'ಹಣ್ಣುಗಳು',
                'slug' => 'fruits',
                'icon' => 'apple-alt',
                'display_order' => 6,
            ],
            'Oilseeds' => [
                'name_kn' => 'ಎಣ್ಣೆಕಾಳುಗಳು',
                'slug' => 'oilseeds',
                'icon' => 'sunflower',
                'display_order' => 7,
            ],
            'Commercial & Plantation' => [
                'name_kn' => 'ವಾಣಿಜ್ಯ ಮತ್ತು ತೋಟಗಾರಿಕೆ ಬೆಳೆಗಳು',
                'slug' => 'commercial-plantation',
                'icon' => 'trees',
                'display_order' => 1,
            ],
        ];

        $categories = [];
        foreach ($categoriesMap as $catName => $catData) {
            $categories[$catName] = CropCategory::firstOrCreate(
                ['name' => $catName],
                [
                    'name_kn' => $catData['name_kn'],
                    'slug' => $catData['slug'],
                    'icon' => $catData['icon'],
                    'display_order' => $catData['display_order'],
                    'is_active' => true,
                ]
            );
        }

        // 2. Define missed crops and their canonical varieties
        $missedCrops = [
            [
                'name' => 'Jowar',
                'name_kn' => 'ಜೋಳ',
                'slug' => 'jowar',
                'scientific_name' => 'Sorghum bicolor',
                'category_name' => 'Cereals & Millets',
                'standard_unit' => 'Quintal',
                'icon' => 'images/crops/jowar.jpg',
                'is_major' => true,
                'description' => 'Major staple millet cereal of North Karnataka (Davanagere, Dharwad, Vijayapura, Bagalkote).',
                'varieties' => [
                    ['name' => 'White Jowar', 'name_kn' => 'ಬಿಳಿ ಜೋಳ (ಮಾಲ್ದಂಡಿ)', 'slug' => 'white-jowar'],
                    ['name' => 'Yellow Jowar', 'name_kn' => 'ಹಳದಿ ಜೋಳ', 'slug' => 'yellow-jowar'],
                    ['name' => 'Hybrid Jowar', 'name_kn' => 'ಹೈಬ್ರಿಡ್ ಜೋಳ', 'slug' => 'hybrid-jowar'],
                    ['name' => 'Local Jowar', 'name_kn' => 'ಸ್ಥಳೀಯ ಜೋಳ', 'slug' => 'local-jowar'],
                ],
                'aliases' => ['Jowar', 'Sorghum', 'Jowar(Sorghum)', 'White Jowar'],
                'variety_aliases' => [
                    'White Jowar' => 'White Jowar',
                ],
            ],
            [
                'name' => 'Tur',
                'name_kn' => 'ತೊಗರಿ',
                'slug' => 'tur',
                'scientific_name' => 'Cajanus cajan',
                'category_name' => 'Pulses',
                'standard_unit' => 'Quintal',
                'icon' => 'images/crops/tur.jpg',
                'is_major' => true,
                'description' => 'GI-tagged pulse of Karnataka, predominantly grown in Kalaburagi, Yadgir, and Bidar bowl.',
                'varieties' => [
                    ['name' => 'Red Tur', 'name_kn' => 'ಕೆಂಪು ತೊಗರಿ (ಕಲಬುರಗಿ)', 'slug' => 'red-tur'],
                    ['name' => 'White Tur', 'name_kn' => 'ಬಿಳಿ ತೊಗರಿ', 'slug' => 'white-tur'],
                    ['name' => 'Local Tur', 'name_kn' => 'ಸ್ಥಳೀಯ ತೊಗರಿ', 'slug' => 'local-tur'],
                    ['name' => 'Hybrid Tur', 'name_kn' => 'ಹೈಬ್ರಿಡ್ ತೊಗರಿ', 'slug' => 'hybrid-tur'],
                ],
                'aliases' => ['Tur', 'Arhar (Tur/Red Gram)', 'Arhar', 'Red Gram', 'Pigeon Pea'],
                'variety_aliases' => [
                    'Red Tur' => 'Red Tur',
                ],
            ],
            [
                'name' => 'Green Chilli',
                'name_kn' => 'ಹಸಿಮೆಣಸಿನಕಾಯಿ',
                'slug' => 'green-chilli',
                'scientific_name' => 'Capsicum annuum',
                'category_name' => 'Vegetables',
                'standard_unit' => 'Quintal',
                'icon' => 'images/crops/green_chilli.jpg',
                'is_major' => true,
                'description' => 'Essential commercial vegetable grown across Belagavi, Haveri, Byadgi, and Dharwad belts.',
                'varieties' => [
                    ['name' => 'Green Chilli Hybrid', 'name_kn' => 'ಹೈಬ್ರಿಡ್ ಹಸಿಮೆಣಸಿನಕಾಯಿ', 'slug' => 'green-chilli-hybrid'],
                    ['name' => 'G4 Chilli', 'name_kn' => 'ಜಿ-4 ಮೆಣಸಿನಕಾಯಿ', 'slug' => 'g4-chilli'],
                    ['name' => 'Local Green Chilli', 'name_kn' => 'ಸ್ಥಳೀಯ ಹಸಿಮೆಣಸಿನಕಾಯಿ', 'slug' => 'local-green-chilli'],
                ],
                'aliases' => ['Green Chilli', 'Chilli (Green)', 'Green Chilly', 'Chilli'],
                'variety_aliases' => [
                    'Green Chilli Hybrid' => 'Green Chilli Hybrid',
                ],
            ],
            [
                'name' => 'Banana',
                'name_kn' => 'ಬಾಳೆಹಣ್ಣು',
                'slug' => 'banana',
                'scientific_name' => 'Musa acuminata',
                'category_name' => 'Fruits',
                'standard_unit' => 'Quintal',
                'icon' => 'images/crops/banana.jpg',
                'is_major' => true,
                'description' => 'Important fruit crop grown extensively in Mysuru, Chamarajanagar, Shimoga, and Mandya.',
                'varieties' => [
                    ['name' => 'Yelakki Banana', 'name_kn' => 'ಏಲಕ್ಕಿ ಬಾಳೆ', 'slug' => 'yelakki-banana'],
                    ['name' => 'Robusta', 'name_kn' => 'ರೋಬಸ್ಟಾ ಬಾಳೆ', 'slug' => 'robusta'],
                    ['name' => 'Pachabale', 'name_kn' => 'ಪಚ್ಚಬಾಳೆ (ಕ್ಯಾವೆಂಡಿಷ್)', 'slug' => 'pachabale'],
                    ['name' => 'Green Cooking Banana', 'name_kn' => 'ಹಸಿ ಬಾಳೆಕಾಯಿ', 'slug' => 'green-cooking-banana'],
                    ['name' => 'Nanjangud Rasabale', 'name_kn' => 'ನಂಜನಗೂಡು ರಸಬಾಳೆ (GI)', 'slug' => 'nanjangud-rasabale'],
                ],
                'aliases' => ['Banana', 'Banana - Yelakki', 'Banana - Robusta', 'Raw Banana', 'Plantain'],
                'variety_aliases' => [
                    'Yelakki Banana' => 'Yelakki Banana',
                    'Green Cooking Banana' => 'Green Cooking Banana',
                ],
            ],
            [
                'name' => 'Groundnut',
                'name_kn' => 'ಕಡಲೆಕಾಯಿ',
                'slug' => 'groundnut',
                'scientific_name' => 'Arachis hypogaea',
                'category_name' => 'Oilseeds',
                'standard_unit' => 'Quintal',
                'icon' => 'images/crops/groundnut.jpg',
                'is_major' => true,
                'description' => 'Major rainfed oilseed crop cultivated widely in Tumakuru, Chitradurga, Ballari, and Davanagere.',
                'varieties' => [
                    ['name' => 'Pod Groundnut', 'name_kn' => 'ಶೇಂಗಾ ಕಾಯಿ (ಪಾಡ್)', 'slug' => 'pod-groundnut'],
                    ['name' => 'Bold Groundnut', 'name_kn' => 'ದಪ್ಪ ಕಡಲೆಕಾಯಿ (ಬೋಲ್ಡ್)', 'slug' => 'bold-groundnut'],
                    ['name' => 'Java Groundnut', 'name_kn' => 'ಜಾವಾ ಕಡಲೆಕಾಯಿ', 'slug' => 'java-groundnut'],
                    ['name' => 'Local Groundnut', 'name_kn' => 'ಸ್ಥಳೀಯ ಕಡಲೆಕಾಯಿ', 'slug' => 'local-groundnut'],
                ],
                'aliases' => ['Groundnut', 'Groundnut (Split)', 'Groundnut Pods', 'Peanut'],
                'variety_aliases' => [
                    'Pod Groundnut' => 'Pod Groundnut',
                ],
            ],
            [
                'name' => 'Sunflower',
                'name_kn' => 'ಸೂರ್ಯಕಾಂತಿ',
                'slug' => 'sunflower',
                'scientific_name' => 'Helianthus annuus',
                'category_name' => 'Oilseeds',
                'standard_unit' => 'Quintal',
                'icon' => 'images/crops/sunflower.jpg',
                'is_major' => true,
                'description' => 'Premier commercial oilseed of North and Central Karnataka mandis.',
                'varieties' => [
                    ['name' => 'Sunflower Seed', 'name_kn' => 'ಸೂರ್ಯಕಾಂತಿ ಬೀಜ', 'slug' => 'sunflower-seed'],
                    ['name' => 'Hybrid Sunflower', 'name_kn' => 'ಹೈಬ್ರಿಡ್ ಸೂರ್ಯಕಾಂತಿ', 'slug' => 'hybrid-sunflower'],
                    ['name' => 'Standard Sunflower', 'name_kn' => 'ಸಾಮಾನ್ಯ ಸೂರ್ಯಕಾಂತಿ', 'slug' => 'standard-sunflower'],
                ],
                'aliases' => ['Sunflower', 'Sunflower Seed'],
                'variety_aliases' => [
                    'Sunflower Seed' => 'Sunflower Seed',
                ],
            ],
            [
                'name' => 'Cotton',
                'name_kn' => 'ಹತ್ತಿ',
                'slug' => 'cotton',
                'scientific_name' => 'Gossypium hirsutum',
                'category_name' => 'Commercial & Plantation',
                'standard_unit' => 'Quintal',
                'icon' => 'images/crops/cotton.jpg',
                'is_major' => true,
                'description' => 'White gold fiber crop of Karnataka (Raichur, Haveri, Bellary, Dharwad APMCs).',
                'varieties' => [
                    ['name' => 'Bt Cotton', 'name_kn' => 'ಬಿಟಿ ಹತ್ತಿ', 'slug' => 'bt-cotton'],
                    ['name' => 'DCH-32', 'name_kn' => 'ಡಿಸಿಎಚ್-32', 'slug' => 'dch-32'],
                    ['name' => 'Bunny', 'name_kn' => 'ಬನ್ನಿ ಹತ್ತಿ', 'slug' => 'bunny'],
                    ['name' => 'Medium Staple', 'name_kn' => 'ಮಧ್ಯಮ ಸ್ಟೇಪಲ್', 'slug' => 'medium-staple'],
                ],
                'aliases' => ['Cotton', 'Cotton (Unginned)', 'Kapas', 'Raw Cotton'],
                'variety_aliases' => [
                    'Bt Cotton' => 'Bt Cotton',
                    'DCH-32' => 'DCH-32',
                ],
            ],
        ];

        // Fetch data sources for alias mapping
        $dataSources = DataSource::whereIn('code', ['data_gov_mandi', 'ceda_agmarknet'])->get();

        foreach ($missedCrops as $cropData) {
            $cat = $categories[$cropData['category_name']] ?? $categories['Commercial & Plantation'];

            $crop = Crop::updateOrCreate(
                ['slug' => $cropData['slug']],
                [
                    'category_id' => $cat->id,
                    'name' => $cropData['name'],
                    'name_kn' => $cropData['name_kn'],
                    'scientific_name' => $cropData['scientific_name'],
                    'standard_unit' => $cropData['standard_unit'],
                    'icon' => $cropData['icon'],
                    'is_major' => $cropData['is_major'],
                    'is_active' => true,
                    'description' => $cropData['description'],
                    'price_source_type' => 'apmc',
                    'market_radius_km' => 300,
                    'default_market_sort' => 'nearest_first',
                    'allow_user_sort_toggle' => true,
                    'enable_smart_badges' => true,
                ]
            );

            // Register Varieties
            $createdVarieties = [];
            foreach ($cropData['varieties'] as $varData) {
                $variety = CropVariety::firstOrCreate(
                    [
                        'crop_id' => $crop->id,
                        'name' => $varData['name'],
                    ],
                    [
                        'name_kn' => $varData['name_kn'],
                        'slug' => $varData['slug'],
                        'is_active' => true,
                    ]
                );
                $createdVarieties[$varData['name']] = $variety;
            }

            // Register Commodity Alias Mappings across Data Sources
            foreach ($dataSources as $ds) {
                foreach ($cropData['aliases'] as $aliasName) {
                    CropSourceMapping::firstOrCreate(
                        [
                            'data_source_id' => $ds->id,
                            'source_crop_name' => $aliasName,
                            'source_variety_name' => null,
                        ],
                        [
                            'crop_id' => $crop->id,
                            'crop_variety_id' => null,
                            'confidence_score' => 1.00,
                            'is_verified' => true,
                        ]
                    );
                }

                // Register Variety Mappings
                foreach ($cropData['variety_aliases'] as $rawVariety => $canonicalVarietyName) {
                    if (isset($createdVarieties[$canonicalVarietyName])) {
                        CropSourceMapping::firstOrCreate(
                            [
                                'data_source_id' => $ds->id,
                                'source_crop_name' => $cropData['name'],
                                'source_variety_name' => $rawVariety,
                            ],
                            [
                                'crop_id' => $crop->id,
                                'crop_variety_id' => $createdVarieties[$canonicalVarietyName]->id,
                                'confidence_score' => 1.00,
                                'is_verified' => true,
                            ]
                        );
                    }
                }
            }
        }

        // 3. Batch reprocess all rejected raw records
        $ingestionService = app(MarketPriceIngestionService::class);
        $reprocessResult = $ingestionService->reprocessBatch();

        $this->command?->info("Missed Karnataka crops and varieties registered successfully.");
        $this->command?->info("Reprocessed raw records: {$reprocessResult['processed']} processed, {$reprocessResult['still_rejected']} remaining.");
    }
}
