# Laporan Audit Kode Sumber & Aplikasi Deployed
## CollectIQ — PTP Collection Intelligence Dashboard
### Telkom Witel Priangan Timur (Kerja Praktik / Skripsi)

---

## 1. Ringkasan Eksekutif Hasil Audit

Audit komprehensif dilakukan terhadap:
1. **Source Code**: `routes/web.php`, `app/Http/Controllers/`, `app/Services/`, `app/Models/`, `database/migrations/`, `resources/views/`.
2. **Deployed Environment**: [https://collectiq.up.railway.app/](https://collectiq.up.railway.app/) (Railway PaaS, PHP 8.2, MySQL 8.0).

Tujuan audit adalah memastikan seluruh diagram UML (Use Case, Activity, Sequence, Class, Component, Deployment, dan ERD) merefleksikan **fitur yang benar-benar terimplementasi dan aktif**, serta mengeliminasi kode legacy atau fitur dormant dari diagram fungsional utama.

---

## 2. Pemetaan Kategori Implementasi

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           STATUS ARSITEKTUR                             │
├────────────────────────────┬─────────────────────────────┬──────────────┤
│    IMPLEMENTED & ACTIVE    │ IMPLEMENTED BUT UNUSED/DORM │   EXTERNAL   │
│     (Masuk ke Diagram)     │    (Dikeluarkan dr UML)     │  DEPENDENCY  │
├────────────────────────────┼─────────────────────────────┼──────────────┤
│ • 11 HTTP Controllers      │ • TelegramChatController    │ • Google     │
│ • 6 Business Services      │ • TelegramService           │   Spreadsheet│
│ • 9 Core Eloquent Models   │ • TelegramReminder Model    │   C3MR       │
│ • 24 Active Web Routes     │ • ImportController (legacy) │ • Google     │
│ • Unified C3MR Sync Engine │ • SyncController (legacy)   │   Drive      │
│ • Rule-based ChurnRisk     │ • PtpController (legacy)    │   (Photo)    │
│ • Google Drive Photo Proxy │ • risk_score_logs &         │              │
│ • WA Link Direct Contact   │   follow_up_recommendations │              │
└────────────────────────────┴─────────────────────────────┴──────────────┘
```

---

## 3. Hasil Audit Rinci per Komponen

### A. Route Aktif (`routes/web.php`)

| No | HTTP Method | URI Pattern | Name | Controller & Method | Kategori Fitur |
|---|---|---|---|---|---|
| 1 | `GET` | `/` | `dashboard` | `DashboardController@index` | Main Dashboard |
| 2 | `GET` | `/search` | `global.search` | `GlobalSearchController@search` | Main Dashboard |
| 3 | `GET` | `/customers` | `customers.index` | `CustomerController@index` | Customer 360 |
| 4 | `GET` | `/customers/{customer}` | `customer.show` | `CustomerController@show` | Customer 360 |
| 5 | `GET` | `/customers/export` | `customers.export` | `CustomerController@export` | Customer 360 |
| 6 | `GET` | `/visits` | `visits.index` | `VisitController@index` | Visits Monitoring |
| 7 | `GET` | `/visits/{visit}` | `visit.show` | `VisitController@show` | Visits Monitoring |
| 8 | `GET` | `/visits/{visit}/photo` | `visit.photo` | `VisitController@photo` | Photo Proxy |
| 9 | `GET` | `/visits/export` | `visit.export` | `VisitController@export` | Visits Monitoring |
| 10 | `GET` | `/ptp-monitoring` | `ptp.monitoring` | `PtpMonitoringController@index` | Collection Intel |
| 11 | `GET` | `/ptp-monitoring/export` | `ptp.export` | `PtpMonitoringController@export` | Collection Intel |
| 12 | `GET` | `/risk-score` | `risk-score.index` | `RiskScoreController@index` | Collection Intel |
| 13 | `GET` | `/risk-score/export` | `risk-score.export` | `RiskScoreController@export` | Collection Intel |
| 14 | `GET` | `/piutang` | `piutang.index` | `PiutangController@index` | Collection Intel |
| 15 | `GET` | `/piutang/export` | `piutang.export` | `PiutangController@export` | Collection Intel |
| 16 | `GET` | `/c3mr` | — | `redirect('/c3mr/sync')` | C3MR Intel |
| 17 | `GET` | `/c3mr/hasil-caring` | `c3mr.caring` | `C3mrCaringController@index` | C3MR Intel |
| 18 | `GET` | `/c3mr/hasil-caring/export` | `c3mr.caring.export` | `C3mrCaringController@export` | C3MR Intel |
| 19 | `GET` | `/c3mr/performance` | `c3mr.performance` | `C3mrPerformanceController@index` | C3MR Intel |
| 20 | `GET` | `/c3mr/sync` | `c3mr.sync` | `C3mrSyncController@index` | Unified Sync |
| 21 | `POST` | `/c3mr/sync/all` | `c3mr.sync.all` | `C3mrSyncController@syncAll` | Unified Sync |
| 22 | `GET` | `/ar-agents` | `ar-agents.index` | `ArAgentController@index` | Agents |
| 23 | `GET` | `/ar-agents/export` | `ar-agents.export` | `ArAgentController@export` | Agents |
| 24 | `GET` | `/settings` | `settings.index` | `SettingController@index` | System Settings |
| 25 | `POST` | `/settings` | `settings.update` | `SettingController@update` | System Settings |

---

### B. Controller yang Aktif vs Tidak Aktif

| Controller | Status | Alasan & Bukti Temuan |
|---|---|---|
| `DashboardController` | **AKTIF** | Menangani halaman utama KPI, chart trend 14 hari, Top AR Agent |
| `CustomerController` | **AKTIF** | Menangani katalog pelanggan, detail Customer 360, export CSV |
| `VisitController` | **AKTIF** | Menangani data kunjungan, filter, KPI, export Excel, dan photo stream proxy |
| `PtpMonitoringController` | **AKTIF** | Menangani halaman monitoring janji bayar, filter per agent, export |
| `RiskScoreController` | **AKTIF** | Menangani evaluasi Churn Risk Indicator berbasis ChurnRiskService |
| `PiutangController` | **AKTIF** | Menangani analisis piutang outstanding per aging dan datel |
| `C3mrCaringController` | **AKTIF** | Menangani log Hasil Caring OBC PRITI, VOC filter, status bayar |
| `C3mrPerformanceController`| **AKTIF** | Menangani rekapitulasi performa billing, CYC, Cash Ratio per Witel |
| `C3mrSyncController` | **AKTIF** | Orchestrator sinkronisasi satu pintu (AJAX POST `/c3mr/sync/all`) |
| `ArAgentController` | **AKTIF** | Menangani katalog profil AR Agent dan statistik kunjungan |
| `SettingController` | **AKTIF** | Menangani konfigurasi URL Google Spreadsheet terpusat |
| `GlobalSearchController` | **AKTIF** | Menangani pencarian lintas entitas (Pelanggan, Kunjungan, Caring, AR) |
| `TelegramChatController` | **DORMANT** | **TIDAK ADA ROUTE** di `web.php`, tidak ada form/action kirim di view |
| `ImportController` | **LEGACY** | Kode sisa sebelum fitur disatukan ke Sync C3MR |
| `SyncController` | **LEGACY** | Pengendali sync lama yang kini telah digantikan oleh `C3mrSyncController` |
| `PtpController` | **LEGACY** | Route `/ptp` bersifat redundan dengan `/ptp-monitoring` |

---

### C. Analisis Khusus: Fitur Telegram

> **Hasil Verifikasi**:
> 1. File `app/Services/TelegramService.php` dan `app/Http/Controllers/TelegramChatController.php` ada di codebase.
> 2. Namun, **tidak ada route** di `routes/web.php` yang mengarah ke `TelegramChatController`.
> 3. Di view `resources/views/ar-agents/index.blade.php`, Telegram hanya muncul sebagai **badge pasif** status konektivitas (`@if($agent->chat_id_telegram) <span class="badge">Telegram connected</span>`).
> 4. Tidak ada tombol kirim pesan, webhook bot interaktif, maupun automated reminder cron job yang aktif di production.
> 
> **Keputusan Arsitektur**:
> Telegram **TIDAK DIMASUKKAN** sebagai Aktor Eksternal, Use Case, maupun alur proses bisnis di Use Case / Activity / Sequence Diagram. Telegram diklasifikasikan sebagai modul *future enhancement* / dormant.

---

### D. External Dependencies yang Benar-Benar Aktif

1. **Google Spreadsheet C3MR** (`docs.google.com/spreadsheets`):
   - Diambil secara live oleh `C3mrSyncService` via HTTP GET endpoint CSV (`/export?format=csv` dan `/gviz/tq?sheet=...`).
   - Melayani 4 sheet utama: Report PRQ, VISEEPRO, DATA ALL, dan PERFORMANSI DETAIL.
2. **Google Drive** (`drive.google.com`):
   - Menyimpan foto bukti fisik kunjungan lapangan yang diunggah AR Agent.
   - Diakses melalui backend proxy `VisitController@photo` (`drive.google.com/uc?id={fileId}&export=view`) untuk mengatasi isu CORS dan otentikasi browser.

---

## 4. Struktur Menu UI Deployed (`resources/views/layouts/app.blade.php`)

Sidebar navigasi CollectIQ terbagi menjadi 5 grup menu resmi:

```
COLLECTIQ SIDEBAR
├── [Main]
│   ├── Dashboard                (/)
│   ├── Customers                (/customers)
│   └── Visits                   (/visits)
├── [Collection & Piutang]
│   ├── PTP Monitoring           (/ptp-monitoring)
│   ├── Risk Score               (/risk-score)
│   └── Piutang Outstanding      (/piutang)
├── [C3MR Intelligence]
│   ├── Hasil Caring OBC         (/c3mr/hasil-caring)
│   ├── Churn Risk & Witel       (/c3mr/performance)
│   └── Sync Data C3MR           (/c3mr/sync)
├── [Agents]
│   └── AR Agents                (/ar-agents)
└── [System]
    └── Settings                 (/settings)
```

---

## 5. Rekomendasi Diagram untuk Dokumen Skripsi / Laporan KP

| No | Nama File Diagram | Tipe Diagram | Cakupan & Fokus |
|---|---|---|---|
| 01 | `01_erd.puml` | Entity Relationship Diagram | 9 Entitas inti database MySQL + relasi PK/FK |
| 02 | `02_use_case_dashboard.puml`| Use Case Diagram | Sub-sistem Dashboard, Customer 360, & Visits |
| 03 | `03_use_case_collection.puml`| Use Case Diagram | Sub-sistem PTP Monitoring, Risk Score, Piutang, & AR |
| 04 | `04_use_case_c3mr.puml` | Use Case Diagram | Sub-sistem Hasil Caring OBC, Witel, & Master Sync C3MR |
| 05 | `05_activity_sync_c3mr.puml`| Activity Diagram (Swimlane)| Alur E2E Master Sinkronisasi C3MR 6-in-1 |
| 06 | `06_activity_visit.puml` | Activity Diagram | Alur Monitoring Kunjungan & Photo Proxy Google Drive |
| 07 | `07_activity_ptp.puml` | Activity Diagram | Alur Monitoring Janji Bayar & Tindak Lanjut WA |
| 08 | `08_sequence_sync_c3mr.puml`| Sequence Diagram | Interaksi Browser -> Controller -> Service -> GSheet -> DB |
| 09 | `09_sequence_visit_detail.puml`| Sequence Diagram | Interaksi Detail Kunjungan & Proxy Stream Foto |
| 10 | `10_sequence_ptp.puml` | Sequence Diagram | Interaksi PTP Monitoring & Streaming Ekspor CSV |
| 11 | `11_class_diagram.puml` | Class Diagram (High-Level) | Hubungan Controller, Service, dan Eloquent Model aktif |
| 12 | `12_component_architecture.puml`| Component Diagram | Arsitektur berlapis (Presentation, App, Logic, Data, Ext) |
| 13 | `13_deployment_diagram.puml`| Deployment Diagram | Topologi Railway PaaS (Nginx Proxy, PHP 8.2, MySQL 8.0) |

---
*Laporan Audit diverifikasi dan disahkan berdasarkan codebase CollectIQ commit `main` — Agustus 2026.*
