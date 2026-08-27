<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CaringLog;
use App\Models\WitelPerformance;
use App\Models\Visit;
use App\Models\ViseeproData;
use App\Models\ArAgent;
use App\Models\Setting;
use App\Services\C3mrSyncService;
use App\Services\PritiSyncService;
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
        $totalPtp       = Visit::where('is_ptp', true)->count();
        $totalViseepro  = ViseeproData::count();
        $totalArAgents  = ArAgent::where('is_active', true)->count();
        $totalPranpc    = Customer::where('is_pranpc', true)->count();
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
            'totalPtp',
            'totalViseepro',
            'totalArAgents',
            'totalPranpc',
            'validPhones',
            'setting',
            'lastSyncAt',
            'lastSyncFormatted',
            'lastSyncStatus',
            'lastSyncResult'
        ));
    }

    /**
     * Master Sync Satu Pintu via Server-Sent Events (SSE)
     * Mengintegrasikan PRITI DATA + C3MR secara otomatis.
     */
    public function syncAll(Request $request)
    {
        return response()->stream(function () {
            @ini_set('max_execution_time', 0);
            @set_time_limit(0);

            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $send = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            };

            try {
                $startTime     = microtime(true);
                $syncTimestamp = Carbon::now();

                $send('progress', [
                    'step'    => 'start',
                    'message' => 'Memulai sinkronisasi satu pintu PRITI DATA & C3MR...',
                    'pct'     => 3,
                ]);

                // ── TAHAP 1: Download & Extract PRITI DATA & C3MR Workbook ──────────
                $send('progress', [
                    'step'    => 'download',
                    'message' => 'Mengunduh data PRITI DATA & C3MR dari Google Spreadsheet...',
                    'pct'     => 8,
                ]);

                try {
                    PritiSyncService::downloadAndExtractPriti();
                } catch (\Throwable $e) {
                    $send('progress', [
                        'step'    => 'download_priti_warn',
                        'message' => 'Catatan PRITI: ' . $e->getMessage() . ' (Melanjutkan dengan cache jika ada)',
                        'pct'     => 15,
                    ]);
                }

                try {
                    C3mrSyncService::downloadAndExtractMasterWorkbook();
                    $send('progress', [
                        'step'    => 'download_done',
                        'message' => 'Workbook PRITI DATA & C3MR berhasil diunduh & diekstrak.',
                        'pct'     => 25,
                    ]);
                } catch (\Throwable $e) {
                    $hasCached = file_exists(storage_path('app/sheet_data-all.csv'));
                    $send('progress', [
                        'step'    => 'download_warn',
                        'message' => 'Gagal unduh master C3MR terbaru: ' . $e->getMessage()
                            . ($hasCached ? ' — Melanjutkan dengan data lokal yang tersedia.' : ''),
                        'pct'     => 25,
                    ]);

                    if (!$hasCached) {
                        $send('error', [
                            'success'      => false,
                            'status'       => 'error',
                            'status_label' => 'Sinkronisasi gagal',
                            'message'      => 'Tidak dapat mengunduh data dari Google Spreadsheet dan tidak ada data cache tersedia. '
                                . 'Periksa koneksi internet dan pastikan Spreadsheet dapat diakses publik (Viewer).',
                            'detail'       => $e->getMessage(),
                        ]);
                        return;
                    }
                }

                // ── TAHAP 2: DATA ALL (Master Pelanggan ~27.000 records) ──────────
                $send('progress', ['step' => 'data_all', 'message' => 'Menyinkronkan master data pelanggan (DATA ALL — ~27.000 records)...', 'pct' => 30]);
                $dataAll = C3mrSyncService::syncDataAll();
                $send('progress', ['step' => 'data_all_done', 'message' => 'DATA ALL: ' . $dataAll['message'], 'pct' => 60]);

                // ── TAHAP 3: PRITI DATA (Sheet Collection — Kunjungan & Chat ID) ───
                $send('progress', ['step' => 'priti', 'message' => 'Memproses data PRITI Collection (Kunjungan, Foto, Chat ID AR)...', 'pct' => 65]);
                $priti = C3mrSyncService::syncPriti();
                $send('progress', ['step' => 'priti_done', 'message' => 'PRITI DATA: ' . $priti['message'], 'pct' => 75]);

                // ── TAHAP 4: Report PRQ & VISEEPRO ────────────────────────────────
                $send('progress', ['step' => 'report_prq', 'message' => 'Menyinkronkan Report PRQ & VISEEPRO...', 'pct' => 78]);
                $reportPrq = C3mrSyncService::syncReportPrq();
                $viseepro  = C3mrSyncService::syncViseepro();
                $send('progress', ['step' => 'report_prq_done', 'message' => 'Report PRQ & VISEEPRO selesai diproses.', 'pct' => 84]);

                // ── TAHAP 5: Hasil Caring OBC ─────────────────────────────────────
                $send('progress', ['step' => 'caring', 'message' => 'Menyinkronkan log Hasil Caring OBC PRITI...', 'pct' => 86]);
                $caring = C3mrSyncService::syncCaring();
                $send('progress', ['step' => 'caring_done', 'message' => 'Caring: ' . $caring['message'], 'pct' => 91]);

                // ── TAHAP 6: Performansi Witel ────────────────────────────────────
                $send('progress', ['step' => 'performance', 'message' => 'Menyinkronkan Performansi Witel Regional...', 'pct' => 93]);
                $performance = C3mrSyncService::syncPerformance();
                $send('progress', ['step' => 'performance_done', 'message' => 'Performansi: ' . $performance['message'], 'pct' => 95]);

                // ── TAHAP 7: Normalisasi & Konsolidasi AR ─────────────────────────
                $send('progress', ['step' => 'ar_agents', 'message' => 'Normalisasi & konsolidasi personil AR (membersihkan non-AR)...', 'pct' => 96]);
                $arAgents = C3mrSyncService::consolidateAr();
                $send('progress', ['step' => 'ar_agents_done', 'message' => 'AR Agents: ' . $arAgents['message'], 'pct' => 98]);

                // ── Agregasi Hasil & Data Quality Diagnostic Check ────────────────
                $results = [
                    'data_all'    => $dataAll,
                    'priti'       => $priti,
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

                $dbTotalCust = Customer::count();
                $sourceRows  = $dataAll['source_rows'] ?? 0;
                $isConsistent = ($dbTotalCust > 0 && $sourceRows > 0 && ($dbTotalCust >= ($sourceRows * 0.8)));
                $difference   = abs($sourceRows - $dbTotalCust);

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
                    'created_customers'        => $dataAll['created'] ?? 0,
                    'updated_customers'        => $dataAll['updated'] ?? 0,
                    'duplicate_in_source'      => $dataAll['duplicates'] ?? 0,
                    'invalid_skipped'          => $dataAll['skipped'] ?? 0,
                    'pranpc_customers'         => Customer::where('is_pranpc', true)->count(),
                    'valid_phones_count'       => Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count(),
                    'total_visits'             => Visit::count(),
                    'total_ptp'                => Visit::where('is_ptp', true)->count(),
                    'total_caring'             => CaringLog::count(),
                    'total_ar_agents'          => ArAgent::count(),
                ];

                $duration      = round(microtime(true) - $startTime, 2);
                $formattedDate = C3mrSyncService::formatIndonesianDate($syncTimestamp);

                // Simpan riwayat sync ke Setting
                $activeC3mrUrl  = C3mrSyncService::getActiveSpreadsheetUrl();
                $activePritiUrl = PritiSyncService::getActivePritiUrl();
                $setting = Setting::first();
                $savePayload = array_merge($results, ['_data_quality' => $dataQuality]);

                if (!$setting) {
                    Setting::create([
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

                $statusLabel = $overallStatus === 'success'
                    ? 'Sinkronisasi berhasil'
                    : ($overallStatus === 'warning' ? 'Sinkronisasi selesai dengan catatan kualitas data' : 'Sinkronisasi gagal / Data Integrity Alert');

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

                $send('progress', ['step' => 'finish', 'message' => "Selesai dalam {$duration}s ({$dbTotalCust} master pelanggan di database)", 'pct' => 100]);
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
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
            'Pragma'            => 'no-cache',
        ]);
    }
}
