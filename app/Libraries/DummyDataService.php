<?php

namespace App\Libraries;

/**
 * DummyDataService
 * 
 * Penyedia data tiruan (dummy data) terpusat untuk tampilan pengunjung dan admin
 * Sistem Monitoring Over Dimension Dishub Kota Banjarbaru.
 * Menyediakan data kanonik bersama agar pengunjung dan admin memiliki konsistensi
 * data 100% tanpa inkonsistensi hitungan atau duplikasi hardcode.
 */
class DummyDataService
{
    /**
     * Cache memori untuk 128 record deteksi hari ini (25 Sep 2026)
     * agar pembentukan deterministik hanya dihitung sekali.
     */
    private static ?array $cachedTodayDetections = null;

    /**
     * Metadata sistem & info demo
     */
    public function getSystemMeta(): array
    {
        return [
            'app_name'      => 'Sistem Monitoring Over Dimension',
            'agency'        => 'Dinas Perhubungan Kota Banjarbaru',
            'city'          => 'Kota Banjarbaru',
            'province'      => 'Kalimantan Selatan',
            'timezone'      => 'WITA',
            'demo_date'     => '25 September 2026',
            'demo_time'     => '15.42 WITA',
            'is_demo'       => true,
            'demo_label'    => 'Data contoh',
            'copyright'     => '© 2026 Dinas Perhubungan Kota Banjarbaru.',
            'contact'       => [
                'address'   => 'Jl. Karang Rejo, Guntungmanggis, Kec. Landasan Ulin, Kota Banjar Baru, Kalimantan Selatan 70731',
                'phone'     => '(0511) 4772123',
                'email'     => 'admin@dishub.banjarbarukota.go.id',
                'instagram' => 'https://www.instagram.com/dishub.bjb/?hl=id',
                'facebook'  => 'https://www.facebook.com/dishubbanjarbaru/',
                'website'   => 'http://www.dishub.banjarbarukota.go.id/',
                'note'      => 'Informasi kontak resmi Dishub Kota Banjarbaru (Contoh tampilan demo)',
            ],
        ];
    }

    /**
     * Daftar 6 CCTV di Kota Banjarbaru (CAM-01 s/d CAM-06)
     * Catatan: CAM-03 berstatus Offline, 5 lainnya Online.
     */
    public function getCctvList(): array
    {
        return [
            [
                'code'        => 'CAM-01',
                'name'        => 'Simpang A Yani',
                'category'    => 'Simpang',
                'location'    => 'Jl. A. Yani Km 36, Banjarbaru',
                'status'      => 'online',
                'ip_stream'   => 'rtsp://demo-stream/cam-01',
                'image_thumb' => 'assets/img/cam-01.jpg',
                'map_coord'   => ['top' => '44%', 'left' => '40%'], // Posisi relatif pada peta SVG
                'description' => 'Persimpangan jalan utama arteri Jl. Jenderal Ahmad Yani Km 36 akses pusat kota.',
                'today_stats' => ['normal' => 24, 'kontainer' => 8, 'over_dimension' => 5],
            ],
            [
                'code'        => 'CAM-02',
                'name'        => 'Simpang Trikora',
                'category'    => 'Simpang',
                'location'    => 'Jl. Trikora Simpang Guntung Manggis',
                'status'      => 'online',
                'ip_stream'   => 'rtsp://demo-stream/cam-02',
                'image_thumb' => 'assets/img/cam-02.jpg',
                'map_coord'   => ['top' => '74%', 'left' => '32%'],
                'description' => 'Jalur lingkar selatan Trikora, perlintasan utama truk barang dan logistik.',
                'today_stats' => ['normal' => 19, 'kontainer' => 11, 'over_dimension' => 7],
            ],
            [
                'code'        => 'CAM-03',
                'name'        => 'Bundaran Banjarbaru',
                'category'    => 'Bundaran',
                'location'    => 'Bundaran Simpang Empat Banjarbaru',
                'status'      => 'offline', // Sesuai kesepakatan: CAM-03 OFFLINE
                'ip_stream'   => null,
                'image_thumb' => 'assets/img/cam-03.jpg',
                'map_coord'   => ['top' => '43%', 'left' => '72%'],
                'description' => 'Bundaran Simpang Empat penghubung Banjarbaru, Martapura, dan Pelaihari.',
                'today_stats' => ['normal' => 0, 'kontainer' => 0, 'over_dimension' => 0],
            ],
            [
                'code'        => 'CAM-04',
                'name'        => 'Gerbang Kota',
                'category'    => 'Ruas Jalan',
                'location'    => 'Jl. A. Yani Km 29 - Batas Kota',
                'status'      => 'online',
                'ip_stream'   => 'rtsp://demo-stream/cam-04',
                'image_thumb' => 'assets/img/cam-04.jpg',
                'map_coord'   => ['top' => '43%', 'left' => '12%'],
                'description' => 'Gerbang pintu masuk perbatasan Kota Banjarbaru dari arah Banjarmasin/Bandara.',
                'today_stats' => ['normal' => 18, 'kontainer' => 4, 'over_dimension' => 2],
            ],
            [
                'code'        => 'CAM-05',
                'name'        => 'Simpang Guntung',
                'category'    => 'Simpang',
                'location'    => 'Simpang Empat Guntung Payung',
                'status'      => 'online',
                'ip_stream'   => 'rtsp://demo-stream/cam-05',
                'image_thumb' => 'assets/img/cam-05.jpg',
                'map_coord'   => ['top' => '25%', 'left' => '30%'],
                'description' => 'Persimpangan kawasan Guntung Payung arah akses Bandara Syamsudin Noor.',
                'today_stats' => ['normal' => 12, 'kontainer' => 2, 'over_dimension' => 1],
            ],
            [
                'code'        => 'CAM-06',
                'name'        => 'Jalan Mistar Cokrokusumo',
                'category'    => 'Ruas Jalan',
                'location'    => 'Jl. Mistar Cokrokusumo Cempaka',
                'status'      => 'online',
                'ip_stream'   => 'rtsp://demo-stream/cam-06',
                'image_thumb' => 'assets/img/cam-06.jpg',
                'map_coord'   => ['top' => '78%', 'left' => '77%'],
                'description' => 'Ruas jalan arteri sekunder menuju kawasan Cempaka dan akses lingkar timur.',
                'today_stats' => ['normal' => 10, 'kontainer' => 2, 'over_dimension' => 3],
            ],
        ];
    }

