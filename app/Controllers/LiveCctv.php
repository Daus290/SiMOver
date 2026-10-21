<?php

namespace App\Controllers;

use App\Libraries\DummyDataService;
use App\Libraries\AtcsCctvService;
use App\Libraries\DishubCameraLocationService;
use App\Models\AiWorkerStatusModel;

class LiveCctv extends BaseController
{
    protected DummyDataService $dummyData;
    protected AtcsCctvService $atcsService;

    public function __construct()
    {
        $this->dummyData   = new DummyDataService();
        $this->atcsService = new AtcsCctvService();
    }

    public function index(): string
    {
        $rawCctv      = $this->request->getGet('cctv');
        $selectedCctv = null;
        $invalidParam = false;
        $cameras      = $this->atcsService->getCctvList();

        // Validasi parameter cctv yang aman
        if ($rawCctv !== null) {
            if (is_array($rawCctv) || !is_string($rawCctv) || strlen(trim((string)$rawCctv)) > 64) {
                $invalidParam = true;
            } else {
                $cleanCode = trim((string)$rawCctv);
                if ($cleanCode !== '') {
                    $selectedCctv = $this->atcsService->getCctvByCode($cleanCode);
                    if ($selectedCctv === null) {
                        $invalidParam = true;
                    }
                }
            }
        }

        // Jika tidak ada parameter cctv, tampilkan kamera pertama secara otomatis agar player langsung memutar stream
        if ($selectedCctv === null && !$invalidParam && !empty($cameras)) {
            $selectedCctv = $cameras[0];
        }

        $data = [
            'title'         => 'Live CCTV - Pemantauan Lalu Lintas Dishub',
            'active_nav'    => 'live_cctv',
            'meta'          => $this->dummyData->getSystemMeta(),
            'stats'         => $this->dummyData->getDailyStatistics(),
            'cameras'       => $cameras,
            'selected_cctv' => $selectedCctv,
            'selected_code' => $selectedCctv ? $selectedCctv['code'] : null,
            'invalid_param' => $invalidParam,
        ];

        return view('pages/live_cctv', $data);
    }
}
