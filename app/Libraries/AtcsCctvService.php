<?php

namespace App\Libraries;

/**
 * AtcsCctvService
 * 
 * Pengelola konfigurasi terpusat untuk 6 kamera CCTV publik ATCS Dishub Kota Banjarmasin.
 * Digunakan sebagai sumber tayangan video nyata dan cuplikan kamera pada tahap pengembangan,
 * terpisah dari dataset deteksi dummy analitik Banjarbaru.
 */
class AtcsCctvService
{
    /**
     * Provider identitas sumber streaming
     */
    public const PROVIDER = 'atcs_banjarmasin';
    public const UPSTREAM_HOST = 'https://atcs.banjarmasinkota.go.id';

    /**
     * Daftar 6 kamera resmi ATCS Banjarmasin
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $cameras = [
        'jembatan_alalak_arah_luar_kota' => [
            'id'             => 'atcs-01',
            'code'           => 'ATCS-01',
            'source_code'    => 'jembatan_alalak_arah_luar_kota',
            'name'           => 'Jembatan Alalak Arah Luar Kota',
            'short_name'     => 'Jembatan Alalak',
            'category'       => 'Ruas Jalan',
            'location'       => 'Jembatan Alalak, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.277988,
            'longitude'      => 114.589547,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan arus lalu lintas dan kendaraan barang pada Jembatan Alalak arah luar kota.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        ],
        'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_keluar_kota' => [
            'id'             => 'atcs-02',
            'code'           => 'ATCS-02',
            'source_code'    => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_keluar_kota',
            'name'           => 'Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal)',
            'short_name'     => 'Jembatan Tatah Bangkal',
            'category'       => 'Ruas Jalan',
            'location'       => 'Jl. Gubernur Soebardjo, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.372661018410213,
            'longitude'      => 114.58754469407165,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Jalur lingkar selatan angkutan logistik dan peti kemas Jembatan Tatah Bangkal keluar kota.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        ],
        'u_turn_jalan_a_yani_km_5' => [
            'id'             => 'atcs-03',
            'code'           => 'ATCS-03',
            'source_code'    => 'u_turn_jalan_a_yani_km_5',
            'name'           => 'U-Turn Jalan A Yani KM 5',
            'short_name'     => 'U-Turn KM 5',
            'category'       => 'Ruas Jalan',
            'location'       => 'Jl. Ahmad Yani KM 5, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.347506,
            'longitude'      => 114.624710,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan putaran balik arteri utama Jalan A. Yani KM 5.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        ],
        'simpang_a_yani_pramuka_km_6' => [
            'id'             => 'atcs-04',
            'code'           => 'ATCS-04',
            'source_code'    => 'simpang_a_yani_pramuka_km_6',
            'name'           => 'Simpang A Yani - Pramuka KM 6',
            'short_name'     => 'Simpang Pramuka KM 6',
            'category'       => 'Simpang',
            'location'       => 'Simpang KM 6 (Jl. A. Yani - Jl. Pramuka), Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.348813,
            'longitude'      => 114.625723,
            'status'         => 'online',
            'resolution'     => '640x360 (SD)',
            'description'    => 'Titik temu koridor angkutan antar kota dan terminal Simpang Pramuka KM 6.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        ],
        'simpang_gerilya' => [
            'id'             => 'atcs-05',
            'code'           => 'ATCS-05',
            'source_code'    => 'simpang_gerilya',
            'name'           => 'Simpang Gerilya',
            'short_name'     => 'Simpang Gerilya',
            'category'       => 'Simpang',
            'location'       => 'Simpang Jl. Gerilya, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.350410,
            'longitude'      => 114.596541,
            'status'         => 'online',
            'resolution'     => '640x360 (SD)',
            'description'    => 'Persimpangan strategis perlintasan angkutan barang dan kendaraan niaga kawasan Gerilya.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        ],
        'simpang_a_yani_gatot_fly_over_km_3' => [
            'id'             => 'atcs-06',
            'code'           => 'ATCS-06',
            'source_code'    => 'simpang_a_yani_gatot_fly_over_km_3',
            'name'           => 'Simpang A Yani Gatot / Fly Over KM 3',
            'short_name'     => 'Fly Over KM 3',
            'category'       => 'Simpang',
            'location'       => 'Fly Over KM 3 (Jl. A. Yani - Jl. Gatot Subroto), Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.331971,
            'longitude'      => 114.613616,
            'status'         => 'online',
            'resolution'     => '640x360 (SD)',
            'description'    => 'Pemantauan lalu lintas Fly Over KM 3 dan simpang Gatot Subroto.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        ],
    ];

    /**
     * Kamera Khusus Pengujian AI Model Integrasi (1 Kamera ATCS Banjarmasin)
     * Tidak menambah jumlah kamera Banjarbaru (tetap 18 titik / 17 terpasang).
     */
    protected array $aiTestCamera = [
        'id'             => 'atcs-ai-test',
        'code'           => 'ATCS-TEST',
        'source_code'    => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
        'name'           => 'Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Masuk Kota',
        'short_name'     => 'Tatah Bangkal Masuk Kota',
        'category'       => 'Pengujian AI (Model Teman)',
        'location'       => 'Jl. Gubernur Soebardjo, Tatah Bangkal, Banjarmasin',
        'city'           => 'Kota Banjarmasin',
        'latitude'       => -3.372661,
        'longitude'      => 114.587545,
        'status'         => 'online',
        'resolution'     => '1280x720 (HD)',
        'description'    => 'Kamera uji coba integrasi model YOLOv8m-cls (5 kelas) untuk deteksi kendaraan niaga dan over dimension.',
        'attribution'    => 'ATCS Dishub Kota Banjarmasin & Tim Peneliti',
        'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        'is_ai_test'     => true,
    ];

