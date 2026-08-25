<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\ArAgent;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ReportPrqImport implements ToCollection
{
    public int $processedCount = 0;
    public int $createdVisits  = 0;
    public int $updatedVisits  = 0;

    /**
     * Normalisasi Nama AR
     */
    private function normalizeAgent($name)
    {
        $name = strtoupper(trim((string) $name));

        if (
            str_contains($name, 'SAYUS') ||
            str_contains($name, 'SUPRIYANTO')
        ) {
            return 'Sayus';
        }

        if (
            str_contains($name, 'SANTI') ||
            str_contains($name, 'SURAHMAN')
        ) {
            return 'Santi';
        }

        if (
            str_contains($name, 'WAHYU') ||
            str_contains($name, 'MULYADI')
        ) {
            return 'Wahyu';
        }

        if (str_contains($name, 'YAYAT')) {
            return 'Yayat';
        }

        if (
            str_contains($name, 'FAJAR') ||
            str_contains($name, 'RAMDHANI') ||
            str_contains($name, 'ISHAK')
        ) {
            return 'Fajar';
        }

        if (str_contains($name, 'RAFLI')) {
            return 'Rafli';
        }

        if (str_contains($name, 'BAMBANG')) {
            return 'Bambang';
        }

        if (str_contains($name, 'TATANG')) {
            return 'Tatang';
        }

        if (
            str_contains($name, 'IDA') ||
            str_contains($name, 'HERLINA')
        ) {
            return 'Ida';
        }

        if (
            str_contains($name, 'YANA') ||
            str_contains($name, 'SURYANA')
        ) {
            return 'Yana';
        }

        if (
            str_contains($name, 'FINA') ||
            str_contains($name, 'VINA')
        ) {
            return 'Fina';
        }

        if (
            str_contains($name, 'AHMAD') ||
            str_contains($name, 'ALI')
        ) {
            return 'Ahmad';
        }

        if (str_contains($name, 'WISNU')) {
            return 'Wisnu';
        }

        return 'Unknown';
    }

    public function collection(Collection $rows)
    {
        // Hapus Header
        $rows->shift();

        // Bungkus semua proses simpan dalam SATU transaksi database.
        // Ini bikin proses jauh lebih cepat karena SQLite cuma perlu
        // "commit" sekali di akhir, bukan per baris data (bisa 10-50x lebih cepat).
        DB::transaction(function () use ($rows) {
            $this->processRows($rows);
        });
    }

    private function processRows(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row[0])) {
                continue;
            }

            $snd = trim((string) $row[0]);

            // Skip data sampah
            if (!preg_match('/^\d{8,20}$/', $snd)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            $customer = Customer::updateOrCreate(
                [
                    'nomor_internet' => $snd,
                ],
                [
                    'nama_layanan_internet' => $row[2] ?? null,
                    'nama_pelanggan' => $row[4] ?? '-',
                    'no_hp_terbaru' => $row[5] ?? null,
                    'tipe_hunian_terbaru' => $row[6] ?? null,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | AR Agent
            |--------------------------------------------------------------------------
            */

            $agentName = $this->normalizeAgent(
                $row[3] ?? ''
            );

            $agent = ArAgent::firstOrCreate(
                [
                    'name' => $agentName,
                ],
                [
                    'is_active' => true,
                    'chat_id_telegram' => $row[11] ?? null,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Tanggal Input
            |--------------------------------------------------------------------------
            */

            $tanggalInput = now();

            if (!empty($row[1])) {

    try {

        $tanggalInput =
            Carbon::createFromFormat(
                'd/m/Y H:i:s',
                trim((string)$row[1])
            );

    } catch (\Exception $e) {

        try {

            $tanggalInput =
                Carbon::createFromFormat(
                    'd/m/Y',
                    trim((string)$row[1])
                );

        } catch (\Exception $e) {

            $tanggalInput = now();
        }
    }
}

            /*
            |--------------------------------------------------------------------------
            | Hasil Visit
            |--------------------------------------------------------------------------
            */

            $hasilVisit = strtolower(
                trim((string) ($row[7] ?? ''))
            );

            $kategoriVisit = strtolower(
                trim((string) ($row[8] ?? ''))
            );

            $isPtp =
                str_contains($hasilVisit, 'janji') ||
                str_contains($kategoriVisit, 'janji');

            /*
            |--------------------------------------------------------------------------
            | Collect ID
            |--------------------------------------------------------------------------
            */

            $collectId =
                'PRQ-' .
                $snd .
                '-' .
                $tanggalInput->format('Ymd');

            /*
            |--------------------------------------------------------------------------
            | Visit
            |--------------------------------------------------------------------------
            */

            $visit = Visit::updateOrCreate(
                [
                    'collect_id' => $collectId,
                ],
                [
                    'customer_id' => $customer->id,
                    'ar_agent_id' => $agent->id,

                    'tanggal_input' => $tanggalInput,

                    'hasil_visit' => !empty($row[7])
                        ? trim((string) $row[7])
                        : 'Belum Diisi',

                    'kategori_visit' => !empty($row[8])
                        ? trim((string) $row[8])
                        : '-',

                    'keterangan_visit' => !empty($row[9])
                        ? trim((string) $row[9])
                        : '-',

                    'foto_url' => !empty($row[10])
                        ? trim((string) $row[10])
                        : null,

                    'no_hp_snapshot' => $row[5] ?? null,
                    'tipe_hunian_snapshot' => $row[6] ?? null,

                    'is_ptp' => $isPtp,
                ]
            );

            $this->processedCount++;
            if ($visit->wasRecentlyCreated) {
                $this->createdVisits++;
            } else {
                $this->updatedVisits++;
            }
        }
    }
}