@extends('layouts.app')
@section('title', 'Hasil Pencarian')
@section('subtitle', 'Hasil pencarian untuk: "' . $q . '"')

@section('content')

{{-- Search Header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <div>
        <div style="font-size:20px; font-weight:800; color:var(--ink-900);">
            Hasil Pencarian <span style="color:var(--primary);">"{{ $q }}"</span>
        </div>
        <div style="font-size:13px; color:var(--ink-400); margin-top:3px;">
            Ditemukan <strong>{{ $totalResults }}</strong> hasil dari database — Customers, Visits, Caring Logs, AR Agents
        </div>
    </div>
</div>

@if($totalResults === 0)
<div class="card">
    <div class="empty-state">
        <i class="bi bi-search"></i>
        Tidak ada hasil yang ditemukan untuk "<strong>{{ $q }}</strong>".<br>
        <div style="margin-top:10px; font-size:13px;">
            Coba gunakan nomor internet, nama pelanggan, atau no HP.
        </div>
    </div>
</div>
@endif

{{-- CUSTOMERS --}}
@if($customers->count() > 0)
<div class="card p-0 mb-4" style="overflow:hidden;">
    <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700; font-size:14px; color:var(--ink-900);">
            <i class="bi bi-people-fill" style="color:var(--primary); margin-right:6px;"></i>
            Pelanggan (Customers)
        </div>
        <span class="badge-status badge-not-contacted">{{ $customers->count() }} hasil</span>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>No Internet</th>
                    <th>No HP</th>
                    <th>Datel / STO</th>
                    <th style="text-align:right;">Saldo Piutang</th>
                    <th style="text-align:center;">Risk</th>
                    <th style="text-align:center;">Visit Terakhir</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $c)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width:30px; height:30px; font-size:11px;">{{ strtoupper(substr($c->nama_pelanggan, 0, 2)) }}</div>
                            <div>
                                <div style="font-weight:700; color:var(--ink-900);">{{ $c->nama_pelanggan }}</div>
                                <div style="font-size:11px; color:var(--ink-400);">{{ $c->nama_layanan_internet ?: 'Internet' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 masked-snd-wrapper" data-snd="{{ $c->nomor_internet }}" data-masked="true">
                            <code class="masked-snd-text" style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">••••••••••</code>
                            <button type="button" class="btn btn-link p-0 text-muted toggle-mask-btn" onclick="toggleInternetMask(this)" title="Tampilkan Nomor Internet">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </td>
                    <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                        @if($c->wa_url)
                            <a href="{{ $c->wa_url }}" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                <i class="bi bi-whatsapp"></i> {{ $c->no_hp_terbaru }}
                            </a>
                        @else
                            {{ $c->no_hp_terbaru ?: '-' }}
                        @endif
                    </td>
                    <td style="font-size:12px; color:var(--ink-500);">{{ $c->datel ?: ($c->sto ?: '-') }}</td>
                    <td style="text-align:right; font-weight:700; color:{{ $c->saldo_piutang > 0 ? 'var(--danger)' : 'var(--ink-400)' }}; white-space:nowrap;">
                        @if($c->saldo_piutang > 0) Rp {{ number_format($c->saldo_piutang, 0, ',', '.') }} @else - @endif
                    </td>
                    <td style="text-align:center;">
                        @if($c->risk_level)
                            <span class="badge-status badge-risk-{{ strtolower($c->risk_level) }}" style="font-size:10px; padding:2px 7px;">
                                {{ strtoupper($c->risk_level) }}
                            </span>
                        @else
                            <span style="color:var(--ink-400); font-size:11px;">-</span>
                        @endif
                    </td>
                    <td style="text-align:center; font-size:12px; color:var(--ink-500);">
                        {{ $c->last_visit_at ? $c->last_visit_at->format('d/m/Y') : '-' }}
                    </td>
                    <td style="text-align:center;">
                        <a href="{{ url('/customers/' . $c->id) }}" class="btn btn-sm btn-outline-telkom" style="font-size:11.5px; padding:3px 8px; white-space:nowrap;" title="Lihat Detail Profil Pelanggan" data-bs-toggle="tooltip">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($customers->count() >= 20)
    <div style="padding:12px 20px; border-top:1px solid var(--border); text-align:center;">
        <a href="{{ url('/customers?search=' . urlencode($q)) }}" style="font-size:12.5px; color:var(--primary); font-weight:600; text-decoration:none;">
            Lihat semua pelanggan dengan kata kunci ini <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    @endif
</div>
@endif

{{-- VISITS --}}
@if($visits->count() > 0)
<div class="card p-0 mb-4" style="overflow:hidden;">
    <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700; font-size:14px; color:var(--ink-900);">
            <i class="bi bi-geo-alt-fill" style="color:var(--primary); margin-right:6px;"></i>
            Kunjungan Lapangan (Visits)
        </div>
        <span class="badge-status badge-ptp">{{ $visits->count() }} hasil</span>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>No Internet</th>
                    <th>Hasil Visit</th>
                    <th>AR Agent</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visits as $v)
                <tr>
                    <td style="font-size:12px; white-space:nowrap;">{{ $v->tanggal_input ? $v->tanggal_input->format('d/m/Y') : '-' }}</td>
                    <td style="font-weight:600; color:var(--ink-900);">{{ optional($v->customer)->nama_pelanggan ?: '-' }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-1 masked-snd-wrapper" data-snd="{{ optional($v->customer)->nomor_internet ?: '' }}" data-masked="true">
                            <code class="masked-snd-text" style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px;">••••••••••</code>
                            @if(optional($v->customer)->nomor_internet)
                                <button type="button" class="btn btn-link p-0 text-muted toggle-mask-btn" onclick="toggleInternetMask(this)" title="Tampilkan Nomor Internet">
                                    <i class="bi bi-eye"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge-status {{ $v->is_ptp ? 'badge-ptp' : 'badge-not-contacted' }}" style="font-size:11px;">
                            {{ $v->hasil_visit }}
                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--ink-500);">{{ optional($v->arAgent)->name ?: '-' }}</td>
                    <td style="text-align:center;">
                        <a href="{{ url('/visits/' . $v->id) }}" class="btn btn-sm" style="background:#EFF6FF; color:#1E40AF; font-weight:600; font-size:11px; padding:3px 8px; border-radius:6px; text-decoration:none;">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- CARING LOGS --}}
