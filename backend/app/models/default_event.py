from app import db


class DefaultEvent(db.Model):
    __tablename__ = "default_events"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    event_type = db.Column(db.String(16), nullable=False)
    enabled = db.Column(db.Boolean, default=True)

    __table_args__ = (
        db.UniqueConstraint("user_id", "event_type", name="uq_user_event_type"),
    )

    def to_dict(self):
        return {
            "id": self.id,
            "user_id": self.user_id,
            "event_type": self.event_type,
            "enabled": self.enabled,
        }
