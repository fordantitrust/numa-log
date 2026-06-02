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

> **Important:** Change the default password after first login via the Users page.

---

## 4. Upgrading

### Before You Upgrade

> **Always create a backup before upgrading.** Go to the **Backup** page and click **Create Backup**, or use the Docker/file copy method below.

Database schema changes (new tables, columns, indexes) are applied automatically via `IF NOT EXISTS` statements on first page load — no manual migration needed.

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
| **v1.4.0** | No manual steps required. JavaScript-only formatting changes; no schema changes. All currency amounts now display with 2 decimal places. |
| **v1.3.11** | No manual steps required. Timezone fix only; no schema changes. Rebuild Docker image with `docker compose up -d --build`. |
| **v1.3.10** | No manual steps required. Docker-only changes; no schema or application code changes. Rebuild image with `docker build -t numa-log:1.3.10 .` |
| **v1.3.9** | No manual steps required. JavaScript-only bug fix; no schema changes. |
| **v1.3.8** | No manual steps required. CSS-only changes; no schema changes. |
| **v1.3.7** | No manual steps required. The `login_attempts` table is created automatically on first load. Existing passwords shorter than 12 characters remain valid until changed. Any user whose password is still `admin` will be forced to change it on next login. |
| **v1.3.x** | No manual steps required. SQLite indexes and PRAGMA settings are applied automatically on first load. |
| **v1.1.0** | CSRF protection was added. If you have custom forms or scripts that POST to `api.php`, add a `csrf_token` field. |
| **v1.0.0** | Initial release — fresh install only. |
