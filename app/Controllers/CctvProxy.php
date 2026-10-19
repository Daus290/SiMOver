<?php

namespace App\Controllers;

use App\Libraries\AtcsCctvService;
use App\Libraries\VivotekCctvService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CctvProxy
 * 
 * Relay HLS dan Snapshot terkontrol untuk:
 * 1. 18 Kamera Vivotek Dishub Banjarbaru (Khusus Jaringan Kantor)
 * 2. 6 CCTV publik ATCS Dishub Banjarmasin (Tayangan Uji)
 * 
 * Mencegah CORS, path traversal, dan membocorkan kredensial ke sisi browser klien.
 */
class CctvProxy extends BaseController
{
    protected AtcsCctvService $atcsService;
    protected VivotekCctvService $vivotekService;
    protected string $atcsCacheDir;
    protected string $vivotekSnapCacheDir;

    public function __construct()
    {
        $this->atcsService    = new AtcsCctvService();
        $this->vivotekService = new VivotekCctvService();

        $this->atcsCacheDir        = WRITEPATH . 'cache/atcs_cctv/';
        $this->vivotekSnapCacheDir = WRITEPATH . 'cache/vivotek_snapshot/';

        if (!is_dir($this->atcsCacheDir)) {
            @mkdir($this->atcsCacheDir, 0775, true);
        }
        if (!is_dir($this->vivotekSnapCacheDir)) {
            @mkdir($this->vivotekSnapCacheDir, 0775, true);
        }
    }

