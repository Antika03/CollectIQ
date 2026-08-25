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
        Schema::create('telegram_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ar_agent_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promise_to_pay_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('follow_up_recommendation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['ptp_due', 'ptp_overdue', 'follow_up', 'custom']);
            $table->dateTime('scheduled_at');
            $table->dateTime('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('message');
            $table->json('telegram_response')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['ar_agent_id', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_reminders');
    }
};
