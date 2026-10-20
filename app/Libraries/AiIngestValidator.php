<?php

namespace App\Libraries;

use Config\AiIntegration;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Validasi & normalisasi payload hasil AI dari pengirim tepercaya.
 *
 * Validator tidak mengubah keputusan prediksi: kelas dan confidence disimpan
 * apa adanya. Label tampilan dan indikasi OD diturunkan dari kontrak
 * AiIntegration, bukan diterima dari payload.
 */
class AiIngestValidator
{
    private const EVENT_ID_PATTERN = '/^[A-Za-z0-9_.:-]{6,64}$/';
    private const VERSION_PATTERN  = '/^[A-Za-z0-9_.:+-]{1,64}$/';
    private const RESULT_UNITS     = ['frame_sample', 'track_event', 'unknown'];

    private AiIntegration $config;
    private DateTimeZone $tz;

    public function __construct(?AiIntegration $config = null)
    {
        $this->config = $config ?? config('AiIntegration');
        $this->tz     = new DateTimeZone('Asia/Makassar');
    }

    /**
     * @return array{ok: bool, errors: list<string>, row: array<string, mixed>|null}
     */
    public function validateEvent(mixed $event, string $origin, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', $this->tz);
        $errors = [];

        if (!is_array($event)) {
            return $this->fail(['event harus berupa objek JSON']);
        }

        // 1. Identitas hasil dari sumber
        $eventId = $event['event_id'] ?? null;
        if (!is_string($eventId) || !preg_match(self::EVENT_ID_PATTERN, $eventId)) {
            $errors[] = 'event_id wajib 6-64 karakter [A-Za-z0-9_.:-]';
        }

        // 2. Kamera harus kamera uji yang terdaftar
        $camera = $event['camera_source'] ?? null;
        if ($camera !== $this->config->testCamera['source_code']) {
            $errors[] = 'camera_source tidak terdaftar';
        }

        // 3. Kelas: indeks dan/atau nama harus cocok dengan mapping tepercaya
        $classIndex = $event['class_index'] ?? null;
        $className  = $event['class_name'] ?? null;
        if ($classIndex !== null && !is_int($classIndex)) {
            $errors[] = 'class_index harus integer';
            $classIndex = null;
        }
        if ($className !== null && !is_string($className)) {
            $errors[] = 'class_name harus string';
            $className = null;
        }
        if ($classIndex === null && $className === null) {
            $errors[] = 'class_index atau class_name wajib diisi';
        } elseif ($classIndex !== null) {
            $class = $this->config->classByIndex($classIndex);
            if ($class === null) {
                $errors[] = 'class_index di luar 0-4';
            } elseif ($className !== null && $className !== $class['name']) {
                $errors[] = 'class_name tidak cocok dengan class_index';
            } else {
                $className = $class['name'];
            }
        } else {
            $classIndex = $this->config->classIndexByName($className);
            if ($classIndex === null) {
                $errors[] = 'class_name tidak dikenal';
            }
        }

        // 4. Confidence apa adanya dari sumber (tidak di-softmax / dibulatkan ulang)
        $confidence = $event['confidence'] ?? null;
        if (!is_int($confidence) && !is_float($confidence)) {
            $errors[] = 'confidence wajib angka';
        } elseif ($confidence < 0 || $confidence > 1 || is_nan((float) $confidence)) {
            $errors[] = 'confidence harus 0-1';
        }

        // 5. Waktu: frame_time (waktu frame) atau observed_time (waktu hasil diamati sumber)
        $frameTime    = $this->parseTime($event['frame_time'] ?? null, 'frame_time', $now, $errors);
        $observedTime = $this->parseTime($event['observed_time'] ?? ($event['event_time'] ?? null), 'observed_time', $now, $errors);

        // 6. Track ID opsional (hanya bila sumber menyediakannya)
        $trackId = $event['track_id'] ?? null;
        if ($trackId !== null && (!is_int($trackId) || $trackId < 0)) {
            $errors[] = 'track_id harus integer >= 0';
        }

        // 7. Bounding box opsional, [x1,y1,x2,y2] atau bbox_x1..bbox_y2
        $bbox = $this->parseBbox($event, $errors);

        // 8. Versi sumber & satuan hasil
        $sourceVersion = $event['source_version'] ?? null;
        if ($sourceVersion !== null && (!is_string($sourceVersion) || !preg_match(self::VERSION_PATTERN, $sourceVersion))) {
            $errors[] = 'source_version tidak valid';
        }
        $unit = $event['result_unit'] ?? ($this->config->origins[$origin]['unit'] ?? 'unknown');
        if (!in_array($unit, self::RESULT_UNITS, true)) {
            $errors[] = 'result_unit harus frame_sample, track_event atau unknown';
        }

        // 9. Metadata mentah terbatas
        $rawMetadata = null;
        if (isset($event['raw_metadata'])) {
            if (!is_array($event['raw_metadata'])) {
                $errors[] = 'raw_metadata harus objek';
            } else {
                $rawMetadata = json_encode($event['raw_metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($rawMetadata === false || strlen($rawMetadata) > $this->config->maxMetadataBytes) {
                    $errors[] = 'raw_metadata melebihi ' . $this->config->maxMetadataBytes . ' byte';
                }
            }
        }

        if ($errors !== []) {
            return $this->fail($errors);
        }

        $class    = $this->config->classByIndex($classIndex);
        $received = $now->format('Y-m-d H:i:s');

        if ($frameTime !== null) {
            $eventTime = $frameTime;
            $basis     = 'frame';
        } elseif ($observedTime !== null) {
            $eventTime = $observedTime;
            $basis     = 'observed';
        } else {
            $eventTime = $received;
            $basis     = 'received';
        }

        return [
            'ok'     => true,
            'errors' => [],
            'row'    => [
                'data_origin'       => $origin,
                'source_version'    => $sourceVersion,
                'result_unit'       => $unit,
                'event_id'          => $eventId,
                'camera_source'     => $camera,
                'camera_name'       => $this->config->testCamera['name'],
                'event_time'        => $eventTime,
                'received_time'     => $received,
                'time_basis'        => $basis,
                'frame_time'        => $frameTime,
                'track_id'          => $trackId,
                'class_index'       => $classIndex,
                'class_name'        => $class['name'],
                'indonesian_label'  => $class['label'],
                'is_over_dimension' => $this->config->isOdClass($class['name']) ? 1 : 0,
                'confidence'        => round((float) $confidence, 4),
                'confidence_raw'    => (float) $confidence,
                'detector_model'    => $this->shortString($event['detector_model'] ?? null) ?? '',
                'classifier_model'  => $this->shortString($event['classifier_model'] ?? null) ?? '',
                'bbox_x1'           => $bbox[0] ?? null,
                'bbox_y1'           => $bbox[1] ?? null,
                'bbox_x2'           => $bbox[2] ?? null,
                'bbox_y2'           => $bbox[3] ?? null,
                'raw_metadata'      => $rawMetadata,
                'created_at'        => $received,
            ],
        ];
    }

    /**
     * Validasi heartbeat. Nama pengirim & asal ditentukan server, bukan payload.
     *
     * @return array{ok: bool, errors: list<string>, row: array<string, mixed>|null}
     */
    public function validateHeartbeat(mixed $payload, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', $this->tz);
        $errors = [];

        if (!is_array($payload)) {
            return $this->fail(['heartbeat harus berupa objek JSON']);
        }

        $status = $payload['status'] ?? null;
        if (!in_array($status, ['active', 'connecting', 'reconnecting', 'stopped', 'error'], true)) {
            $errors[] = 'status tidak dikenal';
        }
        if (($payload['camera_source'] ?? null) !== $this->config->testCamera['source_code']) {
            $errors[] = 'camera_source tidak terdaftar';
        }

        $fps = $payload['fps'] ?? 0;
        if ((!is_int($fps) && !is_float($fps)) || $fps < 0 || $fps > 1000) {
            $errors[] = 'fps harus angka 0-1000';
        }
        $latency = $payload['last_inference_time_ms'] ?? 0;
        if ((!is_int($latency) && !is_float($latency)) || $latency < 0 || $latency > 9999) {
            $errors[] = 'last_inference_time_ms harus angka 0-9999';
        }
        $frameTime = $this->parseTime($payload['last_frame_time'] ?? null, 'last_frame_time', $now, $errors);

        if ($errors !== []) {
            return $this->fail($errors);
        }

        return [
            'ok'     => true,
            'errors' => [],
            'row'    => [
                'camera_source'          => $payload['camera_source'],
                'status'                 => $status,
                'fps'                    => round((float) $fps, 2),
                'last_inference_time_ms' => round((float) $latency, 2),
                'last_frame_time'        => $frameTime,
            ],
        ];
    }

    /**
     * Terima 'Y-m-d H:i:s' (WITA) atau ISO 8601 berzona. Hasil dalam WITA.
     */
    private function parseTime(mixed $value, string $field, DateTimeImmutable $now, array &$errors): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || strlen($value) > 40) {
            $errors[] = "{$field} tidak valid";

            return null;
        }

