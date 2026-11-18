# Development Plan dan Task List
## Sistem Informasi Manajemen Operasional pada Percetakan Midad Al-Ilmi Berbasis Web Menggunakan Framework Laravel

Dokumen ini disusun berdasarkan **Analisis Komprehensif.docx** dan **Blueprint.docx** sebagai acuan kerja terstruktur untuk pengembangan aplikasi web back-office **Percetakan Midad Al-Ilmi** menggunakan metode pengembangan **Waterfall** (Analisis → Desain → Implementasi → Pengujian → Pemeliharaan).

---

## 1. Ringkasan Proyek & Arsitektur Teknis

| Komponen | Spesifikasi |
|---|---|
| **Nama Sistem** | SIM Operasional Percetakan Midad Al-Ilmi (Back-Office Terpusat) |
| **Direktori Kode** | `midad-al-ilmi/` |
| **Backend Framework** | Laravel 12 (PHP 8.2+, pola arsitektur MVC + Event-Driven `OrderObserver`) |
| **Database** | SQLite (pengembangan lokal instan) & siap migrasi penuh ke **MySQL** (produksi) |
| **Antarmuka (UI)** | Blade Templates + Tailwind CSS v4 (Vite) + Chart.js |
| **Library Ekspor & Analitik** | `barryvdh/laravel-dompdf` (Invoice & Laporan PDF), `maatwebsite/excel` (Laporan `.xlsx`), `chart.js` (Grafik Omset) |
| **Zona Waktu Sistem** | `Asia/Jakarta` (WIB) agar pencatatan `clock_in`, `clock_out`, dan transaksi akurat |
| **Ekspansi Masa Depan** | Endpoint REST API `/api/v1/*` + kesiapan PWA untuk pemantauan jarak jauh oleh Owner |

---

## 2. Pemetaan 3 Pilar Operasional & Alur 7 Tahap

1. **Pilar SDM (HR & Absensi Real-Time)**
   - **Tahap 1 (Absensi Masuk)**: Karyawan (`admin`, `operator`, `owner`) melakukan login dan menekan tombol **Absen Masuk** (`user_id`, `date`, `clock_in`).
   - **Tahap 6 (Absensi Pulang)**: Karyawan menekan tombol **Absen Pulang** (`clock_out`) dan sistem menghitung otomatis `total_hours`.
2. **Pilar CRM, Order Entry & Keuangan (POS Split Payment)**
   - **Tahap 2 (Penerimaan Pesanan & Validasi Stok)**: Admin mengecek/menambah data pelanggan (`customers`), menyalin spesifikasi pesanan dari WhatsApp/walk-in ke `orders` & `order_details` (produk, ukuran, bahan, qty, finishing, link/file desain). Sistem **memblokir penyimpanan** dan menampilkan **Insufficient Stock Alert** jika `qty` melebihi `current_stock`.
   - **Tahap 3 (Transaksi & Pembayaran)**: Admin menginput pembayaran awal (**DP** atau **Lunas**, Tunai/Transfer) di modul Kasir, menghasilkan **Nota Digital (Invoice PDF)** untuk dikirim via WhatsApp.
   - **Tahap 5 (Penyelesaian & Pelunasan)**: Setelah produksi selesai (`siap_diambil`), Admin mencatat pelunasan sisa piutang. Order baru dapat ditutup menjadi `closed` (`Sudah Diambil`) **jika dan hanya jika** `payment_status == 'paid'` (lunas 100%).
3. **Pilar Inventori Otomatis & Transparansi Produksi**
   - **Tahap 4 (Antrean Produksi & Trigger `OrderObserver`)**: Pesanan berstatus pembayaran `dp` atau `paid` otomatis tampil di daftar **Antrean Produksi** Operator beserta spesifikasi dan tautan/berkas desain. Saat status order diubah menjadi `diproduksi` (`Sedang Diproduksi`), **`OrderObserver`** otomatis mendeteksi perubahan tersebut dan memotong `current_stock` pada tabel `materials` sesuai detail `order_details` secara atomik.
4. **Pemantauan Manajerial & Laporan Periodik**
   - **Tahap 7 (Dashboard & Pelaporan)**: Dasbor menampilkan KPI real-time, grafik omset harian (Chart.js), dan **Warning Box** bahan baku di bawah `minimal_stock_alert`. Menu Laporan khusus **Owner** menyediakan filter rentang tanggal serta ekspor **Excel (`.xlsx`)** dan **PDF** untuk 4 laporan: Penjualan & Omset, Kas Masuk (Lunas & DP), Sisa Stok Bahan Gudang, dan Kehadiran Karyawan.

