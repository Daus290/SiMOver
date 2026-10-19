<?= $this->extend('layout/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
// Helper untuk membangun URL pagination yang mempertahankan periode, tab hasil, dan filter
$buildPageUrl = static function (int $targetPage) use ($period, $filters) {
    $params = [
        'period' => $period,
        'tab'    => 'hasil-deteksi',
        'page'   => $targetPage,
    ];
    if (!empty($filters['cctv']) && $filters['cctv'] !== 'all') {
        $params['cctv'] = $filters['cctv'];
    }
    if (!empty($filters['vehicle']) && $filters['vehicle'] !== 'all') {
        $params['vehicle'] = $filters['vehicle'];
    }
    if (!empty($filters['result']) && $filters['result'] !== 'all') {
        $params['result'] = $filters['result'];
    }
    return base_url('dashboard') . '?' . http_build_query($params);
};

// URL Reset Filter (membersihkan filter tapi mempertahankan periode dan tab)
$resetFilterUrl = base_url('dashboard') . '?' . http_build_query([
    'period' => $period,
    'tab'    => 'hasil-deteksi',
]);
?>

<div class="py-4">
  <div class="container">

    <!-- 1. HEADER DASHBOARD DENGAN SATU DROPDOWN PERIODE -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">
          Dashboard Monitoring Over Dimension
        </h1>
        <p class="text-muted small mb-0">
          Ringkasan deteksi dimensi kendaraan niaga di Kota Banjarbaru
        </p>
      </div>

      <!-- SATU Dropdown Periode & Keterangan Rentang Waktu WITA -->
      <div class="d-flex flex-column align-items-md-end gap-1">
        <div class="d-flex align-items-center gap-2">
          <label for="periodSelect" class="form-label small fw-semibold text-dark mb-0">Periode:</label>
          <select id="periodSelect" class="form-select form-select-sm period-select-dishub" style="width: auto;" aria-label="Pilih Periode Pengamatan">
            <option value="1" <?= ($period === 1) ? 'selected' : '' ?>>1 Hari</option>
            <option value="7" <?= ($period === 7) ? 'selected' : '' ?>>7 Hari</option>
            <option value="30" <?= ($period === 30) ? 'selected' : '' ?>>30 Hari</option>
          </select>
        </div>
        <div class="period-badge-info">
          <span><?= esc($period_date_range) ?></span>
          <span class="badge bg-secondary text-white ms-1" style="font-size: 0.68rem;">Data contoh</span>
        </div>
      </div>
    </div>

    <!-- 2. NAVIGASI DUA TAB BERSEBELAHAN -->
    <ul class="dashboard-nav-tabs" id="dashboardTabs" role="tablist">
      <li role="presentation">
        <button class="dashboard-tab-btn <?= ($active_tab === 'statistik') ? 'active' : '' ?>" 
                id="tab-btn-statistik" 
                type="button" 
                role="tab" 
                data-tab-target="statistik"
                aria-controls="panel-statistik" 
                aria-selected="<?= ($active_tab === 'statistik') ? 'true' : 'false' ?>">
          <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
          <span>Statistik</span>
        </button>
      </li>
      <li role="presentation">
        <button class="dashboard-tab-btn <?= ($active_tab === 'hasil-deteksi') ? 'active' : '' ?>" 
                id="tab-btn-hasil" 
                type="button" 
                role="tab" 
                data-tab-target="hasil-deteksi"
                aria-controls="panel-hasil-deteksi" 
                aria-selected="<?= ($active_tab === 'hasil-deteksi') ? 'true' : 'false' ?>">
          <i class="bi bi-table" aria-hidden="true"></i>
          <span>Hasil Deteksi</span>
        </button>
      </li>
    </ul>

    <!-- ====================================================================
         3. ISI TAB 1: STATISTIK (Ringkasan, 3 Insight, Distribusi & 2 Grafik)
         ==================================================================== -->
    <div id="panel-statistik" class="<?= ($active_tab === 'statistik') ? '' : 'd-none' ?>" role="tabpanel" aria-labelledby="tab-btn-statistik">

      <!-- 3.1 Lima Kartu Statistik Modern -->
      <div class="row g-3 mb-4">
        <!-- CCTV Terpantau (Urutan Pertama - Kondisi Live Saat Ini) -->
        <div class="col-xl col-md-4 col-sm-6">
          <div class="stat-card-modern accent-cctv">
            <div class="stat-icon-box icon-cctv">
              <i class="bi bi-camera-video" aria-hidden="true"></i>
            </div>
            <div class="stat-num-modern"><?= esc($stats['cctv_status_str']) ?></div>
            <div class="stat-label-modern">CCTV Terpantau</div>
            <div class="stat-sub-modern"><?= esc($stats['cctv_active']) ?> online, 1 offline (Saat ini)</div>
          </div>
        </div>

        <!-- Total Deteksi Periode -->
        <div class="col-xl col-md-4 col-sm-6">
          <div class="stat-card-modern accent-total">
            <div class="stat-icon-box icon-total">
              <i class="bi bi-truck" aria-hidden="true"></i>
            </div>
            <div class="stat-num-modern"><?= number_format($stats['total_detection'], 0, ',', '.') ?></div>
            <div class="stat-label-modern">Total Deteksi</div>
            <div class="stat-sub-modern">Periode <?= esc($period_info['title']) ?></div>
          </div>
        </div>

        <!-- Over Dimension -->
        <div class="col-xl col-md-4 col-sm-6">
          <div class="stat-card-modern accent-overdim">
            <div class="stat-icon-box icon-overdim">
              <i class="bi bi-rulers" aria-hidden="true"></i>
            </div>
            <div class="stat-num-modern" style="color: var(--dishub-orange);"><?= number_format($stats['over_dimension'], 0, ',', '.') ?></div>
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
            <div class="stat-num-modern" style="color: var(--dishub-status-online);"><?= number_format($stats['normal'], 0, ',', '.') ?></div>
            <div class="stat-label-modern">Normal</div>
            <div class="stat-sub-modern"><?= esc($stats['percentages']['normal']) ?>% dari total</div>
          </div>
        </div>

        <!-- Truk Kontainer -->
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
            <div class="stat-num-modern" style="color: var(--dishub-data-kontainer);"><?= number_format($stats['kontainer'], 0, ',', '.') ?></div>
            <div class="stat-label-modern">Truk Kontainer</div>
            <div class="stat-sub-modern"><?= esc($stats['percentages']['kontainer']) ?>% dari total</div>
          </div>
        </div>
      </div>

      <!-- 3.2 Tiga Kartu Insight Periode (Dipindahkan dari Admin) -->
      <div class="row g-3 mb-4">
        <!-- Insight 1: Hari OD Terbanyak -->
        <div class="col-lg-4 col-md-6">
          <div class="dashboard-insight-card">
            <div class="dashboard-insight-icon">
              <i class="bi bi-calendar-check" aria-hidden="true"></i>
            </div>
            <div>
              <div class="dashboard-insight-label">Hari OD Terbanyak</div>
              <div class="dashboard-insight-val"><?= esc($insights['peak_day']['title']) ?></div>
              <div class="dashboard-insight-sub"><?= esc($insights['peak_day']['subtitle']) ?></div>
            </div>
          </div>
        </div>

        <!-- Insight 2: Jam OD Terbanyak -->
        <div class="col-lg-4 col-md-6">
          <div class="dashboard-insight-card">
            <div class="dashboard-insight-icon">
              <i class="bi bi-clock-history" aria-hidden="true"></i>
            </div>
            <div>
              <div class="dashboard-insight-label">Jam OD Terbanyak</div>
              <div class="dashboard-insight-val"><?= esc($insights['peak_hour']['title']) ?></div>
              <div class="dashboard-insight-sub"><?= esc($insights['peak_hour']['subtitle']) ?></div>
            </div>
          </div>
        </div>

        <!-- Insight 3: Titik CCTV OD Terbanyak -->
        <div class="col-lg-4 col-md-12">
          <div class="dashboard-insight-card">
            <div class="dashboard-insight-icon">
              <i class="bi bi-camera-video" aria-hidden="true"></i>
            </div>
            <div>
              <div class="dashboard-insight-label">CCTV OD Terbanyak</div>
              <div class="dashboard-insight-val"><?= esc($insights['peak_cctv']['title']) ?></div>
              <div class="dashboard-insight-sub"><?= esc($insights['peak_cctv']['subtitle']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3.3 Ringkasan Distribusi Kategori Kendaraan -->
      <div class="card-plain mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-pie-chart text-warning fs-5" aria-hidden="true"></i>
          <h2 class="h5 fw-bold mb-0">Distribusi Hasil Deteksi (<?= esc($period_info['title']) ?>)</h2>
        </div>

        <?php if ($stats['total_detection'] > 0): ?>
          <div class="d-flex flex-column gap-3">
            <!-- Normal -->
            <div>
              <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
                <span>Normal</span>
                <span><?= number_format($stats['normal'], 0, ',', '.') ?> kendaraan (<?= esc($stats['percentages']['normal']) ?>%)</span>
              </div>
              <div class="progress-track-clean">
                <div class="progress-fill-clean" style="width: <?= esc($stats['percentages']['normal']) ?>%; background-color: var(--dishub-status-online);"></div>
              </div>
            </div>

            <!-- Kontainer -->
            <div>
              <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
                <span>Truk Kontainer</span>
                <span><?= number_format($stats['kontainer'], 0, ',', '.') ?> kendaraan (<?= esc($stats['percentages']['kontainer']) ?>%)</span>
              </div>
              <div class="progress-track-clean">
                <div class="progress-fill-clean" style="width: <?= esc($stats['percentages']['kontainer']) ?>%; background-color: var(--dishub-data-kontainer);"></div>
              </div>
            </div>

            <!-- Over Dimension -->
            <div>
              <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
                <span>Over Dimension</span>
                <span><?= number_format($stats['over_dimension'], 0, ',', '.') ?> kendaraan (<?= esc($stats['percentages']['over_dimension']) ?>%)</span>
              </div>
              <div class="progress-track-clean">
                <div class="progress-fill-clean" style="width: <?= esc($stats['percentages']['over_dimension']) ?>%; background-color: var(--dishub-orange);"></div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <p class="text-muted small mb-0">Belum ada data deteksi pada periode ini.</p>
        <?php endif; ?>
      </div>

      <!-- 3.4 Grafik Garis Utama (Line Chart) -->
      <div class="card-plain mb-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-1">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-graph-up text-primary fs-5" aria-hidden="true"></i>
            <div>
              <h2 class="h5 fw-bold mb-0"><?= esc($chart_data['title_line']) ?></h2>
              <small class="text-muted"><?= esc($chart_data['subtitle_line']) ?></small>
            </div>
          </div>
          <div class="text-muted small fst-italic">
            <?= esc($chart_data['note']) ?>
          </div>
        </div>

        <div style="position: relative; width: 100%; height: 360px;">
          <canvas id="dailyDetectionLineChart"></canvas>
        </div>
      </div>

      <!-- 3.5 Grafik Batang Bertumpuk (Stacked Bar Chart) -->
      <div class="card-plain">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-1">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-fill text-success fs-5" aria-hidden="true"></i>
            <div>
              <h2 class="h5 fw-bold mb-0"><?= esc($chart_data['title_bar']) ?></h2>
              <small class="text-muted"><?= esc($chart_data['subtitle_bar']) ?></small>
            </div>
          </div>
          <div class="text-muted small fst-italic">
            <?= esc($chart_data['note']) ?>
          </div>
        </div>

        <div style="position: relative; width: 100%; height: 350px;">
          <canvas id="dailyDetectionBarChart"></canvas>
        </div>
      </div>

    </div>

    <!-- ====================================================================
         4. ISI TAB 2: HASIL DETEKSI (Tabel Admin Lama Dipindahkan)
         ==================================================================== -->
    <div id="panel-hasil-deteksi" class="<?= ($active_tab === 'hasil-deteksi') ? '' : 'd-none' ?>" role="tabpanel" aria-labelledby="tab-btn-hasil">

      <!-- 4.1 Filter Pencarian Tabel -->
      <div class="dashboard-filter-card mb-4">
        <form action="<?= base_url('dashboard') ?>" method="get" class="row g-3 align-items-end">
          <!-- Parameter State Halaman -->
          <input type="hidden" name="period" value="<?= esc($period) ?>">
          <input type="hidden" name="tab" value="hasil-deteksi">

          <!-- Filter CCTV -->
          <div class="col-lg-3 col-md-6">
            <label for="filterCctv" class="form-label small fw-semibold text-dark mb-1">
              Titik CCTV
            </label>
            <select id="filterCctv" name="cctv" class="form-select form-select-sm">
              <?php foreach ($filterOptions['cctv'] as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= ($filters['cctv'] === $key) ? 'selected' : '' ?>>
                  <?= esc($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Filter Jenis Kendaraan -->
          <div class="col-lg-3 col-md-6">
            <label for="filterVehicle" class="form-label small fw-semibold text-dark mb-1">
              Jenis Kendaraan
            </label>
            <select id="filterVehicle" name="vehicle" class="form-select form-select-sm">
              <?php foreach ($filterOptions['vehicle'] as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= ($filters['vehicle'] === $key) ? 'selected' : '' ?>>
                  <?= esc($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Filter Hasil Deteksi -->
          <div class="col-lg-3 col-md-6">
            <label for="filterResult" class="form-label small fw-semibold text-dark mb-1">
              Hasil Deteksi
            </label>
            <select id="filterResult" name="result" class="form-select form-select-sm">
              <?php foreach ($filterOptions['result'] as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= ($filters['result'] === $key) ? 'selected' : '' ?>>
                  <?= esc($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Tombol Terapkan & Reset -->
          <div class="col-lg-3 col-md-6">
            <div class="d-flex gap-2">
              <button type="submit" class="btn-dishub-primary flex-grow-1">
                <i class="bi bi-funnel"></i>
                <span>Terapkan</span>
              </button>
              <?php if ($hasActiveFilter): ?>
                <a href="<?= esc($resetFilterUrl) ?>" class="btn-dishub-outline" title="Bersihkan Filter">
                  <i class="bi bi-arrow-counterclockwise"></i>
                  <span>Reset</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>

      <!-- 4.2 Kontrol Jumlah & Indikator Data Terfilter -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
        <div class="text-muted small">
          <?php if ($table['total'] > 0): ?>
            Menampilkan <strong><?= esc($table['from']) ?> - <?= esc($table['to']) ?></strong> dari <strong><?= number_format($table['total'], 0, ',', '.') ?></strong> data deteksi
          <?php else: ?>
            Tidak ada data yang ditemukan
          <?php endif; ?>

          <?php if ($hasActiveFilter): ?>
            <span class="badge-filter-active ms-2">Filter Aktif</span>
            <span class="text-muted ms-1 small">(Total periode: <?= number_format($table['global_total'], 0, ',', '.') ?> data)</span>
          <?php endif; ?>
        </div>

        <div class="text-muted small">
          Halaman <strong><?= esc($table['page']) ?></strong> dari <strong><?= esc($table['total_pages']) ?></strong>
        </div>
      </div>

      <!-- 4.3 Tabel Data Deteksi Individu (Gaya Admin Lama) -->
      <div class="table-responsive-dashboard mb-4">
        <table class="table-detection">
          <thead>
            <tr>
              <th style="width: 170px;">ID Deteksi</th>
              <th style="width: 150px;">Waktu (WITA)</th>
              <th>Titik CCTV</th>
              <th>Jenis Kendaraan</th>
              <th style="width: 170px;">Hasil Deteksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($table['data'])): ?>
              <?php foreach ($table['data'] as $row): ?>
                <tr>
                  <td>
                    <span class="fw-bold font-monospace text-dark small"><?= esc($row['id']) ?></span>
                  </td>
                  <td>
                    <div>
                      <div class="fw-semibold text-dark small"><?= esc($row['date_formatted']) ?></div>
                      <div class="text-muted" style="font-size: 0.75rem;"><?= esc($row['time']) ?></div>
                    </div>
                  </td>
                  <td>
                    <div class="fw-semibold small text-dark"><?= esc($row['cctv_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= esc($row['cctv_code']) ?></div>
                  </td>
                  <td>
                    <span class="small text-dark"><?= esc($row['vehicle_type']) ?></span>
                  </td>
                  <td>
                    <?= view('partials/detection_badge', ['result' => $row['detection_result']]) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center py-5">
                  <div class="text-muted mb-2">
                    <i class="bi bi-search fs-2 opacity-50"></i>
                  </div>
                  <h6 class="fw-bold text-dark mb-1">Tidak ada hasil deteksi yang sesuai filter</h6>
                  <p class="text-muted small mb-3">Coba ubah kriteria pencarian atau bersihkan filter tabel.</p>
                  <a href="<?= esc($resetFilterUrl) ?>" class="btn-dishub-outline">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                  </a>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- 4.4 Navigasi Paginasi Ringkas (10 Record per Halaman) -->
      <?php if ($table['total_pages'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="text-muted small">
            Menampilkan 10 data per halaman
          </div>

          <nav aria-label="Navigasi Halaman Deteksi">
            <div class="dashboard-pagination">
              <!-- Tombol Sebelumnya -->
              <a 
                href="<?= $table['has_prev'] ? esc($buildPageUrl($table['page'] - 1)) : '#' ?>" 
                class="dashboard-page-link <?= !$table['has_prev'] ? 'disabled' : '' ?>"
                aria-label="Halaman Sebelumnya"
                <?= !$table['has_prev'] ? 'tabindex="-1" aria-disabled="true"' : '' ?>
              >
                <i class="bi bi-chevron-left small"></i>
              </a>

              <!-- Daftar Nomor Halaman dengan Jendela Ringkas -->
              <?php
                $startPage = max(1, $table['page'] - 2);
                $endPage = min($table['total_pages'], $table['page'] + 2);

                if ($startPage > 1) {
                    echo '<a href="' . esc($buildPageUrl(1)) . '" class="dashboard-page-link">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="px-1 text-muted small">...</span>';
                    }
                }

                for ($p = $startPage; $p <= $endPage; $p++) {
                    $isActive = ($p === $table['page']);
                    echo '<a href="' . esc($buildPageUrl($p)) . '" class="dashboard-page-link ' . ($isActive ? 'active' : '') . '" ' . ($isActive ? 'aria-current="page"' : '') . '>' . $p . '</a>';
                }

                if ($endPage < $table['total_pages']) {
                    if ($endPage < $table['total_pages'] - 1) {
                        echo '<span class="px-1 text-muted small">...</span>';
                    }
                    echo '<a href="' . esc($buildPageUrl($table['total_pages'])) . '" class="dashboard-page-link">' . $table['total_pages'] . '</a>';
                }
              ?>

              <!-- Tombol Selanjutnya -->
              <a 
                href="<?= $table['has_next'] ? esc($buildPageUrl($table['page'] + 1)) : '#' ?>" 
                class="dashboard-page-link <?= !$table['has_next'] ? 'disabled' : '' ?>"
                aria-label="Halaman Selanjutnya"
                <?= !$table['has_next'] ? 'tabindex="-1" aria-disabled="true"' : '' ?>
              >
                <i class="bi bi-chevron-right small"></i>
              </a>
            </div>
          </nav>
        </div>
      <?php endif; ?>

    </div>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script id="dashboard-json-data" type="application/json">
<?= json_encode([
    'period'     => $period,
    'active_tab' => $active_tab,
    'chart'      => $chart_data,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
<script src="<?= base_url('assets/js/pages/dashboard.js') ?>"></script>
<?= $this->endSection() ?>
