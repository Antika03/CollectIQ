<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\ArAgent;
use Illuminate\Http\Request;

class PtpMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $query = Visit::with(['customer', 'arAgent'])
            ->where('is_ptp', true)
            ->latest('tanggal_input')
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%");
            });
        }

        if ($request->filled('ar_agent_id')) {
            $query->where('ar_agent_id', $request->ar_agent_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_input', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_input', '<=', $request->date_to);
        }

        $ptps = $query->paginate(20)->withQueryString();

        // KPIs
        $totalPtp      = Visit::where('is_ptp', true)->count();
        $todayPtp      = Visit::where('is_ptp', true)->whereDate('tanggal_input', today())->count();
        $ptpWithPhoto  = Visit::where('is_ptp', true)->whereNotNull('foto_url')->where('foto_url', '!=', '')->count();
        $totalVisits   = Visit::count();
        $ptpRate       = $totalVisits > 0 ? round(($totalPtp / $totalVisits) * 100, 1) : 0;

        $arAgents = ArAgent::orderBy('name')->get();

        return view('ptp-monitoring.index', compact(
            'ptps',
            'totalPtp',
            'todayPtp',
            'ptpWithPhoto',
            'ptpRate',
            'arAgents'
        ));
    }

    /**
     * Export data PTP ke CSV.
     */
    public function export(Request $request)
    {
        $query = Visit::with(['customer', 'arAgent'])
            ->where('is_ptp', true)
            ->latest('tanggal_input')
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%");
            });
        }
        if ($request->filled('ar_agent_id')) {
            $query->where('ar_agent_id', $request->ar_agent_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_input', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_input', '<=', $request->date_to);
        }

        $filename = 'export-ptp-monitoring-' . now()->format('Ymd_His') . '.csv';

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
                'Tanggal Input',
                'Nomor Internet',
                'Nama Pelanggan',
                'No HP',
                'AR Agent',
                'Hasil Visit',
                'Keterangan',
                'Foto URL',
            ]);

            $query->chunk(100, function ($ptps) use ($handle) {
                foreach ($ptps as $p) {
                    fputcsv($handle, [
                        $p->id,
                        $p->tanggal_input ? $p->tanggal_input->format('Y-m-d') : '',
                        $p->customer->nomor_internet ?? '',
                        $p->customer->nama_pelanggan ?? '',
                        $p->no_hp_snapshot ?? ($p->customer->no_hp_terbaru ?? ''),
                        $p->arAgent->name ?? '',
                        $p->hasil_visit ?? '',
                        $p->keterangan_visit ?? '',
                        $p->foto_url ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}