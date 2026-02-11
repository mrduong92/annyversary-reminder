from flask import Blueprint, request, jsonify
from app.services.lunar_calendar import lunar_to_solar, solar_to_lunar

calendar_bp = Blueprint("calendar", __name__)


@calendar_bp.route("/lunar-to-solar", methods=["GET"])
def convert_lunar_to_solar():
    day = request.args.get("day", type=int)
    month = request.args.get("month", type=int)
    year = request.args.get("year", type=int)
    leap = request.args.get("leap", "false").lower() == "true"

    if not all([day, month, year]):
        return jsonify({"error": "day, month, year required"}), 400

    result = lunar_to_solar(day, month, year, leap)
    if result:
        d, m, y = result
        return jsonify({
            "solar_day": d,
            "solar_month": m,
            "solar_year": y,
            "solar_date": f"{y:04d}-{m:02d}-{d:02d}",
        })
    return jsonify({"error": "Invalid lunar date"}), 400


@calendar_bp.route("/solar-to-lunar", methods=["GET"])
def convert_solar_to_lunar():
    day = request.args.get("day", type=int)
    month = request.args.get("month", type=int)
    year = request.args.get("year", type=int)

    if not all([day, month, year]):
        return jsonify({"error": "day, month, year required"}), 400

    ld, lm, ly, is_leap = solar_to_lunar(day, month, year)
    return jsonify({
        "lunar_day": ld,
        "lunar_month": lm,
        "lunar_year": ly,
        "is_leap": is_leap,
    })
