<?php

use App\Libraries\AiIngestValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AiIntegration;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Uji kontrak validasi ingestion tanpa database.
 *
 * @internal
 */
final class AiIngestValidatorTest extends CIUnitTestCase
{
    private AiIngestValidator $validator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AiIngestValidator(new AiIntegration());
        $this->now = new DateTimeImmutable('2026-10-20 10:00:00', new DateTimeZone('Asia/Makassar'));
    }

    private function event(array $override = []): array
    {
        return array_merge([
            'event_id'      => 'fixture-001',
            'camera_source' => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
            'class_index'   => 1,
            'class_name'    => 'truck_container',
            'confidence'    => 0.876543219,
        ], $override);
    }

    public function testValidEventKeepsClassAndRawConfidence(): void
    {
        $r = $this->validator->validateEvent($this->event(), 'rizkiya_core', $this->now);

        $this->assertTrue($r['ok'], implode('; ', $r['errors']));
        $this->assertSame(1, $r['row']['class_index']);
        $this->assertSame('truck_container', $r['row']['class_name']);
        $this->assertSame(0.876543219, $r['row']['confidence_raw']);
        $this->assertSame('rizkiya_core', $r['row']['data_origin']);
        $this->assertSame('received', $r['row']['time_basis']);
        $this->assertNull($r['row']['frame_time']);
        $this->assertNull($r['row']['track_id']);
    }

    public function testLabelAndOdAreDerivedFromTrustedMappingNotPayload(): void
    {
        $r = $this->validator->validateEvent($this->event([
            'is_over_dimension' => 1,
            'indonesian_label'  => 'Label Palsu',
        ]), 'rizkiya_core', $this->now);

        $this->assertTrue($r['ok']);
        $this->assertSame(0, $r['row']['is_over_dimension']);
        $this->assertSame('Truk Kontainer', $r['row']['indonesian_label']);

        $od = $this->validator->validateEvent($this->event(['class_index' => 2, 'class_name' => 'truck_over_dimension']), 'rizkiya_core', $this->now);
        $this->assertSame(1, $od['row']['is_over_dimension']);
    }

    public function testClassNameOnlyIsResolvedToIndex(): void
    {
        $r = $this->validator->validateEvent($this->event(['class_index' => null, 'class_name' => 'truck_tangki']), 'rizkiya_core', $this->now);
        $this->assertTrue($r['ok']);
        $this->assertSame(3, $r['row']['class_index']);
    }

    #[DataProvider('invalidEvents')]
    public function testInvalidEventsAreRejected(array $override, string $expectedError): void
    {
        $r = $this->validator->validateEvent($this->event($override), 'rizkiya_core', $this->now);

        $this->assertFalse($r['ok']);
        $this->assertNull($r['row']);
        $this->assertStringContainsString($expectedError, implode('; ', $r['errors']));
    }

    public static function invalidEvents(): iterable
    {
        yield 'kelas tidak cocok indeks' => [['class_index' => 0, 'class_name' => 'truck_container'], 'tidak cocok'];
        yield 'indeks di luar 0-4' => [['class_index' => 5, 'class_name' => null], 'di luar 0-4'];
        yield 'kelas pickup tidak ada' => [['class_index' => null, 'class_name' => 'pickup'], 'tidak dikenal'];
        yield 'indeks string' => [['class_index' => '1'], 'harus integer'];
        yield 'kamera asing' => [['camera_source' => 'jalan_gubernur_soebardjo_keluar_kota'], 'camera_source'];
        yield 'confidence > 1' => [['confidence' => 1.5], 'confidence harus 0-1'];
        yield 'confidence string' => [['confidence' => '0.9'], 'confidence wajib angka'];
        yield 'event_id pendek' => [['event_id' => 'ab'], 'event_id'];
        yield 'event_id karakter aneh' => [['event_id' => 'abc def<script>'], 'event_id'];
        yield 'waktu terlalu lama' => [['frame_time' => '2026-10-01 10:00:00'], 'di luar rentang'];
        yield 'waktu masa depan' => [['frame_time' => '2026-10-20 11:00:00'], 'di luar rentang'];
        yield 'format waktu salah' => [['frame_time' => '20/10/2026'], 'frame_time harus'];
        yield 'bbox terbalik' => [['bbox' => [100, 100, 50, 200]], 'x2>x1'];
        yield 'bbox bukan integer' => [['bbox' => [1.5, 2, 3, 4]], 'bbox harus'];
        yield 'satuan tidak dikenal' => [['result_unit' => 'vehicle'], 'result_unit'];
        yield 'metadata bukan objek' => [['raw_metadata' => 'teks'], 'raw_metadata'];
    }

    public function testFrameTimeIsPreferredAndIsoIsConvertedToWita(): void
    {
        $r = $this->validator->validateEvent($this->event([
            'frame_time'    => '2026-10-20T01:30:00Z',
            'observed_time' => '2026-10-20 09:31:00',
        ]), 'rizkiya_core', $this->now);

        $this->assertTrue($r['ok'], implode('; ', $r['errors']));
        $this->assertSame('frame', $r['row']['time_basis']);
        $this->assertSame('2026-10-20 09:30:00', $r['row']['event_time']);
    }

    public function testLegacyWorkerPayloadShapeIsAccepted(): void
    {
        $r = $this->validator->validateEvent($this->event([
            'event_id'   => 'atcs_tb_14_1792397357',
            'event_time' => '2026-10-20 09:00:00',
            'track_id'   => 14,
            'bbox_x1'    => 10, 'bbox_y1' => 20, 'bbox_x2' => 110, 'bbox_y2' => 220,
            'raw_metadata' => ['centroid' => [60, 120]],
        ]), 'antigravity_worker_p52', $this->now);

        $this->assertTrue($r['ok'], implode('; ', $r['errors']));
        $this->assertSame('observed', $r['row']['time_basis']);
        $this->assertSame('track_event', $r['row']['result_unit']);
        $this->assertSame(110, $r['row']['bbox_x2']);
    }

    public function testHeartbeatValidation(): void
    {
        $ok = $this->validator->validateHeartbeat([
            'camera_source' => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
            'status'        => 'active',
            'fps'           => 2.5,
        ], $this->now);
        $this->assertTrue($ok['ok']);

        $bad = $this->validator->validateHeartbeat([
            'camera_source' => 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota',
            'status'        => 'running',
            'fps'           => -1,
        ], $this->now);
        $this->assertFalse($bad['ok']);
        $this->assertCount(2, $bad['errors']);
    }
}
