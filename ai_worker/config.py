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

# Definisi 2 Kamera ATCS Banjarmasin untuk Deteksi AI
CAMERAS = {
    "jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota": {
        "source_code": "jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota",
        "code": "ATCS-01",
        "name": "Jl. Gubernur Soebardjo (Jembatan Tatah Bangkal)",
        "worker_name": "worker_soebardjo",
        "stream_url": os.getenv(
            "STREAM_URL_SOEBARDJO",
            "https://atcs.banjarmasinkota.go.id/stream/jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota/index.m3u8"
        ),
        "counting_line": {"x1": 80, "y1": 420, "x2": 1200, "y2": 420},
        "detection_zone": {"x_min": 50, "x_max": 1220, "y_min": 260, "y_max": 620},
    },
    "simpang_sultan_adam_sungai_andai": {
        "source_code": "simpang_sultan_adam_sungai_andai",
        "code": "ATCS-02",
        "name": "Simpang Sultan Adam - Sungai Andai",
        "worker_name": "worker_sultan_adam",
        "stream_url": os.getenv(
            "STREAM_URL_SULTAN_ADAM",
            "https://atcs.banjarmasinkota.go.id/stream/simpang_sultan_adam_sungai_andai/video1_stream.m3u8"
        ),
        "counting_line": {"x1": 30, "y1": 210, "x2": 610, "y2": 210},
        "detection_zone": {"x_min": 20, "x_max": 620, "y_min": 100, "y_max": 350},
    },
}

def get_camera_config(key: str = None) -> dict:
    if not key or key == "default" or key == "all":
        return CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]
    
    normalized = key.lower().strip()
    if normalized in ["atcs-01", "soebardjo", "tatah_bangkal", "jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]:
        return CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]
    if normalized in ["atcs-02", "sultan_adam", "sungai_andai", "simpang_sultan_adam_sungai_andai"]:
        return CAMERAS["simpang_sultan_adam_sungai_andai"]
    
    for k, cfg in CAMERAS.items():
        if normalized in k or normalized == cfg["code"].lower():
            return cfg
    return CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]

# Nilai default untuk kompatibilitas mundur
STREAM_URL = CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]["stream_url"]
CAMERA_SOURCE = CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]["source_code"]
CAMERA_NAME = CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]["name"]

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

# Garis Penghitung (Counting Line) & Zona Deteksi Jalan pada Resolusi 1280x720 (Default Kamera 1)
COUNTING_LINE = CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]["counting_line"]
DETECTION_ZONE = CAMERAS["jalan_gubernur_soebardjo_jembatan_tatah_bangkal_masuk_kota"]["detection_zone"]

# Heartbeat & Overlay Polling Interval (detik)
HEARTBEAT_INTERVAL = 4.0
OVERLAY_EMIT_INTERVAL = 0.35  # ~3 FPS overlay updates to backend
PID_FILE = str(BASE_DIR / "worker.pid")
