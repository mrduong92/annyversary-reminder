from flask import Blueprint, request, jsonify, g
from app.middleware.auth import jwt_required
from app.services.chat_service import chat_with_ai
from app.models.chat_message import ChatMessage

chat_bp = Blueprint("chat", __name__)


@chat_bp.route("", methods=["POST"])
@jwt_required
def chat():
    data = request.get_json()
    if not data or "message" not in data:
        return jsonify({"error": "message required"}), 400

    try:
        response = chat_with_ai(g.current_user.id, data["message"])
        return jsonify({"response": response})
    except Exception as e:
        return jsonify({"error": f"Chat failed: {str(e)}"}), 500


@chat_bp.route("/history", methods=["GET"])
@jwt_required
def history():
    messages = (
        ChatMessage.query.filter_by(user_id=g.current_user.id)
        .order_by(ChatMessage.created_at.asc())
        .limit(50)
        .all()
    )
    return jsonify([m.to_dict() for m in messages])
