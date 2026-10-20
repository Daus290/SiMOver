# Integrasi Web untuk Core Deteksi Rizkiya (Prompt 53)

Tanggal: 20 Oktober 2026 (WITA). Status: **integrasi web siap. Core belum menghasilkan keluaran valid dengan model yang diberikan.**

## 1. Batas tanggung jawab

| Lapisan | Pemilik | Lokasi | Status |
|---|---|---|---|
| Core deteksi (kode, model, URL, preprocessing, threshold, interval) | **Rizkiya** | `app/ThirdParty/RizkiyaCore/file-rizkiya.txt` (salinan identik) | Tidak diubah |
| Worker Python Prompt 52 (detector, tracker, garis hitung, softmax) | Antigravity | `ai_worker/` | Dibekukan, ditandai bukan core |
| Host & adapter browser | Integrasi web | `RizkiyaCoreHost`, `ai_core/host.php`, `rizkiya-host-adapter.js` | Baru |
| API ingestion & penyimpanan | Integrasi web | `Api/AiApiController`, `AiIngestValidator`, `AiDetectionModel` | Diperbaiki |
| Dashboard & Live CCTV | Integrasi web | `AiDashboardService`, `dashboard.php/js`, `live_cctv.php`, `cctv-player.js` | Diperbaiki |

Bukti core tidak berubah: SHA256 file asli `C:\Users\Daus\Downloads\file-rizkiya.txt` dan salinan di proyek
sama-sama `3DE8C05467B3790EF535A7F54C7D3173911E78212C8ED5C234D23406115F6348`. Blok core di dalam respons
`/ai-core/rizkiya-host` juga diverifikasi identik byte-per-byte. Host menolak berjalan (HTTP 409) bila hash berbeda.

## 2. Keluaran core yang tersedia

Core adalah contoh frontend: ia membaca video atau canvas, menjalankan classifier, lalu menulis teks ke DOM.

| Field | Tersedia dari core? | Keterangan |
|---|---|---|
| Teks status model (`#status-model`) | Ya | Diamati adapter |
| Teks hasil (`#hasil-deteksi`): nama kelas + persen | Ya, bila inferensi berhasil | Format `Terdeteksi: X (Y%)` / `Tidak ada truk / Kurang yakin (X: Y%)` |
| Indeks kelas mentah | **Tidak** | Hanya teks label dari `classMap` |
| Vektor probabilitas | **Tidak** | |
| Bounding box | **Tidak** | Model adalah classifier, core tidak memakai detector |
| ID kendaraan / track | **Tidak** | |
| Waktu frame | **Tidak** | Hanya waktu diterima adapter |
| Berjalan saat browser ditutup | **Tidak** | Core berjalan di tab browser |

Konsekuensi untuk web:
- Kotak Deteksi ditampilkan "Belum Tersedia" (toggle nonaktif).
- Hasil core di browser hanya ditampilkan di panel, **tidak disimpan**. Browser publik bukan pengirim tepercaya, dan token tidak boleh ditaruh di browser.
- Dashboard sumber "Core Rizkiya" kosong sampai ada pengirim tepercaya.

## 3. Temuan pada core (untuk Rizkiya, tidak diperbaiki sendiri)

1. **Ukuran input tidak cocok dengan model.** Core memakai `INPUT_SIZE = 224`, sedangkan model menerima `images [1,3,320,320]`.
   - Bukti ONNX Runtime Python: `Got invalid dimensions for input: images ... Got: 224 Expected: 320`.
   - Bukti Chrome headless, 20 Okt 2026 08:29 dan 08:39 WITA: model berhasil dimuat sekitar 40 detik setelah dijalankan, video 1280x720 berjalan, lalu setiap 500 ms muncul `Uncaught (in promise) Error: failed to call OrtRun() ... Got: 224 Expected: 320`. Terekam 230 exception dalam sekitar 2 menit, dan teks hasil tetap "Menunggu deteksi...".
