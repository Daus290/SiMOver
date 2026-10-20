<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<!-- 1. Hero Section dengan Depth Gradient Overlay -->
<section class="hero-dishub hero-beranda" style="background-image: url('<?= base_url('assets/img/hero-cctv-truk-bak-tanpa-gradien.webp') ?>');">
  <div class="hero-overlay-depth"></div>
  <div class="container hero-content">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h1 class="hero-title">
          SiMOver (Sistem Monitoring Over Dimension)
        </h1>
        <p class="hero-desc">
          Layanan pemantauan dimensi kendaraan berbasis integrasi kamera CCTV dan Artificial Intelligence di wilayah Kota Banjarbaru.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= base_url('live-cctv') ?>" class="btn-dishub-primary">
            <i class="bi bi-camera-video" aria-hidden="true"></i>Lihat Live CCTV
          </a>
          <a href="<?= base_url('dashboard') ?>" class="btn-dishub-outline">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>Lihat Dashboard
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 2. Kartu Statistik Modern (Ikon, Accent Stripe, Hover Halus) -->
<section class="py-4">
  <div class="container">
    <p class="text-muted small mb-2">
      <span class="badge bg-secondary-subtle text-dark border me-1">Data Contoh</span>
      Angka ringkasan di bawah adalah data contoh tampilan, bukan hasil AI. Hasil model pada kamera uji tersedia di halaman Dashboard.
    </p>
    <div class="row g-3">
      <!-- CCTV Aktif -->
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card-modern accent-cctv">
          <div class="stat-icon-box icon-cctv">
            <i class="bi bi-camera-video" aria-hidden="true"></i>
          </div>
          <div class="stat-num-modern"><?= esc($stats['cctv_status_str']) ?></div>
          <div class="stat-label-modern">CCTV Terpantau</div>
          <div class="stat-sub-modern"><?= esc($stats['cctv_active']) ?> online, 1 offline</div>
        </div>
      </div>

      <!-- Total Deteksi -->
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card-modern accent-total">
          <div class="stat-icon-box icon-total">
            <i class="bi bi-truck" aria-hidden="true"></i>
          </div>
          <div class="stat-num-modern"><?= esc($stats['total_detection']) ?></div>
          <div class="stat-label-modern">Total Deteksi Hari Ini</div>
          <div class="stat-sub-modern">Periode 1 hari</div>
        </div>
      </div>

      <!-- Over Dimension -->
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card-modern accent-overdim">
          <div class="stat-icon-box icon-overdim">
            <i class="bi bi-rulers" aria-hidden="true"></i>
          </div>
          <div class="stat-num-modern" style="color: var(--dishub-orange);"><?= esc($stats['over_dimension']) ?></div>
          <div class="stat-label-modern">Over Dimension</div>
          <div class="stat-sub-modern"><?= esc($stats['percentages']['over_dimension']) ?>% dari total kendaraan</div>
        </div>
      </div>

      <!-- Kontainer -->
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card-modern accent-kontainer">
          <div class="stat-icon-box icon-kontainer">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="2" y="5" width="20" height="14" rx="1.5" />
              <line x1="6" y1="5" x2="6" y2="19" />
              <line x1="10" y1="5" x2="10" y2="19" />
              <line x1="14" y1="5" x2="14" y2="19" />
              <line x1="18" y1="5" x2="18" y2="19" />
            </svg>
          </div>
          <div class="stat-num-modern" style="color: var(--dishub-data-kontainer);"><?= esc($stats['kontainer']) ?></div>
          <div class="stat-label-modern">Truk Kontainer</div>
          <div class="stat-sub-modern"><?= esc($stats['percentages']['kontainer']) ?>% dari total kendaraan</div>
        </div>
      </div>
    </div>

    <!-- Tombol Navigasi Tentang Over Dimension -->
    <div class="mt-4 text-start">
      <a href="<?= base_url('tentang-over-dimension') ?>" class="btn-dishub-light">
        Tentang Over Dimension <i class="bi bi-arrow-right" aria-hidden="true"></i>
      </a>
    </div>
  </div>
