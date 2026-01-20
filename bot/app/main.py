import asyncio
import logging
import re

from aiogram import Bot, Dispatcher

from app.config import get_config
from app.db import Database
from app.handlers import start, search, manage_cars
from app.rbac import can_manage


def normalize_phone(phone: str) -> str:
    return re.sub(r"\D+", "", phone)


async def ensure_admin(db: Database, config: dict) -> None:
    # Гарантируем создание администратора из переменных окружения.
    phone = normalize_phone(config["admin"]["phone"])
    if not phone:
        return
    user = await db.fetchone("SELECT id, role, status FROM users WHERE phone = %s", (phone,))
    if user:
        if user["role"] != "admin" or user["status"] != "active":
            await db.execute(
                "UPDATE users SET role = 'admin', status = 'active' WHERE id = %s",
                (user["id"],),
            )
        return
    await db.execute(
        "INSERT INTO users (name, phone, role, status) VALUES (%s, %s, 'admin', 'active')",
        (config["admin"]["name"], phone),
    )


async def otp_worker(bot: Bot, db: Database) -> None:
    # Фоновая задача: отправка OTP из таблицы otp_outbox.
    while True:
        rows = await db.fetchall(
            "SELECT o.id, o.user_id, o.message, u.tg_id FROM otp_outbox o JOIN users u ON u.id = o.user_id WHERE o.status = 'pending' ORDER BY o.id ASC LIMIT 10"
        )
        for row in rows:
            try:
                if not row["tg_id"]:
                    await db.execute(
                        "UPDATE otp_outbox SET status = 'error', error_message = 'no_tg_id' WHERE id = %s",
                        (row["id"],),
                    )
                    continue
                await bot.send_message(row["tg_id"], row["message"], parse_mode="HTML")
                await db.execute(
                    "UPDATE otp_outbox SET status = 'sent', sent_at = NOW() WHERE id = %s",
                    (row["id"],),
                )
            except Exception as exc:
                await db.execute(
                    "UPDATE otp_outbox SET status = 'error', error_message = %s WHERE id = %s",
                    (str(exc)[:255], row["id"]),
                )
        await asyncio.sleep(5)


async def main() -> None:
    logging.basicConfig(level=logging.INFO)
    config = get_config()
    if not config["bot"]["token"]:
        raise RuntimeError("BOT_TOKEN is required")

    db = Database(config)
    await db.connect()
    await ensure_admin(db, config)

    bot = Bot(token=config["bot"]["token"])
    dp = Dispatcher()

    dp.include_router(start.router)
    dp.include_router(manage_cars.router)
    dp.include_router(search.router)

    dp["db"] = db

    worker = asyncio.create_task(otp_worker(bot, db))
    try:
        await dp.start_polling(bot, db=db)
    finally:
        worker.cancel()
        await db.close()


if __name__ == "__main__":
    asyncio.run(main())
