# Parking System (PHP + Aiogram + MariaDB)

## Requirements
- Docker + Docker Compose

## Setup
1. Copy `.env.example` to `.env` and fill in values:
   - `BOT_TOKEN`
   - `ADM_PHONE`, `ADM_NAME`

2. Start containers:

```bash
docker compose up -d --build
```

Web UI: http://localhost:8080

Optional Adminer (profile):

```bash
docker compose --profile debug up -d
```

## Features
- OTP login via Telegram bot (8 chars, TTL 60s, 10 attempts → 15 min block)
- RBAC roles: admin, manager, guard
- Cars CRUD with soft-delete history (remote_cars)
- Telegram bot search and car management

## Quick checks
1. Run the bot, send `/start`, share phone to link Telegram.
2. On web login page enter the same phone to get OTP.
3. Search and manage cars based on role.

## Notes
- Admin is auto-created/updated from env on startup.
- OTP delivery uses the `otp_outbox` table polled by the bot.
