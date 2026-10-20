<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AiDashboardService;
use App\Libraries\AiIngestValidator;
use App\Models\AiDetectionModel;
use App\Models\AiWorkerStatusModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\AiIntegration;
use Throwable;

/**
 * API integrasi AI.
 *
 * Penulisan (detections, heartbeat, overlay) hanya untuk pengirim tepercaya
 * bertoken dari environment. Asal data ditentukan dari token, bukan payload.
 * Pembacaan (status, overlay, dashboard-data) publik dan read-only.
 */
class AiApiController extends BaseController
{
    protected AiIntegration $ai;
    protected string $overlayCacheFile;

    public function __construct()
    {
        $this->ai = config('AiIntegration');
        $this->overlayCacheFile = WRITEPATH . 'cache/ai_overlay_latest.json';
    }

    /**
     * Cocokkan token request dengan pengirim terdaftar.
     *
     * @return array{origin: string, sender: string}|ResponseInterface
     */
    protected function resolveSender(): array|ResponseInterface
    {
        $token = trim($this->request->getHeaderLine('X-AI-Worker-Token'));
        if ($token === '' && preg_match('/^Bearer\s+(\S+)$/i', $this->request->getHeaderLine('Authorization'), $m)) {
            $token = $m[1];
        }

        $configured = 0;
        foreach ($this->ai->senders as $envKey => $sender) {
            if (!empty($sender['testing_only']) && ENVIRONMENT !== 'testing') {
                continue;
            }
            $expected = (string) env($envKey, '');
            if ($expected === '') {
                continue;
            }
            $configured++;
            if ($token !== '' && hash_equals($expected, $token)) {
                return ['origin' => $sender['origin'], 'sender' => $sender['sender']];
            }
        }

        if ($configured === 0) {
            return $this->error(503, 'Ingestion AI belum dikonfigurasi di server.');
        }

        return $this->error(401, 'Akses ditolak: token pengirim tidak valid.');
    }

    /**
     * @return array|ResponseInterface  isi JSON yang sudah di-decode
     */
    protected function readJsonBody(): array|ResponseInterface
    {
        $body = (string) $this->request->getBody();
        if (strlen($body) > $this->ai->maxBodyBytes) {
            return $this->error(413, 'Payload melebihi ' . $this->ai->maxBodyBytes . ' byte.');
        }

        $json = json_decode($body, true);
        if (!is_array($json) || $json === []) {
            return $this->error(400, 'Payload JSON tidak valid.');
        }

        return $json;
    }

    /**
     * POST /api/ai/detections  (satu event atau array event)
     */
    public function postDetection(): ResponseInterface
    {
        $sender = $this->resolveSender();
        if ($sender instanceof ResponseInterface) {
            return $sender;
        }
        $json = $this->readJsonBody();
        if ($json instanceof ResponseInterface) {
            return $json;
        }

        $events = array_is_list($json) ? $json : [$json];
        if (count($events) > $this->ai->maxBatchSize) {
            return $this->error(413, 'Maksimal ' . $this->ai->maxBatchSize . ' event per request.');
        }

        $validator = new AiIngestValidator($this->ai);
        $model     = new AiDetectionModel();
        $results   = [];
        $counts    = ['inserted' => 0, 'duplicate' => 0, 'rejected' => 0, 'error' => 0];

        foreach ($events as $i => $event) {
            $eventId = is_array($event) && is_string($event['event_id'] ?? null) ? $event['event_id'] : null;
            $check = $validator->validateEvent($event, $sender['origin']);

            if (!$check['ok']) {
                $results[] = ['index' => $i, 'event_id' => $eventId, 'status' => 'rejected', 'errors' => $check['errors']];
                $counts['rejected']++;

                continue;
            }

            try {
                $saved = $model->insertValidated($check['row']);
                $results[] = ['index' => $i, 'event_id' => $eventId, 'status' => $saved['status'], 'id' => $saved['id']];
                $counts[$saved['status']]++;
            } catch (DatabaseException $e) {
                log_message('error', 'Ingestion AI gagal disimpan: ' . $e->getMessage());
                $results[] = ['index' => $i, 'event_id' => $eventId, 'status' => 'error', 'errors' => ['penyimpanan gagal, kirim ulang nanti']];
                $counts['error']++;
            }
        }

        // 503 bila ada kegagalan server agar pengirim mencoba ulang (event_id sama aman diulang)
        $httpStatus = $counts['error'] > 0 ? 503 : ($counts['rejected'] === count($events) ? 422 : 200);

        return $this->response->setStatusCode($httpStatus)->setJSON([
            'success'   => $counts['rejected'] === 0 && $counts['error'] === 0,
            'origin'    => $sender['origin'],
            'processed' => count($events),
            'summary'   => $counts,
            'results'   => $results,
        ]);
    }

