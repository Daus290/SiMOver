<?php
/**
 * CCTV Card Partial
 * Mendukung kartu tayangan nyata ATCS Banjarmasin dan seluruh kartu Vivotek CCTV Banjarbaru
 */
$status        = $cctv['status'] ?? 'offline';
$isOnline      = ($status === 'online');
$isOffline     = ($status === 'offline');
$isUnavailable = ($status === 'unavailable' || ($cctv['availability'] ?? '') === 'unavailable');
$isUnconnected = ($status === 'unconnected');
$isCached      = ($status === 'cached');
$hasStream     = !empty($cctv['stream_url']) && !empty($cctv['is_live_capable']);
$isVivotek     = str_starts_with($cctv['source'] ?? '', 'vivotek');

// Kamera adalah placeholder jika tidak memiliki stream aktif atau ditandai placeholder
$isPlaceholder = !$hasStream && (!empty($cctv['is_placeholder']) || empty($cctv['image_thumb']));
$cardUrl       = base_url('live-cctv?cctv=' . esc($cctv['code']));
$thumbUrl      = !empty($cctv['image_thumb']) ? base_url(esc($cctv['image_thumb'])) : null;
$camType       = $cctv['type'] ?? 'fixed';
$isPtz         = ($camType === 'ptz');
$colClass      = $col_class ?? 'col-lg-4 col-md-6';
$hideRedundantBadges = $hide_redundant_badges ?? false;
$showFavorite  = $show_favorite ?? false;
?>
<div class="<?= esc($colClass) ?> cctv-item-card" 
     data-code="<?= esc($cctv['code']) ?>" 
     data-name="<?= esc($cctv['name']) ?>" 
     data-location="<?= esc($cctv['location'] ?? $cctv['intersection_name'] ?? '') ?>" 
     data-category="<?= esc($cctv['category'] ?? '') ?>"
     data-source="<?= esc($cctv['source'] ?? 'atcs_banjarmasin') ?>"
     data-intersection="<?= esc($cctv['location_id'] ?? '') ?>">
  <div class="cctv-card-simple">
    <!-- Thumbnail Area -->
    <div class="cctv-thumb-wrap">
      <?php if (!$isPlaceholder && $thumbUrl): ?>
        <!-- Thumbnail Cuplikan CCTV Asli / Relay / Cache -->
        <img src="<?= $thumbUrl ?>" 
             alt="Cuplikan CCTV <?= esc($cctv['name']) ?>" 
             loading="lazy"
             onerror="this.onerror=null; this.classList.add('img-fallback');">
        
        <?php if ($hasStream): ?>
          <span class="cctv-demo-tag" style="background: rgba(16, 185, 129, 0.9); color: #fff;"><i class="bi bi-broadcast me-1"></i>LIVE</span>
        <?php elseif ($isCached): ?>
          <span class="cctv-demo-tag" style="background: rgba(71, 85, 105, 0.9); color: #fff;"><i class="bi bi-clock-history me-1"></i>Gambar Tersimpan</span>
        <?php elseif ($isVivotek): ?>
          <span class="cctv-demo-tag"><?= esc($cctv['source_note'] ?? 'Vivotek Banjarbaru') ?></span>
        <?php else: ?>
          <span class="cctv-demo-tag">Cuplikan CCTV</span>
        <?php endif; ?>
      <?php else: ?>
        <!-- Placeholder Bersih CCTV Banjarbaru -->
        <div class="cctv-thumb-placeholder">
          <div class="placeholder-icon-wrap">
            <i class="bi <?= $isPtz ? 'bi-arrows-move' : ($isUnavailable ? 'bi-dash-circle' : ($isOffline ? 'bi-exclamation-triangle' : ($isUnconnected ? 'bi-link-45deg' : 'bi-camera-video'))) ?>" aria-hidden="true"></i>
          </div>
          <div class="placeholder-cam-title fw-bold text-white small">
            <?= esc($cctv['code']) ?>
          </div>
          <div class="placeholder-loc-title text-white-50" style="font-size: 0.72rem;">
            <?= esc($cctv['intersection_name'] ?? $cctv['location'] ?? 'Dishub Kota Banjarbaru') ?>
          </div>
        </div>

        <?php if ($isUnavailable): ?>
          <span class="cctv-demo-tag tag-unavailable">Belum Tersedia</span>
        <?php elseif ($isOffline): ?>
          <span class="cctv-demo-tag tag-unconnected" style="background: rgba(239, 68, 68, 0.85); color: #fff;">Tidak Aktif</span>
        <?php elseif ($isUnconnected): ?>
          <span class="cctv-demo-tag tag-unconnected" style="background: rgba(245, 158, 11, 0.85); color: #fff;">Belum Terhubung</span>
        <?php elseif ($isVivotek): ?>
          <span class="cctv-demo-tag">Vivotek Banjarbaru</span>
        <?php else: ?>
          <span class="cctv-demo-tag tag-unconnected">Belum Terhubung</span>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Kode CCTV Tag -->
      <span class="cctv-code-tag"><?= esc($cctv['code']) ?></span>

      <?php if (!empty($showFavorite)): ?>
      <!-- Tombol Favorit (Bintang) -->
      <button type="button" 
              class="cctv-fav-toggle cctv-fav-btn" 
              data-cctv-code="<?= esc($cctv['code']) ?>" 
              data-cctv-name="<?= esc($cctv['name']) ?>"
              title="Tandai favorit"
              aria-pressed="false"
              aria-label="Tandai favorit <?= esc($cctv['name']) ?>">
        <i class="bi bi-star"></i>
      </button>
      <?php endif; ?>

      <!-- Overlay Offline jika kamera stream nyata tidak aktif -->
      <?php if (!$isPlaceholder && !$isOnline && !$isCached): ?>
        <div class="cctv-offline-box">
          <h5 class="mb-1">Tayangan tidak tersedia</h5>
          <p class="small text-white-50">Perangkat kamera di titik ini saat ini tidak aktif.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Informasi Kartu -->
    <div class="cctv-card-info">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-1">
        <h5 class="cctv-title mb-0"><?= esc($cctv['name']) ?></h5>
        <?php if (!empty($hideRedundantBadges)): ?>
          <?php if ($isUnavailable): ?>
            <span class="cctv-status-badge status-unavailable">Belum Tersedia</span>
          <?php elseif ($isUnconnected): ?>
            <span class="cctv-status-badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">Belum Terhubung</span>
          <?php elseif ($isVivotek && !$hasStream && !$isOffline && !$isCached): ?>
            <span class="cctv-status-badge status-online">Jaringan Kantor</span>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($isUnavailable): ?>
            <span class="cctv-status-badge status-unavailable">Belum Tersedia</span>
          <?php elseif ($isOffline): ?>
            <span class="cctv-status-badge status-offline">Tidak Aktif</span>
          <?php elseif ($isUnconnected): ?>
            <span class="cctv-status-badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">Belum Terhubung</span>
          <?php elseif ($hasStream): ?>
            <span class="cctv-status-badge status-online">Live Streaming</span>
          <?php elseif ($isCached): ?>
            <span class="cctv-status-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">Gambar Tersimpan</span>
          <?php elseif ($isVivotek): ?>
            <span class="cctv-status-badge status-online">Jaringan Kantor</span>
          <?php else: ?>
            <span class="cctv-status-badge status-offline">Tidak Aktif</span>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="cctv-location-text">
        <?= esc($cctv['location'] ?? $cctv['intersection_name'] ?? '') ?>
        <?php if (!empty($cctv['direction'])): ?>
          <span class="text-muted d-block small mt-1">&bull; Arah: <?= esc($cctv['direction']) ?></span>
        <?php endif; ?>
      </div>

      <div class="mt-auto pt-2">
        <?php if ($hasStream): ?>
          <a href="<?= $cardUrl ?>" class="btn-dishub-primary w-100 justify-content-center">
            <i class="bi bi-play-circle" aria-hidden="true"></i>Lihat Tayangan
          </a>
        <?php else: ?>
          <a href="<?= $cardUrl ?>" class="btn-dishub-light w-100 justify-content-center">
            <i class="bi bi-info-circle" aria-hidden="true"></i>Lihat Detail
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
