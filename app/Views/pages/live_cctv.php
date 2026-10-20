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
            Titik kamera pemantauan persimpangan jalan protokol Kota Banjarbaru
          </p>
        </div>
        <div>
          <a href="<?= base_url('peta-cctv') ?>" class="btn-dishub-light btn-sm">
            <i class="bi bi-map" aria-hidden="true"></i>Buka Peta Interaktif
          </a>
        </div>
      </div>
    </div>

    <!-- Peringatan jika parameter URL tidak terdaftar -->
    <?php if (!empty($invalid_param)): ?>
      <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5" aria-hidden="true"></i>
        <div>
          <strong>Kamera tidak terdaftar.</strong> Kode CCTV yang diminta tidak ditemukan pada daftar titik kamera aktif Banjarbaru maupun ATCS Banjarmasin. Menampilkan daftar kamera yang tersedia di bawah.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
      </div>
    <?php endif; ?>

    <!-- =======================================================================
         1. TAYANGAN / PANEL CCTV TERPILIH
         ======================================================================= -->
    <?php if ($selected_cctv): ?>
      <?php 
        $cctvSource = $selected_cctv['source'] ?? 'dishub_banjarbaru';
        $isAtcs = ($cctvSource === 'atcs_banjarmasin');
        $isVivotek = str_starts_with($cctvSource, 'vivotek');
        $hasStream = !empty($selected_cctv['stream_url']);
        $isUnavailable = ($selected_cctv['status'] === 'unavailable' || ($selected_cctv['availability'] ?? '') === 'unavailable');
        $isOffline = ($selected_cctv['status'] === 'offline');
      ?>

      <?php if ($hasStream): ?>
        <!-- A. PLAYER HLS AKTIF TUNGGAL (ATCS BANJARMASIN ATAU VIVOTEK BANJARBARU) -->
        <div class="player-wrap shadow-sm" id="cctvSelectedPlayer">
          <!-- Header Bar Player -->
          <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-dark"><?= esc($selected_cctv['code']) ?></span>
              <span class="fw-bold text-dark"><?= esc($selected_cctv['name']) ?></span>
              <?php if (!empty($selected_cctv['is_ai_test'])): ?>
                <span class="text-muted small">&bull; ATCS Banjarmasin</span>
                <span class="badge bg-warning-subtle text-dark border"><i class="bi bi-cpu me-1"></i>Kamera Uji AI</span>
                <span class="badge bg-primary-subtle text-primary border">Model: best_yolo_cls.onnx (5 kelas)</span>
                <span class="badge bg-secondary-subtle text-dark border">1280x720 (HD)</span>
              <?php elseif ($isVivotek): ?>
                <span class="text-muted small">&bull; <?= esc($selected_cctv['intersection_name'] ?? 'Dishub Kota Banjarbaru') ?></span>
                <span class="badge bg-primary-subtle text-primary border">Vivotek Banjarbaru</span>
                <span class="badge bg-secondary-subtle text-dark border">Jaringan Kantor</span>
                <span class="badge bg-success-subtle text-success border"><?= esc(strtoupper($selected_cctv['type'] ?? 'FIXED')) ?></span>
              <?php else: ?>
                <span class="text-muted small">&bull; <?= esc($selected_cctv['category'] ?? 'Ruas Jalan') ?></span>
                <span class="badge bg-secondary-subtle text-dark border"><?= esc($selected_cctv['resolution'] ?? 'HD') ?></span>
                <span class="badge bg-primary-subtle text-primary border">ATCS Banjarmasin</span>
              <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <?php if (!empty($selected_cctv['is_ai_test'])): ?>
                <!-- Toggle hanya visual. Aktif hanya bila sumber resmi menyediakan kotak + waktu frame. -->
                <button type="button"
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 fw-semibold"
                        id="btnToggleAiOverlay"
                        aria-pressed="false"
                        aria-disabled="true"
                        disabled
                        title="Kotak deteksi belum tersedia: core Rizkiya belum mengeluarkan koordinat dan waktu frame"
                        style="font-size: 0.8rem; border-radius: 6px; padding: 4px 10px;">
                  <i class="bi bi-bounding-box text-muted" id="iconToggleOverlay" aria-hidden="true"></i>
                  <span id="textToggleOverlay">Kotak Deteksi: <strong class="text-secondary">Belum Tersedia</strong></span>
                </button>
              <?php endif; ?>
              <button type="button" class="btn btn-warning btn-sm d-none fw-semibold" id="btnToolbarPlay" style="font-size: 0.8rem;">
                <i class="bi bi-play-fill me-1" aria-hidden="true"></i>Putar Tayangan
              </button>
              <a href="<?= base_url('live-cctv') ?>" class="btn-dishub-light btn-sm" id="btnClosePlayer" style="font-size: 0.8rem;">
                <i class="bi bi-x-lg" aria-hidden="true"></i>Tutup Tayangan
              </a>
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
                   data-is-vivotek="<?= $isVivotek ? 'true' : 'false' ?>"
                   data-is-ai-test="<?= !empty($selected_cctv['is_ai_test']) ? 'true' : 'false' ?>">
            </video>

            <!-- Canvas Overlay untuk Kotak Deteksi AI -->
            <canvas id="aiOverlayCanvas" 
                    class="ai-overlay-canvas" 
                    aria-hidden="true"
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 10; display: none;">
            </canvas>

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
                <?php if ($isVivotek): ?>
                  <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Vivotek
                <?php else: ?>
                  <i class="bi bi-broadcast me-1" aria-hidden="true"></i>ATCS Banjarmasin
                <?php endif; ?>
              </div>
            </div>

            <!-- Overlay State 1: Memuat / Menghubungkan -->
            <div class="player-overlay-state" id="playerLoadingOverlay">
              <div class="spinner-border text-light mb-3" style="width: 2.5rem; height: 2.5rem;" role="status">
                <span class="visually-hidden">Memuat stream...</span>
              </div>
              <div class="text-white fw-semibold mb-1"><?= $isVivotek ? 'Menghubungkan ke Kamera Vivotek (Jaringan Kantor)...' : 'Menghubungkan ke Tayangan ATCS...' ?></div>
              <div class="text-white-50 small">Memproses stream HLS (<?= esc($selected_cctv['resolution'] ?? 'HD') ?>)</div>
            </div>

            <!-- Overlay State 2: Tayangan Tidak Tersedia / Error Jaringan -->
            <div class="player-overlay-state d-none" id="playerErrorOverlay">
              <i class="bi bi-camera-video-off text-warning fs-1 mb-2" aria-hidden="true"></i>
              <h5 class="text-white mb-1">Tayangan tidak tersedia</h5>
              <p class="small text-white-50 mb-3 text-center" style="max-width: 440px;" id="playerErrorMessage">
                <?= $isVivotek 
                  ? 'Aliran video Vivotek belum aktif di relay lokal atau kredensial .env belum dikonfigurasi. Pastikan perangkat terhubung ke jaringan kantor.'
                  : 'Tidak dapat terhubung ke aliran video upstream ATCS Banjarmasin.' ?>
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
                <?php if (!empty($selected_cctv['is_ai_test'])): ?>
                  <span><i class="bi bi-bounding-box me-1"></i>Overlay: <strong class="text-dark" id="aiOverlayStatusText">Memeriksa sumber kotak...</strong></span>
                <?php endif; ?>
              </div>
              <div>
                <?php if ($isVivotek): ?>
                  <span class="text-dark fw-semibold"><i class="bi bi-hdd-network me-1"></i>Vivotek ND9541 (10.0.1.3:8554)</span>
                <?php else: ?>
                  Sumber: <a href="https://atcs.banjarmasinkota.go.id/" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-underline">ATCS Dishub Kota Banjarmasin</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <?php if (!empty($selected_cctv['is_ai_test'])): ?>
          <!-- Core Rizkiya: dijalankan apa adanya di iframe host terisolasi, hanya setelah diminta -->
          <div class="card-plain p-3 mt-3" id="rizkiyaCorePanel" data-host-url="<?= base_url('ai-core/rizkiya-host') ?>">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
              <div>
                <h2 class="h6 fw-bold text-dark mb-1"><i class="bi bi-cpu text-primary me-2" aria-hidden="true"></i>Core Rizkiya &ndash; Uji di Browser</h2>
                <p class="text-muted small mb-0">
                  Kode deteksi milik Rizkiya dijalankan tanpa perubahan di browser ini. Model (&plusmn;63 MB) diunduh dari Hugging Face saat dijalankan.
                  Proses berhenti bila halaman ditutup, dan hasilnya <strong>tidak disimpan</strong> ke database karena browser publik bukan pengirim tepercaya.
                </p>
              </div>
              <button type="button" class="btn-dishub-primary btn-sm text-nowrap" id="btnRunRizkiyaCore" aria-pressed="false">
                <i class="bi bi-play-circle me-1" aria-hidden="true"></i><span>Jalankan Core</span>
              </button>
            </div>

            <dl class="row small mb-0 mt-2" aria-live="polite">
              <dt class="col-sm-4 col-lg-3 text-muted fw-normal">Salinan core</dt>
              <dd class="col-sm-8 col-lg-9 mb-1" id="rzHost">Belum dijalankan</dd>
              <dt class="col-sm-4 col-lg-3 text-muted fw-normal">Video pada core</dt>
              <dd class="col-sm-8 col-lg-9 mb-1" id="rzVideo">-</dd>
              <dt class="col-sm-4 col-lg-3 text-muted fw-normal">Model</dt>
              <dd class="col-sm-8 col-lg-9 mb-1" id="rzModel">-</dd>
              <dt class="col-sm-4 col-lg-3 text-muted fw-normal">Inferensi</dt>
              <dd class="col-sm-8 col-lg-9 mb-1" id="rzInference">-</dd>
              <dt class="col-sm-4 col-lg-3 text-muted fw-normal">Keluaran terakhir core</dt>
              <dd class="col-sm-8 col-lg-9 mb-1" id="rzResult">-</dd>
              <dt class="col-sm-4 col-lg-3 text-muted fw-normal">Diterima adapter</dt>
              <dd class="col-sm-8 col-lg-9 mb-1" id="rzResultTime">-</dd>
            </dl>
            <p class="text-muted mb-0 mt-2" style="font-size: 0.75rem;">
              Keluaran yang dapat diamati dari core hanya teks status dan teks hasil (label + persen). Core belum menyediakan indeks kelas,
              kotak deteksi, ID kendaraan maupun waktu frame.
            </p>

            <div class="mt-3 d-none" id="rizkiyaCoreFrameWrap">
              <iframe id="rizkiyaCoreFrame" title="Host Core Rizkiya" style="width: 100%; max-width: 680px; height: 600px; border: 1px solid var(--bs-border-color); border-radius: 6px;"></iframe>
            </div>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- B. PANEL DETAIL KAMERA DISHUB BANJARBARU (PLACEHOLDER RESMI TANPA STREAM KOSONG) -->
        <div class="player-wrap shadow-sm mb-4" id="cctvSelectedPlayer">
          <!-- Header Bar Panel -->
          <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-dark"><?= esc($selected_cctv['code']) ?></span>
              <span class="fw-bold text-dark"><?= esc($selected_cctv['full_name'] ?? $selected_cctv['name']) ?></span>
              <span class="text-muted small">&bull; <?= esc($selected_cctv['intersection_name']) ?></span>
              <?php if ($selected_cctv['type'] === 'ptz'): ?>
                <span class="badge bg-primary-subtle text-primary border">PTZ</span>
              <?php else: ?>
                <span class="badge bg-success-subtle text-success border">FIXED</span>
              <?php endif; ?>
              <?php if (!empty($selected_cctv['direction'])): ?>
                <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-compass me-1"></i>Arah: <?= esc($selected_cctv['direction']) ?></span>
              <?php endif; ?>
            </div>
            <div>
              <a href="<?= base_url('live-cctv') ?>" class="btn-dishub-light btn-sm" style="font-size: 0.8rem;">
                <i class="bi bi-x-lg" aria-hidden="true"></i>Tutup Pilihan
              </a>
            </div>
          </div>

          <!-- Layar Visual Placeholder Bersih -->
          <div class="bjb-placeholder-screen">
            <div class="placeholder-icon-wrap" style="width: 64px; height: 64px; font-size: 1.8rem; margin-bottom: 1rem;">
              <i class="bi <?= ($selected_cctv['type'] === 'ptz') ? 'bi-arrows-move' : ($isUnavailable ? 'bi-dash-circle' : 'bi-camera-video') ?>" aria-hidden="true"></i>
            </div>

            <?php if ($isUnavailable): ?>
              <h4 class="text-white fw-bold mb-2">Kamera Belum Tersedia</h4>
              <p class="text-white-50 small mb-3" style="max-width: 520px;">
                Titik pemantauan ini masuk dalam rencana persimpangan jalan namun belum terpasang di lapangan.
              </p>
            <?php elseif ($isOffline): ?>
              <h4 class="text-white fw-bold mb-2">Perangkat Kamera Tidak Aktif</h4>
              <p class="text-white-50 small mb-3" style="max-width: 540px;">
                Kamera ini terpasang di persimpangan jalan Kota Banjarbaru namun saat ini mengalami kendala teknis / kerusakan perangkat di lapangan.
              </p>
            <?php else: ?>
              <h4 class="text-white fw-bold mb-2">Tayangan Vivotek Belum Terhubung</h4>
              <p class="text-white-50 small mb-3" style="max-width: 540px;">
                Perangkat kamera terpasang di persimpangan jalan Kota Banjarbaru. Hubungkan relay streaming lokal untuk mengaktifkan tayangan langsung.
              </p>
            <?php endif; ?>

            <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
              <span class="badge bg-dark border text-light py-2 px-3">
                <i class="bi bi-geo-alt me-1 text-warning"></i>Koordinat: <?= esc($selected_cctv['latitude']) ?>, <?= esc($selected_cctv['longitude']) ?>
              </span>
              <?php if (!empty($selected_cctv['direction'])): ?>
                <span class="badge bg-dark border text-light py-2 px-3">
                  <i class="bi bi-compass me-1 text-success"></i>Arah: <?= esc($selected_cctv['direction']) ?>
                </span>
              <?php endif; ?>
            </div>

            <div>
              <a href="<?= base_url('peta-cctv?cctv=' . esc($selected_cctv['code'])) ?>" class="btn btn-outline-light btn-sm px-3 fw-semibold">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i>Lihat Posisi pada Peta Interaktif
              </a>
            </div>
          </div>

          <!-- Keterangan Detail di Bawah Layar -->
          <div class="player-details">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
              <div>
                <div class="fw-semibold text-dark">
                  <?= esc($selected_cctv['intersection_name']) ?>
                </div>
              </div>
              <div>
                <?php if ($isUnavailable): ?>
                  <span class="cctv-status-badge status-unavailable">
                    <i class="bi bi-dash-circle me-1"></i>Status: Belum tersedia
                  </span>
                <?php elseif ($selected_cctv['status'] === 'online'): ?>
                  <span class="cctv-status-badge status-online">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.55rem;"></i>Status: Online
                  </span>
                <?php else: ?>
                  <span class="cctv-status-badge status-offline">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>Status: Offline
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="pt-2 border-top text-muted fst-italic" style="font-size: 0.75rem;">
              * Koordinat posisi PTZ masih perkiraan.
            </div>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <!-- =======================================================================
         2. BAGIAN UTAMA: CCTV DISHUB KOTA BANJARBARU (17 KAMERA)
         ======================================================================= -->
    <div class="mb-5">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
          <div class="d-flex align-items-center gap-2">
            <h2 class="h5 fw-bold mb-0">CCTV Dishub Kota Banjarbaru</h2>
            <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.75rem;">
              17 Terpasang &bull; 1 Belum Tersedia
            </span>
          </div>
          <p class="text-muted small mb-0 mt-1">
            Titik pemantauan pada 4 persimpangan utama Kota Banjarbaru
          </p>
        </div>
      </div>

      <!-- Bar Filter & Pencarian Banjarbaru -->
      <div class="filter-bar mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
          <!-- Filter Persimpangan (Sejajar Satu Baris) -->
          <div class="d-flex flex-wrap flex-lg-nowrap gap-1 align-items-center">
            <button type="button" class="filter-btn active filter-pill" data-filter="semua">
              Semua Simpang (18 Titik / 17 Terpasang)
            </button>
            <button type="button" class="filter-btn filter-pill" data-filter="s3-brimob">
              Simpang Brimob (4)
            </button>
            <button type="button" class="filter-btn filter-pill" data-filter="s3-ro-ulin">
              Simpang RO Ulin (4)
            </button>
            <button type="button" class="filter-btn filter-pill" data-filter="s4-samsat">
              Simpang Samsat (5)
            </button>
            <button type="button" class="filter-btn filter-pill" data-filter="s4-adipura">
              Simpang Adipura (5)
            </button>
          </div>

          <!-- Kolom Pencarian (Diperkecil Agar Muat Sejajar) -->
          <div class="search-col-box" style="min-width: 220px; max-width: 280px; width: 100%;">
            <div class="search-wrapper">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input type="text" 
                     id="cctvSearchInput" 
                     class="search-field-dishub" 
                     placeholder="Cari kode, nama, fase, PTZ, atau arah..."
                     autocomplete="off"
                     aria-label="Cari CCTV Banjarbaru">
            </div>
          </div>
        </div>
      </div>

      <!-- State Kosong Pencarian -->
      <div id="cctvEmptyState" class="card-plain text-center py-5 my-4 d-none">
        <h5 class="h6 fw-bold mb-2">Kamera Tidak Ditemukan</h5>
        <p class="text-muted small mb-3">Tidak ada titik CCTV Banjarbaru yang cocok dengan pencarian atau filter yang dipilih.</p>
        <button type="button" id="resetFilterBtn" class="btn-dishub-primary btn-sm">
          <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Atur Ulang Pencarian
        </button>
      </div>

      <!-- Grid 18 Kartu CCTV Banjarbaru (4 Kartu per Baris) -->
      <div class="row g-3" id="cctvGridContainer">
        <?php foreach ($bjb_cameras as $cctv): ?>
          <?= view('partials/cctv_card', ['cctv' => $cctv, 'col_class' => 'col-xl-3 col-lg-3 col-md-6', 'hide_redundant_badges' => true, 'show_favorite' => true]) ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- =======================================================================
         3. BAGIAN KHUSUS: PENGUJIAN MODEL AI - SATU KAMERA ATCS BANJARMASIN
         ======================================================================= -->
    <?php if (!empty($ai_test_camera)): ?>
      <div class="mt-5 pt-4 border-top" id="sectionAiTestCamera">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
          <div>
            <div class="d-flex align-items-center gap-2">
              <h2 class="h5 fw-bold mb-0">Pengujian AI - ATCS Banjarmasin</h2>
              <span class="badge bg-warning-subtle text-dark border" style="font-size: 0.75rem;">
                1 Titik Pengujian (best_yolo_cls.onnx)
              </span>
            </div>
            <p class="text-muted small mb-0 mt-1">
              Kamera publik ATCS Kota Banjarmasin khusus pengujian integrasi model klasifikasi truk milik Rizkiya
            </p>
          </div>
          <div>
            <?php $coreLive = !empty($ai_core_status['is_live']); ?>
            <span class="badge <?= $coreLive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-dark' ?> border px-2 py-1 small"
                  title="Berdasarkan heartbeat pengirim tepercaya core Rizkiya">
              <i class="bi bi-activity me-1" aria-hidden="true"></i>Pengirim core: <?= esc($ai_core_status['label'] ?? 'Belum terhubung') ?>
            </span>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-xl-4 col-lg-5 col-md-6">
            <?= view('partials/cctv_card', [
              'cctv'                  => $ai_test_camera,
              'col_class'             => 'col-12',
              'hide_redundant_badges' => false,
              'show_favorite'         => false,
            ]) ?>
          </div>
          <div class="col-xl-8 col-lg-7 col-md-6">
            <div class="card-plain p-3 h-100 d-flex flex-column justify-content-center">
              <h6 class="fw-bold text-dark mb-2">
                <i class="bi bi-cpu text-primary me-2"></i>Tentang Pengujian Integrasi Model AI
              </h6>
              <p class="text-muted small mb-2">
                Titik ini diarahkan ke <strong>Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal Masuk Kota)</strong> yang merupakan jalur lintasan angkutan barang.
                Pengujian memakai core deteksi milik Rizkiya dengan model klasifikasi 5 kelas (<code class="text-primary">best_yolo_cls.onnx</code>).
              </p>
              <ul class="text-muted small mb-3 ps-3">
                <li><strong>5 Kelas Model:</strong> Truk Box, Truk Kontainer, Truk Over Dimension, Truk Tangki, Truk Bak Terbuka Normal.</li>
                <li><strong>Core di browser:</strong> core dapat dijalankan dari halaman tayangan. Proses berhenti bila halaman ditutup dan hasilnya belum disimpan.</li>
                <li><strong>Kotak Deteksi:</strong> belum tersedia karena core belum mengeluarkan koordinat kendaraan dan waktu frame.</li>
                <li><strong>Hasil model</strong> adalah prediksi/indikasi visual, bukan pengukuran dimensi atau pelanggaran yang diverifikasi petugas.</li>
              </ul>
              <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('live-cctv?cctv=' . esc($ai_test_camera['code'])) ?>" class="btn-dishub-primary btn-sm">
                  <i class="bi bi-play-circle me-1"></i>Buka Tayangan Kamera Uji
                </a>
                <a href="<?= base_url('dashboard') ?>" class="btn-dishub-light btn-sm">
                  <i class="bi bi-bar-chart-line me-1"></i>Lihat Dashboard
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (!empty($selected_cctv) && !empty($selected_cctv['stream_url'])): ?>
  <!-- Script Pemutar HLS untuk Kamera Terpilih (ATCS Banjarmasin / Vivotek Brimob) -->
  <script src="<?= base_url('assets/vendor/hls/hls.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/pages/cctv-player.js') ?>"></script>
  <?php if (!empty($selected_cctv['is_ai_test'])): ?>
    <script src="<?= base_url('assets/js/pages/rizkiya-core-panel.js') ?>"></script>
  <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
