# Installation & Upgrading — Numa Log

## Table of Contents

1. [Requirements](#1-requirements)
2. [Installation](#2-installation)
3. [Default Login](#3-default-login)
4. [Upgrading](#4-upgrading)

---

## 1. Requirements

**Option A: Docker (Recommended)**
- Docker & Docker Compose

**Option B: Manual**
- PHP 8.2+
- Composer
- Apache with mod_rewrite (XAMPP recommended)
- PHP extensions: `pdo_sqlite`, `mbstring`, `zip`, `gd`

---

## 2. Installation

### Option A: Docker (Recommended)

#### Quick Start

```bash
docker compose up -d
```

Open browser at **http://localhost:8080**

#### Custom Port

Change the port mapping in `docker-compose.yml`:

```yaml
ports:
  - "3000:80"   # Change 8080 to any port you want
```

Then run:

```bash
docker compose up -d
```

#### Manage

```bash
# Start
docker compose up -d

# Stop
docker compose down

# View logs
docker compose logs -f

# Rebuild after code changes
docker compose up -d --build

# Reset everything (WARNING: deletes all data)
docker compose down -v
```

#### Data Persistence

Docker stores data in a named volume `app-data`. This persists across container restarts and rebuilds.

- Database: `data/database.sqlite`
- Backups: `data/backups/`

To back up data from Docker:

```bash
# Copy database out of container
docker cp numa-log:/var/www/html/data/database.sqlite ./backup.sqlite

# Copy database into container
docker cp ./backup.sqlite numa-log:/var/www/html/data/database.sqlite
```

---

### Option B: Manual (XAMPP / PHP Built-in Server)

#### 1. Clone the repository

```bash
git clone https://github.com/<your-username>/numa-log.git
cd numa-log
```

#### 2. Install dependencies

```bash
composer install
```

This installs `phpoffice/phpspreadsheet` for Excel import functionality.

#### 3. Access the application

If using XAMPP, place the project under the document root:

```
C:\xampp\htdocs\numa-log\
```

Open your browser and navigate to:

```
http://localhost/numa-log/
```

Or use PHP built-in server:

```bash
php -S localhost:8080
```

The SQLite database (`database.sqlite`) and all tables will be created automatically on first load.

---

## 3. Default Login

| Username | Password | Role  |
|----------|----------|-------|
| `admin`  | `admin`  | Admin |

> **Important:** Change the default password after first login via the Users page.

---

## 4. Upgrading

### Before You Upgrade

> **Always create a backup before upgrading.** Go to the **Backup** page and click **Create Backup**, or use the Docker/file copy method below.

Database schema changes (new tables, columns, indexes) are applied automatically via `IF NOT EXISTS` statements on first page load — no manual migration needed.

---

### Option A: Docker

```bash
# 1. Pull the latest code
git pull

# 2. Rebuild and restart the container
docker compose up -d --build
```

Your data is stored in the `app-data` Docker volume and is not affected by a rebuild.

**To back up data before upgrading:**

```bash
docker cp numa-log:/var/www/html/data/database.sqlite ./backup-before-upgrade.sqlite
```

---

### Option B: Manual (XAMPP)

```bash
# 1. Pull the latest code
git pull

# 2. Re-install/update Composer dependencies
composer install
```

If you placed the project under XAMPP's `htdocs`, just refresh the browser — schema updates are applied automatically.

**To back up data before upgrading:** copy your `database.sqlite` (or `data/database.sqlite`) file to a safe location before pulling.

---

### Version-Specific Notes

| Upgrading to | Notes |
|---|---|
| **v1.3.9** | No manual steps required. JavaScript-only bug fix; no schema changes. |
| **v1.3.8** | No manual steps required. CSS-only changes; no schema changes. |
| **v1.3.7** | No manual steps required. The `login_attempts` table is created automatically on first load. Existing passwords shorter than 12 characters remain valid until changed. Any user whose password is still `admin` will be forced to change it on next login. |
| **v1.3.x** | No manual steps required. SQLite indexes and PRAGMA settings are applied automatically on first load. |
| **v1.1.0** | CSRF protection was added. If you have custom forms or scripts that POST to `api.php`, add a `csrf_token` field. |
| **v1.0.0** | Initial release — fresh install only. |
