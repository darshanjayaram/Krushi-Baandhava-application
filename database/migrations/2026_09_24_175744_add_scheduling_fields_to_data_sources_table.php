<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_sources', function (Blueprint $table) {
            $table->string('sync_time', 50)->nullable()->default('06:00,18:00')->after('sync_frequency');
            $table->string('sync_days', 30)->default('mon_sat')->after('sync_time'); // mon_sat, all
            $table->string('cron_expression', 100)->nullable()->after('sync_days');
            $table->timestamp('last_heartbeat_at')->nullable()->after('last_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_sources', function (Blueprint $table) {
            $table->dropColumn(['sync_time', 'sync_days', 'cron_expression', 'last_heartbeat_at']);
        });
    }
};
