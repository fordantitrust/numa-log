# Changelog

All notable changes to Numa Log are documented here.

---

## v1.9.0 (2026-06-02)

### Budget Insights

A historical analytics view for budgets — answers "how am I doing **over time**?" alongside the existing single-month Progress view.

- **Scope selector** — pick *Overall* or any scope that has a budget (type / group / company / member). Comparing one scope at a time avoids double-counting nested entities.
- **Selectable range** — last **6 / 12 / 24** months (default 12).
- **Overview KPIs** — total & average spend, average budget, average % used, months over budget, and the highest-spend month.
- **Spent vs. budget chart** — monthly spend bars coloured by status (green / yellow / red) with the effective limit drawn as a line.
- **% used trend** — a line tracking each month's spend as a percentage of its budget, with a 100% reference.
- **Recommendations** — rule-based, bilingual tips: over budget frequently, over last month, projected overspend at the current pace, spending trending up/down, consistently under budget (with a suggested lower limit), or on track.
- **Two surfaces** — a new **Insights** tab on `budget.php` and the same block appended to the **Budget** tab in the report page; all rendering is shared via `assets/budget.js`. New `budget_analytics` API action. No schema change.

---

## v1.8.0 (2026-06-02)

### Budgets & Spending Goals

A new feature for setting recurring **monthly spending limits** and tracking progress against them.

- **Per-scope budgets** — set a monthly limit for the **overall** total, or scoped to a specific **type**, **group**, **company**, or **member**. Group/company/member spending is computed with the same membership-aware joins as the existing reports.
- **Recurring default + per-month overrides** — each scope has a recurring default amount that applies to every month, and you can override the amount/thresholds for a specific month (or add a budget that only applies to one month). The effective budget for a month is the override if one exists, else the default.
- **Configurable colour thresholds** — each budget defines its own yellow (`warn_pct`) and red (`danger_pct`) percentages. Bars show green below yellow, yellow up to red, and red at/above the red threshold. Over-budget is a visual warning only; it never blocks adding items.
- **Three surfaces** — a dedicated **`budget.php`** page with **Progress** (per-month view + tweak) and **Manage** (recurring defaults) tabs, a **Budgets This Month** card on the Dashboard, and a new **Budget** tab in the report page. Month headers show the selected month name.
- **Schema v6 + v7** — adds the `budgets` table (`v6_budgets.php`) and the per-month `period` column (`v7_budget_periods.php`); existing budgets become recurring defaults. Both auto-applied with a pre-migration backup.

---

## v1.7.2 (2026-05-30)

### Dependency update

- **phpoffice/phpspreadsheet** 5.4.0 → 5.7.0 (bug fixes across 3 minor releases; no breaking changes)

---

## v1.7.1 (2026-05-30)

### Help pages — updated to reflect all current features

- **Quick Start** — added automatic Dashboard redirect note after first login, updated report count from 5 to 13, added Language Switcher tip box (v1.7.0)
- **Reports section** — expanded from 5 to all 13 tabs with accurate descriptions: Overview, Monthly, Trends, Seasonality, By Member, Compare, By Group, By Unit, By Company, By Type, By Event, Top Items, Inactive
- Added v-badges on new tabs (v1.6.1) so users can spot features added after their install

---

## v1.7.0 (2026-05-29)

### Bilingual UI — English & Thai (app-wide i18n)

The entire app can now be switched between **English** (default) and **Thai** from an EN/TH toggle in every page's navbar. Previously only the Help page existed in both languages; the rest of the UI was a fixed mix of English labels and Thai strings.

- **Language switcher** on every page (and on the login / change-password screens). The choice is remembered across pages and sessions via the session plus a 1-year cookie. New visitors default to English.
- **Translation layer** — string tables in `lang/en.php` and `lang/th.php` (384 keys, full parity), served to the browser as `window.I18N` and consumed by a shared `t()` helper (`assets/i18n.js`). Missing keys fall back to English so nothing ever renders blank.
- **Coverage** — Dashboard (`index.php`), Items, Report (all 13 tabs incl. charts, tables, month/weekday names), Idols, Types, Users, Backup, Login, and the forced password-change screen. The `<html lang>` attribute now reflects the active language.
- **Help pages** — the existing `help.php` (Thai) / `help_en.php` (English) are kept as-is and wired into the new switcher so the choice stays in sync.
- Currency stays formatted as `฿` with `th-TH` number grouping in both languages (single currency, not language-bound).

### Backend (`config.php`)

