"""
Reminder service: materializes reminders and handles sending notifications.
"""

from datetime import date, timedelta
from app import db
from app.models.anniversary import Anniversary
from app.models.default_event import DefaultEvent
from app.models.reminder import Reminder
from app.models.user import User
from app.models.group_subscription import GroupSubscription
from app.services.lunar_calendar import lunar_to_solar, get_solar_date_for_anniversary


DAYS_BEFORE = [7, 3, 1]


def materialize_reminders_for_year(year: int = None):
    """
    Create reminder rows for all anniversaries for the given year.
    For each anniversary, creates 3 reminders: 7, 3, and 1 days before.
    Also handles default events (Rằm 15 and Mùng 1).
    Also materializes reminders for subscribed groups.
    """
    if year is None:
        year = date.today().year

    users = User.query.all()
    for user in users:
        _materialize_anniversary_reminders(user, year)
        _materialize_default_event_reminders(user, year)
        _materialize_subscribed_group_reminders(user, year)

    db.session.commit()


def _materialize_anniversary_reminders(user: User, year: int):
    """Create reminders for all user's anniversaries."""
    anniversaries = Anniversary.query.filter_by(user_id=user.id).all()

    for ann in anniversaries:
        _create_reminders_for_anniversary(user.id, ann, year)


def _materialize_subscribed_group_reminders(user: User, year: int):
    """Create reminders for anniversaries in subscribed groups."""
    subscriptions = GroupSubscription.query.filter_by(user_id=user.id).all()

    for sub in subscriptions:
        anniversaries = Anniversary.query.filter_by(group_id=sub.group_id).all()
        for ann in anniversaries:
            _create_reminders_for_anniversary(user.id, ann, year)


def _create_reminders_for_anniversary(user_id: int, ann: Anniversary, year: int):
    """Create 7/3/1 day reminders for a single anniversary for a given user."""
    solar = get_solar_date_for_anniversary(ann.lunar_day, ann.lunar_month, year)
    if not solar:
        return

    solar_date = date(solar[2], solar[1], solar[0])

    for days in DAYS_BEFORE:
        remind_date = solar_date - timedelta(days=days)

        # Skip if reminder is in the past
        if remind_date < date.today():
            continue

        # Check if already exists
        existing = Reminder.query.filter_by(
            user_id=user_id,
            anniversary_id=ann.id,
            solar_date=solar_date,
            days_before=days,
        ).first()

        if not existing:
            reminder = Reminder(
                user_id=user_id,
                anniversary_id=ann.id,
                solar_date=solar_date,
                remind_date=remind_date,
                days_before=days,
                status="pending",
            )
            db.session.add(reminder)


def _materialize_default_event_reminders(user: User, year: int):
    """Create reminders for Rằm (15th) and Mùng 1 of each lunar month."""
    default_events = DefaultEvent.query.filter_by(user_id=user.id, enabled=True).all()

    for event in default_events:
        if event.event_type == "ram_15":
            lunar_day = 15
        elif event.event_type == "mung_1":
            lunar_day = 1
        else:
            continue

        for lunar_month in range(1, 13):
            solar = lunar_to_solar(lunar_day, lunar_month, year)
            if not solar:
                continue

            solar_date = date(solar[2], solar[1], solar[0])

            for days in DAYS_BEFORE:
                remind_date = solar_date - timedelta(days=days)
                if remind_date < date.today():
                    continue

                existing = Reminder.query.filter_by(
                    user_id=user.id,
                    event_type=event.event_type,
                    solar_date=solar_date,
                    days_before=days,
                ).first()

                if not existing:
                    reminder = Reminder(
                        user_id=user.id,
                        event_type=event.event_type,
                        solar_date=solar_date,
                        remind_date=remind_date,
                        days_before=days,
                        status="pending",
                    )
                    db.session.add(reminder)


def materialize_reminders_for_subscriber(user_id: int, group_id: int):
    """Backfill reminders for a user who just subscribed to a group."""
    year = date.today().year
    anniversaries = Anniversary.query.filter_by(group_id=group_id).all()
    for ann in anniversaries:
        _create_reminders_for_anniversary(user_id, ann, year)
    db.session.commit()


def get_pending_reminders_for_today():
    """Get all pending reminders for today."""
    today = date.today()
    return Reminder.query.filter_by(remind_date=today, status="pending").all()


def mark_reminder_sent(reminder_id: int):
    """Mark a reminder as sent."""
    from datetime import datetime, timezone
    reminder = Reminder.query.get(reminder_id)
    if reminder:
        reminder.status = "sent"
        reminder.sent_at = datetime.now(timezone.utc)
        db.session.commit()
