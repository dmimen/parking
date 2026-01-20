from aiogram.types import KeyboardButton, ReplyKeyboardMarkup, InlineKeyboardButton, InlineKeyboardMarkup


def contact_keyboard() -> ReplyKeyboardMarkup:
    return ReplyKeyboardMarkup(
        keyboard=[[KeyboardButton(text="Отправить телефон", request_contact=True)]],
        resize_keyboard=True,
        one_time_keyboard=True,
    )


def main_menu(can_manage: bool) -> ReplyKeyboardMarkup:
    buttons = [[KeyboardButton(text="Поиск")]]
    if can_manage:
        buttons.append([KeyboardButton(text="Добавить автомобиль")])
        buttons.append([KeyboardButton(text="Удалить автомобиль")])
    return ReplyKeyboardMarkup(keyboard=buttons, resize_keyboard=True)


def cancel_keyboard() -> ReplyKeyboardMarkup:
    return ReplyKeyboardMarkup(
        keyboard=[[KeyboardButton(text="Отмена")]],
        resize_keyboard=True,
        one_time_keyboard=True,
    )


def delete_button(car_id: int) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup(inline_keyboard=[[
        InlineKeyboardButton(text="Удалить", callback_data=f"del:{car_id}")
    ]])
