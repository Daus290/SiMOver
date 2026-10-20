import time
import logging
from datetime import datetime, timezone, timedelta
from typing import Dict, Any, List, Optional
import requests

from .config import (
    CAMERA_SOURCE,
    CAMERA_NAME,
    DETECTION_API_URL,
    API_TOKEN,
    COUNTING_LINE,
    DETECTOR_MODEL_NAME,
)
from .classifier import VehicleClassifier

logger = logging.getLogger("ai_worker.pipeline")

WITA_TZ = timezone(timedelta(hours=8))


class DetectionPipeline:
    """
    Pipeline pelacakan perlintasan jalan, klasifikasi ONNX, dan pengiriman event deteksi ke API backend SiMover.
    Menjamin: 1 kendaraan fisik = 1 rekaman event (deduplikasi ketat via track_id).
    """

    def __init__(self, classifier: VehicleClassifier):
        self.classifier = classifier
        self.counted_tracks: Dict[int, Dict[str, Any]] = {}
        self.track_history: Dict[int, List[tuple]] = {}
        self.line_y = COUNTING_LINE["y1"]
        self.line_x1 = COUNTING_LINE["x1"]
        self.line_x2 = COUNTING_LINE["x2"]

    def _get_wita_now(self) -> str:
        return datetime.now(WITA_TZ).strftime("%Y-%m-%d %H:%M:%S")

    def process_frame_tracks(self, tracked_objects: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Proses daftar objek kendaraan pada frame saat ini.
        Mengecek crossing line dan mengirimkan event deteksi baru jika melintas.
        Mengembalikan daftar overlay untuk visualisasi client.
        """
        overlay_boxes = []

        for obj in tracked_objects:
            track_id = obj["track_id"]
            cx, cy = obj["centroid"]
            x1, y1, x2, y2 = obj["bbox"]
            crop = obj["crop"]

            # Catat riwayat posisi centroid
            if track_id not in self.track_history:
                self.track_history[track_id] = []
            self.track_history[track_id].append((cx, cy))
            if len(self.track_history[track_id]) > 30:
                self.track_history[track_id].pop(0)

            # Cek apakah sudah pernah diklasifikasikan dan disimpan
            is_already_counted = track_id in self.counted_tracks
            classification_data = None

            if is_already_counted:
                classification_data = self.counted_tracks[track_id]
            else:
                # Periksa apakah kendaraan melintasi counting line
                should_classify = False

                # Kondisi A: Centroid melintasi garis Y
                history = self.track_history[track_id]
                if len(history) >= 2:
                    prev_cy = history[-2][1]
                    curr_cy = history[-1][1]
                    # Melintas dari atas ke bawah atau bawah ke atas
                    if (prev_cy <= self.line_y < curr_cy) or (prev_cy >= self.line_y > curr_cy):
                        if self.line_x1 <= cx <= self.line_x2:
                            should_classify = True

                # Kondisi B: Berada di zona aktif deteksi jalan dan track sudah stabil (>= 6 frames)
                if not should_classify and len(history) >= 6:
                    if abs(cy - self.line_y) < 50 and self.line_x1 <= cx <= self.line_x2:
                        should_classify = True

                if should_classify and crop.shape[0] > 40 and crop.shape[1] > 40:
                    classification_data = self.classifier.classify(crop)
                    self.counted_tracks[track_id] = classification_data

                    # Simpan ke backend via API ingestion
                    self._dispatch_event(track_id, obj, classification_data)

            # Jika belum melintasi garis, jalankan klasifikasi ringan untuk overlay live preview
            if classification_data is None and crop.shape[0] > 40 and crop.shape[1] > 40:
                classification_data = self.classifier.classify(crop)

            # Siapkan metadata kotak overlay
            if classification_data:
                label = f"{classification_data['indonesian_label']} #{track_id} ({int(classification_data['confidence']*100)}%)"
                is_od = classification_data["is_over_dimension"] == 1
            else:
                label = f"Kendaraan #{track_id}"
                is_od = False

            overlay_boxes.append({
                "track_id": track_id,
                "x1": x1,
                "y1": y1,
                "x2": x2,
                "y2": y2,
                "label": label,
                "class_name": classification_data["class_name"] if classification_data else "unknown",
                "indonesian_label": classification_data["indonesian_label"] if classification_data else "Kendaraan Niaga",
                "is_over_dimension": is_od,
                "confidence": classification_data["confidence"] if classification_data else obj["confidence"],
            })

        # Bersihkan riwayat track lama yang sudah tidak aktif (> 150 frames)
        if len(self.track_history) > 300:
            active_ids = {o["track_id"] for o in tracked_objects}
            for old_id in list(self.track_history.keys()):
                if old_id not in active_ids and old_id not in self.counted_tracks:
                    del self.track_history[old_id]

        return overlay_boxes

    def _dispatch_event(self, track_id: int, obj: Dict[str, Any], cls: Dict[str, Any]) -> bool:
        """
        Kirim rekaman deteksi ke endpoint /api/ai/detections
        """
        now_wita = self._get_wita_now()
        timestamp_sec = int(time.time())
        event_id = f"atcs_tb_{track_id}_{timestamp_sec}"

        payload = {
            "event_id": event_id,
            "camera_source": CAMERA_SOURCE,
            "camera_name": CAMERA_NAME,
            "event_time": now_wita,
            "track_id": track_id,
            "class_index": cls["class_index"],
            "class_name": cls["class_name"],
            "indonesian_label": cls["indonesian_label"],
            "is_over_dimension": cls["is_over_dimension"],
            "confidence": cls["confidence"],
            "detector_model": DETECTOR_MODEL_NAME,
            "classifier_model": "best_yolo_cls.onnx",
            "bbox_x1": obj["bbox"][0],
            "bbox_y1": obj["bbox"][1],
            "bbox_x2": obj["bbox"][2],
            "bbox_y2": obj["bbox"][3],
            "raw_metadata": {
                "probabilities": cls.get("probabilities", {}),
                "centroid": obj["centroid"],
                "box_dims": [obj["box_w"], obj["box_h"]],
            }
        }

        headers = {
            "Content-Type": "application/json",
            "X-AI-Worker-Token": API_TOKEN,
        }

        try:
            resp = requests.post(DETECTION_API_URL, json=payload, headers=headers, timeout=3.0)
            if resp.status_code == 200:
                logger.info(f"-> [EVENT TERSIMPAN] {cls['indonesian_label']} #{track_id} (OD: {cls['is_over_dimension']}) - ID: {event_id}")
                return True
            else:
                logger.warning(f"Gagal mengirim event #{track_id} (HTTP {resp.status_code}): {resp.text}")
                return False
        except Exception as e:
            logger.error(f"Koneksi gagal saat mengirim event #{track_id}: {e}")
            return False
