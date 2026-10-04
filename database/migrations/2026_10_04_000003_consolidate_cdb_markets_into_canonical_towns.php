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
        $askMkt = DB::table('markets')->where('code', 'KA_APMC_ASK')->first(); // Arsikere
        $tptMkt = DB::table('markets')->where('code', 'KA_APMC_TIP')->first(); // Tiptur
        $mngMkt = DB::table('markets')->where('code', 'KA_APMC_MNG')->first(); // Mangaluru (Baikampady)
        $tmkMkt = DB::table('markets')->where('code', 'KA_APMC_TUM')->first(); // Tumakuru
        $mdyMkt = DB::table('markets')->where('code', 'KA_APMC_MDY')->first(); // Mandya

        // 2. Specialized CDB market IDs to canonical town market IDs
        $cdbAsk = DB::table('markets')->where('code', 'CDB_ASK')->first();
        $cdbTpt = DB::table('markets')->where('code', 'CDB_TPT')->first();
        $cdbMlr = DB::table('markets')->where('code', 'CDB_MLR')->first();
        $cdbTmk = DB::table('markets')->where('code', 'CDB_TMK')->first();
        $cdbMdy = DB::table('markets')->where('code', 'CDB_MDY')->first();

        $marketIdMap = [];
        if ($cdbAsk && $askMkt) $marketIdMap[$cdbAsk->id] = $askMkt->id;
        if ($cdbTpt && $tptMkt) $marketIdMap[$cdbTpt->id] = $tptMkt->id;
        if ($cdbMlr && $mngMkt) $marketIdMap[$cdbMlr->id] = $mngMkt->id;
        if ($cdbTmk && $tmkMkt) $marketIdMap[$cdbTmk->id] = $tmkMkt->id;
        if ($cdbMdy && $mdyMkt) $marketIdMap[$cdbMdy->id] = $mdyMkt->id;

        if (empty($marketIdMap)) {
            return;
        }

        $cdbMarketIds = array_keys($marketIdMap);

        // 3. Reassign market_prices
        foreach ($marketIdMap as $fromId => $toId) {
            $prices = DB::table('market_prices')
                ->where('market_id', $fromId)
                ->get();

            foreach ($prices as $p) {
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

        // 4. Reassign price_monthly_statistics (delete duplicates first to satisfy unique key)
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

        // 5. Reassign market_source_mappings
        foreach ($marketIdMap as $fromId => $toId) {
            DB::table('market_source_mappings')
                ->where('market_id', $fromId)
                ->update(['market_id' => $toId]);
        }

        // 6. Ensure clean explicit mappings for Coconut Development Board data source (code: coconut_board)
        $cdbDs = DB::table('data_sources')->where('code', 'coconut_board')->first();
        if ($cdbDs) {
            $cleanMappings = [
                'Arsikere'  => $askMkt?->id,
                'Arisikere' => $askMkt?->id,
                'Tiptur'    => $tptMkt?->id,
                'Mangaluru' => $mngMkt?->id,
                'Mangalore' => $mngMkt?->id,
                'Tumakuru'  => $tmkMkt?->id,
                'Tumkur'    => $tmkMkt?->id,
                'Mandya'    => $mdyMkt?->id,
            ];

            foreach ($cleanMappings as $srcName => $targetId) {
                if ($targetId) {
                    DB::table('market_source_mappings')->updateOrInsert(
                        [
                            'data_source_id' => $cdbDs->id,
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

        // 7. Delete the specialized duplicate CDB markets
        DB::table('markets')->whereIn('id', $cdbMarketIds)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for permanent consolidation
    }
};
