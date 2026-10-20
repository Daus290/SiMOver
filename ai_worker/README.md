# ai_worker: Worker Uji Prompt 52 (BUKAN core Rizkiya)

Folder ini dibuat oleh Antigravity pada Prompt 52 (19 Oktober 2026). Isinya **bukan** core deteksi resmi
milik Rizkiya. Core resmi ada di `app/ThirdParty/RizkiyaCore/file-rizkiya.txt`.

Sejak Prompt 53, algoritma di folder ini tidak dikembangkan lagi. Yang disesuaikan hanya lapisan
penghubung: token diambil dari environment (tanpa fallback di source), dan `scripts/ai_worker.ps1`
memverifikasi identitas proses sebelum menghentikannya.

| File | Asal | Fungsi |
|---|---|---|
| `detector.py` | Antigravity | YOLOv8m + ByteTrack (tidak ada di core Rizkiya) |
| `pipeline.py` | Antigravity | Garis hitung dan aturan pencatatan buatan sendiri (tidak ada di core Rizkiya) |
| `classifier.py` | Antigravity | Memanggil `best_yolo_cls.onnx` dengan preprocessing letterbox 320 dan **softmax tambahan** |
| `worker.py`, `config.py` | Antigravity | Loop stream HLS, heartbeat, overlay, dan pengiriman ke API |
| `models/best_yolo_cls.onnx` | Model teman | SHA256 `1E1F66B3...D422309`, identik dengan file yang diberikan |

Masalah yang diketahui (tidak diperbaiki karena di luar batas integrasi):
- Softmax diterapkan pada output yang sudah berupa probabilitas, sehingga confidence terkompres (maksimum 0,405).
- Kelas COCO car dan bus ikut diklasifikasi sebagai salah satu dari 5 kelas truk.
- Kendaraan diam di dekat garis dapat terhitung tanpa melintas.
- Event yang gagal dikirim tidak diulang.

Hasil worker ini masuk dengan `data_origin = antigravity_worker_p52` dan tampil di dashboard hanya bila
sumber "Uji lama (bukan core)" dipilih. Lihat `docs/INTEGRASI_AI_CORE_RIZKIYA.md`.
