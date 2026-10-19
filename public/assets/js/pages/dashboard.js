/**
 * Dashboard Monitoring Over Dimension - Dishub Kota Banjarbaru
 * Modul JavaScript untuk Dua Tab, Dropdown Periode, dan Lifecycle Chart.js
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

    const currentPeriod = config.period || 1;
    let currentTab = config.active_tab || 'statistik';
    const chartData = config.chart || {};

    let lineChartInstance = null;
    let barChartInstance = null;

    // =========================================================================
    // 1. Inisialisasi & Lifecycle Chart.js (Line Chart & Stacked Bar Chart)
    // =========================================================================
    function initCharts() {
      if (!window.Chart || !chartData.labels) return;

      const isHourly = (chartData.mode === 'hourly');
      const labels = chartData.labels || [];
      const normalData = chartData.normal || [];
      const kontainerData = chartData.kontainer || [];
      const overDimData = chartData.over_dimension || [];

      // 1.1 Line Chart
      const ctxLine = document.getElementById('dailyDetectionLineChart');
      if (ctxLine) {
        if (lineChartInstance) {
          lineChartInstance.destroy();
        }

        lineChartInstance = new Chart(ctxLine, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Normal',
                data: normalData,
                borderColor: '#16A34A',
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: isHourly ? 3 : 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#16A34A',
                spanGaps: false
              },
              {
                label: 'Kontainer',
                data: kontainerData,
                borderColor: '#2563EB',
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: isHourly ? 3 : 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#2563EB',
                spanGaps: false
              },
              {
                label: 'Over Dimension',
                data: overDimData,
                borderColor: '#F97316',
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: isHourly ? 3 : 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#F97316',
                spanGaps: false
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 350 },
            interaction: {
              mode: 'index',
              intersect: false
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: {
                  boxWidth: 12,
                  font: { size: 12, weight: '600' }
                }
              },
              tooltip: {
                callbacks: {
                  title: function (items) {
                    if (!items.length) return '';
                    if (isHourly) {
                      return 'Pukul ' + items[0].label + ' WITA';
                    }
                    return 'Tanggal ' + items[0].label + ' 2026';
                  },
                  label: function (context) {
                    const val = context.raw;
                    if (val === null || val === undefined) {
                      return context.dataset.label + ': Belum berlangsung';
                    }
                    return context.dataset.label + ': ' + val + ' kendaraan';
                  }
                }
              }
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: {
                  font: { size: 11 },
                  maxTicksLimit: isHourly ? 12 : 15,
                  maxRotation: isHourly ? 0 : 45
                }
              },
              y: {
                beginAtZero: true,
                grid: { color: '#F1F5F9' },
                ticks: {
                  font: { size: 11 },
                  precision: 0
                }
              }
            }
          }
        });
      }

      // 1.2 Stacked Bar Chart (Kotak / BorderRadius 0)
      const ctxBar = document.getElementById('dailyDetectionBarChart');
      if (ctxBar) {
        if (barChartInstance) {
          barChartInstance.destroy();
        }

        barChartInstance = new Chart(ctxBar, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Normal',
                data: normalData,
                backgroundColor: '#16A34A',
                borderRadius: 0,
                barPercentage: isHourly ? 0.8 : 0.75,
                categoryPercentage: 0.9
              },
              {
                label: 'Kontainer',
                data: kontainerData,
                backgroundColor: '#2563EB',
                borderRadius: 0,
                barPercentage: isHourly ? 0.8 : 0.75,
                categoryPercentage: 0.9
              },
              {
                label: 'Over Dimension',
                data: overDimData,
                backgroundColor: '#F97316',
                borderRadius: 0,
                barPercentage: isHourly ? 0.8 : 0.75,
                categoryPercentage: 0.9
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 350 },
            interaction: {
              mode: 'index',
              intersect: false
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: {
                  boxWidth: 12,
                  boxHeight: 12,
                  font: { size: 12, weight: '600' },
                  padding: 16
                }
              },
              tooltip: {
                callbacks: {
                  title: function (items) {
                    if (!items.length) return '';
                    if (isHourly) {
                      return 'Pukul ' + items[0].label + ' WITA';
                    }
                    return 'Tanggal ' + items[0].label + ' 2026';
                  },
                  label: function (context) {
                    const val = context.raw;
                    if (val === null || val === undefined) {
                      return context.dataset.label + ': Belum berlangsung';
                    }
                    return context.dataset.label + ': ' + val + ' kendaraan';
                  },
                  footer: function (items) {
                    let sum = 0;
                    let hasData = false;
                    items.forEach(function (item) {
                      if (item.raw !== null && item.raw !== undefined) {
                        sum += item.raw;
                        hasData = true;
                      }
                    });
                    return hasData ? 'Total: ' + sum + ' kendaraan' : '';
                  }
                }
              }
            },
            scales: {
              x: {
                stacked: true,
                grid: { display: false },
                ticks: {
                  font: { size: 11 },
                  maxTicksLimit: isHourly ? 12 : 15,
                  maxRotation: isHourly ? 0 : 45
                }
              },
              y: {
                stacked: true,
                beginAtZero: true,
                grid: { color: '#F1F5F9' },
                ticks: {
                  font: { size: 11 },
                  precision: 0
                }
              }
            }
          }
        });
      }
    }

    // Inisialisasi awal grafik jika Tab Statistik aktif saat pertama kali dimuat
    if (currentTab === 'statistik') {
      initCharts();
    }

    // =========================================================================
    // 2. Navigasi Dua Tab (Statistik vs Hasil Deteksi)
    // =========================================================================
    const tabButtons = document.querySelectorAll('.dashboard-tab-btn');
    const panelStatistik = document.getElementById('panel-statistik');
    const panelHasil = document.getElementById('panel-hasil-deteksi');

    function switchTab(targetTab, updateHistory = true) {
      if (!targetTab || (targetTab !== 'statistik' && targetTab !== 'hasil-deteksi')) return;

      currentTab = targetTab;

      // Update tombol aktif
      tabButtons.forEach(function (btn) {
        const isTarget = (btn.getAttribute('data-tab-target') === targetTab);
        btn.classList.toggle('active', isTarget);
        btn.setAttribute('aria-selected', isTarget ? 'true' : 'false');
      });

      // Tampilkan hanya satu panel yang aktif
      if (panelStatistik && panelHasil) {
        if (targetTab === 'statistik') {
          panelStatistik.classList.remove('d-none');
          panelHasil.classList.add('d-none');

          // Tangani lifecycle Chart.js saat tab ditampilkan kembali
          if (!lineChartInstance || !barChartInstance) {
            initCharts();
          } else {
            // Beri sedikit delay untuk memastikan reflow selesai
            requestAnimationFrame(function () {
              if (lineChartInstance) lineChartInstance.resize();
              if (barChartInstance) barChartInstance.resize();
            });
          }
        } else {
          panelHasil.classList.remove('d-none');
          panelStatistik.classList.add('d-none');
        }
      }

      // Sinkronisasi URL query string agar refresh, deep link & back/forward tetap konsisten
      if (updateHistory && window.history && window.history.pushState) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('tab', targetTab);
        // Pastikan periode selalu ada di query
        if (!currentUrl.searchParams.has('period')) {
          currentUrl.searchParams.set('period', currentPeriod);
        }
        window.history.pushState({ tab: targetTab, period: currentPeriod }, '', currentUrl.toString());
      }
    }

    tabButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const targetTab = this.getAttribute('data-tab-target');
        switchTab(targetTab, true);
      });
    });

    // Dukung navigasi browser Back/Forward (popstate)
    window.addEventListener('popstate', function (e) {
      const urlParams = new URLSearchParams(window.location.search);
      const targetTab = urlParams.get('tab') || 'statistik';
      switchTab(targetTab, false);
    });

    // =========================================================================
    // 3. Dropdown Satu Periode (1 Hari, 7 Hari, 30 Hari)
    // =========================================================================
    const periodSelect = document.getElementById('periodSelect');
    if (periodSelect) {
      periodSelect.addEventListener('change', function () {
        const newPeriod = this.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('period', newPeriod);
        currentUrl.searchParams.set('tab', currentTab);
        // Reset halaman tabel ke 1 saat berganti periode
        currentUrl.searchParams.delete('page');

        // Navigasi ke URL baru dengan reload data periode lengkap
        window.location.href = currentUrl.toString();
      });
    }
  });
})();
