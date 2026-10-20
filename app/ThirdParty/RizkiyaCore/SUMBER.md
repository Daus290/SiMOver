# Core Deteksi Rizkiya (salinan identik, jangan diedit)

File `file-rizkiya.txt` di folder ini adalah salinan **byte-per-byte** dari core deteksi milik Rizkiya.
Core adalah tanggung jawab Rizkiya. Integrasi SiMover tidak boleh mengubah isi file ini.

| Item | Nilai |
|---|---|
| Asal file | `C:\Users\Daus\Downloads\file-rizkiya.txt` (diberikan pengguna) |
| Disalin | 20 Oktober 2026 (WITA), Prompt 53 |
| Ukuran | 5.198 byte (CRLF) |
| SHA256 | `3DE8C05467B3790EF535A7F54C7D3173911E78212C8ED5C234D23406115F6348` |
| MODEL_URL di core | `https://huggingface.co/spaces/monkingya/deteksi_truk_overdimension/resolve/main/best_yolo_cls.onnx` |
| JSON_URL di core | `https://huggingface.co/spaces/monkingya/deteksi_truk_overdimension/resolve/main/class_mapping.json` |

Hash yang sama dicatat di `app/Config/AiIntegration.php` (`$rizkiyaCoreSha256`). Host
`/ai-core/rizkiya-host` menolak menjalankan salinan ini bila hash-nya berbeda.

`.gitattributes` menandai file ini `-text` agar Git tidak mengubah line ending (CRLF) sehingga hash tetap sama.

Jika Rizkiya memberikan versi baru: ganti file ini dengan file baru apa adanya, perbarui hash di
tabel ini dan di `AiIntegration.php`, lalu catat versinya di `docs/INTEGRASI_AI_CORE_RIZKIYA.md`.
