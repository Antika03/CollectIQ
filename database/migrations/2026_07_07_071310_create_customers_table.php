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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_internet', 50)->unique();
            $table->string('nama_pelanggan');
            $table->string('nama_layanan_internet')->nullable();
            $table->string('no_hp_terbaru', 20)->nullable();
            $table->string('tipe_hunian_terbaru', 100)->nullable();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->timestamp('last_visit_at')->nullable();
            $table->unsignedInteger('total_visits')->default(0);
            $table->unsignedInteger('broken_ptp_count')->default(0);
            $table->unsignedInteger('pending_ptp_count')->default(0);
            $table->timestamps();

            $table->index('nama_pelanggan');
            $table->index(['risk_level', 'risk_score']);
            $table->index('last_visit_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
