<?php
/**
 * Host terisolasi core Rizkiya.
 * @var string $core        Isi file-rizkiya.txt (sudah diverifikasi SHA256)
 * @var string $coreSha256
 * @var string $streamUrl   Playlist HLS kamera uji melalui proxy lokal (same-origin)
 * @var string $cameraCode
 */
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Host Core Rizkiya</title>
  <style>
    html, body { margin: 0; padding: 8px; background: #ffffff; color: #0f172a; }
  </style>
</head>
<body>
<!-- ===== CORE RIZKIYA MULAI: disisipkan apa adanya, SHA256 <?= esc($coreSha256) ?> ===== -->
<?= $core ?>

<!-- ===== CORE RIZKIYA SELESAI ===== -->

<!-- Adapter integrasi SiMover (di luar core): sumber video + pengamat keluaran DOM -->
<script id="rizkiyaHostConfig" type="application/json"><?= json_encode([
    'streamUrl'  => $streamUrl,
    'cameraCode' => $cameraCode,
    'coreSha256' => $coreSha256,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= base_url('assets/vendor/hls/hls.min.js') ?>"></script>
<script src="<?= base_url('assets/js/pages/rizkiya-host-adapter.js') ?>"></script>
</body>
</html>
