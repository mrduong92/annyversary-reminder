from app import db
from datetime import datetime, timezone


class AnniversaryGroup(db.Model):
    __tablename__ = "anniversary_groups"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    name = db.Column(db.String(256), nullable=False)
    share_code = db.Column(db.String(8), unique=True, nullable=True)
    is_default = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))
    updated_at = db.Column(db.DateTime(timezone=True), default=lambda: datetime.now(timezone.utc), onupdate=lambda: datetime.now(timezone.utc))

    anniversaries = db.relationship("Anniversary", backref="group", lazy="dynamic")
    subscriptions = db.relationship("GroupSubscription", backref="group", lazy="dynamic", cascade="all, delete-orphan")

    def to_dict(self):
        return {
            "id": self.id,
            "user_id": self.user_id,
            "name": self.name,
            "share_code": self.share_code,
            "is_default": self.is_default,
            "anniversary_count": self.anniversaries.count(),
            "subscriber_count": self.subscriptions.count(),
            "created_at": self.created_at.isoformat() if self.created_at else None,
            "updated_at": self.updated_at.isoformat() if self.updated_at else None,
        }
