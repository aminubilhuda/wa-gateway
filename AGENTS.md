# AGENTS.md - WA-BLAST

## Project Overview

Laravel 13 WhatsApp blast/campaign management app with a companion Node.js self-hosted gateway (`whatsapp-gateway/`). Supports two WhatsApp sending modes: **Fonnte cloud API** (default) or **self-hosted gateway** via whatsapp-web.js (QR-code based).

## Two-Service Architecture

| Service | Dir | Stack | Purpose |
|---------|-----|-------|---------|
| Laravel Web App | root | PHP 8.3+, Laravel 13, SQLite | UI, contacts, campaigns, auto-reply, scheduling |
| WhatsApp Gateway | `whatsapp-gateway/` | Node.js, Express, whatsapp-web.js | Self-hosted WhatsApp via Puppeteer + QR auth |

The Laravel app talks to WhatsApp through `FonnteService` (`app/Services/FonnteService.php`). It dynamically picks the base URL from the `Device` model's `gateway_url` or falls back to `WHATSAPP_GATEWAY_URL` env (`https://api.fonnte.com`). The self-hosted gateway exposes `/device`, `/qr`, `/send`, `/disconnect` endpoints that mimic Fonnte's API shape.

## Key Commands

```bash
# Full dev stack (server + queue worker + log tail + Vite HMR)
composer run dev

# Run tests
composer run test

# Initial setup (install deps, generate key, migrate, build assets)
composer run setup

# Laravel artisan (any command)
php artisan <command>

# Start self-hosted gateway (separate terminal)
cd whatsapp-gateway && npm start
```

## Database & Migrations

- **DB**: SQLite (`database/database.sqlite`). Queue and session also use database driver.
- Run `php artisan migrate` after pulling new migrations.
- Models: `User`, `Device`, `Contact`, `Campaign`, `MessageLog`, `AutoReply`, `MessageTemplate`, `Blacklist`.

## Campaign Scheduler

- Registered in `routes/console.php` — runs **every minute** via `Schedule::call()`.
- Requires `php artisan schedule:work` or a system cron (`* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`).
- The `composer run dev` command starts the queue listener but **not** the scheduler — run `php artisan schedule:work` separately if testing recurring campaigns.
- Campaign types: `once`, `daily`, `weekly`, `monthly`, `minute`, `hour`.
- Phone numbers are normalized to Indonesian format (`62` prefix). Numbers starting with `0` → `62`, `8` → `628`, `+` stripped.

## Webhooks

- Public endpoints: `POST /webhook/fonnte/device` and `POST /webhook/fonnte/message`
- The self-hosted gateway posts to these webhooks to report device status and forward incoming messages for auto-reply processing.
- `LARAVEL_WEBHOOK_URL` env in the gateway defaults to `http://127.0.0.1:8000/webhook/fonnte`.

## Env Quirks

- `.env` currently has **duplicated content** (the entire file appears twice). The `FONNTE_TOKEN` and `WHATSAPP_GATEWAY_URL` keys are at the bottom of the second copy. Clean this up if modifying.
- `QUEUE_CONNECTION=database` — jobs table must exist (provided by default Laravel migration).
- Timezone: `Asia/Jakarta`.

## Testing

- PHPUnit 12, config in `phpunit.xml`.
- Tests use `:memory:` SQLite, `sync` queue, `array` cache/session.
- Run: `composer run test` or `php artisan test`.
- Feature tests cover: dashboard, contacts, campaigns, auto-reply, message logs, advanced features.

## Code Style

- Laravel Pint for formatting: `vendor/bin/pint`.
- No custom Pint config — uses Laravel defaults.

## Directory Boundaries

- `app/Http/Controllers/` — all controllers (auth, dashboard, contacts, campaigns, reports, auto-reply, settings, templates, blacklist, webhook).
- `app/Services/FonnteService.php` — **single service**, handles all WhatsApp API communication.
- `app/Models/` — Eloquent models (all use `$guarded = []` or standard Laravel patterns).
- `resources/views/` — Blade templates (dashboard, contacts, campaigns, reports, auto-reply, settings, templates, blacklist).
- `whatsapp-gateway/server.js` — Express server, single file. Auth stored in `whatsapp-gateway/.wwebjs_auth/`.
- `routes/web.php` — web routes + public webhook routes.
- `routes/console.php` — scheduled campaign dispatcher.
