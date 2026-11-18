# SIM Operasional Percetakan Midad Al-Ilmi

Aplikasi web back-office berbasis **Laravel 12** yang dikembangkan dari dokumen `Blueprint.docx` dan `Analisis Komprehensif.docx`.

## Fitur Utama
1. **HR & Absensi Real-Time**: Clock-In & Clock-Out karyawan beserta kalkulasi otomatis `total_hours`.
2. **CRM & Order Entry**: Direktori pelanggan WhatsApp, entri pesanan multi-item (`orders` + `order_details`), unggah/link file desain, dan **validasi stok real-time** (*Insufficient Stock Alert*).
3. **Kasir POS Split Payment & Nota Digital**: Pencatatan DP & Pelunasan (Tunai/Transfer), integritas penutupan order (`closed` hanya setelah lunas 100%), serta cetak Nota PDF (`barryvdh/laravel-dompdf`).
4. **Antrean Produksi & Otomatisasi `OrderObserver`**: Pesanan berstatus `DP` atau `Lunas` otomatis tampil untuk Operator. Saat status diubah ke `Sedang Diproduksi`, `App\Observers\OrderObserver` otomatis memotong `materials.current_stock` secara atomik.
5. **Dashboard & Laporan Manajerial**: Grafik kas masuk harian (Chart.js), Warning Box stok menipis, serta 4 laporan periodik khusus **Owner** dengan ekspor **Excel (`.xlsx`)** dan **PDF**.

## Cara Menjalankan Lokal (SQLite - Langsung Jalan)
```bash
cd midad-al-ilmi
php artisan migrate --seed
php artisan serve
```
Lalu buka `http://127.0.0.1:8000` di browser.

### Akun Demo
Semua akun menggunakan kata sandi `password`:
- **Owner / Pemilik**: `owner@midad.test`
- **Admin / Kasir**: `admin@midad.test`
- **Operator Produksi**: `operator@midad.test`

## Beralih ke Database MySQL (XAMPP)
1. Nyalakan service **MySQL** di XAMPP Control Panel dan buat database `midad_al_ilmi`.
2. Ubah konfigurasi di `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=midad_al_ilmi
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Jalankan `php artisan migrate --seed`.

## Pengujian Otomatis
```bash
php artisan test
```
