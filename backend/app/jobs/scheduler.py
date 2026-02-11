"""
APScheduler jobs for materializing and sending reminders.
"""

from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.cron import CronTrigger


scheduler = BackgroundScheduler()


def materialize_job(app):
    """Daily job at 00:00 UTC+7 (17:00 UTC) to materialize reminders."""
    with app.app_context():
        from app.services.reminder_service import materialize_reminders_for_year
        materialize_reminders_for_year()


def send_job(app):
    """Daily job at 07:00 UTC+7 (00:00 UTC) to send pending reminders."""
    with app.app_context():
        from app.services.reminder_service import get_pending_reminders_for_today, mark_reminder_sent
        from app.models.anniversary import Anniversary

        reminders = get_pending_reminders_for_today()
        for reminder in reminders:
            # Build notification message
            if reminder.anniversary_id:
                ann = Anniversary.query.get(reminder.anniversary_id)
                if ann:
                    message = (
                        f"Nhắc lịch giỗ: {ann.person_name}"
                        f" ({ann.relationship or ''})"
                        f" - Ngày {ann.lunar_day}/{ann.lunar_month} ÂL"
                        f" = {reminder.solar_date.strftime('%d/%m/%Y')} DL"
                        f" - Còn {reminder.days_before} ngày"
                    )
                else:
                    message = f"Nhắc lịch: {reminder.solar_date.strftime('%d/%m/%Y')} - Còn {reminder.days_before} ngày"
            else:
                event_label = "Rằm" if reminder.event_type == "ram_15" else "Mùng 1"
                message = (
                    f"Nhắc: {event_label}"
                    f" - {reminder.solar_date.strftime('%d/%m/%Y')} DL"
                    f" - Còn {reminder.days_before} ngày"
                )

            # TODO: Send via Zalo Mini App Push notification
            # For now, just mark as sent
            print(f"[REMINDER] User {reminder.user_id}: {message}")
            mark_reminder_sent(reminder.id)


def start_scheduler(app):
    """Start the APScheduler with cron jobs."""
    # Materialize at 17:00 UTC = 00:00 UTC+7
    scheduler.add_job(
        materialize_job,
        CronTrigger(hour=17, minute=0),
        args=[app],
        id="materialize_reminders",
        replace_existing=True,
    )

    # Send at 00:00 UTC = 07:00 UTC+7
    scheduler.add_job(
        send_job,
        CronTrigger(hour=0, minute=0),
        args=[app],
        id="send_reminders",
        replace_existing=True,
    )

    scheduler.start()
