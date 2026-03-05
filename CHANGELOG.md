# Changelog

All notable changes to Numa Log are documented here.

---

## v1.3.10 (2026-03-05)

Docker security hardening release.

### Changed
- **Multi-stage Docker build** — แยก builder stage (compile PHP extensions + Composer) ออกจาก runtime stage เพื่อตัด `git`, `unzip`, และ Composer binary ออกจาก final image
- **PHP security hardening** (`security.ini`) — เพิ่ม `expose_php=Off`, `display_errors=Off`, `log_errors` → stderr, `disable_functions` สำหรับ shell execution, session cookie hardening
- **Apache security hardening** (`hardening.conf`) — เพิ่ม `ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`, และ security response headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- **File permissions** — ใช้ `find` แยก permission ระหว่าง file (644) และ directory (755) แทน `chmod -R`
- **HEALTHCHECK** — เพิ่ม Docker health check ด้วย `curl` ทุก 30 วินาที
- **`--no-install-recommends`** — ลด package footprint ใน apt install ทุกขั้นตอน
- **`.dockerignore`** — เพิ่ม exclusion สำหรับ `.claude/`, `.vscode/`, `Dockerfile`, `docker-compose*`, `tests/`, `*.log`, `*.xlsx`, `*.csv`

---

## v1.3.9 (2026-03-01)

Bug fix release.

### Fixed
- **Filter/search menus broken after add or clone** (`index.php`) — After saving or cloning an item while a filter was active, the multi-select dropdowns for Idol and Type became unresponsive. Clicking the box toggled the dropdown open and immediately closed it, and the Clear button had no effect. Root cause: `loadFilters()` was calling `initMultiSelect()` a second time, adding duplicate event listeners on the same DOM elements — the two `toggle('show')` calls cancelled each other out. Fixed by adding an initialisation guard (`_msInit` flag on the wrapper element) so that subsequent calls to `initMultiSelect()` return the existing instance instead of creating a new one. Filter data (`filtersData`) continues to refresh normally via the closure reference, so newly added idol/type names still appear in the dropdowns

---

## v1.3.8 (2026-02-24)

Mobile responsiveness release.

### Changed
- **Navbar button labels** (all pages) — Button text labels are hidden on extra-small screens (< 576 px); only icons are shown. Labels reappear on ≥ 576 px screens. No functionality is changed
- **Layout columns** (`report.php`, `idols.php`, `types.php`, `users.php`, `backup.php`) — Added `col-12` prefix to all `col-lg-*` column classes so that panels stack vertically on mobile instead of overflowing the viewport
- **Filter row** (`index.php`) — Changed filter column classes from `col-md-2` to `col-6 col-md-2` so filters display as two per row on narrow screens (three rows of two) rather than collapsing
- **Summary cards** (`index.php`) — Changed from `col-md-3` to `col-6 col-md-3` so cards show two per row on mobile
- **Chart height** (`report.php`) — Chart containers shrink to 250 px on screens ≤ 767 px to avoid excessive vertical scroll
- **iOS input zoom prevention** (all pages) — Added `font-size: 16px !important` for text/date/password inputs on ≤ 575 px screens, preventing automatic zoom-in on iOS Safari
- **Horizontal table scroll** (all pages) — Added `overflow-x: auto` and `-webkit-overflow-scrolling: touch` on `.table-responsive` and `.card-body.p-0` wrappers so tables scroll horizontally on narrow screens instead of overflowing
- **Multi-select dropdowns** (`index.php`) — Added `-webkit-overflow-scrolling: touch` on `.ms-drop` and `.sd-list` for smooth iOS scrolling inside dropdown lists
- **Tree view padding** (`idols.php`) — Reduced tree item indentation on < 576 px screens to prevent excessive nesting overflow

---

## v1.3.7 (2026-02-24)

Security hardening release.

### Security
- **Brute-force protection** (`login.php`) — Login is blocked for 15 minutes after 5 consecutive failed attempts from the same IP address. Failed attempts are recorded in the `login_attempts` table; records older than 1 hour are purged automatically. Successful login clears previous attempt records for the IP
- **Password minimum length** (`api_users.php`) — Minimum password length raised from 4 to **12 characters** (NIST 2024 recommendation), enforced on new user creation, admin password update, and self-service password change
- **Content Security Policy** (`config.php`) — Added `Content-Security-Policy` header restricting resource origins to `self` and trusted CDN (`cdn.jsdelivr.net`); `frame-ancestors 'none'` replaces `X-Frame-Options`
- **Server-side session timeout** (`config.php`) — Sessions are now expired server-side after `SESSION_LIFETIME` (24 hours) regardless of cookie lifetime; `gc_maxlifetime` aligned with session lifetime
- **Date input validation** (`api.php`, `export.php`) — `date_from` and `date_to` parameters are validated against `YYYY-MM-DD` format and rejected with HTTP 400 if invalid
- **Filename sanitization** (`export.php`) — Export filename is sanitized with `preg_replace` to prevent header injection
- **SQLite file protection** (`.htaccess`) — Added root `.htaccess` to deny direct HTTP access to `.sqlite`, `.sqlite3`, and `.db` files
- **Forced password change on first login** (`change_password_required.php`, `login.php`, `config.php`) — Users logging in with the default `admin` password are automatically redirected to a dedicated change-password page. All other pages are blocked until the new password (min 12 characters) is saved. A Logout link is provided as the only exit

---

## v1.3.6 (2026-02-24)

Multi-select filter release.

### Changed
- **Idol & Type filters** (`index.php`) — Replaced single-select dropdowns with custom multi-select components. Users can now select multiple idols and/or multiple types simultaneously. Selected values are shown as removable badges; an inline search box filters the list
- **Export Excel button** (`index.php`) — Moved from navbar to the filter row, placed next to the **Clear** button, making it visually clear that Export respects the active filters
- **API** (`api.php`) and **Export** (`export.php`) — Filter logic updated from `idol = :idol` to `idol IN (...)` to support multi-value selection

---

## v1.3.5 (2026-02-24)

Excel export release.

### Added
- **Export Excel** (`export.php`) — Export all items matching the current filters to `.xlsx`. The button appears in the Items page navbar and is available to all users (Admin and User). Filters (idol, type, date range, search) are passed through automatically
- Exported file columns match the import format: `Order Date`, `Event Date`, `Title`, `Idol`, `Type`, `Price per Qty`, `Qty`, plus an additional `Price Total` column (Excel formula `=F*G`)
- Filename is auto-generated based on the active date filter (e.g. `numa-log_2025-01-01_2025-12-31.xlsx`) or the current date if no date filter is set

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
