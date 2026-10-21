<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use Config\AiIntegration;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Penyimpanan & query hasil AI.
 *
 * Semua query dashboard memakai satu scope (asal data + kamera uji + rentang
 * waktu) agar kartu, grafik, insight dan tabel menghitung dari himpunan yang sama.
 */
class AiDetectionModel extends Model
{
    protected $table            = 'ai_detections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'data_origin',
        'source_version',
        'result_unit',
        'event_id',
        'camera_source',
        'camera_name',
        'event_time',
        'received_time',
        'time_basis',
        'frame_time',
        'track_id',
        'class_index',
        'class_name',
        'indonesian_label',
        'is_over_dimension',
        'confidence',
        'confidence_raw',
        'detector_model',
        'classifier_model',
        'bbox_x1',
        'bbox_y1',
        'bbox_x2',
        'bbox_y2',
        'raw_metadata',
        'created_at',
    ];

    protected $useTimestamps = false;

    private AiIntegration $ai;

    protected function initialize(): void
    {
        $this->ai = config('AiIntegration');
    }

    /**
     * Simpan satu baris yang sudah divalidasi AiIngestValidator secara idempoten.
     *
     * @return array{status: string, id: int|null}  status: inserted | duplicate
     *
     * @throws DatabaseException bila database gagal (bukan duplikat)
     */
    public function insertValidated(array $row): array
    {
        $existing = $this->findByOriginEvent($row['data_origin'], $row['event_id']);
        if ($existing !== null) {
            return ['status' => 'duplicate', 'id' => (int) $existing['id']];
        }

        try {
            $id = $this->insert($row, true);
        } catch (DatabaseException $e) {
            // Balapan dua kiriman bersamaan: unique (data_origin, event_id) menolak yang kedua
            $existing = $this->findByOriginEvent($row['data_origin'], $row['event_id']);
            if ($existing !== null) {
                return ['status' => 'duplicate', 'id' => (int) $existing['id']];
            }

            throw $e;
        }

        if ($id === false) {
            throw new DatabaseException('Insert hasil AI gagal: ' . implode('; ', $this->errors()));
        }

        return ['status' => 'inserted', 'id' => (int) $id];
    }

    private function findByOriginEvent(string $origin, string $eventId): ?array
    {
        return $this->db->table($this->table)
            ->select('id')
            ->where('data_origin', $origin)
            ->where('event_id', $eventId)
            ->get()
            ->getRowArray();
    }

    /**
     * Rentang kalender WITA. 1 Hari = hari ini 00:00 s.d. sekarang;
     * 7/30 Hari = hari ini beserta 6/29 tanggal sebelumnya s.d. sekarang.
     */
    public function getPeriodRange(int $period, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar'));
        $startDay = $now->setTime(0, 0)->modify('-' . ($period - 1) . ' days');

        $range = $period === 1
            ? $now->format('d M Y') . ', 00:00 - ' . $now->format('H:i') . ' WITA'
            : $startDay->format('d M') . ' - ' . $now->format('d M Y') . ' (s.d. ' . $now->format('H:i') . ' WITA)';

        return [
            'period' => $period,
            'start'  => $startDay->format('Y-m-d H:i:s'),
            'end'    => $now->format('Y-m-d H:i:s'),
            'title'  => $period . ' Hari',
            'range'  => $range,
        ];
    }

    private function scoped(string $origin, array $range, ?string $cameraSource = null): BaseBuilder
    {
        // Builder baru per query (Model::builder() memakai satu instance bersama)
        $builder = $this->db->table($this->table)
            ->where('data_origin', $origin)
            ->where('event_time >=', $range['start'])
            ->where('event_time <=', $range['end']);

        if ($cameraSource !== null && $cameraSource !== 'all') {
            $builder->where('camera_source', $cameraSource);
        } else {
            $allowedSources = array_keys($this->ai->testCameras);
            if (!empty($allowedSources)) {
                $builder->whereIn('camera_source', $allowedSources);
            }
        }

        return $builder;
    }

    /**
     * Ringkasan per kelas dengan agregasi SQL.
     */
    public function getStats(string $origin, array $range): array
    {
        $rows = $this->scoped($origin, $range)
            ->select('class_index, COUNT(*) AS cnt, MAX(received_time) AS last_received')
            ->groupBy('class_index')
            ->get()
            ->getResultArray();

        $byClass = array_fill_keys(array_keys($this->ai->classes), 0);
        $total = 0;
        $lastReceived = null;
        foreach ($rows as $r) {
            $idx = (int) $r['class_index'];
            if (array_key_exists($idx, $byClass)) {
                $byClass[$idx] = (int) $r['cnt'];
            }
            $total += (int) $r['cnt'];
            if ($lastReceived === null || $r['last_received'] > $lastReceived) {
                $lastReceived = $r['last_received'];
            }
        }

        $classes = [];
        $odCount = 0;
        foreach ($this->ai->classes as $idx => $class) {
            $count = $byClass[$idx];
            $isOd  = $this->ai->isOdClass($class['name']);
            if ($isOd) {
                $odCount += $count;
            }
            $classes[] = [
                'index' => $idx,
                'name'  => $class['name'],
                'label' => $class['label'],
                'color' => $class['color'],
                'is_od' => $isOd,
                'count' => $count,
                'pct'   => $this->pct($count, $total),
            ];
        }

        return [
            'total'         => $total,
            'od_count'      => $odCount,
            'od_pct'        => $this->pct($odCount, $total),
            'classes'       => $classes,
            'last_received' => $lastReceived,
            'denominator'   => 'total hasil tercatat pada periode & sumber terpilih',
        ];
    }

    /**
     * Insight periode: hari & jam dengan indikasi OD terbanyak, serta kelas terbanyak.
     * Seri imbang dipecah ke waktu paling awal agar stabil.
     */
    public function getInsights(string $origin, array $range, array $stats): array
    {
        $odNames = $this->ai->odClassNames;

        $day = $this->scoped($origin, $range)
            ->select('DATE(event_time) AS dt, COUNT(*) AS cnt')
            ->whereIn('class_name', $odNames)
            ->groupBy('DATE(event_time)')
            ->orderBy('cnt', 'DESC')
            ->orderBy('dt', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();

        $hour = $this->scoped($origin, $range)
            ->select('HOUR(event_time) AS hr, COUNT(*) AS cnt')
            ->whereIn('class_name', $odNames)
            ->groupBy('HOUR(event_time)')
            ->orderBy('cnt', 'DESC')
            ->orderBy('hr', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();

        $top = null;
        foreach ($stats['classes'] as $class) {
            if ($class['count'] > 0 && ($top === null || $class['count'] > $top['count'])) {
                $top = $class;
            }
        }

        return [
            'peak_day' => $day
                ? ['title' => (new DateTimeImmutable($day['dt']))->format('d M Y'), 'subtitle' => $day['cnt'] . ' indikasi OD pada tanggal ini']
                : ['title' => 'Belum ada indikasi OD', 'subtitle' => 'Tidak ada hasil kelas OD pada periode ini'],
            'peak_hour' => $hour
                ? ['title' => sprintf('%02d:00 - %02d:00 WITA', (int) $hour['hr'], ((int) $hour['hr'] + 1) % 24), 'subtitle' => $hour['cnt'] . ' indikasi OD (akumulasi jam dalam periode)']
                : ['title' => 'Belum ada indikasi OD', 'subtitle' => 'Tidak ada hasil kelas OD pada periode ini'],
            'top_class' => $top
                ? ['title' => $top['label'], 'subtitle' => $top['count'] . ' hasil (' . $top['pct'] . '% dari total)']
                : ['title' => 'Belum ada hasil', 'subtitle' => 'Belum ada hasil tercatat pada periode ini'],
        ];
    }

    /**
     * Seri grafik per kelas. Per jam untuk 1 Hari, per tanggal untuk 7/30 Hari.
     * Nilai null = waktu belum berlangsung atau tidak tercakup pemantauan.
     */
    public function getChartData(string $origin, array $range, int $period): array
    {
        $hourly = $period === 1;
        $start  = new DateTimeImmutable($range['start']);
        $end    = new DateTimeImmutable($range['end']);

        // Bucket waktu
        $buckets = [];
        $labels  = [];
        if ($hourly) {
            for ($h = 0; $h < 24; $h++) {
                $buckets[] = $start->setTime($h, 0);
                $labels[]  = sprintf('%02d:00', $h);
            }
        } else {
            for ($d = 0; $d < $period; $d++) {
                $buckets[] = $start->modify("+{$d} days");
                $labels[]  = $buckets[$d]->format($period === 7 ? 'd M' : 'd/m');
            }
        }
        $keyOf = static fn (DateTimeImmutable $b): string => $hourly ? $b->format('G') : $b->format('Y-m-d');

        $bucketExpr = $hourly ? 'HOUR(event_time)' : 'DATE(event_time)';
        $rows = $this->scoped($origin, $range)
            ->select("{$bucketExpr} AS bucket, class_index, COUNT(*) AS cnt")
            ->groupBy([$bucketExpr, 'class_index'])
            ->get()
            ->getResultArray();

        $counts = [];
        foreach ($rows as $r) {
            $counts[(string) $r['bucket']][(int) $r['class_index']] = (int) $r['cnt'];
        }

        $coverage = $this->getCoverage($origin, $range, $hourly);

        $series = [];
        foreach ($this->ai->classes as $idx => $class) {
            $data = [];
            foreach ($buckets as $b) {
                $key = $keyOf($b);
                if ($b > $end) {
                    $data[] = null; // belum berlangsung
                } elseif ($coverage['known'] && empty($coverage['minutes'][$key]) && empty($counts[$key])) {
                    $data[] = null; // tidak dipantau
                } else {
                    $data[] = $counts[$key][$idx] ?? 0;
                }
            }
            $series[] = [
                'key'   => $class['name'],
                'label' => $class['label'],
                'color' => $class['color'],
                'data'  => $data,
            ];
        }

        return [
            'mode'           => $hourly ? 'hourly' : 'daily',
            'labels'         => $labels,
            'series'         => $series,
            'coverage_known' => $coverage['known'],
            'title_line'     => $hourly ? 'Tren Hasil per Jam (Hari Ini)' : 'Tren Hasil Harian (' . $range['title'] . ')',
            'title_bar'      => $hourly ? 'Komposisi Kelas per Jam' : 'Komposisi Kelas Harian',
            'note'           => $coverage['known']
                ? 'Celah pada grafik = waktu belum berlangsung atau sumber tidak sedang memantau.'
                : 'Cakupan pemantauan sumber ini tidak tercatat; nilai 0 belum tentu berarti tidak ada kendaraan.',
        ];
    }

    /**
     * Menit terpantau per bucket dari heartbeat aktif.
     *
     * @return array{known: bool, minutes: array<string, int>}
     */
    private function getCoverage(string $origin, array $range, bool $hourly): array
    {
        $db = $this->db;
        $allowedSources = array_keys($this->ai->testCameras);

        $ever = $db->table('ai_monitoring_coverage')
            ->where('data_origin', $origin)
            ->whereIn('camera_source', $allowedSources)
            ->countAllResults();
        if ($ever === 0) {
            return ['known' => false, 'minutes' => []];
        }

        $expr = $hourly ? 'HOUR(minute_start)' : 'DATE(minute_start)';
        $rows = $db->table('ai_monitoring_coverage')
            ->select("{$expr} AS bucket, COUNT(DISTINCT minute_start) AS minutes")
            ->where('data_origin', $origin)
            ->whereIn('camera_source', $allowedSources)
            ->where('minute_start >=', $range['start'])
            ->where('minute_start <=', $range['end'])
            ->groupBy($expr)
            ->get()
            ->getResultArray();

        $minutes = [];
        foreach ($rows as $r) {
            $minutes[(string) $r['bucket']] = (int) $r['minutes'];
        }

        return ['known' => true, 'minutes' => $minutes];
    }

    /**
     * Tabel hasil terpaginasi di server.
     *
     * @param array{class: string, od: string} $filters  class: nama kelas|all, od: ya|tidak|all
     */
    public function getPaginatedTable(string $origin, array $range, array $filters, int $page, int $perPage = 10): array
    {
        $globalTotal = $this->scoped($origin, $range)->countAllResults();

        $cameraFilter = $filters['camera'] ?? 'all';
        $builder = $this->scoped($origin, $range, $cameraFilter);
        $hasFilter = false;
        if ($cameraFilter !== 'all') {
            $hasFilter = true;
        }
        if ($filters['class'] !== 'all') {
            $builder->where('class_name', $filters['class']);
            $hasFilter = true;
        }
        if ($filters['od'] === 'ya') {
            $builder->whereIn('class_name', $this->ai->odClassNames);
            $hasFilter = true;
        } elseif ($filters['od'] === 'tidak') {
            $builder->whereNotIn('class_name', $this->ai->odClassNames);
            $hasFilter = true;
        }

        $filteredTotal = (clone $builder)->countAllResults();
        $maxPages = 20;
        $maxResults = $maxPages * $perPage; // Maksimal 200 hasil (20 halaman x 10 baris)
        $displayTotal = min($filteredTotal, $maxResults);
        $totalPages = min($maxPages, max(1, (int) ceil($filteredTotal / $perPage)));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $rows = $builder
            ->select('id, event_id, event_time, time_basis, received_time, camera_source, camera_name, class_index, class_name, confidence, confidence_raw, track_id, bbox_x1, bbox_y1, bbox_x2, bbox_y2')
            ->orderBy('event_time', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $data = [];
        foreach ($rows as $r) {
            $class = $this->ai->classByIndex((int) $r['class_index']);
            $dt = new DateTimeImmutable($r['event_time']);
            $confidence = $r['confidence_raw'] !== null ? (float) $r['confidence_raw'] : (float) $r['confidence'];
            $isOd = $class !== null && $this->ai->isOdClass($class['name']);
            $hasBbox = $r['bbox_x1'] !== null && $r['bbox_x2'] !== null;
            $hasImage = file_exists(FCPATH . 'uploads/detections/' . $r['event_id'] . '.jpg');
            $camMeta = $this->ai->getCameraBySource($r['camera_source'] ?? '');

            $data[] = [
                'id'             => 'DET-' . str_pad((string) $r['id'], 6, '0', STR_PAD_LEFT),
                'event_id'       => $r['event_id'],
                'date_formatted' => $dt->format('d M Y'),
                'time'           => $dt->format('H:i:s') . ' WITA',
                'time_basis'     => $this->timeBasisLabel($r['time_basis']),
                'camera_source'  => $r['camera_source'] ?? '',
                'camera_name'    => $camMeta['name'] ?? ($r['camera_name'] ?: 'Kamera AI'),
                'camera_code'    => $camMeta['code'] ?? 'ATCS',
                'class_name'     => $r['class_name'],
                'class_label'    => $class['label'] ?? $r['class_name'],
                'confidence'     => number_format($confidence * 100, 1, ',', '.') . '%',
                'confidence_raw' => $confidence,
                'is_od'          => $isOd,
                'od_label'       => $isOd ? 'Over Dimension' : 'Bukan OD',
                'bbox'           => $hasBbox ? [(int) $r['bbox_x1'], (int) $r['bbox_y1'], (int) $r['bbox_x2'], (int) $r['bbox_y2']] : null,
                'bbox_str'       => $hasBbox ? "[{$r['bbox_x1']}, {$r['bbox_y1']}, {$r['bbox_x2']}, {$r['bbox_y2']}]" : null,
                'has_image'      => $hasImage,
                'image_url'      => $hasImage ? base_url('uploads/detections/' . $r['event_id'] . '.jpg') : null,
            ];
        }

        return [
            'data'              => $data,
            'total'             => $displayTotal,
            'real_total'        => $filteredTotal,
            'max_pages'         => $maxPages,
            'global_total'      => $globalTotal,
            'page'              => $page,
            'per_page'          => $perPage,
            'total_pages'       => $totalPages,
            'from'              => $displayTotal === 0 ? 0 : $offset + 1,
            'to'                => min($offset + $perPage, $displayTotal),
            'has_prev'          => $page > 1,
            'has_next'          => $page < $totalPages,
            'has_active_filter' => $hasFilter,
        ];
    }

    /**
     * Ambil deteksi terbaru untuk cuplikan visual di dashboard.
     */
    public function getRecentDetections(string $origin, array $range, int $limit = 4): array
    {
        $rows = $this->scoped($origin, $range)
            ->select('id, event_id, event_time, time_basis, camera_source, camera_name, class_index, class_name, confidence, confidence_raw, track_id, bbox_x1, bbox_y1, bbox_x2, bbox_y2')
            ->orderBy('event_time', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $data = [];
        foreach ($rows as $r) {
            $class = $this->ai->classByIndex((int) $r['class_index']);
            $dt = new DateTimeImmutable($r['event_time']);
            $confidence = $r['confidence_raw'] !== null ? (float) $r['confidence_raw'] : (float) $r['confidence'];
            $isOd = $class !== null && $this->ai->isOdClass($class['name']);
            $hasBbox = $r['bbox_x1'] !== null && $r['bbox_x2'] !== null;
            $hasImage = file_exists(FCPATH . 'uploads/detections/' . $r['event_id'] . '.jpg');
            $camMeta = $this->ai->getCameraBySource($r['camera_source'] ?? '');

            $data[] = [
                'id'             => 'DET-' . str_pad((string) $r['id'], 6, '0', STR_PAD_LEFT),
                'event_id'       => $r['event_id'],
                'date_formatted' => $dt->format('d M Y'),
                'time'           => $dt->format('H:i:s') . ' WITA',
                'time_basis'     => $this->timeBasisLabel($r['time_basis']),
                'camera_source'  => $r['camera_source'] ?? '',
                'camera_name'    => $camMeta['name'] ?? ($r['camera_name'] ?: 'Kamera AI'),
                'camera_code'    => $camMeta['code'] ?? 'ATCS',
                'class_name'     => $r['class_name'],
                'class_label'    => $class['label'] ?? $r['class_name'],
                'confidence'     => number_format($confidence * 100, 1, ',', '.') . '%',
                'confidence_raw' => $confidence,
                'is_od'          => $isOd,
                'od_label'       => $isOd ? 'Over Dimension' : 'Bukan OD',
                'bbox'           => $hasBbox ? [(int) $r['bbox_x1'], (int) $r['bbox_y1'], (int) $r['bbox_x2'], (int) $r['bbox_y2']] : null,
                'bbox_str'       => $hasBbox ? "[{$r['bbox_x1']}, {$r['bbox_y1']}, {$r['bbox_x2']}, {$r['bbox_y2']}]" : null,
                'has_image'      => $hasImage,
                'image_url'      => $hasImage ? base_url('uploads/detections/' . $r['event_id'] . '.jpg') : null,
            ];
        }

        return $data;
    }

    private function timeBasisLabel(string $basis): string
    {
        return match ($basis) {
            'frame'    => 'waktu frame',
            'observed' => 'waktu diamati sumber',
            default    => 'waktu diterima server',
        };
    }

    private function pct(int $part, int $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }
}
