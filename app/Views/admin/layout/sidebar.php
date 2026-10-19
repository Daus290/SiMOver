<!-- Sidebar Panel Admin Dishub Banjarbaru -->
<aside id="adminSidebar" class="admin-sidebar" aria-label="Navigasi Admin">
  <!-- Brand / Logo Dishub -->
  <a href="<?= base_url('admin/dashboard') ?>" class="admin-sidebar-brand">
    <img src="<?= base_url('assets/img/logo-dishub.png') ?>" alt="Logo Dishub" class="admin-sidebar-logo">
    <div class="admin-brand-text">
      <h1 class="mb-1">SISTEM MONITORING <br>OVER DIMENSION</h1>
      <p>Dishub Banjarbaru</p>
    </div>
  </a>

  <!-- Menu Navigasi Utama -->
  <nav class="admin-sidebar-nav">
    <div class="admin-nav-category">Menu</div>
    <a href="<?= base_url('admin/dashboard') ?>" class="admin-nav-item <?= ($activeMenu === 'dashboard') ? 'active' : '' ?>">
      <i class="bi bi-speedometer2" aria-hidden="true"></i>
      <span>Dashboard</span>
    </a>
    <a href="<?= base_url('admin/hasil-deteksi') ?>" class="admin-nav-item <?= ($activeMenu === 'hasil_deteksi') ? 'active' : '' ?>">
      <i class="bi bi-table" aria-hidden="true"></i>
      <span>Hasil Deteksi</span>
    </a>
  </nav>

  <!-- Profil & Popup Menu Akun di Bawah Sidebar -->
  <div class="admin-sidebar-footer">
    <!-- Popup Dropdown Akun (Membuka ke Atas) -->
    <div id="adminAccountDropdown" class="admin-account-dropdown" role="menu">
      <div class="admin-dropdown-header">
        <div class="admin-dropdown-user"><?= esc($adminUser ?? 'Admin Dishub') ?></div>
        <div class="admin-dropdown-sub"><?= esc($adminRole ?? 'Administrator') ?></div>
      </div>
      
      <!-- Ubah Kata Sandi (Tanpa Ikon Kunci sesuai kesepakatan desain) -->
      <button type="button" class="admin-dropdown-item" data-bs-toggle="modal" data-bs-target="#modalUbahKataSandi" role="menuitem">
        <span>Ubah Kata Sandi</span>
      </button>

      <!-- Logout Form via POST -->
      <form action="<?= base_url('admin/logout') ?>" method="post" class="m-0 p-0">
        <?= csrf_field() ?>
        <button type="submit" class="admin-dropdown-item text-danger" role="menuitem">
          <i class="bi bi-box-arrow-right text-danger" aria-hidden="true"></i>
          <span>Keluar (Logout)</span>
        </button>
      </form>
    </div>

    <!-- Tombol Pemicu Menu Akun -->
    <button type="button" id="adminAccountBtn" class="admin-user-btn" aria-haspopup="true" aria-expanded="false" title="Menu Akun Admin">
      <div class="admin-user-meta">
        <div class="admin-user-avatar">
          <i class="bi bi-person-fill" aria-hidden="true"></i>
        </div>
        <div class="admin-user-info">
          <div class="admin-user-name"><?= esc($adminUser ?? 'Admin Dishub') ?></div>
          <div class="admin-user-role"><?= esc($adminRole ?? 'Administrator') ?></div>
        </div>
      </div>
      <i class="bi bi-chevron-up small text-muted" aria-hidden="true"></i>
    </button>
  </div>
</aside>
