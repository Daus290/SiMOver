<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\AiIntegration;

/**
 * Status pengirim hasil AI berdasarkan heartbeat yang benar-benar diterima.
 * Nilai "active" yang tersimpan lama tidak dianggap bukti pengirim masih hidup.
 */
class AiWorkerStatusModel extends Model
{
    protected $table            = 'ai_worker_status';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'worker_name',
        'data_origin',
        'camera_source',
        'status',
        'fps',
        'last_inference_time_ms',
        'last_frame_time',
        'last_heartbeat',
        'metadata',
        'updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * Simpan heartbeat tervalidasi. Nama pengirim & asal berasal dari token, bukan payload.
     */
    public function recordHeartbeat(string $workerName, string $origin, array $row): void
    {
        $now = date('Y-m-d H:i:s');
        $data = $row + [
            'worker_name'    => $workerName,
            'data_origin'    => $origin,
            'last_heartbeat' => $now,
            'updated_at'     => $now,
        ];

        $existing = $this->db->table($this->table)->select('id')->where('worker_name', $workerName)->get()->getRowArray();
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }

        // Cakupan pemantauan: hanya menit dengan heartbeat aktif
        if ($row['status'] === 'active') {
            $this->db->table('ai_monitoring_coverage')->ignore(true)->insert([
                'data_origin'   => $origin,
                'camera_source' => $row['camera_source'],
                'minute_start'  => date('Y-m-d H:i:00'),
            ]);
        }
    }

    /**
     * Ringkasan status satu asal data untuk UI.
     *
     * state: belum_terhubung | aktif | menyambung | berhenti | galat | kedaluwarsa
     */
    public function getSourceStatus(string $origin): array
    {
        /** @var AiIntegration $ai */
        $ai = config('AiIntegration');

        $row = $this->db->table($this->table)
            ->where('data_origin', $origin)
            ->orderBy('last_heartbeat', 'DESC')
            ->get(1)
            ->getRowArray();

        $lastResult = $this->db->table('ai_detections')
            ->selectMax('received_time', 'last_received')
            ->where('data_origin', $origin)
            ->get()
            ->getRowArray();

        $status = [
            'origin'           => $origin,
            'origin_label'     => $ai->origins[$origin]['label'] ?? $origin,
            'official'         => (bool) ($ai->origins[$origin]['official'] ?? false),
            'state'            => 'belum_terhubung',
            'label'            => 'Belum terhubung',
            'is_live'          => false,
            'last_heartbeat'   => null,
            'heartbeat_age_sec' => null,
            'fps'              => null,
            'last_result_time' => $lastResult['last_received'] ?? null,
        ];

        if (!$row) {
            return $status;
        }

        $age = time() - strtotime($row['last_heartbeat']);
        $status['last_heartbeat']    = $row['last_heartbeat'];
        $status['heartbeat_age_sec'] = $age;

        if ($age > $ai->staleAfterSec) {
            $status['state'] = 'kedaluwarsa';
            $status['label'] = 'Tidak ada pembaruan sejak ' . date('d M Y H:i', strtotime($row['last_heartbeat'])) . ' WITA';

            return $status;
        }

        $status['fps'] = (float) $row['fps'];
        [$status['state'], $status['label']] = match ($row['status']) {
            'active'                    => ['aktif', 'Aktif'],
            'connecting', 'reconnecting' => ['menyambung', 'Menyambung ke stream'],
            'stopped'                   => ['berhenti', 'Berhenti'],
            default                     => ['galat', 'Galat dilaporkan pengirim'],
        };
        $status['is_live'] = $status['state'] === 'aktif';

        return $status;
    }
}
