<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to retire TSS Sirsi, data.gov.in, and legacy CEDA providers.
     */
    public function up(): void
    {
        $retiredCodes = ['data_gov_mandi', 'tss_sirsi', 'ceda_agmarknet'];

        $sourceIds = DB::table('data_sources')
            ->whereIn('code', $retiredCodes)
            ->pluck('id')
            ->toArray();

        if (empty($sourceIds)) {
            return;
        }

        DB::transaction(function () use ($sourceIds, $retiredCodes) {
            // 1. Delete dependent staging raw records
            if (Schema::hasTable('market_price_raw')) {
                DB::table('market_price_raw')->whereIn('data_source_id', $sourceIds)->delete();
            }

            // 2. Delete crop and market source mappings
            if (Schema::hasTable('crop_source_mappings')) {
                DB::table('crop_source_mappings')->whereIn('data_source_id', $sourceIds)->delete();
            }

            if (Schema::hasTable('market_source_mappings')) {
                DB::table('market_source_mappings')->whereIn('data_source_id', $sourceIds)->delete();
            }

            // 3. Delete field mappings and credentials
            if (Schema::hasTable('data_source_mappings')) {
                DB::table('data_source_mappings')->whereIn('data_source_id', $sourceIds)->delete();
            }

            if (Schema::hasTable('data_source_credentials')) {
                DB::table('data_source_credentials')->whereIn('data_source_id', $sourceIds)->delete();
            }

            // 4. Delete sync logs and api health logs
            if (Schema::hasTable('sync_logs')) {
                DB::table('sync_logs')->whereIn('data_source_id', $sourceIds)->delete();
            }

            if (Schema::hasTable('api_health_logs')) {
                DB::table('api_health_logs')->whereIn('data_source_id', $sourceIds)->delete();
            }

            if (Schema::hasTable('market_arrivals')) {
                DB::table('market_arrivals')->whereIn('data_source_id', $sourceIds)->delete();
            }

            // 5. In canonical market_prices, reassign any dangling records to KRAMA
            if (Schema::hasTable('market_prices')) {
                $kramaId = DB::table('data_sources')->where('code', 'krama_karnataka')->value('id');
                if ($kramaId) {
                    DB::table('market_prices')->whereIn('data_source_id', $sourceIds)->update(['data_source_id' => $kramaId]);
                }
            }

            // 6. Delete the retired data source records
            DB::table('data_sources')->whereIn('id', $sourceIds)->delete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: retired legacy data sources are not restored
    }
};