    /**
     * POST /api/ai/heartbeat
     */
    public function postHeartbeat(): ResponseInterface
    {
        $sender = $this->resolveSender();
        if ($sender instanceof ResponseInterface) {
            return $sender;
        }
        $json = $this->readJsonBody();
        if ($json instanceof ResponseInterface) {
            return $json;
        }

        $check = (new AiIngestValidator($this->ai))->validateHeartbeat($json);
        if (!$check['ok']) {
            return $this->error(422, 'Heartbeat tidak valid.', $check['errors']);
        }

        try {
            (new AiWorkerStatusModel())->recordHeartbeat($sender['sender'], $sender['origin'], $check['row']);
        } catch (Throwable $e) {
            log_message('error', 'Heartbeat AI gagal disimpan: ' . $e->getMessage());

            return $this->error(503, 'Heartbeat gagal disimpan.');
        }

        return $this->response->setJSON(['success' => true, 'origin' => $sender['origin']]);
    }

    /**
     * POST /api/ai/overlay  (metadata kotak sementara, tidak disimpan ke tabel hasil)
     */
    public function postOverlay(): ResponseInterface
    {
        $sender = $this->resolveSender();
        if ($sender instanceof ResponseInterface) {
            return $sender;
        }
        $json = $this->readJsonBody();
        if ($json instanceof ResponseInterface) {
            return $json;
        }
        if (($json['camera_source'] ?? null) !== $this->ai->testCamera['source_code']) {
            return $this->error(422, 'camera_source tidak terdaftar.');
        }

        $boxes = is_array($json['boxes'] ?? null) ? array_slice($json['boxes'], 0, 30) : [];
        $record = [
            'origin'        => $sender['origin'],
            'camera_source' => $json['camera_source'],
            'frame_time'    => is_string($json['frame_time'] ?? null) ? $json['frame_time'] : null,
            'frame_width'   => is_int($json['frame_width'] ?? null) ? $json['frame_width'] : null,
            'frame_height'  => is_int($json['frame_height'] ?? null) ? $json['frame_height'] : null,
            'boxes'         => $boxes,
            'received_at'   => microtime(true),
        ];
        @file_put_contents($this->overlayCacheFile, json_encode($record), LOCK_EX);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * GET /api/ai/overlay
     * Kotak hanya dikembalikan bila berasal dari sumber koordinat resmi, segar,
     * dan untuk kamera uji. Selain itu kotak dibuang (tidak pernah dikirim basi).
     */
    public function getOverlay(): ResponseInterface
    {
        $response = [
            'available'   => false,
            'reason'      => 'Kotak deteksi belum tersedia: core Rizkiya belum mengeluarkan koordinat dan waktu frame.',
            'boxes'       => [],
            'camera_code' => $this->ai->testCamera['code'],
        ];

        $data = is_file($this->overlayCacheFile) ? json_decode((string) @file_get_contents($this->overlayCacheFile), true) : null;
        $originAllowed = is_array($data) && in_array($data['origin'] ?? null, $this->ai->overlayOrigins, true);

        if ($originAllowed) {
            $age = microtime(true) - (float) ($data['received_at'] ?? 0);
            $fresh = $age <= $this->ai->overlayFreshSec;
            $cameraOk = ($data['camera_source'] ?? null) === $this->ai->testCamera['source_code'];

            if ($fresh && $cameraOk && !empty($data['frame_time']) && !empty($data['frame_width']) && !empty($data['frame_height'])) {
                $response = [
                    'available'    => true,
                    'reason'       => null,
                    'origin'       => $data['origin'],
                    'frame_time'   => $data['frame_time'],
                    'frame_width'  => $data['frame_width'],
                    'frame_height' => $data['frame_height'],
                    'boxes'        => $data['boxes'],
                    'age_seconds'  => round($age, 2),
                    'camera_code'  => $this->ai->testCamera['code'],
                ];
            } else {
                $response['reason'] = 'Metadata kotak kedaluwarsa atau tidak lengkap.';
            }
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setJSON($response);
    }

    /**
     * GET /api/ai/status  status semua sumber publik
     */
    public function getStatus(): ResponseInterface
    {
        $model = new AiWorkerStatusModel();
        $sources = [];
        foreach ($this->ai->dashboardSources as $key => $origin) {
            $sources[$key] = $model->getSourceStatus($origin);
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setJSON(['success' => true, 'server_time' => date('Y-m-d H:i:s'), 'sources' => $sources]);
    }

    /**
     * GET /api/ai/dashboard-data  data polling dashboard (kontrak sama dengan halaman)
     */
    public function getDashboardData(): ResponseInterface
    {
        $service = new AiDashboardService();
        $params = $service->normalizeParams($this->request->getGet() ?? []);

        return $this->response
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setJSON($service->build($params));
    }

    protected function error(int $code, string $message, array $errors = []): ResponseInterface
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->response->setStatusCode($code)->setJSON($body);
    }
}
