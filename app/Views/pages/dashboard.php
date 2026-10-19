<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="py-4">
  <div class="container">

    <!-- Header Dashboard Sederhana -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-2">
      <div>
        <h1 class="h3 fw-bold mb-1">
          Dashboard Monitoring Over Dimension
        </h1>
        <p class="text-muted small mb-0">
          Ringkasan deteksi dimensi kendaraan niaga di Kota Banjarbaru
        </p>
      </div>
      <div class="text-muted small">
        Data terakhir diperbarui: <?= esc($meta['demo_date']) ?> pukul <?= esc($meta['demo_time']) ?>
      </div>
    </div>

    <!-- 1. KARTU STATISTIK MODERN (5 Kartu: Ikon Box, Accent Stripe, Hover Halus) -->
    <div class="row g-3 mb-4">
      <!-- CCTV Terpantau (Urutan Pertama) -->
      <div class="col-xl col-md-4 col-sm-6">
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
      <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card-modern accent-total">
          <div class="stat-icon-box icon-total">
            <i class="bi bi-truck" aria-hidden="true"></i>
          </div>
          <div class="stat-num-modern"><?= esc($stats['total_detection']) ?></div>
          <div class="stat-label-modern">Total Deteksi</div>
          <div class="stat-sub-modern">Periode 1 hari</div>
        </div>
      </div>

      <!-- Over Dimension -->
      <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card-modern accent-overdim">
          <div class="stat-icon-box icon-overdim">
            <i class="bi bi-rulers" aria-hidden="true"></i>
          </div>
          <div class="stat-num-modern" style="color: var(--dishub-orange);"><?= esc($stats['over_dimension']) ?></div>
          <div class="stat-label-modern">Over Dimension</div>
          <div class="stat-sub-modern"><?= esc($stats['percentages']['over_dimension']) ?>% dari total</div>
        </div>
      </div>

      <!-- Normal -->
      <div class="col-xl col-md-6 col-sm-6">
        <div class="stat-card-modern accent-normal">
          <div class="stat-icon-box icon-normal">
            <i class="bi bi-check-circle" aria-hidden="true"></i>
          </div>
          <div class="stat-num-modern" style="color: var(--dishub-status-online);"><?= esc($stats['normal']) ?></div>
          <div class="stat-label-modern">Normal</div>
          <div class="stat-sub-modern"><?= esc($stats['percentages']['normal']) ?>% dari total</div>
        </div>
      </div>

      <!-- Kontainer -->
      <div class="col-xl col-md-6 col-sm-12">
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
          <div class="stat-sub-modern"><?= esc($stats['percentages']['kontainer']) ?>% dari total</div>
        </div>
      </div>
    </div>

    <!-- 2. RINGKASAN HASIL DETEKSI (Dengan Ikon pada Judul) -->
    <div class="card-plain mb-4">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-bar-chart-line text-warning fs-5" aria-hidden="true"></i>
        <h2 class="h5 fw-bold mb-0">Ringkasan Hasil Deteksi</h2>
      </div>

      <?php if ($stats['total_detection'] > 0): ?>
        <div class="d-flex flex-column gap-3">
          <!-- Baris Normal -->
          <div>
            <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
              <span>Normal</span>
              <span><?= esc($stats['normal']) ?> kendaraan (<?= esc($stats['percentages']['normal']) ?>%)</span>
            </div>
            <div class="progress-track-clean">
              <div class="progress-fill-clean" style="width: <?= esc($stats['percentages']['normal']) ?>%; background-color: var(--dishub-status-online);"></div>
            </div>
          </div>

          <!-- Baris Kontainer -->
          <div>
            <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
              <span>Truk Kontainer</span>
              <span><?= esc($stats['kontainer']) ?> kendaraan (<?= esc($stats['percentages']['kontainer']) ?>%)</span>
            </div>
            <div class="progress-track-clean">
              <div class="progress-fill-clean" style="width: <?= esc($stats['percentages']['kontainer']) ?>%; background-color: var(--dishub-data-kontainer);"></div>
            </div>
          </div>

          <!-- Baris Over Dimension -->
          <div>
            <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
              <span>Over Dimension</span>
              <span><?= esc($stats['over_dimension']) ?> kendaraan (<?= esc($stats['percentages']['over_dimension']) ?>%)</span>
            </div>
            <div class="progress-track-clean">
              <div class="progress-fill-clean" style="width: <?= esc($stats['percentages']['over_dimension']) ?>%; background-color: var(--dishub-orange);"></div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <p class="text-muted small mb-0">Belum ada data deteksi hari ini.</p>
      <?php endif; ?>
    </div>

    <!-- 3. ANALISIS DETEKSI HARI INI (Dengan Ikon pada Judul & 1 Legend Chart.js) -->
    <div class="card-plain">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-1">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-graph-up text-primary fs-5" aria-hidden="true"></i>
          <div>
            <h2 class="h5 fw-bold mb-0">Analisis Deteksi Hari Ini</h2>
            <small class="text-muted">Interval 00:00 s/d 23:00 WITA</small>
          </div>
        </div>
        <div class="text-muted small fst-italic">
          * Data tersedia sampai 15.42 WITA
        </div>
      </div>

      <!-- Kanvas Chart.js Tunggal -->
      <div style="position: relative; width: 100%; height: 360px;">
        <canvas id="dailyDetectionLineChart"></canvas>
      </div>
    </div>

    <!-- 4. GRAFIK BATANG VOLUME DETEKSI PER JAM (STACKED BAR KOTAK) -->
    <div class="card-plain mt-4">
      <div class="d-flex justify-content-end mb-2">
        <span class="text-muted small fst-italic">* Data tersedia sampai 15.42 WITA</span>
      </div>

      <!-- Kanvas Grafik Batang -->
      <div style="position: relative; width: 100%; height: 350px;">
        <canvas id="dailyDetectionBarChart"></canvas>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script id="visitor-dashboard-data" type="application/json">
<?= json_encode($timeline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
<script src="<?= base_url('assets/js/pages/dashboard.js') ?>"></script>
<?= $this->endSection() ?>