---

## 3. Desain Basis Data Relasional (3NF)

1. **`users`**: `id`, `name`, `email`, `password`, `role` (`admin` | `operator` | `owner`), `timestamps`
2. **`attendances`**: `id`, `user_id` (FK), `date`, `clock_in`, `clock_out`, `total_hours`, `timestamps`
3. **`customers`**: `id`, `name`, `phone_whatsapp`, `timestamps`
4. **`materials`**: `id`, `name`, `type`, `current_stock`, `unit`, `minimal_stock_alert`, `timestamps`
5. **`orders`**: `id`, `order_number` (unik), `customer_id` (FK), `user_id` (FK admin penginput), `total_amount`, `payment_status` (`unpaid` | `dp` | `paid`), `order_status` (`antrean` | `diproduksi` | `siap_diambil` | `closed`), `file_design_link`, `design_file_path` (opsional unggah lokal), `stock_deducted_at` (penjaga idempotensi Observer), `timestamps`
6. **`order_details`**: `id`, `order_id` (FK), `product_name`, `material_id` (FK), `qty`, `size`, `finishing_notes`, `sub_total`, `timestamps`
7. **`payments`**: `id`, `order_id` (FK), `amount_paid`, `payment_type` (`dp` | `lunas`), `payment_method` (`tunai` | `transfer`), `date_paid`, `notes`, `timestamps`

---

## 4. Matriks Hak Akses (Role-Based Access Control)

| Modul / Fitur | Admin | Operator | Owner |
|---|:---:|:---:|:---:|
| Dashboard Operasional (Antrean & Warning Stok) | Ya | Ya (Fokus Produksi) | Ya (Full Analytics + Omset) |
| Absensi Masuk & Pulang Pribadi | Ya | Ya | Ya |
| Manajemen Karyawan (`users`) | Tidak | Tidak | Ya |
| CRM Pelanggan (`customers`) | Ya | Lihat Detail Order | Ya |
| Order Entry & Validasi Stok | Ya | Tidak | Ya |
| Kasir / Pembayaran (DP & Pelunasan) & Invoice PDF | Ya | Tidak | Ya |
| Antrean Produksi & Ubah Status ke `diproduksi` / `siap_diambil` | Ya | Ya | Ya |
| Tutup Pesanan (`closed` setelah lunas) | Ya | Tidak | Ya |
| Master Data Bahan Baku (`materials`) | Lihat Stok | Lihat Stok | Ya (CRUD & Restock) |
| Laporan Periodik & Ekspor Excel/PDF | **Ditolak (403)** | **Ditolak (403)** | Ya |

---

## 5. Rencana Pengembangan (Development Plan - Metode Waterfall)

### Fase 1: Analisis & Inisialisasi Proyek
- Menyelaraskan kebutuhan fungsional dari `Blueprint.docx` dan `Analisis Komprehensif.docx`.
- Menyiapkan proyek Laravel 12 (`midad-al-ilmi/`), mengonfigurasi timezone `Asia/Jakarta`, koneksi database (SQLite lokal & siap MySQL), serta memasang library `barryvdh/laravel-dompdf`, `maatwebsite/excel`, dan `chart.js`.

### Fase 2: Desain & Migrasi Basis Data (3NF)
- Membuat 7 migrasi tabel relasional dengan foreign key constraint yang ketat.
- Membangun Model Eloquent beserta relasi (`hasMany`, `belongsTo`) dan helper scopes.
- Membuat `DatabaseSeeder` untuk akun demo (`owner`, `admin`, `operator`), master bahan baku, pelanggan, dan contoh transaksi awal.

### Fase 3: Fondasi Autentikasi, RBAC & Modul HR/Absensi
- Mengimplementasikan login/logout berbasis sesi Laravel dan middleware `RoleMiddleware`.
- Mengimplementasikan manajemen data karyawan (khusus Owner).
- Mengimplementasikan fitur Absen Masuk (`clock_in`), Absen Pulang (`clock_out`), pencegahan absen ganda pada tanggal sama, serta kalkulasi otomatis `total_hours`.

### Fase 4: Modul CRM, Order Entry & Validasi Stok Real-Time
- Mengimplementasikan CRUD Pelanggan beserta riwayat pesanan pelanggan.
- Mengimplementasikan form Order Entry dinamis (multi-item `order_details` dalam 1 nomor order).
- Mengimplementasikan pengecekan akumulasi kebutuhan per `material_id` terhadap `current_stock` sebelum order tersimpan, serta menampilkan **Insufficient Stock Alert** bila stok kurang.
- Mendukung input tautan desain (Google Drive/WA link) maupun unggah file desain langsung.

