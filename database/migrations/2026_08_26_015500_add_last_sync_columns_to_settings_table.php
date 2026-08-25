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
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('viseepro_url');
            }
            if (!Schema::hasColumn('settings', 'last_sync_status')) {
                $table->string('last_sync_status', 50)->nullable()->after('last_sync_at');
            }
            if (!Schema::hasColumn('settings', 'last_sync_result')) {
                $table->text('last_sync_result')->nullable()->after('last_sync_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'last_sync_at',
                'last_sync_status',
                'last_sync_result',
            ]);
        });
    }
};
