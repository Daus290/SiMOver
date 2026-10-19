/**
 * SISTEM MONITORING OVER DIMENSION - DISHUB KOTA BANJARBARU
 * Client-Side Interactivity (Live CCTV Filter, Search, Favorites, Tooltips)
 */

document.addEventListener('DOMContentLoaded', function () {
  initFavorites();
  initCctvFilterAndSearch();
  initMapMarkers();
});

/**
 * 1. Favorit CCTV via localStorage (Khusus Halaman Live CCTV)
 */
function initFavorites() {
  const gridContainer = document.getElementById('cctvGridContainer');
  if (!gridContainer) return;

  const cards = Array.from(gridContainer.querySelectorAll(':scope > .cctv-item-card'));
  if (!cards.length) return;

  // Catat urutan asli server sekali di awal untuk setiap kartu
  cards.forEach((card, index) => {
    if (!card.dataset.originalIndex) {
      card.dataset.originalIndex = index.toString();
    }
  });

  // Ambil data favorit tersimpan yang valid
  let favorites = getValidFavorites();

  // Helper pembaruan visual kartu & tombol favorit
  function updateCardState(card, isFav) {
    const btn = card.querySelector('.cctv-fav-btn');
    const camName = card.getAttribute('data-name') || card.getAttribute('data-code') || 'Kamera';

    if (isFav) {
      card.classList.add('is-favorite');
      if (btn) {
        btn.classList.add('is-favorite');
        btn.setAttribute('aria-pressed', 'true');
        btn.setAttribute('title', 'Hapus dari favorit');
        btn.setAttribute('aria-label', 'Hapus dari favorit ' + camName);
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.remove('bi-star');
          icon.classList.add('bi-star-fill');
        }
      }
    } else {
      card.classList.remove('is-favorite');
      if (btn) {
        btn.classList.remove('is-favorite');
        btn.setAttribute('aria-pressed', 'false');
        btn.setAttribute('title', 'Tandai favorit');
        btn.setAttribute('aria-label', 'Tandai favorit ' + camName);
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.remove('bi-star-fill');
          icon.classList.add('bi-star');
        }
      }
    }
  }

  // Terapkan status favorit awal ke seluruh kartu
  cards.forEach(card => {
    const code = card.getAttribute('data-code');
    const isFav = favorites.includes(code);
    updateCardState(card, isFav);
  });

  // Fungsi pengurutan: Favorit di atas, disusul kartu biasa. Urutan asli terjaga di tiap kelompok.
  function sortCardsInGrid() {
    const focusedElement = document.activeElement;

    cards.sort((a, b) => {
      const aFav = a.classList.contains('is-favorite');
      const bFav = b.classList.contains('is-favorite');

      if (aFav && !bFav) return -1;
      if (!aFav && bFav) return 1;

      const aOrig = parseInt(a.dataset.originalIndex, 10);
      const bOrig = parseInt(b.dataset.originalIndex, 10);
      return aOrig - bOrig;
    });

    cards.forEach(card => {
      gridContainer.appendChild(card);
    });

    if (focusedElement && document.body.contains(focusedElement)) {
      try {
        focusedElement.focus();
      } catch (err) {}
    }
  }

  // Pengurutan awal saat halaman dibuka/dimuat
  sortCardsInGrid();

  // Event listener klik tombol bintang
  cards.forEach(card => {
    const btn = card.querySelector('.cctv-fav-btn');
    if (!btn) return;

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const code = card.getAttribute('data-code');
      const isCurrentlyFav = card.classList.contains('is-favorite');

      if (isCurrentlyFav) {
        favorites = favorites.filter(c => c !== code);
        updateCardState(card, false);
      } else {
        if (!favorites.includes(code)) {
          favorites.push(code);
        }
        updateCardState(card, true);
      }

      saveFavorites(favorites);
      sortCardsInGrid();

      // Sinkronkan filter pencarian jika aktif
      if (typeof window.applyCctvFilter === 'function') {
        window.applyCctvFilter();
      }
    });
  });
}

/**
 * Validasi dan pembacaan aman dari localStorage
 */
