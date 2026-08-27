@extends('layouts.app')

@section('title', 'Total Piutang & Outstanding Collection')
@section('subtitle', 'Monitoring kondisi piutang, saldo tertahan, penandaan PRANPC dan aging tunggakan pelanggan')

@section('content')

{{-- KPI SUMMARY CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Outstanding Piutang</div>
                <div class="kpi-value rupiah-val" style="font-size:22px; color:var(--primary); white-space:nowrap;">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Saldo tertahan di pelanggan</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Pelanggan Menunggak</div>
                <div class="kpi-value">{{ number_format($totalPelangganMenunggak, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:var(--danger); margin-top:4px;">Memiliki saldo piutang > Rp 0</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Piutang PRANPC</div>
                <div class="kpi-value rupiah-val" style="font-size:20px; color:#D97706; white-space:nowrap;">Rp {{ number_format($pranpcPiutang, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:#D97706; margin-top:4px;">{{ number_format($pranpcCount, 0, ',', '.') }} pelanggan PRANPC</div>
            </div>
            <div class="kpi-icon" style="background:#FEF3C7; color:#D97706;">
                <i class="bi bi-tag-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Piutang Terbesar (Top 1)</div>
                <div class="kpi-value rupiah-val" style="font-size:20px; color:var(--warning); white-space:nowrap;">Rp {{ number_format($maxPiutang, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:var(--warning); margin-top:4px;">Prioritas penagihan utama</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
        </div>
    </div>
</div>

{{-- SECTION: CHARTS AGING & WILAYAH --}}
<div class="row g-3 mb-4">
    {{-- Aging Bucket Chart --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="section-title mb-1"><i class="bi bi-hourglass-split" style="color:var(--primary);"></i> Distribusi Aging Piutang (Umur Customer)</div>
            <div class="section-sub mb-3">Berdasarkan total saldo per kelompok umur</div>
            <canvas id="chartAging" height="170"></canvas>
        </div>
    </div>
    {{-- Wilayah / Datel Distribution --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="section-title mb-1"><i class="bi bi-geo-alt-fill" style="color:var(--primary);"></i> Piutang per Datel / Wilayah</div>
            <div class="section-sub mb-3">Akumulasi piutang tertahan per area kerja</div>
            <canvas id="chartDatel" height="170"></canvas>
        </div>
    </div>
</div>

{{-- FILTER & SEARCH BAR --}}
<div class="filter-bar mb-3">
    <form method="GET" action="{{ route('piutang.index') }}" class="d-flex w-100 gap-2 flex-wrap align-items-end">
        <div style="flex:2; min-width:200px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Pencarian</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                   placeholder="Cari nama, no internet, no HP, atau datel...">
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Kategori PRANPC</label>
            <select name="is_pranpc" class="form-select form-select-sm">
                <option value="">Semua Kategori</option>
                <option value="1" {{ request('is_pranpc') === '1' ? 'selected' : '' }}>PRANPC Saja</option>
                <option value="0" {{ request('is_pranpc') === '0' ? 'selected' : '' }}>Non-PRANPC</option>
            </select>
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Aging Tunggakan</label>
            <select name="umur_customer" class="form-select form-select-sm">
                <option value="">Semua Aging</option>
                @foreach($agingList as $ag)
                    <option value="{{ $ag }}" {{ request('umur_customer') == $ag ? 'selected' : '' }}>{{ $ag }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Datel / Wilayah</label>
            <select name="datel" class="form-select form-select-sm">
                <option value="">Semua Wilayah</option>
                @foreach($datelList as $d)
                    <option value="{{ $d }}" {{ request('datel') == $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1; min-width:120px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Urutkan</label>
            <select name="sort_by" class="form-select form-select-sm">
                <option value="saldo_piutang" {{ request('sort_by') == 'saldo_piutang' ? 'selected' : '' }}>Saldo Terbesar</option>
                <option value="nama_pelanggan" {{ request('sort_by') == 'nama_pelanggan' ? 'selected' : '' }}>Nama Pelanggan</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-telkom" style="height:31px; font-size:12.5px; padding:4px 14px;">
                <i class="bi bi-funnel"></i> Apply
            </button>
            @if(request()->anyFilled(['search', 'umur_customer', 'datel', 'sort_by', 'is_pranpc']))
                <a href="{{ route('piutang.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; height:31px;">Reset</a>
            @endif
            <a href="{{ route('piutang.export', request()->query()) }}" class="btn btn-sm" style="background:var(--success-soft); color:var(--success); border:1px solid rgba(22,163,74,0.3); font-weight:600; border-radius:8px; height:31px; display:inline-flex; align-items:center; gap:5px;">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
            </a>
        </div>
    </form>
</div>

{{-- TABLE OF PIUTANG CUSTOMERS --}}
<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Daftar Pelanggan Menunggak (Outstanding)</div>
            <div style="font-size:11.5px; color:var(--ink-400);">{{ number_format($customers->total(), 0, ',', '.') }} pelanggan ditemukan</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Pelanggan</th>
                    <th>Nomor Internet</th>
                    <th>Status PRANPC</th>
                    <th>AR Responsible</th>
                    <th>Nomor HP</th>
                    <th>Datel / STO</th>
                    <th>Umur Tunggakan</th>
                    <th style="text-align:right;">Saldo Piutang</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $idx => $c)
                    <tr>
                        <td style="color:var(--ink-400); font-weight:600; text-align:center;">
                            {{ $customers->firstItem() + $idx }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width:30px; height:30px; font-size:11px;">
                                    {{ strtoupper(substr($c->nama_pelanggan, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:700; color:var(--ink-900); max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $c->nama_pelanggan }}
                                    </div>
                                    <div style="font-size:11px; color:var(--ink-400);">
                                        {{ $c->nama_layanan_internet ?: 'Internet' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">
                                {{ $c->nomor_internet }}
                            </code>
                        </td>
                        <td>
                            @if($c->is_pranpc)
                                <span class="badge-pranpc"><i class="bi bi-tag-fill"></i> PRANPC</span>
                            @else
                                <span class="badge bg-light text-muted" style="font-size:10px; border:1px solid #E2E8F0;">{{ $c->bill_category ?: 'Eksisting' }}</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--ink-700); font-weight:600;">
                            {{ $c->assignedArAgent ? $c->assignedArAgent->name : '-' }}
                        </td>
                        <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                            @if($c->wa_url)
                                <a href="{{ $c->wa_url }}" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                    <i class="bi bi-whatsapp"></i> {{ $c->no_hp_terbaru }}
                                </a>
                            @else
                                <span style="color:var(--ink-400);">{{ $c->no_hp_terbaru ?: '-' }}</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--ink-700);">
                            {{ $c->datel ?: ($c->sto ?: '-') }}
                        </td>
                        <td>
                            <span class="badge" style="background:var(--secondary); color:var(--ink-900); font-weight:600; padding:4px 8px; border-radius:6px; border:1px solid var(--border);">
                                {{ $c->umur_customer ?: '-' }}
                            </span>
                        </td>
                        <td style="text-align:right; font-weight:800; color:var(--danger); font-size:13.5px; white-space:nowrap;">
                            Rp {{ number_format($c->saldo_piutang, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('customer.show', $c) }}" class="btn btn-sm btn-outline-telkom"
                               style="font-size:11.5px; padding:3px 8px; white-space:nowrap;"
                               title="Lihat Detail Profil Pelanggan 360" data-bs-toggle="tooltip">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i> Tidak ada data tunggakan piutang ditemukan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:14px 20px; border-top:1px solid var(--border);">
        {{ $customers->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const agingLabels = @json($agingBuckets->pluck('umur_customer'));
    const agingData   = @json($agingBuckets->pluck('total_saldo'));
    const ctxAging = document.getElementById('chartAging');
    if (ctxAging && agingLabels.length) {
        new Chart(ctxAging, {
            type: 'bar',
            data: {
                labels: agingLabels,
                datasets: [{
                    label: 'Total Saldo Piutang (Rp)',
                    data: agingData,
                    backgroundColor: 'rgba(226,0,26,0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) { return 'Rp ' + (v/1000000).toFixed(1) + 'M'; }
                        }
                    }
                }
            }
        });
    }

    const datelLabels = @json($datelDistribution->pluck('datel'));
    const datelData   = @json($datelDistribution->pluck('total_saldo'));
    const ctxDatel = document.getElementById('chartDatel');
    if (ctxDatel && datelLabels.length) {
        new Chart(ctxDatel, {
            type: 'bar',
            data: {
                labels: datelLabels,
                datasets: [{
                    label: 'Total Saldo Piutang (Rp)',
                    data: datelData,
                    backgroundColor: 'rgba(59,130,246,0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) { return 'Rp ' + (v/1000000).toFixed(1) + 'M'; }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
