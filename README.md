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

## Tech Stack

- **Backend:** PHP 8.2, PDO/SQLite
- **Frontend:** Bootstrap 5.3.3, Bootstrap Icons 1.11.3, Chart.js 4.4.7
- **Database:** SQLite with WAL mode
- **Import:** PhpSpreadsheet (Composer)
- **CI/CD:** GitHub Actions (Docker build)

## Features

### Item Management (`index.php`)

- Full CRUD for purchase items (Add, Edit, Clone, Delete)
- **Multi-select filters** for Idol and Type — select multiple values with badge display and inline search
- Sortable columns, pagination, text search, and date range filter
- Summary cards: total items, quantity, and spending (updates with active filters)
- URL parameter pre-fill — `idol`, `date_from`, `date_to` applied automatically on load (used by report drill-downs)
- **Export to Excel** — export all filtered items to `.xlsx` with auto-sized columns and filename based on active date range
- Searchable dropdown for Idol and Type fields in the Add/Edit form
- Mobile responsive: 2-per-row cards and filters, horizontal table scroll

### Reports (`report.php`)

| Tab | Description |
|-----|-------------|
| **Monthly** | Bar + line chart of monthly spending & quantity. Click any month to drill down to daily view — includes **Type Breakdown** doughnut chart and **Idol Breakdown** doughnut chart (top 10 + others). Click any day to view filtered items for that day. |
| **By Member** | Ranking of individual idol members by spending. Click a name for detail: type breakdown + monthly spending chart. Click any month row to view filtered items for that member and month. |
| **By Group** | Aggregated spending per group/unit, rolling up all member spending. Click to see member breakdown. |
| **By Company** | Aggregated spending per company with group sub-breakdown. Click to see groups under that company. |
| **By Type** | Ranking of item types by spending. Click any type to see member breakdown (member, group, company) and **Monthly Breakdown** chart. Click any month row to view filtered items for that type and month. |

### Idol Management (`idols.php`)

- Hierarchical tree view: Company > Group/Unit > Member
- CRUD for idol entities with category and parent assignment
- **Display hint** field for distinguishing same-name members (e.g. "Yuna [ITZY]" vs "Yuna [AKB48]"). Soft-suggest fires when a collision is detected
- **Membership panel** inside member entity — track each (member, group, start_date, end_date) period; "Move to new group" shortcut closes the current membership and starts a new one in one click; primary vs sub-unit toggle
- **Ambiguous mappings panel** — surfaces items whose `idol` text matches multiple entities, with a one-click bulk-remap UI
- Stats showing items count, quantity, and spending per entity
- Unmapped names panel — idol names in items not yet linked to any entity, with quick-add button
- Re-seed button (admin only, requires `ALLOW_RESEED`)

### Type Management (`types.php`)

- CRUD for type categories with description and sort order
- Usage stats (rows, quantity, spending)
- Unmapped type names panel with quick-add button
- **Members by Type** accordion — shows which members, groups, and companies appear under each type

### User Management (`users.php`)

- Admin can create, edit, and delete users
- Two roles: `admin` and `user`
- Change own password (available to all users), minimum 12 characters
- Session-based authentication (24-hour lifetime)
- **Brute-force protection** — login blocked for 15 minutes after 5 consecutive failed attempts from the same IP
- **Forced password change** — new installs redirect to password change page until the default `admin` password is replaced

#### Role Permissions

| Feature | Admin | User |
|---------|:-----:|:----:|
| View items list | O | O |
| Add / Edit / Clone / Delete items | O | O |
| Export Excel | O | O |
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
- Restore from any backup (auto-backup created automatically before restore)
- Download / Upload / Delete backups
- Protected `backups/` directory

### Excel Import (`import.php`)

- Import data from `.xlsx` file into SQLite
- Handles Excel serial date numbers
- Admin only; controlled by `ALLOW_IMPORT` config flag

### Help & Guide (`help.php` / `help_en.php`)

- In-app usage guide available in **Thai** and **English**
- TH/EN language switcher on the help page
- Covers all features with step-by-step instructions, tips, and FAQ

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

See **[PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md)** for the full directory layout.

## Database Schema

See **[DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)** for all table definitions.

## API Endpoints

See **[API_ENDPOINTS.md](API_ENDPOINTS.md)** for full API documentation including authentication, parameters, and response formats.

## Changelog

See **[CHANGELOG.md](CHANGELOG.md)** for the full version history.

**Latest:** v1.5.0 — Membership history + ID-based idol reference. Track members across group moves, allow same-name members from different groups, and aggregate reports by the group in effect at the time of purchase.

## License

This project is licensed under the [MIT License](LICENSE).


---

### ❤️ Donation & Sponsorship

If this project has been useful to you, consider supporting its continued development:

- **GitHub Sponsors**: Sponsor the project directly on GitHub

Your support helps keep the project actively maintained and free for the community.