    /**
     * Relay HLS Stream (Master Playlist, Child Playlist, dan Segmen Media)
     * 
     * Route: GET /cctv-stream/{sourceCode}/{file}
     */
    public function stream(string $sourceCode, string $file): ResponseInterface
    {
        // 1. Validasi Keamanan: Karakter nama file yang diizinkan (Cegah Path Traversal & Ekstensi Asing)
        if (!preg_match('/^[a-zA-Z0-9_.-]+\.(m3u8|mp4|ts|m4s)$/', $file)) {
            return $this->response->setStatusCode(400)->setBody('Permintaan file tidak valid.');
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // =====================================================================
        // JALUR A: KAMERA VIVOTEK BANJARBARU (JARINGAN KANTOR)
        // =====================================================================
        if ($this->vivotekService->isAllowedCode($sourceCode)) {
            $streamDir  = $this->vivotekService->getStreamDir($sourceCode);
            $realBase   = realpath($streamDir);
            $targetPath = $streamDir . basename($file);

            // Jika file fisik sudah terbentuk, verifikasi path containment
            if (file_exists($targetPath)) {
                $realFile = realpath($targetPath);
                if ($realFile === false || $realBase === false || !str_starts_with($realFile, $realBase)) {
                    return $this->response->setStatusCode(403)->setBody('Akses ke file stream ditolak.');
                }

                if (is_file($realFile) && filesize($realFile) > 0) {
                    if ($ext === 'm3u8') {
                        $body = file_get_contents($realFile);
                        // Rewrite segment paths relative to proxy
                        $body = preg_replace_callback('/^([a-zA-Z0-9_.-]+\.(?:ts|m4s|mp4))$/m', function ($m) use ($sourceCode) {
                            return base_url("cctv-stream/{$sourceCode}/" . $m[1]);
                        }, $body);

                        // Same-origin stream (tanpa CORS wildcard *)
                        return $this->response
                            ->setStatusCode(200)
                            ->setContentType('application/vnd.apple.mpegurl; charset=utf-8')
                            ->setHeader('Content-Length', (string)strlen($body))
                            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                            ->setHeader('Pragma', 'no-cache')
                            ->setHeader('Expires', '0')
                            ->setBody($body);
                    }

                    $contentType = ($ext === 'ts') ? 'video/mp2t' : 'video/mp4';
                    return $this->serveBinary(file_get_contents($realFile), $contentType, 60);
                }
            }

            // Jika file manifest/segmen belum terbentuk oleh relay
            return $this->response
                ->setStatusCode(503)
                ->setContentType('text/plain')
                ->setBody("Aliran video Vivotek {$sourceCode} belum aktif di relay lokal. Pastikan skrip relay RTSP sedang berjalan.");
        }

        // =====================================================================
        // JALUR B: KAMERA ATCS BANJARMASIN (UPSTREAM RELAY)
        // =====================================================================
        if (!$this->atcsService->isValidSourceCode($sourceCode)) {
            return $this->response->setStatusCode(404)->setBody('Kamera tidak terdaftar.');
        }

        $upstreamUrl = AtcsCctvService::UPSTREAM_HOST . "/stream/{$sourceCode}/{$file}";

        // Untuk segmen biner (.mp4, .ts, .m4s): Periksa cache lokal berdurasi singkat
        $segmentCacheFile = $this->atcsCacheDir . 'seg_' . md5($sourceCode . '_' . $file) . '.' . $ext;
        if (in_array($ext, ['mp4', 'ts', 'm4s'], true) && is_file($segmentCacheFile)) {
            $fileAge = time() - filemtime($segmentCacheFile);
            if ($fileAge < 120 && filesize($segmentCacheFile) > 0) {
                $contentType = ($ext === 'ts') ? 'video/mp2t' : 'video/mp4';
                return $this->serveBinary(file_get_contents($segmentCacheFile), $contentType, 60);
            }
        }

        // Fetch dari upstream ATCS
        $result = $this->fetchUpstream($upstreamUrl);

        if ($result['code'] !== 200 || $result['body'] === null) {
            return $this->response
                ->setStatusCode($result['code'] ?: 502)
                ->setContentType('text/plain')
                ->setBody('Tayangan CCTV sedang tidak tersedia dari server sumber.');
        }

        $body = $result['body'];

        // Pemrosesan Manifest HLS (.m3u8): Rewrite playlist dan segmen
        if ($ext === 'm3u8') {
            $body = preg_replace_callback('/^([a-zA-Z0-9_.-]+\.m3u8)$/m', function ($m) use ($sourceCode) {
                return base_url("cctv-stream/{$sourceCode}/" . $m[1]);
            }, $body);

            $body = preg_replace_callback('/#EXT-X-MAP:URI="([^"]+)"/', function ($m) use ($sourceCode) {
                return '#EXT-X-MAP:URI="' . base_url("cctv-stream/{$sourceCode}/" . $m[1]) . '"';
            }, $body);

            $body = preg_replace_callback('/^([a-zA-Z0-9_.-]+\.(?:mp4|ts|m4s))$/m', function ($m) use ($sourceCode) {
                return base_url("cctv-stream/{$sourceCode}/" . $m[1]);
            }, $body);

            return $this->response
                ->setStatusCode(200)
                ->setContentType('application/vnd.apple.mpegurl; charset=utf-8')
                ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Expires', '0')
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Range, Accept-Encoding')
                ->setBody($body);
        }

        // Simpan cache segmen biner ATCS
        if (in_array($ext, ['mp4', 'ts', 'm4s'], true)) {
            @file_put_contents($segmentCacheFile, $body, LOCK_EX);
            $this->cleanOldCacheFiles();
        }

        $contentType = ($ext === 'ts') ? 'video/mp2t' : 'video/mp4';
        return $this->serveBinary($body, $contentType, 60);
    }

    /**
     * Relay Snapshot Cuplikan Kamera
     * 
     * Route: GET /cctv-snapshot/{sourceCode}
     */
    public function snapshot(string $sourceCode): ResponseInterface
    {
        // =====================================================================
        // JALUR A: KAMERA VIVOTEK BANJARBARU (JARINGAN KANTOR)
        // =====================================================================
        if ($this->vivotekService->isAllowedCode($sourceCode)) {
            $camConfig = $this->vivotekService->getCameraConfig($sourceCode);

            // 1. Cek file cuplikan dari relay streaming aktif
            $streamDir     = $this->vivotekService->getStreamDir($sourceCode);
            $relaySnapFile = $streamDir . 'snapshot.jpg';
            $cacheFile     = $this->vivotekSnapCacheDir . 'snap_' . md5($sourceCode) . '.jpg';

            // Jika ada snapshot segar dari relay yang sedang berjalan
            $sourceSnap = null;
            if (is_file($relaySnapFile) && filesize($relaySnapFile) > 1024) {
                $sourceSnap = $relaySnapFile;
            } elseif (is_file($cacheFile) && filesize($cacheFile) > 1024) {
                $sourceSnap = $cacheFile;
            }

            if ($sourceSnap !== null) {
                $content = file_get_contents($sourceSnap);
                // Validasi ketat format JPEG (Magic bytes: 0xFF 0xD8 0xFF)
                if (strlen($content) > 1024 && str_starts_with($content, "\xFF\xD8\xFF")) {
                    $mtime = filemtime($sourceSnap);
                    $age   = time() - $mtime;
                    $captureTime = date('d M Y H:i:s', $mtime) . ' WITA';

                    $resp = $this->response
                        ->setStatusCode(200)
                        ->setContentType('image/jpeg')
                        ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
                        ->setHeader('X-Snapshot-Captured-At', $captureTime);

                    if ($age < 20) {
                        $resp->setHeader('Cache-Control', 'public, max-age=10')
                             ->setHeader('X-Snapshot-Fresh', 'true');
                    } else {
                        // Snapshot tersimpan (stale cache)
                        $resp->setHeader('Cache-Control', 'public, max-age=30')
                             ->setHeader('X-Snapshot-Cached', 'true');
                    }

                    return $resp->setBody($content);
                }
            }

            // 2. Fallback visual dinamis (SVG) yang jujur dan informatif
            $svg = $this->generateFallbackSnapshotSvg($sourceCode, $camConfig);
            return $this->response
                ->setStatusCode(200)
                ->setContentType('image/svg+xml; charset=utf-8')
                ->setHeader('Cache-Control', 'public, max-age=10')
                ->setHeader('X-Snapshot-Type', 'placeholder')
                ->setBody($svg);
        }

        // =====================================================================
        // JALUR B: KAMERA ATCS BANJARMASIN
        // =====================================================================
        if (!$this->atcsService->isValidSourceCode($sourceCode)) {
            return $this->response->setStatusCode(404)->setBody('Kamera tidak terdaftar.');
        }

        $cacheFile = $this->atcsCacheDir . 'snap_' . md5($sourceCode) . '.webp';

        // Cache 30 detik
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 30 && filesize($cacheFile) > 0) {
            return $this->serveBinary(file_get_contents($cacheFile), 'image/webp', 30);
        }

        // Fetch dari upstream ATCS
        $upstreamUrl = AtcsCctvService::UPSTREAM_HOST . "/screenshot/{$sourceCode}.webp";
        $result = $this->fetchUpstream($upstreamUrl);

        if ($result['code'] === 200 && $result['body'] && strlen($result['body']) > 512) {
            @file_put_contents($cacheFile, $result['body'], LOCK_EX);
            return $this->serveBinary($result['body'], 'image/webp', 30);
        }

        if (is_file($cacheFile) && filesize($cacheFile) > 0) {
            return $this->serveBinary(file_get_contents($cacheFile), 'image/webp', 15);
        }

        return $this->response
            ->setStatusCode(502)
            ->setContentType('text/plain')
            ->setBody('Cuplikan kamera tidak tersedia saat ini.');
    }

