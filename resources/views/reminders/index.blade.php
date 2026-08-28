@extends('layouts.app')

@section('title', 'Reminder Center')
@section('subtitle', 'Pusat rekomendasi dan preview reminder tindak lanjut penagihan AR Lapangan')

@section('content')

{{-- 1. KPI SUMMARY CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <a href="{{ route('reminders.index', ['category' => 'unvisited']) }}" style="text-decoration:none;">
            <div class="card kpi-card h-100 {{ request('category') === 'unvisited' ? 'border-danger shadow-sm' : '' }}">
                <div>
                    <div class="kpi-label">Belum Visit</div>
                    <div class="kpi-value text-danger">{{ number_format($totalUnvisited, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Ada saldo &amp; belum ada visit</div>
                </div>
                <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                    <i class="bi bi-geo-fill"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('reminders.index', ['category' => 'revisit']) }}" style="text-decoration:none;">
            <div class="card kpi-card h-100 {{ request('category') === 'revisit' ? 'border-warning shadow-sm' : '' }}">
                <div>
                    <div class="kpi-label">Perlu Visit Ulang</div>
                    <div class="kpi-value text-warning">{{ number_format($totalRevisit, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Visit terakhir &gt; 14 hari</div>
                </div>
                <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('reminders.index', ['category' => 'ptp']) }}" style="text-decoration:none;">
            <div class="card kpi-card h-100 {{ request('category') === 'ptp' ? 'border-primary shadow-sm' : '' }}">
                <div>
                    <div class="kpi-label">Janji Bayar (PTP)</div>
                    <div class="kpi-value" style="color:var(--primary);">{{ number_format($totalPtp, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Monitoring komitmen bayar</div>
                </div>
                <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('reminders.index', ['priority' => 'high']) }}" style="text-decoration:none;">
            <div class="card kpi-card h-100 {{ request('priority') === 'high' ? 'border-danger shadow-sm' : '' }}">
                <div>
                    <div class="kpi-label">Prioritas Tinggi</div>
                    <div class="kpi-value" style="color:#B91C1C;">{{ number_format($totalHighRisk, 0, ',', '.') }}</div>
                    <div class="kpi-sub">High / Critical Churn Risk</div>
                </div>
                <div class="kpi-icon" style="background:#FEE2E2; color:#B91C1C;">
                    <i class="bi bi-shield-fill-exclamation"></i>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- 2. FILTER BAR & SEARCH --}}
<div class="filter-bar mb-4">
    <form method="GET" action="{{ route('reminders.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label mb-1" style="font-size:11.5px; font-weight:700; color:var(--ink-500); text-transform:uppercase;">Cari Pelanggan</label>
            <div class="position-relative">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nama / No Internet / HP..." style="padding-left:30px;">
                <i class="bi bi-search position-absolute top-50 translate-middle-y" style="left:10px; color:var(--ink-400); font-size:12px;"></i>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:11.5px; font-weight:700; color:var(--ink-500); text-transform:uppercase;">Kategori</label>
            <select name="category" class="form-select form-select-sm">
                <option value="all" {{ request('category') === 'all' || !request('category') ? 'selected' : '' }}>Semua Kategori</option>
                <option value="unvisited" {{ request('category') === 'unvisited' ? 'selected' : '' }}>Belum Visit</option>
                <option value="revisit" {{ request('category') === 'revisit' ? 'selected' : '' }}>Perlu Visit Ulang</option>
                <option value="ptp" {{ request('category') === 'ptp' ? 'selected' : '' }}>Janji Bayar (PTP)</option>
                <option value="ptp_overdue" {{ request('category') === 'ptp_overdue' ? 'selected' : '' }}>PTP Jatuh Tempo</option>
                <option value="outstanding" {{ request('category') === 'outstanding' ? 'selected' : '' }}>Piutang &gt; 500rb</option>
                <option value="follow_up" {{ request('category') === 'follow_up' ? 'selected' : '' }}>Perlu Follow-Up</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:11.5px; font-weight:700; color:var(--ink-500); text-transform:uppercase;">AR Agent</label>
            <select name="ar_agent_id" class="form-select form-select-sm">
                <option value="">Semua AR</option>
                @foreach($arAgents as $ag)
                    <option value="{{ $ag->id }}" {{ request('ar_agent_id') == $ag->id ? 'selected' : '' }}>{{ $ag->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:11.5px; font-weight:700; color:var(--ink-500); text-transform:uppercase;">Prioritas</label>
            <select name="priority" class="form-select form-select-sm">
                <option value="">Semua Prioritas</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>🔴 Tinggi</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>🟠 Sedang</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>🟢 Rendah</option>
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary-telkom btn-sm flex-grow-1" style="height:32px;">
                <i class="bi bi-funnel"></i> Terapkan
            </button>
            <a href="{{ route('reminders.index') }}" class="btn btn-light btn-sm border" style="height:32px;" title="Reset Filter">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
            <a href="{{ route('reminders.export.csv', request()->all()) }}" class="btn btn-outline-telkom btn-sm" style="height:32px; white-space:nowrap;" title="Export CSV">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </a>
        </div>
    </form>
</div>

{{-- 3. REMINDER TABLE --}}
<div class="card p-0" style="overflow:hidden;">
    <div class="p-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--ink-900);">
                <i class="bi bi-bell-fill" style="color:var(--primary); margin-right:6px;"></i> Daftar Rekomendasi Reminder Penagihan
            </div>
            <div style="font-size:12px; color:var(--ink-400); margin-top:2px;">
                Menampilkan {{ $reminders->firstItem() ?? 0 }}–{{ $reminders->lastItem() ?? 0 }} dari total {{ number_format($reminders->total(), 0, ',', '.') }} pelanggan
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-telkom btn-sm" id="btnCopySelected" onclick="copySelectedReminders()" style="display:none;">
                <i class="bi bi-copy"></i> Salin Terpilih (<span id="selectedCount">0</span>)
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-modern mb-0" id="reminderTable">
            <thead>
                <tr>
                    <th style="width:36px; text-align:center;">
                        <input type="checkbox" id="checkAll" class="form-check-input" onclick="toggleCheckAll(this)">
                    </th>
                    <th>Pelanggan</th>
                    <th>Nomor Internet</th>
                    <th>AR Agent</th>
                    <th style="text-align:right;">Saldo Piutang</th>
                    <th>Visit Terakhir</th>
                    <th>Hasil Visit</th>
                    <th style="text-align:center;">Prioritas</th>
                    <th>Rekomendasi Tindakan</th>
                    <th style="text-align:center; width:130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reminders as $idx => $c)
                    @php
                        $r = $c->reminder_data;
                    @endphp
                    <tr id="row-{{ $c->id }}">
                        <td style="text-align:center;">
                            <input type="checkbox" class="form-check-input row-check" value="{{ $c->id }}" onchange="updateSelectedCounter()">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <span style="font-weight:700; color:var(--ink-900);">{{ $c->nama_pelanggan }}</span>
                                <button type="button" class="btn btn-link p-0 text-muted" style="font-size:11px; line-height:1; opacity:0.6;" onclick="copyToClipboard('{{ addslashes($c->nama_pelanggan) }}', this)" title="Salin Nama" data-bs-toggle="tooltip">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>
                            <div style="font-size:11px; color:var(--ink-400);">{{ $c->datel ?: 'Wilayah Telkom' }}</div>
                        </td>
                        <td>
                            {{-- Masked Nomor Internet dengan toggle Eye --}}
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
                        <td>
                            <span style="font-weight:600; color:var(--ink-700);">{{ $r['ar_name'] }}</span>
                        </td>
                        <td style="text-align:right; font-weight:800; color:var(--danger); white-space:nowrap;">
                            Rp {{ number_format($c->saldo_piutang, 0, ',', '.') }}
                        </td>
                        <td style="white-space:nowrap; font-size:12px;">
                            {{ $r['last_visit_date_formatted'] }}
                        </td>
                        <td>
                            <div style="font-size:12px; font-weight:600; color:var(--ink-800);">{{ Str::limit($r['hasil_visit'], 22) }}</div>
                            <div style="font-size:10.5px; color:var(--ink-400);">{{ Str::limit($r['kategori_visit'], 18) }}</div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $r['priority_class'] }}" style="font-size:11px; border-radius:6px; padding:3px 8px;">
                                {{ $r['priority'] }}
                            </span>
                        </td>
                        <td style="font-size:11.5px; color:var(--ink-600); max-width:220px;">
                            {{ $r['recommendation'] }}
                        </td>
                        <td style="text-align:center;">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-primary-telkom" style="font-size:11px; padding:3px 9px;" onclick="openPreviewModal({{ $c->id }})" title="Preview &amp; Edit Pesan Reminder">
                                    <i class="bi bi-eye"></i> Preview
                                </button>
                                <a href="{{ route('customer.show', $c) }}" class="btn btn-sm btn-light border" style="font-size:11px; padding:3px 7px;" title="Customer 360">
                                    <i class="bi bi-person-vcard"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:8px; opacity:0.5;"></i>
                            Tidak ada data pelanggan yang sesuai dengan filter reminder yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($reminders->hasPages())
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div style="font-size:12px; color:var(--ink-500);">
                Halaman {{ $reminders->currentPage() }} dari {{ $reminders->lastPage() }}
            </div>
            <div>
                {{ $reminders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

{{-- 4. MODAL PREVIEW REMINDER --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid var(--border); padding:16px 22px;">
                <div>
                    <h6 class="modal-title mb-0" id="previewModalLabel" style="font-weight:800; color:var(--ink-900);">
                        <i class="bi bi-card-text" style="color:var(--primary); margin-right:6px;"></i> Preview Pesan Reminder Collection
                    </h6>
                    <div style="font-size:11.5px; color:var(--ink-500); margin-top:2px;">
                        Pesan siap disalin atau diteruskan secara manual ke AR Agent (Tanpa pengiriman otomatis)
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3" style="background:var(--secondary); border-radius:10px;">
                            <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase;">Penerima Reminder (AR)</div>
                            <div id="modalArName" style="font-size:14px; font-weight:800; color:var(--ink-900); margin-top:2px;">-</div>
                            <div id="modalArStatus" style="font-size:11px; margin-top:2px;" class="text-muted">Chat ID Direct Telegram: Belum Tersedia</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3" style="background:var(--secondary); border-radius:10px;">
                            <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase;">Pelanggan &amp; Saldo</div>
                            <div id="modalCustName" style="font-size:14px; font-weight:800; color:var(--ink-900); margin-top:2px;">-</div>
                            <div id="modalCustSaldo" style="font-size:12px; font-weight:700; color:var(--danger); margin-top:2px;">Rp 0</div>
                        </div>
                    </div>
                </div>

                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <label class="form-label mb-0" style="font-weight:700; font-size:12px; color:var(--ink-700);">Isi Pesan Reminder (Dapat diedit sebelum disalin):</label>
                    <span class="badge bg-light text-dark border" style="font-size:10.5px;">Plain Text / WA Ready</span>
                </div>
                <textarea id="modalReminderText" class="form-control" rows="11" style="font-family:monospace; font-size:12.5px; line-height:1.5; border-radius:10px;"></textarea>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="border-top:1px solid var(--border); padding:14px 22px;">
                <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Tutup
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-telkom btn-sm" onclick="copyModalMessage(this)">
                        <i class="bi bi-clipboard-check"></i> Salin Pesan (Copy)
                    </button>
                    <button type="button" class="btn btn-primary-telkom btn-sm" onclick="exportSingleReminder()">
                        <i class="bi bi-download"></i> Simpan File Teks
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentPreviewData = null;

function toggleCheckAll(master) {
    const checks = document.querySelectorAll('.row-check');
    checks.forEach(c => c.checked = master.checked);
    updateSelectedCounter();
}

function updateSelectedCounter() {
    const checks = document.querySelectorAll('.row-check:checked');
    const count = checks.length;
    const btn = document.getElementById('btnCopySelected');
    const counter = document.getElementById('selectedCount');
    if (counter) counter.textContent = count;
    if (btn) {
        btn.style.display = count > 0 ? 'inline-flex' : 'none';
    }
}

function openPreviewModal(customerId) {
    fetch('{{ route("reminders.preview") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ customer_id: customerId })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Gagal memuat preview reminder.');
            return;
        }
        currentPreviewData = data;
        document.getElementById('modalArName').textContent = data.customer.ar_name || 'Belum Ditugaskan';
        document.getElementById('modalCustName').textContent = data.customer.nama_pelanggan || '-';
        document.getElementById('modalCustSaldo').textContent = data.customer.saldo_formatted || 'Rp 0';
        document.getElementById('modalReminderText').value = data.message || '';

        const statusEl = document.getElementById('modalArStatus');
        if (data.customer.ar_has_chat_id) {
            statusEl.className = 'text-success';
            statusEl.innerHTML = '<i class="bi bi-check-circle-fill"></i> Direct Telegram Terhubung (' + data.customer.chat_id + ')';
        } else {
            statusEl.className = 'text-muted';
            statusEl.innerHTML = '<i class="bi bi-info-circle"></i> Direct Telegram Belum Aktif (Gunakan Copy manual)';
        }

        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan saat memuat preview reminder.');
    });
}

function copyModalMessage(btn) {
    const text = document.getElementById('modalReminderText').value;
    copyToClipboard(text, btn);
}

function exportSingleReminder() {
    const text = document.getElementById('modalReminderText').value;
    const custName = (currentPreviewData && currentPreviewData.customer) ? currentPreviewData.customer.nama_pelanggan.replace(/[^a-zA-Z0-9_-]/g, '_') : 'reminder';
    const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Reminder_' + custName + '_' + (new Date().toISOString().slice(0,10)) + '.txt';
    link.click();
}

function copySelectedReminders() {
    const checks = document.querySelectorAll('.row-check:checked');
    if (checks.length === 0) return;

    let combinedText = "=== DAFTAR REMINDER COLLECTION TELKOM ===\nTanggal: " + (new Date().toLocaleDateString('id-ID')) + "\nTotal: " + checks.length + " Pelanggan\n\n";

    let promises = [];
    checks.forEach(c => {
        promises.push(
            fetch('{{ route("reminders.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ customer_id: c.value })
            }).then(r => r.json())
        );
    });

    Promise.all(promises).then(results => {
        results.forEach((res, i) => {
            if (res.success) {
                combinedText += "--- Pelanggan #" + (i + 1) + " ---\n" + res.message + "\n\n";
            }
        });

        copyToClipboard(combinedText);
        alert(checks.length + ' reminder berhasil disalin ke clipboard!');
    }).catch(err => {
        console.error(err);
        alert('Gagal menyalin data terpilih.');
    });
}
</script>
@endpush
