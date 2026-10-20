<?php
/**
 * Ditampilkan bila salinan core Rizkiya di proyek tidak identik dengan hash yang diterima.
 * @var string $expected
 * @var string $found
 */
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Core Rizkiya Tidak Dijalankan</title>
  <style>body { font-family: system-ui, sans-serif; margin: 16px; color: #0f172a; } code { word-break: break-all; }</style>
</head>
<body>
  <h3>Core Rizkiya tidak dijalankan</h3>
  <p>Salinan core di proyek tidak identik dengan file yang diterima, sehingga host menolak menjalankannya.</p>
  <p>SHA256 diharapkan: <code><?= esc($expected) ?></code><br>SHA256 ditemukan: <code><?= esc($found) ?></code></p>
  <p>Jika Rizkiya memberikan versi baru, perbarui salinan dan hash sesuai <code>app/ThirdParty/RizkiyaCore/SUMBER.md</code>.</p>
  <script>
    if (window.parent !== window) {
      window.parent.postMessage({ source: 'simover-rizkiya-host', type: 'host', state: 'blocked' }, window.location.origin);
    }
  </script>
</body>
</html>
