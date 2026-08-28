<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Visit;
use App\Models\ArAgent;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    /**
     * Halaman Utama Reminder Center
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Ambil list AR Agents untuk dropdown filter
        $arAgents = ArAgent::where('is_active', true)->orderBy('name')->get();

        // 2. Base Query Customer dengan eager loading
        $query = Customer::with(['assignedArAgent', 'visits' => function ($vq) {
            $vq->latest('tanggal_input')->latest('id');
        }]);

        // Pembatasan jika user login sebagai AR
        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $query->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        } elseif ($request->filled('ar_agent_id')) {
            $arId = $request->ar_agent_id;
            $query->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        }

        // Search Pelanggan / Nomor Internet
        if ($request->filled('search')) {
            $search = trim((string)$request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%");
            });
        }

        // Filter Kategori Reminder
        $category = $request->input('category', 'all');
        if ($category === 'unvisited') {
            $query->whereNull('last_visit_at')->where('saldo_piutang', '>', 0);
        } elseif ($category === 'revisit') {
            $query->whereNotNull('last_visit_at')
                  ->where('last_visit_at', '<=', now()->subDays(14))
                  ->where('saldo_piutang', '>', 0);
        } elseif ($category === 'ptp') {
            $query->whereHas('visits', function ($vq) {
                $vq->where('is_ptp', true);
            });
        } elseif ($category === 'ptp_overdue') {
            $query->whereHas('visits', function ($vq) {
                $vq->where('is_ptp', true)->where('tanggal_input', '<=', now()->subDays(7));
            });
        } elseif ($category === 'outstanding') {
            $query->where('saldo_piutang', '>=', 500000);
        } elseif ($category === 'follow_up') {
            $query->whereHas('visits', function ($vq) {
                $vq->where(function ($sq) {
                    $sq->where('hasil_visit', 'like', '%cabut%')
                      ->orWhere('hasil_visit', 'like', '%komplain%')
                      ->orWhere('hasil_visit', 'like', '%janji%')
                      ->orWhere('kategori_visit', 'like', '%follow%')
                      ->orWhere('kategori_visit', 'like', '%jb%');
                });
            });
        } else {
            // Default 'all': Tampilkan yang memiliki saldo piutang > 0 atau ada visit
            $query->where(function ($q) {
                $q->where('saldo_piutang', '>', 0)
                  ->orWhereNotNull('last_visit_at');
            });
        }

        // Filter Tanggal Visit Terakhir
        if ($request->filled('date_from')) {
            $query->whereDate('last_visit_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('last_visit_at', '<=', $request->date_to);
        }

        // Filter Prioritas (berdasarkan saldo & risk)
        $priorityFilter = $request->input('priority');
        if ($priorityFilter === 'high') {
            $query->where(function ($q) {
                $q->where('saldo_piutang', '>=', 750000)
                  ->orWhereIn('risk_level', ['high', 'critical']);
            });
        } elseif ($priorityFilter === 'medium') {
            $query->where('saldo_piutang', '>=', 250000)
                  ->where('saldo_piutang', '<', 750000);
        } elseif ($priorityFilter === 'low') {
            $query->where('saldo_piutang', '<', 250000);
        }

        // Sorting: Prioritaskan saldo piutang terbesar lalu tanggal visit terbaru
        $reminders = $query->orderByDesc('saldo_piutang')
            ->latest('last_visit_at')
            ->paginate(20)
            ->withQueryString();

        // Siapkan atribut reminder_data pada setiap item customer
        $reminders->getCollection()->each(function ($c) {
            $this->formatReminderRow($c);
        });

        // 3. Ringkasan KPI Kategori Reminder
        $baseKpiQuery = Customer::query();
        if ($user && $user->isAr() && $user->ar_agent_id) {
            $arId = $user->ar_agent_id;
            $baseKpiQuery->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', function ($vq) use ($arId) {
                      $vq->where('ar_agent_id', $arId);
                  });
            });
        }

        $totalUnvisited = (clone $baseKpiQuery)->whereNull('last_visit_at')->where('saldo_piutang', '>', 0)->count();
        $totalRevisit   = (clone $baseKpiQuery)->whereNotNull('last_visit_at')->where('last_visit_at', '<=', now()->subDays(14))->where('saldo_piutang', '>', 0)->count();
        $totalPtp       = (clone $baseKpiQuery)->whereHas('visits', fn($vq) => $vq->where('is_ptp', true))->count();
        $totalHighRisk  = (clone $baseKpiQuery)->whereIn('risk_level', ['high', 'critical'])->count();

        return view('reminders.index', compact(
            'reminders',
            'arAgents',
            'category',
            'priorityFilter',
            'totalUnvisited',
            'totalRevisit',
            'totalPtp',
            'totalHighRisk'
        ));
    }

    /**
     * AJAX: Generate Preview Reminder Text untuk satu customer / visit
     */
    public function preview(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::with(['assignedArAgent', 'visits' => function ($vq) {
            $vq->latest('tanggal_input')->latest('id');
        }])->find($request->customer_id);

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.'], 404);
        }

        $row = $this->formatReminderRow($customer);
        $message = $this->buildReminderMessage($row);

        return response()->json([
            'success'   => true,
            'customer'  => [
                'id'              => $customer->id,
                'nama_pelanggan'  => $customer->nama_pelanggan,
                'nomor_internet'  => $customer->nomor_internet,
                'ar_name'         => $row['ar_name'],
                'ar_has_chat_id'  => !empty($customer->assignedArAgent?->chat_id_telegram),
                'chat_id'         => $customer->assignedArAgent?->chat_id_telegram,
                'saldo'           => $customer->saldo_piutang,
                'saldo_formatted' => 'Rp ' . number_format($customer->saldo_piutang, 0, ',', '.'),
                'priority'        => $row['priority'],
                'recommendation'  => $row['recommendation'],
            ],
            'message'   => $message,
        ]);
    }

    /**
     * Export Reminder Center ke CSV (mengikuti filter aktif)
     */
    public function export(Request $request)
    {
        $query = Customer::with(['assignedArAgent', 'visits' => function ($vq) {
            $vq->latest('tanggal_input')->latest('id');
        }]);

        if ($request->filled('ar_agent_id')) {
            $arId = $request->ar_agent_id;
            $query->where(function ($q) use ($arId) {
                $q->where('assigned_ar_agent_id', $arId)
                  ->orWhereHas('visits', fn($vq) => $vq->where('ar_agent_id', $arId));
            });
        }

        if ($request->filled('search')) {
            $search = trim((string)$request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('no_hp_terbaru', 'like', "%{$search}%");
            });
        }

        $category = $request->input('category', 'all');
        if ($category === 'unvisited') {
            $query->whereNull('last_visit_at')->where('saldo_piutang', '>', 0);
        } elseif ($category === 'revisit') {
            $query->whereNotNull('last_visit_at')
                  ->where('last_visit_at', '<=', now()->subDays(14))
                  ->where('saldo_piutang', '>', 0);
        } elseif ($category === 'ptp') {
            $query->whereHas('visits', fn($vq) => $vq->where('is_ptp', true));
        } elseif ($category === 'ptp_overdue') {
            $query->whereHas('visits', fn($vq) => $vq->where('is_ptp', true)->where('tanggal_input', '<=', now()->subDays(7)));
        } elseif ($category === 'outstanding') {
            $query->where('saldo_piutang', '>=', 500000);
        } elseif ($category === 'follow_up') {
            $query->whereHas('visits', function ($vq) {
                $vq->where(function ($sq) {
                    $sq->where('hasil_visit', 'like', '%cabut%')
                      ->orWhere('hasil_visit', 'like', '%komplain%')
                      ->orWhere('hasil_visit', 'like', '%janji%')
                      ->orWhere('kategori_visit', 'like', '%follow%')
                      ->orWhere('kategori_visit', 'like', '%jb%');
                });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('last_visit_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('last_visit_at', '<=', $request->date_to);
        }

        $filename = 'export-reminder-center-' . now()->format('Ymd_His') . '.csv';

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
                'ID Pelanggan',
                'Nomor Internet',
                'Nama Pelanggan',
                'AR Agent',
                'Saldo Piutang (Rp)',
                'Tanggal Visit Terakhir',
                'Hasil Visit Terakhir',
                'Kategori Visit',
                'Keterangan',
                'Prioritas',
                'Rekomendasi Tindakan',
            ]);

            $query->orderByDesc('saldo_piutang')->chunk(150, function ($customers) use ($handle) {
                foreach ($customers as $c) {
                    $row = $this->formatReminderRow($c);
                    fputcsv($handle, [
                        $c->id,
                        $c->nomor_internet ?? '',
                        $c->nama_pelanggan ?? '',
                        $row['ar_name'],
                        $c->saldo_piutang ?? 0,
                        $row['last_visit_date_formatted'],
                        $row['hasil_visit'],
                        $row['kategori_visit'],
                        $row['keterangan_visit'],
                        $row['priority'],
                        $row['recommendation'],
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Helper: Format baris data reminder & kalkulasi prioritas
     */
    private function formatReminderRow(Customer $customer): array
    {
        $latestVisit = $customer->visits->first();
        $arName = $customer->assignedArAgent?->name 
            ?? $latestVisit?->arAgent?->name 
            ?? 'Belum Ditugaskan';

        $saldo = (float)($customer->saldo_piutang ?? 0);
        $lastVisitDate = $customer->last_visit_at;
        $isPtp = (bool)($latestVisit?->is_ptp ?? false);
        $riskLevel = strtolower($customer->risk_level ?? 'low');

        // Prioritas & Rekomendasi
        $priority = 'Rendah';
        $priorityClass = 'bg-success-soft text-success';
        $recommendation = 'Monitoring berkala.';

        if (!$lastVisitDate && $saldo > 0) {
            $priority = ($saldo >= 500000) ? 'Tinggi' : 'Sedang';
            $recommendation = 'Pelanggan belum pernah divisit lapangan. Jadwalkan penagihan awal.';
        } elseif ($isPtp) {
            $priority = 'Tinggi';
            $recommendation = 'Follow up kepastian pembayaran janji bayar (PTP).';
        } elseif ($lastVisitDate && $lastVisitDate->lte(now()->subDays(14)) && $saldo > 0) {
            $priority = ($saldo >= 500000 || in_array($riskLevel, ['high', 'critical'])) ? 'Tinggi' : 'Sedang';
            $recommendation = 'Kunjungan terakhir >14 hari lalu. Lakukan kunjungan visit ulang.';
        } elseif ($saldo >= 1000000 || in_array($riskLevel, ['high', 'critical'])) {
            $priority = 'Tinggi';
            $recommendation = 'Saldo piutang / risiko churn tinggi. Prioritaskan penanganan AR.';
        } elseif ($saldo > 0) {
            $priority = 'Sedang';
            $recommendation = 'Lakukan koordinasi penagihan dan caring via telepon/WhatsApp.';
        }

        if ($priority === 'Tinggi') {
            $priorityClass = 'bg-danger-soft text-danger';
        } elseif ($priority === 'Sedang') {
            $priorityClass = 'bg-warning-soft text-warning';
        }

        $customer->reminder_data = [
            'ar_name'                    => $arName,
            'last_visit_date_formatted'  => $lastVisitDate ? $lastVisitDate->format('d/m/Y') : 'Belum Pernah',
            'hasil_visit'                => $latestVisit?->hasil_visit ?: ($lastVisitDate ? 'Visit Terjadwal' : 'Belum Ada Visit'),
            'kategori_visit'             => $latestVisit?->kategori_visit ?: '-',
            'keterangan_visit'           => $latestVisit?->keterangan_visit ?: '-',
            'priority'                   => $priority,
            'priority_class'             => $priorityClass,
            'recommendation'             => $recommendation,
        ];

        return [
            'customer_id'                => $customer->id,
            'nomor_internet'             => $customer->nomor_internet,
            'nama_pelanggan'             => $customer->nama_pelanggan,
            'ar_name'                    => $arName,
            'saldo'                      => $saldo,
            'last_visit_date_formatted'  => $lastVisitDate ? $lastVisitDate->format('d/m/Y') : 'Belum Pernah',
            'hasil_visit'                => $latestVisit?->hasil_visit ?: ($lastVisitDate ? 'Visit Terjadwal' : 'Belum Ada Visit'),
            'kategori_visit'             => $latestVisit?->kategori_visit ?: '-',
            'keterangan_visit'           => $latestVisit?->keterangan_visit ?: '-',
            'priority'                   => $priority,
            'priority_class'             => $priorityClass,
            'recommendation'             => $recommendation,
        ];
    }

    /**
     * Helper: Format pesan preview reminder siap kirim / copy
     */
    private function buildReminderMessage(array $row): string
    {
        $saldoFormatted = 'Rp ' . number_format($row['saldo'], 0, ',', '.');
        $arUpper = strtoupper($row['ar_name']);

        $msg  = "REMINDER COLLECTION\n";
        $msg .= "AR: {$arUpper}\n\n";
        $msg .= "Pelanggan: {$row['nama_pelanggan']}\n";
        $msg .= "No. Internet: {$row['nomor_internet']}\n";
        $msg .= "Tanggal Visit: {$row['last_visit_date_formatted']}\n";
        $msg .= "Hasil: {$row['hasil_visit']}\n";
        $msg .= "Kategori: {$row['kategori_visit']}\n";
        $msg .= "Keterangan: {$row['keterangan_visit']}\n";
        $msg .= "Saldo: {$saldoFormatted}\n";
        $msg .= "Prioritas: {$row['priority']}\n\n";
        $msg .= "Rekomendasi:\n{$row['recommendation']}\n\n";
        $msg .= "--- CollectIQ Telkom Intelligence ---";

        return $msg;
    }
}
