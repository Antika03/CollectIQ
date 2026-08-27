@extends('layouts.app')

@section('title', 'Pengaturan Sistem & Integrasi')
@section('subtitle', 'Konfigurasi Sumber Data Spreadsheet PRITI, C3MR & Bot Telegram')

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
                            Mengandung data hasil kunjungan lapangan (~3.500 rows), nomor HP update, foto, dan Chat ID Telegram AR.
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

            {{-- PENGATURAN TELEGRAM REMINDER --}}
            <div class="mt-4 pt-3 border-top">
                <div class="section-title mb-1">
                    <i class="bi bi-telegram text-primary me-2"></i> Pengaturan Telegram Reminder AR
                </div>
                <div class="section-sub mb-3">
                    Jadwal pengiriman otomatis ringkasan tagihan, visit & PTP ke personil AR
                </div>

                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="telegram_reminder_enabled" id="reminderSwitch" value="1" {{ old('telegram_reminder_enabled', $setting->telegram_reminder_enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="reminderSwitch" style="font-size: 13px; font-weight: 600;">
                                Aktifkan Reminder Otomatis
                            </label>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label" for="morningTimeInput" style="font-size: 12px;">Waktu Pagi (WIB)</label>
                        <input id="morningTimeInput" type="time" name="telegram_morning_time" value="{{ old('telegram_morning_time', $setting->telegram_morning_time ?? '08:30') }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label" for="afternoonTimeInput" style="font-size: 12px;">Waktu Sore (WIB)</label>
                        <input id="afternoonTimeInput" type="time" name="telegram_afternoon_time" value="{{ old('telegram_afternoon_time', $setting->telegram_afternoon_time ?? '15:30') }}" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary-telkom">
                    <i class="bi bi-save-fill"></i> Simpan Semua Pengaturan
                </button>
            </div>
        </form>
    </div>

    {{-- KARTU STATUS KONEKSI BOT TELEGRAM & TEST ACTION --}}
    <div class="card">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <div class="section-title">
                    <i class="bi bi-robot text-primary me-2"></i> Status & Uji Bot Telegram
                </div>
                <div class="section-sub">
                    Token dikonfigurasi secara aman di file <code>.env</code> (tidak diekspos di frontend)
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <form action="{{ route('settings.test-telegram') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-telkom">
                        <i class="bi bi-activity"></i> Test Koneksi Bot
                    </button>
                </form>

                <form action="{{ route('settings.send-reminders') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size: 12px; border-radius: 8px;">
                        <i class="bi bi-send-fill"></i> Kirim Reminder Sekarang
                    </button>
                </form>
            </div>
        </div>

        <div class="p-3 rounded" style="background: #F8FAFC; border: 1px solid var(--border);">
            <div class="d-flex align-items-center gap-2">
                @if($botStatus['success'])
                    <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Bot Aktif</span>
                    <span style="font-size: 13px; color: var(--ink-700); font-weight: 600;">
                        {{ $botStatus['message'] }}
                    </span>
                @else
                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> Perhatian</span>
                    <span style="font-size: 13px; color: var(--danger);">
                        {{ $botStatus['message'] }}
                    </span>
                @endif
            </div>
        </div>
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
