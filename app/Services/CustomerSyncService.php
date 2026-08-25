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
     * Sinkronisasi data Customer dari file CSV sheet DATA ALL
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

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $totalRows++;
                $snd = trim((string) ($row[$sndIdx] ?? ''));

                if (empty($snd) || !preg_match('/^\d{8,20}$/', $snd)) {
                    continue;
                }

                // Ambil nomor HP terbaik (prioritaskan NO HP, lalu CP)
                $rawHp = !empty($row[$noHpIdx]) ? trim((string)$row[$noHpIdx]) : (!empty($row[$cpIdx]) ? trim((string)$row[$cpIdx]) : null);
                $validHp = self::cleanPhoneNumber($rawHp);

                $namaPelanggan = !empty($row[$namaIdx]) ? trim((string)$row[$namaIdx]) : null;
                $alamat        = !empty($row[$alamatIdx]) ? trim((string)$row[$alamatIdx]) : null;
                $sto           = !empty($row[$stoIdx]) ? trim((string)$row[$stoIdx]) : null;
                $datel         = !empty($row[$datelIdx]) ? trim((string)$row[$datelIdx]) : null;
                $produk        = !empty($row[$produkIdx]) ? trim((string)$row[$produkIdx]) : null;
                $ncli          = !empty($row[$ncliIdx]) ? trim((string)$row[$ncliIdx]) : null;
                $umurCustomer  = !empty($row[$umurIdx]) ? trim((string)$row[$umurIdx]) : null;
                $email         = !empty($row[$emailIdx]) ? trim((string)$row[$emailIdx]) : null;
                $saldo         = $saldoIdx !== null ? self::cleanNumeric($row[$saldoIdx] ?? null) : 0.0;

                $existing = Customer::where('nomor_internet', $snd)->first();

                if ($existing) {
                    $updateData = [];

                    if (!empty($validHp)) {
                        $updateData['no_hp_terbaru'] = $validHp;
                    } elseif (empty($existing->no_hp_terbaru) || !preg_match('/^\d{9,15}$/', $existing->no_hp_terbaru)) {
                        // Jika HP di DB lama berupa teks tidak valid dan ada nomor di CSV
                        if (!empty($validHp)) {
                            $updateData['no_hp_terbaru'] = $validHp;
                        }
                    }

                    if (!empty($namaPelanggan) && ($existing->nama_pelanggan === '-' || strlen($namaPelanggan) > strlen($existing->nama_pelanggan))) {
                        $updateData['nama_pelanggan'] = $namaPelanggan;
                    }

                    if (!empty($alamat))        $updateData['alamat'] = $alamat;
                    if (!empty($sto))           $updateData['sto'] = $sto;
                    if (!empty($datel))         $updateData['datel'] = $datel;
                    if (!empty($ncli))          $updateData['ncli'] = $ncli;
                    if (!empty($umurCustomer))  $updateData['umur_customer'] = $umurCustomer;
                    if (!empty($email))         $updateData['email'] = $email;
                    if ($saldo > 0)             $updateData['saldo_piutang'] = $saldo;
                    if (!empty($produk))        $updateData['nama_layanan_internet'] = $produk;

                    if (!empty($updateData)) {
                        $existing->update($updateData);
                        $updatedCount++;
                    }
                } else {
                    Customer::create([
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
                        'risk_score'            => 0,
                        'risk_level'            => 'low',
                    ]);
                    $createdCount++;
                }
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
}
