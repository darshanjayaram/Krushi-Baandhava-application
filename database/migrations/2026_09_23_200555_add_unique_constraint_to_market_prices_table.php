<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add a unique constraint to market_prices so that no two rows can
     * represent the same (crop, variety, market, date) combination.
     *
     * MariaDB 10.4 does not support functional indexes, so we handle the
     * nullable variety_id by adding a helper column `variety_id_key` (NOT NULL,
     * default 0) that mirrors variety_id (NULL → 0). The UNIQUE index is then
     * placed on (crop_id, market_id, price_date, variety_id_key).
     *
     * variety_id_key is kept in sync by the application layer (updateOrCreate
     * passes it along), and by a BEFORE INSERT / BEFORE UPDATE trigger for
     * any direct SQL writes.
     */
    public function up(): void
    {
        // Step 1: Delete any remaining duplicates before adding the constraint.
        // Keep the row with the highest id (most recently written).
        DB::statement("
            DELETE mp1
            FROM market_prices mp1
            INNER JOIN market_prices mp2
                ON  mp1.crop_id    = mp2.crop_id
                AND mp1.market_id  = mp2.market_id
                AND mp1.price_date = mp2.price_date
                AND COALESCE(mp1.variety_id, 0) = COALESCE(mp2.variety_id, 0)
                AND mp1.id < mp2.id
        ");

        // Step 2: Add the helper column that maps NULL variety → 0
        Schema::table('market_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('variety_id_key')->default(0)->after('variety_id')
                ->comment('Mirrors variety_id; NULL maps to 0. Used in the canonical unique index.');
        });

        // Step 3: Back-fill the column from existing data
        DB::statement("UPDATE market_prices SET variety_id_key = COALESCE(variety_id, 0)");

        // Step 4: Add the unique constraint
        Schema::table('market_prices', function (Blueprint $table) {
            $table->unique(
                ['crop_id', 'market_id', 'price_date', 'variety_id_key'],
                'market_prices_canonical_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            $table->dropUnique('market_prices_canonical_unique');
            $table->dropColumn('variety_id_key');
        });
    }
};
