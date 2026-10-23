<?php

namespace App\Libraries;

/**
 * AtcsCctvService
 * 
 * Pengelola konfigurasi terpusat untuk 34 kamera CCTV publik ATCS Dishub Kota Banjarmasin.
 * Hanya kamera Jl. Gubernur Soebardjo Masuk Kota (ATCS-01) yang mengaktifkan deteksi AI Over Dimension,
 * sedangkan 33 kamera lainnya difungsikan khusus pemantauan visual (monitoring view).
 */
class AtcsCctvService
{
    /**
     * Provider identitas sumber streaming
     */
    public const PROVIDER = 'atcs_banjarmasin';
    public const UPSTREAM_HOST = 'https://atcs.banjarmasinkota.go.id';

    /**
     * Daftar 34 kamera resmi ATCS Dishub Kota Banjarmasin
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $cameras = [
        'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota' => [
            'id'             => 'atcs-01',
            'code'           => 'ATCS-01',
            'source_code'    => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
            'name'           => 'Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Masuk Kota',
            'short_name'     => 'Jl. Gubernur Soebardjo Jbt. Ta',
            'category'       => 'Jembatan',
            'location'       => 'Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Masuk Kota, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.372631813419066,
            'longitude'      =>  114.58734281104857,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Jalur lingkar angkutan logistik dan peti kemas Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal). Kamera terintegrasi sistem deteksi AI Over Dimension.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'video1_stream.m3u8',
            'is_ai_active'   => true,
        ],
        'simpang_mentari' => [
            'id'             => 'atcs-02',
            'code'           => 'ATCS-02',
            'source_code'    => 'simpang_mentari',
            'name'           => 'Simpang Mentari',
            'short_name'     => 'Spg. Mentari',
            'category'       => 'Simpang',
            'location'       => 'Simpang Mentari, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.324434,
            'longitude'      => 114.591027,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Mentari.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jalan_lambung_mangkurat_bank_bi' => [
            'id'             => 'atcs-03',
            'code'           => 'ATCS-03',
            'source_code'    => 'jalan_lambung_mangkurat_bank_bi',
            'name'           => 'Jalan Lambung Mangkurat (Depan Bank Indonesia)',
            'short_name'     => 'Jl. Lambung Mangkurat (Depan B',
            'category'       => 'Ruas Jalan',
            'location'       => 'Jalan Lambung Mangkurat (Depan Bank Indonesia), Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.323033,
            'longitude'      => 114.591620,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jalan Lambung Mangkurat (Depan Bank Indonesia).',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jalan_a_yani_km_3' => [
            'id'             => 'atcs-04',
            'code'           => 'ATCS-04',
            'source_code'    => 'jalan_a_yani_km_3',
            'name'           => 'Jalan A Yani KM 3 (Depan PDAM)',
            'short_name'     => 'Jl. A Yani KM 3 (Depan PDAM)',
            'category'       => 'Ruas Jalan',
            'location'       => 'Jalan A Yani KM 3 (Depan PDAM), Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.325743,
            'longitude'      => 114.605113,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jalan A Yani KM 3 (Depan PDAM).',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_hasan_basri_cemara' => [
            'id'             => 'atcs-05',
            'code'           => 'ATCS-05',
            'source_code'    => 'simpang_hasan_basri_cemara',
            'name'           => 'Simpang Hasan Basri - Cemara',
            'short_name'     => 'Spg. Hasan Basri - Cemara',
            'category'       => 'Simpang',
            'location'       => 'Simpang Hasan Basri - Cemara, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.292250,
            'longitude'      => 114.589545,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Hasan Basri - Cemara.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_sultan_adam_sungai_andai' => [
            'id'             => 'atcs-06',
            'code'           => 'ATCS-06',
            'source_code'    => 'simpang_sultan_adam_sungai_andai',
            'name'           => 'Simpang Sultan Adam - Sungai Andai',
            'short_name'     => 'Spg. Sultan Adam - Sungai Anda',
            'category'       => 'Simpang',
            'location'       => 'Simpang Sultan Adam - Sungai Andai, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.298744,
            'longitude'      => 114.605329,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Sultan Adam - Sungai Andai.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_keluar_kota' => [
            'id'             => 'atcs-07',
            'code'           => 'ATCS-07',
            'source_code'    => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_keluar_kota',
            'name'           => 'Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Keluar Kota',
            'short_name'     => 'Jl. Gubernur Soebardjo Jbt. Ta',
            'category'       => 'Jembatan',
            'location'       => 'Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Keluar Kota, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.372661018410213,
            'longitude'      => 114.58754469407165,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Keluar Kota.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jembatan_alalak_arah_luar_kota' => [
            'id'             => 'atcs-08',
            'code'           => 'ATCS-08',
            'source_code'    => 'jembatan_alalak_arah_luar_kota',
            'name'           => 'Jembatan Alalak Arah Luar Kota',
            'short_name'     => 'Jbt. Alalak Arah Luar Kota',
            'category'       => 'Jembatan',
            'location'       => 'Jembatan Alalak Arah Luar Kota, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.277988,
            'longitude'      => 114.589547,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jembatan Alalak Arah Luar Kota.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_cemara' => [
            'id'             => 'atcs-09',
            'code'           => 'ATCS-09',
            'source_code'    => 'simpang_cemara',
            'name'           => 'Simpang Cemara',
            'short_name'     => 'Spg. Cemara',
            'category'       => 'Simpang',
            'location'       => 'Simpang Cemara, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.289781,
            'longitude'      => 114.596235,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Cemara.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_rk_ilir_teluk_tiram_darat' => [
            'id'             => 'atcs-10',
            'code'           => 'ATCS-10',
            'source_code'    => 'simpang_rk_ilir_teluk_tiram_darat',
            'name'           => 'Simpang RK Ilir / Teluk Tiram Darat',
            'short_name'     => 'Spg. RK Ilir / Teluk Tiram Dar',
            'category'       => 'Simpang',
            'location'       => 'Simpang RK Ilir / Teluk Tiram Darat, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.329849,
            'longitude'      => 114.584534,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang RK Ilir / Teluk Tiram Darat.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'u_turn_jalan_a_yani_km_5' => [
            'id'             => 'atcs-11',
            'code'           => 'ATCS-11',
            'source_code'    => 'u_turn_jalan_a_yani_km_5',
            'name'           => 'U-Turn Jalan A Yani KM 5',
            'short_name'     => 'U-Turn Jl. A Yani KM 5',
            'category'       => 'Putar Balik',
            'location'       => 'U-Turn Jalan A Yani KM 5, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.347506,
            'longitude'      => 114.624710,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS U-Turn Jalan A Yani KM 5.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'u_turn_jalan_s_parman_arah_luar_kota' => [
            'id'             => 'atcs-12',
            'code'           => 'ATCS-12',
            'source_code'    => 'u_turn_jalan_s_parman_arah_luar_kota',
            'name'           => 'U-Turn Jalan S. Parman Arah Luar Kota',
            'short_name'     => 'U-Turn Jl. S. Parman Arah Luar',
            'category'       => 'Putar Balik',
            'location'       => 'U-Turn Jalan S. Parman Arah Luar Kota, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.306846,
            'longitude'      => 114.588452,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS U-Turn Jalan S. Parman Arah Luar Kota.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jembatan_sungai_andai' => [
            'id'             => 'atcs-13',
            'code'           => 'ATCS-13',
            'source_code'    => 'jembatan_sungai_andai',
            'name'           => 'Jembatan Sungai Andai',
            'short_name'     => 'Jbt. Sungai Andai',
            'category'       => 'Jembatan',
            'location'       => 'Jembatan Sungai Andai, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.296664,
            'longitude'      => 114.606822,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jembatan Sungai Andai.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_a_yani_gatot_fly_over_km_3' => [
            'id'             => 'atcs-14',
            'code'           => 'ATCS-14',
            'source_code'    => 'simpang_a_yani_gatot_fly_over_km_3',
            'name'           => 'Simpang A Yani Gatot / Fly Over KM 3',
            'short_name'     => 'Spg. A Yani Gatot / Fly Over K',
            'category'       => 'Simpang',
            'location'       => 'Simpang A Yani Gatot / Fly Over KM 3, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.331971,
            'longitude'      => 114.613616,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang A Yani Gatot / Fly Over KM 3.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jalan_pangeran_samudera_antasari' => [
            'id'             => 'atcs-15',
            'code'           => 'ATCS-15',
            'source_code'    => 'jalan_pangeran_samudera_antasari',
            'name'           => 'Jembatan Pangeran Samudera (Antasari)',
            'short_name'     => 'Jbt. Pangeran Samudera (Antasa',
            'category'       => 'Jembatan',
            'location'       => 'Jembatan Pangeran Samudera (Antasari), Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.326228,
            'longitude'      => 114.595632,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jembatan Pangeran Samudera (Antasari).',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'u_turn_a_yani_km_2' => [
            'id'             => 'atcs-16',
            'code'           => 'ATCS-16',
            'source_code'    => 'u_turn_a_yani_km_2',
            'name'           => 'U-Turn A Yani KM 2',
            'short_name'     => 'U-Turn A Yani KM 2',
            'category'       => 'Putar Balik',
            'location'       => 'U-Turn A Yani KM 2, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.323777,
            'longitude'      => 114.600674,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS U-Turn A Yani KM 2.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_pasar_lama' => [
            'id'             => 'atcs-17',
            'code'           => 'ATCS-17',
            'source_code'    => 'simpang_pasar_lama',
            'name'           => 'Simpang Pasar Lama',
            'short_name'     => 'Spg. Pasar Lama',
            'category'       => 'Simpang',
            'location'       => 'Simpang Pasar Lama, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.311398,
            'longitude'      => 114.591925,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Pasar Lama.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_a_yani_pramuka_km_6' => [
            'id'             => 'atcs-18',
            'code'           => 'ATCS-18',
            'source_code'    => 'simpang_a_yani_pramuka_km_6',
            'name'           => 'Simpang A Yani - Pramuka KM 6',
            'short_name'     => 'Spg. A Yani - Pramuka KM 6',
            'category'       => 'Simpang',
            'location'       => 'Simpang A Yani - Pramuka KM 6, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.348813,
            'longitude'      => 114.625723,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang A Yani - Pramuka KM 6.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_antasari' => [
            'id'             => 'atcs-19',
            'code'           => 'ATCS-19',
            'source_code'    => 'simpang_antasari',
            'name'           => 'Simpang Antasari',
            'short_name'     => 'Spg. Antasari',
            'category'       => 'Simpang',
            'location'       => 'Simpang Antasari, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.327537,
            'longitude'      => 114.597745,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Antasari.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_s_parman_belitung' => [
            'id'             => 'atcs-20',
            'code'           => 'ATCS-20',
            'source_code'    => 'simpang_s_parman_belitung',
            'name'           => 'Simpang S. Parman - Belitung',
            'short_name'     => 'Spg. S. Parman - Belitung',
            'category'       => 'Simpang',
            'location'       => 'Simpang S. Parman - Belitung, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.310373,
            'longitude'      => 114.589100,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang S. Parman - Belitung.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_s_parman_tarakan' => [
            'id'             => 'atcs-21',
            'code'           => 'ATCS-21',
            'source_code'    => 'simpang_s_parman_tarakan',
            'name'           => 'Simpang S. Parman - Tarakan',
            'short_name'     => 'Spg. S. Parman - Tarakan',
            'category'       => 'Simpang',
            'location'       => 'Simpang S. Parman - Tarakan, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.314317,
            'longitude'      => 114.589819,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang S. Parman - Tarakan.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_veteran_gatot' => [
            'id'             => 'atcs-22',
            'code'           => 'ATCS-22',
            'source_code'    => 'simpang_veteran_gatot',
            'name'           => 'Simpang Veteran - Gatot',
            'short_name'     => 'Spg. Veteran - Gatot',
            'category'       => 'Simpang',
            'location'       => 'Simpang Veteran - Gatot, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.319693,
            'longitude'      => 114.617718,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Veteran - Gatot.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jembatan_mesjid_jami_arah_mesjid' => [
            'id'             => 'atcs-23',
            'code'           => 'ATCS-23',
            'source_code'    => 'jembatan_mesjid_jami_arah_mesjid',
            'name'           => 'Jembatan Mesjid Jami',
            'short_name'     => 'Jbt. Mesjid Jami',
            'category'       => 'Jembatan',
            'location'       => 'Jembatan Mesjid Jami, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.307603,
            'longitude'      => 114.594445,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jembatan Mesjid Jami.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_s_parman_arah_tarakan' => [
            'id'             => 'atcs-24',
            'code'           => 'ATCS-24',
            'source_code'    => 'simpang_s_parman_arah_tarakan',
            'name'           => 'Simpang S. Parman Arah Tarakan',
            'short_name'     => 'Spg. S. Parman Arah Tarakan',
            'category'       => 'Simpang',
            'location'       => 'Simpang S. Parman Arah Tarakan, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.314341,
            'longitude'      => 114.589962,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang S. Parman Arah Tarakan.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_a_yani_antasari_km_3' => [
            'id'             => 'atcs-25',
            'code'           => 'ATCS-25',
            'source_code'    => 'simpang_a_yani_antasari_km_3',
            'name'           => 'Simpang A Yani - Antasari KM 3',
            'short_name'     => 'Spg. A Yani - Antasari KM 3',
            'category'       => 'Simpang',
            'location'       => 'Simpang A Yani - Antasari KM 3, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.326685,
            'longitude'      => 114.606525,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang A Yani - Antasari KM 3.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'jl_ujung_murung_1' => [
            'id'             => 'atcs-26',
            'code'           => 'ATCS-26',
            'source_code'    => 'jl_ujung_murung_1',
            'name'           => 'Jembatan PHB Sutoyo',
            'short_name'     => 'Jbt. PHB Sutoyo',
            'category'       => 'Jembatan',
            'location'       => 'Jembatan PHB Sutoyo, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.324185,
            'longitude'      => 114.567028,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Jembatan PHB Sutoyo.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_gerilya' => [
            'id'             => 'atcs-27',
            'code'           => 'ATCS-27',
            'source_code'    => 'simpang_gerilya',
            'name'           => 'Simpang Gerilya',
            'short_name'     => 'Spg. Gerilya',
            'category'       => 'Simpang',
            'location'       => 'Simpang Gerilya, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.350410,
            'longitude'      => 114.596541,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Gerilya.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_jembatan_merdeka_mesjid_raya' => [
            'id'             => 'atcs-28',
            'code'           => 'ATCS-28',
            'source_code'    => 'simpang_jembatan_merdeka_mesjid_raya',
            'name'           => 'Simpang Jembatan Merdeka / Mesjid Raya',
            'short_name'     => 'Spg. Jbt. Merdeka / Mesjid Ray',
            'category'       => 'Simpang',
            'location'       => 'Simpang Jembatan Merdeka / Mesjid Raya, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.320304,
            'longitude'      => 114.592638,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Jembatan Merdeka / Mesjid Raya.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_kamboja' => [
            'id'             => 'atcs-29',
            'code'           => 'ATCS-29',
            'source_code'    => 'simpang_kamboja',
            'name'           => 'Simpang Kamboja',
            'short_name'     => 'Spg. Kamboja',
            'category'       => 'Simpang',
            'location'       => 'Simpang Kamboja, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.319666,
            'longitude'      => 114.587281,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Kamboja.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_mulawarman' => [
            'id'             => 'atcs-30',
            'code'           => 'ATCS-30',
            'source_code'    => 'simpang_mulawarman',
            'name'           => 'Simpang Mulawarman',
            'short_name'     => 'Spg. Mulawarman',
            'category'       => 'Simpang',
            'location'       => 'Simpang Mulawarman, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.317096,
            'longitude'      => 114.584417,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Mulawarman.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_a_yani_kuripan_km_2' => [
            'id'             => 'atcs-31',
            'code'           => 'ATCS-31',
            'source_code'    => 'simpang_a_yani_kuripan_km_2',
            'name'           => 'Simpang A Yani - Kuripan KM 2',
            'short_name'     => 'Spg. A Yani - Kuripan KM 2',
            'category'       => 'Simpang',
            'location'       => 'Simpang A Yani - Kuripan KM 2, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.325449,
            'longitude'      => 114.604707,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang A Yani - Kuripan KM 2.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_sutoyo_jafri_zam_zam' => [
            'id'             => 'atcs-32',
            'code'           => 'ATCS-32',
            'source_code'    => 'simpang_sutoyo_jafri_zam_zam',
            'name'           => 'Simpang Sutoyo / Jafri Zam Zam',
            'short_name'     => 'Spg. Sutoyo / Jafri Zam Zam',
            'category'       => 'Simpang',
            'location'       => 'Simpang Sutoyo / Jafri Zam Zam, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.321370,
            'longitude'      => 114.579188,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Sutoyo / Jafri Zam Zam.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_3_piston_jembatan_merdeka' => [
            'id'             => 'atcs-33',
            'code'           => 'ATCS-33',
            'source_code'    => 'simpang_3_piston_jembatan_merdeka',
            'name'           => 'Simpang 3 Piston Jembatan Merdeka',
            'short_name'     => 'Spg. 3 Piston Jbt. Merdeka',
            'category'       => 'Simpang',
            'location'       => 'Simpang 3 Piston Jembatan Merdeka, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.320683,
            'longitude'      => 114.594317,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang 3 Piston Jembatan Merdeka.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
        'simpang_kol_sugiono' => [
            'id'             => 'atcs-34',
            'code'           => 'ATCS-34',
            'source_code'    => 'simpang_kol_sugiono',
            'name'           => 'Simpang Kol. Sugiono',
            'short_name'     => 'Spg. Kol. Sugiono',
            'category'       => 'Simpang',
            'location'       => 'Simpang Kol. Sugiono, Banjarmasin',
            'city'           => 'Kota Banjarmasin',
            'latitude'       => -3.323454,
            'longitude'      => 114.599536,
            'status'         => 'online',
            'resolution'     => '1280x720 (HD)',
            'description'    => 'Pemantauan kamera lalu lintas ATCS Simpang Kol. Sugiono.',
            'attribution'    => 'ATCS Dishub Kota Banjarmasin',
            'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
            'stream_file'    => 'index.m3u8',
            'is_ai_active'   => false,
        ],
    ];

    /**
     * Kamera Khusus Pengujian AI Utama (Jl. Gubernur Soebardjo Tatah Bangkal)
     */
    protected array $aiTestCamera = [
        'id'             => 'atcs-01',
        'code'           => 'ATCS-01',
        'source_code'    => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
        'name'           => 'Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal Masuk Kota)',
        'short_name'     => 'Tatah Bangkal Masuk Kota',
        'category'       => 'Ruas Jalan',
        'location'       => 'Jl. Gubernur Soebardjo, Banjarmasin',
        'city'           => 'Kota Banjarmasin',
        'latitude'       => -3.372631813419066,
        'longitude'      => 114.58734281104857,
        'status'         => 'online',
        'resolution'     => '1280x720 (HD)',
        'description'    => 'Kamera integrasi model AI YOLO + Classifier 5 kelas untuk deteksi truk over dimension.',
        'attribution'    => 'ATCS Dishub Kota Banjarmasin',
        'source_url'     => 'https://atcs.banjarmasinkota.go.id/',
        'stream_file'    => 'video1_stream.m3u8',
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
     * Mengembalikan seluruh daftar kamera aktif untuk Live CCTV (34 kamera)
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
     * Cari kamera berdasarkan kode (misal ATCS-01, ATCS-02), source_code, ID, atau kata kunci
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
        
        // Proxy lokal cepat (port 8088) dengan fallback upstream langsung
        $camera['local_proxy_stream'] = "http://127.0.0.1:8088/stream/{$sourceCode}/{$streamFile}";
        $camera['stream_url']         = $camera['local_proxy_stream'];
        $camera['proxy_stream']       = $camera['local_proxy_stream'];
        $camera['snapshot_url']       = $camera['upstream_snapshot'];
        $camera['proxy_snapshot']     = $camera['upstream_snapshot'];
        
        // Kompatibilitas dengan tampilan partial cctv_card (image_thumb)
        $camera['image_thumb']        = $camera['upstream_snapshot'];
        $camera['is_live_capable']    = true;

        return $camera;
    }
}
