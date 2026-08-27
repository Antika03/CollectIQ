<?php

namespace App\Services;

use App\Models\CaringLog;
use App\Models\Customer;
use App\Models\ArAgent;
use App\Models\WitelPerformance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class C3mrCaringService
{
    /**
     * Parse tanggal dari berbagai format spreadsheet (d/m/Y, j/n/Y, Y-m-d, d-m-Y, Excel serial)
     */
    public static function parseDate($rawDate): ?Carbon
    {
        if (empty($rawDate) || trim((string)$rawDate) === '' || str_contains((string)$rawDate, '1899') || str_contains((string)$rawDate, '#')) {
            return null;
        }

        $rawStr = trim((string)$rawDate);

        // Jika Excel serial date
        if (is_numeric($rawStr) && (float)$rawStr > 40000 && (float)$rawStr < 60000) {
            $days = (float)$rawStr;
            $base = Carbon::create(1899, 12, 30, 0, 0, 0);
            return $base->addDays((int)$days);
        }

        $formats = [
            'd/m/Y', 'j/n/Y', 'd/n/Y', 'j/m/Y',
            'd/m/Y H:i:s', 'j/n/Y H:i:s',
            'Y-m-d', 'd-m-Y', 'j-n-Y',
            'd M Y', 'j M Y', 'Y/m/d'
        ];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $rawStr)->startOfDay();
            } catch (\Throwable $e) {
                // coba format berikutnya
            }
        }

        try {
            return Carbon::parse(str_replace('/', '-', $rawStr))->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Import caring log dari sheet DATA ALL
     */
    public static function importCaringFromDataAll(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            throw new \Exception("File CSV tidak ditemukan: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new \Exception("Header CSV kosong");
        }

        $colMap = [];
        foreach ($header as $idx => $colName) {
            $colMap[trim(strtoupper($colName))] = $idx;
        }

        $sndIdx        = $colMap['SND'] ?? null;
        $namaIdx       = $colMap['NAMA'] ?? null;
        $noHpIdx       = $colMap['NO HP'] ?? ($colMap['CP'] ?? null);
        $collAgentIdx  = $colMap['COLL AGENT'] ?? null;
        $petugasIdx    = $colMap['PETUGAS'] ?? null;
        $tglCaringIdx  = $colMap['TANGGAL'] ?? null;
        $statusCaringIdx = $colMap['STATUS CARING'] ?? null;
        $vocIdx        = $colMap['VOC'] ?? null;
        $ketIdx        = $colMap['KET'] ?? null;
        $frekIdx       = $colMap['FREKUENSI'] ?? null;
        $statusBayarIdx = $colMap['STATUS BAYAR'] ?? ($colMap['PAID_L11'] ?? null);
        $jmlBayarIdx   = $colMap['JUMLAH BAYAR'] ?? ($colMap['PAID_RP'] ?? null);
        $billCatIdx    = $colMap['BILL CATEGORY'] ?? null;
        $umurIdx       = $colMap['UMUR_CUSTOMER'] ?? null;

        $imported = 0;
        $updated  = 0;
        $total    = 0;

        DB::beginTransaction();
        try {
            // Pre-load AR Agent map
            $agentMap = [];
            foreach (ArAgent::all() as $ag) {
                $agentMap[strtoupper($ag->name)] = $ag;
            }

            while (($row = fgetcsv($handle)) !== false) {
                $total++;
                $rawSnd = trim((string)($row[$sndIdx] ?? ''));
                $snd = CustomerSyncService::cleanSnd($rawSnd);

                if (!$snd) {
                    continue;
                }

                $rawTgl = trim((string)($row[$tglCaringIdx] ?? ''));
                $tglCaring = self::parseDate($rawTgl);
                $rawVoc = !empty($row[$vocIdx]) ? trim((string)$row[$vocIdx]) : null;
                $statusCaringRaw = !empty($row[$statusCaringIdx]) ? trim((string)$row[$statusCaringIdx]) : null;
                $petugas = !empty($row[$petugasIdx]) ? trim((string)$row[$petugasIdx]) : null;
                $agentNameRaw = !empty($row[$collAgentIdx]) ? trim((string)$row[$collAgentIdx]) : null;

                // Hanya proses baris yang memang memiliki aktivitas caring / data
                if (!$tglCaring && empty($rawVoc) && empty($statusCaringRaw) && empty($petugas)) {
                    continue;
                }

                $tglCaring = $tglCaring ?: Carbon::create(2026, 8, 1)->startOfDay();
                $statusCaring = DataNormalizerService::normalizeCaringStatus($statusCaringRaw);
                $statusBayar  = DataNormalizerService::normalizePaymentStatus($row[$statusBayarIdx] ?? null);
                $voc          = DataNormalizerService::normalizeVoc($rawVoc);
                $billCat      = DataNormalizerService::normalizeBillCategory($row[$billCatIdx] ?? null);
                $petugasNama  = $petugas ?: ($agentNameRaw ?: 'OBC PRITI');
                $keterangan   = !empty($row[$ketIdx]) ? trim((string)$row[$ketIdx]) : null;

                // Cari AR Agent jika ada personil valid (bukan payment channel)
                $arAgent = null;
                $targetArName = $agentNameRaw ?: $petugas;
                if ($targetArName) {
                    $canonicalName = DataNormalizerService::normalizeArName($targetArName);
                    if ($canonicalName) {
                        $cacheKey = strtoupper($canonicalName);
                        if (isset($agentMap[$cacheKey])) {
                            $arAgent = $agentMap[$cacheKey];
                        } else {
                            $arAgent = ArAgent::create(['name' => $canonicalName, 'is_active' => true]);
                            $agentMap[$cacheKey] = $arAgent;
                        }
                    }
                }

                $isPtp = false;
                if ($rawVoc && (str_contains(strtolower($rawVoc), 'janji') || str_contains(strtolower($rawVoc), 'ptp'))) {
                    $isPtp = true;
                }

                $jmlBayar = CustomerSyncService::cleanNumeric($row[$jmlBayarIdx] ?? null);
                $noHp = DataNormalizerService::normalizePhone($row[$noHpIdx] ?? null);

                $caringLog = CaringLog::where('nomor_internet', $snd)
                    ->whereDate('tanggal_caring', $tglCaring->format('Y-m-d'))
                    ->first();

                $isNew = false;
                if (!$caringLog) {
                    $caringLog = new CaringLog([
                        'nomor_internet' => $snd,
                        'tanggal_caring' => $tglCaring->format('Y-m-d'),
                    ]);
                    $isNew = true;
                }

                $caringLog->fill([
                    'ar_agent_id'     => $arAgent?->id,
                    'nama_pelanggan'  => trim((string)($row[$namaIdx] ?? 'Pelanggan ' . $snd)),
                    'no_hp'           => $noHp,
                    'petugas_caring'  => $petugasNama,
                    'status_caring'   => $statusCaring,
                    'voc'             => $voc,
                    'keterangan'      => $keterangan,
                    'frekuensi'       => trim((string)($row[$frekIdx] ?? '1X')),
                    'is_ptp'          => $isPtp,
                    'status_bayar'    => $statusBayar,
                    'jumlah_bayar'    => $jmlBayar,
                    'bill_category'   => $billCat,
                    'umur_customer'   => trim((string)($row[$umurIdx] ?? '-')),
                ]);

                $caringLog->save();

                if ($isNew) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            // Link customer_id ke caring logs
            DB::statement("UPDATE caring_logs SET customer_id = (SELECT id FROM customers WHERE customers.nomor_internet = caring_logs.nomor_internet LIMIT 1) WHERE customer_id IS NULL");

            DB::commit();
            fclose($handle);

            return [
                'total_rows' => $total,
                'imported'   => $imported,
                'updated'    => $updated,
                'total_now'  => CaringLog::count(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }
    }

    /**
     * Import performansi Witel dari sheet PERFORMANSI DETAIL
     */
    public static function importWitelPerformance(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            throw new \Exception("File CSV tidak ditemukan: {$csvPath}");
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $imported = 0;

        $targetWitels = [
            'JAKARTA INNER',
            'JAKARTA CENTRUM',
            'JAKARTA OUTER',
            'BANTEN',
            'PRIANGAN BARAT',
            'BEKASI KARAWANG',
            'BANDUNG',
            'PRIANGAN TIMUR',
        ];

        DB::beginTransaction();
        try {
            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (empty($row[1])) continue;

                $witelName = DataNormalizerService::normalizeWitel(trim((string)$row[1]));
                if (in_array($witelName, $targetWitels)) {
                    $billing   = CustomerSyncService::cleanNumeric($row[2] ?? null);
                    $cashColl  = CustomerSyncService::cleanNumeric($row[3] ?? null);
                    $cycPct    = (float) str_replace(['%', ' '], '', $row[4] ?? '0');
                    $saldo     = CustomerSyncService::cleanNumeric($row[6] ?? null);
                    $gap       = CustomerSyncService::cleanNumeric($row[7] ?? null);
                    $rank      = (int) ($row[9] ?? null);

                    WitelPerformance::updateOrCreate(
                        [
                            'witel'    => $witelName,
                            'kategori' => 'CYC NONPOTS',
                            'periode'  => '2026-08',
                        ],
                        [
                            'segmen'       => 'ALL',
                            'billing'      => $billing,
                            'cash_coll'    => $cashColl,
                            'cyc_percent'  => $cycPct,
                            'cr_percent'   => $billing > 0 ? round(($cashColl / $billing) * 100, 2) : 0,
                            'c3mr_percent' => $cycPct,
                            'saldo'        => $saldo,
                            'gap'          => $gap,
                            'rank'         => $rank ?: null,
                        ]
                    );
                    $imported++;
                }
            }

            DB::commit();

            return [
                'imported_witel' => $imported,
                'total_witel'    => WitelPerformance::count(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
