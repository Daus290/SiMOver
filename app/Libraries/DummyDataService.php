<?php

namespace App\Libraries;

/**
 * DummyDataService
 * 
 * Penyedia data tiruan (dummy data) terpusat untuk Portal Publik SiMover
 * Dinas Perhubungan Kota Banjarbaru.
 * Menyediakan data kanonik bersama dengan konsistensi matematika 100% untuk
 * periode 1 Hari, 7 Hari, dan 30 Hari (tanpa duplikasi hardcode atau inkonsistensi).
 */
class DummyDataService
{
    /**
     * Cache memori untuk 30 hari dataset deteksi kanonik (27 Agu s/d 25 Sep 2026)
     */
    private static ?array $cachedAllDetections = null;

    /**
     * Daftar titik kamera CCTV untuk pembuatan record kanonik
     */
    private array $cctvPool = [
        ['code' => 'CAM-01', 'name' => 'Simpang A Yani'],
        ['code' => 'CAM-02', 'name' => 'Simpang Trikora'],
        ['code' => 'CAM-04', 'name' => 'Gerbang Kota'],
        ['code' => 'CAM-05', 'name' => 'Simpang Guntung'],
        ['code' => 'CAM-06', 'name' => 'Jalan Mistar Cokrokusumo'],
    ];

