from flask import Blueprint, request, jsonify, g
from app.middleware.auth import jwt_required
from app.services.ocr_service import extract_anniversaries_from_image

ocr_bp = Blueprint("ocr", __name__)


@ocr_bp.route("/extract", methods=["POST"])
@jwt_required
def extract():
    data = request.get_json()
    if not data or "image" not in data:
        return jsonify({"error": "image (base64) required"}), 400

    image_base64 = data["image"]

    # Remove data URL prefix if present
    if "," in image_base64:
        image_base64 = image_base64.split(",", 1)[1]

    try:
        results = extract_anniversaries_from_image(image_base64)
        return jsonify({"items": results})
    except Exception as e:
        return jsonify({"error": f"OCR failed: {str(e)}"}), 500
