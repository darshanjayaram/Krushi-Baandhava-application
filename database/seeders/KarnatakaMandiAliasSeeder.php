<?php

namespace Database\Seeders;

use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketSourceMapping;
use Illuminate\Database\Seeder;

class KarnatakaMandiAliasSeeder extends Seeder
{
    /**
     * Seed comprehensive alias mappings for all 49 Karnataka APMC mandis & commodity board centres.
     */
    public function run(): void
    {
        // 1. Target data sources to map (Government Mandi Feeds)
        $dataSources = DataSource::whereIn('code', ['data_gov_mandi', 'ceda_agmarknet'])->get();
        if ($dataSources->isEmpty()) {
            return;
        }

        // 2. Comprehensive Karnataka Mandi Alias Dictionary: Market Code => array of raw feed aliases
        $mandiAliasMap = [
            // North Karnataka Mandis
            'KA_APMC_BYD' => ['Byadgi', 'Byadagi', 'Byadgi(Chilly)', 'Byadgi (Chilli)', 'Byadgi APMC'],
            'KA_APMC_KLB' => ['Gulbarga', 'Kalaburagi', 'Gulbarga (Tur)', 'Kalaburagi (Tur)', 'Gulbarga APMC'],
            'KA_APMC_VJP' => ['Bijapur', 'Vijayapura', 'Bijapur APMC', 'Vijayapura APMC'],
            'KA_APMC_BGK' => ['Bagalkot', 'Bagalkote', 'Bagalkot APMC'],
            'KA_APMC_HSP' => ['Hospet', 'Hosapete', 'Hospet APMC', 'Hosapete APMC'],
            'KA_APMC_CTA' => ['Chitradurga', 'Chitradurg', 'Chitradurga APMC'],
            'KA_APMC_BDR' => ['Bidar', 'Bidar APMC'],
            'KA_APMC_YDG' => ['Yadgir', 'Yadgiri', 'Yadgir APMC'],
            'KA_APMC_KPL' => ['Koppal', 'Koppala', 'Gangavathi', 'Koppal APMC'],
            'KA_APMC_BAL' => ['Bellary', 'Ballari', 'Bellary APMC', 'Ballari APMC'],
            'KA_APMC_BLG' => ['Belgaum', 'Belagavi', 'Belgaum APMC', 'Belagavi APMC'],
            'KA_APMC_GDG' => ['Gadag', 'Gadag APMC'],
            'KA_APMC_HUB' => ['Hubli', 'Hubballi', 'Hubli APMC', 'Hubballi APMC'],
            'KA_APMC_RCH' => ['Raichur', 'Raichur APMC', 'Raichur (Cotton & Paddy)'],

            // Coastal & Malnad Mandis
            'KA_APMC_UDP' => ['Udupi', 'Udupi APMC'],
            'KA_APMC_KND' => ['Kundapura', 'Kundapur', 'Kundapura APMC'],
            'KA_APMC_MNG' => ['Mangalore', 'Mangaluru', 'Mangalore APMC', 'Baikampady', 'Mangaluru (Baikampady)'],
            'KA_APMC_PUT' => ['Puttur', 'Puttur APMC'],
            'KA_APMC_SRS' => ['Sirsi', 'Sirsi TSS', 'Sirsi (TSS)', 'TSS Sirsi', 'Sirsi APMC'],
            'KA_APMC_SHI' => ['Shimoga', 'Shivamogga', 'Shimoga APMC', 'Shivamogga APMC'],
            'KA_APMC_SAG' => ['Sagar', 'Sagara', 'Sagar APMC', 'Sagara APMC'],
            'KA_APMC_THI' => ['Thirthahalli', 'Tirthahalli', 'Thirthahalli APMC'],
            'KA_APMC_CKM' => ['Chikkamagaluru', 'Chikmagalur', 'Chikkamagaluru APMC'],
            'KA_APMC_MUD' => ['Mudigere', 'Mudigere Sub-Market', 'Mudigere Market'],
            'KA_APMC_KAD' => ['Kadur', 'Kadur APMC'],
            'KA_APMC_SAK' => ['Sakleshpur', 'Sakaleshpur', 'Sakleshpur APMC'],
            'KA_MKT_MDK'  => ['Madikeri', 'Mercara', 'Madikeri Market', 'Kodagu Market'],

            // Central & South Karnataka Mandis
            'KA_APMC_DVG' => ['Davanagere', 'Davangere', 'Davanagere APMC'],
            'KA_APMC_CHN' => ['Channagiri', 'Channagiri APMC'],
            'KA_APMC_HAS' => ['Hassan', 'Hassan APMC'],
            'KA_APMC_MDY' => ['Mandya', 'Mandya APMC'],
            'KA_APMC_MYS' => ['Mysore', 'Mysuru', 'Bandipalya', 'Mysore (Bandipalya)', 'Mysuru (Bandipalya)', 'Mysuru APMC'],
            'KA_APMC_CMR' => ['Chamarajanagar', 'Chamarajanagara', 'Santhemarahalli', 'Chamarajanagar APMC'],
            'KA_APMC_TUM' => ['Tumkur', 'Tumakuru', 'Tumkur APMC', 'Tumakuru APMC'],
            'KA_APMC_TIP' => ['Tiptur', 'Tiptur APMC', 'Tiptur(Copra Market)', 'Tiptur (Copra Market)'],
            'KA_APMC_KLR' => ['Kolar', 'Kolar APMC', 'Kolar(Tomato Market)', 'Kolar (Tomato Market)'],
            'KA_APMC_CKB' => ['Chikkaballapur', 'Chikkaballapura', 'Chintamani', 'Chikkaballapur APMC'],
            'KA_APMC_DEV' => ['Devanahalli', 'Devanahally', 'Devanahalli APMC'],
            'KA_APMC_BNM' => ['Binny Mill', 'Binny Mill (F&V)', 'BINNY MILL', 'BINNY MILL (F&V)'],
            'KA_APMC_YPR' => ['Yeshwanthpur', 'Yeshwantpur', 'Yeshwanthpur APMC'],
            'KA_MKT_RAM'  => ['Ramanagara', 'Ramanagaram', 'Ramanagara Silk Market', 'Ramanagara Silk Cocoon Market'],
        ];

        // 3. Insert or update mappings for both data sources
        foreach ($dataSources as $source) {
            foreach ($mandiAliasMap as $marketCode => $aliases) {
                $market = Market::where('code', $marketCode)->first();
                if (!$market) {
                    continue;
                }

                foreach ($aliases as $alias) {
                    MarketSourceMapping::updateOrCreate(
                        [
                            'data_source_id' => $source->id,
                            'source_market_name' => $alias,
                            'source_district_name' => $market->district?->name,
                        ],
                        [
                            'market_id' => $market->id,
                            'confidence_score' => 1.00,
                            'is_verified' => true,
                        ]
                    );
                }
            }
        }
    }
}
