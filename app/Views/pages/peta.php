<?= $this->extend('layout/main') ?>

<?= $this->section('styles') ?>
<!-- Leaflet 1.9.4 Local Vendor CSS -->
<link rel="stylesheet" href="<?= base_url('assets/vendor/leaflet/leaflet.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="py-4">
  <div class="container">

    <!-- Header Peta & Link Kembali -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
      <div>
        <h1 class="h3 fw-bold mb-1">
          Peta Interaktif Titik CCTV Dishub Banjarbaru
        </h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('live-cctv') ?>" class="btn-dishub-light btn-sm">
          <i class="bi bi-camera-video me-1" aria-hidden="true"></i>Buka Live CCTV
        </a>
        <a href="<?= base_url() ?>" class="btn-dishub-light btn-sm">
          <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Beranda
        </a>
      </div>
    </div>

    <!-- Alert / Notifikasi Fokus Kamera jika ada deep-link -->
    <?php if (!empty($focus_camera)): ?>
      <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-3 py-2 px-3 small" role="alert">
        <i class="bi bi-geo-alt-fill text-danger me-2 fs-5" aria-hidden="true"></i>
        <div>
          Menampilkan titik kamera <strong><?= esc($focus_camera['code']) ?> (<?= esc($focus_camera['full_name']) ?>)</strong> pada persimpangan <?= esc($focus_camera['intersection_name']) ?>.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
      </div>
    <?php endif; ?>

    <!-- Area Kanvas Peta Leaflet Geografis -->
    <div class="leaflet-map-wrapper mb-3">
      <!-- Kontrol Tombol Atas Kanan -->
      <div class="leaflet-map-controls-bar">
        <button type="button" id="btnToggleFullscreenMap" class="btn-map-control" title="Tampilkan layar penuh">
          <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
          <span>Tampilkan Layar Penuh</span>
        </button>
      </div>

      <!-- Container Elemen Peta -->
      <div id="dishubLeafletMap" class="leaflet-map-container" role="region" aria-label="Peta Interaktif Titik CCTV Dishub Banjarbaru"></div>
    </div>

    <!-- Info Bar / Legenda Ringkas & Penjelasan Status -->
    <div class="card-plain mb-5 p-3 bg-white border">
      <div class="row g-3 align-items-center">
        <!-- Legenda -->
        <div class="col-lg-7">
          <div class="d-flex flex-wrap align-items-center gap-3 small">
            <span class="fw-bold text-dark"><i class="bi bi-info-circle me-1 text-primary"></i>Legenda Peta:</span>
            <span class="d-inline-flex align-items-center gap-1">
              <span class="d-inline-block rounded-circle bg-orange" style="width: 12px; height: 12px; background-color: var(--dishub-orange);"></span>
              <span>Simpang (Zoom Jauh)</span>
            </span>
            <span class="d-inline-flex align-items-center gap-1">
              <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: var(--dishub-status-online);"></span>
              <span>Kamera Fase/Fixed Online</span>
            </span>
            <span class="d-inline-flex align-items-center gap-1">
              <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: #2563EB;"></span>
              <span>Kamera PTZ</span>
            </span>
            <span class="d-inline-flex align-items-center gap-1">
              <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: var(--dishub-status-offline);"></span>
              <span>Offline</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Divider Pemisah Antara Legenda dan Daftar Simpang -->
    <hr class="my-4 border-secondary-subtle">

    <!-- =======================================================================
         DAFTAR 4 PERSIMPANGAN & KAMERA DI BAWAH PETA
         ======================================================================= -->
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-1">Daftar Titik Persimpangan &amp; Kamera</h2>
          <p class="text-muted small mb-0">
            Total 4 persimpangan &bull; 17 kamera
          </p>
        </div>
      </div>

      <div class="row g-3">
        <?php foreach ($intersections as $loc): ?>
          <div class="col-lg-6">
            <div class="card h-100 border shadow-sm overflow-hidden">
              <div class="card-header bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                <div>
                  <h3 class="h6 fw-bold mb-0 text-dark">
                    <?= esc($loc['name']) ?>
                  </h3>
                  <div class="text-muted" style="font-size: 0.75rem;">
                    Pusat Koordinat: <?= esc($loc['center_lat']) ?>, <?= esc($loc['center_lng']) ?>
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.75rem;">
                    <?= esc($loc['installed_count']) ?> Kamera
                  </span>
                </div>
              </div>

              <!-- Daftar Kamera dalam Simpang -->
              <div class="card-body p-3 d-flex flex-column">
                <p class="text-muted small mb-2"><?= esc($loc['description']) ?></p>

                <div class="list-group list-group-flush border rounded-0 mb-3">
                  <?php foreach ($loc['cameras'] as $cam): ?>
                    <?php 
                      $isUnavailable = !$cam['is_installed'];
                      $isOnline = ($cam['status'] === 'online');
                    ?>
                    <div class="list-group-item p-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark small" style="font-size: 0.7rem;"><?= esc($cam['code']) ?></span>
                        <span class="fw-semibold text-dark small"><?= esc($cam['name']) ?></span>
                        <?php if ($cam['type'] === 'ptz'): ?>
                          <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.65rem;">
                            PTZ
                          </span>
                        <?php else: ?>
                          <span class="badge bg-success-subtle text-success border" style="font-size: 0.65rem;">
                            FIXED
                          </span>
                        <?php endif; ?>
                        <?php if (!empty($cam['direction'])): ?>
                          <span class="text-muted small" style="font-size: 0.72rem;">&bull; <?= esc($cam['direction']) ?></span>
                        <?php endif; ?>
                      </div>

                      <div class="d-flex align-items-center gap-2">
                        <?php if ($isUnavailable): ?>
                          <span class="cctv-status-badge status-unavailable" style="font-size: 0.68rem; padding: 2px 8px;">
                            Belum tersedia
                          </span>
                        <?php elseif ($isOnline): ?>
                          <span class="cctv-status-badge status-online" style="font-size: 0.68rem; padding: 2px 8px;">
                            Online
                          </span>
                        <?php else: ?>
                          <span class="cctv-status-badge status-offline" style="font-size: 0.68rem; padding: 2px 8px;">
                            Offline
                          </span>
                        <?php endif; ?>

                        <a href="<?= base_url('live-cctv?cctv=' . esc($cam['code'])) ?>" 
                           class="btn btn-outline-secondary btn-sm py-0 px-2 btn-detail-rounded" 
                           style="font-size: 0.72rem;"
                           title="Buka detail kamera <?= esc($cam['code']) ?>">
                          <i class="bi bi-info-circle me-1"></i>Detail
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                  <button type="button" 
                          class="btn-dishub-light btn-sm js-btn-open-modal py-1 px-3" 
                          data-location-id="<?= esc($loc['id']) ?>">
                    Buka Modal Simpang
                  </button>
                  <button type="button" 
                          class="btn-dishub-primary btn-sm js-btn-zoom-intersection py-1 px-3" 
                          data-lat="<?= esc($loc['center_lat']) ?>" 
                          data-lng="<?= esc($loc['center_lng']) ?>"
                          data-location-id="<?= esc($loc['id']) ?>">
                    <i class="bi bi-geo-alt me-1"></i>Fokus di Peta
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<!-- =======================================================================
     MODAL DETAIL PERSIMPANGAN (BOOTSTRAP 5)
     ======================================================================= -->
