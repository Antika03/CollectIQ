<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Customer;
use App\Services\CustomerSyncService;
use App\Services\C3mrSyncService;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memastikan database production Railway langsung terisi 26.526 master customer C3MR saat deployment
     */
    public function up(): void
    {
        if (Customer::count() < 1000) {
            $csvPath = storage_path('app/sheet_data-all.csv');
            if (file_exists($csvPath)) {
                try {
                    CustomerSyncService::syncFromDataAllCsv($csvPath);
                    Log::info("[Migration] Berhasil melakukan auto-seed 26.526 master customer C3MR dari sheet_data-all.csv");
                } catch (\Throwable $e) {
                    Log::warning("[Migration] Gagal auto-seed master customer: " . $e->getMessage());
                }
            } else {
                // Jika CSV belum ada, jalankan full sync workbook
                try {
                    C3mrSyncService::syncAll();
                } catch (\Throwable $e) {
                    Log::warning("[Migration] Full sync C3MR on migration warning: " . $e->getMessage());
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
