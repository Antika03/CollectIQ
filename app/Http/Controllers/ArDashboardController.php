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

        // Query data untuk AR ini
        $customersQuery = Customer::where(function ($q) use ($agent) {
            $q->where('assigned_ar_agent_id', $agent->id)
              ->orWhereHas('visits', function ($vq) use ($agent) {
                  $vq->where('ar_agent_id', $agent->id);
              });
        });

        $totalCustomers   = (clone $customersQuery)->count();
        $totalOutstanding = (clone $customersQuery)->sum('saldo_piutang');
        $unvisitedCount   = (clone $customersQuery)->whereNull('last_visit_at')->where('saldo_piutang', '>', 0)->count();
        $revisitCount     = (clone $customersQuery)->whereNotNull('last_visit_at')->where('last_visit_at', '<=', now()->subDays(14))->where('saldo_piutang', '>', 0)->count();
        $highRiskCount    = (clone $customersQuery)->whereIn('risk_level', ['high', 'critical'])->count();
        $pranpcCount      = (clone $customersQuery)->where('is_pranpc', true)->count();

        $totalVisits      = Visit::where('ar_agent_id', $agent->id)->count();
        $activePtpCount   = Visit::where('ar_agent_id', $agent->id)->where('is_ptp', true)->count();
        $totalPaid        = CaringLog::where('ar_agent_id', $agent->id)->where('status_bayar', 'PAID')->count();

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
