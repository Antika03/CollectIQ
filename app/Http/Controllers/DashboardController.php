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

        $dashboardData = \Illuminate\Support\Facades\Cache::remember('executive_dashboard_full_stats', 300, function () {
            $fourteenDaysAgo = now()->subDays(14)->toDateTimeString();
            $today = today()->toDateString();
            $rangeStart = Carbon::today()->subDays(13)->toDateString();

            // 1. Single-pass Aggregate untuk Customer Metrics & Churn Risk (1 Query)
            $custStats = DB::table('customers')->selectRaw("
                COUNT(*) as total_customers,
                COALESCE(SUM(saldo_piutang), 0) as total_piutang,
                SUM(CASE WHEN saldo_piutang > 0 THEN 1 ELSE 0 END) as total_outstanding,
                SUM(CASE WHEN no_hp_terbaru IS NOT NULL AND no_hp_terbaru != '' THEN 1 ELSE 0 END) as valid_phones,
                SUM(CASE WHEN is_pranpc = 1 THEN 1 ELSE 0 END) as pranpc_count,
                COALESCE(SUM(CASE WHEN is_pranpc = 1 THEN saldo_piutang ELSE 0 END), 0) as pranpc_piutang,
                SUM(CASE WHEN last_visit_at IS NULL AND saldo_piutang > 0 THEN 1 ELSE 0 END) as unvisited_count,
                SUM(CASE WHEN last_visit_at IS NOT NULL AND last_visit_at <= ? AND saldo_piutang > 0 THEN 1 ELSE 0 END) as revisit_count,
                SUM(CASE WHEN LOWER(risk_level) = 'critical' THEN 1 ELSE 0 END) as critical_count,
                SUM(CASE WHEN LOWER(risk_level) = 'high' THEN 1 ELSE 0 END) as high_count,
                SUM(CASE WHEN LOWER(risk_level) = 'medium' THEN 1 ELSE 0 END) as medium_count,
                SUM(CASE WHEN LOWER(risk_level) = 'low' OR risk_level IS NULL OR risk_level = '' THEN 1 ELSE 0 END) as low_count
            ", [$fourteenDaysAgo])->first();

            $totalCustomers   = (int)($custStats->total_customers ?? 0);
            $totalPiutang     = (float)($custStats->total_piutang ?? 0);
            $totalOutstanding = (int)($custStats->total_outstanding ?? 0);
            $validPhones      = (int)($custStats->valid_phones ?? 0);
            $missingPhones    = max($totalCustomers - $validPhones, 0);
            $pranpcCount      = (int)($custStats->pranpc_count ?? 0);
            $pranpcPiutang    = (float)($custStats->pranpc_piutang ?? 0);
            $unvisitedCount   = (int)($custStats->unvisited_count ?? 0);
            $revisitCount     = (int)($custStats->revisit_count ?? 0);
            $criticalCount    = (int)($custStats->critical_count ?? 0);
            $highCount        = (int)($custStats->high_count ?? 0);
            $mediumCount      = (int)($custStats->medium_count ?? 0);
            $lowCount         = (int)($custStats->low_count ?? 0);

            // 2. Single-pass Aggregate untuk Visit Metrics (1 Query)
            $visitStats = DB::table('visits')->selectRaw("
                COUNT(*) as total_visits,
                SUM(CASE WHEN is_ptp = 1 THEN 1 ELSE 0 END) as total_ptp,
                SUM(CASE WHEN DATE(tanggal_input) = ? THEN 1 ELSE 0 END) as today_visits,
                SUM(CASE WHEN DATE(tanggal_input) = ? AND is_ptp = 1 THEN 1 ELSE 0 END) as today_ptp
            ", [$today, $today])->first();

            $totalVisits = (int)($visitStats->total_visits ?? 0);
            $totalPtp    = (int)($visitStats->total_ptp ?? 0);
            $todayVisits = (int)($visitStats->today_visits ?? 0);
            $todayPTP    = (int)($visitStats->today_ptp ?? 0);

            // 3. Single-pass Aggregate untuk Caring Metrics (1 Query)
            $caringStats = DB::table('caring_logs')->selectRaw("
                COUNT(*) as total_caring,
                SUM(CASE WHEN status_bayar = 'PAID' THEN 1 ELSE 0 END) as total_paid
            ")->first();

            $totalCaring = (int)($caringStats->total_caring ?? 0);
            $totalPaid   = (int)($caringStats->total_paid ?? 0);

            // 4. Combined 14-Day Trend Query for Visits & PTP (1 Query)
            $trendRaw = DB::table('visits')
                ->whereDate('tanggal_input', '>=', $rangeStart)
                ->selectRaw("
                    DATE(tanggal_input) as tgl,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_ptp = 1 THEN 1 ELSE 0 END) as ptp_total
                ")
                ->groupBy(DB::raw("DATE(tanggal_input)"))
                ->get()
                ->keyBy('tgl');

            $chartLabels = [];
            $chartVisits = [];
            $chartPtp    = [];

            $startCarbon = Carbon::today()->subDays(13);
            for ($i = 0; $i < 14; $i++) {
                $date          = $startCarbon->copy()->addDays($i);
                $key           = $date->format('Y-m-d');
                $chartLabels[] = $date->format('d/m');
                $chartVisits[] = (int)($trendRaw[$key]->total ?? 0);
                $chartPtp[]    = (int)($trendRaw[$key]->ptp_total ?? 0);
            }

            return compact(
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
                'chartPtp'
            );
        });

        // 4. Top Master AR Agents (Query langsung agar model selalu fully hydrated)
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

        return view('dashboard', array_merge($dashboardData, compact(
            'topAgents',
            'topOutstanding',
            'latestVisits',
            'latestCaring'
        )));
    }
}
