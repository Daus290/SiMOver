<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Prompt 53: menandai asal data AI agar hasil core Rizkiya tidak tercampur
 * dengan data uji worker Antigravity (Prompt 52). Tidak ada baris yang dihapus.
 */
class AddDataOriginToAiTables extends Migration
{
    private const LEGACY_ORIGIN = 'antigravity_worker_p52';

    public function up()
    {
        // 1. Kolom penanda asal & penelusuran pada ai_detections
        $this->forge->addColumn('ai_detections', [
            'data_origin' => [
                'type'       => 'VARCHAR',
                'constraint' => 48,
                'null'       => false,
                'default'    => '',
                'after'      => 'id',
            ],
            'source_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'data_origin',
            ],
            'result_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => false,
                'default'    => 'unknown',
                'after'      => 'source_version',
            ],
            'time_basis' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => false,
                'default'    => 'received',
                'after'      => 'received_time',
            ],
            'frame_time' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'time_basis',
            ],
            'confidence_raw' => [
                'type'  => 'DOUBLE',
                'null'  => true,
                'after' => 'confidence',
            ],
        ]);

        $this->forge->modifyColumn('ai_detections', [
            'track_id' => [
                'name'       => 'track_id',
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
        ]);

        // Data lama: event per track dari worker Antigravity, waktu = jam dinding saat dikirim
        $this->db->table('ai_detections')
            ->where('data_origin', '')
            ->set('data_origin', self::LEGACY_ORIGIN)
            ->set('source_version', 'prompt52-lokal')
            ->set('result_unit', 'track_event')
            ->set('time_basis', 'observed')
            ->set('confidence_raw', 'confidence', false)
            ->update();

        // event_id unik per asal data, bukan global
        $prefix = $this->db->DBPrefix;
        $this->db->query("ALTER TABLE `{$prefix}ai_detections` DROP INDEX `event_id`");
        $this->db->query("ALTER TABLE `{$prefix}ai_detections` ADD UNIQUE KEY `uniq_origin_event` (`data_origin`, `event_id`)");
        $this->db->query("ALTER TABLE `{$prefix}ai_detections` ADD KEY `idx_origin_camera_time` (`data_origin`, `camera_source`, `event_time`)");

        // 2. Asal data pada status pengirim
        $this->forge->addColumn('ai_worker_status', [
            'data_origin' => [
                'type'       => 'VARCHAR',
                'constraint' => 48,
                'null'       => false,
                'default'    => '',
                'after'      => 'worker_name',
            ],
        ]);
        $this->db->table('ai_worker_status')
            ->where('data_origin', '')
            ->set('data_origin', self::LEGACY_ORIGIN)
            ->update();

        // 3. Cakupan pemantauan per menit (dari heartbeat aktif) untuk membedakan
        //    "tidak ada kendaraan" dengan "tidak sedang dipantau"
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'data_origin' => [
                'type'       => 'VARCHAR',
                'constraint' => 48,
                'null'       => false,
            ],
            'camera_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'minute_start' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['data_origin', 'camera_source', 'minute_start'], 'uniq_coverage_minute');
        $this->forge->createTable('ai_monitoring_coverage', true);
    }

    public function down()
    {
        $prefix = $this->db->DBPrefix;

        $this->forge->dropTable('ai_monitoring_coverage', true);
        $this->forge->dropColumn('ai_worker_status', 'data_origin');

        $this->db->query("ALTER TABLE `{$prefix}ai_detections` DROP INDEX `idx_origin_camera_time`");
        $this->db->query("ALTER TABLE `{$prefix}ai_detections` DROP INDEX `uniq_origin_event`");
        $this->db->query("ALTER TABLE `{$prefix}ai_detections` ADD UNIQUE KEY `event_id` (`event_id`)");
        $this->forge->dropColumn('ai_detections', ['data_origin', 'source_version', 'result_unit', 'time_basis', 'frame_time', 'confidence_raw']);
    }
}
