import os
import sys
import time
import signal
import logging
import argparse
import threading
from datetime import datetime, timezone, timedelta
from typing import Dict, Any, Optional, List
import cv2
import requests
import torch

try:
    torch.set_num_threads(2)
except Exception:
    pass
cv2.setNumThreads(1)

from .config import (
    CAMERAS,
    get_camera_config,
    HEARTBEAT_API_URL,
    OVERLAY_API_URL,
    API_TOKEN,
    HEARTBEAT_INTERVAL,
    OVERLAY_EMIT_INTERVAL,
    BASE_DIR,
)
from .classifier import VehicleClassifier
from .detector import VehicleDetector
from .pipeline import DetectionPipeline
from .hls_proxy import start_proxy_thread, set_latest_overlay


class FlushingFileHandler(logging.FileHandler):
    def emit(self, record):
        super().emit(record)
        self.flush()


log_file_path = str(BASE_DIR / "worker.log")
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout),
        FlushingFileHandler(log_file_path, encoding="utf-8"),
    ]
)
logger = logging.getLogger("ai_worker")

WITA_TZ = timezone(timedelta(hours=8))


class ThreadedStreamCapture:
    """
    Reader stream video HLS berulir (threaded bufferless).
    Secara kontinu melakukan grab() dan menyimpan HANYA frame terkini.
    Mencegah penumpukan antrean internal FFmpeg dan menjamin inferensi
    selalu memproses frame real-time live detik ini tanpa tunda/lag.
    """
    def __init__(self, stream_url: str):
        self.stream_url = stream_url
        self.cap = None
        self.latest_frame = None
        self.is_running = False
        self.is_connected = False
        self.lock = threading.Lock()
        self.thread = None
        self._connect()

    def _connect(self):
        try:
            self.cap = cv2.VideoCapture(self.stream_url)
            self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            if self.cap.isOpened():
                self.is_connected = True
                self.is_running = True
                self.thread = threading.Thread(target=self._reader_loop, daemon=True)
                self.thread.start()
            else:
                self.is_connected = False
        except Exception:
            self.is_connected = False

    def _reader_loop(self):
        consecutive_failures = 0
        while self.is_running:
            if not self.cap or not self.cap.isOpened():
                time.sleep(0.5)
                continue
            try:
                grabbed = self.cap.grab()
                if grabbed:
                    consecutive_failures = 0
                    ret, frame = self.cap.retrieve()
                    if ret and frame is not None:
                        with self.lock:
                            self.latest_frame = frame
                    time.sleep(0.035)
                else:
                    consecutive_failures += 1
                    if consecutive_failures >= 25:
                        time.sleep(0.5)
                    else:
                        time.sleep(0.04)
            except Exception:
                consecutive_failures += 1
                time.sleep(0.1)

    def read(self):
        if not self.is_connected:
            return False, None
        with self.lock:
            if self.latest_frame is not None:
                return True, self.latest_frame
            return False, None

    def release(self):
        self.is_running = False
        self.is_connected = False
        if self.cap:
            try:
                self.cap.release()
            except Exception:
                pass


