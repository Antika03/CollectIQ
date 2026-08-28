@extends('layouts.app')

@section('title', 'AR Agents Performance')
@section('subtitle', 'Monitoring performansi dan produktivitas tim Account Receivable (AR) Lapangan')

@section('content')

{{-- 1. KPI SUMMARY CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total AR Agent</div>
                <div class="kpi-value">{{ number_format($totalAgents) }}</div>
                <div class="kpi-sub">Master petugas collection</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-person-badge-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Agent Aktif</div>
                <div class="kpi-value" style="color:var(--success);">{{ number_format($activeAgents) }}</div>
                <div class="kpi-sub"><i class="bi bi-check-circle-fill"></i> Siap penugasan visit</div>
            </div>
            <div class="kpi-icon" style="background:var(--success-soft); color:var(--success);">
                <i class="bi bi-person-check-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Kunjungan Visit</div>
                <div class="kpi-value">{{ number_format($totalVisits) }}</div>
                <div class="kpi-sub">Seluruh riwayat penagihan</div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#2563EB;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">PTP Terkumpul</div>
                <div class="kpi-value" style="color:var(--warning);">{{ number_format($totalPtp) }}</div>
                <div class="kpi-sub">
                    {{ $totalVisits > 0 ? round(($totalPtp / $totalVisits) * 100, 1) : 0 }}% PTP Conversion Rate
                </div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>
</div>

{{-- 2. SEARCH & ACTION BAR --}}
<div class="filter-bar mb-3">
    <form method="GET" action="{{ url('/ar-agents') }}" class="d-flex w-100 gap-2 flex-wrap align-items-center">
        <div class="flex-grow-1" style="min-width:220px;">
            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                   placeholder="Cari nama AR Agent...">
        </div>
        <button type="submit" class="btn btn-primary-telkom" style="height:31px; font-size:12.5px; padding:4px 14px;">
            <i class="bi bi-search"></i> Cari
        </button>
        @if($search)
            <a href="{{ url('/ar-agents') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; height:31px;">Reset</a>
        @endif
        <a href="{{ route('ar-agents.export', request()->query()) }}" class="btn btn-sm" style="background:var(--success-soft); color:var(--success); border:1px solid rgba(22,163,74,0.3); font-weight:600; border-radius:8px; height:31px; display:inline-flex; align-items:center; gap:5px;">
            <i class="bi bi-file-earmark-excel"></i> Export CSV
        </a>
    </form>
</div>

{{-- 3. AR AGENTS LEADERBOARD TABLE --}}
<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Klasemen &amp; Produktivitas AR Agent</div>
            <div style="font-size:11.5px; color:var(--ink-400);">Daftar seluruh petugas collection lapangan</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">Rank</th>
                    <th>Nama AR Agent</th>
                    <th style="text-align:center;">Total Visit</th>
                    <th style="text-align:center;">Pelanggan Unik</th>
                    <th style="text-align:center;">Contacted</th>
                    <th style="text-align:center;">PTP</th>
                    <th style="width:160px;">PTP Rate</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $idx => $agent)
                    <tr>
                        <td style="text-align:center;">
                            @php $rank = ($agents->firstItem() ?? 1) + (int)$idx; @endphp
                            <span class="rank-pill rank-{{ $rank <= 3 ? $rank : 'other' }}" style="margin:0 auto;">
                                {{ $rank }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle">
                                    {{ strtoupper(substr($agent->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:700; color:var(--ink-900);">
                                        {{ $agent->name }}
                                    </div>
                                    @if($agent->chat_id_telegram)
                                        <div style="font-size:11px; color:#227093;">
                                            <i class="bi bi-telegram"></i> Telegram connected
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center; font-weight:800; font-size:14px; color:var(--ink-900);">
                            {{ $agent->visits_count }}
                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--ink-700);">
                            {{ $agent->unique_customers ?? 0 }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-status badge-contacted">
                                {{ $agent->contacted_count }} ({{ $agent->contacted_rate }}%)
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-status badge-ptp">
                                {{ $agent->ptp_count }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="flex:1; height:6px; background:var(--secondary); border-radius:99px; overflow:hidden;">
                                    <div style="width:{{ $agent->ptp_rate }}%; height:100%; background:linear-gradient(90deg, #F59E0B, #D97706); border-radius:99px;"></div>
                                </div>
                                <span style="font-size:11.5px; font-weight:700; color:var(--warning); min-width:36px; text-align:right;">
                                    {{ $agent->ptp_rate }}%
                                </span>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            @if($agent->is_active)
                                <span class="badge-status" style="background:#D1FAE5; color:#065F46;">Aktif</span>
                            @else
                                <span class="badge-status" style="background:var(--secondary); color:var(--ink-400);">Non-Aktif</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ url('/visits?ar_agent_id=' . $agent->id) }}" class="btn btn-sm btn-outline-telkom"
                                   style="font-size:11.5px; padding:3px 8px; white-space:nowrap;"
                                   title="Lihat Rekap Kunjungan Visit Petugas" data-bs-toggle="tooltip">
                                    <i class="bi bi-geo-alt"></i> Visit
                                </a>
                                @if($agent->chat_id_telegram)
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            style="font-size:11.5px; padding:3px 8px; white-space:nowrap; background:#EFF6FF; color:#1D4ED8; border-color:#BFDBFE;"
                                            onclick="openTelegramChatModal({{ $agent->id }}, '{{ addslashes($agent->name) }}', '{{ $agent->chat_id_telegram }}')"
                                            title="Kirim Pesan Telegram Langsung">
                                        <i class="bi bi-telegram"></i> Chat
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-person-x"></i> Tidak ada data AR Agent ditemukan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:14px 20px; border-top:1px solid var(--border);">
        {{ $agents->links() }}
    </div>
</div>

{{-- MODAL CHAT TELEGRAM --}}
<div class="modal fade" id="modalTelegramChat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background:#0F172A; color:#FFFFFF; border-radius:14px 14px 0 0; padding:16px 20px;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-telegram" style="color:#38BDF8; font-size:20px;"></i>
                    <div>
                        <h6 class="modal-title mb-0" id="telegramModalTitle" style="font-weight:700; font-size:15px;">Kirim Pesan Telegram</h6>
                        <small style="color:#94A3B8; font-size:11.5px;">Notifikasi instan ke akun Telegram AR Agent</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('telegram.send') }}" method="POST" id="formTelegramChat">
                @csrf
                <input type="hidden" name="ar_agent_id" id="chatArAgentId">
                <div class="modal-body" style="padding:20px;">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:var(--ink-700);">Target AR Agent</label>
                        <input type="text" id="chatArAgentName" class="form-control form-control-sm" readonly style="background:#F1F5F9; font-weight:600;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:var(--ink-700);">Pesan Telegram</label>
                        <textarea name="message" id="chatMessage" rows="5" class="form-control" placeholder="Ketik pesan atau instruksi collection di sini..." required style="font-size:13px; border-radius:8px;"></textarea>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        <button type="button" class="btn btn-sm btn-light" style="font-size:11px; border:1px solid #E2E8F0;" onclick="insertQuickChat('reminder')">⚡ Quick: Reminder Visit</button>
                        <button type="button" class="btn btn-sm btn-light" style="font-size:11px; border:1px solid #E2E8F0;" onclick="insertQuickChat('ptp')">💰 Quick: Follow-up PTP</button>
                    </div>
                </div>
                <div class="modal-footer" style="background:#F8FAFC; border-top:1px solid var(--border); border-radius:0 0 14px 14px; padding:12px 20px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" style="background:#0284C7; border:none; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                        <i class="bi bi-send-fill"></i> Kirim ke Telegram
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTelegramChatModal(agentId, agentName, chatId) {
    document.getElementById('chatArAgentId').value = agentId;
    document.getElementById('chatArAgentName').value = agentName + ' (Chat ID: ' + chatId + ')';
    document.getElementById('chatMessage').value = "Halo Kak " + agentName + ",\n\nMohon lakukan follow-up dan pengecekan tagihan pelanggan hari ini. Terima kasih!";
    const modal = new bootstrap.Modal(document.getElementById('modalTelegramChat'));
    modal.show();
}

function insertQuickChat(type) {
    const name = document.getElementById('chatArAgentName').value.split(' (')[0];
    if (type === 'reminder') {
        document.getElementById('chatMessage').value = "Halo Kak " + name + ",\n\n📋 Reminder: Jangan lupa melakukan kunjungan visit dan update hasil collection pada pelanggan prioritas hari ini. Semangat!";
    } else if (type === 'ptp') {
        document.getElementById('chatMessage').value = "Halo Kak " + name + ",\n\n🤝 Follow-up: Mohon cek komitmen janji bayar (PTP) yang jatuh tempo hari ini dan lakukan konfirmasi pembayaran.";
    }
}
</script>

@endsection
