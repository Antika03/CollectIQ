<?php

namespace App\Http\Controllers;

use App\Models\CaringLog;
use App\Models\ArAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class C3mrCaringController extends Controller
{
    public function index(Request $request)
    {
        $query = CaringLog::with(['customer', 'arAgent'])
            ->latest('tanggal_caring')
            ->latest('id');

        // Filter: Tanggal Mulai
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_caring', '>=', $request->date_from);
        }

        // Filter: Tanggal Akhir
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_caring', '<=', $request->date_to);
        }

        // Filter: AR Agent / Petugas Caring
        if ($request->filled('petugas')) {
            $query->where('petugas_caring', $request->petugas);
        }

        // Filter: Status Caring (CONTACTED / UNCONTACTED)
        if ($request->filled('status_caring')) {
            $query->where('status_caring', $request->status_caring);
        }

        // Filter: VOC / Hasil Caring
        if ($request->filled('voc')) {
            $query->where('voc', $request->voc);
        }

        // Filter: Status Bayar (PAID / UNPAID)
        if ($request->filled('status_bayar')) {
            $query->where('status_bayar', $request->status_bayar);
        }

        // Search: Nama, No Internet, No HP
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $caringLogs = $query->paginate(20)->withQueryString();

        // KPI Metrics dari data asli
        $totalCaring        = CaringLog::count();
        $totalContacted     = CaringLog::where('status_caring', 'CONTACTED')->count();
        $totalUncontacted   = CaringLog::where('status_caring', 'UNCONTACTED')->count();
        $totalPtp           = CaringLog::where('is_ptp', true)->count();
        $totalPaid          = CaringLog::where('status_bayar', 'PAID')->count();
        $totalUnpaid        = CaringLog::where('status_bayar', 'UNPAID')->count();

        $successRate = $totalCaring > 0 ? round(($totalContacted / $totalCaring) * 100, 1) : 0;
        $ptpRate     = $totalCaring > 0 ? round(($totalPtp / $totalCaring) * 100, 1) : 0;
        $paidRate    = $totalCaring > 0 ? round(($totalPaid / $totalCaring) * 100, 1) : 0;

        // Distribusi VOC (Voice of Customer / Hasil Caring)
        $vocDistribution = CaringLog::whereNotNull('voc')
            ->where('voc', '!=', '')
            ->select('voc', DB::raw('COUNT(*) as total'))
            ->groupBy('voc')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        // Daftar dropdown filter
        $petugasList = CaringLog::whereNotNull('petugas_caring')
            ->distinct()
            ->orderBy('petugas_caring')
            ->pluck('petugas_caring');

        $vocList = CaringLog::whereNotNull('voc')
            ->distinct()
            ->orderBy('voc')
            ->pluck('voc');

        // Last Sync Information
        $setting = \App\Models\Setting::first();
        $lastSyncFormatted = \App\Services\C3mrSyncService::formatIndonesianDate($setting?->last_sync_at);

        return view('c3mr.hasil-caring', compact(
            'caringLogs',
            'totalCaring',
            'totalContacted',
            'totalUncontacted',
            'totalPtp',
            'totalPaid',
            'totalUnpaid',
            'successRate',
            'ptpRate',
            'paidRate',
            'vocDistribution',
            'petugasList',
            'vocList',
            'lastSyncFormatted'
        ));
    }

    /**
     * Export data Hasil Caring ke CSV.
     */
    public function export(Request $request)
    {
        $query = CaringLog::latest('tanggal_caring')->latest('id');

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_caring', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_caring', '<=', $request->date_to);
        }
        if ($request->filled('petugas')) {
            $query->where('petugas_caring', $request->petugas);
        }
        if ($request->filled('status_caring')) {
            $query->where('status_caring', $request->status_caring);
        }
        if ($request->filled('voc')) {
            $query->where('voc', $request->voc);
        }
        if ($request->filled('status_bayar')) {
            $query->where('status_bayar', $request->status_bayar);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        $filename = 'export-hasil-caring-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Tanggal Caring',
                'Nomor Internet',
                'Nama Pelanggan',
                'No HP',
                'Petugas Caring',
                'Status Caring',
                'VOC / Hasil',
                'Janji Bayar (PTP)',
                'Status Bayar',
                'Keterangan',
            ]);

            $query->chunk(100, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->tanggal_caring ? $log->tanggal_caring->format('Y-m-d') : '',
                        $log->nomor_internet ?? '',
                        $log->nama_pelanggan ?? '',
                        $log->no_hp ?? '',
                        $log->petugas_caring ?? '',
                        $log->status_caring ?? '',
                        $log->voc ?? '',
                        $log->is_ptp ? 'YA' : 'TIDAK',
                        $log->status_bayar ?? '',
                        $log->keterangan ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
