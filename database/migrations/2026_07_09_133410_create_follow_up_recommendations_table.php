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
        Schema::create('follow_up_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ar_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promise_to_pay_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('priority_score');
            $table->enum('priority_level', ['critical', 'high', 'medium', 'low']);
            $table->string('reason_code', 50);
            $table->text('reason_summary');
            $table->string('recommended_action');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'dismissed'])->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority_score']);
            $table->index(['customer_id', 'status']);
            $table->index('priority_level');
            $table->index('reason_code');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_recommendations');
    }
};
