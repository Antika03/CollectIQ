<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ArAgent;
use App\Services\ChurnRiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');

        $customers = Customer::query();

        // Jika user login sebagai AR, batasi hanya ke data pelanggan miliknya
        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $customers->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        } elseif ($request->filled('ar_agent_id')) {
            $arId = $request->ar_agent_id;
            $customers->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        }

        if ($search) {
            $customers->where(function ($query) use ($search) {
                $query->where('nomor_internet', 'like', "%{$search}%")
                    ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                    ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                    ->orWhere('datel', 'like', "%{$search}%")
                    ->orWhere('sto', 'like', "%{$search}%")
                    ->orWhere('ncli', 'like', "%{$search}%")
                    ->orWhere('nama_layanan_internet', 'like', "%{$search}%");
            });
        }

        if ($request->filled('risk_level')) {
            $customers->where('risk_level', strtolower($request->risk_level));
        }

        if ($request->filled('has_piutang')) {
            if ($request->has_piutang === '1') {
                $customers->where('saldo_piutang', '>', 0);
            } else {
                $customers->where('saldo_piutang', '<=', 0);
            }
        }

        if ($request->filled('is_pranpc')) {
            if ($request->is_pranpc === '1') {
                $customers->where('is_pranpc', true);
            } else {
                $customers->where('is_pranpc', false);
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'id');
        if ($sortBy === 'saldo_piutang') {
            $customers->orderByDesc('saldo_piutang');
        } elseif ($sortBy === 'nama_pelanggan') {
            $customers->orderBy('nama_pelanggan');
        } else {
            $customers->latest('id');
        }

        $customers = $customers
            ->with(['assignedArAgent'])
            ->withCount('visits')
            ->paginate(25)
            ->withQueryString();

        $cacheKey = ($user && $user->isAr() && $user->ar_agent_id) 
            ? 'cust_kpis_ar_' . $user->ar_agent_id 
            : 'cust_kpis_admin';

        $kpiData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($user) {
            $baseCountQuery = Customer::query();
            if ($user && $user->isAr() && $user->ar_agent_id) {
                $arId = $user->ar_agent_id;
                $baseCountQuery->where(function ($q) use ($arId) {
                    $q->where('assigned_ar_agent_id', $arId)
                      ->orWhereHas('visits', function ($vq) use ($arId) {
                          $vq->where('ar_agent_id', $arId);
                      });
                });
            }

            return [
                'totalCustomers' => (clone $baseCountQuery)->count(),
                'highRiskCount'  => (clone $baseCountQuery)->whereIn('risk_level', ['high', 'critical'])->count(),
                'activePtpCount' => (clone $baseCountQuery)->where('pending_ptp_count', '>', 0)->count(),
                'pranpcCount'    => (clone $baseCountQuery)->where('is_pranpc', true)->count(),
                'totalPiutang'   => (clone $baseCountQuery)->sum('saldo_piutang'),
                'staleCount'     => (clone $baseCountQuery)->where(function ($q) {
                    $q->whereNull('last_visit_at')
                      ->orWhere('last_visit_at', '<=', now()->subDays(14));
                })->count(),
            ];
        });

        $totalCustomers = $kpiData['totalCustomers'];
        $highRiskCount  = $kpiData['highRiskCount'];
        $activePtpCount = $kpiData['activePtpCount'];
        $pranpcCount    = $kpiData['pranpcCount'];
        $totalPiutang   = $kpiData['totalPiutang'];
        $staleCount     = $kpiData['staleCount'];

        $allAgents = ArAgent::where('is_active', true)->orderBy('name')->get();

        return view('customers.index', [
            'customers'      => $customers,
            'search'         => $search,
            'totalCustomers' => $totalCustomers,
            'highRiskCount'  => $highRiskCount,
            'activePtpCount' => $activePtpCount,
            'pranpcCount'    => $pranpcCount,
            'totalPiutang'   => $totalPiutang,
            'staleCount'     => $staleCount,
            'allAgents'      => $allAgents,
        ]);
    }

    public function show(Customer $customer)
    {
        $user = Auth::user();

        // Otorisasi AR: jika user AR, pastikan customer adalah tanggung jawabnya
        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $isAssigned = ($customer->assigned_ar_agent_id === $arId);
            $hasVisited = $customer->visits()->where('ar_agent_id', $arId)->exists();

            if (!$isAssigned && !$hasVisited) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melihat detail pelanggan milik AR lain.');
            }
        }

        $customer->load([
            'assignedArAgent',
            'visits.arAgent',
            'caringLogs',
            'viseeproData',
        ]);

        $churnEval = ChurnRiskService::evaluateCustomer($customer);
        $allAgents = ArAgent::where('is_active', true)->orderBy('name')->get();

        return view('customers.show', compact('customer', 'churnEval', 'allAgents'));
    }

    /**
     * Export data customer ke CSV (mengikuti filter aktif).
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $query = Customer::query();

        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $query->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%")
                  ->orWhere('datel', 'like', "%{$search}%")
                  ->orWhere('ncli', 'like', "%{$search}%");
            });
        }
        if ($request->filled('risk_level')) {
            $query->where('risk_level', strtolower($request->risk_level));
        }
        if ($request->filled('has_piutang')) {
            if ($request->has_piutang === '1') {
                $query->where('saldo_piutang', '>', 0);
            } else {
                $query->where('saldo_piutang', '<=', 0);
            }
        }
        if ($request->filled('is_pranpc')) {
            if ($request->is_pranpc === '1') {
                $query->where('is_pranpc', true);
            } else {
                $query->where('is_pranpc', false);
            }
        }

        $filename = 'export-customers-' . now()->format('Ymd_His') . '.csv';

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
                'NCLI',
                'Nama Pelanggan',
                'No HP Terbaru',
                'Email',
                'Layanan',
                'Kategori Tagihan',
                'Status PRANPC',
                'AR Penanggung Jawab',
                'Datel',
                'STO',
                'Alamat',
                'Saldo Piutang (Rp)',
                'Umur Customer / Aging',
                'Risk Level',
                'Total Visit',
                'Visit Terakhir',
            ]);

            $query->with('assignedArAgent')->chunk(200, function ($customers) use ($handle) {
                foreach ($customers as $c) {
                    fputcsv($handle, [
                        $c->id,
                        $c->nomor_internet ?? '',
                        $c->ncli ?? '',
                        $c->nama_pelanggan ?? '',
                        $c->no_hp_terbaru ?? '',
                        $c->email ?? '',
                        $c->nama_layanan_internet ?? '',
                        $c->bill_category ?? 'Eksisting',
                        $c->is_pranpc ? 'PRANPC' : 'NON-PRANPC',
                        $c->assignedArAgent ? $c->assignedArAgent->name : '-',
                        $c->datel ?? '',
                        $c->sto ?? '',
                        $c->alamat ?? '',
                        $c->saldo_piutang ?? 0,
                        $c->umur_customer ?? '',
                        strtoupper($c->risk_level ?? 'LOW'),
                        $c->total_visits ?? 0,
                        $c->last_visit_at ? $c->last_visit_at->format('Y-m-d') : '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
