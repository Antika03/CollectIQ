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
        // Jika database belum memiliki seluruh customer data, lakukan seed dari master data CSV
        if (\App\Models\Customer::count() < 1000) {
            $csvPath = storage_path('app/sheet_data-all.csv');
            if (file_exists($csvPath)) {
                try {
                    \App\Services\CustomerSyncService::syncFromDataAllCsv($csvPath);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Initial seed customer warning: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
