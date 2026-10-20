import os
import sys
import time
import signal
import logging
import argparse
from datetime import datetime, timezone, timedelta
import cv2
import requests

from .config import (
    STREAM_URL,
    CAMERA_SOURCE,
    CAMERA_NAME,
    HEARTBEAT_API_URL,
    OVERLAY_API_URL,
    API_TOKEN,
    PID_FILE,
    HEARTBEAT_INTERVAL,
    OVERLAY_EMIT_INTERVAL,
    COUNTING_LINE,
    BASE_DIR,
)
from .classifier import VehicleClassifier
from .detector import VehicleDetector
from .pipeline import DetectionPipeline


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


class SimoverAiWorker:
    def __init__(self, stream_url: str = STREAM_URL, max_frames: int = 0):
        self.stream_url = stream_url
        self.max_frames = max_frames
        self.is_running = False

        self.classifier = None
        self.detector = None
        self.pipeline = None

        self.last_heartbeat = 0.0
        self.last_overlay_emit = 0.0

        self.fps = 0.0
        self.frame_count = 0
        self.fps_timer = time.time()
        self.last_inference_time_ms = 0.0

    def _write_pid(self):
        try:
            with open(PID_FILE, "w", encoding="utf-8") as f:
                f.write(str(os.getpid()))
            logger.info(f"Worker PID [{os.getpid()}] disimpan ke {PID_FILE}")
        except Exception as e:
            logger.warning(f"Tidak dapat menulis PID file: {e}")

    def _remove_pid(self):
        try:
            if os.path.isfile(PID_FILE):
                os.remove(PID_FILE)
                logger.info(f"Worker PID file dibersihkan.")
        except Exception as e:
            logger.warning(f"Gagal menghapus PID file: {e}")

    def _send_heartbeat(self, status: str = "active"):
        now_wita = datetime.now(WITA_TZ).strftime("%Y-%m-%d %H:%M:%S")
        payload = {
            "worker_name": "worker_tatah_bangkal",
            "camera_source": CAMERA_SOURCE,
            "status": status,
            "fps": round(self.fps, 2),
            "last_inference_time_ms": round(self.last_inference_time_ms, 2),
            "last_frame_time": now_wita,
        }
        headers = {
            "Content-Type": "application/json",
            "X-AI-Worker-Token": API_TOKEN,
        }
        try:
            requests.post(HEARTBEAT_API_URL, json=payload, headers=headers, timeout=2.0)
        except Exception as e:
            logger.debug(f"Heartbeat notice: {e}")

    def _send_overlay(self, boxes: list):
        payload = {
            "active": True,
            "camera_source": CAMERA_SOURCE,
            "fps": round(self.fps, 1),
            "boxes": boxes,
            "line": COUNTING_LINE,
            "vehicle_count": len(boxes),
            "total_counted": len(self.pipeline.counted_tracks) if self.pipeline else 0,
        }
        headers = {
            "Content-Type": "application/json",
            "X-AI-Worker-Token": API_TOKEN,
        }
        try:
            requests.post(OVERLAY_API_URL, json=payload, headers=headers, timeout=1.5)
        except Exception as e:
            logger.debug(f"Overlay emit notice: {e}")

    def setup_signal_handlers(self):
        def _handler(signum, frame):
            logger.info(f"Menerima sinyal {signum}. Menghentikan worker...")
            self.stop()
        signal.signal(signal.SIGINT, _handler)
        signal.signal(signal.SIGTERM, _handler)

    def stop(self):
        self.is_running = False

    def run(self):
        if not API_TOKEN:
            logger.error("AI_INGEST_TOKEN tidak tersedia di environment. Jalankan lewat scripts/ai_worker.ps1.")
            return

        self._write_pid()
        self.setup_signal_handlers()

        logger.info("=== MEMULAI SIMOVER AI WORKER ===")
        logger.info(f"Kamera: {CAMERA_NAME}")
        logger.info(f"Stream: {self.stream_url}")

        # Inisialisasi model
        self.classifier = VehicleClassifier()
        self.detector = VehicleDetector()
        self.pipeline = DetectionPipeline(self.classifier)

        self.is_running = True
        self._send_heartbeat("active")

        consecutive_read_failures = 0
        cap = None

        try:
            while self.is_running:
                # 1. Buka atau sambungkan kembali ke stream HLS
                if cap is None or not cap.isOpened():
                    logger.info("Membuka koneksi video stream HLS...")
                    cap = cv2.VideoCapture(self.stream_url)
                    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

                    if not cap.isOpened():
                        logger.warning("Gagal menghubungkan ke stream. Menunggu 4 detik sebelum mencoba kembali...")
                        self._send_heartbeat("connecting")
                        time.sleep(4.0)
                        continue

                    logger.info("Stream HLS berhasil tersambung.")
                    consecutive_read_failures = 0

                ret, frame = cap.read()
                if not ret or frame is None:
                    consecutive_read_failures += 1
                    logger.debug(f"Frame kosong dari stream (percobaan {consecutive_read_failures})...")
                    if consecutive_read_failures >= 15:
                        logger.warning("Terlalu banyak kegagalan baca berturut-turut. Menginisialisasi ulang video capture...")
                        cap.release()
                        cap = None
                        time.sleep(2.0)
                    else:
                        time.sleep(0.04)
                    continue

                consecutive_read_failures = 0
                self.frame_count += 1

                # 2. Ukuran frame dan inferensi
                t0 = time.time()
                tracked_objects = self.detector.detect_and_track(frame)
                overlay_boxes = self.pipeline.process_frame_tracks(tracked_objects)
                t1 = time.time()
                self.last_inference_time_ms = (t1 - t0) * 1000.0

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
                    logger.info(f"Worker aktif - FPS: {self.fps:.1f} | Latensi: {self.last_inference_time_ms:.1f}ms | Objek aktif: {len(tracked_objects)} | Total deteksi: {len(self.pipeline.counted_tracks)}")

                # Cek jika ada batas maksimum frame untuk pengujian
                if self.max_frames > 0 and self.frame_count >= self.max_frames:
                    logger.info(f"Mencapai batas frame ({self.max_frames}). Berhenti.")
                    break

        except KeyboardInterrupt:
            logger.info("Penghentian manual melalui KeyboardInterrupt.")
        except Exception as e:
            logger.exception(f"Terjadi kesalahan pada loop worker: {e}")
        finally:
            if cap:
                cap.release()
            self._send_heartbeat("stopped")
            self._remove_pid()
            logger.info("Worker telah dihentikan secara bersih.")


def main():
    parser = argparse.ArgumentParser(description="SiMover AI Worker (Detector + Classifier)")
    parser.add_argument("--stream", type=str, default=STREAM_URL, help="URL stream HLS")
    parser.add_argument("--max-frames", type=int, default=0, help="Batas frame maksimum (0 = kontinu)")
    args = parser.parse_args()

    worker = SimoverAiWorker(stream_url=args.stream, max_frames=args.max_frames)
    worker.run()


if __name__ == "__main__":
    main()
