from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path
from typing import Any

import cv2
import numpy as np

from omr import __version__


def load_config(path: Path) -> tuple[dict[str, Any], str]:
    raw = path.read_bytes()
    return json.loads(raw), hashlib.sha256(raw).hexdigest()


def normalize_geometry(image: np.ndarray, config: dict[str, Any]) -> tuple[np.ndarray, dict[str, Any], list[str]]:
    canvas = config["canvas"]
    width, height = int(canvas["width"]), int(canvas["height"])

    if image.shape[1] == width and image.shape[0] == height:
        return image, {"mode": "canonical", "markers_found": 0}, []

    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    dictionary = cv2.aruco.getPredefinedDictionary(cv2.aruco.DICT_4X4_50)
    corners, ids, _ = cv2.aruco.detectMarkers(gray, dictionary)
    detected = {} if ids is None else {int(marker_id): corner[0] for marker_id, corner in zip(ids.flatten(), corners)}

    if not all(marker_id in detected for marker_id in config["geometry"]["ids"]):
        return cv2.resize(image, (width, height)), {"mode": "fallback_resize", "markers_found": len(detected)}, ["GEOMETRY_REVIEW_REQUIRED"]

    centers = {marker_id: detected[marker_id].mean(axis=0) for marker_id in config["geometry"]["ids"]}
    source = np.float32([centers[0], centers[1], centers[2], centers[3]])
    destination = np.float32([[190, 190], [2290, 190], [2290, 3318], [190, 3318]])
    matrix = cv2.getPerspectiveTransform(source, destination)

    return cv2.warpPerspective(image, matrix, (width, height)), {"mode": "aruco", "markers_found": 4}, []


def quality(gray: np.ndarray) -> dict[str, float]:
    return {
        "sharpness": round(float(cv2.Laplacian(gray, cv2.CV_64F).var()), 4),
        "contrast": round(float(gray.std()), 4),
        "brightness": round(float(gray.mean()), 4),
    }


def classify_question(binary: np.ndarray, y: int, config: dict[str, Any]) -> dict[str, Any]:
    answers = config["answers"]
    thresholds = config["thresholds"]
    radius = int(answers["mask_radius"])
    ratios: dict[str, float] = {}

    for alternative, x in answers["centers_x"].items():
        mask = np.zeros(binary.shape, dtype=np.uint8)
        cv2.circle(mask, (int(x), y), radius, 255, -1)
        ratios[alternative] = round(float(cv2.mean(binary, mask=mask)[0] / 255.0), 5)

    ordered = sorted(ratios.items(), key=lambda item: item[1], reverse=True)
    best, second = ordered[0], ordered[1]
    marked = [alternative for alternative, ratio in ratios.items() if ratio >= thresholds["marked_fill_ratio"]]

    if len(marked) > 1:
        detection_type, alternative = "dupla", None
    elif len(marked) == 1 and best[1] - second[1] >= thresholds["minimum_margin"]:
        detection_type, alternative = "marcada", marked[0]
    elif best[1] >= thresholds["ambiguous_fill_ratio"]:
        detection_type, alternative = "ambigua", best[0]
    else:
        detection_type, alternative = "branco", None

    confidence = max(0.0, min(1.0, best[1] - second[1] + 0.5))
    return {
        "alternative": alternative,
        "type": detection_type,
        "confidence": round(confidence, 5),
        "metrics": {"fill_ratio": ratios},
    }


def process(image_path: Path, config_path: Path) -> dict[str, Any]:
    config, checksum = load_config(config_path)
    image = cv2.imread(str(image_path))
    if image is None:
        raise ValueError("IMAGE_UNREADABLE")

    normalized, geometry, alerts = normalize_geometry(image, config)
    gray = cv2.cvtColor(normalized, cv2.COLOR_BGR2GRAY)
    metrics = quality(gray)
    binary = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)[1]
    thresholds = config["thresholds"]

    if metrics["sharpness"] < thresholds["minimum_sharpness"]:
        alerts.append("LOW_SHARPNESS")
    if metrics["contrast"] < thresholds["minimum_contrast"]:
        alerts.append("LOW_CONTRAST")

    responses = []
    for question in range(1, int(config["answers"]["questions"]) + 1):
        y = int(config["answers"]["first_center_y"]) + (question - 1) * int(config["answers"]["row_step"])
        response = classify_question(binary, y, config)
        responses.append({"question": question, **response})
        if response["type"] in {"dupla", "ambigua"}:
            alerts.append(f"QUESTION_{question}_{response['type'].upper()}")

    alerts = sorted(set(alerts))
    return {
        "contract_version": "1.0",
        "processor_version": __version__,
        "model": {"id": config["spec_id"], "version": config["spec_version"], "config_sha256": checksum},
        "status": "partial" if alerts or not config["homologated"] else "success",
        "review_required": True if alerts or not config["homologated"] else False,
        "quality": metrics,
        "geometry": geometry,
        "confidence": round(sum(item["confidence"] for item in responses) / len(responses), 5),
        "printed_code": None,
        "system_code_affixed": None,
        "responses": responses,
        "alerts": alerts + ([] if config["homologated"] else ["MODEL_NOT_HOMOLOGATED"]),
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--image", required=True, type=Path)
    parser.add_argument("--config", required=True, type=Path)
    parser.add_argument("--output", type=Path)
    args = parser.parse_args()
    result = process(args.image, args.config)
    serialized = json.dumps(result, ensure_ascii=True, separators=(",", ":"))

    if args.output:
        args.output.write_text(serialized, encoding="utf-8")
    else:
        print(serialized)


if __name__ == "__main__":
    main()
