<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Services\CustomerSyncService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Admin CollectIQ',
                'email' => 'admin@collectiq.telkom.co.id',
            ]);
        }

        // Auto-seed C3MR master customers if database has less than 1000 records
        if (Customer::count() < 1000) {
            $csvPath = storage_path('app/sheet_data-all.csv');
            if (file_exists($csvPath)) {
                try {
                    CustomerSyncService::syncFromDataAllCsv($csvPath);
                    Log::info("[DatabaseSeeder] Berhasil auto-seed 26.526 master customer C3MR.");
                } catch (\Throwable $e) {
                    Log::warning("[DatabaseSeeder] Auto-seed customer warning: " . $e->getMessage());
                }
            }
        }
    }
}
