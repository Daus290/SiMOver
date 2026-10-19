<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php
// Helper untuk membangun URL pagination dengan tetap mempertahankan filter aktif
$buildPageUrl = static function (int $targetPage) use ($filters) {
    $params = [];
    if (!empty($filters['cctv']) && $filters['cctv'] !== 'all') {
        $params['cctv'] = $filters['cctv'];
    }
    if (!empty($filters['vehicle']) && $filters['vehicle'] !== 'all') {
        $params['vehicle'] = $filters['vehicle'];
    }
    if (!empty($filters['result']) && $filters['result'] !== 'all') {
        $params['result'] = $filters['result'];
    }
    $params['page'] = $targetPage;
    return base_url('admin/hasil-deteksi') . '?' . http_build_query($params);
};
?>

<!-- Header Halaman Hasil Deteksi -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
  <div>
    <h1 class="h4 fw-bold text-dark mb-1">
      Hasil Deteksi Hari Ini
    </h1>
    <p class="text-muted small mb-0">
      Daftar individu 128 pemantauan kendaraan pada tanggal 25 September 2026 (s/d 15.42 WITA)
    </p>
  </div>
  <div>
    <span class="badge bg-white text-secondary border px-3 py-2 small shadow-sm">
      Data Hari Ini: <strong>25 September 2026</strong>
    </span>
  </div>
</div>

<!-- 1. KARTU FILTER PENCARIAN -->
<div class="admin-card mb-4">
  <form action="<?= base_url('admin/hasil-deteksi') ?>" method="get" class="row g-3 align-items-end">
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
        <button type="submit" class="btn-admin-primary btn-sm flex-grow-1">
          <i class="bi bi-funnel"></i>
          <span>Terapkan</span>
        </button>
        <?php if ($hasActiveFilter): ?>
          <a href="<?= base_url('admin/hasil-deteksi') ?>" class="btn-admin-outline btn-sm" title="Bersihkan Filter">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Reset</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </form>
</div>

<!-- 2. KONTROL JUMLAH & INDIKATOR DATA -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
  <div class="text-muted small">
    <?php if ($pagination['total'] > 0): ?>
      Menampilkan <strong><?= esc($pagination['from']) ?> - <?= esc($pagination['to']) ?></strong> dari <strong><?= esc($pagination['total']) ?></strong> data deteksi
    <?php else: ?>
      Tidak ada data yang ditemukan
    <?php endif; ?>

    <?php if ($hasActiveFilter): ?>
      <span class="badge-filter-active ms-2">Filter Aktif</span>
    <?php endif; ?>
  </div>

  <div class="text-muted small">
    Halaman <strong><?= esc($pagination['page']) ?></strong> dari <strong><?= esc($pagination['total_pages']) ?></strong>
  </div>
</div>

<!-- 3. TABEL DATA DETEKSI INDIVIDU -->
<div class="table-responsive-admin mb-4">
  <table class="table-admin">
    <thead>
      <tr>
        <th style="width: 170px;">ID Deteksi</th>
        <th style="width: 140px;">Waktu (WITA)</th>
        <th>Titik CCTV</th>
        <th>Jenis Kendaraan</th>
        <th style="width: 170px;">Hasil Deteksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($pagination['data'])): ?>
        <?php foreach ($pagination['data'] as $row): ?>
          <tr>
            <td>
              <span class="fw-bold font-monospace text-dark small"><?= esc($row['id']) ?></span>
            </td>
            <td>
              <span class="text-muted small"><?= esc($row['time']) ?></span>
            </td>
            <td>
              <div class="fw-semibold small text-dark"><?= esc($row['cctv_name']) ?></div>
              <div class="text-muted" style="font-size: 0.72rem;"><?= esc($row['cctv_code']) ?></div>
            </td>
            <td>
              <span class="small text-dark"><?= esc($row['vehicle_type']) ?></span>
            </td>
            <td>
                <?= view('admin/partials/detection_badge', ['result' => $row['detection_result']]) ?>
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
            <p class="text-muted small mb-3">Coba ubah kriteria pencarian atau bersihkan filter.</p>
            <a href="<?= base_url('admin/hasil-deteksi') ?>" class="btn-admin-outline btn-sm">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
            </a>
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- 4. NAVIGASI PAGINASI (10 Item per Halaman) -->
<?php if ($pagination['total_pages'] > 1): ?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="text-muted small">
      Menampilkan 10 record per halaman
    </div>

    <nav aria-label="Navigasi Halaman Deteksi">
      <div class="admin-pagination">
        <!-- Tombol Sebelumnya -->
        <a 
          href="<?= $pagination['has_prev'] ? esc($buildPageUrl($pagination['page'] - 1)) : '#' ?>" 
          class="admin-page-link <?= !$pagination['has_prev'] ? 'disabled' : '' ?>"
          aria-label="Halaman Sebelumnya"
        >
          <i class="bi bi-chevron-left small"></i>
        </a>

        <!-- Daftar Nomor Halaman (Tampilkan cerdas rentang sekitar current page) -->
        <?php
          $startPage = max(1, $pagination['page'] - 2);
          $endPage = min($pagination['total_pages'], $pagination['page'] + 2);

          if ($startPage > 1) {
              echo '<a href="' . esc($buildPageUrl(1)) . '" class="admin-page-link">1</a>';
              if ($startPage > 2) {
                  echo '<span class="px-1 text-muted small">...</span>';
              }
          }

          for ($p = $startPage; $p <= $endPage; $p++) {
              $isActive = ($p === $pagination['page']);
              echo '<a href="' . esc($buildPageUrl($p)) . '" class="admin-page-link ' . ($isActive ? 'active' : '') . '">' . $p . '</a>';
          }

          if ($endPage < $pagination['total_pages']) {
              if ($endPage < $pagination['total_pages'] - 1) {
                  echo '<span class="px-1 text-muted small">...</span>';
              }
              echo '<a href="' . esc($buildPageUrl($pagination['total_pages'])) . '" class="admin-page-link">' . $pagination['total_pages'] . '</a>';
          }
        ?>

        <!-- Tombol Selanjutnya -->
        <a 
          href="<?= $pagination['has_next'] ? esc($buildPageUrl($pagination['page'] + 1)) : '#' ?>" 
          class="admin-page-link <?= !$pagination['has_next'] ? 'disabled' : '' ?>"
          aria-label="Halaman Selanjutnya"
        >
          <i class="bi bi-chevron-right small"></i>
        </a>
      </div>
    </nav>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
