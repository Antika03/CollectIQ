<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CaringLog;
use App\Models\WitelPerformance;
use App\Models\Visit;
use App\Models\ViseeproData;
use App\Models\Setting;
use App\Services\C3mrSyncService;
use App\Services\CustomerSyncService;
use App\Services\CustomerPhoneEnricher;
use App\Services\C3mrCaringService;
use App\Services\ArAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class C3mrSyncController extends Controller
{
    public function index()
    {
        $totalCustomers = Customer::count();
        $totalCaring    = CaringLog::count();
        $totalWitel     = WitelPerformance::count();
        $totalVisits    = Visit::count();
        $totalViseepro  = ViseeproData::count();
        $validPhones    = Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count();

        $setting = Setting::first();
        $lastSyncAt = $setting?->last_sync_at;
        $lastSyncFormatted = $lastSyncAt 
            ? $lastSyncAt->translatedFormat('d F Y, H:i') . ' WIB' 
            : 'Belum pernah disinkronkan';
        $lastSyncStatus = $setting?->last_sync_status;
        $lastSyncResult = $setting?->last_sync_result;

        return view('c3mr.sync', compact(
            'totalCustomers',
            'totalCaring',
            'totalWitel',
            'totalVisits',
            'totalViseepro',
            'validPhones',
            'setting',
            'lastSyncAt',
            'lastSyncFormatted',
            'lastSyncStatus',
            'lastSyncResult'
        ));
    }

    /**
     * Master Sync Data C3MR (Report PRQ, VISEEPRO, DATA ALL, CARING, PERFORMANSI, & AR)
     */
    public function syncAll(Request $request)
    {
        try {
            $result = C3mrSyncService::syncAll();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($result);
            }

            if ($result['status'] === 'success') {
                return redirect('/c3mr/sync')->with('success', "Sync Data C3MR Berhasil! {$result['total_rows_processed']} data dari seluruh sumber berhasil diperbarui.")->with('syncResult', $result);
            } elseif ($result['status'] === 'warning') {
                return redirect('/c3mr/sync')->with('warning', "Sinkronisasi selesai dengan beberapa catatan. Silakan periksa rincian sumber data.")->with('syncResult', $result);
            } else {
                return redirect('/c3mr/sync')->with('error', "Gagal melakukan sinkronisasi data C3MR.")->with('syncResult', $result);
            }
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'      => false,
                    'status'       => 'error',
                    'status_label' => 'Sinkronisasi gagal',
                    'message'      => $e->getMessage(),
                ], 500);
            }

            return redirect('/c3mr/sync')->with('error', "Terjadi kesalahan saat sinkronisasi: " . $e->getMessage());
        }
    }

    public function syncDataAll(Request $request)
    {
        try {
            $res = C3mrSyncService::syncDataAll();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Sinkronisasi DATA ALL berhasil! Diproses: {$res['count']} baris.");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal sync DATA ALL: " . $e->getMessage());
        }
    }

    public function syncCaring(Request $request)
    {
        try {
            $res = C3mrSyncService::syncCaring();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Sinkronisasi HASIL CARING berhasil! {$res['message']}");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal sync Hasil Caring: " . $e->getMessage());
        }
    }

    public function syncPerformance(Request $request)
    {
        try {
            $res = C3mrSyncService::syncPerformance();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Sinkronisasi PERFORMANSI DETAIL berhasil! {$res['message']}");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal sync Performansi Witel: " . $e->getMessage());
        }
    }

    public function consolidateAr(Request $request)
    {
        try {
            $res = C3mrSyncService::consolidateAr();
            if ($res['success']) {
                return redirect('/c3mr/sync')->with('success', "Konsolidasi AR Agent berhasil! {$res['message']}");
            }
            return redirect('/c3mr/sync')->with('error', $res['message']);
        } catch (\Throwable $e) {
            return redirect('/c3mr/sync')->with('error', "Gagal konsolidasi AR: " . $e->getMessage());
        }
    }
}
