<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ArAgent;
use App\Models\Visit;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PritiSyncService
{
    private static string $defaultPritiUrl = 'https://docs.google.com/spreadsheets/d/1EYcSxJR4Br-mjbJpMNW8Uey5pYjuNnlmki9Fk-QplqE/edit?usp=sharing';

    public static function getActivePritiUrl(): string
    {
        $setting = Setting::first();
        if (!empty($setting?->priti_url)) {
            return $setting->priti_url;
        }
        return self::$defaultPritiUrl;
    }

    public static function parseInputDate($val): Carbon
    {
        if (empty($val)) {
            return now();
        }

        $valStr = trim((string)$val);

        if (is_numeric($valStr) && (float)$valStr > 40000 && (float)$valStr < 60000) {
            $days = (float)$valStr;
            $base = Carbon::create(1899, 12, 30, 0, 0, 0);
            $seconds = (int)round($days * 86400);
            return $base->addSeconds($seconds);
        }

        return C3mrCaringService::parseDate($valStr) ?: now();
    }

    public static function downloadAndExtractPriti(): string
    {
        $url = self::getActivePritiUrl();
        $sheetId = C3mrSyncService::extractSpreadsheetId($url);
        $xlsxPath = storage_path('app/priti_master.xlsx');
        $csvPath = storage_path('app/sheet_priti_collection.csv');

        Log::info("[PRITI Sync] Mengunduh PRITI DATA Workbook (Sheet ID: {$sheetId})...");

        $downloadOk = false;
        $exportUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=xlsx";
        try {
            $resp = Http::timeout(60)->get($exportUrl);
            if ($resp->successful() && strlen($resp->body()) > 2000) {
                file_put_contents($xlsxPath, $resp->body());
                $downloadOk = true;
                Log::info('[PRITI Sync] Berhasil mengunduh master PRITI XLSX (' . round(strlen($resp->body())/1024, 2) . ' KB)');
            }
        } catch (\Throwable $e) {
            Log::warning('[PRITI Sync] Unduh PRITI XLSX format=xlsx gagal: ' . $e->getMessage());
        }

        if (!$downloadOk) {
            $driveUrl = "https://drive.google.com/uc?id={$sheetId}&export=download";
            try {
                $resp = Http::timeout(60)->get($driveUrl);
                if ($resp->successful() && strlen($resp->body()) > 2000) {
                    file_put_contents($xlsxPath, $resp->body());
                    $downloadOk = true;
                }
            } catch (\Throwable $e) {
                Log::warning('[PRITI Sync] Unduh PRITI direct drive gagal: ' . $e->getMessage());
            }
        }

        if (!$downloadOk && !file_exists($xlsxPath)) {
            throw new \Exception("Gagal mengunduh file spreadsheet PRITI DATA dari Google. Pastikan URL dan izin sharing terbuka (Viewer).");
        }

        XlsxSheetExtractor::extractSheetToCsv($xlsxPath, 'Collection', $csvPath);
        return $csvPath;
    }

    public static function syncCollection(): array
    {
        $csvPath = storage_path('app/sheet_priti_collection.csv');

        if (!file_exists($csvPath)) {
            try {
                self::downloadAndExtractPriti();
            } catch (\Throwable $e) {
                Log::warning('[PRITI Sync] Download & extract warning: ' . $e->getMessage());
            }
        }

        if (!file_exists($csvPath)) {
            return [
                'success' => false,
                'label'   => 'PRITI DATA (Collection)',
                'count'   => 0,
                'message' => 'File CSV PRITI Collection belum tersedia.',
                'error'   => 'sheet_priti_collection.csv not found',
            ];
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return [
                'success' => false,
                'label'   => 'PRITI DATA (Collection)',
                'count'   => 0,
                'message' => 'Gagal membuka CSV PRITI Collection.',
                'error'   => 'Cannot open CSV',
            ];
        }

        $header = fgetcsv($handle);
        $totalRows = 0;
        $processedVisits = 0;
        $createdVisits = 0;
        $updatedVisits = 0;
        $skipped = 0;

        // In-memory cache AR
        $agentCache = [];
        foreach (ArAgent::all() as $ag) {
            $agentCache[strtoupper($ag->name)] = $ag;
        }

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $totalRows++;
                $collectIdRaw = trim((string)($row[1] ?? ''));
                $rawSnd = trim((string)($row[3] ?? ''));
                $snd = CustomerSyncService::cleanSnd($rawSnd);

                if (!$snd) {
                    $skipped++;
                    continue;
                }

                $tglInput = self::parseInputDate($row[2] ?? null);
                $layanan = !empty($row[4]) ? trim((string)$row[4]) : null;
                $rawAr = !empty($row[5]) ? trim((string)$row[5]) : null;
                $namaPelanggan = !empty($row[6]) ? trim((string)$row[6]) : null;
                $rawHp = !empty($row[7]) ? trim((string)$row[7]) : null;
                $cleanHp = DataNormalizerService::normalizePhone($rawHp);
                $tipeHunian = !empty($row[8]) ? trim((string)$row[8]) : null;
                $hasilVisit = !empty($row[9]) ? trim((string)$row[9]) : 'Belum Diisi';
                $kategoriVisit = !empty($row[10]) ? DataNormalizerService::normalizeVisitCategory($row[10]) : '-';
                $keterangan = !empty($row[11]) ? trim((string)$row[11]) : '-';
                $fotoUrl = !empty($row[12]) ? trim((string)$row[12]) : null;
                $chatId = !empty($row[13]) ? trim((string)$row[13]) : null;

                // 1. Dapatkan AR Agent
                $arAgent = null;
                if ($rawAr) {
                    $canonicalAr = DataNormalizerService::normalizeArName($rawAr);
                    if ($canonicalAr) {
                        $key = strtoupper($canonicalAr);
                        if (isset($agentCache[$key])) {
                            $arAgent = $agentCache[$key];
                            if (!empty($chatId) && empty($arAgent->chat_id_telegram)) {
                                $arAgent->chat_id_telegram = $chatId;
                                $arAgent->save();
                            }
                        } else {
                            $arAgent = ArAgent::whereRaw('LOWER(name) = ?', [strtolower($canonicalAr)])->first();
                            if (!$arAgent) {
                                $arAgent = ArAgent::create([
                                    'name'              => $canonicalAr,
                                    'chat_id_telegram'  => $chatId,
                                    'is_active'         => true,
                                ]);
                            } elseif (!empty($chatId) && empty($arAgent->chat_id_telegram)) {
                                $arAgent->chat_id_telegram = $chatId;
                                $arAgent->save();
                            }
                            $agentCache[$key] = $arAgent;
                        }
                    }
                }

                // 2. Customer
                $customer = Customer::where('nomor_internet', $snd)->first();
                if (!$customer) {
                    $customer = Customer::create([
                        'nomor_internet'        => $snd,
                        'nama_pelanggan'        => $namaPelanggan ?: 'Pelanggan ' . $snd,
                        'nama_layanan_internet' => $layanan ?: 'Internet',
                        'no_hp_terbaru'         => $cleanHp,
                        'tipe_hunian_terbaru'   => $tipeHunian,
                        'assigned_ar_agent_id'  => $arAgent?->id,
                    ]);
                } else {
                    $updates = [];
                    if (!empty($cleanHp) && empty($customer->no_hp_terbaru)) {
                        $updates['no_hp_terbaru'] = $cleanHp;
                    }
                    if (!empty($tipeHunian) && empty($customer->tipe_hunian_terbaru)) {
                        $updates['tipe_hunian_terbaru'] = $tipeHunian;
                    }
                    if (!empty($layanan) && empty($customer->nama_layanan_internet)) {
                        $updates['nama_layanan_internet'] = $layanan;
                    }
                    if (!empty($arAgent) && empty($customer->assigned_ar_agent_id)) {
                        $updates['assigned_ar_agent_id'] = $arAgent->id;
                    }
                    if (!empty($updates)) {
                        $customer->update($updates);
                    }
                }

                // 3. Visit
                $collectId = $collectIdRaw ?: ('PRITI-' . $snd . '-' . $tglInput->format('Ymd'));
                $isPtp = str_contains(strtolower($hasilVisit), 'janji') ||
                         str_contains(strtolower($kategoriVisit), 'janji') ||
                         str_contains(strtolower($kategoriVisit), 'jb') ||
                         str_contains(strtolower($hasilVisit), 'ptp');

                $visit = Visit::updateOrCreate(
                    [
                        'collect_id' => $collectId,
                    ],
                    [
                        'customer_id'          => $customer->id,
                        'ar_agent_id'          => $arAgent?->id,
                        'tanggal_input'        => $tglInput,
                        'hasil_visit'          => $hasilVisit,
                        'kategori_visit'       => $kategoriVisit,
                        'keterangan_visit'     => $keterangan,
                        'foto_url'             => $fotoUrl,
                        'no_hp_snapshot'       => $cleanHp ?: $rawHp,
                        'tipe_hunian_snapshot' => $tipeHunian,
                        'is_ptp'               => $isPtp,
                    ]
                );

                $customer->update([
                    'last_visit_at' => $tglInput,
                    'total_visits'  => $customer->visits()->count(),
                ]);

                $processedVisits++;
                if ($visit->wasRecentlyCreated) {
                    $createdVisits++;
                } else {
                    $updatedVisits++;
                }
            }

            DB::commit();
            fclose($handle);

            Log::info("[PRITI Sync] Selesai: {$processedVisits} kunjungan diproses dari {$totalRows} baris (Created: {$createdVisits}, Updated: {$updatedVisits}, Skipped: {$skipped})");

            return [
                'success'        => true,
                'label'          => 'PRITI DATA (Collection)',
                'source_rows'    => $totalRows,
                'count'          => $processedVisits,
                'created'        => $createdVisits,
                'updated'        => $updatedVisits,
                'skipped'        => $skipped,
                'message'        => "{$processedVisits} kunjungan & chat ID AR dari PRITI DATA berhasil disinkronkan",
                'error'          => null,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            Log::error('[PRITI Sync] Error: ' . $e->getMessage());
            return [
                'success'     => false,
                'label'       => 'PRITI DATA (Collection)',
                'source_rows' => $totalRows,
                'count'       => 0,
                'created'     => 0,
                'updated'     => 0,
                'skipped'     => 0,
                'message'     => 'Gagal memproses PRITI DATA: ' . $e->getMessage(),
                'error'       => $e->getMessage(),
            ];
        }
    }
}
