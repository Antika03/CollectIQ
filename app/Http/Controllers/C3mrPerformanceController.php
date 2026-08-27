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

        // 1. KPI Counts via database aggregation (blazing fast)
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
        $totalHighCritical = $riskCounts['CRITICAL'] + $riskCounts['HIGH'];
        $churnRiskRate = $totalCustomers > 0 ? round(($totalHighCritical / $totalCustomers) * 100, 1) : 0;
        $totalOutstanding = Customer::sum('saldo_piutang');

        // 2. Query Builder with Search & Filters for Top At-Risk Customers
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

        $paginatedCustomers = $query->with(['visits', 'caringLogs'])
            ->paginate(15)
            ->withQueryString();

        // Evaluate detailed churn metrics for the 15 records on the current page
        $paginatedCustomers->getCollection()->transform(function ($c) {
            $eval = ChurnRiskService::evaluateCustomer($c);
            $c->churn_score          = $eval['score'];
            $c->churn_level          = $eval['level'];
            $c->churn_reasons        = $eval['reasons'];
            $c->churn_recommendation = $eval['recommendation'];
            return $c;
        });

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
