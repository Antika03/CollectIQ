@extends('layouts.app')

@section('title', 'Executive Command Center')
@section('subtitle', 'Monitoring performansi collection, pemulihan piutang, dan indikasi risiko churn pelanggan Telkom')

@section('content')

{{-- 1. EXECUTIVE KPI SUMMARY --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Master Customer</div>
                <div class="kpi-value">{{ number_format($totalCustomers, 0, ',', '.') }}</div>
                <div class="kpi-sub">Master Data C3MR DATA ALL</div>
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
                <div class="kpi-value rupiah-val" style="font-size:20px; color:var(--danger); white-space:nowrap;">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
                <div class="kpi-sub">{{ number_format($totalOutstanding, 0, ',', '.') }} pelanggan menunggak</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Pelanggan PRANPC</div>
                <div class="kpi-value" style="color:#D97706;">{{ number_format($pranpcCount, 0, ',', '.') }}</div>
                <div class="kpi-sub rupiah-val" style="font-size:11px; color:#D97706;">Rp {{ number_format($pranpcPiutang, 0, ',', '.') }} saldo</div>
            </div>
            <div class="kpi-icon" style="background:#FEF3C7; color:#D97706;">
                <i class="bi bi-tag-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Janji Bayar (PTP)</div>
                <div class="kpi-value">{{ number_format($totalPtp, 0, ',', '.') }}</div>
                <div class="kpi-sub">Hari ini: <strong>+{{ $todayPTP }}</strong> PTP</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>
</div>

{{-- 2. ACTION REQUIRED — INDIKASI RISIKO CHURN MATRIX --}}
<div class="card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="section-title">
                <i class="bi bi-lightning-charge-fill" style="color:var(--primary); margin-right:6px;"></i>
                Action Required — Prioritas Tindakan Collection &amp; Retensi
            </div>
            <div class="section-sub">Klasifikasi penanganan pelanggan berdasarkan Indikasi Risiko Churn</div>
        </div>
        <a href="{{ route('c3mr.performance') }}" class="btn btn-outline-telkom btn-sm">
            Lihat Analisis Witel <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--danger-soft); border:1px solid rgba(220,38,38,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--danger);">🔴 CRITICAL RISK</span>
                    <span style="font-size:18px; font-weight:800; color:var(--danger);">{{ number_format($criticalCount, 0, ',', '.') }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Permintaan cabut / saldo kritis. <strong>Intervensi winback segera.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--warning-soft); border:1px solid rgba(217,119,6,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--warning);">🟠 HIGH RISK</span>
                    <span style="font-size:18px; font-weight:800; color:var(--warning);">{{ number_format($highCount, 0, ',', '.') }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Broken PTP / saldo tinggi. <strong>Prioritas kunjungan visit AR.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:#FEFCE8; border:1px solid rgba(202,138,4,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:#A16207;">🟡 MEDIUM RISK</span>
                    <span style="font-size:18px; font-weight:800; color:#A16207;">{{ number_format($mediumCount, 0, ',', '.') }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Uncontacted / RNA. <strong>Caring ulang via WhatsApp/telepon.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--success-soft); border:1px solid rgba(22,163,74,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--success);">🟢 LOW / ROUTINE</span>
                    <span style="font-size:18px; font-weight:800; color:var(--success);">{{ number_format($lowCount, 0, ',', '.') }}</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Pelanggan lancar. <strong>Monitoring reguler.</strong>
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
                    <div class="section-title"><i class="bi bi-award-fill" style="color:var(--warning); margin-right:6px;"></i> Top Master AR Agent</div>
                    <div class="section-sub">Produktivitas kunjungan visit</div>
                </div>
                <a href="{{ route('ar-agents.index') }}" style="font-size:12px; color:var(--primary); font-weight:600; text-decoration:none;">Semua</a>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($topAgents as $idx => $agent)
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $idx == 0 ? 'bg-warning text-dark' : ($idx == 1 ? 'bg-secondary text-white' : 'bg-light text-dark') }}" style="font-size:11px; border-radius:6px; padding:2px 6px;">
                                    #{{ (int)$idx + 1 }}
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
                <a href="{{ route('piutang.index') }}" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
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
                                    <div class="d-flex align-items-center gap-1">
                                        <div style="font-weight:600; color:var(--ink-900);">{{ $c->nama_pelanggan }}</div>
                                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:12px; line-height:1; opacity:0.6;" onclick="copyToClipboard('{{ addslashes($c->nama_pelanggan) }}', this)" title="Salin Nama Pelanggan" data-bs-toggle="tooltip">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                    <div style="font-size:11px; color:var(--ink-400);">{{ $c->datel ?: 'Wilayah Telkom' }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 masked-snd-wrapper" data-snd="{{ $c->nomor_internet }}" data-masked="true">
                                        <code class="masked-snd-text" style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">••••••••••</code>
                                        <button type="button" class="btn btn-link p-0 text-muted toggle-mask-btn" onclick="toggleInternetMask(this)" title="Tampilkan Nomor Internet">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:11px; line-height:1; opacity:0.6;" onclick="copyToClipboard('{{ $c->nomor_internet }}', this)" title="Salin No Internet" data-bs-toggle="tooltip">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td style="text-align:right; font-weight:800; color:var(--danger); white-space:nowrap;">
                                    Rp {{ number_format($c->saldo_piutang, 0, ',', '.') }}
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ route('customer.show', $c) }}" class="btn btn-sm btn-outline-telkom" style="font-size:11.5px; padding:3px 8px; white-space:nowrap;" title="Lihat Detail Pelanggan" data-bs-toggle="tooltip">
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
                <a href="{{ route('visits.index') }}" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="p-3">
                @foreach($latestVisits as $v)
                    <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border);">
                        @if($v->foto_url)
                            <a href="{{ $v->foto_url }}" target="_blank">
                                <img src="{{ $v->foto_url }}"
                                     style="width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid var(--border);"
                                     onerror="this.src='{{ route('visit.photo', $v) }}'"
                                     alt="Foto visit">
                            </a>
                        @else
                            <div style="width:42px; height:42px; border-radius:8px; background:var(--secondary); display:flex; align-items:center; justify-content:center; color:var(--ink-400); flex-shrink:0;">
                                <i class="bi bi-image"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-1" style="min-width:0;">
                                    <span style="font-weight:700; font-size:13px; color:var(--ink-900); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ optional($v->customer)->nama_pelanggan ?? 'Pelanggan' }}
                                    </span>
                                    @if(optional($v->customer)->nama_pelanggan)
                                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:11px; line-height:1; opacity:0.6;" onclick="copyToClipboard('{{ addslashes($v->customer->nama_pelanggan) }}', this)" title="Salin Nama Pelanggan" data-bs-toggle="tooltip">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    @endif
                                </div>
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
