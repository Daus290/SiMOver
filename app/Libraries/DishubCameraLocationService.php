<?php

namespace App\Libraries;

/**
 * DishubCameraLocationService
 * 
 * Layanan metadata terpusat untuk titik kamera CCTV Dinas Perhubungan Kota Banjarbaru.
 * Menyediakan data 4 persimpangan (Simpang Tiga Brimob, Simpang Tiga RO Ulin,
 * Simpang Empat Samsat, Simpang Empat Adipura) beserta 18 kamera (14 Fase/Fixed & 4 PTZ).
 * 
 * Digunakan secara konsisten oleh:
 * - Halaman Peta Geografis (/peta-cctv)
 * - Modal Detail Simpang pada Peta
 * - Halaman Live CCTV (/live-cctv)
 * - Komponen Kartu CCTV (cctv_card)
 */
class DishubCameraLocationService
{
    public const SOURCE = 'dishub_banjarbaru';

    protected ?VivotekCctvService $vivotekService = null;

    protected function getVivotekService(): VivotekCctvService
    {
        if ($this->vivotekService === null) {
            $this->vivotekService = new VivotekCctvService();
        }
        return $this->vivotekService;
    }

    /**
     * Data 4 Persimpangan Utama
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $intersections = [
        's3-brimob' => [
            'id'          => 's3-brimob',
            'name'        => 'Simpang Tiga Brimob',
            'short_name'  => 'Simpang Brimob',
            'type'        => 'Simpang Tiga',
            'center_lat'  => -3.4483265496487,
            'center_lng'  => 114.80369310335236,
            'description' => 'Persimpangan Jl. A. Yani - Jl. Bina Murni.',
        ],
        's3-ro-ulin' => [
            'id'          => 's3-ro-ulin',
            'name'        => 'Simpang Tiga RO Ulin',
            'short_name'  => 'Simpang RO Ulin',
            'type'        => 'Simpang Tiga',
            'center_lat'  => -3.444379458302601,
            'center_lng'  => 114.81647711799954,
            'description' => 'Persimpangan Jl. A. Yani - Jl. RO Ulin.',
        ],
        's4-samsat' => [
            'id'          => 's4-samsat',
            'name'        => 'Simpang Empat Samsat',
            'short_name'  => 'Simpang Samsat',
            'type'        => 'Simpang Empat',
            'center_lat'  => -3.4391355306537217,
            'center_lng'  => 114.83257997238427,
            'description' => 'Persimpangan Jl. A. Yani - Jl. Pangeran Suriansyah / Samsat Kota Banjarbaru.',
        ],
        's4-adipura' => [
            'id'          => 's4-adipura',
            'name'        => 'Simpang Empat Adipura',
            'short_name'  => 'Simpang Adipura',
            'type'        => 'Simpang Empat',
            'center_lat'  => -3.442932051512537,
            'center_lng'  => 114.83262907891645,
            'description' => 'Persimpangan Jl. A. Yani - Tugu Adipura / Taman Kota Banjarbaru.',
        ],
    ];

    /**
     * Data 18 Kamera CCTV Banjarbaru
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $cameras = [
        // =====================================================================
        // A. SIMPANG TIGA BRIMOB (4 Kamera Terpasang)
        // =====================================================================
        'BJB-BRIMOB-F1' => [
            'code'                => 'BJB-BRIMOB-F1',
            'location_id'         => 's3-brimob',
            'name'                => 'Brimob Fase 1',
            'full_name'           => 'Simpang Tiga Brimob - Fase 1',
            'type'                => 'fixed',
            'phase'               => 1,
            'direction'           => 'Masuk Kota / Barat Daya',
            'latitude'            => -3.4483252374969102,
            'longitude'           => 114.80360844268945,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 1.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-BRIMOB-F2' => [
            'code'                => 'BJB-BRIMOB-F2',
            'location_id'         => 's3-brimob',
            'name'                => 'Brimob Fase 2',
            'full_name'           => 'Simpang Tiga Brimob - Fase 2',
            'type'                => 'fixed',
            'phase'               => 2,
            'direction'           => 'Timur Laut',
            'latitude'            => -3.4483110747143617,
            'longitude'           => 114.80379785367452,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 2.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-BRIMOB-F3' => [
            'code'                => 'BJB-BRIMOB-F3',
            'location_id'         => 's3-brimob',
            'name'                => 'Brimob Fase 3',
            'full_name'           => 'Simpang Tiga Brimob - Fase 3',
            'type'                => 'fixed',
            'phase'               => 3,
            'direction'           => 'Tenggara',
            'latitude'            => -3.4484170924696547,
            'longitude'           => 114.80370220321825,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 3.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-BRIMOB-PTZ' => [
            'code'                => 'BJB-BRIMOB-PTZ',
            'location_id'         => 's3-brimob',
            'name'                => 'Brimob PTZ',
            'full_name'           => 'Simpang Tiga Brimob - PTZ',
            'type'                => 'ptz',
            'phase'               => null,
            'direction'           => 'Keluar Kota / 360°',
            'latitude'            => -3.4484028936640803,
            'longitude'           => 114.80362728719835,
            'coordinate_accuracy' => 'estimated',
            'coordinate_note'     => 'Koordinat perkiraan kamera PTZ (Keluar Kota / 360°).',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],

        // =====================================================================
        // B. SIMPANG TIGA RO ULIN (3 Kamera Terpasang)
        // =====================================================================
        'BJB-ROULIN-F1' => [
            'code'                => 'BJB-ROULIN-F1',
            'location_id'         => 's3-ro-ulin',
            'name'                => 'RO Ulin Fase 1',
            'full_name'           => 'Simpang Tiga RO Ulin - Fase 1',
            'type'                => 'fixed',
            'phase'               => 1,
            'direction'           => 'Barat',
            'latitude'            => -3.444347775994965,
            'longitude'           => 114.81636899530143,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 1.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-ROULIN-F2' => [
            'code'                => 'BJB-ROULIN-F2',
            'location_id'         => 's3-ro-ulin',
            'name'                => 'RO Ulin Fase 2',
            'full_name'           => 'Simpang Tiga RO Ulin - Fase 2',
            'type'                => 'fixed',
            'phase'               => 2,
            'direction'           => 'Timur Laut',
            'latitude'            => -3.4443916780150095,
            'longitude'           => 114.81658551944658,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 2.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-ROULIN-F3' => [
            'code'                => 'BJB-ROULIN-F3',
            'location_id'         => 's3-ro-ulin',
            'name'                => 'RO Ulin Fase 3',
            'full_name'           => 'Simpang Tiga RO Ulin - Fase 3',
            'type'                => 'fixed',
            'phase'               => 3,
            'direction'           => 'Selatan',
            'latitude'            => -3.44445,
            'longitude'           => 114.81648,
            'coordinate_accuracy' => 'estimated',
            'coordinate_note'     => 'Titik rencana kamera Fase 3 belum terpasang di lapangan.',
            'availability'        => 'unavailable',
            'status'              => 'unavailable',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-ROULIN-PTZ' => [
            'code'                => 'BJB-ROULIN-PTZ',
            'location_id'         => 's3-ro-ulin',
            'name'                => 'RO Ulin PTZ',
            'full_name'           => 'Simpang Tiga RO Ulin - PTZ',
            'type'                => 'ptz',
            'phase'               => null,
            'direction'           => '360°',
            'latitude'            => -3.444347775994965, // Posisi jangkar Fase 1
            'longitude'           => 114.81636899530143,
            'coordinate_accuracy' => 'estimated',
            'coordinate_note'     => 'Koordinat jangkar perkiraan berada di sebelah kamera Fase 1 (360°).',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 8.0,
            'offset_bearing_deg'  => 200.0, // Southwest offset agar terpisah dari F1
        ],

        // =====================================================================
        // C. SIMPANG EMPAT SAMSAT (5 Kamera Terpasang, F1 Offline)
        // =====================================================================
        'BJB-SAMSAT-F1' => [
            'code'                => 'BJB-SAMSAT-F1',
            'location_id'         => 's4-samsat',
            'name'                => 'Samsat Fase 1',
            'full_name'           => 'Simpang Empat Samsat - Fase 1',
            'type'                => 'fixed',
            'phase'               => 1,
            'direction'           => 'Timur',
            'latitude'            => -3.4391781832028343,
            'longitude'           => 114.83282955393449,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 1.',
            'availability'        => 'installed',
            'status'              => 'offline', // Perangkat mengalami kerusakan
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-SAMSAT-F2' => [
            'code'                => 'BJB-SAMSAT-F2',
            'location_id'         => 's4-samsat',
            'name'                => 'Samsat Fase 2',
            'full_name'           => 'Simpang Empat Samsat - Fase 2',
            'type'                => 'fixed',
            'phase'               => 2,
            'direction'           => 'Selatan',
            'latitude'            => -3.4392119456606722,
            'longitude'           => 114.83261771205859,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 2.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-SAMSAT-F3' => [
            'code'                => 'BJB-SAMSAT-F3',
            'location_id'         => 's4-samsat',
            'name'                => 'Samsat Fase 3',
            'full_name'           => 'Simpang Empat Samsat - Fase 3',
            'type'                => 'fixed',
            'phase'               => 3,
            'direction'           => 'Barat',
            'latitude'            => -3.439094606510781,
            'longitude'           => 114.83260216546262,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 3.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-SAMSAT-F4' => [
            'code'                => 'BJB-SAMSAT-F4',
            'location_id'         => 's4-samsat',
            'name'                => 'Samsat Fase 4',
            'full_name'           => 'Simpang Empat Samsat - Fase 4',
            'type'                => 'fixed',
            'phase'               => 4,
            'direction'           => 'Utara',
            'latitude'            => -3.4390237797084073,
            'longitude'           => 114.83266419173236,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 4.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-SAMSAT-PTZ' => [
            'code'                => 'BJB-SAMSAT-PTZ',
            'location_id'         => 's4-samsat',
            'name'                => 'Samsat PTZ',
            'full_name'           => 'Simpang Empat Samsat - PTZ',
            'type'                => 'ptz',
            'phase'               => null,
            'direction'           => '360°',
            'latitude'            => -3.439094606510781, // Posisi jangkar Fase 3
            'longitude'           => 114.83260216546262,
            'coordinate_accuracy' => 'estimated',
            'coordinate_note'     => 'Koordinat jangkar perkiraan berada di sebelah kamera Fase 3 (360°).',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 8.0,
            'offset_bearing_deg'  => 240.0, // Southwest offset agar terpisah dari F3
        ],

        // =====================================================================
        // D. SIMPANG EMPAT ADIPURA (5 Kamera Terpasang)
        // =====================================================================
        'BJB-ADIPURA-F1' => [
            'code'                => 'BJB-ADIPURA-F1',
            'location_id'         => 's4-adipura',
            'name'                => 'Adipura Fase 1',
            'full_name'           => 'Simpang Empat Adipura - Fase 1',
            'type'                => 'fixed',
            'phase'               => 1,
            'direction'           => 'Barat',
            'latitude'            => -3.442883262585134,
            'longitude'           => 114.83251247821913,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 1.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-ADIPURA-F2' => [
            'code'                => 'BJB-ADIPURA-F2',
            'location_id'         => 's4-adipura',
            'name'                => 'Adipura Fase 2',
            'full_name'           => 'Simpang Empat Adipura - Fase 2',
            'type'                => 'fixed',
            'phase'               => 2,
            'direction'           => 'Utara',
            'latitude'            => -3.442837747327948,
            'longitude'           => 114.83269084511072,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 2.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-ADIPURA-F3' => [
            'code'                => 'BJB-ADIPURA-F3',
            'location_id'         => 's4-adipura',
            'name'                => 'Adipura Fase 3',
            'full_name'           => 'Simpang Empat Adipura - Fase 3',
            'type'                => 'fixed',
            'phase'               => 3,
            'direction'           => 'Timur',
            'latitude'            => -3.4429983894024083,
            'longitude'           => 114.83274180707973,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 3.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-ADIPURA-F4' => [
            'code'                => 'BJB-ADIPURA-F4',
            'location_id'         => 's4-adipura',
            'name'                => 'Adipura Fase 4',
            'full_name'           => 'Simpang Empat Adipura - Fase 4',
            'type'                => 'fixed',
            'phase'               => 4,
            'direction'           => 'Selatan',
            'latitude'            => -3.443062483905185,
            'longitude'           => 114.8325277012293,
            'coordinate_accuracy' => 'exact',
            'coordinate_note'     => 'Koordinat terukur presisi titik kamera Fase 4.',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 0.0,
            'offset_bearing_deg'  => 0.0,
        ],
        'BJB-ADIPURA-PTZ' => [
            'code'                => 'BJB-ADIPURA-PTZ',
            'location_id'         => 's4-adipura',
            'name'                => 'Adipura PTZ',
            'full_name'           => 'Simpang Empat Adipura - PTZ',
            'type'                => 'ptz',
            'phase'               => null,
            'direction'           => '360°',
            'latitude'            => -3.442837747327948, // Posisi jangkar Fase 2
            'longitude'           => 114.83269084511072,
            'coordinate_accuracy' => 'estimated',
            'coordinate_note'     => 'Koordinat jangkar perkiraan berada di sebelah kamera Fase 2 (360°).',
            'availability'        => 'installed',
            'status'              => 'online',
            'status_is_placeholder' => true,
            'offset_distance_m'   => 8.0,
            'offset_bearing_deg'  => 330.0, // Northwest offset agar terpisah dari F2
        ],
    ];

    /**
     * Hitung koordinat geodesik offset menggunakan formula Haversine/Spherical Earth
     */
    protected function calculateGeodesicOffset(float $lat, float $lng, float $distanceMeters, float $bearingDeg): array
    {
        if ($distanceMeters <= 0.0) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        $r = 6378137.0; // Radius Bumi WGS-84 dalam meter
        $d = $distanceMeters / $r;
        $theta = deg2rad($bearingDeg);
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);

