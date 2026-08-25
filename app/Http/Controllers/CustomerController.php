<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ChurnRiskService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $customers = Customer::query();

        if ($search) {
            $customers->where(function ($query) use ($search) {
                $query->where('nomor_internet', 'like', "%{$search}%")
                    ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                    ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                    ->orWhere('datel', 'like', "%{$search}%")
                    ->orWhere('sto', 'like', "%{$search}%")
                    ->orWhere('ncli', 'like', "%{$search}%")
                    ->orWhere('nama_layanan_internet', 'like', "%{$search}%");
            });
        }

        if ($request->filled('risk_level')) {
            $customers->where('risk_level', strtolower($request->risk_level));
        }

        if ($request->filled('has_piutang')) {
            if ($request->has_piutang === '1') {
                $customers->where('saldo_piutang', '>', 0);
            } else {
                $customers->where('saldo_piutang', '<=', 0);
            }
        }

        // Sorting: default by latest ID, or by saldo piutang desc
        $sortBy = $request->input('sort_by', 'id');
        if ($sortBy === 'saldo_piutang') {
            $customers->orderByDesc('saldo_piutang');
        } elseif ($sortBy === 'nama_pelanggan') {
            $customers->orderBy('nama_pelanggan');
        } else {
            $customers->latest('id');
        }

        $customers = $customers
            ->withCount('visits')
            ->paginate(25)
            ->withQueryString();

        $totalCustomers = Customer::count();
        $highRiskCount  = Customer::whereIn('risk_level', ['high', 'critical'])->count();
        $activePtpCount = Customer::where('pending_ptp_count', '>', 0)->count();
        $totalPiutang   = Customer::sum('saldo_piutang');
        $staleCount     = Customer::where(function ($q) {
            $q->whereNull('last_visit_at')
              ->orWhere('last_visit_at', '<=', now()->subDays(14));
        })->count();

        return view('customers.index', [
            'customers'      => $customers,
            'search'         => $search,
            'totalCustomers' => $totalCustomers,
            'highRiskCount'  => $highRiskCount,
            'activePtpCount' => $activePtpCount,
            'totalPiutang'   => $totalPiutang,
            'staleCount'     => $staleCount,
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'visits.arAgent',
            'caringLogs',
            'viseeproData',
        ]);

        $churnEval = ChurnRiskService::evaluateCustomer($customer);

        return view('customers.show', compact('customer', 'churnEval'));
    }

    /**
     * Export data customer ke CSV (mengikuti filter aktif).
     */
    public function export(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                  ->orWhere('datel', 'like', "%{$search}%")
                  ->orWhere('ncli', 'like', "%{$search}%");
            });
        }
        if ($request->filled('risk_level')) {
            $query->where('risk_level', strtolower($request->risk_level));
        }
        if ($request->filled('has_piutang')) {
            if ($request->has_piutang === '1') {
                $query->where('saldo_piutang', '>', 0);
            } else {
                $query->where('saldo_piutang', '<=', 0);
            }
        }

        $filename = 'export-customers-' . now()->format('Ymd_His') . '.csv';

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
                'NCLI',
                'Nama Pelanggan',
                'No HP Terbaru',
                'Email',
                'Layanan',
                'Datel',
                'STO',
                'Alamat',
                'Saldo Piutang (Rp)',
                'Umur Customer / Aging',
                'Risk Level',
                'Total Visit',
                'Visit Terakhir',
            ]);

            $query->chunk(100, function ($customers) use ($handle) {
                foreach ($customers as $c) {
                    fputcsv($handle, [
                        $c->id,
                        $c->nomor_internet ?? '',
                        $c->ncli ?? '',
                        $c->nama_pelanggan ?? '',
                        $c->no_hp_terbaru ?? '',
                        $c->email ?? '',
                        $c->nama_layanan_internet ?? '',
                        $c->datel ?? '',
                        $c->sto ?? '',
                        $c->alamat ?? '',
                        $c->saldo_piutang ?? 0,
                        $c->umur_customer ?? '',
                        strtoupper($c->risk_level ?? 'LOW'),
                        $c->total_visits ?? 0,
                        $c->last_visit_at ? $c->last_visit_at->format('Y-m-d') : '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
