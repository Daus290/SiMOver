<?php

namespace App\Libraries;

/**
 * VivotekCctvService
 * 
 * Layanan integrasi kamera CCTV Dishub Kota Banjarbaru melalui Vivotek NVR ND9541.
 * Khusus penggunaan jaringan kantor (Local LAN / Wi-Fi).
 * 
 * Mengelola registri kamera Banjarbaru, pemetaan kanal NVR C_0 - C_18,
 * status tayangan HLS terkelola, dan cuplikan snapshot resmi.
 */
class VivotekCctvService
{
    public const SOURCE = 'vivotek_dishub';
    public const SOURCE_LABEL = 'Vivotek • Dishub Kota Banjarbaru';
    public const SOURCE_NOTE = 'Akses Jaringan Kantor';

    /**
     * Registri Terpusat 18 Kamera Banjarbaru berdasarkan data Ground-Truth NVR ND9541
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $cameras = [
        // =====================================================================
        // 1. SIMPANG TIGA RO ULIN (4 Titik: 3 Terpasang, 1 Belum Tersedia)
        // =====================================================================
        'BJB-ROULIN-F1' => [
            'code'            => 'BJB-ROULIN-F1',
            'location_id'     => 's3-ro-ulin',
            'name'            => 'RO Ulin Fase 1',
            'full_name'       => 'Simpang Tiga RO Ulin - Fase 1',
            'nvr_name'        => 'Fase 1 s3 RO Ulin',
            'channel'         => 1,
            'stream_index'    => 2,
            'view_no'         => '01',
            'ip'              => '10.0.1.135',
            'model'           => 'IB9367-H',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Barat',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-ROULIN-F2' => [
            'code'            => 'BJB-ROULIN-F2',
            'location_id'     => 's3-ro-ulin',
            'name'            => 'RO Ulin Fase 2',
            'full_name'       => 'Simpang Tiga RO Ulin - Fase 2',
            'nvr_name'        => 'Fase 2 s3 RO Ulin',
            'channel'         => 2,
            'stream_index'    => 1,
            'view_no'         => '02',
            'ip'              => '10.0.1.136',
            'model'           => 'IB9367-H',
            'codec'           => 'H.265',
            'type'            => 'fixed',
            'direction'       => 'Timur',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-ROULIN-F3' => [
            'code'            => 'BJB-ROULIN-F3',
            'location_id'     => 's3-ro-ulin',
            'name'            => 'RO Ulin Fase 3',
            'full_name'       => 'Simpang Tiga RO Ulin - Fase 3',
            'nvr_name'        => 'Fase 3 s3 RO Ulin',
            'channel'         => 3,
            'stream_index'    => 1,
            'view_no'         => '03',
            'ip'              => '10.0.1.17',
            'model'           => 'IB9367-H',
            'codec'           => 'H.265',
            'type'            => 'fixed',
            'direction'       => 'Selatan',
            'is_installed'    => false, // Belum tersedia di lapangan
            'hardware_status' => 'unavailable',
        ],
        'BJB-ROULIN-PTZ' => [
            'code'            => 'BJB-ROULIN-PTZ',
            'location_id'     => 's3-ro-ulin',
            'name'            => 'RO Ulin PTZ',
            'full_name'       => 'Simpang Tiga RO Ulin - PTZ',
            'nvr_name'        => 'PTZ s3 RO Ulin',
            'channel'         => 4,
            'stream_index'    => 1,
            'view_no'         => '04',
            'ip'              => '10.0.1.134',
            'model'           => 'SD9362-EHL',
            'codec'           => 'H.264',
            'type'            => 'ptz',
            'direction'       => '360°',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],

        // =====================================================================
        // 2. SIMPANG TIGA BRIMOB (4 Titik Terpasang)
        // =====================================================================
        'BJB-BRIMOB-F1' => [
            'code'            => 'BJB-BRIMOB-F1',
            'location_id'     => 's3-brimob',
            'name'            => 'Brimob Fase 1',
            'full_name'       => 'Simpang Tiga Brimob - Fase 1',
            'nvr_name'        => 'Fase 1 s3 Brimob',
            'channel'         => 5,
            'stream_index'    => 2,
            'view_no'         => '05',
            'ip'              => '10.0.1.103',
            'model'           => 'IB9367-H',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Masuk Kota / Barat Daya',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-BRIMOB-F2' => [
            'code'            => 'BJB-BRIMOB-F2',
            'location_id'     => 's3-brimob',
            'name'            => 'Brimob Fase 2',
            'full_name'       => 'Simpang Tiga Brimob - Fase 2',
            'nvr_name'        => 'Fase 2 s3 Brimob',
            'channel'         => 6,
            'stream_index'    => 2,
            'view_no'         => '06',
            'ip'              => '10.0.1.104',
            'model'           => 'IB9367-H',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Timur Laut',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-BRIMOB-F3' => [
            'code'            => 'BJB-BRIMOB-F3',
            'location_id'     => 's3-brimob',
            'name'            => 'Brimob Fase 3',
            'full_name'       => 'Simpang Tiga Brimob - Fase 3',
            'nvr_name'        => 'Fase 3 s3 Brimob',
            'channel'         => 7,
            'stream_index'    => 2,
            'view_no'         => '07',
            'ip'              => '10.0.1.18',
            'model'           => 'DS-2CD2T63G2-2LI2U',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Tenggara',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-BRIMOB-PTZ' => [
            'code'            => 'BJB-BRIMOB-PTZ',
            'location_id'     => 's3-brimob',
            'name'            => 'Brimob PTZ',
            'full_name'       => 'Simpang Tiga Brimob - PTZ',
            'nvr_name'        => 'PTZ s3 Brimob',
            'channel'         => 8,
            'stream_index'    => 3,
            'view_no'         => '08',
            'ip'              => '10.0.1.102',
            'model'           => 'SD9362-EHL',
            'codec'           => 'H.264',
            'type'            => 'ptz',
            'direction'       => 'Keluar Kota / 360°',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],

        // =====================================================================
        // 3. SIMPANG EMPAT SAMSAT (5 Titik: 4 Aktif, 1 Rusak di Lapangan)
        // =====================================================================
        'BJB-SAMSAT-F1' => [
            'code'            => 'BJB-SAMSAT-F1',
            'location_id'     => 's4-samsat',
            'name'            => 'Samsat Fase 1',
            'full_name'       => 'Simpang Empat Samsat - Fase 1',
            'nvr_name'        => 'Fase 1 s4 samsat',
            'channel'         => 9,
            'stream_index'    => 1,
            'view_no'         => '09',
            'ip'              => '10.0.4.18',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Selatan',
            'is_installed'    => true,
            'hardware_status' => 'offline_damaged',
        ],
        'BJB-SAMSAT-F2' => [
            'code'            => 'BJB-SAMSAT-F2',
            'location_id'     => 's4-samsat',
            'name'            => 'Samsat Fase 2',
            'full_name'       => 'Simpang Empat Samsat - Fase 2',
            'nvr_name'        => 'Fase 2 s4 samsat',
            'channel'         => 10,
            'stream_index'    => 2,
            'view_no'         => '10',
            'ip'              => '10.0.4.19',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Timur',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-SAMSAT-F3' => [
            'code'            => 'BJB-SAMSAT-F3',
            'location_id'     => 's4-samsat',
            'name'            => 'Samsat Fase 3',
            'full_name'       => 'Simpang Empat Samsat - Fase 3',
            'nvr_name'        => 'Fase 3 s4 samsat',
            'channel'         => 11,
            'stream_index'    => 2,
            'view_no'         => '11',
            'ip'              => '10.0.4.20',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Barat',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-SAMSAT-F4' => [
            'code'            => 'BJB-SAMSAT-F4',
            'location_id'     => 's4-samsat',
            'name'            => 'Samsat Fase 4',
            'full_name'       => 'Simpang Empat Samsat - Fase 4',
            'nvr_name'        => 'Fase 4 s4 samsat',
            'channel'         => 12,
            'stream_index'    => 2,
            'view_no'         => '12',
            'ip'              => '10.0.4.21',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Utara',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-SAMSAT-PTZ' => [
            'code'            => 'BJB-SAMSAT-PTZ',
            'location_id'     => 's4-samsat',
            'name'            => 'Samsat PTZ',
            'full_name'       => 'Simpang Empat Samsat - PTZ',
            'nvr_name'        => 'PTZ s4 samsat',
            'channel'         => 13,
            'stream_index'    => 2,
            'view_no'         => '13',
            'ip'              => '10.0.4.17',
            'model'           => 'SD9384-EHL',
            'codec'           => 'H.264',
            'type'            => 'ptz',
            'direction'       => '360°',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],

        // =====================================================================
        // 4. SIMPANG EMPAT ADIPURA (5 Titik Terpasang)
        // =====================================================================
        'BJB-ADIPURA-F1' => [
            'code'            => 'BJB-ADIPURA-F1',
            'location_id'     => 's4-adipura',
            'name'            => 'Adipura Fase 1',
            'full_name'       => 'Simpang Empat Adipura - Fase 1',
            'nvr_name'        => 'Fase 1 S4 Tugu Adipura',
            'channel'         => 15,
            'stream_index'    => 3,
            'view_no'         => '15',
            'ip'              => '10.0.3.18',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Barat',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-ADIPURA-F2' => [
            'code'            => 'BJB-ADIPURA-F2',
            'location_id'     => 's4-adipura',
            'name'            => 'Adipura Fase 2',
            'full_name'       => 'Simpang Empat Adipura - Fase 2',
            'nvr_name'        => 'Fase 2 S4 Tugu Adipura',
            'channel'         => 16,
            'stream_index'    => 2,
            'view_no'         => '16',
            'ip'              => '10.0.3.19',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Utara',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-ADIPURA-F3' => [
            'code'            => 'BJB-ADIPURA-F3',
            'location_id'     => 's4-adipura',
            'name'            => 'Adipura Fase 3',
            'full_name'       => 'Simpang Empat Adipura - Fase 3',
            'nvr_name'        => 'Fase 3 S4 Tugu Adipura',
            'channel'         => 17,
            'stream_index'    => 2,
            'view_no'         => '17',
            'ip'              => '10.0.3.20',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Timur',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-ADIPURA-F4' => [
            'code'            => 'BJB-ADIPURA-F4',
            'location_id'     => 's4-adipura',
            'name'            => 'Adipura Fase 4',
            'full_name'       => 'Simpang Empat Adipura - Fase 4',
            'nvr_name'        => 'Fase 4 S4 Tugu Adipura',
            'channel'         => 18,
            'stream_index'    => 2,
            'view_no'         => '18',
            'ip'              => '10.0.3.21',
            'model'           => 'IB9387-HT-A',
            'codec'           => 'H.264',
            'type'            => 'fixed',
            'direction'       => 'Selatan',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
        'BJB-ADIPURA-PTZ' => [
            'code'            => 'BJB-ADIPURA-PTZ',
            'location_id'     => 's4-adipura',
            'name'            => 'Adipura PTZ',
            'full_name'       => 'Simpang Empat Adipura - PTZ',
            'nvr_name'        => 'PTZ S4 Adipura',
            'channel'         => 19,
            'stream_index'    => 3,
            'view_no'         => '19',
            'ip'              => '10.0.3.17',
            'model'           => 'DS-2DF8225IX-AEL',
            'codec'           => 'H.264',
            'type'            => 'ptz',
            'direction'       => '360°',
            'is_installed'    => true,
            'hardware_status' => 'active',
        ],
    ];

    protected string $streamCacheDir;
    protected string $snapCacheDir;

    public function __construct()
    {
        $this->streamCacheDir = WRITEPATH . 'cache/vivotek_stream/';
        $this->snapCacheDir   = WRITEPATH . 'cache/vivotek_snapshot/';

        if (!is_dir($this->streamCacheDir)) {
            @mkdir($this->streamCacheDir, 0775, true);
        }
        if (!is_dir($this->snapCacheDir)) {
            @mkdir($this->snapCacheDir, 0775, true);
        }
    }

    /**
     * Periksa apakah kode kamera terdaftar dalam registri Banjarbaru
     */
    public function isAllowedCode(string $code): bool
    {
        return isset($this->cameras[$code]);
    }

