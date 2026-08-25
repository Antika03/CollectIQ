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
        Schema::create('risk_score_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical']);
            $table->json('factors');
            $table->string('algorithm_version', 20);
            $table->timestamp('calculated_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['customer_id', 'calculated_at']);
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_score_logs');
    }
};
