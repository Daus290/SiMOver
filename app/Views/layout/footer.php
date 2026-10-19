<!-- Footer Dark Navy (#0F172A & #1E293B) Sesuai Tahap 4 -->
<footer class="footer-dishub mt-auto">
  <div class="container">
    <div class="row g-4">
      <!-- Kolom 1: Profil Instansi -->
      <div class="col-lg-5 col-md-6">
        <div class="mb-3">
          <img src="<?= base_url('assets/img/logo-dishub.png') ?>" alt="Logo Dishub Kota Banjarbaru" height="52" class="d-inline-block rounded-circle bg-white p-1">
        </div>
        <p class="footer-contact-clean mb-3" style="max-width: 440px;">
          Layanan pemantauan dimensi kendaraan dan CCTV lalu lintas di wilayah Kota Banjarbaru untuk mendukung keselamatan transportasi dan pemeliharaan jalan.
        </p>
        <div class="text-white-50 small">
          Data terakhir diperbarui: <?= esc($meta['demo_date']) ?> pukul <?= esc($meta['demo_time']) ?>
        </div>
      </div>

      <!-- Kolom 2: Navigasi & Informasi -->
      <div class="col-lg-3 col-md-6 col-6">
        <h6 class="footer-heading">Halaman</h6>
        <ul class="footer-links-clean">
          <li><a href="<?= base_url() ?>">Beranda</a></li>
          <li><a href="<?= base_url('live-cctv') ?>">Live CCTV</a></li>
          <li><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
          <li><a href="<?= base_url('peta-cctv') ?>">Peta Interaktif</a></li>
          <li><a href="<?= base_url('tentang-dishub') ?>">Tentang Dishub</a></li>
          <li><a href="<?= base_url('tentang-over-dimension') ?>">Tentang Over Dimension</a></li>
        </ul>
      </div>

      <!-- Kolom 3: Kontak (Dengan Ikon Pemindai) -->
      <div class="col-lg-4 col-md-6">
        <h6 class="footer-heading">Kontak</h6>
        <div class="footer-contact-clean">
          <p class="mb-2 d-flex align-items-start">
            <i class="bi bi-geo-alt me-2 mt-1" aria-hidden="true"></i>
            <span><?= esc($meta['contact']['address']) ?></span>
          </p>
          <p class="mb-2 d-flex align-items-center">
            <i class="bi bi-telephone me-2" aria-hidden="true"></i>
            <span><?= esc($meta['contact']['phone']) ?></span>
          </p>
          <p class="mb-2 d-flex align-items-center">
            <i class="bi bi-envelope me-2" aria-hidden="true"></i>
            <span><?= esc($meta['contact']['email']) ?></span>
          </p>
          <p class="mb-2 d-flex align-items-center">
            <i class="bi bi-instagram me-2" aria-hidden="true"></i>
            <a href="<?= esc($meta['contact']['instagram']) ?>" target="_blank" rel="noopener noreferrer">@dishub.bjb</a>
          </p>
          <p class="mb-2 d-flex align-items-center">
            <i class="bi bi-facebook me-2" aria-hidden="true"></i>
            <a href="<?= esc($meta['contact']['facebook']) ?>" target="_blank" rel="noopener noreferrer">Dishub Banjarbaru</a>
          </p>
          <p class="mb-2 d-flex align-items-center">
            <i class="bi bi-globe me-2" aria-hidden="true"></i>
            <a href="<?= esc($meta['contact']['website']) ?>" target="_blank" rel="noopener noreferrer">dishub.banjarbarukota.go.id</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Sub-Footer Copyright Bar (#1E293B) -->
  <div class="footer-bottom-bar">
    <div class="container text-center">
      <div class="footer-bottom-text">
        <?= esc($meta['copyright']) ?>
      </div>
    </div>
  </div>
</footer>
