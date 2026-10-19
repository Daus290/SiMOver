# LAPORAN KERJA PEMBERSIHAN FILE DAN REFAKTOR PROYEK (PROMPT 46)

**Sistem Monitoring Over Dimension (SiMover) - Dinas Perhubungan Kota Banjarbaru**  
**Tanggal**: 16 Oktober 2026  
**Status**: Selesai Seluruh Tahap (Tahap A s/d Tahap F Terverifikasi Penuh)

---

## 1. SATU BACKUP AWAL (SEBELUM PEMBERSIHAN)
Sesuai instruksi pengguna dan ketentuan batas keamanan:
- **Nama Folder**: `backup_prompt46_sebelum_pembersihan_20261016-100927`
- **Keterangan File**: `KETERANGAN_BACKUP_SEBELUM_PEMBERSIHAN.txt` (tersedia di root dan di dalam folder backup)
- **Waktu Pembuatan**: 16 Oktober 2026, 10:09:38 WITA
- **Total File**: 1.366 file
- **Total Ukuran**: 107.800.637 bytes (102,81 MB)
- **Status Integritas**: 100% Terverifikasi (0 mismatch, seluruh SHA-256 cocok)
- **Lokasi Manifest**: `backup_prompt46_sebelum_pembersihan_20261016-100927/manifest.json`
- **Pengecualian**: `vendor/`, `writable/`, `.env`, backup lama (`backup_prompt29/30/31`), serta folder backup tujuan itu sendiri.
- **Kebijakan Cadangan**: Hanya dibuat SATU KALI sebelum perubahan pertama. Tidak ada backup kedua, ZIP tambahan, atau file `.bak` yang tersebar.

---

## 2. INVENTARIS AWAL DAN REALISASI PEMBERSIHAN

| Kategori | Sebelum Pembersihan (Baseline) | Sesudah Pembersihan & Refaktor | Keterangan & Tindakan |
| :--- | :--- | :--- | :--- |
| **Folder `scratch/`** | 1.178 file (~84,3 MB) | 2 file aktif (.gitkeep & README.md) | Screenshot & artefak lama dibuang aman. Alat diagnostik dipindah ke `scripts/diagnostics/` & `tests/browser/`. |
| **File Root Usang** | `Live` (288 bytes) | Dihapus | File residu teks keluaran lama telah dibuang. |
| **CSS Pengunjung** (`style.css`) | 1.815 baris (monolitik) | 758 baris (modular) | Dipecah ke `pages/beranda.css`, `cctv.css`, `peta.css`, `informasi.css`. Urutan spesifisitas Prompt 44 terjaga 100%. |
| **CSS Admin** (`admin.css`) | 1.291 baris (monolitik) | 848 baris (modular) | Dipecah ke `admin/login.css` (terisolasi). Aturan badge, tabel, dan kartu tetap konsisten. |
| **JavaScript Inline View** | ~939 baris tersebar di 4 view | 0 baris JS inline | Diekstrak ke `peta.js`, `cctv-player.js`, `pages/dashboard.js`, `admin/dashboard.js`. Menggunakan data JSON aman (`application/json`). |
| **Partial Duplikasi Badge** | 2 blok duplikasi di 2 view admin | 1 partial bersama | Dibuat `app/Views/admin/partials/detection_badge.php` (teks-only, 0 ikon). |
| **Duplikat Gambar Kembar** | `gedung-dishub-asli.jpg`, `peta-preview-banjarbaru.jpg` | Dihapus dari `public/assets/img/` | File kembar identik dihapus setelah dipastikan tersimpan di backup awal dan tidak direferensikan kode. |
| **Aset Hero Beranda** | PNG 2,46 MB (`hero-cctv-truk-bak-tanpa-gradien.png`) | WebP 428 KB (`hero-cctv-truk-bak-tanpa-gradien.webp`) | Dimensi identik 1678x937, komposisi sama, hemat payload ~2 MB. |
| **Dokumentasi Root** | Bawaan CodeIgniter | Panduan Proyek SiMover Lengkap | `README.md` diperbarui memuat arsitektur, panduan, dan disclaimer data contoh. |

---

## 3. PEMBAGIAN CSS & JAVASCRIPT MODULAR

