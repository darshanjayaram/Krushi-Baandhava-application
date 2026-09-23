<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Market;
use App\Models\State;
use App\Models\Taluk;
use Illuminate\Database\Seeder;

class GeographicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Karnataka State
        $karnataka = State::firstOrCreate(
            ['code' => 'KA'],
            [
                'name' => 'Karnataka',
                'is_active' => true,
            ]
        );

        // 2. Karnataka Districts & Coordinates
        $districts = [
            [
                'name' => 'Shivamogga',
                'name_kn' => 'ಶಿವಮೊಗ್ಗ',
                'code' => 'KA_SHI',
                'latitude' => 13.9299,
                'longitude' => 75.5681,
                'taluks' => [
                    ['name' => 'Shivamogga', 'name_kn' => 'ಶಿವಮೊಗ್ಗ', 'latitude' => 13.9299, 'longitude' => 75.5681],
                    ['name' => 'Bhadravathi', 'name_kn' => 'ಭದ್ರಾವತಿ', 'latitude' => 13.8400, 'longitude' => 75.7000],
                    ['name' => 'Thirthahalli', 'name_kn' => 'ತೀರ್ಥಹಳ್ಳಿ', 'latitude' => 13.6892, 'longitude' => 75.2427],
                    ['name' => 'Sagara', 'name_kn' => 'ಸಾಗರ', 'latitude' => 14.1667, 'longitude' => 75.0333],
                    ['name' => 'Shikaripura', 'name_kn' => 'ಶಿಕಾರಿಪುರ', 'latitude' => 14.2667, 'longitude' => 75.3500],
                    ['name' => 'Soraba', 'name_kn' => 'ಸೊರಬ', 'latitude' => 14.3833, 'longitude' => 75.1000],
                    ['name' => 'Hosanagara', 'name_kn' => 'ಹೊಸನಗರ', 'latitude' => 13.9167, 'longitude' => 75.0667],
                ],
                'markets' => [
                    ['name' => 'Shivamogga APMC', 'name_kn' => 'ಶಿವಮೊಗ್ಗ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SHI', 'market_type' => 'APMC', 'latitude' => 13.9310, 'longitude' => 75.5700, 'address' => 'APMC Yard, Shivamogga'],
                    ['name' => 'Sagara APMC', 'name_kn' => 'ಸಾಗರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SAG', 'market_type' => 'APMC', 'latitude' => 14.1680, 'longitude' => 75.0350, 'address' => 'APMC Yard, Sagara'],
                    ['name' => 'Thirthahalli APMC', 'name_kn' => 'ತೀರ್ಥಹಳ್ಳಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_THI', 'market_type' => 'APMC', 'latitude' => 13.6910, 'longitude' => 75.2450, 'address' => 'APMC Market, Thirthahalli'],
                ],
            ],
            [
                'name' => 'Chikkamagaluru',
                'name_kn' => 'ಚಿಕ್ಕಮಗಳೂರು',
                'code' => 'KA_CKM',
                'latitude' => 13.3161,
                'longitude' => 75.7720,
                'taluks' => [
                    ['name' => 'Chikkamagaluru', 'name_kn' => 'ಚಿಕ್ಕಮಗಳೂರು', 'latitude' => 13.3161, 'longitude' => 75.7720],
                    ['name' => 'Mudigere', 'name_kn' => 'ಮೂಡಿಗೆರೆ', 'latitude' => 13.1360, 'longitude' => 75.6410],
                    ['name' => 'Koppa', 'name_kn' => 'ಕೊಪ್ಪ', 'latitude' => 13.5286, 'longitude' => 75.3582],
                    ['name' => 'Sringeri', 'name_kn' => 'ಶೃಂಗೇರಿ', 'latitude' => 13.4194, 'longitude' => 75.2575],
                    ['name' => 'Tarikere', 'name_kn' => 'ತರೀಕೆರೆ', 'latitude' => 13.7136, 'longitude' => 75.8167],
                    ['name' => 'Kadur', 'name_kn' => 'ಕಡೂರು', 'latitude' => 13.5539, 'longitude' => 76.0125],
                ],
                'markets' => [
                    ['name' => 'Chikkamagaluru APMC', 'name_kn' => 'ಚಿಕ್ಕಮಗಳೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CKM', 'market_type' => 'APMC', 'latitude' => 13.3200, 'longitude' => 75.7750, 'address' => 'APMC Yard, Chikkamagaluru'],
                    ['name' => 'Mudigere Sub-Market', 'name_kn' => 'ಮೂಡಿಗೆರೆ ಉಪ ಮಾರುಕಟ್ಟೆ', 'code' => 'KA_APMC_MUD', 'market_type' => 'Sub-market', 'latitude' => 13.1380, 'longitude' => 75.6430, 'address' => 'Mudigere Market, Mudigere'],
                    ['name' => 'Kadur APMC', 'name_kn' => 'ಕಡೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_KAD', 'market_type' => 'APMC', 'latitude' => 13.5550, 'longitude' => 76.0140, 'address' => 'APMC Market, Kadur'],
                ],
            ],
            [
                'name' => 'Davanagere',
                'name_kn' => 'ದಾವಣಗೆರೆ',
                'code' => 'KA_DVG',
                'latitude' => 14.4644,
                'longitude' => 75.9218,
                'taluks' => [
                    ['name' => 'Davanagere', 'name_kn' => 'ದಾವಣಗೆರೆ', 'latitude' => 14.4644, 'longitude' => 75.9218],
                    ['name' => 'Harihara', 'name_kn' => 'ಹರಿಹರ', 'latitude' => 14.5125, 'longitude' => 75.8058],
                    ['name' => 'Channagiri', 'name_kn' => 'ಚನ್ನಗಿರಿ', 'latitude' => 14.0253, 'longitude' => 75.9317],
                    ['name' => 'Honnali', 'name_kn' => 'ಹೊನ್ನಾಳಿ', 'latitude' => 14.2464, 'longitude' => 75.6456],
                ],
                'markets' => [
                    ['name' => 'Davanagere APMC', 'name_kn' => 'ದಾವಣಗೆರೆ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_DVG', 'market_type' => 'APMC', 'latitude' => 14.4670, 'longitude' => 75.9230, 'address' => 'APMC Yard, Davanagere'],
                    ['name' => 'Channagiri APMC', 'name_kn' => 'ಚನ್ನಗಿರಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CHN', 'market_type' => 'APMC', 'latitude' => 14.0280, 'longitude' => 75.9340, 'address' => 'APMC Yard, Channagiri'],
                ],
            ],
            [
                'name' => 'Dakshina Kannada',
                'name_kn' => 'ದಕ್ಷಿಣ ಕನ್ನಡ',
                'code' => 'KA_DKN',
                'latitude' => 12.9141,
                'longitude' => 74.8560,
                'taluks' => [
                    ['name' => 'Mangaluru', 'name_kn' => 'ಮಂಗಳೂರು', 'latitude' => 12.9141, 'longitude' => 74.8560],
                    ['name' => 'Bantwal', 'name_kn' => 'ಬಂಟ್ವಾಳ', 'latitude' => 12.8931, 'longitude' => 75.0347],
                    ['name' => 'Puttur', 'name_kn' => 'ಪುತ್ತೂರು', 'latitude' => 12.7667, 'longitude' => 75.2000],
                    ['name' => 'Belthangady', 'name_kn' => 'ಬೆಳ್ತಂಗಡಿ', 'latitude' => 13.0000, 'longitude' => 75.2500],
                    ['name' => 'Sullia', 'name_kn' => 'ಸುಳ್ಯ', 'latitude' => 12.5606, 'longitude' => 75.3886],
                ],
                'markets' => [
                    ['name' => 'Mangaluru APMC (Baikampady)', 'name_kn' => 'ಮಂಗಳೂರು ಎಪಿಎಂಸಿ (ಬೈಕಂಪಾಡಿ)', 'code' => 'KA_APMC_MNG', 'market_type' => 'APMC', 'latitude' => 12.9500, 'longitude' => 74.8200, 'address' => 'APMC Yard, Baikampady, Mangaluru'],
                    ['name' => 'Puttur APMC', 'name_kn' => 'ಪುತ್ತೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_PUT', 'market_type' => 'APMC', 'latitude' => 12.7700, 'longitude' => 75.2050, 'address' => 'APMC Yard, Puttur'],
                ],
            ],
            [
                'name' => 'Mysuru',
                'name_kn' => 'ಮೈಸೂರು',
                'code' => 'KA_MYS',
                'latitude' => 12.2958,
                'longitude' => 76.6394,
                'taluks' => [
                    ['name' => 'Mysuru', 'name_kn' => 'ಮೈಸೂರು', 'latitude' => 12.2958, 'longitude' => 76.6394],
                    ['name' => 'Hunsur', 'name_kn' => 'ಹುಣಸೂರು', 'latitude' => 12.3106, 'longitude' => 76.2892],
                    ['name' => 'Nanjangud', 'name_kn' => 'ನಂಜನಗೂಡು', 'latitude' => 12.1192, 'longitude' => 76.6800],
                ],
                'markets' => [
                    ['name' => 'Mysuru APMC (Bandipalya)', 'name_kn' => 'ಮೈಸೂರು ಎಪಿಎಂಸಿ (ಬಂಡಿಪಾಳ್ಯ)', 'code' => 'KA_APMC_MYS', 'market_type' => 'APMC', 'latitude' => 12.2700, 'longitude' => 76.6600, 'address' => 'Bandipalya APMC, Mysuru'],
                ],
            ],
            [
                'name' => 'Mandya',
                'name_kn' => 'ಮಂಡ್ಯ',
                'code' => 'KA_MDY',
                'latitude' => 12.5218,
                'longitude' => 76.8951,
                'taluks' => [
                    ['name' => 'Mandya', 'name_kn' => 'ಮಂಡ್ಯ', 'latitude' => 12.5218, 'longitude' => 76.8951],
                    ['name' => 'Maddur', 'name_kn' => 'ಮದ್ದೂರು', 'latitude' => 12.5847, 'longitude' => 77.0450],
                    ['name' => 'Pandavapura', 'name_kn' => 'ಪಾಂಡವಪುರ', 'latitude' => 12.5000, 'longitude' => 76.6667],
                ],
                'markets' => [
                    ['name' => 'Mandya APMC', 'name_kn' => 'ಮಂಡ್ಯ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_MDY', 'market_type' => 'APMC', 'latitude' => 12.5250, 'longitude' => 76.8980, 'address' => 'APMC Yard, Mandya'],
                ],
            ],
            [
                'name' => 'Bengaluru Urban',
                'name_kn' => 'ಬೆಂಗಳೂರು ನಗರ',
                'code' => 'KA_BLR',
                'latitude' => 12.9716,
                'longitude' => 77.5946,
                'taluks' => [
                    ['name' => 'Bengaluru North', 'name_kn' => 'ಬೆಂಗಳೂರು ಉತ್ತರ', 'latitude' => 13.0333, 'longitude' => 77.5667],
                    ['name' => 'Bengaluru South', 'name_kn' => 'ಬೆಂಗಳೂರು ದಕ್ಷಿಣ', 'latitude' => 12.9167, 'longitude' => 77.6000],
                ],
                'markets' => [
                    ['name' => 'Binny Mill (F&V)', 'name_kn' => 'ಬಿನ್ನಿ ಮಿಲ್ (ಹಣ್ಣು ಮತ್ತು ತರಕಾರಿ)', 'code' => 'KA_APMC_BNM', 'market_type' => 'Specialized Market', 'latitude' => 12.9667, 'longitude' => 77.5667, 'address' => 'Binny Mill Fruit & Vegetable Market, Cottonpet, Bengaluru'],
                    ['name' => 'Yeshwanthpur APMC', 'name_kn' => 'ಯಶವಂತಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_YPR', 'market_type' => 'APMC', 'latitude' => 13.0238, 'longitude' => 77.5458, 'address' => 'APMC Yard, Yeshwanthpur, Bengaluru'],
                ],
            ],
            [
                'name' => 'Kolar',
                'name_kn' => 'ಕೋಲಾರ',
                'code' => 'KA_KLR',
                'latitude' => 13.1367,
                'longitude' => 78.1340,
                'taluks' => [
                    ['name' => 'Kolar', 'name_kn' => 'ಕೋಲಾರ', 'latitude' => 13.1367, 'longitude' => 78.1340],
                    ['name' => 'Srinivaspur', 'name_kn' => 'ಶ್ರೀನಿವಾಸಪುರ', 'latitude' => 13.3367, 'longitude' => 78.2140],
                ],
                'markets' => [
                    ['name' => 'Kolar APMC (Tomato Market)', 'name_kn' => 'ಕೋಲಾರ ಎಪಿಎಂಸಿ (ಟೊಮೆಟೊ ಮಾರುಕಟ್ಟೆ)', 'code' => 'KA_APMC_KLR', 'market_type' => 'APMC', 'latitude' => 13.1380, 'longitude' => 78.1350, 'address' => 'APMC Yard, Kolar'],
                ],
            ],
            [
                'name' => 'Udupi',
                'name_kn' => 'ಉಡುಪಿ',
                'code' => 'KA_UDP',
                'latitude' => 13.3409,
                'longitude' => 74.7421,
                'taluks' => [
                    ['name' => 'Udupi', 'name_kn' => 'ಉಡುಪಿ', 'latitude' => 13.3409, 'longitude' => 74.7421],
                    ['name' => 'Kundapura', 'name_kn' => 'ಕುಂದಾಪುರ', 'latitude' => 13.6267, 'longitude' => 74.6933],
                ],
                'markets' => [
                    ['name' => 'Udupi APMC', 'name_kn' => 'ಉಡುಪಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_UDP', 'market_type' => 'APMC', 'latitude' => 13.3420, 'longitude' => 74.7450, 'address' => 'APMC Market, Adi Udupi'],
                    ['name' => 'Kundapura APMC', 'name_kn' => 'ಕುಂದಾಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_KND', 'market_type' => 'APMC', 'latitude' => 13.6280, 'longitude' => 74.6950, 'address' => 'APMC Yard, Kundapura'],
                ],
            ],
            [
                'name' => 'Belagavi',
                'name_kn' => 'ಬೆಳಗಾವಿ',
                'code' => 'KA_BLG',
                'latitude' => 15.8497,
                'longitude' => 74.4977,
                'taluks' => [
                    ['name' => 'Belagavi', 'name_kn' => 'ಬೆಳಗಾವಿ', 'latitude' => 15.8497, 'longitude' => 74.4977],
                    ['name' => 'Gokak', 'name_kn' => 'ಗೋಕಾಕ', 'latitude' => 16.1667, 'longitude' => 74.8333],
                ],
                'markets' => [
                    ['name' => 'Belagavi APMC', 'name_kn' => 'ಬೆಳಗಾವಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BLG', 'market_type' => 'APMC', 'latitude' => 15.8520, 'longitude' => 74.5000, 'address' => 'APMC Yard, Belagavi'],
                ],
            ],
        ];

        foreach ($districts as $dData) {
            $taluks = $dData['taluks'];
            $markets = $dData['markets'];
            unset($dData['taluks'], $dData['markets']);

            $district = District::firstOrCreate(
                ['state_id' => $karnataka->id, 'name' => $dData['name']],
                $dData
            );

            $talukMap = [];
            foreach ($taluks as $tData) {
                $taluk = Taluk::firstOrCreate(
                    ['district_id' => $district->id, 'name' => $tData['name']],
                    $tData
                );
                $talukMap[$tData['name']] = $taluk->id;
            }

            foreach ($markets as $mData) {
                Market::firstOrCreate(
                    ['code' => $mData['code']],
                    array_merge($mData, ['district_id' => $district->id])
                );
            }
        }
    }
}
