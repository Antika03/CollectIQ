# Dokumentasi Diagram Arsitektur & UML
## CollectIQ — PTP Collection Intelligence Dashboard
### Telkom Witel Priangan Timur (Laporan Kerja Praktik / Skripsi)

---

## 📌 Gambaran Umum

Seluruh diagram dalam repositori ini disusun berdasarkan **standar akademik UML (Unified Modeling Language)** dan menggunakan format **PlantUML (`.puml`)**. 

Setiap diagram telah diaudit dan diverifikasi langsung dari basis kode Laravel yang aktif dan aplikasi yang berjalan di **Railway Platform as a Service ([https://collectiq.up.railway.app/](https://collectiq.up.railway.app/))**.

---

## 📂 Struktur File Diagram

| No | Nama File | Tipe Diagram | Deskripsi & Cakupan |
|:---:|---|---|---|
| **01** | [`01_erd.puml`](01_erd.puml) | **Entity Relationship Diagram (ERD)** | 9 entitas database utama (`customers`, `visits`, `ar_agents`, `promise_to_pays`, `caring_logs`, `viseepro_data`, `witel_performances`, `settings`, `users`) berserta tipe data, PK, FK, Unique Key, dan kardinalitas relasi. |
| **02** | [`02_use_case_dashboard.puml`](02_use_case_dashboard.puml) | **Use Case Diagram (Bagian 1)** | Sub-sistem Dashboard, Customer 360, Pencarian Global, dan Monitoring Kunjungan (Visits). |
| **03** | [`03_use_case_collection.puml`](03_use_case_collection.puml) | **Use Case Diagram (Bagian 2)** | Sub-sistem Collection Intelligence: PTP Monitoring, Evaluasi Risk Score, Piutang Outstanding, dan Katalog AR Agent. |
| **04** | [`04_use_case_c3mr.puml`](04_use_case_c3mr.puml) | **Use Case Diagram (Bagian 3)** | Sub-sistem C3MR Intelligence: Hasil Caring OBC, Performansi Witel Regional, dan Master Sync C3MR (6 sub-proses `<<include>>`). |
| **05** | [`05_activity_sync_c3mr.puml`](05_activity_sync_c3mr.puml) | **Activity Diagram** | Alur E2E proses Master Sinkronisasi Data C3MR satu pintu (Swimlane: Browser, Controller, Engine, Database). |
| **06** | [`06_activity_visit.puml`](06_activity_visit.puml) | **Activity Diagram** | Alur Monitoring Kunjungan Lapangan, pemfilteran, pembukaan detail, dan proxy stream foto Google Drive. |
| **07** | [`07_activity_ptp.puml`](07_activity_ptp.puml) | **Activity Diagram** | Alur Monitoring Janji Bayar (PTP), integrasi penagihan via WhatsApp link, dan ekspor data CSV. |
| **08** | [`08_sequence_sync_c3mr.puml`](08_sequence_sync_c3mr.puml) | **Sequence Diagram** | Interaksi sekuensial lengkap proses sinkronisasi C3MR antara Browser, Controller, Service, Google Spreadsheet, dan Database. |
| **09** | [`09_sequence_visit_detail.puml`](09_sequence_visit_detail.puml) | **Sequence Diagram** | Interaksi sekuensial saat menampilkan detail kunjungan dan proxy pengambilan foto bukti fisik dari Google Drive. |
| **10** | [`10_sequence_ptp.puml`](10_sequence_ptp.puml) | **Sequence Diagram** | Interaksi sekuensial saat mengakses data PTP, perhitungan KPI, dan streaming pengunduhan file CSV. |
| **11** | [`11_class_diagram.puml`](11_class_diagram.puml) | **Class Diagram (High-Level)** | Struktur kelas Controller aktif, Service bisnis, Eloquent Models, dan dependensi antar-komponen. |
| **12** | [`12_component_architecture.puml`](12_component_architecture.puml) | **Component Diagram** | Arsitektur perangkat lunak berlapis (*Presentation*, *Application/HTTP*, *Business Logic*, *Data Access*, *Persistence*, dan *External Integration*). |
| **13** | [`13_deployment_diagram.puml`](13_deployment_diagram.puml) | **Deployment Diagram** | Topologi infrastruktur deployment aktual di Railway (Nginx Reverse Proxy, Linux Container PHP 8.2, MySQL 8.0, dan Google Cloud). |
| **—** | [`AUDIT.md`](AUDIT.md) | **Dokumen Laporan Audit** | Laporan audit menyeluruh mengenai route aktif, controller, service, status fitur Telegram, dan batasan implementasi. |

---

## 👥 Aktor & Sistem Eksternal

### Aktor Manusia
1. **Tim Collection / Admin**: Pengguna internal kantor yang memonitor seluruh dashboard, piutang, risk score, hasil caring, performansi witel, konfigurasi spreadsheet, dan eksekusi sinkronisasi data C3MR.
2. **AR Agent**: Petugas collection / survey lapangan yang memantau riwayat kunjungan, data janji bayar (PTP), dan status caring.

### Sistem Eksternal (External Systems)
1. **Google Spreadsheet C3MR**: Sumber data spreadsheet terpusat yang diunduh secara live via HTTP GET CSV endpoint oleh `C3mrSyncService`.
2. **Google Drive**: Penyedia penyimpanan awan untuk foto bukti fisik kunjungan lapangan yang dialirkan melalui proxy controller aplikasi.

> ⚠️ **Catatan Khusus Telegram**:
> Berdasarkan hasil audit kode sumber dan UI, komponen Telegram (`TelegramService`, `TelegramChatController`, `TelegramReminder`) berstatus **dormant / tidak memiliki route aktif dan tombol interaktif pada antarmuka**. Oleh karena itu, Telegram **sengaja tidak dimasukkan** sebagai Aktor maupun Use Case fungsional agar diagram akurat secara akademik.

---

## 🛠️ Cara Merender Diagram PlantUML

### 1. Menggunakan Visual Studio Code (Direkomendasikan)
- Pasang ekstensi **PlantUML** (oleh Jebbs) di VS Code.
- Pastikan Java Runtime Environment (JRE) dan Graphviz terpasang, atau gunakan remote server PlantUML di settings.
- Buka file `.puml` dan tekan `Alt + D` untuk mempratinjau diagram secara langsung.
- Klik kanan pada preview untuk mengekspor ke format **PNG**, **SVG**, atau **PDF** untuk dicantumkan pada naskah skripsi/laporan.

### 2. Menggunakan Editor Online
- Salin seluruh isi file `.puml`.
- Kunjungi salah satu editor online PlantUML resmi:
  - [PlantText UML Editor](https://www.planttext.com/)
  - [PlantUML Official Online Server](https://www.plantuml.com/plantuml/uml/)
- Tempel kode dan unduh diagram dalam resolusi tinggi.

---

*Disusun dan diverifikasi untuk Laporan Kerja Praktik / Skripsi Mahasiswa — Telkom Witel Priangan Timur (2026).*
