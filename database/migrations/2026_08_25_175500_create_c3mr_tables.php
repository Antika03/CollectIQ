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
        Schema::create('caring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ar_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nomor_internet', 50)->index();
            $table->string('nama_pelanggan')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('petugas_caring', 100)->nullable();
            $table->date('tanggal_caring')->nullable()->index();
            $table->string('status_caring', 50)->default('UNCONTACTED')->index(); // CONTACTED / UNCONTACTED
            $table->string('voc', 100)->nullable()->index(); // BUSSY, RNA, Customer - Janji Bayar, dll
            $table->text('keterangan')->nullable();
            $table->string('frekuensi', 20)->nullable(); // 1X, 2X, dll
            $table->boolean('is_ptp')->default(false)->index();
            $table->date('tanggal_janji_bayar')->nullable();
            $table->string('status_bayar', 50)->nullable()->default('UNPAID')->index(); // PAID / UNPAID
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->string('bill_category', 50)->nullable(); // PRANPC, PSB, WINBACK
            $table->string('umur_customer', 50)->nullable();
            $table->timestamps();

            $table->index(['status_caring', 'status_bayar']);
            $table->index(['petugas_caring', 'tanggal_caring']);
        });

        Schema::create('witel_performances', function (Blueprint $table) {
            $table->id();
            $table->string('witel', 100)->index();
            $table->string('segmen', 50)->nullable(); // RBS, DGS, DSS, DPS
            $table->string('kategori', 50)->default('NONPOTS'); // NONPOTS, POTS, PRITI
            $table->decimal('billing', 18, 2)->default(0);
            $table->decimal('cash_coll', 18, 2)->default(0);
            $table->decimal('cyc_percent', 8, 2)->default(0);
            $table->decimal('cr_percent', 8, 2)->default(0);
            $table->decimal('c3mr_percent', 8, 2)->default(0);
            $table->decimal('saldo', 18, 2)->default(0);
            $table->decimal('gap', 18, 2)->default(0);
            $table->integer('rank')->nullable();
            $table->string('periode', 20)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('witel_performances');
        Schema::dropIfExists('caring_logs');
    }
};