    /**
     * Rencana breakdown harian 30 hari deterministik (27 Agustus s/d 25 September 2026).
     * 7 hari terakhir (19-25 Sep) merefleksikan 848 record mingguan kanonik.
     */
    private array $dailyBreakdownPlan = [
        // 23 hari sebelum periode mingguan (27 Agu - 18 Sep 2026)
        ['date' => '2026-08-27', 'normal' => 76, 'kontainer' => 22, 'over_dimension' => 14],
        ['date' => '2026-08-28', 'normal' => 85, 'kontainer' => 26, 'over_dimension' => 17],
        ['date' => '2026-08-29', 'normal' => 72, 'kontainer' => 24, 'over_dimension' => 13],
        ['date' => '2026-08-30', 'normal' => 68, 'kontainer' => 20, 'over_dimension' => 11],
        ['date' => '2026-08-31', 'normal' => 88, 'kontainer' => 28, 'over_dimension' => 19],
        ['date' => '2026-09-01', 'normal' => 82, 'kontainer' => 25, 'over_dimension' => 16],
        ['date' => '2026-09-02', 'normal' => 89, 'kontainer' => 27, 'over_dimension' => 18],
        ['date' => '2026-09-03', 'normal' => 78, 'kontainer' => 23, 'over_dimension' => 15],
        ['date' => '2026-09-04', 'normal' => 86, 'kontainer' => 27, 'over_dimension' => 18],
        ['date' => '2026-09-05', 'normal' => 74, 'kontainer' => 22, 'over_dimension' => 13],
        ['date' => '2026-09-06', 'normal' => 69, 'kontainer' => 21, 'over_dimension' => 12],
        ['date' => '2026-09-07', 'normal' => 91, 'kontainer' => 29, 'over_dimension' => 20],
        ['date' => '2026-09-08', 'normal' => 84, 'kontainer' => 26, 'over_dimension' => 17],
        ['date' => '2026-09-09', 'normal' => 79, 'kontainer' => 24, 'over_dimension' => 16],
        ['date' => '2026-09-10', 'normal' => 88, 'kontainer' => 28, 'over_dimension' => 19],
        ['date' => '2026-09-11', 'normal' => 83, 'kontainer' => 25, 'over_dimension' => 17],
        ['date' => '2026-09-12', 'normal' => 71, 'kontainer' => 23, 'over_dimension' => 14],
        ['date' => '2026-09-13', 'normal' => 67, 'kontainer' => 20, 'over_dimension' => 10],
        ['date' => '2026-09-14', 'normal' => 90, 'kontainer' => 29, 'over_dimension' => 21],
        ['date' => '2026-09-15', 'normal' => 86, 'kontainer' => 27, 'over_dimension' => 18],
        ['date' => '2026-09-16', 'normal' => 80, 'kontainer' => 25, 'over_dimension' => 16],
        ['date' => '2026-09-17', 'normal' => 87, 'kontainer' => 28, 'over_dimension' => 19],
        ['date' => '2026-09-18', 'normal' => 93, 'kontainer' => 29, 'over_dimension' => 22],
        // 7 hari terakhir (19 - 25 Sep 2026, total = 848)
        ['date' => '2026-09-19', 'normal' => 70, 'kontainer' => 23, 'over_dimension' => 12],
        ['date' => '2026-09-20', 'normal' => 74, 'kontainer' => 25, 'over_dimension' => 15],
        ['date' => '2026-09-21', 'normal' => 66, 'kontainer' => 21, 'over_dimension' =>  9],
        ['date' => '2026-09-22', 'normal' => 90, 'kontainer' => 28, 'over_dimension' => 24],
        ['date' => '2026-09-23', 'normal' => 81, 'kontainer' => 24, 'over_dimension' => 16],
        ['date' => '2026-09-24', 'normal' => 94, 'kontainer' => 27, 'over_dimension' => 21],
        ['date' => '2026-09-25', 'normal' => 83, 'kontainer' => 27, 'over_dimension' => 18],
    ];

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
                'map_coord'   => ['top' => '44%', 'left' => '40%'],
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
                'status'      => 'offline',
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
     * Dapatkan detail satu CCTV berdasarkan kode
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
     * Ringkasan statistik harian kanonik (digunakan oleh Beranda dan Dashboard 1 Hari)
     */
    public function getDailyStatistics(): array
    {
        $dishubLocation = new DishubCameraLocationService();
        $counts = $dishubLocation->getSummaryCounts();
        $totalCctv = $counts['installed']; // 17
        $activeCctv = 16;                  // 16 online, 1 offline

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
     * Data interval waktu per jam (00.00 s/d 23.00) untuk 1 Hari
     */
    public function getHourlyTimelineData(): array
    {
        $hours = [
            '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
            '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
            '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
            '18:00', '19:00', '20:00', '21:00', '22:00', '23:00',
        ];

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
     * Membangun atau mengembalikan seluruh 30 hari deteksi deterministik
     * @return array<int, array<string, mixed>>
     */
    public function getAllDetections30Days(): array
    {
        if (self::$cachedAllDetections === null) {
            self::$cachedAllDetections = $this->buildDeterministicAllDetections();
        }
        return self::$cachedAllDetections;
    }

    /**
     * Dapatkan record deteksi kanonik untuk periode yang dipilih (1, 7, atau 30 hari)
     * @return array<int, array<string, mixed>>
     */
    public function getDetectionsForPeriod(int $period): array
    {
        $all = $this->getAllDetections30Days();

        if ($period === 1) {
            return array_values(array_filter($all, static fn($r) => $r['date'] === '2026-09-25'));
        }

        if ($period === 7) {
            return array_values(array_filter($all, static fn($r) => $r['date'] >= '2026-09-19' && $r['date'] <= '2026-09-25'));
        }

        // Default 30 hari
        return $all;
    }

    /**
     * Layanan terpadu untuk Dashboard Publik:
     * Menyediakan statistik global, 3 insight dinamis, dataset grafik (hourly/daily),
     * dan tabel terpaginasi dengan filter aktif yang konsisten 100%.
     */
    public function getDashboardData(int $period = 1, array $tableFilters = [], int $page = 1, int $perPage = 10): array
    {
        // 1. Validasi whitelist periode (1, 7, 30)
        if (!in_array($period, [1, 7, 30], true)) {
            $period = 1;
        }

        // 2. Ambil catatan kanonik untuk periode terpilih (unfiltered)
        $periodRecords = $this->getDetectionsForPeriod($period);
        $totalCount = count($periodRecords);

        // 3. Hitung ringkasan statistik periode
        $normalCount = 0;
        $kontainerCount = 0;
        $overDimCount = 0;

        foreach ($periodRecords as $rec) {
            if ($rec['detection_result'] === 'Normal') {
                $normalCount++;
            } elseif ($rec['detection_result'] === 'Kontainer') {
                $kontainerCount++;
            } elseif ($rec['detection_result'] === 'Over Dimension') {
                $overDimCount++;
            }
        }

        // Persentase aman untuk 0
        $pctNormal = ($totalCount > 0) ? round(($normalCount / $totalCount) * 100, 1) : 0.0;
        $pctKontainer = ($totalCount > 0) ? round(($kontainerCount / $totalCount) * 100, 1) : 0.0;
        $pctOverDim = ($totalCount > 0) ? round(($overDimCount / $totalCount) * 100, 1) : 0.0;

        // Status CCTV Terpantau saat ini (kondisi kamera live, bukan akumulasi waktu)
        $dishubLocation = new DishubCameraLocationService();
        $locCounts = $dishubLocation->getSummaryCounts();
        $totalCctv = $locCounts['installed'] ?? 17;
        $activeCctv = 16;

        // Label periode & rentang tanggal
        $periodLabels = [
            1  => [
                'title'      => '1 Hari',
                'range'      => '25 September 2026 (00:00 - 15:42 WITA)',
                'short_date' => '25 September 2026',
            ],
            7  => [
                'title'      => '7 Hari',
                'range'      => '19 - 25 September 2026 (s/d 15:42 WITA)',
                'short_date' => '19 - 25 Sep 2026',
            ],
            30 => [
                'title'      => '30 Hari',
                'range'      => '27 Agustus - 25 September 2026 (s/d 15:42 WITA)',
                'short_date' => '27 Agu - 25 Sep 2026',
            ],
        ];

        // 4. Hitung 3 Insight Penting dari record periode terpilih
        $insights = $this->calculateDynamicInsights($periodRecords, $period);

        // 5. Susun data grafik (hourly untuk 1 hari, daily untuk 7/30 hari)
        $chartData = $this->buildChartData($periodRecords, $period);

        // 6. Filter tabel hasil deteksi
        $filteredRecords = $periodRecords;
        $hasActiveFilter = false;

        if (!empty($tableFilters['cctv']) && $tableFilters['cctv'] !== 'all') {
            $cctvCode = strtoupper(trim((string)$tableFilters['cctv']));
            $filteredRecords = array_values(array_filter($filteredRecords, static fn($r) => $r['cctv_code'] === $cctvCode));
            $hasActiveFilter = true;
        }

        if (!empty($tableFilters['vehicle']) && $tableFilters['vehicle'] !== 'all') {
            $vType = trim((string)$tableFilters['vehicle']);
            $filteredRecords = array_values(array_filter($filteredRecords, static fn($r) => strcasecmp($r['vehicle_type'], $vType) === 0));
            $hasActiveFilter = true;
        }

        if (!empty($tableFilters['result']) && $tableFilters['result'] !== 'all') {
            $res = trim((string)$tableFilters['result']);
            $filteredRecords = array_values(array_filter($filteredRecords, static fn($r) => strcasecmp($r['detection_result'], $res) === 0));
            $hasActiveFilter = true;
        }

        // 7. Paginasi tabel hasil deteksi
        $filteredTotal = count($filteredRecords);
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int)ceil($filteredTotal / $perPage));

        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $pagedData = array_slice($filteredRecords, $offset, $perPage);
        $from = ($filteredTotal === 0) ? 0 : $offset + 1;
        $to = min($offset + $perPage, $filteredTotal);

        return [
            'period'             => $period,
            'period_info'        => $periodLabels[$period],
            'period_label'       => $periodLabels[$period]['range'],
            'period_date_range'  => $periodLabels[$period]['range'],
            'stats'              => [
                'total_detection' => $totalCount,
                'normal'          => $normalCount,
                'kontainer'       => $kontainerCount,
                'over_dimension'  => $overDimCount,
                'cctv_active'     => $activeCctv,
                'cctv_total'      => $totalCctv,
                'cctv_status_str' => "{$activeCctv}/{$totalCctv}",
                'percentages'     => [
                    'normal'         => $pctNormal,
                    'kontainer'      => $pctKontainer,
                    'over_dimension' => $pctOverDim,
                ],
            ],
            'insights'           => $insights,
            'chart_data'         => $chartData,
            'table'              => [
                'data'             => $pagedData,
                'total'            => $filteredTotal,
                'global_total'     => $totalCount,
                'page'             => $page,
                'per_page'         => $perPage,
                'total_pages'      => $totalPages,
                'from'             => $from,
                'to'               => $to,
                'has_prev'         => $page > 1,
                'has_next'         => $page < $totalPages,
                'has_active_filter'=> $hasActiveFilter,
                'filter_options'   => $this->getFilterOptions(),
                'filters'          => [
                    'cctv'    => $tableFilters['cctv'] ?? 'all',
                    'vehicle' => $tableFilters['vehicle'] ?? 'all',
                    'result'  => $tableFilters['result'] ?? 'all',
                ],
            ],
        ];
    }

