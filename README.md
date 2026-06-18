# Translation Management Service — Setup Guide

A Laravel 13 / PHP 8.4 API for managing translations across locales, tags, and
contexts. Runs fully in Docker (app, nginx, MySQL, Redis, Mailpit, queue worker).

## Requirements

- **Docker** (Desktop) with **Docker Compose v2** (`docker compose ...`)
- **Git**
- Free local ports: `8080` (API), `3306` (MySQL), `6379` (Redis)

No local PHP / Composer / MySQL needed — everything runs in containers.

## Services

| Service     | Container    | Purpose                              | Host port |
|-------------|--------------|--------------------------------------|-----------|
| `nginx`     | `tms-nginx`  | Web entrypoint                       | 8080      |
| `app`       | `tms-app`    | PHP-FPM (Laravel)                    | —         |
| `queue`     | `tms-queue`  | `php artisan queue:work` (always on) | —         |
| `mysql`     | `tms-mysql`  | Database                             | 3306      |
| `redis`     | `tms-redis`  | Cache + cache tags                   | 6379      |

The **queue worker starts automatically** with the containers and processes
background jobs (batch create/update).

## Step-by-step

### 1. Clone

```bash
git clone <repo-url> TranslationManagementService
cd TranslationManagementService
```

### 2. Environment file

```bash
cp .env.example .env   # skip if .env already present
```

Defaults already point at the Docker service hostnames (`mysql`, `redis`).

### 3. Build & start containers

```bash
docker compose up -d --build
```

First run builds the PHP image and waits for MySQL to be healthy.

### 4. Install PHP dependencies

```bash
docker compose exec app composer install
```

### 5. App key

```bash
docker compose exec app php artisan key:generate
```

### 6. Run migrations

```bash
docker compose exec app php artisan migrate
```

### 7. Seed a test user + API token

```bash
docker compose exec app php artisan db:seed --class=TestUserSeeder
```

Copy the printed `token:` — all `/api/*` routes require it.

### 8. Use the API

Base URL: `http://localhost:8080`

```bash
curl http://localhost:8080/api/translations \
  -H "Authorization: Bearer <token>"
```

All API requests/responses are JSON (the `Accept: application/json` header is
forced server-side).

## API endpoints (all under `auth:sanctum`)

| Method      | Path                              | Description                          |
|-------------|-----------------------------------|--------------------------------------|
| GET         | `/api/translations`               | List, paginated (`per_page` ≤ 100)   |
| GET         | `/api/translations/id/{id}`       | Single record                        |
| GET         | `/api/translations/tag/{tag}`     | By tag, paginated                    |
| GET         | `/api/translations/search?q=`     | String search (key/tags/content)     |
| POST        | `/api/translations`               | Create                               |
| PUT/PATCH   | `/api/translations/{id}`          | Update                               |
| POST        | `/api/translations/batch`         | Batch create (queued)                |
| PUT/PATCH   | `/api/translations/batch`         | Batch update (queued)                |

Tags are limited to: `mobile`, `desktop`, `web`.

## Running tests

```bash
docker compose exec app php artisan test
```

Tests use an in-memory SQLite database — they do not touch MySQL.

## Useful commands

```bash
# Tail logs
docker compose logs -f app
docker compose logs -f queue

# Watch queued jobs being processed
docker compose logs -f queue

# Stop everything
docker compose down

# Stop and wipe data volumes
docker compose down -v
```

## Troubleshooting

- **`This cache store does not support tagging`** — config was cached with a
  non-tagging store. Clear it: `docker compose exec app php artisan config:clear`.
  `.env` must have `CACHE_STORE=redis`.
- **Queue jobs not running** — check `docker compose ps`; `tms-queue` should be
  `Up`. View logs: `docker compose logs -f queue`. Restart: `docker compose restart queue`.
- **Code changes to jobs** — the worker holds code in memory; restart it after
  changing job/action code: `docker compose restart queue`.
