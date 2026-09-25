<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\CropSourceMapping;
use App\Models\CropVariety;
use App\Models\DataSource;
use App\Models\DataSourceMapping;
use App\Models\Market;
use App\Models\MarketSourceMapping;
use App\Services\DataSources\TssSirsi\TssSirsiDataProvider;
use Illuminate\Database\Seeder;

class TssSirsiDataSourceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Register TSS Sirsi Data Source
        $tssSource = DataSource::firstOrCreate(
            ['code' => 'tss_sirsi'],
            [
                'name' => 'TSS Sirsi Cooperative Society (Arecanut Daily Tender)',
                'provider_class' => TssSirsiDataProvider::class,
                'type' => 'market_prices',
                'base_url' => 'https://web.tssindia.in',
                'endpoint' => 'tender/',
                'auth_type' => 'none',
                'sync_frequency' => 'daily',
                'sync_time' => '16:30',
                'sync_days' => 'mon_sat',
                'is_active' => true,
                'timeout_seconds' => 30,
                'rate_limit_per_minute' => 30,
            ]
        );

        // 2. Default Schema Field Mappings
        $mappings = [
            ['source_field' => 'Commodity', 'target_field' => 'crop_name', 'transformation_rule' => 'trim', 'is_required' => true],
            ['source_field' => 'Variety', 'target_field' => 'variety_name', 'transformation_rule' => 'trim', 'is_required' => true],
            ['source_field' => 'Market', 'target_field' => 'market_name', 'transformation_rule' => 'trim', 'is_required' => true],
            ['source_field' => 'District', 'target_field' => 'district_name', 'transformation_rule' => 'trim', 'is_required' => false, 'default_value' => 'Uttara Kannada'],
            ['source_field' => 'State', 'target_field' => 'state_name', 'transformation_rule' => 'trim', 'is_required' => false, 'default_value' => 'Karnataka'],
            ['source_field' => 'Arrival_Date', 'target_field' => 'price_date', 'transformation_rule' => 'date_format:Y-m-d', 'is_required' => true],
            ['source_field' => 'Min_Price', 'target_field' => 'min_price', 'transformation_rule' => 'to_number', 'is_required' => true],
            ['source_field' => 'Max_Price', 'target_field' => 'max_price', 'transformation_rule' => 'to_number', 'is_required' => true],
            ['source_field' => 'Modal_Price', 'target_field' => 'modal_price', 'transformation_rule' => 'to_number', 'is_required' => true],
            ['source_field' => 'Arrival_Quantity', 'target_field' => 'arrival_quantity', 'transformation_rule' => 'to_number', 'is_required' => false, 'default_value' => '100'],
        ];

        foreach ($mappings as $m) {
            DataSourceMapping::firstOrCreate(
                [
                    'data_source_id' => $tssSource->id,
                    'source_field' => $m['source_field'],
                ],
                $m
            );
        }

        // 3. Crop Mapping for Arecanut
        $arecaCrop = Crop::where('slug', 'arecanut')->orWhere('id', 1)->first();
        if ($arecaCrop) {
            CropSourceMapping::firstOrCreate(
                [
                    'data_source_id' => $tssSource->id,
                    'source_crop_name' => 'Arecanut',
                    'source_variety_name' => null,
                ],
                [
                    'crop_id' => $arecaCrop->id,
                    'confidence_score' => 1.00,
                    'is_verified' => true,
                ]
            );

            // Variety mappings
            $tssVarieties = ['Rashi', 'Chali', 'Bette', 'Bilegotu', 'Kempugotu'];
            foreach ($tssVarieties as $vName) {
                $variety = CropVariety::where('crop_id', $arecaCrop->id)->where('name', $vName)->first();
                if ($variety) {
                    CropSourceMapping::firstOrCreate(
                        [
                            'data_source_id' => $tssSource->id,
                            'source_crop_name' => 'Arecanut',
                            'source_variety_name' => $vName,
                        ],
                        [
                            'crop_id' => $arecaCrop->id,
                            'crop_variety_id' => $variety->id,
                            'confidence_score' => 1.00,
                            'is_verified' => true,
                        ]
                    );
                }
            }
        }

        // 4. Market Mapping for Sirsi
        $sirsiMarket = Market::where('code', 'SIRSI')->orWhere('name', 'like', '%Sirsi%')->first();
        if ($sirsiMarket) {
            $aliases = ['Sirsi APMC (TSS)', 'Sirsi', 'TSS Sirsi', 'TSS', 'Sirsi APMC'];
            foreach ($aliases as $alias) {
                MarketSourceMapping::firstOrCreate(
                    [
                        'data_source_id' => $tssSource->id,
                        'source_market_name' => $alias,
                        'source_district_name' => null,
                    ],
                    [
                        'market_id' => $sirsiMarket->id,
                        'confidence_score' => 1.00,
                        'is_verified' => true,
                    ]
                );
            }
        }
    }
}