<div class="modal fade" id="intersectionModal" tabindex="-1" aria-labelledby="intersectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <div>
          <h5 class="modal-title fw-bold text-dark" id="intersectionModalLabel">Nama Simpang</h5>
          <p class="text-muted small mb-0" id="intersectionModalSubtitle">4 kamera di persimpangan ini</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-3">
        <div id="modalIntersectionDescription" class="text-muted small mb-3"></div>
        <h6 class="fw-bold mb-2 small text-uppercase text-secondary">Daftar Kamera di Simpang Ini:</h6>
        <div class="list-group border rounded-2" id="modalCameraList">
          <!-- Kamera akan dirender dinamis via JavaScript dari metadata terpusat -->
        </div>
      </div>
      <div class="modal-footer bg-light d-flex justify-content-between">
        <button type="button" class="btn-dishub-primary btn-sm" id="btnModalZoomToIntersection">
          <i class="bi bi-zoom-in me-1"></i>Lihat Titik Kamera di Peta
        </button>
        <button type="button" class="btn-dishub-light btn-sm" data-bs-dismiss="modal">
          Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Leaflet 1.9.4 Local Vendor JS -->
<script src="<?= base_url('assets/vendor/leaflet/leaflet.js') ?>"></script>

<!-- Metadata Peta (Aman di-encode tanpa script inline) -->
<script id="peta-metadata" type="application/json">
<?= json_encode([
  'intersections'     => $intersections,
  'cameras'           => $cameras,
  'focusCamera'       => $focus_camera,
  'focusIntersection' => $focus_intersection,
  'baseLiveCctvUrl'   => base_url('live-cctv?cctv='),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
<script src="<?= base_url('assets/js/pages/peta.js') ?>"></script>
<?= $this->endSection() ?>
