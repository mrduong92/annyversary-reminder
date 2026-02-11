from app import db
from datetime import datetime, timezone


class User(db.Model):
    __tablename__ = "users"

    id = db.Column(db.Integer, primary_key=True)
    zalo_uid = db.Column(db.String(64), unique=True, nullable=False)
    display_name = db.Column(db.String(128))
    avatar_url = db.Column(db.Text)
    created_at = db.Column(db.DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))

    anniversaries = db.relationship("Anniversary", backref="user", lazy="dynamic", cascade="all, delete-orphan")
    default_events = db.relationship("DefaultEvent", backref="user", lazy="dynamic", cascade="all, delete-orphan")
    reminders = db.relationship("Reminder", backref="user", lazy="dynamic", cascade="all, delete-orphan")
    chat_messages = db.relationship("ChatMessage", backref="user", lazy="dynamic", cascade="all, delete-orphan")
    anniversary_groups = db.relationship("AnniversaryGroup", backref="owner", lazy="dynamic", cascade="all, delete-orphan")
    group_subscriptions = db.relationship("GroupSubscription", backref="subscriber", lazy="dynamic", cascade="all, delete-orphan")

    def to_dict(self):
        return {
            "id": self.id,
            "zalo_uid": self.zalo_uid,
            "display_name": self.display_name,
            "avatar_url": self.avatar_url,
            "created_at": self.created_at.isoformat() if self.created_at else None,
        }
