# Database Schema

### `items`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| order_date | TEXT | Purchase date (YYYY-MM-DD) |
| event_date | TEXT | Event date (YYYY-MM-DD) — the item's own day; for multi-day events it falls within the event's date range |
| event_id | INTEGER FK | Reference to `events.id` (nullable; `ON DELETE SET NULL`) — links the item to a named event |
| title | TEXT | Item name |
| idol | TEXT | Idol / group name — immutable snapshot of the name at purchase time |
| idol_id | INTEGER FK | Reference to `idol_entities.id` — canonical relation used by reports (nullable; `ON DELETE SET NULL`) |
| type | TEXT | Item type |
| price_per_qty | REAL | Price per unit |
| qty | INTEGER | Quantity |
| created_at | TEXT | Record creation timestamp |
| updated_at | TEXT | Last update timestamp |

### `events` (v8, +`end_date` in v9)
Named events (concerts, fanmeets, etc.) that items can be linked to via `items.event_id`.
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| name | TEXT | Event name |
| event_date | TEXT | Start date (YYYY-MM-DD) |
| end_date | TEXT | (v9) End date (YYYY-MM-DD), nullable — `NULL` (or equal to `event_date`) means a single-day event; otherwise the event spans `event_date`…`end_date` |
| description | TEXT | Optional description |
| created_at | TEXT | Record creation timestamp |

> **v9 note:** auto-assign-by-date links unlinked items whose `event_date` falls within `event_date`…`COALESCE(end_date, event_date)`.

### `idol_entities`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| name | TEXT | Entity name — duplicates allowed across the table (use `display_hint` to disambiguate in the UI) |
| category | TEXT | `company`, `group`, `unit`, or `member` |
| parent_id | INTEGER | Parent entity reference — informational "default group" only; report aggregation goes through `idol_memberships` |
| sort_order | INTEGER | Display order |
| display_hint | TEXT | Optional short label rendered next to the name when the same name appears more than once (e.g. `"ITZY"`, `"AKB48 Team A"`) |

> **v1.5 change:** `name` is no longer `UNIQUE`. Two members can share a name as long as they are distinguished by `display_hint` (recommended) and the items that reference them use `items.idol_id` rather than the text name.

### `idol_memberships`
Tracks which group a member belongs to over time. One row per (member, group) period.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| member_id | INTEGER FK | Member entity (`idol_entities.id`, `category='member'`, `ON DELETE CASCADE`) |
| group_id | INTEGER FK | Group/unit/company entity (`ON DELETE CASCADE`) |
| start_date | TEXT | YYYY-MM-DD, NULL = since the beginning |
| end_date | TEXT | YYYY-MM-DD, NULL = currently active |
| is_primary | INTEGER | `1` = main group (counted in By-Group / By-Company reports), `0` = sub-unit / project (shown only in drill-down) |
| note | TEXT | Free-text note (e.g. `"moved from sister group"`) |
| created_at | TEXT | Record creation timestamp |

Report queries pick the membership whose `start_date` ≤ `items.order_date` ≤ `end_date` (open bounds count as ±∞) and prefer rows where `is_primary = 1`.

### `type_categories`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| name | TEXT UNIQUE | Type name |
| description | TEXT | Description |
| sort_order | INTEGER | Display order |

### `budgets` (v6, `period` added in v7)
Monthly spending limits per scope, with a recurring default plus per-month overrides.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| scope_type | TEXT | `overall`, `type`, `group`, `company`, or `member` (CHECK-constrained) |
| scope_ref_id | INTEGER | `idol_entities.id` for group/company/member; NULL for overall/type |
| scope_ref_name | TEXT | Type name when `scope_type='type'`; otherwise a name snapshot of the referenced entity |
| amount | REAL | Monthly limit (฿) |
| warn_pct | INTEGER | Yellow threshold % (default 80) |
| danger_pct | INTEGER | Red threshold % (default 100); enforced `1 ≤ warn_pct ≤ danger_pct` |
| note | TEXT | Optional note |
| is_active | INTEGER | `1` = active (only active budgets are listed / counted) |
| period | TEXT | **v7.** `NULL` = recurring default (every month); `'YYYY-MM'` = override for that month |
| created_at | TEXT | Record creation timestamp |

> **Effective budget** for (scope, month) = the override row (`period = month`) if one exists, else the recurring default (`period IS NULL`). One default per scope and one override per scope per month are enforced in `handleBudgetSave` (HTTP 409 on duplicate).
>
> Spending is computed per calendar month (`strftime('%Y-%m', order_date)`). Group/company/member scopes reuse the same membership-aware joins (`is_primary = 1`, date-bounded) as the By-Group / By-Company / By-Member reports. Colour status: `ok` when `pct < warn_pct`, `near` when `warn_pct ≤ pct < danger_pct`, `over` when `pct ≥ danger_pct`.

### `users`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| username | TEXT UNIQUE | Login username |
| password | TEXT | Bcrypt hashed password |
| display_name | TEXT | Display name |
| role | TEXT | `admin` or `user` |
| last_login | TEXT | Last login timestamp |

### `login_attempts`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| ip | TEXT | Source IP address |
| attempted_at | TEXT | Attempt timestamp |

Records older than 1 hour are purged automatically on each login.

### `schema_meta`
| Column | Type | Description |
|--------|------|-------------|
| key | TEXT PK | e.g. `version` |
| value | TEXT | Schema version number, stamped after each migration |

See [migrations/README.md](migrations/README.md) for the migration system.
