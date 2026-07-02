# Time Log Management

A Laravel application for tracking daily work time against projects and managing employee leave. Users log time entries per project per day (with a 10-hour daily cap), apply for leave within the current calendar year, and browse both with search, sorting, and pagination.

## Features

- **Authentication** — email/password auth, registration, password reset, email verification, and profile management (Laravel Breeze, Blade + Tailwind).
- **Time logging**
  - Log tasks against active projects for a given work date.
  - Flexible time input — `2:30`, `2h30m`, `2.5h`, `30m`, or a bare `2` (treated as 2 hours). Parsed and validated by `App\ValueObjects\Duration`.
  - Enforced **10h/day cap** per user, computed server-side in a locked transaction (`App\Services\TimeLogService`).
  - Blocks logging on dates the user has leave for.
  - Per-day listing with search (project/description), sortable columns, and pagination (10/25/50 per page).
- **Leave management**
  - Apply for leave with start/end dates, constrained to the **current calendar year**.
  - Overlap detection prevents double-booking; end date cannot precede start date (enforced both client-side and in `StoreLeaveRequest`).
  - Listing with date-range filter, sortable columns, and pagination.
- **Flash + validation feedback** — success/error banners and inline field errors on all forms.

## Tech Stack

| Layer     | Technology                            |
|-----------|---------------------------------------|
| Backend   | PHP 8.3+, Laravel 13                   |
| Auth/UI   | Laravel Breeze (Blade)                |
| Frontend  | Tailwind CSS 3, Vite 8, vanilla JS    |
| Database  | MySQL 5.7+ / 8.x (MariaDB 10.3+ works)|
| Testing   | PHPUnit 12       |

Architecture: thin controllers → **Service** layer (business rules) → **Repository** layer (data access). Value objects (`Duration`) encapsulate parsing/formatting.

## Minimum Server Requirements

- **PHP** ≥ 8.3 with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- **Composer** ≥ 2.x
- **Node.js** ≥ 20 and **npm** ≥ 10 (for asset build via Vite)
- **MySQL** ≥ 5.7 (or MariaDB ≥ 10.3)
- ~256 MB RAM minimum for local dev; a standard PHP-FPM + Nginx/Apache stack for production

## Setup

```bash
# 1. Clone and enter
git clone <repo-url> time-log-management
cd time-log-management

# 2. Install PHP dependencies
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Configure the database in .env (defaults shown)
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=timev1
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Create the database, then migrate + seed sample projects
php artisan migrate --seed

# 6. Install and build front-end assets
npm install
npm run build
```

> Shortcut: `composer run setup` runs install, env, key generation, migrate, npm install, and build in one command.

## Running (development)

```bash
# All-in-one: serve + queue + logs + Vite (hot reload)
composer run dev
```

Or run the pieces individually:

```bash
php artisan serve      # app at http://localhost:8000
npm run dev            # Vite dev server (asset hot reload)
php artisan pail       # live log tail
```

## Common Commands

| Command                            | Purpose                              |
|------------------------------------|--------------------------------------|
| `php artisan migrate`              | Run database migrations              |
| `php artisan migrate:fresh --seed` | Rebuild schema and reseed            |
| `php artisan db:seed`              | Seed sample projects                 |
| `composer run test`                | Clear config + run the test suite    |
| `php artisan test`                 | Run PHPUnit tests                    |
| `./vendor/bin/pint`                | Format code (Laravel Pint)           |
| `php artisan optimize:clear`       | Clear all caches (config/route/view) |
| `npm run build`                    | Compile production assets            |

## Key Routes

| Method   | URI          | Name          | Description                |
|----------|--------------|---------------|----------------------------|
| GET      | `/dashboard` | `dashboard`   | Landing after login        |
| GET/POST | `/time-logs` | `time-logs.*` | List + create time entries |
| GET/POST | `/leaves`    | `leaves.*`    | List + apply for leave     |
| GET/…    | `/profile`   | `profile.*`   | Edit/delete account        |

All application routes require authentication (`auth`); time-log and leave routes also require a verified email.
