from flask import Blueprint, request, jsonify, current_app
import jwt
from datetime import datetime, timedelta, timezone
from app import db
from app.config import Config
from app.models.user import User
from app.services.zalo_auth import verify_zalo_token
from app.services.group_service import create_default_group

auth_bp = Blueprint("auth", __name__)


@auth_bp.route("/login", methods=["POST"])
def login():
    data = request.get_json()
    if not data or "access_token" not in data:
        return jsonify({"error": "access_token required"}), 400

    access_token = data["access_token"]
    zalo_info = verify_zalo_token(access_token)

    if not zalo_info:
        # In debug/dev mode, allow fallback to a dev user
        if current_app.debug:
            zalo_info = {
                "id": f"dev_{access_token[:16]}",
                "name": "Dev User",
                "picture": None,
            }
        else:
            return jsonify({"error": "Invalid Zalo token"}), 401

    zalo_uid = zalo_info["id"]
    user = User.query.filter_by(zalo_uid=zalo_uid).first()

    if not user:
        user = User(
            zalo_uid=zalo_uid,
            display_name=zalo_info.get("name"),
            avatar_url=zalo_info.get("picture"),
        )
        db.session.add(user)
        db.session.flush()
        create_default_group(user.id)
        db.session.commit()
    else:
        user.display_name = zalo_info.get("name", user.display_name)
        user.avatar_url = zalo_info.get("picture", user.avatar_url)
        db.session.commit()

    token = jwt.encode(
        {
            "user_id": user.id,
            "exp": datetime.now(timezone.utc) + timedelta(hours=Config.JWT_EXPIRATION_HOURS),
        },
        Config.SECRET_KEY,
        algorithm="HS256",
    )

    return jsonify({"token": token, "user": user.to_dict()})
