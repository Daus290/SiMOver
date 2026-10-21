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
     * Daftar 2 kamera resmi ATCS Banjarmasin aktif untuk pemantauan Live CCTV & AI
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $cameras = [
        'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota' => [
            'id'             => 'atcs-01',
            'code'           => 'ATCS-01',
            'source_code'    => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
            'name'           => 'Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal)',
            'short_name'     => 'Jembatan Tatah Bangkal',
            'category'       => 'Ruas Jalan',
            'location'       => 'Jl. Gubernur Soebardjo, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.372661,
            'longitude'      => 114.587545,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Jalur lingkar angkutan logistik dan peti kemas Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal).',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => true,
        ],
        'simpang_sultan_adam_sungai_andai' => [
            'id'             => 'atcs-02',
            'code'           => 'ATCS-02',
            'source_code'    => 'simpang_sultan_adam_sungai_andai',
            'name'           => 'Simpang Sultan Adam - Sungai Andai',
            'short_name'     => 'Simpang Sultan Adam',
            'category'       => 'Simpang',
            'location'       => 'Simpang Jl. Sultan Adam - Jl. Sungai Andai, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.300762,
            'longitude'      => 114.606277,
            'status'         => 'online',
            'resolution'     => '640x360 (SD)',
            'description'    => 'Persimpangan strategis koridor Jl. Sultan Adam dan akses kawasan Sungai Andai.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'video1_stream.m3u8',
            'is_ai_active'   => true,
        ],
    ];

    /**
     * Kamera Khusus Pengujian AI Utama (kompatibilitas mundur)
     */
    protected array $aiTestCamera = [
        'id'             => 'atcs-01',
        'code'           => 'ATCS-01',
        'source_code'    => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
        'name'           => 'Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal)',
        'short_name'     => 'Tatah Bangkal Masuk Kota',
        'category'       => 'Ruas Jalan',
        'location'       => 'Jl. Gubernur Soebardjo, Tatah Bangkal, Banjarmasin',
        'city'           => 'Kota Banjarmasin',
        'latitude'       => -3.372661,
        'longitude'      => 114.587545,
        'status'         => 'online',
        'resolution'     => '1280x720 (HD)',
        'description'    => 'Kamera integrasi model AI YOLO + Classifier 5 kelas untuk deteksi truk over dimension.',
        'attribution'    => 'ATCS Dishub Kota Banjarmasin',
        'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        'stream_file'    => 'index.m3u8',
        'is_ai_active'   => true,
    ];

    /**
     * Mengembalikan kamera uji coba integrasi model AI
     */
    public function getAiTestCamera(): array
    {
        return $this->enrichCameraData($this->aiTestCamera);
    }

    /**
     * Mengembalikan seluruh daftar kamera aktif untuk Live CCTV
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
     * Alias method untuk Live CCTV
     */
    public function getLiveCameras(): array
    {
        return $this->getCctvList();
    }

    /**
     * Cari kamera berdasarkan kode (misal ATCS-01, ATCS-02, ATCS-TEST), source_code, ID, atau kata kunci
     */
    public function getCctvByCode(string $code): ?array
    {
        $normalized = strtolower(trim($code));
        if ($normalized === '') {
            return null;
        }

        // Alias ATCS-TEST diarahkan ke ATCS-01 (Gubernur Soebardjo)
        if ($normalized === 'atcs-test' || $normalized === 'soebardjo') {
            return $this->enrichCameraData($this->cameras['jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota']);
        }
        if ($normalized === 'sultan_adam' || $normalized === 'sungai_andai') {
            return $this->enrichCameraData($this->cameras['simpang_sultan_adam_sungai_andai']);
        }

        foreach ($this->cameras as $sourceCode => $camera) {
            if (
                strtolower($camera['code']) === $normalized ||
                strtolower($camera['id']) === $normalized ||
                strtolower($sourceCode) === $normalized ||
                str_contains($sourceCode, $normalized)
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
        return array_key_exists($code, $this->cameras);
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
        $streamFile = $camera['stream_file'] ?? 'index.m3u8';

        $camera['provider']           = self::PROVIDER;
        $camera['upstream_stream']    = self::UPSTREAM_HOST . "/stream/{$sourceCode}/{$streamFile}";
        $camera['upstream_snapshot']  = self::UPSTREAM_HOST . "/screenshot/{$sourceCode}.webp";
        
        // Langsung gunakan stream resmi ATCS Banjarmasin (memiliki CORS * dan SSL tepercaya)
        // Menghindari proxy Python lokal agar pemutaran video HLS real-time tanpa buffering.
        $camera['stream_url']         = $camera['upstream_stream'];
        $camera['proxy_stream']       = $camera['upstream_stream'];
        $camera['snapshot_url']       = $camera['upstream_snapshot'];
        $camera['proxy_snapshot']     = $camera['upstream_snapshot'];
        
        // Kompatibilitas dengan tampilan partial cctv_card (image_thumb)
        $camera['image_thumb']        = $camera['upstream_snapshot'];
        $camera['is_live_capable']    = true;

        return $camera;
    }
}
