<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<!-- Header Halaman Dashboard Admin -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      Dashboard Monitoring Over Dimension
    </h1>
    <p class="text-muted small mb-0">
      Insight pemantauan mingguan (<?= esc($weekly['period_label']) ?>) dan ringkasan aktivitas CCTV Kota Banjarbaru
    </p>
  </div>
  <div>
    <span class="badge bg-white text-secondary border px-3 py-2 small shadow-sm">
      Periode: <strong><?= esc($weekly['period_label']) ?></strong>
    </span>
  </div>
</div>

<!-- 1. KARTU STATISTIK MINGGUAN (4 Kartu) -->
<div class="row g-3 mb-4">
  <!-- CCTV Terpantau (Urutan Pertama) -->
  <div class="col-xl-3 col-sm-6">
    <div class="admin-stat-card accent-cctv">
      <div class="admin-stat-icon icon-cctv">
        <i class="bi bi-camera-video" aria-hidden="true"></i>
      </div>
      <div class="admin-stat-num" style="color: #16A34A;"><?= esc($weekly['totals']['cctv_active_str']) ?></div>
      <div class="admin-stat-label">CCTV Terpantau</div>
      <div class="admin-stat-sub">16 online, 1 offline (4 Simpang)</div>
    </div>
  </div>

  <!-- Total Deteksi 7 Hari -->
  <div class="col-xl-3 col-sm-6">
    <div class="admin-stat-card accent-total">
      <div class="admin-stat-icon icon-total">
        <i class="bi bi-truck" aria-hidden="true"></i>
      </div>
      <div class="admin-stat-num"><?= esc($weekly['totals']['grand_total']) ?></div>
      <div class="admin-stat-label">Total Deteksi 7 Hari</div>
      <div class="admin-stat-sub">Akumulasi seluruh kendaraan terpantau</div>
    </div>
  </div>

  <!-- Over Dimension 7 Hari -->
  <div class="col-xl-3 col-sm-6">
    <div class="admin-stat-card accent-overdim">
      <div class="admin-stat-icon icon-overdim">
        <i class="bi bi-rulers" aria-hidden="true"></i>
      </div>
      <div class="admin-stat-num" style="color: var(--admin-orange);"><?= esc($weekly['totals']['over_dimension']) ?></div>
      <div class="admin-stat-label">Over Dimension 7 Hari</div>
      <div class="admin-stat-sub"><?= esc($weekly['percentages']['over_dimension']) ?>% dari total kendaraan</div>
    </div>
  </div>

  <!-- Truk Kontainer 7 Hari -->
  <div class="col-xl-3 col-sm-6">
    <div class="admin-stat-card accent-kontainer">
      <div class="admin-stat-icon icon-kontainer">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="2" y="5" width="20" height="14" rx="1.5" />
          <line x1="6" y1="5" x2="6" y2="19" />
          <line x1="10" y1="5" x2="10" y2="19" />
          <line x1="14" y1="5" x2="14" y2="19" />
          <line x1="18" y1="5" x2="18" y2="19" />
        </svg>
      </div>
      <div class="admin-stat-num" style="color: #2563EB;"><?= esc($weekly['totals']['kontainer']) ?></div>
      <div class="admin-stat-label">Truk Kontainer 7 Hari</div>
      <div class="admin-stat-sub"><?= esc($weekly['percentages']['kontainer']) ?>% dari total kendaraan</div>
    </div>
  </div>
</div>

<!-- 2. TIGA INSIGHT PENTING MINGGUAN -->
<div class="row g-3 mb-4">
  <!-- Insight 1: Hari OD Terbanyak -->
  <div class="col-lg-4 col-md-6">
    <div class="admin-insight-card">
      <div class="admin-insight-icon">
        <i class="bi bi-calendar-check" aria-hidden="true"></i>
      </div>
      <div>
        <div class="admin-insight-label">Hari OD Terbanyak</div>
        <div class="admin-insight-val"><?= esc($weekly['insights']['peak_day']['title']) ?></div>
        <div class="admin-insight-sub"><?= esc($weekly['insights']['peak_day']['subtitle']) ?></div>
      </div>
    </div>
  </div>

  <!-- Insight 2: Jam OD Terbanyak -->
  <div class="col-lg-4 col-md-6">
    <div class="admin-insight-card">
      <div class="admin-insight-icon">
        <i class="bi bi-clock-history" aria-hidden="true"></i>
      </div>
      <div>
        <div class="admin-insight-label">Jam OD Terbanyak</div>
        <div class="admin-insight-val"><?= esc($weekly['insights']['peak_hour']['title']) ?></div>
        <div class="admin-insight-sub"><?= esc($weekly['insights']['peak_hour']['subtitle']) ?></div>
      </div>
    </div>
  </div>

  <!-- Insight 3: CCTV OD Terbanyak -->
  <div class="col-lg-4 col-md-12">
    <div class="admin-insight-card">
      <div class="admin-insight-icon">
        <i class="bi bi-camera-video" aria-hidden="true"></i>
      </div>
      <div>
        <div class="admin-insight-label">CCTV OD Terbanyak</div>
        <div class="admin-insight-val"><?= esc($weekly['insights']['peak_cctv']['title']) ?></div>
        <div class="admin-insight-sub"><?= esc($weekly['insights']['peak_cctv']['subtitle']) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- 3. DUA GRAFIK INSIGHT MINGGUAN -->