2. **Parser mapping tidak cocok dengan JSON.** JSON remote (SHA256 `119A5DCF...1590389`) berbentuk bertingkat (`idx_to_class`, `idx_to_indonesian_label`, ...). Simulasi loop parser core menghasilkan `{"1":"use_letterbox","320":"img_size","NaN":"test_metrics"}`. Akibatnya label yang akan tampil adalah `Kelas-0`, `use_letterbox`, `Kelas-2`, `Kelas-3`, `Kelas-4`.
3. **Nama kelas peringatan.** Core membandingkan dengan `over_dimension`, sedangkan kelas di model adalah `truck_over_dimension`, sehingga warna merah tidak akan pernah muncul.
4. **Preprocessing.** Core melakukan stretch seluruh frame ke kotak (tanpa letterbox), sedangkan metadata menyebut `use_letterbox: true`. Ultralytics classify bawaan memakai resize + center crop. Perlu dikonfirmasi transform yang dipakai saat training.
5. **Seluruh frame diklasifikasi.** Satu frame berisi banyak kendaraan atau jalan kosong tetap dipaksa masuk ke salah satu dari 5 kelas truk.
6. `ort.min.js` dimuat dari CDN **tanpa versi tetap**.
7. Model (±63 MB) diunduh dari Hugging Face setiap kali halaman core dibuka.

### Informasi atau pembaruan minimum yang perlu diminta ke Rizkiya

1. Versi core yang memakai ukuran input sesuai model (320) atau model yang sesuai dengan core (224), serta transform training yang benar (letterbox / center crop / stretch).
2. Cara membaca `class_mapping.json` bertingkat, atau mapping datar yang dipakai core.
3. Keluaran terstruktur selain teks DOM, misalnya event/callback berisi `class_index`, `class_name`, `confidence`, dan waktu frame.
4. Bila kotak deteksi dan hitungan per kendaraan diinginkan: detector, tracking, aturan hitung, serta koordinat + waktu frame yang dapat dipadankan dengan video HLS.
5. Bila harus berjalan tanpa browser: runtime resmi (misalnya service) yang mengirim ke API ingestion di bawah memakai token `AI_CORE_INGEST_TOKEN`.

## 4. Kontrak API untuk pengirim tepercaya

`POST /api/ai/detections` (header `X-AI-Worker-Token: <AI_CORE_INGEST_TOKEN>`). Body berupa satu objek, atau array berisi maksimal 50 objek, dengan ukuran maksimal 64 KB.

```json
{
  "event_id": "rz-20261020-000123",
  "camera_source": "jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota",
  "class_index": 2,
  "class_name": "truck_over_dimension",
  "confidence": 0.8731,
  "frame_time": "2026-10-20T08:40:12.400+08:00",
  "result_unit": "frame_sample",
  "source_version": "rizkiya-core-1.1",
  "track_id": null,
  "bbox": null
}
```

- Asal data (`data_origin`) ditentukan server dari token, bukan dari payload.
- `indonesian_label` dan `is_over_dimension` diturunkan server dari `app/Config/AiIntegration.php`. Nilai di payload diabaikan.
- Confidence disimpan apa adanya (`confidence_raw`), tanpa softmax atau pembulatan ulang.
- Waktu: `frame_time` lebih diutamakan, lalu `observed_time`; bila keduanya tidak ada, dipakai waktu diterima server. Dasar waktunya tercatat di `time_basis`.
- Respons dilaporkan per event (`inserted`, `duplicate`, `rejected`, `error`).
  - `event_id` yang sama untuk asal yang sama hanya tersimpan satu kali, sehingga aman dikirim ulang.
  - HTTP 503 berarti server gagal menyimpan; kirim ulang nanti dengan `event_id` yang sama.
  - HTTP 422 berarti semua event ditolak validasi.
- `event_id` buatan pengirim hanya menjamin deduplikasi kiriman, **bukan** keunikan kendaraan fisik.
- Bila token tidak dikonfigurasi, endpoint mengembalikan 503 (gagal tertutup). Token yang salah menghasilkan 401.

`POST /api/ai/heartbeat` menerima `{camera_source, status: active|connecting|reconnecting|stopped|error, fps, last_inference_time_ms, last_frame_time}`.
Heartbeat aktif juga mencatat cakupan pemantauan per menit (`ai_monitoring_coverage`). Data ini membuat grafik dapat membedakan "tidak dipantau" dari "nol kendaraan".

