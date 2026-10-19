<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="py-5 bg-white border-bottom">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <h1 class="h3 fw-bold mb-3">
          Mengenal Pelanggaran Over Dimension
        </h1>
        <p class="text-muted mb-3" style="line-height: 1.6;">
          <strong>Over Dimension</strong> adalah kondisi kendaraan angkutan barang yang dimodifikasi sehingga ukuran fisik panjang, lebar, atau tingginya melebihi batas standar teknis yang diizinkan.
        </p>
        <div>
          <a href="<?= base_url('dashboard') ?>" class="btn-dishub-primary mt-4">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>Analitik Over Dimension
          </a>
        </div>
      </div>

      <div class="col-lg-6">
        <img src="<?= base_url('assets/img/ilustrasi-overdimension.svg') ?>" 
             alt="Ilustrasi Skematik Perbandingan Dimensi Standar dan Over Dimension" 
             class="w-100 d-block">
      </div>
    </div>
  </div>
</div>

<div class="py-5">
  <div class="container">

    <!-- 1. Perbedaan Over Dimension dan Over Load (Pola Zigzag 50:50 - Terbuka Ala Lokasi CCTV) -->
    <div class="odol-comparison-section">
      <div class="mb-4">
        <h2 class="h2 fw-bold mb-1">Perbedaan Over Dimension dan Over Load</h2>
      </div>

      <!-- Baris 1: Over Dimension (Desktop: Gambar Kiri 50%, Teks Kanan 50% | Ponsel: Teks Atas, Gambar Bawah) -->
      <div class="odol-row-item">
        <div class="row align-items-center g-4 g-lg-5">
          <!-- Kolom Foto Over Dimension (Desktop 50% Kiri, Mobile Bawah) -->
          <div class="col-lg-6 col-12 order-2 order-lg-1 odol-col-50">
            <figure class="odol-photo-unit mb-0">
              <img src="<?= base_url('assets/img/overdimension-contoh-1.jpg') ?>" 
                   alt="Ilustrasi pickup dengan muatan karung tinggi melebihi bak kendaraan" 
                   class="w-100 d-block" 
                   loading="lazy">
              <figcaption class="odol-photo-caption text-center">
                Pickup bermuatan karung melebihi batas tinggi dan lebar bak kendaraan.
              </figcaption>
            </figure>
          </div>

          <!-- Kolom Teks Over Dimension (Desktop 50% Kanan, Mobile Atas - Tanpa Ikon) -->
          <div class="col-lg-6 col-12 order-1 order-lg-2 odol-col-50">
            <div>
              <h3 class="h3 fw-bold text-dark mb-3">Over Dimension</h3>
              <p class="text-secondary mb-5" style="line-height: 1.65; font-size: 1.05rem;">
                Kondisi ketika ukuran fisik kendaraan angkutan barang dimodifikasi sehingga dimensi panjang, lebar, atau tinggi bak dan sasis melampaui batas rancang bangun serta spesifikasi teknis resmi dalam Buku Uji KIR.
              </p>

              <div class="mb-3">
                <div class="fw-bold text-dark small text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.8rem;">Metode Pengawasan</div>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                  Dalam sistem ini, citra CCTV mendeteksi truk atau pickup yang di luar batas panjang dan tinggi normal akan digunakan sebagai data untuk dianalisis secara otomatis melalui Artificial Intelligence.
                </p>
              </div>
              <div>
                <div class="fw-bold text-dark small text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.8rem;">Risiko di Jalan Raya</div>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                  Perubahan dimensi memperlebar titik buta pengemudi, mengurangi stabilitas manuver di tikungan, serta berisiko tersangkut atau membentur jembatan penyeberangan, kabel listrik, pohon, atau portal jalan.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Baris 2: Over Load (Desktop: Teks Kiri 50%, Gambar Kanan 50% | Ponsel: Teks Atas, Gambar Bawah) -->
      <div class="odol-row-item">
        <div class="row align-items-center g-4 g-lg-5">
          <!-- Kolom Teks Over Load (Desktop 50% Kiri, Mobile Atas - Tanpa Ikon) -->
          <div class="col-lg-6 col-12 order-1 order-lg-1 odol-col-50">
            <div>
              <h3 class="h3 fw-bold text-dark mb-3">Over Load</h3>
              <p class="text-secondary mb-5" style="line-height: 1.65; font-size: 1.05rem;">
                Kondisi ketika berat atau tonase muatan yang diangkut melampaui Jumlah Berat yang Diizinkan (JBI) atau daya dukung sumbu jalan, meskipun bentuk dan ukuran fisik kendaraan tidak diubah.
              </p>

              <div class="mb-3">
                <div class="fw-bold text-dark small text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.8rem;">Metode Pengawasan</div>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                  Pengawasan berat muatan berfokus pada massa dan tonase, yang diukur melalui penimbangan fisik di Jembatan Timbang (UPPKB) atau sensor timbangan khusus.
                </p>
              </div>
              <div>
                <div class="fw-bold text-dark small text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.8rem;">Risiko di Jalan Raya</div>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                  Kelebihan berat muatan dapat menyebabkan roda kemudi depan kehilangan kontak jalan (kehilangan traksi kemudi), memicu rem blong, as roda patah, serta mempercepat kerusakan jalan.
                </p>
              </div>
            </div>
          </div>

          <!-- Kolom Foto Over Load (Desktop 50% Kanan, Mobile Bawah) -->
          <div class="col-lg-6 col-12 order-2 order-lg-2 odol-col-50">
            <figure class="odol-photo-unit mb-0">
              <img src="<?= base_url('assets/img/overload-contoh-1.jpg') ?>" 
                   alt="Ilustrasi truk merah dengan roda depan terangkat akibat kelebihan muatan" 
                   class="w-100 d-block" 
                   loading="lazy">
              <figcaption class="odol-photo-caption text-center">
                Truk dengan roda depan terangkat akibat beban muatan berlebih di bagian belakang.
              </figcaption>
            </figure>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Dampak Pelanggaran Over Dimension (3 Kartu Bergambar Rata Tengah - Terbuka Tanpa Pembungkus Besar) -->
    <div class="mb-5">
      <div class="text-center mb-4">
        <h2 class="h4 fw-bold mb-1">Dampak Pelanggaran ODOL <br>(Over Dimension dan Over Load)</h2>
      </div>

      <div class="row g-4">
        <!-- 1. Kerusakan Jalan -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="dampak-card h-100 bg-white text-center">
            <div class="dampak-img-wrap">
              <img src="<?= base_url('assets/img/dampak-kerusakan-jalan.webp') ?>" 
                   alt="Ilustrasi kondisi jalan rusak dengan kendaraan di tepi jalan." 
                   class="dampak-card-img" 
                   loading="lazy">
            </div>
            <div class="p-3 p-lg-4">
              <h3 class="h6 fw-bold mb-2 text-dark">Kerusakan Jalan</h3>
              <p class="text-muted small mb-0" style="line-height: 1.6;">
                Mempercepat keausan badan jalan serta berisiko membentur portal batas tinggi jembatan dan fasilitas publik.
              </p>
            </div>
          </div>
        </div>

        <!-- 2. Risiko Kecelakaan -->
        <div class="col-lg-4 col-md-6 col-12">
          <div class="dampak-card h-100 bg-white text-center">
            <div class="dampak-img-wrap">
              <img src="<?= base_url('assets/img/dampak-risiko-kecelakaan.jpeg') ?>" 
                   alt="Ilustrasi truk terguling di jalan." 
                   class="dampak-card-img" 
                   loading="lazy">
            </div>
            <div class="p-3 p-lg-4">
              <h3 class="h6 fw-bold mb-2 text-dark">Risiko Kecelakaan</h3>
              <p class="text-muted small mb-0" style="line-height: 1.6;">
                Pusat gravitasi yang tinggi membuat kendaraan lebih mudah terguling saat berbelok dan memperluas titik buta pengemudi.
              </p>
            </div>
          </div>
        </div>

        <!-- 3. Kemacetan Lalu Lintas -->
        <div class="col-lg-4 col-md-12 col-12">
          <div class="dampak-card h-100 bg-white text-center">
            <div class="dampak-img-wrap">
              <img src="<?= base_url('assets/img/dampak-kemacetan.webp') ?>" 
                   alt="Ilustrasi kepadatan kendaraan dan truk di jalan." 
                   class="dampak-card-img" 
                   loading="lazy">
            </div>
            <div class="p-3 p-lg-4">
              <h3 class="h6 fw-bold mb-2 text-dark">Kemacetan Lalu Lintas</h3>
              <p class="text-muted small mb-0" style="line-height: 1.6;">
                Kecepatan manuver yang lambat dan ruang putar yang besar sering kali menghambat kelancaran kendaraan lain.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Alur Kerja Sistem (Rencana Integrasi 6 Tahap) -->
    <div class="mb-5">
      <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4 gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h2 class="h4 fw-bold mb-0">Alur Kerja Sistem</h2>
        </div>
      </div>

      <ol class="odol-workflow-track list-unstyled mb-0 p-0">
        <!-- Tahap 1 -->
        <li class="odol-workflow-item">
          <div class="odol-workflow-card">
            <span class="odol-workflow-num">1</span>
            <span class="odol-workflow-title">CCTV<br>Memantau</span>
          </div>
          <div class="odol-workflow-arrow" aria-hidden="true">
            <i class="bi bi-arrow-right d-none d-lg-block"></i>
            <i class="bi bi-arrow-down d-lg-none"></i>
          </div>
        </li>

        <!-- Tahap 2 -->
        <li class="odol-workflow-item">
          <div class="odol-workflow-card">
            <span class="odol-workflow-num">2</span>
            <span class="odol-workflow-title">Frame<br>Diambil</span>
          </div>
          <div class="odol-workflow-arrow" aria-hidden="true">
            <i class="bi bi-arrow-right d-none d-lg-block"></i>
            <i class="bi bi-arrow-down d-lg-none"></i>
          </div>
        </li>

        <!-- Tahap 3 -->
        <li class="odol-workflow-item">
          <div class="odol-workflow-card">
            <span class="odol-workflow-num">3</span>
            <span class="odol-workflow-title">Kendaraan<br>Dideteksi</span>
          </div>
          <div class="odol-workflow-arrow" aria-hidden="true">
            <i class="bi bi-arrow-right d-none d-lg-block"></i>
            <i class="bi bi-arrow-down d-lg-none"></i>
          </div>
        </li>

        <!-- Tahap 4 -->
        <li class="odol-workflow-item">
          <div class="odol-workflow-card">
            <span class="odol-workflow-num">4</span>
            <span class="odol-workflow-title">Dimensi<br>Dianalisis</span>
          </div>
          <div class="odol-workflow-arrow" aria-hidden="true">
            <i class="bi bi-arrow-right d-none d-lg-block"></i>
            <i class="bi bi-arrow-down d-lg-none"></i>
          </div>
        </li>

        <!-- Tahap 5 -->
        <li class="odol-workflow-item">
          <div class="odol-workflow-card">
            <span class="odol-workflow-num">5</span>
            <span class="odol-workflow-title">Hasil<br>Disimpan</span>
          </div>
          <div class="odol-workflow-arrow" aria-hidden="true">
            <i class="bi bi-arrow-right d-none d-lg-block"></i>
            <i class="bi bi-arrow-down d-lg-none"></i>
          </div>
        </li>

        <!-- Tahap 6 -->
        <li class="odol-workflow-item odol-workflow-item-last">
          <div class="odol-workflow-card">
            <span class="odol-workflow-num">6</span>
            <span class="odol-workflow-title">Ringkasan Hasil Ditampilkan<br>di Dashboard</span>
          </div>
        </li>
      </ol>
    </div>

    <!-- 4. Jenis Kendaraan dalam Pemantauan (Truk, Pickup, Truk Kontainer) -->
    <div class="mb-4">
      <div class="mb-4">
        <h2 class="h4 fw-bold mb-1">Jenis Kendaraan dalam Pemantauan</h2>
      </div>
      <div class="row g-4">
        <!-- Truk -->
        <div class="col-md-4">
          <div class="odol-vehicle-card p-4 border text-center h-100 bg-white shadow-sm">
            <div class="mb-3">
              <img src="<?= base_url('assets/img/kendaraan-truk.svg') ?>" alt="Truk" style="height: 95px; max-width: 100%;">
            </div>
            <h3 class="h6 fw-bold mb-2 text-dark">Truk</h3>
            <p class="text-muted small mb-0" style="line-height: 1.6;">Angkutan barang dengan bak terbuka atau tertutup.</p>
          </div>
        </div>

        <!-- Pickup -->
        <div class="col-md-4">
          <div class="odol-vehicle-card p-4 border text-center h-100 bg-white shadow-sm">
            <div class="mb-3">
              <img src="<?= base_url('assets/img/kendaraan-pickup.svg') ?>" alt="Pickup" style="height: 95px; max-width: 100%;">
            </div>
            <h3 class="h6 fw-bold mb-2 text-dark">Pickup</h3>
            <p class="text-muted small mb-0" style="line-height: 1.6;">Kendaraan niaga ringan bak terbuka.</p>
          </div>
        </div>

        <!-- Truk Kontainer -->
        <div class="col-md-4">
          <div class="odol-vehicle-card p-4 border text-center h-100 bg-white shadow-sm">
            <div class="mb-3">
              <img src="<?= base_url('assets/img/kendaraan-kontainer.svg') ?>" alt="Truk Kontainer" style="height: 95px; max-width: 100%;">
            </div>
            <h3 class="h6 fw-bold mb-2 text-dark">Truk Kontainer</h3>
            <p class="text-muted small mb-0" style="line-height: 1.6;">Kendaraan pengangkut petikemas logistik.</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
