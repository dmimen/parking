# Parking System (PHP + Aiogram + MariaDB)

## Требования
- Docker
- Docker Compose
- Токен Telegram-бота (BOT_TOKEN)

## Быстрый старт
1. Скопируйте `.env.example` в `.env` и заполните значения:

```bash
cp .env.example .env
```

2. Укажите обязательные параметры в `.env`:
- `BOT_TOKEN` — токен бота из BotFather
- `ADM_PHONE` — телефон администратора (только цифры)
- `ADM_NAME` — имя администратора
- `APP_SECRET` — длинный случайный секрет

3. Запустите контейнеры:

```bash
docker compose up -d --build
```

Web UI: http://localhost:8080

## Подробная инструкция по установке
1. **Подготовка окружения**
   - Убедитесь, что Docker и Docker Compose установлены и запущены.
   - Создайте файл `.env` из шаблона `.env.example`.

2. **Настройка переменных окружения**
   - Проверьте параметры БД:
     - `DB_HOST=db`
     - `DB_NAME=parking`
     - `DB_USER=parking`
     - `DB_PASSWORD=parking_pass`
     - `DB_PORT=3306`
   - Заполните параметры Web:
     - `WEB_BASE_URL=http://localhost:8080`
     - `APP_SECRET=...`
     - `SESSION_COOKIE_NAME=park_sess`
   - Заполните параметры Bot:
     - `BOT_TOKEN=...`
   - Заполните администратора:
     - `ADM_PHONE=...`
     - `ADM_NAME=...`

3. **Запуск**
   - Соберите и запустите сервисы:

```bash
docker compose up -d --build
```

4. **Проверка**
   - Откройте http://localhost:8080
   - Войдите через телефон администратора, получите OTP в Telegram.

## Дополнительно
### Adminer (опционально)
Запуск профиля для отладки БД:

```bash
docker compose --profile debug up -d
```

Adminer: http://localhost:8081

## Основные сценарии
1. **Привязка Telegram**
   - Откройте чат с ботом.
   - Введите `/start` и отправьте телефон.

2. **Авторизация по OTP**
   - Введите телефон на `/login.php`.
   - Получите OTP в Telegram.
   - Введите OTP на `/otp.php`.

3. **Работа с автомобилями**
   - `admin`/`manager`: добавление и удаление машин.
   - `guard`: только просмотр и поиск.
   - Удаление переносит запись в `remote_cars`.

4. **Поиск**
   - Веб: динамический поиск на странице `cars.php`.
   - Бот: отправьте номер или его часть.

## Примечания
- Администратор создается/обновляется из `.env` при старте.
- OTP: длина 8 символов, TTL 60 сек, 10 ошибок → блок 15 мин.
- Отправка OTP — через таблицу `otp_outbox`, бот опрашивает очередь.
