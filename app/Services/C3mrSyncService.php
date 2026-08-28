<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\ViseeproData;
use App\Models\CaringLog;
use App\Models\WitelPerformance;
use App\Models\ArAgent;
use App\Helpers\CacheHelper;
use App\Imports\ReportPrqImport;
use App\Imports\ViseeproImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class C3mrSyncService
{
    private static string $defaultC3mrId = '1RjhMpP3pTlzONbuoRajODGz3tTGm3p73';

    /**
     * Ekstrak Spreadsheet ID dari berbagai format URL Google Spreadsheet
     */
    public static function extractSpreadsheetId(?string $url): string
    {
        if (empty($url)) {
            return self::$defaultC3mrId;
        }

        if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Jika user langsung memasukkan Sheet ID
        if (preg_match('/^[a-zA-Z0-9-_]{20,60}$/', trim($url))) {
            return trim($url);
        }

        return self::$defaultC3mrId;
    }

    /**
     * Dapatkan Spreadsheet ID C3MR terpusat dari database Setting
     */
    public static function getActiveSpreadsheetId(): string
    {
        $setting = Setting::first();
        if (!empty($setting?->c3mr_url)) {
            return self::extractSpreadsheetId($setting->c3mr_url);
        }

        return self::$defaultC3mrId;
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
                    'message'  => 'Spreadsheet berhasil terhubung.',
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
                'valid'   => true,
                'sheet_id'=> $sheetId,
                'message' => 'Spreadsheet terhubung (HTTP ' . $status . ').',
            ];
        } catch (\Throwable $e) {
            Log::warning('[Sync] Validasi URL spreadsheet network error: ' . $e->getMessage());
            return [
                'valid'   => true,
                'sheet_id'=> $sheetId,
                'message' => 'URL disimpan (Validasi koneksi langsung: ' . $e->getMessage() . ').',
            ];
        }
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
     * Unduh master workbook XLSX dari Google Spreadsheet dan ekstrak seluruh tab C3MR ke file CSV lokal
     */
    public static function downloadAndExtractMasterWorkbook(): array
    {
        self::ensureStorageDir();
        $sheetId = self::getActiveSpreadsheetId();
        $xlsxPath = storage_path('app/c3mr_master.xlsx');

        Log::info("[C3MR Sync] Mengunduh master workbook C3MR (Sheet ID: {$sheetId})...");

        $downloadSuccess = false;

        $xlsxUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=xlsx";
        try {
            $response = Http::timeout(60)->get($xlsxUrl);
            if ($response->successful() && strlen($response->body()) > 2000) {
                file_put_contents($xlsxPath, $response->body());
                $downloadSuccess = true;
                Log::info('[C3MR Sync] Berhasil mengunduh master XLSX via Google Docs export (' . round(strlen($response->body())/1024/1024, 2) . ' MB)');
            }
        } catch (\Throwable $e) {
            Log::warning('[C3MR Sync] Google Docs export format=xlsx gagal: ' . $e->getMessage());
        }

        if (!$downloadSuccess) {
            $driveUrl = "https://drive.google.com/uc?id={$sheetId}&export=download";
            try {
                $response = Http::timeout(60)->get($driveUrl);
                if ($response->successful() && strlen($response->body()) > 2000) {
                    file_put_contents($xlsxPath, $response->body());
                    $downloadSuccess = true;
                }
            } catch (\Throwable $e) {
                Log::warning('[C3MR Sync] Google Drive direct download gagal: ' . $e->getMessage());
            }
        }

        if (!$downloadSuccess && !file_exists($xlsxPath)) {
            throw new \Exception("Gagal mengunduh file spreadsheet C3MR dari Google. Pastikan Spreadsheet ID valid dan izin sharing terbuka (Viewer).");
        }

        // Ekstrak seluruh sheet yang dibutuhkan
        $sheetsToExtract = [
            'DATA ALL'           => storage_path('app/sheet_data-all.csv'),
            'Report PRQ'         => storage_path('app/sheet_report-prq.csv'),
            'VISEEPRO'           => storage_path('app/sheet_viseepro.csv'),
            'HASIL CARING'       => storage_path('app/sheet_hasil-caring.csv'),
            'PERFORMANSI DETAIL' => storage_path('app/sheet_performansi-detail.csv'),
        ];

        $extracted = [];
        foreach ($sheetsToExtract as $sheetName => $csvPath) {
            try {
                $res = XlsxSheetExtractor::extractSheetToCsv($xlsxPath, $sheetName, $csvPath);
                $extracted[$sheetName] = $res;
            } catch (\Throwable $e) {
                Log::warning("[C3MR Sync] Gagal mengekstrak sheet '{$sheetName}': " . $e->getMessage());
            }
        }

        return $extracted;
    }

    /**
     * 1. SINKRONISASI PRITI DATA (Sheet Collection: ~3.500 data kunjungan & Chat ID AR)
     */
    public static function syncPriti(): array
    {
        return PritiSyncService::syncCollection();
    }

    /**
     * 2. SINKRONISASI C3MR DATA ALL (Master Customer ~27.000 records, Saldo Piutang, PRANPC, HP)
     */
    public static function syncDataAll(): array
    {
        self::ensureStorageDir();
        $csvPath = storage_path('app/sheet_data-all.csv');

        if (!file_exists($csvPath)) {
            try {
                self::downloadAndExtractMasterWorkbook();
            } catch (\Throwable $e) {
                Log::warning('[C3MR Sync] Download master workbook gagal: ' . $e->getMessage());
            }
        }

        if (!file_exists($csvPath)) {
            return [
                'success'     => false,
                'label'       => 'C3MR Master Data (DATA ALL)',
                'count'       => 0,
                'source_rows' => 0,
                'created'     => 0,
                'updated'     => 0,
                'duplicates'  => 0,
                'skipped'     => 0,
                'total'       => Customer::count(),
                'message'     => 'Sumber data master pelanggan (DATA ALL) belum tersedia.',
                'error'       => 'sheet_data-all.csv not found',
            ];
        }

        try {
            $res = CustomerSyncService::syncFromDataAllCsv($csvPath);

            $totalSourceRows   = $res['total_source_rows'] ?? 0;
            $processedRows     = $res['total_rows_processed'] ?? 0;
            $createdCount      = $res['created_customers'] ?? 0;
            $updatedCount      = $res['updated_customers'] ?? 0;
            $duplicatesCount   = $res['duplicate_in_source'] ?? 0;
            $skippedCount      = $res['invalid_skipped'] ?? 0;
            $pranpcCount       = $res['pranpc_customers'] ?? 0;
            $totalCustomersNow = $res['total_customers_now'] ?? Customer::count();
            $validPhonesNow    = Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count();

            return [
                'success'             => true,
                'label'               => 'C3MR Master Data (DATA ALL)',
                'count'               => $processedRows,
                'source_rows'         => $totalSourceRows,
                'created'             => $createdCount,
                'updated'             => $updatedCount,
                'duplicates'          => $duplicatesCount,
                'skipped'             => $skippedCount,
                'pranpc_count'        => $pranpcCount,
                'total'               => $totalCustomersNow,
                'valid_phones'        => $validPhonesNow,
                'message'             => "{$processedRows} master pelanggan diproses ({$totalCustomersNow} total database, {$pranpcCount} PRANPC, {$validPhonesNow} HP valid)",
                'error'               => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[C3MR Sync] DATA ALL sync error: ' . $e->getMessage());
            return [
                'success'     => false,
                'label'       => 'C3MR Master Data (DATA ALL)',
                'count'       => 0,
                'source_rows' => 0,
                'created'     => 0,
                'updated'     => 0,
                'duplicates'  => 0,
                'skipped'     => 0,
                'total'       => Customer::count(),
                'message'     => 'Gagal memproses Sheet DATA ALL: ' . $e->getMessage(),
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * 3. SINKRONISASI REPORT PRQ (Visit, Pelanggan, AR Agent)
     */
    public static function syncReportPrq(): array
    {
        self::ensureStorageDir();
        $csvFile = storage_path('app/sheet_report-prq.csv');

        if (!file_exists($csvFile)) {
            return [
                'success' => true,
                'label'   => 'Report PRQ',
                'count'   => 0,
                'message' => 'Data Report PRQ dilewati (menggunakan data kunjungan PRITI)',
                'error'   => null,
            ];
        }

        try {
            $import = new ReportPrqImport();
            Excel::import($import, $csvFile);

            return [
                'success' => true,
                'label'   => 'Report PRQ',
                'count'   => $import->processedCount,
                'created' => 0,
                'updated' => $import->processedCount,
                'message' => "{$import->processedCount} data pelanggan & AR diproses (kunjungan bersumber dari PRITI DATA)",
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
     * 4. SINKRONISASI VISEEPRO (Aktivitas AR, Data Perusahaan & Pelanggan)
     */
    public static function syncViseepro(): array
    {
        self::ensureStorageDir();
        $csvFile = storage_path('app/sheet_viseepro.csv');

        if (!file_exists($csvFile)) {
            return [
                'success' => true,
                'label'   => 'VISEEPRO',
                'count'   => 0,
                'message' => 'Data VISEEPRO dilewati',
                'error'   => null,
            ];
        }

        try {
            $import = new ViseeproImport();
            Excel::import($import, $csvFile);

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
     * 5. SINKRONISASI HASIL CARING (Log OBC PRITI, Status Bayar, VOC)
     */
    public static function syncCaring(): array
    {
        self::ensureStorageDir();
        $csvPath = storage_path('app/sheet_data-all.csv');

        if (!file_exists($csvPath)) {
            return [
                'success' => false,
                'label'   => 'C3MR Hasil Caring (OBC PRITI)',
                'count'   => 0,
                'message' => 'Sumber data Hasil Caring belum tersedia di server.',
                'error'   => 'sheet_data-all.csv not found',
            ];
        }

        try {
            $res = C3mrCaringService::importCaringFromDataAll($csvPath);

            return [
                'success' => true,
                'label'   => 'C3MR Hasil Caring (OBC PRITI)',
                'count'   => ($res['imported'] ?? 0) + ($res['updated'] ?? 0),
                'created' => $res['imported'] ?? 0,
                'updated' => $res['updated'] ?? 0,
                'total'   => $res['total_now'] ?? CaringLog::count(),
                'message' => "{$res['imported']} log baru & {$res['updated']} log caring diperbarui",
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
     * 6. SINKRONISASI PERFORMANSI WITEL DETAIL (Billing, Cash Coll, CR%, CYC%)
     */
    public static function syncPerformance(): array
    {
        self::ensureStorageDir();
        $csvPath = storage_path('app/sheet_performansi-detail.csv');

        if (!file_exists($csvPath)) {
            return [
                'success' => false,
                'label'   => 'C3MR Performansi Witel',
                'count'   => 0,
                'message' => 'Gagal mengambil Sheet PERFORMANSI DETAIL',
                'error'   => 'sheet_performansi-detail.csv not found',
            ];
        }

        try {
            $res = C3mrCaringService::importWitelPerformance($csvPath);

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
     * 7. KONSOLIDASI AR AGENTS & PEMBERSIHAN CHANNEL NON-AR
     */
    public static function consolidateAr(): array
    {
        try {
            $res = ArAgentService::consolidateAgents();

            return [
                'success'        => true,
                'label'          => 'Normalisasi AR Agent',
                'count'          => $res['final_agent_count'] ?? 0,
                'merged'         => $res['merged_count'] ?? 0,
                'deleted_non_ar' => $res['deleted_non_ar_count'] ?? 0,
                'message'        => "{$res['final_agent_count']} agent unik terkonsolidasi ({$res['merged_count']} duplikat digabung, {$res['deleted_non_ar_count']} channel non-AR dibersihkan)",
                'error'          => null,
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
     * Format tanggal ke teks Indonesia standar (Contoh: 28 Agustus 2026, 08:30)
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

        return "{$day} {$month} {$year}, {$time} WIB";
    }

    /**
     * MASTER SYNC: SINKRONISASI SATU PINTU SELURUH DATA PRITI + C3MR
     * Menjalankan: PRITI Collection, C3MR DATA ALL, Report PRQ, VISEEPRO, HASIL CARING, PERFORMANSI DETAIL, & Konsolidasi AR.
     */
    public static function syncAll(): array
    {
        $startTime = microtime(true);
        $syncTimestamp = Carbon::now();

        Log::info('====================================================');
        Log::info('[Unified Sync] MASTER SYNC SATU PINTU DIMULAI pada ' . $syncTimestamp->toDateTimeString());

        // 0. Unduh workbook PRITI dan C3MR
        try {
            PritiSyncService::downloadAndExtractPriti();
        } catch (\Throwable $e) {
            Log::warning('[Unified Sync] Unduh PRITI warning: ' . $e->getMessage());
        }

        try {
            self::downloadAndExtractMasterWorkbook();
        } catch (\Throwable $e) {
            Log::warning('[Unified Sync] Unduh C3MR master workbook warning: ' . $e->getMessage());
        }

        // Jalankan seluruh proses sinkronisasi dengan DATA ALL sebagai FONDASI MASTER
        $results = [
            'data_all'    => self::syncDataAll(),
            'priti'       => self::syncPriti(),
            'report_prq'  => self::syncReportPrq(),
            'viseepro'    => self::syncViseepro(),
            'caring'      => self::syncCaring(),
            'performance' => self::syncPerformance(),
            'ar_agents'   => self::consolidateAr(),
        ];

        // Evaluasi status keseluruhan
        $successCount   = 0;
        $failCount      = 0;
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

        // Data Quality & Consistency Check (Diagnostic Telemetry)
        $dataAllStats   = $results['data_all'] ?? [];
        $sourceRows     = $dataAllStats['source_rows'] ?? 0;
        $dbTotalCust    = Customer::count();
        $isConsistent   = ($dbTotalCust > 0 && $sourceRows > 0 && ($dbTotalCust >= ($sourceRows * 0.8)));

        $difference = abs($sourceRows - $dbTotalCust);

        if ($dbTotalCust < 5000 && $sourceRows > 10000) {
            $overallStatus = 'error';
            $consistencyMsg = "KRITIKAL: Terjadi kegagalan integritas data! Sheet DATA ALL membaca {$sourceRows} baris, namun database hanya berisi {$dbTotalCust} pelanggan.";
        } elseif ($isConsistent) {
            $consistencyMsg = "Data master pelanggan konsisten ({$dbTotalCust} pelanggan terdaftar di database dari {$sourceRows} baris sheet sumber).";
        } else {
            $consistencyMsg = "Perhatian: Terdapat perbedaan data. Sheet DATA ALL: {$sourceRows} baris, Database: {$dbTotalCust} pelanggan (Selisih: {$difference}).";
            if ($overallStatus === 'success') {
                $overallStatus = 'warning';
            }
        }

        $dataQuality = [
            'source_sheet_rows'        => $sourceRows,
            'database_total_customers' => $dbTotalCust,
            'difference'               => $difference,
            'is_consistent'            => $isConsistent,
            'consistency_message'      => $consistencyMsg,
            'created_customers'        => $dataAllStats['created'] ?? 0,
            'updated_customers'        => $dataAllStats['updated'] ?? 0,
            'duplicate_in_source'      => $dataAllStats['duplicates'] ?? 0,
            'invalid_skipped'          => $dataAllStats['skipped'] ?? 0,
            'pranpc_customers'         => Customer::where('is_pranpc', true)->count(),
            'valid_phones_count'       => Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count(),
            'total_visits'             => Visit::count(),
            'total_ptp'                => Visit::where('is_ptp', true)->count(),
            'total_caring'             => CaringLog::count(),
            'total_ar_agents'          => ArAgent::count(),
        ];

        $formattedDate = self::formatIndonesianDate($syncTimestamp);

        // Simpan riwayat sync ke database Setting
        $activeC3mrUrl = self::getActiveSpreadsheetUrl();
        $activePritiUrl = PritiSyncService::getActivePritiUrl();
        $setting = Setting::first();
        $savePayload = array_merge($results, ['_data_quality' => $dataQuality]);

        if (!$setting) {
            $setting = Setting::create([
                'c3mr_url'         => $activeC3mrUrl,
                'priti_url'        => $activePritiUrl,
                'report_prq_url'   => $activeC3mrUrl,
                'viseepro_url'     => $activeC3mrUrl,
                'last_sync_at'     => $syncTimestamp,
                'last_sync_status' => $overallStatus,
                'last_sync_result' => $savePayload,
            ]);
        } else {
            $setting->update([
                'c3mr_url'         => $setting->c3mr_url ?: $activeC3mrUrl,
                'priti_url'        => $setting->priti_url ?: $activePritiUrl,
                'last_sync_at'     => $syncTimestamp,
                'last_sync_status' => $overallStatus,
                'last_sync_result' => $savePayload,
            ]);
        }

        // Bersihkan cache dashboard & visit agar data terbaru langsung tampil di UI
        CacheHelper::clearDashboardCaches();

        $duration = round(microtime(true) - $startTime, 2);

        Log::info("[Unified Sync] MASTER SYNC SELESAI ({$duration}s). Status: {$overallStatus}, Total Processed: {$totalProcessed}, DB Customers: {$dbTotalCust}");
        Log::info('====================================================');

        $statusLabel = $overallStatus === 'success'
            ? 'Sinkronisasi berhasil'
            : ($overallStatus === 'warning' ? 'Sinkronisasi selesai dengan catatan kualitas data' : 'Sinkronisasi gagal / Data Integrity Alert');

        return [
            'success'              => $overallStatus !== 'error',
            'status'               => $overallStatus,
            'status_label'         => $statusLabel,
            'total_sources'        => count($results),
            'success_sources'      => $successCount,
            'failed_sources'       => $failCount,
            'total_rows_processed' => $totalProcessed,
            'duration_seconds'     => $duration,
            'last_sync_at'         => $syncTimestamp->format('Y-m-d H:i:s'),
            'last_sync_formatted'  => $formattedDate,
            'data_quality'         => $dataQuality,
            'details'              => $results,
        ];
    }
}