from flask import Blueprint, request, jsonify, g
from sqlalchemy.exc import IntegrityError
from app import db
from app.models.anniversary_group import AnniversaryGroup
from app.models.group_subscription import GroupSubscription
from app.models.user import User
from app.middleware.auth import jwt_required
from app.services.group_service import (
    create_share_code,
    revoke_share,
    join_group,
    remove_subscriber,
)

groups_bp = Blueprint("groups", __name__)


@groups_bp.route("", methods=["GET"])
@jwt_required
def list_groups():
    """List owned + subscribed groups."""
    user = g.current_user

    owned = AnniversaryGroup.query.filter_by(user_id=user.id).order_by(
        AnniversaryGroup.is_default.desc(), AnniversaryGroup.created_at
    ).all()

    subscribed_ids = [s.group_id for s in GroupSubscription.query.filter_by(user_id=user.id).all()]
    subscribed = AnniversaryGroup.query.filter(AnniversaryGroup.id.in_(subscribed_ids)).all() if subscribed_ids else []

    return jsonify({
        "owned": [g.to_dict() for g in owned],
        "subscribed": [{
            **g.to_dict(),
            "owner_name": g.owner.display_name,
            "owner_avatar": g.owner.avatar_url,
        } for g in subscribed],
    })


@groups_bp.route("", methods=["POST"])
@jwt_required
def create_group():
    """Create a new group."""
    data = request.get_json()
    if not data or not data.get("name"):
        return jsonify({"error": "name required"}), 400

    group = AnniversaryGroup(
        user_id=g.current_user.id,
        name=data["name"],
    )
    db.session.add(group)
    db.session.commit()

    return jsonify(group.to_dict()), 201


@groups_bp.route("/<int:id>", methods=["PUT"])
@jwt_required
def rename_group(id):
    """Rename a group (owner only)."""
    group = AnniversaryGroup.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not group:
        return jsonify({"error": "Not found"}), 404

    data = request.get_json()
    if not data or not data.get("name"):
        return jsonify({"error": "name required"}), 400

    group.name = data["name"]
    db.session.commit()

    return jsonify(group.to_dict())


@groups_bp.route("/<int:id>", methods=["DELETE"])
@jwt_required
def delete_group(id):
    """Delete a group (owner only, not default)."""
    group = AnniversaryGroup.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not group:
        return jsonify({"error": "Not found"}), 404

    if group.is_default:
        return jsonify({"error": "Cannot delete default group"}), 400

    # Reassign anniversaries to default group
    from app.services.group_service import get_or_create_default_group
    default_group = get_or_create_default_group(g.current_user.id)
    from app.models.anniversary import Anniversary
    Anniversary.query.filter_by(group_id=group.id).update(
        {"group_id": default_group.id}, synchronize_session=False
    )

    db.session.delete(group)
    db.session.commit()

    return jsonify({"message": "Deleted"})


@groups_bp.route("/<int:id>/share", methods=["POST"])
@jwt_required
def share_group(id):
    """Generate share_code for a group."""
    group = AnniversaryGroup.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not group:
        return jsonify({"error": "Not found"}), 404

    if group.share_code:
        return jsonify({"share_code": group.share_code})

    code = create_share_code(group)
    return jsonify({"share_code": code})


@groups_bp.route("/<int:id>/share", methods=["DELETE"])
@jwt_required
def unshare_group(id):
    """Revoke sharing (nullify code, delete subscriptions)."""
    group = AnniversaryGroup.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not group:
        return jsonify({"error": "Not found"}), 404

    revoke_share(group)
    return jsonify({"message": "Share revoked"})


@groups_bp.route("/<int:id>/subscribers", methods=["GET"])
@jwt_required
def list_subscribers(id):
    """List subscribers (owner only)."""
    group = AnniversaryGroup.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not group:
        return jsonify({"error": "Not found"}), 404

    subs = GroupSubscription.query.filter_by(group_id=group.id).all()
    result = []
    for sub in subs:
        user = User.query.get(sub.user_id)
        result.append({
            "user_id": sub.user_id,
            "display_name": user.display_name if user else None,
            "avatar_url": user.avatar_url if user else None,
            "subscribed_at": sub.created_at.isoformat() if sub.created_at else None,
        })

    return jsonify(result)


@groups_bp.route("/<int:id>/subscribers/<int:uid>", methods=["DELETE"])
@jwt_required
def remove_group_subscriber(id, uid):
    """Remove a subscriber (owner only)."""
    group = AnniversaryGroup.query.filter_by(id=id, user_id=g.current_user.id).first()
    if not group:
        return jsonify({"error": "Not found"}), 404

    remove_subscriber(group.id, uid)
    return jsonify({"message": "Removed"})


@groups_bp.route("/preview", methods=["GET"])
def preview_group():
    """Preview group info by share_code (no auth required for display)."""
    share_code = request.args.get("share_code")
    if not share_code:
        return jsonify({"error": "share_code required"}), 400

    group = AnniversaryGroup.query.filter_by(share_code=share_code).first()
    if not group:
        return jsonify({"error": "Group not found"}), 404

    owner = User.query.get(group.user_id)
    return jsonify({
        "id": group.id,
        "name": group.name,
        "anniversary_count": group.anniversaries.count(),
        "subscriber_count": group.subscriptions.count(),
        "owner_name": owner.display_name if owner else None,
        "owner_avatar": owner.avatar_url if owner else None,
    })


@groups_bp.route("/join", methods=["POST"])
@jwt_required
def join_shared_group():
    """Accept a shared group by share_code."""
    data = request.get_json()
    if not data or not data.get("share_code"):
        return jsonify({"error": "share_code required"}), 400

    group = AnniversaryGroup.query.filter_by(share_code=data["share_code"]).first()
    if not group:
        return jsonify({"error": "Group not found"}), 404

    if group.user_id == g.current_user.id:
        return jsonify({"error": "Cannot subscribe to your own group"}), 400

    try:
        sub = join_group(group, g.current_user.id)
    except IntegrityError:
        db.session.rollback()
        return jsonify({"error": "Already subscribed"}), 409

    # Materialize reminders for subscriber
    from app.services.reminder_service import materialize_reminders_for_subscriber
    materialize_reminders_for_subscriber(g.current_user.id, group.id)

    return jsonify({
        "subscription": sub.to_dict(),
        "group": group.to_dict(),
    }), 201


@groups_bp.route("/subscriptions/<int:group_id>", methods=["DELETE"])
@jwt_required
def unsubscribe(group_id):
    """Unsubscribe self from a group."""
    remove_subscriber(group_id, g.current_user.id)
    return jsonify({"message": "Unsubscribed"})
