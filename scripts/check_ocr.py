#!/usr/bin/env python3
import argparse
import json
import math
import re
import sys
import tempfile
import unicodedata
from pathlib import Path


def json_response(payload):
    print(json.dumps(payload, ensure_ascii=False))


def unavailable(message):
    json_response({
        "engine_available": False,
        "error": message,
    })


def normalize_name(value):
    text = unicodedata.normalize("NFD", str(value or ""))
    text = "".join(ch for ch in text if unicodedata.category(ch) != "Mn")
    return re.sub(r"[^A-Z0-9]", "", text.upper().replace(" VLE", "").replace(" VILLE", ""))


def amount_from_text(text):
    raw = str(text or "").replace("\u00a0", " ").upper()
    raw = re.sub(r"[Oo](?=\d)", "0", raw)
    raw = re.sub(r"(?<=\d)[Oo]", "0", raw)
    patterns = [
        r"(?:EUR|EUROS?)?\s*(\d[\d .']{0,10}[,.]\d{2})\s*(?:EUR|EUROS?)?",
        r"(?:EUR|EUROS?)\s*(\d[\d .']{2,8})",
        r"(\d[\d .']{2,8})\s*(?:EUR|EUROS?)",
    ]
    candidates = []
    for pattern in patterns:
        for match in re.finditer(pattern, raw):
            value = match.group(1).replace(" ", "").replace("'", "").replace(".", "").replace(",", ".")
            try:
                number = float(value)
            except ValueError:
                continue
            if 0 < number < 100000:
                score = 100 + min(25, len(value)) + (20 if "EUR" in match.group(0) else 0)
                candidates.append((score, number))
    if not candidates:
        return None
    candidates.sort(reverse=True)
    return round(candidates[0][1], 2)


def load_known_names(path):
    if not path:
        return []
    try:
        payload = json.loads(Path(path).read_text(encoding="utf-8"))
    except Exception:
        return []
    if not isinstance(payload, list):
        return []
    return [str(item).strip() for item in payload if str(item).strip()]


def match_known_name(text, known_names):
    normalized_text = normalize_name(text)
    best_name = ""
    best_score = 0.0
    for name in known_names:
        normalized = normalize_name(name)
        if not normalized:
            continue
        if normalized in normalized_text:
            return name
        for token in re.findall(r"[A-Z0-9]{6,}", normalized_text):
            score = similarity(token, normalized)
            if token in normalized or normalized in token:
                score = max(score, 0.92)
            if score > best_score:
                best_name = name
                best_score = score
    return best_name if best_score >= 0.72 else ""


def similarity(a, b):
    if not a or not b:
        return 0.0
    previous = list(range(len(b) + 1))
    current = [0] * (len(b) + 1)
    for i, ca in enumerate(a, 1):
        current[0] = i
        for j, cb in enumerate(b, 1):
            current[j] = previous[j - 1] if ca == cb else min(previous[j - 1], previous[j], current[j - 1]) + 1
        previous, current = current, previous
    return 1.0 - (previous[len(b)] / max(len(a), len(b), 1))


def clean_name_candidate(line):
    text = re.sub(r"\s+", " ", str(line or "")).strip(" -_.,;:")
    text = re.sub(r"^[^A-Za-zÀ-ÿ]+", "", text)
    text = re.sub(r"[^A-Za-zÀ-ÿ0-9 &'().-]+", " ", text)
    text = re.sub(r"\b(?:VLE|VILLE)\b$", "", text, flags=re.I).strip()
    return re.sub(r"\s+", " ", text)


