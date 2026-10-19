<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * Rute Halaman Pengunjung Sistem Monitoring Over Dimension Dishub Banjarbaru
 */
$routes->get('/', 'Home::index');
$routes->get('live-cctv', 'LiveCctv::index');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('peta-cctv', 'Peta::index');
$routes->get('tentang-dishub', 'Tentang::dishub');
$routes->get('tentang-over-dimension', 'Tentang::overDimension');

/**
 * Rute Relay HLS Stream & Snapshot 6 CCTV Publik ATCS Banjarmasin (Local Dev)
 */
$routes->get('cctv-stream/(:segment)/(:segment)', 'CctvProxy::stream/$1/$2');
$routes->get('cctv-snapshot/(:segment)', 'CctvProxy::snapshot/$1');

/**
 * Rute Area Admin (Tahap 5)
 */
$routes->group('admin', static function ($routes) {
    // Pintu masuk & Autentikasi Demo
    $routes->get('', 'Admin\AuthController::index');
    $routes->get('login', 'Admin\AuthController::login');
    $routes->post('login', 'Admin\AuthController::doLogin', ['filter' => 'csrf']);

    // Halaman & Aksi yang Dilindungi Sesi Admin
    $routes->group('', ['filter' => 'adminAuth'], static function ($routes) {
        $routes->get('dashboard', 'Admin\DashboardController::index');
        $routes->get('hasil-deteksi', 'Admin\HasilDeteksiController::index');
        $routes->post('logout', 'Admin\AuthController::logout', ['filter' => 'csrf']);
        $routes->post('ubah-kata-sandi', 'Admin\AuthController::changePassword', ['filter' => 'csrf']);
    });
});
