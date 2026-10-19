<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DummyDataService;

/**
 * DashboardController (Admin)
 * 
 * Menyajikan dashboard mingguan (insight 7 hari) dan 5 hasil deteksi terbaru hari ini.
 */
class DashboardController extends BaseController
{
    private DummyDataService $dummyData;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
    }

    public function index()
    {
        $meta = $this->dummyData->getSystemMeta();
        $weekly = $this->dummyData->getWeeklyHistoricalSummary();
        $latestDetections = $this->dummyData->getLatestDetections(5);

        $data = [
            'meta'             => $meta,
            'title'            => 'Dashboard Monitoring Mingguan - Admin Dishub',
            'activeMenu'       => 'dashboard',
            'adminUser'        => session()->get('admin_user') ?? 'Admin Dishub',
            'adminRole'        => session()->get('admin_role') ?? 'Administrator Sistem',
            'weekly'           => $weekly,
            'latestDetections' => $latestDetections,
        ];

        return view('admin/pages/dashboard', $data);
    }
}
