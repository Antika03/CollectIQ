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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('collect_id', 100)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ar_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal_input');
            $table->string('hasil_visit');
            $table->string('kategori_visit')->nullable();
            $table->text('keterangan_visit')->nullable();
            $table->text('foto_url')->nullable();
            $table->string('no_hp_snapshot', 255)->nullable();
            $table->string('tipe_hunian_snapshot', 100)->nullable();
            $table->boolean('is_ptp')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'tanggal_input']);
            $table->index(['ar_agent_id', 'tanggal_input']);
            $table->index('tanggal_input');
            $table->index('hasil_visit');
            $table->index('kategori_visit');
            $table->index('is_ptp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
