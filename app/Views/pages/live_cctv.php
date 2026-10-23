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
            Tayangan streaming langsung 34 titik kamera CCTV ATCS. Deteksi AI Over Dimension aktif di Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal).
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
      <?php $isAiActive = !empty($selected_cctv['is_ai_active']); ?>
      <div class="player-wrap shadow-sm mb-4" id="cctvSelectedPlayer">
        <!-- Header Bar Player -->
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-dark"><?= esc($selected_cctv['code']) ?></span>
            <span class="fw-bold text-dark"><?= esc($selected_cctv['name']) ?></span>
            <span class="text-muted small">&bull; <?= esc($selected_cctv['category'] ?? 'Ruas Jalan') ?></span>
            <?php if ($isAiActive): ?>
              <span class="badge bg-success-subtle text-success border"><i class="bi bi-cpu-fill me-1"></i>Deteksi AI Aktif</span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-eye me-1"></i>Monitoring Tayangan</span>
            <?php endif; ?>
            <span class="badge bg-primary-subtle text-primary border"><?= esc($selected_cctv['resolution'] ?? 'HD') ?></span>
            <span class="badge bg-secondary-subtle text-dark border">ATCS Banjarmasin</span>
          </div>

          <!-- Pemilih Kamera Cepat & Dropdown -->
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= base_url('live-cctv?cctv=ATCS-01') ?>" 
               class="btn <?= ($selected_cctv['code'] === 'ATCS-01') ? 'btn-dishub-primary' : 'btn-outline-primary' ?> btn-sm"
               style="font-size: 0.8rem;"
               title="Pindah ke kamera deteksi AI utama">
              <i class="bi bi-cpu me-1"></i>Soebardjo (AI)
            </a>

            <div class="d-flex align-items-center gap-1">
              <select id="cctvSelectPicker" 
                      class="form-select form-select-sm" 
                      style="max-width: 270px; font-size: 0.8rem;" 
                      onchange="if(this.value) window.location.href=this.value;">
                <optgroup label="⭐ Deteksi AI Over Dimension">
                  <option value="<?= base_url('live-cctv?cctv=ATCS-01') ?>" <?= ($selected_cctv['code'] === 'ATCS-01') ? 'selected' : '' ?>>
                    [ATCS-01] Jl. Gubernur Soebardjo (AI Aktif)
                  </option>
                </optgroup>
                <optgroup label="📹 Kamera Monitoring ATCS (33 Titik)">
                  <?php foreach ($cameras as $cam): ?>
                    <?php if ($cam['code'] === 'ATCS-01') continue; ?>
                    <option value="<?= base_url('live-cctv?cctv=' . esc($cam['code'])) ?>" <?= ($selected_cctv['code'] === $cam['code']) ? 'selected' : '' ?>>
                      [<?= esc($cam['code']) ?>] <?= esc($cam['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
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
                 data-fallback-url="<?= esc($selected_cctv['upstream_stream'] ?? '') ?>"
                 data-camera-code="<?= esc($selected_cctv['code']) ?>"
                 data-camera-name="<?= esc($selected_cctv['name']) ?>"
                 data-resolution="<?= esc($selected_cctv['resolution'] ?? '') ?>"
                 data-is-vivotek="false"
                 data-is-ai-test="<?= $isAiActive ? 'true' : 'false' ?>">
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
              <span>
                <i class="bi bi-cpu <?= $isAiActive ? 'text-primary' : 'text-secondary' ?> me-1"></i>Deteksi AI: 
                <?php if ($isAiActive): ?>
                  <span class="badge bg-success-subtle text-success border">Aktif (Over Dimension)</span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle text-secondary border">Monitoring Saja</span>
                <?php endif; ?>
              </span>
            </div>
            <div>
              Sumber: <a href="https://atcs.banjarmasinkota.go.id/" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-underline">ATCS Dishub Kota Banjarmasin</a>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- =======================================================================
         2. DAFTAR 34 KAMERA CCTV ATCS
         ======================================================================= -->
    <div class="mb-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3 gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <h2 class="h5 fw-bold mb-0">Daftar Titik Kamera ATCS Banjarmasin</h2>
            <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.75rem;">
              34 Kamera Terdaftar
            </span>
            <span class="badge bg-success-subtle text-success border" style="font-size: 0.75rem;">
              <i class="bi bi-cpu-fill me-1"></i>1 Titik Deteksi AI
            </span>
          </div>
          <p class="text-muted small mb-0 mt-1">
            Pilih kamera di bawah untuk memutar siaran langsung. Deteksi AI Over Dimension difokuskan pada Jl. Gubernur Soebardjo.
          </p>
        </div>

        <!-- Kolom Pencarian Kamera -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="input-group input-group-sm" style="min-width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="cameraSearchInput" class="form-control border-start-0" placeholder="Cari nama lokasi, jalan, simpang..." aria-label="Cari kamera">
          </div>
        </div>
      </div>

      <!-- Filter Kategori Kancing -->
      <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="categoryFilterButtons">
        <button type="button" class="btn btn-sm btn-dishub-primary active category-filter-btn" data-category="all">
          Semua (34)
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="ai">
          <i class="bi bi-cpu-fill text-success me-1"></i>Deteksi AI (1)
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Simpang">
          Simpang
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Ruas Jalan">
          Ruas Jalan
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Jembatan">
          Jembatan
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Putar Balik">
          Putar Balik
        </button>
      </div>

      <!-- Pesan jika hasil pencarian kosong -->
      <div id="noCameraResultsMessage" class="alert alert-light border text-center py-4 d-none">
        <i class="bi bi-camera-video-off fs-2 text-muted mb-2 d-block"></i>
        <div class="fw-semibold text-dark">Kamera tidak ditemukan</div>
        <div class="text-muted small">Coba masukkan kata kunci pencarian yang lain.</div>
      </div>

      <!-- Grid 34 Kartu CCTV -->
      <div class="row g-3" id="camerasCardGrid">
        <?php foreach ($cameras as $cctv): ?>
          <?php 
            $isSelected   = ($selected_cctv && $selected_cctv['code'] === $cctv['code']); 
            $isAiCam      = !empty($cctv['is_ai_active']);
            $catName      = $cctv['category'] ?? 'Ruas Jalan';
          ?>
          <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 camera-card-col" 
               data-code="<?= esc(strtolower($cctv['code'])) ?>"
               data-name="<?= esc(strtolower($cctv['name'])) ?>"
               data-location="<?= esc(strtolower($cctv['location'])) ?>"
               data-category="<?= esc($catName) ?>"
               data-is-ai="<?= $isAiCam ? 'true' : 'false' ?>">
            <div class="card h-100 shadow-sm border <?= $isSelected ? 'border-primary ring-focus' : '' ?>" style="border-radius: 10px; overflow: hidden; transition: transform 0.15s ease, box-shadow 0.15s ease;">
              
              <!-- Thumbnail Cuplikan -->
              <div style="position: relative; height: 160px; background-color: #111; overflow: hidden;">
                <img src="<?= esc($cctv['upstream_snapshot'] ?? $cctv['snapshot_url'] ?? '') ?>" 
                     alt="<?= esc($cctv['name']) ?>" 
                     loading="lazy"
                     style="width: 100%; height: 100%; object-fit: cover;"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22400%22%20height%3D%22225%22%20viewBox%3D%220%200%20400%20225%22%3E%3Crect%20fill%3D%22%23222%22%20width%3D%22400%22%20height%3D%22225%22%2F%3E%3Ctext%20fill%3D%22%23888%22%20font-family%3D%22sans-serif%22%20font-size%3D%2214%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%3ECCTV%20ATCS%3C%2Ftext%3E%3C%2Fsvg%3E';">
                
                <!-- Badge Kiri Atas (Kode) -->
                <div style="position: absolute; top: 8px; left: 8px; z-index: 2;">
                  <span class="badge bg-dark bg-opacity-75 text-white border border-secondary px-2 py-1" style="font-size: 0.72rem;">
                    <?= esc($cctv['code']) ?>
                  </span>
                </div>

                <!-- Badge Kanan Atas (Status AI / Monitoring) -->
                <div style="position: absolute; top: 8px; right: 8px; z-index: 2;">
                  <?php if ($isAiCam): ?>
                    <span class="badge bg-primary text-white px-2 py-1 shadow-sm" style="font-size: 0.72rem;">
                      <i class="bi bi-cpu-fill me-1"></i>Deteksi AI
                    </span>
                  <?php else: ?>
                    <span class="badge bg-dark bg-opacity-75 text-white-50 px-2 py-1 border border-secondary" style="font-size: 0.72rem;">
                      <i class="bi bi-eye me-1"></i>Monitoring
                    </span>
                  <?php endif; ?>
                </div>

                <!-- Tag Kategori & Resolusi di Bawah Thumbnail -->
                <div style="position: absolute; bottom: 6px; left: 8px; z-index: 2;">
                  <span class="badge bg-dark bg-opacity-75 text-light border border-secondary" style="font-size: 0.68rem;">
                    <?= esc($catName) ?>
                  </span>
                </div>
                <div style="position: absolute; bottom: 6px; right: 8px; z-index: 2;">
                  <span class="badge bg-success text-white" style="font-size: 0.68rem;">
                    <span class="cctv-live-dot dot-live me-1" style="display: inline-block; width: 5px; height: 5px; border-radius: 50%; background-color: #fff;"></span>LIVE
                  </span>
                </div>
              </div>

              <!-- Body Kartu -->
              <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                  <h5 class="card-title fw-bold text-dark mb-1 fs-6 text-truncate" title="<?= esc($cctv['name']) ?>">
                    <?= esc($cctv['name']) ?>
                  </h5>
                  <p class="text-muted small mb-2 text-truncate" style="font-size: 0.78rem;" title="<?= esc($cctv['location']) ?>">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= esc($cctv['location']) ?>
                  </p>
                </div>

                <div class="pt-2 border-top mt-2">
                  <?php if ($isSelected): ?>
                    <button type="button" class="btn btn-success btn-sm w-100 fw-semibold disabled" style="opacity: 0.95; font-size: 0.8rem;">
                      <i class="bi bi-play-circle-fill me-1"></i>Sedang Ditampilkan
                    </button>
                  <?php else: ?>
                    <a href="<?= base_url('live-cctv?cctv=' . esc($cctv['code'])) ?>" class="btn-dishub-primary btn-sm w-100 text-center fw-semibold" style="font-size: 0.8rem;">
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

<script>
  // Filter & Pencarian Kamera Sisi Klien Instan
  document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('cameraSearchInput');
    const filterButtons = document.querySelectorAll('.category-filter-btn');
    const cameraCols = document.querySelectorAll('.camera-card-col');
    const noResults = document.getElementById('noCameraResultsMessage');
    let activeCategory = 'all';

    function applyFilter() {
      const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
      let visibleCount = 0;

      cameraCols.forEach(function (col) {
        const name = col.getAttribute('data-name') || '';
        const loc = col.getAttribute('data-location') || '';
        const code = col.getAttribute('data-code') || '';
        const category = col.getAttribute('data-category') || '';
        const isAi = col.getAttribute('data-is-ai') === 'true';

        // Check category match
        let matchesCategory = false;
        if (activeCategory === 'all') {
          matchesCategory = true;
        } else if (activeCategory === 'ai') {
          matchesCategory = isAi;
        } else if (activeCategory === category) {
          matchesCategory = true;
        }

        // Check text search query
        let matchesQuery = true;
        if (query !== '') {
          matchesQuery = name.includes(query) || loc.includes(query) || code.includes(query);
        }

        if (matchesCategory && matchesQuery) {
          col.classList.remove('d-none');
          visibleCount++;
        } else {
          col.classList.add('d-none');
        }
      });

      if (noResults) {
        if (visibleCount === 0) {
          noResults.classList.remove('d-none');
        } else {
          noResults.classList.add('d-none');
        }
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', applyFilter);
    }

    filterButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        filterButtons.forEach(b => {
          b.classList.remove('btn-dishub-primary', 'active');
          b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-dishub-primary', 'active');
        activeCategory = this.getAttribute('data-category');
        applyFilter();
      });
    });
  });
</script>
<?= $this->endSection() ?>
