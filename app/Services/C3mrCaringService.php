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
     * Parse tanggal dari berbagai format spreadsheet (d/m/Y, Y-m-d, d-m-Y)
     */
    public static function parseDate(?string $rawDate): ?Carbon
    {
        if (empty($rawDate) || trim($rawDate) === '' || str_contains($rawDate, '1899') || str_contains($rawDate, '#')) {
            return null;
        }

        $rawDate = trim($rawDate);

        $formats = ['d/m/Y', 'd/m/Y H:i:s', 'Y-m-d', 'd-m-Y', 'd M Y'];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $rawDate)->startOfDay();
            } catch (\Exception $e) {
                // coba format berikutnya
            }
        }

        try {
            return Carbon::parse($rawDate)->startOfDay();
        } catch (\Exception $e) {
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
            while (($row = fgetcsv($handle)) !== false) {
                $total++;
                $snd = trim((string)($row[$sndIdx] ?? ''));

                if (empty($snd) || !preg_match('/^\d{8,20}$/', $snd)) {
                    continue;
                }

                $customer = Customer::where('nomor_internet', $snd)->first();

                // Cari atau buat AR Agent
                $agentNameRaw = !empty($row[$collAgentIdx]) ? trim((string)$row[$collAgentIdx]) : null;
                $arAgent = null;
                if ($agentNameRaw) {
                    $canonicalName = ArAgentService::getCanonicalName($agentNameRaw);
                    $arAgent = ArAgent::firstOrCreate(['name' => $canonicalName], ['is_active' => true]);
                }

                $statusCaring = !empty($row[$statusCaringIdx]) ? strtoupper(trim((string)$row[$statusCaringIdx])) : 'UNCONTACTED';
                $voc          = !empty($row[$vocIdx]) ? trim((string)$row[$vocIdx]) : null;
                $keterangan   = !empty($row[$ketIdx]) ? trim((string)$row[$ketIdx]) : null;
                $statusBayar  = !empty($row[$statusBayarIdx]) ? strtoupper(trim((string)$row[$statusBayarIdx])) : 'UNPAID';
                $petugas      = !empty($row[$petugasIdx]) ? trim((string)$row[$petugasIdx]) : ($agentNameRaw ?: 'OBC PRITI');
                $tglCaring    = self::parseDate($row[$tglCaringIdx] ?? null) ?: now();
                $noHp         = CustomerSyncService::cleanPhoneNumber($row[$noHpIdx] ?? null) ?: ($customer?->no_hp_terbaru);

                $isPtp = false;
                if ($voc && (str_contains(strtolower($voc), 'janji') || str_contains(strtolower($voc), 'ptp'))) {
                    $isPtp = true;
                }

                $jmlBayar = CustomerSyncService::cleanNumeric($row[$jmlBayarIdx] ?? null);

                $caringLog = CaringLog::updateOrCreate(
                    [
                        'nomor_internet' => $snd,
                        'tanggal_caring' => $tglCaring->format('Y-m-d'),
                    ],
                    [
                        'customer_id'     => $customer?->id,
                        'ar_agent_id'     => $arAgent?->id,
                        'nama_pelanggan'  => $customer?->nama_pelanggan ?: trim((string)($row[$namaIdx] ?? 'Pelanggan ' . $snd)),
                        'no_hp'           => $noHp,
                        'petugas_caring'  => $petugas,
                        'status_caring'   => $statusCaring ?: 'UNCONTACTED',
                        'voc'             => $voc ?: 'General Caring',
                        'keterangan'      => $keterangan,
                        'frekuensi'       => trim((string)($row[$frekIdx] ?? '1X')),
                        'is_ptp'          => $isPtp,
                        'status_bayar'    => str_contains($statusBayar, 'PAID') && !str_contains($statusBayar, 'UN') ? 'PAID' : 'UNPAID',
                        'jumlah_bayar'    => $jmlBayar,
                        'bill_category'   => trim((string)($row[$billCatIdx] ?? 'Eksisting')),
                        'umur_customer'   => trim((string)($row[$umurIdx] ?? '-')),
                    ]
                );

                if ($caringLog->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

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

                $witelName = strtoupper(trim((string)$row[1]));
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