    /**
     * Dapatkan detail satu CCTV berdasarkan kode (misal 'CAM-01')
     */
    public function getCctvByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        foreach ($this->getCctvList() as $cctv) {
            if ($cctv['code'] === $code) {
                return $cctv;
            }
        }
        return null;
    }

    /**
     * Dapatkan ringkasan statistik harian yang konsisten
     */
    public function getDailyStatistics(): array
    {
        $dishubLocation = new DishubCameraLocationService();
        $counts = $dishubLocation->getSummaryCounts();
        $totalCctv = $counts['installed']; // 17
        $activeCctv = 16; // 16 online, 1 offline

        // Nilai pasti sesuai instruksi desain:
        // Normal: 83, Kontainer: 27, Over Dimension: 18, Total: 128
        $normalCount = 83;
        $kontainerCount = 27;
        $overDimCount = 18;
        $totalCount = $normalCount + $kontainerCount + $overDimCount; // 128

        return [
            'total_detection' => $totalCount,
            'normal'          => $normalCount,
            'kontainer'       => $kontainerCount,
            'over_dimension'  => $overDimCount,
            'cctv_active'     => $activeCctv,
            'cctv_total'      => $totalCctv,
            'cctv_status_str' => "{$activeCctv}/{$totalCctv}",
            'percentages'     => [
                'normal'         => round(($normalCount / $totalCount) * 100, 1),
                'kontainer'      => round(($kontainerCount / $totalCount) * 100, 1),
                'over_dimension' => round(($overDimCount / $totalCount) * 100, 1),
            ],
        ];
    }

    /**
     * Data interval waktu per jam (00.00 s/d 23.00)
     * Sesuai ketentuan: jam 00 sampai 15 memiliki nilai,
     * jam 16 s/d 23 bernilai null (belum berlangsung).
     */
    public function getHourlyTimelineData(): array
    {
        $hours = [
            '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
            '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
            '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
            '18:00', '19:00', '20:00', '21:00', '22:00', '23:00',
        ];

        // Sesuai prompt:
        // Normal: 0,0,0,0,0,1,4,6,7,8,9,10,11,10,9,8 (Total: 83)
        // Kontainer: 0,0,0,0,0,0,1,2,2,3,3,3,4,3,2,4 (Total: 27)
        // Over Dimension: 0,0,0,0,0,0,1,1,1,1,1,1,2,2,5,3 (Total: 18)
        $normalValues = [0, 0, 0, 0, 0, 1, 4, 6, 7, 8, 9, 10, 11, 10, 9, 8];
        $kontainerValues = [0, 0, 0, 0, 0, 0, 1, 2, 2, 3, 3, 3, 4, 3, 2, 4];
        $overDimValues = [0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 2, 2, 5, 3];

        $normalSeries = [];
        $kontainerSeries = [];
        $overDimSeries = [];

        for ($i = 0; $i < 24; $i++) {
            if ($i < count($normalValues)) {
                $normalSeries[] = $normalValues[$i];
                $kontainerSeries[] = $kontainerValues[$i];
                $overDimSeries[] = $overDimValues[$i];
            } else {
                // Jam 16 s/d 23 bernilai null (belum berlangsung)
                $normalSeries[] = null;
                $kontainerSeries[] = null;
                $overDimSeries[] = null;
            }
        }

        return [
            'labels'         => $hours,
            'normal'         => $normalSeries,
            'kontainer'      => $kontainerSeries,
            'over_dimension' => $overDimSeries,
            'last_recorded'  => '15.42 WITA',
            'cutoff_hour'    => 15,
        ];
    }

    /**
     * Membangun dan mengembalikan 128 record kanonik deteksi hari ini (25 Sep 2026).
     * Dibentuk secara deterministik 100% konsisten dengan distribusi CCTV dan per-jam.
     * Diurutkan dari waktu paling baru (descending).
     */
    public function getTodayDetections(array $filters = []): array
    {
        if (self::$cachedTodayDetections === null) {
            self::$cachedTodayDetections = $this->buildDeterministicTodayDetections();
        }

        $records = self::$cachedTodayDetections;

        // Terapkan filter jika ada
        if (!empty($filters['cctv']) && $filters['cctv'] !== 'all') {
            $cctvCode = strtoupper(trim($filters['cctv']));
            $records = array_values(array_filter($records, static fn($r) => $r['cctv_code'] === $cctvCode));
        }

        if (!empty($filters['vehicle']) && $filters['vehicle'] !== 'all') {
            $vType = trim($filters['vehicle']);
            $records = array_values(array_filter($records, static fn($r) => strcasecmp($r['vehicle_type'], $vType) === 0));
        }

        if (!empty($filters['result']) && $filters['result'] !== 'all') {
            $res = trim($filters['result']);
            $records = array_values(array_filter($records, static fn($r) => strcasecmp($r['detection_result'], $res) === 0));
        }

        return $records;
    }

    /**
     * Dapatkan record deteksi hari ini dengan sistem pagination sederhana
     */
    public function getTodayDetectionsPaginated(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $allFiltered = $this->getTodayDetections($filters);
        $total = count($allFiltered);
        $totalPages = max(1, (int)ceil($total / $perPage));

        // Validasi boundary page
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $pagedData = array_slice($allFiltered, $offset, $perPage);
        $from = ($total === 0) ? 0 : $offset + 1;
        $to = min($offset + $perPage, $total);

        return [
            'data'        => $pagedData,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'from'        => $from,
            'to'          => $to,
            'has_prev'    => $page > 1,
            'has_next'    => $page < $totalPages,
        ];
    }

    /**
     * Dapatkan N record terbaru hari ini (untuk widget Dashboard Admin)
     */
    public function getLatestDetections(int $limit = 5): array
    {
        $all = $this->getTodayDetections();
        return array_slice($all, 0, $limit);
    }

    /**
     * Opsi-opsi filter untuk dropdown di Halaman Hasil Deteksi
     */
    public function getFilterOptions(): array
    {
        return [
            'cctv' => [
                'all'    => 'Semua CCTV',
                'CAM-01' => 'CAM-01 - Simpang A Yani',
                'CAM-02' => 'CAM-02 - Simpang Trikora',
                'CAM-04' => 'CAM-04 - Gerbang Kota',
                'CAM-05' => 'CAM-05 - Simpang Guntung',
                'CAM-06' => 'CAM-06 - Jalan Mistar Cokrokusumo',
            ],
            'vehicle' => [
                'all'            => 'Semua Jenis',
                'Truk'           => 'Truk',
                'Pickup'         => 'Pickup',
                'Truk Kontainer' => 'Truk Kontainer',
            ],
            'result' => [
                'all'            => 'Semua Hasil',
                'Normal'         => 'Normal',
                'Kontainer'      => 'Kontainer',
                'Over Dimension' => 'Over Dimension',
            ],
        ];
    }

    /**
     * Ringkasan & insight historis 7 hari (19 s/d 25 September 2026).
     * Semua angka diturunkan dari sumber data mingguan yang konsisten.
     */
    public function getWeeklyHistoricalSummary(): array
    {
        // Data harian selama 7 hari
        $dailyData = [
            ['date' => '2026-09-19', 'label' => '19 Sep', 'day' => 'Sabtu',  'normal' => 70, 'kontainer' => 23, 'over_dimension' => 12, 'total' => 105],
            ['date' => '2026-09-20', 'label' => '20 Sep', 'day' => 'Minggu', 'normal' => 74, 'kontainer' => 25, 'over_dimension' => 15, 'total' => 114],
            ['date' => '2026-09-21', 'label' => '21 Sep', 'day' => 'Senin',  'normal' => 66, 'kontainer' => 21, 'over_dimension' => 9,  'total' => 96],
            ['date' => '2026-09-22', 'label' => '22 Sep', 'day' => 'Selasa', 'normal' => 90, 'kontainer' => 28, 'over_dimension' => 24, 'total' => 142],
            ['date' => '2026-09-23', 'label' => '23 Sep', 'day' => 'Rabu',   'normal' => 81, 'kontainer' => 24, 'over_dimension' => 16, 'total' => 121],
            ['date' => '2026-09-24', 'label' => '24 Sep', 'day' => 'Kamis',  'normal' => 94, 'kontainer' => 27, 'over_dimension' => 21, 'total' => 142],
            ['date' => '2026-09-25', 'label' => '25 Sep', 'day' => 'Jumat',  'normal' => 83, 'kontainer' => 27, 'over_dimension' => 18, 'total' => 128],
        ];

        $totalNormal = array_sum(array_column($dailyData, 'normal'));       // 558
        $totalKontainer = array_sum(array_column($dailyData, 'kontainer')); // 175
        $totalOverDim = array_sum(array_column($dailyData, 'over_dimension')); // 115
        $grandTotal = array_sum(array_column($dailyData, 'total'));         // 848

        // Akumulasi Over Dimension per jam selama 7 hari (Total = 115)
        $hourlyAccumulatedOD = [
            '00:00' => 0, '01:00' => 0, '02:00' => 0, '03:00' => 0, '04:00' => 0,
            '05:00' => 2, '06:00' => 5, '07:00' => 7, '08:00' => 9, '09:00' => 8,
            '10:00' => 9, '11:00' => 10, '12:00' => 12, '13:00' => 11, '14:00' => 17,
            '15:00' => 13, '16:00' => 6, '17:00' => 4, '18:00' => 2, '19:00' => 0,
            '20:00' => 0, '21:00' => 0, '22:00' => 0, '23:00' => 0,
        ];

        // Rekap Over Dimension per CCTV selama 7 hari (Total = 115)
        $cctvWeeklyOD = [
            'CAM-01' => ['name' => 'Simpang A Yani', 'count' => 28],
            'CAM-02' => ['name' => 'Simpang Trikora', 'count' => 45],
            'CAM-03' => ['name' => 'Bundaran Banjarbaru', 'count' => 0],
            'CAM-04' => ['name' => 'Gerbang Kota', 'count' => 16],
            'CAM-05' => ['name' => 'Simpang Guntung', 'count' => 9],
            'CAM-06' => ['name' => 'Jalan Mistar Cokrokusumo', 'count' => 17],
        ];

        // 1. Hitung Hari OD Terbanyak secara dinamis
        $peakDay = $dailyData[0];
        foreach ($dailyData as $d) {
            if ($d['over_dimension'] > $peakDay['over_dimension']) {
                $peakDay = $d;
            }
        }

        // 2. Hitung Jam OD Terbanyak secara dinamis
        $peakHourLabel = '14:00';
        $peakHourVal = -1;
        foreach ($hourlyAccumulatedOD as $h => $val) {
            if ($val > $peakHourVal) {
                $peakHourVal = $val;
                $peakHourLabel = $h;
            }
        }
        $hourStart = (int)substr($peakHourLabel, 0, 2);
        $hourIntervalStr = sprintf('%02d:00 - %02d:00 WITA', $hourStart, $hourStart + 1);

        // 3. Hitung CCTV OD Terbanyak secara dinamis
        $peakCctvCode = 'CAM-02';
        $peakCctvInfo = $cctvWeeklyOD['CAM-02'];
        $maxCctvVal = -1;
        foreach ($cctvWeeklyOD as $cCode => $info) {
            if ($info['count'] > $maxCctvVal) {
                $maxCctvVal = $info['count'];
                $peakCctvCode = $cCode;
                $peakCctvInfo = $info;
            }
        }

        // Status CCTV Aktif saat ini (snapshot 5/6)
        $dailyStats = $this->getDailyStatistics();

        return [
            'period_label'   => '19 - 25 September 2026',
            'period_days'    => 7,
            'totals'         => [
                'grand_total'    => $grandTotal,      // 848
                'normal'         => $totalNormal,     // 558
                'kontainer'      => $totalKontainer,  // 175
                'over_dimension' => $totalOverDim,    // 115
                'cctv_active_str'=> $dailyStats['cctv_status_str'], // '5/6'
            ],
            'percentages'    => [
                'normal'         => round(($totalNormal / $grandTotal) * 100, 1),      // 65.8%
                'kontainer'      => round(($totalKontainer / $grandTotal) * 100, 1),   // 20.6%
                'over_dimension' => round(($totalOverDim / $grandTotal) * 100, 1),     // 13.6%
            ],
            'daily_breakdown' => $dailyData,
            'od_trend'        => [
                'labels' => array_column($dailyData, 'label'),
                'values' => array_column($dailyData, 'over_dimension'),
            ],
            'hourly_od'       => [
                'labels' => array_keys($hourlyAccumulatedOD),
                'values' => array_values($hourlyAccumulatedOD),
            ],
            'cctv_od'         => $cctvWeeklyOD,
            'insights'        => [
                'peak_day'  => [
                    'title'    => "{$peakDay['day']}, {$peakDay['label']} 2026",
                    'subtitle' => "{$peakDay['over_dimension']} deteksi Over Dimension",
                    'count'    => $peakDay['over_dimension'],
                ],
                'peak_hour' => [
                    'title'    => "Pukul {$hourIntervalStr}",
                    'subtitle' => "{$peakHourVal} deteksi Over Dimension",
                    'count'    => $peakHourVal,
                ],
                'peak_cctv' => [
                    'title'    => "{$peakCctvCode} - {$peakCctvInfo['name']}",
                    'subtitle' => "{$peakCctvInfo['count']} deteksi Over Dimension",
                    'count'    => $peakCctvInfo['count'],
                ],
            ],
        ];
    }

    /**
     * Membangun 128 record deteksi hari ini secara deterministik
     */
    private function buildDeterministicTodayDetections(): array
    {
        $cctvs = [
            'CAM-01' => ['name' => 'Simpang A Yani', 'normal' => 24, 'kontainer' => 8, 'over_dimension' => 5],
            'CAM-02' => ['name' => 'Simpang Trikora', 'normal' => 19, 'kontainer' => 11, 'over_dimension' => 7],
            'CAM-04' => ['name' => 'Gerbang Kota', 'normal' => 18, 'kontainer' => 4, 'over_dimension' => 2],
            'CAM-05' => ['name' => 'Simpang Guntung', 'normal' => 12, 'kontainer' => 2, 'over_dimension' => 1],
            'CAM-06' => ['name' => 'Jalan Mistar Cokrokusumo', 'normal' => 10, 'kontainer' => 2, 'over_dimension' => 3],
        ];

        // Pool kuota per CCTV per hasil
        $pool = [];
        foreach ($cctvs as $code => $info) {
            for ($i = 0; $i < $info['normal']; $i++) {
                $pool['Normal'][] = ['code' => $code, 'name' => $info['name']];
            }
            for ($i = 0; $i < $info['kontainer']; $i++) {
                $pool['Kontainer'][] = ['code' => $code, 'name' => $info['name']];
            }
            for ($i = 0; $i < $info['over_dimension']; $i++) {
                $pool['Over Dimension'][] = ['code' => $code, 'name' => $info['name']];
            }
        }

        // Jumlah per jam (05:00 s/d 15:00)
        $hourly = [
            5  => ['Normal' => 1, 'Kontainer' => 0, 'Over Dimension' => 0],
            6  => ['Normal' => 4, 'Kontainer' => 1, 'Over Dimension' => 1],
            7  => ['Normal' => 6, 'Kontainer' => 2, 'Over Dimension' => 1],
            8  => ['Normal' => 7, 'Kontainer' => 2, 'Over Dimension' => 1],
            9  => ['Normal' => 8, 'Kontainer' => 3, 'Over Dimension' => 1],
            10 => ['Normal' => 9, 'Kontainer' => 3, 'Over Dimension' => 1],
            11 => ['Normal' => 10, 'Kontainer' => 3, 'Over Dimension' => 1],
            12 => ['Normal' => 11, 'Kontainer' => 4, 'Over Dimension' => 2],
            13 => ['Normal' => 10, 'Kontainer' => 3, 'Over Dimension' => 2],
            14 => ['Normal' => 9, 'Kontainer' => 2, 'Over Dimension' => 5],
            15 => ['Normal' => 8, 'Kontainer' => 4, 'Over Dimension' => 3],
        ];

        $records = [];

        foreach ($hourly as $hour => $cats) {
            $totalInHour = $cats['Normal'] + $cats['Kontainer'] + $cats['Over Dimension'];
            $maxMinute = ($hour === 15) ? 41 : 58;

            // Susun urutan kategori dalam jam tersebut agar terdistribusi alami
            // Pada jam 15 WITA, pastikan Over Dimension, Normal, dan Kontainer berselang-seling
            $hourSeq = [];
            if ($hour === 15) {
                $hourSeq = [
                    'Over Dimension', // slot 1 -> menit 41
                    'Normal',         // slot 2 -> menit 38 (Tampil di 5 Deteksi Terbaru & Hal 1)
                    'Kontainer',      // slot 3 -> menit 35
                    'Over Dimension', // slot 4 -> menit 33
                    'Normal',         // slot 5 -> menit 30 (Tampil di 5 Deteksi Terbaru & Hal 1)
                    'Kontainer',      // slot 6 -> menit 28
                    'Normal',         // slot 7 -> menit 25
                    'Over Dimension', // slot 8 -> menit 22
                    'Normal',         // slot 9 -> menit 19
                    'Kontainer',      // slot 10 -> menit 16
                    'Normal',         // slot 11 -> menit 13
                    'Kontainer',      // slot 12 -> menit 10
                    'Normal',         // slot 13 -> menit 7
                    'Normal',         // slot 14 -> menit 4
                    'Normal',         // slot 15 -> menit 2
                ];
            } else {
                $rem = $cats;
                while (count($hourSeq) < $totalInHour) {
                    foreach (['Over Dimension', 'Kontainer', 'Normal'] as $c) {
                        if ($rem[$c] > 0) {
                            $hourSeq[] = $c;
                            $rem[$c]--;
                        }
                    }
                }
            }

            $step = ($maxMinute - 2) / max(1, $totalInHour - 1);

            foreach ($hourSeq as $mIndex => $cat) {
                $cam = array_shift($pool[$cat]);
                $minute = max(2, min($maxMinute, (int)round($maxMinute - ($mIndex * $step))));
                $second = ($mIndex * 19 + $hour * 7) % 60;
                $timeStr = sprintf('%02d:%02d WITA', $hour, $minute);

                // Jenis Kendaraan yang relevan
                if ($cat === 'Kontainer') {
                    $vType = 'Truk Kontainer';
                } elseif ($cat === 'Over Dimension') {
                    $vType = ($mIndex % 4 === 0) ? 'Pickup' : 'Truk';
                } else {
                    $vType = ($mIndex % 3 === 0) ? 'Pickup' : 'Truk';
                }

                $records[] = [
                    'id'               => '',
                    'date'             => '2026-09-25',
                    'time'             => $timeStr,
                    'timestamp_raw'    => sprintf('2026-09-25 %02d:%02d:%02d', $hour, $minute, $second),
                    'hour'             => $hour,
                    'cctv_code'        => $cam['code'],
                    'cctv_name'        => $cam['name'],
                    'vehicle_type'     => $vType,
                    'detection_result' => $cat,
                ];
            }
        }

        // Urutkan menurun berdasarkan waktu (paling baru di atas)
        usort($records, static function ($a, $b) {
            return strcmp($b['timestamp_raw'], $a['timestamp_raw']);
        });

        // Tetapkan ID secara berurutan
        foreach ($records as $idx => &$rec) {
            $rec['id'] = sprintf('DET-20260925-%03d', $idx + 1);
        }
        unset($rec);

        return $records;
    }
}
