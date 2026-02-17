# Numa Log

Web-based application for managing idol merchandise purchase data. Built with PHP 8.2, SQLite, Bootstrap 5, and Chart.js.

## Requirements

**Option A: Docker (Recommended)**
- Docker & Docker Compose

**Option B: Manual**
- PHP 8.2+
- Composer
- Apache with mod_rewrite (XAMPP recommended)
- PHP extensions: `pdo_sqlite`, `mbstring`, `zip`, `gd`

## Installation

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

### Default Login

| Username | Password | Role  |
|----------|----------|-------|
| `admin`  | `admin`  | Admin |

**Important:** Change the default password after first login via Users page.

## Features

### Item Management (`index.php`)

- Full CRUD for purchase items (Add, Edit, Clone, Delete)
- Searchable dropdown for Idol and Type fields
- Sortable columns, pagination, filters (by Idol, Type, date range, search)
- Summary cards showing total items, quantity, and spending

### Reports (`report.php`)

| Tab | Description |
|-----|-------------|
| **Monthly** | Bar + line chart of monthly spending & quantity. Click any month to drill down to daily view. |
| **By Member** | Ranking of individual idol members by spending. Click name for detail (type breakdown + monthly chart). |
| **By Group** | Aggregated spending per group/unit. Click to see member breakdown. |
| **By Company** | Aggregated spending per company. Click to see groups under that company. |
| **By Type** | Ranking of item types by spending. |

### Idol Management (`idols.php`)

- Hierarchical tree view: Company > Group/Unit > Member
- CRUD for idol entities with category and parent assignment
- Stats showing items count and spending per entity
- Unmapped names panel with quick-add button

### Type Management (`types.php`)

- CRUD for type categories with description and sort order
- Usage stats (rows, quantity, spending)
- Unmapped type names panel with quick-add button

### User Management (`users.php`)

- Admin can create, edit, and delete users
- Two roles: `admin` and `user`
- Change own password (available to all users)
- Session-based authentication (24-hour lifetime)

#### Role Permissions

| Feature | Admin | User |
|---------|:-----:|:----:|
| View items list | O | O |
| Add / Edit / Clone / Delete items | O | O |
| View reports | O | O |
| Manage idols (add/edit/delete) | O | O |
| Manage types (add/edit/delete) | O | O |
| Re-seed idol data | O | X |
| Import Excel | O | X |
| Backup & Restore | O | X |
| Create / Edit / Delete users | O | X |
| Change own password | O | O |

### Backup & Restore (`backup.php`)

- **Admin only**
- Create labeled backup snapshots
- Restore from any backup (auto-backup created before restore)
- Download / Upload / Delete backups
- Protected `backups/` directory

### Excel Import (`import.php`)

- Import data from `.xlsx` file into SQLite
- Handles Excel serial date numbers
- Controlled by `ALLOW_IMPORT` config flag

## Configuration

All settings are in `config.php`:

```php
define('ALLOW_IMPORT', false);       // Enable/disable Excel import button
define('ALLOW_RESEED', false);       // Enable/disable re-seed idol data button
define('AUTH_ENABLED', true);        // Enable/disable authentication
define('SESSION_LIFETIME', 86400);   // Session lifetime in seconds (24h)
```

Database and backup paths are auto-detected:
- **Docker:** uses `data/` directory (persisted via named volume)
- **Manual:** uses project root directory

### Disabling Authentication

To use the app without login (e.g., local/development use):

```php
define('AUTH_ENABLED', false);
```

### Enabling Import / Re-seed

These are disabled by default to prevent accidental data loss:

```php
define('ALLOW_IMPORT', true);   // Shows Import Excel button
define('ALLOW_RESEED', true);   // Shows Re-seed button on Idols page
```

## Project Structure

```
numa-log/
├── .github/
│   └── workflows/
│       └── docker-build.yml  # GitHub Actions: build Docker image
├── config.php                # Database connection, schema, auth helpers
├── index.php                 # Main item list (CRUD)
├── api.php                   # REST API for items, reports, idols, types, backups
├── api_users.php             # REST API for user management
├── report.php                # Reports page (Monthly, Member, Group, Company, Type)
├── idols.php                 # Idol hierarchy management
├── types.php                 # Type category management
├── users.php                 # User management
├── login.php                 # Login page
├── backup.php                # Backup & restore management
├── backup_upload.php         # Backup file upload handler
├── import.php                # Excel to SQLite importer
├── seed_idols.php            # Idol entity seeder
├── Dockerfile                # Docker image definition
├── docker-compose.yml        # Docker Compose configuration
├── .dockerignore             # Docker build exclusions
├── .gitignore                # Git ignored files
├── composer.json             # Composer dependencies
├── database.sqlite           # SQLite database (auto-created)
├── data/                     # Persistent data directory (Docker)
│   ├── database.sqlite
│   └── backups/
└── backups/                  # Backup snapshots directory (manual)
    └── .htaccess             # Deny direct access
```

## Database Schema

### `items`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| order_date | TEXT | Purchase date (YYYY-MM-DD) |
| event_date | TEXT | Event date (YYYY-MM-DD) |
| title | TEXT | Item name |
| idol | TEXT | Idol / group name |
| type | TEXT | Item type |
| price_per_qty | REAL | Price per unit |
| qty | INTEGER | Quantity |
| created_at | TEXT | Record creation timestamp |
| updated_at | TEXT | Last update timestamp |

