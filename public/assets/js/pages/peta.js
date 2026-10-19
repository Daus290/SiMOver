/**
 * Peta Interaktif Leaflet - SiMover Dishub Banjarbaru
 * Modul JavaScript Halaman Peta CCTV
 */
(function () {
  'use strict';

  const dataScript = document.getElementById('peta-metadata');
  if (!dataScript) return;
  let config = {};
  try {
    config = JSON.parse(dataScript.textContent);
  } catch (err) {
    console.error('Gagal membaca metadata peta:', err);
    return;
  }

  const intersectionsData = config.intersections || [];
  const camerasData = config.cameras || [];
  const focusCamera = config.focusCamera || null;
  const focusIntersection = config.focusIntersection || null;
  const baseLiveCctvUrl = config.baseLiveCctvUrl || '';
  // Ambang Zoom Dekat (Layer individual kamera aktif mulai zoom 18)
  const ZOOM_THRESHOLD = 18;

  // 2. Inisialisasi Elemen Peta
  const mapElement = document.getElementById('dishubLeafletMap');
  if (!mapElement) return;

  // Inisialisasi Leaflet
  const map = L.map('dishubLeafletMap', {
    zoomControl: true,
    scrollWheelZoom: true,
    maxZoom: 21,
  });

  // Basemap OpenStreetMap Resmi
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxNativeZoom: 19,
    maxZoom: 21,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors'
  }).addTo(map);

  // Layer Groups
  const junctionLayer = L.layerGroup();
  const cameraLayer = L.layerGroup();

  // Koleksi Bounds untuk 4 Simpang
  const bounds = L.latLngBounds([]);

  // Modal Bootstrap Reference
  const modalEl = document.getElementById('intersectionModal');
  const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;
  const modalTitle = document.getElementById('intersectionModalLabel');
  const modalSubtitle = document.getElementById('intersectionModalSubtitle');
  const modalDesc = document.getElementById('modalIntersectionDescription');
  const modalCamList = document.getElementById('modalCameraList');
  const btnModalZoom = document.getElementById('btnModalZoomToIntersection');

  let activeModalLocation = null;

  // 3. Render Marker Persimpangan (Zoom Jauh < 18)
  intersectionsData.forEach(loc => {
    const lat = parseFloat(loc.center_lat);
    const lng = parseFloat(loc.center_lng);
    bounds.extend([lat, lng]);

    const junctionIcon = L.divIcon({
      className: 'bjb-junction-div-icon',
      html: `
        <div class="bjb-marker-junction" tabindex="0" role="button" aria-label="${escapeHtml(loc.name)} · ${loc.installed_count} kamera">
          <div class="bjb-marker-junction-icon">
            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
          </div>
          <div class="bjb-marker-junction-text">
            <span class="bjb-marker-junction-title">${escapeHtml(loc.short_name || loc.name)}</span>
            <span class="bjb-marker-junction-count">${loc.installed_count} kamera</span>
          </div>
        </div>
      `,
      iconSize: [210, 40],
      iconAnchor: [105, 20]
    });

    const marker = L.marker([lat, lng], { icon: junctionIcon, keyboard: true });
    marker.on('click', () => {
      openIntersectionModal(loc.id);
    });

    junctionLayer.addLayer(marker);
  });

  // 4. Render Marker Kamera Individual (Zoom Dekat >= 18)
  camerasData.forEach(cam => {
    const lat = parseFloat(cam.display_latitude || cam.latitude);
    const lng = parseFloat(cam.display_longitude || cam.longitude);

    const isPtz = (cam.type === 'ptz');
    const isUnavailable = (!cam.is_installed);
    const isOffline = (cam.status === 'offline');
    
    let statusClass = 'status-online';
    if (isUnavailable) statusClass = 'status-unavailable';
    else if (isOffline) statusClass = 'status-offline';

    let iconSymbol = isPtz ? 'bi-arrows-move' : 'bi-camera-video-fill';
    if (isUnavailable) iconSymbol = 'bi-dash-circle';
    else if (isOffline) iconSymbol = 'bi-camera-video-off-fill';

    let phaseText = isPtz ? 'PTZ' : ('F' + (cam.phase || ''));
    let typeClass = isPtz ? 'type-ptz' : 'type-fixed';

    let dirText = cam.direction ? `&bull; ${escapeHtml(cam.direction)}` : '';
    if (isUnavailable) dirText = '&bull; Belum tersedia';

    const camIcon = L.divIcon({
      className: 'bjb-cam-div-icon',
      html: `
        <div class="bjb-marker-camera ${statusClass} ${typeClass}" 
             tabindex="0" 
             role="button" 
             title="${escapeHtml(cam.full_name)} (${escapeHtml(cam.status_label)})"
             aria-label="${escapeHtml(cam.code)} - ${escapeHtml(cam.name)} ${escapeHtml(cam.direction || '')}">
          <div class="bjb-cam-icon-wrap">
            <i class="bi ${iconSymbol}" aria-hidden="true"></i>
          </div>
          <div class="bjb-cam-label">
            <span class="bjb-cam-code">${escapeHtml(cam.code)}</span>
            <span class="bjb-cam-dir">${dirText}</span>
          </div>
        </div>
      `,
      iconSize: [140, 32],
      iconAnchor: [16, 16]
    });

    const marker = L.marker([lat, lng], { 
      icon: camIcon, 
      keyboard: true,
      zIndexOffset: isPtz ? 50 : 10
    });

    marker.on('click', () => {
      // Klik marker individual langsung membuka halaman /live-cctv?cctv=KODE
      window.location.href = baseLiveCctvUrl + encodeURIComponent(cam.code);
    });

    cameraLayer.addLayer(marker);
  });

  // 5. Logika Switch Layer Berdasarkan Zoom Level
  function updateZoomLayers() {
    const currentZoom = map.getZoom();
    if (currentZoom >= ZOOM_THRESHOLD) {
      if (map.hasLayer(junctionLayer)) map.removeLayer(junctionLayer);
      if (!map.hasLayer(cameraLayer)) map.addLayer(cameraLayer);
    } else {
      if (map.hasLayer(cameraLayer)) map.removeLayer(cameraLayer);
      if (!map.hasLayer(junctionLayer)) map.addLayer(junctionLayer);
    }
  }

  map.on('zoomend', updateZoomLayers);

  // 6. Penentuan Tampilan Awal (Initial View)
  if (focusCamera) {
    const targetLat = parseFloat(focusCamera.display_latitude || focusCamera.latitude);
    const targetLng = parseFloat(focusCamera.display_longitude || focusCamera.longitude);
    map.setView([targetLat, targetLng], 19);
    updateZoomLayers();
  } else if (focusIntersection) {
    const targetLat = parseFloat(focusIntersection.center_lat);
    const targetLng = parseFloat(focusIntersection.center_lng);
    map.setView([targetLat, targetLng], 19);
    updateZoomLayers();
  } else {
    // FitBounds seluruh 4 simpang dengan padding yang memadai
    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
    updateZoomLayers();
  }

  // 7. Tombol Layar Penuh (Fullscreen)
  const btnFullscreenMap = document.getElementById('btnToggleFullscreenMap');
  const mapWrapperEl = document.querySelector('.leaflet-map-wrapper');

  function updateFullscreenBtnState() {
    const isFull = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    if (mapWrapperEl) {
      mapWrapperEl.classList.toggle('is-fullscreen', isFull);
    }
    if (btnFullscreenMap) {
      const icon = btnFullscreenMap.querySelector('i');
      const text = btnFullscreenMap.querySelector('span');
      if (isFull) {
        if (icon) icon.className = 'bi bi-fullscreen-exit';
        if (text) text.textContent = 'Keluar Layar Penuh';
        btnFullscreenMap.title = 'Keluar dari layar penuh';
      } else {
        if (icon) icon.className = 'bi bi-arrows-fullscreen';
        if (text) text.textContent = 'Tampilkan Layar Penuh';
        btnFullscreenMap.title = 'Tampilkan layar penuh';
      }
    }
    // Refresh ukuran viewport Leaflet secara bertahap
    map.invalidateSize();
    setTimeout(() => map.invalidateSize(), 50);
    setTimeout(() => map.invalidateSize(), 150);
    setTimeout(() => map.invalidateSize(), 300);
    setTimeout(() => map.invalidateSize(), 500);
  }

  if (btnFullscreenMap && mapWrapperEl) {
    btnFullscreenMap.addEventListener('click', function () {
      if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
        if (mapWrapperEl.requestFullscreen) {
          mapWrapperEl.requestFullscreen();
        } else if (mapWrapperEl.webkitRequestFullscreen) {
          mapWrapperEl.webkitRequestFullscreen();
        } else if (mapWrapperEl.msRequestFullscreen) {
          mapWrapperEl.msRequestFullscreen();
        }
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
          document.msExitFullscreen();
        }
      }
    });

    ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
      document.addEventListener(evt, updateFullscreenBtnState);
    });

    window.addEventListener('resize', () => {
      map.invalidateSize();
    });
  }

  // 8. Logika Modal Persimpangan
  function openIntersectionModal(locationId) {
    const loc = intersectionsData.find(item => item.id === locationId);
    if (!loc || !modalEl || !bsModal) return;

    activeModalLocation = loc;

    modalTitle.textContent = loc.name;
    modalSubtitle.textContent = `${loc.installed_count} kamera di persimpangan ini`;
    modalDesc.textContent = loc.description;

    let html = '';
    loc.cameras.forEach(cam => {
      const isUnavailable = (!cam.is_installed);
      const isOnline = (cam.status === 'online');
      
      let badgeHtml = '';
      if (isUnavailable) {
        badgeHtml = '<span class="cctv-status-badge status-unavailable" style="font-size: 0.72rem; padding: 2px 8px;">Belum tersedia</span>';
      } else if (isOnline) {
        badgeHtml = '<span class="cctv-status-badge status-online" style="font-size: 0.72rem; padding: 2px 8px;">Online</span>';
      } else {
        badgeHtml = '<span class="cctv-status-badge status-offline" style="font-size: 0.72rem; padding: 2px 8px;">Offline</span>';
      }

      const typeBadgeClass = (cam.type === 'ptz')
        ? 'bg-primary-subtle text-primary border'
        : 'bg-success-subtle text-success border';

      html += `
        <div class="list-group-item p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-dark">${escapeHtml(cam.code)}</span>
              <strong class="text-dark">${escapeHtml(cam.name)}</strong>
              <span class="badge ${typeBadgeClass}">${escapeHtml(cam.type.toUpperCase())}</span>
            </div>
            <div class="text-muted small">
              Arah: <strong>${escapeHtml(cam.direction || 'Belum ditentukan')}</strong>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            ${badgeHtml}
            <a href="${baseLiveCctvUrl + encodeURIComponent(cam.code)}" class="btn-dishub-primary btn-sm py-1 px-3">
              <i class="bi bi-play-circle me-1" aria-hidden="true"></i>Buka CCTV
            </a>
          </div>
        </div>
      `;
    });

    modalCamList.innerHTML = html;
    bsModal.show();
  }

  // Tombol di dalam modal untuk memperbesar simpang
  if (btnModalZoom) {
    btnModalZoom.addEventListener('click', function () {
      if (activeModalLocation) {
        if (bsModal) {
          bsModal.hide();
        }
        setTimeout(() => {
          const lat = parseFloat(activeModalLocation.center_lat);
          const lng = parseFloat(activeModalLocation.center_lng);
          map.setView([lat, lng], 19, { animate: true });
          updateZoomLayers();
          map.invalidateSize();
          mapElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
      }
    });
  }

  // Tombol-tombol kartu daftar di bawah peta
  document.querySelectorAll('.js-btn-open-modal').forEach(btn => {
    btn.addEventListener('click', function () {
      const locId = this.getAttribute('data-location-id');
      if (locId) openIntersectionModal(locId);
    });
  });

  document.querySelectorAll('.js-btn-zoom-intersection').forEach(btn => {
    btn.addEventListener('click', function () {
      const lat = parseFloat(this.getAttribute('data-lat'));
      const lng = parseFloat(this.getAttribute('data-lng'));
      if (!isNaN(lat) && !isNaN(lng)) {
        map.setView([lat, lng], 19);
        updateZoomLayers();
        // Smooth scroll ke atas peta
        mapElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });

  // Utility XSS Sanitizer
  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }


})();
