@extends('layouts.app')

@section('title', 'C3MR — Hasil Caring Monitoring')
@section('subtitle', 'Monitoring hasil caring outbound / OBC PRITI pelanggan Telkom')

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
    <div class="col-6 col-lg-2">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Caring</div>
                <div class="kpi-value">{{ number_format($totalCaring) }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Semua aktivitas</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-telephone-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Contacted</div>
                <div class="kpi-value">{{ number_format($totalContacted) }}</div>
                <div style="font-size:11px; color:var(--success); margin-top:4px;">{{ $successRate }}% Keberhasilan</div>
            </div>
            <div class="kpi-icon" style="background:var(--success-soft); color:var(--success);">
                <i class="bi bi-person-check-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Uncontacted</div>
                <div class="kpi-value">{{ number_format($totalUncontacted) }}</div>
                <div style="font-size:11px; color:var(--danger); margin-top:4px;">Perlu retry / follow up</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-telephone-x-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Janji Bayar (PTP)</div>
                <div class="kpi-value">{{ number_format($totalPtp) }}</div>
                <div style="font-size:11px; color:var(--warning); margin-top:4px;">{{ $ptpRate }}% dari caring</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Sudah Bayar (Paid)</div>
                <div class="kpi-value">{{ number_format($totalPaid) }}</div>
                <div style="font-size:11px; color:var(--success); margin-top:4px;">{{ $paidRate }}% terealisasi</div>
            </div>
            <div class="kpi-icon" style="background:#D1FAE5; color:#059669;">
                <i class="bi bi-check2-all"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Belum Bayar</div>
                <div class="kpi-value">{{ number_format($totalUnpaid) }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Outstanding caring</div>
            </div>
            <div class="kpi-icon" style="background:var(--secondary); color:var(--ink-500);">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>
</div>

{{-- FILTER BAR --}}
<div class="filter-bar mb-3">
    <form method="GET" action="{{ url('/c3mr/hasil-caring') }}" class="d-flex w-100 gap-2 flex-wrap align-items-end">
        <div style="flex:2; min-width:200px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Pencarian</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                   placeholder="Cari nama, no internet, no HP, atau keterangan...">
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Petugas Caring</label>
            <select name="petugas" class="form-select form-select-sm">
                <option value="">Semua Petugas</option>
                @foreach($petugasList as $p)
                    <option value="{{ $p }}" {{ request('petugas') == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Status Caring</label>
            <select name="status_caring" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="CONTACTED" {{ request('status_caring') == 'CONTACTED' ? 'selected' : '' }}>Contacted</option>
                <option value="UNCONTACTED" {{ request('status_caring') == 'UNCONTACTED' ? 'selected' : '' }}>Uncontacted</option>
            </select>
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Hasil VOC</label>
            <select name="voc" class="form-select form-select-sm">
                <option value="">Semua VOC</option>
                @foreach($vocList as $v)
                    <option value="{{ $v }}" {{ request('voc') == $v ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1; min-width:120px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Status Bayar</label>
            <select name="status_bayar" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="PAID" {{ request('status_bayar') == 'PAID' ? 'selected' : '' }}>PAID</option>
                <option value="UNPAID" {{ request('status_bayar') == 'UNPAID' ? 'selected' : '' }}>UNPAID</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-telkom" style="height:31px; font-size:12.5px; padding:4px 14px;">
                <i class="bi bi-funnel"></i> Apply
            </button>
            @if(request()->anyFilled(['search', 'petugas', 'status_caring', 'voc', 'status_bayar']))
                <a href="{{ url('/c3mr/hasil-caring') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; height:31px;">Reset</a>
            @endif
            <a href="{{ route('c3mr.caring.export', request()->query()) }}" class="btn btn-sm" style="background:var(--success-soft); color:var(--success); border:1px solid rgba(22,163,74,0.3); font-weight:600; border-radius:8px; height:31px; display:inline-flex; align-items:center; gap:5px;">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
            </a>
        </div>
    </form>
</div>

{{-- MAIN CONTENT: CHART & TABLE --}}
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="section-title mb-1"><i class="bi bi-pie-chart-fill" style="color:var(--primary);"></i> Distribusi Voice of Customer (VOC)</div>
            <div class="section-sub mb-3">Rekapitulasi respons caring pelanggan</div>
            <canvas id="chartVoc" height="220"></canvas>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100 p-0" style="overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Riwayat Aktivitas Caring</div>
                    <div style="font-size:11.5px; color:var(--ink-400);">{{ $caringLogs->total() }} record ditemukan</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>No Internet</th>
                            <th>No HP</th>
                            <th>Petugas Caring</th>
                            <th>Status Caring</th>
                            <th>VOC / Hasil</th>
                            <th>Status Bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($caringLogs as $log)
                            <tr>
                                <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                                    {{ $log->tanggal_caring ? $log->tanggal_caring->format('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    <div style="font-weight:600; color:var(--ink-900); max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $log->nama_pelanggan }}
                                    </div>
                                    <div style="font-size:11px; color:var(--ink-400);">{{ $log->umur_customer ?: '-' }}</div>
                                </td>
                                <td>
                                    <code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">
                                        {{ $log->nomor_internet }}
                                    </code>
                                </td>
                                <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                                    @if($log->customer && $log->customer->wa_url)
                                        <a href="{{ $log->customer->wa_url }}" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                            <i class="bi bi-whatsapp"></i> {{ $log->no_hp ?: $log->customer->no_hp_terbaru }}
                                        </a>
                                    @else
                                        {{ $log->no_hp ?: '-' }}
                                    @endif
                                </td>
                                <td style="font-size:12px; font-weight:600; color:var(--ink-700);">
                                    {{ $log->petugas_caring }}
                                </td>
                                <td>
                                    @if($log->status_caring === 'CONTACTED')
                                        <span class="badge-status badge-contacted"><i class="bi bi-check-circle"></i> Contacted</span>
                                    @else
                                        <span class="badge-status badge-not-contacted"><i class="bi bi-x-circle"></i> Uncontacted</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge" style="background:var(--secondary); color:var(--ink-900); border:1px solid var(--border); font-size:11.5px; font-weight:600; padding:4px 8px; border-radius:6px;">
                                        {{ $log->voc }}
                                    </span>
                                    @if($log->keterangan)
                                        <div style="font-size:11px; color:var(--ink-400); margin-top:2px; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $log->keterangan }}">
                                            {{ $log->keterangan }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status_bayar === 'PAID')
                                        <span class="badge-status" style="background:#D1FAE5; color:#059669; font-weight:700;">PAID</span>
                                    @else
                                        <span class="badge-status badge-ptp" style="font-weight:700;">UNPAID</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->customer_id)
                                        <a href="{{ url('/customers/' . $log->customer_id) }}" class="btn btn-sm btn-outline-telkom"
                                           style="font-size:11.5px; padding:3px 8px; white-space:nowrap;"
                                           title="Lihat Detail Profil Pelanggan" data-bs-toggle="tooltip">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    @else
                                        <span style="color:var(--ink-400); font-size:11px;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i> Tidak ada data hasil caring ditemukan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 20px; border-top:1px solid var(--border);">
                {{ $caringLogs->links() }}
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const vocLabels = @json($vocDistribution->pluck('voc'));
    const vocData   = @json($vocDistribution->pluck('total'));

    const ctx = document.getElementById('chartVoc');
    if (ctx && vocLabels.length) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: vocLabels,
                datasets: [{
                    data: vocData,
                    backgroundColor: [
                        '#E2001A', '#3B82F6', '#22C55E', '#F59E0B',
                        '#8B5CF6', '#EC4899', '#14B8A6', '#64748B'
                    ],
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
                        labels: {
                            font: { size: 10 },
                            boxWidth: 10,
                            padding: 8
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
