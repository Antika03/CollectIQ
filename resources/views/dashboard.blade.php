@extends('layouts.app')

@section('title', 'Executive Command Center')
@section('subtitle', 'Monitoring performansi collection, pemulihan piutang, dan mitigasi risiko churn pelanggan Telkom')

@section('content')

{{-- 1. EXECUTIVE KPI SUMMARY --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Customer</div>
                <div class="kpi-value">{{ number_format($totalCustomers) }}</div>
                <div class="kpi-sub">Master Data C3MR PRITI</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Piutang (Outstanding)</div>
                <div class="kpi-value" style="font-size:22px; color:var(--danger); white-space:nowrap;">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
                <div class="kpi-sub">Saldo tertahan di pelanggan</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Kunjungan Visit</div>
                <div class="kpi-value">{{ number_format($totalVisits) }}</div>
                <div class="kpi-sub">Hari ini: <strong>+{{ $todayVisits }}</strong> kunjungan</div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#2563EB;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Janji Bayar (PTP)</div>
                <div class="kpi-value">{{ number_format($totalPtp) }}</div>
                <div class="kpi-sub">Hari ini: <strong>+{{ $todayPTP }}</strong> PTP</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>
</div>

{{-- 2. ACTION REQUIRED — CHURN RISK MATRIX --}}
<div class="card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="section-title">
                <i class="bi bi-lightning-charge-fill" style="color:var(--primary); margin-right:6px;"></i>
                Action Required — Prioritas Tindakan Collection &amp; Retensi
            </div>
            <div class="section-sub">Klasifikasi penanganan pelanggan berdasarkan Early Warning Churn Risk Indicator</div>
        </div>
        <a href="{{ url('/c3mr/performance') }}" class="btn btn-outline-telkom btn-sm">
            Lihat Analisis Detail <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--danger-soft); border:1px solid rgba(220,38,38,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--danger);">🔴 CRITICAL ACTION</span>
                    <span style="font-size:18px; font-weight:800; color:var(--danger);">{{ $criticalCount }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Permintaan cabut / tunggakan kritis. <strong>Intervensi winback segera.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--warning-soft); border:1px solid rgba(217,119,6,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--warning);">🟠 HIGH PRIORITY</span>
                    <span style="font-size:18px; font-weight:800; color:var(--warning);">{{ $highCount }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Broken PTP / saldo tinggi. <strong>Prioritas kunjungan visit AR.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:#FEFCE8; border:1px solid rgba(202,138,4,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:#A16207;">🟡 MEDIUM PRIORITY</span>
                    <span style="font-size:18px; font-weight:800; color:#A16207;">{{ $mediumCount }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Uncontacted / RNA. <strong>Caring ulang via kanal alternatif.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--success-soft); border:1px solid rgba(22,163,74,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--success);">🟢 LOW / ROUTINE</span>
                    <span style="font-size:18px; font-weight:800; color:var(--success);">{{ $lowCount }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Pelanggan lancar. <strong>Pelayanan reguler &amp; monitoring.</strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 3. TREND CHART & TOP AR AGENTS --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title"><i class="bi bi-graph-up" style="color:var(--primary); margin-right:6px;"></i> Trend Kunjungan Visit &amp; Janji Bayar (14 Hari Terakhir)</div>
                    <div class="section-sub">Aktivitas penagihan lapangan harian</div>
                </div>
            </div>
            <canvas id="chartTrend" height="145"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title"><i class="bi bi-award-fill" style="color:var(--warning); margin-right:6px;"></i> Top AR Agent</div>
                    <div class="section-sub">Produktivitas kunjungan visit</div>
                </div>
                <a href="{{ url('/ar-agents') }}" style="font-size:12px; color:var(--primary); font-weight:600; text-decoration:none;">Semua</a>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($topAgents as $idx => $agent)
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rank-pill rank-{{ $idx + 1 <= 3 ? ($idx + 1) : 'other' }}" style="width:22px; height:22px; font-size:11px;">
                                    {{ $idx + 1 }}
                                </span>
                                <span style="font-size:13px; font-weight:700; color:var(--ink-900);">{{ $agent->name }}</span>
                            </div>
                            <span style="font-size:12px; font-weight:700; color:var(--primary);">{{ $agent->visits_count }} Visit</span>
                        </div>
                        <div style="height:6px; background:var(--secondary); border-radius:99px; overflow:hidden;">
                            <div style="width:{{ $agent->contribution_percent }}%; height:100%; background:linear-gradient(90deg, var(--primary-light), var(--primary-dark)); border-radius:99px;"></div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-3">Belum ada data AR Agent.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- 4. RECENT ACTIVITIES: VISITS & OUTSTANDING --}}
<div class="row g-3">
    {{-- Top Outstanding Customers --}}
    <div class="col-lg-6">
        <div class="card h-100 p-0" style="overflow:hidden;">
            <div style="padding:14px 18px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                    <i class="bi bi-exclamation-circle-fill" style="color:var(--danger); margin-right:6px;"></i> Top Saldo Piutang Menunggak
                </div>
                <a href="{{ url('/piutang') }}" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>No Internet</th>
                            <th style="text-align:right;">Saldo Piutang</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topOutstanding as $c)
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:var(--ink-900);">{{ $c->nama_pelanggan }}</div>
                                    <div style="font-size:11px; color:var(--ink-400);">{{ $c->datel ?: 'Wilayah Telkom' }}</div>
                                </td>
                                <td><code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">{{ $c->nomor_internet }}</code></td>
                                <td style="text-align:right; font-weight:800; color:var(--danger); white-space:nowrap;">
                                    Rp {{ number_format($c->saldo_piutang, 0, ',', '.') }}
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ url('/customers/' . $c->id) }}" class="btn btn-sm btn-outline-telkom" style="font-size:11.5px; padding:3px 8px; white-space:nowrap;" title="Lihat Detail Pelanggan" data-bs-toggle="tooltip">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Visit Activity with Photos --}}
    <div class="col-lg-6">
        <div class="card h-100 p-0" style="overflow:hidden;">
            <div style="padding:14px 18px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                    <i class="bi bi-camera-fill" style="color:var(--primary); margin-right:6px;"></i> Kunjungan Visit Lapangan Terbaru
                </div>
                <a href="{{ url('/visits') }}" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="p-3">
                @foreach($latestVisits as $v)
                    <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border);">
                        @if($v->foto_url)
                            <img src="{{ route('visit.photo', ['visit' => $v->id]) }}"
                                 style="width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid var(--border);"
                                 onerror="this.src='{{ asset('images/photo-placeholder.svg') }}'"
                                 alt="Foto visit">
                        @else
                            <div style="width:42px; height:42px; border-radius:8px; background:var(--secondary); display:flex; align-items:center; justify-content:center; color:var(--ink-400); flex-shrink:0;">
                                <i class="bi bi-image"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-weight:700; font-size:13px; color:var(--ink-900); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ optional($v->customer)->nama_pelanggan ?? 'Pelanggan' }}
                                </span>
                                <span style="font-size:11px; color:var(--ink-400); white-space:nowrap; margin-left:8px;">
                                    {{ $v->tanggal_input?->format('d M') }}
                                </span>
                            </div>
                            <div style="font-size:12px; color:var(--ink-500); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $v->hasil_visit }} &middot; AR: {{ optional($v->arAgent)->name ?? '-' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartTrend');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Total Visit',
                        data: @json($chartVisits),
                        borderColor: '#E2001A',
                        backgroundColor: 'rgba(226,0,26,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    },
                    {
                        label: 'Janji Bayar (PTP)',
                        data: @json($chartPtp),
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245,158,11,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endpush
