@extends('layouts.app')

@section('title', 'Sync Data C3MR')
@section('subtitle', 'Pusat integrasi satu pintu data Google Spreadsheet C3MR')

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

{{-- 1. MASTER HERO SYNC DATA C3MR SECTION --}}
<div class="card sync-hero-card mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge" style="background:#FDEBEC; color:#B8000F; font-weight:700; font-size:11px; padding:5px 10px; border-radius:6px;">
                    <i class="bi bi-lightning-charge-fill"></i> SINKRONISASI SATU PINTU
                </span>
                <span class="badge" style="background:#D1FAE5; color:#059669; font-weight:600; font-size:11px; padding:5px 10px; border-radius:6px;">
                    <i class="bi bi-check-circle-fill"></i> Status: Connected
                </span>
                <span class="badge" style="background:#EFF6FF; color:#2563EB; font-weight:600; font-size:11px; padding:5px 10px; border-radius:6px;" id="lastSyncBadge">
                    <i class="bi bi-clock-history"></i> Last Sync: <span id="lastSyncText">{{ $lastSyncFormatted }}</span>
                </span>
            </div>
            <h4 style="font-weight:800; color:var(--ink-900); margin-bottom:6px; font-size:22px;">
                Sync Data C3MR
            </h4>
            <p style="color:var(--ink-500); font-size:13.5px; margin-bottom:0; line-height:1.5;">
                Perbarui seluruh data collection dari Spreadsheet C3MR. Sistem akan secara otomatis menyinkronkan seluruh dataset collection, kunjungan, caring, dan profil pelanggan.
            </p>
        </div>
        <div class="col-lg-5 text-lg-end text-start">
            <button type="button" id="btnSyncMaster" class="btn-sync-hero w-100 w-lg-auto" onclick="triggerMasterSync()">
                <i class="bi bi-arrow-repeat sync-icon fs-5" id="masterSyncIcon"></i>
                <span id="masterSyncLabel">Sync Data C3MR</span>
            </button>
            <div style="font-size:12px; color:var(--ink-500); margin-top:8px; font-weight:500;">
                <i class="bi bi-shield-check text-success"></i> Memperbarui data dari Spreadsheet C3MR Terpusat
            </div>
        </div>
    </div>

    {{-- LIVE SYNC STATUS & PROGRESS INDICATOR --}}
    <div id="syncLiveStatus" class="mt-4 pt-3" style="display:none; border-top:1px dashed #FCA5A5;">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="spinner-border spinner-border-sm text-danger mt-1" role="status" id="syncSpinner"></div>
            <div style="flex:1;">
                <strong style="color:var(--primary); font-size:13.5px;" id="syncProgressLabel">Memulai sinkronisasi...</strong>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:2px;" id="syncProgressSub">Proses ini membutuhkan waktu 2–3 menit. Halaman tidak perlu di-refresh.</div>
            </div>
        </div>
        {{-- Progress bar --}}
        <div style="background:#FEE2E2; border-radius:8px; overflow:hidden; height:8px; margin-bottom:6px;">
            <div id="syncProgressBar" style="height:100%; background:linear-gradient(90deg,#E2001A,#F59E0B); border-radius:8px; width:3%; transition:width .4s ease;"></div>
        </div>
        <div style="font-size:11px; color:var(--ink-400); text-align:right;" id="syncProgressPct">3%</div>
        {{-- Live step log --}}
        <div id="syncStepLog" style="margin-top:10px; max-height:110px; overflow-y:auto; background:#FFF5F5; border:1px solid #FCA5A5; border-radius:8px; padding:8px 12px; font-size:11.5px; color:#7F1D1D; font-family:monospace; line-height:1.6;"></div>
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

    {{-- DETAILED BREAKDOWN OF ALL DATA SOURCES --}}
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

{{-- 4. SPREADSHEET CONFIGURATION BANNER --}}
<div class="card">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary); width:40px; height:40px; border-radius:10px;">
                <i class="bi bi-gear-fill fs-5"></i>
            </div>
            <div>
                <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Konfigurasi Sumber Spreadsheet C3MR</div>
                <div style="font-size:12px; color:var(--ink-500);">
                    Spreadsheet ID Aktif: <code style="color:var(--primary); font-weight:600;">{{ \App\Services\C3mrSyncService::getActiveSpreadsheetId() }}</code>
                </div>
            </div>
        </div>
        <div>
            <a href="{{ url('/settings') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" style="border-radius:8px; font-weight:600; font-size:12.5px;">
                <i class="bi bi-sliders"></i> Ubah Link Spreadsheet
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ─── SSE-based Sync (Server-Sent Events) ────────────────────────────────────
// Menggunakan streaming response.body agar tidak timeout di Railway.
// Server mengirim progress real-time; browser memperbarui UI tanpa blocking.