### Fase 5: Modul Kasir (POS Split Payment) & Nota Digital PDF
- Mengimplementasikan pencatatan pembayaran bertahap (`dp` / `lunas`) dengan metode `tunai` / `transfer`.
- Mengkalkulasi total terbayar, sisa tagihan, dan pembaruan otomatis `payment_status` (`unpaid` → `dp` → `paid`).
- Memblokir perubahan `order_status` menjadi `closed` apabila `payment_status !== 'paid'`.
- Membuat template Nota Digital / Invoice PDF menggunakan `barryvdh/laravel-dompdf` serta tombol salin pesan tagihan WhatsApp.

### Fase 6: Modul Inventori & Otomatisasi `OrderObserver`
- Mengimplementasikan CRUD Master Bahan Baku (`materials`), status stok (Aman / Menipis / Habis), dan fitur penambahan stok (Restock).
- Mengimplementasikan halaman **Antrean Produksi** yang hanya menampilkan pesanan berstatus bayar `dp` atau `paid`, lengkap dengan detail ukuran, catatan finishing, dan tombol akses/unduh file desain.
- Mengimplementasikan **`App\Observers\OrderObserver`** yang mendengarkan perubahan `order_status` menjadi `diproduksi`, lalu memotong `current_stock` untuk seluruh baris `order_details` di dalam transaksi database (`DB::transaction`) dengan pengaman idempotensi (`stock_deducted_at`) agar stok tidak terpotong ganda.

### Fase 7: Dashboard Real-Time, Laporan Periodik, Ekspor & REST API
- Mengimplementasikan Dashboard utama dengan ringkasan KPI, grafik batang/garis omset harian (**Chart.js**), daftar pesanan aktif, dan **Warning Box** stok kritis.
- Mengimplementasikan 4 Laporan Periodik (filter `start_date` – `end_date`) khusus **Owner**:
  1. Laporan Penjualan & Omset
  2. Laporan Kas Masuk (Lunas & DP)
  3. Laporan Sisa Stok Bahan Gudang
  4. Laporan Kehadiran Karyawan
- Mengimplementasikan ekspor masing-masing laporan ke **Excel (`.xlsx`)** via `maatwebsite/excel` dan **PDF** via `barryvdh/laravel-dompdf`.
- Menyiapkan endpoint **REST API** (`/api/v1/summary`) & manifest dasar untuk ekspansi PWA / Flutter di masa depan.

### Fase 8: Verifikasi, Pengujian Black Box & Kesiapan ISO 25010
- Menjalankan PHPUnit Feature & Unit Tests otomatis untuk seluruh alur kritis.
- Memverifikasi 4 skenario uji eksperimental wajib dari `Analisis Komprehensif.docx` (Batas Ambang Kuantitas, Kalkulasi Akumulatif Observer, Integritas Penutupan Keuangan, Fragmentasi Akses Dasbor).
- Melakukan build aset produksi (`npm run build`) dan verifikasi antarmuka di browser.

---

## 6. Task List Implementasi (Checklist Eksekusi)

### A. Inisialisasi & Konfigurasi
- [x] **T-01**: Ekstraksi dan telaah spesifikasi `Analisis Komprehensif.docx` dan `Blueprint.docx`
- [x] **T-02**: Setup proyek Laravel 12 di `midad-al-ilmi/` dan verifikasi dependensi `composer install`
- [x] **T-03**: Instalasi paket `barryvdh/laravel-dompdf`, `maatwebsite/excel`, dan `chart.js`
- [x] **T-04**: Konfigurasi `.env`, `APP_TIMEZONE=Asia/Jakarta`, serta aset Tailwind CSS v4 (`npm install && npm run build`)

### B. Database & Model (3NF)
- [x] **T-05**: Buat migrasi tabel `users` (tambah kolom `role`), `attendances`, `customers`, `materials`, `orders`, `order_details`, `payments`
- [x] **T-06**: Buat Model Eloquent (`User`, `Attendance`, `Customer`, `Material`, `Order`, `OrderDetail`, `Payment`) beserta relasi dan helper method
- [x] **T-07**: Buat `DatabaseSeeder` (akun Owner, Admin, Operator, data bahan baku, pelanggan, dan contoh pesanan)

