# Dokumentasi Teknis Backend Akun Admin Nyata (CodeIgniter 4)

## 1. Lingkup Pekerjaan
Pekerjaan ini mengimplementasikan autentikasi akun administrator nyata berbasis database MariaDB/MySQL lokal, menggantikan sistem akun demo simulasi sebelumnya:
* Koneksi database lokal via `.env`.
* Migrasi tabel `admin_users` dengan skema keamanan terarah.
* Inisialisasi (bootstrap) akun administrator awal melalui CLI Command `admin:bootstrap`.
* Autentikasi server-side nyata dengan `password_verify` dan mitigasi timing attack.
* Proteksi rute admin dan validasi sesi server-side via `AdminAuthFilter` (memeriksa eksistensi akun, keaktifan, role, dan kecocokan `auth_version`).
* Pembatasan percobaan login (Rate Limiting) sisi server via `Throttler` dan persistent file cache.
* Proteksi CSRF berbasis sesi pada seluruh aksi POST (`login`, `logout`, `ubah-kata-sandi`) dengan sinkronisasi token otomatis.
* Fitur Ubah Kata Sandi permanen yang memperbarui hash dan menaikkan `auth_version` untuk menganulir sesi lama.
* Header `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` untuk mencegah pembukaan kembali data via riwayat browser (tombol Back) setelah logout.

---

## 2. Struktur Tabel Database (`admin_users`)
Tabel dibuat menggunakan Engine **InnoDB** dan Charset **utf8mb4**:

| Nama Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | INT(11) UNSIGNED AUTO_INCREMENT | Primary Key |
| `username` | VARCHAR(100) NOT NULL UNIQUE | Username terdaftar (dinormalisasi huruf kecil) |
| `full_name` | VARCHAR(150) NOT NULL | Nama lengkap / tampilan administrator |
| `password_hash` | VARCHAR(255) NOT NULL | Hash kata sandi (`PASSWORD_DEFAULT` / bcrypt) |
| `role` | VARCHAR(30) DEFAULT 'admin' | Hak akses pengguna |
| `is_active` | TINYINT(1) DEFAULT 1 | Status akun (1 = aktif, 0 = nonaktif) |
| `auth_version` | INT(11) DEFAULT 1 | Versi autentikasi untuk membatalkan sesi lama saat sandi diganti |
| `last_login_at` | DATETIME NULL | Waktu terakhir berhasil login (WITA) |
| `created_at` | DATETIME NULL | Waktu akun dibuat |
| `updated_at` | DATETIME NULL | Waktu pembaruan terakhir |

---

## 3. Berkas yang Diubah dan Dibuat
1. **Dibuat:**
   * `app/Database/Migrations/2026-10-07-024914_CreateAdminUsersTable.php` — Definisi migrasi tabel akun admin.
   * `app/Models/AdminUserModel.php` — Model data akun admin dengan field terbatas dan metode atomik.
   * `app/Commands/AdminBootstrap.php` — Perintah CLI spark `admin:bootstrap` untuk inisialisasi akun aman.
   * `BACKEND_AKUN_ADMIN.md` — Dokumentasi teknis sistem autentikasi.
2. **Diperbarui:**
   * `.env` — Konfigurasi koneksi database default `monitoring_dishub`.
   * `app/Filters/AdminAuthFilter.php` — Filter keamanan sesi nyata, validasi `auth_version`, respon AJAX 401, dan header no-store.
   * `app/Controllers/Admin/AuthController.php` — Controller autentikasi penuh (login database, rate limiting, mitigasi timing, logout, ubah sandi permanen).
   * `app/Views/admin/auth/login.php` — Pembersihan kotak informasi akun demo lama, penyesuaian teks keamanan resmi.
   * `app/Views/admin/layout/main.php` — Penyesuaian badge status menjadi "Data Uji" (tanpa menyatakan akun masih demo).
   * `app/Views/admin/layout/account_modal.php` — Penyesuaian pesan notifikasi perubahan sandi permanen.
   * `public/assets/js/admin.js` — Penanganan respon AJAX 401 saat sesi kedaluwarsa dan pembaruan CSRF token otomatis.

---

