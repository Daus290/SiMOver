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
      if (isDestroyed || video.paused) {
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
        backBufferLength: 30,
      });

      hlsInstance.attachMedia(video);

      hlsInstance.on(Hls.Events.MEDIA_ATTACHED, function () {
        if (!isDestroyed) hlsInstance.loadSource(streamUrl);
      });

      hlsInstance.on(Hls.Events.MANIFEST_PARSED, function () {
        if (isDestroyed) return;
        safePlay();
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

  if (btnRetryStream) {
    btnRetryStream.addEventListener('click', function () {
      retryCount = 0;
      startPlayer();
    });
  }

  // Pemulihan alami saat tab kembali aktif tanpa memaksa pause di background
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && !isDestroyed && video && video.paused) {
      if (!errorOverlay || errorOverlay.classList.contains('d-none')) {
        safePlay();
      }
    }
  });

  if (btnClosePlayer) {
    btnClosePlayer.addEventListener('click', destroyPlayer);
  }

  window.addEventListener('beforeunload', destroyPlayer);
  window.addEventListener('pagehide', destroyPlayer);

  startPlayer();
})();
