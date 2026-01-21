from aiogram.fsm.state import State, StatesGroup


class AddCar(StatesGroup):
    model = State()
    number = State()
    comment = State()


class DeleteCar(StatesGroup):
    query = State()
