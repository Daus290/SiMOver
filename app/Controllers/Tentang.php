<?php

namespace App\Controllers;

use App\Libraries\DummyDataService;

class Tentang extends BaseController
{
    protected DummyDataService $dummyData;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
    }

    public function dishub(): string
    {
        $data = [
            'title'      => 'Tentang Dinas Perhubungan Kota Banjarbaru',
            'active_nav' => 'beranda', // Menggunakan highlight Beranda sesuai instruksi
            'meta'       => $this->dummyData->getSystemMeta(),
            'stats'      => $this->dummyData->getDailyStatistics(),
        ];

        return view('pages/tentang_dishub', $data);
    }

    public function overDimension(): string
    {
        $data = [
            'title'      => 'Tentang Over Dimension - Edukasi & Pengawasan Dishub',
            'active_nav' => 'beranda', // Menggunakan highlight Beranda sesuai instruksi
            'meta'       => $this->dummyData->getSystemMeta(),
            'stats'      => $this->dummyData->getDailyStatistics(),
        ];

        return view('pages/tentang_over_dimension', $data);
    }
}
