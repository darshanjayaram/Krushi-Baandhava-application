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
        Schema::create('notification_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 64)->default('custom_broadcast'); // daily_rates, weather, weekly_forecast, scheme, custom_broadcast
            $table->string('title_kn');
            $table->string('title_en')->nullable();
            $table->text('body_kn');
            $table->text('body_en')->nullable();
            $table->string('target_url')->default('/');
            $table->string('status', 32)->default('pending'); // pending, sending, sent, failed
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->text('error_details')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_broadcasts');
    }
};
