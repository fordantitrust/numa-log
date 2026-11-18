# Installation & Upgrading — Numa Log

## Table of Contents

1. [Requirements](#1-requirements)
2. [Installation](#2-installation)
3. [Default Login](#3-default-login)
4. [Upgrading](#4-upgrading)

---

## 1. Requirements

**Option A: Docker — Pre-built image from GHCR (Recommended)**
- Docker & Docker Compose
- No build tools needed — the image is built by GitHub Actions

**Option B: Docker — Build locally**
- Docker & Docker Compose

**Option C: Manual**
- PHP 8.2+
- Composer
- Apache with mod_rewrite (XAMPP recommended)
- PHP extensions: `pdo_sqlite`, `mbstring`, `zip`, `gd`

---

## 2. Installation

### Option A: Docker — Pre-built image from GHCR (Recommended)

Every push to `master` triggers a GitHub Actions workflow that builds the Docker
image and publishes it to the **GitHub Container Registry (GHCR)**. You can pull
this image directly instead of building it yourself.

Image: `ghcr.io/fordantitrust/numa-log:latest`

#### Quick Start

Create a `docker-compose.yml` (or use the one provided, replacing `build: .`
with the `image:` line below):

```yaml
services:
  app:
    image: ghcr.io/fordantitrust/numa-log:latest
    container_name: numa-log
    ports:
      - "8080:80"
    volumes:
      - app-data:/var/www/html/data
    restart: unless-stopped
    environment:
      - TZ=Asia/Bangkok

volumes:
  app-data:
```

Then pull and start:

```bash
docker compose pull
docker compose up -d
```

Open browser at **http://localhost:8080**

> If the GHCR package is **private**, log in first:
> ```bash
> echo <YOUR_GITHUB_TOKEN> | docker login ghcr.io -u <your-username> --password-stdin
> ```
> The token needs the `read:packages` scope. Public packages need no login.

Alternatively, run without Compose:

```bash
docker run -d --name numa-log -p 8080:80 \
  -v numa-log-data:/var/www/html/data \
  -e TZ=Asia/Bangkok \
  --restart unless-stopped \
  ghcr.io/fordantitrust/numa-log:latest
```

---

### Option B: Docker — Build locally

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

### Option C: Manual (XAMPP / PHP Built-in Server)

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

> **Important:** While the password is still the default `admin`, every login redirects to a forced password-change screen. The new password needs at least 12 characters. After 5 failed logins, an IP address is blocked for 15 minutes.

---

## 4. Upgrading

### Before You Upgrade

> **Always create a backup before upgrading.** Go to the **Backup** page and click **Create Backup**, or use the Docker/file copy method below.

Database schema changes are applied automatically on the first page load after an upgrade, so you never run a migration by hand. Each pending migration in `migrations/` runs inside a transaction. Before it runs, a snapshot is written to `data/backups/pre-v{N}-{timestamp}.sqlite` (or `backups/` on a manual install). The current schema version is **v13**. See [migrations/README.md](migrations/README.md).

To check the schema version from the command line:

```bash
php migrate.php                         # manual install
docker exec numa-log php migrate.php    # Docker
```

---

### Option A: Docker — Pre-built image from GHCR

```bash
# 1. Pull the latest published image
docker compose pull

# 2. Recreate the container with the new image
docker compose up -d
```

No `git pull` or rebuild needed — you get whatever GitHub Actions last published
to `ghcr.io/fordantitrust/numa-log:latest`. Your data in the `app-data` volume is
untouched.

---

### Option B: Docker — Build locally

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

### Option C: Manual (XAMPP)

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
| **v1.13.0** | Schema **v13** (auto): new `item_presets` table. No manual steps. |
| **v1.12.1** | Dependency update only (`phpoffice/phpspreadsheet` 5.10.0). Run `composer install` (manual) or rebuild/pull the image. |
| **v1.12.0** | No schema change. The report date range is stored in the browser, so there is nothing to migrate. |
| **v1.11.0** | No schema change. |
| **v1.10.0** | Schema **v12** (auto): `type_categories.exclude_from_reports`. Existing types default to *not excluded*, so totals are unchanged until you flag a type. |
| **v1.9.18** | Composer platform pinned to PHP 8.2. On a manual install, run `composer install` again. |
| **v1.9.16** | Schema **v11** (auto): `events.is_not_attended`. |
| **v1.9.15** | Schema **v10** (auto): `type_categories.is_ticket` + `events.is_free_entry`. Flag at least one type as **Is Ticket Type** to turn on ticket detection. |
| **v1.9.9** | Schema **v9** (auto): `events.end_date`. Existing events stay single-day. |
| **v1.9.7** | Schema **v8** (auto): `events` table + `items.event_id`. Use **Auto-assign** on the Events page to link old items. |
| **v1.8.0** | Schema **v6 + v7** (auto): `budgets` table with per-month `period` overrides. |
| **v1.6.0** | `index.php` is now the Dashboard and the item list moved to `items.php`. Update any bookmarks. |
| **v1.5.0** | Schema **v5** (auto): membership history + ID-based idol references. Ambiguous idol names show up in the **Ambiguous Mappings** panel on the Idols page, where you can resolve them. |
| **v1.4.0** | No manual steps required. JavaScript-only formatting changes; no schema changes. All currency amounts now display with 2 decimal places. |
| **v1.3.11** | No manual steps required. Timezone fix only; no schema changes. Rebuild Docker image with `docker compose up -d --build`. |
| **v1.3.10** | No manual steps required. Docker-only changes; no schema or application code changes. Rebuild image with `docker build -t numa-log:1.3.10 .` |
| **v1.3.9** | No manual steps required. JavaScript-only bug fix; no schema changes. |
| **v1.3.8** | No manual steps required. CSS-only changes; no schema changes. |
| **v1.3.7** | No manual steps required. The `login_attempts` table is created automatically on first load. Existing passwords shorter than 12 characters remain valid until changed. Any user whose password is still `admin` will be forced to change it on next login. |
| **v1.3.x** | No manual steps required. SQLite indexes and PRAGMA settings are applied automatically on first load. |
| **v1.1.0** | CSRF protection was added. If you have custom forms or scripts that POST to `api.php`, add a `csrf_token` field. |
| **v1.0.0** | Initial release — fresh install only. |
