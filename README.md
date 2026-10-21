# SiMover — Sistem Monitoring Over Dimension

Sistem Monitoring Over Dimension (SiMover) adalah platform berbasis web untuk pemantauan pelanggaran dimensi kendaraan (Over Dimension) dan pengawasan lalu lintas secara real-time melalui integrasi kamera CCTV streaming.

---

## 1. Tujuan Proyek
- **Pengawasan Real-Time**: Pemantauan langsung jaringan kamera CCTV lalu lintas menggunakan streaming HLS.
- **Deteksi Over Dimension**: Menampilkan visualisasi data klasifikasi kendaraan.
- **Portal Dashboard**: Untuk analisis mingguan, tabel deteksi, dan filtering data.

---

## 2. Kebutuhan Sistem yang Terpasang
- **PHP**: Versi 8.2 (ekstensi: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`)
- **Web Framework**: CodeIgniter 4 (PHP MVC)
- **Database**: MySQL / MariaDB (port default `3306`)
- **Streaming Engine**: FFmpeg & FFprobe (tersedia di `writable/bin/`)
- **Frontend**: Vanilla CSS modular, Bootstrap 5.3, Bootstrap Icons, Leaflet 1.9.4, Chart.js (UMD), Hls.js

---

## 3. Cara Menjalankan Sistem
Petunjuk cepat menjalankan seluruh layanan (web server + database + relay CCTV) tersedia di file root:
[`CARA MENYALAKAN SISTEM.txt`](./CARA%20MENYALAKAN%20SISTEM.txt).

Untuk menjalankan web server CodeIgniter secara mandiri:
```bash
php spark serve --port 8080
```
Akses di browser: `http://localhost:8080/`

Untuk menjalankan relay RTSP -> HLS kamera:
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\vivotek_relay.ps1
```

---

## 4. Halaman Utama Aplikasi
### Portal Pengunjung
- **Beranda**: Ringkasan statistik, hero interaktif, preview peta, dan pengenalan SiMover.
- **Live CCTV**: Grid 18 kamera pengawasan (17 terpasang, 1 unavailable), fitur pencarian, filter simpang, favorit, dan pemutar HLS berlatensi rendah.
- **Dashboard Publik**: Grafik tren harian per jam (Line Chart) dan volume akumulasi (Stacked Bar Chart).
- **Peta Interaktif**: Peta Leaflet interaktif 4 simpang strategis dengan layer switching dinamis (cluster simpang & marker kamera).
- **Tentang**: Profil dan peran
- **Tentang Over Dimension**: Edukasi regulasi dimensi kendaraan, dampak pelanggaran, dan alur penindakan.

### Panel Admin
- **Login Admin**: Form autentikasi terlindung dengan layout 40:60 dan background Tugu Adipura.
- **Dashboard Admin**: Ringkasan statistik mingguan, grafik jam puncak Over Dimension, dan 5 aktivitas deteksi terkini.
- **Hasil Deteksi**: Tabel log deteksi lengkap dengan filter kombinasi (CCTV, jenis kendaraan, hasil), reset filter, pagination, dan badge deteksi teks.
