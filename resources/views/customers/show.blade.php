@extends('layouts.app')

@section('title', $customer->nama_pelanggan ?: 'Customer Profile 360')
@section('subtitle', 'Customer 360 Intelligence — Profil & Riwayat Penagihan')

@section('content')

{{-- TOP BAR ACTION & BACK BUTTON --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Customer
    </a>
    <div class="d-flex gap-2 flex-wrap">
        @if(auth()->user() && auth()->user()->isAdmin())
            <button type="button" class="btn btn-sm text-white" style="background:#0284C7; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:6px;" data-bs-toggle="modal" data-bs-target="#modalSendTelegram">
                <i class="bi bi-telegram"></i> Kirim ke Telegram AR
            </button>
        @endif
        <a href="{{ url('/visits?search=' . $customer->nomor_internet) }}" class="btn btn-sm" style="background:var(--primary-soft); color:var(--primary-dark); font-weight:600; border-radius:8px;">
            <i class="bi bi-geo-alt"></i> Lihat Semua Visit
        </a>
        <a href="{{ url('/c3mr/hasil-caring?search=' . $customer->nomor_internet) }}" class="btn btn-sm" style="background:#EFF6FF; color:#2563EB; font-weight:600; border-radius:8px;">
            <i class="bi bi-telephone"></i> Riwayat Caring
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- ============================= --}}
    {{-- 1. IDENTITAS PELANGGAN 360 --}}
    {{-- ============================= --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="section-title">
                    <i class="bi bi-person-vcard-fill" style="color:var(--primary); margin-right:6px;"></i>
                    Identitas Pelanggan (Customer 360)
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($customer->is_pranpc)
                        <span class="badge-pranpc"><i class="bi bi-tag-fill"></i> PRANPC</span>
                    @endif
                    <span class="badge" style="background:var(--secondary); color:var(--ink-700); font-size:12px; font-weight:600; padding:4px 8px; border-radius:6px;">
                        NCLI: {{ $customer->ncli ?: '-' }}
                    </span>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 g-3">
                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">No. Internet (SND)</div>
                    <div class="d-flex align-items-center gap-1 mt-1 masked-snd-wrapper" data-snd="{{ $customer->nomor_internet }}" data-masked="true">
                        <code class="masked-snd-text" style="background:var(--secondary); padding:2px 8px; border-radius:6px; font-size:13.5px; font-weight:700; color:var(--ink-900);">••••••••••</code>
                        <button type="button" class="btn btn-link p-0 text-muted toggle-mask-btn" onclick="toggleInternetMask(this)" title="Tampilkan Nomor Internet">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:12px; line-height:1; opacity:0.6;" onclick="copyToClipboard('{{ $customer->nomor_internet }}', this)" title="Salin No Internet" data-bs-toggle="tooltip">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Nama Pelanggan</div>
                    <div style="font-size:14.5px; font-weight:700; color:var(--ink-900);">
                        {{ $customer->nama_pelanggan ?: '-' }}
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">No. Handphone (Update C3MR)</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-900); display:flex; align-items:center; gap:8px; margin-top:2px;">
                        @if($customer->wa_url)
                            <a href="{{ $customer->wa_url }}" target="_blank" class="btn btn-sm btn-success text-white" style="background:#16A34A; border:none; font-size:12px; font-weight:600; padding:3px 10px; border-radius:7px; display:inline-flex; align-items:center; gap:5px;" title="Kirim Pesan WhatsApp Otomatis" data-bs-toggle="tooltip">
                                <i class="bi bi-whatsapp"></i> {{ $customer->no_hp_terbaru }}
                            </a>
                        @elseif($customer->no_hp_terbaru)
                            <span>{{ $customer->no_hp_terbaru }}</span>
                        @else
                            <span class="text-muted">(Belum tersedia)</span>
                        @endif
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Email</div>
                    <div style="font-size:13px; color:var(--ink-700);">
                        {{ $customer->email ?: '-' }}
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Layanan Produk</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-700);">
                        {{ $customer->nama_layanan_internet ?: 'IndiHome / Internet' }}
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Kategori Tagihan</div>
                    <div style="font-size:13px; font-weight:600; color:var(--ink-700);">
                        {{ $customer->bill_category ?: 'Eksisting' }}
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">AR Penanggung Jawab</div>
                    <div style="font-size:13.5px; font-weight:700; color:#2563EB;">
                        <i class="bi bi-person-badge"></i> {{ $customer->assignedArAgent ? $customer->assignedArAgent->name : '-' }}
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Wilayah (Datel / STO)</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-700);">
                        {{ $customer->datel ?: ($customer->sto ?: '-') }}
                    </div>
                </div>

                <div class="col-sm-12">
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Alamat Pemasangan</div>
                    <div style="font-size:13px; color:var(--ink-700); line-height:1.5;">
                        {{ $customer->alamat ?: '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{-- 2. COLLECTION & INDIKASI RISIKO CHURN --}}
    {{-- ============================= --}}
    <div class="col-lg-5">
        <div class="card h-100" style="border-left:4px solid {{ $churnEval['level'] === 'CRITICAL' ? '#991B1B' : ($churnEval['level'] === 'HIGH' ? 'var(--danger)' : 'var(--primary)') }};">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="section-title">
                    <i class="bi bi-shield-shaded" style="color:var(--primary); margin-right:6px;"></i>
                    Collection &amp; Indikasi Risiko Churn
                </div>
                @if($churnEval['level'] === 'CRITICAL')
                    <span class="badge" style="background:#450A0A; color:#fff; font-size:11px; padding:4px 8px; border-radius:99px;">CRITICAL RISK ({{ $churnEval['score'] }})</span>
                @elseif($churnEval['level'] === 'HIGH')
                    <span class="badge-status badge-risk-high">HIGH RISK ({{ $churnEval['score'] }})</span>
                @elseif($churnEval['level'] === 'MEDIUM')
                    <span class="badge-status badge-risk-medium">MEDIUM RISK ({{ $churnEval['score'] }})</span>
                @else
                    <span class="badge-status badge-risk-low">LOW RISK ({{ $churnEval['score'] }})</span>
                @endif
            </div>

            {{-- Metric Summary --}}
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div style="background:var(--secondary); padding:10px 12px; border-radius:10px;">
                        <div style="font-size:11px; color:var(--ink-400); font-weight:600;">Saldo Piutang</div>
                        <div style="font-size:16px; font-weight:800; color:var(--danger); margin-top:2px; white-space:nowrap;">
                            Rp {{ number_format($customer->saldo_piutang, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:var(--secondary); padding:10px 12px; border-radius:10px;">
                        <div style="font-size:11px; color:var(--ink-400); font-weight:600;">Umur Tunggakan</div>
                        <div style="font-size:14px; font-weight:700; color:var(--ink-900); margin-top:2px;">
                            {{ $customer->umur_customer ?: '-' }}
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:var(--secondary); padding:8px 10px; border-radius:8px; text-align:center;">
                        <div style="font-size:10.5px; color:var(--ink-400);">Total Visit</div>
                        <div style="font-size:14px; font-weight:700; color:var(--ink-900);">{{ $customer->visits->count() }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:var(--secondary); padding:8px 10px; border-radius:8px; text-align:center;">
                        <div style="font-size:10.5px; color:var(--ink-400);">Total PTP</div>
                        <div style="font-size:14px; font-weight:700; color:var(--warning);">{{ $customer->visits->where('is_ptp', true)->count() }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:var(--secondary); padding:8px 10px; border-radius:8px; text-align:center;">
                        <div style="font-size:10.5px; color:var(--ink-400);">Total Caring</div>
                        <div style="font-size:14px; font-weight:700; color:var(--success);">{{ $customer->caringLogs->count() }}</div>
                    </div>
                </div>
            </div>

            {{-- Rekomendasi Tindakan Collection --}}
            <div style="background:var(--primary-soft); padding:12px; border-radius:10px; border:1px solid rgba(226,0,26,0.15); margin-bottom:12px;">
                <div style="font-size:11px; font-weight:700; color:var(--primary-dark); text-transform:uppercase; margin-bottom:4px;">
                    <i class="bi bi-lightbulb-fill"></i> Rekomendasi Tindakan Collection
                </div>
                <div style="font-size:13px; font-weight:600; color:var(--ink-900); line-height:1.4;">
                    {{ $churnEval['recommendation'] }}
                </div>
            </div>

            {{-- Indikator Alasan Risiko --}}
            @if(!empty($churnEval['reasons']))
                <div class="mb-3">
                    <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase; margin-bottom:4px;">Faktor Indikator Risiko:</div>
                    <ul style="margin:0; padding-left:16px; font-size:11.5px; color:var(--ink-700);">
                        @foreach($churnEval['reasons'] as $r)
                            <li>{{ $r }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- WhatsApp Collection Quick Action --}}
            <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:12px; padding:14px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px; font-weight:700; color:#166534;">
                        <i class="bi bi-whatsapp"></i> Template Pesan WhatsApp Resmi
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="copyWaText()" style="font-size:11px; padding:2px 8px; border-radius:6px;">
                        <i class="bi bi-clipboard"></i> Salin Teks
                    </button>
                </div>
                <div style="font-size:11.5px; color:#14532D; background:#FFFFFF; border:1px solid #DCFCE7; border-radius:8px; padding:10px; max-height:140px; overflow-y:auto; font-family:inherit; white-space:pre-wrap; line-height:1.4;" id="waTemplateText">{{ $customer->wa_message_template }}</div>
                @if($customer->wa_url)
                    <div class="mt-2">
                        <a href="{{ $customer->wa_url }}" target="_blank" class="btn btn-sm btn-success w-100 d-flex align-items-center justify-content-center gap-2" style="background:#16A34A; border:none; font-weight:700; font-size:12.5px; padding:7px 12px; border-radius:8px;">
                            <i class="bi bi-whatsapp"></i> Kirim Pesan WhatsApp ke Pelanggan
                        </a>
                    </div>
                @else
                    <div class="mt-2 text-center text-muted" style="font-size:11px;">
                        <i class="bi bi-exclamation-circle"></i> Nomor HP belum tersedia untuk pengiriman otomatis via WhatsApp.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- 3. UNIFIED ACTIVITY TIMELINE (VISITS & CARING) --}}
{{-- ================================================= --}}
<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
        <ul class="nav nav-pills" id="customerTab" role="tablist" style="gap:8px;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="visit-tab" data-bs-toggle="pill" data-bs-target="#visitTabContent" type="button" role="tab" style="font-size:13px; font-weight:600; border-radius:8px;">
                    <i class="bi bi-geo-alt-fill"></i> Riwayat Kunjungan Visit ({{ $customer->visits->count() }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="caring-tab" data-bs-toggle="pill" data-bs-target="#caringTabContent" type="button" role="tab" style="font-size:13px; font-weight:600; border-radius:8px;">
                    <i class="bi bi-telephone-fill"></i> Riwayat Caring OBC ({{ $customer->caringLogs->count() }})
                </button>
            </li>
            @if($customer->viseeproData->count() > 0)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="viseepro-tab" data-bs-toggle="pill" data-bs-target="#viseeproTabContent" type="button" role="tab" style="font-size:13px; font-weight:600; border-radius:8px;">
                        <i class="bi bi-clipboard-data-fill"></i> Data Viseepro ({{ $customer->viseeproData->count() }})
                    </button>
                </li>
            @endif
        </ul>
    </div>

    <div class="tab-content p-3" id="customerTabContent">
        {{-- TAB 1: VISITS TIMELINE --}}
        <div class="tab-pane fade show active" id="visitTabContent" role="tabpanel">
            @forelse($customer->visits->sortByDesc('tanggal_input') as $visit)
                @php
                    $photoUrl = route('visit.photo', ['visit' => $visit->id]);
                @endphp
                <div class="timeline-item d-flex gap-3 py-3" style="border-bottom:1px solid var(--border);">
                    {{-- FOTO THUMBNAIL DENGAN MODAL --}}
                    <div style="flex-shrink:0;">
                        @if($visit->foto_url)
                            <a href="{{ $visit->foto_url }}" target="_blank">
                                <img src="{{ $visit->foto_url }}"
                                     class="photo-thumb"
                                     alt="Foto visit"
                                     loading="lazy"
                                     style="width:56px; height:56px; object-fit:cover; border-radius:10px; border:1px solid var(--border); cursor:pointer;"
                                     onerror="this.src='{{ $photoUrl }}'">
                            </a>
                        @else
                            <div class="photo-placeholder" style="width:56px; height:56px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:var(--secondary); color:var(--ink-400);">
                                <i class="bi bi-image" style="font-size:20px;"></i>
                            </div>
                        @endif
                    </div>

                    {{-- DETAIL INFO VISIT --}}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span style="font-weight:700; font-size:14px; color:var(--ink-900);">
                                    {{ $visit->hasil_visit ?: 'Belum Diisi' }}
                                </span>
                                @if($visit->is_ptp)
                                    <span class="badge-status badge-ptp ms-2">
                                        <i class="bi bi-cash-coin"></i> Janji Bayar (PTP)
                                    </span>
                                @endif
                                @if($visit->kategori_visit && $visit->kategori_visit !== '-')
                                    <span class="badge" style="background:var(--secondary); color:var(--ink-700); font-size:11px; margin-left:4px;">
                                        {{ $visit->kategori_visit }}
                                    </span>
                                @endif
                            </div>
                            <span style="font-size:12px; color:var(--ink-400);">
                                {{ $visit->tanggal_input ? $visit->tanggal_input->translatedFormat('d F Y') : '-' }}
                            </span>
                        </div>

                        <div style="font-size:12.5px; color:var(--ink-500); margin-top:4px;">
                            <i class="bi bi-person-badge"></i> AR Agent: <strong>{{ optional($visit->arAgent)->name ?? '-' }}</strong>
                            @if($visit->keterangan_visit && $visit->keterangan_visit !== '-')
                                &middot; <span>{{ $visit->keterangan_visit }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state py-4">
                    <i class="bi bi-clock-history"></i> Belum ada riwayat visit untuk pelanggan ini.
                </div>
            @endforelse
        </div>

        {{-- TAB 2: CARING TIMELINE --}}
        <div class="tab-pane fade" id="caringTabContent" role="tabpanel">
            @forelse($customer->caringLogs->sortByDesc('tanggal_caring') as $caring)
                <div class="d-flex gap-3 py-3" style="border-bottom:1px solid var(--border);">
                    <div class="avatar-circle" style="background:#EFF6FF; color:#2563EB; width:44px; height:44px; font-size:18px;">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span style="font-weight:700; font-size:14px; color:var(--ink-900);">
                                    {{ $caring->voc ?: 'General Caring' }}
                                </span>
                                @if($caring->status_caring === 'CONTACTED')
                                    <span class="badge-status badge-contacted ms-2">Contacted</span>
                                @else
                                    <span class="badge-status badge-not-contacted ms-2">Uncontacted</span>
                                @endif
                                @if($caring->status_bayar === 'PAID')
                                    <span class="badge-status ms-1" style="background:#D1FAE5; color:#059669;">PAID</span>
                                @endif
                            </div>
                            <span style="font-size:12px; color:var(--ink-400);">
                                {{ $caring->tanggal_caring ? $caring->tanggal_caring->format('d M Y') : '-' }}
                            </span>
                        </div>
                        <div style="font-size:12.5px; color:var(--ink-500); margin-top:4px;">
                            Petugas: <strong>{{ $caring->petugas_caring ?: '-' }}</strong>
                            @if($caring->keterangan)
                                &middot; <span>{{ $caring->keterangan }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state py-4">
                    <i class="bi bi-telephone-x"></i> Belum ada data riwayat caring untuk pelanggan ini.
                </div>
            @endforelse
        </div>

        {{-- TAB 3: VISEEPRO SURVEY DATA --}}
        @if($customer->viseeproData->count() > 0)
            <div class="tab-pane fade" id="viseeproTabContent" role="tabpanel">
                @foreach($customer->viseeproData as $vp)
                    <div class="p-3 mb-2" style="background:var(--secondary); border-radius:10px;">
                        <div class="d-flex justify-content-between">
                            <div style="font-weight:700; color:var(--ink-900);">Activity #{{ $vp->activity_id }} — {{ $vp->nama_perusahaan ?: $customer->nama_pelanggan }}</div>
                            <span class="badge" style="background:var(--surface); color:var(--ink-700); border:1px solid var(--border);">{{ $vp->sto }} ({{ $vp->witel }})</span>
                        </div>
                        <div class="row g-2 mt-2" style="font-size:12px; color:var(--ink-700);">
                            <div class="col-sm-6">Agent: <strong>{{ $vp->nama_agent }}</strong></div>
                            <div class="col-sm-6">PIC: <strong>{{ $vp->pic_name ?: '-' }}</strong> ({{ $vp->pic_cp ?: '-' }})</div>
                            <div class="col-sm-12">Alamat: {{ $vp->address ?: '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- MODAL DISPOSISI TELEGRAM KE AR --}}
<div class="modal fade" id="modalSendTelegram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background:#0F172A; color:#FFFFFF; border-radius:14px 14px 0 0; padding:16px 20px;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-telegram" style="color:#38BDF8; font-size:20px;"></i>
                    <div>
                        <h6 class="modal-title mb-0" style="font-weight:700; font-size:15px;">Disposisi Pelanggan ke Telegram AR</h6>
                        <small style="color:#94A3B8; font-size:11.5px;">Kirim profil &amp; saldo piutang langsung ke bot Telegram AR</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('telegram.send-customer', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-body" style="padding:20px;">
                    <div class="p-3 mb-3" style="background:#F8FAFC; border:1px solid var(--border); border-radius:10px;">
                        <div class="d-flex align-items-center gap-1 mt-1" style="font-size:12px; color:var(--ink-500);">
                            <span>SND:</span>
                            <div class="d-inline-flex align-items-center gap-1 masked-snd-wrapper" data-snd="{{ $customer->nomor_internet }}" data-masked="true">
                                <code class="masked-snd-text" style="background:var(--secondary); padding:1px 5px; border-radius:4px;">••••••••••</code>
                                <button type="button" class="btn btn-link p-0 text-muted toggle-mask-btn" onclick="toggleInternetMask(this)" title="Tampilkan Nomor Internet">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <span>| Saldo: <strong style="color:var(--primary);">Rp {{ number_format($customer->saldo_piutang, 0, ',', '.') }}</strong></span>
                        </div>
                        <div style="font-size:11.5px; color:var(--ink-500); margin-top:2px;">Alamat: {{ $customer->alamat ?: ($customer->datel ?: '-') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:var(--ink-700);">Pilih AR Agent Tujuan</label>
                        <select name="ar_agent_id" class="form-select form-select-sm" required style="border-radius:8px;">
                            @foreach($allAgents as $ag)
                                <option value="{{ $ag->id }}" {{ $customer->assigned_ar_agent_id === $ag->id ? 'selected' : '' }}>
                                    {{ $ag->name }} {{ $ag->chat_id_telegram ? ' (Telegram Aktif)' : ' [Belum ada Chat ID]' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:var(--ink-700);">Catatan Tambahan untuk AR (Opsional)</label>
                        <textarea name="custom_note" rows="3" class="form-control" placeholder="Contoh: Tolong segera kunjungi hari ini sebelum jam 15:00 WIB..." style="font-size:12.5px; border-radius:8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background:#F8FAFC; border-top:1px solid var(--border); border-radius:0 0 14px 14px; padding:12px 20px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" style="background:#0284C7; border:none; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                        <i class="bi bi-send-fill"></i> Kirim Notifikasi Telegram
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyWaText() {
    const textEl = document.getElementById('waTemplateText');
    if (!textEl) return;
    navigator.clipboard.writeText(textEl.innerText).then(() => {
        alert('Teks pesan WhatsApp berhasil disalin ke clipboard!');
    }).catch(err => {
        console.error('Gagal menyalin teks: ', err);
    });
}
</script>
@endpush

@endsection