function triggerMasterSync() {
    const btn          = document.getElementById('btnSyncMaster');
    const icon         = document.getElementById('masterSyncIcon');
    const label        = document.getElementById('masterSyncLabel');
    const liveStatus   = document.getElementById('syncLiveStatus');
    const topBar       = document.getElementById('topProgressBar');
    const errorBanner  = document.getElementById('syncErrorBanner');
    const progressBar  = document.getElementById('syncProgressBar');
    const progressPct  = document.getElementById('syncProgressPct');
    const progressLbl  = document.getElementById('syncProgressLabel');
    const stepLog      = document.getElementById('syncStepLog');

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    const resetUI = () => {
        btn.disabled = false;
        icon.classList.remove('spinning');
        label.innerText = 'Sync Data C3MR';
        liveStatus.style.display = 'none';
        if (topBar) { topBar.className = 'finish'; setTimeout(() => { topBar.className = ''; }, 300); }
    };

    const addLog = (msg) => {
        if (!stepLog) return;
        const line = document.createElement('div');
        const ts   = new Date().toLocaleTimeString('id-ID');
        line.textContent = `[${ts}] ${msg}`;
        stepLog.appendChild(line);
        stepLog.scrollTop = stepLog.scrollHeight;
    };

    const setProgress = (pct, msg) => {
        if (progressBar) progressBar.style.width = Math.min(pct, 100) + '%';
        if (progressPct) progressPct.textContent = Math.min(pct, 100) + '%';
        if (progressLbl && msg) progressLbl.textContent = msg;
        addLog(msg || '');
    };

    const showError = (msg, detail) => {
        if (!errorBanner) { alert('Gagal: ' + msg); return; }
        const titleEl = document.getElementById('errorBannerTitle');
        const msgEl   = document.getElementById('errorBannerMessage');
        const detEl   = document.getElementById('errorBannerDetails');
        if (titleEl) titleEl.innerText = 'Sinkronisasi Gagal';
        if (msgEl)   msgEl.innerText   = msg || 'Terjadi kesalahan tidak terduga.';
        if (detEl && detail) { detEl.textContent = detail; detEl.style.display = 'block'; }
        else if (detEl) detEl.style.display = 'none';
        errorBanner.style.display = 'flex';
        errorBanner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    // ── Init UI ──
    if (errorBanner) errorBanner.style.display = 'none';
    btn.disabled = true;
    icon.classList.add('spinning');
    label.innerText = 'Menyinkronkan...';
    liveStatus.style.display = 'block';
    if (stepLog) stepLog.innerHTML = '';
    if (topBar) topBar.className = 'loading';
    setProgress(3, 'Menghubungi server...');

    // ── POST dengan Accept: text/event-stream → server returns SSE stream ──
    fetch('/c3mr/sync/all', {
        method: 'POST',
        headers: {
            'Accept': 'text/event-stream',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ _token: csrf }),
    }).then(response => {
        if (!response.ok && !response.body) {
            return response.text().then(t => {
                throw new Error(`Server error HTTP ${response.status}: ${t.slice(0, 200)}`);
            });
        }

        // Baca SSE stream via response.body reader
        const reader  = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer    = '';

        const processChunk = (chunk) => {
            buffer += chunk;
            const blocks = buffer.split('\n\n');
            buffer = blocks.pop(); // simpan yang belum selesai

            for (const block of blocks) {
                if (!block.trim()) continue;
                let eventName = 'message', dataStr = '';
                for (const line of block.split('\n')) {
                    if (line.startsWith('event: ')) eventName = line.slice(7).trim();
                    if (line.startsWith('data: '))  dataStr   = line.slice(6).trim();
                }
                if (!dataStr) continue;
                let data;
                try { data = JSON.parse(dataStr); } catch { continue; }

                if (eventName === 'progress') {
                    setProgress(data.pct || 0, data.message);
                } else if (eventName === 'complete') {
                    handleSyncComplete(data);
                } else if (eventName === 'error') {
                    showError(data.message, data.file ? 'File: ' + data.file : null);
                    resetUI();
                }
            }
        };

        const pump = () => reader.read().then(({ done, value }) => {
            if (done) { resetUI(); return; }
            processChunk(decoder.decode(value, { stream: true }));
            pump();
        }).catch(err => {
            console.warn('Stream read error:', err);
            resetUI();
        });

        pump();

    }).catch(err => {
        console.error('C3MR Sync fetch error:', err);
        let msg = err.message || 'Terjadi kesalahan tidak terduga';
        if (msg.toLowerCase().includes('wsarecv') || msg.toLowerCase().includes('aborted') || msg.toLowerCase().includes('connection')) {
            msg = 'Koneksi ke Google Spreadsheet terputus saat mengunduh data. Coba lagi — proses akan menggunakan data cache jika tersedia.';
        } else if (msg.includes('fetch') || msg.includes('network')) {
            msg = 'Gagal menghubungi server. Pastikan koneksi internet aktif.';
        }
        showError(msg, 'Detail teknis: ' + err.message);
        resetUI();
    });
}

