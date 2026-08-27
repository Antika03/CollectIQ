<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\C3mrSyncService;

class SyncC3mrDataCommand extends Command
{
    protected $signature   = 'c3mr:sync {--source=all : Sumber data yang akan disinkronkan (all, data_all, caring, report_prq, viseepro, performance)}';
    protected $description = 'Sinkronisasi seluruh data C3MR dari Google Spreadsheet ke database CollectIQ';

    public function handle(): int
    {
        $source = $this->option('source');
        $this->info("[C3MR] Memulai sinkronisasi C3MR (source: {$source})...");

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

            $this->newLine();
            $this->info("Total diproses  : " . number_format($result['total_rows_processed']));
            $this->info("Waktu sync      : {$result['duration_seconds']}s");
            $this->info("Terakhir sync   : {$result['last_sync_formatted']}");

            return $status === 'error' ? Command::FAILURE : Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Sync gagal: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