    /**
     * Buat SVG snapshot elegan dan jujur untuk kartu kamera Vivotek saat snapshot biner belum siap.
     * Tidak menampilkan indikator hijau palsu atau waktu berjalan menyerupai siaran live.
     */
    protected function generateFallbackSnapshotSvg(string $code, array $config): string
    {
        $name     = htmlspecialchars($config['name'] ?? $code, ENT_QUOTES, 'UTF-8');
        $ip       = htmlspecialchars($config['ip'] ?? '10.0.x.x', ENT_QUOTES, 'UTF-8');
        $viewNo   = htmlspecialchars($config['view_no'] ?? '01', ENT_QUOTES, 'UTF-8');
        $type     = strtoupper($config['type'] ?? 'FIXED');
        $locTitle = 'Dishub Kota Banjarbaru';

        // Label persimpangan berdasarkan location_id
        $locId = $config['location_id'] ?? '';
        if ($locId === 's3-brimob') {
            $locTitle = 'Simpang Tiga Brimob';
        } elseif ($locId === 's3-ro-ulin') {
            $locTitle = 'Simpang Tiga RO Ulin';
        } elseif ($locId === 's4-samsat') {
            $locTitle = 'Simpang Empat Samsat';
        } elseif ($locId === 's4-adipura') {
            $locTitle = 'Simpang Empat Adipura';
        }

        $isDamaged     = ($config['hardware_status'] ?? '') === 'offline_damaged';
        $isUnconnected = ($config['hardware_status'] ?? '') === 'unconnected';
        $isUnavailable = empty($config['is_installed']);

        // Warna tema dan status badge yang jujur
        if ($isUnavailable) {
            $topStatusText = 'TITIK BELUM TERSEDIA';
            $topBadgeColor = '#64748b'; // Slate gray
            $midDesc       = 'Rencana persimpangan jalan, belum terpasang di lapangan.';
            $actionDesc    = 'Integrasi media dinonaktifkan untuk entri ini.';
        } elseif ($isDamaged) {
            $topStatusText = 'TIDAK AKTIF • KERUSAKAN PERANGKAT';
            $topBadgeColor = '#ef4444'; // Red
            $midDesc       = "Perangkat IP {$ip} ({$type}) mengalami kerusakan fisik di lapangan.";
            $actionDesc    = 'Tayangan tidak dapat disajikan hingga perbaikan fisik selesai.';
        } elseif ($isUnconnected) {
            $topStatusText = 'BELUM TERHUBUNG';
            $topBadgeColor = '#f59e0b'; // Amber
            $midDesc       = "Aliran RTSP perangkat IP {$ip} ({$type}) belum terverifikasi.";
            $actionDesc    = 'Pemeriksaan parameter integrasi sedang berlangsung.';
        } else {
            $topStatusText = 'RELAY STANDBY';
            $topBadgeColor = '#64748b'; // Neutral slate
            $midDesc       = "{$locTitle} • IP {$ip} ({$type})";
            $actionDesc    = 'Jalankan relay streaming untuk memperbarui siaran live.';
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f172a" />
      <stop offset="100%" stop-color="#1e293b" />
    </linearGradient>
    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
      <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/>
    </pattern>
  </defs>

  <rect width="640" height="360" fill="url(#bgGrad)"/>
  <rect width="640" height="360" fill="url(#grid)"/>

  <!-- Top bar info -->
  <rect x="0" y="0" width="640" height="36" fill="rgba(0,0,0,0.6)"/>
  <circle cx="20" cy="18" r="4" fill="{$topBadgeColor}"/>
  <text x="32" y="22" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="11" font-weight="600" fill="{$topBadgeColor}">{$topStatusText} • KANAL {$viewNo}</text>
  <text x="620" y="22" text-anchor="end" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="11" fill="#64748b">VIVOTEK NVR (10.0.1.3)</text>

  <!-- Center camera info -->
  <g transform="translate(320, 160)">
    <!-- Camera Icon Circle -->
    <circle cx="0" cy="-20" r="32" fill="#1e293b" stroke="#334155" stroke-width="2"/>
    <path d="M -12 -28 L -6 -28 L -4 -31 L 4 -31 L 6 -28 L 12 -28 C 14.2 -28 16 -26.2 16 -24 L 16 -12 C 16 -9.8 14.2 -8 12 -8 L -12 -8 C -14.2 -8 -16 -9.8 -16 -12 L -16 -24 C -16 -26.2 -14.2 -28 -12 -28 Z" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linejoin="round"/>
    <circle cx="0" cy="-18" r="6" fill="none" stroke="#94a3b8" stroke-width="2"/>

    <text x="0" y="30" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="18" font-weight="700" fill="#f8fafc">{$code} • {$name}</text>
    <text x="0" y="52" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="12" fill="#94a3b8">{$midDesc}</text>
    <text x="0" y="72" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="11" fill="#64748b">{$actionDesc}</text>
  </g>

  <!-- Bottom status bar -->
  <rect x="0" y="324" width="640" height="36" fill="rgba(0,0,0,0.6)"/>
  <text x="20" y="346" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="11" fill="#64748b">DISHUB KOTA BANJARBARU</text>
  <text x="620" y="346" text-anchor="end" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="11" fill="#94a3b8">{$locTitle}</text>
</svg>
SVG;
    }

    /**
     * Layani konten biner dengan header caching terkontrol
     */
    protected function serveBinary(?string $content, string $contentType, int $maxAgeSec = 60): ResponseInterface
    {
        if ($content === null || $content === '') {
            return $this->response->setStatusCode(404)->setBody('Data tidak ditemukan.');
        }

        $length = strlen($content);

        return $this->response
            ->setStatusCode(200)
            ->setContentType($contentType)
            ->setHeader('Content-Length', (string)$length)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', "public, max-age={$maxAgeSec}")
            ->setBody($content);
    }

    /**
     * Ambil data dari server upstream secara aman
     */
    protected function fetchUpstream(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'DishubMediaProxy/2.0',
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $code,
            'body' => $body ?: null,
        ];
    }

    /**
     * Bersihkan cache segmen kadaluarsa
     */
    protected function cleanOldCacheFiles(): void
    {
        // 1 dari 50 request membersihkan cache
        if (random_int(1, 50) !== 1) {
            return;
        }

        $files = glob($this->atcsCacheDir . '*.*');
        if (!$files) return;

        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > 300) {
                @unlink($file);
            }
        }
    }
}
