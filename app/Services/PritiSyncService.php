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
    private static string $defaultPritiUrl = 'https://docs.google.com/spreadsheets/d/1EYcSxJR4Br-mjbJpMNW8Uey5pYjuNnlmki9Fk-QplqE/edit?gid=386805268#gid=386805268';
    private static string $defaultGid = '386805268';

    public static function getActivePritiUrl(): string
    {
        $setting = Setting::first();
        if (!empty($setting?->priti_url)) {
            return $setting->priti_url;
        }
        return self::$defaultPritiUrl;
    }

    public static function extractGid(?string $url): string
    {
        if (empty($url)) {
            return self::$defaultGid;
        }
        if (preg_match('/[#&?]gid=([0-9]+)/', $url, $matches)) {
            return $matches[1];
        }
        return self::$defaultGid;
    }

    public static function parseInputDate($val): Carbon
    {
        if (empty($val) || trim((string)$val) === '' || trim((string)$val) === '-') {
            return Carbon::create(2024, 10, 1)->startOfDay();
        }

        $valStr = trim((string)$val);

        // Jika Excel serial date
        if (is_numeric($valStr) && (float)$valStr > 40000 && (float)$valStr < 60000) {
            $days = (float)$valStr;
            $base = Carbon::create(1899, 12, 30, 0, 0, 0);
            return $base->addDays((int)$days)->startOfDay();
        }

        // Coba parse format d/m/Y, d/m/Y H:i:s, Y-m-d
        $formats = [
            'd/m/Y', 'j/n/Y', 'd/n/Y', 'j/m/Y',
            'd/m/Y H:i:s', 'd/m/Y H:i', 'j/n/Y H:i:s',
            'Y-m-d', 'Y-m-d H:i:s', 'd-m-Y',
            'd M Y', 'j M Y', 'Y/m/d'
        ];

        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $valStr)->startOfDay();
            } catch (\Throwable $e) {
                // coba format berikutnya
            }
        }

        try {
            return Carbon::parse(str_replace('/', '-', $valStr))->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::create(2024, 10, 1)->startOfDay();
        }
    }

    public static function downloadAndExtractPriti(): string
    {
        $url = self::getActivePritiUrl();
        $sheetId = C3mrSyncService::extractSpreadsheetId($url);
        $gid = self::extractGid($url);
        $csvPath = storage_path('app/sheet_priti_collection.csv');

        Log::info("[PRITI Sync] Mengunduh PRITI DATA CSV langsung via GID: {$gid} (Sheet ID: {$sheetId})...");

        // 1. Coba download langsung format CSV via GID (sangat cepat ~1-2 detik)
        $directCsvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        try {
            $resp = Http::timeout(30)->get($directCsvUrl);
            if ($resp->successful() && strlen($resp->body()) > 500) {
                file_put_contents($csvPath, $resp->body());
                Log::info('[PRITI Sync] Berhasil mengunduh PRITI Collection CSV langsung (' . round(strlen($resp->body())/1024, 2) . ' KB)');
                return $csvPath;
            }
        } catch (\Throwable $e) {
            Log::warning('[PRITI Sync] Direct GID CSV download gagal: ' . $e->getMessage());
        }

        // 2. Fallback jika format=csv gagal: unduh XLSX dan ekstrak
        $xlsxPath = storage_path('app/priti_master.xlsx');
        $exportUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=xlsx";
        try {
            $resp = Http::timeout(60)->get($exportUrl);
            if ($resp->successful() && strlen($resp->body()) > 2000) {
                file_put_contents($xlsxPath, $resp->body());
                XlsxSheetExtractor::extractSheetToCsv($xlsxPath, 'Collection', $csvPath);
                return $csvPath;
            }
        } catch (\Throwable $e) {
            Log::warning('[PRITI Sync] Fallback XLSX gagal: ' . $e->getMessage());
        }

        if (!file_exists($csvPath)) {
            throw new \Exception("Gagal mengunduh spreadsheet PRITI DATA dari Google. Pastikan izin sharing link terbuka (Viewer).");
        }

        return $csvPath;
    }

    public static function syncCollection(): array
    {
        $startTime = microtime(true);
        $csvPath = storage_path('app/sheet_priti_collection.csv');

        try {
            self::downloadAndExtractPriti();
        } catch (\Throwable $e) {
            Log::warning('[PRITI Sync] Download warning: ' . $e->getMessage());
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

        // In-memory cache AR & Customers untuk performa tinggi
        $agentCache = [];
        foreach (ArAgent::all() as $ag) {
            $agentCache[strtoupper($ag->name)] = $ag;
        }

        $allRows = [];
        $uniqueSnds = [];

        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;
            $collectIdRaw = trim((string)($row[1] ?? ''));
            $rawSnd = trim((string)($row[3] ?? ''));
            $snd = CustomerSyncService::cleanSnd($rawSnd);

            if (!$snd) {
                $skipped++;
                continue;
            }

            $uniqueSnds[$snd] = true;
            $allRows[] = [
                'collectIdRaw'  => $collectIdRaw,
                'snd'           => $snd,
                'rawDate'       => $row[2] ?? null,
                'layanan'       => !empty($row[4]) ? trim((string)$row[4]) : null,
                'rawAr'         => !empty($row[5]) ? trim((string)$row[5]) : null,
                'namaPelanggan' => !empty($row[6]) ? trim((string)$row[6]) : null,
                'rawHp'         => !empty($row[7]) ? trim((string)$row[7]) : null,
                'tipeHunian'    => !empty($row[8]) ? trim((string)$row[8]) : null,
                'hasilVisit'    => !empty($row[9]) ? trim((string)$row[9]) : 'Belum Diisi',
                'kategoriVisit' => !empty($row[10]) ? DataNormalizerService::normalizeVisitCategory($row[10]) : '-',
                'keterangan'    => !empty($row[11]) ? trim((string)$row[11]) : '-',
                'fotoUrl'       => !empty($row[12]) ? trim((string)$row[12]) : null,
                'chatId'        => !empty($row[13]) ? trim((string)$row[13]) : null,
            ];
        }
        fclose($handle);

        // Preload existing customers by SND in single query
        $existingCustomers = Customer::whereIn('nomor_internet', array_keys($uniqueSnds))
            ->get()
            ->keyBy('nomor_internet');

        // Preload existing visits by collect_id in single query
        $possibleCollectIds = [];
        foreach ($allRows as $item) {
            $tgl = self::parseInputDate($item['rawDate']);
            $cid = $item['collectIdRaw'] ?: ('PRITI-' . $item['snd'] . '-' . $tgl->format('Ymd'));
            $possibleCollectIds[] = $cid;
        }

        $existingVisits = Visit::whereIn('collect_id', $possibleCollectIds)
            ->get()
            ->keyBy('collect_id');

        DB::beginTransaction();
        try {
            $now = now();
            $visitsToInsert = [];
            $customerUpdates = [];

            foreach ($allRows as $item) {
                $snd = $item['snd'];
                $tglInput = self::parseInputDate($item['rawDate']);
                $cleanHp = DataNormalizerService::normalizePhone($item['rawHp']);
                $rawAr = $item['rawAr'];
                $chatId = $item['chatId'];

                // 1. AR Agent lookup / creation
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
                            $arAgent = ArAgent::firstOrCreate(
                                ['name' => $canonicalAr],
                                ['chat_id_telegram' => $chatId, 'is_active' => true]
                            );
                            if (!empty($chatId) && empty($arAgent->chat_id_telegram)) {
                                $arAgent->chat_id_telegram = $chatId;
                                $arAgent->save();
                            }
                            $agentCache[$key] = $arAgent;
                        }
                    }
                }

                // 2. Customer lookup / creation
                $customer = $existingCustomers->get($snd);
                if (!$customer) {
                    $customer = Customer::create([
                        'nomor_internet'        => $snd,
                        'nama_pelanggan'        => $item['namaPelanggan'] ?: 'Pelanggan ' . $snd,
                        'nama_layanan_internet' => $item['layanan'] ?: 'Internet',
                        'no_hp_terbaru'         => $cleanHp,
                        'tipe_hunian_terbaru'   => $item['tipeHunian'],
                        'assigned_ar_agent_id'  => $arAgent?->id,
                        'last_visit_at'         => $tglInput->toDateString(),
                        'total_visits'          => 1,
                    ]);
                    $existingCustomers->put($snd, $customer);
                } else {
                    $custUpdates = [];
                    if (!empty($cleanHp) && empty($customer->no_hp_terbaru)) {
                        $custUpdates['no_hp_terbaru'] = $cleanHp;
                    }
                    if (!empty($item['tipeHunian']) && empty($customer->tipe_hunian_terbaru)) {
                        $custUpdates['tipe_hunian_terbaru'] = $item['tipeHunian'];
                    }
                    if (!empty($arAgent) && empty($customer->assigned_ar_agent_id)) {
                        $custUpdates['assigned_ar_agent_id'] = $arAgent->id;
                    }
                    if (empty($customer->last_visit_at) || $tglInput->gt($customer->last_visit_at)) {
                        $custUpdates['last_visit_at'] = $tglInput->toDateString();
                    }
                    if (!empty($custUpdates)) {
                        $customer->update($custUpdates);
                    }
                }

                // 3. Visit upsert
                $collectId = $item['collectIdRaw'] ?: ('PRITI-' . $snd . '-' . $tglInput->format('Ymd'));
                $hasilVisit = $item['hasilVisit'];
                $kategoriVisit = $item['kategoriVisit'];
                $isPtp = str_contains(strtolower($hasilVisit), 'janji') ||
                         str_contains(strtolower($kategoriVisit), 'janji') ||
                         str_contains(strtolower($kategoriVisit), 'jb') ||
                         str_contains(strtolower($hasilVisit), 'ptp');

                $existingVisit = $existingVisits->get($collectId);

                if ($existingVisit) {
                    $existingVisit->update([
                        'customer_id'          => $customer->id,
                        'ar_agent_id'          => $arAgent?->id,
                        'tanggal_input'        => $tglInput->toDateString(),
                        'hasil_visit'          => $hasilVisit,
                        'kategori_visit'       => $kategoriVisit,
                        'keterangan_visit'     => $item['keterangan'],
                        'foto_url'             => $item['fotoUrl'],
                        'no_hp_snapshot'       => $cleanHp ?: $item['rawHp'],
                        'tipe_hunian_snapshot' => $item['tipeHunian'],
                        'is_ptp'               => $isPtp,
                    ]);
                    $updatedVisits++;
                } else {
                    $newVisit = Visit::create([
                        'collect_id'           => $collectId,
                        'customer_id'          => $customer->id,
                        'ar_agent_id'          => $arAgent?->id,
                        'tanggal_input'        => $tglInput->toDateString(),
                        'hasil_visit'          => $hasilVisit,
                        'kategori_visit'       => $kategoriVisit,
                        'keterangan_visit'     => $item['keterangan'],
                        'foto_url'             => $item['fotoUrl'],
                        'no_hp_snapshot'       => $cleanHp ?: $item['rawHp'],
                        'tipe_hunian_snapshot' => $item['tipeHunian'],
                        'is_ptp'               => $isPtp,
                    ]);
                    $existingVisits->put($collectId, $newVisit);
                    $createdVisits++;
                }

                $processedVisits++;
            }

            DB::commit();

            // Clear KPI caches so UI reflects new dates immediately
            \Illuminate\Support\Facades\Cache::forget('visit_kpis');
            \Illuminate\Support\Facades\Cache::forget('visit_chart_trend');
            \Illuminate\Support\Facades\Cache::forget('dashboard_kpis');

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("[PRITI Sync] Selesai ({$duration}s): {$processedVisits} kunjungan dari {$totalRows} baris (Created: {$createdVisits}, Updated: {$updatedVisits})");

            return [
                'success'        => true,
                'label'          => 'PRITI DATA (Collection)',
                'source_rows'    => $totalRows,
                'count'          => $processedVisits,
                'created'        => $createdVisits,
                'updated'        => $updatedVisits,
                'skipped'        => $skipped,
                'duration'       => $duration,
                'message'        => "{$processedVisits} kunjungan & chat ID AR berhasil disinkronkan ({$createdVisits} baru, {$updatedVisits} diperbarui) dalam {$duration} detik",
                'error'          => null,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
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