</section>

<!-- 3. Pantauan CCTV Terkini (6 Kamera) -->
<section class="py-4">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
      <div>
        <h2 class="h4 mb-0">Pantauan CCTV Terkini</h2>
      </div>
      <div>
        <a href="<?= base_url('live-cctv') ?>" class="btn-dishub-light">
          <i class="bi bi-camera-video" aria-hidden="true"></i>Lihat Semua CCTV
        </a>
      </div>
    </div>

    <!-- Grid 6 CCTV -->
    <div class="row g-4">
      <?php foreach ($cctv_list as $cctv): ?>
        <?= view('partials/cctv_card', [
          'cctv'                  => $cctv,
          'col_class'             => 'col-lg-4 col-md-6',
          'hide_redundant_badges' => true,
          'show_favorite'         => false,
        ]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 4. Bagian Lokasi CCTV & Peta -->
<section class="py-5 bg-white border-top border-bottom my-4">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6 col-12 lokasi-cctv-text-col">
        <h2 class="h4 mb-2">Lokasi CCTV</h2>
        <p class="text-muted mb-4" style="line-height: 1.6;">
          Kamera pemantau ditempatkan pada persimpangan dan koridor jalan utama Kota Banjarbaru.
        </p>
        <a href="<?= base_url('peta-cctv') ?>" class="btn-dishub-primary">
          <i class="bi bi-map" aria-hidden="true"></i>Buka Peta Interaktif
        </a>
      </div>
      <div class="col-lg-6 col-12 lokasi-cctv-img-col">
        <div class="border rounded-0 overflow-hidden shadow-sm">
          <img src="<?= base_url('assets/img/peta-preview-banjarbaru.png') ?>" 
               alt="Peta Lokasi CCTV Banjarbaru" 
               class="w-100 d-block">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. Informasi Pemantauan (3 Kartu dengan Ikon Oranye Lembut) -->
<section class="py-4 pb-5">
  <div class="container">
    <div class="mb-4">
      <h2 class="h4 mb-1">Informasi Pemantauan</h2>
      <p class="text-muted small mb-0">Fitur umum sistem pengawasan kendaraan di jalan raya.</p>
    </div>

    <div class="row g-3">
      <!-- Poin 1 -->
      <div class="col-md-4">
        <div class="info-card-dishub">
          <div class="info-icon-box">
            <i class="bi bi-camera-video" aria-hidden="true"></i>
          </div>
          <h5 class="h6 fw-bold mb-2">Pemantauan Kendaraan</h5>
          <p class="text-muted small mb-0" style="line-height: 1.6;">
            Perekaman arus lalu lintas di titik persimpangan dan ruas jalan utama Kota Banjarbaru.
          </p>
        </div>
      </div>

      <!-- Poin 2 -->
      <div class="col-md-4">
        <div class="info-card-dishub">
          <div class="info-icon-box">
            <i class="bi bi-rulers" aria-hidden="true"></i>
          </div>
          <h5 class="h6 fw-bold mb-2">Pengukuran Dimensi</h5>
          <p class="text-muted small mb-0" style="line-height: 1.6;">
            Analisis estimasi ukuran panjang, lebar, dan tinggi fisik kendaraan yang melintas.
          </p>
        </div>
      </div>

      <!-- Poin 3 -->
      <div class="col-md-4">
        <div class="info-card-dishub">
          <div class="info-icon-box">
            <i class="bi bi-bar-chart" aria-hidden="true"></i>
          </div>
          <h5 class="h6 fw-bold mb-2">Ringkasan Deteksi</h5>
          <p class="text-muted small mb-0" style="line-height: 1.6;">
            Penyajian data hasil pantauan secara berkala untuk mendukung ketertiban angkutan jalan.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
