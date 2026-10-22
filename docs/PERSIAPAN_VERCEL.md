# Persiapan Uji Hosting SiMOver di Vercel

Disusun 22 Oktober 2026 (WITA), Prompt 63, ditinjau ulang di Prompt 64. Dasar: branch `rizkiya`, HEAD `b2d6a2c`.
Dokumen ini tidak memuat rahasia. Nilai `.env`, token, password, dan kredensial NVR tidak ditulis di sini.

**Status: siap mencoba deployment, belum pernah di-deploy.** Belum ada commit, push, proyek Vercel,
database cloud, maupun gateway HTTPS kantor. Build di Vercel, akses dari jaringan luar, dan pemutaran video
**belum diuji**.

---

## 1. Kesimpulan singkat

- Aplikasi CodeIgniter 4.7.4 ini layak **dicoba utuh** di Vercel memakai runtime komunitas `vercel-php`.
  Framework dan tampilan tetap; frontend/backend tidak dipisah.
- Halaman tanpa database (Beranda, Live CCTV, Peta, Tentang) bisa diuji pada deployment pertama.
- Dashboard dan API AI butuh **database MySQL staging** yang belum ada. Sampai itu tersedia, keduanya
  membalas error 500 (bukan data palsu).
- **Video ATCS belum terbukti bisa diputar dari domain lain** (lihat bagian 9). Halaman Live CCTV bisa tampil,
  tetapi itu belum berarti video berjalan.
- Konfigurasi percobaan ini hanya memakai **Vercel Functions** (per request, durasi dibatasi). Relay FFmpeg
  Vivotek dan worker AI yang berjalan terus **tidak ditempatkan** di konfigurasi ini; keduanya tetap di komputer
  kantor/layanan lain. Produk Vercel lain tidak diperiksa di tahap ini.
- Paket Hobby: **hanya untuk pemakaian pribadi non-komersial**. Cocok untuk percobaan, bukan operasional instansi.

## 2. Rancangan uji

```
Browser ──HTTPS──> Vercel CDN
                     ├─ file statis: isi folder public/ (CSS, JS, gambar, library frontend)
                     └─ selain itu: function api/index.php (CodeIgniter utuh, PHP 8.4)
                                        └──TLS──> Database MySQL staging (BELUM ADA)
Browser ──HTTPS──> atcs.banjarmasinkota.go.id (HLS ATCS, langsung; saat ini 403/404, lihat bagian 9)

Komputer kantor (gateway BELUM ADA): NVR Vivotek, relay FFmpeg, worker AI
```

## 3. Inventaris dari kode aktual

| Hal | Temuan | Dampak di Vercel |
|---|---|---|
| PHP | `composer.json`: `^8.2`. Lokal: PHP 8.2.12 (XAMPP). Paket terkunci mendukung 8.2–8.5 (`laminas-escaper` 2.18.0: `~8.2.0 \|\| ~8.3.0 \|\| ~8.4.0 \|\| ~8.5.0`) | Tetap `vercel-php@0.8.0` (PHP 8.4.x). Versi ini ada di npm; terbaru 0.9.0 (PHP 8.5), tidak ada alasan kompatibilitas untuk mengganti |
| Framework | `codeigniter4/framework` v4.7.4 (composer.lock), wajib ext `intl`, `mbstring` | Ada di daftar ekstensi vercel-php, begitu juga `mysqli`, `openssl`, `curl` |
| Composer | Runtime menjalankan `composer install --no-dev --no-scripts --ignore-platform-reqs` dari `composer.lock` | Composer `/vendor` tidak di-commit |
| Front controller | `public/index.php` standar | Adapter `api/index.php` |
| Base URL | Default `http://localhost:8080/`, `.env` lokal `:8081` | `app_baseURL`, atau otomatis `https://` + domain yang diminta |
| Database | MySQLi, `utf8mb4`, `encrypt=false` | Opsi TLS lewat `DB_SSL` |
| Session | Tidak dipakai (tidak ada login/admin) | Tidak perlu session bersama |
| writable | Cache ATCS/Vivotek, file overlay AI, log | `/tmp/simover-writable`, sementara dan per-instance |
| Snapshot deteksi | `public/uploads/detections/*.jpg` ditulis worker lokal | Tidak ada penulis di Vercel, sehingga gambar kosong |
| Path aset | Semua `base_url('assets/...')` cocok huruf besar/kecilnya | Aman untuk Linux |
| Library frontend | `public/assets/vendor/` (13 file) belum terlacak Git | Harus ikut commit |