    /**
     * Hitung insight secara dinamis dari catatan periode terpilih
     */
    private function calculateDynamicInsights(array $records, int $period): array
    {
        $odRecords = array_values(array_filter($records, static fn($r) => $r['detection_result'] === 'Over Dimension'));

        // 1. Hari OD Terbanyak
        $dayCounts = [];
        foreach ($odRecords as $r) {
            $d = $r['date'];
            if (!isset($dayCounts[$d])) {
                $dayCounts[$d] = [
                    'count'     => 0,
                    'formatted' => $r['date_formatted'],
                    'date'      => $d,
                ];
            }
            $dayCounts[$d]['count']++;
        }

        $peakDay = [
            'title'    => 'Tidak ada',
            'subtitle' => 'Belum ada data Over Dimension',
            'count'    => 0,
        ];

        if (!empty($dayCounts)) {
            // Aturan tie-break stabil: ambil tanggal paling baru jika seri
            uksort($dayCounts, static fn($a, $b) => strcmp($b, $a));
            uasort($dayCounts, static fn($a, $b) => $b['count'] <=> $a['count']);
            $firstDate = array_key_first($dayCounts);
            $firstInfo = $dayCounts[$firstDate];

            // Dapatkan hari dalam bahasa Indonesia
            $dayName = $this->getDayNameIndo($firstDate);

            $peakDay = [
                'title'    => ($period === 1) ? "{$dayName}, {$firstInfo['formatted']}" : "{$dayName}, {$firstInfo['formatted']}",
                'subtitle' => "{$firstInfo['count']} deteksi Over Dimension",
                'count'    => $firstInfo['count'],
            ];
        }

        // 2. Jam OD Terbanyak (akumulasi jam dalam periode)
        $hourCounts = array_fill(0, 24, 0);
        foreach ($odRecords as $r) {
            $h = (int)$r['hour'];
            if (isset($hourCounts[$h])) {
                $hourCounts[$h]++;
            }
        }

        // Prioritas tie-break: jam kerja siang/sore (14:00 WITA)
        arsort($hourCounts);
        $topHour = array_key_first($hourCounts);
        $topHourCount = $hourCounts[$topHour];

        $peakHour = [
            'title'    => 'Tidak ada',
            'subtitle' => 'Belum ada data Over Dimension',
            'count'    => 0,
        ];

        if ($topHourCount > 0) {
            $hourStart = sprintf('%02d:00', $topHour);
            $hourEnd = sprintf('%02d:00', ($topHour + 1) % 24);
            $peakHour = [
                'title'    => "Pukul {$hourStart} - {$hourEnd} WITA",
                'subtitle' => ($period === 1) ? "{$topHourCount} deteksi Over Dimension" : "Akumulasi {$topHourCount} deteksi Over Dimension",
                'count'    => $topHourCount,
            ];
        }

        // 3. CCTV OD Terbanyak
        $cctvCounts = [];
        foreach ($odRecords as $r) {
            $c = $r['cctv_code'];
            if (!isset($cctvCounts[$c])) {
                $cctvCounts[$c] = [
                    'count' => 0,
                    'name'  => $r['cctv_name'],
                ];
            }
            $cctvCounts[$c]['count']++;
        }

        $peakCctv = [
            'title'    => 'Tidak ada',
            'subtitle' => 'Belum ada data Over Dimension',
            'count'    => 0,
        ];

        if (!empty($cctvCounts)) {
            uasort($cctvCounts, static fn($a, $b) => $b['count'] <=> $a['count']);
            $topCctvCode = array_key_first($cctvCounts);
            $topCctvInfo = $cctvCounts[$topCctvCode];
            $peakCctv = [
                'title'    => "{$topCctvCode} - {$topCctvInfo['name']}",
                'subtitle' => "{$topCctvInfo['count']} deteksi Over Dimension",
                'count'    => $topCctvInfo['count'],
            ];
        }

        return [
            'peak_day'  => $peakDay,
            'peak_hour' => $peakHour,
            'peak_cctv' => $peakCctv,
        ];
    }

