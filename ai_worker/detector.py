import logging
from typing import List, Dict, Any, Tuple
import cv2
import numpy as np
from ultralytics import YOLO

from .config import DETECTOR_MODEL_NAME, VEHICLE_COCO_CLASSES, CONFIDENCE_THRESHOLD

logger = logging.getLogger("ai_worker.detector")


class VehicleDetector:
    """
    Detector kendaraan menggunakan YOLOv8 + ByteTrack tracker bawaan.
    Mendeteksi truk, bus, dan kendaraan komersial pada video frame, serta mengekstrak bounding box & crop.
    """

    def __init__(self, model_name: str = DETECTOR_MODEL_NAME, conf: float = CONFIDENCE_THRESHOLD):
        self.model_name = model_name
        self.conf = conf
        logger.info(f"Menginisialisasi YOLO detector: {self.model_name} dengan confidence: {self.conf}")
        self.model = YOLO(self.model_name)
        logger.info("YOLO detector siap.")

    def detect_and_track(self, frame: np.ndarray) -> List[Dict[str, Any]]:
        """
        Deteksi dan track kendaraan pada frame.
        Mengembalikan daftar objek aktif yang sedang dilacak dengan track_id dan bounding box.
        """
        if frame is None or frame.size == 0:
            return []

        h, w = frame.shape[:2]

        # Jalankan tracking dengan ByteTrack
        results = self.model.track(
            source=frame,
            persist=True,
            classes=VEHICLE_COCO_CLASSES,
            conf=self.conf,
            tracker="bytetrack.yaml",
            verbose=False
        )

        tracked_objects = []
        if not results or len(results) == 0:
            return tracked_objects

        boxes = results[0].boxes
        if boxes is None or len(boxes) == 0:
            return tracked_objects

        for box in boxes:
            # Periksa apakah objek memiliki track_id yang valid
            if box.id is None:
                continue

            track_id = int(box.id[0].item())
            cls_id = int(box.cls[0].item())
            conf_val = float(box.conf[0].item())

            # Bounding box koordinat integer [x1, y1, x2, y2]
            xyxy = box.xyxy[0].cpu().numpy()
            x1 = max(0, int(xyxy[0]))
            y1 = max(0, int(xyxy[1]))
            x2 = min(w, int(xyxy[2]))
            y2 = min(h, int(xyxy[3]))

            box_w = x2 - x1
            box_h = y2 - y1

            # Filter kendaraan terlalu kecil (noise atau kendaraan sangat jauh)
            if box_w < 50 or box_h < 40:
                continue

            # Centroid kendaraan
            cx = (x1 + x2) // 2
            cy = (y1 + y2) // 2

            # Potong crop kendaraan untuk klasifikasi
            crop = frame[y1:y2, x1:x2].copy()

            tracked_objects.append({
                "track_id": track_id,
                "coco_class": cls_id,
                "coco_name": results[0].names.get(cls_id, "vehicle"),
                "confidence": round(conf_val, 4),
                "bbox": [x1, y1, x2, y2],
                "centroid": (cx, cy),
                "box_w": box_w,
                "box_h": box_h,
                "crop": crop,
            })

        return tracked_objects
