import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent

STREAM_URL = os.getenv(
    "STREAM_URL",
    "https://atcs.banjarmasinkota.go.id/stream/jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota/index.m3u8"
)

CAMERA_SOURCE = "jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"
CAMERA_NAME = "Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Masuk Kota"

API_BASE = os.getenv("API_BASE", "http://localhost:8080/api/ai")
# Token pengirim hanya dari environment (diisi scripts/ai_worker.ps1 dari .env). Tanpa fallback.
API_TOKEN = os.getenv("AI_INGEST_TOKEN", "")

DETECTION_API_URL = f"{API_BASE}/detections"
HEARTBEAT_API_URL = f"{API_BASE}/heartbeat"
OVERLAY_API_URL = f"{API_BASE}/overlay"

MODEL_CLS_PATH = str(BASE_DIR / "models" / "best_yolo_cls.onnx")
CLASS_MAPPING_PATH = str(BASE_DIR / "models" / "class_mapping.json")
DETECTOR_MODEL_NAME = "yolov8m.pt"

# Parameter Pengenalan Kendaraan & Klasifikasi
CONFIDENCE_THRESHOLD = 0.35
CLASSIFIER_INPUT_SIZE = 320

# Target Kelas COCO untuk Kendaraan Niaga/Besar (7: truck, 5: bus, 2: car)
VEHICLE_COCO_CLASSES = [7, 5, 2]

# Garis Penghitung (Counting Line) Virtual pada Resolusi 1280x720
# Kendaraan yang melintasi garis ini dari arah jalan masuk kota akan diklasifikasikan dan disimpan
COUNTING_LINE = {
    "x1": 80,
    "y1": 420,
    "x2": 1200,
    "y2": 420,
}

# Heartbeat & Overlay Polling Interval (detik)
HEARTBEAT_INTERVAL = 4.0
OVERLAY_EMIT_INTERVAL = 0.20  # ~5 FPS overlay updates to backend
PID_FILE = str(BASE_DIR / "worker.pid")