### `idol_entities`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| name | TEXT UNIQUE | Entity name |
| category | TEXT | `company`, `group`, `unit`, or `member` |
| parent_id | INTEGER | Parent entity reference |
| sort_order | INTEGER | Display order |

### `type_categories`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| name | TEXT UNIQUE | Type name |
| description | TEXT | Description |
| sort_order | INTEGER | Display order |

### `users`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| username | TEXT UNIQUE | Login username |
| password | TEXT | Bcrypt hashed password |
| display_name | TEXT | Display name |
| role | TEXT | `admin` or `user` |
| last_login | TEXT | Last login timestamp |

## API Endpoints

All API calls go through `api.php` with `action` parameter.

### Items
| Action | Method | Description |
|--------|--------|-------------|
| `list` | GET | List items (paginated, filterable, sortable) |
| `get` | GET | Get single item by ID |
| `create` | POST | Create new item |
| `update` | POST | Update existing item |
| `delete` | POST | Delete item |
| `filters` | GET | Get distinct idol/type values for filter dropdowns |

### Reports
| Action | Method | Description |
|--------|--------|-------------|
| `report_monthly` | GET | Monthly spending aggregation |
| `report_daily` | GET | Daily breakdown for a given month (`?month=YYYY-MM`) |
| `report_idol` | GET | Spending by member (filtered to member category) |
| `report_type` | GET | Spending by type |
| `report_idol_detail` | GET | Detail for single idol (`?idol=Name`) |
| `report_by_group` | GET | Spending aggregated by group/unit |
| `report_by_company` | GET | Spending aggregated by company |

### Idol Entities
| Action | Method | Description |
|--------|--------|-------------|
| `idol_entities_tree` | GET | Get all entities with stats |
| `idol_entity_save` | POST | Create/update entity |
| `idol_entity_delete` | POST | Delete entity |

### Type Categories
| Action | Method | Description |
|--------|--------|-------------|
| `type_list` | GET | List types with usage stats |
| `type_save` | POST | Create/update type |
| `type_delete` | POST | Delete type |

### Backups (Admin only)
| Action | Method | Description |
|--------|--------|-------------|
| `backup_list` | GET | List all backups |
| `backup_create` | POST | Create new backup |
| `backup_restore` | POST | Restore from backup |
| `backup_delete` | POST | Delete backup |
| `backup_download` | GET | Download backup file |

### Users (`api_users.php`)
| Action | Method | Description |
|--------|--------|-------------|
| `list` | GET | List all users |
| `save` | POST | Create/update user (admin) |
| `delete` | POST | Delete user (admin) |
| `change_password` | POST | Change own password |

## Tech Stack

- **Backend:** PHP 8.2, PDO/SQLite
- **Frontend:** Bootstrap 5.3.3, Bootstrap Icons 1.11.3, Chart.js 4.4.7
- **Database:** SQLite with WAL mode
- **Import:** PhpSpreadsheet (Composer)
- **CI/CD:** GitHub Actions (Docker build)

## Changelog

### v1.2.0 (2026-02-17)

Project rename.

#### Changed
- Renamed project from "Idol Items Purchased" to **Numa Log**
- Updated all page titles, navbar branding, Docker container name, and CI/CD config

### v1.1.0 (2026-02-17)

Security hardening release.

#### Security
- **CSRF protection** - All POST requests require a CSRF token (auto-injected via `<meta>` tag and fetch wrapper)
- **Session hardening** - `HttpOnly`, `SameSite=Strict`, and `Secure` (auto-detect HTTPS) cookie flags
- **Session fixation prevention** - Session ID regenerated on login (`session_regenerate_id`)
- **Security headers** - `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy` on all responses
- **Error message hardening** - API error responses no longer expose internal error details; errors logged server-side via `error_log()`
- **Backup download validation** - Enforces `.sqlite` extension check before download

#### Added
- App version display (`APP_VERSION`) on all page navbars and login page
- Role permissions comparison table in README
- GitHub Actions workflow for Docker image build
- `.gitignore` file

### v1.0.0 (2026-02-17)

Initial release.

#### Features
- **Item Management** - Full CRUD with searchable dropdowns, sortable columns, pagination, filters, and summary cards
- **Reports** - Monthly (with daily drill-down), By Member, By Group, By Company, By Type with interactive charts
- **Idol Management** - Hierarchical tree view (Company > Group/Unit > Member) with unmapped names detection
- **Type Management** - Type categories with usage stats and unmapped names detection
- **User Management** - Role-based authentication (admin/user) with session-based login
- **Backup & Restore** - Create, restore, download, upload, and delete database snapshots
- **Excel Import** - Import data from `.xlsx` files with date handling
- **Clone Item** - Duplicate existing items with one click

#### Infrastructure
- Docker & Docker Compose support with persistent data volume
- GitHub Actions workflow for Docker image build
- Auto-detection of data directory (Docker vs manual installation)
- SQLite with WAL mode and foreign keys
- Apache mod_rewrite support
- Configurable feature flags (`ALLOW_IMPORT`, `ALLOW_RESEED`, `AUTH_ENABLED`)

## License

This project is licensed under the [MIT License](LICENSE).
