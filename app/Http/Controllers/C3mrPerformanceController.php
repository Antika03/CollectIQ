<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\WitelPerformance;
use App\Models\Visit;
use App\Models\CaringLog;
use App\Services\ChurnRiskService;
use Illuminate\Http\Request;

class C3mrPerformanceController extends Controller
{
    public function index(Request $request)
    {
        $witelList = WitelPerformance::orderBy('rank')->get();

        // Evaluasi seluruh customer untuk menghitung Churn Risk Indicator
        $allCustomers = Customer::with(['visits', 'caringLogs'])->get();

        $riskCounts = [
            'CRITICAL' => 0,
            'HIGH'     => 0,
            'MEDIUM'   => 0,
            'LOW'      => 0,
        ];

        $evaluatedCustomers = $allCustomers->map(function ($c) use (&$riskCounts) {
            $eval = ChurnRiskService::evaluateCustomer($c);
            $c->churn_score = $eval['score'];
            $c->churn_level = $eval['level'];
            $c->churn_reasons = $eval['reasons'];
            $c->churn_recommendation = $eval['recommendation'];

            $riskCounts[$eval['level']] = ($riskCounts[$eval['level']] ?? 0) + 1;

            return $c;
        });

        // Filter / Search untuk tabel Top At-Risk Customers
        $search = $request->query('search');
        $filterLevel = $request->query('risk_level');

        $filtered = $evaluatedCustomers;

        if (!empty($search)) {
            $filtered = $filtered->filter(function ($c) use ($search) {
                return str_contains(strtolower($c->nama_pelanggan), strtolower($search)) ||
                       str_contains($c->nomor_internet, $search) ||
                       str_contains($c->no_hp_terbaru ?? '', $search);
            });
        }

        if (!empty($filterLevel)) {
            $filtered = $filtered->where('churn_level', strtoupper($filterLevel));
        }

        // Urutkan dari risiko tertinggi
        $topRiskCustomers = $filtered->sortByDesc('churn_score')->values();

        // Pagination manual sederhana untuk collection
        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $paginatedCustomers = new \Illuminate\Pagination\LengthAwarePaginator(
            $topRiskCustomers->forPage($page, $perPage),
            $topRiskCustomers->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalCustomers = $allCustomers->count();
        $totalHighCritical = $riskCounts['CRITICAL'] + $riskCounts['HIGH'];
        $churnRiskRate = $totalCustomers > 0 ? round(($totalHighCritical / $totalCustomers) * 100, 1) : 0;
        $totalOutstanding = $allCustomers->sum('saldo_piutang');

        // Last Sync Information
        $setting = \App\Models\Setting::first();
        $lastSyncFormatted = \App\Services\C3mrSyncService::formatIndonesianDate($setting?->last_sync_at);

        return view('c3mr.performance', compact(
            'witelList',
            'riskCounts',
            'paginatedCustomers',
            'totalCustomers',
            'totalHighCritical',
            'churnRiskRate',
            'totalOutstanding',
            'search',
            'filterLevel',
            'lastSyncFormatted'
        ));
    }
}
