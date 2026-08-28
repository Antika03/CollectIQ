<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Masuk — CollectIQ Telkom Collection Intelligence</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
:root{
    --primary: #E2001A;
    --primary-dark: #B8000F;
    --primary-light: #FF3B4E;
    --primary-soft: #FDEBEC;
    --ink-900: #0F172A;
    --ink-700: #334155;
    --ink-500: #64748B;
    --border: #E2E8F0;
    --radius: 16px;
}
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: #F8FAFC;
    color: var(--ink-900);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.login-card {
    width: 100%;
    max-width: 440px;
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 36px 32px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
}
.brand-header {
    text-align: center;
    margin-bottom: 28px;
}
.brand-logo {
    height: 52px;
    width: auto;
    object-fit: contain;
    margin-bottom: 12px;
}
.brand-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--ink-900);
    letter-spacing: -0.02em;
}
.brand-sub {
    font-size: 13px;
    color: var(--ink-500);
    margin-top: 4px;
}
.form-label {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--ink-700);
    margin-bottom: 6px;
}
.form-control {
    border-radius: 10px;
    border: 1px solid var(--border);
    font-size: 13.5px;
    padding: 10px 14px;
    background-color: #FFFFFF;
    transition: all .15s ease;
}
.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}
.btn-login {
    width: 100%;
    background: linear-gradient(135deg, var(--primary-light), var(--primary-dark));
    border: none;
    color: #FFFFFF;
    font-size: 14px;
    font-weight: 700;
    padding: 11px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(226, 0, 26, 0.25);
    transition: all .15s ease;
    margin-top: 10px;
}
.btn-login:hover {
    filter: brightness(1.08);
    transform: translateY(-1px);
    color: #FFFFFF;
}
.login-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid #F1F5F9;
    font-size: 11.5px;
    color: var(--ink-500);
}
.quick-login-box {
    background: #F8FAFC;
    border: 1px dashed #CBD5E1;
    border-radius: 10px;
    padding: 12px 14px;
    margin-top: 20px;
    font-size: 11.5px;
}
</style>
</head>
<body>

<div class="login-card">
    <div class="brand-header">
        <img src="{{ asset('images/telkom-logo.png') }}" alt="Telkom Indonesia" class="brand-logo" onerror="this.style.display='none'">
        <div class="brand-title">CollectIQ Telkom</div>
        <div class="brand-sub">Collection Intelligence & Monitoring Dashboard</div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:10px; font-size:12.5px;">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:10px; font-size:12.5px;">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3" style="border-radius:10px; font-size:12.5px;">
            @foreach($errors->all() as $err)
                <div><i class="bi bi-exclamation-circle-fill"></i> {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('login.post') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="emailInput">Email Pengguna</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px; border-color: var(--border); color: #94A3B8;">
                    <i class="bi bi-envelope"></i>
                </span>
                <input id="emailInput" type="email" name="email" value="{{ old('email') }}" class="form-control border-start-0" placeholder="nama@telkom.co.id" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="passwordInput">Kata Sandi</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px; border-color: var(--border); color: #94A3B8;">
                    <i class="bi bi-lock"></i>
                </span>
                <input id="passwordInput" type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                <label class="form-check-label" for="rememberMe" style="font-size:12px; color: var(--ink-700);">
                    Ingat saya
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Masuk ke Dashboard
        </button>
    </form>

    <div class="login-footer">
        &copy; {{ date('Y') }} PT Telekomunikasi Indonesia Tbk.<br>
        Collection &amp; Account Representative — Witel Priangan Timur
    </div>
</div>

</body>
</html>
