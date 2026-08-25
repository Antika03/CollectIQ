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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'ncli')) {
                $table->string('ncli', 50)->nullable()->after('nomor_internet');
            }
            if (!Schema::hasColumn('customers', 'alamat')) {
                $table->text('alamat')->nullable()->after('nama_pelanggan');
            }
            if (!Schema::hasColumn('customers', 'sto')) {
                $table->string('sto', 50)->nullable()->after('alamat');
            }
            if (!Schema::hasColumn('customers', 'datel')) {
                $table->string('datel', 100)->nullable()->after('sto');
            }
            if (!Schema::hasColumn('customers', 'saldo_piutang')) {
                $table->decimal('saldo_piutang', 15, 2)->default(0)->after('risk_level');
            }
            if (!Schema::hasColumn('customers', 'umur_customer')) {
                $table->string('umur_customer', 50)->nullable()->after('saldo_piutang');
            }
            if (!Schema::hasColumn('customers', 'email')) {
                $table->string('email', 150)->nullable()->after('no_hp_terbaru');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'ncli',
                'alamat',
                'sto',
                'datel',
                'saldo_piutang',
                'umur_customer',
                'email',
            ]);
        });
    }
};
