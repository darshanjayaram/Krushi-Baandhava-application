<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketSourceMapping;
use App\Services\DataSources\Agmarknet\AgmarknetHistoricalDataProvider;
use App\Services\DataSources\Krama\KramaMarketDataProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KramaAndAgmarknetSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Primary Live Provider: KRAMA (Karnataka State Agricultural Marketing Board)
        $krama = DataSource::updateOrCreate(
            ['code' => 'krama_karnataka'],
            [
                'name' => 'KRAMA (Karnataka State Agricultural Marketing Board)',
                'provider_class' => KramaMarketDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://krama.karnataka.gov.in',
                'endpoint' => 'reports/Commadity',
                'auth_type' => 'none',
                'sync_frequency' => 'daily',
                'is_active' => true,
                'timeout_seconds' => 35,
                'rate_limit_per_minute' => 30,
            ]
        );

        // 2. Historical & Prediction Provider: Official AGMARKNET (DMI / MoA&FW)
        // Check if legacy CEDA exists and migrate it
        $legacyCeda = DataSource::where('code', 'ceda_agmarknet')->first();
        if ($legacyCeda) {
            $legacyCeda->update([
                'code' => 'agmarknet_official',
                'name' => 'Official AGMARKNET (Govt of India - DMI)',
                'provider_class' => AgmarknetHistoricalDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://api.agmarknet.gov.in/v1',
                'endpoint' => 'daily-price-arrival/report',
                'auth_type' => 'captcha',
                'sync_frequency' => 'manual',
                'is_active' => true,
                'timeout_seconds' => 30,
            ]);
        } else {
            DataSource::updateOrCreate(
                ['code' => 'agmarknet_official'],
                [
                    'name' => 'Official AGMARKNET (Govt of India - DMI)',
                    'provider_class' => AgmarknetHistoricalDataProvider::class,
                    'type' => 'market_prices',
                    'base_url' => 'https://api.agmarknet.gov.in/v1',
                    'endpoint' => 'daily-price-arrival/report',
                    'auth_type' => 'captcha',
                    'sync_frequency' => 'manual',
                    'is_active' => true,
                    'timeout_seconds' => 30,
                ]
            );
        }

        // 3. Crop Mappings for KRAMA
        $cropAliases = [
            'Arecanut' => 'Arecanut',
            'Tomato' => 'Tomato',
            'Paddy' => 'Paddy',
            'Maize' => 'Maize',
            'Onion' => 'Onion',
            'Cotton' => 'Cotton',
            'Ginger' => 'Ginger',
            'Green Chilly' => 'Green Chilli',
            'Green Chilli' => 'Green Chilli',
            'Cucumbar' => 'Cucumber',
            'Cucumber' => 'Cucumber',
            'Jowar' => 'Jowar',
            'Ragi' => 'Ragi',
            'Tur' => 'Tur',
            'Copra' => 'Copra',
            'Coconut' => 'Coconut',
            'Coffee' => 'Coffee',
            'Bengalgram' => 'Bengal Gram',
            'Groundnut' => 'Groundnut',
            'Sunflower' => 'Sunflower',
            'Wheat' => 'Wheat',
            'Bajra' => 'Bajra',
            'Beans' => 'Beans',
            'Brinjal' => 'Brinjal',
            'Carrot' => 'Carrot',
            'Beetroot' => 'Beetroot',
        ];

        foreach ($cropAliases as $sourceCrop => $targetCropName) {
            $crop = Crop::where('name', $targetCropName)->first();
            if ($crop) {
                CropSourceMapping::updateOrCreate(
                    [
                        'data_source_id' => $krama->id,
                        'source_crop_name' => $sourceCrop,
                    ],
                    [
                        'crop_id' => $crop->id,
                        'confidence_score' => 1.0,
                        'is_verified' => true,
                    ]
                );
            }
        }

        // 4. Market Mappings & New Mandi Creation for KRAMA
        // Define known Karnataka APMC markets and their districts
        $kramaMarkets = [
            'SHIVAMOGGA' => ['name' => 'Shivamogga APMC', 'district' => 'Shivamogga', 'code' => 'KA_APMC_SHI'],
            'SAGAR' => ['name' => 'Sagara APMC', 'district' => 'Shivamogga', 'code' => 'KA_APMC_SAG'],
            'TIRTHAHALLI' => ['name' => 'Thirthahalli APMC', 'district' => 'Shivamogga', 'code' => 'KA_APMC_THI'],
            'SHIKARIPUR' => ['name' => 'Shikaripur APMC', 'district' => 'Shivamogga', 'code' => 'KA_APMC_SKP'],
            'HONNALI' => ['name' => 'Honnali APMC', 'district' => 'Davanagere', 'code' => 'KA_APMC_HNL'],
            'DAVANAGERE' => ['name' => 'Davanagere APMC', 'district' => 'Davanagere', 'code' => 'KA_APMC_DVG'],
            'CHANNAGIRI' => ['name' => 'Channagiri APMC', 'district' => 'Davanagere', 'code' => 'KA_APMC_CHN'],
            'HARIHARA' => ['name' => 'Harihara APMC', 'district' => 'Davanagere', 'code' => 'KA_APMC_HRH'],
            'KOLAR' => ['name' => 'Kolar APMC (Tomato Market)', 'district' => 'Kolar', 'code' => 'KA_APMC_KLR'],
            'BANGARPET' => ['name' => 'Bangarpet APMC', 'district' => 'Kolar', 'code' => 'KA_APMC_BPT'],
            'MULBAGAL' => ['name' => 'Mulbagal APMC', 'district' => 'Kolar', 'code' => 'KA_APMC_MLB'],
            'BINNY MILL (F&V)' => ['name' => 'Binny Mill (F&V)', 'district' => 'Bengaluru Urban', 'code' => 'KA_APMC_BNM'],
            'BINNY MILL' => ['name' => 'Binny Mill (F&V)', 'district' => 'Bengaluru Urban', 'code' => 'KA_APMC_BNM'],
            'YESHWANTHPUR' => ['name' => 'Yeshwanthpur APMC', 'district' => 'Bengaluru Urban', 'code' => 'KA_APMC_YPR'],
            'CHINTAMANI' => ['name' => 'Chintamani APMC', 'district' => 'Chikkaballapura', 'code' => 'KA_APMC_CNT'],
            'GOWRIBIDNUR' => ['name' => 'Gowribidnur APMC', 'district' => 'Chikkaballapura', 'code' => 'KA_APMC_GWR'],
            'CHIKKABALLAPURA' => ['name' => 'Chikkaballapura APMC', 'district' => 'Chikkaballapura', 'code' => 'KA_APMC_CKB'],
            'DODDABALLAPUR' => ['name' => 'Doddaballapur APMC', 'district' => 'Bengaluru Rural', 'code' => 'KA_APMC_DBL'],
            'CHANNAPATNA' => ['name' => 'Channapatna APMC', 'district' => 'Ramanagara', 'code' => 'KA_APMC_CPT'],
            'RAMANAGARA' => ['name' => 'Ramanagara APMC', 'district' => 'Ramanagara', 'code' => 'KA_APMC_RMN'],
            'GUNDLUPET' => ['name' => 'Gundlupet APMC', 'district' => 'Chamarajanagar', 'code' => 'KA_APMC_GLP'],
            'SINDHANUR' => ['name' => 'Sindhanur APMC', 'district' => 'Raichur', 'code' => 'KA_APMC_SDN'],
            'RAICHUR' => ['name' => 'Raichur APMC', 'district' => 'Raichur', 'code' => 'KA_APMC_RCH'],
            'MANVI' => ['name' => 'Manvi APMC', 'district' => 'Raichur', 'code' => 'KA_APMC_MNV'],
            'GANGAVATI' => ['name' => 'Gangavati APMC', 'district' => 'Koppal', 'code' => 'KA_APMC_GGV'],
            'KOPPAL' => ['name' => 'Koppal APMC', 'district' => 'Koppal', 'code' => 'KA_APMC_KPL'],
            'KUSTAGI' => ['name' => 'Kustagi APMC', 'district' => 'Koppal', 'code' => 'KA_APMC_KSG'],
            'KANAKAGIRI' => ['name' => 'Kanakagiri APMC', 'district' => 'Koppal', 'code' => 'KA_APMC_KKG'],
            'HANGAL' => ['name' => 'Hangal APMC', 'district' => 'Haveri', 'code' => 'KA_APMC_HGL'],
            'HALIYALA' => ['name' => 'Haliyala APMC', 'district' => 'Uttara Kannada', 'code' => 'KA_APMC_HLY'],
            'HONNAVAR' => ['name' => 'Honnavar APMC', 'district' => 'Uttara Kannada', 'code' => 'KA_APMC_HNV'],
            'SIRSI' => ['name' => 'Sirsi APMC (TSS)', 'district' => 'Uttara Kannada', 'code' => 'KA_APMC_SRS'],
            'HARAPANAHALLI' => ['name' => 'Harapanahalli APMC', 'district' => 'Vijayanagara', 'code' => 'KA_APMC_HPH'],
            'KOTTUR' => ['name' => 'Kottur APMC', 'district' => 'Vijayanagara', 'code' => 'KA_APMC_KTR'],
            'HAGARI BOMMANA HALLI' => ['name' => 'Hagaribommanahalli APMC', 'district' => 'Vijayanagara', 'code' => 'KA_APMC_HBH'],
            'SANTHESARGUR' => ['name' => 'Santhesargur APMC', 'district' => 'Ballari', 'code' => 'KA_APMC_SSG'],
            'BALLARI' => ['name' => 'Ballari APMC', 'district' => 'Ballari', 'code' => 'KA_APMC_BAL'],
            'BADAMI' => ['name' => 'Badami APMC', 'district' => 'Bagalkote', 'code' => 'KA_APMC_BDM'],
            'BAGALKOTE' => ['name' => 'Bagalkote APMC', 'district' => 'Bagalkote', 'code' => 'KA_APMC_BGK'],
            'BAILHONGAL' => ['name' => 'Bailhongal APMC', 'district' => 'Belagavi', 'code' => 'KA_APMC_BLH'],
            'BELAGAVI' => ['name' => 'Belagavi APMC', 'district' => 'Belagavi', 'code' => 'KA_APMC_BLG'],
            'BELGAUM' => ['name' => 'Belagavi APMC', 'district' => 'Belagavi', 'code' => 'KA_APMC_BLG'],
            'HOLALKERE' => ['name' => 'Holalkere APMC', 'district' => 'Chitradurga', 'code' => 'KA_APMC_HLK'],
            'CHITRADURGA' => ['name' => 'Chitradurga APMC', 'district' => 'Chitradurga', 'code' => 'KA_APMC_CTA'],
            'MADIKERI' => ['name' => 'Madikeri Market', 'district' => 'Kodagu', 'code' => 'KA_MKT_MDK'],
            'TUMAKURU' => ['name' => 'Tumakuru APMC', 'district' => 'Tumakuru', 'code' => 'KA_APMC_TUM'],
            'TIPTUR' => ['name' => 'Tiptur APMC (Copra Market)', 'district' => 'Tumakuru', 'code' => 'KA_APMC_TIP'],
            'ARSIKERE' => ['name' => 'Arsikere', 'district' => 'Hassan', 'code' => 'KA_APMC_ASK'],
            'HASSAN' => ['name' => 'Hassan APMC', 'district' => 'Hassan', 'code' => 'KA_APMC_HAS'],
            'MANGALURU' => ['name' => 'Mangaluru APMC (Baikampady)', 'district' => 'Dakshina Kannada', 'code' => 'KA_APMC_MNG'],
            'MANGALORE' => ['name' => 'Mangaluru APMC (Baikampady)', 'district' => 'Dakshina Kannada', 'code' => 'KA_APMC_MNG'],
        ];

        foreach ($kramaMarkets as $rawKramaName => $mktInfo) {
            $district = District::where('name', $mktInfo['district'])->first();
            $districtId = $district ? $district->id : 1;

            // Find existing market or create new
            $market = Market::where('code', $mktInfo['code'])
                ->orWhere('name', $mktInfo['name'])
                ->first();

            if (!$market) {
                $market = Market::create([
                    'district_id' => $districtId,
                    'name' => $mktInfo['name'],
                    'code' => $mktInfo['code'],
                    'market_type' => 'apmc',
                    'is_active' => true,
                ]);
            }

            // Map KRAMA uppercase source name to this market
            MarketSourceMapping::updateOrCreate(
                [
                    'data_source_id' => $krama->id,
                    'source_market_name' => $rawKramaName,
                ],
                [
                    'market_id' => $market->id,
                    'confidence_score' => 1.0,
                    'is_verified' => true,
                ]
            );
        }
    }
}
