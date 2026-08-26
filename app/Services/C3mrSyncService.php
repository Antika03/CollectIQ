<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\ViseeproData;
use App\Models\CaringLog;
use App\Models\WitelPerformance;
use App\Imports\ReportPrqImport;
use App\Imports\ViseeproImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class C3mrSyncService
{
    private static string $defaultSheetId = '1RjhMpP3pTlzONbuoRajODGz3tTGm3p73';

    /**
     * Ekstrak Spreadsheet ID dari berbagai format URL Google Spreadsheet
     */
    public static function extractSpreadsheetId(?string $url): string
    {
        if (empty($url)) {
            return self::$defaultSheetId;
        }

        if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Jika user langsung memasukkan Sheet ID
        if (preg_match('/^[a-zA-Z0-9-_]{20,60}$/', trim($url))) {
            return trim($url);
        }

        return self::$defaultSheetId;
    }

    /**
     * Dapatkan Spreadsheet ID terpusat dari database Setting
     */
    public static function getActiveSpreadsheetId(): string
    {
        $setting = Setting::first();
        if (!empty($setting?->c3mr_url)) {
            return self::extractSpreadsheetId($setting->c3mr_url);
        }

        if (!empty($setting?->report_prq_url)) {
            return self::extractSpreadsheetId($setting->report_prq_url);
        }

        return self::$defaultSheetId;
    }

    /**
     * Dapatkan URL penuh Google Spreadsheet C3MR yang sedang aktif
     */
    public static function getActiveSpreadsheetUrl(): string
    {
        $setting = Setting::first();
        if (!empty($setting?->c3mr_url)) {
            return $setting->c3mr_url;
        }

        $sheetId = self::getActiveSpreadsheetId();
        return "https://docs.google.com/spreadsheets/d/{$sheetId}/edit";
    }

    /**
     * Validasi apakah URL Google Spreadsheet valid dan dapat diakses publik
     */
    public static function validateSpreadsheetUrl(?string $url): array
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid'   => false,
                'message' => 'URL Google Spreadsheet tidak valid.',
            ];
        }

        if (!str_contains($url, 'docs.google.com/spreadsheets')) {
            return [
                'valid'   => false,
                'message' => 'URL harus berasal dari Google Spreadsheet (docs.google.com/spreadsheets).',
            ];
        }

        $sheetId = self::extractSpreadsheetId($url);
        if (empty($sheetId) || strlen($sheetId) < 10) {
            return [
                'valid'   => false,
                'message' => 'Spreadsheet ID tidak ditemukan dalam URL.',
            ];
        }

        // Uji koneksi ke endpoint export CSV publik
        $testUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";
        try {
            $response = Http::timeout(15)->withoutRedirecting()->get($testUrl);
            $status = $response->status();

            if ($status === 200 || $status === 302 || $status === 307) {
                return [
                    'valid'    => true,
                    'sheet_id' => $sheetId,
                    'message'  => 'Spreadsheet C3MR berhasil terhubung.',
                ];
            }

            if ($status === 403 || $status === 401) {
                return [
                    'valid'   => false,
                    'message' => 'Spreadsheet tidak dapat diakses (Akses dibatasi/Private). Pastikan izin sharing diatur ke "Siapa saja yang memiliki link" (Viewer).',
                ];
            }

            if ($status === 404) {
                return [
                    'valid'   => false,
                    'message' => 'Spreadsheet tidak ditemukan (HTTP 404). Periksa kembali Spreadsheet ID.',
                ];
            }

            return [
                'valid'   => true, // Tetap izinkan jika status redirect lain
                'sheet_id'=> $sheetId,
                'message' => 'Spreadsheet terhubung (HTTP ' . $status . ').',
            ];
        } catch (\Throwable $e) {
            Log::warning('[C3MR Sync] Validasi URL spreadsheet network error: ' . $e->getMessage());
            return [
                'valid'   => true, // Tidak memblokir offline development
                'sheet_id'=> $sheetId,
                'message' => 'URL disimpan (Koneksi langsung ke Google tidak dapat divalidasi saat ini: ' . $e->getMessage() . ').',
            ];
        }
    }

    /**
     * Convert Google Sheet URL ke CSV Export URL dengan GID tertentu
     */
    public static function convertToCsvUrl(?string $url, string $defaultGid = '0'): string
    {
        $sheetId = self::extractSpreadsheetId($url ?: self::getActiveSpreadsheetUrl());
        return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$defaultGid}";
    }

    /**
     * Pastikan direktori storage/app tersedia
     */
    private static function ensureStorageDir(): void
    {
        if (!File::isDirectory(storage_path('app'))) {
            File::makeDirectory(storage_path('app'), 0775, true);
        }
    }

    /**
     * 1. SINKRONISASI REPORT PRQ (Visit, Pelanggan, AR Agent)
     */
    public static function syncReportPrq(): array
    {
        self::ensureStorageDir();
        $sheetId = self::getActiveSpreadsheetId();
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid=1303511230";
        $csvFile = storage_path('app/sheet_report-prq.csv');

        Log::info("[C3MR Sync] Memulai sinkronisasi Report PRQ (Sheet ID: {$sheetId})...");

        try {
            $response = Http::timeout(45)->get($csvUrl);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvFile, $response->body());
                Log::info('[C3MR Sync] Berhasil mengunduh Report PRQ dari Google Spreadsheet (HTTP ' . $response->status() . ')');
            } elseif (!file_exists($csvFile)) {
                throw new \Exception("Gagal mengunduh Report PRQ dari spreadsheet (HTTP " . $response->status() . ").");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvFile)) {
                Log::error('[C3MR Sync] Report PRQ gagal: ' . $e->getMessage());
                return [
                    'success' => false,
                    'label'   => 'Report PRQ',
                    'count'   => 0,
                    'message' => 'Gagal mengambil data Report PRQ: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('[C3MR Sync] Report PRQ menggunakan cache lokal terakhir: ' . $e->getMessage());
        }

        try {
            $import = new ReportPrqImport();
            Excel::import($import, $csvFile);

            Log::info("[C3MR Sync] Report PRQ selesai diproses: {$import->processedCount} records (Created: {$import->createdVisits}, Updated: {$import->updatedVisits})");

            return [
                'success' => true,
                'label'   => 'Report PRQ',
                'count'   => $import->processedCount,
                'created' => $import->createdVisits,
                'updated' => $import->updatedVisits,
                'message' => "{$import->processedCount} data kunjungan & update pelanggan berhasil disinkronkan",
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[C3MR Sync] Report PRQ import error: ' . $e->getMessage());
            return [
                'success' => false,
                'label'   => 'Report PRQ',
                'count'   => 0,
                'message' => 'Gagal memproses data Report PRQ: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * 2. SINKRONISASI VISEEPRO (Aktivitas AR, Data Perusahaan & Pelanggan)
     */
    public static function syncViseepro(): array
    {
        self::ensureStorageDir();
        $sheetId = self::getActiveSpreadsheetId();
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid=172624186";
        $csvFile = storage_path('app/sheet_viseepro.csv');

        Log::info("[C3MR Sync] Memulai sinkronisasi VISEEPRO (Sheet ID: {$sheetId})...");

        try {
            $response = Http::timeout(45)->get($csvUrl);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvFile, $response->body());
                Log::info('[C3MR Sync] Berhasil mengunduh VISEEPRO dari Google Spreadsheet (HTTP ' . $response->status() . ')');
            } elseif (!file_exists($csvFile)) {
                throw new \Exception("Gagal mengunduh VISEEPRO dari spreadsheet (HTTP " . $response->status() . ").");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvFile)) {
                Log::error('[C3MR Sync] VISEEPRO gagal: ' . $e->getMessage());
                return [
                    'success' => false,
                    'label'   => 'VISEEPRO',
                    'count'   => 0,
                    'message' => 'Gagal mengambil data VISEEPRO: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('[C3MR Sync] VISEEPRO menggunakan cache lokal terakhir: ' . $e->getMessage());
        }

        try {
            $import = new ViseeproImport();
            Excel::import($import, $csvFile);

            Log::info("[C3MR Sync] VISEEPRO selesai diproses: {$import->processedCount} records (Created: {$import->createdCount}, Updated: {$import->updatedCount})");

            return [
                'success' => true,
                'label'   => 'VISEEPRO',
                'count'   => $import->processedCount,
                'created' => $import->createdCount,
                'updated' => $import->updatedCount,
                'message' => "{$import->processedCount} data aktivitas AR & PIC berhasil disinkronkan",
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[C3MR Sync] VISEEPRO import error: ' . $e->getMessage());
            return [
                'success' => false,
                'label'   => 'VISEEPRO',
                'count'   => 0,
                'message' => 'Gagal memproses data VISEEPRO: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * 3. SINKRONISASI C3MR DATA ALL (Master Customer, Alamat, Saldo Piutang, Nomor HP)
     */
    public static function syncDataAll(): array
    {
        self::ensureStorageDir();
        $sheetId = self::getActiveSpreadsheetId();
        $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode('DATA ALL');
        $csvPath = storage_path('app/sheet_data-all.csv');

        Log::info("[C3MR Sync] Memulai sinkronisasi C3MR Sheet DATA ALL (Sheet ID: {$sheetId})...");

        try {
            $response = Http::timeout(45)->get($url);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvPath, $response->body());
                Log::info('[C3MR Sync] Berhasil mengunduh Sheet DATA ALL dari Google Spreadsheet (HTTP ' . $response->status() . ')');
            } elseif (!file_exists($csvPath)) {
                throw new \Exception("Gagal mengunduh Sheet DATA ALL dari spreadsheet (HTTP " . $response->status() . ").");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvPath)) {
                Log::error('[C3MR Sync] Sheet DATA ALL gagal: ' . $e->getMessage());
                return [
                    'success' => false,
                    'label'   => 'C3MR Master Data (DATA ALL)',
                    'count'   => 0,
                    'message' => 'Gagal mengambil Sheet DATA ALL: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('[C3MR Sync] DATA ALL menggunakan cache lokal terakhir: ' . $e->getMessage());
        }

        try {
            $res = CustomerSyncService::syncFromDataAllCsv($csvPath);
            CustomerPhoneEnricher::enrichPhoneNumbers();

            Log::info("[C3MR Sync] DATA ALL selesai diproses: {$res['total_rows_processed']} baris (Updated: {$res['updated_customers']}, Created: {$res['created_customers']})");

            return [
                'success' => true,
                'label'   => 'C3MR Master Data (DATA ALL)',
                'count'   => $res['total_rows_processed'] ?? 0,
                'created' => $res['created_customers'] ?? 0,
                'updated' => $res['updated_customers'] ?? 0,
                'total'   => $res['total_customers_now'] ?? Customer::count(),
                'message' => "{$res['total_rows_processed']} master pelanggan & saldo piutang diperbarui",
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[C3MR Sync] DATA ALL sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'label'   => 'C3MR Master Data (DATA ALL)',
                'count'   => 0,
                'message' => 'Gagal memproses Sheet DATA ALL: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * 4. SINKRONISASI HASIL CARING (Log OBC PRITI, Status Bayar, VOC)
     */
    public static function syncCaring(): array
    {
        self::ensureStorageDir();
        $sheetId = self::getActiveSpreadsheetId();
        $csvPath = storage_path('app/sheet_data-all.csv');

        Log::info('[C3MR Sync] Memulai sinkronisasi C3MR Hasil Caring...');

        if (!file_exists($csvPath)) {
            $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode('DATA ALL');
            try {
                $response = Http::timeout(45)->get($url);
                if ($response->successful() && strlen($response->body()) > 20) {
                    file_put_contents($csvPath, $response->body());
                }
            } catch (\Throwable $e) {
                // Ignore download error, will check file_exists below
            }
        }

        if (!file_exists($csvPath)) {
            Log::error('[C3MR Sync] File sheet_data-all.csv tidak ditemukan untuk Hasil Caring');
            return [
                'success' => false,
                'label'   => 'C3MR Hasil Caring (OBC PRITI)',
                'count'   => 0,
                'message' => 'Sumber data Hasil Caring belum tersedia di server.',
                'error'   => 'File sheet_data-all.csv not found',
            ];
        }

        try {
            $res = C3mrCaringService::importCaringFromDataAll($csvPath);

            Log::info("[C3MR Sync] Hasil Caring selesai diproses: {$res['imported']} log baru, {$res['updated']} log diperbarui (Total: {$res['total_now']})");

            return [
                'success' => true,
                'label'   => 'C3MR Hasil Caring (OBC PRITI)',
                'count'   => ($res['imported'] ?? 0) + ($res['updated'] ?? 0),
                'created' => $res['imported'] ?? 0,
                'updated' => $res['updated'] ?? 0,
                'total'   => $res['total_now'] ?? CaringLog::count(),
                'message' => "{$res['imported']} log baru & {$res['updated']} log caring berhasil diperbarui",
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[C3MR Sync] Hasil Caring sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'label'   => 'C3MR Hasil Caring (OBC PRITI)',
                'count'   => 0,
                'message' => 'Gagal memproses data Hasil Caring: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * 5. SINKRONISASI PERFORMANSI WITEL DETAIL (Billing, Cash Coll, CR%, CYC%)
     */
    public static function syncPerformance(): array
    {
        self::ensureStorageDir();
        $sheetId = self::getActiveSpreadsheetId();
        $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode('PERFORMANSI DETAIL');
        $csvPath = storage_path('app/sheet_performansi-detail.csv');

        Log::info("[C3MR Sync] Memulai sinkronisasi C3MR Performansi Witel (Sheet ID: {$sheetId})...");

        try {
            $response = Http::timeout(45)->get($url);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvPath, $response->body());
                Log::info('[C3MR Sync] Berhasil mengunduh PERFORMANSI DETAIL dari Google Spreadsheet (HTTP ' . $response->status() . ')');
            } elseif (!file_exists($csvPath)) {
                throw new \Exception("Gagal mengunduh Sheet PERFORMANSI DETAIL dari spreadsheet (HTTP " . $response->status() . ").");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvPath)) {
                Log::error('[C3MR Sync] PERFORMANSI DETAIL gagal: ' . $e->getMessage());
                return [
                    'success' => false,
                    'label'   => 'C3MR Performansi Witel',
                    'count'   => 0,
                    'message' => 'Gagal mengambil Sheet PERFORMANSI DETAIL: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('[C3MR Sync] PERFORMANSI DETAIL menggunakan cache lokal terakhir: ' . $e->getMessage());
        }

        try {
            $res = C3mrCaringService::importWitelPerformance($csvPath);

            Log::info("[C3MR Sync] Performansi Witel selesai diproses: {$res['imported_witel']} witel diperbarui (Total: {$res['total_witel']})");

            return [
                'success' => true,
                'label'   => 'C3MR Performansi Witel',
                'count'   => $res['imported_witel'] ?? 0,
                'total'   => $res['total_witel'] ?? WitelPerformance::count(),
                'message' => "{$res['imported_witel']} witel regional berhasil diperbarui",
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[C3MR Sync] Performansi Witel sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'label'   => 'C3MR Performansi Witel',
                'count'   => 0,
                'message' => 'Gagal memproses Performansi Witel: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * 6. KONSOLIDASI AR AGENTS
     */
    public static function consolidateAr(): array
    {
        Log::info('[C3MR Sync] Memulai konsolidasi AR Agents...');
        try {
            $res = ArAgentService::consolidateAgents();
            Log::info("[C3MR Sync] Konsolidasi AR Agents selesai: {$res['final_agent_count']} agen unik (Merged: {$res['merged_count']})");

            return [
                'success' => true,
                'label'   => 'Normalisasi AR Agent',
                'count'   => $res['final_agent_count'] ?? 0,
                'merged'  => $res['merged_count'] ?? 0,
                'message' => "{$res['final_agent_count']} agent unik terkonsolidasi",
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[C3MR Sync] Consolidate AR error: ' . $e->getMessage());
            return [
                'success' => false,
                'label'   => 'Normalisasi AR Agent',
                'count'   => 0,
                'message' => 'Gagal normalisasi AR Agent: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Format tanggal ke teks Indonesia standar (Contoh: 26 Agustus 2026, 01:50)
     */
    public static function formatIndonesianDate(?Carbon $dt): string
    {
        if (!$dt) {
            return 'Belum pernah disinkronkan';
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $day = $dt->format('d');
        $month = $months[(int)$dt->format('m')] ?? $dt->format('F');
        $year = $dt->format('Y');
        $time = $dt->format('H:i');

        return "{$day} {$month} {$year}, {$time}";
    }

    /**
     * MASTER SYNC: SINKRONISASI SATU PINTU SELURUH DATA C3MR
     * Menjalankan Report PRQ, VISEEPRO, DATA ALL, HASIL CARING, PERFORMANSI DETAIL, & AR Agents.
     */
    public static function syncAll(): array
    {
        $startTime = microtime(true);
        $syncTimestamp = Carbon::now();

        Log::info('====================================================');
        Log::info('[C3MR Sync] MASTER SYNC DIMULAI pada ' . $syncTimestamp->toDateTimeString());

        // Jalankan seluruh proses sinkronisasi
        $results = [
            'report_prq'  => self::syncReportPrq(),
            'viseepro'    => self::syncViseepro(),
            'data_all'    => self::syncDataAll(),
            'caring'      => self::syncCaring(),
            'performance' => self::syncPerformance(),
            'ar_agents'   => self::consolidateAr(),
        ];

        // Evaluasi status keseluruhan
        $successCount = 0;
        $failCount = 0;
        $totalProcessed = 0;

        foreach ($results as $key => $res) {
            if (!empty($res['success'])) {
                $successCount++;
                $totalProcessed += ($res['count'] ?? 0);
            } else {
                $failCount++;
            }
        }

        $overallStatus = 'success';
        if ($failCount > 0 && $successCount > 0) {
            $overallStatus = 'warning';
        } elseif ($failCount > 0 && $successCount === 0) {
            $overallStatus = 'error';
        }

        // Format waktu lokal Indonesia
        $formattedDate = self::formatIndonesianDate($syncTimestamp);

        // Simpan riwayat Last Sync ke database Setting
        $activeUrl = self::getActiveSpreadsheetUrl();
        $setting = Setting::first();
        if (!$setting) {
            $setting = Setting::create([
                'c3mr_url'         => $activeUrl,
                'report_prq_url'   => $activeUrl,
                'viseepro_url'     => $activeUrl,
                'last_sync_at'     => $syncTimestamp,
                'last_sync_status' => $overallStatus,
                'last_sync_result' => $results,
            ]);
        } else {
            $setting->update([
                'c3mr_url'         => $setting->c3mr_url ?: $activeUrl,
                'last_sync_at'     => $syncTimestamp,
                'last_sync_status' => $overallStatus,
                'last_sync_result' => $results,
            ]);
        }

        $duration = round(microtime(true) - $startTime, 2);

        Log::info("[C3MR Sync] MASTER SYNC SELESAI ({$duration}s). Status: {$overallStatus}, Total Processed: {$totalProcessed}, Success: {$successCount}/" . count($results));
        Log::info('====================================================');

        return [
            'success'              => $overallStatus !== 'error',
            'status'               => $overallStatus,
            'status_label'         => $overallStatus === 'success' 
                                        ? 'Sinkronisasi berhasil' 
                                        : ($overallStatus === 'warning' ? 'Sinkronisasi selesai dengan beberapa masalah' : 'Sinkronisasi gagal'),
            'total_sources'        => count($results),
            'success_sources'      => $successCount,
            'failed_sources'       => $failCount,
            'total_rows_processed' => $totalProcessed,
            'duration_seconds'     => $duration,
            'last_sync_at'         => $syncTimestamp->format('Y-m-d H:i:s'),
            'last_sync_formatted'  => $formattedDate,
            'details'              => $results,
        ];
    }
}