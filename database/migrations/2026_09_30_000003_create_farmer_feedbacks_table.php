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
        Schema::create('farmer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 30)->unique();
            $table->enum('type', ['issue', 'feedback'])->default('issue')->index();
            $table->string('category', 100)->index();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('crop_name', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('market_name', 150)->nullable();
            $table->text('message')->nullable();
            $table->string('voice_path', 255)->nullable();
            $table->unsignedInteger('voice_duration')->nullable()->comment('Duration in seconds');
            $table->string('photo_path', 255)->nullable();
            $table->string('farmer_name', 120)->nullable();
            $table->string('farmer_phone', 20)->index();
            $table->string('farmer_email', 120)->nullable();
            $table->enum('status', ['new', 'in_review', 'resolved', 'rejected'])->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmer_feedbacks');
    }
};
