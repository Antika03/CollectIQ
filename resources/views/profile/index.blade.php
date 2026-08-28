@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('subtitle', 'Informasi akun dan pengaturan keamanan kata sandi')

@section('content')

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius:10px; font-size:13.5px;">
        <i class="bi bi-check-circle-fill me-2" style="font-size:18px;"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-4" role="alert" style="border-radius:10px; font-size:13.5px;">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size:18px;"></i>
            <strong>Terjadi kesalahan input:</strong>
        </div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    {{-- 1. KARTU INFORMASI AKUN --}}
    <div class="col-lg-5">
        <div class="card h-100 p-4">
            <div class="text-center pb-3 mb-3 border-bottom">
                <div class="avatar-circle mx-auto mb-3" style="width:72px; height:72px; font-size:24px; background:linear-gradient(135deg, var(--primary), var(--primary-dark)); color:#fff; box-shadow:0 8px 16px rgba(226,0,26,0.2);">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <h5 class="mb-1" style="font-weight:800; color:var(--ink-900);">{{ $user->name }}</h5>
                <div style="font-size:13px; color:var(--ink-400);">{{ $user->email }}</div>
                <div class="mt-2">
                    <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-primary' }}" style="font-size:11px; padding:4px 10px; border-radius:6px; text-transform:uppercase;">
                        {{ $user->role ?: 'AR' }}
                    </span>
                </div>
            </div>

            <div class="d-flex flex-column gap-3">
                <div>
                    <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase;">Nama Lengkap</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-900); margin-top:2px;">{{ $user->name }}</div>
                </div>

                <div>
                    <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase;">Alamat Email</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-900); margin-top:2px;">{{ $user->email }}</div>
                </div>

                <div>
                    <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase;">Hak Akses (Role)</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-900); margin-top:2px;">
                        {{ $user->isAdmin() ? 'Administrator Sistem' : 'Account Representative (AR Lapangan)' }}
                    </div>
                </div>

                @if($user->arAgent)
                    <div>
                        <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase;">Profil AR Terhubung</div>
                        <div style="font-size:13.5px; font-weight:600; color:var(--primary); margin-top:2px;">
                            <i class="bi bi-person-badge"></i> {{ $user->arAgent->name }}
                        </div>
                    </div>
                @endif

                <div>
                    <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase;">Akun Dibuat Pada</div>
                    <div style="font-size:13px; color:var(--ink-600); margin-top:2px;">
                        {{ $user->created_at ? $user->created_at->translatedFormat('d F Y, H:i') : '-' }} WIB
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. FORM UBAH PASSWORD --}}
    <div class="col-lg-7">
        <div class="card p-4">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <i class="bi bi-shield-lock-fill" style="font-size:22px; color:var(--primary);"></i>
                <div>
                    <h6 class="mb-0" style="font-weight:800; color:var(--ink-900);">Ubah Password</h6>
                    <div style="font-size:12px; color:var(--ink-400);">Perbarui kata sandi akun Anda secara berkala demi keamanan data</div>
                </div>
            </div>

            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label class="form-label" style="font-size:12.5px; font-weight:700; color:var(--ink-700);">Password Saat Ini <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Masukkan password lama..." required style="border-radius:8px; font-size:13.5px;">
                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted pe-3" onclick="togglePasswordVisibility('current_password', this)" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:12.5px; font-weight:700; color:var(--ink-700);">Password Baru <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter..." required minlength="8" style="border-radius:8px; font-size:13.5px;">
                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted pe-3" onclick="togglePasswordVisibility('password', this)" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text" style="font-size:11.5px; color:var(--ink-400);">Gunakan kombinasi huruf besar, kecil, angka, dan simbol untuk keamanan maksimal.</div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-size:12.5px; font-weight:700; color:var(--ink-700);">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi password baru..." required minlength="8" style="border-radius:8px; font-size:13.5px;">
                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted pe-3" onclick="togglePasswordVisibility('password_confirmation', this)" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-light border btn-sm px-3" style="font-weight:600; border-radius:8px;">
                        Reset
                    </button>
                    <button type="submit" class="btn btn-primary-telkom btn-sm px-4" style="font-weight:700; border-radius:8px;">
                        <i class="bi bi-check-lg"></i> Simpan Password Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}
</script>
@endpush
