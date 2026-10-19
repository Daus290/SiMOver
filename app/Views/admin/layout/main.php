<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Admin - Sistem Monitoring Over Dimension') ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-dishub.png') ?>">

  <!-- Local Vendor CSS: Bootstrap 5.3 & Bootstrap Icons -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">

  <!-- Admin Scoped Stylesheet -->
  <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
  <!-- 1. Sidebar Navigasi Admin -->
  <?= $this->include('admin/layout/sidebar') ?>

  <!-- Backdrop untuk Layar Sentuh / Ponsel / Tablet -->
  <div id="adminSidebarBackdrop" class="admin-sidebar-backdrop"></div>

  <!-- 2. Area Konten Utama -->
  <div class="admin-main-wrap">
    
    <!-- Topbar Header -->
    <header class="admin-header-bar">
      <div class="d-flex align-items-center gap-3">
        <!-- Tombol Hamburger (Khusus Layar < 992px) -->
        <button type="button" id="adminSidebarToggle" class="btn btn-sm btn-light d-lg-none" aria-label="Buka Menu Navigasi">
          <i class="bi bi-list fs-5"></i>
        </button>
      </div>

      <div class="d-flex align-items-center gap-3">
        <div class="text-muted small d-none d-md-block">
          <i class="bi bi-clock me-1 text-primary"></i>
          Data diperbarui: <?= esc($meta['demo_date']) ?> pukul <?= esc($meta['demo_time']) ?>
        </div>
        <a href="<?= base_url() ?>" class="btn-admin-outline btn-sm" target="_blank" title="Buka Portal Pengunjung di Tab Baru">
          <i class="bi bi-box-arrow-up-right me-1 small"></i>
          <span class="d-none d-sm-inline">Portal Pengunjung</span>
        </a>
      </div>
    </header>

    <!-- Content Section -->
    <main class="admin-content-area">
      <!-- Flash Alert Sukses / Error Sesi -->
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 small" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i>
          <?= esc(session()->getFlashdata('success')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 small" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?= esc(session()->getFlashdata('error')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
      <?php endif; ?>

      <?= $this->renderSection('content') ?>
    </main>

    <!-- Footer Kecil Hak Cipta -->
    <footer class="admin-footer-bar">
      <?= esc($meta['copyright'] ?? '© 2026 Dinas Perhubungan Kota Banjarbaru.') ?>
    </footer>

  </div>
</div>

<!-- Modal Ubah Kata Sandi (Re-usable di Semua Halaman Admin) -->
<?= $this->include('admin/layout/account_modal') ?>

<!-- Local Vendor JS: Bootstrap 5.3 -->
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>

<!-- Script Interaktif Admin -->
<script src="<?= base_url('assets/js/admin.js') ?>"></script>

<!-- Scripts Tambahan dari View Spesifik (Chart.js dsb) -->
<?= $this->renderSection('scripts') ?>

</body>
</html>
