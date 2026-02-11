from flask import Blueprint, request, jsonify, g
from app import db
from app.models.default_event import DefaultEvent
from app.middleware.auth import jwt_required

defaults_bp = Blueprint("defaults", __name__)

VALID_TYPES = ["ram_15", "mung_1"]


@defaults_bp.route("", methods=["GET"])
@jwt_required
def get_defaults():
    events = DefaultEvent.query.filter_by(user_id=g.current_user.id).all()

    # Return all event types with their status
    result = {}
    for t in VALID_TYPES:
        event = next((e for e in events if e.event_type == t), None)
        result[t] = event.enabled if event else False

    return jsonify(result)


@defaults_bp.route("", methods=["PUT"])
@jwt_required
def update_defaults():
    data = request.get_json()
    if not data:
        return jsonify({"error": "Request body required"}), 400

    for event_type in VALID_TYPES:
        if event_type in data:
            event = DefaultEvent.query.filter_by(
                user_id=g.current_user.id, event_type=event_type
            ).first()

            if event:
                event.enabled = bool(data[event_type])
            else:
                event = DefaultEvent(
                    user_id=g.current_user.id,
                    event_type=event_type,
                    enabled=bool(data[event_type]),
                )
                db.session.add(event)

    db.session.commit()

    return get_defaults()