    /**
     * Dapatkan konfigurasi kamera berdasarkan kode
     */
    public function getCameraConfig(string $code): ?array
    {
        return $this->cameras[$code] ?? null;
    }

    /**
     * Dapatkan seluruh kode yang terdaftar
     */
    public function getAllowedCodes(): array
    {
        return array_keys($this->cameras);
    }

    /**
     * Dapatkan kode kamera yang terpasang dan dapat direlay
     */
    public function getRelayableCodes(): array
    {
        $relayable = [];
        foreach ($this->cameras as $code => $cam) {
            if ($cam['is_installed'] && $cam['hardware_status'] === 'active') {
                $relayable[] = $code;
            }
        }
        return $relayable;
    }

    /**
     * Dapatkan daftar kamera berdasarkan persimpangan
     */
    public function getCodesByIntersection(string $locationId): array
    {
        $codes = [];
        foreach ($this->cameras as $code => $cam) {
            if ($cam['location_id'] === $locationId) {
                $codes[] = $code;
            }
        }
        return $codes;
    }

    /**
     * Cek apakah kredensial NVR telah dikonfigurasi di .env
     */
    public function isConfigured(): bool
    {
        $user = env('vivotek.nvrUsername');
        $pass = env('vivotek.nvrPassword');
        return !empty($user) && !empty($pass);
    }

