<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerSyncService
{
    /**
     * Membersihkan dan memvalidasi nomor HP
     */
    public static function cleanPhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Hapus karakter non-digit
        $cleaned = preg_replace('/[^\d]/', '', trim($phone));

        // Jika diawali 62, ubah ke 08...
        if (str_starts_with($cleaned, '62') && strlen($cleaned) >= 10) {
            $cleaned = '0' . substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '8') && strlen($cleaned) >= 9) {
            $cleaned = '0' . $cleaned;
        }

        // Nomor HP Indonesia valid biasanya 10 - 14 digit
        if (strlen($cleaned) >= 9 && strlen($cleaned) <= 15) {
            return $cleaned;
        }

        return null;
    }

    /**
     * Membersihkan nilai nominal rupiah / saldo
     */
    public static function cleanNumeric(?string $val): float
    {
        if (!$val) {
            return 0.0;
        }
        $cleaned = str_replace([',', ' ', 'Rp', 'rp', '.'], '', trim($val));
        // Jika ada desimal titik sebelumnya
        return (float) (preg_replace('/[^\d.-]/', '', $val) ?: 0.0);
    }

    /**
     * Normalisasi format SND / Nomor Internet
     */
    public static function cleanSnd(?string $snd): ?string
    {
        if (empty($snd)) return null;
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
     */
    public static function syncFromDataAllCsv(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            throw new \Exception("File CSV tidak ditemukan: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            throw new \Exception("Gagal membuka file CSV: {$csvPath}");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new \Exception("Header CSV kosong");
        }

        // Petakan indeks kolom
        $colMap = [];
        foreach ($header as $idx => $colName) {
            $colMap[trim(strtoupper($colName))] = $idx;
        }

        $sndIdx       = $colMap['SND'] ?? null;
        $ncliIdx      = $colMap['NCLI'] ?? null;
        $namaIdx      = $colMap['NAMA'] ?? null;
        $alamatIdx    = $colMap['ALAMAT'] ?? null;
        $stoIdx       = $colMap['STO'] ?? null;
        $datelIdx     = $colMap['DATEL'] ?? null;
        $produkIdx    = $colMap['PRODUK'] ?? null;
        $saldoIdx     = $colMap['SALDO'] ?? ($colMap['TAG_TOTAL'] ?? null);
        $umurIdx      = $colMap['UMUR_CUSTOMER'] ?? null;
        $noHpIdx      = $colMap['NO HP'] ?? null;
        $cpIdx        = $colMap['CP'] ?? null;
        $emailIdx     = $colMap['EMAIL'] ?? null;

        if ($sndIdx === null) {
            fclose($handle);
            throw new \Exception("Kolom 'SND' tidak ditemukan pada CSV DATA ALL");
        }

        $updatedCount = 0;
        $createdCount = 0;
        $totalRows    = 0;
        $chunkSize    = 1000;
        $chunkRows    = [];

        $colIndices = compact('sndIdx', 'ncliIdx', 'namaIdx', 'alamatIdx', 'stoIdx', 'datelIdx', 'produkIdx', 'saldoIdx', 'umurIdx', 'noHpIdx', 'cpIdx', 'emailIdx');

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $totalRows++;
                $chunkRows[] = $row;
                if (count($chunkRows) >= $chunkSize) {
                    self::processChunk($chunkRows, $updatedCount, $createdCount, $colIndices);
                    $chunkRows = [];
                }
            }
            if (!empty($chunkRows)) {
                self::processChunk($chunkRows, $updatedCount, $createdCount, $colIndices);
            }

            DB::commit();
            fclose($handle);

            return [
                'total_rows_processed' => $totalRows,
                'updated_customers'    => $updatedCount,
                'created_customers'    => $createdCount,
                'total_customers_now'  => Customer::count(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }
    }

    /**
     * Proses batch chunk baris CSV ke database
     */
    private static function processChunk(array $rows, int &$updatedCount, int &$createdCount, array $colIndices): void
    {
        extract($colIndices);

        $snds = [];
        $parsedRows = [];

        foreach ($rows as $row) {
            $rawSnd = trim((string)($row[$sndIdx] ?? ''));
            $snd = self::cleanSnd($rawSnd);
            if (!$snd) continue;

            $rawHp = !empty($row[$noHpIdx]) ? trim((string)$row[$noHpIdx]) : (!empty($row[$cpIdx]) ? trim((string)$row[$cpIdx]) : null);
            $validHp = self::cleanPhoneNumber($rawHp);

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
            ];
        }

        if (empty($snds)) return;

        $existing = Customer::whereIn('nomor_internet', array_unique($snds))
            ->get()
            ->keyBy('nomor_internet');

        $toInsert = [];
        $now = now();

        foreach ($parsedRows as $snd => $data) {
            if ($existing->has($snd)) {
                $cust = $existing->get($snd);
                $updates = [];

                if (!empty($data['no_hp_terbaru'])) $updates['no_hp_terbaru'] = $data['no_hp_terbaru'];
                if (!empty($data['nama_pelanggan']) && ($cust->nama_pelanggan === '-' || strlen($data['nama_pelanggan']) > strlen($cust->nama_pelanggan))) {
                    $updates['nama_pelanggan'] = $data['nama_pelanggan'];
                }
                if (!empty($data['alamat']))        $updates['alamat'] = $data['alamat'];
                if (!empty($data['sto']))           $updates['sto'] = $data['sto'];
                if (!empty($data['datel']))         $updates['datel'] = $data['datel'];
                if (!empty($data['ncli']))          $updates['ncli'] = $data['ncli'];
                if (!empty($data['umur_customer']))  $updates['umur_customer'] = $data['umur_customer'];
                if (!empty($data['email']))         $updates['email'] = $data['email'];
                if ($data['saldo_piutang'] > 0)     $updates['saldo_piutang'] = $data['saldo_piutang'];
                if (!empty($data['nama_layanan_internet'])) $updates['nama_layanan_internet'] = $data['nama_layanan_internet'];

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
                    'risk_score'        => $riskScore,
                    'risk_level'        => $riskLevel,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
                $createdCount++;
            }
        }

        if (!empty($toInsert)) {
            Customer::insert($toInsert);
        }
    }
}
