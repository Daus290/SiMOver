<?php

namespace App\Controllers;

use App\Libraries\DummyDataService;
use App\Libraries\DishubCameraLocationService;

class Peta extends BaseController
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
        $rawCctv = $this->request->getGet('cctv');
        $rawFocus = $this->request->getGet('focus');

        $focusCamera = null;
        $focusIntersection = null;

        if (is_string($rawCctv) && trim($rawCctv) !== '' && strlen($rawCctv) <= 64) {
            $focusCamera = $this->dishubLocationService->getCameraByCode(trim($rawCctv));
            if ($focusCamera) {
                $focusIntersection = $this->dishubLocationService->getIntersection($focusCamera['location_id']);
            }
        }

        if (!$focusIntersection && is_string($rawFocus) && trim($rawFocus) !== '' && strlen($rawFocus) <= 64) {
            $focusIntersection = $this->dishubLocationService->getIntersection(trim($rawFocus));
        }

        $data = [
            'title'              => 'Peta Interaktif Titik CCTV - Dishub Kota Banjarbaru',
            'active_nav'         => 'peta',
            'meta'               => $this->dummyData->getSystemMeta(),
            'stats'              => $this->dummyData->getDailyStatistics(),
            'intersections'      => $this->dishubLocationService->getIntersections(),
            'cameras'            => $this->dishubLocationService->getAllCameras(),
            'summary'            => $this->dishubLocationService->getSummaryCounts(),
            'focus_camera'       => $focusCamera,
            'focus_intersection' => $focusIntersection,
        ];

        return view('pages/peta', $data);
    }
}