class SimoverAiWorker:
    """
    Instance worker untuk satu kamera pemantauan.
    Mendukung operasi independen dalam multi-thread / multi-camera runtime.
    """
    def __init__(self, camera_config: dict, max_frames: int = 0):
        self.camera_config = camera_config
        self.camera_source = camera_config["source_code"]
        self.camera_code = camera_config.get("code", "ATCS")
        self.camera_name = camera_config["name"]
        self.worker_name = camera_config.get("worker_name", f"worker_{self.camera_code.lower()}")
        self.stream_url = camera_config["stream_url"]

        self.max_frames = max_frames
        self.is_running = False

        self.classifier = None
        self.detector = None
        self.pipeline = None

        self.last_heartbeat = 0.0
        self.last_overlay_emit = 0.0

        self.fps = 0.0
        self.frame_count = 0
        self.total_frames = 0
        self.fps_timer = time.time()
        self.last_inference_time_ms = 0.0

        self.pid_file = str(BASE_DIR / f"{self.worker_name}.pid")

    def _write_pid(self):
        try:
            with open(self.pid_file, "w", encoding="utf-8") as f:
                f.write(str(os.getpid()))
            logger.info(f"[{self.camera_code}] Worker PID [{os.getpid()}] disimpan ke {self.pid_file}")
        except Exception as e:
            logger.warning(f"[{self.camera_code}] Tidak dapat menulis PID file: {e}")

    def _remove_pid(self):
        try:
            if os.path.isfile(self.pid_file):
                os.remove(self.pid_file)
        except Exception:
            pass

    def _send_heartbeat(self, status: str = "active"):
        now_wita = datetime.now(WITA_TZ).strftime("%Y-%m-%d %H:%M:%S")
        payload = {
            "worker_name": self.worker_name,
            "camera_source": self.camera_source,
            "status": status,
            "fps": round(self.fps, 2),
            "last_inference_time_ms": round(self.last_inference_time_ms, 2),
            "last_frame_time": now_wita,
        }
        headers = {
            "Content-Type": "application/json",
            "X-AI-Worker-Token": API_TOKEN,
        }
        def _post_heartbeat():
            try:
                if not hasattr(self, "http_session"):
                    self.http_session = requests.Session()
                self.http_session.post(HEARTBEAT_API_URL, json=payload, headers=headers, timeout=3.0)
            except Exception as e:
                logger.debug(f"[{self.camera_code}] Heartbeat notice: {e}")
        threading.Thread(target=_post_heartbeat, daemon=True).start()

    def _send_overlay(self, boxes: list):
        try:
            set_latest_overlay(boxes, 1280, 720)
        except Exception:
            pass
        # Catatan: visual overlay kotak di player CCTV telah ditiadakan sesuai instruksi,
        # sehingga tidak perlu mengirim HTTP POST berulang kali ke server PHP localhost:8080.

    def stop(self):
        self.is_running = False

    def run(self):
        if not API_TOKEN:
            logger.error(f"[{self.camera_code}] Token AI tidak tersedia di environment. Periksa .env.")
            return

        self._write_pid()

        logger.info(f"=== MEMULAI WORKER: [{self.camera_code}] {self.camera_name} ===")
        logger.info(f"[{self.camera_code}] Stream: {self.stream_url}")

        # Inisialisasi model detector dan classifier
        self.classifier = VehicleClassifier()
        self.detector = VehicleDetector()
        self.pipeline = DetectionPipeline(self.classifier, camera_config=self.camera_config)

        self.is_running = True
        self._send_heartbeat("active")

        consecutive_read_failures = 0
        stream_reader = None

        try:
            while self.is_running:
                # 1. Sambungkan threaded stream capture bufferless
                if stream_reader is None or not stream_reader.is_connected:
                    logger.info(f"[{self.camera_code}] Membuka koneksi video stream HLS...")
                    if stream_reader:
                        stream_reader.release()
                    stream_reader = ThreadedStreamCapture(self.stream_url)

                    if not stream_reader.is_connected:
                        logger.warning(f"[{self.camera_code}] Gagal menghubungkan ke stream. Menunggu 3 detik...")
                        self._send_heartbeat("connecting")
                        time.sleep(3.0)
                        continue

                    logger.info(f"[{self.camera_code}] Stream HLS berhasil tersambung.")
                    consecutive_read_failures = 0

                ret, frame = stream_reader.read()
                if not ret or frame is None:
                    consecutive_read_failures += 1
                    if consecutive_read_failures >= 40:
                        logger.warning(f"[{self.camera_code}] Tidak menerima frame baru. Reconnecting stream...")
                        stream_reader.release()
                        stream_reader = None
                        time.sleep(2.0)
                    else:
                        time.sleep(0.04)
                    continue

                consecutive_read_failures = 0
                self.frame_count += 1
                self.total_frames += 1

                # 2. Ukuran frame dan inferensi deteksi & klasifikasi
                t0 = time.time()
                tracked_objects = self.detector.detect_and_track(frame)
                overlay_boxes = self.pipeline.process_frame_tracks(tracked_objects, frame=frame)
                t1 = time.time()
                self.last_inference_time_ms = (t1 - t0) * 1000.0

                # Jeda adaptif untuk menjaga CPU tetap ringan dan web server responsif
                # Target ~4-5 FPS per kamera sangat ideal untuk mendeteksi truk yang melintas dalam 2-4 detik
                time.sleep(0.14)

                # 3. Hitung FPS
                now = time.time()
                elapsed = now - self.fps_timer
                if elapsed >= 1.0:
                    self.fps = self.frame_count / elapsed
                    self.frame_count = 0
                    self.fps_timer = now

                # 4. Kirim Overlay secara periodik ke backend
                if (now - self.last_overlay_emit) >= OVERLAY_EMIT_INTERVAL:
                    self._send_overlay(overlay_boxes)
                    self.last_overlay_emit = now

                # 5. Kirim Heartbeat ke backend
                if (now - self.last_heartbeat) >= HEARTBEAT_INTERVAL:
                    self._send_heartbeat("active")
                    self.last_heartbeat = now
                    logger.info(
                        f"[{self.camera_code}] Aktif - FPS: {self.fps:.1f} | Latensi: {self.last_inference_time_ms:.1f}ms | "
                        f"Objek aktif: {len(tracked_objects)} | Total deteksi: {len(self.pipeline.counted_tracks)}"
                    )

                # Cek jika ada batas maksimum frame untuk pengujian
                if self.max_frames > 0 and self.total_frames >= self.max_frames:
                    logger.info(f"[{self.camera_code}] Mencapai batas frame ({self.max_frames}). Berhenti.")
                    break

        except KeyboardInterrupt:
            logger.info(f"[{self.camera_code}] Penghentian manual KeyboardInterrupt.")
        except Exception as e:
            logger.exception(f"[{self.camera_code}] Kesalahan loop worker: {e}")
        finally:
            if stream_reader:
                stream_reader.release()
            self._send_heartbeat("stopped")
            self._remove_pid()
            logger.info(f"[{self.camera_code}] Worker dihentikan secara bersih.")


