from flask import Blueprint, request, jsonify, g
from app.middleware.auth import jwt_required
from app.models.reminder import Reminder
from app.models.anniversary import Anniversary
from datetime import date, timedelta

reminders_bp = Blueprint("reminders", __name__)


@reminders_bp.route("/upcoming", methods=["GET"])
@jwt_required
def upcoming():
    days = request.args.get("days", 30, type=int)
    today = date.today()
    end_date = today + timedelta(days=days)

    reminders = (
        Reminder.query.filter(
            Reminder.user_id == g.current_user.id,
            Reminder.remind_date >= today,
            Reminder.remind_date <= end_date,
        )
        .order_by(Reminder.remind_date)
        .all()
    )

    result = []
    for r in reminders:
        d = r.to_dict()
        if r.anniversary_id:
            ann = Anniversary.query.get(r.anniversary_id)
            if ann:
                d["person_name"] = ann.person_name
                d["relationship"] = ann.relationship
                d["lunar_day"] = ann.lunar_day
                d["lunar_month"] = ann.lunar_month
        result.append(d)

    return jsonify(result)