    /**
     * Dapatkan Host NVR dari konfigurasi .env
     */
    public function getNvrHost(): string
    {
        return (string)env('vivotek.nvrHost', '10.0.1.3');
    }

    /**
     * Dapatkan RTSP Port NVR
     */
    public function getNvrRtspPort(): int
    {
        $p = env('vivotek.nvrRtspPort');
        return $p ? (int)$p : 8554;
    }

    /**
     * Dapatkan URL RTSP upstream internal tanpa mengekspos ke publik
     */
    public function getUpstreamRtspUrl(string $code): ?string
    {
        if (!$this->isAllowedCode($code)) {
            return null;
        }

        $cam = $this->cameras[$code];
        if (!$cam['is_installed'] || $cam['hardware_status'] !== 'active') {
            return null;
        }

        $host = $this->getNvrHost();
        $port = $this->getNvrRtspPort();
        $chan = $cam['channel'];
        $streamIdx = $cam['stream_index'] ?? 1;
        $user = env('vivotek.nvrUsername');
        $pass = env('vivotek.nvrPassword');

        $auth = '';
        if (!empty($user)) {
            $auth = rawurlencode($user) . ':' . rawurlencode($pass ?? '') . '@';
        }

        // Jalur RTSP Vivotek ND9541 terverifikasi dari inspeksi NVR liveview:
        // /Media/Live/Normal?camera=C_{chan}&streamindex={stream_index}
        return "rtsp://{$auth}{$host}:{$port}/Media/Live/Normal?camera=C_{$chan}&streamindex={$streamIdx}";
    }