`GET /api/ai/overlay` hanya mengembalikan kotak bila asal data ada di `$overlayOrigins`, metadata berumur ≤ 2 detik, dan memuat `frame_time` serta ukuran frame. Selain itu responsnya `available:false` dengan `boxes: []`. Player menggambar kotak hanya bila `frame_time` selisihnya ≤ 500 ms dari `hls.playingDate`.

## 5. Cara menjalankan dan halaman yang dapat diperiksa

1. Nyalakan web dan database seperti biasa (`start-system.ps1`). Relay kantor tidak diperlukan untuk kamera ATCS.
2. `http://localhost:8080/live-cctv`: kamera uji berada di bagian paling bawah. Badge-nya menampilkan status pengirim core dari heartbeat aktual.
3. `http://localhost:8080/live-cctv?cctv=ATCS-TEST`: video, toggle Kotak Deteksi ("Belum Tersedia"), dan panel **Core Rizkiya - Uji di Browser** dengan tombol **Jalankan Core**.
4. `http://localhost:8080/dashboard?period=1&tab=statistik` dan `...&tab=hasil-deteksi`: dropdown **Sumber** berisi "Core Rizkiya" (default) dan "Uji lama (bukan core)".
5. Pengujian otomatis: `vendor\bin\phpunit -c phpunit.ai.xml` (memakai database terpisah `monitoring_dishub_test`).

## 6. Pengujian yang sudah dijalankan (20 Okt 2026)

- PHPUnit `phpunit.ai.xml`:
  - 22 test validator, termasuk kelas/indeks salah, kamera asing, confidence di luar 0-1, waktu di luar rentang, bbox salah, dan label/OD dari payload yang diabaikan.
  - 8 test integrasi API: token salah 401; event ganda tersimpan sekali; status per event pada batch; 422/413; fixture tidak muncul di sumber publik; kartu, grafik, dan tabel konsisten (25 = 25 = 25); koreksi halaman; heartbeat menjadi kedaluwarsa; overlay sumber tidak resmi tidak pernah dikirim.
  - Dijalankan dengan konfigurasi bawaan, test database otomatis di-skip (pengaman).
- Chrome headless (CDP):
  - Dashboard: 3 kombinasi periode, tab, dan sumber; 5 dataset grafik; polling berhasil; perpindahan tab tanpa error.
  - Lebar 390 px tanpa scroll horizontal.
  - Player ATCS-TEST: video 1280x720 berjalan, toggle nonaktif, kanvas tersembunyi.
  - Core dijalankan lalu dihentikan; iframe dikosongkan.
- Smoke test HTTP: Beranda, Live CCTV, Peta, Tentang, dan redirect `/admin/hasil-deteksi` (302).

Belum dibuktikan: keluaran valid core (terhalang temuan 3.1), akurasi model pada kamera ini, sinkronisasi kotak terhadap video (belum ada sumber kotak resmi), dan operasi 24 jam.

## 7. Insiden data 20 Okt 2026 sekitar 08:15 WITA

Saat menyiapkan database test, override environment tidak berlaku. Akibatnya `migrate:rollback` berjalan di database utama dan men-drop tabel AI.

- **Hilang:** 214 baris data uji worker Antigravity (19 Okt 16:03–17:06) dan 1 baris status worker. `admin_users` utuh.
- **Pemulihan:** binary log nonaktif dan tidak ada backup tabel AI, jadi data asli tidak dapat dipulihkan.
- **Rekonstruksi parsial:** 208 kejadian (tanpa confidence/bbox) dari `ai_worker/worker.log` tersimpan di `writable/ai_legacy_recovery/` dan tidak diimpor otomatis.
- **Pencegahan:** test kini memakai `phpunit.ai.xml` dengan database `monitoring_dishub_test`, plus pengaman nama database sebelum migrasi.

## 8. Keterbatasan

- Footer halaman selain Dashboard masih menampilkan tanggal data contoh.
- Mode development menyisipkan debug toolbar CI4 ke halaman host (di luar blok core).
- Dashboard "Uji lama (bukan core)" kosong karena insiden pada bagian 7.
