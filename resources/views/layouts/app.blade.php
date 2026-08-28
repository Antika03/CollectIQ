<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') — CollectIQ Telkom Intelligence</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
:root{
    --primary: #E2001A;
    --primary-dark: #B8000F;
    --primary-light: #FF3B4E;
    --primary-soft: #FDEBEC;
    --secondary: #F1F5F9;
    --success: #16A34A;
    --success-soft: #DCFCE7;
    --warning: #D97706;
    --warning-soft: #FEF3C7;
    --danger: #DC2626;
    --danger-soft: #FEE2E2;
    --ink-900: #0F172A;
    --ink-700: #334155;
    --ink-500: #64748B;
    --ink-400: #94A3B8;
    --border: #E2E8F0;
    --surface: #FFFFFF;
    --sidebar-w: 248px;
    --radius: 14px;
    --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
    --card-shadow-hover: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
}

* { box-sizing: border-box; }

body{
    margin: 0;
    font-family: 'Inter', sans-serif;
    color: var(--ink-900);
    -webkit-font-smoothing: antialiased;
    background: #F8FAFC;
    min-height: 100vh;
    overflow-x: hidden;
}

/* TOP PROGRESS BAR */
#topProgressBar{
    position: fixed;
    top: 0; left: 0;
    width: 0%; height: 3px;
    background: linear-gradient(90deg, #E2001A, #FF3B4E, #F59E0B);
    z-index: 99999;
    transition: width 0.25s ease, opacity 0.25s ease;
    opacity: 0;
    pointer-events: none;
    box-shadow: 0 0 10px rgba(226, 0, 26, 0.6);
}
#topProgressBar.loading{
    opacity: 1;
    width: 75%;
}
#topProgressBar.finish{
    width: 100%;
    opacity: 0;
}

/* SIDEBAR */
.sidebar{
    width: var(--sidebar-w);
    height: 100vh;
    background: #FFFFFF;
    border-right: 1px solid var(--border);
    position: fixed;
    left: 0; top: 0;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    z-index: 100;
    scrollbar-width: thin;
    scrollbar-color: #E2E8F0 transparent;
}
.sidebar::-webkit-scrollbar{ width: 4px; }
.sidebar::-webkit-scrollbar-thumb{ background: #E2E8F0; border-radius: 4px; }

.sidebar-header{
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid #F1F5F9;
    flex-shrink: 0;
}

.sidebar-menu-label{
    font-size: 10px;
    font-weight: 800;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: .08em;
    padding: 0 12px 6px;
}
.menu-section{ padding: 14px 10px 0; }
.menu{ display: flex; flex-direction: column; gap: 2px; }
.menu a{
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: #475569;
    padding: 8px 12px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 500;
    position: relative;
    transition: all .15s ease;
    white-space: nowrap;
}
.menu a i{
    font-size: 15px;
    width: 18px;
    text-align: center;
    color: #94A3B8;
    transition: color .15s ease;
    flex-shrink: 0;
}
.menu a:hover{
    background: #F1F5F9;
    color: #0F172A;
}
.menu a:hover i{ color: var(--primary); }
.menu a.active{
    background: #FDEBEC;
    color: #B8000F;
    font-weight: 700;
}
.menu a.active i{ color: var(--primary); }
.menu a.active::before{
    content: '';
    position: absolute;
    left: 0; top: 6px; bottom: 6px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--primary);
}
.sidebar-footer{
    margin-top: auto;
    padding: 14px 18px;
    border-top: 1px solid #F1F5F9;
    font-size: 11px;
    color: #94A3B8;
    flex-shrink: 0;
    background: #FAFAFA;
}

