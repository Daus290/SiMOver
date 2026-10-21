<?= $this->extend('layout/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
/** @var array $dash Data dari AiDashboardService::build() */
$params  = $dash['params'];
$stats   = $dash['stats'];
$table   = $dash['table'];
$chart   = $dash['chart'];
$status  = $dash['status'];
$source  = $dash['source'];
$fmt     = static fn ($n) => number_format((int) $n, 0, ',', '.');
$fmtTime = static fn (?string $t) => $t ? date('d M Y H:i:s', strtotime($t)) . ' WITA' : 'Belum ada';

$statusBadgeClass = match ($status['state']) {
    'aktif'      => 'bg-success text-white',
    'menyambung' => 'bg-warning text-dark',
    default      => 'bg-secondary text-white',
};

// URL tabel mempertahankan sumber, periode, tab hasil dan filter aktif
$buildPageUrl = static function (int $targetPage) use ($params) {
    $query = ['source' => $params['source'], 'period' => $params['period'], 'tab' => 'hasil-deteksi', 'page' => $targetPage];
    if ($params['class'] !== 'all') {
        $query['class'] = $params['class'];
    }
    if ($params['od'] !== 'all') {
        $query['od'] = $params['od'];
    }
    if (!empty($params['camera']) && $params['camera'] !== 'all') {
        $query['camera'] = $params['camera'];
    }

    return base_url('dashboard') . '?' . http_build_query($query);
};
$resetFilterUrl = base_url('dashboard') . '?' . http_build_query(['source' => $params['source'], 'period' => $params['period'], 'tab' => 'hasil-deteksi']);
?>

<div class="py-4">
  <div class="container">

    <!-- 1. HEADER: SUMBER DATA & SATU DROPDOWN PERIODE -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">Dashboard Monitoring Over Dimension</h1>
        <p class="text-muted small mb-0">
          Hasil model klasifikasi truk 5 kelas pada kamera uji ATCS Banjarmasin. Bukan hasil seluruh CCTV Kota Banjarbaru.
        </p>
      </div>

      <div class="d-flex flex-column align-items-md-end gap-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <label for="sourceSelect" class="form-label small fw-semibold text-dark mb-0">Sumber:</label>
          <select id="sourceSelect" class="form-select form-select-sm period-select-dishub" style="width: auto;" aria-label="Pilih Sumber Data">
            <?php foreach ($dash['source_options'] as $key => $label): ?>
              <option value="<?= esc($key) ?>" <?= $params['source'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
          </select>
          <label for="periodSelect" class="form-label small fw-semibold text-dark mb-0 ms-md-2">Periode:</label>
          <select id="periodSelect" class="form-select form-select-sm period-select-dishub" style="width: auto;" aria-label="Pilih Periode Pengamatan">
            <option value="1" <?= $params['period'] === 1 ? 'selected' : '' ?>>1 Hari</option>
            <option value="7" <?= $params['period'] === 7 ? 'selected' : '' ?>>7 Hari</option>
            <option value="30" <?= $params['period'] === 30 ? 'selected' : '' ?>>30 Hari</option>
          </select>
        </div>
        <div class="period-badge-info">
          <span id="periodDateRangeText"><?= esc($dash['period_info']['range']) ?></span>
        </div>
      </div>
    </div>

    <!-- Banner sumber data & status pengirim (berdasarkan heartbeat aktual) -->
    <div class="alert alert-light border py-2 px-3 mb-4 shadow-sm" style="border-radius: 8px;">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2 small flex-wrap">
          <?php if ($source['official']): ?>
            <span class="badge bg-primary-subtle text-primary border">Sumber resmi</span>
          <?php else: ?>
            <span class="badge bg-warning-subtle text-dark border">Bukan core Rizkiya</span>
          <?php endif; ?>
          <span class="fw-semibold text-dark"><?= esc($source['label']) ?></span>
          <span class="text-muted">&bull; Kamera pantau: 2 Titik ATCS (Jl. Gubernur Soebardjo & Simpang Sultan Adam)</span>
        </div>
        <div class="d-flex align-items-center gap-2 small">
          <span class="text-muted">Status pengirim:</span>
          <span class="badge <?= $statusBadgeClass ?>" id="badgeSourceStatus"><?= esc($status['label']) ?></span>
          <span class="text-muted d-none d-sm-inline" id="dashboardLiveUpdateNotice">Pembaruan otomatis tiap 5 detik</span>
        </div>
      </div>
      <div class="text-muted mt-1" style="font-size: 0.78rem;"><?= esc($source['description']) ?></div>
    </div>

    <!-- 2. NAVIGASI DUA TAB BERSEBELAHAN -->
    <ul class="dashboard-nav-tabs" id="dashboardTabs" role="tablist">
      <li role="presentation">
        <button class="dashboard-tab-btn <?= $params['tab'] === 'statistik' ? 'active' : '' ?>"
                id="tab-btn-statistik" type="button" role="tab" data-tab-target="statistik"
                aria-controls="panel-statistik" aria-selected="<?= $params['tab'] === 'statistik' ? 'true' : 'false' ?>">
          <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
          <span>Statistik</span>
        </button>
      </li>
      <li role="presentation">
        <button class="dashboard-tab-btn <?= $params['tab'] === 'hasil-deteksi' ? 'active' : '' ?>"
                id="tab-btn-hasil" type="button" role="tab" data-tab-target="hasil-deteksi"
                aria-controls="panel-hasil-deteksi" aria-selected="<?= $params['tab'] === 'hasil-deteksi' ? 'true' : 'false' ?>">
          <i class="bi bi-table" aria-hidden="true"></i>
          <span>Hasil Deteksi</span>
        </button>
      </li>
    </ul>

    <!-- ====================================================================
         3. TAB STATISTIK
         ==================================================================== -->
    <div id="panel-statistik" class="<?= $params['tab'] === 'statistik' ? '' : 'd-none' ?>" role="tabpanel" aria-labelledby="tab-btn-statistik">

      <!-- 3.1 Kartu ringkasan -->
      <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card-modern accent-cctv">
            <div class="stat-icon-box icon-cctv"><i class="bi bi-broadcast" aria-hidden="true"></i></div>
            <div class="stat-num-modern" style="font-size: 1.15rem;" id="statSourceState"><?= esc($status['label']) ?></div>
            <div class="stat-label-modern">Status Pengirim</div>
            <div class="stat-sub-modern" id="statSourceHeartbeat">Heartbeat terakhir: <?= esc($fmtTime($status['last_heartbeat'])) ?></div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card-modern accent-total">
            <div class="stat-icon-box icon-total"><i class="bi bi-truck" aria-hidden="true"></i></div>
            <div class="stat-num-modern" id="statTotal"><?= $fmt($stats['total']) ?></div>
            <div class="stat-label-modern">Hasil Tercatat</div>
            <div class="stat-sub-modern">Satuan: <?= esc($source['unit_label']) ?> &bull; <?= esc($dash['period_info']['title']) ?></div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card-modern accent-overdim">
            <div class="stat-icon-box icon-overdim"><i class="bi bi-rulers" aria-hidden="true"></i></div>
            <div class="stat-num-modern" id="statOd" style="color: var(--dishub-orange);"><?= $fmt($stats['od_count']) ?></div>
            <div class="stat-label-modern">Indikasi Over Dimension</div>
            <div class="stat-sub-modern" id="statOdPct"><?= esc($stats['od_pct']) ?>% dari hasil tercatat (prediksi model)</div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="stat-card-modern accent-normal">
            <div class="stat-icon-box icon-normal"><i class="bi bi-clock-history" aria-hidden="true"></i></div>
            <div class="stat-num-modern" style="font-size: 1.15rem;" id="statLastReceived"><?= esc($fmtTime($stats['last_received'])) ?></div>
            <div class="stat-label-modern">Hasil Terakhir Diterima</div>
            <div class="stat-sub-modern">Waktu diterima server pada periode ini</div>
          </div>
        </div>
      </div>

      <!-- 3.2 Insight periode -->
      <div class="row g-3 mb-4">
        <?php foreach ([
            'peak_day'  => ['icon' => 'bi-calendar-check', 'label' => 'Hari OD Terbanyak'],
            'peak_hour' => ['icon' => 'bi-clock-history',  'label' => 'Jam OD Terbanyak'],
            'top_class' => ['icon' => 'bi-diagram-3',      'label' => 'Kelas Terbanyak'],
        ] as $key => $meta): ?>
          <div class="col-lg-4 col-md-6">
            <div class="dashboard-insight-card">
              <div class="dashboard-insight-icon"><i class="bi <?= $meta['icon'] ?>" aria-hidden="true"></i></div>
              <div>
                <div class="dashboard-insight-label"><?= esc($meta['label']) ?></div>
                <div class="dashboard-insight-val" id="insight-<?= $key ?>-title"><?= esc($dash['insights'][$key]['title']) ?></div>
                <div class="dashboard-insight-sub" id="insight-<?= $key ?>-sub"><?= esc($dash['insights'][$key]['subtitle']) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>


      <!-- 3.3 Distribusi 5 kelas model -->
      <div class="card-plain mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-pie-chart text-warning fs-5" aria-hidden="true"></i>
            <h2 class="h5 fw-bold mb-0">Distribusi 5 Kelas Model (<?= esc($dash['period_info']['title']) ?>)</h2>
          </div>
          <span class="text-muted small">Persentase dari <?= esc($stats['denominator']) ?></span>
        </div>

        <p class="text-muted small mb-0 <?= $stats['total'] > 0 ? 'd-none' : '' ?>" id="distEmptyNotice">
          Belum ada hasil tercatat dari sumber ini pada periode terpilih.
        </p>
        <div class="d-flex flex-column gap-3 <?= $stats['total'] > 0 ? '' : 'd-none' ?>" id="distributionBarsContainer">
          <?php foreach ($stats['classes'] as $class): ?>
            <div>
              <div class="d-flex justify-content-between text-dark small fw-semibold mb-1">
                <span><?= esc($class['label']) ?> <code class="text-muted fw-normal ms-1"><?= esc($class['name']) ?></code></span>
                <span id="distVal-<?= esc($class['name']) ?>"><?= $fmt($class['count']) ?> hasil (<?= esc($class['pct']) ?>%)</span>
              </div>
              <div class="progress-track-clean">
                <div class="progress-fill-clean" id="distBar-<?= esc($class['name']) ?>" style="width: <?= esc($class['pct']) ?>%; background-color: <?= esc($class['color']) ?>;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 3.4 Grafik garis -->
      <div class="card-plain mb-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-1">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-graph-up text-primary fs-5" aria-hidden="true"></i>
            <h2 class="h5 fw-bold mb-0"><?= esc($chart['title_line']) ?></h2>
          </div>
          <div class="text-muted small fst-italic" id="chartNoteLine"><?= esc($chart['note']) ?></div>
        </div>
        <div style="position: relative; width: 100%; height: 360px;">
          <canvas id="dailyDetectionLineChart"></canvas>
        </div>
      </div>

      <!-- 3.5 Grafik batang bertumpuk -->
      <div class="card-plain">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-1">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-fill text-success fs-5" aria-hidden="true"></i>
            <h2 class="h5 fw-bold mb-0"><?= esc($chart['title_bar']) ?></h2>
          </div>
          <div class="text-muted small fst-italic"><?= esc($chart['note']) ?></div>
        </div>
        <div style="position: relative; width: 100%; height: 350px;">
          <canvas id="dailyDetectionBarChart"></canvas>
        </div>
      </div>
    </div>

    <!-- ====================================================================
         4. TAB HASIL DETEKSI
         ==================================================================== -->
    <div id="panel-hasil-deteksi" class="<?= $params['tab'] === 'hasil-deteksi' ? '' : 'd-none' ?>" role="tabpanel" aria-labelledby="tab-btn-hasil">

      <div class="dashboard-filter-card mb-4">
        <form action="<?= base_url('dashboard') ?>" method="get" class="row g-3 align-items-end">
          <input type="hidden" name="source" value="<?= esc($params['source']) ?>">
          <input type="hidden" name="period" value="<?= esc($params['period']) ?>">
          <input type="hidden" name="tab" value="hasil-deteksi">

          <div class="col-lg-3 col-md-6">
            <label for="filterCamera" class="form-label small fw-semibold text-dark mb-1">Titik Kamera</label>
            <select id="filterCamera" name="camera" class="form-select form-select-sm">
              <?php foreach ($dash['filter_options']['camera'] as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= ($params['camera'] ?? 'all') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-lg-3 col-md-6">
            <label for="filterClass" class="form-label small fw-semibold text-dark mb-1">Kelas Model</label>
            <select id="filterClass" name="class" class="form-select form-select-sm">
              <?php foreach ($dash['filter_options']['class'] as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= $params['class'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-lg-3 col-md-6">
            <label for="filterOd" class="form-label small fw-semibold text-dark mb-1">Indikasi Model</label>
            <select id="filterOd" name="od" class="form-select form-select-sm">
              <?php foreach ($dash['filter_options']['od'] as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= $params['od'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-lg-3 col-md-6">
            <div class="d-flex gap-2">
              <button type="submit" class="btn-dishub-primary flex-grow-1">
                <i class="bi bi-funnel" aria-hidden="true"></i><span>Terapkan</span>
              </button>
              <?php if ($table['has_active_filter']): ?>
                <a href="<?= esc($resetFilterUrl) ?>" class="btn-dishub-outline" title="Bersihkan Filter">
                  <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span>Reset</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>

      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
        <div class="text-muted small" id="tableSummaryText">
          <?php if ($table['total'] > 0): ?>
            Menampilkan <strong><?= esc($table['from']) ?> - <?= esc($table['to']) ?></strong> dari <strong><?= $fmt($table['total']) ?></strong> hasil terbaru
            <?php if (!empty($table['real_total']) && $table['real_total'] > $table['total']): ?>
              <span class="text-muted ms-1 small" title="Tabel dibatasi maksimal 20 halaman terbaru agar performa sistem tetap ringan">(dibatasi 20 halaman, total riwayat: <?= $fmt($table['real_total']) ?>)</span>
            <?php endif; ?>
          <?php else: ?>
            Tidak ada hasil yang ditemukan
          <?php endif; ?>
          <?php if ($table['has_active_filter']): ?>
            <span class="badge-filter-active ms-2">Filter Aktif</span>
            <span class="text-muted ms-1 small">(Total periode: <?= $fmt($table['global_total']) ?> hasil)</span>
          <?php endif; ?>
        </div>
        <div class="text-muted small">
          <span class="d-none me-2 text-primary fw-semibold" id="tableNewDataNotice">Ada hasil baru, buka halaman 1 untuk melihat.</span>
          Halaman <strong><span id="tableCurrentPage"><?= esc($table['page']) ?></span></strong> dari <strong><span id="tableTotalPages"><?= esc($table['total_pages']) ?></span></strong>
        </div>
      </div>

      <div class="table-responsive-dashboard mb-4">
        <table class="table-detection">
          <thead>
            <tr>
              <th style="width: 120px;">ID</th>
              <th style="width: 130px;">Cuplikan & Kotak</th>
              <th style="width: 140px;">Waktu (WITA)</th>
              <th>Kamera</th>
              <th>Kelas Model</th>
              <th style="width: 100px;">Confidence</th>
              <th style="width: 140px;">Indikasi Model</th>
            </tr>
          </thead>
          <tbody id="detectionTableBody">
            <?php if (!empty($table['data'])): ?>
              <?php foreach ($table['data'] as $row): ?>
                <tr>
                  <td><span class="fw-bold font-monospace text-dark small"><?= esc($row['id']) ?></span></td>
                  <td>
                    <?php if (!empty($row['image_url'])): ?>
                      <div class="d-flex align-items-center gap-2">
                        <img src="<?= esc($row['image_url']) ?>" alt="Foto Truk"
                             class="rounded border"
                             style="width: 50px; height: 38px; object-fit: cover; cursor: pointer;"
                             title="Klik untuk perbesar foto truk"
                             onclick="openTruckModal('<?= esc($row['image_url']) ?>', '<?= esc($row['id']) ?>', '<?= esc($row['class_label']) ?>', <?= $row['is_od'] ? 'true' : 'false' ?>, '<?= esc($row['confidence']) ?>', '<?= esc($row['date_formatted'] . ' ' . $row['time']) ?>', '<?= esc($row['bbox_str'] ?? '') ?>')">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" style="font-size: 0.68rem;"
                                onclick="openTruckModal('<?= esc($row['image_url']) ?>', '<?= esc($row['id']) ?>', '<?= esc($row['class_label']) ?>', <?= $row['is_od'] ? 'true' : 'false' ?>, '<?= esc($row['confidence']) ?>', '<?= esc($row['date_formatted'] . ' ' . $row['time']) ?>', '<?= esc($row['bbox_str'] ?? '') ?>')">
                          <i class="bi bi-zoom-in"></i>
                        </button>
                      </div>
                    <?php elseif (!empty($row['bbox_str'])): ?>
                      <span class="badge bg-light text-secondary border font-monospace small" title="Koordinat Bounding Box">
                        <i class="bi bi-bounding-box me-1"></i><?= esc($row['bbox_str']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted small">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark small"><?= esc($row['date_formatted']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?= esc($row['time']) ?></div>
                    <div class="text-muted" style="font-size: 0.68rem;"><?= esc($row['time_basis']) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold small text-dark"><?= esc($row['camera_name']) ?></div>
                    <div class="mt-1">
                      <?php $camBadge = str_contains($row['camera_source'] ?? '', 'sultan_adam') || ($row['camera_code'] ?? '') === 'ATCS-02' ? 'bg-info-subtle text-info-emphasis border border-info-subtle' : 'bg-primary-subtle text-primary-emphasis border border-primary-subtle'; ?>
                      <span class="badge <?= $camBadge ?> font-monospace" style="font-size: 0.68rem;"><?= esc($row['camera_code']) ?></span>
                    </div>
                  </td>
                  <td>
                    <div class="small fw-semibold text-dark"><?= esc($row['class_label']) ?></div>
                    <code class="text-muted" style="font-size: 0.7rem;"><?= esc($row['class_name']) ?></code>
                  </td>
                  <td><span class="badge bg-light text-dark border small fw-semibold font-monospace"><?= esc($row['confidence']) ?></span></td>
                  <td><?= view('partials/detection_badge', ['result' => $row['od_label']]) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-5">
                  <div class="text-muted mb-2"><i class="bi bi-search fs-2 opacity-50" aria-hidden="true"></i></div>
                  <h6 class="fw-bold text-dark mb-1">Belum ada hasil yang sesuai</h6>
                  <p class="text-muted small mb-3">
                    <?= $source['official']
                        ? 'Hasil core Rizkiya akan tampil setelah pengirim tepercaya mengirimkan keluaran core.'
                        : 'Tidak ada data uji pada periode atau filter ini.' ?>
                  </p>
                  <?php if ($table['has_active_filter']): ?>
                    <a href="<?= esc($resetFilterUrl) ?>" class="btn-dishub-outline">
                      <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reset Filter
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($table['total_pages'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="text-muted small">Menampilkan <?= esc($table['per_page']) ?> hasil per halaman</div>
          <nav aria-label="Navigasi Halaman Hasil">
            <div class="dashboard-pagination">
              <a href="<?= $table['has_prev'] ? esc($buildPageUrl($table['page'] - 1)) : '#' ?>"
                 class="dashboard-page-link <?= !$table['has_prev'] ? 'disabled' : '' ?>" aria-label="Halaman Sebelumnya"
                 <?= !$table['has_prev'] ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                <i class="bi bi-chevron-left small" aria-hidden="true"></i>
              </a>
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
                    $isActive = $p === $table['page'];
                    echo '<a href="' . esc($buildPageUrl($p)) . '" class="dashboard-page-link ' . ($isActive ? 'active' : '') . '" ' . ($isActive ? 'aria-current="page"' : '') . '>' . $p . '</a>';
                }
                if ($endPage < $table['total_pages']) {
                    if ($endPage < $table['total_pages'] - 1) {
                        echo '<span class="px-1 text-muted small">...</span>';
                    }
                    echo '<a href="' . esc($buildPageUrl($table['total_pages'])) . '" class="dashboard-page-link">' . $table['total_pages'] . '</a>';
                }
              ?>
              <a href="<?= $table['has_next'] ? esc($buildPageUrl($table['page'] + 1)) : '#' ?>"
                 class="dashboard-page-link <?= !$table['has_next'] ? 'disabled' : '' ?>" aria-label="Halaman Selanjutnya"
                 <?= !$table['has_next'] ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                <i class="bi bi-chevron-right small" aria-hidden="true"></i>
              </a>
            </div>
          </nav>
        </div>
      <?php endif; ?>
    </div>

    <!-- MODAL PREVIEW TRUK TERDETEKSI (KOTAK VISUAL BESAR) -->
    <div class="modal fade" id="truckPreviewModal" tabindex="-1" aria-labelledby="truckPreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-bottom">
            <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="truckPreviewModalLabel">
              <i class="bi bi-bounding-box text-primary"></i>
              <span>Foto Cuplikan Truk Terdeteksi</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body p-3 text-center bg-dark rounded-bottom">
            <div class="mb-3 text-center">
              <img id="modalTruckImage" src="" alt="Cuplikan Deteksi Truk" class="img-fluid rounded border border-secondary shadow" style="max-height: 480px;">
            </div>
            <div class="bg-white rounded p-3 text-start shadow-sm">
              <div class="row g-2 align-items-center">
                <div class="col-sm-6">
                  <div class="small text-muted">ID Deteksi & Waktu:</div>
                  <div class="fw-bold font-monospace text-dark" id="modalTruckId">-</div>
                  <div class="small text-secondary" id="modalTruckTime">-</div>
                </div>
                <div class="col-sm-6 text-sm-end">
                  <div class="small text-muted mb-1">Hasil Analisis Model:</div>
                  <div id="modalTruckBadge" class="mb-1"></div>
                  <div class="small fw-semibold text-dark" id="modalTruckClass">-</div>
                </div>
              </div>
              <div class="border-top mt-2 pt-2 d-flex justify-content-between align-items-center small text-muted">
                <span>Koordinat Kotak Deteksi: <strong class="font-monospace text-dark" id="modalTruckBbox">-</strong></span>
                <span>Tingkat Kepastian: <strong class="font-monospace text-dark" id="modalTruckConf">-</strong></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script id="dashboard-json-data" type="application/json">
<?= json_encode([
    'params'      => $params,
    'chart'       => $chart,
    'first_id'    => $table['data'][0]['id'] ?? null,
    'dataUrl'     => base_url('api/ai/dashboard-data'),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>
</script>
<script src="<?= base_url('assets/js/pages/dashboard.js') ?>"></script>
<?= $this->endSection() ?>
