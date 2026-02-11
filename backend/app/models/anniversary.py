from app import db
from datetime import datetime, timezone


class Anniversary(db.Model):
    __tablename__ = "anniversaries"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    person_name = db.Column(db.String(256), nullable=False)
    relationship = db.Column(db.String(128))
    lunar_day = db.Column(db.SmallInteger, nullable=False)
    lunar_month = db.Column(db.SmallInteger, nullable=False)
    lunar_year = db.Column(db.SmallInteger)
    notes = db.Column(db.Text)
    is_recurring = db.Column(db.Boolean, default=True)
    source = db.Column(db.String(16), default="manual")
    group_id = db.Column(db.Integer, db.ForeignKey("anniversary_groups.id", ondelete="SET NULL"), nullable=True)
    created_at = db.Column(db.DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))
    updated_at = db.Column(db.DateTime(timezone=True), default=lambda: datetime.now(timezone.utc), onupdate=lambda: datetime.now(timezone.utc))

    __table_args__ = (
        db.CheckConstraint("lunar_day BETWEEN 1 AND 30", name="ck_lunar_day"),
        db.CheckConstraint("lunar_month BETWEEN 1 AND 12", name="ck_lunar_month"),
    )

    def to_dict(self):
        return {
            "id": self.id,
            "user_id": self.user_id,
            "person_name": self.person_name,
            "relationship": self.relationship,
            "lunar_day": self.lunar_day,
            "lunar_month": self.lunar_month,
            "lunar_year": self.lunar_year,
            "notes": self.notes,
            "is_recurring": self.is_recurring,
            "source": self.source,
            "group_id": self.group_id,
            "group_name": self.group.name if self.group else None,
            "created_at": self.created_at.isoformat() if self.created_at else None,
            "updated_at": self.updated_at.isoformat() if self.updated_at else None,
        }
