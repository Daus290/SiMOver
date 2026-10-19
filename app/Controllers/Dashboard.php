<?php

namespace App\Controllers;

use App\Libraries\DummyDataService;

/**
 * Controller Dashboard Publik
 * 
 * Portal utama pemantauan over dimension Kota Banjarbaru yang dapat diakses publik
 * tanpa login/admin. Menyediakan dua tab bersebelahan (Statistik dan Hasil Deteksi)
 * serta dropdown Periode (1 Hari, 7 Hari, 30 Hari).
 */
class Dashboard extends BaseController
{
    protected DummyDataService $dummyData;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
    }

    public function index(): string
    {
        // 1. Sanitasi & Validasi Periode (Whitelist: 1, 7, 30. Default: 1)
        $rawPeriod = $this->request->getGet('period');
        $period = 1;
        if (is_numeric($rawPeriod)) {
            $parsedPeriod = (int)$rawPeriod;
            if (in_array($parsedPeriod, [1, 7, 30], true)) {
                $period = $parsedPeriod;
            }
        }

        // 2. Sanitasi & Validasi Tab Aktif (Whitelist: 'statistik', 'hasil-deteksi'. Default: 'statistik')
        $rawTab = $this->request->getGet('tab');
        $activeTab = 'statistik';
        if (is_string($rawTab)) {
            $cleanTab = strtolower(trim($rawTab));
            if (in_array($cleanTab, ['statistik', 'hasil-deteksi'], true)) {
                $activeTab = $cleanTab;
            }
        }

        // 3. Sanitasi Nomor Halaman Paginasi (Integer >= 1. Default: 1)
        $rawPage = $this->request->getGet('page');
        $page = 1;
        if (is_numeric($rawPage)) {
            $page = max(1, (int)$rawPage);
        }

        // 4. Sanitasi Filter Tabel (CCTV, Jenis Kendaraan, Hasil Deteksi)
        $rawCctv = $this->request->getGet('cctv');
        $rawVehicle = $this->request->getGet('vehicle');
        $rawResult = $this->request->getGet('result');

        $tableFilters = [
            'cctv'    => is_string($rawCctv) ? trim($rawCctv) : 'all',
            'vehicle' => is_string($rawVehicle) ? trim($rawVehicle) : 'all',
            'result'  => is_string($rawResult) ? trim($rawResult) : 'all',
        ];

        // 5. Ambil data terpadu dari DummyDataService
        $dashboardData = $this->dummyData->getDashboardData($period, $tableFilters, $page, 10);

        // 6. Siapkan data untuk view
        $data = [
            'title'             => 'Dashboard Monitoring Over Dimension - Dishub Banjarbaru',
            'active_nav'        => 'dashboard',
            'meta'              => $this->dummyData->getSystemMeta(),
            'period'            => $period,
            'active_tab'        => $activeTab,
            'period_info'       => $dashboardData['period_info'],
            'period_label'      => $dashboardData['period_label'],
            'period_date_range' => $dashboardData['period_date_range'],
            'stats'             => $dashboardData['stats'],
            'insights'          => $dashboardData['insights'],
            'chart_data'        => $dashboardData['chart_data'],
            'table'             => $dashboardData['table'],
            'filters'           => $dashboardData['table']['filters'],
            'filterOptions'     => $dashboardData['table']['filter_options'],
            'pagination'        => $dashboardData['table'],
            'hasActiveFilter'   => $dashboardData['table']['has_active_filter'],
        ];

        return view('pages/dashboard', $data);
    }
}
