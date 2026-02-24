# Changelog

All notable changes to Numa Log are documented here.

---

## v1.3.4 (2026-02-24)

Report enhancement release.

### Added
- **Monthly → Type Breakdown** (`report.php`) — When drilling into a month's daily view, a "Top 10 by Type" doughnut chart and full Type Breakdown table are now shown, displaying spending share per item type for that month
- **Monthly → Idol Breakdown** (`report.php`) — When drilling into a month's daily view, a "Top 10 by Idol" doughnut chart (top 10 + Others) and full Idol Breakdown table are shown. Click any idol name to navigate to their detail view
- **By Type → Monthly Breakdown** (`report.php`) — When clicking into a type's detail view, a Monthly Spending bar chart and monthly breakdown table are now shown. Click any month row to view filtered items for that type and month
- **API:** `report_daily` now returns `by_type` and `by_idol` breakdowns alongside daily data
- **API:** `report_type_detail` now returns `by_month` breakdown alongside member data

---

## v1.3.3 (2026-02-22)

Report drill-down links release.

### Added
- **Daily Breakdown links** (`report.php`) — Each day row in the Monthly → Daily detail view is now a clickable link to `index.php` pre-filtered by that specific date (`date_from` & `date_to`)
- **Member Monthly Breakdown links** (`report.php`) — Each month row in the By Member → detail view is now a clickable link to `index.php` pre-filtered by that member and month
- **URL param pre-fill** (`index.php`) — Items page now reads `idol`, `date_from`, and `date_to` query parameters on load and auto-applies them to the filter inputs

---

## v1.3.2 (2026-02-19)

Type report enhancement, database optimization & navbar consistency release.

### Added
- **Type detail view in report** (`report.php`) — Click any type in "By Type" tab to see member breakdown with group and company hierarchy
- **Members by Type report** (`types.php`) — Accordion section on Type Management page showing members, group, and company for each type
- **API:** `report_type_detail` — Returns member breakdown for a specific type
- **API:** `type_members_report` — Returns all types with full member/group/company hierarchy

### Changed
- **Navbar redesign** — Consistent navigation across all 8 pages:
  - All pages show **← Items**, **Report**, and page-specific cross-links for quick navigation
  - Standalone **Backup** and **Help** buttons removed; consolidated into the user dropdown
  - **Users** and **Backup** links in dropdown are now admin-only
  - Each page omits its own link from navigation (e.g. Users page has no "Users" in dropdown)
- **Login page** — Default credential hint (`admin / admin`) now auto-hides once the admin password has been changed
- **Documentation** — Updated in-app help (`help.php`, `help_en.php`) and markdown guides (`HOW_TO_USE.md`, `HOW_TO_USE_EN.md`) to reflect By Type drill-down and Members by Type accordion

### Performance
- **SQLite indexes** — Added 6 indexes on hot columns: `items(idol)`, `items(type)`, `items(order_date)`, `items(type, idol)`, `idol_entities(parent_id)`, `idol_entities(category)`. Applied automatically via `IF NOT EXISTS` on first page load — no migration needed
- **PRAGMA tuning** — `synchronous=NORMAL` (faster writes, safe with WAL), `cache_size=-8000` (8 MB page cache), `temp_store=MEMORY` (in-memory temp tables and sorts)
- **`handleReportIdol`** — Replaced two-step fetch + `IN (?, ...)` with a single `JOIN idol_entities` query
- **`handleTypeList`** — Reduced from 3 separate queries to 2 (LEFT JOIN subquery for stats; LEFT JOIN for unmapped detection)

---

## v1.3.1 (2026-02-17)

English documentation release.

### Added
- **English help page** (`help_en.php`) - In-app help & guide page in English
- **English how to use** (`HOW_TO_USE_EN.md`) - Comprehensive usage documentation in English
- **Language switcher** - TH/EN toggle on help pages to switch between Thai and English

---

## v1.3.0 (2026-02-17)

Help & documentation release.

### Added
- **Help page** (`help.php`) - In-app help & guide page in Thai with sticky table of contents, accordion sections, step-by-step instructions, tips, warnings, and FAQ
- **How to Use** (`HOW_TO_USE.md`) - Comprehensive usage documentation in Thai
- **Help navigation** - Added Help link to navbar on all pages (Items, Report, Idols, Types, Users, Backup)

---

## v1.2.0 (2026-02-17)

Project rename.

### Changed
- Renamed project from "Idol Items Purchased" to **Numa Log**
- Updated all page titles, navbar branding, Docker container name, and CI/CD config

---

## v1.1.0 (2026-02-17)

Security hardening release.

### Security
- **CSRF protection** - All POST requests require a CSRF token (auto-injected via `<meta>` tag and fetch wrapper)
- **Session hardening** - `HttpOnly`, `SameSite=Strict`, and `Secure` (auto-detect HTTPS) cookie flags
- **Session fixation prevention** - Session ID regenerated on login (`session_regenerate_id`)
- **Security headers** - `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy` on all responses
- **Error message hardening** - API error responses no longer expose internal error details; errors logged server-side via `error_log()`
- **Backup download validation** - Enforces `.sqlite` extension check before download

### Added
- App version display (`APP_VERSION`) on all page navbars and login page
- Role permissions comparison table in README
- GitHub Actions workflow for Docker image build
- `.gitignore` file

---

## v1.0.0 (2026-02-17)

Initial release.

### Features
- **Item Management** - Full CRUD with searchable dropdowns, sortable columns, pagination, filters, and summary cards
- **Reports** - Monthly (with daily drill-down), By Member, By Group, By Company, By Type with interactive charts
- **Idol Management** - Hierarchical tree view (Company > Group/Unit > Member) with unmapped names detection
- **Type Management** - Type categories with usage stats and unmapped names detection
- **User Management** - Role-based authentication (admin/user) with session-based login
- **Backup & Restore** - Create, restore, download, upload, and delete database snapshots
- **Excel Import** - Import data from `.xlsx` files with date handling
- **Clone Item** - Duplicate existing items with one click

### Infrastructure
- Docker & Docker Compose support with persistent data volume
- GitHub Actions workflow for Docker image build
- Auto-detection of data directory (Docker vs manual installation)
- SQLite with WAL mode and foreign keys
- Apache mod_rewrite support
- Configurable feature flags (`ALLOW_IMPORT`, `ALLOW_RESEED`, `AUTH_ENABLED`)
