from app import db
from datetime import datetime, timezone


class Reminder(db.Model):
    __tablename__ = "reminders"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    anniversary_id = db.Column(db.Integer, db.ForeignKey("anniversaries.id", ondelete="CASCADE"), nullable=True)
    event_type = db.Column(db.String(16))
    solar_date = db.Column(db.Date, nullable=False)
    remind_date = db.Column(db.Date, nullable=False)
    days_before = db.Column(db.SmallInteger, nullable=False)
    status = db.Column(db.String(16), default="pending")
    sent_at = db.Column(db.DateTime(timezone=True))

    __table_args__ = (
        db.Index("idx_reminders_date", "remind_date", "status"),
    )

    def to_dict(self):
        return {
            "id": self.id,
            "user_id": self.user_id,
            "anniversary_id": self.anniversary_id,
            "event_type": self.event_type,
            "solar_date": self.solar_date.isoformat() if self.solar_date else None,
            "remind_date": self.remind_date.isoformat() if self.remind_date else None,
            "days_before": self.days_before,
            "status": self.status,
            "sent_at": self.sent_at.isoformat() if self.sent_at else None,
        }
