<?php

use App\Libraries\AiDashboardService;
use App\Models\AiDetectionModel;
use App\Models\AiWorkerStatusModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Uji integrasi API ingestion, idempotensi, scope sumber, pagination dan status.
 * Seluruh data adalah fixture di database terpisah monitoring_dishub_test.
 *
 * @internal
 */
final class AiIngestionApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const TEST_DATABASE = 'monitoring_dishub_test';
    private const TOKEN = 'fixture-token-hanya-untuk-phpunit';
    private const CAMERA = 'jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota';

    protected $DBGroup   = 'tests';
    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        // Pengaman: jangan pernah migrate/refresh selain database test terpisah
        $group = config('Database')->tests;
        if (($group['database'] ?? '') !== self::TEST_DATABASE || ($group['DBDriver'] ?? '') !== 'MySQLi') {
            $this->markTestSkipped('Jalankan dengan -c phpunit.ai.xml (database ' . self::TEST_DATABASE . ').');
        }

        parent::setUp();

        $this->assertSame(self::TEST_DATABASE, $this->db->getDatabase());
    }

    private function event(string $id, array $override = []): array
    {
        return array_merge([
            'event_id'      => $id,
            'camera_source' => self::CAMERA,
            'class_index'   => 4,
            'class_name'    => 'truck_tidak_over',
            'confidence'    => 0.91,
        ], $override);
    }

    private function postEvents(mixed $payload, ?string $token = self::TOKEN)
    {
        $headers = $token === null ? [] : ['X-AI-Worker-Token' => $token];

        return $this->withHeaders($headers)->withBodyFormat('json')->post('api/ai/detections', $payload);
    }

    public function testWriteEndpointsRequireValidToken(): void
    {
        $this->postEvents($this->event('fixture-a'), null)->assertStatus(401);
        $this->postEvents($this->event('fixture-a'), 'salah')->assertStatus(401);
        $this->withBodyFormat('json')->post('api/ai/heartbeat', ['status' => 'active'])->assertStatus(401);
        $this->seeNumRecords(0, 'ai_detections', []);
    }

    public function testSameEventTwiceIsStoredOnce(): void
    {
        $first = $this->postEvents($this->event('fixture-dup'));
        $first->assertStatus(200);
        $this->assertSame('inserted', json_decode($first->getJSON(), true)['results'][0]['status']);

        $second = $this->postEvents($this->event('fixture-dup'));
        $second->assertStatus(200);
        $this->assertSame('duplicate', json_decode($second->getJSON(), true)['results'][0]['status']);

        $this->seeNumRecords(1, 'ai_detections', ['event_id' => 'fixture-dup']);
        $this->seeInDatabase('ai_detections', ['event_id' => 'fixture-dup', 'data_origin' => 'test_fixture', 'confidence_raw' => 0.91]);
    }

    public function testBatchReportsPerEventStatus(): void
    {
        $result = $this->postEvents([
            $this->event('fixture-b1'),
            $this->event('fixture-b2', ['class_index' => 9]),
            $this->event('fixture-b3', ['class_index' => 2, 'class_name' => 'truck_over_dimension', 'is_over_dimension' => 0]),
        ]);
        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertFalse($body['success']);
        $this->assertSame(['inserted' => 2, 'duplicate' => 0, 'rejected' => 1, 'error' => 0], $body['summary']);
        $this->assertSame('rejected', $body['results'][1]['status']);
        // OD diturunkan server dari kelas, nilai is_over_dimension dari payload diabaikan
        $this->seeInDatabase('ai_detections', ['event_id' => 'fixture-b3', 'is_over_dimension' => 1]);
    }

    public function testAllInvalidReturns422AndOversizeReturns413(): void
    {
        $this->postEvents($this->event('fixture-c1', ['camera_source' => 'kamera_lain']))->assertStatus(422);

        $batch = [];
        for ($i = 0; $i < 51; $i++) {
            $batch[] = $this->event('fixture-big-' . $i);
        }
        $this->postEvents($batch)->assertStatus(413);
        $this->seeNumRecords(0, 'ai_detections', []);
    }

    public function testFixtureOriginNeverAppearsInPublicDashboardSources(): void
    {
        $this->postEvents($this->event('fixture-scope'))->assertStatus(200);

        $service = new AiDashboardService();
        foreach (['core', 'uji-lama'] as $source) {
            $data = $service->build($service->normalizeParams(['source' => $source]));
            $this->assertSame(0, $data['stats']['total'], "Sumber {$source} tidak boleh memuat fixture");
            $this->assertSame(0, $data['table']['global_total']);
        }
    }

    public function testStatsChartAndTableShareOneScope(): void
    {
        $model = new AiDetectionModel();
        $today = date('Y-m-d');
        $classes = ['truck_box', 'truck_container', 'truck_over_dimension', 'truck_tangki', 'truck_tidak_over'];

        // 25 hasil core: 5 per kelas, pukul 00:00-00:24 hari ini; satu baris data uji lama sebagai pembanding scope
        for ($i = 0; $i < 25; $i++) {
            $model->insert([
                'data_origin' => 'rizkiya_core', 'result_unit' => 'unknown', 'event_id' => sprintf('core-%03d', $i),
                'camera_source' => self::CAMERA, 'camera_name' => 'Kamera Uji', 'event_time' => sprintf('%s 00:%02d:00', $today, $i),
                'received_time' => date('Y-m-d H:i:s'), 'time_basis' => 'received', 'class_index' => $i % 5,
                'class_name' => $classes[$i % 5], 'indonesian_label' => 'x', 'is_over_dimension' => (int) ($i % 5 === 2),
                'confidence' => 0.5, 'confidence_raw' => 0.5, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $model->insert([
            'data_origin' => 'antigravity_worker_p52', 'event_id' => 'legacy-1', 'camera_source' => self::CAMERA,
            'camera_name' => 'Kamera Uji', 'event_time' => $today . ' 00:30:00', 'received_time' => date('Y-m-d H:i:s'),
            'class_index' => 2, 'class_name' => 'truck_over_dimension', 'indonesian_label' => 'x', 'is_over_dimension' => 1,
            'confidence' => 0.4, 'created_at' => date('Y-m-d H:i:s'),
        ]);

        $service = new AiDashboardService();
        $data = $service->build($service->normalizeParams(['source' => 'core', 'page' => '3']));

        $this->assertSame(25, $data['stats']['total']);
        $this->assertSame(5, $data['stats']['od_count']);
        $this->assertSame(25, array_sum(array_column($data['stats']['classes'], 'count')));
        $chartSum = 0;
        foreach ($data['chart']['series'] as $s) {
            $chartSum += array_sum(array_filter($s['data'], 'is_int'));
        }
        $this->assertSame(25, $chartSum);
        $this->assertSame(25, $data['table']['global_total']);
        $this->assertSame(3, $data['table']['page']);
        $this->assertCount(5, $data['table']['data']);
        $this->assertSame('core-004', $data['table']['data'][0]['event_id']);

        // Halaman di luar batas dikoreksi, filter kelas & OD konsisten
        $page99 = $service->build($service->normalizeParams(['source' => 'core', 'page' => '99']));
        $this->assertSame(3, $page99['table']['page']);
        $od = $service->build($service->normalizeParams(['source' => 'core', 'od' => 'ya', 'tab' => 'hasil-deteksi']));
        $this->assertSame(5, $od['table']['total']);
        $this->assertTrue($od['table']['has_active_filter']);
        $box = $service->build($service->normalizeParams(['source' => 'core', 'class' => 'truck_box']));
        $this->assertSame(5, $box['table']['total']);
    }

    public function testHeartbeatStatusBecomesStaleAndRecordsCoverage(): void
    {
        $this->withHeaders(['X-AI-Worker-Token' => self::TOKEN])->withBodyFormat('json')
            ->post('api/ai/heartbeat', ['camera_source' => self::CAMERA, 'status' => 'active', 'fps' => 3.2])
            ->assertStatus(200);

        $status = (new AiWorkerStatusModel())->getSourceStatus('test_fixture');
        $this->assertSame('aktif', $status['state']);
        $this->seeNumRecords(1, 'ai_monitoring_coverage', ['data_origin' => 'test_fixture']);

        $this->db->table('ai_worker_status')->update(['last_heartbeat' => date('Y-m-d H:i:s', time() - 60)]);
        $stale = (new AiWorkerStatusModel())->getSourceStatus('test_fixture');
        $this->assertSame('kedaluwarsa', $stale['state']);
        $this->assertFalse($stale['is_live']);
    }

    public function testOverlayFromNonOfficialSourceIsNeverServed(): void
    {
        $this->withHeaders(['X-AI-Worker-Token' => self::TOKEN])->withBodyFormat('json')->post('api/ai/overlay', [
            'camera_source' => self::CAMERA,
            'frame_time'    => date('c'),
            'frame_width'   => 1280,
            'frame_height'  => 720,
            'boxes'         => [['x1' => 1, 'y1' => 1, 'x2' => 50, 'y2' => 50, 'label' => 'fixture']],
        ])->assertStatus(200);

        $body = json_decode($this->get('api/ai/overlay')->getJSON(), true);
        $this->assertFalse($body['available']);
        $this->assertSame([], $body['boxes']);
    }

    protected function tearDown(): void
    {
        // Overlay cache bersifat file; bersihkan sisa fixture agar tidak tampil di player
        $file = WRITEPATH . 'cache/ai_overlay_latest.json';
        if (is_file($file) && str_contains((string) file_get_contents($file), '"origin":"test_fixture"')) {
            unlink($file);
        }
        parent::tearDown();
    }
}
