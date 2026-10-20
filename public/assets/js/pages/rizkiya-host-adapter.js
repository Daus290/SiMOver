/**
 * Adapter Host Core Rizkiya - SiMover
 *
 * Berjalan di LUAR blok core, di dalam iframe host terisolasi. Tugasnya hanya:
 * 1. Menyambungkan playlist HLS kamera uji ke elemen video milik core (#cctv-video).
 * 2. Mengamati keluaran yang memang terlihat dari core (teks #status-model dan
 *    #hasil-deteksi) serta error tak tertangani, lalu melaporkannya ke halaman induk.
 *
 * Adapter tidak memanggil, mengganti atau mem-patch fungsi core, ONNX session,
 * ukuran input, threshold maupun interval inferensi.
 */
(function () {
  'use strict';

  var MESSAGE_SOURCE = 'simover-rizkiya-host';
  var config = {};
  try {
    config = JSON.parse(document.getElementById('rizkiyaHostConfig').textContent);
  } catch (e) {
    return;
  }

  function post(type, payload) {
    if (window.parent === window) return;
    var msg = Object.assign({ source: MESSAGE_SOURCE, type: type, at: new Date().toISOString() }, payload || {});
    window.parent.postMessage(msg, window.location.origin);
  }

  post('host', { state: 'ready', coreSha256: config.coreSha256 });

  // ---------------------------------------------------------------------
  // 1. Sumber video untuk elemen video core (core sendiri tidak memuat src)
  // ---------------------------------------------------------------------
  var video = document.getElementById('cctv-video');
  if (!video) {
    post('video', { state: 'missing', detail: 'Elemen #cctv-video tidak ditemukan pada core.' });
  } else {
    video.addEventListener('playing', function () {
      post('video', { state: 'playing', width: video.videoWidth, height: video.videoHeight });
    });
    video.addEventListener('waiting', function () { post('video', { state: 'buffering' }); });
    video.addEventListener('error', function () { post('video', { state: 'error' }); });

    if (window.Hls && window.Hls.isSupported()) {
      var hls = new window.Hls({ enableWorker: true, lowLatencyMode: false, backBufferLength: 30 });
      hls.on(window.Hls.Events.ERROR, function (event, data) {
        if (data && data.fatal) {
          post('video', { state: 'error', detail: data.type || 'hls' });
          if (data.type === window.Hls.ErrorTypes.NETWORK_ERROR) hls.startLoad();
          else if (data.type === window.Hls.ErrorTypes.MEDIA_ERROR) hls.recoverMediaError();
        }
      });
      hls.attachMedia(video);
      hls.on(window.Hls.Events.MEDIA_ATTACHED, function () { hls.loadSource(config.streamUrl); });
      window.addEventListener('pagehide', function () { try { hls.destroy(); } catch (e) {} });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
      video.src = config.streamUrl;
    } else {
      post('video', { state: 'error', detail: 'Browser tidak mendukung HLS.' });
    }
  }

  // ---------------------------------------------------------------------
  // 2. Pengamatan keluaran DOM core
  // ---------------------------------------------------------------------
  function observeText(id, onChange) {
    var el = document.getElementById(id);
    if (!el) return false;
    var last = null;
    function emit() {
      var text = (el.textContent || '').trim();
      if (text !== last) {
        last = text;
        onChange(text);
      }
    }
    new MutationObserver(emit).observe(el, { childList: true, characterData: true, subtree: true });
    emit();
    return true;
  }

  // Deteksi "tidak ada keluaran": model dimuat + video berjalan, tetapi teks hasil core tidak berubah.
  // Error inferensi core (promise tanpa catch) tidak selalu sampai ke event unhandledrejection,
  // sehingga kondisi ini diamati dari DOM, bukan dengan mengubah/membungkus fungsi core.
  var NO_OUTPUT_AFTER_MS = 10000;
  var modelLoadedAt = 0;
  var lastResultChangeAt = 0;
  var stallReported = false;
  setInterval(function () {
    var videoRunning = video && video.readyState >= 2 && !video.paused;
    var since = Math.max(modelLoadedAt, lastResultChangeAt);
    if (modelLoadedAt && videoRunning && Date.now() - since > NO_OUTPUT_AFTER_MS) {
      if (!stallReported) {
        stallReported = true;
        post('inference_stalled', { seconds: Math.round((Date.now() - since) / 1000) });
      }
    }
  }, 2000);

  var hasStatus = observeText('status-model', function (text) {
    var state = 'loading';
    if (/^Terhubung/i.test(text)) {
      state = 'loaded';
      modelLoadedAt = Date.now();
    } else if (/^Gagal/i.test(text)) {
      state = 'failed';
    }
    post('model', { state: state, text: text });
  });

  // Teks hasil core: "Terdeteksi: <nama> (<persen>%)" atau
  // "Tidak ada truk / Kurang yakin (<nama>: <persen>%)". Nilai diambil apa adanya.
  var hasResult = observeText('hasil-deteksi', function (text) {
    var parsed = { kind: 'lain', label: null, percent: null };
    var m = text.match(/^Terdeteksi:\s*(.+?)\s*\(([\d.]+)%\)$/);
    if (m) {
      parsed = { kind: 'terdeteksi', label: m[1], percent: Number(m[2]) };
    } else if ((m = text.match(/^Tidak ada truk \/ Kurang yakin \((.+?):\s*([\d.]+)%\)$/))) {
      parsed = { kind: 'kurang_yakin', label: m[1], percent: Number(m[2]) };
    } else if (/^Menunggu deteksi/i.test(text)) {
      parsed.kind = 'menunggu';
    }
    if (parsed.kind !== 'menunggu') {
      lastResultChangeAt = Date.now();
      stallReported = false;
    }
    post('result', Object.assign({ text: text }, parsed));
  });

  if (!hasStatus || !hasResult) {
    post('contract', { state: 'mismatch', detail: 'Elemen keluaran core (#status-model / #hasil-deteksi) tidak ditemukan.' });
  }

  // ---------------------------------------------------------------------
  // 3. Error tak tertangani dari core (misalnya kegagalan session.run)
  // ---------------------------------------------------------------------
  var errorCount = 0;
  var lastErrorPost = 0;
  function reportError(message) {
    errorCount++;
    var now = Date.now();
    if (now - lastErrorPost < 1000) return; // batasi laporan, core memanggil inferensi tiap 500 ms
    lastErrorPost = now;
    post('inference_error', { count: errorCount, message: String(message || 'Error tidak diketahui').slice(0, 300) });
  }
  window.addEventListener('unhandledrejection', function (e) {
    var r = e.reason;
    reportError(r && r.message ? r.message : r);
  });
  window.addEventListener('error', function (e) {
    reportError(e.message);
  });
})();
