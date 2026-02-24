# Numa Log

Web-based application for managing idol merchandise purchase data. Built with PHP 8.2, SQLite, Bootstrap 5, and Chart.js.

## Getting Started

See **[INSTALL.md](INSTALL.md)** for full installation and upgrade instructions.

**Quick start (Docker):**

```bash
docker compose up -d
# Open http://localhost:8080
```

**Default login:** `admin` / `admin` — change the password after first login.

## Features

### Item Management (`index.php`)

- Full CRUD for purchase items (Add, Edit, Clone, Delete)
- Searchable dropdown for Idol and Type fields
- Sortable columns, pagination, filters (by Idol, Type, date range, search)
- Summary cards showing total items, quantity, and spending

### Reports (`report.php`)

| Tab | Description |
|-----|-------------|
| **Monthly** | Bar + line chart of monthly spending & quantity. Click any month to drill down to daily view (with Type Breakdown & Idol Breakdown charts). Click any day in the daily breakdown to view filtered items for that day. |
| **By Member** | Ranking of individual idol members by spending. Click name for detail (type breakdown + monthly chart). Click any month in the monthly breakdown to view filtered items for that member and month. |
| **By Group** | Aggregated spending per group/unit. Click to see member breakdown. |
| **By Company** | Aggregated spending per company. Click to see groups under that company. |
| **By Type** | Ranking of item types by spending. Click any type to see member breakdown (member, group, company) and Monthly Breakdown chart. |

### Idol Management (`idols.php`)

- Hierarchical tree view: Company > Group/Unit > Member
- CRUD for idol entities with category and parent assignment
- Stats showing items count and spending per entity
- Unmapped names panel with quick-add button

### Type Management (`types.php`)

- CRUD for type categories with description and sort order
- Usage stats (rows, quantity, spending)
- Unmapped type names panel with quick-add button
- **Members by Type** report — accordion view showing which members, groups, and companies appear under each type

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
├── help.php                  # Help & guide page (Thai)
├── help_en.php               # Help & guide page (English)
├── HOW_TO_USE.md             # How to use documentation (Thai)
├── HOW_TO_USE_EN.md          # How to use documentation (English)
├── INSTALL.md                # Installation & upgrade guide
├── CHANGELOG.md              # Version history
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
| `report_type_detail` | GET | Member breakdown for a single type (`?type=Name`) |

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
| `type_members_report` | GET | All types with member/group/company breakdown |
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

See **[CHANGELOG.md](CHANGELOG.md)** for the full version history.

**Latest:** v1.3.4 — Monthly Type/Idol Breakdown charts and By Type Monthly Breakdown chart added to reports.

## License

This project is licensed under the [MIT License](LICENSE).