- **New helpers:** `currentLang()` (query `?lang=` → session → cookie → default `en`, validated), `loadLang()` (English base + active-language overlay), `t($key, $params)` with `{placeholder}` substitution, and `langSwitcher()` / `langUrl()` for the navbar toggle. Language is resolved at config load (before output) so the preference cookie is set cleanly; skipped on CLI.

---

## v1.6.1 (2026-05-29)

### Report page — 8 new report views

The Report page (`report.php`) gains eight new tabs alongside the existing Monthly / By Member / By Group / By Company / By Type. Heavy analytics tabs are **lazy-loaded** the first time they are opened, so the initial page load stays light.

- **Overview** — landing tab with KPI cards (total spent, items/qty, average per month, latest-month MoM%), monthly trend bar chart, Top 5 members list, a highlights panel, and Type / Company doughnuts. Reuses the existing `report_dashboard` endpoint.
- **Trends** — cumulative spending line, month-over-month growth bars (green/red), and a **current-month forecast** projected from spend-to-date.
- **Seasonality** — spending by **day of week** and by **month of year** (chart + share table), aggregated across all years.
- **Compare** — pick any two members and compare them side by side: summary cards, monthly spending line, and by-type grouped bars.
- **By Unit** — spending rolled up to `unit`-category entities, **including sub-unit / project memberships** that the primary-only By Group report rolls into the parent.
- **By Event** — first report keyed off `event_date` (previously unused): spending per event plus order→event **lead-time** stats (avg / min / max) and a count of items with no event date.
- **Top Items** — the 20 most expensive single purchases, the 20 most frequently bought titles, and average / min / max unit price per type.
- **Inactive** — members with no recent purchases, with a selectable threshold (30 / 90 / 180 / 365 days) and click-through to member detail.

### Backend (`api.php`)

- **New endpoints:** `report_by_unit`, `report_event`, `report_top_items`, `report_seasonality`, `report_inactive`. Unit/event queries are membership- and date-aware, consistent with the other report aggregations.

---

## v1.6.0 (2026-05-29)

### Dashboard landing page
- **New `index.php` Dashboard** — the page you land on after login is now an at-a-glance summary instead of the item list. It shows KPI cards (total spent, items/qty, average per month, latest month with month-over-month delta), a monthly spending trend (bar chart), Top members and Top groups lists, and Type / Company breakdowns (doughnut charts).
- **Period selector** — filter the whole dashboard by *All time*, *Last 12 months*, or a specific year. Years are populated from the data.
- The item list (CRUD) moved to **`items.php`** with no functional change. A **Dashboard** button was added to every page's navbar, and the "Items" links / report deep-links now point to `items.php`.

### Backend (`api.php`)
- **New endpoint `report_dashboard`** — returns KPIs, monthly trend, top members/groups, and type/company breakdowns in one request, with optional `date_from`/`date_to` filtering. Group/company aggregates reuse the membership-aware joins from `report_by_group` / `report_by_company`, so numbers match the Report page.

---

## v1.5.0 (2026-05-27)

Major refactor of idol references to fix two long-standing limitations:
1. **Membership history** — when an idol changes group, items purchased before the move now correctly stay with the old group while new items roll up under the new one.
2. **Duplicate names** — two members can share a name (e.g. "Yuna" in ITZY and AKB48) and be tracked separately.

### Schema (`v5` migration, applied automatically on first request after upgrade)
- **New table `idol_memberships`** — `(member_id, group_id, start_date, end_date, is_primary, note)`. Report queries join by `i.order_date BETWEEN start_date AND end_date`, preferring `is_primary = 1`. Existing `parent_id` values are auto-backfilled as a single open-ended primary membership per member.
- **`idol_entities`** — `UNIQUE` constraint on `name` dropped. New `display_hint` column for disambiguating same-name entities (e.g. "Yuna [ITZY]" vs "Yuna [AKB48]").
- **`items.idol_id`** — new FK to `idol_entities`; canonical reference used by all report aggregations. `items.idol` (text) is preserved as an immutable snapshot of the name at purchase time. Auto-backfilled where the name uniquely matches one entity.
- **`schema_meta`** — version tracking table; future migrations increment this.

### Migration infrastructure (`migrations/`)
- Self-contained migration scripts (one file per version) with auto-backup, `VACUUM INTO` snapshot, and transactional rollback.
- Optional CLI runner (`migrate.php`) for Docker / scripted deploys.
- See [migrations/README.md](migrations/README.md) for the system overview.