### A. Struktur File CSS Baru
1. `public/assets/css/style.css`: Token bersama, navbar, footer, typography, dan aturan border-radius global Prompt 44.
2. `public/assets/css/pages/beranda.css`: Aturan hero section, grid pengenalan, dan preview peta beranda.
3. `public/assets/css/pages/cctv.css`: Grid 18 CCTV, filter bar, pencarian, status badge, dan HLS player view.
4. `public/assets/css/pages/peta.css`: Leaflet wrapper, marker simpang, marker kamera, legenda, dan modal simpang.
5. `public/assets/css/pages/informasi.css`: Halaman Tentang Dishub dan Tentang Over Dimension.
6. `public/assets/css/admin.css`: Panel admin, sidebar, topbar, kartu insight, tabel deteksi, dan pagination.
7. `public/assets/css/admin/login.css`: Desain login terisolasi (rasio 40:60, background Adipura, input/tombol kotak).

### B. Struktur File JavaScript Baru
1. `public/assets/js/pages/peta.js`: Peta interaktif Leaflet, dynamic layer zoom threshold 18, modal simpang, dan deep linking.
2. `public/assets/js/pages/cctv-player.js`: Pemutar video HLS, watchdog 25s, retry mechanism, dan deteksi otomatis Vivotek/ATCS via data-attribute.
3. `public/assets/js/pages/dashboard.js`: Grafik tren harian (Line Chart) dan volume akumulasi (Stacked Bar Chart) berbasis Chart.js.
4. `public/assets/js/admin/dashboard.js`: Grafik jam puncak Over Dimension dan tren 7 hari admin berbasis Chart.js.

---

## 4. HASIL VERIFIKASI DAN PENGUJIAN NON-REGRESI

### A. Integritas Computed Styles & Border Radius (LULUS 100%)
- **Tombol Pengunjung**: `6px` (`.btn-dishub-primary`, `.btn-dishub-outline`)
- **Tombol Kecil / Filter**: `4px`
- **Kartu Statistik Pengunjung**: `14px` (`.stat-card-modern`)
- **Kartu Konten / CCTV**: `8px` (`.cctv-card-simple`, `.card-plain`)
- **Kartu Statistik Admin**: `12px`
- **Panel & Wrapper Tabel Admin**: `8px`
- **Form Select / Input**: `0px` (tetap kotak sempurna)
- **Tombol & Input Login**: `0px` (tetap kotak sesuai arahan Prompt 44/45)
- **Badge Deteksi Admin**: Teks-only tanpa ikon (`hasIcons: 0`) pada Dashboard dan Hasil Deteksi.

### B. Fungsionalitas & Interaktivitas (LULUS 100%)
- Filter CCTV per Simpang: Bekerja akurat (menampilkan 4 kamera di Simpang Brimob).
- Pencarian CCTV: Bekerja akurat (mencari "PTZ" menghasilkan 1 kamera).
- Reset Filter: Mengembalikan 18 kamera.
- Favorit CCTV: Toggle bintang bekerja, naik ke atas, tersimpan di localStorage `dishub_fav_cctv`.
- Peta Interaktif: Pan/zoom lancar, marker cluster dan individual muncul pada threshold zoom 18, modal detail simpang terbuka dengan radius 8px.
- Pagination Admin: Bekerja dengan radius tombol 4px.
- Live CCTV Relay: 15 proses FFmpeg di background tetap berjalan lancar tanpa gangguan atau restart.

---

## 5. PEKERJAAN YANG SENGAJA DITUNDA
Sesuai batasan keamanan sistem:
1. **Cache Runtime HLS (`writable/cache/`)**: Tidak dihapus paksa agar tidak memutus segmen video tayangan aktif pengguna.
2. **Kredensial & File `.env`**: Tetap utuh tanpa modifikasi.
3. **Penyatuan Provider AI**: `DummyDataService` dipertahankan sebagai kontrak stabil hingga integrasi AI pipeline siap dikerjakan pada sesi terpisah.
4. **Git Repository Setup**: Folder belum diinisialisasi Git, tidak dilakukan git reset/clean atau commit yang tidak diminta.

---

## 6. PANDUAN PEMULIHAN (ROLLBACK)
Jika sewaktu-waktu pengguna memerlukan pengembalian kondisi ke sebelum Prompt 46:
1. Buka folder `backup_prompt46_sebelum_pembersihan_20261016-100927/`.
2. Salin kembali folder `app/`, `public/`, `scripts/`, `tests/`, `scratch/` serta file root ke root proyek `d:\Project Coding Daus\Web\Sistem Monitoring Over Dimension Dishub\`.
3. Folder backup awal ini akan tetap disimpan dan tidak dihapus secara otomatis.