### C. Autentikasi, RBAC & Modul HR/Absensi
- [x] **T-08**: Implementasi Login, Logout, Layout Dashboard responsif (Sidebar per role), dan `RoleMiddleware`
- [x] **T-09**: Implementasi CRUD Karyawan oleh Owner (`UserController`)
- [x] **T-10**: Implementasi Modul Absensi Clock-In & Clock-Out (`AttendanceController`) dengan kalkulasi `total_hours`

### D. Modul CRM, Order Entry & Validasi Stok
- [x] **T-11**: Implementasi CRUD Pelanggan (`CustomerController`) + riwayat pesanan
- [x] **T-12**: Implementasi Order Entry multi-item (`OrderController`) dengan input link/upload file desain
- [x] **T-13**: Implementasi validasi stok real-time & **Insufficient Stock Alert** (memblokir penyimpanan jika `qty > current_stock`)

### E. Modul Kasir (POS) & Nota Digital PDF
- [x] **T-14**: Implementasi input pembayaran split (`DP` & `Pelunasan`, Tunai/Transfer) di `PaymentController`
- [x] **T-15**: Implementasi blokir status `closed` jika pesanan belum lunas 100% (`payment_status !== 'paid'`)
- [x] **T-16**: Implementasi cetak/unduh Invoice PDF via `barryvdh/laravel-dompdf` + salin format pesan WhatsApp

### F. Modul Inventori, Antrean Produksi & `OrderObserver`
- [x] **T-17**: Implementasi CRUD Master Bahan Baku (`MaterialController`) & fitur restock + deteksi stok minimum
- [x] **T-18**: Implementasi halaman Antrean Produksi (`ProductionController`) untuk Operator (hanya pesanan `dp`/`paid`, akses unduh/buka desain)
- [x] **T-19**: Implementasi `OrderObserver` (pemotongan stok `materials.current_stock` otomatis saat `order_status` menjadi `diproduksi`) + registrasi Observer

### G. Dashboard Analytics, Laporan Periodik & API
- [x] **T-20**: Implementasi Dashboard Real-Time (`DashboardController`) dengan KPI, Chart.js omset harian, dan Warning Box stok kritis
- [x] **T-21**: Implementasi 4 Laporan Periodik khusus Owner (`ReportController`) dengan filter rentang tanggal
- [x] **T-22**: Implementasi Export Classes (`maatwebsite/excel`) dan View PDF (`dompdf`) untuk seluruh laporan
- [x] **T-23**: Implementasi endpoint REST API (`/api/v1/summary`) dan `manifest.webmanifest` dasar untuk kesiapan PWA/Flutter

### H. Pengujian & Verifikasi Akhir
- [x] **T-24**: Buat Feature Tests PHPUnit mencakup:
  - Test 1: Blokir Order Entry ketika `qty` melebihi `current_stock` (Insufficient Stock Alert)
  - Test 2: Pemotongan stok otomatis melalui `OrderObserver` saat status berubah ke `diproduksi` (dan tidak memotong dua kali)
  - Test 3: Blokir penutupan order (`closed`) saat pembayaran masih `dp` / kurang dari total tagihan
  - Test 4: Proteksi rute laporan keuangan menolak `operator` & `admin` (`403 Forbidden`)
  - Test 5: Alur Absensi Clock-In & Clock-Out menghitung `total_hours` serta verifikasi ekspor PDF & Excel
- [x] **T-25**: Jalankan `php artisan test`, migrasi & seeding, serta `npm run build` hingga seluruh verifikasi lulus tanpa error

---

## 7. Matriks Uji Black Box (Acuan Validasi Akhir)

| No | Parameter Uji | Skenario Eksperimental | Ekspektasi Hasil Sistem |
|---|---|---|---|
| 1 | **Batas Ambang Kuantitas** | Admin input pesanan dengan `qty` melebihi `current_stock` bahan | Sistem membatalkan simpan & menampilkan peringatan **Insufficient Stock Alert** |
| 2 | **Kalkulasi Akumulatif Observer** | Operator mengubah status order dari `antrean` ke `diproduksi` | `OrderObserver` otomatis mengurangi `current_stock` di tabel `materials` sesuai `order_details.qty` |
| 3 | **Integritas Penutupan Keuangan** | Pembayaran ke-2 masih lebih kecil dari total sisa tagihan, lalu status dicoba diubah ke `closed` | Status bayar tetap `dp`; sistem menolak perubahan status menjadi `closed` sebelum lunas 100% |
| 4 | **Fragmentasi Akses Dasbor** | Operator mengakses URL laporan omset/keuangan manajerial | Middleware menghentikan akses dengan status HTTP `403` / halaman Unauthorized |

