<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="py-4">
  <div class="container">

    <!-- Header Live CCTV -->
    <div class="mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
          <h1 class="h3 fw-bold mb-1">
            Live CCTV Pemantauan Lalu Lintas
          </h1>
          <p class="text-muted small mb-0">
            Tayangan streaming langsung kamera lalu lintas terintegrasi sistem deteksi AI Over Dimension
          </p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="<?= base_url('dashboard') ?>" class="btn-dishub-primary btn-sm">
            <i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>Buka Dashboard AI
          </a>
          <a href="<?= base_url('peta-cctv') ?>" class="btn-dishub-light btn-sm">
            <i class="bi bi-map me-1" aria-hidden="true"></i>Peta Interaktif
          </a>
        </div>
      </div>
    </div>

    <!-- Peringatan jika parameter URL tidak terdaftar -->
    <?php if (!empty($invalid_param)): ?>
      <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5" aria-hidden="true"></i>
        <div>
          <strong>Kamera tidak terdaftar.</strong> Kode CCTV yang diminta tidak ditemukan pada daftar titik kamera aktif. Menampilkan kamera pertama yang tersedia.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
      </div>
    <?php endif; ?>

    <!-- =======================================================================
         1. TAYANGAN PEMUTAR CCTV TERPILIH
         ======================================================================= -->
    <?php if ($selected_cctv): ?>
      <div class="player-wrap shadow-sm mb-4" id="cctvSelectedPlayer">
        <!-- Header Bar Player -->
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-dark"><?= esc($selected_cctv['code']) ?></span>
            <span class="fw-bold text-dark"><?= esc($selected_cctv['name']) ?></span>
            <span class="text-muted small">&bull; <?= esc($selected_cctv['category'] ?? 'Ruas Jalan') ?></span>
            <span class="badge bg-success-subtle text-success border"><i class="bi bi-cpu me-1"></i>Terhubung AI</span>
            <span class="badge bg-primary-subtle text-primary border"><?= esc($selected_cctv['resolution'] ?? 'HD') ?></span>
            <span class="badge bg-secondary-subtle text-dark border">ATCS Banjarmasin</span>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Navigasi Cepat Antara 2 Kamera -->
            <div class="btn-group btn-group-sm" role="group" aria-label="Pilih Kamera">
              <?php foreach ($cameras as $cam): ?>
                <?php $isActive = ($selected_cctv['code'] === $cam['code']); ?>
                <a href="<?= base_url('live-cctv?cctv=' . esc($cam['code'])) ?>" 
                   class="btn <?= $isActive ? 'btn-dishub-primary' : 'btn-outline-secondary' ?> btn-sm"
                   style="font-size: 0.78rem;">
                  <i class="bi <?= $isActive ? 'bi-camera-video-fill' : 'bi-camera-video' ?> me-1"></i>
                  <?= esc($cam['short_name'] ?? $cam['code']) ?>
                </a>
              <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-warning btn-sm d-none fw-semibold" id="btnToolbarPlay" style="font-size: 0.8rem;">
              <i class="bi bi-play-fill me-1" aria-hidden="true"></i>Putar Tayangan
            </button>
          </div>
        </div>

        <!-- Layar Player (HLS Video Element) -->
        <div class="player-screen" id="playerScreenContainer" style="position: relative;">
          <video id="cctvVideoPlayer" 
                 class="cctv-video-player" 
                 playsinline 
                 muted 
                 controls 
                 preload="auto" 
                 poster="<?= esc($selected_cctv['proxy_snapshot'] ?? '') ?>"
                 data-stream-url="<?= esc($selected_cctv['proxy_stream'] ?? $selected_cctv['stream_url'] ?? '') ?>"
                 data-camera-code="<?= esc($selected_cctv['code']) ?>"
                 data-camera-name="<?= esc($selected_cctv['name']) ?>"
                 data-resolution="<?= esc($selected_cctv['resolution'] ?? '') ?>"
                 data-is-vivotek="false"
                 data-is-ai-test="true">
          </video>

          <!-- Floating Bar Top Atas Video -->
          <div class="player-bar-top">
            <div class="d-flex align-items-center gap-2">
              <div class="player-tag d-flex align-items-center gap-2">
                <span class="cctv-live-dot dot-connecting" id="liveDotIndicator"></span>
                <span id="playerLiveBadgeText">MEMUAT STREAM</span>
              </div>
              <div class="player-tag d-none d-md-inline-block">
                <?= esc($selected_cctv['code']) ?> &bull; <?= esc($selected_cctv['name']) ?>
              </div>
            </div>
            <div class="player-tag">
              <i class="bi bi-broadcast me-1" aria-hidden="true"></i>ATCS Live Stream
            </div>
          </div>

          <!-- Overlay State 1: Memuat / Menghubungkan -->
          <div class="player-overlay-state" id="playerLoadingOverlay">
            <div class="spinner-border text-light mb-3" style="width: 2.5rem; height: 2.5rem;" role="status">
              <span class="visually-hidden">Memuat stream...</span>
            </div>
            <div class="text-white fw-semibold mb-1">Menghubungkan ke Tayangan ATCS...</div>
            <div class="text-white-50 small">Memproses stream HLS (<?= esc($selected_cctv['resolution'] ?? 'HD') ?>)</div>
          </div>

          <!-- Overlay State 2: Tayangan Tidak Tersedia / Error Jaringan -->
          <div class="player-overlay-state d-none" id="playerErrorOverlay">
            <i class="bi bi-camera-video-off text-warning fs-1 mb-2" aria-hidden="true"></i>
            <h5 class="text-white mb-1">Tayangan tidak tersedia</h5>
            <p class="small text-white-50 mb-3 text-center" style="max-width: 440px;" id="playerErrorMessage">
              Tidak dapat terhubung ke aliran video upstream ATCS Banjarmasin.
            </p>
            <button type="button" id="btnRetryStream" class="btn btn-outline-light btn-sm px-3 fw-semibold">
              <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Coba Lagi
            </button>
          </div>
        </div>

        <!-- Detail Keterangan CCTV Terpilih -->
        <div class="player-details">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
              <div class="fw-semibold text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= esc($selected_cctv['location']) ?></div>
              <div class="text-muted small"><?= esc($selected_cctv['description'] ?? '') ?> &bull; Koordinat: <?= esc($selected_cctv['latitude']) ?>, <?= esc($selected_cctv['longitude']) ?></div>
            </div>
          </div>

          <div class="pt-2 border-top d-flex flex-wrap align-items-center justify-content-between gap-3 small text-muted">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <span><i class="bi bi-clock-history me-1"></i>Waktu tayang: <strong id="videoDurationText" class="text-dark">00:00</strong></span>
              <span><i class="bi bi-aspect-ratio me-1"></i>Resolusi: <strong class="text-dark" id="playerResolutionText"><?= esc($selected_cctv['resolution'] ?? '') ?></strong></span>
              <span><i class="bi bi-shield-check text-success me-1"></i>Relay HLS: <span class="badge bg-success-subtle text-success border">Lokal Aman</span></span>
              <span><i class="bi bi-cpu text-primary me-1"></i>Deteksi AI: <span class="badge bg-primary-subtle text-primary border">Aktif Otomatis</span></span>
            </div>
            <div>
              Sumber: <a href="https://atcs.banjarmasinkota.go.id/" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-underline">ATCS Dishub Kota Banjarmasin</a>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- =======================================================================
         2. DAFTAR 2 KAMERA CCTV AKTIF
         ======================================================================= -->
    <div class="mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
          <div class="d-flex align-items-center gap-2">
            <h2 class="h5 fw-bold mb-0">Daftar Titik Kamera Aktif</h2>
            <span class="badge bg-success-subtle text-success border" style="font-size: 0.75rem;">
              2 Kamera Aktif &bull; Terhubung AI
            </span>
          </div>
          <p class="text-muted small mb-0 mt-1">
            Pilih kamera di bawah untuk memutar siaran langsung dan memantau deteksi kendaraan
          </p>
        </div>
      </div>

      <!-- Grid 2 Kartu CCTV -->
      <div class="row g-3">
        <?php foreach ($cameras as $cctv): ?>
          <?php $isSelected = ($selected_cctv && $selected_cctv['code'] === $cctv['code']); ?>
          <div class="col-md-6">
            <div class="card h-100 shadow-sm border <?= $isSelected ? 'border-primary' : '' ?>" style="border-radius: 10px; overflow: hidden; transition: transform 0.15s ease, box-shadow 0.15s ease;">
              
              <!-- Thumbnail Cuplikan -->
              <div style="position: relative; height: 210px; background-color: #111; overflow: hidden;">
                <img src="<?= esc($cctv['upstream_snapshot'] ?? $cctv['snapshot_url'] ?? '') ?>" 
                     alt="<?= esc($cctv['name']) ?>" 
                     style="width: 100%; height: 100%; object-fit: cover;"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22400%22%20height%3D%22225%22%20viewBox%3D%220%200%20400%20225%22%3E%3Crect%20fill%3D%22%23222%22%20width%3D%22400%22%20height%3D%22225%22%2F%3E%3Ctext%20fill%3D%22%23888%22%20font-family%3D%22sans-serif%22%20font-size%3D%2214%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%3ECCTV%20Aktif%3C%2Ftext%3E%3C%2Fsvg%3E';">
                
                <!-- Badge Atas Thumbnail -->
                <div style="position: absolute; top: 10px; left: 10px; z-index: 2;">
                  <span class="badge bg-dark bg-opacity-75 text-white border border-secondary px-2 py-1">
                    <?= esc($cctv['code']) ?>
                  </span>
                </div>
                <div style="position: absolute; top: 10px; right: 10px; z-index: 2;">
                  <span class="badge bg-success text-white px-2 py-1">
                    <span class="cctv-live-dot dot-live me-1" style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #fff;"></span>LIVE
                  </span>
                </div>

                <!-- Tag Resolusi di Bawah Thumbnail -->
                <div style="position: absolute; bottom: 8px; right: 10px; z-index: 2;">
                  <span class="badge bg-dark bg-opacity-75 text-light border border-secondary" style="font-size: 0.72rem;">
                    <?= esc($cctv['resolution'] ?? 'HD') ?>
                  </span>
                </div>
              </div>

              <!-- Body Kartu -->
              <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <h5 class="card-title fw-bold text-dark mb-0 fs-6">
                      <?= esc($cctv['name']) ?>
                    </h5>
                  </div>
                  <p class="text-muted small mb-2">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= esc($cctv['location']) ?>
                  </p>
                  <p class="text-secondary small mb-3" style="font-size: 0.8rem; line-height: 1.4;">
                    <?= esc($cctv['description'] ?? '') ?>
                  </p>
                </div>

                <div class="pt-2 border-top">
                  <?php if ($isSelected): ?>
                    <button type="button" class="btn btn-success btn-sm w-100 fw-semibold disabled" style="opacity: 0.95;">
                      <i class="bi bi-play-circle-fill me-1"></i>Sedang Ditampilkan di Pemutar
                    </button>
                  <?php else: ?>
                    <a href="<?= base_url('live-cctv?cctv=' . esc($cctv['code'])) ?>" class="btn-dishub-primary btn-sm w-100 text-center fw-semibold">
                      <i class="bi bi-play-circle me-1"></i>Putar Kamera Ini
                    </a>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (!empty($selected_cctv) && !empty($selected_cctv['stream_url'])): ?>
  <!-- Script Pemutar HLS untuk Kamera Terpilih (ATCS Banjarmasin) -->
  <script src="<?= base_url('assets/vendor/hls/hls.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/pages/cctv-player.js') ?>"></script>
<?php endif; ?>
<?= $this->endSection() ?>
