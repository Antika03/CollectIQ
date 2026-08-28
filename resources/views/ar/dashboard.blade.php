@extends('layouts.app')

@section('title', 'AR Intelligence Dashboard')
@section('subtitle', 'Monitoring Operasional & Penagihan Account Representative')

@section('content')
<div class="d-flex flex-column gap-4">

    {{-- HEADER & AR SELECTOR (FOR ADMIN) --}}
    <div class="card d-flex flex-row align-items-center justify-content-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFF5F5, #FFFFFF); border-left: 4px solid var(--primary);">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-circle" style="width: 48px; height: 48px; font-size: 18px; background: var(--primary-soft); color: var(--primary-dark);">
                {{ strtoupper(substr($agent ? $agent->name : 'AR', 0, 2)) }}
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size: 18px; font-weight: 800; color: var(--ink-900);">
                        {{ $agent ? $agent->name : 'Pilih AR Agent' }}
                    </span>
                </div>
                <div style="font-size: 12.5px; color: var(--ink-500); margin-top: 2px;">
                    Account Representative • Telkom Witel Priangan Timur
                </div>
            </div>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="d-flex align-items-center gap-2">
                <label for="agentSelect" style="font-size: 12px; font-weight: 700; color: var(--ink-700); white-space: nowrap;">
                    Pilih AR:
                </label>
                <form action="{{ route('ar.dashboard') }}" method="GET" class="m-0">
                    <select id="agentSelect" name="agent_id" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                        @foreach($allAgents as $ag)
                            <option value="{{ $ag->id }}" {{ ($agent && $agent->id == $ag->id) ? 'selected' : '' }}>
                                {{ $ag->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
    </div>

    {{-- KPI METRICS CARDS --}}
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">Pelanggan Saya</div>
                    <div class="kpi-value">{{ number_format($totalCustomers, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Total terdaftar di wilayah</div>
                </div>
                <div class="kpi-icon" style="background: #EFF6FF; color: #1D4ED8;">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">Outstanding Piutang</div>
                    <div class="kpi-value rupiah-val" style="color: var(--danger); font-size: 21px;">
                        Rp {{ number_format($totalOutstanding, 0, ',', '.') }}
                    </div>
                    <div class="kpi-sub">Saldo belum terbayar</div>
                </div>
                <div class="kpi-icon" style="background: var(--danger-soft); color: var(--danger);">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">Belum Pernah Visit</div>
                    <div class="kpi-value" style="color: var(--warning);">{{ number_format($unvisitedCount, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Prioritas penagihan awal</div>
                </div>
                <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">Perlu Visit Ulang</div>
                    <div class="kpi-value">{{ number_format($revisitCount, 0, ',', '.') }}</div>
                    <div class="kpi-sub">>14 hari sejak visit terakhir</div>
                </div>
                <div class="kpi-icon" style="background: #F1F5F9; color: #475569;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">PTP Aktif (Janji Bayar)</div>
                    <div class="kpi-value" style="color: #059669;">{{ number_format($activePtpCount, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Kunjungan komitmen bayar</div>
                </div>
                <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">Pelanggan PRANPC</div>
                    <div class="kpi-value" style="color: #D97706;">{{ number_format($pranpcCount, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Tagihan kategori PRANPC</div>
                </div>
                <div class="kpi-icon" style="background: #FEF3C7; color: #D97706;">
                    <i class="bi bi-tag-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">Pelanggan High Risk</div>
                    <div class="kpi-value" style="color: #B91C1C;">{{ number_format($highRiskCount, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Indikasi risiko churn tinggi</div>
                </div>
                <div class="kpi-icon" style="background: #FEE2E2; color: #B91C1C;">
                    <i class="bi bi-shield-slash-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div>
                    <div class="kpi-label">Total Kunjungan</div>
                    <div class="kpi-value">{{ number_format($totalVisits, 0, ',', '.') }}</div>
                    <div class="kpi-sub">{{ $totalPaid }} lunas terkonfirmasi</div>
                </div>
                <div class="kpi-icon" style="background: #F8FAFC; color: var(--ink-700);">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL PELANGGAN PRIORITAS & RIWAYAT AKTIVITAS --}}
    <div class="row g-4">
        {{-- DAFTAR PELANGGAN PRIORITAS PENAGIHAN --}}
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="section-title">Pelanggan Prioritas Penagihan</div>
                        <div class="section-sub">Daftar pelanggan dengan saldo tertinggi di bawah tanggung jawab Anda</div>
                    </div>
                    <a href="{{ route('customers.index') }}" class="btn-outline-telkom" style="font-size: 11.5px;">
                        Lihat Semua ({{ $totalCustomers }})
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Saldo Piutang</th>
                                <th>Kategori</th>
                                <th>Kontak WA</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignedCustomers as $cust)
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--ink-900);">
                                            {{ $cust->nama_pelanggan }}
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-1 masked-snd-wrapper" data-snd="{{ $cust->nomor_internet }}" data-masked="true" style="font-size: 11.5px; color: var(--ink-400);">
                                            <code class="masked-snd-text" style="background:var(--secondary); padding:1px 5px; border-radius:4px;">••••••••••</code>
                                            <button type="button" class="btn btn-link p-0 text-muted toggle-mask-btn" onclick="toggleInternetMask(this)" title="Tampilkan Nomor Internet">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <span>• {{ $cust->datel ?: '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rupiah-val" style="color: var(--danger); font-size: 13px;">
                                            Rp {{ number_format($cust->saldo_piutang, 0, ',', '.') }}
                                        </div>
                                        <div style="font-size: 11px; color: var(--ink-400);">{{ $cust->umur_customer ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if($cust->is_pranpc)
                                            <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 10px;">PRANPC</span>
                                        @else
                                            <span class="badge bg-light text-muted" style="font-size: 10px;">{{ $cust->bill_category ?: 'Eksisting' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($cust->wa_url)
                                            <a href="{{ $cust->wa_url }}" target="_blank" class="btn btn-sm btn-outline-success" style="font-size: 11px; border-radius: 7px; padding: 2px 8px;">
                                                <i class="bi bi-whatsapp"></i> Chat
                                            </a>
                                        @else
                                            <span style="font-size: 11px; color: var(--ink-400);">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('customer.show', $cust) }}" class="btn-outline-telkom" style="font-size: 11px; padding: 2px 8px;">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Tidak ada pelanggan terdaftar untuk AR ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIWAYAT KUNJUNGAN TERAKHIR --}}
        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="section-title">Kunjungan Lapangan Terkini</div>
                        <div class="section-sub">Aktivitas visit dan foto monitoring</div>
                    </div>
                    <a href="{{ route('visits.index') }}" class="btn-outline-telkom" style="font-size: 11.5px;">
                        Semua Visit
                    </a>
                </div>

                <div class="d-flex flex-column gap-3">
                    @forelse($recentVisits as $visit)
                        <div class="d-flex align-items-start gap-3 p-2 rounded" style="background: #F8FAFC; border: 1px solid #F1F5F9;">
                            @if($visit->foto_url)
                                <a href="{{ $visit->foto_url }}" target="_blank">
                                    <img src="{{ $visit->foto_url }}" alt="Foto Visit" class="photo-thumb" onerror="this.src='{{ route('visit.photo', $visit) }}'">
                                </a>
                            @else
                                <div class="photo-placeholder"><i class="bi bi-camera"></i></div>
                            @endif

                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div style="font-weight: 700; font-size: 12.5px; color: var(--ink-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $visit->customer ? $visit->customer->nama_pelanggan : 'Pelanggan' }}
                                    </div>
                                    <span style="font-size: 10.5px; color: var(--ink-400);">
                                        {{ $visit->tanggal_input ? $visit->tanggal_input->format('d/m/Y') : '-' }}
                                    </span>
                                </div>
                                <div style="font-size: 11.5px; color: var(--ink-700); margin-top: 2px;">
                                    <b>Hasil:</b> {{ $visit->hasil_visit }}
                                </div>
                                <div style="font-size: 11px; color: var(--ink-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $visit->keterangan_visit ?: '-' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted" style="font-size: 12px;">
                            Belum ada riwayat kunjungan lapangan.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
