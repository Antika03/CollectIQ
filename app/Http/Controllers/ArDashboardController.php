<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\ArAgent;
use App\Models\CaringLog;
use Carbon\Carbon;

class ArDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Tentukan AR Agent yang aktif
        $agent = null;
        if ($user->isAdmin() && $request->filled('agent_id')) {
            $agent = ArAgent::find($request->agent_id);
        } elseif ($user->isAr() && $user->ar_agent_id) {
            $agent = ArAgent::find($user->ar_agent_id);
        } elseif ($user->isAr()) {
            // Fallback cari berdasarkan nama user
            $agent = ArAgent::where('name', 'like', "%{$user->name}%")->first();
        }

        // Jika admin dan tidak memilih agen, default agen pertama dengan visit terbanyak
        if (!$agent && $user->isAdmin()) {
            $agent = ArAgent::withCount('visits')->orderByDesc('visits_count')->first();
        }

        $allAgents = ArAgent::where('is_active', true)->orderBy('name')->get();

        if (!$agent) {
            return view('ar.dashboard', [
                'agent'               => null,
                'allAgents'           => $allAgents,
                'totalCustomers'      => 0,
                'totalOutstanding'    => 0,
                'unvisitedCount'      => 0,
                'revisitCount'        => 0,
                'activePtpCount'      => 0,
                'highRiskCount'       => 0,
                'pranpcCount'         => 0,
                'totalVisits'         => 0,
                'totalPaid'           => 0,
                'assignedCustomers'   => collect(),
                'recentVisits'        => collect(),
                'recentCaring'        => collect(),
            ]);
        }

        // Query aggregate KPIs untuk AR ini (Cached 3 menit)
        $arKpis = \Illuminate\Support\Facades\Cache::remember('ar_dash_kpis_' . $agent->id, 180, function () use ($agent) {
            $fourteenDaysAgo = now()->subDays(14)->toDateTimeString();

            $custRow = \Illuminate\Support\Facades\DB::table('customers')
                ->where(function ($q) use ($agent) {
                    $q->where('assigned_ar_agent_id', $agent->id)
                      ->orWhereExists(function ($sq) use ($agent) {
                          $sq->select(\Illuminate\Support\Facades\DB::raw(1))
                             ->from('visits')
                             ->whereColumn('visits.customer_id', 'customers.id')
                             ->where('visits.ar_agent_id', $agent->id);
                      });
                })
                ->selectRaw("
                    COUNT(*) as total_customers,
                    COALESCE(SUM(saldo_piutang), 0) as total_outstanding,
                    SUM(CASE WHEN last_visit_at IS NULL AND saldo_piutang > 0 THEN 1 ELSE 0 END) as unvisited_count,
                    SUM(CASE WHEN last_visit_at IS NOT NULL AND last_visit_at <= ? AND saldo_piutang > 0 THEN 1 ELSE 0 END) as revisit_count,
                    SUM(CASE WHEN LOWER(risk_level) IN ('high', 'critical') THEN 1 ELSE 0 END) as high_risk_count,
                    SUM(CASE WHEN is_pranpc = 1 THEN 1 ELSE 0 END) as pranpc_count
                ", [$fourteenDaysAgo])
                ->first();

            $visitRow = \Illuminate\Support\Facades\DB::table('visits')
                ->where('ar_agent_id', $agent->id)
                ->selectRaw("
                    COUNT(*) as total_visits,
                    SUM(CASE WHEN is_ptp = 1 THEN 1 ELSE 0 END) as active_ptp
                ")
                ->first();

            $totalPaid = \App\Models\CaringLog::where('ar_agent_id', $agent->id)
                ->where('status_bayar', 'PAID')
                ->count();

            return [
                'totalCustomers'   => (int)($custRow->total_customers ?? 0),
                'totalOutstanding' => (float)($custRow->total_outstanding ?? 0),
                'unvisitedCount'   => (int)($custRow->unvisited_count ?? 0),
                'revisitCount'     => (int)($custRow->revisit_count ?? 0),
                'highRiskCount'    => (int)($custRow->high_risk_count ?? 0),
                'pranpcCount'      => (int)($custRow->pranpc_count ?? 0),
                'totalVisits'      => (int)($visitRow->total_visits ?? 0),
                'activePtpCount'   => (int)($visitRow->active_ptp ?? 0),
                'totalPaid'        => (int)$totalPaid,
            ];
        });

        $totalCustomers   = $arKpis['totalCustomers'];
        $totalOutstanding = $arKpis['totalOutstanding'];
        $unvisitedCount   = $arKpis['unvisitedCount'];
        $revisitCount     = $arKpis['revisitCount'];
        $highRiskCount    = $arKpis['highRiskCount'];
        $pranpcCount      = $arKpis['pranpcCount'];
        $totalVisits      = $arKpis['totalVisits'];
        $activePtpCount   = $arKpis['activePtpCount'];
        $totalPaid        = $arKpis['totalPaid'];

        $customersQuery = Customer::where(function ($q) use ($agent) {
            $q->where('assigned_ar_agent_id', $agent->id)
              ->orWhereHas('visits', function ($vq) use ($agent) {
                  $vq->where('ar_agent_id', $agent->id);
              });
        });

        $assignedCustomers = (clone $customersQuery)
            ->withCount('visits')
            ->orderByDesc('saldo_piutang')
            ->take(10)
            ->get();

        $recentVisits = Visit::where('ar_agent_id', $agent->id)
            ->with('customer')
            ->latest('tanggal_input')
            ->take(8)
            ->get();

        $recentCaring = CaringLog::where('ar_agent_id', $agent->id)
            ->with('customer')
            ->latest('tanggal_caring')
            ->take(8)
            ->get();

        return view('ar.dashboard', compact(
            'agent',
            'allAgents',
            'totalCustomers',
            'totalOutstanding',
            'unvisitedCount',
            'revisitCount',
            'activePtpCount',
            'highRiskCount',
            'pranpcCount',
            'totalVisits',
            'totalPaid',
            'assignedCustomers',
            'recentVisits',
            'recentCaring'
        ));
    }
}
