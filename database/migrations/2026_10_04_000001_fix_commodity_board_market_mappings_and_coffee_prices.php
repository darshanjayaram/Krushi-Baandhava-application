<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Correct Coffee Board market source mappings
        $coffeeDs = DB::table('data_sources')->where('code', 'coffee_board')->first();
        if ($coffeeDs) {
            $ckmMkt = DB::table('markets')->where('code', 'CB_CKM')->first();
            $hsnMkt = DB::table('markets')->where('code', 'CB_HSN')->first();
            $mdkMkt = DB::table('markets')->where('code', 'CB_MDK')->first();
            $skpMkt = DB::table('markets')->where('code', 'CB_SKP')->first();

            $coffeeMappings = [
                'Chikkamagaluru' => $ckmMkt?->id,
                'Chikmagalur'    => $ckmMkt?->id,
                'Hassan'         => $hsnMkt?->id,
                'Madikeri'       => $mdkMkt?->id,
                'Kodagu'         => $mdkMkt?->id,
                'Coorg'          => $mdkMkt?->id,
                'Sakleshpur'     => $skpMkt?->id,
            ];

            foreach ($coffeeMappings as $sourceName => $marketId) {
                if ($marketId) {
                    DB::table('market_source_mappings')->updateOrInsert(
                        [
                            'data_source_id' => $coffeeDs->id,
                            'source_market_name' => $sourceName,
                        ],
                        [
                            'market_id' => $marketId,
                            'confidence_score' => 1.0,
                            'is_verified' => true,
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        // 2. Correct Coconut Development Board market source mappings
        $cdbDs = DB::table('data_sources')->where('code', 'coconut_board')->first();
        if ($cdbDs) {
            $askMkt = DB::table('markets')->where('code', 'CDB_ASK')->first();
            $tptMkt = DB::table('markets')->where('code', 'CDB_TPT')->first();
            $mlrMkt = DB::table('markets')->where('code', 'CDB_MLR')->first();
            $tmkMkt = DB::table('markets')->where('code', 'CDB_TMK')->first();
            $mdyMkt = DB::table('markets')->where('code', 'CDB_MDY')->first();

            $cdbMappings = [
                'Arsikere'  => $askMkt?->id,
                'Arisikere' => $askMkt?->id,
                'Tiptur'    => $tptMkt?->id,
                'Mangaluru' => $mlrMkt?->id,
                'Mangalore' => $mlrMkt?->id,
                'Tumakuru'  => $tmkMkt?->id,
                'Tumkur'    => $tmkMkt?->id,
                'Mandya'    => $mdyMkt?->id,
            ];

            foreach ($cdbMappings as $sourceName => $marketId) {
                if ($marketId) {
                    DB::table('market_source_mappings')->updateOrInsert(
                        [
                            'data_source_id' => $cdbDs->id,
                            'source_market_name' => $sourceName,
                        ],
                        [
                            'market_id' => $marketId,
                            'confidence_score' => 1.0,
                            'is_verified' => true,
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        // 3. Reassign existing coffee (crop_id = 2) prices from APMC markets to Coffee Board centres
        $coffeeCrop = DB::table('crops')->where('slug', 'coffee')->first() ?? DB::table('crops')->where('name', 'Coffee')->first();
        if ($coffeeCrop) {
            $targetCkm = DB::table('markets')->where('code', 'CB_CKM')->value('id');
            $targetSkp = DB::table('markets')->where('code', 'CB_SKP')->value('id');
            $targetMdk = DB::table('markets')->where('code', 'CB_MDK')->value('id');
            $targetHsn = DB::table('markets')->where('code', 'CB_HSN')->value('id');

            $apmcCkm = DB::table('markets')->where('code', 'KA_APMC_CKM')->value('id');
            $apmcSkp = DB::table('markets')->where('code', 'KA_APMC_SAK')->value('id');
            $apmcMdk = DB::table('markets')->where('code', 'KA_MKT_MDK')->value('id');
            $apmcHsn = DB::table('markets')->where('code', 'KA_APMC_HAS')->value('id');

            $reassignments = [
                $apmcCkm => $targetCkm,
                $apmcSkp => $targetSkp,
                $apmcMdk => $targetMdk,
                $apmcHsn => $targetHsn,
            ];

            foreach ($reassignments as $fromMarketId => $toMarketId) {
                if ($fromMarketId && $toMarketId) {
                    // Update any records from APMC to the corresponding Coffee Board market
                    DB::table('market_prices')
                        ->where('crop_id', $coffeeCrop->id)
                        ->where('market_id', $fromMarketId)
                        ->update(['market_id' => $toMarketId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for data normalization
    }
};
