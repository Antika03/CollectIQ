@extends('layouts.app')

@section('title', 'C3MR Performance & Churn Risk Indicator')
@section('subtitle', 'Analisis performansi Witel dan deteksi indikator risiko churn pelanggan')

@section('content')

{{-- LAST SYNC BANNER & ACTIONS --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-2 px-3" style="background:#FFFFFF; border:1px solid var(--border); border-radius:10px;">
    <div class="d-flex align-items-center gap-2" style="font-size:12.5px; color:var(--ink-700);">
        <i class="bi bi-clock-history" style="color:var(--primary);"></i>
        <span><strong>Last Sync:</strong> {{ $lastSyncFormatted ?? 'Belum pernah disinkronkan' }}</span>
    </div>
    <div>
        <a href="{{ url('/c3mr/sync') }}" class="btn btn-primary-telkom btn-sm" style="font-size:12px; padding:4px 12px;">
            <i class="bi bi-arrow-repeat"></i> Sync Data C3MR
        </a>
    </div>
</div>

{{-- KPI SUMMARY CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Pelanggan Terdaftar</div>
                <div class="kpi-value">{{ number_format($totalCustomers) }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Master data C3MR</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Pelanggan Berisiko Tinggi</div>
                <div class="kpi-value" style="color:var(--danger);">{{ number_format($totalHighCritical) }}</div>
                <div style="font-size:11px; color:var(--danger); margin-top:4px;">{{ $churnRiskRate }}% Churn Risk Rate</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-shield-slash-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Kategori Critical (Segera Cabut)</div>
                <div class="kpi-value" style="color:#991B1B;">{{ number_format($riskCounts['CRITICAL']) }}</div>
                <div style="font-size:11px; color:var(--danger); margin-top:4px;"><i class="bi bi-exclamation-triangle-fill"></i> Butuh intervensi segera</div>
            </div>
            <div class="kpi-icon" style="background:#FEE2E2; color:#991B1B;">
                <i class="bi bi-bell-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Outstanding Piutang</div>
                <div class="kpi-value" style="font-size:22px; white-space:nowrap;">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Nilai tagihan tertahan</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-stack"></i>
            </div>
        </div>
    </div>
</div>

{{-- SECTION: WITEL PERFORMANCE OVERVIEW & CHURN RISK CHART --}}
<div class="row g-3 mb-4">
    {{-- Witel Performance Ranking --}}
    <div class="col-lg-7">
        <div class="card h-100 p-0" style="overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Performansi Witel (C3MR / CYC NONPOTS)</div>
                    <div style="font-size:11.5px; color:var(--ink-400);">Berdasarkan data rekapitulasi C3MR terbaru</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-modern mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px;">Rank</th>
                            <th>Witel</th>
                            <th>Billing</th>
                            <th>Cash Collection</th>
                            <th>% CYC</th>
                            <th>Status Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($witelList as $idx => $w)
                            <tr>
                                <td style="font-weight:700; color:var(--ink-900); text-align:center;">
                                    @if($idx == 0)
                                        <span class="badge" style="background:#FEF3C7; color:#92400E; font-size:11px;">#1</span>
                                    @elseif($idx == 1)
                                        <span class="badge" style="background:#E5E7EB; color:#374151; font-size:11px;">#2</span>
                                    @elseif($idx == 2)
                                        <span class="badge" style="background:#FDE7D9; color:#9A3412; font-size:11px;">#3</span>
                                    @else
                                        #{{ $idx + 1 }}
                                    @endif
                                </td>
                                <td style="font-weight:600; color:var(--ink-900);">
                                    {{ $w->witel }}
                                </td>
                                <td style="font-size:12.5px; white-space:nowrap;">
                                    Rp {{ number_format($w->billing, 0, ',', '.') }}
                                </td>
                                <td style="font-size:12.5px; font-weight:600; color:var(--success); white-space:nowrap;">
                                    Rp {{ number_format($w->cash_coll, 0, ',', '.') }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:50px; height:6px; background:var(--secondary); border-radius:99px; overflow:hidden;">
                                            <div style="width:{{ min($w->cyc_percent, 100) }}%; height:100%; background:var(--primary); border-radius:99px;"></div>
                                        </div>
                                        <span style="font-size:12px; font-weight:700;">{{ $w->cyc_percent }}%</span>
                                    </div>
                                </td>
                                <td style="font-size:12px; color:var(--ink-500); white-space:nowrap;">
                                    Rp {{ number_format($w->saldo, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-3 text-muted">Belum ada data performansi Witel.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Churn Risk Level Distribution Chart --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="section-title mb-1"><i class="bi bi-pie-chart-fill" style="color:var(--primary);"></i> Distribusi Churn Risk Indicator</div>
            <div class="section-sub mb-3">Tingkat risiko churn pelanggan berbasis rule transparan</div>
            <canvas id="chartRisk" height="200"></canvas>
            <div class="row g-2 mt-3 pt-2" style="border-top:1px solid var(--border);">
                <div class="col-3 text-center">
                    <div style="font-size:10px; font-weight:700; color:#991B1B;">CRITICAL</div>
                    <div style="font-size:14px; font-weight:800;">{{ $riskCounts['CRITICAL'] }}</div>
                </div>
                <div class="col-3 text-center">
                    <div style="font-size:10px; font-weight:700; color:var(--danger);">HIGH</div>
                    <div style="font-size:14px; font-weight:800;">{{ $riskCounts['HIGH'] }}</div>
                </div>
                <div class="col-3 text-center">
                    <div style="font-size:10px; font-weight:700; color:var(--warning);">MEDIUM</div>
                    <div style="font-size:14px; font-weight:800;">{{ $riskCounts['MEDIUM'] }}</div>
                </div>
                <div class="col-3 text-center">
                    <div style="font-size:10px; font-weight:700; color:var(--success);">LOW</div>
                    <div style="font-size:14px; font-weight:800;">{{ $riskCounts['LOW'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SECTION: TOP AT-RISK CUSTOMERS TABLE & ACTIONABLE RECOMMENDATIONS --}}
<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <div style="font-weight:700; font-size:14.5px; color:var(--ink-900);"><i class="bi bi-shield-exclamation" style="color:var(--danger); margin-right:6px;"></i> Pelanggan Berisiko Churn &amp; Rekomendasi Tindakan</div>
            <div style="font-size:11.5px; color:var(--ink-400);">Daftar pelanggan yang diprioritaskan untuk penanganan Collection / Winback</div>
        </div>
        <form method="GET" action="{{ url('/c3mr/performance') }}" class="d-flex gap-2">
            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari pelanggan..." style="width:180px;">
            <select name="risk_level" class="form-select form-select-sm" style="width:140px;" onchange="this.form.submit()">
                <option value="">Semua Level</option>
                <option value="CRITICAL" {{ $filterLevel === 'CRITICAL' ? 'selected' : '' }}>Critical</option>
                <option value="HIGH" {{ $filterLevel === 'HIGH' ? 'selected' : '' }}>High</option>
                <option value="MEDIUM" {{ $filterLevel === 'MEDIUM' ? 'selected' : '' }}>Medium</option>
                <option value="LOW" {{ $filterLevel === 'LOW' ? 'selected' : '' }}>Low</option>
            </select>
            <button type="submit" class="btn btn-primary-telkom btn-sm">Filter</button>
            @if($search || $filterLevel)
                <a href="{{ url('/c3mr/performance') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>No Internet &amp; Kontak</th>
                    <th>Saldo Piutang</th>
                    <th style="text-align:center;">Risk Indicator</th>
                    <th>Alasan Indikator Risiko</th>
                    <th>Rekomendasi Tindakan Collection</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paginatedCustomers as $c)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width:32px; height:32px; font-size:12px;">
                                    {{ strtoupper(substr($c->nama_pelanggan, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:700; color:var(--ink-900); max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $c->nama_pelanggan }}
                                    </div>
                                    <div style="font-size:11px; color:var(--ink-400);">{{ $c->datel ?: 'Wilayah Telkom' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">
                                {{ $c->nomor_internet }}
                            </code>
                            <div style="font-size:11.5px; color:var(--ink-500); margin-top:2px;">
                                @if($c->wa_url)
                                    <a href="{{ $c->wa_url }}" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                        <i class="bi bi-whatsapp"></i> {{ $c->no_hp_terbaru }}
                                    </a>
                                @else
                                    <i class="bi bi-telephone"></i> {{ $c->no_hp_terbaru ?: '-' }}
                                @endif
                            </div>
                        </td>
                        <td style="font-weight:700; font-size:13px; color:var(--danger); white-space:nowrap;">
                            Rp {{ number_format($c->saldo_piutang, 0, ',', '.') }}
                            @if($c->umur_customer)
                                <div style="font-size:10.5px; color:var(--ink-400); font-weight:normal;">Umur: {{ $c->umur_customer }}</div>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($c->churn_level === 'CRITICAL')
                                <span class="badge" style="background:#450A0A; color:#fff; font-size:11px; padding:4px 8px; border-radius:99px;">CRITICAL ({{ $c->churn_score }})</span>
                            @elseif($c->churn_level === 'HIGH')
                                <span class="badge-status badge-risk-high">HIGH ({{ $c->churn_score }})</span>
                            @elseif($c->churn_level === 'MEDIUM')
                                <span class="badge-status badge-risk-medium">MEDIUM ({{ $c->churn_score }})</span>
                            @else
                                <span class="badge-status badge-risk-low">LOW ({{ $c->churn_score }})</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($c->churn_reasons))
                                <ul style="margin:0; padding-left:14px; font-size:11.5px; color:var(--ink-700);">
                                    @foreach($c->churn_reasons as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span style="font-size:11.5px; color:var(--ink-400);">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="background:var(--primary-soft); color:var(--primary-dark); font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:8px; line-height:1.4;">
                                <i class="bi bi-lightbulb-fill" style="color:var(--primary);"></i> {{ $c->churn_recommendation }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ url('/customers/' . $c->id) }}" class="btn btn-sm btn-outline-telkom"
                               style="font-size:11.5px; padding:3px 8px; white-space:nowrap;"
                               title="Lihat Detail Profil Pelanggan" data-bs-toggle="tooltip">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-shield-check"></i> Tidak ada pelanggan berisiko ditemukan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:14px 20px; border-top:1px solid var(--border);">
        {{ $paginatedCustomers->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartRisk');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Critical Risk', 'High Risk', 'Medium Risk', 'Low Risk'],
                datasets: [{
                    data: [
                        {{ $riskCounts['CRITICAL'] }},
                        {{ $riskCounts['HIGH'] }},
                        {{ $riskCounts['MEDIUM'] }},
                        {{ $riskCounts['LOW'] }}
                    ],
                    backgroundColor: ['#991B1B', '#EF4444', '#F59E0B', '#22C55E'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, boxWidth: 12, padding: 10 }
                    }
                }
            }
        });
    }
});
</script>
@endpush
