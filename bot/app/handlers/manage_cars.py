from aiogram import Router, F
from aiogram.fsm.context import FSMContext
from aiogram.types import Message, CallbackQuery

from app.keyboards import cancel_keyboard, main_menu, delete_button
from app.plates import normalize_plate
from app.rbac import can_manage
from app.states import AddCar, DeleteCar

router = Router()

async def get_user(db, tg_id: int):
    return await db.fetchone("SELECT * FROM users WHERE tg_id = %s AND status = 'active'", (tg_id,))


async def cancel_flow(message: Message, state: FSMContext, db) -> None:
    await state.clear()
    user = await get_user(db, message.from_user.id)
    if not user:
        return
    await message.answer("Действие отменено.", reply_markup=main_menu(can_manage(user["role"])))


@router.message(F.text == "Добавить автомобиль")
async def add_car_start(message: Message, state: FSMContext, db):
    user = await get_user(db, message.from_user.id)
    if not user or not can_manage(user["role"]):
        return
    await state.set_state(AddCar.model)
    await message.answer("Введите модель автомобиля:", reply_markup=cancel_keyboard())


@router.message(AddCar.model)
async def add_car_model(message: Message, state: FSMContext, db):
    if message.text == "Отмена":
        await cancel_flow(message, state, db)
        return
    await state.update_data(car_model=message.text.strip())
    await state.set_state(AddCar.number)
    await message.answer("Введите номер автомобиля:", reply_markup=cancel_keyboard())


@router.message(AddCar.number)
async def add_car_number(message: Message, state: FSMContext, db):
    if message.text == "Отмена":
        await cancel_flow(message, state, db)
        return
    await state.update_data(car_number=normalize_plate(message.text))
    await state.set_state(AddCar.comment)
    await message.answer("Комментарий (или -):", reply_markup=cancel_keyboard())


@router.message(AddCar.comment)
async def add_car_comment(message: Message, state: FSMContext, db):
    if message.text == "Отмена":
        await cancel_flow(message, state, db)
        return
    data = await state.get_data()
    user = await get_user(db, message.from_user.id)
    if not user or not can_manage(user["role"]):
        await state.clear()
        return
    comment = message.text.strip()
    if comment == "-":
        comment = ""
    await db.execute(
        "INSERT INTO cars (car_model, car_number, comment, who_added, date_added) VALUES (%s, %s, %s, %s, NOW())",
        (data["car_model"], data["car_number"], comment, user["id"]),
    )
    await state.clear()
    await message.answer("Автомобиль добавлен.", reply_markup=main_menu(can_manage(user["role"])))


@router.message(F.text == "Удалить автомобиль")
async def delete_car_start(message: Message, state: FSMContext, db):
    user = await get_user(db, message.from_user.id)
    if not user or not can_manage(user["role"]):
        return
    await state.set_state(DeleteCar.query)
    await message.answer("Введите номер или часть номера:", reply_markup=cancel_keyboard())


@router.message(DeleteCar.query)
async def delete_car_query(message: Message, state: FSMContext, db):
    if message.text == "Отмена":
        await cancel_flow(message, state, db)
        return
    query = normalize_plate(message.text)
    results = await db.fetchall(
        "SELECT id, car_model, car_number, comment FROM cars WHERE car_number LIKE %s ORDER BY date_added DESC LIMIT 10",
        (f"%{query}%",),
    )
    if not results:
        await message.answer("Совпадений не найдено.")
        return
    for row in results:
        text = f"{row['car_number']} | {row['car_model']} | {row.get('comment') or '-'}"
        await message.answer(text, reply_markup=delete_button(row["id"]))


@router.callback_query(lambda c: c.data and c.data.startswith("del:"))
async def delete_car_callback(callback: CallbackQuery, db):
    user = await get_user(db, callback.from_user.id)
    if not user or not can_manage(user["role"]):
        await callback.answer("Недостаточно прав", show_alert=True)
        return
    car_id = int(callback.data.split(":", 1)[1])
    car = await db.fetchone("SELECT * FROM cars WHERE id = %s", (car_id,))
    if not car:
        await callback.answer("Не найдено", show_alert=True)
        return
    await db.transaction([
        (
            "INSERT INTO remote_cars (car_model, car_number, comment, who_added, date_added, who_deleted, date_deleted) VALUES (%s, %s, %s, %s, %s, %s, NOW())",
            (
                car["car_model"],
                car["car_number"],
                car.get("comment"),
                car.get("who_added"),
                car["date_added"],
                user["id"],
            ),
        ),
        ("DELETE FROM cars WHERE id = %s", (car_id,)),
    ])
    await callback.answer("Удалено", show_alert=True)
    await callback.message.edit_reply_markup()
