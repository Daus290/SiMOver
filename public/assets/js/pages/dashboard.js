/**
 * Dashboard Monitoring Over Dimension - Dishub Kota Banjarbaru
 * Dua tab, dropdown Sumber & Periode, Chart.js lima kelas, dan polling ringan.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const dataEl = document.getElementById('dashboard-json-data');
    if (!dataEl) return;

    let config = {};
    try {
      config = JSON.parse(dataEl.textContent);
    } catch (e) {
      console.error('Gagal membaca konfigurasi dashboard:', e);
      return;
    }

    const params = config.params || {};
    let currentTab = params.tab || 'statistik';
    let chartData = config.chart || {};
    let firstRowId = config.first_id || null;

    let lineChartInstance = null;
    let barChartInstance = null;

    // =========================================================================
    // 1. Chart.js: satu dataset per kelas model
    // =========================================================================
    function tooltipCallbacks(isHourly) {
      return {
        title: function (items) {
          if (!items.length) return '';
          return isHourly ? 'Pukul ' + items[0].label + ' WITA' : 'Tanggal ' + items[0].label;
        },
        label: function (context) {
          const val = context.raw;
          if (val === null || val === undefined) {
            return context.dataset.label + ': tidak terpantau / belum berlangsung';
          }
          return context.dataset.label + ': ' + val + ' hasil';
        }
      };
    }

    function buildDatasets(type) {
      return (chartData.series || []).map(function (s) {
        const base = { label: s.label, data: s.data };
        if (type === 'line') {
          return Object.assign(base, {
            borderColor: s.color,
            backgroundColor: 'transparent',
            borderWidth: 2,
            pointRadius: chartData.mode === 'hourly' ? 3 : 4,
            pointHoverRadius: 6,
            pointBackgroundColor: s.color,
            spanGaps: false
          });
        }
        return Object.assign(base, {
          backgroundColor: s.color,
          borderRadius: 0,
          barPercentage: chartData.mode === 'hourly' ? 0.8 : 0.75,
          categoryPercentage: 0.9
        });
      });
    }

    function initCharts() {
      if (!window.Chart || !chartData.labels) return;
      const isHourly = chartData.mode === 'hourly';
      const ticks = { font: { size: 11 }, maxTicksLimit: isHourly ? 12 : 15, maxRotation: isHourly ? 0 : 45 };
      const legend = { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 12, weight: '600' } } };

      const ctxLine = document.getElementById('dailyDetectionLineChart');
      if (ctxLine) {
        if (lineChartInstance) lineChartInstance.destroy();
        lineChartInstance = new Chart(ctxLine, {
          type: 'line',
          data: { labels: chartData.labels, datasets: buildDatasets('line') },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 350 },
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: legend, tooltip: { callbacks: tooltipCallbacks(isHourly) } },
            scales: {
              x: { grid: { display: false }, ticks: ticks },
              y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 }, precision: 0 } }
            }
          }
        });
      }

      const ctxBar = document.getElementById('dailyDetectionBarChart');
      if (ctxBar) {
        if (barChartInstance) barChartInstance.destroy();
        const callbacks = tooltipCallbacks(isHourly);
        callbacks.footer = function (items) {
          let sum = 0;
          let hasData = false;
          items.forEach(function (item) {
            if (item.raw !== null && item.raw !== undefined) {
              sum += item.raw;
              hasData = true;
            }
          });
          return hasData ? 'Total: ' + sum + ' hasil' : '';
        };
        barChartInstance = new Chart(ctxBar, {
          type: 'bar',
          data: { labels: chartData.labels, datasets: buildDatasets('bar') },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 350 },
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: legend, tooltip: { callbacks: callbacks } },
            scales: {
              x: { stacked: true, grid: { display: false }, ticks: ticks },
              y: { stacked: true, beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 }, precision: 0 } }
            }
          }
        });
      }
    }

    // Perbarui instance yang ada, bukan membuat chart baru
    function updateCharts() {
      [lineChartInstance, barChartInstance].forEach(function (chart) {
        if (!chart) return;
        chart.data.labels = chartData.labels;
        (chartData.series || []).forEach(function (s, i) {
          if (chart.data.datasets[i]) chart.data.datasets[i].data = s.data;
        });
        chart.update('none');
      });
    }

    if (currentTab === 'statistik') initCharts();

    // =========================================================================
    // 2. Navigasi dua tab
    // =========================================================================
    const tabButtons = document.querySelectorAll('.dashboard-tab-btn');
    const panelStatistik = document.getElementById('panel-statistik');
    const panelHasil = document.getElementById('panel-hasil-deteksi');

    function switchTab(targetTab, updateHistory) {
      if (targetTab !== 'statistik' && targetTab !== 'hasil-deteksi') return;
      currentTab = targetTab;

      tabButtons.forEach(function (btn) {
        const isTarget = btn.getAttribute('data-tab-target') === targetTab;
        btn.classList.toggle('active', isTarget);
        btn.setAttribute('aria-selected', isTarget ? 'true' : 'false');
      });

      if (panelStatistik && panelHasil) {
        panelStatistik.classList.toggle('d-none', targetTab !== 'statistik');
        panelHasil.classList.toggle('d-none', targetTab !== 'hasil-deteksi');
        if (targetTab === 'statistik') {
          if (!lineChartInstance || !barChartInstance) {
            initCharts();
          } else {
            requestAnimationFrame(function () {
              lineChartInstance.resize();
              barChartInstance.resize();
            });
          }
        }
      }

      if (updateHistory && window.history && window.history.pushState) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', targetTab);
        url.searchParams.set('period', params.period);
        url.searchParams.set('source', params.source);
        window.history.pushState({ tab: targetTab }, '', url.toString());
      }
    }

    tabButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        switchTab(this.getAttribute('data-tab-target'), true);
      });
    });

    window.addEventListener('popstate', function () {
      switchTab(new URLSearchParams(window.location.search).get('tab') || 'statistik', false);
    });

    // =========================================================================
    // 3. Dropdown Sumber & Periode (ganti data penuh, kembali ke halaman 1)
    // =========================================================================
    function navigateWith(key, value) {
      const url = new URL(window.location.href);
      url.searchParams.set(key, value);
      url.searchParams.set('tab', currentTab);
      url.searchParams.delete('page');
      window.location.href = url.toString();
    }

    const periodSelect = document.getElementById('periodSelect');
    if (periodSelect) periodSelect.addEventListener('change', function () { navigateWith('period', this.value); });
    const sourceSelect = document.getElementById('sourceSelect');
    if (sourceSelect) sourceSelect.addEventListener('change', function () { navigateWith('source', this.value); });

    // =========================================================================
    // 4. Polling ~5 detik: maksimal satu request aktif, berhenti saat tab tersembunyi
    // =========================================================================
    const POLL_MS = 5000;
    let pollTimer = null;
    let inFlight = false;
    const notice = document.getElementById('dashboardLiveUpdateNotice');

    function setText(id, text) {
      const node = document.getElementById(id);
      if (node) node.textContent = text;
    }

    function fmtNum(n) {
      return Number(n || 0).toLocaleString('id-ID');
    }

    function fmtTime(t) {
      if (!t) return 'Belum ada';
      const d = new Date(t.replace(' ', 'T') + '+08:00');
      if (isNaN(d)) return t;
      return d.toLocaleString('id-ID', { timeZone: 'Asia/Makassar', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) + ' WITA';
    }

    function applyData(data) {
      const stats = data.stats;
      const status = data.status;

      // Status pengirim
      const badge = document.getElementById('badgeSourceStatus');
      if (badge && status) {
        badge.textContent = status.label;
        badge.className = 'badge ' + (status.state === 'aktif' ? 'bg-success text-white' : status.state === 'menyambung' ? 'bg-warning text-dark' : 'bg-secondary text-white');
        setText('statSourceState', status.label);
        setText('statSourceHeartbeat', 'Heartbeat terakhir: ' + fmtTime(status.last_heartbeat));
      }

      // Kartu & distribusi
      setText('statTotal', fmtNum(stats.total));
      setText('statOd', fmtNum(stats.od_count));
      setText('statOdPct', stats.od_pct + '% dari hasil tercatat (prediksi model)');
      setText('statLastReceived', fmtTime(stats.last_received));
      (stats.classes || []).forEach(function (c) {
        setText('distVal-' + c.name, fmtNum(c.count) + ' hasil (' + c.pct + '%)');
        const bar = document.getElementById('distBar-' + c.name);
        if (bar) bar.style.width = c.pct + '%';
      });
      const empty = document.getElementById('distEmptyNotice');
      const bars = document.getElementById('distributionBarsContainer');
      if (empty && bars) {
        empty.classList.toggle('d-none', stats.total > 0);
        bars.classList.toggle('d-none', stats.total === 0);
      }

      // Insight
      ['peak_day', 'peak_hour', 'top_class'].forEach(function (k) {
        if (!data.insights || !data.insights[k]) return;
        setText('insight-' + k + '-title', data.insights[k].title);
        setText('insight-' + k + '-sub', data.insights[k].subtitle);
      });

      // Cuplikan Galeri Truk Terkini
      if (data.recent_detections) {
        renderRecentGallery(data.recent_detections);
      }

      // Grafik
      chartData = data.chart || chartData;
      if (currentTab === 'statistik') updateCharts();

      // Tabel: hanya disegarkan di halaman 1; di halaman lain cukup beri tanda data baru
      const newFirst = data.table && data.table.data.length ? data.table.data[0].id : null;
      if (newFirst !== firstRowId) {
        if (Number(params.page) === 1) {
          renderRows(data.table.data);
          firstRowId = newFirst;
        } else {
          const n = document.getElementById('tableNewDataNotice');
          if (n) n.classList.remove('d-none');
        }
      }
    }

    function renderRecentGallery(items) {
      const gallery = document.getElementById('recentDetectionsGallery');
      if (!gallery || !items || items.length === 0) return;
      gallery.innerHTML = items.map(function (rec) {
        const badge = rec.is_od
          ? '<span class="badge-deteksi badge-overdim">Over Dimension</span>'
          : '<span class="badge-deteksi badge-normal">Bukan OD</span>';

        let mediaHtml = '';
        if (rec.image_url) {
          mediaHtml = '<img src="' + escapeHtml(rec.image_url) + '" alt="' + escapeHtml(rec.class_label) + '" class="w-100 h-100 rounded object-fit-cover" style="object-fit: cover; cursor: pointer;" onclick="openTruckModal(\'' + escapeHtml(rec.image_url) + '\', \'' + escapeHtml(rec.id) + '\', \'' + escapeHtml(rec.class_label) + '\', ' + (rec.is_od ? 'true' : 'false') + ', \'' + escapeHtml(rec.confidence) + '\', \'' + escapeHtml(rec.date_formatted + ' ' + rec.time) + '\', \'' + escapeHtml(rec.bbox_str || '') + '\')">';
        } else {
          mediaHtml = '<div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted p-2 text-center">' +
            '<i class="bi bi-truck fs-1 opacity-25"></i>' +
            '<span class="small font-monospace mt-1">' + escapeHtml(rec.bbox_str || 'Kotak [x1, y1, x2, y2]') + '</span>' +
            '</div>';
        }

        return '<div class="col-xl-3 col-md-6">' +
          '<div class="border rounded p-2 bg-white shadow-sm h-100 d-flex flex-column">' +
          '<div class="position-relative mb-2 rounded overflow-hidden bg-light" style="height: 140px;">' +
          mediaHtml +
          '<div class="position-absolute top-0 start-0 m-2">' + badge + '</div>' +
          '</div>' +
          '<div class="mt-auto">' +
          '<div class="d-flex justify-content-between align-items-center mb-1">' +
          '<span class="fw-bold text-dark small">' + escapeHtml(rec.class_label) + '</span>' +
          '<span class="badge bg-light text-dark border font-monospace small">' + escapeHtml(rec.confidence) + '</span>' +
          '</div>' +
          '<div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.72rem;">' +
          '<span>' + escapeHtml(rec.time) + '</span>' +
          '<span class="font-monospace">' + escapeHtml(rec.id) + '</span>' +
          '</div></div></div></div>';
      }).join('');
    }

    function renderRows(rows) {
      const tbody = document.getElementById('detectionTableBody');
      if (!tbody || !rows || rows.length === 0) return;
      tbody.innerHTML = rows.map(function (r) {
        const badge = r.is_od
          ? '<span class="badge-deteksi badge-overdim">Over Dimension</span>'
          : '<span class="badge-deteksi badge-normal">Bukan OD</span>';

        let thumbCell = '<span class="text-muted small">-</span>';
        if (r.image_url) {
          thumbCell = '<div class="d-flex align-items-center gap-2">' +
            '<img src="' + escapeHtml(r.image_url) + '" alt="Foto Truk" class="rounded border" style="width: 50px; height: 38px; object-fit: cover; cursor: pointer;" title="Klik untuk perbesar foto truk" onclick="openTruckModal(\'' + escapeHtml(r.image_url) + '\', \'' + escapeHtml(r.id) + '\', \'' + escapeHtml(r.class_label) + '\', ' + (r.is_od ? 'true' : 'false') + ', \'' + escapeHtml(r.confidence) + '\', \'' + escapeHtml(r.date_formatted + ' ' + r.time) + '\', \'' + escapeHtml(r.bbox_str || '') + '\')">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" style="font-size: 0.68rem;" onclick="openTruckModal(\'' + escapeHtml(r.image_url) + '\', \'' + escapeHtml(r.id) + '\', \'' + escapeHtml(r.class_label) + '\', ' + (r.is_od ? 'true' : 'false') + ', \'' + escapeHtml(r.confidence) + '\', \'' + escapeHtml(r.date_formatted + ' ' + r.time) + '\', \'' + escapeHtml(r.bbox_str || '') + '\')"><i class="bi bi-zoom-in"></i></button>' +
            '</div>';
        } else if (r.bbox_str) {
          thumbCell = '<span class="badge bg-light text-secondary border font-monospace small" title="Koordinat Bounding Box"><i class="bi bi-bounding-box me-1"></i>' + escapeHtml(r.bbox_str) + '</span>';
        }

        return '<tr>' +
          '<td><span class="fw-bold font-monospace text-dark small">' + escapeHtml(r.id) + '</span></td>' +
          '<td>' + thumbCell + '</td>' +
          '<td><div class="fw-semibold text-dark small">' + escapeHtml(r.date_formatted) + '</div>' +
          '<div class="text-muted" style="font-size: 0.75rem;">' + escapeHtml(r.time) + '</div>' +
          '<div class="text-muted" style="font-size: 0.68rem;">' + escapeHtml(r.time_basis) + '</div></td>' +
          '<td><div class="fw-semibold small text-dark">' + escapeHtml(r.camera_name) + '</div>' +
          '<div class="text-muted" style="font-size: 0.72rem;">' + escapeHtml(r.camera_code) + '</div></td>' +
          '<td><div class="small fw-semibold text-dark">' + escapeHtml(r.class_label) + '</div>' +
          '<code class="text-muted" style="font-size: 0.7rem;">' + escapeHtml(r.class_name) + '</code></td>' +
          '<td><span class="badge bg-light text-dark border small fw-semibold font-monospace">' + escapeHtml(r.confidence) + '</span></td>' +
          '<td>' + badge + '</td>' +
          '</tr>';
      }).join('');
    }

    window.openTruckModal = function (imgUrl, id, title, isOd, conf, time, bbox) {
      const modalEl = document.getElementById('truckPreviewModal');
      if (!modalEl) return;
      const imgEl = document.getElementById('modalTruckImage');
      if (imgEl) imgEl.src = imgUrl;
      const idEl = document.getElementById('modalTruckId');
      if (idEl) idEl.textContent = id;
      const timeEl = document.getElementById('modalTruckTime');
      if (timeEl) timeEl.textContent = time;
      const classEl = document.getElementById('modalTruckClass');
      if (classEl) classEl.textContent = title;
      const confEl = document.getElementById('modalTruckConf');
      if (confEl) confEl.textContent = conf;
      const bboxEl = document.getElementById('modalTruckBbox');
      if (bboxEl) bboxEl.textContent = bbox || 'N/A';
      const badgeEl = document.getElementById('modalTruckBadge');
      if (badgeEl) {
        badgeEl.innerHTML = isOd
          ? '<span class="badge-deteksi badge-overdim">Over Dimension</span>'
          : '<span class="badge-deteksi badge-normal">Bukan OD</span>';
      }
      if (window.bootstrap && window.bootstrap.Modal) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      }
    };

    function escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    async function poll() {
      pollTimer = null;
      if (document.hidden || inFlight) return;
      inFlight = true;
      const controller = new AbortController();
      const timeout = setTimeout(function () { controller.abort(); }, 8000);

      try {
        const q = new URLSearchParams({ source: params.source, period: params.period, page: params.page, class: params.class, od: params.od });
        const resp = await fetch(config.dataUrl + '?' + q.toString(), { cache: 'no-store', signal: controller.signal });
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const data = await resp.json();
        applyData(data);
        if (notice) notice.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour12: false });
      } catch (e) {
        // Data lama tetap tampil, tetapi diberi tanda bahwa pembaruan gagal
        if (notice) notice.textContent = 'Pembaruan gagal, menampilkan data terakhir yang berhasil dimuat';
      } finally {
        clearTimeout(timeout);
        inFlight = false;
        schedule();
      }
    }

    function schedule() {
      if (pollTimer || document.hidden) return;
      pollTimer = setTimeout(poll, POLL_MS);
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        clearTimeout(pollTimer);
        pollTimer = null;
      } else {
        schedule();
      }
    });

    schedule();
  });
})();