        $targetLatRad = asin(sin($latRad) * cos($d) + cos($latRad) * sin($d) * cos($theta));
        $targetLngRad = $lngRad + atan2(sin($theta) * sin($d) * cos($latRad), cos($d) - sin($latRad) * sin($targetLatRad));

        return [
            'lat' => rad2deg($targetLatRad),
            'lng' => rad2deg($targetLngRad),
        ];
    }

    /**
     * Memperkaya metadata kamera dengan informasi persimpangan dan koordinat tampilan
     * 
     * @param array<string, mixed> $camera
     * @return array<string, mixed>
     */
    protected function enrichCamera(array $camera): array
    {
        $locId = $camera['location_id'];
        $intersection = $this->intersections[$locId] ?? [
            'name'       => 'Persimpangan Banjarbaru',
            'short_name' => 'Simpang',
            'type'       => 'Simpang',
        ];

        $camera['source']            = self::SOURCE;
        $camera['source_label']      = 'Dishub Kota Banjarbaru';
        $camera['intersection_name'] = $intersection['name'];
        $camera['intersection_short']= $intersection['short_name'];
        $camera['category']          = $intersection['type'];
        $camera['location']          = $intersection['name'] . ', Kota Banjarbaru';
        $camera['city']              = 'Kota Banjarbaru';
        $camera['is_installed']      = ($camera['availability'] === 'installed');
        $camera['is_placeholder']    = true;
        $camera['stream_url']        = null; // Belum diintegrasikan ke Vivotek
        $camera['snapshot_url']      = null;
        $camera['image_thumb']       = null;

        // Hitung koordinat tampilan (display_lat, display_lng) untuk marker peta
        if ($camera['offset_distance_m'] > 0.0) {
            $offset = $this->calculateGeodesicOffset(
                (float)$camera['latitude'],
                (float)$camera['longitude'],
                (float)$camera['offset_distance_m'],
                (float)$camera['offset_bearing_deg']
            );
            $camera['display_latitude']  = $offset['lat'];
            $camera['display_longitude'] = $offset['lng'];
        } else {
            $camera['display_latitude']  = $camera['latitude'];
            $camera['display_longitude'] = $camera['longitude'];
        }

        // Teks badge status UI sementara
        switch ($camera['status']) {
            case 'online':
                $camera['status_label'] = 'Online (Sementara)';
                $camera['badge_class']  = 'status-online';
                break;
            case 'offline':
                $camera['status_label'] = 'Offline (Sementara)';
                $camera['badge_class']  = 'status-offline';
                break;
            default:
                $camera['status_label'] = 'Belum tersedia';
                $camera['badge_class']  = 'status-unavailable';
                break;
        }

        return $camera;
    }

    /**
     * Dapatkan daftar seluruh 4 persimpangan beserta kamera yang dikelompokkan
     * 
     * @return list<array<string, mixed>>
     */
    public function getIntersections(): array
    {
        $allCameras = $this->getAllCameras();
        $list = [];

        foreach ($this->intersections as $id => $item) {
            $locCameras = array_values(array_filter($allCameras, fn($c) => $c['location_id'] === $id));
            $installedCount = count(array_filter($locCameras, fn($c) => $c['is_installed']));
            
            $item['cameras']         = $locCameras;
            $item['total_cameras']   = count($locCameras);
            $item['installed_count'] = $installedCount;
            $item['has_unavailable'] = ($installedCount < count($locCameras));
            $item['label_marker']    = $item['name'] . ' · ' . $installedCount . ' kamera';

            $list[] = $item;
        }

        return $list;
    }

    /**
     * Dapatkan satu data persimpangan berdasarkan location_id
     */
    public function getIntersection(string $locationId): ?array
    {
        $normalized = strtolower(trim($locationId));
        $intersections = $this->getIntersections();
        foreach ($intersections as $item) {
            if (strtolower($item['id']) === $normalized) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Dapatkan seluruh 18 kamera CCTV Banjarbaru
     * 
     * @return list<array<string, mixed>>
     */
    public function getAllCameras(): array
    {
        $list = [];
        foreach ($this->cameras as $cam) {
            $list[] = $this->enrichCamera($cam);
        }
        return $list;
    }

    /**
     * Cari kamera berdasarkan kode unik (misal BJB-SAMSAT-F2)
     */
    public function getCameraByCode(string $code): ?array
    {
        $normalized = strtoupper(trim($code));
        if ($normalized === '' || !isset($this->cameras[$normalized])) {
            return null;
        }
        return $this->enrichCamera($this->cameras[$normalized]);
    }

    /**
     * Dapatkan daftar seluruh kamera dengan overlay Live CCTV untuk 4 kamera Brimob
     * 
     * @return list<array<string, mixed>>
     */
    public function getCamerasForLiveCctv(): array
    {
        $cameras = $this->getAllCameras();
        foreach ($cameras as &$cam) {
            $cam = $this->applyLiveCctvOverlay($cam);
        }
        unset($cam);
        return $cameras;
    }

    /**
     * Cari kamera berdasarkan kode khusus konteks Live CCTV
     */
    public function getCameraForLiveCctv(string $code): ?array
    {
        $camera = $this->getCameraByCode($code);
        if ($camera === null) {
            return null;
        }
        return $this->applyLiveCctvOverlay($camera);
    }

    /**
     * Terapkan overlay integrasi Live CCTV untuk seluruh kamera Banjarbaru
     * Mempertahankan metadata global / peta tanpa mengubah integritas data dasar.
     */
    public function applyLiveCctvOverlay(array $camera): array
    {
        $code = $camera['code'] ?? '';

        // RO Ulin Fase 3: Entri Belum Tersedia di lapangan
        if ($code === 'BJB-ROULIN-F3' || empty($camera['is_installed'])) {
            $camera['source']          = 'dishub_banjarbaru';
            $camera['source_label']    = 'Dishub Kota Banjarbaru';
            $camera['source_note']     = 'Belum Tersedia di Lapangan';
            $camera['is_live_capable'] = false;
            $camera['is_placeholder']  = true;
            $camera['stream_url']      = null;
            $camera['proxy_stream']    = null;
            $camera['snapshot_url']    = null;
            $camera['proxy_snapshot']  = null;
            $camera['image_thumb']     = null;
            $camera['resolution']      = 'Belum Tersedia';
            $camera['status']          = 'unavailable';
            return $camera;
        }

        // Samsat Fase 1: Perangkat terpasang namun offline / rusak di lapangan (mandat user)
        if ($code === 'BJB-SAMSAT-F1') {
            $camera['source']          = 'vivotek_dishub';
            $camera['source_label']    = 'Vivotek • Dishub Kota Banjarbaru';
            $camera['source_note']     = 'Akses Jaringan Kantor';
            $camera['is_live_capable'] = false; // Rusak, jangan paksakan live
            $camera['is_placeholder']  = true;
            $camera['stream_url']      = null;
            $camera['proxy_stream']    = null;
            $camera['snapshot_url']    = base_url('cctv-snapshot/' . $code);
            $camera['proxy_snapshot']  = base_url('cctv-snapshot/' . $code);
            $camera['image_thumb']     = 'cctv-snapshot/' . $code;
            $camera['resolution']      = 'Tidak Aktif (Kerusakan Perangkat)';
            $camera['status']          = 'offline';
            return $camera;
        }

        $vivotek = $this->getVivotekService();
        $streamStatus = $vivotek->getStreamStatus($code);
        $isRelayActive = ($streamStatus['status'] === 'relay_active');

        // Cek snapshot file di cache
        $streamDir = $vivotek->getStreamDir($code);
        $snapFile = $streamDir . 'snapshot.jpg';
        $hasSnapFile = is_file($snapFile) && filesize($snapFile) > 1024;

        $camera['source']          = 'vivotek_dishub';
        $camera['source_label']    = 'Vivotek • Dishub Kota Banjarbaru';
        $camera['source_note']     = 'Akses Jaringan Kantor';
        $camera['snapshot_url']    = base_url('cctv-snapshot/' . $code);
        $camera['proxy_snapshot']  = base_url('cctv-snapshot/' . $code);
        $camera['image_thumb']     = 'cctv-snapshot/' . $code;

        if ($streamStatus['status'] === 'unconnected') {
            $camera['is_live_capable'] = false;
            $camera['is_placeholder']  = true;
            $camera['stream_url']      = null;
            $camera['proxy_stream']    = null;
            $camera['resolution']      = 'Belum Terhubung (Aliran RTSP Belum Terverifikasi)';
            $camera['status']          = 'unconnected';
            return $camera;
        }

        $vivotek = $this->getVivotekService();
        $streamStatus = $vivotek->getStreamStatus($code);
        $isRelayActive = ($streamStatus['status'] === 'relay_active');

        // Cek snapshot file di cache
        $streamDir = $vivotek->getStreamDir($code);
        $snapFile = $streamDir . 'snapshot.jpg';
        $hasSnapFile = is_file($snapFile) && filesize($snapFile) > 1024;

        $camera['source']          = 'vivotek_dishub';
        $camera['source_label']    = 'Vivotek • Dishub Kota Banjarbaru';
        $camera['source_note']     = 'Akses Jaringan Kantor';
        $camera['snapshot_url']    = base_url('cctv-snapshot/' . $code);
        $camera['proxy_snapshot']  = base_url('cctv-snapshot/' . $code);
        $camera['image_thumb']     = 'cctv-snapshot/' . $code;

        $resolutionMap = [
            'BJB-ADIPURA-PTZ' => 'HD 1080p',
            'BJB-ROULIN-PTZ'  => 'HD 1080p',
            'BJB-ROULIN-F2'   => 'HD 1080p',
            'BJB-ADIPURA-F4'  => '960p',
            'BJB-ADIPURA-F1'  => '480p',
            'BJB-BRIMOB-PTZ'  => '360p',
        ];
        $resolutionLabel = $resolutionMap[$code] ?? 'HD 720p';

        if ($isRelayActive) {
            $camera['is_live_capable'] = true;
            $camera['is_placeholder']  = false;
            $camera['stream_url']      = base_url('cctv-stream/' . $code . '/index.m3u8');
            $camera['proxy_stream']    = base_url('cctv-stream/' . $code . '/index.m3u8');
            $camera['resolution']      = $resolutionLabel;
            $camera['status']          = 'online';
        } else {
            $camera['is_live_capable'] = false;
            $camera['is_placeholder']  = !$hasSnapFile;
            $camera['stream_url']      = null;
            $camera['proxy_stream']    = null;
            $camera['resolution']      = $hasSnapFile ? 'Gambar Tersimpan' : 'Relay Belum Aktif';
            $camera['status']          = $hasSnapFile ? 'cached' : 'idle';
        }

        return $camera;
    }

    /**
     * Ringkasan statistik jumlah kamera untuk UI
     * 
     * @return array<string, int>
     */
    public function getSummaryCounts(): array
    {
        $all = $this->getAllCameras();
        $installed = array_filter($all, fn($c) => $c['is_installed']);
        $online = array_filter($installed, fn($c) => $c['status'] === 'online');
        $offline = array_filter($installed, fn($c) => $c['status'] === 'offline');
        $unavailable = array_filter($all, fn($c) => !$c['is_installed']);

        return [
            'total_entries'     => count($all),        // 18
            'installed'         => count($installed),  // 17
            'unavailable'       => count($unavailable),// 1 (RO Ulin F3)
            'online_temporary'  => count($online),     // 16
            'offline_temporary' => count($offline),    // 1 (Samsat F1)
            'intersections'     => count($this->intersections), // 4
        ];
    }
}
