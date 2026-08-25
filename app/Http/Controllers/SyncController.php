<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Setting;
use App\Imports\ViseeproImport;
use App\Imports\ReportPrqImport;
use Illuminate\Support\Facades\File;

class SyncController extends Controller
{
    /**
     * Convert Google Sheet URL ke CSV Export URL
     */
    private function convertToCsvUrl(string $url): string
    {
        // Jika sudah export URL, langsung pakai
        if (
            str_contains($url, 'export?format=csv')
            || str_contains($url, 'export?format=xlsx')
        ) {
            return $url;
        }

        if (preg_match('/\/d\/([^\/]+)/', $url, $sheetMatch)) {

            $sheetId = $sheetMatch[1];

            $gid = '0';

            if (preg_match('/gid=(\d+)/', $url, $gidMatch)) {
                $gid = $gidMatch[1];
            }

            return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
        }

        return $url;
    }

    public function sync()
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Ambil Setting
            |--------------------------------------------------------------------------
            */

            $setting = Setting::first();

            if (!$setting) {

                return redirect('/import')
                    ->with(
                        'error',
                        'Setting Google Sheet belum diisi'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Convert URL Google Sheet
            |--------------------------------------------------------------------------
            */

            $viseeproUrl = $this->convertToCsvUrl(
                $setting->viseepro_url
            );

            $reportUrl = $this->convertToCsvUrl(
                $setting->report_prq_url
            );

            /*
            |--------------------------------------------------------------------------
            | Sync VISEEPRO
            |--------------------------------------------------------------------------
            */

            $viseeproResponse = Http::timeout(120)
                ->get($viseeproUrl);

            if (!$viseeproResponse->successful()) {

                return redirect('/import')
                    ->with(
                        'error',
                        'Gagal mengambil data VISEEPRO'
                    );
            }

            $viseeproFile =
    storage_path('app/viseepro.csv');

if (!File::isDirectory(storage_path('app'))) {
    File::makeDirectory(storage_path('app'), 0775, true);
}

file_put_contents(
    $viseeproFile,
    $viseeproResponse->body()
);

            Excel::import(
                new ViseeproImport(),
                $viseeproFile
            );

            /*
            |--------------------------------------------------------------------------
            | Sync REPORT PRQ
            |--------------------------------------------------------------------------
            */

            $reportResponse = Http::timeout(120)
                ->get($reportUrl);

            if (!$reportResponse->successful()) {

                return redirect('/import')
                    ->with(
                        'error',
                        'Gagal mengambil data Report PRQ'
                    );
            }

            $reportFile =
    storage_path('app/report-prq.csv');

if (!File::isDirectory(storage_path('app'))) {
    File::makeDirectory(storage_path('app'), 0775, true);
}

file_put_contents(
    $reportFile,
    $reportResponse->body()
);

            Excel::import(
                new ReportPrqImport(),
                $reportFile
            );

            /*
            |--------------------------------------------------------------------------
            | Hapus File Temporary
            |--------------------------------------------------------------------------
            */

            if (file_exists($viseeproFile)) {
                unlink($viseeproFile);
            }

            if (file_exists($reportFile)) {
                unlink($reportFile);
            }

            return redirect('/import')
                ->with(
                    'success',
                    'Sync C3MR POTS PRITI berhasil'
                );

        } catch (\Exception $e) {

            return redirect('/import')
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }
}