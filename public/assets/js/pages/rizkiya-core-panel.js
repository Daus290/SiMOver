/**
 * Panel Core Rizkiya pada player ATCS-TEST - SiMover
 *
 * Memuat host core terisolasi (iframe) hanya ketika pengguna menekan tombol,
 * lalu menampilkan status yang dilaporkan adapter host. Tidak ada hasil yang
 * dikirim ke server dari sini: browser publik bukan pengirim tepercaya.
 */
(function () {
  'use strict';

  var panel = document.getElementById('rizkiyaCorePanel');
  if (!panel) return;

  var MESSAGE_SOURCE = 'simover-rizkiya-host';
  var RESULT_STALE_MS = 5000;

  var btn = document.getElementById('btnRunRizkiyaCore');
  var btnText = btn ? btn.querySelector('span') : null;
  var frameWrap = document.getElementById('rizkiyaCoreFrameWrap');
  var frame = document.getElementById('rizkiyaCoreFrame');
  var el = {
    host: document.getElementById('rzHost'),
    video: document.getElementById('rzVideo'),
    model: document.getElementById('rzModel'),
    inference: document.getElementById('rzInference'),
    result: document.getElementById('rzResult'),
    resultTime: document.getElementById('rzResultTime')
  };

  var running = false;
  var lastResultAt = 0;
  var staleTimer = null;

  function setText(node, text, tone) {
    if (!node) return;
    node.textContent = text;
    node.className = node.className.replace(/\btext-(success|danger|warning|secondary)\b/g, '').trim();
    if (tone) node.classList.add('text-' + tone);
  }

  function resetStatus() {
    setText(el.host, running ? 'Memuat host...' : 'Belum dijalankan');
    ['video', 'model', 'inference', 'result', 'resultTime'].forEach(function (k) { setText(el[k], '-'); });
  }

  function formatTime(iso) {
    var d = new Date(iso);
    return isNaN(d) ? '-' : d.toLocaleTimeString('id-ID', { hour12: false }) + ' (waktu perangkat ini)';
  }

  function start() {
    running = true;
    resetStatus();
    frame.src = panel.getAttribute('data-host-url');
    frameWrap.classList.remove('d-none');
    btn.setAttribute('aria-pressed', 'true');
    if (btnText) btnText.textContent = 'Hentikan Core';
    staleTimer = setInterval(function () {
      if (lastResultAt && Date.now() - lastResultAt > RESULT_STALE_MS) {
        setText(el.inference, 'Tidak ada keluaran baru dalam ' + Math.round((Date.now() - lastResultAt) / 1000) + ' detik', 'warning');
      }
    }, 1000);
  }

  function stop() {
    running = false;
    // Mengosongkan iframe menghentikan seluruh proses core di browser
    frame.removeAttribute('src');
    frame.src = 'about:blank';
    frameWrap.classList.add('d-none');
    btn.setAttribute('aria-pressed', 'false');
    if (btnText) btnText.textContent = 'Jalankan Core';
    clearInterval(staleTimer);
    lastResultAt = 0;
    resetStatus();
  }

  if (btn) {
    btn.addEventListener('click', function () {
      if (running) stop(); else start();
    });
  }

  window.addEventListener('message', function (event) {
    if (!running || event.origin !== window.location.origin || event.source !== frame.contentWindow) return;
    var msg = event.data;
    if (!msg || msg.source !== MESSAGE_SOURCE) return;

    switch (msg.type) {
      case 'host':
        if (msg.state === 'blocked') setText(el.host, 'Ditolak: salinan core tidak identik dengan hash yang diterima', 'danger');
        else setText(el.host, 'Identik (SHA256 ' + String(msg.coreSha256 || '').slice(0, 12) + '...)', 'success');
        break;

      case 'video':
        if (msg.state === 'playing') setText(el.video, 'Diputar ' + (msg.width || '?') + 'x' + (msg.height || '?'), 'success');
        else if (msg.state === 'buffering') setText(el.video, 'Buffering...');
        else setText(el.video, 'Bermasalah' + (msg.detail ? ': ' + msg.detail : ''), 'danger');
        break;

      case 'model':
        if (msg.state === 'loaded') setText(el.model, msg.text, 'success');
        else if (msg.state === 'failed') setText(el.model, msg.text, 'danger');
        else setText(el.model, msg.text);
        break;

      case 'inference_error':
        setText(el.inference, 'Gagal (' + msg.count + 'x): ' + msg.message, 'danger');
        break;

      case 'inference_stalled':
        setText(el.inference,
          'Tidak ada keluaran: model dimuat dan video berjalan, tetapi core tidak menghasilkan hasil dalam ' + msg.seconds +
          ' detik. Pada uji 20 Okt 2026 penyebabnya error ONNX "Got: 224 Expected: 320" (ukuran input core tidak sesuai model).',
          'danger');
        break;

      case 'result':
        if (msg.kind === 'menunggu') {
          setText(el.result, msg.text);
          break;
        }
        lastResultAt = Date.now();
        setText(el.inference, 'Core menghasilkan keluaran', 'success');
        setText(el.result, msg.text);
        setText(el.resultTime, formatTime(msg.at));
        break;

      case 'contract':
        setText(el.inference, msg.detail, 'danger');
        break;
    }
  });

  window.addEventListener('pagehide', function () { if (running) stop(); });
})();
