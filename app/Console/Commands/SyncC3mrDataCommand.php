<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\C3mrSyncService;
use App\Models\Customer;

class SyncC3mrDataCommand extends Command
{
    protected $signature   = 'c3mr:sync {--source=all : Sumber data yang akan disinkronkan (all, data_all, caring, report_prq, viseepro, performance)}';
    protected $description = 'Sinkronisasi seluruh data C3MR dari Google Spreadsheet ke database CollectIQ';

    public function handle(): int
    {
        $source = $this->option('source');
        $this->info("================================================================");
        $this->info("             COLLECTIQ — FULL C3MR DATA SYNCHRONIZER            ");
        $this->info("================================================================");
        $this->line("Sumber Spreadsheet: " . C3mrSyncService::getActiveSpreadsheetId());
        $this->line("Mode: Master Unified Sync (Satu Pintu)");
        $this->newLine();

        $startTime = microtime(true);

        try {
            $result = C3mrSyncService::syncAll();

            $this->newLine();
            $status = $result['status'] ?? 'error';
            $icon   = $status === 'success' ? '✓' : ($status === 'warning' ? '⚠' : '✕');
            $this->line("{$icon} {$result['status_label']}");

            $this->table(['Sumber Data', 'Status', 'Diproses', 'Pesan'], array_map(function ($key, $res) {
                return [
                    $res['label']   ?? $key,
                    !empty($res['success']) ? 'Berhasil' : 'Gagal',
                    number_format($res['count'] ?? 0),
                    $res['message'] ?? '',
                ];
            }, array_keys($result['details']), $result['details']));

            // Data Quality Breakdown Table
            $dq = $result['data_quality'] ?? [];
            if (!empty($dq)) {
                $this->newLine();
                $this->info("DATA QUALITY & CONSISTENCY SUMMARY (DATA ALL):");
                $this->table(['Metrik Kualitas Data', 'Nilai'], [
                    ['Total Baris Mentah di Sheet', number_format($dq['source_sheet_rows'] ?? 0)],
                    ['Baris Valid Diproses', number_format($result['details']['data_all']['count'] ?? 0)],
                    ['Pelanggan Baru (Created)', number_format($dq['created_customers'] ?? 0)],
                    ['Pelanggan Diperbarui (Updated)', number_format($dq['updated_customers'] ?? 0)],
                    ['Duplikat di Sheet (Dide-duplikasi)', number_format($dq['duplicate_in_source'] ?? 0)],
                    ['Baris Dilewati / Invalid', number_format($dq['invalid_skipped'] ?? 0)],
                    ['Nomor HP Valid Terekstrak', number_format($dq['valid_phones_count'] ?? 0)],
                    ['Total Pelanggan di Database Saat Ini', number_format($dq['database_total_customers'] ?? Customer::count())],
                    ['Status Konsistensi', ($dq['is_consistent'] ?? false) ? '✓ KONSISTEN' : '⚠ PERINGATAN (Disparitas Data)'],
                ]);
            }

            $this->newLine();
            $this->info("Total Record Diproses : " . number_format($result['total_rows_processed']));
            $this->info("Waktu Eksekusi        : {$result['duration_seconds']} detik");
            $this->info("Terakhir Sinkronisasi : {$result['last_sync_formatted']}");
            $this->info("================================================================");

            return $status === 'error' ? Command::FAILURE : Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Sinkronisasi gagal: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
