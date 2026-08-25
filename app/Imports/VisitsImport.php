<?php

namespace App\Imports;

use App\Models\Visit;
use App\Models\Customer;
use App\Models\ArAgent;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;

class VisitsImport implements ToModel
{
    public function model(array $row)
    {
        dd($row);
        /*
        |--------------------------------------------------------------------------
        | Skip Header
        |--------------------------------------------------------------------------
        */

        if (
            ($row[0] ?? null) === 'No.' ||
            ($row[0] ?? null) === 'No'
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Skip Baris Kosong
        |--------------------------------------------------------------------------
        */

        $collectId = trim((string) ($row[1] ?? ''));

        if ($collectId === '') {
            return null;
        }

        $nomorInternet = trim((string) ($row[3] ?? ''));

        if ($nomorInternet === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customer = Customer::updateOrCreate(
            [
                'nomor_internet' => $nomorInternet,
            ],
            [
                'nama_pelanggan'         => trim((string) ($row[6] ?? '-')),
                'nama_layanan_internet'  => trim((string) ($row[4] ?? '')),
                'no_hp_terbaru'          => trim((string) ($row[7] ?? '')),
                'tipe_hunian_terbaru'    => trim((string) ($row[8] ?? '')),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Nama AR
        |--------------------------------------------------------------------------
        */

        $namaAr = trim((string) ($row[5] ?? ''));

        if ($namaAr === '') {
            $namaAr = 'UNKNOWN AR';
        }

        $namaAr = strtoupper($namaAr);
        $namaAr = str_replace('.', '', $namaAr);
        $namaAr = preg_replace('/\s+/', ' ', $namaAr);

        $mappingAr = [

            'SAYUS' => 'SAYUS SUPRIYANTO',
            'SAYUS S' => 'SAYUS SUPRIYANTO',
            'SAYUSS' => 'SAYUS SUPRIYANTO',
            'SAYUS SUPRIYSNTO' => 'SAYUS SUPRIYANTO',
            'SAYYS S' => 'SAYUS SUPRIYANTO',

            'RAFLI' => 'RAFLI ZULFIKAR',

            'VINA' => 'VINA APRILIA',
            'VINA APRIL' => 'VINA APRILIA',

            'SANTI' => 'SANTI SURAHMAN',

            'YANA YANASURYANA' => 'YANA SURYANA',
        ];

        $namaAr = $mappingAr[$namaAr] ?? $namaAr;

        /*
        |--------------------------------------------------------------------------
        | AR Agent
        |--------------------------------------------------------------------------
        */

        $arAgent = ArAgent::updateOrCreate(
            [
                'name' => $namaAr,
            ],
            [
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Tanggal Input
        |--------------------------------------------------------------------------
        */

        $tanggalInput = now();

        if (!empty($row[2])) {

            try {

                $tanggalInput = Carbon::parse($row[2]);

            } catch (\Exception $e) {

                $tanggalInput = now();

            }
        }

        /*
        |--------------------------------------------------------------------------
        | Data Visit
        |--------------------------------------------------------------------------
        */

        $hasilVisit = trim((string) ($row[9] ?? ''));

        if ($hasilVisit === '') {
            $hasilVisit = 'Belum Diisi';
        }

        $kategoriVisit = trim((string) ($row[10] ?? ''));
        $keteranganVisit = trim((string) ($row[11] ?? ''));
        $fotoUrl = trim((string) ($row[12] ?? ''));

        $isPtp = str_contains(
            strtolower($hasilVisit),
            'janji'
        );

        /*
        |--------------------------------------------------------------------------
        | Logging Debug
        |--------------------------------------------------------------------------
        */

        \Log::info([
            'collect_id'      => $collectId,
            'nomor_internet'  => $nomorInternet,
            'customer_id'     => $customer->id,
            'ar_agent_id'     => $arAgent->id,
            'hasil_visit'     => $hasilVisit,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Visit
        |--------------------------------------------------------------------------
        */

        Visit::updateOrCreate(
            [
                'collect_id' => $collectId,
            ],
            [
                'customer_id'          => $customer->id,
                'ar_agent_id'          => $arAgent->id,
                'tanggal_input'        => $tanggalInput,
                'hasil_visit'          => $hasilVisit,
                'kategori_visit'       => $kategoriVisit,
                'keterangan_visit'     => $keteranganVisit,
                'foto_url'             => $fotoUrl,
                'no_hp_snapshot'       => $row[7] ?? null,
                'tipe_hunian_snapshot' => $row[8] ?? null,
                'is_ptp'               => $isPtp,
            ]
        );

        return null;
    }
}