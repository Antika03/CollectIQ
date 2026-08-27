<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ChurnRiskService;
use Illuminate\Http\Request;

class RiskScoreController extends Controller
{
    public function index(Request $request)
    {
        // 1. KPI Counts via database aggregation (fast)
        $riskCountsRaw = Customer::selectRaw('LOWER(risk_level) as lvl, COUNT(*) as total')
            ->groupBy('lvl')
            ->pluck('total', 'lvl')
            ->toArray();

        $riskCounts = [
            'CRITICAL' => $riskCountsRaw['critical'] ?? 0,
            'HIGH'     => $riskCountsRaw['high'] ?? 0,
            'MEDIUM'   => $riskCountsRaw['medium'] ?? 0,
            'LOW'      => ($riskCountsRaw['low'] ?? 0) + ($riskCountsRaw[''] ?? 0),
        ];

        $totalCustomers = Customer::count();

        // 2. Query Builder with Search & Filters
        $query = Customer::query();

        $search = $request->query('search');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                  ->orWhere('datel', 'like', "%{$search}%")
                  ->orWhere('ncli', 'like', "%{$search}%");
            });
        }

        $filterLevel = $request->query('risk_level');
        if (!empty($filterLevel)) {
            $query->where('risk_level', strtolower($filterLevel));
        }

        $query->orderByDesc('risk_score')->orderByDesc('saldo_piutang');

        $customers = $query->with(['visits', 'caringLogs'])
            ->paginate(20)
            ->withQueryString();

        // Evaluate detailed metrics for the 20 records on the current page
        $customers->getCollection()->transform(function ($customer) {
            $eval = ChurnRiskService::evaluateCustomer($customer);
            $customer->risk_score_calc = $eval['score'];
            $customer->risk_level_calc = $eval['level'];
            $customer->risk_reasons    = $eval['reasons'];
            $customer->recommendation  = $eval['recommendation'];
            return $customer;
        });

        return view('risk-score.index', compact(
            'customers',
            'riskCounts',
            'totalCustomers',
            'search',
            'filterLevel'
        ));
    }

    /**
     * Export data Risk Score ke CSV.
     */
    public function export(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                  ->orWhere('datel', 'like', "%{$search}%");
            });
        }
        if ($request->filled('risk_level')) {
            $query->where('risk_level', strtolower($request->risk_level));
        }

        $query->orderByDesc('risk_score')->orderByDesc('saldo_piutang');

        $filename = 'export-risk-score-' . now()->format('Ymd_His') . '.csv';

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
                'No HP',
                'Datel',
                'Saldo Piutang (Rp)',
                'Umur Tunggakan',
                'Risk Score (0-100)',
                'Risk Level',
                'Rekomendasi Tindakan',
                'Faktor Risiko',
            ]);

            $query->chunk(100, function ($customers) use ($handle) {
                foreach ($customers as $c) {
                    $eval = ChurnRiskService::evaluateCustomer($c);
                    fputcsv($handle, [
                        $c->id,
                        $c->nomor_internet ?? '',
                        $c->nama_pelanggan ?? '',
                        $c->no_hp_terbaru ?? '',
                        $c->datel ?? '',
                        $c->saldo_piutang ?? 0,
                        $c->umur_customer ?? '',
                        $eval['score'],
                        $eval['level'],
                        $eval['recommendation'],
                        implode('; ', $eval['reasons']),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}