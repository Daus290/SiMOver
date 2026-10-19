<?php

namespace App\Controllers;

use App\Libraries\DummyDataService;
use App\Libraries\DishubCameraLocationService;

class Home extends BaseController
{
    protected DummyDataService $dummyData;
    protected DishubCameraLocationService $dishubLocationService;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
        $this->dishubLocationService = new DishubCameraLocationService();
    }

    public function index(): string
    {
        $targetCodes = [
            'BJB-BRIMOB-F1',
            'BJB-BRIMOB-F2',
            'BJB-BRIMOB-F3',
            'BJB-BRIMOB-PTZ',
            'BJB-ROULIN-F1',
            'BJB-ROULIN-F2',
        ];

        $allCameras = $this->dishubLocationService->getCamerasForLiveCctv();
        $cameraMap = [];
        foreach ($allCameras as $cam) {
            if (isset($cam['code'])) {
                $cameraMap[$cam['code']] = $cam;
            }
        }

        $selectedCameras = [];
        foreach ($targetCodes as $code) {
            if (isset($cameraMap[$code])) {
                $selectedCameras[] = $cameraMap[$code];
            }
        }

        $data = [
            'title'      => 'Beranda - Sistem Monitoring Over Dimension Kota Banjarbaru',
            'active_nav' => 'beranda',
            'meta'       => $this->dummyData->getSystemMeta(),
            'stats'      => $this->dummyData->getDailyStatistics(),
            'cctv_list'  => $selectedCameras,
        ];

        return view('pages/beranda', $data);
    }
}
