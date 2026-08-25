<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ArAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiutangController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['visits', 'caringLogs'])
            ->where('saldo_piutang', '>', 0);

        // Filter Search: Nama, No Internet, No HP
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
        $totalPiutang = Customer::sum('saldo_piutang');
        $totalPelangganMenunggak = Customer::where('saldo_piutang', '>', 0)->count();
        $avgPiutang = $totalPelangganMenunggak > 0 ? round($totalPiutang / $totalPelangganMenunggak) : 0;
        $maxPiutang = Customer::max('saldo_piutang') ?: 0;

        // Aging Bucket Distribusi
        $agingBuckets = Customer::where('saldo_piutang', '>', 0)
            ->select('umur_customer', DB::raw('COUNT(*) as total_cust'), DB::raw('SUM(saldo_piutang) as total_saldo'))
            ->groupBy('umur_customer')
            ->orderByDesc('total_saldo')
            ->get();

        // Distribusi Piutang per Datel / Wilayah
        $datelDistribution = Customer::where('saldo_piutang', '>', 0)
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
        $query = Customer::where('saldo_piutang', '>', 0);

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