<div class="row g-4 mb-4">
  <!-- Grafik Batang Akumulasi Over Dimension per Jam (7 Hari) -->
  <div class="col-lg-6">
    <div class="admin-card h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h6 fw-bold mb-0 text-dark">
            <i class="bi bi-bar-chart-fill text-warning me-2"></i>Akumulasi Over Dimension per Jam
          </h2>
          <small class="text-muted">Total kasus OD tiap jam selama periode 7 hari</small>
        </div>
      </div>

      <div style="position: relative; width: 100%; height: 290px;">
        <canvas id="adminHourlyODChart"></canvas>
      </div>

      <div class="text-muted fst-italic small mt-2" style="font-size: 0.72rem;">
        * Highlight navy menandai jam puncak (14.00-15.00 WITA) dengan akumulasi 17 kasus OD.
      </div>
    </div>
  </div>

  <!-- Grafik Garis Tren Over Dimension 7 Hari -->
  <div class="col-lg-6">
    <div class="admin-card h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h6 fw-bold mb-0 text-dark">
            <i class="bi bi-graph-up text-primary me-2"></i>Tren Over Dimension (7 Hari)
          </h2>
          <small class="text-muted">Pergerakan jumlah kasus OD harian (19 s/d 25 September 2026)</small>
        </div>
      </div>

      <div style="position: relative; width: 100%; height: 290px;">
        <canvas id="adminDailyODTrendChart"></canvas>
      </div>

      <div class="text-muted fst-italic small mt-2" style="font-size: 0.72rem;">
        * Hari terakhir (25 Sep) terhitung sampai pukul 15.42 WITA.
      </div>
    </div>
  </div>
</div>

<!-- 4. RINGKASAN HASIL DETEKSI MINGGUAN (HORIZONTAL BARS) -->
<div class="admin-card mb-4">
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-pie-chart-fill text-primary"></i>
    <h2 class="h6 fw-bold mb-0 text-dark">Ringkasan Hasil Deteksi Mingguan (Total 848 Kendaraan)</h2>
  </div>

  <div class="d-flex flex-column gap-3">
    <!-- Baris Normal -->
    <div>
      <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
        <span>Normal</span>
        <span><?= esc($weekly['totals']['normal']) ?> kendaraan (<?= esc($weekly['percentages']['normal']) ?>%)</span>
      </div>
      <div class="progress" style="height: 10px; background-color: #E2E8F0; border-radius: 4px;">
        <div class="progress-bar" role="progressbar" style="width: <?= esc($weekly['percentages']['normal']) ?>%; background-color: #16A34A;" aria-valuenow="<?= esc($weekly['percentages']['normal']) ?>" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>

    <!-- Baris Truk Kontainer -->
    <div>
      <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
        <span>Truk Kontainer</span>
        <span><?= esc($weekly['totals']['kontainer']) ?> kendaraan (<?= esc($weekly['percentages']['kontainer']) ?>%)</span>
      </div>
      <div class="progress" style="height: 10px; background-color: #E2E8F0; border-radius: 4px;">
        <div class="progress-bar" role="progressbar" style="width: <?= esc($weekly['percentages']['kontainer']) ?>%; background-color: #2563EB;" aria-valuenow="<?= esc($weekly['percentages']['kontainer']) ?>" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>

    <!-- Baris Over Dimension -->
    <div>
      <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
        <span>Over Dimension</span>
        <span><?= esc($weekly['totals']['over_dimension']) ?> kendaraan (<?= esc($weekly['percentages']['over_dimension']) ?>%)</span>
      </div>
      <div class="progress" style="height: 10px; background-color: #E2E8F0; border-radius: 4px;">
        <div class="progress-bar" role="progressbar" style="width: <?= esc($weekly['percentages']['over_dimension']) ?>%; background-color: var(--admin-orange);" aria-valuenow="<?= esc($weekly['percentages']['over_dimension']) ?>" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>
  </div>
</div>

<!-- 5. TABEL 5 HASIL DETEKSI TERBARU HARI INI -->
<div class="admin-card">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
    <div>
      <h2 class="h6 fw-bold mb-0 text-dark">
        <i class="bi bi-clock-history me-1 text-primary"></i>
        Hasil Deteksi Terbaru Hari Ini
      </h2>
      <small class="text-muted">5 aktivitas deteksi terkini pada tanggal 25 September 2026 (s/d 15.42 WITA)</small>
    </div>
    <a href="<?= base_url('admin/hasil-deteksi') ?>" class="btn-admin-outline btn-sm">
      <span>Semua Hasil Deteksi (128)</span>
      <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>

  <div class="table-responsive-admin">
    <table class="table-admin">
      <thead>
        <tr>
          <th>ID Deteksi</th>
          <th>Waktu (WITA)</th>
          <th>Titik CCTV</th>
          <th>Jenis Kendaraan</th>
          <th>Hasil Deteksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($latestDetections)): ?>
          <?php foreach ($latestDetections as $item): ?>
            <tr>
              <td>
                <span class="fw-bold font-monospace text-dark small"><?= esc($item['id']) ?></span>
              </td>
              <td>
                <span class="text-muted small"><?= esc($item['time']) ?></span>
              </td>
              <td>
                <div class="fw-semibold small text-dark"><?= esc($item['cctv_name']) ?></div>
                <div class="text-muted" style="font-size: 0.72rem;"><?= esc($item['cctv_code']) ?></div>
              </td>
              <td>
                <span class="small text-dark"><?= esc($item['vehicle_type']) ?></span>
              </td>
              <td>
                <?= view('admin/partials/detection_badge', ['result' => $item['detection_result']]) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-muted small">
              Belum ada data deteksi hari ini.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script id="admin-dashboard-data" type="application/json">
<?= json_encode([
  'hourly_od' => $weekly['hourly_od'],
  'od_trend'  => $weekly['od_trend'],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
<script src="<?= base_url('assets/js/admin/dashboard.js') ?>"></script>
<?= $this->endSection() ?>
