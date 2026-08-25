<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CaringLog;
use App\Models\WitelPerformance;
use App\Models\Visit;
use App\Services\CustomerSyncService;
use App\Services\CustomerPhoneEnricher;
use App\Services\C3mrCaringService;
use App\Services\ArAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class C3mrSyncController extends Controller
{
    private string $sheetId = '1RjhMpP3pTlzONbuoRajODGz3tTGm3p73';

    public function index()
    {
        $totalCustomers = Customer::count();
        $totalCaring    = CaringLog::count();
        $totalWitel     = WitelPerformance::count();
        $totalVisits    = Visit::count();
        $validPhones    = Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count();

        return view('c3mr.sync', compact(
            'totalCustomers',
            'totalCaring',
            'totalWitel',
            'totalVisits',
            'validPhones'
        ));
    }

    public function syncDataAll(Request $request)
    {
        try {
            $url = "https://docs.google.com/spreadsheets/d/{$this->sheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode('DATA ALL');
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                // Fallback ke file lokal jika offline
                $csvPath = storage_path('app/sheet_data-all.csv');
            } else {
                $csvPath = storage_path('app/sheet_data-all.csv');
                file_put_contents($csvPath, $response->body());
            }

            $res = CustomerSyncService::syncFromDataAllCsv($csvPath);
            CustomerPhoneEnricher::enrichPhoneNumbers();

            return redirect('/c3mr/sync')->with('success', "Sinkronisasi DATA ALL berhasil! Diproses: {$res['total_rows_processed']} baris. Total Customer: {$res['total_customers_now']}");
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal sync DATA ALL: " . $e->getMessage());
        }
    }

    public function syncCaring(Request $request)
    {
        try {
            $url = "https://docs.google.com/spreadsheets/d/{$this->sheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode('DATA ALL');
            $response = Http::timeout(60)->get($url);

            $csvPath = storage_path('app/sheet_data-all.csv');
            if ($response->successful()) {
                file_put_contents($csvPath, $response->body());
            }

            $res = C3mrCaringService::importCaringFromDataAll($csvPath);

            return redirect('/c3mr/sync')->with('success', "Sinkronisasi HASIL CARING berhasil! Diimpor: {$res['imported']} baris baru, {$res['updated']} diperbarui. Total Log: {$res['total_now']}");
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal sync Hasil Caring: " . $e->getMessage());
        }
    }

    public function syncPerformance(Request $request)
    {
        try {
            $url = "https://docs.google.com/spreadsheets/d/{$this->sheetId}/gviz/tq?tqx=out:csv&sheet=" . urlencode('PERFORMANSI DETAIL');
            $response = Http::timeout(60)->get($url);

            $csvPath = storage_path('app/sheet_performansi-detail.csv');
            if ($response->successful()) {
                file_put_contents($csvPath, $response->body());
            }

            $res = C3mrCaringService::importWitelPerformance($csvPath);

            return redirect('/c3mr/sync')->with('success', "Sinkronisasi PERFORMANSI DETAIL berhasil! Total Witel: {$res['total_witel']}");
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal sync Performansi Witel: " . $e->getMessage());
        }
    }

    public function consolidateAr(Request $request)
    {
        try {
            $res = ArAgentService::consolidateAgents();
            return redirect('/c3mr/sync')->with('success', "Konsolidasi AR Agent berhasil! Tersisa {$res['final_agent_count']} agent unik, {$res['merged_count']} duplikat digabungkan.");
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal konsolidasi AR: " . $e->getMessage());
        }
    }
}