def payer_from_lines(lines, known_names):
    known = match_known_name("\n".join(line["text"] for line in lines), known_names)
    if known:
        return known

    blocked = re.compile(
        r"(BANQUE|CHEQUE|PAYEZ|ORDRE|EURO|IBAN|BIC|SIGNATURE|MONTANT|FRANCE|CODE|COMPTE|GUICHET|"
        r"RIB|PAYABLE|ENDOSSABLE|SOMME|LETTRES|MARTIN\s+SOLS|CAISSE|EPARGNE|CIC|SUD\s+OUEST|"
        r"CREDIT|AGRICOLE|MUTUEL|POPULAIRE|POSTALE|BNP|LCL|TEL|RUE|ROUTE|AVENUE|BOULEVARD|"
        r"CHEMIN|IMPASSE|ALLEE|PLACE|QUARTIER|LIEU\s+DIT|RESIDENCE|LOTISSEMENT|CEDEX|PAU|MAZEROLLES)",
        re.I,
    )
    scored = []
    for item in lines:
        text = clean_name_candidate(item["text"])
        if len(text) < 4 or len(text) > 70 or blocked.search(text):
            continue
        words = text.split()
        if len(words) == 1 and text.upper() == text:
            continue
        score = item.get("confidence", 0) + min(len(text), 35)
        if text.upper() == text and len(words) >= 2:
            score += 25
        if re.search(r"\b(SARL|SAS|EURL|SASU|EI|ETS|MME|MONSIEUR|MADAME)\b", text, re.I):
            score += 20
        scored.append((score, text))
    if not scored:
        return ""
    scored.sort(reverse=True)
    return scored[0][1]


def visual_checks(cv2, image):
    return {
        "signature": detect_large_handwriting(cv2, image, (0.66, 0.52, 0.31, 0.34), 1200, 90, 28),
        "order": detect_large_handwriting(cv2, image, (0.10, 0.48, 0.58, 0.14), 1200, 85, 28),
    }


