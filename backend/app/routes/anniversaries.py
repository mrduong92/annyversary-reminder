from flask import Blueprint, request, jsonify, g
from app import db
from app.models.anniversary import Anniversary
from app.models.anniversary_group import AnniversaryGroup
from app.models.group_subscription import GroupSubscription
from app.middleware.auth import jwt_required
from app.services.lunar_calendar import get_solar_date_for_anniversary
from app.services.group_service import get_or_create_default_group
from datetime import date

anniversaries_bp = Blueprint("anniversaries", __name__)


def _enrich_anniversary(a, year, is_editable=True):
    """Add solar_date and is_editable to anniversary dict."""
    d = a.to_dict()
    solar = get_solar_date_for_anniversary(a.lunar_day, a.lunar_month, year)
    d["solar_date"] = f"{solar[2]:04d}-{solar[1]:02d}-{solar[0]:02d}" if solar else None
    d["is_editable"] = is_editable
    return d


@anniversaries_bp.route("", methods=["GET"])
@jwt_required
def list_anniversaries():
    year = request.args.get("year", date.today().year, type=int)
    group_id = request.args.get("group_id", type=int)

    # Own anniversaries
    query = Anniversary.query.filter_by(user_id=g.current_user.id)
    if group_id:
        query = query.filter_by(group_id=group_id)
    own_items = query.order_by(Anniversary.lunar_month, Anniversary.lunar_day).all()
    result = [_enrich_anniversary(a, year, is_editable=True) for a in own_items]

    # Subscribed group anniversaries (if no specific group_id filter, or if filtering to a subscribed group)
    if not group_id:
        subscribed_group_ids = [s.group_id for s in GroupSubscription.query.filter_by(user_id=g.current_user.id).all()]
        if subscribed_group_ids:
            sub_items = Anniversary.query.filter(
                Anniversary.group_id.in_(subscribed_group_ids)
            ).order_by(Anniversary.lunar_month, Anniversary.lunar_day).all()
            result.extend([_enrich_anniversary(a, year, is_editable=False) for a in sub_items])
    else:
        # Check if this group_id is a subscribed group
        sub = GroupSubscription.query.filter_by(group_id=group_id, user_id=g.current_user.id).first()
        if sub:
            sub_items = Anniversary.query.filter_by(group_id=group_id).order_by(
                Anniversary.lunar_month, Anniversary.lunar_day
            ).all()
            result = [_enrich_anniversary(a, year, is_editable=False) for a in sub_items]

    return jsonify(result)


@anniversaries_bp.route("", methods=["POST"])
@jwt_required
def create_anniversary():
    data = request.get_json()
    if not data:
        return jsonify({"error": "Request body required"}), 400

    required = ["person_name", "lunar_day", "lunar_month"]
    for field in required:
        if field not in data:
            return jsonify({"error": f"{field} required"}), 400

    # Default to user's default group if no group_id provided
    group_id = data.get("group_id")
    if not group_id:
        default_group = get_or_create_default_group(g.current_user.id)
        group_id = default_group.id
    else:
        # Verify user owns the group
        group = AnniversaryGroup.query.filter_by(id=group_id, user_id=g.current_user.id).first()
        if not group:
            return jsonify({"error": "Group not found or not owned"}), 404

    anniversary = Anniversary(
        user_id=g.current_user.id,
        person_name=data["person_name"],
        relationship=data.get("relationship"),
        lunar_day=data["lunar_day"],
        lunar_month=data["lunar_month"],
        lunar_year=data.get("lunar_year"),
        notes=data.get("notes"),
        is_recurring=data.get("is_recurring", True),
        source=data.get("source", "manual"),
        group_id=group_id,
    )
    db.session.add(anniversary)
    db.session.commit()

    return jsonify(anniversary.to_dict()), 201


@anniversaries_bp.route("/bulk", methods=["POST"])
@jwt_required
def create_bulk():
    data = request.get_json()
    if not data or "items" not in data:
        return jsonify({"error": "items array required"}), 400

    group_id = data.get("group_id")
    if not group_id:
        default_group = get_or_create_default_group(g.current_user.id)
        group_id = default_group.id

    created = []
    for item in data["items"]:
        if not item.get("person_name") or not item.get("lunar_day") or not item.get("lunar_month"):
            continue

        anniversary = Anniversary(
            user_id=g.current_user.id,
            person_name=item["person_name"],
            relationship=item.get("relationship"),
            lunar_day=item["lunar_day"],
            lunar_month=item["lunar_month"],
            lunar_year=item.get("lunar_year"),
            notes=item.get("notes"),
            is_recurring=item.get("is_recurring", True),
            source="ocr",
            group_id=group_id,
        )
        db.session.add(anniversary)
        created.append(anniversary)

    db.session.commit()
    return jsonify([a.to_dict() for a in created]), 201


@anniversaries_bp.route("/<int:id>", methods=["PUT"])
@jwt_required
def update_anniversary(id):
    anniversary = Anniversary.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not anniversary:
        return jsonify({"error": "Not found"}), 404

    data = request.get_json()
    if not data:
        return jsonify({"error": "Request body required"}), 400

    for field in ["person_name", "relationship", "lunar_day", "lunar_month", "lunar_year", "notes", "is_recurring"]:
        if field in data:
            setattr(anniversary, field, data[field])

    # Allow moving to a different owned group
    if "group_id" in data:
        group = AnniversaryGroup.query.filter_by(id=data["group_id"], user_id=g.current_user.id).first()
        if group:
            anniversary.group_id = data["group_id"]

    db.session.commit()
    return jsonify(anniversary.to_dict())


@anniversaries_bp.route("/<int:id>", methods=["DELETE"])
@jwt_required
def delete_anniversary(id):
    anniversary = Anniversary.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not anniversary:
        return jsonify({"error": "Not found"}), 404

    db.session.delete(anniversary)
    db.session.commit()
    return jsonify({"message": "Deleted"})
