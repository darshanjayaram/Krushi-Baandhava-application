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
        // 1. Target canonical town markets
        $ckmMkt = DB::table('markets')->where('code', 'KA_APMC_CKM')->first(); // Chikkamagaluru
        $mdkMkt = DB::table('markets')->where('code', 'KA_MKT_MDK')->first();  // Madikeri
        $hsnMkt = DB::table('markets')->where('code', 'KA_APMC_HAS')->first(); // Hassan
        $skpMkt = DB::table('markets')->where('code', 'KA_APMC_SAK')->first(); // Sakleshpur

        // 2. Normalize Madikeri name to simple "Madikeri" / "ಮಡಿಕೇರಿ"
        if ($mdkMkt) {
            DB::table('markets')->where('id', $mdkMkt->id)->update([
                'name' => 'Madikeri',
                'name_kn' => 'ಮಡಿಕೇರಿ',
                'updated_at' => now(),
            ]);
        }

        // 3. Mapping from Coffee Board specialized market IDs to canonical town market IDs
        $cbCkm = DB::table('markets')->where('code', 'CB_CKM')->first();
        $cbMdk = DB::table('markets')->where('code', 'CB_MDK')->first();
        $cbHsn = DB::table('markets')->where('code', 'CB_HSN')->first();
        $cbSkp = DB::table('markets')->where('code', 'CB_SKP')->first();

        $marketIdMap = [];
        if ($cbCkm && $ckmMkt) $marketIdMap[$cbCkm->id] = $ckmMkt->id;
        if ($cbMdk && $mdkMkt) $marketIdMap[$cbMdk->id] = $mdkMkt->id;
        if ($cbHsn && $hsnMkt) $marketIdMap[$cbHsn->id] = $hsnMkt->id;
        if ($cbSkp && $skpMkt) $marketIdMap[$cbSkp->id] = $skpMkt->id;

        if (empty($marketIdMap)) {
            return;
        }

        $cbMarketIds = array_keys($marketIdMap);

        // 4. Reassign market_prices
        foreach ($marketIdMap as $fromId => $toId) {
            $collidingPrices = DB::table('market_prices')
                ->where('market_id', $fromId)
                ->get();

            foreach ($collidingPrices as $p) {
                $exists = DB::table('market_prices')
                    ->where('crop_id', $p->crop_id)
                    ->where('market_id', $toId)
                    ->where('price_date', $p->price_date)
                    ->where('variety_id_key', $p->variety_id_key ?? ($p->variety_id ?? 0))
                    ->exists();

                if ($exists) {
                    DB::table('market_prices')->where('id', $p->id)->delete();
                } else {
                    DB::table('market_prices')->where('id', $p->id)->update(['market_id' => $toId]);
                }
            }
        }

        // 5. Reassign price_monthly_statistics (delete duplicates first to satisfy unique key)
        foreach ($marketIdMap as $fromId => $toId) {
            $monthlyRecords = DB::table('price_monthly_statistics')
                ->where('market_id', $fromId)
                ->get();

            foreach ($monthlyRecords as $rec) {
                $duplicate = DB::table('price_monthly_statistics')
                    ->where('crop_id', $rec->crop_id)
                    ->where('variety_id', $rec->variety_id)
                    ->where('market_id', $toId)
                    ->where('year', $rec->year)
                    ->where('month', $rec->month)
                    ->first();

                if ($duplicate) {
                    DB::table('price_monthly_statistics')->where('id', $rec->id)->delete();
                } else {
                    DB::table('price_monthly_statistics')->where('id', $rec->id)->update(['market_id' => $toId]);
                }
            }
        }

        // 6. Reassign market_source_mappings
        foreach ($marketIdMap as $fromId => $toId) {
            DB::table('market_source_mappings')
                ->where('market_id', $fromId)
                ->update(['market_id' => $toId]);
        }

        // 7. Ensure clean explicit mappings for Coffee Board data source
        $coffeeDs = DB::table('data_sources')->where('code', 'coffee_board')->first();
        if ($coffeeDs) {
            $cleanMappings = [
                'Chikkamagaluru' => $ckmMkt?->id,
                'Chikmagalur'    => $ckmMkt?->id,
                'Madikeri'       => $mdkMkt?->id,
                'Kodagu'         => $mdkMkt?->id,
                'Coorg'          => $mdkMkt?->id,
                'Sakleshpur'     => $skpMkt?->id,
                'Hassan'         => $hsnMkt?->id,
            ];

            foreach ($cleanMappings as $srcName => $targetId) {
                if ($targetId) {
                    DB::table('market_source_mappings')->updateOrInsert(
                        [
                            'data_source_id' => $coffeeDs->id,
                            'source_market_name' => $srcName,
                        ],
                        [
                            'market_id' => $targetId,
                            'confidence_score' => 1.0,
                            'is_verified' => true,
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        // 8. Delete the 4 specialized duplicate Coffee Board markets
        DB::table('markets')->whereIn('id', $cbMarketIds)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for permanent consolidation
    }
};
