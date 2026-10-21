import os
import time
import threading
import logging
from datetime import datetime, timezone, timedelta
from typing import Dict, Any, List, Optional
import requests
import cv2

from .config import (
    CAMERA_SOURCE,
    CAMERA_NAME,
    DETECTION_API_URL,
    API_TOKEN,
    COUNTING_LINE,
    DETECTION_ZONE,
    DETECTOR_MODEL_NAME,
    BASE_DIR,
)
from .classifier import VehicleClassifier

logger = logging.getLogger("ai_worker.pipeline")

WITA_TZ = timezone(timedelta(hours=8))


class DetectionPipeline:
    """
    Pipeline pelacakan perlintasan jalan, klasifikasi ONNX, dan pengiriman event deteksi ke API backend SiMover.
    Menjamin: Setiap kendaraan komersial terdeteksi cepat di Detection Zone tanpa terlewat,
    disertai deduplikasi spasial terhadap pergantian track_id.
    """

    def __init__(self, classifier: VehicleClassifier):
        self.classifier = classifier
        self.counted_tracks: Dict[int, Dict[str, Any]] = {}
        self.track_history: Dict[int, List[tuple]] = {}
        self.line_y = COUNTING_LINE["y1"]
        self.line_x1 = COUNTING_LINE["x1"]
        self.line_x2 = COUNTING_LINE["x2"]

        # Batas Zona Deteksi Jalan Aktif
        self.zone_y_min = DETECTION_ZONE.get("y_min", 260)
        self.zone_y_max = DETECTION_ZONE.get("y_max", 620)
        self.zone_x_min = DETECTION_ZONE.get("x_min", 50)
        self.zone_x_max = DETECTION_ZONE.get("x_max", 1220)

        # Riwayat pengiriman untuk deduplikasi spasial (timestamp, cx, cy)
        self.recent_dispatches: List[tuple] = []

        # Persistent HTTP session untuk reuse TCP keep-alive connection ke API
        self.http_session = requests.Session()
        adapter = requests.adapters.HTTPAdapter(pool_connections=5, pool_maxsize=10, max_retries=2)
        self.http_session.mount("http://", adapter)

    def _get_wita_now(self) -> str:
        return datetime.now(WITA_TZ).strftime("%Y-%m-%d %H:%M:%S")

    def process_frame_tracks(self, tracked_objects: List[Dict[str, Any]], frame: Optional[Any] = None) -> List[Dict[str, Any]]:
        """
        Proses daftar objek kendaraan pada frame saat ini.
        Mengecek zona deteksi jalan & crossing line, lalu mengirimkan event deteksi baru jika melintas.
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

            # Cek apakah track_id ini sudah pernah diklasifikasikan
            is_already_counted = track_id in self.counted_tracks
            classification_data = None

            if is_already_counted:
                classification_data = self.counted_tracks[track_id]
            else:
                should_classify = False
                history = self.track_history[track_id]

                # Pemicu 1: Centroid melintasi garis horizontal
                if len(history) >= 2:
                    prev_cy = history[-2][1]
                    curr_cy = history[-1][1]
                    if (prev_cy <= self.line_y < curr_cy) or (prev_cy >= self.line_y > curr_cy):
                        if self.line_x1 <= cx <= self.line_x2:
                            should_classify = True

                # Pemicu 2: Berada di Detection Zone
                if not should_classify:
                    in_zone = (self.zone_y_min <= cy <= self.zone_y_max) and (self.zone_x_min <= cx <= self.zone_x_max)
                    if in_zone:
                        is_large_enough = (crop.shape[0] * crop.shape[1]) >= 1500
                        if len(history) >= 2 or (len(history) >= 1 and is_large_enough and obj.get("confidence", 0) >= 0.30):
                            should_classify = True

                if should_classify and crop.shape[0] >= 30 and crop.shape[1] >= 35:
                    classification_data = self.classifier.classify(crop)

                    # Deduplikasi spasial: hindari hitung ganda jika tracker mengganti ID
                    now_ts = time.time()
                    is_duplicate_spatial = False
                    for prev_ts, prev_cx, prev_cy in self.recent_dispatches:
                        if (now_ts - prev_ts) < 2.5:
                            dist = ((cx - prev_cx) ** 2 + (cy - prev_cy) ** 2) ** 0.5
                            if dist < 90:
                                is_duplicate_spatial = True
                                break

                    self.counted_tracks[track_id] = classification_data

                    if not is_duplicate_spatial:
                        self.recent_dispatches.append((now_ts, cx, cy))
                        if len(self.recent_dispatches) > 60:
                            self.recent_dispatches.pop(0)
                        # Simpan ke backend via API ingestion & simpan cuplikan kotak foto
                        self._dispatch_event(track_id, obj, classification_data, frame=frame)
                    else:
                        logger.debug(f"Track #{track_id} dilewati karena deduplikasi spasial.")

            # Siapkan metadata kotak overlay (hanya gunakan label klasifikasi jika sudah dihitung, tanpa kalkulasi ONNX berlebih)
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

    def _save_detection_snapshot(self, event_id: str, frame: Any, obj: Dict[str, Any], cls: Dict[str, Any]) -> None:
        """
        Simpan foto cuplikan truk yang terdeteksi dengan kotak (bounding box)
        ke direktori public/uploads/detections/{event_id}.jpg agar dapat dilihat di dashboard.
        """
        if frame is None:
            return
        try:
            h, w = frame.shape[:2]
            x1, y1, x2, y2 = obj["bbox"]
            is_od = cls.get("is_over_dimension", 0) == 1
            conf = int(cls.get("confidence", 0) * 100)
            label_text = f"{cls.get('indonesian_label', 'Kendaraan')} ({conf}%)"
            if is_od:
                label_text = f"[OD] {label_text}"

            # Berikan margin visual proporsional di sekitar truk
            pad_x = max(20, int((x2 - x1) * 0.20))
            pad_y = max(20, int((y2 - y1) * 0.20))

            crop_x1 = max(0, x1 - pad_x)
            crop_y1 = max(0, y1 - pad_y)
            crop_x2 = min(w, x2 + pad_x)
            crop_y2 = min(h, y2 + pad_y)

            snapshot = frame[crop_y1:crop_y2, crop_x1:crop_x2].copy()
            sh, sw = snapshot.shape[:2]

            # Koordinat kotak di dalam snapshot
            bx1 = x1 - crop_x1
            by1 = y1 - crop_y1
            bx2 = x2 - crop_x1
            by2 = y2 - crop_y1

            # Warna kotak: Merah untuk Over Dimension, Hijau untuk Normal (BGR)
            box_color = (30, 45, 235) if is_od else (35, 190, 60)
            thickness = 2

            # Gambar bounding box utama
            cv2.rectangle(snapshot, (bx1, by1), (bx2, by2), box_color, thickness)

            # Gambar aksen sudut modern (corner brackets)
            c_len = min(18, (bx2 - bx1) // 4, (by2 - by1) // 4)
            if c_len > 4:
                accent_color = (255, 255, 255)
                # Kiri atas
                cv2.line(snapshot, (bx1, by1), (bx1 + c_len, by1), accent_color, thickness + 1)
                cv2.line(snapshot, (bx1, by1), (bx1, by1 + c_len), accent_color, thickness + 1)
                # Kanan atas
                cv2.line(snapshot, (bx2, by1), (bx2 - c_len, by1), accent_color, thickness + 1)
                cv2.line(snapshot, (bx2, by1), (bx2, by1 + c_len), accent_color, thickness + 1)
                # Kiri bawah
                cv2.line(snapshot, (bx1, by2), (bx1 + c_len, by2), accent_color, thickness + 1)
                cv2.line(snapshot, (bx1, by2), (bx1, by2 - c_len), accent_color, thickness + 1)
                # Kanan bawah
                cv2.line(snapshot, (bx2, by2), (bx2 - c_len, by2), accent_color, thickness + 1)
                cv2.line(snapshot, (bx2, by2), (bx2, by2 - c_len), accent_color, thickness + 1)

            # Banner label di atas kotak
            font = cv2.FONT_HERSHEY_SIMPLEX
            font_scale = 0.50
            (tw, th), _ = cv2.getTextSize(label_text, font, font_scale, 2)
            lbl_y1 = max(0, by1 - th - 8)
            lbl_y2 = by1
            lbl_x1 = bx1
            lbl_x2 = min(sw, bx1 + tw + 10)

            cv2.rectangle(snapshot, (lbl_x1, lbl_y1), (lbl_x2, lbl_y2), box_color, -1)
            cv2.putText(snapshot, label_text, (lbl_x1 + 5, lbl_y2 - 5), font, font_scale, (255, 255, 255), 1, cv2.LINE_AA)

            # Timestamp WITA di pojok bawah
            ts_str = datetime.now(WITA_TZ).strftime("%d/%m/%Y %H:%M:%S WITA")
            cv2.putText(snapshot, ts_str, (6, sh - 6), font, 0.38, (0, 0, 0), 2, cv2.LINE_AA)
            cv2.putText(snapshot, ts_str, (6, sh - 6), font, 0.38, (230, 240, 255), 1, cv2.LINE_AA)

            # Target direktori public/uploads/detections
            out_dir = BASE_DIR.parent / "public" / "uploads" / "detections"
            out_dir.mkdir(parents=True, exist_ok=True)
            out_file = out_dir / f"{event_id}.jpg"

            cv2.imwrite(str(out_file), snapshot, [cv2.IMWRITE_JPEG_QUALITY, 85])

            # Rotasi bersihkan foto lama jika > 250 file agar storage hemat
            files = sorted(out_dir.glob("*.jpg"), key=os.path.getmtime)
            if len(files) > 250:
                for old in files[:-200]:
                    try:
                        old.unlink()
                    except Exception:
                        pass
        except Exception as e:
            logger.debug(f"Snapshot deteksi dilewati: {e}")

    def _dispatch_event(self, track_id: int, obj: Dict[str, Any], cls: Dict[str, Any], frame: Optional[Any] = None) -> bool:
        """
        Kirim rekaman deteksi ke endpoint /api/ai/detections dan simpan cuplikan foto kotak.
        """
        now_wita = self._get_wita_now()
        timestamp_sec = int(time.time())
        event_id = f"atcs_tb_{track_id}_{timestamp_sec}"

        # Simpan snapshot visual di background thread
        if frame is not None:
            threading.Thread(
                target=self._save_detection_snapshot,
                args=(event_id, frame.copy(), obj, cls),
                daemon=True
            ).start()

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

        def _send_worker():
            try:
                resp = self.http_session.post(DETECTION_API_URL, json=payload, headers=headers, timeout=6.0)
                if resp.status_code == 200:
                    logger.info(f"-> [EVENT TERSIMPAN] {cls['indonesian_label']} #{track_id} (OD: {cls['is_over_dimension']}) - ID: {event_id}")
                else:
                    logger.warning(f"Gagal mengirim event #{track_id} (HTTP {resp.status_code}): {resp.text}")
            except Exception as e:
                logger.warning(f"Koneksi gagal saat mengirim event #{track_id}: {e}")

        threading.Thread(target=_send_worker, daemon=True).start()
        return True
