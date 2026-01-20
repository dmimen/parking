import re
from aiogram import Router
from aiogram.filters import CommandStart
from aiogram.types import Message

from app.keyboards import contact_keyboard, main_menu
from app.rbac import can_manage

router = Router()


def normalize_phone(phone: str) -> str:
    return re.sub(r"\D+", "", phone)


@router.message(CommandStart())
async def start_handler(message: Message, db):
    await message.answer(
        "Отправьте номер телефона для привязки аккаунта.",
        reply_markup=contact_keyboard(),
    )


@router.message(lambda m: m.contact is not None)
async def contact_handler(message: Message, db):
    phone = normalize_phone(message.contact.phone_number)
    user = await db.fetchone("SELECT * FROM users WHERE phone = %s AND status = 'active'", (phone,))
    if not user:
        await message.answer("Пользователь не найден или заблокирован.")
        return
    await db.execute("UPDATE users SET tg_id = %s WHERE id = %s", (message.from_user.id, user["id"]))
    await message.answer(
        f"Привязка выполнена. Роль: {user['role']}",
        reply_markup=main_menu(can_manage(user["role"])),
    )


@router.message(lambda m: m.text and re.match(r"^\+?\d{7,}", m.text))
async def phone_text_handler(message: Message, db):
    phone = normalize_phone(message.text)
    user = await db.fetchone("SELECT * FROM users WHERE phone = %s AND status = 'active'", (phone,))
    if not user:
        await message.answer("Пользователь не найден или заблокирован.")
        return
    await db.execute("UPDATE users SET tg_id = %s WHERE id = %s", (message.from_user.id, user["id"]))
    await message.answer(
        f"Привязка выполнена. Роль: {user['role']}",
        reply_markup=main_menu(can_manage(user["role"])),
    )
