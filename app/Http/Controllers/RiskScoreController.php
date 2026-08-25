<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ChurnRiskService;
use Illuminate\Http\Request;

class RiskScoreController extends Controller
{
    public function index(Request $request)
    {
        $allCustomers = Customer::with(['visits', 'caringLogs'])->get();

        $riskCounts = [
            'CRITICAL' => 0,
            'HIGH'     => 0,
            'MEDIUM'   => 0,
            'LOW'      => 0,
        ];

        $evaluated = $allCustomers->map(function ($customer) use (&$riskCounts) {
            $eval = ChurnRiskService::evaluateCustomer($customer);
            $customer->risk_score_calc = $eval['score'];
            $customer->risk_level_calc = $eval['level'];
            $customer->risk_reasons    = $eval['reasons'];
            $customer->recommendation  = $eval['recommendation'];

            $riskCounts[$eval['level']] = ($riskCounts[$eval['level']] ?? 0) + 1;

            return $customer;
        });

        // Filter / Search
        $search = $request->query('search');
        $filterLevel = $request->query('risk_level');

        $filtered = $evaluated;

        if (!empty($search)) {
            $filtered = $filtered->filter(function ($c) use ($search) {
                return str_contains(strtolower($c->nama_pelanggan ?? ''), strtolower($search)) ||
                       str_contains($c->nomor_internet ?? '', $search) ||
                       str_contains($c->no_hp_terbaru ?? '', $search) ||
                       str_contains(strtolower($c->datel ?? ''), strtolower($search));
            });
        }

        if (!empty($filterLevel)) {
            $filtered = $filtered->where('risk_level_calc', strtoupper($filterLevel));
        }

        $sorted = $filtered->sortByDesc('risk_score_calc')->values();

        // Pagination
        $page = (int) $request->input('page', 1);
        $perPage = 20;
        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->forPage($page, $perPage),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalCustomers = $allCustomers->count();

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
        $allCustomers = Customer::with(['visits', 'caringLogs'])->get();

        $filename = 'export-risk-score-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($allCustomers) {
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

            foreach ($allCustomers as $c) {
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

            fclose($handle);
        }, 200, $headers);
    }
}