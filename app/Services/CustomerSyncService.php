<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ArAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerSyncService
{
    /**
     * Membersihkan dan memvalidasi nomor HP via DataNormalizerService
     */
    public static function cleanPhoneNumber(?string $phone): ?string
    {
        return DataNormalizerService::normalizePhone($phone);
    }

    /**
     * Membersihkan nilai nominal rupiah / saldo piutang
     */
    public static function cleanNumeric(?string $val): float
    {
        if (!$val) {
            return 0.0;
        }
        $cleaned = str_replace([',', ' ', 'Rp', 'rp', 'RP'], '', trim($val));
        return (float) (preg_replace('/[^\d.-]/', '', $cleaned) ?: 0.0);
    }

    /**
     * Normalisasi format SND / Nomor Internet
     */
    public static function cleanSnd(?string $snd): ?string
    {
        if (empty($snd)) {
            return null;
        }
        $val = trim((string)$snd);
        if (is_numeric($val) && (stripos($val, 'e+') !== false || stripos($val, 'e') !== false)) {
            $val = sprintf('%.0f', (float)$val);
        }
        $cleaned = preg_replace('/[^\d]/', '', $val);
        if (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) {
            return $cleaned;
        }
        return null;
    }

    /**
     * Sinkronisasi data Customer dari file CSV sheet DATA ALL (Chunked Batch Upsert)
     * Mengimpor ~27.500 baris sumber menjadi master pelanggan utuh di database.
     */
    public static function syncFromDataAllCsv(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            throw new \Exception("File CSV master DATA ALL tidak ditemukan: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            throw new \Exception("Gagal membuka file CSV master DATA ALL: {$csvPath}");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new \Exception("Header CSV DATA ALL kosong");
        }

        // Petakan indeks kolom secara dinamis dan case-insensitive
        $colMap = [];
        foreach ($header as $idx => $colName) {
            $cleanName = trim(strtoupper(str_replace(["\xEF\xBB\xBF", "\r", "\n"], '', $colName)));
            $colMap[$cleanName] = $idx;
        }

        $sndIdx       = $colMap['SND'] ?? null;
        $ncliIdx      = $colMap['NCLI'] ?? null;
        $namaIdx      = $colMap['NAMA'] ?? null;
        $alamatIdx    = $colMap['ALAMAT'] ?? null;
        $stoIdx       = $colMap['STO'] ?? null;
        $datelIdx     = $colMap['DATEL'] ?? null;
        $produkIdx    = $colMap['PRODUK'] ?? null;
        $saldoIdx     = $colMap['SALDO'] ?? ($colMap['TAG_TOTAL'] ?? ($colMap['TAG TOTAL'] ?? null));
        $umurIdx      = $colMap['UMUR_CUSTOMER'] ?? ($colMap['UMUR CUSTOMER'] ?? null);
        $noHpIdx      = $colMap['NO HP'] ?? ($colMap['NO_HP'] ?? ($colMap['NOHP'] ?? null));
        $cpIdx        = $colMap['CP'] ?? null;
        $emailIdx     = $colMap['EMAIL'] ?? null;
        $billCatIdx   = $colMap['BILL CATEGORY'] ?? ($colMap['BILL_CATEGORY'] ?? null);
        $petugasIdx   = $colMap['PETUGAS'] ?? ($colMap['PETUGAS VISIT'] ?? ($colMap['COLL AGENT'] ?? null));

        if ($sndIdx === null) {
            fclose($handle);
            throw new \Exception("Kolom 'SND' tidak ditemukan pada CSV DATA ALL");
        }

        $totalSourceRows     = 0;
        $totalProcessedRows  = 0;
        $updatedCount        = 0;
        $createdCount        = 0;
        $duplicateInSource   = 0;
        $invalidSkipped      = 0;
        $validPhonesCount    = 0;
        $pranpcCount         = 0;

        $seenSndsInCsv = [];
        $chunkSize     = 1000;
        $chunkRows     = [];

        // Pre-load AR Agent map
        $arMap = [];
        foreach (ArAgent::all() as $agent) {
            $arMap[strtoupper($agent->name)] = $agent->id;
        }

        $colIndices = compact('sndIdx', 'ncliIdx', 'namaIdx', 'alamatIdx', 'stoIdx', 'datelIdx', 'produkIdx', 'saldoIdx', 'umurIdx', 'noHpIdx', 'cpIdx', 'emailIdx', 'billCatIdx', 'petugasIdx');

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $totalSourceRows++;

                $rawSnd = trim((string)($row[$sndIdx] ?? ''));
                $cleanSnd = self::cleanSnd($rawSnd);

                if (!$cleanSnd) {
                    $invalidSkipped++;
                    continue;
                }

                if (isset($seenSndsInCsv[$cleanSnd])) {
                    $duplicateInSource++;
                } else {
                    $seenSndsInCsv[$cleanSnd] = true;
                }

                $totalProcessedRows++;
                $chunkRows[] = $row;

                if (count($chunkRows) >= $chunkSize) {
                    self::processChunk($chunkRows, $updatedCount, $createdCount, $validPhonesCount, $pranpcCount, $arMap, $colIndices);
                    $chunkRows = [];
                }
            }

            if (!empty($chunkRows)) {
                self::processChunk($chunkRows, $updatedCount, $createdCount, $validPhonesCount, $pranpcCount, $arMap, $colIndices);
            }

            DB::commit();
            fclose($handle);

            $totalCustomersNow = Customer::count();

            Log::info("[CustomerSyncService] DATA ALL Selesai. Source: {$totalSourceRows}, Processed: {$totalProcessedRows}, Created: {$createdCount}, Updated: {$updatedCount}, Duplicates: {$duplicateInSource}, Skipped: {$invalidSkipped}, PRANPC: {$pranpcCount}, Total in DB: {$totalCustomersNow}");

            return [
                'total_source_rows'       => $totalSourceRows,
                'total_rows_processed'    => $totalProcessedRows,
                'created_customers'       => $createdCount,
                'updated_customers'       => $updatedCount,
                'duplicate_in_source'     => $duplicateInSource,
                'invalid_skipped'         => $invalidSkipped,
                'valid_phones_extracted'  => $validPhonesCount,
                'pranpc_customers'        => $pranpcCount,
                'total_customers_now'     => $totalCustomersNow,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            Log::error("[CustomerSyncService] Gagal memproses DATA ALL: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Proses batch chunk baris CSV ke database
     */
    private static function processChunk(
        array $rows,
        int &$updatedCount,
        int &$createdCount,
        int &$validPhonesCount,
        int &$pranpcCount,
        array &$arMap,
        array $colIndices
    ): void {
        extract($colIndices);

        $snds = [];
        $parsedRows = [];

        foreach ($rows as $row) {
            $rawSnd = trim((string)($row[$sndIdx] ?? ''));
            $snd = self::cleanSnd($rawSnd);
            if (!$snd) {
                continue;
            }

            // Nomor HP: Cari dari NO HP terlebih dahulu, jika kosong/invalid cari dari CP
            $rawHp = !empty($row[$noHpIdx]) ? trim((string)$row[$noHpIdx]) : '';
            $rawCp = !empty($row[$cpIdx]) ? trim((string)$row[$cpIdx]) : '';

            $validHp = DataNormalizerService::normalizePhone($rawHp);
            if (!$validHp && !empty($rawCp)) {
                $validHp = DataNormalizerService::normalizePhone($rawCp);
            }

            if ($validHp) {
                $validPhonesCount++;
            }

            $rawNcli = trim((string)($row[$ncliIdx] ?? ''));
            $ncli = self::cleanSnd($rawNcli);

            $namaPelanggan = !empty($row[$namaIdx]) ? trim((string)$row[$namaIdx]) : null;
            $alamat        = !empty($row[$alamatIdx]) ? trim((string)$row[$alamatIdx]) : null;
            $sto           = !empty($row[$stoIdx]) ? trim((string)$row[$stoIdx]) : null;
            $datel         = !empty($row[$datelIdx]) ? trim((string)$row[$datelIdx]) : null;
            $produk        = !empty($row[$produkIdx]) ? trim((string)$row[$produkIdx]) : null;
            $umurCustomer  = !empty($row[$umurIdx]) ? trim((string)$row[$umurIdx]) : null;
            $email         = !empty($row[$emailIdx]) ? trim((string)$row[$emailIdx]) : null;
            $saldo         = $saldoIdx !== null ? self::cleanNumeric($row[$saldoIdx] ?? null) : 0.0;

            // Kategori Tagihan & PRANPC
            $rawBillCat = !empty($row[$billCatIdx]) ? trim((string)$row[$billCatIdx]) : 'Eksisting';
            $billCat = DataNormalizerService::normalizeBillCategory($rawBillCat);
            $isPranpc = ($billCat === 'PRANPC');

            if ($isPranpc) {
                $pranpcCount++;
            }

            // AR Agent assignment
            $rawPetugas = !empty($row[$petugasIdx]) ? trim((string)$row[$petugasIdx]) : null;
            $assignedArId = null;
            if ($rawPetugas) {
                $canonicalAr = DataNormalizerService::normalizeArName($rawPetugas);
                if ($canonicalAr) {
                    $arKey = strtoupper($canonicalAr);
                    if (isset($arMap[$arKey])) {
                        $assignedArId = $arMap[$arKey];
                    } else {
                        $newAgent = ArAgent::create(['name' => $canonicalAr, 'is_active' => true]);
                        $assignedArId = $newAgent->id;
                        $arMap[$arKey] = $assignedArId;
                    }
                }
            }

            $snds[] = $snd;
            $parsedRows[$snd] = [
                'nomor_internet'        => $snd,
                'ncli'                  => $ncli,
                'nama_pelanggan'        => $namaPelanggan ?: 'Pelanggan ' . $snd,
                'alamat'                => $alamat,
                'sto'                   => $sto,
                'datel'                 => $datel,
                'nama_layanan_internet' => $produk ?: 'Internet',
                'no_hp_terbaru'         => $validHp,
                'email'                 => $email,
                'saldo_piutang'         => $saldo,
                'umur_customer'         => $umurCustomer,
                'is_pranpc'             => $isPranpc,
                'bill_category'         => $billCat,
                'assigned_ar_agent_id'  => $assignedArId,
            ];
        }

        if (empty($snds)) {
            return;
        }

        $existing = Customer::whereIn('nomor_internet', array_unique($snds))
            ->get()
            ->keyBy('nomor_internet');

        $toInsert = [];
        $now = now();

        foreach ($parsedRows as $snd => $data) {
            if ($existing->has($snd)) {
                $cust = $existing->get($snd);
                $updates = [];

                if (!empty($data['no_hp_terbaru']) && $cust->no_hp_terbaru !== $data['no_hp_terbaru']) {
                    $updates['no_hp_terbaru'] = $data['no_hp_terbaru'];
                }
                if (!empty($data['nama_pelanggan']) && ($cust->nama_pelanggan === '-' || strlen($data['nama_pelanggan']) > strlen((string)$cust->nama_pelanggan))) {
                    $updates['nama_pelanggan'] = $data['nama_pelanggan'];
                }
                if (!empty($data['alamat']) && $cust->alamat !== $data['alamat']) {
                    $updates['alamat'] = $data['alamat'];
                }
                if (!empty($data['sto']) && $cust->sto !== $data['sto']) {
                    $updates['sto'] = $data['sto'];
                }
                if (!empty($data['datel']) && $cust->datel !== $data['datel']) {
                    $updates['datel'] = $data['datel'];
                }
                if (!empty($data['ncli']) && $cust->ncli !== $data['ncli']) {
                    $updates['ncli'] = $data['ncli'];
                }
                if (!empty($data['umur_customer']) && $cust->umur_customer !== $data['umur_customer']) {
                    $updates['umur_customer'] = $data['umur_customer'];
                }
                if (!empty($data['email']) && $cust->email !== $data['email']) {
                    $updates['email'] = $data['email'];
                }
                if ($data['saldo_piutang'] > 0 && (float)$cust->saldo_piutang !== (float)$data['saldo_piutang']) {
                    $updates['saldo_piutang'] = $data['saldo_piutang'];
                }
                if (!empty($data['nama_layanan_internet']) && $cust->nama_layanan_internet !== $data['nama_layanan_internet']) {
                    $updates['nama_layanan_internet'] = $data['nama_layanan_internet'];
                }
                if ($cust->is_pranpc !== $data['is_pranpc']) {
                    $updates['is_pranpc'] = $data['is_pranpc'];
                }
                if ($cust->bill_category !== $data['bill_category']) {
                    $updates['bill_category'] = $data['bill_category'];
                }
                if (!empty($data['assigned_ar_agent_id']) && $cust->assigned_ar_agent_id !== $data['assigned_ar_agent_id']) {
                    $updates['assigned_ar_agent_id'] = $data['assigned_ar_agent_id'];
                }

                if (!empty($updates)) {
                    $cust->update($updates);
                    $updatedCount++;
                }
            } else {
                $riskLevel = 'low';
                $riskScore = 0;
                if ($data['saldo_piutang'] > 1000000) {
                    $riskLevel = 'critical';
                    $riskScore = 75;
                } elseif ($data['saldo_piutang'] > 500000) {
                    $riskLevel = 'high';
                    $riskScore = 50;
                } elseif ($data['saldo_piutang'] > 100000) {
                    $riskLevel = 'medium';
                    $riskScore = 30;
                }

                $toInsert[] = array_merge($data, [
                    'risk_score' => $riskScore,
                    'risk_level' => $riskLevel,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $createdCount++;
            }
        }

        if (!empty($toInsert)) {
            Customer::insert($toInsert);
        }
    }
}
