# Dokumentasi Database SiMover (Sistem Monitoring Over Dimension)

Direktori ini memuat snapshot struktur skema dan data awal database SiMover untuk kemudahan penyiapan lingkungan pengujian atau instalasi baru.

---

## 1. Berkas Dump
- **Lokasi**: `database/dumps/monitoring_dishub.sql`
- **Isi Snapshot**:
  - Definisi tabel `admin_users` dan tabel `migrations`.
  - Data akun admin awal (termasuk hash password bcrypt yang telah diotorisasi pengguna untuk disertakan).
  - Riwayat entri tabel `migrations` pada saat ekspor snapshot dibuat.

> [!WARNING]
> **Peringatan Keamanan & Privasi**:  
> Berkas dump ini menyertakan hash password administratif. Pada repositori publik, data hash ini dapat diakses secara terbuka. Pastikan untuk memperbarui password akun admin di lingkungan produksi setelah instalasi awal.  
> Kredensial lain seperti `.env`, secret RTSP NVR, session aktif, dan token API tetap dikecualikan dari repositori.

---

## 2. Petunjuk Penyiapan & Impor Database

### Pilihan A: Mengimpor Snapshot Dump ke Database Kosong (Disarankan untuk Clone Cepat)
Gunakan opsi ini jika Anda ingin langsung memiliki skema tabel dan akun admin awal yang terkonfigurasi:
1. Buat database baru di MySQL/MariaDB:
   ```sql
   CREATE DATABASE IF NOT EXISTS monitoring_dishub CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```
2. Impor berkas SQL dump:
   ```bash
   mysql -u [username] -p monitoring_dishub < database/dumps/monitoring_dishub.sql
   ```
3. Sesuaikan konfigurasi koneksi database pada berkas `.env` aplikasi Anda:
   ```env
   database.default.database = monitoring_dishub
   database.default.username = [username]
   database.default.password = [password]
   ```

> [!IMPORTANT]
> **Catatan Konflik Migrasi**:  
> Berkas dump sudah memuat tabel `admin_users` dan pencatatan riwayat `migrations`. Jangan menjalankan perintah `php spark migrate` pada database yang baru saja diimpor dari dump ini tanpa memeriksa status migrasi terlebih dahulu (`php spark migrate:status`), karena dapat menyebabkan konflik pembuatan tabel ganda.

---

### Pilihan B: Menggunakan Migration CodeIgniter (Basis Skema Murni)
Jika Anda menggunakan alur deployment bersih dari framework:
1. Pastikan database kosong sudah dibuat dan dikonfigurasi di `.env`.
2. Jalankan migration bawaan:
   ```bash
   php spark migrate
   ```