        $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $this->tz);
        if ($dt === false || $dt->format('Y-m-d H:i:s') !== $value) {
            $dt = DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $value)
                ?: DateTimeImmutable::createFromFormat(DATE_RFC3339, $value);
        }
        if ($dt === false) {
            $errors[] = "{$field} harus 'Y-m-d H:i:s' (WITA) atau ISO 8601 berzona";

            return null;
        }

        $dt = $dt->setTimezone($this->tz);
        $earliest = $now->modify('-' . $this->config->maxPastDays . ' days');
        $latest   = $now->modify('+' . $this->config->maxFutureSkewSec . ' seconds');
        if ($dt < $earliest || $dt > $latest) {
            $errors[] = "{$field} di luar rentang yang diterima";

            return null;
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * @return list<int>|null
     */
    private function parseBbox(array $event, array &$errors): ?array
    {
        if (isset($event['bbox'])) {
            $box = $event['bbox'];
        } elseif (isset($event['bbox_x1'], $event['bbox_y1'], $event['bbox_x2'], $event['bbox_y2'])) {
            $box = [$event['bbox_x1'], $event['bbox_y1'], $event['bbox_x2'], $event['bbox_y2']];
        } else {
            return null;
        }

        if (!is_array($box) || count($box) !== 4 || array_filter($box, static fn ($v) => !is_int($v) || $v < 0 || $v > 10000) !== []) {
            $errors[] = 'bbox harus 4 integer 0-10000';

            return null;
        }
        $box = array_values($box);
        if ($box[2] <= $box[0] || $box[3] <= $box[1]) {
            $errors[] = 'bbox harus x2>x1 dan y2>y1';

            return null;
        }

        return $box;
    }

    private function shortString(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return mb_substr(preg_replace('/[^\w.\-+]/u', '', $value), 0, 64);
    }

    private function fail(array $errors): array
    {
        return ['ok' => false, 'errors' => $errors, 'row' => null];
    }
}