## 4. Konfigurasi Database (Contoh Placeholder `.env`)
Konfigurasi database diletakkan di file `.env` (diabaikan oleh Git):
```ini
# Database Default
database.default.hostname = 127.0.0.1
database.default.database = monitoring_dishub
database.default.username = your_db_username
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.DBPrefix = 
database.default.port = 3306
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_general_ci
```

---

## 5. Cara Menjalankan Migrasi dan Bootstrap Lokal
Gunakan PHP XAMPP (`D:\Apps\xampp\php\php.exe`):

1. **Jalankan Migrasi:**
   ```powershell
   & "D:\Apps\xampp\php\php.exe" spark migrate
   ```
2. **Inisialisasi Akun Admin Awal:**
   * Masukkan variabel sementara ke `.env` lokal:
     ```ini
     BOOTSTRAP_ADMIN_USERNAME = admin
     BOOTSTRAP_ADMIN_NAME = 'Admin Dishub'
     BOOTSTRAP_ADMIN_PASSWORD = 'your_initial_password'
     ```
   * Jalankan perintah CLI:
     ```powershell
     & "D:\Apps\xampp\php\php.exe" spark admin:bootstrap
     ```
   * Hapus kembali baris `BOOTSTRAP_ADMIN_*` dari `.env`. Perintah ini idempoten dan menolak menduplikasi akun jika username sudah ada.

---

## 6. Prosedur Login, Logout, dan Ubah Kata Sandi
1. **Login:**
   * Buka URL: `http://localhost:8080/admin/login`
   * Masukkan username dan password yang telah diinisialisasi.
   * Sistem memvalidasi akun aktif, mencatat waktu login, meregenerasi ID sesi, dan mengarahkan ke dashboard.
2. **Logout:**
   * Buka dropdown akun di pojok kiri bawah sidebar, klik **Keluar (Logout)**.
   * Konfirmasi pada form POST yang terproteksi token CSRF.
   * Sesi server dihancurkan tuntas dan akses rute langsung ditolak.
3. **Ubah Kata Sandi:**
   * Buka dropdown akun di pojok kiri bawah sidebar, klik **Ubah Kata Sandi**.
   * Masukkan kata sandi saat ini, kata sandi baru (minimal 8 karakter), dan konfirmasi.
   * Klik **Simpan Perubahan**. Sistem memverifikasi kata sandi lama, menyimpan hash baru secara permanen, menaikkan `auth_version`, dan memperbarui sesi aktif.

---

## 7. Cara Aman Menjalankan Pengujian (Testing)
Pengujian otomatis harus menggunakan database pengujian terpisah agar tidak menimpa data pada database pengembangan `monitoring_dishub`:
* Konfigurasi grup `tests` di `.env` atau `app/Config/Database.php` menggunakan database terpisah (misalnya SQLite `:memory:` atau database MySQL khusus testing seperti `monitoring_dishub_test`).
* Jangan menjalankan `migrate:refresh` pada database pengembangan.
* Jalankan unit test melalui:
  ```powershell
  & "D:\Apps\xampp\php\php.exe" vendor/phpunit/phpunit/phpunit tests/unit
  ```

---

## 8. Status Data Deteksi
* **Penting:** Implementasi ini hanya mencakup autentikasi akun administrator.
* Data lalu lintas, jumlah deteksi harian/mingguan (128 deteksi hari ini, 848 deteksi 7 hari), grafik Chart.js, filter, dan data CCTV tetap bersumber dari dataset deterministik `DummyDataService`.
* Badge pada topbar admin ditampilkan sebagai **Data Uji** untuk mencerminkan status simulasi dataset secara jujur.

---

## 9. Checklist Keamanan Sebelum Deployment / Hosting
Sebelum sistem diunggah ke server produksi/hosting, lakukan langkah-langkah berikut:
- [ ] Ubah kata sandi admin lokal melalui modal Ubah Kata Sandi menjadi kata sandi baru yang kuat, panjang, dan unik.
- [ ] Ubah `CI_ENVIRONMENT = production` pada file `.env` server.
- [ ] Aktifkan protokol HTTPS dan pastikan `app.baseURL` menggunakan `https://`.
- [ ] Buat pengguna database MySQL khusus dengan hak akses terbatas (bukan `root`) dan kata sandi acak yang kuat.
- [ ] Pastikan file `.env` tidak dapat diakses langsung melalui web server (blokir akses ke file tersembunyi).
- [ ] Generate encryption key baru via `php spark key:generate`.
