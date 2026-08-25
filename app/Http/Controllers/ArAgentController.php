<?php

namespace App\Http\Controllers;

use App\Models\ArAgent;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArAgentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = ArAgent::query()
            ->withCount([
                'visits',
                'visits as ptp_count' => function ($q) {
                    $q->where('is_ptp', true);
                },
                'visits as contacted_count' => function ($q) {
                    $q->whereNotNull('hasil_visit')
                      ->where('hasil_visit', '!=', '')
                      ->where('hasil_visit', '!=', 'Belum Diisi')
                      ->where('hasil_visit', '!=', '-');
                },
            ]);

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $agents = $query->orderByDesc('visits_count')
            ->paginate(15)
            ->withQueryString();

        // Hitung metrik customer unik per agent
        $agentIds = $agents->pluck('id')->toArray();
        $uniqueCustomers = Visit::whereIn('ar_agent_id', $agentIds)
            ->select('ar_agent_id', DB::raw('COUNT(DISTINCT customer_id) as total_customers'))
            ->groupBy('ar_agent_id')
            ->pluck('total_customers', 'ar_agent_id')
            ->toArray();

        $agents->each(function ($agent) use ($uniqueCustomers) {
            $agent->unique_customers = $uniqueCustomers[$agent->id] ?? 0;
            $agent->ptp_rate = $agent->visits_count > 0
                ? round(($agent->ptp_count / $agent->visits_count) * 100, 1)
                : 0;
            $agent->contacted_rate = $agent->visits_count > 0
                ? round(($agent->contacted_count / $agent->visits_count) * 100, 1)
                : 0;
        });

        // Summary KPI
        $totalAgents    = ArAgent::count();
        $activeAgents   = ArAgent::where('is_active', true)->count();
        $totalVisits    = Visit::count();
        $totalPtp       = Visit::where('is_ptp', true)->count();

        return view('ar-agents.index', compact(
            'agents',
            'search',
            'totalAgents',
            'activeAgents',
            'totalVisits',
            'totalPtp'
        ));
    }

    /**
     * Export data AR Agents ke CSV.
     */
    public function export(Request $request)
    {
        $agents = ArAgent::withCount([
            'visits',
            'visits as ptp_count' => function ($q) {
                $q->where('is_ptp', true);
            },
            'visits as contacted_count' => function ($q) {
                $q->whereNotNull('hasil_visit')
                  ->where('hasil_visit', '!=', '')
                  ->where('hasil_visit', '!=', 'Belum Diisi')
                  ->where('hasil_visit', '!=', '-');
            },
        ])->orderByDesc('visits_count')->get();

        $filename = 'export-ar-agents-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($agents) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Nama AR Agent',
                'Status Aktif',
                'Telegram Chat ID',
                'Total Kunjungan (Visits)',
                'Total PTP',
                'PTP Rate (%)',
                'Contacted Count',
                'Contacted Rate (%)',
            ]);

            foreach ($agents as $a) {
                $ptpRate = $a->visits_count > 0 ? round(($a->ptp_count / $a->visits_count) * 100, 1) : 0;
                $contactedRate = $a->visits_count > 0 ? round(($a->contacted_count / $a->visits_count) * 100, 1) : 0;

                fputcsv($handle, [
                    $a->id,
                    $a->name,
                    $a->is_active ? 'AKTIF' : 'NON-AKTIF',
                    $a->chat_id_telegram ?? '-',
                    $a->visits_count,
                    $a->ptp_count,
                    $ptpRate . '%',
                    $a->contacted_count,
                    $contactedRate . '%',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}