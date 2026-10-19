<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sistem Monitoring Over Dimension Dinas Perhubungan Kota Banjarbaru - Pemantauan Dimensi Kendaraan dan Live CCTV Lalu Lintas">
  <title><?= esc($title ?? 'Sistem Monitoring Over Dimension Dishub Kota Banjarbaru') ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">

  <!-- Google Fonts: Source Sans 3 (Elegan, Tenang, Formal & Mudah Dibaca) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">

  <!-- Local Vendor CSS: Bootstrap 5.3 & Bootstrap Icons -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">

  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">

  <?= $this->renderSection('styles') ?>
</head>
<body class="public-site d-flex flex-column min-vh-100">

  <!-- Shared Navbar -->
  <?= $this->include('layout/navbar') ?>

  <!-- Main Page Content -->
  <main class="flex-grow-1">
    <?= $this->renderSection('content') ?>
  </main>

  <!-- Shared Footer -->
  <?= $this->include('layout/footer') ?>

  <!-- Local Vendor JS: Bootstrap 5.3 -->
  <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>

  <!-- Client-side Interactive Script -->
  <script src="<?= base_url('assets/js/main.js') ?>"></script>

  <?= $this->renderSection('scripts') ?>
</body>
</html>
