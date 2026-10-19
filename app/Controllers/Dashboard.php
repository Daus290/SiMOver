<?php

namespace App\Controllers;

use App\Libraries\DummyDataService;

class Dashboard extends BaseController
{
    protected DummyDataService $dummyData;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
    }

    public function index(): string
    {
        $data = [
            'title'      => 'Dashboard Pengunjung - Monitoring Over Dimension',
            'active_nav' => 'dashboard',
            'meta'       => $this->dummyData->getSystemMeta(),
            'stats'      => $this->dummyData->getDailyStatistics(),
            'timeline'   => $this->dummyData->getHourlyTimelineData(),
            'cctv_list'  => $this->dummyData->getCctvList(),
        ];

        return view('pages/dashboard', $data);
    }
}
