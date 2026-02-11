from flask import Flask
from flask_cors import CORS
from flask_sqlalchemy import SQLAlchemy
from flask_migrate import Migrate

db = SQLAlchemy()
migrate = Migrate()


def create_app():
    app = Flask(__name__)
    app.config.from_object("app.config.Config")

    CORS(app)
    db.init_app(app)
    migrate.init_app(app, db)

    # Import models so Alembic can detect them
    from app import models  # noqa: F401

    # Register blueprints
    from app.routes.auth import auth_bp
    from app.routes.anniversaries import anniversaries_bp
    from app.routes.defaults import defaults_bp
    from app.routes.ocr import ocr_bp
    from app.routes.chat import chat_bp
    from app.routes.calendar import calendar_bp
    from app.routes.reminders import reminders_bp
    from app.routes.groups import groups_bp

    app.register_blueprint(auth_bp, url_prefix="/api/v1/auth")
    app.register_blueprint(anniversaries_bp, url_prefix="/api/v1/anniversaries")
    app.register_blueprint(defaults_bp, url_prefix="/api/v1/defaults")
    app.register_blueprint(ocr_bp, url_prefix="/api/v1/ocr")
    app.register_blueprint(chat_bp, url_prefix="/api/v1/chat")
    app.register_blueprint(calendar_bp, url_prefix="/api/v1/calendar")
    app.register_blueprint(reminders_bp, url_prefix="/api/v1/reminders")
    app.register_blueprint(groups_bp, url_prefix="/api/v1/groups")

    # Start scheduler in non-debug or main process
    import os
    if not app.debug or os.environ.get("WERKZEUG_RUN_MAIN") == "true":
        from app.jobs.scheduler import start_scheduler
        start_scheduler(app)

    return app
