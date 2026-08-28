<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\ArAgent;
use App\Exports\VisitsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class VisitController extends Controller
{
    /**
     * Menampilkan daftar visit dengan filter, KPI, dan pagination.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Dropdown filter options (Cached 5 menit untuk akses super cepat)
        |--------------------------------------------------------------------------
        */
        $arAgents = \Illuminate\Support\Facades\Cache::remember('ar_agents_all', 300, function () {
            return ArAgent::orderBy('name')->get();
        });

        $filterOptions = \Illuminate\Support\Facades\Cache::remember('visit_filter_options', 300, function () {
            return [
                'hasil'    => Visit::whereNotNull('hasil_visit')->where('hasil_visit', '!=', '')->distinct()->orderBy('hasil_visit')->pluck('hasil_visit'),
                'kategori' => Visit::whereNotNull('kategori_visit')->where('kategori_visit', '!=', '')->distinct()->orderBy('kategori_visit')->pluck('kategori_visit'),
            ];
        });

        $hasilVisitOptions = $filterOptions['hasil'];
        $kategoriOptions   = $filterOptions['kategori'];

        /*
        |--------------------------------------------------------------------------
        | 2. KPI Metrics (Cached 60 detik) — Sumber: PRITI DATA Collection
        |--------------------------------------------------------------------------
        */
        $kpis = \Illuminate\Support\Facades\Cache::remember('visit_kpi_summary', 60, function () {
            $baseVisit = Visit::where('collect_id', 'not like', 'PRQ-%');

            $totalVisit       = (clone $baseVisit)->count();
            $visitHariIni     = (clone $baseVisit)->whereDate('tanggal_input', today())->count();
            $totalPtp         = (clone $baseVisit)->where('is_ptp', true)->count();
            $ptpHariIni       = (clone $baseVisit)->whereDate('tanggal_input', today())->where('is_ptp', true)->count();
            $contactedCount   = (clone $baseVisit)->whereNotNull('hasil_visit')
                                    ->where('hasil_visit', '!=', '')
                                    ->where('hasil_visit', '!=', 'Belum Diisi')
                                    ->where('hasil_visit', '!=', '-')
                                    ->count();
            $notContactedCount = (clone $baseVisit)->where(function ($q) {
                $q->whereNull('hasil_visit')
                  ->orWhere('hasil_visit', '')
                  ->orWhere('hasil_visit', 'Belum Diisi')
                  ->orWhere('hasil_visit', '-');
            })->count();

            return compact('totalVisit', 'visitHariIni', 'totalPtp', 'ptpHariIni', 'contactedCount', 'notContactedCount');
        });

        $totalVisit        = $kpis['totalVisit'];
        $visitHariIni      = $kpis['visitHariIni'];
        $totalPtp          = $kpis['totalPtp'];
        $ptpHariIni        = $kpis['ptpHariIni'];
        $contactedCount    = $kpis['contactedCount'];
        $notContactedCount = $kpis['notContactedCount'];

        /*
        |--------------------------------------------------------------------------
        | 3. Chart: Trend visit harian 14 hari terakhir (Cached 60 detik)
        |--------------------------------------------------------------------------
        */
        $chartData = \Illuminate\Support\Facades\Cache::remember('visit_chart_trend', 60, function () {
            $rangeStart = Carbon::today()->subDays(13);

            $dailyRaw = Visit::where('collect_id', 'not like', 'PRQ-%')
                ->whereDate('tanggal_input', '>=', $rangeStart)
                ->selectRaw('tanggal_input, COUNT(*) as total')
                ->groupBy('tanggal_input')
                ->pluck('total', 'tanggal_input')
                ->toArray();

            $dailyPtpRaw = Visit::where('collect_id', 'not like', 'PRQ-%')
                ->whereDate('tanggal_input', '>=', $rangeStart)
                ->where('is_ptp', true)
                ->selectRaw('tanggal_input, COUNT(*) as total')
                ->groupBy('tanggal_input')
                ->pluck('total', 'tanggal_input')
                ->toArray();

            $chartLabels  = [];
            $chartVisits  = [];
            $chartPtp     = [];

            for ($i = 0; $i < 14; $i++) {
                $date           = $rangeStart->copy()->addDays($i);
                $key            = $date->format('Y-m-d');
                $chartLabels[]  = $date->format('d/m');
                $chartVisits[]  = $dailyRaw[$key] ?? 0;
                $chartPtp[]     = $dailyPtpRaw[$key] ?? 0;
            }

            return compact('chartLabels', 'chartVisits', 'chartPtp');
        });

        $chartLabels = $chartData['chartLabels'];
        $chartVisits = $chartData['chartVisits'];
        $chartPtp    = $chartData['chartPtp'];

        /*
        |--------------------------------------------------------------------------
        | 4. Chart: Distribusi & AR stats (Cached 60 detik)
        |--------------------------------------------------------------------------
        */
        $extraStats = \Illuminate\Support\Facades\Cache::remember('visit_extra_stats', 60, function () {
            $hasilDistribution = Visit::where('collect_id', 'not like', 'PRQ-%')
                ->whereNotNull('hasil_visit')
                ->where('hasil_visit', '!=', '')
                ->selectRaw('hasil_visit, COUNT(*) as total')
                ->groupBy('hasil_visit')
                ->orderByDesc('total')
                ->take(8)
                ->get();

            $agentStats = ArAgent::withCount(['visits' => function ($vq) {
                $vq->where('collect_id', 'not like', 'PRQ-%');
            }])
                ->orderByDesc('visits_count')
                ->take(8)
                ->get();

            return compact('hasilDistribution', 'agentStats');
        });

        $hasilDistribution = $extraStats['hasilDistribution'];
        $agentStats        = $extraStats['agentStats'];

        /*
        |--------------------------------------------------------------------------
        | 5. Query utama Visit dengan filter (Single Source of Truth: PRITI Collection)
        |--------------------------------------------------------------------------
        */
        $query = Visit::with(['customer', 'arAgent'])
            ->where('collect_id', 'not like', 'PRQ-%')
            ->latest('tanggal_input')
            ->latest('id');

        // Filter: tanggal mulai
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_input', '>=', $request->date_from);
        }

        // Filter: tanggal sampai
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_input', '<=', $request->date_to);
        }

        // Filter: AR Agent
        if ($request->filled('ar_agent_id')) {
            $query->where('ar_agent_id', $request->ar_agent_id);
        }

        // Filter: hasil visit
        if ($request->filled('hasil_visit')) {
            $query->where('hasil_visit', $request->hasil_visit);
        }

        // Filter: kategori visit
        if ($request->filled('kategori_visit')) {
            $query->where('kategori_visit', $request->kategori_visit);
        }

        // Filter: status PTP
        if ($request->filled('is_ptp')) {
            $query->where('is_ptp', $request->is_ptp === '1');
        }

        // Filter: status contacted / not contacted
        if ($request->filled('status_contact')) {
            if ($request->status_contact === 'contacted') {
                $query->whereNotNull('hasil_visit')
                      ->where('hasil_visit', '!=', '')
                      ->where('hasil_visit', '!=', 'Belum Diisi')
                      ->where('hasil_visit', '!=', '-');
            } elseif ($request->status_contact === 'not_contacted') {
                $query->where(function ($q) {
                    $q->whereNull('hasil_visit')
                      ->orWhere('hasil_visit', '')
                      ->orWhere('hasil_visit', 'Belum Diisi')
                      ->orWhere('hasil_visit', '-');
                });
            }
        }

        // Search: nama pelanggan, nomor internet
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination — withQueryString() bawa filter saat pindah halaman
        |--------------------------------------------------------------------------
        */
        $visits = $query->paginate(20)->withQueryString();

        return view('visits.index', compact(
            'visits',
            'arAgents',
            'hasilVisitOptions',
            'kategoriOptions',
            'totalVisit',
            'visitHariIni',
            'totalPtp',
            'ptpHariIni',
            'contactedCount',
            'notContactedCount',
            'chartLabels',
            'chartVisits',
            'chartPtp',
            'hasilDistribution',
            'agentStats'
        ));
    }

    /**
     * AJAX: Menampilkan detail satu visit (JSON).
     */
    public function show(Visit $visit)
    {
        $visit->load(['customer', 'arAgent']);

        // Riwayat visit pelanggan yang sama
        $riwayat = Visit::where('customer_id', $visit->customer_id)
            ->where('id', '!=', $visit->id)
            ->with('arAgent')
            ->latest('tanggal_input')
            ->latest('id')
            ->take(5)
            ->get();

        return response()->json([
            'visit'   => [
                'id'                  => $visit->id,
                'tanggal_input'       => $visit->tanggal_input?->format('d M Y'),
                'hasil_visit'         => $visit->hasil_visit,
                'kategori_visit'      => $visit->kategori_visit,
                'keterangan_visit'    => $visit->keterangan_visit,
                'is_ptp'              => $visit->is_ptp,
                'no_hp_snapshot'      => $visit->no_hp_snapshot,
                'tipe_hunian_snapshot'=> $visit->tipe_hunian_snapshot,
                'foto_url'            => $visit->foto_url,
                'photo_preview'       => $visit->photo_preview,
                'drive_url'           => $visit->drive_url,
                'customer'            => $visit->customer ? [
                    'id'              => $visit->customer->id,
                    'nama_pelanggan'  => $visit->customer->nama_pelanggan,
                    'nomor_internet'  => $visit->customer->nomor_internet,
                    'risk_level'      => $visit->customer->risk_level,
                    'risk_score'      => $visit->customer->risk_score,
                    'no_hp_terbaru'   => $visit->customer->no_hp_terbaru,
                    'wa_url'          => $visit->customer->wa_url,
                ] : null,
                'ar_agent'            => $visit->arAgent ? [
                    'name' => $visit->arAgent->name,
                ] : null,
            ],
            'riwayat' => $riwayat->map(fn ($v) => [
                'id'           => $v->id,
                'tanggal_input'=> $v->tanggal_input?->format('d M Y'),
                'hasil_visit'  => $v->hasil_visit,
                'ar_agent'     => $v->arAgent?->name,
                'is_ptp'       => $v->is_ptp,
            ]),
        ]);
    }

    /**
     * Export data visit ke CSV (mengikuti filter aktif).
     */
    public function export(Request $request)
    {
        $query = Visit::with(['customer', 'arAgent'])
            ->where('collect_id', 'not like', 'PRQ-%')
            ->latest('tanggal_input')
            ->latest('id');

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_input', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_input', '<=', $request->date_to);
        }
        if ($request->filled('ar_agent_id')) {
            $query->where('ar_agent_id', $request->ar_agent_id);
        }
        if ($request->filled('hasil_visit')) {
            $query->where('hasil_visit', $request->hasil_visit);
        }
        if ($request->filled('kategori_visit')) {
            $query->where('kategori_visit', $request->kategori_visit);
        }
        if ($request->filled('is_ptp')) {
            $query->where('is_ptp', $request->is_ptp === '1');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%");
            });
        }

        $filename = 'export-visits-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Tanggal Input',
                'Nomor Internet',
                'Nama Pelanggan',
                'AR Agent',
                'Hasil Visit',
                'Kategori Visit',
                'Janji Bayar (PTP)',
                'No HP Snapshot',
                'Tipe Hunian',
                'Keterangan',
                'Foto URL',
            ]);

            $query->chunk(100, function ($visits) use ($handle) {
                foreach ($visits as $v) {
                    fputcsv($handle, [
                        $v->id,
                        $v->tanggal_input?->format('Y-m-d') ?? '',
                        $v->customer->nomor_internet ?? '',
                        $v->customer->nama_pelanggan ?? '',
                        $v->arAgent->name ?? '',
                        $v->hasil_visit ?? '',
                        $v->kategori_visit ?? '',
                        $v->is_ptp ? 'YA' : 'TIDAK',
                        $v->no_hp_snapshot ?? '',
                        $v->tipe_hunian_snapshot ?? '',
                        $v->keterangan_visit ?? '',
                        $v->foto_url ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Menampilkan foto visit dari Google Drive (ultra-robust proxy + caching lokal).
     */
    public function photo(Visit $visit)
    {
        $fileId = $visit->drive_file_id;

        if (!$fileId) {
            return $this->serveFallbackPhoto();
        }

        // 1. Cek local cache di storage/app/public/visit_cache/
        $cacheDir = storage_path('app/public/visit_cache');
        if (!File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0775, true, true);
        }

        $cacheFile = $cacheDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileId) . '.jpg';

        if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
            return response(file_get_contents($cacheFile), 200, [
                'Content-Type'  => 'image/jpeg',
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }

        // 2. Waterfall endpoints Google Drive
        $endpoints = [
            "https://drive.google.com/thumbnail?id={$fileId}&sz=w1200",
            "https://lh3.googleusercontent.com/d/{$fileId}",
            "https://drive.google.com/uc?export=view&id={$fileId}",
            "https://drive.google.com/uc?export=download&id={$fileId}",
        ];

        foreach ($endpoints as $url) {
            try {
                $response = Http::timeout(12)
                    ->withOptions(['allow_redirects' => true])
                    ->get($url);

                if ($response->successful()) {
                    $body = $response->body();
                    $contentType = $response->header('Content-Type');

                    // Validasi: pastikan benar gambar dan bukan halaman HTML peringatan
                    if (
                        strlen($body) > 1000 &&
                        (
                            ($contentType && str_starts_with($contentType, 'image/')) ||
                            str_starts_with($body, "\xFF\xD8\xFF") || // JPEG magic bytes
                            str_starts_with($body, "\x89PNG")       // PNG magic bytes
                        )
                    ) {
                        // Simpan cache lokal
                        @file_put_contents($cacheFile, $body);

                        return response($body, 200, [
                            'Content-Type'  => $contentType ?: 'image/jpeg',
                            'Cache-Control' => 'public, max-age=604800',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Lanjut ke endpoint berikutnya
            }
        }

        return $this->serveFallbackPhoto();
    }

    /**
     * Mengembalikan fallback SVG placeholder jika foto gagal diambil
     */
    private function serveFallbackPhoto()
    {
        $placeholderPath = public_path('images/photo-placeholder.svg');
        if (file_exists($placeholderPath)) {
            return response(file_get_contents($placeholderPath), 200, [
                'Content-Type'  => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return response(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 150" width="200" height="150"><rect width="200" height="150" fill="#f1f5f9"/><text x="100" y="80" font-family="sans-serif" font-size="12" fill="#94a3b8" text-anchor="middle">Foto Tidak Tersedia</text></svg>',
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }
}