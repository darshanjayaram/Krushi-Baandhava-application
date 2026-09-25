<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CropMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Commercial & Plantation',
                'name_kn' => 'ವಾಣಿಜ್ಯ ಮತ್ತು ತೋಟಗಾರಿಕೆ ಬೆಳೆಗಳು',
                'slug' => 'commercial-plantation',
                'icon' => 'trees',
                'display_order' => 1,
                'crops' => [
                    [
                        'name' => 'Arecanut',
                        'name_kn' => 'ಅಡಿಕೆ',
                        'slug' => 'arecanut',
                        'scientific_name' => 'Areca catechu',
                        'standard_unit' => 'Quintal',
                        'is_major' => true,
                        'description' => 'Major cash crop grown in Malnad and Coastal Karnataka (Shivamogga, Chikkamagaluru, Dakshina Kannada, Uttara Kannada).',
                        'varieties' => [
                            ['name' => 'Rashi', 'name_kn' => 'ರಾಶಿ', 'slug' => 'rashi'],
                            ['name' => 'Bette', 'name_kn' => 'ಬೆಟ್ಟೆ', 'slug' => 'bette'],
                            ['name' => 'Gorabalu', 'name_kn' => 'ಗೊರಬಲು', 'slug' => 'gorabalu'],
                            ['name' => 'Chali', 'name_kn' => 'ಚಾಲಿ', 'slug' => 'chali'],
                            ['name' => 'Api', 'name_kn' => 'ಆಪಿ', 'slug' => 'api'],
                            ['name' => 'Koka', 'name_kn' => 'ಕೋಕಾ', 'slug' => 'koka'],
                        ],
                    ],
                    [
                        'name' => 'Coffee',
                        'name_kn' => 'ಕಾಫಿ',
                        'slug' => 'coffee',
                        'scientific_name' => 'Coffea',
                        'standard_unit' => '50 Kg Bag',
                        'is_major' => true,
                        'description' => 'Premium plantation crop grown in Chikkamagaluru, Kodagu, and Hassan hills.',
                        'varieties' => [
                            ['name' => 'Arabica Parchment', 'name_kn' => 'ಅರೇಬಿಕಾ ಪಾರ್ಚ್‌ಮೆಂಟ್', 'slug' => 'arabica-parchment'],
                            ['name' => 'Arabica Cherry', 'name_kn' => 'ಅರೇಬಿಕಾ ಚೆರ್ರಿ', 'slug' => 'arabica-cherry'],
                            ['name' => 'Robusta Parchment', 'name_kn' => 'ರೋಬಸ್ಟಾ ಪಾರ್ಚ್‌ಮೆಂಟ್', 'slug' => 'robusta-parchment'],
                            ['name' => 'Robusta Cherry', 'name_kn' => 'ರೋಬಸ್ಟಾ ಚೆರ್ರಿ', 'slug' => 'robusta-cherry'],
                        ],
                    ],
                    [
                        'name' => 'Coconut',
                        'name_kn' => 'ತೆಂಗಿನಕಾಯಿ',
                        'slug' => 'coconut',
                        'scientific_name' => 'Cocos nucifera',
                        'standard_unit' => '1000 Nuts',
                        'is_major' => true,
                        'description' => 'Widely cultivated in Tumakuru, Hassan, Mandya, and coastal districts.',
                        'varieties' => [
                            ['name' => 'Raw Coconut', 'name_kn' => 'ಹಸಿ ತೆಂಗಿನಕಾಯಿ', 'slug' => 'raw-coconut'],
                            ['name' => 'Tender Coconut', 'name_kn' => 'ಎಳನೀರು', 'slug' => 'tender-coconut'],
                        ],
                    ],
                    [
                        'name' => 'Copra',
                        'name_kn' => 'ಕೊಬ್ಬರಿ',
                        'slug' => 'copra',
                        'scientific_name' => 'Cocos nucifera (Dried)',
                        'standard_unit' => 'Quintal',
                        'is_major' => true,
                        'description' => 'Dried coconut kernel traded extensively in Tiptur and Arsikere APMC markets.',
                        'varieties' => [
                            ['name' => 'Milling Copra', 'name_kn' => 'ಮಿಲ್ಲಿಂಗ್ ಕೊಬ್ಬರಿ', 'slug' => 'milling-copra'],
                            ['name' => 'Ball Copra', 'name_kn' => 'ಉಂಡೆ ಕೊಬ್ಬರಿ', 'slug' => 'ball-copra'],
                        ],
                    ],
                    [
                        'name' => 'Tender Coconut',
                        'name_kn' => 'ಎಳನೀರು',
                        'slug' => 'tender-coconut',
                        'scientific_name' => 'Cocos nucifera (Tender)',
                        'standard_unit' => '100 Nuts',
                        'is_major' => true,
                        'description' => 'Green tender coconuts traded across Mandya (Maddur), Mysuru, and Hassan markets.',
                        'varieties' => [
                            ['name' => 'Standard Tender Coconut', 'name_kn' => 'ಸಾಮಾನ್ಯ ಎಳನೀರು', 'slug' => 'standard-tender-coconut'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Spices',
                'name_kn' => 'ಮಸಾಲೆ ಬೆಳೆಗಳು',
                'slug' => 'spices',
                'icon' => 'sparkles',
                'display_order' => 2,
                'crops' => [
                    [
                        'name' => 'Black Pepper',
                        'name_kn' => 'ಕಾಳುಮೆಣಸು',
                        'slug' => 'black-pepper',
                        'scientific_name' => 'Piper nigrum',
                        'standard_unit' => 'Quintal',
                        'is_major' => true,
                        'description' => 'Black gold of Malnad intercropped with coffee and arecanut.',
                        'varieties' => [
                            ['name' => 'Garbled Black Pepper', 'name_kn' => 'ಗಾರ್ಬಲ್ಡ್ ಕಾಳುಮೆಣಸು', 'slug' => 'garbled'],
                            ['name' => 'Ungarbled Black Pepper', 'name_kn' => 'ಅನ್‌ಗಾರ್ಬಲ್ಡ್ ಕಾಳುಮೆಣಸು', 'slug' => 'ungarbled'],
                        ],
                    ],
                    [
                        'name' => 'Ginger',
                        'name_kn' => 'ಶುಂಠಿ',
                        'slug' => 'ginger',
                        'scientific_name' => 'Zingiber officinale',
                        'standard_unit' => 'Quintal',
                        'is_major' => false,
                        'description' => 'High-value rhizome crop cultivated in Shivamogga, Hassan, and Chamarajanagar.',
                        'varieties' => [
                            ['name' => 'Green Ginger', 'name_kn' => 'ಹಸಿ ಶುಂಠಿ', 'slug' => 'green-ginger'],
                            ['name' => 'Dry Ginger', 'name_kn' => 'ಒಣ ಶುಂಠಿ', 'slug' => 'dry-ginger'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Cereals & Millets',
                'name_kn' => 'ಧಾನ್ಯಗಳು ಮತ್ತು ಸಿರಿಧಾನ್ಯಗಳು',
                'slug' => 'cereals-millets',
                'icon' => 'wheat',
                'display_order' => 3,
                'crops' => [
                    [
                        'name' => 'Paddy',
                        'name_kn' => 'ಭತ್ತ',
                        'slug' => 'paddy',
                        'scientific_name' => 'Oryza sativa',
                        'standard_unit' => 'Quintal',
                        'is_major' => true,
                        'description' => 'Staple food grain grown across Cauvery and Tungabhadra river basins.',
                        'varieties' => [
                            ['name' => 'Sona Masuri', 'name_kn' => 'ಸೋನಾ ಮಸೂರಿ', 'slug' => 'sona-masuri'],
                            ['name' => 'Jyothi', 'name_kn' => 'ಜ್ಯೋತಿ', 'slug' => 'jyothi'],
                            ['name' => 'IR-64', 'name_kn' => 'ಐಆರ್-64', 'slug' => 'ir-64'],
                        ],
                    ],
                    [
                        'name' => 'Ragi (Finger Millet)',
                        'name_kn' => 'ರಾಗಿ',
                        'slug' => 'ragi',
                        'scientific_name' => 'Eleusine coracana',
                        'standard_unit' => 'Quintal',
                        'is_major' => true,
                        'description' => 'Nutritious staple food crop of southern Karnataka (Mysuru, Mandya, Hassan, Tumakuru).',
                        'varieties' => [
                            ['name' => 'Local Ragi', 'name_kn' => 'ನಾಟಿ ರಾಗಿ', 'slug' => 'local-ragi'],
                            ['name' => 'ML-365 Hybrid', 'name_kn' => 'ಎಂಎಲ್-365 ರಾಗಿ', 'slug' => 'ml-365'],
                        ],
                    ],
                    [
                        'name' => 'Maize',
                        'name_kn' => 'ಮೆಕ್ಕೆಜೋಳ',
                        'slug' => 'maize',
                        'scientific_name' => 'Zea mays',
                        'standard_unit' => 'Quintal',
                        'is_major' => true,
                        'description' => 'Grown extensively in central Karnataka, notably Davanagere and Haveri.',
                        'varieties' => [
                            ['name' => 'Yellow Hybrid Maize', 'name_kn' => 'ಹಳದಿ ಹೈಬ್ರಿಡ್ ಮೆಕ್ಕೆಜೋಳ', 'slug' => 'yellow-hybrid'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Vegetables',
                'name_kn' => 'ತರಕಾರಿಗಳು',
                'slug' => 'vegetables',
                'icon' => 'carrot',
                'display_order' => 4,
                'crops' => [
                    [
                        'name' => 'Onion',
                        'name_kn' => 'ಈರುಳ್ಳಿ',
                        'slug' => 'onion',
                        'scientific_name' => 'Allium cepa',
                        'standard_unit' => 'Quintal',
                        'is_major' => true,
                        'description' => 'Major market crop in Hubballi, Gadag, and Bangalore Rose onion in Chikkaballapura.',
                        'varieties' => [
                            ['name' => 'Red Medium Onion', 'name_kn' => 'ಕೆಂಪು ಈರುಳ್ಳಿ', 'slug' => 'red-medium'],
                            ['name' => 'Bangalore Rose Onion', 'name_kn' => 'ಬೆಂಗಳೂರು ರೋಸ್ ಈರುಳ್ಳಿ', 'slug' => 'bangalore-rose'],
                        ],
                    ],
                    [
                        'name' => 'Tomato',
                        'name_kn' => 'ಟೊಮೆಟೊ',
                        'slug' => 'tomato',
                        'scientific_name' => 'Solanum lycopersicum',
                        'standard_unit' => '15 Kg Box / Quintal',
                        'is_major' => true,
                        'description' => 'High trade volume vegetable with major wholesale yards in Kolar and Channapatna.',
                        'varieties' => [
                            ['name' => 'Hybrid Tomato', 'name_kn' => 'ಹೈಬ್ರಿಡ್ ಟೊಮೆಟೊ', 'slug' => 'hybrid-tomato'],
                            ['name' => 'Local Tomato', 'name_kn' => 'ನಾಟಿ ಟೊಮೆಟೊ', 'slug' => 'local-tomato'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $crops = $catData['crops'];
            unset($catData['crops']);

            $category = CropCategory::firstOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            foreach ($crops as $cropData) {
                $varieties = $cropData['varieties'];
                unset($cropData['varieties']);

                $defaults = [
                    'market_radius_km' => match ($cropData['slug']) {
                        'arecanut' => 350,
                        'coffee' => 250,
                        'coconut', 'copra' => 200,
                        'pepper' => 300,
                        'tomato' => 100,
                        default => 250,
                    },
                    'default_market_sort' => 'nearest_first',
                    'allow_user_sort_toggle' => true,
                    'enable_smart_badges' => true,
                    'price_source_type' => match ($cropData['slug']) {
                        'coffee' => 'coffee_board',
                        'coconut', 'copra', 'tender-coconut' => 'coconut_board',
                        default => 'apmc',
                    },
                ];

                $crop = Crop::updateOrCreate(
                    ['slug' => $cropData['slug']],
                    array_merge($cropData, ['category_id' => $category->id], $defaults)
                );

                foreach ($varieties as $vData) {
                    CropVariety::firstOrCreate(
                        ['crop_id' => $crop->id, 'slug' => $vData['slug']],
                        $vData
                    );
                }
            }
        }
    }
}
