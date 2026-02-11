"""
Chat service using OpenAI GPT-4o for anniversary-related Q&A.
"""

import json
from openai import OpenAI
from flask import current_app
from app.models.anniversary import Anniversary
from app.models.chat_message import ChatMessage
from app.models.group_subscription import GroupSubscription
from app.services.lunar_calendar import lunar_to_solar
from app import db
from datetime import date


def get_user_context(user_id: int) -> str:
    """Build context string from user's anniversaries (owned + subscribed) with solar date conversions."""
    # Own anniversaries
    own_anniversaries = Anniversary.query.filter_by(user_id=user_id).all()

    # Subscribed group anniversaries
    subscribed_group_ids = [s.group_id for s in GroupSubscription.query.filter_by(user_id=user_id).all()]
    sub_anniversaries = []
    if subscribed_group_ids:
        sub_anniversaries = Anniversary.query.filter(
            Anniversary.group_id.in_(subscribed_group_ids)
        ).all()

    all_anniversaries = own_anniversaries + sub_anniversaries
    if not all_anniversaries:
        return "Người dùng chưa có ngày giỗ nào được lưu."

    current_year = date.today().year
    items = []
    for a in all_anniversaries:
        solar = lunar_to_solar(a.lunar_day, a.lunar_month, current_year)
        solar_str = f"{solar[0]}/{solar[1]}/{solar[2]}" if solar else "không xác định"
        group_label = f" [{a.group.name}]" if a.group else ""
        items.append(
            f"- {a.person_name} ({a.relationship or 'không rõ'}){group_label}: "
            f"ngày {a.lunar_day}/{a.lunar_month} ÂL = {solar_str} DL"
            f"{' - ' + a.notes if a.notes else ''}"
        )

    return "Danh sách ngày giỗ:\n" + "\n".join(items)


def chat_with_ai(user_id: int, message: str) -> str:
    """Process a chat message and return AI response."""
    client = OpenAI(api_key=current_app.config["OPENAI_API_KEY"])

    context = get_user_context(user_id)

    # Save user message
    user_msg = ChatMessage(user_id=user_id, role="user", content=message)
    db.session.add(user_msg)

    # Get recent chat history (last 10 messages)
    recent = (
        ChatMessage.query.filter_by(user_id=user_id)
        .order_by(ChatMessage.created_at.desc())
        .limit(10)
        .all()
    )
    recent.reverse()

    messages = [
        {
            "role": "system",
            "content": (
                "Bạn là trợ lý nhắc lịch giỗ thông minh cho người Việt Nam. "
                "Bạn có thể trả lời câu hỏi về ngày giỗ, chuyển đổi âm-dương lịch, "
                "và cung cấp thông tin hữu ích. Trả lời bằng tiếng Việt, ngắn gọn và thân thiện.\n\n"
                f"Năm hiện tại: {date.today().year}\n"
                f"Ngày hôm nay: {date.today().strftime('%d/%m/%Y')}\n\n"
                f"{context}"
            ),
        },
    ]

    for msg in recent:
        messages.append({"role": msg.role, "content": msg.content})

    # Add current user message
    messages.append({"role": "user", "content": message})

    response = client.chat.completions.create(
        model="gpt-4o",
        messages=messages,
        max_tokens=1000,
        temperature=0.7,
    )

    ai_content = response.choices[0].message.content

    # Save AI response
    ai_msg = ChatMessage(user_id=user_id, role="assistant", content=ai_content)
    db.session.add(ai_msg)
    db.session.commit()

    return ai_content
