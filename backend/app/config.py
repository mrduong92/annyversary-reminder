import os
from dotenv import load_dotenv

load_dotenv()


class Config:
    SECRET_KEY = os.getenv("SECRET_KEY", "dev-secret-key")
    SQLALCHEMY_DATABASE_URI = os.getenv(
        "DATABASE_URL", "postgresql://user:password@localhost:5432/anniversary_reminder"
    )
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")
    ZALO_APP_ID = os.getenv("ZALO_APP_ID", "")
    ZALO_APP_SECRET = os.getenv("ZALO_APP_SECRET", "")
    JWT_EXPIRATION_HOURS = 24 * 30  # 30 days
