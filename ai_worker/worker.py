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


import threading

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
            grabbed = self.cap.grab()
            if grabbed:
                consecutive_failures = 0
                ret, frame = self.cap.retrieve()
                if ret and frame is not None:
                    with self.lock:
                        self.latest_frame = frame
            else:
                consecutive_failures += 1
                if consecutive_failures >= 25:
                    time.sleep(0.5)
                else:
                    time.sleep(0.02)

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
        def _post_heartbeat():
            try:
                if not hasattr(self, "http_session"):
                    self.http_session = requests.Session()
                self.http_session.post(HEARTBEAT_API_URL, json=payload, headers=headers, timeout=3.0)
            except Exception as e:
                logger.debug(f"Heartbeat notice: {e}")
        threading.Thread(target=_post_heartbeat, daemon=True).start()

    def _send_overlay(self, boxes: list):
        # 1. Update in-memory real-time proxy (port 8088) langsung tanpa beban I/O
        try:
            set_latest_overlay(boxes, 1280, 720)
        except Exception:
            pass

        # 2. Sinkronkan ke cache file PHP via API secara background
        if not OVERLAY_API_URL:
            return
        now_wita = datetime.now(WITA_TZ).strftime("%Y-%m-%d %H:%M:%S")
        payload = {
            "camera_source": CAMERA_SOURCE,
            "frame_time": now_wita,
            "frame_width": 1280,
            "frame_height": 720,
            "boxes": boxes[:25] if boxes else [],
        }
        headers = {
            "Content-Type": "application/json",
            "X-AI-Worker-Token": API_TOKEN,
        }
        def _post():
            try:
                if not hasattr(self, "overlay_session"):
                    self.overlay_session = requests.Session()
                self.overlay_session.post(OVERLAY_API_URL, json=payload, headers=headers, timeout=0.8)
            except Exception:
                pass
        threading.Thread(target=_post, daemon=True).start()

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
            logger.error("Token AI tidak tersedia di environment. Periksa .env.")
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
        stream_reader = None

        try:
            while self.is_running:
                # 1. Sambungkan threaded stream capture bufferless
                if stream_reader is None or not stream_reader.is_connected:
                    logger.info("Membuka koneksi video stream HLS (Threaded Real-Time)...")
                    if stream_reader:
                        stream_reader.release()
                    stream_reader = ThreadedStreamCapture(self.stream_url)

                    if not stream_reader.is_connected:
                        logger.warning("Gagal menghubungkan ke stream. Menunggu 3 detik sebelum mencoba kembali...")
                        self._send_heartbeat("connecting")
                        time.sleep(3.0)
                        continue

                    logger.info("Stream HLS berhasil tersambung (Threaded Reader aktif).")
                    consecutive_read_failures = 0

                ret, frame = stream_reader.read()
                if not ret or frame is None:
                    consecutive_read_failures += 1
                    if consecutive_read_failures >= 40:
                        logger.warning("Tidak menerima frame baru dalam waktu lama. Reconnecting stream...")
                        stream_reader.release()
                        stream_reader = None
                        time.sleep(2.0)
                    else:
                        time.sleep(0.04)
                    continue

                consecutive_read_failures = 0
                self.frame_count += 1

                # 2. Ukuran frame dan inferensi deteksi & klasifikasi
                t0 = time.time()
                tracked_objects = self.detector.detect_and_track(frame)
                overlay_boxes = self.pipeline.process_frame_tracks(tracked_objects, frame=frame)
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
            if stream_reader:
                stream_reader.release()
            self._send_heartbeat("stopped")
            self._remove_pid()
            logger.info("Worker telah dihentikan secara bersih.")


def main():
    parser = argparse.ArgumentParser(description="SiMover AI Worker (Detector + Classifier)")
    parser.add_argument("--stream", type=str, default=STREAM_URL, help="URL stream HLS")
    parser.add_argument("--max-frames", type=int, default=0, help="Batas frame maksimum (0 = kontinu)")
    args = parser.parse_args()

    # Mulai HLS proxy server pada thread latar belakang (port 8088)
    try:
        start_proxy_thread(port=8088)
        logger.info("HLS Stream Proxy thread berhasil dijalankan pada port 8088.")
    except Exception as pe:
        logger.warning(f"Catatan proxy stream port 8088: {pe}")

    worker = SimoverAiWorker(stream_url=args.stream, max_frames=args.max_frames)
    worker.run()


if __name__ == "__main__":
    main()
