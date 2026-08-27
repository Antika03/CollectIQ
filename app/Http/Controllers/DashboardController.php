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
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user && $user->isAr()) {
            return redirect()->route('ar.dashboard');
        }

        // 1. Executive KPIs (Dynamic dari Database Nyata)
        $totalCustomers   = Customer::count();
        $totalVisits      = Visit::count();
        $totalPtp         = Visit::where('is_ptp', true)->count();
        $totalCaring      = CaringLog::count();
        $totalPiutang     = Customer::sum('saldo_piutang');
        $totalPaid        = CaringLog::where('status_bayar', 'PAID')->count();
        $totalOutstanding = Customer::where('saldo_piutang', '>', 0)->count();
        $validPhones      = Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count();
        $missingPhones    = max($totalCustomers - $validPhones, 0);

        // PRANPC Metrics
        $pranpcCount      = Customer::where('is_pranpc', true)->count();
        $pranpcPiutang    = Customer::where('is_pranpc', true)->sum('saldo_piutang');

        // Unvisited & Revisit
        $unvisitedCount   = Customer::whereNull('last_visit_at')->where('saldo_piutang', '>', 0)->count();
        $revisitCount     = Customer::whereNotNull('last_visit_at')->where('last_visit_at', '<=', now()->subDays(14))->where('saldo_piutang', '>', 0)->count();

        $todayVisits      = Visit::whereDate('tanggal_input', today())->count();
        $todayPTP         = Visit::whereDate('tanggal_input', today())->where('is_ptp', true)->count();

        // 2. Churn Risk Matrix (Indikasi Risiko Churn)
        $riskCounts = Customer::selectRaw('LOWER(risk_level) as lvl, COUNT(*) as total')
            ->groupBy('lvl')
            ->pluck('total', 'lvl')
            ->toArray();

        $criticalCount = $riskCounts['critical'] ?? 0;
        $highCount     = $riskCounts['high'] ?? 0;
        $mediumCount   = $riskCounts['medium'] ?? 0;
        $lowCount      = ($riskCounts['low'] ?? 0) + ($riskCounts[''] ?? 0);

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

        // 4. Top Master AR Agents
        $topAgents = ArAgent::where('is_active', true)
            ->withCount('visits')
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
            'totalOutstanding',
            'validPhones',
            'missingPhones',
            'pranpcCount',
            'pranpcPiutang',
            'unvisitedCount',
            'revisitCount',
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
