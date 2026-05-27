# Database Schema

### `items`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| order_date | TEXT | Purchase date (YYYY-MM-DD) |
| event_date | TEXT | Event date (YYYY-MM-DD) |
| title | TEXT | Item name |
| idol | TEXT | Idol / group name — immutable snapshot of the name at purchase time |
| idol_id | INTEGER FK | Reference to `idol_entities.id` — canonical relation used by reports (nullable; `ON DELETE SET NULL`) |
| type | TEXT | Item type |
| price_per_qty | REAL | Price per unit |
| qty | INTEGER | Quantity |
| created_at | TEXT | Record creation timestamp |
| updated_at | TEXT | Last update timestamp |

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
