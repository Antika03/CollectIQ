<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Visit;
use App\Models\ArAgent;
use App\Models\CaringLog;
use App\Models\WitelPerformance;
use App\Services\ChurnRiskService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Executive KPIs
        $totalCustomers = Customer::count();
        $totalVisits    = Visit::count();
        $totalPtp       = Visit::where('is_ptp', true)->count();
        $totalCaring    = CaringLog::count();
        $totalPiutang   = Customer::sum('saldo_piutang');
        $totalPaid      = CaringLog::where('status_bayar', 'PAID')->count();

        $todayVisits    = Visit::whereDate('tanggal_input', today())->count();
        $todayPTP       = Visit::whereDate('tanggal_input', today())->where('is_ptp', true)->count();

        // 2. Churn Risk & Action Required Matrix
        $allCustomers = Customer::all();
        $criticalCount = 0;
        $highCount     = 0;
        $mediumCount   = 0;
        $lowCount      = 0;

        foreach ($allCustomers as $c) {
            $eval = ChurnRiskService::evaluateCustomer($c);
            if ($eval['level'] === 'CRITICAL') $criticalCount++;
            elseif ($eval['level'] === 'HIGH') $highCount++;
            elseif ($eval['level'] === 'MEDIUM') $mediumCount++;
            else $lowCount++;
        }

        // 3. Chart: Trend Visit & PTP 14 Hari Terakhir
        $rangeStart = Carbon::today()->subDays(13);

        $dailyRaw = Visit::whereDate('tanggal_input', '>=', $rangeStart)
            ->selectRaw('tanggal_input, COUNT(*) as total')
            ->groupBy('tanggal_input')
            ->pluck('total', 'tanggal_input')
            ->toArray();

        $dailyPtpRaw = Visit::whereDate('tanggal_input', '>=', $rangeStart)
            ->where('is_ptp', true)
            ->selectRaw('tanggal_input, COUNT(*) as total')
            ->groupBy('tanggal_input')
            ->pluck('total', 'tanggal_input')
            ->toArray();

        $chartLabels = [];
        $chartVisits = [];
        $chartPtp    = [];

        for ($i = 0; $i < 14; $i++) {
            $date          = $rangeStart->copy()->addDays($i);
            $key           = $date->format('Y-m-d');
            $chartLabels[] = $date->format('d/m');
            $chartVisits[] = $dailyRaw[$key] ?? 0;
            $chartPtp[]    = $dailyPtpRaw[$key] ?? 0;
        }

        // 4. Top AR Agents
        $topAgents = ArAgent::withCount('visits')
            ->orderByDesc('visits_count')
            ->take(5)
            ->get();

        $topAgentsTotalVisits = max($topAgents->sum('visits_count'), 1);
        $topAgents->each(function ($agent) use ($topAgentsTotalVisits) {
            $agent->contribution_percent = round(($agent->visits_count / $topAgentsTotalVisits) * 100);
        });

        // 5. Top Outstanding Customers
        $topOutstanding = Customer::where('saldo_piutang', '>', 0)
            ->orderByDesc('saldo_piutang')
            ->take(5)
            ->get();

        // 6. Recent Visits with Photo Preview
        $latestVisits = Visit::with(['customer', 'arAgent'])
            ->latest('tanggal_input')
            ->latest('id')
            ->take(5)
            ->get();

        // 7. Recent Caring Logs
        $latestCaring = CaringLog::with('customer')
            ->latest('tanggal_caring')
            ->latest('id')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalCustomers',
            'totalVisits',
            'totalPtp',
            'totalCaring',
            'totalPiutang',
            'totalPaid',
            'todayVisits',
            'todayPTP',
            'criticalCount',
            'highCount',
            'mediumCount',
            'lowCount',
            'chartLabels',
            'chartVisits',
            'chartPtp',
            'topAgents',
            'topOutstanding',
            'latestVisits',
            'latestCaring'
        ));
    }
}
