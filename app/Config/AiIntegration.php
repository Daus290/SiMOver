<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Kontrak Integrasi AI SiMover (Prompt 53)
 *
 * Satu sumber kebenaran untuk ingestion, dashboard, status dan host core.
 * Core deteksi milik Rizkiya TIDAK diatur di sini; file ini hanya mengatur
 * bagaimana web menerima, menandai asal, dan menampilkan hasil.
 */
class AiIntegration extends BaseConfig
{
    /**
     * Urutan kelas model best_yolo_cls.onnx (dari metadata ONNX & class_mapping.json).
     * Indeks dan nama kelas tidak boleh diubah. Label hanya untuk tampilan.
     *
     * @var array<int, array{name: string, label: string, color: string}>
     */
    public array $classes = [
        0 => ['name' => 'truck_box',            'label' => 'Truk Box',                'color' => '#0891B2'],
        1 => ['name' => 'truck_container',      'label' => 'Truk Kontainer',          'color' => '#2563EB'],
        2 => ['name' => 'truck_over_dimension', 'label' => 'Truk Over Dimension',     'color' => '#F97316'],
        3 => ['name' => 'truck_tangki',         'label' => 'Truk Tangki',             'color' => '#A16207'],
        4 => ['name' => 'truck_tidak_over',     'label' => 'Truk Bak Terbuka Normal', 'color' => '#16A34A'],
    ];

    /**
     * Kelas yang dianggap indikasi over dimension (sesuai odol_alert_classes metadata teman).
     *
     * @var list<string>
     */
    public array $odClassNames = ['truck_over_dimension'];

    /**
     * Satu-satunya kamera uji yang boleh mengirim hasil.
     */
    public array $testCamera = [
        'code'        => 'ATCS-TEST',
        'source_code' => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
        'name'        => 'Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Masuk Kota',
    ];

    /**
     * Asal data (data_origin). Asal ditentukan server dari token pengirim, bukan dari payload.
     *
     * official    : keluaran resmi core Rizkiya
     * public      : boleh dipilih sebagai sumber dashboard publik
     * unit        : satuan satu baris (frame_sample | track_event | unknown)
     */
    public array $origins = [
        'rizkiya_core' => [
            'label'       => 'Core Rizkiya',
            'short_label' => 'Core Rizkiya',
            'official'    => true,
            'public'      => true,
            'unit'        => 'unknown',
            'description' => 'Hasil dari core deteksi resmi milik Rizkiya melalui pengirim tepercaya.',
        ],
        'antigravity_worker_p52' => [
            'label'       => 'Uji Worker Antigravity (Prompt 52)',
            'short_label' => 'Uji lama (bukan core)',
            'official'    => false,
            'public'      => true,
            'unit'        => 'track_event',
            'description' => 'Data uji dari worker Python buatan Antigravity, bukan core Rizkiya. '
                . 'Confidence terdistorsi softmax ganda (maks. 0,405), mobil/bus ikut diklasifikasi, '
                . 'dan kendaraan diam dapat terhitung. Tidak tervalidasi.',
        ],
        'test_fixture' => [
            'label'       => 'Fixture Pengujian',
            'short_label' => 'Fixture',
            'official'    => false,
            'public'      => false,
            'unit'        => 'unknown',
            'description' => 'Data buatan untuk uji otomatis. Tidak pernah tampil di dashboard publik.',
        ],
    ];

    /**
     * Pengirim tepercaya: nama variabel env token => asal data & nama pengirim.
     * Token hanya dibaca dari environment (.env). Tidak ada fallback di source.
     * Token yang kosong berarti pengirim tersebut tidak aktif.
     */
    public array $senders = [
        'AI_CORE_INGEST_TOKEN' => ['origin' => 'rizkiya_core',           'sender' => 'rizkiya_core_sender'],
        'AI_INGEST_TOKEN'      => ['origin' => 'antigravity_worker_p52', 'sender' => 'worker_tatah_bangkal'],
        'AI_TEST_INGEST_TOKEN' => ['origin' => 'test_fixture',           'sender' => 'test_fixture_sender', 'testing_only' => true],
    ];

    /**
     * Pilihan sumber pada dashboard publik (parameter ?source=) => data_origin.
     * Kunci pertama adalah default.
     */
    public array $dashboardSources = [
        'core'     => 'rizkiya_core',
        'uji-lama' => 'antigravity_worker_p52',
    ];

    /**
     * Asal data yang boleh menampilkan kotak deteksi di player.
     * Kosong: core Rizkiya belum mengeluarkan koordinat & waktu frame.
     *
     * @var list<string>
     */
    public array $overlayOrigins = ['rizkiya_core', 'antigravity_worker_p52'];

    /** Batas validasi ingestion */
    public int $maxBodyBytes      = 65536;
    public int $maxBatchSize      = 50;
    public int $maxMetadataBytes  = 4096;
    public int $maxPastDays       = 7;
    public int $maxFutureSkewSec  = 300;

    /** Heartbeat lebih tua dari ini dianggap kedaluwarsa (detik) */
    public int $staleAfterSec = 15;

    /** Metadata overlay lebih tua dari ini dibuang (detik) */
    public float $overlayFreshSec = 3.5;

    /** Salinan core Rizkiya di proyek dan hash file asli yang diterima */
    public string $rizkiyaCorePath   = APPPATH . 'ThirdParty/RizkiyaCore/file-rizkiya.txt';
    public string $rizkiyaCoreSha256 = '3de8c05467b3790ef535a7f54c7d3173911e78212c8ed5c234d23406115f6348';

    public function classByIndex(int $index): ?array
    {
        return $this->classes[$index] ?? null;
    }

    public function classIndexByName(string $name): ?int
    {
        foreach ($this->classes as $idx => $class) {
            if ($class['name'] === $name) {
                return $idx;
            }
        }

        return null;
    }

    public function isOdClass(string $name): bool
    {
        return in_array($name, $this->odClassNames, true);
    }

    public function unitLabel(string $unit): string
    {
        return match ($unit) {
            'track_event'  => 'event per track',
            'frame_sample' => 'sampel klasifikasi per frame',
            default        => 'hasil klasifikasi',
        };
    }
}
