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
            if (!Schema::hasColumn('customers', 'is_pranpc')) {
                $table->boolean('is_pranpc')->default(false)->after('risk_level');
                $table->index('is_pranpc');
            }
            if (!Schema::hasColumn('customers', 'bill_category')) {
                $table->string('bill_category', 50)->default('Eksisting')->after('is_pranpc');
                $table->index('bill_category');
            }
            if (!Schema::hasColumn('customers', 'assigned_ar_agent_id')) {
                $table->foreignId('assigned_ar_agent_id')->nullable()->after('bill_category')->constrained('ar_agents')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'assigned_ar_agent_id')) {
                $table->dropForeign(['assigned_ar_agent_id']);
                $table->dropColumn('assigned_ar_agent_id');
            }
            if (Schema::hasColumn('customers', 'bill_category')) {
                $table->dropIndex(['bill_category']);
                $table->dropColumn('bill_category');
            }
            if (Schema::hasColumn('customers', 'is_pranpc')) {
                $table->dropIndex(['is_pranpc']);
                $table->dropColumn('is_pranpc');
            }
        });
    }
};
