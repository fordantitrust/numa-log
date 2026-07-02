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

### Dashboard (`index.php`)

- **Landing page after login** — an at-a-glance summary instead of the item list
- KPI cards: total spent, items/quantity, average per month, and latest month with month-over-month delta
- Monthly spending trend (bar chart), Top members and Top groups lists
- Type and Company breakdowns (doughnut charts)
- **Period selector** — filter the whole dashboard by *All time*, *Last 12 months*, or a specific year (years populated from the data)
- Powered by a single `report_dashboard` API call; group/company aggregates reuse the membership-aware joins so numbers match the Report page

### Item Management (`items.php`)

- Full CRUD for purchase items (Add, Edit, Clone, Delete)
- **Multi-select filters** for Idol and Type — select multiple values with badge display and inline search
- Sortable columns, pagination, text search, and date range filter
- Summary cards: total items, quantity, and spending (updates with active filters)
- URL parameter pre-fill — `idol`, `date_from`, `date_to` applied automatically on load (used by report drill-downs)
- **Export to Excel** — export all filtered items to `.xlsx` with auto-sized columns and filename based on active date range
- Searchable dropdown for Idol and Type fields in the Add/Edit form
- Mobile responsive: 2-per-row cards and filters, horizontal table scroll

### Reports (`report.php`)

Heavy analytics tabs are lazy-loaded the first time they are opened.

| Tab | Description |
|-----|-------------|
| **Overview** | At-a-glance summary: KPI cards (total spent, items/qty, average per month, latest-month MoM%), monthly trend chart, Top 5 members, a highlights panel, and Type / Company doughnuts. |
| **Monthly** | Bar + line chart of monthly spending & quantity. Click any month to drill down to daily view — includes **Type Breakdown** doughnut chart and **Idol Breakdown** doughnut chart (top 10 + others). Click any day to view filtered items for that day. |
| **Trends** | Cumulative spending line, month-over-month growth bars, and a current-month forecast projected from spend-to-date. |
| **Seasonality** | Spending by day of week and by month of year (chart + share table), aggregated across all years. |
| **By Member** | Ranking of individual idol members by spending. Click a name for detail: type breakdown + monthly spending chart. Click any month row to view filtered items for that member and month. |
| **Compare** | Pick any two members and compare side by side: summary cards, monthly spending line, and by-type grouped bars. |
| **By Group** | Aggregated spending per group/unit, rolling up all member spending. Click to see member breakdown. |
| **By Unit** | Spending rolled up to `unit`-category entities, including sub-unit / project memberships that By Group rolls into the parent. |
| **By Company** | Aggregated spending per company with group sub-breakdown. Click to see groups under that company. |
| **By Type** | Ranking of item types by spending. Click any type to see member breakdown (member, group, company) and **Monthly Breakdown** chart. Click any month row to view filtered items for that type and month. |
| **By Event** | Spending per `event_date` plus order→event lead-time stats (avg / min / max) and a count of items with no event date. |
| **Top Items** | The 20 most expensive single purchases, the 20 most frequently bought titles, and average / min / max unit price per type. |
| **Inactive** | Members with no recent purchases, with a selectable threshold (30 / 90 / 180 / 365 days) and click-through to member detail. |

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
- TH/EN language switcher on the help page (synced with the app-wide switcher)
- Covers all features with step-by-step instructions, tips, and FAQ

### Language (i18n)

- **App-wide English / Thai** with an EN/TH toggle in every page's navbar
- Default language is **English**; the choice is remembered across pages and sessions (session + 1-year cookie)
- Translation tables in `lang/en.php` / `lang/th.php` (served to the browser as `window.I18N`, consumed by a shared `t()` helper in `assets/i18n.js`); missing keys fall back to English
- Covers Dashboard, Items, Report (all tabs, charts, month/weekday names), Idols, Types, Users, Backup, Login, and the password-change screen
- Currency stays as `฿` with `th-TH` grouping in both languages

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

**Latest:** v1.9.16 — Events can now also be marked "Did not attend (ordered items only)", alongside Free entry, to exclude them from the missing-ticket warning.

**v1.9.15** — The Events page now detects missing tickets: mark a type as "Is Ticket Type" in Manage Types and mark free-entry events, and each event shows a Ticket recorded / No ticket / Free entry badge.

**v1.9.11** — Event reports & list: the Events management table now leads with the **Start – End date range** (no more `#` column), and a new **Event Summary** report tab shows each event's date range, **duration in days**, Upcoming/Ongoing/Past status, spend and average per event-day, with summary KPI cards. Report KPI cards on the event tabs were also evened out to a uniform height.

**v1.9.10** — Un-assign events: items can be unlinked from an event without deleting anything — a one-click ✕ clear button next to the Event field on the item form (keeps the item's own Event Date), and an **Unassign** button in the Items bulk action bar to clear the event link on many selected items at once.

**v1.9.9** — Multi-day events: events now have an optional **end date** (`events.end_date`, schema v9), so a single event can span several days (e.g. a two-day concert). Leave the end date empty for a single-day event. The event list, item dropdown, and "By Event" report show the full date range, and auto-assign-by-date links unlinked items whose event date falls anywhere within the range.

**v1.9.8** — Navigation & Items UI: navbar now uses a responsive Bootstrap hamburger menu (`navbar-expand-lg`) with the full menu always shown in a fixed order and the current page highlighted instead of hidden; the Items filter bar was reorganized into two rows with an Event multi-select filter, and **Export**/**Add Item** moved into the filter card's header for a consistent, always-visible position.

**v1.9.7** — Events: new **Events** feature with dedicated `events.php` management page, named event entities (`events` table, schema v8), `event_id` FK on items, searchable event dropdown in item form (auto-fills Event Date), per-row event badge in item list, bulk-assign and auto-assign by date for migrating old data, and an upgraded "By Event" report tab showing named events with clickable links.

**v1.9.6** — Budgets: new **Monthly** tab on `budget.php` — a Scopes × Months grid showing every budget scope across a year (Default column + one column per month) so you can see all months at once. Click any cell to set/override that month or edit the recurring default; custom months are highlighted with a one-click reset. Footer rows total Overall / Allocated / Unallocated per month.

**v1.9.4** — Fix: in Budget Insights, the "Spent vs. budget by month" chart now shows the budget line for non-Overall scopes (member/group/company/type), not just spending bars.

**v1.9.3** — Budgets: an allocation summary (Overall → allocated to sub-budgets → unallocated) in the right-hand panel; fixed double-counted total limit/spent when an Overall budget coexists with sub-budgets; and fixed the budget-edit dropdown not pre-selecting the target.

**v1.9.2** — UI: the 14 report tabs are grouped into compact dropdowns (Over Time / Breakdown / More) so the menu stays on one row.

**v1.9.1** — Fix: asset cache-busting (`?v=APP_VERSION`) so browsers always load the current `budget.js` / `i18n.js` after an upgrade, resolving a stale-cache error on the Budget Insights tab.

## License

This project is licensed under the [MIT License](LICENSE).


---

### ❤️ Donation & Sponsorship

If this project has been useful to you, consider supporting its continued development:

- **GitHub Sponsors**: Sponsor the project directly on GitHub

Your support helps keep the project actively maintained and free for the community.