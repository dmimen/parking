import re
from aiogram import Router
from aiogram.types import Message

router = Router()


def normalize_number(text: str) -> str:
    return re.sub(r"\s+", "", text.upper())


@router.message(lambda m: m.text and m.text not in {"Добавить автомобиль", "Удалить автомобиль", "Отмена"})
async def search_handler(message: Message, db):
    user = await db.fetchone("SELECT * FROM users WHERE tg_id = %s AND status = 'active'", (message.from_user.id,))
    if not user:
        return
    query = normalize_number(message.text)
    if len(query) < 2:
        return
    results = await db.fetchall(
        "SELECT car_model, car_number, comment, date_added FROM cars WHERE car_number LIKE %s ORDER BY date_added DESC LIMIT 10",
        (f"%{query}%",),
    )
    if not results:
        await message.answer("Совпадений не найдено.")
        return
    lines = []
    for row in results:
        lines.append(
            f"{row['car_number']} | {row['car_model']} | {row.get('comment') or '-'} | {row['date_added']}"
        )
    await message.answer("\n".join(lines))
