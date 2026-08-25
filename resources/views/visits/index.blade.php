@extends('layouts.app')

@section('title', 'Visit Monitoring')
@section('subtitle', 'Collection Visit Monitoring — AR Team')

@push('styles')
<style>
/* ===== VISIT MONITORING STYLES ===== */
.vm-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
@media (max-width: 1199px) { .vm-kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 767px)  { .vm-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 479px)  { .vm-kpi-grid { grid-template-columns: 1fr 1fr; } }

.vm-kpi-card {
    background: rgba(255,255,255,0.78);
    backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 18px rgba(15,23,42,0.04);
    transition: box-shadow .2s ease, transform .2s ease;
    position: relative;
    overflow: hidden;
}
.vm-kpi-card:hover { box-shadow: 0 10px 28px rgba(15,23,42,0.08); transform: translateY(-1px); }
.vm-kpi-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 14px 14px 0 0;
}
.vm-kpi-card.accent-red::before    { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.vm-kpi-card.accent-blue::before   { background: linear-gradient(90deg, #3B82F6, #60A5FA); }
.vm-kpi-card.accent-amber::before  { background: linear-gradient(90deg, #F59E0B, #FCD34D); }
.vm-kpi-card.accent-green::before  { background: linear-gradient(90deg, #22C55E, #4ADE80); }
.vm-kpi-card.accent-rose::before   { background: linear-gradient(90deg, #F43F5E, #FB7185); }
.vm-kpi-card.accent-slate::before  { background: linear-gradient(90deg, #64748B, #94A3B8); }

.vm-kpi-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-bottom: 12px;
}
.vm-kpi-label { font-size: 11.5px; font-weight: 700; color: var(--ink-500); text-transform: uppercase; letter-spacing: .04em; }
.vm-kpi-value { font-size: 28px; font-weight: 800; color: var(--ink-900); margin-top: 4px; line-height: 1; }
.vm-kpi-sub   { font-size: 11px; color: var(--ink-400); margin-top: 6px; }

/* Filter Panel */
.vm-filter-panel {
    background: rgba(255,255,255,0.78);
    backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(15,23,42,0.04);
}
.vm-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
    gap: 10px;
    align-items: end;
    margin-top: 14px;
}
.vm-filter-row2 {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 10px;
    align-items: end;
    margin-top: 10px;
}
@media (max-width: 1199px) {
    .vm-filter-grid { grid-template-columns: 1fr 1fr 1fr; }
    .vm-filter-row2 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 767px) {
    .vm-filter-grid { grid-template-columns: 1fr 1fr; }
    .vm-filter-row2 { grid-template-columns: 1fr; }
}
@media (max-width: 479px) {
    .vm-filter-grid { grid-template-columns: 1fr; }
}

.vm-filter-label { font-size: 11.5px; font-weight: 700; color: var(--ink-500); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .03em; }

/* Table Container */
.vm-table-wrap {
    background: rgba(255,255,255,0.78);
    backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 18px rgba(15,23,42,0.04);
}
.vm-table-header {
    padding: 18px 22px 14px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border);
    gap: 12px; flex-wrap: wrap;
}
.vm-table-title { font-size: 15px; font-weight: 700; color: var(--ink-900); }
.vm-table-count { font-size: 12px; color: var(--ink-400); margin-top: 2px; }
.vm-table-scroll { overflow-x: auto; }

/* Status badges */
.vm-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700;
    padding: 3px 9px; border-radius: 99px;
    white-space: nowrap;
}
.vm-badge-ptp       { background: #FEF3C7; color: #92400E; }
.vm-badge-contacted { background: #D1FAE5; color: #065F46; }
.vm-badge-notcon    { background: #FEE2E2; color: #991B1B; }
.vm-badge-neutral   { background: var(--secondary); color: var(--ink-500); }

.vm-ptp-yes { background: #FEF3C7; color: #92400E; }
.vm-ptp-no  { background: #F1F5F9; color: var(--ink-400); }

/* Photo thumb */
.vm-photo-wrap {
    width: 42px; height: 42px; border-radius: 9px;
    overflow: hidden; border: 1px solid var(--border);
    cursor: pointer; flex-shrink: 0;
    transition: transform .15s ease, box-shadow .15s ease;
}
.vm-photo-wrap:hover { transform: scale(1.08); box-shadow: 0 4px 14px rgba(0,0,0,0.14); }
.vm-photo-thumb { width: 100%; height: 100%; object-fit: cover; }
.vm-photo-placeholder {
    width: 42px; height: 42px; border-radius: 9px;
    background: var(--secondary); border: 1px dashed var(--border);
    display: flex; align-items: center; justify-content: center;
    color: var(--ink-400); font-size: 15px; flex-shrink: 0;
}

/* Action buttons */
.vm-btn-action {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 10px; border-radius: 7px; border: 1px solid var(--border);
    font-size: 11.5px; font-weight: 600; color: var(--ink-700);
    background: var(--surface); cursor: pointer;
    transition: all .15s ease; text-decoration: none;
}
.vm-btn-action:hover { background: var(--secondary); color: var(--ink-900); border-color: var(--ink-400); }
.vm-btn-action.primary { background: var(--primary-soft); border-color: var(--primary-soft); color: var(--primary-dark); }
.vm-btn-action.primary:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

/* Charts grid */
.vm-charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-top: 20px;
}
@media (max-width: 991px) { .vm-charts-grid { grid-template-columns: 1fr; } }

.vm-chart-card {
    background: rgba(255,255,255,0.78);
    backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 18px rgba(15,23,42,0.04);
}
.vm-chart-title { font-size: 14px; font-weight: 700; color: var(--ink-900); margin-bottom: 4px; }
.vm-chart-sub   { font-size: 11.5px; color: var(--ink-400); margin-bottom: 16px; }

/* Modal */
.vm-modal-overlay {
    position: fixed; inset: 0; z-index: 1050;
    background: rgba(15,23,42,0.65);
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    opacity: 0; pointer-events: none;
    transition: opacity .2s ease;
}
.vm-modal-overlay.active { opacity: 1; pointer-events: all; }

.vm-modal-box {
    background: var(--surface);
    border-radius: 16px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.22);
    width: 100%; max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(20px) scale(.97);
    transition: transform .25s ease;
}
.vm-modal-overlay.active .vm-modal-box { transform: translateY(0) scale(1); }

.vm-modal-photo-box {
    max-width: 800px;
}
.vm-modal-header {
    padding: 20px 24px 0;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
}
.vm-modal-title { font-size: 16px; font-weight: 700; color: var(--ink-900); }
.vm-modal-close {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--secondary); color: var(--ink-500);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 16px; flex-shrink: 0;
    transition: all .15s ease;
}
.vm-modal-close:hover { background: var(--danger-soft); color: var(--danger); border-color: var(--danger); }
.vm-modal-body { padding: 20px 24px 24px; }

.vm-detail-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
}
@media (max-width: 480px) { .vm-detail-grid { grid-template-columns: 1fr; } }

.vm-detail-item label {
    font-size: 11px; font-weight: 700; color: var(--ink-400);
    text-transform: uppercase; letter-spacing: .04em;
    display: block; margin-bottom: 3px;
}
.vm-detail-item .val { font-size: 13.5px; color: var(--ink-900); font-weight: 500; }
.vm-detail-item .val.empty { color: var(--ink-400); font-style: italic; }

.vm-riwayat-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 10px 0; border-bottom: 1px solid var(--border);
}
.vm-riwayat-item:last-child { border-bottom: none; }
.vm-riwayat-dot {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: var(--primary-soft); color: var(--primary-dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
}

/* Photo modal */
.vm-photo-img-wrap {
    width: 100%; min-height: 300px;
    display: flex; align-items: center; justify-content: center;
    background: #0B0F19; border-radius: 0 0 16px 16px;
}
.vm-photo-img-wrap img {
    max-width: 100%; max-height: 65vh;
    object-fit: contain; display: block;
}
.vm-photo-actions {
    padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    border-top: 1px solid var(--border);
}

/* Pagination */
.vm-pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.vm-pagination-info { font-size: 12.5px; color: var(--ink-400); }

/* Agent bar chart */
.vm-bar-item { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.vm-bar-item:last-child { margin-bottom: 0; }
.vm-bar-name { font-size: 12.5px; color: var(--ink-700); font-weight: 600; min-width: 90px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vm-bar-track { flex: 1; height: 8px; background: var(--secondary); border-radius: 99px; overflow: hidden; }
.vm-bar-fill  { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--primary-light), var(--primary-dark)); transition: width .5s ease; }
.vm-bar-count { font-size: 12px; font-weight: 700; color: var(--ink-900); min-width: 28px; text-align: right; }

/* Loading state */
.vm-spinner { width: 40px; height: 40px; border: 3px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: vmSpin .7s linear infinite; }
@keyframes vmSpin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')

{{-- ============================================================
     KPI SUMMARY CARDS
     ============================================================ --}}
<div class="vm-kpi-grid">

    {{-- Total Visit --}}
    <div class="vm-kpi-card accent-red">
        <div class="vm-kpi-icon" style="background:var(--primary-soft);">
            <i class="bi bi-geo-alt-fill" style="color:var(--primary);"></i>
        </div>
        <div class="vm-kpi-label">Total Visit</div>
        <div class="vm-kpi-value">{{ number_format($totalVisit) }}</div>
        <div class="vm-kpi-sub">Semua waktu</div>
    </div>

    {{-- Visit Hari Ini --}}
    <div class="vm-kpi-card accent-blue">
        <div class="vm-kpi-icon" style="background:#EFF6FF;">
            <i class="bi bi-calendar-check-fill" style="color:#3B82F6;"></i>
        </div>
        <div class="vm-kpi-label">Hari Ini</div>
        <div class="vm-kpi-value">{{ number_format($visitHariIni) }}</div>
        <div class="vm-kpi-sub">{{ now()->translatedFormat('d M Y') }}</div>
    </div>

    {{-- PTP --}}
    <div class="vm-kpi-card accent-amber">
        <div class="vm-kpi-icon" style="background:#FEF3C7;">
            <i class="bi bi-cash-coin" style="color:#D97706;"></i>
        </div>
        <div class="vm-kpi-label">Total PTP</div>
        <div class="vm-kpi-value">{{ number_format($totalPtp) }}</div>
        <div class="vm-kpi-sub">{{ $totalVisit > 0 ? number_format($totalPtp / $totalVisit * 100, 1) : 0 }}% dari total</div>
    </div>

    {{-- Contacted --}}
    <div class="vm-kpi-card accent-green">
        <div class="vm-kpi-icon" style="background:#D1FAE5;">
            <i class="bi bi-person-check-fill" style="color:#059669;"></i>
        </div>
        <div class="vm-kpi-label">Contacted</div>
        <div class="vm-kpi-value">{{ number_format($contactedCount) }}</div>
        <div class="vm-kpi-sub">{{ $totalVisit > 0 ? number_format($contactedCount / $totalVisit * 100, 1) : 0 }}% dari total</div>
    </div>

    {{-- Not Contacted --}}
    <div class="vm-kpi-card accent-rose">
        <div class="vm-kpi-icon" style="background:#FEE2E2;">
            <i class="bi bi-person-x-fill" style="color:#DC2626;"></i>
        </div>
        <div class="vm-kpi-label">Not Contacted</div>
        <div class="vm-kpi-value">{{ number_format($notContactedCount) }}</div>
        <div class="vm-kpi-sub">{{ $totalVisit > 0 ? number_format($notContactedCount / $totalVisit * 100, 1) : 0 }}% dari total</div>
    </div>

    {{-- PTP Hari Ini --}}
    <div class="vm-kpi-card accent-slate">
        <div class="vm-kpi-icon" style="background:var(--secondary);">
            <i class="bi bi-star-fill" style="color:var(--ink-500);"></i>
        </div>
        <div class="vm-kpi-label">PTP Hari Ini</div>
        <div class="vm-kpi-value">{{ number_format($ptpHariIni) }}</div>
        <div class="vm-kpi-sub">{{ $visitHariIni > 0 ? number_format($ptpHariIni / $visitHariIni * 100, 1) : 0 }}% dari hari ini</div>
    </div>

</div>


{{-- ============================================================
     FILTER PANEL
     ============================================================ --}}
<div class="vm-filter-panel">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div>
            <div style="font-size:14px; font-weight:700; color:var(--ink-900);">
                <i class="bi bi-funnel-fill" style="color:var(--primary);"></i> Filter &amp; Pencarian
            </div>
            <div style="font-size:12px; color:var(--ink-400); margin-top:2px;">Filter akan tetap aktif saat pindah halaman</div>
        </div>
        @if(request()->anyFilled(['date_from','date_to','ar_agent_id','hasil_visit','kategori_visit','is_ptp','status_contact','search']))
        <a href="/visits" class="vm-btn-action" style="border-color:var(--danger-soft); color:var(--danger);">
            <i class="bi bi-x-circle"></i> Reset Filter
        </a>
        @endif
    </div>

    <form method="GET" action="/visits" id="filterForm">
        {{-- Row 1: tanggal, AR Agent, hasil visit, kategori, PTP --}}
        <div class="vm-filter-grid">
            <div>
                <div class="vm-filter-label">Tanggal Mulai</div>
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ request('date_from') }}">
            </div>
            <div>
                <div class="vm-filter-label">Tanggal Akhir</div>
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="{{ request('date_to') }}">
            </div>
            <div>
                <div class="vm-filter-label">AR Agent</div>
                <select name="ar_agent_id" class="form-select form-select-sm">
                    <option value="">Semua Agent</option>
                    @foreach($arAgents as $agent)
                    <option value="{{ $agent->id }}" {{ request('ar_agent_id') == $agent->id ? 'selected' : '' }}>
                        {{ $agent->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="vm-filter-label">Hasil Visit</div>
                <select name="hasil_visit" class="form-select form-select-sm">
                    <option value="">Semua Hasil</option>
                    @foreach($hasilVisitOptions as $hasil)
                    <option value="{{ $hasil }}" {{ request('hasil_visit') == $hasil ? 'selected' : '' }}>
                        {{ $hasil }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="vm-filter-label">Status PTP</div>
                <select name="is_ptp" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="1" {{ request('is_ptp') === '1' ? 'selected' : '' }}>PTP</option>
                    <option value="0" {{ request('is_ptp') === '0' ? 'selected' : '' }}>Non-PTP</option>
                </select>
            </div>
        </div>

        {{-- Row 2: search, kategori, contact status, tombol --}}
        <div class="vm-filter-row2">
            <div>
                <div class="vm-filter-label">Cari Pelanggan</div>
                <div style="position:relative;">
                    <i class="bi bi-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--ink-400); font-size:13px; pointer-events:none;"></i>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama pelanggan / nomor internet..."
                        value="{{ request('search') }}" style="padding-left:30px;">
                </div>
            </div>
            <div>
                <div class="vm-filter-label">Kategori Visit</div>
                <select name="kategori_visit" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriOptions as $kat)
                    <option value="{{ $kat }}" {{ request('kategori_visit') == $kat ? 'selected' : '' }}>
                        {{ $kat }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="vm-filter-label">Status Contact</div>
                <select name="status_contact" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="contacted"     {{ request('status_contact') === 'contacted'     ? 'selected' : '' }}>Contacted</option>
                    <option value="not_contacted" {{ request('status_contact') === 'not_contacted' ? 'selected' : '' }}>Not Contacted</option>
                </select>
            </div>
            <div style="display:flex; gap:8px; padding-top:18px;">
                <button type="submit" class="btn-primary-telkom btn w-100" style="height:33px; font-size:13px;">
                    <i class="bi bi-funnel"></i> Apply
                </button>
            </div>
        </div>
    </form>
</div>


{{-- ============================================================
     TABEL VISIT
     ============================================================ --}}
<div class="vm-table-wrap">
    <div class="vm-table-header">
        <div>
            <div class="vm-table-title">
                <i class="bi bi-table" style="color:var(--primary); margin-right:6px;"></i>
                Daftar Visit
            </div>
            <div class="vm-table-count">
                {{ $visits->total() }} data ditemukan
                @if($visits->total() > 0)
                — halaman {{ $visits->currentPage() }} dari {{ $visits->lastPage() }}
                @endif
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            {{-- Export Excel (maatwebsite/excel sudah ada di composer) --}}
            <a href="{{ '/visits/export?' . http_build_query(request()->except('page')) }}"
               class="vm-btn-action" id="btnExport">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>
    </div>

    @if($visits->isEmpty())
    <div class="empty-state" style="padding:60px 20px;">
        <i class="bi bi-inbox" style="font-size:42px; color:var(--ink-400); display:block; margin-bottom:12px;"></i>
        <div style="font-size:15px; font-weight:700; color:var(--ink-700); margin-bottom:6px;">Tidak ada data visit</div>
        <div style="font-size:13px; color:var(--ink-400);">Coba ubah filter atau tanggal pencarian</div>
    </div>
    @else
    <div class="vm-table-scroll">
        <table class="table-modern" style="min-width:900px;">
            <thead>
                <tr>
                    <th style="width:52px;">Foto</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Nomor Internet</th>
                    <th>AR Agent</th>
                    <th>Hasil Visit</th>
                    <th>Kategori</th>
                    <th style="width:80px; text-align:center;">PTP</th>
                    <th style="width:110px; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visits as $visit)
                <tr>
                    {{-- Foto --}}
                    <td>
                        @if($visit->drive_file_id)
                        <div class="vm-photo-wrap" onclick="openPhotoModal({{ $visit->id }}, '{{ addslashes($visit->customer->nama_pelanggan ?? '-') }}', '{{ $visit->drive_url }}')"
                             title="Lihat foto visit">
                            <img src="{{ $visit->photo_preview }}"
                                 class="vm-photo-thumb"
                                 alt="Foto visit"
                                 loading="lazy"
                                 onerror="this.parentElement.classList.add('vm-photo-placeholder'); this.remove(); this.parentElement.innerHTML='<i class=\'bi bi-image-fill\' style=\'color:var(--ink-400);font-size:16px;\'></i>';">
                        </div>
                        @else
                        <div class="vm-photo-placeholder" title="Tidak ada foto">
                            <i class="bi bi-image"></i>
                        </div>
                        @endif
                    </td>

                    {{-- Tanggal --}}
                    <td>
                        <div style="font-size:13px; font-weight:600; color:var(--ink-900);">
                            {{ $visit->tanggal_input?->format('d M Y') ?? '-' }}
                        </div>
                        <div style="font-size:11px; color:var(--ink-400);">
                            {{ $visit->tanggal_input?->diffForHumans() ?? '' }}
                        </div>
                    </td>

                    {{-- Pelanggan --}}
                    <td>
                        @if($visit->customer)
                        <div style="display:flex; align-items:center; gap:9px;">
                            <div class="avatar-circle" style="width:30px;height:30px;font-size:11px;flex-shrink:0;">
                                {{ strtoupper(substr($visit->customer->nama_pelanggan ?? '?', 0, 2)) }}
                            </div>
                            <div>
                                <div style="font-size:13px; font-weight:600; color:var(--ink-900); white-space:nowrap; max-width:180px; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $visit->customer->nama_pelanggan ?? '-' }}
                                </div>
                                @if($visit->customer->risk_level)
                                <span class="vm-badge
                                    @if($visit->customer->risk_level === 'critical') vm-badge-notcon
                                    @elseif($visit->customer->risk_level === 'high') vm-badge-notcon
                                    @elseif($visit->customer->risk_level === 'medium') vm-badge-ptp
                                    @else vm-badge-neutral @endif"
                                    style="font-size:10px; padding:2px 7px; margin-top:2px;">
                                    {{ strtoupper($visit->customer->risk_level) }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @else
                        <span style="color:var(--ink-400); font-size:12px; font-style:italic;">Data pelanggan tidak tersedia</span>
                        @endif
                    </td>

                    {{-- Nomor Internet --}}
                    <td>
                        <code style="font-size:12.5px; background:var(--secondary); padding:2px 7px; border-radius:5px; color:var(--ink-700);">
                            {{ $visit->customer->nomor_internet ?? '-' }}
                        </code>
                    </td>

                    {{-- AR Agent --}}
                    <td>
                        <div style="font-size:13px; color:var(--ink-700); font-weight:500;">
                            {{ $visit->arAgent->name ?? '-' }}
                        </div>
                    </td>

                    {{-- Hasil Visit --}}
                    <td>
                        @if($visit->hasil_visit && $visit->hasil_visit !== 'Belum Diisi' && $visit->hasil_visit !== '')
                        <span class="vm-badge vm-badge-contacted">
                            <i class="bi bi-check-circle-fill" style="font-size:10px;"></i>
                            {{ Str::limit($visit->hasil_visit, 25) }}
                        </span>
                        @else
                        <span class="vm-badge vm-badge-notcon">
                            <i class="bi bi-x-circle-fill" style="font-size:10px;"></i>
                            Belum Diisi
                        </span>
                        @endif
                    </td>

                    {{-- Kategori --}}
                    <td>
                        @if($visit->kategori_visit)
                        <span class="vm-badge vm-badge-neutral">
                            {{ Str::limit($visit->kategori_visit, 20) }}
                        </span>
                        @else
                        <span style="color:var(--ink-400); font-size:12px;">—</span>
                        @endif
                    </td>

                    {{-- PTP --}}
                    <td style="text-align:center;">
                        @if($visit->is_ptp)
                        <span class="vm-badge vm-badge-ptp">
                            <i class="bi bi-cash-coin" style="font-size:10px;"></i> PTP
                        </span>
                        @else
                        <span style="color:var(--ink-400); font-size:12px;">—</span>
                        @endif
                    </td>

                    {{-- Action --}}
                    <td style="text-align:center;">
                        <div style="display:flex; gap:5px; justify-content:center;">
                            <button class="vm-btn-action primary" title="Detail Visit"
                                onclick="openDetailModal({{ $visit->id }})">
                                <i class="bi bi-eye"></i>
                            </button>
                            @if($visit->customer)
                            <a href="/customers/{{ $visit->customer->id }}" class="vm-btn-action" title="Lihat Detail Profil Pelanggan" data-bs-toggle="tooltip">
                                <i class="bi bi-person-vcard"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div style="padding:16px 22px; border-top:1px solid var(--border);">
        <div class="vm-pagination">
            <div class="vm-pagination-info">
                Menampilkan {{ $visits->firstItem() }}–{{ $visits->lastItem() }} dari {{ number_format($visits->total()) }} data
            </div>
            <div>
                {{ $visits->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
    @endif
</div>


{{-- ============================================================
     VISIT ANALYTICS CHARTS
     ============================================================ --}}
<div class="vm-charts-grid">

    {{-- Chart: Trend Harian --}}
    <div class="vm-chart-card">
        <div class="vm-chart-title"><i class="bi bi-graph-up" style="color:var(--primary);"></i> Trend Visit Harian</div>
        <div class="vm-chart-sub">14 hari terakhir — visit vs PTP</div>
        <canvas id="chartTrend" height="130"></canvas>
    </div>

    {{-- Chart: Distribusi Hasil Visit --}}
    <div class="vm-chart-card">
        <div class="vm-chart-title"><i class="bi bi-pie-chart-fill" style="color:var(--primary);"></i> Distribusi Hasil Visit</div>
        <div class="vm-chart-sub">Berdasarkan seluruh data</div>
        <canvas id="chartDistribusi" height="180"></canvas>
    </div>

</div>

{{-- Agent Performance --}}
<div class="vm-chart-card" style="margin-top:16px;">
    <div class="vm-chart-title"><i class="bi bi-bar-chart-fill" style="color:var(--primary);"></i> Performa AR Agent</div>
    <div class="vm-chart-sub">Total visit per agent (top {{ $agentStats->count() }})</div>
    @php $maxVisit = max($agentStats->max('visits_count'), 1); @endphp
    <div style="margin-top:12px;">
        @forelse($agentStats as $agent)
        <div class="vm-bar-item">
            <div class="vm-bar-name" title="{{ $agent->name }}">{{ $agent->name }}</div>
            <div class="vm-bar-track">
                <div class="vm-bar-fill" style="width:{{ round($agent->visits_count / $maxVisit * 100) }}%"></div>
            </div>
            <div class="vm-bar-count">{{ $agent->visits_count }}</div>
        </div>
        @empty
        <div style="color:var(--ink-400); font-size:13px; text-align:center; padding:20px;">Belum ada data</div>
        @endforelse
    </div>
</div>

@endsection


{{-- ============================================================
     MODAL DETAIL VISIT
     ============================================================ --}}
@push('modals')

{{-- Detail Modal --}}
<div class="vm-modal-overlay" id="modalDetail" onclick="if(event.target===this) closeDetailModal()">
    <div class="vm-modal-box">
        <div class="vm-modal-header">
            <div>
                <div class="vm-modal-title" id="detailModalTitle">Detail Visit</div>
                <div style="font-size:12px; color:var(--ink-400); margin-top:2px;" id="detailModalSub"></div>
            </div>
            <button class="vm-modal-close" onclick="closeDetailModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="vm-modal-body">
            {{-- Loading state --}}
            <div id="detailLoading" style="text-align:center; padding:40px 0;">
                <div class="vm-spinner" style="margin:0 auto 12px;"></div>
                <div style="font-size:13px; color:var(--ink-400);">Memuat data...</div>
            </div>

            {{-- Content --}}
            <div id="detailContent" style="display:none;">

                {{-- Header Info --}}
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; padding:14px; background:var(--secondary); border-radius:11px;">
                    <div class="avatar-circle" id="detailAvatar" style="width:42px; height:42px; font-size:15px; flex-shrink:0;"></div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:15px; font-weight:700; color:var(--ink-900);" id="detailNama"></div>
                        <div style="font-size:12px; color:var(--ink-400); margin-top:2px;" id="detailNomor"></div>
                    </div>
                    <a id="detailCust360Btn" href="#" class="vm-btn-action primary" style="white-space:nowrap; display:none;">
                        <i class="bi bi-person-vcard"></i> Customer 360
                    </a>
                </div>

                {{-- Grid detail --}}
                <div class="vm-detail-grid" style="margin-bottom:18px;">
                    <div class="vm-detail-item">
                        <label>Tanggal Visit</label>
                        <div class="val" id="detailTanggal">—</div>
                    </div>
                    <div class="vm-detail-item">
                        <label>AR Agent</label>
                        <div class="val" id="detailAgent">—</div>
                    </div>
                    <div class="vm-detail-item">
                        <label>Hasil Visit</label>
                        <div class="val" id="detailHasil">—</div>
                    </div>
                    <div class="vm-detail-item">
                        <label>Kategori Visit</label>
                        <div class="val" id="detailKategori">—</div>
                    </div>
                    <div class="vm-detail-item">
                        <label>Status PTP</label>
                        <div class="val" id="detailPtp">—</div>
                    </div>
                    <div class="vm-detail-item">
                        <label>Risk Level</label>
                        <div class="val" id="detailRisk">—</div>
                    </div>
                    <div class="vm-detail-item">
                        <label>No. HP Visit</label>
                        <div class="val" id="detailHp">—</div>
                    </div>
                    <div class="vm-detail-item">
                        <label>Tipe Hunian</label>
                        <div class="val" id="detailHunian">—</div>
                    </div>
                </div>

                {{-- Keterangan --}}
                <div style="margin-bottom:18px;" id="detailKetWrap">
                    <div class="vm-filter-label" style="margin-bottom:6px;">Keterangan Visit</div>
                    <div id="detailKet" style="font-size:13.5px; color:var(--ink-700); background:var(--secondary); border-radius:9px; padding:12px; line-height:1.6;"></div>
                </div>

                {{-- Foto --}}
                <div id="detailFotoWrap" style="margin-bottom:18px; display:none;">
                    <div class="vm-filter-label" style="margin-bottom:8px;">Foto Visit</div>
                    <div id="detailFotoThumb" style="cursor:pointer; display:inline-block;"></div>
                </div>

                {{-- Riwayat --}}
                <div id="detailRiwayatWrap" style="display:none;">
                    <div style="font-size:13.5px; font-weight:700; color:var(--ink-900); margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid var(--border);">
                        <i class="bi bi-clock-history" style="color:var(--primary);"></i> Riwayat Visit Pelanggan
                    </div>
                    <div id="detailRiwayat"></div>
                </div>

            </div>

            {{-- Error state --}}
            <div id="detailError" style="display:none; text-align:center; padding:30px 0;">
                <i class="bi bi-exclamation-circle" style="font-size:32px; color:var(--danger); display:block; margin-bottom:10px;"></i>
                <div style="font-size:13px; color:var(--ink-500);">Gagal memuat data. Silakan coba lagi.</div>
            </div>
        </div>
    </div>
</div>

{{-- Photo Modal --}}
<div class="vm-modal-overlay" id="modalPhoto" onclick="if(event.target===this) closePhotoModal()">
    <div class="vm-modal-box vm-modal-photo-box">
        <div class="vm-modal-header" style="padding:16px 20px 0;">
            <div style="font-size:14px; font-weight:700; color:var(--ink-900);" id="photoModalTitle">Foto Visit</div>
            <button class="vm-modal-close" onclick="closePhotoModal()"><i class="bi bi-x-lg"></i></button>
        </div>

        {{-- Loading --}}
        <div id="photoLoading" style="padding:40px; text-align:center;">
            <div class="vm-spinner" style="margin:0 auto 12px;"></div>
            <div style="font-size:13px; color:var(--ink-400);">Memuat foto...</div>
        </div>

        {{-- Photo --}}
        <div class="vm-photo-img-wrap" id="photoImgWrap" style="display:none;">
            <img id="photoImg" src="" alt="Foto visit"
                 onerror="photoError()">
        </div>

        {{-- Error --}}
        <div id="photoError" style="display:none; padding:40px; text-align:center; background:#0B0F19; border-radius:0 0 16px 16px;">
            <i class="bi bi-image-fill" style="font-size:36px; color:#374151; display:block; margin-bottom:10px;"></i>
            <div style="font-size:13px; color:#6B7280;">Foto tidak dapat dimuat</div>
        </div>

        {{-- Actions --}}
        <div class="vm-photo-actions">
            <a id="photoGDriveBtn" href="#" target="_blank" class="vm-btn-action" style="display:none;">
                <i class="bi bi-google"></i> Buka di Google Drive
            </a>
            <button onclick="closePhotoModal()" class="vm-btn-action" style="margin-left:auto;">
                <i class="bi bi-x"></i> Tutup
            </button>
        </div>
    </div>
</div>

@endpush


{{-- ============================================================
     SCRIPTS
     ============================================================ --}}
@push('scripts')
<script>
// ==================================================================
// Chart: Trend Harian
// ==================================================================
(function() {
    const labels  = @json($chartLabels);
    const visits  = @json($chartVisits);
    const ptpData = @json($chartPtp);

    const gridColor = 'rgba(15,23,42,0.05)';
    const textColor = '#64748B';

    const ctx = document.getElementById('chartTrend');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Total Visit',
                    data: visits,
                    borderColor: '#E2001A',
                    backgroundColor: 'rgba(226,0,26,0.08)',
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#E2001A',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'PTP',
                    data: ptpData,
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245,158,11,0.08)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: false,
                    borderDash: [5, 3],
                    pointRadius: 3,
                    pointBackgroundColor: '#F59E0B',
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: { color: textColor, font: { size: 12 }, boxWidth: 16 }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 11 }, precision: 0 }
                },
            },
        }
    });
})();

// ==================================================================
// Chart: Distribusi Hasil Visit
// ==================================================================
(function() {
    const data  = @json($hasilDistribution->pluck('total'));
    const labels = @json($hasilDistribution->pluck('hasil_visit'));

    const ctx = document.getElementById('chartDistribusi');
    if (!ctx || !labels.length) {
        if (ctx) ctx.closest('.vm-chart-card').innerHTML += '<div style="text-align:center;padding:20px;color:var(--ink-400);font-size:13px;">Belum ada data</div>';
        return;
    }

    const palette = [
        '#E2001A','#3B82F6','#22C55E','#F59E0B',
        '#8B5CF6','#EC4899','#14B8A6','#F97316',
    ];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: palette.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#64748B',
                        font: { size: 11 },
                        boxWidth: 12,
                        padding: 10,
                    }
                },
            }
        }
    });
})();