### Endpoint dan ketergantungan

| Route | Butuh DB | Perilaku di Vercel (belum diuji) |
|---|---|---|
| `/`, `/live-cctv`, `/peta-cctv`, `/tentang-*` | Tidak | Statistik di halaman ini memang dari `DummyDataService` sejak sebelumnya (tidak diubah) |
| `/dashboard`, `GET /api/ai/status`, `GET /api/ai/dashboard-data` | Ya | 500 jika DB tidak terjangkau |
| `GET /cctv-snapshot/BJB-*` | Tidak | SVG pengganti (relay tidak ada). SVG memuat IP privat 10.0.x.x kamera, sudah begitu sejak sebelumnya |
| `GET /cctv-stream/BJB-*/...` | Tidak | 503 (relay tidak ada) |
| `POST /api/ai/detections`, `/heartbeat`, `/overlay` | Ya / Tidak | Bertoken, lihat bagian 9 |
| `GET /api/ai/overlay` | Tidak | Membaca file `/tmp`, tidak dibagi antar-instance |
| `/ai-core/rizkiya-host` | Tidak | Tidak dipanggil halaman mana pun di HEAD saat ini |

## 4. Sumber dokumentasi (diakses 22 Oktober 2026)

| Sumber | Yang dipastikan |
|---|---|
| [Git](https://vercel.com/docs/git) | Impor dari **New Project** langsung menuju layar konfigurasi lalu **Deploy**; tidak ada pilihan branch yang terdokumentasi. Production branch default: `main`. Dapat diganti di Settings → Environments → Production → **Branch Tracking**. Ada **Create Deployment** dari Git reference (SHA commit atau nama branch) |
| [Environments](https://vercel.com/docs/deployments/environments) | **Deployment pertama proyek baru selalu Production**, termasuk saat impor dashboard, `vercel deploy` tanpa `--prod`, dan dari branch non-produksi. Deployment pertama memakai env **Production**. Perubahan env berlaku pada deployment baru |
| [Managing Deployments](https://vercel.com/docs/deployments/managing-deployments) | **Redeploy** dijalankan pada deployment tertentu (untuk cache, env, pengaturan build). Untuk mengambil commit terbaru branch: push atau Create Deployment |
| [CLI link](https://vercel.com/docs/cli/link), [project](https://vercel.com/docs/cli/project), [git](https://vercel.com/docs/cli/git), [deploy-from-cli](https://vercel.com/docs/projects/deploy-from-cli) | `vercel project create <nama>` membuat proyek. `vercel link` menulis folder `.vercel`. `vercel git connect` menyambungkan remote Git lokal ke proyek. `vercel curl` dapat mengakses deployment terlindungi |
| [Deployment Protection](https://vercel.com/docs/deployment-protection) | **Standard Protection**: semua dilindungi kecuali domain produksi. **All Deployments**: semua dilindungi, tersedia di semua paket. Dengan Standard Protection, `VERCEL_URL`/`VERCEL_BRANCH_URL` tidak bisa diakses publik, jadi pakai domain yang diminta pengunjung |
| [Runtimes](https://vercel.com/docs/functions/runtimes), [Limits](https://vercel.com/docs/functions/limitations), [Hobby](https://vercel.com/docs/plans/hobby) | PHP = runtime komunitas. `/tmp` maks 500 MB. Body maks 4,5 MB. Hobby: durasi maks 300 dtk, 100 GB transfer, 1 juta invocation, 4 CPU-jam, log 1 jam, non-komersial |
| [vercel-community/php](https://github.com/vercel-community/php) + kode `src/index.ts` | `excludeFiles` **mengganti** daftar default runtime. Glob memakai `@vercel/build-utils` yang memaksa `dot: true`, sehingga dotfile (`.git`, `.env`) ikut kecuali dikecualikan |
| Kode `@vercel/routing-utils` | `redirects` disusun **sebelum** pengecekan file statis; `rewrites` sesudahnya |
| [.vercelignore](https://vercel.com/docs/deployments/vercel-ignore) | Berlaku untuk unggahan CLI; dokumen tidak menyebut perilaku pada build dari Git |

Belum dapat dipastikan tanpa membuka layar Vercel: label tombol terbaru, letak informasi sumber commit di halaman
deployment, dan apakah proteksi bawaan akun Anda Standard atau lainnya. Bagian yang demikian ditandai **(konfirmasi)**.

## 5. File yang berubah

| File | Status | Alasan |
|---|---|---|
| `vercel.json` | baru | Runtime `vercel-php@0.8.0`; output statis `public/`; route lain ke `api/index.php`; `excludeFiles` (lihat bagian 6); region `sin1`; `maxDuration` 30; header `nosniff` + `noindex`; redirect `/index.php`, `/index.php/*`, `/.htaccess` |
| `api/index.php` | baru | Entrypoint Vercel: `SCRIPT_NAME=/index.php` (agar `/api/ai/...` tidak terpotong), writable ke `/tmp`, env Vercel disalin ke `$_ENV`, base URL = `app_baseURL` atau `https://` + domain yang diminta (cadangan `VERCEL_*_URL`) |
| `.vercelignore` | baru | Pembatas unggahan CLI: `.env*`, `/vendor`, `writable`, AI, `tools`, dump, model, log, `.vscode` |
| `.gitignore` | ubah | `/api/` → `/api/*` + `!/api/index.php` (sebelumnya entrypoint ikut diabaikan); tambah `/.vercel/`, `.env.local`, `.env.*.local`. Perubahan lama (`/vendor/`, `node_modules`, `writable/backups`) dipertahankan |
| `app/Config/Logger.php` | tambah constructor | Hanya bila `VERCEL=1`: log ke `error_log()` (Runtime Logs) |
| `app/Config/Database.php` | tambah di constructor | Hanya bila `DB_SSL=true`: TLS terverifikasi |
| `.env.example` | tambah bagian | Nama variabel staging tanpa nilai |
| `docs/PERSIAPAN_VERCEL.md` | baru | Dokumen ini |

Tidak diubah: tampilan, route, controller, model, AI core, kamera, sumber stream, `.env` lokal, database.

## 6. Berkas di GitHub, saat build, di bundle, dan yang publik

| Tahap | Isi |
|---|---|
| **GitHub (setelah commit berikutnya)** | Semua file terlacak + file di bagian 11. Tidak termasuk `.env`, Composer `/vendor`, `writable/` (kecuali file kosong bawaan), model, log |
| **Saat build Vercel** | Hasil clone repo + `vendor/` yang dipasang Composer |
| **Bundle function** | Semua file di folder build **dikurangi** `excludeFiles`. Simulasi lokal: ±909 file, ±5,1 MB (`vendor` 4,6 MB, `app` 0,5 MB). Yang **tidak** masuk: `.git`, `.env*`, `ai_worker`, `tools` (ZIP FFmpeg), `database` (dump), `writable`, `tests`, `scripts`, `docs`, `public/assets`, `public/uploads`, `*.sql/zip/onnx/pt/log`, `*.md`, `*.txt` di root. File wajib (`api/index.php`, `app/`, `app/ThirdParty/RizkiyaCore/file-rizkiya.txt`, `vendor/`) ada |
| **Dapat diakses publik** | Hanya isi `public/` sebagai file statis, ditambah respons CodeIgniter. `public/index.php` dan `public/.htaccess` dialihkan ke `/` oleh `redirects`. Path lain (`/app/...`, `/vendor/...`, `/.env`, `/composer.json`) tidak punya file statis, sehingga diteruskan ke CodeIgniter dan dijawab 404 |

Catatan: `excludeFiles` mengganti daftar default runtime (`node_modules/**`, `vercel.json`, `.vercelignore`, dll.),
jadi pola tersebut ditulis ulang di `vercel.json`. Panjang pola 241 dari batas skema 256 karakter.
Di dalam function, PHP berjalan sebagai `php -S` dengan `api/index.php` sebagai router. Router selalu menangani
permintaan, sehingga file `.php`/`.env` di dalam bundle tidak disajikan langsung. Semua ini **belum teruji di Vercel**.

## 7. Environment variables (isi di Vercel, bukan di repo)

| Nama | Lingkungan Vercel | Isi |
|---|---|---|
| `CI_ENVIRONMENT` | Production **dan** Preview | `production` |
| `app_baseURL` | (kosongkan) | Isi hanya bila memakai domain tetap, mis. `https://<domain>/` |
| `database_default_hostname`, `_port`, `_database`, `_username`, `_password` | Production (+ Preview bila perlu) | Database staging, **bukan** `monitoring_dishub_rizkiya` lokal. Password ditandai Sensitive |
| `DB_SSL` / `DB_SSL_CA` | sama | `true` / path CA (opsional) |
| `AI_CORE_INGEST_TOKEN` | belum | Diisi hanya saat uji AI disepakati; token baru |

`CI_ENVIRONMENT=production` hanyalah **mode aplikasi** (menyembunyikan detail error, mematikan toolbar). Variabel ini
tidak menentukan lingkungan Vercel. Lingkungan Vercel (Production/Preview) ditentukan oleh branch dan aturan
deployment pertama di bagian 8. Jangan isi `vivotek.*` atau `AI_INGEST_TOKEN` di Vercel.

## 8. Alur utama deployment percobaan

**Mengapa tidak langsung "Import" dari New Project:** menurut dokumentasi, impor langsung membuat deployment
pertama (selalu Production) dari production branch, yaitu `main`, dan tidak ada pilihan branch yang terdokumentasi
sebelum Deploy. `main` belum berisi konfigurasi ini. Jadi proyek dibuat dan diatur **dulu tanpa deploy**,
baru deployment pertama dibuat dari `staging-vercel`.

### A. Git lokal: pastikan commit persiapan ada di branch uji

```powershell
git switch rizkiya
git status --short                     # tinjau; pastikan tidak ada .env / writable / vendor root
git add vercel.json api/index.php .vercelignore .gitignore app/Config/Logger.php app/Config/Database.php .env.example docs/PERSIAPAN_VERCEL.md public/assets/vendor
git diff --cached --stat                # periksa sekali lagi
git commit -m "chore: persiapan uji hosting Vercel (vercel-php)"
git switch -c staging-vercel            # branch uji dari commit tersebut
git log -1 --oneline                    # catat SHA ini
git ls-tree -r --name-only HEAD -- vercel.json api/index.php public/assets/vendor/hls/hls.min.js
```
Belum push pada langkah ini.

### B. Hubungkan GitHub ke Vercel tanpa impor

1. Di **New Project**, bagian **Import Git Repository**, klik **GitHub** (konfirmasi label).
2. Saat diminta memasang **Vercel GitHub App**, pilih akun `Daus290` → **Only select repositories** → `SiMOver`.
3. Setelah daftar repository muncul, **jangan klik Import**. Tutup halaman itu.

### C. Buat dan tautkan proyek lewat CLI (tidak men-deploy)

Node 24 dan npm 11 sudah ada di laptop; Vercel CLI belum terpasang.
```powershell
npm i -g vercel
vercel login
cd "D:\Project Coding Daus\Web\SiMOver-Rizkiya"
vercel project create simover-staging
vercel link --yes --project simover-staging     # menulis folder .vercel (sudah di-.gitignore)
vercel git connect                               # pilih/konfirmasi https://github.com/Daus290/SiMOver
vercel project inspect simover-staging           # cek proyek ada; belum ada deployment
```
Jangan jalankan `vercel` atau `vercel deploy` dari folder ini. Perintah itu mengunggah file lokal, bukan commit Git.

### D. Atur proyek di dashboard sebelum deployment pertama

Buka proyek `simover-staging` → **Settings**:
1. **Build and Deployment** (konfirmasi label): Framework Preset **Other**, Root Directory kosong/`./`,
   Build Command tanpa override, Output Directory tanpa override (diambil dari `vercel.json`: `public`).
   Jangan memilih Next.js.
2. **Environments → Production → Branch Tracking**: ganti `main` menjadi `staging-vercel` → **Save**.
3. **Environment Variables**: `CI_ENVIRONMENT` = `production`, centang Production dan Preview.
4. **Deployment Protection**: lihat pengaturan yang aktif, jangan dinonaktifkan. Keputusan Anda di bagian 8F.

### E. Push branch uji, sehingga deployment pertama dari commit yang dimaksud

```powershell
git push -u origin staging-vercel
git ls-remote origin staging-vercel       # SHA harus sama dengan `git rev-parse staging-vercel`
```
Karena Branch Tracking = `staging-vercel`, push ini seharusnya membuat **Production deployment** dari commit tersebut.
Bila tidak ada deployment yang muncul: **Deployments → Create Deployment** → isi branch `staging-vercel`
(atau SHA lengkap dari `git rev-parse staging-vercel`) → **Create Deployment**.

Jangan mengubah Branch Tracking lalu menekan Redeploy pada deployment lama untuk mengambil kode baru.
Redeploy menargetkan deployment yang sudah ada; commit baru diambil lewat push atau Create Deployment.

### F. Cara memastikan sumber yang dibangun

- Halaman detail deployment menampilkan repository, branch, dan commit sumber (konfirmasi letak). Cocokkan dengan
  `git rev-parse --short staging-vercel`.
- **Build Logs**: cari baris clone yang menyebut `Daus290/SiMOver`, branch `staging-vercel`, dan SHA commit
  (konfirmasi format). Pastikan juga ada instalasi Composer `codeigniter4/framework (v4.7.4)` dan runtime `vercel-php@0.8.0`.
- Lingkungan deployment tertulis **Production** (karena deployment pertama dan Branch Tracking).

### G. Production vs Preview, dan batas perlindungan

| | Production (deployment dari `staging-vercel`) | Preview (push branch lain, mis. `rizkiya`/`main`) |
|---|---|---|
| Alamat | Domain produksi proyek `*.vercel.app` (konfirmasi nama) + URL per deployment | URL per branch dan per commit |
| Env | Production | Preview |
| Standard Protection | Domain produksi **publik**, URL per deployment dilindungi | Dilindungi Vercel Authentication |
| All Deployments | Semua dilindungi | Dilindungi |

**Keputusan Anda sebelum langkah E:** uji publik (Standard Protection: domain produksi terbuka, situs ini memang
portal publik dan belum terhubung DB) atau uji privat (All Deployments). Jangan menonaktifkan perlindungan.

Batas pemeriksaan bila alamat dilindungi:
- **HP/jaringan luar**: browser harus login akun Vercel Anda. Untuk uji sebagai pengunjung umum, pakai domain
  produksi dengan Standard Protection.
- **Pengujian baris perintah**: `vercel curl /api/ai/overlay --deployment <url>` dapat menembus perlindungan.
- **Integrasi AI kelak**: POST dari pengirim AI ke alamat terlindungi ditolak Vercel sebelum sampai ke CodeIgniter.
  Jalur resminya "Protection Bypass for Automation" (secret terpisah). Itu keputusan tahap AI. Jangan membagikan
  secret dan jangan melonggarkan token aplikasi.
- Push ke `rizkiya` atau `main` setelah proyek tersambung akan memicu build **Preview** dari kode itu. `main` tidak
  punya `vercel.json`, jadi build-nya tidak bermakna. Abaikan atau hindari push ke sana selama uji.

## 9. Video CCTV, AI, dan komponen yang belum tersedia

### ATCS Banjarmasin: empat tingkat bukti

Uji baca-saja dari laptop ini, 22 Okt 2026 ±11.28 WITA, `curl` ke `https://atcs.banjarmasinkota.go.id`:

| Tingkat | Status |
|---|---|
| 1. Halaman Live CCTV dirender | Lokal: 200 (simulasi adapter). Vercel: **belum diuji** |
| 2. Playlist HLS dapat diambil | **Gagal saat diuji.** `.../stream/<kode>/index.m3u8` (ATCS-01) dan `video1_stream.m3u8` (ATCS-02): **404** tanpa `Origin` maupun dengan `Origin: https://atcs.banjarmasinkota.go.id`; **403** (HTML) dengan `Origin: http://localhost:8081` dan dengan contoh `Origin: https://contoh-simover.vercel.app` (hipotetis). Respons 404 tanpa Origin membawa `Access-Control-Allow-Origin: *`; respons 403 tidak membawa header CORS |
| 3. Segmen HLS dari origin yang benar | **Belum dapat diuji** (playlist tidak tersedia) |
| 4. Video bergerak di browser dari jaringan luar | **Belum diuji.** Berdasarkan hasil 403 untuk origin luar, kemungkinan besar gagal dari domain Vercel kecuali upstream mengizinkan origin tersebut |

Snapshot `.../screenshot/<kode>.webp` membalas 200, tetapi hanya 8 byte (bukan gambar valid).
Komentar di `AtcsCctvService.php` yang menyebut "CORS `*`" **tidak terbukti** untuk origin luar. Kode dan sumber
kamera tidak diubah di tahap ini. Langkah lanjut (keputusan terpisah): konfirmasi ke pengelola ATCS soal izin
origin dan alamat stream yang aktif, atau gateway yang disepakati. Vercel tidak dijadikan proxy video karena
batas body 4,5 MB dan kuota transfer Hobby (1 kamera 1 Mbps ≈ 10,8 GB per 24 jam per penonton).

### Vivotek Banjarbaru

Dari Vercel, `/cctv-snapshot/BJB-*` hanya memberi SVG pengganti dan `/cctv-stream/BJB-*` membalas 503, karena
relay FFmpeg berjalan di komputer kantor. Kelak butuh **gateway HTTPS** dengan sertifikat valid, CORS untuk
domain uji, tanpa kredensial RTSP/NVR ke browser, dan tanpa membuka NVR ke internet. Kode belum punya
pengaturan base URL gateway; itu perubahan terpisah.

### Kontrak API AI (tidak diubah)

| Metode | Path | Auth | Isi |
|---|---|---|---|
| POST | `/api/ai/detections` | `X-AI-Worker-Token: <token>` atau `Authorization: Bearer <token>` | Satu event JSON atau array (maks 50, body maks 65.536 byte); asal data dari token |
| POST | `/api/ai/heartbeat` | sama | Status worker |
| POST | `/api/ai/overlay` | sama | `camera_source`, `frame_time`, `frame_width`, `frame_height`, `boxes` (maks 30) |
| GET | `/api/ai/status`, `/api/ai/dashboard-data`, `/api/ai/overlay` | publik, read-only | |

Tanpa token server membalas 503. Dengan token salah membalas 401 (diuji lokal, tanpa menulis data).
Validasi ada di `app/Config/AiIntegration.php` dan `app/Libraries/AiIngestValidator.php`.

### Pekerjaan berikutnya (belum tersedia)

- Database MySQL staging persisten (TLS, sebaiknya region Singapura).
- Penyimpanan bersama untuk overlay (`/tmp` per instance tidak cukup).
- Object storage untuk snapshot deteksi (bukan `/tmp`).
- Gateway HTTPS kantor untuk Vivotek, dan kejelasan akses stream ATCS.

### Migrasi ke database staging (manual, setelah DB ada)

Jalankan dari **folder clone terpisah** agar `.env` lokal tidak terbaca (CodeIgniter mendahulukan
`database.default.*` dari `.env`):
```powershell
git clone --branch staging-vercel https://github.com/Daus290/SiMOver.git D:\tmp\simover-migrasi
cd D:\tmp\simover-migrasi
composer install --no-dev
# buat .env BARU di folder ini berisi HANYA kredensial DB staging (+ DB_SSL bila perlu)
php spark migrate --all
```
Ini membuat `admin_users`, `ai_detections`, `ai_worker_status`, `ai_monitoring_coverage`. Jangan `migrate:refresh`,
`rollback`, atau seeder; jangan salin data kantor tanpa keputusan terpisah. Hapus folder itu setelah selesai.

## 10. Daftar verifikasi setelah deployment percobaan (semua belum diuji)

- [ ] Sumber deployment: repo `Daus290/SiMOver`, branch `staging-vercel`, SHA sama dengan lokal (bagian 8F).
- [ ] Build log: Composer memasang `codeigniter4/framework v4.7.4`, runtime `vercel-php@0.8.0` tanpa error.
- [ ] `/`, `/live-cctv`, `/peta-cctv`, `/tentang-dishub`, `/tentang-over-dimension` tampil lewat HTTPS; link CSS/JS
      memakai domain yang sedang dibuka, bukan `localhost`.
- [ ] Aset 200: `/assets/css/style.css`, `/assets/vendor/hls/hls.min.js`, `/assets/vendor/leaflet/leaflet.js`,
      `/assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2`, `/assets/img/logo-dishub.png`.
- [ ] `GET /api/ai/overlay` membalas JSON (route API sampai ke CodeIgniter tanpa DB). Jika semua route 404,
      kemungkinan path asli tidak diteruskan rewrite; laporkan.
- [ ] `/index.php` dan `/.htaccess` **dialihkan** ke `/` (bukan isi file).
- [ ] Tidak terbuka (harus 404/redirect): `/.env`, `/app/Config/App.php`, `/vendor/autoload.php`, `/composer.json`,
      `/writable/`, `/database/dumps/monitoring_dishub.sql`, `/ai_worker/`, `/api/index.php`.
- [ ] Runtime Logs menampilkan error dari `error_log`, tanpa password.
- [ ] Live CCTV dari HP memakai data seluler: catat terpisah tingkat 2–4 di bagian 9 (playlist, segmen, video bergerak).
- [ ] Setelah DB staging: `/dashboard` 200 dan isi `/api/ai/status` cocok dengan DB staging; data bertahan setelah redeploy.
- [ ] Integrasi AI bersama teman setelah token staging dan jalur proteksi disepakati.

## 11. File yang perlu masuk commit

```
vercel.json
api/index.php
.vercelignore
.gitignore
app/Config/Logger.php
app/Config/Database.php
.env.example
docs/PERSIAPAN_VERCEL.md
public/assets/vendor/        # 13 file library frontend (Bootstrap, Bootstrap Icons, Chart.js, hls.js, Leaflet)
```

Bedakan dua folder "vendor":
- `public/assets/vendor/`: aset browser yang dipakai halaman, **di-commit**.
- `/vendor/` di root: dependensi Composer, **diabaikan** `.gitignore` (`/vendor/`) dan dipasang ulang saat build.

Pratinjau tanpa mengubah index: `git add --dry-run <daftar di atas>`. Jangan `git add .`.
Jangan commit: `.env`, `.env.local`, `.vercel/`, `/vendor/`, `writable/`, model, log, backup.

**Risiko repo publik yang sudah ada sejak sebelumnya** (tidak diubah di tahap ini):
- `database/dumps/monitoring_dishub.sql` berisi hash password akun admin (lihat `database/README.md`).
- `tools/ffmpeg/ffmpeg-windows.zip` (±78 MB).

Keduanya dikecualikan dari bundle Vercel, tetapi tetap bisa diunduh dari GitHub. Saran tindakan terpisah:
1. Anggap hash itu sudah terbuka, dan ganti password akun tersebut bila pernah dipakai di tempat lain.
2. Hapus kedua file dari HEAD lewat commit terpisah.
3. Putuskan penulisan ulang riwayat Git secara terpisah, karena berdampak pada semua clone.

## 12. Server uji lokal dan cara membatalkan

### Server uji

Prompt 63 meninggalkan dua server simulasi (`php -S 127.0.0.1:8092 ... api/index.php` dan `127.0.0.1:8093 ... index.php`,
dijalankan dari `env.exe` dengan folder sementara sesi Claude). Pada Prompt 64 keduanya diidentifikasi lewat port,
command line, proses induk, dan waktu dibuat, lalu dihentikan. Tersisa hanya server 8081 milik proyek.

Jika kelak perlu memeriksa, **jangan memakai nomor PID lama**. Cek dulu:
```powershell
Get-NetTCPConnection -State Listen -LocalPort 8092,8093,8095,8096 -ErrorAction SilentlyContinue |
  ForEach-Object { Get-CimInstance Win32_Process -Filter "ProcessId=$($_.OwningProcess)" } |
  Select-Object ProcessId, CreationDate, CommandLine
```
Hentikan hanya proses yang command line-nya `php ... -S 127.0.0.1:809x ...`. Jangan menghentikan port 8081, 8080,
atau proses lain.

### Membatalkan hanya perubahan persiapan Vercel (sebelum commit)

1. File baru buatan persiapan ini, dihapus satu per satu (tidak rekursif):
   ```powershell
   Remove-Item vercel.json, .vercelignore, docs\PERSIAPAN_VERCEL.md, api\index.php
   if (-not (Get-ChildItem api -Force)) { Remove-Item api }    # hanya jika folder api kosong
   ```
2. `app/Config/Logger.php`: hapus baris `use CodeIgniter\Log\Handlers\ErrorlogHandler;` dan blok
   `public function __construct()` di akhir class (yang berisi `getenv('VERCEL')`).
3. `app/Config/Database.php`: hapus blok `if (env('DB_SSL') === true) { ... }` beserta dua baris komentar di atasnya.
4. `.env.example`: hapus bagian mulai `# Staging Vercel (Prompt 63)` sampai akhir file.
5. `.gitignore`: kembalikan **hanya** baris persiapan Vercel:
   - `/api/*` → `/api/`, dan hapus `!/api/index.php`
   - hapus blok `# Tautan proyek lokal Vercel CLI ...`, yaitu `/.vercel/`, `.env.local`, `.env.*.local`

   Baris lama `/vendor/`, `node_modules` runtime, dan `writable/backups` tetap.
6. Periksa dengan `git diff -- app/Config/Logger.php app/Config/Database.php .env.example .gitignore`. Hasilnya harus
   tinggal perubahan `.gitignore` lama (`/vendor/`, dua baris di akhir).
7. `public/assets/vendor/` bukan buatan persiapan ini, jadi jangan dihapus. Jika sudah menjalankan `vercel link`,
   folder `.vercel/` (berisi `project.json` dan README) boleh dihapus setelah isinya diperiksa.

Jika sudah di-commit: `git revert <SHA-commit-persiapan>`. Proyek Vercel yang tidak dipakai dihapus dari
Settings → Delete Project (konfirmasi), sebagai keputusan Anda.
