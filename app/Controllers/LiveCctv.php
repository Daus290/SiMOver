<?php

namespace App\Controllers;

use App\Libraries\DummyDataService;
use App\Libraries\AtcsCctvService;
use App\Libraries\DishubCameraLocationService;

class LiveCctv extends BaseController
{
    protected DummyDataService $dummyData;
    protected AtcsCctvService $atcsService;
    protected DishubCameraLocationService $dishubLocationService;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
        $this->atcsService = new AtcsCctvService();
        $this->dishubLocationService = new DishubCameraLocationService();
    }

    public function index(): string
    {
        $rawCctv = $this->request->getGet('cctv');
        $selectedCctv = null;
        $invalidParam = false;

        // Validasi parameter cctv yang aman (hindari array, string terlalu panjang, atau karakter aneh)
        if ($rawCctv !== null) {
            if (is_array($rawCctv) || !is_string($rawCctv) || strlen(trim((string)$rawCctv)) > 64) {
                $invalidParam = true;
            } else {
                $cleanCode = trim((string)$rawCctv);
                if ($cleanCode !== '') {
                    // 1. Cek pada daftar kamera Dishub Banjarbaru terlebih dahulu (BJB-...) dengan overlay Live CCTV
                    $selectedCctv = $this->dishubLocationService->getCameraForLiveCctv($cleanCode);

                    // 2. Jika tidak ditemukan, cek pada daftar kamera ATCS Banjarmasin (ATCS-...)
                    if ($selectedCctv === null) {
                        $selectedCctv = $this->atcsService->getCctvByCode($cleanCode);
                    }

                    // 3. Jika tetap tidak ditemukan di kedua sumber terdaftar
                    if ($selectedCctv === null) {
                        $invalidParam = true;
                    }
                }
            }
        }

        $data = [
            'title'              => 'Live CCTV - Pemantauan Lalu Lintas Dishub Kota Banjarbaru',
            'active_nav'         => 'live_cctv',
            'meta'               => $this->dummyData->getSystemMeta(),
            'stats'              => $this->dummyData->getDailyStatistics(),
            'bjb_cameras'        => $this->dishubLocationService->getCamerasForLiveCctv(),
            'bjb_intersections'  => $this->dishubLocationService->getIntersections(),
            'bjb_summary'        => $this->dishubLocationService->getSummaryCounts(),
            'atcs_cameras'       => $this->atcsService->getCctvList(),
            'selected_cctv'      => $selectedCctv,
            'selected_code'      => $selectedCctv ? $selectedCctv['code'] : null,
            'invalid_param'      => $invalidParam,
        ];

        return view('pages/live_cctv', $data);
    }
}
