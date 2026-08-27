@extends('layouts.app')

@section('title', 'Customers — Master Pelanggan C3MR')
@section('subtitle', 'Pusat data pelanggan, penagihan AR & monitoring PRANPC')

@section('content')

{{-- SUMMARY CARDS --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Customer</div>
                <div class="kpi-value">{{ number_format($totalCustomers, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Master Data C3MR</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Piutang</div>
                <div class="kpi-value rupiah-val" style="font-size:19px; color:var(--danger); white-space:nowrap;">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Saldo tertahan</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Pelanggan PRANPC</div>
                <div class="kpi-value" style="color:#D97706;">{{ number_format($pranpcCount, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:#D97706; margin-top:4px;">Tagihan khusus PRANPC</div>
            </div>
            <div class="kpi-icon" style="background:#FEF3C7; color:#D97706;">
                <i class="bi bi-tag-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Risiko Tinggi / Kritis</div>
                <div class="kpi-value" style="color:var(--danger);">{{ number_format($highRiskCount, 0, ',', '.') }}</div>
                <div style="font-size:11px; color:var(--danger); margin-top:4px;">Perlu tindakan cepat</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-shield-exclamation"></i>
            </div>
        </div>
    </div>
</div>

{{-- FILTER BAR --}}
<div class="filter-bar mb-3">
    <form method="GET" action="{{ route('customers.index') }}" class="d-flex w-100 gap-2 flex-wrap align-items-end">
        <div style="flex:2; min-width:200px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Pencarian</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                   placeholder="Nama, nomor internet, no HP, datel, NCLI...">
        </div>

        @if(auth()->user()->isAdmin() && isset($allAgents))
            <div style="flex:1; min-width:140px;">
                <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">AR Agent</label>
                <select name="ar_agent_id" class="form-select form-select-sm">
                    <option value="">Semua AR</option>
                    @foreach($allAgents as $ag)
                        <option value="{{ $ag->id }}" {{ request('ar_agent_id') == $ag->id ? 'selected' : '' }}>
                            {{ $ag->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div style="flex:1; min-width:120px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Kategori PRANPC</label>
            <select name="is_pranpc" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="1" {{ request('is_pranpc') === '1' ? 'selected' : '' }}>PRANPC Saja</option>
                <option value="0" {{ request('is_pranpc') === '0' ? 'selected' : '' }}>Non-PRANPC</option>
            </select>
        </div>

        <div style="flex:1; min-width:120px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Risk Level</label>
            <select name="risk_level" class="form-select form-select-sm">
                <option value="">Semua Level</option>
                <option value="low" {{ request('risk_level') == 'low' ? 'selected' : '' }}>🟢 Low</option>
                <option value="medium" {{ request('risk_level') == 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                <option value="high" {{ request('risk_level') == 'high' ? 'selected' : '' }}>🟠 High</option>
                <option value="critical" {{ request('risk_level') == 'critical' ? 'selected' : '' }}>🔴 Critical</option>
            </select>
        </div>

        <div style="flex:1; min-width:120px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Status Piutang</label>
            <select name="has_piutang" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1" {{ request('has_piutang') === '1' ? 'selected' : '' }}>Ada Piutang</option>
                <option value="0" {{ request('has_piutang') === '0' ? 'selected' : '' }}>Lunas</option>
            </select>
        </div>

        <div>
            <label class="form-label mb-1" style="font-size:11px;">&nbsp;</label>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary-telkom" style="height:31px; font-size:12.5px; padding:4px 14px;">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                @if($search || request('risk_level') || request('has_piutang') || request('is_pranpc') || request('ar_agent_id'))
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; height:31px;">Reset</a>
                @endif
                <a href="{{ route('customers.export', request()->query()) }}" class="btn btn-sm" style="background:var(--success-soft); color:var(--success); border:1px solid rgba(22,163,74,0.3); font-weight:600; border-radius:8px; height:31px; display:inline-flex; align-items:center; gap:5px;">
                    <i class="bi bi-file-earmark-excel"></i> Export CSV
                </a>
            </div>
        </div>
    </form>
</div>

{{-- TABLE --}}
<div class="card p-0" style="overflow:hidden;">
    <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Daftar Master Pelanggan</div>
            <div style="font-size:11.5px; color:var(--ink-400);">{{ number_format($customers->total(), 0, ',', '.') }} pelanggan ditemukan</div>
        </div>
        <a href="{{ route('piutang.index') }}" class="btn btn-sm" style="background:var(--danger-soft); color:var(--danger); font-size:12px; font-weight:600; border-radius:8px; padding:5px 12px; text-decoration:none;">
            <i class="bi bi-wallet2"></i> Lihat Piutang Outstanding
        </a>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th style="width:36px;">#</th>
                    <th>Pelanggan</th>
                    <th>No Internet</th>
                    <th>Kategori / PRANPC</th>
                    <th>AR Agent</th>
                    <th>No HP / WA</th>
                    <th>Datel</th>
                    <th style="text-align:right;">Saldo Piutang</th>
                    <th style="text-align:center;">Risk</th>
                    <th style="text-align:center;">Visit</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $idx => $customer)
                    <tr>
                        <td style="color:var(--ink-400); font-weight:600; text-align:center;">
                            {{ $customers->firstItem() + $idx }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width:30px; height:30px; font-size:11px;">
                                    {{ strtoupper(substr($customer->nama_pelanggan ?? '-', 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:700; color:var(--ink-900); max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $customer->nama_pelanggan }}
                                    </div>
                                    <div style="font-size:11px; color:var(--ink-400);">{{ $customer->nama_layanan_internet ?? 'Internet' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">
                                {{ $customer->nomor_internet }}
                            </code>
                        </td>
                        <td>
                            @if($customer->is_pranpc)
                                <span class="badge-pranpc"><i class="bi bi-tag-fill"></i> PRANPC</span>
                            @else
                                <span class="badge bg-light text-muted" style="font-size:10px; border:1px solid #E2E8F0;">
                                    {{ $customer->bill_category ?: 'Eksisting' }}
                                </span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--ink-700); font-weight:600;">
                            {{ $customer->assignedArAgent ? $customer->assignedArAgent->name : '-' }}
                        </td>
                        <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                            @if($customer->wa_url)
                                <a href="{{ $customer->wa_url }}" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                    <i class="bi bi-whatsapp"></i> {{ $customer->no_hp_terbaru }}
                                </a>
                            @else
                                <span style="color:var(--ink-400);">{{ $customer->no_hp_terbaru ?: '-' }}</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--ink-500);">
                            {{ $customer->datel ?: ($customer->sto ?: '-') }}
                        </td>
                        <td style="text-align:right; font-weight:700; font-size:13px; color:{{ $customer->saldo_piutang > 0 ? 'var(--danger)' : 'var(--ink-400)' }}; white-space:nowrap;">
                            @if($customer->saldo_piutang > 0)
                                Rp {{ number_format($customer->saldo_piutang, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($customer->risk_level)
                                <span class="badge-status badge-risk-{{ $customer->risk_level }}" style="font-size:10.5px; padding:3px 8px;">
                                    {{ strtoupper(substr($customer->risk_level, 0, 3)) }}
                                </span>
                            @else
                                <span style="color:var(--ink-400); font-size:11px;">-</span>
                            @endif
                        </td>
                        <td style="text-align:center; font-weight:700; color:var(--ink-700); font-size:13px;">
                            {{ $customer->visits_count }}
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('customer.show', $customer) }}" class="btn btn-sm btn-outline-telkom"
                               style="font-size:11.5px; padding:3px 8px; white-space:nowrap;"
                               title="Lihat Detail Profil Pelanggan 360" data-bs-toggle="tooltip">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i> Tidak ada data customer ditemukan.
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