### Backend (`api.php`, `helpers_idol.php`)
- **Hybrid `item_save` policy** — `create`/`update` accept either `idol_id` (preferred) or free-text `idol`; ambiguous names return **HTTP 409** with a candidates list.
- **New endpoints:** `idol_search`, `idol_resolve_name`, `item_remap`, `item_bulk_remap`, `ambiguous_list`, `membership_list`/`save`/`delete`/`move`, `report_group_detail`.
- All eight report queries refactored to use `items.idol_id` + `idol_memberships` for time-aware group resolution.

### UI
- **Idol Management** (`idols.php`) — new membership panel inside the entity-edit modal (list, add, edit, delete, "Move to new group" shortcut). New `display_hint` field with soft-suggest when a name collision is detected. Tree view shows `[hint]` next to the name and a 🔄 icon for members with > 1 membership. New side panel summarising ambiguous mappings with a one-click conflict-resolution modal.
- **Items** (`index.php`) — when the entered name is ambiguous the item form now surfaces the candidate entities inline; picking one retries the save with the explicit `idol_id`. A pending-resolution banner at the top of the page links to the resolver.
- **Reports** (`report.php`) — By-Group drill-down now uses the v5 endpoint and displays sub-unit memberships under each group.

### Import / Export
- Excel import detects an optional `Idol ID` column (header match, position H or I) and uses it when present; rows with ambiguous names import successfully but are queued in the ambiguous panel.
- Excel export adds a hidden `Idol ID` column for clean round-trips.

### Documentation
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md), [API_ENDPOINTS.md](API_ENDPOINTS.md), [HOW_TO_USE.md](HOW_TO_USE.md), [HOW_TO_USE_EN.md](HOW_TO_USE_EN.md) updated.
- See [MEMBERSHIP_HISTORY_PLAN.md](MEMBERSHIP_HISTORY_PLAN.md) for the design rationale.

---

## v1.4.1 (2026-04-15)

### Fixed
- **Qty and Item counts showing decimal places** — `formatNumber` / `fmt` functions were changed globally in v1.4.0 to enforce 2 decimal places, which incorrectly affected integer fields such as item counts and quantities. Fixed by introducing separate `formatInt` / `fmtInt` functions (no decimal places) for count/qty values, while keeping `formatNumber` / `fmt` with 2 decimal places exclusively for currency amounts (`index.php`, `report.php`)

---

## v1.4.0 (2026-04-12)

Decimal formatting release.

### Changed
- **Amount formatting** — All currency amounts are now displayed with exactly 2 decimal places (e.g., ฿1,234.56 instead of ฿1,234.6). Applied to Items page (`index.php`), Idols page (`idols.php`), Report page (`report.php`), and Types page (`types.php`) using `Intl.NumberFormat` with `minimumFractionDigits: 2` and `maximumFractionDigits: 2`

---

## v1.3.12 (2026-03-30)

### Changed
- **Clone Item** — Order Date and Event Date are now set to the current date instead of being copied from the original item

---

## v1.3.11 (2026-03-05)

Timezone fix release.

### Fixed
- **Timezone (Asia/Bangkok)** — Added `date_default_timezone_set('Asia/Bangkok')` in `config.php` so that PHP `date()` and all time functions use Thai time (UTC+7), covering both local and Docker environments
- **Dockerfile timezone** — Added OS timezone configuration (`/etc/localtime` → `Asia/Bangkok`) and `date.timezone = Asia/Bangkok` in `security.ini` to ensure correct timezone at every layer of the Docker image (no longer reliant solely on the `TZ` env var from docker-compose)

---

## v1.3.10 (2026-03-05)

Docker security hardening release.

### Changed
- **Multi-stage Docker build** — Separated the builder stage (compile PHP extensions + Composer) from the runtime stage to remove `git`, `unzip`, and the Composer binary from the final image
- **PHP security hardening** (`security.ini`) — Added `expose_php=Off`, `display_errors=Off`, `log_errors` → stderr, `disable_functions` for shell execution, and session cookie hardening
- **Apache security hardening** (`hardening.conf`) — Added `ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`, and security response headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- **File permissions** — Used `find` to set permissions separately for files (644) and directories (755) instead of `chmod -R`
- **HEALTHCHECK** — Added Docker health check via `curl` every 30 seconds
- **`--no-install-recommends`** — Reduced package footprint in all apt install steps
- **`.dockerignore`** — Added exclusions for `.claude/`, `.vscode/`, `Dockerfile`, `docker-compose*`, `tests/`, `*.log`, `*.xlsx`, `*.csv`

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
