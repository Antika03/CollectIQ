@extends('layouts.app')

@section('title', 'Settings')
@section('subtitle', 'Konfigurasi sumber data Google Spreadsheet C3MR terpusat')

@section('content')

<div class="row g-4">
    <div class="col-lg-8 col-xl-7">
        <div class="card">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4" style="border-bottom:1px solid var(--border);">
                <div>
                    <div class="section-title mb-1">
                        <i class="bi bi-file-earmark-spreadsheet-fill" style="color:var(--primary);"></i> Spreadsheet C3MR
                    </div>
                    <div class="section-sub">
                        Satu tautan Google Spreadsheet untuk seluruh pembaruan data aplikasi
                    </div>
                </div>
                <span class="badge" style="background:#D1FAE5; color:#059669; font-size:12px; padding:6px 12px; border-radius:99px; font-weight:700;">
                    <i class="bi bi-check-circle-fill me-1"></i> Terhubung
                </span>
            </div>

            <form method="POST" action="{{ url('/settings') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px; font-weight:700; color:var(--ink-900);">
                        Google Spreadsheet URL
                    </label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:var(--secondary); border-color:var(--border); color:var(--ink-500);">
                            <i class="bi bi-link-45deg fs-5"></i>
                        </span>
                        <input 
                            type="url" 
                            name="c3mr_url" 
                            class="form-control @error('c3mr_url') is-invalid @enderror" 
                            placeholder="https://docs.google.com/spreadsheets/d/..." 
                            value="{{ old('c3mr_url', $activeUrl) }}"
                            required
                            style="font-size:13.5px; border-color:var(--border);"
                        >
                    </div>
                    @error('c3mr_url')
                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                    @enderror
                    <div style="font-size:12px; color:var(--ink-400); margin-top:6px; line-height:1.4;">
                        <i class="bi bi-info-circle"></i> Masukkan tautan lengkap Google Spreadsheet yang berisi sheet data C3MR (Report PRQ, VISEEPRO, DATA ALL, Hasil Caring, Performansi).
                    </div>
                </div>

                {{-- INFO STATUS & LAST SYNC --}}
                <div class="p-3 mb-4" style="background:var(--secondary); border-radius:10px; border:1px solid var(--border);">
                    <div class="row g-2 align-items-center" style="font-size:12.5px;">
                        <div class="col-sm-6">
                            <span style="color:var(--ink-500);">Spreadsheet ID Aktif:</span>
                            <div style="font-family:monospace; font-weight:700; color:var(--ink-700); word-break:break-all;">
                                {{ $sheetId }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <span style="color:var(--ink-500);">Last Sync / Updated:</span>
                            <div style="font-weight:700; color:var(--ink-700);">
                                <i class="bi bi-clock-history" style="color:var(--primary);"></i> {{ $lastSyncFormatted }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2">
                    <button type="submit" class="btn btn-primary-telkom px-4">
                        <i class="bi bi-floppy-fill me-1"></i> Simpan Spreadsheet
                    </button>

                    <a href="{{ url('/c3mr/sync') }}" class="btn btn-outline-telkom" style="font-size:13px;">
                        <i class="bi bi-arrow-repeat me-1"></i> Buka Sync Data C3MR
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- SIDE INFO CARD --}}
    <div class="col-lg-4 col-xl-5">
        <div class="card h-100">
            <div class="section-title mb-2" style="font-size:14px;">
                <i class="bi bi-shield-check" style="color:var(--success);"></i> Petunjuk Izin Akses
            </div>
            <div style="font-size:12.5px; color:var(--ink-700); line-height:1.6;">
                Agar server aplikasi dapat menyinkronkan data secara otomatis:
                <ol class="ps-3 mt-2 mb-3" style="color:var(--ink-500);">
                    <li>Buka file spreadsheet di Google Sheets.</li>
                    <li>Klik tombol <strong>Bagikan (Share)</strong> di pojok kanan atas.</li>
                    <li>Ubah <em>Akses Umum</em> menjadi <strong>"Siapa saja yang memiliki link"</strong> dengan peran <strong>Pelihat (Viewer)</strong>.</li>
                    <li>Salin link spreadsheet lalu tempel pada input di halaman ini.</li>
                </ol>
            </div>

            <div class="p-3 mt-auto" style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:10px; font-size:12px; color:#1E40AF; line-height:1.5;">
                <i class="bi bi-lightbulb-fill text-primary"></i> <strong>Satu Pintu Sinkronisasi:</strong> Semua dataset (Report PRQ, VISEEPRO, DATA ALL, Hasil Caring, Performansi Witel) kini bersumber dari satu spreadsheet ini dan diperbarui serentak via menu <strong>Sync Data C3MR</strong>.
            </div>
        </div>
    </div>
</div>

@endsection
