<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ArAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PiutangController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Customer::with(['visits', 'caringLogs', 'assignedArAgent'])
            ->where('saldo_piutang', '>', 0);

        // Jika user AR, batasi ke data tanggung jawabnya
        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $query->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        }

        // Filter Search: Nama, No Internet, No HP, Datel
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                  ->orWhere('datel', 'like', "%{$search}%");
            });
        }

        // Filter Aging / Umur Customer
        if ($request->filled('umur_customer')) {
            $query->where('umur_customer', $request->umur_customer);
        }

        // Filter Datel / Wilayah
        if ($request->filled('datel')) {
            $query->where('datel', $request->datel);
        }

        // Filter PRANPC
        if ($request->filled('is_pranpc')) {
            if ($request->is_pranpc === '1') {
                $query->where('is_pranpc', true);
            } else {
                $query->where('is_pranpc', false);
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'saldo_piutang');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['saldo_piutang', 'nama_pelanggan', 'umur_customer', 'last_visit_at'])) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('saldo_piutang');
        }

        $customers = $query->paginate(20)->withQueryString();

        // KPI Summary Piutang
        $baseKpiQuery = Customer::where('saldo_piutang', '>', 0);
        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $baseKpiQuery->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        }

        $totalPiutang = (clone $baseKpiQuery)->sum('saldo_piutang');
        $totalPelangganMenunggak = (clone $baseKpiQuery)->count();
        $avgPiutang = $totalPelangganMenunggak > 0 ? round($totalPiutang / $totalPelangganMenunggak) : 0;
        $maxPiutang = (clone $baseKpiQuery)->max('saldo_piutang') ?: 0;
        $pranpcPiutang = (clone $baseKpiQuery)->where('is_pranpc', true)->sum('saldo_piutang');
        $pranpcCount = (clone $baseKpiQuery)->where('is_pranpc', true)->count();

        // Aging Bucket Distribusi
        $agingBuckets = (clone $baseKpiQuery)
            ->select('umur_customer', DB::raw('COUNT(*) as total_cust'), DB::raw('SUM(saldo_piutang) as total_saldo'))
            ->groupBy('umur_customer')
            ->orderByDesc('total_saldo')
            ->get();

        // Distribusi Piutang per Datel / Wilayah
        $datelDistribution = (clone $baseKpiQuery)
            ->whereNotNull('datel')
            ->where('datel', '!=', '')
            ->select('datel', DB::raw('COUNT(*) as total_cust'), DB::raw('SUM(saldo_piutang) as total_saldo'))
            ->groupBy('datel')
            ->orderByDesc('total_saldo')
            ->take(8)
            ->get();

        $datelList = Customer::whereNotNull('datel')->where('datel', '!=', '')->distinct()->orderBy('datel')->pluck('datel');
        $agingList = Customer::whereNotNull('umur_customer')->where('umur_customer', '!=', '')->distinct()->orderBy('umur_customer')->pluck('umur_customer');

        return view('piutang.index', compact(
            'customers',
            'totalPiutang',
            'totalPelangganMenunggak',
            'avgPiutang',
            'maxPiutang',
            'pranpcPiutang',
            'pranpcCount',
            'agingBuckets',
            'datelDistribution',
            'datelList',
            'agingList',
            'sortBy',
            'sortOrder'
        ));
    }

    /**
     * Export data piutang ke CSV.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $query = Customer::where('saldo_piutang', '>', 0);

        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $query->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                  ->orWhere('datel', 'like', "%{$search}%");
            });
        }
        if ($request->filled('umur_customer')) {
            $query->where('umur_customer', $request->umur_customer);
        }
        if ($request->filled('datel')) {
            $query->where('datel', $request->datel);
        }
        if ($request->filled('is_pranpc')) {
            if ($request->is_pranpc === '1') {
                $query->where('is_pranpc', true);
            } else {
                $query->where('is_pranpc', false);
            }
        }

        $query->orderByDesc('saldo_piutang');

        $filename = 'export-piutang-' . now()->format('Ymd_His') . '.csv';

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
                'Nomor Internet',
                'Nama Pelanggan',
                'Nomor HP',
                'Status PRANPC',
                'Datel',
                'STO',
                'Umur Tunggakan / Aging',
                'Saldo Piutang (Rp)',
                'Layanan Produk',
            ]);

            $query->chunk(100, function ($customers) use ($handle) {
                foreach ($customers as $c) {
                    fputcsv($handle, [
                        $c->id,
                        $c->nomor_internet ?? '',
                        $c->nama_pelanggan ?? '',
                        $c->no_hp_terbaru ?? '',
                        $c->is_pranpc ? 'PRANPC' : 'NON-PRANPC',
                        $c->datel ?? '',
                        $c->sto ?? '',
                        $c->umur_customer ?? '',
                        $c->saldo_piutang ?? 0,
                        $c->nama_layanan_internet ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
