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
            if (!Schema::hasColumn('settings', 'priti_url')) {
                $table->text('priti_url')->nullable()->after('c3mr_url');
            }
            if (!Schema::hasColumn('settings', 'telegram_reminder_enabled')) {
                $table->boolean('telegram_reminder_enabled')->default(true)->after('priti_url');
            }
            if (!Schema::hasColumn('settings', 'telegram_morning_time')) {
                $table->string('telegram_morning_time', 10)->default('08:30')->after('telegram_reminder_enabled');
            }
            if (!Schema::hasColumn('settings', 'telegram_afternoon_time')) {
                $table->string('telegram_afternoon_time', 10)->default('15:30')->after('telegram_morning_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $cols = ['priti_url', 'telegram_reminder_enabled', 'telegram_morning_time', 'telegram_afternoon_time'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