    /**
     * Mengembalikan kamera uji coba integrasi model AI
     */
    public function getAiTestCamera(): array
    {
        return $this->enrichCameraData($this->aiTestCamera);
    }

    /**
     * Mengembalikan seluruh daftar kamera dengan URL stream dan snapshot yang sudah dipetakan
     * 
     * @return list<array<string, mixed>>
     */
    public function getCctvList(): array
    {
        $list = [];
        foreach ($this->cameras as $camera) {
            $list[] = $this->enrichCameraData($camera);
        }
        return $list;
    }

    /**
     * Cari kamera berdasarkan kode (misal ATCS-01, ATCS-TEST), source_code, atau ID
     */
    public function getCctvByCode(string $code): ?array
    {
        $normalized = strtolower(trim($code));
        if ($normalized === '') {
            return null;
        }

        // Cek kamera uji AI terlebih dahulu
        if (
            strtolower($this->aiTestCamera['code']) === $normalized ||
            strtolower($this->aiTestCamera['id']) === $normalized ||
            strtolower($this->aiTestCamera['source_code']) === $normalized
        ) {
            return $this->enrichCameraData($this->aiTestCamera);
        }

        foreach ($this->cameras as $sourceCode => $camera) {
            if (
                strtolower($camera['code']) === $normalized ||
                strtolower($camera['id']) === $normalized ||
                strtolower($sourceCode) === $normalized
            ) {
                return $this->enrichCameraData($camera);
            }
        }

        return null;
    }

    /**
     * Periksa apakah source_code terdaftar secara sah pada allowlist
     */
    public function isValidSourceCode(string $sourceCode): bool
    {
        $code = trim($sourceCode);
        return array_key_exists($code, $this->cameras) || ($code === $this->aiTestCamera['source_code']);
    }

    /**
     * Dapatkan daftar source_code yang diizinkan (allowlist)
     * 
     * @return list<string>
     */
    public function getAllowedSourceCodes(): array
    {
        return array_keys($this->cameras);
    }

    /**
     * Memperkaya data kamera dengan URL proxy lokal dan upstream
     */
    protected function enrichCameraData(array $camera): array
    {
        $sourceCode = $camera['source_code'];

        $camera['provider']           = self::PROVIDER;
        $camera['upstream_stream']    = self::UPSTREAM_HOST . "/stream/{$sourceCode}/index.m3u8";
        $camera['upstream_snapshot']  = self::UPSTREAM_HOST . "/screenshot/{$sourceCode}.webp";
        
        // Stream dilayani melalui HLS Proxy multi-threaded (port 8088) dengan CORS bersih.
        // Mencegah bottleneck I/O pada server PHP CodeIgniter dan menghindari penolakan CORS browser dari upstream ATCS.
        $proxyHost                    = 'http://localhost:8088';
        $camera['stream_url']         = "{$proxyHost}/stream/{$sourceCode}/index.m3u8";
        $camera['proxy_stream']       = "{$proxyHost}/stream/{$sourceCode}/index.m3u8";
        $camera['snapshot_url']       = $camera['upstream_snapshot'];
        $camera['proxy_snapshot']     = $camera['upstream_snapshot'];
        
        // Kompatibilitas dengan tampilan partial cctv_card (image_thumb)
        $camera['image_thumb']        = $camera['upstream_snapshot'];
        $camera['is_live_capable']    = true;

        return $camera;
    }
}