def main():
    parser = argparse.ArgumentParser(description="SiMover AI Worker")
    parser.add_argument(
        "--camera",
        type=str,
        default="soebardjo",
        help="Pilihan kamera: 'soebardjo' (ATCS-01, default tunggal), 'all' (kedua CCTV), atau 'sultan_adam' (ATCS-02)"
    )
    parser.add_argument("--max-frames", type=int, default=0, help="Batas frame maksimum per kamera (0 = kontinu)")
    args = parser.parse_args()

    # 1. Jalankan HLS proxy server pada thread latar belakang (port 8088)
    try:
        start_proxy_thread(port=8088)
        logger.info("HLS Stream Proxy multi-stream aktif pada http://localhost:8088/stream/...")
    except Exception as pe:
        logger.warning(f"Proxy stream catatan: {pe}")

    workers: List[SimoverAiWorker] = []

    if args.camera.lower() == "all" or args.camera.lower() == "both":
        logger.info("Mode: Pemantauan GANDA (2 Kamera ATCS: Jl. Gubernur Soebardjo & Simpang Sultan Adam)")
        for cam_key, cfg in CAMERAS.items():
            w = SimoverAiWorker(camera_config=cfg, max_frames=args.max_frames)
            workers.append(w)
    else:
        cfg = get_camera_config(args.camera)
        logger.info(f"Mode: Pemantauan TUNGGAL ({cfg['code']} - {cfg['name']})")
        w = SimoverAiWorker(camera_config=cfg, max_frames=args.max_frames)
        workers.append(w)

    # Tangani sinyal penghentian bersih
    def _sig_handler(signum, frame):
        logger.info(f"Menerima sinyal {signum}. Menghentikan seluruh worker...")
        for w in workers:
            w.stop()

    signal.signal(signal.SIGINT, _sig_handler)
    signal.signal(signal.SIGTERM, _sig_handler)

    # Jalankan worker
    threads = []
    for w in workers:
        t = threading.Thread(target=w.run, name=f"Thread-{w.worker_name}")
        t.start()
        threads.append(t)

    for t in threads:
        t.join()

    logger.info("Seluruh worker telah selesai.")


if __name__ == "__main__":
    main()
