<?php

/*
 *---------------------------------------------------------------
 * ENTRYPOINT VERCEL (runtime komunitas vercel-php)
 *---------------------------------------------------------------
 * Hanya dipakai di Vercel. Server lokal (php spark serve / Apache)
 * tetap memakai public/index.php. Isinya mengikuti public/index.php
 * dengan tiga penyesuaian lingkungan serverless:
 *
 * 1. SCRIPT_NAME dibuat seperti front controller di public/ agar
 *    CodeIgniter tidak memotong segmen "api" dari URI /api/ai/...
 * 2. writable diarahkan ke /tmp karena filesystem function read-only.
 *    Isi /tmp sementara dan tidak dibagi antar-instance.
 * 3. Environment variables Vercel disalin ke $_ENV, lalu baseURL diambil
 *    dari app_baseURL; bila kosong memakai https:// + domain yang diminta
 *    pengunjung, dengan cadangan VERCEL_*_URL.
 */

$minPhpVersion = '8.2';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo sprintf('PHP minimal %s, versi saat ini %s', $minPhpVersion, PHP_VERSION);

    exit(1);
}

$publicDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicDir . 'index.php';

// Konfigurasi CodeIgniter dibaca dari $_ENV/$_SERVER, bukan getenv(). Bila
// variables_order runtime tanpa "E", env Vercel hanya terbaca lewat getenv().
foreach (getenv() as $key => $value) {
    $_ENV[$key] ??= $value;
}

if (! isset($_ENV['app.baseURL']) && ! isset($_ENV['app_baseURL'])) {
    // Utamakan domain yang diminta pengunjung: CDN Vercel hanya meneruskan
    // domain milik deployment ini, dan aset harus satu domain dengan halaman
    // agar cookie Deployment Protection ikut terkirim.
    $vercelHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
    if (! preg_match('/^[a-z0-9.-]+$/i', $vercelHost)) {
        $vercelHost = getenv('VERCEL_ENV') === 'production'
            ? getenv('VERCEL_PROJECT_PRODUCTION_URL')
            : (getenv('VERCEL_BRANCH_URL') ?: getenv('VERCEL_URL'));
    }

    if (is_string($vercelHost) && $vercelHost !== '') {
        $_ENV['app_baseURL'] = 'https://' . $vercelHost . '/';
    }
}

define('FCPATH', $publicDir);

if (is_dir(FCPATH) && getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

require dirname(__DIR__) . '/app/Config/Paths.php';

$paths = new Config\Paths();

$paths->writableDirectory = rtrim(sys_get_temp_dir(), '\\/') . '/simover-writable';
foreach (['cache', 'logs', 'session', 'uploads', 'debugbar'] as $dir) {
    if (! is_dir($paths->writableDirectory . '/' . $dir)) {
        @mkdir($paths->writableDirectory . '/' . $dir, 0775, true);
    }
}

require $paths->systemDirectory . '/Boot.php';

exit(CodeIgniter\Boot::bootWeb($paths));
