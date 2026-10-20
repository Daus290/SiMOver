<?php

namespace App\Controllers;

use App\Libraries\AiDashboardService;
use App\Libraries\DummyDataService;

/**
 * Controller Dashboard Publik
 *
 * Portal pemantauan over dimension yang dapat diakses publik tanpa login/admin.
 * Dua tab bersebelahan (Statistik dan Hasil Deteksi), satu dropdown Periode
 * (1/7/30 Hari) dan pilihan Sumber Data.
 *
 * Data berasal dari hasil AI tersimpan untuk kamera uji ATCS Banjarmasin.
 * Sumber default adalah core Rizkiya; data uji worker Antigravity hanya
 * tampil bila dipilih eksplisit dan diberi label bukan core.
 */
class Dashboard extends BaseController
{
    public function index(): string
    {
        $service = new AiDashboardService();
        $params  = $service->normalizeParams($this->request->getGet() ?? []);
        $dash    = $service->build($params);

        // Footer menampilkan waktu acuan data dashboard, bukan tanggal data contoh
        $meta = (new DummyDataService())->getSystemMeta();
        $asOf = strtotime($dash['as_of']);
        $meta['demo_date'] = date('d M Y', $asOf);
        $meta['demo_time'] = date('H:i', $asOf) . ' WITA';

        return view('pages/dashboard', [
            'title'      => 'Dashboard Monitoring Over Dimension - Dishub Banjarbaru',
            'active_nav' => 'dashboard',
            'meta'       => $meta,
            'dash'       => $dash,
        ]);
    }
}