    /**
     * Dapatkan direktori stream lokal untuk kamera tertentu
     */
    public function getStreamDir(string $code): string
    {
        $dir = $this->streamCacheDir . $code . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /**
     * Dapatkan status tayangan HLS terkelola
     * 
     * Status detail:
     * - 'unavailable'        : Titik belum terpasang di lapangan (RO Ulin F3)
     * - 'offline_damaged'    : Perangkat rusak di lapangan (Samsat F1)
     * - 'relay_active'       : Relay lokal berjalan aktif dengan segmen video segar
     * - 'stale'              : File manifest ada namun tidak ada segmen baru
     * - 'idle'               : Relay lokal belum dijalankan
     * - 'unconfigured'       : Kredensial NVR belum diisi
     * - 'not_found'          : Kode tidak terdaftar
     */
    public function getStreamStatus(string $code): array
    {
        if (!$this->isAllowedCode($code)) {
            return [
                'status'  => 'not_found',
                'message' => 'Kamera tidak terdaftar',
            ];
        }

        $cam = $this->cameras[$code];

        // Kamera belum terpasang di lapangan
        if (!$cam['is_installed']) {
            return [
                'status'  => 'unavailable',
                'message' => 'Titik kamera belum tersedia di lapangan.',
            ];
        }

        // Kamera rusak di lapangan (Samsat F1)
        if ($cam['hardware_status'] === 'offline_damaged') {
            return [
                'status'  => 'offline_damaged',
                'message' => 'Perangkat kamera di titik ini mengalami gangguan teknis di lapangan.',
            ];
        }

        // Kamera belum terhubung (aliran RTSP belum terverifikasi)
        if ($cam['hardware_status'] === 'unconnected') {
            return [
                'status'  => 'unconnected',
                'message' => 'Aliran RTSP kamera belum terhubung / belum terverifikasi dari NVR.',
            ];
        }

        $streamDir = $this->getStreamDir($code);
        $manifestFile = $streamDir . 'index.m3u8';

        if (is_file($manifestFile)) {
            $manifestAge = time() - filemtime($manifestFile);

            // Validasi keberadaan minimal 1 segmen .ts valid yang baru diperbarui
            $segments = glob($streamDir . 'seg_*.ts');
            $hasFreshSegment = false;
            $newestSegAge = 9999;

            if (!empty($segments)) {
                foreach ($segments as $seg) {
                    if (!is_file($seg)) {
                        continue;
                    }
                    $segMtime = @filemtime($seg);
                    if ($segMtime === false) {
                        continue;
                    }
                    $segAge = time() - $segMtime;
                    if ($segAge < $newestSegAge) {
                        $newestSegAge = $segAge;
                    }
                    $segSize = @filesize($seg);
                    if ($segAge <= 30 && $segSize !== false && $segSize > 5120) {
                        $hasFreshSegment = true;
                    }
                }
            }

            if ($manifestAge < 20 && $hasFreshSegment) {
                return [
                    'status'            => 'relay_active',
                    'message'           => 'Live Streaming Relay Aktif',
                    'manifest_age_sec'  => $manifestAge,
                    'segment_age_sec'   => $newestSegAge,
                ];
            }

            return [
                'status'            => 'stale',
                'message'           => 'Aliran video terputus (relay idle atau upstream timeout)',
                'manifest_age_sec'  => $manifestAge,
                'segment_age_sec'   => $newestSegAge,
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'status'  => 'unconfigured',
                'message' => 'Kredensial NVR Vivotek belum dikonfigurasi di file .env lokal',
            ];
        }

        return [
            'status'  => 'idle',
            'message' => 'Relay HLS lokal belum aktif. Jalankan vivotek_relay.ps1 untuk memulai streaming.',
        ];
    }
}
