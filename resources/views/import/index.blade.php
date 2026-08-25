@extends('layouts.app')

@section('title', 'Sync C3MR PRITI')
@section('subtitle', 'Sinkronisasi data PRQ dan VISEEPRO ke Dashboard Intelligence')

@section('content')

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius:11px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2" style="border-radius:11px;">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
    </div>
@endif

<div class="row g-3">

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="kpi-icon" style="background:var(--success-soft); color:var(--success);">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div class="section-title mb-1">Sync Data Otomatis</div>
                    <div class="section-sub">
                        Ambil data terbaru dari Google Sheet Report PRQ dan VISEEPRO.
                    </div>
                </div>
            </div>

            <a href="/sync-priti" class="btn btn-primary-telkom d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat"></i> Sync Google Sheet
            </a>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                    <i class="bi bi-gear-fill"></i>
                </div>
                <div>
                    <div class="section-title mb-1">Spreadsheet Configuration</div>
                    <div class="section-sub">
                        Atur link spreadsheet yang digunakan saat proses Sync Google Sheet.
                    </div>
                </div>
            </div>

            <a href="/settings" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" style="border-radius:9px; font-size:13.5px; font-weight:600;">
                <i class="bi bi-gear-fill"></i> Buka Settings
            </a>
        </div>
    </div>

</div>

@endsection