function getValidFavorites() {
  try {
    const raw = localStorage.getItem('dishub_fav_cctv');
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed)) {
      const uniqueCodes = [];
      parsed.forEach(item => {
        if (typeof item === 'string' && item.trim()) {
          const code = item.trim();
          if (!uniqueCodes.includes(code)) {
            uniqueCodes.push(code);
          }
        }
      });
      return uniqueCodes;
    }
  } catch (err) {
    console.warn('Gagal membaca favorit dari localStorage:', err);
  }
  return [];
}

/**
 * Penyimpanan aman ke localStorage
 */
function saveFavorites(favArray) {
  try {
    localStorage.setItem('dishub_fav_cctv', JSON.stringify(favArray));
  } catch (err) {
    console.warn('Gagal menyimpan favorit ke localStorage:', err);
  }
}

/**
 * 2. Filter Kategori & Pencarian Live CCTV (Real-time & Kombinasi)
 */
function initCctvFilterAndSearch() {
  const searchInput = document.getElementById('cctvSearchInput');
  const filterPills = document.querySelectorAll('.filter-pill');
  const emptyState = document.getElementById('cctvEmptyState');
  const resetBtn = document.getElementById('resetFilterBtn');
  const gridContainer = document.getElementById('cctvGridContainer');

  if (!gridContainer) return;

  let currentCategory = 'semua';
  let currentSearch = '';

  function applyFilter() {
    let visibleCount = 0;
    const searchVal = currentSearch.toLowerCase().trim();
    const cctvCards = gridContainer.querySelectorAll(':scope > .cctv-item-card');

    cctvCards.forEach(card => {
      const code = (card.getAttribute('data-code') || '').toLowerCase();
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const location = (card.getAttribute('data-location') || '').toLowerCase();
      const category = (card.getAttribute('data-category') || '').toLowerCase();
      const intersection = (card.getAttribute('data-intersection') || '').toLowerCase();

      // Check Category / Intersection
      const matchCat = (currentCategory === 'semua' || 
        category === currentCategory || 
        intersection === currentCategory
      );

      // Check Search (nama, kode, lokasi, arah)
      const matchSearch = (!searchVal ||
        name.includes(searchVal) ||
        code.includes(searchVal) ||
        location.includes(searchVal)
      );

      if (matchCat && matchSearch) {
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (emptyState) {
      if (visibleCount === 0) {
        emptyState.classList.remove('d-none');
      } else {
        emptyState.classList.add('d-none');
      }
    }
  }

  // Ekspos fungsi applyFilter ke window agar sinkron saat favorit diubah
  window.applyCctvFilter = applyFilter;

  // Filter Pill Clicks
  filterPills.forEach(pill => {
    pill.addEventListener('click', function (e) {
      e.preventDefault();
      filterPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      currentCategory = (pill.getAttribute('data-filter') || 'semua').toLowerCase();
      applyFilter();
    });
  });

  // Search Input Keyup/Input
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentSearch = searchInput.value;
      applyFilter();
    });
  }

  // Reset Button
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      if (searchInput) searchInput.value = '';
      currentSearch = '';
      currentCategory = 'semua';
      filterPills.forEach(p => {
        if (p.getAttribute('data-filter') === 'semua') {
          p.classList.add('active');
        } else {
          p.classList.remove('active');
        }
      });
      applyFilter();
    });
  }
}

/**
 * 3. Inisialisasi Peta Interaktif
 */
function initMapMarkers() {
  const markers = document.querySelectorAll('.map-pin-camera, .map-pin');
  markers.forEach(marker => {
    const showTooltip = () => {
      const tooltip = marker.querySelector('.map-pin-tooltip');
      if (tooltip) tooltip.style.opacity = '1';
    };
    const hideTooltip = () => {
      const tooltip = marker.querySelector('.map-pin-tooltip');
      if (tooltip) tooltip.style.opacity = '0';
    };

    marker.addEventListener('mouseenter', showTooltip);
    marker.addEventListener('mouseleave', hideTooltip);
    marker.addEventListener('focus', showTooltip);
    marker.addEventListener('blur', hideTooltip);
  });
}

