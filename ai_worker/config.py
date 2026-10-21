import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent

# Muat variabel dari .env root jika belum ada di environment
def _load_env_file():
    env_file = BASE_DIR.parent / ".env"
    if env_file.exists():
        with open(env_file, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    parts = line.split("=", 1)
                    k = parts[0].strip()
                    v = parts[1].strip().strip("'\"")
                    if k not in os.environ:
                        os.environ[k] = v

_load_env_file()

STREAM_URL = os.getenv(
    "STREAM_URL",
    "https://atcs.banjarmasinkota.go.id/stream/jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota/index.m3u8"
)

CAMERA_SOURCE = "jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"
CAMERA_NAME = "Jalan Gubernur Soebardjo Jembatan Tatah Bangkal Masuk Kota"

API_BASE = os.getenv("API_BASE", "http://localhost:8080/api/ai")
# Token pengirim dari environment (.env). Prioritaskan AI_CORE_INGEST_TOKEN untuk dashboard utama.
API_TOKEN = os.getenv("AI_CORE_INGEST_TOKEN") or os.getenv("AI_INGEST_TOKEN", "")


DETECTION_API_URL = f"{API_BASE}/detections"
HEARTBEAT_API_URL = f"{API_BASE}/heartbeat"
OVERLAY_API_URL = f"{API_BASE}/overlay"

MODEL_CLS_PATH = str(BASE_DIR / "models" / "best_yolo_cls.onnx")
CLASS_MAPPING_PATH = str(BASE_DIR / "models" / "class_mapping.json")
DETECTOR_MODEL_NAME = "yolov8n.pt"

# Parameter Pengenalan Kendaraan & Klasifikasi
CONFIDENCE_THRESHOLD = 0.28
CLASSIFIER_INPUT_SIZE = 320

# Target Kelas COCO untuk Kendaraan Niaga/Besar (7: truck, 5: bus, 2: car)
VEHICLE_COCO_CLASSES = [7, 5, 2]

# Garis Penghitung (Counting Line) & Zona Deteksi Jalan pada Resolusi 1280x720
COUNTING_LINE = {
    "x1": 80,
    "y1": 420,
    "x2": 1200,
    "y2": 420,
}

DETECTION_ZONE = {
    "x_min": 50,
    "x_max": 1220,
    "y_min": 260,
    "y_max": 620,
}

# Heartbeat & Overlay Polling Interval (detik)
HEARTBEAT_INTERVAL = 4.0
OVERLAY_EMIT_INTERVAL = 0.35  # ~3 FPS overlay updates to backend
PID_FILE = str(BASE_DIR / "worker.pid")