function handleSyncComplete(data) {
    const liveStatus  = document.getElementById('syncLiveStatus');
    const statusBox   = document.getElementById('syncResultContainer');
    const errorBanner = document.getElementById('syncErrorBanner');
    const progressBar = document.getElementById('syncProgressBar');
    const topBar      = document.getElementById('topProgressBar');

    if (progressBar) progressBar.style.width = '100%';
    const pctEl = document.getElementById('syncProgressPct');
    if (pctEl) pctEl.textContent = '100%';
    const lblEl = document.getElementById('syncProgressLabel');
    if (lblEl) lblEl.textContent = data.status_label || 'Selesai';

    setTimeout(() => {
        const btn = document.getElementById('btnSyncMaster');
        const icon = document.getElementById('masterSyncIcon');
        const label = document.getElementById('masterSyncLabel');
        if (btn)   btn.disabled = false;
        if (icon)  icon.classList.remove('spinning');
        if (label) label.innerText = 'Sync Data C3MR';
        if (liveStatus) liveStatus.style.display = 'none';
        if (topBar) { topBar.className = 'finish'; setTimeout(() => { topBar.className = ''; }, 300); }

        if (statusBox) statusBox.style.display = 'block';

        // Timestamp
        if (data.last_sync_formatted) {
            ['lastSyncText','resultTimestampText'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = data.last_sync_formatted;
            });
        }

        // Status icon & title
        const isSuccess = data.status === 'success';
        const isWarning = data.status === 'warning';
        const iconEl    = document.getElementById('resultStatusIcon');
        const titleEl   = document.getElementById('resultStatusTitle');
        if (iconEl) {
            iconEl.innerHTML = isSuccess
                ? '<i class="bi bi-check-circle-fill fs-5" style="color:var(--success);"></i>'
                : (isWarning
                    ? '<i class="bi bi-exclamation-triangle-fill fs-5" style="color:var(--warning);"></i>'
                    : '<i class="bi bi-x-circle-fill fs-5" style="color:var(--danger);"></i>');
        }
        if (titleEl) {
            const pfx = isSuccess ? '✓ ' : (isWarning ? '⚠ ' : '✕ ');
            titleEl.innerText = pfx + (data.status_label || 'Selesai');
        }

        // Total processed
        const cntEl = document.getElementById('resultProcessedCount');
        if (cntEl && data.total_rows_processed !== undefined) {
            cntEl.innerText = Number(data.total_rows_processed).toLocaleString('id-ID');
        }

        // Detail cards
        if (data.details) {
            ['report_prq','viseepro','data_all','caring','performance','ar_agents']
                .forEach(k => { if (data.details[k]) updateSourceCard(k, data.details[k]); });
        }

        // KPI counters
        if (data.details?.data_all?.total)   { const el = document.getElementById('kpiCustomers'); if (el) el.innerText = Number(data.details.data_all.total).toLocaleString('id-ID'); }
        if (data.details?.caring?.total)     { const el = document.getElementById('kpiCaring');    if (el) el.innerText = Number(data.details.caring.total).toLocaleString('id-ID'); }
        if (data.details?.report_prq?.count) { const el = document.getElementById('kpiVisits');    if (el) el.innerText = Number(data.details.report_prq.count).toLocaleString('id-ID'); }
        if (data.details?.viseepro?.count)   { const el = document.getElementById('kpiViseepro');  if (el) el.innerText = Number(data.details.viseepro.count).toLocaleString('id-ID'); }

        // Durasi di sub-title
        const subEl = document.getElementById('resultStatusSub');
        if (subEl && data.duration_seconds) {
            const ts = data.last_sync_formatted || '';
            subEl.innerHTML = `Terakhir diperbarui: <span id="resultTimestampText">${ts}</span> &mdash; Waktu sync: <strong>${data.duration_seconds}s</strong>`;
        }

        if (statusBox) statusBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 500);
}

function updateSourceCard(key, item) {
    const badgeEl = document.getElementById('badge_' + key);
    const countEl = document.getElementById('count_' + key);
    const msgEl   = document.getElementById('msg_'   + key);
    if (badgeEl) {
        badgeEl.className = 'source-badge ' + (item.success ? 'source-badge-success' : 'source-badge-error');
        badgeEl.innerText = item.success ? '✓ Berhasil' : '✕ Gagal';
    }
    if (countEl && item.count !== undefined) {
        countEl.innerHTML = Number(item.count).toLocaleString('id-ID')
            + ' <span style="font-size:12px;font-weight:500;color:var(--ink-500);">records</span>';
    }
    if (msgEl && item.message) msgEl.innerText = item.message;
}
</script>
@endpush

