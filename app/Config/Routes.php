<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 * Rute Portal Publik Sistem Monitoring Over Dimension Dishub Banjarbaru
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
 * Rute Kompatibilitas Bookmark Admin Legacy (Dialihkan ke Portal Publik Baru /dashboard)
 * Tidak ada form login, logout, atau ubah password aktif.
 */
$routes->get('admin', static fn() => redirect()->to(base_url('dashboard')));
$routes->get('admin/login', static fn() => redirect()->to(base_url('dashboard')));
$routes->get('admin/dashboard', static fn() => redirect()->to(base_url('dashboard')));
$routes->get('admin/hasil-deteksi', static fn() => redirect()->to(base_url('dashboard?tab=hasil-deteksi')));

/**
 * Rute API Integrasi AI Model & Dashboard Data Real-Time
 */
// Host terisolasi core Rizkiya (iframe pada player ATCS-TEST, isi core tidak diubah)
$routes->get('ai-core/rizkiya-host', 'RizkiyaCoreHost::index');

$routes->group('api/ai', static function ($routes) {
    $routes->post('detections', 'Api\AiApiController::postDetection');
    $routes->post('heartbeat', 'Api\AiApiController::postHeartbeat');
    $routes->post('overlay', 'Api\AiApiController::postOverlay');
    $routes->get('overlay', 'Api\AiApiController::getOverlay');
    $routes->get('status', 'Api\AiApiController::getStatus');
    $routes->get('dashboard-data', 'Api\AiApiController::getDashboardData');
});
