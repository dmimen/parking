import os


def get_config() -> dict:
    return {
        "db": {
            "host": os.getenv("DB_HOST", "db"),
            "name": os.getenv("DB_NAME", "parking"),
            "user": os.getenv("DB_USER", "parking"),
            "password": os.getenv("DB_PASSWORD", "parking_pass"),
            "port": int(os.getenv("DB_PORT", "3306")),
        },
        "bot": {
            "token": os.getenv("BOT_TOKEN", ""),
        },
        "web_url": os.getenv("WEB_URL", "http://localhost:8080"),
        "app_secret": os.getenv("APP_SECRET", "change_me"),
        "admin": {
            "phone": os.getenv("ADM_PHONE", ""),
            "name": os.getenv("ADM_NAME", "Administrator"),
        },
    }
