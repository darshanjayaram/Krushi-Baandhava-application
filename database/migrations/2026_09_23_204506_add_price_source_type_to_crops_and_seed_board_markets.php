<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add price_source_type to crops table
        if (!Schema::hasColumn('crops', 'price_source_type')) {
            Schema::table('crops', function (Blueprint $table) {
                $table->string('price_source_type', 32)->default('apmc')->after('slug')
                    ->comment('Source authority: apmc, coffee_board, coconut_board');
            });
        }

        // 2. Classify crops
        DB::table('crops')->where('slug', 'coffee')->orWhere('name', 'Coffee')
            ->update(['price_source_type' => 'coffee_board']);

        DB::table('crops')->whereIn('slug', ['coconut', 'copra', 'tender-coconut'])
            ->orWhereIn('name', ['Coconut', 'Copra', 'Tender Coconut'])
            ->update(['price_source_type' => 'coconut_board']);

        DB::table('crops')->whereNotIn('slug', ['coffee', 'coconut', 'copra', 'tender-coconut'])
            ->update(['price_source_type' => 'apmc']);

        // 3. Ensure essential Karnataka districts exist
        $state = DB::table('states')->where('code', 'KA')->first();
        $stateId = $state ? $state->id : 1;

        $districts = [
            ['name' => 'Chikkamagaluru', 'name_kn' => 'ಚಿಕ್ಕಮಗಳೂರು', 'code' => 'CKM', 'latitude' => 13.3161, 'longitude' => 75.7720],
            ['name' => 'Hassan', 'name_kn' => 'ಹಾಸನ', 'code' => 'HSN', 'latitude' => 13.0033, 'longitude' => 76.1004],
            ['name' => 'Kodagu', 'name_kn' => 'ಕೊಡಗು', 'code' => 'KDG', 'latitude' => 12.4244, 'longitude' => 75.7382],
            ['name' => 'Tumakuru', 'name_kn' => 'ತುಮಕೂರು', 'code' => 'TMK', 'latitude' => 13.3392, 'longitude' => 77.1017],
            ['name' => 'Dakshina Kannada', 'name_kn' => 'ದಕ್ಷಿಣ ಕನ್ನಡ', 'code' => 'DKN', 'latitude' => 12.9141, 'longitude' => 74.8560],
            ['name' => 'Bengaluru Urban', 'name_kn' => 'ಬೆಂಗಳೂರು ನಗರ', 'code' => 'BLR', 'latitude' => 12.9716, 'longitude' => 77.5946],
        ];

        $districtMap = [];
        foreach ($districts as $d) {
            $existing = DB::table('districts')->where('name', $d['name'])->first();
            if ($existing) {
                $districtMap[$d['name']] = $existing->id;
            } else {
                $id = DB::table('districts')->insertGetId([
                    'state_id' => $stateId,
                    'name' => $d['name'],
                    'name_kn' => $d['name_kn'],
                    'code' => $d['code'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $districtMap[$d['name']] = $id;
            }
        }

        // 4. Seed Coffee Board Curing Works & Purchase Centres
        $coffeeCentres = [
            [
                'district_id' => $districtMap['Chikkamagaluru'] ?? 2,
                'name' => 'Chikkamagaluru (Coffee Board)',
                'name_kn' => 'ಚಿಕ್ಕಮಗಳೂರು (ಕಾಫಿ ಮಂಡಳಿ)',
                'code' => 'CB_CKM',
                'market_type' => 'Coffee Board Centre',
                'latitude' => 13.3161,
                'longitude' => 75.7720,
                'address' => 'Coffee Board Technology Centre & Curing Works, Chikkamagaluru',
                'is_active' => true,
            ],
            [
                'district_id' => $districtMap['Kodagu'] ?? 2,
                'name' => 'Madikeri (Coffee Board)',
                'name_kn' => 'ಮಡಿಕೇರಿ (ಕಾಫಿ ಮಂಡಳಿ)',
                'code' => 'CB_MDK',
                'market_type' => 'Coffee Board Centre',
                'latitude' => 12.4244,
                'longitude' => 75.7382,
                'address' => 'Coffee Board Liaison Office, Madikeri, Kodagu',
                'is_active' => true,
            ],
            [
                'district_id' => $districtMap['Hassan'] ?? 2,
                'name' => 'Hassan (Coffee Board)',
                'name_kn' => 'ಹಾಸನ (ಕಾಫಿ ಮಂಡಳಿ)',
                'code' => 'CB_HSN',
                'market_type' => 'Coffee Board Centre',
                'latitude' => 13.0033,
                'longitude' => 76.1004,
                'address' => 'Coffee Quality Division & Board Depot, Hassan',
                'is_active' => true,
            ],
            [
                'district_id' => $districtMap['Hassan'] ?? 2,
                'name' => 'Sakleshpur (Coffee Board)',
                'name_kn' => 'ಸಕಲೇಶಪುರ (ಕಾಫಿ ಮಂಡಳಿ)',
                'code' => 'CB_SKP',
                'market_type' => 'Coffee Board Centre',
                'latitude' => 12.9733,
                'longitude' => 75.7865,
                'address' => 'Coffee Board Sub-Station, Sakleshpur',
                'is_active' => true,
            ],
        ];

        foreach ($coffeeCentres as $cc) {
            DB::table('markets')->updateOrInsert(
                ['code' => $cc['code']],
                array_merge($cc, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        // 5. Seed Coconut Development Board (CDB) Centres
        $cdbCentres = [
            [
                'district_id' => $districtMap['Hassan'] ?? 2,
                'name' => 'Arsikere (CDB Centre)',
                'name_kn' => 'ಅರಸೀಕೆರೆ (ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರ)',
                'code' => 'CDB_ASK',
                'market_type' => 'Coconut Board Centre',
                'latitude' => 13.3138,
                'longitude' => 76.2570,
                'address' => 'Coconut Development Board Field Office, Arsikere',
                'is_active' => true,
            ],
            [
                'district_id' => $districtMap['Tumakuru'] ?? 2,
                'name' => 'Tiptur (CDB Centre)',
                'name_kn' => 'ತಿಪಟೂರು (ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರ)',
                'code' => 'CDB_TPT',
                'market_type' => 'Coconut Board Centre',
                'latitude' => 13.2578,
                'longitude' => 76.4789,
                'address' => 'CDB Copra Grading & Market Promotion Centre, Tiptur',
                'is_active' => true,
            ],
            [
                'district_id' => $districtMap['Dakshina Kannada'] ?? 4,
                'name' => 'Mangaluru (CDB Centre)',
                'name_kn' => 'ಮಂಗಳೂರು (ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರ)',
                'code' => 'CDB_MLR',
                'market_type' => 'Coconut Board Centre',
                'latitude' => 12.9141,
                'longitude' => 74.8560,
                'address' => 'CDB Regional Office & Demonstration Centre, Mangaluru',
                'is_active' => true,
            ],
            [
                'district_id' => $districtMap['Tumakuru'] ?? 2,
                'name' => 'Tumakuru (CDB Centre)',
                'name_kn' => 'ತುಮಕೂರು (ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರ)',
                'code' => 'CDB_TMK',
                'market_type' => 'Coconut Board Centre',
                'latitude' => 13.3392,
                'longitude' => 77.1017,
                'address' => 'CDB Farmer Advisory & Copra Collection Centre, Tumakuru',
                'is_active' => true,
            ],
        ];

        foreach ($cdbCentres as $cdb) {
            DB::table('markets')->updateOrInsert(
                ['code' => $cdb['code']],
                array_merge($cdb, ['updated_at' => now(), 'created_at' => now()])
            );
        }

        // 6. Seed MarketSourceMapping for Coffee Board (data source code: coffee_board)
        $coffeeDs = DB::table('data_sources')->where('code', 'coffee_board')->first();
        if ($coffeeDs) {
            $ckmMkt = DB::table('markets')->where('code', 'CB_CKM')->first();
            $hsnMkt = DB::table('markets')->where('code', 'CB_HSN')->first();
            $mdkMkt = DB::table('markets')->where('code', 'CB_MDK')->first();
            $skpMkt = DB::table('markets')->where('code', 'CB_SKP')->first();

            $mappings = [
                ['source_market_name' => 'Chikkamagaluru', 'market_id' => $ckmMkt?->id],
                ['source_market_name' => 'Chikmagalur', 'market_id' => $ckmMkt?->id],
                ['source_market_name' => 'Hassan', 'market_id' => $hsnMkt?->id],
                ['source_market_name' => 'Madikeri', 'market_id' => $mdkMkt?->id],
                ['source_market_name' => 'Kodagu', 'market_id' => $mdkMkt?->id],
                ['source_market_name' => 'Coorg', 'market_id' => $mdkMkt?->id],
                ['source_market_name' => 'Sakleshpur', 'market_id' => $skpMkt?->id],
            ];

            foreach ($mappings as $m) {
                if ($m['market_id']) {
                    DB::table('market_source_mappings')->updateOrInsert(
                        [
                            'data_source_id' => $coffeeDs->id,
                            'source_market_name' => $m['source_market_name'],
                        ],
                        [
                            'market_id' => $m['market_id'],
                            'confidence_score' => 1.0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        // 7. Seed MarketSourceMapping for Coconut Board (data source code: coconut_board)
        $coconutDs = DB::table('data_sources')->where('code', 'coconut_board')->first();
        if ($coconutDs) {
            $askMkt = DB::table('markets')->where('code', 'CDB_ASK')->first();
            $tptMkt = DB::table('markets')->where('code', 'CDB_TPT')->first();
            $mlrMkt = DB::table('markets')->where('code', 'CDB_MLR')->first();
            $tmkMkt = DB::table('markets')->where('code', 'CDB_TMK')->first();

            $mappings = [
                ['source_market_name' => 'Arsikere', 'market_id' => $askMkt?->id],
                ['source_market_name' => 'Tiptur', 'market_id' => $tptMkt?->id],
                ['source_market_name' => 'Mangalore', 'market_id' => $mlrMkt?->id],
                ['source_market_name' => 'Mangaluru', 'market_id' => $mlrMkt?->id],
                ['source_market_name' => 'Tumakuru', 'market_id' => $tmkMkt?->id],
                ['source_market_name' => 'Tumkur', 'market_id' => $tmkMkt?->id],
            ];

            foreach ($mappings as $m) {
                if ($m['market_id']) {
                    DB::table('market_source_mappings')->updateOrInsert(
                        [
                            'data_source_id' => $coconutDs->id,
                            'source_market_name' => $m['source_market_name'],
                        ],
                        [
                            'market_id' => $m['market_id'],
                            'confidence_score' => 1.0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('crops', 'price_source_type')) {
            Schema::table('crops', function (Blueprint $table) {
                $table->dropColumn('price_source_type');
            });
        }

        DB::table('markets')->whereIn('code', [
            'CB_CKM', 'CB_MDK', 'CB_HSN', 'CB_SKP',
            'CDB_ASK', 'CDB_TPT', 'CDB_MLR', 'CDB_TMK'
        ])->delete();
    }
};
