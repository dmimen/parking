from aiogram import Router
from aiogram.filters import CommandStart
from aiogram.types import Message

from app.keyboards import contact_keyboard, main_menu
from app.rbac import can_manage

router = Router()


def normalize_phone(phone: str) -> str:
    digits = "".join(ch for ch in phone if ch.isdigit())
    if not digits:
        return ""
    if len(digits) == 10:
        return "7" + digits
    if len(digits) == 11 and digits[0] in {"7", "8"}:
        return "7" + digits[1:]
    return digits


@router.message(CommandStart())
async def start_handler(message: Message, db):
    await message.answer(
        "Для привязки аккаунта отправьте контакт через кнопку ниже.",
        reply_markup=contact_keyboard(),
    )


@router.message(lambda m: m.contact is not None)
async def contact_handler(message: Message, db, config):
    # Принимаем только контакт владельца (Telegram передает user_id владельца).
    if message.contact.user_id != message.from_user.id:
        await message.answer("Нужно отправить свой контакт через кнопку ниже.")
        return
    phone = normalize_phone(message.contact.phone_number)
    user = await db.fetchone("SELECT * FROM users WHERE phone = %s AND status = 'active'", (phone,))
    if not user:
        await message.answer("Пользователь не найден или заблокирован.")
        return
    await db.execute("UPDATE users SET tg_id = %s WHERE id = %s", (message.from_user.id, user["id"]))
    web_url = config.get("web_url", "http://localhost:8080")
    await message.answer(
        "Регистрация завершена — ваш Telegram привязан к учетной записи.\n\n"
        "Что можно делать:\n\n"
        "Поиск авто: отправьте госномер целиком или его часть — я покажу совпадения (марка, номер, комментарий).\n"
        "Пример: А123ВС77 или 123ВС.\n\n"
        "Вход: для авторизации перейдите на сайт\n"
        f"{web_url}\n"
        "Введите номер телефона и подтвердите вход одноразовым кодом, который я пришлю сюда.\n"
        "Код действует 60 секунд.\n",
        reply_markup=main_menu(can_manage(user["role"])),
    )
