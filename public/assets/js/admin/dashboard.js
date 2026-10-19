/**
 * Dashboard Admin - SiMover Dishub Banjarbaru
 * Modul JavaScript untuk Grafik Statistik & Tren Over Dimension (Chart.js)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const dataEl = document.getElementById('admin-dashboard-data');
    if (!dataEl) return;

    let dashboardData = {};
    try {
      dashboardData = JSON.parse(dataEl.textContent);
    } catch (e) {
      console.error('Gagal membaca data admin dashboard:', e);
      return;
    }

    const hourlyOD = dashboardData.hourly_od || { labels: [], values: [] };
    const odTrend = dashboardData.od_trend || { labels: [], values: [] };

    // 1. Inisialisasi Grafik Batang Akumulasi Over Dimension per Jam (7 Hari)
    const ctxHourly = document.getElementById('adminHourlyODChart');
    if (ctxHourly && window.Chart) {
      const hourlyLabels = hourlyOD.labels || [];
      const hourlyValues = hourlyOD.values || [];

      // Highlight jam puncak (14.00) dengan navy (#0F172A), lainnya oranye (#F97316)
      const backgroundColors = hourlyLabels.map((lbl) => {
        return (lbl === '14:00') ? '#0F172A' : '#F97316';
      });

      new Chart(ctxHourly, {
        type: 'bar',
        data: {
          labels: hourlyLabels,
          datasets: [{
            label: 'Kasus Over Dimension',
            data: hourlyValues,
            backgroundColor: backgroundColors,
            borderRadius: 0,
            barPercentage: 0.8,
            categoryPercentage: 0.9
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: function (items) {
                  return 'Pukul ' + items[0].label + ' WITA';
                },
                label: function (context) {
                  return 'Over Dimension: ' + context.raw + ' kasus (7 hari)';
                }
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { font: { size: 10 }, maxTicksLimit: 12 }
            },
            y: {
              beginAtZero: true,
              suggestedMax: 20,
              grid: { color: '#F1F5F9' },
              ticks: { stepSize: 4, font: { size: 10 } }
            }
          }
        }
      });
    }

    // 2. Inisialisasi Grafik Garis Tren Over Dimension 7 Hari
    const ctxTrend = document.getElementById('adminDailyODTrendChart');
    if (ctxTrend && window.Chart) {
      const trendLabels = odTrend.labels || [];
      const trendValues = odTrend.values || [];

      new Chart(ctxTrend, {
        type: 'line',
        data: {
          labels: trendLabels,
          datasets: [{
            label: 'Kasus Over Dimension',
            data: trendValues,
            borderColor: '#F97316',
            backgroundColor: 'rgba(249, 115, 22, 0.08)',
            fill: true,
            borderWidth: 2.5,
            tension: 0.25,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: trendValues.map(v => (v === 24 ? '#0F172A' : '#F97316')),
            pointBorderColor: '#FFFFFF',
            pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: function (items) {
                  return items[0].label + ' 2026';
                },
                label: function (context) {
                  return 'Over Dimension: ' + context.raw + ' kasus';
                }
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { font: { size: 11 } }
            },
            y: {
              beginAtZero: true,
              suggestedMax: 26,
              grid: { color: '#F1F5F9' },
              ticks: { stepSize: 5, font: { size: 10 } }
            }
          }
        }
      });
    }
  });
})();
