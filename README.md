# SiMover — Sistem Monitoring Over Dimension

Sistem Monitoring Over Dimension (SiMover) adalah platform berbasis web untuk pemantauan pelanggaran dimensi kendaraan (Over Dimension) dan pengawasan lalu lintas secara real-time melalui integrasi kamera CCTV streaming.

---

## 1. Tujuan Proyek
- **Pengawasan Real-Time**: Pemantauan langsung jaringan kamera CCTV lalu lintas (simpang strategis Kota Banjarbaru) menggunakan streaming HLS berlatensi rendah.
- **Deteksi Over Dimension**: Menampilkan visualisasi data klasifikasi kendaraan (*Normal*, *Kontainer*, dan *Over Dimension*) untuk mendukung penertiban angkutan barang.
- **Transparansi Publik & Kontrol Internal**: Menyediakan portal publik informatif untuk masyarakat umum serta panel admin terotentikasi untuk analisis mingguan, tabel deteksi, dan filtering data.

> **Catatan Batasan Sistem**: Saat ini deteksi dimensi kendaraan menggunakan data contoh terstruktur (`DummyDataService`) yang siap dihubungkan ke pipeline inferensi Artificial Intelligence (AI) / Computer Vision pada tahapan pengembangan berikutnya.

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

Untuk menjalankan relay RTSP -> HLS kamera Vivotek (jika terhubung jaringan lokal Dishub):
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\vivotek_relay.ps1
```

---

## 4. Struktur Folder Utama
```text
├── app/                  # Logika aplikasi CodeIgniter 4
│   ├── Controllers/      # Home, Admin, Auth, LiveCctv, Peta, Proxy, dsb.
│   ├── Models/           # UserModel, CctvModel, DetectionModel
│   ├── Services/         # DummyDataService, VivotekCctvService, AtcsCctvService
│   └── Views/            # Layouts, pages publik, pages admin, dan partials
├── docs/                 # Dokumentasi perapian, panduan, dan log historis
├── public/               # Web root yang diekspos ke publik
│   ├── assets/
│   │   ├── css/          # style.css, admin.css, pages/ (modular), admin/ (modular)
│   │   ├── js/           # main.js, admin.js, pages/ (peta, cctv, dashboard), admin/
│   │   ├── img/          # Logo, hero visual, peta preview, ikon kendaraan
│   │   └── vendor/       # Bootstrap, Leaflet, Hls.js, Chart.js (lokal)
│   └── index.php         # Entry point aplikasi
├── scripts/              # Skrip pendukung operasional
│   ├── vivotek_relay.ps1 # Runner streaming FFmpeg HLS untuk kamera Dishub
│   └── diagnostics/      # Skrip verifikasi dan monitoring performa stream
├── tests/                # Pengujian sistem
│   └── browser/          # Pengujian visual, interaktivitas, dan computed styles
├── writable/             # Penyimpanan runtime (cache HLS, logs, session, bin FFmpeg)
└── KETERANGAN_BACKUP_SEBELUM_PEMBERSIHAN.txt # Acuan baseline pemulihan awal
```

---

## 5. Halaman Utama Aplikasi
### Portal Pengunjung (Publik)
- **Beranda** (`/`): Ringkasan statistik, hero interaktif, preview peta, dan pengenalan SiMover.
- **Live CCTV** (`/live-cctv`): Grid 18 kamera pengawasan (17 terpasang, 1 unavailable), fitur pencarian, filter simpang, favorit, dan pemutar HLS berlatensi rendah.
- **Dashboard Publik** (`/dashboard`): Grafik tren harian per jam (Line Chart) dan volume akumulasi (Stacked Bar Chart).
- **Peta Interaktif** (`/peta-cctv`): Peta Leaflet interaktif 4 simpang strategis dengan layer switching dinamis (cluster simpang & marker kamera).
- **Tentang Dishub** (`/tentang-dishub`): Profil dan peran Dinas Perhubungan Kota Banjarbaru.
- **Tentang Over Dimension** (`/tentang-over-dimension`): Edukasi regulasi dimensi kendaraan, dampak pelanggaran, dan alur penindakan.

### Panel Admin (Terotentikasi)
- **Login Admin** (`/admin/login`): Form autentikasi terlindung dengan layout 40:60 dan background Tugu Adipura.
- **Dashboard Admin** (`/admin/dashboard`): Ringkasan statistik mingguan, grafik jam puncak Over Dimension, dan 5 aktivitas deteksi terkini.
- **Hasil Deteksi** (`/admin/hasil-deteksi`): Tabel log deteksi lengkap dengan filter kombinasi (CCTV, jenis kendaraan, hasil), reset filter, pagination, dan badge deteksi teks.

---

## 6. Pengujian dan Validasi
Skrip pengujian otomatis berbasis Python & Playwright/Chrome Headless tersedia di folder `tests/browser/`:
- `python tests/browser/verify_computed_styles.py`: Memvalidasi seluruh radius tombol (6px/4px), kartu (8px/12px/14px), dan input (0px).
- `python tests/browser/test_interactivity.py`: Memvalidasi filter simpang, pencarian, toggle favorit, dan modal peta.
- `python tests/browser/test_prompt45.py`: Memvalidasi badge teks (tanpa ikon), filter admin, pagination, dan responsivitas.
