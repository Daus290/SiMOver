/**
 * Pemutar Video HLS CCTV - SiMover Dishub Banjarbaru
 * Modul JavaScript untuk Live CCTV Player (ATCS & Vivotek)
 */
(function () {
  'use strict';

  const video = document.getElementById('cctvVideoPlayer');
  if (!video) return;

  const streamUrl = video.getAttribute('data-stream-url');
  if (!streamUrl) return;

  const isVivotekCam = video.getAttribute('data-is-vivotek') === 'true';
  const loadingOverlay = document.getElementById('playerLoadingOverlay');
  const errorOverlay = document.getElementById('playerErrorOverlay');
  const errorMessageEl = document.getElementById('playerErrorMessage');
  const liveDot = document.getElementById('liveDotIndicator');
  const liveBadgeText = document.getElementById('playerLiveBadgeText');
  const durationText = document.getElementById('videoDurationText');
  const resolutionText = document.getElementById('playerResolutionText');
  const btnToolbarPlay = document.getElementById('btnToolbarPlay');
  const btnRetryStream = document.getElementById('btnRetryStream');
  const btnClosePlayer = document.getElementById('btnClosePlayer');

  let hlsInstance = null;
  let retryCount = 0;
  const maxRetries = 5;
  let isDestroyed = false;
  let watchdogTimer = null;
  let lastTimeUpdate = Date.now();
  let lastVideoTime = -1;

  // Pastikan video selalu default muted sebelum play untuk mematuhi kebijakan autoplay browser
  video.muted = true;
  video.defaultMuted = true;

  function startWatchdog() {
    stopWatchdog();
    lastTimeUpdate = Date.now();
    lastVideoTime = video.currentTime;
    watchdogTimer = setInterval(() => {
      if (isDestroyed || video.paused || video.readyState < 2) {
        lastTimeUpdate = Date.now();
        return;
      }
      if (video.currentTime !== lastVideoTime) {
        lastVideoTime = video.currentTime;
        lastTimeUpdate = Date.now();
      } else {
        const elapsed = Date.now() - lastTimeUpdate;
        if (elapsed > 25000) {
          console.warn('Watchdog timeout: Video terhenti (tidak ada data baru).');
          showError('Aliran data video terhenti (Timeout). Silakan coba lagi.');
          destroyPlayer();
          stopWatchdog();
        }
      }
    }, 1000);
  }

  function stopWatchdog() {
    if (watchdogTimer) {
      clearInterval(watchdogTimer);
      watchdogTimer = null;
    }
  }

  function formatSeconds(sec) {
    if (isNaN(sec) || sec < 0) return '00:00';
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  }

  function hideOverlays() {
    if (loadingOverlay) loadingOverlay.classList.add('d-none');
    if (errorOverlay) errorOverlay.classList.add('d-none');
    if (btnToolbarPlay) btnToolbarPlay.classList.add('d-none');
  }

  function setPlayerState(state) {
    if (!liveDot || !liveBadgeText) return;
    liveDot.className = 'cctv-live-dot';

    switch (state) {
      case 'live':
        liveBadgeText.textContent = 'LIVE';
        break;
      case 'connecting':
        liveDot.classList.add('dot-connecting');
        liveBadgeText.textContent = 'MEMUAT';
        break;
      case 'buffering':
        liveDot.classList.add('dot-connecting');
        liveBadgeText.textContent = 'BUFFERING';
        break;
      case 'error':
        liveDot.classList.add('dot-error');
        liveBadgeText.textContent = 'TERPUTUS';
        break;
      case 'paused':
        liveDot.classList.add('dot-connecting');
        liveBadgeText.textContent = 'STANDBY';
        break;
    }
  }

  function showError(msg) {
    hideOverlays();
    setPlayerState('error');
    if (errorMessageEl) {
      errorMessageEl.textContent = msg || 'Tidak dapat terhubung ke aliran video upstream.';
    }
    if (errorOverlay) errorOverlay.classList.remove('d-none');
  }

  function destroyPlayer() {
    isDestroyed = true;
    stopWatchdog();
    stopOverlayPolling();
    if (hlsInstance) {
      try {
        hlsInstance.stopLoad();
        hlsInstance.detachMedia();
        hlsInstance.destroy();
      } catch (err) {}
      hlsInstance = null;
    }
    if (video) {
      try {
        video.pause();
        video.removeAttribute('src');
        video.load();
      } catch (err) {}
    }
  }

  function safePlay() {
    video.muted = true;
    video.defaultMuted = true;
    const playPromise = video.play();
    if (playPromise !== undefined) {
      playPromise.then(() => {
        hideOverlays();
      }).catch((err) => {
        if (err && err.name === 'AbortError') {
          return;
        }
        if (err && err.name === 'NotAllowedError') {
          hideOverlays();
          if (btnToolbarPlay) btnToolbarPlay.classList.remove('d-none');
          setPlayerState('paused');
          return;
        }
        console.warn('Playback notice:', err);
      });
    }
  }

  function startPlayer() {
    isDestroyed = false;
    startWatchdog();
    startOverlayPolling();
    hideOverlays();
    if (loadingOverlay) loadingOverlay.classList.remove('d-none');
    setPlayerState('connecting');

    if (hlsInstance) {
      try { hlsInstance.destroy(); } catch (e) {}
      hlsInstance = null;
    }

    if (window.Hls && Hls.isSupported()) {
      hlsInstance = new Hls({
        enableWorker: true,
        lowLatencyMode: false,
        backBufferLength: 10,
        maxBufferLength: 6,
        maxMaxBufferLength: 10,
        liveSyncDurationCount: 2,
        startFragPrefetch: true,
        manifestLoadingTimeOut: 10000,
        levelLoadingTimeOut: 10000,
        fragLoadingTimeOut: 15000,
      });

      hlsInstance.attachMedia(video);

      hlsInstance.on(Hls.Events.MEDIA_ATTACHED, function () {
        if (!isDestroyed) hlsInstance.loadSource(streamUrl);
      });

      hlsInstance.on(Hls.Events.MANIFEST_PARSED, function () {
        if (isDestroyed) return;
        safePlay();
      });

      hlsInstance.on(Hls.Events.FRAG_BUFFERED, function () {
        if (isDestroyed) return;
        hideOverlays();
        if (video.paused) {
          safePlay();
        }
      });

      hlsInstance.on(Hls.Events.ERROR, function (event, data) {
        if (isDestroyed) return;
        if (data.fatal) {
          switch (data.type) {
            case Hls.ErrorTypes.NETWORK_ERROR:
              if (retryCount < maxRetries) {
                retryCount++;
                setPlayerState('connecting');
                hlsInstance.startLoad();
              } else {
                showError(isVivotekCam 
                  ? 'Koneksi ke aliran stream Vivotek terputus. Pastikan relay lokal aktif dan perangkat terhubung ke jaringan kantor.' 
                  : 'Koneksi ke aliran upstream ATCS terputus. Silakan coba lagi.');
              }
              break;
            case Hls.ErrorTypes.MEDIA_ERROR:
              hlsInstance.recoverMediaError();
              break;
            default:
              showError('Tayangan tidak tersedia saat ini.');
              destroyPlayer();
              break;
          }
        } else if (data.details === 'bufferStalledError') {
          setPlayerState('buffering');
        }
      });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
      video.src = streamUrl;
      video.addEventListener('loadedmetadata', function () {
        safePlay();
      });
      video.addEventListener('error', function () {
        showError('Tayangan tidak tersedia saat ini.');
      });
    } else {
      showError('Browser ini tidak mendukung pemutaran video HLS.');
    }
  }

  video.addEventListener('loadeddata', function () {
    hideOverlays();
    if (video.paused) safePlay();
  });

  video.addEventListener('canplay', function () {
    hideOverlays();
    if (video.paused) safePlay();
  });

  video.addEventListener('playing', function () {
    hideOverlays();
    if (video.currentTime > 0) {
      setPlayerState('live');
    }
    if (video.videoWidth && video.videoHeight && resolutionText) {
      resolutionText.textContent = `${video.videoWidth} x ${video.videoHeight}`;
    }
  });

  video.addEventListener('waiting', function () {
    setPlayerState('buffering');
  });

  video.addEventListener('timeupdate', function () {
    if (durationText && video.currentTime > 0) {
      durationText.textContent = formatSeconds(video.currentTime);
    }
    if (video.currentTime > 0 && !video.paused) {
      setPlayerState('live');
    }
  });

  video.addEventListener('pause', function () {
    if (!document.hidden && (!errorOverlay || errorOverlay.classList.contains('d-none'))) {
      setPlayerState('paused');
    }
  });

  if (btnToolbarPlay) {
    btnToolbarPlay.addEventListener('click', function () {
      safePlay();
    });
  }

  if (loadingOverlay) {
    loadingOverlay.style.cursor = 'pointer';
    loadingOverlay.addEventListener('click', function () {
      safePlay();
    });
  }

  if (btnRetryStream) {
    btnRetryStream.addEventListener('click', function () {
      retryCount = 0;
      startPlayer();
    });
  }

  // Pemulihan alami saat tab kembali aktif: lompat ke live edge dan pastikan langsung bergerak
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && !isDestroyed && video) {
      if (!errorOverlay || errorOverlay.classList.contains('d-none')) {
        if (hlsInstance) {
          try {
            hlsInstance.startLoad();
            const livePos = hlsInstance.liveSyncPosition;
            if (livePos && isFinite(livePos) && Math.abs(video.currentTime - livePos) > 1.5) {
              video.currentTime = livePos;
            }
          } catch (e) {}
        }
        safePlay();
      }
    }
  });

  if (btnClosePlayer) {
    btnClosePlayer.addEventListener('click', destroyPlayer);
  }

  // =========================================================================
  // MODUL KOTAK DETEKSI (OVERLAY CANVAS & TOGGLE) - HANYA KAMERA UJI ATCS-TEST
  // Kotak digambar secara real-time di atas kanvas video player.
  // =========================================================================
  const isAiTest = video.getAttribute('data-is-ai-test') === 'true';
  const overlayCanvas = document.getElementById('aiOverlayCanvas');
  const btnToggleAiOverlay = document.getElementById('btnToggleAiOverlay');
  const textToggleOverlay = document.getElementById('textToggleOverlay');
  const iconToggleOverlay = document.getElementById('iconToggleOverlay');
  const overlayStatusText = document.getElementById('aiOverlayStatusText');

  const OVERLAY_POLL_ACTIVE_MS = 250;
  const OVERLAY_POLL_IDLE_MS = 3000;

  let overlayPollTimer = null;
  let overlayAvailable = false;
  let overlayEnabled = true; // pilihan pengguna selama halaman terbuka (default ON)
  let lastOverlay = null;

  function clearOverlay() {
    if (!overlayCanvas) return;
    const ctx = overlayCanvas.getContext('2d');
    if (ctx) ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
  }

  function setOverlayStatus(text) {
    if (overlayStatusText) overlayStatusText.textContent = text;
  }

  function updateToggleUI() {
    if (!btnToggleAiOverlay) return;
    btnToggleAiOverlay.disabled = !overlayAvailable;
    btnToggleAiOverlay.setAttribute('aria-disabled', overlayAvailable ? 'false' : 'true');
    btnToggleAiOverlay.setAttribute('aria-pressed', overlayAvailable && overlayEnabled ? 'true' : 'false');
    btnToggleAiOverlay.classList.toggle('btn-outline-primary', overlayAvailable && overlayEnabled);
    btnToggleAiOverlay.classList.toggle('btn-outline-secondary', !(overlayAvailable && overlayEnabled));

    let stateHtml = '<strong class="text-secondary">Belum Tersedia</strong>';
    if (overlayAvailable) {
      stateHtml = overlayEnabled ? '<strong class="text-success">ON</strong>' : '<strong class="text-secondary">OFF</strong>';
    }
    if (textToggleOverlay) textToggleOverlay.innerHTML = 'Kotak Deteksi: ' + stateHtml;
    if (iconToggleOverlay) iconToggleOverlay.className = 'bi bi-bounding-box ' + (overlayAvailable && overlayEnabled ? 'text-primary' : 'text-muted');
    if (overlayCanvas) overlayCanvas.style.display = overlayAvailable && overlayEnabled ? 'block' : 'none';
  }

  if (isAiTest && btnToggleAiOverlay) {
    updateToggleUI();
    btnToggleAiOverlay.addEventListener('click', function () {
      if (!overlayAvailable) return;
      overlayEnabled = !overlayEnabled;
      updateToggleUI();
      if (!overlayEnabled) clearOverlay();
    });
  }

  function startOverlayPolling() {
    if (!isAiTest || !overlayCanvas) return;
    stopOverlayPolling();

    async function poll() {
      if (isDestroyed) return;
      let nextDelay = OVERLAY_POLL_IDLE_MS;

      try {
        let resp = null;
        try {
          resp = await fetch('http://localhost:8088/overlay', { cache: 'no-store' });
        } catch (e) {
          resp = await fetch(window.location.origin + '/api/ai/overlay', { cache: 'no-store' });
        }
        const data = resp && resp.ok ? await resp.json() : null;
        if (data && data.available && (data.camera_code === video.getAttribute('data-camera-code') || !data.camera_code)) {
          overlayAvailable = true;
          lastOverlay = data;
          nextDelay = OVERLAY_POLL_ACTIVE_MS;
          renderOverlay();
        } else {
          overlayAvailable = false;
          lastOverlay = null;
          clearOverlay();
          setOverlayStatus(data && data.reason ? data.reason : 'Kotak deteksi aktif saat ada kendaraan melintas.');
        }
      } catch (err) {
        overlayAvailable = false;
        lastOverlay = null;
        clearOverlay();
        setOverlayStatus('Status kotak deteksi tidak dapat dibaca.');
      }

      updateToggleUI();
      if (!isDestroyed) overlayPollTimer = setTimeout(poll, nextDelay);
    }

    poll();
  }

  function stopOverlayPolling() {
    if (overlayPollTimer) {
      clearTimeout(overlayPollTimer);
      overlayPollTimer = null;
    }
    lastOverlay = null;
    clearOverlay();
  }

  // Area gambar video sebenarnya di dalam elemen (object-fit: contain / letterbox)
  function videoContentRect() {
    const rect = video.getBoundingClientRect();
    const vw = video.videoWidth;
    const vh = video.videoHeight;
    if (!vw || !vh || rect.width <= 0 || rect.height <= 0) return null;
    const scale = Math.min(rect.width / vw, rect.height / vh);
    const w = vw * scale;
    const h = vh * scale;
    return { width: rect.width, height: rect.height, x: (rect.width - w) / 2, y: (rect.height - h) / 2, w: w, h: h };
  }

  function renderOverlay() {
    const data = lastOverlay;
    if (!overlayCanvas || !data || !overlayEnabled || video.paused || video.seeking) {
      clearOverlay();
      return;
    }

    // Periksa kesegaran data overlay (maksimal 4 detik)
    const ageSec = typeof data.age_seconds === 'number' ? data.age_seconds : 0;
    if (ageSec > 4.0) {
      clearOverlay();
      setOverlayStatus('Kotak disembunyikan: data deteksi sudah berlalu.');
      return;
    }

    const area = videoContentRect();
    if (!area) {
      clearOverlay();
      return;
    }
    if (overlayCanvas.width !== Math.round(area.width) || overlayCanvas.height !== Math.round(area.height)) {
      overlayCanvas.width = Math.round(area.width);
      overlayCanvas.height = Math.round(area.height);
    }

    const ctx = overlayCanvas.getContext('2d');
    ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
    const sx = area.w / data.frame_width;
    const sy = area.h / data.frame_height;

    (Array.isArray(data.boxes) ? data.boxes : []).forEach(function (box) {
      const bx = area.x + box.x1 * sx;
      const by = area.y + box.y1 * sy;
      const bw = (box.x2 - box.x1) * sx;
      const bh = (box.y2 - box.y1) * sy;
      const isOd = box.is_over_dimension === true;
      const labelText = String(box.label || 'Belum terklasifikasi').slice(0, 60);

      ctx.save();
      ctx.strokeStyle = isOd ? '#ef4444' : '#10b981';
      ctx.lineWidth = isOd ? 3 : 2;
      ctx.strokeRect(bx, by, bw, bh);

      ctx.font = 'bold 11px system-ui, -apple-system, sans-serif';
      const textW = ctx.measureText(labelText).width + 12;
      const labelY = Math.max(0, by - 20);
      ctx.fillStyle = isOd ? '#dc2626' : 'rgba(15, 23, 42, 0.85)';
      ctx.fillRect(bx, labelY, textW, 20);
      ctx.fillStyle = '#ffffff';
      ctx.fillText(labelText, bx + 6, labelY + 14);
      ctx.restore();
    });

    const count = Array.isArray(data.boxes) ? data.boxes.length : 0;
    setOverlayStatus('Kotak deteksi aktif (' + count + ' objek terdeteksi)');
  }

  ['seeking', 'pause', 'emptied'].forEach(function (evt) {
    video.addEventListener(evt, clearOverlay);
  });
  window.addEventListener('resize', function () {
    if (lastOverlay) renderOverlay();
  });

  window.addEventListener('beforeunload', destroyPlayer);
  window.addEventListener('pagehide', destroyPlayer);

  startPlayer();
})();
