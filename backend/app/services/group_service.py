"""
Group service: business logic for anniversary groups, sharing, and subscriptions.
"""

import secrets
import string
from sqlalchemy.exc import IntegrityError
from app import db
from app.models.anniversary_group import AnniversaryGroup
from app.models.group_subscription import GroupSubscription
from app.models.anniversary import Anniversary
from app.models.reminder import Reminder


def create_default_group(user_id: int) -> AnniversaryGroup:
    """Create the default 'Chung' group for a new user."""
    group = AnniversaryGroup(
        user_id=user_id,
        name="Chung",
        is_default=True,
    )
    db.session.add(group)
    db.session.flush()
    return group


def get_or_create_default_group(user_id: int) -> AnniversaryGroup:
    """Get existing default group or create one."""
    group = AnniversaryGroup.query.filter_by(user_id=user_id, is_default=True).first()
    if not group:
        group = create_default_group(user_id)
        db.session.commit()
    return group


def generate_share_code() -> str:
    """Generate a random 8-character share code."""
    alphabet = string.ascii_letters + string.digits
    return "".join(secrets.choice(alphabet) for _ in range(8))


def create_share_code(group: AnniversaryGroup) -> str:
    """Generate and assign a share_code to a group. Retry on collision."""
    for _ in range(5):
        code = generate_share_code()
        group.share_code = code
        try:
            db.session.flush()
            db.session.commit()
            return code
        except IntegrityError:
            db.session.rollback()
            continue
    raise RuntimeError("Failed to generate unique share code")


def revoke_share(group: AnniversaryGroup):
    """Remove share_code and delete all subscriptions + their pending reminders."""
    subscriber_ids = [s.user_id for s in group.subscriptions.all()]

    if subscriber_ids:
        # Delete pending reminders for subscribers that came from this group's anniversaries
        anniversary_ids = [a.id for a in group.anniversaries.all()]
        if anniversary_ids:
            Reminder.query.filter(
                Reminder.user_id.in_(subscriber_ids),
                Reminder.anniversary_id.in_(anniversary_ids),
                Reminder.status == "pending",
            ).delete(synchronize_session=False)

        # Delete all subscriptions
        GroupSubscription.query.filter_by(group_id=group.id).delete(synchronize_session=False)

    group.share_code = None
    db.session.commit()


def join_group(group: AnniversaryGroup, user_id: int) -> GroupSubscription:
    """Subscribe a user to a group."""
    sub = GroupSubscription(group_id=group.id, user_id=user_id)
    db.session.add(sub)
    db.session.commit()
    return sub


def remove_subscriber(group_id: int, user_id: int):
    """Remove a subscriber from a group and clean up their reminders."""
    sub = GroupSubscription.query.filter_by(group_id=group_id, user_id=user_id).first()
    if sub:
        # Clean up pending reminders for this subscriber from this group
        anniversary_ids = [a.id for a in Anniversary.query.filter_by(group_id=group_id).all()]
        if anniversary_ids:
            Reminder.query.filter(
                Reminder.user_id == user_id,
                Reminder.anniversary_id.in_(anniversary_ids),
                Reminder.status == "pending",
            ).delete(synchronize_session=False)

        db.session.delete(sub)
        db.session.commit()


def reassign_orphaned_anniversaries(user_id: int):
    """Reassign anniversaries with null group_id to the user's default group."""
    default_group = get_or_create_default_group(user_id)
    Anniversary.query.filter_by(user_id=user_id, group_id=None).update(
        {"group_id": default_group.id}, synchronize_session=False
    )
    db.session.commit()