/* MAIN CONTENT */
.main{
    margin-left: var(--sidebar-w);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* TOPBAR */
.topbar{
    height: 64px;
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 50;
    gap: 16px;
}
.page-title{ font-size: 17px; font-weight: 800; color: var(--ink-900); }
.page-subtitle{ font-size: 12px; color: var(--ink-500); margin-top: 1px; }

.topbar-right{ display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.topbar-badge{
    display: flex; align-items: center; gap: 7px;
    background: #FFFFFF;
    border: 1px solid var(--border);
    padding: 6px 12px;
    border-radius: 9px;
    font-size: 12px;
    color: var(--ink-700);
    font-weight: 600;
    white-space: nowrap;
}
.topbar-badge i{ color: var(--primary); }

.user-profile-badge{
    display: flex; align-items: center; gap: 9px;
    background: #FFFFFF;
    border: 1px solid var(--border);
    padding: 5px 12px 5px 6px;
    border-radius: 30px;
    font-size: 12px;
    color: var(--ink-900);
    font-weight: 600;
}
.role-pill{
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 99px;
    text-transform: uppercase;
}
.role-admin{ background: #FEE2E2; color: #B91C1C; }
.role-ar{ background: #E0F2FE; color: #0369A1; }

.topbar-search{
    flex: 1;
    max-width: 360px;
    min-width: 160px;
}
.topbar-search form{ display: flex; position: relative; }
.topbar-search-input{
    width: 100%;
    height: 36px;
    padding: 0 36px 0 13px;
    border: 1px solid var(--border);
    border-radius: 9px;
    font-size: 13px;
    background: #FFFFFF;
    color: var(--ink-900);
    outline: none;
    transition: all .15s ease;
}
.topbar-search-input:focus{
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}
.topbar-search-btn{
    position: absolute;
    right: 0; top: 0; bottom: 0;
    width: 36px;
    display: flex; align-items: center; justify-content: center;
    background: none; border: none;
    cursor: pointer;
    color: var(--ink-400);
    border-radius: 0 9px 9px 0;
    transition: color .15s;
}
.topbar-search-btn:hover{ color: var(--primary); }

.content{ padding: 24px 28px 48px; flex: 1; }

/* CARDS & UI COMPONENTS */
.card{
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--card-shadow);
    transition: box-shadow .2s ease, transform .2s ease;
}
.card:hover{ box-shadow: var(--card-shadow-hover); }

.kpi-card{
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.kpi-icon{
    width: 44px; height: 44px;
    border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
}
.kpi-label{ font-size: 12px; color: var(--ink-500); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
.kpi-value{
    font-size: 25px;
    font-weight: 800;
    color: var(--ink-900);
    margin-top: 5px;
    line-height: 1.1;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.kpi-sub{ font-size: 11.5px; color: var(--ink-400); margin-top: 6px; }

.section-title{ font-size: 15px; font-weight: 700; color: var(--ink-900); }
.section-sub{ font-size: 12px; color: var(--ink-500); margin-top: 2px; }

.rupiah-val{
    white-space: nowrap;
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
}

/* Badges */
.badge-status{
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 99px;
    white-space: nowrap;
}
.badge-pranpc{
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid rgba(217, 119, 6, 0.25);
    font-weight: 800;
    font-size: 10.5px;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-ptp{ background: var(--warning-soft); color: var(--warning); }
.badge-contacted{ background: var(--success-soft); color: var(--success); }
.badge-not-contacted{ background: var(--danger-soft); color: var(--danger); }
.badge-risk-high{ background: var(--danger-soft); color: var(--danger); }
.badge-risk-medium{ background: var(--warning-soft); color: var(--warning); }
.badge-risk-low{ background: var(--success-soft); color: var(--success); }
.badge-risk-critical{ background: #FEE2E2; color: #991B1B; border: 1px solid rgba(153, 27, 27, 0.2); }

.avatar-circle{
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--primary-soft);
    color: var(--primary-dark);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px;
    flex-shrink: 0;
}

.table-modern{ width: 100%; border-collapse: separate; border-spacing: 0; }
.table-modern thead th{
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--ink-500);
    font-weight: 700;
    padding: 11px 16px;
    border-bottom: 1px solid var(--border);
    background: var(--secondary);
    text-align: left;
    white-space: nowrap;
}
.table-modern thead th:first-child{ border-top-left-radius: 10px; }
.table-modern thead th:last-child{ border-top-right-radius: 10px; }
.table-modern tbody td{
    padding: 12px 16px;
    font-size: 13px;
    color: var(--ink-700);
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
.table-modern tbody tr:last-child td{ border-bottom: none; }
.table-modern tbody tr:hover{ background: #FAFBFD; }

.filter-bar{
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px;
    margin-bottom: 20px;
}
.form-control, .form-select{
    border-radius: 9px;
    border: 1px solid var(--border);
    font-size: 13px;
    background-color: var(--surface);
    color: var(--ink-900);
}
.form-control:focus, .form-select:focus{
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
    background-color: var(--surface);
    color: var(--ink-900);
}

.btn-primary-telkom{
    background: linear-gradient(135deg, var(--primary-light), var(--primary-dark));
    border: none; color: #fff !important;
    border-radius: 9px;
    font-weight: 600; font-size: 13px;
    padding: 7px 16px;
    box-shadow: 0 3px 10px rgba(226,0,26,0.22);
    transition: filter .15s ease, transform .15s ease;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none;
}
.btn-primary-telkom:hover{ filter: brightness(1.08); transform: translateY(-1px); }

.btn-outline-telkom{
    background: #FFFFFF;
    border: 1px solid var(--primary);
    color: var(--primary) !important;
    border-radius: 8px;
    font-weight: 600; font-size: 12px;
    padding: 4px 10px;
    transition: all .15s ease;
    display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none;
    white-space: nowrap;
}
.btn-outline-telkom:hover{
    background: var(--primary-soft);
    color: var(--primary-dark) !important;
    border-color: var(--primary-dark);
}

.photo-thumb{
    width: 42px; height: 42px;
    border-radius: 9px;
    object-fit: cover;
    border: 1px solid var(--border);
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
}
.photo-thumb:hover{ transform: scale(1.08); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
.photo-placeholder{
    width: 42px; height: 42px;
    border-radius: 9px;
    background: var(--secondary);
    border: 1px dashed var(--border);
    display: flex; align-items: center; justify-content: center;
    color: var(--ink-400); font-size: 16px;
}

.menu-toggle{
    display: none;
    align-items: center; justify-content: center;
    width: 36px; height: 36px;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: var(--surface);
    cursor: pointer;
    font-size: 18px;
    color: var(--ink-700);
    flex-shrink: 0;
}
.sidebar-overlay{
    display: none;
    position: fixed; inset: 0;
    background: rgba(15,23,42,0.4);
    z-index: 99;
}
.sidebar-overlay.active{ display: block; }

@media (max-width: 991.98px){
    .sidebar{
        transform: translateX(-100%);
        transition: transform .25s ease;
        box-shadow: 0 0 40px rgba(0,0,0,0.2);
    }
    .sidebar.sidebar-open{ transform: translateX(0); }
    .main{ margin-left: 0; }
    .menu-toggle{ display: flex; }
    .content{ padding: 16px 14px 40px; }
    .topbar{ padding: 0 14px; gap: 10px; }
    .page-title{ font-size: 15px; }
    .page-subtitle{ display: none; }
    .topbar-badge{ display: none; }
    .topbar-search{ max-width: 220px; }
}
@media (max-width: 575.98px){
    .kpi-value{ font-size: 20px; }
    .card{ padding: 16px; }
    .topbar-search{ display: none; }
}
</style>
@stack('styles')
</head>
<body>

<div id="topProgressBar"></div>

{{-- SIDEBAR --}}
<div class="sidebar" id="sidebarEl">
    <div class="sidebar-header">
        <a href="{{ url('/') }}" style="display:block; text-decoration:none;">
            <img src="{{ asset('images/telkom-logo.png') }}" alt="Telkom Indonesia" style="height:46px; width:auto; object-fit:contain; display:block; margin:0 auto;" onerror="this.src=''; this.alt='CollectIQ Telkom';">
        </a>
    </div>

    @php
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();
        $isAr = $user && $user->isAr();
    @endphp

    {{-- MAIN GROUP --}}
    <div class="menu-section">
        <div class="sidebar-menu-label">Main</div>
        <div class="menu">
            @if($isAdmin)
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i> Executive Dashboard
                </a>
            @endif

            <a href="{{ route('ar.dashboard') }}" class="{{ request()->routeIs('ar.dashboard') ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i> AR Dashboard
            </a>

            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers*') || request()->routeIs('customer.show') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Customers
            </a>
            <a href="{{ route('visits.index') }}" class="{{ request()->routeIs('visits*') || request()->routeIs('visit.*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt-fill"></i> Visits
            </a>
        </div>
    </div>

    {{-- COLLECTION & PIUTANG GROUP --}}
    <div class="menu-section">
        <div class="sidebar-menu-label">Collection & Piutang</div>
        <div class="menu">
            <a href="{{ route('ptp.monitoring') }}" class="{{ request()->routeIs('ptp.monitoring') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> PTP Monitoring
            </a>
            <a href="{{ route('reminders.index') }}" class="{{ request()->routeIs('reminders*') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i> Reminder Center
            </a>
            <a href="{{ route('risk-score.index') }}" class="{{ request()->routeIs('risk-score*') ? 'active' : '' }}">
                <i class="bi bi-shield-exclamation"></i> Indikasi Risiko Churn
            </a>
            <a href="{{ route('piutang.index') }}" class="{{ request()->routeIs('piutang*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Piutang Outstanding
            </a>
        </div>
    </div>

    {{-- C3MR INTELLIGENCE GROUP --}}
    <div class="menu-section">
        <div class="sidebar-menu-label">C3MR Intelligence</div>
        <div class="menu">
            <a href="{{ route('c3mr.caring') }}" class="{{ request()->routeIs('c3mr.caring*') ? 'active' : '' }}">
                <i class="bi bi-telephone-fill"></i> Hasil Caring OBC
            </a>
            <a href="{{ route('c3mr.performance') }}" class="{{ request()->routeIs('c3mr.performance*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Performansi Witel
            </a>
            @if($isAdmin)
                <a href="{{ route('c3mr.sync') }}" class="{{ request()->routeIs('c3mr.sync*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-repeat"></i> Sync Data PRITI + C3MR
                </a>
            @endif
        </div>
    </div>

    {{-- AGENTS GROUP (ADMIN ONLY) --}}
    @if($isAdmin)
        <div class="menu-section">
            <div class="sidebar-menu-label">Agents Master</div>
            <div class="menu">
                <a href="{{ route('ar-agents.index') }}" class="{{ request()->routeIs('ar-agents*') ? 'active' : '' }}">
                    <i class="bi bi-person-lines-fill"></i> AR Agents
                </a>
            </div>
        </div>

        {{-- SYSTEM GROUP (ADMIN ONLY) --}}
        <div class="menu-section">
            <div class="sidebar-menu-label">System</div>
            <div class="menu">
                <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings*') ? 'active' : '' }}">
                    <i class="bi bi-gear-fill"></i> Settings & Telegram
                </a>
            </div>
        </div>
    @endif

    <div class="sidebar-footer">
        <div style="font-weight:700; color:#1E293B; margin-bottom:2px; font-size:11.5px;">PT Telekomunikasi Indonesia</div>
        <div>Collection & AR Team — PRITI</div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="main">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2" style="min-width:0;">
            <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <div style="min-width:0;">
                <div class="page-title">@yield('title', 'Dashboard')</div>
                <div class="page-subtitle">@yield('subtitle', 'PT Telekomunikasi Indonesia')</div>
            </div>
        </div>

        {{-- GLOBAL SEARCH --}}
        <div class="topbar-search">
            <form action="{{ route('global.search') }}" method="GET">
                <input id="topbar-search-input" type="text" name="q" value="{{ request('q') }}" class="topbar-search-input" placeholder="Cari pelanggan, no internet, AR Agent..." autocomplete="off">
                <button type="submit" class="topbar-search-btn" aria-label="Cari"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="topbar-right">
            <div class="topbar-badge d-none d-md-flex">
                <i class="bi bi-calendar3"></i> {{ now()->translatedFormat('d F Y') }}
            </div>

            @auth
                <div class="dropdown">
                    <button class="btn p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-profile-badge">
                            <div class="avatar-circle" style="width: 26px; height: 26px; font-size: 11px;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <span class="d-none d-sm-inline" style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ auth()->user()->name }}
                            </span>
                            <span class="role-pill {{ auth()->user()->isAdmin() ? 'role-admin' : 'role-ar' }}">
                                {{ strtoupper(auth()->user()->role ?? 'AR') }}
                            </span>
                            <i class="bi bi-chevron-down" style="font-size: 10px; color: #94A3B8;"></i>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border-color: var(--border); font-size: 13px; min-width: 180px;">
                        <li class="px-3 py-2 border-bottom">
                            <div style="font-weight: 700; color: var(--ink-900);">{{ auth()->user()->name }}</div>
                            <div style="font-size: 11px; color: var(--ink-500);">{{ auth()->user()->email }}</div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('profile.index') }}">
                                <i class="bi bi-person-circle me-2 text-muted"></i> Profil
                            </a>
                        </li>
                        @if(auth()->user()->isAdmin())
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('settings.index') }}">
                                    <i class="bi bi-gear me-2 text-muted"></i> Pengaturan
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('c3mr.sync') }}">
                                    <i class="bi bi-arrow-repeat me-2 text-muted"></i> Sync Data
                                </a>
                            </li>
                        @endif
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:11px; font-size:13px;">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:11px; font-size:13px;">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
function toggleSidebar(){
    document.getElementById('sidebarEl').classList.toggle('sidebar-open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar(){
    document.getElementById('sidebarEl').classList.remove('sidebar-open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

const topBar = document.getElementById('topProgressBar');
function startLoader(){
    if (topBar) {
        topBar.classList.remove('finish');
        topBar.classList.add('loading');
    }
}
function endLoader(){
    if (topBar) {
        topBar.classList.remove('loading');
        topBar.classList.add('finish');
    }
}

document.addEventListener('DOMContentLoaded', function(){
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('a[href]').forEach(function(link){
        link.addEventListener('click', function(e){
            const href = link.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !link.hasAttribute('target') && !link.hasAttribute('download')) {
                startLoader();
            }
            if (window.innerWidth < 992) closeSidebar();
        });
    });

    document.querySelectorAll('form').forEach(function(form){
        form.addEventListener('submit', function(){
            startLoader();
        });
    });
});

function copyToClipboard(text, btnElement) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-999999px';
        textarea.style.top = '-999999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }
        document.body.removeChild(textarea);
    }

    if (btnElement) {
        const originalHtml = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="bi bi-check2 text-success"></i>';
        setTimeout(function() {
            btnElement.innerHTML = originalHtml;
        }, 1500);
    }
}

function toggleInternetMask(btn) {
    const wrapper = btn.closest('.masked-snd-wrapper');
    if (!wrapper) return;
    const textEl = wrapper.querySelector('.masked-snd-text');
    const icon = btn.querySelector('i');
    const raw = wrapper.getAttribute('data-snd') || '';
    const isMasked = wrapper.getAttribute('data-masked') === 'true';

    if (isMasked) {
        textEl.textContent = raw;
        wrapper.setAttribute('data-masked', 'false');
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
        btn.setAttribute('title', 'Sembunyikan Nomor Internet');
    } else {
        textEl.textContent = '••••••••••';
        wrapper.setAttribute('data-masked', 'true');
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
        btn.setAttribute('title', 'Tampilkan Nomor Internet');
    }
}

window.addEventListener('pageshow', function(){
    endLoader();
});
</script>

@stack('modals')
@stack('scripts')
</body>
</html>