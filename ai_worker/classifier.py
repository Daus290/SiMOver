import json
import logging
from typing import Dict, Any, Tuple
import cv2
import numpy as np
import onnxruntime as ort

from .config import MODEL_CLS_PATH, CLASS_MAPPING_PATH, CLASSIFIER_INPUT_SIZE

logger = logging.getLogger("ai_worker.classifier")


class VehicleClassifier:
    """
    Wrapper inferensi ONNX untuk model klasifikasi kendaraan komersial (best_yolo_cls.onnx).
    Mendukung letterbox preprocessing 320x320 dan kalkulasi softmax.
    """

    def __init__(self, model_path: str = MODEL_CLS_PATH, mapping_path: str = CLASS_MAPPING_PATH):
        self.model_path = model_path
        self.mapping_path = mapping_path

        logger.info(f"Memuat model ONNX classifier dari: {self.model_path}")
        options = ort.SessionOptions()
        options.graph_optimization_level = ort.GraphOptimizationLevel.ORT_ENABLE_ALL
        options.intra_op_num_threads = 4

        # Menggunakan CPUExecutionProvider (stabil di semua lingkungan Windows)
        self.session = ort.InferenceSession(self.model_path, options, providers=["CPUExecutionProvider"])
        self.input_name = self.session.get_inputs()[0].name
        self.output_name = self.session.get_outputs()[0].name

        # Muat class mapping
        with open(self.mapping_path, "r", encoding="utf-8") as f:
            mapping = json.load(f)

        self.classes = mapping.get("classes", [
            "truck_box",
            "truck_container",
            "truck_over_dimension",
            "truck_tangki",
            "truck_tidak_over"
        ])

        self.labels_id = mapping.get("labels_id", {
            "truck_box": "Truk Box",
            "truck_container": "Truk Kontainer",
            "truck_over_dimension": "Truk Over Dimension",
            "truck_tangki": "Truk Tangki",
            "truck_tidak_over": "Truk Bak Terbuka Normal"
        })

        self.target_size = mapping.get("img_size", CLASSIFIER_INPUT_SIZE)
        logger.info(f"Model classifier siap: {len(self.classes)} kelas, target size: {self.target_size}x{self.target_size}")

    def letterbox(self, image: np.ndarray, target_size: int = 320, color: Tuple[int, int, int] = (114, 114, 114)) -> np.ndarray:
        """
        Resize gambar dengan mempertahankan aspek rasio dan mengisi padding seragam.
        """
        shape = image.shape[:2]  # [tinggi, lebar]
        if shape[0] == 0 or shape[1] == 0:
            return np.full((target_size, target_size, 3), color[0], dtype=np.uint8)

        # Skala rasio (new / old)
        r = min(target_size / shape[0], target_size / shape[1])
        new_unpad = (int(round(shape[1] * r)), int(round(shape[0] * r)))

        # Padding lebar dan tinggi
        dw = target_size - new_unpad[0]
        dh = target_size - new_unpad[1]

        # Bagi padding ke kedua sisi
        dw /= 2
        dh /= 2

        if shape[::-1] != new_unpad:
            resized = cv2.resize(image, new_unpad, interpolation=cv2.INTER_LINEAR)
        else:
            resized = image

        top, bottom = int(round(dh - 0.1)), int(round(dh + 0.1))
        left, right = int(round(dw - 0.1)), int(round(dw + 0.1))

        padded = cv2.copyMakeBorder(
            resized, top, bottom, left, right,
            cv2.BORDER_CONSTANT, value=color
        )

        # Pastikan ukuran tepat target_size x target_size
        if padded.shape[:2] != (target_size, target_size):
            padded = cv2.resize(padded, (target_size, target_size), interpolation=cv2.INTER_LINEAR)

        return padded

    def preprocess(self, crop_bgr: np.ndarray) -> np.ndarray:
        """
        Preprocessing crop kendaraan menjadi tensor input ONNX [1, 3, 320, 320]
        """
        # 1. Letterbox resize
        padded_bgr = self.letterbox(crop_bgr, target_size=self.target_size)

        # 2. BGR -> RGB
        rgb = cv2.cvtColor(padded_bgr, cv2.COLOR_BGR2RGB)

        # 3. Normalisasi [0, 1] dan HWC -> CHW
        chw = np.ascontiguousarray(rgb.transpose(2, 0, 1), dtype=np.float32) / 255.0

        # 4. Tambahkan batch dimension [1, 3, 320, 320]
        tensor = np.expand_dims(chw, axis=0)
        return tensor

    def classify(self, crop_bgr: np.ndarray) -> Dict[str, Any]:
        """
        Klasifikasikan potongan gambar kendaraan.
        Mengembalikan kelas terpilih, confidence, dan label Bahasa Indonesia.
        """
        if crop_bgr is None or crop_bgr.size == 0 or crop_bgr.shape[0] < 10 or crop_bgr.shape[1] < 10:
            return {
                "class_index": 4,
                "class_name": "truck_tidak_over",
                "indonesian_label": "Truk Bak Terbuka Normal",
                "is_over_dimension": 0,
                "confidence": 0.0,
                "probabilities": {},
            }

        input_tensor = self.preprocess(crop_bgr)
        outputs = self.session.run([self.output_name], {self.input_name: input_tensor})
        logits = outputs[0][0]

        # Hitung Softmax yang stabil secara numerik
        exp_logits = np.exp(logits - np.max(logits))
        probabilities = exp_logits / np.sum(exp_logits)

        best_index = int(np.argmax(probabilities))
        best_confidence = float(probabilities[best_index])
        best_class = self.classes[best_index] if best_index < len(self.classes) else "truck_tidak_over"
        label_id = self.labels_id.get(best_class, best_class)
        is_od = 1 if best_class == "truck_over_dimension" else 0

        prob_dict = {
            self.classes[i]: float(probabilities[i])
            for i in range(min(len(self.classes), len(probabilities)))
        }

        return {
            "class_index": best_index,
            "class_name": best_class,
            "indonesian_label": label_id,
            "is_over_dimension": is_od,
            "confidence": round(best_confidence, 4),
            "probabilities": prob_dict,
        }
