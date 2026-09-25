<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\DataSource;
use App\Models\DataSourceCredential;
use App\Models\DataSourceMapping;
use App\Models\Market;
use App\Models\MarketSourceMapping;
use App\Services\DataSources\CoconutBoard\CoconutBoardDataProvider;
use App\Services\DataSources\CoffeeBoard\CoffeeBoardDataProvider;
use App\Services\DataSources\DataGov\DataGovMarketDataProvider;
use Illuminate\Database\Seeder;

class DataSourceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. data.gov.in Mandi Prices
        $dataGov = DataSource::firstOrCreate(
            ['code' => 'data_gov_mandi'],
            [
                'name' => 'data.gov.in Mandi Prices',
                'provider_class' => DataGovMarketDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://api.data.gov.in/resource',
                'endpoint' => 'current-daily-price-various-commodities-various-markets-mandi',
                'auth_type' => 'api_key',
                'sync_frequency' => 'daily',
                'is_active' => true,
                'timeout_seconds' => 30,
                'rate_limit_per_minute' => 60,
            ]
        );

        DataSourceCredential::firstOrCreate(
            ['data_source_id' => $dataGov->id],
            [
                'api_key' => 'mock_data_gov_in_api_key_krushi_2026',
            ]
        );

        $defaultMappings = [
            ['source_field' => 'Commodity', 'target_field' => 'crop_name', 'transformation_rule' => 'trim', 'is_required' => true],
            ['source_field' => 'Variety', 'target_field' => 'variety_name', 'transformation_rule' => 'trim', 'is_required' => false, 'default_value' => 'Local'],
            ['source_field' => 'Market', 'target_field' => 'market_name', 'transformation_rule' => 'trim', 'is_required' => true],
            ['source_field' => 'District', 'target_field' => 'district_name', 'transformation_rule' => 'trim', 'is_required' => false],
            ['source_field' => 'State', 'target_field' => 'state_name', 'transformation_rule' => 'trim', 'is_required' => false, 'default_value' => 'Karnataka'],
            ['source_field' => 'Arrival_Date', 'target_field' => 'price_date', 'transformation_rule' => 'date_format:d/m/Y', 'is_required' => true],
            ['source_field' => 'Min_Price', 'target_field' => 'min_price', 'transformation_rule' => 'to_number', 'is_required' => true],
            ['source_field' => 'Max_Price', 'target_field' => 'max_price', 'transformation_rule' => 'to_number', 'is_required' => true],
            ['source_field' => 'Modal_Price', 'target_field' => 'modal_price', 'transformation_rule' => 'to_number', 'is_required' => true],
            ['source_field' => 'Arrival_Quantity', 'target_field' => 'arrival_quantity', 'transformation_rule' => 'to_number', 'is_required' => false, 'default_value' => '0'],
        ];

        foreach ($defaultMappings as $m) {
            DataSourceMapping::firstOrCreate(
                ['data_source_id' => $dataGov->id, 'source_field' => $m['source_field']],
                $m
            );
        }

        // 2. Coffee Board (Direct Website Web Scraper)
        DataSource::updateOrCreate(
            ['code' => 'coffee_board'],
            [
                'name' => 'Coffee Board of India (Direct Web Scraper)',
                'provider_class' => CoffeeBoardDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://coffeeboard.gov.in',
                'endpoint' => '',
                'auth_type' => 'none',
                'sync_frequency' => 'daily',
                'is_active' => true,
                'timeout_seconds' => 20,
            ]
        );

        // 3. Coconut Development Board (Direct Website Web Scraper)
        DataSource::updateOrCreate(
            ['code' => 'coconut_board'],
            [
                'name' => 'Coconut Development Board (Direct Web Scraper)',
                'provider_class' => CoconutBoardDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://coconutboard.gov.in',
                'endpoint' => '',
                'auth_type' => 'none',
                'sync_frequency' => 'daily',
                'is_active' => true,
                'timeout_seconds' => 20,
            ]
        );

        // 6. Crop Alias Mappings for data.gov.in
        $cropAliases = [
            'Arecanut' => 'Arecanut',
            'Betelnut' => 'Arecanut',
            'Coffee' => 'Coffee',
            'Black Pepper' => 'Pepper',
            'Pepper' => 'Pepper',
            'Coconut' => 'Coconut',
            'Copra' => 'Copra',
            'Paddy' => 'Paddy',
            'Ragi' => 'Ragi',
            'Maize' => 'Maize',
            'Onion' => 'Onion',
            'Tomato' => 'Tomato',
            'Ginger' => 'Ginger',
            'Green Ginger' => 'Ginger',
            'Tender Coconut' => 'Tender Coconut',
        ];

        foreach ($cropAliases as $sourceName => $canonicalName) {
            $crop = Crop::where('name', $canonicalName)->first();
            if ($crop) {
                CropSourceMapping::firstOrCreate(
                    [
                        'data_source_id' => $dataGov->id,
                        'source_crop_name' => $sourceName,
                        'source_variety_name' => null,
                    ],
                    [
                        'crop_id' => $crop->id,
                        'confidence_score' => 1.00,
                        'is_verified' => true,
                    ]
                );
            }
        }

        // 7. Market Alias Mappings for data.gov.in
        $marketAliases = [
            'Shimoga' => 'Shivamogga APMC',
            'Sagar' => 'Sagar APMC',
            'Mangalore' => 'Mangaluru APMC',
            'Chikkamagaluru' => 'Chikkamagaluru APMC',
            'Davanagere' => 'Davanagere APMC',
            'Mandya' => 'Mandya APMC',
            'Mysuru (Bandipalya)' => 'Bandipalya APMC (Mysuru)',
            'Binny Mill (F&V)' => 'Binny Mill (F&V)',
            'Binny Mill' => 'Binny Mill (F&V)',
            'BINNY MILL (F&V)' => 'Binny Mill (F&V)',
            'BINNY MILL' => 'Binny Mill (F&V)',
            'Yeshwanthpur' => 'Yeshwanthpur APMC',
            'Kolar' => 'Kolar APMC (Tomato Market)',
            'Belgaum' => 'Belagavi APMC',
            'Belagavi' => 'Belagavi APMC',
            'Udupi' => 'Udupi APMC',
        ];

        foreach ($marketAliases as $rawMarket => $canonicalMarket) {
            $market = Market::where('name', $canonicalMarket)->first();
            if ($market) {
                MarketSourceMapping::firstOrCreate(
                    [
                        'data_source_id' => $dataGov->id,
                        'source_market_name' => $rawMarket,
                        'source_district_name' => null,
                    ],
                    [
                        'market_id' => $market->id,
                        'confidence_score' => 1.00,
                        'is_verified' => true,
                    ]
                );
            }
        }

        // 8. Market Mappings for Coffee Board
        $coffeeDs = DataSource::where('code', 'coffee_board')->first();
        if ($coffeeDs) {
            $coffeeMarketMap = [
                'Chikkamagaluru' => 'CB_CKM',
                'Chikmagalur'    => 'CB_CKM',
                'Hassan'         => 'CB_HSN',
                'Madikeri'       => 'CB_MDK',
                'Kodagu'         => 'CB_MDK',
                'Coorg'          => 'CB_MDK',
                'Sakleshpur'     => 'CB_SKP',
            ];
            foreach ($coffeeMarketMap as $sourceName => $targetCode) {
                $mkt = Market::where('code', $targetCode)->first();
                if ($mkt) {
                    MarketSourceMapping::updateOrCreate(
                        ['data_source_id' => $coffeeDs->id, 'source_market_name' => $sourceName],
                        ['market_id' => $mkt->id, 'confidence_score' => 1.0, 'is_verified' => true]
                    );
                }
            }
        }

        // 9. Market Mappings for Coconut Development Board
        $coconutDs = DataSource::where('code', 'coconut_board')->first();
        if ($coconutDs) {
            $cdbMarketMap = [
                'Arsikere'  => 'CDB_ASK',
                'Tiptur'    => 'CDB_TPT',
                'Mangaluru' => 'CDB_MLR',
                'Mangalore' => 'CDB_MLR',
                'Tumakuru'  => 'CDB_TMK',
                'Tumkur'    => 'CDB_TMK',
            ];
            foreach ($cdbMarketMap as $sourceName => $targetCode) {
                $mkt = Market::where('code', $targetCode)->first();
                if ($mkt) {
                    MarketSourceMapping::updateOrCreate(
                        ['data_source_id' => $coconutDs->id, 'source_market_name' => $sourceName],
                        ['market_id' => $mkt->id, 'confidence_score' => 1.0, 'is_verified' => true]
                    );
                }
            }
        }
    }
}
