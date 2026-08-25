@extends('layouts.app')

@section('title', 'Settings')
@section('subtitle', 'Konfigurasi sumber data Google Sheet')

@section('content')

<div class="card" style="max-width:560px;">
    <div class="section-title mb-1"><i class="bi bi-gear-fill" style="color:var(--primary);"></i> Pengaturan Google Sheet</div>
    <div class="section-sub mb-4">URL sumber data untuk Report PRQ dan VISEEPRO</div>

    <form method="POST" action="/settings">
        @csrf

        <div class="mb-3">
            <label class="form-label" style="font-size:13px; font-weight:600; color:var(--ink-700);">Link Report PRQ</label>
            <input type="text" name="report_prq_url" class="form-control" value="{{ $setting->report_prq_url ?? '' }}">
        </div>

        <div class="mb-4">
            <label class="form-label" style="font-size:13px; font-weight:600; color:var(--ink-700);">Link VISEEPRO</label>
            <input type="text" name="viseepro_url" class="form-control" value="{{ $setting->viseepro_url ?? '' }}">
        </div>

        <button type="submit" class="btn btn-primary-telkom">
            <i class="bi bi-save"></i> Simpan Setting
        </button>
    </form>
</div>

@endsection
