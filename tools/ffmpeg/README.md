# Distribusi Portabel Binary FFmpeg & FFprobe Windows (SiMover)

Direktori ini memuat arsip terkompresi dari executable `ffmpeg.exe` dan `ffprobe.exe` versi Windows 64-bit yang digunakan oleh sistem relay CCTV HLS SiMover Dinas Perhubungan Kota Banjarbaru.

---

## 1. Latar Belakang & Batasan Ukuran Berkas GitHub
- GitHub menerapkan batas maksimal ukuran berkas tunggal sebesar **100 MiB (104.857.600 bytes)** untuk berkas Git biasa.
- Berkas binary asli `ffmpeg.exe` (105.423.872 bytes / ~100,54 MiB) dan `ffprobe.exe` (105.221.120 bytes / ~100,34 MiB) melebihi batas tersebut jika diunggah tanpa kompresi.
- Melalui kompresi lossless, kedua executable ini dipaketkan bersama ke dalam `ffmpeg-windows.zip` dengan ukuran **77.642.594 bytes (~74,05 MiB)**, sehingga dapat dicatat sebagai objek Git biasa yang sah **tanpa memerlukan Git LFS**.

---

## 2. Cara Memulihkan Binary Setelah Clone Repositori
Setelah melakukan clone repositori SiMover ke komputer baru, jalankan skrip pemulihan PowerShell berikut:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\ffmpeg\Restore-FFmpeg.ps1
```

Skrip ini akan secara otomatis:
1. Memeriksa keberadaan berkas `writable/bin/ffmpeg.exe` dan `writable/bin/ffprobe.exe`.
2. Mengekstrak berkas dari `ffmpeg-windows.zip` jika belum tersedia.
3. Memvalidasi integritas hasil ekstraksi terhadap hash SHA-256 yang tercatat di `manifest.json`.
4. Jika berkas sudah ada dan hash cocok, skrip akan melewatinya tanpa menimpa proses relay yang sedang aktif.

---

## 3. Spesifikasi Berkas & Integritas Hash SHA-256

| Berkas | Ukuran Asli | Hash SHA-256 | Lokasi Target |
| :--- | :--- | :--- | :--- |
| `ffmpeg.exe` | 105.423.872 byte | `3256173F3F8BFFD7DF12227C68ADF68025EDB1832273A9530688A7BB1ED8EDEC` | `writable/bin/ffmpeg.exe` |
| `ffprobe.exe` | 105.221.120 byte | `F0D36ECBBDD3BCFAC3EFA078C96C7271C2E68B3810595552AC3B7F17E9A65C52` | `writable/bin/ffprobe.exe` |
| `ffmpeg-windows.zip` | 77.642.594 byte | `63B7BA976BA00A8C4E0AFDF6E56FBF94CFCF9C55B71310537460301DE4DDBD28` | `tools/ffmpeg/` |

---

## 4. Lisensi & Distribusi
- Binary FFmpeg didistribusikan di bawah lisensi GNU General Public License (GPL) / LGPL version 2.1 atau lebih baru.
- Binary ini ditujukan khusus untuk relay video RTSP ke HLS pada lingkungan lokal Windows (jaringan kantor Dishub Banjarbaru).
- Paket ini tidak dimaksudkan untuk dieksekusi di dalam lingkungan hosting cloud seperti Vercel Serverless Functions.
