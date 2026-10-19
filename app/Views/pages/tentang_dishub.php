<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<!-- 1. Hero Tentang Dishub dengan Depth Overlay (Gedung Kantor Asli Dishub Banjarbaru) -->
<section class="hero-dishub" style="background-image: url('<?= base_url('assets/img/gedung-dishub.jpg') ?>'); background-position: center 30%;">
  <div class="hero-overlay-depth"></div>
  <div class="container hero-content text-center py-3">
    <div class="max-w-700 mx-auto">
      <h1 class="hero-title">
        Dinas Perhubungan Kota Banjarbaru
      </h1>
      <p class="hero-desc mx-auto mb-0">
        Penyelenggara urusan pemerintahan di bidang perhubungan untuk mewujudkan pelayanan transportasi yang tertib, aman, dan berkeselamatan.
      </p>
    </div>
  </div>
</section>

<!-- 2. Profil Dishub: Foto Gedung Kiri, Teks Kanan (Zigzag Baris 1) -->
<section class="py-5 bg-white border-bottom">
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <!-- Kolom Foto Gedung (Desktop Kiri 50%, Mobile Bawah) -->
      <div class="col-lg-6 col-12 order-2 order-lg-1">
        <img src="<?= base_url('assets/img/tentang-dishub/kantor-dishub.jpg') ?>" 
             alt="Gedung Kantor Dinas Perhubungan Kota Banjarbaru dengan deretan armada bus operasional" 
             class="tentang-img"
             loading="lazy">
      </div>

      <!-- Kolom Teks Profil (Desktop Kanan 50%, Mobile Atas) -->
      <div class="col-lg-6 col-12 order-1 order-lg-2">
        <div class="tentang-profile-text">
          <h2 class="tentang-heading mb-3">
            Tentang Dinas Perhubungan Kota Banjarbaru
          </h2>
          <p class="tentang-desc mb-3">
            Dinas Perhubungan Kota Banjarbaru merupakan unsur pelaksana urusan pemerintahan daerah yang bertugas menyelenggarakan pelayanan transportasi jalan, pengaturan lalu lintas, serta pembinaan keselamatan berlalu lintas di wilayah Kota Banjarbaru.
          </p>
          <p class="tentang-desc mb-0">
            Dalam mendukung mobilitas perkotaan yang tertib dan lancar, Dinas Perhubungan mengoptimalkan pengawasan fasilitas jalan dan pemantauan lalu lintas secara berkelanjutan guna mewujudkan sistem transportasi yang aman, nyaman, dan berkeselamatan bagi seluruh pengguna jalan.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Tiga Peran Dinas Perhubungan (3 Kolom Sejajar) -->
<section class="py-5">
  <div class="container">
    <div class="mb-4">
      <h2 class="tentang-heading mb-0">Peran Dinas Perhubungan</h2>
    </div>

    <div class="row g-4">
      <!-- Peran 1: Pengaturan Lalu Lintas -->
      <div class="col-md-4">
        <div class="tentang-peran-card h-100">
          <div class="info-icon-box">
            <i class="bi bi-signpost-2" aria-hidden="true"></i>
          </div>
          <h3 class="h6 fw-bold mb-2 text-dark">Pengaturan Lalu Lintas</h3>
          <p class="text-muted small mb-0" style="line-height: 1.6;">
            Manajemen lalu lintas di persimpangan dan koridor jalan untuk kelancaran arus kendaraan.
          </p>
        </div>
      </div>

      <!-- Peran 2: Pengawasan & Keselamatan -->
      <div class="col-md-4">
        <div class="tentang-peran-card h-100">
          <div class="info-icon-box">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
          </div>
          <h3 class="h6 fw-bold mb-2 text-dark">Pengawasan &amp; Keselamatan</h3>
          <p class="text-muted small mb-0" style="line-height: 1.6;">
            Pemantauan kelaikan kendaraan dan kepatuhan dimensi guna menekan risiko kecelakaan.
          </p>
        </div>
      </div>

      <!-- Peran 3: Penegakan Regulasi -->
      <div class="col-md-4">
        <div class="tentang-peran-card h-100">
          <div class="info-icon-box">
            <i class="bi bi-clipboard-check" aria-hidden="true"></i>
          </div>
          <h3 class="h6 fw-bold mb-2 text-dark">Penegakan Regulasi</h3>
          <p class="text-muted small mb-0" style="line-height: 1.6;">
            Penerapan standar teknis kendaraan sesuai ketentuan peraturan perundang-undangan.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Bagian Pemantauan CCTV: Teks Kiri, Snapshot Kanan (Zigzag Baris 2) -->
<section class="py-5 bg-white border-top">
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <!-- Kolom Teks CCTV (Desktop Kiri 50%, Mobile Atas) -->
      <div class="col-lg-6 col-12">
        <div class="tentang-cctv-text">
          <h2 class="tentang-heading mb-3">
            Pemantauan Kendaraan Berbasis CCTV
          </h2>
          <p class="tentang-desc mb-3">
            Pengawasan lalu lintas di Kota Banjarbaru didukung oleh jaringan kamera pemantau (CCTV) yang terpasang pada persimpangan dan koridor jalan strategis. Sistem ini memungkinkan pemantauan visual kondisi lalu lintas kota secara terpadu.
          </p>
          <p class="tentang-desc mb-0">
            Melalui portal SiMover, rekaman kamera dimanfaatkan untuk membantu pemantauan visual arus kendaraan, khususnya angkutan barang bermuatan besar. Pengamatan visual ini mempermudah identifikasi awal terhadap pergerakan kendaraan yang berpotensi over dimension demi menjaga keawetan infrastruktur jalan dan keselamatan pengguna jalan.
          </p>
        </div>
      </div>

      <!-- Kolom Snapshot CCTV Brimob Fase 3 (Desktop Kanan 50%, Mobile Bawah) -->
      <div class="col-lg-6 col-12">
        <img src="<?= base_url('assets/img/tentang-dishub/brimob-fase-3.png') ?>" 
             alt="Cuplikan rekaman kamera CCTV pemantau Brimob Fase 3 Kota Banjarbaru" 
             class="tentang-img"
             loading="lazy">
      </div>
    </div>
  </div>
</section>

<?= $this->endSection() ?>
