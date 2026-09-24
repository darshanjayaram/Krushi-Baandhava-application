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
            [
                'name' => 'Bagalkote', 'name_kn' => 'ಬಾಗಲಕೋಟೆ', 'code' => 'KA_BGK', 'latitude' => 16.1875, 'longitude' => 75.6989,
                'taluks' => [['name' => 'Bagalkote', 'name_kn' => 'ಬಾಗಲಕೋಟೆ', 'latitude' => 16.1875, 'longitude' => 75.6989]],
                'markets' => [['name' => 'Bagalkote APMC', 'name_kn' => 'ಬಾಗಲಕೋಟೆ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BGK', 'market_type' => 'APMC', 'latitude' => 16.1880, 'longitude' => 75.7000, 'address' => 'APMC Yard, Bagalkote']],
            ],
            [
                'name' => 'Ballari', 'name_kn' => 'ಬಳ್ಳಾರಿ', 'code' => 'KA_BAL', 'latitude' => 15.1394, 'longitude' => 76.9214,
                'taluks' => [['name' => 'Ballari', 'name_kn' => 'ಬಳ್ಳಾರಿ', 'latitude' => 15.1394, 'longitude' => 76.9214]],
                'markets' => [['name' => 'Ballari APMC', 'name_kn' => 'ಬಳ್ಳಾರಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BAL', 'market_type' => 'APMC', 'latitude' => 15.1400, 'longitude' => 76.9220, 'address' => 'APMC Yard, Ballari']],
            ],
            [
                'name' => 'Bengaluru Rural', 'name_kn' => 'ಬೆಂಗಳೂರು ಗ್ರಾಮಾಂತರ', 'code' => 'KA_BLR_R', 'latitude' => 13.2847, 'longitude' => 77.5684,
                'taluks' => [['name' => 'Devanahalli', 'name_kn' => 'ದೇವನಹಳ್ಳಿ', 'latitude' => 13.2483, 'longitude' => 77.7126]],
                'markets' => [['name' => 'Devanahalli APMC', 'name_kn' => 'ದೇವನಹಳ್ಳಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_DEV', 'market_type' => 'APMC', 'latitude' => 13.2500, 'longitude' => 77.7150, 'address' => 'APMC Yard, Devanahalli']],
            ],
            [
                'name' => 'Bidar', 'name_kn' => 'ಬೀದರ್', 'code' => 'KA_BDR', 'latitude' => 17.9104, 'longitude' => 77.5199,
                'taluks' => [['name' => 'Bidar', 'name_kn' => 'ಬೀದರ್', 'latitude' => 17.9104, 'longitude' => 77.5199]],
                'markets' => [['name' => 'Bidar APMC', 'name_kn' => 'ಬೀದರ್ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BDR', 'market_type' => 'APMC', 'latitude' => 17.9120, 'longitude' => 77.5210, 'address' => 'APMC Yard, Bidar']],
            ],
            [
                'name' => 'Chamarajanagar', 'name_kn' => 'ಚಾಮರಾಜನಗರ', 'code' => 'KA_CMR', 'latitude' => 11.9261, 'longitude' => 76.9437,
                'taluks' => [['name' => 'Chamarajanagar', 'name_kn' => 'ಚಾಮರಾಜನಗರ', 'latitude' => 11.9261, 'longitude' => 76.9437]],
                'markets' => [['name' => 'Chamarajanagar APMC', 'name_kn' => 'ಚಾಮರಾಜನಗರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CMR', 'market_type' => 'APMC', 'latitude' => 11.9280, 'longitude' => 76.9450, 'address' => 'APMC Yard, Chamarajanagar']],
            ],
            [
                'name' => 'Chikkaballapura', 'name_kn' => 'ಚಿಕ್ಕಬಳ್ಳಾಪುರ', 'code' => 'KA_CKB', 'latitude' => 13.4325, 'longitude' => 77.7275,
                'taluks' => [['name' => 'Chikkaballapura', 'name_kn' => 'ಚಿಕ್ಕಬಳ್ಳಾಪುರ', 'latitude' => 13.4325, 'longitude' => 77.7275]],
                'markets' => [['name' => 'Chikkaballapura APMC', 'name_kn' => 'ಚಿಕ್ಕಬಳ್ಳಾಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CKB', 'market_type' => 'APMC', 'latitude' => 13.4350, 'longitude' => 77.7300, 'address' => 'APMC Yard, Chikkaballapura']],
            ],
            [
                'name' => 'Chitradurga', 'name_kn' => 'ಚಿತ್ರದುರ್ಗ', 'code' => 'KA_CTA', 'latitude' => 14.2251, 'longitude' => 76.3980,
                'taluks' => [['name' => 'Chitradurga', 'name_kn' => 'ಚಿತ್ರದುರ್ಗ', 'latitude' => 14.2251, 'longitude' => 76.3980]],
                'markets' => [['name' => 'Chitradurga APMC', 'name_kn' => 'ಚಿತ್ರದುರ್ಗ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CTA', 'market_type' => 'APMC', 'latitude' => 14.2280, 'longitude' => 76.4000, 'address' => 'APMC Yard, Chitradurga']],
            ],
            [
                'name' => 'Dharwad', 'name_kn' => 'ಧಾರವಾಡ', 'code' => 'KA_DWR', 'latitude' => 15.4589, 'longitude' => 75.0078,
                'taluks' => [['name' => 'Hubballi', 'name_kn' => 'ಹುಬ್ಬಳ್ಳಿ', 'latitude' => 15.3647, 'longitude' => 75.1240]],
                'markets' => [['name' => 'Hubballi APMC', 'name_kn' => 'ಹುಬ್ಬಳ್ಳಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_HUB', 'market_type' => 'APMC', 'latitude' => 15.3650, 'longitude' => 75.1250, 'address' => 'APMC Yard, Amargol, Hubballi']],
            ],
            [
                'name' => 'Gadag', 'name_kn' => 'ಗದಗ', 'code' => 'KA_GDG', 'latitude' => 15.4313, 'longitude' => 75.6355,
                'taluks' => [['name' => 'Gadag', 'name_kn' => 'ಗದಗ', 'latitude' => 15.4313, 'longitude' => 75.6355]],
                'markets' => [['name' => 'Gadag APMC', 'name_kn' => 'ಗದಗ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_GDG', 'market_type' => 'APMC', 'latitude' => 15.4330, 'longitude' => 75.6370, 'address' => 'APMC Yard, Gadag']],
            ],
            [
                'name' => 'Hassan', 'name_kn' => 'ಹಾಸನ', 'code' => 'KA_HAS', 'latitude' => 13.0033, 'longitude' => 76.1004,
                'taluks' => [
                    ['name' => 'Hassan', 'name_kn' => 'ಹಾಸನ', 'latitude' => 13.0033, 'longitude' => 76.1004],
                    ['name' => 'Sakleshpur', 'name_kn' => 'ಸಕಲೇಶಪುರ', 'latitude' => 12.9442, 'longitude' => 75.7867],
                ],
                'markets' => [
                    ['name' => 'Hassan APMC', 'name_kn' => 'ಹಾಸನ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_HAS', 'market_type' => 'APMC', 'latitude' => 13.0050, 'longitude' => 76.1020, 'address' => 'APMC Yard, Hassan'],
                    ['name' => 'Sakleshpur APMC', 'name_kn' => 'ಸಕಲೇಶಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SAK', 'market_type' => 'APMC', 'latitude' => 12.9460, 'longitude' => 75.7880, 'address' => 'APMC Yard, Sakleshpur'],
                ],
            ],
            [
                'name' => 'Haveri', 'name_kn' => 'ಹಾವೇರಿ', 'code' => 'KA_HVR', 'latitude' => 14.7954, 'longitude' => 75.3991,
                'taluks' => [
                    ['name' => 'Haveri', 'name_kn' => 'ಹಾವೇರಿ', 'latitude' => 14.7954, 'longitude' => 75.3991],
                    ['name' => 'Byadgi', 'name_kn' => 'ಬ್ಯಾಡಗಿ', 'latitude' => 14.6783, 'longitude' => 75.4883],
                ],
                'markets' => [
                    ['name' => 'Byadgi APMC (Chilli Market)', 'name_kn' => 'ಬ್ಯಾಡಗಿ ಎಪಿಎಂಸಿ (ಮೆಣಸಿನಕಾಯಿ ಮಾರುಕಟ್ಟೆ)', 'code' => 'KA_APMC_BYD', 'market_type' => 'Specialized Market', 'latitude' => 14.6800, 'longitude' => 75.4900, 'address' => 'APMC Yard, Byadgi'],
                ],
            ],
            [
                'name' => 'Kalaburagi', 'name_kn' => 'ಕಲಬುರಗಿ', 'code' => 'KA_KLB', 'latitude' => 17.3297, 'longitude' => 76.8343,
                'taluks' => [['name' => 'Kalaburagi', 'name_kn' => 'ಕಲಬುರಗಿ', 'latitude' => 17.3297, 'longitude' => 76.8343]],
                'markets' => [['name' => 'Kalaburagi APMC (Tur Market)', 'name_kn' => 'ಕಲಬುರಗಿ ಎಪಿಎಂಸಿ (ತೊಗರಿ ಮಾರುಕಟ್ಟೆ)', 'code' => 'KA_APMC_KLB', 'market_type' => 'Specialized Market', 'latitude' => 17.3320, 'longitude' => 76.8360, 'address' => 'Nehru Gunj APMC, Kalaburagi']],
            ],
            [
                'name' => 'Kodagu', 'name_kn' => 'ಕೊಡಗು', 'code' => 'KA_KDG', 'latitude' => 12.4244, 'longitude' => 75.7382,
                'taluks' => [
                    ['name' => 'Madikeri', 'name_kn' => 'ಮಡಿಕೇರಿ', 'latitude' => 12.4244, 'longitude' => 75.7382],
                    ['name' => 'Somwarpet', 'name_kn' => 'ಸೋಮವಾರಪೇಟೆ', 'latitude' => 12.5972, 'longitude' => 75.8569],
                ],
                'markets' => [
                    ['name' => 'Madikeri Market', 'name_kn' => 'ಮಡಿಕೇರಿ ಮಾರುಕಟ್ಟೆ', 'code' => 'KA_MKT_MDK', 'market_type' => 'Board/Private', 'latitude' => 12.4260, 'longitude' => 75.7400, 'address' => 'Coffee & Spice Market, Madikeri'],
                ],
            ],
            [
                'name' => 'Koppal', 'name_kn' => 'ಕೊಪ್ಪಳ', 'code' => 'KA_KPL', 'latitude' => 15.3468, 'longitude' => 76.1554,
                'taluks' => [['name' => 'Koppal', 'name_kn' => 'ಕೊಪ್ಪಳ', 'latitude' => 15.3468, 'longitude' => 76.1554]],
                'markets' => [['name' => 'Koppal APMC', 'name_kn' => 'ಕೊಪ್ಪಳ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_KPL', 'market_type' => 'APMC', 'latitude' => 15.3480, 'longitude' => 76.1570, 'address' => 'APMC Yard, Koppal']],
            ],
            [
                'name' => 'Raichur', 'name_kn' => 'ರಾಯಚೂರು', 'code' => 'KA_RCH', 'latitude' => 16.2120, 'longitude' => 77.3439,
                'taluks' => [['name' => 'Raichur', 'name_kn' => 'ರಾಯಚೂರು', 'latitude' => 16.2120, 'longitude' => 77.3439]],
                'markets' => [['name' => 'Raichur APMC (Cotton & Paddy)', 'name_kn' => 'ರಾಯಚೂರು ಎಪಿಎಂಸಿ (ಹತ್ತಿ ಮತ್ತು ಭತ್ತ)', 'code' => 'KA_APMC_RCH', 'market_type' => 'APMC', 'latitude' => 16.2140, 'longitude' => 77.3460, 'address' => 'APMC Yard, Raichur']],
            ],
            [
                'name' => 'Ramanagara', 'name_kn' => 'ರಾಮನಗರ', 'code' => 'KA_RAM', 'latitude' => 12.7209, 'longitude' => 77.2799,
                'taluks' => [['name' => 'Ramanagara', 'name_kn' => 'ರಾಮನಗರ', 'latitude' => 12.7209, 'longitude' => 77.2799]],
                'markets' => [['name' => 'Ramanagara Silk Cocoon Market', 'name_kn' => 'ರಾಮನಗರ ರೇಷ್ಮೆ ಗೂಡು ಮಾರುಕಟ್ಟೆ', 'code' => 'KA_MKT_RAM', 'market_type' => 'Specialized Market', 'latitude' => 12.7220, 'longitude' => 77.2810, 'address' => 'Govt Silk Cocoon Market, Ramanagara']],
            ],
            [
                'name' => 'Tumakuru', 'name_kn' => 'ತುಮಕೂರು', 'code' => 'KA_TUM', 'latitude' => 13.3392, 'longitude' => 77.1017,
                'taluks' => [
                    ['name' => 'Tumakuru', 'name_kn' => 'ತುಮಕೂರು', 'latitude' => 13.3392, 'longitude' => 77.1017],
                    ['name' => 'Tiptur', 'name_kn' => 'ತಿಪಟೂರು', 'latitude' => 13.2572, 'longitude' => 76.4789],
                ],
                'markets' => [
                    ['name' => 'Tiptur APMC (Copra Market)', 'name_kn' => 'ತಿಪಟೂರು ಎಪಿಎಂಸಿ (ಕೊಬ್ಬರಿ ಮಾರುಕಟ್ಟೆ)', 'code' => 'KA_APMC_TIP', 'market_type' => 'Specialized Market', 'latitude' => 13.2600, 'longitude' => 76.4800, 'address' => 'APMC Yard, Tiptur'],
                    ['name' => 'Tumakuru APMC', 'name_kn' => 'ತುಮಕೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_TUM', 'market_type' => 'APMC', 'latitude' => 13.3410, 'longitude' => 77.1040, 'address' => 'APMC Yard, Batawadi, Tumakuru'],
                ],
            ],
            [
                'name' => 'Uttara Kannada', 'name_kn' => 'ಉತ್ತರ ಕನ್ನಡ', 'code' => 'KA_UKN', 'latitude' => 14.7937, 'longitude' => 74.6869,
                'taluks' => [
                    ['name' => 'Sirsi', 'name_kn' => 'ಶಿರಸಿ', 'latitude' => 14.6195, 'longitude' => 74.8354],
                    ['name' => 'Yellapur', 'name_kn' => 'ಯಲ್ಲಾಪುರ', 'latitude' => 14.9642, 'longitude' => 74.7121],
                ],
                'markets' => [
                    ['name' => 'Sirsi APMC (TSS)', 'name_kn' => 'ಶಿರಸಿ ಎಪಿಎಂಸಿ (ಟಿಎಸ್ಎಸ್)', 'code' => 'KA_APMC_SRS', 'market_type' => 'Cooperative APMC', 'latitude' => 14.6210, 'longitude' => 74.8370, 'address' => 'TSS APMC Yard, Sirsi'],
                ],
            ],
            [
                'name' => 'Vijayanagara', 'name_kn' => 'ವಿಜಯನಗರ', 'code' => 'KA_VJN', 'latitude' => 15.2689, 'longitude' => 76.3909,
                'taluks' => [['name' => 'Hosapete', 'name_kn' => 'ಹೊಸಪೇಟೆ', 'latitude' => 15.2689, 'longitude' => 76.3909]],
                'markets' => [['name' => 'Hosapete APMC', 'name_kn' => 'ಹೊಸಪೇಟೆ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_HSP', 'market_type' => 'APMC', 'latitude' => 15.2710, 'longitude' => 76.3930, 'address' => 'APMC Yard, Hosapete']],
            ],
            [
                'name' => 'Vijayapura', 'name_kn' => 'ವಿಜಯಪುರ', 'code' => 'KA_VJP', 'latitude' => 16.8302, 'longitude' => 75.7100,
                'taluks' => [['name' => 'Vijayapura', 'name_kn' => 'ವಿಜಯಪುರ', 'latitude' => 16.8302, 'longitude' => 75.7100]],
                'markets' => [['name' => 'Vijayapura APMC', 'name_kn' => 'ವಿಜಯಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_VJP', 'market_type' => 'APMC', 'latitude' => 16.8320, 'longitude' => 75.7120, 'address' => 'APMC Yard, Vijayapura']],
            ],
            [
                'name' => 'Yadgir', 'name_kn' => 'ಯಾದಗಿರಿ', 'code' => 'KA_YDG', 'latitude' => 16.7628, 'longitude' => 77.1378,
                'taluks' => [['name' => 'Yadgir', 'name_kn' => 'ಯಾದಗಿರಿ', 'latitude' => 16.7628, 'longitude' => 77.1378]],
                'markets' => [['name' => 'Yadgir APMC', 'name_kn' => 'ಯಾದಗಿರಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_YDG', 'market_type' => 'APMC', 'latitude' => 16.7650, 'longitude' => 77.1400, 'address' => 'APMC Yard, Yadgir']],
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