    /**
     * Bentuk data grafik Chart.js sesuai periode
     */
    private function buildChartData(array $records, int $period): array
    {
        if ($period === 1) {
            // Mode Hourly: 24 jam dengan cutoff 15.42 WITA
            $timeline = $this->getHourlyTimelineData();
            return [
                'mode'           => 'hourly',
                'title_line'     => 'Analisis Deteksi Hari Ini',
                'subtitle_line'  => 'Interval 00:00 s/d 23:00 WITA',
                'title_bar'      => 'Volume Deteksi per Jam',
                'subtitle_bar'   => 'Distribusi bertumpuk tiga kategori kendaraan per jam',
                'note'           => '* Data tersedia sampai 15.42 WITA',
                'labels'         => $timeline['labels'],
                'normal'         => $timeline['normal'],
                'kontainer'      => $timeline['kontainer'],
                'over_dimension' => $timeline['over_dimension'],
                'last_recorded'  => '15.42 WITA',
                'cutoff_hour'    => 15,
            ];
        }

        // Mode Daily untuk 7 Hari & 30 Hari: Agregasi per tanggal
        // Urutkan tanggal secara kronologis (dari tanggal terlama ke terbaru)
        $dailyAggregates = [];

        // Kumpulkan rentang tanggal yang unik dalam periode
        foreach ($records as $r) {
            $d = $r['date'];
            if (!isset($dailyAggregates[$d])) {
                $dailyAggregates[$d] = [
                    'label'          => $this->formatShortDateLabel($d),
                    'normal'         => 0,
                    'kontainer'      => 0,
                    'over_dimension' => 0,
                ];
            }
            if ($r['detection_result'] === 'Normal') {
                $dailyAggregates[$d]['normal']++;
            } elseif ($r['detection_result'] === 'Kontainer') {
                $dailyAggregates[$d]['kontainer']++;
            } elseif ($r['detection_result'] === 'Over Dimension') {
                $dailyAggregates[$d]['over_dimension']++;
            }
        }

        // Urutkan kronologis
        ksort($dailyAggregates);

        $labels = [];
        $normalSeries = [];
        $kontainerSeries = [];
        $overDimSeries = [];

        foreach ($dailyAggregates as $dateKey => $data) {
            $labels[] = $data['label'];
            $normalSeries[] = $data['normal'];
            $kontainerSeries[] = $data['kontainer'];
            $overDimSeries[] = $data['over_dimension'];
        }

        $periodTitle = ($period === 7) ? 'Tren Deteksi 7 Hari Terakhir' : 'Tren Deteksi 30 Hari Terakhir';
        $periodBarTitle = ($period === 7) ? 'Volume Deteksi per Hari (7 Hari)' : 'Volume Deteksi per Hari (30 Hari)';

        return [
            'mode'           => 'daily',
            'title_line'     => $periodTitle,
            'subtitle_line'  => 'Pergerakan deteksi harian per kategori kendaraan',
            'title_bar'      => $periodBarTitle,
            'subtitle_bar'   => 'Volume total kendaraan per tanggal pengamatan',
            'note'           => '* Periode observasi s/d 25 September 2026 pukul 15.42 WITA',
            'labels'         => $labels,
            'normal'         => $normalSeries,
            'kontainer'      => $kontainerSeries,
            'over_dimension' => $overDimSeries,
            'last_recorded'  => '15.42 WITA',
            'cutoff_hour'    => null,
        ];
    }

