@extends('layouts.app')

@section('title', 'Sinkronisasi Data Terpusat')
@section('subtitle', 'Integrasi Satu Pintu Google Spreadsheet PRITI DATA & C3MR Intelligence')

@section('content')
<div class="d-flex flex-column gap-4">

    {{-- KARTU UTAMA SINGLE SYNC (SATU PINTU) --}}
    <div class="card" style="background: linear-gradient(135deg, #FFF8F8 0%, #FFFFFF 100%); border-left: 4px solid var(--primary);">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div>
                <div class="section-title d-flex align-items-center gap-2">
                    <span class="avatar-circle" style="width:32px; height:32px; font-size:14px; background:var(--primary-soft); color:var(--primary-dark);">
                        <i class="bi bi-arrow-repeat"></i>
                    </span>
                    <span>Sinkronisasi Satu Pintu: PRITI DATA + C3MR</span>
                </div>
                <div class="section-sub mt-1">
                    Sekali klik untuk memproses master pelanggan (~27.000 records), kunjungan PRITI, caring OBC, performansi, dan konsolidasi AR.
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" id="btnSyncAll" class="btn btn-primary-telkom" style="padding: 10px 22px; font-size: 14px;" onclick="startSingleSync()">
                    <i class="bi bi-cloud-arrow-down-fill me-1"></i>
                    <span id="btnSyncText">SYNC SEMUA DATA</span>
                </button>
            </div>
        </div>

        {{-- PROGRESS BAR & STREAMING STATUS --}}
        <div id="syncProgressContainer" class="p-3 rounded border mt-2" style="background:#FAFAFA; display:none;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span id="syncStepLabel" style="font-weight:700; font-size:13px; color:var(--ink-900);">
                    <span class="spinner-border spinner-border-sm text-danger me-2" role="status"></span>
                    Menyiapkan proses sinkronisasi...
                </span>
                <span id="syncPctLabel" style="font-weight:800; font-size:13px; color:var(--primary);">0%</span>
            </div>
            <div class="progress" style="height: 8px; border-radius: 99px; background: #E2E8F0;">
                <div id="syncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%;"></div>
            </div>
            <div id="syncLogText" style="font-size: 11.5px; color: var(--ink-500); margin-top: 6px; font-family: monospace;">
                Memulai request ke server...
            </div>
        </div>

        {{-- STATUS TERAKHIR & DATA QUALITY ALERT --}}
        <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted" style="font-size: 12px;">
            <div>
                <i class="bi bi-clock-history me-1"></i> Terakhir disinkronkan:
                <b id="lastSyncLabel" class="text-dark">{{ $lastSyncFormatted }}</b>
            </div>
            <div id="syncStatusBadge">
                @if($lastSyncStatus === 'success')
                    <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Data Integrity OK</span>
                @elseif($lastSyncStatus === 'warning')
                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill"></i> Catatan Kualitas Data</span>
                @elseif($lastSyncStatus === 'error')
                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> Gagal / Selisih Data</span>
                @endif
            </div>
        </div>
    </div>

    {{-- HASIL DIAGNOSTIC DATA QUALITY --}}
    <div id="diagnosticCard" class="card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <div class="section-title">
                    <i class="bi bi-clipboard2-data-fill text-primary me-2"></i> Laporan Diagnostik & Integritas Data (Data Quality)
                </div>
                <div class="section-sub">
                    Transparansi jumlah data dari sheet sumber Google Spreadsheet ke database aktual
                </div>
            </div>
            <span class="badge" style="background: #F1F5F9; color: var(--ink-700); font-size: 11.5px;">
                Master Source: DATA ALL
            </span>
        </div>

        @php
            $dq = $lastSyncResult['_data_quality'] ?? [];
            $sourceRows = $dq['source_sheet_rows'] ?? 27547;
            $dbCustomers = $dq['database_total_customers'] ?? $totalCustomers;
            $diff = $dq['difference'] ?? abs($sourceRows - $dbCustomers);
            $isConsistent = $dq['is_consistent'] ?? ($dbCustomers > 20000);
        @endphp

        <div class="row g-3">
            {{-- DATA ALL METRICS --}}
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-3 rounded border" style="background: #F8FAFC;">
                    <div class="kpi-label">C3MR DATA ALL (Source)</div>
                    <div class="kpi-value" id="dqSourceRows" style="font-size: 22px; color: var(--ink-900);">
                        {{ number_format($sourceRows, 0, ',', '.') }}
                    </div>
                    <div class="kpi-sub">Total baris master di spreadsheet</div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-3 rounded border" style="background: #F8FAFC;">
                    <div class="kpi-label">Database Customers</div>
                    <div class="kpi-value" id="dqDbCustomers" style="font-size: 22px; color: #16A34A;">
                        {{ number_format($dbCustomers, 0, ',', '.') }}
                    </div>
                    <div class="kpi-sub">Master pelanggan tersimpan di database</div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-3 rounded border" style="background: #F8FAFC;">
                    <div class="kpi-label">Pelanggan PRANPC</div>
                    <div class="kpi-value" id="dqPranpc" style="font-size: 22px; color: #D97706;">
                        {{ number_format($totalPranpc, 0, ',', '.') }}
                    </div>
                    <div class="kpi-sub">Kategori tagihan PRANPC</div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-3 rounded border" style="background: #F8FAFC;">
                    <div class="kpi-label">Master Personil AR</div>
                    <div class="kpi-value" id="dqArCount" style="font-size: 22px; color: #2563EB;">
                        {{ number_format($totalArAgents, 0, ',', '.') }}
                    </div>
                    <div class="kpi-sub">Terkonsolidasi & non-AR dibersihkan</div>
                </div>
            </div>
        </div>

        {{-- MESSAGE PANEL --}}
        <div id="dqMessageBox" class="alert {{ $isConsistent ? 'alert-success' : 'alert-warning' }} d-flex align-items-center gap-2 mt-3 mb-0" style="border-radius:10px; font-size:12.5px;">
            <i class="bi {{ $isConsistent ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
            <span id="dqMessageText">
                {{ $dq['consistency_message'] ?? "Data master pelanggan konsisten ({$dbCustomers} pelanggan terdaftar di database dari {$sourceRows} baris sumber)." }}
            </span>
        </div>
    </div>

    {{-- DETAIL PER SUMBER DATA --}}
    <div class="row g-4">
        {{-- SUMBER 1: PRITI DATA --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger" style="font-size:11px;">PRITI DATA</span>
                        <span style="font-weight:700; color:var(--ink-900); font-size:14px;">Sheet Collection</span>
                    </div>
                    <span class="badge bg-light text-dark" style="font-size:11px;">~3.500 Kunjungan</span>
                </div>
                <div style="font-size:12.5px; color:var(--ink-700); line-height:1.6;">
                    • <b>Kunjungan Lapangan:</b> Diimpor langsung tanpa copy-paste manual.<br>
                    • <b>Kontak Pelanggan:</b> Nomor HP update dan snapshot kunjungan dipetakan otomatis.<br>
                    • <b>Hasil & Kategori:</b> Dinormalisasi (Janji Bayar/PTP, PDK, Tolak Bayar, dll).<br>
                    • <b>Foto Bukti:</b> Tautan Google Drive foto terhubung ke detail customer.
                </div>
            </div>
        </div>

        {{-- SUMBER 2: C3MR SPREADSHEET --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary" style="font-size:11px;">C3MR</span>
                        <span style="font-weight:700; color:var(--ink-900); font-size:14px;">DATA ALL, Caring & Witel</span>
                    </div>
                    <span class="badge bg-light text-dark" style="font-size:11px;">~27.000 Master</span>
                </div>
                <div style="font-size:12.5px; color:var(--ink-700); line-height:1.6;">
                    • <b>Master Customer:</b> Seluruh pelanggan valid (26.500+) diimpor tanpa drop.<br>
                    • <b>PRANPC Flagging:</b> Deteksi kategori tagihan PRANPC otomatis.<br>
                    • <b>Hasil Caring OBC:</b> Log riwayat panggilan, status bayar & VOC.<br>
                    • <b>Performansi Witel:</b> CR%, CYC%, billing & saldo regional Priangan Timur.
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
let isSyncing = false;

function startSingleSync() {
    if (isSyncing) return;

    if (!confirm('Mulai proses Sinkronisasi Satu Pintu (PRITI DATA + C3MR)?\nProses akan membaca seluruh sumber spreadsheet Google dan memperbarui database.')) {
        return;
    }

    isSyncing = true;
    const btn = document.getElementById('btnSyncAll');
    const btnText = document.getElementById('btnSyncText');
    const progressContainer = document.getElementById('syncProgressContainer');
    const stepLabel = document.getElementById('syncStepLabel');
    const pctLabel = document.getElementById('syncPctLabel');
    const progressBar = document.getElementById('syncProgressBar');
    const logText = document.getElementById('syncLogText');

    btn.disabled = true;
    btnText.innerText = 'Menyinkronkan...';
    progressContainer.style.display = 'block';
    progressBar.style.width = '5%';
    pctLabel.innerText = '5%';

    // Gunakan Server-Sent Events (SSE) via EventSource POST polyfill atau streaming fetch
    fetch('{{ route("c3mr.sync.all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'text/event-stream',
        }
    }).then(response => {
        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let buffer = '';

        function readStream() {
            reader.read().then(({ done, value }) => {
                if (done) {
                    finishSync();
                    return;
                }

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop(); // simpan sisa yang belum lengkap

                let currentEvent = 'message';
                for (const line of lines) {
                    if (line.startsWith('event:')) {
                        currentEvent = line.substring(6).trim();
                    } else if (line.startsWith('data:')) {
                        try {
                            const data = JSON.parse(line.substring(5).trim());
                            handleSyncEvent(currentEvent, data);
                        } catch (e) {
                            // Abaikan baris parse error
                        }
                    }
                }

                readStream();
            }).catch(err => {
                handleSyncError('Koneksi streaming terputus: ' + err.message);
            });
        }

        readStream();
    }).catch(err => {
        handleSyncError('Gagal menghubungi server: ' + err.message);
    });
}

function handleSyncEvent(event, data) {
    const stepLabel = document.getElementById('syncStepLabel');
    const pctLabel = document.getElementById('syncPctLabel');
    const progressBar = document.getElementById('syncProgressBar');
    const logText = document.getElementById('syncLogText');

    if (event === 'progress') {
        stepLabel.innerHTML = '<span class="spinner-border spinner-border-sm text-danger me-2" role="status"></span> ' + data.message;
        logText.innerText = data.message;
        if (data.pct) {
            progressBar.style.width = data.pct + '%';
            pctLabel.innerText = data.pct + '%';
        }
    } else if (event === 'complete') {
        progressBar.style.width = '100%';
        pctLabel.innerText = '100%';
        stepLabel.innerHTML = '<i class="bi bi-check-circle-fill text-success me-2"></i> Sinkronisasi Selesai!';
        logText.innerText = 'Selesai dalam ' + data.duration_seconds + ' detik (' + (data.data_quality.database_total_customers || '') + ' pelanggan di database)';

        updateDiagnosticUI(data);
        finishSync();
    } else if (event === 'error') {
        handleSyncError(data.message || 'Terjadi kesalahan saat sinkronisasi.');
    }
}

function handleSyncError(msg) {
    const stepLabel = document.getElementById('syncStepLabel');
    const logText = document.getElementById('syncLogText');
    const progressBar = document.getElementById('syncProgressBar');

    progressBar.classList.remove('bg-danger');
    progressBar.classList.add('bg-secondary');
    stepLabel.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-2"></i> Sinkronisasi Gagal';
    logText.innerText = msg;
    finishSync();
}

function finishSync() {
    isSyncing = false;
    const btn = document.getElementById('btnSyncAll');
    const btnText = document.getElementById('btnSyncText');
    btn.disabled = false;
    btnText.innerText = 'SYNC SEMUA DATA';
}

function updateDiagnosticUI(payload) {
    const dq = payload.data_quality;
    if (!dq) return;

    document.getElementById('dqSourceRows').innerText = (dq.source_sheet_rows || 0).toLocaleString('id-ID');
    document.getElementById('dqDbCustomers').innerText = (dq.database_total_customers || 0).toLocaleString('id-ID');
    document.getElementById('dqPranpc').innerText = (dq.pranpc_customers || 0).toLocaleString('id-ID');
    document.getElementById('dqArCount').innerText = (dq.total_ar_agents || 0).toLocaleString('id-ID');
    document.getElementById('lastSyncLabel').innerText = payload.last_sync_formatted;

    const msgBox = document.getElementById('dqMessageBox');
    const msgText = document.getElementById('dqMessageText');
    const statusBadge = document.getElementById('syncStatusBadge');

    if (dq.is_consistent) {
        msgBox.className = 'alert alert-success d-flex align-items-center gap-2 mt-3 mb-0';
        msgText.innerText = dq.consistency_message;
        statusBadge.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Data Integrity OK</span>';
    } else {
        msgBox.className = 'alert alert-warning d-flex align-items-center gap-2 mt-3 mb-0';
        msgText.innerText = dq.consistency_message;
        statusBadge.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill"></i> Catatan Kualitas Data</span>';
    }
}
</script>
@endpush
