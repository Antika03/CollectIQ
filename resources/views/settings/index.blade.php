@extends('layouts.app')

@section('title', 'Pengaturan Sumber Data')
@section('subtitle', 'Konfigurasi Sumber Data Google Spreadsheet PRITI & C3MR')

@section('content')
<div class="d-flex flex-column gap-4" style="max-width: 960px;">

    {{-- KARTU INTEGRASI GOOGLE SPREADSHEET --}}
    <div class="card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <div class="section-title">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i> Sumber Data Google Spreadsheet
                </div>
                <div class="section-sub">
                    Pusat konfigurasi tautan Google Spreadsheet untuk PRITI DATA dan C3MR
                </div>
            </div>
            <span class="badge" style="background: #F1F5F9; color: var(--ink-700); font-size: 11.5px;">
                2 Sumber Terintegrasi
            </span>
        </div>

        <form action="{{ route('settings.update') }}" method="POST">
            @csrf

            <div class="row g-3">
                {{-- 1. PRITI DATA --}}
                <div class="col-12">
                    <div class="p-3 rounded border" style="background: #FAFAFA;">
                        <label class="form-label d-flex align-items-center justify-content-between" for="pritiUrlInput">
                            <span style="font-weight: 700; color: var(--ink-900);">
                                <i class="bi bi-1-circle-fill text-danger me-1"></i> Google Spreadsheet PRITI DATA (Sheet: Collection)
                            </span>
                            @if(!empty($pritiSheetId))
                                <span class="badge bg-light text-muted" style="font-size: 11px;">
                                    Sheet ID: {{ substr($pritiSheetId, 0, 14) }}...
                                </span>
                            @endif
                        </label>
                        <div class="input-group">
                            <input id="pritiUrlInput" type="url" name="priti_url" value="{{ old('priti_url', $activePritiUrl) }}" class="form-control" placeholder="https://docs.google.com/spreadsheets/d/.../edit" required>
                            <a href="{{ $activePritiUrl }}" target="_blank" class="btn btn-outline-secondary" title="Buka di tab baru">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                        <div class="form-text" style="font-size: 11.5px; color: var(--ink-500);">
                            Mengandung data hasil kunjungan lapangan (~3.500 rows), nomor HP update, dan foto bukti kunjungan.
                        </div>
                    </div>
                </div>

                {{-- 2. C3MR --}}
                <div class="col-12">
                    <div class="p-3 rounded border" style="background: #FAFAFA;">
                        <label class="form-label d-flex align-items-center justify-content-between" for="c3mrUrlInput">
                            <span style="font-weight: 700; color: var(--ink-900);">
                                <i class="bi bi-2-circle-fill text-danger me-1"></i> Google Spreadsheet C3MR (Master DATA ALL, Caring & Performansi)
                            </span>
                            @if(!empty($c3mrSheetId))
                                <span class="badge bg-light text-muted" style="font-size: 11px;">
                                    Sheet ID: {{ substr($c3mrSheetId, 0, 14) }}...
                                </span>
                            @endif
                        </label>
                        <div class="input-group">
                            <input id="c3mrUrlInput" type="url" name="c3mr_url" value="{{ old('c3mr_url', $activeC3mrUrl) }}" class="form-control" placeholder="https://docs.google.com/spreadsheets/d/.../edit" required>
                            <a href="{{ $activeC3mrUrl }}" target="_blank" class="btn btn-outline-secondary" title="Buka di tab baru">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                        <div class="form-text" style="font-size: 11.5px; color: var(--ink-500);">
                            Master Customer DATA ALL (~27.000 records), Report PRQ, VISEEPRO, HASIL CARING, dan PERFORMANSI DETAIL.
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary-telkom">
                    <i class="bi bi-save-fill"></i> Simpan Sumber Spreadsheet
                </button>
            </div>
        </form>
    </div>

    {{-- RIWAYAT TERAKHIR SINKRONISASI --}}
    <div class="card">
        <div class="section-title mb-2">
            <i class="bi bi-clock-history text-muted me-2"></i> Status Sinkronisasi Terakhir
        </div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="p-3 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                    <div style="font-size: 11.5px; color: var(--ink-500);">Waktu Sinkronisasi Terakhir:</div>
                    <div style="font-size: 14px; font-weight: 700; color: var(--ink-900); margin-top: 2px;">
                        {{ $lastSyncFormatted }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="p-3 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                    <div style="font-size: 11.5px; color: var(--ink-500);">Status Integritas Data:</div>
                    <div style="font-size: 14px; font-weight: 700; margin-top: 2px;">
                        @if($lastSyncStatus === 'success')
                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Sinkronisasi Penuh Berhasil</span>
                        @elseif($lastSyncStatus === 'warning')
                            <span class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Selesai dengan Catatan Data Quality</span>
                        @elseif($lastSyncStatus === 'error')
                            <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Sinkronisasi Gagal</span>
                        @else
                            <span class="text-muted">Belum ada riwayat</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
