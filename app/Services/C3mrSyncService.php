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
     * Convert Google Sheet URL ke CSV Export URL
     */
    public static function convertToCsvUrl(?string $url, string $defaultGid = '0'): string
    {
        if (empty($url)) {
            return "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/export?format=csv&gid={$defaultGid}";
        }

        if (
            str_contains($url, 'export?format=csv')
            || str_contains($url, 'export?format=xlsx')
            || str_contains($url, 'gviz/tq?tqx=out:csv')
        ) {
            return $url;
        }

        if (preg_match('/\/d\/([^\/]+)/', $url, $sheetMatch)) {
            $sheetId = $sheetMatch[1];
            $gid = $defaultGid;

            if (preg_match('/gid=(\d+)/', $url, $gidMatch)) {
                $gid = $gidMatch[1];
            }

            return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        }

        return $url;
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
        $setting = Setting::first();
        $rawUrl = $setting?->report_prq_url ?: "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/edit?gid=1303511230";
        $csvUrl = self::convertToCsvUrl($rawUrl, '1303511230');
        $csvFile = storage_path('app/sheet_report-prq.csv');

        try {
            $response = Http::timeout(60)->get($csvUrl);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvFile, $response->body());
            } elseif (!file_exists($csvFile)) {
                throw new \Exception("Gagal mengunduh Report PRQ dari spreadsheet dan cache lokal tidak tersedia.");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvFile)) {
                return [
                    'success' => false,
                    'label'   => 'Report PRQ',
                    'count'   => 0,
                    'message' => 'Gagal mengambil data Report PRQ: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('Report PRQ sync using local cached file due to network: ' . $e->getMessage());
        }

        try {
            $import = new ReportPrqImport();
            Excel::import($import, $csvFile);

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
            Log::error('Report PRQ import error: ' . $e->getMessage());
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
        $setting = Setting::first();
        $rawUrl = $setting?->viseepro_url ?: "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/edit?gid=172624186";
        $csvUrl = self::convertToCsvUrl($rawUrl, '172624186');
        $csvFile = storage_path('app/sheet_viseepro.csv');

        try {
            $response = Http::timeout(60)->get($csvUrl);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvFile, $response->body());
            } elseif (!file_exists($csvFile)) {
                throw new \Exception("Gagal mengunduh VISEEPRO dari spreadsheet dan cache lokal tidak tersedia.");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvFile)) {
                return [
                    'success' => false,
                    'label'   => 'VISEEPRO',
                    'count'   => 0,
                    'message' => 'Gagal mengambil data VISEEPRO: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('VISEEPRO sync using local cached file due to network: ' . $e->getMessage());
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
            Log::error('VISEEPRO import error: ' . $e->getMessage());
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
        $url = "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/gviz/tq?tqx=out:csv&sheet=" . urlencode('DATA ALL');
        $csvPath = storage_path('app/sheet_data-all.csv');

        try {
            $response = Http::timeout(60)->get($url);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvPath, $response->body());
            } elseif (!file_exists($csvPath)) {
                throw new \Exception("Gagal mengunduh Sheet DATA ALL dari spreadsheet.");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvPath)) {
                return [
                    'success' => false,
                    'label'   => 'C3MR Master Data (DATA ALL)',
                    'count'   => 0,
                    'message' => 'Gagal mengambil Sheet DATA ALL: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('DATA ALL sync using local cached file: ' . $e->getMessage());
        }

        try {
            $res = CustomerSyncService::syncFromDataAllCsv($csvPath);
            CustomerPhoneEnricher::enrichPhoneNumbers();

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
            Log::error('DATA ALL sync error: ' . $e->getMessage());
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
        $csvPath = storage_path('app/sheet_data-all.csv');

        if (!file_exists($csvPath)) {
            // Coba download jika belum ada
            $url = "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/gviz/tq?tqx=out:csv&sheet=" . urlencode('DATA ALL');
            try {
                $response = Http::timeout(60)->get($url);
                if ($response->successful() && strlen($response->body()) > 20) {
                    file_put_contents($csvPath, $response->body());
                }
            } catch (\Throwable $e) {
                // Ignore download error, will check file_exists below
            }
        }

        if (!file_exists($csvPath)) {
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
            Log::error('Hasil Caring sync error: ' . $e->getMessage());
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
        $url = "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/gviz/tq?tqx=out:csv&sheet=" . urlencode('PERFORMANSI DETAIL');
        $csvPath = storage_path('app/sheet_performansi-detail.csv');

        try {
            $response = Http::timeout(60)->get($url);
            if ($response->successful() && strlen($response->body()) > 20) {
                file_put_contents($csvPath, $response->body());
            } elseif (!file_exists($csvPath)) {
                throw new \Exception("Gagal mengunduh Sheet PERFORMANSI DETAIL dari spreadsheet.");
            }
        } catch (\Throwable $e) {
            if (!file_exists($csvPath)) {
                return [
                    'success' => false,
                    'label'   => 'C3MR Performansi Witel',
                    'count'   => 0,
                    'message' => 'Gagal mengambil Sheet PERFORMANSI DETAIL: ' . $e->getMessage(),
                    'error'   => $e->getMessage(),
                ];
            }
            Log::warning('PERFORMANSI DETAIL sync using local cached file: ' . $e->getMessage());
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
            Log::error('Performansi Witel sync error: ' . $e->getMessage());
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
        try {
            $res = ArAgentService::consolidateAgents();
            return [
                'success' => true,
                'label'   => 'Normalisasi AR Agent',
                'count'   => $res['final_agent_count'] ?? 0,
                'merged'  => $res['merged_count'] ?? 0,
                'message' => "{$res['final_agent_count']} agent unik terkonsolidasi",
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Consolidate AR error: ' . $e->getMessage());
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
        $setting = Setting::first();
        if (!$setting) {
            $setting = Setting::create([
                'report_prq_url'   => "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/edit?gid=1303511230",
                'viseepro_url'     => "https://docs.google.com/spreadsheets/d/" . self::$defaultSheetId . "/edit?gid=172624186",
                'last_sync_at'     => $syncTimestamp,
                'last_sync_status' => $overallStatus,
                'last_sync_result' => $results,
            ]);
        } else {
            $setting->update([
                'last_sync_at'     => $syncTimestamp,
                'last_sync_status' => $overallStatus,
                'last_sync_result' => $results,
            ]);
        }

        $duration = round(microtime(true) - $startTime, 2);

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