// ==================================================================
// Modal: Detail Visit
// ==================================================================
function openDetailModal(visitId) {
    const overlay = document.getElementById('modalDetail');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Reset state
    document.getElementById('detailLoading').style.display = 'block';
    document.getElementById('detailContent').style.display = 'none';
    document.getElementById('detailError').style.display   = 'none';

    // AJAX load
    fetch(`/visits/${visitId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => {
        if (!r.ok) throw new Error('Request failed');
        return r.json();
    })
    .then(data => {
        populateDetailModal(data);
        document.getElementById('detailLoading').style.display = 'none';
        document.getElementById('detailContent').style.display = 'block';
    })
    .catch(() => {
        document.getElementById('detailLoading').style.display = 'none';
        document.getElementById('detailError').style.display   = 'block';
    });
}

function populateDetailModal(data) {
    const v = data.visit;
    const c = v.customer;
    const a = v.ar_agent;

    // Header
    const nama = c ? c.nama_pelanggan : '—';
    document.getElementById('detailModalTitle').textContent = 'Detail Visit';
    document.getElementById('detailModalSub').textContent   = v.tanggal_input ?? '';
    document.getElementById('detailAvatar').textContent     = nama.substring(0, 2).toUpperCase();
    document.getElementById('detailNama').textContent       = nama;
    document.getElementById('detailNomor').textContent      = c ? c.nomor_internet : '';

    // Customer 360 button
    const btn360 = document.getElementById('detailCust360Btn');
    if (c) {
        btn360.href = `/customers/${c.id}`;
        btn360.style.display = '';
    } else {
        btn360.style.display = 'none';
    }

    setText('detailTanggal', v.tanggal_input);
    setText('detailAgent',   a ? a.name : null);
    setText('detailHasil',   v.hasil_visit);
    setText('detailKategori',v.kategori_visit);
    if (c && c.wa_url) {
        document.getElementById('detailHp').innerHTML = `<a href="${c.wa_url}" target="_blank" style="color:#16A34A; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:5px;"><i class="bi bi-whatsapp"></i> ${v.no_hp_snapshot || c.no_hp_terbaru}</a>`;
    } else {
        setText('detailHp', v.no_hp_snapshot || (c ? c.no_hp_terbaru : null));
    }
    setText('detailHunian',  v.tipe_hunian_snapshot);

    // PTP
    document.getElementById('detailPtp').innerHTML = v.is_ptp
        ? '<span class="vm-badge vm-badge-ptp"><i class="bi bi-cash-coin"></i> PTP</span>'
        : '<span class="vm-badge vm-badge-neutral">Non-PTP</span>';

    // Risk Level
    if (c && c.risk_level) {
        const cls = {
            'critical': 'vm-badge-notcon', 'high': 'vm-badge-notcon',
            'medium': 'vm-badge-ptp', 'low': 'vm-badge-contacted',
        }[c.risk_level] || 'vm-badge-neutral';
        document.getElementById('detailRisk').innerHTML =
            `<span class="vm-badge ${cls}">${c.risk_level.toUpperCase()} (${c.risk_score})</span>`;
    } else {
        setText('detailRisk', null);
    }

    // Keterangan
    const ketWrap = document.getElementById('detailKetWrap');
    if (v.keterangan_visit) {
        document.getElementById('detailKet').textContent = v.keterangan_visit;
        ketWrap.style.display = '';
    } else {
        ketWrap.style.display = 'none';
    }

    // Foto thumbnail in detail
    const fotoWrap = document.getElementById('detailFotoWrap');
    if (v.photo_preview) {
        fotoWrap.style.display = '';
        document.getElementById('detailFotoThumb').innerHTML =
            `<div class="vm-photo-wrap" style="width:80px;height:80px;"
                  onclick="closeDetailModal(); setTimeout(()=>openPhotoModal(${v.id}, '${escapeHtml(nama)}', '${v.drive_url||''}'), 200)">
                <img src="${v.photo_preview}" style="width:100%;height:100%;object-fit:cover;"
                     alt="Foto visit"
                     onerror="this.parentElement.innerHTML='<i class=\'bi bi-image\' style=\'color:var(--ink-400);font-size:22px;display:flex;align-items:center;justify-content:center;height:100%;\'></i>'">
             </div>`;
    } else {
        fotoWrap.style.display = 'none';
    }

    // Riwayat
    const riwayatWrap = document.getElementById('detailRiwayatWrap');
    const riwayatContainer = document.getElementById('detailRiwayat');
    if (data.riwayat && data.riwayat.length > 0) {
        riwayatWrap.style.display = '';
        riwayatContainer.innerHTML = data.riwayat.map(r => `
            <div class="vm-riwayat-item">
                <div class="vm-riwayat-dot"><i class="bi bi-geo-alt-fill"></i></div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:600; color:var(--ink-900);">${escapeHtml(r.hasil_visit || 'Belum Diisi')}</div>
                    <div style="font-size:11.5px; color:var(--ink-400); margin-top:2px;">
                        ${escapeHtml(r.tanggal_input || '')}
                        ${r.ar_agent ? '· ' + escapeHtml(r.ar_agent) : ''}
                    </div>
                </div>
                ${r.is_ptp ? '<span class="vm-badge vm-badge-ptp" style="flex-shrink:0;"><i class="bi bi-cash-coin"></i> PTP</span>' : ''}
            </div>
        `).join('');
    } else {
        riwayatWrap.style.display = 'none';
    }
}

function closeDetailModal() {
    document.getElementById('modalDetail').classList.remove('active');
    document.body.style.overflow = '';
}

// ==================================================================
// Modal: Foto Visit
// ==================================================================
function openPhotoModal(visitId, nama, driveUrl) {
    const overlay = document.getElementById('modalPhoto');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    document.getElementById('photoModalTitle').textContent = `Foto Visit — ${nama}`;
    document.getElementById('photoLoading').style.display  = 'block';
    document.getElementById('photoImgWrap').style.display  = 'none';
    document.getElementById('photoError').style.display    = 'none';

    const img = document.getElementById('photoImg');
    img.onload = function() {
        document.getElementById('photoLoading').style.display  = 'none';
        document.getElementById('photoImgWrap').style.display  = 'flex';
    };
    img.src = `/visits/${visitId}/photo`;

    // Google Drive button
    const gdBtn = document.getElementById('photoGDriveBtn');
    if (driveUrl) {
        gdBtn.href = driveUrl;
        gdBtn.style.display = '';
    } else {
        gdBtn.style.display = 'none';
    }
}

function photoError() {
    document.getElementById('photoLoading').style.display  = 'none';
    document.getElementById('photoImgWrap').style.display  = 'none';
    document.getElementById('photoError').style.display    = 'block';
}

function closePhotoModal() {
    document.getElementById('modalPhoto').classList.remove('active');
    document.body.style.overflow = '';
    // Reset src supaya tidak ada request lama
    setTimeout(() => { document.getElementById('photoImg').src = ''; }, 300);
}

// Tutup modal dengan Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
        closePhotoModal();
    }
});

// ==================================================================
// Helpers
// ==================================================================
function setText(id, val) {
    const el = document.getElementById(id);
    if (!el) return;
    if (val) {
        el.textContent = val;
        el.classList.remove('empty');
    } else {
        el.textContent = '—';
        el.classList.add('empty');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>
@endpush