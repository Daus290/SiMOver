/**
 * Dashboard Pengunjung - SiMover Dishub Banjarbaru
 * Modul JavaScript untuk Grafik Tren & Statistik Harian (Chart.js)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const dataEl = document.getElementById('visitor-dashboard-data');
    if (!dataEl) return;

    let timeline = {};
    try {
      timeline = JSON.parse(dataEl.textContent);
    } catch (e) {
      console.error('Gagal membaca data timeline:', e);
      return;
    }

    const labels = timeline.labels || [];
    const normalData = timeline.normal || [];
    const kontainerData = timeline.kontainer || [];
    const overDimData = timeline.over_dimension || [];

    // 1. Inisialisasi Grafik Garis (Line Chart)
    const ctxLine = document.getElementById('dailyDetectionLineChart');
    if (ctxLine && window.Chart) {
      new Chart(ctxLine, {
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
              pointRadius: 3,
              pointHoverRadius: 5,
              pointBackgroundColor: '#16A34A',
              spanGaps: false
            },
            {
              label: 'Kontainer',
              data: kontainerData,
              borderColor: '#2563EB',
              backgroundColor: 'transparent',
              borderWidth: 2,
              pointRadius: 3,
              pointHoverRadius: 5,
              pointBackgroundColor: '#2563EB',
              spanGaps: false
            },
            {
              label: 'Over Dimension',
              data: overDimData,
              borderColor: '#F97316',
              backgroundColor: 'transparent',
              borderWidth: 2,
              pointRadius: 3,
              pointHoverRadius: 5,
              pointBackgroundColor: '#F97316',
              spanGaps: false
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 400
          },
          interaction: {
            mode: 'index',
            intersect: false,
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
                title: function(items) {
                  return 'Pukul ' + items[0].label + ' WITA';
                },
                label: function(context) {
                  const val = context.raw;
                  if (val === null || val === undefined) {
                    return context.dataset.label + ': Belum berlangsung';
                  }
                  return context.dataset.label + ': ' + val;
                }
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { font: { size: 11 }, maxTicksLimit: 12 }
            },
            y: {
              beginAtZero: true,
              suggestedMax: 14,
              grid: { color: '#F1F5F9' },
              ticks: { stepSize: 2, font: { size: 11 } }
            }
          }
        }
      });
    }

    // 2. Inisialisasi Grafik Batang Bertumpuk (Stacked Bar Chart Kotak)
    const ctxBar = document.getElementById('dailyDetectionBarChart');
    if (ctxBar && window.Chart) {
      new Chart(ctxBar, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Normal',
              data: normalData,
              backgroundColor: '#16A34A',
              borderRadius: 0,
              barPercentage: 0.8,
              categoryPercentage: 0.9
            },
            {
              label: 'Kontainer',
              data: kontainerData,
              backgroundColor: '#2563EB',
              borderRadius: 0,
              barPercentage: 0.8,
              categoryPercentage: 0.9
            },
            {
              label: 'Over Dimension',
              data: overDimData,
              backgroundColor: '#F97316',
              borderRadius: 0,
              barPercentage: 0.8,
              categoryPercentage: 0.9
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 400
          },
          interaction: {
            mode: 'index',
            intersect: false,
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
                title: function(items) {
                  return 'Pukul ' + items[0].label + ' WITA';
                },
                label: function(context) {
                  const val = context.raw;
                  if (val === null || val === undefined) {
                    return context.dataset.label + ': Belum berlangsung';
                  }
                  return context.dataset.label + ': ' + val + ' kendaraan';
                },
                footer: function(items) {
                  let sum = 0;
                  let hasData = false;
                  items.forEach(function(item) {
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
              ticks: { font: { size: 11 }, maxTicksLimit: 12 }
            },
            y: {
              stacked: true,
              beginAtZero: true,
              suggestedMax: 20,
              grid: { color: '#F1F5F9' },
              ticks: { stepSize: 2, font: { size: 11 } }
            }
          }
        }
      });
    }
  });
})();