    /**
     * Membangun seluruh 30 hari data kanonik secara deterministik
     */
    private function buildDeterministicAllDetections(): array
    {
        $allRecords = [];

        foreach ($this->dailyBreakdownPlan as $dayInfo) {
            $date = $dayInfo['date'];
            $dateParts = explode('-', $date);
            $cleanDate = $dateParts[0] . $dateParts[1] . $dateParts[2];
            $dateFormatted = $this->formatDateIndo($date);

            if ($date === '2026-09-25') {
                // Hari ke-25: Gunakan persis 128 record kanonik existing
                $dayRecords = $this->buildDeterministicTodayDetections();
            } else {
                // Hari lainnya: bentuk catatan deterministik konsisten
                $dayRecords = $this->buildHistoricalDayRecords($date, $cleanDate, $dateFormatted, $dayInfo);
            }

            foreach ($dayRecords as $r) {
                $allRecords[] = $r;
            }
        }

        // Urutkan menurun: datetime paling baru di atas, tie-breaker ID
        usort($allRecords, static function ($a, $b) {
            $cmp = strcmp($b['timestamp_raw'], $a['timestamp_raw']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($b['id'], $a['id']);
        });

        return $allRecords;
    }

    /**
     * Membangun 128 record deteksi hari ini (25 Sep 2026) secara deterministik
     */
    public function buildDeterministicTodayDetections(): array
    {
        $cctvs = [
            'CAM-01' => ['name' => 'Simpang A Yani', 'normal' => 24, 'kontainer' => 8, 'over_dimension' => 5],
            'CAM-02' => ['name' => 'Simpang Trikora', 'normal' => 19, 'kontainer' => 11, 'over_dimension' => 7],
            'CAM-04' => ['name' => 'Gerbang Kota', 'normal' => 18, 'kontainer' => 4, 'over_dimension' => 2],
            'CAM-05' => ['name' => 'Simpang Guntung', 'normal' => 12, 'kontainer' => 2, 'over_dimension' => 1],
            'CAM-06' => ['name' => 'Jalan Mistar Cokrokusumo', 'normal' => 10, 'kontainer' => 2, 'over_dimension' => 3],
        ];

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

            $hourSeq = [];
            if ($hour === 15) {
                $hourSeq = [
                    'Over Dimension', 'Normal', 'Kontainer', 'Over Dimension', 'Normal',
                    'Kontainer', 'Normal', 'Over Dimension', 'Normal', 'Kontainer',
                    'Normal', 'Kontainer', 'Normal', 'Normal', 'Normal',
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
                    'date_formatted'   => '25 Sep 2026',
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

        usort($records, static function ($a, $b) {
            return strcmp($b['timestamp_raw'], $a['timestamp_raw']);
        });

        foreach ($records as $idx => &$rec) {
            $rec['id'] = sprintf('DET-20260925-%03d', $idx + 1);
        }
        unset($rec);

        return $records;
    }

    /**
     * Membangun record satu hari historis secara deterministik
     */
    private function buildHistoricalDayRecords(string $date, string $cleanDate, string $dateFormatted, array $dayInfo): array
    {
        $normalCount = $dayInfo['normal'];
        $kontainerCount = $dayInfo['kontainer'];
        $overDimCount = $dayInfo['over_dimension'];
        $total = $normalCount + $kontainerCount + $overDimCount;

        // Distribusikan kategori berselang-seling
        $rem = [
            'Over Dimension' => $overDimCount,
            'Kontainer'      => $kontainerCount,
            'Normal'         => $normalCount,
        ];
        $items = [];
        while (count($items) < $total) {
            if ($rem['Over Dimension'] > 0) {
                $items[] = 'Over Dimension';
                $rem['Over Dimension']--;
            }
            if ($rem['Kontainer'] > 0) {
                $items[] = 'Kontainer';
                $rem['Kontainer']--;
            }
            if ($rem['Normal'] > 0) {
                $items[] = 'Normal';
                $rem['Normal']--;
            }
            if ($rem['Normal'] > 0) {
                $items[] = 'Normal';
                $rem['Normal']--;
            }
        }

        $dayNum = (int)substr($cleanDate, 6, 2);
        $records = [];

        // Pool jam aktif: kurva distribusi alami lalu lintas kendaraan niaga
        $hourWeights = [
            5 => 2, 6 => 4, 7 => 6, 8 => 7, 9 => 8, 10 => 9,
            11 => 10, 12 => 11, 13 => 10, 14 => 15, 15 => 12,
            16 => 8, 17 => 6, 18 => 4, 19 => 3, 20 => 2, 21 => 1
        ];
        $hourList = [];
        foreach ($hourWeights as $h => $w) {
            for ($k = 0; $k < $w; $k++) {
                $hourList[] = $h;
            }
        }

        // Pool CCTV berbobot untuk Over Dimension: CAM-02 (Simpang Trikora) berbobot ~40%
        $cctvPoolOD = [
            ['code' => 'CAM-02', 'name' => 'Simpang Trikora'],
            ['code' => 'CAM-01', 'name' => 'Simpang A Yani'],
            ['code' => 'CAM-02', 'name' => 'Simpang Trikora'],
            ['code' => 'CAM-06', 'name' => 'Jalan Mistar Cokrokusumo'],
            ['code' => 'CAM-04', 'name' => 'Gerbang Kota'],
            ['code' => 'CAM-02', 'name' => 'Simpang Trikora'],
            ['code' => 'CAM-05', 'name' => 'Simpang Guntung'],
            ['code' => 'CAM-01', 'name' => 'Simpang A Yani'],
            ['code' => 'CAM-02', 'name' => 'Simpang Trikora'],
            ['code' => 'CAM-06', 'name' => 'Jalan Mistar Cokrokusumo'],
        ];

        for ($idx = 0; $idx < $total; $idx++) {
            $cat = $items[$idx];

            if ($cat === 'Over Dimension') {
                $cam = $cctvPoolOD[($idx + $dayNum) % count($cctvPoolOD)];
                $vType = ($idx % 4 === 0) ? 'Pickup' : 'Truk';
                // Jam OD terkonsentrasi di siang/sore (puncaknya 14:00 WITA)
                $hPool = [11, 12, 13, 14, 14, 14, 15, 15, 16, 10, 9, 8, 7, 6, 17];
                $hour = $hPool[($idx + $dayNum) % count($hPool)];
            } elseif ($cat === 'Kontainer') {
                $cIdx = ($idx * 2 + $dayNum) % count($this->cctvPool);
                $cam = $this->cctvPool[$cIdx];
                $vType = 'Truk Kontainer';
                $hour = $hourList[($idx * 3 + $dayNum * 5) % count($hourList)];
            } else {
                $cIdx = ($idx + $dayNum) % count($this->cctvPool);
                $cam = $this->cctvPool[$cIdx];
                $vType = ($idx % 3 === 0) ? 'Pickup' : 'Truk';
                $hour = $hourList[($idx * 7 + $dayNum * 11) % count($hourList)];
            }

            $minute = ($idx * 13 + $dayNum * 7) % 60;
            $second = ($idx * 19 + $dayNum * 13) % 60;
            $timeStr = sprintf('%02d:%02d WITA', $hour, $minute);

            $records[] = [
                'id'               => '',
                'date'             => $date,
                'date_formatted'   => $dateFormatted,
                'time'             => $timeStr,
                'timestamp_raw'    => sprintf('%s %02d:%02d:%02d', $date, $hour, $minute, $second),
                'hour'             => $hour,
                'cctv_code'        => $cam['code'],
                'cctv_name'        => $cam['name'],
                'vehicle_type'     => $vType,
                'detection_result' => $cat,
            ];
        }

        // Urutkan menurun berdasarkan waktu dalam hari tersebut
        usort($records, static function ($a, $b) {
            return strcmp($b['timestamp_raw'], $a['timestamp_raw']);
        });

        foreach ($records as $i => &$r) {
            $r['id'] = sprintf('DET-%s-%03d', $cleanDate, $i + 1);
        }
        unset($r);

        return $records;
    }

    /**
     * Dapatkan record deteksi hari ini (kompatibilitas backward)
     */
    public function getTodayDetections(array $filters = []): array
    {
        $records = $this->getDetectionsForPeriod(1);

        if (!empty($filters['cctv']) && $filters['cctv'] !== 'all') {
            $cctvCode = strtoupper(trim((string)$filters['cctv']));
            $records = array_values(array_filter($records, static fn($r) => $r['cctv_code'] === $cctvCode));
        }

        if (!empty($filters['vehicle']) && $filters['vehicle'] !== 'all') {
            $vType = trim((string)$filters['vehicle']);
            $records = array_values(array_filter($records, static fn($r) => strcasecmp($r['vehicle_type'], $vType) === 0));
        }

        if (!empty($filters['result']) && $filters['result'] !== 'all') {
            $res = trim((string)$filters['result']);
            $records = array_values(array_filter($records, static fn($r) => strcasecmp($r['detection_result'], $res) === 0));
        }

        return $records;
    }

    /**
     * Dapatkan record deteksi hari ini dengan sistem pagination (kompatibilitas backward)
     */
    public function getTodayDetectionsPaginated(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $allFiltered = $this->getTodayDetections($filters);
        $total = count($allFiltered);
        $totalPages = max(1, (int)ceil($total / $perPage));

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
     * Dapatkan N record terbaru hari ini (kompatibilitas backward)
     */
    public function getLatestDetections(int $limit = 5): array
    {
        $all = $this->getTodayDetections();
        return array_slice($all, 0, $limit);
    }

    /**
     * Ringkasan & insight historis 7 hari (kompatibilitas backward)
     */
    public function getWeeklyHistoricalSummary(): array
    {
        $dashboardData = $this->getDashboardData(7);
        $dailyData = [
            ['date' => '2026-09-19', 'label' => '19 Sep', 'day' => 'Sabtu',  'normal' => 70, 'kontainer' => 23, 'over_dimension' => 12, 'total' => 105],
            ['date' => '2026-09-20', 'label' => '20 Sep', 'day' => 'Minggu', 'normal' => 74, 'kontainer' => 25, 'over_dimension' => 15, 'total' => 114],
            ['date' => '2026-09-21', 'label' => '21 Sep', 'day' => 'Senin',  'normal' => 66, 'kontainer' => 21, 'over_dimension' => 9,  'total' => 96],
            ['date' => '2026-09-22', 'label' => '22 Sep', 'day' => 'Selasa', 'normal' => 90, 'kontainer' => 28, 'over_dimension' => 24, 'total' => 142],
            ['date' => '2026-09-23', 'label' => '23 Sep', 'day' => 'Rabu',   'normal' => 81, 'kontainer' => 24, 'over_dimension' => 16, 'total' => 121],
            ['date' => '2026-09-24', 'label' => '24 Sep', 'day' => 'Kamis',  'normal' => 94, 'kontainer' => 27, 'over_dimension' => 21, 'total' => 142],
            ['date' => '2026-09-25', 'label' => '25 Sep', 'day' => 'Jumat',  'normal' => 83, 'kontainer' => 27, 'over_dimension' => 18, 'total' => 128],
        ];

        return [
            'period_label'   => '19 - 25 September 2026',
            'period_days'    => 7,
            'totals'         => [
                'grand_total'    => $dashboardData['stats']['total_detection'],
                'normal'         => $dashboardData['stats']['normal'],
                'kontainer'      => $dashboardData['stats']['kontainer'],
                'over_dimension' => $dashboardData['stats']['over_dimension'],
                'cctv_active_str'=> $dashboardData['stats']['cctv_status_str'],
            ],
            'percentages'    => $dashboardData['stats']['percentages'],
            'daily_breakdown'=> $dailyData,
            'od_trend'       => [
                'labels' => array_column($dailyData, 'label'),
                'values' => array_column($dailyData, 'over_dimension'),
            ],
            'insights'       => $dashboardData['insights'],
        ];
    }

    /**
     * Format tanggal Indonesia singkat (misal: "25 Sep 2026")
     */
    private function formatDateIndo(string $dateStr): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        $ts = strtotime($dateStr);
        $d = date('j', $ts);
        $m = (int)date('n', $ts);
        $y = date('Y', $ts);
        return "{$d} {$months[$m]} {$y}";
    }

    /**
     * Format label tanggal pendek untuk grafik harian (misal: "25 Sep")
     */
    private function formatShortDateLabel(string $dateStr): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        $ts = strtotime($dateStr);
        $d = date('j', $ts);
        $m = (int)date('n', $ts);
        return "{$d} {$months[$m]}";
    }

    /**
     * Dapatkan nama hari dalam bahasa Indonesia
     */
    private function getDayNameIndo(string $dateStr): string
    {
        $days = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        $dayEng = date('l', strtotime($dateStr));
        return $days[$dayEng] ?? $dayEng;
    }
}