@if($caringLogs->count() > 0)
<div class="card p-0 mb-4" style="overflow:hidden;">
    <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700; font-size:14px; color:var(--ink-900);">
            <i class="bi bi-telephone-fill" style="color:var(--success); margin-right:6px;"></i>
            Hasil Caring OBC
        </div>
        <span class="badge-status badge-contacted">{{ $caringLogs->count() }} hasil</span>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>No Internet</th>
                    <th>Status</th>
                    <th>VOC</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($caringLogs as $log)
                <tr>
                    <td style="font-size:12px; white-space:nowrap;">{{ $log->tanggal_caring ? $log->tanggal_caring->format('d/m/Y') : '-' }}</td>
                    <td style="font-weight:600; color:var(--ink-900);">{{ $log->nama_pelanggan }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-1 masked-snd-wrapper" data-snd="{{ $log->nomor_internet }}" data-masked="true">
                            <code class="masked-snd-text" style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px;">••••••••••</code>
                            <button type="button" class="btn btn-link p-0 text-muted toggle-mask-btn" onclick="toggleInternetMask(this)" title="Tampilkan Nomor Internet">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        @if($log->status_caring === 'CONTACTED')
                            <span class="badge-status badge-contacted"><i class="bi bi-check-circle"></i> Contacted</span>
                        @else
                            <span class="badge-status badge-not-contacted"><i class="bi bi-x-circle"></i> Uncontacted</span>
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $log->voc ?: '-' }}</td>
                    <td style="font-size:12px; color:var(--ink-500);">{{ $log->petugas_caring ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- AR AGENTS --}}
@if($arAgents->count() > 0)
<div class="card mb-4">
    <div style="font-weight:700; font-size:14px; color:var(--ink-900); margin-bottom:14px;">
        <i class="bi bi-person-badge-fill" style="color:var(--warning); margin-right:6px;"></i>
        AR Agent
    </div>
    <div class="d-flex flex-wrap gap-3">
        @foreach($arAgents as $agent)
        <div style="padding:14px 18px; background:var(--secondary); border:1px solid var(--border); border-radius:12px; min-width:180px;">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-circle">{{ strtoupper(substr($agent->name, 0, 2)) }}</div>
                <div>
                    <div style="font-weight:700; font-size:13px; color:var(--ink-900);">{{ $agent->name }}</div>
                    <div style="font-size:11.5px; color:var(--ink-400);">{{ $agent->visits_count }} Visit</div>
                </div>
            </div>
            <a href="{{ url('/ar-agents') }}" style="font-size:11px; color:var(--primary); text-decoration:none; display:block; margin-top:8px; font-weight:600;">
                Lihat Profil <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection