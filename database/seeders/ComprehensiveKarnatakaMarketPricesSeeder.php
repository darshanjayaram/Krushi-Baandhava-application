<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComprehensiveKarnatakaMarketPricesSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->toDateString();

        $dataGovSource = DataSource::where('code', 'datagov')->first() ?? DataSource::first();
        $coffeeSource = DataSource::where('code', 'coffee_board')->first() ?? $dataGovSource;
        $coconutSource = DataSource::where('code', 'coconut_board')->first() ?? $dataGovSource;

        $matrix = [
            'arecanut' => [
                'source' => $dataGovSource,
                'variety' => 'Rashi',
                'variety_kn' => 'ರಾಶಿ',
                'prices' => [
                    ['TUMAKURU', 46000, 48500, 47500],
                    ['SAGAR', 44500, 46800, 45999],
                    ['CHANNAGIRI', 44000, 46500, 45585],
                    ['SIRSI', 45000, 47200, 46024],
                    ['KA_APMC_SHI', 45500, 47800, 47059],
                    ['KA_APMC_MNG', 46500, 49000, 48200],
                    ['KA_APMC_PUT', 45000, 47500, 46800],
                ]
            ],
            'paddy' => [
                'source' => $dataGovSource,
                'variety' => 'Sona Masuri',
                'variety_kn' => 'ಸೋನಾ ಮಸೂರಿ',
                'prices' => [
                    ['KA_APMC_RCH', 2350, 2550, 2450],
                    ['KA_APMC_SHI', 2280, 2460, 2380],
                    ['KA_APMC_MDY', 2320, 2490, 2410],
                    ['KA_APMC_DVG', 2250, 2420, 2350],
                    ['KA_APMC_BAL', 2300, 2480, 2420],
                ]
            ],
            'onion' => [
                'source' => $dataGovSource,
                'variety' => 'Medium',
                'variety_kn' => 'ಮಧ್ಯಮ',
                'prices' => [
                    ['KA_APMC_YPR', 3000, 3400, 3200],
                    ['KA_APMC_HUB', 2700, 3050, 2850],
                    ['KA_APMC_DVG', 2750, 3100, 2900],
                    ['KA_APMC_BLG', 2600, 2950, 2750],
                    ['KA_APMC_GDG', 2650, 3000, 2800],
                ]
            ],
            'tomato' => [
                'source' => $dataGovSource,
                'variety' => 'Hybrid',
                'variety_kn' => 'ಹೈಬ್ರಿಡ್',
                'prices' => [
                    ['KA_APMC_KLR', 1500, 1800, 1650],
                    ['KA_APMC_BNM', 1650, 1950, 1800],
                    ['KA_APMC_SHI', 1580, 1850, 1720],
                    ['KA_APMC_BLG', 1400, 1700, 1550],
                    ['KA_APMC_MYS', 1550, 1820, 1680],
                ]
            ],
            'maize' => [
                'source' => $dataGovSource,
                'variety' => 'Yellow',
                'variety_kn' => 'ಹಳದಿ',
                'prices' => [
                    ['KA_APMC_DVG', 2050, 2250, 2150],
                    ['KA_APMC_SHI', 2020, 2210, 2120],
                    ['KA_APMC_HVR', 2080, 2280, 2180],
                    ['KA_APMC_BLG', 2000, 2180, 2090],
                    ['KA_APMC_CKM', 2040, 2230, 2140],
                ]
            ],
            'ragi' => [
                'source' => $dataGovSource,
                'variety' => 'Indaf',
                'variety_kn' => 'ಇಂದಾಫ್',
                'prices' => [
                    ['KA_APMC_MYS', 3250, 3550, 3400],
                    ['KA_APMC_HAS', 3300, 3600, 3450],
                    ['KA_APMC_MDY', 3220, 3510, 3380],
                    ['KA_APMC_TUM', 3280, 3580, 3420],
                    ['KA_APMC_YPR', 3400, 3700, 3550],
                ]
            ],
            'tur' => [
                'source' => $dataGovSource,
                'variety' => 'Red',
                'variety_kn' => 'ಕೆಂಪು',
                'prices' => [
                    ['KA_APMC_KLB', 10000, 11000, 10500],
                    ['KA_APMC_YDG', 9800, 10700, 10250],
                    ['KA_APMC_BDR', 9900, 10800, 10350],
                    ['KA_APMC_DVG', 9400, 10200, 9800],
                ]
            ],
            'green-chilli' => [
                'source' => $dataGovSource,
                'variety' => 'Guntur/Byadgi',
                'variety_kn' => 'ಬ್ಯಾಡಗಿ',
                'prices' => [
                    ['KA_APMC_BYD', 3900, 4500, 4200],
                    ['KA_APMC_BLG', 3600, 4200, 3900],
                    ['KA_APMC_HUB', 3800, 4400, 4100],
                    ['KA_APMC_KLR', 4000, 4600, 4300],
                ]
            ],
            'coconut' => [
                'source' => $coconutSource,
                'variety' => 'Big',
                'variety_kn' => 'ದೊಡ್ಡದು',
                'prices' => [
                    ['KA_CDB_TUM', 3000, 3400, 3200],
                    ['KA_CDB_ARS', 2950, 3350, 3150],
                    ['KA_CDB_MNG', 3150, 3550, 3350],
                    ['KA_CDB_TIP', 3080, 3480, 3280],
                ]
            ],
            'copra' => [
                'source' => $coconutSource,
                'variety' => 'Ball Copra',
                'variety_kn' => 'ಉಂಡೆ ಕೊಬ್ಬರಿ',
                'prices' => [
                    ['KA_APMC_TIP', 10800, 12000, 11400],
                    ['KA_CDB_ARS', 10600, 11800, 11250],
                    ['KA_APMC_TUM', 10500, 11700, 11100],
                ]
            ],
            'coffee' => [
                'source' => $coffeeSource,
                'variety' => 'Arabica Parchment',
                'variety_kn' => 'ಅರೇಬಿಕಾ',
                'prices' => [
                    ['KA_MKT_CKM', 9500, 10200, 9800],
                    ['KA_MKT_MDK', 9350, 10050, 9650],
                    ['KA_MKT_HAS', 9400, 10100, 9720],
                    ['KA_MKT_SKL', 9450, 10150, 9750],
                ]
            ],
            'black-pepper' => [
                'source' => $dataGovSource,
                'variety' => 'Garbled',
                'variety_kn' => 'ಗಾರ್ಬಲ್ಡ್',
                'prices' => [
                    ['KA_APMC_SRS', 62000, 67000, 64500],
                    ['KA_APMC_SHI', 61500, 66000, 63800],
                    ['KA_APMC_MNG', 63000, 68000, 65200],
                    ['KA_MKT_MDK', 61800, 66500, 64000],
                ]
            ],
            'ginger' => [
                'source' => $dataGovSource,
                'variety' => 'Fresh Green',
                'variety_kn' => 'ಹಸಿ ಶುಂಠಿ',
                'prices' => [
                    ['KA_APMC_SHI', 5400, 6200, 5800],
                    ['KA_APMC_HAS', 5250, 6050, 5650],
                    ['KA_APMC_MYS', 5500, 6300, 5900],
                ]
            ],
            'banana' => [
                'source' => $dataGovSource,
                'variety' => 'Yellaki',
                'variety_kn' => 'ಏಲಕ್ಕಿ',
                'prices' => [
                    ['KA_APMC_MYS', 2400, 2800, 2600],
                    ['KA_APMC_CMR', 2300, 2700, 2500],
                    ['KA_APMC_SHI', 2350, 2750, 2550],
                    ['KA_APMC_BNM', 2550, 2950, 2750],
                ]
            ],
            'groundnut' => [
                'source' => $dataGovSource,
                'variety' => 'Pods',
                'variety_kn' => 'ಕಡಲೆಕಾಯಿ',
                'prices' => [
                    ['KA_APMC_DVG', 6800, 7500, 7150],
                    ['KA_APMC_CTA', 6900, 7600, 7250],
                    ['KA_APMC_BAL', 6700, 7400, 7050],
                ]
            ],
            'jowar' => [
                'source' => $dataGovSource,
                'variety' => 'White',
                'variety_kn' => 'ಬಿಳಿ',
                'prices' => [
                    ['KA_APMC_DVG', 2800, 3200, 3000],
                    ['KA_APMC_BLG', 2900, 3300, 3100],
                    ['KA_APMC_VJP', 2950, 3350, 3150],
                ]
            ],
            'sunflower' => [
                'source' => $dataGovSource,
                'variety' => 'Standard',
                'variety_kn' => 'ಸಾಮಾನ್ಯ',
                'prices' => [
                    ['KA_APMC_DVG', 4600, 5100, 4850],
                    ['KA_APMC_RCH', 4700, 5200, 4950],
                    ['KA_APMC_KPL', 4650, 5150, 4900],
                ]
            ],
            'tender-coconut' => [
                'source' => $coconutSource,
                'variety' => 'Green',
                'variety_kn' => 'ಎಳನೀರು',
                'prices' => [
                    ['KA_CDB_TUM', 30, 40, 35],
                    ['KA_APMC_MDY', 28, 38, 33],
                    ['KA_APMC_MYS', 30, 40, 34],
                    ['KA_APMC_BNM', 35, 45, 40],
                ]
            ],
        ];

        foreach ($matrix as $slug => $data) {
            $crop = Crop::where('slug', $slug)
                ->orWhere('slug', 'like', "{$slug}%")
                ->first();

            if (!$crop) {
                continue;
            }

            $varietyName = $data['variety'];
            $varietyNameKn = $data['variety_kn'] ?? $data['variety'];

            $variety = CropVariety::firstOrCreate(
                ['crop_id' => $crop->id, 'name' => $varietyName],
                ['name_kn' => $varietyNameKn, 'slug' => Str::slug($varietyName) . '-' . $crop->id, 'is_active' => true]
            );

            foreach ($data['prices'] as $p) {
                [$marketCode, $min, $max, $modal] = $p;
                $market = Market::where('code', $marketCode)->first();

                if (!$market) {
                    $codeMap = [
                        'KA_APMC_TUM' => 'TUMAKURU',
                        'KA_APMC_SRS' => 'SIRSI',
                        'KA_APMC_SAG' => 'SAGAR',
                        'KA_APMC_CHN' => 'CHANNAGIRI',
                        'KA_MKT_CKM'  => 'CB_CKM',
                        'KA_MKT_MDK'  => 'CB_MDK',
                        'KA_MKT_HAS'  => 'CB_HSN',
                        'KA_MKT_SKL'  => 'CB_SKP',
                        'KA_CDB_TUM'  => 'CDB_TMK',
                        'KA_CDB_ARS'  => 'CDB_ASK',
                        'KA_CDB_MNG'  => 'CDB_MLR',
                        'KA_CDB_TIP'  => 'CDB_TPT',
                    ];
                    if (isset($codeMap[$marketCode])) {
                        $market = Market::where('code', $codeMap[$marketCode])->first();
                    }
                }

                if (!$market) {
                    $stripped = str_replace('KA_APMC_', '', $marketCode);
                    $market = Market::where('code', $stripped)->first()
                        ?? Market::where('name', 'like', "%{$stripped}%")->where('name', 'like', '%APMC%')->first()
                        ?? Market::where('name', 'like', "%{$stripped}%")->first();
                }

                if (!$market) {
                    continue;
                }

                MarketPrice::updateOrCreate(
                    [
                        'market_id' => $market->id,
                        'crop_id' => $crop->id,
                        'variety_id' => $variety->id,
                        'price_date' => $today,
                    ],
                    [
                        'data_source_id' => $data['source']->id,
                        'district_id' => $market->district_id,
                        'min_price' => $min,
                        'max_price' => $max,
                        'modal_price' => $modal,
                        'arrival_quantity' => rand(100, 500),
                        'unit' => $crop->standard_unit ?? 'Quintal',
                    ]
                );
            }
        }

        // Explicitly seed TSS Sirsi specific cooperative auction varieties (Rashi, Chali, Bette, Bilegotu, Kempugotu)
        $sirsiMarket = Market::where('code', 'SIRSI')->first()
            ?? Market::where('name', 'like', '%Sirsi%')->first();
        $arecaCrop = Crop::where('slug', 'arecanut')
            ->orWhere('id', 1)
            ->first();

        if ($sirsiMarket && $arecaCrop) {
            $tssVarieties = [
                ['Rashi', 'ರಾಶಿ', 45000, 47000, 46024],
                ['Chali', 'ಚಾಲಿ', 43000, 45000, 44599],
                ['Bette', 'ಬೆಟ್ಟೆ', 36000, 38000, 37691],
                ['Bilegotu', 'ಬಿಳೆಗೋಟು', 26000, 28000, 27373],
                ['Kempugotu', 'ಕೆಂಪುಗೋಟು', 24500, 26500, 25892],
            ];

            foreach ($tssVarieties as [$vName, $vKn, $min, $max, $modal]) {
                $v = CropVariety::firstOrCreate(
                    ['crop_id' => $arecaCrop->id, 'name' => $vName],
                    ['name_kn' => $vKn, 'slug' => Str::slug($vName) . '-' . $arecaCrop->id, 'is_active' => true]
                );

                MarketPrice::updateOrCreate(
                    [
                        'market_id' => $sirsiMarket->id,
                        'crop_id' => $arecaCrop->id,
                        'variety_id' => $v->id,
                        'price_date' => $today,
                    ],
                    [
                        'data_source_id' => $dataGovSource->id,
                        'district_id' => $sirsiMarket->district_id,
                        'min_price' => $min,
                        'max_price' => $max,
                        'modal_price' => $modal,
                        'arrival_quantity' => rand(150, 450),
                        'unit' => 'Quintal',
                    ]
                );
            }
        }

        // Also ensure Tumakuru APMC has Arecanut Bette variety as well
        $tumakuruMarket = Market::where('code', 'TUMAKURU')->first()
            ?? Market::where('name', 'like', '%Tumakuru%')->where('name', 'like', '%APMC%')->first();
        if ($tumakuruMarket && $arecaCrop) {
            $betteVar = CropVariety::where('crop_id', $arecaCrop->id)->where('name', 'Bette')->first();
            if ($betteVar) {
                MarketPrice::updateOrCreate(
                    [
                        'market_id' => $tumakuruMarket->id,
                        'crop_id' => $arecaCrop->id,
                        'variety_id' => $betteVar->id,
                        'price_date' => $today,
                    ],
                    [
                        'data_source_id' => $dataGovSource->id,
                        'district_id' => $tumakuruMarket->district_id,
                        'min_price' => 37000,
                        'max_price' => 39500,
                        'modal_price' => 38200,
                        'arrival_quantity' => 120,
                        'unit' => 'Quintal',
                    ]
                );
            }
        }

        \Illuminate\Support\Facades\Cache::flush();
    }
}
