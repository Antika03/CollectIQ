@extends('layouts.app')

@section('title', 'Sync Data C3MR')
@section('subtitle', 'Pusat integrasi satu pintu data Google Spreadsheet C3MR, Report PRQ, dan VISEEPRO')

@push('styles')
<style>
.sync-hero-card {
    background: linear-gradient(135deg, #FFFFFF 0%, #FFF5F5 100%);
    border: 1px solid rgba(226, 0, 26, 0.18);
    border-radius: 16px;
    padding: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(226, 0, 26, 0.06);
}
.sync-hero-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #E2001A, #FF3B4E, #F59E0B);
}
.btn-sync-hero {
    background: linear-gradient(135deg, #E2001A 0%, #B8000F 100%);
    color: #FFFFFF !important;
    border: none;
    border-radius: 12px;
    padding: 14px 32px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: .02em;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 6px 20px rgba(226, 0, 26, 0.28);
    transition: all .2s ease;
    cursor: pointer;
    min-width: 240px;
}
.btn-sync-hero:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(226, 0, 26, 0.38);
    filter: brightness(1.05);
}
.btn-sync-hero:active:not(:disabled) {
    transform: translateY(0);
}
.btn-sync-hero:disabled {
    opacity: 0.85;
    cursor: not-allowed;
    filter: grayscale(0.2);
}
.sync-icon.spinning {
    animation: spinSync 1s linear infinite;
    display: inline-block;
}
@keyframes spinSync {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.sync-result-box {
    border-radius: 14px;
    background: #FFFFFF;
    border: 1px solid var(--border);
    padding: 20px;
    transition: all .2s ease;
}
.source-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
}
.source-badge-success { background: #DCFCE7; color: #166534; }
.source-badge-error { background: #FEE2E2; color: #991B1B; }
.sync-grid-item {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 14px 16px;
    transition: transform .15s ease, border-color .15s ease;
}
.sync-grid-item:hover {
    border-color: #CBD5E1;
    background: #FFFFFF;
}
</style>
@endpush

@section('content')

{{-- FLASH SESSION ALERTS (FOR NON-AJAX FALLBACK) --}}
@if(session('success') && !session('syncResult'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif

@if(session('error') && !session('syncResult'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
    </div>
@endif

{{-- 1. MASTER HERO SYNC DATA C3MR SECTION --}}
<div class="card sync-hero-card mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge" style="background:#FDEBEC; color:#B8000F; font-weight:700; font-size:11px; padding:5px 10px; border-radius:6px;">
                    <i class="bi bi-lightning-charge-fill"></i> SINKRONISASI SATU PINTU
                </span>
                <span class="badge" style="background:#EFF6FF; color:#2563EB; font-weight:600; font-size:11px; padding:5px 10px; border-radius:6px;" id="lastSyncBadge">
                    <i class="bi bi-clock-history"></i> Last Sync: <span id="lastSyncText">{{ $lastSyncFormatted }}</span>
                </span>
            </div>
            <h4 style="font-weight:800; color:var(--ink-900); margin-bottom:6px; font-size:22px;">
                Sync Data C3MR
            </h4>
            <p style="color:var(--ink-500); font-size:13.5px; margin-bottom:0; line-height:1.5;">
                Pusat pembaruan seluruh sumber data aplikasi. Memperbarui otomatis <strong>Report PRQ</strong>, <strong>VISEEPRO</strong>, <strong>DATA ALL</strong>, <strong>Hasil Caring OBC</strong>, dan <strong>Performansi Witel Regional</strong> dalam satu kali klik.
            </p>
        </div>
        <div class="col-lg-5 text-lg-end text-start">
            <button type="button" id="btnSyncMaster" class="btn-sync-hero" onclick="triggerMasterSync()">
                <i class="bi bi-arrow-repeat sync-icon fs-5" id="masterSyncIcon"></i>
                <span id="masterSyncLabel">Sync Data C3MR</span>
            </button>
            <div style="font-size:12px; color:var(--ink-500); margin-top:8px; font-weight:500;">
                <i class="bi bi-info-circle"></i> Memperbarui data Report PRQ, VISEEPRO, dan C3MR
            </div>
        </div>
    </div>

    {{-- LIVE SYNC STATUS & PROGRESS INDICATOR --}}
    <div id="syncLiveStatus" class="mt-4 pt-3" style="display:none; border-top:1px dashed #FCA5A5;">
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
            <div>
                <strong style="color:var(--primary); font-size:13.5px;">Sinkronisasi sedang berlangsung...</strong>
                <div style="font-size:12px; color:var(--ink-500);">Mengunduh data terbaru dari Google Spreadsheet dan memperbarui database. Mohon tunggu...</div>
            </div>
        </div>
    </div>
</div>

{{-- 2. LIVE / LAST SYNC RESULT CONTAINER --}}
<div id="syncResultContainer" class="card mb-4" style="{{ ($lastSyncResult || session('syncResult')) ? '' : 'display:none;' }}">
    @php
        $activeResult = session('syncResult') ?: $lastSyncResult;
        $statusKey = $activeResult['status'] ?? ($lastSyncStatus ?: 'success');
        $statusLabel = $activeResult['status_label'] ?? ($statusKey === 'success' ? 'Sinkronisasi berhasil' : 'Sinkronisasi selesai dengan beberapa masalah');
        $details = $activeResult['details'] ?? [];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom:1px solid var(--border);">
        <div class="d-flex align-items-center gap-2">
            <span id="resultStatusIcon">
                @if($statusKey === 'success')
                    <i class="bi bi-check-circle-fill fs-5" style="color:var(--success);"></i>
                @elseif($statusKey === 'warning')
                    <i class="bi bi-exclamation-triangle-fill fs-5" style="color:var(--warning);"></i>
                @else
                    <i class="bi bi-x-circle-fill fs-5" style="color:var(--danger);"></i>
                @endif
            </span>
            <div>
                <div class="section-title mb-0" id="resultStatusTitle">
                    {{ $statusKey === 'success' ? '✓ ' : ($statusKey === 'warning' ? '⚠ ' : '✕ ') }} {{ $statusLabel }}
                </div>
                <div class="section-sub" id="resultStatusSub">
                    Terakhir diperbarui: <span id="resultTimestampText">{{ $lastSyncFormatted }}</span>
                </div>
            </div>
        </div>
        <div>
            <span class="badge" id="resultSummaryBadge" style="background:var(--secondary); color:var(--ink-700); font-weight:600; font-size:11.5px; padding:6px 12px; border-radius:8px;">
                <span id="resultProcessedCount">{{ number_format($activeResult['total_rows_processed'] ?? ($totalVisits + $totalCustomers + $totalCaring)) }}</span> records diproses
            </span>
        </div>
    </div>

    {{-- DETAILED BREAKDOWN OF ALL 6 DATA SOURCES --}}
    <div class="row g-3" id="syncDetailsGrid">
        {{-- Report PRQ --}}
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-geo-alt-fill" style="color:var(--primary); margin-right:4px;"></i> Report PRQ
                    </div>
                    <span class="source-badge {{ ($details['report_prq']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error' }}" id="badge_report_prq">
                        {{ ($details['report_prq']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal' }}
                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_report_prq">
                    {{ number_format($details['report_prq']['count'] ?? $totalVisits) }} <span style="font-size:12px; font-weight:500; color:var(--ink-500);">records</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_report_prq">
                    {{ $details['report_prq']['message'] ?? 'Data kunjungan lapangan, PTP & nomor kontak valid' }}
                </div>
            </div>
        </div>

        {{-- VISEEPRO --}}
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-building-check" style="color:#2563EB; margin-right:4px;"></i> VISEEPRO
                    </div>
                    <span class="source-badge {{ ($details['viseepro']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error' }}" id="badge_viseepro">
                        {{ ($details['viseepro']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal' }}
                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_viseepro">
                    {{ number_format($details['viseepro']['count'] ?? $totalViseepro) }} <span style="font-size:12px; font-weight:500; color:var(--ink-500);">records</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_viseepro">
                    {{ $details['viseepro']['message'] ?? 'Aktivitas survey AR, profil perusahaan, PIC & koordinat' }}
                </div>
            </div>
        </div>

        {{-- C3MR DATA ALL --}}
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-people-fill" style="color:#059669; margin-right:4px;"></i> C3MR Master Data
                    </div>
                    <span class="source-badge {{ ($details['data_all']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error' }}" id="badge_data_all">
                        {{ ($details['data_all']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal' }}
                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_data_all">
                    {{ number_format($details['data_all']['count'] ?? $totalCustomers) }} <span style="font-size:12px; font-weight:500; color:var(--ink-500);">pelanggan</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_data_all">
                    {{ $details['data_all']['message'] ?? 'Master pelanggan, STO, Datel, No HP & Saldo Piutang' }}
                </div>
            </div>
        </div>

        {{-- C3MR HASIL CARING --}}
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-telephone-inbound-fill" style="color:#7C3AED; margin-right:4px;"></i> C3MR Hasil Caring
                    </div>
                    <span class="source-badge {{ ($details['caring']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error' }}" id="badge_caring">
                        {{ ($details['caring']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal' }}
                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_caring">
                    {{ number_format($details['caring']['count'] ?? $totalCaring) }} <span style="font-size:12px; font-weight:500; color:var(--ink-500);">log caring</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_caring">
                    {{ $details['caring']['message'] ?? 'Riwayat penagihan telepon OBC PRITI, VOC & status bayar' }}
                </div>
            </div>
        </div>

        {{-- C3MR PERFORMANSI WITEL --}}
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-diagram-3-fill" style="color:#D97706; margin-right:4px;"></i> Performansi Witel
                    </div>
                    <span class="source-badge {{ ($details['performance']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error' }}" id="badge_performance">
                        {{ ($details['performance']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal' }}
                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_performance">
                    {{ number_format($details['performance']['count'] ?? $totalWitel) }} <span style="font-size:12px; font-weight:500; color:var(--ink-500);">witel</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_performance">
                    {{ $details['performance']['message'] ?? 'Rekapitulasi performansi Billing, Cash Collection & % CYC' }}
                </div>
            </div>
        </div>

        {{-- NORMALISASI AR AGENTS --}}
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-person-badge-fill" style="color:#0284C7; margin-right:4px;"></i> AR Agents
                    </div>
                    <span class="source-badge {{ ($details['ar_agents']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error' }}" id="badge_ar_agents">
                        {{ ($details['ar_agents']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal' }}
                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_ar_agents">
                    {{ number_format($details['ar_agents']['count'] ?? 19) }} <span style="font-size:12px; font-weight:500; color:var(--ink-500);">agent unik</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_ar_agents">
                    {{ $details['ar_agents']['message'] ?? 'Konsolidasi variasi penulisan nama agent petugas' }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 3. DATA OVERVIEW KPI CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Master Pelanggan</div>
                <div class="kpi-value" id="kpiCustomers">{{ number_format($totalCustomers) }}</div>
                <div style="font-size:11px; color:var(--success); margin-top:4px;">
                    <i class="bi bi-check-circle-fill"></i> {{ number_format($validPhones) }} No HP valid
                </div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Riwayat Visit Lapangan</div>
                <div class="kpi-value" id="kpiVisits">{{ number_format($totalVisits) }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Sumber: Report PRQ</div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#2563EB;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Log Caring Terkumpul</div>
                <div class="kpi-value" id="kpiCaring">{{ number_format($totalCaring) }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Aktivitas OBC PRITI</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-telephone-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Aktivitas VISEEPRO</div>
                <div class="kpi-value" id="kpiViseepro">{{ number_format($totalViseepro) }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Survey &amp; Profil PIC</div>
            </div>
            <div class="kpi-icon" style="background:var(--secondary); color:var(--ink-700);">
                <i class="bi bi-building-check"></i>
            </div>
        </div>
    </div>
</div>

{{-- 4. ON-DEMAND INDIVIDUAL SYNC ACTIONS & INTEGRITY --}}
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title mb-1"><i class="bi bi-sliders" style="color:var(--primary);"></i> Sinkronisasi Sumber Data Individual</div>
                    <div class="section-sub">Opsi manual jika hanya ingin menyegarkan sheet tertentu secara terpisah</div>
                </div>
            </div>

            <div class="row g-3">
                {{-- Sync DATA ALL --}}
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">1. Sheet DATA ALL</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Melengkapi Master Customer, Nomor HP valid, Alamat, STO, Datel, dan Saldo Piutang.
                            </div>
                        </div>
                        <form method="POST" action="{{ url('/c3mr/sync/data-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-telkom btn-sm w-100 justify-content-center">
                                <i class="bi bi-arrow-repeat"></i> Sync DATA ALL
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Sync HASIL CARING --}}
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">2. Sheet HASIL CARING</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Mengimpor riwayat respons telepon OBC PRITI (VOC, Status Caring, Status Bayar).
                            </div>
                        </div>
                        <form method="POST" action="{{ url('/c3mr/sync/caring') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm w-100 justify-content-center" style="background:#EFF6FF; color:#2563EB; font-weight:600; border-radius:8px;">
                                <i class="bi bi-telephone-inbound"></i> Sync Hasil Caring
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Sync PERFORMANSI DETAIL --}}
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">3. Sheet PERFORMANSI DETAIL</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Memperbarui rekap performansi Witel (Billing, Cash Coll, % CYC, % CR, Rank).
                            </div>
                        </div>
                        <form method="POST" action="{{ url('/c3mr/sync/performance') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm w-100 justify-content-center" style="background:var(--warning-soft); color:#92400E; font-weight:600; border-radius:8px;">
                                <i class="bi bi-graph-up-arrow"></i> Sync Performansi Witel
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Konsolidasi AR Agent --}}
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">4. Normalisasi AR Agent</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Menggabungkan nama variasi duplikat tanpa merusak relasi histori kunjungan.
                            </div>
                        </div>
                        <form method="POST" action="{{ url('/c3mr/sync/consolidate-ar') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm w-100 justify-content-center" style="background:var(--success-soft); color:var(--success); font-weight:600; border-radius:8px;">
                                <i class="bi bi-person-check"></i> Konsolidasi AR Agent
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DATA INTEGRITY & API CONFIGURATION CARD --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="section-title mb-1"><i class="bi bi-shield-check" style="color:var(--success);"></i> Kualitas &amp; Integritas Data</div>
            <div class="section-sub mb-3">Pemeriksaan kualitas master data</div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">No. HP Terisi Valid</span>
                <span style="font-weight:700; color:var(--success);">
                    {{ $totalCustomers > 0 ? round(($validPhones / $totalCustomers) * 100, 1) : 0 }}%
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">Integrasi Report PRQ</span>
                <span class="badge" style="background:#D1FAE5; color:#059669;">Terkoneksi</span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">Integrasi VISEEPRO</span>
                <span class="badge" style="background:#D1FAE5; color:#059669;">Terkoneksi</span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">Integrasi Sheet C3MR</span>
                <span class="badge" style="background:#D1FAE5; color:#059669;">Terkoneksi (gviz CSV)</span>
            </div>

            <div class="mt-3 p-3" style="background:var(--secondary); border-radius:10px; font-size:11.5px; color:var(--ink-500); line-height:1.5;">
                <i class="bi bi-info-circle-fill" style="color:var(--primary);"></i>
                <strong>Catatan Integrasi:</strong> Data Report PRQ yang diperbarui akan otomatis mempengaruhi metrik di <em>Dashboard</em>, <em>PTP Monitoring</em>, <em>Risk Score</em>, dan <em>Customer 360</em>.
            </div>

            <div class="mt-3">
                <a href="{{ url('/settings') }}" class="btn btn-outline-secondary btn-sm w-100" style="border-radius:8px; font-weight:600; font-size:12px;">
                    <i class="bi bi-gear-fill"></i> Konfigurasi Link Spreadsheet
                </a>
            </div>
        </div>
    </div>
{{-- ERROR ALERT CONTAINER --}}
<div id="syncErrorBanner" class="alert alert-danger align-items-start gap-3 mb-4" style="display:none; border-radius:12px; border:1px solid #FCA5A5;">
    <i class="bi bi-exclamation-octagon-fill fs-4 mt-1" style="color:var(--danger); flex-shrink:0;"></i>
    <div style="flex:1;">
        <div style="font-weight:700; font-size:14px; margin-bottom:2px;" id="errorBannerTitle">Sinkronisasi Gagal</div>
        <div style="font-size:13px; line-height:1.4; color:#7F1D1D;" id="errorBannerMessage">Terjadi kesalahan saat memproses data.</div>
        <div id="errorBannerDetails" style="font-size:11.5px; color:#991B1B; margin-top:6px; font-family:monospace; display:none; background:rgba(255,255,255,0.7); padding:6px 10px; border-radius:6px;"></div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('syncErrorBanner').style.display='none'" style="border-radius:6px; font-size:11.5px;">Tutup</button>
</div>

</div>

@endsection

@push('scripts')
<script>
async function triggerMasterSync() {
    const btn = document.getElementById('btnSyncMaster');
    const icon = document.getElementById('masterSyncIcon');
    const label = document.getElementById('masterSyncLabel');
    const liveStatus = document.getElementById('syncLiveStatus');
    const progressBar = document.getElementById('topProgressBar');
    const errorBanner = document.getElementById('syncErrorBanner');

    // Sembunyikan error sebelumnya jika ada
    if (errorBanner) {
        errorBanner.style.display = 'none';
    }

    // UI Loading State (mengikuti proses sebenarnya tanpa fake timer)
    btn.disabled = true;
    icon.classList.add('spinning');
    label.innerText = 'Syncing Data C3MR...';
    liveStatus.style.display = 'block';
    
    if (progressBar) {
        progressBar.className = 'loading';
    }

    // Ambil CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || '{{ csrf_token() }}';

    // Gunakan relative path /c3mr/sync/all agar tidak terjadi masalah port mismatch (misal port 8000)
    const syncEndpoint = '{{ url("/c3mr/sync/all", [], false) }}';

    try {
        const response = await fetch(syncEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _token: csrfToken
            })
        });

        const contentType = response.headers.get('content-type') || '';
        let data = {};

        if (contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const textResp = await response.text();
            throw new Error(`Server mengembalikan respon tidak valid (HTTP ${response.status}). ${textResp.slice(0, 150)}`);
        }

        if (!response.ok) {
            throw new Error(data.message || `Sinkronisasi gagal dengan kode status HTTP ${response.status}`);
        }

        // Update Timestamp Text
        if (data.last_sync_formatted) {
            const lastSyncEl = document.getElementById('lastSyncText');
            const resultTsEl = document.getElementById('resultTimestampText');
            if (lastSyncEl) lastSyncEl.innerText = data.last_sync_formatted;
            if (resultTsEl) resultTsEl.innerText = data.last_sync_formatted;
        }

        // Update Status Box
        const statusBox = document.getElementById('syncResultContainer');
        if (statusBox) statusBox.style.display = 'block';

        const isSuccess = data.status === 'success';
        const isWarning = data.status === 'warning';

        const statusIconEl = document.getElementById('resultStatusIcon');
        const statusTitleEl = document.getElementById('resultStatusTitle');

        if (statusIconEl && statusTitleEl) {
            if (isSuccess) {
                statusIconEl.innerHTML = '<i class="bi bi-check-circle-fill fs-5" style="color:var(--success);"></i>';
                statusTitleEl.innerText = '✓ ' + (data.status_label || 'Sinkronisasi berhasil');
            } else if (isWarning) {
                statusIconEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5" style="color:var(--warning);"></i>';
                statusTitleEl.innerText = '⚠ ' + (data.status_label || 'Sinkronisasi selesai dengan beberapa masalah');
            } else {
                statusIconEl.innerHTML = '<i class="bi bi-x-circle-fill fs-5" style="color:var(--danger);"></i>';
                statusTitleEl.innerText = '✕ ' + (data.status_label || 'Sinkronisasi gagal');
            }
        }

        if (data.total_rows_processed !== undefined) {
            const countProcessedEl = document.getElementById('resultProcessedCount');
            if (countProcessedEl) {
                countProcessedEl.innerText = Number(data.total_rows_processed).toLocaleString('id-ID');
            }
        }

        // Update details per source
        if (data.details) {
            const d = data.details;
            if (d.report_prq) updateSourceCard('report_prq', d.report_prq);
            if (d.viseepro) updateSourceCard('viseepro', d.viseepro);
            if (d.data_all) updateSourceCard('data_all', d.data_all);
            if (d.caring) updateSourceCard('caring', d.caring);
            if (d.performance) updateSourceCard('performance', d.performance);
            if (d.ar_agents) updateSourceCard('ar_agents', d.ar_agents);
        }

        // Update Master KPI numbers jika ada update total
        if (data.details?.data_all?.total) {
            const kpiCust = document.getElementById('kpiCustomers');
            if (kpiCust) kpiCust.innerText = Number(data.details.data_all.total).toLocaleString('id-ID');
        }
        if (data.details?.caring?.total) {
            const kpiCar = document.getElementById('kpiCaring');
            if (kpiCar) kpiCar.innerText = Number(data.details.caring.total).toLocaleString('id-ID');
        }

        // Scroll to results smoothly
        if (statusBox) {
            statusBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

    } catch (err) {
        console.error('C3MR Sync Error:', err);

        // Tampilkan Error Banner yang informatif di halaman
        if (errorBanner) {
            const titleEl = document.getElementById('errorBannerTitle');
            const msgEl = document.getElementById('errorBannerMessage');
            const detEl = document.getElementById('errorBannerDetails');

            let userMsg = err.message || 'Terjadi kesalahan tidak terduga';
            if (err.name === 'TypeError' && err.message.includes('fetch')) {
                userMsg = 'Gagal menghubungi server aplikasi. Pastikan koneksi web server aktif dan dapat diakses.';
            }

            if (titleEl) titleEl.innerText = 'Sinkronisasi Gagal';
            if (msgEl) msgEl.innerText = userMsg;
            if (detEl) {
                detEl.innerText = `Detail: ${err.message}`;
                detEl.style.display = 'block';
            }
            errorBanner.style.display = 'flex';
            errorBanner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            alert('Gagal melakukan sinkronisasi: ' + err.message);
        }
    } finally {
        // Reset UI State
        btn.disabled = false;
        icon.classList.remove('spinning');
        label.innerText = 'Sync Data C3MR';
        liveStatus.style.display = 'none';
        
        if (progressBar) {
            progressBar.className = 'finish';
            setTimeout(() => { progressBar.className = ''; }, 300);
        }
    }
}

function updateSourceCard(key, item) {
    const badgeEl = document.getElementById('badge_' + key);
    const countEl = document.getElementById('count_' + key);
    const msgEl = document.getElementById('msg_' + key);

    if (badgeEl) {
        badgeEl.className = 'source-badge ' + (item.success ? 'source-badge-success' : 'source-badge-error');
        badgeEl.innerText = item.success ? '✓ Berhasil' : '✕ Gagal';
    }
    if (countEl && item.count !== undefined) {
        countEl.innerHTML = Number(item.count).toLocaleString('id-ID') + ' <span style="font-size:12px; font-weight:500; color:var(--ink-500);">records</span>';
    }
    if (msgEl && item.message) {
        msgEl.innerText = item.message;
    }
}
</script>
@endpush
