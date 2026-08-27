<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CaringLog;
use App\Models\WitelPerformance;
use App\Models\Visit;
use App\Models\ViseeproData;
use App\Models\Setting;
use App\Services\C3mrSyncService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class C3mrSyncController extends Controller
{
    public function index()
    {
        $totalCustomers = Customer::count();
        $totalCaring    = CaringLog::count();
        $totalWitel     = WitelPerformance::count();
        $totalVisits    = Visit::count();
        $totalViseepro  = ViseeproData::count();
        $validPhones    = Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count();

        $setting = Setting::first();
        $lastSyncAt = $setting?->last_sync_at;
        $lastSyncFormatted = $lastSyncAt
            ? $lastSyncAt->translatedFormat('d F Y, H:i') . ' WIB'
            : 'Belum pernah disinkronkan';
        $lastSyncStatus = $setting?->last_sync_status;
        $lastSyncResult = $setting?->last_sync_result;

        return view('c3mr.sync', compact(
            'totalCustomers',
            'totalCaring',
            'totalWitel',
            'totalVisits',
            'totalViseepro',
            'validPhones',
            'setting',
            'lastSyncAt',
            'lastSyncFormatted',
            'lastSyncStatus',
            'lastSyncResult'
        ));
    }

    /**
     * Master Sync via Server-Sent Events (SSE).
     *
     * Menggunakan streaming response agar tidak timeout di Railway.
     * Browser menerima progress real-time; setiap tahap mengirimkan event SSE.
     * Frontend membaca stream dan memperbarui UI secara live.
     */
    public function syncAll(Request $request)
    {
        return response()->stream(function () {
            @ini_set('max_execution_time', 0);
            @set_time_limit(0);

            // Pastikan output tidak di-buffer
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            /**
             * Kirim satu SSE event ke browser.
             */
            $send = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                if (function_exists('fastcgi_finish_request')) {
                    // Tidak untuk SSE
                }
                if (ob_get_level() > 0) ob_flush();
                flush();
            };

            try {
                $startTime     = microtime(true);
                $syncTimestamp = Carbon::now();

                $send('progress', [
                    'step'    => 'start',
                    'message' => 'Memulai sinkronisasi data C3MR...',
                    'pct'     => 3,
                ]);

                // ── TAHAP 1: Download & Extract XLSX ──────────────────────────────
                $send('progress', [
                    'step'    => 'download',
                    'message' => 'Mengunduh master workbook dari Google Spreadsheet (~60–90 detik, mohon tunggu)...',
                    'pct'     => 8,
                ]);

                $downloadOk = false;
                try {
                    C3mrSyncService::downloadAndExtractMasterWorkbook();
                    $downloadOk = true;
                    $send('progress', [
                        'step'    => 'download_done',
                        'message' => 'Master workbook berhasil diunduh & diekstrak dari Google Spreadsheet.',
                        'pct'     => 30,
                    ]);
                } catch (\Throwable $e) {
                    // Fallback: pakai CSV lama jika ada
                    $hasCached = file_exists(storage_path('app/sheet_data-all.csv'));
                    $send('progress', [
                        'step'    => 'download_warn',
                        'message' => 'Gagal unduh workbook terbaru: ' . $e->getMessage()
                            . ($hasCached ? ' — Melanjutkan dengan data cache yang tersedia.' : ''),
                        'pct'     => 20,
                    ]);

                    if (!$hasCached) {
                        $send('error', [
                            'success'      => false,
                            'status'       => 'error',
                            'status_label' => 'Sinkronisasi gagal',
                            'message'      => 'Tidak dapat mengunduh data dari Google Spreadsheet dan tidak ada data cache tersedia. '
                                . 'Periksa koneksi internet dan pastikan Spreadsheet dapat diakses publik.',
                            'detail'       => $e->getMessage(),
                        ]);
                        return;
                    }
                }

                // ── TAHAP 2: DATA ALL (Master Pelanggan ~27.000 records) ──────────
                $send('progress', ['step' => 'data_all', 'message' => 'Menyinkronkan master data pelanggan (DATA ALL — ~27.000 records)...', 'pct' => 35]);
                $dataAll = C3mrSyncService::syncDataAll();
                $send('progress', ['step' => 'data_all_done', 'message' => 'DATA ALL: ' . $dataAll['message'], 'pct' => 68]);

                // ── TAHAP 3: Report PRQ ───────────────────────────────────────────
                $send('progress', ['step' => 'report_prq', 'message' => 'Menyinkronkan Report PRQ (Visit lapangan & AR Agent)...', 'pct' => 70]);
                $reportPrq = C3mrSyncService::syncReportPrq();
                $send('progress', ['step' => 'report_prq_done', 'message' => 'Report PRQ: ' . $reportPrq['message'], 'pct' => 78]);

                // ── TAHAP 4: VISEEPRO ─────────────────────────────────────────────
                $send('progress', ['step' => 'viseepro', 'message' => 'Menyinkronkan data VISEEPRO (Aktivitas AR)...', 'pct' => 80]);
                $viseepro = C3mrSyncService::syncViseepro();
                $send('progress', ['step' => 'viseepro_done', 'message' => 'VISEEPRO: ' . $viseepro['message'], 'pct' => 85]);

                // ── TAHAP 5: Hasil Caring ─────────────────────────────────────────
                $send('progress', ['step' => 'caring', 'message' => 'Menyinkronkan log Hasil Caring OBC PRITI...', 'pct' => 87]);
                $caring = C3mrSyncService::syncCaring();
                $send('progress', ['step' => 'caring_done', 'message' => 'Caring: ' . $caring['message'], 'pct' => 93]);

                // ── TAHAP 6: Performansi Witel ────────────────────────────────────
                $send('progress', ['step' => 'performance', 'message' => 'Menyinkronkan Performansi Witel (Regional)...', 'pct' => 94]);
                $performance = C3mrSyncService::syncPerformance();
                $send('progress', ['step' => 'performance_done', 'message' => 'Performansi: ' . $performance['message'], 'pct' => 96]);

                // ── TAHAP 7: AR Agents ────────────────────────────────────────────
                $send('progress', ['step' => 'ar_agents', 'message' => 'Normalisasi & konsolidasi AR Agents...', 'pct' => 97]);
                $arAgents = C3mrSyncService::consolidateAr();
                $send('progress', ['step' => 'ar_agents_done', 'message' => 'AR Agents: ' . $arAgents['message'], 'pct' => 98]);

                // ── Agregasi hasil & Data Quality ─────────────────────────────────
                $results = [
                    'data_all'    => $dataAll,
                    'report_prq'  => $reportPrq,
                    'viseepro'    => $viseepro,
                    'caring'      => $caring,
                    'performance' => $performance,
                    'ar_agents'   => $arAgents,
                ];

                $successCount   = collect($results)->where('success', true)->count();
                $failCount      = collect($results)->where('success', false)->count();
                $totalProcessed = collect($results)->sum('count');

                $overallStatus = 'success';
                if ($failCount > 0 && $successCount > 0) $overallStatus = 'warning';
                elseif ($failCount > 0 && $successCount === 0) $overallStatus = 'error';

                $dbTotalCust    = Customer::count();
                $sourceRows     = $dataAll['source_rows'] ?? 0;
                $isConsistent   = ($dbTotalCust > 0 && $sourceRows > 0 && ($dbTotalCust >= ($sourceRows * 0.8)));
                $consistencyMsg = $isConsistent
                    ? "Data master pelanggan konsisten ({$dbTotalCust} pelanggan di database dari {$sourceRows} baris sheet)"
                    : "Perhatian: Terdapat perbedaan data. Sheet DATA ALL: {$sourceRows} baris, Database: {$dbTotalCust} pelanggan.";

                if (!$isConsistent && $sourceRows > 1000 && $dbTotalCust < 1000) {
                    $overallStatus = 'warning';
                }

                $dataQuality = [
                    'source_sheet_rows'        => $sourceRows,
                    'database_total_customers' => $dbTotalCust,
                    'is_consistent'            => $isConsistent,
                    'consistency_message'      => $consistencyMsg,
                    'created_customers'        => $dataAll['created'] ?? 0,
                    'updated_customers'        => $dataAll['updated'] ?? 0,
                    'duplicate_in_source'      => $dataAll['duplicates'] ?? 0,
                    'invalid_skipped'          => $dataAll['skipped'] ?? 0,
                    'valid_phones_count'       => Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count(),
                ];

                $duration      = round(microtime(true) - $startTime, 2);
                $formattedDate = C3mrSyncService::formatIndonesianDate($syncTimestamp);

                // Simpan riwayat sync ke Setting
                $activeUrl = C3mrSyncService::getActiveSpreadsheetUrl();
                $setting   = Setting::first();
                $savePayload = array_merge($results, ['_data_quality' => $dataQuality]);

                if (!$setting) {
                    Setting::create([
                        'c3mr_url'         => $activeUrl,
                        'report_prq_url'   => $activeUrl,
                        'viseepro_url'     => $activeUrl,
                        'last_sync_at'     => $syncTimestamp,
                        'last_sync_status' => $overallStatus,
                        'last_sync_result' => $savePayload,
                    ]);
                } else {
                    $setting->update([
                        'c3mr_url'         => $setting->c3mr_url ?: $activeUrl,
                        'last_sync_at'     => $syncTimestamp,
                        'last_sync_status' => $overallStatus,
                        'last_sync_result' => $savePayload,
                    ]);
                }

                $statusLabel = $overallStatus === 'success'
                    ? 'Sinkronisasi berhasil'
                    : ($overallStatus === 'warning' ? 'Sinkronisasi selesai dengan catatan kualitas data' : 'Sinkronisasi gagal');

                $finalPayload = [
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

                $send('progress', ['step' => 'finish', 'message' => "Selesai dalam {$duration}s ({$dbTotalCust} pelanggan di database)", 'pct' => 100]);
                $send('complete', $finalPayload);

            } catch (\Throwable $e) {
                $send('error', [
                    'success'      => false,
                    'status'       => 'error',
                    'status_label' => 'Sinkronisasi gagal',
                    'message'      => $e->getMessage(),
                    'file'         => basename($e->getFile()) . ':' . $e->getLine(),
                ]);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=UTF-8',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',   // Nonaktifkan buffering nginx
            'Connection'        => 'keep-alive',
            'Pragma'            => 'no-cache',
        ]);
    }

    public function syncDataAll(Request $request)
    {
        try {
            $res = C3mrSyncService::syncDataAll();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Sinkronisasi DATA ALL berhasil! Diproses: {$res['count']} baris.");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', 'Gagal sync DATA ALL: ' . $e->getMessage());
        }
    }

    public function syncCaring(Request $request)
    {
        try {
            $res = C3mrSyncService::syncCaring();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Sinkronisasi HASIL CARING berhasil! {$res['message']}");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', 'Gagal sync Hasil Caring: ' . $e->getMessage());
        }
    }

    public function syncPerformance(Request $request)
    {
        try {
            $res = C3mrSyncService::syncPerformance();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Sinkronisasi PERFORMANSI DETAIL berhasil! {$res['message']}");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', 'Gagal sync Performansi Witel: ' . $e->getMessage());
        }
    }

    public function consolidateAr(Request $request)
    {
        try {
            $res = C3mrSyncService::consolidateAr();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Konsolidasi AR Agent berhasil! {$res['message']}");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', 'Gagal konsolidasi AR: ' . $e->getMessage());
        }
    }
}
