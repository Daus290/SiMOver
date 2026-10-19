<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Login Admin - Sistem Monitoring Over Dimension') ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-dishub.png') ?>">

  <!-- Google Fonts: Source Sans 3 (Konsisten dengan Halaman Pengunjung) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">

  <!-- Local Vendor CSS: Bootstrap 5.3 & Bootstrap Icons -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">

  <!-- Admin Scoped Stylesheet & Dedicated Login Styles -->
  <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/admin/login.css') ?>">
</head>
<body class="admin-login-body">

<div class="admin-login-layout">
  
  <!-- Sisi Kiri: Panel Visual & Snapshot CCTV Statis (40:60 Desktop) -->
  <div class="admin-login-visual" style="background-image: linear-gradient(rgba(15, 23, 42, 0.50), rgba(15, 23, 42, 0.50)), url('<?= base_url('assets/img/admin-login/bg-tugu-adipura.jpg') ?>');">
    <!-- Header Identitas Sistem -->
    <div class="admin-login-brand">
      <div class="admin-login-logo-box">
        <img src="<?= base_url('assets/img/logo-dishub.png') ?>" alt="Logo Dishub Kota Banjarbaru" class="admin-login-logo-img">
      </div>
      <div class="admin-login-brand-text">
        <span class="admin-login-brand-title">DINAS PERHUBUNGAN BANJARBARU</span>
      </div>
    </div>

    <!-- Pratinjau Sistem & 2 Thumbnail CCTV Statis (Tengah Panel) -->
    <div class="admin-login-preview">
      <h1 class="admin-login-preview-title">Sistem Monitoring Over Dimension <br> Halaman Admin</h1>
      <div class="admin-login-cctv-grid">
        <!-- Thumbnail 1: Brimob Fase 3 (Statis) -->
        <div class="admin-login-cctv-card">
          <img src="<?= base_url('assets/img/admin-login/brimob-fase-3.jpg') ?>" alt="Kamera Pemantau Brimob Fase 3" class="admin-login-cctv-img">
          <div class="admin-login-cctv-overlay"></div>
          <span class="admin-login-cctv-label">Brimob Fase 3</span>
        </div>
        <!-- Thumbnail 2: Brimob PTZ (Statis) -->
        <div class="admin-login-cctv-card">
          <img src="<?= base_url('assets/img/admin-login/brimob-ptz.jpg') ?>" alt="Kamera Pemantau Brimob PTZ" class="admin-login-cctv-img">
          <div class="admin-login-cctv-overlay"></div>
          <span class="admin-login-cctv-label">Brimob PTZ</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Sisi Kanan: Panel Formulir Masuk (40:60 Desktop) -->
  <div class="admin-login-form-pane">
    <div class="admin-login-form-container">
      <div class="admin-login-form-header">
        <h2 class="admin-login-title">Masuk</h2>
      </div>

      <!-- Alert Kesalahan Kredensial Umum -->
      <?php if (!empty($authError)): ?>
        <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
          <i class="bi bi-exclamation-circle-fill text-danger fs-5"></i>
          <span><?= esc($authError) ?></span>
        </div>
      <?php endif; ?>

      <!-- Alert Berhasil Logout atau Lainnya -->
      <?php if (!empty($success)): ?>
        <div class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
          <span><?= esc($success) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= base_url('admin/login') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <!-- Field Username -->
        <div class="admin-login-field mb-3">
          <label for="username" class="admin-login-label">Username</label>
          <div class="admin-input-group">
            <span class="admin-input-icon">
              <img src="<?= base_url('assets/img/admin-login/user.svg') ?>" alt="" width="18" height="18" aria-hidden="true">
            </span>
            <input 
              type="text" 
              class="form-control admin-login-input <?= !empty($errors['username']) ? 'is-invalid' : '' ?>" 
              id="username" 
              name="username" 
              placeholder="Masukkan username" 
              value="<?= esc($oldInput['username'] ?? '') ?>" 
              autocomplete="username"
              required
            >
          </div>
          <?php if (!empty($errors['username'])): ?>
            <div class="invalid-feedback d-block small mt-1">
              <?= esc($errors['username']) ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Field Kata Sandi / Password -->
        <div class="admin-login-field mb-4">
          <label for="login_password" class="admin-login-label">Password</label>
          <div class="admin-input-group">
            <span class="admin-input-icon">
              <img src="<?= base_url('assets/img/admin-login/lock.svg') ?>" alt="" width="18" height="18" aria-hidden="true">
            </span>
            <input 
              type="password" 
              class="form-control admin-login-input <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" 
              id="login_password" 
              name="password" 
              placeholder="Masukkan password" 
              autocomplete="current-password"
              required
            >
            <button type="button" class="btn-eye-toggle" data-target="login_password" aria-label="Tampilkan atau sembunyikan password">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback d-block small mt-1">
              <?= esc($errors['password']) ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Tombol Masuk Sekarang -->
        <button type="submit" class="btn-admin-primary btn-submit-login w-100">
          <span>Masuk Sekarang</span>
        </button>
      </form>

      <!-- Tautan Kembali ke Portal Pengunjung -->
      <div class="admin-login-footer mt-4 text-center">
        <a href="<?= base_url() ?>" class="admin-login-back-link">
          <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Pengunjung
        </a>
      </div>

    </div>
  </div>

</div>

<!-- Local Vendor JS -->
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/admin.js') ?>"></script>

</body>
</html>