def detect_large_handwriting(cv2, image, zone, min_area, min_width, min_height):
    h, w = image.shape[:2]
    x, y, zw, zh = zone
    crop = image[int(h * y):int(h * (y + zh)), int(w * x):int(w * (x + zw))]
    if crop.size == 0:
        return False
    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
    _, binary = cv2.threshold(gray, 105, 255, cv2.THRESH_BINARY_INV)
    contours, _ = cv2.findContours(binary, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    for contour in contours:
        x0, y0, bw, bh = cv2.boundingRect(contour)
        area = cv2.contourArea(contour)
        ratio = bw / max(1, bh)
        if area >= min_area and bw >= min_width and bh >= min_height and ratio >= 1.05:
            return True
    return False


def visual_amount(cv2, image):
    h, w = image.shape[:2]
    zones = [
        (0.795, 0.392, 0.125, 0.13),
        (0.785, 0.392, 0.145, 0.13),
    ]
    for zone in zones:
        x, y, zw, zh = zone
        crop = image[int(h * y):int(h * (y + zh)), int(w * x):int(w * (x + zw))]
        if crop.size == 0:
            continue
        digits = classify_visual_digits(cv2, crop)
        if digits and len(digits) >= 3:
            try:
                return float("".join(digits))
            except ValueError:
                pass
    return None


def classify_visual_digits(cv2, crop):
    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
    _, binary = cv2.threshold(gray, 120, 255, cv2.THRESH_BINARY_INV)
    contours, _ = cv2.findContours(binary, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    boxes = []
    ch, cw = binary.shape[:2]
    for contour in contours:
        x, y, w, h = cv2.boundingRect(contour)
        area = cv2.contourArea(contour)
        if area > 70 and h > ch * 0.22 and w > cw * 0.025:
            boxes.append((x, y, w, h, area))
    if not boxes:
        return []
    max_h = max(box[3] for box in boxes)
    filtered = []
    for box in boxes:
        x, y, w, h, area = box
        if w / max(1, h) > 1.15 and h < max_h * 0.85:
            continue
        if 0.95 <= w / max(1, h) <= 1.75:
            split = x + int(w * 0.56)
            filtered.append((x, y, max(1, split - x), h, area / 2))
            filtered.append((split, y, max(1, x + w - split), h, area / 2))
        else:
            filtered.append(box)
    filtered.sort(key=lambda item: item[0])
    digits = []
    for box in filtered[:5]:
        digit = classify_digit(binary, box, max_h)
        if digit:
            digits.append(digit)
    return digits


def classify_digit(binary, box, max_h):
    x, y, w, h, _ = box
    if h < max_h * 0.45:
        return ""
    ratio = w / max(1, h)
    if ratio > 1.15 and h < max_h * 0.85:
        return ""

    def density(x0, x1, y0, y1):
        sx = x + int(w * x0)
        ex = x + int(w * x1)
        sy = y + int(h * y0)
        ey = y + int(h * y1)
        roi = binary[sy:max(sy + 1, ey), sx:max(sx + 1, ex)]
        return float((roi > 0).mean()) if roi.size else 0.0

    left = density(0, 0.3, 0.15, 0.85)
    right = density(0.7, 1, 0.15, 0.85)
    middle = density(0.35, 0.65, 0.3, 0.7)
    top = density(0.15, 0.85, 0, 0.25)
    bottom = density(0.15, 0.85, 0.75, 1)
    if middle < 0.05 and top > 0.09 and bottom > 0.08 and (left > 0.08 or right > 0.07):
        return "0"
    if left < 0.09 and right > 0.13 and middle > 0.08:
        return "3"
    if middle > 0.22 and left > 0.08 and right > 0.1:
        return "8"
    return ""


def read_paddle(ocr, image_path):
    result = ocr.ocr(str(image_path), cls=True)
    lines = []
    collect_paddle_lines(result, lines)
    return lines


def collect_paddle_lines(node, lines):
    if isinstance(node, dict):
        texts = node.get("rec_texts")
        scores = node.get("rec_scores") or []
        if isinstance(texts, list):
            for index, text in enumerate(texts):
                lines.append({"text": str(text), "confidence": float(scores[index] * 100) if index < len(scores) else 0.0})
        for value in node.values():
            collect_paddle_lines(value, lines)
        return
    if isinstance(node, (list, tuple)):
        if len(node) >= 2 and isinstance(node[1], (list, tuple)) and node[1] and isinstance(node[1][0], str):
            confidence = 0.0
            if len(node[1]) > 1:
                try:
                    confidence = float(node[1][1]) * 100
                except Exception:
                    confidence = 0.0
            lines.append({"text": node[1][0], "confidence": confidence})
            return
        for value in node:
            collect_paddle_lines(value, lines)


def orient_image(cv2, image):
    h, w = image.shape[:2]
    if h > w * 1.12:
        return cv2.rotate(image, cv2.ROTATE_90_COUNTERCLOCKWISE), 270
    return image, 0


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--image", required=True)
    parser.add_argument("--known-names", default="")
    args = parser.parse_args()

    try:
        import cv2
        from paddleocr import PaddleOCR
    except Exception as exc:
        unavailable(f"Dependances OCR manquantes: {exc}")
        return 0

    image_path = Path(args.image)
    image = cv2.imread(str(image_path))
    if image is None:
        unavailable("Image illisible")
        return 0

    known_names = load_known_names(args.known_names)
    oriented, rotation = orient_image(cv2, image)
    checks = visual_checks(cv2, oriented)
    amount = visual_amount(cv2, oriented)

    with tempfile.NamedTemporaryFile(suffix=".jpg", delete=False) as tmp:
        oriented_path = Path(tmp.name)
        cv2.imwrite(str(oriented_path), oriented)

    try:
        ocr = PaddleOCR(use_angle_cls=True, lang="fr", show_log=False)
        lines = read_paddle(ocr, oriented_path)
    finally:
        try:
            oriented_path.unlink(missing_ok=True)
        except Exception:
            pass

    ocr_text = "\n".join(line["text"] for line in lines)
    text_amount = amount_from_text(ocr_text)
    if amount is None:
        amount = text_amount

    payer_name = payer_from_lines(lines, known_names)
    micr = ""
    micr_candidates = [line["text"] for line in lines if re.search(r"\d{6,}", line["text"])]
    if micr_candidates:
        micr = max(micr_candidates, key=len)
    confidences = [line["confidence"] for line in lines if line.get("confidence")]
    confidence = sum(confidences) / len(confidences) if confidences else None

    json_response({
        "engine_available": True,
        "engine": "paddleocr+opencv",
        "rotation": rotation,
        "payer_name": payer_name,
        "amount": amount,
        "confidence": confidence,
        "visual_checks": checks,
        "micr": micr,
        "ocr_text": "\n".join([
            f"[orientation] rotation {rotation} deg",
            "[controles visuels]",
            f"Signature: {'oui' if checks['signature'] else 'a verifier'}",
            f"Destinataire: {'oui' if checks['order'] else 'a verifier'}",
            "[paddleocr]",
            ocr_text,
        ]),
    })
    return 0


if __name__ == "__main__":
    sys.exit(main())
