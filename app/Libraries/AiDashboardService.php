<?php

namespace App\Libraries;

use App\Models\AiDetectionModel;
use App\Models\AiWorkerStatusModel;
use Config\AiIntegration;

/**
 * Menyusun data dashboard publik dari hasil AI tersimpan.
 * Dipakai bersama oleh halaman /dashboard dan endpoint polling /api/ai/dashboard-data
 * agar keduanya memakai kontrak, scope dan aturan filter yang sama.
 */
class AiDashboardService
{
    private AiIntegration $ai;
    private AiDetectionModel $detections;
    private AiWorkerStatusModel $status;

    public function __construct()
    {
        $this->ai         = config('AiIntegration');
        $this->detections = new AiDetectionModel();
        $this->status     = new AiWorkerStatusModel();
    }

    /**
     * Normalisasi parameter query. Nilai tidak dikenal kembali ke default
     * sehingga pilihan di UI selalu sama dengan filter yang benar-benar diterapkan.
     */
    public function normalizeParams(array $get): array
    {
        $str = static fn ($v): ?string => is_string($v) ? strtolower(trim($v)) : null;

        $period = is_numeric($get['period'] ?? null) ? (int) $get['period'] : 1;
        if (!in_array($period, [1, 7, 30], true)) {
            $period = 1;
        }

        $tab = $str($get['tab'] ?? null);
        if (!in_array($tab, ['statistik', 'hasil-deteksi'], true)) {
            $tab = 'statistik';
        }

        $source = $str($get['source'] ?? null);
        if (!array_key_exists((string) $source, $this->ai->dashboardSources)) {
            $source = array_key_first($this->ai->dashboardSources);
        }

        $page = is_numeric($get['page'] ?? null) ? max(1, (int) $get['page']) : 1;

        $classNames = array_column($this->ai->classes, 'name');
        $class = $str($get['class'] ?? null);
        if (!in_array($class, $classNames, true)) {
            $class = 'all';
        }

        $od = $str($get['od'] ?? null);
        if (!in_array($od, ['ya', 'tidak'], true)) {
            $od = 'all';
        }

        return [
            'period' => $period,
            'tab'    => $tab,
            'source' => $source,
            'page'   => $page,
            'class'  => $class,
            'od'     => $od,
        ];
    }

    public function build(array $params): array
    {
        $origin = $this->ai->dashboardSources[$params['source']];
        $originCfg = $this->ai->origins[$origin];

        $range  = $this->detections->getPeriodRange($params['period']);
        $stats  = $this->detections->getStats($origin, $range);

        return [
            'params'      => $params,
            'source'      => [
                'key'         => $params['source'],
                'origin'      => $origin,
                'label'       => $originCfg['label'],
                'official'    => $originCfg['official'],
                'description' => $originCfg['description'],
                'unit_label'  => $this->ai->unitLabel($originCfg['unit']),
            ],
            'period_info' => $range,
            'stats'       => $stats,
            'insights'    => $this->detections->getInsights($origin, $range, $stats),
            'chart'       => $this->detections->getChartData($origin, $range, $params['period']),
            'table'       => $this->detections->getPaginatedTable($origin, $range, [
                'class' => $params['class'],
                'od'    => $params['od'],
            ], $params['page']),
            'status'      => $this->status->getSourceStatus($origin),
            'filter_options' => [
                'class' => ['all' => 'Semua Kelas Model'] + array_column($this->ai->classes, 'label', 'name'),
                'od'    => ['all' => 'Semua', 'ya' => 'Over Dimension', 'tidak' => 'Bukan OD'],
            ],
            'source_options' => array_map(
                fn (string $o) => $this->ai->origins[$o]['short_label'],
                $this->ai->dashboardSources
            ),
            'camera'      => $this->ai->testCamera,
            'as_of'       => $range['end'],
        ];
    }
}
