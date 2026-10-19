<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DummyDataService;

/**
 * HasilDeteksiController (Admin)
 * 
 * Mengelola penyajian daftar hasil deteksi individu hari ini (25 Sep 2026),
 * filter CCTV, Jenis Kendaraan, Hasil Deteksi, serta paginasi 10 item per halaman.
 */
class HasilDeteksiController extends BaseController
{
    private DummyDataService $dummyData;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
    }

    public function index()
    {
        $meta = $this->dummyData->getSystemMeta();
        $filterOptions = $this->dummyData->getFilterOptions();

        // Ambil dan sanitasi parameter query GET
        $rawCctv = (string)$this->request->getGet('cctv');
        $rawVehicle = (string)$this->request->getGet('vehicle');
        $rawResult = (string)$this->request->getGet('result');
        $rawPage = (int)$this->request->getGet('page');

        // Validasi parameter terhadap whitelist yang diizinkan
        $cctv = array_key_exists($rawCctv, $filterOptions['cctv']) ? $rawCctv : 'all';
        $vehicle = array_key_exists($rawVehicle, $filterOptions['vehicle']) ? $rawVehicle : 'all';
        $result = array_key_exists($rawResult, $filterOptions['result']) ? $rawResult : 'all';
        $page = max(1, $rawPage);

        $filters = [
            'cctv'    => $cctv,
            'vehicle' => $vehicle,
            'result'  => $result,
        ];

        $hasActiveFilter = ($cctv !== 'all' || $vehicle !== 'all' || $result !== 'all');

        $pagination = $this->dummyData->getTodayDetectionsPaginated($filters, $page, 10);

        $data = [
            'meta'            => $meta,
            'title'           => 'Hasil Deteksi Hari Ini - Admin Dishub',
            'activeMenu'      => 'hasil_deteksi',
            'adminUser'       => session()->get('admin_user') ?? 'Admin Dishub',
            'adminRole'       => session()->get('admin_role') ?? 'Administrator Sistem',
            'pagination'      => $pagination,
            'filters'         => $filters,
            'filterOptions'   => $filterOptions,
            'hasActiveFilter' => $hasActiveFilter,
        ];

        return view('admin/pages/hasil_deteksi', $data);
    }
}
