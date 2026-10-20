<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiDetectionsTable extends Migration
{
    public function up()
    {
        // 1. Tabel ai_detections
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'event_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'camera_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'camera_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'event_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'received_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'track_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'class_index' => [
                'type'       => 'INT',
                'constraint' => 5,
                'null'       => false,
            ],
            'class_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'indonesian_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'is_over_dimension' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'confidence' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,4',
                'null'       => false,
            ],
            'detector_model' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => 'yolov8m.pt',
            ],
            'classifier_model' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => 'best_yolo_cls.onnx',
            ],
            'bbox_x1' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'bbox_y1' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'bbox_x2' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'bbox_y2' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'raw_metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('event_id');
        $this->forge->addKey('event_time');
        $this->forge->addKey('class_name');
        $this->forge->addKey('is_over_dimension');
        $this->forge->addKey('camera_source');
        $this->forge->createTable('ai_detections', true);

        // 2. Tabel ai_worker_status
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'worker_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'camera_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'default'    => 'active',
            ],
            'fps' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'last_inference_time_ms' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'default'    => 0.00,
            ],
            'last_frame_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_heartbeat' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('worker_name');
        $this->forge->createTable('ai_worker_status', true);
    }

    public function down()
    {
        $this->forge->dropTable('ai_worker_status', true);
        $this->forge->dropTable('ai_detections', true);
    }
}
